---
name: wp-migracion
description: >
  Paso de un WordPress + Elementor 4 de staging a producción y lanzamiento, en
  cualquier hosting con SSH: auditoría de solo lectura de origen y destino,
  congelación, volcado y wp-content con checksums, BD nueva, search-replace en
  orden hasta 0 restos, obra fuera del docroot y cambio con dos `mv` detrás de
  una cortina 503, .htaccess por bloques, PHP y .user.ini, correo del dominio
  (SPF, DKIM, DMARC, enrutamiento de cPanel), lanzamiento (blog_public, Yoast,
  IndexNow, Search Console) y vuelta atrás. Úsala siempre que haya que migrar o
  mover el sitio de servidor o de dominio, publicar el WordPress en el dominio del
  cliente, sustituir un sitio anterior (prototipo estático u otro WordPress),
  retirar una cortina, arreglar el correo tras cambiar de hosting o deshacer un
  cambio a producción.
---

# Migración a producción y lanzamiento

Guía de oficio, independiente de marca. La marca, los accesos (por alias) y el
estado del entorno viven en el `CLAUDE.md` y la memoria del proyecto.

**Caso de referencia (HomeMaker):** staging en SiteGround → producción en
Bluehost/cPanel, con el docroot del dominio **dentro del `public_html` de otro
sitio vivo** de la misma cuenta. Caída para el público: 0 min. Vuelta atrás
preparada para menos de 1 min (no hizo falta). El lanzamiento quedó planificado y
sin ejecutar, a la espera del DNS del correo y del OK de SEO. El método sirve para
cualquier hosting con SSH; lo específico de cPanel está marcado como tal.

## Lo primero, siempre

1. **El docroot real, nunca deducido.** En cPanel un dominio adicional suele
   vivir en una subcarpeta del `public_html` de otro sitio. Compruébalo con
   `uapi DomainInfo single_domain_data domain=<DOMINIO>` o con un archivo sonda
   pedido por HTTPS. Si el vecino tiene WordPress, **WP-CLI sube carpetas** hasta
   encontrar su `wp-load.php` o `wp-cli.yml` y escribiría en el sitio ajeno:
   `--path` en todos los `wp` y guarda de `table_prefix` en cada bloque que escribe.
2. **Plan con decisiones antes de escribir.** Auditoría de solo lectura →
   decisiones del dueño (D1…D16 del runbook) presentadas con su recomendación →
   OK explícito del usuario **por cada paso que escribe**. Plantilla del kit:
   `../wp-elementor-replica/plantillas/plan-con-decisiones.md`.
3. **Qué ve el público en el dominio real mientras dura la obra: siempre 503.**
   Cortina estática con llave o Mantenimiento de Elementor; nunca «Próximamente»
   de Elementor (200 + noindex en todo, también en `robots.txt`: desindexa un
   dominio con historial). Ver `references/lanzamiento.md`.
4. **Quién más toca el sitio.** Sesiones paralelas (SEO, entrega) y el propio
   cliente: congelación anunciada y comprobada con datos antes de exportar.
   Protocolo: `../wp-elementor-replica/references/sesiones-paralelas.md`.

## Reglas que no se negocian

- **Origen en solo lectura.** BD y `wp-content` salen por la salida estándar de
  SSH hacia el Mac; en el origen no se escribe ni un archivo. Si los servidores
  no se ven entre sí, el traslado pasa por el Mac con SHA-256 en los dos extremos.
- **Volcado de BD que conserve los IDs** (`wp db export`), nunca WXR: portada,
  kit, plantillas del Theme Builder, menús de Polylang, traducciones y adjuntos
  fijados en el child dependen de IDs. Mismo `$table_prefix` (las claves
  `<prefix>capabilities` de usermeta dependen de él).
- **Núcleo limpio** de la misma versión, verificado con checksums. No se copia
  el `wp-config.php` del origen (lleva includes del proveedor) ni su `.htaccess`.
- **La contraseña de la BD nace en el servidor** y no se imprime nunca. Sales
  nuevas. Nada de `set -x`.
- **Search-replace en cuatro formas y en orden** (ruta absoluta → dominio sin
  esquema → https normal → https escapada), con `--skip-plugins --skip-themes`,
  hasta 0 restos comprobados con `wp db search`.
- **Obra fuera del docroot, en el mismo disco; cambio con dos `mv`** detrás de la
  cortina. El sitio anterior queda respaldado entero y **no se borra nunca**.
- **`.well-known/` intocable**: fuera de la redirección https y de la cortina, y
  copiado a la obra antes del cambio. De ahí depende la renovación del SSL.
- **Tareas posteriores con la ruta final**, no en la obra: reindexado de Yoast,
  CSS de Elementor, condiciones del Theme Builder, cachés de Polylang.
- **Correo:** pruebas en seco; la única prueba real va al buzón del usuario y
  con su permiso. Los DNS se verifican contra los NS autoritativos, no contra el
  panel. Ver `references/correo-dns.md`.
- **`blog_public=1` solo en producción** y antes de retirar la cortina.
- **Ningún borrado de datos lo hace el agente.** La limpieza (F13) es del usuario,
  con permiso por cada borrado y sin `rm` encadenados. La única excepción: opciones y
  crons de plugins que no viajan, en la BD recién creada de la copia (F6), con el OK
  de ese paso; el origen conserva los suyos.
- **Secretos:** ni la llave de la cortina, ni contraseñas, ni `cat` de
  `wp-config.php`, `.env` o `*.sql` en auditorías (solo nombres de constantes).

## Fases

| Fase | Qué | Dónde |
|---|---|---|
| F0 | Auditorías de origen y destino, decisiones, estado de partida | `references/auditoria-hosting.md`, `references/runbook.md` |
| F1 | Congelar y exportar (BD por stdout, tar con integridad) | runbook |
| F2 | Traslado con SHA-256 | runbook |
| F3 | Obra: núcleo limpio, `wp-content`, `.well-known`, recursos de la cortina | runbook |
| F4 | BD nueva y `wp-config.php` | runbook |
| F5 | Importar (colación) y search-replace hasta 0 restos | runbook |
| F6 | Plugins del hosting de origen, URL de login y estado previo | runbook |
| F7 | `.htaccess` por bloques y `.user.ini` | runbook |
| F8 | Cambio de carpetas: dos `mv` | runbook |
| F9 | Tareas con la ruta final y versión de PHP | runbook |
| F10 | Verificación con la llave (A–J) | runbook |
| F11 | Mantenimiento de Elementor o lanzamiento | `references/lanzamiento.md` |
| F12 | Vuelta atrás | `references/rollback.md` |
| F13 | Limpieza (solo el usuario) | runbook |

Puertas GO/NO-GO: **A** antes de empezar, **B** antes del cambio, **C** justo
después (si falla, vuelta atrás inmediata), **D** antes de lanzar. El correo, el
OK de SEO y la decisión sobre plugins **bloquean el lanzamiento, no el cambio**.

## Referencias

- `references/runbook.md`: F0–F13 con comandos, salida esperada y «si falla».
- `references/auditoria-hosting.md`: qué auditar en origen y destino y
  checklist de riesgos (docroot compartido, `--path`, guardas, sitio vecino).
- `references/correo-dns.md`: sendmail del hosting, SPF/DKIM/DMARC, la trampa
  del Zone Editor de cPanel, enrutamiento local/remoto, prueba final.
- `references/lanzamiento.md`: cortina 503, orden de lanzamiento, Yoast dos
  veces, IndexNow, Search Console, PSI y caché de página, primeras 48 h.
- `references/rollback.md`: respaldos, marca de tiempo y R1–R6.
- `scripts/README.md`: comprobaciones curl, DNS, exportación, search-replace,
  `.htaccess` por bloques, cambio y vuelta atrás, sondas PHP.

Skills hermanas: `wp-elementor-replica` (proceso y operación segura),
`elementor4-atomico` (formularios en seco, clases globales y preview),
`wp-polylang` (cachés de idioma, `PLL_COOKIE`), `wp-seo-rendimiento` (mapa
301/410, Yoast, medición), `wp-qa`.

## Marcadores

| Marcador | Qué es |
|---|---|
| `<PREFIJO>` | prefijo del proyecto (en el caso de referencia, `hm`); nombra carpetas y bloques |
| `<SSH_ALIAS_ORIGEN>` · `<SSH_ALIAS_DESTINO>` | alias de `~/.ssh/config` (una llave por cliente) |
| `<WP_PATH_ORIGEN>` · `<WP_PATH>` | raíz de WP en origen · raíz final (docroot) en destino |
| `<DOMINIO>` · `<STAGING_URL>` · `<STAGING_DOMINIO>` | dominio final · URL de staging · la misma sin esquema |
| `<TABLE_PREFIX>` · `<DB_NAME>` · `<DB_USER>` | prefijo de tablas · BD y usuario nuevos (sin contraseña) |
| `<IP_SERVIDOR>` · `<NS_AUTORITATIVO>` | IP del destino · NS que sirven la zona (`dig +short NS <DOMINIO>`) |
| `<PARAM_LLAVE>` | parámetro de la URL que abre la cortina (la llave nunca se escribe) |
| `<LOGIN_SLUG>` · `<ADMIN_EMAIL>` | acceso propio al admin · correo de administración |
| `<IDIOMA_BASE>` · `<IDIOMA_2>` | idioma sin prefijo · idioma con `/<IDIOMA_2>/` |
