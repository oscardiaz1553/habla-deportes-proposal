# Propuesta nueva · fuentes del sitio

Generador estático en Python (3.8+, requiere Pillow). Funciona en Windows y Linux.

    python3 propuesta/nueva-src/build.py

Salida: `propuesta/html/nueva/*.html` (se abren con doble clic). No editar los HTML de salida: se sobrescriben.

## Dónde está cada cosa

| Archivo | Qué contiene |
|---|---|
| `sitio.py` | **Único origen de verdad** de la navegación: barra azul, panel «Más», barras contextuales, menú móvil, barra inferior, pie y el mapa de las 18 páginas (sección, ítem activo). |
| `datos.py` | Lee `propuesta/nueva/data-ejemplo.json` (fuente única de datos) y formatea fechas y horas. |
| `componentes.py` | Componentes de `01-sistema-de-diseno.md` y bloques de datos (tabla, partidos, mercado, banner, pauta, sello…). Se llaman desde las páginas con `{{ nombre(argumentos) }}`. |
| `plantilla.py` | `<head>` (metadatos, Open Graph, JSON-LD), cabecera de dos niveles + barra contextual, buscador, menú móvil, pie, barra inferior y reproductor de radio. Equivale a `header.php`/`footer.php`. |
| `imagenes.py` | Catálogo de imágenes con su `alt` (03-contenido, Banco 1) y recorte. Convierte a WebP (calidad 80, mismas dimensiones) solo las usadas. |
| `nucleo.py` | Iconos (sprite Phosphor inline, solo los usados), `<img>` con `width`/`height` y lazy, escudos. |
| `paginas/*.html` | Una fuente por página: cabecera de metadatos entre `---` y el contenido de `<main>`. |
| `iconos/` | SVG de Phosphor (licencia MIT incluida). |

CSS y JS se editan directamente en `propuesta/html/nueva/assets/`: `tokens.css` → `base.css` → `components.css` (orden de 01 §6) → `pages.css`; `js/main.js`.

## Añadir o completar una página

1. Si es nueva, regístrala en `PAGINAS` de `sitio.py` (sección, ítem activo).
2. Sustituye el contenido de `paginas/<slug>.html` (hoy las provisionales llaman a `en_construccion(...)`). Metadatos admitidos: `titulo`, `descripcion`, `tipo` (`portada`|`articulo`|`pagina`), `url_wp`, `og_imagen`, `migas` (`Texto>destino|…`), `pauta_cierre` (`si`|`no`), y para artículos `titular`, `autor`, `publicado`.
3. Ejecuta el build. Falla (y no da por buena la página) si: una foto se repite en la misma página, falta o sobra un `h1`, un enlace apunta a un archivo inexistente, hay `href="#"` o aparece una raya (— –) en el texto.

## Reglas que el código ya hace cumplir

- Datos solo desde el JSON; todo módulo de datos lleva «Datos de ejemplo».
- Rojo solo para «en vivo»; errores de formulario en tinta (borde 2 px + icono + texto).
- Paneles del menú solo con hover (CSS); cada padre es un enlace real a una página con los mismos destinos.
- Reproductor de radio simulado, sin autoplay; aparece solo tras pulsar reproducir. `?radio=aire` fuerza el estado «al aire» para demostrar.
