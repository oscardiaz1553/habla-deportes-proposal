# Observaciones del sitio actual — habladeportes.co

> Registro de referencia capturado durante la auditoría UX/UI (2026-07-13).
> **Nota sobre capturas:** el motor de captura de pantalla del navegador in-app
> quedó bloqueado de forma consistente en este sitio (timeouts de 30 s en cada
> intento, incluso con la página en `readyState=complete` y sin iframes de
> anuncios). El bloqueo es atribuible al comportamiento de la página (inyección
> continua de anuncios de Google AdSense / posible anti-bot). En lugar de PNGs se
> preserva aquí el **árbol de accesibilidad, el texto renderizado y los datos del
> backend WordPress**, que son la evidencia real sobre la que se basa el
> diagnóstico.

## Stack y tecnología (observado)
- **CMS:** WordPress 6.9.4 (`meta[name=generator]`).
- **Tema:** `livecast` (body class `theme-livecast`, `ce-template-livecast`).
- **Constructor:** Elementor + plugin de galería de YouTube (`epyt-facade`,
  `codeless-player-toggle`, `codeless` gallery).
- **WooCommerce activo** (body class `woocommerce-js`; páginas Tienda/Carrito/
  Mi cuenta/Finalizar compra existen).
- **Publicidad:** iframes de Google AdSense (pub-2899342978273998 /
  ca-host-pub-2644536267352236) inyectados en el DOM, incluso sobre contenido.

## Estructura real (WP REST API)
Páginas publicadas (`/wp-json/wp/v2/pages`):
`Inicio`, `Tienda`, `Carrito`, `Mi cuenta`, `Finalizar compra`,
`Política de privacidad`, `Términos y Condiciones`.
→ No existe página de "Radio", "Selección", "Junior", "Noticias" ni "Contacto".

Categorías (`/wp-json/wp/v2/categories`):
`Blog`, `Branding`, `Marketing`, `Optimization`, `Planning`, `SEO`, `Society`,
`Web`, `Uncategorized`.
→ **Ninguna categoría deportiva.** Son las categorías demo del tema.

Entradas (`/wp-json/wp/v2/posts`) — todo contenido demo del tema, en inglés,
2021:
`¡Hola, mundo!`, `Easy Strategies to Help Your Family Succeed in 2020`,
`Women Balancing Family And Work During COVID-19`,
`When Chocolate was Medicine: Colmenero, Wadsworth, and Dufour`, etc.
→ **El sitio no tiene contenido editorial propio de noticias.** Al entrar a
cualquier "nota" se muestra un artículo demo del tema sin relación con el fútbol.

## Home (una sola pantalla)
- **Header:** logo (`LogoHablaDeportes.png`, 2021), enlace a patrocinador
  Transelca, enlace a YouTube etiquetado **"YOTUBE"** (mal escrito), y un icono
  de búsqueda (`#`). **No hay menú de navegación con secciones.**
- **Main:** es en su totalidad una **galería/carrusel de YouTube** con contador
  "265" videos, botones Anterior/Siguiente, botón "Suscríbete al canal" y ~44
  miniaturas de video. Títulos reales de video sí son de fútbol (Selección,
  Junior, fichajes) — el contenido de valor existe, pero solo como thumbnails de
  YouTube.
- **Footer:** logo, texto "El show de las mañanas en Barranquilla... 10:00 a.m. a
  12:00 m.", logo Transelca, menú (solo Política de privacidad / Términos),
  Dirección "Barranquilla" (sin dirección real), contacto info@habladeportes.co,
  "© 2026 — Habladeportes / Realizado por spacerock".

## Radio
- Sin elemento `<audio>` ni reproductor/stream en el sitio.
- La radio solo se menciona como texto en el footer (horario). No hay forma de
  escuchar en vivo ni "en vivo" visible.

## Identidad visual (estilos computados)
- **Tipografía:** una sola familia, **Rubik** (sans-serif genérica) para todo.
- **Fondo body:** `#F6FAFC` (gris muy claro).
- **Texto body:** `#6D727C` (gris medio-claro → contraste bajo sobre fondo casi
  blanco).
- **Color de acento / CTA principal:** rojo **#E62117** (rojo tipo YouTube), usado
  en el botón "Suscríbete".
- 29 imágenes, 44 tiles de video.

## Mobile (375×812)
- **Sin menú hamburguesa ni navegación**: header solo muestra logo, Transelca,
  "Yotube" y búsqueda. El único "menú" real está en el footer (privacidad/
  términos).
- Sin overflow horizontal (bien).
- Botón "Suscríbete" mide **163×38 px** → altura 38 px, por debajo del mínimo
  recomendado de 44 px para objetivo táctil.

## Otros
- Etiqueta de YouTube mal escrita: **"YOTUBE" / "Yotube"** (visible en header y en
  el texto renderizado). Afecta credibilidad.
- Footer muestra "Dirección: Barranquilla" sin dirección concreta.
- Créditos "Realizado por spacerock" visibles en el footer del cliente.
