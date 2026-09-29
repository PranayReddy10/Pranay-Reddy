/* DevLedger service worker: offline shell + last-seen pages. */
const VERSION = 'v4';
const STATIC_CACHE = `devledger-static-${VERSION}`;
const PAGE_CACHE = `devledger-pages-${VERSION}`;
const PRECACHE = [
    '/offline',
    '/css/app.css',
    '/js/app.js',
    '/manifest.webmanifest',
    '/favicon.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => ![STATIC_CACHE, PAGE_CACHE].includes(k)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Logging out wipes cached pages so financial data doesn't linger on the device.
self.addEventListener('message', (event) => {
    if (event.data === 'clear-pages') {
        event.waitUntil(caches.delete(PAGE_CACHE));
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // Pages: network first, fall back to the last copy, then the offline screen.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const cacheable = response.ok && !response.redirected && !url.pathname.startsWith('/login')
                        && !url.pathname.endsWith('/export');
                    if (cacheable) {
                        const copy = response.clone();
                        caches.open(PAGE_CACHE).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(request).then((hit) => hit || caches.match('/offline')))
        );
        return;
    }

    // Static assets: stale-while-revalidate.
    if (/\.(css|js|png|svg|webmanifest|woff2?)$/.test(url.pathname)) {
        event.respondWith(
            caches.open(STATIC_CACHE).then((cache) =>
                cache.match(request).then((hit) => {
                    const network = fetch(request).then((response) => {
                        if (response.ok) cache.put(request, response.clone());
                        return response;
                    }).catch(() => hit || cache.match(request, { ignoreSearch: true }));
                    return hit || network;
                })
            )
        );
    }
});
