package main

import (
	"context"
	"fmt"
	"log"
	"os"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
)

var dbPool *pgxpool.Pool

// InitDB инициализирует пул соединений с PostgreSQL и запускает автомиграцию.
func InitDB(ctx context.Context) error {
	connStr := os.Getenv("DATABASE_URL")
	if connStr == "" {
		host := os.Getenv("DB_HOST")
		if host == "" {
			host = "db"
		}
		port := os.Getenv("DB_PORT")
		if port == "" {
			port = "5432"
		}
		user := os.Getenv("DB_USER")
		if user == "" {
			user = os.Getenv("DB_USERNAME") // Поддержка переменных Laravel
		}
		password := os.Getenv("DB_PASSWORD")
		dbName := os.Getenv("DB_NAME")
		if dbName == "" {
			dbName = os.Getenv("DB_DATABASE") // Поддержка переменных Laravel
		}

		connStr = fmt.Sprintf("postgres://%s:%s@%s:%s/%s?sslmode=disable", user, password, host, port, dbName)
	}

	var pool *pgxpool.Pool
	var err error

	// Пробуем подключиться несколько раз с задержкой (полезно при старте в docker-compose)
	for i := 0; i < 10; i++ {
		pool, err = pgxpool.New(ctx, connStr)
		if err == nil {
			err = pool.Ping(ctx)
			if err == nil {
				break
			}
		}
		log.Printf("Не удалось подключиться к БД (попытка %d/10): %v. Ожидание 3 секунды...", i+1, err)
		time.Sleep(3 * time.Second)
	}

	if err != nil {
		return fmt.Errorf("ошибка подключения к PostgreSQL после нескольких попыток: %w", err)
	}

	dbPool = pool
	log.Println("Успешное подключение к PostgreSQL")

	// Запуск миграций таблиц
	if err := migrateSchema(ctx); err != nil {
		return fmt.Errorf("ошибка миграции схемы БД: %w", err)
	}

	return nil
}

// migrateSchema создает схему ledger, таблицы wallets и transactions, если они отсутствуют, и нужные индексы.
func migrateSchema(ctx context.Context) error {
	// Создаем схему и таблицы
	queries := []string{
		`CREATE SCHEMA IF NOT EXISTS ledger;`,
		`CREATE TABLE IF NOT EXISTS ledger.wallets (
			id UUID PRIMARY KEY,
			user_id VARCHAR(255) NOT NULL,
			currency VARCHAR(10) NOT NULL DEFAULT 'BYN',
			created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
		);`,
		`CREATE TABLE IF NOT EXISTS ledger.transactions (
			id UUID PRIMARY KEY,
			wallet_id UUID NOT NULL REFERENCES ledger.wallets(id) ON DELETE CASCADE,
			amount NUMERIC(15, 2) NOT NULL,
			type VARCHAR(50) NOT NULL, -- 'deposit', 'withdraw', 'payment', 'hold'
			status VARCHAR(50) NOT NULL, -- 'pending', 'completed', 'failed'
			external_id VARCHAR(255),
			created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
		);`,
		// Индексы для оптимизации
		`CREATE INDEX IF NOT EXISTS idx_wallets_user_id ON ledger.wallets(user_id);`,
		`CREATE INDEX IF NOT EXISTS idx_transactions_wallet_id ON ledger.transactions(wallet_id);`,
		`CREATE INDEX IF NOT EXISTS idx_transactions_wallet_status ON ledger.transactions(wallet_id, status);`,
		`CREATE INDEX IF NOT EXISTS idx_transactions_wallet_type_status ON ledger.transactions(wallet_id, type, status);`,
		`CREATE INDEX IF NOT EXISTS idx_transactions_external_id ON ledger.transactions(external_id);`,
	}

	for _, query := range queries {
		if _, err := dbPool.Exec(ctx, query); err != nil {
			return fmt.Errorf("ошибка выполнения SQL запроса: %s: %w", query, err)
		}
	}

	log.Println("Миграция базы данных успешно завершена (таблицы wallets и transactions готовы к работе)")
	return nil
}
