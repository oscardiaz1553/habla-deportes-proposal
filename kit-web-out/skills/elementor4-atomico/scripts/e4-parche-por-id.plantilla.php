<?php
/**
 * e4-parche-por-id.plantilla.php — Cambia settings concretos de elementos concretos (por ID de
 * elemento, nunca por posición) en documentos ya construidos, y re-guarda cada documento:
 * atributos (poner o quitar), etiqueta HTML y, en widgets V3, claves planas.
 *
 * PROPÓSITO
 *   Arreglos quirúrgicos cuando la página ya existe y quizá el cliente ya la editó: no se reescribe
 *   el árbol (eso es el <pagina>-build.php), solo se tocan los elementos listados. Casos reales:
 *   sizes/loading/fetchpriority en imágenes de plantillas, h4 → h2 por orden de encabezados,
 *   quitar un atributo duplicado, colapso del nav-menu.
 *
 * USO
 *   cp e4-parche-por-id.plantilla.php parche-<motivo>.php       # edita $CAMBIOS
 *   scp parche-<motivo>.php e4-dynamic-tags-preambulo.php e4-sync-preview.php <SSH_ALIAS>:tmp-<PREFIJO>/
 *   E4_SECO=1 wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/parche-<motivo>.php   # enseña qué tocaría
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/parche-<motivo>.php
 *   E4_SOLO=<ID>,<ID> … limita la ejecución a esos documentos.
 *   Salida: «#<doc>: <ids> (hechos/total)». Un total incompleto = id mal copiado: revisa.
 *   Los ids de elemento salen del HTML (data-id="…") o de e4-build (E4_ARBOL=1).
 *
 * QUÉ EDITAR
 *   $CAMBIOS = [ <doc_id> => [ '<id elemento>' => [ …operaciones… ] ] ]. Operaciones:
 *     'attrs'        => [ 'loading' => 'lazy' ]   V4: pone o reemplaza atributos (prop «attributes»)
 *     'quitar_attrs' => [ 'fetchpriority' ]       V4: quita atributos por nombre
 *     'tag'          => 'h2'                      V4: etiqueta (respeta el enum del elemento)
 *     'v3'           => [ 'dropdown' => 'tablet_extra' ]   widget clásico: claves planas
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (plantillas-perf.php: attrs y tag por id, E4_SOLO era HM_SOLO;
 *   quitar-fetchpriority.php: quitar un atributo; bp-tabletextra.php: clave plana de un V3). Generalizado, no probado
 *   fuera de HomeMaker: 'quitar_attrs' y 'v3' dentro de la misma plantilla, modo seco y la
 *   comprobación del retorno de save().
 *
 * RIESGOS
 *   - Carga siempre el preámbulo de etiquetas dinámicas: sin él, un documento con $$type 'dynamic'
 *     no se puede re-guardar desde CLI («Settings validation failed»).
 *   - Guardar un documento dispara las purgas de caché de Elementor y del hosting.
 *   - Si el id no existe en el documento, no se hace nada con él (se ve en hechos/total).
 *   - fetchpriority/loading a mano solo en plantillas del Theme Builder (singles, loop items), que
 *     no pasan por the_content; en páginas saldría duplicado con el que ya pone Elementor/WordPress.
 */

require __DIR__ . '/e4-dynamic-tags-preambulo.php';

$CAMBIOS = [
	/* 0 => [ 'abc1234' => [ 'attrs' => [ 'sizes' => '(max-width: 1024px) 126px, 135px' ] ], 'def5678' => [ 'tag' => 'h2' ] ], */
];

$SECO = (bool) getenv( 'E4_SECO' );
$solo = getenv( 'E4_SOLO' ) ? array_map( 'intval', explode( ',', getenv( 'E4_SOLO' ) ) ) : null;
$kv   = fn( $k, $v ) => [ '$$type' => 'key-value', 'value' => [ 'key' => [ '$$type' => 'string', 'value' => (string) $k ], 'value' => [ '$$type' => 'string', 'value' => (string) $v ] ] ];

foreach ( $CAMBIOS as $doc_id => $por_el ) {
	if ( $solo && ! in_array( (int) $doc_id, $solo, true ) ) { continue; }
	$data = json_decode( (string) get_post_meta( $doc_id, '_elementor_data', true ), true );
	if ( ! is_array( $data ) ) { WP_CLI::warning( "#$doc_id sin _elementor_data legible" ); continue; }
	$hechos = [];
	/* por valor: nunca &$el['settings']['x'] sobre una clave que puede no existir (crea null y
	   Elementor revienta al guardar) */
	$walk = function ( array $els ) use ( &$walk, $por_el, $kv, &$hechos ) {
		foreach ( $els as $i => $e ) {
			$id = $e['id'] ?? '';
			if ( isset( $por_el[ $id ] ) ) {
				$c = $por_el[ $id ];
				if ( isset( $c['tag'] ) ) {
					$e['settings']['tag'] = [ '$$type' => 'string', 'value' => $c['tag'] ];
				}
				if ( ! empty( $c['attrs'] ) || ! empty( $c['quitar_attrs'] ) ) {
					$lista  = $e['settings']['attributes']['value'] ?? [];
					$fuera  = array_merge( array_keys( $c['attrs'] ?? [] ), $c['quitar_attrs'] ?? [] );
					$lista  = array_values( array_filter( $lista, fn( $x ) => ! in_array( $x['value']['key']['value'] ?? '', $fuera, true ) ) );
					foreach ( $c['attrs'] ?? [] as $k => $v ) { $lista[] = $kv( $k, $v ); }
					if ( $lista ) {
						$e['settings']['attributes'] = [ '$$type' => 'attributes', 'value' => $lista ];
					} else {
						unset( $e['settings']['attributes'] );
					}
				}
				foreach ( $c['v3'] ?? [] as $k => $v ) { $e['settings'][ $k ] = $v; }
				$hechos[] = $id;
			}
			if ( ! empty( $e['elements'] ) ) { $e['elements'] = $walk( $e['elements'] ); }
			$els[ $i ] = $e;
		}
		return $els;
	};
	$data = $walk( $data );
	echo "#$doc_id: " . implode( ', ', $hechos ) . ' (' . count( $hechos ) . '/' . count( $por_el ) . ')' . ( $SECO ? ' [SECO: sin guardar]' : '' ) . "\n";
	if ( $SECO || ! $hechos ) { continue; }
	$doc = \Elementor\Plugin::$instance->documents->get( (int) $doc_id, false );
	if ( ! $doc || false === $doc->save( [ 'elements' => $data ] ) ) { WP_CLI::error( "save() falló en #$doc_id (¿--user=1?)" ); }
}

if ( ! $SECO ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
	require __DIR__ . '/e4-sync-preview.php'; // red de seguridad (idempotente): este script no toca clases
}
