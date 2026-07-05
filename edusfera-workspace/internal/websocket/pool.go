package websocket

import (
	"context"
	"encoding/json"
	"fmt"
	"log/slog"
	"sync"

	"edusfera-workspace/internal/redis"
	"github.com/gorilla/websocket"
)

type Client struct {
	ID     string
	Conn   *websocket.Conn
	Send   chan []byte
	RoomID string
}

type Room struct {
	RoomID     string
	Clients    map[*Client]bool
	Register   chan *Client
	Unregister chan *Client
	Broadcast  chan []byte
	ctx        context.Context
	cancel     context.CancelFunc
	rdb        *redis.RedisClient
	logger     *slog.Logger
}

type Pool struct {
	mu     sync.RWMutex
	Rooms  map[string]*Room
	rdb    *redis.RedisClient
	logger *slog.Logger
}

func NewPool(rdb *redis.RedisClient, logger *slog.Logger) *Pool {
	return &Pool{
		Rooms:  make(map[string]*Room),
		rdb:    rdb,
		logger: logger,
	}
}

func (p *Pool) GetRoom(roomId string) *Room {
	p.mu.Lock()
	defer p.mu.Unlock()

	if room, ok := p.Rooms[roomId]; ok {
		return room
	}

	ctx, cancel := context.WithCancel(context.Background())
	room := &Room{
		RoomID:     roomId,
		Clients:    make(map[*Client]bool),
		Register:   make(chan *Client),
		Unregister: make(chan *Client),
		Broadcast:  make(chan []byte, 256),
		ctx:        ctx,
		cancel:     cancel,
		rdb:        p.rdb,
		logger:     p.logger,
	}

	p.Rooms[roomId] = room
	go room.Run()

	return room
}

func (p *Pool) RemoveRoom(roomId string) {
	p.mu.Lock()
	defer p.mu.Unlock()

	if room, ok := p.Rooms[roomId]; ok {
		room.cancel()
		delete(p.Rooms, roomId)
	}
}

func (r *Room) Run() {
	r.logger.Info("starting room worker", "roomId", r.RoomID)

	// Subscribe to Redis Pub/Sub channel for this room
	pubsub := r.rdb.Subscribe(r.ctx, r.RoomID)
	defer pubsub.Close()

	ch := pubsub.Channel()

	// Handle cross-instance events from Redis Pub/Sub
	go func() {
		for {
			select {
			case <-r.ctx.Done():
				return
			case msg, ok := <-ch:
				if !ok {
					return
				}
				// Forward event to local broadcast channel
				r.Broadcast <- []byte(msg.Payload)
			}
		}
	}()

	for {
		select {
		case <-r.ctx.Done():
			r.logger.Info("stopping room worker", "roomId", r.RoomID)
			return

		case client := <-r.Register:
			r.Clients[client] = true
			r.logger.Info("client connected to room", "roomId", r.RoomID, "clientId", client.ID)

		case client := <-r.Unregister:
			if _, ok := r.Clients[client]; ok {
				delete(r.Clients, client)
				close(client.Send)
				r.logger.Info("client disconnected from room", "roomId", r.RoomID, "clientId", client.ID)
			}

		case message := <-r.Broadcast:
			// Send message to all connected clients in this room
			for client := range r.Clients {
				select {
				case client.Send <- message:
				default:
					close(client.Send)
					delete(r.Clients, client)
				}
			}
		}
	}
}

func (c *Client) Read(room *Room, onMessage func(payload []byte)) {
	defer func() {
		room.Unregister <- c
		c.Conn.Close()
	}()

	for {
		_, message, err := c.Conn.ReadMessage()
		if err != nil {
			break
		}
		onMessage(message)
	}
}

func (c *Client) Write() {
	defer c.Conn.Close()

	for message := range c.Send {
		err := c.Conn.WriteMessage(websocket.TextMessage, message)
		if err != nil {
			break
		}
	}
}

func (c *Client) SendJSON(event string, payload interface{}) error {
	msg := struct {
		Event   string      `json:"event"`
		Payload interface{} `json:"payload"`
	}{
		Event:   event,
		Payload: payload,
	}

	raw, err := json.Marshal(msg)
	if err != nil {
		return err
	}

	select {
	case c.Send <- raw:
		return nil
	default:
		return fmt.Errorf("client send buffer full")
	}
}
