<?php
/**
 * e4-sondeo-esquema.php — Sonda de esquemas V4: elementos atómicos registrados, props de settings
 * de cada elemento (con los miembros de cada unión y los enum de 'tag'), propiedades de estilo
 * disponibles y la forma exacta ($$type, shape, enum) de las que pidas. SOLO LECTURA.
 *
 * PROPÓSITO (síntoma → causa)
 *   Los props fallaban al validar por el tipo: font-family no es 'string', los bordes no son
 *   'dimensions', 'tag' no admite dl ni ul, gap puede ser size o layout-direction… V4 tipa cada
 *   prop con uniones y formas propias. Regla: antes de usar un tipo nuevo, sondéalo; no adivines
 *   envoltorios. El error del parser señala la prop exacta, pero esto te ahorra el ensayo y error.
 *
 * USO
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-sondeo-esquema.php
 *   wp … eval-file e4-sondeo-esquema.php "gap,flex,box-shadow" "e-flexbox,e-image"
 *     $args[0] = propiedades de estilo a describir · $args[1] = elementos cuyos settings listar
 *   E4_RENDER=1 wp … eval-file e4-sondeo-esquema.php   # además pinta un e-heading con <strong> y
 *     <span class> para ver qué HTML sobrevive (no guarda nada)
 *
 * QUÉ EDITAR
 *   Nada (las listas por defecto están abajo).
 *
 * ESTADO
 *   Probado en HomeMaker (une footer-probe.php, schema.php, styleschema.php, unions.php,
 *   probe-props.php, probe-tags.php y probe-home.php). Generalizado, no probado fuera de
 *   HomeMaker: los argumentos y el orden de salida.
 *
 * RIESGOS
 *   Ninguno (no escribe). E4_RENDER instancia elementos en memoria.
 */

use Elementor\Plugin;
use Elementor\Modules\AtomicWidgets\Styles\Style_Schema;
use Elementor\Modules\AtomicWidgets\PropTypes\Escaped_Html_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;

$props_estilo = ! empty( $args[0] ) ? array_map( 'trim', explode( ',', $args[0] ) )
	: [ 'gap', 'flex', 'border-width', 'border-radius', 'box-shadow', 'background', 'font-family', 'aspect-ratio', 'position', 'z-index', 'opacity', 'grid-template-columns', 'transform', 'transition' ];
$elementos = ! empty( $args[1] ) ? array_map( 'trim', explode( ',', $args[1] ) )
	: [ 'e-flexbox', 'e-div-block', 'e-grid', 'e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-svg' ];

$em = Plugin::$instance->elements_manager;
$wm = Plugin::$instance->widgets_manager;
$tipo_de = fn( $t ) => $wm->get_widget_types( $t ) ?: $em->get_element_types( $t );
$corto   = fn( $obj ) => substr( strrchr( '\\' . get_class( $obj ), '\\' ), 1 );

/* descripción recursiva de un prop type: key{shape}[items]<enum> */
$desc = function ( $p, $depth = 0 ) use ( &$desc ) {
	$out     = [];
	$members = method_exists( $p, 'get_prop_types' ) ? $p->get_prop_types() : [ $p ];
	foreach ( $members as $m ) {
		$k = $m::get_key();
		if ( in_array( $k, [ 'overridable', 'dynamic' ], true ) ) { continue; }
		$line = $k;
		if ( method_exists( $m, 'get_shape' ) && $m->get_shape() && $depth < 2 ) {
			$sub = [];
			foreach ( $m->get_shape() as $sk => $sp ) { $sub[] = "$sk:" . implode( '|', $desc( $sp, $depth + 1 ) ); }
			$line .= '{' . implode( ', ', $sub ) . '}';
		}
		if ( method_exists( $m, 'get_item_prop_type' ) && $m->get_item_prop_type() && $depth < 2 ) {
			$line .= '[' . implode( '|', $desc( $m->get_item_prop_type(), $depth + 1 ) ) . ']';
		}
		$set = method_exists( $m, 'get_settings' ) ? $m->get_settings() : [];
		if ( ! empty( $set['enum'] ) ) { $line .= '<' . implode( '|', array_slice( $set['enum'], 0, 14 ) ) . '>'; }
		$out[] = $line;
	}
	return $out;
};

echo "=== ELEMENTOS ATÓMICOS REGISTRADOS ===\n";
foreach ( $em->get_element_types() as $name => $el ) {
	if ( str_starts_with( $name, 'e-' ) ) { echo "  $name (" . $corto( $el ) . ")\n"; }
}
foreach ( $wm->get_widget_types() as $name => $el ) {
	if ( str_starts_with( $name, 'e-' ) ) { echo "  $name (widget " . $corto( $el ) . ")\n"; }
}

echo "\n=== SETTINGS POR ELEMENTO ===\n";
foreach ( $elementos as $t ) {
	$el = $tipo_de( $t );
	if ( ! $el || ! method_exists( $el, 'get_props_schema' ) ) { echo "$t: NO REGISTRADO o sin esquema\n"; continue; }
	echo "--- $t\n";
	foreach ( $el::get_props_schema() as $key => $prop ) {
		echo '   ' . str_pad( $key, 22 ) . implode( ' | ', $desc( $prop ) ) . "\n";
	}
}

echo "\n=== PROPIEDADES DE ESTILO ===\n";
$s    = Style_Schema::get();
$keys = array_keys( $s );
sort( $keys );
echo 'disponibles: ' . count( $keys ) . "\n  " . implode( ' ', $keys ) . "\n\n";
foreach ( $props_estilo as $k ) {
	if ( ! isset( $s[ $k ] ) ) { echo str_pad( $k, 22 ) . "NO EXISTE\n"; continue; }
	echo str_pad( $k, 22 ) . implode( '  ||  ', $desc( $s[ $k ] ) ) . "\n";
}

if ( getenv( 'E4_RENDER' ) ) {
	echo "\n=== RENDER DE PRUEBA (nada se guarda) ===\n";
	$render = function ( array $data ) use ( $em ) {
		$el = $em->create_element_instance( $data );
		if ( ! $el ) { return 'NO SE PUDO INSTANCIAR'; }
		ob_start();
		$el->print_element();
		return trim( preg_replace( '/\s+/', ' ', ob_get_clean() ) );
	};
	echo $render( [ 'id' => 'prueba1', 'elType' => 'widget', 'widgetType' => 'e-heading', 'settings' => [
		'tag'   => String_Prop_Type::generate( 'h2' ),
		'title' => Escaped_Html_Prop_Type::generate( 'Texto con <strong>negrita</strong> y <span class="pfx-acento">span con clase</span>' ),
	] ] ) . "\n";
	echo "(en el caso de referencia <strong> sobrevivió y el <span> perdió la clase)\n";
}
