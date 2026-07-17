# Auditoría Técnica y SEO — Habla Deportes

**Sitio auditado:** https://habladeportes.co/ (WordPress)
**Fecha:** 2026-07-13
**Autor:** agente `auditor-tecnico-seo` (Fase 1 — Diagnóstico)
**Método:** inspección directa del sitio real con navegador in-app (DOM, consola, red, Resource Timing API), verificación de `robots.txt`/sitemap por HTTP, pruebas mobile a 375 px. Solo diagnóstico; no se modificó nada.

> **Nota de medición (honestidad de datos):** las métricas de peso/tiempos se capturaron con **caché caliente** (CDN de Hostinger + LiteSpeed respondiendo `HIT`, muchos recursos con `transferSize = 0`). Por lo tanto **el peso real en la primera visita es mayor** que lo medido. No fue posible correr Lighthouse ni acceder a datos de campo de Core Web Vitals (CrUX), así que LCP/CLS/INP se estiman **cualitativamente** a partir de la arquitectura observada y se marcan como estimaciones. Los **conteos de requests, plugins, cabeceras HTTP y hallazgos de SEO/HTML son observaciones directas y firmes.**

---

## 1. Resumen ejecutivo

1. **El sitio no tiene SEO técnico funcional.** No hay meta descripción, **cero** etiquetas Open Graph/Twitter, **cero** datos estructurados (JSON-LD) y la home no tiene **ningún H1/H2**. No se detecta plugin SEO (Yoast/RankMath). Para un medio que vive del alcance y de compartir en redes, esto es una fuga directa de tráfico e ingresos. **(Riesgo #1)**
2. **El idioma declarado es incorrecto:** `<html lang="en-US">` en un sitio 100% en español. Afecta SEO, accesibilidad (lectores de pantalla) y señales de relevancia geográfica (Colombia). Corrección trivial, impacto alto.
3. **El contenido indexable es basura de demo, no editorial.** Los únicos "posts"/"podcasts" en el sitemap son contenido de plantilla ("hola-mundo", "greenland-unicorns-and-the-magical-alicorn", "when-chocolate-was-medicine"…). No hay artículos reales de fútbol colombiano indexados: la home es prácticamente **solo un carrusel de YouTube** sin texto rastreable.
4. **Peso muerto de plugins y un page-loader WebGL innecesario.** Se cargan **WooCommerce completo** (sin tienda), **Three.js r88 (WebGL)** para una animación decorativa de carga, jQuery + Migrate, varias fuentes de iconos duplicadas y un JS roto (404). ~38 scripts y ~33 hojas de estilo en la home. En una audiencia mayoritariamente móvil, esto castiga velocidad y Core Web Vitals.
5. **Higiene de seguridad floja pero base decente.** HTTPS y caché CDN OK, pero se exponen versiones (`WordPress 6.9.4`, `PHP/8.1.34`), no hay HSTS y la CSP es mínima. Sin errores JS críticos en consola, salvo el 404 de un asset.

**Oportunidad:** casi todos los problemas de SEO/HTML se resuelven "gratis" en el rediseño (marcado semántico + meta + schema), y el mayor lastre de rendimiento se elimina simplemente **no arrastrando** el tema `livecast` y sus plugins a la nueva implementación.

---

## 2. Hallazgos por categoría

### 2.1 Rendimiento / velocidad

| # | Hallazgo | Evidencia | Impacto | Esfuerzo |
|---|----------|-----------|---------|----------|
| P1 | **Page-loader WebGL con Three.js r88** | Consola: `THREE.WebGLRenderer 88`. Red: `three.min.js`, `OrbitControls.js`, `fast-simplex-noise.js`, `pace.min.js`, `page-loader-momentum.js` (tema `livecast`). Three r88 es de ~2017. | Alto — carga de CPU/GPU en el hilo principal solo para una animación de "loading"; retrasa el render y el INP en gama baja móvil. (El screenshot del navegador llegó a agotar 30 s por hang del renderer, consistente con carga pesada de JS/WebGL.) | Medio |
| P2 | **WooCommerce cargado en todo el sitio sin tienda** | Red: `woocommerce.css`, `woocommerce-layout.css`, `woocommerce-smallscreen.css`, `wc-blocks.css`, `add-to-cart.min.js`, `blockUI`, `sourcebuster`, `order-attribution`, `js.cookie`. Sitemap incluye `product`/`product_cat`. | Alto — ~7-9 requests CSS/JS puro desperdicio en cada carga. | Bajo (desactivar) |
| P3 | **Exceso de requests: ~38 scripts + ~33 hojas de estilo** | `scriptCount=38`, `styleCount=33` (DOM). Resource Timing contó 115 entradas (incluye reintentos/iframes). | Alto — sobrecarga de conexiones y parseo, especialmente en 3G/4G móvil. | Medio |
| P4 | **Fuentes de iconos duplicadas / múltiples** | Red: `fontawesome.min.css`, `brands.css`, `feather.css` + `feather.woff` (cargado dos veces por tema y por plugin), `codeless-livecast-icons.css`, `audioplayer-font.ttf`, `iconos cowidgets`. | Medio — varias descargas de fuentes de iconos para pocos glifos. | Bajo |
| P5 | **jQuery + jQuery Migrate (legacy)** | Consola: `JQMIGRATE: Migrate is installed, version 3.4.1`. Red: `jquery.min.js 3.7.1` + `jquery-migrate.min.js`. | Medio — dependencia pesada y capa de compatibilidad heredada. | Medio |
| P6 | **Request roto (404) repetido** | Red: `cowidgets-elementor-addons/assets/js/lib/swiper.min.js` → **404 (ERR_ABORTED)** en cada carga. | Bajo-Medio — round-trip desperdiciado + posible JS que no inicializa. | Bajo |
| P7 | **Imagen hero sin optimizar (JPEG de WhatsApp)** | Red: `WhatsApp-Image-2026-04-15-...jpeg` ~**88 KB** (el recurso más pesado), servido como JPEG (no WebP/AVIF) y en variante `1536x568`. Nombres "WhatsApp-Image" sugieren subida sin flujo de optimización. | Medio — mayor imagen del LCP potencial sin formato moderno. | Bajo |
| P8 | **Terceros: AdSense + reCAPTCHA** | DOM: iframes de `googleads.g.doubleclick.net` (~60 KB), `reactive_library_fy2021.js` (~66 KB), `google.com/recaptcha/api2`. `ins.adsbygoogle` presente. | Medio — JS de terceros compite por el hilo principal y añade CLS/latencia. | Medio |

**Datos cuantificados (caché caliente, subestiman el peso real):** ~232 KB transferidos medidos, `domContentLoaded` ~453 ms, `load` ~1.6 s, 457 nodos DOM en la home. Conteo de requests fiable: **~38 JS + ~33 CSS + fuentes + imágenes → del orden de 90+ peticiones en primera carga.**

**Core Web Vitals (estimación cualitativa, no medida):**
- **LCP:** en riesgo por render-blocking (decenas de CSS/JS en `<head>`) + page-loader WebGL + hero JPEG sin optimizar.
- **CLS:** riesgo por ausencia de `width/height` explícitos en varias imágenes del carrusel y por inserción tardía de anuncios AdSense.
- **INP:** en riesgo por Three.js/WebGL y jQuery en el hilo principal en dispositivos móviles de gama media/baja.

---

### 2.2 Mobile / responsive

| # | Hallazgo | Evidencia | Impacto | Esfuerzo |
|---|----------|-----------|---------|----------|
| M1 | **Viewport correcto** | `<meta name="viewport" content="width=device-width, initial-scale=1">`. | Positivo | — |
| M2 | **Sin scroll horizontal a 375 px** | Prueba a 375 px: `scrollWidth == clientWidth == 375`, `horizontalOverflow=false`, cero elementos desbordados. | Positivo | — |
| M3 | **Peso de JS/WebGL penaliza a la audiencia móvil** | Ver P1/P3/P5. La audiencia es mayoritariamente móvil (brief). | Alto — la velocidad percibida en móvil es el ingreso. | Medio |

El responsive base funciona (viewport + sin overflow), pero el problema mobile real es **rendimiento**, no layout.

---

### 2.3 SEO técnico

| # | Hallazgo | Evidencia | Impacto | Esfuerzo |
|---|----------|-----------|---------|----------|
| S1 | **Sin meta descripción** (home ni post) | `meta[name=description]` = ausente en home y en post de prueba. | Alto — Google genera snippet arbitrario; peor CTR. | Bajo |
| S2 | **Cero Open Graph / Twitter Cards** | `meta[property^="og:"]` = `[]`, `meta[name^="twitter:"]` = `[]` en home y post. | **Muy alto** — al compartir en WhatsApp/Facebook/X (canal natural de un medio video-first) no hay título, imagen ni descripción. Fuga directa de alcance. | Bajo |
| S3 | **Cero datos estructurados (JSON-LD)** | `script[type=application/ld+json]` = `[]` en home y en post. Sin `NewsArticle`, `VideoObject`, `Organization` ni `BreadcrumbList`. | **Muy alto** — sin elegibilidad para resultados enriquecidos, Google News, carrusel de videos ni miniaturas. Clave para un medio deportivo. | Medio |
| S4 | **Home sin H1/H2/H3** | `h1=[]`, `h2=[]`, `h3count=0` en la portada. | Alto — Google no entiende el tema principal de la home. | Medio |
| S5 | **`lang="en-US"` en sitio en español** | `document.documentElement.lang = "en-US"`. | Alto — señal de idioma/geo equivocada para un medio colombiano. | Trivial |
| S6 | **Title de la home genérico** | `document.title = "Habla Deportes"` (sin keywords ni propuesta). Los posts sí usan patrón `Título – Habla Deportes` (78 chars). | Medio — desaprovecha el título más valioso del sitio. | Bajo |
| S7 | **Contenido indexable = demo de plantilla** | Sitemap `wp-sitemap-posts-post-1.xml` y `-podcast-1.xml` solo contienen posts de demostración ("hola-mundo", "greenland-unicorns…", "when-chocolate-was-medicine…", "startups-podcast-episode-13…"). No hay artículos reales de Selección Colombia/Junior FC. | **Muy alto** — el sitio no tiene contenido de texto rastreable propio; todo el SEO depende de páginas basura que incluso pueden diluir autoridad. | Medio-Alto (editorial) |
| S8 | **Sin plugin SEO** | Ausencia combinada de description/OG/schema + uso del sitemap **core** `wp-sitemap.xml` (no de Yoast/RankMath). | Alto — no hay capa que gestione meta/schema/social a escala. | Medio |
| S9 | **`robots.txt` y sitemap presentes y válidos** | `robots.txt` correcto (bloquea `/wp-admin/`, permite `admin-ajax.php`, apunta a `wp-sitemap.xml`); `wp-sitemap.xml` → 200; `sitemap.xml` → 301. | Positivo (base OK). | — |
| S10 | **Post template sí tiene H1 + `<article>`** | Post de prueba: 1×H1, 4×H2, `<article>` presente, pero **sin `<time>`/fecha marcada** y sin schema. | Medio — el marcado semántico del artículo es mejorable pero existe. | Bajo |

---

### 2.4 Accesibilidad (a11y) — WCAG orientativo

| # | Hallazgo | Evidencia | Impacto | Esfuerzo |
|---|----------|-----------|---------|----------|
| A1 | **`lang` incorrecto** | `lang="en-US"` (ver S5) — el lector de pantalla lee español con fonética inglesa. | Alto (WCAG 3.1.1). | Trivial |
| A2 | **Alt genéricos/vacíos en la mayoría de imágenes** | 20 de 29 `<img>` con alt genérico o vacío (`site-logo`, `play`, `loading`, `subscribe`, `YouTube player`, `Placeholder Image`). | Medio (WCAG 1.1.1) — las miniaturas de video no describen el contenido. | Bajo |
| A3 | **Campo de búsqueda sin `<label>`** | `form label = 0`, `input = 1` (solo `placeholder="Search"`). | Medio (WCAG 1.3.1/4.1.2). | Bajo |
| A4 | **Enlaces vacíos `href="#"` / `javascript:void(0)`** | 4 enlaces sin destino real; sin propósito para teclado. | Bajo-Medio (WCAG 2.4.4). | Bajo |
| A5 | **Sin "skip link" y sin landmark `<nav>`** | `hasSkipLink=false`; `<nav>`=ausente (el header usa `div`/`region`). `<main>` sí existe. | Medio (WCAG 2.4.1/1.3.1) — navegación por teclado/lector menos eficiente. | Bajo |
| A6 | **Contraste: no verificado con precisión** | No se pudo medir ratios exactos en esta pasada (tema oscuro con acentos). **Se declara como pendiente**, a validar en el rediseño. | — | — |

Nivel orientativo: **por debajo de WCAG 2.1 AA** por `lang`, labels y textos alternativos, aunque la estructura base (`<main>`, botones con nombre accesible) es parcialmente correcta.

---

### 2.5 Higiene / buenas prácticas / integraciones

| # | Hallazgo | Evidencia | Impacto | Esfuerzo |
|---|----------|-----------|---------|----------|
| H1 | **HTTPS + CDN + caché OK** | Cabeceras: `content-security-policy: upgrade-insecure-requests`, `x-litespeed-cache: HIT`, `x-hcdn-cache-status: HIT` (Hostinger). | Positivo. | — |
| H2 | **Versiones expuestas** | `<meta name="generator" content="WordPress 6.9.4">`; cabecera `x-powered-by: PHP/8.1.34`. | Medio — facilita fingerprinting de vulnerabilidades. | Trivial |
| H3 | **Sin HSTS y CSP mínima** | No hay `Strict-Transport-Security`; CSP solo `upgrade-insecure-requests`. | Medio — endurecimiento de seguridad pendiente. | Bajo |
| H4 | **Stack de plugins pesado detectado** | Elementor 3.25.7, WooCommerce 9.4.5, Contact Form 7 5.9.5, YouTube Embed Plus 14.2.6, WP-Statistics 14.11.3, cowidgets-elementor-addons 1.1.1, codeless-player 1.0.2, tema `livecast` 6.9.4. | Medio-Alto — superficie de mantenimiento/rendimiento grande para un sitio esencialmente de videos. | Medio |
| H5 | **Sin errores JS críticos en consola** | Solo logs de `JQMIGRATE` y `THREE.WebGLRenderer 88`; ningún error rojo salvo el 404 de P6. | Positivo. | — |
| H6 | **Integración YouTube vía plugin, no iframes nativos en home** | La home usa carrusel de miniaturas (plugin YouTube Embed Plus + `fitvids`) con thumbnails, no 265 iframes — bien para rendimiento; el peso viene del tema/WebGL, no de los embeds. | Positivo (matiz). | — |
| H7 | **AdSense de sponsors/red** | `ca-pub-2899342978273998` (doubleclick) + reCAPTCHA. Además banners de sponsor propio (Transelca) como imágenes/enlaces directos. | Medio — mezcla de monetización programática y patrocinio directo; los iframes de AdSense son el principal coste de terceros. | Medio |

---

## 3. Recomendaciones priorizadas

Prioridad = impacto en alcance/ingresos ÷ esfuerzo. **[HTML]** = debe quedar resuelto ya en el prototipo del rediseño (es marcado/estructura). **[WP]** = se resuelve en la implementación WordPress (plugins, servidor, plantillas).

### P0 — Crítico (hacer sí o sí)
1. **[HTML]** Estructura semántica correcta en cada plantilla: un `<h1>` por página (incluida la home), jerarquía H2/H3, landmarks `<header><nav><main><footer>`, skip-link. *(Resuelve S4, A5.)*
2. **[HTML]** `lang="es-CO"` (o `es`) en `<html>` desde el prototipo. *(S5, A1 — trivial, alto impacto.)*
3. **[HTML]** Meta base por plantilla: `<title>` único con keywords + marca, `meta description`, canonical, y **Open Graph + Twitter Card completos** (title, description, image, type=article/video). *(S1, S2, S6.)*
4. **[HTML]** Datos estructurados JSON-LD: `Organization`/`WebSite` en home, `NewsArticle` en notas, **`VideoObject`** en cada video (clave para un medio video-first), `BreadcrumbList`. Dejar el marcado listo en el prototipo. *(S3.)*

### P1 — Alto (rediseño / primeras semanas de WP)
5. **[WP]** **No arrastrar el tema `livecast` ni su page-loader WebGL** al nuevo sitio. Eliminar Three.js/OrbitControls/pace/momentum-loader. *(P1.)*
6. **[WP]** **Desactivar/eliminar WooCommerce** si no habrá tienda; si se necesita para algo puntual, cargarlo solo donde aplique. *(P2.)*
7. **[WP]** Instalar y configurar un **plugin SEO** (RankMath/Yoast) que genere meta, OG y schema a escala editorial, y consolidar el sitemap. *(S1-S3, S8.)*
8. **[WP]** **Limpiar el contenido demo** (posts/podcasts de plantilla) y definir arquitectura editorial real; hasta entonces, `noindex` a las páginas basura. *(S7.)*
9. **[HTML]** Imágenes con `width/height` explícitos y `loading="lazy"` fuera del viewport; servir **WebP/AVIF** y tamaños responsive. Reduce CLS y peso. *(P7, CLS.)*

### P2 — Medio (optimización continua)
10. **[WP]** Reducir requests: combinar/purgar CSS no usado, eliminar fuentes de iconos duplicadas, quitar jQuery Migrate y minimizar dependencia de jQuery. *(P3, P4, P5.)*
11. **[WP]** Corregir el 404 de `swiper.min.js` (cowidgets) o retirar el addon. *(P6.)*
12. **[WP]** Endurecer seguridad: quitar `generator`/`x-powered-by`, añadir HSTS y una CSP real. *(H2, H3.)*
13. **[HTML/WP]** A11y: `alt` descriptivos en miniaturas, `<label>` en el buscador, revisar **contraste AA** (pendiente de medir), foco visible y navegación por teclado. *(A2, A3, A4, A6.)*
14. **[WP]** Diferir/optimizar terceros (AdSense, reCAPTCHA solo donde haya formularios) para proteger LCP/INP. *(P8.)*

---

## 4. Qué se resuelve en el HTML vs. en WordPress

**Ya en el prototipo HTML (Fase 2 — barato y alto impacto):** semántica y jerarquía de encabezados, `lang="es-CO"`, `<title>`/meta description por vista, Open Graph/Twitter, plantillas JSON-LD (`NewsArticle`/`VideoObject`/`Organization`), imágenes con dimensiones + formatos modernos + lazy-load, landmarks + skip-link, labels y alt descriptivos. Es decir: **todo el SEO on-page, el social sharing y la base de a11y nacen resueltos en el rediseño**, sin depender de WordPress.

**En la implementación WordPress (post-aprobación):** cambio de tema (fuera `livecast`/WebGL), desactivación de WooCommerce, plugin SEO para escalar meta/schema, limpieza de contenido demo y arquitectura editorial, reducción de plugins/requests, endurecimiento de seguridad (HSTS/CSP/ocultar versiones) y gestión de terceros (AdSense/reCAPTCHA). Es decir: **rendimiento, higiene y escalado editorial** se materializan aquí.

---

*Limitaciones de esta auditoría: métricas de peso/tiempos medidas con caché caliente (peso real de primera visita superior); no se ejecutó Lighthouse ni se consultaron datos de campo de Core Web Vitals; el contraste de color exacto quedó pendiente de medición. Los conteos de requests, plugins, cabeceras, y todos los hallazgos de SEO/HTML/a11y estructural son observaciones directas del sitio real.*
