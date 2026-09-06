// Package main starts the Edusfera media server (WebRTC SFU).
package main

import (
	"context"
	"fmt"
	"log/slog"
	"net/http"
	"os"
	"os/signal"
	"strings"
	"syscall"
	"time"

	"github.com/edusfera/media/internal/config"
	"github.com/edusfera/media/internal/health"
	"github.com/edusfera/media/internal/media"
	"github.com/edusfera/media/internal/room"
	"github.com/edusfera/media/internal/signaling"
)

func main() {
	cfg := config.Load()

	// ---- Logger ----
	logger := slog.New(slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{
		Level: parseLogLevel(cfg.LogLevel),
	}))
	slog.SetDefault(logger)

	// Для HS256-токенов нужен JWT_SECRET; RS256-токены проверяются через JWKS.
	// Достаточно одного из двух механизмов.
	if cfg.JWTSecret == "" && cfg.JWKSURL == "" {
		logger.Error("either JWT_SECRET (HS256) or JWKS_URL (RS256) is required")
		os.Exit(1)
	}

	// ---- SFU ----
	sfu, err := media.NewSFU(cfg.STUNServers, logger)
	if err != nil {
		logger.Error("failed to initialize SFU", "error", err)
		os.Exit(1)
	}

	// ---- Room Manager ----
	roomMgr := room.NewManager(cfg, logger)

	// ---- HTTP Router ----
	mux := http.NewServeMux()

	// Health endpoint
	mux.Handle("/health", health.NewHandler(roomMgr))

	// WebSocket signaling endpoint
	mux.Handle("/ws/", signaling.NewHandler(roomMgr, sfu, cfg, logger))

	// ---- HTTP Server ----
	addr := fmt.Sprintf(":%d", cfg.Port)
	srv := &http.Server{
		Addr:              addr,
		Handler:           corsMiddleware(mux, cfg.AllowedOrigins),
		ReadHeaderTimeout: 10 * time.Second,
		IdleTimeout:       120 * time.Second,
	}

	// ---- Graceful Shutdown ----
	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	go func() {
		logger.Info("starting Edusfera media server",
			"address", addr,
			"stun_servers", cfg.STUNServers,
			"max_room_size", cfg.MaxRoomSize,
			"idle_timeout", cfg.RoomIdleTimeout.String(),
			"log_level", cfg.LogLevel,
		)
		if listenErr := srv.ListenAndServe(); listenErr != nil && listenErr != http.ErrServerClosed {
			logger.Error("server error", "error", listenErr)
			os.Exit(1)
		}
	}()

	<-ctx.Done()
	logger.Info("received shutdown signal, stopping...")

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
	defer cancel()

	// Close all rooms first (gracefully disconnect all WebRTC/WebSocket sessions).
	roomMgr.CloseAll()

	if err := srv.Shutdown(shutdownCtx); err != nil {
		logger.Error("server shutdown error", "error", err)
	}

	logger.Info("Edusfera media server stopped")
}

// corsMiddleware adds CORS headers and handles preflight OPTIONS requests.
func corsMiddleware(next http.Handler, allowedOrigins []string) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		origin := r.Header.Get("Origin")

		if origin != "" && isOriginAllowed(origin, allowedOrigins) {
			w.Header().Set("Access-Control-Allow-Origin", origin)
			w.Header().Set("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
			w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Authorization")
			w.Header().Set("Access-Control-Allow-Credentials", "true")
			w.Header().Set("Vary", "Origin")
		}

		if r.Method == http.MethodOptions {
			w.WriteHeader(http.StatusNoContent)
			return
		}

		next.ServeHTTP(w, r)
	})
}

func isOriginAllowed(origin string, allowed []string) bool {
	for _, o := range allowed {
		o = strings.TrimSpace(o)
		if o == "*" || o == origin {
			return true
		}
	}
	return false
}

func parseLogLevel(level string) slog.Level {
	switch strings.ToLower(level) {
	case "debug":
		return slog.LevelDebug
	case "warn":
		return slog.LevelWarn
	case "error":
		return slog.LevelError
	default:
		return slog.LevelInfo
	}
}
