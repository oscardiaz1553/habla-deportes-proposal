# Rendimiento · PageSpeed en verde con Elementor 4

Recetas y trampas, ordenadas por impacto en el caso de referencia (HomeMaker). Verificado sobre
WordPress 7.1, Elementor 4.3.0 + Pro 4.2.3, Hello 3.5.1, Polylang Pro 3.8.6, Yoast 27.7,
SiteGround Speed Optimizer 7.8.2 y, tras la migración, Apache 2.4 + lsapi. Los números de línea
de los plugins cambian entre versiones; los mecanismos no.

El código completo está en `../scripts/tema-hijo/` (plantillas con prefijo `pfx`) y los scripts de
servidor en `../scripts/wp-cli/`. Cómo medir cada cosa: `medicion.md`.

**Seguridad primero.** Antes de tocar nada: respaldo en el servidor (BD, child, opciones de
Yoast/optimizador/Elementor y `_elementor_data` de cada documento que vayas a editar; comando en
`medicion.md` §1). Cambios invisibles primero; lo visible es decisión del usuario.

---

## 1. Caché de página y TTFB (el factor número uno)

**Síntoma.** TTFB alto e irregular, escritorio peor que móvil. Cabeceras de la portada:
`set-cookie: pll_language=es`, `x-proxy-cache: MISS`, `x-proxy-cache-info: 0 NC:000000
UP:SKIP_CACHE_SET_COOKIE`. La misma URL con `Cookie: pll_language=es` daba HIT. Otras páginas
«parecían» HIT solo porque una visita con cookie las había calentado.

**Causa.** Polylang envía la cookie por cabecera (`maybe_setcookie`, hook `wp` prioridad 7) porque
`pll_is_cache_active()` es false (no hay `WP_CACHE`). Ninguna caché de página guarda respuestas
con `Set-Cookie`. Con idioma por directorio, `browser=0` y `hide_default=1`, la cookie solo es el
respaldo del ajax sin `lang`.

**Regla.** En todo sitio Polylang con el idioma en la URL, desde el día 1, un **mu-plugin**:

```php
defined( 'ABSPATH' ) || exit;
if ( ! defined( 'PLL_COOKIE' ) ) {
	define( 'PLL_COOKIE', false );
}
```

- Tiene que ser mu-plugin (o `wp-config.php`): Polylang define sus constantes en `plugins_loaded`
  prioridad 1 y `functions.php` llega tarde. Archivo listo: `../scripts/tema-hijo/mu-sin-cookie-idioma.php`.
- Si algún día se activa la detección por navegador: en su lugar
  `add_filter( 'pll_is_cache_active', '__return_true' );` (cookie por JS; Polylang marca la portada
  `DONOTCACHEPAGE`).
- Formularios y ajax del editor deben tomar el idioma del `post_id`, no de la cookie (skill
  `wp-polylang`).
- **Viaja en cada migración.** Sin él, ningún hosting cachea.

**Verificar** (en `/`, en el segundo idioma y en un single):

```bash
for u in / /<ruta>/ /en/; do for i in 1 2; do curl -s -D - -o /dev/null "https://<STAGING_URL>$u" \
  | grep -iE 'set-cookie|x-proxy-cache:' | tr -d '\r' | tr '\n' ' '; echo " <- $u #$i"; done; done
```
Esperado: sin `set-cookie` y la 2.ª petición HIT (donde el hosting expone su caché en una
cabecera; en SiteGround, `x-proxy-cache`). En incógnito: selector de idioma, filtros y
formulario siguen funcionando.

**Más reglas de caché**
- Purga después de cada subida del child, de cada cambio de Yoast (también cachea `robots.txt` y
  los sitemaps) y al quitar una cortina: cambiar el modo de mantenimiento de Elementor no purga.
- Para saltarte la caché en pruebas usa `?nocache=$RANDOM`, **nunca `?m=`**: `m` es la variable de
  archivo por fecha de WordPress y devuelve 404. Y UA de navegador (`-A`): algunos hostings retan
  al UA de curl.
- **Hosting sin caché de página** (Apache + lsapi, sin LiteSpeed/nginx/Varnish): en el caso el
  TTFB pasó de 0,1–0,4 s a 0,7–1,1 s. Siguió en verde (Lighthouse local, una corrida detrás de la
  cortina: portada 93/99, listado 98/99) pero con poco margen en móvil (LCP 3,0 s).
  **Es una decisión del usuario, no un paso tuyo** (D11 del runbook de `wp-migracion`). Preséntala
  así: recomendación de la sesión SEO, activarla antes o justo al lanzar, con WP Super Cache en
  modo Expert (mod_rewrite, Apache sirve el HTML sin PHP) o la caché del plugin del propio hosting;
  LiteSpeed Cache **solo** con servidor LiteSpeed. Ajustes propuestos: sin usuarios con sesión,
  sin URL con query (filtros de Elementor), solo respuestas 200 (la 503 de la cortina no se
  guarda), purga al publicar y al quitar el mantenimiento, una caché común para móvil y
  escritorio. En el caso quedó como decisión pendiente del usuario: ni la instalación ni esos
  ajustes se llegaron a probar, y no hay medición con caché en el hosting nuevo.

## 2. Fuentes: autoalojar lo usado, con respaldo de métricas

**Síntoma.** Elementor pedía 18 variantes por familia a `fonts.googleapis.com` (36 en total):
0,75–1,2 s de bloqueo de render en móvil y una cadena documento → googleapis → gstatic. CLS de
0,05–0,235 mientras llegaba la fuente (el navegador pintaba su serif por defecto).

**Causa.** El kit y las clases V4 encolan la familia completa; se usaban 1 peso de una familia y
un rango de la otra (variable), sin itálicas. Sin familia de respaldo con `size-adjust`, el
cambio de fuente mueve la página.

**Receta**
1. Audita pesos y estilos realmente usados: `grep` de `font-weight`/`font-style` en
   `wp-content/uploads/elementor/css/*.css` y `style.css`.
2. Descarga el CSS de Google **con UA de Chrome** (con el UA de WordPress Google devuelve TTF: el
   «Web Fonts Optimization» de SiteGround bajó 19 `.ttf`):
   ```bash
   UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
   curl -s -A "$UA" "https://fonts.googleapis.com/css2?family=<Familia1>&family=<Familia2>:wght@100..900&display=swap" -o gf.css
   file *.woff2   # → «Web Open Font Format (Version 2)»
   ```
   Quédate solo con los subconjuntos `latin` y `latin-ext` en woff2 (un archivo por subconjunto si
   la fuente es variable, `font-weight: 100 900`) y copia los `unicode-range` exactos.
3. `@font-face` inline en `wp_head` prioridad 1 con `unicode-range` y `font-display:swap`;
   `preload` **solo de los latin** (los que usa el texto en español e inglés).
4. Quita **solo** los handles de esas familias:
   ```php
   add_filter( 'style_loader_tag', function ( $tag, $handle ) {
   	return preg_match( '/^elementor-gf-(local-)?(familia1|familia2)$/', $handle ) ? '' : $tag;
   }, 10, 2 );
   ```
   **Trampa:** la primera versión usó `elementor/frontend/print_google_fonts → false`. La revisión
   adversarial lo tumbó: apaga **todas** las Google Fonts, así que cualquier fuente que el cliente
   elija después se vería en el editor y no en la web.
5. Respaldo con métricas, sobre fuentes que existen también en Linux (PSI corre en Linux: sin
   Liberation Sans/Arimo el respaldo allí no existe):
   ```css
   @font-face{font-family:"X Fallback";src:local("Arial"),local("ArialMT"),local("Liberation Sans"),local("Arimo");
     ascent-override:…%;descent-override:…%;line-gap-override:0%;size-adjust:…%}
   ```
   Si el prototipo es Next.js con `next/font`, copia los valores exactos de su build:
   `grep -rhoE "@font-face\{font-family:(X|Y) Fallback[^}]*\}" .next | sort -u`.
   Sin prototipo Next no hay métricas «gratis»: calcúlalas o valora «Load Google Fonts Locally»
   de Elementor como vía barata (no se probó en el caso; nunca junto con el autoalojado).
6. Añade `"X Fallback"` a todas las pilas: en las clases V4 con el envoltorio del transformador
   `font-family` (§3) y **a mano** en `style.css` para el nav-menu clásico (V3), que usa
   `var(--e-global-typography-*)` y no pasa por el transformador.
7. Un solo método: no actives además «Load Google Fonts Locally» ni la optimización/precarga de
   fuentes del hosting (duplican).
8. Las fuentes van sin `?ver=` y con caché de un año `immutable`: si cambian, cámbiales el nombre.

**Verificar:** `grep -c fonts.googleapis` = 0; `grep -o 'as="font"' | wc -l` = número de preloads;
sin filas de googleapis en `render-blocking-insight`; `layout-shifts` sin «Fuente web cargada»; y
una Google Font elegida en el editor sigue cargando. Resultado del caso: 4 woff2 de 12–35 KB en
lugar de 36 variantes; CLS ≤ 0,02 en las 14 corridas.

## 3. Transformadores V4: envolver, nunca heredar

Elementor 4 resuelve las props de los widgets y clases atómicas con «transformadores». Se tocan
dos: `font-family` (estilos) para añadir el respaldo y `image` (settings) para `sizes` e `id`.

**Trampa que podía tumbar el sitio.** `new class() extends Image_Transformer { public function
transform( $value, Props_Resolver_Context $context ) … }` produce un `E_COMPILE_ERROR` (no
capturable) en todas las páginas si una actualización añade tipo de retorno o parámetros, marca la
clase `final` o mueve `Props_Resolver_Context`. `class_exists` no protege de una firma
incompatible, y el registro se construye en cada render atómico y tras cada limpieza de CSS, que
ocurre justo al actualizar. Lo encontró la revisión adversarial con todo en verde.

**Regla.** Toma el transformador ya registrado y registra un envoltorio que extienda solo el
abstracto, sin tipos, con guardia por Reflection:

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
		public function __construct( $orig, $despues ) { $this->orig = $orig; $this->despues = $despues; }
		public function transform( $value, $context = null, ...$resto ) {
			return ( $this->despues )( $value, fn() => $this->orig->transform( $value, $context, ...$resto ) );
		}
	} );
}
// font-family: add_action( 'elementor/atomic-widgets/styles/transformers/register',   …, 20 )
// image:       add_action( 'elementor/atomic-widgets/settings/transformers/register', …, 20 )
```
Luego `wp elementor flush-css`. Si algo cambia en Elementor, se degrada en silencio («deja de
añadir el respaldo»), nunca un fatal.

**Tras cada actualización de Elementor:** `php -l`, render de 3 páginas sin «critical error» y
comprobar que el efecto sigue: `grep -c '<Familia> Fallback'
wp-content/uploads/elementor/css/global-<id>-frontend-desktop.css` > 0 y `grep -c 'sizes="auto'`
en el HTML.

## 4. Imágenes

### 4.1 `sizes` e `id` de la imagen V4
**Síntoma.** Un logo de 135 px se descargaba a 900w/768w (53–68 KB) en todas las páginas; 1,4–1,9 MB
desperdiciados por página; `id="32"` repetido (logo en cabecera y pie).
**Causa.** `Image_Transformer` devuelve `id/src/width/height/srcset/alt` **sin `sizes`** (el
navegador asume 100vw) y usa el ID del adjunto como atributo `id`.
**Regla.** Envoltorio del transformador `image`: `unset($img['id'])` y, si hay `srcset`,
`sizes="auto, 100vw"`; además `add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' )`.
- `sizes=auto` solo lo respeta el navegador con `loading="lazy"`. A las eager (logos) ponles un
  `sizes` explícito en Avanzado › Atributos, p. ej. `(max-width: 1024px) 126px, 135px`; se imprime
  delante y gana.
- Las imágenes `width:auto; height:auto` (logos de tiendas) **colapsan** con `sizes=auto` porque el
  navegador aplica `contain:size` a `img[sizes^="auto"]`: dales `sizes` fijo.
- Simúlalo en vivo antes de desplegar: `sizes-sim.mjs` y `sizes-sim.mjs --lazy`.
- Comprueba que el `alt` sigue saliendo en el segundo idioma.
**Verificar:** `curl -s <URL> | grep -o ' id="[0-9]*"' | sort | uniq -d` vacío; en DevTools
`[...document.images].map(i=>[i.currentSrc.split('/').pop(),i.getAttribute('sizes'),i.clientWidth])`.

### 4.2 Prioridad (`fetchpriority` y `loading`)
**Síntoma.** Los filtros `wp_omit_loading_attr_threshold` y `wp_get_loading_optimization_attributes`
no hacían nada; una imagen decorativa `display:none` en móvil recibía `fetchpriority="high"`.
**Causa.** La «Carga de imágenes optimizada» de Elementor (activa por defecto) **sustituye** la
lógica de WordPress: `modules/image-loading-optimization/module.php` fija `private $omit_threshold
= 3` sin filtro, desactiva el fetchpriority del núcleo y `wp_lazy_loading_enabled`, y corre su
propio `wp_content_img_tag` a prioridad 10.
**Regla.** Deja la opción activa y corrige con `wp_content_img_tag` a **prioridad 11, sin
estado**: conserva `fetchpriority="high"` solo en la clase de la imagen LCP (el banner de las
páginas interiores), quítalo del resto y añade `loading="lazy"` si falta. En portadas cuyo LCP es
un fondo CSS o un texto, ninguna imagen de contenido lleva high. **Nada de contadores**: Yoast
pasa el contenido por `the_content` dentro de `wp_head` (respaldo de `og:description`) y
desplazaría la cuenta. Código: bloque 4b de `rendimiento.plantilla.php`.
**Verificar:** como máximo un `fetchpriority="high"` por página; `lcp-discovery-insight` aprobado;
la imagen decorativa ya no aparece en `network-requests` móvil.

### 4.3 Plantillas del Theme Builder: no pasan por `the_content`
**Síntoma.** El banner del single del CPT salía sin `fetchpriority`; las tarjetas del loop item y
del grid, eager. **Causa.** Singles y loop items del Theme Builder no atraviesan
`wp_filter_content_tags`; el supuesto «WordPress ya se lo pone a la primera imagen grande» es
falso ahí. **Regla.** Fija `fetchpriority=high` / `loading=lazy` como atributos del widget en
`_elementor_data` (clave `attributes`, `$$type: key-value`) con
`../scripts/wp-cli/atributos-por-id.php`. Cubre también las tarjetas que devuelve el refresco REST
de un filtro. Guardar por CLI un documento con etiquetas dinámicas exige sacar a Elementor del
«modo frontend» (el script lo hace; detalle en `../../elementor4-atomico/references/gotchas-cli.md`).

### 4.4 Atributos repetidos en `<img>`
Los atributos de Avanzado › Atributos se imprimen delante de los que añaden Elementor y los
filtros: `sizes`, `loading` y `fetchpriority` salían duplicados. Un único búfer
`ob_start('pfx_img_normaliza')` en `get_header` **prioridad 9** (Elementor abre el suyo en 10: así
lo envuelve y su comprobación en `flush_header_buffer` sigue funcionando) conserva la primera copia
y quita `auto` de `sizes` si la imagen no es lazy. Fuera del editor y del ajax. Bloque 8.

### 4.5 LCP pintado como fondo CSS (póster del vídeo)
**Síntoma.** El LCP de la portada era el póster del vídeo como `background` en `style.css` (JPG de
136 KB), sin prioridad y descubierto después de cargar el CSS. **Causa.** El escáner de precarga
no ve imágenes de CSS. **Regla.** Póster en WebP (móvil recortado al centro) y dos `preload` con
`media` y `fetchpriority="high"`, con href **idéntico** al que resuelve el CSS (bloque 4c):
```bash
ffmpeg -loglevel error -y -i poster.jpg -vf "crop=1480:1080:(in_w-1480)/2:0" m.png
cwebp -quiet -q 80 -m 6 poster.jpg -o hero-poster.webp && cwebp -quiet -q 78 -m 6 m.png -o hero-poster-m.webp
```
Si el cliente cambia el vídeo, regenera los pósters. **Verificar:** los 2 preloads en el HTML de
`/`; en PSI la petición del póster arranca < 100 ms después del documento.

### 4.6 WebP
- **SiteGround:** `wp sg optimize webp enable` solo cambia la opción; la conversión masiva la lanza
  el panel por ajax. Por CLI: `../scripts/wp-cli/webp-convertir.php` (JPG con el conversor del
  plugin, PNG sin pérdida con `cwebp -lossless -z 9`, descarta la copia si no ahorra). Caso: 156
  archivos, 11,2 MB → 6,3 MB en 31 s. SiteGround las sirve por nginx según `Accept`.
- **Apache:** las copias `foto.jpg.webp` solo se sirven con reglas: `AddType image/webp .webp`,
  `RewriteCond %{HTTP_ACCEPT} image/webp` + `RewriteCond %{REQUEST_FILENAME}.webp -f` →
  `RewriteRule ^(.+\.(?:jpe?g|png))$ $1.webp [T=image/webp,E=webp:1,L]` y `Vary: Accept`. Bloque
  listo (el 6, después de la cortina y antes de `# BEGIN WordPress`) en
  `../../wp-migracion/scripts/htaccess-bloques.plantilla`; orden y porqué de cada bloque en
  `../../wp-migracion/references/runbook.md` (F7). `Vary: Accept` también en el JPG/PNG original:
  si no, una caché intermedia podría dar JPG a quien acepta WebP o al revés.
- Sin el optimizador del hosting, **las subidas nuevas no tendrán WebP**: elegir conversor es
  decisión del usuario (quedó abierto en el caso).
- **Verificar:** `curl -sI -H 'Accept: image/webp,*/*' <jpg>` → `content-type: image/webp`,
  `vary: Accept` y menor `content-length`; sin esa cabecera, `image/jpeg` y `vary: Accept`.

## 5. JavaScript: jQuery al pie

**Síntoma.** `jquery` + `jquery-migrate` bloqueaban 0,33–0,47 s en todas las páginas; su primer
consumidor estaba en el pie. **Causa.** WordPress lo registra en el grupo del `<head>`.
**Regla.** En `wp_enqueue_scripts` prioridad 100, fuera del admin y de `?elementor-preview`:
`wp_scripts()->add_data( $h, 'group', 1 )` para `jquery`, `jquery-core` y `jquery-migrate`, y añade
`jquery` a los `deps` de cualquier script propio que lea `window.jQuery` (bloque 3).
- **Nunca** con «Defer render-blocking JS» de SiteGround: rompe el inline
  `jQuery.uiBackCompat=true` → error de consola → Buenas prácticas.
- Si un script del `<head>` depende de jquery, WordPress lo devuelve al `<head>`: compruébalo.
**Verificar:** `curl -s <URL> | sed -n '1,/<\/head>/p' | grep -c jquery` = 0; consola limpia en
todas las páginas × 2 anchos (`console.mjs`); el usuario prueba el editor.

## 6. Vídeo de fondo V4 (portada)

**Síntoma.** `e-background-video` imprime `preload="auto"` fijo: 1,4 MB de mp4 compitiendo con el
póster. PSI portada móvil se quedaba en 84 con todo lo demás hecho. **Causa.** La plantilla del
widget no tiene opción de preload ni de póster.
**Regla.** En `the_content` (solo portada, fuera del editor) `preload="auto"` → `none` y `src` →
`data-pfx-src` en el `<source>`; un script de `wp_footer` asigna el `src` tras `load` +
`requestIdleCallback(c,{timeout:1500})`; el botón de reproducir también lo engancha; con
`prefers-reduced-motion: reduce` no descarga nada hasta pulsar Reproducir y deja el control en
«Reproducir» (bloque 7). **Es un cambio visible** (el póster dura ~1 s más): se aplicó con
evidencia (checkpoint de Lighthouse) y con aprobación. Resultado: PSI portada móvil 84 → 94.
**Verificar:** `video-diferido.mjs` (hora de petición del mp4 con y sin reduce; estado del control).

## 7. Animaciones de entrada y LCP

**Síntoma.** En una página el LCP (un h1) estaba dentro de un bloque animado a opacidad 0 hasta
que el JS del pie, diferido tras ~150 KB de JS, añadía `.is-in`: +4,36 s de render delay en
escritorio. **Causa.** El revelado dependía del JS del pie.
**Regla.** Script inline justo después de `</main>` (hooks
`elementor/page_templates/header-footer/after_content` y `elementor/theme/after_do_single`,
prioridad 20) que asigna `--i` a los grupos escalonados (si no, animan todos a la vez) y añade
`.is-in` a lo que ya está en el viewport (`top < innerHeight*.92`); los retrasos del hero, en CSS;
respeta reduced-motion; el JS general no repite lo que ya tiene `.is-in` (bloque 5). El sistema de
revelado en sí (clase `pfx-js` en `<head>`, mejora progresiva, nada oculto en el editor) es del
tema hijo: `../../elementor4-atomico/references/tema-hijo.md`.
**Verificar:** `lcp-breakdown` con render delay < 100 ms en la página afectada.

## 8. CSS y ajustes de Elementor

- **Inventaría antes de activar nada** (`../scripts/wp-cli/probe-perf.php`). En instalaciones
  Elementor ≥ 4.2.4 ya vienen activos Optimized Markup, Inline Font Icons, Optimized Image
  Loading, Optimized Gutenberg Loading y lazy background. Explícale al usuario qué ya está y por
  qué no se activa otra cosa.
- Lo que **no** hace nada con V4: «Lazy load background images» apunta a `.e-con.e-parent` (los
  nodos V4 nunca llevan `e-parent`); Element Cache solo guarda placeholders (V4 siempre es
  dinámico). `e_optimized_css_files` es alfa: no activar. «Load Google Fonts Locally»: no, si ya
  autoalojas (§2).
- «Optimized Gutenberg Loading» no se aplica a los singles de CPT: `wp-block-library` (17 KB,
  100 % sin usar) seguía cargando. `wp_dequeue_style('wp-block-library')` y `-theme` en
  `is_singular('<cpt>')` si la ficha no usa bloques (bloque 6). Verificar: `grep -c
  wp-block-library-css` = 0 en el single; `unused-css-rules` aprobado.

## 9. El optimizador del hosting (SiteGround Speed Optimizer)

Opciones **incompatibles con Elementor 4**, demostradas en el código del plugin:

| Opción | Qué rompe |
|---|---|
| Combine CSS | inserta el paquete tras `</title>`, antes de los CSS por breakpoint (con `media`) y del inline de Elementor → la cascada del child se invierte |
| Combine JS | mueve el inline del `<head>` (clase `pfx-js`) al final → parpadeo de animaciones |
| Defer render-blocking JS | rompe el inline `jQuery.uiBackCompat=true` → error de consola |
| Lazy Load Media | quita el `src` a los banners con `fetchpriority=high` |
| Remove Query Strings | rompe el cache-busting con `?ver=`/`filemtime` y max-age de 1 año |
| Web Fonts Optimization / Fonts Preload | trae TTF (UA de WordPress) y duplica el autoalojado |
| Minify HTML | recorta los scripts inline línea a línea sin ganar auditorías |
| Browser caching / GZIP | el servidor ya lo hace |

Mantén la exclusión `siteground_optimizer_async_javascript_exclude = [jquery-core, jquery-migrate,
jquery]`. SiteGround no aplica optimizaciones a usuarios con sesión: prueba en incógnito. Purga
todo en `elementor/core/files/clear_cache`. Verificar: bucle de `wp option get
siteground_optimizer_<opción>` sobre las opciones que deben estar a 0.

## 10. Cambiar de hosting: lo que era infraestructura

El código del child es portable; la caché, el WebP por `Accept`, la expiración y la compresión
eran del proveedor. Antes de migrar, lista cada dependencia y su equivalente:

- caché de página (§1) y su purga (sustituye `wp sg purge` por la nueva);
- `mu-plugins/` (cookie de Polylang), `fonts/` y pósters **viajan** en el paquete: compruébalo;
- WebP por `Accept` + `AddType` + `Vary` (§4.6);
- caché de estáticos (lo que se desplegó en el caso): css/js/woff2 con `Cache-Control: public,
  max-age=31536000, immutable` y medios con `max-age=2592000` (bloque 8 de
  `../../wp-migracion/scripts/htaccess-bloques.plantilla`). `immutable` es seguro en css/js porque
  WordPress/Elementor versionan con `?ver=` y el child con `filemtime`; las fuentes van sin versión
  (renombrar si cambian).
- compresión: comprueba html/css/js/svg/json con `curl -sI -H 'Accept-Encoding: gzip' <url> | grep
  -i content-encoding`; si falta alguno, el bloque `mod_deflate` comentado al final de esa misma
  plantilla.
- no copies bloques del proveedor anterior al `.htaccess` nuevo (p. ej. `SGO Unset Vary`, que
  perjudica gzip fuera de SiteGround).
Mide después del lanzamiento (con la cortina puesta mides la cortina). Runbook: `../../wp-migracion/references/runbook.md`.

## 11. Buenas prácticas (Best Practices)

En el caso estuvo en 100 todo el tiempo; el riesgo está en los cambios de JS y en lo que llegue
después:
- revisa la consola en todas las páginas tras cada ajuste de JS (`errors-in-console` y
  `deprecations` pesan);
- píxeles de terceros (Meta, Ads, TikTok) solo tras consentimiento (`third-party-cookies` pesa 5);
- HSTS, `X-Frame-Options: SAMEORIGIN` y `COOP same-origin-allow-popups` solo **después** de lanzar
  y probando el editor. Si el login usa la URL propia de SiteGround Security, `Referrer-Policy`
  solo puede ser `strict-origin-when-cross-origin`, `same-origin` o `no-referrer` (con `origin` o
  `strict-origin` el administrador no puede entrar).

## 12. Propuestos y no aplicados (medidos, sin probar en el sitio)

Ninguno hacía falta para llegar a 90. Si el proyecto necesita margen:
- tamaño de imagen de 400w para envases/productos (400w WebP q80: 72 KB frente a 121 KB del 543w);
- vídeo del hero a 720p CRF 27 (−31 %): cambio de calidad visible, decisión del cliente;
- quitar `jquery-migrate` (4,9 KB) tras revisar la consola en todas las páginas y el editor;
- quitar el CSS inline `global-styles` (~10 KB de presets de bloques sin usar) y revisar márgenes.
