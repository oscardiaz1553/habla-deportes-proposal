/* ==========================================================================
   HABLA DEPORTES — Megamenú (vanilla JS, sin dependencias)
   --------------------------------------------------------------------------
   Retoma la lógica ya resuelta del nav.js huérfano y la adapta al header navy
   actual. Convive con home.js (que gestiona el sticky, el lightbox y el
   toggle del menú hamburguesa); aquí SOLO añadimos:
     - Desktop: hover-intent + puente + delay de cierre para los desplegables.
     - Móvil: acordeón dentro de #mobile-menu (disparadores <button>).

   Temporizadores (megamenu-ux.md):
     · Abrir por hover ...... 120ms (cancelable si el cursor sale antes)
     · Abrir por foco/teclado. 0ms
     · Cerrar por hover ...... 280ms (cancelable si el cursor re-entra)
     · Cierre explícito ...... 0ms (clic fuera, Esc, clic en enlace, otro ítem)
   El puente geométrico (CSS) mantiene el cursor dentro de la región; el delay
   de 280ms es la red de seguridad para trayectorias en diagonal.
   ========================================================================== */
(function () {
  'use strict';

  var OPEN_DELAY = 120;
  var CLOSE_DELAY = 280;

  // Solo aplicamos hover-intent en dispositivos con puntero fino (mouse).
  // En táctil (hover:none) el disparador funciona como tap-to-toggle.
  var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  var header = document.querySelector('.site-header');
  var items = [].slice.call(document.querySelectorAll('.mm')); // <li> desplegables (desktop)

  var openItem = null;   // ítem actualmente abierto (uno a la vez)
  var openTimer = null;  // temporizador de apertura por hover
  var closeTimer = null; // temporizador de cierre por hover (compartido)

  function triggerOf(dd) { return dd.querySelector('.mm-trigger'); }
  function linksOf(dd) {
    var p = dd.querySelector('.mm-panel');
    return p ? [].slice.call(p.querySelectorAll('a[href]')) : [];
  }
  function syncHeader() {
    if (header) header.classList.toggle('mm-open', !!openItem);
  }

  function openDropdown(dd) {
    if (openItem === dd) {                       // ya abierto: solo cancela cierre
      if (closeTimer) { window.clearTimeout(closeTimer); closeTimer = null; }
      return;
    }
    if (openItem) closeDropdown(openItem);       // exclusivo: cierra el anterior al instante
    if (closeTimer) { window.clearTimeout(closeTimer); closeTimer = null; }
    dd.classList.add('is-open');
    var t = triggerOf(dd);
    if (t) t.setAttribute('aria-expanded', 'true');
    openItem = dd;
    syncHeader();
  }

  function closeDropdown(dd) {
    dd.classList.remove('is-open');
    var t = triggerOf(dd);
    if (t) t.setAttribute('aria-expanded', 'false');
    if (openItem === dd) openItem = null;
    syncHeader();
  }

  function closeAll() {
    if (openTimer) { window.clearTimeout(openTimer); openTimer = null; }
    if (closeTimer) { window.clearTimeout(closeTimer); closeTimer = null; }
    items.forEach(function (dd) {
      if (dd.classList.contains('is-open')) closeDropdown(dd);
    });
    openItem = null;
    syncHeader();
  }

  function scheduleOpen(dd) {
    if (openTimer) window.clearTimeout(openTimer);
    openTimer = window.setTimeout(function () { openTimer = null; openDropdown(dd); }, OPEN_DELAY);
  }
  function scheduleClose(dd) {
    if (closeTimer) window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(function () { closeTimer = null; closeDropdown(dd); }, CLOSE_DELAY);
  }

  items.forEach(function (dd) {
    var trigger = triggerOf(dd);
    if (!trigger) return;
    var links = linksOf(dd);

    /* ---- Hover-intent (solo puntero fino). El <li> engloba disparador +
       panel (el panel es descendiente del <li>), así que mouseenter/leave
       tratan ambos como UNA sola región. ---- */
    if (canHover) {
      dd.addEventListener('mouseenter', function () {
        if (closeTimer) { window.clearTimeout(closeTimer); closeTimer = null; } // re-entra: cancela cierre
        if (openItem === dd) return;
        scheduleOpen(dd);
      });
      dd.addEventListener('mouseleave', function () {
        if (openTimer) { window.clearTimeout(openTimer); openTimer = null; }    // pasó de largo: no abre
        if (dd.classList.contains('is-open')) scheduleClose(dd);
      });
    }

    /* ---- Clic en el disparador: toggle instantáneo (mouse + tap táctil).
       preventDefault para no navegar al href de degradación. ---- */
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      if (openTimer) { window.clearTimeout(openTimer); openTimer = null; }
      if (closeTimer) { window.clearTimeout(closeTimer); closeTimer = null; }
      if (dd.classList.contains('is-open')) closeDropdown(dd);
      else openDropdown(dd);
    });

    /* ---- Teclado en el disparador ---- */
    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openDropdown(dd);                 // foco/teclado: sin demora
        if (links[0]) links[0].focus();
      } else if (e.key === 'Escape') {
        if (dd.classList.contains('is-open')) { closeDropdown(dd); trigger.focus(); }
      }
    });

    /* ---- Teclado dentro del panel ---- */
    links.forEach(function (link, i) {
      link.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); (links[i + 1] || links[0]).focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); (links[i - 1] || links[links.length - 1]).focus(); }
        else if (e.key === 'Escape') { e.preventDefault(); closeDropdown(dd); trigger.focus(); }
        else if (e.key === 'Tab' && !e.shiftKey && i === links.length - 1) { closeDropdown(dd); }
      });
      // Clic en un enlace: cierre instantáneo (no pegajoso). No preventDefault: navega.
      link.addEventListener('click', function () { closeAll(); });
    });

    /* ---- Red de seguridad para teclado: si el foco sale del <li>, cerrar. ---- */
    dd.addEventListener('focusout', function () {
      window.setTimeout(function () {
        if (dd.classList.contains('is-open') && !dd.contains(document.activeElement)) {
          closeDropdown(dd);
        }
      }, 0);
    });
  });

  /* ---- Cierre explícito global: clic fuera + Escape ---- */
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.mm')) closeAll();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && openItem) {
      var t = triggerOf(openItem);
      closeAll();
      if (t) t.focus();
    }
  });

  /* ---- Acordeón dentro del menú móvil (#mobile-menu) --------------------
     El disparador es un <button.mm-acc-trigger> (no navega): togglea
     aria-expanded + .is-open en su grupo. No es exclusivo: abrir uno no cierra
     el otro. Los sub-enlaces son <a> reales; home.js ya cierra el menú móvil
     al hacer clic en cualquier <a> de #mobile-menu. ---- */
  var accTriggers = [].slice.call(document.querySelectorAll('.mm-acc-trigger'));
  accTriggers.forEach(function (tr) {
    var id = tr.getAttribute('aria-controls');
    var group = id ? document.getElementById(id) : null;
    if (!group) return;
    tr.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var willOpen = tr.getAttribute('aria-expanded') !== 'true';
      tr.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      group.classList.toggle('is-open', willOpen);
    });
  });
})();
