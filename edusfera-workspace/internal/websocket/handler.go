package websocket

import (
	"context"
	"encoding/json"
	"log/slog"
	"net/http"
	"time"

	"edusfera-workspace/internal/auth"
	"edusfera-workspace/internal/redis"
	"edusfera-workspace/internal/workspace"
	"github.com/google/uuid"
	"github.com/gorilla/websocket"
)

var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
	CheckOrigin: func(r *http.Request) bool {
		// Allow all origins for dev/testing. In production, we'd lock this down.
		return true
	},
}

type Handler struct {
	pool       *Pool
	manager    *workspace.Manager
	rdb        *redis.RedisClient
	jwtSecret  string
	logger     *slog.Logger
}

func NewHandler(pool *Pool, manager *workspace.Manager, rdb *redis.RedisClient, jwtSecret string, logger *slog.Logger) *Handler {
	return &Handler{
		pool:      pool,
		manager:   manager,
		rdb:       rdb,
		jwtSecret: jwtSecret,
		logger:    logger,
	}
}

func (h *Handler) HandleWS(w http.ResponseWriter, r *http.Request, roomId string) {
	// Check if API Gateway passed authenticated user headers
	gatewayUserId := r.Header.Get("X-User-Id")
	if gatewayUserId != "" {
		gatewayUserRole := r.Header.Get("X-User-Role")
		gatewayRoomId := r.Header.Get("X-Classroom-Room-Id")

		h.logger.Info("authenticated via gateway headers", "userId", gatewayUserId, "role", gatewayUserRole, "room", gatewayRoomId)

		// Verification: room ID must match for classroom token-authenticated requests
		// S2S/service requests (where X-User-Role is "service") are allowed globally, but users must belong to the room.
		if gatewayUserRole != "service" && gatewayRoomId != roomId {
			h.logger.Warn("WebSocket connection rejected: gateway room mismatch", "roomId", roomId, "gatewayRoomId", gatewayRoomId)
			http.Error(w, "Forbidden: room mismatch", http.StatusForbidden)
			return
		}
	} else {
		// Fallback: Authenticate JWT token from query string (direct connection / tests / local dev without gateway)
		tokenStr := r.URL.Query().Get("token")
		if tokenStr == "" {
			h.logger.Warn("WebSocket connection rejected: missing token", "roomId", roomId)
			http.Error(w, "Unauthorized: missing token", http.StatusUnauthorized)
			return
		}

		claims, err := auth.ValidateToken(tokenStr, h.jwtSecret)
		if err != nil {
			h.logger.Warn("WebSocket connection rejected: invalid token", "roomId", roomId, "error", err)
			http.Error(w, "Unauthorized: invalid token", http.StatusUnauthorized)
			return
		}

		// Verify token room matches request room
		if claims.Room != roomId {
			h.logger.Warn("WebSocket connection rejected: room mismatch", "roomId", roomId, "tokenRoom", claims.Room)
			http.Error(w, "Forbidden: room mismatch", http.StatusForbidden)
			return
		}
	}

	// 2. Upgrade to WebSocket connection
	conn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		h.logger.Error("failed to upgrade to websocket", "error", err)
		return
	}

	client := &Client{
		ID:     uuid.New().String(),
		Conn:   conn,
		Send:   make(chan []byte, 256),
		RoomID: roomId,
	}

	room := h.pool.GetRoom(roomId)
	room.Register <- client

	// Start write pump in a background goroutine
	go client.Write()

	// 3. Load initial board state and send it to the newly connected client
	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	board, err := h.manager.LoadBoard(ctx, roomId)
	if err != nil {
		h.logger.Error("failed to load board on join", "roomId", roomId, "error", err)
	}

	if err := client.SendJSON("workspace.sync", board); err != nil {
		h.logger.Error("failed to send initial board sync", "clientId", client.ID, "error", err)
	}

	// 4. Start reading messages from client
	client.Read(room, func(payload []byte) {
		var wsMsg workspace.WsMessage
		if err := json.Unmarshal(payload, &wsMsg); err != nil {
			h.logger.Error("failed to unmarshal client ws message", "error", err)
			return
		}

		h.logger.Debug("received websocket message from client", "event", wsMsg.Event, "clientId", client.ID)

		// Process client mutation
		ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
		defer cancel()

		updatedBoard, err := h.manager.ApplyMutation(ctx, roomId, wsMsg.Event, wsMsg.Payload)
		if err != nil {
			h.logger.Error("failed to apply mutation", "event", wsMsg.Event, "roomId", roomId, "error", err)
			// Send error event back to client
			_ = client.SendJSON("workspace.error", map[string]string{"message": err.Error()})
			return
		}

		// Broadcast updated board state to all instances via Redis Pub/Sub
		syncMsg := struct {
			Event   string           `json:"event"`
			Payload *workspace.Board `json:"payload"`
		}{
			Event:   "workspace.sync",
			Payload: updatedBoard,
		}

		syncJSON, err := json.Marshal(syncMsg)
		if err != nil {
			h.logger.Error("failed to marshal sync message for pubsub", "error", err)
			return
		}

		if err := h.rdb.PublishEvent(ctx, roomId, string(syncJSON)); err != nil {
			h.logger.Error("failed to publish sync event to Redis Pub/Sub", "roomId", roomId, "error", err)
		}
	})
}
