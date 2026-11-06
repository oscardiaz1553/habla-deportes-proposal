<?php
/**
 * e4-clases-upsert.plantilla.php — Plantilla del <pagina>-classes.php: crea o actualiza clases
 * globales V4 SIN tocar las demás. Patrón único:
 *   leer (all(true)) → fusionar → ordenar (modificadores antes de su base) → validar con
 *   Global_Classes_Parser → put() → limpiar caché → sincronizar preview.
 *
 * PROPÓSITO (síntoma → causa)
 *   - put( $items, $order ) NO es un upsert: recibe el conjunto COMPLETO. Según el código de
 *     Global_Classes_Repository (Elementor 4.3), las clases que falten en $items se borran (sus
 *     posts se eliminan) y se dispara elementor/global_classes/cleanup, que las quita de los
 *     documentos. Un put() con solo tus clases nuevas destruiría las del resto del sitio.
 *   - Entre clases globales que tocan la misma propiedad gana la PRIMERA del orden (al revés que
 *     la cascada). Por eso los modificadores (base--mod) van delante de su base, y cada propiedad
 *     vive en una sola clase: la tipografía sin color ni margen, el espaciado en utilidades.
 *   - Sin la sincronización final, publicar desde el editor borra las clases creadas por CLI.
 *
 * USO
 *   cp e4-clases-upsert.plantilla.php <pagina>-classes.php     # edita las secciones 2 y 3
 *   php -l <pagina>-classes.php
 *   scp <pagina>-classes.php e4-helpers.php e4-sync-preview.php <SSH_ALIAS>:tmp-<PREFIJO>/
 *   ssh <SSH_ALIAS> 'E4_SECO=1 wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-classes.php'   # valida y enseña el orden, sin escribir
 *   ssh <SSH_ALIAS> 'wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-classes.php'
 *   Va SIEMPRE antes del <pagina>-build.php (el build aborta si falta alguna clase).
 *   Salida esperada: «clases: antes N → después M (nuevas a, actualizadas b, retiradas c)», las
 *   posiciones de los pares y la línea de e4-sync-preview.php.
 *
 * QUÉ EDITAR
 *   E4_PREFIJO (el <PREFIJO> del proyecto) y las secciones 2 (clases), 3 (orden) y 4 (parches).
 *   Los ejemplos usan «pfx-»: sustitúyelos o bórralos.
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (idéntico a historia-, contacto-, 404-classes.php y fix-clases.php):
 *   leer con all(true), fusionar por id, anteponer las nuevas, validar, put, clear_cache, printf
 *   de posiciones y require del sync. Generalizado, no probado fuera de HomeMaker: modo seco
 *   (E4_SECO), guardas contra borrados y órdenes inconsistentes, corrección automática de
 *   modificadores detrás de su base, lista $PARES, aviso de valor 0 en variantes responsive,
 *   retirada explícita ($RETIRAR) y $parchear por meta (breakpoint + estado) en vez de variants[0].
 *
 * RIESGOS
 *   - Redefinir una clase existente en $new reemplaza TODAS sus variantes. Para cambiar una
 *     propiedad suelta usa $parchear (sección 4).
 *   - Retirar una clase ($RETIRAR) la borra del sitio y de todos los documentos que la usan.
 *   - Anteponer las nuevas cambia la prioridad frente a clases anteriores: re-ejecutar un script
 *     viejo reordena. Revisa las posiciones impresas y fija $FRENTE / $AL_FINAL si hace falta.
 *   - Nunca edites las metas de clases a mano: todo pasa por el parser.
 */

use Elementor\Plugin;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;
use Elementor\Modules\GlobalClasses\Global_Classes_Parser;

if ( ! defined( 'E4_PREFIJO' ) ) { define( 'E4_PREFIJO', 'pfx' ); }   // <PREFIJO> del proyecto
$E4_SEED = 'clases';
require __DIR__ . '/e4-helpers.php';   // $px $em $pct $cus $auto $none $str $num $font $hex $bg $dim $bw $gap2 $flex0 $flexgs $sombra $var $cls $variante

$SECO = (bool) getenv( 'E4_SECO' );

/* =====================================================================
 * 1. Estado actual (nunca se construye el conjunto desde cero)
 * ===================================================================== */
$repo  = Global_Classes_Repository::make();
$all   = $repo->all( true );           // true = fuerza la lectura de la BD, sin la caché del repositorio
$items = $all->get_items()->all();     // Collection → array
$order = $all->get_order()->all();
$antes = array_keys( $items );

/* =====================================================================
 * 2. TUS CLASES
 *    - Declara cada modificador ANTES que su base (se anteponen en este orden).
 *    - Todo contenedor fija padding (0 si no lleva) y, si no debe estirarse, width auto: los
 *      e-flexbox/e-div-block/e-grid nacen con padding 10px, width 100% y min-width 30px (vacíos).
 *    - Toda clase de e-grid fija grid-template-rows auto (nace con 2 filas) y repite
 *      grid-template-columns en mobile aunque tablet ya lo resuelva.
 *    - Todo hover de botón o enlace fija fondo Y color (si no, el a:hover del kit pinta el texto).
 *    - Copia literalmente los clamp() del prototipo con $cus(); rem → px exactos; letter-spacing en em.
 *    - Colores: mejor $var( '<etiqueta>' ) que $hex(): el cliente los cambia en el kit.
 * ===================================================================== */
$new = [];

/* modificador antes que su base */
$new['pfx-btn--sm'] = $cls( [ 'padding' => $dim( 10, 10, 18, 18 ), 'font-size' => $px( 13 ) ] );
$new['pfx-btn']     = $cls(
	[ 'padding' => $dim( 14, 14, 24, 24 ), 'border-radius' => $px( 999 ), 'width' => $auto,
	  'font-family' => $font( 'Archivo' ), 'font-size' => $px( 15 ), 'font-weight' => $str( '700' ),
	  'background' => $bg( $hex( '#1A1A1A' ) ), 'color' => $hex( '#FFFFFF' ) ],   // o $var( 'tinta' )
	null,
	null,
	[ 'background' => $bg( $hex( '#333333' ) ), 'color' => $hex( '#FFFFFF' ) ]     // hover: fondo Y color
);
/* utilidad de espaciado (una propiedad, una clase) */
$new['pfx-mt-24'] = $cls( [ 'margin' => $dim( 24 ) ] );
/* rejilla responsive */
$new['pfx-rejilla-3'] = $cls(
	[ 'grid-template-columns' => $str( 'repeat(3, minmax(0, 1fr))' ), 'grid-template-rows' => $str( 'auto' ),
	  'gap' => $px( 24 ), 'padding' => $dim( 0, 0, 0, 0 ), 'width' => $pct( 100 ) ],
	[ 'grid-template-columns' => $str( 'repeat(2, minmax(0, 1fr))' ) ],
	[ 'grid-template-columns' => $str( 'minmax(0, 1fr)' ) ]
);
/* visibilidad en un breakpoint adicional: va en el CONTENEDOR, no en cada hijo (un contenedor
   vacío conserva su gap y su hueco). Requiere el breakpoint activo en el kit
   (e4-kit-breakpoints.plantilla.php); en el caso de referencia validó con solo esta variante.
$new['pfx-solo-escritorio'] = [ $variante( 'tablet_extra', [ 'display' => $str( 'none' ) ] ) ]; */

/* =====================================================================
 * 3. ORDEN Y RETIRADAS
 * ===================================================================== */
$FRENTE   = [];   // ids a forzar al principio (máxima prioridad), en este orden
$AL_FINAL = [];   // ids a forzar al final (mínima prioridad)
$PARES    = [     // [ a, b ] = «a debe ir antes que b». Se comprueban e imprimen; si falla, no se escribe.
	[ 'pfx-btn--sm', 'pfx-btn' ],
];
$RETIRAR  = [];   // ids a borrar del sitio (y de los documentos que las usen). Normalmente vacío.

/* =====================================================================
 * 4. PARCHES a clases existentes (por meta, nunca por índice de variante)
 * ===================================================================== */
$hechos   = [];
$parchear = function ( $id, $bp, $state, array $props ) use ( &$items, &$hechos ) {
	if ( ! isset( $items[ $id ] ) ) { WP_CLI::error( "parche: la clase «{$id}» no existe." ); }
	foreach ( $items[ $id ]['variants'] as $i => $v ) {
		if ( ( $v['meta']['breakpoint'] ?? 'desktop' ) === $bp && ( $v['meta']['state'] ?? null ) === $state ) {
			$items[ $id ]['variants'][ $i ]['props'] = array_merge( $v['props'], $props );
			$hechos[] = "$id@$bp" . ( $state ? ":$state" : '' ) . ' (fusionada)';
			return;
		}
	}
	$items[ $id ]['variants'][] = [ 'meta' => [ 'breakpoint' => $bp, 'state' => $state ], 'props' => $props ];
	$hechos[] = "$id@$bp" . ( $state ? ":$state" : '' ) . ' (nueva variante)';
};
/* Solo para clases que NO redefines en $new (la fusión de la sección 5 las reemplazaría enteras).
   ejemplo: $parchear( 'pfx-btn--verde', 'desktop', 'hover', [ 'color' => $hex( '#FFFFFF' ) ] ); */

/* =====================================================================
 * 5. Fusión y guardas (no editar)
 * ===================================================================== */
foreach ( array_keys( $new ) as $id ) {
	if ( ! str_starts_with( $id, E4_PREFIJO . '-' ) ) { WP_CLI::error( "«{$id}» no lleva el prefijo " . E4_PREFIJO . '- (y las etiquetas cortas fallan: class_name_too_short).' ); }
}
$actualizadas = array_values( array_intersect( array_keys( $new ), $antes ) );
foreach ( $new as $id => $variants ) {
	$items[ $id ] = [ 'id' => $id, 'type' => 'class', 'label' => $id, 'variants' => $variants ];
}
foreach ( $RETIRAR as $id ) { unset( $items[ $id ] ); }

/* Elementor descarta un valor 0 en variantes responsive (p. ej. order: 0 en tablet no se emite) */
foreach ( $new as $id => $variants ) {
	foreach ( $variants as $v ) {
		if ( 'desktop' === ( $v['meta']['breakpoint'] ?? 'desktop' ) ) { continue; }
		foreach ( $v['props'] as $prop => $val ) {
			if ( is_array( $val ) && 'number' === ( $val['$$type'] ?? '' ) && isset( $val['value'] ) && 0 == $val['value'] ) {
				echo "AVISO: {$id}@{$v['meta']['breakpoint']} {$prop} = 0 se descarta en variantes responsive; usa -1 u otro valor equivalente.\n";
			}
		}
	}
}

/* orden: nuevas delante (en el orden declarado), luego el resto */
$nuevas = array_keys( $new );
$order  = array_values( array_merge( $nuevas, array_diff( $order, $nuevas, $RETIRAR ) ) );
if ( $FRENTE ) { $order = array_values( array_merge( $FRENTE, array_diff( $order, $FRENTE ) ) ); }
if ( $AL_FINAL ) { $order = array_values( array_merge( array_diff( $order, $AL_FINAL ), $AL_FINAL ) ); }

/* modificadores (base--mod) delante de su base: se corrige si el par toca una clase de este
   script; si no, solo se avisa (moverlo cambiaría prioridades de otras páginas) */
$tocadas = array_flip( array_merge( $nuevas, $FRENTE, $AL_FINAL ) );
$movidas = [];
foreach ( $order as $id ) {
	$cut = strpos( $id, '--' );
	if ( false === $cut ) { continue; }
	$base = substr( $id, 0, $cut );
	$pos  = array_flip( $order );
	if ( ! isset( $pos[ $base ] ) || $pos[ $id ] < $pos[ $base ] ) { continue; }
	if ( ! isset( $tocadas[ $id ] ) && ! isset( $tocadas[ $base ] ) ) {
		echo "AVISO: {$id} ({$pos[ $id ]}) va detrás de su base {$base} ({$pos[ $base ]}); no lo toca este script.\n";
		continue;
	}
	$order = array_values( array_diff( $order, [ $id ] ) );
	array_splice( $order, array_search( $base, $order, true ), 0, [ $id ] );
	$movidas[] = "{$id} → delante de {$base}";
}
if ( $movidas ) { echo 'reordenadas: ' . implode( ' | ', $movidas ) . "\n"; }

/* coherencia items ↔ orden y ningún borrado no pedido */
$keys = array_keys( $items );
if ( array_diff( $keys, $order ) || array_diff( $order, $keys ) || count( $order ) !== count( array_unique( $order ) ) ) {
	WP_CLI::error( 'Orden e items no cuadran (faltan, sobran o hay repetidos). No se escribe nada.' );
}
$perdidas = array_values( array_diff( $antes, $keys ) );
if ( array_diff( $perdidas, $RETIRAR ) ) {
	WP_CLI::error( 'Se borrarían clases no pedidas: ' . implode( ', ', array_diff( $perdidas, $RETIRAR ) ) . '. No se escribe nada.' );
}

/* pares a < b */
$pos = array_flip( $order );
$mal = [];
$txt = [];
foreach ( $PARES as [ $a, $b ] ) {
	if ( ! isset( $pos[ $a ], $pos[ $b ] ) ) { $txt[] = "$a/$b: alguna no existe"; continue; }
	$txt[] = "$a {$pos[ $a ]} < $b {$pos[ $b ]}";
	if ( $pos[ $a ] > $pos[ $b ] ) { $mal[] = "$a > $b"; }
}

/* =====================================================================
 * 6. Validar, escribir, sincronizar
 * ===================================================================== */
$parsed = Global_Classes_Parser::make()->parse( [ 'items' => $items, 'order' => $order ] );
if ( ! $parsed->is_valid() ) {
	foreach ( $parsed->errors()->all() as $e ) { echo "ERROR {$e['key']} -> {$e['error']}\n"; }
	WP_CLI::error( 'Validación fallida: no se escribe nada. La clave señala la prop exacta (p. ej. items.pfx-x.variants[0].margin).' );
}
if ( $txt ) { echo 'pares: ' . implode( ' | ', $txt ) . "\n"; }
if ( $mal ) { WP_CLI::error( 'Orden incorrecto en: ' . implode( ', ', $mal ) . '. Ajusta $FRENTE / $AL_FINAL.' ); }
if ( $hechos ) { echo 'parches: ' . implode( ', ', $hechos ) . "\n"; }

$retiradas = count( array_intersect( $RETIRAR, $antes ) );
if ( $SECO ) {
	printf( "SECO: válido. Se escribirían %d clases (antes %d; nuevas %d, actualizadas %d, retiradas %d). Nada escrito.\n",
		count( $items ), count( $antes ), count( $nuevas ) - count( $actualizadas ), count( $actualizadas ), $retiradas );
	return;
}

$repo->put( $items, $order );
Plugin::$instance->files_manager->clear_cache();

$o = $repo->all( true )->get_order()->all();
printf( "clases: antes %d → después %d (nuevas %d, actualizadas %d, retiradas %d)\n",
	count( $antes ), count( $o ), count( $nuevas ) - count( $actualizadas ), count( $actualizadas ), $retiradas );
if ( count( $o ) < count( $antes ) - $retiradas ) {
	WP_CLI::warning( 'Hay MENOS clases de las esperadas. Revisa con e4-dump-clases.php antes de seguir.' );
}

/* el editor lee el contexto «preview»: sin esto, publicar desde Elementor borra estas clases */
require __DIR__ . '/e4-sync-preview.php';
