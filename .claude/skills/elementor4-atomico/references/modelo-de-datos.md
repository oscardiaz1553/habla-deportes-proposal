# Modelo de datos de Elementor 4 atómico (V4)

Verificado con Elementor **4.3.0**, Elementor Pro **4.2.3**, Hello Elementor 3.5.1,
WordPress 7.1.2 y PHP 8.2, con el experimento `e_atomic_elements` activo. Las clases
internas cambian entre versiones; los mecanismos, menos. Antes de usar una forma que
no esté aquí, **sondéala** (sección 1). No te fíes de lo que sabes de Elementor 3.

Las trampas con su arreglo están en `references/gotchas-cli.md`. Los códigos entre
corchetes, como [C1], remiten a ese archivo.

---

## 1. Sondea antes de escribir

V4 se describe solo: si puedes volcar el esquema, puedes generar el JSON sin adivinar.
Todas estas sondas son de solo lectura (`wp --path=<WP_PATH> eval-file sonda.php`). Las
versiones completas están en `scripts/e4-sondeo-kit.php` (versiones, experimentos, kit,
variables, clases, breakpoints y opciones de colapso del nav-menu) y
`scripts/e4-sondeo-esquema.php` (elementos, settings, enums de `tag` y formas de estilo).

**¿Es V4?** Primer script de cualquier proyecto:
```php
$e = \Elementor\Plugin::$instance->experiments;
foreach ( $e->get_features() as $slug => $f ) {
	printf( "%s  %-34s %s\n", $e->is_feature_active( $slug ) ? 'ACTIVO  ' : 'inactivo', $slug, $f['title'] ?? '' );
}
```
Tiene que salir `e_atomic_elements` activo. En el caso de referencia también estaban
activos `e_optimized_markup`, `e_pro_atomic_form` y `e_font_icon_svg`. Si el sitio
está en V3, esta skill no aplica tal cual.

**Props de un elemento** (qué claves admite `settings`):
```php
$em = \Elementor\Plugin::$instance->elements_manager;
$wm = \Elementor\Plugin::$instance->widgets_manager;
foreach ( [ 'e-heading', 'e-paragraph', 'e-button', 'e-image', 'e-flexbox', 'e-div-block' ] as $t ) {
	$el = $wm->get_widget_types( $t ) ?: $em->get_element_types( $t );
	echo "== $t\n";
	foreach ( $el::get_props_schema() as $key => $prop ) {
		echo '   ' . str_pad( $key, 22 ) . ( method_exists( $prop, 'get_key' ) ? $prop::get_key() : get_class( $prop ) ) . "\n";
	}
}
```

**Forma y enum de una prop de estilo** (el `$$type` exacto):
```php
use Elementor\Modules\AtomicWidgets\Styles\Style_Schema;
$s = Style_Schema::get();
foreach ( [ 'grid-template-columns', 'display', 'flex-direction', 'border-width' ] as $k ) {
	$p = $s[ $k ];
	$members = method_exists( $p, 'get_prop_types' ) ? $p->get_prop_types() : [ $p ];
	foreach ( $members as $m ) {
		$shape = method_exists( $m, 'get_shape' ) ? array_keys( $m->get_shape() ) : [];
		$enum  = method_exists( $m, 'get_settings' ) ? ( $m->get_settings()['enum'] ?? null ) : null;
		printf( "%-22s key=%-24s shape=[%s]%s\n", $k, $m::get_key(), implode( ', ', $shape ), $enum ? ' enum=' . implode( '|', array_slice( $enum, 0, 10 ) ) : '' );
	}
}
```

**Enum de `tag`** (qué etiquetas HTML admite cada elemento):
```php
$em = \Elementor\Plugin::$instance->elements_manager; $wm = \Elementor\Plugin::$instance->widgets_manager;
foreach ( [ 'e-flexbox', 'e-div-block', 'e-grid', 'e-paragraph', 'e-heading' ] as $t ) {
	$el = $wm->get_widget_types( $t ) ?: $em->get_element_types( $t );
	$p = $el::get_props_schema()['tag'];
	foreach ( ( method_exists( $p, 'get_prop_types' ) ? $p->get_prop_types() : [ $p ] ) as $m ) {
		$set = method_exists( $m, 'get_settings' ) ? $m->get_settings() : [];
		if ( ! empty( $set['enum'] ) ) { echo str_pad( $t, 12 ) . implode( ' ', $set['enum'] ) . "\n"; }
	}
}
```

**Elementos atómicos registrados:** `elements_manager->get_element_types()` filtrando los
que empiezan por `e-`. En 4.3.0 salieron, entre otros: `e-div-block`, `e-flexbox`,
`e-grid`, la familia `e-tabs` (`e-tabs-menu`, `e-tab`, `e-tabs-content-area`,
`e-tab-content`), la familia `e-accordion` y la familia `e-background-video`.

**Pintar un elemento sin guardar nada** (para decidir cómo modelar algo antes de
escribir un constructor grande):
```php
$el = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $data );
ob_start(); $el->print_element(); echo trim( preg_replace( '/\s+/', ' ', ob_get_clean() ) );
try { $el->get_data_for_save(); echo "VÁLIDO\n"; } catch ( \Throwable $e ) { echo $e->getMessage(); }
```

**Opciones de un control de un widget V3** (p. ej. qué admite `dropdown` del nav-menu):
en CLI Elementor no registra todos los controles; hay que activar el modo editor e
instanciar el widget. Aun así, para los valores reales manda la plantilla CSS o el PHP
del widget [A12] [K3].
```php
\Elementor\Plugin::$instance->editor->set_edit_mode( true );
$type = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( 'nav-menu' );
$cls  = get_class( $type );
$w    = new $cls( [ 'id' => 'probe00', 'elType' => 'widget', 'widgetType' => 'nav-menu', 'settings' => [] ], [] );
print_r( $w->get_controls( 'dropdown' )['options'] );
```

---

## 2. El árbol de un documento

`_elementor_data` (meta del post, JSON) es una lista de elementos raíz. Cada elemento V4
tiene esta forma:

```php
[
	'id'              => 'a1b2c3d',            // 7 caracteres [a-z0-9]: ver sección 4
	'elType'          => 'e-flexbox',          // contenedor atómico; en un widget, 'widget'
	'widgetType'      => 'e-heading',          // solo cuando elType = 'widget'
	'version'         => '0.4',
	'settings'        => [ /* props con $$type: sección 3 */ ],
	'elements'        => [ /* hijos */ ],
	'styles'          => [],                   // estilos locales del elemento: sección 6
	'editor_settings' => [ 'title' => 'Banner' ], // nombre en el Navegador del editor
];
```

- `editor_settings.title` es lo que el cliente ve en el Navegador. Pon títulos legibles
  en español a cada sección y bloque: es parte de «editable por el cliente».
- Un widget V3 (nav-menu, form, loop-grid, shortcode, text-editor) va con `elType`
  `widget`, **sin** `version` ni `styles`, y con settings **planos** (sin `$$type`).
  Ver sección 9.

### Elementos usados en el caso de referencia

| Elemento | `elType` / `widgetType` | Settings de contenido | Notas |
|---|---|---|---|
| `e-flexbox` | `e-flexbox` | `classes`, `tag`, `link`, `attributes`, `_cssid` | Flex en fila por defecto. Con `link` se pinta como `<a>`: no metas dentro otro elemento enlazado. |
| `e-div-block` | `e-div-block` | ídem | `display:block`. Útil para cajas, rayas e iconos. |
| `e-grid` | `e-grid` | ídem | Base con 3 columnas × 2 filas y `gap:20px` [D4]. |
| `e-heading` | `widget` / `e-heading` | `title` (HTML), `tag` (`h1`–`h6`), `link` | El HTML se sanea: `<strong>` sobrevive, los atributos de `<span>` no [E1]. |
| `e-paragraph` | `widget` / `e-paragraph` | `paragraph` (HTML), `tag` (`p` o `span`) | Sin listas: pasa por `striptags` [E2]. |
| `e-button` | `widget` / `e-button` | `text` (HTML), `link` | Con `link` es `<a>`; sin `link`, `<button>`. |
| `e-image` | `widget` / `e-image` | `image` = `{src:{id,url,alt}, size}` | El alt sale de la Biblioteca, no del widget [E4]. |
| `e-svg` | `widget` / `e-svg` | `svg` = `{url}` o `{id}` | Fuerza `fill="currentColor"` en la raíz [E7]. |
| `e-background-video` | `e-background-video` | `source`, `autoplay`, `mute`, `loop`, `show_controls`, `state` | Hijos: `e-background-video-content` y los controles [E9]. |

Todos los elementos del esquema comparten `classes`, `tag`, `link`, `attributes`,
`_cssid` y `display-conditions`.

### Valores válidos de `tag` (Elementor 4.3.0)

| Elemento | Enum |
|---|---|
| `e-flexbox`, `e-div-block` | `div header section article aside footer a button main nav` |
| `e-grid` | lo mismo **sin** `main` ni `nav` |
| `e-paragraph` | `p span` |
| `e-heading` | `h1`–`h6` |
| `e-background-video` | no admite `main` (verificado); usa `section` + atributo `role="main"` |

No existen `dl`, `ul` ni `ol`. Lo que no quepa en el enum se resuelve con contenedores
(y se avisa de la pérdida semántica) o con un widget Shortcode que imprima el HTML.

---

## 3. Settings: props con `$$type`

Construye los valores **siempre con los generadores** de Elementor, nunca escribiendo
el JSON a mano: el generador pone el envoltorio correcto y el error de validación te
dirá la prop exacta si algo falla.

```php
use Elementor\Plugin;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Link_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Url_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Image_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Svg_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Video_Src_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Video_Attachment_Id_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Escaped_Html_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\Boolean_Prop_Type;
```

| Setting | Cómo se construye |
|---|---|
| `classes` | `Classes_Prop_Type::generate( [ '<PREFIJO>-banda', '<PREFIJO>-hero' ] )`. El array queda en `settings.classes.value`. |
| `tag`, `_cssid` | `String_Prop_Type::generate( 'section' )` |
| `title`, `paragraph`, `text` | `Escaped_Html_Prop_Type::generate( 'Texto con <strong>acento</strong>' )` |
| `link` | `Link_Prop_Type::generate( [ 'destination' => Url_Prop_Type::generate( home_url( '/ruta/' ) ), 'isTargetBlank' => Boolean_Prop_Type::generate( false ) ] )` |
| `image` | `Image_Prop_Type::generate( [ 'src' => Image_Src_Prop_Type::generate( [ 'id' => Image_Attachment_Id_Prop_Type::generate( $id ), 'url' => null, 'alt' => String_Prop_Type::generate( $alt ) ] ), 'size' => String_Prop_Type::generate( 'full' ) ] )` |
| `svg` | `Svg_Src_Prop_Type::generate( [ 'url' => Url_Prop_Type::generate( set_url_scheme( get_stylesheet_directory_uri(), 'https' ) . '/img/x.svg' ) ] )` |
| `source` (vídeo) | `Video_Src_Prop_Type::generate( [ 'id' => Video_Attachment_Id_Prop_Type::generate( $id ), 'url' => null ] )`. **Id o url, nunca ambos** [E3]. |
| booleanos | `Boolean_Prop_Type::generate( true )` |
| `attributes` | ver abajo |

`attributes` es una lista `key-value`. Sirve para enganchar comportamiento
(`data-<PREFIJO>-*`), `aria-*`, `role` o `lang`, y el cliente la edita en el panel:
```php
$attrs = function ( array $kv ) {
	$list = [];
	foreach ( $kv as $k => $val ) {
		$list[] = [ '$$type' => 'key-value', 'value' => [
			'key'   => [ '$$type' => 'string', 'value' => $k ],
			'value' => [ '$$type' => 'string', 'value' => (string) $val ],
		] ];
	}
	return [ '$$type' => 'attributes', 'value' => $list ];
};
```

`size` de `image`: `'full'` por defecto; baja a `'large'` o `'medium_large'` cuando la
imagen no ocupa la pantalla. Las rutas que empiezan por `/` se resuelven con
`home_url()`; con Polylang, las del segundo idioma necesitan su prefijo (`/en/…`) o
salen apuntando al idioma base (skill `wp-polylang`).

Las **props dinámicas** (`$$type: dynamic`: título, imagen destacada, campos, términos)
tienen forma y requisitos propios en CLI: `references/contenido-dinamico.md`, [X1] y
`scripts/e4-dynamic-tags-preambulo.php`.

### Constructores como closures

`wp eval-file` ejecuta el archivo en un ámbito local, así que los constructores
compartidos se escriben como closures en variables y se cargan con
`require __DIR__ . '/<archivo>.php'` [A5]. El núcleo del patrón probado:

```php
$box = function ( $type, array $classes, array $children, array $o = [] ) use ( $uid, $settings ) {
	return [
		'id' => $uid(), 'elType' => $type, 'version' => '0.4',
		'settings' => $settings( $classes, $o ), 'elements' => $children, 'styles' => [],
		'editor_settings' => isset( $o['title'] ) ? [ 'title' => $o['title'] ] : [],
	];
};
$flex = fn( $c, $ch, $o = [] ) => $box( 'e-flexbox', $c, $ch, $o );
$grid = fn( $c, $ch, $o = [] ) => $box( 'e-grid', $c, $ch, $o );
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
```
`$settings( $classes, $o )` traduce opciones cortas (`tag`, `link`, `attrs`, `cssid`) a
props. La versión completa y generalizada es `scripts/e4-helpers.php` (con `$img`, `$svg`, `$v3`,
`$con_ids`, `$estilo_local`, los constructores de valores y `$e4_guardar`).

### Vídeo de fondo

`e-background-video` lleva un hijo `e-background-video-content` (el contenido) y, si
hay controles, el árbol que genera el propio Elementor:

```php
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Background_Video\Atomic_Background_Video_Controls\Atomic_Background_Video_Controls;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Background_Video\Atomic_Background_Video_Play\Atomic_Background_Video_Play;
use Elementor\Modules\AtomicWidgets\Elements\Atomic_Background_Video\Atomic_Background_Video_Pause\Atomic_Background_Video_Pause;

$controles = $con_ids( Atomic_Background_Video_Controls::generate()
	->meta( [ 'required' => true ] )
	->editor_settings( [ 'title' => 'Controles' ] )
	->children( [ Atomic_Background_Video_Play::build_default_element( true ), Atomic_Background_Video_Pause::build_default_element( true ) ] )
	->build() );
```
Los builders no ponen `id` (en el editor lo pone el navegador) y sus textos salen en
inglés: pásalos por `$con_ids` (sección 4) y traduce después los `e-paragraph` hijos
de `e-background-video-play` y `-pause` [E9].

---

## 4. IDs deterministas

- Formato: **7 caracteres `[a-z0-9]`**. El endpoint `refresh-loop` exige
  `/^[a-zA-Z0-9]{7}$/` y el parser de `?e-filter-<id>-<tax>` exige `[a-z0-9]{7}`.
- Genera con una semilla por documento y un contador:
  `$uid = function () use ( &$__n, $E4_SEED ) { return substr( md5( $E4_SEED . '-' . ( ++$__n ) ), 0, 7 ); };`
  (así en `scripts/e4-helpers.php`: `$E4_SEED` se fija antes del `require`).
  Re-ejecutar el script produce los mismos ids, así que las referencias (filtro → grid,
  pruebas → formulario) y los mapas de traducción por id siguen valiendo.
- Los elementos a los que otros apuntan llevan un id **fijo y legible** de 7 caracteres
  (en el caso de referencia: `recgrid`, `recfilt`, `relgrid`).
- Para la copia en otro idioma, **reinicia el contador** (`$__n = 0`) antes de construirla:
  así tiene los mismos ids que la original.
- Insertar un elemento desplaza los ids posteriores. Es aceptable porque el documento
  se reescribe entero, pero no guardes fuera del script ids generados.
- Todo árbol que devuelva un builder de Elementor pasa por:
```php
$con_ids = function ( array $el ) use ( &$con_ids, $uid ) {
	if ( empty( $el['id'] ) ) { $el['id'] = $uid(); }
	if ( ! isset( $el['version'] ) ) { $el['version'] = '0.4'; }
	foreach ( $el['elements'] ?? [] as $i => $child ) { $el['elements'][ $i ] = $con_ids( $child ); }
	return $el;
};
```

---

## 5. Estilos: props, formas y variantes

`Style_Schema::get()` expone **73 propiedades CSS** en 4.3.0. El nombre del `$$type` no
siempre coincide con el de la propiedad; estas formas están verificadas:

| Propiedad | `$$type` y forma |
|---|---|
| tamaños (`font-size`, `width`, `max-width`, `inset-*`, `gap`…) | `size` = `{size, unit}`. Unidades usadas: `px`, `em`, `%`, `custom` (acepta `clamp()`, `calc()`, `min()`, `none`, `100svh`, `30ch`) y `auto` (con `size: ''`). Admite negativos. |
| `margin`, `padding` | `dimensions` = `{block-start, block-end, inline-start, inline-end}`, cada uno `size` |
| `border-width` | `border-width-v2`, mismas claves que `dimensions` |
| `border-radius` | `size` o `border-radius-v2` (claves `start-start`…) |
| `gap` doble | `layout-direction` = `{row, column}` |
| `flex` | `flex` = `{flexGrow, flexShrink, flexBasis}` con `number` dentro |
| `background` | `background` = `{color, background-overlay, clip}` |
| `color` | `color` (hex o `rgba(…)`) o `global-color-variable` (valor = **id** de la variable, no la etiqueta) |
| `font-family` | `font-family` (no `string`) |
| `display`, `position`, `object-fit`, `align-items`, `justify-content`, `aspect-ratio` (`'5 / 4'`), `grid-template-columns` (`'1.05fr 0.95fr'`, `'minmax(0, 1fr)'`), `grid-template-rows` (`'auto'`) | `string` |
| `text-align` | `string`, solo valores **lógicos** (`start`, `end`, `center`) [C8] |
| `z-index`, `order` | `number` |
| `opacity` | `size` en `%` |
| `box-shadow` | `box-shadow` = lista de `shadow` `{hOffset, vOffset, blur, spread, color, position}` |

Constructores de valores (patrón probado):
```php
$px   = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => 'px' ] ];
$em   = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => 'em' ] ];
$pct  = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => '%' ] ];
$cus  = fn( $s ) => [ '$$type' => 'size', 'value' => [ 'size' => $s, 'unit' => 'custom' ] ];
$auto = [ '$$type' => 'size', 'value' => [ 'unit' => 'auto', 'size' => '' ] ];
$none = [ '$$type' => 'size', 'value' => [ 'unit' => 'custom', 'size' => 'none' ] ];
$str  = fn( $s ) => [ '$$type' => 'string', 'value' => $s ];
$num  = fn( $n ) => [ '$$type' => 'number', 'value' => $n ];
$font = fn( $f ) => [ '$$type' => 'font-family', 'value' => $f ];
$var  = fn( $l ) => [ '$$type' => 'global-color-variable', 'value' => $V[ $l ] ]; // $V: etiqueta → id (sección 8)
$hex  = fn( $c ) => [ '$$type' => 'color', 'value' => $c ];
$bg   = fn( $c ) => [ '$$type' => 'background', 'value' => [ 'color' => $c ] ];
$dim  = function ( $bs = null, $be = null, $is = null, $ie = null ) use ( $px ) {
	$m = [];
	foreach ( [ 'block-start' => $bs, 'block-end' => $be, 'inline-start' => $is, 'inline-end' => $ie ] as $k => $v ) {
		if ( null !== $v ) { $m[ $k ] = is_array( $v ) ? $v : $px( $v ); }
	}
	return [ '$$type' => 'dimensions', 'value' => $m ];
};
$bw   = fn( $t, $r, $b, $l ) => [ '$$type' => 'border-width-v2', 'value' => [ 'block-start' => $px( $t ), 'inline-end' => $px( $r ), 'block-end' => $px( $b ), 'inline-start' => $px( $l ) ] ];
$gap2 = fn( $row, $col ) => [ '$$type' => 'layout-direction', 'value' => [ 'row' => $px( $row ), 'column' => $px( $col ) ] ];
$flexgs = fn( $g, $s ) => [ '$$type' => 'flex', 'value' => [ 'flexGrow' => $num( $g ), 'flexShrink' => $num( $s ) ] ];
$sombra = fn( $v, $b, $s, $a ) => [ '$$type' => 'shadow', 'value' => [ 'hOffset' => $px( 0 ), 'vOffset' => $px( $v ), 'blur' => $px( $b ),
	'spread' => $px( $s ), 'color' => $hex( "rgba(0, 0, 0, $a)" ), 'position' => null ] ];
$sombra_xl = [ '$$type' => 'box-shadow', 'value' => [ $sombra( 20, 25, -5, 0.1 ), $sombra( 8, 10, -6, 0.1 ) ] ];
```

### Copiar el prototipo tal cual

- Un tamaño fluido del prototipo va **literal** con unidad `custom` y una sola variante
  desktop: `'font-size' => $cus( 'clamp(2.3rem, 4.6vw, 3.9rem)' )`. Así cuadra en todos
  los anchos; los saltos por breakpoint (90/46/46…) nunca cuadran.
- `0.72rem` → `11.52px` exactos; el `letter-spacing` en `em` (`0.18em`).
- Las clases de sistema no llevan `line-height` si en el prototipo lo pone el contexto.
- Replica también los defaults implícitos del framework del prototipo (en Tailwind:
  interlineado 1.5 del cuerpo, `h1`–`h4` a 1.15, `space-y-2.5` = 10px).

### Variantes

Cada estilo es una lista de variantes; cada variante, un `meta` y sus `props`:
```php
$cls = function ( $d, $t = null, $m = null, $h = null ) {
	$v = [ [ 'meta' => [ 'breakpoint' => 'desktop', 'state' => null ], 'props' => $d ] ];
	if ( $t ) { $v[] = [ 'meta' => [ 'breakpoint' => 'tablet', 'state' => null ], 'props' => $t ]; }
	if ( $m ) { $v[] = [ 'meta' => [ 'breakpoint' => 'mobile', 'state' => null ], 'props' => $m ]; }
	if ( $h ) { $v[] = [ 'meta' => [ 'breakpoint' => 'desktop', 'state' => 'hover' ], 'props' => $h ]; }
	return $v;
};
// breakpoint no estándar (activo en el kit): 'tablet_extra'
[ 'meta' => [ 'breakpoint' => 'tablet_extra', 'state' => null ], 'props' => [ 'display' => [ '$$type' => 'string', 'value' => 'none' ] ] ];
```
- `breakpoint`: `desktop` y los activos en el kit (`tablet`, `mobile`, `tablet_extra`…).
- `state`: `null` o `hover`. La variante `hover` compila a `:hover, :focus-visible`
  juntos: declara **fondo y color** en ella [C10].
- Un valor `0` en una variante responsive (p. ej. `order: 0`) se descarta: usa `-1` u
  otro equivalente [C7].
- Cuando una variante cambia `position`, fija también `inset-block-start: auto` (`$auto`)
  o el desplazamiento de escritorio sigue actuando [D9].
- Para arreglar una variante existente, **búscala por su `meta`**, nunca por índice
  (`variants[0]`), que se rompe si cambia el orden.

---

## 6. Estilos locales (el panel Estilo de un elemento)

Para valores propios de **una sola instancia** (la posición de cada pin de un mapa, un
ajuste puntual) no crees una clase global: escribe un estilo local. Es lo que edita el
panel Estilo del elemento, así que el cliente lo ve y lo cambia en el editor (el CSS
del tema hijo no se ve ni se edita desde allí).

Formato exacto (en `scripts/e4-helpers.php` es `$estilo_local`, que admite además
breakpoint y estado):
```php
$posicion = function ( array $el, $x, $y ) {
	$sid = 'e-' . $el['id'] . '-' . substr( md5( 'pos' . $el['id'] ), 0, 7 );   // e-<id elemento>-<7 hex>
	$pc  = fn( $n ) => [ '$$type' => 'size', 'value' => [ 'size' => $n, 'unit' => '%' ] ];
	$el['styles'][ $sid ] = [ 'id' => $sid, 'type' => 'class', 'label' => 'local', 'variants' => [ [
		'meta'  => [ 'breakpoint' => 'desktop', 'state' => null ],
		'props' => [ 'inset-inline-start' => $pc( $x ), 'inset-block-start' => $pc( $y ) ],
	] ] ];
	$el['settings']['classes']['value'][] = $sid;   // sin esto, el estilo no se aplica
	return $el;
};
```
- Si el elemento aún no tiene `classes`, créalas antes con `Classes_Prop_Type::generate( [] )`:
  añadir solo `value` deja la prop sin `$$type` (`$estilo_local` ya lo hace).
- `$sid` = `e-<id del elemento>-<7 hex>`, con el hex determinista para que re-ejecutar
  no duplique estilos.
- El `$sid` va en `styles` **y** en `settings.classes.value`.
- Admite las mismas variantes que una clase global.

Verificación: el HTML servido lleva la clase `e-<id>-<hash>` en el elemento; en el
editor, Estilo › Posición muestra los valores.

---

## 7. Clases globales

Son el sistema de diseño del sitio: todo lo editable (layout, tipografía, color,
espaciado) vive aquí, con prefijo de proyecto (`<PREFIJO>-…`).

### Dónde viven

- Cada clase es un post del tipo `e_global_class` (en el caso de referencia, 243 posts
  para 243 clases).
- El **orden** vive en dos metas del kit activo: `_elementor_global_classes_order`
  (contexto *frontend*) y `_elementor_global_classes_order_preview` (contexto
  *preview*, el que lee el editor). Al leerlas pueden venir como array o como JSON con
  clave `order`: decodifica antes de comparar.
- El repositorio declara además `META_KEY_FRONTEND = '_elementor_global_classes'` y
  `META_KEY_PREVIEW = '_elementor_global_classes_preview'`.
- No escribas estas metas a mano: usa el repositorio y la clase de orden.

### Formato de un item

```php
$items[ $id ] = [ 'id' => $id, 'type' => 'class', 'label' => $id, 'variants' => $variants ];
```
Usa `id` = `label` (legible, con prefijo). La clase compila a `.elementor .<nombre>`
(especificidad 0,2,0) en `uploads/elementor/css/global-<id documento>-frontend-<breakpoint>.css`,
así que el nombre legible sirve también para aplicarla desde `_css_classes` de un
widget V3 **si algún elemento V4 del documento la usa** [C9]. Nombres de al menos
3 caracteres [C6].

### Escribir: leer, fusionar, validar, escribir, sincronizar

`put()` recibe el conjunto **entero** de items y el orden entero; no es un upsert. En
contexto frontend, **borra** los posts de las clases del orden actual que falten en `$items` y dispara
`elementor/global_classes/cleanup`, que las quita de los documentos (código de
`global-classes-repository.php` en 4.3.0) [C2]. El único patrón admitido (plantilla
completa en `scripts/e4-clases-upsert.plantilla.php`):

```php
use Elementor\Plugin;
use Elementor\Modules\GlobalClasses\Global_Classes_Repository;
use Elementor\Modules\GlobalClasses\Global_Classes_Parser;

$repo  = Global_Classes_Repository::make();
$all   = $repo->all( true );
$items = $all->get_items()->all();   // Collection → array
$order = $all->get_order()->all();

foreach ( $new as $id => $variants ) { $items[ $id ] = [ 'id' => $id, 'type' => 'class', 'label' => $id, 'variants' => $variants ]; }
$order = array_values( array_merge( array_keys( $new ), array_diff( $order, array_keys( $new ) ) ) );

$parsed = Global_Classes_Parser::make()->parse( [ 'items' => $items, 'order' => $order ] );
if ( ! $parsed->is_valid() ) { foreach ( $parsed->errors()->all() as $e ) { echo "ERROR {$e['key']} -> {$e['error']}\n"; } return; }
$repo->put( $items, $order );
Plugin::$instance->files_manager->clear_cache();
// y SIEMPRE, al final: sincronizar el contexto preview (abajo)
```
El parser señala la prop exacta: `items.<clase>.variants[0].font-family -> invalid_value`.
Imprime el número de clases antes y después: no debe bajar salvo que lo pretendas.
Prueba primero con **una** clase antes de lanzar un sistema completo.

### Prioridad: gana la PRIMERA del orden

Si dos clases de un elemento escriben la misma propiedad, gana la que va **antes en el
orden global**, no la última del atributo `class` [C4]. Consecuencias de diseño:

1. **Una propiedad, una familia de clases.** Tipografía sin color ni margen; el color
   en `<PREFIJO>-tono-*`, el espaciado en `<PREFIJO>-mt-*`, el ancho en `<PREFIJO>-mw-*`.
   Los contenedores fijan padding y width explícitos (sección 12).
2. **Los modificadores van delante de su base** en el orden, de forma explícita:
   ```php
   $front = [ '<PREFIJO>-btn--sm', '<PREFIJO>-centrado' ];
   $order = array_values( array_merge( $front, array_diff( $order, $front ) ) );
   printf( "--sm %d < btn %d\n", array_search( '<PREFIJO>-btn--sm', $order ), array_search( '<PREFIJO>-btn', $order ) );
   ```
3. Para anular una propiedad de una base en un caso, crea un modificador (`--pegado`,
   con `padding:0`) en vez de editar la base: cambiar una base es global.
4. Anteponer las clases nuevas hace que la prioridad dependa del **historial de
   ejecución**: re-ejecutar un script antiguo cambia quién gana. Recomendación (no se
   llegó a construir en el caso de referencia): un registro declarativo del orden por
   capas fijas (modificadores y utilidades → componentes → sistema) que se reaplica
   entero en cada script de clases.

### Contextos frontend y preview: sincroniza SIEMPRE

`Global_Classes_Repository::put()` solo escribe el orden del contexto en el que corre
(frontend). El editor lee el contexto **preview** y, al publicar cualquier página, borra
del sitio (`wp_delete_post` forzado) las clases que no ve allí y las quita de los
documentos [C1]. Al final de **todo** script que cree o modifique clases:

```php
use Elementor\Plugin;
use Elementor\Modules\GlobalClasses\Global_Classes_Order;

$kit   = Plugin::$instance->kits_manager->get_active_kit();
$front = Global_Classes_Order::make( $kit )->get_order();
$prev  = Global_Classes_Order::make( $kit )->set_preview( true );
$solo_preview = array_diff( $prev->get_order(), $front );   // pueden ser del usuario: se conservan al final
$prev->set_order( array_values( array_merge( $front, $solo_preview ) ) );
Plugin::$instance->files_manager->clear_cache();
printf( "frontend %d · preview %d\n", count( $front ), count( Global_Classes_Order::make( $kit )->set_preview( true )->get_order() ) );
```
Es `scripts/e4-sync-preview.php`: cierra cada script de clases con su `require`, no como
paso manual, y ejecútalo suelto después de migrar o ante la duda.

Comprobaciones:
- Metas del kit: `_elementor_global_classes_order` y `_elementor_global_classes_order_preview`
  iguales, y su recuento igual al número de posts `e_global_class`.
- REST como usuario 1 (`wp_set_current_user( 1 )` + `rest_do_request`):
  `GET /elementor/v1/global-classes?context=preview` y `?context=frontend` con el mismo
  número de items; `GET /elementor/v1/global-classes/styles?context=preview&ids=<muestra>`
  sin estilos `null`. En el caso de referencia el recuento de **orden** por REST salía 0
  por una forma de respuesta distinta: fíate del recuento de items y de las metas.
- Repite la comprobación **después de migrar** y antes de que nadie publique
  (skill `wp-migracion`).

### Sondas útiles

- **Clases que faltan antes de construir:** extrae con regex los nombres que usa el
  build y compáralos con el repositorio.
  ```php
  $items = Global_Classes_Repository::make()->all( true )->get_items()->all();
  $src = file_get_contents( __DIR__ . '/pagina-build.php' ) . file_get_contents( __DIR__ . '/e4-helpers.php' );
  preg_match_all( "/'(<PREFIJO>-[a-z0-9_-]+)'/", $src, $m );   // sustituye <PREFIJO> por el prefijo real
  echo 'faltan: ' . implode( ', ', array_diff( array_unique( $m[1] ), array_keys( $items ) ) ) . "\n";
  ```
- **Volcado legible** de clases concretas con argumento de CLI: `scripts/e4-dump-clases.php`
  (`wp eval-file e4-dump-clases.php "<PREFIJO>-a,<PREFIJO>-b"`, que llega en `$args[0]`), con
  cada variante como `<PREFIJO>-x @tablet:hover — padding: {block-start=0px …}; color: var:<id>`.
- **Antes de retirar una clase**, búscala en todos los `_elementor_data`: un documento
  que la referencie se queda sin estilo sin avisar.

---

## 8. Variables V4

Las variables (color, fuente, medida) viven en la meta `_elementor_global_variables` del
kit activo. Se leen y crean con el servicio, nunca con la meta en bruto:

```php
use Elementor\Plugin;
use Elementor\Modules\Variables\Storage\Variables_Repository;
use Elementor\Modules\Variables\Services\Variables_Service;
use Elementor\Modules\Variables\Services\Batch_Operations\Batch_Processor;

$svc = new Variables_Service( new Variables_Repository( Plugin::$instance->kits_manager->get_active_kit() ), new Batch_Processor() );
$svc->create( [ 'type' => 'global-color-variable', 'label' => 'color-marca', 'value' => '<HEX_MARCA>' ] );
// también 'global-font-variable' ('<FUENTE_TEXTO>') y 'global-size-variable' ('1320px')

$list = $svc->get_variables_list(); $data = $list['data'] ?? $list; $V = [];
foreach ( $data as $id => $v ) { if ( is_array( $v ) && isset( $v['label'] ) ) { $V[ $v['label'] ] = $id; } } // etiqueta → id
Plugin::$instance->files_manager->clear_cache();
```

- Etiquetas en **kebab-case**, sin espacios y únicas (`Variable::validate` rechaza los
  espacios) [C11].
- `create()` falla si la etiqueta ya existe: un script que solo crea es seguro pero no
  idempotente. `scripts/e4-variables.plantilla.php` lista, indexa por etiqueta y crea solo
  las que faltan.
  El método de actualización del servicio no se usó en el caso de referencia:
  compruébalo en `modules/variables/services/` antes de escribir uno.
- En las clases, `global-color-variable` pide el **id**: resuélvelo siempre por etiqueta.
- Salen en `:root` como `--<etiqueta>`. El CSS del tema hijo puede usarlas con respaldo
  (`var(--color-marca, <HEX_MARCA>)`), así un cambio de color en Elementor llega también al
  CSS propio. Si el cliente **renombra** la etiqueta, el hijo cae al respaldo:
  documéntalo en la entrega.
- Verifica leyendo por API en un proceso nuevo, contando por `type`.

---

## 9. Widgets V3 dentro de V4

Lo que V4 aún no resuelve se hace con widgets clásicos dentro del maquetado atómico:
`nav-menu` (menús editables en Apariencia › Menús), `form` de Pro, `loop-grid` y
`taxonomy-filter`, `shortcode`, `text-editor` (texto con shortcodes, p. ej. el año del
copyright).

Forma: settings planos, sin `$$type`, sin `version` ni `styles`:
```php
$nav = [
	'id' => $uid(), 'elType' => 'widget', 'widgetType' => 'nav-menu',
	'settings' => [
		'menu'         => 'principal-<IDIOMA_BASE>',   // por SLUG, no por ubicación
		'layout'       => 'horizontal',
		'pointer'      => 'none',
		'dropdown'     => 'tablet_extra',   // no existe 'laptop' (sección 11)
		'toggle'       => 'burger',
		'full_width'   => 'stretch',
		'submenu_icon' => [ 'value' => 'fas fa-caret-down', 'library' => 'fa-solid' ],
		'_css_classes' => '<PREFIJO>-nav',
	],
	'elements' => [],
];
$texto = [
	'id' => $uid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
	'settings' => [ 'editor' => '© [<PREFIJO>_anio] Marca. Todos los derechos reservados.', '_css_classes' => '<PREFIJO>-legal' ],
	'elements' => [],
];
```
(`text-editor` ejecuta shortcodes: `parse_text_editor()` hace `do_shortcode`.)

Reglas:
- **Envuelve cada widget V3 en un `e-flexbox` «caja»**: una clase con `flex-direction:
  column`, `align-items: stretch`, `gap:0`, `padding:0` y `width:100%`, más la utilidad
  de margen. Motivo doble: el CSS global de la página solo incluye las clases que usa
  algún elemento V4 [C9], y el `e-flexbox` es fila por defecto, con lo que un
  formulario (inputs con `size="1"`) se colapsa [D13].
- Al widget V3 dale solo `_css_classes` con una clase propia y estilízalo desde el CSS
  del tema hijo, con selectores que empiecen por `.elementor ` (sección 12 y
  `references/tema-hijo.md`).
- En una fila flex, pon `_element_width => 'auto'` al widget V3 para que no se estire.
- `_attributes` de un widget V3 es **texto** `clave|valor`, una pareja por línea
  (Avanzado › Atributos). Sirve para textos editables que el widget no expone:
  `"data-<PREFIJO>-enviando|Enviando…\ndata-<PREFIJO>-error|No pudimos enviar el mensaje."`.
- Los widgets V3 heredan la tipografía y los colores **de sistema** del kit
  (`primary`, `text`, `accent`): ver sección 10.
- Detalle por widget: formularios en `references/formularios.md`; Loop Grid y filtro en
  `references/contenido-dinamico.md`; menús en `references/theme-builder.md`.

---

## 10. Kit

El kit activo (`get_option( 'elementor_active_kit' )`) es un post `elementor_library`
cuyos ajustes viven en la meta `_elementor_page_settings`.

**Nunca `$kit->save()` desde CLI**: los controles del kit no están cargados y el guardado
descarta las claves que no reconoce [K1]. Se lee, se modifica y se escribe la meta
(plantilla con modo en seco en `scripts/e4-kit-breakpoints.plantilla.php`):

```php
use Elementor\Plugin;
$kit_id = (int) get_option( 'elementor_active_kit' );
$raw = get_post_meta( $kit_id, '_elementor_page_settings', true );
$raw['active_breakpoints']    = [ 'viewport_mobile', 'viewport_tablet', 'viewport_tablet_extra' ];
$raw['viewport_tablet_extra'] = 1279;
unset( $raw['viewport_laptop'] );
update_post_meta( $kit_id, '_elementor_page_settings', $raw );
Plugin::$instance->breakpoints->refresh();
\Elementor\Core\Breakpoints\Manager::compile_stylesheet_templates();
Plugin::$instance->files_manager->clear_cache();
```

- Antes y después cuenta `system_colors`, `custom_colors`, `system_typography` y
  `custom_typography`, y mira `container_width`, `link_normal_color` y
  `link_hover_color`: nada debe desaparecer.
- Globals V3 del kit (para widgets V3 y para el cliente), formato:
  `[ '_id' => 'primary', 'title' => 'Color de marca', 'color' => '<HEX_MARCA>' ]` en colores;
  en tipografías, `'typography_typography' => 'custom'` más `typography_font_family`,
  `typography_font_weight`, `typography_font_size` (y `_tablet`, `_mobile`)…
- **Tipografía y colores de sistema neutros.** Los widgets V3 usan por defecto
  `var(--e-global-typography-primary-*)` y `var(--e-global-color-text)`. En el caso de
  referencia `primary` era la tipografía de cartel y el menú salió a 62px en esa
  fuente [K4].
- El color de enlaces del kit compila como `.elementor-kit-<id> a` (0,1,1), que gana a
  una clase simple del hijo (0,1,0), y `.elementor-kit-<id> a:hover` (0,2,1), que gana
  al color base de una clase global (`.elementor .<clase>`, 0,2,0) si su variante hover
  no declara `color` (la variante, `.elementor .<clase>:hover`, es 0,3,0 y sí gana).
  Revísalo antes de crear botones y componentes que se pinten como `<a>` [C10].
- El CSS del kit (`post-<kit>.css`) se genera perezosamente cuando algo construido con
  Elementor se pinta: en un sitio vacío no existe y no hay `--e-global-color-*` en el
  HTML. Verifica las escrituras leyendo la meta, no el CSS [K2].
- El kit **no lleva idioma** (Polylang): skill `wp-polylang`.

---

## 11. Breakpoints

Por defecto: `mobile` ≤767 y `tablet` ≤1024. Los adicionales se activan en el kit
(sección 10) y se verifican con `get_active_breakpoints()` **en un proceso nuevo**: el
gestor cachea dentro del proceso [A8].

Correspondencia usada con un prototipo Tailwind:

| Tailwind | Elementor |
|---|---|
| `xl` (1280) | `tablet_extra` = **1279** |
| `lg` (1024) | `tablet` (≤1024) |
| `md` (768) | `mobile` (≤767) |
| `sm` (640) | sin equivalente: `@media (min-width:640px) and (max-width:767px)` en el CSS del hijo |

- **Colapso del menú en el `xl` del prototipo:** el `nav-menu` de Pro solo colapsa en
  `mobile`, `mobile_extra`, `tablet`, `tablet_extra` o `none`, **nunca en `laptop`** [K3].
  Activa `tablet_extra = 1279`, deja `laptop` inactivo, pon `dropdown => 'tablet_extra'`
  y crea una clase de visibilidad (`<PREFIJO>-solo-escritorio`) con variante
  `tablet_extra` y `display:none`. Consulta qué admite cada widget clásico **antes** de
  fijar los breakpoints del sitio.
- `active_breakpoints` se escribe completo: lee el valor actual antes de sobrescribirlo.
- A exactamente 1024px Elementor aplica `tablet` y Tailwind ya aplica `lg`: diferencia
  de un píxel de ancho, aceptada y documentada en el caso de referencia.
- Las variantes de clase usan el nombre del breakpoint (`'tablet_extra'`), y los
  widgets V3 sus sufijos (`columns_tablet_extra`).

---

## 12. Estilos base de los contenedores

Los contenedores atómicos traen estilos que no ves en el panel y que descuadran la
réplica en silencio (`base-desktop.css` de Elementor 4.3.0):

| Selector | Qué trae |
|---|---|
| `.elementor .e-flexbox-base` (0,2,0) | `padding:10px`, `display:flex`, `flex-direction:row`; vacío, `min-width:30px` |
| `.elementor .e-div-block-base` | `padding:10px`, `display:block`, `min-width:30px` |
| `.e-grid-base` | `padding:10px`, `display:grid`, `gap:20px`, 3 columnas × 2 filas |
| `.e-background-video-content-base` | `padding:10px` |
| `.e-con` | `width:100%`, `position:relative`; su `::before` es `position:absolute` arriba a la izquierda (capa de fondo) |

Reglas:
- Toda clase de contenedor fija **explícitamente** `padding` (0 si no hay), `width`
  (`auto` si no debe estirarse) y `flex-direction`. Un `dimensions` con solo
  `inline-*` deja los 10px verticales.
- Ten clases «pila / col / caja» con `padding:0` y un contenedor de página que solo
  lleve el padding horizontal; una «banda» de sección y otra «banda libre» sin padding
  ni overflow (para `sticky`).
- Cajas vacías (iconos, pines, rayas): `min-width:0` (y `min-height:0`).
- `e-grid`: `grid-template-rows: auto` siempre, y variante `mobile` explícita de
  `grid-template-columns` (`minmax(0, 1fr)`) aunque la de tablet ya lo resuelva [D5].
- Iconos pintados con `::before` dentro de un `.e-con`: `position:absolute; inset:0;
  margin:auto` más su ancho y alto.
- Un hijo absoluto se posiciona respecto al `.e-con` más cercano: si debe referirse a un
  ancestro más lejano, `position: static` en los intermedios.
- Reparto: en clases V4 va lo editable (layout, tipografía, color, espaciado). En el CSS
  del hijo, solo lo que V4 no expresa (pseudo-elementos, degradados y velos,
  `writing-mode`, `scroll-snap`, hover entre elementos, animaciones, widgets V3), con
  selectores que empiecen por `.elementor ` también dentro de `@media` de una línea
  (`references/tema-hijo.md`).

Auditoría por página (debe devolver `total:0`; incluye cabecera y pie):
```js
const bad=[];document.querySelectorAll('[data-elementor-id] .e-flexbox-base, [data-elementor-id] .e-grid-base, [data-elementor-id] .e-div-block-base, [data-elementor-id] .e-background-video-content-base').forEach(e=>{const s=getComputedStyle(e);const sides=['Top','Right','Bottom','Left'].filter(k=>s['padding'+k]==='10px');if(sides.length){bad.push({doc:e.closest('[data-elementor-id]').dataset.elementorId,cls:[...e.classList].filter(c=>c.startsWith('<PREFIJO>-')).join(' '),sides:sides.join('')})}});({total:bad.length,bad:bad.slice(0,20)})
```

---

## 13. Guardar y releer

```php
$doc = \Elementor\Plugin::$instance->documents->get( $id, false );
$ok  = $doc->save( [ 'elements' => $arbol, 'settings' => $ajustes ] );   // 'settings' es opcional
if ( false === $ok ) { WP_CLI::error( "save() falló en #$id" ); }       // nunca ! $ok: Loop::save() devuelve null
\Elementor\Plugin::$instance->files_manager->clear_cache();
$guardado = json_decode( get_post_meta( $id, '_elementor_data', true ), true );   // relee y resume
```

- En `scripts/e4-helpers.php` todo esto es `$e4_guardar( $id, $arbol, $ajustes )`:
  comprueba el retorno, fusiona los ajustes, guarda en el idioma del documento y resume.
- Ejecuta siempre con `--user=1`: sin usuario, `save()` devuelve `false` sin avisar y no
  persiste nada [B1].
- `save()` **pinta** el documento para generar el texto plano (`post_content`, que usa
  Yoast). Con Polylang, cambia `PLL()->curlang` al idioma del documento durante el
  guardado [B6].
- Prefiere `save()` a escribir `_elementor_data` a mano: valida, sanea, regenera el CSS y
  el texto plano [B7].
- Los ajustes del documento (`settings`) **reemplazan** la meta `_elementor_page_settings`
  entera: fusiona con lo que había y quita la clave basura `0` [B2]:
  ```php
  $prev = $doc->get_meta( '_elementor_page_settings' );
  $prev = is_array( $prev ) ? $prev : [];
  unset( $prev[0] );
  $ajustes = array_merge( $prev, $nuevos );
  ```
- Una página nueva, lista para Elementor con cabecera y pie del Theme Builder:
  `wp_insert_post` + `update_post_meta( $id, '_elementor_edit_mode', 'builder' )` +
  `update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' )` (y su
  idioma con Polylang). Mantén los slugs del prototipo para que valga su mapa de
  redirecciones.
- Para cambios quirúrgicos por id de elemento en documentos ya construidos (que quizá el
  cliente ya editó), usa `scripts/e4-parche-por-id.plantilla.php`, con modo en seco.
- Para modificar un documento existente, recorre `get_elements_data()` con un walker
  por referencia que compruebe `isset( $el['settings'] )` antes de tomar la
  referencia [B3], cambia solo lo que busca, comprueba si el cambio ya está (idempotente)
  y guarda:
  ```php
  $doc  = \Elementor\Plugin::$instance->documents->get( $id, false );
  $data = $doc->get_elements_data();
  $walk = function ( &$els, $padre = null ) use ( &$walk ) {
  	foreach ( $els as &$e ) {
  		if ( 'nav-menu' === ( $e['widgetType'] ?? '' ) && isset( $e['settings'] ) ) { $e['settings']['dropdown'] = 'tablet_extra'; }
  		if ( ! empty( $e['elements'] ) ) { $walk( $e['elements'], $e['elType'] ); }
  	}
  	unset( $e );
  };
  $walk( $data );
  if ( false === $doc->save( [ 'elements' => $data ] ) ) { WP_CLI::error( "save() falló en #$id" ); }
  ```
- Imprime siempre un resumen verificable: secciones y número de elementos por sección,
  o el árbol con tipo y clases (`· e-flexbox [<PREFIJO>-banda <PREFIJO>-hero]`). Si no
  aparece la última línea, el script no terminó.
