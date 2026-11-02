# Trampas de Elementor 4 por WP-CLI

Todas las trampas que costaron tiempo al construir el caso de referencia por
`wp eval-file`, con su síntoma, causa, arreglo y cómo detectarlas. Verificadas con
Elementor 4.3.0, Elementor Pro 4.2.3, Polylang Pro 3.8.6, Hello Elementor 3.5.1 y
WordPress 7.1.2. El modelo de datos que las explica está en
`references/modelo-de-datos.md`.

Grupos: **A** ejecución · **B** guardado · **C** clases y variables · **D** contenedores
· **E** elementos · **K** kit y breakpoints · **V** widgets V3 y menús · **X** contenido
dinámico · **T** Theme Builder · **H** caché, hijo y versiones · **M** medición ·
**P** después de migrar · **O** índice de trampas de CLI fuera de Elementor.

## Buscar por mensaje de error

| Mensaje | Trampa |
|---|---|
| `Parse error: syntax error, unexpected token "\"" … eval()'d code` | A1 |
| Errores de sintaxis raros en PHP generado desde el shell (`= ->all( true )`) | A2 |
| La salida es CSS o HTML de «Ha habido un error crítico» | A3 |
| `Warning: foreach() argument must be of type array\|object, null given` al guardar; nada persiste | B1 |
| `save() falló` en una plantilla loop-item que sí se guardó | B1 |
| `TypeError: Controls_Stack::sanitize_settings(): Argument #1 ($settings) must be of type array, null given` | B3 |
| `Undefined array key "id"` | B4 |
| `Settings validation failed. tag: invalid_value` | D12 |
| `Settings validation failed. source: invalid_value` | E3 |
| `Settings validation failed. title: invalid_value` / `image: invalid_value` (props dinámicas) | X1 |
| `Control must have name, section, label, and default` / `Select control must have options` | X1 |
| `Invalid image URL.` y un `<img>` sin `src` | X3 |
| `Fatal error: in_array(): Argument #2 ($haystack) must be of type array, Elementor\Core\Utils\Collection given` | C3 |
| `ERROR items.<clase>.variants[0].<prop> -> invalid_value` | C5 |
| `items.<clase>.label -> class_name_too_short` | C6 |
| `Fatal error: Cannot unset string offsets … conditions-manager.php` | T1 |
| `E_COMPILE_ERROR` que tumba todo el sitio tras actualizar Elementor | H4 |
| `Warning: Unable to Purge File Cache` al purgar | H2 (inocuo) |
| `Warning: Undefined variable $…` y un informe vacío | A13 |

---

## A. Ejecución por WP-CLI y shell

### A1 · `wp eval '…'` en línea por SSH rompe las comillas
- **Síntoma:** `Parse error: syntax error, unexpected token "\"" … eval()'d code` y una página HTML de error.
- **Causa:** tres capas de comillas (shell local → ssh → shell remoto → PHP).
- **Arreglo:** cada operación es un archivo PHP: se escribe en local, `php -l`, `scp` a un directorio propio fuera del webroot (`~/tmp-<PREFIJO>/`) y `ssh <SSH_ALIAS> 'wp --path=<WP_PATH> --user=1 eval-file ~/tmp-<PREFIJO>/x.php'`.
- **Detecta:** cada script termina imprimiendo un resumen; si no sale la última línea, no terminó.

### A2 · Los heredocs sin comillas destrozan el PHP generado
- **Síntoma:** errores de sintaxis como `unexpected token "\"` o `= ->all( true )` en un PHP escrito desde el shell o desde Python.
- **Causa:** un heredoc sin comillas (`<<PY`) expande `$o`, `$new`, `$repo`… del PHP.
- **Arreglo:** heredocs siempre con comillas (`<<'PHP'`, `<<'PY'`) y las rutas por argumento (`python3 - "$S/archivo" <<'PY'` con `sys.argv[1]`). `php -l` y `node --check` antes de cada `scp`.
- **Detecta:** `php -l` limpio antes de desplegar.

### A3 · Los fatales se esconden detrás de la página de «error crítico»
- **Síntoma:** `wp eval-file x.php | tail -40` solo enseña CSS de la página de error de WordPress.
- **Causa:** el manejador de fatales de WordPress imprime su HTML en stdout y el `Fatal error: Uncaught …` queda arriba.
- **Arreglo:** redirige siempre a archivo y busca el error: `wp eval-file x.php --user=1 > ~/tmp-<PREFIJO>/salida.txt 2>&1; grep -i -m3 "fatal\|uncaught" ~/tmp-<PREFIJO>/salida.txt | cut -c1-300; tail -7 ~/tmp-<PREFIJO>/salida.txt`. Para salidas cortas, filtra el ruido con `| grep -vE $'^<|^\t'` (líneas de HTML y de CSS tabulado).
- **Detecta:** aparece la línea `Fatal error: Uncaught …` con su stack.

### A4 · Guardar sin usuario no persiste nada
Ver B1. Todo `eval-file` que guarde documentos, plantillas, clases o el kit lleva `--user=1`.

### A5 · `eval-file` ejecuta el archivo en un ámbito local
- **Síntoma:** helpers que no se pueden cargar en varios scripts sin redeclarar funciones; rutas a helpers que se quedan obsoletas.
- **Causa:** `eval-file` no ejecuta en el ámbito global.
- **Arreglo:** helpers como closures en variables (`$flex`, `$h`…), cargados con `require __DIR__ . '/e4-helpers.php'`, con `use` o funciones flecha. Nada de rutas fijas a `/tmp` del servidor (un builder antiguo con `require '/tmp/…'` quedó apuntando a una ruta obsoleta). Los argumentos de la línea de comandos llegan en `$args` (`wp eval-file dump.php "a,b"` → `$args[0]`). `const X = …` funciona en el nivel superior.
- **Detecta:** `php -l` en local y el resumen final del script.

### A6 · Borrados encadenados que nadie leyó
- **Síntoma:** un `ssh "verificar; wp option update …; wp post delete 1 --force; …"` cuya salida quedó tapada por una página de error; los borrados se ejecutaron sin leerse. El clasificador de permisos, además, bloquea los `rm` encadenados.
- **Causa:** lectura y escritura destructiva en el mismo comando, sin comprobación intermedia.
- **Arreglo:** una operación destructiva = un comando propio, después de leer el resultado del anterior. Nada de `--force` en lote. Deja los temporales remotos y avisa al usuario para que los limpie él. Si algo se ejecutó sin leerse, compruébalo y dilo en el informe.
- **Detecta:** el informe al usuario incluye un «aviso» cuando pasó algo así.

### A7 · Las URL calculadas en CLI salen en `http://`
- **Síntoma:** URLs `http://…` guardadas en `_elementor_data` (SVG, enlaces): el editor, en https, las bloquea como contenido mixto; el frontend hace una petición loopback en vez de leer el disco.
- **Causa:** bajo wp-cli `is_ssl()` es falso y `get_stylesheet_directory_uri()` devuelve `http://` aunque `home` y `siteurl` sean https.
- **Arreglo:** toda URL absoluta que se vaya a guardar pasa por `set_url_scheme( $url, 'https' )` (o `$_SERVER['HTTPS'] = 'on'` al principio del script).
- **Detecta:** `wp db query` sobre `<DB_PREFIX>postmeta` con `meta_key='_elementor_data'` buscando `http:` y el dominio (en el JSON las barras van escapadas: `http:\/\/`) → ninguna fila.

### A8 · Elementor cachea dentro del proceso
- **Síntoma:** tras cambiar breakpoints o variables, la relectura en el mismo script dice que no cambió (o que sí, cuando no persistió).
- **Causa:** el gestor de breakpoints y la lista de variables se cachean en el proceso.
- **Arreglo:** verifica en un `eval-file` aparte.
- **Detecta:** `get_active_breakpoints()` o `get_variables_list()` en un proceso nuevo.

### A9 · Polylang en CLI: `PLL_Admin`, sin idioma y sin filtro
- **Síntoma:** búsquedas de idempotencia que en CLI devuelven todos los idiomas y en ajax o frontend solo uno; entradas nuevas que nacen con el idioma por defecto.
- **Causa:** en WP-CLI `PLL()` es `PLL_Admin`, no hay `curlang` y `--user` se aplica después de que Polylang lea el filtro de idioma del usuario. En frontend y ajax, Polylang sí filtra cualquier consulta de tipos traducidos (engancha `parse_query`, que `suppress_filters` no evita).
- **Arreglo:** pasa siempre `'lang' => ''` (o el idioma) explícito en `get_posts` y `get_terms`, y fija el idioma de todo lo que crees (`pll_set_post_language`, `pll_insert_post`). Detalle en la skill `wp-polylang`.
- **Detecta:** re-ejecuta el script: dice «reutilizo #ID» y el número de posts no crece.

### A10 · Los subcomandos de Elementor van con guion bajo
- **Síntoma:** dudas entre `flush-css` y `flush_css` (las notas del caso de referencia usan las dos).
- **Causa:** `wp help elementor` no lista los subcomandos en esta instalación.
- **Arreglo:** usa las formas con guion bajo, las verificadas para los tres: `wp elementor flush_css`, `wp elementor replace_urls <viejo> <nuevo> --force` y `wp elementor-pro theme-builder clear_conditions` (respuestas: «Flushed the Elementor CSS Cache», «Template conditions cache is cleared.»). `flush-css` con guion también funcionó en el caso (misma respuesta); `replace-urls` y `clear-conditions` con guion no se probaron.
- **Detecta:** `wp elementor flush_css --help >/dev/null && echo OK`.

### A11 · zsh expande `?` y `*` en URLs y patrones
- **Síntoma:** `no matches found` o un curl a una URL distinta de la escrita.
- **Causa:** globbing de zsh.
- **Arreglo:** entrecomilla siempre las URL con query y los patrones.
- **Detecta:** el comando falla antes de ejecutarse.

### A12 · En CLI no están todos los controles de un widget V3
- **Síntoma:** `get_widget_types( 'nav-menu' )->get_controls()` solo trae los de contenido; `$c['options']` de `dropdown` sale indefinido; 252 controles todos etiquetados `content`.
- **Causa:** Elementor registra algunos controles solo en el editor, y en CLI oculta los de estilo y las copias responsive.
- **Arreglo:** `Plugin::$instance->editor->set_edit_mode( true )` e instanciar el widget (sonda en `references/modelo-de-datos.md` §1). No valides claves V3 con `get_controls()` en CLI: usa un JSON de settings probado y lee la plantilla CSS o el PHP del widget.
- **Detecta:** la lista de opciones sale completa.

### A13 · `"…«$var»…"` se traga la variable
- **Síntoma:** un informe imprime vacío y sale `Warning: Undefined variable $…`, aunque la escritura sí se hizo.
- **Causa:** PHP admite bytes 0x80–0xff en los identificadores: en `"original: «$es»"`, `$es»` es la variable `es»` (el `»` es `C2 BB` en UTF-8).
- **Arreglo:** llaves cuando la variable va pegada a un carácter no ASCII (`«{$es}»`) o `sprintf`. Un informe vacío no significa que la escritura fallara: compruébalo aparte.
- **Detecta:** `php -l` no lo ve; busca «Undefined variable» en la salida de `wp eval-file`.

---

## B. Guardar documentos

### B1 · `save()` sin usuario devuelve `false` en silencio; `Loop::save()` devuelve `null` aunque guarde
- **Síntoma:** nada persiste, sin error (a veces `Warning: foreach() argument must be of type array|object, null given`); el término `elementor_library_type` no se asigna. Al revés, un script da por fallida una plantilla loop-item que sí se guardó.
- **Causa:** `Document::save()` comprueba la capacidad del usuario actual (en CLI no hay). Cada tipo de documento devuelve algo distinto.
- **Arreglo:** `wp eval-file x.php --user=1` siempre. Comprueba con `if ( false === $doc->save( $data ) )`, nunca con `! $doc->save()`. Relee `_elementor_data` e imprime el árbol o los recuentos.
- **Detecta:** el resumen «documento #ID — secciones: N · elementos: M» que imprime `$e4_guardar` tras cada guardado.

### B2 · Los ajustes del documento reemplazan la meta entera
- **Síntoma:** un ajuste de página enviado solo borró los demás; aparece una clave `0` basura.
- **Causa:** `save_settings_to_db` reemplaza `_elementor_page_settings` entera. Con la meta vacía, `get_meta` devuelve `''` y `(array) ''` es `[0 => '']`.
- **Arreglo:** `$prev = $doc->get_meta( '_elementor_page_settings' ); $prev = is_array( $prev ) ? $prev : []; unset( $prev[0] ); $data['settings'] = array_merge( $prev, $nuevos );`
- **Detecta:** vuelca la meta después del guardado.

### B3 · Una referencia PHP crea la clave con `null`
- **Síntoma:** `TypeError: Controls_Stack::sanitize_settings(): Argument #1 ($settings) must be of type array, null given` al guardar tras un script de ajuste.
- **Causa:** `$s = &$el['settings']` **crea** `settings` con `null` en los elementos que no lo tienen.
- **Arreglo:** en todo walker que modifique el árbol, `isset( $el['settings'] ) && is_array( $el['settings'] )` antes de tomar la referencia; si no, recorre los hijos y `continue`. `unset( $ref )` tras cada referencia.
- **Detecta:** `json_decode` de la meta y busca `"settings":null`.

### B4 · Lo que devuelven los builders de Elementor no trae `id`
- **Síntoma:** `Undefined array key "id"` al guardar (vídeo de fondo con controles).
- **Causa:** `Element_Builder::make()/generate()->build()` y `build_default_element()` construyen como el editor, donde el id lo pone el navegador.
- **Arreglo:** pasa esos árboles por un `$con_ids` recursivo (`scripts/e4-helpers.php`) (id de 7 caracteres + `'version' => '0.4'`).
- **Detecta:** relee el JSON y confirma que ningún elemento queda sin `id`.

### B5 · Ids aleatorios rompen referencias y re-ejecuciones
- **Síntoma:** al re-ejecutar un builder, el filtro deja de apuntar a su rejilla, las pruebas no encuentran el formulario y los mapas de traducción por id dejan de valer.
- **Causa:** ids nuevos en cada ejecución.
- **Arreglo:** ids deterministas `substr( md5( $E4_SEED . '-' . ( ++$__n ) ), 0, 7 )` con semilla por documento; ids fijos legibles de 7 caracteres `[a-z0-9]` para los elementos referenciados (modelo-de-datos §4).
- **Detecta:** tras re-ejecutar, la relectura muestra los mismos ids.

### B6 · `save()` pinta el documento, y en CLI sin idioma
- **Síntoma:** el `og:description` de una página en el segundo idioma sale con términos del idioma base.
- **Causa:** al guardar, Elementor renderiza el documento para generar el texto plano (`post_content`, que usa Yoast). En CLI no hay idioma actual y las consultas del render salen en el idioma por defecto.
- **Arreglo:** `$antes = PLL()->curlang; PLL()->curlang = PLL()->model->get_language( $lang ); /* save */ PLL()->curlang = $antes;`
- **Detecta:** `wp post get <ID> --field=post_content` en el idioma correcto.

### B7 · Escribir `_elementor_data` a mano se salta todo
- **Síntoma:** documentos con props inválidas que el editor no abre, o sin CSS ni texto plano regenerados.
- **Causa:** la meta en bruto no pasa por validación, saneado ni regeneración.
- **Arreglo:** `Plugin::$instance->documents->get( $id, false )->save( [ 'elements' => …, 'settings' => … ] )` siempre; la escritura directa solo como último recurso consciente.
- **Detecta:** revisión de editabilidad: valida cada `_elementor_data` con los parsers en modo editor.

---

## C. Clases globales y variables

### C1 · CRÍTICO · El editor borra las clases creadas por CLI al publicar
- **Síntoma:** al publicar cualquier página desde el editor, desaparecen del sitio clases creadas por script y se quitan de los documentos (en el caso de referencia, 44 clases en riesgo detectadas por una revisión adversarial).
- **Causa:** `Global_Classes_Repository::put()` solo escribe el orden del contexto frontend (`_elementor_global_classes_order`). El editor lee `_elementor_global_classes_order_preview` y borra (`wp_delete_post` forzado) lo que no ve allí.
- **Arreglo:** al final de **todo** script que toque clases: `Global_Classes_Order::make( $kit )->set_preview( true )->set_order( array_merge( $front, $solo_preview ) )` + `files_manager->clear_cache()` (código en modelo-de-datos §7), cargado con `require __DIR__ . '/e4-sync-preview.php';` al final del propio script.
- **Detecta:** metas de orden frontend y preview iguales y con el mismo recuento que los posts `e_global_class`; REST `GET /elementor/v1/global-classes?context=preview` con el mismo número de items que `context=frontend`; `…/global-classes/styles?context=preview&ids=…` sin `null`.

### C2 · `put()` no es un upsert
- **Síntoma:** un script de clases antiguo, re-ejecutado, entregaría 8 clases como conjunto completo frente a las 243 del sitio: las otras 235 desaparecerían.
- **Causa:** `put( $items, $order )` recibe el conjunto entero y reemplaza el orden entero. En contexto frontend borra los posts de las clases que falten en `$items` y dispara `elementor/global_classes/cleanup`, que las quita de los documentos (código de `global-classes-repository.php`, Elementor 4.3.0).
- **Arreglo:** siempre `all( true )` → fusionar → validar → `put` → `clear_cache` → sincronizar preview (`scripts/e4-clases-upsert.plantilla.php`). Marca en la cabecera los scripts que no fusionan o retíralos.
- **Detecta:** imprime el número de clases antes y después: no baja salvo que lo pretendas.

### C3 · El repositorio devuelve `Collection`, no arrays
- **Síntoma:** `in_array(): Argument #2 ($haystack) must be of type array, Elementor\Core\Utils\Collection given`.
- **Causa:** `get_items()` y `get_order()` devuelven `Elementor\Core\Utils\Collection`.
- **Arreglo:** `$all = $repo->all( true ); $items = $all->get_items()->all(); $order = $all->get_order()->all();`
- **Detecta:** el fatal desaparece.

### C4 · Entre clases globales gana la PRIMERA del orden
- **Síntoma:** los modificadores (`--sm`, `--fin`, `centrado`) no pisan a su base; más tarde, una clase de texto nueva con `margin:0` anula las utilidades `*-mt-*`, más antiguas.
- **Causa:** si dos clases de un elemento escriben la misma propiedad, gana la que va antes en el orden global, no la última del atributo `class`. Los scripts anteponen las nuevas, así que la prioridad depende del historial.
- **Arreglo:** una propiedad por familia de clases (tipografía sin color ni margen); modificadores explícitamente delante de su base; para un caso concreto, un modificador nuevo en vez de tocar la base (modelo-de-datos §7).
- **Detecta:** `printf` de `array_search` de cada par crítico tras cada script; `getComputedStyle` en el navegador.

### C5 · Valida con el parser antes de escribir
- **Síntoma:** un guardado falla o descarta una prop sin decir cuál.
- **Causa:** cada prop tiene su `$$type` y su forma.
- **Arreglo:** `Global_Classes_Parser::make()->parse( [ 'items' => $items, 'order' => $order ] )`; si `! is_valid()`, imprime `errors()->all()` (`key -> error`) y sal sin escribir. Ante un tipo nuevo, sonda de `Style_Schema` (modelo-de-datos §1).
- **Detecta:** `ERROR items.<clase>.variants[0].<prop> -> invalid_value` señala la prop exacta.

### C6 · Etiqueta de clase demasiado corta
- **Síntoma:** `items.x.label -> class_name_too_short`.
- **Causa:** validación de longitud mínima del nombre.
- **Arreglo:** prefijo de proyecto y nombres de 3 o más caracteres, también en pruebas.
- **Detecta:** parser válido.

### C7 · Un `0` en una variante responsive se descarta
- **Síntoma:** en tableta y móvil el `order` de la variante no se aplica.
- **Causa:** `order: 0` se trata como vacío (falso) y no se emite en `global-<id>-frontend-tablet.css`.
- **Arreglo:** usa `-1` u otro valor equivalente. Tras cada script con variantes, lee la hoja del breakpoint.
- **Detecta:** `curl -s "https://<DOMINIO>/wp-content/uploads/elementor/css/global-<id>-frontend-tablet.css?nocache=$(date +%s)" | tr '}' '\n' | grep '<PREFIJO>-x'`.

### C8 · `text-align` solo admite valores lógicos
- **Síntoma:** `ERROR items.<clase>.variants[0].text-align -> invalid_value`.
- **Causa:** el esquema de estilos V4 es lógico: `left` y `right` no son válidos.
- **Arreglo:** `start`, `end`, `center`; y propiedades lógicas en general (`inset-inline-start`, `inset-block-start`, `block-start`…).
- **Detecta:** parser válido.

### C9 · Una clase global solo llega al CSS de la página si la usa un elemento V4 de ese documento
- **Síntoma:** clases puestas en `_css_classes` de un Loop Grid, filtro, shortcode o formulario no hacen nada; la regla no existe en `document.styleSheets`.
- **Causa:** `global-<id>-frontend-<breakpoint>.css` se genera con las clases que referencian los elementos atómicos del documento; `_css_classes` de un V3 no cuenta.
- **Arreglo:** envuelve el widget V3 en un `e-flexbox` con una clase «caja» y la utilidad de margen; al V3, solo una clase propia para el CSS del hijo (modelo-de-datos §9).
- **Detecta:** `curl -s …/global-<id>-frontend-desktop.css | tr '}' '\n' | grep <clase>`.

### C10 · Hover que vuelve invisible el texto; enlaces del kit que pisan componentes
- **Síntoma:** el texto de los botones verdes desaparece al pasar el ratón (verde sobre verde); un «EN» inactivo del conmutador sale verde sobre verde; en el panel móvil, las píldoras de idioma crecen al hover.
- **Causa:** la variante hover de la clase solo cambiaba el fondo y `.elementor-kit-<id> a:hover{color}` (0,2,1) gana a `.elementor .<clase>` (0,2,0); `.elementor-kit-<id> a` gana a una clase simple del hijo; el `:hover/:focus` de los enlaces del nav-menu aplica `padding:10px 20px` a todo enlace del panel.
- **Arreglo:** toda variante `hover` de un botón o enlace declara **fondo y color** (la variante compila a `:hover, :focus-visible`). Los componentes del hijo dentro de zonas de Elementor usan dos niveles de clase y fijan también sus estados (padding, line-height, color). Revisa los colores de enlace del kit antes de crear botones.
- **Detecta:** fuerza el hover y mide color y contraste; en la hoja global debe verse `.elementor .<clase>:hover, .elementor .<clase>:focus-visible { color: …; background-color: … }`.

### C11 · Variables V4: etiquetas sin espacios, id en vez de etiqueta, creación no idempotente
- **Síntoma:** `Variable::validate` rechaza la etiqueta; re-ejecutar el script de variables da errores; una clase con variable no valida.
- **Causa:** las etiquetas no admiten espacios y deben ser únicas; `Variables_Service::create()` falla si ya existe; `global-color-variable` pide el **id**, no la etiqueta.
- **Arreglo:** kebab-case; «crear si falta» indexando por etiqueta (`scripts/e4-variables.plantilla.php`); resuelve siempre etiqueta → id con `get_variables_list()` (modelo-de-datos §8). Mantén también los globals V3 del kit para los widgets V3.
- **Detecta:** recuento por `type` en un proceso nuevo.

### C12 · Arreglos que tocan `variants[0]` por índice
- **Síntoma:** un script de arreglo modifica la variante equivocada.
- **Causa:** asumen que `variants[0]` es desktop sin estado.
- **Arreglo:** busca la variante por `meta` (`breakpoint` y `state`) y comprueba antes si el cambio ya está (idempotencia).
- **Detecta:** vuelca la clase después del arreglo.

### C13 · Cambiar o retirar una clase base es global
- **Síntoma:** quitar el color de una clase de tipografía cambia todos sus usos; una clase retirada del repositorio deja documentos sin estilo sin avisar.
- **Causa:** las clases son compartidas y los documentos guardan solo su nombre.
- **Arreglo:** antes de cambiar la semántica de una base, lista sus usos; antes de retirar una clase, búscala en todos los `_elementor_data`. Antes de construir, compara las clases que usa el build con las del repositorio (modelo-de-datos §7, «Sondas útiles»).
- **Detecta:** la sonda de «faltan: …» vacía.

### C14 · La comprobación REST del orden devuelve 0
- **Síntoma:** el script de sincronización imprime siempre `REST preview [0,N]`.
- **Causa:** la respuesta no trae `meta.order` ni `order` en la forma que esperaba el script.
- **Arreglo:** fíate del recuento de items por REST y de la comparación de las metas del kit.
- **Detecta:** `frontend N · preview N · iguales: sí` leyendo las metas.

---

## D. Contenedores y maquetación

### D1 · Los contenedores atómicos nacen con `padding:10px`
- **Síntoma:** bloques 20px más altos que el prototipo; cabecera que mide de más.
- **Causa:** `.elementor .e-flexbox-base` (0,2,0), `.e-div-block-base`, `.e-grid-base` y `.e-background-video-content-base` traen `padding:10px`.
- **Arreglo:** toda clase de contenedor fija `padding` explícito (0 si no hay).
- **Detecta:** auditoría JS de 10px (modelo-de-datos §12) → `total:0` en cada página, cabecera y pie incluidos.

### D2 · Cajas vacías con `min-width:30px`
- **Síntoma:** iconos de 16px que ocupan 30 de ancho; pines de 30px en vez de 36.
- **Causa:** `e-div-block` y `e-flexbox` vacíos traen `min-width:30px`.
- **Arreglo:** `min-width:0` (y `min-height:0`) y `padding:0` en toda caja vacía.
- **Detecta:** `getComputedStyle( el ).minWidth`.

### D3 · `width:100%` de `.e-con`
- **Síntoma:** marca y acciones de la cabecera estiradas.
- **Causa:** `.e-con` da `width:100%`.
- **Arreglo:** `width:auto` en la clase si el bloque debe ir a su tamaño; con widgets V3 en fila, `_element_width => 'auto'` (V1).
- **Detecta:** mide los anchos contra el spec.

### D4 · `e-grid` trae dos filas predefinidas
- **Síntoma:** en el pie, la rejilla reserva la mitad del alto para una fila vacía.
- **Causa:** la base de `e-grid` define 3 columnas × 2 filas y `gap:20px`.
- **Arreglo:** `grid-template-rows: auto` y `gap` explícitos en toda clase de rejilla.
- **Detecta:** `getComputedStyle( grid ).gridTemplateRows`.

### D5 · `e-grid` vuelve a varias columnas en móvil
- **Síntoma:** la variante tablet dice una columna, pero en móvil la rejilla vuelve a varias.
- **Causa:** la base de `e-grid` se reaplica en el breakpoint móvil y pisa lo heredado de tablet (observado; no se aisló en el código).
- **Arreglo:** declara siempre la variante `mobile` de `grid-template-columns`, con `minmax(0, 1fr)` (no `1fr`, que desborda).
- **Detecta:** `getComputedStyle( grid ).gridTemplateColumns` a 375px.

### D6 · El `::before` de `.e-con` es absoluto
- **Síntoma:** iconos pintados con `::before` que salen arriba a la izquierda aunque el padre centre.
- **Causa:** en los contenedores atómicos el `::before` es la capa de fondo: `position:absolute; top:0; left:0`.
- **Arreglo:** `position:absolute; inset:0; margin:auto; width:16px; height:16px` en el icono.
- **Detecta:** `getComputedStyle( el, '::before' )`.

### D7 · Un hijo absoluto se posiciona respecto al `.e-con` más cercano
- **Síntoma:** una ficha absoluta se coloca mal.
- **Causa:** `.e-con` lleva `position:relative`.
- **Arreglo:** `position: static` en los contenedores intermedios si debe referirse a un ancestro más lejano.
- **Detecta:** mide `getBoundingClientRect`.

### D8 · Ocultar los hijos deja el hueco del contenedor
- **Síntoma:** al ocultar el CTA y el idioma uno a uno, el contenedor de acciones sigue ocupando su gap y su ancho.
- **Causa:** el contenedor vacío conserva gap y ancho.
- **Arreglo:** la clase de visibilidad va en el contenedor, no en cada hijo (ni en `classes` V4 ni en `_css_classes` V3 de los hijos).
- **Detecta:** mide la fila a ambos lados del corte (p. ej. 1279 y 1280).

### D9 · Un `sticky` que cambia de `position` conserva su desplazamiento
- **Síntoma:** en móvil, la tarjeta que en escritorio era sticky queda desplazada.
- **Causa:** la variante móvil cambió `position` a `relative` pero conservó `inset-block-start`.
- **Arreglo:** en la variante, `inset-block-start` = `['$$type'=>'size','value'=>['unit'=>'auto','size'=>'']]`.
- **Detecta:** `getComputedStyle( el ).top` a 375.

### D10 · `dimensions` con solo `inline-*` no toca el vertical
- **Síntoma:** después de «quitar el padding», siguen 10px arriba y abajo.
- **Causa:** las claves ausentes no se sobrescriben: queda la base.
- **Arreglo:** declara las cuatro claves (`block-start`, `block-end`, `inline-start`, `inline-end`).
- **Detecta:** auditoría de 10px.

### D11 · En el editor, las cajas vacías se ven como zonas de soltar de 120px
- **Síntoma:** pines e iconos aparecen en el editor como huecos enormes.
- **Causa:** Elementor inserta `.elementor-empty-view` en los contenedores atómicos vacíos.
- **Arreglo:** marca esas cajas con un atributo (`data-<PREFIJO>-icono`) y en el hijo: `.e-atomic-element[data-<PREFIJO>-icono]:has(> .elementor-empty-view){min-height:0!important}` y `.e-atomic-element[data-<PREFIJO>-icono] > .elementor-empty-view{display:none!important}`.
- **Detecta:** abrir la página en el editor (prueba del usuario si Claude no puede iniciar sesión).

### D12 · Enum de `tag` por elemento
- **Síntoma:** `Settings validation failed. tag: invalid_value` (con `dl` para una tabla, `nav` en un `e-grid`, `main` en un `e-background-video`).
- **Causa:** cada elemento tiene su enum (modelo-de-datos §2).
- **Arreglo:** sondea el enum antes de usar una etiqueta semántica nueva. Sin `dl/ul/ol`: contenedores (avisando de la pérdida) o widget Shortcode. Para el contenido principal de un lienzo sin `<main>`: `section` + atributo `role="main"`.
- **Detecta:** sonda de tags; el guardado deja de lanzar.

### D13 · `e-flexbox` es fila por defecto y colapsa lo que lleva dentro
- **Síntoma:** un formulario V3 dentro de un `e-flexbox` sale estrechísimo.
- **Causa:** `flex-direction: row` de la base y los inputs del formulario con `size="1"`.
- **Arreglo:** envoltorio «caja» en columna con `align-items:stretch` y `width:100%`; `width:100%` al formulario desde el hijo (`.elementor .elementor-widget-form.<clase>`).
- **Detecta:** mide el ancho del formulario contra el spec.

---

## E. Elementos concretos

### E1 · `e-heading` y `e-paragraph` limpian los atributos del HTML
- **Síntoma:** `que tu familia <span class="acento">merece.</span>` se pinta como `<span>merece.</span>`.
- **Causa:** el tipo HTML escapado sanea atributos. `<strong>` sobrevive.
- **Arreglo:** acento de color = `<strong>` más una clase en el widget y CSS en el hijo (`.elementor .<PREFIJO>-acento-x strong { color: var(--x); font-weight: inherit; }`). El cliente lo edita con la negrita.
- **Detecta:** render de prueba con `print_element()` (modelo-de-datos §1).

### E2 · `e-paragraph` no admite listas
- **Síntoma:** una lista `<ul>` dentro de un párrafo sale plana o vacía.
- **Causa:** `e-paragraph` pasa el texto por `striptags` y su `tag` solo admite `p` o `span`.
- **Arreglo:** HTML rico (listas, pasos) con un widget Shortcode propio dentro de una «caja»; en un loop se re-renderiza por ítem.
- **Detecta:** HTML servido.

### E3 · Fuente de imagen o vídeo: id **o** url
- **Síntoma:** `Settings validation failed. source: invalid_value`.
- **Causa:** la validación del prop rechaza id y url juntos.
- **Arreglo:** id del adjunto y `'url' => null` (así sobrevive a un cambio de dominio).
- **Detecta:** sonda con `create_element_instance( … )->get_data_for_save()` dentro de `try`.

### E4 · El alt de `e-image` sale de la Biblioteca, no del widget
- **Síntoma:** la misma foto necesita otro alt en otra página; en el segundo idioma los alt salen en el idioma base aunque el árbol traducido tenga el alt traducido.
- **Causa:** con id de adjunto, el transformador de imagen lee `_wp_attachment_image_alt`; el alt del widget solo se usa con imagen por URL.
- **Arreglo:** fija el alt al importar (`wp media import … --alt=…`) o con `wp post meta update <ID> _wp_attachment_image_alt "…"`; si una imagen necesita dos alts, duplica el adjunto; el alt del segundo idioma va por meta (skill `wp-polylang`). Explícale al cliente dónde se edita.
- **Detecta:** `curl -s <url> | grep -o 'alt="[^"]*"'`.

### E5 · `e-image` sale sin `sizes` y con un `id` numérico repetido
- **Síntoma:** un logo de 135px se descarga en su versión grande en todas las páginas; `id="32"` repetido (logo en cabecera y pie).
- **Causa:** el transformador de imagen devuelve `srcset` sin `sizes` (el navegador supone 100vw) y usa el id del adjunto como atributo `id`.
- **Arreglo:** envolver el transformador `image` registrado (nunca heredar de él, H4) para quitar `id` y poner `sizes`; `sizes` explícito en Avanzado › Atributos para las imágenes no diferidas. Detalle y código en la skill `wp-seo-rendimiento` y `references/tema-hijo.md`.
- **Detecta:** `curl -s <url> | grep -o ' id="[0-9]*"' | sort | uniq -d` vacío.

### E6 · `fetchpriority` a mano duplica atributos
- **Síntoma:** `fetchpriority="high"` duplicado en el banner, o la prioridad alta en el logo de la cabecera y no en la foto principal.
- **Causa:** el módulo «Carga de imágenes optimizada» de Elementor sustituye la lógica del núcleo y da `fetchpriority="high"` a la primera imagen que supera su umbral (a menudo, el logo), en su propio `wp_content_img_tag`. Lo del atributo del widget se suma.
- **Arreglo:** en páginas, no pongas `fetchpriority` ni `loading` como atributo de `e-image`; la prioridad se corrige en el hijo, sin estado (skill `wp-seo-rendimiento`). En plantillas del Theme Builder, que no pasan por `the_content`, sí van como atributos del widget (T7).
- **Detecta:** bucle `curl` por páginas contando `fetchpriority="high"` (una por página, en la imagen principal) y consulta a `postmeta` con `LIKE '%fetchpriority%'`.

### E7 · `e-svg` fuerza `fill="currentColor"` en la raíz
- **Síntoma:** ilustraciones de línea que salen rellenas del color del texto.
- **Causa:** Elementor pone `fill="currentColor"` en el `<svg>` raíz, en PHP y en el editor (JS).
- **Arreglo:** en los SVG de solo trazo, todo el contenido dentro de `<g fill="none">…</g>` (o `fill` explícito en cada hijo); alternativa, `fill:none` desde CSS bajo el selector del widget.
- **Detecta:** `curl <url> | grep -c '<g fill="none">'` = número de ilustraciones; `getComputedStyle( path ).fill`.

### E8 · `e-svg` con URL del propio sitio
- **Síntoma:** cajas vacías en el editor y peticiones loopback en el frontend.
- **Causa:** URL guardada en `http://` (A7): el transformador no la reconoce como local y la pide por HTTP en vez de leerla del disco.
- **Arreglo:** URL https de un archivo del hijo (`img/…`): Elementor lo lee del disco y lo sanea, sin habilitar la subida de SVG (que es decisión de seguridad del cliente; avísale de que para sustituirlos desde el editor tendría que activarla).
- **Detecta:** el SVG sale inline en el HTML servido.

### E9 · `e-background-video`: sin póster, capa que blanquea, controles en inglés y sin id
- **Síntoma:** capturas en blanco al hacer scroll sobre el hero; controles con «Play»/«Pause» y `aria-label="Play video"`; hijos sin id; el vídeo compite con el primer pintado.
- **Causa:** el `<video>` sale con `z-index:0` y el navegador lo promueve a una capa compuesta; no hay control de póster; los hijos generados por builder vienen del editor sin id y con textos por defecto en inglés; imprime `preload="auto"` fijo.
- **Arreglo:** `.<clase> .e-background-video__media { object-fit: cover; z-index: -1; }` en un contenedor con `isolation: isolate`; póster como fondo CSS del contenedor; `$con_ids` (B4) y traducción de los `e-paragraph` de `e-background-video-play/-pause`; botón de pausa y respeto a `prefers-reduced-motion`. El retraso de la carga del vídeo es cosa de la skill `wp-seo-rendimiento`.
- **Detecta:** capturas a distintas alturas con el vídeo reproduciéndose; ningún elemento sin `id`; etiquetas en español en el HTML.

### E10 · Qué se pinta como `<a>` y qué como `<button>`
- **Síntoma:** enlaces anidados (HTML inválido) o un «botón» que no navega.
- **Causa:** un `e-flexbox` con `link` se pinta como `<a>`; un `e-button` con `link` es `<a>` y sin `link`, `<button>`.
- **Arreglo:** no metas un elemento enlazado dentro de un `e-flexbox` enlazado (en tarjetas, el enlace va en el contenedor y el botón interior sin `link`).
- **Detecta:** validador HTML o `grep` de `<a` anidados.

---

## K. Kit y breakpoints

### K1 · `$kit->save()` desde CLI descarta claves
- **Síntoma:** tras activar un breakpoint con `$kit->save( [ 'settings' => … ] )`, `active_breakpoints` y `viewport_laptop` salen `null` en un proceso nuevo; el kit pierde claves.
- **Causa:** en CLI los controles del kit no están registrados y el guardado filtra lo que no reconoce.
- **Arreglo:** leer `_elementor_page_settings` del kit, modificar y `update_post_meta`; después `breakpoints->refresh()`, `Manager::compile_stylesheet_templates()` y `files_manager->clear_cache()` (código en modelo-de-datos §10; plantilla en `scripts/e4-kit-breakpoints.plantilla.php`).
- **Detecta:** cuenta `system_colors`, `custom_colors`, `system_typography`, `custom_typography` y mira `container_width` y los colores de enlace antes y después.

### K2 · El CSS del kit no existe hasta que algo lo usa
- **Síntoma:** después de escribir el kit, `post-<kit>.css` no existe y el HTML no trae `--e-global-color-*`.
- **Causa:** Elementor lo genera perezosamente al pintar algo construido con Elementor.
- **Arreglo:** verifica las escrituras leyendo la meta o la API, no el CSS ni el HTML.
- **Detecta:** `get_post_meta( $kit_id, '_elementor_page_settings', true )`.

### K3 · El `nav-menu` de Pro no colapsa en `laptop`
- **Síntoma:** con `dropdown => 'laptop'` el menú nunca colapsa; regenerar no crea ninguna regla `dropdown-laptop`.
- **Causa:** la plantilla fuente `elementor-pro/assets/css/templates/widget-nav-menu.min.css` solo tiene `dropdown-mobile`, `-mobile_extra`, `-tablet`, `-tablet_extra` y `-none`.
- **Arreglo:** para un corte en el `xl` (1280) del prototipo: `tablet_extra = 1279`, `laptop` inactivo y `dropdown => 'tablet_extra'` (modelo-de-datos §11). Consulta qué admite cada widget clásico **antes** de fijar los breakpoints.
- **Detecta:** `grep -oE 'dropdown-[a-z_]+' <WP_PATH>/wp-content/plugins/elementor-pro/assets/css/templates/widget-nav-menu.min.css | sort | uniq -c`; en el HTML servido, `elementor-nav-menu--dropdown-tablet_extra`.

### K4 · Los widgets V3 heredan la tipografía y los colores de sistema del kit
- **Síntoma:** los ítems del menú salen a 62px en la fuente de cartel y con el color de texto del kit.
- **Causa:** `.elementor-widget-nav-menu .elementor-nav-menu .elementor-item{font-family:var(--e-global-typography-primary-font-family)…}`; los widgets V3 usan `primary`, `text` y `accent` por defecto, y `primary` era la tipografía de cartel.
- **Arreglo:** tipografías y colores de **sistema** neutros en el kit (o asume que se propagan a menú, formularios…) y estiliza el menú desde el hijo con selectores de alta especificidad (`.elementor-location-header.elementor .<PREFIJO>-nav …`) usando `var(--<variable>)`.
- **Detecta:** `getComputedStyle( document.querySelector( '.<PREFIJO>-nav .elementor-item' ) )`.

### K5 · `active_breakpoints` se escribe completo
- **Síntoma:** un breakpoint que el sitio usaba desaparece tras activar otro.
- **Causa:** la clave es una lista y el script la sobrescribe entera.
- **Arreglo:** lee la lista actual y añade o quita; para quitar uno, `unset( $raw['viewport_<nombre>'] )` y sácalo de la lista. Haz copia de la meta antes.
- **Detecta:** `get_active_breakpoints()` en un proceso nuevo, con sus valores.

### K6 · A 1024px exactos, Elementor y Tailwind no coinciden
- **Síntoma:** a 1024px una rejilla sale a una columna en WordPress y a dos en el prototipo.
- **Causa:** `tablet` de Elementor es ≤1024; `lg` de Tailwind empieza en 1024.
- **Arreglo:** diferencia de un píxel de ancho; se acepta y se documenta como «conocida».
- **Detecta:** medir a 1023, 1024 y 1025.

### K7 · «El `nav-menu` no tiene pestaña Estilo en V4» (sin confirmar)
- **Síntoma:** al volcar sus controles desde CLI, todos salen como contenido.
- **Causa:** probablemente A12 (en CLI no se cargan los controles de estilo). No se comprobó en el editor.
- **Arreglo:** estilízalo desde el hijo de todos modos (es lo que se hizo y funciona), y comprueba en el editor antes de afirmarlo en una entrega.
- **Detecta:** abrir el widget en el editor.

---

## V. Widgets V3 y menús

### V1 · Un widget V3 en una fila flex se estira
- **Síntoma:** el menú o el shortcode de idioma ocupan todo el ancho disponible.
- **Causa:** los widgets V3 dentro de un `e-flexbox` en fila crecen.
- **Arreglo:** `'_element_width' => 'auto'` en sus settings.
- **Detecta:** mide la fila.

### V2 · El `nav-menu` pide el menú por slug, y el slug se rehace con el nombre
- **Síntoma:** menús llamados «Pie · Productos ES» con slug `pie-%c2%b7-productos-es`; el widget, que usa `menu => '<slug>'`, se queda sin menú (y Polylang cae al menú de una ubicación). En el segundo idioma, las copias de cabecera y pie siguen mostrando los menús del idioma base.
- **Causa:** `sanitize_title( nombre )` codifica los símbolos, y `wp-admin/nav-menus.php` rehace el slug con el nombre cada vez que se pulsa «Guardar menú». `settings.menu` guarda un slug y Polylang solo traduce ubicaciones de tema.
- **Arreglo:** nombres ASCII que se saneen exactamente al slug deseado («Pie Productos ES» → `pie-productos-es`); para renombrar conservando el slug, `wp term update nav_menu <id> --name=…`; para normalizar, `wp_update_term( $term_id, 'nav_menu', [ 'slug' => … ] )`. Menús por idioma con el mismo patrón (`-<IDIOMA_BASE>` → `-<IDIOMA_2>`) y `settings.menu` reescrito en el árbol del segundo idioma. Documenta en la entrega que no se renombren.
- **Detecta:** `sanitize_title( $nombre ) === $slug` para cada `wp_get_nav_menus()`.

### V3 · `$args->menu` puede llegar como id, slug u objeto
- **Síntoma:** tras mapear menús por idioma en Polylang, el panel móvil pierde el botón de contacto y el selector de idioma en los dos idiomas.
- **Causa:** el filtro del hijo comparaba `$args->menu` como slug; Polylang lo pasa como id del menú del idioma.
- **Arreglo:** normaliza siempre: `$menu = $args->menu instanceof WP_Term ? $args->menu : wp_get_nav_menu_object( $args->menu ); $slug = $menu ? $menu->slug : '';`. Mantén un prefijo común en los slugs de los menús por idioma.
- **Detecta:** `curl` de `/` y `/<IDIOMA_2>/` buscando los elementos inyectados, a 375px.

### V4 · Extras solo en el panel móvil del menú
- **Síntoma:** hace falta añadir el CTA y el idioma solo al menú móvil, como el prototipo.
- **Causa:** el widget llama dos veces a `wp_nav_menu()`; la segunda con `$args->menu_type = 'dropdown'`.
- **Arreglo:** filtro `wp_nav_menu_items` que actúa solo si `( $args->menu_type ?? '' ) === 'dropdown'` y el slug empieza por el prefijo del menú principal (código en `references/tema-hijo.md`).
- **Detecta:** abre la hamburguesa a 375px y lista los `li` del desplegable.

### V5 · SmartMenus queda en modo táctil tras emular un móvil
- **Síntoma:** tras emular móvil y volver a 1440, el hover no abre el submenú en la prueba automática.
- **Causa:** artefacto de la prueba: SmartMenus detectó un dispositivo táctil.
- **Arreglo:** para verificar el submenú de escritorio, `jQuery( ul ).smartmenus( 'itemActivate', jQuery( a ) )` o una pestaña nunca emulada. No es un bug.
- **Detecta:** submenú en `display:block` y centrado respecto a su padre.

### V6 · El `aria-label` del `<nav>` sale de `menu_name`
- **Síntoma:** en el segundo idioma los `<nav>` se anuncian con el nombre del idioma base.
- **Causa:** el `aria-label` sale de `settings.menu_name` del widget, que el pipeline de traducción no copiaba.
- **Arreglo:** incluye `settings.menu_name`, `form_name`, `title` de imágenes y los `aria-*` entre las rutas que se traducen (skill `wp-polylang`).
- **Detecta:** `curl -s <url> | grep -o '<nav[^>]*aria-label="[^"]*"'`.

### V7 · `_attributes` de un widget V3 es texto
- **Síntoma:** un atributo con forma de array no aparece.
- **Causa:** Avanzado › Atributos de un V3 se guarda como texto `clave|valor`, una pareja por línea (a diferencia de `attributes` de V4).
- **Arreglo:** `'_attributes' => "data-x|valor\ndata-y|otro"`. Sirve para dejar editables textos que el widget no expone.
- **Detecta:** el atributo en el HTML servido.

### V8 · Elementor Pro sin traducción es_ES
- **Síntoma:** parte del panel (Theme Builder, Submissions, Actions After Submit) y los mensajes del formulario salen en inglés; una guía de entrega con etiquetas «de memoria» no coincide con el panel real.
- **Causa:** Pro no trae paquete es_ES en el sitio; el núcleo sí.
- **Arreglo:** comprueba las etiquetas con las traducciones instaladas (`translate( $texto, 'elementor' )`) antes de documentarlas; en formularios, fija todos los mensajes a mano (`references/formularios.md`).
- **Detecta:** la guía usa las etiquetas que devuelve `translate()`.

### V9 · Formularios: trampas del widget Form por script (resumen)
Detalle y código en `references/formularios.md`:
- `e-form` atómico (4.3.0 / Pro 4.2.3) descartado: nonce en el HTML que la caché de página caduca, sin validación en servidor, sin honeypot ni campo oculto. Se usa el `form` V3 dentro de un `e-flexbox`.
- `custom_id` solo `[A-Za-z0-9_]` y único entre todos los formularios de la página (prefijos por formulario): el id HTML es `form-field-{custom_id}`.
- Cada ítem del repetidor necesita su `_id` de 7 hex: Elementor no lo genera al guardar.
- `email_reply_to` = el `custom_id` del campo de email, no un shortcode (con `[field id=…]` sale `Reply-To:` vacío).
- `required_field_message` solo se aplica con `form_validation: "custom"`; `custom_messages: "yes"` y todos los mensajes escritos; `form_metadata: []` para no mandar «Date/Time/Page URL» en inglés.
- En el hook de validación, `add_error_message()` no bloquea; hace falta `add_error( $id, … )`.
- Nunca un envío real para probar: `pre_wp_mail` + validación en memoria (`scripts/probe-forms-seco.php`).

---

## X. Contenido dinámico (resumen)

Formas completas, Loop Grid, filtro y CPT en `references/contenido-dinamico.md`.

### X1 · WP-CLI cuenta como «frontend» y las props dinámicas no validan
- **Síntoma:** `save()` lanza `Exception: Settings validation failed. title: invalid_value` (o `image:`) en toda prop con un dynamic tag que tenga settings (post-title, post-featured-image, post-custom-field, shortcode, post-terms). Solo pasa `post-url`, que no tiene settings.
- **Causa:** en CLI `Performance::should_optimize_controls()` es cierto y quita el `label` de los controles → `Dynamic_Tags_Editor_Config::convert_control_to_atomic` descarta esos tags («Control must have name, section, label, and default»): quedan 9 de 38 en CLI frente a 37 de 38 en modo editor. `post-terms` además calcula sus taxonomías con el post actual, vacío en CLI («Select control must have options»).
- **Arreglo:** al principio de **todo** script que guarde props dinámicas (mejor, en el helper común):
  ```php
  $perf = new ReflectionProperty( \Elementor\Core\Frontend\Performance::class, 'is_frontend' );
  if ( PHP_VERSION_ID < 80100 ) { $perf->setAccessible( true ); } // desde PHP 8.1 no hace falta (en 8.5 está obsoleto)
  $perf->setValue( null, false );
  \Elementor\Modules\AtomicWidgets\DynamicTags\Dynamic_Tags_Module::fresh();
  add_filter( 'elementor_pro/dynamic_tags/post_terms/taxonomy_args', function ( $a ) { unset( $a['object_type'] ); return $a; } );
  ```
  Es `scripts/e4-dynamic-tags-preambulo.php`, que va antes de cualquier otro `require`. Un script nuevo que lo olvidó volvió a fallar en el caso de referencia.
- **Detecta:** `save()` sin excepción y la relectura con `$$type: dynamic`.

### X2 · Forma de un dynamic tag
`{"$$type":"dynamic","value":{"name":"post-title","group":"post","settings":{…con envoltorios…}}}`. `group` es obligatorio; los valores crudos fallan; las claves desconocidas se descartan.

### X3 · Límites de lo dinámico
- Solo en **settings**; los estilos se compilan a CSS por documento y en caché (nada de fondos dinámicos).
- Imagen destacada sin `fallback` → «Invalid image URL.» y un `<img>` sin `src`.
- `post-custom-field` solo pinta cadenas y no está en la categoría de imagen.
- Metas sin `_` inicial si Polylang debe copiarlas al traducir.

### X4 · Filtro por taxonomía: sin guiones y grid de 7 caracteres
La URL `?e-filter-<id>-<taxonomía>=<slug>` solo se relee al cargar si la taxonomía no lleva guion (`categoria_receta`, no `categoria-receta`) y el id del Loop Grid es `[a-z0-9]{7}`: `loop-filter/module.php` valida `/^e-filter-[a-z0-9]{7}-[a-z0-9_\-]+$/` y hace `explode( '-' )` esperando dos partes. Decídelo **al registrar la taxonomía**. Detecta: `curl` de la URL filtrada con `aria-pressed="true"` en el botón.

### X5 · Claves del Loop Grid que engañan
Prefijo `post_query_` (skin `post`); `template_id` numérico; en el loop-item, `source` = `'post'` (lo que ata al CPT es `post_query_post_type` del grid); include/exclude de términos por `term_taxonomy_id`; `nothing_found_message_html_tag` no admite `p`; `posts_per_page: -1` pasa aunque el control diga mínimo 1; orden de botones del filtro con `elementor/loop_taxonomy/args`.

### X6 · Un callback de Query ID se llama a sí mismo
Mientras Pro construye su `WP_Query`, `elementor/query/<id>` recibe **cualquier** consulta, incluida la `get_posts()` del propio callback: la página se cuelga. Guarda estática de reentrada (`static $dentro`).

### X7 · El refresco AJAX del filtro no lleva idioma
`POST …/elementor-pro/v1/refresh-loop` no manda `lang` y puede mezclar idiomas: fíjalo en `rest_pre_dispatch` desde `pll_get_post_language( $request['post_id'] )` (skill `wp-polylang`).

---

## T. Theme Builder

Recetas completas en `references/theme-builder.md`.

### T1 · `save_conditions()` espera una lista de arrays
- **Síntoma:** `save_conditions( $id, [ 'include/general' ] )` → `Fatal error: Cannot unset string offsets … conditions-manager.php`.
- **Causa:** el método hace `unset( $condition['_id'] )` e `implode( '/', $condition )` sobre cada condición.
- **Arreglo:** `[ [ 'type' => 'include', 'name' => 'general' ] ]` (o con `sub_name` y `sub_id`).
- **Detecta:** `get_post_meta( $id, '_elementor_conditions', true )` = `["include/general"]`.

### T2 · Desde CLI la condición del subtipo no se guarda sola
- **Síntoma:** plantillas de single o 404 creadas por script que no se aplican.
- **Causa:** `Archive_Single_Base::save_template_type()` solo guarda la condición si llega `$_REQUEST['_elementor_template_sub_type']` (nunca en CLI). Si se forzara, **cada** `save()` la grabaría, también en la copia del segundo idioma.
- **Arreglo:** `unset( $_REQUEST['_elementor_template_sub_type'] )` al principio y `save_conditions()` a mano, solo en la plantilla del idioma base.
- **Detecta:** informe final con las condiciones de cada idioma.

### T3 · La caché de condiciones solo ve plantillas publicadas y CPT navegables
- **Síntoma:** una plantilla con condición que no se aplica; la condición `singular/<cpt>` no existe.
- **Causa:** `Conditions_Cache::regenerate()` solo incluye plantillas publicadas; la condición `singular/<cpt>` solo existe si el CPT es público y `show_in_nav_menus`.
- **Arreglo:** crea con `'post_status' => 'publish'`; registra el CPT con esas dos propiedades.
- **Detecta:** `get_option( 'elementor_pro_theme_builder_conditions' )`; reconstruye con `wp elementor-pro theme-builder clear_conditions`.

### T4 · `save_conditions()` devuelve `false` al re-ejecutar
- **Síntoma:** un script idempotente «falla» la segunda vez.
- **Causa:** `false` = sin cambios.
- **Arreglo:** no lo trates como error; comprueba la meta.
- **Detecta:** la meta sigue igual.

### T5 · La caché de condiciones se regenera con el idioma del contexto
- **Síntoma:** (riesgo) al guardar desde el editor en un idioma, el sitio se queda sin cabecera, pie o ficha en el otro.
- **Causa:** la regeneración consulta `elementor_library` y Polylang la filtra por el idioma del ajax del editor.
- **Arreglo:** `add_filter( 'elementor/theme/conditions/cache/regenerate/query_args', function ( $a ) { $a['lang'] = ''; return $a; } );` en el hijo, y condiciones solo en el idioma base.
- **Detecta:** guardar una plantilla con la cookie en el segundo idioma y comprobar que la opción conserva todas las plantillas.

### T6 · Diseño de página en singles y 404: ni Canvas ni «ancho completo»
- **Síntoma:** con Canvas se pierden cabecera y pie; con «Elementor ancho completo» salen dos `<main id="content">` y el script de revelado dos veces.
- **Causa:** el hijo abre `<main>` en `elementor/page_templates/header-footer/before_content` **y** en `elementor/theme/before_do_single`; la plantilla `header-footer` dispara los dos.
- **Arreglo:** Diseño «Predeterminado» en singles y 404 (el ajuste `page_template` del documento, vacío: en las plantillas del Theme Builder el Diseño es `page_template`, no `template`; theme-builder §1); la raíz del árbol es `<section>`, nunca `<main>`.
- **Detecta:** `curl -s <url> | grep -c '<main'` = 1.

### T7 · Las plantillas del Theme Builder no pasan por `the_content`
- **Síntoma:** el banner de un single sale sin `fetchpriority`; las tarjetas de loop, sin `loading="lazy"`.
- **Causa:** singles y loop items no atraviesan `wp_filter_content_tags`; el supuesto «WordPress ya pone la prioridad a la primera imagen grande» es falso ahí.
- **Arreglo:** en singles y loop items, `fetchpriority` y `loading` como `attributes` del `e-image` en `_elementor_data` (también cubre las tarjetas del refresco REST del filtro).
- **Detecta:** `curl` del single: banner con `fetchpriority="high"`, relacionadas con `loading="lazy"`.

### T8 · Buscar plantillas solo por título duplica
- **Síntoma:** un script crea otra «Cabecera» en vez de reutilizarla.
- **Causa:** búsqueda por título sin tipo ni `'lang' => ''` (A9).
- **Arreglo:** `get_posts` por título **y** `meta_key => '_elementor_template_type'`, con `'lang' => ''` y `'post_status' => 'any'` (código en `references/theme-builder.md` §2).
- **Detecta:** «reutilizo plantilla #ID» en la segunda ejecución.

### T9 · `?elementor_library=<slug>` no sirve como vista previa pública
- **Síntoma:** la vista previa de una plantilla redirige a la portada.
- **Causa:** `Source_Local::block_template_frontend` (en `template_redirect`, prioridad 10) manda con 301 a la home a quien no puede editar.
- **Arreglo:** vista previa con un parámetro propio en `template_redirect` prioridad 11 (theme-builder §10).
- **Detecta:** `curl -o /dev/null -w '%{http_code}'` sin sesión.

### T10 · La plantilla de mantenimiento sale en blanco en el segundo idioma
- **Síntoma:** con el modo encendido, `/<IDIOMA_2>/` sale vacío.
- **Causa:** Elementor busca la plantilla con una consulta que Polylang filtra por idioma, y la opción guarda el id del idioma base.
- **Arreglo:** filtro `option_elementor_maintenance_mode_template_id` → `pll_get_post( $id )` si la traducción está publicada (fuera del admin).
- **Detecta:** `/` y `/<IDIOMA_2>/` con su plantilla y su `<html lang>`.

### T11 · «Próximamente» (`coming_soon`) responde 200 + noindex en todo
- **Síntoma:** `robots.txt` devuelve el HTML de la cortina con 200 y `text/html`; en un dominio con historial, eso desindexa.
- **Causa:** en `coming_soon`, `template_redirect` (prioridad 11) sustituye la consulta en todas las URL. En `maintenance` responde **503 + `Retry-After: 600`**.
- **Arreglo:** `coming_soon` solo en staging; en un dominio público con historial, `maintenance` (o una cortina estática 503) el menor tiempo posible (theme-builder §10; skill `wp-migracion`).
- **Detecta:** `curl -sI https://<DOMINIO>/` → `503` + `retry-after`; `curl -sI https://<DOMINIO>/robots.txt`.

---

## H. Caché, tema hijo y versiones

### H1 · Un plugin olvidado reescribe el `?ver=` del hijo
- **Síntoma:** el CSS del hijo sigue con la versión de la cabecera de `style.css` aunque se encola con `filemtime`; se diagnosticó mal como «caché de PHP».
- **Causa:** *Child Theme Configurator* (residuo de un tema anterior) filtra `style_loader_src` con `chld_thm_cfg_version`; también en wp-login.
- **Arreglo:** al cambiar de tema, desactiva los plugins que servían al anterior; mientras tanto, `remove_filter( 'style_loader_src', 'chld_thm_cfg_version' )` en `wp_head` prioridad 0. Ante algo que «no cuadra», no supongas caché: lista con `ReflectionFunction` los callbacks de `style_loader_src`, `style_loader_tag` y `script_loader_src`. Si ya escribiste un diagnóstico erróneo en memoria, corrígelo diciéndolo.
- **Detecta:** `curl -s "<url>?nocache=$(date +%s)" | grep -oE "<child>/style.css\?ver=[0-9.]+"` = `filemtime`.

### H2 · Estáticos con `max-age` de un año y HTML cacheado con el `?ver=` viejo
- **Síntoma:** con el `style.css` ya cambiado en el servidor, la página carga la hoja anterior.
- **Causa:** el hosting sirve estáticos con `max-age=31536000` y su caché de página guarda el HTML que apunta a la versión vieja.
- **Arreglo:** CSS y JS del hijo versionados por `filemtime` y purga de la caché de página tras **cada** despliegue del hijo y cada script que cambie documentos (en SiteGround, `wp sg purge`, que avisa «Unable to Purge File Cache» sin consecuencias; sin caché del hosting, `wp cache flush` + `wp elementor flush_css`).
- **Detecta:** el `?ver=` servido cambia en cada despliegue; `curl -s -D- -o /dev/null <url> | grep -i x-proxy-cache`.

### H3 · `?m=` en una URL de WordPress da 404
- **Síntoma:** todas las páginas medidas con `?m=<timestamp>` devuelven «Página no encontrada».
- **Causa:** `m` es la variable pública de archivo por fecha.
- **Arreglo:** rompe la caché con `?nocache=`, `?nc=` o `?v=`; nunca `m`, `p`, `page_id`, `s`, `cat`, `name`.
- **Detecta:** el `<title>` no dice «no encontrada».

### H4 · Heredar de un transformador concreto puede tumbar el sitio
- **Síntoma:** (riesgo confirmado por revisión) `new class() extends Image_Transformer` o `Font_Family_Transformer` provoca un `E_COMPILE_ERROR` no capturable si una actualización añade un tipo de retorno, marca la clase `final` o mueve una dependencia.
- **Causa:** acoplamiento a clases internas; el registro de transformadores se construye en cada render atómico y tras limpiar el CSS (justo al actualizar).
- **Arreglo:** toma el registrado (`$transformers->get( $clave, false )`), comprueba que es `Transformer_Base` y que su `transform` no tiene tipo de retorno, y envuélvelo en una clase que extienda **solo** `Transformer_Base` con `transform( $value, $context = null, ...$resto )`. Código en `references/tema-hijo.md`.
- **Detecta:** tras actualizar Elementor, pinta tres páginas sin «error crítico» y comprueba que el efecto sigue.

### H5 · El transformador `font-family` no emite respaldo
- **Síntoma:** el CSS de clases trae `font-family:Archivo` sin pila de respaldo.
- **Causa:** el transformador devuelve una sola familia.
- **Arreglo:** envolverlo (H4) para devolver `Familia, "Familia Fallback", sans-serif` en las fuentes propias; el nav-menu V3 usa `var(--e-global-typography-*)` y no pasa por él: respaldo a mano en el hijo. Detalle en la skill `wp-seo-rendimiento`.
- **Detecta:** `grep -c 'Fallback' …/global-<id>-frontend-desktop.css` > 0.

### H6 · `html{scroll-behavior:smooth}` de Elementor rompe las anclas entre páginas
- **Síntoma:** `/pagina/#seccion` desde otra página espera unos 2 s y se desliza (o se queda arriba); el prototipo salta en seco.
- **Causa:** `frontend.min.css` incluye `@media (prefers-reduced-motion:no-preference){html{scroll-behavior:smooth}}`; no era JS.
- **Arreglo:** si el prototipo no anima el scroll, `html { scroll-behavior: auto; }` en el hijo, y `scroll-margin-top` por breakpoint según la cabecera fija (contando la barra de admin).
- **Detecta:** iframe que carga `/pagina/#id` y registra `scrollY` cada pocos ms: debe llegar en un solo paso.

### H7 · Reglas del hijo que pierden contra los estilos atómicos
- **Síntoma:** decenas de reglas `[data-<PREFIJO>=…]` sin efecto, sobre todo dentro de `@media` de una línea.
- **Causa:** las clases compilan a `.elementor .<clase>` (0,2,0); un selector de atributo solo pierde.
- **Arreglo:** prefija con `.elementor ` (o con la ubicación, `.elementor-location-header.elementor`) también dentro de `@media`. Audita con regex tras cada edición masiva (`references/tema-hijo.md`).
- **Detecta:** la auditoría devuelve 0 reglas sin prefijo.

### H8 · Optimizaciones del hosting incompatibles con Elementor 4
- **Síntoma:** cascada del hijo invertida, parpadeo de las animaciones, banners sin `src`, cache-busting roto.
- **Causa:** en SiteGround Optimizer, Combine CSS/JS, Defer JS, Lazy Load, Remove Query Strings y Web Fonts chocan con el CSS por breakpoint de Elementor, con el `<script>` inline del `<head>` y con el versionado.
- **Arreglo:** déjalas apagadas (lista y motivos en la skill `wp-seo-rendimiento`).
- **Detecta:** bucle de `wp option get siteground_optimizer_*`.

### H9 · `clean_permalinks` de Yoast rompe filtros y vista previa
- **Síntoma:** (riesgo) `?e-filter-…`, `?cat=` y `?elementor-preview` redirigidos.
- **Causa:** Yoast redirige los parámetros desconocidos.
- **Arreglo:** `clean_permalinks=false` en sitios Elementor.
- **Detecta:** el filtro y la vista previa funcionan en incógnito.

---

## M. Medición y verificación

### M1 · Capturas en blanco y animaciones que no se disparan
- **Síntoma:** capturas en blanco o con la cabecera a media pantalla; «0 de 11 bloques revelados».
- **Causa:** el panel del navegador oculto o la pestaña no activa (los subagentes abren pestañas propias): `requestAnimationFrame` e `IntersectionObserver` se pausan. Una captura durante el scroll tampoco está revelada.
- **Arreglo:** confirma por JS antes de «arreglar» (opacidad, clase `is-in`, `img.complete`, `elementFromPoint`); lista las pestañas, cierra las ajenas y activa la propia. Mide por JS, no por capturas.
- **Detecta:** tras activar la pestaña, los bloques pasan a `is-in` al hacer scroll.

### M2 · Las entradas animadas y la barra de scroll falsean las medidas
- **Síntoma:** `top` desplazados 28px; anchos que no cuadran a 1440.
- **Causa:** los reveals (`translateY` y opacidad) cambian `getBoundingClientRect`; con barra de scroll, el ancho útil a 1440 es 1425.
- **Arreglo:** fuerza el estado final (`.is-in` y `transition:none`) en los dos lados, prototipo y WordPress; anota el ancho útil real en el spec; mide la posición del texto, no solo el alto de la caja.
- **Detecta:** `getComputedStyle( e ).transform === 'none'` en lo medido; diferencias ≤ ±1px.

### M3 · «El sticky no funciona» con sesión iniciada
- **Síntoma:** el cliente dice que un índice fijo no queda fijo.
- **Causa:** la barra de admin (`top:32px`; 46px por debajo de 782; no fija por debajo de 600) empuja la cabecera fija y el sticky queda detrás.
- **Arreglo:** mide sin sesión en tres anchos antes de tocar nada; deja offsets para `.admin-bar`; pregunta si lo vio con sesión.
- **Detecta:** `top` y `position` del sticky y la cabecera en varias alturas de scroll.

### M4 · Claude no puede abrir el editor de Elementor
- **Síntoma:** cada cierre de página queda «pendiente de verificar en el editor».
- **Causa:** sin sesión de administrador en el navegador, la interfaz del editor no es comprobable.
- **Arreglo:** verifica por datos lo que usará el editor (parsers en modo editor sobre cada `_elementor_data`, clases existentes en el repositorio y en preview, ids únicos y presentes, `template_id` publicados, `selected_element` coherente) y pide siempre al usuario la prueba visual, como pendiente explícito hasta que la confirme.
- **Detecta:** la dimensión de editabilidad del workflow de revisión.

### M5 · Una diferencia de medida puede ser una decisión anterior
- **Síntoma:** una página mide 33px más que el prototipo.
- **Causa:** la diferencia venía del pie (un enlace añadido a propósito), no de la página.
- **Arreglo:** compara sección por sección hasta aislarla y busca en la memoria o en la sesión si fue deliberada; mantén una lista «conocido y aceptado» que se pasa a los revisores.
- **Detecta:** el resto de secciones cuadra dentro de la tolerancia canónica (≤1 px; ≤3 px solo en anchos intermedios: skill `wp-elementor-replica`, `references/verificacion.md` §2).

### M6 · Probar 404 y admin con curl
- **Síntoma:** `/product` no da 404 sino 301; `curl` a `/wp-admin/` devuelve 403.
- **Causa:** `redirect_guess_404_permalink` de WordPress; el reto anti-bot del hosting con el UA por defecto de curl.
- **Arreglo:** prueba la 404 con slugs absurdos (`/no-existe-xq/`) y todo lo de acceso con `-A 'Mozilla/5.0 …'`.
- **Detecta:** `curl -A "$UA" -o /dev/null -w '%{http_code}'`.

---

## P. Después de migrar (lo de Elementor)

Detalle en la skill `wp-migracion`. Con la ruta y el dominio finales (`--url=https://<DOMINIO>`):
- `wp elementor replace_urls <viejo> <nuevo> --force`, `wp elementor flush_css`,
  `wp elementor-pro theme-builder clear_conditions`, y comprueba que
  `elementor_pro_theme_builder_conditions` trae todas las plantillas.
- Compara las metas de orden de clases frontend y preview (C1) **antes** de que nadie
  publique desde el editor.
- El WAF del hosting nuevo (ModSecurity) puede bloquear el guardado de Elementor con un
  403 en `admin-ajax`: pide al usuario que abra una página por idioma y la cabecera,
  pulse «Actualizar» sin cambios y «Regenerar archivos y datos»; si hay 403, ticket al
  proveedor antes de dar acceso al cliente.

---

## O. Otras trampas de WP-CLI del caso, fuera de Elementor (índice)

Viven en su skill; se listan aquí para que no se escapen al buscar por síntoma.

- **Polylang** (skill `wp-polylang`): `pll_save_post_translations` descarta en silencio
  los ids cuyo idioma no coincide con su clave (asigna el idioma antes de enlazar);
  `pll_insert_post` deja slugs con `-2` (se corrigen después con `wp_update_post`); los
  términos del segundo idioma se crean antes que las entradas y en el mismo orden; la
  portada del segundo idioma sale en `/<IDIOMA_2>/home/` hasta activar `redirect_lang`;
  las cadenas se escriben con `PLL_MO` y un solo escritor; `pll_get_post_types` va en el
  nivel superior del archivo; nunca «Synchronize» de Polylang Pro en páginas Elementor.
- **Idioma en ajax y caché**: el ajax del editor y el de los formularios toman el idioma
  de la cookie de Polylang, no el del documento (mu-plugin con `pll_preferred_language`:
  `references/tema-hijo.md`); la cookie `pll_language` impide la caché de página del
  hosting (`PLL_COOKIE` a `false` en un mu-plugin: skill `wp-seo-rendimiento`).
- **Acceso y correo** (skills `wp-elementor-replica` y `wp-migracion`): el login propio
  de SG Security va **sin barras** en `sg_security_login_url`, se escribe antes que
  `sg_security_login_type = custom` y su CLI solo trae `status` y `disable`; una
  `Referrer-Policy` global `origin` o `strict-origin` rompe ese login;
  `wp option update admin_email` no pasa por la confirmación ni avisa a la dirección
  anterior.
- **Migración** (skill `wp-migracion`): la colación `utf8mb4_0900_ai_ci` rompe la
  importación de MySQL 8 a 5.7; `wp db export --no-data` no funciona; el search-replace
  va en cuatro formas y en orden; un `tar` de un sitio vivo corta con «file changed as
  we read it»; en un cPanel compartido, WP-CLI sube por las carpetas y puede escribir en
  el WordPress de otro sitio: siempre `--path`.
