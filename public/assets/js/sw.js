importScripts('https://storage.googleapis.com/workbox-cdn/releases/6.6.0/workbox-sw.js');

workbox.precaching.precacheAndRoute(self.__WB_MANIFEST || []);

workbox.routing.registerRoute(
  ({request}) => request.destination === 'style' || request.destination === 'script',
  new workbox.strategies.StaleWhileRevalidate()
);

workbox.routing.registerRoute(
  ({url}) => url.pathname.startsWith('/api/read/'),
  new workbox.strategies.NetworkFirst({ cacheName: 'api-read', networkTimeoutSeconds: 5 })
);

workbox.routing.registerRoute(
  ({request}) => request.destination === 'image',
  new workbox.strategies.CacheFirst({
    cacheName: 'images',
    plugins: [new workbox.expiration.ExpirationPlugin({maxEntries: 200, maxAgeSeconds: 60*60*24*7})]
  })
);

// Optionnel : file d’attente POST si offline (peut ne pas fonctionner en WebView)
const bgSync = new workbox.backgroundSync.BackgroundSyncPlugin('queue-write', {maxRetentionTime: 24*60});
workbox.routing.registerRoute(
  ({url}) => url.pathname.startsWith('/api/write/'),
  new workbox.strategies.NetworkOnly({plugins: [bgSync]}), 'POST'
);