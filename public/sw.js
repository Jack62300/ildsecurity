

/**************************************************** WORKBOX IMPORT ****************************************************/
// The configuration is set to use Workbox
// The following code will import Workbox from CDN or public URL
// Import from public URL

importScripts('/workbox/workbox-sw.js');
workbox.setConfig({modulePathPrefix: '/workbox'});
/**************************************************** END WORKBOX IMPORT ****************************************************/






/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: CacheFirst
// Match: ({url}) => url.pathname.startsWith('/assets')
// Cache Name: assets
// Enabled: 1
// Needs Workbox: 1
// Method: 

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed
const cache_0_0 = new workbox.strategies.CacheFirst({
  cacheName: 'assets',plugins: [new workbox.expiration.ExpirationPlugin({
    "maxEntries": 132,
    "maxAgeSeconds": 31536000
})]
});
workbox.routing.registerRoute(({url}) => url.pathname.startsWith('/assets'),cache_0_0);
self.addEventListener('install', event => {
  const done = [
    "/assets/@spomky-labs/pwa-bundle/backgroundsync-form_controller-ynS5Onz.js",
    "/assets/@spomky-labs/pwa-bundle/connection-status_controller-6uY2aFO.js",
    "/assets/@spomky-labs/pwa-bundle/prefetch-on-demand_controller-e2Rt_GG.js",
    "/assets/@spomky-labs/pwa-bundle/sync-broadcast_controller-2-cTsg3.js",
    "/assets/@symfony/ux-turbo/turbo_controller-8wQNi2p.js",
    "/assets/@symfony/ux-turbo/turbo_stream_controller-LcKyCZE.js",
    "/assets/@symfony/stimulus-bundle/controllers-rSGaVI7.js",
    "/assets/@symfony/stimulus-bundle/loader-V1GtHuK.js",
    "/assets/app-XrWhsJK.js",
    "/assets/bootstrap-xCO4u8H.js",
    "/assets/controllers/csrf_protection_controller-qtTFcyL.js",
    "/assets/controllers/hello_controller-VYgvytJ.js",
    "/assets/styles/admin/extensions-lZIjLAH.css",
    "/assets/styles/admin/fields/client_photos-Vnox2ih.css",
    "/assets/styles/admin/fuel/form-uGdIHBR.css",
    "/assets/styles/admin/fuel/index-SKxspoW.css",
    "/assets/styles/admin/fuel/show-LRuN5zn.css",
    "/assets/styles/admin/vehicles/vehicle_form-4_2kniu.css",
    "/assets/styles/admin/vehicles/vehicle_index-R7NKWLD.css",
    "/assets/styles/agences/form-p2wAvWA.css",
    "/assets/styles/agences/index-rCanefw.css",
    "/assets/styles/app-5erhJ66.css",
    "/assets/styles/base-ZnhzpPg.css",
    "/assets/styles/bundles/easyadminbundle/crud/index-Mbb66R0.css",
    "/assets/styles/bundles/easyadminbundle/layout-Dv78rwX.css",
    "/assets/styles/bundles/easyadminbundle/menu-qpnKLEV.css",
    "/assets/styles/client-form-pm7yNOa.css",
    "/assets/styles/client-show-BnicDxG.css",
    "/assets/styles/clients/form-yvuYYrl.css",
    "/assets/styles/clients/show-nRQjJ5p.css",
    "/assets/styles/clients/_cards-hvQEmgB.css",
    "/assets/styles/custom-4g1b5o2.css",
    "/assets/styles/denied-JEa8Zih.css",
    "/assets/styles/devices/index-r0wstFV.css",
    "/assets/styles/email/trusted_device_approval-_Yuu726.css",
    "/assets/styles/errors/denied-gi9s2NT.css",
    "/assets/styles/export/index-DGwvCqR.css",
    "/assets/styles/fleet/fill_form-y4stNdT.css",
    "/assets/styles/fleet/index-HUCl4Qv.css",
    "/assets/styles/home/index-K_O10qX.css",
    "/assets/styles/index/index-sRcvQcd.css",
    "/assets/styles/intervention/edit-ilOQT9Q.css",
    "/assets/styles/intervention/index-eQ4PQPF.css",
    "/assets/styles/intervention/new-Qgw6k5i.css",
    "/assets/styles/intervention/print-VVQpqiC.css",
    "/assets/styles/intervention/show-6edTgGt.css",
    "/assets/styles/intervention/_delete_form-aa1efEI.css",
    "/assets/styles/intervention/_form-hXLjcVY.css",
    "/assets/styles/interventions-I2fZ_K7.css",
    "/assets/styles/organismes/form-EPi7GhU.css",
    "/assets/styles/organismes/index-QDCGd8M.css",
    "/assets/styles/phone_category/edit-uBrT4BW.css",
    "/assets/styles/phone_category/index-_CkYQFp.css",
    "/assets/styles/phone_category/new-A52O7nM.css",
    "/assets/styles/phone_category/_form-QKwcb51.css",
    "/assets/styles/phone_number/edit-maoG0wF.css",
    "/assets/styles/phone_number/index-a8zrU1T.css",
    "/assets/styles/phone_number/new-AKZPdPA.css",
    "/assets/styles/phone_number/_form-oGh-Ih7.css",
    "/assets/styles/require/navbar-8x0gKgv.css",
    "/assets/styles/security/device_approve_result-96SLujR.css",
    "/assets/styles/security/login-hKXOStv.css",
    "/assets/styles/users/form-MLc-XQO.css",
    "/assets/styles/users/index-sGrAw98.css",
    "/assets/vendor/@hotwired/stimulus/stimulus.index-S4zNcea.js",
    "/assets/vendor/@hotwired/turbo/turbo.index-pT15T6h.js"
].map(
    path =>
      cache_0_0.handleAll({
        event,
        request: new Request(path),
      })[1]
  );
  event.waitUntil(Promise.all(done));
});

/**************************************************** END CACHE STRATEGY ****************************************************/





/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: CacheFirst
// Match: ({request}) => request.destination === 'font'
// Cache Name: fonts
// Enabled: 1
// Needs Workbox: 1
// Method: GET

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed
const cache_2_0 = new workbox.strategies.CacheFirst({
  cacheName: 'fonts',plugins: [new workbox.cacheableResponse.CacheableResponsePlugin({
    "statuses": [
        0,
        200
    ]
}), new workbox.expiration.ExpirationPlugin({
    "maxEntries": 60,
    "maxAgeSeconds": 31536000
})]
});
workbox.routing.registerRoute(({request}) => request.destination === 'font',cache_2_0,'GET');
/**************************************************** END CACHE STRATEGY ****************************************************/





/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: StaleWhileRevalidate
// Match: ({url}) => url.origin === 'https://fonts.googleapis.com'
// Cache Name: google-fonts-stylesheets
// Enabled: 1
// Needs Workbox: 1
// Method: 

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed
const cache_3_0 = new workbox.strategies.StaleWhileRevalidate({
  cacheName: 'google-fonts-stylesheets',plugins: []
});
workbox.routing.registerRoute(({url}) => url.origin === 'https://fonts.googleapis.com',cache_3_0);
/**************************************************** END CACHE STRATEGY ****************************************************/





/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: CacheFirst
// Match: ({url}) => url.origin === 'https://fonts.gstatic.com'
// Cache Name: google-fonts-webfonts
// Enabled: 1
// Needs Workbox: 1
// Method: 

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed
const cache_3_1 = new workbox.strategies.CacheFirst({
  cacheName: 'google-fonts-webfonts',plugins: [new workbox.cacheableResponse.CacheableResponsePlugin({
    "statuses": [
        0,
        200
    ]
}), new workbox.expiration.ExpirationPlugin({
    "maxEntries": 30,
    "maxAgeSeconds": 31536000
})]
});
workbox.routing.registerRoute(({url}) => url.origin === 'https://fonts.gstatic.com',cache_3_1);
/**************************************************** END CACHE STRATEGY ****************************************************/





/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: CacheFirst
// Match: ({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('/assets'))
// Cache Name: images
// Enabled: 1
// Needs Workbox: 1
// Method: 

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed
const cache_4_0 = new workbox.strategies.CacheFirst({
  cacheName: 'images',plugins: []
});
workbox.routing.registerRoute(({request, url}) => (request.destination === 'image' && !url.pathname.startsWith('/assets')),cache_4_0);
/**************************************************** END CACHE STRATEGY ****************************************************/





/**************************************************** CACHE STRATEGY ****************************************************/
// Strategy: StaleWhileRevalidate
// Match: ({url}) => '/site.webmanifest' === url.pathname
// Cache Name: manifest
// Enabled: 1
// Needs Workbox: 1
// Method: 

// 1. Creation of the Workbox Cache Strategy object
// 2. Register the route with the Workbox Router
// 3. Add the assets to the cache when the service worker is installed
const cache_5_0 = new workbox.strategies.StaleWhileRevalidate({
  cacheName: 'manifest',plugins: []
});
workbox.routing.registerRoute(({url}) => '/site.webmanifest' === url.pathname,cache_5_0);
/**************************************************** END CACHE STRATEGY ****************************************************/




/**************************************************** CACHE CLEAR ****************************************************/
// The configuration is set to clear the cache on each install event
// The following code will remove all the caches
self.addEventListener("install", function (event) {
    event.waitUntil(caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});
/**************************************************** END CACHE CLEAR ****************************************************/



