<?php
/**
 * <PREFIJO> — SEO: solo lo que Yoast SEO no puede expresar desde su panel.
 *
 * Títulos, descripciones, imágenes para redes, Organization y sitemap son ajustes de Yoast (los
 * edita el cliente en SEO › Ajustes y en la pestaña «Yoast SEO» del editor de Elementor). Aquí
 * van filtros de salida independientes entre sí; apaga los que no necesites en CONFIGURACIÓN.
 * Plantilla del módulo inc/seo.php del caso de referencia (HomeMaker).
 * Estado: generalizado, NO probado fuera de HomeMaker. El original sí está probado (Yoast 27.7 +
 *   Yoast Premium, Polylang Pro 3.8.6).
 *
 * Instalación: copia a wp-content/themes/<child>/inc/seo.php, sustituye el prefijo
 *   (sed -i '' 's/pfx_/<PREFIJO>_/g; s/PFX_/<PREFIJO_MAYÚSCULAS>_/g; s/Pfx_/<Prefijo>_/g' seo.php),
 *   rellena CONFIGURACIÓN y añade require_once __DIR__ . '/inc/seo.php'; en functions.php.
 * Verificar: checks-html.sh (hreflang, og:locale, tipos del JSON-LD) y la prueba de resultados
 *   enriquecidos de Google para las piezas propias (funciona aunque el sitio tenga noindex).
 * Riesgos: usa la API de Yoast (filtros wpseo_*, Abstract_Schema_Piece). La descripción de
 *   Organization del segundo idioma vive en código: si el cliente la cambia en Yoast, hay que
 *   cambiarla también aquí (mejor que viniera de una opción traducible; pendiente en el caso).
 */

defined( 'ABSPATH' ) || exit;

/* ================= CONFIGURACIÓN ================= */

/* Idioma cuyo hreflang se repite como x-default. Polylang no imprime x-default cuando el idioma
   por defecto va sin prefijo en la URL. '' = apagado. */
const PFX_XDEFAULT = ''; // '<IDIOMA_BASE>', p. ej. 'es'
/* Sin buscador en el sitio → sin SearchAction en el JSON-LD de WebSite. */
const PFX_SIN_BUSCADOR = true;
/* org-description de Yoast no es traducible con Polylang: texto por idioma distinto del base. */
const PFX_ORG_DESCRIPCION = [
	// '<IDIOMA_2>' => '<descripción de la organización en el segundo idioma>',
];
/* og:locale que Yoast emite → el que Polylang anuncia como alternativo (Polylang mapea es_PR a
   es_LA en Facebook; Yoast valida contra su lista y emitía es_ES). */
const PFX_OG_LOCALE = [
	// 'es_ES' => 'es_LA',
];
/* Migas: CPT → ruta de la página que hace de archivo (se inserta como segundo paso). */
const PFX_MIGAS = [
	// '<cpt>' => '<ruta-de-la-pagina-listado>',
];
/* Piezas schema propias: CPT → [ archivo en inc/, clase ]. La clase extiende
   Abstract_Schema_Piece (esqueleto en references/seo-yoast-polylang.md). */
const PFX_SCHEMA_PIEZAS = [
	// '<cpt>' => [ 'schema-<cpt>.php', 'Pfx_Schema_<Tipo>' ],
];

/* ================= piezas schema propias ================= */
/* require_once DENTRO del filtro: este filtro solo corre con Yoast activo, así que la clase que
   extiende Abstract_Schema_Piece nunca se carga sin Yoast (no rompe si lo desactivan). */
if ( PFX_SCHEMA_PIEZAS ) {
	add_filter( 'wpseo_schema_graph_pieces', function ( $pieces, $context ) {
		$sub = $context->indexable->object_sub_type ?? '';
		if ( isset( PFX_SCHEMA_PIEZAS[ $sub ] ) ) {
			[ $archivo, $clase ] = PFX_SCHEMA_PIEZAS[ $sub ];
			require_once __DIR__ . '/' . $archivo;
			$pieces[] = new $clase();
		}
		return $pieces;
	}, 11, 2 );
}

/* ================= migas: página archivo del CPT, en el idioma del post ================= */
if ( PFX_MIGAS ) {
	add_filter( 'wpseo_breadcrumb_links', function ( $crumbs ) {
		foreach ( PFX_MIGAS as $cpt => $ruta ) {
			if ( ! is_singular( $cpt ) || count( $crumbs ) < 2 ) {
				continue;
			}
			$pagina = get_page_by_path( $ruta );
			if ( ! $pagina ) {
				continue;
			}
			$id = function_exists( 'pll_get_post' ) ? ( pll_get_post( $pagina->ID ) ?: $pagina->ID ) : $pagina->ID;
			array_splice( $crumbs, 1, 0, [ [ 'url' => get_permalink( $id ), 'text' => get_the_title( $id ), 'id' => $id ] ] );
		}
		return $crumbs;
	} );
}

if ( PFX_SIN_BUSCADOR ) {
	add_filter( 'disable_wpseo_json_ld_search', '__return_true' );
}

/* ================= hreflang x-default ================= */
if ( PFX_XDEFAULT ) {
	add_filter( 'pll_rel_hreflang_attributes', function ( $hreflangs ) {
		if ( isset( $hreflangs[ PFX_XDEFAULT ] ) && ! isset( $hreflangs['x-default'] ) ) {
			$hreflangs['x-default'] = $hreflangs[ PFX_XDEFAULT ];
		}
		return $hreflangs;
	} );
}

/* ================= Organization en el segundo idioma =================
   Prioridad 99: Yoast Premium añade la descripción DESPUÉS de la prioridad 10. */
if ( PFX_ORG_DESCRIPCION ) {
	add_filter( 'wpseo_schema_organization', function ( $data ) {
		$lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
		if ( isset( PFX_ORG_DESCRIPCION[ $lang ] ) && ! empty( $data['description'] ) ) {
			$data['description'] = PFX_ORG_DESCRIPCION[ $lang ];
		}
		return $data;
	}, 99 );
}

/* ================= WebPage.name sin entidades =================
   Con el separador sc-mdash el «—» llegaba al JSON-LD como la entidad literal &#8212;. */
add_filter( 'wpseo_schema_webpage', function ( $data ) {
	if ( isset( $data['name'] ) && is_string( $data['name'] ) ) {
		$data['name'] = html_entity_decode( $data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
	return $data;
} );

/* ================= og:locale coherente con Polylang ================= */
if ( PFX_OG_LOCALE ) {
	add_filter( 'wpseo_og_locale', function ( $locale ) {
		return PFX_OG_LOCALE[ $locale ] ?? $locale;
	} );
}

/* ================= imagen principal del schema = imagen para redes =================
   Las páginas hechas con Elementor no tienen imagen destacada y Yoast tomaba la primera del
   contenido (un logo, un recorte). Si la página tiene imagen OG elegida en Yoast, manda esa. */
add_filter( 'wpseo_schema_main_image_id', function ( $image_id ) {
	if ( ! is_page() ) {
		return $image_id;
	}
	$og = (int) get_post_meta( get_queried_object_id(), '_yoast_wpseo_opengraph-image-id', true );
	return $og > 0 ? $og : $image_id;
} );
