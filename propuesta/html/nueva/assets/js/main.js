/* ==========================================================================
   HABLA DEPORTES · main.js (vanilla, sin dependencias)
   Solo interacciones: el contenido base se lee completo sin JavaScript.
   Módulos: cabecera pegada · menú móvil · buscador · pestañas · banner de
   partidos · carril · estado de la radio y reproductor acoplado · cuenta
   regresiva · encuesta · boletín · copiar enlace · chips de torneo.
   Respeta prefers-reduced-motion. Los paneles del menú son solo CSS (hover).
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var desktop = window.matchMedia('(min-width: 1024px)');

  /* 1. Cabecera: sombra y logo pequeño cuando el menú azul queda pegado ------ */
  var header = $('[data-header]');
  if (header) {
    var ticking = false;
    var update = function () {
      var limit = desktop.matches ? $('.masthead', header).offsetHeight : 0;
      header.classList.toggle('is-stuck', window.scrollY > limit);
      ticking = false;
    };
    window.addEventListener('scroll', function () {
      if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();
  }

  /* 2 y 3. Menú móvil y buscador (franja bajo la cabecera, no modal) --------- */
  function disclosure(btn, panel, onOpen, labels) {
    if (!btn || !panel) return null;
    var label = $('[data-menu-label]', btn);
    function set(open) {
      btn.setAttribute('aria-expanded', String(open));
      panel.hidden = !open;
      if (label && labels) label.textContent = open ? labels[1] : labels[0];
      if (open && onOpen) onOpen();
    }
    btn.addEventListener('click', function () { set(btn.getAttribute('aria-expanded') !== 'true'); });
    panel.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { set(false); btn.focus(); }
    });
    return set;
  }
  var setMenu = disclosure($('[data-menu-toggle]'), $('[data-menu]'), function () {
    if (setSearch) setSearch(false);
  }, ['Abrir el menú', 'Cerrar el menú']);
  var searchBtn = $('[data-search-toggle]');
  var setSearch = disclosure(searchBtn, $('[data-search]'), function () {
    if (setMenu) setMenu(false);
    var q = $('#q'); if (q) q.focus();
  });
  var closeSearch = $('[data-search-close]');
  if (closeSearch && setSearch) {
    closeSearch.addEventListener('click', function () { setSearch(false); searchBtn.focus(); });
  }

  /* 4. Pestañas accesibles (flechas, Inicio, Fin; el hash abre la pestaña) ---- */
  $$('[data-tabs]').forEach(function (root) {
    var tabs = $$('[role="tab"]', root);
    var select = function (tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
      if (focus) tab.focus();
    };
    tabs.forEach(function (tab, i) {
      tab.addEventListener('click', function () { select(tab); });
      tab.addEventListener('keydown', function (e) {
        var next = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
        if (next === undefined) return;
        e.preventDefault();
        select(tabs[(next + tabs.length) % tabs.length], true);
      });
    });
  });

  /* 5. Banner de partidos: rota cada 8 s; pausa con hover, foco o botón; no rota
        con movimiento reducido. Anuncia el cambio solo si lo pide el usuario. */
  $$('[data-carousel]').forEach(function (root) {
    var slides = $$('[data-slide]', root);
    var dots = $$('[data-goto]', root);
    var live = $('[data-slides]', root);
    var pauseBtn = $('[data-pause]', root);
    var pauseLabel = $('[data-pause-label]', root);
    var current = 0, timer = null, userPaused = false, hover = false, focused = false;
    var interval = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--rotate-interval')) * 1000 || 8000;

    function show(i, byUser) {
      i = (i + slides.length) % slides.length;
      if (i === current) return;
      live.setAttribute('aria-live', byUser ? 'polite' : 'off');
      slides[current].hidden = true;
      slides[current].classList.remove('is-entering');
      slides[i].hidden = false;
      slides[i].classList.add('is-entering');
      dots[current].removeAttribute('aria-current');
      dots[i].setAttribute('aria-current', 'true');
      current = i;
    }
    function running() { return !userPaused && !hover && !focused && !reduce.matches; }
    function schedule() {
      window.clearInterval(timer);
      if (running()) timer = window.setInterval(function () { show(current + 1, false); }, interval);
    }
    $('[data-prev]', root).addEventListener('click', function () { show(current - 1, true); });
    $('[data-next]', root).addEventListener('click', function () { show(current + 1, true); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { show(i, true); }); });
    pauseBtn.addEventListener('click', function () {
      userPaused = !userPaused;
      pauseBtn.setAttribute('aria-pressed', String(userPaused));
      pauseLabel.textContent = userPaused ? 'Reanudar la rotación' : 'Pausar la rotación';
      schedule();
    });
    root.addEventListener('mouseenter', function () { hover = true; schedule(); });
    root.addEventListener('mouseleave', function () { hover = false; schedule(); });
    root.addEventListener('focusin', function () { focused = true; schedule(); });
    root.addEventListener('focusout', function (e) {
      if (!root.contains(e.relatedTarget)) { focused = false; schedule(); }
    });
    var syncMotion = function () { pauseBtn.hidden = reduce.matches; schedule(); };
    if (reduce.addEventListener) reduce.addEventListener('change', syncMotion);
    syncMotion();
  });

  /* 6. Carril con flechas (Shorts) ------------------------------------------- */
  $$('[data-rail]').forEach(function (rail) {
    var track = $('.rail__track', rail);
    var prev = $('[data-rail-prev]', rail);
    var next = $('[data-rail-next]', rail);
    var sync = function () {
      var max = track.scrollWidth - track.clientWidth;
      rail.classList.toggle('rail--static', max <= 1);
      prev.disabled = track.scrollLeft <= 1;
      next.disabled = track.scrollLeft >= max - 1;
    };
    var step = function (dir) {
      track.scrollBy({ left: dir * track.clientWidth * 0.8, behavior: reduce.matches ? 'auto' : 'smooth' });
    };
    prev.addEventListener('click', function () { step(-1); });
    next.addEventListener('click', function () { step(1); });
    track.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
    sync();
  });

  /* 7. Radio: estado por horario de Bogotá (L-V 10:00-12:00) [H] y reproductor
        simulado. Sin autoplay: el acoplado aparece solo tras pulsar reproducir.
        Demostración: ?radio=aire o ?radio=fuera fuerzan el estado. */
  var html = document.documentElement;
  var bogota = new Date(Date.now() - 5 * 3600 * 1000); // UTC-5, sin horario de verano
  var day = bogota.getUTCDay();
  var minutes = bogota.getUTCHours() * 60 + bogota.getUTCMinutes();
  var weekday = day >= 1 && day <= 5;
  var onAir = weekday && minutes >= 600 && minutes < 720;
  var forced = new URLSearchParams(location.search).get('radio');
  if (forced === 'aire' || forced === 'fuera') onAir = forced === 'aire';
  html.setAttribute('data-radio', onAir ? 'aire' : 'fuera');
  $$('.only-aire').forEach(function (el) { el.hidden = !onAir; });
  $$('.only-fuera').forEach(function (el) { el.hidden = onAir; });

  var nextShow = $('[data-radio-next]');
  if (nextShow && !onAir) {
    var when = 'el lunes';
    if (weekday && minutes < 600) when = 'hoy';
    else if (day >= 0 && day <= 4) when = 'mañana';
    nextShow.textContent = 'Fuera del aire. El programa vuelve ' + when + ' a las 10:00 a. m.';
  }
  var slotIndex = Math.floor((minutes - 600) / 30);
  var rows = $$('[data-schedule] .schedule-row');
  var currentBlock = '';
  if (onAir && rows[slotIndex]) {
    rows[slotIndex].classList.add('is-now');
    $('.schedule-row__now', rows[slotIndex]).hidden = false;
    currentBlock = $('.schedule-row__topic', rows[slotIndex]).firstChild.textContent;
  }

  var dock = $('[data-radio-dock]');
  var KEY = 'hd-radio';
  var store = {
    get: function () { try { return JSON.parse(sessionStorage.getItem(KEY)); } catch (e) { return null; } },
    set: function (v) { try { sessionStorage.setItem(KEY, JSON.stringify(v)); } catch (e) { /* sin almacenamiento */ } },
    clear: function () { try { sessionStorage.removeItem(KEY); } catch (e) { /* sin almacenamiento */ } }
  };
  var player = { kind: null, title: '', state: 'paused' };
  var connectTimer = null;

  function renderDock() {
    if (!dock) return;
    var isLive = player.kind === 'vivo';
    $('[data-dock-live]', dock).hidden = !isLive;
    $('[data-dock-title]', dock).textContent = isLive ? 'El show de las mañanas' : player.title;
    var status = {
      connecting: 'Conectando…',
      playing: isLive ? 'Al aire' + (currentBlock ? ': ' + currentBlock : '') + ' (demostración, sin audio)'
                      : 'Episodio anterior (demostración, sin audio)',
      paused: 'En pausa',
      restored: 'Continuar donde ibas'
    }[player.state];
    $('[data-dock-status]', dock).textContent = status;
    dock.setAttribute('data-state', player.state === 'playing' || player.state === 'connecting' ? 'playing' : 'paused');
    $('[data-dock-action]', dock).textContent = player.state === 'playing' || player.state === 'connecting' ? 'Pausar' : 'Reanudar';
  }
  function openDock() {
    if (!dock || html.getAttribute('data-page') === 'radio') return;
    dock.hidden = false;
    document.body.classList.add('has-dock');
  }
  function play(kind, title) {
    player.kind = kind; player.title = title || ''; player.state = 'connecting';
    openDock(); renderDock();
    window.clearTimeout(connectTimer);
    connectTimer = window.setTimeout(function () { player.state = 'playing'; renderDock(); }, 700);
    store.set({ kind: kind, title: player.title });
  }
  $$('[data-radio-play]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var kind = btn.getAttribute('data-radio-play');
      play(kind === 'vivo' && onAir ? 'vivo' : 'episodio', btn.getAttribute('data-title'));
    });
  });
  if (dock) {
    $('[data-radio-toggle]', dock).addEventListener('click', function () {
      if (player.state === 'playing' || player.state === 'connecting') {
        window.clearTimeout(connectTimer);
        player.state = 'paused';
        renderDock();
      } else {
        play(player.kind, player.title);
      }
    });
    $('[data-radio-close]', dock).addEventListener('click', function () {
      window.clearTimeout(connectTimer);
      dock.hidden = true;
      document.body.classList.remove('has-dock');
      store.clear();
      var main = $('#contenido'); if (main) main.focus({ preventScroll: true });
    });
    var saved = store.get();
    if (saved) {
      player.kind = saved.kind === 'vivo' && onAir ? 'vivo' : 'episodio';
      player.title = saved.title || 'El show de las mañanas';
      player.state = 'restored';
      openDock(); renderDock();
    }
  }

  /* 8. Cuenta regresiva ------------------------------------------------------- */
  var countdowns = $$('[data-countdown]');
  function tick() {
    countdowns.forEach(function (el) {
      var s = Math.floor((new Date(el.getAttribute('data-countdown')) - Date.now()) / 1000);
      if (s <= 0) { el.textContent = 'El partido ya empezó'; return; }
      var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60);
      el.textContent = 'Faltan ' + d + (d === 1 ? ' día, ' : ' días, ') + h + ' h y ' + m + ' min';
    });
  }
  if (countdowns.length) { tick(); window.setInterval(tick, 60000); }

  /* 9. Encuesta (resultados de ejemplo tras votar) ---------------------------- */
  $$('[data-poll]').forEach(function (form) {
    var error = $('[data-error]', form);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var choice = $('input:checked', form);
      error.hidden = !!choice;
      if (!choice) { $('input', form).focus(); return; }
      form.classList.add('is-voted');
      $$('.poll__result', form).forEach(function (r) {
        r.hidden = false;
        var bar = $('.poll__bar', r);
        bar.style.width = bar.getAttribute('data-pct') + '%';
      });
      $$('input', form).forEach(function (i) { i.disabled = true; });
      $('button[type="submit"]', form).hidden = true;
      $('[data-poll-msg]', form).textContent = 'Gracias por votar. Estos son los resultados hasta ahora (ejemplo).';
    });
  });

  /* 10. Boletín: validación sin rojo (borde en tinta + icono + texto) --------- */
  $$('[data-newsletter]').forEach(function (form) {
    var input = $('input[type="email"]', form);
    var error = $('[data-error]', form);
    var ok = $('[data-success]', form);
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
      input.setAttribute('aria-invalid', String(!valid));
      error.hidden = valid;
      ok.hidden = !valid;
      if (!valid) { input.focus(); return; }
      form.reset();
    });
  });

  /* 11. Copiar enlace ---------------------------------------------------------- */
  $$('[data-copy]').forEach(function (btn) {
    var status = $('[data-copy-status]', btn.closest('[data-share]'));
    btn.addEventListener('click', function () {
      var done = function () { status.textContent = 'Enlace copiado'; };
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(location.href).then(done, done);
      else done();
    });
  });

  /* 12. Chips de torneo (?t=): marca el activo y avisa que solo hay datos de Liga */
  var torneos = $('[data-torneos]');
  if (torneos) {
    var t = new URLSearchParams(location.search).get('t') || 'liga';
    $$('[data-torneo]', torneos).forEach(function (a) {
      if (a.getAttribute('data-torneo') === t) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
    var aviso = $('[data-torneo-aviso]');
    if (aviso) aviso.hidden = t === 'liga';
  }
})();
