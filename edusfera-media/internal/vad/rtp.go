package vad

import "github.com/pion/rtp"

// AudioLevelResult contains the parsed audio level from the RTP header extension.
type AudioLevelResult struct {
	// Level is the audio level (0-127, 0=loudest, 127=silent).
	Level uint8

	// Voice is the VAD flag from the extension (false = voice active).
	// Some browsers always set this to false.
	Voice bool

	// Present is true when the audio-level extension was found.
	Present bool
}

// ExtractAudioLevel parses the audio-level RTP header extension (RFC 6464).
func ExtractAudioLevel(hdr *rtp.Header, extensionID int) AudioLevelResult {
	payload := hdr.GetExtension(uint8(extensionID))
	if len(payload) < 1 {
		return AudioLevelResult{Present: false}
	}

	levelByte := payload[0]
	return AudioLevelResult{
		Level:   levelByte & 0x7F,
		Voice:   (levelByte & 0x80) == 0,
		Present: true,
	}
}
