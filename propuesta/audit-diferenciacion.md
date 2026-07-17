# Auditoría: diferenciación Video vs. Nota en el prototipo HTML

**Alcance:** `propuesta/html/index.html` (todas las secciones), contrastado contra `seccion.html` (que ya resuelve el problema con un sistema de badges por tipo). Revisión de código fuente directa (HTML + `assets/css/main.css`), sin necesidad de levantar servidor porque el comportamiento de interacción está declarado explícitamente en los atributos (`href`, `data-youtube-id`).

**Método de clasificación usado:** una tarjeta es **Video** si tiene `data-youtube-id` (abre el lightbox de YouTube) o enlaza a `video.html`. Es **Nota** si enlaza a `articulo.html` sin `data-youtube-id`. El "marcador" es cualquier señal visual en la tarjeta (botón play, ícono YouTube, badge de texto, duración, tiempo de lectura, ausencia deliberada de play).

---

## 1. Tabla por sección

| Sección | Tarjeta | Tipo real (por `href`/`data-youtube-id`) | Marcador visual presente | ¿Claro o ambiguo? |
|---|---|---|---|---|
| **Hero** | Slide destacado ("Cuadrado a Junior...") | Video (botón `data-hero-play` + `data-youtube-id`) | Botón play grande + ícono YouTube + texto "YouTube" en el meta | **Claro.** Es el único lugar del sitio donde el tipo se anuncia con ícono + etiqueta de marca ("YouTube") además del play. |
| **El show de la mañana** | Card 1 — "Recorriendo el Supermetro..." | Video, pero navega a `video.html` (sin lightbox) | Botón play (`vcard__play`), igual a las otras 3 tarjetas | **Ambiguo en la interacción**, no en el tipo. Las 4 tarjetas son video, pero esta abre una página nueva y las otras 3 abren un lightbox in-place — con el mismo ícono de play no hay forma de anticipar cuál pasa qué. |
| | Cards 2, 3, 4 | Video (`data-youtube-id`, abre lightbox) | Botón play (`vcard__play`) | Claro como "es un video"; ambiguo en el "qué pasa al hacer clic" (ver arriba). |
| **Mundo Junior** | Feature grande ("Cuadrado a Junior: ¿qué hay de cierto?") | **Nota** (`href="articulo.html"`, sin `data-youtube-id`) | Ningún ícono de play (correcto), pero tampoco ningún marcador positivo de "esto es una nota" (sin badge, sin ícono de texto/lectura). Solo hay byline (autor, comentarios, hora) | **Ambiguo por omisión.** La única señal es la *ausencia* de un play button. En un sitio video-first, donde 3 de las 4 tarjetas vecinas en la lista SÍ tienen play, un usuario que no ve play puede leer la tarjeta como "video roto" o "aún no cargó", no como "esto se lee". |
| | jitem 1 — "¿Jhomier Guerrero a Millonarios?..." | **Nota** (`href="articulo.html"`, sin `data-youtube-id`) | **Tiene `jitem__play` (botón de reproducción)**, idéntico al de los videos de al lado | **Error concreto.** Es una nota con un play button falso. El usuario hace clic esperando ver un video y en su lugar navega a `articulo.html`. Este es el caso más directo de lo que el cliente describe como "no se diferencian". |
| | jitem 2, 3, 4 | Video (`data-youtube-id`, abre lightbox) | `jitem__play` | Correcto y consistente entre sí, pero visualmente idéntico al jitem 1 (nota), así que dentro de la lista no hay forma de distinguir cuál de los 4 ítems es la nota. |
| **Clips** | 8 tarjetas verticales | Video (`data-youtube-id`, abre lightbox) | `clip__play` + badge de duración (`clip__badge`, ej. "0:38") | **Claro y es el mejor patrón del home.** Duración + play dejan cero ambigüedad de que es un video corto. |
| **Temporada de humo 2026** | 8 filas de fichajes | Ninguno de los dos — son enlaces de texto (`href="#"`) sin `data-youtube-id` ni miniatura | Sin ícono, solo texto de enlace | No genera confusión de tipo (no hay miniatura que sugiera video), pero tampoco comunica si al hacer clic se abre una nota larga o solo ancla al mismo lugar. Fuera del foco central de este audit, pero vale mencionarlo. |
| **Selección Colombia** | Feature grande + jitems 1-4 | **Todos Video** (`data-youtube-id` en los 5) | `feature__play` / `jitem__play` en los 5 | **Consistente internamente** (toda la sección es video), pero crea una asimetría de comportamiento con "Mundo Junior" (que mezcla tipos con el mismo componente visual). El usuario que aprendió en "Mundo Junior" que el play a veces miente no tiene motivo para confiar en el play de esta sección tampoco. |
| **Radio a la carta** | 4 episodios | Audio del programa, pero técnicamente reproducido como Video vía YouTube lightbox (`data-youtube-id`) | Botón play circular (mismo triángulo que video) + ícono de forma de onda (`episode__wave`) + duración en texto ("1:52:04") | Caso aparte (audio, no nota): el ícono de onda sugiere audio, pero el botón central es el mismo triángulo de play que usan los videos, y la duración larga (~2h) no se lee igual que la de un clip de 38s. No es la confusión video/nota que pide el cliente, pero es una tercera categoría que tampoco tiene marcador propio. |

**Referencia — cómo lo resuelve `seccion.html` (que SÍ funciona):**
Cada `.vcard` en `seccion.html` lleva un badge `.vcard__type` fijo en la esquina superior izquierda de la miniatura con la palabra **"Nota"**, **"Video"** (+ ícono de play dentro del badge) o **"Fichaje"** (en rojo de marca). Solo las tarjetas de video, además del badge, tienen el botón de play grande centrado. El destacado de sección (`feature-lg`) va un paso más allá: badge con duración exacta + etiqueta ("14:52 · Video destacado"). Este sistema de badges existe en `main.css` (clase `.vcard__type`, línea ~2334) pero **no se usa en ninguna tarjeta de `index.html`**, ni en el módulo de relacionados de `articulo.html`/`video.html` (que usan un tercer patrón distinto: sufijo de texto "· Nota" / "· Video" junto a la fecha, sin badge visual).

Es decir: **el sitio ya tiene la solución construida, pero aplicada en una sola plantilla (`seccion.html`) y no en la página que más tráfico recibe (`index.html`).**

---

## 2. Problemas concretos, priorizados

1. **[Alto impacto] Nota con play button falso — "Mundo Junior", jitem 1.** Un enlace a `articulo.html` (texto) muestra el mismo ícono de reproducción que los videos de al lado. Es el ejemplo más literal del problema que reporta el cliente: el usuario cree que va a ver un video y termina en una nota. Fácil de reproducir, fácil de mostrar en una demo al cliente.

2. **[Alto impacto] El feature grande de "Mundo Junior" es una nota sin ningún marcador positivo.** Al no tener play (correcto) pero tampoco un badge "Nota" ni ícono de lectura, la señal es puramente negativa ("no tiene lo que las otras tienen"), lo cual es una lectura débil, sobre todo en mobile donde las tarjetas se ven una por una y no hay nada al lado para comparar.

3. **[Alto impacto] Tres sistemas de marcado distintos conviviendo en el prototipo.** `index.html` (sin marcador), `articulo.html`/`video.html` (sufijo de texto "· Nota"/"· Video" junto a la fecha) y `seccion.html` (badge de color en la miniatura). Un usuario que aprende a leer las tarjetas en una página no puede transferir ese aprendizaje a otra, porque el mismo componente (`.vcard`) se comporta distinto según la plantilla.

4. **[Medio impacto] El play button no distingue "abre aquí" de "te saca de la página".** En "El show de la mañana", la Card 1 navega a `video.html` mientras las Cards 2-4 abren un lightbox sobre la misma página. Visualmente son indistinguibles (mismo ícono, mismo tamaño). No es confusión video/nota, pero sí rompe la promesa implícita del ícono de play ("un clic, mismo lugar, reproduce"). Esto es más grave en mobile, donde salir de la página implica perder el scroll/contexto sin aviso.

5. **[Medio impacto] "Radio a la carta" usa el mismo triángulo de play que los videos cortos**, pero son episodios de ~2 horas reproducidos en un lightbox pensado para video. No hay nada que anticipe "esto es audio de un programa largo" versus "esto es un clip de 40 segundos". No es el foco central del pedido, pero alimenta la misma sensación general de "todo se ve igual".

6. **[Bajo impacto] "Temporada de humo" no indica si el enlace lleva a una nota completa.** Son filas de una sola línea sin miniatura; el riesgo de confusión video/nota es bajo porque no hay imagen que sugiera "reproducible", pero tampoco hay señal de "esto abre una nota".

---

## 3. Recomendaciones priorizadas (impacto / esfuerzo)

### Quick wins (alto impacto, bajo esfuerzo — reutilizan CSS ya existente)

1. **Extender el badge `.vcard__type` (ya definido en `main.css`) a todas las tarjetas de `index.html`.** Es la clase que ya existe y ya funciona en `seccion.html`. Aplicarla a los `.vcard` de "El show de la mañana" (todas Video, badge "Video" con ícono play), y adaptar una versión equivalente para `.jitem` y `.feature` en "Mundo Junior" y "Selección Colombia" con las etiquetas "Nota" / "Video". Con los tokens de marca: badge con fondo `--scrim-chip` (translúcido navy oscuro) + texto "Video" + ícono play para video; mismo tratamiento con texto "Nota" y sin ícono de play para nota; opción de usar `--brand-red` (#D20000) solo para "Fichaje"/EN VIVO, manteniendo el rojo reservado a urgencia/marca y no a "tipo de contenido" en general.

2. **Quitar el `jitem__play` del jitem 1 de "Mundo Junior"** (el que enlaza a `articulo.html`). Es un cambio de una línea que elimina el error más flagrante detectado.

3. **Unificar el patrón entre `index.html`, `articulo.html`, `video.html` y `seccion.html`.** Elegir uno solo (recomendado: el badge visual de `seccion.html`, porque es perceptible sin leer texto pequeño) y aplicarlo en las cuatro plantillas. El sufijo de texto "· Nota"/"· Video" de `articulo.html`/`video.html` puede convivir como refuerzo textual (accesibilidad/SEO), pero no debería ser el único marcador donde ya existe el badge.

### Corto plazo (impacto medio-alto, esfuerzo medio)

4. **Agregar un ícono de "tiempo de lectura" a las notas**, en el mismo lugar donde los clips muestran duración (`clip__badge`). Ej.: "4 min de lectura" con un ícono de documento/reloj, en la esquina de la miniatura o junto a la fecha. Esto le da a la nota una señal *positiva* equivalente a la duración del video, en vez de depender solo de la ausencia de play. Refuerza el patrón "duración = video, tiempo de lectura = nota" de forma simétrica.

5. **Diferenciar el ícono de "Radio a la carta"** del de los videos cortos: mantener el ícono de onda de audio pero quitar el triángulo de play idéntico al de `.clip__play`/`.jitem__play`, o encerrarlo en un badge de color distinto (ej. navy sólido en vez de navy translúcido) para que se lea como su propia categoría ("Episodio", con duración larga tipo "1:52:04" ya presente).

### Mediano plazo (esfuerzo mayor, requiere decisión de UX)

6. **Definir la regla de interacción del play button y aplicarla sin excepciones**: o el play siempre abre lightbox in-place, o siempre navega a `video.html` — no ambos con el mismo ícono. Si el prototipo necesita mantener ambos comportamientos (ej. el video "ancla" de una sección va a su propia página para SEO/permalink, el resto abre lightbox), se recomienda un micro-indicador adicional (ej. un pequeño ícono de "expandir/enlace externo" superpuesto solo en las tarjetas que navegan) para que el usuario prediga el resultado del clic antes de hacerlo. Este punto queda para que `director-arte`/`desarrollador-frontend` definan el patrón único; aquí solo se señala la inconsistencia.

---

## Resumen ejecutivo (8-12 líneas)

Los puntos más confusos hoy: (1) en "Mundo Junior" hay una nota (jitem 1) con un botón de play falso, idéntico al de los videos de al lado — el caso más directo del problema que reporta el cliente; (2) el feature grande de esa misma sección es una nota que solo se distingue por la *ausencia* de play, sin ningún marcador positivo de "esto se lee"; (3) el prototipo usa **tres sistemas distintos** de marcado tipo de contenido según la plantilla: sin marcador en `index.html`, sufijo de texto "· Nota"/"· Video" en `articulo.html`/`video.html`, y un badge visual en la miniatura en `seccion.html` — y este último, que es el que mejor funciona, no se aplicó al home; (4) dentro de "El show de la mañana" las 4 tarjetas son video pero una navega a `video.html` y tres abren un lightbox, con el mismo ícono de play para ambos comportamientos; (5) "Radio a la carta" comparte el mismo triángulo de play que los clips cortos aunque son episodios de ~2 horas, sin distinguirse como una tercera categoría (audio/programa largo).

Recomendaciones clave: extender el badge `.vcard__type` que ya existe en `main.css` (usado hoy solo en `seccion.html`) a todas las tarjetas de `index.html`, quitar el play falso del jitem-nota, unificar el patrón de marcado entre las cuatro plantillas, y agregar un indicador de "tiempo de lectura" a las notas como contraparte simétrica de la duración en los videos — todo reutilizando componentes y tokens de color (navy `--scrim-chip`, rojo `--brand-red` reservado a fichajes/urgencia) que el sistema ya tiene definidos.

**Archivo:** `propuesta/audit-diferenciacion.md`
