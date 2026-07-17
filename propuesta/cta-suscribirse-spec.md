# CTA "Suscribirse" — Especificación de contraste y coherencia

**Problema.** El botón rojo `#D20000` sobre superficies navy `#294D6E` (bloque `.inline-cta`, header sticky, `.live-hero__panel`) no separa del fondo: el borde del componente contra el navy da **1.57:1** (falla WCAG 1.4.11, que exige ≥3:1 para contorno de componente). Un intento previo de botón blanco con texto navy rompía el reconocimiento de marca (el rojo = acción + YouTube) y "no quedó bien".

**Diagnóstico de raíz.** No es un problema del botón, es un problema de **dos colores de marca saturados peleando**: un slab navy con un botón rojo encima. La solución no debe fragmentar la identidad del botón (a veces rojo, a veces blanco confunde). Se resuelve en dos frentes: (1) donde se puede, se retira el navy de detrás del rojo; (2) donde el navy es estructural (header, hero en vivo), se le da al botón rojo un contorno claro que garantiza el borde.

---

## 1. La regla general

**El botón `.btn--subscribe` es SIEMPRE rojo `#D20000` con texto e ícono de YouTube blancos.** El rojo es el color de acción único de la marca y su vínculo con YouTube; no cambia de color según el fondo. Lo que cambia es la **definición del borde**, según la superficie:

| Superficie | Tratamiento | Por qué |
|---|---|---|
| **CLARA** (blanco, tints claros, card, menú móvil, `inline-cta` rediseñado) | Rojo tal cual, sin anillo. Sombra sutil opcional. | Rojo vs blanco = **5.61:1** en el borde. Cumple sin ayuda. |
| **NAVY / OSCURA / FOTO** (header, `live-hero__panel`) | Rojo + variante `.btn--on-dark`: anillo claro de 1.5px + sombra. | El anillo blanco da borde ≥3:1 contra el navy y contra el rojo. |

Regla mental para el frontend: **si el botón se apoya sobre `--color-surface-brand` (navy), una foto o cualquier fondo oscuro → variante on-dark. Si se apoya sobre blanco o un tint claro → base.**

---

## 2. El `.inline-cta` — rediseño a superficie CLARA

En lugar de parchar el botón, se cambia el slab navy por una **card clara con acento navy**, replicando el patrón ya probado de `.wa-cta` (tint claro + botón de color encapsulado). Así el rojo funciona nativamente, el bloque se siente más aireado (feedback del cliente) y deja de competir con el navy.

**Valores exactos:**

| Elemento | Antes (navy) | Ahora (claro) | Ratio |
|---|---|---|---|
| Fondo card | `#294D6E` (navy-700) | **`#EEF3F8`** (`--navy-50`) | — |
| Borde | — | **`#BBD2E6`** hairline (`--navy-200`) | contorno card 1.4:1 vs página, decorativo |
| Acento (barra izq. 4px) | — | **`#294D6E`** (`--navy-700`) | 7.9:1 vs card ✓ |
| Título | `#FFFFFF` | **`#294D6E`** (`--color-accent-text`) | **7.90:1** ✓ AAA |
| Subtítulo | `rgba(255,255,255,.68)` | **`#334155`** (`--color-text-secondary`) | ~9:1 ✓ AAA |
| Botón | rojo (fallaba en borde) | **rojo base `.btn--subscribe`** | borde **5.03:1** vs `#EEF3F8` ✓; texto blanco **5.61:1** ✓ |

Especificación de referencia:

```css
.inline-cta {
  background: var(--navy-50);              /* #EEF3F8 */
  color: var(--color-text);
  border: var(--border-hairline) solid var(--navy-200);
  border-left: 4px solid var(--navy-700);  /* acento navy; rojo reservado al botón */
  box-shadow: var(--shadow-sm);            /* baja el peso: card, no slab */
}
.inline-cta__title { color: var(--color-accent-text); }      /* navy #294D6E, 7.9:1 */
.inline-cta__sub   { color: var(--color-text-secondary); }   /* slate-700 */
/* El botón NO se sobre-escribe: usa .btn--subscribe base (rojo). */
```

> Se elimina por completo el override `inline-cta .btn--subscribe { background:#fff; color:navy }` y el ícono rojo sobre blanco. El botón vuelve a ser el rojo estándar.

Aplica a: `articulo.html` (línea ~249) y `en-vivo.html` (`.inline-cta`, línea ~397).

---

## 3. Variante `.btn--on-dark` (rojo sobre navy/oscuro/foto)

El botón mantiene fill rojo y texto blanco; se añade un **anillo claro + sombra** que crea el borde exigido por WCAG 1.4.11.

```css
.btn--subscribe.btn--on-dark {
  background: var(--brand-red);            /* #D20000 sin cambios */
  color: #fff;
  box-shadow:
    0 0 0 1.5px rgba(255, 255, 255, 0.90), /* anillo: borde vs superficie y vs fill */
    0 4px 14px rgba(14, 27, 41, 0.35);     /* elevación sobre navy/foto */
}
.btn--subscribe.btn--on-dark:hover  { background: var(--brand-red-hover); } /* #B00000, anillo intacto */
.btn--subscribe.btn--on-dark:active { transform: translateY(1px); }
.btn--subscribe.btn--on-dark:focus-visible {
  outline: 2px solid #fff;
  outline-offset: 2px;
}
```

**Ratios verificados (variante on-dark sobre navy `#294D6E`):**

| Par de contraste | Ratio | Requisito | Estado |
|---|---|---|---|
| Texto blanco vs fill rojo `#D20000` | **5.61:1** | ≥4.5 texto | ✓ AA |
| Anillo blanco (~`#FFF`) vs superficie navy | **8.83:1** | ≥3 contorno | ✓ |
| Anillo blanco vs fill rojo | **5.61:1** | ≥3 contorno | ✓ |
| Hover: texto blanco vs `#B00000` | **7.38:1** | ≥4.5 texto | ✓ AA |

Sin el anillo, el borde rojo↔navy es **1.57:1** (falla). Con el anillo, el botón queda perfectamente delimitado sobre navy, foto de hero, o cualquier fondo oscuro.

> **Tokens sugeridos** (para `tokens.css`, evita valores sueltos):
> ```css
> --subscribe-ring:        rgba(255, 255, 255, 0.90);
> --subscribe-shadow-dark: 0 4px 14px rgba(14, 27, 41, 0.35);
> ```

---

## 4. Aplicabilidad — dónde va cada variante

### Variante BASE (rojo, sin anillo) — superficies claras
| Ubicación | Superficie | Ratio borde |
|---|---|---|
| Menú móvil `.mobile-menu .btn--subscribe` (todas las páginas) | overlay blanco `--color-surface` | 5.61:1 ✓ |
| `.inline-cta` rediseñado — `articulo.html`, `en-vivo.html` | `#EEF3F8` navy-50 | 5.03:1 ✓ |
| `.video-actions .btn--subscribe` — `video.html` | card blanca `--color-surface` | 5.61:1 ✓ |
| `.program-actions .btn--subscribe` — `programas.html` | body claro | 5.61:1 ✓ |

### Variante ON-DARK (rojo + anillo) — superficies navy/oscuras
| Ubicación | Superficie | Ratio texto / borde |
|---|---|---|
| Header desktop `.site-header .btn--subscribe` — todas las páginas | transparente sobre hero **y** navy sticky `.is-stuck` | 5.61:1 / 8.83:1 ✓ |
| `.live-hero__actions .btn--subscribe` — `en-vivo.html` | `.live-hero__panel` navy `#294D6E` | 5.61:1 / 8.83:1 ✓ |

**Implementación en HTML:** añadir la clase `btn--on-dark` al `<a class="btn btn--subscribe">` del header (todas las páginas) y del `.live-hero__actions` en `en-vivo.html`. El resto queda en base. Alternativa sin tocar HTML: heredar por contexto en CSS —
```css
.site-header .btn--subscribe,
.live-hero__panel .btn--subscribe { /* aplicar el bloque on-dark */ }
```

---

## 5. Resumen de la decisión

- **Identidad intacta:** el botón es rojo `#D20000` con YouTube blanco en todo el sitio. Nunca blanco, nunca otro color. Reconocimiento de marca + acción preservado.
- **Dos superficies, un solo botón:** claro → base; navy/oscuro/foto → `.btn--on-dark` (anillo blanco 1.5px + sombra) que resuelve el borde.
- **El `.inline-cta` deja de ser navy:** pasa a card clara `#EEF3F8` con acento navy y título navy 7.9:1; el rojo trabaja nativo (5.03:1). Más aireado, coherente con `.wa-cta`.
- **Coherencia:** mismo patrón de "tint claro + botón de color encapsulado" que ya usa la franja de WhatsApp.
- Todos los pares cumplen **AA** (texto ≥4.5:1, contorno ≥3:1).
