# Accesibilidad AA · Elementor 4 + Pro + Hello

Objetivo: **WCAG AA real**, no solo la nota de Lighthouse. En el caso de referencia bastaron dos
arreglos invisibles para pasar de 87–97 a 93–97 (verde) en las 14 corridas; el 100 y los fallos AA
que Lighthouse no ve exigían tocar colores de marca y se presentaron como decisión del cliente.
Plantilla de código: `../scripts/tema-hijo/accesibilidad.plantilla.php`. Cómo medir: `medicion.md`.

---

## 1. La trampa: axe no audita lo que está a opacidad 0

**Síntoma.** Fallos reales de contraste (blanco sobre naranja 2,86:1, sobre rosa 2,04:1, sobre rojo
4,498:1, una franja verde 3,06:1) no aparecían en ningún informe de PSI.
**Causa.** Las animaciones de entrada dejan bajo el pliegue `html.pfx-js [data-pfx-reveal]
{opacity:0}` y axe omite los elementos con opacidad 0. Además axe trunca 4,498 a 4,49 y lo marca
como fallo en texto pequeño.
**Regla.** El sitio debe revelarlo todo con `prefers-reduced-motion: reduce` (también es un
requisito de accesibilidad). Haz una segunda pasada de accesibilidad con movimiento reducido:
```bash
npx -y lighthouse@13.5.0 "<URL>" --only-categories=accessibility \
  --chrome-flags="--headless=new --no-sandbox --force-prefers-reduced-motion" --output=json --output-path=<f>.json
```
y mide contraste contra el fondo **real** (si va sobre una foto o un velo, muestrea el píxel y
aplica la opacidad). En el caso se usó además un analizador estático propio sobre el HTML y el CSS
de las clases globales (resolvía variables del kit, opacidades y capas de fondo); era específico de
aquel proyecto y no se incluye: reescríbelo si lo necesitas, o mide en el navegador.
**Verificar:** `color-contrast` = 1 en la pasada con reduce.

## 2. Arreglos invisibles (aplícalos sin preguntar)

### 2.1 Foco en el panel móvil del menú de Pro (`aria-hidden-focus`)
**Síntoma.** `aria-hidden-focus` en los 7 informes móviles: el CTA y el selector de idioma que el
child añade al panel «dropdown» cerrado seguían en el orden de tabulación.
**Causa.** Pro solo pone `tabindex=-1` a sus propios enlaces.
**Regla.** En servidor, `wp_nav_menu_items` (solo `$args->menu_type === 'dropdown'`) añade
`tabindex="-1"` a los extras (funciona sin JS), y un script en `wp_footer` con `MutationObserver`
sobre el `aria-hidden` de `.elementor-nav-menu--dropdown.elementor-nav-menu__container` alterna
`tabindex` 0/-1 en todos sus enlaces.
**Verificar:** PSI móvil `aria-hidden-focus` = 1; recorrido con Tab a 412 px con el panel cerrado
(no entra) y abierto (entra).

### 2.2 Orden de encabezados (`heading-order`)
Los títulos de columna del pie eran `h4` después de un `h2`. Cambia `settings.tag` de h4 a h2
**conservando las clases** (el tamaño lo da la clase, no la etiqueta) con
`../scripts/wp-cli/atributos-por-id.php` (`'tag' => 'h2'`). Si el segundo idioma se regenera por
script, hazlo en el base y avisa.

### 2.3 Nombres accesibles
- Cuatro `<nav aria-label="Menu">` idénticos y en inglés: el setting `menu_name` del widget
  nav-menu es el `aria-label` del `<nav>`. Ponle uno único por menú y por idioma
  (`atributos-por-id.php`, `'menu_name' => '…'`). Verificar:
  `curl -s <URL> | grep -o '<nav aria-label="[^"]*"'`.
- Botón hamburguesa «Menu Toggle»: Pro no lo trae traducido al locale regional. Filtro
  `gettext_elementor-pro` según `pll_current_language()`. Las cadenas de Hello van por
  `gettext_hello-elementor`.
- El nombre del formulario de Pro se lee en voz alta: tradúcelo en el segundo idioma.
- Botones del vídeo de fondo: Elementor fija un `aria-label` en inglés («Play video»). El child
  copia el texto visible (editable) al `aria-label` por JS (`../../elementor4-atomico/references/tema-hijo.md`).

### 2.4 Autocompletado en formularios (WCAG 1.3.5)
Campos de nombre, correo y empresa sin `autocomplete`. Filtro
`elementor_pro/forms/render/item` + `$form->add_render_attribute( 'input' . $index, 'autocomplete',
… )` por `custom_id` del campo (`name`, `email`, `organization` en el caso), **sin tocar los campos
antispam**. Verificar: `curl -s /<contacto>/ | grep -o 'autocomplete="[a-z]*"'`.

### 2.5 Foco visible que Hello no pise
**Síntoma.** El prototipo no tenía foco visible; al añadirlo, el `reset.css` de Hello lo pisaba con
su contorno, y los elementos con sombra la perdían al hacer clic. **Causa.** Hello aplica
`[tabindex]:not([tabindex="-1"]):focus-visible` (0,3,0) y
`[tabindex]:not([tabindex="-1"]):focus:not(:focus-visible)` (0,4,0), que quita `box-shadow`.
**Regla.**
```css
html :is(a, button, input, select, textarea, summary, [tabindex]:not([tabindex="-1"])):focus-visible {
	outline: 3px solid var(--<color-foco>, #xxxxxx);   /* ≥ 3:1 contra los fondos donde aparece */
	outline-offset: 3px;
}
```
(especificidad 0,3,1) y repite las sombras decorativas también en `[tabindex]:focus` con 0,4,0 o
más (p. ej. `.elementor .<clase>[tabindex]:focus`; detalle en
`../../elementor4-atomico/references/tema-hijo.md`). Donde el
fondo es oscuro o de color, un contorno de otro color (en el caso, blanco en cabecera, pie y
bloques de color; tinta dentro de submenús blancos): decisión visible, preséntala.

### 2.6 El `#c36` de Hello en botones propios
El `reset.css` de Hello pinta `button:focus,button:hover{background-color:#c36;color:#fff}`
(0,1,1), que gana a una clase de botón propia (0,1,0): los puntos y flechas de un carrusel se
volvían magenta al tocarlos. Sobrescribe siempre hover/focus de tus `<button>`:
```css
.elementor .<clase-flecha>:is(:hover, :focus) { background: transparent; color: #fff; }
```

### 2.7 Estructura
`<main id="content">` y el «Saltar al contenido» funcionando: Hello y Elementor no envuelven el
contenido en `<main>`; el child lo abre y cierra en los hooks de Elementor (skill
`elementor4-atomico`). Plantillas single/404 con Diseño «Predeterminado» o saldrán dos `<main>`.

### 2.8 Movimiento
- Con `prefers-reduced-motion: reduce`, todo visible y sin transformaciones.
- Nada de animaciones infinitas (WCAG 2.2.2): se descartó una en la 404 del caso por eso.
- El vídeo de fondo no se descarga con reduce hasta pulsar Reproducir (`rendimiento.md` §6).

## 3. Cambios visibles: se presentan, no se aplican

Para pasar de verde a 100 (o para cumplir AA en lo que axe no ve) suele hacer falta cambiar
colores de marca, tamaños de objetivos táctiles o posiciones. Regla:
- En el plan, cada ítem lleva `visual_change: true|false`. Lo invisible se aplica; lo visible se
  presenta con el **ratio medido**, el valor propuesto y capturas lado a lado con el prototipo a
  375 y 1440 px. Decide el cliente, ítem por ítem.
- Ejemplos del caso (quedaron como decisión, el sitio se entregó en 93–97):

| Hallazgo | Medido | Propuesta |
|---|---|---|
| textos del pie en blanco translúcido | 3,00–3,75:1 | blanco al 65 % |
| enlace activo naranja sobre verde | 1,62:1 | blanco con subrayado permanente; hover naranja |
| acento naranja en titulares sobre blanco | 2,86:1 | un naranja más profundo solo para texto; el de marca queda en fondos |
| texto verde sobre gris claro | 4,28:1 | variable «verde-texto» algo más oscura |
| texto blanco sobre bloques de color | 2,04–4,498:1 | texto tinta en los bloques claros; ajuste mínimo del rojo |
| puntos del carrusel 8×8 px | `target-size` | botón de 24×24 con el punto dibujado en `::before` |
| número decorativo «01» | 1,21:1 | párrafo vacío y el número pintado por CSS (`content: attr(data-…)`) |
| pines del mapa superpuestos en móvil | `target-size` | mover dos pines (solo móvil) |

- Cualquier cambio de clase global: sincroniza el orden de clases «preview» (`e4-sync-preview.php`);
  tras cambiar clases o variables globales, `wp elementor flush_css` y purga (skill
  `elementor4-atomico`).

## 4. Checklist de accesibilidad de la sesión

- [ ] Pasada normal y pasada con `--force-prefers-reduced-motion`, móvil y escritorio.
- [ ] `aria-hidden-focus`, `heading-order`, `color-contrast`, `target-size`, `label`,
      `link-name`, `button-name` revisados con `audits.py` (nodos concretos, no solo la nota).
- [ ] Recorrido con teclado a 412 px y 1440 px: salto al contenido, menú móvil cerrado y abierto,
      formularios, foco visible en cada control.
- [ ] `<nav>` con nombres únicos y en el idioma de la página; hamburguesa traducida.
- [ ] `autocomplete` en nombre/correo/empresa; honeypot intacto.
- [ ] Contraste medido contra el fondo real en lo que tapan las animaciones.
- [ ] Lo visible, en la lista de decisiones del cliente con ratios y capturas.
- [ ] Segundo idioma: los mismos arreglos llegaron (si se regenera, compruébalo después).
