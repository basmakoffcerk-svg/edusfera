package db

import (
	"context"
	"fmt"
	"log/slog"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
)

type DB struct {
	Pool *pgxpool.Pool
}

func Connect(ctx context.Context, dsn string, logger *slog.Logger) (*DB, error) {
	var pool *pgxpool.Pool
	var err error

	// Retry database connection a few times at startup
	for i := 0; i < 5; i++ {
		pool, err = pgxpool.New(ctx, dsn)
		if err == nil {
			err = pool.Ping(ctx)
			if err == nil {
				break
			}
		}
		logger.Warn("Database not ready yet, retrying...", "attempt", i+1, "error", err)
		time.Sleep(2 * time.Second)
	}

	if err != nil {
		return nil, fmt.Errorf("failed to connect to postgres: %w", err)
	}

	logger.Info("Connected to PostgreSQL successfully")

	db := &DB{Pool: pool}
	if err := db.Migrate(ctx, logger); err != nil {
		return nil, fmt.Errorf("failed to run database migrations: %w", err)
	}

	return db, nil
}

func (db *DB) Migrate(ctx context.Context, logger *slog.Logger) error {
	query := `
	CREATE TABLE IF NOT EXISTS classroom_workspaces (
		id BIGSERIAL PRIMARY KEY,
		classroom_session_id BIGINT NOT NULL UNIQUE,
		board_state JSONB NOT NULL DEFAULT '{"columns": []}',
		created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
		updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
	);

	CREATE INDEX IF NOT EXISTS idx_classroom_workspaces_session ON classroom_workspaces(classroom_session_id);
	`

	_, err := db.Pool.Exec(ctx, query)
	if err != nil {
		return err
	}

	logger.Info("Database migrations completed successfully (classroom_workspaces table is ready)")
	return nil
}

func (db *DB) Close() {
	db.Pool.Close()
}
