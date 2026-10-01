<!-- PWA Manifest & Mobile Meta Tags -->
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0C0A14">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="Edusfera">

<!-- Apple iOS Safari PWA Support -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Edusfera">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ asset('icons/icon-192x192.png') }}">
<link rel="apple-touch-icon" sizes="512x512" href="{{ asset('icons/icon-512x512.png') }}">

<!-- Microsoft Windows Tile -->
<meta name="msapplication-TileColor" content="#0C0A14">
<meta name="msapplication-TileImage" content="{{ asset('icons/icon-192x192.png') }}">

<!-- Native PWA Ergonomics & Safe Area -->
<style>
:root {
  --sat: env(safe-area-inset-top, 0px);
  --sar: env(safe-area-inset-right, 0px);
  --sab: env(safe-area-inset-bottom, 0px);
  --sal: env(safe-area-inset-left, 0px);
}
@media (display-mode: standalone) {
  body {
    overscroll-behavior-y: contain;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
  }
  .pwa-safe-top {
    padding-top: max(16px, env(safe-area-inset-top, 16px)) !important;
  }
  .pwa-safe-bottom {
    padding-bottom: max(16px, env(safe-area-inset-bottom, 16px)) !important;
  }
}
</style>

<!-- Native Haptic Feedback Engine -->
<script>
window.EdusferaHaptics = {
  isSupported: typeof navigator !== 'undefined' && 'vibrate' in navigator,
  tap: function() {
    if (this.isSupported) {
      try { navigator.vibrate(10); } catch(e) {}
    }
  },
  selection: function() {
    if (this.isSupported) {
      try { navigator.vibrate(15); } catch(e) {}
    }
  },
  success: function() {
    if (this.isSupported) {
      try { navigator.vibrate([20, 50, 30]); } catch(e) {}
    }
  },
  warning: function() {
    if (this.isSupported) {
      try { navigator.vibrate([35, 40, 35]); } catch(e) {}
    }
  },
  error: function() {
    if (this.isSupported) {
      try { navigator.vibrate([60, 40, 60, 40, 80]); } catch(e) {}
    }
  }
};
document.addEventListener('click', function(e) {
  var target = e.target.closest('[data-haptic], button, .wb-btn, a.fi-btn');
  if (target) {
    var hapticType = target.getAttribute('data-haptic') || 'tap';
    if (window.EdusferaHaptics[hapticType]) {
      window.EdusferaHaptics[hapticType]();
    } else {
      window.EdusferaHaptics.tap();
    }
  }
}, { passive: true });
</script>
