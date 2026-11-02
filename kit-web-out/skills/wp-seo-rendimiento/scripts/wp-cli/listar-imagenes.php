<?php
/**
 * listar-imagenes.php — lista de SOLO LECTURA de las imágenes, encabezados y menús de documentos
 * Elementor: ID de elemento, tipo, etiqueta, clases, atributos de Avanzado › Atributos, ID del
 * adjunto y, en los nav-menu, su menu_name. Es el gemelo de lectura de atributos-por-id.php:
 * úsalo antes para elegir los IDs y después para comprobar el cambio.
 *
 * Estado: generalizado del caso de referencia (HomeMaker); no probado fuera de HomeMaker.
 * Uso:    DOCS=<id_cabecera>,<id_pie>,<id_single> wp --path=<WP_PATH> --user=1 eval-file listar-imagenes.php
 * Riesgos: ninguno, no escribe.
 */

defined( 'ABSPATH' ) || exit;

$docs = getenv( 'DOCS' ) ? array_map( 'intval', explode( ',', getenv( 'DOCS' ) ) ) : [];
if ( ! $docs ) {
	WP_CLI::error( 'Indica DOCS=<id>,<id>' );
}
foreach ( $docs as $id ) {
	$data = json_decode( get_post_meta( $id, '_elementor_data', true ), true );
	if ( ! is_array( $data ) ) {
		WP_CLI::warning( "#$id sin _elementor_data" );
		continue;
	}
	$walk = function ( $els ) use ( &$walk, $id ) {
		foreach ( $els as $e ) {
			$t = $e['widgetType'] ?? $e['elType'];
			if ( in_array( $t, [ 'e-image', 'e-heading', 'image', 'theme-post-featured-image' ], true ) ) {
				$attrs = [];
				foreach ( $e['settings']['attributes']['value'] ?? [] as $kv ) {
					$attrs[] = $kv['value']['key']['value'] . '=' . $kv['value']['value']['value'];
				}
				printf( "#%d %s %s tag=%s classes=%s attrs=[%s] img=%s title=%s\n", $id, $e['id'], $t, $e['settings']['tag']['value'] ?? '', implode( ',', $e['settings']['classes']['value'] ?? [] ), implode( '; ', $attrs ),
					wp_json_encode( $e['settings']['image']['value']['src']['value']['id'] ?? ( $e['settings']['image']['value']['src'] ?? null ) ), mb_substr( wp_strip_all_tags( $e['settings']['title']['value'] ?? '' ), 0, 30 ) );
			} elseif ( 'nav-menu' === $t ) {
				printf( "#%d %s nav-menu menu=%s menu_name=%s\n", $id, $e['id'], wp_json_encode( $e['settings']['menu'] ?? null ), wp_json_encode( $e['settings']['menu_name'] ?? null, JSON_UNESCAPED_UNICODE ) );
			}
			$walk( $e['elements'] ?? [] );
		}
	};
	$walk( $data );
}
