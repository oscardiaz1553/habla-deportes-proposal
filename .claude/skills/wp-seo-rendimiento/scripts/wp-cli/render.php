<?php
/**
 * render.php — pinta una URL del frontend desde WP-CLI como el usuario 1 y la escribe en stdout.
 *
 * Para verificar el HTML real cuando curl no puede: con la cortina («Próximamente»/Mantenimiento
 * de Elementor) activa, que excluye a los usuarios con sesión, o para comprobar un filtro sin
 * desplegar nada público. Aplica los búferes de salida anidados (p. ej. un normalizador de <img>
 * abierto en get_header), así que el HTML es el que recibiría el visitante.
 *
 * Estado: probado en HomeMaker. Genérico.
 * Uso:    RENDER_URL=/ruta/ wp --path=<WP_PATH> eval-file render.php 2>/dev/null > /tmp/pagina.html
 *         (luego: grep, o las comprobaciones de checks-html.sh sobre el archivo)
 * Trampas resueltas aquí: wp-cli no define WP_USE_THEMES (sin él template-loader.php no pinta
 *   nada: 0 bytes); redirect_canonical y la falta de HTTPS redirigían en vez de pintar.
 * Limitación: Polylang no resuelve /en/ (portada estática del segundo idioma) desde CLI: esa URL
 *   se comprueba en vivo. El aviso «Undefined array key SERVER_NAME» es benigno.
 * Riesgos: solo lectura; ve el sitio como administrador (sin barra de admin).
 */

defined( 'ABSPATH' ) || exit;

$path = getenv( 'RENDER_URL' ) ?: '/';
if ( ! defined( 'WP_USE_THEMES' ) ) {
	define( 'WP_USE_THEMES', true );
}
wp_set_current_user( 1 );
$_SERVER['REQUEST_URI']    = $path;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS']          = 'on';
remove_action( 'template_redirect', 'redirect_canonical' );
add_filter( 'wp_redirect', function ( $l ) {
	fwrite( STDERR, "REDIRECT → $l\n" );
	return $l;
} );
$_SERVER['HTTP_HOST'] = wp_parse_url( home_url(), PHP_URL_HOST );
add_filter( 'show_admin_bar', '__return_false' );
wp();
$base = ob_get_level();
ob_start();
require ABSPATH . WPINC . '/template-loader.php';
while ( ob_get_level() > $base + 1 ) {
	ob_end_flush(); // aplica los callbacks de los búferes que abrió el tema
}
echo ob_get_clean();
