<?php
/**
 * e4-dump-clases.php — Vuelca en formato legible las clases globales V4 (todas o las pedidas),
 * con su posición en el orden, sus variantes por breakpoint/estado y las variables del kit.
 * SOLO LECTURA.
 *
 * PROPÓSITO
 *   Ver qué hay de verdad antes de tocar una clase: qué propiedades define cada variante, qué
 *   clase gana a cuál (la PRIMERA del orden gana) y el id de cada variable de color (las clases
 *   guardan el id, no la etiqueta). Sirve también como respaldo en texto antes de un cambio grande.
 *
 * USO
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-dump-clases.php                    # todas, en orden
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-dump-clases.php "pfx-btn,pfx-btn--sm" # solo esas
 *   E4_CONTEXTO=preview wp … eval-file e4-dump-clases.php       # lee el contexto que ve el editor
 *   E4_SALIDA=~/tmp-<PREFIJO>/clases-globales.txt wp … eval-file e4-dump-clases.php   # a archivo
 *   Formato de cada variante:
 *     [12] pfx-btn @tablet:hover — padding: {block-start=14px block-end=14px …}; color: var:<id>
 *
 * QUÉ EDITAR
 *   Nada.
 *
 * ESTADO
 *   Probado en HomeMaker (une dump-classes.php, list-classes.php y dump-clases.php del caso).
 *   Generalizado, no probado fuera de HomeMaker: E4_CONTEXTO y la posición en el orden en cada línea.
 *
 * RIESGOS
 *   Ninguno (no escribe en la BD). Con E4_SALIDA escribe un archivo en la ruta indicada.
 */

use Elementor\Plugin;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;
use Elementor\Modules\Variables\Storage\Variables_Repository;
use Elementor\Modules\Variables\Services\Variables_Service;
use Elementor\Modules\Variables\Services\Batch_Operations\Batch_Processor;

$ctx   = 'preview' === getenv( 'E4_CONTEXTO' ) ? 'preview' : 'frontend';
$repo  = Global_Classes_Repository::make();
if ( 'preview' === $ctx ) { $repo = $repo->set_preview( true ); }
$all   = $repo->all( true );
$items = $all->get_items()->all();
$order = $all->get_order()->all();
$pos   = array_flip( $order );
$want  = ! empty( $args[0] ) ? array_filter( array_map( 'trim', explode( ',', $args[0] ) ) ) : $order;

/* valor legible: size → «16px», variable → «var:<id>», formas → {clave=valor …} */
$flat = function ( $v ) use ( &$flat ) {
	if ( ! is_array( $v ) ) { return is_bool( $v ) ? ( $v ? 'true' : 'false' ) : (string) $v; }
	if ( isset( $v['$$type'] ) ) {
		$val = $v['value'] ?? null;
		if ( 'size' === $v['$$type'] && is_array( $val ) ) {
			return ( $val['size'] ?? '' ) . ( ( $val['unit'] ?? '' ) === 'custom' ? '' : ( $val['unit'] ?? '' ) );
		}
		if ( 'global-color-variable' === $v['$$type'] ) { return 'var:' . $val; }
		if ( is_array( $val ) ) {
			$o = [];
			foreach ( $val as $k => $x ) { $o[] = $k . '=' . $flat( $x ); }
			return '{' . implode( ' ', $o ) . '}';
		}
		return null === $val ? 'null' : (string) $val;
	}
	$o = [];
	foreach ( $v as $k => $x ) { $o[] = $k . '=' . $flat( $x ); }
	return '[' . implode( ' ', $o ) . ']';
};

$out   = [];
$out[] = "contexto: $ctx · clases: " . count( $items ) . ' · en el orden: ' . count( $order );
foreach ( $want as $w ) {
	if ( ! isset( $items[ $w ] ) ) { $out[] = "$w: (no existe en $ctx)"; continue; }
	$n = isset( $pos[ $w ] ) ? $pos[ $w ] : '?';
	if ( empty( $items[ $w ]['variants'] ) ) { $out[] = "[$n] $w — (sin variantes)"; continue; }
	foreach ( $items[ $w ]['variants'] as $var ) {
		$p = [];
		foreach ( (array) ( $var['props'] ?? [] ) as $k => $x ) { $p[] = "$k: " . $flat( $x ); }
		$bp    = $var['meta']['breakpoint'] ?? 'desktop';
		$state = $var['meta']['state'] ?? null;
		$out[] = "[$n] $w @" . $bp . ( $state ? ':' . $state : '' ) . ' — ' . implode( '; ', $p );
	}
}

$svc  = new Variables_Service( new Variables_Repository( Plugin::$instance->kits_manager->get_active_kit() ), new Batch_Processor() );
$list = $svc->get_variables_list();
$data = $list['data'] ?? $list;
$out[] = '--- variables del kit ---';
foreach ( $data as $id => $v ) {
	if ( is_array( $v ) && isset( $v['label'] ) ) {
		$out[] = 'VAR ' . $v['label'] . ' = ' . ( is_scalar( $v['value'] ?? null ) ? $v['value'] : wp_json_encode( $v['value'] ?? null ) ) . ' (' . ( $v['type'] ?? '?' ) . ", $id)";
	}
}

$texto = implode( "\n", $out ) . "\n";
$dest  = getenv( 'E4_SALIDA' );
if ( $dest ) {
	file_put_contents( $dest, $texto );
	echo count( $want ) . " clases volcadas en $dest\n";
} else {
	echo $texto;
}
