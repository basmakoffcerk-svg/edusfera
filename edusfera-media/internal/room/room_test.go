package room

import (
	"log/slog"
	"testing"
	"time"

	"github.com/edusfera/media/internal/config"
)

func TestRoomManager(t *testing.T) {
	logger := slog.Default()
	cfg := &config.Config{
		MaxRoomSize:     5,
		RoomIdleTimeout: 1 * time.Minute,
	}
	mgr := NewManager(cfg, logger)

	rm := mgr.GetOrCreateRoom("room1")
	if rm == nil {
		t.Fatal("expected room to be created")
	}

	rm2 := mgr.GetOrCreateRoom("room1")
	if rm != rm2 {
		t.Fatal("expected the same room instance")
	}

	mgr.CloseAll()
	rm3 := mgr.GetOrCreateRoom("room1")
	if rm == rm3 {
		t.Fatal("expected new room instance after CloseAll")
	}
}

func TestRoomParticipants(t *testing.T) {
	logger := slog.Default()
	cfg := &config.Config{
		MaxRoomSize:     2,
		RoomIdleTimeout: 1 * time.Minute,
	}
	mgr := NewManager(cfg, logger)

	rm := mgr.GetOrCreateRoom("room-limit")

	p1 := NewParticipant("p1", "u1", "Tutor", "tutor", nil)
	p2 := NewParticipant("p2", "u2", "Student 1", "student", nil)
	p3 := NewParticipant("p3", "u3", "Student 2", "student", nil)

	peers1, err := rm.AddParticipant(p1)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if len(peers1) != 0 {
		t.Fatalf("expected 0 peers for first participant, got %d", len(peers1))
	}

	peers2, err := rm.AddParticipant(p2)
	if err != nil {
		t.Fatalf("expected no error, got %v", err)
	}
	if len(peers2) != 1 {
		t.Fatalf("expected 1 peer for second participant, got %d", len(peers2))
	}

	_, err = rm.AddParticipant(p3)
	if err != ErrRoomFull {
		t.Fatalf("expected ErrRoomFull, got %v", err)
	}

	if count := rm.ParticipantCount(); count != 2 {
		t.Fatalf("expected 2 participants, got %d", count)
	}

	rm.RemoveParticipant("p1")
	if count := rm.ParticipantCount(); count != 1 {
		t.Fatalf("expected 1 participant, got %d", count)
	}
}
