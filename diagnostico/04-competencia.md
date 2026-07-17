# 04 — Benchmark competitivo

**Proyecto:** Rediseño Habla Deportes
**Fase:** 1 — Diagnóstico
**Agente:** analista-competencia
**Fecha:** 2026-07-13

> Objetivo: comparar Habla Deportes (medio regional de Barranquilla, video-first, radio + fútbol colombiano, con sponsors) contra referentes nacionales, regionales/de radio e internacionales, para extraer buenas prácticas replicables y oportunidades de diferenciación que alimenten a `director-arte`.
>
> **Nota de método:** análisis basado en navegación y fetch/búsqueda web de las home actuales (julio 2026). No se pudieron capturar screenshots por bloqueo del navegador in-app; las referencias van enlazadas para que `director-arte` las visite en vivo. Todas las observaciones son de la estructura y jerarquía real de cada sitio.

---

## 0. Punto de partida — el sitio actual de Habla Deportes

Lo que hoy tiene [habladeportes.co](https://habladeportes.co/):

- **Video-first real:** carrusel con ~265 videos del canal de YouTube incrustados; acceso directo a YouTube en el header; botón de suscripción visible. El video es el corazón del contenido.
- **Foco editorial claro:** Junior FC, Selección Colombia, Atlético Nacional, fichajes/mercado.
- **Radio:** programa matutino L–V 10:00 a.m.–12:00 m., mencionado pero sin protagonismo de reproductor en vivo.
- **Sponsor:** logo de ISA TRANSELCA vinculado a su sitio (azules/dorados corporativos).
- **Buscador** tipo overlay ("Hit enter to search or ESC to close").

**Debilidades evidentes frente a los referentes (se detallan abajo):** el video vive en un carrusel plano (no jerarquizado), no hay marcadores/resultados en vivo, no hay navegación por equipos/torneos, la radio no tiene un reproductor persistente, y la identidad visual está subordinada a los colores del patrocinador en vez de a una marca propia caribeña.

---

## 1. Referentes seleccionados

| Categoría | Referente | Por qué es relevante |
|---|---|---|
| Nacional (TV/OTT) | **Win Sports** ([winsports.co](https://www.winsports.co/)) | El portal deportivo #1 de Colombia; vara de referencia local en navegación por equipos y torneos. |
| Nacional (TV) | **ESPN Colombia** ([espn.com.co](https://www.espn.com.co/)) | Estándar de marcadores en vivo + personalización, adaptado a Colombia. |
| Nacional (medio) | **Gol Caracol** ([noticiascaracol.com/golcaracol](https://www.noticiascaracol.com/golcaracol)) | Bloques de marcador + video de goles + distribución social agresiva. |
| Radio deportiva | **Antena 2 / Deportes RCN** ([deportesrcn.com](https://www.deportesrcn.com/)) | Modelo radio+web con talento al frente y gamificación; el más comparable a Habla Deportes en formato. |
| Internacional (editorial) | **The Athletic** ([theathletic.com](https://theathletic.com/)) | Vara alta mundial de diseño editorial minimalista y "follow" de equipos. |
| Internacional (portal) | **Marca** ([marca.com](https://www.marca.com/)) | Densidad informativa, marca de color fuerte, live + video + podcast. |
| Regional (contexto local) | **El Heraldo — Rincón Juniorista** ([elheraldo.co/rincon-juniorista](https://www.elheraldo.co/rincon-juniorista/)) | Cómo un medio de Barranquilla capitaliza la identidad rojiblanca/juniorista. |

Escala: The Athletic, ESPN y Marca son inspiración de **patrones**, no de presupuesto. Win Sports, Gol Caracol, Antena 2 y El Heraldo son los competidores directos con los que Habla Deportes se mide de tú a tú.

---

## 2. Comparativo por dimensión

### 2.1 Presentación de video / contenido en vivo

| Referente | Qué hace, concretamente |
|---|---|
| **Win Sports** | Sección "Videos en vivo" con miniaturas contextualizadas + narrativa del encuentro; señal en vivo detrás de botón de suscripción/login. |
| **ESPN Col** | Video incrustado junto a titulares; timestamps relativos (3h, 19h, 21h) que señalan recencia. |
| **Gol Caracol** | Bloque "Goles y mejores jugadas" con videos incrustados justo debajo de los marcadores finales — el clip vive pegado al resultado. |
| **Antena 2/RCN** | YouTube incrustado por programa ("Gregarios", "Somos Mundial"); el video es extensión de la marca radial. |
| **Marca** | Apuesta declarada por directos, video-entrevistas y podcasts como formato de primera clase. |
| **Habla Deportes (hoy)** | Carrusel plano de 265 videos, sin jerarquía (todos pesan igual), sin conexión a un partido/resultado. |

**Lectura:** todos los buenos jerarquizan el video (uno héroe grande + grilla secundaria) y lo **anclan a un evento**. Habla Deportes tiene el mejor activo (video propio de YouTube) peor presentado.

### 2.2 Home y jerarquía

| Referente | Patrón de jerarquía |
|---|---|
| **The Athletic** | Una historia héroe con foto grande + tipografía editorial fuerte; sin sidebars de ads, sin ticker, sin autoplay. Todo apunta a las palabras y la foto. |
| **Win Sports** | Grilla con imagen destacada 1920×1080 para la nota principal, luego módulos menores; "Lo último" como feed. |
| **Gol Caracol** | Partidos finalizados como bloques destacados con marcador ("Argentina 3 - 1 Suiza") arriba, video de goles debajo, luego exclusivas y recientes. |
| **Marca** | Alta densidad: mucha información por pantalla, jerarquizada por tamaño/peso tipográfico. |
| **Habla Deportes (hoy)** | Carrusel + bloques sin una historia héroe clara ni ritmo de lectura. |

**Lectura:** el patrón ganador es **1 héroe + módulos + feed**. Para un medio pequeño, la vía "menos ruido, más foco" de The Athletic es más alcanzable y más impactante que competir en densidad con Marca.

### 2.3 Navegación por equipos / torneos

| Referente | Qué hace, concretamente |
|---|---|
| **Win Sports** | Navegación por 37 clubes con logos + torneos nombrados (Liga Dimayor, Copa Colombia, Liga Femenina); "Tabla de Posiciones" como ítem de primer nivel del menú. |
| **Gol Caracol** | Menú por competición con sub-secciones (Liga BetPlay → Liga/Copa/Femenina; Selección → mayores/femenina/juveniles); calendario, tabla, goleadores, simulador. |
| **ESPN Col** | Menú por deporte → ligas → equipos; la profundidad es sistemática. |
| **The Athletic** | Modelo "follow": el usuario sigue equipos/ligas y arma su feed. |
| **Habla Deportes (hoy)** | Sin navegación por equipo ni torneo. |

**Lectura:** un **hub de Junior FC** (y secundariamente Selección) es la mejora de arquitectura de mayor impacto y la más natural para un medio de Barranquilla. No necesita los 37 clubes; necesita profundidad en los 2–3 que le importan a su audiencia.

### 2.4 Experiencia móvil

| Referente | Qué hace |
|---|---|
| **Win Sports** | Rediseño explícitamente optimizado para móvil, con secciones personalizadas y carga más rápida. |
| **The Athletic** | Lectura larga cómoda en móvil por ausencia de clutter/ads. |
| **Marca** | Denso; funciona por jerarquía tipográfica pero es exigente en pantalla pequeña. |
| **Habla Deportes (hoy)** | WordPress; el carrusel de video y el overlay de búsqueda son los puntos a revisar en móvil (ver auditoría técnica). |

**Lectura:** la audiencia de fútbol colombiano es mayoritariamente móvil. Diseñar mobile-first, con video héroe y reproductor de radio sticky que no estorbe la lectura.

### 2.5 Integración de redes / YouTube

| Referente | Qué hace |
|---|---|
| **Gol Caracol** | Barra completa: Facebook, TikTok, Instagram, X, WhatsApp, YouTube y Google Discover — distribución multiplataforma explícita. |
| **Antena 2/RCN** | Los programas de radio se publican como playlists/videos de YouTube incrustados; el talento cruza radio → web → YouTube. |
| **Habla Deportes (hoy)** | Acceso a YouTube en header + carrusel; buen inicio, pero sin TikTok/Instagram/WhatsApp visibles ni "compartir" por nota. |

**Lectura:** Habla Deportes ya es nativo de YouTube; falta cerrar el círculo con TikTok/Instagram Reels y **compartir por WhatsApp** (canal clave en Colombia).

### 2.6 Manejo de patrocinadores

| Referente | Qué hace |
|---|---|
| **Gol Caracol** | Logos de BetPlay y Puntos Colombia integrados en banners promocionales, sin romper la lectura. |
| **Win Sports / ESPN** | Patrocinio y cross-promo (Disney+, tienda, boletería) en módulos definidos, no invasivos. |
| **Habla Deportes (hoy)** | Un sponsor (Transelca) cuyo azul/dorado condiciona la paleta del sitio. |

**Lectura:** el sponsor debe tener un **espacio de marca digno y medible** (módulo "presentado por", pre-roll en video, mención en el reproductor de radio) **sin dictar la identidad visual** del medio. Hoy la marca propia está subordinada al color del patrocinador — eso hay que invertirlo.

### 2.7 Identidad visual

| Referente | Firma visual |
|---|---|
| **The Athletic** | Editorial, tipografía protagonista, foto grande, mucho aire; se lee "premium". |
| **Marca** | Rojo característico como código de marca inconfundible. |
| **Win Sports** | Tema oscuro con acentos por color de equipo; look "de canal deportivo". |
| **El Heraldo (Rincón Juniorista)** | Explota lo **rojiblanco** como código emocional local. |
| **Habla Deportes (hoy)** | Estética deportiva correcta pero condicionada por Transelca; sin código de color propio memorable. |

**Lectura:** falta una **firma de color/tipografía propia**. La oportunidad caribeña/rojiblanca (abajo) es el camino de diferenciación.

---

## 3. Buenas prácticas replicables (priorizadas para Habla Deportes)

Priorizadas por impacto × factibilidad para un medio pequeño video-first.

1. **Video héroe jerarquizado (no carrusel plano).** Un clip protagonista grande + grilla de secundarios, al estilo del bloque de video de Gol Caracol y las miniaturas contextualizadas de Win Sports. Es el cambio de mayor impacto sobre el activo que ya tienen. *(Alta / Alta)*
2. **Hub de Junior FC (y Selección).** Página de equipo con últimos videos, notas, próximo partido y resultado, inspirado en la navegación por club de Win Sports pero acotado a lo que le importa a la costa. *(Alta / Media)*
3. **Reproductor de radio persistente (sticky).** Escuchar el programa en vivo mientras se navega, con marca del sponsor. Es la traducción digital natural del formato radio+video; ningún competidor local lo hace bien. *(Alta / Media)*
4. **Minimalismo editorial de The Athletic.** Menos ruido, una historia héroe, tipografía fuerte, foto grande, sin autoplay agresivo ni sidebars de ads. Alcanzable con poco presupuesto y sube la percepción de calidad. *(Alta / Alta)*
5. **Compartir + distribución social por nota.** Botones de WhatsApp/TikTok/Instagram/X como Gol Caracol. Barato y multiplica alcance. *(Media / Alta)*
6. **Módulo de patrocinador bien encajado.** "Presentado por Transelca" como componente definido (no como color de todo el sitio), tipo banners integrados de Gol Caracol. *(Media / Alta)*
7. **Bloque de marcador/próximo partido de Junior.** Aunque sea un widget simple (resultado + próximo rival + fecha), no un scoreboard completo tipo ESPN. Ancla el contenido a la actualidad. *(Media / Media)*
8. **Gamificación ligera** (polla/predicción de Junior o Selección), como la "Polla Mundialista" y "Zona Interactiva" de Antena 2/RCN, para engagement en fechas clave. *(Baja / Media)*

**Realismo de escala:** NO replicar el scoreboard completo de ESPN, ni los 37 clubes de Win Sports, ni la densidad de Marca. Habla Deportes gana con **foco**, no con volumen.

---

## 4. Oportunidades de diferenciación

Habla Deportes no debe ser "un Win Sports más pequeño". Su ventaja es ser **el medio caribeño, cercano y video-first de la hinchada del Junior**. Palancas:

1. **Identidad caribeña / rojiblanca como código visual propio.** El rojo y blanco del Junior es identidad de Barranquilla y de toda la costa desde hace casi un siglo (fuente El Heraldo). Una paleta y tipografía que respiren Caribe (calidez, energía, "sabor") diferencian de inmediato del azul corporativo actual y del tema oscuro genérico de los canales nacionales. *(Insumo directo para director-arte.)*
2. **Cercanía con la hinchada, no cobertura nacional neutra.** Tono, secciones y video pensados para el hincha rojiblanco (Frente Rojiblanco Sur, ambiente de barrio, la ciudad). Los grandes son neutros; Habla Deportes puede ser **de la casa**.
3. **Radio + video como formato integrado, no dos cosas separadas.** El talento al frente (como hace Antena 2 con sus programas), reproductor en vivo sticky, y clips del programa como contenido evergreen. Ese cruce radio↔video↔YouTube es su seña de identidad y ningún local lo ejecuta bien digitalmente.
4. **Agilidad video-first nativa de YouTube.** Ya producen +265 videos; pueden publicar y jerarquizar video más rápido y con más frescura que un portal corporativo pesado. Convertir esa velocidad en experiencia de home.
5. **Foco geográfico como fortaleza SEO/editorial.** Ser la referencia de "Junior de Barranquilla" y fútbol de la costa, donde compiten con menos ruido que en "Selección Colombia".

---

## 5. Mini-moodboard para director-arte

Enlaces + qué tomar de cada uno (visitar en vivo para ver el detalle):

| Referencia | Enlace | Qué tomar |
|---|---|---|
| **The Athletic** | [theathletic.com](https://theathletic.com/) | Minimalismo editorial: historia héroe, tipografía protagonista, foto grande, mucho aire, cero clutter. La *actitud* de diseño premium. |
| **Win Sports** | [winsports.co](https://www.winsports.co/) | Grilla con imagen destacada + módulos; hub de equipo con logos; "Tabla de Posiciones" en el menú. Nivel de referencia local. |
| **Gol Caracol** | [noticiascaracol.com/golcaracol](https://www.noticiascaracol.com/golcaracol) | Bloque marcador→video de goles pegados; barra social completa (incl. WhatsApp); banners de sponsor integrados sin romper lectura. |
| **ESPN Colombia** | [espn.com.co](https://www.espn.com.co/) | Timestamps de recencia; personalización "Favoritos"; módulos de patrocinio/cross-promo bien contenidos. *(Tomar el patrón, no la densidad.)* |
| **Antena 2 / Deportes RCN** | [deportesrcn.com](https://www.deportesrcn.com/) | Radio con talento al frente; programas como video de YouTube; "Zona Interactiva" y polla para engagement. El modelo radio+web más comparable. |
| **Marca** | [marca.com](https://www.marca.com/) | Un color de marca fuerte e inconfundible (su rojo) como código. Tomar la *idea de firma cromática*, no la densidad. |
| **El Heraldo — Rincón Juniorista** | [elheraldo.co/rincon-juniorista](https://www.elheraldo.co/rincon-juniorista/) | Cómo un medio de Barranquilla capitaliza lo rojiblanco/juniorista como marca emocional local. Insumo de identidad caribeña. |

**Dirección visual sugerida (hipótesis para director-arte):** paleta cálida caribeña con acento rojiblanco propio (diferenciado del escudo del club para no depender de él), tipografía editorial de carácter, layout de home con video héroe + grilla, y un reproductor de radio sticky como elemento firma. Menos "canal deportivo oscuro genérico", más "medio caribeño con voz propia".

---

## 6. Resumen ejecutivo

- Habla Deportes tiene el **mejor activo mal presentado**: video propio de YouTube atrapado en un carrusel plano. Jerarquizarlo (héroe + grilla, anclado a partidos) es la mejora #1.
- Le faltan tres patrones que todos los referentes tienen: **navegación por equipo/torneo** (empezar por un hub de Junior), **algún ancla de actualidad** (marcador/próximo partido) y **distribución social por nota** (WhatsApp incluido).
- Su formato **radio+video** es diferenciador si se traduce en un **reproductor en vivo sticky** con talento al frente — nadie a nivel local lo hace bien.
- Su identidad visual está **secuestrada por el color del sponsor**; hay que darle al patrocinador un módulo digno y devolverle al medio una **firma caribeña/rojiblanca propia**.
- El camino no es imitar a Win Sports/ESPN/Marca en volumen, sino ganar por **foco, cercanía con la hinchada del Junior y agilidad video-first** — con el minimalismo editorial de The Athletic como vara de calidad alcanzable.
