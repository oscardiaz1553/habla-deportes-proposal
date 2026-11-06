<!--
PLANTILLA DEL KIT: spec del prototipo por página y sección.
Copia este archivo a <PROYECTO>/wp/scripts/<pagina>/spec-prototipo.md, rellénalo midiendo el
prototipo RENDERIZADO (nunca leyendo sus clases) y borra este bloque y las líneas de ayuda
(las que empiezan por «>»). Método completo: skill wp-elementor-replica,
references/prototipo-a-spec.md §5. Cómo medir: medir-vs-prototipo.js (skill elementor4-atomico,
navegador propio) o qa-medir.mjs (Chrome headless para subagentes; el script vive en
~/.claude/skills/wp-qa/scripts/ y su README explica preparación y uso); en lote:
workflows/extraer-spec.js de estas mismas plantillas.
Por qué: el código del prototipo miente. En el caso de referencia, más de 60 usos de clases de
peso (font-600/700/800) que no existen en Tailwind v4 caían en silencio a 400; replicar el código
habría replicado pesos que el cliente nunca vio.
-->

# <Página> — medidas del prototipo

- **Prototipo:** `<URL local>/<ruta>` · commit `<hash>` · fecha `<AAAA-MM-DD>`
- **WordPress:** `<STAGING_URL>/<ruta>` · documento `#<ID>` · scripts `<pagina>-classes.php`,
  `<pagina>-build.php`
- **Condiciones de la medida:** entradas animadas forzadas (`<selectores>`), idioma fijado
  (`<clave localStorage>=<valor>`), pestaña visible, sin sesión.
- **Cabecera fija:** `<alto>` px a 1440 · `<alto>` px a 375 (los `top` de abajo la incluyen).
- **Ancho útil a 1440:** `<1425>` px si hay barra de scroll (anótalo: cambia las fracciones).

> Notación telegráfica: `14/20` = tamaño/interlineado en px · `padding 96/40` = vertical/
> horizontal (`padding 128/40/64` = arriba/lados/abajo) · `mt28` = margin-top 28 · `ls .6px` =
> letter-spacing · `upper` = mayúsculas · `radio full` = 9999 px · `0.85fr/1.15fr = 503.2/680.8`
> = fracciones y anchos resultantes · colores **por token** (`tinta-suave`), hex solo si no hay
> token · `x`/`y` absolutos con el scroll incluido.

---

## 1440 × 900

### S1 · <nombre de la sección> (`#<ancla>` si la tiene)
- Caja: top `<n>`, alto `<n>`, fondo `<token>`, borde `<…>`, `scroll-margin-top <n>`.
- Contenedor: ancho máx `<n>`, padding `<v>/<h>`; rejilla `<fr/fr> = <px/px>`, gap `<n>`,
  alineación `<…>`.
- Tipografía por nivel (familia · tamaño/interlineado · peso · tracking · caso · color):
  - kicker: `<…>` (¿con línea? ancho de la línea)
  - h1/h2: `<…>`, ancho máx `<n>`, mt `<n>`
  - lead/párrafo: `<…>`, ancho máx `<n>`
- Imágenes: proporción `<4/3>` (`<ancho>x<alto>`), `object-fit`, radio, sombra, alt literal.
- Botones y enlaces: padding, tamaño/peso, fondo/texto por token, radio; **hover** (fondo y
  color), foco, activo, deshabilitado.
- Formularios (si hay): alto de campo, padding, radio, borde, placeholder, anillo de foco,
  filas de textarea, mensajes de éxito/error.
- Estados e interacción: transiciones (propiedad, duración, curva), animaciones de entrada
  (desplazamiento, retardo por ítem), sticky, carruseles.
- Anomalías de la medición: `<p. ej. «columnas sin revelar: +28 px en su top»>`.

### S2 · <…>
- …

**Alto del documento:** `<n>`.

## 375 × 812

> Solo lo que cambia respecto a 1440. Si una rejilla cambia de columnas, anota el orden real.

### S1 · <…>
- …

**Alto del documento:** `<n>`.

## 1280 y 768 (siempre; solo lo que cambia respecto a 1440)

> Anchos de «siempre» de `references/verificacion.md` §2: 1280 es el más estrecho con menú de
> escritorio (con `tablet_extra` = 1279) y 768 el primero por encima de `mobile`.

- `<…>`

## 1100 y 900 (cabecera, pie y bloques que cambian entre tablet y escritorio)

- `<…>`

## 1279, 1024 y 767 (solo si la spec cambia justo en un borde de breakpoint)

- `<…>` · A exactamente 1024 px el tablet de Elementor y el `lg` del prototipo pueden
  diferir: si es así, va a «Conocido y aceptado».

---

## Contenido y enlaces

| Sección | Texto o atributo | Literal del prototipo | Origen (archivo:clave) |
|---|---|---|---|
| S1 | h1 | «<…>» | `<lib/copy.ts:…>` |
| S1 | alt de la imagen | «<…>» | `<…>` |
| S2 | enlace del botón | `<ruta o ancla>` | `<…>` |

Textos del idioma 2: literales del prototipo (`fuente: prototipo`); lo que no esté se marca
`fuente: propuesta` y va a las dudas del cliente.

## Traducción a V4

| Sección | Elementos (`e-flexbox`, `e-grid`, `e-heading`…) | Clases globales existentes | Clases nuevas | Va al tema hijo (`data-<PREFIJO>-*`) |
|---|---|---|---|---|
| S1 | `<…>` | `<PREFIJO>-banda, <PREFIJO>-kicker…` | `<…>` | `<pseudoelementos, hovers entre elementos, animaciones…>` |

> Antes de crear una clase, vuelca las existentes (`e4-dump-clases.php`) y reutiliza. La
> tipografía no lleva margen ni color; los modificadores van antes que su base en el orden.
> Replica los defaults implícitos del prototipo (en Tailwind: interlineado 1,5 del cuerpo,
> `h1–h4` a 1,15) y neutraliza los estilos base atómicos (`padding:10px`, `min-width:30px`,
> `width:100%`).

## Conocido y aceptado (esta página)

- `<diferencia deliberada y por qué>`

## Verificación (se rellena después de construir)

| Ancho | Sección | Prototipo top/alto | WordPress top/alto | Δ | ¿OK? |
|---|---|---|---|---|---|
| 1440 | S1 | `<n>/<n>` | `<n>/<n>` | `<n>` | ≤ 1 px (≤ 3 px solo en anchos intermedios) |
| 1280 | S1 | | | | |
| 768 | S1 | | | | |
| 375 | S1 | | | | |
| — | documento | `<n>` | `<n>` | | sin scroll horizontal |

---

## Ejemplo real (caso de referencia, extracto de Orangeology)

```
## /orangeology @1440x900 (nav 100px)
- Banner: top 100, 1440x576 (clamp(440px,64svh,660px)).
- #recetas: top 676, border-bottom 1px línea, scroll-margin-top 172. Caja 1320 centrada, padding 80/40.
- Cabecera: flex row, align-items end, space-between, gap 12. Kicker verde (línea 24px) 11.52/17.28 bold ls 2.07 upper.
- Filtros: mt 32, flex wrap gap 8. Píldora: 26 alto, padding 4/12, borde 1px, radio full, 12/16 semibold.
  Activa: fondo/borde tinta, texto blanco. Inactiva: fondo blanco, borde línea, texto tinta-suave.
- Rejilla: mt 32, min-height 420, gap 24, 4 cols de 292 (≥1280), 3 (≥1024), 2 (≥640), 1.
- Tarjeta 292x316: borde línea, radio 16, fondo blanco, transition all .3s cubic-bezier(.4,0,.2,1);
  hover: translateY(-4px), borde tinta, shadow-xl. Imagen 4:3 (290x217.5) cover, hover scale 1.05 (.5s).
- Carga del filtro: esqueletos 420ms, luego entrada escalonada .5s, delay i*45ms (translateY 14px scale .985).
## /orangeology @375x812 (nav 88)
- Caja padding 64/20. Cabecera en columna. Filtros en dos filas. Rejilla 1 columna 335; tarjetas 335x348.5.
```
