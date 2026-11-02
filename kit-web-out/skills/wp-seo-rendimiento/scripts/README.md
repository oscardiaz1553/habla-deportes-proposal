# Scripts de wp-seo-rendimiento

Herramientas de la sesión de SEO y rendimiento. Salen del caso de referencia (HomeMaker) y están
generalizadas: sin dominios, rutas ni prefijos del proyecto. Cada archivo lleva en su cabecera el
propósito, el uso exacto, las variables y su **estado** («probado en HomeMaker» o «generalizado, no
probado fuera de HomeMaker»). Todos pasan `php -l` (PHP 8.5), `node --check`, `python3 -m
py_compile` o `bash -n`; los de Python se probaron además en local con los informes del caso.

Marcadores: `<WP_PATH>` ruta de WordPress en el servidor, `<STAGING_URL>` / `<DOMINIO>`,
`<SSH_ALIAS>`, `<PREFIJO>` (p. ej. `hm`), `<IDIOMA_BASE>` / `<IDIOMA_2>`.

## medicion/ — en tu Mac (nunca contra el navegador del usuario)

Requisitos: Node ≥ 18, Python 3, Google Chrome (o `CHROME=<ruta>`), `npx`. Una vez:
`cd medicion && npm i` (instala `puppeteer-core`). Copia `paginas.ejemplo.txt` a `paginas.txt`.

| Archivo | Qué hace | Uso | Estado |
|---|---|---|---|
| `paginas.ejemplo.txt` | URL representativas `nombre\|/ruta/` que leen los demás | copiar a `paginas.txt` | — |
| `lh-run.sh` | Lighthouse local móvil+escritorio, N corridas en serie con caché caliente; opcional detrás de cortina por cookie (`LH_HEADERS`) | `BASE=https://<STAGING_URL> ./lh-run.sh final 3` | generalizado, no probado fuera |
| `lh-sum.py` | tabla por informe (P/A/BP/SEO, FCP/LCP/TBT/CLS/SI, rtt/srv del origen, `formFactor`, `fetchTime`, «FF!») y `--mediana` descartando corridas con red > 300 ms | `python3 lh-sum.py psi/final --mediana` | generalizado, probado en local con los informes del caso |
| `audits.py` | auditorías < 0,9 por categoría con peso y nodos | `python3 audits.py <informe.json>` | copiado del caso, genérico |
| `psi-web.mjs` | cifra de PSI de Google sin API: pagespeed.web.dev por Puppeteer, captura `rpcids=LsX2he`. **Bug de `formFactor` corregido** | `BASE=https://<DOMINIO> node psi-web.mjs / desktop home-desktop.json` | generalizado; corrección solo con `node --check`: valida con `lh-sum.py` |
| `snap.mjs` | foto geométrica por página y ancho (reduced-motion, lazy disparado, fuentes listas) | `BASE=… node snap.mjs antes.json` | generalizado, no probado fuera |
| `cmp.py` | compara dos fotos con tolerancia en px | `python3 cmp.py antes.json despues.json 1` | copiado del caso, probado en local |
| `console.mjs` | errores/warnings/pageerror/peticiones fallidas, 1440 y 375 px | `BASE=… node console.mjs` | generalizado, no probado fuera |
| `sizes-sim.mjs` | simula en vivo `sizes="auto, 100vw"` (y `--lazy`) y lista imágenes que cambiarían de tamaño | `BASE=… node sizes-sim.mjs --lazy` | generalizado, no probado fuera |
| `video-diferido.mjs` | cuándo se pide el mp4 del vídeo de fondo, con y sin reduced-motion; estado de controles | `BASE=… node video-diferido.mjs /` | generalizado, no probado fuera |
| `checks-html.sh` | verificaciones de una línea por URL: cookie/caché, googleapis, preloads, jQuery en head, fetchpriority, atributos repetidos, ids numéricos, head de Yoast, hreflang, tipos JSON-LD, sitemap y robots | `BASE=… ./checks-html.sh` | generalizado, no probado fuera (parseo probado en local) |
| `probar-301.py` | simula un mapa de `RewriteRule` 301/410 contra URL viejas (destino esperado) y nuevas (ninguna capturada) | `python3 probar-301.py mapa.conf viejas.tsv nuevas.txt` | generalizado, probado en local con el mapa del caso |

## wp-cli/ — en el servidor, con `wp --path=<WP_PATH> --user=1 eval-file <archivo>`

Sube el archivo por `scp` y ejecútalo por el alias SSH; nunca PHP inline por SSH.

| Archivo | Qué hace | Escribe | Estado |
|---|---|---|---|
| `probe-perf.php` | inventario de Elementor (experimentos, rendimiento), Polylang/`PLL_COOKIE`, mu-plugins, optimizador de SiteGround, Yoast | no | generalizado, no probado fuera |
| `yoast-guard.php` | IndexNow y tracking de Yoast a false (primer paso en staging) | sí (2 opciones) | probado en HomeMaker |
| `yoast-config.plantilla.php` + `yoast-datos.ejemplo.json` | configuración completa de Yoast por `WPSEO_Options::set` + meta por página desde JSON; se niega a correr con marcadores sin rellenar | sí | generalizado, no probado fuera |
| `render.php` | pinta una URL del frontend como usuario 1 (detrás de la cortina) | no | probado en HomeMaker |
| `webp-convertir.php` | copias `.jpg.webp`/`.png.webp` de la Biblioteca (SiteGround o cwebp) | sí (archivos) | probado con SiteGround; rama sin SiteGround no probada |
| `atributos-por-id.php` | atributos HTML, `tag` o `menu_name` de elementos Elementor por ID de elemento; al final ejecuta `e4-sync-preview.php` (súbelo a la misma carpeta) | sí (documentos) | generalizado, no probado fuera |
| `listar-imagenes.php` | lista imágenes, encabezados y menús de documentos con sus atributos | no | generalizado, no probado fuera |

## tema-hijo/ — módulos para `wp-content/themes/<child>/inc/` y `mu-plugins/`

Prefijo `pfx`: sustitúyelo por `<PREFIJO>` (instrucción `sed` en cada cabecera). Los bloques con
constante de configuración se apagan dejándola vacía o `false`; los que no la tienen (en
`rendimiento.plantilla.php`: jQuery al pie, `sizes`/`id` de imagen y normalizador de `<img>`) van
siempre y se quitan borrando el bloque. Tras instalarlos: `php -l`, `wp elementor flush-css`, purga
y medición.

| Archivo | Destino | Estado |
|---|---|---|
| `mu-sin-cookie-idioma.php` | `mu-plugins/<PREFIJO>-cache.php` (`PLL_COOKIE=false`) | probado en HomeMaker, tal cual |
| `rendimiento.plantilla.php` | `inc/rendimiento.php`: fuentes + respaldo, envoltorio de transformadores V4, jQuery al pie, `sizes`/`id`/prioridad de imágenes, póster, revelado temprano, CSS de bloques, vídeo diferido, normalizador de `<img>` | generalizado, no probado fuera |
| `accesibilidad.plantilla.php` | `inc/accesibilidad.php`: foco del panel móvil de Pro, «Menu Toggle», `autocomplete` | generalizado, no probado fuera |
| `seo.plantilla.php` | `inc/seo.php`: x-default, sin SearchAction, Organization por idioma, `WebPage.name`, `og:locale`, imagen principal, migas del CPT, piezas schema | generalizado, no probado fuera |

## Qué no se incluyó (y por qué)

- El analizador estático de contraste del caso: dependía de las clases y colores de aquel
  proyecto. Usa la pasada de Lighthouse con `--force-prefers-reduced-motion` y mide en el navegador.
- La pieza schema concreta (Recipe) y los datos de Yoast del caso: son de la marca. Esqueleto de
  pieza en `../references/seo-yoast-polylang.md` §10; el ejemplo completo y saneado, en
  `../../wp-elementor-replica/ejemplos-homemaker/tema-hijo/homemaker-child/inc/` (`seo.php` y
  `schema-receta.php`; se instala con la skill `wp-elementor-replica`).
- La sincronización del orden de clases «preview» de Elementor: es de la skill
  `elementor4-atomico` (`../../elementor4-atomico/scripts/e4-sync-preview.php`).
  `atributos-por-id.php` la ejecuta al final desde su misma carpeta (u otra ruta con
  `SYNC_PREVIEW=<ruta>`).
