export const meta = {
  name: 'plan-rendimiento-seo',
  description: 'Cinco analistas de solo lectura (rendimiento, accesibilidad y buenas prácticas, internos de los plugins, copy SEO, Yoast técnico) y un crítico que devuelve UN plan ordenado con esquema fijo',
  whenToUse: 'En la sesión de SEO/rendimiento (F11), con la línea base de Lighthouse ya medida y antes de tocar nada',
  phases: [
    { title: 'Analizar', detail: 'cinco roles en paralelo, solo lectura' },
    { title: 'Criticar', detail: 'conflictos, huecos y un plan fusionado y priorizado' },
  ],
}

/*
 * plan-rendimiento-seo.js — kit «WP Elementor 4 — réplica de prototipo» (skills/wp-elementor-replica/plantillas/workflows).
 *
 * QUÉ HACE
 *   Cinco analistas en paralelo, en SOLO LECTURA, estudian la línea base de Lighthouse, el HTML servido, el CSS,
 *   el código de los plugins en el servidor y el contenido; un crítico busca conflictos (p. ej. «combinar o
 *   diferir JS» frente a «eso rompe Elementor»), huecos y afirmaciones sin comprobar, y devuelve UN plan
 *   ordenado. Cada ítem: id, area, title, evidence, change, pages, expected_impact, risk, visual_change, verify,
 *   priority; más open_questions (solo decisiones del cliente). Después se implementa por lotes con regresión
 *   visual ±1 px (skill wp-seo-rendimiento).
 *
 * USO (herramienta Workflow)
 *   Workflow({ scriptPath: '<HOME>/.claude/skills/wp-elementor-replica/plantillas/workflows/plan-rendimiento-seo.js', args: {
 *     url: 'https://<STAGING_URL>', paginas: [{ nombre: 'inicio', ruta: '/' }, { nombre: 'contacto', ruta: '/contact/' }],
 *     lighthouse: '<carpeta con los JSON de la línea base>', medicion: '~/.claude/skills/wp-seo-rendimiento/scripts/medicion',
 *     sshAlias: '<SSH_ALIAS>', wpPath: '<WP_PATH>', hijo: '<copia local del tema hijo>', prototipo: '<ruta>',
 *     marca: 'qué vende la marca, dónde, a quién y en qué idiomas; vocabulario del cliente',
 *     cpt: 'receta (meta: …)', optimizador: '<plugin de optimización del hosting, si lo hay>',
 *     hechos: ['blog_public=0 a propósito hasta el lanzamiento', 'la cookie de idioma impide la caché de página', …],
 *     stack: '…' } })
 *   Obligatorios: url, paginas, lighthouse, sshAlias, wpPath. La línea base se mide ANTES y en serie con
 *   lh-run.sh (misma versión de Lighthouse que PSI, 3 corridas, mediana): en paralelo se contaminan.
 *
 * ESTADO
 *   Generalizado a partir del workflow real perf-seo-plan (probado en HomeMaker: plan de 29 pasos; el crítico
 *   demostró inútiles dos arreglos propuestos). Esta versión parametrizada no se ha ejecutado.
 *   Validación local: node --check del cuerpo envuelto en función async y meta evaluada como literal.
 *
 * RIESGOS
 *   Solo lectura (sin wp eval ni eval-file en esta fase). El diseño no se toca: todo cambio visible sale marcado
 *   (visual_change) y se presenta como decisión.
 */

const A = args || {}
const falta = ['url', 'paginas', 'lighthouse', 'sshAlias', 'wpPath'].filter(k => !A[k])
if (falta.length) throw new Error('plan-rendimiento-seo: faltan args ' + falta.join(', ') + ' (ver el README.md junto a este script)')
const lista = x => (Array.isArray(x) ? x : x ? [x] : [])
const PAGS = lista(A.paginas).map(p => p.nombre + ' ' + A.url + p.ruta).join(' · ')
const MED = A.medicion || '~/.claude/skills/wp-seo-rendimiento/scripts/medicion'

const CTX = `
CONTEXTO DEL PROYECTO
- Sitio: ${A.url}. Stack: ${A.stack || 'ver el CLAUDE.md del proyecto'}. Réplica EXACTA de un prototipo (${A.prototipo || 'ruta no indicada'}) en Elementor 4 atómico: el diseño no se toca; un cambio visible solo si hace falta para pasar una auditoría, mínimo y marcado. Todo sigue siendo editable por el cliente en Elementor.
- Páginas representativas: ${PAGS}.${A.cpt ? ' Contenido dinámico: ' + A.cpt + '.' : ''}
- Línea base de Lighthouse (mismo motor que PageSpeed Insights) en ${A.lighthouse}. Resumen: python3 ${MED}/lh-sum.py ${A.lighthouse} --mediana ; auditorías que fallan: python3 ${MED}/audits.py <informe.json>.
- Tema hijo (copia local idéntica a la desplegada): ${A.hijo || 'no indicada'}.
- SSH: ssh ${A.sshAlias} con WordPress en --path=${A.wpPath}. SOLO LECTURA ESTRICTA: permitido cat, grep, sed, find, ls, head, md5sum en el servidor, "wp option get|list", "wp post list|get", "wp post meta list|get", "wp plugin list", "wp theme list" y curl de URL públicas. PROHIBIDO: cualquier write, update, set, delete, activate, deactivate, install o purge, "wp eval", "wp eval-file", scp, editar archivos, enviar formularios y TODAS las herramientas de navegador (mcp__Claude_Browser__*, Claude in Chrome, computer-use). No leas wp-config.php. Si tu Bash corre en sandbox, las llamadas con red necesitan dangerouslyDisableSandbox: true.
- Hechos establecidos: ${lista(A.hechos).join(' | ') || 'ninguno declarado'}.
- Plugins: nunca propongas desactivarlos ni instalarlos sin marcarlo como decisión del usuario; configurar sus ajustes sí.
Sé concreto con ESTE stack. Cita evidencia (archivo:línea del plugin, id de auditoría, fragmento del HTML). Nada de consejos genéricos.`

const PLAN = {
  type: 'object',
  properties: {
    summary: { type: 'string' },
    items: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          id: { type: 'string' },
          area: { type: 'string' },
          title: { type: 'string' },
          evidence: { type: 'string' },
          change: { type: 'string', description: 'implementación exacta: ajuste y valor, archivo + fragmento o comando wp-cli' },
          pages: { type: 'string' },
          expected_impact: { type: 'string' },
          risk: { type: 'string' },
          visual_change: { type: 'boolean' },
          verify: { type: 'string', description: 'comprobación concreta de una línea' },
          priority: { type: 'integer', description: '1 = primero' },
        },
        required: ['id', 'area', 'title', 'evidence', 'change', 'expected_impact', 'risk', 'visual_change', 'verify', 'priority'],
      },
    },
    open_questions: { type: 'array', items: { type: 'string' } },
  },
  required: ['summary', 'items', 'open_questions'],
}
const SEO = {
  type: 'object',
  properties: {
    summary: { type: 'string' },
    site: { type: 'object', description: 'ajustes globales de Yoast: representación, nombre y alternativo, separador, organización y logo, perfiles sociales, imagen OG por defecto, tipos de contenido, taxonomías y archivos, sitemap, migas, esquema por defecto' },
    pages: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          post_id: { type: 'integer' }, url: { type: 'string' },
          seo_title: { type: 'string' }, meta_description: { type: 'string' },
          focus_keyphrase: { type: 'string' }, related_keyphrases: { type: 'array', items: { type: 'string' } },
          og_title: { type: 'string' }, og_description: { type: 'string' }, og_image_suggestion: { type: 'string' },
          schema_page_type: { type: 'string' }, content_notes: { type: 'string' },
        },
        required: ['post_id', 'url', 'seo_title', 'meta_description', 'focus_keyphrase', 'schema_page_type'],
      },
    },
    implementation: { type: 'array', items: { type: 'string' }, description: 'pasos técnicos en orden con claves de opción, metas o código exactos' },
    risks: { type: 'array', items: { type: 'string' } },
  },
  required: ['summary', 'site', 'pages', 'implementation', 'risks'],
}

const ROLES = [
  { key: 'rendimiento', schema: PLAN, prompt: 'ROL: ingeniero de rendimiento web. Objetivo: Performance ≥ 90 en MÓVIL y ESCRITORIO en todas las páginas. Lee todos los informes de la línea base: por página, el elemento LCP y su desglose (TTFB, retraso de carga, carga, retraso de pintado), fuentes de CLS, peticiones que bloquean el render, qué mueve el Speed Index, peso, CSS/JS sin usar y entrega de imágenes. Inspecciona el HTML servido (curl -s, el <head>) y el CSS realmente usado (CSS generado de Elementor en el servidor y style.css del hijo) para saber qué familias, pesos y estilos de fuente se usan de verdad. Da una lista priorizada y concreta, p. ej.: cookie de idioma que impide la caché de página; fuentes propias solo con lo usado, precarga y respaldo con métricas ajustadas; precarga y prioridad de la imagen LCP; srcset y sizes de las imágenes V4; jQuery en el <head>; el vídeo del hero frente a LCP y Speed Index (cuantifícalo y da opciones que conserven el diseño, diciendo lo que cuesta cada una); impacto de las entradas animadas y cómo mantenerlas; recursos de plugins que cargan en el frontend sin usarse.' },
  { key: 'a11y-bp', schema: PLAN, prompt: 'ROL: auditor de accesibilidad y buenas prácticas. Objetivo: Accesibilidad ≥ 90 (aspira a 100) y Buenas prácticas 100 en todos los informes. Lista cada auditoría que falla (sin duplicar causas) e inspecciona el HTML y el CSS servidos para la causa raíz: aria-hidden con enlaces enfocables (panel móvil del nav-menu de Pro), contraste (calcula el ratio con los colores reales, opacidad incluida sobre el fondo real, y propone el cambio mínimo que llega a 4,5:1 o 3:1, diciendo cuánto se aparta del prototipo), orden de encabezados, áreas táctiles, nombres de enlaces y botones, alt (e-image pinta el alt de la Biblioteca), etiquetas de formulario, ids duplicados, lang y hreflang, landmarks, orden del foco, consola, APIs obsoletas, cookies de terceros. Cada ítem con el arreglo exacto (selector y valores del hijo, cambio en una plantilla o filtro PHP), riesgo, si cambia lo visual y verificación.' },
  { key: 'internos', schema: PLAN, prompt: 'ROL: investigador de los internos de los plugins. Lee su código en el servidor (solo lectura) y documenta con exactitud: (a) cada ajuste de rendimiento de Elementor instalado y sus experimentos (qué hace, clave de opción y valores, si sirve con los elementos atómicos V4 y con el tema hijo; en el caso de referencia casi todo ya estaba activo y varios no hacían nada con V4, y «Carga de imágenes optimizada» sustituía la lógica de WordPress); cómo deja Elementor de pedir Google Fonts si se autoalojan; (b) ' + (A.optimizador ? 'el optimizador del hosting (' + A.optimizador + '): cada optimización de frontend, sus claves y comandos, exclusiones y conflictos conocidos con Elementor 4 y Pro (combinar o diferir el JS que necesitan el frontend, los formularios, el filtro del Loop Grid y el nav-menu)' : 'si hay un plugin de optimización del hosting, cada optimización, sus claves, exclusiones y conflictos con Elementor 4 y Pro') + '; (c) Polylang: para qué sirve su cookie de idioma en esta configuración y las consecuencias de define(\'PLL_COOKIE\', false) y dónde debe definirse (mu-plugin frente a wp-config). Devuelve la configuración recomendada como ítems con comandos exactos y exclusiones, marcando los arriesgados.' },
  { key: 'seo-copy', schema: SEO, prompt: 'ROL: estratega de SEO en el idioma del sitio para esta marca: ' + (A.marca || 'descríbela a partir del contenido publicado') + '. Lee las páginas publicadas (curl -s de cada URL y de cada elemento del contenido dinámico; lista con wp post list), el copy del prototipo y los documentos de marca. Propón la configuración de Yoast (representación, nombre del sitio y alternativo, separador, logo, perfiles sociales SOLO si el cliente los ha confirmado, imagen OG por defecto, tipos de contenido, archivos de autor y fecha, adjuntos, taxonomías, migas, alcance del sitemap) y, por página y por elemento del contenido dinámico: título SEO (≤ 60 caracteres), meta descripción (120–155, natural en el idioma del mercado), frase clave y relacionadas realistas, OG, tipo de página del esquema y notas de contenido. Títulos y descripciones deben coincidir con lo que la página dice: nada de claims inventados.' },
  { key: 'yoast-tecnico', schema: SEO, prompt: 'ROL: integrador técnico de Yoast. Desde el código del plugin en el servidor (solo lectura) y las opciones actuales (wp option get wpseo, wpseo_titles, wpseo_social…), documenta cómo configurar Yoast por script y con seguridad: claves de representación, separador, por tipo de contenido (title-<tipo>, metadesc-<tipo>, noindex-*, display-metabox-*, schema-page-type-*), archivos, migas, OG por defecto, marcas de primera instalación; metas por entrada (_yoast_wpseo_title, _yoast_wpseo_metadesc, _yoast_wpseo_focuskw y las de OG y esquema) y cómo reconstruir los indexables después (wp yoast index). Cómo trabaja Yoast con Polylang (hreflang, sitemaps por idioma, opciones traducibles), con el editor de Elementor y con blog_public=0 (qué sale y robots.txt). Si hay contenido dinámico con esquema propio, el PHP exacto de la pieza de esquema vía wpseo_schema_graph_pieces con sus metas, que acepte el resultado enriquecido de Google, y cómo verificar la salida (curl del <head> y del JSON-LD). Deja vacío el copy por página: céntrate en «site» e «implementation».' },
]

phase('Analizar')
const resultados = await parallel(ROLES.map(r => () =>
  agent(CTX + '\n\n' + r.prompt, { label: 'analizar:' + r.key, phase: 'Analizar', schema: r.schema })
    .then(x => (x ? { key: r.key, r: x } : null))
))
const ok = resultados.filter(Boolean)
const faltan = ROLES.map(r => r.key).filter(k => !ok.some(x => x.key === k))
if (faltan.length) log('Análisis que no volvieron (el plan tendrá ese hueco): ' + faltan.join(', '))

phase('Criticar')
const plan = await agent(CTX + `

ROL: crítico de completitud y planificador. Los analistas entregaron los informes de abajo (JSON). Tu trabajo:
1) Encuentra conflictos (uno propone combinar o diferir JS que otro demuestra que rompe Elementor), ítems arriesgados o no verificables y HUECOS: lo que falta para llegar a verde (≥ 90) en Performance, Accesibilidad, Buenas prácticas y SEO en móvil Y escritorio en todas las páginas (SEO sin contar el noindex intencionado del staging) y para una configuración completa de Yoast. Comprueba tú mismo las afirmaciones que más pesan contra los informes, el código de los plugins y el HTML servido (solo lectura); descarta con evidencia los arreglos que no cambian nada.
2) Devuelve UN plan fusionado, sin duplicados y ordenado (priority 1..n) que se pueda ejecutar paso a paso: cambio exacto, riesgo, visual_change y verificación. Los pasos de Yoast y SEO también como ítems (area "seo"); el copy por página se queda en el informe de SEO (referéncialo).
3) open_questions: SOLO decisiones que necesitan al cliente o al usuario (p. ej. el coste de diferir el vídeo del hero, cambiar un color de marca para llegar a 100 en accesibilidad, publicar perfiles sociales).
${faltan.length ? 'Faltan los análisis: ' + faltan.join(', ') + '. Dilo en summary y no inventes sus conclusiones.' : ''}

INFORMES:
${JSON.stringify(ok, null, 1)}`, { label: 'critico', phase: 'Criticar', schema: PLAN, effort: 'high' })

return {
  informes: ok,
  plan,
  siguiente: [
    'Respaldo en el servidor antes de tocar nada (BD, hijo, opciones y _elementor_data de los documentos a editar).',
    'Guardas urgentes primero (IndexNow y seguimiento apagados en staging), luego arreglos invisibles por lotes con regresión ±1 px (snap.mjs + cmp.py) y consola limpia.',
    'Punto de control con Lighthouse antes de proponer cambios visibles; los visibles se presentan como decisión.',
  ],
}
