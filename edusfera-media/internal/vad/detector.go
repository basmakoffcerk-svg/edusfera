// Package vad provides voice activity detection for WebRTC audio streams.
//
// It uses the audio-level RTP header extension (RFC 6464) sent by browsers
// to determine whether a participant is speaking. The detector debounces
// state changes to avoid flickering.
package vad

import (
	"sync"
	"time"
)

// Config tunes the VAD detector behaviour.
type Config struct {
	// Threshold is the audio level threshold (0-127, 0=loudest, 127=silent).
	// Levels below this value are considered voice activity. Default: 35.
	Threshold uint8

	// HoldMs is the duration in milliseconds to keep speaking state active
	// after the last detected voice activity. Default: 600.
	HoldMs int
}

// DefaultConfig returns sensible defaults for the VAD detector.
func DefaultConfig() Config {
	return Config{
		Threshold: 35,
		HoldMs:    600,
	}
}

// Detector monitors audio levels and determines speaking state with debounce.
type Detector struct {
	mu sync.Mutex

	speaking     bool
	lastSpeechAt time.Time
	cfg          Config
}

// NewDetector creates a VAD detector with the given config.
// If cfg contains zero values, defaults are used.
func NewDetector(cfg Config) *Detector {
	if cfg.Threshold == 0 {
		cfg.Threshold = DefaultConfig().Threshold
	}
	if cfg.HoldMs == 0 {
		cfg.HoldMs = DefaultConfig().HoldMs
	}
	return &Detector{cfg: cfg}
}

// FeedAudioLevel feeds an audio level value (0-127, 0=loudest) and returns
// whether the speaking state changed as a result.
func (d *Detector) FeedAudioLevel(level uint8) bool {
	d.mu.Lock()
	defer d.mu.Unlock()

	now := time.Now()
	isVoice := level < d.cfg.Threshold

	if isVoice {
		d.lastSpeechAt = now
		if !d.speaking {
			d.speaking = true
			return true // transitioned to speaking
		}
		return false
	}

	// No voice detected — check if hold period expired.
	if d.speaking && now.Sub(d.lastSpeechAt) > time.Duration(d.cfg.HoldMs)*time.Millisecond {
		d.speaking = false
		return true // transitioned to silent
	}

	return false
}

// IsSpeaking returns the current debounced speaking state.
func (d *Detector) IsSpeaking() bool {
	d.mu.Lock()
	defer d.mu.Unlock()

	if !d.speaking {
		return false
	}

	// Auto-expire if hold expired since last check.
	if time.Since(d.lastSpeechAt) > time.Duration(d.cfg.HoldMs)*time.Millisecond {
		d.speaking = false
		return false
	}

	return true
}

// Reset clears the detector state.
func (d *Detector) Reset() {
	d.mu.Lock()
	defer d.mu.Unlock()
	d.speaking = false
	d.lastSpeechAt = time.Time{}
}
