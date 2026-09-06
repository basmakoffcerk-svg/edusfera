package main

import (
	"encoding/json"
	"errors"
	"net/http"
	"sort"

	"github.com/google/uuid"
	"github.com/jackc/pgx/v5"
	"github.com/shopspring/decimal"
)

// respondWithError отправляет ошибку в формате JSON
func respondWithError(w http.ResponseWriter, code int, message string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	_ = json.NewEncoder(w).Encode(ErrorResponse{Error: message})
}

// respondWithJSON отправляет JSON ответ
func respondWithJSON(w http.ResponseWriter, code int, payload interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	_ = json.NewEncoder(w).Encode(payload)
}

// createWalletHandler обрабатывает создание кошелька: POST /wallets
func createWalletHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	var req CreateWalletRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_request_body")
		return
	}

	if req.UserID == "" {
		respondWithError(w, http.StatusBadRequest, "missing_user_id")
		return
	}

	if req.Currency == "" {
		req.Currency = "BYN"
	}

	// Проверяем, нет ли уже кошелька у этого пользователя
	var existing Wallet
	ctx := r.Context()
	err := dbPool.QueryRow(ctx, "SELECT id, user_id, currency, created_at FROM ledger.wallets WHERE user_id = $1 AND currency = $2", req.UserID, req.Currency).
		Scan(&existing.ID, &existing.UserID, &existing.Currency, &existing.CreatedAt)
	if err == nil {
		// Кошелек уже существует, отдаем его (идемпотентность)
		respondWithJSON(w, http.StatusOK, existing)
		return
	}

	walletID := uuid.New()
	wallet := Wallet{
		ID:       walletID,
		UserID:   req.UserID,
		Currency: req.Currency,
	}

	query := `INSERT INTO ledger.wallets (id, user_id, currency) VALUES ($1, $2, $3) RETURNING created_at`
	err = dbPool.QueryRow(ctx, query, wallet.ID, wallet.UserID, wallet.Currency).Scan(&wallet.CreatedAt)
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_create_wallet: "+err.Error())
		return
	}

	respondWithJSON(w, http.StatusCreated, wallet)
}

// getWalletByUserIDHandler находит кошелек по user_id: GET /users/{user_id}/wallet
func getWalletByUserIDHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	userID := r.PathValue("user_id")
	if userID == "" {
		respondWithError(w, http.StatusBadRequest, "missing_user_id")
		return
	}

	ctx := r.Context()
	var wallet Wallet
	query := `SELECT id, user_id, currency, created_at FROM ledger.wallets WHERE user_id = $1`
	err := dbPool.QueryRow(ctx, query, userID).Scan(&wallet.ID, &wallet.UserID, &wallet.Currency, &wallet.CreatedAt)
	if err != nil {
		if errors.Is(err, pgx.ErrNoRows) {
			respondWithError(w, http.StatusNotFound, "wallet_not_found")
		} else {
			respondWithError(w, http.StatusInternalServerError, "database_error: "+err.Error())
		}
		return
	}

	respondWithJSON(w, http.StatusOK, wallet)
}

// getWalletBalanceHandler обрабатывает получение баланса: GET /wallets/{id}/balance
func getWalletBalanceHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	idStr := r.PathValue("id")
	walletID, err := uuid.Parse(idStr)
	if err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_wallet_uuid")
		return
	}

	ctx := r.Context()
	var balance BalanceResponse
	balance.WalletID = walletID

	query := `
		SELECT 
			w.currency,
			COALESCE(SUM(t.amount) FILTER (WHERE t.status = 'completed'), 0) AS actual_balance,
			COALESCE(-SUM(t.amount) FILTER (WHERE t.type = 'hold' AND t.status = 'pending'), 0) AS locked_balance,
			COALESCE(SUM(t.amount) FILTER (WHERE t.status = 'pending' AND t.amount > 0), 0) AS pending_balance
		FROM ledger.wallets w
		LEFT JOIN ledger.transactions t ON t.wallet_id = w.id
		WHERE w.id = $1
		GROUP BY w.id, w.currency`

	err = dbPool.QueryRow(ctx, query, walletID).Scan(&balance.Currency, &balance.Actual, &balance.Locked, &balance.Pending)
	if err != nil {
		if errors.Is(err, pgx.ErrNoRows) {
			respondWithError(w, http.StatusNotFound, "wallet_not_found")
		} else {
			respondWithError(w, http.StatusInternalServerError, "failed_to_query_balance: "+err.Error())
		}
		return
	}

	balance.Available = balance.Actual.Sub(balance.Locked)
	respondWithJSON(w, http.StatusOK, balance)
}

// createTransactionHandler обрабатывает создание транзакции: POST /transactions
func createTransactionHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	var req CreateTransactionRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_request_body")
		return
	}

	if req.WalletID == uuid.Nil {
		respondWithError(w, http.StatusBadRequest, "missing_wallet_id")
		return
	}

	switch req.Type {
	case "deposit", "withdraw", "payment", "hold":
		// OK
	default:
		respondWithError(w, http.StatusBadRequest, "invalid_transaction_type")
		return
	}

	switch req.Status {
	case "pending", "completed", "failed":
		// OK
	default:
		respondWithError(w, http.StatusBadRequest, "invalid_transaction_status")
		return
	}

	if req.Type == "deposit" {
		if req.Amount.LessThanOrEqual(decimal.Zero) {
			respondWithError(w, http.StatusBadRequest, "deposit_amount_must_be_positive")
			return
		}
	} else {
		if req.Amount.GreaterThanOrEqual(decimal.Zero) {
			respondWithError(w, http.StatusBadRequest, "withdrawal_or_payment_amount_must_be_negative")
			return
		}
	}

	ctx := r.Context()
	tx, err := dbPool.BeginTx(ctx, pgx.TxOptions{IsoLevel: pgx.ReadCommitted})
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_start_db_transaction")
		return
	}
	defer func() {
		_ = tx.Rollback(ctx)
	}()

	var walletCurrency string
	err = tx.QueryRow(ctx, "SELECT currency FROM ledger.wallets WHERE id = $1 FOR UPDATE", req.WalletID).Scan(&walletCurrency)
	if err != nil {
		if errors.Is(err, pgx.ErrNoRows) {
			respondWithError(w, http.StatusNotFound, "wallet_not_found")
		} else {
			respondWithError(w, http.StatusInternalServerError, "database_error: "+err.Error())
		}
		return
	}

	if req.Type != "deposit" && req.Status != "failed" {
		var actualBalance decimal.Decimal
		var lockedBalance decimal.Decimal

		queryBal := `
			SELECT 
				COALESCE(SUM(amount) FILTER (WHERE status = 'completed'), 0) AS actual_balance,
				COALESCE(-SUM(amount) FILTER (WHERE type = 'hold' AND status = 'pending'), 0) AS locked_balance
			FROM ledger.transactions
			WHERE wallet_id = $1`

		err = tx.QueryRow(ctx, queryBal, req.WalletID).Scan(&actualBalance, &lockedBalance)
		if err != nil {
			respondWithError(w, http.StatusInternalServerError, "failed_to_check_balance")
			return
		}

		availableBalance := actualBalance.Sub(lockedBalance)
		if availableBalance.Add(req.Amount).LessThan(decimal.Zero) {
			respondWithError(w, http.StatusBadRequest, "insufficient_funds")
			return
		}
	}

	txID := uuid.New()
	var createdTx Transaction
	createdTx.ID = txID
	createdTx.WalletID = req.WalletID
	createdTx.Amount = req.Amount
	createdTx.Type = req.Type
	createdTx.Status = req.Status
	createdTx.ExternalID = req.ExternalID

	queryInsert := `
		INSERT INTO ledger.transactions (id, wallet_id, amount, type, status, external_id)
		VALUES ($1, $2, $3, $4, $5, $6)
		RETURNING created_at`

	err = tx.QueryRow(ctx, queryInsert, txID, req.WalletID, req.Amount, req.Type, req.Status, req.ExternalID).Scan(&createdTx.CreatedAt)
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_insert_transaction: "+err.Error())
		return
	}

	if err := tx.Commit(ctx); err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_commit_db_transaction")
		return
	}

	respondWithJSON(w, http.StatusCreated, createdTx)
}

// createTransferHandler обрабатывает групповой перевод (Double-Entry): POST /transfers
func createTransferHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	var req TransferRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_request_body")
		return
	}

	if len(req.Postings) == 0 {
		respondWithError(w, http.StatusBadRequest, "missing_postings")
		return
	}

	// 1. Проверяем балансовое равенство (сумма всех проводок должна быть 0)
	sum := decimal.Zero
	for _, p := range req.Postings {
		sum = sum.Add(p.Amount)
	}
	if !sum.Equal(decimal.Zero) {
		respondWithError(w, http.StatusBadRequest, "unbalanced_postings_sum_must_be_zero")
		return
	}

	ctx := r.Context()
	tx, err := dbPool.BeginTx(ctx, pgx.TxOptions{IsoLevel: pgx.ReadCommitted})
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_start_db_transaction")
		return
	}
	defer func() {
		_ = tx.Rollback(ctx)
	}()

	// Получаем уникальные ID кошельков и сортируем их для избежания дедлоков при блокировке FOR UPDATE
	walletIDsMap := make(map[uuid.UUID]bool)
	for _, p := range req.Postings {
		walletIDsMap[p.WalletID] = true
	}
	var sortedWalletIDs []uuid.UUID
	for id := range walletIDsMap {
		sortedWalletIDs = append(sortedWalletIDs, id)
	}
	sort.Slice(sortedWalletIDs, func(i, j int) bool {
		return sortedWalletIDs[i].String() < sortedWalletIDs[j].String()
	})

	// 2. Блокируем все кошельки FOR UPDATE в сортированном порядке
	for _, walletID := range sortedWalletIDs {
		var currency string
		err = tx.QueryRow(ctx, "SELECT currency FROM ledger.wallets WHERE id = $1 FOR UPDATE", walletID).Scan(&currency)
		if err != nil {
			if errors.Is(err, pgx.ErrNoRows) {
				respondWithError(w, http.StatusNotFound, "wallet_not_found: "+walletID.String())
			} else {
				respondWithError(w, http.StatusInternalServerError, "database_error: "+err.Error())
			}
			return
		}
	}

	// 3. Проверяем балансы списания для кошельков (amount < 0)
	// Для каждого кошелька собираем общую сумму списания в этом переводе
	debitSums := make(map[uuid.UUID]decimal.Decimal)
	for _, p := range req.Postings {
		if p.Amount.LessThan(decimal.Zero) && p.Status != "failed" {
			debitSums[p.WalletID] = debitSums[p.WalletID].Add(p.Amount)
		}
	}

	for walletID, amountToDebit := range debitSums {
		var actualBalance decimal.Decimal
		var lockedBalance decimal.Decimal

		queryBal := `
			SELECT 
				COALESCE(SUM(amount) FILTER (WHERE status = 'completed'), 0) AS actual_balance,
				COALESCE(-SUM(amount) FILTER (WHERE type = 'hold' AND status = 'pending'), 0) AS locked_balance
			FROM ledger.transactions
			WHERE wallet_id = $1`

		err = tx.QueryRow(ctx, queryBal, walletID).Scan(&actualBalance, &lockedBalance)
		if err != nil {
			respondWithError(w, http.StatusInternalServerError, "failed_to_check_balance: "+walletID.String())
			return
		}

		availableBalance := actualBalance.Sub(lockedBalance)
		// Напоминаем: amountToDebit отрицательный
		if availableBalance.Add(amountToDebit).LessThan(decimal.Zero) {
			respondWithError(w, http.StatusBadRequest, "insufficient_funds_on_wallet: "+walletID.String())
			return
		}
	}

	// 4. Создаем транзакции для всех проводок
	var createdTransactions []Transaction
	queryInsert := `
		INSERT INTO ledger.transactions (id, wallet_id, amount, type, status, external_id)
		VALUES ($1, $2, $3, $4, $5, $6)
		RETURNING created_at`

	for _, p := range req.Postings {
		txID := uuid.New()
		var t Transaction
		t.ID = txID
		t.WalletID = p.WalletID
		t.Amount = p.Amount
		t.Type = p.Type
		t.Status = p.Status
		t.ExternalID = req.ExternalID

		err = tx.QueryRow(ctx, queryInsert, txID, p.WalletID, p.Amount, p.Type, p.Status, req.ExternalID).Scan(&t.CreatedAt)
		if err != nil {
			respondWithError(w, http.StatusInternalServerError, "failed_to_insert_posting: "+err.Error())
			return
		}

		createdTransactions = append(createdTransactions, t)
	}

	if err := tx.Commit(ctx); err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_commit_db_transaction")
		return
	}

	respondWithJSON(w, http.StatusCreated, createdTransactions)
}

// updateTransactionStatusHandler обрабатывает обновление статуса транзакции по ID: POST /transactions/{id}/status
func updateTransactionStatusHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	idStr := r.PathValue("id")
	txID, err := uuid.Parse(idStr)
	if err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_transaction_uuid")
		return
	}

	var req UpdateStatusRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_request_body")
		return
	}

	switch req.Status {
	case "completed", "failed":
		// OK
	default:
		respondWithError(w, http.StatusBadRequest, "invalid_target_status")
		return
	}

	ctx := r.Context()
	tx, err := dbPool.BeginTx(ctx, pgx.TxOptions{IsoLevel: pgx.ReadCommitted})
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_start_db_transaction")
		return
	}
	defer func() {
		_ = tx.Rollback(ctx)
	}()

	var currentTx Transaction
	querySelect := `
		SELECT id, wallet_id, amount, type, status, external_id, created_at 
		FROM ledger.transactions 
		WHERE id = $1 
		FOR UPDATE`

	err = tx.QueryRow(ctx, querySelect, txID).Scan(
		&currentTx.ID,
		&currentTx.WalletID,
		&currentTx.Amount,
		&currentTx.Type,
		&currentTx.Status,
		&currentTx.ExternalID,
		&currentTx.CreatedAt,
	)
	if err != nil {
		if errors.Is(err, pgx.ErrNoRows) {
			respondWithError(w, http.StatusNotFound, "transaction_not_found")
		} else {
			respondWithError(w, http.StatusInternalServerError, "database_error: "+err.Error())
		}
		return
	}

	if currentTx.Status == req.Status {
		_ = tx.Rollback(ctx)
		respondWithJSON(w, http.StatusOK, currentTx)
		return
	}

	if currentTx.Status != "pending" {
		respondWithError(w, http.StatusBadRequest, "cannot_change_final_status")
		return
	}

	// Блокируем кошелек
	var walletCurrency string
	err = tx.QueryRow(ctx, "SELECT currency FROM ledger.wallets WHERE id = $1 FOR UPDATE", currentTx.WalletID).Scan(&walletCurrency)
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_lock_wallet")
		return
	}

	// Проверка баланса при списании
	if req.Status == "completed" && currentTx.Type != "deposit" && currentTx.Type != "hold" {
		var actualBalance decimal.Decimal
		var lockedBalance decimal.Decimal

		queryBal := `
			SELECT 
				COALESCE(SUM(amount) FILTER (WHERE status = 'completed'), 0) AS actual_balance,
				COALESCE(-SUM(amount) FILTER (WHERE type = 'hold' AND status = 'pending'), 0) AS locked_balance
			FROM ledger.transactions
			WHERE wallet_id = $1`

		err = tx.QueryRow(ctx, queryBal, currentTx.WalletID).Scan(&actualBalance, &lockedBalance)
		if err != nil {
			respondWithError(w, http.StatusInternalServerError, "failed_to_check_balance")
			return
		}

		availableBalance := actualBalance.Sub(lockedBalance)
		if availableBalance.Add(currentTx.Amount).LessThan(decimal.Zero) {
			respondWithError(w, http.StatusBadRequest, "insufficient_funds_to_complete_transaction")
			return
		}
	}

	queryUpdate := `UPDATE ledger.transactions SET status = $1 WHERE id = $2`
	_, err = tx.Exec(ctx, queryUpdate, req.Status, txID)
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_update_status: "+err.Error())
		return
	}

	currentTx.Status = req.Status
	if err := tx.Commit(ctx); err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_commit_db_transaction")
		return
	}

	respondWithJSON(w, http.StatusOK, currentTx)
}

// updateExternalTransactionsStatusHandler обновляет статус всех транзакций по external_id: POST /transactions/external/{external_id}/status
func updateExternalTransactionsStatusHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		respondWithError(w, http.StatusMethodNotAllowed, "method_not_allowed")
		return
	}

	externalID := r.PathValue("external_id")
	if externalID == "" {
		respondWithError(w, http.StatusBadRequest, "missing_external_id")
		return
	}

	var req UpdateStatusRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		respondWithError(w, http.StatusBadRequest, "invalid_request_body")
		return
	}

	switch req.Status {
	case "completed", "failed":
		// OK
	default:
		respondWithError(w, http.StatusBadRequest, "invalid_target_status")
		return
	}

	ctx := r.Context()
	tx, err := dbPool.BeginTx(ctx, pgx.TxOptions{IsoLevel: pgx.ReadCommitted})
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_start_db_transaction")
		return
	}
	defer func() {
		_ = tx.Rollback(ctx)
	}()

	// 1. Получаем все транзакции с данным external_id
	querySelect := `
		SELECT id, wallet_id, amount, type, status, external_id, created_at 
		FROM ledger.transactions 
		WHERE external_id = $1`

	rows, err := tx.Query(ctx, querySelect, externalID)
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "database_error: "+err.Error())
		return
	}
	defer rows.Close()

	var transactions []Transaction
	for rows.Next() {
		var t Transaction
		err = rows.Scan(&t.ID, &t.WalletID, &t.Amount, &t.Type, &t.Status, &t.ExternalID, &t.CreatedAt)
		if err != nil {
			respondWithError(w, http.StatusInternalServerError, "database_error_scan: "+err.Error())
			return
		}
		transactions = append(transactions, t)
	}

	if len(transactions) == 0 {
		respondWithError(w, http.StatusNotFound, "transactions_not_found_by_external_id")
		return
	}

	// Получаем уникальные ID кошельков и сортируем для избежания дедлоков
	walletIDsMap := make(map[uuid.UUID]bool)
	for _, t := range transactions {
		walletIDsMap[t.WalletID] = true
	}
	var sortedWalletIDs []uuid.UUID
	for id := range walletIDsMap {
		sortedWalletIDs = append(sortedWalletIDs, id)
	}
	sort.Slice(sortedWalletIDs, func(i, j int) bool {
		return sortedWalletIDs[i].String() < sortedWalletIDs[j].String()
	})

	// 2. Блокируем кошельки FOR UPDATE
	for _, walletID := range sortedWalletIDs {
		var currency string
		err = tx.QueryRow(ctx, "SELECT currency FROM ledger.wallets WHERE id = $1 FOR UPDATE", walletID).Scan(&currency)
		if err != nil {
			respondWithError(w, http.StatusInternalServerError, "failed_to_lock_wallet: "+walletID.String())
			return
		}
	}

	// 3. Если переводим в completed, проверяем балансы списания (для тех транзакций, которые были в pending и amount < 0)
	if req.Status == "completed" {
		debitSums := make(map[uuid.UUID]decimal.Decimal)
		for _, t := range transactions {
			if t.Status == "pending" && t.Amount.LessThan(decimal.Zero) && t.Type != "hold" {
				debitSums[t.WalletID] = debitSums[t.WalletID].Add(t.Amount)
			}
		}

		for walletID, amountToDebit := range debitSums {
			var actualBalance decimal.Decimal
			var lockedBalance decimal.Decimal

			queryBal := `
				SELECT 
					COALESCE(SUM(amount) FILTER (WHERE status = 'completed'), 0) AS actual_balance,
					COALESCE(-SUM(amount) FILTER (WHERE type = 'hold' AND status = 'pending'), 0) AS locked_balance
				FROM ledger.transactions
				WHERE wallet_id = $1`

			err = tx.QueryRow(ctx, queryBal, walletID).Scan(&actualBalance, &lockedBalance)
			if err != nil {
				respondWithError(w, http.StatusInternalServerError, "failed_to_check_balance")
				return
			}

			availableBalance := actualBalance.Sub(lockedBalance)
			// amountToDebit отрицательный
			if availableBalance.Add(amountToDebit).LessThan(decimal.Zero) {
				respondWithError(w, http.StatusBadRequest, "insufficient_funds_on_wallet: "+walletID.String())
				return
			}
		}
	}

	// 4. Обновляем статус всех транзакций, которые были в pending
	queryUpdate := `UPDATE ledger.transactions SET status = $1 WHERE external_id = $2 AND status = 'pending'`
	_, err = tx.Exec(ctx, queryUpdate, req.Status, externalID)
	if err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_update_external_status: "+err.Error())
		return
	}

	for i := range transactions {
		if transactions[i].Status == "pending" {
			transactions[i].Status = req.Status
		}
	}

	if err := tx.Commit(ctx); err != nil {
		respondWithError(w, http.StatusInternalServerError, "failed_to_commit_db_transaction")
		return
	}

	respondWithJSON(w, http.StatusOK, transactions)
}
