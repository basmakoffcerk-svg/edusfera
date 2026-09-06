// Package auth: JWKS support for RS256 classroom tokens.
//
// Laravel issue classroom-JWT, подписанные RS256 (RsaClassroomTokenIssuer),
// и публикует публичные ключи на GET /api/v1/.well-known/jwks.json. Раньше
// сервис принимал только HS256 с shared secret — токены RS256 отбрасывались
// с "unexpected signing method: RS256", и подключение к классу всегда
// завершалось 401.
package auth

import (
	"crypto/rsa"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io"
	"math/big"
	"net/http"
	"sync"
	"time"
)

type jwk struct {
	Kty string `json:"kty"`
	Kid string `json:"kid"`
	Alg string `json:"alg"`
	N   string `json:"n"`
	E   string `json:"e"`
}

type jwksResponse struct {
	Keys []jwk `json:"keys"`
}

// JWKSProvider fetches and caches a remote JWK Set. Keys are refreshed on a
// TTL; a stale copy is served if a refresh fails (log-only degradation), so a
// transient Laravel outage does not kick participants out.
type JWKSProvider struct {
	url   string
	client *http.Client

	mu        sync.RWMutex
	keys      map[string]*rsa.PublicKey
	fetchedAt time.Time
	ttl       time.Duration
}

// NewJWKSProvider builds a provider for the given JWK Set URL.
func NewJWKSProvider(rawURL string) *JWKSProvider {
	return &JWKSProvider{
		url:    rawURL,
		client: &http.Client{Timeout: 10 * time.Second},
		keys:   make(map[string]*rsa.PublicKey),
		ttl:    15 * time.Minute,
	}
}

// Key returns the RSA public key for a token's kid, refreshing the set when
// stale or when the kid is unknown (covers key rotation).
func (p *JWKSProvider) Key(kid string) (*rsa.PublicKey, error) {
	p.mu.RLock()
	key, ok := p.keys[kid]
	fresh := time.Since(p.fetchedAt) < p.ttl
	p.mu.RUnlock()

	if ok && fresh {
		return key, nil
	}

	if err := p.refresh(); err != nil {
		// Serve stale keys rather than failing closed on a transient error.
		if ok {
			return key, nil
		}
		return nil, fmt.Errorf("jwks refresh failed: %w", err)
	}

	p.mu.RLock()
	defer p.mu.RUnlock()
	if key, ok := p.keys[kid]; ok {
		return key, nil
	}
	return nil, fmt.Errorf("no key for kid %q in JWKS", kid)
}

func (p *JWKSProvider) refresh() error {
	resp, err := p.client.Get(p.url)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("jwks endpoint returned %d", resp.StatusCode)
	}

	var set jwksResponse
	if err := json.NewDecoder(io.LimitReader(resp.Body, 1<<20)).Decode(&set); err != nil {
		return err
	}

	keys := make(map[string]*rsa.PublicKey, len(set.Keys))
	for _, k := range set.Keys {
		if k.Kty != "RSA" || k.N == "" || k.E == "" || k.Kid == "" {
			continue
		}
		pub, err := parseRsaJwk(k.N, k.E)
		if err != nil {
			continue
		}
		keys[k.Kid] = pub
	}

	if len(keys) == 0 {
		return fmt.Errorf("jwks contains no usable RSA keys")
	}

	p.mu.Lock()
	p.keys = keys
	p.fetchedAt = time.Now()
	p.mu.Unlock()
	return nil
}

func parseRsaJwk(nB64, eB64 string) (*rsa.PublicKey, error) {
	nBytes, err := base64.RawURLEncoding.DecodeString(nB64)
	if err != nil {
		return nil, fmt.Errorf("invalid JWK modulus: %w", err)
	}
	eBytes, err := base64.RawURLEncoding.DecodeString(eB64)
	if err != nil {
		return nil, fmt.Errorf("invalid JWK exponent: %w", err)
	}

	e := new(big.Int).SetBytes(eBytes)
	if !e.IsInt64() || e.Int64() <= 0 || e.Int64()%2 == 0 {
		return nil, fmt.Errorf("invalid RSA exponent")
	}

	return &rsa.PublicKey{
		N: new(big.Int).SetBytes(nBytes),
		E: int(e.Int64()),
	}, nil
}

// providers caches one JWKSProvider per URL (ValidateToken is called per
// connection; caching avoids refetching the key set every time).
var providers sync.Map

func jwksProviderFor(rawURL string) *JWKSProvider {
	if cached, ok := providers.Load(rawURL); ok {
		return cached.(*JWKSProvider)
	}
	created := NewJWKSProvider(rawURL)
	actual, _ := providers.LoadOrStore(rawURL, created)
	return actual.(*JWKSProvider)
}
