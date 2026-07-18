# Especificación UX — Megamenú con hover para el header de Habla Deportes

Consulta de UX/UI. Este documento es **solo especificación de comportamiento**; no implementa código de producción (eso corresponde a `desarrollador-frontend`).

## 0. Diagnóstico: qué hay hoy y por qué "se desaparece inmediatamente"

Revisé `propuesta/html/index.html`, `propuesta/html/assets/css/main.css` y `propuesta/html/assets/js/home.js` (las páginas activas del prototipo: `articulo.html`, `en-vivo.html`, `programas.html`, `seccion.html`, `video.html` comparten el mismo header). Hallazgo clave, distinto al que se suele asumir:

**Hoy el megamenú no existe — no es que se cierre rápido, es que nunca llega a abrirse.**

- En el header (`index.html` líneas 100–114 y equivalentes en las demás páginas), los ítems "Futbol" y "Más" son `<a class="nav-link" href="#">` con un SVG `.nav-link__caret` puramente decorativo. No hay ningún elemento de panel en el HTML, ninguna regla en `main.css` que muestre/oculte algo al hacer hover, y `home.js` (líneas 1–60 y el resto del archivo) solo controla: sombra del header al hacer scroll, menú hamburguesa, slider del hero, ticker y carrusel de "El show". No hay un solo `mouseenter`/`mouseleave`/`click` atado a `.nav-link__caret` o a los ítems "Futbol"/"Más".
- Consistente con esto, `tokens.css` (línea 251) ya reserva `--z-dropdown: 300` con el comentario *"menús Equipos/Torneos"* — es decir, el sistema de diseño **previó** este megamenú pero nunca se cableó.
- Verifiqué también el sitio en producción (https://habladeportes.co/): el header actual (Elementor) no tiene menú de navegación con submenús; solo un botón "Youtube" y enlaces de pie de página. Tampoco es la fuente del bug reportado.
- Sí encontré, sin usar por ninguna página actual, un sistema de megamenú completo de una iteración anterior del rediseño (paleta oscura/naranja, previa al pivote a navy #294D6E documentado en `decisiones-diseno.md`): `assets/css/nav.css` + `assets/js/nav.js` + `assets/main.js` + `assets/styles.css`. Ese sistema **ya resuelve correctamente** el problema de cierre inmediato: usa `transition-delay: 300ms` solo al cerrar (0ms al abrir), `:focus-within`, un puente invisible (`.has-dropdown::after`, 12px) y, para el mega full-width, `align-self:stretch` en el `<li>` para eliminar el hueco por completo. Es la base técnica correcta — el problema es que quedó huérfano cuando el diseño pivotó a la paleta navy y no se volvió a conectar a `main.css`/`home.js`/`index.html`.

**Conclusión:** el megamenú hay que construirlo desde cero sobre `main.css`/`home.js` (no reparar uno roto), pero existe una base ya resuelta y probada en `nav.css`/`nav.js` que se puede retomar, re-temizar a navy y simplificar. Si el "se desaparece inmediatamente" que reporta el cliente viene de una demo o boceto que vio en algún momento del proceso, el patrón más probable es el típico de un `mouseleave` puesto directamente sobre el `<li>` disparador sin puente ni demora — exactamente lo que `nav.css` ya evita y lo que esta especificación exige para la versión final.

---

## 1. Patrón recomendado: hover-intent + puente + demora de cierre

No se necesita el algoritmo completo de "triángulo seguro" (safe-triangle con seguimiento de trayectoria del cursor vía JS) — es el patrón que usan interfaces con paneles flotantes que NO están alineados directamente bajo el disparador (ej. Amazon). Aquí el panel cuelga justo debajo del ítem de nav, así que una combinación más simple de **dos mecanismos redundantes** resuelve el problema por completo y es mucho más barata de implementar y mantener:

### a) Apertura con intención (evita flicker al pasar de largo)
- `mouseenter` sobre el disparador → arrancar un temporizador de **100–150ms**.
- Si el cursor sale antes de que venza el temporizador (el usuario solo pasó de largo camino a otro ítem), cancelar: el panel nunca llega a abrirse.
- Si el temporizador vence con el cursor aún dentro, abrir el panel.
- El foco por teclado (`:focus-within` / `focus`) **abre sin demora** — la demora es solo para affordance de mouse, nunca para teclado.

### b) Puente invisible entre el disparador y el panel (mecanismo primario)
- Para el megamenú full-width ("Futbol"): hacer que el `<li>` disparador se estire a toda la altura de la barra (`align-self: stretch` dentro de un `nav-list` en `display:flex`) para que **no exista hueco geométrico** entre el texto del ítem y el borde superior del panel. Es el patrón ya usado en `nav.css` línea 38 (`.sticky-nav .has-mega{position:static;align-self:stretch;...}`) — recomiendo reutilizarlo tal cual para el header navy.
- Para un dropdown simple no full-width (si "Más" termina siendo una lista corta en vez de mega), agregar un pseudo-elemento `::after` sobre el `<li>` que cubra el margen vertical entre el trigger y el panel (8–12px de alto, mismo ancho que el panel) para que el cursor nunca esté "fuera" de área interactiva mientras viaja en línea recta hacia abajo.
- Esto por sí solo ya elimina la inmensa mayoría de los cierres accidentales, porque no depende de temporizadores: el cursor sigue "dentro" de la zona interactiva en todo momento.

### c) Demora de cierre como red de seguridad (~250–300ms, cancelable)
- Aun con el puente, un usuario puede mover el cursor en diagonal más allá del área cubierta (por ejemplo, saltar directo hacia un enlace de la columna derecha del mega, más ancho que el propio disparador). Para esos casos: al disparar `mouseleave` (del disparador **o** del panel — deben tratarse como una sola región para efectos de este temporizador), esperar **250–300ms** antes de cerrar.
- Si en ese lapso el cursor vuelve a entrar al disparador o al panel, cancelar el cierre inmediatamente.
- El cierre **explícito** (clic fuera, Escape, clic en un enlace, clic en otro disparador) es **instantáneo, sin demora** — la demora de 250–300ms es exclusiva del camino de hover. `nav.css` ya distingue esto con la clase `.is-dismissed` (transition-delay:0 por 400ms) precisamente para que un clic no se sienta "pegajoso".
- Implementación: un único `hoverCloseTimer` por megamenú abierto (no por elemento), seteado en el `mouseleave` combinado del disparador+panel y limpiado en cualquier `mouseenter` sobre esa misma región. Evita temporizadores duplicados si el usuario entra y sale varias veces rápido.

**Por qué esta combinación y no un triángulo seguro completo:** el triángulo seguro brilla cuando el panel está desplazado lateralmente respecto al disparador (menús verticales tipo Amazon) y hay que decidir hacia qué submenú "va" el cursor entre varias opciones simultáneas. Aquí el panel está siempre directamente debajo del disparador (mega full-width) o pegado a su borde (dropdown simple), sin ambigüedad de destino — puente + demora resuelven el 100% de los casos con una fracción del código y sin necesidad de calcular geometría de trayectoria en cada `mousemove`.

**Valores recomendados (resumen):**

| Evento | Retraso | Cancelable |
|---|---|---|
| Abrir por hover | 120ms | Sí (si el mouse sale antes) |
| Abrir por foco/teclado | 0ms | — |
| Cerrar por hover (mouseleave disparador+panel) | 280ms | Sí (si el mouse re-entra) |
| Cerrar explícito (clic fuera, Esc, clic en enlace, abrir otro ítem) | 0ms | — |

---

## 2. Estructura visual del panel

- **"Futbol" → panel full-width**, anclado a `.site-header__inner` (no al `<li>`), ancho completo del viewport bajo la barra, con columnas internas centradas al mismo `max-width` que el resto del sitio (`.container`). Justificación: es la sección con más subcategorías esperables (Selección Colombia, Junior, Liga Betplay, Fichajes/Temporada de humo, Internacional…) y un mega de columnas comunica mejor la profundidad del sitio que una lista angosta. Puede incluir una columna destacada a la derecha con una nota/video en curso (thumbnail + título), como ya prototipó `nav.css` (`.mega-feat`).
- **"Programas" y "Más" → dropdown simple anclado al ítem** (no full-width): son listas cortas (2–5 enlaces) y anclarlas al propio disparador evita un panel gigante para poco contenido.
- **Fondo:** dado que el header alterna transparente-sobre-hero → navy sólido al hacer scroll (`main.css` líneas 143–167, `.site-header.is-stuck`), el panel del megamenú debe **forzar el header a su estado sólido mientras esté abierto** (igual que hace `nav.css` línea 87 con `:has(.has-dropdown:hover)` — o el equivalente vía clase JS `.menu-open` para navegadores sin soporte de `:has()`), para que el panel nunca cuelgue de una barra transparente sobre el video del hero. El panel en sí: fondo navy sólido (`--color-surface-brand`) o blanco, a elegir junto con `director-arte` para mantener consistencia con el resto del sistema claro adoptado en `decisiones-diseno.md`; en cualquier caso con sombra (`--shadow-md`, ya definida en tokens) para separarlo del contenido debajo.
- **Animación de entrada:** fade + `translateY(-6/-8px → 0)`, 180–220ms con `--ease-out` (ya definido en `tokens.css`), igual que el patrón de `nav.css`. Nada de `display:none/block` puro (rompe la transición); usar `opacity` + `visibility` + `transform`, con `pointer-events:none` mientras está oculto para que no capture clics ni interfiera con el puente cuando está cerrado.
- **z-index:** ya existe el token `--z-dropdown: 300` en `tokens.css` — usarlo tal cual para el panel.

---

## 3. Accesibilidad y teclado (no puede depender solo de hover)

- Disparador: `aria-haspopup="true"` + `aria-expanded="false|true"` (se actualiza en apertura/cierre, no solo en hover — también en foco/clic).
- **Tab** llega al disparador → foco visible; **Enter/Espacio/Flecha abajo** abre el panel sin demora y mueve el foco al primer enlace.
- **Flechas arriba/abajo** navegan entre los enlaces del panel (o Tab/Shift+Tab si se prefiere no reinterceptar Tab — cualquiera de las dos es válida, pero debe ser consistente en las tres barras).
- **Esc** cierra el panel y devuelve el foco al disparador (nunca lo deja "perdido" en un elemento oculto).
- **Tab** en el último enlace del panel cierra el panel y continúa el flujo natural hacia el siguiente elemento del header (el botón "Suscribirse").
- El disparador debe seguir siendo navegable con teclado aunque no tenga JS cargado: si es un `<a href="#">` sin JS, hoy no lleva a ningún sitio (ver diagnóstico); lo correcto es que el disparador apunte a una URL real de categoría (ej. `/futbol/`) para que degrade con gracia (sin JS, sin hover, el clic al menos navega a la sección en vez de no hacer nada).

## 4. Touch (tablets/desktop táctil)

- Con `(hover: none)` o `(pointer: coarse)`, el hover-intent no aplica: el **primer tap** sobre el disparador abre el panel (sin navegar); un **segundo tap** en el mismo disparador, o un tap fuera, lo cierra; un tap en un enlace del panel navega normalmente.
- En el breakpoint actual (`main.css` línea 327: `<1024px` oculta `.primary-nav` y usa el menú hamburguesa), esto solo importa para tablets/desktop con pantalla táctil ≥1024px — pero hay que cubrirlo explícitamente, no asumir que "en móvil ya no aplica".

## 5. Móvil (<1024px, menú hamburguesa)

- El mega de columnas **colapsa a acordeón** dentro de `#mobile-menu`: cada sección con submenú se vuelve un `<button>` disparador (no un `<a>`, para no navegar) que expande/colapsa su grupo de enlaces con `aria-expanded` + animación de alto (grid-template-rows `0fr → 1fr`, sin medir en JS).
- Nunca por hover en móvil — todo por tap/clic.
- Aquí también existe ya una implementación reutilizable en el código huérfano: `nav.js` líneas 124–156 (`.mm-trigger` / `.mm-group`, no exclusivo — abrir un grupo no cierra el otro) y su CSS en `nav.css` líneas 115–134. Es la misma lógica que hay que portar al `#mobile-menu` actual de `main.css`/`home.js`, adaptando las clases.

## 6. `prefers-reduced-motion`

- Con movimiento reducido: quitar las transiciones de apertura/cierre (mostrar/ocultar sin animación) pero **mantener** los temporizadores de intención/cierre — no son movimiento, son timing de interacción y siguen evitando el parpadeo.

---

## Resumen de la corrección respecto a lo que existe hoy

1. Construir el HTML del panel (hoy no existe: los carets de "Futbol"/"Más" no tienen panel asociado en `index.html` ni en las demás páginas).
2. Cablear en `home.js` (o un nuevo `assets/js/nav.js` re-temizado, retomando la lógica ya resuelta del archivo huérfano del mismo nombre): temporizador de apertura 120ms cancelable, temporizador de cierre 280ms cancelable, cierre instantáneo en clic fuera/Esc/clic en enlace/apertura de otro disparador.
3. En CSS: puente geométrico (`align-self:stretch` para el mega full-width; `::after` de 8–12px para dropdowns simples) como mecanismo primario, demora de cierre como red de seguridad — nunca demora sola sin puente, ni puente solo sin demora (la diagonal hacia contenido ancho del mega sigue rompiendo el puente si no hay demora).
4. Forzar el header a su estado sólido (`is-stuck`) mientras el panel esté abierto, para que nunca quede colgando de la barra transparente sobre el hero.
5. Reutilizar el patrón de acordeón ya resuelto en `nav.js`/`nav.css` (huérfano) para el equivalente en móvil dentro de `#mobile-menu`.
