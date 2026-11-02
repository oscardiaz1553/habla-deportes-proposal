<?php
/**
 * e4-helpers.php — Biblioteca compartida para construir con Elementor 4 atómico (V4) por WP-CLI:
 * constructores de elementos (e-flexbox, e-grid, e-heading, e-paragraph, e-button, e-image, e-svg…),
 * constructores de valores con envoltorio $$type para clases globales y estilos locales,
 * guardado verificado de documentos y utilidades de árbol.
 *
 * PROPÓSITO
 *   Todo son closures en variables, no funciones: el mismo archivo se carga desde varios scripts
 *   sin «Cannot redeclare». No escribe nada al cargarse; solo $e4_guardar, $e4_plantilla y
 *   $e4_condiciones escriben, y solo cuando se llaman.
 *
 * USO
 *   $E4_SEED = '<pagina>';                   // semilla de ids: una distinta por documento
 *   require __DIR__ . '/e4-helpers.php';     // mismo directorio; nunca el /tmp del servidor
 *   $s1 = $flex( [ 'pfx-banda' ], [ $h( 'h2', [ 'pfx-titulo' ], 'Hola<strong> mundo.</strong>' ) ], [ 'tag' => 'section', 'title' => 'Intro' ] );
 *   $e4_guardar( <ID_PAGINA>, [ $s1 ] );
 *   Ejecución: wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-build.php
 *   (sin --user=1, Document::save() devuelve false sin avisar y no guarda nada).
 *
 * QUÉ EDITAR
 *   Nada en este archivo. En cada script: $E4_SEED antes del require. Para rehacer la traducción
 *   de un documento con los MISMOS ids que el original, pon $__n = 0 antes de reconstruir su árbol.
 *   Opciones comunes ($o) de los constructores de elementos:
 *     'tag'   => etiqueta HTML. Enums verificados (Elementor 4.3): e-flexbox y e-div-block
 *                div header section article aside footer a button main nav; e-grid igual sin main
 *                ni nav; e-paragraph p span; e-heading h1–h6. No hay dl, ul ni ol.
 *     'link'  => '/ruta/' (se resuelve con home_url), '#ancla', 'tel:…', 'mailto:…' o URL completa.
 *     'attrs' => [ 'data-pfx-reveal' => 120, 'aria-hidden' => 'true' ] (prop «attributes», editable
 *                en Avanzado › Atributos). Engancha el JS del tema hijo a data-*, nunca a clases.
 *     'cssid' => id HTML (_cssid) para anclas.
 *     'title' => nombre que el cliente ve en el Navegador del editor (editor_settings.title).
 *
 * ESTADO
 *   Probado en HomeMaker (Elementor 4.3.0, Pro 4.2.3, Hello 3.5.1): $uid, $attrs, $link, $settings,
 *   $box, $flex, $grid, $widget, $h, $p, $btn, $img, $tam, $con_tag, $svg, $v3, $con_ids,
 *   $estilo_local (era $posicion, solo desktop), $texto_din, $titulo_din, $foto_din, $enlace_din,
 *   los constructores de valores ($px … $sombra) y $cls.
 *   Generalizado, no probado fuera de HomeMaker: $e4_guardar (el $hm_save original más la
 *   comprobación de save(), la fusión de ajustes y el idioma de Polylang de 404-build),
 *   $e4_variables/$var perezosos, $variante, $e4_plantilla, $e4_condiciones, $e4_recorrer,
 *   $e4_arbol, $e4_clases_usadas, $e4_ids_repetidos, el fallback de imagen de $foto_din y las
 *   variantes por breakpoint o estado de $estilo_local.
 *
 * RIESGOS
 *   - $link usa home_url(): en CLI no hay idioma, así que las rutas del idioma secundario se pasan
 *     completas ('/<IDIOMA_2>/products/').
 *   - Un e-flexbox con 'link' se pinta como <a>: no metas dentro otro elemento enlazado.
 *   - e-heading y e-paragraph limpian atributos del HTML: <span class> pierde la clase; <strong>
 *     sobrevive. El acento de color va con <strong> + una clase en el widget y CSS del hijo.
 *   - $img guarda solo el id del adjunto (url null), así sobrevive a un cambio de dominio. El alt
 *     real sale de la Biblioteca de medios. Tamaño 'full' por defecto: usa $tam() para 'large' o
 *     'medium_large'. fetchpriority: en PÁGINAS no lo pongas a mano (Elementor/WordPress ya lo asignan a
 *     la primera imagen grande y saldría duplicado); en singles y loop items del Theme Builder no
 *     pasa (no atraviesan the_content): ahí sí, como atributo del widget ('attrs'). Las e-image salen
 *     sin sizes: a logos y otras imágenes eager ponles 'sizes' explícito en 'attrs'.
 *   - $svg: bajo WP-CLI get_stylesheet_directory_uri() sale en http:// y el editor lo bloquearía
 *     por contenido mixto; por eso se fuerza https. e-svg pone fill="currentColor" en la raíz:
 *     para SVG de solo trazo, envuelve los hijos en <g fill="none"> dentro del archivo.
 *   - Los hijos que generan los builders de Elementor (p. ej. controles de e-background-video) no
 *     traen id ni version y sus textos van en inglés: pásalos por $con_ids() y tradúcelos.
 *   - $e4_guardar reescribe el árbol entero del documento (idempotente si los ids son deterministas).
 */

use Elementor\Plugin;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Escaped_Html_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
use Elementor\Modules\Variables\Storage\Variables_Repository;
use Elementor\Modules\Variables\Services\Variables_Service;
use Elementor\Modules\Variables\Services\Batch_Operations\Batch_Processor;

if ( ! class_exists( '\Elementor\Plugin' ) ) {
	WP_CLI::error( 'Elementor no está cargado.' );
}

/* =====================================================================
 * 1. Ids deterministas
 *    7 caracteres [a-z0-9]: el endpoint refresh-loop exige /^[a-zA-Z0-9]{7}$/ y la URL del filtro
 *    ?e-filter-<id>-<tax> exige [a-z0-9]{7}. Misma semilla + mismo orden = mismos ids al re-ejecutar.
 *    Los elementos a los que otros apuntan (Loop Grid, formularios) llevan un id fijo legible.
 * ===================================================================== */
$E4_SEED = $E4_SEED ?? 'e4';
$__n     = 0;
$uid     = function () use ( &$__n, $E4_SEED ) {
	return substr( md5( $E4_SEED . '-' . ( ++$__n ) ), 0, 7 );
};

/* =====================================================================
 * 2. Settings comunes
 * ===================================================================== */
$attrs = function ( array $kv ) {
	$list = [];
	foreach ( $kv as $k => $val ) {
		$list[] = [ '$$type' => 'key-value', 'value' => [
			'key'   => [ '$$type' => 'string', 'value' => (string) $k ],
			'value' => [ '$$type' => 'string', 'value' => (string) $val ],
		] ];
	}
	return [ '$$type' => 'attributes', 'value' => $list ];
};
/* rutas que empiezan por «/» se resuelven contra el sitio; lo demás va tal cual */
$link = fn( $path ) => Link_Prop_Type::generate( [
	'destination'   => Url_Prop_Type::generate( str_starts_with( $path, '/' ) ? home_url( $path ) : $path ),
	'isTargetBlank' => Boolean_Prop_Type::generate( false ),
] );
$settings = function ( array $classes, array $o ) use ( $attrs, $link ) {
	$s = [];
	if ( $classes ) { $s['classes'] = Classes_Prop_Type::generate( array_values( array_filter( $classes ) ) ); }
	if ( ! empty( $o['tag'] ) ) { $s['tag'] = String_Prop_Type::generate( $o['tag'] ); }
	if ( ! empty( $o['link'] ) ) { $s['link'] = $link( $o['link'] ); }
	if ( ! empty( $o['attrs'] ) ) { $s['attributes'] = $attrs( $o['attrs'] ); }
	if ( ! empty( $o['cssid'] ) ) { $s['_cssid'] = String_Prop_Type::generate( $o['cssid'] ); }
	return $s;
};

/* =====================================================================
 * 3. Elementos V4
 * ===================================================================== */
$box = function ( $type, array $classes, array $children, array $o = [] ) use ( $uid, $settings ) {
	return [
		'id' => $uid(), 'elType' => $type, 'version' => '0.4',
		'settings' => $settings( $classes, $o ), 'elements' => $children, 'styles' => [],
		'editor_settings' => isset( $o['title'] ) ? [ 'title' => $o['title'] ] : [],
	];
};
$flex = fn( $c, $ch, $o = [] ) => $box( 'e-flexbox', $c, $ch, $o );
$grid = fn( $c, $ch, $o = [] ) => $box( 'e-grid', $c, $ch, $o );
/* e-div-block: $box( 'e-div-block', [ 'pfx-raya' ], [], [ 'title' => 'Raya' ] ) */
$widget = function ( $type, array $classes, array $extra, array $o = [] ) use ( $uid, $settings ) {
	return [
		'id' => $uid(), 'elType' => 'widget', 'widgetType' => $type, 'version' => '0.4',
		'settings' => array_merge( $settings( $classes, $o ), $extra ), 'elements' => [], 'styles' => [],
		'editor_settings' => isset( $o['title'] ) ? [ 'title' => $o['title'] ] : [],
	];
};
$h   = fn( $tag, $c, $html, $o = [] ) => $widget( 'e-heading', $c, [ 'tag' => String_Prop_Type::generate( $tag ), 'title' => Escaped_Html_Prop_Type::generate( $html ) ], $o );
$p   = fn( $c, $html, $o = [] ) => $widget( 'e-paragraph', $c, [ 'paragraph' => Escaped_Html_Prop_Type::generate( $html ) ], $o );
$btn = fn( $c, $text, $o = [] ) => $widget( 'e-button', $c, [ 'text' => Escaped_Html_Prop_Type::generate( $text ) ], $o );
$img = fn( $c, $id, $alt, $o = [] ) => $widget( 'e-image', $c, [ 'image' => Image_Prop_Type::generate( [
	'src'  => Image_Src_Prop_Type::generate( [
		'id'  => Image_Attachment_Id_Prop_Type::generate( (int) $id ),
		'url' => null,
		'alt' => String_Prop_Type::generate( $alt ),
	] ),
	'size' => String_Prop_Type::generate( 'full' ),
] ) ], $o );

/* tamaño registrado de WordPress para una e-image ya construida: 'large', 'medium_large'… */
$tam = function ( array $el, $size ) {
	$el['settings']['image']['value']['size'] = String_Prop_Type::generate( $size );
	return $el;
};
/* cambia la etiqueta de un elemento ya construido, p. ej. un e-paragraph como <span> */
$con_tag = function ( array $el, $tag ) {
	$el['settings']['tag'] = String_Prop_Type::generate( $tag );
	return $el;
};
/* e-svg desde un archivo del propio sitio (Elementor lo lee del disco y lo sanea: no hace falta
   habilitar subidas SVG). $ruta «/img/x.svg» = relativa al tema hijo; si no, URL completa. */
$svg = function ( array $c, $ruta, array $o = [] ) use ( $widget ) {
	$url = str_starts_with( $ruta, '/' ) ? get_stylesheet_directory_uri() . $ruta : $ruta;
	return $widget( 'e-svg', $c, [
		'svg' => Svg_Src_Prop_Type::generate( [ 'url' => Url_Prop_Type::generate( set_url_scheme( $url, 'https' ) ) ] ),
	], $o );
};
/* widget clásico (V3) dentro del maquetado V4: settings planos, sin $$type. Envuélvelo en un
   e-flexbox con una clase «caja» (column, stretch, padding 0, width 100%): las clases globales
   solo llegan al CSS de la página si las usa un elemento V4. */
$v3 = fn( $id, $type, array $s ) => [ 'id' => $id, 'elType' => 'widget', 'widgetType' => $type, 'settings' => $s, 'elements' => [] ];

/* los builders de Elementor (Element_Builder::…->build(), build_default_element()) no ponen id:
   en el editor lo asigna el navegador. Esto los completa de forma determinista. */
$con_ids = function ( array $el ) use ( &$con_ids, $uid ) {
	if ( empty( $el['id'] ) ) { $el['id'] = $uid(); }
	if ( ! isset( $el['version'] ) ) { $el['version'] = '0.4'; }
	foreach ( $el['elements'] ?? [] as $i => $child ) { $el['elements'][ $i ] = $con_ids( $child ); }
	return $el;
};

/* estilo local: el valor queda en el panel Estilo del propio elemento (p. ej. la posición de
   cada pin de un mapa), editable por el cliente y visible en el editor. Una clase global por
   elemento sería absurda y el CSS del hijo no se ve en el panel. */
$estilo_local = function ( array $el, array $props, $sal = 'loc', $bp = 'desktop', $state = null ) {
	$sid = 'e-' . $el['id'] . '-' . substr( md5( $sal . $el['id'] ), 0, 7 );
	$var = [ 'meta' => [ 'breakpoint' => $bp, 'state' => $state ], 'props' => $props ];
	if ( isset( $el['styles'][ $sid ] ) ) {
		$el['styles'][ $sid ]['variants'][] = $var;
	} else {
		$el['styles'][ $sid ] = [ 'id' => $sid, 'type' => 'class', 'label' => 'local', 'variants' => [ $var ] ];
	}
	if ( ! isset( $el['settings']['classes']['value'] ) ) {
		$el['settings']['classes'] = Classes_Prop_Type::generate( [] );
	}
	if ( ! in_array( $sid, $el['settings']['classes']['value'], true ) ) {
		$el['settings']['classes']['value'][] = $sid;
	}
	return $el;
};

/* =====================================================================
 * 4. Contenido dinámico (los valores $$type 'dynamic' salen de e4-dynamic-tags-preambulo.php,
 *    que va ANTES de este require). Solo en settings: los estilos se compilan a CSS en caché.
 * ===================================================================== */
$texto_din = function ( $classes, array $dinamico, $o = [] ) use ( $p ) {
	$el = $p( $classes, 'x', $o );
	$el['settings']['paragraph'] = $dinamico;
	return $el;
};
$titulo_din = function ( $tag, $classes, array $dinamico, $o = [] ) use ( $h ) {
	$el = $h( $tag, $classes, 'x', $o );
	$el['settings']['title'] = $dinamico;
	return $el;
};
/* $src_dinamico = $d_foto_destacada( <id de adjunto de respaldo> ): sin respaldo, un post sin
   imagen destacada pinta un <img> sin src. */
$foto_din = function ( $classes, array $src_dinamico, $size = 'medium_large', $o = [] ) use ( $img ) {
	$el = $img( $classes, 1, '', $o );
	$el['settings']['image'] = Image_Prop_Type::generate( [ 'src' => $src_dinamico, 'size' => String_Prop_Type::generate( $size ) ] );
	return $el;
};
/* enlaza un contenedor o botón a la URL del post del loop: $enlace_din( $flex(…), $d_url ) */
$enlace_din = function ( array $el, array $d_url ) {
	$el['settings']['link'] = Link_Prop_Type::generate( [ 'destination' => $d_url, 'isTargetBlank' => Boolean_Prop_Type::generate( false ) ] );
	return $el;
};

/* =====================================================================
 * 5. Valores de estilo con $$type (clases globales y estilos locales)
 *    Formas verificadas con Global_Classes_Parser en Elementor 4.3. Ante un tipo nuevo, sondea
 *    antes con e4-sondeo-esquema.php: no adivines envoltorios.
 * ===================================================================== */
$px   = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => 'px' ] ];
$em   = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => 'em' ] ];
$pct  = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => '%' ] ];
/* clamp()/calc() literales del prototipo: 'clamp(2.3rem, 4.6vw, 3.9rem)' (una sola variante desktop) */
$cus  = fn( $s ) => [ '$$type' => 'size', 'value' => [ 'size' => $s, 'unit' => 'custom' ] ];
$auto = [ '$$type' => 'size', 'value' => [ 'unit' => 'auto', 'size' => '' ] ];
$none = [ '$$type' => 'size', 'value' => [ 'unit' => 'custom', 'size' => 'none' ] ];
/* display, position, aspect-ratio ('5 / 4'), object-fit, flex-direction, grid-template-*… */
$str  = fn( $s ) => [ '$$type' => 'string', 'value' => $s ];
/* z-index y el interior de flex. Nunca 0 en una variante responsive: se descarta. */
$num  = fn( $n ) => [ '$$type' => 'number', 'value' => $n ];
$font = fn( $f ) => [ '$$type' => 'font-family', 'value' => $f ];   // no es 'string'
$hex  = fn( $c ) => [ '$$type' => 'color', 'value' => $c ];         // '#FFFFFF' o 'rgba(0, 0, 0, 0.1)'
$bg   = fn( $c ) => [ '$$type' => 'background', 'value' => [ 'color' => $c ] ]; // $c = $hex(…) o $var(…)
/* margin/padding: bloque arriba, bloque abajo, línea inicio, línea fin (null = no se toca).
   Ojo: si solo das inline-*, los 10px verticales de base del contenedor siguen ahí. */
$dim  = function ( $bs = null, $be = null, $is = null, $ie = null ) use ( $px ) {
	$m = [];
	foreach ( [ 'block-start' => $bs, 'block-end' => $be, 'inline-start' => $is, 'inline-end' => $ie ] as $k => $v ) {
		if ( null !== $v ) { $m[ $k ] = is_array( $v ) ? $v : $px( $v ); }
	}
	return [ '$$type' => 'dimensions', 'value' => $m ];
};
/* border-width (arriba, derecha, abajo, izquierda) */
$bw   = fn( $t, $r, $b, $l ) => [ '$$type' => 'border-width-v2', 'value' => [ 'block-start' => $px( $t ), 'inline-end' => $px( $r ), 'block-end' => $px( $b ), 'inline-start' => $px( $l ) ] ];
/* gap doble: fila y columna */
$gap2 = fn( $row, $col ) => [ '$$type' => 'layout-direction', 'value' => [ 'row' => $px( $row ), 'column' => $px( $col ) ] ];
$flex0  = [ '$$type' => 'flex', 'value' => [ 'flexShrink' => [ '$$type' => 'number', 'value' => 0 ] ] ];
$flexgs = fn( $g, $s ) => [ '$$type' => 'flex', 'value' => [ 'flexGrow' => $num( $g ), 'flexShrink' => $num( $s ) ] ];
/* una sombra; varias van en [ '$$type' => 'box-shadow', 'value' => [ $sombra(…), $sombra(…) ] ] */
$sombra = fn( $v, $b, $s, $a ) => [ '$$type' => 'shadow', 'value' => [ 'hOffset' => $px( 0 ), 'vOffset' => $px( $v ), 'blur' => $px( $b ),
	'spread' => $px( $s ), 'color' => $hex( "rgba(0, 0, 0, $a)" ), 'position' => null ] ];

/* variables de color V4: el valor es el ID de la variable, no su etiqueta. Carga perezosa. */
$e4_variables = function () {
	$svc  = new Variables_Service( new Variables_Repository( Plugin::$instance->kits_manager->get_active_kit() ), new Batch_Processor() );
	$list = $svc->get_variables_list();
	$data = $list['data'] ?? $list;
	$map  = [];
	foreach ( $data as $id => $v ) {
		if ( is_array( $v ) && isset( $v['label'] ) ) { $map[ $v['label'] ] = $id; }
	}
	return $map;
};
$E4_V = null;
$var  = function ( $label ) use ( &$E4_V, $e4_variables ) {
	if ( null === $E4_V ) { $E4_V = $e4_variables(); }
	if ( ! isset( $E4_V[ $label ] ) ) {
		WP_CLI::error( "La variable «{$label}» no existe en el kit. Créala antes (e4-variables.plantilla.php) o revisa la etiqueta." );
	}
	return [ '$$type' => 'global-color-variable', 'value' => $E4_V[ $label ] ];
};

/* variantes de una clase: desktop, tablet, mobile y hover (desktop) */
$cls = function ( $d, $t = null, $m = null, $h = null ) {
	$v = [ [ 'meta' => [ 'breakpoint' => 'desktop', 'state' => null ], 'props' => $d ] ];
	if ( $t ) { $v[] = [ 'meta' => [ 'breakpoint' => 'tablet', 'state' => null ], 'props' => $t ]; }
	if ( $m ) { $v[] = [ 'meta' => [ 'breakpoint' => 'mobile', 'state' => null ], 'props' => $m ]; }
	if ( $h ) { $v[] = [ 'meta' => [ 'breakpoint' => 'desktop', 'state' => 'hover' ], 'props' => $h ]; }
	return $v;
};
/* cualquier otra combinación: array_merge( $cls( … ), [ $variante( 'tablet_extra', [ 'display' => $str( 'none' ) ] ) ] ) */
$variante = fn( $bp, array $props, $state = null ) => [ 'meta' => [ 'breakpoint' => $bp, 'state' => $state ], 'props' => $props ];

/* =====================================================================
 * 6. Árbol: recorrer, contar, imprimir, comprobar
 * ===================================================================== */
/* $fn( array $el, ?string $tipo_padre ): array → devuelve el elemento (modificado o no).
   Recorre por valor: nunca tomes una referencia a una clave que puede no existir
   ($s = &$el['settings']['x'] la crea con null y Elementor revienta al guardar). */
$e4_recorrer = function ( array $els, callable $fn, $padre = null ) use ( &$e4_recorrer ) {
	foreach ( $els as $i => $el ) {
		$el = $fn( $el, $padre );
		if ( ! empty( $el['elements'] ) ) {
			$el['elements'] = $e4_recorrer( $el['elements'], $fn, $el['widgetType'] ?? $el['elType'] ?? null );
		}
		$els[ $i ] = $el;
	}
	return $els;
};
$e4_contar = function ( array $els ) use ( &$e4_contar ) {
	$c = 0;
	foreach ( $els as $e ) { $c += 1 + $e4_contar( $e['elements'] ?? [] ); }
	return $c;
};
/* árbol tipo + clases (V4) o _css_classes (V3) */
$e4_arbol = function ( array $els, $depth = 0 ) use ( &$e4_arbol ) {
	foreach ( $els as $e ) {
		$t = $e['widgetType'] ?? $e['elType'];
		$c = isset( $e['settings']['classes']['value'] ) ? implode( ' ', $e['settings']['classes']['value'] ) : ( $e['settings']['_css_classes'] ?? '' );
		echo str_repeat( '  ', $depth ) . "· $t  [$c]" . ( isset( $e['editor_settings']['title'] ) ? '  «' . $e['editor_settings']['title'] . '»' : '' ) . "\n";
		if ( ! empty( $e['elements'] ) ) { $e4_arbol( $e['elements'], $depth + 1 ); }
	}
};
/* clases globales que usa el árbol (sin contar los estilos locales de cada elemento) */
$e4_clases_usadas = function ( array $els ) use ( &$e4_clases_usadas ) {
	$out = [];
	foreach ( $els as $e ) {
		foreach ( (array) ( $e['settings']['classes']['value'] ?? [] ) as $c ) {
			if ( ! isset( ( $e['styles'] ?? [] )[ $c ] ) ) { $out[ $c ] = true; }
		}
		$out += array_fill_keys( $e4_clases_usadas( $e['elements'] ?? [] ), true );
	}
	return array_keys( $out );
};
/* ids ausentes o repetidos dentro del documento */
$e4_ids_repetidos = function ( array $els ) {
	$vistos = [];
	$mal    = [];
	$walk   = function ( array $els ) use ( &$walk, &$vistos, &$mal ) {
		foreach ( $els as $e ) {
			$id = $e['id'] ?? '';
			if ( ! is_string( $id ) || '' === $id ) { $mal[] = '(sin id: ' . ( $e['widgetType'] ?? $e['elType'] ?? '?' ) . ')'; }
			elseif ( isset( $vistos[ $id ] ) ) { $mal[] = $id; }
			$vistos[ $id ] = true;
			$walk( $e['elements'] ?? [] );
		}
	};
	$walk( $els );
	return $mal;
};

/* =====================================================================
 * 7. Documentos
 * ===================================================================== */
/* Guarda el árbol completo en un documento, comprueba el resultado y resume lo guardado.
   $ajustes (opcional) se FUSIONA con _elementor_page_settings: save_settings_to_db reemplaza el
   meta entero, y un meta vacío es '' → (array) '' deja una clave 0 basura. */
$e4_guardar = function ( int $id, array $elements, ?array $ajustes = null ) use ( $e4_contar ) {
	$doc = Plugin::$instance->documents->get( $id, false );
	if ( ! $doc ) { WP_CLI::error( "Elementor no abre el documento #$id (¿existe?, ¿--user=1?)." ); }
	$data = [ 'elements' => $elements ];
	if ( null !== $ajustes ) {
		$prev = $doc->get_meta( '_elementor_page_settings' );
		$prev = is_array( $prev ) ? $prev : [];
		unset( $prev[0] );
		$data['settings'] = array_merge( $prev, $ajustes );
	}
	/* al guardar, Elementor pinta el documento para su texto plano (post_content): con Polylang,
	   que lo pinte en el idioma del documento, no en el por defecto de CLI */
	$cambiado = false;
	$antes    = null;
	if ( function_exists( 'PLL' ) && function_exists( 'pll_get_post_language' ) ) {
		$lang = pll_get_post_language( $id );
		$obj  = $lang ? PLL()->model->get_language( $lang ) : null;
		if ( $obj ) { $antes = PLL()->curlang; PLL()->curlang = $obj; $cambiado = true; }
	}
	$ok = $doc->save( $data );
	if ( $cambiado ) { PLL()->curlang = $antes; }
	/* Document::save() devuelve false sin usuario; Loop::save() de Pro devuelve null aunque guarde:
	   por eso se compara con false, nunca con !$ok */
	if ( false === $ok ) { WP_CLI::error( "save() falló en #$id. ¿Ejecutaste con --user=1?" ); }
	Plugin::$instance->files_manager->clear_cache();   // el CSS se regenera en la siguiente visita
	$saved = json_decode( (string) get_post_meta( $id, '_elementor_data', true ), true );
	if ( ! is_array( $saved ) ) { WP_CLI::error( "No se pudo releer _elementor_data de #$id." ); }
	echo "documento #$id — secciones: " . count( $saved ) . '   elementos: ' . $e4_contar( $saved ) . "\n";
	foreach ( $saved as $sec ) {
		echo '  · ' . ( $sec['editor_settings']['title'] ?? $sec['elType'] ) . ' (' . $e4_contar( $sec['elements'] ?? [] ) . ")\n";
	}
	return $saved;
};

/* Plantilla de Theme Builder o Loop Item: la reutiliza por título + tipo o la crea publicada
   (la caché de condiciones solo incluye plantillas publicadas). 'lang' => '' explícito: en CLI
   Polylang (PLL_Admin) no filtra get_posts por idioma, pero en ajax o frontend sí, y entonces la
   búsqueda no vería la plantilla del otro idioma y el script la duplicaría (gotchas-cli.md A9).
   $tipo: 'header' | 'footer' | 'single-post' | 'loop-item' | 'error-404' …
   $meta: p. ej. [ '_elementor_template_sub_type' => '<cpt>' ] para un single de CPT. */
$e4_plantilla = function ( $tipo, $titulo, array $meta = [] ) {
	$found = get_posts( [ 'post_type' => 'elementor_library', 'post_status' => 'any', 'title' => $titulo, 'numberposts' => 1, 'fields' => 'ids',
		'meta_key' => '_elementor_template_type', 'meta_value' => $tipo, 'lang' => '' ] );
	if ( $found ) { echo "reutilizo plantilla «{$titulo}» #{$found[0]}\n"; return (int) $found[0]; }
	$doc = Plugin::$instance->documents->create( $tipo, [ 'post_title' => $titulo, 'post_status' => 'publish' ], $meta );
	if ( is_wp_error( $doc ) || ! $doc ) { WP_CLI::error( "No se pudo crear «{$titulo}» ($tipo)." ); }
	echo "plantilla creada «{$titulo}» #" . $doc->get_main_id() . "\n";
	return (int) $doc->get_main_id();
};

/* Condiciones del Theme Builder. Desde CLI no se guardan solas. Array de arrays, no cadenas:
   [ [ 'type' => 'include', 'name' => 'general' ] ]
   [ [ 'type' => 'include', 'name' => 'singular', 'sub_name' => '<cpt>', 'sub_id' => '' ] ]
   Un false al re-ejecutar significa «sin cambios», no error. */
$e4_condiciones = function ( int $id, array $conds ) {
	if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) { WP_CLI::error( 'Elementor Pro no está cargado.' ); }
	\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->save_conditions( $id, $conds );
	echo "condiciones #$id: " . wp_json_encode( get_post_meta( $id, '_elementor_conditions', true ) ) . "\n";
};
