# Sistema de Diseño — Habla Deportes

**Proyecto:** Rediseño de habladeportes.co
**Fase:** 2 — Desarrollo de la propuesta
**Agente:** `director-arte`
**Fecha:** 2026-07-13 · **Revisión:** R2 (cambio de dirección del cliente)
**Consume esto:** `desarrollador-frontend` (prototipo HTML) · `estratega-wordpress` (implementación)
**Tokens:** [`propuesta/html/assets/tokens.css`](html/assets/tokens.css)

> **Cambio de dirección del cliente (mandato R2).** Tras ver la primera Home, el cliente **rechazó** la estética oscura/broadcast con acento *volt* (verde-lima). Pide un estilo **claro, limpio y aireado, más parecido al sitio actual** de Habla Deportes (fondo gris muy claro, mucho blanco, look profesional y sobrio, labels de sección en mayúsculas grises), pero **mejor jerarquizado**. El **color de marca y acción es el navy del logo real, `#294D6E`**; el *volt* se **elimina por completo**. El **rojo** sigue **reservado solo para "AL AIRE / EN VIVO"**. El sponsor (Transelca) mantiene su módulo digno con su azul **encapsulado**. Todo cumple contraste **AA** y la base de a11y del [informe técnico](../diagnostico/02-tecnico-seo.md).

---

## 1. Dirección de arte

### Concepto (una frase)

> **"Claro editorial": el aire y la limpieza de un medio profesional serio, con el azul de marca haciendo de único acento que ordena y da carácter.**

El sitio debe sentirse como un **medio deportivo moderno y confiable**, no como un canal de TV ni como un tema de WordPress plano. Es la evolución natural del sitio actual (claro, sobrio, con etiquetas grises en mayúsculas) pero con **jerarquía real, foco video-first y una firma cromática propia**: el **navy `#294D6E`**. La personalidad ya no vive en un color estridente, sino en **tres gestos**: el **navy de marca** usado con disciplina, la **jerarquía tipográfica editorial** (display con peso, cuerpo muy legible) y el **lenguaje "en directo"** (rojo aislado para el vivo).

### Atributos (5)

1. **Claro y aireado** — fondo gris muy claro / blanco, mucho espacio en blanco, respiración entre secciones.
2. **Editorial y legible** — jerarquía fuerte, foco en una historia héroe, cero ruido (la vara de The Athletic).
3. **Profesional / confiable** — sobriedad y navy de marca; vende calidad a la audiencia **y a los anunciantes**.
4. **Video-first** — thumbnails curados, hero de video y "en vivo" como componentes de primera clase.
5. **Con firma, neutro de equipo** — no depende de ningún club ni del sponsor; su identidad es el **sistema navy + neutros**, no un logo prestado.

### Cómo responde al diagnóstico

| Hallazgo del diagnóstico | Respuesta de dirección de arte |
|---|---|
| H3.1 identidad genérica, una sola fuente (Rubik) | Pareja tipográfica: **display** (Archivo) + cuerpo legible (Inter); jerarquía clara sin gritar. |
| H3.1 acento = rojo de YouTube; H6.1/2.6 identidad secuestrada por Transelca | Acento propio **navy `#294D6E`** (del logo real), ajeno a YouTube y a Transelca. El rojo se **reserva** solo para "AL AIRE". |
| H3.2 contraste insuficiente (gris claro sobre gris claro) | Todos los pares texto/fondo verificados AA; el navy da 8.8:1 sobre blanco. |
| H2.1/2.2 video-first y radio invisibles | Sistema con **thumbnail firmado**, **hero de video** y **reproductor sticky "AL AIRE"** como componentes de primera clase. |
| 04-competencia: ganar por foco, no por volumen | Minimalismo editorial claro: 1 héroe + grilla + feed, mucho aire, sin clutter. |

---

## 2. Paleta de color

**El sistema es LIGHT-FIRST.** El **tema claro** es el **default de todo el sitio** y es lo único que se muestra al cliente. Existe un **tema oscuro OPCIONAL** (superficies navy profundas) como override secundario `[data-theme="dark"]`, pero **no** es la experiencia por defecto.

### 2.1 Firma cromática — la regla de oro

- **NAVY `#294D6E`** = **color de marca y de acción**. Es la firma: CTAs, enlaces de énfasis, estados activos, barras/etiquetas de sección, botón "play", subrayados. Se usa **con disciplina** (~10–15% de la superficie) sobre un lienzo mayormente claro.
- **ROJO BROADCAST** = **reservado exclusivamente al estado "AL AIRE / EN VIVO"** (badge y punto pulsante). No es color de marca: es una **señal semántica** universal de directo. Aislarlo hace que "en vivo" siempre destaque y nunca compita con el navy.
- **NEUTROS CLAROS (Slate)** = todo lo demás. ~85% del sitio es blanco / gris muy claro (`#F4F6F8`); el navy hace el trabajo justo.

Esta separación (navy = marca, rojo = directo, slate = lienzo) hace el sistema **memorable, sobrio y neutro a la vez**, y resuelve de raíz la "identidad secuestrada por el sponsor".

### 2.2 Acento de marca — Navy (escala completa alrededor de #294D6E)

| Token | HEX | Uso |
|---|---|---|
| `--navy-700` (base) | `#294D6E` | **Color de marca.** Relleno de CTA primario, play, barras de sección, **enlace/énfasis de texto** sobre claro (AAA, 8.8:1). |
| `--navy-600` | `#335F86` | **Hover** del navy sobre claro. |
| `--navy-800` | `#1E3A55` | **Pressed** / titulares fuertes. |
| `--navy-500` | `#3E729F` | **Azul secundario** (más brillante), derivado del navy — interacción alterna / enlaces neutros / anillo de foco (AA, 5.1:1). |
| `--navy-50 / -100` | `#EEF3F8` / `#DCE7F1` | Fondos suaves de énfasis (fila destacada, chip activo, tint de superficie). |
| `--navy-900 / -950` | `#14263A` / `#0E1B29` | Superficies navy profundas (footer opcional, barras de marca, tema oscuro). |
| `--color-accent-tint` | `rgba(41,77,110,.08)` | Fondo suave de estado activo/énfasis sobre claro. |

> **Regla de contraste del navy:** el navy es **oscuro**, así que funciona como **texto y como relleno**. Sobre relleno navy el texto es **blanco** (`--color-text-on-accent`, 8.8:1). Como **texto/enlace** sobre claro va `--navy-700` (AAA). Nunca texto navy oscuro sobre fondo navy: en el tema oscuro el acento se **aclara** a `--navy-300/400`.

### 2.3 Estado en vivo — Broadcast Red

| Token | HEX | Uso |
|---|---|---|
| `--live-500` | `#FF3B30` | Relleno del badge "AL AIRE", punto pulsante. **Solo** esto. |
| `--live-600` | `#E11D12` | **Texto/ícono "en vivo" sobre claro** (AA, 4.8:1 en blanco) + hover/pressed del badge. |
| `--live-glow` | `rgba(255,59,48,.40)` | Halo del punto pulsante. |

Texto sobre el badge rojo: **blanco en mayúsculas bold** (tratado como texto grande, AA).

### 2.4 Neutros claros (Slate) — base del tema

| Token | HEX | Rol |
|---|---|---|
| `--slate-50` | `#F4F6F8` | **Fondo de página** (gris muy claro, aireado — como el sitio actual). |
| `--white` | `#FFFFFF` | Superficie de **cards**, header, paneles. |
| `--slate-100` | `#EDF1F5` | Franja alterna de sección / hover sutil. |
| `--slate-200` | `#E1E7ED` | Pressed / seleccionado. |
| `--slate-300` | `#DCE3EA` | Borde estándar / hairline. |
| `--slate-400` | `#C4CED8` | Borde fuerte. |
| `--slate-600` | `#5E6B77` | Texto atenuado (meta, timestamps) + **kickers gris mayúsculas** — AA. |
| `--slate-700` | `#4A5866` | Texto secundario — AAA en blanco. |
| `--slate-900` | `#17222E` | **Texto primario** (tinta con matiz navy, editorial). |

### 2.5 Semánticos / estado

| Token | HEX | Uso |
|---|---|---|
| `--green-600` | `#1F9254` | Victoria / positivo / "disponible" (AA en blanco). |
| `--amber-500` | `#B7791F` | Texto de aviso / "próximo programa" (AA en blanco). `--amber-bg` `#FFB020` para relleno de chip con texto tinta. |
| `--red-600` | `#D42A2C` | Error / derrota (familia distinta al live-red por contexto). |
| `--info-600` | `#2563A8` | Info / enlaces neutros. |

### 2.6 Sponsor — Transelca (encapsulado)

| Token | HEX | Uso |
|---|---|---|
| `--sponsor-blue` | `#0A85C9` | **Solo** dentro del módulo de sponsor (azul **brillante/turquesa**, deliberadamente distinto del navy de marca). |
| `--sponsor-blue-deep` | `#0568A3` | Detalle/hover del mismo módulo. |
| `--sponsor-tint` | `rgba(10,133,201,.08)` | Fondo suave del bloque "Presentado por". |

> El azul del sponsor es **más brillante/turquesa que el navy** a propósito, para que se distinga y **no compita** con la marca. **Nunca** sale de su módulo; el logo va sobre superficie neutra o su propio bloque, y la marca del medio (navy/slate) domina el resto de la página.

### 2.7 Verificación de contraste (WCAG 2.1 AA) — tema claro

| Par | Ratio aprox. | Resultado |
|---|---|---|
| Texto primario `#17222E` / blanco | ~15.4:1 | ✅ AAA |
| Texto primario `#17222E` / fondo `#F4F6F8` | ~14:1 | ✅ AAA |
| Texto secundario `#4A5866` / blanco | ~7.3:1 | ✅ AAA |
| Texto secundario `#4A5866` / fondo `#F4F6F8` | ~6.7:1 | ✅ AA |
| Texto atenuado / kicker `#5E6B77` / blanco | ~5.5:1 | ✅ AA |
| Texto atenuado `#5E6B77` / fondo `#F4F6F8` | ~5.0:1 | ✅ AA |
| **Navy enlace/énfasis `#294D6E` / blanco** | ~8.8:1 | ✅ AAA |
| Navy enlace `#294D6E` / fondo `#F4F6F8` | ~8.1:1 | ✅ AAA |
| Azul secundario `#3E729F` / blanco | ~5.1:1 | ✅ AA |
| **Blanco / botón navy `#294D6E`** | ~8.8:1 | ✅ AAA |
| Blanco / hover navy `#335F86` | ~6.7:1 | ✅ AA |
| En vivo texto `#E11D12` / blanco | ~4.8:1 | ✅ AA |
| Blanco / badge live `#FF3B30` | ~3.6:1 | ✅ AA (texto grande/bold) |

Objetivos táctiles mínimos: **44×44 px** (`--touch-min`), resolviendo H5.2.

---

## 3. Tipografía

Pareja **open source (Google Fonts)**, con un tratamiento **suavizado** respecto a R1: menos "rótulo de transmisión en mayúsculas", más **editorial/corporativo limpio**, acorde al navy.

### 3.1 Familias

| Rol | Fuente | Por qué |
|---|---|---|
| **Display / titulares** | **Archivo** (600–800) | Grotesca sólida y deportiva; ahora en **caja normal (sentence case)** y pesos 700–800 (no 900). Aporta jerarquía firme sin sensación de canal de TV. |
| **Cuerpo / UI** | **Inter** (400–700) | Máxima legibilidad en pantalla (audiencia móvil), neutra, excelente para artículos y componentes. |
| **Numérico / mono** (auxiliar) | Inter *tabular* / `--font-mono` | Marcadores, minutos, timestamps con cifras tabulares alineadas. |

> **Firma tipográfica suavizada:** los **kickers** (etiqueta superior de sección/categoría, p. ej. `JUNIOR FC` · `FICHAJES`) van en **Inter 600, mayúsculas, tracking `0.08em`** (antes 0.14em), en **gris `--color-text-muted`** por defecto — el look sobrio de "label de sección en mayúsculas grises" del footer actual — y en **navy `--color-accent-text`** cuando marcan sección activa o énfasis de marca. `EN VIVO` es el único kicker en rojo.

### 3.2 Escala tipográfica

| Token | Tamaño | Uso | Fuente / peso |
|---|---|---|---|
| `--fs-hero` | clamp 44→76px | Titular hero de portada | Archivo 800, `--lh-tight`, `--ls-tightest`, **sentence case** |
| `--fs-3xl` | clamp 36→52px | H1 de página | Archivo 800, `--lh-snug`, `--ls-tight` |
| `--fs-2xl` | 36px | H2 / título de bloque | Archivo 700–800 |
| `--fs-xl` | 28px | H3 / encabezado de sección | Archivo 700 |
| `--fs-lg` | 22px | Título de card grande | Archivo 700 |
| `--fs-md` | 18px | Bajada / lead / cuerpo destacado | Inter 400–500, `--lh-relaxed` |
| `--fs-base` | 16px | Cuerpo | Inter 400, `--lh-relaxed` |
| `--fs-sm` | 14px | UI, meta, títulos de card pequeña | Inter 500 |
| `--fs-xs` | 12px | **Kickers**, chips, captions | Inter 600 MAYÚS `--ls-kicker` |
| `--fs-2xs` | 11px | Timestamps, contadores | Inter 500 tabular |

### 3.3 Reglas de uso

- **Titulares de card y sección → Archivo (sentence case).** **Todo texto corrido → Inter.** No mezclar dentro de un párrafo.
- **Mayúsculas solo en piezas cortas** (kickers, botones opcionales, badges). **Nunca en titulares** (cambio vs R1: los titulares ya no van en mayúsculas).
- **Medida de lectura** en artículos: `--container-text` = 720px máx.
- Números de marcador/minuto: `font-variant-numeric: tabular-nums`.
- Enlaces en cuerpo: subrayado + color `--color-accent-text` (navy `#294D6E`, AAA sobre claro).

---

## 4. Retícula, breakpoints y espaciado *(conservado de R1)*

### 4.1 Contenedores

| Token | Valor | Uso |
|---|---|---|
| `--container-max` | 1280px | Ancho de contenido estándar. |
| `--container-wide` | 1440px | Héroes y franjas full-bleed. |
| `--container-text` | 720px | Medida de lectura de artículos. |
| `--page-margin` | clamp 16→48px | Margen lateral responsivo. |

### 4.2 Grid y breakpoints

- **Grid de 12 columnas**, gutter `--gutter` = 24px (desktop) / 16px (móvil).
- Patrones de grilla de cards: **4 col** (≥1280), **3 col** (≥1024), **2 col** (≥768), **1 col** (móvil).

| Breakpoint | Ancho | Nota |
|---|---|---|
| `xs` | 0–479 | Móvil (mobile-first; base de diseño). |
| `sm` | 480 | Móvil grande. |
| `md` | 768 | Tablet — aparece grilla 2 col. |
| `lg` | 1024 | Desktop — nav horizontal completa, 3 col. |
| `xl` | 1280 | Desktop amplio — 4 col, hero full. |
| `2xl` | 1440 | Contenedor wide. |

### 4.3 Escala de espaciado (base 4px)

`--space-1:4` · `2:8` · `3:12` · `4:16` · `5:20` · `6:24` · `7:32` · `8:40` · `9:48` · `10:64` · `11:80` · `12:96` · `13:128`.

- Ritmo vertical entre secciones de portada: `--space-11` (80px) desktop / `--space-8` (40px) móvil. En claro, el **aire generoso** entre secciones es parte del carácter.
- Padding interno de card: `--space-4`/`--space-5`.
- Gap de grilla: `--space-6` (24px).

### 4.4 Radios y elevación

- Radios: cards `--radius-md` (12px), botones/inputs `--radius-sm` (8px), tags `--radius-xs` (4px), **chips y badges `--radius-pill`**. Mezcla deliberada: bloques editoriales de esquina viva-suave + elementos "en vivo" en píldora.
- **Elevación en claro:** **sombras suaves y difusas** (`--shadow-sm/md/lg`), no glow. Las cards descansan sobre el gris `#F4F6F8` con borde `--color-border` de 1px + sombra sutil al hover (`--shadow-md`). El acento navy usa `--shadow-accent` solo en piezas puntuales (botón de foco, card destacada). El "en vivo" usa `--shadow-live`.

---

## 5. Componentes clave (look & feel)

Todos parten de los tokens; el HTML define la implementación. Aquí, el criterio visual. La **estructura de componentes se conserva** de R1; cambia el **acabado a claro/navy**.

### 5.1 Header / Nav — con indicador "AL AIRE"

- Barra sticky (`--header-height` 68px), fondo **`--color-surface` (blanco)** con `--color-border-subtle` inferior y sombra `--shadow-sm`; al hacer scroll gana un pelín más de sombra (no se oscurece).
- Izquierda: **logo real** (wordmark navy, ver §8). Centro/derecha: nav horizontal (`Inicio · Noticias · Videos · En Vivo · Equipos ▾ · Torneos ▾ · Fichajes`), Inter 500 en `--color-text`, hover con **subrayado navy de 2px** animado.
- **Indicador "AL AIRE"**: en horario (L–V 10–12) el ítem "En Vivo" muestra un **badge píldora rojo** con **punto pulsante** y texto `AL AIRE`. Fuera de horario: chip ámbar sutil `Próximo 10:00`.
- Derecha fija: **buscador** (ícono lupa con `<label>` accesible) + **CTA "Suscríbete"** (botón **navy** relleno, texto blanco, ícono YouTube).
- Móvil (`--header-height-mobile` 56px): logo + botón "En Vivo" (con su estado) + hamburguesa. Menú = overlay a pantalla completa **claro** (`--z-modal`).

### 5.2 Card de video (thumbnail firmado)

El componente **más repetido**; su consistencia es la que hace ver "medio", no "carrusel de YouTube". Funciona con **thumbnails placeholder y reales por igual**:

- Contenedor `--radius-md`, **`--color-surface` (blanco)**, borde `--color-border`, sombra `--shadow-sm`.
- **Thumbnail 16:9** con **scrim inferior obligatorio** (`--scrim-thumb`): degradado a tinta navy que garantiza legibilidad del texto blanco **sobre cualquier imagen**. Esta es la decisión que hace robusto el sistema con placeholders.
- Sobre el thumb: **kicker de categoría** arriba-izquierda (mayúsculas; blanco sobre el scrim, o navy sobre chip claro en variante), **duración** abajo-derecha (chip píldora oscuro translúcido), y **botón play navy circular** con triángulo blanco, centrado, que aparece/crece en hover.
- Bajo el thumb: título en **Archivo 700 sentence case** (`--color-text`), 2 líneas máx (line-clamp), + meta (fecha relativa + equipo) en Inter `--color-text-muted`.
- Hover: elevar con `--shadow-md`, borde navy sutil (`--navy-200`), play a escala. Transición `--dur-base`/`--ease-out`.

### 5.3 Card de nota

- Igual retícula que la de video pero **sin botón play** y con **etiqueta de tipo "NOTA"** en kicker.
- Variante "titular": imagen izquierda + texto derecha en desktop; apilada en móvil.
- Meta: autor · fecha · tiempo de lectura · chip de equipo/torneo (píldora enlazada al índice).

### 5.4 Hero / Destacado

- **Full-bleed** hasta `--container-wide`, alto ~60–70vh en desktop, sobre fondo claro.
- Imagen/thumb grande con `--scrim-hero`. Contenido alineado abajo-izquierda: **kicker** (navy o blanco según fondo) → **titular `--fs-hero` Archivo 800 sentence case** → bajada `--fs-md` (2 líneas) → fila de acciones (**play navy** + "Ver video" / "Leer nota") + meta.
- Variante alterna **editorial clara**: hero de dos columnas sobre blanco (imagen + bloque de texto con kicker gris, titular navy-tinta), útil para portada sobria.
- **Espacio de sponsor integrado**: microcopy `Presentado por` + logo Transelca en la esquina, sobre chip neutro (no invade el titular).
- Variante video: al pulsar play, embed de YouTube reemplaza la imagen in-situ.

### 5.5 Bloque en vivo / Radio — reproductor sticky "AL AIRE"

Elemento **firma** del medio (nadie local lo hace bien; ver 04-competencia §3).

- **Barra sticky inferior** (`--radio-bar-height` 72px, `--z-radio`), fondo **`--color-surface-brand` (navy `#294D6E`)** con texto blanco y hairline superior — el navy le da presencia y la ancla como pieza de marca sobre el lienzo claro. Persiste al navegar.
- Izquierda: **badge "AL AIRE"** (rojo + punto pulsante) o "FUERA DE AIRE / Próximo 10:00" (ámbar). Nombre del programa + horario, en blanco.
- Centro: **botón play/pause circular** (claro sobre navy, o navy-claro; grande, ≥44px), barra de progreso/onda sutil, control de volumen.
- Derecha: mini-logo del **sponsor del programa** (versión que contraste sobre navy) + botón cerrar/minimizar.
- En `/en-vivo`: versión expandida con parrilla de la semana y últimos episodios.
- Estado del "AL AIRE" gobernado por horario (L–V 10:00–12:00), sin API externa.

### 5.6 Bloque de sponsor

- Componente reutilizable con **inventario definido**:
  - **"Presentado por"** (hero/programa): microcopy + logo sobre chip neutro.
  - **Franja patrocinada**: banda `--color-bg-subtle` con "Contenido presentado por [logo]" + hairline sutil, separada del contenido editorial y del AdSense.
  - **Módulo lateral** en artículo, con fondo `--sponsor-tint` y hairline `--sponsor-blue` **solo dentro de la caja**.
- El módulo puede usar `--sponsor-blue` **solo dentro de su caja**; nunca fuera. Siempre etiquetado como patrocinio.

### 5.7 Footer

- Fondo **navy profundo** `--navy-900` (`#14263A`) con texto claro, o alternativamente `--color-bg-subtle` claro con etiquetas grises mayúsculas — se elige la versión navy para cerrar la página con presencia de marca (coherente con el footer actual pero jerarquizado). 4 columnas (Marca · Secciones · El programa · Institucional) según [03 §4.3](../diagnostico/03-arquitectura-contenido.md).
- Incluye: redes con íconos de línea, **CTA de escucha de radio real**, y **franja de sponsor fija y digna** abajo.
- **Etiquetas de columna en mayúsculas grises** (el gesto sobrio del footer actual que el cliente valora).
- Sin el crédito visible de la agencia (H7.1); datos reales o ausentes, no placeholders vacíos.

### 5.8 Botones y estados

| Variante | Reposo | Hover | Pressed | Focus | Disabled |
|---|---|---|---|---|---|
| **Primario (navy)** | relleno `--color-accent` (navy), **texto blanco**, `--radius-sm` | `--color-accent-hover` (`#335F86`) | `--color-accent-press` (`#1E3A55`) | `--focus-shadow` (anillo navy) | opacidad .45, sin sombra |
| **Secundario (ghost)** | transparente/blanco, borde `--color-border-strong`, **texto navy** | fondo `--color-surface-2` + borde navy | `--color-surface-3` | `--focus-shadow` | opacidad .45 |
| **Live (escuchar en vivo)** | ghost con acento contextual | — | — | anillo | — |
| **Texto/enlace** | `--color-accent-text` (navy) subrayado | subrayado más grueso / `--navy-600` | — | anillo | — |

- Altura mínima 44px, padding `--space-3`/`--space-5`, Inter 600. **Foco siempre visible** (`--color-focus-ring`, azul secundario) — requisito a11y del diagnóstico.

### 5.9 Chips de filtro

- Píldora (`--radius-pill`), Inter 600 `--fs-sm`, altura 36–44px.
- Reposo: `--color-surface` blanco, texto secundario, borde `--color-border`.
- **Activo:** fondo `--color-accent-tint` (navy suave) + borde navy + texto `--color-accent-text` (navy).
- Fila horizontal con scroll en móvil; usados en índices por tipo (Video/Nota), equipo y torneo.

---

## 6. Imagen, iconografía y thumbnails

### 6.1 Tratamiento de imagen (TEMA CLARO)

- **Fotografía deportiva de acción**, encuadres cerrados y de alto contraste, presentada sobre **cards claras** con borde sutil y sombra suave — la imagen aporta el color, el marco es neutro y aireado.
- Sobre las imágenes con texto encima, **scrim navy suave** (`--scrim-hero`/`--scrim-thumb`) solo en la zona inferior para legibilidad del texto blanco, más ligero que en R1 (la card ya no es oscura).
- Formato: **WebP/AVIF**, `width/height` explícitos y `loading="lazy"` fuera del viewport (resuelve CLS / P7).

### 6.2 Placeholders SVG para TEMA CLARO (a regenerar por frontend)

> **Los placeholders actuales son de fondo oscuro + volt y ya NO sirven.** El frontend debe regenerarlos con este tratamiento claro:

- **Fondo:** relleno claro con **degradado sutil `--white → --slate-100`** (o `--navy-50` para variantes de marca), **no** fondo oscuro.
- **Grafismo:** líneas de cancha / retícula geométrica muy tenue en **navy claro (`--navy-100/200`)**, con un bloque diagonal o arco en `--navy-50`; sensación limpia y editorial, sin "haces de luz de estudio".
- **Acento:** **kicker de categoría en navy `#294D6E`** mayúsculas (o gris `--slate-600`), y un **ícono play/nota en navy** (contorno o relleno navy con detalle blanco). **Cero volt.**
- **Monograma:** "HD" o wordmark en `--navy-200` a baja opacidad como marca de agua discreta.
- **Etiqueta fantasma:** texto grande (p. ej. `JUANFER`) en `--slate-200`/`--navy-100` muy tenue.
- Al llevar scrim + kicker, el placeholder se ve **intencional**, no roto, y encaja tanto con placeholders como con foto real (Junior FC, Selección, fichajes, Transelca) sin retoques.

### 6.3 Iconografía

- Set de **línea, open source (Lucide)**, stroke ~1.75px, uniones redondeadas, grid 24px.
- Color por defecto `--color-text-secondary`; íconos de acción en **navy** (`--color-accent`); íconos de estado en vivo (punto, ondas de radio) en `--color-live`.
- Íconos siempre con `aria-label`/texto asociado; nunca solo color para comunicar estado.

---

## 7. Movimiento y accesibilidad

- **Rápido y sobrio:** transiciones 120–180ms (`--dur-fast`/`--dur-base`) con `--ease-out`. Nada de animaciones largas ni page-loader (se elimina el WebGL/Three.js del sitio actual, P1 técnico).
- **Punto "AL AIRE"**: pulso suave de halo (1.2s loop).
- **`prefers-reduced-motion`:** el HTML desactiva pulsos/transiciones no esenciales.
- **A11y base:** `lang="es-CO"`, un `<h1>` por página, landmarks + skip-link, foco visible, `alt` descriptivos, `<label>` en buscador, objetivos ≥44px, contraste AA (verificado en §2.7).

---

## 8. Tokens CSS y nota de coordinación para `desarrollador-frontend`

Todas las decisiones están codificadas en **[`propuesta/html/assets/tokens.css`](html/assets/tokens.css)** como variables `:root` (**tema claro = default**) + override opcional `[data-theme="dark"]`. El `desarrollador-frontend` debe:

1. `<link rel="stylesheet" href="assets/tokens.css">` como **primera** hoja (antes del CSS de componentes).
2. Cargar fuentes en `<head>`: **`Archivo` (600–800) + `Inter` (400–700)** vía Google Fonts (`preconnect` + `display=swap`).
3. **`<html>` sin atributo (o `data-theme="light"`) como default.** El tema oscuro (`data-theme="dark"`) es **opcional** y **no** se muestra en la presentación al cliente. **Todo lo que ve el cliente es claro.**
4. Consumir **siempre** los tokens semánticos (`--color-*`, `--space-*`, `--fs-*`, `--radius-*`), no las primitivas ni valores crudos.

### 8.1 Acciones específicas de esta revisión (R2)

- **Logo real:** usar **`propuesta/html/assets/img/LogoHablaDeportes.webp`** (wordmark navy "Habla / DEPORTES") como logo del header y footer. Sobre fondo claro el navy funciona directo. **Prever una versión en blanco** del logo para cuando quede sobre fondo navy (footer navy, barra de radio sticky): si no existe .webp en blanco, aplicar el wordmark en `--color-text-inverse` o solicitar el activo. Retirar el `logo-habla.svg` con play *volt* de R1.
- **Regenerar todos los placeholders SVG** de `assets/img/` (`video-*`, `nota-*`, `junior-*`, `seleccion-*`, `hero-*`) al tratamiento **claro/navy** descrito en §6.2. Los actuales (fondo `#0B0E11` + `#C6F53C`) deben reemplazarse.
- **Eliminar cualquier residuo de *volt*** (`#C6F53C` y familia) en HTML/CSS de componentes; el acento es navy.
- **Barra de radio y footer** pasan a fondo **navy** (piezas de marca sobre lienzo claro); el resto del sitio es claro.
- **Sponsor Transelca:** su azul `--sponsor-blue` solo dentro del módulo; el logo del sponsor sobre navy (barra de radio) necesita versión que contraste.

### 8.2 Resumen de grupos de tokens

| Grupo | Prefijo | Ejemplos |
|---|---|---|
| Color semántico | `--color-*` | `--color-bg`, `--color-surface`, `--color-text`, `--color-accent`, `--color-live` |
| Primitivas | `--navy-*`, `--slate-*`, `--live-*` | `--navy-700` (#294D6E), `--slate-50` |
| Tipografía | `--font-*`, `--fs-*`, `--fw-*`, `--lh-*`, `--ls-*` | `--font-display`, `--fs-hero`, `--ls-kicker` |
| Layout | `--container-*`, `--space-*`, `--gutter*`, `--*-height` | `--container-max`, `--space-6`, `--radio-bar-height` |
| Forma / elevación | `--radius-*`, `--shadow-*`, `--border-*` | `--radius-pill`, `--shadow-accent`, `--shadow-live` |
| Movimiento / capas | `--dur-*`, `--ease-*`, `--z-*` | `--dur-base`, `--ease-out`, `--z-radio` |

---

## 9. Qué NO hacer (guardarraíles anti-genérico)

- ❌ No usar el **azul brillante/turquesa de Transelca** fuera de su módulo. La marca es **navy `#294D6E`** + neutros claros.
- ❌ No reintroducir el **volt** (verde-lima) ni ningún acento estridente: quedó descartado por el cliente.
- ❌ No usar el **rojo** como color decorativo: es exclusivo de "AL AIRE / EN VIVO".
- ❌ No poner **titulares en mayúsculas** (R2 los suaviza a sentence case); mayúsculas solo en kickers/badges cortos.
- ❌ No apilar thumbnails sin scrim ni kicker (se vuelve "carrusel de YouTube").
- ❌ No convertir el sitio en oscuro por defecto: **light-first**; el dark es opcional y secundario.
- ❌ No page-loaders ni animaciones pesadas (lección técnica del sitio actual).

---

*Este documento define **el sistema visual** (revisión R2, dirección clara/navy/editorial). La construcción del prototipo la ejecuta `desarrollador-frontend` consumiendo `tokens.css`; el salto a WordPress lo planifica `estratega-wordpress` mapeando estos tokens a variables del tema.*
