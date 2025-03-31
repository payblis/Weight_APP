const CACHE_NAME = 'myfity-v1';
const ASSETS_TO_CACHE = [
    './',
    './index.php',
    './dashboard.php',
    './food-log.php',
    './exercise-log.php',
    './weight-log.php',
    './profile.php',
    './assets/css/style.css',
    './manifest.json',
    './assets/icons/icon-72x72.png',
    './assets/icons/icon-96x96.png',
    './assets/icons/icon-128x128.png',
    './assets/icons/icon-144x144.png',
    './assets/icons/icon-152x152.png',
    './assets/icons/icon-192x192.png',
    './assets/icons/icon-384x384.png',
    './assets/icons/icon-512x512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Install');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Pre-caching offline page');
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .catch((error) => {
                console.error('[ServiceWorker] Pre-cache error:', error);
            })
    );
    self.skipWaiting();
});

// Activation du Service Worker
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[ServiceWorker] Removing old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Interception des requêtes
self.addEventListener('fetch', (event) => {
    console.log('[ServiceWorker] Fetch', event.request.url);
    
    // Stratégie Cache First pour les assets statiques
    if (event.request.url.match(/\.(css|js|png|jpg|jpeg|gif|ico)$/)) {
        event.respondWith(
            caches.match(event.request)
                .then((response) => {
                    if (response) {
                        console.log('[ServiceWorker] Return from cache:', event.request.url);
                        return response;
                    }
                    
                    console.log('[ServiceWorker] Fetch from network:', event.request.url);
                    return fetch(event.request)
                        .then((response) => {
                            return caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, response.clone());
                                    return response;
                                });
                        });
                })
                .catch(() => {
                    // Retourne une image par défaut si offline
                    if (event.request.url.match(/\.(png|jpg|jpeg|gif|ico)$/)) {
                        return caches.match('./assets/icons/icon-offline.png');
                    }
                })
        );
        return;
    }

    // Stratégie Network First pour les autres requêtes
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Mettre en cache la réponse si c'est une requête GET
                if (event.request.method === 'GET') {
                    return caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, response.clone());
                            return response;
                        });
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request)
                    .then((response) => {
                        if (response) {
                            return response;
                        }
                        // Si la page n'est pas en cache, retourner la page offline
                        if (event.request.mode === 'navigate') {
                            return caches.match('./offline.html');
                        }
                        return new Response('Offline');
                    });
            })
    );
}); 