<?php
/**
 * Habla Deportes — Modelo de contenido.
 * CPT `video` + taxonomías `equipo` / `torneo` / `tema`
 * (compartidas con las entradas normales, que harán de "notas").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

/**
 * Refresca los permalinks UNA sola vez tras registrar el modelo
 * (equivale a entrar a Ajustes → Enlaces permanentes → Guardar).
 */
add_action( 'init', function () {
	if ( '3' !== get_option( 'hd_model_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'hd_model_flushed', '3' );
	}
}, 99 );
