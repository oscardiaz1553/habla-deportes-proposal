/* ==========================================================================
   HABLA DEPORTES — JS de los componentes de portada.
   --------------------------------------------------------------------------
   Portado de propuesta/html/assets/js/home.js. El comportamiento es el del
   prototipo, ya probado (incluida la robustez táctil); lo que cambia aquí es
   el andamiaje, para que funcione como widget de Elementor:

     - Cada componente se inicializa por ELEMENTO, no una vez por página:
       un widget puede aparecer varias veces en la misma pantalla.
     - Las opciones llegan por atributos data-*, que rellena el widget desde
       los controles del panel.
     - Se engancha a `frontend/element_ready/*` para que el editor reinicie el
       componente cada vez que tocas un control y la vista previa sea real.
     - initX() es idempotente: marca el elemento y no vuelve a enlazar.
   ========================================================================== */
(function () {
	'use strict';

	var REDUCE = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/** Lee un data-* con valor por defecto. 'no'/'0'/'false' cuentan como false. */
	function opt(el, nombre, defecto) {
		var v = el.getAttribute('data-' + nombre);
		if (null === v || '' === v) return defecto;
		if ('no' === v || '0' === v || 'false' === v) return false;
		if ('yes' === v || '1' === v || 'true' === v) return true;
		return v;
	}

	function yaIniciado(el, marca) {
		if (el.getAttribute(marca) === '1') return true;
		el.setAttribute(marca, '1');
		return false;
	}

	/* ======================================================================
	   HERO: crossfade real con 2 capas + Ken Burns
	   ====================================================================== */
	function initHero(hero) {
		if (!hero || yaIniciado(hero, 'data-hd-hero-init')) return;

		var slides = [];
		try {
			slides = JSON.parse(hero.getAttribute('data-slides') || '[]');
		} catch (err) {
			slides = [];
		}
		if (!slides.length) return;

		var autoplay = opt(hero, 'autoplay', true);
		var intervalo = parseInt(opt(hero, 'interval', 6000), 10) || 6000;

		var layers = Array.prototype.slice.call(hero.querySelectorAll('[data-hd-hero-layer]'));
		var titleEl = hero.querySelector('[data-hd-hero-title]');
		var metaEl = hero.querySelector('.hd-hero__meta');
		var metaTimeEl = hero.querySelector('[data-hd-hero-time]');
		var playLink = hero.querySelector('[data-hd-hero-play]');
		var dots = Array.prototype.slice.call(hero.querySelectorAll('[data-hd-hero-dot]'));
		var current = 0;
		var active = 0; // índice (0/1) de la capa visible

		/* Reinicia una animación de entrada quitando y re-agregando la clase
		   (forzando reflow), para que dispare en cada cambio de slide. */
		function replay(el) {
			if (REDUCE || !el) return;
			el.classList.remove('is-enter');
			void el.offsetWidth; // reflow
			el.classList.add('is-enter');
		}

		function syncText(idx) {
			var s = slides[idx];
			if (!s) return;
			if (titleEl) titleEl.textContent = s.title;
			if (metaTimeEl) metaTimeEl.textContent = s.time;
			if (playLink && s.url) playLink.setAttribute('href', s.url);
			replay(titleEl);
			replay(metaEl);
			dots.forEach(function (d, di) {
				d.setAttribute('aria-current', di === idx ? 'true' : 'false');
			});
		}

		/* Precarga la imagen destino en la capa oculta ANTES de intercambiar la
		   opacidad, para no mostrar una imagen a medio cargar. */
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

			if (incoming.getAttribute('src') === s.img &&
				incoming.complete && incoming.naturalWidth > 0) {
				reveal();
				return;
			}
			var pre = new Image();
			pre.onload = pre.onerror = function () {
				incoming.src = s.img;
				reveal();
			};
			pre.src = s.img;
		}

		function render(i) {
			current = (i + slides.length) % slides.length;
			crossfade(current);
			syncText(current);
		}

		var timer = null;
		function stop() { if (timer) { clearInterval(timer); timer = null; } }
		function restart() {
			if (REDUCE || !autoplay) return;
			stop();
			timer = setInterval(function () { render(current + 1); }, intervalo);
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

		hero.addEventListener('mouseenter', stop);
		hero.addEventListener('mouseleave', restart);
		hero.addEventListener('focusin', stop);
		hero.addEventListener('focusout', restart);

		/* Swipe: el hero es crossfade (no hay track que arrastrar), así que el
		   gesto no desplaza en vivo; al soltar, si supera el umbral, cambia de
		   slide. No intercepta el scroll vertical (touch-action: pan-y). Un
		   swipe que acaba sobre el play no debe navegar. */
		var heroSlide = hero.querySelector('.hd-hero__slide');
		if (heroSlide) {
			var hPid = null, hSx = 0, hSy = 0, hDx = 0, hMode = '', heroSwiped = false;
			var hThreshold = function () { return Math.max(45, (heroSlide.clientWidth || 0) * 0.15); };

			var endHeroGesture = function (e, cancelled) {
				if (hPid === null || e.pointerId !== hPid) return;
				var wasSwipe = hMode === 'swipe';
				hPid = null; hMode = '';
				if (wasSwipe) {
					try { heroSlide.releasePointerCapture(e.pointerId); } catch (err) { /* no-op */ }
					if (!cancelled && Math.abs(hDx) >= hThreshold()) {
						heroSwiped = true;
						render(hDx < 0 ? current + 1 : current - 1);
					}
					setTimeout(function () { heroSwiped = false; }, 300);
				}
				restart();
			};

			heroSlide.addEventListener('pointerdown', function (e) {
				if (e.button !== 0 || hPid !== null) return;
				hPid = e.pointerId; hSx = e.clientX; hSy = e.clientY; hDx = 0; hMode = '';
				stop();
			});
			heroSlide.addEventListener('pointermove', function (e) {
				if (hPid === null || e.pointerId !== hPid) return;
				if (e.pointerType === 'mouse' && e.buttons === 0) { endHeroGesture(e, true); return; }
				var mx = e.clientX - hSx, my = e.clientY - hSy;
				if (!hMode) {
					if (Math.abs(mx) > 10 && Math.abs(mx) > Math.abs(my)) {
						hMode = 'swipe';
						try { heroSlide.setPointerCapture(hPid); } catch (err) { /* no-op */ }
					} else if (Math.abs(my) > 10) {
						hMode = 'scroll';
					}
				}
				if (hMode !== 'swipe') return;
				hDx = mx;
				if (e.cancelable) e.preventDefault();
			});
			window.addEventListener('pointerup', function (e) { endHeroGesture(e, false); });
			window.addEventListener('pointercancel', function (e) { endHeroGesture(e, true); });

			heroSlide.addEventListener('click', function (e) {
				if (!heroSwiped) return;
				heroSwiped = false;
				e.preventDefault();
				e.stopPropagation();
			}, true);
			heroSlide.addEventListener('pointerdown', function () { heroSwiped = false; }, true);
			heroSlide.addEventListener('dragstart', function (e) { e.preventDefault(); });
		}

		// La capa 0 ya muestra el slide 0 desde el HTML; precargamos la
		// siguiente para que el primer crossfade sea instantáneo.
		if (slides[1]) { var p = new Image(); p.src = slides[1].img; }
		syncText(0);
		restart();
	}

	/* ======================================================================
	   CENTRO DE PARTIDOS: slider con arrastre en vivo
	   ====================================================================== */
	function initMatchSlider(root) {
		if (!root || yaIniciado(root, 'data-hd-match-init')) return;

		var track = root.querySelector('[data-hd-match-track]');
		var slides = track ? Array.prototype.slice.call(track.children) : [];
		var dots = Array.prototype.slice.call(root.querySelectorAll('[data-hd-match-dot]'));
		if (!track || slides.length < 2) return;

		var autoplay = opt(root, 'autoplay', true);
		var intervalo = parseInt(opt(root, 'interval', 6000), 10) || 6000;

		var viewport = track.parentElement;
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
			if (REDUCE || !autoplay) return;
			stop();
			timer = setInterval(function () { go(idx + 1); }, intervalo);
		}

		dots.forEach(function (d, di) {
			d.addEventListener('click', function () { go(di); restart(); });
		});
		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', restart);
		root.addEventListener('focusin', stop);
		root.addEventListener('focusout', restart);

		/* Arrastre EN VIVO: el track sigue al dedo sin transición. Solo es
		   swipe si la intención es horizontal. Al soltar, si el recorrido
		   supera el 20% del ancho (mínimo 45px) se confirma el cambio. En los
		   extremos hay resistencia y no da la vuelta. */
		var DECIDE_PX = 10;
		var pointerId = null;
		var startX = 0, startY = 0, dx = 0;
		var mode = '';
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
				track.style.transition = '';
				var commit = 0;
				if (!cancelled && Math.abs(dx) >= threshold()) commit = dx < 0 ? 1 : -1;
				if (commit === 1 && idx < slides.length - 1) go(idx + 1);
				else if (commit === -1 && idx > 0) go(idx - 1);
				else go(idx);
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
			if (e.pointerType === 'mouse' && e.buttons === 0) { endGesture(e, true); return; }
			var mx = e.clientX - startX;
			var my = e.clientY - startY;
			if (!mode) {
				if (Math.abs(mx) > DECIDE_PX && Math.abs(mx) > Math.abs(my)) {
					mode = 'swipe';
					suppressClick = true;
					try { track.setPointerCapture(pointerId); } catch (err) { /* no-op */ }
					track.style.transition = 'none';
				} else if (Math.abs(my) > DECIDE_PX) {
					mode = 'scroll';
				}
			}
			if (mode !== 'swipe') return;
			dx = mx;
			if ((idx === 0 && dx > 0) || (idx === slides.length - 1 && dx < 0)) dx *= 0.35;
			track.style.transform = 'translateX(calc(' + (-idx * 100) + '% + ' + dx + 'px))';
			if (e.cancelable) e.preventDefault();
		});

		/* pointerup/cancel en WINDOW: si se suelta fuera del track, endGesture
		   igual corre y libera el estado. Sin esto el pointerId quedaba colgado
		   y bloqueaba el siguiente gesto. endGesture es idempotente. */
		window.addEventListener('pointerup', function (e) { endGesture(e, false); });
		window.addEventListener('pointercancel', function (e) { endGesture(e, true); });

		root.addEventListener('click', function (e) {
			if (!suppressClick) return;
			suppressClick = false;
			e.preventDefault();
			e.stopPropagation();
		}, true);
		root.addEventListener('pointerdown', function () { suppressClick = false; }, true);
		track.addEventListener('dragstart', function (e) { e.preventDefault(); });

		go(0);
		restart();
	}

	/* ======================================================================
	   TICKER: marquesina infinita
	   ----------------------------------------------------------------------
	   El CSS desplaza la pista un -50%. Para que eso caiga justo sobre el
	   inicio de la segunda copia, las dos copias viven en la MISMA pista.
	   Las copias son decorativas: fuera del lector de pantalla y del
	   tabulador, para no leerlas ni tabularlas dos veces.
	   ====================================================================== */
	function initTicker(track) {
		if (!track || yaIniciado(track, 'data-hd-ticker-init')) return;

		var originales = Array.prototype.slice.call(track.children);
		if (!originales.length) return;

		originales.forEach(function (item) {
			var copia = item.cloneNode(true);
			copia.setAttribute('aria-hidden', 'true');
			copia.setAttribute('tabindex', '-1');
			track.appendChild(copia);
		});
	}

	/* ======================================================================
	   Arranque
	   ====================================================================== */
	function initAll(scope) {
		var raiz = scope || document;
		raiz.querySelectorAll('[data-hd-hero]').forEach(initHero);
		raiz.querySelectorAll('[data-hd-match-slider]').forEach(initMatchSlider);
		raiz.querySelectorAll('[data-hd-ticker-track]').forEach(initTicker);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(); });
	} else {
		initAll();
	}

	/* Editor y frontend de Elementor: cada vez que un widget se (re)dibuja,
	   `frontend/element_ready` dispara con su ámbito. Sin esto, en el editor
	   tocas un control, Elementor repinta el widget y el componente se queda
	   muerto hasta recargar. */
	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) return;

		var enlazar = function (widget, init, selector) {
			elementorFrontend.hooks.addAction(
				'frontend/element_ready/' + widget + '.default',
				function ($scope) {
					var raiz = $scope && $scope[0] ? $scope[0] : null;
					if (!raiz) return;
					raiz.querySelectorAll(selector).forEach(init);
				}
			);
		};

		enlazar('hd-hero', initHero, '[data-hd-hero]');
		enlazar('hd-partidos', initMatchSlider, '[data-hd-match-slider]');
		enlazar('hd-ticker', initTicker, '[data-hd-ticker-track]');

		// Los shortcodes siguen funcionando dentro del widget Shortcode.
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/shortcode.default',
			function ($scope) {
				if ($scope && $scope[0]) initAll($scope[0]);
			}
		);
	});
})();
