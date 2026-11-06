# 01 · Sistema de diseño: propuesta nueva de Habla Deportes

> Dirección de arte · 6-nov-2026. Fuente: `00-brief-para-agentes.md` (las decisiones firmes de la sección 4 se respetan todas; los desvíos que propongo están en la sección 11).
> Tokens: `propuesta/html/nueva/assets/css/tokens.css`. Este documento manda sobre el CSS: si algo no está aquí, no se inventa en el componente.
> Lo mío es una hipótesis (el cliente no ha respondido): video-first, con Junior y la Selección en el centro.

**Concepto, en una frase:** *Azul en la cabina, blanco en la cancha.* Habla Deportes nació en la radio. El sitio se arma como una transmisión: arriba la cabina azul con la marca, abajo una cancha blanca donde se ve el contenido. Hay una pieza que manda por pantalla, el tiempo marca el orden (horas, minutos, duraciones) y el rojo solo aparece cuando hay señal en vivo.

**Atributos:** sobrio · rápido de escanear · de Barranquilla, no genérico · video primero · ordenado (se pueden contar los módulos con la mano).

---

## 1. Crítica de la versión actual (`propuesta/html/completa/`)

Revisé capturas a 1440 y 390 px de la portada y leí `completa.css`. Lo que funciona se conserva: el azul del logo, la pareja Archivo + Inter, la noticia principal horizontal y las imágenes con esquina recta. Lo que la hace ver «de IA» o desordenada:

1. **La cabecera ocupa 196 px y repite cosas.** Son 92 px del logo, 52 del menú y 52 de la barra blanca. «Datos» y «Opinión» aparecen en la barra azul y otra vez en la blanca. En la portada, la barra blanca dice «Habla Deportes» al lado del logo que dice lo mismo. La fecha y el clima («Barranquilla · 31 °C») ocupan espacio sin tener función. Antes de la primera noticia ya se fue el 22 % de la pantalla.
2. **Seis bloques azules seguidos.** Datos, Selección, radio, boletín, efemérides y pie usan fondo navy. El cliente rechazó el exceso de azul en fondos, y además el azul deja de distinguir algo: si todo es azul, nada lo es. A eso se suma la sección de datos completa en navy a mitad de página, que rompe la regla del fondo blanco.
3. **Once secciones con el mismo molde.** Cada sección lleva barra vertical azul, icono, título y la píldora «Ver todo ›». Después del Centro de Partidos vienen datos, el show, Mundo Junior, Clips, Fichajes, Selección, redes, voces, radio, encuesta, efemérides, boletín y publicidad. Es el ritmo de una plantilla: no hay una pieza que mande y choca con la decisión 11 (no exceso de tarjetas). En móvil son 12.861 px de scroll.
4. **La forma no es coherente.** Hay 10 radios distintos en el CSS: píldoras en «Ver todo», en «Suscribirse» y en el campo del boletín; 8 y 12 px en los bloques de radio y boletín; 50 % en las flechas y 0 en las imágenes. Unas tarjetas tienen borde de 1 px y otras no, y la principal va metida en una caja con borde. Botones redondos dentro de un mundo de esquinas rectas: es el error que más delata una página «armada».
5. **Exceso de etiquetas y un «en vivo» que pierde valor.** Junto al titular principal aparecen «EN VIVO» y «EXCLUSIVO»; cada noticia pequeña lleva una caja azul rellena («VIDEO», «SELECCIÓN», «JUNIOR») con el mismo peso visual que el aviso de en vivo. El bloque de radio muestra «AL AIRE» en rojo aunque esté fuera de horario. El banner dice «EN VIVO · 67'» y, en la misma pieza, «Hoy · 7:30 p.m.». Si el rojo aparece sin razón, deja de servir como alarma.
6. **No hay escala tipográfica.** El CSS usa unos 30 tamaños de letra (13, 14, 14,5, 15, 15,5 px…). Todos los titulares de tarjeta van en azul, del mismo color que los enlaces, así que titular y acción no se distinguen. Hay etiquetas superiores en mayúscula con espaciado sobre casi todos los bloques: es el tic número uno de las páginas hechas por IA.
7. **Las imágenes se repiten a la vista y hay huecos.** En la portada, `juansalvador1.png` sale 9 veces y `juansalvador2.png` 6 (principal, show, clips, Instagram, tuit). En Selección e Instagram quedan cajas celestes vacías. Se usan fotos de Messi y Mbappé en un medio de fútbol colombiano. En los Shorts, el texto va sobre la foto con degradado.
8. **El código tiene dos sistemas superpuestos.** `completa.css` no carga `tokens.css` y redefine su propia paleta, con nombres heredados que ya mienten: `--sun` es azul y `--f-serif` es Archivo. Son 68 hexadecimales distintos, 13 `!important` y 12 capas de parches (v1 a v12). Cada cambio del cliente se resolvió con otra capa encima. La versión nueva arranca de estos tokens y no hereda nada de ese archivo.

---

## 2. Principios (por qué esto se ve propio y no genérico)

1. **Azul en la cabina, blanco en la cancha.** El azul es la cabecera de dos niveles y la acción (botones, enlaces, estado activo). Todo lo demás va sobre blanco. Solo hay dos bloques oscuros en la página: el banner de partidos (exigido por el cliente) y el pie. Nada más se pinta de azul.
2. **El texto nunca va sobre la foto.** Titular, bajada y metadatos van siempre fuera de la imagen, al estilo de BBC, Sky y ESPN. Sobre la foto solo se permiten controles y datos cortos: play, duración, «Short» y la píldora de en vivo. Así no hacen falta degradados, el contraste está garantizado y las fotos repetidas pesan menos, porque la imagen acompaña y no carga el mensaje.
3. **El tiempo es la estructura (la escaleta).** Habla Deportes es radio: piensa en horas y minutos. Horas del programa, minuto del partido, duración del video y fecha del fichaje van en una misma voz, Archivo condensado con cifras tabulares, en una columna fija a la izquierda. Es la firma del medio y sale de su oficio, no de un adorno.
4. **La cursiva del logo, solo en los rótulos.** El logo «Habla» es una grotesca gruesa en cursiva. Los rótulos de sección la repiten (Archivo 800 cursiva) sobre un filete de 2 px en tinta. Es el único gesto tipográfico decorativo del sistema. Los titulares de noticias van rectos para leerse bien.
5. **Una pieza manda y el resto se ordena por tamaño y aire.** Nada de cajas con borde ni sombras para agrupar: se agrupa con proximidad y filetes de 1 px. La portada tiene un presupuesto fijo (sección 6.0). Los módulos de bajo uso (encuesta, efemérides, opinión, boletín) viven en sus secciones, no en la portada.

---

## 3. Paleta

Contrastes calculados con la fórmula WCAG 2.1 (luminancia relativa), no a ojo. AA exige 4,5:1 para texto normal y 3:1 para texto grande y elementos de interfaz.

### 3.1 Primitivas

| Token | HEX | Uso |
|---|---|---|
| `--brand-600` | `#294D6E` | **Azul del logo.** Cabecera nivel 1, botones, enlaces, estado activo |
| `--brand-700` | `#203E5A` | Cabecera nivel 2 (menú), hover de botón |
| `--brand-800` | `#183148` | Botón presionado, hover de enlace |
| `--brand-900` | `#0F2133` | Los dos bloques oscuros: banner de partidos y pie |
| `--brand-300` | `#9FBAD3` | Enlace y foco sobre azul oscuro |
| `--brand-100` / `--brand-50` | `#D8E3ED` / `#EDF2F7` | Borde y fondo de lo seleccionado (fila de Junior, pestaña de tabla) |
| `--gray-900` | `#111C27` | Tinta: titulares y cuerpo |
| `--gray-700` | `#3B4856` | Texto secundario (bajadas) |
| `--gray-600` | `#52606D` | Texto atenuado (meta, fechas, «Publicidad») |
| `--gray-400` | `#8794A1` | Borde de campos e iconos inactivos (no se usa para texto informativo) |
| `--gray-200` | `#DCE2E8` | Filetes |
| `--gray-100` / `--gray-50` | `#EBEFF3` / `#F4F6F8` | Hover de fila / superficie sutil (pauta, cebra, skeleton) |
| `--live-600` | `#D42318` | **Solo en vivo:** píldora, punto, minuto del partido |
| `--error-700` | `#A8261C` | Errores de formulario (excepción justificada en la sección 11) |

Los neutros son fríos (matiz cercano a 210°), del mismo matiz que el azul, para que no haya mezcla de grises cálidos y fríos. El fondo es `#FFFFFF`, sin excepción.

### 3.2 Reglas de uso

- **Azul:** cabecera, botones primarios, enlaces de acción («Ver todo», enlaces relacionados), indicadores de activo y forma «G». **Los titulares van en tinta, no en azul:** el azul significa «acción», no «título».
- **Rojo:** solo «En vivo» y «Al aire», y solo cuando es verdad (partido en juego o L–V de 10:00 a 12:00). Nunca para derrotas, «Exclusivo», YouTube ni alertas.
- **Datos sin verde ni rojo.** La racha (G/E/P) se codifica con relleno y borde: G es relleno azul con letra blanca, E relleno gris con letra tinta y P blanco con borde gris-400 y letra tinta. La letra siempre está presente. Los estados de fichajes funcionan igual: «Cerrado» relleno azul, «Negociando» borde azul y «Rumor» borde gris discontinuo (el trazo discontinuo comunica «no confirmado»).
- **Patrocinador:** sus colores viven solo dentro de su pieza. Sobre azul, el logo va en blanco monocromo (coincide con `04-pauta.md`).

### 3.3 Contrastes verificados

| Par (texto / fondo) | Ratio | Resultado |
|---|---|---|
| Tinta `#111C27` / blanco | 17,22 | AAA |
| Secundario `#3B4856` / blanco | 9,34 | AAA |
| Atenuado `#52606D` / blanco · / `#F4F6F8` · / `#EDF2F7` | 6,46 · 5,96 · 5,73 | AA |
| Azul `#294D6E` / blanco (enlace) | 8,82 | AAA |
| Blanco / azul `#294D6E` (botón, cabecera 1) | 8,82 | AAA |
| Blanco / `#203E5A` (menú) · blanco 80 % / `#203E5A` | 11,06 · 7,70 | AAA |
| Blanco 80 % / `#294D6E` (secundario en cabecera) | 6,31 | AA |
| Blanco / `#0F2133` (banner, pie) · blanco 80 % / `#0F2133` | 16,33 · 10,84 | AAA |
| `#9FBAD3` / `#0F2133` (enlace en pie) | 8,12 | AAA |
| Blanco / rojo `#D42318` (píldora en vivo) · rojo / blanco (minuto) | 5,18 · 5,18 | AA |
| Error `#A8261C` / blanco | 7,10 | AAA |
| Blanco / chip `rgba(17,28,39,.85)` sobre foto blanca (peor caso) | 10,87 | AAA |
| Elementos de interfaz: borde de campo `#8794A1` / blanco | 3,10 | AA (3:1) |
| Foco: anillo `#294D6E` / blanco · anillo blanco / azul | 8,82 · 8,82 | AA |
| Forma G `#294D6E` frente a E `#DCE2E8` | 6,76 | Se distinguen sin color |

Pares que **no** pasan y por eso no se usan: rojo sobre azul (1,70), y `#294D6E` frente a `#203E5A` (1,25). Por eso el ítem activo del menú se marca con una barra blanca de 3 px y peso 700, no con otro tono de azul.

---

## 4. Tipografía

Familias: **Archivo** para titulares, números y rótulos (con eje de ancho 75–100 e itálica 700–800) e **Inter** para texto e interfaz. Cada familia usa tres pesos; no todo va en negrita. Comprobé que Archivo incluye la función `tnum`: los marcadores usan `font-variant-numeric: tabular-nums` y el ancho `--wdth-condensed: 82`.

| Token | Tamaño (390 → 1440 px) | Familia / peso | Interlineado / tracking | Uso |
|---|---|---|---|---|
| `--fs-h1` | 32 → 48 | Archivo 800 | 1,05 / -0,02em | H1 de nota, sección y datos |
| `--fs-lead` | 28 → 40 | Archivo 700 | 1,15 / -0,02em | Titular de la noticia principal (máx. 4 líneas) |
| `--fs-section` | 24 → 32 | **Archivo 800 cursiva** | 1,15 / -0,01em | Rótulo de sección (H2), con filete de 2 px encima |
| `--fs-title-lg` | 20 → 24 | Archivo 700 | 1,15 / -0,01em | Tarjeta de programa, H2 dentro de la nota |
| `--fs-title-md` | 18 → 20 | Archivo 600 | 1,3 / -0,01em | Las tres noticias pequeñas, tarjeta de video |
| `--fs-title-sm` | 16 | Archivo 600 | 1,3 | Listas, Shorts, filas de fichajes |
| `--fs-standfirst` | 17 → 18 | Inter 400 | 1,6 | Bajada y cuerpo de la nota |
| `--fs-body` | 16 | Inter 400 | 1,6 | Cuerpo e interfaz (mínimo para texto corrido) |
| `--fs-ui` | 15 | Inter 600 (menú azul) / 500 (barra blanca) | 1,3 | Navegación, botones secundarios |
| `--fs-meta` | 14 | Inter 500 | 1,4 | Autor, hora, tablas, pie |
| `--fs-label` | 12 | Inter 600, mayúscula y 0,04em solo en cabeceras de tabla | 1,4 | «Publicidad», duración, cabecera de tabla, píldora en vivo |
| `--fs-score-xl` | 48 → 72 | Archivo 800, wdth 82, tabular | 1,05 | Marcador del banner de partidos |
| `--fs-score-lg` | 32 → 40 | Archivo 800, wdth 82, tabular | 1,05 | Tarjeta de partido en vivo |
| `--fs-score-md` / `-sm` | 24 / 18 | Archivo 700, wdth 82, tabular | 1,05 | Franja por día / tabla y escaleta |

Reglas:

- Mayúsculas solo en cabeceras de tabla y en la píldora «EN VIVO». **Sin etiquetas superiores** sobre las secciones. La categoría de una noticia («Junior», «Selección») va en Inter 600 de 14 px, en azul y con la capitalización normal de la frase, pegada al titular. No lleva caja.
- Ancho de párrafo: `--measure` 68ch. En la nota, la columna es de 720 px.
- `text-wrap: balance` en titulares y `pretty` en bajadas.
- Navegador: la selección de texto usa `--brand-50` con tinta, el `accent-color` es `--brand-600` y el subrayado de enlaces lleva `text-underline-offset: 3px` con grosor de 1 px (2 px en hover).

---

## 5. Retícula, contenedor y espaciado

- **Contenedor:** 1280 px de máximo. Márgenes laterales de 16 px (base), 24 (≥600) y 32 (≥1024).
- **Retícula:** 12 columnas. Separación de 16 px en base y 24 px desde 1024.
- **Breakpoints:** base <600 · sm ≥600 · md ≥768 · lg ≥1024 (aparece el menú azul de nivel 2 y desaparece la barra inferior) · xl ≥1280.
- **Columna lateral:** 300 px (datos y pauta lateral) en notas y secciones, desde lg. La portada no tiene columna lateral.
- **Espaciado (8 px):** 8 · 16 · 24 · 32 · 40 · 48 · 64 · 80 · 96. `--space-half` (4 px) solo para separar icono y texto o el relleno de una etiqueta.
- **Ritmo:** entre secciones de portada, 48 px (base) y 64 px (lg). Dentro de una sección: rótulo → contenido 24 px; tarjeta → tarjeta 24 px; imagen → texto 16 px; categoría → titular 8 px; titular → meta 8 px. Siempre hay más aire encima de un rótulo que debajo.
- **Sin scroll horizontal** a 360, 768 y 1440 px. Los carriles (Shorts, barra contextual) se desplazan dentro de su propio contenedor con `overscroll-behavior-x: contain`.

---

## 6. Componentes

### 6.0 Presupuesto de la portada (para que no haya exceso de tarjetas)

En este orden: 1) noticia principal · 2) tres noticias · 3) Centro de Partidos + pauta `partidos` · 4) Programas (3 tarjetas) · 5) Shorts (un carril) · 6) Radio (escaleta) · 7) Junior y Selección: una lista de 5 titulares con miniatura pequeña + próximo partido · 8) Redes (6 tarjetas compactas) · 9) pauta `cierre` · pie. **Son 9 bloques; hoy hay 14.** Encuesta, efemérides, opinión y boletín se van a sus secciones. Si se quiere una señal en la portada, es una sola línea de 3 enlaces solo texto dentro del bloque 7.

### 6.1 Encabezado de dos niveles (azul)

| | Escritorio (≥1024) | Móvil (<1024) |
|---|---|---|
| Nivel 1, logo | 64 px, `--brand-600`. Logo blanco (`LogoHablaDeportes-white.png`) de 36 px de alto a la izquierda. A la derecha: buscar y YouTube como botones de icono de 44×44, blancos, con `aria-label`. **Sin fecha, sin clima, sin botón de radio.** | 56 px; logo de 28 px; buscar a la derecha |
| Nivel 2, menú | 48 px, `--brand-700`. Junior · Selección · Liga · Fichajes · Videos · Datos · Radio · Opinión. Inter 600 de 15 px, blanco, con 16 px de relleno lateral. **Activo:** barra blanca de 3 px abajo + peso 700. Hover: fondo `rgba(255,255,255,.08)` + subrayado de 1 px. Sin escudos. | No existe: lo sustituye la barra inferior |
| Pegado al hacer scroll | Solo el nivel 2 se queda arriba (`--z-sticky`, `--shadow-sticky`) y muestra a la izquierda el logo de 24 px. Los 64 px del nivel 1 se van con el scroll. | El nivel 1 se queda pegado (56 px) |

Total visible antes del contenido: **160 px en escritorio y 104 en móvil** (antes, 196). El logo enlaza a la portada, así que no hace falta un ítem «Inicio».

### 6.2 Barra blanca contextual y paneles

- 48 px, blanca, con filete inferior de 1 px `--color-border`. Siempre visible bajo el menú (no queda pegada).
- Izquierda: **nombre de la sección** en Archivo 700 cursiva de 18 px, tinta, seguido de un divisor vertical de 1×24 px. En la portada el nombre es «Portada», nunca «Habla Deportes».
- Enlaces: Inter 500 de 15 px en `--color-text-secondary`, separados 24 px. **Activo:** tinta, peso 600 y barra de 3 px en `--brand-600` montada sobre el filete. Los ítems con panel llevan un chevron de 16 px.
- **No repite el menú azul.** Muestra lo de dentro de la sección (Junior: Noticias · Partidos · Plantilla · Fichajes · Torneos ▾).
- **Panel (escritorio, por hover):** blanco, pegado al borde inferior de la barra (sin hueco), borde de 1 px, `--shadow-panel` y radio 0. Ancho entre 320 y 640 px, relleno de 24 px, 2 o 3 columnas de enlaces Inter 500 de 15 px (cada fila de 44 px de alto, sin separación extra). El título de columna va en Inter 600 de 14 px en tinta, sin mayúsculas. Se abre tras 120 ms de intención y se cierra tras 280 ms de gracia, con un puente invisible de 8 px (según `megamenu-ux.md`). Entra con opacidad y `translateY(-4px → 0)` en `--dur-base`.
- **Móvil:** la barra se desliza de lado (`overflow-x: auto`, `scroll-snap`, máscara en degradado de 24 px a la derecha para indicar que hay más). Un ítem con panel abre una **hoja inferior** blanca: arranca a 70 dvh de alto, con asa de 32×4, lista de filas de 48 px y botón cerrar de 44 px. En móvil no hay hover, así que el toque es la única vía (ver la sección 11).

### 6.3 Noticia principal horizontal (estilo Sky Sports)

- **≥1024:** imagen en 7 columnas a 16:9 (unos 740×416 px), texto en 5 columnas alineado arriba con 32 px de separación. **Sin caja ni borde:** imagen y texto sobre blanco.
- Texto: categoría (Inter 600 de 14 px, azul) + píldora «En vivo» solo si es verdad → titular `--fs-lead` (tinta, máx. 4 líneas) → bajada `--fs-standfirst` en `--color-text-secondary` (máx. 3 líneas) → meta de 14 px (autor · hace 2 h) → **enlaces relacionados**: 2 o 3 filas de 48 px con filete superior de 1 px, Inter 600 de 16 px, tinta y flecha de 16 px en azul al final. En hover la fila se subraya y la flecha avanza 2 px.
- Si es video: botón play circular de 64 px (`--brand-600`, triángulo blanco) abajo a la izquierda, a 16 px del borde, y chip de duración abajo a la derecha. Toda la imagen es objetivo táctil.
- 768–1023: 6 + 6 columnas y titular de 32 px. **<768:** imagen a sangre, 16:9 y de borde a borde, con el texto debajo y 16 px de margen. Titular de 28 px y un solo enlace relacionado.

### 6.4 Noticia pequeña y tarjeta de video

- **Las tres noticias (≥768):** 3 columnas de 4. Imagen 16:9 arriba (unos 400×225 px), 16 px de aire, categoría, titular `--fs-title-md` (máx. 3 líneas) y meta. Entre columnas, un filete vertical de 1 px centrado en la separación de 24 px. No llevan caja.
- **<768:** filas de lista con miniatura 16:9 de 128×72 a la izquierda, titular de 17 px a la derecha y filete de 1 px entre filas. Cada fila mide 96 px como mínimo.
- **Video:** igual que la noticia, más play de 40 px abajo a la izquierda (8 px de margen) y chip de duración abajo a la derecha. El tipo de contenido se ve por el play, no por una etiqueta «VIDEO».
- **Chip sobre imagen:** `--color-chip-on-media`, texto blanco Inter 600 de 12 px tabular, relleno de 4×8 y radio de 2 px.
- **Hover (solo `hover: hover`):** el titular se subraya 1 px y la imagen baja a `brightness(.94)` en `--dur-fast`. Sin zoom ni elevación.

### 6.5 Carril de Shorts (clips verticales)

- Rótulo «Shorts» + flechas circulares de 44 px (borde de 1 px `--color-border-field`, icono de 20 px) a la derecha, solo con puntero fino.
- Tarjeta 9:16: **180×320** (≥1024, 6 visibles) y **148×263** (móvil, 2,4 visibles para que se vea que sigue). Separación de 16 px y `scroll-snap-type: x mandatory`.
- **El título va debajo** (Inter 600 de 15 px, máx. 2 líneas) y las vistas debajo en meta, con la marca «ejemplo». Sobre la imagen solo va el chip «Short» arriba a la izquierda.
- **Nunca se mezcla con programas:** carril distinto, proporción distinta y rótulo distinto.

### 6.6 Cuadrícula de programas

- Rótulo «El show de las mañanas» + enlace «Todos los programas» a la derecha.
- 3 columnas (≥1024), 2 (≥600) y 1 (móvil, con la primera grande y dos en lista). Imagen 16:9, fecha del episodio en Archivo condensado de 14 px tabular («Lun 2 nov»), título `--fs-title-lg` (máx. 2 líneas), duración en el chip y play de 40 px.
- En la portada se muestran solo 3.

### 6.7 Banner de partidos (estilo Centro de Partidos) + pauta

- Va debajo de las tres noticias, como en la decisión 9. Retícula `1fr 322px` con 24 px de separación. **Alto de 424 px** en escritorio (requisito del cliente), 300 px en 768–1023 y 240 px por debajo de 480.
- Fondo `--color-surface-dark` liso. Las texturas de bandera (`junior_nacional.png`…) se dejan como opción al 20 % de opacidad; recomiendo el fondo liso porque pesan 2,8 MB y compiten con los escudos. **Radio 0** en banner y pauta (reemplaza los 8 px de `centro-partidos-spec.md`, por la decisión 5).
- Contenido centrado. Arriba: torneo · fase en Inter 600 de 14 px, blanco al 80 %, + estado (píldora roja «EN VIVO 67'» solo en juego; «Final» o la hora en los demás casos). Escudos de 180 px de alto en escritorio, 112 en móvil, con `object-fit: contain` y sin recorte. Al centro, el marcador `--fs-score-xl` o, si no ha empezado, la hora (`19:30`) en el mismo estilo. Debajo, el nombre del equipo en Archivo 700 de 20 px. Fila de información: estadio · canal (logo de 24 px), Inter 500 de 14 px.
- Abajo: flechas circulares de 44 px (borde blanco al 24 %) + puntos de 8 px, con el activo como barra de 24×4. El área táctil de cada punto es de 44 px. Rota cada 8 s con fundido en `--dur-slow`, se pausa con hover, con foco o si hay movimiento reducido, y lleva un botón «Pausar» de 44 px para cumplir WCAG 2.2.2.
- La pauta de la derecha sigue `04-pauta.md` (pieza fija de 322×424, no rota). <1024: se apila debajo, con 300 px de ancho máximo.

### 6.8 Franja de partidos por día (página de datos)

- Pestañas **Ayer · Hoy · Mañana**: 48 px de alto, Inter 600 de 15 px. Activa: tinta, barra de 3 px `--brand-600` abajo y `aria-selected`. Inactiva: `--color-text-muted`.
- Debajo, partidos agrupados por torneo (cabecera de 40 px, Inter 600 de 14 px, con filete). **Fila de 56 px** en retícula `64px 1fr 56px 1fr auto`: hora o estado (Archivo condensado de 18 px tabular; en juego, minuto en rojo con punto) · local alineado a la derecha + escudo de 24 · marcador de 24 px tabular · escudo de 24 + visitante · canal. El ganador va en peso 700 y el perdedor en 500.
- **Vacío:** «No hay partidos este día. Próxima fecha: sáb 8 nov.» + enlace. Sin ilustración.

### 6.9 Tabla de posiciones

- **Lateral (300 px):** 8 filas de 40 px. Columnas # (32) · equipo (escudo de 20 + nombre de 14 px) · PJ · DG · PTS, todas tabulares y alineadas a la derecha. Cabecera de 12 px en mayúscula y `--color-text-muted`. **Fila de Junior:** fondo `--color-surface-selected` y peso 600. Zonas de clasificación: un cuadrado de 8×8 en `--brand-600` antes del número, con leyenda debajo. No se usa borde izquierdo de color.
- **Completa:** añade PG, PE, PP, GF, GC y racha (5 cuadros de 20×20 con letra G/E/P, según la sección 3.2). Cebra `--gray-50` en filas pares solo en la versión completa. En móvil, la columna de equipo queda fija y el resto se desplaza.
- Sello «Datos presentados por [Transelca]» encima de la tabla (componente `.sponsor-seal` de `04-pauta.md`) y la línea «Datos de ejemplo» en meta debajo.

### 6.10 Tarjeta de partido en vivo

- Blanca, borde de 1 px `--color-border`, radio 0 y relleno de 16. Cabecera: torneo en meta + píldora «EN VIVO» con el minuto.
- Dos filas de 48 px: escudo de 32 + nombre Inter 600 de 16 + goles `--fs-score-lg` a la derecha. Debajo, filete y el último evento («Gol de [jugador] 64'», Inter 400 de 14 px), con el enlace «Minuto a minuto ›» en azul.
- Al terminar, la píldora pasa a «Final» en `--color-text-muted`, sin rojo.

### 6.11 Módulo de radio («La escaleta»)

En portada es un bloque horizontal blanco entre filetes de 1 px (sin fondo azul):

- **≥1024, 3 zonas:** foto del programa en 4:3 (240×180, radio 0) · nombre «El show de las mañanas» (Archivo 700 de 24 px) + «L–V · 10:00 a.m.–12:00 m.» + estado + botón · escaleta del día: 4 filas de 40 px con la hora en Archivo condensado de 18 px tabular azul a la izquierda (64 px de columna) y el tema en Inter de 16. **El tramo en curso** se marca con punto rojo, peso 600 y el texto «Ahora».
- **Al aire:** botón primario «Escuchar en vivo» con icono de play. **Fuera del aire:** botón secundario «Último programa» + «Próxima emisión: mañana, 10:00 a.m.». Sin rojo.
- Móvil: se apila en este orden: nombre, estado, botón, escaleta. La foto se oculta.

### 6.12 Tarjetas de redes

- Retícula de 3×2 (≥768) o lista (móvil). Cada tarjeta mide 88 px de alto, lleva filete de 1 px abajo y no tiene caja: icono de la red de 24 px **monocromo en tinta** (sin los colores de cada red) · nombre de la red y usuario (Inter 600 de 16) · descripción de una línea (14, atenuado) · enlace «Seguir» a la derecha (Inter 600, azul, área de 44 px).
- Redes: YouTube, Shorts, Instagram, X @habladeportes, **X de Juan Salvador** (con foto cuadrada de 48 px, radio 0), TikTok y canal de WhatsApp. Las cifras de seguidores llevan la marca «ejemplo».

### 6.13 Pauta y sello de patrocinio

Se adopta `04-pauta.md` tal cual (3 huecos: `partidos`, `lateral`, `cierre`; rótulo «Publicidad» de 12 px encima de la pieza; colapso si está vacío). Ajustes de forma para este sistema:

- Marco con **radio 0** (no 8 px) y filete de 1 px `--color-border`. Placeholder en `--color-surface-subtle` con el tamaño centrado en meta.
- Equivalencias de tokens: `--ink-3` → `--color-text-muted` · `--line-2` → `--color-border` · `--navy-50` → `--brand-50` · `--navy-700` → `--brand-600` · `--radius-sm` → `--radius-0`.
- El chip «Publicidad» dentro del anuncio del Centro de Partidos pasa a rectangular (radio 2 px).

### 6.14 Pie de página

- `--color-surface-dark`, 64 px arriba y 32 abajo. En móvil se suma `--bottom-nav-h` + `safe-area` abajo.
- Fila 1: logo blanco de 32 px + una línea de descripción (blanco al 80 %) + iconos de redes de 44 px.
- Fila 2: 4 columnas (Noticias · Datos · Multimedia · Habla Deportes). Título Inter 600 de 14 px blanco y enlaces Inter 400 de 15 px blanco al 80 % (filas de 32 px; de 44 px en móvil, a 2 columnas).
- Fila 3: sello «Con el respaldo de» + Transelca en blanco monocromo (28 px), filete `--color-border-on-dark` y aviso legal en 14 px.

### 6.15 Barra inferior móvil (<1024)

- 64 px + `env(safe-area-inset-bottom)`, blanca, con filete superior de 1 px y `--shadow-dock`. Cuatro ítems de igual ancho: **Noticias · Videos · Datos · Radio**, con icono de 24 y rótulo Inter 600 de 12 px.
- **Activo:** icono relleno en azul, rótulo en tinta 700 y barra de 3 px en `--brand-600` arriba del ítem. Inactivo: icono de línea en `--gray-600`.
- **Radio al aire:** punto rojo de 8 px sobre el icono y `aria-label="Radio, en vivo"`.
- `touch-action: manipulation`, sin `-webkit-tap-highlight-color` y respuesta en `:active`.

### 6.16 Botones y estados

| Variante | Reposo | Hover | Presionado | Foco |
|---|---|---|---|---|
| Primario | `--color-action`, texto blanco Inter 600 de 16, alto 48 (44 mín.), relleno lateral de 24, radio 2 px | `--color-action-hover` | `--color-action-press` + `translateY(1px)` | Anillo de 2 px `--color-focus` con separación de 2 |
| Secundario | Blanco, borde de 1 px `--brand-600`, texto azul | Fondo `--brand-50` | `--brand-100` | Igual |
| Enlace de acción | Inter 600 de 15 px, azul, chevron de 16 | Subrayado de 2 px, chevron +2 px | — | Igual |
| Icono circular | 44×44, radio redondo, borde de 1 px `--color-border-field` | Fondo `--gray-100` | `scale(.96)` | Igual |
| Sobre azul u oscuro | Primario blanco con texto `--brand-600`; secundario con borde blanco al 24 % | Fondo blanco al 8 % | — | Anillo blanco |

- **Desactivado:** texto `--color-text-disabled`, fondo `--gray-50`, sin cambio en hover, con `aria-disabled`.
- **Vacío:** frase directa + siguiente paso («Aún no hay Shorts de esta semana. Ver todos en YouTube ›»). Sin dibujos.
- **Cargando:** skeleton con la misma geometría (bloques `--gray-50`) y pulso de opacidad que desaparece con movimiento reducido.
- **Error de datos:** «No pudimos cargar los datos. Reintentar», con botón secundario.
- Los hover van envueltos en `@media (hover: hover) and (pointer: fine)`.
- **Ningún estado depende solo del color:** el activo siempre lleva barra y peso.

---

## 7. Imagen

- **Proporciones fijas:** 16:9 en noticias, videos y programas; 9:16 en Shorts; 1:1 en avatares; 4:3 en la foto del programa en el módulo de radio. Los escudos van en `contain` sobre blanco o azul oscuro y nunca se recortan.
- **Radio 0 en todo**, incluidos los avatares. Sin borde ni sombra: la foto termina en seco contra el blanco.
- **Recorte:** cada foto tiene su `object-position` (por ejemplo, `juansalvador*.png` a 60 % 35 % para no cortar la cara en 16:9 ni en 9:16). Se anota en el HTML.
- **Para que no se noten las repeticiones (hay ~10 fotos útiles):**
  1. **Una foto, una vez por página.** El HTML lleva un inventario y una imagen no se repite en la misma página. Se permite repetirla entre páginas, no dentro de la misma.
  2. **Cuando faltan fotos, se usa la variante sin imagen**, no un recuadro celeste. La lista de Junior y Selección, la opinión y los fichajes son filas de texto con escudos. Son la forma natural de una portada con jerarquía: una principal con foto, tres con foto y el resto en lista.
  3. **Corrección de color uniforme** (`filter: saturate(.95) contrast(1.03)`) para que las fotos de fuentes distintas parezcan de la misma familia. Sin duotono azul: eso volvería a «demasiado azul».
  4. **Las fotos de Juan Salvador se reservan** para lo que es suyo: programa, radio y su perfil. No ilustran noticias de Junior.
- **No usar:** `img-01.png` (es el logo de Diario Deportes), las fotos de Messi y Mbappé (no son del foco editorial) ni la foto del planeta (`efemerides.png`) como imagen de noticia.
- **Peso:** se exporta a WebP en 640 y 1280 px de ancho (`srcset`), ≤150 KB. Hoy hay PNG de 1,8 a 3 MB. Siempre con `width`/`height`, y `loading="lazy"` salvo la principal (`fetchpriority="high"`).
- **Iconos:** un solo set de línea (Phosphor Regular, trazo 1,5, retícula de 24) servido como sprite SVG local. Para las redes sociales se usa su glifo oficial monocromo.

---

## 8. Movimiento

Hay una curva (`--ease-out`), tres duraciones (120, 200 y 320 ms) y **un solo bucle**: el punto en vivo.

| Qué | Cómo | Duración | Para qué |
|---|---|---|---|
| Hover de enlaces, titulares e iconos | Color, subrayado, brillo | 120 ms | Responder a la acción |
| Botón presionado | `translateY(1px)` o `scale(.96)` | 120 ms | Sensación física al tocar |
| Panel de la barra contextual | Opacidad + `translateY(-4px→0)` | 200 ms (tras 120 ms de intención) | Mostrar de dónde sale |
| Hoja inferior móvil y reproductor | `translateY(100%→0)` | 200 ms | Dejar claro que es una capa |
| Pestañas Ayer / Hoy / Mañana | Fundido del contenido | 200 ms | Indicar el cambio de estado |
| Banner de partidos | Fundido entre partidos cada 8 s, en pausa con hover o foco | 320 ms | Rotación sin saltos |
| Punto en vivo | Opacidad 1 → .4 → 1 | Bucle de 1,6 s | La única señal que debe llamar la atención |

- No se animan entradas al hacer scroll, ni parallax, ni zoom de imágenes, ni contadores. El contenido es visible desde el primer momento.
- Solo se animan `transform` y `opacity`.
- **`prefers-reduced-motion: reduce`:** `tokens.css` pone las tres duraciones a 0 y el pulso a 0s (el punto queda fijo). El banner no rota solo (se cambia con las flechas). Los temporizadores de intención del panel se mantienen, porque no son movimiento.

---

## 9. Dónde vive la radio en vivo (reemplazo del botón blanco «Al aire · Escuchar»)

El botón blanco desentonaba porque era un control de reproducción metido en la zona de identidad, con forma y color propios. La propuesta separa **la señal** del **reproductor**, y cada cosa vive donde le corresponde.

1. **La señal está en la navegación y usa su mismo lenguaje.**
   - En escritorio, el ítem «Radio» del menú azul es un ítem más. Entre L–V de 10:00 a 12:00 se le añade la píldora roja «EN VIVO» (12 px, texto blanco, 5,18:1).
   - En móvil, el ítem «Radio» de la barra inferior lleva el punto rojo de 8 px.
   - Fuera del horario no aparece nada. No hace falta un botón: el menú ya avisa.
2. **La entrada está en el contenido.** En la portada, el módulo «La escaleta» (6.11) lleva el único botón «Escuchar en vivo». En `/radio`, el reproductor grande con la parrilla y los episodios anteriores.
3. **El reproductor se acopla abajo solo cuando el usuario da play.** No aparece por defecto.
   - **Escritorio:** barra fija inferior de 64 px, blanca, con filete superior y `--shadow-dock`. Dentro del contenedor van:
     - play/pausa circular de 44 en `--brand-600`;
     - píldora «EN VIVO» + «El show de las mañanas» (Inter 600 de 15 px) + tramo actual de la escaleta (14 px, atenuado);
     - volumen (solo con puntero fino);
     - enlace «Ver programa ›»;
     - cerrar de 44 px (detiene el audio).
   - **Móvil:** franja de 56 px apoyada sobre la barra inferior (`--z-dock`), con play, título truncado y cerrar. El contenido reserva su alto con `padding-bottom`.
   - Sin logo de patrocinador ni pauta dentro del reproductor, en línea con `04-pauta.md`. El crédito del patrocinador va en la ficha del programa.
4. **Limitación técnica (dicha con honestidad):** en un sitio HTML de varias páginas, el audio se corta al cambiar de página. El prototipo recuerda el estado (`sessionStorage`) y vuelve a montar el reproductor en pausa, con «Continuar». En WordPress, la continuidad real exige navegación sin recarga (por ejemplo, Swup o Turbo) o abrir el reproductor en una ventana aparte. Esa decisión se toma en la fase WP.

---

## 10. Lo que NO hacemos

- Fondos azul claro, beige o gris en la página; bloques azules fuera de la cabecera, el banner de partidos y el pie.
- Un segundo color de acento: amarillo de la Selección, verde de WhatsApp, degradado de Instagram, rojo de YouTube. Las redes van en monocromo.
- Rojo para cualquier cosa que no sea «en vivo» (salvo el texto de error de formulario, ver la sección 11).
- Titular encima de la foto, degradados sobre imágenes o el héroe cinematográfico de Diario Deportes.
- Tarjetas con borde y sombra para agrupar, cajas dentro de cajas, sombras en tarjetas.
- Píldoras en botones o campos, radios de 8, 12 o 16 px, avatares circulares.
- Etiquetas superiores en mayúscula sobre las secciones, barras verticales de color junto a los rótulos, iconos decorativos en los títulos de sección.
- Etiquetas de categoría en cajas rellenas; «Exclusivo», «Última hora», tira de marcadores bajo el menú, franja amarilla.
- Fecha y clima en la cabecera; escudos de clubes en el menú.
- Más de 3 tarjetas en una fila de portada fuera de los carriles; más de un carril de Shorts en la portada.
- Recuadros vacíos por falta de foto; la misma foto dos veces en una página; fotos ajenas al fútbol colombiano.
- Animaciones de entrada al hacer scroll, parallax, contadores animados, zoom en hover.
- Pauta flotante, sticky o intercalada en listas (ver `04-pauta.md`).
- Valores sueltos en el CSS: todo color, tamaño, espacio y duración sale de `tokens.css`. Sin `!important` y sin capas «v2…v12».
- Serifas, o una tercera familia tipográfica.

---

## 11. Decisiones del brief: qué mantengo y qué propongo cambiar

**Mantengo todas las de la sección 4**: Archivo + Inter, un solo azul (#294D6E exacto), rojo solo en vivo, fondo blanco, cabecera y menú azules con logo blanco, esquinas rectas, sin héroe cinematográfico, principal horizontal + 3, sin «última hora» ni tira de marcadores, Centro de Partidos bajo las tres noticias, sin el botón blanco de radio, menú de dos niveles sin escudos con barra blanca contextual, barra inferior móvil con 4 ítems, Shorts separados de programas y 3 huecos de pauta.

**Propongo cambiar, con razón:**

1. **Paneles «solo con hover» (decisión 12).** Así incumplen WCAG 2.1.1 (todo debe poder usarse con teclado) y no funcionan en tabletas táctiles de ≥1024 px. Propuesta mínima: el hover se queda como el cliente lo pidió, pero también se abren con el foco del teclado y con un toque en pantallas táctiles. Visualmente no cambia nada. **Mientras no se apruebe:** el disparador es un enlace real a la página de la sección, que lista los mismos enlaces del panel (así hay un camino equivalente), y en móvil se usa la hoja inferior.
2. **Color de error de formulario.** Decir «rojo solo en vivo» literalmente deja sin señal los errores del boletín y del contacto. Propongo `--error-700` (#A8261C), más oscuro y siempre con icono y texto, limitado a mensajes de formulario. Si se rechaza, el error se marca con borde de 2 px en tinta + icono + texto.
3. **Radio de 8 px en `centro-partidos-spec.md` y `04-pauta.md`.** No es un cambio al brief: es aplicar la decisión 5. Banner y marcos de pauta pasan a radio 0.
4. **Módulos de la decisión 18 fuera de la portada.** Encuesta, efemérides, opinión y boletín se mantienen, pero en sus secciones. En la portada, como mucho, una línea de 3 enlaces. Responde a la decisión 11 (no exceso de tarjetas).
