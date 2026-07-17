# Centro de Partidos — Especificación pixel-exact

> **Objetivo:** que el desarrollador implemente el componente SIN interpretar. Todas
> las medidas están tomadas del mockup de Figma a resolución completa
> (`Home - Diario Deportes.png`, 5760×16384 px) y convertidas al sitio con el factor
> de escala derivado del requisito del cliente.

---

## 0. Factor de escala (cómo se derivó)

- El componente (banner y publicidad) en el mockup mide **1695 px de alto** (medido:
  banda de contenido y = 108→1803 en el recorte, idéntica para ambas columnas).
- El cliente exige que en el sitio real la altura sea **424 px**.
- **Factor de escala = 1695 / 424 = 3.998 ≈ 4.0.**
- **Regla:** toda medida del mockup se divide entre **4.0** para obtener el px del sitio.

> El ancho nativo del componente al factor 4.0 es **1360 px** (banner 992 + gap 26 +
> ad 342), ligeramente mayor que el contenedor estándar de 1280 px. Como la altura
> 424 es requisito duro del cliente, se **fija la altura en 424 px** y las dos
> columnas se ajustan al contenedor de 1280 px conservando la proporción del mockup
> (ver §1). Los tamaños internos (escudos, tipografía, dots, chip) NO se recalculan:
> se usan siempre los valores de la tabla (÷4.0).

---

## 1. Tabla de medidas convertidas (mockup → sitio, ÷4.0)

### Layout general

| Elemento | Mockup (px) | Sitio (px) | Notas de implementación |
|---|---|---|---|
| **Altura del componente** (banner y ad) | 1695 | **424** | Requisito duro. Ambas columnas idéntico alto. |
| Ancho banner (nativo) | 3967 | 992 | Ratio banner:ad = **2.90 : 1** |
| Ancho publicidad (nativo) | 1367 | 342 | |
| Gap entre columnas (nativo) | 105 | 26 | |
| **Ancho banner (ajustado a contenedor 1280)** | — | **934** | `grid-template-columns: 2.9fr 1fr` |
| **Ancho publicidad (ajustado a 1280)** | — | **322** | |
| **Gap (ajustado a 1280)** | — | **24** | |
| Border-radius (ambas cajas) | ~24 | **8** | medido ~6px; usar 8 (token `--radius-sm`) |
| Margen de página (mockup) | 160 | 40 | coincide con `--page-margin` máx (48) |

**Recomendación de grid (implementación exacta):**
```css
.match-center{
  display:grid;
  grid-template-columns: 2.9fr 1fr;   /* banner : ad = 2.90 : 1 */
  gap: 24px;
  align-items: stretch;
}
.match-slider__viewport,
.match-ad{ height: 424px; }            /* ALTURAS IGUALES — requisito */
```

### Banner — slide 1 (Junior vs Nacional)

| Elemento | Mockup (px) | Sitio (px) | Detalle |
|---|---|---|---|
| **Kicker "Final Liga Betplay"** | cap 35 | font **13** | peso 500, color `#FFFFFF`, **sentence case** (`text-transform:none`), **sin tracking** (`letter-spacing:0`) |
| Kicker — posición | left 84 / top 88 | **left 21 / top 22** | anclado arriba-izquierda (`position:absolute`) |
| **Escudo (cada uno)** | alto 719 | **alto 180**, ancho auto | igualados por ALTO, no por caja cuadrada; conservar aspecto natural |
| Escudo Junior — ancho render | 635 | ~159 | (aspecto 0.88) |
| Escudo Nacional — ancho render | 499 | ~125 | (aspecto 0.70) |
| **"vs"** | x-height 34 | font **16** | **minúscula**, peso 500, `#FFFFFF` |
| Separación escudo↔vs↔escudo | inner-gap 76 | **gap 30 px por lado** | flex `gap:30px`; centros de escudo a 109 px (referencia) |
| Bloque de escudos — centro vertical | 722 desde arriba | **180 desde arriba** | |
| **Fila info (fecha · estadio)** | cap 45 | font **16** | peso **700**, `#FFFFFF` |
| Fila info — centro vertical | 1192 desde arriba | **298 desde arriba** | ≈ 20 px bajo los escudos |
| Icono calendario / pin | ~72 | **18** | `#FFFFFF`, stroke 1.75, gap icono-texto 8 px |
| Separación entre los 2 items | 145 | **36** | **sin punto "·" separador** (ver §2) |
| **Logo WIN Sports** | 191×95 | **alto 24**, ancho auto (~48) | imagen `Win_Sports_nuevo_logo.svg.webp`, centrado |
| Logo WIN — centro vertical | 1359 desde arriba | **340 desde arriba** | ≈ 22 px bajo la fila info. **SIN rótulo "Dónde ver"** (§2) |
| **Dot activo** | 91×15 | **22×5**, pill | `#FFFFFF` |
| **Dot inactivo** | 35×15 | **8×5**, pill | `#4B4B4B` (rgb 75,75,75) |
| Dots — gap | 29 | **7** | |
| Dots — posición | 88 desde abajo | **bottom 22**, centrado | `position:absolute` |

### Publicidad (panel derecho)

| Elemento | Mockup (px) | Sitio (px) | Detalle |
|---|---|---|---|
| Panel | 1367×1695 | **322×424** | aspecto 0.807 |
| Imagen `publicidad.png` | 1126×1397 | — | aspecto 0.806 → casi idéntico al panel |
| Ajuste imagen | — | — | `object-fit:cover; object-position:center` (recorte mínimo). **Sin overlay, sin filtro, sin tinte** (§2, §3) |
| **Chip "Publicidad"** | 90×24 | — | **DENTRO** de la imagen, arriba-izquierda, a ras de la esquina |
| Chip texto | cap 34 | font **12** | peso 500, `#FFFFFF`, sentence case |
| Chip padding | v30 / h70 | **8 / 16** | vertical 8, horizontal 16 |
| Chip fondo | — | `rgba(41,77,110,0.72)` | navy de marca translúcido (§3) |
| Chip radio | ~40 | `8px 0 10px 0` | esquina sup-izq = radio panel (8), inf-der redondeada (10) |

---

## 2. Los 4 errores del cliente y su corrección explícita

**Error 1 — Alturas distintas.**
Banner y panel Publicidad DEBEN medir lo mismo: **424 px** en desktop.
→ Corrección: `.match-slider__viewport` y `.match-ad` con `height:424px`. Prohibido usar
`min-height` distintos (la implementación tenía banner 400 / ad 320). No usar `aspect-ratio`
que produzca alturas divergentes.

**Error 2 — Rótulo "DÓNDE VER" inexistente.**
En el mockup NO existe ningún texto "Dónde ver": bajo la fila fecha·estadio aparece
**únicamente el logo de WIN Sports** (slide 1).
→ Corrección: eliminar el `<span class="match-banner__tv-label">Dónde ver</span>`. Dejar
solo `<img>` del logo WIN, alto 24 px, centrado.

**Error 3 — Kicker en mayúsculas con tracking.**
"Final Liga Betplay" va **en sentence case, tal cual está escrito**, sin mayúsculas y sin
tracking de kicker.
→ Corrección en `.match-banner__kicker`: `text-transform:none; letter-spacing:0;`
`font-weight:500;` y el texto literal `Final Liga Betplay`. Quitar `text-transform:uppercase`
y `letter-spacing:var(--ls-kicker)`.

**Error 4 — Banner y publicidad "lavados" (tinte azulado).**
En el mockup el banner es **oscuro y saturado** (foto de banderas a plena saturación bajo un
oscurecimiento negro) y la publicidad se ve **a pleno color**.
→ Corrección:
- Banner: reemplazar el overlay actual (que se vuelve transparente en el centro y deja la
  imagen clara/lavada) por el **gradiente negro de arriba-abajo** especificado en §3. Sin
  degradados de color azul. Sin `filter`.
- Publicidad: **prohibido** cualquier overlay, `filter`, `mix-blend-mode` o capa de color sobre
  `publicidad.png`. Solo `object-fit:cover`. La imagen se muestra a color pleno.
- Además, quitar el punto separador "·" entre fecha y estadio (no existe en el mockup): son dos
  items separados por 36 px de espacio, sin glifo intermedio.

---

## 3. Fondo y overlays — valores rgba exactos

### 3.1 Banner slide 1 (Junior vs Nacional) — foto real oscurecida

La imagen `junior_nacional.png` (1254×1254) se coloca a sangre con `background-size:cover;
background-position:center`. Encima va **un único overlay negro con gradiente vertical**,
medido comparando el mockup contra la imagen fuente (alfa de negro por altura):

| Altura | α negro medido |
|---|---|
| 0% (arriba) | 0.39 |
| 30% | 0.54 |
| 48% (centro) | 0.67 |
| 74% | 0.84 |
| 100% (abajo) | 0.95 |

**Overlay a implementar (una sola capa, sin color, sin tinte azul):**
```css
.match-banner--junnac{
  background:
    linear-gradient(to bottom,
      rgba(0,0,0,0.40) 0%,
      rgba(0,0,0,0.55) 30%,
      rgba(0,0,0,0.68) 50%,
      rgba(0,0,0,0.82) 72%,
      rgba(0,0,0,0.95) 100%),
    url('../img/junior_nacional.png') center / cover no-repeat;
}
```
- El overlay **NUNCA baja de 0.40** (por eso el centro no se lava) y se oscurece hacia abajo
  para dar legibilidad al bloque fecha/estadio/WIN/dots.
- Es negro puro → oscurece preservando el tono (banderas rojas/verdes quedan saturadas). **No**
  usar rgba azules ni `radial-gradient` de color sobre la foto.
- **No** aplicar `filter`, `brightness`, `mix-blend-mode` ni `backdrop-filter` a la imagen.

> Ya NO se necesita el `<span class="match-banner__overlay">` separado con gradiente de tres
> tramos: el oscurecimiento va en el `background` del propio banner. Si se conserva el span,
> debe reproducir exactamente el gradiente de arriba.

### 3.2 Publicidad — a pleno color, SIN overlay
```css
.match-ad__img{ width:100%; height:100%; object-fit:cover; object-position:center;
  filter:none; }              /* prohibido cualquier tinte */
```
No hay ninguna capa entre la imagen y el usuario salvo el chip "Publicidad".

### 3.3 Chip "Publicidad" (dentro de la imagen, arriba-izquierda)
```css
.match-ad__label{
  position:absolute; top:0; left:0;
  padding:8px 16px;
  font-size:12px; font-weight:500; color:#FFFFFF;
  text-transform:none;                     /* sentence case: "Publicidad" */
  letter-spacing:0;
  background:rgba(41,77,110,0.72);         /* navy de marca #294D6E translúcido */
  border-radius:8px 0 10px 0;              /* sup-izq = radio panel; inf-der redondeada */
}
```
- Color de fondo: navy de marca `#294D6E` a 72% de opacidad. Sobre el cielo brillante del
  creativo ISA, el color mezclado medido es ≈ `#486B98` (referencia; no fijar ese valor:
  fijar el `rgba(41,77,110,0.72)` para que funcione sobre cualquier creativo).
- Texto blanco `#FFFFFF`, sentence case, exactamente `Publicidad`.
- El chip requiere que `.match-ad__link` sea `position:relative; overflow:hidden`.

---

## 4. Estado responsive — PROPUESTA (no está en el mockup)

El mockup solo define desktop. Propuesta por defecto (aplicar salvo indicación del cliente):

- **< 900 px:** apilar en una columna. Banner arriba, publicidad debajo.
  - Banner: alto fijo **300 px** (conserva `cover` y el mismo overlay).
  - Publicidad: centrada, ancho máx **360 px**, alto auto respetando su aspecto vertical
    (0.807); el chip permanece arriba-izquierda.
  - Los escudos bajan a **alto 140 px**; kicker 12 px; fila info 14 px; logo WIN 20 px.
- **< 480 px:** banner alto **240 px**; escudos alto **112 px**; fila info puede envolver a
  dos líneas manteniendo centrado.
- Los dots y el chip conservan tamaños de la tabla.

---

## PREGUNTAS AL CLIENTE

*(Solo lo que el mockup no resuelve. Si no hay respuesta, se aplica el default indicado.)*

1. **Fondo de los slides 2 y 3 (Colombia vs Perú, Junior vs Millonarios).**
   El mockup solo muestra el slide 1 (foto de banderas oscurecida). ¿Los otros partidos
   tendrán foto real por enfrentamiento?
   **Default:** usar una foto real por partido con el mismo overlay negro de §3.1; si no hay
   foto disponible, fondo oscuro saturado (nunca pálido) con los colores de cada equipo.

2. **Bloque "dónde ver" en slides 2 y 3.**
   El slide 1 muestra solo el logo WIN (sin rótulo). Colombia vs Perú va por Caracol y RCN
   (dos canales). ¿Mostramos los logos de ambos canales?
   **Default:** mostrar el/los logo(s) del canal correspondiente, del mismo alto (24 px), sin
   rótulo "Dónde ver". Si no hay logo, omitir el bloque por completo (no poner texto).

3. **Comportamiento en móvil.**
   No está en el mockup. ¿Se aprueba el apilado propuesto en §4 (banner 300 px arriba,
   publicidad debajo máx 360 px)?
   **Default:** aplicar §4 tal cual.
