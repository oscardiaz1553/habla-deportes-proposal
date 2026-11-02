# Theme Builder de Elementor Pro por WP-CLI

Cabecera, pie, single de un CPT, tarjetas (loop items), 404 y la cortina de
«Próximamente», construidos por script con árboles V4. Verificado con Elementor Pro
**4.2.3** sobre Elementor 4.3.0, Hello Elementor 3.5.1 y Polylang Pro 3.8.6.

Todo lo que hace el panel del Theme Builder por su cuenta (asignar condiciones,
regenerar su caché, elegir la plantilla del idioma) **hay que hacerlo a mano desde
CLI**. Las trampas con su arreglo, en `references/gotchas-cli.md` (grupo T); el formato
de los árboles, en `references/modelo-de-datos.md`.

---

## 1. Tipos de documento

| Plantilla | Tipo para `documents->create()` | Meta extra al crear | Condición | Ajustes del documento |
|---|---|---|---|---|
| Cabecera | `header` | — | `include/general` | — |
| Pie | `footer` | — | `include/general` | — |
| Single de un CPT | `single-post` | `[ '_elementor_template_sub_type' => '<cpt>' ]` | `include/singular/<cpt>` | `preview_type = 'single/<cpt>'`, `preview_id` de una entrada real; sin `page_template` |
| Tarjeta de un listado | `loop-item` | — | ninguna (la usa el Loop Grid por `template_id`) | `source = 'post'`, `preview_type`, `preview_id` |
| Página 404 | `error-404` | `[ '_elementor_template_sub_type' => 'not_found404' ]` | `include/singular/not_found404` | `preview_type = 'page/404'`; sin `page_template` |
| «Próximamente» / mantenimiento | `page` | — | ninguna (la elige una opción) | `template = 'elementor_canvas'` |

- Todas se crean **publicadas**: la caché de condiciones solo incluye plantillas
  publicadas [T3].
- Cada plantilla es un post `elementor_library` con `_elementor_template_type` = su
  tipo y el término `elementor_library_type` correspondiente (lo asigna el primer
  `save()`, que exige `--user=1`) [B1].
- Nunca `elementor_canvas` en singles ni en la 404 (pierden cabecera y pie), ni
  «Elementor ancho completo» (dos `<main>`): Diseño «Predeterminado» [T6].
- La clave del Diseño depende del tipo de documento. En las plantillas del Theme Builder
  (single, 404) es el ajuste `page_template` de `_elementor_page_settings`, que lee
  `Locations_Manager::template_include()` de Pro. En un documento de tipo `page` (la
  cortina, sección 10) es `template`, y Elementor lo guarda en `_wp_page_template`.

---

## 2. Crear o reutilizar, y guardar

Patrón idempotente probado: busca por título **y** tipo, en todos los idiomas; si no
existe, crea.

```php
use Elementor\Plugin;

$doc_por_titulo = function ( $type, $title, $meta = [] ) {
	$found = get_posts( [ 'post_type' => 'elementor_library', 'post_status' => 'any', 'title' => $title, 'numberposts' => 1, 'fields' => 'ids',
		'meta_key' => '_elementor_template_type', 'meta_value' => $type, 'lang' => '' ] );
	if ( $found ) { return (int) $found[0]; }
	$doc = Plugin::$instance->documents->create( $type, [ 'post_title' => $title, 'post_status' => 'publish' ], $meta );
	if ( is_wp_error( $doc ) || ! $doc ) { WP_CLI::error( "No se pudo crear $title" ); }
	return (int) $doc->get_main_id();
};

$guardar = function ( $id, $elements, $settings = null ) {
	$doc  = Plugin::$instance->documents->get( $id, false );
	$data = [ 'elements' => $elements ];
	if ( null !== $settings ) {
		/* un meta vacío es '' y (array) '' deja una clave 0 basura en los ajustes */
		$previos = $doc->get_meta( '_elementor_page_settings' );
		$previos = is_array( $previos ) ? $previos : [];
		unset( $previos[0] );
		$data['settings'] = array_merge( $previos, $settings );
	}
	if ( false === $doc->save( $data ) ) { WP_CLI::error( "save() falló en #$id" ); }   // Loop::save() devuelve null
};
```

- Con Polylang, si la plantilla tiene idioma, cambia `PLL()->curlang` al suyo durante el
  `save()`: Elementor pinta el documento al guardar para generar su texto plano [B6].
- Tras guardar: `Plugin::$instance->files_manager->clear_cache()` y un informe con el
  árbol (tipo y clases de cada elemento) y las condiciones.
- Imprime «reutilizo plantilla #ID» o «plantilla creada #ID»: en la segunda ejecución
  debe salir lo primero [T8].
- En `scripts/e4-helpers.php` estos pasos son `$e4_plantilla( $tipo, $titulo, $meta )`,
  `$e4_guardar( $id, $arbol, $ajustes )` y `$e4_condiciones( $id, $condiciones )`.

---

## 3. Condiciones

### Formato

```php
use ElementorPro\Modules\ThemeBuilder\Module as Theme_Builder;

$cm = Theme_Builder::instance()->get_conditions_manager();
$cm->save_conditions( $id, [ [ 'type' => 'include', 'name' => 'general' ] ] );
$cm->save_conditions( $id, [ [ 'type' => 'include', 'name' => 'singular', 'sub_name' => '<cpt>', 'sub_id' => '' ] ] );
$cm->save_conditions( $id, [ [ 'type' => 'include', 'name' => 'singular', 'sub_name' => 'not_found404', 'sub_id' => '' ] ] );
$cm->save_conditions( $id, [] );   // quita todas las condiciones de una plantilla
```

- Una **lista de arrays**, nunca una cadena: con `[ 'include/general' ]` sale
  `Fatal error: Cannot unset string offsets` [T1]. El método une las claves con `/` y
  quita la barra final.
- Se guarda en la meta `_elementor_conditions` de la plantilla (`["include/general"]`) y
  regenera la opción `elementor_pro_theme_builder_conditions`, con esta forma:
  ```json
  {"single":{"<id_404>":["include/singular/not_found404"],"<id_single>":["include/singular/<cpt>"]},"footer":{"<id_pie>":["include/general"]},"header":{"<id_cabecera>":["include/general"]}}
  ```
- `save_conditions()` devuelve `false` si no hubo cambios: no es un error [T4].
- `singular/<cpt>` solo existe si el CPT es público y `show_in_nav_menus` [T3].

### Desde CLI la condición no se pone sola

`Archive_Single_Base::save_template_type()` solo guarda la condición del subtipo si
llega `$_REQUEST['_elementor_template_sub_type']`, cosa que en CLI nunca pasa. No la
simules: si existiera, **cada** `save()` grabaría la condición, también en la copia del
segundo idioma. Al principio de los scripts de single y 404:
```php
unset( $_REQUEST['_elementor_template_sub_type'], $_GET['_elementor_template_sub_type'], $_POST['_elementor_template_sub_type'] );
```
y guarda la condición a mano, **solo en la plantilla del idioma base** [T2].

### Prioridad

`Conditions_Manager::get_condition_priority()` da a `include/singular/not_found404`
prioridad **5** (la más específica: min(60, 20) − 10 − 5), así que no choca con un
`include/singular/<cpt>`: en un 404 la condición `Singular` pasa, pero la del CPT
(`is_singular( '<cpt>' )`) no.

### La caché de condiciones

- Opción `elementor_pro_theme_builder_conditions`. Solo lista plantillas publicadas.
- Con Polylang, la regeneración desde el editor consulta con el idioma del ajax y puede
  perder las plantillas del otro idioma. En el tema hijo [T5]:
  ```php
  add_filter( 'elementor/theme/conditions/cache/regenerate/query_args', function ( $args ) {
  	$args['lang'] = '';
  	return $args;
  } );
  ```
- Reconstrucción manual: `wp elementor-pro theme-builder clear_conditions` (responde
  «Template conditions cache is cleared.»). Obligatoria después de migrar.
- Comprobación: `wp option get elementor_pro_theme_builder_conditions --format=json`.

---

## 4. Cabecera

Estructura que cuadró al píxel con el prototipo en 1440, 1100, 900 y 375:

```
e-flexbox  <PREFIJO>-header                 «Barra»          (banda de color, padding 0)
└─ e-flexbox  <PREFIJO>-container <PREFIJO>-header__row   «Fila»
   ├─ e-flexbox  <PREFIJO>-header__brand + link a la portada   «Marca»
   │  └─ e-image  <PREFIJO>-header__logo                        «Logo»
   ├─ nav-menu (V3)  _css_classes <PREFIJO>-nav, _element_width auto
   └─ e-flexbox  <PREFIJO>-header__actions <PREFIJO>-solo-escritorio   «Acciones»
      ├─ shortcode (V3)  [<PREFIJO>_idioma], _element_width auto
      └─ e-button  <PREFIJO>-btn-nav + link a contacto
```

- El enlace del logo va en el `e-flexbox` que lo envuelve (que se pinta como `<a>`), no
  en el `e-image`: así quedó en el caso de referencia tras un script de ajuste.
- `nav-menu` (settings verbatim en `references/modelo-de-datos.md` §9): menú **por slug**
  [V2], `dropdown => 'tablet_extra'` (nunca `laptop`) [K3], `full_width => 'stretch'`,
  `toggle => 'burger'`, `pointer => 'none'`. Su `menu_name` es el `aria-label` del
  `<nav>`: tradúcelo en el segundo idioma [V6].
- La clase de visibilidad (`<PREFIJO>-solo-escritorio`, variante `tablet_extra` con
  `display:none`) va en el contenedor de acciones, no en cada hijo [D8].
- Lo que el prototipo añade al final del menú móvil (CTA, fila de idioma) se inyecta
  en la copia `dropdown` del menú con `wp_nav_menu_items`, normalizando `$args->menu`
  [V3] [V4]. Código en `references/tema-hijo.md`.
- El menú clásico se estiliza desde el hijo con selectores de alta especificidad; los
  widgets V3 heredan la tipografía de sistema del kit [K4].
- Si el logo se lleva la prioridad de carga de la página, márcalo con
  `fetchpriority="auto"` como atributo del widget (skill `wp-seo-rendimiento`).
- Condición `include/general` solo en la plantilla del idioma base.

## 5. Pie

```
e-flexbox  <PREFIJO>-footer                    «Banda»
└─ e-div-block  <PREFIJO>-container <PREFIJO>-footer__inner   «Contenido»
   ├─ e-grid  <PREFIJO>-footer__grid            (p. ej. '1.4fr repeat(3, 1fr)'; tablet '1fr')
   │  ├─ columna de marca (e-image + textos)
   │  └─ columnas con título + nav-menu (V3) vertical
   └─ e-flexbox  <PREFIJO>-footer__bottom       «Barra inferior»
      └─ text-editor (V3)  «© [<PREFIJO>_anio] Marca…»
```

- Rejilla con `grid-template-rows: auto` (la base trae dos filas y se come la mitad del
  alto) y variante `mobile` explícita [D4] [D5].
- `nav-menu` vertical: `'layout' => 'vertical'`, `'dropdown' => 'none'`, `'toggle' => ''`,
  `'submenu_icon' => [ 'value' => '', 'library' => '' ]`.
- Menús del pie con nombres que se saneen exactamente a su slug; documenta en la
  entrega que no se renombren [V2].
- El año va con un shortcode dentro del `text-editor` (que ejecuta shortcodes), así el
  cliente edita el resto del texto.

## 6. Single de un CPT

```php
$ficha = $doc_por_titulo( 'single-post', '<Tipo> — ficha', [ '_elementor_template_sub_type' => '<cpt>' ] );
$guardar( $ficha, [ $arbol_ficha ], [ 'preview_type' => 'single/<cpt>', 'preview_id' => (string) $id_de_una_entrada ] );
Theme_Builder::instance()->get_conditions_manager()
	->save_conditions( $ficha, [ [ 'type' => 'include', 'name' => 'singular', 'sub_name' => '<cpt>', 'sub_id' => '' ] ] );
```
- `preview_type` y `preview_id` hacen que el editor muestre datos reales.
- Diseño «Predeterminado»; la raíz del árbol, `<section>`: el tema hijo pone el `<main>`
  en `elementor/theme/before_do_single` (`references/tema-hijo.md`).
- Las props dinámicas (título, imagen destacada, campos) exigen el preámbulo de CLI [X1];
  formas y límites en `references/contenido-dinamico.md`.
- La ficha no pasa por `the_content`: `fetchpriority="high"` en la imagen principal y
  `loading="lazy"` en las relacionadas, como atributos del widget [T7].
- Con Polylang, una sola ficha sirve los dos idiomas con sus textos fijos en el idioma
  base, salvo que `elementor_library` sea traducible (sección 9).

## 7. Tarjetas y loop items

```php
$tarjeta = $doc_por_titulo( 'loop-item', '<Tipo> — tarjeta' );
$guardar( $tarjeta, [ $arbol_tarjeta ], [ 'source' => 'post', 'preview_type' => 'single/<cpt>', 'preview_id' => (string) $id_de_una_entrada ] );
```
- `source` es `'post'`, no el nombre del CPT: lo que ata el listado al CPT es
  `post_query_post_type` del Loop Grid.
- `Loop::save()` devuelve `null` aunque guarde: comprueba con `false ===` [B1].
- Sin condiciones: el Loop Grid la usa por `template_id` (numérico, como cadena).
  En el segundo idioma, el Loop Grid traducido apunta a la tarjeta traducida
  (`settings.template_id`).
- Enlace de la tarjeta: un `e-flexbox` con `link` a `post-url` se pinta como `<a>`; nada
  enlazado dentro [E10].
- Las tarjetas tampoco pasan por `the_content`: `loading="lazy"` como atributo [T7].
- Loop Grid, filtro por taxonomía, Query ID y accesibilidad del listado:
  `references/contenido-dinamico.md`.

---

## 8. Página 404

### Cómo se pinta (Hello + Theme Builder)

1. En un 404, `Locations_Manager::template_include()` usa la ubicación `single`. Hello
   registra las ubicaciones del núcleo y no cambia el archivo de plantilla; como no hay
   `404.php` ni en Hello ni en el hijo, WordPress usa `hello-elementor/index.php`, que
   llama a `elementor_theme_do_location( 'single' )`.
2. Esa llamada dispara `elementor/theme/before_do_single` y `after_do_single`, donde el
   hijo abre y cierra `<main id="content">`: un solo `<main>`, siempre que el Diseño de
   la plantilla sea «Predeterminado» [T6].
3. El estado HTTP sigue en **404**: `WP::handle_404()` lo fija antes de
   `template_redirect` y ni Elementor Pro ni Hello lo tocan.
4. El `<body>` conserva `error404` y suma `elementor-page-<id>` con el id de la
   plantilla pintada.
5. Sin plantilla, Hello pinta un párrafo sin `<h1>` (con
   `hello_elementor_settings_page_title` activo) y sin salidas útiles.

### Receta (patrón probado en el caso de referencia, generalizado aquí)

Estado: **generalizado, no probado fuera de HomeMaker**. Requiere los constructores V4
(`$flex`, `$h`, `$p`, `$btn` y el contador `$__n`) de `scripts/e4-helpers.php` y, si hay
segundo idioma, `elementor_library` traducible (sección 9).

```php
<?php
/**
 * Página 404 (Theme Builder › Error 404) en <IDIOMA_BASE> y <IDIOMA_2>.
 * Uso: wp --path=<WP_PATH> --user=1 eval-file 404-build.php   (junto a los helpers)
 * Idempotente: si las plantillas existen, rehace su contenido.
 * La condición include/singular/not_found404 va SOLO en la del idioma base; en el
 * segundo idioma la elige el filtro elementor/theme/get_location_templates/template_id.
 */
use Elementor\Plugin;
use ElementorPro\Modules\ThemeBuilder\Module as Theme_Builder;

if ( ! function_exists( 'pll_set_post_language' ) || ! pll_is_translated_post_type( 'elementor_library' ) ) {
	WP_CLI::error( 'Polylang no está cargado o elementor_library no es traducible.' );
}
unset( $_REQUEST['_elementor_template_sub_type'], $_GET['_elementor_template_sub_type'], $_POST['_elementor_template_sub_type'] );

$E4_SEED = 'error404';
require __DIR__ . '/e4-helpers.php';

$T = [
	'<IDIOMA_BASE>' => [ 'doc' => 'Página 404', 'kicker' => 'Error 404', 'h1' => 'Esta página no<strong> existe.</strong>',
		'texto' => 'Puede que el enlace esté roto o que la página se haya mudado.', 'btn' => [ 'Volver al inicio', '/' ] ],
	'<IDIOMA_2>'    => [ 'doc' => 'Página 404 (<IDIOMA_2>)', 'kicker' => 'Error 404', 'h1' => 'This page doesn’t<strong> exist.</strong>',
		'texto' => 'The link may be broken or the page may have moved.', 'btn' => [ 'Back to home', '/<IDIOMA_2>/' ] ],
];

/* Mismo esqueleto en los dos idiomas. La raíz es <section>, nunca <main>. */
$arbol = function ( array $t ) use ( $flex, $h, $p, $btn ) {
	return [
		$flex( [ '<PREFIJO>-banda' ], [
			$flex( [ '<PREFIJO>-container', '<PREFIJO>-col' ], [
				$p( [ '<PREFIJO>-kicker' ], $t['kicker'] ),
				$h( 'h1', [ '<PREFIJO>-display-xl' ], $t['h1'] ),
				$p( [ '<PREFIJO>-lead' ], $t['texto'] ),
				$btn( [ '<PREFIJO>-btn' ], $t['btn'][0], [ 'link' => $t['btn'][1] ] ),
			], [ 'title' => 'Texto' ] ),
		], [ 'tag' => 'section', 'title' => 'No encontrada' ] ),
	];
};

$buscar = function ( string $lang ) {
	$ids = get_posts( [ 'post_type' => 'elementor_library', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids',
		'meta_key' => '_elementor_template_type', 'meta_value' => 'error-404', 'lang' => $lang, 'orderby' => 'ID', 'order' => 'ASC' ] );
	return $ids ? (int) $ids[0] : 0;
};
$crear = function ( string $title ) {
	$doc = Plugin::$instance->documents->create( 'error-404',
		[ 'post_title' => $title, 'post_status' => 'publish' ],
		[ '_elementor_template_sub_type' => 'not_found404' ] );
	if ( is_wp_error( $doc ) || ! $doc ) { WP_CLI::error( "No se pudo crear «{$title}»" ); }
	return (int) $doc->get_main_id();
};

$base = $buscar( '<IDIOMA_BASE>' ) ?: $crear( $T['<IDIOMA_BASE>']['doc'] );
if ( '<IDIOMA_BASE>' !== pll_get_post_language( $base ) ) { pll_set_post_language( $base, '<IDIOMA_BASE>' ); }
$otro = (int) pll_get_post( $base, '<IDIOMA_2>' );
if ( ! $otro ) { $otro = $crear( $T['<IDIOMA_2>']['doc'] ); }   // Polylang le pone el idioma base al insertarla…
if ( '<IDIOMA_2>' !== pll_get_post_language( $otro ) ) { pll_set_post_language( $otro, '<IDIOMA_2>' ); }   // …y aquí se corrige
pll_save_post_translations( [ '<IDIOMA_BASE>' => $base, '<IDIOMA_2>' => $otro ] );

$guardar = function ( int $id, string $lang, array $elements ) {
	$doc = Plugin::$instance->documents->get( $id, false );
	if ( ! $doc ) { WP_CLI::error( "Elementor no abre #$id" ); }
	$ajustes = $doc->get_meta( '_elementor_page_settings' );
	$ajustes = is_array( $ajustes ) ? $ajustes : [];
	unset( $ajustes[0], $ajustes['page_template'] );   // Diseño «Predeterminado»
	$ajustes['preview_type'] = 'page/404';
	$antes = PLL()->curlang;
	PLL()->curlang = PLL()->model->get_language( $lang );
	$ok = $doc->save( [ 'elements' => $elements, 'settings' => $ajustes ] );
	PLL()->curlang = $antes;
	if ( false === $ok ) { WP_CLI::error( "save() falló en #$id" ); }
};
$__n = 0; $guardar( $base, '<IDIOMA_BASE>', $arbol( $T['<IDIOMA_BASE>'] ) );
$__n = 0; $guardar( $otro, '<IDIOMA_2>', $arbol( $T['<IDIOMA_2>'] ) );   // mismos ids de elemento

$cm = Theme_Builder::instance()->get_conditions_manager();
$cm->save_conditions( $base, [ [ 'type' => 'include', 'name' => 'singular', 'sub_name' => 'not_found404', 'sub_id' => '' ] ] );
if ( get_post_meta( $otro, '_elementor_conditions', true ) ) { $cm->save_conditions( $otro, [] ); }
Plugin::$instance->files_manager->clear_cache();

echo "base #$base · segundo idioma #$otro\n";
echo 'tipo/término: ' . get_post_meta( $base, '_elementor_template_type', true ) . ' / '
	. implode( ',', wp_get_object_terms( $base, 'elementor_library_type', [ 'fields' => 'slugs' ] ) )
	. ' · sub: ' . get_post_meta( $base, '_elementor_template_sub_type', true ) . "\n";
echo 'condiciones: ' . wp_json_encode( get_post_meta( $base, '_elementor_conditions', true ) )
	. ' · segundo idioma: ' . wp_json_encode( get_post_meta( $otro, '_elementor_conditions', true ) ) . "\n";
$ps = get_post_meta( $base, '_elementor_page_settings', true );
echo 'Diseño (page_template): ' . ( is_array( $ps ) && ! empty( $ps['page_template'] ) ? $ps['page_template'] : 'predeterminado' ) . "\n";
echo 'caché: ' . wp_json_encode( get_option( 'elementor_pro_theme_builder_conditions' ) ) . "\n";
```

Salida esperada: `tipo/término: error-404 / error-404 · sub: not_found404`,
`condiciones: ["include/singular/not_found404"] · segundo idioma: ""`,
`Diseño (page_template): predeterminado` y, en la caché, **solo el id del idioma base**
dentro de `single`.

Notas de la receta:
- El contador `$__n` se reinicia antes de cada idioma: la copia tiene los mismos ids de
  elemento que la original (lo necesitan los mapas de traducción por id).
- Las rutas de los botones del segundo idioma van explícitas (`/<IDIOMA_2>/…`): el
  constructor de enlaces resuelve con `home_url( $ruta )`.
- El script quita `page_template` de los ajustes porque en una plantilla del Theme
  Builder esa es la clave del Diseño (sección 1): si alguien eligió «Elementor ancho
  completo» en el editor, al re-ejecutar vuelve a «Predeterminado». No uses aquí
  `template`, que es la clave de los documentos de tipo `page` (la cortina, sección 10).
  Lo verificado en el caso es el resultado: un solo `<main>`.
- Si la 404 no usa props dinámicas, no necesita el preámbulo de CLI [X1]; déjalo
  comentado.

### Diseño y contexto

- La 404 es también la cara del acceso bloqueado: con una URL de acceso propia
  (`/<LOGIN_SLUG>`), un plugin de seguridad como SG Security manda `wp-login.php`,
  `/wp-admin/` y atajos del núcleo a `home_url( '/404' )`, **siempre en el idioma
  base**. Diseña salidas útiles (inicio, secciones clave), no menciones el acceso y
  publícala antes o a la vez que la URL de acceso propia.
- El título SEO del 404 (Yoast `title-404-wpseo`) se traduce aparte como cadena de
  Polylang (skill `wp-polylang`).
- En SiteGround los 404 no se cachean (`no-store`, `x-proxy-cache: MISS`): no hace falta
  purgar para ver el cambio.

### Verificación

```bash
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36'
for u in /no-existe-xq/ /<IDIOMA_2>/does-not-exist-xq/; do
  F=$(mktemp); curl -s -A "$UA" -o "$F" -w "%{http_code} $u\n" "https://<DOMINIO>$u"
  grep -oE 'data-elementor-type="error-404" data-elementor-id="[0-9]+"|<html[^>]*lang="[^"]+"' "$F"
  grep -c '<main' "$F"   # 1
done
```
Esperado: **404**, la plantilla de su idioma, `lang` correcto y un solo `<main>`. Slugs
absurdos: uno parecido a una página real acaba en 301 por
`redirect_guess_404_permalink` [M6].

---

## 9. Plantillas por idioma (resumen)

El detalle, los scripts de generación del segundo idioma y las cadenas están en la
skill `wp-polylang`. Lo que afecta al Theme Builder:

1. **Decídelo antes de maquetar**: convertir después una plantilla única en una por
   idioma es rehacer.
2. `elementor_library` traducible, en el **nivel superior** del archivo del hijo (no
   dentro de `init`: Polylang cachea la lista antes):
   `add_filter( 'pll_get_post_types', function ( $t ) { $t['elementor_library'] = 'elementor_library'; return $t; } );`
3. Cada plantilla del idioma base con su idioma asignado por script, y su traducción
   enlazada con `pll_save_post_translations`.
4. **Condiciones solo en la plantilla del idioma base.** La del segundo idioma la elige
   el filtro que Elementor Pro expone (pensado para WPML),
   `elementor/theme/get_location_templates/template_id`, que se aplica a cada id de la
   caché **antes** de evaluar la condición: devuelve `pll_get_post( $template_id )` solo
   si la traducción está publicada (un borrador no se sirve). Código en la skill
   `wp-polylang`, `references/polylang-elementor.md`.
5. Regeneración de la caché con `'lang' => ''` (sección 3) [T5].
6. El kit de Elementor **sin idioma**: sácalo del aviso de Polylang con
   `pll_get_objects_with_no_lang` y nunca pulses su botón de «asignar el idioma por
   defecto a todo».
7. Al traducir con el «+» de Polylang, Elementor copia todas sus metas: en la copia hay
   que borrar las cachés (`_elementor_css`, `_elementor_element_cache`,
   `_elementor_page_assets`), `_elementor_conditions` y los índices de clases V4
   (`_elementor_used_global_class`, `_elementor_used_global_class_preview`,
   `_elementor_global_class_usage_indexed`, `_elementor_global_class_usage_indexed_preview`)
   con el filtro `pll_copy_post_metas` (prioridad 20, 4 argumentos). Código en la skill
   `wp-polylang`, `references/polylang-elementor.md`.
8. Guarda cada documento con `PLL()->curlang` en su idioma [B6] y no edites a mano las
   copias generadas: el generador las rehace desde el idioma base.
9. Los menús van por slug y por idioma [V2]; los enlaces absolutos del árbol se
   reescriben al prefijo del segundo idioma.

Verificación: `curl` de `/` y `/<IDIOMA_2>/` y compara el `data-elementor-id` de
cabecera, pie y 404; la caché de condiciones solo contiene ids del idioma base.

---

## 10. Modo mantenimiento y «Próximamente»

Elementor trae un modo mantenimiento que sirve una plantilla a quien no tiene sesión.
Opciones:

| Opción | Valores |
|---|---|
| `elementor_maintenance_mode_mode` | `''` (apagado) · `coming_soon` · `maintenance` |
| `elementor_maintenance_mode_exclude_mode` | `logged_in` |
| `elementor_maintenance_mode_template_id` | id de la plantilla (idioma base) |

**Qué responde cada modo** [T11]:
- `coming_soon`: **200 + noindex en todas las URL**, incluido `robots.txt`, que sale
  como HTML. Los sitemaps de Yoast y `/wp-json/` siguen respondiendo.
- `maintenance`: **503 + `Retry-After: 600`**.
- Por entorno: en staging (con `blog_public=0`), `coming_soon` vale. En un dominio
  público con historial, solo 503 (`maintenance` o una cortina estática) y el menor
  tiempo posible: Google tolera días, no semanas. La decisión de encenderla y cuándo es
  del usuario y se coordina con la sesión de SEO: con la cortina puesta, PageSpeed y
  Lighthouse solo miden la cortina. Migración y lanzamiento: skill `wp-migracion`.

**La plantilla** (patrón probado):
- Tipo `page`, creada con `documents->create( 'page', [ 'post_title' => 'Próximamente', 'post_name' => 'proximamente', 'post_status' => 'publish' ] )`
  y guardada con `save( [ 'settings' => [ 'template' => 'elementor_canvas' ], 'elements' => [ $arbol ] ] )`:
  queda con `_wp_page_template = elementor_canvas`, sin cabecera ni pie.
- El lienzo no tiene `<main>`: la raíz es `section` con el atributo `role="main"`.
  `e-background-video` no admite `tag = main` [D12].
- Copia en el segundo idioma enlazada por Polylang; el script copia a la traducción
  `_elementor_template_type`, `_elementor_edit_mode`, `_elementor_version` y el término
  `elementor_library_type`, y guarda cada una con su `PLL()->curlang`.
- El script fija `elementor_maintenance_mode_template_id` y, si está vacío,
  `exclude_mode = logged_in`, pero **nunca enciende el modo**.

**En el tema hijo** (patrón probado; el prefijo del parámetro es del proyecto):
```php
/* En el segundo idioma, su traducción: Elementor busca la plantilla con una consulta
   que Polylang filtra por idioma, y con la del idioma base saldría en blanco. */
add_filter( 'option_elementor_maintenance_mode_template_id', function ( $id ) {
	if ( is_admin() || ! $id || ! function_exists( 'pll_get_post' ) ) {
		return $id;
	}
	$tr = pll_get_post( (int) $id );
	return ( $tr && 'publish' === get_post_status( $tr ) ) ? $tr : $id;
} );

/* La cortina no se indexa. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( ! is_singular( 'elementor_library' ) || ! class_exists( '\Elementor\Maintenance_Mode' ) ) {
		return $robots;
	}
	$plantilla = (int) get_option( 'elementor_maintenance_mode_template_id' );
	if ( $plantilla && (int) get_queried_object_id() === $plantilla ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

/* Vista previa sin encender el modo: /?<PREFIJO>-vista=proximamente (y /<IDIOMA_2>/?…).
   Mismo mecanismo que Elementor (Maintenance_Mode::template_redirect, prioridad 11). */
add_action( 'template_redirect', function () {
	if ( 'proximamente' !== ( $_GET['<PREFIJO>-vista'] ?? '' ) ) {
		return;
	}
	$id = (int) get_option( 'elementor_maintenance_mode_template_id' );
	if ( ! $id || 'publish' !== get_post_status( $id ) ) {
		return;
	}
	$GLOBALS['post'] = get_post( $id );
	query_posts( [ 'p' => $id, 'post_type' => 'elementor_library' ] );
}, 11 );
```
`?elementor_library=<slug>` no sirve como vista previa pública: redirige con 301 a la
portada a quien no puede editar [T9].

**Verificación**:
- Apagada: `wp option get elementor_maintenance_mode_mode` vacío.
- Vista previa: `curl "https://<STAGING_URL>/?<PREFIJO>-vista=proximamente"` y la del
  segundo idioma → 200, `elementor-template-canvas`, `lang` correcto,
  `robots` con `noindex, nofollow`, sin `elementor-location-header`; mídela contra el
  prototipo en 1440 y 375.
- Encendida en `maintenance`: `curl -sI https://<DOMINIO>/` → `503` y `retry-after`;
  `/` y `/<IDIOMA_2>/` con su plantilla.
- Para ver el sitio real detrás de la cortina sin sesión en el navegador, se puede pintar
  una URL desde WP-CLI (skill `wp-seo-rendimiento`).

---

## 11. Verificación general del Theme Builder

```bash
# plantillas activas y sus condiciones
wp --path=<WP_PATH> option get elementor_pro_theme_builder_conditions --format=json
# cada página lleva cabecera y pie de su idioma
for u in / /<IDIOMA_2>/; do
  curl -s "https://<DOMINIO>$u?nocache=$(date +%s)" | grep -oE 'data-elementor-type="(header|footer)" data-elementor-id="[0-9]+"'
done
# un solo <main> en páginas, singles y 404
curl -s "https://<DOMINIO>/<ruta>/" | grep -c '<main'
```
En el editor (prueba que hace el usuario si Claude no puede iniciar sesión [M4]):
abrir cabecera, pie, una ficha y la 404, comprobar que cargan con estilos y pulsar
«Actualizar» sin cambios; después, repetir la comprobación de clases en preview [C1].
