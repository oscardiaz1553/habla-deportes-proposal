<?php
/**
 * Habla Deportes Core — Shortcodes de presentación del video.
 * ---------------------------------------------------------------
 * Por qué existen:
 *   El ID de YouTube de cada entrada `video` vive en post meta
 *   `_hd_youtube_id`. El guion bajo inicial la marca como meta
 *   PROTEGIDA, y las etiquetas dinámicas de Elementor ("Campo
 *   personalizado" / ACF) no listan metas protegidas. Es decir: el
 *   reproductor no se puede armar de forma fiable con widgets
 *   nativos. Estos shortcodes resuelven el ID desde PHP y se colocan
 *   en la plantilla con el widget "Shortcode" de Elementor.
 *
 *   [hd_video_player]  → reproductor 16:9 con facade (portada + play).
 *   [hd_youtube_url]   → URL pública del video en YouTube (texto plano),
 *                        útil para el enlace del botón "Ver en YouTube".
 *
 * Patrón de assets: el CSS/JS se REGISTRA en `wp_enqueue_scripts` y solo
 * se ENCOLA cuando el shortcode se usa de verdad (igual que [hd_videos]
 * en includes/youtube-importer.php).
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------------------------------------------------------------
 * Registro (que no encolado) del JS del reproductor. El CSS comparte
 * el handle `hd-shortcodes` ya registrado en youtube-importer.php
 * (WordPress mantiene registros separados para estilos y scripts, así
 * que reutilizar el nombre no colisiona).
 * ------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', function () {
	wp_register_script(
		'hd-shortcodes',
		HDC_URL . 'assets/js/shortcodes.js',
		array(),
		HDC_VERSION,
		true // en el footer.
	);
} );

/**
 * ¿Es un ID de YouTube con forma válida?
 * Los IDs de video son exactamente 11 caracteres [A-Za-z0-9_-].
 *
 * @param mixed $id Candidato.
 * @return bool
 */
function hd_is_youtube_id( $id ) {
	return is_string( $id ) && (bool) preg_match( '/^[A-Za-z0-9_-]{11}$/', $id );
}

/**
 * Devuelve el ID de YouTube guardado en `_hd_youtube_id` para un post,
 * ya validado. Cadena vacía si no hay o si no pasa la validación.
 *
 * @param int $post_id ID de la entrada (0 = la actual del loop).
 * @return string
 */
function hd_get_youtube_id( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( $post_id <= 0 ) {
		return '';
	}

	$raw = get_post_meta( $post_id, '_hd_youtube_id', true );
	$raw = is_string( $raw ) ? trim( $raw ) : '';

	return hd_is_youtube_id( $raw ) ? $raw : '';
}

/**
 * Resuelve los atributos comunes de los shortcodes a un par
 * [ $post_id, $youtube_id ].
 *
 * Atributos aceptados:
 *   post="123"  → ID de la entrada `video`.
 *   id="..."    → si son 11 caracteres con forma de ID de YouTube se usa
 *                 tal cual; si es numérico se interpreta como ID de post
 *                 (es la confusión más habitual, así que se acepta).
 * Sin atributos: la entrada actual del loop.
 *
 * @param array $a Atributos ya normalizados por shortcode_atts().
 * @return array{0:int,1:string}
 */
function hd_resolve_video_target( $a ) {
	$id_attr = isset( $a['id'] ) ? trim( (string) $a['id'] ) : '';
	$post_at = isset( $a['post'] ) ? trim( (string) $a['post'] ) : '';

	// 1) id="XXXXXXXXXXX" con forma de ID de YouTube → directo, sin post.
	if ( '' !== $id_attr && ! ctype_digit( $id_attr ) ) {
		return array( 0, hd_is_youtube_id( $id_attr ) ? $id_attr : '' );
	}

	// 2) post="123", o id="123" (numérico → se trata como ID de entrada).
	$post_id = 0;
	if ( '' !== $post_at && ctype_digit( $post_at ) ) {
		$post_id = (int) $post_at;
	} elseif ( '' !== $id_attr && ctype_digit( $id_attr ) ) {
		$post_id = (int) $id_attr;
	} else {
		$post_id = (int) get_the_ID();
	}

	return array( $post_id, hd_get_youtube_id( $post_id ) );
}

/**
 * Aviso visible SOLO para quien puede editar. Para el visitante el
 * shortcode devuelve cadena vacía (nunca imprime algo roto).
 *
 * @param string $mensaje Texto del aviso.
 * @return string
 */
function hd_shortcode_admin_notice( $mensaje ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return '';
	}
	return '<p class="hd-shortcode-notice">' . esc_html( $mensaje ) . '</p>';
}

/* ===============================================================
 * [hd_video_player]
 * ---------------------------------------------------------------
 * Reproductor 16:9 con patrón "facade": pinta la imagen destacada de
 * la entrada (o la miniatura hqdefault de YouTube como respaldo) con
 * un botón de play encima. El <iframe> de YouTube NO se carga hasta
 * que el visitante hace clic — mejor rendimiento (no se descargan
 * ~1 MB de JS de YouTube en cada visita) y mejor privacidad (no hay
 * cookies de YouTube hasta que el usuario decide reproducir).
 *
 * Atributos:
 *   id="..."   ID de YouTube (11 car.) o ID numérico de la entrada.
 *   post="123" ID numérico de la entrada.
 *   titulo=""  Título accesible del iframe (por defecto, el del post).
 * =============================================================== */
add_shortcode( 'hd_video_player', 'hd_video_player_shortcode' );
function hd_video_player_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'id'     => '',
			'post'   => '',
			'titulo' => '',
		),
		$atts,
		'hd_video_player'
	);

	list( $post_id, $yt_id ) = hd_resolve_video_target( $a );

	if ( '' === $yt_id ) {
		return hd_shortcode_admin_notice(
			'[hd_video_player]: esta entrada no tiene un ID de YouTube válido en el campo _hd_youtube_id.'
		);
	}

	// --- Portada: imagen destacada; si no hay, miniatura de YouTube. ---
	$poster = '';
	$alt    = '';
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$poster    = (string) get_the_post_thumbnail_url( $post_id, 'full' );
		$thumb_id  = get_post_thumbnail_id( $post_id );
		$alt_meta  = $thumb_id ? get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '';
		$alt       = is_string( $alt_meta ) ? $alt_meta : '';
	}
	if ( '' === $poster ) {
		$poster = 'https://i.ytimg.com/vi/' . $yt_id . '/hqdefault.jpg';
	}

	// --- Título accesible (para aria-label y para el title del iframe). ---
	$titulo = trim( (string) $a['titulo'] );
	if ( '' === $titulo && $post_id ) {
		$titulo = (string) get_the_title( $post_id );
	}
	if ( '' === $titulo ) {
		$titulo = 'Video de Habla Deportes';
	}
	if ( '' === $alt ) {
		$alt = $titulo;
	}

	$aria = 'Reproducir video';

	wp_enqueue_style( 'hd-shortcodes' );
	wp_enqueue_script( 'hd-shortcodes' );

	// Triángulo blanco con sombra: sin fondo ni aro (así quedó el prototipo).
	$icono_play = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>';

	$html  = '<div class="hd-player" data-hd-player>';
	$html .= '<button class="hd-player__facade" type="button"'
		. ' data-youtube-id="' . esc_attr( $yt_id ) . '"'
		. ' data-player-title="' . esc_attr( $titulo ) . '"'
		. ' aria-label="' . esc_attr( $aria ) . '">';
	$html .= '<img class="hd-player__img" src="' . esc_url( $poster ) . '"'
		. ' alt="' . esc_attr( $alt ) . '" width="1280" height="720" decoding="async">';
	$html .= '<span class="hd-player__scrim" aria-hidden="true"></span>';
	$html .= '<span class="hd-player__play" aria-hidden="true">' . $icono_play . '</span>';
	$html .= '</button>';
	$html .= '</div>';

	return $html;
}

/* ===============================================================
 * [hd_youtube_url]
 * ---------------------------------------------------------------
 * Devuelve la URL pública del video (texto plano, sin markup) para
 * usarla, por ejemplo, en el enlace de un botón "Ver en YouTube".
 * Mismos atributos que [hd_video_player].
 * =============================================================== */
add_shortcode( 'hd_youtube_url', 'hd_youtube_url_shortcode' );
function hd_youtube_url_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'id'   => '',
			'post' => '',
		),
		$atts,
		'hd_youtube_url'
	);

	list( , $yt_id ) = hd_resolve_video_target( $a );

	if ( '' === $yt_id ) {
		return '';
	}

	return esc_url( 'https://www.youtube.com/watch?v=' . $yt_id );
}
