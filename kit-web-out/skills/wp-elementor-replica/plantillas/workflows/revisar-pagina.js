export const meta = {
  name: 'revisar-pagina',
  description: 'Revisión adversarial de solo lectura de una página o plantilla de Elementor 4 contra el prototipo: buscadores por lente y un escéptico por hallazgo',
  whenToUse: 'Al cerrar una página, plantilla o bloque grande (en segundo plano mientras se avanza), tras regenerar el segundo idioma o tras un lote de cambios que deben ser invisibles',
  phases: [
    { title: 'Buscar', detail: 'un buscador por lente, solo lectura, sin navegador' },
    { title: 'Verificar', detail: 'un escéptico por hallazgo; por defecto, no real' },
  ],
}

/*
 * revisar-pagina.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   2–4 buscadores independientes (uno por lente) revisan en SOLO LECTURA una página o plantilla
 *   ya construida; se juntan los hallazgos, se ordenan por gravedad y un escéptico por hallazgo
 *   intenta refutarlo. Devuelve { confirmed, refuted, unverified, lineas }. Se aplica solo lo
 *   confirmado; lo no verificado se informa, nunca se da por bueno.
 *   Por qué: la medición no ve fallos de datos ni de comportamiento. En el caso de referencia esta
 *   revisión encontró el borrado de clases al publicar desde el editor, un parámetro de URL tipo
 *   array que daba 500, el enlace de salto roto y el hover invisible de todos los botones verdes.
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/revisar-pagina.js', args: {
 *     pagina: 'Contacto', url: 'https://<STAGING_URL>/contact/', docIds: [<ID>],
 *     prototipo: '<ruta del código del prototipo>', ruta: '/contact/', spec: '<scripts>/contacto/spec-prototipo.md',
 *     scripts: ['<scripts>/contacto/contacto-build.php', '<scripts>/contacto/contacto-classes.php'],
 *     hijo: '<copia local del tema hijo>', sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', prefijo: '<PREFIJO>',
 *     sondas: '<carpeta local para sondas>', stack: 'WP x · Elementor x (V4) · Pro x · Polylang Pro x',
 *     medido: ['alturas de sección a 1440 y 375 dentro de 1 px'], conocidos: ['breakpoints de Elementor 767/1024/<tablet_extra>'],
 *     preset: 'pagina', formularios: true } })
 *   args (los objetos y listas como JSON real, no como texto):
 *     pagina, url ........... obligatorios.
 *     preset ................ 'pagina' (fidelidad, a11y-js, editabilidad [+ formularios]) · 'idioma' (texto, enlaces,
 *                             regresion; pide idioma2 y rutas) · 'cambios' (regresion, codigo; pide cambios y rutas).
 *     lentes ................ [{ key, prompt }] para sustituir las del preset.
 *     conocidos, medido ..... listas: lo aceptado y lo ya medido NO se reporta (sin esto, ruido).
 *     maxHallazgos (6), maxVerificar (7) — valores del caso; sube maxVerificar si vas a decidir sobre más.
 *     agentType ............. opcional, p. ej. 'qa-elementor' si el agente del kit está instalado (buscadores y escépticos).
 *     dirMedicion ........... carpeta de trabajo con qa-medir.mjs + puppeteer-core (por defecto ~/qa-headless).
 *     extra ................. texto libre que se añade al contexto común.
 *   Sin sshAlias/wpPath los buscadores trabajan solo con curl y archivos locales.
 *
 * ESTADO
 *   Generalizado a partir de los workflows reales de HomeMaker contacto-review, historia-review,
 *   orangeology-review, review-tiendas y review-en (probados allí). Esta versión parametrizada no se ha ejecutado fuera
 *   de HomeMaker. Validación local: sintaxis con node --check (cuerpo envuelto en función async) y
 *   meta evaluada como literal puro.
 *
 * RIESGOS
 *   Solo lectura por instrucción: revisa que ningún agente haya escrito (sondas en ~/tmp-<PREFIJO>-revision/
 *   del servidor; se dejan y las limpia el usuario). Si un agente imprime un secreto, díselo al usuario.
 */

const A = args || {}
const falta = ['pagina', 'url'].filter(k => !A[k])
if (falta.length) throw new Error('revisar-pagina: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')

const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const P = A.prefijo || '<PREFIJO>'
const PRESET = A.preset || 'pagina'
const MAX_H = A.maxHallazgos || 6
const MAX_V = A.maxVerificar || 7
const DOCS = lista(A.docIds).length ? '#' + lista(A.docIds).join(', #') : 'los documentos de la página'
const IDIOMA2 = A.idioma2 || '<IDIOMA_2>'
const RUTAS = lista(A.rutas).join(', ') || A.url
const TMP = '~/tmp-' + P + '-revision/'

const ACCESO = A.sshAlias && A.wpPath
  ? 'SERVIDOR: ssh ' + A.sshAlias + " '<comando>' con WordPress en --path=" + A.wpPath + ' (WP-CLI disponible). ' +
    (A.sondas
      ? 'Sondas PHP: escríbelas en local en ' + A.sondas + ', súbelas con scp a ' + TMP + ' y ejecútalas con "wp --path=' + A.wpPath + ' eval-file ' + TMP + 'x.php --user=1". Nunca "wp eval" inline por SSH (tres capas de comillas).'
      : 'No escribas sondas: usa solo comandos de lectura (wp option get, wp post meta get, wp post list, grep y sed -n sobre el código de los plugins).')
  : 'SIN acceso SSH en esta revisión: trabaja con curl sobre el HTML y el CSS servidos y con los archivos locales.'

// Truco probado en HomeMaker para validar elementos atómicos en modo editor desde WP-CLI.
const TRUCO_EDITOR = "$rp=new ReflectionProperty(\\Elementor\\Core\\Frontend\\Performance::class,'is_frontend'); if (PHP_VERSION_ID < 80100) { $rp->setAccessible(true); } $rp->setValue(null,false); \\Elementor\\Modules\\AtomicWidgets\\DynamicTags\\Dynamic_Tags_Module::fresh();"

const REGLAS = `REGLAS (obligatorias):
- SOLO LECTURA ESTRICTA en el servidor y en los archivos del proyecto: no guardes documentos, no cambies opciones, no purgues cachés, no subas archivos del tema, nada de "rm". Las sondas solo leen.
- NUNCA envíes un formulario real ni dejes que wp_mail se ejecute: si simulas un envío, corta el correo con add_filter('pre_wp_mail', fn() => true) ANTES de nada. Un envío válido le llega al cliente.
- NO uses herramientas de navegador (mcp__Claude_Browser__*, Claude in Chrome, computer-use): el navegador es de otra sesión y una pestaña oculta pausa las animaciones. Mide con curl, con sondas PHP o con un Chrome headless propio (qa-medir.mjs: original en ~/.claude/skills/wp-qa/scripts/, uso en wp-qa/references/mediciones.md M5) si ya está preparado con puppeteer-core en ${A.dirMedicion || '~/qa-headless'}; no instales paquetes.
- No leas ni imprimas secretos: prohibido cat, sed o grep de contenido sobre wp-config.php, .env, .my.cnf o volcados *.sql. Para el prefijo de tablas, "wp config get table_prefix".
- Si tu Bash corre en sandbox, las llamadas con red (ssh, scp, curl remoto) necesitan dangerouslyDisableSandbox: true.
- Rompe la caché con ?nocache=<número> (nunca ?m=, que es el archivo por fecha de WordPress) y usa un User-Agent de navegador en curl.
- Cita la evidencia: archivo:línea, URL + fragmento del HTML, o comando + salida. Si algo no se puede determinar, dilo; no supongas.`

const CONTEXTO = `
PROYECTO: réplica exacta de un prototipo en WordPress + Elementor 4 con el editor atómico (V4). Stack: ${A.stack || 'ver el CLAUDE.md del proyecto'}. El cliente tiene que poder editarlo todo en Elementor. Prefijo del proyecto: ${P} (clases globales ${P}-*, atributos data-${P}-*).
QUÉ SE REVISA: ${A.pagina} — ${A.url} (documentos de Elementor: ${DOCS}).
PROTOTIPO (la referencia de diseño): ${A.prototipo || 'no indicado'}${A.ruta ? ' · ruta ' + A.ruta : ''}. SPEC MEDIDA: ${A.spec || 'no indicada'}.
SCRIPTS DE CONSTRUCCIÓN: ${lista(A.scripts).join(', ') || 'no indicados'}. TEMA HIJO (copia local; la fuente de verdad es el servidor): ${A.hijo || 'no indicada'}.
${ACCESO}
YA MEDIDO Y CORRECTO (no lo reportes salvo evidencia nueva y concreta): ${lista(A.medido).join('; ') || 'nada declarado'}.
CONOCIDO Y ACEPTADO (NO lo reportes): ${lista(A.conocidos).join('; ') || 'nada declarado'}.
${A.extra || ''}
${REGLAS}
Reporta solo defectos concretos y reproducibles, ordenados por gravedad. Nada de opiniones de estilo ni de «podría».`

const PRESETS = {
  pagina: [
    { key: 'fidelidad', prompt: 'LENTE: fidelidad visual y de contenido. Compara cada texto, color, tamaño y peso de fuente, borde, radio, sombra, estado (hover, activo, foco), transición y regla responsive entre 375 y 1440 del prototipo (código de la página y de sus componentes, y la spec medida) con la salida de WordPress (curl del HTML y del CSS que carga: CSS de clases globales, post-<id>.css, style.css del hijo). Busca lo que notaría un visitante: copy distinto o que falta, color equivocado en un estado, hover que deja el texto del color del fondo, icono ausente, imagen o alt distintos (e-image pinta el alt de la Biblioteca de medios, no el del widget), comportamiento distinto entre 375 y 1440.' },
    { key: 'a11y-js', prompt: 'LENTE: accesibilidad, validez del HTML y comportamiento. Un solo h1 y orden de encabezados sin saltos; un único <main id="content"> que envuelve el contenido (no la cabecera ni el pie) y el enlace de salto que apunta a él; alt con información; SVG decorativos ocultos (aria-hidden) y sin partes enfocables; roles y aria coherentes (aria-pressed, aria-label, aria-live); teclado y foco visible; qué recibe un lector de pantalla en regiones ocultas; ids duplicados; interactivos anidados; lang. JS del hijo: casos límite (elementos que faltan, varias instancias, errores), comportamiento sin JS y dentro del editor de Elementor (sin la clase html.' + P + '-js), fugas a otras páginas por selectores globales.' },
    { key: 'editabilidad', prompt: 'LENTE: editabilidad e integridad de los datos de Elementor. Valida en modo editor cada elemento atómico de ' + DOCS + ' (props contra su esquema) empezando la sonda con: ' + TRUCO_EDITOR + ' Comprueba que cada clase global usada existe en los DOS contextos: las metas del kit _elementor_global_classes_order y _elementor_global_classes_order_preview deben contener los mismos ids (si no, publicar desde el editor borra clases de todo el sitio). Ajustes de widgets V3 (formulario, nav-menu, loop) que el saneado de controles del editor descartaría o reiniciaría en el próximo guardado; ids de elemento duplicados; selectores CSS o JS que dependen de estructura frágil o de clases de estilo en vez de data-' + P + '-*; qué pasa si el cliente duplica un bloque o cambia un texto; conflictos entre clases globales (la misma propiedad en dos clases: gana la PRIMERA del orden, y los modificadores deben ir antes que su base).' },
  ],
  idioma: [
    { key: 'texto', prompt: 'LENTE: texto del idioma ' + IDIOMA2 + '. En cada URL del idioma ' + IDIOMA2 + ' (' + RUTAS + '), con su cabecera y su pie, compara cada texto visible y cada atributo de texto (alt, aria-label, title, placeholder, data-' + P + '-*, botones, menús y desplegable móvil) con el idioma ' + IDIOMA2 + ' del prototipo. Reporta restos del idioma base, textos que faltan o que difieren del prototipo y fichas de contenido dinámico con campos distintos.' },
    { key: 'enlaces', prompt: 'LENTE: enlaces, cambio de idioma y URL. En cada página del idioma ' + IDIOMA2 + ' (' + RUTAS + '): todo href interno apunta a su equivalente en /' + IDIOMA2 + '/, las anclas existen en la página destino, ninguna página del idioma ' + IDIOMA2 + ' enlaza al idioma base (salvo la opción del selector), hreflang y selector apuntan a la pareja correcta, ningún enlace da 404, y las páginas del idioma base siguen enlazando solo a su idioma.' },
    { key: 'regresion', prompt: 'LENTE: regresión del idioma base y robustez de Polylang. Las páginas, plantillas y fichas del idioma base no cambiaron de comportamiento (cabecera, pie, menús, alt, filtros por REST en su idioma); las condiciones del Theme Builder siguen solo en las plantillas del idioma base y el kit no tiene idioma; el código del hijo y los mu-plugins de idioma no tienen fallos (firmas de hooks, recursión en get_post_metadata, is_admin() dentro de admin-ajax, funciones pll_* antes de que cargue Polylang); simula en memoria (con pre_wp_mail) un envío de formulario del idioma ' + IDIOMA2 + '; los documentos del idioma ' + IDIOMA2 + ' cargan sin ajustes nulos.' },
  ],
  cambios: [
    { key: 'regresion', prompt: 'LENTE: regresión de los últimos cambios en TODO el sitio. Cambios: ' + (A.cambios || 'no descritos: pídelos antes de lanzar este preset') + '. En cada ruta (' + RUTAS + '): HTTP 200, un solo <main id="content"> en su sitio y ningún segundo id="content"; lo que tocaron los cambios sigue funcionando (lee el código nuevo y pruébalo en solo lectura); el CSS nuevo gana o pierde especificidad donde debe frente al CSS del kit servido (calcúlala).' },
    { key: 'codigo', prompt: 'LENTE: corrección del código nuevo (PHP y JS del hijo, mu-plugins, scripts). Hooks y prioridades contra el código de los plugins en el servidor; casos límite (parámetros tipo array en la URL, elementos que faltan, valores vacíos); avisos PHP (sonda con captura de errores); lo que se rompería tras actualizar Elementor sin una guarda (herencia de clases internas, APIs privadas); salida sin escapar y entradas sin sanear.' },
  ],
}

const FORMULARIOS = { key: 'formularios', prompt: 'LENTE: formularios, siempre en seco. Cada campo con etiqueta programática (label[for] = id), required donde lo pide el prototipo, type=email en los correos, sin ids duplicados entre formularios, el campo trampa oculto y no enfocable, el campo de tiempo no enfocable; mensajes de éxito y error anunciados (role=alert) y en el idioma de la página; foco visible (especificidad frente al CSS que genera Elementor); el script de tiempo solo donde hay formulario y su valor sobrevive al reset tras un envío; el filtro de validación del hijo (prioridad frente al del campo trampa; el campo de tiempo no llega al correo ni a Envíos); inyección de cabeceras por Reply-To. Simula el envío SOLO con pre_wp_mail y revisa asunto, cuerpo y cabeceras (From, Sender, Reply-To). No reportes la ausencia de límite de envíos.' }

let LENTES = lista(A.lentes).length ? lista(A.lentes) : (PRESETS[PRESET] || PRESETS.pagina)
if (!lista(A.lentes).length && PRESET === 'pagina' && A.formularios) LENTES = LENTES.concat([FORMULARIOS])

const HALLAZGOS = {
  type: 'object',
  properties: {
    findings: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          severity: { type: 'string', enum: ['critical', 'high', 'medium', 'low'] },
          title: { type: 'string' },
          where: { type: 'string', description: 'archivo:línea, URL + selector o documento #id + id de elemento' },
          evidence: { type: 'string', description: 'la prueba reproducible: comando + salida o fragmento literal' },
          suggested_fix: { type: 'string' },
        },
        required: ['severity', 'title', 'where', 'evidence', 'suggested_fix'],
      },
    },
    checked: { type: 'string', description: 'qué comprobaste de verdad (para distinguir «sin hallazgos» de «no revisado»)' },
  },
  required: ['findings', 'checked'],
}

const VEREDICTO = {
  type: 'object',
  properties: {
    real: { type: 'boolean' },
    reproduced_how: { type: 'string' },
    corrected_severity: { type: 'string', enum: ['critical', 'high', 'medium', 'low', 'none'] },
    notes: { type: 'string' },
    better_fix: { type: 'string' },
  },
  required: ['real', 'reproduced_how', 'corrected_severity', 'notes'],
}

const opcionesAgente = extra => (A.agentType ? Object.assign({ agentType: A.agentType }, extra) : extra)

phase('Buscar')
const porLente = await parallel(LENTES.map(l => () =>
  agent(CONTEXTO + '\n\n' + l.prompt + '\n\nDevuelve como máximo ' + MAX_H + ' hallazgos, el más grave primero. Lista vacía si no hay nada concreto, y di en "checked" qué revisaste.',
    opcionesAgente({ label: 'buscar:' + l.key, phase: 'Buscar', schema: HALLAZGOS }))
    .then(r => ({ key: l.key, checked: r ? r.checked : 'el agente no devolvió resultado', findings: r ? r.findings : [] }))
))

// Barrera justificada: hay que ordenar y deduplicar TODOS los hallazgos antes de elegir cuáles se verifican.
const RANGO = { critical: 0, high: 1, medium: 2, low: 3 }
const revisado = porLente.filter(Boolean).map(r => ({ lente: r.key, checked: r.checked, hallazgos: r.findings.length }))
const vistos = new Set()
const todos = porLente.filter(Boolean)
  .flatMap(r => r.findings.map((f, i) => Object.assign({ id: r.key + '-' + (i + 1), lens: r.key }, f)))
  .sort((a, b) => RANGO[a.severity] - RANGO[b.severity])
  .filter(f => {
    const clave = (f.where + '|' + f.title.slice(0, 40)).toLowerCase().replace(/\s+/g, ' ')
    if (vistos.has(clave)) return false
    vistos.add(clave)
    return true
  })
const aVerificar = todos.slice(0, MAX_V)
const sinVerificar = todos.slice(MAX_V)
if (sinVerificar.length) log('Se verifican ' + MAX_V + ' de ' + todos.length + '; SIN verificar: ' + sinVerificar.map(f => f.id + ' ' + f.title).join(' | '))
if (!todos.length) log('Ningún buscador devolvió hallazgos; revisa "revisado" para ver qué se comprobó.')

phase('Verificar')
const verificados = await parallel(aVerificar.map(f => () =>
  agent(CONTEXTO + '\n\nEres un ESCÉPTICO independiente. Un revisor (lente "' + f.lens + '") afirma el defecto de abajo. Intenta REFUTARLO reproduciéndolo desde cero: curl de la URL, lectura del archivo en la línea citada o sonda de solo lectura. Marca real=false si no lo reproduces, si está en «conocido y aceptado», si es idéntico al comportamiento del prototipo (no es una regresión) o si la evidencia es falsa. Ante la duda, real=false. Corrige la gravedad si está inflada y, si ves un arreglo mejor o más acotado, dalo en better_fix.\n\nHALLAZGO ' + f.id + ' (' + f.severity + '): ' + f.title + '\nDónde: ' + f.where + '\nEvidencia: ' + f.evidence + '\nArreglo propuesto: ' + f.suggested_fix,
    opcionesAgente({ label: 'verificar:' + f.id, phase: 'Verificar', schema: VEREDICTO }))
    .then(v => Object.assign({}, f, { verdict: v }))
))

// Un escéptico que falla no refuta nada: su hallazgo pasa a «sin verificar», nunca a refutado.
const salida = aVerificar.map((f, i) => verificados[i] || Object.assign({}, f, { verdict: null }))
const confirmed = salida.filter(f => f.verdict && f.verdict.real)
const refuted = salida.filter(f => f.verdict && !f.verdict.real).map(f => ({ id: f.id, title: f.title, why: f.verdict.notes }))
const fallidos = salida.filter(f => !f.verdict)
if (fallidos.length) log('Escépticos sin veredicto (quedan sin verificar): ' + fallidos.map(f => f.id).join(', '))
const noVerificados = sinVerificar.concat(fallidos)
const lineas = salida.map(f => f.id + ' [' + f.severity + '→' + (f.verdict ? f.verdict.corrected_severity : '?') + '] real=' + (f.verdict ? f.verdict.real : '?') + ' :: ' + f.title)

return {
  pagina: A.pagina,
  url: A.url,
  preset: lista(A.lentes).length ? 'personalizado' : PRESET,
  resumen: todos.length + ' propuestos · ' + (aVerificar.length - fallidos.length) + ' verificados · ' + confirmed.length + ' confirmados · ' + noVerificados.length + ' sin verificar',
  revisado,
  lineas,
  confirmed,
  refuted,
  unverified: noVerificados.map(f => ({ id: f.id, severity: f.severity, title: f.title, where: f.where })),
}
