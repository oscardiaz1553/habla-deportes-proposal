# Diseño de las 6 secciones nuevas de la Home

**Proyecto:** Rediseño Habla Deportes — Ampliación de Home, Etapa 2 (diseño/layout)
**Agente:** `director-arte`
**Fecha:** 2026-07-16
**Insumos:** `propuesta/html/index.html` + `assets/css/main.css` + `assets/tokens.css` (sistema vigente), `propuesta/secciones-home-propuesta.md`, `propuesta/secciones-benchmark.md`, memoria de decisiones (light-first, navy `#294D6E`, rojo `#D20000` solo live/CTA, diseño AIREADO ≤ recargado).

---

## 0. Reglas que respeta este documento (no re-tematizar)

Esto **no inventa un lenguaje nuevo**: encaja 6 módulos dentro del sistema ya aprobado. Reglas heredadas que **no se tocan**:

1. **Navy `#294D6E` = identidad + acción.** Titulares (`--color-accent-text`), barras, play, CTAs primarios.
2. **Rojo `#D20000` = SOLO "en vivo" + CTA Suscribirse + kicker de categoría** (`.jitem__cat`, `.ticker__cat`). No se expande a decoración.
3. **Fondo blanco/gris muy claro, mucho aire.** ~85% de la superficie es neutra. Cada módulo respira: `padding-block` de sección estándar (`.section`), nunca cajas apretadas.
4. **Encabezado de sección estándar** en las 6: `.section-head` = ícono + `.section-head__title` (navy) + a la derecha `.section-head__link` "Ver todo" (o flechas `.round-arrow` si es carrusel). Sin excepciones.
5. **Mobile-first.** Todo colapsa a 1 columna; los carruseles usan scroll-snap horizontal con "peek" del siguiente ítem.
6. **Componentes existentes primero.** Solo se crean componentes nuevos cuando la estructura lo exige (tabla, tarjeta de partido, tarjeta vertical 9:16, fila de episodio, franja WhatsApp). Todos reusan tokens de color/espaciado/radio/sombra ya definidos.

> **Principio rector de densidad:** el cliente odia lo recargado. Por eso cada módulo nuevo entrega **un solo trabajo visual** y se apoya en el aire y en las líneas divisorias sutiles (`--color-border`, `--shadow-sm`) en vez de cajas pesadas o colores de relleno. Cuando dudé entre "más dato" y "más aire", gané el aire.

---

## 1. Centro de Partidos  *(NUEVO — tras el Ticker)*

**Propósito:** responder en 3 segundos la pregunta #1 del hincha —¿cuándo juega Junior/Colombia, contra quién, dónde verlo y cómo va la tabla— sin salir del sitio.

### Wireframe

```
┌─ section-head: [🗓 ícono] Centro de Partidos ················· Ver calendario → ┐
│                                                                                │
│  DESKTOP (≥ 900px):  grid 2 columnas  →  [ 1.5fr  columna partidos ] [ 1fr tabla ]
│                                                                                │
│  ┌──────────────────────────────┐   ┌──────────────────────────────┐          │
│  │ PRÓXIMO · LIGA BETPLAY        │   │  TABLA · LIGA BETPLAY         │          │
│  │ ┌────┐        ┌────┐          │   │  Pos  Equipo        PJ   Pts  │          │
│  │ │crest│  VS   │crest│         │   │  1   ● Bucaramanga  15    32  │          │
│  │ │Junior│      │Tolima│        │   │  2   ● Nacional     15    30  │          │
│  │ └────┘        └────┘          │   │  3 ▎● Junior        15    28  │◀ resaltado│
│  │ Sáb 20 jul · 6:10 pm          │   │  4   ● Medellín     15    27  │          │
│  │ 📍 Metropolitano              │   │  5   ● Millonarios  15    25  │          │
│  │ [ Win+ ] [ RCN ]  ← dónde ver │   │  ─────────────────────────    │          │
│  └──────────────────────────────┘   │       Ver tabla completa →    │          │
│                                      └──────────────────────────────┘          │
│  ┌───────────────┐ ┌───────────────┐                                           │
│  │ SELECCIÓN     │ │ ÚLTIMO RESULT.│   ← fila de 2 tarjetas más chicas         │
│  │ COL vs SUI    │ │ Junior 2–1 SF │                                           │
│  │ Mié · octavos │ │ ✓ Victoria    │                                           │
│  └───────────────┘ └───────────────┘                                           │
└────────────────────────────────────────────────────────────────────────────────┘
```

**Layout:** contenedor `.section` estándar. Grid principal de 2 columnas en desktop (`grid-template-columns: 1.5fr 1fr; gap: var(--space-8)`): izquierda = tarjetas de partido, derecha = mini-tabla. La columna izquierda tiene su propio grid interno: la tarjeta "Próximo Junior" ocupa el ancho completo arriba, y debajo una fila de 2 (Próximo Selección + Último resultado).

### Componentes nuevos (mínimos)

- **`.match-card`** — tarjeta blanca (`--color-surface`), `border-radius: var(--radius-md)`, `border: 1px solid var(--color-border)`, `box-shadow: var(--shadow-sm)`, `padding: var(--space-5)`.
  Estructura: `kicker` (PRÓXIMO · torneo, en `--fs-xs`, mayúsculas, `--ls-kicker`, color `--color-text-muted`) → **fila de enfrentamiento** (dos bloques `escudo + abreviatura` a los lados, "VS" navy centrado en `--font-display`) → **meta** (fecha · hora · estadio, `--fs-sm`, `--color-text-secondary`) → **"dónde ver"**: chips-pill de canal (`.match-card__channel`, borde navy suave, texto navy `--fs-xs`).
  El escudo de Junior/Selección se toma de los assets existentes (`Escudo_Junior_...png`); el rival es un placeholder circular gris.
- **Variante `.match-card--result`** (Último resultado): en lugar de "VS" muestra el **marcador** (`2–1`, `--font-display`, `--fw-extrabold`, navy, tamaño `--fs-2xl`) y un **chip de resultado** semántico:
  - Victoria → texto `--color-success` (#1F9254) con `✓`.
  - Empate → texto `--color-text-muted`.
  - Derrota → texto `--color-danger` (#D42A2C).
  El rojo de derrota es `--red-600` (familia distinta al live `#D20000`), evitando confundir "perdió" con "en vivo".
- **`.standings`** — mini-tabla compacta. Solo **4 columnas** (Pos · Equipo · PJ · Pts) para que quepa airada en móvil; nada de tabla ancha de escritorio.
  - Header: fila de kickers (`--fs-xs`, mayúsculas, `--color-text-muted`).
  - Filas: `--fs-sm`, separadas por `border-bottom: 1px solid var(--color-border-subtle)`, alto ≥ 40px (táctil).
  - **Fila de Junior resaltada**: fondo `--color-accent-tint` + borde izquierdo de 3px navy (`box-shadow: inset 3px 0 var(--color-accent)`) + nombre en `--fw-bold` navy. Si Junior cae fuera del top 5, se muestra top 4 + separador `···` + fila Junior (patrón estándar de tablas).
  - Cierre: `.section-head__link` reutilizado "Ver tabla completa →".

### Responsive (móvil)
Todo apila en 1 columna en este orden: Próximo Junior → Próximo Selección → Último resultado → Mini-tabla. Las tarjetas van a ancho completo; la fila de enfrentamiento mantiene los dos escudos con "VS" centrado (funciona igual en 320px). La tabla conserva sus 4 columnas (por eso se limitó a 4).

### Color / acento
100% dentro del sistema navy/neutro. Únicos toques de color: los **chips de resultado** (verde/rojo-derrota semánticos, ya en tokens) y el **resaltado navy** de la fila de Junior. Sin rojo de marca aquí (no hay "en vivo"). Premium por **jerarquía y aire**, no por color.

> **Nota de datos (para frontend):** en el prototipo se puebla con MOCK realista (fechas/marcadores/tabla escritos a mano). El markup debe quedar limpio y semántico (`<table>` real para `.standings`) para que en WordPress se reemplace por un widget SofaScore/API-Football sin rehacer el diseño.

---

## 2. Clips — formato corto vertical  *(NUEVO — tras "El show de la mañana")*

**Propósito:** lucir el consumo nativo móvil (reacciones/momentos < 90s, estilo Shorts/Reels), en contraste explícito con la grilla 16:9 de "El show".

### Wireframe

```
┌─ section-head: [▶ ícono] Clips ······················ ◀ ▶ (round-arrow) / Ver todo ┐
│                                                                                    │
│  carrusel horizontal, tarjetas VERTICALES 9:16, scroll-snap + peek                 │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌───                                 │
│  │ 0:45 │ │      │ │      │ │      │ │      │ │      ← chip duración arriba-izq      │
│  │      │ │      │ │      │ │      │ │      │ │                                     │
│  │  ▶   │ │  ▶   │ │  ▶   │ │  ▶   │ │  ▶   │ │  ▶   ← play centrado, chico         │
│  │      │ │      │ │      │ │      │ │      │ │                                     │
│  │ título│ │título│ │título│ │título│ │título│ │      ← título 2 líneas SOBRE media  │
│  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └───   (blanco, sobre scrim)          │
└────────────────────────────────────────────────────────────────────────────────────┘
```

**Diferencia clave con "El show":** ahí el título va **debajo** de un thumb 16:9; aquí el título va **dentro** de la tarjeta 9:16, sobre la imagen con scrim (patrón nativo de Shorts). Esto hace evidente a simple vista que es "otro formato", sin cambiar la paleta.

### Componentes

- Reutiliza **el mecanismo de carrusel** de `.vcards` (grid `grid-auto-flow: column`, `scroll-snap-type: x mandatory`, flechas `.round-arrow` en `.section-head__actions`, scrollbar oculto).
- Nuevo **`.clip`** (tarjeta):
  - `aspect-ratio: 9 / 16`, `border-radius: var(--radius-md)`, `overflow: hidden`, fondo `--navy-900`.
  - Imagen `object-fit: cover` + `.vcard__scrim` reutilizado (o `--scrim-thumb`).
  - **Play**: reusar `.play-btn` en tamaño reducido (44–48px), centrado. Hover: `scale(1.06)` (idéntico patrón).
  - **Chip de duración** arriba-izquierda: reutiliza el patrón `--scrim-chip` (píldora translúcida `rgba(14,27,41,.62)`, texto blanco `--fs-2xs`, `--radius-pill`).
  - **Título** abajo, `--font-display`, `--fw-bold`, color `--color-on-media` (blanco), `-webkit-line-clamp: 2`, `text-shadow` suave. Padding interno `--space-3`.
- Nuevo contenedor **`.clips`** análogo a `.vcards` pero con `grid-auto-columns` más angosto (ver responsive).

### Proporción y tamaño

| Breakpoint | Ancho de tarjeta (grid-auto-columns) | Visibles | Alto aprox (9:16) |
|---|---|---|---|
| Móvil < 560px | `44%` (peek del siguiente) | ~2 + peek | ~270–290px |
| Tablet 560–900 | `28%` | ~3 + peek | — |
| Desktop 900–1100 | `20%` | ~5 | — |
| ≥ 1100px | `160px` fijo, `grid-auto-flow: column` (mantiene carrusel, NO grilla completa) | 6–7 | ~284px |

> A diferencia de `.vcards`, en desktop **Clips NO se convierte en grilla estática de 4**: sigue siendo carrusel (los shorts se consumen deslizando). Es el único módulo de video que conserva el scroll horizontal en desktop, y eso refuerza su identidad de "formato corto".

### Responsive (móvil)
Carrusel deslizable con el pulgar; se ve ~2 tarjetas y el borde de la 3ª (peek) para invitar al swipe. Las flechas `.round-arrow` se pueden ocultar en móvil (`display:none` < 900px) porque el gesto nativo basta.

### Color / acento
Sin color de marca nuevo: navy de fondo, scrim navy, play/blancos y chips translúcidos ya existentes. La energía viene de la **forma vertical y el ritmo del carrusel**, no del color.

---

## 3. Selección Colombia — espejo de "Mundo Junior"  *(NUEVO — tras "Mundo Junior")*

**Propósito:** dar a la Selección el mismo peso editorial que Junior (los dos pilares del medio), manteniendo la simetría Junior ↔ Selección.

### Wireframe
Idéntico a "Mundo Junior": `.junior-grid` (feature grande a la izquierda + lista `.junior-list` de 4 a la derecha).

```
┌─ section-head: [escudo FCF] Selección Colombia ················· Ver todo → ┐
│  ┌───────────────────────────┐   ┌─ jitem ─────────────────────┐            │
│  │       FEATURE (16:11)      │   │ [thumb] KICKER · título     │            │
│  │          ▶                 │   │ [thumb] KICKER · título     │            │
│  │  Título grande (navy)      │   │ [thumb] KICKER · título     │            │
│  │  byline · meta             │   │ [thumb] KICKER · título     │            │
│  └───────────────────────────┘   └─────────────────────────────┘            │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Componentes
Reutiliza **tal cual** `.feature`, `.byline`, `.junior-list`, `.jitem` (renombrables a `.feature-grid` genérico en refactor, pero pueden compartir las mismas clases). Solo cambian contenido (imágenes/textos de Selección) y el **escudo** del `.section-head__icon--crest` (usar el de la FCF/Selección; si no hay asset, placeholder).

### Decisión de acento (amarillo Selección) — **medida y con criterio**

Evalué usar amarillo Colombia y la conclusión es: **el sistema se queda navy; el amarillo entra SOLO como un acento decorativo puntual, nunca como texto ni como relleno dominante.** Razones:

1. **Accesibilidad manda.** El amarillo Colombia (`#FCD116`) como **texto** sobre blanco da ~1.3:1 — inusable (falla AA por mucho). Por eso el kicker de categoría NO puede ser amarillo.
2. **Coherencia > novedad.** Los titulares siguen navy `--color-accent-text` como en todo el sitio; así el módulo se lee "de la misma familia" que Mundo Junior.

**Cómo se usa el amarillo (y dónde NO):**
- ✅ **Sí:** un **acento de 3px** (barra/subrayado) junto al escudo en el `.section-head`, o un pequeño **chip "★ Selección" con texto navy sobre relleno amarillo** (navy sobre `#FCD116` ≈ 9:1, AA holgado). Es el "guiño" caribeño/patrio, acotado a milímetros.
- ✅ **Diferenciador limpio:** el **kicker de categoría** de esta lista (`.jitem__cat`) pasa de rojo a **navy** (`--color-accent-text`), no a amarillo. Así el rojo se reserva a Junior/live y la Selección se distingue sin romper contraste.
- ❌ **No:** amarillo en titulares, en texto de meta, en fondos de tarjeta ni en el play. Nada de "bandera" a pantalla completa.

Resultado: la Selección se siente propia (un acento amarillo mínimo + kicker navy) pero el módulo es visualmente hermano de Mundo Junior. Elegante y accesible.

### Responsive (móvil)
Igual que Mundo Junior: feature arriba a ancho completo, lista debajo en columna. Cero trabajo nuevo de layout.

---

## 4. Fichajes / Mercado  *(NUEVO — tras "Selección Colombia")*

**Propósito:** consulta rápida de rumores y movimientos con estado claro; respiro de "lista/texto" entre bloques de video.

### Wireframe

```
┌─ section-head: [⇄ ícono] Fichajes ······························· Ver todo → ┐
│                                                                              │
│  DESKTOP: 2 columnas de filas · MÓVIL: 1 columna                             │
│  ┌────────────────────────────────────────────────────────────┐             │
│  │ [CONFIRMADO]  Juanfer Quintero regresa a Junior      · hoy  │             │
│  ├────────────────────────────────────────────────────────────┤             │
│  │ [RUMOR]       Cuadrado, sondeado por el cuadro rojiblanco   │             │
│  ├────────────────────────────────────────────────────────────┤             │
│  │ [CERRADO]     Jhomier Guerrero firma con Millonarios  · 2d  │             │
│  ├────────────────────────────────────────────────────────────┤             │
│  │ [DESCARTADO]  No prospera la llegada del delantero X        │             │
│  └────────────────────────────────────────────────────────────┘             │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Layout:** lista compacta de filas (`.transfer-list`), sin thumbnails (es consulta de texto, mantiene el aire). Cada fila: **etiqueta de estado** (izquierda, ancho fijo) + **titular** (`--fw-semibold`, navy, 1 línea con `line-clamp`) + **timestamp** opcional a la derecha (`--fs-2xs`, muted). Filas divididas por `--color-border-subtle`, alto ≥ 44px. En desktop, 2 columnas para no dejar una tira larga y vacía.

### Componente nuevo: etiqueta de estado `.tag--status`

Píldoras (`--radius-pill`) pequeñas (`--fs-2xs`, mayúsculas, `--ls-kicker`, `padding: 2px 8px`). Se apoyan en tokens semánticos ya existentes — **no requieren color nuevo salvo el neutro "cerrado"**:

| Estado | Estilo | Tokens |
|---|---|---|
| **Confirmado** | texto verde sobre tint verde suave | texto `--color-success` #1F9254 / fondo `rgba(31,146,84,.10)` |
| **Rumor** | texto ámbar sobre tint ámbar | texto `--color-warning` #B7791F / fondo `rgba(183,121,31,.12)` |
| **Cerrado** | neutro navy sobre tint navy (trato "archivado/hecho") | texto `--color-accent-text` / fondo `--color-accent-tint` |
| **Descartado** | texto gris muted, sin relleno (des-enfatizado) | `--color-text-muted` / fondo `--color-bg-subtle` |

> Nota de criterio: uso **tint + texto de color** (no relleno saturado con texto blanco). Es más sobrio, más "aireado" y pasa AA con holgura. El "Descartado" se apaga a propósito (menos peso visual). Nada compite con el rojo de marca.

### Responsive (móvil)
1 columna, filas a ancho completo. La etiqueta de estado va arriba/inline a la izquierda del titular; el timestamp puede ocultarse < 400px para no apretar.

### Color / acento
Verde/ámbar/navy semánticos (ya en tokens) + un neutro. Ningún token de color nuevo real (solo tints derivados de tokens existentes).

---

## 5. Radio a la carta  *(NUEVO — cerca del cierre, antes de la franja WhatsApp)*

**Propósito:** capturar al oyente que llega fuera del horario en vivo (10am–12m), ofreciendo los episodios anteriores on-demand.

### Wireframe

```
┌─ section-head: [〰 ícono ondas] Radio a la carta ·············· Ver episodios → ┐
│  (opcional) tira "● EN VIVO ahora · El show de la mañana" cuando aplica         │
│                                                                                 │
│  DESKTOP: 2 columnas de episodios · MÓVIL: 1 columna                            │
│  ┌───────────────────────────────────────────────┐                             │
│  │ (▶)  El show — Cuadrado a Junior     14 jul·1:52:0│  ← play navy + título + meta│
│  ├───────────────────────────────────────────────┤                             │
│  │ (▶)  El show — Previa Colombia vs.   13 jul·1:47 │                             │
│  ├───────────────────────────────────────────────┤                             │
│  │ (▶)  El show — Mercado de pases      12 jul·2:03 │                             │
│  └───────────────────────────────────────────────┘                             │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### Componente nuevo: `.episode` (fila de episodio)

Fila horizontal: **botón play circular navy** (izquierda) + **cuerpo** (título `--fw-semibold` navy 1 línea + meta `fecha · duración` en `--fs-sm` muted) + (opcional) mini-onda decorativa a la derecha.

- **Play "de audio":** círculo relleno **navy** (`background: var(--color-accent)`, ícono blanco), 40–44px. A diferencia del play de video (translúcido sobre imagen), aquí es **sólido navy sobre superficie clara** → el color navy sólido comunica "audio/reproducir" y distingue el módulo de los de video sin salir de la paleta.
- Fondo de fila: `--color-surface`; hover: `--color-surface-2`. Separadores `--color-border-subtle`. Alto ≥ 56px.
- **Acento "onda de audio":** decorativo, un pequeño SVG de barras/waveform en `--navy-300` (tenue) a la derecha de la fila en desktop. Es el único guiño "sonoro"; en móvil se oculta para ganar aire.
- **Estado "AL AIRE" (reusa live):** cuando el programa está en vivo, la tira superior usa `.live-dot` (rojo `#D20000`, ya existe) + texto "EN VIVO ahora". Es la ÚNICA aparición de rojo en este módulo y es semánticamente correcta (en vivo).

### Responsive (móvil)
1 columna, filas a ancho completo, sin waveform. El play navy queda a la izquierda como objetivo táctil grande (≥44px).

### Color / acento
Navy sólido (play) + neutros + waveform navy tenue. Rojo solo si hay emisión en vivo (badge live). Coherente y sobrio.

---

## 6. CTA Canal de WhatsApp  *(NUEVO — franja de conversión, antes del footer)*

**Propósito:** convertir a la audiencia al canal de mayor consumo de noticias en Colombia (WhatsApp), con alertas de último minuto.

### Wireframe

```
┌────────────────────────────────────────────────────────────────────────────┐
│  ░░ franja tint verde muy suave, dentro de .container, radius-lg ░░          │
│                                                                              │
│   (◉ícono WA verde)   Únete a nuestro Canal de WhatsApp                       │
│                       Goles, fichajes y el arranque del show, al instante.   │
│                                                     [  Unirme al canal  ] →   │
└────────────────────────────────────────────────────────────────────────────┘
```

**Layout:** una **franja horizontal** (`.wa-cta`) dentro de `.container`, `border-radius: var(--radius-lg)`, `padding: var(--space-6) var(--space-7)`. Estructura: ícono WhatsApp (círculo verde) + bloque de texto (título `--font-display` navy + subtítulo muted) + botón verde a la derecha. En desktop, fila (`display:flex; justify-content: space-between`); en móvil, apilado y centrado.

### Componente nuevo: `.wa-cta`

- **Fondo:** tint verde MUY suave (`--wa-tint`, ~`rgba(37,211,102,.08)`) con `border: 1px solid rgba(37,211,102,.24)`. El verde se **encapsula** exactamente como el módulo de sponsor Transelca: vive solo dentro de esta franja, no se filtra al resto de la Home.
- **Ícono:** logo WhatsApp en círculo relleno verde de marca `--wa-green` (#25D366) — es el elemento reconocible; el verde vivo se usa aquí porque el ícono es icónico y pequeño.
- **Título:** navy (`--color-accent-text`), NO verde → mantiene la voz de marca. El verde no invade la tipografía.
- **Botón `.btn--whatsapp`:** reutiliza la base `.btn` (pill, misma altura/tipografía que `.btn--subscribe`). Relleno **verde profundo** `--wa-green-deep` (#128C7E o más oscuro para AA con texto blanco), texto blanco, ícono WA. Hover más oscuro. Así el botón es reconociblemente "WhatsApp" pero con contraste AA real (el `#25D366` puro con texto blanco NO pasa; por eso el botón va en verde profundo y solo el ícono/decoración usan el verde vivo).

### Responsive (móvil)
Apilado y centrado: ícono → título → subtítulo → botón a ancho completo (`width:100%`, ≥44px). Franja con menos padding lateral.

### Color / acento
Verde WhatsApp **acotado al módulo** (tint de fondo, ícono vivo, botón profundo). Convive sin chocar con navy/rojo porque: (a) está encapsulado, (b) el título sigue navy, (c) aparece una sola vez y cerca del footer. Es el mismo criterio que ya se aplicó al azul de Transelca.

---

## 7. Orden final de la Home (con NUEVAS marcadas)

```
1.  Topbar — "El show de la mañana, L–V 10:00 AM"                 (existente)
2.  Header / Nav                                                  (existente)
3.  Hero de video                                                 (existente)
4.  Ticker "● Noticias"                                           (existente)
5.  ▸ Centro de Partidos                                          【NUEVO · §1】
6.  El show de la mañana (grilla 16:9)                            (existente)
7.  ▸ Clips (carrusel vertical 9:16)                              【NUEVO · §2】
8.  Mundo Junior (feature + lista)                               (existente)
9.  ▸ Selección Colombia (espejo de Mundo Junior)                【NUEVO · §3】
10. ▸ Fichajes / Mercado (lista con estados)                     【NUEVO · §4】
11. ▸ Radio a la carta (episodios on-demand)                     【NUEVO · §5】
12. ▸ CTA Canal de WhatsApp (franja de conversión)               【NUEVO · §6】
13. Módulo de sponsor / franja institucional                     (existente/pendiente)
14. Footer                                                        (existente)
```

**Ritmo del scroll (por qué este orden respira):** el orden alterna deliberadamente **dato ↔ video ↔ dato** para que nada canse:
`Centro de Partidos (dato)` → `El show (video 16:9)` → `Clips (video 9:16)` → `Mundo Junior (video+lista)` → `Selección (video+lista)` → `Fichajes (texto/lista)` → `Radio (audio/lista)` → `WhatsApp (CTA)`.
Los dos bloques de "lista/texto" (Fichajes, Radio) funcionan como **respiros** entre tanto video, y la franja WhatsApp cierra en conversión. Coherente con "aireado, no recargado".

> **Nota de alcance:** este set corresponde a los módulos P0/P1 del `arquitecto-contenido`. "La Voz del Hincha" (encuesta) y "Un día como hoy" (efemérides) quedan **fuera de esta ronda** a propósito, para no recargar la Home (decisión alineada con el feedback del cliente).

---

## 8. Tokens / acentos nuevos a agregar

Todo lo demás reusa tokens existentes. Estos son los **únicos** valores nuevos que el frontend debe añadir a `tokens.css` (encapsulados, igual que el sponsor):

```css
:root {
  /* --- WhatsApp (ENCAPSULADO en el módulo .wa-cta; nunca fuera) --------- */
  --wa-green:       #25D366;                  /* ícono/acento reconocible */
  --wa-green-deep:  #128C7E;                  /* relleno de botón (texto blanco, AA) */
  --wa-green-press: #0E6F63;                  /* pressed / hover del botón */
  --wa-tint:        rgba(37, 211, 102, 0.08); /* fondo suave de la franja */
  --wa-border:      rgba(37, 211, 102, 0.24); /* borde de la franja */

  /* --- Acento Selección (ENCAPSULADO en §3; SOLO decorativo, nunca texto) */
  --sel-yellow:     #FCD116;                  /* barra/subrayado o chip con TEXTO NAVY encima */
  --sel-yellow-ink: var(--navy-800);          /* texto sobre el amarillo (≈9:1, AA holgado) */

  /* --- Tints de estado para etiquetas de Fichajes (derivados, no colores nuevos) */
  --tag-confirmado-bg: rgba(31, 146, 84, 0.10);   /* texto: --color-success */
  --tag-rumor-bg:      rgba(183, 121, 31, 0.12);  /* texto: --color-warning */
  --tag-cerrado-bg:    var(--color-accent-tint);  /* texto: --color-accent-text */
  --tag-descartado-bg: var(--color-bg-subtle);    /* texto: --color-text-muted */
}
```

**Reglas de uso de los tokens nuevos:**
- `--wa-*` → exclusivamente dentro de `.wa-cta` / `.btn--whatsapp`. Botón AA: usar `--wa-green-deep` (no `--wa-green`) cuando lleve texto blanco.
- `--sel-yellow` → solo como **relleno decorativo** (barra 3px, subrayado del section-head, o chip "★ Selección" con `--sel-yellow-ink` encima). **Prohibido** como color de texto sobre blanco o como fondo de tarjeta.
- Estados de Fichajes → **tint de fondo + texto de token semántico existente** (verde/ámbar/navy/muted). No introducen color saturado nuevo.
- **No** se agregan tokens para Centro de Partidos, Clips ni Radio: reutilizan navy, neutros, scrim, live y semánticos ya definidos.

### Componentes nuevos a construir (resumen para `desarrollador-frontend`)
| Sección | Clases nuevas | Reutiliza |
|---|---|---|
| Centro de Partidos | `.match-card`, `.match-card--result`, `.match-card__channel`, `.standings` | `.section`, `.section-head`, `.section-head__link`, tint/borde/sombra tokens |
| Clips | `.clips`, `.clip` (+ chip duración) | mecánica de `.vcards`, `.round-arrow`, `.play-btn`, `.vcard__scrim`, `--scrim-chip` |
| Selección Colombia | — (0 nuevos) | `.junior-grid`, `.feature`, `.byline`, `.junior-list`, `.jitem` |
| Fichajes | `.transfer-list`, `.tag--status` (4 variantes) | `.section-head`, tokens semánticos |
| Radio a la carta | `.episode`, `.radio-list` (+ waveform SVG opcional) | `.play-btn` (variante sólida navy), `.live-dot`, `.section-head` |
| CTA WhatsApp | `.wa-cta`, `.btn--whatsapp` | base `.btn`, patrón de encapsulado del sponsor |

---

*Este documento define layout y sistema visual; no incluye HTML/CSS de producción. La maquetación la ejecuta `desarrollador-frontend` reutilizando componentes y tokens existentes + los 3 componentes/tokens nuevos acotados aquí.*
