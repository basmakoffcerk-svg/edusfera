// Package whiteboard provides synchronized whiteboard state management.
package whiteboard

import (
	"encoding/json"
	"sync"
)

// Event represents a single whiteboard action (draw, clear, undo).
type Event struct {
	Action string          `json:"action"`
	Data   json.RawMessage `json:"data"`
	FromID string          `json:"fromId"`
}

// Board maintains an ordered history of whiteboard events for a room.
// It is safe for concurrent use.
type Board struct {
	mu     sync.Mutex
	events []Event
}

// NewBoard creates a new empty whiteboard.
func NewBoard() *Board {
	return &Board{
		events: make([]Event, 0, 64),
	}
}

// AddEvent appends a new event to the history.
// Special actions "clear" and "undo" modify the history accordingly.
func (b *Board) AddEvent(evt Event) {
	b.mu.Lock()
	defer b.mu.Unlock()

	switch evt.Action {
	case "clear":
		b.events = b.events[:0]
	case "undo":
		if len(b.events) > 0 {
			b.events = b.events[:len(b.events)-1]
		}
	default:
		b.events = append(b.events, evt)
	}
}

// History returns a snapshot copy of all recorded events.
func (b *Board) History() []Event {
	b.mu.Lock()
	defer b.mu.Unlock()

	out := make([]Event, len(b.events))
	copy(out, b.events)
	return out
}

// Len returns the current number of events.
func (b *Board) Len() int {
	b.mu.Lock()
	defer b.mu.Unlock()
	return len(b.events)
}
