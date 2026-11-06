# Mediciones · fragmentos exactos para el QA de Elementor 4

Cada casilla de `checklist.md` se cierra con uno de estos fragmentos. Salen de la réplica de
HomeMaker (caso de referencia: WordPress 7.1, Elementor 4.3.0 con editor V4 atómico, Elementor
Pro 4.2.3, Hello + tema hijo, Polylang Pro 3.8.6, Yoast). Los selectores y los números cambian
entre proyectos; los mecanismos no.

Estado de cada fragmento: **probado en HomeMaker** (se usó así, solo saneado) o **generalizado, no
probado fuera de HomeMaker** (compuesto o parametrizado a partir de lo probado). Aquí quedan los
fragmentos cortos (consola, curl, greps) y el resumen de cada herramienta; las herramientas largas
son archivos en [`../scripts/`](../scripts/README.md), con su cabecera de propósito, uso y estado:
`qa-medir.mjs`, `qa-cmp.py`, `qa-hover-foco.mjs`, `qa-orden-clases.php`, `qa-editable.php`,
`qa-html.py`, `qa-es-en.py` y `qa-avisos.php`. Todo el PHP pasa `php -l`; todo el JS,
`node --check`; todo el Python, `python3 -m py_compile`.

## Seguridad primero

- **Solo lectura.** Ninguna sonda guarda documentos, opciones ni metas. `qa-editable.php` lleva
  un guardián que bloquea y lista cualquier escritura. Nada de `rm`: los temporales se quedan en
  `~/tmp-<PREFIJO>/` y se avisa al usuario para que los limpie él.
- **Ningún correo real.** Toda prueba de formulario o de `wp_mail` intercepta con `pre_wp_mail`
  (devolviendo `true`) antes de cargar nada. Un envío válido real le llega al cliente.
- **Ningún secreto en la salida.** Nunca `cat`/`grep` sobre `wp-config.php`, `.env`, `.my.cnf` ni
  `*.sql` (un subagente de auditoría imprimió así las credenciales de otro sitio y hubo que
  rotarlas). La llave de la cortina se lee en el servidor y no se imprime (M17). El slug de login
  real no va en informes compartibles: en el kit es `<LOGIN_SLUG>`.
- **No enciendas ni apagues** la cortina, el modo mantenimiento ni la caché para medir: otra sesión
  puede estar midiendo sin sesión y la rompes.

## Convenciones

```bash
BASE='https://<DOMINIO>'          # o <STAGING_URL>
UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36'
NC() { echo "nocache=$(date +%s)$RANDOM"; }
```

- **Romper la caché:** `?nocache=`, `?nc=` o `?v=`. **Nunca `?m=`**: `m` es la query var pública de
  WordPress del archivo por fecha y la URL da 404 (pasó midiendo todas las páginas EN con
  iframes). Tampoco `p`, `page_id`, `s`, `cat` ni `name`.
- **UA de navegador siempre.** En el caso de referencia, el borde antibot de SiteGround respondió
  403 al UA de curl en `/wp-admin/`; cuenta con lo mismo en el login y en cualquier hosting con
  antibot.
- **zsh** expande `?` y `*` («no matches found»): entrecomilla URLs y patrones. `echo ====` falla
  («= not found», opción EQUALS): usa `echo "-----"`. macOS no trae `timeout`: usa
  `ssh -o BatchMode=yes -o ConnectTimeout=15`.
- **PHP en el servidor:** archivo local → `php -l` → `scp` a `~/tmp-<PREFIJO>/` →
  `ssh <SSH_ALIAS> 'cd <WP_PATH> && wp eval-file ~/tmp-<PREFIJO>/x.php --user=1 > ~/tmp-<PREFIJO>/x.txt 2>&1'`.
  Nunca `wp eval '…'` en línea por SSH: la triple capa de comillas (zsh → ssh → PHP) rompe el código.
  Los argumentos llegan al script en `$args`.
- **Red en Claude Code:** `ssh`, `scp` y `curl` a servidores remotos pueden necesitar
  `dangerouslyDisableSandbox: true`.
- Marcadores propios de este archivo: `<CHILD>` (carpeta del tema hijo), `<ID_KIT>`
  (`wp option get elementor_active_kit`), `<ID_DOC>` (ID de la página o plantilla),
  `<PARAM_LLAVE>` (parámetro de la cortina estática), `<VERSION_PSI>` (versión de Lighthouse que
  usa PageSpeed: léela en `lighthouseVersion` de un informe de PSI; 13.5.0 en el caso de
  referencia), `<ruta>`, `<ancla>`.

## M1 · Estado del entorno, antes de medir nada

Probado en HomeMaker. Si esto falla, cualquier medición posterior mide otra cosa.

```bash
# código y tiempo, sin sesión
curl -s -A "$UA" -o /dev/null -w '%{http_code} %{time_total}s\n' "$BASE/<ruta>/?$(NC)"
# caché de página: sin Set-Cookie de idioma y HIT en la 2.ª petición (cabecera de SiteGround)
for i in 1 2; do curl -s -A "$UA" -D - -o /dev/null "$BASE/<ruta>/" | grep -iE 'set-cookie|x-proxy-cache' | tr -d '\r' | tr '\n' ' '; echo " <- #$i"; done
# la hoja del child servida es la última: ?ver= debe ser el filemtime del archivo en el servidor
curl -s -A "$UA" "$BASE/<ruta>/?$(NC)" | grep -oE "themes/<CHILD>/style.css\?ver=[0-9.]+"
ssh <SSH_ALIAS> "stat -c %Y <WP_PATH>/wp-content/themes/<CHILD>/style.css"
# ¿cortina o mantenimiento encendidos? ('' = apagado)
ssh <SSH_ALIAS> "cd <WP_PATH> && wp option get elementor_maintenance_mode_mode"
```

Lectura:
- `?ver=` viejo → falta purgar (`wp sg purge` en SiteGround; en otro hosting, su purga) o un
  plugin reescribe la versión: *Child Theme Configurator* filtra `style_loader_src` con
  `chld_thm_cfg_version`. La caché dinámica guarda el HTML entero con la URL versionada vieja y
  los estáticos van con `max-age` de un año.
- `set-cookie: pll_language` + `x-proxy-cache: MISS` + `UP:SKIP_CACHE_SET_COOKIE` → ninguna página
  se cachea (falta el mu-plugin `define('PLL_COOKIE', false)`; ver la skill `wp-seo-rendimiento`).
  Un HIT aislado puede ser falso: lo calentó una visita con cookie.
- Los 404 no se cachean en SiteGround (`no-store`, `MISS`): no hace falta purgar para verlos.

## M2 · Forzar el estado final de las animaciones (navegador propio)

Generalizado, no probado fuera de HomeMaker (allí: `is-in` + `transition:none` y 300 ms). Las
entradas con `translateY(28px)` o `opacity:0` desplazan `getBoundingClientRect` (+28 px en el `top`)
y hacen que una captura parezca un fallo. Fuérzalas **en los dos lados** (prototipo y WP) antes de
medir; en el prototipo, con sus propias clases de revelado.

```js
const P = 'pfx'; // <PREFIJO> del proyecto
document.querySelectorAll(`[data-${P}-reveal],[data-${P}-stagger]`).forEach(e => { e.style.transition = 'none'; e.classList.add('is-in'); });
document.getAnimations().forEach(a => { try { if (a.effect.getComputedTiming().iterations !== Infinity) a.finish(); } catch (e) {} });
await new Promise(r => setTimeout(r, 300));
[...document.querySelectorAll('main *')].filter(e => e.offsetParent && (getComputedStyle(e).opacity === '0' || (e.matches(`[data-${P}-reveal]`) && getComputedStyle(e).transform !== 'none'))).length
```

Debe devolver `0`. Si da más, no midas: algo sigue oculto o desplazado. Antes, comprueba
`document.visibilityState === 'visible'`: en una pestaña oculta se pausan `requestAnimationFrame` e
`IntersectionObserver` y las entradas no se disparan (capturas en blanco, «0 de 11 revelados»).
Nunca metas `location.reload()` en el mismo script: corta el valor devuelto.

## M3 · Geometría y tipografía contra el prototipo (navegador propio)

Script: [`medir-vs-prototipo.js`](../../elementor4-atomico/scripts/medir-vs-prototipo.js) de la skill `elementor4-atomico` (núcleo
probado en HomeMaker). Fuerza los revelados en cada lado, mide las secciones de primer nivel
(`x`, `y + scrollY`, ancho, alto) y las propiedades tipográficas computadas (`fontFamily`,
`fontSize`, `lineHeight`, `fontWeight`, `letterSpacing`, `color`, `textTransform`), y compara
contra el JSON del otro lado con tolerancia.

Uso en QA:
- Primero en la pestaña del prototipo (`CONFIG.lado = 'prototipo'`), copia el JSON; luego en la de
  WP con `CONFIG.referencia` = ese JSON. **Mismo ancho** en los dos lados: 1440, 1280, 768 y 375.
- En WP las secciones son `e-flexbox` con `tag=section` dentro del `<main id="content">` del child;
  en un prototipo Next, `main > section`. Ajusta `CONFIG.raiz` y `CONFIG.elementos` si el marcado
  difiere (en WP por clase `<PREFIJO>-…` o `data-<PREFIJO>`; en el prototipo por estructura).
- Con barra de scroll clásica, 1440 deja 1425 de ancho útil: anótalo en la spec. (`qa-medir.mjs`
  mide sin barras: 1440 útiles. No mezcles las dos herramientas en una comparación.)
- Fija el idioma del prototipo antes de medir la versión del idioma 2 (en el caso de referencia,
  `localStorage.setItem('<PREFIJO>-lang','en')`) y **devuélvelo** a su valor al terminar.
- Si eres subagente, no uses esta vía (necesita una pestaña): usa M5.

## M4 · Muchas páginas a 375 en una sola llamada

El mismo [`medir-vs-prototipo.js`](../../elementor4-atomico/scripts/medir-vs-prototipo.js) con `CONFIG.urls = ['/', '/<ruta>/', '/<IDIOMA_2>/']`
crea un iframe del mismo origen de 375×812 por URL (las media queries responden al ancho del
iframe) y devuelve alto del documento, desborde y alto de cada sección. Probado en HomeMaker. Si
una entrada vuelve con el título «Página no encontrada», revisa que no usaste `?m=`.

## M5 · Chrome headless propio: `qa-medir.mjs` (subagentes y QA sin navegador compartido)

Los subagentes **no usan las herramientas de navegador de la sesión**: abren pestañas propias,
dejan oculta la del usuario (se pausan sus animaciones) y producen capturas en blanco. Miden con
curl o con este script, que lanza su propio Chrome headless. `qa-medir.mjs` sirve para la
fidelidad contra el prototipo en los cuatro anchos; para la regresión de todo el sitio antes y
después de un cambio que no debe verse, usa `snap.mjs` + `cmp.py` de
[`wp-seo-rendimiento/scripts/medicion/`](../../wp-seo-rendimiento/scripts/medicion/).

```bash
# una vez: carpeta de trabajo con los scripts y puppeteer-core (ver ../scripts/README.md)
mkdir -p ~/qa-headless && cp ~/.claude/skills/wp-qa/scripts/{package.json,qa-*.mjs,qa-*.py} ~/qa-headless/ && cd ~/qa-headless && npm i
QA_REVELAR='[data-<PREFIJO>-reveal],[data-<PREFIJO>-stagger]' node qa-medir.mjs wp.json "$BASE" "/,/<ruta>/,/<IDIOMA_2>/"
QA_REVELAR='.reveal,.reveal-zoom' QA_IDIOMA='<PREFIJO>-lang=es' node qa-medir.mjs proto.json http://localhost:3000 "/,/<ruta>/"
```

Script: [`qa-medir.mjs`](../scripts/qa-medir.mjs). Por cada ancho abre una pestaña headless con
`prefers-reduced-motion: reduce`, recorre la página (dispara lazy-load e IntersectionObserver),
añade `is-in` a `QA_REVELAR`, termina las animaciones finitas y guarda por `<ancho></ruta/>`: `x`,
`y` absoluta, ancho y alto de las secciones de primer nivel (`QA_SECCIONES`, por defecto
`main section`), encabezados, textos y botones con su tipografía computada, imágenes con `sizes`,
`loading`, `fetchpriority` y `alt`, fuentes cargadas y consola. Nunca usa `?m=`.
Estado: generalizado, no probado fuera de HomeMaker. Puppeteer oculta las barras de scroll: el ancho
útil es el del viewport (1440, no 1425); mide los dos lados con la misma herramienta.

Lectura: el sitio debe revelarlo todo con movimiento reducido, así que `ocultos` > 0 = contenido
visible con opacidad 0; `desborde` = scroll horizontal; `consola` recoge errores, avisos,
`pageerror` y peticiones fallidas.

## M6 · Comparar con tolerancia: `qa-cmp.py`

Compara salidas de `qa-medir.mjs` (su formato no es el de `snap.mjs`).

Script: [`qa-cmp.py`](../scripts/qa-cmp.py). Por página y ancho: alto del documento, número de
secciones y cada sección cuyo `[x, y, w, h]` se sale de la tolerancia; además, scroll horizontal,
opacidad 0 y consola del segundo archivo. Con `--todo`, geometría y tipografía de encabezados,
textos, botones e imágenes. Estado: generalizado, no probado fuera de HomeMaker.

```bash
python3 qa-cmp.py proto.json wp.json 1            # 1440, 1280, 768 y 375, y el idioma 2
python3 qa-cmp.py proto.json wp.json 3            # solo anchos intermedios (1100, 900, bordes de breakpoint)
python3 qa-cmp.py antes.json despues.json 1 --todo # regresión de un cambio que no debe verse
```

Tolerancia canónica ([verificacion.md](../../wp-elementor-replica/references/verificacion.md)):
objetivo ≤1 px; se aceptan ≤3 px por sección solo en anchos intermedios (1100, 900 y bordes de
breakpoint); el idioma 2 frente al idioma base, ≤1 px. Regresión tras un cambio que no debe verse:
tolerancia 1 con `--todo`. Antes de «corregir» una diferencia, localiza la sección que la produce y
busca si fue una decisión (en HomeMaker, 33 px de más venían del pie, por un enlace añadido a
propósito).

## M7 · Residuos de los contenedores atómicos (10 px y min-width 30 px)

Script: [`auditar-padding-atomico.js`](../../elementor4-atomico/scripts/auditar-padding-atomico.js) de `elementor4-atomico`
(núcleo probado en HomeMaker). `.elementor .e-flexbox-base` (0,2,0) trae `padding:10px`; los
`e-div-block` y `e-flexbox` vacíos traen `min-width:30px`. Si la clase no los anula, aparecen 10 px
de más por lado o iconos de 16 px que ocupan 30 (así salieron en el caso de referencia). Debe dar `total: 0` a 1440 y a 375, cabecera y pie incluidos.
Sin navegador: pásalo como `page.evaluate()` dentro de un script de Puppeteer como `qa-medir.mjs`.

## M8 · Saltos de ancla y elementos sticky

Probado en HomeMaker. Elementor pone `@media (prefers-reduced-motion:no-preference){html{scroll-behavior:smooth}}`
y Chrome retrasa o cancela el salto al llegar a `/pagina/#ancla` desde otra página. El iframe
registra `scrollY` y `scroll-behavior` cada 3 ms: debe llegar a la posición final en un solo paso.

```js
const log = []; const f = document.createElement('iframe'); f.style.cssText = 'position:fixed;left:0;top:0;width:1440px;height:900px;z-index:99999;background:#fff'; document.body.appendChild(f);
const t0 = performance.now(); let last = ''; const T = () => Math.round(performance.now() - t0);
const iv = setInterval(() => { try { const w = f.contentWindow, d = w.document; if (!/<ruta>/.test(w.location.href)) return;
 const s = [d.readyState, w.getComputedStyle(d.documentElement).scrollBehavior, Math.round(w.scrollY)].join(',');
 if (s !== last) { last = s; log.push([T(), s]); } } catch (e) {} }, 3);
f.src = '/<ruta>/#<ancla>';
await new Promise(r => setTimeout(r, 4500)); clearInterval(iv); f.remove(); log
```

El `top` del destino debe ser su `scroll-margin-top` (el del prototipo). Repite a 375×812.

Sticky (generalizado a partir del caso de referencia): mide **sin sesión**. Con sesión, la barra de
administración baja la cabecera fija 32 px (46 px ≤782, 0 ≤600) y tapa el sticky: en el caso de
referencia, «no queda fijo» era eso.

```js
const SEL = '.elementor-location-header, .<PREFIJO>-indice'; const out = [];
for (const y of [0, 800, 2000, 4000]) { scrollTo(0, y); await new Promise(r => setTimeout(r, 200)); out.push([y, ...[...document.querySelectorAll(SEL)].map(e => { const s = getComputedStyle(e); return [Math.round(e.getBoundingClientRect().top), s.position, s.top]; })]); }
scrollTo(0, 0); JSON.stringify({ adminBar: document.body.classList.contains('admin-bar'), out })
```

## M9 · Hover y foco

### M9a · En el CSS generado (probado en HomeMaker)

Si el kit define color de enlace en hover, emite `.elementor-kit-<ID_KIT> a:hover{color:…}` (0,2,1),
que gana a `.elementor .<clase-botón>{color:…}` (0,2,0): si la variante hover de un botón `<a>` solo
cambia el fondo, el texto se vuelve invisible.
El editor V4 compila la variante hover como `:hover, :focus-visible`.

```bash
# regla a:hover del kit (si el sitio imprime el CSS en línea, búscala en el HTML)
curl -s -A "$UA" "$BASE/wp-content/uploads/elementor/css/post-<ID_KIT>.css?$(NC)" | tr '}' '\n' | grep -oE '[^{]*a:hover\{[^}]*'
# CSS de clases globales de la página, por breakpoint
for f in $(curl -s -A "$UA" "$BASE/<ruta>/?$(NC)" | grep -oE "global-[0-9]+-frontend-[a-z_]+\.css" | sort -u); do
  echo "== $f"
  # variantes hover sin color de texto propio (candidatas al texto invisible)
  curl -s -A "$UA" "$BASE/wp-content/uploads/elementor/css/$f?$(NC)" | tr '}' '\n' | grep ':hover' | grep -vE '[{;]color:'
done
```

### M9b · Con ratón y teclado reales: `qa-hover-foco.mjs`

Generalizado, no probado fuera de HomeMaker. Pasa el ratón por cada botón y mide el contraste en
hover; luego recorre la página con Tab y anota el indicador de foco, el magenta `#c36`
(`rgb(204, 51, 102)`) del `reset.css` de Hello y el foco que entra en paneles `aria-hidden`
(el panel móvil del menú de Pro solo quita del orden de tabulación sus propios enlaces).

```bash
node qa-hover-foco.mjs "$BASE" /<ruta>/ 1440
node qa-hover-foco.mjs "$BASE" /<ruta>/ 375 'a.e-button-base,button,.elementor-menu-toggle' 60
```

Script: [`qa-hover-foco.mjs`](../scripts/qa-hover-foco.mjs). Hover: color y fondo en reposo y en
hover, fondo efectivo con las capas semitransparentes compuestas, ratio WCAG contra 4,5 o 3 y aviso
de `#c36`; si el fondo es imagen o vídeo, lo deja para M10b. Foco: por cada Tab, etiqueta, nombre,
`outline` y `box-shadow`, y aviso si no hay indicador, si el foco cae en un elemento no visible o
dentro de `[aria-hidden="true"]`. Salida en líneas `ok` / `FALLA`.

Si solo tienes la consola: `el.focus()` y lee `getComputedStyle(el).outline` y `.boxShadow`, y
busca en `document.styleSheets` la regla que gana. Un foco con `:where()` tiene especificidad 0 y
pierde contra `[tabindex]:not([tabindex="-1"]):focus-visible` (0,3,0) de Hello.

## M10 · Contraste AA

Mínimos: **4,5:1** texto normal · **3:1** texto grande (≥24 px, o ≥18,66 px en negrita). Contra el
fondo **real**.

### M10a · Texto sobre fondo liso, con colores computados (generalizado, no probado fuera de HomeMaker)

```js
const rgba = s => { const m = /rgba?\(([^)]+)\)/.exec(s || ''); if (!m) return null; const p = m[1].split(/[\s,/]+/).filter(Boolean).map(Number); return [p[0], p[1], p[2], p.length > 3 ? p[3] : 1]; };
const sobre = (a, b) => [0, 1, 2].map(i => a[i] * a[3] + b[i] * (1 - a[3]));
const lum = c => { const s = c.map(v => { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * s[0] + 0.7152 * s[1] + 0.0722 * s[2]; };
const fondo = el => { const capas = []; for (let e = el; e; e = e.parentElement) { const s = getComputedStyle(e); if (s.backgroundImage !== 'none') return { img: true }; const c = rgba(s.backgroundColor); if (c && c[3] > 0) { capas.push(c); if (c[3] >= 1) break; } } let bg = [255, 255, 255]; for (const c of capas.reverse()) bg = sobre(c, bg); return { rgb: bg }; };
const medir = sel => [...document.querySelectorAll(sel)].filter(e => e.offsetParent && e.textContent.trim()).map(e => {
  const s = getComputedStyle(e), f = fondo(e), c = rgba(s.color), t = e.textContent.trim().slice(0, 30);
  if (!c || f.img) return { sel, t, nota: f.img ? 'sobre imagen o vídeo: método de foto' : 'color no parseable: no verificado' };
  const tx = sobre(c, f.rgb), x = lum(tx), y = lum(f.rgb), r = (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
  const px = parseFloat(s.fontSize), min = (px >= 24 || (px >= 18.66 && Number(s.fontWeight) >= 700)) ? 3 : 4.5;
  return { sel, t, ratio: Math.round(r * 100) / 100, min, pasa: r >= min }; });
JSON.stringify(['h1', 'h2', 'h3', 'p', 'a', 'button', 'label'].flatMap(medir).filter(x => !x.pasa))
```

Devuelve solo lo que falla o no se pudo medir. No tiene en cuenta la `opacity` de los
contenedores: si la usan, compón a mano como en el último párrafo de M10b.

### M10b · Texto sobre foto o vídeo con velo (probado en HomeMaker)

Script: [`contrast.py`](../../elementor4-atomico/scripts/contrast.py) de `elementor4-atomico`. Recorta cada fotograma (o la
imagen del banner) como `object-fit:cover` al tamaño real de la caja, compone el velo con alfa
combinado `1-(1-a)(1-b)` en cada píxel y muestrea la banda de cada texto según su geometría
**medida** (M3/M5). Informa peor caso, mediana y fotogramas por debajo de 4,5:1 y de 3:1. Un color
picker sobre una captura no sirve: el contraste cambia con cada fotograma y con el degradado.

Para un par concreto con transparencia (texto blanco al 45 % sobre un fondo liso, por ejemplo),
compón antes de calcular: color efectivo = `a·texto + (1−a)·fondo`, canal a canal.

### M10c · Lighthouse con movimiento reducido (probado en HomeMaker)

axe (Lighthouse) **no audita texto con opacidad 0**, así que todo lo que está bajo el pliegue con
entrada animada queda fuera de PSI. Pasada extra con todo revelado:

```bash
npx -y lighthouse@<VERSION_PSI> "$BASE/<ruta>/" --only-categories=accessibility \
  --chrome-flags="--headless=new --no-sandbox --force-prefers-reduced-motion" --output=json --output-path=qa-a11y.json
python3 -c "import json;d=json.load(open('qa-a11y.json'));a=d['audits']['color-contrast'];print(a['score'],[i['node']['snippet'][:80] for i in a.get('details',{}).get('items',[])][:10])"
```

axe trunca 4,498 a 4,49: un rojo de 4,498:1 falla aunque «casi» pase.

## M11 · Clases globales: orden frontend = preview y CSS generado

`Global_Classes_Repository::put()` solo escribe el orden del contexto en el que corre (frontend:
meta del kit `_elementor_global_classes_order`). El editor lee
`_elementor_global_classes_order_preview` y, **al publicar cualquier página, borra del sitio** (`wp_delete_post` forzado) las clases que no ve
allí y las quita de los documentos.

Script: [`qa-orden-clases.php`](../scripts/qa-orden-clases.php) (probado en HomeMaker como one-liner
tras la migración):

```bash
scp -q ~/.claude/skills/wp-qa/scripts/qa-orden-clases.php <SSH_ALIAS>:tmp-<PREFIJO>/
ssh <SSH_ALIAS> 'cd <WP_PATH> && wp eval-file ~/tmp-<PREFIJO>/qa-orden-clases.php'
# kit <ID_KIT> · frontend 243 · preview 243 · iguales: sí   ← así cerró el caso de referencia
```

Debe decir `iguales: sí`. Un preview sin orden guardado (preview 0) no borra nada en Elementor 4.2+,
que usa entonces el orden frontend como respaldo; la sonda lo indica aparte. Si no son iguales, el arreglo es
[`e4-sync-preview.php`](../../elementor4-atomico/scripts/e4-sync-preview.php) de `elementor4-atomico`:
**escribe**, así que lo ejecuta quien construye, no el QA. En HomeMaker, la comprobación REST del
script original imprimía siempre `REST preview [0,N]` (la respuesta no trae el orden donde el
script lo buscaba): fíate de la comparación de metas y del recuento de items, no de un `[0,N]`.

Probado en HomeMaker: el CSS de cada documento (`global-<ID_DOC>-frontend-<breakpoint>.css`) solo
incluye las clases que usa **algún elemento V4** de ese documento. Una clase puesta solo en
`_css_classes` de un widget V3 (Loop Grid, filtro, Form, Shortcode) no llega:

```bash
curl -s -A "$UA" "$BASE/wp-content/uploads/elementor/css/global-<ID_DOC>-frontend-desktop.css?$(NC)" | tr '}' '\n' | grep -o '<PREFIJO>-<clase>[^{]*{[^}]*'
```

Las variables V4 salen en `:root` con su etiqueta como nombre (etiquetas sin espacios). En la
consola: `getComputedStyle(document.documentElement).getPropertyValue('--<etiqueta>')` → el valor esperado.

## M12 · Editabilidad por datos: `qa-editable.php`

Claude no inicia sesión en el editor, así que la editabilidad se verifica **por datos** y la prueba
visual en el editor se le pide al usuario. La sonda valida lo que el editor usará: parsers de
Elementor en modo editor, clases en frontend y preview, ids, Loop Grid y filtro, carga del documento
y render.

```bash
scp -q ~/.claude/skills/wp-qa/scripts/qa-editable.php <SSH_ALIAS>:tmp-<PREFIJO>/ && ssh <SSH_ALIAS> 'cd <WP_PATH> && wp eval-file ~/tmp-<PREFIJO>/qa-editable.php <ID_DOC>,<ID_PLANTILLA> --user=1 > ~/tmp-<PREFIJO>/qa-editable.txt 2>&1; grep -E "ISSUE|AVISO|RESUMEN|Escrituras|fatal|Uncaught" ~/tmp-<PREFIJO>/qa-editable.txt | head -60'
```

Script: [`qa-editable.php`](../scripts/qa-editable.php) (generalizado, no probado fuera de HomeMaker).
Arranca con un **guardián de escrituras** (filtros `update_/add_/delete_post_metadata` y
`pre_update_option` a `PHP_INT_MIN` que anulan y listan cada intento) y con el preámbulo de modo
editor de `e4-dynamic-tags-preambulo.php` (sin él, WP-CLI cuenta como frontend y los props dinámicos
no validan). Después: orden de clases frontend = preview; por documento, JSON válido, ids únicos de 7
caracteres `[a-z0-9]`, `Props_Parser` sobre `get_props_schema()` de cada elemento atómico, claves
fuera del esquema, estilos locales con `Style_Parser`, cada clase referenciada en el repositorio
frontend **y** en preview, índices `_elementor_used_global_class(_preview)`, Loop Grid con plantilla
publicada, filtro apuntando a un grid del documento, carga como la pide el editor y render de cada
sección sin excepciones ni avisos PHP.

Lectura: `claves fuera del esquema` y `props inválidas` se pierden o rompen en el siguiente guardado
desde el editor; `NO está en preview` = el editor borrará esa clase; `Escrituras bloqueadas`
distinta de «ninguna» es informativa (Elementor intentó cachear o indexar algo y el guardián lo
anuló) pero anótala. El guardián no cubre `add_option`, `$wpdb` directo ni archivos.

## M13 · HTML estático: `qa-html.py` y greps de una línea

Script: [`qa-html.py`](../scripts/qa-html.py) (generalizado, no probado fuera de HomeMaker: reúne las
comprobaciones de las revisiones de accesibilidad del caso; solo biblioteca estándar, sirve para
subagentes sin navegador). Imprime
`lang`, los `<main>` (debe haber exactamente uno), el skip-link y si su destino existe, los h1 y los
saltos de nivel, el `aria-label` de cada `<nav>` (repetidos marcados), el nombre de cada `<form>` y
los campos con `autocomplete`, y los problemas con su línea: `<img>` sin `alt`, `<svg>` sin
`aria-hidden` ni `role="img"`, enlaces o botones sin nombre accesible, interactivos anidados e ids
duplicados.

```bash
curl -s -A "$UA" "$BASE/<ruta>/?$(NC)" > p.html && python3 qa-html.py p.html
```

Greps probados en HomeMaker:

```bash
H=$(curl -s -A "$UA" "$BASE/<ruta>/?$(NC)")
echo "$H" | grep -c '<main id="content"'                                    # 1
echo "$H" | grep -o '<nav aria-label="[^"]*"'                                # únicos y en el idioma de la página
echo "$H" | grep -o 'data-elementor-type="[^"]*" data-elementor-id="[0-9]*"' # plantillas que se aplican
echo "$H" | grep -o 'fetchpriority="high"' | wc -l                            # 1: solo la imagen LCP
echo "$H" | grep -c 'autocomplete="'                                         # en páginas con formulario
echo "$H" | grep -o 'alt="[^"]*"' | sort | uniq -c | sort -rn | head          # alt vacíos o repetidos
```

El alt de un `e-image` con ID de adjunto sale de la Biblioteca de medios (el del widget se ignora);
el alt del segundo idioma, de su meta propia si el child la implementa.

## M14 · Segundo idioma

Probado en HomeMaker. Tres capas: restos del idioma base en el HTML, medición contra el prototipo
en ese idioma (M3/M5 con el idioma fijado) e interacción real (selector de idioma, filtros AJAX).

Script: [`qa-es-en.py`](../scripts/qa-es-en.py) (probado en HomeMaker con el par es→en; para otro par,
cambia sus listas). Busca palabras con tilde, `ñ`, `¿`, `¡` o frecuentes del español en el texto
visible y en `alt`, `aria-label`, `title`, `placeholder`, `content` y `data-<PREFIJO>-*`:

```bash
curl -s -A "$UA" "$BASE/<IDIOMA_2>/<ruta>/?$(NC)" > en.html && python3 qa-es-en.py en.html pfx   # pfx = <PREFIJO> del proyecto
```

```bash
EN=$(curl -s -A "$UA" "$BASE/<IDIOMA_2>/<ruta>/?$(NC)")
echo "$EN" | grep -o '<html[^>]*lang="[^"]*"'
echo "$EN" | grep -o 'data-elementor-type="\(header\|footer\)" data-elementor-id="[0-9]*"'   # IDs de las plantillas del idioma 2, no las del base
echo "$EN" | grep -o 'hreflang="[a-zA-Z-]*"' | sort -u                                      # los dos idiomas + x-default
echo "$EN" | grep -o '<form[^>]*aria-label="[^"]*"'                                          # el nombre del formulario se lee en voz alta
# enlaces internos que siguen yendo al idioma base (revisa a mano: el selector de idioma sí debe ir)
echo "$EN" | grep -oE 'href="https?://[^"]*"' | grep -F "${BASE#https://}" | grep -vE "/<IDIOMA_2>/|/wp-content/|/wp-json/" | sort -u | head
```

La portada del idioma 2 debe servirse en `/<IDIOMA_2>/`, no en `/<IDIOMA_2>/home/`
(`curl -s -o /dev/null -w '%{http_code} %{redirect_url}'`). Los documentos del idioma 2 se regeneran
por script desde el base: un arreglo hecho a mano en ellos se pierde en la siguiente regeneración.

## M15 · Formularios en seco

Script: [`probe-forms-seco.php`](../../elementor4-atomico/scripts/probe-forms-seco.php) de
`elementor4-atomico` (núcleo probado en HomeMaker). Descubre los widgets «form» de la página, valida en memoria (`Form_Record` +
`Ajax_Handler`) una matriz de casos contra los hooks desplegados y simula el correo del caso válido
con `pre_wp_mail`: **no envía nada** (y aborta si un plugin redefine `wp_mail`). Con
`E4_CABECERAS=1` imprime también From, FromName, Sender y Reply-To reales de PHPMailer.

```bash
scp -q ~/.claude/skills/elementor4-atomico/scripts/probe-forms-seco.php <SSH_ALIAS>:tmp-<PREFIJO>/ && ssh <SSH_ALIAS> 'cd <WP_PATH> && E4_PAGINA=<ID_DOC> E4_CABECERAS=1 wp --url=https://<DOMINIO> --user=1 eval-file ~/tmp-<PREFIJO>/probe-forms-seco.php > ~/tmp-<PREFIJO>/forms.txt 2>&1; grep -iE "válido|correo simulado|cabeceras|fatal" ~/tmp-<PREFIJO>/forms.txt | head -40'
```

Repite con el ID de la página del idioma 2. Casos esperados en el caso de referencia (antispam =
honeypot nativo + tiempo mínimo de 3000 ms medido en el navegador): `válido` → true, con
`Reply-To` = email del visitante y remitente del dominio (From y Sender alineados); `trampa llena`,
`en 0,9 s`, `sin JS` y `sin email` → false; `email sin TLD` → false con un mensaje de email, no
«Este campo es obligatorio». La salida lleva los destinatarios reales: no la pegues en documentos
públicos. Nunca se prueba con un envío válido real: le llega al cliente; la prueba real es del
usuario, a su buzón y con su permiso.

Marcadores que el HTML debe llevar (probado en HomeMaker):

```bash
H=$(curl -s -A "$UA" "$BASE/<ruta>/?$(NC)")
echo "$H" | grep -o 'class="elementor-form"[^>]*>'
echo "$H" | grep -o '<form[^>]*aria-label="[^"]*"'            # el nombre del formulario se lee en voz alta
echo "$H" | grep -o 'autocomplete="[a-z-]*"' | sort | uniq -c   # WCAG 1.3.5
echo "$H" | grep -o 'href="tel:[^"]*"\|href="mailto:[^"]*"'
```

## M16 · 404, login y mantenimiento por curl

Probado en HomeMaker. La batería completa de códigos, redirecciones, 301/410 y cabeceras del
dominio (modos `publico`, `llave` y `lanzado`) es
[`comprobar-http.sh`](../../wp-migracion/scripts/comprobar-http.sh) de `wp-migracion`; aquí, lo que
el QA de una plantilla especial necesita. Slugs absurdos para el 404: `/product` no da 404 sino
301 a `/products/` (`redirect_guess_404_permalink`).

```bash
# 404 real en los dos idiomas: HTTP 404, plantilla error-404 con el ID de su idioma, un solo <main>, lang y título traducidos
for u in /no-existe-xq/ '/<IDIOMA_2>/does-not-exist-xq/'; do
  curl -s -A "$UA" -o /tmp/qa404.html -w "%{http_code} $u\n" "$BASE$u"
  grep -o 'data-elementor-type="[^"]*" data-elementor-id="[0-9]*"' /tmp/qa404.html
  echo "main: $(grep -c '<main' /tmp/qa404.html)"
  grep -o '<html[^>]*>\|<title>[^<]*' /tmp/qa404.html | head -2
done

# login propio (SG Security): el slug da 302 a wp-login.php?sgs-token=… + cookie sgs-token-login-*;
# wp-login.php y wp-admin/ sin sesión acaban en /404; /wp-json/ no cambia
h(){ curl -s -A "$UA" -o /dev/null -D - "$@" | grep -iE '^HTTP|^location|^set-cookie: sgs|x-proxy-cache:'; }
f(){ curl -s -A "$UA" -L -o /dev/null -w '%{http_code} %{url_effective}\n' "$@"; }
h "$BASE/<LOGIN_SLUG>"; f "$BASE/wp-login.php"; f "$BASE/wp-admin/"; f "$BASE/wp-json/"
# Referrer-Policy compatible con el token: strict-origin-when-cross-origin, same-origin o no-referrer (origin/strict-origin lo rompen)
curl -sI -A "$UA" "$BASE/wp-login.php?sgs-token=<LOGIN_SLUG>" | grep -i 'referrer-policy'
# login de marca en el idioma 2: la clase propia en el <body> (locale-* no sigue al desplegable)
curl -s -A "$UA" "$BASE/wp-login.php?sgs-token=<LOGIN_SLUG>&wp_lang=en_US" | grep -o '<body[^>]*>'   # wp_lang = locale del idioma 2

# cortina: maintenance = 503 + Retry-After; coming_soon = 200 + noindex en TODO, robots.txt incluido
curl -sI -A "$UA" "$BASE/" | grep -iE '^HTTP|retry-after'
curl -sI -A "$UA" "$BASE/robots.txt" | grep -iE '^HTTP|content-type'   # coming_soon: 200 text/html (mal en un dominio con historial)
# vista previa de la plantilla sin encender el modo (si el child la implementa con un parámetro propio)
curl -s -A "$UA" "$BASE/?<PREFIJO>-vista=proximamente&$(NC)" | grep -oE 'elementor-template-canvas|<html[^>]*>|noindex, nofollow|elementor-location-header' | sort | uniq -c
```

Esperado en la vista previa: `elementor-template-canvas`, `lang` correcto, `noindex, nofollow` y
**ninguna** `elementor-location-header`. Recuperación del login si algo sale mal:
`wp sg custom-login disable` o `/wp-login.php?sgs-token=<LOGIN_SLUG>`.

## M17 · Detrás de la cortina

Con la cortina puesta, curl y PageSpeed solo ven la cortina. Dos vías, las dos probadas en HomeMaker:

- **Como administrador, por WP-CLI:**
  [`render.php`](../../wp-seo-rendimiento/scripts/wp-cli/render.php) de `wp-seo-rendimiento` pinta la URL como usuario 1 (la cortina excluye a los usuarios con sesión):
  `RENDER_URL=/<ruta>/ wp --path=<WP_PATH> eval-file ~/tmp-<PREFIJO>/render.php 2>/dev/null > ~/tmp-<PREFIJO>/r.html`,
  y luego `qa-html.py` o los greps sobre ese archivo. Límite: Polylang no resuelve
  `/<IDIOMA_2>/` como portada estática desde CLI; esa URL se comprueba en vivo.
- **Como visitante con la llave (cortina estática):**
  [`comprobar-http.sh`](../../wp-migracion/scripts/comprobar-http.sh) de `wp-migracion`, modo
  `llave`: lee la llave por SSH dentro de una sustitución de shell, la canjea por una cookie en un
  archivo temporal, **nunca la imprime** y borra el archivo al salir. Recorre todas las URL
  publicadas en los dos idiomas.

## M18 · Consola y errores PHP

- **Consola del navegador:** la recoge `qa-medir.mjs` (M5) en cada ancho y página; para el sitio
  entero, [`console.mjs`](../../wp-seo-rendimiento/scripts/medicion/console.mjs) de
  `wp-seo-rendimiento`. Tras cada cambio de JS, todas las páginas sin errores (`errors-in-console` resta en Buenas prácticas).
- **Fatal de `wp eval-file`** (probado en HomeMaker): el manejador de WP imprime su página HTML de
  «error crítico» y tapa el `Fatal error: Uncaught …`. Vuelca a archivo y busca:

```bash
ssh <SSH_ALIAS> 'cd <WP_PATH> && wp eval-file ~/tmp-<PREFIJO>/x.php --user=1 > ~/tmp-<PREFIJO>/salida.txt 2>&1; grep -i -m5 "fatal\|uncaught" ~/tmp-<PREFIJO>/salida.txt | cut -c1-300; tail -20 ~/tmp-<PREFIJO>/salida.txt | cut -c1-250'
```

- **Avisos PHP del child al pintar una página:** [`qa-avisos.php`](../scripts/qa-avisos.php), que se
  carga con `--require` junto a `render.php` e imprime por STDERR los avisos PHP de archivos del
  child, `_doing_it_wrong` y funciones obsoletas. Generalizado, no probado: la sonda existió en una
  revisión de HomeMaker, pero su uso con `--require` no consta.

```bash
scp -q ~/.claude/skills/wp-qa/scripts/qa-avisos.php ~/.claude/skills/wp-seo-rendimiento/scripts/wp-cli/render.php <SSH_ALIAS>:tmp-<PREFIJO>/
ssh <SSH_ALIAS> 'cd <WP_PATH> && RENDER_URL=/<ruta>/ wp --require=$HOME/tmp-<PREFIJO>/qa-avisos.php eval-file ~/tmp-<PREFIJO>/render.php > /dev/null'
```

## M19 · Rendimiento básico

Control de humo, no auditoría: el protocolo completo (PSI de Google por Puppeteer, regresión
visual, fuentes, imágenes) está en [medicion.md](../../wp-seo-rendimiento/references/medicion.md)
de la skill `wp-seo-rendimiento`, y las herramientas en
[`scripts/medicion/`](../../wp-seo-rendimiento/scripts/medicion/) (`lh-run.sh` ya imprime la tabla
de `lh-sum.py`).

```bash
# misma versión de Lighthouse que PSI; calienta la caché y hace 3 corridas móvil + escritorio en serie
BASE="$BASE" LH_VERSION=<VERSION_PSI> ./lh-run.sh qa-<ruta> 3 "<ruta>|/<ruta>/"
# tabla con la mediana, descartando corridas con rtt o latencia del origen > 300 ms
python3 lh-sum.py psi/qa-<ruta> --mediana --max-red 300
# lo que cada arreglo de rendimiento dejó en el HTML servido, una línea por comprobación
BASE="$BASE" ./checks-html.sh /<ruta>/
```

- Nunca decidas con una sola corrida de escritorio: en el caso de referencia, con picos de RTT de
  1,3 s hacia el hosting, el escritorio daba FCP de 7–9 s mientras el servidor respondía en 105 ms
  (la simulación de Lighthouse traslada la red a la nota).
- La API de PageSpeed sin clave devuelve 429 (cuota anónima compartida): no la uses.
- SEO en staging se queda en 58–66 con `blog_public=0` (`is-crawlable`): no persigas 100 antes de
  lanzar. Con la cortina encendida, Lighthouse mide la cortina (`LH_HEADERS` con la cookie).
- Esperado en el caso de referencia: sin `set-cookie: pll_language` y HIT en la 2.ª petición (M1),
  `fonts.googleapis` 0 si las fuentes son propias, jQuery fuera del `<head>`, un solo
  `fetchpriority="high"`, CLS ≈ 0.
