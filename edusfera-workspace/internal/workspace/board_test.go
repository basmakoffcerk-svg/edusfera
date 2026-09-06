package workspace

import (
	"encoding/json"
	"testing"
)

func TestBoard_AddColumn(t *testing.T) {
	board := &Board{Columns: []Column{}}

	board.addColumn("col-1", "To Do")
	if len(board.Columns) != 1 {
		t.Fatalf("expected 1 column, got %d", len(board.Columns))
	}
	if board.Columns[0].ID != "col-1" || board.Columns[0].Title != "To Do" {
		t.Errorf("unexpected column: %+v", board.Columns[0])
	}

	// Double add should be ignored
	board.addColumn("col-1", "Duplicate")
	if len(board.Columns) != 1 {
		t.Fatalf("expected duplicate column to be ignored, total columns: %d", len(board.Columns))
	}
}

func TestBoard_MoveColumn(t *testing.T) {
	board := &Board{
		Columns: []Column{
			{ID: "col-1", Title: "To Do", Cards: []Card{}},
			{ID: "col-2", Title: "In Progress", Cards: []Card{}},
			{ID: "col-3", Title: "Done", Cards: []Card{}},
		},
	}

	// Move col-1 to index 1
	board.moveColumn("col-1", 1)
	if board.Columns[1].ID != "col-1" {
		t.Errorf("expected col-1 at index 1, got %s", board.Columns[1].ID)
	}
	if board.Columns[0].ID != "col-2" {
		t.Errorf("expected col-2 at index 0, got %s", board.Columns[0].ID)
	}

	// Move col-3 to index 0
	board.moveColumn("col-3", 0)
	if board.Columns[0].ID != "col-3" {
		t.Errorf("expected col-3 at index 0, got %s", board.Columns[0].ID)
	}
}

func TestBoard_DeleteColumn(t *testing.T) {
	board := &Board{
		Columns: []Column{
			{ID: "col-1", Title: "To Do", Cards: []Card{}},
			{ID: "col-2", Title: "In Progress", Cards: []Card{}},
		},
	}

	board.deleteColumn("col-1")
	if len(board.Columns) != 1 {
		t.Fatalf("expected 1 column after deletion, got %d", len(board.Columns))
	}
	if board.Columns[0].ID != "col-2" {
		t.Errorf("expected remaining column to be col-2, got %s", board.Columns[0].ID)
	}
}

func TestBoard_AddCard(t *testing.T) {
	board := &Board{
		Columns: []Column{
			{ID: "col-1", Title: "To Do", Cards: []Card{}},
		},
	}

	card := Card{
		ID:    "card-1",
		Type:  CardTypeNote,
		Title: "Test Note",
	}

	board.addCard("col-1", card)
	if len(board.Columns[0].Cards) != 1 {
		t.Fatalf("expected 1 card in column, got %d", len(board.Columns[0].Cards))
	}
	if board.Columns[0].Cards[0].ID != "card-1" {
		t.Errorf("unexpected card ID: %s", board.Columns[0].Cards[0].ID)
	}

	// Double add in same column should be ignored
	board.addCard("col-1", card)
	if len(board.Columns[0].Cards) != 1 {
		t.Errorf("expected duplicate card to be ignored, got %d cards", len(board.Columns[0].Cards))
	}
}

func TestBoard_UpdateCard(t *testing.T) {
	board := &Board{
		Columns: []Column{
			{
				ID: "col-1",
				Cards: []Card{
					{
						ID:    "card-1",
						Type:  CardTypeNote,
						Title: "Old Title",
						Content: "Old Content",
					},
				},
			},
		},
	}

	titleUpdate := "New Title"
	contentUpdate := "New Content"
	metaUpdate := json.RawMessage(`{"lang":"javascript"}`)
	updates := struct {
		Title   *string          `json:"title"`
		Content *string          `json:"content"`
		Meta    *json.RawMessage `json:"meta"`
	}{
		Title:   &titleUpdate,
		Content: &contentUpdate,
		Meta:    &metaUpdate,
	}

	payload, _ := json.Marshal(updates)
	board.updateCard("card-1", payload)

	card := board.Columns[0].Cards[0]
	if card.Title != "New Title" {
		t.Errorf("expected Title 'New Title', got '%s'", card.Title)
	}
	if card.Content != "New Content" {
		t.Errorf("expected Content 'New Content', got '%s'", card.Content)
	}
	if string(card.Meta) != `{"lang":"javascript"}` {
		t.Errorf("expected Meta '{\"lang\":\"javascript\"}', got '%s'", string(card.Meta))
	}
}

func TestBoard_MoveCard(t *testing.T) {
	board := &Board{
		Columns: []Column{
			{
				ID: "col-1",
				Cards: []Card{
					{ID: "card-1", Title: "Card 1"},
					{ID: "card-2", Title: "Card 2"},
				},
			},
			{
				ID: "col-2",
				Cards: []Card{
					{ID: "card-3", Title: "Card 3"},
				},
			},
		},
	}

	// Move card-1 from col-1 to col-2 at index 0
	board.moveCard("card-1", "col-1", "col-2", 0)

	if len(board.Columns[0].Cards) != 1 {
		t.Errorf("expected 1 card left in col-1, got %d", len(board.Columns[0].Cards))
	}
	if board.Columns[0].Cards[0].ID != "card-2" {
		t.Errorf("expected col-1 remaining card to be card-2, got %s", board.Columns[0].Cards[0].ID)
	}

	if len(board.Columns[1].Cards) != 2 {
		t.Errorf("expected 2 cards in col-2, got %d", len(board.Columns[1].Cards))
	}
	if board.Columns[1].Cards[0].ID != "card-1" {
		t.Errorf("expected card-1 at index 0 of col-2, got %s", board.Columns[1].Cards[0].ID)
	}
}

func TestBoard_DeleteCard(t *testing.T) {
	board := &Board{
		Columns: []Column{
			{
				ID: "col-1",
				Cards: []Card{
					{ID: "card-1", Title: "Card 1"},
				},
			},
		},
	}

	board.deleteCard("card-1", "col-1")
	if len(board.Columns[0].Cards) != 0 {
		t.Errorf("expected 0 cards in col-1, got %d", len(board.Columns[0].Cards))
	}
}
