// ============================================
// Edusfera.by — Virtual Classroom Modules
// ES Modules for WebRTC, WebSocket, Whiteboard
// ============================================

// ============================================
// WebSocket Manager
// ============================================

/**
 * Manages WebSocket connection with auto-reconnect
 * and message type dispatching.
 */
class WebSocketManager {
    /**
     * @param {string} url - WebSocket server URL
     */
    constructor(url) {
        this.url = url;
        /** @type {WebSocket|null} */
        this.ws = null;
        /** @type {Map<string, Set<Function>>} */
        this.handlers = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.baseDelay = 1000;
        this.isClosedManually = false;
        this._reconnectTimer = null;
    }

    /**
     * Establish WebSocket connection
     */
    connect() {
        if (this.ws && (this.ws.readyState === WebSocket.OPEN || this.ws.readyState === WebSocket.CONNECTING)) {
            return;
        }

        try {
            this.ws = new WebSocket(this.url);
        } catch (err) {
            console.error('[Classroom] WebSocket creation failed:', err);
            this._scheduleReconnect();
            return;
        }

        this.ws.onopen = () => {
            console.log('[Classroom] WebSocket connected');
            this.reconnectAttempts = 0;
            this._dispatch('_connected', {});
        };

        this.ws.onclose = (event) => {
            console.log('[Classroom] WebSocket closed:', event.code, event.reason);
            this._dispatch('_disconnected', { code: event.code, reason: event.reason });

            if (!this.isClosedManually) {
                this._scheduleReconnect();
            }
        };

        this.ws.onerror = (err) => {
            console.error('[Classroom] WebSocket error:', err);
        };

        this.ws.onmessage = (event) => {
            try {
                const msg = JSON.parse(event.data);
                if (msg.type) {
                    this._dispatch(msg.type, msg.data || {});
                }
            } catch (err) {
                console.error('[Classroom] Failed to parse WS message:', err);
            }
        };
    }

    /**
     * Send a typed message via WebSocket
     * @param {string} type - Message type
     * @param {Object} data - Message payload
     */
    send(type, data = {}) {
        if (!this.connected) {
            console.warn('[Classroom] Cannot send, WS not connected');
            return;
        }
        this.ws.send(JSON.stringify({ type, data }));
    }

    /**
     * Register handler for a message type
     * @param {string} type - Message type
     * @param {Function} handler - Callback
     * @returns {Function} Unsubscribe function
     */
    on(type, handler) {
        if (!this.handlers.has(type)) {
            this.handlers.set(type, new Set());
        }
        this.handlers.get(type).add(handler);
        return () => this.off(type, handler);
    }

    /**
     * Remove a handler for a message type
     * @param {string} type
     * @param {Function} handler
     */
    off(type, handler) {
        const set = this.handlers.get(type);
        if (set) {
            set.delete(handler);
        }
    }

    /**
     * Schedule reconnection with exponential backoff
     * @private
     */
    _scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('[Classroom] Max reconnect attempts reached');
            this._dispatch('_reconnect_failed', {});
            return;
        }

        const delay = Math.min(this.baseDelay * Math.pow(2, this.reconnectAttempts), 30000);
        console.log(`[Classroom] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts + 1})`);

        this._reconnectTimer = setTimeout(() => {
            this.reconnectAttempts++;
            this.connect();
        }, delay);
    }

    /**
     * Dispatch message to registered handlers
     * @private
     */
    _dispatch(type, data) {
        const set = this.handlers.get(type);
        if (set) {
            set.forEach((handler) => {
                try {
                    handler(data);
                } catch (err) {
                    console.error(`[Classroom] Handler error for "${type}":`, err);
                }
            });
        }
    }

    /**
     * Close WebSocket connection
     */
    close() {
        this.isClosedManually = true;
        clearTimeout(this._reconnectTimer);
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }

    /**
     * Check if WebSocket is connected
     * @returns {boolean}
     */
    get connected() {
        return this.ws?.readyState === WebSocket.OPEN;
    }
}

// ============================================
// WebRTC Manager (SFU)
// ============================================

/**
 * Manages single WebRTC connection to SFU server.
 */
class WebRTCManager {
    /**
     * @param {WebSocketManager} wsManager
     * @param {RTCIceServer[]} iceServers
     */
    constructor(wsManager, iceServers) {
        this.ws = wsManager;
        this.iceServers = iceServers || [{ urls: 'stun:stun.l.google.com:19302' }];
        /** @type {RTCPeerConnection|null} */
        this.pc = null;
        /** @type {MediaStream|null} */
        this.localStream = null;
        /** @type {MediaStream|null} */
        this.screenStream = null;

        // Callbacks
        /** @type {Function|null} */
        this.onTrack = null;
        /** @type {Function|null} */
        this.onLocalStream = null;
    }

    /**
     * Acquire local media (camera + microphone)
     * @param {boolean} audioEnabled
     * @param {boolean} videoEnabled
     * @returns {Promise<MediaStream>}
     */
    async getLocalMedia(audioEnabled = true, videoEnabled = true) {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                audio: audioEnabled ? {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                } : false,
                video: videoEnabled ? {
                    width: { ideal: 1280, max: 1920 },
                    height: { ideal: 720, max: 1080 },
                    frameRate: { ideal: 30 },
                } : false,
            });

            console.log('[Classroom] Local media acquired');

            if (this.onLocalStream) {
                this.onLocalStream(this.localStream);
            }

            return this.localStream;
        } catch (err) {
            console.error('[Classroom] Failed to get local media:', err);
            throw err;
        }
    }

    /**
     * Create connection to SFU and send initial offer
     * @returns {Promise<void>}
     */
    async connect() {
        const config = {
            iceServers: this.iceServers,
            iceCandidatePoolSize: 10,
        };

        this.pc = new RTCPeerConnection(config);

        // Add local tracks to connection
        if (this.localStream) {
            this.localStream.getTracks().forEach((track) => {
                this.pc.addTrack(track, this.localStream);
            });
        }

        // ICE candidate handling
        this.pc.onicecandidate = (event) => {
            if (event.candidate) {
                this.ws.send('ice', {
                    candidate: event.candidate.toJSON(),
                });
            }
        };

        // Remote track handling (SFU sends all tracks here)
        this.pc.ontrack = (event) => {
            // SFU preserves stream.id == participant.id
            const stream = event.streams[0];
            const peerId = stream.id;

            console.log(`[Classroom] Received track for peer: ${peerId}`);

            if (this.onTrack && peerId) {
                this.onTrack(peerId, stream);
            }
        };

        // Connection state monitoring
        this.pc.onconnectionstatechange = () => {
            console.log(`[Classroom] PC connection state: ${this.pc.connectionState}`);
        };

        try {
            const offer = await this.pc.createOffer({
                offerToReceiveAudio: true,
                offerToReceiveVideo: true,
            });
            await this.pc.setLocalDescription(offer);
            
            this.ws.send('offer', {
                sdp: this.pc.localDescription.toJSON(),
            });
        } catch (err) {
            console.error('[Classroom] Failed to create SFU offer:', err);
        }
    }

    /**
     * Handle incoming SDP offer from SFU (renegotiation)
     * @param {RTCSessionDescriptionInit} sdp
     */
    async handleOffer(sdp) {
        if (!this.pc) return;
        try {
            await this.pc.setRemoteDescription(new RTCSessionDescription(sdp));
            const answer = await this.pc.createAnswer();
            await this.pc.setLocalDescription(answer);

            this.ws.send('answer', {
                sdp: this.pc.localDescription.toJSON(),
            });
        } catch (err) {
            console.error('[Classroom] Failed to handle SFU offer:', err);
        }
    }

    /**
     * Handle incoming SDP answer from SFU
     * @param {RTCSessionDescriptionInit} sdp
     */
    async handleAnswer(sdp) {
        if (!this.pc) return;
        try {
            await this.pc.setRemoteDescription(new RTCSessionDescription(sdp));
        } catch (err) {
            console.error('[Classroom] Failed to handle SFU answer:', err);
        }
    }

    /**
     * Handle incoming ICE candidate from SFU
     * @param {RTCIceCandidateInit} candidate
     */
    async handleIceCandidate(candidate) {
        if (!this.pc) return;
        try {
            await this.pc.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (err) {
            console.error('[Classroom] Failed to add SFU ICE candidate:', err);
        }
    }

    /**
     * Start screen sharing, replacing video track
     * @returns {Promise<MediaStream>}
     */
    async startScreenShare() {
        try {
            this.screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    cursor: 'always',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            });

            const screenTrack = this.screenStream.getVideoTracks()[0];

            if (this.pc) {
                const sender = this.pc.getSenders().find((s) => s.track?.kind === 'video');
                if (sender) {
                    sender.replaceTrack(screenTrack);
                }
            }

            // Listen for user stopping screen share via browser UI
            screenTrack.onended = () => {
                this.stopScreenShare();
            };

            console.log('[Classroom] Screen sharing started');
            this.ws.send('media-toggle', { kind: 'video', enabled: true }); // Ensure SFU forwards it
            return this.screenStream;
        } catch (err) {
            console.error('[Classroom] Failed to start screen share:', err);
            throw err;
        }
    }

    /**
     * Stop screen sharing and restore camera track
     */
    async stopScreenShare() {
        if (this.screenStream) {
            this.screenStream.getTracks().forEach((track) => track.stop());
            this.screenStream = null;
        }

        const cameraTrack = this.localStream?.getVideoTracks()[0];
        if (cameraTrack && this.pc) {
            const sender = this.pc.getSenders().find((s) => s.track?.kind === 'video');
            if (sender) {
                sender.replaceTrack(cameraTrack);
            }
        }
        console.log('[Classroom] Screen sharing stopped');
    }

    /**
     * Toggle audio tracks
     * @param {boolean} enabled
     */
    toggleAudio(enabled) {
        if (this.localStream) {
            this.localStream.getAudioTracks().forEach((track) => {
                track.enabled = enabled;
            });
        }
        this.ws.send('media-toggle', { kind: 'audio', enabled: enabled });
    }

    /**
     * Toggle video tracks
     * @param {boolean} enabled
     */
    toggleVideo(enabled) {
        if (this.localStream) {
            this.localStream.getVideoTracks().forEach((track) => {
                track.enabled = enabled;
            });
        }
        this.ws.send('media-toggle', { kind: 'video', enabled: enabled });
    }

    /**
     * Close connection and streams
     */
    close() {
        if (this.pc) {
            this.pc.close();
            this.pc = null;
        }
        if (this.localStream) {
            this.localStream.getTracks().forEach((track) => track.stop());
            this.localStream = null;
        }
        if (this.screenStream) {
            this.screenStream.getTracks().forEach((track) => track.stop());
            this.screenStream = null;
        }
        console.log('[Classroom] WebRTC manager closed');
    }
}

// ============================================
// Whiteboard Engine
// ============================================

/**
 * Canvas-based whiteboard with tools, undo,
 * and real-time sync via WebSocket.
 */
class WhiteboardEngine {
    /**
     * @param {HTMLCanvasElement} canvas
     * @param {WebSocketManager} wsManager
     */
    constructor(canvas, wsManager) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.ws = wsManager;
        this.tool = 'pen';
        this.color = '#7D39EB';
        this.lineWidth = 3;
        this.isDrawing = false;
        /** @type {ImageData[]} */
        this.history = [];
        this.currentPath = [];
        this.startPoint = null;
        this.maxHistory = 30;

        // Bound event handlers
        this._onMouseDown = this._handleStart.bind(this);
        this._onMouseMove = this._handleMove.bind(this);
        this._onMouseUp = (e) => this._handleEnd(e);
        this._onTouchStart = this._handleTouchStart.bind(this);
        this._onTouchMove = this._handleTouchMove.bind(this);
        this._onTouchEnd = (e) => this._handleEnd(e);
    }

    /**
     * Initialize the whiteboard
     */
    init() {
        this.resize();

        // Mouse events
        this.canvas.addEventListener('mousedown', this._onMouseDown);
        this.canvas.addEventListener('mousemove', this._onMouseMove);
        this.canvas.addEventListener('mouseup', this._onMouseUp);
        this.canvas.addEventListener('mouseleave', this._onMouseUp);

        // Touch events
        this.canvas.addEventListener('touchstart', this._onTouchStart, { passive: false });
        this.canvas.addEventListener('touchmove', this._onTouchMove, { passive: false });
        this.canvas.addEventListener('touchend', this._onTouchEnd);

        // Remote sync
        this.ws.on('wb', (action) => this.applyRemoteAction(action));

        // Save initial state
        this._saveState();

        console.log('[Classroom] Whiteboard initialized');
    }

    /**
     * Save current canvas state to history
     * @private
     */
    _saveState() {
        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        this.history.push(imageData);
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        }
    }

    /**
     * Get coordinates from mouse event
     * @param {MouseEvent} e
     * @returns {{x: number, y: number}}
     */
    _getCoords(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top,
        };
    }

    /** @private */
    _handleTouchStart(e) {
        e.preventDefault();
        const touch = e.touches[0];
        this._handleStart({ clientX: touch.clientX, clientY: touch.clientY });
    }

    /** @private */
    _handleTouchMove(e) {
        e.preventDefault();
        const touch = e.touches[0];
        this._handleMove({ clientX: touch.clientX, clientY: touch.clientY });
    }

    /** @private */
    _handleTouchEnd(e) {
        if (e.changedTouches && e.changedTouches.length > 0) {
            const touch = e.changedTouches[0];
            this._handleEnd({ clientX: touch.clientX, clientY: touch.clientY });
        } else {
            this._handleEnd(null);
        }
    }

    /** @private */
    _handleStart(e) {
        this.isDrawing = true;
        const coords = this._getCoords(e);
        this._saveState();

        if (this.tool === 'pen' || this.tool === 'eraser') {
            this.currentPath = [coords];
            this.ctx.beginPath();
            this.ctx.moveTo(coords.x, coords.y);
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';

            if (this.tool === 'eraser') {
                this.ctx.globalCompositeOperation = 'destination-out';
                this.ctx.lineWidth = this.lineWidth * 4;
            } else {
                this.ctx.globalCompositeOperation = 'source-over';
                this.ctx.strokeStyle = this.color;
                this.ctx.lineWidth = this.lineWidth;
            }
        } else if (this.tool === 'text') {
            const text = prompt('Введите текст:');
            if (text) {
                this.addText(coords.x, coords.y, text);
            }
            this.isDrawing = false;
        } else {
            // Shapes: line, rect, circle
            this.startPoint = coords;
        }
    }

    /** @private */
    _handleMove(e) {
        if (!this.isDrawing) return;
        const coords = this._getCoords(e);

        if (this.tool === 'pen' || this.tool === 'eraser') {
            // Draw only the new segment to prevent line thickening
            const prev = this.currentPath[this.currentPath.length - 1];
            this.ctx.beginPath();
            this.ctx.moveTo(prev.x, prev.y);
            this.ctx.lineTo(coords.x, coords.y);
            this.ctx.stroke();
            this.currentPath.push(coords);
        } else if (this.startPoint) {
            // Shape preview — restore state and draw preview
            const lastState = this.history[this.history.length - 1];
            if (lastState) {
                this.ctx.putImageData(lastState, 0, 0);
            }
            this.ctx.globalCompositeOperation = 'source-over';
            this.ctx.strokeStyle = this.color;
            this.ctx.lineWidth = this.lineWidth;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this._drawShape(this.tool, this.startPoint, coords);
        }
    }

    /** @private */
    _handleEnd(e) {
        if (!this.isDrawing) return;
        this.isDrawing = false;
        this.ctx.globalCompositeOperation = 'source-over';

        if (this.tool === 'pen' || this.tool === 'eraser') {
            this.syncToRemote({
                type: this.tool === 'eraser' ? 'erase' : 'stroke',
                data: {
                    path: this.currentPath,
                    color: this.color,
                    lineWidth: this.tool === 'eraser' ? this.lineWidth * 4 : this.lineWidth,
                },
            });
            this.currentPath = [];
        } else if (this.startPoint) {
            // Use last known coordinates if event is missing (e.g. mouseleave)
            let endPoint;
            if (e && e.clientX !== undefined) {
                endPoint = this._getCoords(e);
            } else {
                endPoint = this.startPoint;
            }
            this._drawShape(this.tool, this.startPoint, endPoint);
            this.syncToRemote({
                type: 'shape',
                data: {
                    shape: this.tool,
                    start: this.startPoint,
                    end: endPoint,
                    color: this.color,
                    lineWidth: this.lineWidth,
                },
            });
            this.startPoint = null;
        }
    }

    /**
     * Draw a shape on the canvas
     * @param {string} type - 'line', 'rect', 'circle'
     * @param {{x: number, y: number}} start
     * @param {{x: number, y: number}} end
     */
    _drawShape(type, start, end) {
        this.ctx.beginPath();

        switch (type) {
            case 'line':
                this.ctx.moveTo(start.x, start.y);
                this.ctx.lineTo(end.x, end.y);
                break;
            case 'rect':
                this.ctx.rect(start.x, start.y, end.x - start.x, end.y - start.y);
                break;
            case 'circle': {
                const rx = (end.x - start.x) / 2;
                const ry = (end.y - start.y) / 2;
                const cx = start.x + rx;
                const cy = start.y + ry;
                const r = Math.max(Math.abs(rx), Math.abs(ry));
                this.ctx.arc(cx, cy, r, 0, Math.PI * 2);
                break;
            }
        }

        this.ctx.stroke();
    }

    /**
     * Add text to the whiteboard
     * @param {number} x
     * @param {number} y
     * @param {string} text
     */
    addText(x, y, text) {
        this._saveState();
        this.ctx.globalCompositeOperation = 'source-over';
        this.ctx.fillStyle = this.color;
        this.ctx.font = `${Math.max(16, this.lineWidth * 6)}px Inter, sans-serif`;
        this.ctx.fillText(text, x, y);

        this.syncToRemote({
            type: 'text',
            data: { x, y, text, color: this.color, fontSize: Math.max(16, this.lineWidth * 6) },
        });
    }

    /**
     * Clear entire whiteboard
     */
    clear() {
        this._saveState();
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        // Fill white background
        this.ctx.fillStyle = '#ffffff';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        this.syncToRemote({ type: 'clear', data: {} });
    }

    /**
     * Undo last action
     */
    undo() {
        if (this.history.length <= 1) return;
        this.history.pop();
        const prevState = this.history[this.history.length - 1];
        if (prevState) {
            this.ctx.putImageData(prevState, 0, 0);
        }
        this.syncToRemote({ type: 'undo', data: {} });
    }

    /**
     * Send whiteboard action to remote peers
     * @param {Object} action
     */
    syncToRemote(action) {
        this.ws.send('wb', action);
    }

    /**
     * Apply an action received from a remote peer
     * @param {Object} action
     */
    applyRemoteAction(action) {
        switch (action.type) {
            case 'stroke':
            case 'erase':
                this._replayStroke(action.data, action.type === 'erase');
                break;
            case 'shape':
                this.ctx.globalCompositeOperation = 'source-over';
                this.ctx.strokeStyle = action.data.color;
                this.ctx.lineWidth = action.data.lineWidth;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';
                this._drawShape(action.data.shape, action.data.start, action.data.end);
                break;
            case 'text':
                this.ctx.globalCompositeOperation = 'source-over';
                this.ctx.fillStyle = action.data.color;
                this.ctx.font = `${action.data.fontSize}px Inter, sans-serif`;
                this.ctx.fillText(action.data.text, action.data.x, action.data.y);
                break;
            case 'clear':
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.fillStyle = '#ffffff';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                break;
            case 'undo':
                this.undo();
                break;
        }
    }

    /**
     * Replay a stroke from recorded path
     * @private
     */
    _replayStroke(data, isEraser) {
        if (!data.path || data.path.length < 2) return;

        this.ctx.beginPath();
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        if (isEraser) {
            this.ctx.globalCompositeOperation = 'destination-out';
        } else {
            this.ctx.globalCompositeOperation = 'source-over';
            this.ctx.strokeStyle = data.color;
        }
        this.ctx.lineWidth = data.lineWidth;

        this.ctx.moveTo(data.path[0].x, data.path[0].y);
        for (let i = 1; i < data.path.length; i++) {
            this.ctx.lineTo(data.path[i].x, data.path[i].y);
        }
        this.ctx.stroke();
        this.ctx.globalCompositeOperation = 'source-over';
    }

    /**
     * Serialize current whiteboard state
     * @returns {string} Data URL
     */
    getState() {
        return this.canvas.toDataURL('image/png');
    }

    /**
     * Load whiteboard state from data URL
     * @param {string} dataUrl
     */
    loadState(dataUrl) {
        const img = new Image();
        img.onload = () => {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.ctx.drawImage(img, 0, 0);
            this._saveState();
        };
        img.src = dataUrl;
    }

    /**
     * Resize canvas to match container dimensions
     */
    resize() {
        const container = this.canvas.parentElement;
        if (!container) return;

        // Save current content
        let dataUrl = null;
        try {
            dataUrl = this.canvas.toDataURL();
        } catch (_) {
            // Canvas might be empty
        }

        const rect = container.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;

        this.canvas.width = rect.width * dpr;
        this.canvas.height = (rect.height - 56) * dpr; // Account for toolbar
        this.canvas.style.width = rect.width + 'px';
        this.canvas.style.height = (rect.height - 56) + 'px';

        this.ctx.scale(dpr, dpr);

        // Restore content
        if (dataUrl && dataUrl !== 'data:,') {
            this.loadState(dataUrl);
        } else {
            // Fill white background
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        }
    }

    /**
     * Clean up event listeners
     */
    destroy() {
        this.canvas.removeEventListener('mousedown', this._onMouseDown);
        this.canvas.removeEventListener('mousemove', this._onMouseMove);
        this.canvas.removeEventListener('mouseup', this._onMouseUp);
        this.canvas.removeEventListener('mouseleave', this._onMouseUp);
        this.canvas.removeEventListener('touchstart', this._onTouchStart);
        this.canvas.removeEventListener('touchmove', this._onTouchMove);
        this.canvas.removeEventListener('touchend', this._onTouchEnd);
        console.log('[Classroom] Whiteboard destroyed');
    }
}

// ============================================
// Session Timer
// ============================================

/**
 * Counts elapsed session time and formats as HH:MM:SS
 */
class SessionTimer {
    /**
     * @param {Function} onTick - Callback with formatted time string
     */
    constructor(onTick) {
        this.seconds = 0;
        this.intervalId = null;
        this.onTick = onTick;
    }

    /** Start the timer */
    start() {
        if (this.intervalId) return;
        this.intervalId = setInterval(() => {
            this.seconds++;
            if (this.onTick) {
                this.onTick(this.getFormatted());
            }
        }, 1000);
    }

    /** Stop the timer */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    /** Reset the timer */
    reset() {
        this.seconds = 0;
    }

    /**
     * Get formatted time string
     * @returns {string} HH:MM:SS
     */
    getFormatted() {
        const h = Math.floor(this.seconds / 3600);
        const m = Math.floor((this.seconds % 3600) / 60);
        const s = this.seconds % 60;
        return [h, m, s].map((v) => String(v).padStart(2, '0')).join(':');
    }
}

// ============================================
// File Uploader
// ============================================

/**
 * Handles file uploads with progress tracking
 */
class FileUploader {
    /**
     * @param {number} lessonId
     * @param {string} csrfToken
     */
    constructor(lessonId, csrfToken) {
        this.lessonId = lessonId;
        this.csrfToken = csrfToken;
        this.uploadUrl = `/classroom/${lessonId}/files`;
    }

    /**
     * Upload a file with progress tracking
     * @param {File} file
     * @param {Function} onProgress - Callback with percent (0-100)
     * @returns {Promise<Object>} Server response
     */
    upload(file, onProgress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', file);

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && onProgress) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    onProgress(percent);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch {
                        resolve({ success: true });
                    }
                } else {
                    reject(new Error(`Upload failed with status ${xhr.status}`));
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Upload failed: network error'));
            });

            xhr.addEventListener('abort', () => {
                reject(new Error('Upload aborted'));
            });

            xhr.open('POST', this.uploadUrl);
            xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        });
    }

    /**
     * Format file size to human-readable string
     * @param {number} bytes
     * @returns {string}
     */
    formatSize(bytes) {
        if (bytes === 0) return '0 Б';
        const units = ['Б', 'КБ', 'МБ', 'ГБ'];
        const k = 1024;
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + units[i];
    }
}

// ============================================
// Export for Vite + expose globally for Alpine
// ============================================

export { WebSocketManager, WebRTCManager, WhiteboardEngine, SessionTimer, FileUploader };

// Make available globally for Alpine.js inline scripts
window.ClassroomModules = {
    WebSocketManager,
    WebRTCManager,
    WhiteboardEngine,
    SessionTimer,
    FileUploader,
};
