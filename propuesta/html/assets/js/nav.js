/* Diario Deportes — navegación compartida (index, artículo, categoría).
   - Menús desplegables «Más ▾»: hover + click + teclado, accesibles y consistentes.
   - Nav principal: único navbar, siempre visible (position:sticky en CSS, sin JS de scroll).
   - Menú móvil (hamburguesa): panel real con todos los enlaces (rastreable).
   Los enlaces viven SIEMPRE en el HTML; el JS solo abre/cierra. */
(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Desplegables ---------- */
  var dropdowns = [].slice.call(document.querySelectorAll('.has-dropdown'));

  // Fondo sólido del navbar mientras haya un desplegable abierto (click/teclado vía
  // .is-open; hover vía mouseenter/leave con 300ms de gracia, acompañando al panel).
  var navBar = document.getElementById('stickyNav');
  var navHoverTimer = null;
  function syncNavOpen() {
    if (!navBar) return;
    var anyOpen = dropdowns.some(function (dd) { return dd.classList.contains('is-open'); });
    navBar.classList.toggle('menu-open', anyOpen);
  }
  if (navBar) {
    dropdowns.forEach(function (dd) {
      if (!navBar.contains(dd)) return;
      dd.addEventListener('mouseenter', function () {
        if (navHoverTimer) { window.clearTimeout(navHoverTimer); navHoverTimer = null; }
        navBar.classList.add('menu-open');
      });
      dd.addEventListener('mouseleave', function () {
        if (navHoverTimer) window.clearTimeout(navHoverTimer);
        navHoverTimer = window.setTimeout(function () {
          navHoverTimer = null;
          syncNavOpen(); // conserva el fondo solo si algo sigue abierto por click
        }, 300);
      });
    });
  }
  function closeDropdown(dd) {
    // Cierre explícito (click/Escape/click fuera): sin el periodo de gracia de 300ms,
    // que es solo para el hover. .is-dismissed pone transition-delay:0 un instante.
    if (dd.classList.contains('is-open')) {
      dd.classList.add('is-dismissed');
      window.setTimeout(function () { dd.classList.remove('is-dismissed'); }, 400);
    }
    dd.classList.remove('is-open');
    var t = dd.querySelector('.nav-trigger');
    if (t) t.setAttribute('aria-expanded', 'false');
    syncNavOpen();
  }
  function closeAllDropdowns(except) {
    dropdowns.forEach(function (dd) { if (dd !== except) closeDropdown(dd); });
  }
  function openDropdown(dd) {
    closeAllDropdowns(dd);
    dd.classList.add('is-open');
    var t = dd.querySelector('.nav-trigger');
    if (t) t.setAttribute('aria-expanded', 'true');
    syncNavOpen();
  }

  dropdowns.forEach(function (dd) {
    var trigger = dd.querySelector('.nav-trigger');
    var links = [].slice.call(dd.querySelectorAll('.submenu a, .mega a'));
    if (!trigger) return;

    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (dd.classList.contains('is-open')) { closeDropdown(dd); }
      else { openDropdown(dd); }
    });

    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openDropdown(dd);
        if (links[0]) links[0].focus();
      } else if (e.key === 'Escape') {
        closeDropdown(dd);
      }
    });

    links.forEach(function (link, i) {
      link.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); (links[i + 1] || links[0]).focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); (links[i - 1] || links[links.length - 1]).focus(); }
        else if (e.key === 'Escape') { e.preventDefault(); closeDropdown(dd); trigger.focus(); }
        else if (e.key === 'Tab' && !e.shiftKey && i === links.length - 1) { closeDropdown(dd); }
      });
    });
  });

  // Cerrar al hacer click fuera
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.has-dropdown')) closeAllDropdowns(null);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAllDropdowns(null);
  });

  /* ---------- Menú móvil (hamburguesa) ---------- */
  var mobile = document.getElementById('mobileMenu');
  var toggles = [].slice.call(document.querySelectorAll('[data-menu-toggle]'));
  if (mobile && toggles.length) {
    var openMenu = function (state) {
      mobile.classList.toggle('open', state);
      document.body.style.overflow = state ? 'hidden' : '';
      toggles.forEach(function (b) { b.setAttribute('aria-expanded', state ? 'true' : 'false'); });
    };
    toggles.forEach(function (b) {
      b.setAttribute('aria-controls', 'mobileMenu');
      b.setAttribute('aria-expanded', 'false');
      b.addEventListener('click', function (e) { e.stopPropagation(); openMenu(!mobile.classList.contains('open')); });
    });
    var closeBtn = mobile.querySelector('.mm-close');
    if (closeBtn) closeBtn.addEventListener('click', function () { openMenu(false); });
    mobile.addEventListener('click', function (e) { if (e.target === mobile) openMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') openMenu(false); });
    [].slice.call(mobile.querySelectorAll('a')).forEach(function (a) {
      a.addEventListener('click', function () { openMenu(false); });
    });
  }

  /* ---------- Dropdowns colapsables DENTRO del menú móvil (FPC, «Más») ----------
     El padre es un <button.mm-trigger> (no navega): togglea aria-expanded + .is-open
     en su grupo. stopPropagation evita cerrar el panel; como es <button> y no <a>,
     el handler de cierre-al-click-en-<a> de arriba no lo afecta. Todos colapsados por
     defecto salvo el grupo que contenga el enlace activo (aria-current="page"). */
  var mmTriggers = [].slice.call(document.querySelectorAll('.mm-panel .mm-trigger'));
  // Abre el grupo que contenga el enlace activo (aria-current="page"). NO-destructivo:
  // solo añade .is-open, nunca cierra, así respeta lo que el usuario haya abierto.
  function openActiveMmGroup() {
    mmTriggers.forEach(function (trigger) {
      var id = trigger.getAttribute('aria-controls');
      var group = id ? document.getElementById(id) : null;
      if (group && group.querySelector('a[aria-current="page"]')) {
        trigger.setAttribute('aria-expanded', 'true');
        group.classList.add('is-open');
      }
    });
  }
  mmTriggers.forEach(function (trigger) {
    var id = trigger.getAttribute('aria-controls');
    var group = id ? document.getElementById(id) : null;
    if (!group) return;
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var willOpen = trigger.getAttribute('aria-expanded') !== 'true';
      trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      group.classList.toggle('is-open', willOpen); // no exclusivo: abrir uno no cierra el otro
    });
  });
  openActiveMmGroup(); // en carga (p. ej. categoria.html#liga-betplay abre el grupo FPC)
  // En categoria.html el hash cambia la sección activa sin recargar; re-sincroniza el grupo.
  window.addEventListener('hashchange', openActiveMmGroup);

  /* ---------- Nav principal: fondo azul solo al quedar "pegado" arriba ---------- */
  var sentinel = document.getElementById('navSentinel');
  var stickyNav = document.getElementById('stickyNav');
  if (sentinel && stickyNav && 'IntersectionObserver' in window) {
    var stuckIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        stickyNav.classList.toggle('is-stuck', !entry.isIntersecting);
      });
    }, { threshold: 0 });
    stuckIO.observe(sentinel);
  } else if (stickyNav) {
    stickyNav.classList.add('is-stuck'); // sin soporte de IO: fondo siempre visible (degradación segura)
  }

  /* ---------- Rotador de staff (Director / Editor General / Editora Digital) ---------- */
  var rotator = document.getElementById('staffRotator');
  if (rotator) {
    var staff = [
      { role: 'Director', name: 'Hugo Illera' },
      { role: 'Editor General', name: 'Fabián Buendía' },
      { role: 'Editora Digital', name: 'Lizeth Torres' }
    ];
    var roleEl = rotator.querySelector('.who-role');
    var nameEl = rotator.querySelector('.who-name');
    var rDots = [].slice.call(rotator.querySelectorAll('.dots button'));
    var rIdx = 0, rTimer = null;
    function renderStaff(i) {
      rIdx = (i + staff.length) % staff.length;
      var apply = function () {
        roleEl.textContent = staff[rIdx].role;
        nameEl.textContent = staff[rIdx].name;
        rDots.forEach(function (d, di) { d.classList.toggle('active', di === rIdx); });
        rotator.classList.remove('is-animating');
      };
      if (reduce) { apply(); return; }
      rotator.classList.add('is-animating');
      window.setTimeout(apply, 180);
    }
    function startStaff() {
      if (reduce) return;
      stopStaff();
      rTimer = window.setInterval(function () { renderStaff(rIdx + 1); }, 4000);
    }
    function stopStaff() { if (rTimer) { window.clearInterval(rTimer); rTimer = null; } }
    rDots.forEach(function (d) {
      d.addEventListener('click', function () { renderStaff(+d.dataset.i); stopStaff(); startStaff(); });
    });
    rotator.addEventListener('mouseenter', stopStaff);
    rotator.addEventListener('mouseleave', startStaff);
    startStaff();
  }

  /* ---------- Tagline del topbar: alterna entre dos mensajes con desvanecido ---------- */
  var tagline = document.getElementById('taglineRotator');
  if (tagline) {
    var taglineMsgs = [
      'Desde Barranquilla, el deporte a un ¡clic!',
      'El primer diario deportivo de la Costa Caribe.'
    ];
    var tgIdx = 0;
    if (!reduce) {
      window.setInterval(function () {
        tagline.classList.add('is-fading');
        window.setTimeout(function () {
          tgIdx = (tgIdx + 1) % taglineMsgs.length;
          tagline.textContent = taglineMsgs[tgIdx];
          tagline.classList.remove('is-fading');
        }, 240);
      }, 4000);
    }
  }
})();
