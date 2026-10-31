const CACHE_NAME = 'novateam-v24';

/* Solo activos estáticos: las páginas son dinámicas y dependen de la sesión. */
const ASSETS = [
  '/assets/css/app.css?v=13',
  '/assets/css/landing.css?v=1',
  '/assets/css/theme-nova.css?v=4',
  '/assets/js/tab.js?v=3',
  '/assets/js/notificaciones.js?v=3',
  '/assets/js/quiz.js?v=3',
  '/assets/js/app.js?v=3',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then(c => Promise.all(ASSETS.map(url => c.add(url).catch(() => null))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function isStatic(url) {
  return url.pathname.startsWith('/assets/') ||
    url.hostname.includes('jsdelivr.net');
}

self.addEventListener('fetch', e => {
  const req = e.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);

  if (req.mode === 'navigate') {
    // Red primero; sin conexión, mensaje offline (no se cachean páginas con sesión).
    e.respondWith(
      fetch(req).catch(() => new Response(
        '<!doctype html><meta charset="utf-8"><title>Sin conexión</title>' +
        '<body style="font-family:system-ui;padding:2rem;text-align:center">' +
        '<h1>Sin conexión</h1><p>NovaTeam necesita internet para cargar tus guías.</p></body>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
      ))
    );
    return;
  }

  if (isStatic(url)) {
    // Cache primero y actualización en segundo plano.
    e.respondWith(
      caches.match(req).then(cached => {
        const network = fetch(req).then(resp => {
          if (resp && resp.status === 200) {
            const clone = resp.clone();
            caches.open(CACHE_NAME).then(c => c.put(req, clone));
          }
          return resp;
        }).catch(() => cached);
        return cached || network;
      })
    );
  }
});
