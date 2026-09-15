(function () {
  'use strict';

  var KEY = 'novateam-tab';

  function randomToken() {
    var arr = new Uint8Array(16);
    var crypto = window.crypto || window.msCrypto;
    if (crypto && crypto.getRandomValues) {
      crypto.getRandomValues(arr);
    } else {
      for (var i = 0; i < arr.length; i++) {
        arr[i] = Math.floor(Math.random() * 256);
      }
    }
    var t = '';
    for (var j = 0; j < arr.length; j++) {
      t += ('0' + arr[j].toString(16)).slice(-2);
    }
    return t;
  }

  function getToken() {
    var t = window.__NOVATEAM_TAB__;
    if (t) return t;
    t = sessionStorage.getItem(KEY);
    if (!t || !/^[A-Za-z0-9_-]{16,64}$/.test(t)) {
      t = randomToken();
      sessionStorage.setItem(KEY, t);
    }
    window.__NOVATEAM_TAB__ = t;
    return t;
  }

  var token = getToken();

  // Si la URL no lleva el token, agrégalo (same-document) para conservarlo en recargas.
  try {
    if (location.search.indexOf('tab=') === -1) {
      var u = new URL(location.href);
      u.searchParams.set('tab', token);
      history.replaceState(null, '', u.toString().slice(u.origin.length));
    }
  } catch (e) {}

  function addTab(url) {
    try {
      var u = new URL(url, location.origin);
      if (u.origin !== location.origin) return url;
      u.searchParams.set('tab', token);
      return u.pathname + u.search + u.hash;
    } catch (e) {
      return url;
    }
  }

  // Reescribe enlaces internos al hacer clic.
  document.addEventListener('click', function (ev) {
    var t = ev.target;
    var a = t && t.closest ? t.closest('a[href]') : null;
    if (!a) return;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#') return;
    if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-no-tab')) return;
    var abs;
    try {
      abs = new URL(a.href);
    } catch (e) {
      return;
    }
    if (abs.origin !== location.origin) return;
    if (abs.protocol !== 'http:' && abs.protocol !== 'https:') return;
    if (abs.searchParams.get('tab') === token) return;
    ev.preventDefault();
    a.setAttribute('href', addTab(abs.href));
    location.href = a.getAttribute('href');
  });

  // Agrega el token como campo oculto a cada formulario.
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f || !f.matches || !f.matches('form')) return;
    if (f.querySelector('input[name="tab"]')) return;
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'tab';
    hidden.value = token;
    f.appendChild(hidden);
  });

  // Agrega el token a las peticiones fetch internas.
  var origFetch = window.fetch;
  if (typeof origFetch === 'function') {
    window.fetch = function (input, init) {
      if (typeof input === 'string' && input.indexOf('://') === -1) {
        var sep = input.indexOf('?') !== -1 ? '&' : '?';
        input = input + sep + 'tab=' + encodeURIComponent(token);
      }
      return origFetch.call(this, input, init);
    };
  }
})();