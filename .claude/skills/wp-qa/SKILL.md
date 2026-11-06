---
name: wp-qa
description: >
  QA adversarial de una página o plantilla de Elementor 4 (editor V4 atómico) construida por
  WP-CLI, antes de dársela por buena al usuario: fidelidad medida contra el prototipo a 1440,
  1280, 768 y 375 px, tipografía y color computados, clases globales sincronizadas con el editor,
  editabilidad real, hover y foco, contraste AA, accesibilidad, segundo idioma, formularios en
  seco, 404/login/mantenimiento por curl, consola y rendimiento básico. Úsala después de construir
  o modificar cualquier página, cabecera, pie o plantilla del Theme Builder, tras regenerar el
  segundo idioma o migrar, y siempre que alguien diga que "no quedó igual al prototipo", "no se
  ve en el editor", "el botón desaparece al pasar el ratón" o "el sticky no funciona".
---

# QA · páginas y plantillas Elementor 4

Guía de oficio, independiente de marca. La marca, el prototipo y los accesos viven en el
`CLAUDE.md` del proyecto y en su `spec-prototipo.md`; HomeMaker aparece solo como caso de
referencia. Esta skill existe porque los fallos de una réplica en Elementor 4 son **silenciosos**:
el script de construcción termina sin error, el HTML parece correcto y el defecto solo aparece al
medir, cuando el cliente pulsa «Publicar» en el editor o en el otro idioma. Los códigos M1–M19
remiten a `references/mediciones.md`.

## Lo primero, siempre

1. **Orden de clases frontend = preview.** Las clases creadas por script solo existen para la web
   hasta que se sincroniza el contexto «preview»; la primera publicación desde el editor las borra
   **de todo el sitio**. Es el único fallo que destruye trabajo (también el de otras páginas): se
   mira antes que nada ([`qa-orden-clases.php`](scripts/qa-orden-clases.php), M11). El arreglo, `e4-sync-preview.php` de
   `elementor4-atomico`, escribe: lo ejecuta quien construye, no el QA.
2. **Mides lo último y sin sesión.** `?ver=` del child = `filemtime`, caché purgada, sin cookie de
   idioma, cache-bust con `?nocache=` (nunca `?m=`), sin barra de administración. Si la cortina está
   encendida, no la apagas: mides con `render.php` por WP-CLI o con la cookie de la llave (M1, M17).
3. **Tienes la spec y la lista «conocido y aceptado».** La spec son medidas **computadas** del
   prototipo por ancho; la lista, las decisiones deliberadas (breakpoints de Elementor, añadidos
   pactados, defectos del prototipo que no se copiaron). Sin ellas no sabes qué es un fallo.

## La regla de oro

**Mide, no mires.** Una medida vale cuando sale de `getComputedStyle`/`getBoundingClientRect`, de
curl sobre el HTML servido o de una sonda de solo lectura sobre los datos, nunca de leer el código.
Un control «funciona» cuando cambiar su valor cambia el computado. Un contraste «pasa» cuando
calculaste el ratio contra el fondo real. Lo que no pudiste medir se entrega como **no verificado**.

## Los fallos recurrentes

### 1. Clases que el editor borrará
`Global_Classes_Repository::put()` escribe solo el contexto en el que corre (por CLI, el orden de
frontend: `_elementor_global_classes_order`); el editor lee `_elementor_global_classes_order_preview`
y al publicar elimina lo que no ve.
*Verifica:* `iguales: sí` y ninguna clase del documento ausente en preview (M11, M12).

### 2. Botón invisible en hover
La variante hover de la clase solo cambia el fondo y el `a:hover` del kit (0,2,1) gana a
`.elementor .<clase>` (0,2,0): texto del color del fondo. En el caso de referencia afectó a todos
los botones verdes del sitio y solo lo cazó la revisión adversarial.
*Verifica:* toda variante hover declara fondo **y** color; contraste medido con el ratón real (M9).

### 3. Diez píxeles que nadie puso
Los contenedores atómicos traen estilos base: `padding:10px` (e-flexbox, e-div-block, e-grid),
`min-width:30px` (e-flexbox y e-div-block vacíos) y `width:100%`; `e-grid` trae además dos filas
predefinidas y una base que vuelve a aplicarse en móvil y pisa la variante de tablet.
*Verifica:* auditoría de residuos `total: 0` y variante móvil explícita en cada rejilla (M7).

### 4. Contenido a opacidad 0
Las entradas animadas dejan bloques en `opacity:0` con scroll rápido, sin JS o en pestaña oculta,
y axe (Lighthouse) no audita texto a opacidad 0: sus fallos de contraste no salen en PSI.
*Verifica:* `ocultos: 0` con movimiento reducido y scroll completo; pasada de accesibilidad con
`--force-prefers-reduced-motion` (M5, M10c).

### 5. Lo que el cliente no podrá editar
Estados ocultos por CSS que también se ocultan en el editor, cajas de icono vacías que el editor
pinta como zonas de soltar de 120 px, props que el parser rechaza o claves que se pierden al
guardar, y controles del panel muertos porque el CSS del child fija la misma propiedad con más
especificidad.
*Verifica:* `qa-editable.php` sin `ISSUE`, lo oculto colgado de `html.<PREFIJO>-js` y la prueba en
el editor pedida al usuario (M12).

### 6. El segundo idioma a medias
Cabecera, pie o 404 del idioma base en `/<IDIOMA_2>/`, enlaces absolutos que vuelven al base,
`aria-label` de `<nav>` y nombre del `<form>` sin traducir, alt en el idioma base.
*Verifica:* IDs de plantilla del idioma 2, restos del idioma base en texto y atributos, y medición
contra el prototipo en ese idioma (M14).

### 7. Foco y landmarks que Hello pisa
Un foco con `:where()` (especificidad 0) pierde contra el reset de Hello (0,3,0); Hello pinta
`#c36` en hover/foco de los `<button>`; «Elementor ancho completo» en una plantilla 404/single
produce dos `<main>`.
*Verifica:* Tab real con indicador visible, sin `#c36`, y un solo `<main id="content">` (M9b, M13).

## Trampas de método que ya costaron tiempo

- **Computado, no código.** En el caso de referencia, 60 usos de `font-600/700/800` del prototipo no
  existían en Tailwind v4 y caían a 400 en silencio; probar esa clase en la consola también da 400.
  La spec se hace con valores computados y se compara con valores computados.
- **`?m=` es una query var de WordPress** (archivo por fecha): la URL da 404 y parece un fallo de la
  página. Rompe la caché con `?nocache=` o `?v=`.
- **La caché dinámica de SiteGround** guarda el HTML con la URL versionada vieja del CSS: tras cada
  despliegue del child, `wp sg purge` (o la purga del hosting) y lee `x-proxy-cache`. Si el `?ver=`
  sigue viejo, busca *Child Theme Configurator* reescribiéndolo. La cookie `pll_language` impide
  cachear nada. Los 404 no se cachean.
- **Subagentes sin navegador compartido.** Las herramientas de navegador de la sesión abren pestañas
  que dejan oculta la del usuario (se pausan rAF e IntersectionObserver, capturas en blanco). Los
  revisores usan curl, sondas de solo lectura y Chrome headless propio (`qa-medir.mjs`).
- **Captura rara ≠ fallo.** Antes de «arreglar», confírmalo por JS (opacidad, `is-in`, `naturalWidth`,
  `elementFromPoint`). Tras emular móvil, el menú de Pro se queda en modo táctil: recarga en escritorio.
- **Con sesión no es lo mismo.** La barra de administración empuja cabecera y sticky 32/46 px.
  Pregunta al usuario si vio el fallo con sesión iniciada: casi siempre navega logueado.
- **UA de navegador** en todo curl a admin, login o hosting con antibot; **slugs absurdos** para el
  404 (`/product` redirige a `/products/`).
- **No toques la cortina ni la caché** para medir: otra sesión puede estar midiendo sin sesión.
- **El `[0,N]` del REST** del script de sincronización de HomeMaker era engañoso: fíate de la
  comparación de metas.
- **Una sola corrida de Lighthouse no decide nada**: mediana de 3, descartando red contaminada. La
  API de PSI sin clave da 429; con la cortina puesta, PSI mide la cortina.

## Recorrido

- Checklist ejecutable por letras (A preparación → M cierre): [references/checklist.md](references/checklist.md).
- Mediciones M1–M19 (fragmentos de consola, curl y greps; cómo se lee cada resultado; estado
  «probado en HomeMaker» o «generalizado») y enlaces a los scripts hermanos que el QA reutiliza
  (medición contra el prototipo, residuos atómicos, contraste sobre vídeo, formularios en seco,
  render, Lighthouse): [references/mediciones.md](references/mediciones.md).
- Herramientas de solo lectura como archivos (`qa-medir.mjs`, `qa-cmp.py`, `qa-hover-foco.mjs`,
  `qa-html.py`, `qa-es-en.py`, `qa-orden-clases.php`, `qa-editable.php`, `qa-avisos.php`), con
  instalación y uso: [scripts/README.md](scripts/README.md).
- Tolerancia canónica (la de `verificacion.md`, abajo): objetivo ≤1 px; se aceptan ≤3 px por
  sección solo en anchos intermedios (1100, 900, bordes de breakpoint); el idioma 2 frente al
  idioma base, ≤1 px.
- Protocolo general de verificación y revisión adversarial con workflows:
  [verificacion.md](../wp-elementor-replica/references/verificacion.md) (skill `wp-elementor-replica`).
- Por qué se rompe lo que se rompe (modelo de datos V4, trampas de CLI, Theme Builder, formularios,
  tema hijo): skill `elementor4-atomico`, sobre todo
  [gotchas-cli.md](../elementor4-atomico/references/gotchas-cli.md).
- Idiomas: skill `wp-polylang`. Rendimiento, SEO y accesibilidad a fondo: skill `wp-seo-rendimiento`.
  Verificación tras migrar: skill `wp-migracion`.
- Auditor listo para lanzar: agente `qa-elementor`.

## Qué entregas

Hallazgos ordenados por gravedad (crítico, alto, medio, bajo). Por cada uno: **qué falla** en una
frase, **la medición** (esperado contra real, con ancho, URL o comando), **la causa** concreta
(clase, selector y especificidad, prop, opción) y **el arreglo** acotado. Separa lo que rompió el
cambio auditado, lo que ya estaba roto y lo heredado del prototipo. Di explícitamente las
categorías sin hallazgos y marca **no verificado** lo que no pudiste medir, con el motivo.
