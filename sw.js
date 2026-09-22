const CACHE_NAME = 'novateam-v18';
const ASSETS = [
  '/',
  '/index.php',
  '/login.php',
  '/registro.php',
  '/estudiante.php',
  '/profesor.php',
  '/admin.php',
  '/descargar.php',
  '/tienda.php',
  '/estudiante.php',
  '/leaderboard.php',
  '/progreso.php',
  '/perfil.php',
  '/mensajes.php',
  '/notificaciones.php',
  '/recuperar.php',
  '/nueva-clave.php',
  '/alumnos.php',
  '/guia.php',
  '/crear-guia.php',
  '/editar-guia.php',
  '/assets/css/app.css?v=13',
  '/assets/js/tab.js?v=3',
  '/assets/js/notificaciones.js?v=2',
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
    caches.open(CACHE_NAME).then(c => c.addAll(ASSETS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  const isAsset =
    e.request.destination === 'style' ||
    e.request.destination === 'script' ||
    e.request.destination === 'image' ||
    e.request.destination === 'font' ||
    e.request.url.includes('/assets/');
  e.respondWith(
    fetch(e.request)
      .then(resp => {
        if (resp && resp.status === 200 && isAsset) {
          const clone = resp.clone();
          caches.open(CACHE_NAME).then(c => c.put(e.request, clone));
        }
        return resp;
      })
      .catch(() => caches.match(e.request))
  );
});