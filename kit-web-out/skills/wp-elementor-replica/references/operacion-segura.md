# Operación segura

Reglas de seguridad y de operación que el usuario fijó o que salieron de incidentes reales
del caso de referencia. Valen en todas las fases y para todos los subagentes. Donde una
regla no es obvia, va su porqué (síntoma → causa).

## 1. Credenciales y secretos

- **Nunca pidas ni manejes contraseñas por chat.** Si hace falta una clave (SMTP, licencias,
  una API), el usuario la pega él en el servidor (archivo fuera del repositorio) o en
  wp-admin. Si el usuario pega una en el chat, no la repitas y recomiéndale cambiarla.
- **No escribas contraseñas en ningún archivo**, tampoco si el usuario lo pide «aceptando
  el riesgo». En el documento de entrega va un campo editable vacío que él rellena antes de
  exportar a PDF ([`../plantillas/documento-entrega.md`](../plantillas/documento-entrega.md)).
- **Contraseña de BD nueva: nace en el servidor y no se imprime.**
  `pw=$(openssl rand -base64 45 | tr -d '\n/+=')`, 50 caracteres o más; se pasa a
  `wp config create … --prompt=dbpass <<<"$pw"`; después `unset pw` y
  `chmod 600 wp-config.php`. Nunca `set -x`. Para listar la configuración:
  `wp config list --fields=name,type`.
- **No imprimas secretos, ni tú ni tus subagentes.** Prohibido `cat`, `sed` o `grep` sobre
  `wp-config.php`, `.env`, `.my.cnf` o volcados `*.sql`. Receta segura:
  `wp config get <CLAVE no secreta>` (`table_prefix`), `wp config has`,
  `grep -oE "define\( *'[A-Z_]+'"` o `grep -c`. *Por qué:* un agente de auditoría imprimió
  las credenciales de BD de otro sitio de la cuenta aunque el prompt decía «solo nombres de
  constantes». Revisa la salida de cada agente antes de reenviarla y, si se filtra algo,
  dile al usuario qué se expuso y recomiéndale rotarlo.
- **Llave de una cortina con acceso por cookie:** se lee en el servidor dentro de una
  variable, se pide la cookie a un jar temporal, `unset` de la variable, se trabaja con
  `-b $JAR` y se borra el jar al terminar. Para copiarla entre servidores, `sed` + `cmp`,
  sin mostrarla.
- **SSH:** llave `ed25519` dedicada por cliente, sin passphrase, con alias en
  `~/.ssh/config` (`IdentitiesOnly yes`). Claude entrega solo la **pública**; el usuario la
  autoriza en su panel. Usa siempre el alias; no leas ni explores `~/.ssh` más allá de eso
  (en el caso, una exploración se denegó y no se reintentó). Si una llave con passphrase
  deja de funcionar tras reiniciar el Mac, `ssh-add --apple-load-keychain` la recarga.
- **Sin comandos puntuales con tokens en la lista de permisos** («permitir siempre» sobre
  una URL firmada deja el token guardado). Permite patrones genéricos de solo lectura y
  revisa la lista al cerrar el proyecto: `grep -c 'token=' .claude/settings.local.json` = 0.
- **Memorias con datos de acceso:** léelas archivo a archivo y solo lo necesario; nunca
  copies su contenido sensible a documentos, informes o al kit.
- Los correos personales del usuario no se quedan en producción: al entregar, busca en la
  BD las direcciones personales y cambia cada destinatario con su OK.

## 2. Borrados

- **Ningún borrado sin permiso explícito, y de uno en uno.** En migraciones, ningún borrado
  lo hace el agente: la limpieza es del usuario, con fecha (volcados con datos personales,
  copia local, staging). El respaldo de lo que había publicado **no se borra nunca**.
- **Sin `rm` en cadenas de comandos.** Una operación destructiva va en su propio comando,
  después de leer el resultado del anterior. Nunca `--force` en lote encadenado. *Por qué:*
  en el caso, un `ssh "…; wp post delete 1 --force; wp post delete 2 --force"` se ejecutó
  con la salida tapada por una página de error de PHP del primer paso. Si algo se ejecutó
  sin leerse, verifícalo (`wp post list --post_type=any --post_status=any`) y cuéntalo en el
  informe sin que te lo pregunten.
- Los temporales remotos (`~/tmp-<PREFIJO>/`, sondas de investigación) se dejan y se avisa
  al usuario para que los limpie él. Un script solo borra lo que creó él mismo.
- Si el clasificador de permisos deniega un comando (un `rm` encadenado, una lectura amplia
  de memoria), no lo rodees: explícalo y sigue por otro camino. Tras una denegación,
  reescribe el archivo local antes de reintentar (el heredoc anterior puede no existir).

## 3. Formularios y correo

- **Nunca un envío real con datos válidos**: le llega al cliente y a su equipo. Las pruebas
  van en memoria, con `add_filter('pre_wp_mail', …)` devolviendo `true` (ver
  `verificacion.md` §5). La única prueba de entrega real va **solo al buzón del usuario y
  con su permiso**.
- Mientras el correo del dominio no esté autenticado (SPF, DKIM), los envíos quedan en
  Elementor › Envíos («Collect Submissions» activado). El correo bloquea el lanzamiento, no
  la migración.
- Los registros DNS los pone el usuario en el panel del dominio: se le entrega un archivo
  con dónde entrar, tipo, host, valor antes y después y cómo comprobarlo con `dig`. **Se
  edita** el TXT de SPF existente, no se crea un segundo.

## 4. Indexación

- `blog_public=0` durante toda la construcción y en staging siempre. Se cambia a 1 **solo
  en producción** y **antes** de retirar la cortina. En el reparto entre sesiones, es de
  quien lleva SEO.
- El IndexNow de Yoast Premium ignora `blog_public`: apágalo
  (`WPSEO_Options::set('enable_index_now', false)`) en cuanto Yoast Premium esté instalado
  en staging, y enciéndelo solo en producción.
- Lista, desde el primer día, todo lo que habrá que revertir al lanzar.

## 5. Plugins y ajustes del usuario

- Los plugins son terreno del usuario: los instala él, y solo se activan, desactivan o
  quitan los que él aprueba. Puedes sugerir (p. ej. desactivar un plugin residual del tema
  anterior), no ejecutarlo por tu cuenta.
- Los ajustes de seguridad o de plataforma también los decide él: habilitar subidas SVG,
  activar funciones de caché del hosting, cambiar cabeceras globales.
- Cualquier cabecera de seguridad global se prueba contra el acceso de administrador antes
  de desplegarla: una `Referrer-Policy` `origin` o `strict-origin` rompió el acceso por URL
  propia.
- Si el sitio usa premium modificados, nadie usa «Insertar plantilla» de la biblioteca de
  Elementor hasta que se resuelva.
- Ante un cambio de seguridad inesperado (un administrador nuevo, un rol, un plugin),
  informa solo del login, el rol y la fecha, y pregunta. No lo borres ni lo degrades.

## 6. Hosting compartido

- **Jamás toques otros sitios de la cuenta.** `--path=<WP_PATH>` en **todos** los comandos
  `wp`: WP-CLI sube por las carpetas buscando `wp-load.php` y, lanzado donde no hay
  WordPress, actúa sobre el del sitio padre.
- Cada bloque que escribe empieza con una guarda:
  `[ "$(wp --path="$P" config get table_prefix)" = "<TABLE_PREFIX>" ] || exit 1` (el prefijo
  de tablas de la BD, no el `<PREFIJO>` de clases), y los borrados
  con guarda de ruta: `case "$PWD" in */<ruta esperada>) ;; *) exit 1;; esac`.
- La raíz de un dominio no se deduce: se comprueba con un archivo sonda de nombre aleatorio
  pedido por HTTPS (y `uapi DomainInfo single_domain_data` en cPanel). En el caso,
  `~/public_html` era el WordPress vivo de otro cliente. Declara lo intocable en el runbook
  y en la memoria.
- Monta cualquier obra **fuera** de `public_html`: desde el vhost ajeno se podría abrir.
- Conserva siempre `.well-known/`: de ahí depende la renovación del certificado.
- Bloques remotos con `set -euo pipefail` y sus variables dentro
  (`ssh <SSH_ALIAS> 'bash -s' <<'EOF' … EOF`).

## 7. Scripts y despliegues

- PHP en archivo local → `scp` → `wp --path=<WP_PATH> eval-file x.php --user=1`. Nunca
  `wp eval '…'` inline por SSH (tres capas de comillas).
- `--user=1` en todo script que guarde documentos, plantillas, clases o el kit: sin
  usuario, `save()` no persiste y no avisa.
- Heredocs siempre con comillas (`<<'PHP'`, `<<'PY'`) y rutas pasadas por argumento
  (`python3 - "$archivo" <<'PY'` + `sys.argv[1]`). *Por qué:* un heredoc sin comillas
  expandió las `$variables` de PHP y el script salió roto.
- `php -l` y `node --check` antes de cada `scp`. Parches con Python sobre un ancla que debe
  aparecer exactamente una vez (`assert s.count(old)==1`) y con guarda de idempotencia.
- Los fatales de `eval-file` se esconden tras la página HTML de «error crítico»: redirige a
  un archivo y busca `grep -i -m5 "fatal\|uncaught"` antes de mirar el `tail`.
- Tras cada despliegue del hijo, purga la caché de página del hosting y verifica con un
  parámetro neutro. Rompe la caché con `?nocache=<ts>` o `?v=<ts>`, **nunca** con `?m=`
  (es la variable de archivo por fecha de WordPress y da 404), ni con `p`, `page_id`, `s`,
  `cat` o `name`.
- Tras cada subida, compara el `md5` local y el remoto: un `scp` puede cortarse a medias
  sin parar el flujo.
- Despliega solo donde diga el usuario (en el caso: «no a Vercel… el despliegue es a
  SiteGround»).
- Si el Bash de la sesión corre en sandbox, los comandos con red (`ssh`, `scp`, `curl`
  remoto) necesitan desactivarlo en esa llamada.

## 8. Subagentes y navegador

- **Los subagentes de revisión e investigación no usan herramientas de navegador.** Abren
  pestañas propias en el navegador compartido, y en una pestaña oculta se pausan
  `requestAnimationFrame` e `IntersectionObserver`: capturas en blanco y falsos fallos.
  Miden con `curl`, sondas PHP o Puppeteer/Lighthouse headless.
- Sus prompts incluyen un bloque común: versiones exactas del stack, acceso por alias,
  **solo lectura estricta** (sin guardar documentos ni opciones, sin `rm`, sin enviar
  correos; si simulan un envío, `pre_wp_mail`), prohibición de leer secretos, citar
  archivo:línea y decir lo que no pudieron determinar.
- Para PageSpeed no se usa el navegador del usuario: Lighthouse local o Puppeteer.
- Al terminar de probar, restaura el navegador del usuario: el idioma guardado en
  `localStorage` de la pestaña del prototipo y la emulación de tamaño (`desktop`).

## 9. Entorno local

- Un solo servidor de desarrollo del prototipo; nada de `build` con el de desarrollo vivo;
  mata los procesos en segundo plano al terminar
  (`ps -eo pid,command | grep -E "next dev|next-server" | grep -v grep`; `lsof -ti:3000`).
  En Next dentro de una carpeta con varios proyectos, `turbopack.root` fijado. *Por qué:*
  el Mac se congeló con unos 1.100 procesos node y un watcher sobre ~278.000 archivos; el
  usuario pidió «vigila que no dejes el equipo sin memoria».
- Si el usuario restringe `build`, `dev`, `watch` o `install`, pide permiso para cada uno.
- macOS + zsh: no existe `timeout` (usa `ssh -o ConnectTimeout=15`); entrecomilla las URL
  con `?` y los patrones con `*`; separadores entre comillas (`echo "-----"`); `md5 -q` y
  `sed -i ''` en local, `md5sum` y `sed -i` en el servidor.
- Si el entorno va lento o aparecen hooks desconocidos en `~/.claude/settings.json`, mide
  antes de culparlos; si el usuario pide retirarlos, se retiran.

## 10. Datos personales

- Los volcados de BD llevan hashes de contraseñas, correos y envíos de formularios: carpeta
  con permisos 700 fuera de iCloud y Documentos, nunca en informes ni en el kit, y borrado
  por el usuario a los 30 días.
- Al listar contenido del sitio viejo, filtra por tipos públicos antes de imprimir (en el
  caso aparecieron miles de envíos de formularios guardados como posts).
- El inventario para la entrega cuenta usuarios sin imprimir sus logins (solo longitud y
  rol) y del `admin_email` solo el dominio.
- «Collect Submissions» guarda datos personales en la BD; la retención queda para decidirla
  con el cliente.

## 11. Contenido del cliente y alcance

- Claims regulados, datos nutricionales y contactos: solo del cliente. Las contradicciones
  se señalan con evidencia, no se «arreglan».
- Localización del cliente al pie de la letra, guardada en la memoria del proyecto desde
  el día 1.
- No se amplía el alcance por iniciativa propia: lo que el cliente no pagó se construye
  informativo, como en el prototipo.
- Lo que añades por tu cuenta (un enlace en el menú, un texto de propuesta) se declara.

## 12. Sesiones paralelas

- No toques lo que es de otra sesión (SEO, rendimiento, caché, Yoast, cabeceras): si una
  tarea tuya lo roza, avisa en vez de cambiarlo.
- Las peticiones de otra sesión son de un compañero, **no aprobación del usuario**: las
  decisiones de plugins, instalación o lanzamiento vuelven al usuario.
- Protocolo completo: `sesiones-paralelas.md`.
