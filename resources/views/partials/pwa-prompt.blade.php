{{-- PWA Registration & Install Prompt --}}
<div id="edusfera-pwa-install-banner" class="ed-pwa-banner" style="display: none;" aria-hidden="true">
    <div class="ed-pwa-content">
        <div class="ed-pwa-icon">
            <img src="{{ asset('icons/icon-192x192.png') }}" alt="Edusfera" width="44" height="44" style="border-radius: 10px; display: block;">
        </div>
        <div class="ed-pwa-text">
            <div class="ed-pwa-title" id="ed-pwa-title">Приложение Edusfera</div>
            <div class="ed-pwa-desc" id="ed-pwa-desc">Установите на рабочий стол для быстрого входа и работы офлайн</div>
        </div>
        <div class="ed-pwa-actions">
            <button type="button" id="ed-pwa-install-btn" class="ed-pwa-btn ed-pwa-btn-primary">
                Установить
            </button>
            <button type="button" id="ed-pwa-dismiss-btn" class="ed-pwa-btn-close" aria-label="Закрыть">
                ✕
            </button>
        </div>
    </div>
</div>

{{-- iOS Specific Instruction Modal/Tooltip --}}
<div id="edusfera-pwa-ios-banner" class="ed-pwa-banner ed-pwa-ios" style="display: none;" aria-hidden="true">
    <div class="ed-pwa-content">
        <div class="ed-pwa-icon">
            <img src="{{ asset('icons/apple-touch-icon.png') }}" alt="Edusfera" width="44" height="44" style="border-radius: 10px; display: block;">
        </div>
        <div class="ed-pwa-text">
            <div class="ed-pwa-title">Установить на iPhone / iPad</div>
            <div class="ed-pwa-desc">
                Нажмите 
                <span class="ed-pwa-ios-icon" title="Поделиться">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: -2px;">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                        <polyline points="16 6 12 2 8 6"/>
                        <line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                </span> 
                <strong>«Поделиться»</strong> в Safari и выберите <strong>«На экран “Домой”»</strong> ⊞
            </div>
        </div>
        <div class="ed-pwa-actions">
            <button type="button" id="ed-pwa-ios-dismiss-btn" class="ed-pwa-btn ed-pwa-btn-primary" style="padding: 6px 14px; font-size: 13px;">
                Понятно
            </button>
        </div>
    </div>
</div>

<style>
    .ed-pwa-banner {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        max-width: 420px;
        width: calc(100% - 32px);
        background: rgba(12, 10, 20, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 18px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6), 0 0 24px rgba(125, 57, 235, 0.25);
        color: #FFFFFF;
        font-family: inherit;
        padding: 14px 16px;
        animation: edPwaSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @media (max-width: 640px) {
        .ed-pwa-banner {
            bottom: 16px;
            right: 16px;
            left: 16px;
            width: auto;
            max-width: none;
        }
    }
    .ed-pwa-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .ed-pwa-icon {
        flex-shrink: 0;
    }
    .ed-pwa-text {
        flex: 1;
        min-width: 0;
        text-align: left;
    }
    .ed-pwa-title {
        font-size: 14px;
        font-weight: 800;
        color: #FFFFFF;
        line-height: 1.25;
        margin-bottom: 3px;
        letter-spacing: -0.01em;
    }
    .ed-pwa-desc {
        font-size: 12px;
        line-height: 1.35;
        color: #9CA3AF;
    }
    .ed-pwa-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .ed-pwa-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .ed-pwa-btn-primary {
        background: #7D39EB;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(125, 57, 235, 0.4);
    }
    .ed-pwa-btn-primary:hover {
        background: #6D28D9;
        transform: translateY(-1px);
    }
    .ed-pwa-btn-close {
        background: transparent;
        border: none;
        color: #6B7280;
        font-size: 16px;
        padding: 6px;
        cursor: pointer;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: color 0.15s ease;
    }
    .ed-pwa-btn-close:hover {
        color: #FFFFFF;
        background: rgba(255, 255, 255, 0.1);
    }
    .ed-pwa-ios-icon {
        display: inline-flex;
        padding: 2px 4px;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 4px;
        color: #60A5FA;
    }
    @keyframes edPwaSlideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    (function() {
        // 1. Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(reg) {
                        // Check for SW updates
                        reg.addEventListener('updatefound', function() {
                            const newWorker = reg.installing;
                            if (newWorker) {
                                newWorker.addEventListener('statechange', function() {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        console.log('[Edusfera PWA] Новая версия доступна.');
                                    }
                                });
                            }
                        });
                    })
                    .catch(function(err) {
                        console.warn('[Edusfera PWA] Service Worker registration failed:', err);
                    });
            });
        }

        // 2. Check if already running in standalone / installed PWA mode
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                             window.navigator.standalone === true ||
                             document.referrer.includes('android-app://');

        if (isStandalone) {
            return; // Already in PWA app mode, no prompt needed
        }

        // Check if user dismissed prompt recently (7 days cooldown)
        const dismissedTime = localStorage.getItem('edusfera_pwa_dismissed');
        if (dismissedTime && (Date.now() - parseInt(dismissedTime, 10)) < 7 * 24 * 60 * 60 * 1000) {
            return;
        }

        // 3. Android / Chrome / Edge beforeinstallprompt handling
        let deferredPrompt = null;
        const banner = document.getElementById('edusfera-pwa-install-banner');
        const installBtn = document.getElementById('ed-pwa-install-btn');
        const dismissBtn = document.getElementById('ed-pwa-dismiss-btn');

        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;

            if (banner) {
                banner.style.display = 'block';
                banner.setAttribute('aria-hidden', 'false');
            }
        });

        if (installBtn) {
            installBtn.addEventListener('click', async function() {
                if (!deferredPrompt) return;
                banner.style.display = 'none';
                banner.setAttribute('aria-hidden', 'true');
                deferredPrompt.prompt();
                const choiceResult = await deferredPrompt.userChoice;
                if (choiceResult.outcome === 'accepted') {
                    console.log('[Edusfera PWA] Пользователь установил приложение.');
                }
                deferredPrompt = null;
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', function() {
                if (banner) {
                    banner.style.display = 'none';
                    banner.setAttribute('aria-hidden', 'true');
                }
                localStorage.setItem('edusfera_pwa_dismissed', Date.now().toString());
            });
        }

        window.addEventListener('appinstalled', function() {
            if (banner) {
                banner.style.display = 'none';
            }
            deferredPrompt = null;
            console.log('[Edusfera PWA] Установка завершена.');
        });

        // 4. iOS Safari Detection & Helper Prompt
        const isIos = /iPhone|iPad|iPod/.test(navigator.userAgent) && !window.MSStream;
        const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome|CriOS|FxiOS|EdgiOS/.test(navigator.userAgent);

        if (isIos && isSafari && !window.navigator.standalone) {
            const iosBanner = document.getElementById('edusfera-pwa-ios-banner');
            const iosDismissBtn = document.getElementById('edusfera-pwa-ios-dismiss-btn');

            // Show after a subtle delay (4 seconds) so as not to interrupt immediate user orientation
            setTimeout(function() {
                if (iosBanner && !localStorage.getItem('edusfera_pwa_dismissed')) {
                    iosBanner.style.display = 'block';
                    iosBanner.setAttribute('aria-hidden', 'false');
                }
            }, 4000);

            if (iosDismissBtn) {
                iosDismissBtn.addEventListener('click', function() {
                    if (iosBanner) {
                        iosBanner.style.display = 'none';
                        iosBanner.setAttribute('aria-hidden', 'true');
                    }
                    localStorage.setItem('edusfera_pwa_dismissed', Date.now().toString());
                });
            }
        }

        // 5. App Badging Sync (Unread messages + upcoming lessons)
        async function syncPwaBadge() {
            if (!('setAppBadge' in navigator)) return;
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || !!navigator.standalone;
            const hasAuthUser = typeof window !== 'undefined' && !!window.EDUSFERA_USER;
            if (!isStandalone && !hasAuthUser) return;

            try {
                const res = await fetch('/api/pwa/badge-count', {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                const count = parseInt(data.count, 10) || 0;
                if (count > 0) {
                    await navigator.setAppBadge(count);
                } else {
                    await navigator.clearAppBadge();
                }
            } catch (e) {}
        }

        // Defer execution off the critical rendering path
        if ('requestIdleCallback' in window) {
            window.addEventListener('load', function() {
                requestIdleCallback(function() {
                    setTimeout(syncPwaBadge, 4000);
                });
            });
        } else {
            window.addEventListener('load', function() {
                setTimeout(syncPwaBadge, 4000);
            });
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') syncPwaBadge();
        });
        setInterval(syncPwaBadge, 120000);

        // 6. Global Web Push Notification Helper
        window.EdusferaPush = {
            async requestPermissionAndSubscribe() {
                if (!('Notification' in window) || !('serviceWorker' in navigator)) {
                    return false;
                }
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') return false;

                try {
                    const reg = await navigator.serviceWorker.ready;
                    let sub = await reg.pushManager.getSubscription();
                    if (!sub) {
                        sub = await reg.pushManager.subscribe({
                            userVisibleOnly: true
                        });
                    }
                    if (sub) {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        await fetch('/api/pwa/subscribe', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(sub)
                        });
                        return true;
                    }
                } catch (e) {
                    console.warn('[PWA Push] Subscription error:', e);
                }
                return false;
            }
        };
    })();
</script>
