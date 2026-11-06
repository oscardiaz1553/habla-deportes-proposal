<?php
/**
 * e4-build-pagina.plantilla.php — Plantilla del <pagina>-build.php: construye o REEMPLAZA el árbol
 * completo de una página con elementos V4, lo guarda con Document::save() y limpia la caché de
 * CSS de Elementor (se regenera en la siguiente visita). Idempotente: re-ejecutarlo deja el mismo
 * documento con los mismos ids.
 *
 * PROPÓSITO
 *   Un script por página, reejecutable: la página se reescribe entera en cada ejecución con ids
 *   deterministas ($E4_SEED distinta por documento). Nada de plantillas importadas: todo son
 *   elementos V4 con clases globales, textos dentro de los widgets y títulos legibles en el
 *   Navegador del editor, para que el cliente edite sin tocar código.
 *
 * USO
 *   cp e4-build-pagina.plantilla.php <pagina>-build.php          # edita las secciones 1 y 2
 *   php -l <pagina>-build.php
 *   scp <pagina>-build.php e4-helpers.php <SSH_ALIAS>:tmp-<PREFIJO>/
 *   ssh <SSH_ALIAS> 'wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/<pagina>-build.php > ~/tmp-<PREFIJO>/salida.txt 2>&1; grep -i -m3 "fatal\|uncaught" ~/tmp-<PREFIJO>/salida.txt; tail -12 ~/tmp-<PREFIJO>/salida.txt'
 *   Después: purga la caché del hosting (SiteGround: wp sg purge; en cPanel/Apache:
 *   wp cache flush && wp elementor flush_css, con guion bajo: trampa A10) y mide contra el prototipo (medir-vs-prototipo.js).
 *   Orden por página: medios → <pagina>-classes.php (+ sync) → <pagina>-build.php → medir → arreglos.
 *
 * QUÉ EDITAR
 *   Sección 1: slug, título, idioma de la página (o su ID fijo) y la semilla.
 *   Sección 2: el árbol. Ids de adjuntos desde el JSON de medios (nunca a mano en el código).
 *   Sección 3 (opcional): ajustes del documento.
 *   Si el árbol usa etiquetas dinámicas, descomenta el require del preámbulo (primera línea).
 *
 * ESTADO
 *   Núcleo probado en HomeMaker (historia-, contacto-, 404-build.php): semilla, helpers,
 *   secciones como variables, guardado del árbol completo, resumen por sección. Búsqueda o alta de
 *   la página como pages-menu.php (edit_mode builder + plantilla elementor_header_footer).
 *   Generalizado, no probado fuera de HomeMaker: búsqueda por slug + idioma con 'lang' => '', la
 *   comprobación previa de clases inexistentes e ids repetidos (antes era check-clases.php, por
 *   regex sobre el código) y el árbol impreso tras guardar.
 *
 * RIESGOS
 *   - Reescribe TODO el contenido Elementor de la página: lo que el cliente haya editado en el
 *     editor se pierde. Úsalo en construcción; después, parches por id (e4-parche-por-id.plantilla.php).
 *   - La raíz del árbol son <section> (o div), NUNCA <main>: el tema hijo abre <main id="content">
 *     con los hooks de la plantilla «Elementor ancho completo»; dos <main> rompen el salto al contenido.
 *   - No guardes ids generados fuera del script: insertar un elemento desplaza los posteriores.
 *   - Si la página no tiene idioma y Polylang está activo, el script le asigna <IDIOMA_BASE>.
 */

/* require __DIR__ . '/e4-dynamic-tags-preambulo.php';   // SOLO si el árbol usa $$type 'dynamic' (debe ir primero) */

/* =====================================================================
 * 1. PÁGINA
 * ===================================================================== */
$PAGINA_ID     = 0;              // si ya conoces el ID, ponlo aquí y se ignora la búsqueda
$PAGINA_SLUG   = 'nosotros';     // slug en el idioma de la página
$PAGINA_TITULO = 'Nosotros';
$PAGINA_IDIOMA = 'es';           // <IDIOMA_BASE>; se ignora sin Polylang
$E4_SEED       = 'nosotros';     // una semilla distinta por documento

require __DIR__ . '/e4-helpers.php';

$pagina = function () use ( $PAGINA_ID, $PAGINA_SLUG, $PAGINA_TITULO, $PAGINA_IDIOMA ) {
	$pll = function_exists( 'pll_get_post_language' );
	if ( $PAGINA_ID ) {
		if ( 'page' !== get_post_type( $PAGINA_ID ) ) { WP_CLI::error( "#$PAGINA_ID no es una página." ); }
		return (int) $PAGINA_ID;
	}
	/* 'lang' => '' explícito: en CLI Polylang no filtra, pero en ajax o frontend sí (gotchas-cli.md A9);
	   el idioma se elige abajo con pll_get_post_language() */
	$ids = get_posts( [ 'post_type' => 'page', 'name' => $PAGINA_SLUG, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'lang' => '' ] );
	foreach ( $ids as $id ) {
		if ( ! $pll || pll_get_post_language( $id ) === $PAGINA_IDIOMA || ! pll_get_post_language( $id ) ) {
			echo "página existente «{$PAGINA_TITULO}» #$id\n";
			return (int) $id;
		}
	}
	$id = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $PAGINA_TITULO, 'post_name' => $PAGINA_SLUG ], true );
	if ( is_wp_error( $id ) ) { WP_CLI::error( $id ); }
	echo "página creada «{$PAGINA_TITULO}» #$id\n";
	return (int) $id;
};

/* =====================================================================
 * 2. ÁRBOL (sustituye el ejemplo). Clases «pfx-» = <PREFIJO>-.
 *    - Cada sección: flex|grid con 'tag' => 'section' y 'title' legible.
 *    - Acentos de color: <strong> dentro del texto + clase de acento en el widget.
 *    - Comportamiento (revelados, carruseles): atributos data-pfx-*, nunca clases de estilo.
 *    - Widget V3 (formulario, loop grid, nav-menu…): $v3( '<id 7 [a-z0-9]>', '<tipo>', [ …planos… ] )
 *      dentro de un $flex( [ 'pfx-caja', … ] ).
 * ===================================================================== */
$MEDIOS = [ 'banner' => 0 ];   // carga el mapa clave → id de adjunto: json_decode( file_get_contents( __DIR__ . '/media-ids.json' ), true )

$s_intro = $flex( [ 'pfx-banda', 'pfx-seccion' ], [
	$flex( [ 'pfx-container', 'pfx-col' ], [
		$p( [ 'pfx-kicker' ], 'Quiénes somos', [ 'attrs' => [ 'data-pfx-reveal' => 0 ] ] ),
		$h( 'h1', [ 'pfx-display-lg', 'pfx-acento', 'pfx-mt-16' ], 'Una marca que piensa<strong> en ti.</strong>', [ 'attrs' => [ 'data-pfx-reveal' => 120 ] ] ),
		$p( [ 'pfx-lead', 'pfx-mt-24' ], 'Texto de entrada copiado literalmente del prototipo.' ),
		$flex( [ 'pfx-botones', 'pfx-mt-24' ], [
			$btn( [ 'pfx-btn--sm', 'pfx-btn' ], 'Ver productos', [ 'link' => '/productos/' ] ),
		], [ 'title' => 'Botones' ] ),
	], [ 'title' => 'Texto' ] ),
], [ 'tag' => 'section', 'title' => 'Intro' ] );

$s_foto = $flex( [ 'pfx-banda' ], [
	$tam( $img( [ 'pfx-img-cubre' ], $MEDIOS['banner'], 'Descripción real de la foto' ), 'large' ),
], [ 'tag' => 'section', 'title' => 'Foto' ] );

$SECCIONES = [ $s_intro ];   // añade $s_foto cuando $MEDIOS tenga ids reales

/* =====================================================================
 * 3. AJUSTES DEL DOCUMENTO (opcional; se fusionan con los existentes)
 * ===================================================================== */
$AJUSTES = null;   // [ 'clave' => valor ]; mira las claves reales con: wp post meta get <ID> _elementor_page_settings --format=json

/* =====================================================================
 * 4. Comprobaciones previas, guardado y verificación (no editar)
 * ===================================================================== */
$repetidos = $e4_ids_repetidos( $SECCIONES );
if ( $repetidos ) { WP_CLI::error( 'Ids ausentes o repetidos: ' . implode( ', ', $repetidos ) ); }

$existentes = array_keys( \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->all( true )->get_items()->all() );
$faltan     = array_values( array_diff( $e4_clases_usadas( $SECCIONES ), $existentes ) );
if ( $faltan ) {
	WP_CLI::error( 'Faltan clases globales (ejecuta antes el <pagina>-classes.php): ' . implode( ', ', $faltan ) );
}

/* la página se busca o se crea solo cuando el árbol ya pasó las comprobaciones */
$ID = $pagina();
if ( 'builder' !== get_post_meta( $ID, '_elementor_edit_mode', true ) ) { update_post_meta( $ID, '_elementor_edit_mode', 'builder' ); }
if ( ! get_post_meta( $ID, '_wp_page_template', true ) || 'default' === get_post_meta( $ID, '_wp_page_template', true ) ) {
	update_post_meta( $ID, '_wp_page_template', 'elementor_header_footer' );   // «Elementor ancho completo»: cabecera y pie del Theme Builder
}
if ( function_exists( 'pll_set_post_language' ) && ! pll_get_post_language( $ID ) ) { pll_set_post_language( $ID, $PAGINA_IDIOMA ); }

$guardado = $e4_guardar( $ID, $SECCIONES, $AJUSTES );
if ( getenv( 'E4_ARBOL' ) ) { $e4_arbol( $guardado ); }   // E4_ARBOL=1 imprime tipo + clases de cada elemento
echo 'URL: ' . get_permalink( $ID ) . "\n";
