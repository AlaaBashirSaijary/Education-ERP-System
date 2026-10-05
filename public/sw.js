/*
 * Service worker for the school app.
 *
 *  - Page navigations go to the network; when the network is gone the cached /offline page is shown.
 *    Pages themselves are NEVER stored: they contain personal data about students and parents.
 *  - Fingerprinted build files (/build/assets/*) and the icons are cached for speed.
 *  - Everything else (Livewire updates, forms, CSV, receipts) goes straight to the network.
 *
 * Bump VERSION to drop old caches when this file or /offline changes.
 */
const VERSION = 'v1';
const CACHE = `school-static-${VERSION}`;
const OFFLINE_URL = '/offline';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/icons/icon-512.png', '/favicon.svg'];
const MAX_ASSETS = 80;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('school-') && k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

async function cacheFirst(request) {
    const cache = await caches.open(CACHE);
    const hit = await cache.match(request);
    if (hit) return hit;

    const response = await fetch(request);
    if (response.ok) {
        await cache.put(request, response.clone());
        const keys = await cache.keys();
        // keep the cache bounded: forget the oldest build files after many deployments
        keys.filter((k) => new URL(k.url).pathname.startsWith('/build/assets/')).slice(0, -MAX_ASSETS).forEach((k) => cache.delete(k));
    }
    return response;
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(cacheFirst(request));
    }
});
