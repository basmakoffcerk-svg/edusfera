import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Excalidraw, exportToBlob, reconcileElements, getSceneVersion } from '@excalidraw/excalidraw';
import '@excalidraw/excalidraw/index.css';

function ExcalidrawWhiteboard({ options = {} }) {
    const [excalidrawAPI, setExcalidrawAPI] = useState(null);
    const isReceivingRemoteUpdate = useRef(false);
    const lastBroadcastedVersion = useRef(0);
    const syncTimeoutRef = useRef(null);

    // Expose API to window for Alpine.js / classroom integration
    useEffect(() => {
        if (!excalidrawAPI) return;
        window.excalidrawAPI = excalidrawAPI;

        // Apply initial read-only state if student and board is locked
        if (options.isBoardLocked) {
            excalidrawAPI.updateScene({
                appState: { viewModeEnabled: true }
            });
        }

        // Apply initial theme
        const theme = options.isDark ? 'dark' : 'light';
        excalidrawAPI.updateScene({
            appState: { theme }
        });

        // Robust remote scene reconciliation handler
        const applyRemoteElements = (remoteElements) => {
            if (!remoteElements || !Array.isArray(remoteElements) || !excalidrawAPI) return;
            isReceivingRemoteUpdate.current = true;
            try {
                if (remoteElements.length === 0) {
                    lastBroadcastedVersion.current = 0;
                    excalidrawAPI.updateScene({
                        elements: [],
                        commitToHistory: false,
                    });
                    return;
                }

                const currentElements = excalidrawAPI.getSceneElements() || [];
                const currentAppState = excalidrawAPI.getAppState() || {};

                // Use official Excalidraw element reconciliation algorithm
                const reconciled = reconcileElements(currentElements, remoteElements, currentAppState);

                const newVersion = getSceneVersion(reconciled);
                lastBroadcastedVersion.current = newVersion;

                excalidrawAPI.updateScene({
                    elements: reconciled,
                    commitToHistory: false,
                });
            } catch (err) {
                console.warn('[Excalidraw] Failed to reconcile remote scene:', err);
            } finally {
                // Release lock on next animation frame to prevent freeze and allow concurrent drawing
                requestAnimationFrame(() => {
                    isReceivingRemoteUpdate.current = false;
                });
            }
        };

        // Register both naming conventions to guarantee compatibility
        window.__onRemoteExcalidrawScene = (payload) => {
            const elements = payload?.elements || payload;
            applyRemoteElements(elements);
        };
        window.__onExcalidrawRemoteSync = window.__onRemoteExcalidrawScene;

        // Register global callback for remote cursor (collaborator pointer)
        window.__onRemoteExcalidrawPointer = (payload) => {
            if (!payload || !excalidrawAPI) return;
            try {
                const collaborators = new Map(excalidrawAPI.getAppState().collaborators || []);
                collaborators.set(payload.userId || 'peer', {
                    pointer: { x: payload.x, y: payload.y },
                    button: payload.button || 'up',
                    username: payload.userName || 'Собеседник',
                    color: {
                        background: payload.color || '#7D39EB',
                        stroke: payload.color || '#7D39EB',
                    },
                });
                excalidrawAPI.updateScene({ collaborators });
            } catch (e) {}
        };
        window.__onExcalidrawRemotePointer = window.__onRemoteExcalidrawPointer;

        // Register global callback for board lock toggle
        window.__onExcalidrawLockToggle = (isLocked) => {
            excalidrawAPI.updateScene({
                appState: { viewModeEnabled: isLocked }
            });
        };

        // Register global callback for theme change
        window.__onExcalidrawThemeToggle = (isDark) => {
            excalidrawAPI.updateScene({
                appState: { theme: isDark ? 'dark' : 'light' }
            });
        };

        // Register global helper to get current scene elements for initial sync
        window.getExcalidrawElements = () => {
            try {
                return excalidrawAPI ? excalidrawAPI.getSceneElements() : [];
            } catch (e) {
                return [];
            }
        };

        // Register global helper to clear the board
        window.clearExcalidrawBoard = () => {
            try {
                if (excalidrawAPI) {
                    excalidrawAPI.updateScene({ elements: [] });
                }
            } catch (e) {}
        };

        return () => {
            if (trailingTimer.current) clearTimeout(trailingTimer.current);
            delete window.__onRemoteExcalidrawScene;
            delete window.__onExcalidrawRemoteSync;
            delete window.__onRemoteExcalidrawPointer;
            delete window.__onExcalidrawRemotePointer;
            delete window.__onExcalidrawLockToggle;
            delete window.__onExcalidrawThemeToggle;
            delete window.getExcalidrawElements;
            delete window.clearExcalidrawBoard;
        };
    }, [excalidrawAPI]);

    const lastBroadcastTime = useRef(0);
    const trailingTimer = useRef(null);
    const latestElementsRef = useRef(null);
    const latestVersionRef = useRef(0);

    const broadcastSync = (elements, version) => {
        if (options.onBroadcast && typeof options.onBroadcast === 'function') {
            options.onBroadcast({
                type: 'wb_excalidraw_sync',
                elements: elements,
                version: version,
            });
        }
    };

    // Handle local drawing changes and broadcast in true real-time (~28fps)
    const handleChange = (elements, appState, files) => {
        if (!elements || isReceivingRemoteUpdate.current) return;
        if (options.isBoardLocked) return;

        // Calculate monotonic scene version
        const currentVersion = getSceneVersion(elements);
        if (currentVersion === lastBroadcastedVersion.current) return;

        latestElementsRef.current = elements;
        latestVersionRef.current = currentVersion;

        const now = performance.now();
        const elapsed = now - lastBroadcastTime.current;
        const THROTTLE_MS = 35; // Responsive 35ms gives silky smooth real-time pen strokes

        if (elapsed >= THROTTLE_MS) {
            if (trailingTimer.current) {
                clearTimeout(trailingTimer.current);
                trailingTimer.current = null;
            }
            lastBroadcastTime.current = now;
            lastBroadcastedVersion.current = currentVersion;
            broadcastSync(elements, currentVersion);
        } else {
            // Guarantee trailing edge fires to sync complete stroke when pen/mouse is released
            if (!trailingTimer.current) {
                trailingTimer.current = setTimeout(() => {
                    trailingTimer.current = null;
                    lastBroadcastTime.current = performance.now();
                    lastBroadcastedVersion.current = latestVersionRef.current;
                    broadcastSync(latestElementsRef.current, latestVersionRef.current);
                }, THROTTLE_MS - elapsed);
            }
        }
    };

    // Track cursor movement on pointer update to show remote collaborator pointer
    const lastPointerBroadcast = useRef(0);
    const handlePointerUpdate = (payload) => {
        if (!payload || !payload.pointer) return;
        const now = performance.now();
        if (now - lastPointerBroadcast.current < 40) return; // throttle to ~25fps
        lastPointerBroadcast.current = now;
        if (options.onPointerMove && typeof options.onPointerMove === 'function') {
            options.onPointerMove(payload.pointer.x, payload.pointer.y);
        }
    };

    return (
        <div className="cr-excalidraw-container" style={{ width: '100%', height: '100%', position: 'relative' }}>
            <Excalidraw
                excalidrawAPI={(api) => setExcalidrawAPI(api)}
                onChange={handleChange}
                onPointerUpdate={handlePointerUpdate}
                viewModeEnabled={options.isBoardLocked || false}
                theme={options.isDark ? 'dark' : 'light'}
                langCode="ru-RU"
                UIOptions={{
                    canvasActions: {
                        loadScene: false,
                        saveAsImage: true,
                        export: { saveFileToDisk: true },
                        theme: false, // Managed by Edusfera bottom dock
                        clearCanvas: true,
                        changeViewBackgroundColor: true,
                    },
                }}
            />
        </div>
    );
}

export function mountExcalidraw(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) {
        console.warn(`[Excalidraw] Element with id #${containerId} not found.`);
        return null;
    }

    const root = createRoot(el);
    root.render(<ExcalidrawWhiteboard options={options} />);
    return root;
}

if (typeof window !== 'undefined') {
    window.mountExcalidraw = mountExcalidraw;
    window.dispatchEvent(new CustomEvent('excalidraw:ready'));
}

