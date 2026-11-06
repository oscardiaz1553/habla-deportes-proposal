export const meta = {
  name: 'planificar-migracion',
  description: 'Auditoría de solo lectura de origen y destino, procedimiento de migración y runbook con decisiones del dueño, puertas GO/NO-GO y vuelta atrás; control final de secretos',
  whenToUse: 'En F12, antes de escribir nada en el hosting de producción; nada se ejecuta hasta que el usuario cierre las decisiones',
  phases: [
    { title: 'Auditar', detail: 'destino, origen y procedimiento en paralelo, solo lectura' },
    { title: 'Runbook', detail: 'consolidar decisiones Dn, puertas A–D, pasos y vuelta atrás' },
    { title: 'Control', detail: 'hechos clave comprobados y ningún secreto en los informes' },
  ],
}

/*
 * planificar-migracion.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Tres agentes en paralelo y en SOLO LECTURA auditan el hosting de destino, el de origen (staging) y el
 *   procedimiento; un cuarto consolida el runbook con la plantilla del kit (decisiones del dueño con
 *   recomendación, puertas GO/NO-GO, pasos marcados [lectura]/[ESCRIBE en X]/[MANUAL usuario], vuelta atrás,
 *   tiempos y caída) y un control final comprueba los hechos que más pesan y que ningún informe lleve secretos.
 *   El resultado se le presenta al usuario con ../plan-con-decisiones.md (variante B) y AskUserQuestion.
 *   Las decisiones se numeran con el catálogo D1–D16 de wp-migracion/references/runbook.md §1 (el mismo
 *   número para la misma decisión; las propias del proyecto, desde D17).
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/planificar-migracion.js', args: {
 *     origen: { sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', url: 'https://<STAGING_URL>' },
 *     destino: { sshAlias: '<SSH_ALIAS_PROD>', dominio: '<DOMINIO>', docroot: '<ruta si ya se conoce; se comprueba igual>' },
 *     intocable: ['~/public_html (otro sitio de la cuenta)', 'su base de datos', 'su .htaccess'],
 *     hechos: ['el dominio sirve hoy <qué> detrás de una cortina 503', 'SPF actual: <…>'],
 *     sesionSeo: 'la sesión de SEO decide la cortina, el mapa 301 y blog_public',
 *     stack: '…', prefijo: '<PREFIJO>', salida: '<carpeta local privada del proyecto>/migracion', kit: '<HOME>/.claude' } })
 *   Obligatorios: origen.sshAlias, origen.wpPath, destino.sshAlias, destino.dominio, salida, kit (carpeta que
 *   contiene skills/: <HOME>/.claude para la copia instalada).
 *
 * ESTADO
 *   Generalizado a partir del workflow real plan-migracion-bluehost (probado en HomeMaker: auditorías de los dos
 *   hostings, procedimiento v1 con D1–D11 y runbook v2 con D1–D17, numeración histórica; el catálogo del kit,
 *   D1–D16, une optimizador y conector de IA en D3). El control final es un añadido del kit por un
 *   incidente real: un agente de auditoría imprimió las credenciales de BD de OTRO sitio de la cuenta aunque el
 *   prompt lo prohibía. Esta versión parametrizada no se ha ejecutado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Todo es lectura, salvo los informes que se escriben en «salida» (carpeta local privada, fuera de iCloud:
 *   llevan datos de infraestructura). El archivo sonda para confirmar el docroot por HTTPS es una ESCRITURA:
 *   aquí solo se propone como paso [ESCRIBE] del runbook, con OK del usuario.
 */

const A = args || {}
const O = A.origen || {}
const D = A.destino || {}
const falta = []
if (!O.sshAlias) falta.push('origen.sshAlias')
if (!O.wpPath) falta.push('origen.wpPath')
if (!D.sshAlias) falta.push('destino.sshAlias')
if (!D.dominio) falta.push('destino.dominio')
if (!A.salida) falta.push('salida')
if (!A.kit) falta.push('kit')
if (falta.length) throw new Error('planificar-migracion: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const OUT = A.salida
const MIG = A.kit + '/skills/wp-migracion'

const RO = `SOLO LECTURA ESTRICTA en LOS DOS servidores: nada de escribir, mover, borrar ni cambiar permisos; ningún wp-cli que escriba (option, post, plugin, search-replace, db import, ni db export al disco del servidor); uapi solo con funciones que consultan (list, get, validate, parse); ningún cambio de base de datos. Lo único que puedes escribir es TU informe en ${OUT}/ (en tu Mac).
SECRETOS: prohibido cat, sed, grep de contenido o head sobre wp-config.php, .env, .my.cnf y volcados *.sql, también del sitio vecino. Receta segura: "wp config list --fields=name,type" (solo nombres), "wp config get table_prefix", como mucho DB_NAME, "wp config has <CONST>", grep -c. Nunca imprimas contraseñas, claves, sales, la llave de una cortina ni tokens; de los usuarios, solo login, rol y fecha de alta; de admin_email, solo el dominio.
No uses herramientas de navegador. Si tu Bash corre en sandbox, las llamadas con red necesitan dangerouslyDisableSandbox: true. macOS: no hay "timeout"; usa ssh -o BatchMode=yes -o ConnectTimeout=15.
ORIGEN (staging): ssh ${O.sshAlias}, WordPress en --path=${O.wpPath}${O.url ? ', URL ' + O.url : ''}.
DESTINO (producción): ssh ${D.sshAlias}, dominio ${D.dominio}${D.docroot ? ', docroot declarado ' + D.docroot + ' (COMPRUÉBALO: nunca se deduce)' : ', docroot por descubrir (nunca se deduce: en el caso de referencia ~/public_html era el WordPress vivo de otro cliente)'}.
INTOCABLE: ${lista(A.intocable).join('; ') || 'cualquier otro sitio de la cuenta'}. Ningún wp sin --path: WP-CLI sube carpetas buscando wp-load.php y actuaría sobre el sitio vecino.
Stack: ${A.stack || 'ver el CLAUDE.md del proyecto'}. Hechos conocidos: ${lista(A.hechos).join('; ') || 'ninguno declarado'}.${A.sesionSeo ? '\nSesión paralela de SEO: ' + A.sesionSeo + '.' : ''}`

const INFORME = {
  type: 'object',
  properties: {
    archivo: { type: 'string' },
    resumen: { type: 'string' },
    bloqueos: { type: 'array', items: { type: 'string' } },
    decisiones: { type: 'array', items: { type: 'string' }, description: 'decisiones del dueño que salen de esta auditoría, con opciones y recomendación' },
  },
  required: ['archivo', 'resumen', 'bloqueos', 'decisiones'],
}
const CONTROL = {
  type: 'object',
  properties: {
    secretos: { type: 'string', description: 'recuentos por archivo de los patrones buscados (solo números, nunca las líneas)' },
    hechos: { type: 'array', items: { type: 'object', properties: { hecho: { type: 'string' }, confirmado: { type: 'boolean' }, como: { type: 'string' } }, required: ['hecho', 'confirmado', 'como'] } },
    avisos: { type: 'array', items: { type: 'string' } },
  },
  required: ['secretos', 'hechos', 'avisos'],
}

phase('Auditar')
const auditorias = await parallel([
  () => agent(RO + `

Audita el DESTINO y escribe ${OUT}/auditoria-destino.md siguiendo ${MIG}/references/auditoria-hosting.md (sección «Destino»): dominios y docroots (uapi DomainInfo single_domain_data domain=${D.dominio}; domains_data), permisos y disco, SSL y renovación, redirecciones actuales, herencia de .htaccess del sitio padre (enmascara llaves con sed), PHP web frente a CLI y límites, módulos del servidor web, MySQL (versión, prefijos obligatorios, BD existentes), WP-CLI, correo (MX, SPF, DKIM, DMARC contra los NS autoritativos; enrutamiento local o remoto), caché y CDN, cron, cuota, estado del sitio en vivo y qué hay que conservar (.well-known, cortina y sus recursos, verificaciones, mapa 301). Tabla de hallazgos con impacto y «qué hacer»; al final, los comandos usados y la frase «este informe no contiene contraseñas, claves, sales ni llaves». Devuelve el resumen.`,
  { label: 'auditar:destino', phase: 'Auditar', schema: INFORME }),
  () => agent(RO + `

Audita el ORIGEN y escribe ${OUT}/auditoria-origen.md siguiendo ${MIG}/references/auditoria-hosting.md (sección «Origen»): actividad reciente (última edición, archivos cambiados en 30 min, administradores con fecha de alta), tamaños, qué NO migrar (copias generadas por el optimizador, cachés, restos de temas, plugins atados al proveedor, claves de cifrado del plugin de seguridad), wp-config solo por nombres, plugins con versión y procedencia de los premium (primeras líneas del archivo principal: pre_http_request, update_option de licencias, dominios ajenos), mu-plugins, código del hijo ligado al hosting, dónde aparece el dominio del staging (por tabla.columna, incluida la forma JSON escapada https:\\/\\/, rutas absolutas, correos no-reply@ y base64), colaciones, opciones de lanzamiento (blog_public, modo de mantenimiento de Elementor, portada, kit, opción polylang, login propio) e IDs fijos. Devuelve el resumen.`,
  { label: 'auditar:origen', phase: 'Auditar', schema: INFORME }),
  () => agent(RO + `

Investiga el PROCEDIMIENTO más seguro para este par concreto y escribe ${OUT}/procedimiento.md con comandos exactos, partiendo de ${MIG}/references/runbook.md (y rollback.md, correo-dns.md, lanzamiento.md del mismo directorio): exportación con el origen en solo lectura (por la salida estándar hacia el Mac si los servidores no se ven entre sí; comprueba si se ven), traslado con SHA-256, núcleo limpio de la misma versión, obra FUERA de public_html y en el mismo disco, BD nueva con contraseña nacida en el servidor y nunca impresa, importación (colaciones si el destino es MySQL 5.7), search-replace en orden (ruta absoluta → dominio sin esquema → https normal y escapado) hasta 0 restos, plugins del hosting de origen, .htaccess por bloques con marcadores, cambio de carpetas con dos mv detrás de la cortina 503, tareas con la ruta final, verificación con la llave sin imprimirla, correo, lanzamiento y vuelta atrás. Señala lo que cambia respecto a la plantilla del kit por lo que veas en ESTOS servidores. Devuelve el resumen con las decisiones del dueño.`,
  { label: 'procedimiento', phase: 'Auditar', schema: INFORME }),
])
const buenas = auditorias.filter(Boolean)
if (buenas.length < 3) log('Solo ' + buenas.length + ' de 3 auditorías terminaron: el runbook lo dirá como hueco')

phase('Runbook')
const runbook = await agent(RO + `

Eres el responsable de la migración. Lee ${OUT}/auditoria-destino.md, ${OUT}/auditoria-origen.md y ${OUT}/procedimiento.md, y comprueba en solo lectura lo que te parezca dudoso. Escribe ${OUT}/runbook.md con la estructura de ${MIG}/references/runbook.md y la variante B de ${A.kit}/skills/wp-elementor-replica/plantillas/plan-con-decisiones.md:
- «Estado: listo para ejecutar cuando se cierren las decisiones. No se ha ejecutado nada.»
- §0 En una pantalla (origen, destino, intocable, método, qué pasa con lo publicado hoy, caída para el público, ventana, qué bloquea el lanzamiento y no el cambio).
- §1 Decisiones del dueño Dn: opciones con su consecuencia, recomendación y «qué cambia en el runbook»; dueño (usuario, sesión SEO, cliente) y si bloquea el cambio o el lanzamiento. Numera con el catálogo D1–D16 de ${MIG}/references/runbook.md §1 (misma decisión, mismo número; las que añada este par de servidores, desde D17); el porqué de cada una está en ${A.kit}/skills/wp-elementor-replica/references/decisiones.md §3.8.
- §2 Puertas GO/NO-GO A (antes de empezar), B (antes del cambio), C (justo después), D (lanzamiento), con casillas medibles.
- §3 Convenciones, §4 pasos con [lectura] / [ESCRIBE en X] / [MANUAL usuario], duración, salida esperada y «si falla», cada bloque autónomo (ssh <alias> 'bash -s' <<'EOF' … EOF con set -euo pipefail y guarda de table_prefix); §5 vuelta atrás; §6 tiempos y caída; §7 qué adapta la sesión paralela; §8 riesgos; §9 cambios respecto al procedimiento; §10 evidencia de solo lectura.
- Nunca se borra lo publicado hoy (va a respaldo); la limpieza final es MANUAL del usuario, con fechas. Nunca se toca lo intocable.
Devuelve el resumen con las decisiones.

RESÚMENES DE LAS AUDITORÍAS:
${JSON.stringify(buenas, null, 1)}`, { label: 'runbook', phase: 'Runbook', schema: INFORME, effort: 'high' })

phase('Control')
const control = await agent(RO + `

CONTROL FINAL (solo lectura):
1. Secretos en los informes: para cada .md de ${OUT}, cuenta con grep -c (NUNCA imprimas las líneas) los patrones: DB_PASSWORD, AUTH_KEY, SECURE_AUTH, _SALT, password=, pass=, token=, BEGIN .*PRIVATE KEY y el parámetro de la llave de la cortina si la hay (búscalo por su nombre, sin su valor). Devuelve solo los números por archivo. Si alguno es mayor que 0, dilo en avisos para que el orquestador lo revise y lo sanee.
2. Hechos que más pesan, comprobados por ti en solo lectura: el docroot real del dominio (uapi DomainInfo single_domain_data domain=${D.dominio}); que ningún paso [ESCRIBE] del runbook apunta a lo intocable; que todos los wp del runbook llevan --path; los administradores del origen (número y fecha de alta, sin correos); la procedencia de los plugins premium; el SPF actual del dominio (dig contra los NS autoritativos).
3. Avisos: huecos del runbook (auditorías que no terminaron, decisiones sin recomendación, pasos sin salida esperada).`, { label: 'control', phase: 'Control', schema: CONTROL })

return {
  archivos: [OUT + '/auditoria-destino.md', OUT + '/auditoria-origen.md', OUT + '/procedimiento.md', OUT + '/runbook.md'],
  auditorias: buenas,
  runbook,
  control,
  siguiente: [
    'Leer el runbook entero y revisar la salida del control antes de reenviar nada (secretos = 0 en todos los archivos).',
    'Presentar las decisiones con plan-con-decisiones.md (variante B) y AskUserQuestion, la recomendada primero.',
    'No ejecutar ningún paso [ESCRIBE] sin el OK explícito del usuario en el chat.',
  ],
}
