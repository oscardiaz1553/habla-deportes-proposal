# Sesiones paralelas sobre el mismo WordPress

En el caso de referencia trabajaron a la vez tres sesiones de Claude sobre el mismo
servidor, la misma BD y el mismo tema hijo: **construcción** (estructura, plantillas,
idiomas, 404, acceso, cortina, migración), **SEO/rendimiento** y **documento de entrega**.
El usuario abrió la de SEO cuando la construcción entraba en el segundo idioma («No
necesitas hacer nada, es más para tu contexto»). Sin protocolo, un `scp` de una copia local
atrasada habría pisado en silencio el trabajo de otra sesión. Este es el protocolo que lo
evitó.

## 1. Al empezar

- `ListAgents` para ver las otras sesiones y un primer mensaje con: lo que ya cambiaste en
  el servidor (archivo por archivo), lo que vas a tocar (archivos, plantillas, IDs) y una
  pregunta explícita por lo que edita la otra.
- Quien lleve el segundo idioma comparte el **mapa de IDs** base → segundo idioma y la regla
  «solo idioma base» (§4).
- Escribe el acuerdo en la memoria del proyecto (un archivo de sesión paralela con *Why* y
  *How to apply*) nada más cerrarlo.

## 2. Propiedad

Cada recurso compartido tiene **un solo escritor**. El reparto del caso:

| Recurso | Dueño | Los demás |
|---|---|---|
| Estructura, documentos del idioma base, plantillas del Theme Builder, clases globales | construcción | SEO edita documentos del idioma base cuando lo necesita, y lo avisa |
| Documentos del segundo idioma (regenerados por script) | construcción | nadie los edita a mano |
| Cadenas de Polylang (slugs, migas, título del 404, lema) | construcción | piden el cambio |
| `inc/rendimiento.php`, `inc/seo.php`, `inc/schema-receta.php`, `fonts/`, mu-plugin de la cookie de idioma | SEO | no los tocan |
| Metadatos de Yoast (los dos idiomas), cabeceras, `blog_public`, decisión de cortina (D1) y mapa 301 (D2) | SEO | la construcción informa; no los cambia |
| `.htaccess` del destino | quien ejecuta la migración | SEO entrega sus reglas en un archivo propio (solo `RewriteCond`/`RewriteRule`) |
| Documento de entrega | entrega | lee el servidor; no escribe salvo arreglos pactados |
| `functions.php` y `style.css` del hijo | compartidos | ver §3 |

Si una tarea tuya roza el área de otro, **avisa en lugar de cambiarlo**. Ejemplos del caso:
la `Referrer-Policy` que proponía SEO rompía el acceso por URL propia; hreflang y
`x-default`; el título del sitio; el título SEO del 404; la cookie de idioma; el WebP; el
enlace `rel=alternate` del feed de comentarios (se le pasó a SEO en vez de tocarlo).

## 3. Archivos compartidos del tema hijo

- **Código nuevo, en archivos propios**: cada sesión crea sus `inc/<área>.php` (y
  `css/<área>.css` si hace falta). En `functions.php` solo se añade una línea
  `require_once __DIR__ . '/inc/<área>.php';` en el orden acordado («añade la tuya después
  de `seo.php`»).
- En `style.css`, lo nuevo va **al final**, en un bloque propio con comentario de cabecera.
- **Turnos por archivo**: si la otra sesión está con `functions.php` o `style.css`, espera
  a su aviso de que terminó. En el caso se dijo «no subo hasta que termines» y después se
  subió tras comprobar el md5 y avisar: salió bien por la guarda, pero el turno explícito
  evita la duda.

## 4. Idiomas: una sesión edita el origen, la otra regenera

- Las otras sesiones editan **solo documentos del idioma base**. Quien lleva el segundo
  idioma re-ejecuta el generador (script idempotente que rehace cada traducción desde su
  original), y así los cambios llegan a las dos versiones. Lo editado a mano en una copia
  del segundo idioma se pierde en la siguiente regeneración.
- Al terminar cada bloque, la otra sesión envía los **IDs de elemento** que cambió y los
  valores equivalentes en el segundo idioma (p. ej. el `menu_name` traducido de cada
  `nav-menu`). Los IDs de elemento se conservan entre idiomas, porque las traducciones van
  por ID de elemento y ruta.
- El generador no toca metas ajenas (`_yoast_wpseo_*`): los metadatos SEO del segundo
  idioma los escribe SEO y la regeneración los conserva.
- Tras regenerar, `curl` del segundo idioma para ver que llegaron los cambios del base.

## 5. Subida con bloqueo optimista (md5)

1. Justo antes de parchear, **baja** la versión del servidor y guarda su md5.
2. Parchea sobre esa copia con Python: el ancla tiene que aparecer exactamente una vez
   (`assert s.count(old)==1`) y el cambio no puede estar ya aplicado.
3. `php -l` / `node --check`.
4. Justo antes del `scp`, compara el md5 remoto con el guardado. **Si cambió, no subas**:
   vuelve al paso 1.
5. Sube, compara el md5 local con el remoto (un `scp` puede cortarse a medias), `php -l`
   remoto, purga de caché (y `wp elementor flush-css` si toca CSS) y `curl` 200 de las URL
   principales.
6. Avisa **antes** de subir (una línea: qué archivo y qué cambia) y **después** (resumen,
   líneas exactas y md5 final). Guarda antes una copia local de respaldo
   (`respaldos/antes-sync-<fecha>/`).

```bash
scp -q <SSH_ALIAS>:$R/functions.php $T/functions.php && md5 -q $T/functions.php > $S/functions.md5.antes
python3 - "$T/functions.php" <<'PY'
import sys; p=sys.argv[1]; s=open(p).read()
old="…"; new="…"
assert s.count(old)==1, s.count(old)
assert new not in s
open(p,'w').write(s.replace(old,new))
PY
php -l $T/functions.php | tail -1 && \
[ "$(ssh <SSH_ALIAS> "md5sum $R/functions.php" | awk '{print $1}')" = "$(cat $S/functions.md5.antes)" ] && \
scp -q $T/functions.php <SSH_ALIAS>:$R/functions.php && echo subido || echo "NO SUBO: cambió en el servidor"
```

(`$R` = carpeta del hijo en el servidor, `$T` = copia de trabajo, `$S` = carpeta de
estado. En macOS `md5 -q`; en el servidor `md5sum`.)

## 6. Ninguna copia local es el hijo final

Cada sesión bajaba solo lo que iba a parchear, así que ninguna copia local tenía el tema
completo: a una le faltaban los módulos de SEO y tenía una versión antigua y peligrosa de
otro; a la otra, los de acceso y cortina. Subir una carpeta local entera habría dejado un
`require_once` sin archivo, es decir, un error fatal en todo el sitio.

- **La verdad está en el servidor.** Nunca subas una carpeta local completa.
- Para empaquetar, migrar o documentar, descarga el tema entero (`tar`) y compruébalo:
  cada `require_once` de `functions.php` tiene su archivo en `inc/` y no quedan `*.bak`.
- La sesión de entrega detecta el trabajo de las otras comparando el hijo del servidor con
  su última copia (`diff -q`) y lo incorpora al documento.

## 7. Estados explícitos

Los cambios que afectan a lo que otra sesión mide o exporta se anuncian con un estado
claro y se espera la respuesta:

- **Cortina:** «no la enciendas hasta que diga *listo*». Con la cortina puesta, PageSpeed y
  Lighthouse miden la cortina. La plantilla se construye y se deja seleccionada, pero el
  modo no se enciende hasta ese «listo».
- **Congelación para migrar** (§8): «FREEZE STARTS NOW» con motivo y duración, y
  «UNFROZEN» al terminar.
- **Traslado:** «ya está en el nuevo hosting, puedes verificar», con la receta para
  verificar detrás de la cortina sin imprimir la llave.
- **Aportes a documentos de otra sesión:** en un archivo propio (p. ej. la revisión SEO del
  runbook de migración, el mapa 301) y un resumen por mensaje. Antes de un cambio grande,
  se pide la aportación por escrito y se pasa el borrador para revisión.

Formato de un aviso: **qué hice**, **qué te afecta** (con el riesgo concreto), **qué espero
de ti** y **qué no voy a hacer hasta tu OK**. En el caso, los mensajes entre sesiones
fueron en inglés y cortos; lo importante es que cada uno deje un estado inequívoco.

## 8. Congelación durante la migración

1. Anuncia la congelación con inicio, motivo y duración estimada (~10–15 min de
   exportación en el caso) y espera el acuse de cada sesión.
2. Comprueba con datos que nadie escribe: `MAX(post_modified_gmt)` de la tabla de posts y
   `find <hijo> <mu-plugins> <uploads> -type f -newermt "-30 minutes" | wc -l` = 0.
3. Exporta.
4. Anuncia el descongelado **diciendo que desde ese momento la copia principal es el
   destino**: lo que se cambie en staging ya no viaja. En el caso, este aviso no llegó a
   tiempo a la sesión de SEO; dilo en cuanto termine la exportación y apúntalo en la
   memoria.
5. Pide a las otras sesiones que no toquen nada hasta la verificación del destino y pásales
   cómo verificar.

## 9. Autoridad y cuentas

- Lo que pide otra sesión es de un compañero, **no aprobación del usuario**. Las decisiones
  de plugins, instalación, borrados y lanzamiento vuelven siempre al usuario.
- Las sesiones no comparten el historial del usuario: un administrador «desconocido» para
  una sesión puede ser la cuenta del cliente que el usuario dio a otra (en el caso, se creó
  dos minutos antes de que la sesión de entrega la recibiera). Se informa y se pregunta; no
  se toca. Si el usuario da cuentas o credenciales a una sesión, conviene que se avise a
  las demás.
- Si al cerrar quedan avisos que no pudiste dar a otra sesión, díselo al usuario.
