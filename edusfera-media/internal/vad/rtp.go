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

// ExtractAudioLevel parses the audio-level RTP header extension (RFC 6464)
// from a one-byte header extension (RFC 8285, profile 0xBEDE).
//
// The extension ID for audio-level is typically negotiated via SDP and is
// most commonly 1. Pass the negotiated ID; if unsure, try ID 1 first.
func ExtractAudioLevel(hdr *rtp.Header, extensionID int) AudioLevelResult {
	if !hdr.Extension || hdr.ExtensionProfile != 0xBEDE {
		return AudioLevelResult{Present: false}
	}

	payload := hdr.ExtensionPayload
	if len(payload) < 1 {
		return AudioLevelResult{Present: false}
	}

	i := 0
	for i < len(payload)-1 {
		// Skip padding (zero byte).
		if payload[i] == 0 {
			i++
			continue
		}

		id := payload[i] >> 4            // top 4 bits = extension ID
		length := int(payload[i] & 0x0F) // bottom 4 bits = (data length in bytes - 1) / 4
		i++

		// length == 0 means 1 byte of data follows.
		if i+length*4 > len(payload) {
			break
		}

		if id == uint8(extensionID) && length == 0 {
			levelByte := payload[i]
			return AudioLevelResult{
				Level:   levelByte & 0x7F, // bottom 7 bits
				Voice:   (levelByte & 0x80) == 0,
				Present: true,
			}
		}

		i += length * 4
	}

	return AudioLevelResult{Present: false}
}
