# 09 — Menú y megamenú: arquitectura y contenido

**Rol:** arquitecto de información / estratega de contenido
**Fecha:** 7 de noviembre de 2026
**Responde a:** `07-brief-menu.md` ("el menú no se entiende; quiero un megamenú muy visual con contenido destacado").
**Insumos:** `00`, `02` (§3 y §8), `05`, `06`, `03-contenido.md`, `data-ejemplo.json`, `aliados.json`, `nueva-src/sitio.py`, `nueva-src/plantilla.py`, `nueva-src/imagenes.py`, el sitio generado en `propuesta/html/nueva/` (para comprobar anclas y filtros reales) y las capturas `actual-*.png`.
**Alcance:** estructura, etiquetas, orden y contenido exacto de cada panel, menú móvil, destinos y mapa para el generador. **No** define el aspecto visual (eso es `08-menu-diseno.md`) ni escribe HTML.

> Todo dato, titular y cifra del menú sale de `03-contenido.md` y `data-ejemplo.json` y es **de ejemplo**. El menú no inventa hechos nuevos.

---

## 0. Resumen de decisiones

1. **Dos capas, no tres.** La barra blanca contextual **se funde con el megamenú**: sus enlaces pasan a cada panel y lo que era "índice de página" pasa al contenido de la página. El encabezado baja de ~160 px a **112 px** (logo 64 + barra azul 48).
2. **Primarios: Videos · Noticias · Junior FC · Selección · Datos · En vivo · Más.** Seis y "Más". **Cambia el orden** (Videos primero): el cliente lo puso de primero.
3. **Los siete ítems abren un megamenú** con el mismo esqueleto de tres zonas: **enlaces agrupados (Z1) · una pieza destacada con imagen o dato (Z2) · un dato vivo (Z3)**. Cada ítem sigue siendo un enlace real.
4. **Regla de oro de etiquetas: una etiqueta, un destino.** Una URL puede aparecer en varios paneles como atajo, pero una misma etiqueta nunca lleva a dos URL. Todas las etiquetas se entienden **fuera de contexto** ("Noticias de Junior", no "Noticias").
5. **"En vivo" = el programa de YouTube** (decisión D1 de `06`). El partido en juego tiene su propio punto rojo en **Datos**, y aparece como cortesía dentro del panel de En vivo.
6. **Mercado y Opinión salen de "Más"** y viven en el panel de Noticias. **Juan Salvador** pasa a tener nombre propio en "Más" (antes "Opinión" llevaba a su perfil).
7. **Móvil:** menú de pantalla completa con buscador visible, tarjeta "Ahora" (lo que está en vivo), seis filas con acordeón y enlaces secundarios; la barra blanca deslizable desaparece. **Barra inferior de 5 pestañas:** Inicio · Videos · Noticias · Datos · En vivo.
8. **No hace falta ninguna página nueva.** Todos los destinos existen; tres filtros por parámetro degradan (cargan la página completa) hasta implementarse (§5).

### Desvíos del brief (con razones)

| # | Desvío | Razón |
|---|---|---|
| V1 | Se **elimina** la barra blanca (el brief la daba por abierta; `00` decisión 12 la fijaba). | Con megamenús, sus enlaces se repetirían dos veces en pantalla. Ver §2.3. Plan B en §2.3. |
| V2 | **Videos pasa a primer lugar** (antes cuarto). | `05`: prioridad 1 = videos del programa, 2 = Shorts. `02` §3.2 ya dejaba esto como hipótesis a corregir. |
| V3 | **Barra inferior de 5 pestañas** (el brief: 4). | "Noticias" llevaba a la portada (etiqueta ≠ destino) y la portada hoy es video-first. Se añade "Inicio" y se conserva "Noticias" (pregunta 1 del cliente: "ver videos, leer noticias"). Plan B: Inicio · Videos · Datos · En vivo. |
| V4 | El menú móvil **sí usa acordeones** (`02` I3 decía "enlaces simples"). | La barra contextual desaparece; sin acordeón los sub-destinos no tendrían acceso táctil en un toque. |
| V5 | El punto rojo de **Datos** se enciende con partidos de Junior o Selección en juego. | `02` lo ponía en un hijo de Datos; al quitar la barra blanca, ese hijo ya no está visible. |

---

## 1. Por qué el menú actual "no se entiende" (desde la arquitectura)

Capturas: `actual-home.png`, `actual-datos-panel.png`, `actual-mas.png`, `actual-m-abierto.png`.

### 1.1 Hallazgos

| # | Problema | Evidencia en `sitio.py` | Efecto |
|---|---|---|---|
| H1 | **Misma etiqueta, destinos distintos.** "Mercado" lleva a tres sitios; "Opinión" a dos. | `CONTEXTO.noticias`: Mercado → `noticias.html?tema=mercado` y Opinión → `?tema=opinion`. `MAS`: "Mercado de fichajes" → `mercado.html` y "Opinión" → `juan-salvador.html` (un perfil, no un índice de opinión). `CONTEXTO.junior`: Mercado → `mercado.html?equipo=junior`. | El usuario no sabe qué va a abrir. Rompe la confianza en la etiqueta. |
| H2 | **Misma función, cinco nombres.** La "casa" de cada sección se llama Todas / Todos / Resumen / Programa en vivo / Hoy. | Primer ítem de cada barra blanca. | Hay que aprender un vocabulario por sección. |
| H3 | **Dos ejes mezclados en un solo árbol.** El sitio es una matriz (equipo × formato): Junior × Noticias, Junior × Videos, Junior × Datos. El menú la trata como árbol y duplica. | "Noticias" contiene Junior y Selección, que ya son primarios. "Junior FC" contiene Noticias, Videos, Mercado y Datos, que ya son (o tienen) primarios. | Cualquier contenido tiene dos "padres" y el usuario duda dónde buscar. |
| H4 | **"En vivo" significa dos cosas.** Es el programa (primario) y también el partido ("Partido en vivo" dentro de Datos), con punto rojo en dos sitios. | `PRIMARIO` y `CONTEXTO.datos`. | Quien busca el marcador toca "En vivo" y cae en un reproductor. |
| H5 | **Etiquetas sin pista ("scent" débil).** "Resumen", "Todas", "Hoy", "Datos", "Más" no dicen qué hay detrás. | Todas las barras. | El primario promete poco y el usuario no entra. |
| H6 | **Solapamiento interno en Datos.** "Hoy", "Resultados", "Partido en vivo", "Estadísticas" y "Datos" se pisan; "Datos" aparece también como panel en Junior (lleva a la liga completa, no a Junior). | `CONTEXTO.datos`, `CONTEXTO.junior`. | El panel promete algo de Junior y entrega la tabla de todos. |
| H7 | **"Más" mezcla lo editorial con lo corporativo.** Mercado y Opinión (temas de lectura) conviven con Contacto y Quiénes somos. Juan Salvador, la cara del programa, no aparece. | `MAS`. | El contenido más "vendible" queda escondido. |
| H8 | **El nivel 2 solo existe para la sección actual** y los primarios no despliegan nada. | `plantilla._contexto`. | El menú no "vende" el contenido antes de entrar; nada visual. |
| H9 | **Shorts y Selección-Eliminatorias enterrados o saliendo de la sección.** Shorts es 3.º en la barra de Videos pese a ser la prioridad 2 del cliente; "Eliminatorias" en Selección lleva a Datos sin avisar. | `CONTEXTO.videos`, `CONTEXTO.seleccion`. | Se pierde el formato que el cliente más quiere. |
| H10 | **El orden no refleja las prioridades del cliente** (Videos, Shorts, Datos). | `PRIMARIO`. | El primer ítem no es lo más importante. |
| H11 | **Sección "Habla Deportes" sin presencia en la barra azul** (redes, contacto, legales no tienen primario). | `PAGINAS` (`"primario": None`). | Pierde orientación en esas páginas. |
| H12 | **Móvil.** La barra contextual se corta; la hamburguesa no abre nada; la pestaña "Noticias" va a la portada. | `INFERIOR`, `MENU_MOVIL`. | Etiqueta ≠ destino; sin acceso a los sub-destinos. |

### 1.2 Qué reglas de arquitectura de información se aplican

Fuentes: NN/g (ver "Fuentes" al final), los skills `impeccable/clarify.md` y `ui-ux-pro-max`, y el benchmark del proyecto.

| Regla | Cómo se aplica aquí |
|---|---|
| **Los megamenús funcionan porque muestran varios niveles a la vez** y permiten comparar caminos; los menús en cascada fallan. | Un solo nivel de panel; sin submenús dentro de paneles. |
| **Agrupar en bloques con título según el modelo mental del usuario.** | 2 a 3 grupos por panel, cada grupo con 2 a 6 enlaces y un rótulo de una a tres palabras. |
| **Etiquetas cortas y descriptivas, con la palabra informativa primero.** Imágenes e iconos complementan el texto, no lo reemplazan. | "Noticias de Junior", "Calendario y resultados de Junior"; ninguna etiqueta depende de una imagen. |
| **Cada opción se muestra una sola vez por panel.** | Sin enlaces repetidos dentro de un panel; el padre es el encabezado del panel, no un ítem más. |
| **Tamaño razonable del panel.** Las cifras publicadas varían (de 5 a 8 enlaces por columna; hasta ~36 en total según fuentes secundarias no verificadas). | **Máximo 11 enlaces por panel** (Datos); el resto, entre 5 y 8. Muy por debajo de cualquier tope citado. |
| **Información-scent:** la etiqueta debe anticipar el destino. | Etiquetas autosuficientes + pieza destacada que muestra un ejemplo real del contenido. |
| **Hover:** hay que distinguir "paso el mouse" de "quiero leer"; NN/g recomienda ~0,5 s de pausa y avisa de que los menús por hover frustran por aperturas y cierres accidentales. | Ver §2.5. El dueño fijó hover-only; se compensa con retardos, puente invisible y la mitigación de accesibilidad. |
| **Patrón de los referentes deportivos.** ESPN usa subnavegación de fútbol con sustantivos de intención ("Scores", "Tables", "Transfers", "Teams"); agrupa los deportes secundarios en "More". El benchmark del proyecto: un color de marca, dos niveles, marcadores y datos junto al contenido. | Etiquetas-sustantivo; "Más" para lo secundario; datos vivos dentro del panel. |

> Límite de la investigación: no pude verificar en vivo el menú de BBC Sport ni los números exactos de enlaces por panel; las cifras de los blogs secundarios no se citan como hechos.

---

## 2. Nueva estructura de navegación

### 2.1 Ítems primarios (barra azul)

| # | Etiqueta | Destino | Pregunta del hincha que responde | Por qué este nombre |
|---|---|---|---|---|
| 1 | **Videos** | `videos.html` | ¿Qué hay nuevo del programa? | Prioridad 1 y 2 del cliente (programa y Shorts). Un solo sustantivo; cubre programas completos y Shorts. |
| 2 | **Noticias** | `noticias.html` | ¿Qué se dice hoy y de qué temas? | Pregunta 1 del cliente: "leer noticias". Es el índice de **todos los temas**, incluidos los que no tienen hub propio. |
| 3 | **Junior FC** | `junior.html` | ¿Cómo va Junior? | "Junior FC" distingue el **equipo** (hub) del tema "Junior" en noticias. |
| 4 | **Selección** | `seleccion.html` | ¿Quién juega y a quién convocaron? | Corto. "Selección Colombia" queda para el `h1` y el título del panel. |
| 5 | **Datos** | `datos.html` | ¿Cómo va la tabla y qué se juega hoy? | Es la palabra del cliente ("resultados y datos") y de la barra inferior. Su pista (la tabla) la pone el panel. |
| 6 | **En vivo** | `en-vivo.html` | ¿Hay algo al aire ahora? | Se queda: es corto, se entiende y el punto rojo lo respalda. Es el programa de YouTube (`06` D1). |
| 7 | **Más** | `#pie-enlaces` | ¿Quién es Habla Deportes y cómo los contacto? | Convención para lo secundario. Su panel lleva el rostro del programa. |

**Etiquetas descartadas:** "Inicio" (el logo ya lo es), "Radio" (no confirmado; `06`), "Programa" (se confunde con En vivo), "Fútbol" (demasiado amplio y pisa a Junior/Selección), "Resultados" (promete menos que la sección: la tabla, los goleadores), "Tablas", "Multimedia", "Opinión" como primario (hoy es un solo autor).

**Orden.** Videos y Noticias (lo que el cliente quiere que la gente haga) · Junior FC y Selección (el foco editorial, juntos) · Datos (prioridad 3) · En vivo (la señal, al extremo de la lista con su punto rojo) · Más. Se agrupa por intención: *formatos → equipos → datos → ahora*.

### 2.2 Cómo se resuelve el cruce "equipo × formato"

| | Noticias | Videos | Datos | Mercado |
|---|---|---|---|---|
| **Junior** | atajo en Noticias y en Junior: **"Noticias de Junior"** → `noticias.html?tema=junior` | atajo en Videos y en Junior: **"Videos de Junior"** → `videos.html?tema=junior` | solo en Junior: **"Tabla de posiciones"** → `datos.html#posiciones` | **"Mercado de Junior"** → `mercado.html?equipo=junior` |
| **Selección** | **"Noticias de la Selección"** → `noticias.html?tema=seleccion` | **"Videos de la Selección"** → `videos.html?tema=seleccion` | **"Eliminatorias"** → `datos.html?t=eliminatorias` | — |

Cada celda es **un único enlace con un único nombre** que aparece en los dos "padres" que la reclaman. Quien busque por equipo o por formato la encuentra; nadie encuentra dos etiquetas para lo mismo.

### 2.3 La barra blanca contextual: **se fusiona** (desaparece como capa)

**Decisión.** Se elimina como barra independiente en escritorio y móvil. Sus enlaces se reparten así:

| Antes | Ahora |
|---|---|
| Ítems de la sección (Resumen, Noticias, Partidos…) | **Z1 del megamenú** de esa sección (visibles al pasar el mouse, antes de entrar). |
| Filtros y pestañas internas (`?tema=`, `#posiciones`, `?t=`) | **Fichas locales de página** (`nav` "En esta página", dentro del `main`, §2.4). |
| Título en cursiva ("Noticias", "Datos") | Desaparece. Lo sustituyen el ítem primario activo, el `h1` y las migas (profundidad ≥ 2). |
| Enlace "Suscríbete en YouTube" | Botón en el panel de Videos y en el módulo de video de las páginas. |

**Por qué:**
1. Con megamenús, la barra blanca **repetiría los mismos enlaces** en la pantalla: el principal causante de "no se entiende".
2. **Quita 48 px** de cabecera fija (160 → 112 px) y una jerarquía que competía.
3. Resuelve H8: el segundo nivel se ve **antes de entrar**, no solo cuando ya estás dentro.
4. Resuelve H12: en móvil no hay barra recortada ("Fút…").
5. El "¿dónde estoy?" lo cubren cuatro señales: primario activo con subrayado y `aria-current`, `h1`, migas y fichas locales.

**Plan B (si el dueño insiste en la barra blanca):** conservarla **solo en escritorio y solo en Datos, Noticias y Videos**, con los mismos ítems que las fichas locales de esa página y **sin título en cursiva**; y quitar de esos tres paneles el grupo repetido. No se recomienda: reintroduce la tercera capa.

### 2.4 Qué reemplaza la barra: fichas locales (en el contenido de cada página)

Un `nav aria-label="En esta página"` de chips, no fijo, justo bajo el `h1`. Todo lo que ya existe en el sitio generado:

| Página | Fichas locales | Estado hoy |
|---|---|---|
| `noticias` | Todas · Junior · Selección · Fútbol colombiano · Europa · Colombianos en el exterior · Opinión · **Mercado (→ `mercado.html`, ya no un filtro)** | Existen y filtran (`data-filter`); cambiar el destino de "Mercado". |
| `datos` | Hoy · Posiciones · Resultados · Goleadores · Estadísticas + chips de torneo (`#torneos`) | Existen. |
| `videos` | Todos · Junior · Selección + anclas Programas · Shorts | Chips por tema: **falta el filtro** (§5). |
| `junior` | Partidos · Plantilla · Noticias · Videos · Mercado (anclas y enlaces a las páginas de tema) | Añadir fila de chips. |
| `seleccion` | Convocatoria · Partidos · Noticias · Videos | Añadir fila de chips. |
| `en-vivo` | Parrilla · Programas anteriores · Participa | Existen (`t-parrilla`, `t-episodios`, `t-participa`). |
| `juan-salvador` | Columnas · Videos · En X · Pregúntale · Equipo | Existen (`02` T11). |
| `contacto` | Quiénes somos · Contacto · Publicidad | Anclas existen. |

### 2.5 Comportamiento del megamenú (escritorio ≥ 1024 px)

| Aspecto | Decisión |
|---|---|
| Apertura | **Solo hover** (decisión del dueño). Retardo de apertura **250 ms** (NN/g sugiere ~500 ms para evitar activaciones accidentales; se acorta porque es la única vía y la barra está llena de destinos). |
| Cambio entre ítems | **Inmediato (≤ 80 ms)** si ya hay un panel abierto: evita el parpadeo al recorrer la barra. |
| Cierre | **400 ms** tras salir del conjunto ítem + panel; **puente invisible** (panel pegado a la barra, sin hueco) o triángulo de seguridad para el movimiento en diagonal. Se cierra al hacer scroll. |
| Padres | Cada ítem es un `<a href>` real al hub. En el panel, la **primera línea es un enlace al hub** ("Todos los videos ›", "Ir a Junior FC ›"…). |
| Tamaño | Panel a ancho del contenedor (máx. 1200 px), **alto máximo ~420 px**, sin scroll interno. "Más" es un panel estrecho alineado a la derecha. |
| Qué no tapa | Solo se superpone al contenido bajo la barra mientras el mouse está encima. No se abre por foco ni al cargar. El contador de cuenta regresiva de la cabecera de Selección queda cubierto mientras dura el hover; no hay nada crítico fijo bajo la barra. |
| Movimiento | Opacidad (y 4 px de desplazamiento) en 200 ms; con `prefers-reduced-motion`, sin desplazamiento ni fundido. |
| Pantallas táctiles ≥ 1024 px | El primer toque en un ítem **navega a su hub** (no hay hover). Es seguro: el hub repite todo en sus fichas locales. |
| Teclado (recomendación, fuera del brief) | El dueño pidió quitar teclado y clic. Riesgo de accesibilidad conocido (`02` R4). Se recomienda, sin cambio visual, que `:focus-within` también abra el panel. Decisión pendiente del dueño. |

### 2.6 Esqueleto de panel (para dirección de arte y generador)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Z0  encabezado del panel: enlace al hub  ("Ir a Junior FC ›")               │
├───────────────────────────┬──────────────────────────┬──────────────────────┤
│ Z1  ENLACES  (5/12)       │ Z2  DESTACADO  (4/12)    │ Z3  DATO VIVO (3/12) │
│ 2 a 3 grupos con rótulo,  │ una pieza con imagen 16:9│ lista o tarjeta corta│
│ 2 a 6 enlaces por grupo   │ (o dato visual), titular │ que se actualiza     │
│                           │ debajo, nunca encima     │                      │
└───────────────────────────┴──────────────────────────┴──────────────────────┘
```

- **Z2:** imagen 16:9 (salvo Datos: datos visuales con escudos), esquinas rectas, titular **debajo** de la foto, una etiqueta y un metadato. Toda la tarjeta es **un solo enlace**; la imagen lleva `alt=""` (el titular nombra el enlace).
- **Z3:** módulo breve, de solo texto y escudos, o dos tarjetas verticales (Shorts). Pie "Datos de ejemplo · fuente por definir" cuando lleva cifras.
- **"Más"** usa solo Z1 (7/12) y Z2 (5/12).
- **Ninguna publicidad ni logo de aliado en el menú** (los huecos A/B/C y el muro de aliados ya son el inventario de pauta; Stake no debe asomar en la navegación; `06` D3).

---

## 3. Contenido exacto de cada megamenú

Convenciones: destino hoy = ruta del sitio generado. `★` = atajo: la **misma etiqueta y el mismo destino** aparecen también en otro panel.

### 3.1 Videos — `videos.html`

**Qué se destaca:** lo nuevo del programa (prioridad 1) y que existen los Shorts (prioridad 2), con una portada de video grande y dos verticales. **Se vende:** "hay programa nuevo y hay clips cortos".

**Z0:** "Todos los videos ›" → `videos.html`

**Z1 — Enlaces**

| Grupo | Etiqueta | Destino |
|---|---|---|
| Qué ver | Programas completos | `videos.html#programas` |
| | Shorts | `shorts.html` |
| Por tema | Videos de Junior ★ | `videos.html?tema=junior` |
| | Videos de la Selección ★ | `videos.html?tema=seleccion` |
| | Videos de Juan Salvador ★ | `juan-salvador.html#videos` |
| (botón) | Suscríbete en YouTube ↗ | `https://youtube.com/@habladeportesradio` (nueva pestaña, `rel="noopener"`) |

**Z2 — Destacado (video).** Fuente: `03` banco V1 y `video.html`.
- Etiqueta: **Último programa** (y el icono de play sobre la imagen)
- Titular: **Programa completo: la previa de Junior vs. Nacional y la lista de la Selección**
- Metadato: **1:51:20 · 6 nov**
- Imagen: `js-2` (16:9, `object-position` 60 % 35 %). Es el mismo video de `video.html`.
- Destino: `video.html`

**Z3 — Shorts** (encabezado "Shorts", enlace "Ver todos ›" → `shorts.html`). Dos tarjetas **9:16**, cada una → `shorts.html`:
1. **El gol de Pertuz, en 30 segundos** · 0:30 (S1) · imagen `junior-plantel-trofeo` (recorte vertical, 50 % 35 %)
2. **Tres datos de la Fecha 19 en menos de un minuto** · 0:48 (S5) · imagen `junior-escudo-bandera` (30 % 50 %)

### 3.2 Noticias — `noticias.html`

**Qué se destaca:** la nota del día (Junior, el centro) y **la amplitud de cobertura** que pidió el cliente (fútbol colombiano, Europa, colombianos afuera). **Se vende:** "aquí hay de todo, pero Junior primero".

**Z0:** "Todas las noticias ›" → `noticias.html`

**Z1 — Enlaces**

| Grupo | Etiqueta | Destino |
|---|---|---|
| Nuestro foco | Noticias de Junior ★ | `noticias.html?tema=junior` |
| | Noticias de la Selección ★ | `noticias.html?tema=seleccion` |
| Más fútbol | Fútbol colombiano | `noticias.html?tema=futbol-colombiano` |
| | Europa | `noticias.html?tema=europa` |
| | Colombianos en el exterior | `noticias.html?tema=colombianos-exterior` |
| Seguimiento y opinión | Mercado de fichajes ★ | `mercado.html` |
| | Opinión | `noticias.html?tema=opinion` |

**Z2 — Destacado (nota).** Fuente: `03` banco A1 y `articulo.html`.
- Etiqueta: **Junior · Análisis**
- Titular: **Rivas y Castro: el eje que sostiene al líder**
- Metadato: **5 min de lectura**
- Imagen: `junior-trofeo-bandera` (50 % 40 %)
- Destino: `articulo.html`

**Z3 — "Más cobertura"** (tres filas de texto con etiqueta de tema arriba y titular; sin imágenes: no hay fotos de Europa ni de colombianos afuera en el catálogo). Fuente: `data-ejemplo.json > noticias_por_tema.notas`.

| Etiqueta | Titular | Destino |
|---|---|---|
| Fútbol colombiano | Tolima y América se miran en la zona media: la Fecha 20 los empareja (`n-fc1`) | `noticias.html?tema=futbol-colombiano` |
| Europa | Un grande de Inglaterra pierde el liderato: tres razones del tropiezo (`n-eu1`) | `noticias.html?tema=europa` |
| Colombianos en el exterior | El extremo colombiano que gana minutos en un club de Portugal (`n-ex1`) | `noticias.html?tema=colombianos-exterior` |

Cada fila lleva a la lista filtrada, donde esa nota aparece primera: el destino es coherente con el titular.

### 3.3 Junior FC — `junior.html`

**Qué se destaca:** cómo va Junior hoy (partido) y una historia con foto. **Se vende:** "Junior al centro: marcador, crónica y todo el equipo".

**Z0:** "Ir a Junior FC ›" → `junior.html`

**Z1 — Enlaces**

| Grupo | Etiqueta | Destino |
|---|---|---|
| El equipo | Partidos de Junior | `junior.html#partidos` |
| | Plantilla de Junior | `junior.html#plantilla` |
| | Tabla de posiciones ★ | `datos.html#posiciones` |
| Lo último | Noticias de Junior ★ | `noticias.html?tema=junior` |
| | Videos de Junior ★ | `videos.html?tema=junior` |
| | Mercado de Junior | `mercado.html?equipo=junior` |

(Se quitan Goleadores y Estadísticas del contexto de Junior: son de toda la liga y viven en Datos.)

**Z2 — Destacado (nota).** Fuente: `03` P1 §1.4.
- Etiqueta: **Junior · Crónica**
- Titular: **El día que Junior se coronó bicampeón: la noche en tres escenas** (texto de ejemplo)
- Imagen: `junior-bicampeon` (50 % 45 %)
- Destino hoy: `articulo.html` (única nota de muestra; en producción, su propio post)

**Z3 — Partido de Junior** (tarjeta compacta, encabezado "Partido", todo → `partido.html`). Fuente: `data-ejemplo.json > partido_en_vivo` y `calendario_junior`.
- **Si hay partido en juego** (estado de ejemplo): chip rojo **En vivo · 67'** · `Junior 2–1 Nacional` con los dos escudos (`escudo-junior`, `escudo-nacional`) · "Liga BetPlay · Fecha 20" · "Estadio Metropolitano Roberto Meléndez · Win Sports" · botón **Seguir el partido ›**.
- **Si no hay partido:** chip gris **Próximo partido** · `Bucaramanga vs Junior` · "Sáb 21 nov · 6:10 p. m. · Win Sports" (calendario_junior.proximos[1]) · botón **Ver el partido ›** → `junior.html#partidos`.
- Línea inferior (ambos casos): "Racha: ● ● ○ ● ●" (GGEGG; G = azul, E = gris) y "Líder · 39 pts" (tabla_posiciones, `forma`). Nada en rojo.

### 3.4 Selección — `seleccion.html`

**Qué se destaca:** la convocatoria y el próximo partido. **Se vende:** "la Selección, partido a partido y lista por lista".

**Z0:** "Ir a la Selección ›" → `seleccion.html`

**Z1 — Enlaces**

| Grupo | Etiqueta | Destino |
|---|---|---|
| La Selección | Convocatoria | `seleccion.html#convocatoria` |
| | Partidos de la Selección | `seleccion.html#partidos` |
| | Eliminatorias | `datos.html?t=eliminatorias` |
| Lo último | Noticias de la Selección ★ | `noticias.html?tema=seleccion` |
| | Videos de la Selección ★ | `videos.html?tema=seleccion` |

**Z2 — Destacado (nota).** Fuente: `03` banco A2.
- Etiqueta: **Selección · Convocatoria**
- Titular: **La lista para Perú y Chile: cinco novedades y una ausencia que se discute**
- Metadato: **6 min de lectura**
- Imagen: `seleccion-once` (50 % 45 %)
- Destino hoy: `articulo.html` (nota de muestra)

**Z3 — Próximo partido** (tarjeta compacta, → `seleccion.html#partidos`). Fuente: `calendario_seleccion.proximos[0]`.
- Chip azul **Próximo partido** · `Colombia vs Perú` con `escudo-colombia` y `escudo-peru` · "Amistoso · Fecha FIFA de noviembre" · "Viernes 13 de noviembre · 7:30 p. m." · "Caracol TV · Estadio por confirmar (ejemplo)".
- Debajo, en una línea: "Después: Chile vs Colombia · mar 17 nov · 6:00 p. m.".

### 3.5 Datos — `datos.html`

**Qué se destaca:** la tabla (con Junior resaltado) y los partidos de hoy y mañana. **Se vende:** "la tabla y lo que se juega, sin entrar". Aquí la pieza destacada **es el dato**, no una foto: la imagen la ponen los escudos y los puntos de forma.

**Z0:** "Ir a Datos ›" → `datos.html`

**Z1 — Enlaces (2 columnas)**

| Grupo | Etiqueta | Destino |
|---|---|---|
| Hoy y tablas | Partidos de hoy | `datos.html#hoy` |
| | Partido en vivo (punto rojo si hay un partido de Junior o la Selección en juego; si no, la etiqueta pasa a **Último partido**) | `partido.html` |
| | Posiciones | `datos.html#posiciones` |
| | Resultados de la fecha | `datos.html#resultados` |
| | Goleadores | `datos.html#goleadores` |
| | Estadísticas | `datos.html#estadisticas` |
| Torneos | Liga BetPlay | `datos.html?t=liga` |
| | Copa Colombia | `datos.html?t=copa` |
| | Eliminatorias ★ | `datos.html?t=eliminatorias` |
| | Copa Libertadores | `datos.html?t=libertadores` |
| | Copa Sudamericana | `datos.html?t=sudamericana` |

**Z2 — Destacado (dato): clasificación.** Encabezado "Liga BetPlay · Finalización 2026 (ejemplo)". Fuente: `tabla_posiciones.filas[0..5]`.

| # | Equipo (con escudo) | PJ | Pts |
|---|---|---|---|
| 1 | Junior (fila resaltada en azul) | 19 | 39 |
| 2 | Nacional | 19 | 38 |
| 3 | Tolima | 19 | 35 |
| 4 | Millonarios | 19 | 33 |
| 5 | Medellín | 19 | 31 |
| 6 | América | 19 | 29 |

Escudos: los seis existen en el catálogo (`escudo-junior`, `-nacional`, `-tolima`, `-millonarios`, `-medellin`, `-america`). Pie: "Hasta la Fecha 19 · sin el partido en vivo · Datos de ejemplo · fuente por definir" y enlace "Ver la tabla completa ›" → `datos.html#posiciones`.

**Z3 — "Hoy y mañana"** (lista de 4 filas, cada una → `partido.html` la primera y `datos.html#hoy` las demás). Fuente: `resultados.filtro_dias`.

| Cuándo | Partido | Estado |
|---|---|---|
| Hoy | Junior 2–1 Nacional | chip rojo **En vivo · 67'** (→ `partido.html`) |
| Mañana | Pasto – Pereira · 2:00 p. m. | → `datos.html#hoy` |
| Mañana | Tolima – América · 4:00 p. m. | → `datos.html#hoy` |
| Mañana | Águilas D. – Alianza · 6:00 p. m. | → `datos.html#hoy` |

Sin partido en juego: "Hoy" muestra "Sin partidos de Liga hoy" en gris y las filas de mañana.

### 3.6 En vivo — `en-vivo.html`

**Qué se destaca:** el programa al aire (o el próximo) y lo que viene en la parrilla. **Se vende:** "mira el programa ahora, sin salir de la web".

**Z0:** "Ir a En vivo ›" → `en-vivo.html`

**Z1 — Enlaces**

| Grupo | Etiqueta | Destino |
|---|---|---|
| El programa | Parrilla de lunes a viernes | `en-vivo.html#parrilla` |
| | Participa en el programa | `en-vivo.html#participa` |
| Para ponerte al día | Programas anteriores | `en-vivo.html#episodios` |
| (botón) | Seguir el canal en YouTube ↗ | `https://youtube.com/@habladeportesradio` |

(Se omite "Escuchar solo el audio (por confirmar)": el menú no promete lo no confirmado.)

**Z2 — Destacado (programa en directo).** Fuente: `data-ejemplo.json > en_vivo`, `programa`, `03` P10.
- **Al aire:** chip rojo **Al aire** · "Lunes a viernes, 10:00 a. m. a 12:00 m." · botón **Ver en vivo**.
- **Fuera de horario:** chip gris **Próximo programa** · "Lunes a viernes, 10:00 a. m." · botón **Ver el último programa**.
- Titular: **El show de las mañanas, en vivo desde Barranquilla**
- Imagen: `js-1` (16:9, 60 % 35 %), con botón de play
- Destino: `en-vivo.html`

**Z3 — "Ahora y después"** (3 filas). Fuente: `programa.parrilla` y `partido_en_vivo`.
- **Ahora** · Apertura y titulares (10:00) → `en-vivo.html#parrilla`
- **Después** · Junior en primer plano (10:30) → `en-vivo.html#parrilla`
- **Partido en juego** (solo si lo hay; punto rojo) · Junior 2–1 Nacional · 67' → `partido.html`
- Fuera de horario: la fila "Ahora" se reemplaza por "Próximo programa · lunes a viernes, 10:00 a. m.".

### 3.7 Más — panel estrecho (alineado a la derecha)

**Qué se destaca:** el rostro del programa. **Se vende:** "conoce a quien está detrás del micrófono".

**Z1 — Enlaces (7/12)**

| Grupo | Etiqueta | Destino |
|---|---|---|
| Habla Deportes | Quiénes somos | `contacto.html#nosotros` |
| | Contacto | `contacto.html#contacto` |
| | Publicidad y patrocinios | `contacto.html#publicidad` |
| | Nuestros aliados | `#pie-aliados` |
| | Redes | `redes.html` |
| | Legales | `legales.html` |

Debajo, fila de cuatro iconos de 44 px (YouTube, Instagram, X, Facebook) con los enlaces de `REDES`.

**Z2 — Destacado (perfil) (5/12).**
- Etiqueta: **El programa**
- Nombre: **Juan Salvador Bárcena** (como talento del medio; sin citas ni datos personales, `03` §0)
- Texto: **Presentador y creador de contenido**
- Imagen: `js-2` (1:1 o 4:5, 60 % 35 %)
- Destino: `juan-salvador.html`
- Tres enlaces cortos bajo la tarjeta: **Videos de Juan Salvador** ★ (`juan-salvador.html#videos`) · **Su timeline en X** (`juan-salvador.html#x`) · **Pregúntale a Juan** (`juan-salvador.html#pregunta`)

### 3.8 Verificación de las reglas

| Regla | Cumplida |
|---|---|
| Etiqueta única ⇒ destino único | Sí. Las ★ repiten etiqueta **y** destino. "Mercado de fichajes" → `mercado.html` en todas partes; "Mercado de Junior" es otra etiqueta y otro destino. |
| ≤ 6 primarios + Más | Sí (6 + Más). |
| Enlaces por panel | Videos 5, Noticias 7, Junior 6, Selección 5, Datos 11, En vivo 4, Más 6 (+ 3 del perfil). |
| Cada primario es un enlace real | Sí; además Z0 repite el hub dentro del panel. |
| Sin escudos como atajo a equipos | Sí. Los escudos solo están en el partido de Junior, el próximo de la Selección, la tabla y la lista de partidos de Datos. |
| Ninguna foto prohibida | Se usan `js-1`, `js-2`, `junior-plantel-trofeo`, `junior-escudo-bandera`, `junior-trofeo-bandera`, `junior-bicampeon`, `seleccion-once`. No se usan fondos de partido (banderas), anuncio, ni las dos fotos excluidas del catálogo (Messi, Mbappé). |

**Fotos nuevas necesarias:** ninguna para el prototipo. Deseables a futuro: una imagen para Europa y otra para Colombianos en el exterior (hoy el catálogo no tiene; por eso Z3 de Noticias es de texto), y una foto vertical propia de Shorts (hoy se recortan fotos horizontales).

---

## 4. Menú móvil (< 1024 px)

Sin hover: **la hamburguesa abre una hoja de pantalla completa** (desde la cabecera hasta la barra inferior, que queda visible). Reemplaza a la barra azul de secciones y a la barra blanca (ambas se ocultan en móvil).

### 4.1 Cabecera móvil (56 px)

Logo · buscar · menú (hamburguesa que pasa a "X"). **Al abrirse la hoja:** el botón cambia su etiqueta a "Cerrar el menú", `aria-expanded="true"`, el foco entra a la hoja y `body` no hace scroll. **Corregir:** en la prueba del 6-nov la hamburguesa no abría nada; el generador debe revisar el enlace `data-menu-toggle` ↔ `#menu-movil` y que `hidden` se retire.

### 4.2 Orden de la hoja (de arriba abajo)

| # | Bloque | Contenido | Por qué aquí |
|---|---|---|---|
| 1 | **Buscador** | Campo visible con etiqueta "Buscar en Habla Deportes" (no solo icono) + 4 chips sugeridos (Junior · Selección · Mercado · Europa → `buscar.html?q=…`). | El móvil depende de la búsqueda; NN/g desaconseja esconderla. |
| 2 | **Tarjeta "Ahora"** | La **única imagen** de la hoja: miniatura 16:9 de 96 px (`js-1`) + chip (rojo "Al aire" o gris "Próximo programa · lunes a viernes, 10:00 a. m.") + "El show de las mañanas" + "Ver en vivo ›" → `en-vivo.html`. Si hay partido de Junior o Selección en juego, una segunda fila: "Junior 2–1 Nacional · 67'" → `partido.html`. | Lo más perecedero va primero; es el gancho visual del menú. |
| 3 | **Seis filas primarias** (56 px) | Videos · Noticias · Junior FC · Selección · Datos · En vivo. Cada fila: **etiqueta = enlace al hub** + **botón de 44 × 44 con chevron** que abre un acordeón con los enlaces de Z1 de ese panel (con sus rótulos de grupo en texto pequeño). Un solo acordeón abierto a la vez; **se abre por defecto el de la sección actual**. | Mismos nombres y orden que escritorio. |
| 4 | **Más** | Filas sencillas de 48 px: Juan Salvador · Redes · Quiénes somos · Contacto y publicidad · Nuestros aliados · Legales. | Lo secundario, sin acordeón. |
| 5 | **Redes** | Iconos de 44 px: YouTube · Instagram · X · Facebook. | |

**Se oculta en móvil:** Z2 y Z3 de cada panel (piezas destacadas y datos vivos), salvo la tarjeta "Ahora". Esos módulos ya están en la portada y en los hubs. El acordeón de Datos (11 enlaces) usa los dos rótulos "Hoy y tablas" y "Torneos".

### 4.3 Barra inferior (5 pestañas, 56 px + área segura)

| Pestaña | Destino | Activa en | Notas |
|---|---|---|---|
| **Inicio** | `index.html` | `index` | Icono nuevo `house` (y `house-fill`) en el sprite. Segundo toque estando en la portada sube al inicio. |
| **Videos** | `videos.html` | `videos`, `shorts`, `video` | |
| **Noticias** | `noticias.html` | `noticias`, `articulo`, `mercado`, `junior`, `seleccion` | Antes llevaba a la portada. Las fichas de tema (Junior, Selección…) están a un toque dentro de la página. |
| **Datos** | `datos.html` | `datos`, `partido` | Punto rojo si hay partido de Junior o Selección en juego. |
| **En vivo** | `en-vivo.html` | `en-vivo` | Punto rojo si el programa está al aire. |

Cinco pestañas a 360 px son 72 px cada una, sobre el mínimo táctil de 44 px; son el máximo recomendado (`ui-ux-pro-max`: bottom nav ≤ 5). **Plan B** si el dueño prefiere cuatro: Inicio · Videos · Datos · En vivo (Noticias queda en la hoja). Páginas sin pestaña activa: `juan-salvador`, `redes`, `contacto`, `legales`, `buscar`, `404`.

---

## 5. Destinos y páginas: qué existe y qué falta

**No hace falta crear ninguna página.** Las 18 existen (5 completas, 13 provisionales según el brief). Lo que sí conviene hacer:

| Destino | Estado hoy | Qué usar hoy / qué hacer |
|---|---|---|
| `noticias.html?tema=` (8 valores) | **Funciona** (`data-filter`, JS) | Nada. Cambiar el chip "Mercado" para que vaya a `mercado.html` y no filtre. |
| `datos.html?t=` (5 valores) | **Funciona parcialmente**: marca el chip y avisa que solo hay datos de Liga | Nada; sigue la advertencia de ejemplo. |
| `videos.html?tema=junior\|seleccion` | **Degrada:** la página carga completa; no hay `data-filter` | Funciona como enlace. Para cumplir la promesa: reutilizar el patrón `data-filterable` de `noticias.html` en las tarjetas de `videos.html` (cada tarjeta con `data-tema`). |
| `mercado.html?equipo=junior` | **Degrada:** carga completa | Igual: filtro `?equipo=` por JS (`data-equipo` en cada fila). Hasta entonces el enlace "Mercado de Junior" lleva a la lista completa. |
| `mercado.html?estado=` | No lo usa el menú | Mantener como en `02`. |
| `shorts.html` | Existe | Sin filtros (no se prometen). |
| Anclas `#partidos`, `#plantilla` (Junior); `#convocatoria`, `#partidos` (Selección); `#programas` (Videos); `#hoy #posiciones #resultados #goleadores #estadisticas #torneos` (Datos); `#parrilla #episodios #participa` (En vivo); `#nosotros #contacto #publicidad` (Contacto); `#videos #x #pregunta` (Juan Salvador); `#pie-aliados`, `#pie-enlaces` (pie de toda página) | **Todas existen** en `propuesta/html/nueva/` | Nada. |
| `articulo.html`, `video.html`, `partido.html` | Una sola instancia de muestra (A1, V1, Junior–Nacional) | A1 → `articulo.html` y V1 → `video.html` **coinciden** con su titular. La crónica de Junior y A2 (Selección) apuntan hoy a `articulo.html` aunque el titular difiera; en producción, a su post. |
| Iconos | `house` y `house-fill` faltan en el sprite | Añadir (Phosphor, mismo trazo). |
| Fichas locales (`nav` "En esta página") | Existen en Noticias, Datos, En vivo, Juan Salvador; faltan en Junior y Selección | Añadir fila de chips en Junior y Selección (§2.4). |

**Contenido duplicado (nota, no bloquea):** "Programas completos" (Videos) y "Programas anteriores" (En vivo) son hoy los mismos 8 videos. Recomendado: que `en-vivo.html#episodios` muestre los 4 últimos y enlace a `videos.html#programas`.

---

## 6. Cómo se marca "en vivo", y qué pasa con Mercado y Opinión

### 6.1 "En vivo"

Un único componente (punto rojo + texto "En vivo"/"Al aire") y un solo color (`02` §7.6). El punto cae **en el ítem cuya página contiene lo que está en vivo**, para que el destino cumpla la promesa:

| Señal | Se enciende cuando | Dónde se ve | Lector de pantalla |
|---|---|---|---|
| Programa al aire (`data-aire="si"`) | Lunes a viernes, 10:00 a. m. a 12:00 m. (o campo manual) | Punto en **En vivo** (barra azul y pestaña inferior); chip "Al aire" en Z2 de En vivo y en la tarjeta "Ahora" móvil. | ", al aire ahora" |
| Partido de Junior o Selección en juego | Estado `en_vivo` del partido | Punto en **Datos** (barra azul y pestaña inferior); chip "En vivo · minuto" en Z3 de Junior, Z3 de Datos y Z3 de En vivo; punto junto a "Partido en vivo" en Z1 de Datos. | ", partido en vivo" |
| Otros partidos de la liga | — | Solo chip dentro de Datos. **No** encienden el punto de la barra (si no, siempre estaría encendido). | — |

Reglas: máximo **dos puntos** a la vez en la barra (programa + partido); "en vivo" nunca significa "reciente"; sin datos, "Sin datos en este momento" en gris, nunca rojo. Si los dos coinciden, el panel de En vivo pone el programa en Z2 y el partido en Z3.

### 6.2 Mercado

- **Etiqueta única:** "**Mercado de fichajes**" → `mercado.html`, en el panel de Noticias (grupo "Seguimiento y opinión"), en el pie y en las fichas de `noticias`. "**Mercado de Junior**" → `mercado.html?equipo=junior`, en el panel de Junior.
- **Sale de "Más"** y de la barra contextual de Noticias. El filtro `noticias.html?tema=mercado` sigue vivo (para etiquetar notas) pero ningún menú lo enlaza.
- **No tiene primario propio**: son 8 movimientos de ejemplo y un solo hub de página; no justifica un séptimo ítem.

### 6.3 Opinión

- **"Opinión" → `noticias.html?tema=opinion`** (el índice de columnas). Está en el panel de Noticias.
- **Juan Salvador** deja de llamarse "Opinión": su perfil (`juan-salvador.html`) se llama **Juan Salvador** en "Más" y como tarjeta en su Z2. Sus columnas siguen en `juan-salvador.html#columnas` y aparecen también en el índice de Opinión.
- Si llegan más columnistas, "Opinión" puede pasar a página propia (`02` R11) sin cambiar la etiqueta.

---

## 7. Mapa para el desarrollador (`sitio.py`)

Sustituye `PRIMARIO`, `MAS`, `CONTEXTO`, `INFERIOR` y `MENU_MOVIL`. `PAGINAS` pierde `seccion` y `activo`; conserva `primario` e `inferior`. Los textos y rutas son **los de arriba, sin cambios**. El generador puede leer este YAML tal cual.

```yaml
menu:
  version: 1
  contexto: eliminado            # ya no hay barra blanca; ver fichas_locales
  hover: {abrir_ms: 250, entre_items_ms: 0, cerrar_ms: 400, puente_invisible: true, cierra_al_scroll: true}
  primario:                      # orden de izquierda a derecha
    - {clave: videos,    texto: Videos,     href: videos.html,    panel: videos}
    - {clave: noticias,  texto: Noticias,   href: noticias.html,  panel: noticias}
    - {clave: junior,    texto: Junior FC,  href: junior.html,    panel: junior}
    - {clave: seleccion, texto: Selección,  href: seleccion.html, panel: seleccion}
    - {clave: datos,     texto: Datos,      href: datos.html,     panel: datos,   punto_rojo: partido_junior_o_seleccion}
    - {clave: en-vivo,   texto: En vivo,    href: en-vivo.html,   panel: en-vivo, punto_rojo: programa_al_aire}
    - {clave: mas,       texto: Más,        href: "#pie-enlaces", panel: mas,     ancho: estrecho_derecha}

  # pagina -> primario activo (aria-current) y pestaña inferior
  paginas:
    index:         {primario: null,      inferior: inicio}
    videos:        {primario: videos,    inferior: videos}
    shorts:        {primario: videos,    inferior: videos}
    video:         {primario: videos,    inferior: videos}
    noticias:      {primario: noticias,  inferior: noticias}
    articulo:      {primario: noticias,  inferior: noticias}
    mercado:       {primario: noticias,  inferior: noticias}
    junior:        {primario: junior,    inferior: noticias}
    seleccion:     {primario: seleccion, inferior: noticias}
    datos:         {primario: datos,     inferior: datos}
    partido:       {primario: datos,     inferior: datos}
    en-vivo:       {primario: en-vivo,   inferior: en-vivo}
    juan-salvador: {primario: mas,       inferior: null}
    redes:         {primario: mas,       inferior: null}
    contacto:      {primario: mas,       inferior: null}
    legales:       {primario: mas,       inferior: null}
    buscar:        {primario: null,      inferior: null}
    "404":         {primario: null,      inferior: null}

  inferior:                      # barra móvil, 5 pestañas
    - {clave: inicio,   texto: Inicio,   href: index.html,    icono: house}
    - {clave: videos,   texto: Videos,   href: videos.html,   icono: monitor-play}
    - {clave: noticias, texto: Noticias, href: noticias.html, icono: newspaper}
    - {clave: datos,    texto: Datos,    href: datos.html,    icono: chart-bar,  punto_rojo: partido_junior_o_seleccion}
    - {clave: en-vivo,  texto: En vivo,  href: en-vivo.html,  icono: broadcast, punto_rojo: programa_al_aire}

  paneles:
    videos:
      z0: {texto: "Todos los videos", href: videos.html}
      z1:
        - grupo: Qué ver
          enlaces:
            - {t: Programas completos, h: "videos.html#programas"}
            - {t: Shorts,              h: shorts.html}
        - grupo: Por tema
          enlaces:
            - {t: Videos de Junior,          h: "videos.html?tema=junior"}
            - {t: Videos de la Selección,    h: "videos.html?tema=seleccion"}
            - {t: Videos de Juan Salvador,   h: "juan-salvador.html#videos"}
        - boton: {t: Suscríbete en YouTube, h: "https://youtube.com/@habladeportesradio", externo: true}
      z2:
        tipo: video
        etiqueta: Último programa
        titular: "Programa completo: la previa de Junior vs. Nacional y la lista de la Selección"
        meta: "1:51:20 · 6 nov"
        imagen: {clave: js-2, aspecto: "16:9", pos: "60% 35%", alt: ""}
        href: video.html
      z3:
        tipo: shorts
        titulo: {t: Shorts, h: shorts.html, enlace: "Ver todos ›"}
        piezas:
          - {titular: "El gol de Pertuz, en 30 segundos",               duracion: "0:30", imagen: {clave: junior-plantel-trofeo, aspecto: "9:16", pos: "50% 35%", alt: ""}, href: shorts.html}
          - {titular: "Tres datos de la Fecha 19 en menos de un minuto", duracion: "0:48", imagen: {clave: junior-escudo-bandera, aspecto: "9:16", pos: "30% 50%", alt: ""}, href: shorts.html}

    noticias:
      z0: {texto: "Todas las noticias", href: noticias.html}
      z1:
        - grupo: Nuestro foco
          enlaces:
            - {t: Noticias de Junior,       h: "noticias.html?tema=junior"}
            - {t: Noticias de la Selección, h: "noticias.html?tema=seleccion"}
        - grupo: Más fútbol
          enlaces:
            - {t: Fútbol colombiano,         h: "noticias.html?tema=futbol-colombiano"}
            - {t: Europa,                    h: "noticias.html?tema=europa"}
            - {t: Colombianos en el exterior, h: "noticias.html?tema=colombianos-exterior"}
        - grupo: Seguimiento y opinión
          enlaces:
            - {t: Mercado de fichajes, h: mercado.html}
            - {t: Opinión,             h: "noticias.html?tema=opinion"}
      z2:
        tipo: nota
        etiqueta: "Junior · Análisis"
        titular: "Rivas y Castro: el eje que sostiene al líder"
        meta: "5 min de lectura"
        imagen: {clave: junior-trofeo-bandera, aspecto: "16:9", pos: "50% 40%", alt: ""}
        href: articulo.html
      z3:
        tipo: titulares_texto
        titulo: Más cobertura
        filas:        # fuente: data-ejemplo.json > noticias_por_tema.notas
          - {etiqueta: Fútbol colombiano,          id: n-fc1, href: "noticias.html?tema=futbol-colombiano"}
          - {etiqueta: Europa,                     id: n-eu1, href: "noticias.html?tema=europa"}
          - {etiqueta: Colombianos en el exterior, id: n-ex1, href: "noticias.html?tema=colombianos-exterior"}

    junior:
      z0: {texto: "Ir a Junior FC", href: junior.html}
      z1:
        - grupo: El equipo
          enlaces:
            - {t: Partidos de Junior,   h: "junior.html#partidos"}
            - {t: Plantilla de Junior,  h: "junior.html#plantilla"}
            - {t: Tabla de posiciones,  h: "datos.html#posiciones"}
        - grupo: Lo último
          enlaces:
            - {t: Noticias de Junior,   h: "noticias.html?tema=junior"}
            - {t: Videos de Junior,     h: "videos.html?tema=junior"}
            - {t: Mercado de Junior,    h: "mercado.html?equipo=junior"}
      z2:
        tipo: nota
        etiqueta: "Junior · Crónica"
        titular: "El día que Junior se coronó bicampeón: la noche en tres escenas"
        meta: null
        imagen: {clave: junior-bicampeon, aspecto: "16:9", pos: "50% 45%", alt: ""}
        href: articulo.html          # hoy; producción: su propio post
      z3:
        tipo: partido_compacto
        titulo: Partido
        fuente: {en_juego: partido_en_vivo, proximo: "calendario_junior.proximos[1]", racha: "tabla_posiciones.filas[0].forma"}
        escudos: [escudo-junior, escudo-nacional]
        href: partido.html
        sin_partido: {chip: Próximo partido, texto: "Bucaramanga vs Junior · sáb 21 nov · 6:10 p. m. · Win Sports", href: "junior.html#partidos"}
        linea_extra: "Racha de cinco · Líder · 39 pts"

    seleccion:
      z0: {texto: "Ir a la Selección", href: seleccion.html}
      z1:
        - grupo: La Selección
          enlaces:
            - {t: Convocatoria,             h: "seleccion.html#convocatoria"}
            - {t: Partidos de la Selección, h: "seleccion.html#partidos"}
            - {t: Eliminatorias,            h: "datos.html?t=eliminatorias"}
        - grupo: Lo último
          enlaces:
            - {t: Noticias de la Selección, h: "noticias.html?tema=seleccion"}
            - {t: Videos de la Selección,   h: "videos.html?tema=seleccion"}
      z2:
        tipo: nota
        etiqueta: "Selección · Convocatoria"
        titular: "La lista para Perú y Chile: cinco novedades y una ausencia que se discute"
        meta: "6 min de lectura"
        imagen: {clave: seleccion-once, aspecto: "16:9", pos: "50% 45%", alt: ""}
        href: articulo.html          # hoy; producción: su propio post
      z3:
        tipo: proximo_partido
        titulo: Próximo partido
        fuente: "calendario_seleccion.proximos[0]"
        escudos: [escudo-colombia, escudo-peru]
        texto: ["Colombia vs Perú", "Amistoso · Fecha FIFA de noviembre", "Viernes 13 de noviembre · 7:30 p. m.", "Caracol TV · Estadio por confirmar (ejemplo)"]
        despues: "Chile vs Colombia · mar 17 nov · 6:00 p. m."
        href: "seleccion.html#partidos"

    datos:
      z0: {texto: "Ir a Datos", href: datos.html}
      z1:
        - grupo: Hoy y tablas
          enlaces:
            - {t: Partidos de hoy,       h: "datos.html#hoy"}
            - {t: Partido en vivo,       h: partido.html, punto_rojo: partido_junior_o_seleccion, etiqueta_sin_vivo: Último partido}
            - {t: Posiciones,            h: "datos.html#posiciones"}
            - {t: Resultados de la fecha, h: "datos.html#resultados"}
            - {t: Goleadores,            h: "datos.html#goleadores"}
            - {t: Estadísticas,          h: "datos.html#estadisticas"}
        - grupo: Torneos
          enlaces:
            - {t: Liga BetPlay,         h: "datos.html?t=liga"}
            - {t: Copa Colombia,        h: "datos.html?t=copa"}
            - {t: Eliminatorias,        h: "datos.html?t=eliminatorias"}
            - {t: Copa Libertadores,    h: "datos.html?t=libertadores"}
            - {t: Copa Sudamericana,    h: "datos.html?t=sudamericana"}
      z2:
        tipo: tabla
        titulo: "Liga BetPlay · Finalización 2026 (ejemplo)"
        fuente: "tabla_posiciones.filas[0:6]"     # pos, escudo, equipo, pj, pts; resaltar junior
        columnas: [pos, equipo, pj, pts]
        pie: "Hasta la Fecha 19 · sin el partido en vivo · Datos de ejemplo · fuente por definir"
        enlace: {t: "Ver la tabla completa ›", h: "datos.html#posiciones"}
      z3:
        tipo: lista_partidos
        titulo: Hoy y mañana
        fuente: "resultados.filtro_dias"           # hoy: junior-nacional; manana: pasto-pereira, tolima-america, aguilas-alianza
        filas:
          - {cuando: Hoy,    partido: "Junior 2–1 Nacional", chip_rojo: "En vivo · 67'", href: partido.html}
          - {cuando: Mañana, partido: "Pasto – Pereira",     hora: "2:00 p. m.", href: "datos.html#hoy"}
          - {cuando: Mañana, partido: "Tolima – América",    hora: "4:00 p. m.", href: "datos.html#hoy"}
          - {cuando: Mañana, partido: "Águilas D. – Alianza", hora: "6:00 p. m.", href: "datos.html#hoy"}
        sin_partido_hoy: "Sin partidos de Liga hoy"

    en-vivo:
      z0: {texto: "Ir a En vivo", href: en-vivo.html}
      z1:
        - grupo: El programa
          enlaces:
            - {t: Parrilla de lunes a viernes, h: "en-vivo.html#parrilla"}
            - {t: Participa en el programa,    h: "en-vivo.html#participa"}
        - grupo: Para ponerte al día
          enlaces:
            - {t: Programas anteriores, h: "en-vivo.html#episodios"}
        - boton: {t: Seguir el canal en YouTube, h: "https://youtube.com/@habladeportesradio", externo: true}
      z2:
        tipo: programa_en_directo
        titular: "El show de las mañanas, en vivo desde Barranquilla"
        imagen: {clave: js-1, aspecto: "16:9", pos: "60% 35%", alt: ""}
        con_play: true
        al_aire:     {chip_rojo: Al aire, texto: "Lunes a viernes, 10:00 a. m. a 12:00 m.", boton: Ver en vivo}
        fuera_aire:  {chip_gris: Próximo programa, texto: "Lunes a viernes, 10:00 a. m.", boton: Ver el último programa}
        href: en-vivo.html
      z3:
        tipo: ahora_y_despues
        titulo: Ahora y después
        fuente: "programa.parrilla"
        filas:
          - {rotulo: Ahora,   texto: "Apertura y titulares (10:00)",    href: "en-vivo.html#parrilla"}
          - {rotulo: Después, texto: "Junior en primer plano (10:30)",  href: "en-vivo.html#parrilla"}
          - {rotulo: Partido en juego, texto: "Junior 2–1 Nacional · 67'", href: partido.html, solo_si: partido_en_juego, punto_rojo: true}
        fuera_aire: "Próximo programa · lunes a viernes, 10:00 a. m."

    mas:
      ancho: estrecho_derecha
      z1:
        - grupo: Habla Deportes
          enlaces:
            - {t: Quiénes somos,           h: "contacto.html#nosotros"}
            - {t: Contacto,                h: "contacto.html#contacto"}
            - {t: Publicidad y patrocinios, h: "contacto.html#publicidad"}
            - {t: Nuestros aliados,        h: "#pie-aliados"}
            - {t: Redes,                   h: redes.html}
            - {t: Legales,                 h: legales.html}
        - redes_iconos: [youtube, instagram, x, facebook]     # desde REDES
      z2:
        tipo: perfil
        etiqueta: El programa
        titular: Juan Salvador Bárcena
        meta: Presentador y creador de contenido
        imagen: {clave: js-2, aspecto: "4:5", pos: "60% 35%", alt: ""}
        href: juan-salvador.html
        enlaces:
          - {t: Videos de Juan Salvador, h: "juan-salvador.html#videos"}
          - {t: Su timeline en X,        h: "juan-salvador.html#x"}
          - {t: Pregúntale a Juan,       h: "juan-salvador.html#pregunta"}

  movil:
    cabecera: [logo, buscar, hamburguesa]
    hoja:                           # pantalla completa; deja visible la barra inferior
      - bloque: buscador            # campo visible + chips: Junior, Selección, Mercado, Europa
      - bloque: tarjeta_ahora       # miniatura js-1 96px + chip + "Ver en vivo ›"; fila de partido si está en juego
      - bloque: primarios           # mismas 6 filas y orden que escritorio
        fila: {etiqueta_es_enlace: true, boton_acordeon_px: 44, un_acordeon_abierto: true, abrir_el_de_la_pagina_actual: true}
        acordeon: z1                # los grupos y enlaces de cada panel
      - bloque: mas                 # Juan Salvador, Redes, Quiénes somos, Contacto y publicidad, Nuestros aliados, Legales
      - bloque: redes_iconos
    oculta: [z2, z3]                # excepto la tarjeta "Ahora"

  fichas_locales:                   # reemplazo de la barra blanca; <nav aria-label="En esta página"> dentro de <main>
    noticias:      [Todas, Junior, Selección, Fútbol colombiano, Europa, Colombianos en el exterior, Opinión, "Mercado → mercado.html"]
    datos:         [Hoy, Posiciones, Resultados, Goleadores, Estadísticas, "chips de torneo"]
    videos:        [Todos, Junior, Selección, "Programas completos", Shorts]
    junior:        [Partidos, Plantilla, "Noticias → noticias.html?tema=junior", "Videos → videos.html?tema=junior", "Mercado → mercado.html?equipo=junior"]
    seleccion:     [Convocatoria, Partidos, "Noticias → noticias.html?tema=seleccion", "Videos → videos.html?tema=seleccion"]
    en-vivo:       [Parrilla, Programas anteriores, Participa]
    juan-salvador: [Columnas, Videos, "En X", Pregúntale, Equipo]
    contacto:      [Quiénes somos, Contacto, Publicidad]

  pie:                              # ajustes para respetar "una etiqueta, un destino"
    cambios:
      - {columna: Noticias, "Opinión": "noticias.html?tema=opinion"}      # antes juan-salvador.html
      - {columna: Habla Deportes, añadir: {t: Juan Salvador, h: juan-salvador.html}}
```

### Notas de implementación

- **Marcado:** `<nav aria-label="Secciones">` > `li` > `a[href]` + `div.mega` (hermano del enlace, no hijo, para que el enlace sea un solo nombre accesible). Dentro, `h2` no (el `h1` de la página es único): usa `p` con rol visual de rótulo. Los rótulos de grupo de Z1 son `p`/`span`, no encabezados.
- **Imágenes del panel:** `<img width height loading="lazy" decoding="async" alt="">` con las dimensiones reales del catálogo (`imagenes.dims(clave)`). Un panel oculto con `display:none` no descarga imágenes perezosas hasta abrirse: se recomienda cambiar a `loading="eager"` en el primer `pointerenter` de la barra para evitar el "salto" del primer hover.
- **Estados vivos:** reutilizar `data-aire` y las clases `.only-aire` ya existentes para el programa; añadir un indicador equivalente para el partido (p. ej. `data-partido="en-juego"`) que lea el mismo valor que el banner de partidos.
- **Un único origen:** el pie, el menú móvil y los paneles salen del mismo diccionario (`sitio.py`); el generador valida que **no exista la misma etiqueta con dos destinos** (comprobación automática barata que evita la regresión de H1).

---

## 8. Dudas para el dueño

1. **"Datos" como etiqueta:** se mantiene por ser la palabra del cliente y de la barra inferior; el panel pone la pista (la tabla). Alternativa: "Tabla y resultados" (más descriptiva, más larga).
2. **Eliminar la barra blanca** contradice la decisión 12 de `00`. Plan B en §2.3.
3. **5 pestañas inferiores** frente a las 4 del brief. Plan B en §4.3.
4. **Hover y teclado:** abrir también con `:focus-within` (sin impacto visual). Recomendado.
5. **El punto de "Datos"** solo con Junior o Selección en juego: confirmar que es lo que quiere ver el dueño.

---

## Fuentes

- NN/g, [Mega Menus Work Well for Site Navigation](https://www.nngroup.com/articles/mega-menus-work-well/): muestran varios niveles a la vez; pausa de ~0,5 s antes de abrir con hover.
- NN/g, [Menu-Design Checklist: 17 UX Guidelines](https://www.nngroup.com/articles/menu-design/): etiquetas descriptivas y concisas; imágenes como complemento; aperturas y cierres accidentales frustran.
- Resultados secundarios sobre estructura de ESPN y Sky Sports (subnavegación de fútbol con "Scores, Fixtures, Transfers, League & Cups, Teams, Tables"; "More Sports"). Las cifras de enlaces por panel de blogs de terceros no se tratan como hechos.
- Skills del proyecto (solo lectura): `impeccable/reference/clarify.md`, `ui-ux-pro-max/SKILL.md`, `mobile-native/SKILL.md`; y `02-arquitectura.md` §3 (navegación previa).
