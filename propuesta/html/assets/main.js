/* ==========================================================================
   HABLA DEPORTES — main.js
   JS mínimo vanilla: menú móvil, dropdowns, buscador, estado de radio "AL AIRE".
   Respeta prefers-reduced-motion (las animaciones se gobiernan por CSS).
   ========================================================================== */
(function () {
  'use strict';

  var doc = document;
  var body = doc.body;

  function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
  function $all(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }

  /* ----------------------------------------------------------------------
     1. HEADER — estado al hacer scroll
     ---------------------------------------------------------------------- */
  var header = $('.site-header');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ----------------------------------------------------------------------
     2. MENÚ MÓVIL (overlay)
     ---------------------------------------------------------------------- */
  var menu = $('#mobile-menu');
  var menuToggle = $('#nav-toggle');
  var menuClose = $('#mobile-menu-close');

  function openMenu() {
    if (!menu) return;
    menu.classList.add('is-open');
    menu.setAttribute('aria-hidden', 'false');
    if (menuToggle) menuToggle.setAttribute('aria-expanded', 'true');
    body.classList.add('no-scroll');
    var first = menu.querySelector('a, button');
    if (first) first.focus();
  }
  function closeMenu() {
    if (!menu) return;
    menu.classList.remove('is-open');
    menu.setAttribute('aria-hidden', 'true');
    if (menuToggle) { menuToggle.setAttribute('aria-expanded', 'false'); menuToggle.focus(); }
    body.classList.remove('no-scroll');
  }
  if (menuToggle) menuToggle.addEventListener('click', openMenu);
  if (menuClose) menuClose.addEventListener('click', closeMenu);

  /* submenús acordeón dentro del menú móvil */
  $all('.m-group-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var group = btn.closest('.m-group');
      var open = group.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  /* ----------------------------------------------------------------------
     3. DROPDOWNS (Equipos / Torneos) — desktop
     ---------------------------------------------------------------------- */
  var dropdowns = $all('.has-dropdown');

  function closeAllDropdowns(except) {
    dropdowns.forEach(function (d) {
      if (d === except) return;
      d.classList.remove('is-open');
      var t = d.querySelector('[aria-haspopup]');
      if (t) t.setAttribute('aria-expanded', 'false');
    });
  }

  dropdowns.forEach(function (d) {
    var trigger = d.querySelector('[aria-haspopup]');
    if (!trigger) return;

    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      var willOpen = !d.classList.contains('is-open');
      closeAllDropdowns(d);
      d.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    /* hover en desktop (con puntero fino) */
    d.addEventListener('mouseenter', function () {
      if (window.matchMedia('(hover:hover) and (min-width:1024px)').matches) {
        closeAllDropdowns(d);
        d.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
    d.addEventListener('mouseleave', function () {
      if (window.matchMedia('(hover:hover) and (min-width:1024px)').matches) {
        d.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  });

  doc.addEventListener('click', function (e) {
    if (!e.target.closest('.has-dropdown')) closeAllDropdowns();
  });

  /* ----------------------------------------------------------------------
     4. BUSCADOR (overlay)
     ---------------------------------------------------------------------- */
  var search = $('#search-overlay');
  var searchInput = $('#search-input');
  var searchOpeners = $all('[data-open-search]');
  var searchClose = $('#search-close');

  function openSearch() {
    if (!search) return;
    search.classList.add('is-open');
    search.setAttribute('aria-hidden', 'false');
    body.classList.add('no-scroll');
    if (searchInput) setTimeout(function () { searchInput.focus(); }, 60);
  }
  function closeSearch() {
    if (!search) return;
    search.classList.remove('is-open');
    search.setAttribute('aria-hidden', 'true');
    body.classList.remove('no-scroll');
  }
  searchOpeners.forEach(function (b) { b.addEventListener('click', openSearch); });
  if (searchClose) searchClose.addEventListener('click', closeSearch);
  if (search) {
    search.addEventListener('click', function (e) {
      if (e.target === search) closeSearch();
    });
    var form = search.querySelector('form');
    if (form) form.addEventListener('submit', function (e) { e.preventDefault(); });
  }

  /* ESC global cierra overlays/dropdowns */
  doc.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (search && search.classList.contains('is-open')) closeSearch();
      if (menu && menu.classList.contains('is-open')) closeMenu();
      closeAllDropdowns();
    }
  });

  /* ----------------------------------------------------------------------
     5. ESTADO DE RADIO "AL AIRE" — L–V 10:00–12:00 (sin API externa)
     ---------------------------------------------------------------------- */
  var SHOW = { name: 'El show de las mañanas', startH: 10, endH: 12, days: [1, 2, 3, 4, 5] };
  var DAY_NAMES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function isOnAir(now) {
    var d = now.getDay();
    var h = now.getHours();
    return SHOW.days.indexOf(d) !== -1 && h >= SHOW.startH && h < SHOW.endH;
  }

  /* Texto del próximo programa (día + hora) */
  function nextAirText(now) {
    var probe = new Date(now.getTime());
    // hoy aún antes de empezar
    if (SHOW.days.indexOf(now.getDay()) !== -1 && now.getHours() < SHOW.startH) {
      return 'Hoy ' + pad(SHOW.startH) + ':00';
    }
    for (var i = 1; i <= 7; i++) {
      probe.setDate(now.getDate() + i);
      if (SHOW.days.indexOf(probe.getDay()) !== -1) {
        var label = (i === 1) ? 'Mañana' : capitalize(DAY_NAMES[probe.getDay()]);
        return label + ' ' + pad(SHOW.startH) + ':00';
      }
    }
    return 'Lunes ' + pad(SHOW.startH) + ':00';
  }
  function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

  function applyRadioState() {
    var now = new Date();
    var onair = isOnAir(now);
    var next = nextAirText(now);

    /* elementos que reaccionan al estado */
    var liveBadges = $all('[data-live-badge]');   // píldora AL AIRE / próximo
    var liveTexts = $all('[data-live-text]');      // texto descriptivo
    var liveDots = $all('[data-live-dot]');        // puntito rojo del nav (solo al aire)
    var docks = $all('.radio-dock');

    /* En el nav, "En Vivo" solo muestra un puntito rojo cuando está AL AIRE */
    liveDots.forEach(function (el) {
      if (onair) { el.removeAttribute('hidden'); }
      else { el.setAttribute('hidden', ''); }
    });

    liveBadges.forEach(function (el) {
      if (onair) {
        el.className = 'badge badge--live';
        el.innerHTML = '<span class="dot" aria-hidden="true"></span>AL AIRE';
      } else {
        el.className = 'badge badge--soon';
        el.innerHTML = '<span class="dot" aria-hidden="true"></span>Próximo ' + next;
      }
    });

    liveTexts.forEach(function (el) {
      if (onair) {
        el.innerHTML = '<strong>' + SHOW.name + '</strong> está al aire ahora · L–V 10:00 a.m.–12:00 m.';
      } else {
        el.innerHTML = '<strong>' + SHOW.name + '</strong> — Fuera de aire. Próxima emisión: ' + next + '.';
      }
    });

    docks.forEach(function (dock) {
      var badge = dock.querySelector('[data-dock-badge]');
      if (badge) {
        if (onair) {
          badge.className = 'badge badge--live';
          badge.innerHTML = '<span class="dot" aria-hidden="true"></span>AL AIRE';
        } else {
          badge.className = 'badge badge--soon';
          badge.innerHTML = '<span class="dot" aria-hidden="true"></span>' + next;
        }
      }
    });
  }
  applyRadioState();
  // reevalúa por si cruza el umbral horario mientras la página está abierta
  setInterval(applyRadioState, 60 * 1000);

  /* ----------------------------------------------------------------------
     6. REPRODUCTOR (play/pause visual — prototipo, sin stream real)
     ---------------------------------------------------------------------- */
  var players = $all('[data-player]');
  players.forEach(function (scope) {
    var btns = $all('[data-play-toggle]', scope);
    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var playing = scope.classList.toggle('playing');
        btn.setAttribute('aria-pressed', playing ? 'true' : 'false');
        var label = playing ? 'Pausar' : 'Reproducir';
        btn.setAttribute('aria-label', label + ' radio en vivo');
        /* sincroniza otros toggles del mismo scope */
        btns.forEach(function (b) { if (b !== btn) b.setAttribute('aria-pressed', playing ? 'true' : 'false'); });
      });
    });
  });

  /* Enlaces sin destino todavía (#) no deben saltar arriba bruscamente */
  $all('a[href="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); });
  });
})();
