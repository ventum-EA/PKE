/**
 * Šaha Analīzes Platforma — Service Worker
 *
 * Caching strategy:
 *   - Navigation (HTML): network-first, cached fallback. Ensures new deploys
 *     load immediately but offline users still see the last-known UI.
 *   - Static assets (/build/*, icons, manifest): cache-first. These are
 *     fingerprinted by Vite so cache can be long-lived.
 *   - Stockfish WASM (CDN): cache-first with 30-day TTL. The biggest asset
 *     on the site, ~1.2 MB. Caching it means puzzles and endgame pages work
 *     offline after the first visit.
 *   - API requests (/api/*): never cached. Always go to the network so the
 *     user doesn't see stale games or scores.
 */

const CACHE_VERSION = 'v1';
const STATIC_CACHE = `pke-static-${CACHE_VERSION}`;
const RUNTIME_CACHE = `pke-runtime-${CACHE_VERSION}`;

const PRECACHE_URLS = [
    '/',
    '/manifest.webmanifest',
    '/icon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== STATIC_CACHE && key !== RUNTIME_CACHE)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Never cache anything that isn't GET
    if (request.method !== 'GET') return;

    // Never cache API requests
    if (url.pathname.startsWith('/api/')) return;

    // Never cache the CSRF endpoint
    if (url.pathname === '/sanctum/csrf-cookie') return;

    // Stockfish WASM from CDN — cache-first, long-lived
    if (url.hostname === 'cdnjs.cloudflare.com' && url.pathname.includes('stockfish')) {
        event.respondWith(cacheFirst(request, RUNTIME_CACHE));
        return;
    }

    // HTML navigation — network-first with cache fallback
    if (request.mode === 'navigate') {
        event.respondWith(networkFirst(request, STATIC_CACHE));
        return;
    }

    // Built assets and other static resources — cache-first
    if (url.pathname.startsWith('/build/') || url.pathname.match(/\.(js|css|svg|png|webp|woff2?)$/)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }
});

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response && response.status === 200) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (err) {
        return cached || Response.error();
    }
}

async function networkFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    try {
        const response = await fetch(request);
        if (response && response.status === 200) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (err) {
        const cached = await cache.match(request);
        return cached || cache.match('/') || Response.error();
    }
}
