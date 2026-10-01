// ============================================
// Edusfera.by — Virtual Classroom 2.0
// LiveKit Enterprise SFU & Real-time Whiteboard
// ============================================

import { Room, RoomEvent, Track, createLocalAudioTrack, createLocalVideoTrack, createLocalTracks, AudioPresets, VideoPresets } from 'livekit-client';

/**
 * Lightweight HTTP Signaling Client for WebRTC over Laravel Backend
 */
class SignalingClient {
    constructor(lessonId, csrfToken, userRole) {
        this.lessonId = lessonId;
        this.csrfToken = csrfToken;
        this.userRole = userRole; // 'tutor' | 'student'
        this.clientId = 'client_' + Math.random().toString(36).substring(2, 9) + '_' + Date.now();
        this.lastServerTime = 0;
        this.lastSeq = -1;
        this.seenSignalIds = new Set();
        this.sessionStartTime = Date.now() - 30000; // Accept signals from last 30s only to ignore stale sessions
        this.pollTimer = null;
        this.isPolling = false;
        this.handlers = new Map();
        this.pollInterval = 600; // ms (smooth fast start)
        this.fastPollInterval = 600; // ms (during handshake / negotiation / reconnect)
        this.normalPollInterval = 3000; // ms (stable call)
        this.isStopped = false;
        this.latencyMs = 0;
        this.signalsSent = 0;
        this.signalsReceived = 0;
        this.onPing = null;
    }

    on(type, handler) {
        if (!this.handlers.has(type)) {
            this.handlers.set(type, new Set());
        }
        this.handlers.get(type).add(handler);
        return () => this.handlers.get(type)?.delete(handler);
    }

    _dispatch(type, payload, meta) {
        this.handlers.get(type)?.forEach((fn) => {
            try { fn(payload, meta); } catch (e) { console.error('[Signaling] Dispatch error:', e); }
        });
    }

    startPolling() {
        if (this.isPolling || this.isStopped) return;
        this.isPolling = true;
        this._pollLoop();
    }

    setFastPolling(fast = true) {
        const targetInterval = fast ? this.fastPollInterval : this.normalPollInterval;
        if (this.pollInterval !== targetInterval) {
            this.pollInterval = targetInterval;
            if (fast && this.pollTimer) {
                clearTimeout(this.pollTimer);
                this.pollTimer = setTimeout(() => this._pollLoop(), 40);
            }
        }
    }

    stop() {
        this.isStopped = true;
        this.isPolling = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
    }

    async _pollLoop() {
        if (this.isStopped) return;
        const pollStart = performance.now();
        try {
            const url = `/classroom/${this.lessonId}/signal?since_seq=${this.lastSeq}&since=${this.lastServerTime}&client_id=${this.clientId}`;
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Client-ID': this.clientId,
                }
            });

            this.latencyMs = Math.round(performance.now() - pollStart);
            if (this.onPing) {
                this.onPing(this.latencyMs);
            }

            if (res.ok) {
                const data = await res.json();
                if (data.server_time) {
                    this.lastServerTime = data.server_time;
                }
                if (typeof data.max_seq === 'number' && data.max_seq > this.lastSeq) {
                    this.lastSeq = data.max_seq;
                }
                if (Array.isArray(data.signals) && data.signals.length > 0) {
                    for (const sig of data.signals) {
                        if (sig.client_id && sig.client_id === this.clientId) {
                            continue;
                        }
                        if (sig.id && this.seenSignalIds.has(sig.id)) {
                            continue;
                        }
                        if (sig.id) {
                            this.seenSignalIds.add(sig.id);
                            if (this.seenSignalIds.size > 300) {
                                const first = this.seenSignalIds.values().next().value;
                                this.seenSignalIds.delete(first);
                            }
                        }
                        if (typeof sig.seq === 'number' && sig.seq > this.lastSeq) {
                            this.lastSeq = sig.seq;
                        }
                        this.signalsReceived++;
                        this._dispatch(sig.type, sig.payload, sig);
                    }
                }
            }
        } catch (err) {
            console.warn('[Signaling] Poll error:', err);
        }

        if (!this.isStopped) {
            this.pollTimer = setTimeout(() => this._pollLoop(), this.pollInterval);
        }
    }

    async send(type, payload = null, retryCount = 0) {
        try {
            this.signalsSent++;
            const res = await fetch(`/classroom/${this.lessonId}/signal`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Client-ID': this.clientId,
                },
                body: JSON.stringify({
                    type,
                    payload,
                    client_id: this.clientId,
                    role: this.userRole,
                }),
            });

            if (!res.ok) {
                const isCritical = (type === 'offer' || type === 'answer' || type === 'ping' || type === 'hello');
                if (isCritical && retryCount < 3) {
                    console.warn(`[Signaling] Send ${type} returned HTTP ${res.status}. Retrying in ${250 * (retryCount + 1)}ms...`);
                    await new Promise(r => setTimeout(r, 250 * (retryCount + 1)));
                    return this.send(type, payload, retryCount + 1);
                }
                return null;
            }

            const data = await res.json();
            if (data?.seq && typeof data.seq === 'number' && data.seq > this.lastSeq) {
                this.lastSeq = data.seq;
            }
            return data;
        } catch (err) {
            const isCritical = (type === 'offer' || type === 'answer' || type === 'ping' || type === 'hello');
            if (isCritical && retryCount < 3) {
                console.warn(`[Signaling] Send ${type} network error (${err.message}). Retrying in ${250 * (retryCount + 1)}ms...`);
                await new Promise(r => setTimeout(r, 250 * (retryCount + 1)));
                return this.send(type, payload, retryCount + 1);
            }
            console.error('[Signaling] Send failed:', type, err);
            return null;
        }
    }
}

/**
 * Peer-to-Peer WebRTC Connection Manager with DataChannel
 */
class P2PConnectionManager {
    constructor({ lessonId, csrfToken, userRole, iceServers }) {
        this.lessonId = lessonId;
        this.csrfToken = csrfToken;
        this.userRole = userRole; // 'tutor' | 'student'
        this.isPolite = (userRole === 'student'); // Polite peer in W3C Perfect Negotiation
        const fallbackServers = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' },
            { urls: 'stun:stun3.l.google.com:19302' },
            { urls: 'stun:stun.cloudflare.com:3478' },
            { urls: 'stun:global.stun.twilio.com:3478' },
            {
                urls: [
                    'turn:openrelay.metered.ca:80',
                    'turn:openrelay.metered.ca:443',
                    'turn:openrelay.metered.ca:443?transport=tcp',
                ],
                username: 'openrelayproject',
                credential: 'openrelayproject',
            },
        ];
        this.iceServers = (iceServers && iceServers.length > 0)
            ? [...iceServers, ...fallbackServers]
            : fallbackServers;

        this.signaling = new SignalingClient(lessonId, csrfToken, userRole);
        this.pc = null;
        this.dataChannel = null;
        this.localStream = null;
        this.remoteStream = null;
        this.screenStream = null;
        this.isScreenSharing = false;
        this.pendingCandidates = [];
        this.candidateQueue = [];
        this.candidateFlushTimer = null;
        this.isConnected = false;
        this.connectedAt = 0;
        this.activePeerClientId = null;
        this._unifiedDisconnectTimer = null;
        this.heartbeatTimer = null;
        this.makingOffer = false;
        this.isRestartingIce = false;

        // Live Diagnostics & Metrics
        this.diagnostics = {
            role: userRole,
            clientId: this.signaling.clientId,
            iceConnectionState: 'new',
            connectionState: 'new',
            signalingState: 'stable',
            peerRole: null,
            peerClientId: null,
            peerSeenAt: null,
            connectionType: 'Ожидание подключения...',
            candidates: { host: 0, srflx: 0, relay: 0 },
            remoteCandidates: { host: 0, srflx: 0, relay: 0 },
            latencyMs: 0,
            logs: [],
        };

        this.signaling.onPing = (ms) => {
            this.diagnostics.latencyMs = ms;
            this._notifyDiagnostic();
        };

        // Callbacks
        this.onRemoteTrack = null;
        this.onLocalStream = null;
        this.onData = null;
        this.onConnectionStateChange = null;
        this.onScreenShareChange = null;
        this.onWhiteboardAction = null;
        this.onChatMessage = null;
        this.onDiagnosticUpdate = null;
    }

    log(message, level = 'info') {
        const time = new Date().toLocaleTimeString();
        const entry = { time, message, level };
        this.diagnostics.logs.unshift(entry);
        if (this.diagnostics.logs.length > 30) {
            this.diagnostics.logs.pop();
        }
        console.log(`[WebRTC ${time}]`, message);
        this._notifyDiagnostic();
    }

    _notifyDiagnostic() {
        if (this.onDiagnosticUpdate) {
            try {
                this.onDiagnosticUpdate({
                    ...this.diagnostics,
                    latencyMs: this.signaling.latencyMs,
                    signalsSent: this.signaling.signalsSent,
                    signalsReceived: this.signaling.signalsReceived,
                });
            } catch (e) {}
        }
    }

    async _detectActiveConnectionType() {
        if (!this.pc?.getStats) return;
        try {
            const stats = await this.pc.getStats();
            stats.forEach((report) => {
                if (report.type === 'candidate-pair' && (report.state === 'succeeded' || report.nominated)) {
                    const local = stats.get(report.localCandidateId);
                    const remote = stats.get(report.remoteCandidateId);
                    const isRelay = local?.candidateType === 'relay' || remote?.candidateType === 'relay';
                    this.diagnostics.connectionType = isRelay
                        ? '🛡️ Relay (TURN Обход NAT/4G)'
                        : '⚡ Прямое P2P соединение';
                    this.log(`Активный маршрут: ${this.diagnostics.connectionType}`, 'success');
                    this._notifyDiagnostic();
                }
            });
        } catch (e) {}
    }

    async initMedia() {
        if (this.localStream && this.localStream.getTracks().length > 0) return this.localStream;

        const isMobile = (typeof window !== 'undefined') && (
            window.innerWidth <= 768 ||
            /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
        );

        // Safe mobile video constraints without strict max/min clamps (which cause OverconstrainedError on Android Camera2 HAL in portrait mode)
        const videoConstraintsList = isMobile ? [
            { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 }, frameRate: { ideal: 24, max: 30 } },
            { facingMode: 'user' },
            true,
        ] : [
            { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 25, max: 30 } },
            { width: { ideal: 640 }, height: { ideal: 480 } },
            true,
        ];

        // Safe standard audio constraints (no latency: 0 or rigid sampleRate which crash Android audio HAL)
        const safeAudioConstraints = {
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true,
            channelCount: 1,
        };

        let audioStream = null;
        let videoStream = null;

        // 1. Primary attempt: combined capture with first safe profile
        try {
            const combined = await navigator.mediaDevices.getUserMedia({
                audio: safeAudioConstraints,
                video: videoConstraintsList[0],
            });
            this.localStream = combined;
            this.log('Камера и микрофон успешно получены (совместно)', 'success');
        } catch (combinedErr) {
            this.log(`Совместный захват не удался (${combinedErr.name}), запускаем независимый захват...`, 'warn');

            // 2. Independent Audio Capture
            try {
                audioStream = await navigator.mediaDevices.getUserMedia({ audio: safeAudioConstraints });
            } catch (aErr) {
                try {
                    audioStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                } catch (aErr2) {
                    this.log(`Не удалось получить микрофон: ${aErr2.message}`, 'warn');
                }
            }

            // 3. Independent Video Capture (cycling through mobile constraints)
            for (const vConstraints of videoConstraintsList) {
                try {
                    videoStream = await navigator.mediaDevices.getUserMedia({ video: vConstraints });
                    if (videoStream && videoStream.getVideoTracks().length > 0) {
                        this.log('Камера успешно получена отдельно', 'success');
                        break;
                    }
                } catch (vErr) {
                    this.log(`Попытка видео (${vErr.name}): ${vErr.message}`, 'debug');
                }
            }

            const combinedTracks = [];
            if (audioStream) combinedTracks.push(...audioStream.getAudioTracks());
            if (videoStream) combinedTracks.push(...videoStream.getVideoTracks());

            this.localStream = combinedTracks.length > 0 ? new MediaStream(combinedTracks) : new MediaStream();
        }

        if (this.onLocalStream && this.localStream) {
            this.onLocalStream(this.localStream);
        }

        return this.localStream;
    }

    async start() {
        this.log(`Инициализация P2P клиента (Роль: ${this.userRole})`, 'info');
        this._setupSignaling();
        this.signaling.setFastPolling(true);
        this.signaling.startPolling();
        await this._createPeerConnection();

        // Broadcast hello so any peer in the room knows we are active
        await this.signaling.send('hello', {
            role: this.userRole,
            clientId: this.signaling.clientId,
            hasVideo: !!this.localStream?.getVideoTracks().length,
            hasAudio: !!this.localStream?.getAudioTracks().length,
        });
        this.log('Отправлен сигнал hello в комнату', 'info');

        // Periodic presence heartbeat (purely presence, never destroys connection)
        if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
        this.heartbeatTimer = setInterval(async () => {
            if (!this.signaling.isStopped) {
                // If connection is established, slow down keepalive to 15s to save server resources
                if (this.isConnected) {
                    this._hbCount = (this._hbCount || 0) + 1;
                    if (this._hbCount % 3 !== 0) return;
                }
                await this.signaling.send('hello', {
                    role: this.userRole,
                    clientId: this.signaling.clientId,
                });
            }
        }, 5000);
    }

    _queueIceCandidate(candidate) {
        if (!candidate) return;
        this.candidateQueue.push(candidate);
        if (this.candidateFlushTimer) clearTimeout(this.candidateFlushTimer);
        // Batch flush with 150ms debounce to prevent HTTP candidate storms
        this.candidateFlushTimer = setTimeout(() => this._flushIceCandidates(), 150);
    }

    async _flushIceCandidates() {
        if (this.candidateFlushTimer) {
            clearTimeout(this.candidateFlushTimer);
            this.candidateFlushTimer = null;
        }
        if (this.candidateQueue.length === 0) return;

        const batch = [...this.candidateQueue];
        this.candidateQueue = [];
        await this.signaling.send('candidates', { candidates: batch });
    }

    _setupSignaling() {
        this.signaling.on('hello', async (payload, meta) => {
            const peerClientId = meta?.client_id || payload?.clientId || 'unknown';
            this.diagnostics.peerRole = payload?.role || meta?.sender_role || 'unknown';
            this.diagnostics.peerClientId = peerClientId;
            this.diagnostics.peerSeenAt = Date.now();
            this.log(`Собеседник в сети: ${this.diagnostics.peerRole}`, 'success');

            // Peer session change detection: only restart if ALREADY connected and the new hello is explicitly newer than our connected time
            if (peerClientId !== 'unknown' && this.activePeerClientId && this.activePeerClientId !== peerClientId) {
                if (this.isConnected && this.connectedAt && meta?.created_at_ms && meta.created_at_ms > this.connectedAt) {
                    this.log(`Собеседник сменил сессию (${peerClientId}). Пересоздание P2P соединения...`, 'warn');
                    this.activePeerClientId = peerClientId;
                    await this.restartConnection();
                    return;
                }
            }
            this.activePeerClientId = peerClientId;

            // If we are tutor and not connected yet, initiate an offer safely (throttled)
            if (this.userRole === 'tutor' && !this.isConnected && !this.makingOffer) {
                if (this.pc && this.pc.signalingState === 'stable' && this.pc.connectionState !== 'connecting') {
                    await this._makeOffer();
                }
            } else if (!this.isConnected && this.userRole === 'student') {
                // Student notifies tutor with ping (throttled: at most once every 6 seconds)
                const now = Date.now();
                if (!this._lastPingTime || (now - this._lastPingTime > 6000)) {
                    this._lastPingTime = now;
                    await this.signaling.send('ping', { role: 'student', clientId: this.signaling.clientId });
                }
            }
        });

        this.signaling.on('ping', async (payload, meta) => {
            const senderClient = meta?.client_id || payload?.clientId;
            if (senderClient) {
                this.activePeerClientId = senderClient;
            }
            this.log('Получен ping от собеседника', 'info');
            if (payload?.restart) {
                this.log('Запрошен перезапуск ICE от собеседника', 'warn');
                await this._attemptIceRestart();
                return;
            }
            if (payload?.renegotiate) {
                if (this.userRole === 'tutor' && !this.makingOffer) {
                    this.log('Собеседник добавил/обновил медиа-трек. Запуск ренегоциации...', 'info');
                    await this._makeOffer(true);
                }
                return;
            }
            if (this.userRole === 'tutor' && !this.isConnected && !this.makingOffer) {
                if (this.pc && this.pc.signalingState === 'stable' && this.pc.connectionState !== 'connecting') {
                    await this._makeOffer();
                }
            }
        });

        this.signaling.on('offer', async (payload, meta) => {
            const senderClient = meta?.client_id || payload?.clientId;
            if (senderClient) this.activePeerClientId = senderClient;
            this.log('Получен SDP Offer от собеседника', 'info');
            await this._handleOffer(payload);
        });

        this.signaling.on('answer', async (payload, meta) => {
            const senderClient = meta?.client_id || payload?.clientId;
            if (senderClient) this.activePeerClientId = senderClient;
            this.log('Получен SDP Answer от собеседника', 'success');
            await this._handleAnswer(payload);
        });

        const handleCandidate = async (candidate, meta) => {
            if (!candidate) return;
            const senderClient = meta?.client_id;
            if (senderClient) this.activePeerClientId = senderClient;
            const cStr = candidate.candidate || '';
            const cType = cStr.includes('typ srflx') ? 'srflx' : (cStr.includes('typ relay') ? 'relay' : 'host');
            this.diagnostics.remoteCandidates[cType] = (this.diagnostics.remoteCandidates[cType] || 0) + 1;
            this.log(`Входящий ICE кандидат: ${cType}`, 'debug');

            if (this.pc && this.pc.remoteDescription && this.pc.remoteDescription.type) {
                try {
                    await this.pc.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (e) {
                    console.warn('[WebRTC] addIceCandidate failed:', e);
                }
            } else {
                this.pendingCandidates.push(candidate);
            }
        };

        this.signaling.on('candidate', async (candidate, meta) => {
            await handleCandidate(candidate, meta);
        });

        this.signaling.on('candidates', async (payload, meta) => {
            const list = Array.isArray(payload) ? payload : (payload?.candidates || []);
            for (const cand of list) {
                await handleCandidate(cand, meta);
            }
        });

        this.signaling.on('wb-action', (action) => {
            if (action?.type === 'wb-toggle') {
                if (window.classroomApp && window.classroomApp.isWhiteboardActive !== action.enabled) {
                    window.classroomApp.toggleWhiteboard(false);
                }
                return;
            }
            if (this.onWhiteboardAction) {
                this.onWhiteboardAction(action);
            }
        });

        this.signaling.on('chat-message', (msg) => {
            if (this.onChatMessage) {
                this.onChatMessage(msg);
            }
        });

        this.signaling.on('screenshare-state', (payload) => {
            if (this.onScreenShareChange) {
                this.onScreenShareChange(payload);
            }
        });

        this.signaling.on('wb_broadcast', (payload) => {
            if (this.onData) {
                this.onData(payload);
            }
        });

        this.signaling.on('restart', async () => {
            this.log('Запрос перезапуска сессии', 'warn');
            await this.restartConnection();
        });
    }

    async _createPeerConnection() {
        if (this.pc) {
            try { this.pc.close(); } catch (e) {}
            this.pc = null;
        }

        const config = {
            iceServers: this.iceServers,
            iceCandidatePoolSize: 2,
        };

        this.pc = new RTCPeerConnection(config);

        // Add local tracks if available
        if (this.localStream) {
            this.localStream.getTracks().forEach((track) => {
                this.pc.addTrack(track, this.localStream);
            });
        }

        // Ensure transceivers exist for both audio and video
        const kinds = ['audio', 'video'];
        for (const kind of kinds) {
            const hasTrack = this.localStream && this.localStream.getTracks().some(t => t.kind === kind);
            if (!hasTrack) {
                try {
                    this.pc.addTransceiver(kind, { direction: 'recvonly' });
                } catch (e) {
                    console.warn('[WebRTC] addTransceiver warning:', e);
                }
            }
        }

        // ICE candidate exchange via debounced queue
        this.pc.onicecandidate = (event) => {
            if (event.candidate) {
                const c = event.candidate.toJSON ? event.candidate.toJSON() : event.candidate;
                const cStr = c.candidate || '';
                const cType = cStr.includes('typ srflx') ? 'srflx' : (cStr.includes('typ relay') ? 'relay' : 'host');
                this.diagnostics.candidates[cType] = (this.diagnostics.candidates[cType] || 0) + 1;
                this.log(`Локальный ICE кандидат: ${cType}`, 'debug');
                this._queueIceCandidate(c);
            } else {
                // ICE gathering finished, flush queued candidates immediately
                this._flushIceCandidates();
            }
        };

        // Remote track received
        this.pc.ontrack = (event) => {
            this.log(`Получен удаленный медиа-трек: ${event.track.kind}`, 'success');
            // Force zero jitter buffer playout delay for immediate audio/video response
            if (event.receiver && 'playoutDelayHint' in event.receiver) {
                try {
                    event.receiver.playoutDelayHint = 0;
                } catch (e) {}
            }
            if (!this.remoteStream) {
                this.remoteStream = new MediaStream();
            }
            if (event.streams && event.streams[0]) {
                this.remoteStream = event.streams[0];
            } else if (!this.remoteStream.getTracks().includes(event.track)) {
                this.remoteStream.addTrack(event.track);
            }

            // Listen to unmute/mute for renegotiated or dynamically attached tracks
            event.track.onunmute = () => {
                this.log(`Удаленный медиа-трек ${event.track.kind} активен (unmute)`, 'success');
                if (this.onRemoteTrack) {
                    this.onRemoteTrack(this.remoteStream, event.track);
                }
            };
            event.track.onmute = () => {
                this.log(`Удаленный медиа-трек ${event.track.kind} заглушен (mute)`, 'info');
                if (this.onRemoteTrack) {
                    this.onRemoteTrack(this.remoteStream, event.track);
                }
            };

            if (this.onRemoteTrack) {
                this.onRemoteTrack(this.remoteStream, event.track);
            }
        };

        // Connection state monitoring
        this.pc.onconnectionstatechange = () => {
            const state = this.pc?.connectionState || 'unknown';
            this.diagnostics.connectionState = state;
            this.log(`Состояние WebRTC связи: ${state}`, state === 'connected' ? 'success' : (state === 'failed' ? 'error' : 'info'));
            if (state === 'connected') {
                this._handleConnected();
            } else if (state === 'disconnected') {
                this._handleDisconnect('connection-disconnected');
            } else if (state === 'failed') {
                this.isConnected = false;
                this.signaling.setFastPolling(true);
                if ((this.diagnostics.candidates.relay || 0) === 0) {
                    this.log('⚠️ Сбой связи (failed): Отсутствуют TURN Relay кандидаты. При связи через 4G/LTE требуется активный TURN сервер.', 'warn');
                }
                this._attemptIceRestart();
            }
            if (this.onConnectionStateChange) {
                this.onConnectionStateChange(state);
            }
            this._notifyDiagnostic();
        };

        // ICE Connection state monitoring
        this.pc.oniceconnectionstatechange = () => {
            const state = this.pc?.iceConnectionState || 'unknown';
            this.diagnostics.iceConnectionState = state;
            this.log(`Статус ICE: ${state}`, (state === 'connected' || state === 'completed') ? 'success' : (state === 'failed' ? 'error' : 'info'));
            if (state === 'connected' || state === 'completed') {
                this._handleConnected();
            } else if (state === 'disconnected') {
                this._handleDisconnect('ice-disconnected');
            } else if (state === 'failed') {
                if ((this.diagnostics.candidates.relay || 0) === 0) {
                    this.log('⚠️ Обрыв ICE: Для обхода мобильного NAT (4G/LTE) необходим работающий TURN сервер.', 'warn');
                }
                this.log('Обрыв ICE соединения. Попытка восстановления (ICE Restart)...', 'warn');
                this._attemptIceRestart();
            }
            this._notifyDiagnostic();
        };

        // DataChannel setup
        if (this.userRole === 'tutor') {
            try {
                this.dataChannel = this.pc.createDataChannel('edusfera-data', { ordered: true });
                this._setupDataChannel(this.dataChannel);
            } catch (e) {
                console.warn('[WebRTC] Create data channel error:', e);
            }
        } else {
            this.pc.ondatachannel = (event) => {
                this.dataChannel = event.channel;
                this._setupDataChannel(this.dataChannel);
            };
        }
    }

    _handleConnected() {
        if (this._unifiedDisconnectTimer) {
            clearTimeout(this._unifiedDisconnectTimer);
            this._unifiedDisconnectTimer = null;
        }
        this.isConnected = true;
        this.connectedAt = Date.now();
        this.signaling.setFastPolling(false);
        this._detectActiveConnectionType();
    }

    _handleDisconnect(source) {
        this.log(`Временный разрыв соединения (${source}), ожидание восстановления (grace period)...`, 'warn');
        if (this._unifiedDisconnectTimer) return;
        this._unifiedDisconnectTimer = setTimeout(async () => {
            this._unifiedDisconnectTimer = null;
            const isDead = !this.pc ||
                this.pc.connectionState === 'disconnected' ||
                this.pc.connectionState === 'failed' ||
                this.pc.iceConnectionState === 'disconnected' ||
                this.pc.iceConnectionState === 'failed';
            if (isDead) {
                this.log('Связь не восстановилась за 5с. Перезапуск ICE...', 'warn');
                this.isConnected = false;
                this.signaling.setFastPolling(true);
                await this._attemptIceRestart();
            }
        }, 5000);
    }

    _setupDataChannel(channel) {
        channel.onopen = () => {
            console.log('[WebRTC] DataChannel open and ready');
            try {
                // Request current whiteboard state from peer
                this.sendData({ type: 'wb_excalidraw_request_sync' });

                if (typeof window.getExcalidrawElements === 'function') {
                    const elements = window.getExcalidrawElements();
                    if (elements && elements.length > 0) {
                        this.sendData({
                            type: 'wb_excalidraw_sync',
                            elements: elements,
                            version: Date.now(),
                        });
                    }
                }
            } catch (err) {
                console.warn('[WebRTC] Initial Excalidraw scene sync error:', err);
            }
        };
        channel.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (this.onData) this.onData(data);
            } catch (err) {
                console.warn('[WebRTC] DataChannel message parse error:', err);
            }
        };
    }

    _optimizeOpusSdp(sdp) {
        if (!sdp || typeof sdp !== 'string') return sdp;
        const match = sdp.match(/a=rtpmap:(\d+)\s+opus\/48000\/2/i);
        if (!match) return sdp;
        const pt = match[1];
        const fmtpRegex = new RegExp(`a=fmtp:${pt}\\s+(.+)`, 'i');
        const defaultParams = {
            minptime: '10',
            useinbandfec: '1',
            stereo: '0',
            'sprop-stereo': '0',
            usedtx: '1',
        };
        if (fmtpRegex.test(sdp)) {
            return sdp.replace(fmtpRegex, (m, existing) => {
                const params = {};
                existing.split(';').forEach(pair => {
                    const [k, v] = pair.trim().split('=');
                    if (k && k !== 'cbr' && k !== 'maxaveragebitrate') {
                        params[k] = v ?? '';
                    }
                });
                Object.assign(params, defaultParams);
                const serialized = Object.entries(params)
                    .map(([k, v]) => v !== '' ? `${k}=${v}` : k)
                    .join(';');
                return `a=fmtp:${pt} ${serialized}`;
            });
        } else {
            const serialized = Object.entries(defaultParams)
                .map(([k, v]) => `${k}=${v}`)
                .join(';');
            return sdp.replace(
                new RegExp(`(a=rtpmap:${pt}\\s+opus\\/48000\\/2\\r?\\n)`, 'i'),
                `$1a=fmtp:${pt} ${serialized}\r\n`
            );
        }
    }

    _normalizeSdp(sdp) {
        if (!sdp || typeof sdp !== 'string') return sdp;
        const lines = sdp
            .split(/\r\n|\r|\n/)
            .map(line => line.trim())
            .filter(line => line.length > 0);
        return lines.join('\r\n') + '\r\n';
    }

    sendData(data) {
        if (this.dataChannel && this.dataChannel.readyState === 'open') {
            try {
                if (this.dataChannel.bufferedAmount < 65536) {
                    this.dataChannel.send(JSON.stringify(data));
                    return true;
                }
            } catch (e) {
                console.warn('[WebRTC] sendData error:', e);
            }
        }
        return false;
    }

    async _makeOffer(force = false) {
        if (!this.pc || this.makingOffer) return;
        const now = Date.now();
        if (!force && this._lastOfferTime && (now - this._lastOfferTime < 4500)) {
            console.log('[WebRTC] Throttling offer: previous offer sent < 4.5s ago');
            return;
        }
        if (!force && (this.isConnected || this.pc.connectionState === 'connected')) {
            console.log('[WebRTC] Skipping offer: already connected');
            return;
        }
        if (this.pc.signalingState !== 'stable') {
            console.warn('[WebRTC] Skipping offer: state is', this.pc.signalingState);
            return;
        }

        try {
            this.makingOffer = true;
            this._lastOfferTime = now;
            this.signaling.setFastPolling(true);

            if (!this.dataChannel || this.dataChannel.readyState === 'closed') {
                this.dataChannel = this.pc.createDataChannel('edusfera-data', { ordered: true });
                this._setupDataChannel(this.dataChannel);
            }

            const offer = await this.pc.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true,
            });

            if (this.pc.signalingState !== 'stable') {
                console.warn('[WebRTC] Skipping offer: state changed during createOffer', this.pc.signalingState);
                return;
            }

            const tunedOffer = new RTCSessionDescription({
                type: 'offer',
                sdp: this._normalizeSdp(this._optimizeOpusSdp(offer.sdp)),
            });
            await this.pc.setLocalDescription(tunedOffer);
            await this.signaling.send('offer', { sdp: this._normalizeSdp(this.pc.localDescription.sdp) });
            await this._flushIceCandidates();
            console.log('[WebRTC] Offer sent (Opus low-latency tuned)');
        } catch (err) {
            console.error('[WebRTC] Failed to create offer:', err);
        } finally {
            this.makingOffer = false;
        }
    }

    async _handleOffer(payload) {
        if (!this.pc || !payload?.sdp || this._isSettingOffer) return;
        try {
            this._isSettingOffer = true;
            const offerCollision = (this.makingOffer || this.pc.signalingState !== 'stable');
            if (offerCollision) {
                if (!this.isPolite) {
                    console.warn('[WebRTC] Offer collision as impolite peer, ignoring offer');
                    return;
                }
                // Polite peer rolls back local description to accept the incoming offer
                await Promise.all([
                    this.pc.setLocalDescription({ type: 'rollback' }).catch(() => {}),
                ]);
            }

            const sdp = this._normalizeSdp(payload.sdp);
            this.log('Применение полученного SDP Offer...', 'info');
            await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp }));
            await this._drainPendingCandidates();

            const answer = await this.pc.createAnswer();
            const tunedAnswer = new RTCSessionDescription({
                type: 'answer',
                sdp: this._normalizeSdp(this._optimizeOpusSdp(answer.sdp)),
            });
            await this.pc.setLocalDescription(tunedAnswer);
            this.signaling.setFastPolling(true);
            await this.signaling.send('answer', { sdp: this._normalizeSdp(this.pc.localDescription.sdp) });
            await this._flushIceCandidates();
            this.log('Отправлен SDP Answer собеседнику (Opus low-latency tuned)', 'success');
        } catch (err) {
            console.error('[WebRTC] Error handling offer:', err);
        } finally {
            this._isSettingOffer = false;
        }
    }

    async _handleAnswer(payload) {
        if (!this.pc || !payload?.sdp || this._isSettingAnswer) return;
        if (this.pc.signalingState !== 'have-local-offer') {
            console.log('[WebRTC] Answer received but signalingState is already', this.pc.signalingState);
            return;
        }
        try {
            this._isSettingAnswer = true;
            const sdp = this._normalizeSdp(payload.sdp);
            this.log('Применение полученного SDP Answer...', 'info');
            await this.pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp }));
            await this._drainPendingCandidates();
            this.log('SDP Answer успешно принят, соединение устанавливается', 'success');
        } catch (err) {
            console.error('[WebRTC] Error handling answer:', err);
        } finally {
            this._isSettingAnswer = false;
        }
    }

    async _drainPendingCandidates() {
        if (!this.pc || !this.pc.remoteDescription) return;
        const candidates = [...this.pendingCandidates];
        this.pendingCandidates = [];
        for (const candidate of candidates) {
            try {
                await this.pc.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (e) {
                console.warn('[WebRTC] Drain candidate error:', e);
            }
        }
    }

    async _attemptIceRestart() {
        if (this.isRestartingIce) return;
        const now = Date.now();
        if (this._lastIceRestartTime && (now - this._lastIceRestartTime < 7000)) {
            return;
        }
        this.isRestartingIce = true;
        this._lastIceRestartTime = now;
        this.signaling.setFastPolling(true);
        try {
            if (this.userRole === 'tutor' && this.pc) {
                if (typeof this.pc.restartIce === 'function') {
                    this.pc.restartIce();
                }
                await this._makeOffer(true);
            } else {
                await this.signaling.send('ping', { restart: true });
            }
        } catch (e) {
            console.warn('[WebRTC] ICE restart failed, full reconnect:', e);
            await this.restartConnection();
        } finally {
            setTimeout(() => { this.isRestartingIce = false; }, 4000);
        }
    }

    async toggleScreenShare(enable) {
        if (enable) {
            try {
                this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                    video: { cursor: 'always' },
                    audio: true
                });

                const screenVideoTrack = this.screenStream.getVideoTracks()[0];
                if (!screenVideoTrack) return false;

                const senders = this.pc?.getSenders() || [];
                const videoSender = senders.find(s => s.track && s.track.kind === 'video');

                if (videoSender) {
                    await videoSender.replaceTrack(screenVideoTrack);
                } else if (this.pc) {
                    this.pc.addTrack(screenVideoTrack, this.screenStream);
                }

                this.isScreenSharing = true;

                screenVideoTrack.onended = () => {
                    this.toggleScreenShare(false);
                };

                const statePayload = {
                    active: true,
                    peerName: this.userRole === 'tutor' ? 'Преподаватель' : 'Ученик',
                    role: this.userRole
                };
                this.sendData({ type: 'screenshare-state', ...statePayload });
                this.signaling.send('screenshare-state', statePayload);

                return this.screenStream;
            } catch (err) {
                console.warn('[WebRTC] Screen share failed:', err);
                return false;
            }
        } else {
            if (this.screenStream) {
                this.screenStream.getTracks().forEach(t => t.stop());
                this.screenStream = null;
            }

            const camVideoTrack = this.localStream?.getVideoTracks()[0] || null;
            const senders = this.pc?.getSenders() || [];
            const videoSender = senders.find(s => s.track && (s.track.kind === 'video' || !s.track));

            if (videoSender && camVideoTrack) {
                await videoSender.replaceTrack(camVideoTrack);
            }

            this.isScreenSharing = false;

            const statePayload = { active: false, role: this.userRole };
            this.sendData({ type: 'screenshare-state', ...statePayload });
            this.signaling.send('screenshare-state', statePayload);

            return true;
        }
    }

    async setCameraEnabled(enabled) {
        if (enabled) {
            let vTrack = this.localStream?.getVideoTracks().find(t => t.readyState === 'live');
            if (!vTrack) {
                this.log('Запрос видеокамеры у устройства...', 'info');
                try {
                    const isMobile = (typeof window !== 'undefined') && (
                        window.innerWidth <= 768 ||
                        /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
                    );
                    const videoConstraintsList = isMobile ? [
                        { facingMode: this._facingMode || 'user', width: { ideal: 640 }, height: { ideal: 480 }, frameRate: { ideal: 24, max: 30 } },
                        { facingMode: this._facingMode || 'user' },
                        true,
                    ] : [
                        { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 25, max: 30 } },
                        { width: { ideal: 640 }, height: { ideal: 480 } },
                        true,
                    ];

                    let vStream = null;
                    for (const vConstraints of videoConstraintsList) {
                        try {
                            vStream = await navigator.mediaDevices.getUserMedia({ video: vConstraints, audio: false });
                            if (vStream && vStream.getVideoTracks().length > 0) break;
                        } catch (err) {
                            console.warn('[WebRTC] dynamic video capture attempt error:', err);
                        }
                    }

                    if (!vStream || vStream.getVideoTracks().length === 0) {
                        throw new Error('Камера не найдена или доступ не предоставлен');
                    }

                    vTrack = vStream.getVideoTracks()[0];
                    if (this.localStream) {
                        this.localStream.addTrack(vTrack);
                    } else {
                        this.localStream = vStream;
                    }
                } catch (e) {
                    this.log(`Не удалось включить камеру: ${e.message}`, 'error');
                    return false;
                }
            } else {
                vTrack.enabled = true;
            }

            // Sync track with active RTCPeerConnection
            const pc = this.pc || this.peerConnection;
            if (pc && vTrack) {
                // Find existing video transceiver or sender
                const transceivers = pc.getTransceivers ? pc.getTransceivers() : [];
                const vTransceiver = transceivers.find(t =>
                    (t.receiver?.track?.kind === 'video') ||
                    (t.sender?.track?.kind === 'video')
                );

                if (vTransceiver) {
                    vTransceiver.direction = 'sendrecv';
                    if (vTransceiver.sender) {
                        try {
                            await vTransceiver.sender.replaceTrack(vTrack);
                        } catch (rtErr) {
                            console.warn('[WebRTC] replaceTrack on vTransceiver error:', rtErr);
                        }
                    }
                } else {
                    const senders = pc.getSenders ? pc.getSenders() : [];
                    const videoSender = senders.find(s => s.track && s.track.kind === 'video');
                    if (videoSender) {
                        await videoSender.replaceTrack(vTrack);
                    } else if (pc.addTrack) {
                        pc.addTrack(vTrack, this.localStream);
                    }
                }

                // Renegotiate with peer
                if (this.userRole === 'tutor') {
                    await this._makeOffer(true);
                } else {
                    await this.signaling.send('ping', {
                        role: 'student',
                        clientId: this.signaling.clientId,
                        renegotiate: true,
                    });
                }
            }

            if (this.onLocalStream && this.localStream) {
                this.onLocalStream(this.localStream);
            }
            this.log('Камера включена', 'success');
            return true;
        } else {
            if (this.localStream) {
                this.localStream.getVideoTracks().forEach(t => { t.enabled = false; });
            }
            this.log('Камера выключена', 'info');
            return true;
        }
    }

    async flipCamera() {
        if (!this.localStream) return null;
        this._facingMode = this._facingMode === 'environment' ? 'user' : 'environment';

        try {
            let newStream = null;
            try {
                newStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: this._facingMode }, width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false,
                });
            } catch (e) {
                newStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: this._facingMode },
                    audio: false,
                });
            }

            const newTrack = newStream?.getVideoTracks()[0];
            if (newTrack) {
                const pc = this.pc || this.peerConnection;
                if (pc && pc.getSenders) {
                    const senders = pc.getSenders();
                    const videoSender = senders.find(s => s.track && s.track.kind === 'video');
                    if (videoSender) {
                        await videoSender.replaceTrack(newTrack);
                    } else if (pc.addTrack) {
                        pc.addTrack(newTrack, this.localStream);
                    }
                }

                const currentVideoTrack = this.localStream.getVideoTracks()[0];
                if (currentVideoTrack) {
                    currentVideoTrack.stop();
                    this.localStream.removeTrack(currentVideoTrack);
                }
                this.localStream.addTrack(newTrack);

                if (this.onLocalStream) this.onLocalStream(this.localStream);
                this.log(`Камера переключена: ${this._facingMode === 'environment' ? 'Основная' : 'Фронтальная'}`, 'info');
                return this._facingMode;
            }
        } catch (err) {
            this.log(`Ошибка поворота камеры: ${err.message}`, 'warn');
            return null;
        }
    }

    setMicEnabled(enabled) {
        if (this.localStream) {
            this.localStream.getAudioTracks().forEach(t => { t.enabled = enabled; });
        }
    }

    async restartConnection() {
        this.pendingCandidates = [];
        this.candidateQueue = [];
        await this._createPeerConnection();
        if (this.userRole === 'tutor') {
            await this._makeOffer();
        } else {
            await this.signaling.send('hello', { role: this.userRole });
        }
    }

    close() {
        if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
        if (this.candidateFlushTimer) clearTimeout(this.candidateFlushTimer);
        this.signaling.stop();
        if (this.screenStream) {
            this.screenStream.getTracks().forEach(t => t.stop());
        }
        if (this.localStream) {
            this.localStream.getTracks().forEach(t => t.stop());
        }
        if (this.pc) {
            try { this.pc.close(); } catch (e) {}
            this.pc = null;
        }
    }
}

function playClassroomChime() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, now); // D5
        osc.frequency.setValueAtTime(880.00, now + 0.12); // A5
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.45);
    } catch (e) {}
}

/**
 * Excalidraw-Grade Vector Canvas Interactive Whiteboard
 * Multi-page slides, infinite pan/zoom, peer cursors, teacher lock, hand raise, stamps
 */
class WhiteboardEngine {
    constructor(canvas, p2pManager, lessonId, csrfToken, userRole = 'student', userName = 'Пользователь') {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.p2p = p2pManager;
        this.lessonId = lessonId;
        this.csrfToken = csrfToken;
        this.userRole = userRole;
        this.userName = userName;

        // Tool state
        this.tool = 'pen'; // 'select' | 'pan' | 'pen' | 'highlighter' | 'eraser' | 'line' | 'arrow' | 'rect' | 'circle' | 'text' | 'stamp'
        this.color = '#1e1e1e';
        this.fillColor = 'transparent';
        this.lineWidth = 3;
        this.strokeStyle = 'solid'; // 'solid' | 'dashed'
        this.stampType = 'task'; // 'task' | 'solution' | 'correct' | 'incorrect'

        this.isDrawing = false;
        this.isPanning = false;
        this.isSpacePressed = false;
        this.startPoint = null;
        this.currentStroke = null;
        this.isLocked = false;

        // Infinite Viewport: Pan & Zoom
        this.zoom = 1.0;
        this.panX = 0;
        this.panY = 0;
        this.panStart = { x: 0, y: 0 };

        // Multi-page Slides
        this.pages = [
            { id: 1, title: 'Слайд 1', items: [], undoStack: [] }
        ];
        this.currentPageIndex = 0;

        // Peer Cursors
        this.peerCursors = new Map(); // id -> { x, y, name, role, timestamp }
        this._lastBroadcastCursor = 0;

        // Image Cache
        this._imageCache = new Map();

        // Callbacks for UI
        this.onPageChanged = null;
        this.onLockChanged = null;
        this.onHandRaised = null;
        this.onToast = null;
        this.onZoomChanged = null;

        // Bound listeners
        this._boundPointerDown = this._onPointerDown.bind(this);
        this._boundPointerMove = this._onPointerMove.bind(this);
        this._boundPointerUp = this._onPointerUp.bind(this);
        this._boundWheel = this._onWheel.bind(this);
        this._boundKeyDown = this._onKeyDown.bind(this);
        this._boundKeyUp = this._onKeyUp.bind(this);
    }

    get currentPage() {
        if (!this.pages[this.currentPageIndex]) {
            this.pages[0] = { id: 1, title: 'Слайд 1', items: [], undoStack: [] };
            this.currentPageIndex = 0;
        }
        return this.pages[this.currentPageIndex];
    }

    get items() {
        return this.currentPage.items;
    }

    set items(newItems) {
        this.currentPage.items = newItems;
    }

    get undoStack() {
        return this.currentPage.undoStack;
    }

    init() {
        this.canvas.style.touchAction = 'none';

        this.canvas.addEventListener('pointerdown', this._boundPointerDown);
        window.addEventListener('pointermove', this._boundPointerMove);
        window.addEventListener('pointerup', this._boundPointerUp);
        window.addEventListener('pointercancel', this._boundPointerUp);
        this.canvas.addEventListener('wheel', this._boundWheel, { passive: false });
        window.addEventListener('keydown', this._boundKeyDown);
        window.addEventListener('keyup', this._boundKeyUp);

        if (this.p2p) {
            this.p2p.onWhiteboardAction = (action) => {
                this.applyRemoteAction(action);
            };
        }

        this.resize();
        this.loadRemoteState();
    }

    destroy() {
        this.canvas.removeEventListener('pointerdown', this._boundPointerDown);
        window.removeEventListener('pointermove', this._boundPointerMove);
        window.removeEventListener('pointerup', this._boundPointerUp);
        window.removeEventListener('pointercancel', this._boundPointerUp);
        this.canvas.removeEventListener('wheel', this._boundWheel);
        window.removeEventListener('keydown', this._boundKeyDown);
        window.removeEventListener('keyup', this._boundKeyUp);
    }

    resize() {
        const rect = this.canvas.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;

        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = Math.round(rect.width * dpr);
        this.canvas.height = Math.round(rect.height * dpr);

        this.ctx.resetTransform?.();
        this.ctx.scale(dpr, dpr);
        this.redraw();
    }

    // Viewport zoom & pan methods
    setZoom(level) {
        const clamped = Math.min(3.0, Math.max(0.2, level));
        this.zoom = clamped;
        if (this.onZoomChanged) this.onZoomChanged(Math.round(this.zoom * 100));
        this.redraw();
    }

    zoomIn() {
        this.setZoom(this.zoom + 0.15);
    }

    zoomOut() {
        this.setZoom(this.zoom - 0.15);
    }

    resetView() {
        this.zoom = 1.0;
        this.panX = 0;
        this.panY = 0;
        if (this.onZoomChanged) this.onZoomChanged(100);
        this.redraw();
    }

    _onWheel(e) {
        e.preventDefault();
        if (e.ctrlKey || e.metaKey) {
            // Zoom centered at cursor
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const zoomDelta = -e.deltaY * 0.002;
            const oldZoom = this.zoom;
            const newZoom = Math.min(3.0, Math.max(0.2, oldZoom + zoomDelta));

            this.panX = mouseX - (mouseX - this.panX) * (newZoom / oldZoom);
            this.panY = mouseY - (mouseY - this.panY) * (newZoom / oldZoom);
            this.zoom = newZoom;
            if (this.onZoomChanged) this.onZoomChanged(Math.round(this.zoom * 100));
        } else {
            // Pan
            this.panX -= e.deltaX;
            this.panY -= e.deltaY;
        }
        this.redraw();
    }

    _onKeyDown(e) {
        if (e.code === 'Space' && !this.isSpacePressed && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
            this.isSpacePressed = true;
            this.canvas.style.cursor = 'grab';
        }
    }

    _onKeyUp(e) {
        if (e.code === 'Space') {
            this.isSpacePressed = false;
            this.canvas.style.cursor = this.tool === 'pan' ? 'grab' : 'crosshair';
        }
    }

    _getCanvasCoords(e) {
        const rect = this.canvas.getBoundingClientRect();
        const sx = e.clientX - rect.left;
        const sy = e.clientY - rect.top;
        return {
            x: (sx - this.panX) / this.zoom,
            y: (sy - this.panY) / this.zoom,
            sx,
            sy,
        };
    }

    _onPointerDown(e) {
        if (this.isSpacePressed || this.tool === 'pan' || e.button === 1) {
            this.isPanning = true;
            this.panStart = { x: e.clientX - this.panX, y: e.clientY - this.panY };
            this.canvas.style.cursor = 'grabbing';
            return;
        }

        if (this.isLocked && this.userRole !== 'tutor') {
            if (this.onToast) this.onToast('🔒 Доска заблокирована преподавателем');
            return;
        }

        e.preventDefault();
        const pt = this._getCanvasCoords(e);
        this.isDrawing = true;
        this.startPoint = pt;

        if (this.tool === 'text') {
            const text = prompt('Введите текст для доски:');
            if (text && text.trim()) {
                const item = {
                    type: 'text',
                    text: text.trim(),
                    x: pt.x,
                    y: pt.y,
                    color: this.color,
                    size: Math.max(16, this.lineWidth * 6),
                };
                this.addItem(item, true);
            }
            this.isDrawing = false;
            return;
        }

        if (this.tool === 'stamp') {
            const stampLabels = {
                task: '📝 Задание: ',
                solution: '💡 Решение: ',
                correct: '🟢 Верно!',
                incorrect: '🔴 Ошибка',
            };
            const label = stampLabels[this.stampType] || '📝 Заметка';
            let extra = '';
            if (this.stampType === 'task' || this.stampType === 'solution') {
                extra = prompt('Текст задания / решения:', '') || '';
            }
            const item = {
                type: 'stamp',
                stampType: this.stampType,
                text: label + extra,
                x: pt.x,
                y: pt.y,
                color: this.stampType === 'correct' ? '#2f9e44' : (this.stampType === 'incorrect' ? '#e03131' : '#1971c2'),
                size: 16,
            };
            this.addItem(item, true);
            this.isDrawing = false;
            return;
        }

        this.currentStroke = {
            type: this.tool,
            color: this.color,
            fillColor: this.fillColor,
            width: this.lineWidth,
            strokeStyle: this.strokeStyle,
            points: [[pt.x, pt.y]],
            start: [pt.x, pt.y],
            end: [pt.x, pt.y],
        };
    }

    _onPointerMove(e) {
        const pt = this._getCanvasCoords(e);

        // Broadcast peer cursor (throttled 50ms)
        const now = Date.now();
        if (now - this._lastBroadcastCursor > 50) {
            this._lastBroadcastCursor = now;
            this._broadcastCursor(pt.x, pt.y);
        }

        if (this.isPanning) {
            this.panX = e.clientX - this.panStart.x;
            this.panY = e.clientY - this.panStart.y;
            this.redraw();
            return;
        }

        if (!this.isDrawing || !this.currentStroke) return;

        if (this.tool === 'pen' || this.tool === 'highlighter' || this.tool === 'eraser') {
            this.currentStroke.points.push([pt.x, pt.y]);
            this.redraw();
            this._renderItem(this.currentStroke);
        } else {
            this.currentStroke.end = [pt.x, pt.y];
            this.redraw();
            this._renderItem(this.currentStroke);
        }
    }

    _onPointerUp() {
        if (this.isPanning) {
            this.isPanning = false;
            this.canvas.style.cursor = this.isSpacePressed || this.tool === 'pan' ? 'grab' : 'crosshair';
            return;
        }

        if (!this.isDrawing || !this.currentStroke) {
            this.isDrawing = false;
            return;
        }
        this.isDrawing = false;

        if (this.tool === 'pen' || this.tool === 'highlighter' || this.tool === 'eraser') {
            if (this.currentStroke.points.length > 1) {
                this.addItem(this.currentStroke, true);
            }
        } else {
            this.addItem(this.currentStroke, true);
        }
        this.currentStroke = null;
    }

    _broadcastCursor(x, y) {
        if (!this.p2p) return;
        const payload = {
            type: 'cursor-pos',
            x,
            y,
            name: this.userName,
            role: this.userRole,
            clientId: this.p2p.signaling?.clientId || 'self',
        };
        this.p2p.sendData(payload);
    }

    setRemoteCursor(data) {
        if (!data || !data.clientId || data.clientId === this.p2p?.signaling?.clientId) return;
        this.peerCursors.set(data.clientId, {
            x: data.x,
            y: data.y,
            name: data.name || 'Собеседник',
            role: data.role || 'student',
            timestamp: Date.now(),
        });
        this.redraw();
    }

    // Slide / Multi-page management
    addPage(title = null, broadcast = true) {
        const id = Date.now();
        const pageTitle = title || `Слайд ${this.pages.length + 1}`;
        const newPage = { id, title: pageTitle, items: [], undoStack: [] };
        this.pages.push(newPage);
        this.currentPageIndex = this.pages.length - 1;
        this.redraw();

        if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
        if (broadcast) {
            this._syncAction({ type: 'wb-page-add', page: newPage });
            this._saveStateToBackendDebounced();
        }
    }

    switchPage(index, broadcast = true) {
        if (index < 0 || index >= this.pages.length) return;
        this.currentPageIndex = index;
        this.redraw();

        if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
        if (broadcast) {
            this._syncAction({ type: 'wb-page-switch', index });
            this._saveStateToBackendDebounced();
        }
    }

    deletePage(index, broadcast = true) {
        if (this.pages.length <= 1) return;
        this.pages.splice(index, 1);
        this.currentPageIndex = Math.max(0, Math.min(this.currentPageIndex, this.pages.length - 1));
        this.redraw();

        if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
        if (broadcast) {
            this._syncAction({ type: 'wb-page-delete', index });
            this._saveStateToBackendDebounced();
        }
    }

    // Teacher Board Lock
    setLocked(locked, broadcast = true) {
        this.isLocked = locked;
        if (this.onLockChanged) this.onLockChanged(this.isLocked);
        if (broadcast) {
            this._syncAction({ type: 'wb-lock', locked: this.isLocked });
            this._saveStateToBackendDebounced();
        }
    }

    // Student Hand Raise
    raiseHand() {
        playClassroomChime();
        this._syncAction({ type: 'hand-raise', studentName: this.userName, timestamp: Date.now() });
        if (this.onToast) this.onToast('✋ Вы подняли руку. Преподаватель видит сигнал.');
    }

    // Canvas Item management
    addItem(item, broadcast = true) {
        this.items.push(item);
        this.undoStack.length = 0;
        this.redraw();

        if (broadcast) {
            this._syncAction({ type: 'wb-add', item, pageIndex: this.currentPageIndex });
            this._saveStateToBackendDebounced();
        }
    }

    undo(broadcast = true) {
        if (this.items.length === 0) return;
        const popped = this.items.pop();
        this.undoStack.push(popped);
        this.redraw();

        if (broadcast) {
            this._syncAction({ type: 'wb-undo', pageIndex: this.currentPageIndex });
            this._saveStateToBackendDebounced();
        }
    }

    redo(broadcast = true) {
        if (!this.undoStack || this.undoStack.length === 0) return;
        const item = this.undoStack.pop();
        this.items.push(item);
        this.redraw();

        if (broadcast) {
            this._syncAction({ type: 'wb-add', item, pageIndex: this.currentPageIndex });
            this._saveStateToBackendDebounced();
        }
    }

    clear(broadcast = true) {
        this.undoStack.push(...this.items);
        this.items = [];
        this.redraw();

        if (broadcast) {
            this._syncAction({ type: 'wb-clear', pageIndex: this.currentPageIndex });
            this._saveStateToBackendDebounced();
        }
    }

    // Background & Rendering
    _drawDotGrid(w, h) {
        const dotSpacing = 24 * this.zoom;
        if (dotSpacing < 10) return;

        const startX = ((this.panX % dotSpacing) + dotSpacing) % dotSpacing;
        const startY = ((this.panY % dotSpacing) + dotSpacing) % dotSpacing;

        const isLight = document.body.classList.contains('theme-light') || localStorage.getItem('cr-theme') === 'light';
        this.ctx.fillStyle = isLight ? 'rgba(0, 0, 0, 0.12)' : 'rgba(255, 255, 255, 0.12)';

        const radius = Math.min(1.4, Math.max(0.8, 1.0 * this.zoom));
        for (let x = startX; x < w; x += dotSpacing) {
            for (let y = startY; y < h; y += dotSpacing) {
                this.ctx.beginPath();
                this.ctx.arc(x, y, radius, 0, Math.PI * 2);
                this.ctx.fill();
            }
        }
    }

    redraw() {
        const rect = this.canvas.getBoundingClientRect();
        const w = rect.width;
        const h = rect.height;

        this.ctx.save();
        this.ctx.setTransform(1, 0, 0, 1, 0, 0);
        const dpr = window.devicePixelRatio || 1;
        this.ctx.scale(dpr, dpr);

        // Background
        const isLight = document.body.classList.contains('theme-light') || localStorage.getItem('cr-theme') === 'light';
        this.ctx.fillStyle = isLight ? '#f8fafc' : '#121216';
        this.ctx.fillRect(0, 0, w, h);

        // Dot matrix grid
        this._drawDotGrid(w, h);

        // Apply viewport transform (Pan & Zoom)
        this.ctx.translate(this.panX, this.panY);
        this.ctx.scale(this.zoom, this.zoom);

        // Render current page items
        for (const item of this.items) {
            this._renderItem(item);
        }

        // Render remote peer cursors
        this._renderPeerCursors();

        this.ctx.restore();
    }

    _renderItem(item) {
        this.ctx.save();

        if (item.strokeStyle === 'dashed') {
            this.ctx.setLineDash([8, 6]);
        }

        if (item.type === 'eraser') {
            const isLight = document.body.classList.contains('theme-light') || localStorage.getItem('cr-theme') === 'light';
            this.ctx.strokeStyle = isLight ? '#f8fafc' : '#121216';
            this.ctx.lineWidth = item.width * 5;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this._renderPoints(item.points);
        } else if (item.type === 'highlighter') {
            this.ctx.strokeStyle = item.color;
            this.ctx.globalAlpha = 0.35;
            this.ctx.lineWidth = item.width * 4;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this._renderPoints(item.points);
        } else if (item.type === 'pen') {
            this.ctx.strokeStyle = item.color;
            this.ctx.lineWidth = item.width;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this._renderPoints(item.points);
        } else if (item.type === 'line') {
            this.ctx.strokeStyle = item.color;
            this.ctx.lineWidth = item.width;
            this.ctx.lineCap = 'round';
            this.ctx.beginPath();
            this.ctx.moveTo(item.start[0], item.start[1]);
            this.ctx.lineTo(item.end[0], item.end[1]);
            this.ctx.stroke();
        } else if (item.type === 'arrow') {
            this.ctx.strokeStyle = item.color;
            this.ctx.fillStyle = item.color;
            this.ctx.lineWidth = item.width;
            this.ctx.lineCap = 'round';
            const [x1, y1] = item.start;
            const [x2, y2] = item.end;

            this.ctx.beginPath();
            this.ctx.moveTo(x1, y1);
            this.ctx.lineTo(x2, y2);
            this.ctx.stroke();

            const angle = Math.atan2(y2 - y1, x2 - x1);
            const headLen = Math.max(14, item.width * 3.5);
            this.ctx.beginPath();
            this.ctx.moveTo(x2, y2);
            this.ctx.lineTo(x2 - headLen * Math.cos(angle - Math.PI / 6), y2 - headLen * Math.sin(angle - Math.PI / 6));
            this.ctx.lineTo(x2 - headLen * Math.cos(angle + Math.PI / 6), y2 - headLen * Math.sin(angle + Math.PI / 6));
            this.ctx.closePath();
            this.ctx.fill();
        } else if (item.type === 'rect') {
            this.ctx.strokeStyle = item.color;
            this.ctx.lineWidth = item.width;
            const x = Math.min(item.start[0], item.end[0]);
            const y = Math.min(item.start[1], item.end[1]);
            const rw = Math.abs(item.end[0] - item.start[0]);
            const rh = Math.abs(item.end[1] - item.start[1]);

            if (item.fillColor && item.fillColor !== 'transparent') {
                this.ctx.fillStyle = item.fillColor;
                this.ctx.fillRect(x, y, rw, rh);
            }
            this.ctx.strokeRect(x, y, rw, rh);
        } else if (item.type === 'circle') {
            this.ctx.strokeStyle = item.color;
            this.ctx.lineWidth = item.width;
            const cx = (item.start[0] + item.end[0]) / 2;
            const cy = (item.start[1] + item.end[1]) / 2;
            const rx = Math.abs(item.end[0] - item.start[0]) / 2;
            const ry = Math.abs(item.end[1] - item.start[1]) / 2;

            this.ctx.beginPath();
            this.ctx.ellipse(cx, cy, Math.max(1, rx), Math.max(1, ry), 0, 0, Math.PI * 2);
            if (item.fillColor && item.fillColor !== 'transparent') {
                this.ctx.fillStyle = item.fillColor;
                this.ctx.fill();
            }
            this.ctx.stroke();
        } else if (item.type === 'text') {
            this.ctx.fillStyle = item.color;
            this.ctx.font = `600 ${item.size || 20}px Inter, -apple-system, sans-serif`;
            this.ctx.fillText(item.text, item.x, item.y);
        } else if (item.type === 'stamp') {
            this._renderStampCard(item);
        } else if (item.type === 'image') {
            this._renderImage(item);
        }

        this.ctx.restore();
    }

    _renderPoints(pts) {
        if (!pts || pts.length < 2) return;
        this.ctx.beginPath();
        this.ctx.moveTo(pts[0][0], pts[0][1]);
        for (let i = 1; i < pts.length; i++) {
            this.ctx.lineTo(pts[i][0], pts[i][1]);
        }
        this.ctx.stroke();
    }

    _renderStampCard(item) {
        this.ctx.save();
        this.ctx.font = '600 14px Inter, sans-serif';
        const textMetrics = this.ctx.measureText(item.text);
        const padX = 14;
        const cardW = textMetrics.width + padX * 2;
        const cardH = 34;

        this.ctx.fillStyle = item.color === '#2f9e44' ? 'rgba(34, 197, 94, 0.15)' : (item.color === '#e03131' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(37, 99, 235, 0.15)');
        this.ctx.strokeStyle = item.color;
        this.ctx.lineWidth = 1.5;

        const r = 8;
        this.ctx.beginPath();
        this.ctx.roundRect ? this.ctx.roundRect(item.x, item.y, cardW, cardH, r) : this.ctx.rect(item.x, item.y, cardW, cardH);
        this.ctx.fill();
        this.ctx.stroke();

        this.ctx.fillStyle = item.color;
        this.ctx.fillText(item.text, item.x + padX, item.y + 22);
        this.ctx.restore();
    }

    _renderImage(item) {
        if (!item.src) return;
        let img = this._imageCache.get(item.src);
        if (!img) {
            img = new Image();
            img.src = item.src;
            img.onload = () => this.redraw();
            this._imageCache.set(item.src, img);
        }
        if (img.complete && img.naturalWidth > 0) {
            this.ctx.drawImage(img, item.x, item.y, item.width, item.height);
        }
    }

    _renderPeerCursors() {
        const now = Date.now();
        for (const [id, cursor] of this.peerCursors.entries()) {
            if (now - cursor.timestamp > 4000) {
                this.peerCursors.delete(id);
                continue;
            }

            const x = cursor.x;
            const y = cursor.y;
            const isTutor = cursor.role === 'tutor';
            const color = isTutor ? '#7D39EB' : '#10b981';

            this.ctx.save();
            this.ctx.fillStyle = color;
            this.ctx.strokeStyle = '#ffffff';
            this.ctx.lineWidth = 1.5;

            this.ctx.beginPath();
            this.ctx.moveTo(x, y);
            this.ctx.lineTo(x + 12, y + 12);
            this.ctx.lineTo(x + 5, y + 13);
            this.ctx.lineTo(x + 2, y + 19);
            this.ctx.lineTo(x - 2, y + 17);
            this.ctx.lineTo(x + 2, y + 11);
            this.ctx.closePath();
            this.ctx.fill();
            this.ctx.stroke();

            const label = (isTutor ? '👨‍🏫 ' : '👨‍🎓 ') + cursor.name;
            this.ctx.font = '600 11px Inter, sans-serif';
            const tm = this.ctx.measureText(label);
            const badgeW = tm.width + 12;
            const badgeH = 20;
            const badgeX = x + 12;
            const badgeY = y + 14;

            this.ctx.fillStyle = color;
            this.ctx.beginPath();
            this.ctx.roundRect ? this.ctx.roundRect(badgeX, badgeY, badgeW, badgeH, 6) : this.ctx.rect(badgeX, badgeY, badgeW, badgeH);
            this.ctx.fill();

            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillText(label, badgeX + 6, badgeY + 14);

            this.ctx.restore();
        }
    }

    _syncAction(action) {
        if (this.p2p) {
            const sent = this.p2p.sendData(action);
            if (!sent && this.p2p.signaling) {
                this.p2p.signaling.send('wb-action', action);
            }
        }
    }

    applyRemoteAction(action) {
        if (!action) return;

        if (action.type === 'wb-add' && action.item) {
            const targetPage = typeof action.pageIndex === 'number' && this.pages[action.pageIndex]
                ? this.pages[action.pageIndex]
                : this.currentPage;
            targetPage.items.push(action.item);
            this.redraw();
        } else if (action.type === 'wb-undo') {
            const targetPage = typeof action.pageIndex === 'number' && this.pages[action.pageIndex]
                ? this.pages[action.pageIndex]
                : this.currentPage;
            if (targetPage.items.length > 0) {
                targetPage.items.pop();
                this.redraw();
            }
        } else if (action.type === 'wb-clear') {
            const targetPage = typeof action.pageIndex === 'number' && this.pages[action.pageIndex]
                ? this.pages[action.pageIndex]
                : this.currentPage;
            targetPage.items = [];
            this.redraw();
        } else if (action.type === 'wb-page-add' && action.page) {
            if (!this.pages.some(p => p.id === action.page.id)) {
                this.pages.push(action.page);
                this.currentPageIndex = this.pages.length - 1;
                this.redraw();
                if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
            }
        } else if (action.type === 'wb-page-switch' && typeof action.index === 'number') {
            if (this.pages[action.index]) {
                this.currentPageIndex = action.index;
                this.redraw();
                if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
            }
        } else if (action.type === 'wb-page-delete' && typeof action.index === 'number') {
            if (this.pages.length > 1) {
                this.pages.splice(action.index, 1);
                this.currentPageIndex = Math.max(0, Math.min(this.currentPageIndex, this.pages.length - 1));
                this.redraw();
                if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
            }
        } else if (action.type === 'wb-lock') {
            this.isLocked = !!action.locked;
            if (this.onLockChanged) this.onLockChanged(this.isLocked);
        } else if (action.type === 'hand-raise') {
            playClassroomChime();
            if (this.onHandRaised) this.onHandRaised(action.studentName || 'Ученик');
        } else if (action.type === 'cursor-pos') {
            this.setRemoteCursor(action);
        }
    }

    // Export & Persistence
    exportPng() {
        const dpr = 2; // High-res export
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = 1920 * dpr;
        exportCanvas.height = 1080 * dpr;
        const ectx = exportCanvas.getContext('2d');
        ectx.scale(dpr, dpr);

        const isLight = document.body.classList.contains('theme-light') || localStorage.getItem('cr-theme') === 'light';
        ectx.fillStyle = isLight ? '#ffffff' : '#121216';
        ectx.fillRect(0, 0, 1920, 1080);

        const savedCtx = this.ctx;
        this.ctx = ectx;
        for (const item of this.items) {
            this._renderItem(item);
        }
        this.ctx = savedCtx;

        const link = document.createElement('a');
        link.download = `edusfera-board-slide-${this.currentPageIndex + 1}-lesson-${this.lessonId}.png`;
        link.href = exportCanvas.toDataURL('image/png');
        link.click();
    }

    exportJson() {
        const data = {
            version: 2,
            lessonId: this.lessonId,
            exportedAt: new Date().toISOString(),
            pages: this.pages,
            currentPageIndex: this.currentPageIndex,
        };
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.download = `edusfera-whiteboard-lesson-${this.lessonId}.json`;
        link.href = url;
        link.click();
        URL.revokeObjectURL(url);
    }

    _saveStateToBackendDebounced() {
        if (this._saveTimer) clearTimeout(this._saveTimer);
        this._saveTimer = setTimeout(async () => {
            try {
                await fetch(`/classroom/${this.lessonId}/whiteboard`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        state: {
                            pages: this.pages,
                            currentPageIndex: this.currentPageIndex,
                            isLocked: this.isLocked,
                        }
                    }),
                });
            } catch (err) {
                console.warn('[Whiteboard] Backend sync error:', err);
            }
        }, 1200);
    }

    async loadRemoteState() {
        try {
            const res = await fetch(`/classroom/${this.lessonId}/whiteboard`, {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const json = await res.json();
                const state = json.state;
                if (state) {
                    if (Array.isArray(state.pages) && state.pages.length > 0) {
                        this.pages = state.pages;
                        this.currentPageIndex = Math.min(state.currentPageIndex || 0, this.pages.length - 1);
                    } else if (Array.isArray(state.items) && state.items.length > 0) {
                        this.pages = [{ id: 1, title: 'Слайд 1', items: state.items, undoStack: [] }];
                        this.currentPageIndex = 0;
                    }
                    if (typeof state.isLocked === 'boolean') {
                        this.isLocked = state.isLocked;
                        if (this.onLockChanged) this.onLockChanged(this.isLocked);
                    }
                    this.redraw();
                    if (this.onPageChanged) this.onPageChanged(this.currentPageIndex, this.pages);
                }
            }
        } catch (err) {
            console.warn('[Whiteboard] Load state error:', err);
        }
    }
}

/**
 * Accurate Session Timer
 */
class SessionTimer {
    constructor(onTick) {
        this.onTick = onTick;
        this.seconds = 0;
        this.timer = null;
    }

    start() {
        this.timer = setInterval(() => {
            this.seconds++;
            const h = String(Math.floor(this.seconds / 3600)).padStart(2, '0');
            const m = String(Math.floor((this.seconds % 3600) / 60)).padStart(2, '0');
            const s = String(this.seconds % 60).padStart(2, '0');
            if (this.onTick) this.onTick(`${h}:${m}:${s}`);
        }, 1000);
    }

    stop() {
        if (this.timer) clearInterval(this.timer);
    }
}

/**
 * File Uploader Helper
 */
class FileUploader {
    constructor(lessonId, csrfToken) {
        this.uploadUrl = `/classroom/${lessonId}/files`;
        this.csrfToken = csrfToken;
    }

    upload(file, onProgress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && onProgress) {
                    onProgress(Math.round((e.loaded / e.total) * 100));
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try { resolve(JSON.parse(xhr.responseText)); } catch { resolve({ success: true }); }
                } else {
                    reject(new Error(`Upload failed: ${xhr.status}`));
                }
            });

            xhr.addEventListener('error', () => reject(new Error('Network error')));
            xhr.open('POST', this.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        });
    }

    formatSize(bytes) {
        if (!bytes || bytes === 0) return '0 Б';
        const units = ['Б', 'КБ', 'МБ', 'ГБ'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + units[i];
    }
}

/**
 * High-performance Packet Codec with Deflate/GZIP Compression & Safe Chunking
 * Eliminates 64 KB LiveKit publishData overflow and WebRTC SCTP channel buffering.
 */
class PacketCodec {
    constructor() {
        this.inFlightChunks = new Map();
    }

    async compress(bytes) {
        if (typeof CompressionStream === 'undefined') return null;
        try {
            const cs = new CompressionStream('deflate-raw');
            const writer = cs.writable.getWriter();
            writer.write(bytes);
            writer.close();
            const reader = cs.readable.getReader();
            const chunks = [];
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
            }
            const totalLen = chunks.reduce((acc, c) => acc + c.length, 0);
            const out = new Uint8Array(totalLen);
            let offset = 0;
            for (const c of chunks) {
                out.set(c, offset);
                offset += c.length;
            }
            return out;
        } catch (e) {
            return null;
        }
    }

    async decompress(bytes) {
        if (typeof DecompressionStream === 'undefined') return null;
        try {
            const ds = new DecompressionStream('deflate-raw');
            const writer = ds.writable.getWriter();
            writer.write(bytes);
            writer.close();
            const reader = ds.readable.getReader();
            const chunks = [];
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
            }
            const totalLen = chunks.reduce((acc, c) => acc + c.length, 0);
            const out = new Uint8Array(totalLen);
            let offset = 0;
            for (const c of chunks) {
                out.set(c, offset);
                offset += c.length;
            }
            return out;
        } catch (e) {
            return null;
        }
    }

    async encode(payload) {
        const rawBytes = new TextEncoder().encode(JSON.stringify(payload));
        let toSend = rawBytes;
        let isCompressed = false;

        // Automatically compress payloads larger than 512 bytes (20-30x ratio for Excalidraw JSON)
        if (rawBytes.length > 512) {
            const compressed = await this.compress(rawBytes);
            if (compressed && compressed.length < rawBytes.length) {
                toSend = compressed;
                isCompressed = true;
            }
        }

        const CHUNK_SIZE = 32000;
        // Fits comfortably in a single LiveKit data packet (< 64000 bytes)
        if (toSend.length <= CHUNK_SIZE) {
            if (isCompressed) {
                const packet = new Uint8Array(1 + toSend.length);
                packet[0] = 0x01; // Marker: 0x01 = single compressed payload
                packet.set(toSend, 1);
                return [packet];
            } else {
                return [toSend]; // Raw JSON UTF-8 (starts with 0x7B '{' or 0x5B '[')
            }
        }

        // Multi-chunk fragmentation for massive scenes / attachments
        const transferId = Math.floor(Math.random() * 0xFFFFFFFF);
        const totalChunks = Math.ceil(toSend.length / CHUNK_SIZE);
        const packets = [];

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, toSend.length);
            const chunkData = toSend.subarray(start, end);

            const packet = new Uint8Array(10 + chunkData.length);
            const view = new DataView(packet.buffer);
            packet[0] = 0x02; // Marker: 0x02 = chunked packet
            view.setUint32(1, transferId);
            view.setUint16(5, i);
            view.setUint16(7, totalChunks);
            packet[9] = isCompressed ? 0x01 : 0x00;
            packet.set(chunkData, 10);
            packets.push(packet);
        }
        return packets;
    }

    async decode(packet) {
        if (!packet || packet.length === 0) return null;

        // 1. Raw JSON UTF-8 (starts with '{' or '[')
        if (packet[0] === 0x7B || packet[0] === 0x5B) {
            const text = new TextDecoder().decode(packet);
            return JSON.parse(text);
        }

        // 2. Single compressed packet
        if (packet[0] === 0x01) {
            const compressed = packet.subarray(1);
            const decompressed = await this.decompress(compressed);
            if (!decompressed) throw new Error('Decompress failed');
            const text = new TextDecoder().decode(decompressed);
            return JSON.parse(text);
        }

        // 3. Chunked packet
        if (packet[0] === 0x02) {
            const view = new DataView(packet.buffer, packet.byteOffset, packet.byteLength);
            const transferId = view.getUint32(1);
            const index = view.getUint16(5);
            const total = view.getUint16(7);
            const isCompressed = packet[9] === 0x01;
            const chunkData = packet.subarray(10);

            let record = this.inFlightChunks.get(transferId);
            if (!record) {
                record = {
                    total,
                    received: 0,
                    chunks: new Array(total),
                    isCompressed,
                    timer: setTimeout(() => this.inFlightChunks.delete(transferId), 10000)
                };
                this.inFlightChunks.set(transferId, record);
            }

            if (!record.chunks[index]) {
                record.chunks[index] = chunkData;
                record.received++;
            }

            if (record.received === record.total) {
                clearTimeout(record.timer);
                this.inFlightChunks.delete(transferId);

                const totalLen = record.chunks.reduce((acc, c) => acc + c.length, 0);
                const assembled = new Uint8Array(totalLen);
                let offset = 0;
                for (const c of record.chunks) {
                    assembled.set(c, offset);
                    offset += c.length;
                }

                let rawBytes = assembled;
                if (record.isCompressed) {
                    rawBytes = await this.decompress(assembled);
                    if (!rawBytes) throw new Error('Decompress assembled chunk failed');
                }
                const text = new TextDecoder().decode(rawBytes);
                return JSON.parse(text);
            }
            return null; // Awaiting remaining chunks
        }

        // Fallback: try raw decode
        const text = new TextDecoder().decode(packet);
        return JSON.parse(text);
    }
}

/**
 * Enterprise WebRTC Manager on LiveKit Cloud SFU
 * Hardened for Ultra-Low Latency and Real-time Whiteboard Streaming
 */
class LiveKitConnectionManager {
    constructor(options = {}) {
        this.wsUrl = options.wsUrl || 'wss://edusfera.livekit.cloud';
        this.token = options.token;
        this.userRole = options.userRole; // 'tutor' | 'student'
        this.userName = options.userName;
        this.onLocalTrack = options.onLocalTrack;
        this.onRemoteTrack = options.onRemoteTrack;
        this.onRemoteTrackUnsubscribed = options.onRemoteTrackUnsubscribed;
        this.onData = options.onData;
        this.onConnectionStateChange = options.onConnectionStateChange;
        this.onParticipantJoined = options.onParticipantJoined;
        this.onParticipantLeft = options.onParticipantLeft;
        this.onActiveSpeakers = options.onActiveSpeakers;
        this.onLog = options.onLog;

        this.onRemoteTrackMuted = options.onRemoteTrackMuted;
        this.onRemoteAudioMuted = options.onRemoteAudioMuted;

        this.isConnected = false;
        this.isClosed = false;
        this.localAudioTrack = null;
        this.localVideoTrack = null;
        this.facingMode = 'user';
        this._reconnectTimer = null;
        this._packetCodec = new PacketCodec();

        this._initRoom();
    }

    log(msg, level = 'info') {
        console.log(`[LiveKit] [${level}] ${msg}`);
        if (this.onLog) this.onLog(msg, level);
    }

    _initRoom() {
        if (this.room) {
            try { this.room.removeAllListeners(); } catch (_) {}
            try { this.room.disconnect(); } catch (_) {}
        }
        // adaptiveStream: false prevents video pause deadlocks when video elements are hidden/resized on mobile
        // stopLocalTrackOnUnpublish: false keeps Android camera session alive without re-prompting permissions
        // disconnectOnPageLeave: false avoids terminating call on brief backgrounding or notification shades
        this.room = new Room({
            adaptiveStream: false,
            dynacast: false,
            stopLocalTrackOnUnpublish: false,
            disconnectOnPageLeave: false,
            publishDefaults: {
                simulcast: false, // 1-on-1 direct stream: saves 60% CPU, eliminates mobile encoder queue delay
                videoCodec: 'vp8', // Hardware accelerated on all devices, lowest encoding & decoding latency
                audioPreset: AudioPresets?.speech, // Interactive speech preset (10ms buffers instead of music)
                dtx: true,
                red: true,
                degradationPreference: 'maintain-framerate', // Fluid real-time video
                videoEncoding: {
                    maxBitrate: 1_200_000,
                    maxFramerate: 30,
                    priority: 'high',
                },
            },
            audioCaptureDefaults: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
                latency: 0.01,
                channelCount: 1,
            },
            videoCaptureDefaults: {
                resolution: {
                    width: 640,
                    height: 480,
                    frameRate: 30,
                },
            },
        });
        this._setupListeners();
    }

    _setupListeners() {
        this.room.on(RoomEvent.Connected, () => {
            this.isConnected = true;
            this.log('Подключено к комнате LiveKit Cloud: ' + this.room.name, 'success');
            if (this.onConnectionStateChange) this.onConnectionStateChange('connected');
            this.syncRemoteTracks();
        });

        this.room.on(RoomEvent.Disconnected, (reason) => {
            this.isConnected = false;
            this.log('Отключено от LiveKit: ' + reason, 'warn');
            if (this.onConnectionStateChange) this.onConnectionStateChange('disconnected');

            // Do not auto-reconnect if kicked due to duplicate session or manual removal
            if (reason === 2 || reason === 'DUPLICATE_IDENTITY' || reason === 3 || reason === 'PARTICIPANT_REMOVED') {
                this.log('Вход с другого устройства/вкладки под тем же аккаунтом', 'warn');
                return;
            }

            if (!this.isClosed && this.token) {
                if (this._reconnectTimer) clearTimeout(this._reconnectTimer);
                this._reconnectTimer = setTimeout(async () => {
                    if (!this.isClosed && !this.isConnected) {
                        this.log('Авто-переподключение к LiveKit...', 'info');
                        try {
                            this._initRoom();
                            await this.start();
                        } catch (err) {
                            this.log('Ошибка авто-переподключения: ' + err.message, 'warn');
                        }
                    }
                }, 2000);
            }
        });

        this.room.on(RoomEvent.Reconnecting, () => {
            this.log('Переподключение к LiveKit...', 'warn');
            if (this.onConnectionStateChange) this.onConnectionStateChange('reconnecting');
        });

        this.room.on(RoomEvent.Reconnected, () => {
            this.isConnected = true;
            this.log('Связь с LiveKit восстановлена', 'success');
            if (this.onConnectionStateChange) this.onConnectionStateChange('connected');
            this.syncRemoteTracks();
        });

        this.room.on(RoomEvent.ParticipantConnected, (participant) => {
            this.log(`Собеседник подключился: ${participant.name || participant.identity}`, 'success');
            if (this.onParticipantJoined) this.onParticipantJoined(participant);
            this.syncRemoteTracks();
        });

        this.room.on(RoomEvent.ParticipantDisconnected, (participant) => {
            this.log(`Собеседник отключился: ${participant.name || participant.identity}`, 'warn');
            if (this.onParticipantLeft) this.onParticipantLeft(participant);
        });

        this.room.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
            this.log(`Получен трек ${track.kind} от ${participant.name || participant.identity}`, 'success');
            // Force zero playout delay hint on WebRTC jitter buffer for Ultra-Low Latency
            if (typeof track.setPlayoutDelay === 'function') {
                try {
                    track.setPlayoutDelay(0);
                } catch (_) {}
            }
            if (this.onRemoteTrack) {
                this.onRemoteTrack(track, publication, participant);
            }
        });

        this.room.on(RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
            this.log(`Трек ${track.kind} отключен`, 'info');
            if (this.onRemoteTrackUnsubscribed) {
                this.onRemoteTrackUnsubscribed(track, publication, participant);
            }
        });

        this.room.on(RoomEvent.TrackMuted, (pub, participant) => {
            if (!participant.isLocal) {
                // In LiveKit, RemoteTrackPublication has metadataMuted which reflects actual participant mute state.
                // Do not mute UI on transient WebRTC renegotiation/jitter packet loss if metadata is not muted.
                const isExplicitlyMuted = pub.metadataMuted === true || (pub.isMuted && !pub.track);
                if (pub.kind === 'video' && isExplicitlyMuted && this.onRemoteTrackMuted) {
                    this.onRemoteTrackMuted(true);
                }
                if (pub.kind === 'audio' && isExplicitlyMuted && this.onRemoteAudioMuted) {
                    this.onRemoteAudioMuted(true);
                }
            }
        });

        this.room.on(RoomEvent.TrackUnmuted, (pub, participant) => {
            if (!participant.isLocal) {
                if (pub.kind === 'video') {
                    if (this.onRemoteTrackMuted) this.onRemoteTrackMuted(false);
                    if (this.onRemoteTrackUnmuted) this.onRemoteTrackUnmuted();
                }
                if (pub.kind === 'audio') {
                    if (this.onRemoteAudioMuted) this.onRemoteAudioMuted(false);
                }
                if (pub.track && this.onRemoteTrack) {
                    if (typeof pub.track.setPlayoutDelay === 'function') {
                        try { pub.track.setPlayoutDelay(0); } catch (_) {}
                    }
                    this.onRemoteTrack(pub.track, pub, participant);
                }
            }
        });

        this.room.on(RoomEvent.LocalTrackPublished, (pub) => {
            if (pub.track && this.onLocalTrack) {
                if (pub.track.kind === 'video') this.localVideoTrack = pub.track;
                if (pub.track.kind === 'audio') this.localAudioTrack = pub.track;
                this.onLocalTrack(pub.track, pub);
            }
        });

        this.room.on(RoomEvent.ActiveSpeakersChanged, (speakers) => {
            if (this.onActiveSpeakers) {
                this.onActiveSpeakers(speakers);
            }
        });

        this.room.on(RoomEvent.DataReceived, async (payload, participant) => {
            try {
                const data = await this._packetCodec.decode(payload);
                if (data && this.onData) {
                    this.onData(data, participant);
                }
            } catch (e) {
                console.warn('[LiveKit] Data parse error', e);
            }
        });
    }

    syncRemoteTracks() {
        if (!this.room || !this.room.remoteParticipants) return;
        this.room.remoteParticipants.forEach((participant) => {
            if (this.onParticipantJoined) this.onParticipantJoined(participant);
            participant.trackPublications.forEach((pub) => {
                if (pub.isSubscribed && pub.track) {
                    if (typeof pub.track.setPlayoutDelay === 'function') {
                        try { pub.track.setPlayoutDelay(0); } catch (_) {}
                    }
                    if (this.onRemoteTrack) {
                        this.onRemoteTrack(pub.track, pub, participant);
                    }
                }
            });
        });
    }

    async start() {
        if (!this.token) {
            throw new Error('Токен LiveKit отсутствует');
        }
        this.isClosed = false;
        this.log('Подключение к ' + this.wsUrl + '...', 'info');
        await this.room.connect(this.wsUrl, this.token);

        // Keep local media streams persistent to prevent repeating Android permission dialogs
        const hasLiveVideo = this.localVideoTrack?.mediaStreamTrack?.readyState === 'live';
        const hasLiveAudio = this.localAudioTrack?.mediaStreamTrack?.readyState === 'live';

        if (!hasLiveVideo || !hasLiveAudio) {
            try {
                const tracks = await createLocalTracks({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                        latency: 0.01,
                        channelCount: 1,
                    },
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 640, max: 800 },
                        height: { ideal: 480, max: 600 },
                        frameRate: { ideal: 30, max: 30 },
                    }
                });
                for (const t of tracks) {
                    if (t.kind === 'audio') this.localAudioTrack = t;
                    if (t.kind === 'video') this.localVideoTrack = t;
                }
            } catch (bothErr) {
                if (!hasLiveAudio) {
                    try {
                        this.localAudioTrack = await createLocalAudioTrack({
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: true,
                            latency: 0.01,
                            channelCount: 1,
                        });
                    } catch (aErr) {
                        this.log('Микрофон недоступен: ' + aErr.message, 'warn');
                    }
                }
                if (!hasLiveVideo) {
                    try {
                        this.localVideoTrack = await createLocalVideoTrack({
                            facingMode: this.facingMode,
                            width: { ideal: 640, max: 800 },
                            height: { ideal: 480, max: 600 },
                            frameRate: { ideal: 30, max: 30 },
                        });
                    } catch (vErr) {
                        this.log('Камера недоступна: ' + vErr.message, 'warn');
                    }
                }
            }
        }

        if (this.localAudioTrack) {
            try {
                await this.room.localParticipant.publishTrack(this.localAudioTrack, {
                    audioPreset: AudioPresets?.speech,
                    dtx: true,
                    red: true,
                    priority: 'high',
                });
                this.log('Микрофон подключен (Ultra-Low Latency Speech)', 'success');
                if (this.onLocalTrack) this.onLocalTrack(this.localAudioTrack);
            } catch (err) {
                this.log('Ошибка публикации аудио: ' + err.message, 'warn');
            }
        }

        if (this.localVideoTrack) {
            try {
                await this.room.localParticipant.publishTrack(this.localVideoTrack, {
                    simulcast: false, // 1-on-1: no multi-layer encoding overhead
                    videoCodec: 'vp8',
                    degradationPreference: 'maintain-framerate',
                    videoEncoding: {
                        maxBitrate: 1_200_000,
                        maxFramerate: 30,
                        priority: 'high',
                    },
                });
                this.log('Камера подключена (VP8 30fps Direct)', 'success');
                if (this.onLocalTrack) this.onLocalTrack(this.localVideoTrack);
            } catch (err) {
                this.log('Ошибка публикации видео: ' + err.message, 'warn');
            }
        }

        this.syncRemoteTracks();
    }

    async setMicEnabled(enabled) {
        if (this.localAudioTrack) {
            try {
                if (enabled) {
                    await this.localAudioTrack.unmute();
                    if (this.localAudioTrack.mediaStreamTrack) {
                        this.localAudioTrack.mediaStreamTrack.enabled = true;
                    }
                } else {
                    await this.localAudioTrack.mute();
                    if (this.localAudioTrack.mediaStreamTrack) {
                        this.localAudioTrack.mediaStreamTrack.enabled = false;
                    }
                }
                return;
            } catch (e) {
                console.warn('[LiveKit] setMicEnabled error:', e);
            }
        }
        if (this.room?.localParticipant) {
            try {
                await this.room.localParticipant.setMicrophoneEnabled(enabled);
            } catch (_) {}
        }
    }

    async setCameraEnabled(enabled) {
        if (this.localVideoTrack) {
            try {
                if (enabled) {
                    await this.localVideoTrack.unmute();
                    if (this.localVideoTrack.mediaStreamTrack) {
                        this.localVideoTrack.mediaStreamTrack.enabled = true;
                    }
                } else {
                    await this.localVideoTrack.mute();
                    if (this.localVideoTrack.mediaStreamTrack) {
                        this.localVideoTrack.mediaStreamTrack.enabled = false;
                    }
                }
                return;
            } catch (e) {
                console.warn('[LiveKit] setCameraEnabled error:', e);
            }
        }
        if (this.room?.localParticipant) {
            try {
                await this.room.localParticipant.setCameraEnabled(enabled);
            } catch (_) {}
        }
    }

    async flipCamera() {
        this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
        if (this.localVideoTrack && typeof this.localVideoTrack.restartTrack === 'function') {
            try {
                await this.localVideoTrack.restartTrack({ facingMode: this.facingMode });
                if (this.onLocalTrack) this.onLocalTrack(this.localVideoTrack);
                return this.facingMode;
            } catch (e) {
                console.warn('[LiveKit] flipCamera restartTrack error:', e);
            }
        }
        if (this.room?.localParticipant && typeof this.room.localParticipant.switchCamera === 'function') {
            try {
                await this.room.localParticipant.switchCamera();
                return this.facingMode;
            } catch (e) {
                console.warn('[LiveKit] switchCamera error:', e);
            }
        }
        return this.facingMode;
    }

    async toggleScreenShare(enabled) {
        return this.room.localParticipant.setScreenShareEnabled(enabled);
    }

    sendData(payload, options = {}) {
        if (!this.room?.localParticipant) return false;
        const reliable = options.reliable !== false && payload?.type !== 'wb_excalidraw_pointer';

        // Fast path for high-frequency lossy updates (e.g. pointer coordinates)
        if (!reliable || (typeof payload === 'object' && payload?.type === 'wb_excalidraw_pointer')) {
            try {
                const rawBytes = new TextEncoder().encode(JSON.stringify(payload));
                this.room.localParticipant.publishData(rawBytes, { reliable: false }).catch(() => {});
                return true;
            } catch (e) {
                console.warn('[LiveKit] sendData lossy error:', e);
                return false;
            }
        }

        // Reliable path with compression and chunking (eliminates 64 KB overflow permanently)
        (async () => {
            try {
                const packets = await this._packetCodec.encode(payload);
                for (const packet of packets) {
                    await this.room.localParticipant.publishData(packet, { reliable: true });
                }
            } catch (err) {
                console.warn('[LiveKit] sendData reliable error:', err);
            }
        })();
        return true;
    }

    close() {
        this.isClosed = true;
        if (this._reconnectTimer) clearTimeout(this._reconnectTimer);
        try {
            if (this.localAudioTrack) this.localAudioTrack.stop();
            if (this.localVideoTrack) this.localVideoTrack.stop();
        } catch (_) {}
        try {
            this.room?.disconnect();
        } catch (e) {}
    }
}


/**
 * Ultra-reliable Whiteboard Synchronizer over Edusfera Backend Server
 * High performance with long-polling and optimistic local updates.
 */
class ServerWhiteboardManager {
    constructor({ lessonId, csrfToken, onRemoteSync, onLockToggle, initialLocked = false, onStatusChange }) {
        this.lessonId = lessonId;
        this.csrfToken = csrfToken;
        this.clientId = 'client_' + Math.random().toString(36).substring(2, 9) + '_' + Date.now();
        this.onRemoteSync = onRemoteSync;
        this.onLockToggle = onLockToggle;
        this.onStatusChange = onStatusChange;
        this.lastVersion = 0;
        this.isStopped = false;
        this.isLocked = initialLocked;
        this.syncTimeout = null;
        this.pendingElements = null;
        this.pendingVersion = 0;
        this.isSending = false;
        this.pollAbortController = null;
    }

    start() {
        this.isStopped = false;
        if (this.onStatusChange) this.onStatusChange('active');
        this._pollLoop();
    }

    stop() {
        this.isStopped = true;
        if (this.syncTimeout) {
            clearTimeout(this.syncTimeout);
            this.syncTimeout = null;
        }
        if (this.pollAbortController) {
            try { this.pollAbortController.abort(); } catch (_) {}
            this.pollAbortController = null;
        }
        if (this.onStatusChange) this.onStatusChange('stopped');
    }

    // Broadcast local changes to server (throttled to 40ms)
    broadcast(elements, version) {
        if (!elements) return;
        this.pendingElements = elements;
        this.pendingVersion = version || Date.now();
        this.lastVersion = Math.max(this.lastVersion, this.pendingVersion);

        if (this.isSending) return;
        if (this.syncTimeout) return;

        this.syncTimeout = setTimeout(() => {
            this.syncTimeout = null;
            this._sendPending();
        }, 40);
    }

    async _sendPending() {
        if (!this.pendingElements || this.isSending) return;
        const elementsToSend = this.pendingElements;
        const versionToSend = this.pendingVersion;
        this.pendingElements = null;
        this.isSending = true;

        try {
            const res = await fetch(`/classroom/${this.lessonId}/whiteboard/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    elements: elementsToSend,
                    version: versionToSend,
                    client_id: this.clientId,
                }),
            });
            if (res.ok) {
                const data = await res.json();
                if (data.version) {
                    this.lastVersion = Math.max(this.lastVersion, data.version);
                }
            }
        } catch (err) {
            console.warn('[ServerWhiteboard] Sync error:', err);
        } finally {
            this.isSending = false;
            if (this.pendingElements) {
                this.syncTimeout = setTimeout(() => {
                    this.syncTimeout = null;
                    this._sendPending();
                }, 40);
            }
        }
    }

    async lockToggle(isLocked) {
        this.isLocked = isLocked;
        try {
            await fetch(`/classroom/${this.lessonId}/whiteboard/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    elements: typeof window.getExcalidrawElements === 'function' ? window.getExcalidrawElements() : [],
                    version: Date.now(),
                    client_id: this.clientId,
                    is_locked: isLocked,
                }),
            });
        } catch (e) {
            console.warn('[ServerWhiteboard] Lock error:', e);
        }
    }

    async clear() {
        this.lastVersion = Date.now();
        this.pendingElements = null;
        try {
            await fetch(`/classroom/${this.lessonId}/whiteboard/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    elements: [],
                    version: this.lastVersion,
                    client_id: this.clientId,
                }),
            });
        } catch (e) {
            console.warn('[ServerWhiteboard] Clear error:', e);
        }
    }

    async _pollLoop() {
        if (this.isStopped) return;
        try {
            this.pollAbortController = new AbortController();
            const res = await fetch(`/classroom/${this.lessonId}/whiteboard/poll?since_version=${this.lastVersion}&client_id=${this.clientId}`, {
                signal: this.pollAbortController.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (res.ok) {
                const data = await res.json();
                if (data.has_update && data.state) {
                    const { elements, version, is_locked } = data.state;
                    if (version > this.lastVersion) {
                        this.lastVersion = version;
                        if (typeof this.onRemoteSync === 'function') {
                            this.onRemoteSync(elements);
                        }
                    }
                    if (is_locked !== undefined && is_locked !== this.isLocked) {
                        this.isLocked = is_locked;
                        if (typeof this.onLockToggle === 'function') {
                            this.onLockToggle(is_locked);
                        }
                    }
                } else if (data.version && data.version > this.lastVersion) {
                    this.lastVersion = data.version;
                }
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.warn('[ServerWhiteboard] Poll error:', err);
                await new Promise(r => setTimeout(r, 400));
            }
        }

        if (!this.isStopped) {
            setTimeout(() => this._pollLoop(), 20);
        }
    }
}

// Export for Vite & global Alpine
export { LiveKitConnectionManager, ServerWhiteboardManager, SignalingClient, P2PConnectionManager, WhiteboardEngine, SessionTimer, FileUploader, Track };

window.ClassroomModules = {
    LiveKitConnectionManager,
    ServerWhiteboardManager,
    SignalingClient,
    P2PConnectionManager,
    WhiteboardEngine,
    SessionTimer,
    FileUploader,
    Track,
    WebRTCManager: P2PConnectionManager,
    WebSocketManager: SignalingClient,
};

