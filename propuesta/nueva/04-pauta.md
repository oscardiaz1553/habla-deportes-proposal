# 04 · Pauta y patrocinio — Habla Deportes (propuesta nueva)

> Fecha: 6-nov-2026. Fuente principal: `00-brief-para-agentes.md` (decisiones firmes 9, 17 y 10). Base técnica revisada: `propuesta/completa-src/` (pages, build.py), `completa.css`, `centro-partidos-spec.md`, `diagnostico/*`.
> Sin respuesta del cliente sobre si vende banners o solo patrocinios fijos. **Todo el sistema funciona en los dos escenarios** (ver sección 0). Los datos de audiencia que aparezcan son de ejemplo.

## 0. Criterio general y los dos escenarios

**Principio:** el contenido manda. La monetización vive en puestos fijos, rotulados y enmarcados, y se ve claramente menos que en cualquier referente saturado. La sobriedad es el argumento de venta: el diagnóstico (benchmark) muestra que BBC, ESPN y OneFootball se ven mejor que Marca y Win Sports precisamente por esto, y la pauta de un anunciante premium vale más en un medio que se ve cuidado.

**Dos tipos de inventario, que no se mezclan:**
- **Pauta (rotulada «Publicidad»):** los 3 huecos fijos. Es un anuncio de un tercero.
- **Patrocinio (rotulado «Presentado por»):** sello sobrio en datos, radio, programas y pie. No es un anuncio: es un crédito de marca. No lleva marco de anuncio ni el rótulo «Publicidad».

**Escenario A: solo patrocinios fijos (no venden banners).**
- Los 3 huecos no se muestran vacíos. Se usan en este orden: (1) pieza del patrocinador principal (Transelca) si la entrega; (2) pieza propia del medio (suscribirse a YouTube, canal de WhatsApp, boletín) rotulada **«Habla Deportes»**, nunca «Publicidad»; (3) el hueco se colapsa (cero altura, sin caja vacía).
- El sello «Presentado por» es el ingreso principal. El Centro de Partidos funciona con el sponsor único a la derecha.

**Escenario B: venden banners.**
- Mismos 3 huecos, mismos tamaños, mismas reglas. Se vende directo y con reserva por hueco. Recomendación: **sin programática (AdSense) en esta versión.** El diagnóstico ya lo marca como el principal coste de terceros en velocidad (H7) y es lo que más ensucia. Si el cliente la exige, solo en el hueco de cierre (3) con carga diferida, nunca en los otros dos, y con tope de 1 anunciante por hueco.
- El sistema escala por **rotación de piezas dentro del mismo hueco**, no por más huecos.

En ambos casos la página tiene la misma estructura: lo único que cambia es qué contiene cada hueco.

---

## 1. Crítica de cómo está resuelta la pauta hoy (versión «completa»)

1. **Dos rotulados distintos para lo mismo.** Los placeholders `.ad` llevan «PUBLICIDAD» en mayúsculas, 10 px y con tracking (`.ad::before`); el anuncio del Centro de Partidos (`.mc__adl`) lleva el chip blanco en mayúsculas; el spec del partido pide chip navy en sentence case. Tres versiones del mismo rótulo: el usuario no aprende un patrón.
2. **El marco del placeholder rompe el sistema.** `.ad` usa borde punteado y fondo rayado beige (`#faf8f3`/`#f3efe6`). El cliente pidió blanco, sin beige. Parece un hueco en construcción, no un espacio reservado y profesional.
3. **Cinco formatos para tres decisiones.** Hay 970×90, 300×250, 300×600, el 322×424 de partidos y un `sky-ad` de alto libre. Cada página usa uno distinto y en lugares distintos (a mitad de datos, en equipo con `style` en línea). No hay un inventario predecible; eso también impide venderlo.
4. **La pauta no tiene regla de posición.** En datos hay un leaderboard a mitad de página y un MPU en lateral; en equipo, un leaderboard suelto con `margin` en línea. Falta la regla «un hueco por función».
5. **El sello de patrocinio y la pauta compiten.** El sello de datos (`dx-seal`) está en mayúsculas con tracking, como el rótulo de anuncio; en el pie y en radio, el patrocinio usa otro formato («Con el respaldo de», «Programa presentado por»). Tres fórmulas para el mismo crédito, con el logo en texto provisional «TRANSELCA» con borde, que se ve como botón.
6. **La página de Contacto vende lo que el sitio no hace.** Ofrece un «Sticky vertical» (flota, y la regla del cliente es nunca flotante) y muestra cifras de impresiones («120 mil», «90 mil», «70 mil») que son inventadas. Los títulos `.ct-ad h3` usan `--f-serif`, contra la decisión firme de no usar serifas.

Lo que sí funciona y se conserva: el hueco previo al pie ya está como `<aside aria-label="Publicidad">` en `build.py`; el anuncio del Centro de Partidos va a color pleno sin filtros; el sello de datos ya es sobrio.

---

## 2. Inventario de slots: tres huecos fijos

Nombre estable de clase y atributo: `ad-slot` + `data-slot`. Un mismo componente para los tres, en variantes de tamaño.

| # | Slot | `data-slot` | Escritorio (≥1000) | Móvil (390) |
|---|---|---|---|---|
| 1 | **Centro de Partidos** (anuncio a la derecha del banner) | `partidos` | 322×424 (ratio 0,807), pieza vertical, fija, sin rotar | 300×372 (misma pieza) centrada bajo el banner; si el anunciante da pieza 300×250, se usa esa |
| 2 | **Lateral** (columna derecha, bajo los módulos de datos) | `lateral` | 300×250; en artículo largo se permite 300×600 (estático, **sin sticky**) | 300×250 al final del cuerpo de la nota, nunca entre párrafos |
| 3 | **Cierre** (previo al pie) | `cierre` | 970×90 centrado en contenedor de 1280 | 320×100 (si no hay pieza, 300×250; si no hay ninguna, se colapsa) |

**Por qué estos tres.** Cubren las tres funciones sin competir: un hueco **editorial-deportivo** (partidos, donde el sponsor tiene sentido natural), uno **contextual** (lateral, junto a lo que se lee) y uno **de cierre** (después de que el usuario terminó de leer). No hay leaderboard bajo el menú: sería lo primero que se ve, y la regla es que no lo sea.

**Reglas de ubicación comunes:**
- Nunca antes del primer pliegue de contenido editorial; el primer hueco en cualquier página queda **por debajo** del titular y del contenido principal.
- Nunca entre dos elementos de una misma lista (cards, comentarios, tabla).
- Nunca dentro ni encima de un reproductor (video o radio).
- Separación mínima 48 px a cada lado del hueco, para que respire.

### Matriz por tipo de página

| Página | `partidos` | `lateral` | `cierre` | Total huecos | Notas |
|---|---|---|---|---|---|
| **Portada** | Sí: Centro de Partidos, **debajo de las 3 noticias** (decisión 9) | No (la portada no tiene lateral) | Sí | **2** | Las 3 noticias y el destacado quedan limpios. |
| **Artículo** | No | Sí: 300×600 en escritorio; en móvil 300×250 **después del último párrafo**, antes de relacionados | Sí | **2** | Sin pauta intercalada en el texto. La columna lateral ya lleva clasificación + próximos partidos; la pauta va **debajo** de ellos. |
| **Sección** (Selección, Junior FC) | No | Sí: 300×250 bajo la tabla/próximos partidos | Sí | **2** | La sección de Junior FC puede llevar sello «Presentado por» en lugar de `lateral` (no los dos). |
| **Datos** | No (ya es una página de partidos; el banner no se duplica) | Sí: 300×250 bajo la clasificación | Sí | **2** | Más el sello «Presentado por» (sección 5). |
| **Partido en vivo** | No | Sí: 300×250, **debajo** de la alineación y la crónica | **No** | **1** | Pantalla de alta atención; el cierre se omite. |
| **Video** (reproductor) | No | Sí: columna derecha, bajo «Siguientes videos» | Sí | **2** | Nada sobre ni dentro del reproductor, ni pre-roll en la web. |
| **Videos y redes** | No | No | Sí | **1** | Es una página de descubrimiento; el carril de Shorts y el de programas no se interrumpen. |
| **Radio** | No | No | Sí | **1** | Solo sello de programa (sección 5); el reproductor queda libre de pauta. |
| **Juan Salvador** | No | No | No | **0** | Página de persona; solo sello si el patrocinador del programa lo pide. |
| **Contacto y publicidad** | No | No | No | **0** | Aquí se muestra el media kit, no anuncios. |

### Comportamiento móvil (390 px), resumen
- **Qué cambia:** el lateral baja al final del cuerpo; el cierre pasa a 320×100; el de partidos se apila bajo el banner, no a su lado.
- **Qué se oculta:** nada se oculta por defecto (si se oculta, se pierde inventario vendido). Excepción: si no hay pieza del tamaño móvil, el hueco **se colapsa**, no se estira la pieza de escritorio.
- **Barra inferior fija** (Noticias, Videos, Datos, Radio): ningún hueco puede quedar debajo de ella. Se reserva `padding-bottom` para el pie.
- **Sin sticky ni flotantes en ningún ancho.**

---

## 3. Etiquetado y marco

**Rótulo.** Texto exacto: **«Publicidad»** (sentence case, sin mayúsculas forzadas ni tracking). Un solo componente en todo el sitio, en una banda **sobre** la pieza (no dentro), para que nunca tape la creatividad:

- Banda de 24 px de alto sobre el anuncio, alineada a la izquierda; texto Inter 12 px/500, color `--ink-3` con contraste AA sobre blanco (≥4.5:1; verificar con el token final).
- Sin icono, sin «cerrar», sin «ver más».

**Excepción acordada: Centro de Partidos.** El spec ya fija el chip dentro de la imagen, arriba-izquierda (`rgba(41,77,110,.72)`, texto blanco 12 px). Se **mantiene** esa solución porque es decisión del cliente en el mockup, y se alinea el resto del sitio a ese mismo texto y tamaño. Si luego la pieza real trae contenido en esa esquina, se pasa a la banda superior. En el CSS, se retira `.mc__adl` (blanco, mayúsculas) y se usa un único `.ad-label`.

**Marco.** Contorno de 1 px `--line-2` (hairline), fondo blanco, esquinas con `--radius-sm` (8 px) solo en el contenedor (la imagen interior mantiene esquinas rectas, coherente con la decisión 5), 16 px de padding **solo en placeholders** (la pieza real va a sangre dentro del marco). Sin sombra y sin color de acento: el azul de marca se reserva para la navegación.

**Placeholder (mockup).** Fondo blanco con un único borde sólido hairline (se elimina el rayado beige y el punteado). Texto centrado: «Espacio publicitario · Publicidad» y la medida debajo («970 × 90»). Marcar en comentario: `<!-- slot pauta: partidos|lateral|cierre -->`.

**Accesibilidad.**
- Contenedor `<aside class="ad-slot" aria-label="Publicidad" data-slot="lateral">`. Landmark con nombre único por página: si hay dos, el segundo lleva «Publicidad, cierre de página». Hoy el nombre ya existe en `build.py`.
- El rótulo visible es texto real (no imagen). Además, el enlace lleva `aria-label="Publicidad: Transelca"` (patrón ya usado) y `rel="sponsored noopener"`, `target="_blank"`.
- `alt` de la pieza: describe el mensaje del anuncio en una frase, no el nombre de archivo. Ejemplo con la pieza actual: «Anuncio de Transelca: Eso que menos ves es lo que más cuidamos». Si la pieza es solo decorativa y el enlace tiene nombre, `alt=""`.
- Foco: un solo foco por anuncio (el enlace), anillo visible de 2 px `--navy-700` con offset 2 px, objetivo mínimo de 44×44 (lo cumple el área completa del anuncio).
- **El anuncio no roba el foco ni se auto-reproduce.** Sin iframes, sin animación autoplay. Si el anunciante entrega un GIF o video, se sirve como imagen estática bajo `prefers-reduced-motion`.
- Reservar siempre `width`/`height` (o `aspect-ratio`) para evitar saltos de layout (CLS).

---

## 4. Banner de partidos con sponsor

Se parte del `centro-partidos-spec.md`, sin tocarlo, y se añade lo que falta.

**Proporciones (escritorio).**
- Grid `2.9fr 1fr`, gap 24 px, **ambas columnas a 424 px** (requisito duro). Con contenedor 1280: banner 934, anuncio 322.
- La pieza actual `publicidad.png` (1026×1272, ratio 0,807) calza con 322×424 casi sin recorte: `object-fit:cover; object-position:center`, sin filtro ni overlay (el spec prohíbe cualquier tinte).
- Si el sponsor entrega otra proporción, se le pide **322×424 exacto (x2 = 644×848)** y no se recorta una pieza horizontal: se ve peor que no tenerla.

**El anuncio es estático.** Solo el banner rota entre partidos; el anuncio de la derecha **no cambia con el slide** (un solo sponsor, una sola pieza). Así la marca no parpadea y el usuario no confunde un anuncio con un resultado.

**Estado sin anuncio (sin pieza, o contrato sin renovar).**
1. **Primera opción:** pieza propia (suscribirse a YouTube o WhatsApp) en el mismo marco, con rótulo **«Habla Deportes»** en vez de «Publicidad».
2. **Segunda opción:** el banner pasa a **ancho completo** (grid de una columna), 424 px de alto con los escudos centrados, sin columna vacía. Nunca se deja un rectángulo en blanco ni un placeholder visible en producción.
3. **En el mockup**, mostrar siempre el estado con anuncio (es el que se vende) y documentar este estado como variante `.match-center--solo` en el CSS.

**Móvil (390 px).**
- Se apila, según el spec §4: banner arriba (alto 240 px por debajo de 480), anuncio debajo.
- Cambio respecto al spec: la pieza vertical a ancho 360 mediría ~446 px de alto, casi una pantalla completa de pauta. Se limita a **300 px de ancho (≈372 de alto) centrado**, con 24 px de separación y el rótulo intacto. Si el anunciante provee 300×250, se prefiere esa.
- El anuncio queda **siempre después de los puntos del carrusel**, nunca entre el banner y su navegación.
- Los puntos del carrusel conservan 44 px de objetivo táctil aunque el dibujo sea de 8×5 (área de toque ampliada con padding).

**Observación para el cliente:** el creativo de Transelca es una pieza **de marca** de tono institucional («Eso que menos ves…»), no una oferta. Esto favorece su convivencia con contenido deportivo y conviene mantenerlo como criterio de aceptación para pauta futura.

---

## 5. Sellos de patrocinio («Presentado por…»)

Un solo componente, `.sponsor-seal`, para datos, radio, programas y pie. Reemplaza `dx-seal`, «Con el respaldo de» y «Programa presentado por».

**Formato.**
- Una línea, alto 48 px, alineada a la izquierda al borde del contenedor (no centrada, no a sangre).
- Texto: «Presentado por» en Inter 12 px/500, sentence case, sin tracking, `--ink-3`; logo a continuación a 8 a 12 px del texto.
- Variantes del texto: «Sección de datos presentada por», «Programa presentado por», «Con el respaldo de» (solo en el pie). Se elige por contexto, no se inventan otras.
- Sin borde de caja, sin fondo, sin sombra; solo un filete superior de 1 px `--line` cuando se separa de contenido. **No lleva el rótulo «Publicidad» ni el marco de anuncio**, porque no es un anuncio; así el usuario distingue los dos tipos.
- Enlace con `rel="sponsored noopener"` y nombre accesible: «Transelca, patrocinador de la sección de datos» (el `alt` del logo es «Transelca»).

**Tamaños del logo.**
| Contexto | Alto del logo | Ancho máx. |
|---|---|---|
| Datos / radio / programa (escritorio) | 24 px | 120 px |
| Móvil | 20 px | 100 px |
| Pie | 28 px | 140 px |
| Mínimo absoluto | 16 px | — |

**Fondo claro y oscuro.**
- Sobre blanco o tints claros: logo a color o monocromo navy/negro si el original no contrasta (≥3:1).
- Sobre navy `#294D6E` (pie, cabeceras oscuras): **versión monocroma blanca**. Nunca poner el logo a color sobre el navy del sitio: choca con el azul de marca y reabre el problema del diagnóstico, donde el sponsor «secuestra» la paleta.
- Sin plaquita blanca detrás del logo en fondos oscuros salvo que el patrocinador lo exija (se pregunta, sección 10).

**Zona de seguridad.** Mínimo **½ del alto del logo** libre en todos los lados (o la que fije el manual de marca de Transelca si es mayor). Nada de iconos, rótulos ni enlaces dentro de esa zona.

**Qué se pide al patrocinador.**
1. Logo **vectorial (SVG)**, no PNG ni imagen incrustada en una pieza.
2. Versiones: **a color**, **monocroma negra** y **monocroma blanca**.
3. Zona de seguridad y tamaño mínimo (extracto del manual de marca).
4. Texto exacto del crédito si lo exigen («Presentado por ISA Transelca» o «Transelca»).
5. URL de destino y parámetros UTM acordados.
6. Pieza o piezas publicitarias en las medidas de la sección 2 (@1x y @2x), en JPG/PNG/WebP ≤150 KB.

**Marcador provisional de Transelca (el logo oficial no está).**
- Texto «Transelca» en Archivo 700, 16 px, color `--navy-700`, sentence case, **sin borde ni caja ni tracking**. Hoy el provisional se pinta con borde, tracking y mayúsculas y parece un botón; no debe confundirse con un logo oficial.
- Atributo `data-logo-pendiente="transelca"` y comentario `<!-- logo oficial de Transelca (SVG): pendiente del cliente -->`.
- Se **reemplaza solo el contenido** del `<span>` por `<img src="…svg" alt="Transelca" height="24">`; el resto del componente no cambia.
- No se recorta ni se vectoriza el logo de `publicidad.png`: es una pieza con efectos, no un logo oficial.

**Dónde va cada sello (conteo máximo en la sección 8).**
| Sello | Ubicación exacta |
|---|---|
| Datos | Línea sobre el título H1 de `/datos` (ya existe; se normaliza) |
| Radio | Bajo el nombre del programa y su horario en la cabecera de `/radio`, **fuera** del reproductor |
| Programa / video | En la ficha del programa y en la descripción de la página de video, bajo el título |
| Pie | Una vez en el pie global («Con el respaldo de») |

---

## 6. Patrocinio de contenido

**Programa presentado por.** El patrocinio de «El show de las mañanas» se integra como sello (sección 5) en la cabecera del programa y en la ficha de cada episodio. En YouTube va también en la descripción. No se agrega nada al reproductor: ni logo, ni cortinilla visual, ni banner.

**Reproductor de radio.**
- El reproductor persistente (a futuro, sección Radio) es siempre limpio: título, play/pausa, volumen, estado «En vivo» en rojo (el rojo es solo para «en vivo»).
- La mención del patrocinador es **de voz** (la cortinilla del programa, que ya existe en la radio) y, en pantalla, el sello **junto a la tarjeta del programa**, nunca dentro de los controles.
- Sin audio-anuncios insertados por la web.

**Sección patrocinada (por ejemplo «Mundo Junior, presentado por…»).** Sello en la cabecera de la sección, un solo patrocinador. No cambia el color, la tipografía ni el orden editorial de la sección.

**Contenido de marca (notas o videos producidos con el anunciante).**
- Rótulo obligatorio y visible arriba de la nota, bajo el titular: **«Contenido patrocinado por [marca]»** en banda de 32 px con fondo `--navy-50` y texto navy. Es un rótulo distinto de «Publicidad», más honesto: la pieza es editorial, pero pagada.
- Nunca aparece en el destacado de portada ni en el carril de noticias; vive en su sección o como enlace en el `lateral`.
- Máximo **1 por semana** y siempre con autoría clara. Sin titulares que imiten una noticia deportiva real.
- La independencia editorial se declara una vez, en el pie del media kit y en la página de contacto: «El patrocinio no condiciona el contenido editorial».

**Patrocinio de boletín.** Una línea «Presentado por» arriba del correo, mismo formato; fuera del alcance del mockup.

---

## 7. Reglas de oro y lista de lo que no se hace

### Reglas de oro (8)
1. **Tres huecos y ya.** `partidos`, `lateral`, `cierre`. Cada uno con tamaño y lugar fijos.
2. **El contenido va primero.** Ningún hueco antes del titular y del contenido principal de la página.
3. **Un hueco visible por pantalla** (escritorio y móvil).
4. **Siempre «Publicidad».** Mismo texto, mismo tamaño, en el mismo lugar del marco, en todos los huecos.
5. **El anuncio no se disfraza.** No imita una card de noticia, ni el estilo de un módulo del sitio, ni usa el rojo «en vivo».
6. **El patrocinio es un crédito, no un anuncio.** «Presentado por» sobrio, en una línea, sin el marco de pauta.
7. **El patrocinador no cambia la identidad.** El azul de marca sigue siendo el único color de marca; el logo del sponsor va monocromo sobre fondos navy.
8. **Hueco sin pieza = hueco colapsado.** Nunca una caja vacía ni un «anuncie aquí» en producción.

### Lo que no se hace
- **Apuestas, casinos, afiliados o cripto: nunca primero, nunca sin rotular.** Si algún día se aceptan, solo en `cierre`, con «Publicidad», sin animación, y se confirma con el cliente (ver preguntas). No en portada, no en datos, no en partido en vivo. Ojo: el banner del Centro de Partidos menciona la «Liga Betplay» como nombre del torneo; es dato editorial, no pauta.
- **Sin pop-ups, overlays, interstitiales, ni «cerrar anuncio».**
- **Sin pauta sobre el reproductor**: ni de video ni de radio; sin pre-roll, sin banner superpuesto, sin logo sobre el video.
- Sin anuncios flotantes, sticky ni «flotando sobre el pie» (se retira el «Sticky vertical» de Contacto).
- Sin anuncios intercalados en listas de noticias, entre Shorts o en medio de un artículo.
- Sin autoplay de audio o video en anuncios; sin sonido.
- Sin anuncios que expandan, se muevan o desplacen el contenido.
- Sin texto «Patrocinado» ambiguo en lugar de «Publicidad» en los huecos; sin rótulos en mayúsculas gritadas.
- Sin tracking de terceros no declarado; sin iframes externos en el mockup.
- Sin cifras de audiencia inventadas en la página de contacto.

---

## 8. Densidad máxima

**Por pantalla (viewport de 1440×900 o 390×844):** máx. **1 hueco publicitario visible** y **1 sello de patrocinio visible**; nunca ambos pegados (≥ 1 pantalla de contenido entre ellos).

**Por página:**
| Concepto | Máximo |
|---|---|
| Huecos publicitarios | **2** (1 en partido en vivo, videos y radio) |
| Sellos «Presentado por» | **2** (el de la sección o programa + el del pie) |
| Contenido patrocinado | 1 por página |
| Total de módulos de marca de terceros (pauta + sellos) | **4** (incluye el pie) |
| Proporción del alto de página ocupado por pauta | ≤ 8 % |
| Distancia vertical mínima entre dos huecos | 1,5 pantallas |

**Por qué se ve más ordenado.** Hoy la versión «completa» ya es discreta; la regla nueva es más estricta y es verificable por cualquiera: se cuentan los huecos con el dedo. Para la presentación comercial se puede mostrar una captura de portada con los 2 huecos marcados frente a la de Marca o Win Sports del benchmark (`claude.ai/artifact/VmbTuTUijvBmceBksq3tCA`). **No se cita un número de anuncios de Marca o Win Sports porque no se contaron en esta fase**: el argumento es visual, no numérico.

---

## 9. Media kit (una página, para /contacto)

**Título de la página:** «Anuncia en Habla Deportes». Tono: cercano y claro, sin jerga (el cliente pidió evitar términos como CPM o UTM).

**Estructura:** una columna de texto introductorio + 6 fichas en rejilla de 3×2 (2×3 en tablet; 1 columna en móvil). Cada ficha: nombre, medida, dónde se ve, qué incluye, ejemplo gráfico (placeholder).

| Formato | Medida | Dónde se ve | Incluye |
|---|---|---|---|
| **Centro de Partidos** | 322×424 (pieza vertical, @2x 644×848) | Portada, junto al banner de partidos | Pieza fija a color pleno; exclusividad (un solo anunciante); enlace con tracking |
| **Lateral** | 300×250 (300×600 opcional en notas) | Artículos, secciones, datos, video | Pieza fija; rotación de hasta 3 piezas por campaña (si se acuerda); enlace |
| **Cierre de página** | 970×90 y 320×100 móvil | Casi todas las páginas, antes del pie | Una pieza por tamaño; enlace |
| **Presentado por (datos)** | Logo SVG, alto 24 px | Sección Datos | Sello exclusivo en todas las páginas de datos; enlace; informe del periodo |
| **Patrocinio del programa** | Logo SVG + mención de voz | Radio, YouTube y ficha del programa | Mención en vivo de lunes a viernes, 10:00 a.m. a 12:00 m.; sello en la web; descripción en YouTube |
| **Contenido de marca** | Nota o video | Sección propia, rotulado | Pieza producida con el equipo, rótulo «Contenido patrocinado por…», máx. 1 por semana |

**Elementos fijos de la página:**
- «Cómo cuidamos tu marca»: tres líneas: siempre rotulado, nunca sobre el reproductor, máximo 2 anuncios por página.
- Especificaciones técnicas de entrega: formatos JPG/PNG/WebP, ≤150 KB, @1x y @2x, sin animación ni sonido, enlace de destino, texto alternativo sugerido.
- Plazos y contacto: info@habladeportes.co (dato ya en el sitio) y un formulario corto (nombre, marca, formato de interés).
- **Cifras de alcance:** sección con la etiqueta «Cifras de ejemplo; se reemplazan con las reales del cliente». Se retiran las impresiones inventadas de la versión actual. Se piden: visitas mensuales, suscriptores de YouTube (el brief dice 265+ videos), seguidores en Instagram/X, oyentes de radio.
- Aviso de independencia editorial (sección 6).
- No se muestran precios en el mockup.

**Compatibilidad con ambos escenarios.** Si el cliente solo vende patrocinios, la tabla se reduce a las 3 filas de «Presentado por», «Patrocinio del programa» y «Contenido de marca»; los formatos de banner se esconden con un solo atributo (`hidden`) sin tocar el diseño.

---

## 10. Supuestos y preguntas abiertas

**Supuestos (marcados como tales):**
- Transelca es el único patrocinador confirmado; el creativo `publicidad.png` es real y se usa como pieza de ejemplo del slot `partidos`.
- No hay programática en la propuesta (se parte de que se prefiere venta directa).
- Los tres tamaños (322×424, 300×250, 970×90) son los estándar del mercado, ajustados al layout acordado.
- La radio hoy se patrocina con mención de voz; la web aún no participa de ese patrocinio (según el dossier vigente).
- Todas las cifras de audiencia en el mockup son de ejemplo.

**Preguntas para el cliente:**
1. ¿Venden banners o solo patrocinios fijos? ¿Con quién (directo, agencia, red)?
2. ¿Qué incluye hoy el acuerdo con Transelca (web, radio, YouTube, exclusividad por categoría, plazo)?
3. ¿Mantienen AdSense? Si sí, ¿aceptarían quitarlo de la web nueva o limitarlo al hueco de cierre?
4. ¿Hay otros patrocinadores o categorías vendibles (bebidas, banca, telecomunicaciones)? ¿Aceptarían apuestas o casas de juego (la «Liga Betplay» es nombre del torneo, no pauta)?
5. ¿Transelca puede entregar el logo en SVG (color, negro, blanco), su manual de marca y piezas en 322×424, 300×250, 970×90 y 320×100?
6. ¿Transelca exige plaquita blanca detrás de su logo o un texto de crédito específico («ISA Transelca»)?
7. ¿El anuncio del Centro de Partidos debe ser siempre del patrocinador principal o rota entre varios?
8. ¿Están de acuerdo en no tener pre-roll en el reproductor web ni pauta sobre el reproductor de radio?
9. ¿Quieren ofrecer contenido de marca? ¿Con qué límites editoriales?
10. ¿Qué cifras de audiencia reales pueden mostrarse en el media kit?
11. ¿Quién cambia las piezas a diario (el cliente o nosotros)? Define si se implementa como campos del tema o plugin de anuncios en WordPress.

---

## Anexo: entrega para el desarrollo (`desarrollador-frontend`)

- Un componente `.ad-slot` (con modificadores `--partidos`, `--lateral`, `--cierre`), un `.ad-label` y un `.sponsor-seal`. Se eliminan `.ad`, `.ad--lb|mpu|tall`, `.sky-ad`, `.mc__adl`, `.dx-seal` y `.spons`. Cero estilos en línea.
- Tokens: `--ink-3`, `--line-2`, `--navy-50`, `--navy-700`, `--radius-sm`; espaciado en múltiplos de 8.
- Cada hueco lleva `<!-- slot pauta: nombre -->` y `<aside aria-label="Publicidad">`.
- El marco del placeholder en el mockup: blanco, 1 px sólido, sin rayado.
- Texto de la pieza actual: `alt="Anuncio de Transelca: Eso que menos ves es lo que más cuidamos"`.
