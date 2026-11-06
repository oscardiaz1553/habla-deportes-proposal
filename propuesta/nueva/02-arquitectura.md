# Arquitectura de información y plantillas — Habla Deportes, propuesta nueva

**Rol:** arquitecto de información / estratega de contenido
**Fecha:** 6 de noviembre de 2026
**Insumos:** `00-brief-para-agentes.md` (fuente principal), `diagnostico/03` y `00`, `propuesta/secciones-home-propuesta.md`, `megamenu-contenido.md`, `megamenu-ux.md`, `clasificacion-contenido.md`, `secciones-benchmark.md`, las 11 páginas de `completa-src/pages/` y `build.py`, y los skills (`impeccable/layout`, `ui-ux-pro-max`, `mobile-native`, `web-design-guidelines`; solo lectura).
**Alcance:** estructura, navegación, orden de módulos, plantillas y reglas de contenido. No define el aspecto visual ni escribe HTML.

> **Convención:** `[H]` marca una **hipótesis** que el cliente aún no confirmó (no hay respuestas al cuestionario). Todo dato de partidos, tablas, jugadores, cifras y textos de la propuesta es de ejemplo y debe rotularse "Datos de ejemplo" en el prototipo.

---

## 0. Resumen de decisiones

1. **Se pasa de 11 a 18 páginas** sobre 15 plantillas (varias son variantes de una misma base). 12 páginas son P0, 4 son P1 (mercado, shorts, redes, legales) y 2 son P2 (buscar, 404).
2. **Barra azul: Noticias · Junior FC · Selección · Videos · Datos · Radio · Más ▾** (6 + Más). Se quita "Inicio" (el logo ya lo es) y se saca "Opinión" y "Videos y redes" de los primarios.
3. **Cada ítem de cada barra blanca lleva a un destino distinto y real** (página, filtro `?param=` o ancla definida). Se acaban los ítems que prometen un panel y llevan a la misma página.
4. **La portada pierde módulos y repeticiones**: de 12 bloques a 3 de cabecera + 7 de contenido, con una **columna lateral de datos** (clasificación, próximos partidos, publicidad, mercado) en lugar del bloque "Centro de datos". Regla: **una pieza aparece una sola vez en la portada**.
5. **Radio en vivo**: sin botón en el encabezado. Punto rojo en "Radio" cuando está al aire + **mini-reproductor acoplado abajo que aparece solo cuando el usuario pulsa play** + página Radio como casa del reproductor grande.
6. **Tres huecos de publicidad con nombre y medida fijos (A, B, C)**; el patrocinador del banner de partidos y el sello de Datos son patrocinio, no cuentan como hueco.
7. **Separación definitiva** de Videos (hub YouTube + Shorts) y Redes (directorio de cuentas).

### Desvíos del brief

No hay desvíos de las decisiones firmes (sección 4 del brief). Hay **cinco interpretaciones** que conviene que el dueño confirme:

| # | Interpretación | Por qué |
|---|---|---|
| I1 | El anuncio del sponsor dentro del banner de partidos (decisión 9) se trata como **patrocinio** ("Patrocinado por Transelca"), no como uno de los 3 huecos de publicidad (decisión 17). | Si se contara como hueco, la portada tendría cuatro. |
| I2 | Se **descarta el "sticky vertical 300×600"** que ofrece `contacto.html`. | Contradice "nunca flotantes" (decisión 17). |
| I3 | Los paneles de hover (decisión 12) se mantienen **solo con hover**, pero cada padre de panel es un enlace real a una página que repite los ítems del panel. | Respeta la decisión y evita que teclado y táctil queden sin acceso (riesgo R4). |
| I4 | En móvil se **oculta la barra azul de secciones** (la reemplazan la barra inferior y el menú hamburguesa) y quedan encabezado + barra blanca deslizable. | El brief no dice qué pasa con la barra azul en móvil; tres bandas arriba restarían 144 px de alto útil. |
| I5 | Se **quita la temperatura ("31°C")** del encabezado. | Es un dato inventado sin fuente y ruido en la cabecera. |

---

## 1. Crítica de la arquitectura actual (versión "completa")

| # | Hallazgo | Qué sobra / falta / confunde | Consecuencia |
|---|---|---|---|
| H1 | **"Noticias" no está en la barra azul.** Está en "Más"; la pestaña inferior "Noticias" apunta a la portada; "Inicio" ocupa un primario que el logo ya cubre. | Sobra "Inicio"; falta Noticias y Videos como destinos limpios. | El usuario no encuentra el índice de notas; se desperdicia un ítem primario. |
| H2 | **`seccion.html` es un cajón de sastre**: es la "Selección", pero también aloja Fichajes (`#fichajes`) y Efemérides (`#efemerides`) por ancla, y a la vez sirve de comodín para casi todos los demás enlaces. | Falta un hub de Selección real y una página de Mercado. | Tres temas distintos en una página; enlaces que prometen una cosa y llevan a otra. |
| H3 | **Las barras blancas prometen más de lo que entregan.** Plantilla, Títulos e Ídolos → `equipo.html`; los 5 torneos → `datos.html`; Columnistas, Sus videos y Pregúntale a Juan → `juan-salvador.html`; Cómo escucharnos y Participa → `radio.html` sin ancla. | Sobran ítems sin destino propio. | El usuario hace hover, hace clic y "no pasa nada": rompe la confianza en el menú. |
| H4 | **"Videos y redes" mezcla dos cosas.** La pestaña inferior "Videos" termina en `redes.html`, un directorio de redes con embeds de Instagram, X y TikTok; el hub de video no tiene casa propia. | Falta `videos.html`; sobra el embed de redes sociales en una página de video. | Viola en espíritu la decisión 15 (no mezclar Shorts y programas) y confunde el rol de Videos. |
| H5 | **La portada tiene 12+ módulos y repite las mismas piezas.** El programa de Cuadrado aparece en el principal, en "El show", en radio, en Juan Salvador y en redes; hay tres carriles verticales (Clips, TikTok, "Reacciones"); el bloque "Centro de datos" duplica el banner de partidos y `datos.html`; encuesta, efemérides, boletín y "Participa" son cuatro módulos pequeños sueltos. | Sobran módulos y repeticiones; falta jerarquía. | Muro de bloques, justo lo que el cliente pidió evitar (decisión 11); con pocas fotos, la repetición se nota más. |
| H6 | **Los datos no acompañan al contenido.** No hay columna lateral en notas, listados ni hubs (decisión 14); "Partido en vivo" está enterrado en "Más" y en Datos. | Falta la columna lateral de datos y el acceso directo a lo "vivo". | El hincha que lee una nota no ve la tabla ni el próximo partido sin salir. |
| H7 | **La publicidad no tiene sistema.** Aparecen 970×90, 300×250 y 300×600 en posiciones distintas por página; `contacto.html` vende un "sticky vertical". | Sobran formatos y posiciones libres; falta un inventario con nombre. | Imposible de vender o de mantener; contradice la decisión 17. |
| H8 | **Restos de decisiones ya rechazadas y huecos de páginas utilitarias.** `--f-serif` sigue en 5 páginas (`index`, `equipo`, `juan-salvador`, `radio`, `redes`); el hub de equipo usa un bloque oscuro con degradado (decisión 6); "31°C" en cabecera; el programa se llama "El show de la mañana" y "El show de las mañanas"; el buscador no tiene destino; "Política de privacidad" es `href="#"`; no hay 404. | Falta `buscar`, `404`, `legales`; sobra el degradado. | Enlaces muertos y señales de prototipo inacabado. |

---

## 2. Sitemap completo

### 2.1 Árbol

```
Habla Deportes (index.html)
│
├── NOTICIAS (barra azul: Noticias)
│   ├── noticias.html            Últimas noticias (listado, filtros ?tema=)
│   │   └── articulo.html        Artículo (plantilla; 1 instancia)
│   └── mercado.html             Mercado de fichajes (seguimiento)
│
├── JUNIOR FC
│   └── junior.html              Hub de Junior (anclas #partidos, #plantilla)
│
├── SELECCIÓN COLOMBIA
│   └── seleccion.html           Hub de la Selección (anclas #convocatoria, #partidos)
│
├── VIDEOS
│   ├── videos.html              Hub YouTube: programas completos + carril de Shorts
│   │   └── video.html           Video individual (plantilla; programa completo / episodio)
│   └── shorts.html              Todos los Shorts (cuadrícula vertical; sin página por Short)
│
├── DATOS
│   ├── datos.html               Centro de datos (?t= torneo; #hoy #posiciones #resultados #goleadores #estadisticas)
│   └── partido.html             Partido (previa / en vivo / final)
│
├── RADIO
│   └── radio.html               Radio en vivo + programa "El show de las mañanas" (#parrilla #episodios #escuchar #participa)
│
├── MÁS ▾
│   ├── juan-salvador.html       Perfil y opinión de Juan Salvador Bárcena
│   ├── redes.html               Directorio de redes
│   ├── contacto.html            Quiénes somos · Contacto · Publicidad
│   └── (Mercado, ver arriba)
│
└── UTILITARIAS (fuera de la navegación principal)
    ├── legales.html             Privacidad, términos y cookies (anclas)
    ├── buscar.html              Resultados de búsqueda
    └── 404.html                 Página no encontrada
```

### 2.2 Tabla de archivos

Los archivos van **planos** en `propuesta/html/nueva/` (se abren con doble clic). "Reutilizable" = plantilla que en WordPress se repite para muchos contenidos.

| # | Archivo | Página | Plantilla | Reutilizable | Prioridad | Equivale a (versión anterior) |
|---|---|---|---|---|---|---|
| 1 | `index.html` | Portada | T1 Portada | No | P0 | `index.html` |
| 2 | `noticias.html` | Últimas noticias | T3 Listado editorial | **Sí** (base de mercado, shorts y buscar) | P0 | nuevo (antes `seccion.html`) |
| 3 | `articulo.html` | Artículo | T4 Artículo | **Sí** (cada nota) | P0 | `articulo.html` |
| 4 | `mercado.html` | Mercado de fichajes | T3b variante "seguimiento" | No | P1 | nuevo (antes `seccion.html#fichajes`) |
| 5 | `junior.html` | Junior FC | T2 Hub de equipo | **Sí** (con `seleccion.html`) | P0 | `equipo.html` |
| 6 | `seleccion.html` | Selección Colombia | T2 Hub de equipo | **Sí** | P0 | `seccion.html` |
| 7 | `videos.html` | Videos | T5 Hub de video | No | P0 | nuevo (antes `redes.html#youtube`) |
| 8 | `shorts.html` | Shorts | T6 Cuadrícula vertical | No | P1 | nuevo (antes `redes.html#shorts`) |
| 9 | `video.html` | Video individual | T7 Video | **Sí** (programa completo, episodio) | P0 | `video.html` |
| 10 | `datos.html` | Centro de datos | T8 Datos | No | P0 | `datos.html` |
| 11 | `partido.html` | Partido | T9 Partido | **Sí** (cada partido, 3 estados) | P0 | `partido.html` |
| 12 | `radio.html` | Radio y programa | T10 Radio | No | P0 | `radio.html` |
| 13 | `juan-salvador.html` | Juan Salvador / Opinión | T11 Perfil | No | P0 | `juan-salvador.html` |
| 14 | `redes.html` | Redes | T12 Directorio | No | P1 | `redes.html` (ahora solo directorio) |
| 15 | `contacto.html` | Quiénes somos, contacto y publicidad | T13 Contacto | No | P0 | `contacto.html` |
| 16 | `legales.html` | Legales | T14 Texto | **Sí** (cualquier página de texto) | P1 | nuevo |
| 17 | `buscar.html` | Búsqueda | T3c variante "resultados" | No | P2 | nuevo |
| 18 | `404.html` | No encontrada | T15 Utilitaria | No | P2 | nuevo |

### 2.3 Decisiones de unir, dividir y añadir

| Decisión | Resolución | Por qué |
|---|---|---|
| **Videos y redes** | **Dividir**: `videos.html` (hub de video) y `redes.html` (directorio). | La pestaña "Videos" debe llevar a video; las redes son otra intención (seguir, no ver). Resuelve H4. |
| **Shorts** | **Añadir `shorts.html`** (solo listado). **No** hay página por Short: abre un visor vertical en la misma página. | El carril de la portada y de `videos.html` necesita un "Ver todos" interno. Una página por Short tendría casi cero valor de búsqueda y multiplicaría páginas. |
| **Mercado de fichajes** | **Añadir `mercado.html`** como página propia. | Formato distinto (seguimiento con estados Rumor / Confirmado / Descartado), tema de mayor tráfico recurrente y módulo exigido por el brief (decisión 18). Si falta tiempo: degradar a `noticias.html?tema=mercado`. |
| **Programa ("El show de las mañanas")** | **No** crear `programa.html`: `radio.html` ES el programa (en vivo, parrilla y episodios). | Hoy hay un solo programa; una ficha aparte duplicaría `radio.html`. La plantilla "programa" se crea cuando exista un segundo programa [H]. Cada episodio grabado usa `video.html`. |
| **Selección vs Junior** | **Misma plantilla T2**, dos páginas. | Mismo patrón, contenido distinto; Junior pesa más en módulos (ver T2). |
| **Opinión vs Juan Salvador** | **Unir** en `juan-salvador.html`. | Hoy no hay otros columnistas [H]; un índice de opinión con un solo autor sería una página vacía. Si aparecen más voces, `opinion.html` se crea como listado T3. |
| **Efemérides** | **No** es página; es un módulo en la portada y en `noticias.html#efemerides`. | Contenido de bajo volumen y bajo peso; no merece ítem de menú. |
| **Otros clubes** | **No** hay hubs de Nacional, Millonarios, etc. | El medio gira alrededor de Junior y la Selección (brief §1). Los demás clubes aparecen solo dentro de tablas, resultados y partidos. Se retira el megamenú "Fútbol ▾ > Equipos". |
| **Nosotros** | **Unir** en `contacto.html#nosotros`. | Evita una página casi vacía; sigue sirviendo a anunciantes. |
| **Legales** | Una sola `legales.html` con tres anclas. | Elimina el `href="#"` de privacidad con una sola página. |
| **Tienda** | Fuera de alcance. | Fuera del brief. |
| **Búsqueda y 404** | **Añadir** (P2). | El buscador existe en la cabecera y debe llevar a algún sitio; el 404 evita la página de error genérica del WordPress. |

---

## 3. Navegación

### 3.1 Estructura de bandas (escritorio ≥ 1024 px)

```
Banda 1  Encabezado azul     logo blanco · fecha · iconos de redes · buscar          (64 px, se va con el scroll)
Banda 2  Barra azul          Noticias · Junior FC · Selección · Videos · Datos · Radio · Más ▾   (48 px, fija)
Banda 3  Barra blanca        [Nombre de la sección]  ítem · ítem · ítem · ítem ▾          (48 px, fija, siempre visible)
```

Reglas comunes:

- **Sin escudos de clubes en ninguna barra ni panel.** Los escudos son contenido (banner, hubs, tablas), nunca navegación.
- La **barra azul** marca con `aria-current="page"` la sección que contiene la página. En la portada no se marca nada (el logo es "Inicio").
- La **barra blanca** cambia según la sección; su primer elemento es el nombre de la sección (texto, enlace a su página raíz).
- **Panel (hover):** panel blanco con sombra, una columna de texto, máximo 5 ítems. Se abre solo con hover (retardo 120 ms, cierre 280 ms, con puente invisible, según `megamenu-ux.md`). Cada padre de panel es un enlace real; cada ítem del panel está también como chip o enlace en la página de destino.
- **Móvil/tablet (< 1024 px):** no hay hover. Los padres de panel se convierten en **un enlace simple a su destino** y los ítems del panel no aparecen en la barra (viven en la página destino como chips).
- Al hacer scroll en escritorio, la banda 1 desaparece y las bandas 2 y 3 quedan fijas (96 px en total).
- **Se retira del encabezado** el botón "Al aire · Escuchar" y la temperatura. El indicador de radio se resuelve en la sección 6.
- **El CTA "Suscríbete a YouTube"** no va en el encabezado: vive en la barra blanca de Videos y en los módulos de video. Mantiene el encabezado limpio (decisión 4).

### 3.2 Barra azul (primarios)

| Orden | Ítem | Destino | Notas |
|---|---|---|---|
| 1 | Noticias | `noticias.html` | Marca también: portada (no), artículo, mercado, Juan Salvador. |
| 2 | Junior FC | `junior.html` | Primer hub: el equipo del medio. |
| 3 | Selección | `seleccion.html` | |
| 4 | Videos | `videos.html` | |
| 5 | Datos | `datos.html` | Marca también `partido.html`. |
| 6 | Radio | `radio.html` | Punto rojo cuando hay emisión en vivo (sección 6). |
| 7 | Más ▾ | (sin página propia) | Panel de hover; todos sus ítems están además en el pie. |

**Orden [H]:** Noticias y Junior primero porque el medio es video-first pero con foco editorial en Junior. Si el cliente responde que predominan los videos, Videos sube a la posición 1 o 2.

**Panel "Más ▾"** (una columna):

| Ítem | Destino |
|---|---|
| Mercado de fichajes | `mercado.html` |
| Opinión | `juan-salvador.html` |
| Redes | `redes.html` |
| Quiénes somos | `contacto.html#nosotros` |
| Contacto y publicidad | `contacto.html#publicidad` |

### 3.3 Barras blancas contextuales (contenido exacto)

Leyenda de destino: **P** = página; **F** = filtro por parámetro en la misma plantilla (`?param=`); **A** = ancla dentro de la página; **↗** = enlace externo; **▾** = abre panel con hover.

**NOTICIAS** (en `index.html`, `noticias.html`, `articulo.html`, `mercado.html`, `juan-salvador.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| Portada | `index.html` | P |
| Últimas | `noticias.html` | P |
| Junior | `junior.html` | P |
| Selección | `seleccion.html` | P |
| Mercado | `mercado.html` | P |
| Análisis | `noticias.html?tema=analisis` | F |
| Opinión | `juan-salvador.html` | P |

Junior y Selección están aquí a propósito: son la única vía de un toque a los hubs desde móvil, donde la barra azul no existe. En `articulo.html` ningún ítem queda activo; el contexto lo da la miga de pan.

**JUNIOR FC** (`junior.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| Resumen | `junior.html` | P |
| Noticias | `noticias.html?tema=junior` | F |
| Partidos | `junior.html#partidos` | A |
| Plantilla | `junior.html#plantilla` | A |
| Mercado | `mercado.html?equipo=junior` | F |
| Videos | `videos.html?tema=junior` | F |
| Datos ▾ | padre → `datos.html` | P |

Panel **Datos ▾**: Posiciones → `datos.html#posiciones` · Resultados y calendario → `datos.html#resultados` · Goleadores → `datos.html#goleadores` · Estadísticas → `datos.html#estadisticas`. (Se quita el panel "Historia" de la versión anterior: sus tres ítems no tenían contenido propio. [H] Se recupera si el cliente aporta historia, títulos e ídolos.)

**SELECCIÓN COLOMBIA** (`seleccion.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| Resumen | `seleccion.html` | P |
| Noticias | `noticias.html?tema=seleccion` | F |
| Convocatoria | `seleccion.html#convocatoria` | A |
| Partidos | `seleccion.html#partidos` | A |
| Eliminatorias | `datos.html?t=eliminatorias` | F |
| Videos | `videos.html?tema=seleccion` | F |

Sin panel: con tres o cuatro destinos reales no se justifica uno.

**VIDEOS** (`videos.html`, `shorts.html`, `video.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| Todos | `videos.html` | P |
| Programas completos | `videos.html#programas` | A |
| Shorts | `shorts.html` | P |
| Junior | `videos.html?tema=junior` | F |
| Selección | `videos.html?tema=seleccion` | F |
| Suscríbete en YouTube | canal de YouTube | ↗ (a la derecha, botón de contorno azul) |

**DATOS** (`datos.html`, `partido.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| Hoy | `datos.html#hoy` | A |
| Posiciones | `datos.html#posiciones` | A |
| Resultados | `datos.html#resultados` | A |
| Goleadores | `datos.html#goleadores` | A |
| Estadísticas | `datos.html#estadisticas` | A |
| Partido en vivo | `partido.html` | P (punto rojo solo si hay partido en juego) |
| Torneos ▾ | padre → `datos.html#torneos` | A |

Panel **Torneos ▾**: Liga BetPlay → `datos.html?t=liga` · Copa Colombia → `datos.html?t=copa` · Eliminatorias → `datos.html?t=eliminatorias` · Copa Libertadores → `datos.html?t=libertadores` · Copa Sudamericana → `datos.html?t=sudamericana`. En `datos.html` la misma lista aparece como chips en `#torneos` (es lo que ve el usuario en móvil y con teclado).

**RADIO** (`radio.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| En vivo | `radio.html` | P |
| Parrilla | `radio.html#parrilla` | A |
| Episodios | `radio.html#episodios` | A |
| Cómo escucharnos | `radio.html#escuchar` | A |
| Participa | `radio.html#participa` | A |

**HABLA DEPORTES** (`redes.html`, `contacto.html`, `legales.html`, `buscar.html`, `404.html`)

| Ítem | Destino | Tipo |
|---|---|---|
| Quiénes somos | `contacto.html#nosotros` | A |
| Redes | `redes.html` | P |
| Contacto | `contacto.html#contacto` | A |
| Publicidad | `contacto.html#publicidad` | A |
| Legales | `legales.html` | P |

> `juan-salvador.html` usa además anclas internas (`#columnas`, `#videos`, `#x`, `#pregunta`, `#equipo`) como índice de página en la cabecera del perfil, **no** en la barra blanca (esa es la de Noticias).

### 3.4 Móvil

**Banda superior (móvil):** encabezado azul de 56 px (logo, buscar, hamburguesa) + barra blanca de 44 px **deslizable de lado** con el scroll-snap habitual y degradado en el borde derecho que indica que hay más. No hay barra azul de secciones.

**Menú hamburguesa:** lista vertical de **enlaces simples**, sin acordeones, en dos grupos:

```
Noticias · Junior FC · Selección · Videos · Datos · Radio
───────────────
Mercado de fichajes · Opinión · Redes · Quiénes somos · Contacto y publicidad · Legales
```

**Barra inferior (4 destinos, 56 px + área segura):**

| Pestaña | Destino | Activa en | Comportamiento |
|---|---|---|---|
| Noticias | `index.html` | portada, noticias, artículo, mercado, Junior, Selección, Juan Salvador | Segundo toque estando en la portada: sube al inicio. Se llama "Noticias" porque la portada es el frente de noticias. |
| Videos | `videos.html` | videos, shorts, video | Ya no termina en el directorio de redes. |
| Datos | `datos.html` | datos, partido | Muestra punto rojo si hay un partido en vivo. |
| Radio | `radio.html` | radio | Punto rojo si está al aire; animación de ecualizador si el mini-reproductor está sonando. |

La barra inferior está **siempre visible** (no se oculta al hacer scroll): son solo 4 destinos y es la navegación principal en móvil. Si hay mini-reproductor, se acopla encima de ella (sección 6).

### 3.5 Búsqueda

Icono en la banda 1 (y en el encabezado móvil). Despliega una **franja de búsqueda bajo el encabezado** (no un modal): campo con etiqueta visible, envío a `buscar.html?q=`, y 4 sugerencias fijas (Junior, Selección, Mercado, Radio). Se cierra con Esc o con el icono.

### 3.6 Pie de página

| Columna | Enlaces |
|---|---|
| Noticias | Últimas · Junior · Selección · Mercado · Análisis · Opinión |
| Videos y radio | Videos · Shorts · Radio en vivo · Parrilla · Episodios |
| Datos | Posiciones · Resultados · Goleadores · Partido en vivo |
| Habla Deportes | Quiénes somos · Redes · Contacto · Publicidad · Legales |

Debajo: logo, frase del programa ("El show de las mañanas, de lunes a viernes de 10:00 a.m. a 12:00 m."), iconos de redes (YouTube, Instagram, X, TikTok, WhatsApp), "Con el respaldo de Transelca" y derechos. **El pie contiene todos los ítems de los paneles hover**: es el respaldo de acceso para quien no puede abrir paneles.

---

## 4. Portada (`index.html`): orden y contenido, módulo por módulo

### 4.1 Principios

1. **Dos primeras pantallas = lo que pasa hoy**: el principal, las tres noticias pequeñas y el banner de partidos.
2. **Una pieza, un lugar.** Ninguna pieza aparece dos veces en la portada (ni en otro carril ni en la columna lateral). El gestor de contenido excluye automáticamente lo ya mostrado.
3. **El video manda, los datos acompañan.** [H] Los videos y los datos van en el cuerpo; los datos en una columna lateral que sigue a los módulos principales.
4. **Cada módulo responde una pregunta**: qué pasa, cuándo juega, qué dijo el programa, cómo va Junior, qué más hay, qué se dice en la radio.
5. **Junior pesa más que la Selección** en la portada, aunque ambos tengan módulo. No son espejos exactos.

### 4.2 Orden en escritorio (≥ 1024 px)

```
[Encabezado azul] [Barra azul] [Barra blanca: Noticias]

M1  PRINCIPAL HORIZONTAL  (12 col)  imagen 7 col | texto 5 col
M2  TRES NOTICIAS PEQUEÑAS         [  ] [  ] [  ]
M3  BANNER DE PARTIDOS             [ escudos · fecha · estadio · canal  ||  Patrocinado por ]
    PUBLICIDAD A  970×90
┌─ principal (8 col) ──────────────────────┐ ┌─ lateral (4 col) ────────────┐
│ M4  EL PROGRAMA (videos)                 │ │ L1  Clasificación            │
│ M5  JUNIOR                               │ │ L2  Próximos partidos        │
│                                          │ │ L3  PUBLICIDAD B  300×250    │
└──────────────────────────────────────────┘ └──────────────────────────────┘
M6  SHORTS  (carril vertical, ancho completo)
┌─ principal (8 col) ──────────────────────┐ ┌─ lateral (4 col) ────────────┐
│ M7  SELECCIÓN                            │ │ L4  Mercado de fichajes      │
└──────────────────────────────────────────┘ └──────────────────────────────┘
M8  RADIO  (banda de ancho completo)
M9  OPINIÓN | M10  COMUNIDAD  (dos columnas)
    PUBLICIDAD C  970×90
[Pie]
```

### 4.3 Detalle de módulos

**M1 — Noticia principal horizontal (estilo Sky Sports)**

| Campo | Definición |
|---|---|
| Propósito | La historia del día; decide lo primero que se ve. |
| Contiene | Imagen 16:9 a la izquierda (7 col, esquinas rectas, sin texto encima); a la derecha, chip de formato (Video/Nota) y, solo si aplica, chip rojo **En vivo**; titular (h2, ≤ 90 caracteres); bajada (≤ 140); firma + hora relativa; **hasta 3 "rutas" de la misma historia** (Ver el programa ›, Escuchar en la radio ›, Leer la nota ›) mostrando solo las que existen. |
| Cantidad | 1 pieza. |
| Enlaza | La imagen y el titular van a la pieza principal (`video.html` o `articulo.html`); las rutas a `video.html`, `radio.html`, `articulo.html`. |
| Por qué aquí | Es el primer elemento tras la navegación: la expectativa de un medio de actualidad. Las tres rutas muestran que el medio es multiformato (video, audio, texto) sin repetir la historia en otros módulos. |
| Regla | Curado a mano. Cuando el programa está al aire y es la historia del día, el chip **En vivo** y la ruta "Ver en directo" sustituyen a "Ver el programa". El `h1` de la portada es invisible ("Habla Deportes: portada"); el titular del principal es `h2`. |

**M2 — Tres noticias pequeñas**

| Campo | Definición |
|---|---|
| Propósito | Tres entradas rápidas al resto del día. |
| Contiene | Cada una: imagen 16:10 arriba (sin texto encima), chip de formato, kicker de tema, titular (h3, máximo 2 líneas), hora relativa. Sin bajada. |
| Cantidad | **Exactamente 3** (decisión 7). |
| Enlaza | A su pieza. |
| Por qué aquí | Sky Sports: principal + tres, nada de muro de tarjetas. |
| Regla [H] | Una de las tres es de Junior; una combina formato distinto del principal (si M1 es video, al menos una es nota). Ninguna repite la historia de M1. |

**M3 — Banner de partidos (estilo Centro de Partidos, con sponsor)**

| Campo | Definición |
|---|---|
| Propósito | Responder "¿cuándo juega y cómo va?" sin salir de la portada. |
| Contiene | Banner oscuro; cada diapositiva: competencia y jornada, fecha y hora, **dos escudos grandes**, marcador o "vs", estadio, canal. Estado (En vivo en rojo / Próximo / Final). A la derecha, panel de sponsor ("Patrocinado por" + logo). Controles: flechas y puntos; sin autoplay obligatorio. |
| Cantidad | Máx. 5 diapositivas, en este orden: (1) partido en juego, Junior primero; (2) próximo de Junior; (3) próximo de la Selección; (4) último resultado de Junior; (5) partido destacado de la fecha. |
| Enlaza | Cada diapositiva a `partido.html`; enlace "Todos los partidos ›" a `datos.html#hoy`. |
| Por qué aquí | Debajo de las tres noticias, como pide el brief (decisión 9); cierra el bloque "hoy" antes del cuerpo. Reemplaza la tira de marcadores (descartada). |
| Reglas | Si hay rotación automática: pausa al hover/foco, botón de pausa visible y desactivada con `prefers-reduced-motion`. Todo con la etiqueta "Datos de ejemplo" en el prototipo. El sponsor es patrocinio (I1), no hueco de publicidad. |

**Publicidad A (970×90 / 320×100 en móvil)** — directamente bajo M3. Rótulo "Publicidad".

**M4 — El programa (videos)**

| Campo | Definición |
|---|---|
| Propósito | Poner el producto estrella (video) a un toque, sin volver a mostrar lo que ya está arriba. |
| Contiene | Encabezado "El show de las mañanas" + enlace "Todos los videos ›". Una pieza destacada (último programa completo) y tres programas anteriores en fila. Cada tarjeta: miniatura con botón de play, chip "Video", duración, título, fecha. |
| Cantidad | 4 (1 destacada + 3). Solo **programas completos**: nunca Shorts (decisión 15). |
| Enlaza | Cada tarjeta a `video.html`; el enlace del encabezado a `videos.html#programas`. |
| Por qué aquí | Primer módulo del cuerpo [H: medio video-first]. Si el cliente responde que predominan las noticias, M4 y M5 se intercambian. |
| Regla | Excluye el programa que ya sea M1. Todas las tarjetas se comportan igual (todas a `video.html`; el lightbox se reserva para Shorts). |

**M5 — Junior**

| Campo | Definición |
|---|---|
| Propósito | Dar a Junior el peso que tiene en el medio. |
| Contiene | Encabezado "Junior FC" + "Ir a Junior FC ›". Una pieza destacada con imagen y 4 titulares en lista de **solo texto** (chip de formato + titular + hora). |
| Cantidad | 1 + 4. |
| Enlaza | A cada pieza; el encabezado a `junior.html`. |
| Por qué aquí | Segundo módulo del cuerpo: Junior es el centro editorial (brief §1). |
| Regla | Las listas son de solo texto para no multiplicar imágenes (hay pocas y se repiten). |

**L1 — Clasificación (lateral)**

Mini-tabla Liga BetPlay: top 5 + fila de Junior resaltada si está fuera. Fuente rotulada "Data Factory" y "Datos de ejemplo". Enlaza a `datos.html#posiciones`. Responde la pregunta nº 1 del hincha (`secciones-benchmark.md`). 7 filas máx.

**L2 — Próximos partidos (lateral)**

3 filas: dos de Junior y una de la Selección, con fecha, hora y canal; cada una a `partido.html`; "Ver calendario ›" a `datos.html#resultados`. Se distingue del banner (M3) porque es una lista de consulta, sin escudos grandes; si el banner ya muestra el próximo de Junior, L2 muestra los dos siguientes.

**L3 — Publicidad B (300×250)** — rotulada. En móvil se mueve (4.4).

**M6 — Shorts (carril vertical)**

| Campo | Definición |
|---|---|
| Propósito | Descubrimiento rápido y formato vertical nativo; punto de pausa visual entre bloques de texto. |
| Contiene | Encabezado "Shorts" + "Ver todos ›"; carril horizontal de tarjetas 9:16 con duración y título corto; flechas en escritorio, deslizar en móvil. |
| Cantidad | 8 (6 visibles en 1440 px). |
| Enlaza | "Ver todos" a `shorts.html`; cada Short abre el visor vertical en el sitio (sin página propia). |
| Por qué aquí | Va **después** de los programas para respetar "nunca mezclar" (decisión 15) y a mitad de página para que el carril sea el respiro entre dos bloques editoriales. Se elimina el segundo y tercer carril vertical de la versión anterior (TikTok y "Reacciones en corto"). |

**M7 — Selección Colombia**

| Campo | Definición |
|---|---|
| Propósito | Segundo pilar editorial. |
| Contiene | "Selección Colombia" + "Ir a la Selección ›"; una destacada con imagen y 3 titulares de solo texto. |
| Cantidad | 1 + 3 (menos que Junior a propósito). |
| Enlaza | A cada pieza; el encabezado a `seleccion.html`. |
| Regla [H] | Si no hay pieza de la Selección de menos de 7 días, el módulo **se colapsa** a una sola línea con el próximo partido (no se muestra vacío ni con contenido viejo). |

**L4 — Mercado de fichajes (lateral)**

Lista de 5 filas de texto: fecha, jugador, "origen → destino", chip de estado (Confirmado / Rumor / Descartado). "Ver el mercado ›" a `mercado.html`. Sin imágenes ni escudos. Sustituye al bloque a ancho completo "Temporada de fichajes" de la versión anterior: es consulta rápida, no merece una banda.

**M8 — Radio**

| Campo | Definición |
|---|---|
| Propósito | Convertir al visitante en oyente; es el segundo pilar del medio. |
| Contiene | Banda de ancho completo con dos mitades. Izquierda: estado ("Al aire" en rojo o "Próximo programa: mañana 10:00 a.m."), botón de reproducir (activa el mini-reproductor; sección 6) y "Hoy en la programación" (4 filas). Derecha: **3 episodios anteriores** en lista de audio (botón de play azul sólido + duración larga, para distinguirse de un video). |
| Cantidad | 4 filas de parrilla + 3 episodios. |
| Enlaza | Episodios a `video.html`; "Parrilla completa ›" a `radio.html#parrilla`; "Todos los episodios ›" a `radio.html#episodios`. |
| Por qué aquí | Cierra el cuerpo con una conversión (escuchar) antes de las secciones secundarias. Fusiona los dos bloques de la versión anterior (radio en vivo + "Radio a la carta") y quita "Participa", que vive solo en `radio.html#participa`. |

**M9 — Opinión** (columna izquierda de la penúltima fila)

Foto de Juan Salvador (rectangular), su nombre, y 3 titulares de columnas o reacciones. Enlaza a `juan-salvador.html#columnas`. **Primer módulo a recortar si la portada se siente pesada** (ver 4.6).

**M10 — Comunidad** (columna derecha de la penúltima fila)

Tres bloques pequeños apilados: **Encuesta del hincha** (una pregunta, 3 opciones; el resultado aparece al votar); **Boletín + canal de WhatsApp** (campo de correo y botón "Seguir el canal"); **Un día como hoy** (una efeméride con fecha, enlazada a `noticias.html#efemerides`). Sustituye a las cuatro piezas sueltas anteriores (encuesta, efemérides, boletín, "Participa") y al bloque de embeds de redes; las redes pasan a una fila de iconos en el pie y a `redes.html`.

**Publicidad C (970×90 / 320×100 en móvil)** — sobre el pie. Rótulo "Publicidad".

### 4.4 Móvil (< 768 px): módulos y orden

```
1  M1  Principal (imagen arriba, texto abajo; solo la ruta principal + 1 secundaria)
2  M2  Tres noticias (filas: miniatura 96 px a la izquierda, texto a la derecha)
3  M3  Banner de partidos (se desliza; el sponsor pasa a una línea bajo el banner)
4  Publicidad A  (320×100)
5  M4  El programa (1 destacada + carril horizontal de 3)
6  M5  Junior (destacada + 3 titulares)
7  L1+L2  Tarjeta única "Clasificación y próximos partidos" (5 filas + 3 partidos, plegable)
8  M6  Shorts (carril)
9  M7  Selección (destacada + 3 titulares)
10 Publicidad B  (300×250)
11 L4  Mercado (5 filas)
12 M8  Radio (estado + botón + enlace a la parrilla; sin lista de parrilla ni episodios)
13 M10 Comunidad (encuesta + boletín/WhatsApp; sin efemérides)
14 Publicidad C  (320×100)
15 Pie
```

**No aparece en móvil:** "Hoy en la programación" y los episodios de M8 (están a un toque en `radio.html`), el módulo M9 Opinión (se llega por el menú) y la efeméride. Sin pérdida de funciones: todo sigue accesible desde la barra inferior, el menú o el pie. Cada módulo conserva su encabezado con "Ver todos ›".

### 4.5 Qué se descarta de la portada anterior

| Módulo anterior | Decisión | Motivo |
|---|---|---|
| Tira de marcadores bajo el menú | Ya descartada por el brief | Decisión 9. |
| Bloque "Centro de datos" a ancho completo | **Eliminado**; sus datos van a L1/L2 | Duplicaba el banner de partidos y `datos.html`. |
| "Clips" + carril de TikTok + "Reacciones" | **Un solo carril** (M6) | Tres carriles verticales repiten el mismo tipo de pieza. |
| "Lo que pasa en las redes" (embeds) | **Eliminado** | Pesado, repetitivo y de bajo valor editorial; las redes están en el encabezado, el pie y `redes.html`. |
| Radio en vivo + Radio a la carta + Participa | **Fusionados en M8** | Un solo módulo con una sola conversión. |
| Encuesta + Un día como hoy + Boletín | **Fusionados en M10** | Evitan tres paneles sueltos. |
| Mercado a ancho completo | **Pasa a lateral (L4)** | Es una lista de consulta rápida. |

### 4.6 Orden de recorte si el cliente pide menos

Quitar en este orden: (1) Efemérides de M10, (2) M9 Opinión, (3) L4 Mercado, (4) Encuesta. **No recortar** M1–M3, M4, M5, M8 ni L1/L2.

---

## 5. Plantillas por tipo de página (wireframes)

**Elementos fijos en todas las plantillas:** salto al contenido; encabezado azul; barra azul; barra blanca; `main`; Publicidad C; pie; barra inferior móvil; mini-reproductor de radio (cuando está sonando).

**Los tres huecos de publicidad:**

| Hueco | Formato escritorio | Formato móvil | Rotulado |
|---|---|---|---|
| **A, Superior** | 970×90 (ancho completo) | 320×100 | "Publicidad" |
| **B, Lateral** | 300×250 (columna lateral) | 300×250 en el flujo | "Publicidad" |
| **C, Cierre** | 970×90 (ancho completo, sobre el pie) | 320×100 | "Publicidad" |

Reglas: los huecos reservan su alto desde el inicio (sin saltos de maquetación); no hay sticky, flotantes ni intercalados entre párrafos; el "sello de patrocinio" de Datos es un texto sobrio ("Datos presentados por Transelca"), no un hueco.

### T1 — Portada (`index.html`)

Ver la sección 4. **Fijo:** M1–M3, banner, A, lateral, C. **Varía:** las piezas, el orden del banner, qué módulos se colapsan por falta de contenido reciente.
**Publicidad:** A bajo el banner · B en lateral bajo L2 (móvil: tras Selección) · C sobre el pie.

### T2 — Hub de equipo (`junior.html`, `seleccion.html`)

```
Cabecera blanca (sin bloque oscuro ni degradado)
  [escudo 96 px]  h1 "Junior FC"  · "Liga BetPlay · puesto 3 (ejemplo)"        [Próximo partido: tarjeta]
Destacado horizontal (mismo componente que M1) + lista de 5 titulares de texto
[PUBLICIDAD A]
┌─ principal ───────────────────────────┐ ┌─ lateral ──────────────────────┐
│ Videos del equipo (carril de 4)       │ │ Clasificación (±2 del equipo)  │
│ #partidos  Resultados (5) + Calendario│ │ Próximos partidos (3)          │
│ #plantilla Plantilla por posición     │ │ PUBLICIDAD B                   │
│            (Selección: #convocatoria) │ │ Mercado del equipo (4)         │
└───────────────────────────────────────┘ └────────────────────────────────┘
[PUBLICIDAD C]
```

- **Fijo:** cabecera, componente de destacado, lateral, orden.
- **Varía:** `junior.html` incluye **#plantilla** (porteros, defensas, medios, delanteros; nombre y dorsal; marcar "ejemplo") y el módulo de mercado propio; `seleccion.html` sustituye la plantilla por **#convocatoria** (lista de convocados por posición, con club) [H: depende de que Data Factory o la redacción la provea] y muestra el calendario de Eliminatorias/amistosos.
- **Peso:** Junior lleva 5 titulares y mercado; Selección, 4 y sin mercado.

### T3 — Listado editorial (`noticias.html`)

```
h1 "Noticias"  + una línea de descripción
Filtros (chips): Todo · Notas · Videos · Junior · Selección · Análisis · Mercado        (mismo dato que ?tema=)
Destacado horizontal (componente M1)
[PUBLICIDAD A]
┌─ principal ─────────────────────────────┐ ┌─ lateral ─────────────────────┐
│ Lista densa (10 piezas): miniatura 16:9 │ │ Clasificación (top 5)         │
│ a la izquierda, chip + kicker + titular │ │ Próximos partidos (3)         │
│ + bajada de una línea + fecha           │ │ PUBLICIDAD B                  │
│ "Cargar más"                            │ │ Lo más leído (5, solo texto)  │
│                                         │ │ #efemerides Un día como hoy   │
└─────────────────────────────────────────┘ └───────────────────────────────┘
[PUBLICIDAD C]
```

- **Fijo:** filtros, lista densa en filas (no cuadrícula de tarjetas), lateral.
- **Varía:** el filtro activo cambia el h1 ("Noticias de Junior") y la miga; las piezas.
- **Variantes:**
  - **T3b `mercado.html`:** filtros de estado (Todos · Confirmados · Rumores · Descartados) y filas de seguimiento: fecha, jugador, origen → destino, chip de estado (confirmado = azul sólido; rumor = azul de contorno; descartado = gris, **nunca rojo**), fuente y enlace a la nota. Sin miniaturas.
  - **T3c `buscar.html`:** barra con la consulta, "N resultados para …", chips de formato (Todo · Notas · Videos · Shorts · Episodios) y filas iguales a T3. Estado vacío con sugerencias.
  - **T6 `shorts.html`:** cuadrícula 9:16 (5 columnas en escritorio, 2 en móvil), chips (Todos · Junior · Selección · Reacciones). Publicidad A tras la segunda fila, C al final; sin lateral.

### T4 — Artículo (`articulo.html`)

```
Miga: Inicio › Noticias › Junior                       (en móvil: "‹ Noticias")
kicker de tema  ·  h1 (≤ 90 car.)  ·  bajada
Firma · fecha · "6 min de lectura" · compartir (WhatsApp, X, copiar enlace)
┌─ cuerpo (680 px máx.) ──────────────────┐ ┌─ lateral ─────────────────────┐
│ Imagen o video principal 16:9 + pie     │ │ PUBLICIDAD B                  │
│ Texto con intertítulos h2               │ │ Clasificación (top 5)         │
│ Caja "Datos del partido/jugador"        │ │ Próximos partidos (3)         │
│  (Data Factory, opcional)               │ │ Lo más leído (5)              │
│ Video relacionado (1, tras el 3.er      │ │                               │
│  párrafo; si la nota tiene video)       │ │                               │
│ Etiquetas (tema, equipo)                │ │                               │
└─────────────────────────────────────────┘ └───────────────────────────────┘
[PUBLICIDAD A]
"Te puede interesar" (3 filas)  ·  Comentarios (ver riesgos)
[PUBLICIDAD C]
```

- **Fijo:** estructura, caja de datos, lateral.
- **Varía:** si la nota es una crónica, entra el módulo "Minuto a minuto" resumido; si es opinión, entra la firma con foto.
- **Móvil:** B va tras el segundo intertítulo; A, tras el cuerpo; el lateral desaparece (su contenido se convierte en "Te puede interesar").
- **Comentarios:** [H] recomendación = no tener comentarios propios (moderación, spam); el prototipo puede mostrarlos como opción, rotulados "opcional".

### T5 — Hub de video (`videos.html`)

```
h1 "Videos"            [chips: Todos · Junior · Selección]  (?tema=)
Destacado: último programa (componente M1)
[PUBLICIDAD A]
#programas  "Programas completos": cuadrícula 3×2 (6) · "Más en YouTube ↗"
Aviso breve: suscripción a YouTube (botón de contorno)  ┌ PUBLICIDAD B (a la derecha en escritorio) ┐
Shorts: carril 9:16 (8)  ·  "Ver todos ›" → shorts.html
[PUBLICIDAD C]
```

- **Fijo:** programas y Shorts en bloques separados (decisión 15); regla de no repetición: lo que sale en el destacado no sale en la cuadrícula.
- **Varía:** filtro `?tema=` oculta las piezas de otros temas y cambia el h1.
- **Sin lateral de datos:** es una página de consumo de video; el espacio se reserva para el contenido.

### T7 — Video individual (`video.html`)

```
Miga: Inicio › Videos › Programas completos
┌─ principal ─────────────────────────────┐ ┌─ lateral ─────────────────────┐
│ Reproductor 16:9 (embed YouTube, con    │ │ Siguientes videos (5)         │
│  portada y carga diferida)              │ │ PUBLICIDAD B                  │
│ h1 · fecha · duración · etiquetas       │ │                               │
│ Botones: Suscríbete · Compartir ·       │ │                               │
│  (si es episodio) Solo audio            │ │                               │
│ Descripción                             │ │                               │
│ Capítulos (con marcas de tiempo)        │ │                               │
│ Transcripción resumida                  │ │                               │
│ Nota relacionada (1)                    │ │                               │
└─────────────────────────────────────────┘ └───────────────────────────────┘
[PUBLICIDAD A]
Shorts del tema (carril de 4)
[PUBLICIDAD C]
```

- **Fijo:** reproductor arriba a la izquierda, siguientes a la derecha.
- **Varía:** "episodio de radio" añade el botón "Solo audio" y la miga "Radio › Episodios"; "Short" **no** usa esta plantilla (visor vertical).
- **Comentarios:** se omiten; se enlaza a YouTube ("Comentar en YouTube ↗") [H].

### T8 — Centro de datos (`datos.html`)

```
h1 "Todo el fútbol colombiano, en una sola pantalla"      [sello: Datos presentados por Transelca]
#hoy        Franja por día: [Ayer] [Hoy] [Mañana]  → tarjetas de partido agrupadas por torneo
#torneos    Chips: Liga BetPlay · Copa Colombia · Eliminatorias · Libertadores · Sudamericana   (?t=)
┌─ principal ─────────────────────────────┐ ┌─ lateral ─────────────────────┐
│ #posiciones  Tabla (20 filas, zonas,    │ │ Próximo partido de Junior     │
│              Junior resaltado)          │ │ Racha de Junior               │
│ #resultados  Jornada con selector       │ │ Tabla del descenso            │
│ #goleadores  Top 10                     │ │ PUBLICIDAD B                  │
│ #estadisticas Promedios (rankings)      │ │                               │
└─────────────────────────────────────────┘ └───────────────────────────────┘
[PUBLICIDAD C]
```

- **Fijo:** orden de las anclas, rótulo de fuente "Data Factory" y "Datos de ejemplo" en cada panel, hora de actualización.
- **Varía:** `?t=` cambia el conjunto de datos y el h1 secundario; las pestañas internas siguen el hash.
- **Publicidad:** A tras `#resultados` (a ancho completo, fuera de la columna principal); B en lateral; C al final. El sello de patrocinio es un texto, no un hueco.
- **Accesibilidad:** los chips de `#torneos` son enlaces reales; son el equivalente del panel "Torneos ▾".

### T9 — Partido (`partido.html`)

```
Miga: Inicio › Datos › Liga BetPlay › Junior vs Nacional
Cabecera de marcador: escudos grandes · marcador · minuto · chip de estado · estadio · canal
Pestañas: Minuto a minuto · Alineaciones · Estadísticas
[PUBLICIDAD A]
┌─ principal ─────────────────────────────┐ ┌─ lateral ─────────────────────┐
│ Cronología / alineaciones / estadísticas│ │ Datos del partido (árbitro,   │
│                                         │ │  estadio, canal) · Data Factory│
│ Previa o crónica (nota enlazada)        │ │ Cómo vamos en la tabla        │
│ Video relacionado (programa)            │ │ PUBLICIDAD B                  │
└─────────────────────────────────────────┘ └───────────────────────────────┘
[PUBLICIDAD C]
```

- **Estados:** `previa` (cuenta regresiva, alineaciones probables, "Dónde verlo"), `en vivo` (chip rojo, cronología que se actualiza), `final` (crónica y estadísticas). El mismo archivo sirve para los tres con `data-estado`.
- **Fijo:** cabecera de marcador y pestañas. **Varía:** todo el cuerpo según el estado.
- La publicidad no desplaza la cronología en vivo: A tiene su alto reservado.

### T10 — Radio y programa (`radio.html`)

```
Banda de estado:  [Al aire · L–V 10:00 a.m.–12:00 m.]  o  [Próximo programa: mañana 10:00 a.m.]
h1 "El show de las mañanas"          Reproductor grande (fuente del mini-reproductor)
"Ahora" y "A continuación"
[PUBLICIDAD A]
┌─ principal ─────────────────────────────┐ ┌─ lateral ─────────────────────┐
│ #episodios  Lista de episodios (8) con  │ │ #parrilla  Hoy (4 filas)      │
│  play azul sólido, duración, fecha      │ │ PUBLICIDAD B                  │
│  "Cargar más"                           │ │                               │
└─────────────────────────────────────────┘ └───────────────────────────────┘
#parrilla (semana) · #escuchar (cómo escucharnos) · #participa (tu voz al aire)
[PUBLICIDAD C]
```

- **Fijo:** estado + reproductor. **Varía:** la banda de estado, que cambia por hora.
- `#escuchar` [H]: lista los medios reales de escucha (la web, YouTube en vivo, dial/frecuencia, aplicación). Hasta tener esos datos se rotula "por confirmar".
- Un episodio individual usa T7.

### T11 — Perfil y opinión (`juan-salvador.html`)

```
Cabecera: foto rectangular · h1 "Juan Salvador Bárcena" · rol · botones (X, YouTube, Instagram)
Índice interno: Columnas · Videos · En X · Pregúntale · Equipo
#columnas   3 columnas recientes (lista)
[PUBLICIDAD A]
#videos     Sus mejores videos (carril de 4)
#x          Tres publicaciones seleccionadas (tarjetas estáticas) + "Abrir en X ↗"
#pregunta   Formulario "Pregúntale a Juan"
#equipo     Equipo de Habla Deportes
[PUBLICIDAD C]
```

- Se elimina el carril de "Reacciones en corto": enlaza a `shorts.html` en su lugar.
- `#x`: tarjetas estáticas, no el embed de X (peso, cookies y bloqueo de navegadores) [H].

### T12 — Directorio de redes (`redes.html`)

```
h1 "Redes"
6 tarjetas: YouTube · Shorts (→ shorts.html) · Instagram · X (→ perfil de Juan en juan-salvador.html#x) · TikTok · WhatsApp
Cada tarjeta: icono · @usuario · qué publicamos ahí · cifra de seguidores (ejemplo) · 3 publicaciones recientes (miniaturas estáticas) · botón "Seguir ↗"
[PUBLICIDAD A tras la tercera tarjeta]
[PUBLICIDAD C]
```

Anclas `#youtube #instagram #x #tiktok #whatsapp`. Sin embeds de terceros.

### T13 — Contacto y publicidad (`contacto.html`)

```
h1 "Habla Deportes"
#nosotros   Quiénes somos (3 líneas) + qué hacemos (video, radio, datos) + equipo
#contacto   Formulario (nombre, correo, asunto, mensaje) + info@habladeportes.co + Barranquilla
#publicidad Inventario: A Superior · B Lateral · C Cierre (cada uno con medida y dónde aparece)
            + Patrocinios: banner de partidos, sello de Datos, programa [H]
            + Botón "Escríbenos para pautar" (correo)
```

Sin publicidad en esta página. No incluye el "sticky vertical" (I2).

### T14 — Página de texto (`legales.html`)

`h1 "Legales"` + índice con anclas `#privacidad`, `#terminos`, `#cookies`; texto en columna de 680 px. Debe incluir la política de tratamiento de datos personales (Ley 1581 de 2012), porque habrá formularios y boletín. Publicidad C al final.

### T15 — Utilitaria (`404.html`)

`h1 "No encontramos esa página"` · campo de búsqueda · 4 enlaces (Portada, Junior FC, Videos, Radio) · 3 piezas recientes. Sin publicidad.

---

## 6. Dónde vive el reproductor de radio en vivo

### 6.1 Principio

El botón blanco del encabezado desentonaba porque mezclaba un control de acción con la navegación. La radio se resuelve con **tres capas de menor a mayor presencia**, ninguna en la banda 1:

| Capa | Dónde | Qué es |
|---|---|---|
| 1. Señal | "Radio" en la barra azul y en la barra inferior móvil | **Punto rojo** cuando el programa está al aire (único uso del rojo aparte de "En vivo"). Sin texto, sin botón. |
| 2. Invitación | Módulo M8 de la portada y banda de estado en `radio.html` | Estado ("Al aire" / "Próximo programa") y botón de reproducir. |
| 3. Control | **Mini-reproductor acoplado** | Aparece **solo después** de que el usuario pulsa reproducir; acompaña la navegación. |

### 6.2 Mini-reproductor

- **Ubicación:** escritorio, acoplado abajo a la izquierda (barra de 56 px, 360 px de ancho); móvil, barra a todo el ancho de 56 px **justo encima de la barra inferior**. Nunca cubre el contenido: el `body` suma ese alto de padding mientras está activo.
- **Contenido:** reproducir/pausar (44 px), "Al aire · El show de las mañanas" (o el título del episodio), ecualizador animado (estático con `prefers-reduced-motion`), volumen (solo escritorio), "Abrir" → `radio.html`, cerrar (detiene y retira).
- **Sin autoplay, nunca.** Solo se inicia por acción explícita.

### 6.3 Estados

| Estado | Visible | Comportamiento |
|---|---|---|
| Fuera de aire (fuera de L–V 10:00–12:00) | Sin punto rojo; M8 y `radio.html` muestran "Próximo programa: mañana 10:00 a.m." y "Escuchar el último episodio" | El botón reproduce el último episodio y el mini-reproductor lo rotula como episodio, no como "en vivo". |
| Al aire, sin reproducir | Punto rojo en Radio; M8 y `radio.html` con chip rojo "Al aire" | Nada suena. |
| Cargando | Mini-reproductor con indicador | Texto "Conectando…". |
| Reproduciendo | Mini-reproductor activo; ecualizador en la pestaña Radio | Persiste entre páginas. |
| Pausado | Mini-reproductor con "Reanudar" | En directo: al reanudar se salta al momento actual. |
| Error | Mensaje "No pudimos conectar. Reintentar" | Reintento manual; enlace a YouTube en vivo como alternativa [H]. |

### 6.4 Comportamiento entre páginas

- En el prototipo (páginas estáticas) el estado `{reproduciendo, fuente}` se guarda en `sessionStorage` y el mini-reproductor se **reinstancia** en cada página; en un directo la reconexión es casi imperceptible. Si el almacenamiento no está disponible, la página funciona sin él.
- En `radio.html` el mini-reproductor se oculta (el reproductor grande es la fuente); al salir, reaparece con el mismo estado.
- Si el usuario inicia un video (YouTube) mientras suena la radio, la radio se pausa (un solo sonido a la vez).
- **WordPress (Fase 2):** para no cortar el audio entre páginas hace falta una navegación sin recarga (un solo reproductor fuera del contenido que cambia) o un `iframe` persistente; hay que decidirlo antes de construir el tema (riesgo R5).
- `aria-live="polite"` para los cambios de estado; el mini-reproductor es una región con nombre ("Radio en vivo").

---

## 7. Reglas de contenido

### 7.1 Etiquetas

Cuatro ejes independientes; **máximo dos etiquetas visibles por tarjeta** (formato + tema). La portada y los hubs añaden un chip de estado solo en el principal y en el banner.

| Eje | Valores | Cardinalidad | Dónde se ve |
|---|---|---|---|
| **Formato** | Video · Short · Nota · Audio (episodio) | 1 obligatorio | Chip sobre la miniatura. **El botón de play va solo en Video, Short y Audio; nunca en una Nota** (`clasificacion-contenido.md`). El play de Audio es azul sólido con onda y duración larga. |
| **Tema** (kicker) | Junior · Selección · Liga BetPlay · Copa Colombia · Internacional · Mercado · Radio | 1 principal, hasta 2 secundarias | Texto sobre el titular. |
| **Género** (solo notas) | Análisis · Opinión · Crónica · Previa | 0 o 1 | En el artículo y el filtro "Análisis". |
| **Estado** | **En vivo** · Exclusivo · Confirmado · Rumor · Descartado | 0 o 1 | Chips de estado. Solo "En vivo" va en rojo; Confirmado = azul sólido; Rumor = azul de contorno; Descartado = gris. |

### 7.2 Redacción

- Titulares en frase normal, sin mayúsculas sostenidas ni signos de exclamación dobles (los títulos actuales de YouTube están en mayúsculas). ≤ 90 caracteres; bajada ≤ 140.
- Fechas: relativas hasta 24 h ("Hace 2 horas"); después "6 nov 2026".
- Datos de ejemplo: siempre rotulados. Ningún hecho sobre personas reales presentado como cierto.
- El programa se llama **"El show de las mañanas"** en todo el sitio (hoy hay dos grafías).

### 7.3 URLs

En el prototipo los archivos son planos. La nomenclatura destino (WordPress, **sin fechas, sin tildes, en minúsculas y con guiones, máximo 6 palabras**):

| Prototipo | URL en WordPress | Tipo de contenido |
|---|---|---|
| `index.html` | `/` | Página de inicio |
| `noticias.html` | `/noticias/` | Archivo de entradas |
| `articulo.html` | `/noticias/{slug}/` | Entrada (`post`) |
| `mercado.html` | `/mercado/` | Archivo con taxonomía `tema = mercado` |
| `junior.html` | `/junior/` | Página de equipo (taxonomía `equipo = junior`) |
| `seleccion.html` | `/seleccion-colombia/` | Página de equipo |
| `videos.html` | `/videos/` | Archivo CPT `video`, formato `programa` |
| `video.html` | `/videos/{slug}/` | CPT `video` |
| `shorts.html` | `/shorts/` | Archivo CPT `video`, formato `short` |
| `datos.html` | `/datos/` y `/datos/{torneo}/` | Página + parámetro de torneo |
| `partido.html` | `/partido/{local}-{visitante}-{aaaa-mm-dd}/` | CPT `partido` (alimentado por Data Factory) |
| `radio.html` | `/radio/` | Página; episodios como CPT `video` formato `audio` |
| `juan-salvador.html` | `/opinion/juan-salvador/` | Página de autor |
| `redes.html` | `/redes/` | Página |
| `contacto.html` | `/contacto/` | Página |
| `legales.html` | `/legales/` | Página |
| `buscar.html` | `/?s={consulta}` | Plantilla de búsqueda |
| `404.html` | (plantilla 404) | Plantilla |

Filtros de listado: `?tema=junior|seleccion|analisis|mercado`, `?estado=confirmados|rumores|descartados`, `?equipo=junior`, `?t=liga|copa|eliminatorias|libertadores|sudamericana`.

### 7.4 Migas de pan

- **Solo en profundidad ≥ 2** (artículo, video, partido, filtros). Las páginas raíz de sección no llevan miga: la barra blanca ya dice dónde está el usuario.
- Formato: `Inicio › Sección › Subsección` (la pieza actual no se repite: es el h1).
  - Artículo: `Inicio › Noticias › Junior`
  - Video: `Inicio › Videos › Programas completos`
  - Episodio: `Inicio › Radio › Episodios`
  - Partido: `Inicio › Datos › Liga BetPlay`
- Móvil: se reduce a un enlace "‹ Noticias" (un nivel).
- Se acompaña de `BreadcrumbList` en los datos estructurados.

### 7.5 Piezas por módulo

| Módulo / lugar | Escritorio | Móvil |
|---|---|---|
| Principal (M1) | 1 | 1 |
| Noticias pequeñas (M2) | 3 | 3 |
| Banner de partidos (M3) | 5 diapositivas máx. | 5 |
| Programa (M4) | 4 (1+3) | 1+3 (carril) |
| Junior (M5) | 1+4 | 1+3 |
| Selección (M7) | 1+3 | 1+3 |
| Shorts (M6) | 8 | 8 |
| Clasificación (L1) | 5–7 filas | 5 |
| Próximos partidos (L2) | 3 | 3 |
| Mercado (L4) | 5 | 5 |
| Parrilla de hoy (M8) | 4 | 0 |
| Episodios en la portada (M8) | 3 | 0 |
| Opinión (M9) | 3 | 0 |
| Listado (T3) | 10 + "Cargar más" | 10 + "Cargar más" |
| Cuadrícula de programas (T5) | 6 | 6 |
| Shorts (T6) | 15 | 15 |
| "Te puede interesar" | 3 | 3 |

### 7.6 Cómo se marca "en vivo"

- **Un único color y un único componente:** chip rojo con punto y el texto "En vivo". El rojo no se usa para nada más.
- **Fuentes de verdad:** partido = estado de Data Factory; radio = horario L–V 10:00–12:00 [H] (más, si existe, la señal del stream); video = campo manual "en directo" en la entrada.
- **Dónde aparece:** chip en M1 (si la historia principal es el directo), diapositiva del banner, `partido.html`, banda de `radio.html`, punto en "Radio" (barra azul e inferior) y en "Partido en vivo" (barra de Datos), y en la tarjeta del video si lo es.
- **Nunca** se usa "en vivo" como sinónimo de "reciente": para eso está la hora relativa. No hay tira de "última hora" (decisión 8).
- **Si coinciden dos directos** (programa y partido): el banner pone primero el partido; M1 se queda con la historia del día; el resto sigue su propia señal. No más de un chip rojo por tarjeta.
- **Al terminar:** el chip desaparece, la pieza pasa a "Final"/"Disponible en video".
- **Degradación:** si Data Factory no responde, el partido se muestra "Sin datos en este momento" en gris, nunca como "en vivo".

---

## 8. Mapa de enlaces

### 8.1 Enlaces globales (presentes en todas las páginas)

| Elemento | Destinos |
|---|---|
| Logo | `index.html` |
| Iconos de redes (encabezado) | `redes.html#youtube`, `#instagram`, `#x`, `#whatsapp` |
| Buscar | `buscar.html?q=` (franja de búsqueda); sugerencias → `buscar.html?q=junior`, `?q=seleccion`, `?q=mercado`, `?q=radio` |
| Barra azul (escritorio) | `noticias.html`, `junior.html`, `seleccion.html`, `videos.html`, `datos.html`, `radio.html`; panel Más → `mercado.html`, `juan-salvador.html`, `redes.html`, `contacto.html#nosotros`, `contacto.html#publicidad` |
| Barra blanca | Según 3.3 |
| Menú hamburguesa y barra inferior | Según 3.4 |
| Pie | Según 3.6; "Transelca" → sin destino (texto) hasta tener URL del sponsor |
| Mini-reproductor | "Abrir" → `radio.html` |

### 8.2 Enlaces propios de cada página

| Página | Enlaza a |
|---|---|
| `index.html` | `video.html` (M1, M2, M4, M8 episodios) · `articulo.html` (M1, M2, M5, M7, M9) · `radio.html` (M1 ruta; M8) · `radio.html#parrilla` · `radio.html#episodios` · `partido.html` (M3, L2) · `datos.html#hoy` · `datos.html#posiciones` (L1) · `datos.html#resultados` (L2) · `videos.html#programas` (M4) · `junior.html` (M5) · `seleccion.html` (M7) · `shorts.html` (M6) · `mercado.html` (L4) · `juan-salvador.html#columnas` (M9) · `noticias.html#efemerides` (M10) · `redes.html#whatsapp` (M10) · `contacto.html#publicidad` (rótulo "Publicidad") |
| `noticias.html` | `articulo.html` · `video.html` · `noticias.html?tema=…` · `datos.html#posiciones` · `partido.html` · `junior.html` · `seleccion.html` |
| `articulo.html` | `noticias.html` y `noticias.html?tema=junior` (miga) · `video.html` · `partido.html` · `datos.html#posiciones` · `articulo.html` (relacionados) · `junior.html` / `seleccion.html` (etiquetas) |
| `mercado.html` | `articulo.html` · `junior.html` · `seleccion.html` · `mercado.html?estado=…` · `noticias.html` (miga) |
| `junior.html` | `articulo.html` · `video.html` · `videos.html?tema=junior` · `noticias.html?tema=junior` · `partido.html` · `datos.html#posiciones` · `datos.html#resultados` · `mercado.html?equipo=junior` |
| `seleccion.html` | `articulo.html` · `video.html` · `videos.html?tema=seleccion` · `noticias.html?tema=seleccion` · `partido.html` · `datos.html?t=eliminatorias` |
| `videos.html` | `video.html` · `shorts.html` · `junior.html` y `seleccion.html` (filtros por parámetro en la misma página) · canal de YouTube ↗ |
| `shorts.html` | visor vertical (misma página) · `videos.html` · `junior.html` / `seleccion.html` (filtros en la misma página) |
| `video.html` | `videos.html`, `videos.html#programas` (miga) · `video.html` (siguientes) · `articulo.html` (nota relacionada) · `shorts.html` · `radio.html#episodios` (si es episodio) · canal de YouTube ↗ |
| `datos.html` | `partido.html` · `junior.html` · `datos.html?t=…` (chips) · `datos.html#…` (pestañas) |
| `partido.html` | `datos.html`, `datos.html?t=liga` (miga) · `articulo.html` (previa/crónica) · `video.html` · `junior.html` · `datos.html#posiciones` |
| `radio.html` | `video.html` (episodios) · `radio.html#…` (anclas) · `redes.html#youtube` y `#whatsapp` (cómo escucharnos / participa) · `contacto.html#contacto` (participa) |
| `juan-salvador.html` | `articulo.html` (columnas) · `video.html` · `shorts.html` · `redes.html#x` · `radio.html` (equipo) · X ↗ |
| `redes.html` | `shorts.html` · `juan-salvador.html#x` · redes ↗ |
| `contacto.html` | `contacto.html#…` · `legales.html#privacidad` · correo `mailto:info@habladeportes.co` |
| `legales.html` | `legales.html#…` · `contacto.html#contacto` |
| `buscar.html` | `articulo.html` · `video.html` · `shorts.html` · `junior.html` · `seleccion.html` |
| `404.html` | `index.html` · `junior.html` · `videos.html` · `radio.html` · `buscar.html` |

### 8.3 Anclas y parámetros que el desarrollador debe definir

| Página | Anclas (`id`) | Parámetros |
|---|---|---|
| `noticias.html` | `efemerides` | `?tema=junior\|seleccion\|analisis\|mercado` |
| `mercado.html` | — | `?estado=confirmados\|rumores\|descartados`, `?equipo=junior` |
| `junior.html` | `partidos`, `plantilla` | — |
| `seleccion.html` | `convocatoria`, `partidos` | — |
| `videos.html` | `programas` | `?tema=junior\|seleccion` |
| `datos.html` | `hoy`, `torneos`, `posiciones`, `resultados`, `goleadores`, `estadisticas` | `?t=liga\|copa\|eliminatorias\|libertadores\|sudamericana` |
| `radio.html` | `parrilla`, `episodios`, `escuchar`, `participa` | — |
| `juan-salvador.html` | `columnas`, `videos`, `x`, `pregunta`, `equipo` | — |
| `redes.html` | `youtube`, `instagram`, `x`, `tiktok`, `whatsapp` | — |
| `contacto.html` | `nosotros`, `contacto`, `publicidad` | — |
| `legales.html` | `privacidad`, `terminos`, `cookies` | — |

**Reglas para no dejar enlaces rotos:**

1. Un parámetro desconocido o un ancla inexistente no rompe nada: la página carga completa.
2. Los filtros por parámetro se implementan con JS que lee `location.search`; sin JS se muestra todo.
3. Los enlaces externos (YouTube, Instagram, X, TikTok, WhatsApp) llevan `rel="noopener"` y el icono ↗; en el prototipo apuntan a las raíces de cada red mientras no se tengan los perfiles reales [H].
4. Ningún `href="#"`.

---

## 9. Riesgos y supuestos pendientes de confirmar con el cliente

| # | Riesgo / supuesto | Impacto | Qué hacer |
|---|---|---|---|
| R1 | **[H] Qué predomina: video, noticias o datos.** Todo el orden de la portada parte de "video-first con Junior y Selección". | Si predominan noticias: M4 y M5 se intercambian. Si predominan datos: el lateral sube a la altura de M4. Si predomina el video: Videos pasa a la posición 1 de la barra. | Confirmar con el cuestionario antes de pulir. |
| R2 | **[H] Si venden pauta o solo patrocinios fijos.** | Si solo hay patrocinios, A/B/C se convierten en bloques "Patrocinador" con las mismas medidas. | Preguntar; la estructura no cambia, solo el rótulo. |
| R3 | **[H] Alcance de Data Factory**: ¿Selección, copas internacionales, descenso, goleadores, alineaciones y minuto a minuto? ¿Licencia de escudos? | Sin esos datos, `?t=eliminatorias`, T9 en vivo y la convocatoria no tienen fuente. | Pedir catálogo de datos y condiciones de atribución; mientras tanto, todo "Datos de ejemplo". |
| R4 | **Paneles solo con hover** (decisión 12): inaccesibles para teclado y táctil. | Posible incumplimiento de accesibilidad. | Mitigado con padres enlazables, destino que repite los ítems y pie con todo. Recomendación al dueño: permitir también `:focus-within` (cero impacto visual). |
| R5 | **Radio persistente entre páginas** exige una arquitectura de tema distinta en WordPress. | Costo de la Fase 2. | Decidir antes de maquetar el tema; el prototipo lo simula con `sessionStorage`. |
| R6 | **[H] URL del stream de radio**, derechos y formato. | Sin stream, el mini-reproductor solo reproduce episodios. | Pedir URL o aceptar YouTube en vivo como fuente. |
| R7 | **Capacidad editorial.** La portada necesita unas 15 piezas frescas por semana en Junior, Selección y opinión. | Módulos vacíos o viejos. | Reglas de colapso (7 días) y rotulado de antigüedad; priorizar M1–M5. |
| R8 | **Pocas imágenes (11) que se repiten**; la portada pide ~17. | Repetición visible. | Listas de solo texto, un recorte distinto por uso, y ninguna imagen dos veces en el mismo pliegue. |
| R9 | **[H] Existencia de canal de WhatsApp, boletín y plataforma de encuestas.** | M10 sin función. | Confirmar; si no existen, M10 se reduce a la efeméride y las redes. |
| R10 | **[H] Comentarios** propios en notas y videos. | Moderación y spam. | Recomendación: no tenerlos; enlazar a YouTube. |
| R11 | **[H] Otros columnistas o programas.** | Si aparecen, hay que crear `opinion.html` (T3) y fichas de programa. | La estructura ya reserva el espacio. |
| R12 | **Contenido legal**: política de datos personales (Ley 1581 de 2012), cookies y términos no existen. | Bloquea formularios y boletín. | Pedir o redactar con el cliente. |
| R13 | **Nombres de personas reales en los textos de ejemplo** (jugadores, técnico, presentador). | Riesgo de presentar hechos inventados como ciertos. | Rotular siempre "Datos de ejemplo" y usar rumores solo en tono de ejemplo. |
| R14 | **Logo vectorial de Transelca** y URL del sponsor pendientes. | Marca provisional en banner y pie. | Pedir al cliente. |
| R15 | **Peso de imágenes (~12 MB)** y reproductores de YouTube. | Rendimiento. | Portadas de video con carga diferida; compresión (tarea de frontend). |
| R16 | **Apilado en móvil**: barra inferior + mini-reproductor + aviso de cookies. | Se tapa el contenido. | Orden fijo: contenido, mini-reproductor, barra inferior; el aviso de cookies se integra en el pie, sin banner flotante. |

---

*Este documento define estructura, navegación, módulos y reglas. El aspecto visual lo resuelve dirección de arte; el HTML lo construye desarrollo front-end a partir de las plantillas T1–T15 y del mapa de enlaces de la sección 8.*
