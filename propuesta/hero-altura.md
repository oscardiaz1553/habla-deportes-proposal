# Altura del hero de la Home — diagnóstico y recomendación

**Archivo a editar:** `propuesta/html/assets/css/main.css` → selector `.hero__slide` (líneas ~329‑339).
**Contexto:** el cliente siente el hero "muy pequeño en altura". El hero es la pieza estelar (video‑first): retrato del presentador reaccionando, botón play centrado, titular centrado en la zona centro‑inferior, meta "YouTube · hace X" y dots.

---

## 1. Diagnóstico — por qué se siente bajo

Hoy la altura se deriva de `aspect-ratio` + `min-height: 440px` + `max-height: 78vh`:

```
móvil            aspect-ratio: 16/10
≥768px (tablet)  aspect-ratio: 21/9
≥1280px (desktop) aspect-ratio: 24/9
min-height: 440px ;  max-height: 78vh
```

El problema es que el `aspect-ratio` **ata la altura al ancho**, y en formatos anchos (21:9, 24:9) eso produce una franja baja. Alturas reales resultantes:

| Dispositivo (ancho×alto) | Fórmula activa | Altura real | Sensación |
|---|---|---|---|
| Móvil 390×844 | 16/10 → 244px, pisa min 440 | **440px** | corto |
| Tablet 768×1024 | 21/9 → 329px, pisa min 440 | **440px** | corto |
| Tablet 1024×768 | 21/9 → 439px | **~440px** | corto |
| Laptop 1280×800 | 24/9 → 480px | **480px** | franja baja |
| Laptop 1440×900 | 24/9 → 540px | **540px** (≈60vh) | franja baja |
| Desktop 1920×1080 | 24/9 → 720px, tope 78vh=842 | **720px** | aceptable pero es 2.67:1 |

En el equipo más común (1440×900) el hero mide **540px / ≈2.67:1**: una franja ancha y delgada donde la cara del presentador y el titular quedan apretados. **El `24/9` es el culpable.**

El mockup del cliente (`Home - Diario Deportes.png`) muestra lo contrario: un hero **tipo portada/retrato**, alto, de proporción ≈**1.9:1** (ocupa casi toda la mitad superior de la pantalla, deja ver el ticker y un asomo de la sección siguiente). Los benchmarks de medios deportivos ubican el hero de portada en **~80–88vh** (≈620–860px).

---

## 2. Recomendación — altura por viewport con topes de seguridad

Reemplazar el enfoque de `aspect-ratio` por **altura basada en viewport con `min(Xvh, Ypx)` y un `min-height` de piso**. Esto da impacto de portada, se autolimita en monitores gigantes y no salta (CLS = 0 porque la altura queda definida antes de cargar la imagen).

### Valores recomendados (principal)

```css
.hero__slide {
  position: relative;
  width: 100%;
  height: min(70vh, 580px);   /* MÓVIL (base) */
  min-height: 460px;          /* piso: titular + play respiran */
  overflow: hidden;
  /* eliminar: aspect-ratio y max-height (los reemplaza height: min()) */
}

@media (min-width: 768px) {   /* TABLET */
  .hero__slide {
    height: min(78vh, 680px);
    min-height: 520px;
  }
}

@media (min-width: 1280px) {  /* DESKTOP */
  .hero__slide {
    height: min(80vh, 720px);
    min-height: 600px;
  }
}
```

**Nota:** quitar `aspect-ratio` y `max-height` del bloque base y los `aspect-ratio` de los dos `@media`. La altura ahora la fija `height`.

### Alturas resultantes con estos valores

| Dispositivo | Antes | Ahora | Proporción nueva |
|---|---|---|---|
| Móvil 390×844 | 440px | **~580px** (min(608,580)) | ~0.67:1 (retrato) |
| Móvil pequeño 375×667 | 440px | **~467px** (min 460 piso) | equilibrado |
| Tablet 768×1024 | 440px | **~680px** | ~1.1:1 |
| Laptop 1280×800 | 480px | **640px** (80vh) | 2:1 |
| Laptop 1440×900 | 540px | **720px** (80vh) | 2:1 |
| Desktop 1920×1080 | 720px | **720px** (tope) | no crece de más |

En 1440×900 pasa de 540→**720px** (de 2.67:1 a **2:1**, cerca del ~1.9:1 del mockup) y aún deja ~110px para que el ticker y el inicio de "El show de la mañana" asomen bajo el fold. No se come toda la pantalla.

### Alternativas (si se quiere ajustar el punto medio, solo desktop)

- **Más alto (más portada):** `height: min(86vh, 780px); min-height: 640px;`
- **Más bajo (más contenido a la vista):** `height: min(74vh, 660px); min-height: 560px;`

---

## 3. Verificación de composición (no hay colisiones)

Los elementos internos usan **porcentajes**, así que escalan con la nueva altura y ganan aire (justo lo que pedía el cliente: que la cara y el titular "respiren"):

- `.hero__play` en `top: 44%` → en desktop 720px cae a ~317px (buena zona alta‑centro).
- `.hero__content` (titular) en `top: 58–60%` → ~418px; quedan ~300px por debajo para meta + dots. Sin solapes.
- `.hero__dots` anclado a `bottom: var(--space-4)` → siempre pegado al borde inferior, sin chocar con el titular.
- El `min-height` (460 móvil / 520 / 600) evita que en pantallas cortas play y titular se aprieten.

**`object-position` de `.hero__img`:** hoy `48% 28%`. Al pasar a un encuadre más vertical (menos recorte arriba/abajo), la cabeza tiende a subir. Recomiendo **`50% 25%`** para mantener cabeza/manos y el escudo del Junior en el tercio superior y dejar el pecho (centro‑inferior) limpio para el titular. En móvil `48% 28%` sigue funcionando; si se unifica, `50% 25%` sirve para todos los breakpoints.

## 4. CLS
La altura queda **explícita** (viewport + px) desde el primer render, y la imagen es `position:absolute; inset:0; object-fit:cover`, por lo que no genera reflow al cargar. Cero salto de layout.
