# Fases F0–F12: del prototipo al sitio lanzado

Orden real del caso de referencia (HomeMaker). Cada fase trae objetivo, entradas, pasos,
criterio de «hecho», trampas conocidas (síntoma → causa → regla) y qué se le pregunta al
usuario. El detalle técnico vive en las skills de oficio y aquí solo se enlaza.

- F3 a F10 comparten el ciclo por página de `SKILL.md` (spec → clases → construcción → hijo
  → despliegue → medición → revisión adversarial → memoria → informe).
- F11 corre **en paralelo**, en otras sesiones, desde que la construcción entra en F9.
- El runbook de la skill `wp-migracion` numera sus propias fases (F0–F13) dentro de la F12
  de este playbook. Para no confundirlas, cítalas como «runbook F5».
- Marcadores: `<PREFIJO>` (prefijo de clases, metas y archivos; en el caso, `hm`),
  `<WP_PATH>`, `<SSH_ALIAS>`, `<DOMINIO>`, `<STAGING_URL>`, `<ADMIN_EMAIL>`, `<LOGIN_SLUG>`,
  `<IDIOMA_BASE>` / `<IDIOMA_2>` (en el caso, `es` sin prefijo y `en` en `/en/`).

## Transversal a todas las fases

- **Memoria viva.** Tras cada hito, actualiza el archivo de proyecto de la memoria (estado,
  IDs de páginas, plantillas, menús y adjuntos, decisiones, «pendiente de verificar por el
  usuario») y el archivo de trampas técnicas, con la versión exacta de los plugins. Si un
  diagnóstico que escribiste resulta falso, corrígelo diciendo que fue un error. Formato:
  [`../plantillas/memorias-semilla.md`](../plantillas/memorias-semilla.md).
- **Git desde el día 1** para el prototipo, el tema hijo y los scripts. Commit cuando el
  usuario lo pida o lo tenga pactado («commitea y despliega»), con mensaje en español: qué,
  por qué y qué queda pendiente y sin tocar.
- **Informe por hito** con la estructura de `verificacion.md` §7.
- **Workflows.** Si la sesión los tiene, úsalos para lo sustancial: investigación de solo
  lectura, auditorías y revisión adversarial. Plantillas en `../plantillas/workflows/`; qué
  workflow va en cada fase, en su [README](../plantillas/workflows/README.md).

---

## F0 · Análisis y acceso

**Objetivo.** Saber qué hay de verdad (prototipo, servidor, plugins), para qué es el servidor
y tener un acceso seguro, antes de proponer arquitectura.

**Entradas.** El prototipo (repo o carpeta), el brief y la guía de estilo, el material del
cliente (`.docx`, capturas), los datos de acceso que configura el usuario (sin
contraseñas), la lista de plugins de pago y sus licencias, el dominio final, el hosting de
producción y quién gestiona el DNS y el correo del dominio. Guía:
[`../plantillas/kickoff-checklist.md`](../plantillas/kickoff-checklist.md).

**Pasos.**
1. Contexto: `CLAUDE.md` y memoria del proyecto. Si es nuevo, créalos desde
   `../plantillas/CLAUDE.md` y `../plantillas/memorias-semilla.md`. Guarda desde el primer
   día las reglas de marca y de localización del cliente (en el caso, vocabulario regional
   que no se «corrige»: «china», no «naranja»).
2. Prototipo versionado y vivo: `git init` si falta y un solo servidor de desarrollo
   (`.claude/launch.json` + `preview_start`). En Next.js dentro de una carpeta con varios
   proyectos, fija `turbopack: { root: path.join(__dirname) }`.
3. Acceso SSH: llave `ed25519` dedicada al cliente, sin passphrase; entrega **solo la
   pública** y dile dónde se importa en su panel; alias en `~/.ssh/config` con
   `IdentitiesOnly yes`; prueba con
   `ssh -o BatchMode=yes -o ConnectTimeout=20 <SSH_ALIAS> 'echo CONECTADO; whoami'`. Un
   `Permission denied (publickey)` antes de que la autorice significa host vivo (esperado).
4. Inventario del WordPress, de solo lectura: `wp core version`;
   `wp option get blogname|siteurl|blogdescription|blog_public|permalink_structure|timezone_string`;
   `wp theme list --status=active`; `wp plugin list --status=active`;
   `wp post list --post_type=page`; `wp user list --fields=ID,user_login,roles`;
   versión de PHP; `curl -sI <STAGING_URL>` (cabeceras de caché).
5. Sonda de Elementor como primer script: `experiments->get_features()` +
   `is_feature_active()` y el listado de `wp-content/plugins/elementor/modules/`. Con
   `e_atomic_elements` activo, el sistema de diseño son variables y clases globales, no los
   colores y tipografías globales del kit.
6. Procedencia de los plugins premium: `wp plugin verify-checksums --all` (solo cubre los de
   wordpress.org) y, en cada premium, las primeras ~40 líneas del archivo principal buscando
   `update_option(.*license`, `pre_http_request` y dominios ajenos. Si hay copias
   modificadas, es una decisión del usuario (`decisiones.md`); mientras tanto nadie usa
   «Insertar plantilla» de la biblioteca de Elementor.
7. Auditoría del prototipo en paralelo (agentes por dimensión + verificador) y medición
   propia en el navegador: `prototipo-a-spec.md`.
8. Ajustes base: `blog_public=0`, zona horaria del cliente, permalinks `/%postname%/`. El
   relleno de WordPress se borra con permiso y con un comando propio. Apunta en la lista de
   lanzamiento todo lo que habrá que revertir.
9. Memoria: entorno (staging o producción, `--path`, alias), decisiones pendientes y un
   archivo vivo de trampas técnicas.

**Hecho cuando.**
- El primer informe tiene la tabla «Stack / Contenido / Usuarios / Extras / URL y cabeceras»
  y la pregunta que puede cambiar el plan.
- El acceso funciona en `BatchMode`.
- La auditoría del prototipo está entregada con su procedencia (propuestos, confirmados y
  sin verificar).
- Las decisiones de F1 están redactadas y `blog_public` vale 0.

**Trampas.**
- *Kit clásico escrito en un sitio V4.* Se escribieron colores y tipografías V3 dando por
  hecho Elementor 3, y el sitio tenía el editor atómico activo. Causa: suponer la versión.
  Regla: la sonda de versiones y experimentos va primero.
- *`wp eval '…'` inline por SSH* devuelve `Parse error … eval()'d code` y una página HTML de
  error. Causa: tres capas de comillas (shell local, SSH, PHP). Regla: PHP en archivo.
- *Borrados sin leer.* Un `ssh "a; b; wp post delete 1 --force; …"` ejecutó los borrados
  con la salida tapada por un error PHP del primer paso. Regla: cada operación destructiva
  va en su propio comando, después de leer el resultado del anterior. Si ya pasó,
  compruébalo y cuéntalo en el informe sin que te pregunten.
- *Auditoría de código que aprueba un fallo de ejecución.* La auditoría automática dio por
  bueno un revelado que dejaba 4 de 13 bloques en `opacity:0` con scroll rápido. Regla:
  audita también en el navegador.
- *Plugins premium modificados descubiertos al migrar.* Regla: se comprueban en F0.
- *El Mac se congeló.* Next tomó como raíz una carpeta con 9 proyectos y vigilaba ~278.000
  archivos, con varios `npm run dev` vivos. Regla: raíz fijada, un solo servidor, nada de
  `build` con el de desarrollo vivo, y matar los procesos en segundo plano al terminar.
- *La guía de estilo escrita contradice lo que el cliente aprobó* (tipografías, hex). Las
  referencias visuales que admira el cliente pesan más; valídalo con el usuario.
- *Denegaciones del clasificador de permisos* con `rm` encadenados o lecturas amplias de
  memorias con datos de acceso. Lee la memoria archivo a archivo; no rodees una
  denegación: explícala.

**Qué preguntar.**
- ¿Para qué es este servidor? ¿Réplica exacta o plantillas? (En el caso: réplica exacta,
  sin plantillas importadas, todo editable.)
- ¿Qué plugins de pago instalas tú y con qué licencias?
- Dominio final, hosting de producción y quién gestiona el DNS y el correo del dominio.
- ¿Podrás abrir en el editor de Elementor cada página cuando te lo pida?
- ¿Hay funcionalidades con coste o datos grandes pendientes? (En el caso: un localizador
  con miles de puntos y una API de mapas sin cotizar.)

---

## F1 · Decisiones de base: editor, tema, menú e idioma

**Objetivo.** Cerrar lo que condiciona toda la construcción. Cambiarlo después es rehacer.

**Entradas.** Inventario y sondas de F0; auditoría del prototipo.

**Pasos.**
1. Evidencia para V4 frente a V3: vuelca `get_props_schema()` de `e-heading`,
   `e-paragraph`, `e-button`, `e-image`, `e-flexbox` y `e-div-block`, y
   `Style_Schema::get()` (73 propiedades CSS en 4.3). Un prototipo hecho con utilidades
   (Tailwind) mapea casi 1:1 con clases globales V4, y el validador de V4 dice qué prop
   exacta falla. V3 obliga a escribir de memoria miles de claves por widget y breakpoint.
2. Tema: comprueba si V4 exige Hello (`grep -rl 'hello-elementor'` en `atomic-widgets` y
   `atomic-opt-in` da 0: no lo exige). Recomienda Hello + hijo para tener una sola fuente
   de verdad de estilos, y di el coste (dejar de usar un tema de pago, si lo había).
3. Hello como lienzo: `wp option update hello_elementor_settings_<clave> 'true'` para
   `description_meta_tag`, `header_footer`, `page_title` y `hello_theme`. Evita la meta
   description duplicada, el H1 doble, la cabecera y el pie propios y `theme.css`. Conserva
   `skip_link` y el reset (`hello-elementor`).
4. Tema hijo mínimo: encolado con `filemtime()` y dependencia `['hello-elementor']`, y
   retirada en `wp_head` (prioridad 0) de los filtros de terceros que reescriben `ver`.
5. Idioma y URL, **antes de maquetar**: plugin (el que esté instalado), idioma base sin
   prefijo, segundo idioma en `/<IDIOMA_2>/` y plantillas del Theme Builder por idioma.
   Skill `wp-polylang`.
6. Slugs: los del prototipo, aunque estén en otro idioma, para que siga valiendo su mapa 301.
7. Menú clásico (`nav-menu` de Pro, editable en Apariencia › Menús).
8. Tras cambiar de tema, comprueba que variables, clases y kit sobrevivieron (cuentas por
   tipo, kit activo, tema activo).

**Hecho cuando.** Las decisiones están confirmadas y escritas en la memoria; Hello + hijo
activos; el `curl` del HTML solo trae `hello-elementor-css` y la hoja del hijo.

**Trampas.**
- *Recomendar V3 «por el ecosistema de plantillas» sin sondear.* Se revirtió con
  evidencia. Si cambias de recomendación, di qué argumento cayó.
- *El `?ver=` del CSS del hijo no cambiaba* y se diagnosticó como caché de PHP (y así quedó
  en la memoria). Era el filtro `chld_thm_cfg_version` de Child Theme Configurator, un
  plugin que servía al tema anterior. Regla: no supongas caché, instrumenta (lista los
  callbacks de `style_loader_src` con `ReflectionFunction`) y corrige la memoria.
- *Plugins residuales del tema anterior.* Sugiere desactivarlos; no lo hagas tú.

**Qué preguntar.** Editor V4 o V3 (con la recomendación); tema, con su coste; menú clásico;
plugin de idioma, esquema de URL y plantillas por idioma; slugs. Ver `decisiones.md`.

---

## F2 · Fundaciones: kit, variables, clases globales y breakpoints

**Objetivo.** El sistema de diseño dentro de Elementor, exacto y editable por el cliente.

**Entradas.** Tokens del prototipo (colores, fuentes, medidas), escalas tipográficas
computadas y la matriz de contraste de la paleta.

**Pasos.**
1. Variables V4 de color, fuente y medida, con etiquetas en kebab-case; script de
   crear-o-actualizar indexado por etiqueta. Los mismos colores y fuentes como globales del
   kit (V3), escribiendo la meta del kit, para los widgets V3 y para el cliente.
2. Matriz de contraste texto/fondo convertida en reglas de uso: sin opacidad en texto
   pequeño sobre el color primario, acento de marca solo como fondo o en texto grande,
   estado activo con subrayado y no solo con color.
3. Clases globales al estilo Tailwind: cada propiedad la controla una sola familia de
   clases (tipografía sin margen ni color; `<PREFIJO>-tono-*`, `<PREFIJO>-mt-*`,
   `<PREFIJO>-mw-*`); contenedores con `padding` y `width` explícitos; modificadores antes
   que su base en el orden. Valida con el parser antes de guardar y prueba primero con una
   sola clase.
4. Sincroniza el orden *preview* al final de cada script de clases y compruébalo por REST.
5. Breakpoints: si el prototipo colapsa el menú en `xl` (1280), activa `tablet_extra` =
   1279 escribiendo la meta del kit y recompila las hojas.
6. Base del hijo: las reglas que compiten con estilos atómicos empiezan por `.elementor `
   (también dentro de `@media`); `html{scroll-behavior:auto}` si el prototipo no anima el
   scroll; `<main id="content">` en los hooks de Elementor; clase `<PREFIJO>-js` en `<html>`
   fuera del preview del editor, para ocultar solo cuando hay JS.
7. Helpers compartidos de construcción con IDs de elemento deterministas.

**Hecho cuando.** El script imprime, leídas en un proceso nuevo, las variables por tipo, las
clases con sus variantes, `frontend N · preview N · iguales` y los breakpoints activos con
sus valores. En el caso: 21 variables al arrancar; más de 240 clases al final del proyecto.

**Trampas** (detalle en
[gotchas-cli.md](../../elementor4-atomico/references/gotchas-cli.md) y
[modelo-de-datos.md](../../elementor4-atomico/references/modelo-de-datos.md)):
- `$kit->save()` desde CLI descarta claves: se escribe `_elementor_page_settings` y se
  recompila.
- `Global_Classes_Repository::put()` solo escribe el contexto *frontend*; el editor, al
  publicar, borra las clases que no ve en *preview*. Sincroniza siempre.
- Si dos clases escriben la misma propiedad, gana la primera del `order`: una clase de texto
  con `margin:0` anuló las utilidades de margen.
- Estilos base atómicos: `padding:10px`, `width:100%`, `min-width:30px`, `::before`
  absoluto y `e-grid` con dos filas predefinidas.
- Las etiquetas de variable no admiten espacios y `create()` falla con las existentes.
- El CSS del kit no existe hasta que una página lo usa: verifica leyendo la meta, no el CSS.
- Sin `--user=1`, `save()` no guarda y no avisa.
- La tipografía `primary` del kit, si es la display, se propaga a los widgets V3 que se
  añadan después (formularios): valora neutralizarla.

**Qué preguntar.** Los colores de marca que no pasan AA: decisión del cliente, con captura
comparativa.

---

## F3 · Cabecera y pie

**Objetivo.** Plantillas «Cabecera» y «Pie» del Theme Builder idénticas al prototipo, con
menús que el cliente edita en Apariencia › Menús.

**Entradas.** Spec de cabecera y pie en los anchos de `verificacion.md` §2 (1440, 1280, 768 y
375) y además a 1100 y 900; logos del prototipo y estructura de menús.

**Pasos.**
1. Páginas vacías y menús con un script idempotente: por cada slug, `get_page_by_path()`;
   si no existe, `wp_insert_post` + `_elementor_edit_mode=builder` +
   `_wp_page_template=elementor_header_footer` + idioma; portada estática; menús con nombres
   que al sanearse den exactamente su slug.
2. Clases de la cabecera y construcción de la plantilla (logo `e-image`, `nav-menu`,
   selector de idioma, botón), con la condición en formato array:
   `[['type'=>'include','name'=>'general']]`.
3. `nav-menu` con `dropdown = tablet_extra`, estilizado desde el hijo. Desde CLI no se ven
   sus controles de estilo; que tenga o no pestaña Estilo en el editor V4 quedó sin
   confirmar.
4. Extras solo en el panel móvil del menú (botón de contacto, idioma): filtro
   `wp_nav_menu_items` cuando `$args->menu_type === 'dropdown'`.
5. Selector de idioma:
   `pll_the_languages(['raw'=>1,'hide_if_empty'=>0,'hide_if_no_translation'=>0])`,
   pintado como enlaces `<a hreflang lang aria-current>` dentro de `<nav aria-label>`.
6. Pie: `e-grid` con `grid-template-rows:auto`, columnas como `nav-menu` verticales y el año
   por shortcode dentro de un texto editable.
7. Mide en 1440, 1280, 768 y 375, y además en 1100 y 900, contra el prototipo; prueba la
   hamburguesa (a 1279 ya colapsa, a 1280 no) y el submenú.

**Hecho cuando.** En tolerancia (`verificacion.md` §2): ≤1 px en 1440, 1280, 768 y 375 y ≤3 px
en los intermedios 1100 y 900 (en el caso cuadró al píxel: cabecera de 100 px, 88 px en
móvil); submenú centrado a su distancia, panel móvil con sus extras y menús editables.

**Trampas** (detalle en
[theme-builder.md](../../elementor4-atomico/references/theme-builder.md)):
- El `nav-menu` de Pro colapsa en `mobile`, `mobile_extra`, `tablet` y `tablet_extra`, no en
  `laptop`: usa `tablet_extra` = 1279.
- WordPress rehace el slug con el nombre del menú en cada «Guardar menú» (`pie-%c2%b7-…`) y
  el widget, que pide el menú por slug, se queda sin él. Nombres que saneen a su slug;
  renombrar con `wp term update nav_menu <id> --name=…`; y en la entrega, «no renombrar los
  menús».
- Al asignar menús con Polylang, `$args->menu` pasa de slug a ID: los filtros de menú deben
  aceptar ID, slug u objeto.
- Tras emular móvil, SmartMenus queda en modo táctil y el hover no abre el submenú: es un
  artefacto de la prueba, no un fallo. Recarga en una pestaña que no haya emulado.
- El color de enlaces del kit pisa componentes del hijo.
- Un enlace que añadiste por tu cuenta al menú o al pie hay que declararlo. En el caso, uno
  así explicó más tarde 33 px de diferencia en otra página.

**Qué preguntar.** Redes sociales sin URL (no se ponen); cualquier añadido propio al menú.

---

## F4 · Inicio

**Objetivo.** La portada réplica del prototipo, con sus comportamientos (carruseles,
entradas animadas, vídeo), medida y editable.

**Entradas.** `spec-prototipo.md` de la portada; medios del prototipo.

**Pasos.**
1. Medios: compara dimensiones y `md5` de lo que haya en la Biblioteca con el prototipo;
   optimiza en local (p. ej. `cwebp -quiet -resize 600 0 -q 82 -alpha_q 90`); sube con un
   manifiesto `clave|archivo|título|alt` → `wp media import … --porcelain` → JSON
   `clave → ID` que leen los scripts (nunca IDs escritos a mano).
2. Clases de la página → construcción (títulos de navegador legibles para el cliente).
3. Hijo: solo lo que V4 no expresa (pseudoelementos, hovers entre elementos, máscaras de
   iconos, animaciones), por atributos `data-<PREFIJO>-*`.
4. Entradas animadas robustas: se ven sin JS, revelan lo que ya está en pantalla sin
   esperar al JS diferido, no dependen de un único disparo del observer y respetan
   `prefers-reduced-motion`.
5. Mide, lanza la revisión adversarial y pide al usuario que abra la página en el editor.

**Hecho cuando.** En tolerancia por sección (`verificacion.md` §2) a 1440, 1280, 768 y 375;
0 bloques sin revelar tras un scroll
rápido; sin scroll horizontal; se ve con JS desactivado; la prueba del editor queda
pendiente de forma explícita hasta que el usuario la confirme.

**Trampas.**
- *Revelados del prototipo que se quedan en `opacity:0`* (`threshold: 0.15` + `unobserve`
  al primer disparo, y sin estado visible sin JS). No se replican: se corrigen y se dice.
- `e-heading` y `e-paragraph` sanean el HTML y se pierden las clases de los `<span>`. El
  acento de color va como `<strong>` con clase de acento: el cliente lo edita poniendo
  negrita.
- `e-background-video` sale con `z-index:0` y el navegador lo promueve a otra capa
  (capturas en blanco al hacer scroll): `z-index:-1` dentro de un contenedor con
  `isolation:isolate`, póster como fondo CSS y textos «Play/Pause» traducidos.
- La fuente de una imagen o un vídeo acepta id **o** url, no las dos; los hijos creados con
  `Element_Builder` salen sin `id`.
- `fetchpriority`: WordPress y Elementor ya lo ponen a la primera imagen grande; no lo
  dupliques a mano.
- El logo subido al WordPress no era el del prototipo: compara antes de usar.
- Vídeo o foto generados por IA que «se ven falsos»: material real (de banco o del cliente),
  y revisa a zoom el texto de envases y rótulos.

**Qué preguntar.** El origen de cada foto o vídeo si hay duda. Los defectos del prototipo
que corriges no se preguntan: se informan.

---

## F5 · Resto de páginas

**Objetivo.** Cada página con el mismo ciclo y el mismo nivel de verificación que el Inicio.

**Entradas.** Spec por página; clases existentes (vuélcalas antes para no duplicar).

**Pasos.** El ciclo por página. Primero las estáticas; las que tienen contenido dinámico o
formularios, cuando su investigación esté hecha (F6, F7). Mientras corre la revisión de una
página, avanza con la siguiente (el usuario lo pidió así en el caso).

**Hecho cuando.** Como en F4, página a página, y el usuario tiene la lista de páginas por
abrir en el editor.

**Trampas.**
- *«El sticky no funciona»* solo con sesión iniciada: la barra de administración. Prueba
  con y sin sesión, y en móvil anula también `top`.
- *Anclas desde otra página que tardan unos 2 s*: Elementor fuerza
  `html{scroll-behavior:smooth}`. Fija `auto` en el hijo y define `scroll-margin-top` por
  breakpoint según la cabecera fija (en el caso, 172 px en escritorio y 160 px en móvil).
- Los elementos atómicos no aceptan `dl`, `ul` ni `ol`: la semántica que se pierda,
  anótala.
- Elementor descarta `order: 0` en las variantes responsive.
- `text-align` en clases globales solo acepta valores lógicos.
- `e-grid`: declara la variante móvil aunque la de tablet ya tenga una columna.
- Iconos por `::before` descentrados: el `::before` de `.e-con` es absoluto; usa
  `inset:0; margin:auto`.
- En el editor, las cajas de icono vacías se ven como zonas de 120 px, y los estados que el
  JS oculta en la web tienen que verse: cuélgalos de `html.<PREFIJO>-js` o de
  `body:not(.elementor-editor-active)`.
- Antes de corregir una diferencia de medida, aísla la sección que difiere y busca en la
  memoria si fue una decisión.

**Qué preguntar.** Contrastes que no pasan y claims de producto: se replican y se listan
para el cliente.

---

## F6 · Contenido dinámico: CPT, Loop Grid, filtro y ficha

**Objetivo.** El contenido repetible (en el caso, recetas) como tipo de contenido que el
cliente edita, con listado, filtro por categoría y ficha iguales al prototipo.

**Entradas.** Los datos del prototipo (su archivo de contenido) y la spec del listado, la
tarjeta y la ficha.

**Pasos.**
1. Investigación de solo lectura en paralelo antes de construir: Loop Grid + filtro por
   taxonomía, etiquetas dinámicas atómicas y Theme Builder con CPT e idiomas. Mientras,
   adelanta lo independiente (extraer datos, subir fotos, medir).
2. Decide **al registrar**: taxonomía con guion bajo (`categoria_receta`, no con guiones) e
   IDs de 7 caracteres `[a-z0-9]` para el Loop Grid y el filtro. Si no,
   `?e-filter-<id>-<taxonomía>=<slug>` no prefiltra al cargar, y cambiarlo después obliga a
   migrar términos.
3. Extrae los datos del prototipo en vez de transcribirlos (Node ejecuta TypeScript →
   JSON), con claves estables para categorías y niveles. Importador idempotente por slug.
4. Metacampos con revisiones y saneado; listas en un formato que el cliente pueda editar
   (una línea por elemento).
5. Plantillas de tarjeta y ficha por CLI en «modo editor» (las etiquetas dinámicas V4 no
   validan si Elementor cree que está en frontend); Loop Grid y filtro (widgets V3)
   envueltos en `e-flexbox` con la clase global.
6. Query ID con guarda de reentrada, orden de los botones del filtro, idioma en el refresco
   AJAX y enlaces cortos heredados (`?cat=<clave>`) redirigidos al filtro.

**Hecho cuando.** El `curl` de la URL filtrada trae solo las tarjetas de esa categoría; la
ficha carga en un tiempo normal; el usuario edita un elemento del CPT desde el panel.

**Trampas** (detalle en
[contenido-dinamico.md](../../elementor4-atomico/references/contenido-dinamico.md)):
- Un callback de `elementor/query/<id>` que hace su propia consulta se llama a sí mismo sin
  fin: la ficha con «relacionadas» colgó la página.
- Las clases globales puestas en un widget V3 no llegan al CSS de la página si ningún
  elemento V4 del documento las usa.
- `Loop::save()` de Pro devuelve `null` aunque guarde: comprueba con `false === $r`.
- Recorrer `_elementor_data` con `$s = &$el['settings']` crea la clave con `null` y
  Elementor revienta al guardar.
- La petición `refresh-loop` del filtro no lleva idioma.
- La accesibilidad del filtro y del Loop Grid de Pro hay que repararla en JS, y el filtro no
  cancela la petición anterior.

**Qué preguntar.** El formato de los campos (ingredientes, pasos), la relación entre
contenidos (p. ej. recetas por producto) y qué hacer con el contenido que falta: en el caso,
retirar lo vacío en vez de esperarlo.

---

## F7 · Formularios y correo

**Objetivo.** Formularios iguales al prototipo que llegan al buzón del cliente, con antispam
sin terceros, y ningún correo real enviado al probar.

**Entradas.** Campos, destinatarios (los da el cliente), remitente, textos de éxito y error.

**Pasos.**
1. Investigación: `e-form` atómico frente al widget Formulario de Pro.
2. Widget Formulario de Pro (V3) dentro de un `e-flexbox`, estilizado desde el hijo por
   `_css_classes`.
3. Antispam: campo nativo Honeypot + campo oculto cuyo `custom_id` termina en `_ts` (uno
   distinto por formulario de la misma página: en el caso, `c_ts` y `d_ts`), que un JS
   rellena al enviar con los **milisegundos desde que se abrió la página**; el hook
   `elementor_pro/forms/validation` rechaza menos de 3000 ms y quita el campo del correo y
   de Envíos.
4. Acción Email: `email_reply_to` = `custom_id` del campo email; `form_metadata: []`;
   «Collect Submissions» activado (copia en Elementor › Envíos).
5. Remitente: `«<Marca>» <no-reply@<dominio en uso>>`, derivado de `home_url()` para que
   siga al sitio al migrar. `phpmailer_init` con `Sender = From`; `wp_mail_from` solo si
   empieza por `wordpress@` y `wp_mail_from_name` solo si es `WordPress`.
6. Prueba en seco con la matriz de casos (`verificacion.md` §5).
7. Entrega del correo: `dig TXT <dominio>`. Sin SMTP mientras el sendmail del hosting
   entregue y el SPF del dominio del From autorice al servidor.
8. `admin_email` (si el usuario lo pide) sin el flujo de confirmación:
   `add_filter('send_site_admin_email_change_email','__return_false');`
   `update_option('admin_email','<ADMIN_EMAIL>'); delete_option('new_admin_email');`, e
   inventario de **todos** los destinatarios del sitio (plugins de seguridad y SEO, cada
   formulario en cada idioma).

**Hecho cuando.** La sonda en seco imprime la validación por caso y el correo simulado con
From, Reply-To y Sender correctos; la página está medida; el usuario hace o autoriza la
única prueba real, a su buzón.

**Trampas** (detalle en
[formularios.md](../../elementor4-atomico/references/formularios.md)):
- `e-form` (Elementor 4.3) depende de un nonce horneado en la página que la caché caduca,
  no valida en servidor, no tiene honeypot y Elementor lo marca como «Development».
- El prototipo comparaba el reloj del cliente con el del servidor: un reloj adelantado
  bloqueaba para siempre. Mide duración, no hora.
- `alguien@correo` (sin TLD) responde «Este campo es obligatorio» y los textos del servidor de
  Pro salen en inglés.
- `custom_id` solo admite `[A-Za-z0-9_]` y debe ser único entre formularios de la misma
  página; cada ítem del repetidor necesita su `_id`.
- Dentro de un `e-flexbox` en fila, los inputs con `size="1"` colapsan: `width:100%`.

**Qué preguntar.** Destinatarios de cada formulario y de los avisos del sistema; si
prefiere que durante el desarrollo lleguen solo a su buzón; SMTP o sendmail; permiso para
la prueba real.

---

## F8 · Ronda de ajustes del cliente

**Objetivo.** Aplicar la ronda completa con un inventario verificable y sin ampliar el
alcance.

**Entradas.** El feedback en todos sus canales: texto, `.docx`, capturas con marcas.

**Pasos.**
1. Inventario numerado de pedidos, leyendo cada canal por separado: el texto, el dibujo de
   las capturas y los adjuntos del `.docx` (`unzip -l "feedback.docx" | grep word/media`;
   el `.md` exportado los pierde). Si es grande, enséñaselo al usuario antes de ejecutar.
2. Clasifícalo en «ejecutable ya / bloqueado por material / necesita tu decisión», con «lo
   que más pesa» arriba: contradicciones con rondas anteriores, adjuntos que faltan y
   efectos colaterales («si ocultamos X, quedan N botones apuntando a una página que ya no
   existe»).
3. Ejecuta con el ciclo de siempre. Lo que el cliente no pagó se construye informativo,
   como en el prototipo, y fuera de los menús si lo pide.
4. Si no reproduces un fallo reportado, dilo y pregunta en qué condición lo vio (con o sin
   sesión, qué ancho).
5. Informa con la numeración del pedido.

**Hecho cuando.** Cada pedido del inventario tiene estado (hecho, bloqueado por X,
decide el cliente) y el usuario lo aprueba.

**Trampas.**
- «Igual que en el prototipo» significa comprobar la versión desplegada del prototipo y todo
  su comportamiento, no solo el campo que se nombra.
- Quitar una página del menú no la aísla: busca con `curl` + `grep` los enlaces de
  contenido que siguen apuntando a ella e informa.
- Una carpeta de feedback con el nombre de otro cliente: confírmalo.
- Cursor personalizado: dentro de `@media (hover:hover) and (pointer:fine)`, `cursor:auto`
  en los campos y solo fuera del editor.
- Claims y cifras que se contradicen: señálalos con evidencia (foto ampliada de la etiqueta)
  y una redacción acotada; no los unifiques tú.

**Qué preguntar.** Contradicciones, material que falta y todo lo que tenga coste.

---

## F9 · Segundo idioma

**Objetivo.** El segundo idioma completo, generado por script desde el idioma base. Nadie
edita las copias a mano.

**Entradas.** Los textos del prototipo en el segundo idioma (literales), las decisiones de
slugs y de texto alternativo.

**Pasos** (detalle en la skill `wp-polylang`:
[polylang-elementor.md](../../wp-polylang/references/polylang-elementor.md),
[generacion-idioma.md](../../wp-polylang/references/generacion-idioma.md),
[cadenas-menus-slugs.md](../../wp-polylang/references/cadenas-menus-slugs.md)):
1. Investigación del plugin de idiomas (código leído) y auditoría de las cadenas fijas del
   hijo.
2. Mapas de traducción por página, hechos por agentes:
   `{doc, el, ruta, es, en, fuente: prototipo|propuesta, nota}`, más medios, otros y dudas.
   Valida que toda ruta existe, que el texto base coincide con el sitio, la cobertura y los
   conflictos de alt; consolida en un único JSON.
3. Primero el hijo y los mu-plugins (plantillas traducibles, caché de condiciones sin
   idioma, kit sin idioma, metas de caché que no se copian, idioma del ajax del editor, alt
   por meta, cookie de idioma apagada).
4. Script idempotente de generación: crea o actualiza cada traducción enlazada, copia el
   árbol, aplica los textos por ID de elemento y ruta, y avisa si el texto base cambió.
5. Portada en `/<IDIOMA_2>/`, cadenas (base del CPT, migas, título del 404, lema) con un
   único escritor, y menús por idioma. `wp rewrite flush` y purga.

**Hecho cuando.** El segundo idioma, medido contra el prototipo en ese idioma, queda a
≤1 px; el escaneo del HTML no encuentra restos del idioma base (texto y atributos); el
filtro AJAX, el selector de idioma y los enlaces funcionan; las propuestas de copy están
listadas para el cliente.

**Trampas.**
- Sin `elementor_library` traducible, `/<IDIOMA_2>/` sale con cabecera, pie y ficha en el
  idioma base.
- Condiciones del Theme Builder solo en la plantilla del idioma base; la caché de
  condiciones regenerada desde el editor pierde las plantillas del otro idioma.
- El kit nunca lleva idioma, y el botón «asignar el idioma por defecto a todo» es una
  trampa. Al traducir con el «+», Elementor copia sus metas de caché y las condiciones.
- Nunca «Synchronize» de Polylang Pro en páginas de Elementor.
- Al guardar desde CLI un documento del segundo idioma, fija `PLL()->curlang` a ese idioma:
  Elementor pinta el documento al guardar para generar el texto plano.
- `pll_insert_post` calcula el slug antes de tener idioma (`products-2`).
- La cookie `pll_language` impide que la caché de página del hosting guarde nada.

**Qué preguntar.** Slugs compartidos o traducidos; alt por meta o traducción de medios;
idioma del correo interno (en el caso, el del cliente); copy que no está en el prototipo
(propuestas); el nombre del sitio es la marca y no se traduce.

---

## F10 · 404, acceso con marca y «Próximamente»

**Objetivo.** Pantallas especiales de marca en los dos idiomas, URL de acceso propia y la
cortina lista pero apagada hasta coordinarla.

**Entradas.** La cortina del prototipo si existe, el slug de acceso que da el usuario y el
plugin de seguridad del hosting (en el caso, SiteGround Security; con otro, investiga su
código igual).

**Pasos.**
1. Investigación de solo lectura (plantilla 404 del Theme Builder, pantalla de login,
   plugin de seguridad) → dos propuestas de diseño en paralelo → un juez que compara con
   una tabla de criterios, injerta lo mejor de la perdedora y escribe la especificación
   final con copy, árbol V4, clases y código → construcción.
2. 404: plantilla `error-404` creada con
   `documents->create('error-404', [...], ['_elementor_template_sub_type'=>'not_found404'])`
   y la condición `include/singular/not_found404` guardada por script (`save_conditions`)
   **solo en el idioma base**: desde CLI no se pone sola. Con salidas útiles. Publícala antes o a la vez que la URL de acceso propia.
3. Acceso: `wp option update sg_security_login_url <LOGIN_SLUG>` (sin barras) y
   **después** `wp option update sg_security_login_type custom`. Pantalla de marca en
   `inc/acceso.php` + `css/login.css`, con el idioma sacado de `determine_locale()`.
4. «Próximamente»: plantilla de tipo Página con Canvas y su traducción enlazada; opciones
   `elementor_maintenance_mode_mode` (`''` | `coming_soon` | `maintenance`),
   `elementor_maintenance_mode_exclude_mode = logged_in` y
   `elementor_maintenance_mode_template_id`; vista previa pública por parámetro
   (`/?<PREFIJO>-vista=proximamente`). El script **no** enciende el modo.

**Hecho cuando.** Con `curl` y UA de navegador: `/no-existe-xyz/` y
`/<IDIOMA_2>/no-existe-xyz/` dan 404 con la plantilla de su idioma y un solo `<main`;
`/wp-login.php` y `/wp-admin/` sin sesión dan 302 a `/404`; `/<LOGIN_SLUG>` da 302 a
`wp-login.php?sgs-token=…`; la vista previa de la cortina da 200 con `noindex`; y el usuario
entra de verdad por el slug.

**Trampas.**
- El plugin quita **todas** las barras de la ruta y compara estricto: un slug guardado con
  barra deja el acceso inaccesible. Recuperación: `wp sg custom-login disable` o
  `/wp-login.php?sgs-token=<LOGIN_SLUG>`.
- Una `Referrer-Policy` global `origin` o `strict-origin` rompe el acceso por el slug. Las
  cabeceras de seguridad se prueban contra el login antes de desplegarlas.
- El aviso «sesión caducada» del editor abre `wp-login.php` sin token: hay que entrar por
  `/<LOGIN_SLUG>` en otra pestaña. Nada público enlaza al login.
- La 404 es también lo que ve quien prueba `wp-admin` sin sesión, y sale siempre en el
  idioma base.
- `coming_soon` responde 200 + `noindex` en **todas** las URL, `robots.txt` incluido;
  `maintenance` responde 503 + `Retry-After: 600`. En un dominio con historial, nunca
  `coming_soon` (ver `decisiones.md`).
- Sin traducir el ID de la opción de plantilla, `/<IDIOMA_2>/` sale en blanco con la cortina.
- Con el diseño «Elementor ancho completo», la 404 lleva dos `<main>`: usa el diseño
  predeterminado.
- La pantalla de login no ejecuta `wp_head`: fuentes, tokens y CSS del kit no llegan.
- Las animaciones decorativas usan `translate`, `rotate` y `scale` sueltas para no pisar
  el `transform` de las entradas animadas.

**Qué preguntar.** El slug de acceso (lo da el usuario y no se publica); cuándo encender la
cortina y en qué modo en cada entorno (se coordina con la sesión de rendimiento, que mide sin
sesión).

---

## F11 · En paralelo: SEO/rendimiento y documento de entrega

**Objetivo.** PageSpeed en verde en las cuatro categorías con Yoast configurado, sin cambiar
el diseño (regresión ±1 px), y un documento técnico de entrega y cierre fiel al sitio.

**Cuándo.** El usuario abre otra sesión cuando la construcción entra en el segundo idioma
(en el caso: «No necesitas hacer nada, es más para tu contexto»). Protocolo de convivencia:
`sesiones-paralelas.md`.

**Entradas.** El sitio en staging con el idioma base construido, el acceso por alias, el
prototipo para comparar (sus fuentes y métricas de respaldo, si es Next.js), los acuerdos
de propiedad entre sesiones y, para la entrega, la memoria completa del proyecto, la URL de
acceso y el firmante que indique el usuario.

**Pasos SEO/rendimiento** (skill `wp-seo-rendimiento`, agente `wp-seo`):
1. Inventario de solo lectura y línea base: Lighthouse de la misma versión que PSI, 3
   corridas por URL y formato, mediana; geometría de cada página para la regresión visual.
2. Análisis en paralelo por agentes y un crítico que los contrasta con el código de los
   plugins y devuelve un único plan JSON ordenado.
3. Respaldo en el servidor; guardas urgentes (IndexNow apagado, cookie de idioma apagada).
4. Primero los arreglos invisibles, cada lote con regresión ±1 px y consola; Yoast por
   script; accesibilidad no visual; medición final; revisión adversarial.

**Pasos documento de entrega** (plantilla
[`../plantillas/documento-entrega.md`](../plantillas/documento-entrega.md)):
1. Leer la memoria; inventario de solo lectura del servidor (de los usuarios solo la
   longitud del login y los roles).
2. Bajar el hijo del servidor y compararlo con la última copia para ver el trabajo de las
   otras sesiones.
3. Comprobar cada etiqueta del panel con las traducciones instaladas antes de documentarla.
4. HTML A4 → PDF con Chrome headless, y leer las páginas del PDF antes de entregarlo.
5. Revisión adversarial («hechos», «rutas del panel», «completitud»); lo que salga mal en
   el sitio se arregla en el sitio, no solo en el documento.

**Hecho cuando.** SEO: tabla antes/después por página, móvil y escritorio. Cifras del caso:
móvil 89–99, escritorio 88–100, accesibilidad 93–97, buenas prácticas 100 y SEO 66 en
staging (tope con `noindex`; 100 al lanzar). Entrega: `grep` de contraseñas y tokens en el
HTML = 0, y todo lo que el documento da por hecho es cierto en el servidor o sale como
supuesto por confirmar.

**Trampas.**
- Con la cortina encendida, PageSpeed mide la cortina: se enciende después del «listo» de
  quien mide.
- La API de PSI sin clave devuelve 429; la captura de pagespeed.web.dev puede guardar el
  informe móvil aunque pidas escritorio (valida `formFactor`).
- El IndexNow de Yoast Premium ignora `blog_public`: apágalo en staging.
- Heredar de un transformador concreto de Elementor puede tumbar el sitio en una
  actualización (`E_COMPILE_ERROR`): envuelve el registrado.
- Apagar todas las Google Fonts de Elementor rompe las que el cliente elija después: quita
  solo los handles de las familias que autoalojas.
- Elementor Pro sin traducción es_ES: el panel sale medio en inglés y la guía tiene que usar
  las etiquetas reales.
- El documento marcaba como retirados unos plugins que seguían activos: separa los
  supuestos.
- `break-before: page` por sección y `columns:2` dejaron páginas medio vacías en el PDF:
  sin saltos forzados y con rejilla.

**Qué preguntar.** Cualquier cambio visible (decisión); colores para llegar a
Accesibilidad 100 (cliente); perfiles sociales y datos de contacto del schema; qué incluye
el documento (licencias sí o no, firmante y cargo, URL de acceso). La cuenta del cliente la
crea el usuario; su contraseña nunca se escribe.

---

## F12 · Migración a producción y lanzamiento

**Objetivo.** El WordPress sustituye a lo que hay publicado en el dominio del cliente sin
caída y con vuelta atrás inmediata. Después se lanza, en orden.

**Entradas.** Staging verificado; acceso SSH al destino (llave dedicada); las decisiones
D1–D16 (`decisiones.md` §3.8); el mapa 301 de la sesión de SEO; el DNS del dominio.

**Pasos** (skill `wp-migracion`:
[runbook.md](../../wp-migracion/references/runbook.md),
[auditoria-hosting.md](../../wp-migracion/references/auditoria-hosting.md),
[correo-dns.md](../../wp-migracion/references/correo-dns.md),
[lanzamiento.md](../../wp-migracion/references/lanzamiento.md),
[rollback.md](../../wp-migracion/references/rollback.md)):
1. Auditoría de solo lectura de origen y destino en un workflow (3 agentes y un
   consolidador), con un prompt que prohíbe escrituras, comandos del panel que modifiquen,
   imprimir secretos y herramientas de navegador, y que declara lo intocable. Comprueba tú
   en persona los hechos clave (administradores, código de los premium).
2. Runbook: «En una pantalla», decisiones del dueño con su recomendación, puertas
   GO/NO-GO A–D, marcas `[lectura]` / `[ESCRIBE en X]` / `[MANUAL usuario]`, pasos con su
   salida esperada, vuelta atrás y una limpieza que no hace el agente. No se ejecuta nada
   hasta cerrar las decisiones, y cada paso que escribe lleva el OK del usuario.
3. Congelación anunciada y comprobada con datos; exportación por la salida estándar de SSH
   (el origen queda en solo lectura), con SHA-256.
4. Obra aparte, fuera del docroot: núcleo limpio verificado, `wp-content`, `.well-known`,
   BD nueva con la contraseña generada en el servidor, `search-replace` en orden, plugins
   del hosting de origen que no viajan (optimizador y conector de IA, D3) retirados sin
   ejecutar su código y `.htaccess` por bloques. El de seguridad se conserva si su código
   funciona fuera (D4): en el caso se conservó, porque lleva la URL de acceso propia.
5. Cambio de carpetas con dos `mv` detrás de la cortina 503; puerta C; la vuelta atrás son
   otros dos `mv`.
6. Tareas tras el cambio, con la ruta final; versión de PHP del dominio; la batería de
   verificación del runbook (su F10) con la llave de la cortina sin imprimirla;
   formularios en seco; clases preview = frontend.
7. Memoria actualizada **en el acto**: qué es producción y qué queda como staging congelado.
8. Correo del dominio (SPF, DKIM, enrutamiento): si el panel no propaga, un archivo con los
   registros exactos para el usuario.
9. Lanzamiento (puerta D, runbook F11): correo comprobado, OK de SEO, plugins decididos.
   `blog_public=1` **antes** de retirar la cortina; modo de mantenimiento vacío; IndexNow y
   reindexado de Yoast solo en producción; robots, sitemap, canonical y hreflang; Search
   Console; medir PageSpeed otra vez. Caché de página del nuevo hosting (D11): la sesión de
   rendimiento la recomienda antes o justo al lanzar; la decide el usuario (en el caso quedó
   pendiente).

**Hecho cuando.** Pasan las puertas. En el caso, el cambio se hizo con 0 min de caída y el
lanzamiento quedó preparado, bloqueado por el DNS del correo y el OK de SEO.

**Trampas.**
- En un cPanel compartido, la raíz del dominio no es `public_html` (era el WordPress de
  otro sitio de la cuenta): compruébala con un archivo sonda y `uapi DomainInfo`.
- WP-CLI sube por las carpetas hasta encontrar `wp-load.php` y puede escribir en el
  WordPress ajeno: `--path` siempre y guarda de `table_prefix` en cada bloque que escribe.
- MySQL 8 → 5.7: la colación `utf8mb4_0900_ai_ci` rompe la importación.
- El `search-replace` va en orden: ruta absoluta, dominio **sin esquema** (cubre el JSON
  escapado de Elementor y el `no-reply@` de los formularios) y https en forma normal y
  escapada. Termina con 0 coincidencias del dominio viejo.
- Un subagente de auditoría imprimió las credenciales de otro sitio aunque el prompt lo
  prohibía: revisa las salidas y, si se filtra algo, recomienda rotarlo.
- La cortina estática bloquea `wp-cron.php` y los loopbacks; `.well-known` tiene que quedar
  fuera de la cortina y de las redirecciones.
- Los `[G]` (410) del mapa dan 404 en cPanel sin `ErrorDocument 410 default`.
- La versión de PHP de un dominio adicional se hereda del `.htaccess` del sitio padre: fíjala
  después del cambio.
- Lo que daba el hosting de origen no viaja: caché de página, WebP por `Accept`,
  cabeceras de caché.
- Las zonas DNS editadas en cPanel pueden no llegar a los NS públicos, y el enrutamiento de
  correo «local» se traga los avisos cuando el MX es externo.
- ModSecurity del nuevo hosting puede dar 403 al guardar en Elementor: pide al usuario que
  pulse «Actualizar» en una página de cada idioma.
- Un administrador «desconocido» puede haberlo creado el usuario desde otra sesión:
  confírmalo, no lo toques.
- No se avisó a la sesión de SEO de que el staging dejaba de ser la fuente: dilo en cuanto
  termine la exportación.

**Qué preguntar.** Las decisiones de migración (`decisiones.md` §3.8), el momento («ya,
detrás de la cortina» fue la recomendada y la elegida) y el OK de cada paso que escribe.
