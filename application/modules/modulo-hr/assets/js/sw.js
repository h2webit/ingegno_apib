// sw.js
const CACHE_NAME = 'timbratore-cache-v1';
const urlsToCache = [
    "../pwa/manifest.json",
    '../../../modulo-hr/qrcode/pwa', // `start_url` nel manifest
    '../../../modulo-hr/qrcode/timbratore',
    // Altre risorse JS, CSS e immagini da mettere in cache
    "./sw.js",
    "./scan_badge.js",
    "./html5-qrcode.min.js",
    "./toastr.min.js",
    "../pwa/icon.png",
    "../audio/error.mp3",
    "../audio/success.mp3",
    "../css/toastr.min.css",
    "../images/logo_ingegno.png",
    // Risorse esterne
    "https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap-theme.min.css",
    "https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css",
    "https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js",
    "https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js",
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css",
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
            // Restituisci la risorsa dalla cache se esiste
            if (response) {
                return response;
            }

            // Prova a recuperare la risorsa dalla rete e mettila in cache
            return fetch(event.request).then(function(networkResponse) {
                if (!networkResponse || networkResponse.status !== 200 || (networkResponse.type !== 'basic' && networkResponse.type !== 'cors')) {
                    return networkResponse;
                }

                // Clona la risposta e mettila in cache
                var responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(event.request, responseToCache);
                });

                return networkResponse;
            }).catch(function() {
                // Se la rete non è disponibile e non troviamo la risorsa nella cache
                return caches.match('offline.html'); // Mostra una pagina di fallback se esiste
            });
        })
    );
});

