package main

import (
	"context"
	"errors"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"
)

func main() {
	log.Println("Запуск микросервиса Ledger...")

	// Инициализация базы данных
	ctx, cancel := context.WithTimeout(context.Background(), 45*time.Second)
	defer cancel()

	if err := InitDB(ctx); err != nil {
		log.Fatalf("Критическая ошибка инициализации БД: %v", err)
	}
	defer dbPool.Close()

	// Инициализация JWT и JWKS
	InitAuth()

	// Защищенный роутер для финансовых операций
	apiMux := http.NewServeMux()

	// Кошельки
	apiMux.HandleFunc("POST /wallets", createWalletHandler)
	apiMux.HandleFunc("GET /wallets/{id}/balance", getWalletBalanceHandler)
	apiMux.HandleFunc("GET /users/{user_id}/wallet", getWalletByUserIDHandler)

	// Транзакции и переводы (Double-Entry)
	apiMux.HandleFunc("POST /transactions", createTransactionHandler)
	apiMux.HandleFunc("POST /transactions/{id}/status", updateTransactionStatusHandler)
	apiMux.HandleFunc("POST /transactions/external/{external_id}/status", updateExternalTransactionsStatusHandler)
	apiMux.HandleFunc("POST /transfers", createTransferHandler)

	// Основной роутер
	mainMux := http.NewServeMux()
	
	// Оборачиваем финансовое API в JWT-аутентификацию
	mainMux.Handle("/", JWTMiddleware(apiMux))

	// Здоровье сервиса (Liveness) доступно без авторизации
	mainMux.HandleFunc("GET /health", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte(`{"status":"ok"}`))
	})

	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	server := &http.Server{
		Addr:    ":" + port,
		Handler: mainMux,
	}

	// Канал для graceful shutdown
	stop := make(chan os.Signal, 1)
	signal.Notify(stop, os.Interrupt, syscall.SIGTERM)

	go func() {
		log.Printf("HTTP Сервер запущен на порту %s", port)
		if err := server.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
			log.Fatalf("Ошибка HTTP сервера: %v", err)
		}
	}()

	// Ожидаем сигнал остановки
	<-stop
	log.Println("Остановка HTTP сервера...")

	shutdownCtx, shutdownCancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer shutdownCancel()

	if err := server.Shutdown(shutdownCtx); err != nil {
		log.Printf("Ошибка при graceful shutdown сервера: %v", err)
	}

	log.Println("Микросервис Ledger успешно остановлен.")
}
