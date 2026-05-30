// Package media provides the SFU (Selective Forwarding Unit) engine
// that creates PeerConnections and forwards RTP tracks between participants.
package media

import (
	"context"
	"io"
	"log/slog"

	"github.com/pion/rtp"
	"github.com/pion/webrtc/v4"
)

// VADMonitor is notified on each audio RTP packet to detect voice activity.
type VADMonitor interface {
	// FeedRTPHeader is called for every RTP packet read from an audio track.
	// Returns true if the speaking state changed.
	FeedRTPHeader(hdr *rtp.Header) bool
}

// SFU manages WebRTC PeerConnection creation and configuration.
type SFU struct {
	config webrtc.Configuration
	api    *webrtc.API
	logger *slog.Logger
}

// NewSFU creates a new SFU engine with the given STUN/TURN servers.
func NewSFU(stunServers []string, logger *slog.Logger) (*SFU, error) {
	iceServers := make([]webrtc.ICEServer, 0, len(stunServers))
	for _, s := range stunServers {
		iceServers = append(iceServers, webrtc.ICEServer{
			URLs: []string{s},
		})
	}

	m := &webrtc.MediaEngine{}
	if err := m.RegisterDefaultCodecs(); err != nil {
		return nil, err
	}

	api := webrtc.NewAPI(webrtc.WithMediaEngine(m))

	return &SFU{
		config: webrtc.Configuration{
			ICEServers: iceServers,
		},
		api:    api,
		logger: logger.With("component", "sfu"),
	}, nil
}

// CreatePeerConnection creates a new PeerConnection using the SFU's ICE configuration.
func (s *SFU) CreatePeerConnection() (*webrtc.PeerConnection, error) {
	pc, err := s.api.NewPeerConnection(s.config)
	if err != nil {
		return nil, err
	}

	// Add transceivers for receiving audio and video from the client.
	// This ensures the PeerConnection is ready to receive media from the start.
	if _, err := pc.AddTransceiverFromKind(webrtc.RTPCodecTypeAudio, webrtc.RTPTransceiverInit{
		Direction: webrtc.RTPTransceiverDirectionRecvonly,
	}); err != nil {
		_ = pc.Close()
		return nil, err
	}

	if _, err := pc.AddTransceiverFromKind(webrtc.RTPCodecTypeVideo, webrtc.RTPTransceiverInit{
		Direction: webrtc.RTPTransceiverDirectionRecvonly,
	}); err != nil {
		_ = pc.Close()
		return nil, err
	}

	return pc, nil
}

// CreateLocalTrack creates a local static RTP track mirroring a remote track.
// The local track can be added to other PeerConnections for forwarding.
func CreateLocalTrack(remote *webrtc.TrackRemote, streamID string) (*webrtc.TrackLocalStaticRTP, error) {
	return webrtc.NewTrackLocalStaticRTP(
		remote.Codec().RTPCodecCapability,
		remote.ID(),
		streamID,
	)
}

// ForwardTrack reads RTP packets from a remote track and writes them to a local track.
// It blocks until the context is cancelled or the remote track is closed.
// If vad is non-nil, each audio packet is inspected for voice activity.
func ForwardTrack(ctx context.Context, remote *webrtc.TrackRemote, local *webrtc.TrackLocalStaticRTP, logger *slog.Logger, vad VADMonitor) {
	buf := make([]byte, 1500)

	for {
		select {
		case <-ctx.Done():
			return
		default:
		}

		n, _, readErr := remote.Read(buf)
		if readErr != nil {
			if readErr != io.EOF {
				logger.Debug("remote track read ended",
					"trackID", remote.ID(),
					"error", readErr,
				)
			}
			return
		}

		// Feed the VAD monitor if available (only for audio tracks).
		if vad != nil {
			var hdr rtp.Header
			if err := hdr.Unmarshal(buf[:n]); err == nil {
				vad.FeedRTPHeader(&hdr)
			}
		}

		if _, writeErr := local.Write(buf[:n]); writeErr != nil {
			if writeErr != io.ErrClosedPipe {
				logger.Debug("local track write error",
					"trackID", local.ID(),
					"error", writeErr,
				)
			}
			return
		}
	}
}
