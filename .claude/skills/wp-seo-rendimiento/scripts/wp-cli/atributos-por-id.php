<?php
/**
 * atributos-por-id.php — cambia elementos concretos de documentos Elementor buscándolos por su
 * ID de elemento (nunca por posición):
 *   'attrs'     => atributos HTML de Avanzado › Atributos (añade o reemplaza por nombre)
 *   'tag'       => etiqueta de un widget V4 (settings.tag), p. ej. h4 → h2 conservando clases
 *   'menu_name' => nombre del widget nav-menu (V3), que Pro imprime como aria-label del <nav>
 *
 * Para qué, en la sesión de SEO/rendimiento:
 *   - fetchpriority="high" en la imagen LCP y loading="lazy" en tarjetas de plantillas del Theme
 *     Builder (single, loop item): no pasan por the_content, así que los filtros de contenido no
 *     les llegan (también cubre las tarjetas que devuelve el refresco REST de un filtro);
 *   - sizes explícito en imágenes eager (logos): sizes=auto solo lo respeta el navegador con lazy;
 *   - heading-order (títulos de columna del pie en h4 tras un h2) y <nav> con nombre único por idioma.
 *
 * Estado: generalizado de dos scripts del caso de referencia (HomeMaker); no probado fuera.
 * Uso:
 *   1. Lista antes con listar-imagenes.php (IDs de elemento, clases, atributos actuales).
 *   2. Rellena $CAMBIOS: [ <id_documento> => [ '<id_elemento>' => [ 'attrs' => [...], 'tag' => 'h2' ] ] ].
 *   3. Sube este archivo junto a e4-sync-preview.php (skill elementor4-atomico,
 *      scripts/e4-sync-preview.php) a la misma carpeta del servidor y ejecuta:
 *      wp --path=<WP_PATH> --user=1 eval-file atributos-por-id.php
 *      SOLO=<id>,<id>  acota a esos documentos.
 *      SYNC_PREVIEW=<ruta>  otra ruta para e4-sync-preview.php (por defecto, la misma carpeta que
 *      este script). Se ejecuta al final y debe imprimir «iguales: sí».
 * Idiomas: si el segundo idioma se regenera por script desde el base, cambia SOLO documentos del
 *   idioma base y pasa a quien regenera los IDs tocados y los valores equivalentes (p. ej. el
 *   menu_name traducido). Editar a mano un documento derivado se pierde en la siguiente regeneración.
 * Riesgos: save() regenera el CSS y dispara purgas. Este script no toca clases globales: la
 *   sincronización del orden «preview» del final es una red de seguridad (idempotente); la
 *   obligatoria va tras cada script de clases y tras migrar. Siempre --user=1.
 */

defined( 'ABSPATH' ) || exit;

$CAMBIOS = [
	// <id_documento> => [ '<id_elemento>' => [ 'attrs' => [ 'fetchpriority' => 'high' ] ] ],
	// <id_cabecera>  => [ '<id_logo>'     => [ 'attrs' => [ 'sizes' => '(max-width: 1024px) 126px, 135px' ] ],
	//                    '<id_nav>'       => [ 'menu_name' => '<Menú principal>' ] ],
	// <id_pie>       => [ '<id_titulo>'   => [ 'tag' => 'h2' ] ],
];

if ( ! $CAMBIOS ) {
	WP_CLI::error( 'Rellena $CAMBIOS.' );
}

/* Etiquetas dinámicas V4 desde WP-CLI: Elementor se cree en frontend, quita las etiquetas de los
   controles y el registro queda casi vacío → save() falla con «Settings validation failed.
   image: invalid_value» en documentos con la imagen destacada dinámica. */
$perf = new ReflectionProperty( \Elementor\Core\Frontend\Performance::class, 'is_frontend' );
$perf->setValue( null, false );
\Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module::fresh();
add_filter( 'elementor_pro/dynamic_tags/post_terms/taxonomy_args', function ( $a ) {
	unset( $a['object_type'] );
	return $a;
} );

$solo = getenv( 'SOLO' ) ? array_map( 'intval', explode( ',', getenv( 'SOLO' ) ) ) : null;
$kv   = fn( $k, $v ) => [ '$$type' => 'key-value', 'value' => [ 'key' => [ '$$type' => 'string', 'value' => $k ], 'value' => [ '$$type' => 'string', 'value' => (string) $v ] ] ];

foreach ( $CAMBIOS as $doc_id => $por_el ) {
	if ( $solo && ! in_array( $doc_id, $solo, true ) ) {
		continue;
	}
	$data = json_decode( get_post_meta( $doc_id, '_elementor_data', true ), true );
	if ( ! is_array( $data ) ) {
		WP_CLI::warning( "#$doc_id sin _elementor_data" );
		continue;
	}
	$hechos = [];
	$walk   = function ( &$els ) use ( &$walk, $por_el, $kv, &$hechos ) {
		foreach ( $els as &$e ) {
			if ( isset( $por_el[ $e['id'] ] ) ) {
				$c = $por_el[ $e['id'] ];
				if ( isset( $c['tag'] ) ) {
					$e['settings']['tag'] = [ '$$type' => 'string', 'value' => $c['tag'] ];
				}
				if ( isset( $c['menu_name'] ) ) {
					$e['settings']['menu_name'] = $c['menu_name'];
				}
				foreach ( $c['attrs'] ?? [] as $k => $v ) {
					/* quitar antes el atributo del mismo nombre: si no, sale repetido */
					$lista   = $e['settings']['attributes']['value'] ?? [];
					$lista   = array_values( array_filter( $lista, fn( $x ) => ( $x['value']['key']['value'] ?? '' ) !== $k ) );
					$lista[] = $kv( $k, $v );
					$e['settings']['attributes'] = [ '$$type' => 'attributes', 'value' => $lista ];
				}
				$hechos[] = $e['id'];
			}
			if ( ! empty( $e['elements'] ) ) {
				$walk( $e['elements'] );
			}
		}
		unset( $e );
	};
	$walk( $data );
	\Elementor\Plugin::$instance->documents->get( $doc_id )->save( [ 'elements' => $data ] );
	echo "#$doc_id: " . implode( ', ', $hechos ) . ' (' . count( $hechos ) . '/' . count( $por_el ) . ")\n";
}
\Elementor\Plugin::$instance->files_manager->clear_cache();

$sync = getenv( 'SYNC_PREVIEW' ) ?: __DIR__ . '/e4-sync-preview.php';
if ( is_readable( $sync ) ) {
	require $sync;
} else {
	WP_CLI::warning( "No encuentro $sync: ejecuta ya e4-sync-preview.php (skill elementor4-atomico) antes de que nadie publique desde el editor." );
}
