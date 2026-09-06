// Package health provides the HTTP health-check endpoint.
package health

import (
	"encoding/json"
	"net/http"
)

// StatsProvider returns current room and peer counts.
type StatsProvider interface {
	Stats() (rooms, peers int)
}

// Handler serves the /health endpoint.
type Handler struct {
	stats StatsProvider
}

// NewHandler creates a health-check handler backed by the given stats provider.
func NewHandler(stats StatsProvider) *Handler {
	return &Handler{stats: stats}
}

// ServeHTTP responds with a JSON health status including room/peer counts.
func (h *Handler) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		http.Error(w, `{"error":"method not allowed"}`, http.StatusMethodNotAllowed)
		return
	}

	rooms, peers := h.stats.Stats()

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)

	_ = json.NewEncoder(w).Encode(map[string]interface{}{
		"status": "ok",
		"rooms":  rooms,
		"peers":  peers,
	})
}
