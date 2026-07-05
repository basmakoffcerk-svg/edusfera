package main

import (
	"context"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"edusfera-workspace/internal/config"
	"edusfera-workspace/internal/db"
	"edusfera-workspace/internal/redis"
	"edusfera-workspace/internal/websocket"
	"edusfera-workspace/internal/workspace"
)

func main() {
	// 1. Initialize Logger
	logHandler := slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{
		Level: slog.LevelDebug,
	})
	logger := slog.New(logHandler)
	slog.SetDefault(logger)

	logger.Info("starting edusfera-workspace microservice")

	// 2. Load Configuration
	cfg := config.Load()
	logger.Info("configuration loaded", "port", cfg.Port, "db_host", cfg.DBHost, "redis_host", cfg.RedisHost)

	// 3. Connect to Database (PostgreSQL)
	ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	database, err := db.Connect(ctx, cfg.DSN(), logger)
	if err != nil {
		logger.Error("failed to connect to database", "error", err)
		os.Exit(1)
	}
	defer database.Close()

	// 4. Connect to Redis
	rdb, err := redis.Connect(ctx, cfg.RedisAddr(), logger)
	if err != nil {
		logger.Error("failed to connect to redis", "error", err)
		os.Exit(1)
	}
	defer rdb.Close()

	// 5. Initialize Components
	boardManager := workspace.NewManager(database, rdb, logger)
	pool := websocket.NewPool(rdb, logger)
	wsHandler := websocket.NewHandler(pool, boardManager, rdb, cfg.JWTSecret, logger)

	// 6. Setup HTTP Router (Go 1.22+ routing features)
	mux := http.NewServeMux()

	// Health check
	mux.HandleFunc("GET /health", func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte(`{"status":"healthy"}`))
	})

	// WebSocket Room endpoint
	mux.HandleFunc("GET /ws/workspace/{roomId}", func(w http.ResponseWriter, r *http.Request) {
		roomId := r.PathValue("roomId")
		if roomId == "" {
			http.Error(w, "missing roomId", http.StatusBadRequest)
			return
		}
		wsHandler.HandleWS(w, r, roomId)
	})

	// Internal REST API for Laravel to apply AI patches
	mux.HandleFunc("POST /api/v1/workspace/{roomId}/apply-ai-patch", func(w http.ResponseWriter, r *http.Request) {
		roomId := r.PathValue("roomId")
		if roomId == "" {
			http.Error(w, "missing roomId", http.StatusBadRequest)
			return
		}

		// Authorization header verification
		authHeader := r.Header.Get("Authorization")
		expectedAuth := fmt.Sprintf("Bearer %s", cfg.InternalSecret)
		if authHeader != expectedAuth {
			logger.Warn("Unauthorized AI patch attempt", "roomId", roomId, "received", authHeader)
			http.Error(w, "Unauthorized", http.StatusUnauthorized)
			return
		}

		var req workspace.AIPatchRequest
		if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
			logger.Error("failed to decode AI patch request", "error", err)
			http.Error(w, "Invalid JSON payload", http.StatusBadRequest)
			return
		}

		ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
		defer cancel()

		logger.Info("applying AI patch to board", "roomId", roomId, "actions_count", len(req.Actions))

		// Apply all mutations in sequence
		var board *workspace.Board
		for _, action := range req.Actions {
			var err error
			board, err = boardManager.ApplyMutation(ctx, roomId, action.Action, action.Payload)
			if err != nil {
				logger.Error("failed to apply AI mutation", "action", action.Action, "error", err)
				http.Error(w, fmt.Sprintf("Failed to apply action %s: %v", action.Action, err), http.StatusInternalServerError)
				return
			}
		}

		// If no actions were provided, we just load the current board
		if board == nil {
			var err error
			board, err = boardManager.LoadBoard(ctx, roomId)
			if err != nil {
				logger.Error("failed to load board for fallback", "roomId", roomId, "error", err)
				http.Error(w, "Failed to load board", http.StatusInternalServerError)
				return
			}
		}

		// Broadcast updated state to all connected clients in the room via Redis Pub/Sub
		syncMsg := struct {
			Event   string           `json:"event"`
			Payload *workspace.Board `json:"payload"`
		}{
			Event:   "workspace.sync",
			Payload: board,
		}

		syncJSON, err := json.Marshal(syncMsg)
		if err == nil {
			if err := rdb.PublishEvent(ctx, roomId, string(syncJSON)); err != nil {
				logger.Error("failed to broadcast AI patch sync to Redis Pub/Sub", "roomId", roomId, "error", err)
			}
		}

		// Return updated board to Laravel
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusOK)
		_ = json.NewEncoder(w).Encode(map[string]interface{}{
			"status": "success",
			"board":  board,
		})
	})

	// 7. Start HTTP Server
	serverAddr := fmt.Sprintf(":%d", cfg.Port)
	server := &http.Server{
		Addr:    serverAddr,
		Handler: mux,
	}

	go func() {
		logger.Info("http server listening", "addr", serverAddr)
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			logger.Error("server listener failed", "error", err)
			os.Exit(1)
		}
	}()

	// 8. Graceful Shutdown
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	logger.Info("shutting down server...")

	shutdownCtx, shutdownCancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer shutdownCancel()

	if err := server.Shutdown(shutdownCtx); err != nil {
		logger.Error("forced server shutdown", "error", err)
	}

	logger.Info("server exited gracefully")
}
