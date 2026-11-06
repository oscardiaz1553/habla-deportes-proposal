<?php
/**
 * qa-editable.php — sonda de SOLO LECTURA: ¿podrá el cliente abrir y publicar estos documentos en el editor?
 * M12 de ../references/mediciones.md.
 *
 * Propósito: Claude no inicia sesión en el editor, así que la editabilidad se verifica por datos (y la
 *          prueba visual se le pide al usuario). Los fallos son silenciosos: props que el parser
 *          rechaza, claves que se pierden al próximo guardado, clases que el editor borrará al publicar.
 * Uso:     wp --path=<WP_PATH> eval-file ~/tmp-<PREFIJO>/qa-editable.php <ID>[,<ID>…] --user=1 > ~/tmp-<PREFIJO>/qa-editable.txt 2>&1
 *          (los IDs llegan en $args; después: grep -E "ISSUE|AVISO|RESUMEN|Escrituras|fatal|Uncaught")
 * Mira:    orden de clases frontend = preview; por documento: JSON válido, ids únicos de 7 caracteres [a-z0-9],
 *          props atómicas válidas con el parser de Elementor en modo editor, estilos locales válidos, clases
 *          referenciadas presentes en frontend y en preview, índice de clases usadas al día, Loop Grid y
 *          filtro enlazados, carga del documento como la pide el editor y render sin excepciones ni avisos.
 * Salida:  líneas ISSUE (se rompe o se pierde al guardar desde el editor), AVISO (revisar) e INFO, la
 *          lista de avisos PHP, las escrituras bloqueadas y «RESUMEN docs=N issues=N avisos=N».
 * Estado:  generalizado a partir de la sonda de la revisión de editabilidad de HomeMaker (Elementor 4.3.0,
 *          Pro 4.2.3); no probado fuera de HomeMaker. Las APIs usadas existen en el código de Elementor 4.2.1.
 * Riesgos: no llama a save(). El guardián de abajo anula y lista las escrituras por update/add/delete de
 *          metas y por update_option; no cubre add_option, $wpdb directo ni archivos (el render puede
 *          generar CSS en uploads). Nunca lo ejecutes sin el guardián.
 */
use Elementor\Plugin;
use Elementor\Modules\AtomicWidgets\Parsers\Props_Parser;
use Elementor\Modules\AtomicWidgets\Parsers\Style_Parser;
use Elementor\Modules\AtomicWidgets\Styles\Style_Schema;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;

// Guardián de escrituras: todo intento de escribir metas u opciones se anula y se apunta.
$bloqueadas = [];
foreach ( [ 'update_post_metadata', 'add_post_metadata', 'delete_post_metadata' ] as $f ) {
	add_filter( $f, function ( $check, $object_id, $meta_key ) use ( &$bloqueadas, $f ) {
		$bloqueadas[] = "$f:$object_id:$meta_key";
		return false;
	}, PHP_INT_MIN, 3 );
}
add_filter( 'pre_update_option', function ( $value, $option, $old ) use ( &$bloqueadas ) {
	if ( $value !== $old ) { $bloqueadas[] = "option:$option"; }
	return $old;
}, PHP_INT_MIN, 3 );

// WP-CLI cuenta como «frontend» para Elementor: sin esto los props dinámicos V4 no validan
// (mismo preámbulo que e4-dynamic-tags-preambulo.php de elementor4-atomico).
$rp = new ReflectionProperty( \Elementor\Core\Frontend\Performance::class, 'is_frontend' );
if ( PHP_VERSION_ID < 80100 ) { $rp->setAccessible( true ); } // desde PHP 8.1 no hace falta (en 8.5 está obsoleto)
$rp->setValue( null, false );
\Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module::fresh();
add_filter( 'elementor_pro/dynamic_tags/post_terms/taxonomy_args', function ( $a ) { unset( $a['object_type'] ); return $a; } );

$avisos_php = [];
set_error_handler( function ( $no, $str, $file, $line ) use ( &$avisos_php ) {
	$avisos_php[] = "E$no $str @ " . str_replace( ABSPATH, '', $file ) . ":$line";
	return true;
} );

$docs = array_values( array_filter( array_map( 'intval', explode( ',', implode( ',', $args ?? [] ) ) ) ) );
if ( ! $docs ) { WP_CLI::error( 'Pasa los IDs: wp eval-file qa-editable.php 12,34 --user=1' ); }
$n_issue = 0;
$n_aviso = 0;

// 1) Orden de clases globales: frontend y preview deben ser iguales (si no, el editor borrará clases al publicar).
$kit = Plugin::$instance->kits_manager->get_active_kit();
$kid = $kit->get_id();
$orden = function ( $clave ) use ( $kid ) {
	$d = get_post_meta( $kid, $clave, true );
	while ( is_string( $d ) ) { $d = json_decode( $d, true ); }
	return is_array( $d ) ? (array) ( $d['order'] ?? $d ) : [];
};
$oF = $orden( '_elementor_global_classes_order' );
$oP = $orden( '_elementor_global_classes_order_preview' );
$solo_f = array_diff( $oF, $oP );
echo "kit #$kid orden: frontend " . count( $oF ) . ' · preview ' . count( $oP ) . ' · iguales: ' . ( $oF === $oP ? 'sí' : 'NO' ) . "\n";
if ( ! $oP && $oF ) { $n_aviso++; echo "  AVISO preview sin orden guardado: Elementor 4.2+ usa el de frontend como respaldo; sincroniza igualmente (e4-sync-preview.php)\n"; }
elseif ( $solo_f ) { $n_issue++; echo '  ISSUE clases solo en frontend (el editor las borrará al publicar): ' . implode( ',', $solo_f ) . "\n"; }
if ( array_diff( $oP, $oF ) ) { echo '  INFO clases solo en preview (¿creadas en el editor?): ' . implode( ',', array_diff( $oP, $oF ) ) . "\n"; }

$itemsF = Global_Classes_Repository::make()->all( true )->get_items()->all();
$itemsP = Global_Classes_Repository::make()->set_preview( true )->all( true )->get_items()->all();
echo 'repositorio: frontend ' . count( $itemsF ) . ' · preview ' . count( $itemsP ) . "\n";

foreach ( $docs as $doc_id ) {
	$data = json_decode( (string) get_post_meta( $doc_id, '_elementor_data', true ), true );
	$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $doc_id ) : '-';
	printf( "\n== DOC #%d «%s» estado=%s tipo=%s plantilla=%s modo=%s idioma=%s json=%s\n", $doc_id, get_the_title( $doc_id ),
		get_post_status( $doc_id ), get_post_meta( $doc_id, '_elementor_template_type', true ), get_post_meta( $doc_id, '_wp_page_template', true ),
		get_post_meta( $doc_id, '_elementor_edit_mode', true ), $lang ?: '-', is_array( $data ) ? 'ok' : 'NO' );
	if ( ! is_array( $data ) ) { $n_issue++; continue; }

	$ids = []; $usadas = []; $tipos = []; $grids = []; $filtros = []; $n = 0;
	$walk = function ( $els, $ruta ) use ( &$walk, &$ids, &$usadas, &$tipos, &$grids, &$filtros, &$n, &$n_issue, &$n_aviso, $itemsF, $itemsP ) {
		foreach ( $els as $el ) {
			$n++;
			$t = $el['widgetType'] ?? $el['elType'] ?? '?';
			$tipos[ $t ] = ( $tipos[ $t ] ?? 0 ) + 1;
			$p  = $ruta . '/' . $t . '#' . ( $el['id'] ?? '?' );
			$id = (string) ( $el['id'] ?? '' );
			if ( '' === $id ) { $n_issue++; echo "  ISSUE $p: elemento sin id\n"; }
			elseif ( isset( $ids[ $id ] ) ) { $n_issue++; echo "  ISSUE $p: id duplicado (también en {$ids[$id]})\n"; }
			elseif ( ! preg_match( '/^[0-9a-z]{7}$/', $id ) ) { $n_aviso++; echo "  AVISO $p: id que no es de 7 caracteres [a-z0-9]\n"; }
			$ids[ $id ] = $p;
			$settings = (array) ( $el['settings'] ?? [] );
			if ( 'loop-grid' === $t ) { $grids[ $id ] = (int) ( $settings['template_id'] ?? 0 ); }
			if ( 'taxonomy-filter' === $t ) { $filtros[ $id ] = (string) ( $settings['selected_element'] ?? '' ); }
			$inst = Plugin::$instance->elements_manager->create_element_instance( $el );
			if ( ! $inst ) { $n_issue++; echo "  ISSUE $p: Elementor no puede instanciarlo\n"; $walk( $el['elements'] ?? [], $p ); continue; }
			if ( ! method_exists( $inst, 'get_atomic_settings' ) ) { $walk( $el['elements'] ?? [], $p ); continue; } // widget V3
			$schema = $inst::get_props_schema();
			$sobran = array_diff( array_keys( $settings ), array_keys( $schema ) );
			if ( $sobran ) { $n_issue++; echo "  ISSUE $p: claves fuera del esquema (se pierden al próximo guardado): " . implode( ',', $sobran ) . "\n"; }
			try {
				$res = Props_Parser::make( $schema )->parse( $settings );
				if ( ! $res->is_valid() ) { $n_issue++; echo "  ISSUE $p: props inválidas: " . $res->errors()->to_string() . "\n"; }
				else {
					$san = $res->unwrap(); $a = $settings; ksort( $a ); ksort( $san );
					if ( wp_json_encode( $a ) !== wp_json_encode( $san ) ) { $n_aviso++; echo "  AVISO $p: el saneado cambia el valor guardado: " . substr( wp_json_encode( $san ), 0, 300 ) . "\n"; }
				}
			} catch ( \Throwable $e ) { $n_issue++; echo "  ISSUE $p: el parser lanza: " . $e->getMessage() . "\n"; }
			$locales = (array) ( $el['styles'] ?? [] );
			foreach ( $locales as $sid => $estilo ) {
				$r = Style_Parser::make( Style_Schema::get() )->parse( $estilo );
				if ( ! $r->is_valid() ) { $n_issue++; echo "  ISSUE $p: estilo local $sid inválido\n"; }
			}
			foreach ( (array) ( $settings['classes']['value'] ?? [] ) as $c ) {
				if ( isset( $locales[ $c ] ) ) { continue; } // estilo local del propio elemento
				$usadas[ $c ] = true;
				if ( ! isset( $itemsF[ $c ] ) ) { $n_issue++; echo "  ISSUE $p: clase $c NO está en el repositorio frontend\n"; }
				if ( ! isset( $itemsP[ $c ] ) ) { $n_issue++; echo "  ISSUE $p: clase $c NO está en preview (el editor la borrará)\n"; }
			}
			try { $inst->get_atomic_settings(); } catch ( \Throwable $e ) { $n_issue++; echo "  ISSUE $p: get_atomic_settings lanza: " . $e->getMessage() . "\n"; }
			$walk( $el['elements'] ?? [], $p );
		}
	};
	$walk( $data, '' );
	echo "  elementos=$n tipos=" . wp_json_encode( $tipos ) . "\n";

	foreach ( $grids as $gid => $tpl ) {
		if ( 'publish' !== get_post_status( $tpl ) ) { $n_issue++; echo "  ISSUE loop-grid#$gid: template_id $tpl no está publicado\n"; }
	}
	foreach ( $filtros as $fid => $sel ) {
		if ( ! isset( $grids[ $sel ] ) ) { $n_issue++; echo "  ISSUE taxonomy-filter#$fid: selected_element '$sel' no es un loop-grid de este documento\n"; }
	}

	$vivas = array_keys( $usadas ); sort( $vivas );
	foreach ( [ '_elementor_used_global_class', '_elementor_used_global_class_preview' ] as $k ) {
		$idx = get_post_meta( $doc_id, $k ); sort( $idx );
		if ( $idx !== $vivas ) { $n_aviso++; echo "  AVISO índice $k desfasado: solo índice=" . implode( ',', array_diff( $idx, $vivas ) ) . ' solo datos=' . implode( ',', array_diff( $vivas, $idx ) ) . "\n"; }
	}

	// 2) El documento se carga como lo pide el editor y cada sección se pinta sin excepciones.
	$antes = count( $avisos_php );
	try {
		$doc = Plugin::$instance->documents->get( $doc_id, false );
		$doc->get_elements_data();
		$doc->get_elements_raw_data( null, true );
		$doc->get_config();
		Plugin::$instance->documents->switch_to_document( $doc );
		foreach ( $data as $sec ) {
			$inst = Plugin::$instance->elements_manager->create_element_instance( $sec );
			if ( ! $inst ) { continue; }
			ob_start();
			try { $inst->print_element(); } catch ( \Throwable $e ) { $n_issue++; echo "  ISSUE render #{$sec['id']}: " . $e->getMessage() . "\n"; }
			ob_end_clean();
		}
		Plugin::$instance->documents->restore_document();
		echo '  carga de editor y render: ok · avisos PHP nuevos: ' . ( count( $avisos_php ) - $antes ) . "\n";
	} catch ( \Throwable $e ) { $n_issue++; echo '  ISSUE la carga del editor lanza: ' . $e->getMessage() . "\n"; }
}

restore_error_handler();
echo "\nAvisos PHP: " . ( $avisos_php ? "\n    " . implode( "\n    ", array_unique( $avisos_php ) ) : 'ninguno' ) . "\n";
echo 'Escrituras bloqueadas: ' . ( $bloqueadas ? implode( ', ', array_unique( $bloqueadas ) ) : 'ninguna' ) . "\n";
echo "RESUMEN docs=" . count( $docs ) . " issues=$n_issue avisos=$n_aviso\n";
