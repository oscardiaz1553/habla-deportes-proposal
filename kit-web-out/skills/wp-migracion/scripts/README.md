# Scripts de `wp-migracion`

Utilidades para ejecutar el runbook (`../references/runbook.md`) sin teclear a mano los
pasos delicados. Son **plantillas**: se copian al scratchpad del proyecto, se rellena su
bloque CONFIG o sus variables, se valida la sintaxis y se ejecutan. Ninguna guarda ni
imprime secretos.

Reglas comunes:
- Los `.sh` de destino se ejecutan con `ssh <SSH_ALIAS_DESTINO> 'bash -s' < script.sh`
  (o `'bash -s -- <modo>'`): cada uno lleva `set -euo pipefail` y sus guardas.
- Los `.php` se suben con `scp` y se ejecutan con `wp --path=<WP_PATH> … eval-file <archivo>
  --user=1` (en cPanel, con el PHP de la web: ver «Convenciones» del runbook).
- Antes de ejecutar: `bash -n archivo.sh` o `php -l archivo.php`.
- Los `.sh` que usan carpetas del proyecto llevan `PREFIJO=pfx` en su bloque CONFIG: cámbialo por el
  `<PREFIJO>` del proyecto; de él salen la obra, la carpeta de migración y los respaldos.
- En `--path=` usa `"$HOME/…"`, no `~/…`: bash no expande la tilde detrás de `--path=`.
- Los que escriben (`[ESCRIBE]`) necesitan el OK explícito del usuario **cada vez**.
- En Claude Code, los que usan red (`ssh`, `scp`, `curl`) necesitan `dangerouslyDisableSandbox`.

| Script | Dónde corre | Qué hace | Modo | Fase | Estado |
|---|---|---|---|---|---|
| `exportar-origen.sh` | Mac | `congelacion`: última edición y archivos cambiados en 30 min. `exportar`: BD y `wp-content` por la salida estándar de SSH, `tar --warning=no-file-changed` aceptando `rc ≤ 1`, integridad (cabecera y cierre del volcado, `CREATE TABLE`, colación 0900, `gzip -t`, archivos clave, plugins que no viajan) y `SHA256SUMS` | lectura en origen; escribe solo en el Mac | F1 | generalizado, no probado fuera de HomeMaker |
| `search-replace.sh` | destino | importación opcional con la colación corregida solo en el esquema; reemplazos en orden (ruta normal y escapada → dominio sin esquema → https normal → https escapada); restos por `meta_key` y opción; `home`/`siteurl`; ruta del logo de Yoast. `MODO=seco` primero | `[ESCRIBE]` en la BD de la obra con `MODO=real` | F5 | generalizado, no probado fuera de HomeMaker |
| `htaccess-bloques.plantilla` | — | el `.htaccess` de producción por bloques, en orden y con el porqué de cada uno | plantilla | F7 | probado en HomeMaker (bloques); marcadores generalizados |
| `cortina-503.plantilla` | — | cortina estática 503 con llave de revisión por cookie, con paso a `wp-cron.php` y `.well-known` | plantilla | F7 | estructura probada en HomeMaker; llave generada y marcadores, no |
| `componer-htaccess.sh` | destino | compone el `.htaccess` de la obra desde la plantilla, copia la cortina actual sin imprimirla (o la crea desde su plantilla con llave generada en el servidor), añade el mapa de SEO, `cmp` de la cortina, recuento de reglas y marcadores; escribe `.user.ini` | `[ESCRIBE]` en la obra | F7 | generalizado, no probado fuera de HomeMaker (probado en local con carpetas falsas) |
| `cambio-carpetas.sh` | destino | `comprobar` (mismo disco, obra completa, permisos), `cambiar` (dos `mv`, `ULTIMO-CAMBIO`) y `volver` (R1) | `comprobar` lectura; `cambiar`/`volver` `[ESCRIBE]` | F8, F12 | comandos probados en HomeMaker; script con modos, no |
| `post-cambio.php` | destino (`eval-file`) | cachés de idiomas y slugs de Polylang; IP del servidor en SG Security; portada de cada idioma | `[ESCRIBE]` (transitorios y una opción de SG Security) | F9 | probado en HomeMaker |
| `estado-sitio.php` | obra o destino (`eval-file`) | URL, portada, kit, `blog_public`, cortina de Elementor, IndexNow, login propio (sin el slug), plugins y mu-plugins, `PLL_COOKIE`, idiomas, clases V4 frontend = preview, condiciones del Theme Builder | lectura | puertas B y D, F10-H | generalizado, no probado fuera de HomeMaker |
| `correo-seco.php` | destino (`eval-file`) | cabeceras reales de un aviso del núcleo (`From`, `Sender`, `Mailer`), dominio y alineación, `sendmail_path`; no envía nada | lectura | F10-G, D7 | probado en HomeMaker (la prueba en seco) |
| `comprobar-http.sh` | Mac | batería curl: `publico` (503, `Retry-After`, http/www → 301), `llave` (con la cookie de revisión leída sin imprimirla: rutas publicadas, especiales, mapa 301/410, contenido, cabeceras) y `lanzado` (robots con `Sitemap:`, sin noindex, canonical) | lectura | F8, F10, F11 | generalizado, no probado fuera de HomeMaker |
| `dns-correo.sh` | Mac | SPF (cuántos y si autoriza la IP), DKIM, DMARC, MX y serial SOA en el NS autoritativo frente a un resolver; `esperar` repite cada 25 s | lectura | F0, D7 | generalizado, no probado fuera de HomeMaker |

## De otras skills (no se duplican aquí)

- **Formularios en seco** (validación por casos y correo simulado con `pre_wp_mail`):
  `../../elementor4-atomico/scripts/probe-forms-seco.php`, con `E4_PAGINA=<ID>` una vez por
  idioma y `E4_CABECERAS=1` para ver el `From`/`Sender` reales.
- **Orden de clases V4 frontend → preview:** `../../elementor4-atomico/scripts/e4-sync-preview.php`.
- **Probador del mapa 301/410 antes de subirlo:** `../../wp-seo-rendimiento/scripts/medicion/probar-301.py`.
- **HTML real detrás de la cortina de Elementor:** `../../wp-seo-rendimiento/scripts/wp-cli/render.php`.
- **IndexNow apagado en staging:** `../../wp-seo-rendimiento/scripts/wp-cli/yoast-guard.php`.
- **mu-plugin `PLL_COOKIE=false`** (que la caché de página pueda guardar el HTML):
  `../../wp-seo-rendimiento/scripts/tema-hijo/mu-sin-cookie-idioma.php`.

## Secuencia típica

```bash
./exportar-origen.sh congelacion                 # F1a (tras anunciar la congelación)
./exportar-origen.sh exportar                    # F1b-c
# F2: scp -p + sha256sum -c (runbook)
# F3-F4: bloques del runbook (obra, BD, wp-config)
ssh <SSH_ALIAS_DESTINO> 'bash -s' < search-replace.sh        # MODO=seco, después MODO=real e IMPORTAR=si
ssh <SSH_ALIAS_DESTINO> 'bash -s' < componer-htaccess.sh     # F7
ssh <SSH_ALIAS_DESTINO> 'wp --path="$HOME/<PREFIJO>-wp-nuevo" eval-file ~/<PREFIJO>-migracion/estado-sitio.php --user=1'   # puerta B
ssh <SSH_ALIAS_DESTINO> 'bash -s -- comprobar' < cambio-carpetas.sh
ssh <SSH_ALIAS_DESTINO> 'bash -s -- cambiar' < cambio-carpetas.sh    # F8, con OK
DOMINIO=<DOMINIO> ACME=<archivo> ./comprobar-http.sh publico          # puerta C, en el mismo minuto
# F9: bloque del runbook + post-cambio.php + versión de PHP
DOMINIO=<DOMINIO> SSH_ALIAS=<SSH_ALIAS_DESTINO> WP_PATH=<WP_PATH> PARAM_LLAVE=<PARAM_LLAVE> RUTAS=rutas.txt MAPA=mapa.tsv ./comprobar-http.sh llave   # F10
DOMINIO=<DOMINIO> IP_SERVIDOR=<IP_SERVIDOR> ./dns-correo.sh           # D7
DOMINIO=<DOMINIO> ./comprobar-http.sh lanzado                         # tras F11b
```
