// Basic PWA service worker for the driver app shell.
// Full offline action-queueing (IndexedDB + background sync) is documented as a
// fast-follow item in docs/06-API-Design.md § 6.5 — this MVP shell only caches the
// static shell so the app opens instantly on repeat visits; it does not yet queue
// pickup/deliver/fail actions performed while offline.
const CACHE_NAME = 'shipping-driver-shell-v1';
const SHELL_ASSETS = ['/driver', '/manifest.json'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS)).catch(() => {}));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
