<?php
/**
 * yoast-guard.php — primer paso tras instalar Yoast (Premium) en un STAGING: apaga IndexNow y el
 * envío de datos de uso.
 *
 * Por qué: el IndexNow de Yoast Premium avisa a Bing/Yandex en cada publicación aunque el sitio
 * tenga blog_public=0; solo mira enable_index_now y que wp_get_environment_type() sea
 * 'production' (y sin WP_ENVIRONMENT_TYPE definido, el entorno ES production). En el caso de
 * referencia estuvo enviando URLs de staging hasta que se apagó.
 *
 * Estado: probado en HomeMaker (Yoast 27.7). Genérico.
 * Uso:    wp --path=<WP_PATH> --user=1 eval-file yoast-guard.php
 * Deshacer (solo en producción, al lanzar): WPSEO_Options::set( 'enable_index_now', true );
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPSEO_Options' ) ) {
	WP_CLI::error( 'Yoast SEO no está activo.' );
}
WPSEO_Options::set( 'enable_index_now', false );
WPSEO_Options::set( 'tracking', false );
$w = get_option( 'wpseo' );
echo 'enable_index_now=' . var_export( $w['enable_index_now'] ?? null, true ) . ' tracking=' . var_export( $w['tracking'] ?? null, true ) . ' entorno=' . wp_get_environment_type() . ' blog_public=' . get_option( 'blog_public' ) . "\n";
