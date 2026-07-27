<?php
/**
 * Habla Deportes Core — Shortcodes de la portada.
 * ---------------------------------------------------------------
 * Portados del prototipo aprobado (propuesta/html/index.html +
 * assets/css/main.css §6 y §7 + assets/js/home.js). Son las piezas
 * de la home que NO se pueden armar con widgets nativos de Elementor
 * porque llevan comportamiento propio:
 *
 *   [hd_hero]    -> hero de video: crossfade de 2 capas, Ken Burns,
 *                   flechas, dots, autoavance y swipe táctil.
 *   [hd_ticker]  -> franja de titulares en marquesina infinita.
 *
 * Las secciones que SÍ son rejillas normales (Mundo Junior, Clips,
 * Selección…) se hacen con Loop Grid / Loop Carousel de Elementor
 * sobre el CPT `video`; no necesitan shortcode.
 *
 * DIFERENCIAS DELIBERADAS respecto al prototipo:
 *   - El botón play NO abre un lightbox: enlaza a la ficha del video.
 *     En WordPress la ficha ya existe (plantilla "Habla Deportes —
 *     Video") y allí está el reproductor, la descripción y el CTA.
 *   - El hero NO lleva el margen negativo que lo metía bajo el header
 *     transparente del prototipo. El header aquí lo controla Elementor;
 *     si se hace transparente, ese margen se añade desde el editor.
 *
 * Patrón de assets: se REGISTRAN siempre y solo se ENCOLAN cuando el
 * shortcode se usa de verdad (igual que el resto del plugin).
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'hd-home', HDC_URL . 'assets/css/home.css', array(), HDC_VERSION );
	wp_register_script( 'hd-home', HDC_URL . 'assets/js/home.js', array(), HDC_VERSION, true );
} );

/**
 * Últimos videos publicados que sirven para la portada: los que tienen
 * ID de YouTube. Devuelve array de WP_Post.
 *
 * @param int $cantidad Cuántos como máximo.
 * @return WP_Post[]
 */
function hd_home_latest_videos( $cantidad ) {
	$cantidad = max( 1, (int) $cantidad );

	return get_posts( array(
		'post_type'      => 'video',
		'post_status'    => 'publish',
		'posts_per_page' => $cantidad,
		'meta_key'       => '_hd_youtube_id',
		'no_found_rows'  => true,
	) );
}

/**
 * URL de la imagen destacada, con la miniatura de YouTube como respaldo
 * si el post no tiene imagen (p. ej. si falló la descarga al importar).
 *
 * @param int    $post_id ID de la entrada.
 * @param string $size    Tamaño registrado de WordPress.
 * @return string URL, o cadena vacía si no hay nada que mostrar.
 */
function hd_home_thumb_url( $post_id, $size = 'large' ) {
	$url = get_the_post_thumbnail_url( $post_id, $size );
	if ( $url ) {
		return $url;
	}

	$yt = hd_get_youtube_id( $post_id );

	return $yt ? 'https://i.ytimg.com/vi/' . rawurlencode( $yt ) . '/hqdefault.jpg' : '';
}

/**
 * Tiempo relativo en castellano: "Hace 2 horas", "Hace 3 días".
 * A partir de una semana pasa a fecha corta ("12 jul 2026"), que se lee
 * mejor que "Hace 23 días".
 * ---------------------------------------------------------------
 * NO usa human_time_diff() ni date_i18n() a propósito: ambas siguen el
 * idioma de WordPress, y el sitio está en inglés (devolvían "Hace 32
 * minutes"). El front es en castellano pase lo que pase con el ajuste
 * de idioma del panel, así que los textos van fijos aquí.
 *
 * @param int $post_id ID de la entrada.
 * @return string
 */
function hd_home_hace( $post_id ) {
	$publicado = get_post_time( 'U', true, $post_id );
	if ( ! $publicado ) {
		return '';
	}

	$seg = time() - (int) $publicado;
	if ( $seg < 0 ) {
		$seg = 0; // programado a futuro: tratar como recién publicado.
	}

	if ( $seg >= WEEK_IN_SECONDS ) {
		$meses = array( 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic' );
		$fecha = get_post_time( 'j|n|Y', false, $post_id );
		list( $dia, $mes, $anio ) = explode( '|', $fecha );

		return $dia . ' ' . $meses[ (int) $mes - 1 ] . ' ' . $anio;
	}

	if ( $seg < MINUTE_IN_SECONDS ) {
		return 'Hace instantes';
	}

	if ( $seg < HOUR_IN_SECONDS ) {
		$n = (int) floor( $seg / MINUTE_IN_SECONDS );
		return 'Hace ' . $n . ( 1 === $n ? ' minuto' : ' minutos' );
	}

	if ( $seg < DAY_IN_SECONDS ) {
		$n = (int) floor( $seg / HOUR_IN_SECONDS );
		return 'Hace ' . $n . ( 1 === $n ? ' hora' : ' horas' );
	}

	$n = (int) floor( $seg / DAY_IN_SECONDS );
	return 'Hace ' . $n . ( 1 === $n ? ' día' : ' días' );
}

/* ---------------------------------------------------------------
 * [hd_hero cantidad="3"]
 * ------------------------------------------------------------- */
add_shortcode( 'hd_hero', 'hd_hero_shortcode' );
function hd_hero_shortcode( $atts ) {
	$a        = shortcode_atts( array( 'cantidad' => 3 ), $atts );
	$cantidad = max( 2, (int) $a['cantidad'] ); // el crossfade necesita 2 capas.

	$videos = hd_home_latest_videos( $cantidad );

	$slides = array();
	foreach ( $videos as $v ) {
		$img = hd_home_thumb_url( $v->ID, 'large' );
		if ( '' === $img ) {
			continue;
		}
		$slides[] = array(
			'img'   => $img,
			'alt'   => get_the_title( $v ),
			'title' => get_the_title( $v ),
			'time'  => hd_home_hace( $v->ID ),
			'url'   => get_permalink( $v ),
		);
	}

	// Sin al menos dos videos utilizables no hay hero que pintar.
	if ( count( $slides ) < 2 ) {
		return current_user_can( 'edit_posts' )
			? '<p class="hd-aviso">Hero: hacen falta al menos 2 videos con imagen. Corre el importador en Videos → Importar de YouTube.</p>'
			: '';
	}

	wp_enqueue_style( 'hd-home' );
	wp_enqueue_script( 'hd-home' );

	$s0 = $slides[0];
	$s1 = $slides[1];

	ob_start();
	?>
	<section class="hd-hero" data-hd-hero aria-label="Video destacado"
		data-slides="<?php echo esc_attr( wp_json_encode( $slides ) ); ?>">
		<div class="hd-hero__slide">
			<?php // Dos capas apiladas: el crossfade real lo hace home.js. ?>
			<div class="hd-hero__layers">
				<img class="hd-hero__img is-active" data-hd-hero-layer
					src="<?php echo esc_url( $s0['img'] ); ?>"
					fetchpriority="high" alt="<?php echo esc_attr( $s0['alt'] ); ?>">
				<img class="hd-hero__img" data-hd-hero-layer
					src="<?php echo esc_url( $s1['img'] ); ?>"
					loading="lazy" alt="">
			</div>
			<div class="hd-hero__scrim" aria-hidden="true"></div>

			<button class="hd-hero__arrow hd-hero__arrow--prev" type="button" data-hd-hero-prev aria-label="Video anterior">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
			</button>
			<button class="hd-hero__arrow hd-hero__arrow--next" type="button" data-hd-hero-next aria-label="Siguiente video">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
			</button>

			<a class="hd-play-btn hd-hero__play" data-hd-hero-play
				href="<?php echo esc_url( $s0['url'] ); ?>"
				aria-label="Ver el video destacado">
				<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
			</a>

			<div class="hd-hero__content">
				<h2 class="hd-hero__title" data-hd-hero-title><?php echo esc_html( $s0['title'] ); ?></h2>
				<p class="hd-hero__meta">
					<svg class="hd-yt-mark" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>
					<span>YouTube</span>
					<span class="hd-meta-sep" aria-hidden="true">•</span>
					<span data-hd-hero-time><?php echo esc_html( $s0['time'] ); ?></span>
				</p>
				<div class="hd-hero__dots">
					<?php foreach ( $slides as $i => $slide ) : ?>
						<button class="hd-hero__dot" type="button" data-hd-hero-dot
							aria-current="<?php echo 0 === $i ? 'true' : 'false'; ?>"
							aria-label="Video <?php echo esc_attr( $i + 1 ); ?>"></button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* ---------------------------------------------------------------
 * [hd_ticker cantidad="8" etiqueta="Noticias"]
 * ------------------------------------------------------------- */
add_shortcode( 'hd_ticker', 'hd_ticker_shortcode' );
function hd_ticker_shortcode( $atts ) {
	$a = shortcode_atts( array(
		'cantidad' => 8,
		'etiqueta' => 'Noticias',
	), $atts );

	$videos = hd_home_latest_videos( $a['cantidad'] );
	if ( empty( $videos ) ) {
		return '';
	}

	wp_enqueue_style( 'hd-home' );
	wp_enqueue_script( 'hd-home' );

	ob_start();
	?>
	<div class="hd-ticker" aria-label="Últimos videos">
		<span class="hd-ticker__label"><span class="hd-live-dot" aria-hidden="true"></span><?php echo esc_html( $a['etiqueta'] ); ?></span>
		<div class="hd-ticker__viewport">
			<div class="hd-ticker__track" data-hd-ticker-track>
				<?php foreach ( $videos as $v ) : ?>
					<a class="hd-ticker__item" href="<?php echo esc_url( get_permalink( $v ) ); ?>">
						<span class="hd-ticker__cat">Videos</span>
						<span class="hd-ticker__headline"><?php echo esc_html( get_the_title( $v ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
