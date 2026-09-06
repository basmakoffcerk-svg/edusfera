package main

import (
	"time"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
)

// Wallet представляет сущность кошелька в БД
type Wallet struct {
	ID        uuid.UUID `json:"id"`
	UserID    string    `json:"user_id"`
	Currency  string    `json:"currency"`
	CreatedAt time.Time `json:"created_at"`
}

// Transaction представляет сущность транзакции в БД
type Transaction struct {
	ID         uuid.UUID       `json:"id"`
	WalletID   uuid.UUID       `json:"wallet_id"`
	Amount     decimal.Decimal `json:"amount"`      // Использование shopspring/decimal
	Type       string          `json:"type"`        // deposit, withdraw, payment, hold
	Status     string          `json:"status"`      // pending, completed, failed
	ExternalID *string         `json:"external_id"` // Ссылка на банковский ID (nullable)
	CreatedAt  time.Time       `json:"created_at"`
}

// BalanceResponse возвращает информацию по балансам кошелька
type BalanceResponse struct {
	WalletID  uuid.UUID       `json:"wallet_id"`
	Actual    decimal.Decimal `json:"actual_balance"`    // SUM(amount) WHERE status = 'completed'
	Locked    decimal.Decimal `json:"locked_balance"`    // SUM(amount) WHERE type = 'hold' AND status = 'pending' (положительное значение)
	Pending   decimal.Decimal `json:"pending_balance"`   // SUM(amount) WHERE status = 'pending' AND amount > 0 (ожидаемые поступления репетиторов)
	Available decimal.Decimal `json:"available_balance"` // Actual - Locked
	Currency  string          `json:"currency"`
}

// CreateWalletRequest тело запроса для создания кошелька
type CreateWalletRequest struct {
	UserID   string `json:"user_id"`
	Currency string `json:"currency"` // 'BYN' по умолчанию
}

// CreateTransactionRequest тело запроса для создания транзакции
type CreateTransactionRequest struct {
	WalletID   uuid.UUID       `json:"wallet_id"`
	Amount     decimal.Decimal `json:"amount"`
	Type       string          `json:"type"`                  // deposit, withdraw, payment, hold
	Status     string          `json:"status"`                // pending, completed, failed
	ExternalID *string         `json:"external_id,omitempty"` // ID из банка
}

// UpdateStatusRequest тело запроса для обновления статуса
type UpdateStatusRequest struct {
	Status string `json:"status"` // completed, failed
}

// Posting представляет одну проводку в рамках перевода
type Posting struct {
	WalletID uuid.UUID       `json:"wallet_id"`
	Amount   decimal.Decimal `json:"amount"`
	Type     string          `json:"type"`   // deposit, withdraw, payment, hold
	Status   string          `json:"status"` // pending, completed, failed
}

// TransferRequest тело запроса для группового перевода (двойная запись)
type TransferRequest struct {
	ExternalID *string   `json:"external_id,omitempty"`
	Postings   []Posting `json:"postings"`
}

// ErrorResponse стандартный формат ошибки
type ErrorResponse struct {
	Error string `json:"error"`
}
