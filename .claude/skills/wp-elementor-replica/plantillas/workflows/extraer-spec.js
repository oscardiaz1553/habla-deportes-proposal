export const meta = {
  name: 'extraer-spec',
  description: 'Mide el prototipo renderizado con Chrome headless propio y escribe un spec-prototipo.md por página con la plantilla del kit; un escéptico re-mide una muestra y se corrige lo que no cuadre',
  whenToUse: 'Antes de construir una o varias páginas (F3–F10), con el prototipo servido por su único servidor de desarrollo ya arrancado',
  phases: [
    { title: 'Medir', detail: 'geometría y estilos computados por ancho, sin navegador compartido' },
    { title: 'Redactar', detail: 'spec-prototipo.md con la plantilla spec-seccion.md' },
    { title: 'Verificar', detail: 'un escéptico re-mide una muestra de cifras de la spec' },
    { title: 'Corregir', detail: 'solo si hubo discrepancias' },
  ],
}

/*
 * extraer-spec.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Por cada página (en pipeline, sin esperar a las demás): mide el prototipo RENDERIZADO a los anchos
 *   pedidos con un Chrome headless propio (qa-medir.mjs + un volcado de estilos computados), redacta
 *   <salida>/spec-prototipo.md siguiendo ../spec-seccion.md, y un escéptico vuelve a medir una
 *   muestra de cifras; si algo no cuadra, se corrige la spec y se anota.
 *   Por qué medir y no leer el código: en el caso de referencia más de 60 usos de clases de peso del
 *   prototipo (font-600/700/800) no existen en Tailwind v4 y caían en silencio a 400. Replicar el código
 *   habría replicado pesos que el cliente nunca vio.
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/extraer-spec.js', args: {
 *     paginas: [ { nombre: 'contacto', ruta: '/contact/', codigo: ['src/app/contact/page.tsx', 'src/lib/copy.ts'],
 *                  salida: '<scripts>/contacto/spec-prototipo.md', anchosExtra: [1024] },
 *                { nombre: 'cabecera-pie', ruta: '/', codigo: ['src/components/Navbar.tsx', 'src/components/Footer.tsx'],
 *                  salida: '<scripts>/cabecera-pie/spec-prototipo.md', anchosExtra: [1100, 900], selector: 'header, footer' } ],
 *     prototipoUrl: 'http://localhost:3000', prototipo: '<ruta del código del prototipo>', kit: '<HOME>/.claude',
 *     dirMedicion: '~/qa-headless', revelar: '.reveal,.reveal-zoom', secciones: 'main > section',
 *     idioma: '<PREFIJO>-lang=es', anchos: [1440, 1280, 768, 375], muestra: 10,
 *     tokens: { 'tinta': '#……', 'tinta-suave': '#……' } } })
 *   Obligatorios: paginas (con nombre, ruta y salida), prototipoUrl, prototipo, kit (carpeta que contiene
 *   skills/: <HOME>/.claude para la copia instalada).
 *   Requisitos: el prototipo lo sirve UN servidor de desarrollo ya arrancado (el workflow no arranca
 *   servidores ni hace builds: en el caso, varios servidores vivos congelaron el Mac) y dirMedicion (por
 *   defecto ~/qa-headless) tiene puppeteer-core instalado; qa-medir.mjs se copia allí desde
 *   <kit>/skills/wp-qa/scripts/ si falta (preparación y uso en <kit>/skills/wp-qa/scripts/README.md).
 *   Si tokens no se pasa, los agentes los sacan de las variables CSS del prototipo.
 *
 * ESTADO
 *   Nuevo del kit, NO probado: en HomeMaker las specs las midió el orquestador en su navegador
 *   (fragmento R(el) + getComputedStyle, entradas forzadas). Aquí se hace con Chrome headless porque los
 *   subagentes no deben usar el navegador de la sesión. qa-medir.mjs tiene su propio estado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Solo lee el prototipo. Escribe la spec en «salida» (sobrescribe si existe: haz commit antes) y los
 *   JSON y scripts de medición en dirMedicion. Una página cuyo prototipo no responda sale como no
 *   verificable, sin spec inventada.
 */

const A = args || {}
const falta = ['paginas', 'prototipoUrl', 'prototipo', 'kit'].filter(k => !A[k])
if (falta.length) throw new Error('extraer-spec: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const PAGINAS = lista(A.paginas)
const malas = PAGINAS.filter(p => !p || !p.nombre || !p.ruta || !p.salida)
if (malas.length) throw new Error('extraer-spec: cada página necesita nombre, ruta y salida')
const DIRM = A.dirMedicion || '~/qa-headless'
const ANCHOS = lista(A.anchos).length ? lista(A.anchos) : [1440, 1280, 768, 375]
const MUESTRA = A.muestra || 10
const TOKENS = A.tokens ? JSON.stringify(A.tokens) : 'no se pasaron: sácalos de las variables CSS globales del prototipo (p. ej. :root o @theme) y nómbralos igual que allí'

const BASE = `
CONTEXTO: se va a replicar al píxel un prototipo en WordPress + Elementor 4. La spec es la ÚNICA fuente de medidas para construir: se mide lo RENDERIZADO (getBoundingClientRect, getComputedStyle), nunca se deduce de las clases del código.
PROTOTIPO: código en ${A.prototipo}; servido en ${A.prototipoUrl} por un único servidor de desarrollo que ya está arrancado.
MEDICIÓN: Chrome headless propio con puppeteer desde ${DIRM} (qa-medir.mjs: original en ${A.kit}/skills/wp-qa/scripts/, preparación y uso en ${A.kit}/skills/wp-qa/scripts/README.md; se ejecuta desde ${DIRM} porque Node resuelve puppeteer-core desde la carpeta del script). Entradas animadas forzadas a su estado final (selector: ${A.revelar || 'el que use el prototipo; léelo en su código'}), movimiento reducido emulado, idioma fijado (${A.idioma || 'el idioma base'}), secciones = ${A.secciones || 'main section'}.
TOKENS DE COLOR: ${TOKENS}. En la spec, los colores van por token; hex solo si no hay token.
REGLAS: no uses herramientas de navegador de la sesión (mcp__Claude_Browser__*, Claude in Chrome, computer-use): abren pestañas propias y una pestaña oculta pausa las animaciones. No arranques servidores, no hagas builds, no instales paquetes: si ${A.prototipoUrl} no responde o falta el entorno de medición, dilo y para. No modifiques el código del prototipo. Escribe solo en ${DIRM} y en la ruta de salida de la spec.`

const MEDIDAS = {
  type: 'object',
  properties: {
    verificable: { type: 'boolean' },
    motivo: { type: 'string' },
    json_geometria: { type: 'string', description: 'ruta del JSON de qa-medir.mjs' },
    json_estilos: { type: 'string', description: 'ruta del JSON de estilos computados por sección y elemento' },
    cabecera_fija: { type: 'string', description: 'alto de la cabecera por ancho' },
    ancho_util_1440: { type: 'string' },
    secciones: { type: 'integer' },
    anomalias: { type: 'array', items: { type: 'string' } },
  },
  required: ['verificable', 'motivo', 'json_geometria', 'json_estilos', 'secciones', 'anomalias'],
}
const REDACCION = {
  type: 'object',
  properties: {
    salida: { type: 'string' },
    secciones: { type: 'integer' },
    dudas: { type: 'array', items: { type: 'string' }, description: 'valores que no se pudieron medir o que dependen de un estado no reproducible' },
    fuera_de_token: { type: 'array', items: { type: 'string' }, description: 'colores sin token' },
  },
  required: ['salida', 'secciones', 'dudas', 'fuera_de_token'],
}
const COMPROBACION = {
  type: 'object',
  properties: {
    comprobados: { type: 'integer' },
    discrepancias: { type: 'array', items: { type: 'object', properties: { linea: { type: 'string' }, en_spec: { type: 'string' }, medido: { type: 'string' }, como: { type: 'string' } }, required: ['linea', 'en_spec', 'medido', 'como'] } },
  },
  required: ['comprobados', 'discrepancias'],
}
const ARREGLO = {
  type: 'object',
  properties: { corregidas: { type: 'integer' }, notas: { type: 'string' } },
  required: ['corregidas', 'notas'],
}

const anchosDe = p => ANCHOS.concat(lista(p.anchosExtra)).filter((w, i, a) => a.indexOf(w) === i)

const medir = (p, i) => agent(BASE + `

PÁGINA: ${p.nombre} · ruta ${p.ruta}${p.selector ? ' · mide SOLO: ' + p.selector : ''} · código: ${lista(p.codigo).join(', ') || 'búscalo en ' + A.prototipo}.
TAREA (solo lectura del prototipo):
1. Comprueba que ${A.prototipoUrl}${p.ruta} responde y que ${DIRM}/node_modules/puppeteer-core existe; si falta ${DIRM}/qa-medir.mjs, cópialo desde ${A.kit}/skills/wp-qa/scripts/ (cp -n). Si algo sigue faltando, verificable=false con el motivo.
2. Geometría: cd ${DIRM} && QA_REVELAR='${A.revelar || ''}' QA_SECCIONES='${p.selector || A.secciones || 'main section'}' QA_IDIOMA='${A.idioma || ''}' node qa-medir.mjs proto-spec-${p.nombre}.json ${A.prototipoUrl} "${p.ruta}" ${anchosDe(p).join(',')}
3. Estilos computados: escribe ${DIRM}/spec-estilos-${p.nombre}.mjs partiendo de qa-medir.mjs (mismo arranque, viewport, movimiento reducido, recorrido de scroll y entradas forzadas) que, por ancho y por sección, vuelque de la sección y de sus descendientes visibles: caja (x, y + scrollY, ancho, alto), padding, gap, display, grid-template-columns, flex-direction, align-items, justify-content, max-width, margin-top, border, border-radius, box-shadow, background-color, font-family, font-size, line-height, font-weight, letter-spacing, text-transform, color, opacity, transition y animation; en imágenes, proporción y object-fit; en campos de formulario, alto y placeholder. Guárdalo en ${DIRM}/proto-estilos-${p.nombre}.json.
4. Estados que la medición estática no ve (hover, foco, activo, transiciones, entradas escalonadas, carruseles): léelos en el código de la página y sus componentes y anótalos como «del código» en anomalias si no los pudiste medir con page.hover().
5. Anota la cabecera fija por ancho, el ancho útil a 1440 (con barra de scroll suele ser 1425) y cualquier anomalía (p. ej. «columnas sin revelar: +28 px en su top»).`, { label: 'medir:' + p.nombre, phase: 'Medir', schema: MEDIDAS })
  .then(m => ({ pagina: p, medidas: m }))

const redactar = r => {
  if (!r || !r.medidas || !r.medidas.verificable) return Object.assign({}, r, { estado: 'no-verificable' })
  const p = r.pagina
  return agent(BASE + `

TAREA: escribe ${p.salida} siguiendo EXACTAMENTE la plantilla ${A.kit}/skills/wp-elementor-replica/plantillas/spec-seccion.md (léela entera; borra su bloque de instrucciones y las líneas de ayuda que empiezan por «>»).
Datos medidos: ${r.medidas.json_geometria} y ${r.medidas.json_estilos}. Cabecera fija: ${r.medidas.cabecera_fija || 'ver JSON'}. Anomalías: ${JSON.stringify(r.medidas.anomalias)}.
- Prosa telegráfica por ancho y sección, con cifras (redondeo a 0,1 px) y colores por token; nada de cifras que no salgan de los JSON o del código de estados.
- Sección «Contenido y enlaces»: textos y alt literales con su origen (archivo:clave) de ${lista(p.codigo).join(', ') || A.prototipo}.
- Sección «Traducción a V4»: propuesta de elementos y de qué va al tema hijo; marca las clases como «por confirmar contra las existentes».
- Deja vacía la tabla de «Verificación» (se rellena tras construir).
- Lo que no pudiste medir va a dudas; los colores sin token, a fuera_de_token.`, { label: 'redactar:' + p.nombre, phase: 'Redactar', schema: REDACCION })
    .then(d => Object.assign({}, r, { redaccion: d }))
}

const verificar = r => {
  if (!r || !r.redaccion) return r
  const p = r.pagina
  return agent(BASE + `

Eres un ESCÉPTICO. Lee ${r.redaccion.salida} y elige ${MUESTRA} cifras repartidas entre secciones y anchos (alto de sección, padding, gap, tamaño/interlineado, color, radio). Vuelve a medir cada una DESDE CERO con tu propio page.evaluate en Chrome headless (desde ${DIRM}, sin reutilizar los JSON del medidor) en ${A.prototipoUrl}${p.ruta}. Tolerancia: 1 px en medidas; exacto en colores y pesos. Devuelve las discrepancias con la línea de la spec, lo que dice, lo que mediste y cómo.`, { label: 'verificar:' + p.nombre, phase: 'Verificar', schema: COMPROBACION })
    .then(v => Object.assign({}, r, { comprobacion: v }))
}

const corregir = r => {
  if (!r || !r.comprobacion || !r.comprobacion.discrepancias.length) return r
  const p = r.pagina
  return agent(BASE + `

TAREA: corrige en ${p.salida} las líneas con discrepancias confirmadas (vuelve a medir si las dos mediciones no coinciden entre sí) y añade al final de la spec una línea: «Spec verificada por muestreo: N cifras, M corregidas». No toques lo que no está en la lista.

DISCREPANCIAS:
${JSON.stringify(r.comprobacion.discrepancias, null, 1)}`, { label: 'corregir:' + p.nombre, phase: 'Corregir', schema: ARREGLO })
    .then(c => Object.assign({}, r, { correccion: c }))
}

phase('Medir')
const resultados = await pipeline(PAGINAS, (p, _o, i) => medir(p, i), redactar, verificar, corregir)

const salida = resultados.map((r, i) => {
  if (!r) return { pagina: PAGINAS[i].nombre, estado: 'fallo del agente: repetir' }
  if (r.estado === 'no-verificable') return { pagina: r.pagina.nombre, estado: 'no-verificable', motivo: r.medidas ? r.medidas.motivo : 'sin medidas' }
  return {
    pagina: r.pagina.nombre,
    estado: r.comprobacion ? (r.comprobacion.discrepancias.length ? 'escrita-y-corregida' : 'escrita-y-verificada') : 'escrita-sin-verificar',
    spec: r.redaccion ? r.redaccion.salida : null,
    muestra: r.comprobacion ? r.comprobacion.comprobados : 0,
    discrepancias: r.comprobacion ? r.comprobacion.discrepancias.length : null,
    dudas: r.redaccion ? r.redaccion.dudas : [],
    fuera_de_token: r.redaccion ? r.redaccion.fuera_de_token : [],
  }
})
const noOk = salida.filter(s => s.estado !== 'escrita-y-verificada' && s.estado !== 'escrita-y-corregida')
if (noOk.length) log('Specs sin terminar: ' + noOk.map(s => s.pagina + ' (' + s.estado + ')').join(', '))
return { paginas: salida }
