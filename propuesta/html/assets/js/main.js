/* Diario Deportes — comportamiento compartido (index, artículo, categoría).
   - Carrusel del hero + auto-ajuste de titular (solo se activa si la página tiene .hero-track).
   - Flechas prev/next del rail de noticias (solo si existen en la página).
   - Contador animado (count-up) de la banda de cifras (solo home).
   - Parallax genérico reutilizable: banda "Pelotas y letras/Efemérides" (.pb-bg) y
     fondo de la franja destacada (.feature-bg img).
   - Reveal-on-scroll genérico para los módulos editoriales de las 3 páginas.
   El sticky-nav, los desplegables «Más ▾», el menú móvil y los rotadores de
   staff/tagline los gestiona assets/js/nav.js (no se tocan ni se duplican aquí).
   Todo vive dentro de un único IIFE: no se expone ninguna variable global. */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Construye el enlace a la plantilla única de artículo con los datos de la nota
  // (título/sección/bajada/imagen) codificados en el hash. articleReader() los lee.
  function buildArticleHref(d) {
    var q = [];
    if (d.title) q.push('t=' + encodeURIComponent(d.title));
    if (d.section) q.push('s=' + encodeURIComponent(d.section));
    if (d.excerpt) q.push('d=' + encodeURIComponent(d.excerpt));
    if (d.img) q.push('img=' + encodeURIComponent(d.img));
    return 'articulo.html' + (q.length ? '#' + q.join('&') : '');
  }

  /* ============================================================
     CARRUSEL DEL HERO + AUTO-AJUSTE DE TITULAR
     Solo corre si la página tiene hero (hoy, index.html). En artículo/categoría
     no hay .hero-track y esta función sale de inmediato sin tocar nada.
     ============================================================ */
  (function heroCarousel() {
    var track = document.querySelector('.hero-track');
    var slides = [].slice.call(document.querySelectorAll('.hero-slide'));
    if (!track || !slides.length) return;

    var dots = [].slice.call(document.querySelectorAll('.hero-dots button'));
    var tagEl = document.querySelector('.hero-tag');
    var titleEl = document.querySelector('.hero-body h1 a');
    var byEl = document.querySelector('.hero-meta .by');
    var cmEl = document.querySelector('.hero-meta .cm span');
    var whenEl = document.querySelector('.hero-meta .when');
    var idx = 0, timer = null;
    var titleH1 = document.querySelector('.hero-body h1');

    // Auto-ajuste: reduce el tamaño de fuente en pasos de 1px hasta que el titular
    // quepa en 2 líneas completas. Nunca corta texto ni agrega "...".
    function fitHeroTitle() {
      if (!titleH1) return;
      titleH1.style.fontSize = '';
      var base = parseFloat(getComputedStyle(titleH1).fontSize);
      var size = base, min = base * 0.6, guard = 0;
      while (guard < 40) {
        var lh = parseFloat(getComputedStyle(titleH1).lineHeight);
        var lines = Math.round(titleH1.scrollHeight / lh);
        if (lines <= 2 || size <= min) break;
        size -= 1;
        titleH1.style.fontSize = size + 'px';
        guard++;
      }
    }
    function show(n) {
      idx = (n + slides.length) % slides.length;
      // El toggle de .active en cada slide reinicia también el Ken Burns (site.css: .hero-slide.active img)
      slides.forEach(function (s, i) { s.classList.toggle('active', i === idx); });
      dots.forEach(function (d, i) {
        var on = i === idx;
        d.classList.toggle('active', on);
        if (on) d.setAttribute('aria-current', 'true'); else d.removeAttribute('aria-current');
      });
      var s = slides[idx];
      if (tagEl) tagEl.textContent = s.dataset.tag;
      if (titleEl) {
        titleEl.textContent = s.dataset.title;
        var himg = s.querySelector('img');
        // Habla (video-first): si el slide define data-href (p. ej. el video en
        // YouTube), el titular enlaza ahí; si no, cae al artículo generado.
        if (s.dataset.href) {
          titleEl.setAttribute('href', s.dataset.href);
          titleEl.setAttribute('target', '_blank');
          titleEl.setAttribute('rel', 'noopener');
        } else {
          titleEl.setAttribute('href', buildArticleHref({
            title: s.dataset.title,
            section: (s.dataset.tag || '').replace(/^#/, ''),
            img: himg ? himg.getAttribute('src') : ''
          }));
        }
      }
      if (byEl) byEl.textContent = s.dataset.by;
      if (cmEl) cmEl.textContent = s.dataset.cm;
      if (whenEl) whenEl.textContent = s.dataset.when;
      fitHeroTitle();
    }
    function next() { show(idx + 1); }
    function prev() { show(idx - 1); }
    function start() { if (reduce) return; stop(); timer = setInterval(next, 6000); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    dots.forEach(function (d) { d.addEventListener('click', function () { show(+d.dataset.goto); stop(); start(); }); });
    var nx = document.querySelector('.hero-ctrl .next'), pv = document.querySelector('.hero-ctrl .prev');
    if (nx) nx.addEventListener('click', function () { next(); stop(); start(); });
    if (pv) pv.addEventListener('click', function () { prev(); stop(); start(); });
    var hero = document.querySelector('.hero');
    if (hero) { hero.addEventListener('mouseenter', stop); hero.addEventListener('mouseleave', start); }

    /* Swipe táctil (mobile + tablet): cambia de nota deslizando. El hero es FADE
       (no arrastra un track), así que el gesto solo llama a next()/prev(). Con
       touch-action:pan-y (CSS) el navegador conserva el scroll vertical nativo y
       cede el eje horizontal → listeners passive, sin preventDefault ni jank.
       Los touch events son intrínsecamente táctiles: en desktop no disparan. */
    if (hero) {
      var SWIPE_MIN = 48;   // px horizontales mínimos para cambiar de nota
      var sx = 0, sy = 0, sdx = 0, sdy = 0, swTracking = false, swiped = false;

      hero.addEventListener('touchstart', function (e) {
        if (e.touches.length > 1) { swTracking = false; return; } // ignora pinch/multitouch
        var t = e.touches[0];
        sx = t.clientX; sy = t.clientY; sdx = 0; sdy = 0;
        swTracking = true; swiped = false;
        stop();             // pausa autoplay mientras el dedo está en pantalla
      }, { passive: true });

      hero.addEventListener('touchmove', function (e) {
        if (!swTracking) return;
        var t = e.touches[0];
        sdx = t.clientX - sx; sdy = t.clientY - sy;
      }, { passive: true });

      hero.addEventListener('touchend', function () {
        if (!swTracking) return;
        swTracking = false;
        // swipe válido: horizontal dominante y por encima del umbral
        if (Math.abs(sdx) >= SWIPE_MIN && Math.abs(sdx) > Math.abs(sdy)) {
          if (sdx < 0) next(); else prev();   // dedo der→izq = siguiente
          swiped = true;                        // bloquea el click fantasma sobre el <a> del titular
          setTimeout(function () { swiped = false; }, 400);
        }
        start();            // reanuda autoplay (no-op bajo prefers-reduced-motion)
      }, { passive: true });

      hero.addEventListener('touchcancel', function () { swTracking = false; start(); }, { passive: true });

      // Un swipe que termina sobre el enlace del titular NO debe abrir el artículo:
      // se intercepta el click en fase de captura mientras 'swiped' está activo.
      hero.addEventListener('click', function (e) {
        if (swiped) { e.preventDefault(); e.stopPropagation(); swiped = false; }
      }, true);
    }

    show(idx); // fija textos, enlace y ajuste del titular del slide inicial
    start();

    var fitResizeT = null;
    window.addEventListener('resize', function () {
      // los breakpoints cambian el tamaño base del titular; recalcular tras el resize
      clearTimeout(fitResizeT);
      fitResizeT = setTimeout(fitHeroTitle, 150);
    });
  })();

  /* ============================================================
     PARALLAX GENÉRICO
     Reemplaza la lógica que antes vivía hardcodeada para .pb-bg: ahora es
     reutilizable para cualquier selector. El contenedor de referencia (para
     calcular el progreso 0→1 en el viewport) es el ancestro .promo-band o
     .feature más cercano de cada elemento observado. Mismo cálculo, mismos
     límites ±max/±maxMobile que la versión original de .pb-bg.
     ============================================================ */
  function initParallax(selector, opts) {
    if (reduce) return; // el CSS también trae su propio guard (!important) como red de seguridad
    opts = opts || {};
    var max = opts.max != null ? opts.max : 24;
    var maxMobile = opts.maxMobile != null ? opts.maxMobile : 16;
    var els = [].slice.call(document.querySelectorAll(selector));
    if (!els.length) return;

    var curMax = window.innerWidth <= 768 ? maxMobile : max;
    var ticking = false;

    function referenceEl(el) {
      return el.closest('.promo-band') || el.closest('.feature') || el.parentElement;
    }
    function update() {
      ticking = false;
      els.forEach(function (el) {
        var ref = referenceEl(el);
        if (!ref) return;
        var rect = ref.getBoundingClientRect();
        var vh = window.innerHeight;
        // progreso 0→1 a medida que el contenedor de referencia atraviesa el viewport
        // (0 = entra por abajo, 1 = sale por arriba)
        var progress = (vh - rect.top) / (vh + rect.height);
        progress = Math.max(0, Math.min(1, progress));
        var offset = curMax - progress * (curMax * 2); // interpola de +max a -max
        el.style.setProperty('--pb-parallax', offset + 'px');
      });
    }
    function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }

    window.addEventListener('scroll', onScroll, { passive: true });
    update();

    var resizeT = null;
    window.addEventListener('resize', function () {
      clearTimeout(resizeT);
      resizeT = setTimeout(function () {
        curMax = window.innerWidth <= 768 ? maxMobile : max;
        update();
      }, 150);
    });
  }
  // Banda "Pelotas y letras / Efemérides" (comportamiento idéntico al original)
  // + fondo de la franja destacada (.feature-bg img), mismos límites ±24/±16px.
  initParallax('.pb-half--left .pb-bg,.pb-half--right .pb-bg,.feature-bg img', { max: 24, maxMobile: 16 });

  /* ============================================================
     RAIL DE NOTICIAS: flechas prev/next (solo si existen en la página).
     Cada rail busca sus propios botones .mn-prev/.mn-next dentro del mismo
     .wrap (así conviven varios rails en una misma página sin interferirse). ============================================================ */
  [].slice.call(document.querySelectorAll('.news-rail')).forEach(function (rail) {
    var scope = rail.closest('.wrap') || document;
    var rp = scope.querySelector('.mn-prev'), rn = scope.querySelector('.mn-next');
    if (rn) rn.addEventListener('click', function () { rail.scrollBy({ left: 340, behavior: 'smooth' }); });
    if (rp) rp.addEventListener('click', function () { rail.scrollBy({ left: -340, behavior: 'smooth' }); });
  });

  /* ============================================================
     CONTADOR ANIMADO (count-up) de la banda de cifras — solo home.
     Sube de 0 al valor final al entrar en pantalla. Usa su propio
     IntersectionObserver, independiente del de reveal-on-scroll: ambos
     pueden observar los mismos nodos .stat sin conflicto.
     ============================================================ */
  (function statsCounter() {
    var statNums = [].slice.call(document.querySelectorAll('.stat .num-value'));
    if (!statNums.length) return;

    function easeOutExpo(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }
    function animateCount(el) {
      var target = parseInt(el.dataset.count, 10) || 0;
      if (reduce) { el.textContent = target.toLocaleString('es-CO'); return; }
      var duration = 1400, start = null;
      function frame(ts) {
        if (!start) start = ts;
        var p = Math.min((ts - start) / duration, 1);
        el.textContent = Math.round(target * easeOutExpo(p)).toLocaleString('es-CO');
        if (p < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    }
    var statsSection = document.querySelector('.stats');
    if (statsSection && 'IntersectionObserver' in window) {
      var statsAnimated = false;
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting && !statsAnimated) {
            statsAnimated = true;
            statNums.forEach(animateCount);
            io.disconnect();
          }
        });
      }, { threshold: .4 });
      io.observe(statsSection);
    } else {
      statNums.forEach(animateCount);
    }
  })();

  /* ============================================================
     REVEAL ON SCROLL
     Añade .reveal a los módulos editoriales listados y, cuando entran en
     pantalla, .in-view (una sola vez, vía IntersectionObserver compartido).
     Si el usuario prefiere movimiento reducido, no se toca nada: todo el
     contenido queda visible de inmediato, sin excepción.
     La pauta y los widgets de afiliados (.ad-band, .ad-lead, .ad-duo,
     .ad-inline, .ad-slot, .side-ad, .house-ad) quedan deliberadamente fuera:
     no deben competir visualmente ni sumar movimiento (sensibilidad de marca).
     ============================================================ */
  (function revealOnScroll() {
    if (reduce) return;

    // Stagger para hermanos directos de un mismo grid/rail/lista: 0/60/120/180ms
    // en los primeros 4 elementos; de ahí en adelante, tope en 240ms.
    function stagger(i) { return i < 4 ? i * 60 : 240; }
    function markSingle(el) { if (el) el.classList.add('reveal'); }
    function markGroup(list) {
      [].slice.call(list).forEach(function (el, i) {
        el.classList.add('reveal');
        el.style.transitionDelay = stagger(i) + 'ms';
      });
    }

    // --- Home: franja destacada, rails de noticias, sección Junior, cifras,
    //     más deportes, resultados/programación, lo último + sidebar, promo-band.
    markSingle(document.querySelector('.feature .wrap'));
    [].slice.call(document.querySelectorAll('.news-rail')).forEach(function (rail) {
      markGroup(rail.querySelectorAll('.news-card'));
    });
    markSingle(document.querySelector('.feat-lead'));
    markGroup(document.querySelectorAll('.feat-item'));
    markGroup(document.querySelectorAll('.stats .stat'));
    markGroup(document.querySelectorAll('.cards4 .card'));
    markGroup(document.querySelectorAll('.data-grid .panel'));
    markGroup(document.querySelectorAll('.stream .row'));
    // Ojo: algunos .widget del aside SOLO envuelven pauta (.house-ad en home,
    // .side-ad en categoría). Esos quedan fuera del reveal: la regla de marca
    // "la pauta nunca se anima" pesa más que "cada .widget se revela".
    markGroup([].slice.call(document.querySelectorAll('aside .widget')).filter(function (w) {
      return !w.querySelector('.house-ad,.side-ad,.ad-slot,.ad-inline,.ad-band,.ad-lead,.ad-duo');
    }));
    markGroup(document.querySelectorAll('.promo-band .pb-half'));
    markSingle(document.querySelector('.news-band .inner'));

    // --- Artículo: cabecera + figura (no se anima párrafo por párrafo).
    //     "Temas relacionados" ya queda cubierto arriba (es un .news-rail más).
    markSingle(document.querySelector('.art-head'));
    markSingle(document.querySelector('.art-figure'));

    // --- Categoría: cabecera de sección (h1 + intro).
    //     feat-lead/feat-item/stream/widget/news-band ya quedan cubiertos arriba,
    //     porque categoria.html reutiliza exactamente las mismas clases que home.
    markSingle(document.querySelector('.cat-head'));

    var toObserve = [].slice.call(document.querySelectorAll('.reveal'));
    if (!toObserve.length) return;

    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: .15, rootMargin: '0px 0px -10% 0px' });
      toObserve.forEach(function (el) { io.observe(el); });
    } else {
      // sin soporte de IO: degradación segura, todo visible de inmediato
      toObserve.forEach(function (el) { el.classList.add('in-view'); });
    }
  })();

  /* ============================================================
     ENLACES DE ARTÍCULO — una sola plantilla que se adapta al titular clicado.
     Reescribe cada enlace de tarjeta hacia articulo.html añadiéndole en el hash
     el título/sección/bajada/imagen de esa nota. Así cada titular abre "su"
     artículo (así funcionará en WordPress: una plantilla, muchas URLs).
     El hero lo maneja el carrusel (su enlace se actualiza en show()).
     ============================================================ */
  (function articleLinks() {
    var cardSel = '.news-card,.card,.feat-lead,.feat-item,.stream .row,.op-card,.mostread li';
    [].slice.call(document.querySelectorAll('a[href="articulo.html"]')).forEach(function (a) {
      var card = a.closest(cardSel);
      if (!card) return; // enlaces sueltos (breadcrumb, "Ver todo", prose) no se tocan
      var titleEl = card.querySelector('h1,h2,h3,h4');
      var title = (titleEl ? titleEl.textContent : a.textContent).trim();
      if (!title) return;
      var ey = card.querySelector('.eyebrow,.feature-tag,.tagline');
      var exEl = card.querySelector('p.dek') || card.querySelector('p');
      var img = card.querySelector('img');
      a.setAttribute('href', buildArticleHref({
        title: title,
        section: ey ? ey.textContent.trim() : '',
        excerpt: exEl ? exEl.textContent.trim() : '',
        img: img ? img.getAttribute('src') : ''
      }));
    });
  })();

  /* ============================================================
     LECTOR DEL ARTÍCULO — pinta la plantilla con los datos del hash.
     Sin hash (o si no es la página de artículo), no toca nada: se ve la nota
     de ejemplo por defecto. El cuerpo de la nota es contenido de muestra.
     ============================================================ */
  (function articleReader() {
    var h1 = document.querySelector('.art-head h1');
    if (!h1 || !location.hash) return;
    var p = new URLSearchParams(location.hash.slice(1));
    var t = p.get('t');
    if (!t) return;
    var s = p.get('s'), d = p.get('d'), img = p.get('img');

    h1.textContent = t;
    document.title = t + ' — Diario Deportes';
    var crumbLast = document.querySelector('.breadcrumb > span');
    if (crumbLast) crumbLast.textContent = t;

    if (s) {
      var eyeEl = document.querySelector('.art-head .eyebrow');
      if (eyeEl) eyeEl.textContent = s;
      var crumbSec = document.querySelector('.breadcrumb a[href="articulo.html"]');
      if (crumbSec) crumbSec.textContent = s;
      // Resalta la sección correcta en el nav (o ninguna si no mapea a un ítem del menú)
      var navLinks = [].slice.call(document.querySelectorAll('.sn-menu .nav-list a,.mobile-menu a'));
      navLinks.forEach(function (l) { l.removeAttribute('aria-current'); });
      navLinks.forEach(function (l) {
        if (l.textContent.trim().toLowerCase() === s.toLowerCase()) l.setAttribute('aria-current', 'page');
      });
    }
    var sf = document.querySelector('.art-head .standfirst');
    if (sf) { if (d) { sf.textContent = d; } else { sf.style.display = 'none'; } }
    if (img) {
      var fig = document.querySelector('.art-figure img');
      if (fig) { fig.setAttribute('src', img); fig.setAttribute('alt', t); }
    }
    var cap = document.querySelector('.art-figure figcaption');
    if (cap) cap.textContent = 'Foto de ejemplo.';
  })();

  /* ============================================================
     HABLA DEPORTES — ESTADO DE RADIO "AL AIRE" (L–V 10:00–12:00).
     Sin API externa: se calcula con la hora del navegador y gobierna las
     píldoras [data-live-badge], el texto [data-live-text], el punto rojo del
     nav [data-live-dot] y el badge del reproductor. Además, un play/pause
     visual sobre cualquier [data-player] > [data-play-toggle] (prototipo).
     ============================================================ */
  (function radioState() {
    var SHOW = { name: 'El show de las mañanas', startH: 10, endH: 12, days: [1, 2, 3, 4, 5] };
    var DAY_NAMES = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
    function isOnAir(now) {
      return SHOW.days.indexOf(now.getDay()) !== -1 && now.getHours() >= SHOW.startH && now.getHours() < SHOW.endH;
    }
    function nextAirText(now) {
      if (SHOW.days.indexOf(now.getDay()) !== -1 && now.getHours() < SHOW.startH) return 'Hoy ' + pad(SHOW.startH) + ':00';
      var probe = new Date(now.getTime());
      for (var i = 1; i <= 7; i++) {
        probe.setDate(now.getDate() + i);
        if (SHOW.days.indexOf(probe.getDay()) !== -1) {
          return (i === 1 ? 'Mañana' : cap(DAY_NAMES[probe.getDay()])) + ' ' + pad(SHOW.startH) + ':00';
        }
      }
      return 'Lunes ' + pad(SHOW.startH) + ':00';
    }
    function q(sel, ctx) { return [].slice.call((ctx || document).querySelectorAll(sel)); }

    function apply() {
      var now = new Date(), onair = isOnAir(now), next = nextAirText(now);
      q('[data-live-dot]').forEach(function (el) { if (onair) el.removeAttribute('hidden'); else el.setAttribute('hidden', ''); });
      q('[data-live-badge]').forEach(function (el) {
        el.className = 'badge ' + (onair ? 'badge--live' : 'badge--soon');
        el.innerHTML = onair
          ? '<span class="dot" aria-hidden="true"></span>AL AIRE'
          : '<span class="dot" aria-hidden="true"></span>Próximo ' + next;
      });
      q('[data-live-text]').forEach(function (el) {
        el.innerHTML = onair
          ? '<strong>' + SHOW.name + '</strong> está al aire ahora · L–V 10:00 a.m.–12:00 m.'
          : '<strong>' + SHOW.name + '</strong> — Fuera de aire. Próxima emisión: ' + next + '.';
      });
    }
    apply();
    setInterval(apply, 60 * 1000);

    /* Play/pause visual del reproductor (prototipo, sin stream real). */
    q('[data-player]').forEach(function (scope) {
      q('[data-play-toggle]', scope).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var playing = scope.classList.toggle('playing');
          btn.setAttribute('aria-pressed', playing ? 'true' : 'false');
          btn.setAttribute('aria-label', (playing ? 'Pausar' : 'Reproducir') + ' radio en vivo');
        });
      });
    });
  })();

})();
