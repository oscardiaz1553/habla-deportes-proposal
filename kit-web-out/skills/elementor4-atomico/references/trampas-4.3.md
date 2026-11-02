# Trampas verificadas en Elementor 4.3.1 / Pro 4.3.0

Se suman a `gotchas-cli.md`. Salieron de una obra real (WooCommerce 11, YITH Booking, ACF Pro, Yoast, hosting
SiteGround) entre septiembre y octubre de 2026. Versión fijada: Elementor 4.3.1 con los experimentos V4 activos.

## Kit y CLI
1. Si escribes los breakpoints por meta y compilas en el mismo proceso, salen los valores viejos. Compila en un
   proceso nuevo (p. ej. una constante `E4_SOLO_COMPILAR=1` y una segunda llamada a `eval-file`).
2. `Global_Classes_Repository::all()` puede escribir migraciones: una sonda «de solo lectura» que la llame no lo es.
3. `qa-editable.php` da un ISSUE falso en CLI (`get_config()` falla en todo documento).
4. `get_posts(['post_type'=>'any'])` deja fuera `elementor_library`. Para recorrer documentos, SQL sobre
   `_elementor_data`.

## Elementos atómicos
5. Elementor descarta los atributos con valor vacío: todo `data-*` de enganche necesita un valor.
6. `e-image` ya pone `loading="lazy"`: si lo añades en `attrs`, sale duplicado.
7. Un `e-flexbox` absoluto con `left` y `right` se desborda porque `.e-con` trae `width:100%`: ponle `width:auto`.
8. `e-grid` trae `repeat(1,1fr)` en `base-mobile.css` (≤ breakpoint mobile). Una variante `mobile_extra` no llega a
   móvil: repítela en `mobile`.
9. En vistas no singulares (tienda, búsqueda, 404, archivos) se imprime una hoja de clases por documento (cabecera y
   luego pie). Una clase de sistema **nunca** se combina con otra que declare sus mismas propiedades: lo decide el
   orden de las hojas.
10. Las clases globales salen como `.elementor .clase` (0,2,0) y el CSS del tema hijo carga después: `.elementor .x`
    en el hijo gana sin `!important`, salvo contra el CSS por elemento de los widgets V3.

## Widgets V3 de Pro
11. Nav-menu de Pro: el clearfix `::after` del `ul` suma un hueco en flex (`content:none`);
    `.elementor-item::after` viene con `position:absolute` y `opacity:0`; los ítems llevan `white-space:nowrap`.
12. Formulario de Pro 4.3: el honeypot se pinta como `.elementor-field-type-text` con `display:none!important`; el
    grupo alinea el botón al centro, así que una fila campo + botón necesita `stretch`.
13. `backdrop-filter`, o un `animation … both` que acaba en `transform:none`, convierte la caja en bloque contenedor
    de sus `position:fixed` (un cajón lateral queda encerrado). Usa `backwards` y pon el desenfoque en un `::after`.
    Solo se ve **sin** `prefers-reduced-motion`: mide con `no-preference`.
14. `is_admin()` es true en `admin-ajax.php`: los filtros para el visitante necesitan `|| wp_doing_ajax()` (los
    fragmentos del carrito de Woo salen de ahí).
15. Pro y Hello no traen es_ES: Menu Cart y el enlace de salto salen en inglés si no se traducen desde el hijo.

## WooCommerce, YITH y ACF
16. Con un tema de bloques, la cabecera del Theme Builder no llega a Woo: usa Hello + hijo.
17. YITH Booking no hace abonos sin YITH Deposits, no tiene widget de Elementor y añade «Reservas» a Mi cuenta sin
    mirar ninguna opción. Su caché de franjas va por día UTC: ajusta la zona horaria antes de crear franjas.
18. Las etiquetas dinámicas de ACF en Pro no admiten repetidores: listas (jornadas, FAQ) por shortcode del hijo.
19. Caché de SiteGround: comprueba con `curl` GET, nunca con `-I` (HEAD y GET dan cabeceras distintas en el carrito).

## Rendimiento y servidor
20. El módulo de carga de imágenes de Elementor abre un búfer en `get_header` y trata la cabecera antes que el
    contenido: la primera `<img>` grande sin `loading` se lleva `fetchpriority="high"` (p. ej. una foto oculta del
    megamenú en vez del héroe). Pon en diferido las imágenes ocultas de la cabecera desde el hijo.
21. Con `serialize_precision = 100` (SiteGround), `json_encode` escribe ~50 cifras por decimal: redondea las
    coordenadas antes de codificar.
22. Un build vacía la caché de CSS de Elementor (`files_manager->clear_cache()`). Antes de medir, calienta el CSS de
    todos los documentos que pinte la página (cabecera, pie, loop items); si no, el render sale sin su hoja y parece
    una regresión.
