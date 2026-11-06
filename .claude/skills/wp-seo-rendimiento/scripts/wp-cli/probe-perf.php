<?php
/**
 * probe-perf.php — inventario de SOLO LECTURA antes de tocar rendimiento o SEO.
 *
 * Imprime: experimentos de Elementor (estado, valor por defecto, madurez), ajustes de
 * rendimiento de Elementor, opciones de Polylang y PLL_COOKIE, mu-plugins, opciones del
 * optimizador de SiteGround (si las hay), banderas de Yoast, plantillas de título de cada tipo
 * público, redes y blogname/blogdescription/site_icon.
 * Sirve para explicar al usuario qué funciones de velocidad ya están activas (en Elementor >= 4.2.4
 * casi todas) y para no activar dos métodos para lo mismo.
 *
 * Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
 * Uso:    wp --path=<WP_PATH> --user=1 eval-file probe-perf.php
 * Riesgos: ninguno, no escribe. La salida contiene URLs y ajustes del sitio: no la pegues en
 *          documentos públicos.
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\Elementor\Plugin' ) ) {
	echo "--- experimentos de Elementor\n";
	foreach ( \Elementor\Plugin::$instance->experiments->get_features() as $name => $f ) {
		printf( "%-40s state=%-8s default=%-8s release=%s\n", $name, $f['state'] ?? '', $f['default'] ?? '', $f['release_status'] ?? '' );
	}
}

echo "--- ajustes de rendimiento (Elementor › Ajustes)\n";
foreach ( [ 'elementor_css_print_method', 'elementor_google_font', 'elementor_font_display', 'elementor_local_google_fonts', 'elementor_optimized_image_loading', 'elementor_optimized_gutenberg_loading', 'elementor_lazy_load_background_images', 'elementor_element_cache_ttl', 'elementor_load_fa4_shim', 'elementor_meta_generator_tag', 'elementor_unfiltered_files_upload' ] as $o ) {
	printf( "%-42s %s\n", $o, var_export( get_option( $o, '(no existe)' ), true ) );
}

echo "--- polylang\n";
$p = get_option( 'polylang' );
if ( is_array( $p ) ) {
	foreach ( [ 'browser', 'force_lang', 'hide_default', 'rewrite', 'redirect_lang', 'media_support', 'post_types', 'taxonomies', 'default_lang' ] as $k ) {
		echo "$k=" . wp_json_encode( $p[ $k ] ?? null ) . "\n";
	}
}
echo 'PLL_COOKIE=' . var_export( defined( 'PLL_COOKIE' ) ? PLL_COOKIE : null, true ) . "   (false = sin cookie de idioma; null = Polylang la envía)\n";

echo "--- mu-plugins\n";
print_r( array_keys( get_mu_plugins() ) );

echo "--- optimizador de SiteGround (todas las opciones)\n";
global $wpdb;
foreach ( $wpdb->get_results( "SELECT option_name, LEFT(option_value,80) v FROM {$wpdb->options} WHERE option_name LIKE 'siteground_optimizer_%'" ) as $r ) {
	echo $r->option_name . ' = ' . $r->v . "\n";
}

echo "--- yoast\n";
$w = get_option( 'wpseo' );
if ( is_array( $w ) ) {
	foreach ( [ 'keyword_analysis_active', 'content_analysis_active', 'enable_xml_sitemap', 'enable_admin_bar_menu', 'first_time_install', 'should_redirect_after_install_free', 'indexables_indexing_completed', 'enable_index_now', 'tracking', 'enable_enhanced_slack_sharing', 'enable_llms_txt', 'enable_ai_generator' ] as $k ) {
		echo "$k=" . wp_json_encode( $w[ $k ] ?? null ) . "\n";
	}
}
$t = get_option( 'wpseo_titles' );
if ( is_array( $t ) ) {
	$claves = [ 'separator', 'company_or_person', 'company_name', 'company_logo', 'website_name', 'title-page', 'metadesc-page', 'noindex-author-wpseo', 'disable-author', 'disable-date', 'disable-attachment', 'breadcrumbs-enable', 'open_graph_frontpage_title', 'title-404-wpseo' ];
	foreach ( get_post_types( [ 'public' => true, '_builtin' => false ] ) as $cpt ) {
		array_push( $claves, "title-$cpt", "metadesc-$cpt", "noindex-$cpt" );
	}
	foreach ( $claves as $k ) {
		echo "$k=" . wp_json_encode( $t[ $k ] ?? null, JSON_UNESCAPED_UNICODE ) . "\n";
	}
}
echo 'social=' . wp_json_encode( get_option( 'wpseo_social' ), JSON_UNESCAPED_SLASHES ) . "\n";
echo 'blog_public=' . get_option( 'blog_public' ) . ' | WP_ENVIRONMENT_TYPE=' . wp_get_environment_type() . "\n";
echo 'blogname=' . get_option( 'blogname' ) . ' | blogdescription=' . get_option( 'blogdescription' ) . ' | site_icon=' . get_option( 'site_icon' ) . "\n";
