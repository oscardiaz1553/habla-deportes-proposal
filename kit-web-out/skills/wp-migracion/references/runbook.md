# Runbook de migración a producción (F0–F13)

Plantilla para pasar un WordPress + Elementor 4 de staging a producción en
cualquier hosting con SSH. Cópiala al scratchpad del proyecto, rellena los
marcadores (tabla en `../SKILL.md`), preséntala al usuario con las decisiones
del §1 y **no ejecutes nada hasta cerrar las decisiones que bloquean cada paso**.

Caso de referencia (HomeMaker): staging en SiteGround (MySQL 8.4) → Bluehost
cPanel (Apache 2.4 + mod_lsapi, MySQL 5.7.44, WP-CLI 2.9), dominio adicional con
docroot dentro del `public_html` de otro sitio vivo; WP 7.1.2, Elementor 4.3 (V4),
Elementor Pro 4.2, Polylang Pro 3.8, Yoast 27.7, Hello + child. El público vio el
mismo 503 antes, durante y después del cambio; lanzar es retirar la cortina.

Piezas relacionadas: `auditoria-hosting.md` (F0), `correo-dns.md` (D7),
`lanzamiento.md` (F11), `rollback.md` (F12) y los scripts de `../scripts/`.

---

## Convenciones

- **Marcas:** `[lectura]` no cambia nada · `[ESCRIBE en X]` cambia algo en X ·
  `[MANUAL usuario]` lo hace el usuario en el navegador o el panel.
- **Cada paso `[ESCRIBE]` necesita el OK explícito del usuario en el chat.** Los de
  lectura se hacen sin preguntar. En Claude Code, `ssh`, `scp` y `curl` remotos
  necesitan red (`dangerouslyDisableSandbox`).
- **Cada bloque es autónomo:** `ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF' … EOF`, con
  `set -euo pipefail` y sus propias variables. El heredoc va entre comillas para
  que el shell local no toque `$` ni comillas.
- **PHP de más de una línea, por archivo** (`scp` + `wp eval-file … --user=1`). Un
  `wp eval` corto funciona dentro de un heredoc entre comillas; fuera de él, las
  comillas se rompen al pasar por el shell local, SSH y PHP.
- **WP-CLI con el mismo PHP que la web.** En cPanel la CLI por defecto puede ser
  otra versión (en el caso de referencia, 8.3 frente a 8.2 de la web):
  `wp82(){ /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/wp "$@"; }`.
  Localiza los binarios con `ls /opt/cpanel/ea-php*/root/usr/bin/php`.
- **Nunca `wp` sin `--path`.** WP-CLI sube carpetas buscando `wp-load.php` o
  `wp-cli.yml`: en una carpeta sin WordPress dentro del `public_html` de otro sitio,
  actuaría sobre el sitio ajeno. Cada bloque que escribe empieza con la guarda
  `[ "$(wp --path="$W" config get table_prefix)" = "<TABLE_PREFIX>" ]` (y `DB_NAME`
  cuando aplique). `wp config get` y `wp db query` no cargan WordPress: leen sin
  ejecutar plugins.
- **Escrituras en la BD de la copia con `--skip-plugins --skip-themes`:** ningún
  plugin escribe durante el reemplazo (en el caso de referencia, un Elementor Pro
  modificado hacía dos `update_option` en cada carga).
- **Secretos:** nunca `set -x`, `echo "$pw"` ni imprimir la llave de la cortina. La
  cortina se copia de servidor a servidor y se compara con `cmp`.
- **Borrados:** ningún paso borra datos del cliente ni el sitio anterior. Solo se
  eliminan cosas en **copias recién creadas** (opciones y crons de plugins que no
  viajan). La limpieza final es del usuario (F13).
- **macOS:** no existe `timeout` (usa `ssh -o BatchMode=yes -o ConnectTimeout=15`);
  zsh expande `=palabra`, `?` y `*` (entrecomilla separadores, URLs con query y
  patrones). En el Mac `shasum -a 256`, `md5 -q`, `sed -i ''`; en Linux `sha256sum`,
  `md5sum`, `sed -i`.

| Variable | Qué es |
|---|---|
| `L` | carpeta local del Mac: `$HOME/<PREFIJO>-migracion-local/<TS>`, `chmod 700`, **fuera de Documentos/iCloud** (lleva hashes de contraseñas, correos y envíos) |
| `B` | `~/<PREFIJO>-migracion` en destino, `chmod 700`: volcados, mapa 301 y scripts |
| `W` | obra: `~/<PREFIJO>-wp-nuevo`, **fuera de `public_html`** y en el mismo disco que el docroot |
| `D` / `P` | docroot final = `<WP_PATH>` |
| `R` | respaldos: `~/<PREFIJO>-respaldos`, `chmod 700` |
| `TS` | marca de tiempo `date +%Y%m%d-%H%M` |

En el Mac, cada bloque recupera la carpeta local con
`L="$(ls -1d "$HOME"/<PREFIJO>-migracion-local/2*/ | tail -1)"; L="${L%/}"`.

---

## §0 · En una pantalla (rellenar)

| | |
|---|---|
| **Origen** | `<SSH_ALIAS_ORIGEN>` · `<WP_PATH_ORIGEN>` · versión de WP · motor y versión de MySQL · `<TABLE_PREFIX>` · tamaños de BD y `wp-content` |
| **Destino** | `<SSH_ALIAS_DESTINO>` · docroot `<WP_PATH>` · PHP web · MySQL · versión de WP-CLI |
| **Intocable** | todo lo que no sea `<WP_PATH>`, la obra y las carpetas `<PREFIJO>-*`: el sitio vecino, su BD, su `.htaccess`, su staging |
| **Origen durante el runbook** | solo lectura: la BD y `wp-content` salen por la salida estándar de SSH |
| **Método** | obra en `W` con BD nueva; cambio con dos `mv` en el mismo disco detrás de la cortina 503 |
| **Sitio anterior** | pasa entero a `R/<docroot>.prototipo-<TS>` (o `.anterior-<TS>`) con sus permisos. **No se borra nunca** |
| **Caída para el público** | objetivo 0 min |
| **Ventana** | F1–F10: 1 h 30 – 2 h. Lanzamiento: 20 min |
| **Bloquean el lanzamiento, no el cambio** | correo (D7), OK de SEO (D1/D2), decisión sobre plugins (D5) |

---

## §1 · Decisiones del dueño antes de empezar

Cada una con opciones, recomendación y qué cambia en el runbook. Se presentan
juntas (AskUserQuestion, la recomendada primero). Por defecto se sigue la
recomendada.

| # | Decisión | Recomendación | Qué cambia |
|---|---|---|---|
| D1 | Cortina en el dominio real | Estática 503 con llave durante el cambio y la verificación; si el lanzamiento se retrasa, Mantenimiento de Elementor (503). **Nunca** «Próximamente» (200 + noindex) | F7, F11. Ver `lanzamiento.md` |
| D2 | Mapa 301/410 del sitio anterior | El de la sesión SEO, sacado de la BD del sitio viejo (no del prototipo), como archivo con solo `RewriteCond`/`RewriteRule`, probado antes con `../../wp-seo-rendimiento/scripts/medicion/probar-301.py` contra las URL viejas y todas las nuevas. Si no llega a tiempo, uno provisional y se sustituye antes de lanzar | F2, F7, lanzamiento |
| D3 | Plugins propios del hosting de origen (optimizador, conector de IA) | No migrarlos | exclusiones del tar (F1), F6 |
| D4 | Plugin de seguridad del origen y la URL de login propia | Mantenerlo si su código funciona fuera de ese hosting (revísalo); si no viaja, decidir antes la entrada al admin y corregir el documento de entrega | F6, F10 |
| D5 | Plugins premium de procedencia dudosa (*nulled*) | Licencias oficiales y ZIP limpios de la misma versión, **como cambio aparte** después de migrar; mientras tanto, nadie usa «Insertar plantilla» de la biblioteca de Elementor | Puerta D. En el caso de referencia el usuario eligió seguir con los actuales |
| D6 | Restos de temas o plugins anteriores | No tocarlos durante la migración; retirarlos después, con copia y comprobación visual | `lanzamiento.md` (primeras 48 h) |
| D7 | Correo desde el nuevo servidor | DNS: SPF + DKIM + enrutamiento remoto, sin guardar credenciales. Alternativas: SMTP (la clave la pone el usuario en el servidor) o proveedor transaccional | `correo-dns.md`. Bloquea el lanzamiento |
| D8 | Con o sin `www` | La variante que usaba el sitio anterior (compruébalo en su BD: `siteurl`/`home`) | bloque canónico (F7) |
| D9 | Versión de PHP y límites | Fijar la misma rama que el origen para el dominio; `.user.ini` con `max_input_vars = 3000`; subir de versión después, aparte | F7, F9 |
| D10 | Copias WebP del optimizador de origen | Migrarlas y servirlas por `Accept` desde `.htaccess`; las subidas nuevas no tendrán WebP sin conversor (decisión de rendimiento) | F1, F7 |
| D11 | Caché de página en destino | La de la sesión de SEO/rendimiento: WP Super Cache en modo mod_rewrite (Expert) o la caché del plugin del hosting, instalada **antes o justo al lanzar**, sin sesiones, sin URL con query, solo 200 (nunca el 503) y purga al publicar. Lo decide el usuario; en el caso de referencia quedó como decisión pendiente y producción quedó sin caché de página | `lanzamiento.md` |
| D12 | Qué pasa con el staging | Congelado N días (30 en el caso de referencia) con noindex y su cortina; desde la exportación se edita solo en destino | F1, F13 |
| D13 | Copia local en el Mac | Fuera de Documentos/iCloud, 700, con fecha de borrado (30 días) que decide el usuario | F1, F13 |
| D14 | Ventana y congelación | 2–3 h acordadas; congelación anunciada al cliente y a las sesiones paralelas; confirmar administradores desconocidos | F0, F1 |
| D15 | Tareas programadas | Paso libre a `wp-cron.php` en la cortina; cron del sistema opcional después | F7, F9 |
| D16 | Qué se conserva | El sitio anterior respaldado (nunca se borra), su BD antigua (decisión aparte), el staging según D12 | F13 |

---

## §2 · Puertas GO / NO-GO

**Puerta A · antes de empezar (al terminar F0)**
- [ ] D1–D16 respondidas; para D7 hay responsable y fecha.
- [ ] SEO confirmó D1 y entregó D2 (o aceptó por escrito el mapa provisional).
- [ ] Congelación anunciada; 0 archivos cambiados en 30 min y última edición anterior a la congelación.
- [ ] Estado de partida guardado: códigos del dominio, caducidad del certificado, `dns-antes.txt`.
- [ ] Carpetas `B`, `W`, `R` y `<DB_NAME>` no existen; mismo disco; cuota libre suficiente.
- [ ] El usuario está disponible en la ventana (entrada al admin y pruebas manuales de F10-I).

**Puerta B · antes del cambio (fin de F7)**
- [ ] F2: `sha256sum -c` da OK en todos los archivos.
- [ ] F3: checksums del núcleo OK; 0 carpetas sin `o+x` y 0 archivos sin `o+r`.
- [ ] F4: «Conexión BD: OK» y `wp-config.php` en 600.
- [ ] F5: nº de tablas = nº de `CREATE TABLE`; 0 restos del staging y de la ruta de origen; `home`/`siteurl` correctos.
- [ ] F6: lista de plugins activos esperada; `blog_public=0`; modo de Elementor según D1.
- [ ] F7: «cortina idéntica», 1 regla `wp-cron`, 0 marcadores sin rellenar, pares BEGIN/END completos, `.user.ini`.
- [ ] Comprobación en la obra: `wp eval` carga con plugins sin *fatal*; portada, kit y permisos esperados (`../scripts/estado-sitio.php`).

**Puerta C · nada más cambiar las carpetas (F8)**
- [ ] `/` da 503 (cortina), la página de la cortina 200, `/wp-login.php` 503 y el reto ACME 200.
- **NO-GO → vuelta atrás R1 de inmediato** (`rollback.md`).

**Puerta D · lanzamiento (F11)**
- [ ] F9 sin errores; 0 restos del staging, de la obra y de la ruta de origen; indexables > 0; `cron test` OK.
- [ ] PHP fijado y el sitio sigue dando 503.
- [ ] F10 A–J como se espera; clases V4 frontend = preview.
- [ ] D7 resuelto y probado solo al buzón del usuario con `spf=pass dkim=pass dmarc=pass`, o aceptación por escrito de lanzar con los envíos guardándose solo en Elementor › Envíos.
- [ ] SEO: mapa 301 definitivo cargado y visto bueno a `blog_public=1`; acceso a Search Console listo.
- [ ] D5 decidido. Respaldo del sitio anterior en `R` y `ULTIMO-CAMBIO` escrito.

---

## §3 · Fases

### F0 · Auditorías, decisiones y estado de partida `[lectura]` · 30–60 min

**0a · Auditorías de origen y destino.** Qué mirar y cómo, sin imprimir secretos:
`auditoria-hosting.md`. Lo que funcionó: un workflow con tres agentes de solo
lectura en paralelo (destino, origen, procedimiento) y un cuarto que consolida el
runbook, mientras el orquestador comprueba en persona los hechos clave
(administradores, código de los plugins premium). Revisa la salida de cada agente
antes de reenviarla.

**0b · Estado de partida (Mac y destino).**
```bash
TS=$(date +%Y%m%d-%H%M); L="$HOME/<PREFIJO>-migracion-local/$TS"; mkdir -p "$L" && chmod 700 "$L"; echo "$L"
curl -sS -o /dev/null -w '%{http_code}  /\n' https://<DOMINIO>/
echo | openssl s_client -connect <DOMINIO>:443 -servername <DOMINIO> 2>/dev/null | openssl x509 -noout -enddate
NS=$(dig +short NS <DOMINIO> | head -1)
{ dig +short @"$NS" <DOMINIO> TXT; dig +short @"$NS" _dmarc.<DOMINIO> TXT
  dig +short @"$NS" default._domainkey.<DOMINIO> TXT; dig +short @"$NS" <DOMINIO> MX
  dig +short @"$NS" <DOMINIO> SOA; } > "$L/dns-antes.txt"; wc -l "$L/dns-antes.txt"
```
```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
D=<WP_PATH>
stat -c '%U:%G %a %n' "$D"; id
for d in ~/<PREFIJO>-migracion ~/<PREFIJO>-wp-nuevo ~/<PREFIJO>-respaldos; do [ -e "$d" ] && echo "EXISTE $d -> NO-GO" || echo "libre: $d"; done
echo "BD <DB_NAME>: $(uapi --output=json Mysql list_databases | grep -c '"database":"<DB_NAME>"')"
[ "$(stat -c %d ~)" = "$(stat -c %d "$D")" ] && echo "mismo disco: OK" || echo "DISCO DISTINTO -> NO-GO"
uapi Quota get_quota_info | grep -iE 'megabyte'
ls -A "$D/.well-known" 2>/dev/null
EOF
```
Los `uapi` son de cPanel; en otro panel, sus equivalentes o `[MANUAL usuario]`.

**Lo esperado:** la cortina o el sitio actual con su código; `notAfter` anotado (la
renovación AutoSSL pasa por `/.well-known/` unos 30 días antes); `dns-antes.txt`
con 5 líneas o más (sirve para volver atrás el correo); carpetas libres; mismo
disco; cuota holgada.

**Por qué el mismo disco:** `mv` dentro del mismo sistema de archivos es un
`rename` atómico e instantáneo. Entre discos es una copia: el cambio deja de ser
instantáneo. Si es otro disco, NO-GO y se replantea.

**Permisos del docroot:** cPanel suele dejarlo `usuario:nobody 750`. Una carpeta
nueva queda `usuario:usuario`; con 750 Apache (que lee como `nobody`) daría 403,
con 755 se sirve. En el caso de referencia la auditoría temía el 403 («cambiar el
contenido, no la carpeta») y se comprobó que una carpeta 755 del usuario se servía
(`.well-known` y otras ya lo hacían). Compruébalo en cada hosting antes de elegir.

**0c · Administradores y plugins premium** `[lectura en origen]`:
`wp --path=<WP_PATH_ORIGEN> user list --role=administrator --fields=ID,user_login,user_registered`.
Una cuenta que no creaste se confirma con el usuario y no se toca (puede ser de
otra sesión o del propio usuario). `wp plugin verify-checksums --all` (solo cubre
plugins de wordpress.org) y las primeras 40 líneas del archivo principal de cada
premium buscando `pre_http_request`, `update_option(.*licen` o dominios ajenos → D5.

**Comprobación:** puerta A.

---

### F1 · Congelar y exportar `[lectura en origen; ESCRIBE solo en el Mac]` · 10–15 min

**1a · Congelación.** Avisa a las sesiones paralelas y al cliente: «FREEZE STARTS
NOW», motivo y duración. Comprueba con datos:
```bash
ssh <SSH_ALIAS_ORIGEN> 'bash -s' <<'EOF'
R=<WP_PATH_ORIGEN>
P=$(wp --path="$R" config get table_prefix); echo "prefijo: $P"
echo "última edición (GMT): $(wp --path="$R" db query "SELECT MAX(post_modified_gmt) FROM ${P}posts" --skip-column-names)"
echo "archivos cambiados en 30 min: $(find "$R/wp-content/themes/<CHILD>" "$R/wp-content/mu-plugins" "$R/wp-content/uploads" -type f -newermt '-30 minutes' | wc -l)"
wp --path="$R" db query "SELECT option_name, option_value FROM ${P}options WHERE option_name IN ('elementor_maintenance_mode_mode','blog_public')" --skip-column-names
date -u
EOF
```
**Lo esperado:** 0 archivos y la última edición anterior a la hora de congelación.
Si no, se espera y se repite: un cambio entre la exportación y el cambio se pierde.

**1b · Exportar por la salida estándar** (el origen no escribe nada; además, entre
hostings no suele haber SSH en los dos sentidos). Script: `../scripts/exportar-origen.sh`.
```bash
RS='<WP_PATH_ORIGEN>'   # comillas simples si lleva $HOME: se expande en el origen
ssh <SSH_ALIAS_ORIGEN> "wp --path=$RS db export - --single-transaction --set-gtid-purged=OFF --default-character-set=utf8mb4 --exclude_tables=<tablas que no viajan>" > "$L/db.sql"
ssh <SSH_ALIAS_ORIGEN> "tar -C $RS/wp-content -czf - --exclude=./upgrade --exclude=./upgrade-temp-backup --exclude=./cache --exclude='*.log' --exclude=php_errorlog --exclude=error_log <exclusiones del proveedor> --warning=no-file-changed ." > "$L/wpcontent.tgz"; echo "tar rc=$?"
ssh <SSH_ALIAS_ORIGEN> "cat $RS/wp-includes/.htaccess" > "$L/wp-includes.htaccess"   # solo si existe (endurecimiento del plugin de seguridad)
```
- **Exclusiones del proveedor en el caso de referencia (SiteGround):**
  `--exclude=./sgs_encrypt_key.php --exclude=./uploads/siteground-optimizer-assets
  --exclude=./plugins/sg-ai-studio --exclude=./plugins/sg-cachepress`, y la tabla de
  SG AI Studio fuera del volcado. D10-b (sin WebP del optimizador): `--exclude='*.jpg.webp'
  --exclude='*.jpeg.webp' --exclude='*.png.webp'`.
- **Por qué `--warning=no-file-changed` y aceptar `rc ≤ 1`:** `tar` sale con 1 cuando
  un archivo cambia mientras lo lee (caché, cron). Con `set -e` eso abortó la
  exportación del caso de referencia. Solo se acepta con las comprobaciones de 1c.
- **`--set-gtid-purged=OFF`** es una opción de `mysqldump` de MySQL (el origen era
  8.4). Si el `mysqldump` del origen no la conoce (`mysqldump --help | grep -c gtid`
  da 0), quítala.
- `wp db export --no-data` falla (WP-CLI lo convierte en `--data=`). Para sacar solo
  el esquema: `--where=0`.

**1c · Integridad** `[lectura]`:
```bash
head -1 "$L/db.sql"                       # -- MySQL dump … (si no: un aviso de PHP se coló en la salida; repetir)
tail -1 "$L/db.sql"                       # -- Dump completed on …
grep -c '^CREATE TABLE' "$L/db.sql"       # anótalo: F5 lo compara
grep -c 'utf8mb4_0900_ai_ci' "$L/db.sql"  # si > 0 y el destino es MySQL 5.7, F5 lo corrige
gzip -t "$L/wpcontent.tgz" && echo "tgz íntegro"; tar -tzf "$L/wpcontent.tgz" | wc -l
for f in themes/<CHILD>/functions.php mu-plugins/<MU_PLUGIN>.php plugins/<PLUGIN_CLAVE>/<ARCHIVO>.php uploads/<AAAA>/<MM>/<ARCHIVO>; do
  tar -tzf "$L/wpcontent.tgz" "./$f" >/dev/null 2>&1 && echo "ok $f" || echo "FALTA $f"; done
tar -tzf "$L/wpcontent.tgz" | grep -cE '^\./plugins/(<plugins que no viajan>)/'   # 0
( cd "$L" && shasum -a 256 db.sql wpcontent.tgz wp-includes.htaccess > SHA256SUMS )
```
**1d · Descongelar:** «UNFROZEN» a las sesiones paralelas, avisando de que **desde
ahora la copia principal es el destino** y lo que se toque en staging no viaja
(si alguien lo toca, que avise para portarlo). Pide que no se toque nada hasta
verificar.

---

### F2 · Traslado con SHA-256 `[ESCRIBE en destino: B]` · 2–3 min

```bash
ssh <SSH_ALIAS_DESTINO> 'mkdir -p ~/<PREFIJO>-migracion && chmod 700 ~/<PREFIJO>-migracion'
scp -p "$L/db.sql" "$L/wpcontent.tgz" "$L/wp-includes.htaccess" "$L/SHA256SUMS" <SSH_ALIAS_DESTINO>:<PREFIJO>-migracion/
ssh <SSH_ALIAS_DESTINO> 'cd ~/<PREFIJO>-migracion && sha256sum -c SHA256SUMS'
```
Sube también el mapa 301 de SEO (`<PREFIJO>-301.conf`), los scripts de `../scripts/`
que se ejecutan en el servidor y la sonda de formularios de `elementor4-atomico`. **Lo esperado:** una línea `OK` por
archivo. `scp` puede cortarse a medias («Connection closed») sin detener el flujo:
sin checksum no hay nada subido.

---

### F3 · Obra: núcleo limpio, `wp-content`, `.well-known` y cortina `[ESCRIBE en destino: W]` · 3 min

```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
wp82(){ /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/wp "$@"; }
W=~/<PREFIJO>-wp-nuevo; B=~/<PREFIJO>-migracion; D=<WP_PATH>
[ ! -e "$W" ] || { echo "$W ya existe: NO-GO"; exit 1; }
mkdir -p "$W" && chmod 755 "$W"
wp82 --path="$W" core download --version=<X.Y.Z> --locale=en_US --skip-content
wp82 --path="$W" core verify-checksums --version=<X.Y.Z> --locale=en_US
mkdir -p "$W/wp-content"; tar -xzf "$B/wpcontent.tgz" -C "$W/wp-content"
[ -s "$B/wp-includes.htaccess" ] && cp "$B/wp-includes.htaccess" "$W/wp-includes/.htaccess"
cp -a "$D/.well-known" "$W/"                        # AutoSSL y retos del CDN del hosting
cp -a "$D/<PAGINA_CORTINA>.html" "$W/"               # D1: la cortina y cada recurso que dibuja, en las mismas rutas
# mkdir -p "$W/<ruta>"; cp -a "$D/<ruta>/<recurso>" "$W/<ruta>/"   (logo, póster, vídeo, favicon…)
echo "carpetas sin o+x: $(find "$W" -type d ! -perm -o=x | wc -l) · archivos sin o+r: $(find "$W" -type f ! -perm -o=r | wc -l)"
du -sh "$W"; ls -A "$W"
EOF
```
**Lo esperado:** `Success: WordPress downloaded.` y `…verifies against checksums.`;
`0 · 0` en permisos (si no, `chmod o+rX` sobre esa ruta de la obra). Las
traducciones viajan en `wp-content/languages`: el idioma del núcleo descargado da
igual. Nunca montes la obra dentro de `public_html`: desde el vhost vecino se
vería como `https://<sitio-vecino>/<carpeta>/`.

---

### F4 · BD nueva y `wp-config.php` `[ESCRIBE en destino: MySQL y W/wp-config.php]` · 2 min

La contraseña se genera en el servidor, entra por STDIN y no se imprime nunca.
```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
wp82(){ /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/wp "$@"; }
W=~/<PREFIJO>-wp-nuevo; DB=<DB_NAME>; U=<DB_USER>
[ -d "$W/wp-includes" ] && [ ! -e "$W/wp-config.php" ] || { echo "Obra sin núcleo o con wp-config.php: NO-GO"; exit 1; }
if uapi --output=json Mysql list_databases | grep -q "\"database\":\"$DB\""; then echo "La BD $DB ya existe: NO-GO"; exit 1; fi
pw=$(openssl rand -base64 45 | tr -d '\n/+='); [ ${#pw} -ge 50 ]
uapi --output=json Mysql create_database name="$DB" | grep -q '"status":1'
uapi --output=json Mysql create_user name="$U" password="$pw" | grep -q '"status":1'
uapi --output=json Mysql set_privileges_on_database user="$U" database="$DB" privileges=ALL%20PRIVILEGES | grep -q '"status":1'
wp82 --path="$W" config create --dbname="$DB" --dbuser="$U" --dbhost=localhost --dbprefix=<TABLE_PREFIX> --dbcharset=utf8mb4 --prompt=dbpass <<<"$pw" >/dev/null 2>&1 || true
if ! wp82 --path="$W" db query 'SELECT 1' >/dev/null 2>&1; then
  [ -f "$W/wp-config.php" ] || wp82 --path="$W" config create --dbname="$DB" --dbuser="$U" --dbhost=localhost --dbprefix=<TABLE_PREFIX> --dbcharset=utf8mb4 --dbpass=placeholder --skip-check >/dev/null
  wp82 --path="$W" config set DB_PASSWORD "$pw" --quiet
fi
unset pw; chmod 600 "$W/wp-config.php"
wp82 --path="$W" config set WP_ENVIRONMENT_TYPE production --type=constant --quiet
wp82 --path="$W" config set WP_DEBUG false --raw --type=constant --quiet
wp82 --path="$W" config set WP_MEMORY_LIMIT 256M --type=constant --quiet
wp82 --path="$W" config set DISALLOW_FILE_EDIT true --raw --type=constant --quiet
wp82 --path="$W" config list --fields=name,type --format=csv | grep -viE 'password|key|salt'
wp82 --path="$W" db query 'SELECT 1' >/dev/null && echo 'Conexión BD: OK'
EOF
```
- **Por qué el respaldo del `--prompt`:** en WP-CLI 2.9 no quedó claro que
  `--prompt=dbpass` leyera de STDIN; el bloque comprueba la conexión y, si no la
  hay, fija la contraseña con `config set … --quiet` (queda unos milisegundos como
  argumento, visible solo para la propia cuenta).
- **Si la API de sales no responde:** `--skip-salts` y después `config shuffle-salts`.
- **Sales nuevas:** todos los administradores vuelven a iniciar sesión (por la URL de
  login propia, si la hay). Los hashes de contraseña no dependen de ellas.
- **cPanel:** los nombres de BD y usuario llevan el prefijo de la cuenta y el usuario
  tiene un máximo de caracteres (`uapi Mysql get_restrictions`).
- **Sin UAPI:** el usuario crea la BD y el usuario en su panel y escribe él la
  contraseña en `wp-config.php` `[MANUAL usuario]`. Nunca por chat.
- **No se copia el `wp-config.php` del origen:** trae includes del proveedor (en
  SiteGround, `@include_once('/var/lib/sec/…')`, que además definían
  `WP_MEMORY_LIMIT`; por eso se fija aquí). `DISALLOW_FILE_EDIT` no choca con SG
  Security (usa `map_meta_cap` y no define la constante; comprobado en su código).

---

### F5 · Importar y search-replace hasta 0 restos `[ESCRIBE en destino: BD nueva]` · 3–5 min

Script con guardas y modo en seco: `../scripts/search-replace.sh`. El núcleo, dentro de
un bloque `ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'` con `set -euo pipefail` y `wp82`:
```bash
W=~/<PREFIJO>-wp-nuevo; B=~/<PREFIJO>-migracion
[ "$(wp82 --path="$W" config get table_prefix)" = "<TABLE_PREFIX>" ]; [ "$(wp82 --path="$W" config get DB_NAME)" = "<DB_NAME>" ]
sed -E '/^INSERT INTO/!s/utf8mb4_0900_ai_ci/utf8mb4_unicode_520_ci/g' "$B/db.sql" > "$B/db-57.sql"   # solo esquema, nunca datos
if grep -q utf8mb4_0900_ai_ci "$B/db-57.sql"; then echo "Queda utf8mb4_0900_ai_ci: NO-GO"; exit 1; fi
WP="wp82 --path=$W --skip-plugins --skip-themes"
$WP db import "$B/db-57.sql"
$WP db query 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()' --skip-column-names
SR="$WP search-replace --all-tables-with-prefix --precise --report-changed-only"
esc(){ printf '%s' "$1" | sed 's#/#\\/#g'; }             # /a/b → \/a\/b
$SR '<RUTA_FISICA_ORIGEN>' '<WP_PATH>'                   # 1a · ruta absoluta → ruta FINAL (no la de la obra)
$SR "$(esc '<RUTA_FISICA_ORIGEN>')" "$(esc '<WP_PATH>')"   # 1b · la misma ruta dentro de JSON
$SR '<STAGING_DOMINIO>' '<DOMINIO>' --dry-run | tail -1   # 2 · dominio SIN esquema, primero en seco
$SR '<STAGING_DOMINIO>' '<DOMINIO>'
$SR 'http://<DOMINIO>'   'https://<DOMINIO>'              # 3 · https, forma normal
$SR 'http:\/\/<DOMINIO>' 'https:\/\/<DOMINIO>'            # 4 · https, forma escapada de Elementor
$WP db search '<STAGING_DOMINIO>' --all-tables-with-prefix --stats | tail -1   # Found 0 matches
$WP db search '<fragmento único de la ruta de origen>' --all-tables-with-prefix --stats | tail -1
echo "home=$($WP option get home) siteurl=$($WP option get siteurl)"
$WP option pluck wpseo_titles company_logo_meta path || true
```
`<WP_PATH>` aquí es la ruta física absoluta del docroot final (`cd <docroot> && pwd -P`),
sin barra final: la que calculará WordPress tras el cambio.

**Por qué este orden y estas cuatro formas** (en el caso de referencia, las pasadas
solo por URL dejaron 45 filas de `_elementor_data` con el staging):
1. **Ruta absoluta primero, a la ruta final.** Yoast guarda rutas físicas en
   `wpseo_titles.company_logo_meta.path` y en metadatos de imagen de los indexables
   (también en JSON escapado: 34 filas en el caso de referencia). En SiteGround la
   ruta contiene el dominio de staging (`/home/customer/www/<STAGING_DOMINIO>/public_html`):
   si el dominio se reemplaza antes, queda una ruta inexistente con el dominio nuevo.
   Y a la ruta de la obra no: tras el `mv` dejaría de existir.
2. **Dominio sin esquema** cubre `https://`, `http://`, el JSON escapado
   (`https:\/\/`) y los correos: `email_from` = `no-reply@<staging>` en los
   formularios de Elementor (19 filas, revisiones incluidas) y `email_from_2`.
3. y 4. **Normalizar a https** en las dos escrituras (un `e-svg` guardaba
   `http:\/\/`).

**Hasta 0 restos:** si `db search` no da 0, localiza por clave antes de decidir:
`SELECT meta_key, COUNT(*) FROM <TABLE_PREFIX>postmeta WHERE meta_value LIKE '%<STAGING_DOMINIO>%' GROUP BY meta_key`
y `SELECT option_name FROM <TABLE_PREFIX>options WHERE option_value LIKE '%<STAGING_DOMINIO>%'`.
- `elementor_log` (y el aviso «Skipping an uninitialized class
  Elementor\Core\Logger\Items\Base») es un registro: inocuo.
- **Base64:** search-replace no lo ve (en el caso de referencia,
  `sg_ai_studio_connected_url`). Si el plugin no viaja, se borran sus opciones (F6).
- **GUID:** se reemplazan a propósito si el staging nunca fue público.
- `wp elementor replace_urls` solo toca `_elementor_data`: red de seguridad en F9,
  no sustituto.

**Si falla la importación** (*Unknown collation*): la BD es nueva y está vacía; lee el
mensaje, corrige el `sed` y repite (`db import` sobrescribe las tablas). En MySQL
5.7, `utf8mb3` sí se acepta; `utf8mb4_0900_ai_ci` no.

---

### F6 · Plugins del hosting de origen, URL de login y estado previo `[ESCRIBE en destino: BD]` · 2 min

Dentro de un bloque `ssh … 'bash -s'` como el de F5 (`W`, `wp82`, `set -euo pipefail`):
```bash
WP="wp82 --path=$W --skip-plugins --skip-themes"
[ "$($WP config get table_prefix)" = "<TABLE_PREFIX>" ]
$WP eval '$a=get_option("active_plugins"); $b=array_values(array_filter($a, fn($p)=>!preg_match("#^(<plugin-1>|<plugin-2>)/#",$p))); update_option("active_plugins",$b); echo implode(", ",$b),"\n";'
n=0; for o in $($WP option list --search='<prefijo_opciones_1>*' --field=option_name) $($WP option list --search='<prefijo_opciones_2>*' --field=option_name); do $WP option delete "$o" >/dev/null && n=$((n+1)); done; echo "opciones borradas en la copia: $n"
for h in <hook_cron_1> <hook_cron_2>; do $WP cron event delete "$h" >/dev/null 2>&1 || true; done
$WP option update elementor_maintenance_mode_mode ''     # D1-a: protege la cortina estática; se verifica como visitante con la llave
echo "blog_public=$($WP option get blog_public)"
wp82 --path="$W" plugin list --status=active --field=name | sort | tr '\n' ' '; echo
```
**Por qué filtrar `active_plugins` con `--skip-plugins`:** se quitan sin ejecutar su
código de desactivación (equivale a `deactivate_plugins([...], true)`); sus
carpetas ya no viajaron. Las opciones y crons se borran **solo en la copia**; el
origen conserva las suyas.

| Plugin del origen (caso SiteGround) | Decisión | Motivo |
|---|---|---|
| SG Optimizer (`sg-cachepress`) | no viaja | fuera de SiteGround pasa a «file caching», manda informes y puede escribir en `.htaccess`; su caché y su WebP eran infraestructura (nginx). Opciones `siteground_optimizer_*`, `sg_cachepress`; cron `siteground_optimizer_check_assets_dir` |
| SG AI Studio (`sg-ai-studio`) | no viaja | conector con claves de la cuenta, URL del staging en base64, la única tabla con colación de MySQL 8 y rutas REST para un agente de IA. Opciones `sg_ai_studio_*`; crons `sg_ai_studio_*_cron` |
| SG Security (`sg-security`) | viaja (revisado en su código) | lógica PHP; `is_siteground()` solo limita banners y configuración; la IP sale de `REMOTE_ADDR`. Tras el cambio, `\SG_Security\Helper\Helper::set_server_ip()` (F9). No viaja su `sgs_encrypt_key.php` si el 2FA no está activo (no hay metas `sg_security_2fa*`) |

En el caso de referencia quedaron 8 plugins activos y se borraron 55 opciones del
proveedor en la copia.

**URL de login propia (D4).**
- **Si el plugin de seguridad viaja**, su acceso viaja con la BD
  (`sg_security_login_type=custom`, `sg_security_login_url=<LOGIN_SLUG>` sin barras).
  Solo hay que llamar a `set_server_ip()` en F9 y verificar en F10. Con las sales
  nuevas, todos entran otra vez por `/<LOGIN_SLUG>`. Recuperación:
  `wp sg custom-login disable`, entrada directa `/wp-login.php?sgs-token=<LOGIN_SLUG>`
  o `wp option delete sg_security_unsuccessful_login` (IP bloqueada por intentos).
  No escribas `sg_security_login_redirect`.
- **Si no viaja**, la entrada vuelve a `wp-login.php`: decídelo antes, corrige el
  documento de entrega y cualquier enlace que prometa `/<LOGIN_SLUG>`, y avisa a la
  sesión que diseñó la pantalla de login y la 404.
- En los dos casos, revisa sus destinatarios de avisos (`sg_security_notification_emails`
  en el caso de referencia seguía con un correo personal): `correo-dns.md`.

---

### F7 · `.htaccess` por bloques y `.user.ini` `[ESCRIBE en destino: W/.htaccess y W/.user.ini]` · 2 min

Script: `../scripts/componer-htaccess.sh` con la plantilla
`../scripts/htaccess-bloques.plantilla`. Orden y porqué:

| # | Bloque | Por qué aquí |
|---|---|---|
| 1 | https + dominio canónico (D8), con `RewriteCond %{REQUEST_URI} !^/\.well-known/` | antes que nada; `.well-known` fuera o la renovación ACME falla semanas después, sin aviso |
| 2 | Cortina `# BEGIN <PREFIJO>-CORTINA … # END <PREFIJO>-CORTINA`, con paso a `wp-cron.php` | se retira con un solo `sed -i '/^# BEGIN …/,/^# END …/d'`. Sin la excepción, el cron y los *loopbacks* de WP reciben 503 |
| 3 | Mapa 301/410 del sitio anterior (archivo de SEO, `RewriteBase /`) | encima de WordPress, no en un plugin (Redirects de Yoast Premium sin licencia) |
| 4 | `ErrorDocument 410 default` | cPanel tiene una página 410 global: Apache se la pasa a WordPress y WP responde 404 |
| 5 | XML-RPC cerrado (bloque del plugin de seguridad) | `order deny,allow` necesita `mod_access_compat` (estaba cargado) |
| 6 | WebP por `Accept` + `AddType image/webp .webp` + `Vary: Accept` | sustituye al nginx del hosting de origen (D10) |
| 7 | `# BEGIN WordPress … # END WordPress` | estándar |
| 8 | Caché de estáticos: `Options -MultiViews`; css/js/woff2 un año `immutable`; medios 30 días | WP versiona con `?ver=` y el child con `filemtime` |
| 9 | `# php -- BEGIN cPanel-generated handler …` | **no se escribe a mano**: lo añade MultiPHP en F9 al final del archivo |

**No se copian:** los bloques del proveedor de origen (`SGO Unset Vary` quita `Vary`
y perjudica gzip fuera de SiteGround), los del sitio anterior (Next.js: reescritura a
`index.html`, barra final, `.txt`, `ErrorDocument 404 /404/index.html`) ni ningún
`php_value` (con mod_lsapi y `lsapi_mod_php_behaviour Off` no funcionan).

**La cortina, sin imprimirla:** se copia del `.htaccess` del docroot actual con `sed`
hasta su línea de cierre, `awk` inserta
`  RewriteCond %{REQUEST_URI}  !^/wp-cron\.php$` justo después de la excepción de
`.well-known` (antes del `R=503`) y `cmp` confirma que el resto es idéntico byte a
byte. Si el destino no tiene cortina todavía, plantilla en `../scripts/cortina-503.plantilla`.

**`.user.ini`:** `max_input_vars = 3000` (cPanel trae 1000, corto para menús, Polylang
y Elementor). Con mod_lsapi funciona si el servidor tiene `lsapi_enable_user_ini On`
(compruébalo en la auditoría).

**Lo esperado** (salida de `componer-htaccess.sh`): `cortina: idéntica…`,
`reglas wp-cron: 1`, `reglas del mapa: <n>` (el número del mapa de SEO), `marcadores sin
rellenar: 0`, todos los pares BEGIN/END y `max_input_vars = 3000`.

**Antes del cambio, puerta B.** `wp82 --path="$W" eval-file ~/<PREFIJO>-migracion/estado-sitio.php`
carga WordPress con plugins contra la BD nueva: portada, kit, `blog_public=0`, modo de
mantenimiento, clases V4. Un *fatal* aquí es NO-GO: se corrige en la obra y el público
no se entera.

---

### F8 · Cambio de carpetas: dos `mv` `[ESCRIBE en destino]` · < 1 s + 1 min de comprobación

Script con modos `cambiar` y `volver`: `../scripts/cambio-carpetas.sh`.
```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
D=<WP_PATH>; W=~/<PREFIJO>-wp-nuevo; R=~/<PREFIJO>-respaldos; TS=$(date +%Y%m%d-%H%M); N=$(basename "$D")
mkdir -p "$R" && chmod 700 "$R"
test -d "$D" && test -f "$W/wp-config.php" && test -f "$W/.htaccess" && test -d "$W/.well-known" && test ! -e "$R/$N.prototipo-$TS" \
  || { echo "Falta algo en la obra o el respaldo ya existe: NO-GO"; exit 1; }
cp -an "$D/.well-known/." "$W/.well-known/" || true  # por si AutoSSL escribió algo desde F3 (-n puede salir con 1 al saltar existentes)
mv "$D" "$R/$N.prototipo-$TS"
mv "$W" "$D" || { mv "$R/$N.prototipo-$TS" "$D"; echo "Falló el segundo mv: sitio anterior restaurado. NO-GO"; exit 1; }
echo "$TS" > "$R/ULTIMO-CAMBIO"
stat -c '%U:%G %a %n' "$D" "$R/$N.prototipo-$TS"
EOF
```
En el mismo minuto, desde el Mac (UA de navegador: algunos hostings retan al de curl):
`/` → 503, la página de la cortina → 200, `/wp-login.php` → 503, un archivo real de
`/.well-known/acme-challenge/` → 200 por http y por https.

**Por qué es seguro:** mismo disco (dos `rename` instantáneos, hueco de
microsegundos); la carpeta nueva 755 se sirve; el sitio anterior conserva sus
permisos en el respaldo. **Cualquier otro código (403, 500, 404): R1 de inmediato.**

**Por qué la guarda lleva `|| { …; exit 1; }`:** con `set -e`, una lista `a && b && c`
solo aborta si falla el último comando; si falla el primero, el script sigue y haría
el `mv` con la obra incompleta. Por lo mismo, un `! grep …` suelto no aborta nunca.

**Si el docroot sirve hoy un sitio vivo sin cortina** (generalización, no probada en
el caso de referencia): o se pone antes la cortina estática en el docroot actual (el
público ve 503 desde ese momento), o el público pasa del sitio anterior a la cortina
de la obra en el instante del `mv`. Decídelo en D1.

---

### F9 · Tareas con la ruta final y versión de PHP `[ESCRIBE en destino: BD y .htaccess]` · 5–10 min

```bash
ssh <SSH_ALIAS_DESTINO> 'bash -s' <<'EOF'
set -euo pipefail
wp82(){ /opt/cpanel/ea-php82/root/usr/bin/php /usr/local/bin/wp "$@"; }
P=<WP_PATH>; WP="wp82 --path=$P --url=https://<DOMINIO>"
[ "$($WP config get table_prefix)" = "<TABLE_PREFIX>" ]
$WP transient delete --all
$WP cache flush
$WP elementor replace_urls https://<STAGING_DOMINIO> https://<DOMINIO> --force   # red de seguridad: 0 filas
$WP elementor flush_css
$WP elementor-pro theme-builder clear_conditions
$WP eval-file ~/<PREFIJO>-migracion/post-cambio.php --user=1     # cachés de Polylang, IP del plugin de seguridad
$WP rewrite flush                                                 # suave: no reescribe el .htaccess
$WP yoast index --reindex --skip-confirmation
for s in '<STAGING_DOMINIO>' '<PREFIJO>-wp-nuevo' '<fragmento de la ruta de origen>'; do echo "$s → $($WP db search "$s" --all-tables-with-prefix --stats | tail -1)"; done
$WP db query "SELECT COUNT(*) FROM <TABLE_PREFIX>yoast_indexable WHERE permalink LIKE 'https://<DOMINIO>%'" --skip-column-names
$WP eval 'echo wp_json_encode( get_option( "elementor_pro_theme_builder_conditions" ) ), "\n";'
$WP cron test || true
EOF
```
- **Por qué después del `mv` y con `--url`:** los indexables de Yoast y los metadatos
  de imagen guardan rutas y URL calculadas en el momento; reindexar en la obra
  escribiría su ruta en la BD (se comprueba con `db search '<PREFIJO>-wp-nuevo'` = 0).
  La caché de condiciones del Theme Builder y las de Polylang (`pll_languages_list`,
  `pll_translated_slugs`) guardaban el dominio viejo.
- Usa las formas con guion bajo (`replace_urls`, `flush_css`, `clear_conditions`): son
  las verificadas (trampa A10 de `elementor4-atomico/references/gotchas-cli.md`).
  `flush-css` con guion también funcionó; `replace-urls` y `clear-conditions` con guion
  no se probaron.
- **Lo esperado:** tres `Found 0 matches` (si el fragmento de la ruta sale solo en
  `elementor_log`, es el registro: inocuo; en el caso de referencia, 58 coincidencias ahí); indexables > 0; condiciones completas
  (cabecera, pie, singles, 404); `home=https://<DOMINIO> idiomas=<IDIOMA_BASE>,<IDIOMA_2>` y la
  portada del segundo idioma en `https://<DOMINIO>/<IDIOMA_2>/`;
  `Success: WP-Cron spawning is working as expected.` (gracias al paso a `wp-cron.php`).
- **Si `yoast index` falla con un WP-CLI antiguo** (2.9 frente a WP 7.1): phar oficial
  2.12 verificando su SHA-512 y con permiso del usuario para descargarlo, o reindexar
  desde Yoast › Herramientas.

**9b · Versión de PHP (D9)** `[ESCRIBE: MultiPHP; cPanel añade un bloque al .htaccess]`
En su propio bloque `ssh <SSH_ALIAS_DESTINO> 'bash -s'` con `set -euo pipefail` y `P=<WP_PATH>`:
```bash
cp -p "$P/.htaccess" ~/<PREFIJO>-respaldos/htaccess.antes-php-$(date +%Y%m%d-%H%M)
uapi --output=json LangPHP php_set_vhost_versions version=ea-php82 vhost=<DOMINIO> | grep -o '"status":[01]'
uapi --output=json LangPHP php_get_vhost_versions   # la entrada del dominio: version ea-php82 y su propio phpversion_source
grep -n 'cPanel-generated\|AddHandler' "$P/.htaccess"
```
- **Por qué después del cambio:** MultiPHP escribe su `AddHandler` en el `.htaccess`
  del docroot; hecho antes, se habría ido con la carpeta vieja. Con `phpversion:
  inherit`, el dominio adicional heredaba la versión del `AddHandler` del `.htaccess`
  del sitio vecino (que además reescribía un gestor automático del hosting). Con
  mod_lsapi los handlers se heredan por directorio; las reglas de rewrite no.
- **Lo esperado:** `"status":1`; al final del `.htaccess`:
  `# php -- BEGIN cPanel-generated handler, do not edit` ·
  `AddHandler application/x-httpd-ea-php82___lsphp .php .php8 .phtml` ·
  `# php -- END cPanel-generated handler, do not edit`. El sitio sigue en 503.
- **En cualquier edición posterior del `.htaccess`, ese bloque se conserva** y se
  avisa a las otras sesiones. Si `uapi` lo rechaza: MultiPHP Manager `[MANUAL usuario]`.
- **Cron del sistema (D15, opcional, después de lanzar):** `DISABLE_WP_CRON` y
  `*/15 * * * * cd <WP_PATH> && /opt/cpanel/ea-php82/root/usr/bin/php wp-cron.php >/dev/null 2>&1`.

**Memoria del proyecto, ahora:** «`<DOMINIO>` es producción; el staging queda congelado
N días; trabaja siempre con `wp --path=<WP_PATH>` en producción». Sin esto, una
sesión futura edita el staging y el cambio no llega al sitio real.

---

### F10 · Verificación con la llave, como visitante sin sesión `[lectura, salvo J]` · 30–40 min

La llave se lee del servidor dentro de una sustitución de shell, se cambia por una
cookie en un archivo temporal y se descarta; nunca aparece en la salida.
```bash
J=$(mktemp); UA='Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36'
TOKEN=$(ssh <SSH_ALIAS_DESTINO> "grep -oE '<PARAM_LLAVE>=[a-z0-9]{8,}' <WP_PATH>/.htaccess | head -1 | cut -d= -f2")
curl -sS -A "$UA" -o /dev/null -c "$J" "https://<DOMINIO>/?<PARAM_LLAVE>=$TOKEN"; unset TOKEN
c(){ curl -sS -A "$UA" -o /dev/null -b "$J" -w "%{http_code} %{redirect_url}  $1\n" "https://<DOMINIO>$1"; }
# … comprobaciones …
rm -f "$J"
```
Batería completa: `../scripts/comprobar-http.sh` (modos `publico`, `llave` y `lanzado`).
La expresión `[a-z0-9]{8,}` recoge la llave entera y no confunde la palabra de salida
de la cortina (`salir`) con ella.

| Bloque | Qué | Lo esperado |
|---|---|---|
| A · público sin llave | `/` y una interior | 503 + `Retry-After` + `Cache-Control: no-store`; `http://` y `www` → 301 a `https://<DOMINIO>/…` |
| B · todas las URL publicadas, en los dos idiomas | `wp post list --post_type=page,<CPT> --post_status=publish --field=url --lang=` | 200 en todas (`sort \| uniq -c` de los códigos) |
| C · rutas especiales | `robots.txt`, `sitemap_index.xml`, `/wp-json/`, `/no-existe-<azar>/`, `/<LOGIN_SLUG>`, `/wp-login.php`, `/wp-admin/`, `xmlrpc.php`, un `.php` dentro de `plugins/` | robots 200 `text/plain` (con `Disallow: /` mientras `blog_public=0`); sitemap 200 XML; wp-json 200; 404 real con la plantilla 404; con SG Security, login propio 302 a `wp-login.php?sgs-token=…` y `wp-login.php`/`wp-admin` sin sesión → 302 a `/404`; xmlrpc 403; PHP en plugins 403 |
| D · mapa 301/410 | una muestra por regla y **todas** las URL nuevas | 301 al destino del mapa; 410 en lo retirado; ninguna URL nueva capturada |
| E · contenido | portada y una interior por idioma | 0 menciones del staging; `<html lang>` correcto; canonical y hreflang (`<IDIOMA_BASE>`, `<IDIOMA_2>`, `x-default`) con `https://<DOMINIO>` (con noindex, Yoast omite el canonical: se ve tras lanzar) |
| F · cabeceras | portada, una fuente woff2, un JPG con `Accept: image/webp` | `content-encoding: gzip`; woff2 `max-age=31536000, immutable`; `image/webp` + `vary: Accept` si existe la copia (si no, `image/jpeg` es lo correcto) |

- **Trampa:** no listes las URL con `--skip-themes` si el CPT o sus reescrituras los
  registra el tema hijo: salen `/?p=<id>` y parecen 301 (pasó en el caso de referencia).
- **G · Formularios y correo en seco** `[lectura]`: la sonda de la skill
  `elementor4-atomico`, `../../elementor4-atomico/scripts/probe-forms-seco.php`
  (`E4_PAGINA=<ID> wp --path=<WP_PATH> --url=https://<DOMINIO> --user=1 eval-file …`, una
  vez por idioma; `E4_CABECERAS=1` añade el `From`/`Sender` reales). `pre_wp_mail` corta
  el envío: no sale nada. Esperado: validación correcta por casos y el correo simulado con
  `From: <Marca> <no-reply@<DOMINIO>>`. Los avisos del núcleo, con
  `../scripts/correo-seco.php`. **Nunca** un envío real: le llega al cliente.
- **H · Clases V4 antes de que nadie publique en el editor:**
  `_elementor_global_classes_order` y `_elementor_global_classes_order_preview` del kit
  iguales y con el mismo número que los posts `e_global_class` (`estado-sitio.php`).
  Si difieren, publicar en el editor borraría clases del sitio: se sincronizan con
  `../../elementor4-atomico/scripts/e4-sync-preview.php` antes de dar acceso a nadie.
- **I · `[MANUAL usuario, con la llave]`:** entrar por `/<LOGIN_SLUG>` (sales nuevas);
  abrir una página de cada idioma y la cabecera del Theme Builder en Elementor y pulsar
  «Actualizar» sin cambios (un 403 o 500 en la consola de red = ModSecurity del
  hosting: ticket al proveedor antes de dar acceso al cliente); Elementor › Herramientas
  › «Regenerar archivos y datos»; selector de idioma en portada, listado, un single y
  contacto; Salud del sitio: versión de PHP y `max_input_vars` (un aviso de *loopback*
  mientras dure la cortina es normal).
- **J · Cortina de Elementor, encender y apagar** `[ESCRIBE: una opción que vuelve a su
  valor]`: `option update elementor_maintenance_mode_mode maintenance` → con la llave y
  sin sesión, `/` y `/<IDIOMA_2>/` dan 503 con la plantilla de mantenimiento de cada
  idioma → `option update elementor_maintenance_mode_mode ''`. Mientras exista la cortina
  estática, su cabecera deja `Retry-After: 3600` aunque Elementor mande 600.
- **Sesión SEO:** pídele que verifique su parte en el destino con la misma receta de la
  llave (sin imprimirla) y UA de navegador.
- **Detrás de la cortina de Elementor** (excluye a quien tiene sesión, así que la llave ya
  no sirve), el HTML real se pinta desde WP-CLI con
  `../../wp-seo-rendimiento/scripts/wp-cli/render.php`.

---

### F11 · Salida de la cortina: Mantenimiento de Elementor o lanzamiento

Todo en `lanzamiento.md`:
- **11a · no se lanza hoy** y el usuario quiere controlar la cortina desde wp-admin:
  primero `elementor_maintenance_mode_mode maintenance`, después se quita el bloque
  estático (con copia del `.htaccess` antes). La llave deja de servir; solo ve el sitio
  quien ha iniciado sesión. Fue el estado final del caso de referencia.
- **11b · lanzamiento**, solo con la puerta D en verde. En el caso de referencia es una
  secuencia **planificada y no ejecutada**: el sitio quedó en 11a, a la espera del DNS del
  correo y del OK de SEO.

---

### F12 · Vuelta atrás

`rollback.md`: R1 (dos `mv` inversos, < 1 min), R2 (Mantenimiento en un segundo), R3
(`.htaccess`), R4 (PHP), R5 (DNS y correo), R6 (plugins o licencias).

---

### F13 · Limpieza `[MANUAL usuario; ningún borrado lo hace el agente]`

Se le presenta al usuario como tabla; cada borrado, con su permiso explícito y en
comandos separados.

| Dónde | Qué | Cuándo |
|---|---|---|
| destino | `B/*.sql` (datos personales: hashes, correos, envíos) | a los 7 días sin incidencias |
| destino | `R/<docroot>.prototipo-*` (sitio anterior) | **nunca**: es la vuelta atrás |
| destino | `R/<docroot>.wp-retirado-*` (solo si hubo R1) | tras un lanzamiento estable |
| destino | la página y los recursos de la cortina en la raíz de WP | pueden quedarse; el `favicon.ico` del sitio anterior se sustituye por el icono del sitio en WP |
| destino | BD del sitio anterior | decisión aparte (D16), con copia local |
| Mac | `L` | 30 días (D13) |
| origen | staging | según D12; mientras viva, noindex y su cortina |

Los volcados de la BD **del sitio anterior** (para el mapa 301) también contienen datos
personales (envíos de formularios guardados como posts): se filtran por tipos públicos
antes de imprimir nada y se borran al terminar, con permiso.

---

## §4 · Tiempos y caída (caso de referencia)

| Tramo | Duración | Qué ve el público |
|---|---|---|
| F1–F7 (preparación en la obra; las auditorías de F0 van antes) | 45–60 min | el sitio anterior detrás de su 503, sin cambios |
| F8 (cambio) | < 1 s + 1 min de comprobación | el mismo 503 antes y después |
| F9–F10 (ajustes y verificación) | 40–55 min | el mismo 503 |
| F11b (lanzamiento; planificado, no ejecutado en el caso) | instantáneo + 10 min de comprobaciones | 503 → sitio (200) |
| R1 (vuelta atrás; preparada, no hizo falta) | < 1 min | vuelve el 503 del sitio anterior |

## §5 · Riesgos que suelen quedar aceptados

- Correo sin autenticar hasta D7: los envíos se guardan en Elementor › Envíos.
- Plugins de procedencia dudosa hasta D5: desvío de la biblioteca de plantillas a un
  tercero, escrituras en la BD en cada petición, sin actualizaciones oficiales.
- Sin caché de página hasta D11: TTFB de PHP más alto (0,7–1,1 s en el caso de referencia).
- MySQL fuera de soporte en el destino (5.7): Salud del sitio lo avisa.
- WP-CLI antiguo en el destino: plan B con el phar oficial verificado.
- ModSecurity del hosting no gestionable desde la cuenta: se detecta en F10-I.
- Sitio vecino en la misma cuenta: fuera de alcance; si una auditoría expuso algo suyo,
  se le dice al usuario y se recomienda rotarlo.
