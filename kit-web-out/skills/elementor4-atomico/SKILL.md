---
name: elementor4-atomico
description: >
  Oficio técnico de Elementor 4 atómico (Editor V4) por WP-CLI: construir páginas y
  plantillas con elementos e-* (e-flexbox, e-grid, e-heading, e-image, e-svg…), clases
  globales, variables, estilos locales, kit y breakpoints, y el Theme Builder (cabecera,
  pie, single, loop items, 404, «Próximamente») con `wp eval-file`. Úsala siempre que se
  escriba, ejecute o depure un script que guarde documentos, clases o el kit de
  Elementor 4; cuando un estilo «no se aplica», el editor «borró las clases», un guardado
  no persiste o salta «Settings validation failed»; y antes de meter un widget clásico
  (nav-menu, formulario, Loop Grid) dentro de un maquetado V4.
---

# Elementor 4 atómico por WP-CLI

Guía de oficio, independiente de marca. La marca vive en el `CLAUDE.md` del proyecto; el
proceso de punta a punta, en la skill `wp-elementor-replica`. Verificado con Elementor
4.3.0, Elementor Pro 4.2.3 y Hello Elementor 3.5.1 (caso de referencia: HomeMaker,
réplica exacta de un prototipo Next.js construida entera por script). **No te fíes de lo
que sabes de Elementor 3**: el sistema de diseño, el guardado y la prioridad de estilos
funcionan de otra manera.

## Lo primero, siempre

1. **Confirma que es V4.** El primer script de cualquier proyecto es
   `scripts/e4-sondeo-kit.php`: versiones, experimentos, kit, variables, clases y
   breakpoints. Tiene que salir `e_atomic_elements` activo. En el caso de referencia se
   escribió primero el kit al estilo V3 y hubo que rehacerlo al descubrir que era V4,
   donde el diseño son variables y clases globales.
2. **Todo por archivo y con usuario.** PHP escrito en local, `php -l`, `scp` a
   `~/tmp-<PREFIJO>/` y `wp --path=<WP_PATH> --user=1 eval-file …`. Sin `--user=1`,
   `save()` devuelve `false` y no guarda nada, sin avisar. Nunca `wp eval '…'` por SSH:
   las comillas se rompen.
3. **Tras cada script de clases, sincroniza el contexto preview**
   (`require __DIR__ . '/e4-sync-preview.php';` al final). Sin eso, el primer «Publicar»
   en el editor borra del sitio las clases creadas por CLI y las quita de los documentos.
4. **Fija los breakpoints antes de maquetar.** Si el prototipo colapsa el menú en su
   `xl` (1280), activa `tablet_extra = 1279`: el nav-menu de Pro no colapsa en `laptop`.

## Cómo se reparte el trabajo

- **Estructura y texto:** elementos V4 construidos con los generadores de Prop Types,
  ids deterministas y títulos legibles en el Navegador (`editor_settings.title`).
- **Estilo editable:** clases globales con prefijo, al estilo utilitario del prototipo,
  y variables V4. Los valores de una sola instancia, en estilos locales (panel Estilo).
- **Lo que V4 aún no resuelve:** widgets clásicos (nav-menu, formulario de Pro, Loop
  Grid, filtro por taxonomía, shortcode, editor de texto) envueltos en un `e-flexbox`
  «caja» y estilizados desde el tema hijo.
- **Tema hijo:** lo que V4 no expresa (pseudo-elementos, velos, animaciones, widgets V3)
  y el comportamiento, enganchado a atributos `data-<PREFIJO>-*`, nunca a clases.
- **Kit:** variables, globals V3 neutros y breakpoints, escritos por meta.

## Reglas que no se negocian

- **Nada de JSON a mano.** Valores con `Classes_Prop_Type::generate`,
  `String_Prop_Type::generate`, `Escaped_Html_Prop_Type::generate`… y props de estilo
  con su `$$type` exacto. Ante un tipo nuevo, sondéalo (`scripts/e4-sondeo-esquema.php`):
  `font-family` no es `string` y los bordes no son `dimensions`.
- **Clases: leer, fusionar, validar, escribir, sincronizar.** `put()` recibe el conjunto
  entero y borra las clases que falten. Valida con `Global_Classes_Parser` antes y no
  escribas si falla.
- **Gana la PRIMERA clase del orden**, al revés que la cascada. Una propiedad por
  familia de clases (tipografía sin color ni margen) y modificadores delante de su base.
- **Todo contenedor fija su `padding` y su `width`.** Los atómicos nacen con
  `padding:10px`, `width:100%` y, vacíos, `min-width:30px`; `e-grid` trae dos filas.
  Auditoría de 10px por página (`scripts/auditar-padding-atomico.js`) hasta `total:0`.
- **Kit por meta, nunca `$kit->save()`**: en CLI descarta las claves que no reconoce.
- **`save()` se comprueba con `false ===`** y se relee `_elementor_data`: `Loop::save()`
  devuelve `null` aunque guarde.
- **Ids de 7 caracteres `[a-z0-9]`, deterministas por documento.** Re-ejecutar un build
  deja el mismo documento; los ids referenciados (grid del filtro, formularios) son fijos.
- **Theme Builder:** condiciones a mano, como lista de arrays y solo en la plantilla del
  idioma base; plantillas publicadas; singles y 404 con Diseño «Predeterminado»
  (`page_template` vacío; en un documento de tipo `page` la clave es `template`).
- **URLs guardadas desde CLI, en https** (`set_url_scheme()`): en CLI salen en `http://`.
- **Lo oculto o animado se ve en el editor.** El estado inicial cuelga de
  `html.<PREFIJO>-js`, que no se marca con `?elementor-preview`: si el CSS oculta por
  defecto, el cliente no puede editar lo que no ve.
- **Editable por el cliente:** textos dentro de los widgets, títulos en el Navegador,
  posiciones en estilos locales, textos extra en Atributos, formularios con los
  controles nativos de Pro. Nada de plantillas importadas.
- **Verifica el editor por datos y pide la prueba visual.** Claude no puede iniciar
  sesión en el editor: valida los documentos con los parsers y deja la prueba del
  usuario como pendiente explícito hasta que la confirme.

## Ciclo por página

1. Spec medida del prototipo (skill `wp-elementor-replica`: anchos y tolerancias en
   `references/verificacion.md` §2).
2. Medios con su alt ya redactado (`wp media import --alt … --porcelain`) y un JSON
   clave → id de adjunto; nunca ids a mano.
3. `<pagina>-classes.php` desde `scripts/e4-clases-upsert.plantilla.php`, con la
   sincronización de preview al final.
4. `<pagina>-build.php` desde `scripts/e4-build-pagina.plantilla.php`: reescribe el
   documento entero e imprime secciones y recuento de elementos.
5. Medición contra el prototipo con el mismo JS en los dos lados y reveals forzados
   (`scripts/medir-vs-prototipo.js`), y auditoría de 10px
   (`scripts/auditar-padding-atomico.js`). Checklist en la skill `wp-qa`.
6. Revisión adversarial (agente `qa-elementor`) y arreglos pequeños por id de elemento
   (`scripts/e4-parche-por-id.plantilla.php`, con modo en seco). Vuelta a medir.
7. Purga de la caché del hosting tras cada despliegue.

## Referencias

- `references/modelo-de-datos.md`: árbol de elementos e-*, settings con `$$type`,
  enums de `tag`, ids, formas de estilo y variantes, estilos locales, clases globales
  (almacenamiento, orden, contextos, sincronización), variables, widgets V3 dentro de
  V4, kit, breakpoints, estilos base de los contenedores y guardado.
- `references/gotchas-cli.md`: todas las trampas con síntoma, causa, arreglo y cómo
  detectarlas, y una tabla para buscar por mensaje de error.
- `references/theme-builder.md`: cabecera, pie, single, loop items, 404, condiciones y
  su caché, plantillas por idioma y modo mantenimiento.
- `references/contenido-dinamico.md`: CPT, Loop Grid, filtro por taxonomía y props
  dinámicas desde CLI.
- `references/formularios.md`: formulario de Pro dentro de V4, antispam, correo y
  pruebas en seco.
- `references/tema-hijo.md`: frontera entre Elementor y el tema hijo, y sus patrones.
- `references/trampas-4.3.md`: 22 trampas verificadas en Elementor 4.3.1 / Pro 4.3.0 con Woo, YITH, ACF y
  SiteGround (kit y CLI, atómicos, widgets V3, rendimiento).

## Scripts (`scripts/`)

| Script | Para qué |
|---|---|
| `e4-sondeo-kit.php` | Primer script y control posterior (solo lectura). |
| `e4-sondeo-esquema.php` | Props, enums y formas `$$type` antes de usar un tipo nuevo. |
| `e4-helpers.php` | Constructores V4, valores de estilo, `$e4_guardar`, `$e4_plantilla`, `$e4_condiciones`. |
| `e4-variables.plantilla.php` | Variables V4 del kit (crea solo las que faltan). |
| `e4-clases-upsert.plantilla.php` | Clases globales: leer, fusionar, ordenar, validar, escribir. |
| `e4-sync-preview.php` | Orden frontend → preview. Obligatorio tras cada script de clases y tras migrar. |
| `e4-dump-clases.php` | Volcado legible de clases y variables (solo lectura). |
| `e4-kit-breakpoints.plantilla.php` | Breakpoints por meta del kit, con modo en seco. |
| `e4-build-pagina.plantilla.php` | Build idempotente de una página. |
| `e4-parche-por-id.plantilla.php` | Cambios quirúrgicos por id de elemento. |
| `e4-dynamic-tags-preambulo.php` | Preámbulo para guardar props dinámicas desde CLI. |
| `probe-forms-seco.php` | Prueba de formularios sin enviar correo. |
| `medir-vs-prototipo.js` | Cajas y estilos computados, reveals forzados, contra el prototipo (navegador o Puppeteer). |
| `auditar-padding-atomico.js` | Contenedores con el `padding:10px` de base y cajas vacías con `min-width:30px`. |
| `contrast.py` | Contraste WCAG de texto sobre vídeo o foto con velo, por fotograma (local). |

Lee la cabecera de cada script antes de usarlo: dice su uso exacto, qué editar y si
está probado en el caso de referencia o solo generalizado. Orden de uso y salidas
esperadas: `scripts/README.md`.

## Skills y agentes relacionados

`wp-elementor-replica` (proceso y fases), `wp-polylang` (segundo idioma, plantillas por
idioma, cadenas), `wp-seo-rendimiento` (imágenes, fuentes, Yoast, PageSpeed),
`wp-migracion` (staging → producción, cortina, lanzamiento), `wp-qa` (checklist y
mediciones). Agentes: `elementor-dev` construye con esta skill cargada; `qa-elementor`
audita midiendo.
