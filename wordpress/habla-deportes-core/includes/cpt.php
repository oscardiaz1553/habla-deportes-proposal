<?php
/**
 * Habla Deportes Core — Modelo de contenido.
 * CPT `video` + taxonomías `equipo` / `torneo` / `tema`
 * (compartidas con las entradas normales, que harán de "notas").
 *
 * El refresco de permalinks NO vive aquí: lo hace el
 * register_activation_hook del plugin principal.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tamaño de imagen 16:9 recortado.
 * ---------------------------------------------------------------
 * Las miniaturas de YouTube vienen en 4:3 con bandas negras arriba y
 * abajo (hqdefault). Registrando el recorte como tamaño de WordPress,
 * el widget Imagen de Elementor lo elige en un desplegable y la tarjeta
 * sale en 16:9 sin recortes por CSS ni contenedores auxiliares.
 */
add_action( 'after_setup_theme', function () {
	add_image_size( 'hd-16x9', 640, 360, true );
	add_image_size( 'hd-16x9-grande', 1280, 720, true );
} );

/** Que los dos tamaños aparezcan con nombre legible en los selectores. */
add_filter( 'image_size_names_choose', function ( $sizes ) {
	$sizes['hd-16x9']        = 'Video 16:9 (640×360)';
	$sizes['hd-16x9-grande'] = 'Video 16:9 grande (1280×720)';

	return $sizes;
} );

add_action( 'init', 'hd_register_content_model' );
function hd_register_content_model() {

	register_post_type( 'video', array(
		'labels'        => array(
			'name'          => 'Videos',
			'singular_name' => 'Video',
			'add_new'       => 'Añadir video',
			'add_new_item'  => 'Añadir video',
			'edit_item'     => 'Editar video',
			'all_items'     => 'Todos los videos',
			'search_items'  => 'Buscar videos',
			'not_found'     => 'No hay videos todavía.',
		),
		'public'        => true,
		'menu_position' => 5,
		'menu_icon'     => 'dashicons-video-alt3',
		'has_archive'   => 'videos',
		'rewrite'       => array( 'slug' => 'video' ),
		'show_in_rest'  => true, // editor de bloques + Elementor
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
	) );

	$tax_base = array(
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
	);

	register_taxonomy( 'equipo', array( 'video', 'post' ), array_merge( $tax_base, array(
		'labels'  => array( 'name' => 'Equipos', 'singular_name' => 'Equipo' ),
		'rewrite' => array( 'slug' => 'equipo' ),
	) ) );

	register_taxonomy( 'torneo', array( 'video', 'post' ), array_merge( $tax_base, array(
		'labels'  => array( 'name' => 'Torneos', 'singular_name' => 'Torneo' ),
		'rewrite' => array( 'slug' => 'torneo' ),
	) ) );

	register_taxonomy( 'tema', array( 'video', 'post' ), array_merge( $tax_base, array(
		'labels'  => array( 'name' => 'Temas', 'singular_name' => 'Tema' ),
		'rewrite' => array( 'slug' => 'tema' ),
	) ) );
}
