// Package signaling defines all WebSocket message types for client-server communication.
package signaling

import (
	"encoding/json"

	"github.com/edusfera/media/internal/room"
)

// ---------------------------------------------------------------------------
// Client → Server messages
// ---------------------------------------------------------------------------

// IncomingMessage is the unified envelope for all client-to-server messages.
type IncomingMessage struct {
	Type      string          `json:"type"`
	Token     string          `json:"token,omitempty"`
	TargetID  string          `json:"targetId,omitempty"`
	SDP       string          `json:"sdp,omitempty"`
	Candidate json.RawMessage `json:"candidate,omitempty"`
	Kind      string          `json:"kind,omitempty"`
	Enabled   *bool           `json:"enabled,omitempty"`
	Action    string          `json:"action,omitempty"`
	Data      json.RawMessage `json:"data,omitempty"`
	Message   string          `json:"message,omitempty"`
}

// ---------------------------------------------------------------------------
// Server → Client messages
// ---------------------------------------------------------------------------

// JoinedMessage is sent to the participant who just joined the room.
type JoinedMessage struct {
	Type         string              `json:"type"`
	SelfID       string              `json:"selfId"`
	Participants []room.ParticipantInfo `json:"participants"`
}



// PeerJoinedMessage notifies existing participants about a newcomer.
type PeerJoinedMessage struct {
	Type   string `json:"type"`
	PeerID string `json:"peerId"`
	Role   string `json:"role"`
	Name   string `json:"name"`
}

// PeerLeftMessage notifies remaining participants that someone left.
type PeerLeftMessage struct {
	Type   string `json:"type"`
	PeerID string `json:"peerId"`
}

// SDPMessage carries an SDP offer or answer.
type SDPMessage struct {
	Type   string `json:"type"`
	FromID string `json:"fromId,omitempty"`
	SDP    string `json:"sdp"`
}

// ICEMessage carries an ICE candidate.
type ICEMessage struct {
	Type      string          `json:"type"`
	FromID    string          `json:"fromId,omitempty"`
	Candidate json.RawMessage `json:"candidate"`
}

// MediaStateMessage informs peers about a participant's audio/video toggle.
type MediaStateMessage struct {
	Type   string `json:"type"`
	PeerID string `json:"peerId"`
	Audio  bool   `json:"audio"`
	Video  bool   `json:"video"`
}

// SpeakingMessage notifies peers about a participant's speaking state.
type SpeakingMessage struct {
	Type     string `json:"type"`
	PeerID   string `json:"peerId"`
	Speaking bool   `json:"speaking"`
}

// WhiteboardMessage carries a whiteboard event.
type WhiteboardMessage struct {
	Type   string          `json:"type"`
	FromID string          `json:"fromId"`
	Action string          `json:"action"`
	Data   json.RawMessage `json:"data"`
}

// WhiteboardHistoryMessage carries the initial state of the whiteboard.
type WhiteboardHistoryMessage struct {
	Type   string        `json:"type"`
	Events []interface{} `json:"events"`
}

// WhiteboardToggleMessage carries the active state of the whiteboard.
type WhiteboardToggleMessage struct {
	Type     string `json:"type"`
	IsActive bool   `json:"isActive"`
}

// ChatMessage carries a chat text message.
type ChatMessage struct {
	Type      string `json:"type"`
	FromID    string `json:"fromId"`
	Name      string `json:"name"`
	Message   string `json:"message"`
	Timestamp string `json:"timestamp"`
}

// ErrorMessage reports an error to the client.
type ErrorMessage struct {
	Type    string `json:"type"`
	Message string `json:"message"`
}

// RoomClosedMessage notifies all participants that the room has been closed.
type RoomClosedMessage struct {
	Type string `json:"type"`
}
