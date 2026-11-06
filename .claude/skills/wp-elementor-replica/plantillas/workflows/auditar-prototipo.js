export const meta = {
  name: 'auditar-prototipo',
  description: 'Auditoría del prototipo antes de replicarlo: un agente por dimensión, un verificador adversarial por hallazgo y una síntesis que separa decisiones aprobadas de defectos',
  whenToUse: 'En F0, antes de construir: para decidir qué se replica tal cual y qué defectos del prototipo se corrigen en la réplica',
  phases: [
    { title: 'Analizar', detail: 'un agente por dimensión del diseño, solo lectura' },
    { title: 'Verificar', detail: 'un escéptico por hallazgo contra el código real' },
    { title: 'Sintetizar', detail: 'informe con procedencia, sin suposiciones' },
  ],
}

/*
 * auditar-prototipo.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Audita el CÓDIGO del prototipo por dimensiones (sistema visual, inicio, páginas internas, componentes,
 *   copy e idiomas, accesibilidad, responsive y rendimiento, fidelidad al encargo). Cada hallazgo pasa por un
 *   verificador que intenta refutarlo; una síntesis final escribe el informe con su procedencia.
 *   Objetivo: separar «decisión de diseño aprobada» (se replica aunque no guste) de «defecto» (se corrige en la
 *   réplica y se dice). Método completo: skill wp-elementor-replica, references/prototipo-a-spec.md §2.
 *
 * LO QUE ESTE WORKFLOW NO VE (hazlo tú en el navegador, en paralelo)
 *   Fallos de ejecución. En el caso de referencia la auditoría de código dio por bueno un componente de entradas
 *   animadas que, con scroll rápido, dejaba 4 de 13 bloques en opacity:0 para siempre y, sin JS, la página en blanco.
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/auditar-prototipo.js', args: {
 *     prototipo: '<ruta del código del prototipo>',
 *     documentos: ['<brief>.md', '<guía de estilo>.md', '<feedback del cliente>.md'],
 *     marca: 'localización del cliente: «<término>», no «<término corregido>»; tipografías aprobadas …',
 *     estructura: { estilos: 'src/app/globals.css', componentes: 'src/components', inicio: 'src/app/page.tsx',
 *                   internas: 'src/app/<ruta>/page.tsx', contenido: 'src/lib', publico: 'public' },
 *     maxVerificar: 20, salida: '<PROYECTO>/Auditoria-prototipo-<fecha>.md' } })
 *   Obligatorio: prototipo. estructura trae por defecto la de un Next.js con App Router + Tailwind (la del caso).
 *   dimensiones: [{ key, prompt }] sustituye las ocho de serie.
 *
 * ESTADO
 *   Generalizado a partir del workflow real analisis-diseno-homemaker (probado en HomeMaker: 134 hallazgos
 *   propuestos → 93 confirmados). Cambios del kit: dimensiones sin datos de la marca, verificación de TODOS los
 *   hallazgos hasta maxVerificar (el original verificó solo 12 por dimensión y dejó 38 sin verificar) con log de
 *   lo que quede fuera, y síntesis que no puede añadir notas operativas ni suposiciones (en el caso, la «nota
 *   operativa» del sintetizador era falsa). Esta versión parametrizada no se ha ejecutado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Solo lectura. Si se pasa «salida», el sintetizador escribe ese archivo (y nada más).
 */

const A = args || {}
if (!A.prototipo) throw new Error('auditar-prototipo: falta args.prototipo (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const R = A.prototipo
const E = Object.assign({ estilos: 'src/app/globals.css', componentes: 'src/components', inicio: 'src/app/page.tsx', internas: 'src/app/*/page.tsx', contenido: 'src/lib', publico: 'public' }, A.estructura || {})
const ruta = x => (String(x).indexOf('/') === 0 ? x : R + '/' + x)
const DOCS = lista(A.documentos)
const MAX_V = A.maxVerificar || 20

const BASE = `Proyecto: prototipo navegable que se va a replicar EXACTAMENTE en WordPress + Elementor 4. Antes de replicar hay que saber qué es decisión de diseño aprobada (se replica) y qué es defecto (se corrige en la réplica y se le dice al cliente).
Código: ${R}. Estilos y tokens: ${ruta(E.estilos)}. Componentes: ${ruta(E.componentes)}. Inicio: ${ruta(E.inicio)}. Páginas internas: ${ruta(E.internas)}. Contenido y copy: ${ruta(E.contenido)}. Recursos publicados: ${ruta(E.publico)}.
Documentos del encargo: ${DOCS.join(', ') || 'no indicados'}.
Reglas de marca conocidas: ${A.marca || 'ver el CLAUDE.md del proyecto'}.
Trabaja SOLO leyendo archivos (cat, sed -n, grep). NO modifiques nada. NO arranques el servidor de desarrollo ni hagas builds ni instales nada. No uses herramientas de navegador.
Sé concreto y verificable: cada hallazgo cita archivo y línea y el fragmento literal que lo demuestra. No inventes. Los hallazgos son problemas u oportunidades reales que notaría un diseñador senior o el cliente, no detalles de estilo de código. Si algo solo se puede comprobar ejecutando (animaciones, scroll, sin JS), dilo como «a medir en el navegador», no lo afirmes.`

const DIMENSIONES = lista(A.dimensiones).length ? lista(A.dimensiones) : [
  { key: 'sistema-visual', prompt: 'Audita el SISTEMA VISUAL: tokens de color, tipografía, escala de espaciado, radios, sombras y su consistencia. Lee entero ' + ruta(E.estilos) + ' y compáralo con la guía de estilo si existe. Busca: variables definidas y nunca usadas; colores sueltos en componentes que deberían ser tokens; escalas tipográficas inconsistentes entre páginas; clases de peso o de estilo que el framework NO genera (en Tailwind v4, font-600/700/800 no existen y el navegador pinta 400); pesos o itálicas que la fuente no tiene; más de una forma de hacer lo mismo (dos botones, dos tamaños de sección). Cuenta cuántos valores distintos hay de cada cosa.' },
  { key: 'inicio', prompt: 'Audita la PÁGINA DE INICIO: ' + ruta(E.inicio) + ' y los componentes que usa. Jerarquía del hero (vídeo o imagen, titular, llamada a la acción), orden narrativo, propósito y acción de cada sección, enlaces y botones que no llevan a ninguna parte o que llevan todos a la misma URL, y si el recorrido lleva a producto y a contacto. Compara con el brief y el feedback: ¿se implementó todo lo pedido?' },
  { key: 'paginas-internas', prompt: 'Audita las PÁGINAS INTERNAS (' + ruta(E.internas) + '): consistencia de cabeceras, ritmo de secciones, información que un comprador busca, utilidad real de los bloques interactivos (¿localizador, mapa o filtro decorativos?), formularios (campos, estados, validación, respuesta del envío) y rutas de detalle. Señala la página más débil y por qué.' },
  { key: 'componentes-interaccion', prompt: 'Audita COMPONENTES E INTERACCIÓN (' + ruta(E.componentes) + '): navegación móvil y estado activo, carruseles (¿varias implementaciones de lo mismo?), entradas animadas (¿respetan prefers-reduced-motion?, ¿qué pasa sin JS?, ¿pueden quedarse en opacity:0?), estados que faltan (hover, foco, activo, deshabilitado, vacío, carga), gestos táctiles y restos de wireframe vivos. Anota cada interacción y su posible equivalente editable en Elementor.' },
  { key: 'copy-idiomas', prompt: 'Audita COPY e IDIOMAS (' + ruta(E.contenido) + ' y su uso en las páginas): cobertura real de cada idioma (qué queda sin traducir o mezclado), las reglas de localización de la marca aplicadas en TODOS los textos, textos de relleno o Lorem, claims de producto (compáralos entre páginas y con lo que diga el encargo), contactos, teléfonos, redes o dominios inventados, metadatos repetidos en todas las páginas. Cita cada cadena literalmente.' },
  { key: 'accesibilidad', prompt: 'Audita ACCESIBILIDAD (WCAG 2.1 AA) leyendo el código. Calcula el contraste real de cada par de colores usado para texto (da el ratio numérico); textos sobre vídeo o imagen sin capa de protección; jerarquía de encabezados por página; alt vacíos, genéricos o duplicados; foco visible; teclado en carruseles y menú móvil; roles y aria; áreas táctiles; vídeos con autoplay y sin controles; etiquetas de formulario.' },
  { key: 'responsive-rendimiento', prompt: 'Audita RESPONSIVE y RENDIMIENTO. Breakpoints usados por página, valores fijos en px que rompan en móvil, desbordes horizontales probables, rejillas que no se reordenan, contenido solo de escritorio. Pesa los recursos (ls -la, du -h en ' + ruta(E.publico) + '): los 15 archivos más pesados; viabilidad del vídeo del hero en móvil; imágenes sin dimensiones o sin redimensionar; formatos; peso total del inicio.' },
  { key: 'fidelidad-encargo', prompt: 'Audita la FIDELIDAD AL ENCARGO y el estado del proyecto. Lee enteros los documentos del encargo y contrástalos con lo que existe hoy en el código: qué pedía y no está, qué se construyó de más, qué puntos del feedback siguen pendientes y qué contenido es relleno frente a contenido real del cliente. Describe también en qué estado de publicación está (cortinas, redirecciones, formularios) según los archivos del proyecto, sin suponer nada del servidor.' },
]

const HALLAZGOS = {
  type: 'object',
  properties: {
    resumen: { type: 'string', description: 'diagnóstico de 4–8 frases de esta dimensión' },
    fortalezas: { type: 'array', items: { type: 'string' } },
    hallazgos: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          titulo: { type: 'string' }, detalle: { type: 'string' }, archivo: { type: 'string' }, linea: { type: 'number' },
          evidencia: { type: 'string', description: 'fragmento literal del código o del copy' },
          severidad: { type: 'string', enum: ['alta', 'media', 'baja'] },
          tipo: { type: 'string', enum: ['defecto', 'decision-del-cliente', 'a-medir-en-navegador'] },
          recomendacion: { type: 'string' },
        },
        required: ['titulo', 'detalle', 'archivo', 'evidencia', 'severidad', 'tipo', 'recomendacion'],
      },
    },
  },
  required: ['resumen', 'fortalezas', 'hallazgos'],
}
const VEREDICTO = {
  type: 'object',
  properties: {
    confirmado: { type: 'boolean' },
    razon: { type: 'string' },
    correccion: { type: 'string', description: 'si es parcialmente cierto, la versión precisa' },
  },
  required: ['confirmado', 'razon'],
}
const RANGO = { alta: 0, media: 1, baja: 2 }

phase('Analizar')
log('Auditando ' + DIMENSIONES.length + ' dimensiones del prototipo en paralelo')
const porDimension = await pipeline(
  DIMENSIONES,
  d => agent(BASE + '\n\n=== TU DIMENSIÓN: ' + d.key + ' ===\n' + d.prompt, { label: 'analizar:' + d.key, phase: 'Analizar', schema: HALLAZGOS }),
  async (res, d) => {
    if (!res) return null
    const todos = (res.hallazgos || []).slice().sort((a, b) => RANGO[a.severidad] - RANGO[b.severidad])
    const revisar = todos.slice(0, MAX_V)
    const fuera = todos.slice(MAX_V)
    if (fuera.length) log(d.key + ': ' + todos.length + ' hallazgos; SIN verificar ' + fuera.length + ': ' + fuera.map(h => h.titulo).join(' | '))
    const veredictos = await parallel(revisar.map((h, i) => () =>
      agent(BASE + `

=== TU TAREA: REFUTAR UN HALLAZGO ===
Otro auditor afirma lo siguiente. Intenta REFUTARLO leyendo el código real: muchos hallazgos suenan plausibles y son falsos porque el auditor no vio una línea, un breakpoint, una clase, una traducción o un respaldo que sí existe.
Título: ${h.titulo}
Detalle: ${h.detalle}
Archivo: ${h.archivo}${h.linea ? ':' + h.linea : ''}
Evidencia que alega: ${h.evidencia}
Severidad propuesta: ${h.severidad} · Tipo: ${h.tipo}
Abre el archivo y su contexto. Si el problema está resuelto en otro sitio, está refutado. Si es real pero exagerado o mal atribuido, confirmado=true con la versión precisa en "correccion". Si solo se puede comprobar ejecutando, confirmado=false y dilo en la razón. Ante la duda sin poder comprobarlo, confirmado=false.`,
        { label: 'verificar:' + d.key + '#' + (i + 1), phase: 'Verificar', schema: VEREDICTO })
        .then(v => ({ h, v }))
    ))
    const ok = veredictos.filter(Boolean)
    return {
      dimension: d.key,
      resumen: res.resumen,
      fortalezas: res.fortalezas || [],
      confirmados: ok.filter(x => x.v && x.v.confirmado).map(x => Object.assign({}, x.h, { dimension: d.key, correccion: x.v.correccion || '', razonVerificacion: x.v.razon })),
      aMedir: ok.filter(x => x.v && !x.v.confirmado && x.h.tipo === 'a-medir-en-navegador').map(x => ({ titulo: x.h.titulo, archivo: x.h.archivo, razon: x.v.razon })),
      descartados: ok.filter(x => x.v && !x.v.confirmado && x.h.tipo !== 'a-medir-en-navegador').length,
      sinVerificar: fuera.map(h => ({ titulo: h.titulo, archivo: h.archivo, severidad: h.severidad })).concat(veredictos.map((x, i) => (x ? null : { titulo: revisar[i].titulo, archivo: revisar[i].archivo, severidad: revisar[i].severidad })).filter(Boolean)),
      propuestos: todos.length,
    }
  }
)

// Barrera justificada: la síntesis necesita todas las dimensiones para fusionar duplicados.
const dims = porDimension.filter(Boolean)
const procedencia = {
  dimensiones: dims.length + ' de ' + DIMENSIONES.length,
  propuestos: dims.reduce((n, d) => n + d.propuestos, 0),
  confirmados: dims.reduce((n, d) => n + d.confirmados.length, 0),
  descartados: dims.reduce((n, d) => n + d.descartados, 0),
  aMedirEnNavegador: dims.reduce((n, d) => n + d.aMedir.length, 0),
  sinVerificar: dims.reduce((n, d) => n + d.sinVerificar.length, 0),
}
log('Procedencia: ' + JSON.stringify(procedencia))

phase('Sintetizar')
const informe = await agent(BASE + `

=== TU TAREA: SÍNTESIS FINAL ===
Los auditores analizaron el prototipo y sus hallazgos pasaron un filtro adversarial. Resultados (JSON):
${JSON.stringify({ procedencia, dimensiones: dims.map(d => ({ dimension: d.dimension, resumen: d.resumen, fortalezas: d.fortalezas, aMedir: d.aMedir, sinVerificar: d.sinVerificar })), confirmados: dims.flatMap(d => d.confirmados) }, null, 1)}

Escribe el informe en ESPAÑOL para el responsable del proyecto, en Markdown, con este orden:
1. **Procedencia**: cómo se hizo y las cifras de arriba (propuestos, confirmados, descartados, a medir en el navegador, sin verificar).
2. **Veredicto** (5–8 frases): qué es el prototipo hoy, cuánto le falta y su mayor riesgo para la réplica.
3. **Lo que está bien (conservar)**.
4. **Decisiones del cliente que se replican tal cual** (claims, contrastes de marca, elecciones de diseño): se listan, no se corrigen.
5. **Defectos que se corrigen en la réplica**, por prioridad: «Bloquea el lanzamiento», «Importante antes de presentar al cliente», «Pulido». Fusiona duplicados entre dimensiones. Cada uno: qué pasa, dónde (archivo:línea) y el arreglo concreto en la réplica.
6. **A medir en el navegador** (lo que el código no demuestra).
7. **Lo que el cliente pidió y aún no está**.
8. **Siguientes pasos** (5), por impacto y esfuerzo.
REGLAS: usa SOLO hallazgos confirmados (y la lista «a medir» como tal). Antes de escribir, abre tú los archivos de los hallazgos de severidad alta y descarta los que no se sostengan, diciéndolo. NO añadas notas operativas, datos de servidores, hosts, accesos ni suposiciones que no salgan de un hallazgo confirmado. Sin relleno ni elogios vacíos.${A.salida ? '\nGuarda el informe en ' + A.salida + ' (solo ese archivo) y devuélvelo también como texto.' : '\nDevuelve solo el informe.'}`,
{ label: 'sintesis', phase: 'Sintetizar', effort: 'high' })

return {
  informe,
  procedencia,
  porDimension: dims.map(d => ({ dimension: d.dimension, propuestos: d.propuestos, confirmados: d.confirmados.length, descartados: d.descartados, aMedir: d.aMedir.length, sinVerificar: d.sinVerificar.length })),
}
