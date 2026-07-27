<?php
/**
 * Habla Deportes Core — Importador automático del canal de YouTube.
 * ---------------------------------------------------------------
 * Lee el feed RSS público del canal (@HablaDeportesRadio) — sin API
 * key — y crea una entrada `video` por cada video nuevo, con su
 * miniatura como imagen destacada y el ID de YouTube en post meta.
 *
 *  - Corre solo cada hora (WP-Cron). El cron se programa en la
 *    activación del plugin y se limpia en la desactivación.
 *  - Se puede forzar desde: Videos → Importar de YouTube.
 *  - HD_YT_AUTOPUBLISH: true = publica directo (como hoy en el
 *    sitio del cliente); false = entran como borrador a revisión.
 *    Ambas constantes se pueden sobreescribir desde wp-config.php.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HD_YT_CHANNEL_ID' ) ) {
	define( 'HD_YT_CHANNEL_ID', 'UCm_25dALt4-r7C0wSsbaxzA' ); // @HablaDeportesRadio
}
if ( ! defined( 'HD_YT_AUTOPUBLISH' ) ) {
	define( 'HD_YT_AUTOPUBLISH', true );
}

/* ---------------------------------------------------------------
 * Cron: una corrida por hora (la primera, 5 min después de activar).
 * La programación "oficial" está en register_activation_hook; esto es
 * una red de seguridad por si el evento desaparece del cron table.
 * La limpieza vive en register_deactivation_hook (antes: switch_theme).
 * ------------------------------------------------------------- */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'hd_yt_import_event' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'hd_yt_import_event' );
	}
} );
add_action( 'hd_yt_import_event', 'hd_yt_import' );

/* ---------------------------------------------------------------
 * 1) Leer el feed del canal → lista de videos.
 * ------------------------------------------------------------- */
function hd_yt_fetch_feed() {
	$url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . rawurlencode( HD_YT_CHANNEL_ID );
	$res = wp_remote_get( $url, array( 'timeout' => 15 ) );

	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'hd_yt_http', 'No se pudo conectar con YouTube: ' . $res->get_error_message() );
	}
	if ( 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return new WP_Error( 'hd_yt_http', 'YouTube respondió con código ' . wp_remote_retrieve_response_code( $res ) . '.' );
	}

	$prev = libxml_use_internal_errors( true );
	$xml  = simplexml_load_string( wp_remote_retrieve_body( $res ) );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );

	if ( false === $xml ) {
		return new WP_Error( 'hd_yt_xml', 'El feed del canal no se pudo interpretar.' );
	}

	$videos = array();
	foreach ( $xml->entry as $entry ) {
		$yt    = $entry->children( 'http://www.youtube.com/xml/schemas/2015' );
		$media = $entry->children( 'http://search.yahoo.com/mrss/' );

		$video_id = (string) $yt->videoId;
		if ( '' === $video_id ) {
			continue;
		}

		$desc = '';
		if ( isset( $media->group->description ) ) {
			$desc = (string) $media->group->description;
		}

		$videos[] = array(
			'id'        => $video_id,
			'title'     => (string) $entry->title,
			'published' => (string) $entry->published,
			'desc'      => $desc,
		);
	}

	return $videos;
}

/**
 * Descripción del feed → HTML seguro para `post_content`.
 * ---------------------------------------------------------------
 * El feed entrega texto plano (SimpleXML ya decodificó las entidades),
 * así que se escapa, se respetan los saltos de línea con wpautop y se
 * pasa por wp_kses_post. Las URLs quedan como TEXTO, no como enlaces:
 * las descripciones del canal traen links a redes y patrocinadores que
 * no queremos convertir en salidas del sitio.
 * Devuelve '' si el video no trae descripción (los clips/shorts casi
 * nunca la traen) — así el widget de Elementor no pinta un bloque vacío.
 */
function hd_yt_desc_to_content( $desc ) {
	$desc = trim( wp_strip_all_tags( (string) $desc ) );
	if ( '' === $desc ) {
		return '';
	}
	return wp_kses_post( wpautop( esc_html( $desc ) ) );
}

/** Extracto corto para tarjetas y listados (mismo criterio desde el día 1). */
function hd_yt_desc_to_excerpt( $desc ) {
	$desc = trim( wp_strip_all_tags( (string) $desc ) );
	if ( '' === $desc ) {
		return '';
	}
	return wp_trim_words( $desc, 40, '…' );
}

/* ---------------------------------------------------------------
 * 2) Importar los que no existan todavía.
 * ------------------------------------------------------------- */
function hd_yt_import() {
	$videos = hd_yt_fetch_feed();
	if ( is_wp_error( $videos ) ) {
		update_option( 'hd_yt_last_run', current_time( 'mysql' ) );
		update_option( 'hd_yt_last_result', 'Error: ' . $videos->get_error_message() );
		return $videos;
	}

	$nuevos = 0;
	foreach ( $videos as $v ) {
		if ( hd_yt_video_exists( $v['id'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( array(
			'post_type'    => 'video',
			'post_status'  => HD_YT_AUTOPUBLISH ? 'publish' : 'draft',
			'post_title'   => wp_strip_all_tags( $v['title'] ),
			'post_content' => hd_yt_desc_to_content( $v['desc'] ),
			'post_excerpt' => hd_yt_desc_to_excerpt( $v['desc'] ),
			'post_date'    => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $v['published'] ) ) ),
		) );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, '_hd_youtube_id', sanitize_text_field( $v['id'] ) );
		hd_yt_set_thumbnail( $post_id, $v['id'], $v['title'] );
		hd_yt_classify( $post_id, $v['title'] );      // equipo/tema por título
		hd_yt_tag_if_short( $post_id, $v['id'] );     // tema:clip si es un short
		$nuevos++;
	}

	update_option( 'hd_yt_last_run', current_time( 'mysql' ) );
	update_option( 'hd_yt_last_result', $nuevos . ' video(s) nuevo(s)' );
	return $nuevos;
}

/** ID de la entrada `video` que corresponde a un ID de YouTube (0 si no existe). */
function hd_yt_find_video( $yt_id ) {
	$ids = get_posts( array(
		'post_type'      => 'video',
		'post_status'    => 'any',
		'meta_key'       => '_hd_youtube_id',
		'meta_value'     => $yt_id,
		'fields'         => 'ids',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	) );
	return $ids ? (int) $ids[0] : 0;
}

function hd_yt_video_exists( $yt_id ) {
	return (bool) hd_yt_find_video( $yt_id );
}

/**
 * Rellena la descripción (`post_content`) de los videos YA importados
 * releyendo el feed del canal, sin volver a crear entradas.
 * ---------------------------------------------------------------
 * Reglas:
 *   - Solo toca videos cuyo `post_content` esté VACÍO: nunca pisa un
 *     texto que el editor haya escrito a mano.
 *   - Si además falta el extracto, lo genera de paso.
 *   - El feed público solo expone las últimas ~15 entradas del canal:
 *     los videos más antiguos que eso no se pueden completar sin usar
 *     la API de YouTube. Se reportan como "pendientes".
 * Devuelve un array con el recuento, o WP_Error si el feed falló.
 */
function hd_yt_backfill_descriptions() {
	$videos = hd_yt_fetch_feed();
	if ( is_wp_error( $videos ) ) {
		return $videos;
	}

	$actualizados = 0;
	foreach ( $videos as $v ) {
		$post_id = hd_yt_find_video( $v['id'] );
		if ( ! $post_id ) {
			continue; // todavía no importado; de eso se encarga "Importar ahora".
		}

		$contenido = hd_yt_desc_to_content( $v['desc'] );
		if ( '' === $contenido ) {
			continue; // el video no trae descripción en YouTube.
		}

		$post = get_post( $post_id );
		if ( ! $post || '' !== trim( (string) $post->post_content ) ) {
			continue; // ya tiene texto (importado nuevo o escrito a mano).
		}

		$update = array(
			'ID'           => $post_id,
			'post_content' => $contenido,
		);
		if ( '' === trim( (string) $post->post_excerpt ) ) {
			$update['post_excerpt'] = hd_yt_desc_to_excerpt( $v['desc'] );
		}

		if ( ! is_wp_error( wp_update_post( $update ) ) ) {
			$actualizados++;
		}
	}

	// Cuántos siguen sin descripción después de la pasada.
	$pendientes = 0;
	$todos      = get_posts( array(
		'post_type'      => 'video',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $todos as $pid ) {
		$p = get_post( $pid );
		if ( $p && '' === trim( (string) $p->post_content ) ) {
			$pendientes++;
		}
	}

	return array(
		'actualizados' => $actualizados,
		'pendientes'   => $pendientes,
		'total'        => count( $todos ),
	);
}

/* ---------------------------------------------------------------
 * 2c) Detección de shorts → `tema:clip`.
 * ---------------------------------------------------------------
 * El feed RSS no expone el formato del video. El truco fiable sin API:
 * pedir youtube.com/shorts/{id} SIN seguir redirecciones. YouTube
 * responde 200 si el video es de verdad un short, y 303 (redirección a
 * /watch?v=) si es un video normal. Comprobado contra este canal: los
 * shorts dan 200 y los videos largos 303, sin falsos positivos.
 *
 * El resultado se cachea en la meta `_hd_is_short` ('1'/'0') para no
 * repetir la petición en cada corrida. Si la petición falla NO se
 * cachea, para reintentarlo más adelante.
 * ------------------------------------------------------------- */
function hd_yt_is_short( $post_id, $yt_id ) {
	$cache = get_post_meta( $post_id, '_hd_is_short', true );
	if ( '' !== $cache ) {
		return '1' === $cache;
	}

	$res = wp_remote_head(
		'https://www.youtube.com/shorts/' . rawurlencode( $yt_id ),
		array(
			'timeout'     => 10,
			'redirection' => 0, // clave: sin esto seguiríamos el 303 y todo daría 200.
		)
	);
	if ( is_wp_error( $res ) ) {
		return false;
	}

	$es_short = 200 === (int) wp_remote_retrieve_response_code( $res );
	update_post_meta( $post_id, '_hd_is_short', $es_short ? '1' : '0' );

	return $es_short;
}

/** Añade `tema:clip` si el video es un short. Devuelve true si etiquetó. */
function hd_yt_tag_if_short( $post_id, $yt_id ) {
	if ( ! hd_yt_is_short( $post_id, $yt_id ) ) {
		return false;
	}

	$term = get_term_by( 'slug', 'clip', 'tema' );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}

	wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'tema', true ); // append
	return true;
}

/**
 * Recorre los videos ya importados y etiqueta los que sean shorts.
 * Una petición HTTP por video sin comprobar todavía (los ya
 * comprobados salen de la meta cacheada, sin red). Devuelve el
 * recuento: array( 'clips', 'revisados' ).
 */
function hd_yt_detect_clips_all() {
	$ids = get_posts( array(
		'post_type'      => 'video',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	$clips = 0;
	foreach ( $ids as $pid ) {
		$yt_id = get_post_meta( $pid, '_hd_youtube_id', true );
		if ( ! $yt_id ) {
			continue;
		}
		if ( hd_yt_tag_if_short( $pid, $yt_id ) ) {
			$clips++;
		}
	}

	return array(
		'clips'     => $clips,
		'revisados' => count( $ids ),
	);
}

/* ---------------------------------------------------------------
 * 2b) Clasificador automático por TÍTULO (heurística conservadora).
 * ---------------------------------------------------------------
 * Solo AÑADE términos (wp_set_object_terms con append=true); nunca
 * borra los que el editor haya puesto a mano. Reglas (case-insensitive,
 * unicode para acentos):
 *   /junior/                          -> equipo `junior`
 *   /selecci[oó]n|colombia|tricolor/  -> equipo `seleccion-colombia`
 *   /nacional/                        -> equipo `nacional`
 *   /millonarios/                     -> equipo `millonarios`
 *   /am[eé]rica/                      -> equipo `america`
 *   /fichaj|fich[oó]|refuerzo|contrat/-> tema   `fichaje`
 *
 * NOTA: `tema:clip` NO se asigna aquí. El título no dice si un video es
 * un short; eso lo resuelve hd_yt_tag_if_short() más abajo, con una
 * señal fiable.
 * Devuelve true si aplicó al menos un término.
 */
function hd_yt_classify( $post_id, $title ) {
	$title = (string) $title;

	$add = array( 'equipo' => array(), 'tema' => array() );

	if ( preg_match( '/junior/iu', $title ) ) {
		$add['equipo'][] = 'junior';
	}
	if ( preg_match( '/selecci[oó]n|colombia|tricolor/iu', $title ) ) {
		$add['equipo'][] = 'seleccion-colombia';
	}
	if ( preg_match( '/nacional/iu', $title ) ) {
		$add['equipo'][] = 'nacional';
	}
	if ( preg_match( '/millonarios/iu', $title ) ) {
		$add['equipo'][] = 'millonarios';
	}
	if ( preg_match( '/am[eé]rica/iu', $title ) ) {
		$add['equipo'][] = 'america';
	}
	if ( preg_match( '/fichaj|fich[oó]|refuerzo|contrat/iu', $title ) ) {
		$add['tema'][] = 'fichaje';
	}

	$touched = false;
	foreach ( $add as $taxonomy => $slugs ) {
		if ( empty( $slugs ) ) {
			continue;
		}
		$ids = array();
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}
		if ( $ids ) {
			wp_set_object_terms( $post_id, $ids, $taxonomy, true ); // append=true
			$touched = true;
		}
	}

	return $touched;
}

/**
 * Reclasifica TODOS los videos ya importados con la misma heurística
 * (para poblar los que ya están sin volver a tocar YouTube). Devuelve
 * cuántos recibieron al menos un término.
 */
function hd_yt_reclassify_all() {
	$ids = get_posts( array(
		'post_type'      => 'video',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	$tocados = 0;
	foreach ( $ids as $pid ) {
		if ( hd_yt_classify( $pid, get_the_title( $pid ) ) ) {
			$tocados++;
		}
	}
	return $tocados;
}

/**
 * Clasificación inicial ÚNICA de la videoteca ya importada.
 * Al activar el plugin sobre un sitio que YA tiene videos, los etiqueta
 * una sola vez sin que el editor tenga que pulsar "Reclasificar
 * existentes". Corre después de la siembra de términos (init 20) y se
 * marca con un flag para no repetirse.
 */
add_action( 'init', function () {
	if ( '1' === get_option( 'hd_videos_classified' ) ) {
		return;
	}
	// Solo si los términos ya están sembrados (evita correr sin taxonomía).
	if ( ! get_term_by( 'slug', 'junior', 'equipo' ) ) {
		return;
	}
	hd_yt_reclassify_all();
	update_option( 'hd_videos_classified', '1' );
}, 25 );

/* Miniatura de YouTube como imagen destacada (hqdefault siempre existe). */
function hd_yt_set_thumbnail( $post_id, $yt_id, $title ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$url = 'https://i.ytimg.com/vi/' . rawurlencode( $yt_id ) . '/hqdefault.jpg';
	$att = media_sideload_image( $url, $post_id, $title, 'id' );
	if ( ! is_wp_error( $att ) ) {
		set_post_thumbnail( $post_id, $att );
	}
}

/* ---------------------------------------------------------------
 * 3) Página de administración: Videos → Importar de YouTube.
 * ------------------------------------------------------------- */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=video',
		'Importar de YouTube',
		'Importar de YouTube',
		'manage_options',
		'hd-yt-import',
		'hd_yt_admin_page'
	);
} );

function hd_yt_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No tienes permisos para ver esta página.' );
	}

	$resultado = null;
	$reclass   = null;
	$backfill  = null;
	$clips     = null;
	if ( isset( $_POST['hd_yt_run'] ) && check_admin_referer( 'hd_yt_import_now' ) ) {
		$resultado = hd_yt_import();
	}
	if ( isset( $_POST['hd_yt_reclass'] ) && check_admin_referer( 'hd_yt_import_now' ) ) {
		$reclass = hd_yt_reclassify_all();
	}
	if ( isset( $_POST['hd_yt_backfill'] ) && check_admin_referer( 'hd_yt_import_now' ) ) {
		$backfill = hd_yt_backfill_descriptions();
	}
	if ( isset( $_POST['hd_yt_clips'] ) && check_admin_referer( 'hd_yt_import_now' ) ) {
		$clips = hd_yt_detect_clips_all();
	}
	$last_run    = get_option( 'hd_yt_last_run', 'nunca' );
	$last_result = get_option( 'hd_yt_last_result', '—' );
	?>
	<div class="wrap">
		<h1>Importar de YouTube</h1>
		<p>
			Canal conectado: <strong>Habla Deportes</strong>
			(<code><?php echo esc_html( HD_YT_CHANNEL_ID ); ?></code>).
			El importador corre solo <strong>cada hora</strong>; aquí puedes forzar una corrida.
		</p>

		<?php if ( null !== $resultado ) : ?>
			<?php if ( is_wp_error( $resultado ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $resultado->get_error_message() ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success"><p>
					Importación completa: <strong><?php echo (int) $resultado; ?></strong> video(s) nuevo(s).
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=video' ) ); ?>">Ver videos</a>
				</p></div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( null !== $reclass ) : ?>
			<div class="notice notice-success"><p>
				Reclasificación completa: <strong><?php echo (int) $reclass; ?></strong> video(s) recibieron término(s) por título.
			</p></div>
		<?php endif; ?>

		<?php if ( null !== $backfill ) : ?>
			<?php if ( is_wp_error( $backfill ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $backfill->get_error_message() ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success"><p>
					Descripciones rellenadas: <strong><?php echo (int) $backfill['actualizados']; ?></strong> video(s) actualizado(s).
					<?php if ( $backfill['pendientes'] > 0 ) : ?>
						Quedan <strong><?php echo (int) $backfill['pendientes']; ?></strong> de
						<?php echo (int) $backfill['total']; ?> sin descripción: o el video no la trae en
						YouTube (típico en los clips), o es más antiguo que las últimas ~15 entradas
						que expone el feed.
					<?php endif; ?>
				</p></div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( null !== $clips ) : ?>
			<div class="notice notice-success"><p>
				Detección de clips completa: <strong><?php echo (int) $clips['clips']; ?></strong>
				short(s) etiquetado(s) como <code>tema:clip</code>
				sobre <?php echo (int) $clips['revisados']; ?> video(s) revisado(s).
			</p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'hd_yt_import_now' ); ?>
			<p>
				<button type="submit" name="hd_yt_run" value="1" class="button button-primary">Importar ahora</button>
				<button type="submit" name="hd_yt_reclass" value="1" class="button">Reclasificar existentes</button>
				<button type="submit" name="hd_yt_backfill" value="1" class="button">Rellenar descripciones existentes</button>
				<button type="submit" name="hd_yt_clips" value="1" class="button">Detectar clips</button>
			</p>
			<p class="description">
				<strong>Reclasificar existentes</strong> recorre los videos ya importados y les
				añade términos de <code>equipo</code>/<code>tema</code> según el título (no borra los
				que pongas a mano). <code>tema:clip</code> no sale del título: lo asigna
				<strong>Detectar clips</strong>.
			</p>
			<p class="description">
				<strong>Detectar clips</strong> marca como <code>tema:clip</code> los videos que
				son shorts (formato vertical). Lo comprueba contra YouTube y guarda el resultado,
				así que la primera corrida tarda unos segundos y las siguientes son inmediatas.
				Los videos nuevos se detectan solos al importarse.
			</p>
			<p class="description">
				<strong>Rellenar descripciones existentes</strong> completa la descripción de los
				videos importados antes de esta versión, que se guardaron sin ella. Relee el feed
				del canal (últimas ~15 entradas) y solo escribe donde la descripción está vacía:
				nunca pisa un texto editado a mano.
			</p>
		</form>

		<p><em>Última corrida: <?php echo esc_html( $last_run ); ?> · Resultado: <?php echo esc_html( $last_result ); ?></em></p>
	</div>
	<?php
}

/* ---------------------------------------------------------------
 * 4) Shortcode [hd_videos cantidad="8"]
 * ---------------------------------------------------------------
 * Grilla simple con los últimos videos importados. Se CONSERVA porque
 * es autónomo y útil (permite verificar el importador desde cualquier
 * página sin montar nada en Elementor). Su CSS ya no depende del tema:
 * vive en assets/css/shortcodes.css y solo se encola si el shortcode
 * se usa realmente en la página. Para el diseño definitivo el usuario
 * usará los widgets de Elementor (Posts / Loop Grid) sobre el CPT
 * `video`; este shortcode es una utilidad de respaldo.
 * ------------------------------------------------------------- */
add_shortcode( 'hd_videos', 'hd_videos_shortcode' );
function hd_videos_shortcode( $atts ) {
	$a = shortcode_atts( array( 'cantidad' => 8 ), $atts );

	$cantidad = (int) $a['cantidad'];
	if ( $cantidad < 1 ) {
		$cantidad = 8;
	}

	$q = new WP_Query( array(
		'post_type'      => 'video',
		'posts_per_page' => $cantidad,
		'no_found_rows'  => true,
	) );

	if ( ! $q->have_posts() ) {
		return '<p>No hay videos todavía. Corre el importador en Videos → Importar de YouTube.</p>';
	}

	wp_enqueue_style( 'hd-shortcodes' );

	$out = '<div class="hd-videos-grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$out .= '<a class="hd-video-card" href="' . esc_url( get_permalink() ) . '">'
			. '<span class="hd-video-card__thumb">' . get_the_post_thumbnail( get_the_ID(), 'medium_large' ) . '</span>'
			. '<span class="hd-video-card__title">' . esc_html( get_the_title() ) . '</span>'
			. '<span class="hd-video-card__date">' . esc_html( get_the_date() ) . '</span>'
			. '</a>';
	}
	wp_reset_postdata();

	return $out . '</div>';
}

/* Registro (que no encolado) del CSS del shortcode: se encola bajo
   demanda desde hd_videos_shortcode(). */
add_action( 'wp_enqueue_scripts', function () {
	wp_register_style(
		'hd-shortcodes',
		HDC_URL . 'assets/css/shortcodes.css',
		array(),
		HDC_VERSION
	);
} );
