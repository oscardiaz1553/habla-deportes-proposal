<?php
/**
 * Habla Deportes — CPT `partido` (Centro de Partidos).
 * ---------------------------------------------------------------
 * Modelo editable para el "Centro de Partidos" de la home. El cliente
 * añade partidos desde el admin (Partidos → Añadir) sin tocar código.
 *
 * Contenido:
 *   - CPT `partido` (título + imagen destacada = foto de fondo del banner).
 *   - Meta box nativo "Detalles del partido" (sin ACF): equipo local /
 *     visitante (taxonomía `equipo`), torneo (taxonomía `torneo`), fecha,
 *     hora, estadio y canal. Nonce + capability + sanitización al guardar.
 *   - Term meta `_hd_escudo` en la taxonomía `equipo` (campo imagen en las
 *     pantallas de añadir/editar término) con fallback por slug a los
 *     escudos bundled del prototipo.
 *   - Helpers de consulta y render que reutilizan el markup EXACTO del
 *     Centro de Partidos (clases .match-banner*, hooks data-match-* del
 *     slider de home.js).
 *   - Siembra idempotente de 2-3 partidos demo (sideload de imágenes
 *     bundled) para que la sección se vea poblada pero ya editable.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================
 * 1) Registro del CPT `partido`
 * ============================================================ */
add_action( 'init', 'hd_register_partido', 11 ); // 11 = después de las taxonomías (prioridad 10 en cpt.php).
function hd_register_partido() {
	register_post_type( 'partido', array(
		'labels'        => array(
			'name'          => 'Partidos',
			'singular_name' => 'Partido',
			'add_new'       => 'Añadir partido',
			'add_new_item'  => 'Añadir partido',
			'edit_item'     => 'Editar partido',
			'new_item'      => 'Nuevo partido',
			'view_item'     => 'Ver partido',
			'all_items'     => 'Todos los partidos',
			'search_items'  => 'Buscar partidos',
			'not_found'     => 'No hay partidos todavía.',
			'menu_name'     => 'Partidos',
		),
		'public'        => true,
		'menu_position' => 6,
		'menu_icon'     => 'dashicons-calendar-alt',
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'partido' ),
		'show_in_rest'  => true, // editor de bloques + REST (verificación).
		'supports'      => array( 'title', 'thumbnail' ),
	) );
}

/* ============================================================
 * 2) Meta box "Detalles del partido"
 * ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'hd_partido_detalles',
		'Detalles del partido',
		'hd_partido_metabox_render',
		'partido',
		'normal',
		'high'
	);
} );

/**
 * Pinta un <select> con los términos de una taxonomía.
 * $selected es el term_id guardado (int) o 0.
 */
function hd_partido_term_select( $name, $taxonomy, $selected, $placeholder ) {
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );
	echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" style="width:100%;max-width:340px;">';
	echo '<option value="0">' . esc_html( $placeholder ) . '</option>';
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			echo '<option value="' . esc_attr( $t->term_id ) . '"' . selected( (int) $selected, (int) $t->term_id, false ) . '>' . esc_html( $t->name ) . '</option>';
		}
	}
	echo '</select>';
}

function hd_partido_metabox_render( $post ) {
	wp_nonce_field( 'hd_partido_save', 'hd_partido_nonce' );

	$local     = (int) get_post_meta( $post->ID, '_hd_local', true );
	$visitante = (int) get_post_meta( $post->ID, '_hd_visitante', true );
	$torneo    = (int) get_post_meta( $post->ID, '_hd_torneo', true );
	$fecha     = (string) get_post_meta( $post->ID, '_hd_fecha', true );
	$hora      = (string) get_post_meta( $post->ID, '_hd_hora', true );
	$estadio   = (string) get_post_meta( $post->ID, '_hd_estadio', true );
	$canal     = get_post_meta( $post->ID, '_hd_canal', true );
	if ( '' === $canal ) {
		$canal = 'Win Sports';
	}
	?>
	<style>
		.hd-mb-grid { display:grid; grid-template-columns:160px 1fr; gap:12px 16px; align-items:center; max-width:640px; }
		.hd-mb-grid label { font-weight:600; }
		.hd-mb-grid input[type="text"] { width:100%; max-width:340px; }
		.hd-mb-note { grid-column:1 / -1; color:#646970; margin:4px 0 0; }
	</style>
	<div class="hd-mb-grid">
		<label for="_hd_local">Equipo local</label>
		<div><?php hd_partido_term_select( '_hd_local', 'equipo', $local, '— Selecciona equipo —' ); ?></div>

		<label for="_hd_visitante">Equipo visitante</label>
		<div><?php hd_partido_term_select( '_hd_visitante', 'equipo', $visitante, '— Selecciona equipo —' ); ?></div>

		<label for="_hd_torneo">Torneo</label>
		<div><?php hd_partido_term_select( '_hd_torneo', 'torneo', $torneo, '— Selecciona torneo —' ); ?></div>

		<label for="_hd_fecha">Fecha</label>
		<div><input type="date" name="_hd_fecha" id="_hd_fecha" value="<?php echo esc_attr( $fecha ); ?>"></div>

		<label for="_hd_hora">Hora (opcional)</label>
		<div><input type="time" name="_hd_hora" id="_hd_hora" value="<?php echo esc_attr( $hora ); ?>"></div>

		<label for="_hd_estadio">Estadio</label>
		<div><input type="text" name="_hd_estadio" id="_hd_estadio" value="<?php echo esc_attr( $estadio ); ?>" placeholder="Metropolitano"></div>

		<label for="_hd_canal">Canal / TV</label>
		<div><input type="text" name="_hd_canal" id="_hd_canal" value="<?php echo esc_attr( $canal ); ?>" placeholder="Win Sports"></div>

		<p class="hd-mb-note">La <strong>imagen destacada</strong> del partido es la foto de fondo del banner. Los escudos se toman de cada equipo (Equipos → editar → Escudo), con respaldo automático.</p>
	</div>
	<?php
}

add_action( 'save_post_partido', 'hd_partido_save', 10, 2 );
function hd_partido_save( $post_id, $post ) {
	// Nonce.
	if ( ! isset( $_POST['hd_partido_nonce'] ) || ! wp_verify_nonce( $_POST['hd_partido_nonce'], 'hd_partido_save' ) ) {
		return;
	}
	// Autosave / revisiones.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	// Permisos.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Selects de taxonomía (term_id o 0).
	update_post_meta( $post_id, '_hd_local', isset( $_POST['_hd_local'] ) ? absint( $_POST['_hd_local'] ) : 0 );
	update_post_meta( $post_id, '_hd_visitante', isset( $_POST['_hd_visitante'] ) ? absint( $_POST['_hd_visitante'] ) : 0 );
	update_post_meta( $post_id, '_hd_torneo', isset( $_POST['_hd_torneo'] ) ? absint( $_POST['_hd_torneo'] ) : 0 );

	// Fecha: validar formato YYYY-MM-DD; guardar '' si no cuadra.
	$fecha = isset( $_POST['_hd_fecha'] ) ? sanitize_text_field( wp_unslash( $_POST['_hd_fecha'] ) ) : '';
	if ( $fecha && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) ) {
		$fecha = '';
	}
	update_post_meta( $post_id, '_hd_fecha', $fecha );

	// Hora: HH:MM opcional.
	$hora = isset( $_POST['_hd_hora'] ) ? sanitize_text_field( wp_unslash( $_POST['_hd_hora'] ) ) : '';
	if ( $hora && ! preg_match( '/^\d{2}:\d{2}$/', $hora ) ) {
		$hora = '';
	}
	update_post_meta( $post_id, '_hd_hora', $hora );

	$estadio = isset( $_POST['_hd_estadio'] ) ? sanitize_text_field( wp_unslash( $_POST['_hd_estadio'] ) ) : '';
	update_post_meta( $post_id, '_hd_estadio', $estadio );

	$canal = isset( $_POST['_hd_canal'] ) ? sanitize_text_field( wp_unslash( $_POST['_hd_canal'] ) ) : '';
	if ( '' === $canal ) {
		$canal = 'Win Sports';
	}
	update_post_meta( $post_id, '_hd_canal', $canal );
}

/* ============================================================
 * 3) Term meta `_hd_escudo` en la taxonomía `equipo`
 * ============================================================ */

/** Campo en la pantalla de AÑADIR término (sin valor previo). */
add_action( 'equipo_add_form_fields', function () {
	wp_nonce_field( 'hd_escudo_save', 'hd_escudo_nonce' );
	?>
	<div class="form-field term-hd-escudo-wrap">
		<label for="hd_escudo_id">Escudo del equipo</label>
		<input type="hidden" name="_hd_escudo" id="hd_escudo_id" value="">
		<p>
			<img id="hd_escudo_preview" src="" alt="" style="max-width:96px;height:auto;display:none;border:1px solid #dcdcde;padding:4px;background:#fff;">
		</p>
		<p>
			<button type="button" class="button" id="hd_escudo_pick">Elegir escudo</button>
			<button type="button" class="button" id="hd_escudo_clear">Quitar</button>
		</p>
		<p class="description">Imagen del escudo (PNG con fondo transparente recomendado). Si se deja vacío, se usa un respaldo por defecto.</p>
	</div>
	<?php
} );

/** Campo en la pantalla de EDITAR término (con valor y previsualización). */
add_action( 'equipo_edit_form_fields', function ( $term ) {
	$escudo_id = (int) get_term_meta( $term->term_id, '_hd_escudo', true );
	$preview   = $escudo_id ? wp_get_attachment_image_url( $escudo_id, 'thumbnail' ) : '';
	wp_nonce_field( 'hd_escudo_save', 'hd_escudo_nonce' );
	?>
	<tr class="form-field term-hd-escudo-wrap">
		<th scope="row"><label for="hd_escudo_id">Escudo del equipo</label></th>
		<td>
			<input type="hidden" name="_hd_escudo" id="hd_escudo_id" value="<?php echo esc_attr( $escudo_id ); ?>">
			<p>
				<img id="hd_escudo_preview" src="<?php echo esc_url( $preview ); ?>" alt="" style="max-width:96px;height:auto;<?php echo $preview ? '' : 'display:none;'; ?>border:1px solid #dcdcde;padding:4px;background:#fff;">
			</p>
			<p>
				<button type="button" class="button" id="hd_escudo_pick">Elegir escudo</button>
				<button type="button" class="button" id="hd_escudo_clear">Quitar</button>
			</p>
			<p class="description">Imagen del escudo. Si se deja vacío, se usa un respaldo por defecto (escudos del prototipo por slug).</p>
		</td>
	</tr>
	<?php
}, 10, 1 );

/** Guardado del term meta (crear y editar término de `equipo`). */
function hd_escudo_save_term( $term_id ) {
	if ( ! isset( $_POST['hd_escudo_nonce'] ) || ! wp_verify_nonce( $_POST['hd_escudo_nonce'], 'hd_escudo_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( ! isset( $_POST['_hd_escudo'] ) ) {
		return;
	}
	$escudo_id = absint( $_POST['_hd_escudo'] );
	if ( $escudo_id > 0 ) {
		update_term_meta( $term_id, '_hd_escudo', $escudo_id );
	} else {
		delete_term_meta( $term_id, '_hd_escudo' );
	}
}
add_action( 'created_equipo', 'hd_escudo_save_term' );
add_action( 'edited_equipo', 'hd_escudo_save_term' );

/** Media picker (JS inline) SOLO en las pantallas de términos de `equipo`. */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
		return;
	}
	$tax = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';
	if ( 'equipo' !== $tax ) {
		return;
	}
	wp_enqueue_media();
	$js = <<<'JS'
( function () {
	function ready( fn ) { if ( document.readyState !== 'loading' ) { fn(); } else { document.addEventListener( 'DOMContentLoaded', fn ); } }
	ready( function () {
		var frame;
		function bind() {
			var pick = document.getElementById( 'hd_escudo_pick' );
			var clear = document.getElementById( 'hd_escudo_clear' );
			var input = document.getElementById( 'hd_escudo_id' );
			var preview = document.getElementById( 'hd_escudo_preview' );
			if ( ! pick || ! input ) { return; }
			pick.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				if ( frame ) { frame.open(); return; }
				frame = wp.media( { title: 'Escudo del equipo', button: { text: 'Usar este escudo' }, library: { type: 'image' }, multiple: false } );
				frame.on( 'select', function () {
					var att = frame.state().get( 'selection' ).first().toJSON();
					input.value = att.id;
					if ( preview ) {
						var url = ( att.sizes && att.sizes.thumbnail ) ? att.sizes.thumbnail.url : att.url;
						preview.src = url;
						preview.style.display = '';
					}
				} );
				frame.open();
			} );
			if ( clear ) {
				clear.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					input.value = '';
					if ( preview ) { preview.src = ''; preview.style.display = 'none'; }
				} );
			}
		}
		bind();
		// La pantalla de "añadir" reinyecta el formulario tras crear un término (AJAX).
		document.addEventListener( 'ajaxComplete', bind );
	} );
} )();
JS;
	wp_add_inline_script( 'jquery-core', $js );
} );

/* ============================================================
 * 4) Helpers de escudo y render del Centro de Partidos
 * ============================================================ */

/**
 * URL del escudo de un equipo:
 *   1) term meta `_hd_escudo` (attachment) si existe;
 *   2) fallback por slug a los escudos bundled del prototipo;
 *   3) '' (el markup degrada omitiendo la imagen).
 */
function hd_equipo_escudo_url( $term_id, $size = 'medium' ) {
	$term_id = (int) $term_id;
	if ( $term_id > 0 ) {
		$att = (int) get_term_meta( $term_id, '_hd_escudo', true );
		if ( $att > 0 ) {
			$url = wp_get_attachment_image_url( $att, $size );
			if ( $url ) {
				return $url;
			}
		}
	}
	// Fallback por slug a los escudos bundled.
	$map = array(
		'junior'             => 'Escudo_Junior_de_Barranquilla_2018.png',
		'nacional'           => 'Atlético_Nacional.png',
		'millonarios'        => 'Escudo_Millos.png',
		'america'            => 'Escudo_América_de_Cali.png',
		'seleccion-colombia' => 'Escudo_de_la_Federación_Colombiana_de_Fútbol.png',
		'tolima'             => 'Escudo_Deportes_Tolima.png',
		'medellin'           => 'Escudo_del_Deportivo_Independiente_Medellín.png',
	);
	$term = $term_id > 0 ? get_term( $term_id, 'equipo' ) : null;
	if ( $term && ! is_wp_error( $term ) && isset( $map[ $term->slug ] ) ) {
		return hd_proto_uri() . '/img/' . $map[ $term->slug ];
	}
	return '';
}

/** Nombre visible de un equipo por term_id ('' si no existe). */
function hd_equipo_nombre( $term_id ) {
	$term_id = (int) $term_id;
	if ( $term_id <= 0 ) {
		return '';
	}
	$term = get_term( $term_id, 'equipo' );
	return ( $term && ! is_wp_error( $term ) ) ? $term->name : '';
}

/** Fecha larga en español a partir de 'YYYY-MM-DD': "Sábado 20 de Julio". */
function hd_fecha_larga_es( $ymd ) {
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $ymd ) ) {
		return '';
	}
	$ts = strtotime( $ymd . ' 12:00:00' );
	if ( ! $ts ) {
		return '';
	}
	$dias  = array( 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado' );
	$meses = array( '', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' );
	$w = (int) gmdate( 'w', $ts );
	$d = (int) gmdate( 'j', $ts );
	$m = (int) gmdate( 'n', $ts );
	return $dias[ $w ] . ' ' . $d . ' de ' . $meses[ $m ];
}

/** SVG calendario del prototipo (info del banner). */
function hd_svg_cal() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg>';
}

/** SVG pin de estadio del prototipo (info del banner). */
function hd_svg_pin() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
}

/**
 * Partidos próximos (fecha >= hoy), orden ascendente, límite 5.
 * Devuelve array de IDs (vacío si no hay).
 */
function hd_partidos_proximos( $limite = 5 ) {
	$today = current_time( 'Y-m-d' );
	$q = new WP_Query( array(
		'post_type'      => 'partido',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $limite,
		'meta_key'       => '_hd_fecha',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_hd_fecha',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
		'no_found_rows'  => true,
		'fields'         => 'ids',
	) );
	return ! empty( $q->posts ) ? $q->posts : array();
}

/**
 * Un `.match-banner` (mismo markup del prototipo) para un partido.
 * Reutiliza EXACTAMENTE las clases que espera home.js y el CSS.
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
	$kicker_bits = array_filter( array( $torneo_nom, $fecha_larga ) );
	$kicker      = implode( ' · ', $kicker_bits );
	if ( '' === $kicker ) {
		$kicker = get_the_title( $post_id );
	}

	$fecha_info = $fecha_larga;
	if ( $hora ) {
		$fecha_info = trim( $fecha_info . ' · ' . $hora );
	}

	$bg = get_the_post_thumbnail_url( $post_id, 'large' );

	$aria = trim( ( $torneo_nom ? $torneo_nom . ': ' : '' ) . $local_nom . ' vs ' . $visitante_nom . '. ' . $fecha_larga . ( $estadio ? ' en ' . $estadio : '' ) . '.' );

	$style = $bg ? ' style="background-image:url(\'' . esc_url( $bg ) . '\');"' : '';

	echo '<a class="match-banner" href="#"' . $style . ' aria-label="' . esc_attr( $aria ) . '">';
	echo '<span class="match-banner__overlay" aria-hidden="true"></span>';
	echo '<span class="match-banner__kicker">' . esc_html( $kicker ) . '</span>';
	echo '<span class="match-banner__body">';

	echo '<span class="match-banner__fixture">';
	if ( $local_escudo ) {
		echo '<img class="match-banner__crest" src="' . esc_url( $local_escudo ) . '" width="200" height="200" loading="lazy" alt="' . esc_attr( 'Escudo de ' . $local_nom ) . '">';
	}
	echo '<span class="match-banner__vs">vs</span>';
	if ( $visit_escudo ) {
		echo '<img class="match-banner__crest" src="' . esc_url( $visit_escudo ) . '" width="200" height="200" loading="lazy" alt="' . esc_attr( 'Escudo de ' . $visitante_nom ) . '">';
	}
	echo '</span>';

	echo '<span class="match-banner__info">';
	if ( $fecha_info ) {
		echo '<span class="match-banner__info-item">' . hd_svg_cal() . ' ' . esc_html( $fecha_info ) . '</span>';
	}
	if ( $estadio ) {
		echo '<span class="match-banner__info-item">' . hd_svg_pin() . ' ' . esc_html( $estadio ) . '</span>';
	}
	echo '</span>';

	echo '<span class="match-banner__tv">';
	if ( $canal && false !== stripos( $canal, 'win' ) ) {
		echo '<img class="match-banner__tv-logo" src="' . esc_url( hd_proto_uri() . '/img/Win_Sports_nuevo_logo.svg.webp' ) . '" width="3840" height="2171" loading="lazy" alt="' . esc_attr( $canal ) . '">';
	} elseif ( $canal ) {
		echo '<span class="match-banner__tv-text">' . esc_html( $canal ) . '</span>';
	}
	echo '</span>';

	echo '</span>'; // .match-banner__body
	echo '</a>';
}

/**
 * Un dot `[data-match-dot]` para un partido (paridad con el nº de banners).
 * $i es el índice base-0; el primero queda aria-current="true".
 */
function hd_render_match_dot( $post_id, $i ) {
	$local     = hd_equipo_nombre( (int) get_post_meta( $post_id, '_hd_local', true ) );
	$visitante = hd_equipo_nombre( (int) get_post_meta( $post_id, '_hd_visitante', true ) );
	$label     = 'Partido ' . ( $i + 1 ) . ': ' . $local . ' vs ' . $visitante;
	echo '<button class="match-slider__dot" type="button" data-match-dot aria-current="' . ( 0 === $i ? 'true' : 'false' ) . '" aria-label="' . esc_attr( $label ) . '"></button>';
}

/* ============================================================
 * 5) Siembra idempotente de partidos demo
 * ============================================================ */

/**
 * Sube al Media Library una copia de un archivo bundled del tema.
 * Copia a un temporal primero (media_handle_sideload mueve/borra el temp).
 * Devuelve el attachment ID o 0.
 */
function hd_sideload_local( $abs_path, $post_id = 0 ) {
	if ( ! file_exists( $abs_path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( basename( $abs_path ) );
	if ( ! $tmp ) {
		return 0;
	}
	if ( ! @copy( $abs_path, $tmp ) ) {
		@unlink( $tmp );
		return 0;
	}
	$file_array = array(
		'name'     => basename( $abs_path ),
		'tmp_name' => $tmp,
	);
	$id = media_handle_sideload( $file_array, (int) $post_id );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return 0;
	}
	return (int) $id;
}

/** term_id de un equipo por slug (0 si no existe). */
function hd_term_id_by_slug( $slug, $taxonomy ) {
	$t = get_term_by( 'slug', $slug, $taxonomy );
	return ( $t && ! is_wp_error( $t ) ) ? (int) $t->term_id : 0;
}

/** Asigna un escudo bundled como term meta si el equipo aún no tiene. */
function hd_seed_escudo( $slug, $filename ) {
	$term_id = hd_term_id_by_slug( $slug, 'equipo' );
	if ( ! $term_id ) {
		return;
	}
	if ( get_term_meta( $term_id, '_hd_escudo', true ) ) {
		return; // ya tiene escudo: no sobreescribir.
	}
	$abs = get_stylesheet_directory() . '/assets/proto/img/' . $filename;
	$att = hd_sideload_local( $abs );
	if ( $att ) {
		update_term_meta( $term_id, '_hd_escudo', $att );
	}
}

add_action( 'init', 'hd_seed_partidos', 25 ); // 25 = tras registrar CPT/taxonomías y sembrar términos (20).
function hd_seed_partidos() {
	if ( '1' === get_option( 'hd_partidos_seeded' ) ) {
		return;
	}
	// Requiere que el CPT y las taxonomías estén registrados.
	if ( ! post_type_exists( 'partido' ) || ! taxonomy_exists( 'equipo' ) ) {
		return;
	}

	// Escudos por equipo (term meta) para que la home muestre escudos reales.
	hd_seed_escudo( 'junior', 'Escudo_Junior_de_Barranquilla_2018.png' );
	hd_seed_escudo( 'nacional', 'Atlético_Nacional.png' );
	hd_seed_escudo( 'millonarios', 'Escudo_Millos.png' );
	hd_seed_escudo( 'america', 'Escudo_América_de_Cali.png' );
	hd_seed_escudo( 'seleccion-colombia', 'Escudo_de_la_Federación_Colombiana_de_Fútbol.png' );

	$hoy = current_time( 'timestamp' );
	$f   = function ( $dias_desde_hoy ) use ( $hoy ) {
		return gmdate( 'Y-m-d', $hoy + ( $dias_desde_hoy * DAY_IN_SECONDS ) );
	};

	$partidos = array(
		array(
			'title'     => 'Junior vs Millonarios',
			'local'     => 'junior',
			'visitante' => 'millonarios',
			'torneo'    => 'liga-betplay',
			'fecha'     => $f( 3 ),
			'hora'      => '19:30',
			'estadio'   => 'Metropolitano',
			'canal'     => 'Win Sports',
			'bg'        => 'junior_millonarios.png',
		),
		array(
			'title'     => 'Junior vs Nacional',
			'local'     => 'junior',
			'visitante' => 'nacional',
			'torneo'    => 'liga-betplay',
			'fecha'     => $f( 7 ),
			'hora'      => '18:00',
			'estadio'   => 'Metropolitano',
			'canal'     => 'Win Sports',
			'bg'        => 'junior_nacional.png',
		),
		array(
			'title'     => 'Selección Colombia vs América',
			'local'     => 'seleccion-colombia',
			'visitante' => 'america',
			'torneo'    => 'eliminatorias',
			'fecha'     => $f( 14 ),
			'hora'      => '19:00',
			'estadio'   => 'Metropolitano',
			'canal'     => 'Win Sports',
			'bg'        => 'colombia_peru.png',
		),
	);

	foreach ( $partidos as $p ) {
		$post_id = wp_insert_post( array(
			'post_type'   => 'partido',
			'post_status' => 'publish',
			'post_title'  => $p['title'],
		), true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}
		update_post_meta( $post_id, '_hd_local', hd_term_id_by_slug( $p['local'], 'equipo' ) );
		update_post_meta( $post_id, '_hd_visitante', hd_term_id_by_slug( $p['visitante'], 'equipo' ) );
		update_post_meta( $post_id, '_hd_torneo', hd_term_id_by_slug( $p['torneo'], 'torneo' ) );
		update_post_meta( $post_id, '_hd_fecha', $p['fecha'] );
		update_post_meta( $post_id, '_hd_hora', $p['hora'] );
		update_post_meta( $post_id, '_hd_estadio', $p['estadio'] );
		update_post_meta( $post_id, '_hd_canal', $p['canal'] );

		// Imagen destacada = foto de fondo del banner (sideload bundled).
		$abs = get_stylesheet_directory() . '/assets/proto/img/' . $p['bg'];
		$att = hd_sideload_local( $abs, $post_id );
		if ( $att ) {
			set_post_thumbnail( $post_id, $att );
		}
	}

	update_option( 'hd_partidos_seeded', '1' );
}
