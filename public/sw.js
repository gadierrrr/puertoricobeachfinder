/**
 * Beach Finder Service Worker
 * Handles offline caching and background sync
 */

const CACHE_VERSION = 'v2.1.0';
const CACHE_NAME = `beach-finder-${CACHE_VERSION}`;
const DATA_CACHE_NAME = `beach-finder-data-${CACHE_VERSION}`;

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/',
    '/offline',
    '/manifest.json',
    '/assets/css/styles.css',
    '/assets/css/tailwind.min.css',
    '/assets/js/app.min.js',
    '/assets/js/map.js',
    '/assets/js/filters.js',
    '/assets/js/geolocation.js',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png'
];

// Max cache entries per cache type
const MAX_IMAGE_CACHE = 200;
const MAX_DATA_CACHE = 50;

// Install event - cache core assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_ASSETS))
            .catch((error) => {
                console.error('[SW] Precache failed:', error);
            })
    );
});

// Activate event - clean up old caches, enable navigation preload
self.addEventListener('activate', (event) => {
    const currentCaches = [CACHE_NAME, DATA_CACHE_NAME];

    event.waitUntil(
        (async () => {
            // Enable navigation preload if supported
            if (self.registration.navigationPreload) {
                await self.registration.navigationPreload.enable();
            }

            // Clean up old caches
            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames
                    .filter((name) => name.startsWith('beach-finder-') && !currentCaches.includes(name))
                    .map((name) => caches.delete(name))
            );

            // Take control of all pages immediately
            await self.clients.claim();
        })()
    );
});

/**
 * Trim a cache to a maximum number of entries (LRU eviction).
 */
async function trimCache(cacheName, maxEntries) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxEntries) {
        await cache.delete(keys[0]);
        return trimCache(cacheName, maxEntries);
    }
}

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip auth endpoints
    if (url.pathname.startsWith('/auth/') ||
        url.pathname.includes('login') ||
        url.pathname.includes('logout')) {
        return;
    }

    // API requests - Network first with data cache fallback
    if (url.pathname.startsWith('/api/')) {
        // For beach list/detail APIs, cache the response
        if (url.pathname.includes('beaches') || url.pathname.includes('beach-detail')) {
            event.respondWith(
                fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(DATA_CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                                trimCache(DATA_CACHE_NAME, MAX_DATA_CACHE);
                            });
                        }
                        return response;
                    })
                    .catch(() => {
                        // Try data cache for offline access
                        return caches.match(event.request)
                            .then((cached) => {
                                if (cached) return cached;
                                return new Response(
                                    JSON.stringify({ success: false, error: 'Offline - data not cached' }),
                                    { headers: { 'Content-Type': 'application/json' } }
                                );
                            });
                    })
            );
            return;
        }

        // Other API requests - network only
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return new Response(
                        JSON.stringify({ success: false, error: 'Offline' }),
                        { headers: { 'Content-Type': 'application/json' } }
                    );
                })
        );
        return;
    }

    // For navigation requests - use preload response if available
    if (event.request.mode === 'navigate') {
        event.respondWith(
            (async () => {
                try {
                    // Use navigation preload response if available
                    const preloadResponse = await event.preloadResponse;
                    if (preloadResponse) {
                        // Cache the preloaded response
                        const clone = preloadResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, clone);
                        });
                        return preloadResponse;
                    }

                    // Otherwise fetch normally
                    const response = await fetch(event.request);
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                } catch (error) {
                    // Try cache first
                    const cached = await caches.match(event.request);
                    if (cached) return cached;
                    // Fallback to offline page
                    return caches.match('/offline');
                }
            })()
        );
        return;
    }

    // For images - Cache first with network fallback
    if (event.request.destination === 'image') {
        event.respondWith(
            caches.match(event.request)
                .then((cached) => {
                    if (cached) return cached;

                    return fetch(event.request)
                        .then((response) => {
                            if (response.ok) {
                                const clone = response.clone();
                                caches.open(CACHE_NAME).then((cache) => {
                                    cache.put(event.request, clone);
                                    trimCache(CACHE_NAME, MAX_IMAGE_CACHE);
                                });
                            }
                            return response;
                        })
                        .catch(() => {
                            // Return placeholder for failed images
                            return new Response(
                                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="#e5e7eb" width="100" height="100"/><text x="50" y="55" text-anchor="middle" fill="#9ca3af" font-size="40">🏖️</text></svg>',
                                { headers: { 'Content-Type': 'image/svg+xml' } }
                            );
                        });
                })
        );
        return;
    }

    // For other assets (CSS, JS) - Stale while revalidate
    event.respondWith(
        caches.match(event.request)
            .then((cached) => {
                const fetchPromise = fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    })
                    .catch(() => cached);

                return cached || fetchPromise;
            })
    );
});

// Handle messages from the app
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data === 'clearCache') {
        caches.delete(CACHE_NAME);
    }
});

// Push notifications
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();

    event.waitUntil(
        self.registration.showNotification(data.title || 'Beach Finder', {
            body: data.body || '',
            icon: '/assets/icons/icon-192x192.png',
            badge: '/assets/icons/icon-96x96.png',
            data: data.url || '/'
        })
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data || '/')
    );
});
