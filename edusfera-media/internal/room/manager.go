package room

import (
	"log/slog"
	"sync"
	"time"

	"github.com/edusfera/media/internal/config"
)

// Manager manages all active rooms.
type Manager struct {
	mu          sync.RWMutex
	rooms       map[string]*Room
	maxSize     int
	idleTimeout time.Duration
	cfg         *config.Config
	logger      *slog.Logger
}

// NewManager creates a room manager.
func NewManager(cfg *config.Config, logger *slog.Logger) *Manager {
	return &Manager{
		rooms:       make(map[string]*Room),
		maxSize:     cfg.MaxRoomSize,
		idleTimeout: cfg.RoomIdleTimeout,
		cfg:         cfg,
		logger:      logger.With("component", "room-manager"),
	}
}

// GetOrCreateRoom returns an existing room or creates a new one.
func (m *Manager) GetOrCreateRoom(roomID string) *Room {
	m.mu.Lock()
	defer m.mu.Unlock()

	if r, ok := m.rooms[roomID]; ok {
		return r
	}

	r := NewRoom(roomID, m.maxSize, m.idleTimeout, m.logger, m.removeRoom, m.cfg.LaravelAPIUrl, m.cfg.JWTSecret)
	m.rooms[roomID] = r
	m.logger.Info("room created", "room", roomID)
	return r
}

// GetRoom returns a room by ID or nil if not found.
func (m *Manager) GetRoom(roomID string) *Room {
	m.mu.RLock()
	defer m.mu.RUnlock()
	return m.rooms[roomID]
}

// removeRoom is called by a Room's idle timer callback to remove the room.
func (m *Manager) removeRoom(roomID string) {
	m.mu.Lock()
	defer m.mu.Unlock()

	if r, ok := m.rooms[roomID]; ok {
		r.Cancel()
		delete(m.rooms, roomID)
		m.logger.Info("room removed after idle timeout", "room", roomID)
	}
}

// Stats returns the total number of active rooms and connected peers.
// Implements the health.StatsProvider interface.
func (m *Manager) Stats() (rooms, peers int) {
	m.mu.RLock()
	defer m.mu.RUnlock()

	rooms = len(m.rooms)
	for _, r := range m.rooms {
		peers += r.ParticipantCount()
	}
	return
}

// CloseAll force-closes every room. Used during graceful shutdown.
func (m *Manager) CloseAll() {
	m.mu.Lock()
	defer m.mu.Unlock()

	for id, r := range m.rooms {
		r.ForceClose()
		delete(m.rooms, id)
	}
	m.logger.Info("all rooms closed")
}
