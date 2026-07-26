# Guía de construcción en Elementor — Habla Deportes

Valores **exactos** del prototipo aprobado. Sigue el orden: primero los ajustes
globales, luego header y footer. Así todo lo que construyas después hereda bien.

---

## A · Ajustes del sitio

`Elementor → Ajustes del sitio` (menú ☰ arriba a la izquierda dentro del editor).

### A1. Layout

| Campo | Valor |
|---|---|
| Ancho de contenido | **1280** px |
| Espacio entre widgets | **20** px |
| Padding del contenedor | **16** px (móvil) |

> El kit importó colores y tipografías, pero **no** el ancho de contenido:
> Elementor viene en 1140 px. Cámbialo a 1280 o todo quedará más angosto que
> el prototipo.

### A2. Puntos de interrupción

`Ajustes del sitio → Layout → Breakpoints` (o Elementor → Ajustes → Funciones →
Breakpoints adicionales).

| Dispositivo | Valor |
|---|---|
| Mobile | **767** px |
| Tablet | **1023** px |

El prototipo cambia a desktop en **1024 px**; con estos valores coincide.

### A3. Tipografía — escala del prototipo

Las familias ya vienen del kit (Archivo para títulos, Inter para texto). Faltan
los tamaños. En `Ajustes del sitio → Tipografía`:

| Elemento | Familia | Peso | Tamaño desktop | Móvil |
|---|---|---|---|---|
| H1 | Archivo | 700 | **52** px | 36 px |
| H2 | Archivo | 700 | **36** px | 28 px |
| H3 | Archivo | 700 | **28** px | 22 px |
| H4 | Archivo | 600 | **22** px | 20 px |
| Cuerpo | Inter | 400 | **16** px | 16 px |

Interlineado: títulos **1.15**, cuerpo **1.6**.
Espaciado entre letras en títulos: **-0.02em** (el prototipo usa títulos
ligeramente condensados).

### A4. Estilos del tema

| Ajuste | Valor |
|---|---|
| Color de fondo del sitio | `#F4F6F8` (Fondo página) |
| Color de enlace | `#294D6E` (Accent) |
| Color de enlace :hover | `#E36209` (Naranja hover) |

---

## B · Header

`Plantillas → Theme Builder → Encabezado → Añadir nuevo`.
Condición de visualización: **Todo el sitio**.

### B0. Estructura

El header tiene **dos franjas**:

```
┌──────────────────────────────────────────────┐
│ TOPBAR navy · "El show de la mañana…"        │  ← franja fina
├──────────────────────────────────────────────┤
│ LOGO      Inicio Junior Fútbol…   [Suscribirse] │  ← barra principal
└──────────────────────────────────────────────┘
```

### B1. Topbar (franja superior)

Contenedor a ancho completo:

| Propiedad | Valor |
|---|---|
| Fondo | `#294D6E` (Accent / navy de marca) |
| Altura mínima | **34** px (desktop) · **32** px (móvil) |
| Padding vertical | 6 px |
| Texto | Inter **14** px, peso 500, color `rgba(255,255,255,.88)` |
| Alineación | Centrado (desktop) |

Contenido: un punto rojo (`#D20000`) + el texto
*"El show de la mañana, de lunes a viernes a las 10:00 AM"*.

> El punto que parpadea y el efecto de brillo del prototipo son CSS a medida.
> Si los quieres, ver **Anexo 1** al final.

### B2. Barra principal

| Propiedad | Valor |
|---|---|
| Altura mínima | **68** px (desktop) · **56** px (móvil) |
| Ancho | Full width, contenido a 1280 px |
| Fondo | **Transparente** (se superpone al hero) |
| Padding lateral | clamp 16–48 px |

Estructura interna: contenedor horizontal, 3 zonas →
**logo** (izquierda) · **menú** (centro/derecha) · **botón** (derecha).

**Logo:** alto **34** px, ancho automático, alineado a la izquierda.

**Enlaces del menú:**

| Propiedad | Valor |
|---|---|
| Tipografía | Inter **16** px, peso 500 |
| Color | `rgba(255,255,255,.92)` |
| Color :hover | `#FFFFFF` |
| Espacio entre ítems | 24 px |
| Altura mínima del enlace | 40 px (área táctil) |

Ítems: **Inicio · Junior · Fútbol · Programas · Más** (máximo 5 — el cliente
pidió expresamente un navbar despejado).

**Botón "Suscribirse":**

| Propiedad | Valor |
|---|---|
| Fondo | `#D20000` (Rojo vivo) |
| Fondo :hover | `#B00000` |
| Texto | Inter 15 px, peso 600, blanco |
| Padding | 10 px 20 px |
| Radio | 999 px (pastilla) |
| Icono | YouTube, 20 px, a la derecha |

> El rojo se reserva **solo** para este botón y para el estado "en vivo".
> No lo uses en otros elementos: pierde fuerza.

### B3. Comportamiento al hacer scroll

El header arranca **transparente sobre el hero** y pasa a **navy sólido** al
bajar. En Elementor:

1. Selecciona el contenedor del header → pestaña **Avanzado → Efectos de
   movimiento**.
2. Activa **Sticky: Arriba**.
3. Activa **"Efectos al hacer scroll"** y define para el estado sticky:
   - Fondo: `#294D6E`
   - Sombra: `0 2px 12px rgba(0,0,0,.15)`

En las páginas internas (que no tienen hero) el header debe ser navy sólido
desde el inicio.

---

## C · Footer

`Theme Builder → Pie de página → Añadir nuevo`. Condición: **Todo el sitio**.

### C1. Contenedor general

| Propiedad | Valor |
|---|---|
| Fondo | `#294D6E` (navy de marca) |
| Color de texto | Blanco |
| Color de enlaces | `rgba(255,255,255,.72)` |
| Enlaces :hover | `#FFFFFF` |
| Margen superior | **80** px |
| Padding vertical | **48** px |

### C2. Grilla de 4 columnas

Proporciones del prototipo: **1.6fr · 1fr · 1fr · 1.2fr**, separación **40 px**.
En tablet baja a 3 columnas; en móvil, a una.

| Columna | Contenido |
|---|---|
| 1 — Marca | Logo (34 px) + *"Video, análisis y radio del fútbol colombiano"* + iconos sociales |
| 2 — Secciones | Junior · Fútbol · Videos · Clips · Fichajes |
| 3 — El programa | *El show de la mañana* · Lunes a viernes · 10:00 a.m. – 12:00 m. · Barranquilla |
| 4 — Institucional | Nosotros · Publicidad · Contacto · Aviso legal |

**Títulos de columna:** Archivo 14 px, peso 700, mayúsculas, letter-spacing
0.08em, color `rgba(255,255,255,.6)`.

### C3. Franja inferior

Separada por una línea `rgba(255,255,255,.15)`, padding vertical 20 px,
distribuida a los extremos:

- Izquierda: `© 2026 Habla Deportes. Todos los derechos reservados.`
- Derecha: enlaces legales pequeños (12 px).

---

## D · Orden sugerido de trabajo

1. Ajustes del sitio (sección A) — **primero, siempre**
2. Header
3. Footer
4. Plantilla de **Archivo** (sirve para `/videos/`, `/equipo/junior/`, etc.)
5. Plantilla de **Entrada individual** (nota) y de **Video**
6. Home (página estática)

> Las páginas de sección **no se crean a mano**: `/videos/`, `/equipo/junior/`,
> `/torneo/liga-betplay/` ya existen. Se diseñan con la plantilla de Archivo.

---

## Anexo 1 · Topbar animado (opcional)

Si quieres el punto pulsante y el brillo del texto del prototipo, añade un
widget **HTML** en el topbar con esto:

```html
<div class="hd-topbar">
  <span class="hd-dot"></span>
  <p>El show de la mañana, de lunes a viernes a las 10:00 AM</p>
</div>

<style>
.hd-topbar{display:flex;align-items:center;justify-content:center;gap:8px}
.hd-topbar p{margin:0;font:500 14px/1.45 Inter,sans-serif;color:rgba(255,255,255,.88)}
.hd-dot{width:9px;height:9px;border-radius:50%;background:#D20000;flex:none;
  animation:hdPulse 1.6s ease-in-out infinite}
@keyframes hdPulse{
  0%,100%{box-shadow:0 0 0 0 rgba(210,0,0,.55)}
  50%{box-shadow:0 0 0 6px rgba(210,0,0,0)}
}
@media (prefers-reduced-motion:reduce){.hd-dot{animation:none}}
</style>
```

---

## Anexo 2 · Referencia visual

El sitio de **laboratorio** tiene el prototipo funcionando en WordPress. Ábrelo
al lado mientras diseñas: sirve para comparar y para copiar CSS con el
inspector del navegador.
