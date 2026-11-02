# Plantilla · documento técnico de entrega y cierre

Esqueleto y guía del documento que cierra el proyecto: un **HTML A4 autocontenido** que el
usuario completa (campos editables) y exporta a PDF desde el navegador. Se produce en la fase
F11, normalmente en una sesión paralela que **lee** el sitio y no escribe en él (salvo arreglar,
coordinado, lo que la revisión demuestre roto). Caso de referencia: 14 secciones, 12 páginas A4.

**Reglas que no se negocian**
- **Nunca la contraseña escrita**, tampoco si el usuario lo pide «aceptando el riesgo»: va un
  campo editable vacío que él rellena antes de exportar. Portada marcada «Confidencial ·
  contiene credenciales». Si el usuario pegó una contraseña en el chat, no la repitas y
  recomiéndale cambiarla tras la entrega.
- **Lo que el documento afirma tiene que ser cierto en el servidor** o salir como supuesto a
  confirmar. *Por qué:* la lista de cierre del caso marcaba «plugins y temas de construcción
  retirados» cuando cinco seguían activos. No marques una casilla hasta que se cumpla.
- **Rutas del panel comprobadas con las traducciones instaladas**, no de memoria. *Por qué:*
  Elementor Pro salía en inglés (sin traducción es_ES) y Elementor 4 reorganizó sus menús; el
  borrador tenía rutas que el cliente no iba a encontrar. Escribe la ruta tal como la ve el
  cliente, aunque mezcle idiomas, y avísalo.
- **Solo lo indispensable.** En el caso, el usuario pidió un documento formal, sin licencias ni
  detalles de hosting más allá de la URL final, dando por terminadas traducciones y SEO.
  Pregunta qué incluir ([`decisiones.md`](../references/decisiones.md) §3.7) antes de escribir.
- Nada del staging (URL, alias, rutas del servidor) en el documento.

---

## 1. Proceso (el que funcionó)

1. **Leer** el `CLAUDE.md` del proyecto y la memoria (usuario, formularios, dominio final,
   construcción, sesiones paralelas, cortina).
2. **Inventario de solo lectura** del servidor (§5) → `inventario.json`. Es la fuente de las
   secciones 03–10: versiones, plugins, documentos con idioma y traducciones, condiciones,
   CPT, menús, formularios.
3. **Etiquetas reales del panel** (§5, `etiquetas.php`) para las rutas de las secciones 05, 08,
   09, 10 y 11.
4. **Bajar el tema hijo del servidor** y compararlo (`diff -q`) con la última copia conocida:
   así aparecen los módulos que añadieron otras sesiones (rendimiento, SEO, accesibilidad,
   acceso, «Próximamente») y se documentan.
5. **Escribir la fuente** `entrega.src.html` (esqueleto en §3) con el marcador `{{LOGO}}` y
   montarla con el logo en base64 para que el HTML no dependa de nada:
   ```python
   import sys, base64
   logo = base64.b64encode(open(sys.argv[3], 'rb').read()).decode()
   s = open(sys.argv[1]).read().replace('{{LOGO}}', 'data:image/png;base64,' + logo)
   assert '{{' not in s
   open(sys.argv[2], 'w').write(s)
   ```
   (`python3 - entrega.src.html <Marca>-Documento-de-entrega.html <logo>.png <<'PY' … PY`)
6. **PDF con Chrome headless, contar páginas y leerlas** (no basta con el HTML):
   ```bash
   S=<carpeta de trabajo local>; OUT="$S/<Marca>-Documento-de-entrega.html"   # rutas absolutas
   "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --no-pdf-header-footer \
     --virtual-time-budget=8000 --print-to-pdf="$S/preview.pdf" "file://$OUT"
   python3 -c "import re;d=open('$S/preview.pdf','rb').read();print('paginas',len(re.findall(rb'/Type\s*/Page[^s]',d)))"
   ```
   Lee las páginas del PDF con la herramienta de lectura y corrige la maquetación (§4).
7. **Revisión adversarial** de solo lectura con [`revisar-entrega.js`](workflows/revisar-entrega.js)
   (lentes: hechos, rutas del panel, completitud; un escéptico por hallazgo). Pásale lo que el
   autor decidió y **no** es un defecto (sin hosting, sin licencias, contraseña en blanco, sin
   URL de staging, solo plugins de producción).
8. Mientras corre, **verifica tú** alguna afirmación con cifras (en el caso: 46 de 49 adjuntos
   con alt en el idioma 2 → se escribió «las imágenes del contenido», no «todas»).
9. **Arregla en el sitio** lo que la revisión demuestre roto (en el caso, nombres de menú),
   coordinado con la sesión de construcción: no basta con documentarlo.
10. **Aplica las correcciones** con sustituciones que fallan si el ancla no es única:
    ```python
    def rep(a, b):
        global s
        assert s.count(a) == 1, (a[:80], s.count(a))
        s = s.replace(a, b)
    ```
11. **Controles finales** (§6), PDF definitivo, envío del archivo con las instrucciones de
    exportación, **correo de cierre** (§7) y memoria actualizada (incluidas las trampas nuevas).

## 2. Formato

- Un solo HTML autocontenido (logo en base64; fuentes de marca por Google Fonts o locales),
  A4, exportable con **Imprimir → Guardar como PDF**.
- Barra fija en pantalla (se oculta al imprimir): «Completa los campos resaltados (se editan
  haciendo clic) y exporta con Imprimir → Guardar como PDF, tamaño A4, márgenes
  predeterminados, con gráficos de fondo» + botón `window.print()`.
- Tono formal, de usted, sin jerga. Rutas del panel en **negrita** con «→», tal como las ve el
  cliente. Notas en cajas: acento = aviso; color de marca = estado o dato positivo. Tablas con
  cabecera en versalitas y la primera columna en seminegrita.
- Responsive a 820 px para leerlo en el móvil (tablas con `overflow-x: auto`).

## 3. Esqueleto HTML

Sustituye los tokens de `:root` por los de la marca. Estructura y CSS de impresión tomados del
documento del caso (probado: Chrome headless, 12 páginas A4 sin páginas medio vacías).

```html
<!DOCTYPE html>
<html lang="<IDIOMA_BASE>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Documento de entrega — <MARCA></title>
<!-- fuentes de marca: Google Fonts o @font-face con archivos locales -->
<style>
  :root {
    --marca: #……; --marca-profunda: #……; --acento: #……;
    --marca-clara: #……; --acento-claro: #……;            /* fondos de las notas */
    --tinta: #……; --tinta-suave: #……; --tinta-tenue: #……;
    --linea: #……; --papel: #FFFFFF; --papel-alterno: #……;
    --titulo: "<FUENTE_DISPLAY>", sans-serif; --texto: "<FUENTE_TEXTO>", Arial, sans-serif;
  }
  @page { size: A4; margin: 18mm 17mm 20mm; }
  @page {
    @bottom-left { content: "<MARCA> · Documento de entrega y cierre"; font: 8pt var(--texto); color: var(--tinta-tenue); }
    @bottom-right { content: counter(page) " / " counter(pages); font: 8pt var(--texto); color: var(--tinta-tenue); }
  }
  @page portada { margin: 0; @bottom-left { content: none; } @bottom-right { content: none; } }
  * { box-sizing: border-box; }
  html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body { margin: 0; background: #E9E8E4; color: var(--tinta); font: 10pt/1.55 var(--texto); }
  .hoja { width: 210mm; margin: 24px auto; background: var(--papel); box-shadow: 0 10px 30px rgb(0 0 0 / .12); }
  .pagina { padding: 18mm 17mm 20mm; }
  h2 { font: 400 22pt/1.02 var(--titulo); color: var(--marca); margin: 0 0 4mm; break-after: avoid; }
  h2 .n { color: var(--acento); }
  h3 { font: 400 12.5pt/1.02 var(--titulo); margin: 6mm 0 2mm; break-after: avoid; }
  .seccion { padding-top: 2mm; margin-bottom: 9mm; }        /* SIN break-before por sección */
  .bloque, .nota, .acceso, tr { break-inside: avoid; }
  h2 + *, h3 + * { break-before: avoid; }
  .nota { border-left: 3px solid var(--acento); background: var(--acento-claro); padding: 3mm 4mm; margin: 3mm 0 4mm; font-size: 9.4pt; }
  .nota.ok { border-color: var(--marca); background: var(--marca-clara); }
  table { width: 100%; border-collapse: collapse; margin: 2mm 0 4mm; font-size: 9.2pt; }
  th, td { text-align: left; vertical-align: top; padding: 2.2mm 2.6mm; border-bottom: 1px solid var(--linea); }
  th { font-size: 7.8pt; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--tinta-suave); background: var(--papel-alterno); }
  td:first-child { font-weight: 600; }
  .portada { page: portada; height: 297mm; display: flex; flex-direction: column; position: relative; overflow: hidden; }
  .portada__confidencial { position: absolute; top: 15mm; right: 20mm; font-size: 7.6pt; font-weight: 700; letter-spacing: .14em;
    text-transform: uppercase; color: var(--acento); border: 1.5px solid var(--acento); padding: 1.4mm 3mm; border-radius: 99px; }
  .portada__meta { margin: auto 20mm 22mm; border-top: 2px solid var(--tinta); padding-top: 5mm; display: grid; grid-template-columns: repeat(2, 1fr); gap: 4mm 10mm; }
  /* índice y lista de cierre con REJILLA, nunca con columns: CSS */
  .indice { list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; grid-auto-flow: column; grid-template-rows: repeat(7, auto); column-gap: 12mm; }
  .check { list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 0 10mm; }
  .campo { display: inline-block; min-width: 70mm; border-bottom: 1.5px dashed var(--acento); background: var(--acento-claro); padding: 0 2mm; outline: none; }
  .campo:empty::before { content: attr(data-ayuda); font-style: italic; opacity: .7; }
  .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 12mm; margin-top: 8mm; break-inside: avoid; }
  .firma .espacio { height: 20mm; }
  .barra { position: sticky; top: 0; z-index: 5; background: var(--tinta); color: #fff; padding: 10px 16px; display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
  @media print {
    body { background: #fff; } .barra { display: none; }
    .hoja { width: auto; margin: 0; box-shadow: none; } .pagina { padding: 0; }
    .campo { background: transparent; } .campo:empty { min-height: 5mm; } .campo:empty::before { content: ""; }
  }
  @media screen and (max-width: 820px) {
    .hoja { width: auto; margin: 0; } .pagina { padding: 24px 16px; }
    .portada { height: auto; min-height: 100vh; } .portada__meta { grid-template-columns: 1fr; margin: 40px 16px 24px; }
    table { display: block; overflow-x: auto; } .campo { min-width: 0; width: 100%; } .firmas { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>
<div class="barra"><span>Completa los campos resaltados (se editan haciendo clic) y exporta con <strong>Imprimir → Guardar como PDF</strong>, A4, márgenes predeterminados, con gráficos de fondo.</span>
  <button type="button" onclick="window.print()">Imprimir / PDF</button></div>
<div class="hoja">
  <section class="portada">
    <span class="portada__confidencial">Confidencial · contiene credenciales</span>
    <img src="{{LOGO}}" alt="<MARCA>">
    <p>Documento técnico</p>
    <h1>Entrega y cierre del <span>sitio web.</span></h1>
    <p>Arquitectura, accesos, componentes y guía de administración del sitio de <MARCA>.</p>
    <dl class="portada__meta">
      <div><dt>Cliente</dt><dd><CLIENTE></dd></div>
      <div><dt>Sitio</dt><dd><DOMINIO></dd></div>
      <div><dt>Versión del documento</dt><dd>1.0 — Entrega final</dd></div>
      <div><dt>Fecha</dt><dd><fecha></dd></div>
      <div><dt>Preparado por</dt><dd><Nombre> — <Cargo></dd></div>
      <div><dt>Recibe</dt><dd><span class="campo" contenteditable="true" data-ayuda="Nombre del responsable del cliente"></span></dd></div>
    </dl>
  </section>
  <div class="pagina">
    <section class="seccion"><h2>Contenido</h2><ol class="indice"><li><span>01</span>Resumen de la entrega</li><!-- … 02–14 --></ol></section>
    <section class="seccion"><h2><span class="n">02</span> Acceso al administrador</h2>
      <div class="acceso"><table>
        <tr><td>URL de acceso</td><td><strong>https://<DOMINIO>/<LOGIN_SLUG></strong></td></tr>
        <tr><td>Usuario</td><td><strong><usuario que da el usuario></strong></td></tr>
        <tr><td>Contraseña</td><td><span class="campo" contenteditable="true" spellcheck="false" autocomplete="off" data-ayuda="Pegar aquí la contraseña antes de exportar"></span></td></tr>
        <tr><td>Correo de la cuenta</td><td><correo del cliente></td></tr>
      </table></div>
    </section>
    <!-- 01, 03–13 según §4 -->
    <section class="seccion"><h2><span class="n">14</span> Acta de entrega</h2>
      <div class="firmas">
        <div class="firma"><b>Entrega</b><div class="espacio"></div><!-- Nombre · Cargo · Fecha (campos) --></div>
        <div class="firma"><b>Recibe — Cliente</b><div class="espacio"></div><!-- Nombre · Cargo · Fecha (campos) --></div>
      </div>
    </section>
  </div>
</div>
</body>
</html>
```

## 4. Qué va en cada sección

| # | Sección | Contenido | De dónde sale |
|---|---|---|---|
| — | Portada | banda de color de marca, sello «Confidencial · contiene credenciales», logo, kicker «Documento técnico», H1 «Entrega y cierre del *sitio web.*» (acento en una parte), subtítulo, metadatos: Cliente · Sitio · Versión («1.0 — Entrega final») · Fecha · Preparado por (Nombre — Cargo) · Recibe (campo) | usuario |
| — | Contenido | índice 01–14 en rejilla de dos columnas | — |
| 01 | Resumen de la entrega | párrafo (qué, con qué, en qué idiomas, que todo se edita sin código); 6 tarjetas: Sitio público · Versión en el idioma 2 · Administrador (URL de acceso) · Idioma principal · Idioma secundario · Construcción; «Qué incluye» (páginas por idioma, CPT con sus cifras, formularios, cabecera y pie, sistema de diseño propio); nota de **estado a la fecha** (p. ej. en «Próximamente» por decisión del cliente, cómo se abre y que no requiere desarrollo); nota de **alcance** de lo que no incluye alguna página | inventario (`docs`, `cpt`, `formularios`, `mantenimiento`) |
| 02 | Acceso al administrador | entrada solo por la URL propia (también con la cortina activa); caja de credenciales (URL · Usuario · Contraseña = **campo vacío** · Correo de la cuenta · «Rol: Administrador»); recomendaciones: cambiar la contraseña al primer ingreso en **Usuarios → Perfil** (16 caracteres o más), una cuenta por persona (Editor frente a Administrador), no compartir la cuenta por correo o mensajería, eliminar cuentas y reasignar contenido | usuario; etiquetas |
| 03 | Arquitectura del sitio | tabla (gestor + PHP · constructor y versión del editor · tema padre + hijo · idiomas y esquema de URL · SEO · enlaces permanentes y zona horaria); «Cómo está organizado» en 5 capas (Contenido · Plantillas · Sistema de diseño: kit, variables y N clases globales con prefijo · Tema hijo · Base); muestras de color (nombre + hex), tipografías y la regla del acento («negrita en un título = color») | inventario (`wp`, `php`, `elementor`, `polylang`, `site`) |
| 04 | Mapa del sitio | tabla Página · Idioma 1 · Idioma 2 · En el menú; «Anclas de sección» (Página · Identificadores · Se usan en) y dónde se edita el ID en Elementor; URL cortas o filtros que el sitio convierte solo | inventario (`docs`, `menus`); sondas de anclas |
| 05 | Plantillas del Theme Builder | ruta real en el panel (y aviso si sale en otro idioma); tabla Plantilla · Dónde aparece · Idioma 1 · Idioma 2 (cabecera, pie, ficha del CPT, tarjetas de loop, 404, «Próximamente»); nota: **las condiciones solo van en las plantillas del idioma principal** | inventario (`docs.tpl_type`, `docs.cond`, `condiciones`); etiquetas |
| 06 | Plugins | solo el conjunto de producción: Plugin · Para qué · Versión; «Complementos obligatorios (mu-plugins)»: Archivo · Función, y por qué no se desactivan por error | inventario (`plugins`, `mu`) — contrasta con `wp plugin list --status=active` |
| 07 | Tema hijo y código a medida | ruta del hijo y por qué el padre se puede actualizar; tabla Archivo · Qué hace (`functions.php`, `inc/*.php` por módulo, `js/*.js`, `style.css`, `fonts/`, `img/`); nota sobre los atributos `data-<PREFIJO>-*` que conectan bloques con su comportamiento (se edita el contenido, no el atributo) | inventario (`hijo`) + `diff` del hijo |
| 08 | Idiomas | idioma principal sin prefijo y secundario en `/<IDIOMA_2>/`; tabla Qué · Dónde se traduce (páginas y plantillas · CPT y taxonomías · menús con sus **nombres reales** · texto alternativo · cadenas generales · textos automáticos); pasos «Crear una traducción nueva»; nota: **no pulse «asignar idioma a todo»: el kit no debe tener idioma** | inventario (`idiomas`, `polylang`, `menus`); etiquetas |
| 09 | Guía rápida de administración | tabla Tarea · Cómo se hace: editar una página · destacar parte de un título · añadir o editar un elemento del CPT (formato de cada campo) · cambiar un menú (**no renombrarlo**: el nombre cambia el slug y desconecta los widgets) · texto alternativo · elementos especiales (atributos) · plantillas · colores y tipografías · ver mensajes recibidos · «un cambio no se ve» (vaciar archivos y datos + caché) · **abrir el sitio al público** (modo de mantenimiento → desactivado + visibilidad) y vista previa de la cortina | etiquetas; memoria |
| 10 | Formularios de contacto | tabla Formulario · Campos (* obligatorios) · Asunto; datos: destinatarios · remitente (lo usan también los avisos de WordPress; responder contesta a quien escribió) · idioma del correo · copia de envíos en el panel · antispam (sin captcha ni servicios externos); «Cambiar destinatarios» con la ruta exacta y **cuántos lugares** (formularios × idiomas), y aviso de que las pruebas llegan a los destinatarios reales | inventario (`formularios`, `tabla_envios`); etiquetas |
| 11 | SEO e indexación | títulos y descripciones (dónde, por idioma) · resultados enriquecidos · URL del sitemap · hreflang y x-default · **visibilidad** (etiqueta literal de la casilla de Ajustes → Lectura) · textos alternativos | inventario (`yoast`, `docs.yoast_*`); etiquetas |
| 12 | Mantenimiento y lo que no se debe tocar | «Actualizaciones»: copia completa antes; Elementor y Pro juntos y qué páginas revisar después en cada idioma; de uno en uno; el padre se puede actualizar. Tabla «No se debe borrar, renombrar ni desactivar» · Por qué: tema hijo · kit · clases globales con prefijo · atributos `data-*` · IDs de sección · nombres de menús · plantilla «Próximamente» · condiciones de las plantillas del idioma principal · mu-plugins | inventario; memoria |
| 13 | Lista de cierre | casillas en rejilla: instalado con HTTPS y estado · idioma 2 completo · diseño verificado contra el prototipo en escritorio y móvil · formularios probados · SEO · alt · acceso entregado · plugins y temas de construcción retirados · copia de seguridad al cierre. **Solo las que se cumplen** | comprobación en el servidor |
| 14 | Acta de entrega | párrafo formal («Con la firma de este documento, las partes dejan constancia de la entrega… y dan por cerrado el proyecto de desarrollo. [Estado vigente] responde a una decisión del cliente y no afecta a la finalización de la entrega.») + dos firmas (Entrega / Recibe — Cliente), cada una con Nombre · Cargo · Fecha editables y 20 mm de espacio | usuario |

**Maquetación que funcionó** (el primer PDF del caso tenía páginas medio vacías, un `h3`
cortado al pie y listas partidas entre columnas; pasó de 14 a 12 páginas):
- sin `break-before: page` por sección;
- `tr, .bloque, .nota, .acceso { break-inside: avoid }` y `h2 + *, h3 + * { break-before: avoid }`;
- índice y lista de cierre con `display: grid` (el índice con `grid-auto-flow: column;
  grid-template-rows: repeat(N, auto)`), nunca con `columns: 2`;
- `.campo[contenteditable]:empty::before { content: attr(data-ayuda) }`, vacío en `@media print`.

## 5. Inventario de solo lectura

Ejecuta los dos scripts con `wp eval-file` (PHP por archivo, nunca inline por SSH) y guarda la
salida en local. `inventario.json` lleva los destinatarios de los formularios: es sensible, no
se publica ni se adjunta.

```php
<?php
/**
 * inventario.php — Inventario de SOLO LECTURA del sitio para el documento de entrega → JSON.
 * Uso:  scp inventario.php <SSH_ALIAS>:~/tmp-<PREFIJO>/
 *       ssh <SSH_ALIAS> 'INV_CPT=<cpt> INV_TAX=<taxonomia> INV_ALT_META=<meta_alt_idioma2> wp --path=<WP_PATH> eval-file ~/tmp-<PREFIJO>/inventario.php --user=1' > inventario.json
 * Parámetros (variables de entorno, todas opcionales): INV_CPT y INV_TAX (listas separadas por comas),
 *   INV_ALT_META (meta del texto alternativo del idioma 2, p. ej. _<PREFIJO>_alt_en).
 * Privacidad: de los usuarios solo la longitud del login y los roles; de admin_email, solo el dominio.
 *   La salida SÍ lleva los destinatarios de los formularios: trátala como sensible y no la subas a ningún sitio.
 * Estado: generalizado a partir de inventario.php (entrega de HomeMaker); no probado fuera de HomeMaker.
 *   Cambios: CPT, taxonomías y meta de alt por parámetro; formularios buscados en todos los documentos;
 *   experimentos por el gestor de Elementor; guardas de Polylang.
 */
$lista = static function ( $v ) { return array_values( array_filter( array_map( 'trim', explode( ',', (string) $v ) ) ) ); };
$cpts  = $lista( getenv( 'INV_CPT' ) );
$taxs  = $lista( getenv( 'INV_TAX' ) );
$altm  = (string) getenv( 'INV_ALT_META' );
$pll   = function_exists( 'pll_get_post_language' );
$o     = [];
$o['wp']   = get_bloginfo( 'version' );
$o['php']  = PHP_VERSION;
$o['site'] = [
	'home' => home_url(), 'blogname' => get_option( 'blogname' ), 'blogdescription' => get_option( 'blogdescription' ),
	'blog_public' => get_option( 'blog_public' ), 'permalink' => get_option( 'permalink_structure' ),
	'timezone' => wp_timezone_string(), 'WPLANG' => get_option( 'WPLANG' ), 'front' => get_option( 'page_on_front' ),
	'show_on_front' => get_option( 'show_on_front' ),
	'admin_email_domain' => substr( (string) strrchr( (string) get_option( 'admin_email' ), '@' ), 1 ),
];
if ( ! function_exists( 'get_plugins' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
$act = (array) get_option( 'active_plugins' );
foreach ( get_plugins() as $f => $p ) {
	$o['plugins'][] = [ 'file' => $f, 'name' => $p['Name'], 'version' => $p['Version'], 'active' => in_array( $f, $act, true ),
		'author' => wp_strip_all_tags( $p['Author'] ), 'desc' => mb_substr( wp_strip_all_tags( $p['Description'] ), 0, 160 ) ];
}
foreach ( get_mu_plugins() as $f => $p ) { $o['mu'][] = [ 'file' => $f, 'name' => $p['Name'], 'desc' => $p['Description'] ]; }
foreach ( wp_get_themes() as $slug => $t ) {
	$o['themes'][] = [ 'slug' => $slug, 'name' => $t->get( 'Name' ), 'version' => $t->get( 'Version' ),
		'parent' => $t->parent() ? $t->parent()->get_stylesheet() : '', 'active' => get_stylesheet() === $slug ];
}
$o['elementor'] = [ 'core' => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '', 'pro' => defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : '',
	'kit' => (int) get_option( 'elementor_active_kit' ), 'experimentos_activos' => [] ];
if ( class_exists( '\Elementor\Plugin' ) ) {
	$e = \Elementor\Plugin::$instance->experiments;
	foreach ( $e->get_features() as $slug => $f ) { if ( $e->is_feature_active( $slug ) ) { $o['elementor']['experimentos_activos'][] = $slug; } }
}
if ( class_exists( '\Elementor\Modules\GlobalClasses\Global_Classes_Repository' ) ) {
	try { $o['elementor']['clases_globales'] = count( \Elementor\Modules\GlobalClasses\Global_Classes_Repository::make()->all( true )->get_items()->all() ); }
	catch ( \Throwable $t ) { $o['elementor']['clases_globales'] = 'error: ' . $t->getMessage(); }
}
$o['mantenimiento'] = [ 'modo' => get_option( 'elementor_maintenance_mode_mode' ), 'plantilla' => get_option( 'elementor_maintenance_mode_template_id' ),
	'excluir' => get_option( 'elementor_maintenance_mode_exclude_mode' ) ];
$o['polylang'] = array_intersect_key( (array) get_option( 'polylang' ), array_flip( [ 'default_lang', 'force_lang', 'hide_default', 'redirect_lang', 'browser', 'media_support', 'nav_menus', 'sync', 'version' ] ) );
if ( function_exists( 'pll_languages_list' ) ) {
	foreach ( pll_languages_list( [ 'fields' => '' ] ) as $l ) { $o['idiomas'][] = [ $l->slug, $l->locale, $l->name, $l->get_home_url() ]; }
}
$forms = [];
$walk  = function ( $els, $pid ) use ( &$walk, &$forms ) {
	foreach ( (array) $els as $el ) {
		if ( 'form' === ( $el['widgetType'] ?? '' ) ) {
			$s = $el['settings'] ?? [];
			$forms[] = [ 'doc' => $pid, 'el' => $el['id'] ?? '', 'name' => $s['form_name'] ?? '', 'to' => $s['email_to'] ?? '', 'subject' => $s['email_subject'] ?? '',
				'from' => $s['email_from'] ?? '', 'from_name' => $s['email_from_name'] ?? '', 'actions' => $s['submit_actions'] ?? [],
				'fields' => array_map( static function ( $f ) { return ( $f['custom_id'] ?? '' ) . ':' . ( $f['field_type'] ?? 'text' ) . ':' . ( $f['field_label'] ?? '' ) . ( ( $f['required'] ?? '' ) === 'true' ? '*' : '' ); }, $s['form_fields'] ?? [] ) ];
		}
		$walk( $el['elements'] ?? [], $pid );
	}
};
foreach ( get_posts( [ 'post_type' => [ 'page', 'elementor_library' ], 'posts_per_page' => -1, 'post_status' => 'any', 'lang' => '', 'orderby' => 'ID', 'order' => 'ASC' ] ) as $p ) {
	$o['docs'][] = [ 'id' => $p->ID, 'type' => $p->post_type, 'status' => $p->post_status, 'title' => $p->post_title, 'slug' => $p->post_name,
		'lang' => $pll ? pll_get_post_language( $p->ID ) : '', 'tr' => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $p->ID ) : [],
		'url' => 'page' === $p->post_type ? get_permalink( $p->ID ) : '', 'tpl_type' => get_post_meta( $p->ID, '_elementor_template_type', true ),
		'cond' => get_post_meta( $p->ID, '_elementor_conditions', true ),
		'yoast_title' => (bool) get_post_meta( $p->ID, '_yoast_wpseo_title', true ), 'yoast_desc' => (bool) get_post_meta( $p->ID, '_yoast_wpseo_metadesc', true ) ];
	$walk( json_decode( (string) get_post_meta( $p->ID, '_elementor_data', true ), true ), $p->ID );
}
$o['formularios']  = $forms;
$o['condiciones']  = get_option( 'elementor_pro_theme_builder_conditions' );
foreach ( $cpts as $cpt ) {
	foreach ( get_posts( [ 'post_type' => $cpt, 'posts_per_page' => -1, 'post_status' => 'any', 'lang' => '', 'orderby' => 'menu_order', 'order' => 'ASC' ] ) as $r ) {
		$o['cpt'][ $cpt ][] = [ $r->ID, $pll ? pll_get_post_language( $r->ID ) : '', $r->post_status, $r->post_title, get_permalink( $r->ID ) ];
	}
}
foreach ( $taxs as $tax ) {
	$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false, 'lang' => '' ] );
	if ( is_wp_error( $terms ) ) { $o['tax'][ $tax ] = 'error: ' . $terms->get_error_message(); continue; }
	foreach ( $terms as $t ) { $o['tax'][ $tax ][] = [ $t->term_id, $t->slug, $t->name, function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $t->term_id ) : '', $t->count ]; }
}
foreach ( wp_get_nav_menus() as $m ) {
	$o['menus'][ $m->slug ] = [ 'id' => $m->term_id, 'name' => $m->name, 'items' => array_map( static function ( $i ) { return $i->title . ' → ' . $i->url; }, (array) wp_get_nav_menu_items( $m->term_id ) ) ];
}
$o['usuarios']      = array_map( static function ( $u ) { return [ 'login_len' => strlen( $u->user_login ), 'roles' => $u->roles ]; }, get_users( [ 'fields' => 'all' ] ) );
$o['usuarios_total'] = count_users();
global $wpdb;
$o['tabla_envios']  = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'e_submissions' ) );
$o['adjuntos']      = (int) wp_count_posts( 'attachment' )->inherit;
if ( '' !== $altm ) { $o['adjuntos_con_alt_idioma2'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", $altm ) ); }
$o['yoast']         = defined( 'WPSEO_VERSION' ) ? [ 'version' => WPSEO_VERSION, 'premium' => defined( 'WPSEO_PREMIUM_VERSION' ) ? WPSEO_PREMIUM_VERSION : '' ] : null;
$dir                = get_stylesheet_directory();
$o['hijo']          = array_map( static function ( $f ) use ( $dir ) { return str_replace( $dir . '/', '', $f ); }, array_merge( (array) glob( $dir . '/*.*' ), (array) glob( $dir . '/*/*.*' ), (array) glob( $dir . '/*/*/*.*' ) ) );
$o['login_propio']  = get_option( 'sg_security_login_type' );
echo wp_json_encode( $o, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
```

```php
<?php
/**
 * etiquetas.php — SOLO LECTURA. Etiquetas reales del panel, en el idioma del cliente, para escribir
 * la guía de administración con las rutas que verá (y estado actual de lo que se documenta).
 * Uso:  ssh <SSH_ALIAS> 'INV_LOCALE=es_ES wp --path=<WP_PATH> eval-file ~/tmp-<PREFIJO>/etiquetas.php --user=1'
 * Por qué: en el caso de referencia el borrador decía «Plantillas → Constructor de temas» y el panel real
 *   mostraba Elementor Pro en inglés (sin traducción instalada) y el menú reorganizado de Elementor 4.
 *   Cada plugin tiene su text domain y puede no tener traducción: se comprueba, no se recuerda.
 * Parámetros: INV_LOCALE (idioma del panel del cliente; por defecto, el del sitio). Añade a las listas
 *   las etiquetas que vayas a citar en el documento.
 * Estado: generalizado a partir de etiquetas.php (entrega de HomeMaker, con admin-es_ES.mo fijo);
 *   no probado fuera de HomeMaker. La carga explícita de los .mo de plugins es añadido del kit.
 */
$loc = getenv( 'INV_LOCALE' ) ?: get_locale();
echo "locale del sitio: " . get_locale() . " · locale pedido: $loc\n";
load_textdomain( 'default', WP_LANG_DIR . "/admin-$loc.mo" );
$doms = [ 'elementor', 'elementor-pro', 'polylang', 'polylang-pro', 'wordpress-seo' ];
foreach ( $doms as $dom ) {
	$mo = WP_LANG_DIR . "/plugins/$dom-$loc.mo";
	if ( file_exists( $mo ) || file_exists( WP_LANG_DIR . "/plugins/$dom-$loc.l10n.php" ) ) { load_textdomain( $dom, $mo ); }
}
$grupos = [
	'default'       => [ 'Discourage search engines from indexing this site', 'Search engine visibility', 'Reading', 'Save Menu', 'Page Attributes', 'Order', 'Profile', 'Media Library', 'Alternative Text', 'Menus', 'Appearance', 'Settings', 'Users' ],
	'elementor'     => [ 'Theme Builder', 'Tools', 'Clear Files & Data', 'Regenerate Files & Data', 'Maintenance Mode', 'Coming Soon', 'Maintenance', 'Disabled', 'Choose Mode', 'Saved Templates', 'Templates', 'Editor', 'General', 'Style', 'Settings', 'Attributes', 'ID', 'Variables', 'Design System', 'Position', 'Advanced', 'Site Settings', 'Edit with Elementor', 'Publish', 'Global Classes', 'Class Manager', 'Variables Manager' ],
	'elementor-pro' => [ 'Theme Builder', 'Submissions', 'Actions After Submit', 'Email', 'To' ],
	'polylang'      => [ 'Languages', 'Translations', 'Strings translations', 'Menus' ],
];
foreach ( $grupos as $dom => $textos ) {
	foreach ( $textos as $t ) { echo str_pad( $dom, 14 ) . "| $t => " . translate( $t, $dom ) . "\n"; }
}
foreach ( $doms as $dom ) {    // después de traducir: la carga «justo a tiempo» ya se ha intentado
	echo "$dom: " . ( is_textdomain_loaded( $dom ) ? 'traducción cargada' : 'SIN traducción (el cliente lo verá en inglés)' ) . "\n";
}
echo 'mantenimiento: modo=«' . get_option( 'elementor_maintenance_mode_mode' ) . '» plantilla=' . get_option( 'elementor_maintenance_mode_template_id' )
	. ' excluir=' . wp_json_encode( [ get_option( 'elementor_maintenance_mode_exclude_mode' ), get_option( 'elementor_maintenance_mode_exclude_roles' ) ] ) . "\n";
$tpl = (int) get_option( 'elementor_maintenance_mode_template_id' );
if ( $tpl && function_exists( 'pll_get_post_language' ) ) {
	echo 'plantilla «' . get_the_title( $tpl ) . '» lang=' . pll_get_post_language( $tpl ) . ' tr=' . wp_json_encode( pll_get_post_translations( $tpl ) )
		. ' tipo=' . get_post_meta( $tpl, '_elementor_template_type', true ) . "\n";
}
echo 'plugins activos: ' . implode( ', ', (array) get_option( 'active_plugins' ) ) . "\n";
foreach ( wp_get_nav_menus() as $m ) { echo "menú #{$m->term_id} nombre=«{$m->name}» slug={$m->slug}\n"; }
$pl = (array) get_option( 'polylang' );
echo 'nav_menus (Polylang): ' . wp_json_encode( $pl['nav_menus'] ?? null ) . "\n";
echo 'ubicaciones: ' . wp_json_encode( get_theme_mod( 'nav_menu_locations' ) ) . "\n";
echo 'mu-plugins: ' . implode( ', ', array_keys( get_mu_plugins() ) ) . "\n";
echo 'site_icon: ' . get_option( 'site_icon' ) . "\n";
```

*Ojo con la interpolación en PHP:* `"…«$var»…"` se traga la variable porque `»` cuenta como parte
de su nombre; usa `{$var}` o concatena. En el caso, el informe de un script de cadenas salió vacío
por esto aunque la escritura sí se hizo, y `php -l` no lo detecta: revisa los «Undefined variable».

## 6. Controles antes de enviar

```bash
H="<Marca>-Documento-de-entrega.html"
grep -c '{{' "$H"                                                # 0: no quedan marcadores
grep -c 'data-ayuda="Pegar aquí la contraseña antes de exportar"></span>' "$H"   # 1: el campo sigue vacío
grep -ciE 'password=|pass=|token=' "$H"                         # 0: ni contraseñas ni tokens escritos
grep -ciE '<STAGING_URL>|<SSH_ALIAS>|/home/|public_html' "$H"    # 0: nada del staging ni del servidor
ssh <SSH_ALIAS_PROD> 'wp --path=<WP_PATH_PROD> plugin list --status=active'   # contra la tabla 06
ssh <SSH_ALIAS_PROD> 'wp --path=<WP_PATH_PROD> theme list'                     # contra «temas de construcción retirados»
```
- Cada casilla de la sección 13 se ha comprobado. Lo que aún no es cierto se quita o se lista
  como supuesto en el mensaje al usuario («Dos supuestos que conviene confirmar»).
- Las rutas del panel coinciden con `etiquetas.php`.
- El PDF se ha leído página a página tras la última corrección.

## 7. Correo de cierre (plantilla)

Lo envía el usuario. Los corchetes se rellenan a mano; adjunta el PDF.

```text
Asunto: <MARCA>: entrega y cierre del desarrollo del sitio web en WordPress

Hola, [nombre]:

Les confirmamos que el desarrollo del nuevo sitio web de <MARCA> en WordPress está terminado,
y con este correo damos por finalizada la entrega.

Qué incluye:
- El sitio completo en [idiomas], construido a partir del prototipo aprobado: [páginas].
- [Contenido dinámico, p. ej. el recetario: N elementos por idioma, filtro y ficha].
- Los formularios [cuáles] en [idiomas], que llegan a los destinatarios acordados. Cada envío
  queda además guardado en el panel.
- Todo el contenido (textos, imágenes, menús y formularios) se edita desde el panel de
  WordPress, sin tocar código.

Estado del sitio:
[Por solicitud suya, el sitio está en modo «Próximamente» en <DOMINIO>. Los visitantes ven la
página de próxima apertura y el sitio completo sigue disponible desde el administrador. Abrirlo
al público no requiere desarrollo adicional; se hará cuando ustedes lo indiquen. Este estado
no afecta a la finalización de la entrega.]

Documentación y acceso:
Adjunto el documento técnico de entrega y cierre. Explica cómo está construido el sitio, qué
plugins usa, cómo se administran los idiomas, e incluye una guía rápida de edición y el acceso
de administración: https://<DOMINIO>/<LOGIN_SLUG>
Como el documento contiene la credencial de acceso, les pedimos guardarlo en un lugar seguro y
cambiar la contraseña en el primer ingreso.

Para cerrar formalmente, les agradecemos que nos confirmen la recepción respondiendo a este
correo, o que nos devuelvan firmada el acta de entrega de la sección 14 del documento.

Gracias por la confianza durante todo el proyecto. Quedo atento a cualquier duda.

Saludos,
[Nombre]
[Cargo]
```

## 8. Mensaje al usuario al entregar

- El archivo (SendUserFile) y cómo exportarlo: rellenar los campos resaltados, **Imprimir →
  Guardar como PDF**, A4, márgenes predeterminados, con gráficos de fondo.
- Qué debe rellenar él: contraseña (no la pegues en el chat), quién recibe, firmas.
- Los supuestos del documento que aún no son ciertos y en qué tarea de lanzamiento quedan.
- Lo que la revisión encontró y se arregló en el sitio, y lo que se descartó y por qué.
- Recordatorios de limpieza con fecha (volcados con datos personales, copias locales).
