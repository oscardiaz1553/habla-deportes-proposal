<?php
/**
 * e4-dynamic-tags-preambulo.php — Preámbulo obligatorio para GUARDAR desde WP-CLI documentos V4
 * con etiquetas dinámicas ($$type 'dynamic'), más constructores de esos valores.
 *
 * PROPÓSITO
 *   WP-CLI cuenta como «frontend» para Elementor y el guardado de etiquetas dinámicas falla:
 *     Exception: Settings validation failed. title: invalid_value      (o image:, paragraph:…)
 *   Solo pasan las etiquetas sin settings (post-url). Cadena de causas (Elementor 4.3 / Pro 4.2.3):
 *     1. Performance::should_optimize_controls() es true fuera de admin, preview y REST, y eso
 *        quita el `label` de todos los controles.
 *     2. Dynamic_Tags_Editor_Config::convert_control_to_atomic descarta los tags cuyos controles no
 *        tienen label: en CLI quedan 9 de 38 en el registro atómico; en modo editor, 37 de 38.
 *     3. Dynamic_Prop_Type::validate_value no encuentra el tag y el save() lanza la excepción.
 *     4. post-terms, además, sale sin taxonomías porque en CLI no hay post actual.
 *   Este archivo devuelve el proceso al modo editor (solo en memoria, solo para esta ejecución).
 *   El render nunca usa el registro atómico: lo que se guarda bien se pinta bien.
 *
 * USO — PRIMERA línea ejecutable del script, antes de cualquier otro require o save():
 *   require __DIR__ . '/e4-dynamic-tags-preambulo.php';
 *   $E4_SEED = '<pagina>';
 *   require __DIR__ . '/e4-helpers.php';
 *   $titulo = $titulo_din( 'h3', [ 'pfx-tarjeta__titulo' ], $d_titulo );
 *   Ejecución: wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<script>.php
 *   Después de guardar: relee _elementor_data y confirma que el JSON contiene "$$type":"dynamic".
 *
 * QUÉ EDITAR
 *   Nada. Las claves de metas que pases a $d_campo() van SIN «_» inicial si quieres que Polylang
 *   las copie al traducir. La taxonomía de $d_terminos() es la tuya.
 *
 * ESTADO
 *   Probado en HomeMaker: las tres líneas del preámbulo, el filtro de post-terms y los
 *   constructores $dyn, $d_titulo, $d_url, $d_campo, $d_sc, $d_terminos (era $d_cat).
 *   Generalizado, no probado fuera de HomeMaker: $d_foto_destacada con respaldo (la forma del
 *   respaldo se probó en la investigación de solo lectura, no en los builders finales) y la
 *   llamada a setAccessible() solo en PHP < 8.1.
 *
 * RIESGOS
 *   - Toca una propiedad estática interna de Elementor por reflexión: si una actualización la
 *     renombra, el script falla aquí con un error claro, sin escribir nada. Revisa tras actualizar.
 *   - Formato exigido por Dynamic_Prop_Type::validate_value: name, group y settings; cada setting
 *     con su envoltorio $$type. Sin 'group' falla; las claves desconocidas se descartan al guardar.
 *   - Solo en settings de contenido. Nada dinámico en estilos (se compilan a CSS en caché).
 *   - post-custom-field solo pinta cadenas y no sirve como imagen. e-paragraph pasa el texto por
 *     striptags: listas o HTML rico van en un widget V3 'shortcode' con un shortcode propio.
 *   - Alternativa citada y no usada en el caso: escribir _elementor_data directamente (se salta la
 *     validación y el saneado).
 */

use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;

if ( ! class_exists( '\Elementor\Core\Frontend\Performance' ) || ! class_exists( '\Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module' ) ) {
	WP_CLI::error( 'Preámbulo: no encuentro Performance o Dynamic_Tags_Module en esta versión de Elementor. Revisa antes de guardar etiquetas dinámicas.' );
}

/* ---- el preámbulo (tres líneas + un filtro) ---- */
$e4_perf = new ReflectionProperty( \Elementor\Core\Frontend\Performance::class, 'is_frontend' );
if ( PHP_VERSION_ID < 80100 ) { $e4_perf->setAccessible( true ); }   // desde PHP 8.1 no hace falta (y en 8.5 está obsoleto)
$e4_perf->setValue( null, false );
\Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module::fresh();
add_filter( 'elementor_pro/dynamic_tags/post_terms/taxonomy_args', function ( $a ) {
	unset( $a['object_type'] );   // sin post actual, post-terms se queda sin taxonomías
	return $a;
} );

/* ---- constructores de valores dinámicos ---- */
$s   = fn( $v ) => String_Prop_Type::generate( (string) $v );
$dyn = fn( $name, $group, $settings = [] ) => [ '$$type' => 'dynamic', 'value' => [ 'name' => $name, 'group' => $group, 'settings' => $settings ] ];

$d_titulo = $dyn( 'post-title', 'post' );   // e-heading: settings['title']
$d_url    = $dyn( 'post-url', 'post' );     // destino de un link: $enlace_din( $el, $d_url )
/* e-paragraph: settings['paragraph'] */
$d_campo  = fn( $key ) => $dyn( 'post-custom-field', 'post', [ 'key' => $s( '' ), 'custom_key' => $s( $key ) ] );
$d_sc     = fn( $sc ) => $dyn( 'shortcode', 'site', [ 'shortcode' => $s( $sc ) ] );
$d_terminos = fn( $taxonomia, $sep = ', ' ) => $dyn( 'post-terms', 'post', [
	'taxonomy'  => $s( $taxonomia ),
	'separator' => $s( $sep ),
	'link'      => Boolean_Prop_Type::generate( false ),
] );
/* imagen destacada del post del loop, con adjunto de respaldo (0 = sin respaldo):
   uso: $foto_din( [ 'pfx-img-cubre' ], $d_foto_destacada( <ID_ADJUNTO> ), 'medium_large' ) */
$d_foto_destacada = function ( $fallback_id = 0 ) use ( $dyn ) {
	if ( ! $fallback_id ) { return $dyn( 'post-featured-image', 'post' ); }
	return $dyn( 'post-featured-image', 'post', [ 'fallback' => [ '$$type' => 'image', 'value' => [
		'src'  => [ '$$type' => 'image-src', 'value' => [ 'id' => [ '$$type' => 'image-attachment-id', 'value' => (int) $fallback_id ], 'url' => null ] ],
		'size' => [ '$$type' => 'string', 'value' => 'full' ],
	] ] ] );
};

echo "preámbulo de etiquetas dinámicas: modo editor activo para este proceso\n";
