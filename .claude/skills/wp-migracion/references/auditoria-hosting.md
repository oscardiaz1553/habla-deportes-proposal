# Auditoría de origen y destino (F0)

Antes de escribir una línea del runbook se audita **en solo lectura** el hosting de
origen (staging) y el de destino (producción). La auditoría decide el método, las
exclusiones, el orden del search-replace, el `.htaccess` y las decisiones D1–D16 de
`runbook.md`. En el caso de referencia cambió el plan en cinco puntos: plugins
*nulled*, correo que iba a fallar SPF/DKIM, colación de MySQL 8 frente a 5.7, dominio
guardado en base64 y un docroot dentro del `public_html` de otro sitio.

---

## Cómo se audita sin romper nada ni filtrar nada

- **Solo lectura en los dos servidores:** `ls`, `stat`, `du`, `find`, `grep`, `sed -n`,
  `wp … get/list`, `wp db query` con `SELECT`, `uapi` de consulta (`list`, `get`,
  `validate`, `parse`), `php -i`, `curl -I`, `dig`, `openssl s_client`. Nada de
  escrituras, `mv`, `chmod`, `uapi` que modifique, `search-replace`, `db import` ni
  exportaciones al disco del servidor.
- **Workflow que funcionó:** tres agentes en paralelo (destino, origen, procedimiento) y
  un cuarto que consolida el runbook. El prompt común declara lo intocable y prohíbe
  escribir, imprimir secretos y usar el navegador. El orquestador comprueba en persona
  los hechos clave (administradores, código de los plugins premium).
- **Secretos: el prompt lo prohíbe y aun así se filtró.** En el caso de referencia, un
  agente leyó entero el `wp-config.php` del sitio vecino para averiguar su prefijo y sus
  credenciales de BD quedaron en la transcripción; hubo que recomendar rotarlas. Regla:
  - Prohibido `cat`, `sed` o `grep` de contenido sobre `wp-config.php`, `.env`,
    `.my.cnf` y `*.sql`.
  - Receta segura: `wp config list --fields=name,type` (solo nombres),
    `wp config get table_prefix` y como mucho `DB_NAME`, `wp config has <CONST>`,
    `grep -c` o `grep -oE "define\( *'[A-Z_]+'"`.
  - Revisa la salida de cada agente antes de reenviarla:
    `grep -iE "DB_PASSWORD|AUTH_KEY|SALT'" informe.md` → 0.
  - Si algo se filtró, díselo al usuario en el cierre (qué, de quién) y recomienda
    rotarlo. No lo arregles tú en un sitio ajeno.
- **Datos personales:** listas de usuarios, solo login, rol y fecha de alta; de
  `admin_email`, solo el dominio; de volcados antiguos, filtra por tipos públicos antes
  de imprimir (los envíos de formularios suelen estar guardados como posts).
- **Informe para el dueño:** tabla de hallazgos con impacto (crítico, alto, medio,
  info) y «qué hacer», tamaños, qué NO migrar y por qué, dónde aparece el dominio
  (tabla.columna y filas), opciones relevantes y los comandos usados. Declara al final
  que no contiene contraseñas, claves, sales ni la llave de la cortina.

---

## Origen (staging)

| Qué | Cómo | Para qué |
|---|---|---|
| Actividad reciente | `MAX(post_modified_gmt)`; `find <child> <mu-plugins> <uploads> -newermt '-30 minutes'`; administradores con fecha de alta | ¿alguien sigue editando? Congelación (F1) y cuentas que confirmar |
| Tamaños | `du -sh` de raíz, `wp-content`, plugins, uploads, languages; tamaño de la BD por tabla y recuento de posts por tipo | tiempos, cuota y qué pesa |
| Qué NO migrar | copias generadas por el optimizador (`*.jpg.webp`), caché CSS de Elementor, restos de temas anteriores, carpetas vacías de plugins, logs de PHP, claves de cifrado del plugin de seguridad, temporales de actualización, plugins atados al proveedor | exclusiones del tar (F1). Ojo: un `.webp` sin doble extensión suele ser un original y **sí** viaja |
| `wp-config.php` | **solo nombres** y valores no secretos (`DB_CHARSET`, `table_prefix`, `WP_DEBUG`); includes del proveedor; constantes que pone el proveedor en tiempo de ejecución (`WP_MEMORY_LIMIT`, `WP_AUTO_UPDATE_CORE`) | no se copia: se crea uno nuevo (F4) |
| Plugins | lista con versión y actualización disponible; `wp plugin verify-checksums --all`; primeras líneas del archivo principal de cada premium buscando `pre_http_request`, `update_option` de licencias y dominios ajenos | D3, D4, D5 |
| Plugins del proveedor | qué hace cada uno fuera de su infraestructura (buscar `is_<proveedor>()` o equivalentes), qué opciones y crons deja, si guarda claves de la cuenta | F6: no viajan o viajan revisados |
| mu-plugins | qué hacen y si dependen del hosting | casi siempre viajan tal cual (p. ej. `PLL_COOKIE=false`, filtro de idioma del editor) |
| Código del child ligado al hosting | `grep` en php/js/css de: nombre del proveedor, `purge`, `x-cache`, `nginx`, `.webp`, el dominio de staging, `home_url`, `mail` | casi siempre son comentarios; confirma que el remitente del correo se deriva de `home_url()` |
| Dónde aparece el dominio | por tabla.columna: `posts.guid`, `posts.post_content`, `postmeta` (`_elementor_data` normal y **escapado** `https:\/\/`, `_menu_item_url`, imágenes OG de Yoast), `yoast_seo_links`, `yoast_indexable`, `options` (`siteurl`, `home`, `wpseo`, `wpseo_titles`, `wpseo_social`, transitorios), `usermeta`, `users.user_url`, logs de plugins; **rutas físicas** (`wpseo_titles.company_logo_meta.path`); **correos** `no-reply@<staging>` de los formularios; **base64** | orden y formas del search-replace (F5) |
| Colaciones | `SELECT table_name, table_collation FROM information_schema.tables WHERE table_schema=DATABASE()` | `utf8mb4_0900_ai_ci` no existe en MySQL 5.7 (F5) |
| Opciones de lanzamiento | `blog_public`, `elementor_maintenance_mode_mode` / `_template_id` / `_exclude_mode`, `page_on_front`, `elementor_active_kit`, `permalink_structure`, `WPLANG`, opción `polylang` (`default_lang`, `force_lang`, `hide_default`, `domains`, `nav_menus`), login propio del plugin de seguridad | estado previo (F6) y lanzamiento |
| IDs fijos | portada, kit, plantillas de mantenimiento y del Theme Builder, menús por idioma, adjuntos fijados en constantes del child, traducciones | por qué se migra con volcado de BD y no con WXR |
| Licencias | dónde guarda cada premium su clave y qué pasa al cambiar de URL | D5: con licencia real, la reactiva el usuario en el wp-admin nuevo; con *nulled* no hay nada que reactivar |
| Redirecciones existentes | `wpseo_redirect` y exportaciones de Yoast; `.htaccess` actual | el mapa 301 va en `.htaccess` (F7) |

---

## Destino (producción)

| Qué | Cómo (cPanel) | Para qué |
|---|---|---|
| Dominios y docroots | `uapi DomainInfo list_domains`, `uapi --output=json DomainInfo domains_data`, `uapi DomainInfo single_domain_data domain=<DOMINIO>`; archivo sonda con nombre aleatorio pedido por HTTPS (lo borra el usuario) | **el docroot real, nunca deducido**. Un dominio adicional suele ser subcarpeta del `public_html` del principal |
| Permisos y disco | `stat -c '%U:%G %a %n'` del docroot y de carpetas servidas (`.well-known`); `id` (¿el usuario está en el grupo `nobody`?); `stat -c %d ~ <docroot>` | 755 o grupo `nobody` para que Apache lea; mismo disco para que el `mv` sea atómico |
| SSL | `uapi SSL installed_hosts`; `openssl s_client … \| openssl x509 -noout -enddate`; problemas de AutoSSL; un archivo real de `/.well-known/acme-challenge/` por http y https | fecha de renovación; `.well-known` fuera de cortina y redirecciones |
| Redirecciones actuales | `curl -sI` de `http://`, `https://`, con y sin `www` | qué hay que añadir en el bloque canónico |
| Herencia de `.htaccess` | leer el `.htaccess` del padre y el del docroot (sin imprimir llaves: enmascara con `sed`) | las `RewriteRule` no se heredan sin `RewriteOptions Inherit`; los handlers de PHP sí; las cabeceras del padre también |
| PHP web y CLI | `uapi LangPHP php_get_vhost_versions` (`phpversion: inherit` y `phpversion_source`); `uapi LangPHP php_ini_get_user_basic_directives type=vhost vhost=<DOMINIO>`; versión de `php` en CLI; extensiones | fijar versión (F9); `max_input_vars` (1000 por defecto es corto); WP-CLI con el binario de la web |
| Servidor web | módulos: `mod_lsapi` (`lsapi_mod_php_behaviour`, `lsapi_enable_user_ini`), `mod_access_compat`, `mod_deflate`, `mod_expires`, `mod_headers`, `mod_security2` | `.user.ini` sí, `php_value` no; `order deny,allow`; gzip; ModSecurity no gestionable = riesgo al guardar en Elementor |
| MySQL | `uapi Mysql get_server_information`; `uapi Mysql get_restrictions`; `uapi Mysql list_databases` | versión (colaciones), prefijo y longitudes obligatorios; que `<DB_NAME>` no exista; BD antiguas que no se tocan |
| WP-CLI | `wp --version`, su intérprete | un WP-CLI antiguo puede fallar con un WP reciente (`yoast index`) |
| Correo | `uapi Email list_mxs` (`mxcheck`, `alwaysaccept`); `uapi EmailAuth validate_current_spfs` y `validate_current_dkims`; `dig` contra los NS autoritativos (MX, SPF, DKIM, DMARC, SOA); `uapi DNS parse_zone zone=<DOMINIO>` (campos en base64); `sendmail_path` | `correo-dns.md` |
| Caché y CDN | ¿LiteSpeed, nginx o Varnish? cabeceras de caché del padre; ¿el DNS apunta al origen o a un CDN? | caché de página por plugin (D11: recomendada antes o justo al lanzar; la decide el usuario; `lanzamiento.md`) |
| Cron | `crontab -l`, `uapi Cron list_cron` | la cortina bloquea `wp-cron.php` si no se exceptúa |
| Disco | `uapi Quota get_quota_info` | cabe la obra además del respaldo |
| Sitio en vivo | `curl` de `/`, `robots.txt`, una interior, `/wp-login.php` | estado de partida y qué ve Google hoy |
| Qué conservar | `.well-known/` completo (incluido su `.htaccess` y los retos del CDN del hosting), la cortina y cada recurso que dibuja, el mapa 301 del sitio anterior, archivos de verificación (`google*.html`, `BingSiteAuth.xml`, `ads.txt`) | copiarlo a la obra (F3) y no perderlo en el cambio |

---

## Checklist de riesgos (el orden importa: los primeros rompen sitios ajenos)

- [ ] **Docroot compartido.** Si el docroot es subcarpeta del `public_html` de otro sitio,
      declara «intocable» todo lo demás en el runbook y en la memoria del proyecto (con
      el aviso «LA TRAMPA IMPORTANTE»). Nunca se vacía ni se renombra `public_html`.
- [ ] **`wp --path` siempre.** WP-CLI sube carpetas buscando `wp-load.php` o
      `wp-cli.yml`; en una carpeta sin WordPress dentro del sitio vecino, escribiría en él.
- [ ] **Guardas de prefijo en cada bloque que escribe:**
      `[ "$(wp --path="$P" config get table_prefix)" = "<TABLE_PREFIX>" ] || exit 1`, y
      `DB_NAME` cuando se trabaja con la BD nueva. Borrados (del usuario) con guarda de
      ruta: `case "$PWD" in */<ruta esperada>) ;; *) exit 1;; esac`.
- [ ] **No tocar el sitio vecino:** ni su BD, ni su `.htaccess`, ni su staging, ni sus
      respaldos. Sus hallazgos (archivos sospechosos, credenciales expuestas) solo se
      informan.
- [ ] **Obra fuera de `public_html`:** dentro sería visible como `https://<vecino>/<carpeta>/`.
- [ ] **PHP heredado del vecino** (`phpversion: inherit`): si el vecino o un actualizador
      automático cambia su versión, arrastra la tuya. Se fija en F9.
- [ ] **Colación MySQL 8 → 5.7** y `--no-data` roto: F1 y F5.
- [ ] **CLI y web con PHP distinto:** usa el binario de la web para WP-CLI.
- [ ] **Correo local en cPanel con MX externo** y **DNS del panel que no llega a los NS
      autoritativos:** `correo-dns.md`.
- [ ] **La cortina estática bloquea `wp-cron.php` y los *loopbacks*:** excepción en F7.
- [ ] **`.well-known` fuera de redirecciones y cortina,** y copiado dos veces (F3 y F8).
- [ ] **Plugins *nulled*:** decisión D5 antes de migrar; nunca mezclada con la migración.
- [ ] **Administradores desconocidos:** se confirman con el usuario; no se borran ni se
      degradan.
- [ ] **ModSecurity del destino:** se prueba el guardado en Elementor en F10-I.
- [ ] **Permisos del docroot:** carpeta nueva 755 o grupo del servidor web (F0).

---

## Comandos de lectura útiles (cPanel)

```bash
uapi --output=json DomainInfo single_domain_data domain=<DOMINIO>      # docroot real
uapi Mysql get_server_information; uapi Mysql get_restrictions         # versión; prefijo y longitudes
uapi LangPHP php_get_vhost_versions                                    # phpversion inherit / phpversion_source
uapi SSL installed_hosts; uapi DNS parse_zone zone=<DOMINIO>; uapi Email list_mxs domain=<DOMINIO>
uapi EmailAuth validate_current_spfs domain=<DOMINIO>; uapi EmailAuth validate_current_dkims domain=<DOMINIO>
uapi Quota get_quota_info
echo | openssl s_client -connect <DOMINIO>:443 -servername <DOMINIO> 2>/dev/null | openssl x509 -noout -enddate
wp --path=<WP_PATH_ORIGEN> config list --fields=name,type                # solo nombres
wp --path=<WP_PATH_ORIGEN> user list --role=administrator --fields=ID,user_login,user_registered
wp --path=<WP_PATH_ORIGEN> db query "SELECT table_name, table_collation FROM information_schema.tables WHERE table_schema=DATABASE()"
```
