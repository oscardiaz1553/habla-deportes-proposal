# Tema hijo: frontera con Elementor y catálogo de patrones

Qué va en Elementor (lo edita el cliente) y qué va en el tema hijo de Hello (código),
cómo organizar el child para que dos sesiones lo toquen sin pisarse, y los patrones
que resolvieron problemas reales en el caso de referencia (HomeMaker), cada uno con
el problema, por qué necesita código y cómo generalizarlo.

Verificado sobre Hello Elementor 3.5.1 · Elementor 4.3.0 · Pro 4.2.3 · Polylang Pro
3.8.6 · Yoast 27.7 · WP 7.1 · PHP 8.2. Los archivos completos (saneados) están en
`../../wp-elementor-replica/ejemplos-homemaker/tema-hijo/`.

**Convención de los fragmentos.** Son patrones genéricos: `pfx_` / `pfx-` / `data-pfx-*` es el
`<PREFIJO>` (en el caso de referencia, `hm`). Renómbralo en funciones, constantes, clases CSS,
atributos, shortcodes, metas y handles. El código real de HomeMaker, con `hm`, está en
`../../wp-elementor-replica/ejemplos-homemaker/tema-hijo/`.

## Lo primero, siempre

1. **La frontera va escrita en la cabecera de `functions.php`:** «El diseño vive en
   Elementor: variables y clases globales del kit activo. Aquí solo va lo que
   Elementor no puede expresar por sí mismo».
2. **El child vive en git desde el día 1** (aunque sea un repo local que se sincroniza
   con `rsync` antes y después de cada despliegue). La verdad está en el servidor:
   nunca subas una carpeta local completa ni reconstruyas el child desde copias
   parciales (§2).
3. **Estáticos versionados con `filemtime()`** y, tras cada subida, purga de la caché de
   página y `curl` buscando una marca del cambio (§3).
4. **No instales Child Theme Configurator.** Si ya está, pide al usuario que lo
   desactive o neutralízalo (§3).
5. **Todo selector del child que toque elementos atómicos empieza por `.elementor `**,
   también dentro de `@media` de una línea (§6).
6. **Nunca heredes de una clase concreta de Elementor.** Envuelve lo registrado (§5).

## 1. La frontera

| Va en Elementor (lo edita el cliente) | Va en el child (código) |
|---|---|
| Maquetado V4, tipografías, colores (variables), clases globales, textos, imágenes | CPT, metas y taxonomías; shortcodes de datos |
| Menús (Apariencia › Menús) y plantillas del Theme Builder | Integraciones por hooks: Polylang, Pro Forms, Theme Builder, Yoast, plugin de seguridad |
| Campos, destinatarios, asunto y mensajes de los formularios | Lo que una clase V4 no expresa: pseudo-elementos, degradados, `writing-mode`, `scroll-snap`, `:has()`, hover entre elementos, máscaras, animaciones |
| Consulta y columnas del Loop Grid | Estilo completo del `nav-menu` clásico y de las partes del formulario que el widget no expone |
| IDs de sección para anclas; atributos `data-pfx*` y textos en atributos (Avanzado › Atributos) | Pantalla de acceso, rendimiento y accesibilidad transversal |

Prueba rápida: si el cliente puede cambiarlo desde Elementor sin tocar código, va en
Elementor. Si es un dato estructurado, una integración por hook o CSS que las clases
V4 no expresan, va en el child.

Cuatro principios que hicieron el código mantenible:

- **Editable primero, con respaldo por idioma.** Cada texto visible que pinta el child
  sale, por este orden, de un atributo editable en Elementor
  (`data-pfx-enviando`, `data-anterior`, el texto visible de un botón copiado a su
  `aria-label`, atributos de shortcode), de un respaldo por idioma y, por último, del
  texto en el idioma base. Al añadir un idioma, todo literal del child necesita su
  respaldo: `grep -rn "'[A-ZÁÉÍÓÚ][a-záéíóúñ]\+" inc js` los lista.
- **Suma, no pisa.** Cuando el código toca configuración del cliente, la respeta: la
  consulta del Loop Grid se fusiona, el cuerpo del correo solo se reescribe si sigue
  en `[all-fields]`, `wp_mail_from` solo sustituye el valor por defecto de WordPress,
  solo se anulan las Google Fonts que sirve el child.
- **Enganches por atributo, no por clase de estilo.** JS y CSS de comportamiento se
  enganchan a `data-pfx="…"`, `data-pfx-reveal`, `data-pfx-icono`… puestos en
  Avanzado › Atributos. El cliente puede cambiar clases sin romper nada; en la
  entrega queda escrito que esos atributos no se borran.
- **Falla en seguro.** `function_exists( 'pll_*' )` en todo lo que toca Polylang;
  `class_exists` + Reflection antes de tocar clases internas; guardas de reentrada en
  hooks de consulta; parches JS con bandera («ya parcheado») y `typeof … === 'function'`;
  regex sobre HTML de Elementor que, si no casa, devuelve el HTML igual; mapas de
  traducción exactos que devuelven la original si no hay coincidencia.

## 2. Organización del child

```
<child>/
├── style.css           cabecera del tema + CSS por bloques comentados
├── functions.php       require_once + pegamento corto (encolado, [idioma], extras del menú, <main>, bloque Polylang/TB)
├── inc/
│   ├── <contenido>.php  CPT, metas, shortcodes, Query IDs, idioma del filtro, ?cat= → contenido-dinamico.md
│   ├── formularios.php  antispam, mensajes, cuerpo, remitente → formularios.md
│   ├── rendimiento.php  fuentes, transformadores, imágenes, jQuery, vídeo
│   ├── accesibilidad.php panel móvil, gettext de Pro, autocomplete
│   ├── seo.php (+ schema-<tipo>.php)  lo que Yoast no expresa desde su panel
│   ├── proximamente.php modo mantenimiento por idioma
│   └── acceso.php       pantalla de login con marca (+ css/login.css)
├── js/                 home.js (comportamiento por data-*), pfx-form-timer.js
├── css/login.css
├── fonts/              woff2 autoalojados
└── img/                cursor, póster del hero, SVG decorativos
mu-plugins/             lo que tiene que correr antes que el tema (Polylang)
```

- **Un `inc/` por dominio.** `functions.php` solo lleva `require_once` y pegamento.
  Cada `inc/<dominio>.php` empieza con un bloque que explica qué resuelve y por qué no
  se hace en Elementor; cada hook lleva 1–3 líneas con la causa («el menú puede llegar
  como slug, id u objeto: Polylang lo cambia por el id…»). Esos comentarios
  alimentaron la tabla «archivo → qué hace» del documento de entrega y los leyó la otra
  sesión para entender el código sin preguntar (ver
  `../../wp-elementor-replica/plantillas/documento-entrega.md`).
- **Un `require_once` sin su archivo es un fatal en todo el sitio.** Antes de subir o
  empaquetar:
  `grep -o "require_once __DIR__ . '/inc/[a-z-]*\.php'" functions.php` contra
  `ls inc/`, y `find . -name '*.bak'` vacío.

### Dueño de cada archivo con sesiones paralelas

En el caso de referencia tres sesiones trabajaron en paralelo sobre el mismo WordPress
(construcción, SEO y rendimiento, documento de entrega); las dos primeras editaron el
child. Reparto que funcionó:

| Archivo o recurso | Dueño | Los demás |
|---|---|---|
| `functions.php` | construcción | solo añaden su línea `require_once`, detrás de la anterior y en el orden pactado |
| `style.css`, `js/home.js` | construcción | parches mínimos, con guarda de md5 y aviso antes y después |
| `inc/<contenido>.php`, `inc/formularios.php`, `js/pfx-form-timer.js` | construcción | — |
| `inc/proximamente.php`, `inc/acceso.php`, `css/login.css` | construcción | `acceso.php` se separó **a propósito** para no tocar archivos de la otra sesión |
| `inc/rendimiento.php`, `inc/accesibilidad.php`, `inc/seo.php`, schema, `fonts/`, póster | SEO/rendimiento | si la construcción necesita algo ahí (p. ej. que el envoltorio del transformador de imagen conserve el alt del idioma 2), lo pide por mensaje |
| mu-plugin de idioma en ajax / mu-plugin sin cookie de idioma | construcción / SEO | — |
| Cadenas de Polylang, documentos del idioma 2, `.htaccess` | **un solo escritor** por recurso | los documentos del idioma 2 se regeneran por script; la otra sesión solo edita los del idioma base |

Protocolo de subida de un archivo compartido: bajar la versión del servidor y guardar
su md5 → parchear con reemplazo exacto (`assert s.count(old) == 1`) → `php -l` → justo
antes de `scp`, comparar el md5 remoto con el guardado (si cambió, no subas y vuelve a
bajar) → subir y comparar md5 local y remoto (un `scp` cortado a medias no para el
flujo) → purga → `curl` con marca del cambio en las URLs de cada idioma → mensaje a la
otra sesión con las líneas y el md5 final. Detalle y mensajes tipo:
`../../wp-elementor-replica/references/sesiones-paralelas.md`.

## 3. Versionado de estáticos y la trampa de Child Theme Configurator

```php
/* Hello 3.x ya encola su reset como 'hello-elementor'; el style.css del padre es solo
   la cabecera del tema: no se encola. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'pfx-child', get_stylesheet_uri(), [ 'hello-elementor' ], (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
	$js = get_stylesheet_directory() . '/js/home.js';
	wp_enqueue_script( 'pfx-home', get_stylesheet_directory_uri() . '/js/home.js', [], (string) filemtime( $js ), [ 'in_footer' => true, 'strategy' => 'defer' ] );
}, 20 );

/* Child Theme Configurator reescribe el ?ver= de toda hoja cuya URL contenga el slug
   del child con la versión de la cabecera de style.css. */
add_action( 'wp_head', function () {
	remove_filter( 'style_loader_src', 'chld_thm_cfg_version' );
}, 0 );
add_action( 'login_enqueue_scripts', function () {
	remove_filter( 'style_loader_src', 'chld_thm_cfg_version' ); // el login no ejecuta wp_head
	/* …y aquí se encola css/login.css con filemtime y dependencia [ 'login' ] */
} );
```

- **Por qué `filemtime`:** los hostings sirven los estáticos con `max-age` de un año;
  cada cambio tiene que cambiar la URL.
- **La trampa (síntoma → causa):** el CSS salía con `?ver=0.5.0` (la versión de la
  cabecera) en lugar de la fecha. Se diagnosticó primero como opcache y hubo que
  corregirlo ante el usuario. Era Child Theme Configurator, instalado para el child de
  un tema anterior: engancha `chld_thm_cfg_version` a `style_loader_src`. Purgar la
  caché no lo arregla porque no es caché. En el kickoff, `wp plugin list --status=active`
  y marca los plugins heredados de temas o proyectos anteriores. Desactivarlo lo
  decide el usuario; mientras tanto, quita su filtro en **los dos** sitios.
- **Purga siempre después de subir.** La caché de página guarda el HTML con el `?ver=`
  viejo incrustado: aunque el archivo sea nuevo, el HTML cacheado sigue pidiendo el
  anterior («la corrección no funciona»).
- **Comprobación:**
  `curl -s "<URL>?nocache=$RANDOM" | grep -o 'themes/<child>/style.css?ver=[0-9]*'`
  debe dar el mismo número que `stat -c %Y style.css` en el servidor (y lo mismo con
  `css/login.css` en `wp-login.php`).
- **Para romper la caché en pruebas usa `?nocache=$RANDOM`, nunca `?m=`**: `m` es la
  variable de archivo por fecha de WordPress y devuelve 404. Usa también un
  User-Agent de navegador (`curl -A`): algunos hostings responden con un reto
  anti-bot al UA de curl.

## 4. Catálogo de patrones

Formato: **Problema** · **Por qué código** · **Generaliza**. Los de contenido y
formularios tienen su propia referencia; aquí van resumidos.

### `functions.php`

**Selector de idioma como enlaces** (`[pfx_idioma]`).
Problema: réplica exacta del selector del prototipo. Por qué código: el widget de
Polylang no da ese marcado. Generaliza: `pll_the_languages( [ 'raw' => 1, 'hide_if_empty' => 0, 'hide_if_no_translation' => 0 ] )`,
enlaces (cada idioma es otra URL, no un botón), `aria-current` en el activo,
`<nav aria-label>`, y selectores `.pfx-lang .pfx-lang__opt` con dos clases para ganar al
color de enlaces del kit (`.elementor-kit-N a`), que ponía el inactivo verde sobre
verde. Declara también `:hover` y `:focus` del enlace.

**Extras solo en el panel móvil del menú** (botón de contacto y fila de idioma).
Problema: el menú móvil del prototipo tiene elementos que el de escritorio no.
Por qué código: el `nav-menu` de Pro no los ofrece; genera una copia del menú con
`menu_type = 'dropdown'` para móvil. Generaliza:

```php
add_filter( 'wp_nav_menu_items', function ( $items, $args ) {
	if ( ( $args->menu_type ?? '' ) !== 'dropdown' ) {
		return $items; // solo la copia móvil del nav-menu de Pro
	}
	/* slug, ID u objeto: al asignar menús por idioma, Polylang pasa el ID */
	$menu = $args->menu instanceof WP_Term ? $args->menu : wp_get_nav_menu_object( $args->menu );
	$slug = $menu ? $menu->slug : '';
	if ( ! str_starts_with( $slug, 'principal-' ) ) {
		return $items;
	}
	$en     = function_exists( 'pll_current_language' ) && 'en' === pll_current_language();
	$items .= sprintf( '<li class="menu-item pfx-nav-extra pfx-nav-extra--cta"><a class="pfx-nav-cta" tabindex="-1" href="%s">%s</a></li>',
		esc_url( home_url( $en ? '/en/contact/' : '/contact/' ) ), esc_html( $en ? 'Contact Us' : 'Contáctanos' ) );
	return $items;
}, 10, 2 );
```

El `tabindex="-1"` va aquí en el marcado para abreviar; en el caso de referencia lo
añade la sesión de accesibilidad con un segundo `wp_nav_menu_items` (prioridad 20) que
lo inyecta por regex en los enlaces del CTA y de la fila de idioma (ver
`inc/accesibilidad.php` abajo). El archivo real añade además la fila de idioma
(`[pfx_idioma]`) y resuelve la URL de contacto con `get_page_by_path()` +
`pll_get_post()`. Trampa asociada: el nombre de un menú se convierte en su slug
**cada vez** que se pulsa «Guardar menú»; «Pie · Productos ES» da
`pie-%c2%b7-productos-es` y desconecta el widget (que pide el menú por slug) y este
filtro. Pon nombres que se saneen exactamente a su slug («Principal ES» →
`principal-es`) y renombra con `wp term update nav_menu <id> --name=…`, que conserva
el slug.

**Theme Builder por idioma, kit sin idioma y limpieza de metas copiadas.** Cinco
filtros de Polylang + Elementor (`pll_get_post_types` con `elementor_library`,
`elementor/theme/get_location_templates/template_id`,
`elementor/theme/conditions/cache/regenerate/query_args` con `'lang' => ''`,
`pll_get_objects_with_no_lang` sin el kit y `pll_copy_post_metas` sin cachés de
Elementor). Van en `functions.php` porque afectan a todo el sitio. Código y porqués:
`../../wp-polylang/references/polylang-elementor.md`.

**Texto alternativo del idioma 2 en el mismo adjunto.** Campo «Texto alternativo (EN)»
con `attachment_fields_to_edit/save` → meta `_pfx_alt_en`, servido con
`get_post_metadata` fuera del admin cuando el idioma es el 2. Detalle en la skill
`wp-polylang`.

**Año del copyright** (`[pfx_anio]` → `esc_html( wp_date( 'Y' ) )`) dentro de un widget
de texto: el cliente edita la frase, el año se actualiza solo.

**Marca `html.pfx-js` antes de pintar, fuera del editor.**

```php
add_action( 'wp_head', function () {
	echo "<script>if(!/[?&]elementor-preview=/.test(location.search)){document.documentElement.classList.add('pfx-js');}</script>\n";
}, 1 );
```

Todo lo que nace oculto (entradas animadas, estados de JS) cuelga de `html.pfx-js`:
sin JS se ve todo, y el editor, que carga con `elementor-preview`, enseña todo lo
editable.

**`<main id="content">` propio.** Problema: con «Elementor ancho completo» y con las
fichas del Theme Builder, Hello no imprime `<main>`; su «Saltar al contenido» apunta a
`#content`, que no existe, sale oculto también al enfocarlo (la hoja de Hello que lo
muestra no se carga) y en inglés si el locale no tiene traducción. Por qué código: son
hooks de plantilla. Generaliza:

```php
add_action( 'elementor/page_templates/header-footer/before_content', 'pfx_abrir_main' );
add_action( 'elementor/page_templates/header-footer/after_content', 'pfx_cerrar_main' );
add_action( 'elementor/theme/before_do_single', 'pfx_abrir_main' );
add_action( 'elementor/theme/after_do_single', 'pfx_cerrar_main' );
function pfx_abrir_main() {
	echo '<main id="content" class="pfx-main">'; // no .site-main: Hello le pone max-width fuera de las páginas Elementor
}
function pfx_cerrar_main() {
	echo '</main>';
}
add_filter( 'gettext_hello-elementor', function ( $traduccion, $texto ) {
	if ( 'Skip to content' === $texto && ( ! function_exists( 'pll_current_language' ) || 'en' !== pll_current_language() ) ) {
		return 'Saltar al contenido';
	}
	return $traduccion;
}, 10, 2 );
```

Más el CSS del §7 que muestra `.skip-link.screen-reader-text:focus`. Las plantillas 404
y single tienen que ir con Diseño «Predeterminado» o saldrán dos `<main>` (ver
`theme-builder.md`). Comprobación: `curl -s <url> | grep -c '<main id="content"'` = 1
en páginas y fichas.

**Clase de body solo fuera del editor** (para el cursor de marca, §6):

```php
add_filter( 'body_class', function ( $clases ) {
	$editor = isset( $_GET['elementor-preview'] ) || ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() );
	if ( ! $editor ) {
		$clases[] = 'pfx-cursor-marca';
	}
	return $clases;
} );
```

### `inc/<contenido>.php` y `inc/formularios.php` (+ `js/pfx-form-timer.js`)

CPT con metas y revisiones, shortcodes de datos, Query IDs con guarda de reentrada,
orden del filtro, idioma del refresco del Loop Grid, enlaces `?cat=` y schema:
`contenido-dinamico.md`. Antispam, mensajes, cuerpo del correo, remitente y pruebas
en seco: `formularios.md`.

### `inc/rendimiento.php` (dueño: sesión de SEO/rendimiento)

Detalle y mediciones en `../../wp-seo-rendimiento/references/rendimiento.md`. Lo que
importa para diseñar el child:

- **Fuentes propias sin apagar las del cliente** (§6).
- **Envoltorios de transformadores V4** para `font-family` e `image` (§5).
- **jQuery al pie:** `wp_scripts()->add_data( $h, 'group', 1 )` para `jquery`,
  `jquery-core` y `jquery-migrate` fuera del editor, y `jquery` como dependencia
  explícita de los scripts del child que lo usan.
- **Imágenes:** el transformador `image` envuelto quita el `id="<número de adjunto>"`
  (se repetía y empezaba por número) y pone `sizes="auto, 100vw"` si hay `srcset`;
  `wp_img_tag_add_auto_sizes` a `false`; `wp_content_img_tag` (prioridad 11) deja
  `fetchpriority="high"` solo en la clase del LCP real y pone `loading="lazy"` al resto.
  **Sin contadores de «primeras N imágenes»:** Yoast pasa el contenido por
  `the_content` dentro del `<head>` y el contador se consume antes de tiempo.
- **Póster del hero** en WebP como fondo CSS de la sección, precargado con
  `<link rel="preload" as="image" media=… fetchpriority="high">` por breakpoint; el
  vídeo de fondo se engancha tras `load` (`preload="none"` + `data-pfx-src` por
  `the_content`) y con «reducir movimiento» no se descarga hasta pulsar Reproducir.
- **Revelado temprano:** un script inline justo después de `</main>` (hooks
  `…/header-footer/after_content` y `elementor/theme/after_do_single`, prioridad 20)
  revela lo que ya está en pantalla sin esperar al JS diferido, que retrasaba el LCP.
- **Normalizador de `<img>`:** un único `ob_start` en `get_header` (prioridad 9,
  fuera del editor y del ajax) que deja la primera copia de `sizes`, `loading`,
  `fetchpriority` y `decoding` (los atributos manuales de Avanzado › Atributos se
  imprimen delante de los automáticos) y quita el `auto, ` de `sizes` en las que no
  son diferidas (`sizes="auto"` solo vale con `loading="lazy"`). Límite: las plantillas
  que no llaman a `get_header()` (Canvas, mantenimiento) no pasan por él.
- **Mu-plugin sin cookie de idioma** (`define( 'PLL_COOKIE', false )`, §4 mu-plugins).

### `inc/accesibilidad.php` (dueño: sesión de SEO/rendimiento)

- **Panel móvil cerrado con enlaces enfocables.** Pro pone `aria-hidden="true"` al
  panel pero solo gestiona el `tabindex` de sus propios ítems. Un `MutationObserver`
  sobre `aria-hidden` de `.elementor-nav-menu--dropdown.elementor-nav-menu__container`
  alterna `tabindex` 0/-1 en todos sus enlaces, y en servidor los extras ya salen con
  `tabindex="-1"` (sin JS el panel sigue cerrado).
- **«Menu Toggle»** sin traducción en el locale regional → `gettext_elementor-pro`.
- **`autocomplete` por `custom_id`** en los formularios (ver `formularios.md`).
- Detalle: `../../wp-seo-rendimiento/references/accesibilidad.md`.

### `inc/seo.php` + `inc/schema-<tipo>.php` (dueño: sesión de SEO)

Solo lo que Yoast no expresa desde su panel: pieza Recipe en el grafo, página del
listado en las migas, `disable_wpseo_json_ld_search` (sin buscador), x-default con
`pll_rel_hreflang_attributes`, descripción de Organization por idioma
(`wpseo_schema_organization`, prioridad 99), `html_entity_decode` del título en
`wpseo_schema_webpage` e imagen principal = imagen OG elegida en Yoast
(`wpseo_schema_main_image_id`). Ver `contenido-dinamico.md` §9 y
`../../wp-seo-rendimiento/references/seo-yoast-polylang.md`.

### `inc/proximamente.php`

Cortina con el modo mantenimiento de Elementor: la plantilla del idioma 2 por
`option_elementor_maintenance_mode_template_id` → `pll_get_post()` (Elementor la busca
con una consulta que Polylang filtra: sin esto `/<IDIOMA_2>/` sale en blanco),
`noindex, nofollow` por `wp_robots` y vista previa pública `?pfx-vista=proximamente`
que replica el mecanismo de Elementor (`template_redirect` prioridad 11 +
`query_posts` de la plantilla). En un dominio con historial se usa `maintenance`
(503), nunca `coming_soon` (200 + noindex en todo). Ver `theme-builder.md` y
`../../wp-migracion/references/lanzamiento.md`.

### `inc/acceso.php` + `css/login.css`

Pantalla de acceso con marca. La pantalla de login no ejecuta `wp_head`: ni fuentes,
ni variables, ni CSS del kit llegan solos.

- **Idioma real:** `determine_locale()` (desplegable → cookie `wp_lang`), no la clase
  `locale-*` del body, que sale de `get_locale()` y no cambia al elegir otro idioma.
  `login_body_class` añade `pfx-en`.
- **Hoja** encolada en `login_enqueue_scripts` con dependencia `[ 'login' ]` y
  `filemtime`; quita ahí también el filtro de Child Theme Configurator.
- **Imágenes desde la Biblioteca** (siguen al dominio) con `wp_add_inline_style` y
  `esc_url_raw()`, no `esc_url()`: dentro de CSS un `&#038;` rompe la URL. Los IDs de
  adjunto van en constantes.
- **Textos del núcleo:** `login_headerurl`, `login_headertext`, `login_title`,
  `login_site_html_link`, `login_remember_me_help_text` (WP 7.1),
  `login_language_dropdown_args` (dejar solo el locale regional) y un `gettext`
  acotado que se añade **dentro de `login_init`**. Títulos decorativos en `::before`
  con `content: "Texto" / ""`. El modal de sesión caducada (`interim-login`, iframe de
  unos 380×415) se diseña aparte, sin logo ni tarjeta.
- **Nunca escribas `<LOGIN_SLUG>` en un archivo público.** En el caso de referencia el
  comentario de cabecera de `css/login.css`, servido sin sesión, nombraba la URL de
  acceso. URL de acceso propia, recuperación y cabeceras que la rompen:
  `../../wp-elementor-replica/references/fases.md` (fase de 404, login y
  Próximamente) y `../../wp-elementor-replica/references/operacion-segura.md`.

### `js/home.js`

Comportamiento por atributos `data-pfx*`: entradas animadas con red de seguridad (en
cada scroll revela lo que ya quedó por encima del 92 % de la ventana, porque el
`IntersectionObserver` no muestrea saltos de ancla ni scroll rápido), carriles con
flechas, `aria-label` del vídeo copiado del texto visible del botón (Elementor fija
«Play video» en inglés), parche del filtro de Pro y selector de idioma que conserva el
filtro (`contenido-dinamico.md` §4 y §8). Los textos de respaldo por idioma salen de
`document.documentElement.lang`.

### `mu-plugins/`

Dos mu-plugins, porque tienen que actuar **antes que el tema** (Polylang decide el idioma en
`plugins_loaded` y lee `PLL_COOKIE` al arrancar): el idioma del documento en el ajax del editor y
de los formularios, y `PLL_COOKIE=false` para que la caché de página guarde el HTML. Código,
síntomas y verificación: `../../wp-polylang/references/polylang-elementor.md` §4; el segundo,
listo para copiar, en `../../wp-seo-rendimiento/scripts/tema-hijo/mu-sin-cookie-idioma.php`.

**Depuración temporal por cabeceras.** Para saber qué callback redirige (p. ej.
`/<IDIOMA_2>/` → `/<IDIOMA_2>/home/`), una copia `.debug.php` de un mu-plugin que, solo
con `?<PREFIJO>dbg`, escribe cabeceras `X-DBG-*` con el estado (idioma actual, objeto
consultado, URL en cada prioridad de `redirect_canonical`). Se leen con `curl -sI` sin
tocar el HTML y se retira en el siguiente despliegue. Código:
`../../wp-polylang/references/cadenas-menus-slugs.md` §4.

## 5. Nunca heredes de un transformador concreto de Elementor: envuelve el registrado

Síntoma: las primeras versiones hacían `new class() extends Font_Family_Transformer` y
`extends Image_Transformer`, protegidas solo con `class_exists`. La revisión
adversarial señaló que una actualización de Elementor que añada tipo de retorno o
parámetros, marque la clase `final` o mueva `Props_Resolver_Context` produce un
`E_COMPILE_ERROR` (no capturable) **en cada petición**: el registro de transformadores
se construye en cada render atómico y tras cada limpieza de CSS, que ocurre justo al
actualizar. `class_exists` no protege de una firma incompatible.

Regla: toma la instancia ya registrada y registra en su lugar un envoltorio que
extienda solo la base abstracta, con firma sin tipos y delegando en el original. Antes,
comprueba con Reflection que la base no haya cambiado. El peor caso pasa a ser «deja
de añadir el respaldo», nunca un fatal.

```php
function pfx_envolver_transformador( $transformers, $clave, callable $despues ) {
	$base = '\Elementor\Modules\AtomicWidgets\PropsResolver\Transformer_Base';
	if ( ! class_exists( $base ) || ( new \ReflectionMethod( $base, 'transform' ) )->hasReturnType() ) {
		return;
	}
	$orig = $transformers->get( $clave, false );
	if ( ! $orig instanceof $base ) {
		return;
	}
	$transformers->register( $clave, new class( $orig, $despues ) extends \Elementor\Modules\AtomicWidgets\PropsResolver\Transformer_Base {
		private $orig;
		private $despues;
		public function __construct( $orig, $despues ) {
			$this->orig    = $orig;
			$this->despues = $despues;
		}
		public function transform( $value, $context = null, ...$resto ) {
			return ( $this->despues )( $value, fn() => $this->orig->transform( $value, $context, ...$resto ) );
		}
	} );
}

const PFX_FUENTES = [ 'Anton' => 'Anton Fallback', 'Archivo' => 'Archivo Fallback' ]; // familias del caso de referencia

/* estilos: las clases V4 escriben la familia con su respaldo métrico */
add_action( 'elementor/atomic-widgets/styles/transformers/register', function ( $transformers ) {
	pfx_envolver_transformador( $transformers, 'font-family', function ( $value, $original ) {
		$familia = is_string( $value ) ? trim( $value, " \t\"'" ) : '';
		return isset( PFX_FUENTES[ $familia ] ) ? sprintf( '%s, "%s", sans-serif', $familia, PFX_FUENTES[ $familia ] ) : $original();
	} );
}, 20 );

/* settings: e-image con sizes y sin id numérico; el alt lo sigue leyendo el original */
add_action( 'elementor/atomic-widgets/settings/transformers/register', function ( $transformers ) {
	pfx_envolver_transformador( $transformers, 'image', function ( $value, $original ) {
		$img = $original();
		if ( is_array( $img ) ) {
			unset( $img['id'] );
			if ( ! empty( $img['srcset'] ) ) {
				$img['sizes'] = 'auto, 100vw';
			}
		}
		return $img;
	} );
}, 20 );
```

- Tras registrar el envoltorio de `font-family`, `wp elementor flush-css` para que el
  CSS de clases se regenere.
- Si otra sesión envuelve el transformador de imagen, tiene que seguir llamando al
  original: ahí se lee el alt (también el del idioma 2).
- Comprueba tras **cada** actualización de Elementor:
  `curl -s <url> | grep -o 'Archivo Fallback' | head -1` y `grep -c 'sizes="auto'`.

## 6. Cursor de marca, foco visible y fuentes propias

### Cursor personalizado (lo pidió el cliente)

Elementor y los widgets fijan `cursor: pointer` en muchos sitios, así que hace falta
`!important`; solo con ratón, con variante `pointer` en lo clicable y cursor del
sistema en los campos. La clase de body se añade solo fuera del editor (§4).

```css
@media (hover: hover) and (pointer: fine) {
	body.pfx-cursor-marca,
	body.pfx-cursor-marca * { cursor: url("img/cursor-32.png") 16 16, auto !important; }
	body.pfx-cursor-marca a,
	body.pfx-cursor-marca a *,
	body.pfx-cursor-marca button,
	body.pfx-cursor-marca button *,
	body.pfx-cursor-marca [role="button"],
	body.pfx-cursor-marca [role="button"] *,
	body.pfx-cursor-marca summary { cursor: url("img/cursor-32.png") 16 16, pointer !important; }
	body.pfx-cursor-marca input,
	body.pfx-cursor-marca textarea,
	body.pfx-cursor-marca select { cursor: auto !important; }
}
```

PNG de 32 px con el punto activo en el centro (`16 16`). Verifica con
`getComputedStyle(el).cursor` en el body, un enlace, un botón y un input.

### Foco visible por encima del reset de Hello

El `reset.css` de Hello aplica su contorno con
`[tabindex]:not([tabindex="-1"]):focus-visible` (0,3,0) y quita `box-shadow` con
`[tabindex]:not([tabindex="-1"]):focus:not(:focus-visible)` (0,4,0). Un foco declarado con `:where()` pierde.

```css
/* (0,3,1): gana al reset de Hello. Color con contraste >= 3:1 sobre el fondo */
html :is(a, button, input, select, textarea, summary, [tabindex]:not([tabindex="-1"])):focus-visible {
	outline: 3px solid var(--verde-profundo, #005836);
	outline-offset: 3px;
}
/* «Saltar al contenido» de Hello, visible al enfocarlo */
.skip-link.screen-reader-text:focus {
	position: fixed; top: 8px; left: 8px; z-index: 100000;
	width: auto; height: auto; margin: 0; padding: 12px 20px;
	overflow: visible; clip: auto; clip-path: none;
	background: #fff; color: var(--verde-profundo, #005836);
}
```

Repite las sombras decorativas también en `[tabindex]:focus` (los pines del mapa
perdían la sombra al hacer clic) y sobrescribe el hover/focus `#c36` (magenta) que el
reset de Hello pinta en los `<button>` propios.

### Fuentes propias sin apagar las Google Fonts del cliente

- Sirve desde `fonts/` del child solo los subconjuntos woff2 usados (latin y latin-ext;
  una fuente variable en un archivo, `font-weight: 100 900`), con `unicode-range` y
  `font-display: swap`, en un `<style>` en `wp_head` prioridad 1. Precarga solo los
  latin. Descarga el CSS de Google Fonts con un UA de Chrome (con otro UA devuelve TTF).
- Anula **solo** los handles de esas familias:
  ```php
  add_filter( 'style_loader_tag', function ( $tag, $handle ) {
  	return preg_match( '/^elementor-gf-(local-)?(anton|archivo)$/', $handle ) ? '' : $tag;
  }, 10, 2 );
  ```
  La primera solución, `elementor/frontend/print_google_fonts` → `false`, apagaba
  **todas** las Google Fonts: una fuente que el cliente eligiera después se vería en el
  editor pero no en la web.
- Respaldo métrico para CLS 0: `@font-face{font-family:"X Fallback";src:local("Arial"),local("ArialMT"),local("Liberation Sans"),local("Arimo");ascent-override:…;descent-override:…;line-gap-override:0%;size-adjust:…}`.
  Si el prototipo es Next.js con `next/font`, copia los valores exactos de su build.
  Liberation Sans y Arimo cubren Linux (PageSpeed corre en Linux).
- El transformador envuelto (§5) añade el respaldo a las clases V4. El `nav-menu`
  clásico usa `var(--e-global-typography-*)` y no pasa por el transformador: pon
  `"X Fallback"` a mano en el CSS del child.
- No actives además «Load Google Fonts Locally» de Elementor ni la optimización de
  fuentes del hosting: duplican.

Valores medidos y comprobaciones: `../../wp-seo-rendimiento/references/rendimiento.md`.

## 7. CSS del child frente a los estilos atómicos

- **Prefijo `.elementor `.** Los estilos base V4 son `.elementor .e-flexbox-base`
  (0,2,0): `padding: 10px`, `flex-direction: row`; `.e-con` lleva `width: 100%` y
  `position: relative`; las clases globales compilan a `.elementor .pfx-x`. Una regla
  `[data-pfx="…"]` sin prefijo pierde. Para el `nav-menu` clásico:
  `.elementor-location-header.elementor .pfx-nav …`. `!important` solo donde un script
  escribe estilos en línea (SmartMenus en `.sub-menu`) o contra el CSS por elemento de
  un widget V3.
- **El `nav-menu` clásico se estiliza entero desde el child.** Se eligió por
  editabilidad (el cliente edita los ítems en Apariencia › Menús). Que no tenga
  pestaña Estilo en Elementor 4 se dedujo volcando controles desde CLI y no se
  confirmó en el editor; en todo caso, el diseño del menú vive en el child. Cuidado con
  el `:hover/:focus` del widget (`padding: 10px 20px`), que alcanza cualquier `<a>`
  inyectado dentro del menú.
- **Botones que son `<a>`:** el `a:hover` del kit gana a una clase suelta; declara el
  color del texto también en la variante hover (el texto de un botón verde desaparecía
  al pasar el ratón).
- **Colores desde las variables de Elementor**, que se publican en `:root` con su
  etiqueta como nombre: `var(--verde-profundo, #005836)`; el valor tras la coma es
  solo respaldo.
- **Iconos en contenedores atómicos:** Elementor reserva el `::before` de `.e-con` como
  capa de fondo, absoluto arriba a la izquierda; el centrado por flex no actúa. Pinta
  el icono con máscara y céntralo así (y `min-width: 0` en la clase del contenedor
  vacío, que nace con `min-width: 30px`):
  ```css
  .elementor [data-pfx-icono]::before {
  	content: ""; position: absolute; inset: 0; margin: auto; width: 16px; height: 16px;
  	background: currentColor;
  	-webkit-mask: var(--pfx-icono) center / contain no-repeat;
  	mask: var(--pfx-icono) center / contain no-repeat;
  }
  ```
- **El editor tiene que enseñar todo lo editable.** Estados ocultos por JS colgados de
  `html.pfx-js` o `body:not(.elementor-editor-active)`; clases de body que cambian la
  experiencia solo fuera de `is_preview_mode()`; y las cajas de icono vacías, que el
  editor pinta como zonas de soltar de 120 px:
  ```css
  .e-atomic-element[data-pfx-icono]:has(> .elementor-empty-view) { min-height: 0 !important; }
  .e-atomic-element[data-pfx-icono] > .elementor-empty-view { display: none !important; }
  ```
- **Barra de administración.** «El sticky no funciona» resultó ser la barra: con
  sesión, la cabecera fija baja 32 px (46 px por debajo de 782 px, 0 por debajo de
  600 px, donde la barra deja de ser fija). Todo `position: sticky` y todo destino de
  ancla lleva su desplazamiento `.admin-bar` en los cuatro tramos (>782, ≤1024, ≤782,
  ≤600). Pregunta siempre si el cliente lo vio con sesión iniciada: él navega
  logueado.
  ```css
  .admin-bar .elementor-location-header { top: 32px; }
  @media (max-width: 782px) { .admin-bar .elementor-location-header { top: 46px; } }
  @media (max-width: 600px) { .admin-bar .elementor-location-header { top: 0; } }
  ```
- **Anclas entre páginas.** Elementor pone `html{scroll-behavior:smooth}` (dentro de
  `prefers-reduced-motion: no-preference`) y Chrome cancela el salto inicial al llegar
  a `/pagina/#id` desde otra página. Fija `html { scroll-behavior: auto; }` y da a cada
  destino un `scroll-margin-top` igual a la altura de la cabecera fija, con su
  variante de barra de administración.
- **Animaciones decorativas con `translate`, `rotate` y `scale` sueltas.** `transform`
  es una sola propiedad y la última regla gana: resérvala para el sistema de entradas
  animadas; los efectos propios de cada componente usan las propiedades individuales,
  que se componen con él.
- **Entradas animadas con mejora progresiva:**
  ```css
  html.pfx-js [data-pfx-reveal] { opacity: 0; transform: translateY(28px); transition: opacity .7s, transform .7s; }
  html.pfx-js [data-pfx-reveal].is-in { opacity: 1; transform: none; }
  @media (prefers-reduced-motion: reduce) {
  	html.pfx-js [data-pfx-reveal] { opacity: 1 !important; transform: none !important; transition: none !important; }
  }
  ```
  Con «reducir movimiento» todo visible (además, así se puede auditar el contraste:
  axe omite el texto a opacidad 0).

## 8. Checklist del child

- [ ] Cabecera de `functions.php` con la regla de frontera; un `inc/` por dominio con
      su bloque de porqués.
- [ ] Cada `require_once` tiene su archivo; sin `.bak` en el tema.
- [ ] `filemtime` en estilos y scripts; filtro de Child Theme Configurator fuera (en
      `wp_head` y en `login_enqueue_scripts`) si el plugin sigue activo.
- [ ] Purga tras cada subida; `?ver=` servido = `filemtime` del servidor.
- [ ] Selectores sobre atómicos con `.elementor `; nav-menu con prefijo de ubicación.
- [ ] `<main id="content">` único en páginas y fichas; skip-link visible y traducido.
- [ ] Foco visible (0,3,1); cursor solo con ratón y fuera del editor.
- [ ] Transformadores envueltos, nunca heredados; comprobación tras actualizar Elementor.
- [ ] Todo texto del child con cascada atributo editable → respaldo por idioma.
- [ ] El usuario abrió cada página en el editor y confirmó que carga y se ve todo
      (Claude no inicia sesión: pídeselo explícitamente).

## Dudas abiertas

- **Riesgo de la mejora progresiva:** si `home.js` no carga después de marcar
  `html.pfx-js`, lo que está bajo el pliegue se queda con `opacity: 0`. No hay un
  temporizador CSS de seguridad (idea no probada: una animación que fuerce
  `opacity: 1` a los pocos segundos).
- **Fragilidad ante actualizaciones sin prueba automática:** mapas `gettext` sobre
  cadenas inglesas exactas de Pro y del núcleo, regex sobre el marcado del vídeo de
  fondo, parche de `taxonomyFilter.refreshLoopWidget`, selectores internos del
  `nav-menu`. Falta un script de humo (`curl`/JS) que lo compruebe tras cada
  actualización.
- La falta de pestaña Estilo del `nav-menu` en Elementor 4 no se confirmó en el editor.
