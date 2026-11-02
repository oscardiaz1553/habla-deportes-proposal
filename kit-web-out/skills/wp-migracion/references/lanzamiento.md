# Cortina y lanzamiento (F11)

Lanzar es una operación de minutos, pero se prepara durante toda la migración: qué
responde el dominio mientras tanto, en qué orden se abre, qué se comprueba y qué queda
para las primeras 48 horas. `blog_public`, Yoast, el mapa 301 y la caché suelen ser de
la sesión de SEO/rendimiento: esta sesión ejecuta en su orden y con su OK
(`../../wp-elementor-replica/references/sesiones-paralelas.md`).

---

## La cortina correcta en cada entorno

| Entorno | Cortina | Por qué |
|---|---|---|
| Staging o dominio sin historial | «Próximamente» de Elementor (`coming_soon`) + `blog_public=0` + IndexNow apagado | nadie lo tiene indexado; el noindex basta |
| Dominio real con historial, durante la migración | cortina **estática 503** en `.htaccess`, con llave de revisión por cookie | idéntica antes y después del cambio de carpeta; el cliente revisa sin iniciar sesión; se retira con un `sed` |
| Dominio real, si el lanzamiento se retrasa | **Mantenimiento** de Elementor (`maintenance`): 503 + `Retry-After: 600` | se enciende y apaga desde wp-admin; solo ve el sitio quien ha iniciado sesión |
| Nunca en un dominio con historial | «Próximamente» de Elementor | responde **200 + noindex en todas las URL, incluido `robots.txt`** (el HTML de la cortina con `text/html`): desindexa lo que el sitio anterior tenía posicionado |

- **Por qué 503:** significa «no disponible temporalmente» y los buscadores conservan lo
  indexado. Un 200 indexaría la cortina como si fuera el sitio; un 404 lo borraría de
  los resultados. Comprobado en `includes/maintenance-mode.php` (líneas 152-154 en
  Elementor 4.3): `maintenance` envía 503 + `Retry-After: 600`; en `coming_soon`, el
  `template_redirect` (prioridad 11) sustituye la consulta en todas las URL; los
  sitemaps de Yoast y `/wp-json/` siguen respondiendo.
- **Días, no semanas.** Con cualquiera de las dos cortinas 503 (estática o Mantenimiento)
  `robots.txt` responde 503, y Google trata un 5xx prolongado en `robots.txt` como «no
  rastrear». La propia Elementor desaconseja el 503 más de un par de días.
- **Opciones:** `elementor_maintenance_mode_mode` (`''` | `coming_soon` | `maintenance`),
  `elementor_maintenance_mode_template_id`, `elementor_maintenance_mode_exclude_mode`
  (`logged_in`), `elementor_maintenance_mode_exclude_roles`. El script que construye la
  plantilla solo fija la plantilla y `exclude_mode`; **encender la cortina es una
  decisión de negocio y de SEO** que no toma un script.
- **Plantilla:** tipo Página con lienzo Canvas (sin cabecera, pie ni `<main>`: se pone
  `role="main"` en la raíz), con su traducción enlazada. Con Polylang, el tema hijo
  filtra `option_elementor_maintenance_mode_template_id` con `pll_get_post()`: si no,
  `/<IDIOMA_2>/` sale en blanco (Elementor busca la plantilla con una consulta que
  Polylang filtra por idioma). Vista previa sin encender el modo mediante un parámetro
  propio (`/?<PREFIJO>-vista=proximamente`). Detalle en la skill `elementor4-atomico`
  (`../../elementor4-atomico/references/theme-builder.md`).
- **`Retry-After`:** la cortina estática del caso de referencia enviaba 3600 y la de
  Elementor 600; mientras coexisten, sale 3600. No es un problema, pero conviene saberlo
  al medir.
- **Con la cortina puesta, PageSpeed mide la cortina** (el SEO bajó a 58 por el noindex y
  el `robots.txt` en 503). Planifica la medición final antes de encenderla o justo
  después de lanzar, y pide a quien la gestiona que espere tu «listo».
- **Cambiar el modo no purga la caché de página:** al quitar la cortina, purga.

---

## 11a · Pasar a Mantenimiento de Elementor sin lanzar `[ESCRIBE en destino: opción y .htaccess]`

Primero se enciende la cortina de Elementor y **después** se quita la estática: el
sitio nunca queda abierto.
```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
wp82(){ /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/wp "$@"; }
P=<WP_PATH>
[ "$(wp82 --path="$P" config get table_prefix)" = "<TABLE_PREFIX>" ]
wp82 --path="$P" option update elementor_maintenance_mode_mode maintenance
echo "modo: $(wp82 --path="$P" option get elementor_maintenance_mode_mode) · excluye: $(wp82 --path="$P" option get elementor_maintenance_mode_exclude_mode)"
cp -p "$P/.htaccess" ~/<PREFIJO>-respaldos/htaccess.con-cortina-$(date +%Y%m%d-%H%M)
sed -i '/^# BEGIN <PREFIJO>-CORTINA/,/^# END <PREFIJO>-CORTINA/d' "$P/.htaccess"
grep -c '<PREFIJO>-CORTINA' "$P/.htaccess" || true
grep -nE '^# (BEGIN|END)|cPanel-generated' "$P/.htaccess"
EOF
```
**Lo esperado:** `0` restos del bloque; los demás bloques intactos, incluido el del PHP
de cPanel; `/` y `/<IDIOMA_2>/` → 503 con `Retry-After: 600` y la plantilla de cada
idioma; `wp-cron.php` → 200; `/<LOGIN_SLUG>` → 302; `.well-known` → 200. La llave de
revisión deja de servir. Fue el estado final del caso de referencia a la espera del
correo y del OK de SEO.

---

## Antes de lanzar: puerta D

La lista completa está en `runbook.md` §2. Lo que más se olvida:
- **Correo resuelto y probado** (`correo-dns.md`), o aceptación por escrito de lanzar con
  los envíos guardándose solo en Elementor › Envíos.
- **Mapa 301 definitivo** de SEO cargado (no el provisional) y su visto bueno a
  `blog_public=1`.
- **Decisión sobre plugins** tomada (D5).
- **Respaldo del sitio anterior** en `~/<PREFIJO>-respaldos/` y `ULTIMO-CAMBIO` escrito.
- La **medición** de rendimiento acordada con la sesión de SEO (cuándo y con qué caché).

---

## 11b · Lanzamiento `[ESCRIBE en destino: opciones y .htaccess]` · 10 min

**En el caso de referencia, secuencia planificada y no ejecutada:** el sitio quedó en 11a
(Mantenimiento de Elementor) a la espera del DNS del correo y del OK de SEO. Los comandos
salen del runbook que se preparó; lo comprobado en el servidor llega hasta 11a.

**Orden: primero `blog_public=1`, después retirar la cortina.** Al revés hay un
intervalo con el sitio visible y en noindex.
```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
wp82(){ /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/wp "$@"; }
P=<WP_PATH>; WP="wp82 --path=$P --url=https://<DOMINIO>"
[ "$($WP config get table_prefix)" = "<TABLE_PREFIX>" ]
cp -p "$P/.htaccess" ~/<PREFIJO>-respaldos/htaccess.antes-del-lanzamiento-$(date +%Y%m%d-%H%M)
# Si el mapa 301 definitivo llegó después de F7: sustituir aquí su bloque (misma técnica) y repetir F10-D.
$WP option update blog_public 1
$WP option update elementor_maintenance_mode_mode ''
if grep -q '^# BEGIN <PREFIJO>-CORTINA' "$P/.htaccess"; then sed -i '/^# BEGIN <PREFIJO>-CORTINA/,/^# END <PREFIJO>-CORTINA/d' "$P/.htaccess"; fi
$WP eval 'WPSEO_Options::set( "enable_index_now", true ); echo "index_now=", var_export( WPSEO_Options::get( "enable_index_now" ), true ), "\n";'
$WP yoast index --reindex --skip-confirmation
$WP elementor flush_css
$WP transient delete --all
$WP cache flush
echo "cortina: $(grep -c '<PREFIJO>-CORTINA' "$P/.htaccess" || true) · blog_public=$($WP option get blog_public) · modo='$($WP option get elementor_maintenance_mode_mode)'"
EOF
```
Si ya hay caché de página, púrgala al final (la de la cortina no debe quedar guardada).

### Comprobación pública, sin cookie

`../scripts/comprobar-http.sh lanzado` o a mano:
```bash
for u in / /<IDIOMA_2>/ /<pagina>/ /<IDIOMA_2>/<pagina>/; do curl -sS -o /dev/null -w "%{http_code}  $u\n" "https://<DOMINIO>$u"; done   # 200
curl -sS https://<DOMINIO>/robots.txt                     # 200, sin la cortina, sin «Disallow: /», con «Sitemap: https://<DOMINIO>/sitemap_index.xml»
curl -sS -o /dev/null -w '%{http_code} %{content_type}\n' https://<DOMINIO>/sitemap_index.xml   # 200 XML
curl -sS https://<DOMINIO>/ | grep -oiE '<meta name=.robots.[^>]+>'                          # index, follow
curl -sS https://<DOMINIO>/ | grep -oE '<link rel="(canonical|alternate)"[^>]+>'             # canonical + hreflang <IDIOMA_BASE>/<IDIOMA_2>/x-default
curl -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' http://www.<DOMINIO>/<pagina>/      # 301 → https://<DOMINIO>/<pagina>/
curl -sS -o /dev/null -w '%{http_code} %{redirect_url}\n' https://<DOMINIO>/<url-vieja>/      # 301 o 410 según el mapa
```
- El **canonical** no aparece mientras hay noindex (Yoast lo omite en
  `canonical-presenter.php`): por eso se comprueba después de lanzar.
- `sitemap_index.xml` solo debe listar los sitemaps de páginas y CPT públicos (las
  taxonomías auxiliares y los tipos de plugins, en noindex): skill `wp-seo-rendimiento`.

---

## Yoast: reindexar dos veces

1. **Tras la migración** (runbook F9), ya con la ruta final: los indexables guardan
   rutas absolutas y URL calculadas en el momento (`permalink_hash` depende de la URL).
2. **Otra vez tras `blog_public=1`:** `is_robots_noindex` vive en el indexable; sin
   reindexar, el noindex puede seguir en la salida.

`wp yoast index --reindex --skip-confirmation` no depende de los contadores de la
interfaz (en el caso de referencia, un Yoast modificado los forzaba a cero). Según la
sesión de SEO, `wp yoast index` solo corre con entorno `production`: no definas
`WP_ENVIRONMENT_TYPE` como `staging` en un sitio que necesite reindexar por CLI. Tras
cada cambio de Yoast, purga: con caché de página, también `robots.txt` queda cacheado.
Configuración de Yoast y Polylang: `../../wp-seo-rendimiento/references/seo-yoast-polylang.md`.

---

## IndexNow y `blog_public`: solo en producción

- **El IndexNow de Yoast Premium ignora `blog_public`:** solo comprueba
  `enable_index_now` y que `wp_get_environment_type()` sea `production` (sin
  `WP_ENVIRONMENT_TYPE` definido, el entorno es `production`). En el staging del caso de
  referencia, cada publicación avisaba a los buscadores con URL de staging.
  - En staging, nada más instalar Yoast Premium:
    `WPSEO_Options::set( 'enable_index_now', false )` (y `tracking` a false); script:
    `../../wp-seo-rendimiento/scripts/wp-cli/yoast-guard.php`.
  - En producción, al lanzar: `WPSEO_Options::set( 'enable_index_now', true )`.
  - Comprobación: `wp option get wpseo --format=json` → `enable_index_now`.
- **`blog_public`:** 0 en staging siempre; 1 solo en producción y en el paso de
  lanzamiento. En el panel: Ajustes › Lectura › «Pedir a los motores de búsqueda que no
  indexen este sitio». Es de la sesión de SEO: se cambia con su OK.

---

## Search Console

- Alta de la propiedad verificando por **DNS TXT** en el panel que sea autoritativo (la
  trampa del Zone Editor de `correo-dns.md` también aplica aquí); comprobación con
  `dig +short TXT <DOMINIO> @<NS_AUTORITATIVO>`. En el caso de referencia no había
  archivos de verificación (`google*.html`, `BingSiteAuth.xml`).
- Enviar `https://<DOMINIO>/sitemap_index.xml`.
- En los días siguientes, las 404 que aparezcan se añaden al bloque 301 (lo mantiene SEO).

---

## Volver a medir (PSI) y caché de página

- **Medir otra vez tras lanzar y tras poner la caché:** las cifras de staging se tomaron
  con la infraestructura del hosting de origen. Caso de referencia: 89–99 en móvil con la
  caché dinámica de SiteGround (Lighthouse local, mediana de 3; la línea base previa fue de
  una sola corrida); en Bluehost, antes de lanzar y sin caché de página, **una sola corrida**
  de Lighthouse local detrás de la cortina: portada 93/99 y productos 98/99
  (móvil/escritorio), CLS 0, TTFB 0,7–1,1 s y LCP móvil de 3,0 s (verde, con poco margen;
  sin PSI de Google ni medición con caché en producción). Método para la medición de
  verdad (Lighthouse local de la misma versión, mediana de 3, PSI por Puppeteer):
  `../../wp-seo-rendimiento/references/medicion.md`.
- **Caché de página (D11), decisión del usuario:** la sesión de SEO/rendimiento recomienda
  instalarla **antes o justo al lanzar** (WP Super Cache en modo mod_rewrite/Expert, que
  sirve el HTML desde Apache sin PHP). En el caso de referencia quedó como decisión
  pendiente del usuario y no se llegó a instalar ni a medir. Preséntala así:
  - Apache sin LiteSpeed: WP Super Cache (modo mod_rewrite/Expert), Cache Enabler o la
    caché del plugin del hosting. LiteSpeed Cache solo sirve con servidor LiteSpeed.
  - Reglas: sin usuarios con sesión, sin URL con query (filtros), solo respuestas 200
    (**nunca guardar un 503**), purga al publicar y al quitar el mantenimiento.
  - **`PLL_COOKIE=false` en un mu-plugin tiene que haber viajado:** Polylang envía
    `set-cookie: pll_language` y ninguna caché guarda respuestas con `Set-Cookie`.
    Comprobación: `curl -sI https://<DOMINIO>/` sin `set-cookie`. El mu-plugin:
    `../../wp-seo-rendimiento/scripts/tema-hijo/mu-sin-cookie-idioma.php`; el porqué, en la
    skill `wp-polylang`.
  - Mientras no haya caché de página, «purgar» es `wp cache flush` + `wp elementor
    flush_css` (`wp sg purge` solo existe en SiteGround).
- **WebP de las subidas nuevas:** sin el optimizador del hosting de origen no se generan;
  decide un conversor con la sesión de rendimiento.

---

## Primeras 48 horas

- `[lectura]` `error_log` del docroot (si existe) y Salud del sitio.
- `[lectura]` 404 de Search Console → al bloque 301 (SEO).
- `[lectura]` Renovación del SSL: revisa `notAfter` cuando toque (AutoSSL renueva unos 30
  días antes por `/.well-known/`).
- `[ESCRIBE, cambios aparte y con copia previa]` licencias oficiales (D5) y retirada de
  restos de temas o plugins (D6): antes, `wp db export` a `~/<PREFIJO>-respaldos/` y un tar
  de `wp-content/plugins`; después, comprobación visual. Tras instalar licencias
  oficiales, las opciones de licencia falsas se borran con permiso explícito del usuario.
- `[ESCRIBE]` Sustituir el `favicon.ico` que dejó el sitio anterior por el icono del
  sitio en WP.
- **Cabeceras de seguridad** (HSTS, `X-Frame-Options: SAMEORIGIN`, COOP) solo después de
  lanzar y probando el editor y el login. **Nunca** `Referrer-Policy: origin` ni
  `strict-origin` con el login propio de SG Security: rechaza al administrador si el
  `Referer` no lleva `sgs-token`. Avisa a quien toque cabeceras.
- **Documento de entrega:** lo que da por hecho (plugins retirados, copia de seguridad al
  cierre, URL de acceso, estado de la cortina) tiene que ser cierto en el servidor o salir
  como supuesto a confirmar. Plantilla del kit:
  `../../wp-elementor-replica/plantillas/documento-entrega.md`.
- **Memoria del proyecto:** estado final (producción, cortina, `blog_public`, pendientes
  del usuario como el DNS) y quién es dueño de qué.
- **Staging:** sigue con noindex y su cortina hasta que el usuario decida retirarlo o
  protegerlo (D12). Avisa a las sesiones paralelas de que ya no es la fuente.
