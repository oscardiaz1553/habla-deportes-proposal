export const meta = {
  name: 'disenar-pantalla',
  description: 'Pantalla especial sin diseño en el prototipo (404, acceso, próximamente): investigación de solo lectura, dos propuestas en paralelo y un juez que escribe la especificación final con código',
  whenToUse: 'En F10 o cuando haya que diseñar una pantalla que el prototipo no trae, manteniendo el lenguaje visual y las clases existentes',
  phases: [
    { title: 'Investigar', detail: 'hechos verificados del plugin y del tema, solo lectura' },
    { title: 'Proponer', detail: 'dos o más propuestas con ángulos distintos' },
    { title: 'Juzgar', detail: 'tabla de criterios, ganadora con injertos y spec final' },
  ],
}

/*
 * disenar-pantalla.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Investigación de solo lectura (código del plugin, opciones, curl) y propuestas de diseño corren a la vez; un
 *   juez compara las propuestas con una tabla de criterios, elige una, INJERTA lo mejor de las otras, lista lo
 *   descartado y por qué, calcula la tabla responsive y la de contrastes, y escribe final-<pantalla>.md con el
 *   copy en los dos idiomas, el árbol V4, las clases nuevas y el código completo en bloques ```php / ```css
 *   extraíbles. Después el constructor comprueba que existe cada clase reutilizada y construye con el ciclo
 *   de siempre (clases → sync → build idempotente → purga → curl y medición).
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/disenar-pantalla.js', args: {
 *     pantalla: '404',
 *     investigaciones: [ { key: '404', prompt: 'Tipo de documento y condición de la plantilla 404 del Theme Builder …' } ],
 *     angulos: [ { key: 'A-editorial', enfoque: 'sigue el patrón de cabecera de las páginas internas …' },
 *                { key: 'B-visual', enfoque: 'dos columnas con la imagen recortada de marca …' } ],
 *     diseno: 'Lenguaje visual: lee <componentes del prototipo>. Clases globales existentes: <archivo del volcado>. Medios: <ids>.',
 *     idiomas: ['<IDIOMA_BASE>', '<IDIOMA_2>'], salida: '<scripts>/404',
 *     sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', url: 'https://<STAGING_URL>', stack: '…' } })
 *   Obligatorios: pantalla, angulos (2 o más), diseno, salida.
 *
 * ESTADO
 *   Generalizado a partir del workflow real plan-404-login (probado en HomeMaker: el juez eligió B con cuatro
 *   injertos de A; la especificación del acceso corrigió seis errores de la propuesta). El paso de la
 *   investigación al juez es un añadido del kit. Esta versión parametrizada no se ha ejecutado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Solo lectura en el servidor. Escribe los research-*.md y final-<pantalla>.md en «salida». Encender una
 *   cortina o cambiar la URL de acceso NO es parte de este workflow: son decisiones del usuario.
 */

const A = args || {}
const falta = ['pantalla', 'angulos', 'diseno', 'salida'].filter(k => !A[k])
if (falta.length) throw new Error('disenar-pantalla: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const ANGULOS = lista(A.angulos)
if (ANGULOS.length < 2) throw new Error('disenar-pantalla: hacen falta al menos dos ángulos de diseño')
const INVS = lista(A.investigaciones)
const IDIOMAS = lista(A.idiomas).length ? lista(A.idiomas) : ['<IDIOMA_BASE>', '<IDIOMA_2>']

const RO = `SOLO LECTURA ESTRICTA: no modifiques archivos ni la base de datos; ningún wp-cli que escriba (ni opciones, ni posts, ni plugins); no uses herramientas de navegador. Permitido: leer archivos locales, curl a ${A.url || 'la URL pública del staging'}${A.sshAlias && A.wpPath ? ', ssh ' + A.sshAlias + ' en solo lectura (cat, grep, ls, wp option get, wp eval-file de código que solo lee) con WordPress en --path=' + A.wpPath : ''}. No leas ni imprimas wp-config.php, .env ni volcados. Si tu Bash corre en sandbox, las llamadas con red necesitan dangerouslyDisableSandbox: true. Stack: ${A.stack || 'ver el CLAUDE.md del proyecto'}. Si otra sesión está editando el tema hijo, solo lees sus archivos.`

const DISENO = 'CONTEXTO DE DISEÑO: ' + A.diseno + '\nLa pantalla tiene que parecer nativa junto a las demás páginas, construirse con elementos V4 y las clases globales EXISTENTES más, como mucho, unas pocas clases nuevas pequeñas, y tener copy en ' + IDIOMAS.join(' y ') + ' con la voz de la marca (sin chistes forzados).'

const RESUMEN = { type: 'object', properties: { archivo: { type: 'string' }, resumen: { type: 'string' }, dudas: { type: 'array', items: { type: 'string' } } }, required: ['archivo', 'resumen', 'dudas'] }
const PROPUESTA = {
  type: 'object',
  properties: {
    nombre: { type: 'string' },
    concepto: { type: 'string' },
    copy: { type: 'object', description: 'un objeto por idioma con cada texto' },
    estructura: { type: 'string', description: 'árbol: tipo de elemento V4 + clases globales + clave de texto + enlace; comportamiento en escritorio y móvil' },
    clases_nuevas: { type: 'string', description: 'clases nuevas con sus propiedades por breakpoint' },
    css_extra: { type: 'string' },
    riesgos: { type: 'string' },
  },
  required: ['nombre', 'concepto', 'copy', 'estructura', 'clases_nuevas', 'riesgos'],
}

const investigacion = parallel(INVS.map(r => () =>
  agent(RO + '\n\n' + r.prompt + '\nCada hecho con su fuente (archivo:línea, opción o comando). Escribe ' + A.salida + '/research-' + r.key + '.md con una receta de implementación (fragmentos PHP) y los pasos de verificación con curl, y devuelve el resumen.',
    { label: 'investigar:' + r.key, phase: 'Investigar', schema: RESUMEN })
))
const propuestas = parallel(ANGULOS.map(g => () =>
  agent(RO + '\n' + DISENO + '\n\nPropón el diseño de la pantalla «' + A.pantalla + '» con este ángulo (' + g.key + '): ' + g.enfoque,
    { label: 'proponer:' + g.key, phase: 'Proponer', schema: PROPUESTA })
    .then(p => (p ? Object.assign({ angulo: g.key }, p) : null))
))
const [invs, props] = await Promise.all([investigacion, propuestas])
const validas = props.filter(Boolean)
if (validas.length < 2) log('Solo ' + validas.length + ' propuesta(s) válida(s): el juez no tendrá con qué comparar')

phase('Juzgar')
const juicio = await agent(RO + '\n' + DISENO + `

Eres el JUEZ de diseño de la pantalla «${A.pantalla}». Compara las propuestas con una tabla de criterios puntuados: fidelidad al lenguaje visual del sitio (lee los componentes del prototipo citados), tono de marca en cada idioma, claridad y utilidad (explicar y ofrecer caminos), accesibilidad (un solo h1, contraste, sin animaciones infinitas: WCAG 2.2.2, prefers-reduced-motion), construible con las clases existentes (compruébalo en la lista de clases), comportamiento responsive.
Elige una ganadora e INJERTA las ideas claramente mejores de las demás; lista lo descartado y por qué.
Escribe ${A.salida}/final-${A.pantalla}.md con: veredicto y tabla; copy final exacto en ${IDIOMAS.join(' y ')}; árbol V4 exacto con clases; clases nuevas con propiedades por breakpoint; tabla responsive CALCULADA (1440, 1025, 1024–768, 375, 320); tabla de contrastes CALCULADOS; imágenes elegidas (ids); verificación con curl (HTTP esperado, lang, id de plantilla, un solo <main>) y el código completo en bloques \`\`\`php y \`\`\`css extraíbles. Si la investigación contradice algo de una propuesta (nombres de opciones, selectores, hooks), manda la investigación.

INVESTIGACIÓN:
${JSON.stringify(invs.filter(Boolean), null, 1)}

PROPUESTAS:
${JSON.stringify(validas, null, 1)}`, { label: 'juez', phase: 'Juzgar', schema: RESUMEN, effort: 'high' })

return {
  pantalla: A.pantalla,
  investigacion: invs.filter(Boolean),
  propuestas: validas.map(p => ({ angulo: p.angulo, nombre: p.nombre, concepto: p.concepto })),
  juicio,
  siguiente: [
    'Leer final-' + A.pantalla + '.md entero y comprobar en el código las APIs que se van a usar.',
    'Extraer los bloques de código, comprobar que existe cada clase reutilizada (e4-dump-clases.php) y construir: clases → sync → build idempotente → purga.',
    'Verificar con curl y medir a 1440, 1280, 768 y 375 (tolerancia de verificacion.md §2); después revisar-pagina.js.',
  ],
}
