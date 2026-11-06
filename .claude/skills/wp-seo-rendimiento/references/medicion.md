# Medición · Lighthouse local, PSI de Google, regresión visual y consola

**Mide, no supongas.** Un arreglo de rendimiento «funciona» cuando la mediana de 3 corridas lo
muestra; un cambio es «invisible» cuando la geometría no se mueve más de 1 px; una cifra «de
Google» vale cuando has comprobado su `formFactor` y su `fetchTime`. Herramientas en
`../scripts/medicion/` (Mac local: Node + `puppeteer-core`, Python 3, `npx lighthouse`) y
`../scripts/wp-cli/` (servidor). Instalación y uso de cada una: `../scripts/README.md`.

---

## 1. Antes de tocar nada: inventario y respaldo

Solo lectura, en este orden:
1. `wp plugin list`, `wp option get blog_public`, `probe-perf.php` (experimentos y ajustes de
   Elementor, Polylang y `PLL_COOKIE`, mu-plugins, optimizador del hosting, banderas de Yoast).
2. Cabeceras con curl (`set-cookie`, estado de la caché) de la portada, el segundo idioma y un single.
3. Metadatos del prototipo que aplican (títulos, fuentes y sus métricas si es Next.js, vídeo del hero).
4. Línea base: `lh-run.sh base 3` y `snap.mjs antes.json`.
5. Copia local del child verificada por md5 contra el servidor (la verdad está en el servidor).

Respaldo en el servidor antes de la primera escritura (solo lee el sitio; escribe en el home):
```bash
D=~/bak-seo-perf-$(date +%F); mkdir -p $D; cd <WP_PATH> && wp db export $D/db.sql --quiet && cp -a wp-content/themes/<child> $D/ \
 && for o in blogname wpseo wpseo_titles wpseo_social wpseo_premium wpseo_llmstxt; do wp option get $o --format=json > $D/$o.json; done \
 && wp option list --search="siteground_optimizer_*" --format=json > $D/sg.json && wp option list --search="elementor_*" --format=json > $D/el.json \
 && for id in <ids de documentos a editar>; do wp post meta get $id _elementor_data > $D/elementor-data-$id.json; done
```
(El dump contiene datos personales: no lo copies a tu equipo sin necesidad y bórralo, con permiso, al cerrar.)

## 2. Qué herramienta para qué

| Pregunta | Herramienta | Nota |
|---|---|---|
| ¿Mejoró? ¿qué auditoría falla? | Lighthouse local, **misma versión que PSI** | diagnóstico; mediana de 3 |
| ¿Qué dice Google? | PSI web por Puppeteer (`psi-web.mjs`) | cifra de referencia para el usuario |
| ¿Se movió el diseño? | `snap.mjs` + `cmp.py` | geometría ±1 px, no píxeles |
| ¿Rompí JS? | `console.mjs` | 2 anchos × todas las páginas |
| ¿Sigue cada arreglo en el HTML? | `checks-html.sh` | una línea por arreglo |
| ¿Qué ve el visitante detrás de la cortina? | `render.php` por WP-CLI | como usuario 1 |

**La API de PSI sin clave no sirve:** el primer `curl …/pagespeedonline/v5/runPagespeed` devolvió 429
«Quota exceeded … Queries per day» (cuota anónima compartida). Y la interfaz de pagespeed.web.dev
no pinta el informe en un panel de navegador oculto. Por eso: Lighthouse local para diagnosticar y
Puppeteer propio para la cifra de Google. **Nunca** las herramientas de navegador del usuario.

## 3. Protocolo de Lighthouse local

- Fija la versión que usa PSI: lee `lighthouseVersion` de un informe de PSI (en el caso,
  `npx -y lighthouse@13.5.0`).
- 7 URL representativas (portada, listado, página con formulario, contenido largo, single del
  CPT, segundo idioma…), en `paginas.txt`.
- Calienta la caché (2 curls por URL), luego **3 corridas por URL y formato, en serie**
  (`lh-run.sh <etiqueta> 3`), y quédate con la **mediana** (`lh-sum.py <dir> --mediana`).
- **Descarta** las corridas cuyo `network-rtt` o `network-server-latency` del origen pase de
  300 ms. Síntoma en el caso: escritorio P46–71 con FCP de 7–9 s mientras `server-response-time`
  era 105 ms; RTT del origen 1.246–1.262 ms y un handshake TLS de 4,37 s. Causa: la simulación de
  Lighthouse usa el RTT y la latencia medidos desde tu equipo; los picos de red hacia el hosting se
  trasladan a la nota (Speed Index pondera la tira observada). **Nunca decidas un cambio con una
  sola corrida de escritorio.**
- `audits.py <informe>` lista las auditorías < 0,9 con su peso y sus nodos: es lo que se arregla.
- Punto de control a mitad de sesión (tras los arreglos invisibles) para decidir si hace falta
  un cambio visible (en el caso, el vídeo diferido).

```bash
npx -y lighthouse@13.5.0 "<URL>" --quiet [--preset=desktop] --output=json --output-path=<f>.json \
  --chrome-flags="--headless=new --no-sandbox" --locale=es
```

## 4. PSI de Google por Puppeteer (`psi-web.mjs`)

Abre `https://pagespeed.web.dev/analysis?url=<URL>&form_factor=<mobile|desktop>` con un Chrome
headless propio y captura el JSON de Lighthouse de la respuesta
`POST /_/PagespeedUi/data/batchexecute?rpcids=LsX2he` (línea que empieza por `[["wrb.fr"`, JSON
dentro de JSON).

**BUG conocido (corregido en la versión del kit).** La versión original se quedaba con la
**primera** respuesta `LsX2he` que traía `categories.performance`. pagespeed.web.dev analiza móvil
y escritorio a la vez, así que al pedir escritorio guardó el móvil: `home-desktop-2.json` era
idéntico a `home-mobile-2.json` (mismo `fetchTime`, `formFactor: mobile`) y se llegó a informar un
«escritorio 94» que era el móvil. La corrección acepta el LHR **solo si
`lhr.configSettings.formFactor === ff`** y lo imprime junto al `fetchTime`.

**Regla, uses la herramienta que uses:** antes de reportar una cifra, valida cada JSON:
```bash
python3 -c "import json,sys;d=json.load(open(sys.argv[1]));print(d['configSettings']['formFactor'],d['fetchTime'],d['lighthouseVersion'])" <f>.json
python3 lh-sum.py <carpeta>     # columna formFactor; «FF!» si el nombre del archivo dice otra cosa
```
PSI solo ve URL públicas: con la cortina activa mide la cortina.

## 5. Detrás de la cortina

- **Lighthouse con una cortina por cookie:** `LH_HEADERS=<archivo.json> ./lh-run.sh …`, con
  `{"Cookie":"<nombre>=<valor>"}` en un archivo 600 que borras al terminar.
- **La llave nunca en la salida ni en el chat.** Léela en el servidor dentro de una sustitución de
  shell, pide la cookie a un jar temporal, olvida la variable, usa el jar y bórralo; UA de Chrome
  (los hostings retan al de curl):
  ```bash
  J=$(mktemp); UA='<UA de Chrome>'
  TOKEN=$(ssh <SSH_ALIAS> "grep -oE '<PARAM_LLAVE>=[a-z0-9]{8,}' <WP_PATH>/.htaccess | head -1 | cut -d= -f2")
  curl -sS -A "$UA" -o /dev/null -c "$J" "https://<DOMINIO>/?<PARAM_LLAVE>=$TOKEN"; unset TOKEN
  curl -sS -A "$UA" -b "$J" -o /dev/null -w "%{http_code} %{redirect_url}\n" "https://<DOMINIO>/<url>/"
  rm -f "$J"
  ```
- **HTML como administrador:** `RENDER_URL=/ruta/ wp --path=<WP_PATH> eval-file render.php
  2>/dev/null > pagina.html` (la cortina de Elementor excluye a los usuarios con sesión). No
  resuelve la portada del segundo idioma (`/en/`) desde CLI: esa, en vivo.
- **Coordina la medición final con quien gestiona la cortina:** «no la actives hasta que diga
  *listo*». Plan: 7 URL × 2 × 3 antes de activarla o justo después del lanzamiento.

## 6. Accesibilidad con todo revelado

Segunda pasada con movimiento reducido (axe omite lo que está a opacidad 0 por las animaciones):
```bash
npx -y lighthouse@13.5.0 "<URL>" --only-categories=accessibility \
  --chrome-flags="--headless=new --no-sandbox --force-prefers-reduced-motion" --output=json --output-path=<f>.json
```
Detalle y checklist: `accesibilidad.md`.

## 7. Qué esperar de SEO en staging

Con `blog_public=0` la categoría SEO **no puede** llegar a 100: 58 sin descripciones → 66 con
descripciones (`is-crawlable` pesa 4,04 de 12,04) → 100 al lanzar. Detrás de una cortina estática
en producción salió 58 (noindex + `robots.txt` 503). No persigas 100 en staging; demuestra con
`audits.py` que solo falla `is-crawlable`.

## 8. Regresión visual y consola

- `snap.mjs antes.json` antes de empezar y `snap.mjs despues.json` tras cada lote; `cmp.py
  antes.json despues.json 1` debe dar OK en todas las páginas y anchos. Emula reduced-motion
  (todo revelado), recorre la página para disparar lazy y espera a las fuentes; guarda alto del
  documento, `scrollWidth`, cajas y `font-family` de encabezados/párrafos/botones, cajas y
  atributos de imágenes, fuentes cargadas y lo que hay en el `<head>`. Compara geometría, no
  píxeles: robusto a antialiasing y a vídeo.
- `console.mjs` tras cada cambio de JS: errores, warnings, `pageerror` y peticiones fallidas en
  todas las páginas a 1440 y 375 px. El editor no lo cubre: la prueba del editor se le pide al
  usuario (Claude no inicia sesión).
- `sizes-sim.mjs [--lazy]` simula en vivo un cambio de `sizes`/`loading` antes de desplegarlo.
- `video-diferido.mjs` mide cuándo se pide el mp4 con y sin reduced-motion.

## 9. Verificaciones de una línea por cambio

| Cambio | Comprobación (esperado) |
|---|---|
| Cookie de Polylang | `curl -s -D - -o /dev/null <URL> \| grep -iE 'set-cookie\|x-proxy-cache'` ×2 → sin cookie, 2.ª HIT |
| Google Fonts fuera | `grep -c fonts.googleapis` = 0; `grep -o 'as="font"' \| wc -l` = nº de preloads |
| jQuery al pie | `sed -n '1,/<\/head>/p' \| grep -c jquery` = 0 |
| Póster precargado | 2 `rel="preload" as="image"` en la portada |
| Sin CSS de bloques | `grep -c wp-block-library-css` = 0 en el single |
| Revelado temprano | el script inline aparece tras `</main>` |
| Imágenes | IDs numéricos duplicados vacío; `fetchpriority="high"` ≤ 1; sin atributos repetidos; `sizes` esperados |
| Respaldo de fuente | `grep -c '<Familia> Fallback'` en `uploads/elementor/css/global-<id>-frontend-desktop.css` > 0 |
| Yoast `<head>` | `sed -n '/<!-- This site is optimized/,/<!-- \/ Yoast SEO/p' \| grep -E '<title>\|name="description"\|og:\|hreflang\|robots'` |
| JSON-LD | Python sobre `yoast-schema-graph` (tipos, piezas propias, BreadcrumbList, Organization, WebSite sin potentialAction) |
| Sitemap/robots | `sitemap_index.xml` `<loc>`; `<tax>-sitemap.xml` 404; `robots.txt` |
| WebP | `curl -sI -H 'Accept: image/webp,*/*' <jpg>` → `image/webp` + `vary: Accept` |
| Mapa 301/410 | `probar-301.py` antes; `curl -o /dev/null -w "%{http_code} %{redirect_url}"` después |

Casi todo junto, por URL: `checks-html.sh`.

## 10. Ciclo de un cambio en el child (con otra sesión en el mismo servidor)

bajar la versión del servidor → md5 → parche con `assert` del ancla y de que no esté ya aplicado →
`php -l` local → subir los archivos propios → comparar el md5 remoto con el guardado (si difiere,
abortar y volver a bajar) → subir los compartidos → `php -l` remoto → `wp elementor flush-css` →
purga → curl 200 de todas las URL → greps de verificación → `snap.mjs` + `cmp.py` + `console.mjs`
→ mensaje a la otra sesión con las líneas y el md5 final. Tras cada subida compara md5 local y
remoto: `scp` puede cortarse a medias («Connection closed») sin detener el flujo. Protocolo
completo: `../../wp-elementor-replica/references/sesiones-paralelas.md`.

## 11. Cómo presentar los resultados

- Tabla antes → después por categoría (rangos de móvil y escritorio), diciendo qué es mediana
  local y qué es PSI de Google (y con qué `fetchTime`).
- «Qué frenaba la velocidad», en lenguaje llano: caché, fuentes, vídeo, imágenes, otros.
- Qué funciones de Elementor ya estaban activas y por qué no se activó otra.
- Decisiones numeradas para el cliente (cambios de color para Accesibilidad 100, perfiles
  sociales, plugins, pasos de lanzamiento).
- Qué no se pudo verificar y por qué (p. ej. la portada del segundo idioma desde CLI).
- Dónde vive el código y dónde está el respaldo.

## 12. Cifras de referencia (caso HomeMaker)

- Lighthouse local 13.5, línea base (1 corrida por URL y formato) → final (mediana simple de 3,
  sin aplicar el filtro de red): móvil P 68–96 → 89–99; escritorio P 46–71 → 88–100;
  Accesibilidad 87–97 → 93–97; Buenas prácticas 100; SEO 58 → 66 (staging con noindex). Con el
  filtro de `lh-sum.py --mediana` esos mismos informes dan otras medianas y alguna página se queda
  sin corridas válidas: di siempre qué método usaste.
- Portada móvil: 49 (día 1, una corrida de Lighthouse local) → 84 → 94 en PSI de Google tras
  diferir el vídeo.
- Producción (Apache sin caché de página), Lighthouse local, una corrida detrás de la cortina con
  la cookie (`LH_HEADERS`): portada 93 móvil / 99 escritorio, listado 98/99, CLS 0, TTFB
  0,7–1,1 s. SEO 58 por la cortina (noindex + `robots.txt` 503). No hay PSI de Google ni
  medición con caché de página en producción.
- WebP: 156 archivos, 11,2 MB → 6,3 MB. Fuentes: 4 woff2 de 12–35 KB en lugar de 36 variantes.
- Sesión completa (inventario → informe → revisión adversarial): unas 3 h de reloj.

## 13. Trampas de método

- **zsh expande `?` y `*`**: entrecomilla siempre URL con query y patrones (`--include='*.tsx'`),
  o verás «no matches found».
- `?m=` no rompe la caché: es el archivo por fecha de WordPress (404). Usa `?nocache=$RANDOM`.
- UA de navegador en curl para admin, login y hostings con reto anti-bot.
- Dos tandas de Lighthouse a la vez se contaminan entre sí; tampoco midas mientras otra sesión
  hace curl masivos.
- Una petición con `Pragma: no-cache` tardó 9,5 s aun con HIT: se atribuyó a ruido de red. Una
  anomalía aislada no es una conclusión: repite.
- Con la cortina activa, cualquier medición anónima mide la cortina.
- Un informe «escritorio» puede ser el móvil (§4): valida `formFactor`.
