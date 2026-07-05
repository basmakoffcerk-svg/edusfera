package config

import (
	"fmt"
	"os"
	"strconv"
)

type Config struct {
	Port           int
	DBHost         string
	DBPort         int
	DBUsername     string
	DBPassword     string
	DBDatabase     string
	RedisHost      string
	RedisPort      int
	InternalSecret string
	JWTSecret      string
	LogLevel       string
}

func Load() *Config {
	port := getEnvInt("PORT", 8080)
	dbPort := getEnvInt("DB_PORT", 5432)
	redisPort := getEnvInt("REDIS_PORT", 6379)

	return &Config{
		Port:           port,
		DBHost:         getEnv("DB_HOST", "127.0.0.1"),
		DBPort:         dbPort,
		DBUsername:     getEnv("DB_USERNAME", "postgres"),
		DBPassword:     getEnv("DB_PASSWORD", "postgres"),
		DBDatabase:     getEnv("DB_DATABASE", "edusfera"),
		RedisHost:      getEnv("REDIS_HOST", "127.0.0.1"),
		RedisPort:      redisPort,
		InternalSecret: getEnv("INTERNAL_SECRET", "secret"),
		JWTSecret:      getEnv("JWT_SECRET", "secret"),
		LogLevel:       getEnv("LOG_LEVEL", "info"),
	}
}

func (c *Config) DSN() string {
	return fmt.Sprintf("postgres://%s:%s@%s:%d/%s?sslmode=disable",
		c.DBUsername, c.DBPassword, c.DBHost, c.DBPort, c.DBDatabase)
}

func (c *Config) RedisAddr() string {
	return fmt.Sprintf("%s:%d", c.RedisHost, c.RedisPort)
}

func getEnv(key, fallback string) string {
	if val, ok := os.LookupEnv(key); ok {
		return val
	}
	return fallback
}

func getEnvInt(key string, fallback int) int {
	if val, ok := os.LookupEnv(key); ok {
		if i, err := strconv.Atoi(val); err == nil {
			return i
		}
	}
	return fallback
}
