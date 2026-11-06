<?php
/**
 * estado-sitio.php · Foto de solo lectura del estado de un WordPress en migración.
 *
 * Propósito: comprobar de una vez las casillas de las puertas B, D y del lanzamiento
 *   (runbook §2): que WordPress carga con sus plugins, URL, portada, kit, visibilidad,
 *   cortina de Elementor, IndexNow, login propio, plugins activos, administradores y que el
 *   orden de clases globales V4 de «frontend» y «preview» coincide (si no, publicar en el
 *   editor borraría clases del sitio).
 * Uso:     wp --path=<WP_PATH> eval-file estado-sitio.php --user=1
 *          (en la obra, antes del cambio: --path="$HOME/<PREFIJO>-wp-nuevo"; con ~/ tras
 *          «--path=» bash no expande la tilde)
 * Parámetros: ninguno.
 * Estado:  generalizado, no probado fuera de HomeMaker. Cada comprobación sale de comandos
 *          usados en HomeMaker (runbook pasos 9, 12-H y 13b del caso de referencia).
 * Privacidad: no imprime logins, ni el slug de acceso, ni correos completos (solo el dominio
 *          de admin_email), ni la llave de la cortina.
 * Riesgos: ninguno; no escribe.
 */

defined( 'ABSPATH' ) || exit;

$si = function ( $v ) {
	return $v ? 'sí' : 'no';
};

echo "== URL y portada\n";
echo 'home=' . home_url() . ' · siteurl=' . site_url() . "\n";
echo 'portada=' . get_option( 'page_on_front' ) . ' (' . get_option( 'show_on_front' ) . ') · kit=' . get_option( 'elementor_active_kit' ) . "\n";
echo 'ABSPATH=' . ABSPATH . "\n";

echo "== Visibilidad y cortina\n";
echo 'blog_public=' . get_option( 'blog_public' ) . "\n";
echo 'modo mantenimiento=' . var_export( get_option( 'elementor_maintenance_mode_mode' ), true )
	. ' · plantilla=' . get_option( 'elementor_maintenance_mode_template_id' )
	. ' · excluye=' . var_export( get_option( 'elementor_maintenance_mode_exclude_mode' ), true ) . "\n";
if ( class_exists( 'WPSEO_Options' ) ) {
	echo 'IndexNow (Yoast)=' . var_export( WPSEO_Options::get( 'enable_index_now' ), true ) . "\n";
}
echo 'WP_ENVIRONMENT_TYPE=' . wp_get_environment_type() . "\n";

echo "== Acceso\n";
$tipo = get_option( 'sg_security_login_type' );
if ( false !== $tipo ) {
	$slug = (string) get_option( 'sg_security_login_url' );
	echo 'SG Security login propio=' . $si( 'custom' === $tipo ) . ' · slug con barras=' . $si( false !== strpos( $slug, '/' ) ) . " (debe ser «no»)\n";
}
$admin = (string) get_option( 'admin_email' );
echo 'admin_email (dominio)=' . substr( strrchr( $admin, '@' ), 1 ) . ' · cambio pendiente=' . $si( get_option( 'new_admin_email' ) ) . "\n";
$admins = get_users( array( 'role' => 'administrator', 'fields' => array( 'user_registered' ) ) );
$fechas = array_map( function ( $u ) {
	return $u->user_registered;
}, $admins );
sort( $fechas );
echo 'administradores=' . count( $admins ) . ' · alta más reciente=' . ( $fechas ? end( $fechas ) : '-' ) . "\n";

echo "== Plugins\n";
$activos = (array) get_option( 'active_plugins' );
sort( $activos );
echo count( $activos ) . ' activos: ' . implode( ', ', array_map( 'dirname', $activos ) ) . "\n";
if ( ! function_exists( 'get_mu_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$mu = array_keys( get_mu_plugins() );
echo 'mu-plugins: ' . ( $mu ? implode( ', ', $mu ) : '-' ) . "\n";
echo 'PLL_COOKIE=' . ( defined( 'PLL_COOKIE' ) ? var_export( PLL_COOKIE, true ) : 'sin definir' ) . "\n";

echo "== Idiomas\n";
if ( function_exists( 'pll_languages_list' ) ) {
	echo 'idiomas=' . implode( ',', pll_languages_list() ) . ' · por defecto=' . pll_default_language() . "\n";
} else {
	echo "Polylang no está activo\n";
}

echo "== Clases globales V4 (frontend = preview)\n";
$kit = (int) get_option( 'elementor_active_kit' );
$orden = function ( $clave ) use ( $kit ) {
	$d = get_post_meta( $kit, $clave, true );
	while ( is_string( $d ) && '' !== $d ) {
		$j = json_decode( $d, true );
		if ( null === $j ) {
			break;
		}
		$d = $j;
	}
	return is_array( $d ) ? ( $d['order'] ?? $d ) : array();
};
$a = $orden( '_elementor_global_classes_order' );
$b = $orden( '_elementor_global_classes_order_preview' );
$posts = count( get_posts( array( 'post_type' => 'e_global_class', 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids', 'lang' => '' ) ) );
echo 'kit ' . $kit . ' · frontend ' . count( (array) $a ) . ' · preview ' . count( (array) $b ) . ' · posts e_global_class ' . $posts . ' · iguales: ' . ( $a === $b ? 'sí' : 'NO' ) . "\n";

echo "== Theme Builder\n";
echo 'condiciones=' . wp_json_encode( get_option( 'elementor_pro_theme_builder_conditions' ) ) . "\n";

echo "estado-sitio: fin\n";
