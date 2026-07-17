# Diagnóstico consolidado — Habla Deportes

**Proyecto:** Rediseño de habladeportes.co
**Fecha:** 2026-07-13
**Responsable:** `director-proyecto` (consolidación de las 4 auditorías de Fase 1)
**Insumos:** [01-UX/UI](01-ux-ui.md) · [02-Técnico/SEO](02-tecnico-seo.md) · [03-Arquitectura/Contenido](03-arquitectura-contenido.md) · [04-Competencia](04-competencia.md)

---

## 1. Veredicto en una frase

**Habla Deportes tiene el mejor activo del mercado local mal presentado:** un canal de YouTube fuerte (+265 videos) y un programa de radio con audiencia, atrapados en un sitio que no es un medio digital sino una *landing que redirige a YouTube*. El rediseño no es cosmético — es **construir el medio digital que nunca existió**.

Las 4 auditorías, hechas de forma independiente, llegaron al **mismo diagnóstico**. Eso da alta confianza.

---

## 2. El hallazgo raíz (del que cuelga todo lo demás)

**Detrás del sitio no hay un sitio.** Inspeccionando el WordPress real (API REST), el contenido propio es **inexistente**: categorías, posts y tags siguen siendo el **demo del theme, en inglés, de 2021** ("Greenland Unicorns", "When Chocolate was Medicine"). Hay una **tienda WooCommerce vacía y huérfana**, y la **radio solo existe como una frase en el footer**. La home entera es un carrusel de miniaturas de YouTube.

Esto tiene una lectura **positiva**: no hay que reorganizar un sitio complejo ni migrar años de contenido. **Partimos casi de cero y podemos hacerlo bien desde el principio.**

---

## 3. Los 5 problemas críticos (y qué gana el cliente al resolverlos)

| # | Problema (evidencia) | Qué le cuesta hoy al negocio | Qué gana con el rediseño |
|---|----------------------|------------------------------|--------------------------|
| **1** | **No hay medio, hay redirección a YouTube.** Todo el valor vive fuera del sitio. | El medio no controla su audiencia ni sus datos; depende 100% del algoritmo de YouTube. El sitio no retiene ni construye marca. | Casa propia que retiene, con el video jerarquizado (héroe + grilla) en vez del carrusel plano. |
| **2** | **No existe navegación ni arquitectura.** Menú = solo "Inicio". Sin secciones por equipo/torneo/tema. | El hincha de Junior o de la Selección no encuentra "su" contenido. Se pierde el tráfico de búsqueda por equipo, que es el más valioso. | Navegación real + **hub de Junior FC** y Selección. SEO por entidad y retención por interés. |
| **3** | **La radio —2º pilar— es invisible.** Sin reproductor ni "en vivo"; solo texto en el footer. | La mitad de la propuesta de valor no se puede consumir desde la web. | **Reproductor de radio sticky** con estado "AL AIRE" (L–V 10–12). Ningún competidor local lo hace bien. |
| **4** | **SEO técnico casi nulo.** Sin meta description, **cero** Open Graph, **cero** datos estructurados (JSON-LD), home sin H1/H2, `lang="en-US"` en sitio español. | Al compartir en WhatsApp/redes (canal natural del medio) no aparece título ni imagen → fuga directa de alcance. Google no entiende el sitio. | Alcance recuperado: previews ricos al compartir, elegibilidad para Google News y carrusel de videos. **Se resuelve casi gratis en el HTML.** |
| **5** | **Identidad secuestrada por el sponsor.** La paleta gira en torno al azul/dorado de Transelca; único acento el rojo de YouTube. Detalles que restan (ej. "YOTUBE" mal escrito, dirección vacía, contenido demo indexable). | El medio no tiene firma propia memorable; se ve como plantilla genérica. Baja credibilidad ante audiencia y **anunciantes**. | **Firma visual caribeña/rojiblanca propia** + módulo de sponsor digno y medible. Sube la percepción de calidad (clave para vender pauta y por recomendación). |

---

## 4. Plan de acción priorizado (impacto ÷ esfuerzo)

### 🟢 Quick wins — alto impacto, bajo esfuerzo (nacen resueltos en el prototipo HTML)
- `lang="es-CO"`, `<title>`/meta description, **Open Graph + Twitter Cards**, JSON-LD (`NewsArticle`/`VideoObject`) en cada plantilla.
- Estructura semántica: un `<h1>` por página, landmarks, skip-link, `alt` descriptivos, label en el buscador.
- Corregir textos y señales de credibilidad ("YouTube", dirección real o quitarla, sin contenido demo).
- Contraste AA, objetivos táctiles ≥44 px.

### 🟡 Núcleo del rediseño — alto impacto, esfuerzo medio (el corazón del prototipo)
- **Navegación real** (desktop + hamburguesa móvil) con Equipos ▾ / Torneos ▾.
- **Home como portada de actualidad**: hero destacado + video héroe + grilla + bloques por equipo + franja de radio.
- **Reproductor de radio "AL AIRE"** integrado.
- **Sistema de identidad visual propio** (dirección de arte caribeña) → `director-arte`.
- **Módulo de patrocinio** con narrativa ("Presentado por Transelca"), separado del AdSense intrusivo.
- **Hub de Junior FC** y plantilla de sección/nota/video/contacto-publicidad.

### 🔵 Se materializa en la implementación WordPress (Fase 2 post-aprobación)
- No arrastrar el theme `livecast` ni el page-loader WebGL (Three.js); desactivar WooCommerce.
- Plugin SEO (RankMath/Yoast) para escalar meta/schema; limpiar contenido demo.
- Reducir requests/plugins, endurecer seguridad (HSTS, CSP, ocultar versiones).
- Definir y poblar la arquitectura editorial real (taxonomías Equipos/Torneos/Temas).

---

## 5. Narrativa comercial (el "por qué" para el cliente)

> **Hoy Habla Deportes trabaja para YouTube. El rediseño hace que YouTube trabaje para Habla Deportes.**

Tres mensajes para la presentación:

1. **"Tienes oro mal exhibido."** El contenido (video + radio + foco en Junior/Selección) es bueno y ya existe. El problema no es producir más, es *presentarlo como un medio*. Mensaje positivo: no partimos de una crisis, partimos de un activo desaprovechado.

2. **"Cada vez que alguien comparte tu contenido, pierdes."** Sin Open Graph ni schema, cada enlace compartido en WhatsApp sale sin imagen ni título. Para un medio que vive de compartir, eso es alcance regalado. Se arregla en el rediseño.

3. **"Una marca propia vende más pauta."** Un medio con identidad caribeña fuerte, cercano a la hinchada del Junior, con inventario de patrocinio claro, es mucho más vendible a anunciantes que una plantilla con el logo del sponsor pegado. La calidad del sitio *es* el argumento comercial — y, por la modalidad por recomendación, también es nuestra mejor carta de presentación.

**Posicionamiento del rediseño:** no imitar a Win Sports/ESPN en volumen, sino ganar por **foco, cercanía con la hinchada del Junior y agilidad video-first**, con el minimalismo editorial de The Athletic como vara de calidad alcanzable.

---

## 6. Puente a la Fase 2

El diagnóstico entrega a la Fase 2 tres cosas listas:
- **Qué construir** → sitemap y 7 plantillas P0 definidas ([03](03-arquitectura-contenido.md)).
- **Con qué estándar** → checklist de SEO/a11y/rendimiento que el HTML debe cumplir ([02](02-tecnico-seo.md)).
- **Con qué inspiración** → moodboard de 7 referentes y dirección visual hipótesis para `director-arte` ([04](04-competencia.md)).

**Siguiente paso recomendado:** arrancar con **`director-arte`** para definir el sistema de diseño (dirección caribeña/rojiblanca, tipografía, color, componentes, tokens CSS), y sobre esa base **`desarrollador-frontend`** construye el prototipo navegable, empezando por la Home como pieza de venta.
