# 08 · Menú y megamenús: diseño

> Dirección de arte · 7-nov-2026. Responde a `07-brief-menu.md`, que manda sobre 01–04 en todo lo que toca la navegación.
>
> **Reparto con `09-menu-arquitectura.md`:** 09 decide la estructura, las etiquetas, el orden, el contenido de cada panel y los destinos. Este documento decide **cómo se ve y cómo se mueve**: medidas, retícula, tipografía, imagen, estados, tiempos y menú móvil. Donde los dos tocan lo mismo, uso los valores de 09. Las pocas diferencias están en la sección 8.
>
> Tokens: `tokens.css` tal cual, más los nuevos de `08-menu-tokens.css`, que se carga después y no lo modifica. Aquí no se escribe HTML. Los textos y datos son de ejemplo (`data-ejemplo.json`; viernes 6-nov-2026, Junior–Nacional en juego, 67').

**Idea en una frase:** *el menú es la vitrina del programa.* Cada panel muestra siempre lo mismo, en el mismo orden de izquierda a derecha:
1. **Qué hay dentro**: los enlaces.
2. **Lo mejor de hoy**: una sola imagen.
3. **Lo que pasa ahora**: el partido, la escaleta, la tabla o los Shorts.

Se aprende una vez y sirve para los siete paneles.

---

## 1. Crítica del menú actual (5 puntos)

Revisé las capturas `actual-*.png` a 1440 y 390 px, `sitio.py`, `plantilla.py` y `components.css` (líneas 70–470).

1. **Tres capas compiten por el primer lugar.** La cabecera mide 64 + 48 + 48 = **160 px** y tiene tres voces tipográficas: Inter 600 en blanco, Archivo cursiva en negro e Inter 500 en gris. La barra blanca, con texto oscuro sobre blanco, es la que más contraste tiene y le quita la atención a la barra azul, que es la principal. En `actual-junior.png` hay **dos estados activos a la vez** («Junior FC» con barra blanca y «Resumen» con barra azul), así que el ojo no sabe cuál de los dos dice «estás aquí».
2. **El menú no vende el contenido.** Seis de los siete ítems azules son enlaces sin panel. El único panel que se ve, «Torneos» (`actual-datos-panel.png`), está escondido en el segundo nivel y es una lista de cinco textos. No hay imagen, ni marcador, ni aviso de en vivo. Es un medio que vive del video y en el menú no aparece ni un video.
3. **Las etiquetas se pisan.** «Noticias» aparece en la barra azul, otra vez como título de la barra blanca, y a su lado «Todas». «Junior» (tema) y «Junior FC» (sección) llevan a páginas distintas. «Mercado» tiene tres destinos. «Hoy», «Datos» y «Estadísticas» se solapan. «En vivo» nombra a la vez el programa y el partido. 09 lo detalla en H1–H7.
4. **Las señales de interacción no son fiables.**
   - El título en cursiva parece un rótulo, pero es un enlace sin ninguna pista de que se puede pulsar.
   - El chevron solo aparece en «Más» y en «Torneos».
   - El punto rojo de «Partido en vivo» se ve siempre, haya o no partido, y el rojo pierde su valor de alarma.
   - El panel lleva borde y sombra a la vez (parece una «tarjeta fantasma») y queda separado del ítem que lo abre, sin nada que diga de dónde sale.
5. **En móvil no hay menú.** La barra contextual corta la palabra a la mitad («Fút…») y la hamburguesa no abrió nada en la prueba. La pestaña «Noticias» de la barra inferior lleva a la portada, así que la etiqueta engaña. Cabecera, barra blanca y barra inferior suman 168 de los 844 px de la pantalla (20 %), y aun así no se puede ver todo lo que tiene el sitio.

---

## 2. Estructura de la cabecera

### 2.1 Decisión: de tres capas a dos. La barra blanca desaparece (igual que en 09, V1)

| | Antes | Ahora |
|---|---|---|
| Escritorio | Logo 64 + menú 48 + barra blanca 48 = **160 px** | Logo 64 + menú 48 = **112 px** |
| Escritorio, al bajar | 96 px pegados (menú + barra blanca) | **48 px**: solo el menú, con el logo de 24 px |
| Móvil | 104 px pegados | **56 px** pegados |

**Qué la reemplaza:** cada función de la barra blanca pasa a un sitio, sin una tercera capa.
- **Para descubrir lo que hay en cada sección:** el megamenú.
- **Para saber dónde estás:** el ítem activo de la barra azul, con peso 700 y una barra blanca de 3 px. Es la única marca de activo de la cabecera.
- **Para moverse dentro de la sección:** las fichas locales dentro de la página (09, §2.4).
  - Van bajo el H1, ocupan 48 px y llevan Inter 600 de 15 px con un filete inferior de 1 px.
  - La activa va en tinta, con una barra de 3 px `--brand-600` y `aria-current`.
  - No se quedan pegadas. En móvil se deslizan de lado y el último ítem se ve cortado a propósito (máscara de 24 px).
- **En las páginas de detalle:** una miga de pan de una línea sobre el titular, en Inter 500 de 14 px, `--color-text-muted`, con la forma «Noticias / Junior».

### 2.2 Variantes que consideré (lado a lado)

| | **A · Dos barras azules (la que recomiendo)** | B · Una sola barra de 72 px |
|---|---|---|
| Composición | Nivel 1 `--brand-600`: logo, buscador y redes. Nivel 2 `--brand-700`: secciones | Logo, secciones y buscador en la misma barra (BBC, ESPN) |
| Alto | 112 px, que bajan a 48 al hacer scroll | 72 px fijos |
| A favor | Cumple el «topbar y navbar azules» del dueño. El logo respira y el buscador se lee como un campo | Es la opción más compacta |
| En contra | 40 px más | A 1024 px no caben logo, 7 ítems, buscador y redes sin apretarse. Además se pierde el doble azul que el dueño pidió como personalidad |

### 2.3 Nivel 1 · 64 px · `--brand-600`

```
┌───────────────────────────────────────────────────────────────────────────────────────────┐
│ [Habla logo blanco 36 px]                [⌕  Buscar equipo, jugador o tema ]  [▶] [◎] [X] │ 64
└───────────────────────────────────────────────────────────────────────────────────────────┘
  logo → portada                          campo-botón de 280×40                iconos de 44×44
```
- **Buscador con forma de campo** (`--search-trigger-*`). Es un `button` que abre el buscador que ya existe.
  - Lupa de 20 px y el texto «Buscar equipo, jugador o tema» en Inter 500 de 15 px, blanco al 88 % (5,36:1).
  - Borde de 1 px en blanco al 48 % (3,37:1) y radio de 2 px.
  - Una lupa sola no dice qué se puede buscar; el campo sí.
- Sin fecha, sin clima y sin botón de radio (decisión 10 del 00).

### 2.4 Nivel 2 · 48 px · `--brand-700`

```
┌───────────────────────────────────────────────────────────────────────────────────────────┐
│ Videos   Noticias   Junior FC   Selección   ●Datos   ●En vivo   Más ⌄                     │ 48
└───────────────────────────────────────────────────────────────────────────────────────────┘
  Inter 600 de 15 px, blanco, 16 px de relleno por lado. Unos 650 px en total: cabe a 1024 px.
```
- **Orden y etiquetas de 09:** Videos · Noticias · Junior FC · Selección · Datos · En vivo · Más.
- **Puntos rojos:**
  - En **En vivo**, solo cuando el programa está al aire.
  - En **Datos**, solo con un partido de Junior o de la Selección en juego.
  - Como mucho, dos a la vez (09, §6.1).
- **Sin chevron en los seis ítems de sección.** Todos abren un panel y todos son enlaces; seis flechas iguales solo añaden ruido.
- **«Más» sí lleva chevron**, porque no es una página.

### 2.5 Al hacer scroll
- **Escritorio:**
  - El nivel 1 se va con la página (`top: -64px`, ya implementado).
  - El nivel 2 queda pegado (48 px, `--shadow-sticky`) y el logo de 24 px entra a la izquierda con un fundido de `--dur-fast`.
  - El megamenú funciona igual desde la barra pegada y nunca pasa de `--mega-max-h`.
  - **No se esconde al bajar ni reaparece al subir:** una barra que se mueve sola cansa y hace fallar el hover.
- **Móvil:** 56 px pegados. La barra inferior, como está.

---

## 3. Anatomía del megamenú (escritorio)

### 3.1 Cuándo existe
- Solo con `@media (min-width: 1024px) and (hover: hover) and (pointer: fine)`.
- En pantallas táctiles de 1024 px o más:
  - El ítem navega a su página (09, §2.5).
  - El nivel 1 muestra además el botón **«Menú»**, que abre la hoja de la sección 6. Así la tableta también tiene el menú visual.

### 3.2 Medidas

| Elemento | 1440 px | 1024 px |
|---|---|---|
| Ancho del panel | **Todo el ancho de la ventana**, colgado del borde inferior del nivel 2, sin hueco. El contenido va dentro de `.container` (1280 máx., márgenes de 32) → **1216** | → **960** |
| Retícula | Las **12 columnas** del sitio, con 24 de separación; columna de 79,33 | columna de 58 |
| **Z1 · enlaces** (c1–c5) | **492**, en 2 subcolumnas de 234 | 386 (2 × 181) |
| **Z2 · destacado** (c6–c9) | **389**: imagen 16:9 de **389×219** | 304: imagen de 304×171 |
| **Z3 · ahora** (c10–c12) | **286** | 222 |
| Alto | **Fijo, 416**: 32 + **352 de contenido** + 32. Es igual en los seis paneles grandes, así que el borde no salta al pasar de uno a otro | Igual |
| Tope de alto | `--mega-max-h` = `100dvh − 48 − 32`. Si la ventana es más baja, el panel se desplaza por dentro con `overscroll-behavior: contain` | Igual |
| Panel «Más» | **596** de ancho. Su borde derecho se alinea con el borde derecho del ítem (a 1024 px se abre hacia la izquierda y no se sale) | Igual |

**Superficie y capas:**
- **Fondo, borde y radio:** blanco, sin borde y radio 0. Una sola elevación: borde más sombra a la vez es la «tarjeta fantasma» del craft floor.
- **Sombra:** `--mega-shadow` = `0 16px 32px -8px rgba(15,33,51,.20), 0 2px 4px rgba(15,33,51,.06)`. Solo cae hacia abajo, porque el panel cuelga de la barra.
- **Separador:** un único filete vertical de 1 px `--color-border`, centrado en el hueco entre Z1 y Z2.
- **Velo:** `--mega-scrim` (tinta al 16 %) sobre la página, bajo la cabecera (`--z-mega-scrim: 99`), con `pointer-events: none`. Separa el panel de lo que tiene debajo sin convertirlo en un modal.
- **Capa:** `--z-mega` (200), dentro de `<header>`.

### 3.3 Esquema general

```
 nivel 2 ─ Videos  Noticias ▐ Junior FC ▌ Selección  ●Datos  ●En vivo  Más ⌄
                            └─ pestaña blanca = «este panel es de este ítem»
┌──────────────────────────────────────────────────────────────────────────────────────────────┐
│                                        32                                                    │
│  ┌ Z1 · c1–c5 · 492 ──────────────────┐ ┆ ┌ Z2 · c6–c9 · 389 ────────┐  ┌ Z3 · c10–c12 · 286 ┐ │
│  │ Junior FC →   ← Z0 (24, cursiva)   │ ┆ │ ┌──────────────────────┐ │  │ Título del módulo  │ │
│  │ 24                                 │ ┆ │ │                      │ │  │                    │ │
│  │ Rótulo de grupo   │ Rótulo de grupo │ ┆ │ │                      │ │  │                    │ │
│  │ Enlace         40 │ Enlace       40 │ ┆ │ │  16:9 · 389 × 219    │ │  │                    │ │
│  │ Enlace         40 │ Enlace       40 │ ┆ │ └──────────────────────┘ │  │ partido · tabla ·  │ │
│  │ Enlace         40 │                 │ ┆ │ Etiqueta (14, azul)      │  │ escaleta · Shorts  │ │
│  │                   │ [botón]         │ ┆ │ Titular Archivo 700 20   │  │                    │ │
│  │                   │                 │ ┆ │ en 2 líneas · meta 14    │  │                    │ │
│  └────────────────────────────────────┘ ┆ └──────────────────────────┘  └────────────────────┘ │
│                                        32                         352 de contenido en total  │
└──────────────────────────────────────────────────────────────────────────────────────────────┘
   sombra hacia abajo · velo del 16 % sobre la página
```

**Cómo se reparten los 352 px de alto:**
- **Z1:** el título (Z0) ocupa 28 + 24 = 52 y deja **300** para los grupos. La subcolumna más larga (Datos: rótulo de 20 + 8 + 6 × 40) suma 268.
- **Z2:** imagen 219 + 16 + etiqueta 20 + 4 + titular 46 + 8 + meta 20 = 333 de los 352. El título va **solo sobre Z1**, de modo que Z2 y Z3 arrancan en el borde superior del contenido: el título del panel queda alineado con la parte de arriba de la foto.
- **Z3:** también arranca arriba, con su propio título de módulo.

### 3.4 Las zonas (iguales en los siete paneles)

**Z0 · Título del panel.** Le dice al usuario que «esta es la sección».
- Archivo 800 cursiva de 24 px (`--mega-title-fs`), en tinta, seguido de una flecha de 20 px en `--brand-600`. Es el gesto de los rótulos de sección del sitio, que viene de la cursiva del logo.
- Es un enlace al hub. El texto visible es el nombre de la sección («Junior FC»). Para el lector de pantalla, el enlace se llama como el Z0 de 09 («Ir a Junior FC», «Todos los videos», «Todas las noticias»): esa parte va con `visually-hidden`.
- Hover: subrayado de 2 px y la flecha avanza 2 px.

**Z1 · Enlaces.** Son las subpáginas.
- **Rótulo de grupo:** Inter 600 de 14 px, `--color-text-muted` (6,46:1), capitalización normal de la frase y sin mayúsculas. Distinto del enlace en color, peso y tamaño, para que nadie intente pulsarlo.
- **Enlace:** fila de 40 px (`--mega-row-h`) en Inter 500 de 16 px, en tinta.
  - Las etiquetas son las autosuficientes de 09 («Noticias de Junior»).
  - Pueden partirse en 2 líneas a 1024 px; la fila crece y no se trunca.
- **Separaciones:** 16 px entre grupos y 8 px entre el rótulo y el primer enlace.
- **Hover del enlace:** color `--brand-600` + subrayado de 1 px.
- **Enlace de la página actual:** peso 600, un cuadrado de 8×8 en `--brand-600` antes del texto (el mismo signo de las zonas de la tabla) y `aria-current="page"`.
- **Atajos `★` de 09:** sin marca visual. Son el mismo destino con la misma etiqueta, y no hace falta explicarlo.
- **Botón al pie de Z1** (YouTube en Videos y En vivo): botón secundario de 40 px con el icono de YouTube monocromo y la indicación de que abre otra pestaña.

**Z2 · Destacado.** Es la pieza que manda y **la única imagen** del panel (salvo en Datos, donde manda el dato).
- **Imagen:** 16:9 de 389×219, radio 0 y **sin texto encima**. Solo pueden ir sobre ella el play, el chip de duración y la píldora «EN VIVO» o «Al aire».
- **Debajo, en este orden:**
  - Etiqueta en Inter 600 de 14 px, en azul («Junior · Crónica»).
  - Titular en Archivo 700 de 20 px, en tinta, `-0.01em`, máximo 2 líneas con `text-wrap: balance`.
  - Meta en Inter 500 de 14 px, atenuada.
- **Enlace:** el bloque completo es un solo enlace y la imagen lleva `alt=""` (09).
- **Hover:** el titular se subraya y la imagen baja a `brightness(.94)`. Sin zoom.

**Z3 · Ahora.** Lo que tiene hora.
- **Título del módulo:** Inter 600 de 14 px, en tinta («Partido», «Shorts»). Es el nombre del bloque, no una etiqueta encima de otro título.
- **Horas y fechas:** Archivo condensado (`--wdth-condensed`) con cifras tabulares. Es la firma de la escaleta del sistema.
- **Pie con cifras:** «Datos de ejemplo · fuente por definir», en 12 px atenuado.

**Tarjeta de partido de Z3** (Junior, Selección y la fila «Hoy» de Datos). Es un mini Centro de Partidos.
- **Caja:** 286 de ancho, fondo `--mega-match-bg` (`--brand-900`), relleno de 24 y radio 0. Es el mismo lenguaje del banner de partidos.
- **Fila 1:** el torneo en Inter 600 de 14 px, blanco al 80 % (10,81:1). A la derecha, el estado:
  - En juego: píldora roja «EN VIVO 67'» (3,15:1 contra el fondo; texto 5,18:1).
  - Sin empezar: chip «Próximo partido» con borde blanco al 24 % y sin rojo.
  - Terminado: «Final».
- **Centro:** escudos de 48 px (`contain`, sin recorte) y, entre ellos, el marcador en `--fs-score-lg` (Archivo 800, ancho 82, tabular, blanco). Si el partido aún no empieza, en su lugar va la hora («7:30 p.m.») con el mismo estilo. Debajo, los nombres en Archivo 700 de 16 px.
- **Línea inferior:** estadio y canal en Inter 500 de 14 px, blanco al 72 % (8,99:1).
- **Enlace:** la tarjeta completa enlaza. En hover, el fondo pasa a `--brand-800` y el enlace de acción («Seguir el partido ›») se subraya en `--brand-300` (8,12:1).
- Es **el único bloque oscuro** del panel. Se justifica porque es lo único que está pasando en este momento.

### 3.5 Comportamiento: intención de hover, zona segura y tiempos

**Qué dice la investigación:**
- **NN/g:** no abrir hasta que el puntero se detenga (de 0,3 a 0,5 s si el panel tapa contenido). Dar una respuesta visual en menos de 0,1 s. No cerrar hasta 0,5 s fuera del panel, y no cerrar si el puntero va en diagonal hacia él.
- **Smashing:** registra implementaciones con 100 ms de entrada y 300 ms de salida.
- **Amazon (`jQuery-menu-aim`, B. Kamens):** usa un triángulo de intención para saber si el puntero va hacia el panel.

Tomo los números de 09 (250 / 0 / 400), que caen dentro de esos rangos, y les añado la zona segura en diagonal.

| Evento | Qué pasa | Tiempo |
|---|---|---|
| El puntero entra en un ítem | El fondo pasa a `--nav-hover-bg` **de inmediato**. Es la respuesta visual en menos de 0,1 s | `--dur-fast` 120 ms |
| El puntero se detiene y no hay panel abierto | Se abre el panel: opacidad 0→1 y `translateY(-4px→0)`. El ítem se vuelve pestaña blanca y el velo aparece con un fundido | Espera `--mega-open-delay` **250 ms**; entra en `--mega-dur-in` 200 ms |
| El puntero solo pasa de largo | No se abre nada: el temporizador se cancela | — |
| Hay un panel abierto y el puntero se mueve **en horizontal** a otro ítem | El panel cambia **al instante**. No se anima el panel: solo el contenido se funde. El alto es fijo, así que el borde no salta | `--mega-switch-delay` **0 ms**; fundido `--mega-dur-swap` 120 ms |
| **Zona segura en diagonal** | Mientras haya un panel abierto, se guardan los 3 últimos `pointermove`. Si el puntero cruza otro ítem **bajando** (dy > 0 y \|dy\| ≥ \|dx\|), **no cambia de panel**. El triángulo entre el puntero y las esquinas superiores del panel cubre todo el ancho porque el panel es tan ancho como la ventana. Solo cambia si el puntero se queda quieto `--mega-aim-rest` **80 ms** sobre ese ítem | — |
| El puntero sale del ítem y del panel, que cuentan como **una sola región** | El panel se cierra si el puntero no vuelve. La salida es solo de opacidad, sin desplazamiento | `--mega-close-delay` **400 ms**; sale en `--mega-dur-out` 120 ms |
| Tecla Esc | Cierre **inmediato**. Esc solo cierra, no abre (sigue mandando el hover). WCAG 1.4.13 lo exige para el contenido que aparece con hover | 0 |
| Scroll de más de 8 px, clic en un enlace, la ventana pierde el foco o pasa a menos de 1024 px | Cierre inmediato | 0 |
| La página carga con el puntero ya encima del menú | No se abre nada hasta el primer `pointermove` | — |

**Reglas generales:**
- **Sin hueco entre la barra y el panel,** así que el puente de 09 queda resuelto por geometría.
- **`prefers-reduced-motion`:** se quitan el fundido y el desplazamiento, pero **los temporizadores se mantienen**. No son movimiento: miden la intención.
- **Solo se animan `opacity` y `transform`.** Nunca el alto.

### 3.6 Accesibilidad con la regla de «solo hover» del dueño
- Cada ítem es un `<a>` real a su hub. El hub repite en sus fichas locales los destinos de Z1 y muestra el contenido de Z2 y Z3. Ese es el camino equivalente para teclado y lector de pantalla. La recomendación de 09 (que `:focus-within` también abra el panel) queda pendiente de que el dueño la apruebe; si la aprueba, no cambia nada visual.
- **Panel cerrado:** `visibility: hidden`, así que sus enlaces no reciben foco ni se leen.
- **Etiqueta del menú:** `<nav aria-label="Secciones">`. Los puntos rojos llevan su texto oculto: «, al aire ahora» y «, partido en vivo».
- **Imágenes:**
  - `width` y `height`, `loading="lazy"` y `decoding="async"`, con el fondo `--color-surface-subtle` mientras cargan.
  - WebP en 400 y 800 px de ancho con `sizes="(min-width:1280px) 389px, 304px"`.
  - En total, unos 200 KB entre los siete paneles.
- **El panel nunca tapa la cabecera** ni sus puntos rojos: cuelga bajo el nivel 2.
  - Lo único que cubre mientras dura el hover es el contenido de la página. Por ejemplo, el contador de cuenta regresiva de la cabecera de Selección (09, §2.5).
  - Se cierra con el scroll, así que nunca se queda encima de lo que el usuario quiere leer.

---

## 4. Los siete paneles (contenido de 09 §3, diseño aquí)

**Regla de fotos del menú:**
- Solo fotos del catálogo de `imagenes.py`.
- Las de Juan Salvador (`js-1`, `js-2`), solo en Videos, En vivo y Más, que es su función reservada (01, §7.4).
- Una foto no se repite entre paneles, **con una excepción**: `js-2` en Videos (16:9) y en Más (retrato 1:1). Solo hay dos fotos del presentador (ver la petición en la sección 9).

### 4.1 Videos

```
 Videos →
┌ Z1 · 492 ─────────────────────────────┐ ┆ ┌ Z2 · 389 ─────────────────┐  ┌ Z3 · 286 ───────────────┐
│ Qué ver               │ Por tema      │ ┆ │ ┌───────────────────────┐ │  │ Shorts        Ver todos ›│
│ Programas completos   │ Videos de     │ ┆ │ │ js-2 · 389×219        │ │  │ ┌──────────┐┌──────────┐│
│ Shorts                │   Junior      │ ┆ │ │(▶48)        [1:51:20] │ │  │ │ 9:16     ││ 9:16     ││
│                       │ Videos de la  │ ┆ │ └───────────────────────┘ │  │ │ 135×240  ││          ││
│                       │   Selección   │ ┆ │ Último programa           │  │ │[Short]   ││[Short]   ││
│                       │ Videos de Juan│ ┆ │ Programa completo: la     │  │ └──────────┘└──────────┘│
│ [▶ Suscríbete en YouTube ↗]           │ ┆ │ previa de Junior vs. Nac… │  │ El gol de   Tres datos  │
│                       │               │ ┆ │ 1:51:20 · 6 nov           │  │ Pertuz…0:30 de la F19…  │
└───────────────────────────────────────┘ ┆ └───────────────────────────┘  └─────────────────────────┘
```
- **Z2:** `js-2`, con `object-position: 60% 35%`. Play circular de 48 px abajo a la izquierda y chip «1:51:20» abajo a la derecha. La etiqueta «Último programa» va en azul.
- **Z3:** dos Shorts 9:16 de **135×240**, separados por 16 px, con el chip «Short» arriba a la izquierda. Debajo, el título en Inter 600 de 14 px (2 líneas) y la duración en meta.
  - **S1** «El gol de Pertuz, en 30 segundos»: `junior-plantel-trofeo` (recorte vertical a 50 % 35 %; 740 px de alto, nítida a 2x).
  - **S5** «Tres datos de la Fecha 19»: **`banderas-junior-millonarios`**, la misma pareja que en el carril de la portada. 09 propone aquí `junior-escudo-bandera`, pero mide 1440×372: en 9:16 da un recorte de 209×372 que se pixela a 135 px de ancho en pantallas 2x.
- Los Shorts nunca se mezclan con los programas: zona distinta, proporción distinta y título distinto.

### 4.2 Noticias

```
 Noticias →
┌ Z1 · 492 ─────────────────────────────┐ ┆ ┌ Z2 · 389 ─────────────────┐  ┌ Z3 · 286 ───────────────┐
│ Nuestro foco          │ Seguimiento y │ ┆ │ ┌───────────────────────┐ │  │ Más cobertura           │
│ Noticias de Junior    │ opinión       │ ┆ │ │ junior-trofeo-bandera │ │  │ Fútbol colombiano       │
│ Noticias de la Selec. │ Mercado de    │ ┆ │ │ 389×219               │ │  │ Tolima y América se     │
│                       │   fichajes    │ ┆ │ └───────────────────────┘ │  │ miran en la zona media… │
│ Más fútbol            │ Opinión       │ ┆ │ Junior · Análisis         │  │ ─────────────────────── │
│ Fútbol colombiano     │               │ ┆ │ Rivas y Castro: el eje    │  │ Europa                  │
│ Europa                │               │ ┆ │ que sostiene al líder     │  │ Un grande de Inglaterra…│
│ Colombianos en el ext.│               │ ┆ │ 5 min de lectura          │  │ ─────────────────────── │
│                       │               │ ┆ │                           │  │ Colombianos en el ext.  │
└───────────────────────────────────────┘ ┆ └───────────────────────────┘  └─────────────────────────┘
```
- **Z2:** `junior-trofeo-bandera` (50 % 40 %).
- **Z3:** 3 filas de texto, sin foto, separadas por un filete de 1 px:
  - Tema en Inter 600 de 14 px, en azul.
  - Debajo, el titular en Inter 600 de 16 px, en tinta, en 2 líneas.
  - Cada fila mide unos 88 px.
- Que estos temas vayan sin imagen es una decisión, no un hueco: el catálogo no tiene fotos de Europa ni del exterior. La regla es una pieza con foto y el resto en lista.

### 4.3 Junior FC

```
 Junior FC →
┌ Z1 · 492 ─────────────────────────────┐ ┆ ┌ Z2 · 389 ─────────────────┐  ┌ Z3 · brand-900 · 286 ───┐
│ El equipo             │ Lo último     │ ┆ │ ┌───────────────────────┐ │  │ Liga BetPlay · F20      │
│ Partidos de Junior    │ Noticias de   │ ┆ │ │ junior-bicampeon      │ │  │            [EN VIVO 67']│
│ Plantilla de Junior   │   Junior      │ ┆ │ │ 389×219               │ │  │ [JUN 48]  2–1  [NAC 48] │
│ Tabla de posiciones   │ Videos de     │ ┆ │ └───────────────────────┘ │  │  Junior       Nacional  │
│                       │   Junior      │ ┆ │ Junior · Crónica          │  │ Metropolitano · Win S.  │
│                       │ Mercado de    │ ┆ │ El día que Junior se      │  └─────────────────────────┘
│                       │   Junior      │ ┆ │ coronó bicampeón…         │  Racha [G][G][E][G][G]
│                       │               │ ┆ │                           │  Líder · 39 pts
└───────────────────────────────────────┘ ┆ └───────────────────────────┘
```
- **Z2:** `junior-bicampeon` (1080×1080, recortada a 16:9 con 50 % 45 %).
- **Z3:** tarjeta de partido (sección 3.4) con `escudo-junior` y `escudo-nacional`. Si Junior no está jugando, muestra el próximo partido (Bucaramanga vs. Junior, sáb 21 nov, 6:10 p. m.) sin rojo.
- **Debajo de la tarjeta, la racha:** 5 cuadros de 20×20 con la letra dentro, con el código del sistema (01, §3.2). G va en azul relleno con letra blanca; E en gris con letra en tinta. Nunca con círculos ni en rojo. Al lado, «Líder · 39 pts» en Inter 600 de 14 px.

### 4.4 Selección

```
 Selección Colombia →
┌ Z1 · 492 ─────────────────────────────┐ ┆ ┌ Z2 · 389 ─────────────────┐  ┌ Z3 · brand-900 · 286 ───┐
│ La Selección          │ Lo último     │ ┆ │ ┌───────────────────────┐ │  │ Amistoso · Fecha FIFA   │
│ Convocatoria          │ Noticias de   │ ┆ │ │ seleccion-once        │ │  │      [Próximo partido]  │
│ Partidos de la        │   la Selección│ ┆ │ │ 389×219               │ │  │ [COL 48] 7:30 [PER 48]  │
│   Selección           │ Videos de la  │ ┆ │ └───────────────────────┘ │  │  Colombia        Perú   │
│ Eliminatorias         │   Selección   │ ┆ │ Selección · Convocatoria  │  │ Vie 13 nov · Caracol TV │
│                       │               │ ┆ │ La lista para Perú y Chile│  └─────────────────────────┘
│                       │               │ ┆ │ cinco novedades y una…    │  Después: Chile vs Colombia
│                       │               │ ┆ │ 6 min de lectura          │  mar 17 nov · 6:00 p. m.
└───────────────────────────────────────┘ ┆ └───────────────────────────┘
```
- **Título del panel:** «Selección Colombia», el nombre completo; en la barra dice «Selección».
- **Z2:** `seleccion-once` (50 % 45 %), la misma foto que esa nota tiene en la portada. Mide 520 px de ancho: a 389 se ve nítida en 1x y algo blanda en 2x. Pido una versión más grande (sección 9).
- **Z3:** tarjeta del próximo partido con `escudo-colombia` y `Logotipo_FPF_2016` (Perú), y debajo la línea «Después: …» en Inter 500 de 14 px.

### 4.5 Datos

```
 Datos →
┌ Z1 · 492 ─────────────────────────────┐ ┆ ┌ Z2 · 389 ─────────────────┐  ┌ Z3 · 286 ───────────────┐
│ Hoy y tablas          │ Torneos       │ ┆ │ Liga BetPlay · Finaliza-  │  │ Hoy y mañana            │
│ Partidos de hoy       │ Liga BetPlay  │ ┆ │ ción 2026 (ejemplo)       │  │ HOY  Junior 2–1 Nacional│
│ ● Partido en vivo     │ Copa Colombia │ ┆ │ #  Equipo       PJ   PTS  │  │      [EN VIVO 67']      │
│ Posiciones            │ Eliminatorias │ ┆ │ 1 ■[e] Junior   19   39   │◄ │ ─────────────────────── │
│ Resultados de la fecha│ Copa Liberta- │ ┆ │ 2  [e] Nacional 19   38   │  │ SÁB  Pasto–Pereira 2:00 │
│ Goleadores            │   dores       │ ┆ │ 3  [e] Tolima   19   35   │  │ SÁB  Tolima–América 4:00│
│ Estadísticas          │ Copa Sudame-  │ ┆ │ 4  [e] Millonar.19   33   │  │ SÁB  Águilas–Alianza 6:00│
│                       │   ricana      │ ┆ │ 5  [e] Medellín 19   31   │  │                         │
│                       │               │ ┆ │ 6  [e] América  19   29   │  │                         │
│                       │               │ ┆ │ Datos de ejemplo · Ver la tabla completa ›│             │
└───────────────────────────────────────┘ ┆ └───────────────────────────┘  └─────────────────────────┘
```
- **Sin foto: aquí la imagen es el dato.**
- **Z2:** tabla de 6 filas de 36 px, con 32 px de cabecera.
  - Columnas: # (24), escudo de 20 + equipo en Inter 500 de 14 px, PJ y PTS, todas tabulares y alineadas a la derecha.
  - Cabecera en Inter 600 de 12 px, mayúscula y `--color-text-muted`.
  - **Fila de Junior:** `--color-surface-selected`, peso 600 (tinta sobre `--brand-50`, 15,29:1) y el cuadrado de 8 px de zona.
  - Pie: «Datos de ejemplo · fuente por definir» y «Ver la tabla completa ›».
- **Z3:** 4 filas de 56 px.
  - Columna de 48 px con el día en Archivo condensado de 14 px: «HOY» en tinta 700; «SÁB» en `--color-text-muted`.
  - Equipos en Inter 500 de 14 px y hora tabular a la derecha.
  - En la fila del partido en juego, la píldora roja va en lugar de la hora.
- **Z1:**
  - «Partido en vivo» lleva el punto rojo solo cuando hay partido; si no, pasa a «Último partido» (09).
  - Es el único panel con 11 enlaces. Caben en 2 subcolumnas (6 y 5) sin superar los 268 px.
- **Sin sello de Transelca en el menú:** 09 deja fuera del menú cualquier logo o patrocinio, y estoy de acuerdo.

### 4.6 En vivo

```
 En vivo →
┌ Z1 · 492 ─────────────────────────────┐ ┆ ┌ Z2 · 389 ─────────────────┐  ┌ Z3 · 286 ───────────────┐
│ El programa           │ Para ponerte  │ ┆ │ ┌───────────────────────┐ │  │ Ahora y después         │
│ Parrilla de lunes a   │ al día        │ ┆ │ │[Al aire] js-1 389×219 │ │  │ 10:00 ● Ahora           │◄ fondo brand-50
│   viernes             │ Programas     │ ┆ │ │                       │ │  │ Apertura y titulares    │
│ Participa en el       │   anteriores  │ ┆ │ │(▶ 64)                 │ │  │ ─────────────────────── │
│   programa            │               │ ┆ │ └───────────────────────┘ │  │ 10:30   Después         │
│ [▶ Seguir el canal en YouTube ↗]      │ ┆ │ L–V · 10:00 a.m.–12:00 m. │  │ Junior en primer plano  │
│                       │               │ ┆ │ El show de las mañanas,   │  │ ─────────────────────── │
│                       │               │ ┆ │ en vivo desde Barranquilla│  │ 67' ● Junior 2–1 Nacional│
│                       │               │ ┆ │ [Ver en vivo]             │  │ Partido en juego        │
└───────────────────────────────────────┘ ┆ └───────────────────────────┘  └─────────────────────────┘
```
- **Z2:** `js-1`, con play de 64 px.
  - **Al aire:** píldora roja «Al aire» arriba a la izquierda de la imagen.
  - **Fuera del aire:** chip `--color-chip-on-media` «Próximo programa», sin rojo.
  - El botón va debajo del titular, primario de 40 px: «Ver en vivo» o «Ver el último programa».
- **Z3:** 3 filas de 64 px.
  - La hora en Archivo condensado de 18 px, tabular, en `--brand-600`.
  - Al lado, el estado («Ahora», «Después») en Inter 600 de 14 px, y debajo el bloque en Inter 500 de 15 px.
  - **El tramo en curso** va con fondo `--brand-50`, punto rojo de 8 px y «Ahora» en rojo (4,60:1 sobre `--brand-50`).
  - La fila del partido solo aparece si hay uno en juego, con el minuto en rojo.
  - Fuera del aire, no hay rojo en todo el panel.

### 4.7 Más (panel estrecho de 596 px)

```
                                         Más ⌄
                   ┌ 596 · borde derecho alineado con el ítem ──────────────┐
                   │ Habla Deportes            ┆ El programa                 │
                   │ Quiénes somos             ┆ ┌────────┐ Juan Salvador    │
                   │ Contacto                  ┆ │ js-2   │ Bárcena          │
                   │ Publicidad y patrocinios  ┆ │ 1:1    │ Presentador y    │
                   │ Nuestros aliados          ┆ │ 120×120│ creador de cont. │
                   │ Redes                     ┆ └────────┘                  │
                   │ Legales                   ┆ Videos de Juan Salvador     │
                   │ [▶] [◎] [X] [f]           ┆ Su timeline en X            │
                   │                           ┆ Pregúntale a Juan           │
                   └─────────────────────────────────────────────────────────┘
```
- **Medidas:** relleno de 32 y alto según el contenido (unos 400 px).
- **Columnas:** Z1 de 7/12 (unos 300 px) y Z2 de 5/12 (unos 212 px).
- **Retrato:** 1:1 de 120×120, con radio 0 (también en avatares), `object-position: 60% 35%`. Al lado, el nombre en Archivo 700 de 18 px y el cargo en 14 px atenuado. Debajo, los 3 enlaces en filas de 40.
- **Redes:** iconos de 44 en monocromo, en tinta.
- **Sin título Z0:** «Más» no es una sección.

---

## 5. Estados y claridad

### 5.1 Ítem de la barra azul

| Estado | Tratamiento | Señal que no depende del color |
|---|---|---|
| Reposo | Blanco, Inter 600 de 15 px | — |
| Hover (antes de abrir) | Fondo `--nav-hover-bg`, blanco al 8 % (texto 8,73:1), en 120 ms | Cambio de fondo |
| **Panel abierto** | **Pestaña blanca:** `--nav-tab-open-bg` con texto `--nav-tab-open-text` (8,82:1). La pestaña y el panel forman una sola pieza blanca que cuelga del azul. Así se ve a qué ítem pertenece el panel | Forma: el ítem se recorta de la barra (blanco frente a `--brand-700`, 11,06:1) |
| Activo (sección actual) | Peso 700 + barra blanca de 3 px abajo + `aria-current="page"` | Peso y barra |
| Activo y abierto | Pestaña blanca, peso 700 y la barra de 3 px pasa a `--brand-600` | Peso y barra |
| Foco de teclado | Anillo de 2 px `--color-focus-on-dark` con desplazamiento de −4 px. El foco **no** abre el panel (lo decidió el dueño); Intro lleva a la página | Anillo |
| **Al aire o partido en juego** | Punto rojo de 8 px **con anillo blanco de 2 px** (`--live-dot-*`) antes del texto, a 8 px. Late con `--live-pulse` y queda fijo con movimiento reducido. Lleva texto oculto («, al aire ahora» o «, partido en vivo») | Forma del punto y texto oculto |
| Sin nada en vivo | Sin punto y nada en rojo | — |

**Por qué el anillo:** el rojo directo sobre `--brand-700` da 2,14:1 y no llega al 3:1 de WCAG 1.4.11. Con el anillo, el blanco frente al azul da 11,06:1 y el rojo frente al blanco, 5,18:1. Cuando la pestaña está abierta (fondo blanco), el anillo se funde con ella y el rojo sigue en 5,18:1.

### 5.2 Para que el menú se entienda
1. **Tres niveles que no se parecen entre sí.**
   - La barra es la sección: blanco sobre azul.
   - El título del panel es su portada: Archivo 800 cursiva de 24 px con flecha.
   - Los rótulos de grupo solo ordenan: Inter 600 de 14 px gris, que no son enlaces.
   - Los enlaces son las subpáginas: Inter 500 de 16 px en tinta.
2. **Una etiqueta, un destino** (regla de 09). Las etiquetas se entienden también fuera del panel, en la hoja móvil y en las búsquedas.
3. **El rojo solo dice «está pasando ahora».** En la barra aparece en dos ítems como máximo. Dentro de los paneles: la píldora del partido en juego, «Partido en vivo» con partido, el tramo «Ahora» y «Al aire».
4. **Sin etiquetas superiores, sin mayúsculas decorativas.** Solo van en mayúscula la píldora «EN VIVO», las cabeceras de la tabla y los días de Z3 de Datos.
5. **Lo que está en vivo no cambia de sitio.** Los paneles mantienen sus zonas aunque haya un partido. La píldora roja ya atrae la mirada, y que todo esté siempre en el mismo lugar ayuda más que reordenar.

### 5.3 Dentro del panel
- **Enlace:** en hover, `--brand-600` y subrayado de 1 px. El de la página actual lleva el cuadrado de 8 px, peso 600 y `aria-current`.
- **Destacado:** en hover, el titular se subraya y la imagen pasa a `brightness(.94)` en `--dur-fast`.
- **Tarjeta de partido:** en hover, fondo `--brand-800` y el enlace de acción se subraya.
- **Imagen que no carga:** queda el fondo `--color-surface-subtle` con la misma proporción, sin icono roto.
- **Sin datos:** «Sin datos en este momento», en gris y nunca en rojo (09, §6.1).

### 5.4 Contrastes de los pares nuevos (calculados con la fórmula WCAG 2.1)

| Par | Ratio | Uso |
|---|---|---|
| Blanco / `--brand-700` + blanco 8 % | 8,73 | Hover del ítem |
| `--brand-600` / blanco | 8,82 | Texto de la pestaña abierta |
| Blanco (pestaña) frente a `--brand-700` (barra) | 11,06 | Contorno de la pestaña |
| Rojo `#D42318` / `--brand-700` | **2,14 ✗** | Por eso el punto lleva anillo |
| Anillo blanco / `--brand-700` · rojo / blanco | 11,06 · 5,18 | Punto en vivo sobre azul |
| Blanco 88 % / campo del buscador (blanco 12 % sobre `--brand-600`) | 5,36 | Texto del buscador. Al 80 % daba 4,56, demasiado justo |
| Borde blanco 48 % / `--brand-600` | 3,37 | Límite del buscador. Al 40 % daba 2,82 ✗ |
| Blanco · blanco 80 % · blanco 72 % / `--brand-900` | 16,33 · 10,81 · 8,99 | Tarjeta de partido |
| `--brand-300` / `--brand-900` | 8,12 | Enlace de la tarjeta |
| Píldora roja frente a `--brand-900` · blanco / rojo | 3,15 · 5,18 | Píldora sobre la tarjeta |
| Tinta · `--gray-600` · rojo · `--brand-600` / `--brand-50` | 15,29 · 5,73 · 4,60 · 7,83 | Fila de Junior y tramo «Ahora» |
| `--gray-600` · tinta / `--gray-100` | 5,59 · 14,91 | Fila pulsada en la hoja móvil |
| `--brand-600` / `--gray-50` | 8,14 | Enlaces sobre el fondo de carga |

---

## 6. Menú móvil (< 1024 px, y tabletas táctiles)

### 6.1 Cabecera móvil, 56 px pegados
- A la izquierda, el logo de 28 px.
- A la derecha, la lupa de 44×44 y el botón **«Menú»**: icono de lista de 24 px más la palabra «Menú» en Inter 600 de 14 px, en blanco, de 44 de alto y sin borde.
- Abierto, pasa a una X con «Cerrar» (`aria-expanded`, «Cerrar el menú»). La palabra hace falta porque la hamburguesa sola no se descubre.

### 6.2 La hoja
- **Posición:** pantalla completa entre la cabecera y la barra inferior, que siguen visibles (09, §4): `position: fixed; top: var(--sheet-top); bottom: var(--sheet-bottom)`.
- **Fondo y desplazamiento:** blanco, con desplazamiento interno (`overscroll-behavior: contain`) y la página de fondo bloqueada.
- **Movimiento:** entra con opacidad y `translateY(-4px→0)` en 200 ms, como el megamenú, porque baja del mismo sitio. Sale en 120 ms.
- **Foco:** queda atrapado dentro de la hoja. Esc cierra y el foco vuelve al botón.
- **Tabletas** (desde 600 px): el contenido se centra a 640 px.

```
┌──────────────────────────────── 390 ─┐
│ [Habla 28]              [⌕] [✕ Cerrar]│ 56  cabecera azul (sigue visible)
├──────────────────────────────────────┤
│ Buscar en Habla Deportes             │     etiqueta visible
│ [⌕ Equipo, jugador o tema          ] │ 48  campo real, texto de 16 px
│ (Junior) (Selección) (Mercado) (Europa)    chips de sugerencia (ya existen)
│                                      │
│ Ahora                                │
│ [Al aire] El show de las mañanas     │ 88  fila sin foto: píldora + título
│           Ver en vivo ›              │
│ ─────────────────────────────────────│
│ [EN VIVO 67'] [JUN] Junior 2–1       │ 88  solo con partido de Junior o
│               Nacional [NAC]  Seguir ›│     de la Selección en juego
│                                      │
│ ┌──────┐ Videos                  ┆ ⌄ │ 80  fila-tarjeta: miniatura + nombre
│ │96×54 │ Programas y Shorts      ┆   │     + descripción · acordeón de 48
│ └──────┘─────────────────────────────│
│ ┌──────┐ Noticias                ┆ ⌃ │ 80  (abierto: la sección actual)
│ │96×54 │ Todos los temas         ┆   │
│ └──────┘                             │
│          Nuestro foco                │     rótulo de grupo de 12 px atenuado
│          Noticias de Junior          │ 48
│          Noticias de la Selección    │ 48
│          Más fútbol · …              │
│ ┌──────┐ Junior FC               ┆ ⌄ │
│ ┌──────┐ Selección               ┆ ⌄ │
│ ┌──────┐ ● Datos                 ┆ ⌄ │
│ ┌──────┐ ● En vivo               ┆ ⌄ │
│                                      │
│ Más                                  │
│ Juan Salvador · Redes · Quiénes somos · Contacto y publicidad
│ · Nuestros aliados · Legales                        filas de 48
│ [▶] [◎] [X] [f]                       iconos de 44
├──────────────────────────────────────┤
│ Inicio  Videos  Noticias  ●Datos ●En vivo │ 64 + área segura: barra inferior visible
└──────────────────────────────────────┘
```

### 6.3 Las piezas de la hoja
- **Buscador primero**, con etiqueta visible y texto de 16 px para que iOS no haga zoom.
- **«Ahora».** Filas de 88 px **sin foto**:
  - Programa: píldora roja «Al aire» o chip gris «Próximo programa», con «El show de las mañanas» y «Ver en vivo ›».
  - Partido (solo si hay uno de Junior o la Selección en juego): píldora con el minuto, escudos de 24, marcador en Archivo condensado de 18 px tabular y «Seguir ›».
  - **Diferencia con 09:** 09 pone aquí la miniatura `js-1`. Yo la llevo a la fila de En vivo, para que la misma foto no salga dos veces en una sola pantalla.
- **Seis filas-tarjeta de sección** (80 px, mismo orden que la barra). Es lo que hace visual la hoja.
  - **Miniatura 16:9 de 96×54** con radio 0 y la foto de Z2 de su panel: `js-2` (Videos), `junior-trofeo-bandera` (Noticias), `junior-bicampeon` (Junior FC), `seleccion-once` (Selección), `banderas-junior-nacional` (Datos: la textura de la previa del partido, porque su panel no tiene foto) y `js-1` (En vivo).
  - **Textos:** el nombre en Archivo 700 de 18 px y una línea de descripción en Inter 400 de 14 px, atenuada. Las descripciones son las de 09.
  - **Dos zonas táctiles separadas por un filete de 1×32.** La fila (miniatura y textos) lleva al hub. El botón de 48×48 de la derecha abre o cierra el acordeón: `aria-expanded` y un chevron que gira 180° en 200 ms.
  - **Un solo acordeón abierto a la vez.** Por defecto, el de la sección actual (09).
  - **Dentro del acordeón:**
    - Rótulos de grupo en Inter 600 de 12 px, atenuados.
    - Enlaces de 48 en Inter 500 de 16 px, alineados con el texto a 112 px del borde y separados por filetes de 1 px.
    - Z2 y Z3 no aparecen aquí (09, §4.2): ya están en el hub.
  - **Al pulsar:** fondo `--gray-100` en `:active`, sin el destello de toque del navegador.
  - Datos y En vivo llevan el mismo punto rojo de la barra.
- **Más y redes:** filas sencillas de 48 e iconos de 44.
- **Peso:** 6 miniaturas a 192 px de ancho (2x), unos 50 KB, que se cargan al abrir la hoja.

### 6.4 Barra inferior
- Se adopta la de 09: **5 pestañas, Inicio · Videos · Noticias · Datos · En vivo** (72 px cada una a 360 px).
  - «Noticias» lleva ahora a `noticias.html` y la portada pasa a «Inicio», así que la etiqueta ya no engaña.
  - Si el dueño prefiere 4, el plan B de 09 es Inicio · Videos · Datos · En vivo.
- **Visual:** es la de 01 (§6.15) con 5 ítems. Icono de 24 y rótulo de Inter 600 de 12 px. El activo lleva el icono relleno, la barra de 3 px en `--brand-600` y peso 700.
- **Puntos rojos** de 8 px sobre el icono de Datos o de En vivo (sobre blanco, 5,18:1: aquí no hace falta anillo).
- **No se añade una sexta pestaña «Menú»:** el botón ya está arriba, con su palabra.

---

## 7. Lo que NO hacemos

- **La barra blanca contextual** pegada bajo el menú, o cualquier tercera capa en la cabecera.
- **Escudos de clubes en el menú** como atajos a equipos. Los escudos solo aparecen en las tarjetas de partido, en la tabla y en la lista de partidos.
- **Titulares sobre las fotos,** degradados sobre imagen o texto blanco encima de la foto. Sobre ella solo pueden ir el play, la duración, «Short» y «EN VIVO» / «Al aire».
- **Más de una imagen grande por panel,** cuadrículas de miniaturas o carruseles dentro del menú.
- **Pauta, GIFs, logos de aliados o sellos de patrocinio** en el menú.
- **Rojo** en «Exclusivo», en YouTube o para destacar una sección. Tampoco el punto rojo sin anillo sobre el azul.
- **En escritorio:**
  - Abrir con clic o con teclado (decisión del dueño).
  - Abrir al instante, sin esperar intención, o cerrar al instante al salir.
  - Animar el alto del panel.
  - Paneles grandes de altos distintos.
- **Borde y sombra a la vez,** paneles o imágenes con esquinas redondeadas, o sombras en las tarjetas de dentro.
- **Etiquetas repetidas o sin pista** («Todas», «Resumen», «Hoy» solo), etiquetas superiores en mayúscula o chevrones en todos los ítems.
- **Puntos de color decorativos.** Solo existe el punto rojo de en vivo y el cuadrado azul de «estás aquí».
- **Fotos fuera del catálogo,** fotos de Juan Salvador ilustrando noticias o recortes que se pixelan (fotos de menos de 2x del tamaño en pantalla en las piezas grandes).
- **Una hamburguesa sin palabra,** una hoja móvil hecha solo de texto plano o un cajón lateral estrecho que corta las etiquetas.
- **Una cabecera que se esconde al bajar** y aparece al subir.

---

## 8. Diferencias con `09-menu-arquitectura.md` (para que el desarrollador no dude)

| Tema | 09 | 08 (este documento) | Motivo |
|---|---|---|---|
| Ancho del panel | Ancho del contenedor (máx. 1200) | Toda la ventana, con el contenido dentro de `.container` (1280) | El panel se siente una capa del encabezado y no una tarjeta flotante. El contenedor del sitio mide 1280, no 1200 |
| Alto | ~420 | **416 fijo** | Coinciden. Lo fijo evita saltos al pasar de un panel a otro |
| Desplazamiento de entrada | 4 px | 4 px | Coinciden (01, §6.2) |
| Short S5 | `junior-escudo-bandera` | `banderas-junior-millonarios` | La primera mide 372 px de alto y se pixela en 9:16 a 2x. La segunda es la que usa la portada |
| Imagen de «Ahora» en la hoja | `js-1` en la tarjeta «Ahora» | «Ahora» sin foto; `js-1` va en la fila de En vivo | Evitar la misma foto dos veces en una sola pantalla |
| `js-2` | En Videos y en Más | Igual, como única excepción | Solo hay dos fotos del presentador (petición en la sección 9) |

Todo lo demás (etiquetas, orden, grupos, destinos, puntos rojos, barra inferior y tiempos de 250 / 0 / 400) sale de 09 sin cambios.

## 9. Fotos y contenido que pido
1. **Un tercer retrato de Juan Salvador** (1:1 o 4:5, de al menos 800 px), para que «Más» no repita la foto de Videos.
2. **Versiones grandes (≥ 1280 px de ancho)** de `seleccion-once` y `junior-trofeo-bandera`. Hoy miden 520 px y se ven blandas en pantallas 2x a 389 px.
3. **Portadas verticales reales de los Shorts del canal** (9:16, 1080×1920). Hoy se recortan fotos horizontales.
4. **Una foto para Europa y otra para Colombianos en el exterior,** si se quiere que algún día tengan destacado propio. Hoy van en texto, a propósito.
5. **Cifras reales de seguidores** si se muestran en «Más». Si no, se quitan: hoy son de ejemplo.

### Fuentes consultadas
- NN/g, «Mega Menus Work Well for Site Navigation» y «Timing Guidelines for Exposing Hidden Content»: pausa antes de abrir, respuesta visual en menos de 0,1 s, gracia al cerrar, problema de la diagonal, opciones agrupadas y no repetidas.
- Smashing Magazine, «Frustrating Design Patterns: Mega-Dropdown Hover Menus» (2021): 100 ms para abrir y 300 ms para cerrar; no más de 6–7 ítems por grupo.
- B. Kamens, «Breaking down Amazon's mega dropdown»: triángulo de intención.
- WCAG 2.1: 1.4.11 (contraste de interfaz), 1.4.13 (contenido en hover: se puede cerrar, se puede recorrer con el puntero y persiste), 2.1.1 (camino equivalente mediante un enlace real) y 2.5.8 (tamaño de objetivo, filas de 40 con puntero fino).
- Capturas `ref-*.png`: Sky Sports (dos barras más la sección), Globo Esporte (cajón de texto) y Win Sports (lista lateral en mayúsculas). Las tres usan listas de texto; el salto de esta propuesta es el destacado con imagen y la zona «Ahora».
- Skills del proyecto (solo lectura):
  - `impeccable/reference/craft-floor.md`: una sola elevación, sin etiquetas superiores, sin bordes laterales de color.
  - `layout.md`: prueba de entrecerrar los ojos, agrupar por proximidad.
  - `mobile-native`: `hover: hover` y `pointer: fine`, `dvh`, `safe-area`, `overscroll-behavior`.
  - `design-taste-frontend`: menú en una línea y de 80 px como máximo, sin puntos decorativos.
  - `ui-ux-pro-max`: barra inferior de 5 ítems como máximo, 44 px, no depender solo del hover.
