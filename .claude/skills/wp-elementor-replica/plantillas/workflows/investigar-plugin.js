export const meta = {
  name: 'investigar-plugin',
  description: 'Investigación de solo lectura del código de los plugins instalados (archivo:línea y sondas en memoria) antes de elegir enfoque, con un contraste independiente de cada afirmación',
  whenToUse: 'Antes de construir algo con incertidumbre: formularios, Loop Grid y filtros, Theme Builder con CPT e idiomas, etiquetas dinámicas V4, login propio, 404, cortina, Yoast por script',
  phases: [
    { title: 'Investigar', detail: 'una pregunta por agente: código fuente + sondas que no guardan' },
    { title: 'Contrastar', detail: 'otro agente comprueba cada afirmación en el código citado' },
  ],
}

/*
 * investigar-plugin.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Por cada pregunta (en pipeline): un agente lee el código del plugin INSTALADO en el servidor (o una copia
 *   local de la misma versión) y ejecuta sondas de solo lectura; devuelve la recomendación en la primera línea,
 *   los bloqueos arriba y la evidencia debajo (archivo:línea). Otro agente abre cada cita y la confirma o la
 *   refuta. Si se pasa «salida», escribe research-<key>.md con ese formato.
 *   Por qué: así se descubrieron antes de construir, en el caso de referencia, la taxonomía sin guiones y el id
 *   de 7 caracteres del filtro de Loop Grid, y los problemas del formulario atómico e-form (se usó el
 *   formulario de Pro). Y el orquestador verificó en el código las APIs que iba a usar, porque una
 *   investigación puede citar mal.
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/investigar-plugin.js', args: {
 *     contexto: 'Vamos a construir <qué> con <widgets/plugins>. Requisitos: …',
 *     preguntas: [ { key: 'loop-grid-filtro', pregunta: '1. JSON exacto del widget … 2. … Da JSON o PHP listo para copiar.' },
 *                  { key: 'dinamicas-v4', pregunta: '…' } ],
 *     sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', sondas: '<carpeta local para sondas>', prefijo: '<PREFIJO>',
 *     stack: 'WP x · Elementor x (V4) · Pro x · Polylang Pro x', salida: '<scripts>/<tema>' } })
 *   Obligatorios: contexto, preguntas. Sin sshAlias/wpPath hay que pasar «fuentes» (ruta local del código de
 *   los plugins, de la MISMA versión instalada).
 *
 * ESTADO
 *   Generalizado a partir de los workflows reales orangeology-research y contacto-forms-research (probados en
 *   HomeMaker) y del patrón research-*.md con recomendación arriba. La fase de contraste es un añadido del kit
 *   (en el caso la hizo el orquestador a mano). Esta versión parametrizada no se ha ejecutado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Solo lectura por instrucción. Las sondas quedan en ~/tmp-<PREFIJO>-research/ del servidor (no se borran: se
 *   avisa al usuario). Los números de línea cambian entre versiones: revalídalos tras actualizar el plugin.
 */

const A = args || {}
const falta = ['contexto', 'preguntas'].filter(k => !A[k])
if (falta.length) throw new Error('investigar-plugin: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const PREGUNTAS = lista(A.preguntas)
if (PREGUNTAS.some(p => !p || !p.key || !p.pregunta)) throw new Error('investigar-plugin: cada pregunta necesita key y pregunta')
if (!(A.sshAlias && A.wpPath) && !A.fuentes) throw new Error('investigar-plugin: hace falta sshAlias + wpPath o una copia local de los plugins en «fuentes»')
const P = A.prefijo || '<PREFIJO>'
const TMP = '~/tmp-' + P + '-research/'

const ACCESO = A.sshAlias && A.wpPath
  ? 'ACCESO: ssh ' + A.sshAlias + " '<comando>'; WordPress en --path=" + A.wpPath + ' (plugins en wp-content/plugins). WP-CLI disponible. ' +
    (A.sondas ? 'Sondas PHP: escríbelas en local en ' + A.sondas + ', súbelas con scp a ' + TMP + ' y ejecútalas con "wp --path=' + A.wpPath + ' eval-file ' + TMP + 'x.php --user=1"; nunca PHP inline por SSH.' : 'Sin carpeta de sondas: solo lectura de código y comandos wp de consulta.')
  : 'CÓDIGO: copia local de los plugins en ' + A.fuentes + ' (misma versión que la instalada). Sin acceso al servidor: no puedes ejecutar sondas.'

const COMUN = `
CONTEXTO: ${A.contexto}
STACK: ${A.stack || 'ver el CLAUDE.md del proyecto'}.
${ACCESO}
SOLO LECTURA ESTRICTA: no crees, cambies ni borres posts, opciones, metas ni archivos del sitio; no guardes ningún documento de Elementor; nada de "rm". Si una sonda necesita tocar un camino de envío de correo, córtalo antes con add_filter('pre_wp_mail', fn() => true). No leas ni imprimas wp-config.php, .env, .my.cnf ni volcados *.sql. No uses herramientas de navegador. Si tu Bash corre en sandbox, las llamadas con red necesitan dangerouslyDisableSandbox: true.
MÉTODO: prefiere las fuentes sin minificar (PHP de modules/ e includes/, JS sin .min). Cita fragmentos cortos y exactos con archivo:línea. Si algo no se puede determinar, dilo en no_determinado en vez de suponer. Tu respuesta la usará otra persona para escribir scripts de construcción: claves de ajustes exactas, formas JSON exactas, nombres de clases, hooks y funciones exactos, y fragmentos listos para copiar.`

const INVESTIGACION = {
  type: 'object',
  properties: {
    recomendacion: { type: 'string', description: 'una o dos frases: qué hacer; va la primera en el informe' },
    bloqueos: { type: 'array', items: { type: 'string' }, description: 'lo que hay que resolver antes de escribir los scripts' },
    hechos: {
      type: 'array',
      items: {
        type: 'object',
        properties: { afirmacion: { type: 'string' }, evidencia: { type: 'string', description: 'archivo:línea o comando + salida' }, fragmento: { type: 'string' } },
        required: ['afirmacion', 'evidencia'],
      },
    },
    recetas: { type: 'string', description: 'JSON o PHP listo para copiar' },
    no_determinado: { type: 'array', items: { type: 'string' } },
    archivo: { type: 'string', description: 'ruta del research-<key>.md escrito, o vacío' },
  },
  required: ['recomendacion', 'bloqueos', 'hechos', 'recetas', 'no_determinado', 'archivo'],
}
const CONTRASTE = {
  type: 'object',
  properties: {
    comprobados: { type: 'integer' },
    refutados: { type: 'array', items: { type: 'object', properties: { afirmacion: { type: 'string' }, porque: { type: 'string' } }, required: ['afirmacion', 'porque'] } },
    notas: { type: 'string' },
  },
  required: ['comprobados', 'refutados', 'notas'],
}

phase('Investigar')
const resultados = await pipeline(
  PREGUNTAS,
  q => agent(COMUN + '\n\nPREGUNTA «' + q.key + '»:\n' + q.pregunta +
    (A.salida ? '\n\nEscribe además ' + A.salida + '/research-' + q.key + '.md (solo ese archivo): primera línea = la recomendación; debajo, los bloqueos («Hay N bloqueos que resolver antes de escribir los scripts»); después la evidencia numerada con archivo:línea, las recetas y lo no determinado. Sin rutas del servidor que identifiquen al cliente más allá de wp-content/.' : ''),
  { label: 'investigar:' + q.key, phase: 'Investigar', schema: INVESTIGACION }),
  (inv, q) => {
    if (!inv) return { key: q.key, error: 'el agente no devolvió resultado' }
    if (!inv.hechos.length) return { key: q.key, investigacion: inv, contraste: { comprobados: 0, refutados: [], notas: 'sin hechos que contrastar' } }
    return agent(COMUN + `

Eres un VERIFICADOR independiente. Abre cada cita de abajo en el código (archivo y línea) y comprueba que dice lo que se afirma y que aplica a la versión instalada. Refuta lo que no se sostenga (cita mal, versión distinta, conclusión que no se deduce). Revisa también que las recetas usan exactamente las claves y funciones citadas.

RECOMENDACIÓN: ${inv.recomendacion}
HECHOS:
${JSON.stringify(inv.hechos, null, 1)}
RECETAS:
${inv.recetas}`, { label: 'contrastar:' + q.key, phase: 'Contrastar', schema: CONTRASTE })
      .then(c => ({ key: q.key, investigacion: inv, contraste: c }))
  },
)

const out = resultados.map((r, i) => r || { key: PREGUNTAS[i].key, error: 'falló la cadena' })
const conRefutados = out.filter(r => r.contraste && r.contraste.refutados.length)
if (conRefutados.length) log('Con afirmaciones refutadas (revisa antes de construir): ' + conRefutados.map(r => r.key).join(', '))
return {
  resumen: out.map(r => r.key + ': ' + (r.error ? 'ERROR ' + r.error : r.investigacion.recomendacion + (r.investigacion.bloqueos.length ? ' · BLOQUEOS: ' + r.investigacion.bloqueos.length : '') + (r.contraste ? ' · refutadas: ' + r.contraste.refutados.length : ''))),
  resultados: out,
}
