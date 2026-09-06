package main

import (
	"log"
	"net/http"
	"os"
	"strings"

	"github.com/golang-jwt/jwt/v5"
	"github.com/MicahParks/keyfunc/v3"
)

var jwks *keyfunc.Keyfunc
var jwtAuthEnabled bool

// InitAuth инициализирует JWKS клиент, если авторизация включена.
func InitAuth() {
	jwtAuthEnabled = os.Getenv("JWT_AUTH_ENABLED") != "false"
	if !jwtAuthEnabled {
		log.Println("JWT Аутентификация ОТКЛЮЧЕНА (JWT_AUTH_ENABLED=false)")
		return
	}

	jwksURL := os.Getenv("CORE_JWKS_URL")
	if jwksURL == "" {
		jwksURL = "http://app:8000/.well-known/jwks.json" // Дефолт для Laravel Passport
	}

	log.Printf("Инициализация JWKS клиента для URL: %s", jwksURL)

	k, err := keyfunc.NewDefault([]string{jwksURL})
	if err != nil {
		log.Printf("ВНИМАНИЕ: Ошибка инициализации JWKS клиента: %v. Будет произведена повторная попытка при первом запросе.", err)
	}
	jwks = &k
}

// JWTMiddleware проверяет подпись JWT токена во входящем HTTP запросе
func JWTMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		// Если запрос был предварительно аутентифицирован шлюзом (Nginx auth_request)
		if gatewayUserId := r.Header.Get("X-User-Id"); gatewayUserId != "" {
			next.ServeHTTP(w, r)
			return
		}

		if !jwtAuthEnabled {
			next.ServeHTTP(w, r)
			return
		}

		authHeader := r.Header.Get("Authorization")
		if authHeader == "" {
			respondWithError(w, http.StatusUnauthorized, "missing_authorization_header")
			return
		}

		parts := strings.Split(authHeader, " ")
		if len(parts) != 2 || strings.ToLower(parts[0]) != "bearer" {
			respondWithError(w, http.StatusUnauthorized, "invalid_authorization_format")
			return
		}

		tokenStr := parts[1]

		// Если JWKS клиент не был успешно инициализирован ранее, пробуем инициализировать
		if jwks == nil {
			jwksURL := os.Getenv("CORE_JWKS_URL")
			if jwksURL == "" {
				jwksURL = "http://app:8000/.well-known/jwks.json"
			}
			k, err := keyfunc.NewDefault([]string{jwksURL})
			if err != nil {
				log.Printf("Ошибка при ленивой инициализации JWKS: %v", err)
				respondWithError(w, http.StatusInternalServerError, "auth_provider_unavailable")
				return
			}
			jwks = &k
		}

		token, err := jwt.Parse(tokenStr, (*jwks).Keyfunc)
		if err != nil || !token.Valid {
			respondWithError(w, http.StatusUnauthorized, "invalid_or_expired_token: "+err.Error())
			return
		}

		// Токен валиден, пропускаем запрос
		next.ServeHTTP(w, r)
	})
}
