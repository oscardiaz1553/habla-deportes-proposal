# 03 — Arquitectura de Información y Estrategia de Contenido

**Proyecto:** Rediseño Habla Deportes
**Agente:** `arquitecto-contenido`
**Fecha:** 2026-07-13
**Sitio auditado:** https://habladeportes.co/
**Método:** Inventario del sitio real (navegador in-app) + inspección de la API WordPress (`/wp-json/wp/v2/…`) para ver la estructura de contenido de fondo.

---

## 0. Resumen ejecutivo (TL;DR)

El sitio de Habla Deportes **no tiene arquitectura de contenido**. Es, en la práctica, **una sola página** que incrusta un carrusel de 265 videos de YouTube, un logo de patrocinador (Transelca) y un footer. Detrás, en WordPress, **no existe ni un solo contenido editorial propio**: todas las categorías, posts y tags son **contenido demo del theme** (en inglés, de 2021: "Greenland Unicorns", "When Chocolate was Medicine", etc.). Hay además una tienda **WooCommerce huérfana y vacía**. La radio —uno de los dos pilares del medio— **solo existe como una frase de texto en el footer**: no hay reproductor, ni programación, ni página.

En otras palabras: todo el valor del medio vive **fuera del sitio** (en YouTube). El sitio actual no retiene, no organiza, no permite descubrir por equipo/torneo, no da hogar a la radio y no da visibilidad estructurada a los sponsors. **Esto es una oportunidad, no un problema:** partimos casi de cero y podemos diseñar una arquitectura correcta desde el principio.

La propuesta de este documento convierte el sitio en un **hub de medio deportivo video-first**: home viva de actualidad, navegación por **equipos** y **torneos**, plantillas para **video**, **nota**, **radio/en vivo** y **programa**, y un modelo de contenido limpio, listo para trasladar a WordPress en la Fase 2.

---

## 1. Diagnóstico de la estructura actual

### 1.1 Qué encontramos realmente (inventario)

**Página de inicio (`/`, "Inicio"):**
- Un **carrusel de videos de YouTube** (indicador "265") con títulos reales del canal, p. ej.:
  - "¡CONTRA TODO PRONÓSTICO! NOTICIA DE ÚLTIMA HORA SOBRE EL FUTURO DE LA SELECCIÓN COLOMBIA"
  - "¿LLEGARÁ? JUANFER QUINTERO Y LA REALIDAD DE SU POSIBLE REGRESO A JUNIOR"
  - "SE ACABÓ EL SUEÑO: COLOMBIA ELIMINADA EN PENALES"
  - "¡LLEGÓ EL PRIMER REFUERZO! JUNIOR SE EMPIEZA A ARMAR PARA EL TRICAMPEONATO"
- Botón **"Suscríbete al canal"** (link a YouTube con `sub_confirmation=1`).
- Logo/enlace del patrocinador **Transelca** (repetido varias veces).
- **Buscador** (lupa que abre un formulario de búsqueda).

**Header:** logo, enlace a Transelca, enlace a YouTube, buscador. **Sin menú de navegación real.**

**Menú de navegación:** un único ítem, **"Inicio"**. No hay secciones, ni equipos, ni torneos, ni radio, ni "acerca de".

**Footer:** frase de la radio *("El show de las mañanas en Barranquilla es de lunes a viernes desde las 10:00 a.m. hasta las 12:00 m.")*, logo Transelca, "Políticas" (Política de privacidad, Términos y Condiciones), "Dirección: Barranquilla", "Contáctanos: info@habladeportes.co", crédito "Realizado por spacerock".

**Contenido de fondo en WordPress (vía API REST):**
- **Categorías:** `Blog`, `Branding`, `Marketing`, `Optimization`, `Planning`, `SEO`, `Society`, `Uncategorized`, `Web`. → **Todas demo del theme.** Ninguna es deportiva.
- **Posts:** `¡Hola, mundo!`, `Easy Strategies to Help Your Family Succeed in 2020`, `Women Balancing Family And Work During COVID-19`, `Greenland Unicorns and the Magical Alicorn`, `When Chocolate was Medicine`… → **Todo demo, en inglés, de 2021.** Cero notas deportivas.
- **Tags:** `Enjoy`, `Holiday`, `Life`. → Demo.
- **Páginas:** `Inicio`, `Tienda`, `Carrito`, `Finalizar compra`, `Mi cuenta`, `Política de privacidad`, `Términos y Condiciones`. → **WooCommerce instalado pero vacío/huérfano** (no hay tienda enlazada en la navegación).
- Página 404 del sitio devuelve *"No Posts found."* → el loop de blog existe pero no tiene contenido propio que mostrar.

### 1.2 Problemas de estructura (diagnóstico)

| # | Problema | Impacto en el negocio / audiencia |
|---|----------|-----------------------------------|
| P1 | **El sitio es una sola página.** No hay arquitectura: ni secciones, ni jerarquía, ni rutas internas de contenido. | El usuario no puede explorar; no hay "siguiente paso" ni razón para quedarse. Cero retención y cero SEO de contenido. |
| P2 | **Todo el contenido vive en YouTube; el sitio solo lo incrusta.** | El medio no controla su audiencia ni sus datos; depende 100% del algoritmo de YouTube. El sitio no aporta valor propio. |
| P3 | **No hay taxonomía deportiva.** No se puede navegar por equipo (Junior FC, Selección) ni torneo (Copa América, Liga BetPlay). Las categorías reales son basura de theme demo. | El fan de Junior o de la Selección no encuentra "su" contenido agrupado. Se pierde el tráfico de búsqueda por equipo/torneo, que es el más valioso. |
| P4 | **La radio no tiene hogar en el sitio.** Solo una frase en el footer. No hay reproductor, ni "en vivo", ni parrilla. | Se desperdicia el segundo pilar del medio. El oyente no puede escuchar desde la web ni saber cuándo están al aire. |
| P5 | **Navegación inexistente** (solo "Inicio"). | No hay forma de descubrir contenido; el sitio se siente roto/incompleto y poco profesional. |
| P6 | **No hay sistema de notas/artículos en uso.** El blog está lleno de demo en inglés. | Sin contenido escrito no hay SEO, ni indexación de noticias, ni long-tail de fichajes/resultados. Google no tiene qué rankear. |
| P7 | **Sponsors sin estructura.** Transelca aparece como logo suelto y repetido, sin espacios definidos. | La monetización no es escalable ni vendible; no hay "inventario" de espacios publicitarios claro para ofrecer a marcas. |
| P8 | **WooCommerce huérfano.** Tienda/carrito instalados pero vacíos y sin enlazar. | Deuda técnica y confusión. O se activa con intención (merch) o se retira. |
| P9 | **Sin páginas institucionales.** No hay "Nosotros/Quiénes somos" ni página de contacto real (solo un email en footer). | Baja credibilidad ante audiencia y, sobre todo, ante **anunciantes** que necesitan saber quién es el medio y cómo pautar. |
| P10 | **Metadatos y frescura ausentes.** Sin fechas, sin "última hora", sin destacados curados. | La home no "respira actualidad", que es lo que un medio necesita para que el usuario vuelva a diario. |

> **Conclusión del diagnóstico:** no se trata de reorganizar contenido existente, sino de **crear la arquitectura de información que el medio nunca tuvo**, usando como materia prima el catálogo de YouTube (265+ videos), la radio y las notas escritas que se produzcan.

---

## 2. Arquitectura de información nueva (Sitemap)

Principios que guían el sitemap (cada uno atado a un objetivo):

- **Video-first, pero con casa propia** → el video manda en la home y tiene su plantilla, pero embebido en un sitio que retiene (objetivo: retención + control de audiencia).
- **Navegar por lo que al fan le importa: su equipo y su torneo** → taxonomías de Equipos y Torneos (objetivo: SEO por entidad + retención del hincha).
- **La radio es un pilar, no una nota al pie** → sección propia con reproductor y parrilla (objetivo: escucha de radio).
- **La actualidad primero** → la home es un diario, no una landing estática (objetivo: recurrencia diaria).
- **Convertir visitas en suscriptores/oyentes** → CTAs de YouTube y radio presentes en todas las plantillas (objetivo: suscripción YouTube).
- **Sponsors con inventario definido** → espacios de patrocinio estructurados y una página comercial (objetivo: monetización).

### 2.1 Sitemap propuesto (árbol)

```
Habla Deportes
│
├── Home (/)                              ← portada de actualidad, video-first
│
├── Noticias (/noticias)                  ← índice de notas escritas (últimas)
│   └── Nota / Artículo (/noticias/{slug})
│
├── Videos (/videos)                      ← catálogo del canal de YouTube
│   ├── por Equipo / Torneo / Programa (filtros)
│   └── Video (/videos/{slug})
│
├── En Vivo / Radio (/en-vivo)            ← reproductor + estado "al aire" + parrilla
│   └── Programas (/programas)
│       └── Programa (/programas/{slug})  ← ficha del show (p. ej. "El show de las mañanas")
│
├── Equipos (/equipos)                    ← hub de taxonomía por club/selección
│   ├── Junior FC (/equipos/junior-fc)
│   ├── Selección Colombia (/equipos/seleccion-colombia)
│   ├── Atlético Nacional (/equipos/atletico-nacional)
│   └── … (otros clubes según cobertura)
│
├── Torneos (/torneos)                    ← hub de taxonomía por competición
│   ├── Liga BetPlay (/torneos/liga-betplay)
│   ├── Copa América (/torneos/copa-america)
│   ├── Eliminatorias / Mundial (/torneos/eliminatorias)
│   └── … (Copa Colombia, Libertadores, etc.)
│
├── Fichajes (/fichajes)                  ← sección/tema editorial de alto tráfico
│
├── Nosotros (/nosotros)                  ← historia, equipo humano, cobertura
│
├── Contacto (/contacto)                  ← contacto general + Publicidad/Pauta (sponsors)
│
└── Legales
    ├── Política de privacidad (/politica-privacidad)
    └── Términos y Condiciones (/terminos-y-condiciones)
```

### 2.2 Modelo de taxonomías

El contenido (videos y notas) se clasifica de forma **cruzada** con tres ejes independientes, de modo que una misma pieza aparece en varios "estantes":

| Taxonomía | Ejemplos de términos | Para qué sirve (objetivo) |
|-----------|----------------------|----------------------------|
| **Equipos** | Junior FC, Selección Colombia, Atlético Nacional, Millonarios, América… | Retención del hincha; SEO por entidad (búsquedas tipo "Junior noticias"). |
| **Torneos** | Liga BetPlay, Copa América, Eliminatorias, Copa Colombia, Libertadores | Picos de tráfico por evento; agrupa cobertura de coyuntura. |
| **Tipo de contenido** | Video, Nota, Radio/Podcast, Transmisión en vivo | Permite plantillas y filtros por formato; alimenta "Videos" vs "Noticias". |
| **Temas** (etiquetas libres) | Fichajes, Rumores, Resultados, Análisis, Última hora | Long-tail y secciones temáticas transversales (p. ej. `/fichajes`). |
| **Programa** (para radio/video) | El show de las mañanas | Agrupa episodios/clips de un mismo show. |

> **Ejemplo:** el video "JUANFER QUINTERO Y SU POSIBLE REGRESO A JUNIOR" → Equipo: *Junior FC*; Torneo: *Liga BetPlay*; Tipo: *Video*; Tema: *Fichajes*. Aparece en Home, en `/equipos/junior-fc`, en `/fichajes` y en `/videos`.

---

## 3. Plantillas de página para el prototipo HTML

Estas son las plantillas que la Fase 2 (director-arte + desarrollador-frontend) debe construir. Se priorizan las **7 esenciales** para el prototipo navegable; las demás son "nice to have" si hay tiempo.

> Prioridad: **P0** = imprescindible en el prototipo · **P1** = recomendada · **P2** = opcional.

### 3.1 Home — `/` · **P0**
**Propósito:** que el sitio "respire actualidad" y sea el hogar del medio. Retener, empujar a YouTube y a la radio, y dar visibilidad premium al sponsor. Es la página que se muestra al cliente para vender el rediseño.

**Bloques de contenido (de arriba a abajo):**
1. **Header** con logo, menú principal, buscador y CTA "Suscríbete" (YouTube).
2. **Barra "En vivo / Al aire"**: si la radio está en horario (L–V 10:00–12:00), badge "AL AIRE AHORA" con botón de escucha; si no, "Próximo programa".
3. **Hero destacado**: video o nota principal del día (imagen grande + título + play). Espacio para **sponsor** ("Presentado por Transelca").
4. **Últimos videos** (carrusel/grilla alimentada del canal de YouTube — reemplaza el carrusel actual, pero curado y con títulos legibles).
5. **Últimas noticias** (grilla de notas escritas).
6. **Bloques por equipo destacado** (p. ej. "Lo último de Junior FC" y "Selección Colombia").
7. **Franja de la radio / programa** con CTA de escucha y horario.
8. **Espacio de patrocinio** (banner de sponsor definido).
9. **Footer** completo.

### 3.2 Sección / Índice (Noticias · Videos · Equipo · Torneo · Fichajes) — `/noticias`, `/videos`, `/equipos/{x}`, `/torneos/{x}` · **P0**
**Propósito:** listar y dejar explorar todo el contenido de una categoría/taxonomía. Es la plantilla más reutilizada (una sola plantilla sirve para sección, equipo, torneo y tema). Objetivo: descubrimiento y retención por interés.

**Bloques:**
1. **Encabezado de sección** (nombre + descripción corta; para equipo/torneo, escudo/badge y contexto).
2. **Destacado de la sección** (pieza principal).
3. **Filtros** (por tipo: video/nota; por torneo/equipo cuando aplica).
4. **Grilla de piezas** (tarjetas con thumbnail, tipo, título, fecha).
5. **Paginación / "cargar más".**
6. **Módulo relacionado** (otros equipos/torneos) + **espacio de sponsor**.

### 3.3 Artículo / Nota — `/noticias/{slug}` · **P0**
**Propósito:** dar hogar y SEO al contenido escrito (fichajes, resultados, análisis). Objetivo: tráfico de búsqueda + tiempo en sitio.

**Bloques:**
1. **Titular, bajada, meta** (fecha, autor, equipo/torneo, tiempo de lectura).
2. **Imagen o video destacado.**
3. **Cuerpo** (texto enriquecido, con posibilidad de incrustar video de YouTube).
4. **CTA de suscripción** a YouTube / escucha de radio (intercalado).
5. **Etiquetas** (equipos, torneos, temas) enlazadas a sus índices.
6. **"Sigue leyendo" / relacionados** por equipo o torneo.
7. **Espacio de sponsor** (lateral o intercalado).

### 3.4 Página de Video — `/videos/{slug}` · **P0**
**Propósito:** ver un video del canal dentro del sitio (no perder al usuario en YouTube), con CTA fuerte de suscripción. Objetivo: retención + suscripción YouTube.

**Bloques:**
1. **Reproductor** (embed de YouTube) grande.
2. **Título, fecha, descripción** del video.
3. **CTA "Suscríbete al canal"** prominente.
4. **Etiquetas** (equipo/torneo/programa).
5. **"Más videos"** (del mismo equipo/torneo/programa).
6. **Nota relacionada** (si existe cobertura escrita del mismo tema).
7. **Espacio de sponsor.**

### 3.5 Radio / En Vivo — `/en-vivo` · **P0**
**Propósito:** dar por fin casa a la radio. Escuchar en vivo, saber si están al aire y ver la parrilla. Objetivo: escucha de radio (pilar del medio).

**Bloques:**
1. **Reproductor de audio** persistente (stream en vivo) con estado "AL AIRE / FUERA DE AIRE".
2. **Programa actual / próximo** con horario (L–V 10:00–12:00).
3. **Parrilla / programación** de la semana.
4. **Últimos episodios / clips** (del programa en YouTube).
5. **CTA** de suscripción y redes.
6. **Espacio de sponsor** (patrocinador del programa).

### 3.6 Programa (ficha del show) — `/programas/{slug}` · **P1**
**Propósito:** presentar cada show ("El show de las mañanas"), sus presentadores y su archivo. Objetivo: identidad del programa + fidelización.

**Bloques:** cabecera del programa (nombre, horario, presentadores), descripción, botón de escucha en vivo/últimos episodios, archivo de emisiones, sponsor del programa.

### 3.7 Nosotros / About — `/nosotros` · **P1**
**Propósito:** dar credibilidad ante audiencia y **anunciantes**. Objetivo: confianza + soporte a la venta de pauta.

**Bloques:** historia del medio, cobertura (equipos/torneos), equipo humano, presencia (YouTube, radio, redes), métricas/alcance si se quieren mostrar, CTA a Contacto/Publicidad.

### 3.8 Contacto / Publicidad — `/contacto` · **P1**
**Propósito:** canal de contacto general y, sobre todo, **captación de sponsors** (pauta). Objetivo: monetización.

**Bloques:** formulario de contacto, email (info@habladeportes.co), datos (Barranquilla), bloque específico **"Pauta con nosotros / Publicidad"** con propuesta de valor y espacios disponibles, redes sociales.

### 3.9 (Opcional) Tienda — `/tienda` · **P2**
**Propósito:** solo si el cliente quiere activar merchandising. Si no, **retirar WooCommerce** para eliminar deuda técnica. Se evalúa con el cliente; no es prioridad del prototipo.

### 3.10 Componentes / plantillas de sistema · **P0 transversal**
- **Header global** (menú, buscador, CTA YouTube, indicador "al aire").
- **Footer global** (radio, secciones, legales, contacto, redes, sponsor).
- **Tarjeta de contenido** (video/nota) reutilizable en toda grilla.
- **Bloque de sponsor** reutilizable (inventario definido).
- **Resultados de búsqueda** `/buscar` (P1).
- **404** (P2, pero ya existe y hay que rehacerla).

---

## 4. Estructura de navegación

Priorizada por los objetivos del medio: **retención**, **suscripción a YouTube**, **escucha de radio** y **visibilidad de sponsors**.

### 4.1 Menú principal (header)

Orden pensado para llevar al usuario a lo que más importa, en este orden de prioridad:

```
[Logo]   Inicio · Noticias · Videos · En Vivo/Radio · Equipos ▾ · Torneos ▾ · Fichajes        [🔍]  [▶ Suscríbete]
```

- **En Vivo / Radio** va destacado (color/badge "AL AIRE" cuando corresponda) → objetivo escucha de radio.
- **Equipos ▾** y **Torneos ▾** son menús desplegables con los términos más relevantes (Junior FC, Selección Colombia / Copa América, Liga BetPlay…) → objetivo retención por interés + SEO.
- **CTA "Suscríbete"** (botón, color de marca, ícono YouTube) siempre visible → objetivo suscripción YouTube.
- **Buscador** accesible (ya existe hoy, se conserva).
- En **móvil**: menú hamburguesa; se mantienen visibles fuera del menú el botón "Suscríbete" y el acceso a "En Vivo".

**Justificación del orden:** primero actualidad (Inicio/Noticias/Videos), luego el pilar diferenciador en directo (En Vivo/Radio), luego la navegación por interés profundo (Equipos/Torneos) y un atajo al tema de mayor tráfico coyuntural (Fichajes). Lo institucional (Nosotros/Contacto) baja al footer para no competir con el contenido.

### 4.2 Navegación secundaria / contextual
- **Chips de filtro** en índices (por tipo, torneo, equipo).
- **Migas de pan** (breadcrumbs) en notas y videos: Home › Equipo/Torneo › Pieza.
- **Enlaces de taxonomía** dentro de cada nota/video (etiquetas que llevan a los índices).

### 4.3 Footer

Estructurado en columnas, dando espacio a lo institucional, la radio y el sponsor:

```
COL 1 — Marca            COL 2 — Secciones      COL 3 — El programa        COL 4 — Institucional
[Logo]                   Noticias               "El show de las mañanas"    Nosotros
Barranquilla, Colombia   Videos                 L–V 10:00 a.m.–12:00 m.     Contacto / Publicidad
Redes: YouTube, IG…      En Vivo / Radio        [▶ Escuchar / Suscribirse]  Política de privacidad
[▶ Suscríbete YouTube]   Equipos · Torneos                                  Términos y Condiciones

──────────────────────────────────────────────────────────────────────────────
Patrocinador: [ Transelca ]      © 2026 Habla Deportes — Realizado por spacerock
```

- El bloque de **sponsor** tiene un lugar fijo y digno (no un logo suelto y repetido como hoy).
- Se conserva la frase de la radio, pero ahora acompañada de un **CTA real de escucha**.
- Redes sociales explícitas → refuerza suscripción/seguimiento.

---

## 5. Notas de mapeo a WordPress (Fase 2)

El prototipo debe diseñarse pensando en este modelo, para que la implementación en WP sea directa.

### 5.1 Limpieza previa (obligatoria)
- **Eliminar todo el contenido demo del theme**: categorías (Branding, Marketing, SEO, Society, Web, Planning, Optimization…), posts en inglés (Greenland Unicorns, etc.) y tags (Enjoy, Holiday, Life).
- **Decidir sobre WooCommerce**: activarlo con intención (merch) o desinstalarlo. Recomendación: **retirarlo** salvo que haya plan de tienda.

### 5.2 Tipos de contenido (post types)

| Plantilla del prototipo | Implementación en WordPress | Notas |
|-------------------------|-----------------------------|-------|
| Nota / Artículo | **Post** estándar (`post`) | El post nativo, ahora sí con contenido real. |
| Video | **CPT `video`** (o Post con campo de URL de YouTube) | Recomendado CPT propio con campo "YouTube ID" para poder listar `/videos` y filtrar. Puede alimentarse manual o vía feed del canal. |
| Programa | **CPT `programa`** | Ficha del show; pocos registros, larga vida. |
| Radio / En Vivo | **Página** `/en-vivo` + opción de tema para el stream y el horario | El "al aire" se resuelve con el horario (L–V 10–12) + URL del stream. |
| Nosotros, Contacto, Legales, Tienda | **Páginas** estáticas | Contacto con formulario (plugin de formularios). |
| Home | **Página** de inicio + plantilla custom (front-page) | Curación de destacados vía campos/opciones del tema. |

### 5.3 Taxonomías en WordPress

| Taxonomía del modelo | Implementación WP | Aplica a |
|----------------------|-------------------|----------|
| **Equipos** | Taxonomía personalizada `equipo` (jerárquica, tipo categoría) | Post, `video` |
| **Torneos** | Taxonomía personalizada `torneo` (jerárquica) | Post, `video` |
| **Tipo de contenido** | Se resuelve por post type (Post vs CPT `video`) | — |
| **Temas** (Fichajes, Resultados, Análisis, Última hora) | **Categorías** nativas *o* taxonomía `tema` | Post, `video` |
| **Programa** | Taxonomía `programa` **o** relación al CPT `programa` | `video`, Post |

> `/fichajes`, `/equipos/junior-fc`, `/torneos/copa-america`, etc. se sirven con **una sola plantilla de archivo** (`archive.php` / plantilla de taxonomía) que corresponde a la plantilla **"Sección / Índice"** del prototipo (3.2). Esto reduce el trabajo de tema y garantiza consistencia.

### 5.4 Menús y widgets
- El **menú principal** y el **footer** se arman con el gestor de menús de WordPress apuntando a las páginas/archivos anteriores. Los desplegables **Equipos ▾** y **Torneos ▾** se llenan con los términos de sus taxonomías.
- El **bloque de sponsor** debe ser un área/widget reutilizable (o campo de opciones del tema) para que el cliente cambie de patrocinador sin tocar código.
- El indicador **"AL AIRE"** se puede resolver con lógica de horario en el tema (sin depender de una API externa) más una URL de stream configurable.

---

## 6. Justificación resumida (estructura → objetivo)

| Decisión de arquitectura | Objetivo que persigue |
|--------------------------|------------------------|
| Home de actualidad video-first (no landing estática) | Recurrencia diaria + retención |
| Sección "En Vivo/Radio" con reproductor y parrilla | Escucha de radio (segundo pilar) |
| Taxonomías de Equipos y Torneos + una plantilla de índice reutilizable | Retención del hincha + SEO por entidad, con bajo costo de desarrollo |
| Plantilla de Video con CTA de suscripción y "más videos" | Suscripción a YouTube sin perder al usuario del sitio |
| Sistema real de Notas/Artículos con taxonomías | SEO de contenido, long-tail de fichajes/resultados |
| Sección `/fichajes` propia | Capturar el tema de mayor tráfico coyuntural |
| Bloques e inventario de sponsor definidos + página de Publicidad | Monetización escalable y vendible |
| Página Nosotros/Contacto | Credibilidad ante audiencia y anunciantes |
| Limpieza de demo + WooCommerce | Reducir deuda técnica, mejorar percepción de calidad |

---

*Este documento define **estructura y contenido**. El aspecto visual lo define `director-arte` y el HTML lo construye `desarrollador-frontend` a partir de estas plantillas y esta navegación.*
