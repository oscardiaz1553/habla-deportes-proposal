# WordPress — Fase 3

Código de la implementación en WordPress del rediseño de Habla Deportes.
Fuente de verdad: **este repo**. Los sitios locales (app Studio) son copias
desplegadas.

## Qué hay aquí

| Carpeta | Qué es |
|---|---|
| `habla-deportes-core/` | **Plugin.** Funcionalidad pura, sin diseño: CPT `video` con importador automático del canal de YouTube y clasificador por título, CPT `partido` con su ficha (equipos, torneo, fecha, estadio, canal), taxonomías `equipo`/`torneo`/`tema` con siembra de términos, y campo de escudo por equipo. Es el que se instala en el sitio que se diseña con Elementor. |
| `hello-elementor-child/` | **Child theme** con el prototipo portado a plantillas PHP (home, single de video/nota, archivos, header/footer con megamenús). Fue la prueba "todo en código"; vive en el sitio de laboratorio. |
| `elementor-kit-habla-deportes.zip` | Kit de Elementor importable: colores y tipografías de marca. Se sube en Elementor → Herramientas → Importar, marcando "Ajustes del sitio". **No importar dos veces** (los colores personalizados se acumulan). |
| `elementor-kit-INSTRUCCIONES.md` | Tabla de valores por si se prefiere cargar el kit a mano. |

## Dos sitios locales

- **Laboratorio** — pruebas, con el child theme en código. Se puede romper sin consecuencias.
- **Final** — el que se entrega: Hello Elementor + Elementor + plugin Core, diseñado visualmente.

## Imágenes no versionadas

Para no duplicar 40 MB, estas imágenes **no** están en git (son copias exactas
de `propuesta/html/assets/img/`):

- `hello-elementor-child/assets/proto/img/`
- `habla-deportes-core/assets/img/partido-*.png`

Los escudos del plugin (`escudo-*.png`, ~770 KB) **sí** están versionados: son
el fallback que necesita el plugin para funcionar por sí solo.

### Restaurarlas

```bash
# Imagenes del child theme (copia directa del prototipo)
cp -r propuesta/html/assets/img/* wordpress/hello-elementor-child/assets/proto/img/

# Fondos de los partidos demo del plugin (renombrados a ASCII)
cp propuesta/html/assets/img/junior_millonarios.png wordpress/habla-deportes-core/assets/img/partido-junior-millonarios.png
cp propuesta/html/assets/img/junior_nacional.png    wordpress/habla-deportes-core/assets/img/partido-junior-nacional.png
cp propuesta/html/assets/img/colombia_peru.png      wordpress/habla-deportes-core/assets/img/partido-colombia-peru.png
```

## Desplegar a un sitio local

```bash
# Plugin al sitio final
cp -r wordpress/habla-deportes-core "<ruta-del-sitio>/wp-content/plugins/"

# Child theme al laboratorio
cp -r wordpress/hello-elementor-child "<ruta-del-sitio>/wp-content/themes/"
```

## Notas

- El importador de YouTube usa el feed RSS público del canal (sin API key) y
  corre cada hora por WP-Cron. También se puede forzar desde
  **Videos → Importar de YouTube**.
- El clasificador solo **añade** términos por coincidencia en el título; nunca
  borra los que ponga el editor a mano. Puede generar falsos positivos
  (ej. "Copa Colombia" cae en Selección) — se corrigen editando el video.
