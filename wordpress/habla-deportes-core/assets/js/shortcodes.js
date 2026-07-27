/* ==========================================================================
   Habla Deportes Core — JS mínimo de los shortcodes.
   Solo se encola cuando [hd_video_player] se usa realmente en la página.

   Reproductor "facade": el contenedor [data-hd-player] muestra la portada
   con un botón de play. Al hacer CLIC se inyecta el <iframe> de YouTube con
   autoplay, reemplazando la portada. El iframe nunca se crea al cargar, así
   que YouTube no descarga nada (ni pone cookies) hasta que el visitante
   decide reproducir.

   Delegación en `document` a propósito: Elementor puede inyectar el
   shortcode después de este script (editor, popups, contenido diferido).
   ========================================================================== */
(function () {
	'use strict';

	function esIdValido(id) {
		return /^[A-Za-z0-9_-]{11}$/.test(id || '');
	}

	document.addEventListener('click', function (e) {
		var boton = e.target.closest ? e.target.closest('[data-hd-player] [data-youtube-id]') : null;
		if (!boton) {
			return;
		}

		var contenedor = boton.closest('[data-hd-player]');
		if (!contenedor || contenedor.classList.contains('is-playing')) {
			return;
		}

		var id = boton.getAttribute('data-youtube-id');
		if (!esIdValido(id)) {
			return;
		}

		e.preventDefault();

		var iframe = document.createElement('iframe');
		iframe.className = 'hd-player__iframe';
		iframe.src = 'https://www.youtube.com/embed/' + id +
			'?autoplay=1&rel=0&modestbranding=1&playsinline=1';
		iframe.title = boton.getAttribute('data-player-title') || 'Video de Habla Deportes';
		iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen');
		iframe.setAttribute('allowfullscreen', '');
		iframe.setAttribute('loading', 'eager');

		contenedor.innerHTML = '';
		contenedor.appendChild(iframe);
		contenedor.classList.add('is-playing');

		// El foco se mueve al reproductor para que teclado y lectores de
		// pantalla sigan en el mismo sitio tras reemplazar el botón.
		iframe.setAttribute('tabindex', '-1');
		iframe.focus();
	}, false);
})();
