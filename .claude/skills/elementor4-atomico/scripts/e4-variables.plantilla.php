<?php
/**
 * e4-variables.plantilla.php — Crea las variables V4 del sistema de diseño (colores, fuentes y
 * medidas) en el kit activo. Idempotente: las etiquetas que ya existen no se tocan.
 *
 * PROPÓSITO (síntoma → causa)
 *   En Elementor 4 (e_atomic_elements activo) el sistema de diseño no son los system_colors del
 *   kit sino variables en el meta _elementor_global_variables del kit, y se crean con
 *   Variables_Service. Las clases globales guardan el ID de la variable (no la etiqueta) y el CSS
 *   las emite en :root como --<etiqueta>, así que el CSS del tema hijo puede usar
 *   var(--<etiqueta>, <hex de respaldo>) y seguir al cliente cuando cambie el color en Elementor.
 *
 * USO
 *   cp e4-variables.plantilla.php variables.php     # edita $VARS con la paleta y tipografías del prototipo
 *   php -l variables.php && scp variables.php <SSH_ALIAS>:tmp-<PREFIJO>/
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/variables.php
 *   Comprueba en un proceso nuevo: e4-sondeo-kit.php (sección VARIABLES V4) o e4-dump-clases.php.
 *
 * QUÉ EDITAR
 *   $VARS: [ tipo, etiqueta, valor ]. Tipos: global-color-variable, global-font-variable,
 *   global-size-variable. Etiquetas en kebab-case: NO admiten espacios y deben ser únicas.
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (variables-v4.php creó 21: 15 colores, 2 fuentes, 4 medidas).
 *   Generalizado, no probado fuera de HomeMaker: el salto de etiquetas existentes y el aviso de
 *   valor distinto (la plantilla original intentaba crearlas todas y registraba el error).
 *
 * RIESGOS
 *   - Si el cliente RENOMBRA una etiqueta, el CSS del hijo que use var(--<etiqueta>) cae al
 *     respaldo: documéntalo en la entrega.
 *   - No cambia el valor de una variable existente (solo avisa): cámbialo en el editor.
 */

use Elementor\Plugin;
use Elementor\Modules\Variables\Storage\Variables_Repository;
use Elementor\Modules\Variables\Services\Variables_Service;
use Elementor\Modules\Variables\Services\Batch_Operations\Batch_Processor;

if ( ! class_exists( Variables_Service::class ) ) { WP_CLI::error( 'Variables_Service no existe: ¿Elementor 4 con el editor atómico activo? Ejecuta e4-sondeo-kit.php.' ); }

$COLOR = 'global-color-variable';
$FONT  = 'global-font-variable';
$SIZE  = 'global-size-variable';

/* ejemplo neutro: sustituye por los valores medidos en el prototipo */
$VARS = [
	[ $COLOR, 'primario',        '#1F6F4A' ],
	[ $COLOR, 'primario-oscuro', '#124A30' ],
	[ $COLOR, 'acento',          '#E4572E' ],
	[ $COLOR, 'tinta',           '#17211A' ],
	[ $COLOR, 'tinta-suave',     '#45513F' ],
	[ $COLOR, 'papel',           '#FFFFFF' ],
	[ $COLOR, 'papel-alterno',   '#F6F6F4' ],
	[ $COLOR, 'linea',           '#E6E4DF' ],
	[ $FONT,  'fuente-titulos',  'Anton' ],     // las del caso de referencia; pon las del prototipo
	[ $FONT,  'fuente-texto',    'Archivo' ],
	[ $SIZE,  'ancho-contenido', '1320px' ],
	[ $SIZE,  'seccion-y',       '96px' ],
];

$svc = new Variables_Service( new Variables_Repository( Plugin::$instance->kits_manager->get_active_kit() ), new Batch_Processor() );
$lista = $svc->get_variables_list();
$data  = $lista['data'] ?? $lista;
$hay   = [];
foreach ( $data as $id => $v ) {
	if ( is_array( $v ) && isset( $v['label'] ) ) { $hay[ $v['label'] ] = $v; }
}

$ok = 0; $saltadas = 0; $fail = 0;
foreach ( $VARS as [ $type, $label, $value ] ) {
	if ( preg_match( '/\s/', $label ) ) { printf( "  ERROR  %-18s la etiqueta no puede llevar espacios\n", $label ); $fail++; continue; }
	if ( isset( $hay[ $label ] ) ) {
		$actual = $hay[ $label ]['value'] ?? null;
		printf( "  existe %-18s %s%s\n", $label, is_scalar( $actual ) ? $actual : wp_json_encode( $actual ),
			( is_scalar( $actual ) && (string) $actual !== (string) $value ) ? "   ← AVISO: el prototipo dice $value" : '' );
		$saltadas++;
		continue;
	}
	try {
		$svc->create( [ 'type' => $type, 'label' => $label, 'value' => $value ] );
		printf( "  OK     %-18s %s\n", $label, $value );
		$ok++;
	} catch ( \Throwable $e ) {
		printf( "  ERROR  %-18s %s -> %s\n", $label, $value, $e->getMessage() );
		$fail++;
	}
}
echo "\ncreadas: $ok · ya existían: $saltadas · fallidas: $fail\n";
Plugin::$instance->files_manager->clear_cache();
