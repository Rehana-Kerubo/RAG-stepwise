const CACHE_NAME = 'stepwise-v14';
// Pages are cached as they are visited (see fetch below); the shell is only static files.
const SHELL = [
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/apple-touch-icon.png',
  '/js/sync-status.js'
];

// Install: cache the app shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL))
  );
  self.skipWaiting();
});

// Activate: remove old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Fetch: network-first, fall back to cache. API and wizard data calls are never cached.
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  if (req.method !== 'GET' || url.pathname.startsWith('/api/') || url.pathname.startsWith('/wizard/')) return;

  event.respondWith(
    fetch(req, req.mode === 'navigate' ? { cache: 'no-store' } : undefined)
      .then((res) => {
        // Keep good pages (and redirects) for offline use, never error pages
        if (res.status < 400) {
          const copy = res.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
        }
        return res;
      })
      .catch(() => caches.match(req))
  );
});