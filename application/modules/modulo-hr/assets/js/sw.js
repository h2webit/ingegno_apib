// sw.js
const CACHE_NAME = 'timbratore-cache-v1';
const urlsToCache = [
    /* './',
    'index.html',
    'manifest.json',*/
    "../pwa/manifest.json",
    '../../../modulo-hr/qrcode/pwa', // Assicurati che questo corrisponda al tuo `start_url` nel manifest
    // Aggiungi altre risorse necessarie, ad esempio CSS, immagini, ecc.
    "./sw.js",
    "./scan_badge.js",
    "./html5-qrcode.min.js",
    "./toastr.min.js",
    "../pwa/icon.png",
    "../audio/error.mp3",
    "../audio/success.mp3",
    "../css/toastr.min.css",
    "../images/logo_ingegno.png",
];

self.addEventListener('install', function(event) {
    // Cache static assets
    /* event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(urlsToCache);
        })
        .catch(function(error) {
            console.error('Errore durante la cache delle risorse:', error);
        })
    ); */
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            // Usa un ciclo per aggiungere ogni risorsa singolarmente e gestire gli errori
            return Promise.all(
                urlsToCache.map(url => {
                    return cache.add(url).catch(err => {
                        console.error('Errore durante la cache della risorsa:', url, err);
                    });
                })
            );
        }).catch(function(error) {
            console.error('Errore durante l\'apertura della cache:', error);
        })
    );
});

self.addEventListener('activate', function(event) {
    // Remove old caches
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cache) {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
});


self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request).then(function(response) {
            // Se la risorsa è nella cache, restituiscila
            if (response) {
                return response;
            }

            // Altrimenti, prova a recuperarla dalla rete e mettila in cache
            return fetch(event.request).then(function(networkResponse) {
                // Verifica che la risposta sia valida
                if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                    return networkResponse;
                }

                // Clona la risposta per metterla in cache
                var responseToCache = networkResponse.clone();

                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(event.request, responseToCache);
                });

                return networkResponse;
            }).catch(function() {
                // Fallback se la risorsa non è disponibile nella cache e la rete non è raggiungibile
                console.log('Risorsa non trovata nella cache e non disponibile in rete:', event.request.url);
            });
        })
    );
});
