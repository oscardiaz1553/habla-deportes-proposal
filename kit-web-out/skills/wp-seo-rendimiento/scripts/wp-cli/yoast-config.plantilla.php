<?php
/**
 * yoast-config.plantilla.php — configuración completa de Yoast SEO por script. Idempotente.
 *
 * Qué hace: identidad (blogname, sitio, organización, logo), títulos y separador, tipos y
 * taxonomías indexables, archivos de autor/fecha/adjuntos, migas, redes, limpieza del <head> y
 * del rastreo, IndexNow/tracking/llms.txt apagados, y la meta de cada página desde un JSON.
 * Por qué por script: la interfaz de Yoast no es scriptable y escribir las opciones a pelo salta
 * sus validadores y sus watchers. Aquí todo pasa por WPSEO_Options::set (update_option →
 * sanitize_option_{wpseo_*} → validate) y update_post_meta (el watcher reconstruye el indexable).
 * Tras cada set se relee con WPSEO_Options::get y se imprime «REVISAR» si el validador lo cambió.
 *
 * Estado: generalizado de la configuración del caso de referencia (HomeMaker, Yoast 27.7);
 *         no probado fuera de HomeMaker.
 * Uso:
 *   1. Rellena $CFG. Mientras quede un marcador <ASÍ> el script se niega a escribir.
 *   2. Un JSON de datos por idioma, formato de yoast-datos.ejemplo.json (valida longitudes antes).
 *   3. YOAST_DATA=datos-<IDIOMA_BASE>.json wp --path=<WP_PATH> --user=1 eval-file yoast-config.plantilla.php
 *      YOAST_DATA=datos-<IDIOMA_2>.json    wp --path=<WP_PATH> --user=1 eval-file yoast-config.plantilla.php
 *      (sin YOAST_DATA lee datos-es.json; el JSON va en la misma carpeta que el script)
 *   4. wp --path=<WP_PATH> yoast index --reindex --skip-confirmation  y purga la caché de página
 *      (robots.txt y los sitemaps también se cachean).
 * Riesgos:
 *   - Cambia blogname: afecta og:site_name, el feed y deja huérfana su traducción en Polylang (la
 *     clave es el texto de origen). Avisa a quien lleve las cadenas.
 *   - company_logo (URL) va ANTES que company_logo_id: el validador de company_logo reinicia _id/_meta.
 *   - No toca las traducciones de las plantillas de título (cadenas de Polylang del grupo
 *     wordpress-seo) ni hreflang: ver references/seo-yoast-polylang.md.
 *   - IndexNow, tracking y llms.txt quedan apagados: se encienden SOLO en producción, al lanzar.
 */

defined( 'ABSPATH' ) || exit;

$CFG = [
	'blogname'               => '<MARCA>', // SOLO la marca: alimenta %%sitename%% en todos los títulos
	'website_name'           => '<MARCA>',
	'alternate_website_name' => '',        // opcional (otro nombre con el que se conoce la marca)
	'company_name'           => '<MARCA>',
	'company_alternate_name' => '',
	'org_description'        => '<DESCRIPCION_DE_LA_ORGANIZACION_EN_IDIOMA_BASE>', // no traducible: el otro idioma va por filtro
	'org_founding_date'      => '',        // 'AAAA', solo si el cliente lo confirma
	'logo_id'                => 0,         // ID del adjunto del logo (company_logo)
	'og_id'                  => 0,         // ID de la imagen para redes por defecto
	'separator'              => 'sc-mdash', // «Título — Marca»
	'title_page'             => '%%title%% %%page%% %%sep%% %%sitename%%',
	'breadcrumbs_home'       => '<INICIO_EN_IDIOMA_BASE>',
	'twitter_card_type'      => 'summary_large_image',
	'sin_blog'               => true,      // noindex en category y post_tag
	// CPT indexables con sus plantillas. %%cf_<meta>%% sobrevive al saneado de Yoast.
	'cpts'                   => [
		// '<cpt>' => [ 'title' => '%%title%% %%sep%% %%sitename%%', 'metadesc' => '%%cf_<meta_intro>%%', 'social_description' => '%%cf_<meta_intro>%%', 'page_type' => 'WebPage', 'article_type' => 'None' ],
	],
	// Taxonomías de filtro (públicas pero sin URL propia): noindex y sin metabox. Nunca como maintax.
	'tax_auxiliares'         => [],
];

/* ---------- guardas ---------- */
if ( ! class_exists( 'WPSEO_Options' ) || ! class_exists( 'WPSEO_Meta' ) ) {
	WP_CLI::error( 'Yoast SEO no está activo.' );
}
array_walk_recursive( $CFG, function ( $v, $k ) {
	if ( is_string( $v ) && preg_match( '/<[A-Z_ÁÉÍÓÚÑ0-9]+>/u', $v ) ) {
		WP_CLI::error( "Rellena \$CFG['$k'] (sigue con un marcador): $v" );
	}
} );

$revisar = 0;
$set     = static function ( $key, $value ) use ( &$revisar ) {
	$ok  = WPSEO_Options::set( $key, $value );
	$now = WPSEO_Options::get( $key );
	if ( ! $ok && $now !== $value && ! ( is_scalar( $value ) && (string) $now === (string) $value ) ) {
		$revisar++;
		WP_CLI::log( str_pad( $key, 44 ) . 'REVISAR → ' . wp_json_encode( $now, JSON_UNESCAPED_UNICODE ) );
	}
};

/* ---------- identidad ---------- */
update_option( 'blogname', $CFG['blogname'] );
$set( 'website_name', $CFG['website_name'] );
$set( 'alternate_website_name', $CFG['alternate_website_name'] );
$set( 'company_or_person', 'company' );
$set( 'company_name', $CFG['company_name'] );
$set( 'company_alternate_name', $CFG['company_alternate_name'] );
if ( $CFG['logo_id'] ) {
	update_post_meta( $CFG['logo_id'], '_wp_attachment_image_alt', $CFG['company_name'] );
	$set( 'company_logo', wp_get_attachment_url( $CFG['logo_id'] ) ); // primero la URL…
	$set( 'company_logo_id', $CFG['logo_id'] );                        // …después el id
} else {
	WP_CLI::warning( 'Sin logo_id: Organization saldrá sin logo.' );
}
$set( 'org-description', $CFG['org_description'] );
if ( '' !== $CFG['org_founding_date'] ) {
	$set( 'org-founding-date', $CFG['org_founding_date'] );
}

/* ---------- títulos y tipos de contenido ---------- */
$set( 'separator', $CFG['separator'] );
$set( 'title-page', $CFG['title_page'] );
$set( 'social-title-page', '' );
foreach ( $CFG['cpts'] as $cpt => $c ) {
	$set( "title-$cpt", $c['title'] );
	$set( "metadesc-$cpt", $c['metadesc'] );
	$set( "social-title-$cpt", '' );
	$set( "social-description-$cpt", $c['social_description'] ?? $c['metadesc'] );
	$set( "schema-page-type-$cpt", $c['page_type'] ?? 'WebPage' );
	$set( "schema-article-type-$cpt", $c['article_type'] ?? 'None' );
	$set( "noindex-$cpt", false );
	$set( "display-metabox-pt-$cpt", true );
}
foreach ( $CFG['tax_auxiliares'] as $tax ) {
	$set( "noindex-tax-$tax", true );
	$set( "display-metabox-tax-$tax", false );
}
if ( $CFG['sin_blog'] ) {
	$set( 'noindex-tax-category', true );
	$set( 'noindex-tax-post_tag', true );
}
$set( 'disable-post_format', true );
/* tipos públicos que no son contenido del sitio (e-floating-buttons, elementor_library…) */
$indexables = array_merge( [ 'page', 'post', 'attachment' ], array_keys( $CFG['cpts'] ) );
foreach ( get_post_types( [ 'public' => true ] ) as $pt ) {
	if ( in_array( $pt, $indexables, true ) ) {
		continue;
	}
	$set( "noindex-$pt", true );
	$set( "display-metabox-pt-$pt", false );
	WP_CLI::log( "tipo no indexable: $pt" );
}
$set( 'display-metabox-pt-attachment', false );
$set( 'disable-attachment', true );
$set( 'disable-author', true );
$set( 'noindex-author-wpseo', true );
$set( 'noindex-author-noposts-wpseo', true );
$set( 'disable-date', true );
$set( 'noindex-archive-wpseo', true );
$set( 'breadcrumbs-enable', true );
$set( 'breadcrumbs-home', $CFG['breadcrumbs_home'] );
$set( 'breadcrumbs-display-blog-page', false );

/* ---------- redes ---------- */
$set( 'opengraph', true );
$set( 'twitter', true );
$set( 'twitter_card_type', $CFG['twitter_card_type'] );
if ( $CFG['og_id'] ) {
	$set( 'og_default_image', wp_get_attachment_url( $CFG['og_id'] ) );
	$set( 'og_default_image_id', $CFG['og_id'] );
}

/* ---------- limpieza del <head> y del rastreo ---------- */
foreach ( [ 'remove_shortlinks', 'remove_rest_api_links', 'remove_rsd_wlw_links', 'remove_oembed_links', 'remove_generator', 'remove_pingback_header', 'remove_powered_by_header', 'remove_emoji_scripts',
	'remove_feed_global', 'remove_feed_global_comments', 'remove_feed_post_comments', 'remove_feed_authors', 'remove_feed_categories', 'remove_feed_tags', 'remove_feed_custom_taxonomies', 'remove_feed_post_types', 'remove_feed_search', 'remove_atom_rdf_feeds',
	'search_cleanup', 'search_cleanup_emoji', 'search_cleanup_patterns', 'deny_search_crawling', 'enable_xml_sitemap' ] as $k ) {
	$set( $k, true );
}
/* clean_permalinks=false: con true Yoast redirige los parámetros desconocidos y rompe los filtros
   de Elementor (?e-filter-…), los enlaces ?cat= y la vista previa (?elementor-preview). */
foreach ( [ 'enable_index_now', 'tracking', 'enable_enhanced_slack_sharing', 'semrush_integration_active', 'wincher_integration_active', 'enable_llms_txt',
	'clean_campaign_tracking_urls', 'clean_permalinks', 'deny_wp_json_crawling', 'deny_gptbot', 'deny_ccbot', 'deny_google_extended', 'first_time_install' ] as $k ) {
	$set( $k, false );
}

/* ---------- meta por página (JSON de datos) ---------- */
$archivo = __DIR__ . '/' . ( getenv( 'YOAST_DATA' ) ?: 'datos-es.json' );
if ( ! is_readable( $archivo ) ) {
	WP_CLI::error( "No encuentro $archivo" );
}
$datos = json_decode( file_get_contents( $archivo ), true );
if ( ! is_array( $datos ) ) {
	WP_CLI::error( "JSON inválido: $archivo" );
}
/* la meta de Yoast siempre como string (un int se guarda vacío) y con wp_slash */
$m = static fn( $id, $k, $v ) => update_post_meta( $id, '_yoast_wpseo_' . $k, wp_slash( (string) $v ) );
foreach ( $datos as $id => $e ) {
	if ( ! ctype_digit( (string) $id ) || ! get_post( (int) $id ) ) {
		WP_CLI::warning( "no existe el post $id" );
		continue;
	}
	$id = (int) $id;
	if ( mb_strlen( $e['metadesc'] ?? '' ) > 156 ) {
		WP_CLI::warning( "$id: metadesc de " . mb_strlen( $e['metadesc'] ) . ' caracteres (> 156)' );
	}
	$m( $id, 'title', $e['title'] ?? '' );
	$m( $id, 'metadesc', $e['metadesc'] ?? '' );
	$m( $id, 'focuskw', $e['focuskw'] ?? '' );
	$m( $id, 'opengraph-title', $e['og_title'] ?? '' );
	$m( $id, 'opengraph-description', $e['og_desc'] ?? '' );
	if ( ! empty( $e['related'] ) ) {
		$m( $id, 'focuskeywords', wp_json_encode( array_map( static fn( $kw ) => [ 'keyword' => $kw, 'score' => 0 ], $e['related'] ), JSON_UNESCAPED_UNICODE ) );
	}
	if ( ! empty( $e['synonyms'] ) ) {
		$m( $id, 'keywordsynonyms', wp_json_encode( $e['synonyms'], JSON_UNESCAPED_UNICODE ) );
	}
	if ( ! empty( $e['og_image'] ) ) {
		$m( $id, 'opengraph-image', wp_get_attachment_url( (int) $e['og_image'] ) );
		$m( $id, 'opengraph-image-id', (int) $e['og_image'] );
	}
	if ( ! empty( $e['schema'] ) ) {
		$m( $id, 'schema_page_type', $e['schema'] ); // CollectionPage, AboutPage, ContactPage…
	}
	if ( ! empty( $e['cornerstone'] ) ) {
		$m( $id, 'is_cornerstone', '1' );
	}
	WP_CLI::log( sprintf( '%-5d %s', $id, get_the_title( $id ) ) );
}
$revisar ? WP_CLI::warning( "$revisar opciones a revisar" ) : WP_CLI::success( 'Yoast configurado. Ahora: wp yoast index --reindex --skip-confirmation y purga.' );
