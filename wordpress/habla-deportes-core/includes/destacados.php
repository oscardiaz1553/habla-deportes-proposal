<?php
/**
 * Habla Deportes Core — Marcar piezas para la portada.
 * ---------------------------------------------------------------
 * Da a la redacción el control de qué sale en el hero, sin tocar
 * Elementor: una casilla en la pantalla de edición de videos y
 * entradas, más una columna con estrella en los listados para marcar y
 * desmarcar de un clic.
 *
 * El dato es la meta `_hd_destacado` con valor '1'. Protegida (guion
 * bajo) a propósito: se gestiona desde esta interfaz, no como campo
 * personalizado suelto que cualquiera pueda escribir a mano.
 *
 * Entre las marcadas manda la fecha: lo último publicado abre la
 * portada. Sin números de prioridad que mantener.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Tipos de contenido que pueden ir al hero. */
function hd_destacado_post_types() {
	return array( 'video', 'post' );
}

/* ---------------------------------------------------------------
 * 1) Casilla en la pantalla de edición.
 * ------------------------------------------------------------- */
add_action( 'add_meta_boxes', function () {
	foreach ( hd_destacado_post_types() as $tipo ) {
		add_meta_box(
			'hd_destacado',
			'Portada',
			'hd_destacado_metabox',
			$tipo,
			'side',
			'high'
		);
	}
} );

function hd_destacado_metabox( $post ) {
	$marcado = '1' === get_post_meta( $post->ID, '_hd_destacado', true );
	wp_nonce_field( 'hd_destacado_guardar', 'hd_destacado_nonce' );
	?>
	<p>
		<label>
			<input type="checkbox" name="hd_destacado" value="1" <?php checked( $marcado ); ?>>
			<strong>Mostrar en portada</strong>
		</label>
	</p>
	<p class="description">
		Las piezas marcadas son las que aparecen en el destacado grande de la
		página de inicio, de la más reciente a la más antigua. Si no hay
		suficientes marcadas, el hueco se rellena con lo último publicado.
	</p>
	<?php
}

add_action( 'save_post', function ( $post_id, $post ) {
	// Guarda solo cuando el formulario que trae la casilla se ha enviado de
	// verdad: sin esto, una edición rápida o una edición en lote —donde la
	// casilla no existe— borraría la marca sin querer.
	if ( ! isset( $_POST['hd_destacado_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( $_POST['hd_destacado_nonce'] ), 'hd_destacado_guardar' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! in_array( $post->post_type, hd_destacado_post_types(), true ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['hd_destacado'] ) ) {
		update_post_meta( $post_id, '_hd_destacado', '1' );
	} else {
		delete_post_meta( $post_id, '_hd_destacado' );
	}
}, 10, 2 );

/* ---------------------------------------------------------------
 * 2) Columna con estrella en los listados, para marcar de un clic.
 * ------------------------------------------------------------- */
foreach ( array( 'video', 'post' ) as $hd_tipo ) {
	add_filter( "manage_{$hd_tipo}_posts_columns", function ( $columnas ) {
		// La estrella va justo después de la casilla de selección.
		$nuevas = array();
		foreach ( $columnas as $clave => $etiqueta ) {
			$nuevas[ $clave ] = $etiqueta;
			if ( 'cb' === $clave ) {
				$nuevas['hd_destacado'] = '<span title="Portada" class="dashicons dashicons-star-filled"></span>';
			}
		}
		return $nuevas;
	} );

	add_action( "manage_{$hd_tipo}_posts_custom_column", function ( $columna, $post_id ) {
		if ( 'hd_destacado' !== $columna ) {
			return;
		}

		$marcado = '1' === get_post_meta( $post_id, '_hd_destacado', true );
		$url     = wp_nonce_url(
			admin_url( 'admin-post.php?action=hd_toggle_destacado&post=' . (int) $post_id ),
			'hd_destacado_toggle_' . $post_id
		);

		printf(
			'<a href="%s" title="%s" style="text-decoration:none;"><span class="dashicons dashicons-%s" style="color:%s"></span></a>',
			esc_url( $url ),
			esc_attr( $marcado ? 'Quitar de portada' : 'Mostrar en portada' ),
			$marcado ? 'star-filled' : 'star-empty',
			$marcado ? '#E36209' : '#c3c4c7'
		);
	}, 10, 2 );
}

/** Conmutador de la estrella. Vuelve al listado de donde salió. */
add_action( 'admin_post_hd_toggle_destacado', function () {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'No tienes permisos para cambiar esta pieza.' );
	}
	check_admin_referer( 'hd_destacado_toggle_' . $post_id );

	if ( '1' === get_post_meta( $post_id, '_hd_destacado', true ) ) {
		delete_post_meta( $post_id, '_hd_destacado' );
	} else {
		update_post_meta( $post_id, '_hd_destacado', '1' );
	}

	$volver = wp_get_referer();
	wp_safe_redirect( $volver ? $volver : admin_url( 'edit.php?post_type=' . get_post_type( $post_id ) ) );
	exit;
} );

/** Un poco de aire para que la columna no se coma el ancho del título. */
add_action( 'admin_head', function () {
	$pantalla = get_current_screen();
	if ( ! $pantalla || 'edit' !== $pantalla->base ) {
		return;
	}
	if ( ! in_array( $pantalla->post_type, hd_destacado_post_types(), true ) ) {
		return;
	}
	echo '<style>.column-hd_destacado{width:32px;text-align:center}'
		. '.column-hd_destacado .dashicons{font-size:18px;line-height:1.4}</style>';
} );

/* ---------------------------------------------------------------
 * 3) Consulta: las marcadas para portada.
 * ------------------------------------------------------------- */

/**
 * Piezas marcadas para portada, de la más reciente a la más antigua.
 *
 * @param int    $cantidad Cuántas como máximo.
 * @param string $fuente   video|post|ambos.
 * @return WP_Post[]
 */
function hd_destacados( $cantidad, $fuente = 'video' ) {
	$tipos = array(
		'video' => array( 'video' ),
		'post'  => array( 'post' ),
		'ambos' => array( 'video', 'post' ),
	);

	return get_posts( array(
		'post_type'      => isset( $tipos[ $fuente ] ) ? $tipos[ $fuente ] : array( 'video' ),
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $cantidad ),
		'meta_key'       => '_hd_destacado',
		'meta_value'     => '1',
		'no_found_rows'  => true,
	) );
}
