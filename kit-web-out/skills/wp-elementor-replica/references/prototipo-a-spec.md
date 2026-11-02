# Del prototipo a la especificación

Antes de construir, el prototipo se **audita** (qué se replica y qué no) y se **mide** (una
spec por página y sección con valores computados). Se construye contra esas cifras, nunca
contra el código fuente del prototipo.

*Por qué:* en el caso de referencia el prototipo (Next.js 16 + Tailwind v4) tenía más de 60
usos de `font-600/700/800`, clases que no existen en Tailwind v4: el navegador pintaba peso
400. La queja del usuario («la tipografía de la cabecera se ve débil») venía de ahí. Leyendo
el código se habrían replicado pesos que el cliente nunca vio ni aprobó. Y una clase JIT que
no está en ningún archivo, probada en la consola, también devuelve 400.

## 1. Preparación

- **Si el prototipo aún no existe o está flojo** (huecos, datos o fotos inventados, diseño
  tímido), no lo repliques todavía: usa antes la skill `metodo-diseno-web` si está
  instalada. Es el método para diseñar el prototipo HTML/CSS/JS con una página de
  referencia primero, información y fotos reales, verificación visual en bucle y crítica
  adversarial. Esta skill empieza cuando hay un diseño aprobado.
- **Prototipo versionado** (`git init` y commit si falta: en el caso, el repo solo tenía el
  commit inicial y nada se podía deshacer) y **servido por un único servidor de desarrollo**
  (`.claude/launch.json` + `preview_start`). En Next.js dentro de una carpeta con varios
  proyectos, `turbopack: { root: path.join(__dirname) }`.
- En un prototipo Next.js reciente, la documentación de la versión instalada vive en
  `node_modules/next/dist/docs`: no des por sabida la API.
- Las rondas de feedback del cliente se cierran **en el prototipo**, no en WordPress: la
  réplica arranca con el diseño aprobado.

## 2. Auditoría del prototipo

Objetivo: separar **decisión de diseño aprobada** (se replica aunque no te guste) de
**defecto** (se corrige en la réplica y se dice).

**Cómo** (en paralelo con el resto de F0):
- Un workflow con un agente por dimensión: sistema visual, portada, páginas internas,
  componentes, copy e idiomas, accesibilidad, responsive y rendimiento, y fidelidad al
  brief. Un verificador adversarial por hallazgo intenta refutarlo contra el código. En el
  caso: 134 propuestos → 93 confirmados.
- **Y medición propia en el navegador**: la revisión de código no ve fallos de ejecución.
  La auditoría dio por bueno un componente de entradas animadas que, con scroll rápido,
  dejaba 4 de 13 bloques en `opacity:0` para siempre (entre ellos el catálogo completo), y
  que sin JS dejaba la página en blanco.
- **Verifica todo lo que vaya a usarse para decidir.** En el caso solo se verificaron los 12
  primeros hallazgos de cada dimensión (38 quedaron sin verificar), y la «nota operativa»
  que añadió el agente sintetizador era una suposición falsa.

**Qué buscar** (lo que apareció en el caso):
- Enlaces y botones que no llevan a ninguna parte, tarjetas que apuntan todas a la misma URL.
- Entradas animadas que no revelan (scroll rápido, sin JS, `prefers-reduced-motion`).
- Contrastes: calcula la matriz texto/fondo de la paleta (en el caso, el naranja de marca
  daba 2,86:1 sobre blanco, y el blanco sobre el verde 4,63:1, justo el mínimo, así que
  cualquier opacidad lo tumbaba).
- Claims que contradicen la etiqueta del producto, cifras que cambian entre páginas,
  contactos, teléfonos o dominios inventados, Lorem Ipsum.
- Metadatos idénticos en todas las páginas.
- Pesos o itálicas que la fuente no tiene (la display del caso solo tenía peso 400 y no
  tenía itálica: los acentos van por color, nunca con negrita sintética).
- Imágenes generadas por IA con texto ilegible en envases o rótulos; packshots con márgenes
  transparentes distintos (recórtalos a su contenido y dimensiona la fila por altura).
- Material del cliente que el formato esconde: adjuntos de un `.docx` que el `.md`
  exportado pierde (`unzip -l … | grep word/media`), pedidos dibujados en capturas.

**Informe** (formato del caso, guardado en la raíz del proyecto y enviado al usuario):
cabecera **«Procedencia»** (cómo se hizo, cuántos hallazgos propuestos, confirmados y sin
verificar), **correcciones medidas** por ti sobre lo que dicen los agentes, veredicto, «lo
que está bien (conservar)», problemas por prioridad (**bloquea el lanzamiento /
importante antes de presentar al cliente / pulido**), cada uno con archivo:línea y su
prueba; «lo que el cliente pidió y aún no está» y los siguientes pasos.

Lo que la auditoría marque como decisión del cliente (claims, contrastes de marca) no lo
resuelves tú: se replica y se lista (`decisiones.md` §3.3).

## 3. Inventario de interacciones y su equivalente en Elementor

Cada interacción del prototipo necesita un equivalente editable. El prototipo del caso se
diseñó ya pensando en Elementor y su tabla apuntaba a Motion Effects y Loop Carousel; en la
construcción real, entradas y carruseles se hicieron en el hijo para que fueran robustos y
no dependieran de widgets V3. Así quedó:

| En el prototipo | En WordPress + Elementor (lo construido) |
|---|---|
| Aparición al hacer scroll | entradas animadas del hijo por `data-<PREFIJO>-reveal`, robustas (se ven sin JS y en el editor) |
| Hover de elevación o zoom | clases globales con variante de estado `hover` o CSS del hijo |
| Carruseles con flechas | carril `data-<PREFIJO>="rail"` con `scroll-snap` y flechas en el JS del hijo |
| Esqueleto de carga + entrada escalonada | Loop Grid con filtro AJAX + esqueleto del hijo |
| Datos en un archivo del prototipo (recetas, productos) | CPT con su ficha individual |
| Captcha simulado | honeypot + tiempo mínimo (sin terceros) |
| Mapa simulado | página informativa con SVG del hijo, sin API de pago hasta que se apruebe |
| Menú que colapsa en `xl` | `nav-menu` de Pro con `dropdown = tablet_extra` (1279) |

Lo que el prototipo hace con JS y V4 no expresa (pseudoelementos, hovers entre elementos,
máscaras de iconos, `scroll-snap`, `writing-mode`) va al hijo, enganchado por atributos
`data-<PREFIJO>-*` que el cliente no necesita tocar.

## 4. Extraer datos, no transcribir

- Contenido estructurado: impórtalo del código del prototipo en vez de copiarlo a mano. En
  el caso, `node dump.mts` (Node 24 ejecuta TypeScript) importó el archivo de contenido y
  volcó los datos de los dos idiomas a JSON, que el importador PHP leyó.
- Categorías, niveles y filtros como **clave estable** (`desayunos`, `facil`), no como
  texto visible, para que el filtro funcione en los dos idiomas.
- SVG del prototipo: sácalos del DOM renderizado (`svg.cloneNode(true)`, sin `class`, con
  tamaño fijo, `outerHTML`).
- Textos del segundo idioma: **literales del prototipo** (`fuente: prototipo`); lo que el
  prototipo no tiene se marca `fuente: propuesta` y va a la lista de dudas del cliente.
- Medios: compara dimensiones y `md5` de lo subido a WordPress con lo del prototipo (en el
  caso, el logo de la Biblioteca era otra versión); optimiza en local y sube con un
  manifiesto que produce un JSON `clave → ID`.

## 5. La spec por página

**Dónde:** `<pagina>/spec-prototipo.md` junto a los scripts de esa página. Plantilla:
[`../plantillas/spec-seccion.md`](../plantillas/spec-seccion.md).

**Cómo se mide:** prototipo en el navegador en los anchos de `verificacion.md` §2 (**1440**,
**1280**, **768** y **375**; cabecera y pie también a 1100 y 900; 1279, 1024 y 767 si la
spec cambia en ese borde), con las entradas animadas ya disparadas y el idioma fijado. La
spec tiene que cubrir los mismos anchos en los que después se mide WordPress. Con el mismo script que luego medirá WordPress
(`verificacion.md` §2): `getBoundingClientRect` + `scrollY` para cajas y
`getComputedStyle` para tipografía, color, radio, sombra y transición.

**Qué se anota, por sección y por ancho:**
- Posición (top) y alto de la sección; alto total del documento.
- Rejilla: fracciones y anchos resultantes, `gap`, `padding`, alineación.
- Tipografía computada de cada nivel: familia, tamaño/interlineado, peso, tracking,
  mayúsculas, color (por token).
- Espaciados entre bloques (`margin-top` efectivos), anchos máximos.
- Radios, bordes, sombras, fondos (por token), proporciones de imagen.
- Estados: hover, foco, deshabilitado, mensajes; transiciones.
- Formularios: alto de campos, padding, radio, placeholder, anillo de foco.
- Anclas de sección y su `scroll-margin`.
- Anomalías de la medición (p. ej. «columnas sin revelar: +28 px en su top»).

**Formato:** prosa telegráfica por ancho, con cifras y tokens. Extracto real del caso
(página de contacto, 1440):

```
- Rejilla: 0.85fr/1.15fr = 503.2/680.8, gap 56, padding 96/40, align stretch.
- Label 12/16 bold upper ls .6px tinta-suave, mb 6. Input 46 alto: padding 12/16, 14/20,
  radio 12, borde línea, fondo blanco, placeholder #6E7664; focus: borde naranja + ring 2px
  naranja 15%. Textarea filas 5 → 126.
- Botón mt28, padding 14/32, 14/20 bold, verde, radio full; hover verde profundo.
## 375
- Rejilla gap 40, padding 64/20; foto 4/3 (335x251.3); form padding 24; filas en una columna.
```

## 6. De la spec al sistema V4

- **Tokens → variables V4** (etiquetas en kebab-case) y los mismos colores y fuentes como
  globales del kit.
- **Escalas → clases globales** con una sola familia por propiedad: la tipografía no lleva
  margen ni color (van en `<PREFIJO>-tono-*` y `<PREFIJO>-mt-*`), los contenedores fijan
  `padding` y `width`, y los modificadores van antes que su base en el orden. Los tamaños
  fluidos del prototipo (`clamp()`) se conservan como tamaño `custom` (en el caso, el H1
  dio 75,2 px a 1440, igual que el prototipo).
- **Defaults implícitos de Tailwind** que hay que replicar a mano: interlineado 1,5 del
  cuerpo, `h1–h4` a 1,15 y `space-y-2.5` = 10 px, entre otros. Casi todas las diferencias
  de pocos píxeles del caso salieron de aquí o de los estilos base atómicos.
- **Breakpoints:** los de Elementor (móvil 767, tablet 1024, `tablet_extra` 1279) no son
  los de Tailwind (640/768/1024/1280). A exactamente 1024 px, el tablet de Elementor y el
  `lg` de Tailwind difieren: anótalo en «conocido y aceptado».
- **Acento de color dentro de un título** = `<strong>` con clase de acento: el cliente lo
  edita poniendo negrita.
- Técnica: [modelo-de-datos.md](../../elementor4-atomico/references/modelo-de-datos.md).

## 7. Desde el primer día: «conocido y aceptado»

Abre una lista de diferencias deliberadas con el prototipo y mantenla viva: breakpoints de
Elementor, defectos del prototipo que no se copian, añadidos pedidos o declarados, semántica
que V4 no permite (sin `dl`, `ul` ni `ol` en los atómicos). Se la pasas a cada revisión
adversarial para que no se reporte, y se la cuentas al usuario en los informes.

## 8. Si el origen no es Next.js

- **HTML estático:** igual. Sírvelo en local con un único servidor y mide con el mismo
  script.
- **Figma:** la spec sale de las medidas del archivo de diseño por frame y ancho. Carga
  antes la skill de Figma que corresponda (`figma:figma-design-to-code` si vas a leer el
  contexto de diseño). Sin navegador que medir, la verificación se hace contra las medidas
  anotadas y, si existen, contra frames a 1440 y 375. Este camino **no se probó** en el caso
  de referencia.
