package auth

import (
	"fmt"

	"github.com/golang-jwt/jwt/v5"
)

type Claims struct {
	Room string `json:"room"`
	Role string `json:"role"`
	Name string `json:"name"`
	jwt.RegisteredClaims
}

// ValidateToken parses and validates a JWT issued by Laravel.
//
// Laravel подписывает classroom-токены алгоритмом RS256 (публичный ключ —
// в JWKS, GET /api/v1/.well-known/jwks.json). HS256 с shared secret остаётся
// переходным вариантом; без секрета HS256-токены отвергаются. Список
// допустимых алгоритмов ограничен явно (защита от alg confusion).
func ValidateToken(tokenStr, hs256Secret, jwksURL string) (*Claims, error) {
	token, err := jwt.ParseWithClaims(tokenStr, &Claims{}, func(t *jwt.Token) (interface{}, error) {
		switch t.Method.(type) {
		case *jwt.SigningMethodRSA:
			if jwksURL == "" {
				return nil, fmt.Errorf("RS256 token received but JWKS URL is not configured")
			}
			kid, _ := t.Header["kid"].(string)
			if kid == "" {
				return nil, fmt.Errorf("RS256 token without kid header")
			}
			return jwksProviderFor(jwksURL).Key(kid)
		case *jwt.SigningMethodHMAC:
			if hs256Secret == "" {
				return nil, fmt.Errorf("HS256 token received but shared secret is not configured")
			}
			return []byte(hs256Secret), nil
		default:
			return nil, fmt.Errorf("unexpected signing method: %v", t.Header["alg"])
		}
	}, jwt.WithValidMethods([]string{"RS256", "HS256"}))
	if err != nil {
		return nil, fmt.Errorf("token validation failed: %w", err)
	}

	claims, ok := token.Claims.(*Claims)
	if !ok || !token.Valid {
		return nil, fmt.Errorf("invalid token claims")
	}

	return claims, nil
}
