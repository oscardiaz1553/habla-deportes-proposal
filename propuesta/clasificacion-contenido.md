# Clasificación de contenido — Video vs. Nota vs. Fichaje vs. Radio

**Proyecto:** Rediseño Habla Deportes
**Agente:** `arquitecto-contenido`
**Fecha:** 2026-07-16
**Problema a resolver:** hoy el sitio no diferencia con claridad, de un vistazo, si una tarjeta es un VIDEO que se reproduce o una NOTA que se lee. La mezcla es inconsistente entre secciones y hay al menos un caso donde el marcador visual miente sobre el destino real del clic.
**Método:** inspección de `propuesta/html/index.html`, `seccion.html`, `articulo.html`, `video.html` y de `assets/css/main.css` / `assets/js/home.js` (lógica real del lightbox).
**Alcance:** este documento define el sistema y las reglas. No implementa CSS/HTML — eso es tarea de `desarrollador-frontend`.

---

## 0. Hallazgo clave

El sitio **ya tiene** un sistema de badges de tipo bien resuelto — `.vcard__type` (Video / Nota / Fichaje) — pero **solo vive en `seccion.html`**. La Home (`index.html`) no lo usa en ninguna de sus secciones y depende únicamente de la presencia o ausencia de un botón de play como señal de tipo. Esa señal es frágil: en un punto del código ya falla (ver corrección C1), y además es insuficiente para distinguir Nota de Fichaje, que hoy se ven exactamente igual en Home.

**La solución no es inventar un sistema nuevo: es extender a toda la Home el sistema que `seccion.html` ya define, y corregir los puntos donde el marcador actual contradice el destino real del clic.**

---

## 1. Tipos de contenido y regla de identidad

| Tipo | Qué es | Regla de identidad (cómo se sabe qué es, en una tarjeta) |
|---|---|---|
| **Video (YouTube)** | Cualquier pieza cuyo consumo es "verla": videos del canal, clips cortos, episodios de radio grabados. | Tiene **botón de play** sobre la miniatura. Punto no negociable: **si no se reproduce, no lleva botón de play.** |
| **Nota escrita** | Artículo de texto (análisis, crónica, reacción) con imagen de apoyo. | **Nunca** lleva botón de play. Lleva indicador de lectura (badge "NOTA" y/o tiempo de lectura) y autor. |
| **Fichaje / dato** | Movimiento de mercado: fecha + escudo origen → escudo destino + texto corto. | Formato propio (escudo→escudo), sin foto de miniatura. Cuando aparece en una grilla mixta (tipo `seccion.html`), badge rojo "FICHAJE". |
| **Radio / Episodio** | Grabación del programa en vivo, presentada como audio/podcast, no como "video de YouTube" aunque técnicamente viva en YouTube. | Lista (no grilla de miniaturas), botón de play **sólido navy** (no el ghost-button blanco de video) + ícono de onda de audio + duración larga (hh:mm:ss). |

> Nota sobre Radio/Episodio: técnicamente es un Video (se reproduce en el mismo lightbox de YouTube), pero editorialmente se presenta como audio para no competir visualmente con "El show de la mañana" / Clips. Es la única excepción a "todo lo que se reproduce lleva el play-btn blanco genérico": aquí se usa un play-btn distinto a propósito, y esa diferencia de color/forma ES el marcador de "esto es radio, no video de reacción".

---

## 2. Marcadores visuales por tipo (qué construir, reusando lo que ya existe)

### 2.1 Video (YouTube)
- **Botón de play** blanco translúcido (`.play-btn`, ya existe) centrado sobre la miniatura — obligatorio en toda tarjeta de video, sin excepción.
- **Badge de tipo**, esquina superior izquierda de la miniatura: ícono de play + texto "VIDEO" (patrón `.vcard__type` de `seccion.html`, líneas 258-260). Reusar tal cual en Home.
- Cuando aplica, **duración** (`clip__badge`, ya existe en Clips: `0:38`) o el patrón más completo de `feature-lg__badge` / `player__badge` / `article-video__badge` (ícono YouTube + duración + texto, ej. "14:52 · Video destacado" / "Ver en YouTube") — ya construido, solo falta llevarlo a las tarjetas de grilla estándar.
- El hero de Home (línea 189-196 de `index.html`) ya hace esto bien: ícono YouTube + "YouTube" + tiempo relativo. **Este es el estándar a copiar**, no a reinventar.

### 2.2 Nota escrita
- **Sin botón de play. Nunca.**
- Badge de tipo (mismo componente `.vcard__type`, variante neutra): texto "NOTA" o "LEER", sin ícono de play — puede llevar un ícono pequeño de documento/texto si se quiere reforzar, pero no es obligatorio; lo obligatorio es la AUSENCIA del play.
- Refuerzo secundario (ya existe y funciona bien en `articulo.html` línea 180-183): **tiempo de lectura** con ícono de reloj ("6 min de lectura") + autor con avatar (`byline`). Este refuerzo debería aparecer también en la vista de tarjeta (feature/jitem) cuando el espacio lo permita, no solo en la página completa del artículo.
- El texto "· Nota" que ya aparece junto a la fecha en las tarjetas relacionadas de `articulo.html`/`video.html` (ej. línea 307: "13 de Julio, 2026 · Nota") es un buen refuerzo, pero **no puede ser el único marcador** — es texto pequeño, bajo la miniatura, fácil de no ver. El marcador principal debe estar sobre la imagen (el badge), como ya ocurre en `seccion.html`.

### 2.3 Fichaje / dato
- Formato propio: escudo origen → flecha → escudo destino + fecha + texto (`.humo`, ya existe y es un patrón fuerte y autoexplicativo — no necesita badge de texto cuando se muestra en su propia sección "Temporada de humo", porque el formato mismo ya lo distingue de video/nota).
- Cuando un fichaje se lista dentro de una grilla mixta con otros tipos (patrón `.vcard` de `seccion.html`), sí necesita el badge rojo "FICHAJE" (`.vcard__type--fichaje`, ya existe) porque ahí comparte el mismo formato visual (miniatura 16:9) que Video y Nota, y sin el badge sería indistinguible de una Nota.

### 2.4 Radio / Episodio
- Lista, no grilla (`.radio-list` / `.episode`, ya existe).
- Play button **sólido navy** (`.episode__play`, ya usa `var(--color-accent)` — distinto del ghost-button blanco de `.play-btn`). Esta diferencia de color ya es, sin querer, un marcador de tipo correcto — hay que preservarla y no "normalizarla" por accidente en una futura pasada de diseño.
- Ícono de onda de audio (`.episode__wave`) + duración larga (`1:52:04`) refuerzan "esto es un audio largo", no un clip de reacción.

---

## 3. Interacción por tipo (qué pasa al hacer clic)

La lógica real ya está en `assets/js/home.js` (línea 531-536): **cualquier elemento con el atributo `data-youtube-id` es interceptado globalmente y abre el lightbox**, sin importar si visualmente parece tarjeta, feature, jitem o botón de episodio. Todo lo demás sigue el `href` normal del navegador.

| Tipo | Trigger correcto | Destino correcto |
|---|---|---|
| **Video** | `data-youtube-id="..."` en el elemento clicable | Lightbox in-page (no navega) — **o** `href="video.html"` sin `data-youtube-id`, cuando se quiere que el video tenga página propia (SEO, más contexto, "más videos") |
| **Nota** | `href="articulo.html"`, **sin** `data-youtube-id` | Navega a la página del artículo |
| **Fichaje** | `href="articulo.html"` (o su fuente), **sin** `data-youtube-id` | Navega a la nota/fuente del fichaje |
| **Radio/Episodio** | `data-youtube-id="..."` en `.episode__play` | Lightbox in-page (misma mecánica que Video, pero con marcador visual distinto — ver 2.4) |

### 3.1 Dónde hoy es ambiguo o incoherente

**El video puede llevar a dos destinos distintos (lightbox o `video.html`) y eso es una decisión editorial válida** — pero solo si el usuario puede distinguirlos, o si dentro de una misma sección se usa un único comportamiento. Hoy `index.html` mezcla ambos silenciosamente dentro de la MISMA sección con la MISMA tarjeta (`.vcard`), sin ninguna señal:

- En "El show de la mañana" (líneas 349-387): la tarjeta 1 ("Supermetro") no tiene `data-youtube-id` → navega a `video.html`. Las tarjetas 2, 3 y 4 sí tienen `data-youtube-id` → abren el lightbox. Visualmente las 4 tarjetas son idénticas. El usuario no puede predecir si el clic lo saca de la Home o no.

**El caso grave: una Nota con marcador de Video** (ver corrección C1 abajo) — ahí el marcador miente directamente sobre el tipo de contenido, no solo sobre el destino.

---

## 4. Reglas de consistencia por sección

| Sección | Tipos que admite | Cómo debe verse |
|---|---|---|
| **Hero (destacado)** | Solo Video | Ya correcto: play + ícono YouTube + texto "YouTube" + tiempo relativo. Mantener como estándar de referencia. |
| **El show de la mañana** | Solo Video | Homogeneizar el destino del clic dentro de la sección: si se quiere que el primer ítem tenga página propia (`video.html`) y el resto abra lightbox, hay que señalarlo (p. ej. badge "Ver ficha completa" en esa tarjeta) o, más simple para el prototipo, unificar comportamiento (todas lightbox, o todas con página propia). Independientemente del destino, mantener el badge "VIDEO" en las 4. |
| **Mundo Junior** | Mixta: Nota (feature) + Nota/Video (lista) | Cada ítem de `.junior-list` debe llevar su badge de tipo (Video con play, Nota sin play + "NOTA"). El feature principal, si es Nota, sin play + indicador de lectura; si es Video, con play + badge "VIDEO" (como ya ocurre bien en el feature de Selección Colombia). |
| **Clips** | Solo Video (formato corto) | Ya correcto: duración + play en todas. No necesita badge "VIDEO" adicional porque el formato vertical/corto ya es autoexplicativo — mantener así para no sobrecargar visualmente. |
| **Temporada de humo** | Solo Fichaje | Ya correcto: el formato escudo→escudo es autoexplicativo, no necesita badge. |
| **Selección Colombia** | Hoy: solo Video | Si se mantiene 100% video, está bien sin badges por autoevidencia de sección — pero como buena práctica recomendamos igual el badge "VIDEO" en cada tarjeta, porque el usuario puede llegar a una tarjeta suelta compartida/enlazada sin el contexto de la sección. Si en el futuro se agregan notas de Selección (muy probable, es contenido editorial de alto valor), la sección pasa a ser mixta y el badge deja de ser opcional. |
| **Radio a la carta** | Solo Radio/Episodio | Ya correcto: lista + play navy + onda + duración larga. No mezclar con formato de video (grilla de miniaturas). |
| **Plantilla Sección/Índice** (`seccion.html`) | Mixta (Video, Nota, Fichaje) | Ya es el estándar correcto: filtros "Todo/Videos/Notas/Fichajes" + badge `.vcard__type` por tarjeta. Este es el patrón que hay que replicar en Home, no al revés. |
| **Sigue leyendo / Más videos** (relacionados en `articulo.html`/`video.html`) | Mixta | Ya usa el sufijo "· Nota" / "· Video" junto a la fecha y oculta el play en las notas — correcto en la lógica, pero débil como único marcador (texto pequeño). Reforzar con el badge sobre la miniatura, igual que en `seccion.html`. |

**Regla general para el filtro de `seccion.html`:** los filtros "Todo / Videos / Notas / Fichajes" deben mapear 1:1 a `data-tipo="video|nota|fichaje"`, que a su vez determina qué badge se muestra. No debe existir una tarjeta con `data-tipo="video"` sin `data-youtube-id` (o sin ir a `video.html`), ni una tarjeta con `data-tipo="nota"` que muestre `.vcard__play`.

---

## 5. Correcciones puntuales (lista accionable para el frontend)

**C1 — [BUG, prioridad alta] Nota con marcador de Video en "Mundo Junior".**
`index.html`, líneas 438-447: la primera tarjeta de `.junior-list` enlaza a `articulo.html` (es una Nota, sin `data-youtube-id`) pero incluye `<span class="jitem__play">` con el ícono de play — visualmente idéntica a las tres tarjetas de video que le siguen. El usuario espera que reproduzca un video y en realidad navega a un artículo. **Acción:** quitar `jitem__play` de esa tarjeta y añadir un badge de tipo "Nota" equivalente al `.vcard__type` de `seccion.html`, adaptado a `.jitem`.

**C2 — [Alta] Extender `.vcard__type` (badge Video/Nota/Fichaje) a toda la Home.**
Hoy el badge de tipo solo existe en `seccion.html`. Ninguna tarjeta de `index.html` (`.vcard` de "El show", `.feature`/`.jitem` de "Mundo Junior" y "Selección Colombia") lo usa. **Acción:** aplicar el mismo componente en todas las tarjetas de Home que sean Nota o compartan formato de miniatura con Video, priorizando "Mundo Junior" por ser la única sección hoy mixta.

**C3 — [Media] Comportamiento inconsistente dentro de "El show de la mañana".**
Ver sección 3.1. Cuatro tarjetas visualmente idénticas, pero la primera navega a `video.html` y las otras tres abren el lightbox. **Acción:** unificar el comportamiento dentro de la sección, o marcar explícitamente la excepción (ej. badge "Ficha completa" en la tarjeta que navega).

**C4 — [Media] El feature de "Mundo Junior" y de "Selección Colombia" no llevan ningún badge de tipo.**
El de Mundo Junior es Nota (correcto que no tenga play, pero no hay ningún indicador positivo de que es una nota — ni "NOTA" ni tiempo de lectura). El de Selección Colombia es Video (tiene play, correcto, pero tampoco lleva badge "VIDEO" ni duración, a diferencia del feature de `seccion.html` que sí lleva `feature-lg__badge`). **Acción:** llevar el patrón de `feature-lg__badge` (ya construido) a estos dos features de Home.

**C5 — [Baja] El sufijo "· Nota" / "· Video" junto a la fecha (en relacionados de `articulo.html`/`video.html`) es un buen refuerzo pero insuficiente como único marcador.** **Acción:** mantenerlo, pero sumar el badge visual sobre la miniatura (mismo fix que C2) para que la señal esté donde el ojo mira primero — la imagen, no el texto pequeño debajo.

**C6 — [Baja, para WordPress / editorial] Los enlaces de "Temporada de humo" son placeholders (`href="#"`).** No es un problema de clasificación de tipo (el formato ya es autoexplicativo), pero antes de pasar a WordPress hay que decidir si cada fichaje enlaza a su propia nota (`post` con taxonomía `tema:fichajes`) o a una página de detalle propia. Dejar definido para `estratega-wordpress`.

**C7 — [Nota de consistencia, no bug] Preservar la diferencia de color entre `.play-btn` (blanco, video) y `.episode__play` (navy sólido, radio).** Es un marcador de tipo que ya funciona bien sin badge de texto adicional. Señalarlo a `director-arte` para que no se "unifique" por accidente en una pasada de pulido visual — esa diferencia de color ES la clasificación.

---

## 6. Resumen para implementación

El sistema no requiere componentes nuevos: requiere **generalizar** `.vcard__type` (ya construido en `seccion.html`) a toda la Home, **corregir** la tarjeta de Mundo Junior donde el ícono de play miente sobre el tipo (C1), y **decidir** el comportamiento de clic dentro de "El show de la mañana" para que dos tarjetas iguales no hagan cosas distintas sin avisar (C3). El resto de secciones (Clips, Temporada de humo, Radio a la carta) ya están bien resueltas porque su *formato* — no un badge — ya comunica el tipo sin ambigüedad.
