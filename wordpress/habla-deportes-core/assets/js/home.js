/* ==========================================================================
   HABLA DEPORTES — JS de los shortcodes de portada.
   --------------------------------------------------------------------------
   Portado de propuesta/html/assets/js/home.js (módulos "Hero slider" y
   "Ticker"), con los selectores renombrados al prefijo `hd-` que usan los
   shortcodes del plugin. El código del prototipo ya estaba probado, incluida
   la robustez táctil; se porta tal cual y solo cambian los selectores.

   Diferencia respecto al prototipo: el botón play es un enlace a la ficha
   del video, no un disparador de lightbox. Al cambiar de slide se actualiza
   su href.
   ========================================================================== */
(function () {
	'use strict';

	/* ---- Hero: crossfade real con 2 capas + Ken Burns ------------------ */
	var hero = document.querySelector('[data-hd-hero]');
	if (hero) {
		var slides = [];
		try {
			slides = JSON.parse(hero.getAttribute('data-slides') || '[]');
		} catch (err) {
			slides = [];
		}

		var layers = Array.prototype.slice.call(hero.querySelectorAll('[data-hd-hero-layer]'));
		var titleEl = hero.querySelector('[data-hd-hero-title]');
		var metaEl = hero.querySelector('.hd-hero__meta');
		var metaTimeEl = hero.querySelector('[data-hd-hero-time]');
		var playLink = hero.querySelector('[data-hd-hero-play]');
		var dots = Array.prototype.slice.call(hero.querySelectorAll('[data-hd-hero-dot]'));
		var current = 0;
		var active = 0; // índice (0/1) de la capa visible
		var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		/* Reinicia una animación CSS de entrada quitando y re-agregando la
		   clase (forzando reflow), para que dispare en cada cambio de slide. */
		function replay(el) {
			if (reduce || !el) return;
			el.classList.remove('is-enter');
			void el.offsetWidth; // reflow
			el.classList.add('is-enter');
		}

		function syncText(idx) {
			var s = slides[idx];
			if (!s) return;
			if (titleEl) titleEl.textContent = s.title;
			if (metaTimeEl) metaTimeEl.textContent = s.time;
			// El play del hero apunta al video del slide activo.
			if (playLink && s.url) playLink.setAttribute('href', s.url);
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
			if (!incoming || !s) return;

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

		hero.querySelectorAll('[data-hd-hero-prev]').forEach(function (b) {
			b.addEventListener('click', function () { render(current - 1); restart(); });
		});
		hero.querySelectorAll('[data-hd-hero-next]').forEach(function (b) {
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

		// Pausar el autoavance al pasar el cursor o al enfocar dentro del hero.
		hero.addEventListener('mouseenter', stop);
		hero.addEventListener('mouseleave', restart);
		hero.addEventListener('focusin', stop);
		hero.addEventListener('focusout', restart);

		/* ---- Swipe táctil ------------------------------------------------
		   El hero es crossfade (no hay track que arrastrar), así que el gesto
		   NO desplaza en vivo: al soltar, si el recorrido horizontal supera el
		   umbral, dispara next (izq) / prev (der). No intercepta el scroll
		   vertical (touch-action: pan-y en el CSS). Un swipe que termina sobre
		   el play NO debe abrir el video: se suprime el click resultante.
		   Robustez: captura del puntero, pointerup en window y aborto si el
		   ratón pierde el botón por el camino. */
		var heroSlide = hero.querySelector('.hd-hero__slide');
		if (heroSlide) {
			var hPid = null, hSx = 0, hSy = 0, hDx = 0, hMode = '', heroSwiped = false;
			var hThreshold = function () { return Math.max(45, (heroSlide.clientWidth || 0) * 0.15); };

			function endHeroGesture(e, cancelled) {
				if (hPid === null || e.pointerId !== hPid) return;
				var wasSwipe = hMode === 'swipe';
				hPid = null; hMode = '';
				if (wasSwipe) {
					try { heroSlide.releasePointerCapture(e.pointerId); } catch (err) { /* no-op */ }
					if (!cancelled && Math.abs(hDx) >= hThreshold()) {
						heroSwiped = true;               // hubo swipe: anular el click resultante
						render(hDx < 0 ? current + 1 : current - 1);
					}
					setTimeout(function () { heroSwiped = false; }, 300);
				}
				restart();
			}

			heroSlide.addEventListener('pointerdown', function (e) {
				if (e.button !== 0 || hPid !== null) return;
				hPid = e.pointerId; hSx = e.clientX; hSy = e.clientY; hDx = 0; hMode = '';
				stop();                                  // pausa el autoavance durante el gesto
			});
			heroSlide.addEventListener('pointermove', function (e) {
				if (hPid === null || e.pointerId !== hPid) return;
				// Ratón: si el botón ya no está pulsado, aborta.
				if (e.pointerType === 'mouse' && e.buttons === 0) { endHeroGesture(e, true); return; }
				var mx = e.clientX - hSx, my = e.clientY - hSy;
				if (!hMode) {
					if (Math.abs(mx) > 10 && Math.abs(mx) > Math.abs(my)) {
						hMode = 'swipe';
						try { heroSlide.setPointerCapture(hPid); } catch (err) { /* no-op */ }
					} else if (Math.abs(my) > 10) {
						hMode = 'scroll';                // gesto vertical: dejar scrollear
					}
				}
				if (hMode !== 'swipe') return;
				hDx = mx;
				if (e.cancelable) e.preventDefault();    // evita gestos horizontales del navegador
			});
			window.addEventListener('pointerup', function (e) { endHeroGesture(e, false); });
			window.addEventListener('pointercancel', function (e) { endHeroGesture(e, true); });

			// Tras un swipe, suprime el click para no navegar sin querer.
			heroSlide.addEventListener('click', function (e) {
				if (!heroSwiped) return;
				heroSwiped = false;
				e.preventDefault();
				e.stopPropagation();
			}, true);
			// Un gesto nuevo limpia la bandera residual (los taps navegan normal).
			heroSlide.addEventListener('pointerdown', function () { heroSwiped = false; }, true);
			// Sin arrastre nativo de imágenes.
			heroSlide.addEventListener('dragstart', function (e) { e.preventDefault(); });
		}

		if (slides.length) {
			// La capa 0 ya muestra el slide 0 desde el HTML; precargamos el
			// siguiente para que el primer crossfade sea instantáneo.
			if (slides[1]) { var p = new Image(); p.src = slides[1].img; }
			syncText(0);
			restart();
		}
	}

	/* ---- Centro de Partidos: slider con arrastre en vivo ---------------
	   Track con translateX; dots, autoavance y arrastre. Se pausa al pasar
	   el cursor, al enfocar dentro y durante el gesto. Bajo
	   prefers-reduced-motion no hay autoavance y el cambio es directo (la
	   transición la anula el CSS). */
	document.querySelectorAll('[data-hd-match-slider]').forEach(function (root) {
		var track = root.querySelector('[data-hd-match-track]');
		var slides = track ? Array.prototype.slice.call(track.children) : [];
		var dots = Array.prototype.slice.call(root.querySelectorAll('[data-hd-match-dot]'));
		if (!track || slides.length < 2) return;

		var viewport = track.parentElement;
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

		/* Arrastre EN VIVO con Pointer Events: el track sigue al dedo sin
		   transición. Solo es swipe si la intención es horizontal; un gesto
		   vertical no se intercepta y la página scrollea normal. Al soltar,
		   si el recorrido supera el 20% del ancho (mínimo 45px) se confirma
		   el cambio; si no, vuelve al slide actual. En los extremos hay
		   resistencia y no da la vuelta (los dots y el autoavance sí). */
		var DECIDE_PX = 10;
		var pointerId = null;
		var startX = 0, startY = 0, dx = 0;
		var mode = '';                 // '' indeciso | 'swipe' | 'scroll'
		var suppressClick = false;

		function threshold() {
			var w = (viewport && viewport.clientWidth) || track.clientWidth || 0;
			return Math.max(45, w * 0.2);
		}

		function endGesture(e, cancelled) {
			if (pointerId === null || e.pointerId !== pointerId) return;
			var wasSwipe = mode === 'swipe';
			pointerId = null;
			mode = '';
			if (wasSwipe) {
				try { track.releasePointerCapture(e.pointerId); } catch (err) { /* no-op */ }
				track.style.transition = '';       // restaurar el snap animado
				var commit = 0;
				if (!cancelled && Math.abs(dx) >= threshold()) commit = dx < 0 ? 1 : -1;
				if (commit === 1 && idx < slides.length - 1) go(idx + 1);
				else if (commit === -1 && idx > 0) go(idx - 1);
				else go(idx);                      // bajo el umbral o extremo: volver
				setTimeout(function () { suppressClick = false; }, 300);
			}
			restart();
		}

		track.addEventListener('pointerdown', function (e) {
			if (e.button !== 0 || pointerId !== null) return;
			pointerId = e.pointerId;
			startX = e.clientX;
			startY = e.clientY;
			dx = 0;
			mode = '';
			stop();
		});

		track.addEventListener('pointermove', function (e) {
			if (pointerId === null || e.pointerId !== pointerId) return;
			// Ratón: si el botón ya no está pulsado, aborta en vez de arrastrar.
			if (e.pointerType === 'mouse' && e.buttons === 0) { endGesture(e, true); return; }
			var mx = e.clientX - startX;
			var my = e.clientY - startY;
			if (!mode) {
				if (Math.abs(mx) > DECIDE_PX && Math.abs(mx) > Math.abs(my)) {
					mode = 'swipe';
					suppressClick = true;          // hubo arrastre: anular el click del <a>
					try { track.setPointerCapture(pointerId); } catch (err) { /* no-op */ }
					track.style.transition = 'none';
				} else if (Math.abs(my) > DECIDE_PX) {
					mode = 'scroll';
				}
			}
			if (mode !== 'swipe') return;
			dx = mx;
			// Resistencia en los extremos: el arrastre no da la vuelta circular.
			if ((idx === 0 && dx > 0) || (idx === slides.length - 1 && dx < 0)) dx *= 0.35;
			track.style.transform = 'translateX(calc(' + (-idx * 100) + '% + ' + dx + 'px))';
			if (e.cancelable) e.preventDefault();
		});

		/* pointerup/cancel en WINDOW, no solo en el track: si se suelta fuera
		   (arrastre que sale del banner), endGesture igual corre y libera el
		   estado. Sin esto el pointerId quedaba colgado y bloqueaba el
		   siguiente gesto. endGesture es idempotente. */
		window.addEventListener('pointerup', function (e) { endGesture(e, false); });
		window.addEventListener('pointercancel', function (e) { endGesture(e, true); });

		// Un tap navega normal; tras un arrastre real, el click se anula.
		root.addEventListener('click', function (e) {
			if (!suppressClick) return;
			suppressClick = false;
			e.preventDefault();
			e.stopPropagation();
		}, true);
		// Un gesto nuevo limpia la bandera residual (p. ej. tap en un dot).
		root.addEventListener('pointerdown', function () { suppressClick = false; }, true);
		// Sin arrastre nativo de <a>/<img>, que rompería el gesto con ratón.
		track.addEventListener('dragstart', function (e) { e.preventDefault(); });

		go(0);
		restart();
	});

	/* ---- Ticker: duplicar la pista para un bucle sin costuras ---------- */
	var track = document.querySelector('[data-hd-ticker-track]');
	if (track && track.parentNode) {
		var clone = track.cloneNode(true);
		clone.setAttribute('aria-hidden', 'true');
		clone.removeAttribute('data-hd-ticker-track');
		track.parentNode.appendChild(clone);
	}
})();
