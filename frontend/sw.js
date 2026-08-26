const CACHE_NAME = "cms-v4";
const STATIC_ASSETS = [
  "./index.html",
  "./landing.html",
  "./css/style.css",
  "./js/api.js",
  "./js/demo-data.js",
  "./js/i18n.js",
  "./retail/rt-style.css",
  "./retail/calculator.js",
  "./icons/logo.png"
];

self.addEventListener("install", e => {
  e.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener("activate", e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", e => {
  const url = new URL(e.request.url);

  // Never cache API calls — always go to network
  if (url.pathname.startsWith("/api/") || url.hostname !== self.location.hostname) {
    e.respondWith(fetch(e.request));
    return;
  }

  // Static assets: cache first, fallback to network
  e.respondWith(
    caches.match(e.request).then(cached => {
      const networkFetch = fetch(e.request).then(res => {
        if (res && res.status === 200) {
          const clone = res.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
        }
        return res;
      }).catch(() => cached);
      return cached || networkFetch;
    })
  );
});
