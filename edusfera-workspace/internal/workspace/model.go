package workspace

import "encoding/json"

type CardType string

const (
	CardTypeNote      CardType = "note"
	CardTypeChecklist CardType = "checklist"
	CardTypeCode      CardType = "code"
	CardTypeTimer     CardType = "timer"
	CardTypeMedia     CardType = "media"
)

type ChecklistItem struct {
	ID        string `json:"id"`
	Text      string `json:"text"`
	Completed bool   `json:"completed"`
}

type Card struct {
	ID      string          `json:"id"`
	Type    CardType        `json:"type"`
	Title   string          `json:"title"`
	Content string          `json:"content,omitempty"`
	Items   []ChecklistItem `json:"items,omitempty"`
	Meta    json.RawMessage `json:"meta,omitempty"` // For timer, code languages, etc.
}

type Column struct {
	ID    string `json:"id"`
	Title string `json:"title"`
	Cards []Card `json:"cards"`
}

type Board struct {
	Columns []Column `json:"columns"`
}

type WsMessage struct {
	Event   string          `json:"event"`
	Payload json.RawMessage `json:"payload"`
}

type AIAction struct {
	Action  string          `json:"action"`
	Payload json.RawMessage `json:"payload"`
}

type AIPatchRequest struct {
	Actions []AIAction `json:"actions"`
}
