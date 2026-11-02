<?php
/**
 * e4-kit-breakpoints.plantilla.php — Activa, cambia o quita breakpoints del kit de Elementor
 * escribiendo el meta del kit en bruto, recompila las plantillas de CSS y, opcionalmente, hace que
 * los nav-menu de unas plantillas colapsen en ese breakpoint.
 *
 * PROPÓSITO (síntoma → causa)
 *   - $kit->save( [ 'settings' => … ] ) desde CLI NO guardó active_breakpoints ni viewport_*
 *     (leído en un proceso nuevo: null): en CLI los controles del kit no están registrados y save()
 *     descarta las claves que no reconoce. Se escribe _elementor_page_settings con update_post_meta.
 *   - El nav-menu de Pro no colapsa en 'laptop' (su CSS no tiene dropdown-laptop). Para el «xl»
 *     (1280) de Tailwind: viewport_tablet_extra = 1279, laptop fuera, dropdown = 'tablet_extra'.
 *   Correspondencia usada en el caso: lg 1024 → tablet (≤1024) · md 768 → mobile (≤767) ·
 *   xl 1280 → tablet_extra (1279) · sm 640 → sin equivalente (media query en el CSS del hijo).
 *
 * USO
 *   cp e4-kit-breakpoints.plantilla.php kit-breakpoints.php     # edita la sección 1
 *   E4_SECO=1 wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/kit-breakpoints.php   # enseña el cambio sin escribir
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/kit-breakpoints.php
 *   Luego, en un proceso NUEVO: e4-sondeo-kit.php (el gestor de breakpoints cachea en el proceso).
 *   Con dropdown en tablet_extra, el HTML servido debe llevar elementor-nav-menu--dropdown-tablet_extra.
 *
 * QUÉ EDITAR
 *   $ACTIVOS, $VALORES, $QUITAR y, si procede, $NAV_MENU_DOCS / $NAV_MENU_DROPDOWN.
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (bp-tabletextra.php y laptop2.php: escribir el meta, refresh,
 *   compile_stylesheet_templates, clear_cache; walker del nav-menu). Generalizado, no probado fuera
 *   de HomeMaker: modo seco, recuento de claves del kit antes/después con restauración automática
 *   si baja, y la lista de plantillas como parámetro.
 *
 * RIESGOS
 *   - active_breakpoints se sobrescribe COMPLETO con $ACTIVOS: incluye todos los que quieras activos.
 *   - Cambiar breakpoints cambia el CSS de todo el sitio: las variantes de clase por breakpoint
 *     (tablet_extra…) solo existen si el breakpoint está activo.
 *   - Guardar las plantillas del nav-menu reescribe su documento (sin cambiar nada más).
 */

use Elementor\Plugin;

/* =====================================================================
 * 1. QUÉ QUIERES
 * ===================================================================== */
$ACTIVOS = [ 'viewport_mobile', 'viewport_tablet', 'viewport_tablet_extra' ];   // lista completa de activos
$VALORES = [ 'viewport_tablet_extra' => 1279 ];                                // px máximos de cada uno
$QUITAR  = [ 'viewport_laptop' ];                                               // claves a borrar del meta
$NAV_MENU_DOCS     = [];               // ids de plantillas (cabecera…) cuyos nav-menu deben colapsar en…
$NAV_MENU_DROPDOWN = 'tablet_extra';   // mobile | mobile_extra | tablet | tablet_extra | none (laptop NO)

/* =====================================================================
 * 2. Escribir (no editar)
 * ===================================================================== */
$SECO   = (bool) getenv( 'E4_SECO' );
$kit_id = (int) get_option( 'elementor_active_kit' );
if ( ! $kit_id ) { WP_CLI::error( 'No hay kit activo (elementor_active_kit).' ); }
$raw = get_post_meta( $kit_id, '_elementor_page_settings', true );
if ( ! is_array( $raw ) ) { WP_CLI::error( "El meta del kit #$kit_id no es un array: revisa a mano antes de escribir." ); }

$contar = function ( $r ) {
	$o = [];
	foreach ( [ 'system_colors', 'custom_colors', 'system_typography', 'custom_typography' ] as $k ) { $o[ $k ] = isset( $r[ $k ] ) ? count( $r[ $k ] ) : 0; }
	$o['claves'] = count( $r );
	return $o;
};
$antes_raw = $raw;
$antes     = $contar( $raw );

$raw['active_breakpoints'] = array_values( $ACTIVOS );
foreach ( $VALORES as $k => $v ) { $raw[ $k ] = (int) $v; }
foreach ( $QUITAR as $k ) { unset( $raw[ $k ] ); }

echo 'active_breakpoints: ' . wp_json_encode( $antes_raw['active_breakpoints'] ?? null ) . ' → ' . wp_json_encode( $raw['active_breakpoints'] ) . "\n";
foreach ( array_unique( array_merge( array_keys( $VALORES ), $QUITAR ) ) as $k ) {
	echo "$k: " . wp_json_encode( $antes_raw[ $k ] ?? null ) . ' → ' . wp_json_encode( $raw[ $k ] ?? null ) . "\n";
}
if ( $SECO ) { echo "SECO: nada escrito.\n"; return; }

update_post_meta( $kit_id, '_elementor_page_settings', $raw );
$despues = $contar( get_post_meta( $kit_id, '_elementor_page_settings', true ) );
foreach ( [ 'system_colors', 'custom_colors', 'system_typography', 'custom_typography' ] as $k ) {
	if ( $despues[ $k ] < $antes[ $k ] ) {
		update_post_meta( $kit_id, '_elementor_page_settings', $antes_raw );
		WP_CLI::error( "El kit perdió $k ({$antes[ $k ]} → {$despues[ $k ]}). Meta restaurado; no se cambió nada." );
	}
}
echo 'kit intacto: ' . wp_json_encode( $antes ) . ' → ' . wp_json_encode( $despues ) . "\n";

Plugin::$instance->breakpoints->refresh();

/* nav-menu de las plantillas indicadas: colapso en el breakpoint elegido */
foreach ( $NAV_MENU_DOCS as $doc_id ) {
	$doc = Plugin::$instance->documents->get( (int) $doc_id, false );
	if ( ! $doc ) { WP_CLI::warning( "No abro la plantilla #$doc_id" ); continue; }
	$n    = 0;
	$data = $doc->get_elements_data();
	$fix  = function ( array $els ) use ( &$fix, &$n, $NAV_MENU_DROPDOWN ) {
		foreach ( $els as $i => $e ) {
			if ( ( $e['widgetType'] ?? '' ) === 'nav-menu' ) { $els[ $i ]['settings']['dropdown'] = $NAV_MENU_DROPDOWN; $n++; }
			if ( ! empty( $e['elements'] ) ) { $els[ $i ]['elements'] = $fix( $e['elements'] ); }
		}
		return $els;
	};
	$data = $fix( $data );
	if ( $n && false === $doc->save( [ 'elements' => $data ] ) ) { WP_CLI::error( "save() falló en #$doc_id (¿--user=1?)" ); }
	echo "plantilla #$doc_id: $n nav-menu → dropdown $NAV_MENU_DROPDOWN\n";
}

\Elementor\Core\Breakpoints\Manager::compile_stylesheet_templates();
Plugin::$instance->files_manager->clear_cache();

foreach ( Plugin::$instance->breakpoints->get_active_breakpoints() as $name => $bp ) {
	echo '  activo (en este proceso): ' . str_pad( $name, 13 ) . $bp->get_value() . "px\n";
}
echo "Comprueba en un proceso nuevo con e4-sondeo-kit.php.\n";
