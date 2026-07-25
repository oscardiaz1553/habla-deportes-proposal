<?php
/**
 * Habla Deportes — Importador automático del canal de YouTube.
 * ---------------------------------------------------------------
 * Lee el feed RSS público del canal (@HablaDeportesRadio) — sin API
 * key — y crea una entrada `video` por cada video nuevo, con su
 * miniatura como imagen destacada y el ID de YouTube en post meta.
 *
 *  - Corre solo cada hora (WP-Cron).
 *  - Se puede forzar desde: Videos → Importar de YouTube.
 *  - HD_YT_AUTOPUBLISH: true = publica directo (como hoy en el
 *    sitio del cliente); false = entran como borrador a revisión.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HD_YT_CHANNEL_ID', 'UCm_25dALt4-r7C0wSsbaxzA' ); // @HablaDeportesRadio
define( 'HD_YT_AUTOPUBLISH', true );

/* ---------------------------------------------------------------
 * Cron: una corrida por hora (la primera, 5 min después de activar).
 * ------------------------------------------------------------- */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'hd_yt_import_event' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'hd_yt_import_event' );
	}
} );
add_action( 'hd_yt_import_event', 'hd_yt_import' );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( 'hd_yt_import_event' );
} );

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

	$xml = simplexml_load_string( wp_remote_retrieve_body( $res ) );
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
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $v['desc'] ), 40, '…' ),
			'post_date'    => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $v['published'] ) ) ),
		) );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, '_hd_youtube_id', sanitize_text_field( $v['id'] ) );
		hd_yt_set_thumbnail( $post_id, $v['id'], $v['title'] );
		hd_yt_classify( $post_id, $v['title'] ); // etiqueta equipo/tema por título
		$nuevos++;
	}

	update_option( 'hd_yt_last_run', current_time( 'mysql' ) );
	update_option( 'hd_yt_last_result', $nuevos . ' video(s) nuevo(s)' );
	return $nuevos;
}

function hd_yt_video_exists( $yt_id ) {
	$q = new WP_Query( array(
		'post_type'      => 'video',
		'post_status'    => 'any',
		'meta_key'       => '_hd_youtube_id',
		'meta_value'     => $yt_id,
		'fields'         => 'ids',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
	) );
	return $q->have_posts();
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
 * NOTA: `tema:clip` se deja para asignación MANUAL — el RSS del canal
 * no expone si un video es "short", así que no hay señal fiable.
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
 * Al activar el tema sobre un sitio que YA tiene videos (los 15 del
 * canal, p. ej.), los etiqueta una sola vez sin que el editor tenga que
 * pulsar "Reclasificar existentes". Corre después de la siembra de
 * términos (init 20) y se marca con un flag para no repetirse.
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
	$resultado = null;
	$reclass   = null;
	if ( isset( $_POST['hd_yt_run'] ) && check_admin_referer( 'hd_yt_import_now' ) ) {
		$resultado = hd_yt_import();
	}
	if ( isset( $_POST['hd_yt_reclass'] ) && check_admin_referer( 'hd_yt_import_now' ) ) {
		$reclass = hd_yt_reclassify_all();
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

		<form method="post">
			<?php wp_nonce_field( 'hd_yt_import_now' ); ?>
			<p>
				<button type="submit" name="hd_yt_run" value="1" class="button button-primary">Importar ahora</button>
				<button type="submit" name="hd_yt_reclass" value="1" class="button">Reclasificar existentes</button>
			</p>
			<p class="description">
				<strong>Reclasificar existentes</strong> recorre los videos ya importados y les
				añade términos de <code>equipo</code>/<code>tema</code> según el título (no borra los
				que pongas a mano). <code>tema:clip</code> se asigna manualmente.
			</p>
		</form>

		<p><em>Última corrida: <?php echo esc_html( $last_run ); ?> · Resultado: <?php echo esc_html( $last_result ); ?></em></p>
	</div>
	<?php
}

/* ---------------------------------------------------------------
 * 4) Shortcode de prueba: [hd_videos cantidad="8"]
 *    Grilla simple con los últimos videos importados (la versión
 *    final usará las tarjetas del prototipo).
 * ------------------------------------------------------------- */
add_shortcode( 'hd_videos', function ( $atts ) {
	$a = shortcode_atts( array( 'cantidad' => 8 ), $atts );

	$q = new WP_Query( array(
		'post_type'      => 'video',
		'posts_per_page' => (int) $a['cantidad'],
		'no_found_rows'  => true,
	) );

	if ( ! $q->have_posts() ) {
		return '<p>No hay videos todavía. Corre el importador en Videos → Importar de YouTube.</p>';
	}

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
} );
