<?php
/**
 * Habla Deportes Core — Integración con Elementor.
 * ---------------------------------------------------------------
 * Registra los widgets propios del proyecto: las piezas de la portada
 * que no tienen equivalente nativo (hero de video con crossfade,
 * marquesina de titulares y Centro de Partidos).
 *
 * Por qué widgets y no shortcodes: un shortcode es una caja negra —
 * en el editor solo puedes tocar el contenedor que lo envuelve. Un
 * widget aparece en el panel con sus propios controles de contenido y
 * de estilo, se edita con vista previa en vivo y se comporta como
 * cualquier widget nativo.
 *
 * Los shortcodes se mantienen: son el mismo render, así que lo ya
 * montado con ellos sigue funcionando.
 *
 * Los controles de estilo apuntan a los selectores del CSS del plugin
 * mediante {{WRAPPER}}, de modo que home.css pone los valores por
 * defecto (los del prototipo aprobado) y el panel los sobreescribe
 * por instancia.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Categoría propia en el panel de widgets. */
add_action( 'elementor/elements/categories_registered', function ( $manager ) {
	$manager->add_category( 'habla-deportes', array(
		'title' => 'Habla Deportes',
		'icon'  => 'eicon-font',
	) );
} );

/** Registro de los widgets. */
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once HDC_PATH . 'includes/widgets/class-hd-widget-hero.php';
	require_once HDC_PATH . 'includes/widgets/class-hd-widget-ticker.php';
	require_once HDC_PATH . 'includes/widgets/class-hd-widget-partidos.php';

	$widgets_manager->register( new HD_Widget_Hero() );
	$widgets_manager->register( new HD_Widget_Ticker() );
	$widgets_manager->register( new HD_Widget_Partidos() );
} );

/**
 * En el editor, el CSS y el JS deben estar SIEMPRE encolados: los widgets
 * se pintan por AJAX y el encolado bajo demanda de la portada no llega a
 * tiempo, así que sin esto la vista previa sale sin estilos.
 */
add_action( 'elementor/editor/after_enqueue_scripts', function () {
	wp_enqueue_style( 'hd-home' );
	wp_enqueue_script( 'hd-home' );
} );

add_action( 'elementor/preview/enqueue_styles', function () {
	wp_enqueue_style( 'hd-home' );
} );
