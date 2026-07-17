# Ampliación de la Home — Secciones nuevas propuestas

**Proyecto:** Rediseño Habla Deportes — Etapa 1 de ampliación de Home
**Agente:** `arquitecto-contenido`
**Fecha:** 2026-07-16
**Insumo:** `diagnostico/03-arquitectura-contenido.md`, `diagnostico/00-informe-consolidado.md`, inventario de `propuesta/html/index.html`
**Método:** Investigación web (hábitos de consumo de audiencia deportiva, benchmark de medios de fútbol colombianos e internacionales, datos de comportamiento de fans en móvil/video corto/radio-podcast/redes) + lectura del prototipo HTML actual.

---

## 0. Por qué este documento

El cliente siente que la Home "le falta cosas". El diagnóstico de arquitectura (`03-arquitectura-contenido.md`) ya definió el **sitemap completo del sitio** (Noticias, Videos, En Vivo/Radio, Equipos, Torneos, Fichajes, etc.). Este documento **no repite eso**: se enfoca en un solo problema puntual — **qué módulos nuevos debe tener la página de inicio (`index.html`)** para que respire actualidad y le dé al usuario lo que busca al llegar, basado en evidencia de qué consume realmente la audiencia de fútbol colombiano, no en intuición.

Hoy la Home tiene 6 bloques: Topbar (radio), Hero (video destacado), Ticker de noticias, "El show de la mañana" (grilla de YouTube), "Mundo Junior" (nota destacada + lista) y Footer. Es corta para lo que un hincha de Junior/Selección espera encontrar al entrar a un medio deportivo.

---

## 1. Qué dice la evidencia (resumen de investigación)

| Hallazgo | Fuente / evidencia | Implicación para Habla Deportes |
|---|---|---|
| **Tabla de posiciones, resultados y calendario son el núcleo casi universal de un sitio de fútbol.** Sitios líderes (Futbolred, TUDN, Win Sports, Promiedos, Whoscored, Sofascore) construyen su home alrededor de resultados en vivo, tabla de posiciones y fixture. | Búsqueda "qué espera la audiencia de un sitio de fútbol"; benchmark Futbolred/Win Sports/ESPN Colombia. | Falta un módulo de **"Centro de partidos"** (próximo partido, último resultado, mini-tabla) — hoy no existe nada de esto en la Home. |
| **El propio sitio oficial de Junior FC dedica una página a "Próximos 4 partidos: fechas y horarios".** Es la forma más directa de validar que el hincha de Junior busca esta información. | `app.juniorfc.co` — "Próximos 4 partidos de Junior FC. Fechas y horarios". | El módulo de próximo partido/calendario es alta prioridad, no un "nice to have". |
| **Fichajes y convocatorias/alineaciones probables generan un volumen constante de notas** en los medios colombianos (Futbolred, Fichajes.com, Infobae) durante todo el año, no solo en ventana de mercado. | Búsquedas sobre Selección Colombia (convocatoria, alineación probable) y fichajes. | Ya existe `/fichajes` en el sitemap; en Home conviene un **módulo dedicado** (hoy los fichajes solo aparecen mezclados en "Mundo Junior"). |
| **El video corto vertical domina el consumo:** en 2025 el 63% del contenido deportivo es vertical, el video promedio bajó a ~1 minuto, y el 55% de los fans **descubre** equipos/jugadores a través de video corto. Los recaps de 2–5 min son el formato más elegido (55.7%), seguidos de clips verticales cortos (38.8%). | WSC Sports, "Trends Report: What Millions of Videos Say About Fan Behavior in 2025"; GWI "7 Sports Viewership Trends". | La Home necesita un módulo de **clips/reacciones cortas**, distinto de la grilla larga de "El show de la mañana" (que son videos de programa completo, no shorts). |
| **80% de la Gen Z prefiere ver deporte desde el celular.** La audiencia de Habla Deportes ya es mayoritariamente móvil (confirmado en diagnóstico técnico previo). | GWI, WSC Sports Fan Engagement Study 2025/26. | Todo módulo nuevo debe diseñarse **mobile-first** y priorizar formatos verticales/tarjetas, no tablas anchas de escritorio. |
| **El consumo de radio/streaming es mayoritariamente en diferido: ~60% del streaming se escucha grabado, no en vivo**, aunque la franja deportiva en vivo sigue siendo la de mayor audiencia. | El Independiente / EGM 2025 (España, tendencia trasladable); deRadios.com. | Un CTA de "en vivo" en el topbar **no basta**: hace falta un módulo de **episodios anteriores / radio a la carta** para capturar al oyente que llega fuera del horario 10 a.m.–12 m. |
| **En Colombia, WhatsApp es el segundo canal de consumo de noticias (35%), por encima de YouTube (34%) e Instagram (28%), solo detrás de Facebook (47%).** Medios colombianos (El Colombiano) fueron pioneros mundiales en Canales de WhatsApp. | Reuters Institute Digital News Report 2025 — Colombia. | Vale más un CTA a **Canal de WhatsApp** en la Home que apostar solo a redes clásicas: es el canal de distribución más subestimado hoy. |
| **~50% de los hinchas usa redes sociales en simultáneo mientras ve un partido**, y las encuestas/trivias son una táctica de bajo costo y alto enganche durante la previa y el partido. | Economis/Anda — "El Mundial impulsa el consumo digital"; Drimify, Qualifio. | Un módulo simple de **encuesta del hincha** (ej. "¿Quién debe ser titular?") es barato de producir y genera interacción medible. |
| **El contenido de nostalgia/efemérides es de bajo costo de producción y alta recurrencia** en medios deportivos (formato estándar "un día como hoy"), útil para llenar días sin partido. | Práctica estándar de la industria (ESPN, Futbolred usan secciones de este tipo); inferencia editorial de bajo riesgo, no requiere fuente externa. | Módulo de esfuerzo mínimo para mantener la Home "viva" incluso en fechas sin noticia fuerte. |

---

## 2. Secciones/módulos nuevos propuestos para la Home

Priorización: **P0** = construir ahora (alto valor de audiencia, evidencia fuerte, factible para equipo pequeño) · **P1** = siguiente ronda · **P2** = deseable, sin urgencia.

Esfuerzo: **Bajo** (contenido editorial simple, sin integraciones) · **Medio** (requiere estructura de datos propia o widget externo simple) · **Alto** (requiere API/integración externa mantenida).

### 2.1 Centro de Partidos (próximo partido + último resultado + mini-tabla) — **P0 · Esfuerzo medio**

- **Qué muestra:** dos tarjetas — "Próximo partido" (rival, fecha, hora, estadio, competencia) de Junior FC y, cuando aplique, de la Selección Colombia — junto a una mini tabla de posiciones de la Liga BetPlay (top 5 + posición de Junior resaltada) con link a la tabla completa.
- **Por qué la audiencia la quiere:** es el bloque más universal de cualquier sitio de fútbol (Futbolred, Win Sports, TUDN, Promiedos, Sofascore lo tienen todos como eje central) y el propio sitio oficial de Junior dedica una página entera a "próximos partidos, fechas y horarios" — evidencia directa de que es lo primero que un hincha busca.
- **Contenido/dato necesario:** calendario de partidos (Junior y Selección), resultado del último partido, tabla de posiciones Liga BetPlay actualizada.
- **De dónde sale:** **dato en vivo que requiere fuente externa.** Dos caminos: (a) MVP de bajo esfuerzo — actualización manual semanal por el equipo editorial (calendario se conoce con antelación); (b) a mediano plazo, widget/API de un proveedor de datos de fútbol (hay opciones embebibles gratuitas/pagas tipo iframe de resultados) para automatizar tabla y resultados. Recomendación: **arrancar manual (bajo esfuerzo), dejar la estructura lista para conectar una API en Fase 2/WordPress.**
- **Dónde encaja:** justo después del Ticker de noticias, antes de "El show de la mañana". Es información "de servicio" que el usuario espera resolver rápido antes de consumir video largo.

### 2.2 Selección Colombia (espejo de "Mundo Junior") — **P0 · Esfuerzo bajo**

- **Qué muestra:** la misma estructura que "Mundo Junior" (nota/video destacado + lista de 4 piezas), pero para la Selección Colombia.
- **Por qué la audiencia la quiere:** la Selección es, junto con Junior, uno de los **dos pilares editoriales** del medio (así lo define el propio README y el diagnóstico de arquitectura), pero hoy no tiene ningún bloque dedicado en la Home — solo aparece mezclada dentro de "Mundo Junior". Las búsquedas sobre convocatoria y alineación probable de Selección son de las más constantes en medios colombianos, lo que confirma demanda alta y recurrente.
- **Contenido/dato necesario:** los mismos videos/notas que ya produce el canal, solo que filtrados/etiquetados por "Selección Colombia" en vez de mezclados.
- **De dónde sale:** **contenido editorial que ya producen** (no requiere fuente nueva, solo etiquetado/categorización del contenido existente).
- **Dónde encaja:** inmediatamente después de "Mundo Junior", como su contraparte. Mantiene la simetría Junior/Selección que pide la identidad del medio.

### 2.3 Clips / Lo más visto en corto — **P0 · Esfuerzo bajo-medio**

- **Qué muestra:** grilla horizontal de formato vertical (estilo Reels/Shorts) con reacciones, momentos y frases cortas (<90 segundos), distinta de "El show de la mañana" (que son segmentos largos del programa).
- **Por qué la audiencia la quiere:** en 2025 el video vertical corto ya es el **63% de todo el contenido deportivo producido**, el 55.7% de los fans elige recaps de 2–5 min y un 38.8% adicional prefiere clips verticales aún más cortos; el 55% de los fans **descubre** equipos y jugadores a través de este formato. Es, además, el formato nativo de cómo la audiencia mayoritariamente móvil de Habla Deportes ya consume contenido fuera del sitio (Shorts/Reels).
- **Contenido/dato necesario:** clips cortos existentes o recortados del material que ya se produce para YouTube (muchos ya podrían existir como Shorts del canal).
- **De dónde sale:** **contenido editorial** — reutilización/recorte de material propio ya grabado; no requiere producción adicional grande, solo un flujo de recorte y etiquetado.
- **Dónde encaja:** justo después de "El show de la mañana" (contraste formato largo → formato corto) y antes de "Mundo Junior"/"Selección Colombia".

### 2.4 Radio a la carta / Episodios anteriores — **P0-P1 · Esfuerzo bajo**

- **Qué muestra:** franja con los últimos 4–6 episodios de "El show de la mañana" para escuchar/ver on-demand, más el CTA de "Escuchar en vivo" cuando esté al aire y de suscripción al podcast (Spotify/YouTube).
- **Por qué la audiencia la quiere:** el propio dato de la industria de radio/streaming indica que **cerca del 60% del consumo de audio en streaming es en diferido**, no en vivo. El programa solo transmite 2 horas al día (10 a.m.–12 m., L–V); fuera de esa franja, hoy la Home no le ofrece nada al oyente que no llegó a tiempo, más allá de la frase del topbar.
- **Contenido/dato necesario:** archivo de episodios del programa (ya existen como videos de YouTube).
- **De dónde sale:** **contenido editorial ya producido**, solo requiere organizarlo como "episodios" con fecha en vez de solo como videos sueltos.
- **Dónde encaja:** cerca del final de la Home, antes del footer — es un CTA de conversión (escuchar/suscribirse), funciona bien como bloque de cierre antes de la franja institucional.

### 2.5 Fichajes / Mercado — **P1 · Esfuerzo bajo**

- **Qué muestra:** lista corta tipo "ticker" (5-6 ítems) de rumores y fichajes confirmados de Junior, Liga BetPlay y Selección, con estado ("Rumor", "Confirmado", "Descartado").
- **Por qué la audiencia la quiere:** los fichajes y movimientos de mercado son de los temas de mayor tráfico recurrente en medios de fútbol colombianos durante todo el año (no solo en ventana oficial) — así lo confirma el propio ticker actual de la Home, que ya incluye titulares de fichajes, y el volumen de cobertura de sitios como Futbolred sobre el tema.
- **Contenido/dato necesario:** notas/rumores que el equipo editorial ya redacta.
- **De dónde sale:** **contenido editorial.**
- **Dónde encaja:** después de "Selección Colombia", antes de "Radio a la carta". Es contenido de alto interés pero de "consulta rápida" (lista, no video), funciona bien como respiro visual entre bloques de video.

### 2.6 La Voz del Hincha (encuesta rápida) — **P1-P2 · Esfuerzo bajo**

- **Qué muestra:** una pregunta simple y cambiante (ej. "¿Quién debe ser titular ante Tolima?", "¿Cuadrado a Junior: sí o no?") con resultados en vivo tipo barra de porcentaje.
- **Por qué la audiencia la quiere:** cerca de la mitad de los hinchas usa redes sociales en simultáneo mientras sigue un partido, y las encuestas/trivias son la táctica de menor costo y mayor interacción reportada por herramientas de marketing deportivo para mantener al fan activo (no solo consumiendo, sino participando).
- **Contenido/dato necesario:** la pregunta (editorial, cambia semanalmente) + un widget de encuesta simple.
- **De dónde sale:** **contenido editorial + widget ligero** (herramienta de encuestas embebible; no requiere API compleja, esfuerzo bajo).
- **Dónde encaja:** intercalada entre "Fichajes" y el footer, o como columna lateral del "Centro de Partidos" si el layout lo permite. No debe competir visualmente con el video.

### 2.7 Un día como hoy (efemérides) — **P2 · Esfuerzo bajo**

- **Qué muestra:** una pieza breve de nostalgia/historia relacionada con la fecha (título de Junior, gol histórico de la Selección, etc.).
- **Por qué la audiencia la quiere:** es un formato estándar de bajo costo en medios deportivos para mantener la Home "viva" en días sin partido ni noticia fuerte de coyuntura; no requiere evidencia externa adicional — es una práctica editorial probada y de riesgo mínimo.
- **Contenido/dato necesario:** una efeméride corta redactada por el equipo, idealmente con foto de archivo.
- **De dónde sale:** **contenido editorial**, banco de contenido reutilizable año tras año.
- **Dónde encaja:** al final, justo antes del footer — llena el "día flojo" sin ocupar espacio prioritario.

### 2.8 CTA de Canal de WhatsApp — **P1 · Esfuerzo bajo**

- **Qué muestra:** una franja o tarjeta compacta invitando a seguir el Canal de WhatsApp de Habla Deportes para alertas de último minuto (goles, fichajes confirmados, arranque del programa).
- **Por qué la audiencia la quiere:** en Colombia, WhatsApp es el **segundo canal de consumo de noticias (35%)**, por encima de YouTube (34%) e Instagram (28%) — un dato que suele subestimarse frente a la obsesión por redes "tradicionales". Medios colombianos (El Colombiano) fueron pioneros mundiales en adoptar Canales de WhatsApp precisamente por esto.
- **Contenido/dato necesario:** ninguno nuevo — solo el enlace al Canal de WhatsApp del medio (crearlo si no existe).
- **De dónde sale:** **contenido editorial/operativo**, sin integración técnica más allá de un link.
- **Dónde encaja:** en la franja final junto a "Radio a la carta" / cerca del footer, como parte de los CTAs de conversión de la Home (junto a "Suscríbete a YouTube").

---

## 3. Wireframe textual — nuevo orden de la Home

Mobile-first (el orden vertical es el mismo en desktop; en desktop algunos bloques marcados se acomodan en 2 columnas). Se marca **[NUEVO]** lo que no existe hoy.

```
1.  Topbar — "El show de la mañana, L-V 10:00 a.m."           (existente)
2.  Header — logo, menú, buscador, CTA Suscríbete              (existente)
3.  Hero — video/nota destacada del día                        (existente)
4.  Ticker — últimas noticias (scroll horizontal)               (existente)

5.  [NUEVO] Centro de Partidos
    ├─ Próximo partido Junior FC (rival · fecha · hora · estadio)
    ├─ Próximo partido Selección Colombia (si aplica)
    └─ Mini tabla de posiciones Liga BetPlay (Junior resaltado) + "Ver tabla completa"

6.  El show de la mañana — grilla de videos del programa       (existente)

7.  [NUEVO] Clips — formato vertical corto, reacciones y momentos

8.  Mundo Junior — feature + lista                              (existente)

9.  [NUEVO] Selección Colombia — feature + lista (espejo de #8)

10. [NUEVO] Fichajes / Mercado — lista corta tipo ticker

11. [NUEVO] Radio a la carta — últimos episodios + CTA en vivo/suscripción

12. [NUEVO] La Voz del Hincha — encuesta rápida                 (P1/P2, opcional en v1)

13. [NUEVO] Un día como hoy — efeméride breve                   (P2, opcional en v1)

14. [NUEVO] Franja de conversión — CTA Canal de WhatsApp + redes
            (puede fusionarse visualmente con el bloque de sponsor)

15. Espacio de sponsor (definido en 03-arquitectura-contenido.md)  (existente/pendiente)

16. Footer                                                       (existente, ya contempla
                                                                    la mayoría de estos links)
```

**Recomendación de alcance para "v1" (no recargar):** construir en esta primera ronda los bloques **5, 7, 8→9, 10, 11 y 14** (P0/P1 con evidencia más fuerte). Dejar **12 y 13** (encuesta, efemérides) para una segunda pasada — son de bajo costo pero no son los que más impacto tienen; agregarlos todos de una vez sí volvería la Home "recargada", que es justo lo que el cliente pidió evitar en la fase de diseño visual.

---

## 4. Tabla resumen — prioridad, esfuerzo y tipo de dato

| # | Sección | Prioridad | Esfuerzo | Tipo de contenido |
|---|---|---|---|---|
| 1 | Centro de Partidos (próximo partido + resultado + mini-tabla) | **P0** | Medio | Dato en vivo — externo (MVP manual, luego API/widget) |
| 2 | Selección Colombia (bloque espejo de Mundo Junior) | **P0** | Bajo | Editorial (recategorización de contenido existente) |
| 3 | Clips / formato corto vertical | **P0** | Bajo-Medio | Editorial (recorte de material propio) |
| 4 | Radio a la carta / episodios anteriores | **P0-P1** | Bajo | Editorial (archivo del programa) |
| 5 | Fichajes / Mercado (lista tipo ticker) | **P1** | Bajo | Editorial |
| 6 | CTA Canal de WhatsApp | **P1** | Bajo | Operativo (solo enlace) |
| 7 | La Voz del Hincha (encuesta) | **P1-P2** | Bajo | Editorial + widget ligero |
| 8 | Un día como hoy (efemérides) | **P2** | Bajo | Editorial |

---

## 5. Notas para las siguientes fases

- Este documento **no define diseño visual** (paleta, tipografía, densidad de tarjetas) — eso corresponde a `director-arte`, respetando la pauta ya acordada de diseño aireado/limpio y navbar ≤5 ítems.
- El **"Centro de Partidos"** es el único módulo que a mediano plazo pide una fuente de datos externa real (API de resultados/tabla). Para el prototipo HTML y el lanzamiento inicial en WordPress, se recomienda poblarlo **manualmente** (el equipo ya conoce el calendario con anticipación) y dejar la integración automática como mejora de Fase 2/3, sin bloquear el resto de la Home.
- Los módulos "Selección Colombia", "Clips", "Radio a la carta" y "Fichajes" **no requieren contenido nuevo que el equipo no sepa producir hoy** — son, en su mayoría, una reorganización/etiquetado de lo que ya se graba para YouTube y la radio. Esto los hace de bajo riesgo y alto retorno para un equipo pequeño.
- En el modelo de WordPress (ver `03-arquitectura-contenido.md`, sección 5), estos módulos de Home se resuelven con: consultas destacadas por taxonomía (`equipo=seleccion-colombia`, `tema=fichajes`), un campo de "próximo partido" en opciones del tema o un CPT ligero `partido`, y una lista de reproducción/CPT `episodio` para la radio a la carta.
