const CACHE_NAME = 'fmp-scan-cache-v1';
const RUNTIME_CDN = [
  'https://unpkg.com/html5-qrcode'
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    // Precache minimal shell; CDN will be cached on first successful fetch
    await cache.addAll([
      // Add a tiny offline stub so SW activates cleanly
      new Request('./sw.js', {cache: 'reload'})
    ]);
  })());
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Cache-first for the html5-qrcode CDN resources
  if (RUNTIME_CDN.some(prefix => url.href.startsWith(prefix))) {
    event.respondWith((async () => {
      const cache = await caches.open(CACHE_NAME);
      const cached = await cache.match(event.request);
      if (cached) return cached;
      try {
        const resp = await fetch(event.request);
        // Only cache successful responses
        if (resp && resp.status === 200) {
          cache.put(event.request, resp.clone());
        }
        return resp;
      } catch (e) {
        // Offline and not cached: fail through
        return new Response('', { status: 503, statusText: 'Offline' });
      }
    })());
    return;
  }

  // Default: network falling back to cache (helps for static assets)
  event.respondWith((async () => {
    try {
      const resp = await fetch(event.request);
      return resp;
    } catch (e) {
      const cached = await caches.match(event.request);
      if (cached) return cached;
      throw e;
    }
  })());
});
