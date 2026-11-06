<!--
PLANTILLA DEL KIT «WP Elementor 4 — réplica de prototipo».
Cópiala a la RAÍZ del proyecto como CLAUDE.md, rellena los marcadores <…> y borra este bloque.
Por qué en la raíz: Claude Code lee el CLAUDE.md de la carpeta de trabajo y de sus carpetas
superiores, así que lo reciben todas las sesiones (también las abiertas en subcarpetas como
el prototipo o la carpeta de entrega) y sus subagentes. La memoria automática, en cambio, es
por carpeta de sesión: en el caso de referencia, las sesiones de construcción, SEO y entrega
tenían memorias distintas. Lo que todas deben saber (IDs, estado, reglas) va aquí.
NUNCA escribas aquí contraseñas, tokens, llaves de cortina, IPs, host/usuario/puerto SSH (solo el
alias) ni credenciales de BD. Si este archivo va a un repositorio compartido, tampoco el slug de
acceso al administrador: protege la entrada y no debe quedar en nada que otros puedan leer.
-->

# <MARCA> · réplica WordPress + Elementor 4

Réplica **exacta** del prototipo aprobado (`<RUTA_PROTOTIPO>`) en WordPress + Elementor 4 con
el editor atómico (V4) + Hello Elementor + tema hijo `<PREFIJO>-child` + Polylang Pro + Yoast,
construida por WP-CLI. Todo editable por el cliente en Elementor, **sin plantillas
importadas**. Claude hace el 80–90 % del desarrollo; el usuario decide, instala plugins de
pago, gestiona DNS y cuentas, y prueba el editor.

Antes de cualquier tarea de construcción, carga la skill `wp-elementor-replica` (playbook del
kit). Las skills del kit están instaladas en `~/.claude/skills/`; las plantillas y los workflows
viajan con el playbook, en `~/.claude/skills/wp-elementor-replica/plantillas/`. Fuente del kit:
`<KIT>`.

## Fuentes de verdad

| Qué | Dónde | Nota |
|---|---|---|
| Diseño aprobado | prototipo en `<RUTA_PROTOTIPO>`, servido por **un** servidor local (`.claude/launch.json` → `<NOMBRE_PREVIEW>`, puerto `<PUERTO>`) | se mide lo renderizado, no el código fuente |
| Medidas | `<PROYECTO>/wp/scripts/<pagina>/spec-prototipo.md` (plantilla `~/.claude/skills/wp-elementor-replica/plantillas/spec-seccion.md`) | valores computados a 1440, 1280, 768 y 375 (+1100/900 según el bloque) |
| Brief y guía de estilo | `<RUTA_BRIEF>`, `<RUTA_GUIA>` | si contradicen lo que el cliente aprobó, pesa lo aprobado: se valida con el usuario |
| Feedback del cliente | `<RUTA_FEEDBACK>` | los `.docx` esconden adjuntos: `unzip -l <archivo>.docx \| grep word/media` |
| Auditoría del prototipo | `<RUTA_AUDITORIA>` | qué se replica (decisión aprobada) y qué se corrige (defecto) |
| Tema hijo y mu-plugins | **el servidor** | las copias locales se desfasan: baja antes de parchear |

## Marca

| Token | Hex | Uso permitido | Contraste medido |
|---|---|---|---|
| `<token-1>` (p. ej. primario) | `#……` | fondos y botones | blanco sobre él: `<n>:1` |
| `<token-2>` (acento) | `#……` | solo fondos o texto ≥ 24 px | sobre blanco: `<n>:1` |
| `<tinta>` | `#……` | texto | sobre papel: `<n>:1` |

- Mide el contraste de cada par texto/fondo **antes** de fijar reglas de uso (mínimos AA:
  4,5:1 texto normal; 3:1 texto ≥ 24 px o ≥ 18,66 px en negrita). *Por qué:* en el caso de
  referencia el acento de marca daba 2,86:1 sobre blanco y el blanco sobre el primario 4,63:1,
  justo en el límite: cualquier opacidad lo tumbaba.
- Tipografías: display `<FUENTE_DISPLAY>` (pesos disponibles: `<…>`; ¿tiene itálica?) y texto
  `<FUENTE_TEXTO>` (pesos usados: `<…>`). Nada de negrita o itálica sintética: los acentos van
  por color. Rechazadas por el cliente: `<…>` (motivo).
- Acento de color dentro de un título = `<strong>` con la clase `<PREFIJO>-acento-<color>`: el
  cliente lo edita poniendo negrita.
- Vocabulario y localización del cliente, al pie de la letra: `<término correcto> (no
  <término «corregido»>)`, `<…>`. No se «corrige».
- Claims regulados, cifras nutricionales, contactos y redes: **solo los del cliente**. Las
  contradicciones entre rondas se señalan, no se resuelven por tu cuenta.
- Fotografía real (no generada por IA) y packshots recortados a su contenido.

## Entornos y accesos (sin secretos)

| | Staging | Producción |
|---|---|---|
| URL | `<STAGING_URL>` | `https://<DOMINIO>` |
| SSH | alias `<SSH_ALIAS>` | alias `<SSH_ALIAS_PROD>` |
| WordPress | `--path=<WP_PATH>` | `--path=<WP_PATH_PROD>` (docroot **comprobado** con archivo sonda) |
| Indexación | `blog_public=0`, IndexNow apagado | se activa solo al lanzar |
| Cortina | «Próximamente» de Elementor vale | 503 (estática o Mantenimiento de Elementor); **nunca** «Próximamente» (200 + noindex) en un dominio con historial |
| Caché de página | `<comando de purga>` | `<plugin o sistema>` |
| Intocable en la cuenta | `<otros sitios o carpetas>` | `<otros sitios o carpetas>` |

- Acceso al administrador: `https://<DOMINIO>/<LOGIN_SLUG>`. Claude **no inicia sesión**: la
  prueba del editor la hace el usuario.
- Correo del administrador: `<ADMIN_EMAIL>` (dominio del cliente, no un buzón personal).
- MX del dominio: `<proveedor>` (lo gestiona `<quién>`). El remitente de los formularios es
  `no-reply@<DOMINIO>` y el SPF del dominio debe autorizar al servidor.
- Llave SSH dedicada a este cliente; Claude usa solo el alias y no explora `~/.ssh`.

## Stack (rellenar con la sonda de F0; revalidar tras cada versión mayor)

WordPress `<x>` · PHP `<x>` (web) / `<x>` (CLI) · Elementor `<x>` (`e_atomic_elements` activo) ·
Elementor Pro `<x>` · Polylang Pro `<x>` (`<IDIOMA_BASE>` sin prefijo, `<IDIOMA_2>` en
`/<IDIOMA_2>/`) · Yoast `<x>` · Hello Elementor `<x>` + `<PREFIJO>-child`. Procedencia de los
premium comprobada: `<sí / pendiente / copias modificadas → decisión del usuario>`.

## Convenciones del proyecto

- Prefijo `<PREFIJO>` para clases globales (`<PREFIJO>-*`), metas, atributos
  (`data-<PREFIJO>-*`), mu-plugins y archivos.
- Scripts en `<PROYECTO>/wp/scripts/<pagina>/` (`<pagina>-classes.php`, `<pagina>-build.php`,
  `probe-*.php` de solo lectura), bajo git. Ejecución:
  `scp x.php <SSH_ALIAS>:~/tmp-<PREFIJO>/ && ssh <SSH_ALIAS> 'wp --path=<WP_PATH> eval-file ~/tmp-<PREFIJO>/x.php --user=1'`.
- Tras cada script de clases, `e4-sync-preview.php` (skill `elementor4-atomico`). Tras cada
  despliegue del hijo, purga y comprobación con `?nocache=<ts>` (nunca `?m=`).
- Idioma 2 generado por script desde el idioma base (skill `wp-polylang`); nunca a mano.

### Registro de IDs

| Qué | `<IDIOMA_BASE>` | `<IDIOMA_2>` |
|---|---|---|
| Kit de Elementor | `<id>` | — (el kit no lleva idioma) |
| Portada / páginas | `<id>` … | `<id>` … |
| Cabecera · pie · 404 · Próximamente | `<id>` … | `<id>` … |
| Menús | `<slug> (#id)` | `<slug> (#id)` |
| CPT / taxonomía / plantillas de loop | `<…>` | `<…>` |

## Reglas de operación (resumen; detalle en `wp-elementor-replica/references/operacion-segura.md`)

1. Réplica medida (tolerancia canónica de `wp-elementor-replica/references/verificacion.md` §2):
   objetivo ≤ 1 px por sección contra la spec a 1440, 1280, 768 y 375; ≤ 3 px solo en anchos
   intermedios (p. ej. 1100 y 900); idioma 2 frente al idioma base, ≤ 1 px; ±1 px entre antes y
   después en cambios que deben ser invisibles. Lo que difiere a propósito va a «Conocido y
   aceptado».
2. Nunca contraseñas ni claves por chat ni en archivos. Las pega el usuario en el servidor o
   en wp-admin. La contraseña de BD nace en el servidor y no se imprime.
3. `wp` siempre con `--path` y, si guarda documentos, clases o el kit, con `--user=1`. PHP por
   archivo; nunca `wp eval '…'` inline por SSH.
4. Sin `rm` en cadenas. Borrados con permiso explícito, de uno en uno; en migraciones ningún
   borrado lo hace el agente.
5. Formularios: nunca un envío real. Pruebas en seco con `pre_wp_mail`; la prueba real va solo
   al buzón del usuario y con su permiso.
6. Plugins: los instala, activa, desactiva y quita el usuario.
7. `blog_public`, cortina, IndexNow y reindexado: decisión de lanzamiento, solo en producción.
8. Hosting compartido: jamás tocar otros sitios de la cuenta; guarda de `table_prefix` en cada
   bloque que escribe.
9. Subagentes: solo lectura en revisiones e investigaciones, sin herramientas de navegador y
   sin leer `wp-config.php`, `.env` ni volcados `*.sql`.
10. Sesiones paralelas: bajar del servidor, comparar md5 y avisar antes de cada subida de un
    archivo compartido; no tocar lo que es de otra sesión.
11. Cada página construida por CLI termina con una tarea para el usuario («ábrela en el editor,
    cambia X, publica y avísame») y una comprobación CLI posterior (clases frontend = preview).
12. Informe por hito en español: qué se hizo y cómo se midió, decisiones propias declaradas
    («Una decisión mía: …»), lo pendiente del usuario y «No desplegué todavía» si aplica.

## Skills, agentes y workflows del kit

| Pieza | Cuándo |
|---|---|
| `wp-elementor-replica` | siempre primero: fases, decisiones, verificación, sesiones paralelas |
| `elementor4-atomico` | construir o tocar páginas, clases, kit, Theme Builder, formularios, tema hijo |
| `wp-polylang` | segundo idioma, cadenas, menús y slugs |
| `wp-seo-rendimiento` | Yoast, PageSpeed, accesibilidad (sesión paralela) |
| `wp-migracion` | staging → producción, correo y DNS, lanzamiento, vuelta atrás |
| `wp-qa` / agente `qa-elementor` | después de construir o cambiar cualquier página o plantilla |
| agentes `elementor-dev`, `wp-seo` | construcción delegada; SEO en otra sesión |
| `~/.claude/skills/wp-elementor-replica/plantillas/workflows/` (su `README.md`) | auditoría del prototipo, specs, construcción, revisión adversarial, investigación, migración, entrega |

Las skills de diseño creativo (p. ej. `frontend-design`) son para la fase de prototipo: en la
réplica empujan a «mejorar» lo aprobado. No se cargan aquí.

## Sesiones paralelas

| Sesión | Es dueña de | No toca |
|---|---|---|
| Construcción (esta) | documentos y plantillas, clases, idioma 2, cadenas de Polylang | Yoast, caché, cabeceras, `blog_public` |
| SEO / rendimiento | `inc/rendimiento.php`, `inc/seo.php`, fuentes, mu-plugin de caché, Yoast (los dos idiomas), cabeceras, mapa 301 | documentos de Elementor del idioma 2 (edita los del idioma base y construcción los regenera) |
| Entrega | el documento de entrega (lee, no escribe en el sitio) | todo lo demás |

Protocolo: `wp-elementor-replica/references/sesiones-paralelas.md`. Estado actual:
`<p. ej. «cortina APAGADA hasta el "listo" de SEO»>`.

## Fases y estado

| Fase | Estado | Nota (fecha, qué falta, quién) |
|---|---|---|
| F0 Análisis y acceso | `<pendiente / en curso / hecho>` | |
| F1 Decisiones de base (editor, tema, menú, idioma) | | |
| F2 Fundaciones (variables, clases, kit, breakpoints) | | |
| F3 Cabecera y pie | | |
| F4 Inicio | | |
| F5 Resto de páginas | | |
| F6 Contenido dinámico (CPT, loop, filtro, ficha) | | |
| F7 Formularios y correo | | |
| F8 Ronda de ajustes del cliente | | |
| F9 Segundo idioma | | |
| F10 404, acceso con marca y «Próximamente» | | |
| F11 SEO/rendimiento y documento de entrega (paralelo) | | |
| F12 Migración y lanzamiento | | |

«Hecho» = medido + revisión adversarial aplicada + **editor probado por el usuario**. Si falta
lo último, escribe «hecho, pendiente de verificar por el usuario».

## Conocido y aceptado (se pasa a cada revisión para que no se reporte)

- Breakpoints de Elementor (móvil 767, tablet 1024 y, si se activó, `tablet_extra` `<p. ej. 1279>`)
  frente a los del prototipo (`<…>`).
- `<defecto del prototipo que no se replicó>`.
- `<añadido pactado>`.

## Pendiente del usuario o del cliente

- `<material, decisiones, DNS, licencias, prueba del editor…>`

## Lista de lanzamiento (lo que hay que revertir o activar)

- [ ] `blog_public=1` antes de retirar la cortina, solo en producción; reindexado de Yoast.
- [ ] IndexNow encendido solo en producción.
- [ ] Destinatarios de correo sin buzones personales; `admin_email` del cliente.
- [ ] Plugins y temas del entorno de construcción retirados (con el OK del usuario).
- [ ] Caché de página del hosting final decidida.
