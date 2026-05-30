package room

import (
	"sync"

	"github.com/edusfera/media/internal/vad"
	"github.com/gorilla/websocket"
	"github.com/pion/webrtc/v4"
)

// Participant represents a single user connected to a room via WebSocket and WebRTC.
type Participant struct {
	ID     string
	UserID string
	Name   string
	Role   string

	mu   sync.Mutex
	conn *websocket.Conn
	pc   *webrtc.PeerConnection

	audio bool
	video bool

	// speaking indicates the debounced voice activity state of this participant.
	speaking bool

	// vadDetector performs voice activity detection on this participant's audio track.
	vadDetector *vad.Detector

	// onSpeakingChanged is called when the VAD state changes.
	// Set by the signaling handler. Must not be called under participant lock.
	onSpeakingChanged func(speaking bool)

	// subscriberMode indicates this participant is receive-only (no tracks forwarded to others).
	subscriberMode bool

	// publishedTracks holds local copies of tracks published by this participant.
	// key: remote track ID
	publishedTracks map[string]*webrtc.TrackLocalStaticRTP

	// inboundSenders tracks RTP senders on THIS participant's PeerConnection
	// for tracks originating from OTHER participants.
	// key: "sourceParticipantID:trackID"
	inboundSenders map[string]*webrtc.RTPSender

	// needsRenegotiation is set when an SDP negotiation was deferred
	// because the PeerConnection was not in a stable state.
	needsRenegotiation bool

	// initialNegotiationDone tracks whether the first offer/answer exchange has completed.
	initialNegotiationDone bool

	// isReconnected is set to true when an existing participant reconnects
	// (replaces their WebSocket connection).
	isReconnected bool

	done      chan struct{}
	closeOnce sync.Once
}

// NewParticipant creates a participant with sensible defaults (audio & video enabled).
func NewParticipant(id, userID, name, role string, conn *websocket.Conn) *Participant {
	return &Participant{
		ID:              id,
		UserID:          userID,
		Name:            name,
		Role:            role,
		conn:            conn,
		audio:           true,
		video:           true,
		speaking:        false,
		publishedTracks: make(map[string]*webrtc.TrackLocalStaticRTP),
		inboundSenders:  make(map[string]*webrtc.RTPSender),
		done:            make(chan struct{}),
	}
}

// ---------------------------------------------------------------------------
// PeerConnection
// ---------------------------------------------------------------------------

// SetPeerConnection assigns the WebRTC PeerConnection.
func (p *Participant) SetPeerConnection(pc *webrtc.PeerConnection) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.pc = pc
}

// PeerConnection returns the WebRTC PeerConnection (may be nil).
func (p *Participant) PeerConnection() *webrtc.PeerConnection {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.pc
}

// ---------------------------------------------------------------------------
// WebSocket
// ---------------------------------------------------------------------------

// Conn returns the underlying WebSocket connection.
func (p *Participant) Conn() *websocket.Conn {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.conn
}

// SendJSON writes a JSON-encoded message to the WebSocket connection.
func (p *Participant) SendJSON(v interface{}) error {
	p.mu.Lock()
	defer p.mu.Unlock()
	if p.conn == nil {
		return nil
	}
	return p.conn.WriteJSON(v)
}

// ---------------------------------------------------------------------------
// Media state
// ---------------------------------------------------------------------------

// SetMediaState toggles the audio or video state.
func (p *Participant) SetMediaState(kind string, enabled bool) {
	p.mu.Lock()
	defer p.mu.Unlock()
	switch kind {
	case "audio":
		p.audio = enabled
	case "video":
		p.video = enabled
	}
}

// MediaState returns the current audio and video enabled state.
func (p *Participant) MediaState() (audio, video bool) {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.audio, p.video
}

// ---------------------------------------------------------------------------
// VAD / Speaking state
// ---------------------------------------------------------------------------

// SetVADDetector assigns a VAD detector for this participant.
func (p *Participant) SetVADDetector(d *vad.Detector) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.vadDetector = d
}

// VADDetector returns the participant's VAD detector, or nil.
func (p *Participant) VADDetector() *vad.Detector {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.vadDetector
}

// SetOnSpeakingChanged registers the callback for speaking state changes.
// The callback is called WITHOUT the participant lock held.
func (p *Participant) SetOnSpeakingChanged(fn func(speaking bool)) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.onSpeakingChanged = fn
}

// FeedAudioLevel feeds an audio level value to the VAD detector.
// If speaking state changed, the callback (if any) is invoked.
func (p *Participant) FeedAudioLevel(level uint8) {
	p.mu.Lock()
	detector := p.vadDetector
	cb := p.onSpeakingChanged
	p.mu.Unlock()

	if detector == nil || cb == nil {
		return
	}

	if detector.FeedAudioLevel(level) {
		cb(detector.IsSpeaking())
	}
}

// SetSpeaking manually sets the speaking state (used when VAD is not available).
func (p *Participant) SetSpeaking(speaking bool) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.speaking = speaking
}

// Speaking returns the current speaking state.
func (p *Participant) Speaking() bool {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.speaking
}

// ---------------------------------------------------------------------------
// Published tracks (tracks this participant sends to the SFU)
// ---------------------------------------------------------------------------

// AddPublishedTrack stores a local track copy for forwarding.
func (p *Participant) AddPublishedTrack(trackID string, track *webrtc.TrackLocalStaticRTP) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.publishedTracks[trackID] = track
}

// RemovePublishedTrack removes a local track copy.
func (p *Participant) RemovePublishedTrack(trackID string) {
	p.mu.Lock()
	defer p.mu.Unlock()
	delete(p.publishedTracks, trackID)
}

// PublishedTracks returns a snapshot copy of the published tracks map.
func (p *Participant) PublishedTracks() map[string]*webrtc.TrackLocalStaticRTP {
	p.mu.Lock()
	defer p.mu.Unlock()
	tracks := make(map[string]*webrtc.TrackLocalStaticRTP, len(p.publishedTracks))
	for k, v := range p.publishedTracks {
		tracks[k] = v
	}
	return tracks
}

// SubscriberMode returns whether this participant is in subscriber-only mode.
func (p *Participant) SubscriberMode() bool {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.subscriberMode
}

// SetSubscriberMode sets whether this participant is in subscriber-only mode.
func (p *Participant) SetSubscriberMode(v bool) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.subscriberMode = v
}

// ---------------------------------------------------------------------------
// Inbound senders (tracks from other participants added to this PC)
// ---------------------------------------------------------------------------

// AddInboundSender records an RTP sender for a track from another participant.
func (p *Participant) AddInboundSender(key string, sender *webrtc.RTPSender) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.inboundSenders[key] = sender
}

// RemoveInboundSender removes and returns an RTP sender by key.
func (p *Participant) RemoveInboundSender(key string) *webrtc.RTPSender {
	p.mu.Lock()
	defer p.mu.Unlock()
	s := p.inboundSenders[key]
	delete(p.inboundSenders, key)
	return s
}

// InboundSenderKeys returns all current inbound sender keys.
func (p *Participant) InboundSenderKeys() []string {
	p.mu.Lock()
	defer p.mu.Unlock()
	keys := make([]string, 0, len(p.inboundSenders))
	for k := range p.inboundSenders {
		keys = append(keys, k)
	}
	return keys
}

// ---------------------------------------------------------------------------
// Negotiation state
// ---------------------------------------------------------------------------

// MarkNeedsRenegotiation flags that a renegotiation should happen
// once the PeerConnection reaches a stable state.
func (p *Participant) MarkNeedsRenegotiation() {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.needsRenegotiation = true
}

// CheckAndClearRenegotiation returns true if renegotiation was pending and clears the flag.
func (p *Participant) CheckAndClearRenegotiation() bool {
	p.mu.Lock()
	defer p.mu.Unlock()
	needs := p.needsRenegotiation
	p.needsRenegotiation = false
	return needs
}

// MarkInitialNegotiationDone marks the first offer/answer exchange as completed.
// Returns true if this is the first call (i.e., the flag was not previously set).
func (p *Participant) MarkInitialNegotiationDone() bool {
	p.mu.Lock()
	defer p.mu.Unlock()
	if p.initialNegotiationDone {
		return false
	}
	p.initialNegotiationDone = true
	return true
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

// SetReconnected marks this participant as having reconnected (WebSocket replaced).
func (p *Participant) SetReconnected(v bool) {
	p.mu.Lock()
	defer p.mu.Unlock()
	p.isReconnected = v
}

// IsReconnected returns whether this participant is a reconnection.
func (p *Participant) IsReconnected() bool {
	p.mu.Lock()
	defer p.mu.Unlock()
	return p.isReconnected
}

// ReplaceWebSocket swaps the WebSocket connection (used during reconnection).
func (p *Participant) ReplaceWebSocket(conn *websocket.Conn) *websocket.Conn {
	p.mu.Lock()
	defer p.mu.Unlock()
	old := p.conn
	p.conn = conn
	p.isReconnected = true
	return old
}

// Close tears down the PeerConnection and WebSocket connection.
// It is safe to call multiple times.
func (p *Participant) Close() {
	p.closeOnce.Do(func() {
		close(p.done)

		p.mu.Lock()
		defer p.mu.Unlock()

		if p.pc != nil {
			_ = p.pc.Close()
			p.pc = nil
		}
		if p.conn != nil {
			_ = p.conn.Close()
			p.conn = nil
		}
	})
}

// Done returns a channel that is closed when the participant is cleaned up.
func (p *Participant) Done() <-chan struct{} {
	return p.done
}
