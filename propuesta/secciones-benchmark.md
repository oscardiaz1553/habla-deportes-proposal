# Benchmark de secciones de Home — qué le falta a Habla Deportes

**Proyecto:** Rediseño Habla Deportes
**Etapa:** Ampliación de la Home (post-aprobación de la propuesta base)
**Fecha:** 2026-07-16
**Método:** Búsqueda y lectura de medios comparables (Win Sports, Gol Caracol, ESPN Colombia, Antena 2/Deportes RCN, El Heraldo–Rincón Juniorista, Futbolred, sitios especializados de datos), más evidencia de intención de búsqueda real de la afición de Junior FC y Selección Colombia. Construye sobre `diagnostico/04-competencia.md` (benchmark de identidad/UX) y `diagnostico/03-arquitectura-contenido.md` (sitemap propuesto) — no repite esos hallazgos, los profundiza a nivel de **qué módulos concretos poner en la Home**.

> **Nota de contexto (julio 2026):** Colombia está jugando el Mundial 2026 en este momento — invicta en fase de grupos, clasificada a octavos contra Suiza. Esto es evidencia viva de por qué los módulos de "próximo partido" y "resultados en vivo" no son un capricho de diseño: son el contenido que la audiencia está buscando *hoy*.

---

## 1. Qué módulos usan los referentes en su Home (tabla comparativa)

| Módulo | Qué hace / cómo se ve | Quién lo hace (ejemplo concreto) |
|---|---|---|
| **Marcador / resultados en vivo** | Bloque con resultado del último partido o "en vivo ahora" (minuto, marcador), típicamente arriba de la home o en franja fija. | **Gol Caracol**: bloque "Argentina 3–1 Suiza" con video de goles debajo, en la parte alta de `golcaracol.com`. **ESPN Colombia**: marcadores en vivo integrados junto a titulares con estadísticas, plantel y calendario del partido. |
| **Tabla de posiciones** | Widget compacto de la tabla del torneo (Liga BetPlay / Eliminatorias), a veces filtrable por equipo. | **Win Sports**: "Tabla de Posiciones" es ítem de primer nivel del menú, con vista propia por torneo y por equipo (`/futbol-colombiano/liga-dimayor/posiciones`, `/equipos/junior/tablas-de-posiciones`). **Gol Caracol**: tabla de Eliminatorias con jornada actual y próximos partidos, además promovida en redes (TikTok) como contenido en sí mismo. |
| **Calendario / próximo partido** | Fecha, hora, rival y dónde ver el próximo partido del equipo/selección seguido. | **Win Sports** y **Gol Caracol**: calendario de torneo con resultados + próximos partidos en una sola vista. **Junior FC (sitio oficial)**: notas dedicadas tipo "PRÓXIMOS 4 PARTIDOS DE JUNIOR FC. FECHAS Y HORARIOS" y "¡HOY JUEGA JUNIOR! Información del partido: boletería, estadísticas y recomendaciones". |
| **Fichajes / mercado de pases** | Sección o etiqueta dedicada a altas, bajas y rumores, muy usada en fechas de mercado. | **Win Sports**: etiqueta `/etiquetas/fichajes` dedicada. **Futbolred**: notas específicas ("Así quedó la plantilla de Junior para 2026: estos serán los 25 jugadores inscritos", "Junior de Barranquilla: todas las bajas y rumores"). Sitios especializados enteros (Transfermarkt, TransferFeed, Fichajes.com) existen solo para esto — señal de demanda alta. |
| **Minuto a minuto / crónica de partido** | Cobertura en vivo, texto, del desarrollo del partido: alineaciones, goles, sustituciones, crónica al final. | **El Heraldo — Rincón Juniorista**: formato recurrente "Equipo X, Junior Y: [titular editorial]" con minuto a minuto, formaciones, crónica y goles en cada partido de Junior (ver ejemplos "Santa Fe 2, Junior 1", "Tolima 1, Junior 0"). Es su formato más repetido — indica que funciona. |
| **Alineaciones / convocados** | Posible once titular antes del partido, convocados del técnico. | **OneFootball / medios locales**: notas tipo "Posibles alineaciones del Junior vs. Medellín" generan tráfico previo al partido. Clubes rivales (Millonarios) publican "convocados" como nota propia — evidencia de que el dato se busca desde ambos lados. |
| **Boletería / entradas** | Precio y disponibilidad de boletas para el próximo partido en casa. | **Win Sports** publica notas dedicadas ("Junior vs. Nacional: precios de la boletería en el Metropolitano", "Junior anuncia la boletería para la final"). El propio sitio de Junior FC tiene notas de boletería recurrentes. Es contenido de utilidad práctica muy buscado por el hincha local. |
| **Plantilla / jugadores** | Ficha del equipo con jugadores por posición, a veces con estadísticas individuales. | **ESPN Colombia**: página "Plantel" del Atlético Junior. **Win Sports**: tabla de posiciones + plantilla por club. **Futbolred**: nota específica de la plantilla inscrita de Junior para la temporada. |
| **Goleadores / estadísticas** | Tabla de goleadores del torneo, a veces cruzada con el equipo seguido. | **Gol Caracol**: sección "Resultados y estadísticas". Ampliamente estándar en todos los portales grandes (Win Sports, ESPN). |
| **Opinión / columnas** | Espacio editorial con voz propia (no solo noticia), firmado por un periodista o comentarista. | **Marca / ESPN**: columnas de opinión como sección de primer nivel. Es lo que más diferencia "medio con criterio" de "agregador de resultados" — oportunidad para que las voces de Habla Deportes Radio tengan versión escrita. |
| **Podcasts / radio en video** | Episodios del programa como contenido navegable, no solo un carrusel de YouTube. | **Antena 2 / Deportes RCN**: programas ("Gregarios", "Somos Mundial") con talento al frente, publicados como video de YouTube incrustado y organizados por programa. Es el patrón más cercano al modelo de Habla Deportes. |
| **Encuestas / predicciones (polla)** | Mecánica de participación: predecir resultados, votar, competir con amigos. | **Deportes RCN — Zona Interactiva**: "Polla Mundialista" con tabla de clasificación automática entre participantes; mecánica de trivia y votación. Bajo costo, alto engagement en fechas de partido. |
| **Redes / tendencias** | Barra o widget que muestra actividad en redes (lo más compartido, lo más visto). | **Gol Caracol**: barra social completa (Facebook, TikTok, Instagram, X, WhatsApp, YouTube). Refuerza el hallazgo ya documentado en `04-competencia.md` sobre compartir contenido. |
| **Efemérides** | Recordatorios de fechas históricas del club/selección ("un día como hoy…"). | Uso más ocasional en medios grandes (ESPN, Marca) como contenido de relleno de bajo costo editorial; no es prioridad para un medio pequeño pero es fácil de producir con archivo propio. |

---

## 2. Qué busca realmente la afición de Junior y de la Selección (evidencia)

No hay acceso directo a Google Trends con cifras exportables desde este entorno, así que la evidencia se construye por **densidad de oferta especializada**: cuando múltiples sitios —incluidos gigantes de datos deportivos globales— dedican página propia a un mismo dato para Junior FC, es porque existe demanda sostenida de búsqueda. Encontramos:

- **Tabla de posiciones / clasificación de Junior**: páginas dedicadas en Sofascore, Flashscore, 365Scores, Tribuna, AiScore, Win Sports y APWin — al menos 7 proveedores distintos compitiendo por esta misma consulta. Señal de intención de búsqueda muy alta y recurrente (cada jornada).
- **Próximo partido / calendario**: Sofascore, Flashscore y el propio sitio de Junior FC publican notas específicas de "próximos partidos, fechas y horarios". Es la consulta más ligada a la decisión de "voy al estadio o veo por dónde".
- **Fichajes / plantilla / rumores**: Transfermarkt, TransferFeed, 365Scores y Futbolred tienen secciones dedicadas exclusivamente a altas/bajas/rumores de Junior; Futbolred incluso publicó nota de "los 25 jugadores inscritos para 2026". Es contenido de alto tráfico en ventanas de mercado (enero–febrero y junio–julio).
- **Boletería**: Win Sports y el sitio oficial de Junior publican notas puntuales de precios y disponibilidad antes de cada partido en el Metropolitano — contenido de utilidad práctica, local, con ciclo de vida corto pero pico de tráfico alto.
- **Minuto a minuto y crónica**: El Heraldo — Rincón Juniorista repite el mismo formato en cada partido ("Equipo X, Junior Y: [titular]"), señal de que es su formato de mayor consumo y el que sostiene la sección.
- **Selección Colombia / Mundial 2026**: en el momento de esta investigación, Colombia está clasificada a octavos de final invicta — contenido de "próximo partido, cuándo y contra quién" está siendo cubierto simultáneamente por El Tiempo, CNN, Futbolred, Gol Caracol y FIFA.com. Es la prueba más directa de que "próximo partido + dónde verlo" es la pregunta número uno del hincha en momentos de coyuntura, y Habla Deportes no tiene hoy ningún módulo que la responda sin salir del sitio.

**Lectura:** la audiencia de Junior/Selección no busca "noticias" en abstracto — busca **datos accionables de corto plazo**: ¿cuándo juega, contra quién, dónde verlo, cómo va la tabla, quién llega/se va, cuánto cuesta la boleta. Habla Deportes hoy no responde ninguna de estas preguntas sin mandar al usuario a otro sitio.

---

## 3. Priorización — Impacto en audiencia × Factibilidad para medio pequeño video-first

| # | Módulo | Impacto en audiencia | Factibilidad (medio pequeño) | Evidencia / por qué importa |
|---|---|---|---|---|
| 1 | **Próximo partido de Junior y Selección** (rival, fecha, hora, dónde ver) | **Alto** | **Alta** — dato estático/manual, editable a mano en WordPress cada semana; no requiere API. | Es la pregunta #1 del hincha (ver sección 2); todos los referentes lo tienen destacado. Bajísimo costo técnico. |
| 2 | **Resultado más reciente / marcador** | **Alto** | **Alta con widget** (SofaScore/API-Football, ambos con capa gratuita — ver sección 4) o **manual** si se prefiere cero dependencias externas. | Gol Caracol y ESPN lo ponen arriba de todo; ancla la home a la actualidad, tal como recomienda `04-competencia.md`. |
| 3 | **Tabla de posiciones (Liga BetPlay, con fila de Junior resaltada)** | **Alto** | **Media** — mejor vía widget embebido (SofaScore es gratis y sin límite; API-Football tiene capa gratuita de 100 requests/día) que construirlo a mano. | 7+ proveedores de datos compiten por esta consulta; se actualiza cada jornada, inviable mantenerla 100% manual sin fricción editorial. |
| 4 | **Fichajes / mercado de pases (bloque o mini-sección)** | **Alto** en ventanas de mercado (enero–feb, jun–jul), medio el resto del año | **Alta** — es contenido editorial (notas escritas), no requiere datos en vivo. | Futbolred, Win Sports y sitios enteros dedicados a esto; es el tema de mayor tráfico coyuntural ya identificado en `03-arquitectura-contenido.md` (`/fichajes`). |
| 5 | **Minuto a minuto / crónica de partido** (para partidos de Junior) | **Alto** en día de partido | **Media** — exige cobertura editorial en vivo (esfuerzo humano, no técnico); encaja con el formato radio+video si el locutor de la radio narra y se transcribe/resumen en texto. | Es el formato más repetido de El Heraldo–Rincón Juniorista; conecta directamente con el diferencial radio+video de Habla Deportes. |
| 6 | **Alineaciones / convocados antes del partido** | **Medio-Alto**, muy puntual (pico horas antes del partido) | **Alta** — nota corta manual, bajo esfuerzo. | Tráfico específico de previa (OneFootball, Millonarios FC lo publican como nota propia). |
| 7 | **Boletería / info práctica del partido en casa** (precio, taquillas, recomendaciones) | **Medio-Alto** para la audiencia local de Barranquilla específicamente | **Alta** — nota manual, contenido de servicio, encaja con la identidad "de la casa" ya recomendada en `04-competencia.md`. | Win Sports y el sitio oficial de Junior lo publican cada vez que hay partido en el Metropolitano; nadie lo hace con enfoque *hincha rojiblanco* como podría hacerlo Habla Deportes. |
| 8 | **Plantilla de Junior (ficha de jugadores)** | Medio | **Media** — se puede resolver como página estática actualizada por temporada (baja frecuencia de cambio) sin necesidad de API. | ESPN, Win Sports y Futbolred la tienen; útil como página "ancla" de SEO por jugador. |
| 9 | **Encuesta / predicción ligera** (¿quién gana Junior vs. X?, ¿marcador del partido?) | Medio, alto en fechas clave (clásico, final, Mundial) | **Media-Alta** — un plugin simple de WordPress (Football Pool u otro) o una encuesta ligera basta; no requiere un sistema propio complejo. | Ya recomendado en `04-competencia.md` (#8) inspirado en la "Polla Mundialista" de Deportes RCN; en el contexto actual (Colombia en octavos de Mundial 2026) tiene ventana perfecta de lanzamiento. |
| 10 | **Goleadores / estadísticas del torneo** | Medio | **Media** — mejor vía el mismo widget que la tabla de posiciones (viene incluido en la mayoría de proveedores). | Estándar en portales grandes; menor urgencia que resultado/tabla/próximo partido para un medio chico. |
| 11 | **Opinión / columna de voz propia** | Medio (fideliza, no atrae tráfico masivo) | **Alta** — es contenido editorial puro, aprovecha las voces ya existentes del programa de radio. | Diferencia "medio con criterio propio" de "agregador"; barato de producir si el talento de radio ya opina en el programa — solo falta transcribir/adaptar. |
| 12 | **Redes / tendencias (lo más compartido)** | Bajo-Medio como módulo de home, alto como práctica transversal (botones de compartir) | **Alta** | Ya cubierto como recomendación #5 en `04-competencia.md`; se resuelve con botones, no con un módulo de home dedicado. |
| 13 | **Efemérides** | Bajo | **Alta** — contenido de relleno editorial de bajo costo. | Útil para llenar calendario editorial en días sin partido, no prioritario para el lanzamiento. |

**Lectura de la priorización:** los 5 primeros lugares (próximo partido, resultado/marcador, tabla de posiciones, fichajes, minuto a minuto) son los que **más impactan** porque responden preguntas que la audiencia ya está haciendo activamente (sección 2) — y todos son **factibles** para un medio pequeño, combinando contenido manual (próximo partido, fichajes) con widgets externos de bajo o ningún costo (resultado, tabla).

---

## 4. Nota de datos — qué requiere datos en vivo y cómo alimentarlo

**Módulos que se pueden resolver 100% manual (edición en WordPress, sin integración externa):**
- Próximo partido (rival, fecha, hora, dónde ver)
- Fichajes / mercado de pases
- Alineaciones / convocados
- Boletería / info práctica
- Plantilla (actualización por temporada)
- Opinión / columnas
- Efemérides
- Encuesta/predicción simple (plugin ligero tipo *Football Pool* para WordPress, o incluso un formulario simple)

Esto es intencional: son los módulos de **mayor impacto** (próximo partido, fichajes) y **no dependen de ninguna API**, lo cual es coherente con la recomendación de `03-arquitectura-contenido.md` de resolver el indicador "AL AIRE" de la radio con lógica de horario, sin depender de servicios externos.

**Módulos que sí requieren datos en vivo o semi-vivos (resultado, tabla de posiciones, goleadores):**

| Opción | Cómo funciona | Costo | Nivel de esfuerzo |
|---|---|---|---|
| **Widget embebido (SofaScore)** | Se elige la liga/equipo en el sitio de SofaScore y se copia un `<iframe>` a la plantilla de WordPress. Datos en vivo, se actualiza solo. | Gratis, sin límite declarado. | Muy bajo — copiar/pegar, sin backend propio. |
| **Widget embebido (API-Football)** | Widgets de marcador, calendario y tabla, personalizables en CSS, embebibles con script. | Gratuito en todos los planes (incluido el free). | Bajo — requiere una cuenta y configurar el widget una vez. |
| **API con clave (API-Football / api-sports.io)** | Llamadas propias a la API para mostrar los datos con el diseño exacto del sitio (control total de estilo). | Plan free: 100 requests/día, 10/min. Plan Pro ~US$19/mes = 7.500 requests/día. | Medio-alto — requiere desarrollo (llamadas server-side o cacheadas) para no agotar la cuota. |
| **Plugin de WordPress dedicado** (p. ej. *Soccer Info*, *WP Club Manager*, *Soccer Live Scores*) | Se instala como plugin nativo de WP; trae tabla, resultados y próximos partidos de decenas de ligas. | Variable (algunos con capa gratuita, otros de pago). | Bajo — nativo del CMS que ya usan (WordPress vía spacerock). |
| **BeSoccer API / widgets** | Módulos similares a SofaScore/API-Football (marcador, plantilla, calendario, clasificación), panel de integración compatible con AMP. | No publica precio en la página pública; requiere contacto directo (`api@besoccer.com`). | Medio — depende de la cotización. |

**Recomendación de realismo:** para el lanzamiento de esta ampliación, lo más costo-efectivo es:
1. **Widget embebido gratuito** (SofaScore o API-Football) para **resultado/marcador** y **tabla de posiciones** — cero desarrollo, cero mantenimiento de datos, riesgo bajo de quedar desactualizado.
2. **Todo lo demás** (próximo partido, fichajes, boletería, alineaciones, opinión) se resuelve como **contenido editorial manual** dentro del flujo normal de publicación en WordPress — es lo que el equipo editorial ya sabe hacer y no depende de terceros.
3. Evitar, por ahora, construir una integración de API propia con backend — es el camino de mayor costo técnico y no aporta más valor que un widget ya hecho para el tamaño actual del medio. Se puede reconsiderar si el sitio crece y quiere control total de estilo sobre los datos en vivo.

---

## 5. Enlaces de referencia usados

- Win Sports — [winsports.co](https://www.winsports.co/) · [Tabla de posiciones Liga Dimayor](https://www.winsports.co/futbol-colombiano/liga-dimayor/posiciones) · [Tabla de posiciones de Junior](https://www.winsports.co/futbol-colombiano/equipos/junior/tablas-de-posiciones) · [Etiqueta Fichajes](https://www.winsports.co/etiquetas/fichajes)
- Gol Caracol — [noticiascaracol.com/golcaracol](https://www.noticiascaracol.com/golcaracol) · [Resultados y estadísticas](https://www.noticiascaracol.com/golcaracol/resultados-estadisticas) · [Fútbol Colombiano — tabla y noticias](https://www.noticiascaracol.com/golcaracol/futbol-colombiano)
- ESPN Colombia — [espn.com.co](https://www.espn.com.co/) · [Resultados de fútbol](https://www.espn.com.co/futbol/resultados) · [Plantel Atlético Junior](https://www.espn.com.co/futbol/equipo/plantel/_/id/4815/col.atletico_junior)
- Deportes RCN / Antena 2 — [deportesrcn.com](https://www.deportesrcn.com/) · [Zona Interactiva](https://zona-interactiva.deportesrcn.com/) · [Polla Mundialista](https://zona-interactiva.deportesrcn.com/predicciones/mundial-2026/)
- El Heraldo — Rincón Juniorista — [elheraldo.co/rincon-juniorista](https://www.elheraldo.co/rincon-juniorista/) · ejemplo minuto a minuto: [Santa Fe 2, Junior 1](https://www.elheraldo.co/deportes/rincon-juniorista/2025/05/10/santa-fe-vs-junior-minuto-a-minuto-formaciones-goles-y-cronica-del-partido-de-la-fecha-18-de-la-liga-i/)
- Futbolred — [Junior — noticias](https://www.futbolred.com/equipos-futbol-colombiano/junior) · [Plantilla Junior 2026](https://www.futbolred.com/futbol-colombiano/liga-betplay/asi-quedo-la-plantilla-de-junior-para-el-2026-estos-seran-los-25-jugadores-inscritos-260789)
- Sitios de datos especializados (evidencia de demanda) — [Sofascore — Junior](https://www.sofascore.com/football/team/junior-barranquilla/6105) · [Flashscore — Junior](https://www.flashscore.co/equipo/junior/ptqMix37/) · [365Scores — Junior](https://www.365scores.com/es/football/team/junior-fc-7204/standings) · [Transfermarkt — Junior](https://www.transfermarkt.us/junior-fc/kadernachposition/verein/11854) · [TransferFeed — Junior](https://www.transferfeed.com/es/clubes/junior-fc/4021)
- Junior FC — sitio oficial (notas de partido/boletería) — [app.juniorfc.co](https://app.juniorfc.co/)
- Selección Colombia / Mundial 2026 (contexto de actualidad) — [FIFA — Colombia fixtures](https://www.fifa.com/es/tournaments/mens/worldcup/canadamexicousa2026/teams/colombia/fixtures) · [El Tiempo — próximo partido](https://www.eltiempo.com/deportes/futbol-internacional/cuando-sera-el-proximo-partido-de-la-seleccion-colombia-en-el-mundial-2026-conozca-aca-el-dia-la-hora-y-donde-verlo-3565118)
- APIs / widgets de datos — [API-Football — Widgets](https://www.api-football.com/widgets) · [API-Football — Pricing](https://www.api-football.com/pricing) · [SofaScore — Widgets](https://corporate.sofascore.com/widgets) · [BeSoccer API — Widgets](https://api.besoccer.com/es/widgets)
- Plugins WordPress — [Soccer Info (PluginsWeb)](https://www.pluginsweb.es/mostrar-resultados-de-futbol-con-wordpress/) · [Football Pool (WordPress.org)](https://wordpress.org/plugins/football-pool/) · [Plugins categoría soccer](https://wordpress.org/plugins/tags/soccer/)

---

*Este documento alimenta la ampliación de la Home. La decisión final de qué módulos entran en el prototipo HTML y en qué orden la toma `director-proyecto` junto con `director-arte` y `desarrollador-frontend`, respetando la preferencia del cliente por un diseño aireado y no recargado (ver memoria de decisiones de diseño: navbar ≤5 ítems, sin sobrecargar la home de widgets).*
