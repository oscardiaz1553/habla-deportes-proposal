/* ==========================================================================
   HABLA DEPORTES · main.js (vanilla, sin dependencias)
   Solo interacciones: el contenido base se lee completo sin JavaScript.
   Módulos: cabecera pegada · megamenú (intención de hover) · buscador · hoja
   del menú móvil · pestañas · banner de partidos · carril · estado «en vivo»
   · filtros por tema · cuenta regresiva · encuesta · formularios · copiar
   enlace · filtros pendientes · chips de torneo.
   Respeta prefers-reduced-motion (los temporizadores del hover se mantienen:
   miden intención, no son movimiento).
   ========================================================================== */
(function () {
  'use strict';

  /* INDICADOR «EN VIVO» (un solo sitio para cambiarlo):
     'horario' = al aire de lunes a viernes, 10:00 a 12:00 (hora de Bogotá);
     'si' / 'no' = forzado para demostraciones. También ?envivo=si|no en la URL. */
  var EN_VIVO = 'horario';

  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  var desktop = window.matchMedia('(min-width: 1024px)');

  /* 1. Cabecera: se compacta al bajar. Escritorio: el nivel 1 (64) se va con la
        página y queda pegado el nivel 2 (48) con el logo de 24 px. No se esconde
        al bajar ni reaparece al subir (08 §2.5). */
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

  /* 2. Megamenú: SOLO hover en escritorio con puntero fino (decisión del dueño).
        No abre con clic ni con teclado; cada ítem es un enlace real a su hub.
        Intención: abre tras 250 ms quieto; con un panel abierto cambia al
        instante en horizontal; si el puntero BAJA en diagonal hacia el panel
        cruzando otro ítem, no cambia salvo que se quede 80 ms quieto (zona
        segura). Ítem + panel son una sola región: cierra 400 ms después de
        salir. El panel cuelga de la barra sin hueco: ese es el puente invisible.
        Esc, Tab, scroll > 8 px, clic, perder el foco de la ventana o dejar de
        ser escritorio: cierre inmediato. Tiempos: tokens --mega-* (tokens.css). */
  var megaMQ = window.matchMedia('(min-width: 1024px) and (hover: hover) and (pointer: fine)');
  var megaNav = $('[data-mega]');
  var closeMega = function () {};
  if (megaNav) {
    var rootStyle = getComputedStyle(document.documentElement);
    var ms = function (name, fallback) {
      var v = rootStyle.getPropertyValue(name).trim();
      var n = parseFloat(v);
      if (isNaN(n)) return fallback;
      return /ms$/.test(v) ? n : n * 1000;
    };
    var T = {
      open: ms('--mega-open-delay', 250), swap: ms('--mega-switch-delay', 0),
      aim: ms('--mega-aim-rest', 80), close: ms('--mega-close-delay', 400)
    };
    var scrim = $('[data-mega-scrim]');
    var items = $$('[data-mega-item]', megaNav);
    var current = null, openT = 0, closeT = 0, aimT = 0, moved = false, eagerDone = false, scrollAt = 0;
    var pts = [];
    var clearTimers = function () {
      window.clearTimeout(openT); window.clearTimeout(closeT); window.clearTimeout(aimT);
      openT = closeT = aimT = 0;
    };
    var openMega = function (item) {
      clearTimers();
      if (current === item) return;
      if (current) {
        // Cambio de panel: sin animar el panel, solo se funde el contenido
        megaNav.classList.add('is-switching');
        current.classList.remove('is-open', 'is-swapped');
        item.classList.add('is-open', 'is-swapped');
        void megaNav.offsetWidth;
        megaNav.classList.remove('is-switching');
      } else {
        item.classList.add('is-open');
        scrollAt = window.scrollY;
      }
      current = item;
      if (scrim) scrim.classList.add('is-visible');
    };
    closeMega = function () {
      clearTimers();
      if (!current) return;
      current.classList.remove('is-open', 'is-swapped');
      current = null;
      if (scrim) scrim.classList.remove('is-visible');
    };
    // El puntero baja hacia el panel: dy > 0 y |dy| >= |dx| entre el más antiguo de los 3
    // últimos movimientos recientes (150 ms; si no hay, el último) y la posición actual
    var aimingDown = function (e) {
      var now = e.timeStamp;
      var recent = pts.filter(function (p) { return now - p.t <= 150; });
      var a = recent[0] || pts[pts.length - 1];
      if (!a) return false;
      var dx = e.clientX - a.x, dy = e.clientY - a.y;
      return dy > 0 && Math.abs(dy) >= Math.abs(dx);
    };
    // Imágenes del panel: pasan a carga inmediata con el primer hover de la barra
    var eager = function () {
      if (eagerDone) return;
      eagerDone = true;
      $$('.mega img[loading="lazy"]', megaNav).forEach(function (im) { im.loading = 'eager'; });
    };
    document.addEventListener('pointermove', function (e) {
      if (e.pointerType !== 'mouse') return;
      moved = true; // si la página carga con el puntero encima, nada abre hasta moverlo
      pts.push({ x: e.clientX, y: e.clientY, t: e.timeStamp });
      if (pts.length > 3) pts.shift();
    }, { passive: true });
    items.forEach(function (item) {
      item.addEventListener('pointerenter', function (e) {
        if (e.pointerType !== 'mouse' || !megaMQ.matches || !moved) return;
        eager();
        window.clearTimeout(closeT); closeT = 0;
        if (current === item) { window.clearTimeout(aimT); aimT = 0; return; }
        window.clearTimeout(openT); window.clearTimeout(aimT);
        if (!current) {
          openT = window.setTimeout(function () { openMega(item); }, T.open);
        } else if (aimingDown(e)) {
          aimT = window.setTimeout(function () { openMega(item); }, T.aim);
        } else if (T.swap > 0) {
          openT = window.setTimeout(function () { openMega(item); }, T.swap);
        } else {
          openMega(item);
        }
      });
      item.addEventListener('pointermove', function (e) {
        // Zona segura: solo cambia si el puntero se queda quieto 80 ms sobre el otro ítem
        if (e.pointerType !== 'mouse' || !aimT || current === item) return;
        window.clearTimeout(aimT);
        aimT = window.setTimeout(function () { openMega(item); }, T.aim);
      });
      item.addEventListener('pointerleave', function (e) {
        if (e.pointerType !== 'mouse') return;
        window.clearTimeout(openT); window.clearTimeout(aimT); openT = aimT = 0;
        if (current) {
          window.clearTimeout(closeT);
          closeT = window.setTimeout(closeMega, T.close);
        }
      });
    });
    megaNav.addEventListener('click', function (e) { if (e.target.closest('a')) closeMega(); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' || e.key === 'Tab') closeMega(); // el teclado nunca deja un panel abierto
    });
    window.addEventListener('scroll', function () {
      if (current && Math.abs(window.scrollY - scrollAt) > 8) closeMega();
    }, { passive: true });
    window.addEventListener('blur', function () { closeMega(); });
    if (megaMQ.addEventListener) megaMQ.addEventListener('change', function () { closeMega(); });
  }

  /* 3. Buscador (opción B: campo con línea inferior) y hoja del menú móvil ------
     Escritorio: el campo siempre está visible. Móvil: la lupa abre el campo, que
     reemplaza al logo; Esc o la flecha lo cierran y devuelven el foco a la lupa.
     El atajo «/» enfoca el campo desde cualquier página (salvo escribiendo en un campo). */
  var setMenu = null;
  var masthead = $('[data-masthead]');
  var searchBtn = $('[data-search-toggle]');
  var searchForm = $('[data-search]');
  var searchInput = $('#q');
  var setSearch = null;
  if (masthead && searchBtn && searchForm && searchInput) {
    setSearch = function (open) {
      masthead.classList.toggle('is-searching', open);
      searchBtn.setAttribute('aria-expanded', String(open));
      if (open) {
        if (setMenu) setMenu(false);
        closeMega();
        searchInput.focus();
      }
    };
    searchBtn.addEventListener('click', function () { setSearch(!masthead.classList.contains('is-searching')); });
    var closeSearch = $('[data-search-close]');
    if (closeSearch) closeSearch.addEventListener('click', function () { setSearch(false); searchBtn.focus(); });
    searchForm.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        setSearch(false);
        if (getComputedStyle(searchBtn).display !== 'none') searchBtn.focus(); else searchInput.blur();
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
      var t = e.target, tag = t && t.tagName;
      if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (t && t.isContentEditable)) return;
      e.preventDefault();
      if (getComputedStyle(searchBtn).display !== 'none') setSearch(true); else searchInput.focus();
    });
  }

  /* Hoja del menú (< 1024 y pantallas sin hover): pantalla completa entre la
     cabecera y la barra inferior. Foco atrapado (botón «Cerrar» + hoja), Esc
     cierra y devuelve el foco al botón, la página de fondo no se desplaza. */
  var menuToggles = $$('[data-menu-toggle]');  // «Menú» de la cabecera y «Más» de la barra inferior
  var menuBtn = $('.site-header [data-menu-toggle]');
  var sheet = $('[data-menu]');
  if (menuBtn && sheet) {
    var word = $('[data-menu-word]', menuBtn);
    var label = $('[data-menu-label]', menuBtn);
    var closingT = 0;
    var opener = menuBtn;
    sheet.setAttribute('tabindex', '-1');
    var isOpen = function () { return menuBtn.getAttribute('aria-expanded') === 'true'; };
    var focusables = function () {
      return menuToggles.concat($$('a[href], button:not([disabled]), input, select, textarea', sheet)).filter(function (el) {
        return el.getClientRects().length > 0;
      });
    };
    setMenu = function (open, restoreFocus) {
      window.clearTimeout(closingT);
      menuToggles.forEach(function (t) { t.setAttribute('aria-expanded', String(open)); });
      if (word) word.textContent = open ? 'Cerrar' : 'Menú';
      if (label) label.textContent = open ? 'Cerrar el menú' : 'Abrir el menú';
      document.documentElement.classList.toggle('is-menu-open', open);
      if (open) {
        if (setSearch) setSearch(false);
        closeMega();
        sheet.style.setProperty('--sheet-top', Math.max(0, Math.round(header.getBoundingClientRect().bottom)) + 'px');
        sheet.classList.remove('is-closing');
        sheet.hidden = false;
        sheet.scrollTop = 0;
        sheet.focus({ preventScroll: true });
      } else if (!sheet.hidden) {
        sheet.classList.add('is-closing');
        var d = parseFloat(getComputedStyle(sheet).animationDuration) * 1000 || 0;
        closingT = window.setTimeout(function () { sheet.hidden = true; sheet.classList.remove('is-closing'); }, d);
        if (restoreFocus) opener.focus();
      }
    };
    menuToggles.forEach(function (t) {
      t.addEventListener('click', function () { opener = t; setMenu(!isOpen(), false); });
    });
    document.addEventListener('keydown', function (e) {
      if (!isOpen()) return;
      if (e.key === 'Escape') { e.preventDefault(); setMenu(false, true); return; }
      if (e.key !== 'Tab') return;
      var list = focusables();
      var i = list.indexOf(document.activeElement);
      if (e.shiftKey && i <= 0) { e.preventDefault(); list[list.length - 1].focus(); }
      else if (!e.shiftKey && (i === -1 || i === list.length - 1)) { e.preventDefault(); list[0].focus(); }
    });
    sheet.addEventListener('click', function (e) {
      var a = e.target.closest('a[href]');
      if (a && a.getAttribute('href').charAt(0) === '#') setMenu(false, false); // ancla en la misma página
    });
    if (megaMQ.addEventListener) megaMQ.addEventListener('change', function (m) { if (m.matches && isOpen()) setMenu(false, false); });

    // Acordeón: uno abierto a la vez; por defecto, el de la sección actual (lo marca el HTML)
    var toggles = $$('[data-accordion]', sheet);
    toggles.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var willOpen = btn.getAttribute('aria-expanded') !== 'true';
        toggles.forEach(function (b) {
          var on = b === btn && willOpen;
          b.setAttribute('aria-expanded', String(on));
          document.getElementById(b.getAttribute('aria-controls')).hidden = !on;
        });
      });
    });
  }

  /* Barra inferior: «Inicio» estando en la portada sube al principio */
  var homeTab = $('.bottom-nav__link[href="index.html"][aria-current="page"]');
  if (homeTab) {
    homeTab.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: reduce.matches ? 'auto' : 'smooth' });
    });
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

  /* 7. Estado «en vivo» del programa (transmisión de YouTube, 06 D1).
        Sin reproductor de audio acoplado: el play lleva a En vivo / YouTube. */
  var html = document.documentElement;
  var bogota = new Date(Date.now() - 5 * 3600 * 1000); // UTC-5, sin horario de verano
  var day = bogota.getUTCDay();
  var minutes = bogota.getUTCHours() * 60 + bogota.getUTCMinutes();
  var weekday = day >= 1 && day <= 5;
  var mode = new URLSearchParams(location.search).get('envivo') || EN_VIVO;
  var onAir = mode === 'si' ? true : mode === 'no' ? false : weekday && minutes >= 600 && minutes < 720;
  html.setAttribute('data-aire', onAir ? 'si' : 'no');
  $$('.only-aire').forEach(function (el) { el.hidden = !onAir; });
  $$('.only-fuera').forEach(function (el) { el.hidden = onAir; });

  if (!onAir) {
    var when = 'el lunes';
    if (weekday && minutes < 600) when = 'hoy';
    else if (day >= 0 && day <= 4) when = 'mañana';
    $$('[data-next-show]').forEach(function (el) {
      el.textContent = 'Próximo programa · ' + when + ' a las 10:00 a. m.';
    });
  }
  var slot = -1;
  if (onAir) slot = Math.max(0, Math.min(3, Math.floor((minutes - 600) / 30)));
  $$('[data-schedule]').forEach(function (list) {
    var rows = $$('.schedule-row', list);
    if (slot >= 0 && rows[slot]) {
      rows[slot].classList.add('is-now');
      $('.schedule-row__now', rows[slot]).hidden = false;
    }
  });
  if (slot >= 0) {
    $$('[data-now-line]').forEach(function (el) {
      var topics = el.getAttribute('data-now-line').split('|');
      el.textContent = 'Ahora: ' + topics[slot] + (slot < 3 ? ' · Después: ' + topics[slot + 1] : '');
    });
  }
  var grid = $('[data-week-grid]');
  if (grid && weekday) {
    $$('td[data-day="' + day + '"]', grid).forEach(function (td, i) {
      td.classList.add('is-today');
      if (i === slot) td.classList.add('is-now');
    });
  }

  /* 7b. Filtros por tema: los enlaces funcionan sin JS (?tema=); con JS filtran en el sitio.
        En noticias.html el ítem activo de la barra azul, la hoja y la barra inferior
        sigue al tema: Junior, FPC o Fútbol internacional (data-temas); si no, «Más». */
  function markSection(tema) {
    var owner = $$('.primary-nav__item[data-temas]').filter(function (li) {
      return li.getAttribute('data-temas').split(' ').indexOf(tema) > -1;
    })[0];
    var key = owner ? owner.getAttribute('data-nav') : 'mas';
    $$('[data-nav]').forEach(function (li) {
      var link = $('.primary-nav__link, .sheet-section__link, .bottom-nav__link', li);
      if (!link) return;
      if (li.getAttribute('data-nav') === key) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  }
  $$('[data-filterable]').forEach(function (root) {
    var links = $$('[data-filter]', root);
    var items = $$('[data-filter-items] > li', root);
    var empty = $('[data-filter-empty]', root);
    var status = $('[data-filter-status]', root);
    var title = $('[data-filter-title]');
    var useUrl = root.hasAttribute('data-filter-url');
    function apply(tema, push) {
      var shown = 0;
      items.forEach(function (li) {
        var card = li.querySelector('[data-tema]') || li;
        var ok = tema === 'todas' || card.getAttribute('data-tema') === tema;
        li.hidden = !ok;
        if (ok) shown++;
      });
      var label = 'Todas';
      links.forEach(function (a) {
        var on = a.getAttribute('data-filter') === tema;
        if (on) { a.setAttribute('aria-current', 'true'); label = a.textContent; } else { a.removeAttribute('aria-current'); }
      });
      empty.hidden = shown > 0;
      status.textContent = 'Tema: ' + label + '. ' + shown + (shown === 1 ? ' resultado.' : ' resultados.');
      if (useUrl) {
        markSection(tema);
        if (title) title.textContent = tema === 'todas' ? title.getAttribute('data-filter-title') : 'Noticias: ' + label;
        if (push) history.replaceState(null, '', tema === 'todas' ? 'noticias.html' : 'noticias.html?tema=' + tema);
      }
    }
    links.forEach(function (a) {
      a.addEventListener('click', function (e) { e.preventDefault(); apply(a.getAttribute('data-filter'), true); });
    });
    var initial = useUrl ? new URLSearchParams(location.search).get('tema') : null;
    if (initial && links.some(function (a) { return a.getAttribute('data-filter') === initial; })) apply(initial, false);
  });

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

  /* 10b. Formularios de demostración (participa): errores en tinta, sin rojo */
  $$('[data-validate]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var first = null;
      $$('[required]', form).forEach(function (input) {
        var ok = input.value.trim() !== '';
        input.setAttribute('aria-invalid', String(!ok));
        var err = document.getElementById(input.getAttribute('aria-describedby'));
        if (err) err.hidden = ok;
        if (!ok && !first) first = input;
      });
      $('[data-success]', form).hidden = !!first;
      if (first) first.focus(); else form.reset();
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

  /* 11b. Filtros que aún no existen (videos ?tema=, mercado ?equipo=): la página
         carga completa y lo dice, en lugar de fingir que filtró */
  $$('[data-filter-pending]').forEach(function (el) {
    el.hidden = !new URLSearchParams(location.search).get(el.getAttribute('data-filter-pending'));
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
