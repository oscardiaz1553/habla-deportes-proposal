/* ==========================================================================
   HABLA DEPORTES — Home interactions (vanilla JS, sin dependencias)
   - Header sombra al hacer scroll
   - Menú móvil accesible
   - Slider del hero (flechas + dots)
   - Ticker: duplica el contenido para loop continuo
   - Carrusel "El show" (flechas que hacen scroll)
   ========================================================================== */
(function () {
  'use strict';

  /* ---- Header: transparente sobre el hero -> navy sticky al hacer scroll
     Umbral = altura de la topbar (~34px). Al superarlo, el header deja de
     estar superpuesto sobre el hero y pasa a fondo navy sólido. ---------- */
  var header = document.querySelector('.site-header');
  /* Páginas internas (articulo, video, seccion…) marcan el header con
     .site-header--solid: arranca navy sólido y NO debe alternar a
     transparente al hacer scroll (no hay hero oscuro detrás). En ese caso
     omitimos por completo el listener de scroll. */
  if (header && !header.classList.contains('site-header--solid')) {
    var topbar = document.querySelector('.topbar');
    var stuckThreshold = topbar ? topbar.offsetHeight - 2 : 32;
    var onScroll = function () {
      header.classList.toggle('is-stuck', window.scrollY > stuckThreshold);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---- Menú móvil ---------------------------------------------------- */
  var toggle = document.querySelector('[data-nav-toggle]');
  var menu = document.querySelector('[data-mobile-menu]');
  var menuClose = document.querySelector('[data-menu-close]');

  function setMenu(open) {
    if (!menu || !toggle) return;
    menu.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    menu.setAttribute('aria-hidden', String(!open));
    document.body.style.overflow = open ? 'hidden' : '';
    if (open) {
      var first = menu.querySelector('a, button');
      if (first) first.focus();
    } else {
      toggle.focus();
    }
  }
  if (toggle) toggle.addEventListener('click', function () {
    setMenu(toggle.getAttribute('aria-expanded') !== 'true');
  });
  if (menuClose) menuClose.addEventListener('click', function () { setMenu(false); });
  if (menu) {
    menu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { setMenu(false); });
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu && menu.classList.contains('is-open')) setMenu(false);
  });

  /* ---- Hero slider (crossfade real con 2 capas + Ken Burns) ---------- */
  var hero = document.querySelector('[data-hero]');
  if (hero) {
    var slides = JSON.parse(hero.getAttribute('data-slides') || '[]');
    var layers = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-layer]'));
    var titleEl = hero.querySelector('[data-hero-title]');
    var metaEl = hero.querySelector('.hero__meta');
    var metaTimeEl = hero.querySelector('[data-hero-time]');
    var playBtn = hero.querySelector('[data-hero-play]');
    var dots = Array.prototype.slice.call(hero.querySelectorAll('[data-hero-dot]'));
    var current = 0;
    var active = 0; // índice (0/1) de la capa visible
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Reinicia una animación CSS de entrada quitando y re-agregando la clase
       (forzando reflow), para que se dispare en cada cambio de slide. */
    function replay(el) {
      if (reduce || !el) return;
      el.classList.remove('is-enter');
      void el.offsetWidth; // reflow
      el.classList.add('is-enter');
    }

    function syncText(idx) {
      var s = slides[idx];
      if (titleEl) titleEl.textContent = s.title;
      if (metaTimeEl) metaTimeEl.textContent = s.time;
      // El botón play del hero refleja el video del slide activo (facade).
      if (playBtn && s.youtube) {
        playBtn.setAttribute('data-youtube-id', s.youtube);
        playBtn.setAttribute('data-video-title', s.title || '');
      }
      replay(titleEl);
      replay(metaEl);
      dots.forEach(function (d, di) {
        d.setAttribute('aria-current', di === idx ? 'true' : 'false');
      });
    }

    /* Crossfade: precarga la imagen destino en la capa oculta ANTES de
       intercambiar la opacidad, para no mostrar una imagen a medio cargar. */
    function crossfade(idx) {
      var s = slides[idx];
      var incoming = layers[(active + 1) % layers.length];
      var outgoing = layers[active];
      if (!incoming) return;

      var reveal = function () {
        incoming.classList.add('is-active');
        outgoing.classList.remove('is-active');
        outgoing.setAttribute('alt', ''); // la saliente pasa a decorativa
        active = (active + 1) % layers.length;
      };

      incoming.setAttribute('alt', s.alt || '');

      // La capa ya tiene esta imagen cargada -> revelar directo.
      if (incoming.getAttribute('src') === s.img &&
          incoming.complete && incoming.naturalWidth > 0) {
        reveal();
        return;
      }
      // Precargar y recién ahí intercambiar.
      var pre = new Image();
      pre.onload = pre.onerror = function () {
        incoming.src = s.img;
        reveal();
      };
      pre.src = s.img;
    }

    function render(i) {
      if (!slides.length) return;
      current = (i + slides.length) % slides.length;
      crossfade(current);
      syncText(current);
    }

    hero.querySelectorAll('[data-hero-prev]').forEach(function (b) {
      b.addEventListener('click', function () { render(current - 1); restart(); });
    });
    hero.querySelectorAll('[data-hero-next]').forEach(function (b) {
      b.addEventListener('click', function () { render(current + 1); restart(); });
    });
    dots.forEach(function (d, di) {
      d.addEventListener('click', function () { render(di); restart(); });
    });

    var timer = null;
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function restart() {
      if (reduce) return;
      stop();
      timer = setInterval(function () { render(current + 1); }, 6000);
    }

    // Pausar autoplay al pasar el cursor o al enfocar dentro del hero.
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', restart);
    hero.addEventListener('focusin', stop);
    hero.addEventListener('focusout', restart);

    if (slides.length) {
      // La capa 0 ya muestra el slide 0 (desde el HTML); precargamos el
      // siguiente para que el primer crossfade sea instantáneo.
      if (slides[1]) { var p = new Image(); p.src = slides[1].img; }
      syncText(0);
      restart();
    }
  }

  /* ---- Ticker: duplicar para loop sin costuras ----------------------- */
  var track = document.querySelector('[data-ticker-track]');
  if (track) {
    track.setAttribute('aria-hidden', 'false');
    var clone = track.cloneNode(true);
    clone.setAttribute('aria-hidden', 'true');
    track.parentNode.appendChild(clone);
  }

  /* ---- Slider del Centro de Partidos (banner de partido) -------------
     Track con transform translateX; dots + autoavance. Se pausa al pasar
     el cursor o enfocar dentro. Respeta prefers-reduced-motion (sin
     autoavance ni transición; la transición se anula además vía CSS). */
  document.querySelectorAll('[data-match-slider]').forEach(function (root) {
    var track = root.querySelector('[data-match-track]');
    var slides = track ? Array.prototype.slice.call(track.children) : [];
    var dots = Array.prototype.slice.call(root.querySelectorAll('[data-match-dot]'));
    if (!track || slides.length < 2) return;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var idx = 0;
    var timer = null;

    function go(i) {
      idx = (i + slides.length) % slides.length;
      track.style.transform = 'translateX(' + (-idx * 100) + '%)';
      dots.forEach(function (d, di) {
        d.setAttribute('aria-current', di === idx ? 'true' : 'false');
      });
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function restart() {
      if (reduce) return;
      stop();
      timer = setInterval(function () { go(idx + 1); }, 6000);
    }
    dots.forEach(function (d, di) {
      d.addEventListener('click', function () { go(di); restart(); });
    });
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', restart);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', restart);
    go(0);
    restart();
  });

  /* ---- Carrusel "El show": flechas hacen scroll ---------------------- */
  document.querySelectorAll('[data-carousel]').forEach(function (root) {
    var viewport = root.querySelector('[data-carousel-viewport]');
    var prev = root.querySelector('[data-carousel-prev]');
    var next = root.querySelector('[data-carousel-next]');
    if (!viewport) return;

    function step() {
      // Soporta tanto "El show" (.vcard) como "Clips" (.clip). El gap real se
      // lee de la grilla para que el desplazamiento sea exacto en cada módulo.
      var card = viewport.querySelector('.vcard, .clip');
      var gap = parseFloat(getComputedStyle(viewport).columnGap) || 20;
      return card ? card.getBoundingClientRect().width + gap : viewport.clientWidth * 0.8;
    }
    function update() {
      if (!prev || !next) return;
      var maxScroll = viewport.scrollWidth - viewport.clientWidth - 2;
      prev.disabled = viewport.scrollLeft <= 2;
      next.disabled = viewport.scrollLeft >= maxScroll;
    }
    if (prev) prev.addEventListener('click', function () {
      viewport.scrollBy({ left: -step(), behavior: 'smooth' });
    });
    if (next) next.addEventListener('click', function () {
      viewport.scrollBy({ left: step(), behavior: 'smooth' });
    });
    viewport.addEventListener('scroll', function () {
      window.requestAnimationFrame(update);
    }, { passive: true });
    window.addEventListener('resize', update);
    update();
  });

  /* ---- Clips: preview autoplay MUTEADO por visibilidad (tipo Shorts/Reels)
     - IntersectionObserver sobre cada .clip de #clips.
     - Al entrar en vista (>=60%) se crea un iframe de YouTube muteado + loop
       (facade: sólo bajo demanda, nunca al cargar la página).
     - Concurrencia limitada a 2 previews a la vez; se priorizan las tarjetas
       más visibles y más centradas en el viewport.
     - Al salir de vista se ELIMINA el iframe (corta reproducción/consumo).
     - Se pausa (destruye) todo cuando la pestaña queda oculta.
     - El clic NO lo captura el preview (pointer-events:none) -> el enlace
       abre el lightbox existente CON sonido.
     - Respeta prefers-reduced-motion y Save-Data: sin previews (thumbnail fijo). */
  var clipsSection = document.getElementById('clips');
  if (clipsSection && 'IntersectionObserver' in window) {
    var prefersReduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var conn = navigator.connection || navigator.webkitConnection || navigator.mozConnection;
    var saveData = !!(conn && conn.saveData);

    if (!prefersReduce && !saveData) {
      var MAX_PREVIEWS = 2;
      var VISIBLE_RATIO = 0.6;
      var clips = Array.prototype.slice.call(clipsSection.querySelectorAll('.clip'));
      var visible = new Map();   // clip -> intersectionRatio
      var playing = new Set();   // clips con preview activo

      function validId(id) { return /^[A-Za-z0-9_-]{11}$/.test(id || ''); }

      function createPreview(clip) {
        if (clip.__preview) return;
        var id = clip.getAttribute('data-youtube-id');
        if (!validId(id)) return;
        var iframe = document.createElement('iframe');
        iframe.className = 'clip__preview';
        iframe.src = 'https://www.youtube.com/embed/' + id +
          '?autoplay=1&mute=1&loop=1&playlist=' + id +
          '&controls=0&modestbranding=1&playsinline=1&rel=0&disablekb=1&fs=0';
        iframe.setAttribute('title', '');
        iframe.setAttribute('aria-hidden', 'true');   // decorativo
        iframe.setAttribute('tabindex', '-1');         // fuera del orden de tabulación
        iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
        var muted = document.createElement('span');
        muted.className = 'clip__muted';
        muted.setAttribute('aria-hidden', 'true');
        muted.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true">' +
          '<path d="M11 5 6 9H3v6h3l5 4z" fill="currentColor"/>' +
          '<path d="M16 9l6 6M22 9l-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        clip.appendChild(iframe);
        clip.appendChild(muted);
        clip.__preview = iframe;
        clip.__muted = muted;
        clip.classList.add('is-previewing');
      }

      function destroyPreview(clip) {
        if (clip.__preview) { clip.__preview.remove(); clip.__preview = null; }
        if (clip.__muted) { clip.__muted.remove(); clip.__muted = null; }
        clip.classList.remove('is-previewing');
      }

      function centerDist(clip) {
        var r = clip.getBoundingClientRect();
        var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        return Math.abs(cx - window.innerWidth / 2) + Math.abs(cy - window.innerHeight / 2);
      }

      function reconcile() {
        // Pestaña oculta: no reproducir en segundo plano.
        if (document.hidden) {
          playing.forEach(destroyPreview);
          playing.clear();
          return;
        }
        // Ordena las visibles: primero por ratio, desempata por cercanía al centro.
        var candidates = Array.prototype.slice.call(visible.keys());
        candidates.sort(function (a, b) {
          var dr = visible.get(b) - visible.get(a);
          if (Math.abs(dr) > 0.05) return dr;
          return centerDist(a) - centerDist(b);
        });
        var keep = new Set(candidates.slice(0, MAX_PREVIEWS));
        // Detén las que ya no deben reproducirse.
        playing.forEach(function (clip) {
          if (!keep.has(clip)) { destroyPreview(clip); playing.delete(clip); }
        });
        // Crea las nuevas (respetando el límite).
        keep.forEach(function (clip) {
          if (!playing.has(clip)) { createPreview(clip); playing.add(clip); }
        });
      }

      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && entry.intersectionRatio >= VISIBLE_RATIO) {
            visible.set(entry.target, entry.intersectionRatio);
          } else {
            visible.delete(entry.target);
          }
        });
        reconcile();
      }, { threshold: [0, VISIBLE_RATIO, 0.9, 1] });

      clips.forEach(function (clip) { io.observe(clip); });

      // Pausa/reanuda con la visibilidad de la pestaña.
      document.addEventListener('visibilitychange', reconcile);
    }
  }

  /* ---- Reproductor INLINE (facade) — página de Video ----------------
     El contenedor [data-video-facade] muestra portada + botón play. Al hacer
     clic se INYECTA el <iframe> de YouTube CON sonido (autoplay=1, sin mute)
     dentro del propio reproductor (no abre modal). El iframe se crea sólo bajo
     demanda (nunca al cargar). Usa data-player-yt (NO data-youtube-id) para no
     colisionar con la delegación del lightbox. */
  document.querySelectorAll('[data-video-facade]').forEach(function (facade) {
    var btn = facade.querySelector('[data-player-yt]');
    if (!btn) return;
    function validPlayerId(id) { return /^[A-Za-z0-9_-]{11}$/.test(id || ''); }
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var id = btn.getAttribute('data-player-yt');
      if (!validPlayerId(id)) return;
      var iframe = document.createElement('iframe');
      iframe.className = 'player__iframe';
      iframe.src = 'https://www.youtube.com/embed/' + id +
                   '?autoplay=1&rel=0&modestbranding=1&playsinline=1';
      iframe.title = btn.getAttribute('data-player-title') || 'Video de Habla Deportes';
      iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen');
      iframe.setAttribute('allowfullscreen', '');
      facade.innerHTML = '';
      facade.appendChild(iframe);
      facade.classList.add('is-playing');
      iframe.focus();
    });
  });

  /* ---- Sección / Índice: filtros (chips) + "cargar más" -------------
     Progresivo: sin JS, todas las tarjetas se ven (los extras llevan [hidden]
     como estado inicial de "cargar más", pero el botón sólo aparece con JS).
     - Filtros: botones [data-filter] con aria-pressed. Al activar uno, las
       tarjetas cuyo data-tipo no coincide reciben .is-filtered-out (display:none).
     - "Cargar más": revela las tarjetas extra ([hidden]) y se auto-oculta. */
  var filterBar = document.querySelector('[data-filter-bar]');
  var grid = document.querySelector('[data-filter-grid]');
  if (grid) {
    var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-tipo]'));
    var emptyMsg = document.querySelector('[data-filter-empty]');
    var moreBtn = document.querySelector('[data-load-more]');
    var current = 'todo';

    function applyFilter() {
      var shown = 0;
      cards.forEach(function (card) {
        var match = (current === 'todo' || card.getAttribute('data-tipo') === current);
        card.classList.toggle('is-filtered-out', !match);
        // Cuenta como visible sólo si además no está oculta por "cargar más".
        if (match && !card.hasAttribute('hidden')) shown++;
      });
      if (emptyMsg) emptyMsg.hidden = shown > 0;
    }

    if (filterBar) {
      var chips = Array.prototype.slice.call(filterBar.querySelectorAll('[data-filter]'));
      chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          current = chip.getAttribute('data-filter') || 'todo';
          chips.forEach(function (c) {
            c.setAttribute('aria-pressed', String(c === chip));
          });
          applyFilter();
        });
      });
    }

    if (moreBtn) {
      moreBtn.hidden = false; // sólo con JS: enseñar el control
      moreBtn.addEventListener('click', function () {
        grid.querySelectorAll('[data-extra]').forEach(function (card) {
          card.removeAttribute('hidden');
        });
        moreBtn.hidden = true;
        applyFilter(); // reevaluar por si hay un filtro activo
      });
    }
  }

  /* ---- Lightbox de video (YouTube) ----------------------------------
     Facade: cada pieza (hero/cards/feature/lista) lleva data-youtube-id.
     Al hacer clic se abre un modal y se INYECTA el <iframe> (autoplay).
     Al cerrar se ELIMINA el iframe del DOM para detener el audio. -------- */
  var lightbox = document.querySelector('[data-lightbox]');
  if (lightbox) {
    var lbDialog = lightbox.querySelector('.lightbox__dialog');
    var lbFrame = lightbox.querySelector('[data-lightbox-frame]');
    var lbClose = lightbox.querySelector('[data-lightbox-close]');
    var lastFocused = null;
    var isOpen = false;
    var reduceLb = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Valida un ID de YouTube (11 caracteres del alfabeto base64url).
    function isValidId(id) { return /^[A-Za-z0-9_-]{11}$/.test(id || ''); }

    function focusables() {
      return Array.prototype.slice.call(
        lightbox.querySelectorAll('button, iframe, [href], [tabindex]:not([tabindex="-1"])')
      ).filter(function (el) { return el.offsetParent !== null || el.tagName === 'IFRAME'; });
    }

    function onKeydown(e) {
      if (e.key === 'Escape') { e.preventDefault(); closeLightbox(); return; }
      if (e.key !== 'Tab') return;
      // Focus trap: mantener el foco dentro del modal.
      var items = focusables();
      if (!items.length) { e.preventDefault(); return; }
      var first = items[0];
      var last = items[items.length - 1];
      var activeInside = lightbox.contains(document.activeElement);
      if (e.shiftKey) {
        if (document.activeElement === first || !activeInside) { e.preventDefault(); last.focus(); }
      } else {
        if (document.activeElement === last || !activeInside) { e.preventDefault(); first.focus(); }
      }
    }

    function openLightbox(id, title, opener) {
      if (!isValidId(id) || isOpen) return;
      lastFocused = opener || document.activeElement;
      isOpen = true;

      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube.com/embed/' + id +
                   '?autoplay=1&rel=0&modestbranding=1&playsinline=1';
      iframe.title = title || 'Video de Habla Deportes';
      iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen');
      iframe.setAttribute('allowfullscreen', '');
      lbFrame.appendChild(iframe);

      lightbox.setAttribute('aria-label', 'Reproductor: ' + (title || 'Video de Habla Deportes'));
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';           // bloquear scroll de fondo
      document.addEventListener('keydown', onKeydown, true);

      // Mostrar con transición SIN depender de requestAnimationFrame (que se
      // "congela" en pestañas en segundo plano): un reflow forzado hace que la
      // transición de opacidad se dispare al añadir is-open.
      if (!reduceLb) { void lightbox.offsetWidth; }
      lightbox.classList.add('is-open');

      // Foco al botón cerrar en una macrotarea (setTimeout 0): asegura que el
      // elemento ya esté renderizado y le gana al foco del elemento activador.
      setTimeout(function () { if (isOpen && lbClose) lbClose.focus(); }, 0);
    }

    function closeLightbox() {
      if (!isOpen) return;
      isOpen = false;
      lightbox.classList.remove('is-open');
      document.removeEventListener('keydown', onKeydown, true);

      var finalize = function () {
        lightbox.hidden = true;
        lbFrame.innerHTML = '';                           // ELIMINA el iframe -> detiene el audio
        document.body.style.overflow = '';
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
        lastFocused = null;
      };
      if (reduceLb) { finalize(); }
      else {
        var done = false;
        var once = function () { if (done) return; done = true; lbDialog.removeEventListener('transitionend', once); finalize(); };
        lbDialog.addEventListener('transitionend', once);
        setTimeout(once, 320);                            // respaldo si no dispara transitionend
      }
    }

    // Cierre: botón ✕ y clic en el backdrop (ambos con data-lightbox-close).
    lightbox.querySelectorAll('[data-lightbox-close]').forEach(function (el) {
      el.addEventListener('click', closeLightbox);
    });

    // Delegación global: cualquier pieza con data-youtube-id abre el modal.
    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-youtube-id]');
      if (!trigger) return;
      e.preventDefault();
      openLightbox(
        trigger.getAttribute('data-youtube-id'),
        trigger.getAttribute('data-video-title') || 'Video de Habla Deportes',
        trigger
      );
    });
  }

  /* ---- Estado "EN VIVO / Próximo" del programa (ficha de Programa) -------
     El show de la mañana va L–V de 10:00 a 12:00 (hora de Colombia, UTC-5,
     sin horario de verano). Calculamos "ahora" en Bogotá desde UTC y, según
     la franja, marcamos el badge [data-live-schedule] como "EN VIVO ahora"
     o "Próximo: [día] 10:00 a.m.". Degrada a texto estático si no hay JS. */
  document.querySelectorAll('[data-live-schedule]').forEach(function (badge) {
    var label = badge.querySelector('[data-live-label]');
    if (!label) return;

    var DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    var START = 10, END = 12; // horas locales de Colombia

    function nowBogota() {
      // Desplaza el instante actual a UTC-05:00 leyendo los componentes UTC.
      var d = new Date();
      return new Date(d.getTime() + (d.getTimezoneOffset() * 60000) - (5 * 3600000));
    }

    function nextAiring(from) {
      // Devuelve el próximo inicio de emisión (L–V 10:00) a partir de "from".
      var t = new Date(from.getTime());
      for (var i = 0; i < 8; i++) {
        var dow = t.getDay();
        var isWeekday = dow >= 1 && dow <= 5;
        if (isWeekday && (i > 0 || from.getHours() < START)) {
          t.setHours(START, 0, 0, 0);
          return t;
        }
        t.setDate(t.getDate() + 1);
        t.setHours(0, 0, 0, 0);
      }
      return t;
    }

    function render() {
      var now = nowBogota();
      var dow = now.getDay();
      var isWeekday = dow >= 1 && dow <= 5;
      var live = isWeekday && now.getHours() >= START && now.getHours() < END;

      if (live) {
        badge.classList.add('is-live');
        label.textContent = 'EN VIVO ahora';
      } else {
        badge.classList.remove('is-live');
        var next = nextAiring(now);
        var mismoDia = next.toDateString() === now.toDateString();
        var cuando = mismoDia ? 'hoy' : DIAS[next.getDay()];
        label.textContent = 'Próximo: ' + cuando + ' 10:00 a.m.';
      }
    }

    render();
    // Reevaluar cada minuto para cruzar el borde de la franja sin recargar.
    setInterval(render, 60000);
  });

  /* ---- Reproductor EN VIVO / Radio (página en-vivo) — estado AL AIRE ----
     Reutiliza la MISMA lógica horaria que [data-live-schedule] (L–V 10–12,
     hora de Colombia UTC-5, sin DST). Sobre el contenedor [data-live-player]
     alterna el estado AL AIRE / FUERA DE AIRE, actualiza "programa actual" y
     "próximo", y resalta en la parrilla la franja del día en emisión.
     Degrada a un estado estático coherente si no hay JS. */
  document.querySelectorAll('[data-live-player]').forEach(function (root) {
    var DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    var START = 10, END = 12; // horas locales de Colombia
    var stateEl   = root.querySelector('[data-live-state]');
    var currentEl = document.querySelector('[data-current-program]');
    var nextEl    = document.querySelector('[data-next-program]');
    var slots     = document.querySelectorAll('[data-schedule-day]');

    function nowBogota() {
      var d = new Date();
      return new Date(d.getTime() + (d.getTimezoneOffset() * 60000) - (5 * 3600000));
    }
    function nextAiring(from) {
      var t = new Date(from.getTime());
      for (var i = 0; i < 8; i++) {
        var dow = t.getDay();
        if (dow >= 1 && dow <= 5 && (i > 0 || from.getHours() < START)) {
          t.setHours(START, 0, 0, 0);
          return t;
        }
        t.setDate(t.getDate() + 1);
        t.setHours(0, 0, 0, 0);
      }
      return t;
    }
    function render() {
      var now = nowBogota();
      var dow = now.getDay();
      var live = dow >= 1 && dow <= 5 && now.getHours() >= START && now.getHours() < END;

      root.classList.toggle('is-onair', live);
      root.classList.toggle('is-offair', !live);
      if (stateEl) stateEl.textContent = live ? 'AL AIRE' : 'FUERA DE AIRE';
      if (currentEl) currentEl.textContent = live ? 'El show de la mañana' : 'Sin emisión en directo';

      if (nextEl) {
        if (live) {
          nextEl.textContent = 'Hoy en directo hasta las 12:00 m.';
        } else {
          var n = nextAiring(now);
          var mismoDia = n.toDateString() === now.toDateString();
          nextEl.textContent = 'Próximo programa: ' + (mismoDia ? 'hoy' : DIAS[n.getDay()]) + ' 10:00 a.m.';
        }
      }

      // Resalta la celda "El show de la mañana" del día en emisión.
      slots.forEach(function (cell) {
        var day = parseInt(cell.getAttribute('data-schedule-day'), 10);
        cell.classList.toggle('is-now', live && day === dow);
      });
    }
    render();
    setInterval(render, 60000);
  });

})();
