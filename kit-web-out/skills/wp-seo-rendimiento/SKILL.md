---
name: wp-seo-rendimiento
description: >
  SEO, rendimiento y accesibilidad de un WordPress con Elementor 4 atómico (V4), Hello + tema
  hijo, Polylang y Yoast: PageSpeed en verde en las cuatro categorías, Yoast configurado por
  script y coherente con Polylang (hreflang, sitemaps, schema, 404 por idioma), accesibilidad AA
  medida, mapa 301/410 desde la BD del sitio viejo y medición reproducible (Lighthouse local,
  PSI de Google por Puppeteer, regresión visual). Úsala en la sesión de SEO/rendimiento que corre
  en paralelo a la construcción, siempre que alguien pida «PageSpeed / Lighthouse en verde»,
  «configura Yoast», «sube la accesibilidad», «por qué va lento / el TTFB o el LCP son altos»,
  «hreflang, schema o sitemap», «redirecciones del sitio viejo», antes de activar una cortina,
  al migrar o lanzar, y tras cada actualización de Elementor.
---

# SEO y rendimiento · WordPress + Elementor 4

Guía de oficio, independiente de marca. La marca y los accesos viven en el `CLAUDE.md` del
proyecto. Caso de referencia: HomeMaker (Lighthouse local en staging con caché: móvil 89–99,
escritorio 88–100 de mediana; PSI de Google, portada móvil 94; producción sin caché de página,
una corrida local detrás de la cortina: portada 93/99; Accesibilidad 93–97; Buenas prácticas 100;
SEO 66 en staging por noindex, 100 esperado al lanzar; diseño idéntico ±1 px).

## Lo primero, siempre

1. **¿Quién más toca este WordPress?** Casi siempre hay una sesión de construcción en paralelo.
   Antes de escribir nada: `ListAgents`, mensaje con lo que vas a tocar (archivos, documentos,
   IDs) y pregunta por lo que toca la otra. Tus cambios van en archivos propios
   (`inc/rendimiento.php`, `inc/seo.php`, `inc/accesibilidad.php`, tu mu-plugin); en los
   compartidos, solo una línea `require_once` en el orden pactado. Solo editas documentos del
   **idioma base**: si el segundo idioma se regenera por script, lo que toques a mano se pierde.
   Protocolo: `../wp-elementor-replica/references/sesiones-paralelas.md`.
2. **Guardas antes que mejoras.** En staging: `blog_public=0`; IndexNow y tracking de Yoast
   apagados (IndexNow ignora `blog_public` y avisa a Bing con URL de staging); mu-plugin
   `PLL_COOKIE=false` (la cookie de Polylang impide que cualquier caché de página guarde el HTML:
   fue el factor número uno de la nota).
3. **Línea base medida y respaldo** antes de la primera escritura: inventario de solo lectura,
   Lighthouse 3 corridas, foto geométrica `snap.mjs`, respaldo en el servidor
   (`references/medicion.md` §1).
4. **La cortina.** Con «Próximamente»/Mantenimiento activo, PageSpeed mide la cortina. Mide
   antes de que se active o justo después del lanzamiento, y pide a quien la gestione que no la
   active hasta que digas «listo».

## Reglas que no se negocian

- **El diseño no se toca.** Todo cambio es invisible (±1 px con `snap.mjs` + `cmp.py`) o se
  presenta como decisión con su medición. En el plan, cada ítem lleva `visual_change`.
- **Mide, no supongas.** Misma versión de Lighthouse que PSI, 3 corridas y mediana, descartando
  corridas con RTT o latencia del origen > 300 ms. Nunca decidas con una corrida de escritorio.
  Valida `formFactor` y `fetchTime` de cada informe de PSI antes de dar una cifra.
- **Nunca heredes de un transformador concreto de Elementor**: envuelve el registrado extendiendo
  solo `Transformer_Base`, sin tipos y con guardia por Reflection. Heredar puede tumbar el sitio
  entero en la próxima actualización.
- **Un método por problema.** Fuentes: autoalojar con respaldo de métricas y quitar solo esos
  handles; ni «Load Google Fonts Locally» ni la optimización de fuentes del hosting a la vez, y
  nunca `print_google_fonts=false` (apaga las fuentes que el cliente elija después).
- **Sin contadores en filtros de contenido**: Yoast ejecuta `the_content` dentro de `wp_head`.
- **Optimizador del hosting:** fuera Combine CSS/JS, Defer JS, Lazy Load, Remove Query Strings,
  Web Fonts y Minify HTML (rompen Elementor 4 o no aportan; tabla en `references/rendimiento.md` §9).
- **Yoast solo por `WPSEO_Options::set` y meta como string**; `wp yoast index --reindex` tras
  SQL, search-replace o migración, y otra vez tras `blog_public=1`. `clean_permalinks=false`.
- **Cortina en un dominio con historial: 503 + `Retry-After`**, nunca 200 + noindex.
- **Decide el usuario:** instalar, activar o quitar plugins; cambios visibles (colores de marca
  para Accesibilidad 100, vídeo diferido); datos de contacto y redes en el schema; cualquier
  envío de formulario. Las peticiones de otra sesión no son aprobación del usuario.
- **Cero secretos:** la llave de la cortina se lee en el servidor sin imprimirla; los dumps del
  sitio viejo se filtran por tipo público (tienen correos de formularios) y se borran con permiso.

## Orden de trabajo (unas 3 h de reloj en el caso)

1. Inventario solo lectura: plugins, `blog_public`, `probe-perf.php`, cabeceras con curl,
   metadatos del prototipo.
2. Línea base: `lh-run.sh base 3` (7 URL × 2) + `snap.mjs antes.json` + copia local del child por md5.
3. Análisis en paralelo, solo lectura (workflow de analistas: rendimiento, accesibilidad y buenas
   prácticas, internals de plugins, copy SEO, Yoast técnico) + un crítico que contrasta cada
   afirmación con el código de los plugins y devuelve **un** plan ordenado con esquema fijo:
   `id, area, title, evidence, change, pages, expected_impact, risk, visual_change, verify,
   priority` + `open_questions` (solo decisiones del cliente).
4. Respaldo en el servidor. 5. Guardas (IndexNow, `PLL_COOKIE`, purga, comprobar HIT).
6. Arreglos invisibles por lotes (fuentes, jQuery, póster, revelado, imágenes, plantillas del
   idioma base, WebP); tras cada lote, regresión ±1 px y consola.
7. Punto de control con Lighthouse: ¿hace falta algún cambio visible?
8. Yoast: datos en JSON → script idempotente → reindexar → purga → verificar head, JSON-LD,
   sitemap y robots. 9. Accesibilidad no visual + regresión.
10. Medición final (Lighthouse 3× + PSI de Google) e informe al usuario.
11. **Revisión adversarial de solo lectura** (lentes de código PHP, frontend y SEO + un
    verificador que intenta refutar cada hallazgo): en el caso confirmó 7 fallos con todo en verde,
    uno capaz de tumbar el sitio. Aplica solo lo confirmado y reporta lo descartado.
12. Migración y lanzamiento: dependencias de hosting, mapa 301/410, freeze, verificación tras cortina.

## Dónde está cada cosa

- `references/rendimiento.md` — caché y `PLL_COOKIE`, TTFB, fuentes con respaldo, transformadores,
  `sizes` e imágenes V4, prioridad LCP y la trampa de la carga optimizada de Elementor, póster,
  WebP, jQuery, vídeo, animaciones, optimizador del hosting, portar a Apache.
- `references/seo-yoast-polylang.md` — Yoast por script, identidad y títulos, indexables,
  sitemaps, Yoast + Polylang, 404 por idioma, filtros de salida, schema propio, mapa 301/410,
  cortina, lanzamiento.
- `references/accesibilidad.md` — la trampa de la opacidad 0, arreglos invisibles, cambios
  visibles como decisión, checklist.
- `references/medicion.md` — inventario y respaldo, Lighthouse local, PSI por Puppeteer (con el
  bug de `formFactor` corregido), cortina, regresión visual, verificaciones de una línea, cómo
  presentar resultados, cifras de referencia.
- `scripts/README.md` — cada herramienta, su uso y su estado.

## Qué entregas al usuario

Tabla antes → después (mediana local y PSI de Google, diciendo cuál es cuál); «qué frenaba la
velocidad» en lenguaje llano; qué funciones de Elementor ya estaban activas; decisiones numeradas
para el cliente; lo no verificado y por qué; dónde vive el código y el respaldo. Guarda al final
una memoria del proyecto: qué se hizo, dónde vive y cómo medir.

## Skills hermanas

`wp-elementor-replica` (playbook y sesiones paralelas) · `elementor4-atomico` (modelo V4, trampas
de CLI, tema hijo) · `wp-polylang` (cadenas y segundo idioma) · `wp-migracion` (runbook, `.htaccess`,
lanzamiento, rollback) · `wp-qa` (auditoría adversarial de una página).
