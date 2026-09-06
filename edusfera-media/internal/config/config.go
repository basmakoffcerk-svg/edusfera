// Package config provides application configuration loaded from environment variables.
package config

import (
	"os"
	"strconv"
	"strings"
	"time"
)

// Config holds all application configuration values.
type Config struct {
	Port            int
	JWTSecret       string
	AllowedOrigins  []string
	STUNServers     []string
	LogLevel        string
	MaxRoomSize     int
	RoomIdleTimeout time.Duration
	LaravelAPIUrl   string
	JWKSURL         string
}

// Load reads configuration from environment variables with sensible defaults.
func Load() *Config {
	cfg := &Config{
		Port:            getEnvInt("PORT", 8088),
		JWTSecret:       getEnv("JWT_SECRET", ""),
		AllowedOrigins:  getEnvSlice("ALLOWED_ORIGINS", []string{"*"}),
		STUNServers:     getEnvSlice("STUN_SERVERS", []string{"stun:stun.l.google.com:19302"}),
		LogLevel:        getEnv("LOG_LEVEL", "info"),
		MaxRoomSize:     getEnvInt("MAX_ROOM_SIZE", 5),
		RoomIdleTimeout: time.Duration(getEnvInt("ROOM_IDLE_TIMEOUT_MINUTES", 120)) * time.Minute,
		LaravelAPIUrl:   getEnv("LARAVEL_API_URL", "http://nginx:80"),
	}

	// JWKS для проверки RS256 classroom-токенов (Laravel публикует публичные
	// ключи на /api/v1/.well-known/jwks.json). По умолчанию берётся из
	// LARAVEL_API_URL; переопределяется через JWKS_URL.
	cfg.JWKSURL = getEnv("JWKS_URL", strings.TrimRight(cfg.LaravelAPIUrl, "/")+"/api/v1/.well-known/jwks.json")

	return cfg
}

func getEnv(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func getEnvInt(key string, fallback int) int {
	if v := os.Getenv(key); v != "" {
		if i, err := strconv.Atoi(v); err == nil {
			return i
		}
	}
	return fallback
}

func getEnvSlice(key string, fallback []string) []string {
	if v := os.Getenv(key); v != "" {
		parts := strings.Split(v, ",")
		result := make([]string, 0, len(parts))
		for _, p := range parts {
			if trimmed := strings.TrimSpace(p); trimmed != "" {
				result = append(result, trimmed)
			}
		}
		if len(result) > 0 {
			return result
		}
	}
	return fallback
}
