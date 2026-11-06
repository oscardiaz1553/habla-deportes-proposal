# Contenido dinámico: CPT, Loop Grid, filtro por taxonomía y dynamic tags V4

Cómo montar contenido que el cliente edita como datos (un tipo de contenido propio)
y que Elementor pinta con plantillas: tarjeta (Loop Item), ficha (Theme Builder
«Individual») y listado filtrable (Loop Grid + Taxonomy Filter), todo desde WP-CLI.

Verificado sobre WP 7.1 · Elementor 4.3.0 · Elementor Pro 4.2.3 · Polylang Pro 3.8.6 ·
Yoast 27.7 · Hello 3.5.1. Las rutas `archivo.php:NN` de los plugins son de esas
versiones: los mecanismos se mantienen, los números de línea cambian.

**Convención de los fragmentos.** Son patrones genéricos: `pfx_` / `pfx-` / `PFX_` es el
`<PREFIJO>` (en el caso de referencia, HomeMaker, `hm`); `receta` y `categoria_receta` son el
CPT y la taxonomía de ejemplo; `/recetario/` es la página del listado. Renómbralos en cada
proyecto. El código real, con `hm`, está en
`../../wp-elementor-replica/ejemplos-homemaker/tema-hijo/homemaker-child/inc/recetas.php`.

## Lo primero, siempre

Decide esto **antes** de crear términos o contenido. Cambiarlo después obliga a
reimportar:

1. **La taxonomía del filtro va con guion bajo** (`categoria_receta`, nunca
   `categoria-receta`). Con guion, el filtro funciona por AJAX pero al recargar o al
   entrar por un enlace el listado sale sin filtrar (§4).
2. **El Loop Grid lleva un `id` fijo de exactamente 7 caracteres `[a-z0-9]`**
   (`recgrid`, `relgrid`). Lo pones tú en el JSON; minúsculas obligatorias (§4).
3. **Metas sin `_` inicial** (`pfx_tiempo`, no `_pfx_tiempo`): Polylang solo copia las
   no protegidas al crear la traducción (§2).
4. **¿Las plantillas necesitan versión por idioma?** Si hay segundo idioma, haz
   `elementor_library` traducible desde el principio; si no, la ficha única saldrá
   con sus textos fijos en el idioma base también en `/<IDIOMA_2>/`. Ver
   `../../wp-polylang/references/polylang-elementor.md`.
5. **Todo script que guarde props dinámicos empieza por el preámbulo del §6.** Sin
   él, `save()` revienta desde WP-CLI.

## 1. Qué va dónde

| Pieza | Dónde vive | Quién la edita |
|---|---|---|
| Datos (CPT, metas, taxonomía, panel de campos) | child, `inc/<dominio>.php` | cliente, en el panel del CPT |
| Tarjeta del listado | plantilla `loop-item` (Elementor) | cliente, en Elementor |
| Ficha de cada entrada | plantilla Theme Builder `single-post`, condición `include/singular/<cpt>` | cliente, en Elementor |
| Listado filtrable | página con Taxonomy Filter + Loop Grid (widgets V3 envueltos en `e-flexbox`) | cliente: consulta, columnas, textos |
| Listas con HTML (ingredientes, pasos) y valores traducidos/mapeados | shortcodes del child | cliente edita los datos; el formato es código |
| Query IDs, orden de botones, idioma del filtro, enlaces `?cat=`, schema | child | nadie (código) |

La regla de frontera general está en `tema-hijo.md`.

## 2. CPT, taxonomía y metas

### Registro (en `init`, desde el child)

```php
add_action( 'init', function () {
	register_post_type( 'receta', [
		'labels'        => [ 'name' => 'Recetas', 'singular_name' => 'Receta', 'add_new_item' => 'Añadir receta', 'edit_item' => 'Editar receta' ],
		'public'        => true,
		'show_in_rest'  => true,
		'has_archive'   => false,
		'taxonomies'    => [ 'categoria_receta' ],
		'rewrite'       => [ 'slug' => 'recetario/recetas', 'with_front' => false, 'feeds' => false ],
		'supports'      => [ 'title', 'thumbnail', 'page-attributes', 'revisions' ],
	] );
	register_taxonomy( 'categoria_receta', [ 'receta' ], [
		'labels'             => [ 'name' => 'Categorías de receta', 'singular_name' => 'Categoría' ],
		'hierarchical'       => true,
		'public'             => true,
		'publicly_queryable' => false,
		'show_in_nav_menus'  => true,
		'show_in_rest'       => true,
		'show_admin_column'  => true,
		'rewrite'            => false,
	] );
} );
```

Por qué cada ajuste:

- **`public` (y `show_in_nav_menus`, que hereda de `public`).** La condición del Theme
  Builder `singular/<cpt>` y las listas de tipos de Pro (Loop Grid, vista previa del
  Loop Item) salen de `get_post_types(['show_in_nav_menus'=>true])`. Si no está, la
  plantilla se ignora **sin avisar**.
- **`labels` completos.** Sin ellos, Theme Builder y Loop Grid muestran «Entradas».
- **Sin `'editor'` en `supports`.** WordPress usa la pantalla clásica, ideal para un
  panel de campos propio. `'page-attributes'` da el campo «Orden» (el listado se
  ordena por `menu_order`) y `'thumbnail'` alimenta la etiqueta `post-featured-image`.
- **No hace falta soporte `'elementor'`** ni la opción `elementor_cpt_support`: solo
  activa «Editar con Elementor» en cada entrada, y aquí el diseño vive en plantillas.
- **`rewrite` bajo una página existente** (`recetario/recetas`) no choca con la página:
  las reglas del CPT van en `extra_rules_top`, antes que las de páginas. Efectos
  colaterales: `/recetario/recetas/` a secas da 404 y una página hija con esa ruta
  quedaría tapada.
- **Taxonomía sin archivo** (`publicly_queryable=false`, `rewrite=false`) cuando el
  filtrado vive en la página del listado. `show_in_nav_menus=true` es obligatorio si
  vas a usar la etiqueta dinámica `post-terms`. Con Yoast, marca además
  `noindex-tax-<tax>` y `display-metabox-tax-<tax>=false`, o el sitemap listará URLs
  `/?taxonomy=…&term=…` que responden 200 (ver
  `../../wp-seo-rendimiento/references/seo-yoast-polylang.md`).
- **Polylang:** hazlos traducibles con los filtros, no con `update_option` crudo:
  ```php
  add_filter( 'pll_get_post_types', function ( $t ) { $t['receta'] = 'receta'; return $t; } );
  add_filter( 'pll_get_taxonomies', function ( $t ) { $t['categoria_receta'] = 'categoria_receta'; return $t; } );
  ```

Tras desplegar el registro (y cada vez que cambie):
`wp rewrite flush && wp transient delete pll_translated_slugs` + purga de la caché de
página. **Nunca** `flush_rewrite_rules()` en `init`. `wp rewrite flush` sin `--hard` solo
toca la opción de la BD, no `.htaccess`.

### Metas con revisiones y un panel propio

```php
const PFX_RECETA_CAMPOS = [ 'pfx_tiempo', 'pfx_nivel', 'pfx_rinde', 'pfx_intro', 'pfx_ingredientes', 'pfx_pasos' ];

add_action( 'init', function () {
	foreach ( PFX_RECETA_CAMPOS as $key ) {
		register_post_meta( 'receta', $key, [
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'revisions_enabled' => true, // sin esto la vista previa enseña lo guardado antes y restaurar una revisión no devuelve los campos
			'sanitize_callback' => fn( $valor ) => pfx_receta_limpiar( $key, $valor ), // cubre panel, REST y revisiones
			'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
		] );
	}
} );

add_action( 'save_post_receta', function ( $post_id ) {
	if ( ! isset( $_POST['pfx_receta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['pfx_receta_nonce'] ), 'pfx_receta_guardar' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( PFX_RECETA_CAMPOS as $key ) {
		$valor = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		update_post_meta( $post_id, $key, wp_slash( $valor ) ); // update_post_meta vuelve a quitar barras: sin wp_slash se pierde una «\»
	}
} );
```

El metabox se registra con `add_meta_boxes_<cpt>` + `add_meta_box()` y lleva
`wp_nonce_field( 'pfx_receta_guardar', 'pfx_receta_nonce' )`.

- **`is_string()` antes de sanear cualquier `$_POST`/`$_GET`.** En el caso de
  referencia `?cat[]=x` daba un fatal (HTTP 500) porque `sanitize_title()` recibió un
  array.
- **Listas en texto plano, no repetidores.** Ingredientes y pasos son `textarea` con
  un elemento por línea: una línea que termina en `:` es un subtítulo; «Etiqueta:
  texto» resalta la etiqueta. El mismo parser (regex `^([^:.]{3,28}):\s+(.+)$`)
  alimenta los shortcodes de la ficha y el schema. Al cliente le resultó más fácil
  que un repetidor y no depende de ACF.

## 3. Plantillas: Loop Item (tarjeta) y ficha (single)

Crear o reutilizar por título y tipo, idempotente. Siempre `--user=1`: sin usuario,
`Document::save()` devuelve `false` sin avisar y no se asigna el término
`elementor_library_type`. Los mismos pasos, empaquetados, son `$e4_plantilla`,
`$e4_guardar` y `$e4_condiciones` de `../scripts/e4-helpers.php`.

```php
use Elementor\Plugin;
use ElementorPro\Modules\ThemeBuilder\Module as Theme_Builder;

$doc_por_titulo = function ( $type, $title, $meta = [] ) {
	$found = get_posts( [ 'post_type' => 'elementor_library', 'post_status' => 'any', 'title' => $title, 'numberposts' => 1, 'fields' => 'ids',
		'meta_key' => '_elementor_template_type', 'meta_value' => $type, 'lang' => '' ] ); // en CLI Polylang no filtra (gotchas-cli.md A9), pero en ajax o frontend sí: explícito siempre
	if ( $found ) { return (int) $found[0]; }
	$doc = Plugin::$instance->documents->create( $type, [ 'post_title' => $title, 'post_status' => 'publish' ], $meta );
	if ( is_wp_error( $doc ) || ! $doc ) { WP_CLI::error( "No se pudo crear $title" ); }
	return (int) $doc->get_main_id();
};
$guardar = function ( $id, $elements, $settings = null ) {
	$doc  = Plugin::$instance->documents->get( $id, false );
	$data = [ 'elements' => $elements ];
	if ( null !== $settings ) {
		$previos = $doc->get_meta( '_elementor_page_settings' );
		$previos = is_array( $previos ) ? $previos : [];
		unset( $previos[0] ); // un meta vacío es '' y (array) '' deja una clave 0 basura
		$data['settings'] = array_merge( $previos, $settings ); // save_settings_to_db reemplaza el meta entero: fusiona
	}
	if ( false === $doc->save( $data ) ) { WP_CLI::error( "save() falló en #$id" ); } // Loop::save() devuelve null aunque guarde: compara con false
};

$preview = [ 'preview_type' => 'single/receta', 'preview_id' => '<ID de una receta real>' ];

$t_tarjeta = $doc_por_titulo( 'loop-item', 'Receta — tarjeta' );
$guardar( $t_tarjeta, [ /* árbol V4 de la tarjeta */ ], [ 'source' => 'post' ] + $preview );

$t_ficha = $doc_por_titulo( 'single-post', 'Receta — ficha', [ '_elementor_template_sub_type' => 'receta' ] );
$guardar( $t_ficha, [ /* árbol V4 de la ficha */ ], $preview );
Theme_Builder::instance()->get_conditions_manager()
	->save_conditions( $t_ficha, [ [ 'type' => 'include', 'name' => 'singular', 'sub_name' => 'receta', 'sub_id' => '' ] ] );
Plugin::$instance->files_manager->clear_cache();
```

Reglas y trampas:

- **Loop Item:** `source` = `'post'` (o `'post_taxonomy'`), **no** el nombre del CPT.
  Lo que ata la tarjeta al CPT es `post_query_post_type` del Loop Grid. Publícala: el
  selector de plantillas del Loop Grid solo lista publicadas.
- **`preview_type` / `preview_id`** en los ajustes de Loop Item y ficha: sin ellos el
  editor pinta la plantilla sin datos reales.
- **Dentro del Loop Item valen elementos V4.** Sus estilos locales y las clases
  globales que usa se encolan en la página del listado (Pro recorre los `template_id`
  del documento); la página tiene que ser singular. Si alguna vez escribes
  `_elementor_data` a mano en vez de `save()`, llama a `files_manager->clear_cache()`.
- **Ficha:** usa el tipo `single-post` (el `single` antiguo ya no sale en el diálogo)
  y guarda `_elementor_template_sub_type` para que el editor previsualice una entrada
  del CPT. Desde CLI las condiciones **no** se guardan solas:
  `save_conditions()` a mano. Un `false` al reejecutar significa «sin cambios», no
  error. La condición `include/singular/<cpt>` tiene prioridad 30 (gana a
  `singular` general y a `general`). La caché de condiciones solo incluye plantillas
  publicadas. Detalle de condiciones, caché e idioma: `theme-builder.md`.
- **`<main>` y ancho:** Hello no imprime `<main>` en fichas del Theme Builder; el
  child lo abre con `elementor/theme/before_do_single`. Ver `tema-hijo.md`.

## 4. Loop Grid y Taxonomy Filter (widgets V3 dentro de V4)

**Por qué V3.** El Taxonomy Filter trabaja contra un `loop-grid`, y su endpoint de
refresco comprueba `widgetType === 'loop-grid'`. El `e-collection-loop` atómico
existe y está activo en 4.3 (experimento `e_pro_collection_loop`), pero no se evaluó
con filtro: no lo uses para un listado filtrable sin probarlo antes.

**Envuélvelos en un `e-flexbox`.** Las clases globales V4 puestas en `_css_classes` de
un widget V3 **no hacen nada**: el CSS global de cada página solo incluye las clases
que usa algún elemento V4 del documento. Patrón: `e-flexbox` con una clase neutra
(`pfx-caja`: columna, `align-items: stretch`, gap 0, padding 0, width 100%) más
utilidades de margen, y dentro el widget V3 con una clase propia
(`pfx-filtro`, `pfx-rejilla-recetas`) que estiliza el CSS del child.

### Ajustes del Loop Grid

```php
$px = fn( $n ) => [ 'unit' => 'px', 'size' => $n, 'sizes' => [] ];
$v3 = fn( $id, $type, array $settings ) => [ 'id' => $id, 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => [] ];
$rejilla = fn( $id, $template, $query_id, $ppp, $cols, $clases ) => $v3( $id, 'loop-grid', [
	'_skin' => 'post', 'template_id' => (string) $template,
	'columns' => (string) $cols[0], 'columns_tablet_extra' => (string) $cols[1], 'columns_tablet' => (string) $cols[2], 'columns_mobile' => (string) $cols[3],
	'posts_per_page' => $ppp,
	'post_query_post_type' => 'receta', 'post_query_orderby' => 'menu_order', 'post_query_order' => 'asc',
	'post_query_query_id' => $query_id,
	'pagination_type' => '', 'masonry' => '', 'equal_height' => 'yes',
	'column_gap' => $px( 24 ), 'row_gap' => $px( 24 ),
	'enable_nothing_found_message' => 'yes',
	'nothing_found_message_text' => 'Aún no hay recetas en esta categoría.',
	'nothing_found_message_html_tag' => 'div',
	'_css_classes' => $clases,
] );
// $rejilla( 'recgrid', $t_tarjeta, 'pfx_recetario', -1, [ 4, 3, 2, 1 ], 'pfx-rejilla-recetas' )
```

| Clave | Valor | Trampa |
|---|---|---|
| `_skin` / prefijo | `post` → claves `post_query_*` | el prefijo es `{skin}_query` |
| `template_id` | ID numérico (int o string numérica) | Pro comprueba `is_numeric` para encolar los estilos atómicos de la plantilla |
| `columns*` | `columns`, `columns_tablet_extra`, `columns_tablet`, `columns_mobile` | `tablet_extra` solo si el breakpoint está activo en el kit |
| `posts_per_page` | `-1` para todas | pasa aunque el control declare `min 1` |
| include/exclude por término | **evítalos** | usan `term_taxonomy_id`, mientras la exclusión del filtro compara `term_id`; `post_type` ya trae todo |
| `nothing_found_message_html_tag` | `h1`–`h6`, `div`, `span` | `p` no es válido |
| `equal_height` | `yes` | funciona con raíz V4 porque `e-flexbox` lleva la clase `e-con` |
| `post_query_query_id` | el Query ID de tus hooks (§5) | — |

No valides claves con `get_controls()` desde CLI: oculta los controles de estilo y las
copias responsive (`equal_height`, `column_gap`, `*_mobile` salen «MISSING» y son
válidas). Relee el JSON guardado y renderiza.

El Loop Grid tiene que estar **directamente en el documento de la página** (no en una
plantilla incrustada): el refresco manda como `post_id` el `[data-elementor-id]` más
cercano, y el filtro y la rejilla deben estar en el mismo documento. El endpoint
también exige que esa página esté **publicada**: en un borrador el filtro no refresca.

### Ajustes del Taxonomy Filter

```php
$v3( 'recfilt', 'taxonomy-filter', [
	'selected_element' => 'recgrid', 'taxonomy' => 'categoria_receta', 'direction' => 'horizontal',
	'item_alignment_horizontal' => 'start', 'multiple_selection' => 'no', 'show_empty_items' => 'no',
	'show_child_taxonomy' => 'no', 'show_first_item' => 'yes', 'first_item_title' => 'Todos',
	'horizontal_scroll' => 'disable', 'horizontal_scroll_tablet' => 'disable', 'horizontal_scroll_mobile' => 'disable',
	'taxonomy_filter_items_space_between' => $px( 8 ),
	'_css_classes' => 'pfx-filtro',
] );
```

- `selected_element` = el `id` del Loop Grid, comparado como cadena exacta.
- `first_item_title` por defecto es «All»: ponlo siempre. Se imprime **sin escapar**.
- `logical_combination` (AND/OR) solo viaja al frontend con `multiple_selection=yes`.

### Los dos requisitos del filtro (id de 7 caracteres y taxonomía sin guiones)

El filtro escribe en la URL `?e-filter-<id del grid>-<taxonomía>=<slug>` (varios
términos con `+` para AND o `~` para OR). Al cargar la página, Pro relee ese
parámetro en servidor, filtra la consulta y marca el botón con `aria-pressed="true"`.
El parser (`elementor-pro/modules/loop-filter/module.php`) es:

```php
if ( ! preg_match( '/^e-filter-[a-z0-9]{7}-[a-z0-9_\-]+$/', $param_key ) ) { return []; }
$filter = explode( '-', str_replace( 'e-filter-', '', $param_key ) );
if ( count( $filter ) !== 2 ) { return []; }
```

| Parámetro | Resultado |
|---|---|
| `e-filter-a1b2c3d-categoria-receta` | ignorado (el `explode` da 3 partes) |
| `e-filter-a1b2c3d-categoria_receta` | aplicado |
| `e-filter-A1b2c3d-…` | ignorado (mayúscula en el id) |

El endpoint de refresco valida además el id con `/^[a-zA-Z0-9]{7}$/`. Conclusión: id
del grid de 7 caracteres en minúscula y dígitos, fijado por ti; taxonomía con guion
bajo (el slug de reescritura sí puede llevar guiones). **Yoast `clean_permalinks`
tiene que quedar en `false`**: redirige parámetros desconocidos y rompe
`?e-filter-…`, `?cat=` y `?elementor-preview`.

### Orden de los botones

El filtro no tiene control de orden (por defecto, nombre ASC). En el caso de
referencia se fijó el orden de creación de los términos:

```php
add_filter( 'elementor/loop_taxonomy/args', function ( $args ) {
	if ( 'categoria_receta' === ( $args['taxonomy'] ?? '' ) ) {
		$args['orderby'] = 'term_id';
		$args['order']   = 'ASC';
	}
	return $args;
} );
```

Para un orden arbitrario, la investigación propuso `$args['slug'] = [ … ]` +
`$args['orderby'] = 'slug__in'` (argumentos estándar de `get_terms`); no es lo que quedó
en producción.

### Cómo refresca (para engancharse sin tocar los archivos de Pro)

`POST {rest}elementor-pro/v1/refresh-loop` con
`{post_id, widget_id, widget_filters, pagination_base_url}`. Secuencia de
`refreshLoopWidget()`:

1. Actualiza la URL con `history.pushState` (no hay manejador de `popstate`: «atrás»
   no vuelve a filtrar).
2. Añade `e-loading-overlay` a `.elementor-element-<gridId>` (animación de pulso por
   defecto; las tarjetas viejas siguen en el DOM).
3. Pide y **sustituye el contenedor entero del widget**, no los hijos. Con cero
   resultados el nuevo contenedor solo trae `.e-loop-nothing-found-message`. Si falla,
   no sustituye nada.
4. Ejecuta los manejadores de elementos y `runReadyTrigger`.
5. En `.finally` quita `e-loading-overlay`.

No emite eventos propios. Enganches fiables: un `MutationObserver` sobre la clase
`e-loading-overlay` de la rejilla (inicio y fin, también si falla);
`elementorFrontend.hooks.addAction('frontend/element_ready/loop-grid.post', …)`
dentro de `window.addEventListener('elementor/frontend/init', …)` (rejilla nueva);
y `elementorProFrontend.modules.taxonomyFilter.refreshLoopWidget`, que devuelve la
promesa (no documentado).

### Parches de UX y accesibilidad que hicieron falta (JS del child)

- **Pro no cancela la petición anterior** y quita el estado de carga con la primera
  respuesta: con dos clics rápidos se veían tarjetas de la categoría anterior. Se
  envuelve `refreshLoopWidget` con un contador de pendientes. No envuelvas
  `window.fetch` global (primer intento, más frágil).
- **«Todos» va el último en el DOM** y sale primero solo por CSS (`order:-1`): el
  teclado lo recorre el último. Muévelo al principio del DOM.
- `role="list"` sin `listitem` en las tarjetas; el grupo de botones sin nombre.

```js
var pendientes = 0;
function parchearFiltro() {
	var m = window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.taxonomyFilter;
	if (!m || m.hmParcheado || typeof m.refreshLoopWidget !== 'function') { return; } // si Pro cambia, no hace nada
	var original = m.refreshLoopWidget;
	m.refreshLoopWidget = function () {
		var p = original.apply(this, arguments);
		if (p && typeof p.finally === 'function') {
			pendientes++;
			p.finally(function () {
				pendientes = Math.max(0, pendientes - 1);
				if (!pendientes) { document.dispatchEvent(new CustomEvent('hm:recetario-listo')); }
			});
		}
		return p;
	};
	m.hmParcheado = true;
}
window.addEventListener('elementor/frontend/init', parchearFiltro);
window.addEventListener('load', parchearFiltro);

function rolesDeLista(root) {
	root.querySelectorAll('.elementor-loop-container[role="list"] > .e-loop-item').forEach(function (el) {
		el.setAttribute('role', 'listitem');
	});
}
document.querySelectorAll('.pfx-filtro .e-filter').forEach(function (f) {
	var todos = f.querySelector('[data-filter="__all"]');
	if (todos) { f.insertBefore(todos, f.firstChild); }
	f.setAttribute('role', 'group');
	f.setAttribute('aria-label', /^en/i.test(document.documentElement.lang || '') ? 'Filter recipes' : 'Filtrar recetas');
});
```

En la rejilla: `aria-live="polite"`, `aria-busy="true"` mientras carga, esqueletos
de tarjeta al menos 420 ms (como el prototipo) y `rolesDeLista()` tras cada refresco.
Quita el pulso de Pro con `.elementor .pfx-rejilla-recetas.e-loading-overlay{animation:none}`.

**Entrada escalonada:** los `<style id="loop-<T>">` que Pro inserta como hermanos de
las tarjetas descuadran `:nth-child`. Usa `.e-loop-item:nth-of-type(n)`,
`:nth-child(n of .e-loop-item)` o fija `--i` por JS. Como el refresco inserta nodos
nuevos, una animación CSS en `.e-loop-item` se repite sola tras cada filtrado.

## 5. Query ID: `elementor/query/<id>` con guarda de reentrada

Pro dispara `elementor/query/<post_query_query_id>` con la `WP_Query` del grid para
que la modifiques. **Trampa grave:** mientras construye su consulta, Pro engancha
`pre_get_posts` y dispara la acción para **cualquier** `WP_Query` que se ejecute en
ese intervalo, incluida la que lance tu propio callback. En el caso de referencia, un
`get_posts()` dentro del callback provocó recursión infinita: la ficha tardaba 60 s
y cortaba.

```php
add_action( 'elementor/query/pfx_relacionadas', function ( WP_Query $query ) {
	static $dentro = false;             // guarda de reentrada
	if ( $dentro ) {
		return;
	}
	$post_id = get_the_ID();
	if ( ! $post_id || 'receta' !== get_post_type( $post_id ) ) {
		return;                         // fuera de contexto: no toques nada
	}
	$dentro = true;
	$misma  = pfx_receta_hay_misma_categoria( $post_id ); // hace get_posts(): siempre ANTES de tocar $query
	$dentro = false;

	/* se suma a lo que el cliente configure en el Loop Grid, no lo pisa */
	$query->set( 'post_type', 'receta' );
	$query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), [ $post_id ] ) ) ) );
	if ( $misma ) {
		$tax   = array_filter( (array) $query->get( 'tax_query' ) );
		$tax[] = [ 'taxonomy' => 'categoria_receta', 'terms' => wp_get_post_terms( $post_id, 'categoria_receta', [ 'fields' => 'ids' ] ) ];
		if ( count( $tax ) > 1 && ! isset( $tax['relation'] ) ) {
			$tax['relation'] = 'AND';
		}
		$query->set( 'tax_query', $tax );
	}
} );
```

Reglas: guarda `static`; consultas internas antes de modificar `$query`; salida
temprana fuera de contexto; **fusiona** `post__not_in` y `tax_query` en vez de
reemplazarlos, para que «Posts por página» y demás ajustes del cliente sigan mandando.

### Idioma del listado con Polylang

El refresco por REST no manda `lang` y Polylang solo fija el idioma REST si llega ese
parámetro (lo hace en `rest_pre_dispatch` con prioridad 10). Resultado sin arreglo:
en `/<IDIOMA_2>/` el filtro devuelve tarjetas del idioma base.

```php
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	if ( function_exists( 'pll_get_post_language' ) && str_starts_with( $request->get_route(), '/elementor-pro/v1/refresh-loop' ) && ! $request->get_param( 'lang' ) ) {
		$lang = pll_get_post_language( (int) $request->get_param( 'post_id' ) ); // la página que contiene el grid
		if ( $lang ) {
			$request->set_param( 'lang', $lang );
		}
	}
	return $result;
}, 5, 3 ); // 5: antes que Polylang

add_action( 'elementor/query/pfx_recetario', function ( WP_Query $query ) {
	if ( function_exists( 'pll_current_language' ) && pll_current_language() ) {
		$query->set( 'lang', pll_current_language() );
	}
} );
```

Sin IDs escritos a mano: sirve para cualquier página nueva del segundo idioma. Los
slugs de término que viajen en la URL deben ser los del idioma de la página.

## 6. Dynamic tags V4 desde WP-CLI

### El fallo

`$document->save()` desde `wp eval-file` rechaza todo prop dinámico **con settings**
(`post-title`, `post-featured-image`, `post-custom-field`, `shortcode`, `post-terms`):

```
Exception: Settings validation failed. title: invalid_value
```

Solo pasan los tags sin settings, como `post-url`. Cadena causal:

1. WP-CLI no es admin, ni preview, ni REST → `Performance::should_optimize_controls()`
   devuelve `true` → `Controls_Stack::add_control` quita el `label` de todos los
   controles.
2. `Dynamic_Tags_Editor_Config::convert_control_to_atomic` exige `name`, `section`,
   `label` y `default`; sin label el tag entero queda fuera del registro atómico
   (medido: 9 de 38 tags en CLI, 37 de 38 en modo editor).
3. `Dynamic_Prop_Type::validate_value` no encuentra el tag → `Has_Atomic_Base::parse_atomic_settings`
   lanza la excepción y `Document::get_elements_raw_data` no la captura.
4. `post-terms`, además, calcula sus opciones con
   `get_taxonomies(['show_in_nav_menus'=>true,'object_type'=>[get_post_type()]])`,
   vacío en CLI → «Select control must have options».

El render en frontend no se ve afectado (usa el registro V3).

### El preámbulo (primeras líneas del script, antes de tocar nada de tags)

```php
$perf = new ReflectionProperty( \Elementor\Core\Frontend\Performance::class, 'is_frontend' );
if ( PHP_VERSION_ID < 80100 ) { $perf->setAccessible( true ); } // desde PHP 8.1 no hace falta; en 8.5 está obsoleto
$perf->setValue( null, false );
\Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module::fresh();
add_filter( 'elementor_pro/dynamic_tags/post_terms/taxonomy_args', function ( $a ) { unset( $a['object_type'] ); return $a; } );
```

Listo para `require`, con los constructores de abajo, en
`../scripts/e4-dynamic-tags-preambulo.php`. Al empezar el script la estática aún es
`NULL`, así que no hay nada cacheado; el cambio vive solo en memoria. La investigación dio también por válido
`define( 'REST_REQUEST', true );` al principio, pero los scripts del caso de referencia
usaron el preámbulo de arriba. Alternativa peor: escribir `_elementor_data` directamente, que se salta
la validación **y** el saneado. Las sondas que validen `_elementor_data` con los
parsers de Elementor necesitan el mismo preámbulo.

### Forma del valor

```json
{"$$type":"dynamic","value":{"name":"post-title","group":"post","settings":{}}}
```

- `name`, `group` y `settings` son obligatorios (sin `group` no valida).
- Cada setting va con su envoltorio `$$type` (texto/select → `string`, switcher →
  `boolean`, number → `number`, media → `image`). Los valores crudos fallan al validar
  (aunque rendericen) y las claves desconocidas se descartan al guardar.

Constructores (con `String_Prop_Type`, `Boolean_Prop_Type`, `Image_Prop_Type` y
`Link_Prop_Type` de `Elementor\Modules\AtomicWidgets\PropTypes`):

```php
$s    = fn( $v ) => String_Prop_Type::generate( (string) $v );
$dyn  = fn( $name, $group, $settings = [] ) => [ '$$type' => 'dynamic', 'value' => [ 'name' => $name, 'group' => $group, 'settings' => $settings ] ];
$d_titulo = $dyn( 'post-title', 'post' );                                              // e-heading: settings['title']
$d_url    = $dyn( 'post-url', 'post' );                                                // destino de un link
$d_campo  = fn( $key ) => $dyn( 'post-custom-field', 'post', [ 'key' => $s( '' ), 'custom_key' => $s( $key ) ] );
$d_sc     = fn( $sc ) => $dyn( 'shortcode', 'site', [ 'shortcode' => $s( $sc ) ] );  // e-paragraph: settings['paragraph']
$d_cat    = $dyn( 'post-terms', 'post', [ 'taxonomy' => $s( 'categoria_receta' ), 'separator' => $s( ', ' ), 'link' => Boolean_Prop_Type::generate( false ) ] );

// foto por ítem (e-image)
$el['settings']['image'] = Image_Prop_Type::generate( [ 'src' => $dyn( 'post-featured-image', 'post' ), 'size' => $s( 'medium_large' ) ] );
// tarjeta entera enlazada (e-flexbox → se pinta como <a>)
$el['settings']['link'] = Link_Prop_Type::generate( [ 'destination' => $d_url, 'isTargetBlank' => Boolean_Prop_Type::generate( false ) ] );
```

Imagen destacada con respaldo (sin `fallback`, una entrada sin foto pinta un `<img>`
sin `src`):

```json
{"image":{"$$type":"image","value":{"src":{"$$type":"dynamic","value":{"name":"post-featured-image","group":"post","settings":{"fallback":{"$$type":"image","value":{"src":{"$$type":"image-src","value":{"id":{"$$type":"image-attachment-id","value":123},"url":null}},"size":{"$$type":"string","value":"full"}}}}}},"size":{"$$type":"string","value":"medium_large"}}}}
```

### Tags útiles y sus límites

| Tag (`name`) | `group` | Settings | Uso y límites |
|---|---|---|---|
| `post-title` | `post` | `before`, `after`, `fallback` | títulos |
| `post-url` | `post` | — | destino de `link`; valida incluso sin preámbulo |
| `post-featured-image` | `post` | `fallback` (`image`) | `image.value.src` de un `e-image` |
| `post-custom-field` | `post` | `key` (vacío), `custom_key`, `before`, `after`, `fallback` | **solo cadenas** (arrays y serializados salen vacíos); no está en la categoría `image`, así que no sirve para `image.src`; el desplegable `key` oculta metas con `_`, `custom_key` acepta cualquiera |
| `post-terms` | `post` | `taxonomy`, `separator`, `link` (`boolean`) | exige el filtro del preámbulo y `show_in_nav_menus=true` en la taxonomía |
| `shortcode` | `site` | `shortcode`, `before`, `after`, `fallback` | texto calculado por un shortcode; dentro, `get_the_ID()` es el post del bucle |

Reglas:

- **Dinámico solo en settings, nunca en estilos.** Los estilos atómicos se compilan a
  CSS por documento y se cachean: un fondo dinámico se resolvería una vez, no por
  tarjeta. La foto por ítem va en un `e-image`, no como `background-image`.
- **El HTML dinámico se sanea.** Las cadenas pasan por `wp_kses` con las etiquetas de
  envoltorio de Elementor (se pierden clases y atributos) y luego por `striptags`.
  `e-paragraph` solo admite `tag` `p` o `span`, así que una lista acabaría dentro de un
  `<p>` (HTML inválido). Listas y HTML rico → widget Shortcode (§7).
- **Un `e-flexbox` con `link` se pinta como `<a>`**: no metas dentro otro elemento
  enlazado (un `e-button` con enlace dentro de una tarjeta enlazada).
- **Contra qué post resuelve:** el `$post` global. El Loop Grid hace `the_post()` por
  ítem y la ficha usa el post consultado. En el editor, lo decide
  `preview_type`/`preview_id` de la plantilla.
- **Con segundo idioma**, los documentos del idioma 2 se guardan desde CLI en el
  contexto de ese idioma: ver `../../wp-polylang/references/generacion-idioma.md`.

Verificación: `save()` sin excepción, relectura del JSON con `"$$type":"dynamic"`, y
render de dos posts en una `WP_Query` simulada con valores distintos.

## 7. Shortcodes de apoyo

Úsalos cuando el valor no es un campo tal cual:

- **HTML rico por ítem** (listas con subtítulos, pasos numerados con etiqueta en
  negrita): widget V3 `shortcode`, con ajustes V3 planos, dentro de un `pfx-caja`.
  Se re-renderiza por ítem del bucle y conserva el marcado completo.
  ```json
  {"id":"recingr","elType":"widget","widgetType":"shortcode","settings":{"shortcode":"[pfx_receta_ingredientes]"},"elements":[]}
  ```
- **Valores mapeados o traducidos** (clave `facil` → «Fácil»/«Easy»), resúmenes
  compuestos («10 min · Fácil», sin separador suelto si falta un dato) y títulos que
  dependen de los datos («Más de <categoría>» u «Otras recetas»): etiqueta dinámica
  `shortcode` en un `e-paragraph`/`e-heading`.

Catálogo del caso de referencia:

| Shortcode | Devuelve |
|---|---|
| `[pfx_receta campo="tiempo\|nivel\|rinde\|intro\|categoria\|resumen"]` | un dato escapado; `nivel` traducido al idioma **del post** |
| `[pfx_receta_ingredientes]` | `<ul>`; las líneas que acaban en `:` son `<li>` de subtítulo |
| `[pfx_receta_pasos]` | `<ol>` numerado; «Etiqueta: texto» → `<strong>Etiqueta.</strong> texto` |
| `[pfx_receta_relacionadas_titulo mas="Más de" otras="Otras recetas"]` | título de relacionadas; sin atributos, textos en el idioma del post |

```php
add_shortcode( 'pfx_receta_pasos', function () {
	$raw    = (string) get_post_meta( get_the_ID(), 'pfx_pasos', true );
	$lineas = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ), 'strlen' ) );
	if ( ! $lineas ) {
		return '';
	}
	$out = '';
	foreach ( $lineas as $i => $linea ) {
		$texto = esc_html( $linea );
		if ( preg_match( '/^([^:.]{3,28}):\s+(.+)$/u', $linea, $m ) ) {
			$texto = '<strong>' . esc_html( $m[1] ) . '.</strong> ' . esc_html( $m[2] );
		}
		$out .= sprintf( '<li class="pfx-pasos__paso"><span class="pfx-pasos__num" aria-hidden="true">%d</span><p>%s</p></li>', $i + 1, $texto );
	}
	return '<ol class="pfx-pasos">' . $out . '</ol>';
} );
```

Reglas: escapa todo; el idioma sale de `pll_get_post_language( $post_id )`, no del
idioma actual; los textos fijos van como atributos editables con respaldo por
idioma; devuelve `''` si no hay dato y oculta los huecos con CSS:

```css
.elementor .pfx-tarjeta__badge:not(:has(> span:not(:empty))),
.elementor .pfx-dato:empty,
.elementor .pfx-receta-hero__meta > :empty { display: none; }
```

## 8. Enlaces estables a un listado filtrado (`?cat=`)

La URL del filtro lleva el id del grid, que cambia si alguien rehace el widget, y el
slug del término cambia de idioma. Para que la home (o cualquier enlace) apunte al
listado ya filtrado, publica una URL estable `/<pagina>/?cat=<slug>#<ancla>` y
redirígela en servidor:

```php
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['cat'] ) || ! is_page() ) {
		return;
	}
	$cat  = is_string( $_GET['cat'] ) ? wp_unslash( $_GET['cat'] ) : ''; // ?cat[]= daba 500
	$grid = pfx_recetario_grid_id( get_queried_object_id() );
	if ( ! $grid ) {
		return;
	}
	$slug = sanitize_title( $cat );
	$url  = remove_query_arg( 'cat' );
	if ( $slug ) {
		/* la clave puede venir en cualquier idioma: se busca sin filtro de idioma y se pasa al de la página */
		$terms = get_terms( [ 'taxonomy' => 'categoria_receta', 'slug' => $slug, 'hide_empty' => false, 'lang' => '' ] );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$term = $terms[0];
			$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( get_queried_object_id() ) : '';
			$tr   = ( $lang && function_exists( 'pll_get_term' ) ) ? pll_get_term( $term->term_id, $lang ) : 0;
			$t    = $tr ? get_term( $tr, 'categoria_receta' ) : null;
			if ( $t instanceof WP_Term ) {
				$term = $t;
			}
			$url = add_query_arg( 'e-filter-' . $grid . '-categoria_receta', $term->slug, $url );
		}
	}
	wp_safe_redirect( $url, 302 ); // el navegador conserva el #ancla
	exit;
} );

/* id del primer loop-grid del CPT en la página: el enlace sobrevive si se rehace el widget */
function pfx_recetario_grid_id( $page_id ) {
	$data = json_decode( (string) get_post_meta( $page_id, '_elementor_data', true ), true );
	$find = function ( $els ) use ( &$find ) {
		foreach ( (array) $els as $el ) {
			if ( 'loop-grid' === ( $el['widgetType'] ?? '' ) && 'receta' === ( $el['settings']['post_query_post_type'] ?? '' ) ) {
				return $el['id'];
			}
			$hit = $find( $el['elements'] ?? [] );
			if ( $hit ) {
				return $hit;
			}
		}
		return null;
	};
	return $find( $data );
}
```

- Slug desconocido → 302 a la página sin filtrar. Límite: con dos Loop Grid del mismo
  CPT en la página, usa el primero.
- **El cambio de idioma conserva el filtro:** un JS reescribe el `href` de los enlaces
  del otro idioma a `<url base>?cat=<slug actual>#<ancla>` al cargar, tras cada
  refresco (el evento del contador de §4) y en `popstate`. La redirección traduce el
  slug.
- Alternativa descartada: `register_widget_filter()` del módulo `loop-filter` en `wp`
  filtra en servidor, pero el estado JS del filtro y la URL no se enteran y `?cat=` se
  queda en la barra.

## 9. Schema de receta (u otro tipo) como pieza del grafo de Yoast

Yoast solo genera WebPage/WebSite/Organization. Para resultados enriquecidos añade
una pieza al grafo con la API pública de Yoast, cargada solo cuando hace falta (así
nada rompe si Yoast no está):

```php
add_filter( 'wpseo_schema_graph_pieces', function ( $pieces, $context ) {
	if ( 'receta' === ( $context->indexable->object_sub_type ?? '' ) ) {
		require_once __DIR__ . '/schema-receta.php';
		$pieces[] = new PFX_Schema_Receta();
	}
	return $pieces;
}, 11, 2 );
```

```php
use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

class PFX_Schema_Receta extends Abstract_Schema_Piece {
	public $identifier = 'pfx_receta'; // filtros: wpseo_schema_needs_pfx_receta / wpseo_schema_pfx_receta

	public function is_needed() {
		return 'post' === $this->context->indexable->object_type
			&& 'receta' === $this->context->indexable->object_sub_type
			&& $this->context->has_image                                        // Google: name e image obligatorios
			&& [] !== pfx_receta_lineas( (int) $this->context->id, 'pfx_ingredientes' );
	}

	public function generate() {
		$id   = (int) $this->context->id;
		$page = $this->context->main_schema_id;
		$html = $this->helpers->schema->html;
		$data = [
			'@type'            => 'Recipe',
			'@id'              => $page . '#recipe',
			'name'             => $html->smart_strip_tags( $this->helpers->post->get_post_title_with_fallback( $id ) ),
			'mainEntityOfPage' => [ '@id' => $page ],
			'image'            => [ $this->context->main_image_url ],
			'datePublished'    => $this->helpers->date->format( $this->context->post->post_date_gmt ),
			'dateModified'     => $this->helpers->date->format( $this->context->post->post_modified_gmt ),
		];
		if ( 'company' === $this->context->site_represents ) {
			$org               = $this->context->site_url . Schema_IDs::ORGANIZATION_HASH;
			$data['author']    = [ '@type' => 'Organization', '@id' => $org, 'name' => $html->smart_strip_tags( $this->context->company_name ), 'url' => $this->context->site_url ];
			$data['publisher'] = [ '@id' => $org ];
		}
		$rinde = trim( (string) get_post_meta( $id, 'pfx_rinde', true ) );
		if ( '' !== $rinde ) { // Google: primero el número solo, luego el texto
			$data['recipeYield'] = ( preg_match( '/\d+/', $rinde, $n ) && $n[0] !== $rinde ) ? [ $n[0], $rinde ] : [ $rinde ];
		}
		foreach ( pfx_receta_lineas( $id, 'pfx_ingredientes' ) as $linea ) {
			if ( ! str_ends_with( $linea, ':' ) ) { // «Para el glaseado:» es subtítulo
				$data['recipeIngredient'][] = $html->smart_strip_tags( $linea );
			}
		}
		foreach ( pfx_receta_lineas( $id, 'pfx_pasos' ) as $linea ) {
			$paso = [ '@type' => 'HowToStep', 'text' => $html->smart_strip_tags( $linea ) ];
			if ( preg_match( '/^([^:.]{3,28}):\s+(.+)$/u', $linea, $m ) ) { // misma regla que el shortcode
				$paso['name'] = $html->smart_strip_tags( $m[1] );
				$paso['text'] = $html->smart_strip_tags( $m[2] );
			}
			$data['recipeInstructions'][] = $paso;
		}
		return $this->helpers->schema->language->add_piece_language( $data );
	}
}
```

El archivo real añade además `description` (campo de introducción o descripción de
Yoast), `recipeCategory` y `keywords` (frase clave de Yoast). Reglas:

- **Sin `totalTime`** si el campo «tiempo» no incluye reposos (nevera, congelador):
  Google lo mostraría como tiempo total.
- **Migas:** mete la página del listado en el idioma del post con
  `wpseo_breadcrumb_links` + `array_splice( $crumbs, 1, 0, [ [ 'url' => …, 'text' => …, 'id' => … ] ] )`.
- **Con `post_content` vacío**, el análisis de contenido de Yoast sale en gris. Avisa
  al cliente para que no pegue texto en el contenido «para ponerlo en verde».
- Ajustes de Yoast/Polylang (x-default, Organization por idioma, sitemap):
  `../../wp-seo-rendimiento/references/seo-yoast-polylang.md`.

Verificación: Rich Results Test de una ficha (funciona aunque haya `noindex`) y
extracción del bloque `yoast-schema-graph` del HTML.

## 10. Verificación

```bash
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/126 Safari/537.36'
# ficha y listado responden rápido (una recursión en el Query ID se ve aquí)
curl -s -A "$UA" -m 60 -o /dev/null -w '%{http_code} %{time_total}s\n' "https://<DOMINIO>/recetario/recetas/<slug>/?nocache=$RANDOM"
# el filtro se aplica en servidor al cargar (botón marcado y solo sus tarjetas)
curl -s -A "$UA" "https://<DOMINIO>/recetario/?e-filter-recgrid-categoria_receta=<slug>&nocache=$RANDOM" | grep -o 'data-filter="<slug>" aria-pressed="true"'
# enlace corto: 302 al filtro, en el idioma de la página
curl -sI -A "$UA" "https://<DOMINIO>/<IDIOMA_2>/recetario/?cat=<slug-idioma-base>" | grep -i '^location'
# parámetro tipo array: 302, nunca 500
curl -s -A "$UA" -o /dev/null -w '%{http_code}\n' "https://<DOMINIO>/recetario/?cat%5B%5D=x"
# condiciones del Theme Builder
wp option get elementor_pro_theme_builder_conditions --format=json
```

En el navegador: pulsa tres categorías seguidas (el esqueleto dura hasta la última
respuesta), recarga la URL filtrada, recorre el filtro con Tab («Todos» primero) y
filtra desde `/<IDIOMA_2>/` comprobando que todas las tarjetas son de ese idioma.

## Dudas abiertas

- `e-collection-loop` (V4) no se evaluó con el Taxonomy Filter. Hasta probarlo, el
  listado filtrable va con Loop Grid V3.
- El parche de `refreshLoopWidget` se apoya en una API interna no documentada de Pro.
  Revisa el filtro tras cada actualización de Pro (si deja de existir, el parche no
  hace nada y solo se pierde la UX de carga).
