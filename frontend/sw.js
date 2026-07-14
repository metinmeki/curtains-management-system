const CACHE_NAME = "cms-v1";
const ASSETS = [
  "./index.html",
  "./landing.html",
  "./css/style.css",
  "./js/api.js",
  "./js/demo-data.js",
  "./js/i18n.js"
];

self.addEventListener("install", e => {
  e.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS)));
});

self.addEventListener("fetch", e => {
  e.respondWith(
    caches.match(e.request).then(res => res || fetch(e.request))
  );
});
