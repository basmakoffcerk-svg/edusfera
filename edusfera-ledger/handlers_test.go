package main

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"os"
	"testing"
	"time"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
)

func TestLedgerIntegration(t *testing.T) {
	// Отключаем JWT авторизацию для тестов
	_ = os.Setenv("JWT_AUTH_ENABLED", "false")

	if os.Getenv("DATABASE_URL") == "" && os.Getenv("DB_HOST") == "" {
		_ = os.Setenv("DB_HOST", "localhost")
		_ = os.Setenv("DB_USER", "postgres")
		_ = os.Setenv("DB_PASSWORD", "postgres")
		_ = os.Setenv("DB_NAME", "edusfera")
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	err := InitDB(ctx)
	if err != nil {
		t.Skipf("Пропускаем интеграционный тест, так как подключение к БД не удалось: %v", err)
		return
	}
	defer dbPool.Close()

	// Инициализируем auth с флагом отключенной авторизации
	InitAuth()

	// Настройка роутера
	apiMux := http.NewServeMux()
	apiMux.HandleFunc("POST /wallets", createWalletHandler)
	apiMux.HandleFunc("GET /wallets/{id}/balance", getWalletBalanceHandler)
	apiMux.HandleFunc("GET /users/{user_id}/wallet", getWalletByUserIDHandler)
	apiMux.HandleFunc("POST /transactions", createTransactionHandler)
	apiMux.HandleFunc("POST /transactions/{id}/status", updateTransactionStatusHandler)
	apiMux.HandleFunc("POST /transactions/external/{external_id}/status", updateExternalTransactionsStatusHandler)
	apiMux.HandleFunc("POST /transfers", createTransferHandler)

	mainMux := http.NewServeMux()
	mainMux.Handle("/", JWTMiddleware(apiMux))

	server := httptest.NewServer(mainMux)
	defer server.Close()

	client := server.Client()

	// 1. Создаем кошелек Студента
	var studentWallet Wallet
	studentReq := CreateWalletRequest{
		UserID:   "test_student_" + uuid.New().String()[:8],
		Currency: "BYN",
	}
	body, _ := json.Marshal(studentReq)
	resp, err := client.Post(server.URL+"/wallets", "application/json", bytes.NewBuffer(body))
	if err != nil {
		t.Fatalf("Ошибка при создании кошелька: %v", err)
	}
	if resp.StatusCode != http.StatusCreated && resp.StatusCode != http.StatusOK {
		t.Fatalf("Ожидался статус 201 или 200, получили %d", resp.StatusCode)
	}
	_ = json.NewDecoder(resp.Body).Decode(&studentWallet)
	resp.Body.Close()

	// 2. Создаем кошелек Репетитора
	var tutorWallet Wallet
	tutorReq := CreateWalletRequest{
		UserID:   "test_tutor_" + uuid.New().String()[:8],
		Currency: "BYN",
	}
	body, _ = json.Marshal(tutorReq)
	resp, err = client.Post(server.URL+"/wallets", "application/json", bytes.NewBuffer(body))
	if err != nil {
		t.Fatalf("Ошибка при создании кошелька репетитора: %v", err)
	}
	_ = json.NewDecoder(resp.Body).Decode(&tutorWallet)
	resp.Body.Close()

	// 3. Тест поиска по user_id
	resp, err = client.Get(server.URL + "/users/" + tutorReq.UserID + "/wallet")
	if err != nil {
		t.Fatalf("Ошибка поиска кошелька: %v", err)
	}
	if resp.StatusCode != http.StatusOK {
		t.Errorf("Ожидался статус 200 при поиске по user_id, получили %d", resp.StatusCode)
	}
	var foundWallet Wallet
	_ = json.NewDecoder(resp.Body).Decode(&foundWallet)
	resp.Body.Close()
	if foundWallet.ID != tutorWallet.ID {
		t.Errorf("Найден неверный кошелек: %v != %v", foundWallet.ID, tutorWallet.ID)
	}

	// 4. Пополняем кошелек студента на 100.00
	txReq := CreateTransactionRequest{
		WalletID: studentWallet.ID,
		Amount:   decimal.RequireFromString("100.00"),
		Type:     "deposit",
		Status:   "completed",
	}
	body, _ = json.Marshal(txReq)
	resp, err = client.Post(server.URL+"/transactions", "application/json", bytes.NewBuffer(body))
	if err != nil {
		t.Fatalf("Ошибка депозита: %v", err)
	}
	resp.Body.Close()

	// Функция проверки балансов
	checkBalance := func(wID uuid.UUID, actual, locked, pending, available string) {
		r, err := client.Get(server.URL + "/wallets/" + wID.String() + "/balance")
		if err != nil {
			t.Fatalf("Ошибка баланса: %v", err)
		}
		var bal BalanceResponse
		_ = json.NewDecoder(r.Body).Decode(&bal)
		r.Body.Close()

		if !bal.Actual.Equal(decimal.RequireFromString(actual)) {
			t.Errorf("Кошелек %s Actual: ожидали %s, получили %s", wID, actual, bal.Actual)
		}
		if !bal.Locked.Equal(decimal.RequireFromString(locked)) {
			t.Errorf("Кошелек %s Locked: ожидали %s, получили %s", wID, locked, bal.Locked)
		}
		if !bal.Pending.Equal(decimal.RequireFromString(pending)) {
			t.Errorf("Кошелек %s Pending: ожидали %s, получили %s", wID, pending, bal.Pending)
		}
		if !bal.Available.Equal(decimal.RequireFromString(available)) {
			t.Errorf("Кошелек %s Available: ожидали %s, получили %s", wID, available, bal.Available)
		}
	}

	checkBalance(studentWallet.ID, "100.00", "0.00", "0.00", "100.00")
	checkBalance(tutorWallet.ID, "0.00", "0.00", "0.00", "0.00")

	// 5. Тестируем Double-Entry перевод (списание у студента в hold, начисление репетитору в pending)
	externalID := "test_bank_tx_111"
	transferReq := TransferRequest{
		ExternalID: &externalID,
		Postings: []Posting{
			{
				WalletID: studentWallet.ID,
				Amount:   decimal.RequireFromString("-25.00"),
				Type:     "hold",
				Status:   "pending",
			},
			{
				WalletID: tutorWallet.ID,
				Amount:   decimal.RequireFromString("25.00"),
				Type:     "deposit",
				Status:   "pending",
			},
		},
	}
	body, _ = json.Marshal(transferReq)
	resp, err = client.Post(server.URL+"/transfers", "application/json", bytes.NewBuffer(body))
	if err != nil {
		t.Fatalf("Ошибка трансфера: %v", err)
	}
	if resp.StatusCode != http.StatusCreated {
		t.Fatalf("Ожидался статус 201 при трансфере, получили %d", resp.StatusCode)
	}
	resp.Body.Close()

	// Проверяем балансы после трансфера:
	// Студент: actual = 100, locked = 25, available = 75
	// Репетитор: actual = 0, locked = 0, pending = 25, available = 0
	checkBalance(studentWallet.ID, "100.00", "25.00", "0.00", "75.00")
	checkBalance(tutorWallet.ID, "0.00", "0.00", "25.00", "0.00")

	// 6. Подтверждаем трансфер по external_id
	statusReq := UpdateStatusRequest{Status: "completed"}
	body, _ = json.Marshal(statusReq)
	resp, err = client.Post(server.URL+"/transactions/external/"+externalID+"/status", "application/json", bytes.NewBuffer(body))
	if err != nil {
		t.Fatalf("Ошибка подтверждения трансфера: %v", err)
	}
	if resp.StatusCode != http.StatusOK {
		t.Fatalf("Ожидался статус 200 при подтверждении, получили %d", resp.StatusCode)
	}
	resp.Body.Close()

	// Проверяем балансы после подтверждения:
	// Студент: actual = 75, locked = 0, available = 75
	// Репетитор: actual = 25, locked = 0, pending = 0, available = 25
	checkBalance(studentWallet.ID, "75.00", "0.00", "0.00", "75.00")
	checkBalance(tutorWallet.ID, "25.00", "0.00", "0.00", "25.00")
}
