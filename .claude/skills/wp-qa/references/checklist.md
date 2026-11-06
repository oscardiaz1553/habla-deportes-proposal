# Checklist de QA · página o plantilla Elementor 4

Recórrelo entero antes de decir que una página, plantilla del Theme Builder, cabecera o pie está
lista. Cada casilla se cierra con una **medición** (el código entre paréntesis remite a
`mediciones.md`), no con una lectura del código. Si una casilla no aplica, dilo; si no pudiste
medirla, márcala **no verificado**.

Protocolo general de verificación del proyecto (cuándo se verifica, revisión adversarial con
workflows, capturas para el usuario): [verificacion.md](../../wp-elementor-replica/references/verificacion.md).

## A · Antes de medir

- [ ] Tienes la **spec del prototipo** de esta página (`spec-prototipo.md`: medidas computadas por
      ancho) y la lista **«conocido y aceptado»** (decisiones deliberadas que no son fallos:
      breakpoints de Elementor 767/1024/1279 en vez de los 640/1024/1280 de Tailwind, añadidos
      pactados, defectos del prototipo que a propósito no se copiaron). Sin la lista, reportarás
      decisiones como fallos. (cómo se hace la spec: [prototipo-a-spec.md](../../wp-elementor-replica/references/prototipo-a-spec.md))
- [ ] El servidor sirve lo último: `?ver=` del child = `filemtime`, caché purgada tras el último
      despliegue, sin `set-cookie: pll_language`. (M1)
- [ ] Sabes si la cortina o el modo mantenimiento están encendidos. No los tocas: si están
      encendidos, mides con `render.php` por WP-CLI o con la cookie de la llave. (M1, M17)
- [ ] Mides **sin sesión** en el sitio. Con sesión, la barra de administración mueve la cabecera
      fija y los sticky 32/46 px y crea falsos fallos.
- [ ] Si eres subagente: **sin herramientas de navegador compartidas**. Curl, sondas PHP de solo
      lectura y los scripts de [`../scripts/`](../scripts/README.md) (`qa-medir.mjs`,
      `qa-hover-foco.mjs`) con Chrome headless propio. (M5)

## B · Fidelidad al prototipo (mide, no mires)

- [ ] Animaciones de entrada en su estado final en los dos lados antes de medir; pestaña visible.
      (M2)
- [ ] Mismo script en prototipo y WP a **1440, 1280, 768 y 375**. 1280 es el primer ancho por encima
      de `tablet_extra` (≤1279, que hace de `xl` de Tailwind); 768 es el primero por encima de `mobile`. Si la
      spec cambia en un borde de breakpoint, mide también 1279, 1024 y 767. Cabecera y pie, además en
      los anchos intermedios de su spec (en el caso de referencia, 1100 y 900). (M3, M5)
- [ ] Cada sección de primer nivel: `top`, ancho y alto dentro de la **tolerancia canónica**
      ([verificacion.md](../../wp-elementor-replica/references/verificacion.md)): objetivo ≤1 px; se
      aceptan ≤3 px por sección solo en anchos intermedios (1100, 900, bordes de breakpoint); el
      idioma 2 frente al idioma base, ≤1 px.
      Alto total del documento igual. (M6)
- [ ] Sin scroll horizontal en ningún ancho: `scrollWidth` ≤ `clientWidth`. (M3, M5)
- [ ] Cada diferencia fuera de tolerancia tiene **origen localizado** (qué sección, qué propiedad) y
      comprobado que no es una decisión registrada. Si es decisión, va a «conocido y aceptado», no al
      informe de fallos.
- [ ] Contenedores atómicos sin los 10 px de padding ni el `min-width:30px` de base donde el
      prototipo no los tiene: `total: 0`, cabecera y pie incluidos. (M7)
- [ ] `e-grid` en móvil con su variante móvil **explícita**: la base vuelve a aplicar su
      `grid-template-columns` en móvil y pisa la herencia de tablet. Mide
      `getComputedStyle(grid).gridTemplateColumns` a 375.
- [ ] Anclas: `/pagina/#ancla` desde otra página salta en seco y deja el destino a su
      `scroll-margin-top`, en 1440 y 375; `html{scroll-behavior:auto}` en el child si el prototipo no
      usa smooth global. (M8)
- [ ] Sticky y cabecera fija: `position` y `top` a lo largo del scroll, sin sesión; y con la clase
      `admin-bar`, los desplazamientos de los cuatro tramos (>782, ≤1024, ≤782, ≤600). (M8)
- [ ] Contenido visible con opacidad 0 = 0 tras recorrer la página con scroll rápido. Sin JS la
      página se ve entera: en el `style.css` del child, ninguna regla que oculte para animar
      (`opacity:0`, `display:none` de estados) está fuera de `html.<PREFIJO>-js`. (M5)

## C · Tipografía y color computados

- [ ] `fontFamily`, `fontSize`, `lineHeight`, `fontWeight`, `letterSpacing`, `color` y
      `textTransform` de títulos, textos, botones y enlaces iguales al prototipo **computado**. El
      código del prototipo miente a veces: en el caso de referencia, 60 usos de `font-600/700/800` no
      existían en Tailwind v4 y todos caían a 400. (M3; M5 + M6 con `--todo`)
- [ ] `clamp()` y unidades rem/em del prototipo copiadas literalmente: el tamaño coincide a 1440,
      1024, 768 y 375, no solo en un ancho.
- [ ] Las fuentes que se ven son las que se cargaron (`document.fonts` con estado `loaded`) y no
      hay Google Fonts de más si el proyecto las sirve desde el child. (M5, M19)
- [ ] Colores: valor computado igual al hex de la spec, y las variables V4 existen en `:root` con su
      etiqueta como nombre. (M11)
- [ ] Ningún color marcado «solo fondo» en la paleta usado como texto.

## D · Clases globales y CSS generado

- [ ] **Orden frontend = preview** (`iguales: sí`). Es lo primero que se mira: si no, la primera vez
      que alguien pulse «Publicar» en el editor se borran del sitio las clases creadas por script.
      Obligatorio tras cada script de clases y tras cada migración. (M11)
- [ ] Toda clase referenciada por el documento existe en el repositorio frontend **y** en preview.
      (M12)
- [ ] Las clases de la página aparecen en `global-<ID_DOC>-frontend-<breakpoint>.css`. Una clase que
      solo lleva un widget V3 (Loop Grid, filtro, Form, Shortcode) no llega: el widget va envuelto en
      un `e-flexbox` que lleve la clase. (M11)
- [ ] Prioridad: entre clases globales gana la **primera del orden**. Cada modificador (`--x`) va
      antes que su base; si un modificador no se aplica, compara sus posiciones en el orden antes de
      tocar valores.
- [ ] El CSS del child que compite con estilos atómicos empieza por `.elementor ` (también dentro de
      `@media` escritas en una línea): sin el prefijo pierde contra `.elementor .<clase>` (0,2,0).

## E · Editable en el editor (los controles responden)

- [ ] Datos que el editor usará, validados con los parsers de Elementor en modo editor: sin
      `ISSUE` (props inválidas, claves fuera del esquema, ids duplicados o vacíos, clases ausentes en
      preview, Loop Grid con plantilla no publicada, filtro que no apunta a su grid). (M12)
- [ ] **Un solo control por propiedad.** Si el CSS del child fija una propiedad que la clase V4 del
      mismo elemento también fija (con más especificidad o `!important`), el control del panel
      Estilo queda muerto: el cliente lo mueve y no pasa nada. `grep` en el `style.css` del child de
      los selectores con `<PREFIJO>-` y compara sus propiedades con las variantes de la clase. El CSS
      del child solo expresa lo que V4 no puede (pseudo-elementos, estados entre elementos,
      máscaras, animaciones).
- [ ] Todo lo oculto por JS depende de `html.<PREFIJO>-js`, que no se añade con `?elementor-preview=`:
      en el editor se ven todas las regiones, pestañas y entradas animadas.
- [ ] Las cajas vacías usadas como icono no se pintan en el editor como zonas de soltar de 120 px
      (`.e-atomic-element[data-<PREFIJO>-icono]:has(> .elementor-empty-view){min-height:0!important}`).
- [ ] Textos editables donde el cliente los busca: dentro de los widgets, en Avanzado › Atributos
      (`data-*` que lee el JS) o en la Biblioteca de medios (alt). Valores propios de una instancia,
      en su **estilo local** (panel Estilo), no en una clase global.
- [ ] Títulos legibles en el Navegador de Elementor (`editor_settings.title`), no «Contenedor».
- [ ] **Tarea para el usuario**, siempre: «abre X en el editor, cambia Y, publica y avísame». Queda
      como pendiente explícito hasta que confirme; justo después se repite la primera casilla de D
      (orden frontend = preview) por si el editor tocó algo.

## F · Hover y foco

- [ ] Toda variante `hover` de un botón o enlace declara **fondo y color de texto**. Si no, el
      `a:hover` del kit (0,2,1) pinta el texto y desaparece (en el caso de referencia, todos los botones
      verdes del sitio). Comprobado en el CSS generado y con el ratón real. (M9a, M9b)
- [ ] Contraste AA también **en hover**. (M9b)
- [ ] Foco visible en cada control al tabular, con contraste ≥ 3:1: la regla global va con
      `html :is(a, button, input, select, textarea, summary, [tabindex]:not([tabindex="-1"])):focus-visible`
      (0,3,1) para ganar al reset de Hello (0,3,0). (M9b)
- [ ] Ningún `<button>` propio muestra el magenta `#c36` del reset de Hello en hover o foco. (M9b)
- [ ] Las sombras decorativas se mantienen al hacer clic: el reset de Hello quita `box-shadow` con
      una regla `:focus:not(:focus-visible)` sobre `[tabindex]` (0,4,0 en el caso de referencia), así
      que el child repite la sombra también en `[tabindex]:focus`.

## G · Contraste AA (medido)

- [ ] Texto sobre fondo liso: ratio ≥4,5:1 (≥3:1 si ≥24 px o ≥18,66 px en negrita). (M10a)
- [ ] Texto sobre foto o vídeo: peor caso por fotograma, con el velo compuesto. (M10b)
- [ ] Pasada de Lighthouse de accesibilidad con `--force-prefers-reduced-motion`: axe no audita
      texto a opacidad 0 y deja fuera todo lo que entra animado. (M10c)
- [ ] Lo que no pasa y viene de la paleta del prototipo **no se cambia por iniciativa propia**: se
      reporta con el ratio medido y un valor propuesto, marcado «heredado del prototipo», para que
      decida el cliente.

## H · Accesibilidad

- [ ] Exactamente un `<main id="content">` (Hello + «Elementor ancho completo» no lo tienen: lo
      abre el child; en plantillas 404/single con Diseño «Elementor ancho completo» salen dos). El
      enlace «Saltar al contenido» existe, está traducido y lleva a `#content`. (M13, M16)
- [ ] Un solo `h1` y sin saltos de nivel (títulos de columna del pie en `h2`, no `h4` tras un `h2`).
      (M13)
- [ ] `alt` descriptivo en las imágenes que informan, vacío en las decorativas; SVG decorativos con
      `aria-hidden="true"`. (M13)
- [ ] Enlaces y botones con nombre accesible; sin interactivos anidados; sin ids duplicados que
      importen. (M13)
- [ ] Cada `<nav>` con `aria-label` único y en el idioma de la página (sale del `menu_name` del
      widget nav-menu); la hamburguesa no se anuncia «Menu Toggle». (M13)
- [ ] Con el panel móvil del menú cerrado, Tab no entra en él (`aria-hidden-focus`). (M9b)
- [ ] Formularios con `autocomplete` en nombre, email, empresa… (WCAG 1.3.5) y nombre accesible
      del `<form>` en el idioma de la página. (M13, M14)
- [ ] `prefers-reduced-motion`: todo visible, sin animaciones infinitas, vídeo de fondo detenido o
      con su póster, y botón de pausa operable con teclado.

## I · Segundo idioma equivalente

- [ ] Cero restos del idioma base en texto, `alt`, `aria-label`, `title`, `placeholder`, `data-*`
      y metas. (M14)
- [ ] Cabecera, pie, ficha y 404 del idioma 2 con **sus** IDs de plantilla; `<html lang>`,
      `hreflang` (los dos + `x-default`) y portada en `/<IDIOMA_2>/`, no en `/<IDIOMA_2>/home/`. (M14)
- [ ] Enlaces internos y menús del idioma 2 apuntan a `/<IDIOMA_2>/…` (salvo el selector de
      idioma). Los enlaces absolutos guardados en `_elementor_data` son el sospechoso habitual. (M14)
- [ ] Geometría del idioma 2 contra el prototipo en ese idioma, con el idioma del prototipo fijado
      y devuelto a su valor al terminar. (M3, M5)
- [ ] Interacción real: selector de idioma entre las dos versiones de la misma página, filtros AJAX
      que devuelven contenido del idioma de la página, formularios con mensajes en ese idioma.
- [ ] Los arreglos del idioma 2 se hacen en el **origen** (idioma base o datos de traducción) y se
      regeneran; un arreglo hecho a mano en el documento del idioma 2 se pierde.

## J · Formularios (en seco)

- [ ] Matriz en memoria sin enviar nada: válido, honeypot lleno, envío en menos del tiempo mínimo,
      sin JS, sin email, email sin TLD. Solo pasa el válido. (M15)
- [ ] Correo simulado: destinatarios correctos, asunto, `Reply-To` = email del visitante, remitente
      del dominio del sitio (From y Sender alineados). En el idioma 2, el correo interno sigue en el
      idioma del equipo con la marca del idioma. (M15)
- [ ] «Igual que el prototipo» se comprueba contra la versión **desplegada** del prototipo, no solo
      la copia local: destinatarios, asunto, cuerpo, remitente y `Reply-To`.
- [ ] Nunca un envío real. La prueba real de extremo a extremo es del usuario, a su buzón, con su
      permiso.

## K · 404, login y mantenimiento (curl)

- [ ] `/no-existe-xq/` y `/<IDIOMA_2>/does-not-exist-xq/` → HTTP 404, `data-elementor-type="error-404"`
      con el ID de su idioma, un solo `<main`, `lang` y título traducidos. (M16)
- [ ] Condición del 404 solo en la plantilla del idioma base:
      `wp option get elementor_pro_theme_builder_conditions --format=json` contiene solo ese ID
      (`"single":{"<ID_404_BASE>":["include/singular/not_found404"]}`).
- [ ] Login propio: el slug da 302 con la cookie `sgs-token-login-*`; `wp-login.php` y `wp-admin/`
      sin sesión acaban en `/404`; `/wp-json/`, `admin-ajax.php` y `wp-cron.php` no cambian;
      `Referrer-Policy` compatible. Nada público enlaza a `wp_login_url()`. (M16)
- [ ] Pantalla de login de marca a 375, 768 y 1440, en los dos idiomas, sin cadenas del núcleo en
      inglés en el idioma base y sin scroll horizontal.
- [ ] Cortina: en un dominio con historial, **503 + `Retry-After`** (`maintenance`), nunca
      `coming_soon` (200 + noindex en todo, `robots.txt` incluido). Vista previa de la plantilla sin
      encender el modo: canvas, `lang`, `noindex, nofollow`, sin cabecera. (M16)

## L · Consola, PHP y rendimiento básico

- [ ] Consola sin errores ni peticiones fallidas en todas las páginas tocadas, a 1440 y 375. (M5)
- [ ] Sin avisos PHP del child al pintar la página; ningún fatal escondido tras la página de «error
      crítico». (M18)
- [ ] Lighthouse local de la misma versión que PSI, mediana de 3, corridas con red limpia: sin
      regresiones frente a la última medición; CLS ≈ 0; un solo `fetchpriority="high"`. (M19)

## M · Cierre

- [ ] Informe ordenado por gravedad: qué falla, medición (esperado contra real), causa, arreglo
      acotado. Separado: lo que rompió el cambio auditado, lo que ya estaba roto y lo heredado del
      prototipo. Categorías sin hallazgos, dichas explícitamente; lo no medible, **no verificado**.
- [ ] Pendientes del usuario explícitos: abrir en el editor y publicar; prueba real de formularios.
- [ ] Nada desplegado ni encendido por el QA. Los temporales de `~/tmp-<PREFIJO>/` listados para
      que los limpie el usuario.
