package signaling

import (
	"context"
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"strings"
	"time"

	"github.com/edusfera/media/internal/auth"
	"github.com/edusfera/media/internal/config"
	"github.com/edusfera/media/internal/media"
	"github.com/edusfera/media/internal/room"
	"github.com/edusfera/media/internal/vad"
	"github.com/edusfera/media/internal/whiteboard"
	"github.com/gorilla/websocket"
	"github.com/pion/rtp"
	"github.com/pion/webrtc/v4"
)

var upgrader = websocket.Upgrader{
	CheckOrigin: func(r *http.Request) bool {
		return true // Origin validation is handled in Handler.ServeHTTP.
	},
	ReadBufferSize:  4096,
	WriteBufferSize: 4096,
}

// Handler handles WebSocket connections for WebRTC signaling.
type Handler struct {
	rooms  *room.Manager
	sfu    *media.SFU
	cfg    *config.Config
	logger *slog.Logger
}

// NewHandler creates a new signaling handler.
func NewHandler(rooms *room.Manager, sfu *media.SFU, cfg *config.Config, logger *slog.Logger) *Handler {
	return &Handler{
		rooms:  rooms,
		sfu:    sfu,
		cfg:    cfg,
		logger: logger.With("component", "signaling"),
	}
}

// ServeHTTP upgrades the HTTP connection to WebSocket and starts the signaling loop.
// Path: /ws/{roomId}?token=JWT
func (h *Handler) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	// ----- Extract room ID from URL path -----
	pathParts := strings.Split(strings.TrimRight(r.URL.Path, "/"), "/")
	if len(pathParts) < 2 {
		http.Error(w, `{"error":"invalid path"}`, http.StatusBadRequest)
		return
	}
	roomID := pathParts[len(pathParts)-1]

	// ----- Validate JWT -----
	token := r.URL.Query().Get("token")
	if token == "" {
		http.Error(w, `{"error":"missing token"}`, http.StatusUnauthorized)
		return
	}

	claims, err := auth.ValidateToken(token, h.cfg.JWTSecret)
	if err != nil {
		h.logger.Warn("authentication failed", "error", err, "room", roomID)
		http.Error(w, `{"error":"unauthorized"}`, http.StatusUnauthorized)
		return
	}

	if claims.Room != roomID {
		http.Error(w, `{"error":"room mismatch"}`, http.StatusForbidden)
		return
	}

	// ----- Validate origin -----
	if !h.isOriginAllowed(r.Header.Get("Origin")) {
		http.Error(w, `{"error":"origin not allowed"}`, http.StatusForbidden)
		return
	}

	// ----- Upgrade to WebSocket -----
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		h.logger.Error("websocket upgrade failed", "error", err)
		return
	}

	// ----- Create participant -----
	participantID := generateID()
	p := room.NewParticipant(participantID, claims.Subject, claims.Name, claims.Role, conn)

	// ----- Join room -----
	rm := h.rooms.GetOrCreateRoom(roomID)

	// Initialize VAD detector and state change callback
	p.SetVADDetector(vad.NewDetector(vad.DefaultConfig()))
	p.SetOnSpeakingChanged(func(speaking bool) {
		h.logger.Debug("speaking state changed", "participant", p.ID, "speaking", speaking)
		p.SetSpeaking(speaking)
		rm.Broadcast("", SpeakingMessage{
			Type:     "speaking",
			PeerID:   p.ID,
			Speaking: speaking,
		})
	})

	peers, err := rm.AddParticipant(p)
	if err != nil {
		_ = p.SendJSON(ErrorMessage{Type: "error", Message: err.Error()})
		conn.Close()
		return
	}

	// Send the "joined" confirmation with the list of current peers.
	_ = p.SendJSON(JoinedMessage{
		Type:         "joined",
		SelfID:       participantID,
		Participants: peers,
	})

	// Send whiteboard history if there are any events
	history := rm.Board().History()
	if len(history) > 0 {
		events := make([]interface{}, len(history))
		for i, ev := range history {
			events[i] = ev
		}
		_ = p.SendJSON(WhiteboardHistoryMessage{
			Type:   "wb-history",
			Events: events,
		})
	}

	// ----- Create PeerConnection -----
	pc, err := h.sfu.CreatePeerConnection()
	if err != nil {
		h.logger.Error("failed to create peer connection", "error", err)
		rm.RemoveParticipant(participantID)
		return
	}
	p.SetPeerConnection(pc)

	// Setup WebRTC event handlers.
	ctx, cancel := context.WithCancel(rm.Context())
	h.setupPeerConnection(ctx, p, rm)

	h.logger.Info("participant connected",
		"participant", participantID,
		"room", roomID,
		"user", claims.Subject,
		"name", claims.Name,
		"role", claims.Role,
	)

	// Start the WebSocket read loop in a goroutine.
	go h.readLoop(ctx, cancel, p, rm)
}

// readLoop reads incoming WebSocket messages and dispatches them.
func (h *Handler) readLoop(ctx context.Context, cancel context.CancelFunc, p *room.Participant, rm *room.Room) {
	defer func() {
		cancel()
		h.cleanup(p, rm)
	}()

	conn := p.Conn()
	if conn == nil {
		return
	}

	for {
		select {
		case <-ctx.Done():
			return
		default:
		}

		_, raw, err := conn.ReadMessage()
		if err != nil {
			if websocket.IsUnexpectedCloseError(err,
				websocket.CloseGoingAway,
				websocket.CloseNormalClosure,
				websocket.CloseNoStatusReceived,
			) {
				h.logger.Warn("websocket read error",
					"participant", p.ID,
					"error", err,
				)
			}
			return
		}

		var msg IncomingMessage
		if err := json.Unmarshal(raw, &msg); err != nil {
			_ = p.SendJSON(ErrorMessage{Type: "error", Message: "invalid message format"})
			continue
		}

		h.handleMessage(ctx, p, rm, msg)
	}
}

// handleMessage routes a decoded message to the appropriate handler.
func (h *Handler) handleMessage(ctx context.Context, p *room.Participant, rm *room.Room, msg IncomingMessage) {
	switch msg.Type {
	case "offer":
		h.handleOffer(p, rm, msg)
	case "answer":
		h.handleAnswer(p, msg)
	case "ice":
		h.handleICE(p, msg)
	case "media-toggle":
		h.handleMediaToggle(p, rm, msg)
	case "wb-toggle":
		h.handleWhiteboardToggle(p, rm, msg)
	case "wb":
		h.handleWhiteboard(p, rm, msg)
	case "chat":
		h.handleChat(p, rm, msg)
	case "screen-share-start":
		h.logger.Debug("screen share started", "participant", p.ID)
	case "screen-share-stop":
		h.logger.Debug("screen share stopped", "participant", p.ID)
	default:
		_ = p.SendJSON(ErrorMessage{
			Type:    "error",
			Message: fmt.Sprintf("unknown message type: %s", msg.Type),
		})
	}
}

// =========================================================================
// SDP & ICE handlers
// =========================================================================

func (h *Handler) handleOffer(p *room.Participant, rm *room.Room, msg IncomingMessage) {
	pc := p.PeerConnection()
	if pc == nil {
		return
	}

	offer := webrtc.SessionDescription{
		Type: webrtc.SDPTypeOffer,
		SDP:  msg.SDP,
	}

	if err := pc.SetRemoteDescription(offer); err != nil {
		h.logger.Error("failed to set remote description (offer)",
			"participant", p.ID,
			"error", err,
		)
		_ = p.SendJSON(ErrorMessage{Type: "error", Message: "failed to process offer"})
		return
	}

	answer, err := pc.CreateAnswer(nil)
	if err != nil {
		h.logger.Error("failed to create answer",
			"participant", p.ID,
			"error", err,
		)
		_ = p.SendJSON(ErrorMessage{Type: "error", Message: "failed to create answer"})
		return
	}

	if err := pc.SetLocalDescription(answer); err != nil {
		h.logger.Error("failed to set local description (answer)",
			"participant", p.ID,
			"error", err,
		)
		return
	}

	_ = p.SendJSON(SDPMessage{
		Type: "answer",
		SDP:  answer.SDP,
	})

	// After the first successful offer/answer exchange, forward existing
	// tracks from other participants and trigger renegotiation.
	if p.MarkInitialNegotiationDone() {
		h.addExistingTracks(p, rm)
	}
}

func (h *Handler) handleAnswer(p *room.Participant, msg IncomingMessage) {
	pc := p.PeerConnection()
	if pc == nil {
		return
	}

	answer := webrtc.SessionDescription{
		Type: webrtc.SDPTypeAnswer,
		SDP:  msg.SDP,
	}

	if err := pc.SetRemoteDescription(answer); err != nil {
		h.logger.Error("failed to set remote description (answer)",
			"participant", p.ID,
			"error", err,
		)
		return
	}

	// Process any pending renegotiation that was deferred while negotiation
	// was in progress.
	if p.CheckAndClearRenegotiation() {
		h.negotiate(p)
	}
}

func (h *Handler) handleICE(p *room.Participant, msg IncomingMessage) {
	pc := p.PeerConnection()
	if pc == nil {
		return
	}

	var candidate webrtc.ICECandidateInit
	if err := json.Unmarshal(msg.Candidate, &candidate); err != nil {
		h.logger.Debug("failed to parse ICE candidate",
			"participant", p.ID,
			"error", err,
		)
		return
	}

	if err := pc.AddICECandidate(candidate); err != nil {
		h.logger.Debug("failed to add ICE candidate",
			"participant", p.ID,
			"error", err,
		)
	}
}

// =========================================================================
// Feature handlers
// =========================================================================

func (h *Handler) handleMediaToggle(p *room.Participant, rm *room.Room, msg IncomingMessage) {
	if msg.Kind == "" || msg.Enabled == nil {
		return
	}

	p.SetMediaState(msg.Kind, *msg.Enabled)
	audio, video := p.MediaState()

	rm.Broadcast(p.ID, MediaStateMessage{
		Type:   "media-state",
		PeerID: p.ID,
		Audio:  audio,
		Video:  video,
	})
}

func (h *Handler) handleWhiteboardToggle(p *room.Participant, rm *room.Room, msg IncomingMessage) {
	if msg.Enabled == nil {
		return
	}

	rm.Broadcast(p.ID, WhiteboardToggleMessage{
		Type:     "wb-toggle",
		IsActive: *msg.Enabled,
	})
}

func (h *Handler) handleWhiteboard(p *room.Participant, rm *room.Room, msg IncomingMessage) {
	evt := whiteboard.Event{
		Action: msg.Action,
		Data:   msg.Data,
		FromID: p.ID,
	}

	rm.Board().AddEvent(evt)

	rm.Broadcast(p.ID, WhiteboardMessage{
		Type:   "wb",
		FromID: p.ID,
		Action: msg.Action,
		Data:   msg.Data,
	})
}

func (h *Handler) handleChat(p *room.Participant, rm *room.Room, msg IncomingMessage) {
	if msg.Message == "" {
		return
	}

	rm.Broadcast(p.ID, ChatMessage{
		Type:      "chat",
		FromID:    p.ID,
		Name:      p.Name,
		Message:   msg.Message,
		Timestamp: time.Now().UTC().Format(time.RFC3339),
	})
}

// =========================================================================
// WebRTC PeerConnection setup
// =========================================================================

// setupPeerConnection wires up OnTrack, OnICECandidate, and OnConnectionStateChange
// for a participant's PeerConnection.
func (h *Handler) setupPeerConnection(ctx context.Context, p *room.Participant, rm *room.Room) {
	pc := p.PeerConnection()

	// --- Handle incoming tracks (media published by this participant) ---
	pc.OnTrack(func(remoteTrack *webrtc.TrackRemote, receiver *webrtc.RTPReceiver) {
		h.logger.Info("track received",
			"participant", p.ID,
			"trackID", remoteTrack.ID(),
			"kind", remoteTrack.Kind().String(),
			"streamID", remoteTrack.StreamID(),
		)

		// Create a local track copy for forwarding to other participants.
		localTrack, err := media.CreateLocalTrack(remoteTrack, p.ID)
		if err != nil {
			h.logger.Error("failed to create local track",
				"participant", p.ID,
				"error", err,
			)
			return
		}

		p.AddPublishedTrack(remoteTrack.ID(), localTrack)

		// Add this track to every other participant's PeerConnection.
		others := rm.GetOtherParticipants(p.ID)
		for _, other := range others {
			h.addTrackToParticipant(other, p.ID, remoteTrack.ID(), localTrack)
		}

		var monitor media.VADMonitor
		if remoteTrack.Kind() == webrtc.RTPCodecTypeAudio {
			extID := 0
			for _, ext := range receiver.GetParameters().HeaderExtensions {
				if ext.URI == "urn:ietf:params:rtp-hdrext:ssrc-audio-level" {
					extID = ext.ID
					break
				}
			}
			if extID != 0 {
				monitor = &participantVAD{
					participant: p,
					extID:       extID,
				}
			}
		}

		// Start forwarding RTP packets from remote → local.
		go media.ForwardTrack(ctx, remoteTrack, localTrack, h.logger, monitor)
	})

	// --- Send ICE candidates generated by the SFU back to the client ---
	pc.OnICECandidate(func(c *webrtc.ICECandidate) {
		if c == nil {
			return
		}
		candidateJSON, err := json.Marshal(c.ToJSON())
		if err != nil {
			return
		}
		_ = p.SendJSON(ICEMessage{
			Type:      "ice",
			Candidate: candidateJSON,
		})
	})

	// --- Monitor connection state ---
	pc.OnConnectionStateChange(func(state webrtc.PeerConnectionState) {
		h.logger.Debug("peer connection state changed",
			"participant", p.ID,
			"state", state.String(),
		)
	})
}

// =========================================================================
// Track management
// =========================================================================

// addTrackToParticipant adds a local track to the target participant's
// PeerConnection and triggers renegotiation.
func (h *Handler) addTrackToParticipant(
	target *room.Participant,
	sourceID, trackID string,
	track *webrtc.TrackLocalStaticRTP,
) {
	pc := target.PeerConnection()
	if pc == nil {
		return
	}

	sender, err := pc.AddTrack(track)
	if err != nil {
		h.logger.Error("failed to add track to participant",
			"target", target.ID,
			"source", sourceID,
			"trackID", trackID,
			"error", err,
		)
		return
	}

	key := sourceID + ":" + trackID
	target.AddInboundSender(key, sender)

	// Read and discard RTCP feedback (required by WebRTC spec).
	go func() {
		rtcpBuf := make([]byte, 1500)
		for {
			if _, _, rtcpErr := sender.Read(rtcpBuf); rtcpErr != nil {
				return
			}
		}
	}()

	h.negotiate(target)
}

// addExistingTracks adds all currently published tracks from other participants
// to a newly joined participant's PeerConnection, then triggers a single renegotiation.
func (h *Handler) addExistingTracks(p *room.Participant, rm *room.Room) {
	pc := p.PeerConnection()
	if pc == nil {
		return
	}

	others := rm.GetOtherParticipants(p.ID)
	added := false

	for _, other := range others {
		tracks := other.PublishedTracks()
		for trackID, localTrack := range tracks {
			sender, err := pc.AddTrack(localTrack)
			if err != nil {
				h.logger.Error("failed to add existing track",
					"participant", p.ID,
					"source", other.ID,
					"trackID", trackID,
					"error", err,
				)
				continue
			}

			key := other.ID + ":" + trackID
			p.AddInboundSender(key, sender)

			// Read and discard RTCP.
			go func(s *webrtc.RTPSender) {
				rtcpBuf := make([]byte, 1500)
				for {
					if _, _, rtcpErr := s.Read(rtcpBuf); rtcpErr != nil {
						return
					}
				}
			}(sender)

			added = true
		}
	}

	if added {
		h.negotiate(p)
	}
}

// negotiate initiates SDP renegotiation with a participant by creating
// and sending a new offer. If the PeerConnection is not in a stable state,
// renegotiation is deferred.
func (h *Handler) negotiate(p *room.Participant) {
	pc := p.PeerConnection()
	if pc == nil {
		return
	}

	// Only renegotiate when the signaling state is stable.
	if pc.SignalingState() != webrtc.SignalingStateStable {
		p.MarkNeedsRenegotiation()
		return
	}

	offer, err := pc.CreateOffer(nil)
	if err != nil {
		h.logger.Error("failed to create renegotiation offer",
			"participant", p.ID,
			"error", err,
		)
		return
	}

	if err := pc.SetLocalDescription(offer); err != nil {
		h.logger.Error("failed to set local description for renegotiation",
			"participant", p.ID,
			"error", err,
		)
		return
	}

	_ = p.SendJSON(SDPMessage{
		Type: "offer",
		SDP:  offer.SDP,
	})
}

// =========================================================================
// Cleanup
// =========================================================================

// cleanup removes a participant's tracks from all other participants'
// PeerConnections and removes the participant from the room.
func (h *Handler) cleanup(p *room.Participant, rm *room.Room) {
	h.logger.Info("cleaning up participant", "participant", p.ID)

	// Remove this participant's published tracks from every other peer.
	publishedTracks := p.PublishedTracks()
	others := rm.GetOtherParticipants(p.ID)

	for _, other := range others {
		otherPC := other.PeerConnection()
		if otherPC == nil {
			continue
		}

		needsReneg := false
		for trackID := range publishedTracks {
			key := p.ID + ":" + trackID
			sender := other.RemoveInboundSender(key)
			if sender != nil {
				if err := otherPC.RemoveTrack(sender); err != nil {
					h.logger.Error("failed to remove track from peer",
						"peer", other.ID,
						"trackID", trackID,
						"error", err,
					)
				} else {
					needsReneg = true
				}
			}
		}

		if needsReneg {
			h.negotiate(other)
		}
	}

	rm.RemoveParticipant(p.ID)
}

// =========================================================================
// Helpers
// =========================================================================

// isOriginAllowed checks whether the given origin is in the allowed list.
func (h *Handler) isOriginAllowed(origin string) bool {
	// Allow empty origin (non-browser clients, curl, etc.).
	if origin == "" {
		return true
	}

	for _, allowed := range h.cfg.AllowedOrigins {
		if allowed == "*" || allowed == origin {
			return true
		}
	}

	return false
}

// generateID produces a random 16-character hex participant identifier.
func generateID() string {
	b := make([]byte, 8)
	if _, err := rand.Read(b); err != nil {
		return fmt.Sprintf("p_%d", time.Now().UnixNano())
	}
	return fmt.Sprintf("p_%s", hex.EncodeToString(b))
}

type participantVAD struct {
	participant *room.Participant
	extID       int
}

func (v *participantVAD) FeedRTPHeader(hdr *rtp.Header) bool {
	res := vad.ExtractAudioLevel(hdr, v.extID)
	if res.Present {
		v.participant.FeedAudioLevel(res.Level)
	}
	return false
}
