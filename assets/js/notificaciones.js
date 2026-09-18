(function () {
  'use strict';

  // Solo estudiantes logueados tienen la campana de notificaciones.
  var bell = document.querySelector('a[href="notificaciones.php"]');
  if (!bell) return;

  var token = window.__NOVATEAM_TAB__ || '';
  if (!token) return;

  var MAX_TOASTS = 4;
  var TOAST_TTL = 8000;
  var cursor = null;
  var source = null;
  var fallbackTimer = null;

  function badges() {
    return Array.prototype.slice.call(document.querySelectorAll('.js-notif-badge'));
  }

  function setBadge(n) {
    var list = badges();
    if (!list.length) {
      var bells = document.querySelectorAll('a[href="notificaciones.php"]');
      Array.prototype.forEach.call(bells, function (a) {
        var b = document.createElement('span');
        b.className = 'badge bg-danger rounded-pill ms-1 js-notif-badge';
        b.style.cssText = 'font-size:.65rem;';
        a.appendChild(b);
        list.push(b);
      });
    }
    list.forEach(function (b) {
      if (n > 0) {
        b.textContent = n;
        b.style.display = '';
        b.classList.remove('d-none');
      } else {
        b.style.display = 'none';
      }
    });
  }

  function pageUrl(path) {
    var sep = path.indexOf('?') !== -1 ? '&' : '?';
    return path + sep + 'tab=' + encodeURIComponent(token);
  }

  function formatTime(iso) {
    if (!iso) return '';
    var d = new Date(String(iso).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return 'ahora';
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + ' h';
    return d.toLocaleDateString('es-CO', { day: 'numeric', month: 'short' });
  }

  function removeToast(elm) {
    if (elm.hasAttribute('data-closing')) return;
    elm.setAttribute('data-closing', '1');
    if (elm._ttl) clearTimeout(elm._ttl);
    elm.classList.add('out');
    setTimeout(function () {
      if (elm.parentNode) elm.parentNode.removeChild(elm);
    }, 350);
  }

  function toastContainer() {
    var c = document.getElementById('notif-toasts');
    if (!c) {
      c = document.createElement('div');
      c.id = 'notif-toasts';
      document.body.appendChild(c);
    }
    return c;
  }

  function showToast(item) {
    var container = toastContainer();

    var toast = document.createElement('div');
    toast.className = 'ntoast';

    var avatar = document.createElement('div');
    avatar.className = 'ntoast-avatar';
    avatar.innerHTML = '<i class="bi bi-bell"></i>';

    var body = document.createElement('div');
    body.className = 'ntoast-body';

    var app = document.createElement('div');
    app.className = 'ntoast-title';
    app.textContent = 'NovaTeam';

    var heading = document.createElement('div');
    heading.className = 'ntoast-heading';
    heading.textContent = item.titulo || 'Nueva notificación';

    var msg = document.createElement('div');
    msg.className = 'ntoast-msg';
    msg.textContent = item.mensaje || '';

    var time = document.createElement('div');
    time.className = 'ntoast-time';
    time.textContent = formatTime(item.fecha_creacion);

    body.appendChild(app);
    body.appendChild(heading);
    body.appendChild(msg);
    body.appendChild(time);

    var close = document.createElement('button');
    close.className = 'ntoast-close';
    close.type = 'button';
    close.title = 'Cerrar';
    close.innerHTML = '&times;';

    toast.appendChild(avatar);
    toast.appendChild(body);
    toast.appendChild(close);

    toast.addEventListener('click', function (ev) {
      if (ev.target === close) {
        removeToast(toast);
        return;
      }
      try { location.href = pageUrl('notificaciones.php'); } catch (e) {}
    });

    if (item.id_guia) {
      var go = document.createElement('a');
      go.className = 'ntoast-link';
      go.href = '#';
      go.textContent = 'Ir a la guía';
      go.addEventListener('click', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        try { location.href = pageUrl('guia.php?id=' + encodeURIComponent(item.id_guia)); } catch (e) {}
      });
      body.appendChild(go);
    }

    container.appendChild(toast);
    while (container.children.length > MAX_TOASTS) {
      removeToast(container.firstChild);
    }

    toast._ttl = setTimeout(function () { removeToast(toast); }, TOAST_TTL);
  }

  function playChime() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      var ctx = new Ctx();
      if (ctx.state === 'suspended') { ctx.resume().catch(function () {}); }
      var now = ctx.currentTime;
      var tones = [880, 1318.5]; // chime alegre estilo WhatsApp
      tones.forEach(function (f, i) {
        var o = ctx.createOscillator();
        var g = ctx.createGain();
        o.type = 'sine';
        o.frequency.value = f;
        var t0 = now + i * 0.15;
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(0.2, t0 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.4);
        o.connect(g);
        g.connect(ctx.destination);
        o.start(t0);
        o.stop(t0 + 0.45);
      });
    } catch (e) {}
  }

  function onReady(ev) {
    try {
      var d = JSON.parse(ev.data);
      if (cursor === null) cursor = d.maxId;
      if (typeof d.count === 'number') setBadge(d.count);
    } catch (e) {}
  }

  function onNotif(ev) {
    try {
      var d = JSON.parse(ev.data);
      if (typeof d.count === 'number') setBadge(d.count);
      if (Array.isArray(d.items)) {
        d.items.forEach(function (it) {
          var id = parseInt(it.id_notificacion, 10);
          if (!isNaN(id) && cursor !== null && id <= cursor) return; // dedupe
          if (!isNaN(id) && id > (cursor || 0)) cursor = id;
          showToast(it);
        });
        playChime();
        try { if (navigator.vibrate) navigator.vibrate(200); } catch (e) {}
      }
    } catch (e) {}
  }

  function pollUnread() {
    fetch('api/notificaciones.php?accion=unread')
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.ok) setBadge(d.count); })
      .catch(function () {});
  }

  function startFallback() {
    if (fallbackTimer) return;
    fallbackTimer = setInterval(pollUnread, 30000);
  }

  function start() {
    if (!('EventSource' in window)) {
      startFallback();
      return;
    }
    source = new EventSource('api/notificaciones_sse.php?tab=' + encodeURIComponent(token));
    source.addEventListener('ready', onReady);
    source.addEventListener('notificacion', onNotif);
    source.addEventListener('cerrar', function () { source.close(); startFallback(); });
    source.onerror = function () {
      if (source && source.readyState === EventSource.CLOSED) {
        startFallback();
      }
    };
  }

  start();
})();