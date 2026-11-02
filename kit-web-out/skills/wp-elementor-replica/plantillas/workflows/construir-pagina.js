export const meta = {
  name: 'construir-pagina',
  description: 'Spec del prototipo → scripts de clases y de construcción V4 → (en staging) ejecución, medición contra el prototipo y corrección acotada, para UNA página',
  whenToUse: 'Para construir o rehacer una página de la réplica cuando ya existe su spec-prototipo.md; una sola ejecución a la vez por sitio',
  phases: [
    { title: 'Preparar', detail: 'leer spec, prototipo y clases existentes; plan y bloqueos' },
    { title: 'Escribir', detail: 'classes.php + build.php desde las plantillas del kit, php -l' },
    { title: 'Ejecutar', detail: 'solo en modo staging: guardas, clases, sync preview, build, purga' },
    { title: 'Medir', detail: 'Chrome headless propio: prototipo contra WordPress con tolerancia' },
    { title: 'Corregir', detail: 'iteraciones acotadas sobre las diferencias medidas' },
  ],
}

/*
 * construir-pagina.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Automatiza el ciclo por página del playbook (skill wp-elementor-replica, SKILL.md y fases.md F3–F10)
 *   para UNA página: plan (solo lectura) → <pagina>-classes.php y <pagina>-build.php escritos en local desde
 *   las plantillas del kit y validados con php -l → en modo 'staging': ejecución con guardas, sincronización
 *   del contexto preview, purga, medición headless contra el prototipo y hasta N iteraciones de corrección.
 *   El CSS/JS que V4 no expresa NO se sube: se deja en <pagina>-hijo.css / <pagina>-hijo.js para integrarlo
 *   en el tema hijo con el protocolo de sesiones paralelas (bajar, comparar md5, avisar, subir).
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/construir-pagina.js', args: {
 *     pagina: 'contacto', ruta: '/contact/', docId: <ID>, modo: 'escribir',     // 'staging' para ejecutar y medir
 *     url: 'https://<STAGING_URL>', prototipoUrl: 'http://localhost:3000', prototipo: '<ruta del código>',
 *     spec: '<scripts>/contacto/spec-prototipo.md', scripts: '<scripts>/contacto', kit: '<HOME>/.claude',
 *     prefijo: '<PREFIJO>', sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', tablePrefix: '<prefijo_>',
 *     dominioProduccion: '<DOMINIO>', purga: 'wp sg purge', dirMedicion: '~/qa-headless',
 *     revelarWp: '[data-<PREFIJO>-reveal]', revelarProto: '.reveal', seccionesProto: 'main > section',
 *     idiomaProto: '<PREFIJO>-lang=es', anchos: [1440, 1280, 768, 375], tolerancia: 1, toleranciaIntermedios: 3,
 *     maxIteraciones: 2, agenteConstructor: 'elementor-dev', agenteQa: 'qa-elementor',
 *     conocidos: ['breakpoints de Elementor 767/1024/<tablet_extra>'] } })
 *   Obligatorios: pagina, ruta, docId, spec, scripts, kit, prefijo, prototipo. En modo 'staging' además
 *   url, prototipoUrl, sshAlias y wpPath. kit = carpeta que contiene skills/ (<HOME>/.claude para la copia
 *   instalada). Requisitos previos: la página existe (docId), sus medios están subidos, el prototipo lo
 *   sirve UN servidor de desarrollo que ya está arrancado y, para medir, dirMedicion (por defecto
 *   ~/qa-headless) tiene puppeteer-core instalado; qa-medir.mjs y qa-cmp.py se copian allí desde
 *   <kit>/skills/wp-qa/scripts/ si faltan (preparación y uso en wp-qa/scripts/README.md). El workflow no
 *   arranca servidores ni instala paquetes.
 *   Tolerancia (la canónica de wp-elementor-replica/references/verificacion.md §2): tolerancia (1 px) en
 *   1440, 1280, 768 y 375; toleranciaIntermedios (3 px) solo en los demás anchos.
 *   agenteConstructor / agenteQa: opcionales, tipos de subagente del kit si están instalados.
 *
 * ESTADO
 *   Nuevo del kit, NO probado: compone pasos que en HomeMaker hizo el orquestador a mano, página a página
 *   (spec → classes → sync → build → purga → medición en tolerancia → revisión adversarial). Las piezas que usa
 *   (plantillas e4-*, qa-medir.mjs, qa-cmp.py) tienen su propio estado en sus cabeceras.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   - Modo 'staging' ESCRIBE en el WordPress de staging (clases globales, documento docId, caché). Nunca
 *     lo lances contra producción: se aborta si la URL coincide con dominioProduccion, y el agente comprueba
 *     blog_public=0 y el prefijo de tablas antes de escribir.
 *   - El build reescribe TODO el contenido Elementor de docId: lo editado por el cliente en esa página se
 *     pierde. Úsalo en construcción; después, parches por id.
 *   - Una ejecución a la vez por sitio: el repositorio de clases globales es uno y put() recibe el conjunto
 *     completo; dos scripts de clases en paralelo pueden perder clases.
 *   - Cierra siempre con la tarea para el usuario (abrir la página en el editor y publicar) y lanza después
 *     revisar-pagina.js en segundo plano.
 */

const A = args || {}
const falta = ['pagina', 'ruta', 'docId', 'spec', 'scripts', 'kit', 'prefijo', 'prototipo'].filter(k => !A[k])
if (falta.length) throw new Error('construir-pagina: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const MODO = A.modo === 'staging' ? 'staging' : 'escribir'
if (MODO === 'staging') {
  const faltaS = ['url', 'prototipoUrl', 'sshAlias', 'wpPath'].filter(k => !A[k])
  if (faltaS.length) throw new Error('construir-pagina: en modo staging faltan ' + faltaS.join(', '))
  const host = u => ((String(u || '').match(/^(?:https?:\/\/)?([^/:?#]+)/i) || [])[1] || '').toLowerCase().replace(/^www\./, '')
  if (A.dominioProduccion && host(A.url) === host(A.dominioProduccion)) {
    throw new Error('construir-pagina: la URL apunta al dominio de producción; este workflow solo escribe en staging')
  }
}

const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const P = A.prefijo
const KIT = A.kit
const SC = A.scripts
const TMP = '~/tmp-' + P + '/'
const ANCHOS = lista(A.anchos).length ? lista(A.anchos) : [1440, 1280, 768, 375]
const TOL = A.tolerancia || 1
const TOL_INT = A.toleranciaIntermedios || 3
const PRINCIPALES = [1440, 1280, 768, 375]
const INTERMEDIOS = ANCHOS.filter(w => PRINCIPALES.indexOf(Number(w)) === -1)
const QA = A.kit + '/skills/wp-qa/scripts'
const conTipo = (tipo, extra) => (tipo ? Object.assign({ agentType: tipo }, extra) : extra)
const MAX_IT = A.maxIteraciones === 0 ? 0 : (A.maxIteraciones || 2)
const DIRM = A.dirMedicion || '~/qa-headless'
const RUTA_WP = A.rutaWp || A.ruta
const WP = A.wpPath ? 'wp --path=' + A.wpPath : 'wp --path=<WP_PATH>'
const E4 = KIT + '/skills/elementor4-atomico'

const BASE = `
PROYECTO: réplica EXACTA de un prototipo en WordPress + Elementor 4 con el editor atómico (V4), construida por WP-CLI con scripts idempotentes; el cliente debe poder editarlo todo en Elementor; sin plantillas importadas. Stack: ${A.stack || 'ver el CLAUDE.md del proyecto'}. Prefijo: ${P} (clases ${P}-*, atributos data-${P}-*).
PÁGINA: ${A.pagina} · ruta del prototipo ${A.ruta} · ruta en WordPress ${RUTA_WP} · documento #${A.docId}.
FUENTES: spec medida ${A.spec} (la verdad son sus cifras, no las clases del código) · código del prototipo ${A.prototipo} · carpeta de scripts de esta página ${SC}.
KIT: carga la skill "elementor4-atomico" si está instalada; si no, lee ${E4}/SKILL.md. Referencias: ${E4}/references/modelo-de-datos.md, ${E4}/references/gotchas-cli.md, ${E4}/references/tema-hijo.md. Plantillas: ${E4}/scripts/e4-clases-upsert.plantilla.php, ${E4}/scripts/e4-build-pagina.plantilla.php, ${E4}/scripts/e4-helpers.php, ${E4}/scripts/e4-sync-preview.php, ${E4}/scripts/e4-dump-clases.php.
CONOCIDO Y ACEPTADO: ${lista(A.conocidos).join('; ') || 'nada declarado'}.
${A.extra || ''}
REGLAS: nunca "wp eval" inline por SSH (PHP por archivo + scp + eval-file); --user=1 en todo lo que guarde; nada de "rm" en cadenas; no leas ni imprimas wp-config.php, .env, .my.cnf ni volcados *.sql; no uses herramientas de navegador (mcp__Claude_Browser__*, Claude in Chrome, computer-use); no arranques servidores ni instales paquetes. Si tu Bash corre en sandbox, las llamadas con red (ssh, scp, curl remoto) necesitan dangerouslyDisableSandbox: true.`

const PLAN = {
  type: 'object',
  properties: {
    resumen: { type: 'string' },
    bloqueos: { type: 'array', items: { type: 'string' }, description: 'lo que impide escribir los scripts: medios sin id, spec incompleta, contenido que solo puede dar el cliente' },
    secciones: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          nombre: { type: 'string' },
          arbol: { type: 'string', description: 'elementos V4 anidados con sus clases, textos (clave de origen) y enlaces' },
          clases_reutilizadas: { type: 'array', items: { type: 'string' } },
          clases_nuevas: { type: 'array', items: { type: 'object', properties: { id: { type: 'string' }, props: { type: 'string', description: 'propiedades por breakpoint y estado, con valores de la spec' } }, required: ['id', 'props'] } },
          hijo: { type: 'string', description: 'lo que V4 no expresa y va al tema hijo, enganchado por data-*' },
        },
        required: ['nombre', 'arbol', 'clases_reutilizadas', 'clases_nuevas', 'hijo'],
      },
    },
    medios: { type: 'array', items: { type: 'object', properties: { clave: { type: 'string' }, archivo: { type: 'string' }, id: { type: 'string', description: 'id de adjunto o FALTA' } }, required: ['clave', 'archivo', 'id'] } },
    conocidos_nuevos: { type: 'array', items: { type: 'string' }, description: 'diferencias deliberadas con el prototipo que habrá que declarar' },
    dudas_cliente: { type: 'array', items: { type: 'string' } },
  },
  required: ['resumen', 'bloqueos', 'secciones', 'medios', 'conocidos_nuevos', 'dudas_cliente'],
}
const ESCRITURA = {
  type: 'object',
  properties: {
    archivos: { type: 'array', items: { type: 'object', properties: { ruta: { type: 'string' }, que: { type: 'string' } }, required: ['ruta', 'que'] } },
    php_l: { type: 'string', description: 'salida literal de php -l de cada PHP escrito' },
    pendientes: { type: 'array', items: { type: 'string' } },
  },
  required: ['archivos', 'php_l', 'pendientes'],
}
const EJECUCION = {
  type: 'object',
  properties: {
    ok: { type: 'boolean' },
    guardas: { type: 'string', description: 'valores leídos antes de escribir: blog_public, table_prefix, URL del sitio' },
    salida: { type: 'string', description: 'líneas de resumen de cada script (clases antes → después, sync frontend = preview, página #id — secciones y elementos)' },
    errores: { type: 'array', items: { type: 'string' } },
    http: { type: 'string', description: 'código HTTP y marcadores comprobados con curl' },
  },
  required: ['ok', 'guardas', 'salida', 'errores', 'http'],
}
const MEDICION = {
  type: 'object',
  properties: {
    verificable: { type: 'boolean', description: 'false si falta el entorno de medición o el prototipo no responde' },
    motivo: { type: 'string' },
    anchos: { type: 'array', items: { type: 'object', properties: { ancho: { type: 'integer' }, ok: { type: 'boolean' }, diferencias: { type: 'array', items: { type: 'string' } } }, required: ['ancho', 'ok', 'diferencias'] } },
    desborde: { type: 'boolean' },
    ocultos: { type: 'integer', description: 'elementos visibles con opacidad 0 en WordPress' },
    consola: { type: 'array', items: { type: 'string' } },
  },
  required: ['verificable', 'motivo', 'anchos', 'desborde', 'ocultos', 'consola'],
}
const CORRECCION = {
  type: 'object',
  properties: {
    cambios: { type: 'array', items: { type: 'object', properties: { archivo: { type: 'string' }, que: { type: 'string' }, porque: { type: 'string' } }, required: ['archivo', 'que', 'porque'] } },
    conocidos_nuevos: { type: 'array', items: { type: 'string' } },
    sin_resolver: { type: 'array', items: { type: 'string' } },
  },
  required: ['cambios', 'conocidos_nuevos', 'sin_resolver'],
}

phase('Preparar')
const plan = await agent(BASE + `

TAREA (SOLO LECTURA, no escribas nada): prepara el plan de construcción de la página.
1. Lee la spec completa y el código del prototipo de la página y de sus componentes (textos: toma la clave de origen de cada uno).
2. Clases existentes: ${A.sshAlias && A.wpPath ? 'vuélcalas en solo lectura con e4-dump-clases.php (scp a ' + TMP + ' y "' + WP + ' --user=1 eval-file ' + TMP + 'e4-dump-clases.php"; con E4_SALIDA puedes guardarlas en un archivo del servidor)' : 'usa ' + (A.clasesExistentes || 'la lista que haya en ' + SC) + ' (sin acceso al servidor)'}. Reutiliza antes de crear.
3. Medios: localiza el id de adjunto de cada imagen o vídeo que usa la página${A.sshAlias && A.wpPath ? ' ("' + WP + ' post list --post_type=attachment --fields=ID,post_title,guid")' : ''}; si falta alguno, es un bloqueo.
4. Árbol V4 por sección: raíz <section> (nunca <main>: lo abre el tema hijo), una familia de propiedades por clase (la tipografía sin margen ni color; espaciados en utilidades ${P}-mt-*), modificadores antes que su base en el orden, estilos base atómicos neutralizados (padding 10px, min-width 30px, width 100%) donde la spec no los tenga, defaults implícitos del prototipo replicados (en Tailwind: interlineado 1,5 del cuerpo, h1–h4 a 1,15), acento de color = <strong> con clase de acento, comportamiento enganchado por data-${P}-* y lo que V4 no expresa (pseudoelementos, hovers entre elementos, máscaras, scroll-snap, animaciones) al tema hijo.
5. Diferencias deliberadas (p. ej. breakpoints de Elementor 767/1024/<tablet_extra> frente a los del prototipo) en conocidos_nuevos; contenido que solo puede dar el cliente, en dudas_cliente.`, conTipo(A.agenteConstructor, { label: 'plan:' + A.pagina, phase: 'Preparar', schema: PLAN }))

if (!plan) throw new Error('construir-pagina: el agente de plan no devolvió resultado')
if (plan.bloqueos.length) {
  log('Bloqueado antes de escribir: ' + plan.bloqueos.join(' | '))
  return { pagina: A.pagina, estado: 'bloqueado', bloqueos: plan.bloqueos, plan }
}

phase('Escribir')
const escritura = await agent(BASE + `

TAREA: escribe en ${SC} (y SOLO ahí) los scripts de esta página a partir del plan:
- ${SC}/${A.pagina}-classes.php desde e4-clases-upsert.plantilla.php: E4_PREFIJO = '${P}', las clases nuevas del plan con los valores de la spec, orden con los modificadores delante de su base, validación con Global_Classes_Parser, put(), limpieza de caché y require del e4-sync-preview.php al final (sin la sincronización, publicar desde el editor borra las clases).
- ${SC}/${A.pagina}-build.php desde e4-build-pagina.plantilla.php: $E4_SEED = '${A.pagina}' (ids deterministas), ID fijo ${A.docId}, secciones como variables con títulos legibles para el Navegador del editor, ids de adjuntos desde el plan (nunca a mano), textos literales de la spec.
- Copia e4-helpers.php y e4-sync-preview.php a ${SC} si no están (el require usa __DIR__).
- Lo que va al tema hijo: ${SC}/${A.pagina}-hijo.css y ${SC}/${A.pagina}-hijo.js (bloque propio con comentario de cabecera y el porqué de cada regla: medida de la spec o clase equivalente del prototipo). NO los subas: los integra el orquestador con el protocolo de sesiones paralelas.
- Pasa "php -l" a cada PHP y "node --check" al JS; devuelve la salida literal.
Heredocs con comillas si usas la terminal ('PHP'), y parches con un ancla que aparezca exactamente una vez.

PLAN:
${JSON.stringify(plan, null, 1)}`, conTipo(A.agenteConstructor, { label: 'escribir:' + A.pagina, phase: 'Escribir', schema: ESCRITURA }))

const tareasUsuario = [
  'Abre la página #' + A.docId + ' en el editor de Elementor, cambia un texto, publica y avísame; después compruebo por CLI que las clases frontend = preview y que el documento no cambió de forma inesperada.',
]
if (MODO !== 'staging') {
  return {
    pagina: A.pagina, modo: MODO, estado: 'escrito', plan, escritura,
    siguiente: [
      'E4_SECO=1 con ' + A.pagina + '-classes.php para validar sin escribir, y después ejecutar en este orden: classes (incluye sync) → build → purga.',
      'Integrar ' + A.pagina + '-hijo.css / .js en el tema hijo (bajar, comparar md5, avisar, subir).',
      'Relanzar con modo: "staging" para ejecutar, medir y corregir, o medir a mano con medir-vs-prototipo.js.',
    ],
    tareasUsuario,
  }
}

const EJECUTAR = BASE + `

TAREA [ESCRIBE en staging]: ejecuta los scripts de ${SC} en el WordPress de staging.
0. GUARDAS antes de escribir (si alguna falla, NO escribas y devuelve ok=false): "${WP} option get blog_public" debe ser 0; ${A.tablePrefix ? '"' + WP + ' config get table_prefix" debe ser "' + A.tablePrefix + '"; ' : ''}"${WP} option get home" debe coincidir con ${A.url}.
1. scp de ${A.pagina}-classes.php, ${A.pagina}-build.php, e4-helpers.php y e4-sync-preview.php a ${TMP} (${A.sshAlias}); compara el tamaño local y el remoto de cada uno.
2. E4_SECO=1 ${WP} --user=1 eval-file ${TMP}${A.pagina}-classes.php (valida sin escribir); si hay errores, para.
3. ${WP} --user=1 eval-file ${TMP}${A.pagina}-classes.php > ${TMP}salida-classes.txt 2>&1; grep -i -m5 "fatal\\|uncaught" sobre la salida y tail. La línea del sync debe decir «iguales: sí».
4. ${WP} --user=1 eval-file ${TMP}${A.pagina}-build.php > ${TMP}salida-build.txt 2>&1; mismo filtrado de fatales y tail (resumen «documento #${A.docId} — secciones: … elementos: …»).
5. ${WP} elementor flush_css${A.purga ? ' y ' + A.purga : ''}.
6. curl -s -o /dev/null -w '%{http_code}' "${A.url}${RUTA_WP}?nocache=<n>" = 200, y grep en el HTML de: un solo "<main", clases ${P}- esperadas, data-${P}-* esperados.
Cada operación en su propio comando; ningún "rm"; los temporales de ${TMP} se quedan.`

const MEDIR = BASE + `

TAREA (solo lectura): mide la página contra el prototipo con Chrome headless propio, sin tocar el navegador de la sesión.
- Entorno: ${DIRM} debe tener node_modules/puppeteer-core. Si faltan qa-medir.mjs o qa-cmp.py, cópialos allí desde ${QA}/ (cp -n; preparación y uso en ${QA}/README.md): se ejecutan desde ${DIRM} porque Node resuelve puppeteer-core desde la carpeta del script. Si falta puppeteer-core, faltan los originales o ${A.prototipoUrl} no responde, devuelve verificable=false con el motivo: no instales nada ni arranques servidores.
- Prototipo: cd ${DIRM} && QA_REVELAR='${A.revelarProto || ''}' QA_SECCIONES='${A.seccionesProto || 'main section'}' QA_IDIOMA='${A.idiomaProto || ''}' node qa-medir.mjs proto-${A.pagina}.json ${A.prototipoUrl} "${A.ruta}" ${ANCHOS.join(',')}
- WordPress: cd ${DIRM} && QA_REVELAR='${A.revelarWp || '[data-' + P + '-reveal]'}' QA_SECCIONES='${A.seccionesWp || 'main section'}' node qa-medir.mjs wp-${A.pagina}.json ${A.url} "${RUTA_WP}" ${ANCHOS.join(',')}
  (con rutas distintas en los dos lados, renombra las claves del JSON a la misma ruta antes de comparar).
- Compara: python3 qa-cmp.py proto-${A.pagina}.json wp-${A.pagina}.json ${TOL}. Tolerancia: ${TOL} px por sección en 1440, 1280, 768 y 375${INTERMEDIOS.length ? '; en los anchos intermedios (' + INTERMEDIOS.join(', ') + ') se aceptan hasta ' + TOL_INT + ' px (ok=true si ninguna diferencia de esos anchos pasa de ' + TOL_INT + ')' : ''}.
- Para cada diferencia, di sección, ancho y valores; separa las que están en «conocido y aceptado». Si una diferencia viene de la cabecera o del pie, dilo (no es de la página).`

phase('Ejecutar')
let ejecucion = await agent(EJECUTAR, conTipo(A.agenteConstructor, { label: 'ejecutar:' + A.pagina, phase: 'Ejecutar', schema: EJECUCION }))
if (!ejecucion || !ejecucion.ok) {
  return { pagina: A.pagina, modo: MODO, estado: 'fallo-ejecucion', plan, escritura, ejecucion, tareasUsuario }
}

phase('Medir')
let medicion = await agent(MEDIR, conTipo(A.agenteQa, { label: 'medir:' + A.pagina, phase: 'Medir', schema: MEDICION }))
const historial = [{ iteracion: 0, medicion }]
const pendiente = m => m && m.verificable && (m.anchos.some(a => !a.ok) || m.desborde || m.ocultos > 0)

let it = 0
while (pendiente(medicion) && it < MAX_IT) {
  it++
  phase('Corregir')
  const correccion = await agent(BASE + `

TAREA (iteración ${it} de ${MAX_IT}): corrige las diferencias medidas SIN tocar lo que ya cuadra.
Método: aísla la sección que difiere (top y alto de cada hijo); mira si es una decisión («conocido y aceptado»: entonces pásala a conocidos_nuevos y no la «arregles»); si no, busca primero las causas típicas: defaults implícitos del prototipo no replicados, estilos base atómicos (padding 10px, min-width 30px, width 100%), una clase que pierde frente a otra anterior en el orden, variante de tablet pisada por la base en móvil.
Edita ${A.pagina}-classes.php / ${A.pagina}-build.php en ${SC}, o escribe ${SC}/${A.pagina}-fix${it}.php para un cambio acotado (que compruebe si ya está aplicado antes de aplicarlo). php -l a todo. NO ejecutes nada en el servidor: lo hace el paso siguiente.
Lo que no sepas resolver, en sin_resolver con tu hipótesis.

MEDICIÓN:
${JSON.stringify(medicion, null, 1)}`, conTipo(A.agenteConstructor, { label: 'corregir#' + it, phase: 'Corregir', schema: CORRECCION }))
  ejecucion = await agent(EJECUTAR + (it ? '\nSi existe ' + A.pagina + '-fix' + it + '.php, súbelo y ejecútalo después del build, con el mismo filtrado de fatales.' : ''),
    conTipo(A.agenteConstructor, { label: 'ejecutar#' + it, phase: 'Corregir', schema: EJECUCION }))
  if (!ejecucion || !ejecucion.ok) {
    historial.push({ iteracion: it, correccion, ejecucion })
    break
  }
  medicion = await agent(MEDIR, conTipo(A.agenteQa, { label: 'medir#' + it, phase: 'Medir', schema: MEDICION }))
  historial.push({ iteracion: it, correccion, medicion })
}
if (pendiente(medicion)) log('Quedan diferencias tras ' + it + ' iteraciones: se entregan como pendientes, no como hechas.')
if (medicion && !medicion.verificable) log('Medición NO verificada: ' + medicion.motivo)

return {
  pagina: A.pagina,
  modo: MODO,
  estado: !medicion || !medicion.verificable ? 'construida-sin-medir' : pendiente(medicion) ? 'construida-con-diferencias' : 'construida-y-medida',
  plan: { resumen: plan.resumen, conocidos_nuevos: plan.conocidos_nuevos, dudas_cliente: plan.dudas_cliente },
  escritura,
  ejecucion,
  medicion,
  historial,
  tareasUsuario,
  siguiente: [
    'Integrar ' + A.pagina + '-hijo.css / .js en el tema hijo con el protocolo de sesiones paralelas, purgar y volver a medir.',
    'Lanzar revisar-pagina.js en segundo plano con la lista de «conocido y aceptado» actualizada.',
    'Actualizar el registro de IDs y el estado en el CLAUDE.md del proyecto y en la memoria.',
  ],
}
