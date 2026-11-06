<?php
/**
 * e4-sondeo-kit.php — Sonda de arranque y de control: versiones, experimentos de Elementor, kit
 * activo, variables V4, clases globales y breakpoints (activos y guardados), más las opciones de
 * colapso del nav-menu de Pro. SOLO LECTURA.
 *
 * PROPÓSITO (síntoma → causa)
 *   - Se construyó primero con la API de Elementor 3 (colores y tipografías del kit) y la
 *     instalación era Elementor 4.3 con e_atomic_elements ACTIVO, donde el sistema de diseño son
 *     variables y clases globales. Este es el PRIMER script de cualquier proyecto.
 *   - El nav-menu clásico nunca colapsaba con dropdown 'laptop': su plantilla CSS solo trae
 *     mobile, mobile_extra, tablet, tablet_extra y none. Antes de fijar breakpoints, mira aquí qué
 *     admite el widget. Para el «xl» (1280) de Tailwind: tablet_extra = 1279 y laptop inactivo.
 *   - El CSS del kit no existe hasta que una página lo usa: verifica leyendo el meta, no el CSS.
 *
 * USO
 *   wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/e4-sondeo-kit.php
 *   Úsalo también DESPUÉS de cambiar breakpoints, en un proceso NUEVO: el gestor de breakpoints
 *   cachea dentro del proceso y el mismo script que escribe no ve el cambio.
 *   Comprobación en disco de lo que admite el nav-menu (shell):
 *     grep -oE 'dropdown-[a-z_]+' <WP_PATH>/wp-content/plugins/elementor-pro/assets/css/templates/widget-nav-menu.min.css | sort | uniq -c
 *
 * QUÉ EDITAR
 *   Nada.
 *
 * ESTADO
 *   Probado en HomeMaker (une probe.php, kitintact.php, bp.php, bpcheck.php, bpcheck2.php,
 *   verify2.php y survive.php). Generalizado, no probado fuera de HomeMaker: el orden de las
 *   secciones y las guardas cuando falta Pro o el widget.
 *
 * RIESGOS
 *   Ninguno en la BD. Activa el modo editor de Elementor en ESTE proceso para leer los controles
 *   del nav-menu (en CLI solo se registran los de contenido), por eso va al final.
 */

use Elementor\Plugin;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;
use Elementor\Modules\Variables\Storage\Variables_Repository;
use Elementor\Modules\Variables\Services\Variables_Service;
use Elementor\Modules\Variables\Services\Batch_Operations\Batch_Processor;

if ( ! class_exists( '\Elementor\Plugin' ) ) { WP_CLI::error( 'Elementor no cargado.' ); }

echo "=== VERSIONES ===\n";
printf( "PHP %s · WordPress %s · Elementor %s · Elementor Pro %s · tema %s (plantilla %s)\n",
	PHP_VERSION, get_bloginfo( 'version' ), defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'n/d',
	defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : 'no instalado',
	wp_get_theme()->get( 'Name' ), get_template() );

echo "\n=== EXPERIMENTOS ===\n";
$e = Plugin::$instance->experiments;
foreach ( $e->get_features() as $slug => $f ) {
	printf( "%s  %-34s %s\n", $e->is_feature_active( $slug ) ? 'ACTIVO  ' : 'inactivo', $slug, $f['title'] ?? '' );
}

echo "\n=== KIT ===\n";
$kit_id = (int) get_option( 'elementor_active_kit' );
$raw    = get_post_meta( $kit_id, '_elementor_page_settings', true );
$raw    = is_array( $raw ) ? $raw : [];
echo "kit id: $kit_id · claves en _elementor_page_settings: " . count( $raw ) . "\n";
foreach ( [ 'system_colors', 'custom_colors', 'system_typography', 'custom_typography' ] as $k ) {
	printf( "  %-20s %s\n", $k, isset( $raw[ $k ] ) ? count( $raw[ $k ] ) : 'FALTA' );
}
foreach ( [ 'container_width', 'space_between_widgets', 'body_color', 'link_normal_color', 'link_hover_color', 'site_name' ] as $k ) {
	printf( "  %-20s %s\n", $k, isset( $raw[ $k ] ) ? wp_json_encode( $raw[ $k ] ) : 'FALTA' );
}

echo "\n=== VARIABLES V4 ===\n";
try {
	$svc  = new Variables_Service( new Variables_Repository( Plugin::$instance->kits_manager->get_active_kit() ), new Batch_Processor() );
	$list = $svc->get_variables_list();
	$data = $list['data'] ?? $list;
	$por  = [];
	foreach ( $data as $v ) {
		if ( is_array( $v ) && isset( $v['type'] ) ) { $por[ $v['type'] ][] = $v['label'] ?? '?'; }
	}
	echo 'total: ' . count( $data ) . "\n";
	foreach ( $por as $t => $labels ) { echo "  $t = " . count( $labels ) . ': ' . implode( ', ', $labels ) . "\n"; }
} catch ( \Throwable $ex ) {
	echo 'sin servicio de variables V4: ' . $ex->getMessage() . "\n";
}

echo "\n=== CLASES GLOBALES V4 ===\n";
if ( class_exists( Global_Classes_Repository::class ) ) {
	$nf = count( Global_Classes_Repository::make()->all( true )->get_items()->all() );
	$np = count( Global_Classes_Repository::make()->set_preview( true )->all( true )->get_items()->all() );
	echo "frontend: $nf · preview: $np" . ( $nf !== $np ? '   ← distintas: ejecuta e4-sync-preview.php' : '' ) . "\n";
}

echo "\n=== BREAKPOINTS ===\n";
foreach ( Plugin::$instance->breakpoints->get_breakpoints() as $name => $bp ) {
	printf( "  %-14s %5dpx  %s\n", $name, $bp->get_value(), $bp->is_enabled() ? 'ACTIVO' : 'inactivo' );
}
echo '  guardado active_breakpoints: ' . wp_json_encode( $raw['active_breakpoints'] ?? null ) . "\n";
foreach ( $raw as $k => $v ) {
	if ( str_starts_with( (string) $k, 'viewport_' ) ) { echo "  guardado $k: " . wp_json_encode( $v ) . "\n"; }
}

echo "\n=== NAV-MENU (Pro): opciones de colapso ===\n";
$type = Plugin::$instance->widgets_manager->get_widget_types( 'nav-menu' );
if ( ! $type ) {
	echo "widget nav-menu no registrado (¿Pro inactivo?)\n";
} else {
	Plugin::$instance->editor->set_edit_mode( true );   // sin esto get_controls() solo trae los de contenido
	$cls = get_class( $type );
	$w   = new $cls( [ 'id' => 'probe00', 'elType' => 'widget', 'widgetType' => 'nav-menu', 'settings' => [] ], [] );
	$c   = $w->get_controls( 'dropdown' );
	echo 'dropdown: ' . implode( ', ', array_keys( $c['options'] ?? [] ) ) . "\n";
	echo "(las opciones del control no garantizan CSS: confirma con el grep de la cabecera)\n";
}
