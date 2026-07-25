<?php
/**
 * Hello Elementor Child — Habla Deportes
 * ---------------------------------------------------------------
 * Encola, en este orden:
 *   1) style.css del tema padre (Hello Elementor)
 *   2) tokens.css   -> variables de diseño del prototipo aprobado
 *   3) components.css / components.js -> componentes a medida
 *      (hero swipe, centro de partidos, clips, topbar, megamenús),
 *      portados desde el prototipo a medida que se integran.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acceso directo prohibido.
}

define( 'HD_CHILD_VERSION', '1.4.0' );

/* Módulos del tema: modelo de contenido + siembra de términos +
   importador de YouTube (con clasificador) + CPT Partido (Centro de
   Partidos editable) + helpers de plantilla. */
require_once get_stylesheet_directory() . '/inc/cpt.php';
require_once get_stylesheet_directory() . '/inc/taxonomy-seed.php';
require_once get_stylesheet_directory() . '/inc/youtube-importer.php';
require_once get_stylesheet_directory() . '/inc/template-helpers.php';
require_once get_stylesheet_directory() . '/inc/cpt-partido.php';

/**
 * Versión por fecha de modificación del archivo (cache-bust automático:
 * al tocar un asset, la URL cambia sola sin subir HD_CHILD_VERSION).
 */
function hd_asset_ver( $relative_path ) {
	$file = get_stylesheet_directory() . $relative_path;
	$mtime = file_exists( $file ) ? filemtime( $file ) : false;
	return $mtime ? (string) $mtime : HD_CHILD_VERSION;
}

add_action( 'wp_enqueue_scripts', function () {

	// 1) Estilo del tema padre.
	wp_enqueue_style(
		'hello-elementor-parent',
		get_template_directory_uri() . '/style.css',
		array(),
		HD_CHILD_VERSION
	);

	// 2) Tokens de diseño del prototipo (colores, tipografía, espaciado, radios…).
	wp_enqueue_style(
		'hd-tokens',
		get_stylesheet_directory_uri() . '/assets/css/tokens.css',
		array( 'hello-elementor-parent' ),
		HD_CHILD_VERSION
	);

	// 3) Componentes a medida (se van llenando al portar cada pieza del prototipo).
	wp_enqueue_style(
		'hd-components',
		get_stylesheet_directory_uri() . '/assets/css/components.css',
		array( 'hd-tokens' ),
		HD_CHILD_VERSION
	);

	wp_enqueue_script(
		'hd-components',
		get_stylesheet_directory_uri() . '/assets/js/components.js',
		array(),
		HD_CHILD_VERSION,
		true // en el footer
	);

	// 4) CSS del prototipo aprobado (portado tal cual a assets/proto).
	//    main.css referencia imágenes con url('../img/...'), por eso la
	//    carpeta proto conserva la MISMA estructura css/ + img/ del prototipo.
	wp_enqueue_style(
		'hd-proto-main',
		get_stylesheet_directory_uri() . '/assets/proto/css/main.css',
		array( 'hd-tokens', 'hd-components' ),
		hd_asset_ver( '/assets/proto/css/main.css' )
	);

	wp_enqueue_style(
		'hd-proto-megamenu',
		get_stylesheet_directory_uri() . '/assets/proto/css/megamenu.css',
		array( 'hd-proto-main' ),
		hd_asset_ver( '/assets/proto/css/megamenu.css' )
	);

	// 5) JS del prototipo (vanilla, en el footer, mismo orden que el prototipo).
	wp_enqueue_script(
		'hd-proto-home',
		get_stylesheet_directory_uri() . '/assets/proto/js/home.js',
		array(),
		hd_asset_ver( '/assets/proto/js/home.js' ),
		true
	);

	wp_enqueue_script(
		'hd-proto-megamenu',
		get_stylesheet_directory_uri() . '/assets/proto/js/megamenu.js',
		array( 'hd-proto-home' ),
		hd_asset_ver( '/assets/proto/js/megamenu.js' ),
		true
	);
}, 20 );

/**
 * Hello Elementor estila las MISMAS clases del prototipo (.site-header /
 * .site-footer / .site-main) con ancho "boxed" de 1140px (en theme.css y
 * header-footer.css) y rompía el ancho completo. Este tema trae su propio
 * sistema de estilos completo, así que desactivamos las hojas OPINADAS del
 * padre con sus filtros oficiales (el reset de Hello se conserva).
 */
add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );

add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'hello-elementor-header-footer' );
	wp_deregister_style( 'hello-elementor-header-footer' );
}, 30 );

/**
 * Tipografías del prototipo: Archivo (display) + Inter (cuerpo).
 * Se cargan desde Google Fonts con preconnect (mismo par del prototipo).
 * Cuando el sitio pase a producción se puede migrar a self-hosting si
 * se quiere exprimir el rendimiento.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'hd-fonts',
		'https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap',
		array(),
		null
	);
}, 10 );

add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}, 10, 2 );
