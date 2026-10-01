const CACHE_NAME = 'edusfera-pwa-v1.0.0';
const OFFLINE_URL = '/offline.html';

const STATIC_PRECACHE = [
  '/',
  '/offline.html',
  '/manifest.json',
  '/favicon.svg',
  '/apple-touch-icon.png',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/apple-touch-icon.png'
];

// Установка: кэшируем критические статические ресурсы
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_PRECACHE).catch((err) => {
        console.warn('[Edusfera PWA] Precache warning:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Активация: удаляем старые версии кэша
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Слушаем сообщения от клиента (принудительное обновление и установка бейджей)
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data && event.data.type === 'SET_BADGE') {
    const count = parseInt(event.data.count, 10) || 0;
    if (self.navigator && 'setAppBadge' in self.navigator) {
      if (count > 0) {
        self.navigator.setAppBadge(count).catch(() => {});
      } else {
        self.navigator.clearAppBadge().catch(() => {});
      }
    }
  }
});

// ─── Web Push Notifications ─────────────────────────────────────
self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = {
      title: 'Edusfera',
      body: event.data ? event.data.text() : 'Новое уведомление'
    };
  }

  const title = data.title || 'Edusfera';
  const options = {
    body: data.body || 'У вас новое уведомление в Edusfera',
    icon: data.icon || '/icons/icon-192x192.png',
    badge: '/icons/icon-192x192.png',
    tag: data.tag || 'edusfera-push',
    data: {
      url: data.url || '/admin',
    },
    vibrate: [100, 50, 100],
    actions: data.actions || [
      { action: 'open', title: 'Открыть' }
    ]
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// Клик по нативному уведомлению
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url)
    ? event.notification.data.url
    : '/admin';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes(targetUrl) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});


// Обработка запросов (Fetch)
self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Игнорируем не-GET запросы (POST формы, платежи, Livewire mutations)
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);

  // Игнорируем внешние запросы, платежные шлюзы, видеопотоки WebRTC, Livewire update endpoints
  if (
    url.origin !== self.location.origin ||
    url.pathname.startsWith('/livewire/') ||
    url.pathname.startsWith('/api/') ||
    url.pathname.includes('/checkout/') ||
    url.pathname.includes('/payments/') ||
    url.pathname.includes('/alfa-') ||
    url.searchParams.has('livewire')
  ) {
    return;
  }

  // 1. Навигация по страницам (HTML) — Network First с переходом в Offline Fallback
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((response) => {
          // Если получили валидный ответ, кэшируем копию
          if (response && response.status === 200) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return response;
        })
        .catch(async () => {
          const cachedResponse = await caches.match(req);
          if (cachedResponse) {
            return cachedResponse;
          }
          return caches.match(OFFLINE_URL);
        })
    );
    return;
  }

  // 2. Статические файлы (картинки, шрифты, CSS, JS) — Stale-While-Revalidate
  const isStatic =
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname.startsWith('/fonts/') ||
    url.pathname.startsWith('/logo/') ||
    url.pathname.match(/\.(css|js|woff2?|ttf|png|jpg|jpeg|svg|ico|webp)$/i);

  if (isStatic) {
    event.respondWith(
      caches.match(req).then((cachedResponse) => {
        const fetchPromise = fetch(req)
          .then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              const copy = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
            }
            return networkResponse;
          })
          .catch(() => cachedResponse);

        return cachedResponse || fetchPromise;
      })
    );
  }
});
