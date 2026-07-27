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
 * Últimas piezas para el hero, según la fuente elegida.
 * ---------------------------------------------------------------
 * `video`  solo videos del canal (deben tener ID de YouTube).
 * `post`   solo entradas: noticias y notas escritas por la redacción.
 * `ambos`  lo más reciente de las dos, mezclado por fecha.
 *
 * Existe porque las portadas de YouTube son de baja resolución y sus
 * títulos, largos y en mayúsculas: para una pieza tan grande como el
 * hero, una nota con foto propia y titular escrito queda mejor.
 *
 * @param int    $cantidad Cuántas como máximo.
 * @param string $fuente   video|post|ambos.
 * @return WP_Post[]
 */
function hd_home_latest_destacados( $cantidad, $fuente = 'video', $seleccion = 'recientes' ) {
	$cantidad = max( 1, (int) $cantidad );

	/* Marcadas para portada, y si no llegan a la cantidad pedida se
	   completa con lo último publicado. Rellenar en vez de quedarse corto
	   es deliberado: el hero necesita 2 piezas como mínimo para el fundido,
	   y la portada no puede romperse porque ese día solo se marcara una. */
	if ( 'destacados' === $seleccion ) {
		$marcadas = hd_destacados( $cantidad, $fuente );

		if ( count( $marcadas ) >= $cantidad ) {
			return $marcadas;
		}

		$ya      = wp_list_pluck( $marcadas, 'ID' );
		$relleno = hd_home_latest_destacados( $cantidad + count( $ya ), $fuente, 'recientes' );

		foreach ( $relleno as $p ) {
			if ( count( $marcadas ) >= $cantidad ) {
				break;
			}
			if ( ! in_array( $p->ID, $ya, true ) ) {
				$marcadas[] = $p;
			}
		}

		return $marcadas;
	}

	if ( 'post' === $fuente ) {
		return get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $cantidad,
			'no_found_rows'  => true,
		) );
	}

	if ( 'ambos' === $fuente ) {
		// Sin meta_key: exigir el ID de YouTube dejaría fuera las entradas.
		return get_posts( array(
			'post_type'      => array( 'video', 'post' ),
			'post_status'    => 'publish',
			'posts_per_page' => $cantidad,
			'no_found_rows'  => true,
		) );
	}

	return hd_home_latest_videos( $cantidad );
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
	$a = shortcode_atts( array(
		'cantidad'  => 3,
		'fuente'    => 'video',      // video | post | ambos
		'seleccion' => 'destacados', // destacados | recientes
		'autoplay'  => 'yes',
		'interval'  => 6000,
		'flechas'   => 'yes',
		'dots'      => 'yes',
	), $atts );

	return hd_hero_render( $a );
}

/**
 * Render del hero. Lo comparten el shortcode [hd_hero] y el widget de
 * Elementor `hd-hero`, para que no haya dos maquetaciones que mantener.
 *
 * @param array $a cantidad, autoplay, interval, flechas, dots.
 * @return string HTML.
 */
function hd_hero_render( $a ) {
	$a = wp_parse_args( $a, array(
		'cantidad'  => 3,
		'fuente'    => 'video',
		'seleccion' => 'destacados',
		'autoplay'  => 'yes',
		'interval'  => 6000,
		'flechas'   => 'yes',
		'dots'      => 'yes',
	) );

	$cantidad  = max( 2, (int) $a['cantidad'] ); // el crossfade necesita 2 capas.
	$fuente    = in_array( $a['fuente'], array( 'video', 'post', 'ambos' ), true ) ? $a['fuente'] : 'video';
	$seleccion = 'recientes' === $a['seleccion'] ? 'recientes' : 'destacados';
	$autoplay = in_array( $a['autoplay'], array( 'yes', '1', 1, true ), true ) ? 'yes' : 'no';
	$interval = max( 1000, (int) $a['interval'] );
	$flechas  = in_array( $a['flechas'], array( 'yes', '1', 1, true ), true );
	$dots     = in_array( $a['dots'], array( 'yes', '1', 1, true ), true );

	$piezas = hd_home_latest_destacados( $cantidad, $fuente, $seleccion );

	$slides = array();
	foreach ( $piezas as $v ) {
		$img = hd_home_thumb_url( $v->ID, 'full' );
		if ( '' === $img ) {
			continue; // sin imagen no hay slide que pintar.
		}
		$slides[] = array(
			'img'   => $img,
			'alt'   => get_the_title( $v ),
			'title' => get_the_title( $v ),
			'time'  => hd_home_hace( $v->ID ),
			'url'   => get_permalink( $v ),
			// El play y el sello de YouTube solo tienen sentido en un video.
			'video' => 'video' === get_post_type( $v ),
		);
	}

	if ( count( $slides ) < 2 ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		$que = array(
			'video' => 'videos',
			'post'  => 'entradas',
			'ambos' => 'videos o entradas',
		);

		return '<p class="hd-aviso">Hero: hacen falta al menos 2 ' . esc_html( $que[ $fuente ] )
			. ' con imagen destacada. Cambia la fuente en el panel del widget o publica más contenido.</p>';
	}

	wp_enqueue_style( 'hd-home' );
	wp_enqueue_script( 'hd-home' );

	$s0 = $slides[0];
	$s1 = $slides[1];

	ob_start();
	?>
	<section class="hd-hero" data-hd-hero aria-label="Video destacado"
		data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
		data-interval="<?php echo esc_attr( $interval ); ?>"
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

			<?php if ( $flechas ) : ?>
				<button class="hd-hero__arrow hd-hero__arrow--prev" type="button" data-hd-hero-prev aria-label="Video anterior">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
				</button>
				<button class="hd-hero__arrow hd-hero__arrow--next" type="button" data-hd-hero-next aria-label="Siguiente video">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
				</button>
			<?php endif; ?>

			<a class="hd-play-btn hd-hero__play<?php echo $s0['video'] ? '' : ' is-oculto'; ?>" data-hd-hero-play
				href="<?php echo esc_url( $s0['url'] ); ?>"
				aria-label="Ver la pieza destacada">
				<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
			</a>

			<div class="hd-hero__content">
				<h2 class="hd-hero__title" data-hd-hero-title><?php echo esc_html( $s0['title'] ); ?></h2>
				<p class="hd-hero__meta">
					<span class="hd-hero__fuente<?php echo $s0['video'] ? '' : ' is-oculto'; ?>" data-hd-hero-fuente>
						<svg class="hd-yt-mark" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>
						<span>YouTube</span>
						<span class="hd-meta-sep" aria-hidden="true">•</span>
					</span>
					<span data-hd-hero-time><?php echo esc_html( $s0['time'] ); ?></span>
				</p>
				<?php if ( $dots ) : ?>
					<div class="hd-hero__dots">
						<?php foreach ( $slides as $i => $slide ) : ?>
							<button class="hd-hero__dot" type="button" data-hd-hero-dot
								aria-current="<?php echo 0 === $i ? 'true' : 'false'; ?>"
								aria-label="Video <?php echo esc_attr( $i + 1 ); ?>"></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* ---------------------------------------------------------------
 * [hd_partidos cantidad="5"]
 * ---------------------------------------------------------------
 * Carrusel de próximos partidos, alimentado por el CPT `partido`.
 * Medidas y comportamiento del prototipo (main.css §[1], spec en
 * propuesta/centro-partidos-spec.md): banner a sangre con overlay,
 * escudos por term meta, dots y arrastre en vivo con Pointer Events.
 *
 * IMPORTANTE: esto pinta SOLO el slider, no la retícula de dos
 * columnas con el panel de publicidad que tiene el prototipo. El
 * anuncio es material del cliente y su sitio natural es un widget
 * Imagen de Elementor al lado, dentro de un contenedor a 2.9fr / 1fr.
 * El plugin pone el comportamiento; Elementor, la maquetación.
 * ------------------------------------------------------------- */
add_shortcode( 'hd_partidos', 'hd_partidos_shortcode' );
function hd_partidos_shortcode( $atts ) {
	$a = shortcode_atts( array(
		'cantidad' => 5,
		'autoplay' => 'yes',
		'interval' => 6000,
		'dots'     => 'yes',
	), $atts );

	return hd_partidos_render( $a );
}

/**
 * Render del Centro de Partidos, compartido por el shortcode y el widget
 * de Elementor `hd-partidos`.
 *
 * @param array $a cantidad, autoplay, interval, dots.
 * @return string HTML.
 */
function hd_partidos_render( $a ) {
	$a = wp_parse_args( $a, array(
		'cantidad' => 5,
		'autoplay' => 'yes',
		'interval' => 6000,
		'dots'     => 'yes',
	) );

	$autoplay = in_array( $a['autoplay'], array( 'yes', '1', 1, true ), true ) ? 'yes' : 'no';
	$interval = max( 1000, (int) $a['interval'] );
	$dots     = in_array( $a['dots'], array( 'yes', '1', 1, true ), true );

	$partidos = function_exists( 'hd_partidos_proximos' )
		? hd_partidos_proximos( max( 1, (int) $a['cantidad'] ) )
		: array();

	if ( empty( $partidos ) ) {
		return current_user_can( 'edit_posts' )
			? '<p class="hd-aviso">Centro de Partidos: no hay partidos con fecha futura. Añádelos en Partidos → Añadir partido.</p>'
			: '';
	}

	wp_enqueue_style( 'hd-home' );
	wp_enqueue_script( 'hd-home' );

	ob_start();
	?>
	<div class="hd-match-slider" data-hd-match-slider
		data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
		data-interval="<?php echo esc_attr( $interval ); ?>">
		<div class="hd-match-slider__viewport">
			<div class="hd-match-slider__track" data-hd-match-track>
				<?php foreach ( $partidos as $pid ) : ?>
					<?php hd_render_match_banner( $pid ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( $dots && count( $partidos ) > 1 ) : ?>
			<div class="hd-match-slider__dots" aria-label="Seleccionar partido">
				<?php foreach ( $partidos as $i => $pid ) : ?>
					<?php hd_render_match_dot( $pid, $i ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** Icono de calendario del prototipo. */
function hd_svg_cal() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg>';
}

/** Icono de chincheta del prototipo. */
function hd_svg_pin() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
}

/**
 * Un banner de partido. Si la entrada tiene imagen destacada se usa a
 * sangre de fondo; si no, queda el navy oscuro de respaldo del CSS.
 *
 * @param int $post_id ID de la entrada `partido`.
 */
function hd_render_match_banner( $post_id ) {
	$local_id     = (int) get_post_meta( $post_id, '_hd_local', true );
	$visitante_id = (int) get_post_meta( $post_id, '_hd_visitante', true );
	$torneo_id    = (int) get_post_meta( $post_id, '_hd_torneo', true );
	$fecha        = (string) get_post_meta( $post_id, '_hd_fecha', true );
	$hora         = (string) get_post_meta( $post_id, '_hd_hora', true );
	$estadio      = (string) get_post_meta( $post_id, '_hd_estadio', true );
	$canal        = (string) get_post_meta( $post_id, '_hd_canal', true );

	$local_nom     = hd_equipo_nombre( $local_id );
	$visitante_nom = hd_equipo_nombre( $visitante_id );
	$local_escudo  = hd_equipo_escudo_url( $local_id, 'medium' );
	$visit_escudo  = hd_equipo_escudo_url( $visitante_id, 'medium' );

	$torneo_nom = '';
	if ( $torneo_id > 0 ) {
		$t = get_term( $torneo_id, 'torneo' );
		if ( $t && ! is_wp_error( $t ) ) {
			$torneo_nom = $t->name;
		}
	}

	$fecha_larga = hd_fecha_larga_es( $fecha );

	$kicker = implode( ' · ', array_filter( array( $torneo_nom, $fecha_larga ) ) );
	if ( '' === $kicker ) {
		$kicker = get_the_title( $post_id );
	}

	$fecha_info = $hora ? trim( $fecha_larga . ' · ' . $hora ) : $fecha_larga;

	$aria = trim(
		( $torneo_nom ? $torneo_nom . ': ' : '' )
		. $local_nom . ' vs ' . $visitante_nom . '. '
		. $fecha_larga . ( $estadio ? ' en ' . $estadio : '' ) . '.'
	);

	$bg    = get_the_post_thumbnail_url( $post_id, 'large' );
	$style = $bg ? ' style="background-image:url(\'' . esc_url( $bg ) . '\');"' : '';

	echo '<a class="hd-match-banner" href="' . esc_url( get_permalink( $post_id ) ) . '"' . $style . ' aria-label="' . esc_attr( $aria ) . '">';
	echo '<span class="hd-match-banner__overlay" aria-hidden="true"></span>';
	echo '<span class="hd-match-banner__kicker">' . esc_html( $kicker ) . '</span>';
	echo '<span class="hd-match-banner__body">';

	echo '<span class="hd-match-banner__fixture">';
	if ( $local_escudo ) {
		echo '<img class="hd-match-banner__crest" src="' . esc_url( $local_escudo ) . '" width="200" height="200" loading="lazy" alt="' . esc_attr( 'Escudo de ' . $local_nom ) . '">';
	}
	echo '<span class="hd-match-banner__vs">vs</span>';
	if ( $visit_escudo ) {
		echo '<img class="hd-match-banner__crest" src="' . esc_url( $visit_escudo ) . '" width="200" height="200" loading="lazy" alt="' . esc_attr( 'Escudo de ' . $visitante_nom ) . '">';
	}
	echo '</span>';

	echo '<span class="hd-match-banner__info">';
	if ( $fecha_info ) {
		echo '<span class="hd-match-banner__info-item">' . hd_svg_cal() . ' ' . esc_html( $fecha_info ) . '</span>';
	}
	if ( $estadio ) {
		echo '<span class="hd-match-banner__info-item">' . hd_svg_pin() . ' ' . esc_html( $estadio ) . '</span>';
	}
	echo '</span>';

	if ( $canal ) {
		echo '<span class="hd-match-banner__tv"><span class="hd-match-banner__tv-text">' . esc_html( $canal ) . '</span></span>';
	}

	echo '</span>'; // .hd-match-banner__body
	echo '</a>';
}

/**
 * Un dot del slider (paridad con el número de banners).
 *
 * @param int $post_id ID del partido.
 * @param int $i       Índice base 0; el primero queda aria-current="true".
 */
function hd_render_match_dot( $post_id, $i ) {
	$local     = hd_equipo_nombre( (int) get_post_meta( $post_id, '_hd_local', true ) );
	$visitante = hd_equipo_nombre( (int) get_post_meta( $post_id, '_hd_visitante', true ) );
	$label     = 'Partido ' . ( (int) $i + 1 ) . ': ' . $local . ' vs ' . $visitante;

	echo '<button class="hd-match-slider__dot" type="button" data-hd-match-dot aria-current="'
		. ( 0 === (int) $i ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $label ) . '"></button>';
}

/* ---------------------------------------------------------------
 * [hd_ticker cantidad="8" etiqueta="Noticias"]
 * ------------------------------------------------------------- */
add_shortcode( 'hd_ticker', 'hd_ticker_shortcode' );
function hd_ticker_shortcode( $atts ) {
	$a = shortcode_atts( array(
		'cantidad'  => 8,
		'etiqueta'  => 'Noticias',
		'categoria' => 'Videos',
	), $atts );

	return hd_ticker_render( $a );
}

/**
 * Render del ticker, compartido por el shortcode y el widget de
 * Elementor `hd-ticker`.
 *
 * @param array $a cantidad, etiqueta, categoria.
 * @return string HTML.
 */
function hd_ticker_render( $a ) {
	$a = wp_parse_args( $a, array(
		'cantidad'  => 8,
		'etiqueta'  => 'Noticias',
		'categoria' => 'Videos',
	) );

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
						<span class="hd-ticker__cat"><?php echo esc_html( $a['categoria'] ); ?></span>
						<span class="hd-ticker__headline"><?php echo esc_html( get_the_title( $v ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
