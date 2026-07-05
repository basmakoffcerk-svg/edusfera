package workspace

import (
	"context"
	"encoding/json"
	"fmt"
	"log/slog"
	"time"

	"edusfera-workspace/internal/db"
	"edusfera-workspace/internal/redis"
)

type Manager struct {
	db     *db.DB
	rdb    *redis.RedisClient
	logger *slog.Logger
}

func NewManager(db *db.DB, rdb *redis.RedisClient, logger *slog.Logger) *Manager {
	return &Manager{
		db:     db,
		rdb:    rdb,
		logger: logger,
	}
}

func (m *Manager) LoadBoard(ctx context.Context, roomId string) (*Board, error) {
	// 1. Try Redis cache
	stateJSON, err := m.rdb.GetState(ctx, roomId)
	if err == nil && stateJSON != "" {
		var board Board
		if err := json.Unmarshal([]byte(stateJSON), &board); err == nil {
			return &board, nil
		}
	}

	// 2. Try PostgreSQL
	var boardJSON []byte
	query := `
		SELECT board_state 
		FROM classroom_workspaces cw
		JOIN classroom_sessions cs ON cw.classroom_session_id = cs.id
		WHERE cs.room_id = $1
	`
	err = m.db.Pool.QueryRow(ctx, query, roomId).Scan(&boardJSON)
	if err == nil && len(boardJSON) > 0 {
		var board Board
		if err := json.Unmarshal(boardJSON, &board); err == nil {
			// Cache in Redis
			_ = m.rdb.SetState(ctx, roomId, string(boardJSON), 24*time.Hour)
			return &board, nil
		}
	}

	// Return empty board if none found
	return &Board{Columns: []Column{}}, nil
}

func (m *Manager) SaveBoard(ctx context.Context, roomId string, board *Board) error {
	boardJSON, err := json.Marshal(board)
	if err != nil {
		return fmt.Errorf("failed to serialize board: %w", err)
	}

	// 1. Save in Redis
	err = m.rdb.SetState(ctx, roomId, string(boardJSON), 24*time.Hour)
	if err != nil {
		m.logger.Error("failed to save board in Redis", "roomId", roomId, "error", err)
	}

	// 2. Save in PostgreSQL
	// We lookup classroom_session_id from room_id first
	var sessionId int64
	sessionQuery := `SELECT id FROM classroom_sessions WHERE room_id = $1`
	err = m.db.Pool.QueryRow(ctx, sessionQuery, roomId).Scan(&sessionId)
	if err != nil {
		return fmt.Errorf("classroom session not found for room_id %s: %w", roomId, err)
	}

	upsertQuery := `
		INSERT INTO classroom_workspaces (classroom_session_id, board_state, created_at, updated_at)
		VALUES ($1, $2, NOW(), NOW())
		ON CONFLICT (classroom_session_id) 
		DO UPDATE SET board_state = EXCLUDED.board_state, updated_at = NOW()
	`
	_, err = m.db.Pool.Exec(ctx, upsertQuery, sessionId, boardJSON)
	if err != nil {
		return fmt.Errorf("failed to save board in PostgreSQL: %w", err)
	}

	m.logger.Debug("saved board successfully", "roomId", roomId)
	return nil
}

func (m *Manager) ApplyMutation(ctx context.Context, roomId string, action string, payloadRaw []byte) (*Board, error) {
	board, err := m.LoadBoard(ctx, roomId)
	if err != nil {
		return nil, err
	}

	mutated := false

	switch action {
	case "column.add":
		var p struct {
			ColumnID string `json:"columnId"`
			Title    string `json:"title"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.ColumnID != "" {
			board.addColumn(p.ColumnID, p.Title)
			mutated = true
		}

	case "column.move":
		var p struct {
			ColumnID string `json:"columnId"`
			Index    int    `json:"index"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.ColumnID != "" {
			board.moveColumn(p.ColumnID, p.Index)
			mutated = true
		}

	case "column.delete":
		var p struct {
			ColumnID string `json:"columnId"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.ColumnID != "" {
			board.deleteColumn(p.ColumnID)
			mutated = true
		}

	case "card.add":
		var p struct {
			ColumnID string `json:"columnId"`
			Card     Card   `json:"card"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.ColumnID != "" && p.Card.ID != "" {
			board.addCard(p.ColumnID, p.Card)
			mutated = true
		}

	case "card.update":
		var p struct {
			CardID string          `json:"cardId"`
			Data   json.RawMessage `json:"data"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.CardID != "" {
			board.updateCard(p.CardID, p.Data)
			mutated = true
		}

	case "card.move":
		var p struct {
			CardID       string `json:"cardId"`
			FromColumnID string `json:"fromColumnId"`
			ToColumnID   string `json:"toColumnId"`
			Index        int    `json:"index"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.CardID != "" {
			board.moveCard(p.CardID, p.FromColumnID, p.ToColumnID, p.Index)
			mutated = true
		}

	case "card.delete":
		var p struct {
			CardID   string `json:"cardId"`
			ColumnID string `json:"columnId"`
		}
		if err := json.Unmarshal(payloadRaw, &p); err == nil && p.CardID != "" && p.ColumnID != "" {
			board.deleteCard(p.CardID, p.ColumnID)
			mutated = true
		}
	}

	if mutated {
		err = m.SaveBoard(ctx, roomId, board)
		if err != nil {
			return nil, err
		}
	}

	return board, nil
}

// ---- Board mutations ----

func (b *Board) addColumn(id, title string) {
	// Check if already exists
	for _, col := range b.Columns {
		if col.ID == id {
			return
		}
	}
	b.Columns = append(b.Columns, Column{
		ID:    id,
		Title: title,
		Cards: []Card{},
	})
}

func (b *Board) moveColumn(id string, toIndex int) {
	colIndex := -1
	for i, col := range b.Columns {
		if col.ID == id {
			colIndex = i
			break
		}
	}
	if colIndex == -1 {
		return
	}

	col := b.Columns[colIndex]
	// Remove from slice
	b.Columns = append(b.Columns[:colIndex], b.Columns[colIndex+1:]...)

	// Bounds safety check
	if toIndex < 0 {
		toIndex = 0
	}
	if toIndex > len(b.Columns) {
		toIndex = len(b.Columns)
	}

	// Insert at toIndex
	b.Columns = append(b.Columns[:toIndex], append([]Column{col}, b.Columns[toIndex:]...)...)
}

func (b *Board) deleteColumn(id string) {
	for i, col := range b.Columns {
		if col.ID == id {
			b.Columns = append(b.Columns[:i], b.Columns[i+1:]...)
			return
		}
	}
}

func (b *Board) addCard(colId string, card Card) {
	for i, col := range b.Columns {
		if col.ID == colId {
			// Check if card already exists in this column
			for _, c := range col.Cards {
				if c.ID == card.ID {
					return
				}
			}
			b.Columns[i].Cards = append(b.Columns[i].Cards, card)
			return
		}
	}
}

func (b *Board) updateCard(cardId string, dataRaw []byte) {
	for i, col := range b.Columns {
		for j, card := range col.Cards {
			if card.ID == cardId {
				// We unmarshal updates into current card
				var updates struct {
					Title   *string          `json:"title"`
					Content *string          `json:"content"`
					Items   []ChecklistItem  `json:"items"`
					Meta    *json.RawMessage `json:"meta"`
				}
				if err := json.Unmarshal(dataRaw, &updates); err == nil {
					if updates.Title != nil {
						b.Columns[i].Cards[j].Title = *updates.Title
					}
					if updates.Content != nil {
						b.Columns[i].Cards[j].Content = *updates.Content
					}
					if updates.Items != nil {
						b.Columns[i].Cards[j].Items = updates.Items
					}
					if updates.Meta != nil {
						b.Columns[i].Cards[j].Meta = *updates.Meta
					}
				}
				return
			}
		}
	}
}

func (b *Board) moveCard(cardId, fromColId, toColId string, index int) {
	fromColIdx := -1
	cardIdx := -1

	// 1. Find and remove card from origin column
	for i, col := range b.Columns {
		if col.ID == fromColId {
			fromColIdx = i
			for j, card := range col.Cards {
				if card.ID == cardId {
					cardIdx = j
					break
				}
			}
			break
		}
	}

	if fromColIdx == -1 || cardIdx == -1 {
		return
	}

	targetCard := b.Columns[fromColIdx].Cards[cardIdx]
	b.Columns[fromColIdx].Cards = append(b.Columns[fromColIdx].Cards[:cardIdx], b.Columns[fromColIdx].Cards[cardIdx+1:]...)

	// 2. Insert card into destination column
	for i, col := range b.Columns {
		if col.ID == toColId {
			if index < 0 {
				index = 0
			}
			if index > len(col.Cards) {
				index = len(col.Cards)
			}
			b.Columns[i].Cards = append(col.Cards[:index], append([]Card{targetCard}, col.Cards[index:]...)...)
			return
		}
	}
}

func (b *Board) deleteCard(cardId, colId string) {
	for i, col := range b.Columns {
		if col.ID == colId {
			for j, card := range col.Cards {
				if card.ID == cardId {
					b.Columns[i].Cards = append(b.Columns[i].Cards[:j], b.Columns[i].Cards[j+1:]...)
					return
				}
			}
		}
	}
}
