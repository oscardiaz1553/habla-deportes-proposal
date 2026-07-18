# Contenido del megamenú — Header de Habla Deportes

**Proyecto:** Rediseño Habla Deportes
**Agente:** `arquitecto-contenido`
**Fecha:** 2026-07-17
**Alcance:** define QUÉ va dentro de cada desplegable del header (columnas, enlaces, destacado). No define maquetación, animación de hover ni CSS — eso es de `director-arte` / `desarrollador-frontend`.

---

## 0. Punto de partida

El header actual (`propuesta/html/index.html`, líneas ~96-123) tiene:

```
Inicio · Junior · Futbol ▾ · Programas ▾ · Más ▾        [Suscribirse]
```

- **Inicio** y **Junior**: enlaces simples (Junior apunta a `#mundo-junior`, ancla dentro de la home; en el resto de páginas apunta a `seccion.html`).
- **Futbol ▾, Programas ▾, Más ▾**: ya tienen el caret (`.nav-link__caret`) pero **sin contenido real detrás** — son placeholders (`href="#"` o `seccion.html` genérico) a la espera de este documento.
- Páginas reales a las que se puede enlazar hoy: `index.html`, `seccion.html` (plantilla de índice reutilizable — hoy es un comodín sin filtros reales), `articulo.html`, `video.html`, `programas.html`, `en-vivo.html`.
- No existen todavía páginas propias de **Nosotros** ni **Contacto/Publicidad** (solo `mailto:info@habladeportes.co` y anclas `#`). Se marcan como pendientes de construir (ver 03-arquitectura-contenido.md, plantillas 3.7 y 3.8).

---

## 1. Qué ítems llevan megamenú y cuáles quedan simples

| Ítem | Tipo | Justificación |
|---|---|---|
| **Inicio** | Enlace simple | Es el destino, no necesita submenú; cualquier desplegable aquí sería ruido. |
| **Junior ▾** | **Megamenú** (se agrega el caret, hoy no lo tiene) | Junior FC es el equipo insignia del medio: en el inventario de contenido es, con diferencia, el que más piezas y más tráfico concentra (sección propia en home, la mayoría de los videos/clips/fichajes). Convertirlo en megamenú lo trata como un **hub**, no como un simple ancla a la home, y acelera su llegada a `/equipos/junior-fc` en WP sin esperar a esa página. |
| **Fútbol ▾** | **Megamenú** | Es el paraguas de todo lo demás: equipos rivales, selección, torneos. Sin megamenú, el usuario tendría que pasar por un índice intermedio para llegar a "Millonarios" o "Copa América"; con megamenú, dos clics se convierten en uno. Objetivo: SEO por entidad + retención por interés. |
| **Programas ▾** | **Megamenú** (compacto, 2 columnas) | Agrupa el segundo pilar del medio (radio/show) con su contenido derivado (episodios, clips). Aunque hoy es un solo programa, el megamenú dice "esto también importa" y deja espacio para crecer (más programas a futuro) sin rediseñar el nav. |
| **Más ▾** | **Dropdown simple de 1 columna** (no megamenú multicolumna) | Es el cajón institucional (Nosotros, Contacto/Publicidad, Legales, redes). El contenido es corto y no gana nada con columnas — forzar un megamenú aquí sería recargar visualmente algo que debe ser discreto. Mantenerlo como lista simple respeta el pedido del cliente de un nav **aireado, no recargado**. |
| **Suscribirse** | Botón CTA (no es parte del nav ni lleva desplegable) | Debe seguir siendo la acción más visible del header; un megamenú restaría urgencia al CTA. |

**Resumen de prioridad:** 3 megamenús multicolumna (Junior, Fútbol, Programas) + 1 dropdown simple (Más). Columnas de 4-6 enlaces máximo cada una, según el pedido de densidad del cliente.

---

## 2. Junior ▾ — Megamenú

**Objetivo de negocio:** retención del hincha más numeroso del medio; puerta de entrada al futuro `/equipos/junior-fc`.

| Columna 1 — Contenido | Columna 2 — El equipo |
|---|---|
| Últimas noticias → `seccion.html` *(futuro: `/equipos/junior-fc?tipo=noticia`)* | Próximo partido → `en-vivo.html` *(reutiliza el banner de partido ya existente en home, id `#centro-partidos`; hoy es dato mock)* |
| Videos → `video.html` | Tabla de posiciones — **no incluir todavía**: no existe página ni dato real; agregar cuando haya widget de tabla (ver nota abajo) |
| Clips → `seccion.html` *(futuro: filtro por tipo "clip")* | Plantel — **no incluir todavía**, mismo motivo |
| Fichajes de Junior → `seccion.html`, ancla equivalente a `#humo` de home *(Temporada de humo, filtrado a Junior)* | — |

**Destacado (columna derecha, opcional):** card grande con la nota o video más reciente de Junior (imagen + título + "Nota" o "Video"), reutilizando el patrón `.feature` de `#mundo-junior` en home → enlaza a `articulo.html` o `video.html` según el caso. Da vida al menú y evita que se sienta un listado seco.

> Nota sobre "El equipo": **Próximo partido** sí se puede enlazar hoy porque `en-vivo.html` y el banner de partido ya existen en el prototipo (aunque con datos mock). **Plantel** y **Tabla de posiciones** requieren una fuente de datos (API de la liga o carga manual) que hoy no existe — se recomienda dejarlas fuera del megamenú en esta fase y añadirlas cuando haya una plantilla/página real que las sirva, para no prometer un destino que da 404 o página vacía.

---

## 3. Fútbol ▾ — Megamenú

**Objetivo de negocio:** navegación por equipo/torneo (SEO por entidad) + acceso rápido a formatos (video/noticias) sin pasar por Junior.

| Columna 1 — Equipos | Columna 2 — Torneos | Columna 3 — Secciones |
|---|---|---|
| Junior FC → `seccion.html` | Liga BetPlay → `seccion.html` | Noticias → `seccion.html` *(índice de notas)* |
| Selección Colombia → `seccion.html` | Copa Colombia → `seccion.html` | Videos → `video.html` |
| Atlético Nacional → `seccion.html` | Copa América → `seccion.html` | Fichajes (Temporada de humo) → `seccion.html` |
| Millonarios → `seccion.html` | Eliminatorias → `seccion.html` | En Vivo → `en-vivo.html` |
| América de Cali → `seccion.html` | Copa Libertadores → `seccion.html` | |
| Deportes Tolima / Independiente Medellín → `seccion.html` | | |

*(Todos los enlaces de Equipos/Torneos apuntan hoy a `seccion.html` como comodín; en WordPress cada uno será el archivo de su término de taxonomía `equipo` o `torneo` — ver §5 de `03-arquitectura-contenido.md`.)*

**Destacado (opcional):** mini banner de "Próximo partido destacado" (el mismo componente `.match-banner` del centro de partidos de home) o un bloque "Tendencia" con el video/nota más visto del día, transversal a equipos. Refuerza que el megamenú no es solo un mapa de sitio sino una vitrina viva.

---

## 4. Programas ▾ — Megamenú (2 columnas)

**Objetivo de negocio:** dar casa al segundo pilar (radio) y empujar la escucha en vivo.

| Columna 1 — El show | Columna 2 — Contenido del show |
|---|---|
| El show de la mañana (ficha del programa) → `programas.html` | Últimos episodios / clips → `video.html` |
| Escuchar en vivo / Radio → `en-vivo.html` | Parrilla de la semana → `en-vivo.html` (ancla a la sección de programación) |
| Horario: L–V 10:00 a.m.–12:00 m. *(texto informativo, no enlace)* | Presentadores → `programas.html` (ancla a la sección de presentadores) |

**Destacado:** card de estado en vivo — badge "AL AIRE AHORA" (si es horario de emisión) o "Próxima emisión: mañana 10:00 a.m." con botón "Escuchar" → `en-vivo.html`. Es el elemento con mayor valor de conversión de todo el megamenú: aparece siempre, cambia según el reloj, y es el único que puede convertir una visita casual en un oyente.

---

## 5. Más ▾ — Dropdown simple (1 columna, sin megamenú)

**Objetivo de negocio:** credibilidad institucional + acceso a pauta publicitaria, sin competir visualmente con el contenido.

| Enlace | Destino | Estado |
|---|---|---|
| Nosotros | `nosotros.html` *(pendiente de construir — ver plantilla 3.7 de `03-arquitectura-contenido.md`)* | **P1**, a construir |
| Publicidad / Pauta con nosotros | `contacto.html#publicidad` *(pendiente — ver plantilla 3.8)* | **P1**, a construir |
| Contacto | `contacto.html` o, mientras no exista, `mailto:info@habladeportes.co` | **P1**, a construir |
| Política de privacidad | página legal *(pendiente)* | **P2** |
| Términos y condiciones | página legal *(pendiente)* | **P2** |
| — separador — | | |
| YouTube / Instagram / X | enlaces externos (ya usados en footer) | Ya disponibles |

No se propone destacado aquí: es intencionalmente el menú "de servicio", y meter una card visual reforzaría justo el desorden que el cliente pidió evitar.

---

## 6. Resumen de prioridad (para desarrollador-frontend)

- **P0 — construir ya con las páginas existentes:** columnas de Fútbol (Equipos/Torneos/Secciones), columnas de Junior "Contenido", columnas de Programas, y el dropdown simple de Más (aunque algunos enlaces terminen en páginas por construir, se pueden dejar como placeholders `href="#"` con comentario, igual que el resto del prototipo).
- **P1 — construir cuando existan las páginas de Nosotros y Contacto** (ya priorizadas en `03-arquitectura-contenido.md`): activar esos enlaces en el megamenú "Más".
- **P2 / diferido — no incluir todavía:** "Plantel" y "Tabla de posiciones" dentro de Junior ▾, hasta tener una fuente de datos o página real; evita destinos rotos o vacíos en el prototipo.
- El **destacado** de cada megamenú (Junior, Fútbol, Programas) es recomendado pero opcional: si el tiempo de desarrollo aprieta, los tres megamenús funcionan igual de bien solo con columnas de enlaces.

---

*Este documento define contenido y estructura del megamenú. El comportamiento de hover, la maquetación de columnas y el estilo visual los define `director-arte`/`desarrollador-frontend` a partir de esta especificación.*
