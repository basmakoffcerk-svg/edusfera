package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"log"
	"net/url"
	"os"
	"os/signal"
	"sync"
	"syscall"
	"time"

	"github.com/edusfera/media/internal/auth"
	"github.com/golang-jwt/jwt/v5"
	"github.com/gorilla/websocket"
)

var (
	addr       = flag.String("addr", "localhost:8088", "http service address")
	room       = flag.String("room", "load-test-room", "room id")
	secret     = flag.String("secret", "super_secret_for_local_development_only", "jwt secret")
	clients    = flag.Int("clients", 50, "number of concurrent clients")
	msgDelay   = flag.Duration("delay", 1*time.Second, "delay between chat messages per client")
	runFor     = flag.Duration("time", 10*time.Second, "how long to run the test")
)

type WSMessage struct {
	Type string          `json:"type"`
	Data json.RawMessage `json:"data"`
}

type ChatMessage struct {
	Message string `json:"message"`
}

func main() {
	flag.Parse()

	log.Printf("Starting load test on %s with %d clients for %s", *addr, *clients, *runFor)

	var wg sync.WaitGroup
	ctx, cancel := contextWithStop()
	defer cancel()

	successCount := 0
	errorCount := 0
	var mu sync.Mutex

	for i := 0; i < *clients; i++ {
		wg.Add(1)
		go func(idx int) {
			defer wg.Done()
			
			time.Sleep(time.Duration(idx) * 10 * time.Millisecond)

			if err := runClient(ctx, idx); err != nil {
				mu.Lock()
				errorCount++
				mu.Unlock()
				log.Printf("Client %d error: %v", idx, err)
			} else {
				mu.Lock()
				successCount++
				mu.Unlock()
			}
		}(i)
	}

	timer := time.NewTimer(*runFor)
	select {
	case <-ctx:
		log.Println("Interrupted by user")
	case <-timer.C:
		log.Println("Time's up, stopping clients...")
		cancel()
	}

	wg.Wait()

	log.Printf("Load test finished. Success: %d, Errors: %d", successCount, errorCount)
}

func generateToken(userID, room, role, name, secret string) (string, error) {
	claims := auth.Claims{
		Room: room,
		Role: role,
		Name: name,
		RegisteredClaims: jwt.RegisteredClaims{
			Subject:   userID,
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(time.Hour)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	return token.SignedString([]byte(secret))
}

func runClient(ctx <-chan struct{}, idx int) error {
	token, err := generateToken(fmt.Sprintf("user-%d", idx), *room, "student", fmt.Sprintf("Test User %d", idx), *secret)
	if err != nil {
		return fmt.Errorf("generate token: %v", err)
	}

	u := url.URL{Scheme: "ws", Host: *addr, Path: "/ws/" + *room, RawQuery: "token=" + token}
	
	c, _, err := websocket.DefaultDialer.Dial(u.String(), nil)
	if err != nil {
		return fmt.Errorf("dial: %v", err)
	}
	defer c.Close()

	go func() {
		for {
			_, _, err := c.ReadMessage()
			if err != nil {
				return
			}
		}
	}()

	ticker := time.NewTicker(*msgDelay)
	defer ticker.Stop()

	for {
		select {
		case <-ctx:
			err := c.WriteMessage(websocket.CloseMessage, websocket.FormatCloseMessage(websocket.CloseNormalClosure, ""))
			return err
		case <-ticker.C:
			chatMsg := ChatMessage{Message: fmt.Sprintf("Hello from client %d", idx)}
			chatBytes, _ := json.Marshal(chatMsg)
			
			msg := WSMessage{
				Type: "chat",
				Data: chatBytes,
			}
			
			if err := c.WriteJSON(msg); err != nil {
				return fmt.Errorf("write: %v", err)
			}
		}
	}
}

func contextWithStop() (<-chan struct{}, func()) {
	ctx := make(chan struct{})
	c := make(chan os.Signal, 1)
	signal.Notify(c, os.Interrupt, syscall.SIGTERM)
	
	var once sync.Once
	cancel := func() {
		once.Do(func() {
			close(ctx)
		})
	}
	
	go func() {
		<-c
		cancel()
	}()
	
	return ctx, cancel
}