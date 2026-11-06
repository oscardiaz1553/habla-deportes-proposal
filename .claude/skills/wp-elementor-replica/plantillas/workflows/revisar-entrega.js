export const meta = {
  name: 'revisar-entrega',
  description: 'Revisión adversarial de solo lectura del documento de entrega: hechos contra el inventario y el servidor, rutas del panel contra las traducciones instaladas y completitud; un escéptico por hallazgo',
  whenToUse: 'En F11, cuando el borrador del documento técnico de entrega está escrito y antes de generar el PDF final',
  phases: [
    { title: 'Revisar', detail: 'lentes: hechos, rutas del panel, entrega' },
    { title: 'Verificar', detail: 'un escéptico por hallazgo; por defecto, refutado' },
  ],
}

/*
 * revisar-entrega.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Tres revisores independientes leen el documento de entrega (HTML fuente) contra la verdad disponible:
 *   el inventario de solo lectura (inventario.json), el tema hijo bajado del servidor, el propio servidor y las
 *   traducciones instaladas. Cada hallazgo trae la cita exacta del documento, la evidencia y el texto de
 *   sustitución; un escéptico intenta refutarlo. Devuelve { confirmed, refuted, unverified }.
 *   Formato del documento y proceso completo: ../documento-entrega.md (en la copia instalada,
 *   ~/.claude/skills/wp-elementor-replica/plantillas/documento-entrega.md).
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/revisar-entrega.js', args: {
 *     documento: '<carpeta>/entrega.src.html', inventario: '<carpeta>/inventario.json', hijo: '<carpeta>/hijo',
 *     sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', locale: 'es_ES',
 *     decisionesAutor: ['el sitio final estará en https://<DOMINIO> con acceso en /<LOGIN_SLUG>',
 *       'sin detalles de hosting ni licencias', 'se asume que traducciones y SEO están terminados',
 *       'la contraseña es un campo en blanco que rellena el usuario', 'ninguna URL de staging',
 *       'la tabla de plugins muestra solo el conjunto de producción'],
 *     maxVerificar: 7 } })
 *   Obligatorios: documento, inventario. Opcional: agentType (p. ej. 'qa-elementor', si está instalado).
 *
 * ESTADO
 *   Generalizado a partir del workflow real review-handover-doc (probado en HomeMaker: encontró rutas del panel que
 *   no existían con las traducciones instaladas y menús mal nombrados, que se corrigieron en el sitio y en el
 *   documento). Esta versión parametrizada no se ha ejecutado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Solo lectura. El inventario lleva destinatarios de correo: no se copia a ningún informe.
 */

const A = args || {}
const falta = ['documento', 'inventario'].filter(k => !A[k])
if (falta.length) throw new Error('revisar-entrega: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const MAX_V = A.maxVerificar || 7
const LOC = A.locale || 'el idioma del panel del cliente'
const opcionesAgente = extra => (A.agentType ? Object.assign({ agentType: A.agentType }, extra) : extra)

const CTX = `
Revisas un documento técnico formal de entrega y cierre de un sitio WordPress + Elementor 4 (fuente HTML: ${A.documento}; léelo entero).
VERDAD DISPONIBLE (SOLO LECTURA: no modifiques nada, ningún wp-cli que escriba, sin herramientas de navegador):
- Inventario de solo lectura del sitio: ${A.inventario} (plugins y versiones, mu-plugins, temas, documentos con idioma y traducciones, condiciones del Theme Builder, contenido dinámico, menús, formularios, recuentos).
- Tema hijo bajado del servidor: ${A.hijo || 'no indicado'}.
${A.sshAlias && A.wpPath ? '- El servidor: ssh ' + A.sshAlias + ' con WordPress en --path=' + A.wpPath + ', solo para leer (cat, grep, ls, wp eval-file de código que solo lee, con --user=1). Las traducciones instaladas están en wp-content/languages (incluidos .l10n.php, .mo y .po de los plugins).' : '- Sin acceso al servidor en esta revisión.'}
- No leas ni imprimas wp-config.php ni volcados. Si tu Bash corre en sandbox, las llamadas con red necesitan dangerouslyDisableSandbox: true.
DECIDIDO POR EL AUTOR (NO es un defecto): ${lista(A.decisionesAutor).join('; ') || 'nada declarado'}.
Reporta solo problemas concretos y verificables que confundirían al cliente o a quien mantenga el sitio: hechos falsos, rutas o etiquetas del panel equivocadas, procedimientos que no funcionan, contradicciones internas, faltas imprescindibles para una entrega ordenada y errores claros de lengua. Nada de estilo. Cada hallazgo: cita EXACTA del documento, evidencia (archivo:línea, clave del inventario o cadena de traducción) y el texto de sustitución exacto en español.`

const LENTES = [
  { key: 'hechos', prompt: 'LENTE: exactitud de los hechos. Comprueba cada afirmación (versiones, nombres de páginas, plantillas y menús, URL y patrones de URL, anclas, campos de formulario con sus obligatorios, asuntos, destinatarios, remitente, antispam, qué hace cada archivo del hijo y cada mu-plugin, campos del contenido dinámico y sus reglas de formato, comportamiento de los idiomas, sitemap y hreflang, recuentos) contra el inventario, el código del hijo y el servidor. Marca lo falso o lo exagerado.' },
  { key: 'rutas', prompt: 'LENTE: rutas del panel y procedimientos. El panel del cliente está en ' + LOC + '. Para cada instrucción del tipo «Menú → Submenú → Opción» o etiqueta entre comillas (núcleo de WordPress, Elementor y Elementor Pro, Polylang, Yoast, pantallas propias), verifica la etiqueta real y su ubicación con las traducciones instaladas (grep en wp-content/languages y en el código de los plugins; una traducción que no existe significa que el cliente ve la etiqueta en inglés). Comprueba que cada procedimiento funciona como se describe (cambiar destinatarios de formularios, crear una traducción, regenerar archivos de Elementor, dónde están los envíos, Theme Builder, orden del contenido dinámico, variables, modo de mantenimiento).' },
  { key: 'entrega', prompt: 'LENTE: calidad de la entrega. Léelo como el cliente que recibe el sitio y como quien lo mantendrá. Busca lo imprescindible que falta (sin añadir hosting ni licencias), contradicciones (con el estado de «Próximamente», entre secciones), casillas de la lista de cierre que no son ciertas hoy en el servidor, afirmaciones que el autor no puede garantizar y conviene suavizar, instrucciones confusas o arriesgadas y errores claros de lengua. Mantén el documento breve: propón solo añadidos imprescindibles.' },
]

const HALLAZGOS = {
  type: 'object',
  properties: {
    findings: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          severity: { type: 'string', enum: ['high', 'medium', 'low'] },
          title: { type: 'string' },
          quote: { type: 'string', description: 'texto exacto del documento' },
          evidence: { type: 'string' },
          replacement: { type: 'string', description: 'texto de sustitución exacto' },
        },
        required: ['severity', 'title', 'quote', 'evidence', 'replacement'],
      },
    },
  },
  required: ['findings'],
}
const VEREDICTO = {
  type: 'object',
  properties: { refuted: { type: 'boolean' }, reasoning: { type: 'string' }, better_replacement: { type: 'string' } },
  required: ['refuted', 'reasoning'],
}

phase('Revisar')
const revisiones = await parallel(LENTES.map(l => () =>
  agent(CTX + '\n\n' + l.prompt + '\n\nDevuelve como máximo 8 hallazgos, el más grave primero. Lista vacía si no hay nada concreto.',
    opcionesAgente({ label: 'revisar:' + l.key, phase: 'Revisar', schema: HALLAZGOS }))
    .then(r => (r ? r.findings.map((f, i) => Object.assign({ id: l.key + '-' + (i + 1), lens: l.key }, f)) : []))
))

// Barrera justificada: orden global por gravedad antes de elegir qué se verifica.
const RANGO = { high: 0, medium: 1, low: 2 }
const todos = revisiones.filter(Boolean).flat().sort((a, b) => RANGO[a.severity] - RANGO[b.severity])
const aVerificar = todos.slice(0, MAX_V)
const sinVerificar = todos.slice(MAX_V)
if (sinVerificar.length) log('Verificando ' + MAX_V + ' de ' + todos.length + '; SIN verificar: ' + sinVerificar.map(f => f.id + ' ' + f.title).join(' | '))

phase('Verificar')
const verificados = await parallel(aVerificar.map(f => () =>
  agent(CTX + '\n\nUn revisor afirma el problema de abajo. Intenta REFUTARLO comprobando el documento, el inventario, el código, el servidor y las traducciones instaladas. Ante la duda, o si el cambio no le importaría al cliente, refuted=true. Si es real, confírmalo y da el mejor texto de sustitución.\n\nHALLAZGO ' + f.id + ' (' + f.severity + ', ' + f.lens + '): ' + f.title + '\nCita: ' + f.quote + '\nEvidencia: ' + f.evidence + '\nSustitución propuesta: ' + f.replacement,
    opcionesAgente({ label: 'verificar:' + f.id, phase: 'Verificar', schema: VEREDICTO }))
    .then(v => Object.assign({}, f, { verdict: v }))
))

// Un escéptico que falla no refuta nada: su hallazgo pasa a «sin verificar», nunca a refutado.
const out = aVerificar.map((f, i) => verificados[i] || Object.assign({}, f, { verdict: null }))
const confirmados = out.filter(f => f.verdict && !f.verdict.refuted)
const fallidos = out.filter(f => !f.verdict)
if (fallidos.length) log('Escépticos sin veredicto (quedan sin verificar): ' + fallidos.map(f => f.id).join(', '))
return {
  resumen: todos.length + ' propuestos · ' + (aVerificar.length - fallidos.length) + ' verificados · ' + confirmados.length + ' confirmados · ' + (sinVerificar.length + fallidos.length) + ' sin verificar',
  confirmed: confirmados,
  refuted: out.filter(f => f.verdict && f.verdict.refuted).map(f => ({ id: f.id, title: f.title, why: f.verdict.reasoning })),
  unverified: sinVerificar.concat(fallidos),
  siguiente: [
    'Aplicar lo confirmado con sustituciones que fallan si la cita no es única (rep(a, b) con assert s.count(a) == 1).',
    'Si un hallazgo demuestra algo roto en el sitio (p. ej. un menú mal nombrado), arreglarlo en el sitio, coordinado con la sesión de construcción, además de en el documento.',
    'Regenerar el PDF y leer sus páginas antes de enviarlo.',
  ],
}
