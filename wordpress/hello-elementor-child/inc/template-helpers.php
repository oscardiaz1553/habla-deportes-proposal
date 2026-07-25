<?php
/**
 * Habla Deportes — Helpers de plantilla.
 * ---------------------------------------------------------------
 * Funciones compartidas por header/footer/front-page/single/archive
 * para portar el prototipo aprobado sin repetir markup:
 *   - hd_proto_uri()      -> base de los assets del prototipo
 *   - hd_tax_link()       -> enlace a un termino si existe, '#' si no
 *   - hd_youtube_id()     -> ID de YouTube guardado por el importador
 *   - hd_thumb_url()      -> imagen destacada con fallback del prototipo
 *   - hd_fecha()          -> "14 de Julio, 2026" (fecha del post)
 *   - hd_hace()           -> "Hace 2 horas" (tiempo relativo)
 *   - hd_svg_play() etc.  -> iconos SVG del prototipo
 *   - hd_render_vcard()   -> tarjeta de video/nota (grillas y carruseles)
 *   - hd_render_jitem()   -> item de lista (Mundo Junior / Seleccion)
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Base URL de los assets portados del prototipo (sin slash final). */
function hd_proto_uri() {
	return get_stylesheet_directory_uri() . '/assets/proto';
}

/**
 * Enlace a un termino de taxonomia si existe; '#' como degradacion v1.
 * Se usa en los megamenus para que los enlaces cobren vida a medida
 * que el cliente cree los terminos (equipo/torneo/tema).
 */
function hd_tax_link( $taxonomy, $slug ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term && ! is_wp_error( $term ) ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return '#';
}

/**
 * Términos de una taxonomía para los megamenús dinámicos.
 * hide_empty=false para que se vean aunque aún no tengan contenido.
 * Devuelve array de WP_Term (vacío si no hay términos o hay error),
 * para que el header degrade con elegancia (sin columnas vacías).
 */
function hd_get_terms( $taxonomy ) {
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}
	return $terms;
}

/** URL de un WP_Term con degradación a '#' si get_term_link falla. */
function hd_term_url( $term ) {
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? '#' : $link;
}

/** Último video publicado (WP_Post) o null — card destacada del menú Fútbol. */
function hd_latest_video() {
	$q = get_posts( array(
		'post_type'      => 'video',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
	) );
	return ! empty( $q ) ? $q[0] : null;
}

/**
 * Último contenido (post|video) etiquetado con un término de taxonomía,
 * o null si el término no existe / no tiene contenido. Card destacada
 * del menú Junior (equipo:junior), con fallback al último video.
 */
function hd_latest_in_term( $taxonomy, $slug, $post_types = array( 'post', 'video' ) ) {
	if ( ! get_term_by( 'slug', $slug, $taxonomy ) ) {
		return null;
	}
	$q = get_posts( array(
		'post_type'      => $post_types,
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'tax_query'      => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $slug,
			),
		),
	) );
	return ! empty( $q ) ? $q[0] : null;
}

/** ID de YouTube del video (meta guardado por el importador) o ''. */
function hd_youtube_id( $post_id ) {
	$id = get_post_meta( $post_id, '_hd_youtube_id', true );
	return is_string( $id ) ? trim( $id ) : '';
}

/**
 * URL de la imagen destacada; si el post no tiene, usa una imagen
 * del prototipo para que la grilla nunca quede "rota".
 */
function hd_thumb_url( $post_id, $size ) {
	$url = get_the_post_thumbnail_url( $post_id, $size );
	if ( $url ) {
		return $url;
	}
	return hd_proto_uri() . '/img/Junior-de-Barranquilla-1200x740.jpg';
}

/** Fecha del post con el formato del prototipo: "14 de Julio, 2026". */
function hd_fecha( $post_id ) {
	return date_i18n( 'j \d\e F, Y', get_post_time( 'U', false, $post_id ) );
}

/** Tiempo relativo: "Hace 2 horas" / "Hace 1 dia". */
function hd_hace( $post_id ) {
	return 'Hace ' . human_time_diff( get_post_time( 'U', true, $post_id ), current_time( 'timestamp', true ) );
}

/** Icono play (triangulo) del prototipo. */
function hd_svg_play() {
	return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
}

/** Logo de YouTube (relleno currentColor) del prototipo. */
function hd_svg_youtube( $class = '' ) {
	$cls = $class ? ' class="' . esc_attr( $class ) . '"' : '';
	return '<svg' . $cls . ' viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>';
}

/** Flecha "chevron derecha" pequena de los enlaces "Ver todo". */
function hd_svg_chevron() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
}

/**
 * Tarjeta .vcard del prototipo (grillas y carruseles).
 * - CPT video: badge VIDEO + boton play + data-youtube-id (abre el
 *   lightbox de home.js). El href apunta al single como degradacion
 *   sin JS y para SEO.
 * - Post normal: badge "Nota", navega al articulo.
 * $args: array( 'data_tipo' => bool ) para las grillas con filtros.
 */
function hd_render_vcard( $post_id, $args = array() ) {
	$is_video = ( 'video' === get_post_type( $post_id ) );
	$title    = get_the_title( $post_id );
	$link     = get_permalink( $post_id );
	$thumb    = hd_thumb_url( $post_id, 'medium_large' );
	$yt       = $is_video ? hd_youtube_id( $post_id ) : '';
	$tipo     = ! empty( $args['data_tipo'] );

	echo '<a class="vcard" href="' . esc_url( $link ) . '"';
	if ( $tipo ) {
		echo ' data-tipo="' . ( $is_video ? 'video' : 'nota' ) . '"';
	}
	if ( $is_video && $yt ) {
		echo ' data-youtube-id="' . esc_attr( $yt ) . '" data-video-title="' . esc_attr( $title ) . '"';
		echo ' aria-label="' . esc_attr( 'Reproducir video: ' . $title ) . '"';
	} else {
		echo ' aria-label="' . esc_attr( 'Leer nota: ' . $title ) . '"';
	}
	echo '>';
	echo '<div class="vcard__thumb">';
	echo '<img class="vcard__img" src="' . esc_url( $thumb ) . '" width="520" height="358" loading="lazy" alt="' . esc_attr( $title ) . '">';
	echo '<span class="vcard__scrim" aria-hidden="true"></span>';
	if ( $is_video ) {
		echo '<span class="vcard__type" aria-hidden="true">' . hd_svg_play() . 'Video</span>';
		echo '<span class="play-btn vcard__play" aria-hidden="true">' . hd_svg_play() . '</span>';
	} else {
		echo '<span class="vcard__type" aria-hidden="true">Nota</span>';
	}
	echo '</div>';
	echo '<p class="vcard__date">' . esc_html( hd_fecha( $post_id ) ) . '</p>';
	echo '<h3 class="vcard__title">' . esc_html( $title ) . '</h3>';
	echo '</a>';
}

/**
 * Item .jitem del prototipo (lista lateral de Mundo Junior / Seleccion).
 * $cat_label: kicker de categoria ("Junior", "Seleccion"...).
 * $cat_mod:   modificador opcional de la clase del kicker (p.ej. 'sel').
 */
function hd_render_jitem( $post_id, $cat_label, $cat_mod = '' ) {
	$is_video = ( 'video' === get_post_type( $post_id ) );
	$title    = get_the_title( $post_id );
	$link     = get_permalink( $post_id );
	$thumb    = hd_thumb_url( $post_id, 'medium_large' );
	$yt       = $is_video ? hd_youtube_id( $post_id ) : '';
	$cat_cls  = 'jitem__cat' . ( $cat_mod ? ' jitem__cat--' . $cat_mod : '' );

	echo '<a class="jitem" href="' . esc_url( $link ) . '"';
	if ( $is_video && $yt ) {
		echo ' data-youtube-id="' . esc_attr( $yt ) . '" data-video-title="' . esc_attr( $title ) . '"';
		echo ' aria-label="' . esc_attr( 'Reproducir video: ' . $title ) . '"';
	} else {
		echo ' aria-label="' . esc_attr( 'Leer nota: ' . $title ) . '"';
	}
	echo '>';
	echo '<div class="jitem__thumb">';
	echo '<img class="jitem__img" src="' . esc_url( $thumb ) . '" width="520" height="358" loading="lazy" alt="' . esc_attr( $title ) . '">';
	if ( $is_video ) {
		echo '<span class="jitem__type" aria-hidden="true">' . hd_svg_play() . 'Video</span>';
		echo '<span class="jitem__play" aria-hidden="true">' . hd_svg_play() . '</span>';
	} else {
		echo '<span class="jitem__type" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>Nota</span>';
	}
	echo '</div>';
	echo '<div class="jitem__body">';
	echo '<span class="' . esc_attr( $cat_cls ) . '">' . esc_html( $cat_label ) . '</span>';
	echo '<h3 class="jitem__title">' . esc_html( $title ) . '</h3>';
	echo '</div>';
	echo '</a>';
}
