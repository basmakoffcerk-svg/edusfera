package room

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net/http"
	"sync"
	"time"

	"github.com/edusfera/media/internal/whiteboard"
)

// ParticipantInfo describes a participant currently in the room (sent over WebSocket).
type ParticipantInfo struct {
	ID    string `json:"id"`
	Role  string `json:"role"`
	Name  string `json:"name"`
	Audio bool   `json:"audio"`
	Video bool   `json:"video"`
}

type peerJoinedMsg struct {
	Type   string `json:"type"`
	PeerID string `json:"peerId"`
	Role   string `json:"role"`
	Name   string `json:"name"`
}

type peerLeftMsg struct {
	Type   string `json:"type"`
	PeerID string `json:"peerId"`
}

type roomClosedMsg struct {
	Type string `json:"type"`
}

// ErrRoomFull is returned when a participant tries to join a room that has
// already reached its maximum capacity.
var ErrRoomFull = errors.New("room is full")

// Room represents a video-call room with multiple participants.
type Room struct {
	ID string

	mu           sync.RWMutex
	participants map[string]*Participant
	maxSize      int

	board *whiteboard.Board

	ctx    context.Context
	cancel context.CancelFunc

	idleTimer   *time.Timer
	idleTimeout time.Duration

	logger  *slog.Logger
	onEmpty func(roomID string)
	
	laravelAPIUrl string
	jwtSecret     string
}

// NewRoom creates a room that will invoke onEmpty when it becomes idle
// for longer than idleTimeout.
func NewRoom(id string, maxSize int, idleTimeout time.Duration, logger *slog.Logger, onEmpty func(string), laravelAPIUrl string, jwtSecret string) *Room {
	ctx, cancel := context.WithCancel(context.Background())
	return &Room{
		ID:            id,
		participants:  make(map[string]*Participant),
		maxSize:       maxSize,
		board:         whiteboard.NewBoard(),
		ctx:           ctx,
		cancel:        cancel,
		idleTimeout:   idleTimeout,
		logger:        logger.With("room", id),
		onEmpty:       onEmpty,
		laravelAPIUrl: laravelAPIUrl,
		jwtSecret:     jwtSecret,
	}
}

// AddParticipant adds a participant to the room and notifies existing peers.
// Returns the list of current participants (before the newcomer was added)
// so the newcomer can be informed about who is already present.
func (r *Room) AddParticipant(p *Participant) ([]ParticipantInfo, error) {
	r.mu.Lock()
	defer r.mu.Unlock()

	if len(r.participants) >= r.maxSize {
		return nil, ErrRoomFull
	}

	// Stop idle timer since someone is joining.
	if r.idleTimer != nil {
		r.idleTimer.Stop()
		r.idleTimer = nil
	}

	// Build participant list for the newcomer.
	peers := make([]ParticipantInfo, 0, len(r.participants))
	for _, existing := range r.participants {
		audio, video := existing.MediaState()
		peers = append(peers, ParticipantInfo{
			ID:    existing.ID,
			Role:  existing.Role,
			Name:  existing.Name,
			Audio: audio,
			Video: video,
		})
	}

	// Notify existing participants about the newcomer.
	for _, existing := range r.participants {
		_ = existing.SendJSON(peerJoinedMsg{
			Type:   "peer-joined",
			PeerID: p.ID,
			Role:   p.Role,
			Name:   p.Name,
		})
	}

	r.participants[p.ID] = p

	r.logger.Info("participant joined",
		"participant", p.ID,
		"name", p.Name,
		"role", p.Role,
		"total", len(r.participants),
	)

	return peers, nil
}

// RemoveParticipant removes a participant, notifies remaining peers,
// and starts the idle timer if the room is now empty.
func (r *Room) RemoveParticipant(participantID string) {
	r.mu.Lock()
	p, ok := r.participants[participantID]
	if !ok {
		r.mu.Unlock()
		return
	}
	delete(r.participants, participantID)

	// Notify remaining participants.
	for _, other := range r.participants {
		_ = other.SendJSON(peerLeftMsg{
			Type:   "peer-left",
			PeerID: participantID,
		})
	}

	remaining := len(r.participants)
	r.mu.Unlock()

	p.Close()

	r.logger.Info("participant left",
		"participant", participantID,
		"remaining", remaining,
	)

	if remaining == 0 {
		r.startIdleTimer()
	}
}

// startIdleTimer begins the countdown to room removal.
func (r *Room) startIdleTimer() {
	r.mu.Lock()
	defer r.mu.Unlock()

	if r.idleTimer != nil {
		r.idleTimer.Stop()
	}

	r.idleTimer = time.AfterFunc(r.idleTimeout, func() {
		r.logger.Info("room idle timeout reached, removing room")
		if r.onEmpty != nil {
			r.onEmpty(r.ID)
		}
	})
}

// GetParticipant returns the participant by ID, or nil.
func (r *Room) GetParticipant(id string) *Participant {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return r.participants[id]
}

// GetOtherParticipants returns all participants except the one with excludeID.
func (r *Room) GetOtherParticipants(excludeID string) []*Participant {
	r.mu.RLock()
	defer r.mu.RUnlock()
	others := make([]*Participant, 0, len(r.participants))
	for id, p := range r.participants {
		if id != excludeID {
			others = append(others, p)
		}
	}
	return others
}

// Broadcast sends a JSON message to every participant except the sender.
func (r *Room) Broadcast(senderID string, msg interface{}) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	for id, p := range r.participants {
		if id != senderID {
			_ = p.SendJSON(msg)
		}
	}
}

// BroadcastAll sends a JSON message to every participant including the sender.
func (r *Room) BroadcastAll(msg interface{}) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	for _, p := range r.participants {
		_ = p.SendJSON(msg)
	}
}

// ParticipantCount returns the number of currently connected participants.
func (r *Room) ParticipantCount() int {
	r.mu.RLock()
	defer r.mu.RUnlock()
	return len(r.participants)
}

// Board returns the room's whiteboard.
func (r *Room) Board() *whiteboard.Board {
	return r.board
}

// Context returns the room's context (cancelled when the room is closed).
func (r *Room) Context() context.Context {
	return r.ctx
}

// ForceClose broadcasts a room-closed event, closes all participants,
// and cancels the room context. Used when the tutor force-closes the room.
func (r *Room) ForceClose() {
	r.BroadcastAll(roomClosedMsg{Type: "room-closed"})

	r.mu.Lock()
	for id, p := range r.participants {
		p.Close()
		delete(r.participants, id)
	}
	if r.idleTimer != nil {
		r.idleTimer.Stop()
		r.idleTimer = nil
	}
	r.mu.Unlock()

	r.saveWhiteboardState()

	r.cancel()
	r.logger.Info("room force-closed")
}

// Cancel cancels the room context.
func (r *Room) Cancel() {
	r.saveWhiteboardState()
	r.cancel()
}

// saveWhiteboardState sends the board history to Laravel if it's not empty.
func (r *Room) saveWhiteboardState() {
	if r.board.Len() == 0 || r.laravelAPIUrl == "" {
		return
	}

	history := r.board.History()
	data, err := json.Marshal(history)
	if err != nil {
		r.logger.Error("failed to marshal whiteboard history", "error", err)
		return
	}

	url := fmt.Sprintf("%s/api/internal/classroom/%s/whiteboard", r.laravelAPIUrl, r.ID)
	req, err := http.NewRequestWithContext(context.Background(), http.MethodPost, url, bytes.NewReader(data))
	if err != nil {
		r.logger.Error("failed to create request for whiteboard state", "error", err)
		return
	}

	req.Header.Set("Content-Type", "application/json")
	if r.jwtSecret != "" {
		req.Header.Set("Authorization", "Bearer "+r.jwtSecret) // Simple internal auth
	}

	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		r.logger.Error("failed to save whiteboard state", "error", err)
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		r.logger.Error("laravel API returned error for whiteboard state", "status", resp.StatusCode)
		return
	}

	r.logger.Info("whiteboard state saved to laravel")
}
