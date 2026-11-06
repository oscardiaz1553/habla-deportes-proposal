# SEO · Yoast + Polylang + Elementor 4

Configuración de Yoast por script, su convivencia con Polylang, filtros de salida, schema propio,
mapa 301/410 y lanzamiento. Verificado sobre Yoast SEO / Premium 27.7 y Polylang Pro 3.8.6 (idioma
base sin prefijo, segundo idioma en `/en/`, `force_lang=1`, `browser=0`, `hide_default=1`).
Scripts: `../scripts/wp-cli/` (Yoast, render) y `../scripts/tema-hijo/seo.plantilla.php`
(filtros). Cadenas de Polylang por script: `../../wp-polylang/references/cadenas-menus-slugs.md`. Runbook de migración: skill
`wp-migracion`.

---

## 1. Guardas de staging (lo primero tras instalar Yoast)

- `blog_public=0` en staging hasta el lanzamiento, que se hace **solo en producción**.
- **IndexNow de Yoast Premium ignora `blog_public`.** `index-now-ping.php` solo comprueba
  `enable_index_now` y `wp_get_environment_type() === 'production'`, y sin `WP_ENVIRONMENT_TYPE`
  definido el entorno ES production. En el caso avisó a Bing/Yandex con URLs de staging en cada
  publicación hasta que se apagó. Primer paso: `../scripts/wp-cli/yoast-guard.php`
  (`enable_index_now` y `tracking` a false). Se reactiva solo en producción, al lanzar.
- Si defines `WP_ENVIRONMENT_TYPE='staging'`, recuerda que `wp yoast index` solo corre en
  production (`indexable-helper.php` + `environment-helper.php`).
- Verificar: `wp option get wpseo --format=json` → `enable_index_now: false`.

## 2. Configurar Yoast por script

La interfaz de Yoast no es scriptable y escribir las opciones a pelo salta validadores y
watchers. Plantilla completa: `../scripts/wp-cli/yoast-config.plantilla.php` + un JSON de datos
por idioma (`yoast-datos.ejemplo.json`).

**Reglas (cada una costó un intento fallido o salió de leer el código de Yoast)**
- Todo por `WPSEO_Options::set()` y relectura con `WPSEO_Options::get()`; si el validador cambió o
  rechazó el valor, el script lo imprime como «REVISAR».
- `company_logo` (URL) **antes** que `company_logo_id`: el validador de `company_logo` reinicia
  `_id` y `_meta`.
- Meta de post `_yoast_wpseo_*` siempre **string** con `wp_slash` (un int se guarda vacío).
- `focuskeywords` = JSON `[{"keyword":"…","score":0}]`; `keywordsynonyms` = JSON array;
  `opengraph-image` (URL) + `opengraph-image-id`; `is_cornerstone` = `'1'`;
  `schema_page_type` = `CollectionPage` / `AboutPage` / `ContactPage`.
- `%%cf_<meta>%%` sobrevive al saneado de Yoast: plantillas de metadescripción de CPT desde
  campos (`metadesc-<cpt>` = `%%cf_<meta_intro>%%`). Solo con campos completos: en el caso se
  descartó `%%cf_<tiempo>%%` porque el campo omitía horas de nevera.
- `update_post_meta` dispara el watcher y el indexable se reconstruye al terminar la petición. SQL,
  importaciones o search-replace exigen `wp yoast index --reindex --skip-confirmation`.
- `first_time_install=false` (Yoast estaba instalado sin configurar: indexables sin construir).
- Datos separados de la lógica: un JSON por idioma con, por ID, `title`, `metadesc`, `focuskw`,
  `related`, `og_title`, `og_desc`, `og_image`, `schema`, `cornerstone`, `synonyms`. Valida
  longitudes antes de escribir (título ≤ 60 y descripción ≤ 156 ya expandidas las variables).
- Copy: de las fuentes del cliente; ningún claim nuevo. Si dos textos del cliente se contradicen
  (p. ej. un «100 %» general frente a un producto que no lo cumple), el título no lo afirma y se señala.

**Lo que se configuró en el caso** (valores genéricos; los textos, del cliente):

| Grupo | Opciones |
|---|---|
| Identidad | `blogname` = solo la marca; `website_name`, `alternate_website_name`, `company_or_person=company`, `company_name`, `company_alternate_name`, `company_logo(+_id)`, `org-description`, `org-founding-date` |
| Títulos | `separator=sc-mdash`; `title-page` = `%%title%% %%page%% %%sep%% %%sitename%%`; `title-<cpt>` = `%%title%% %%sep%% %%sitename%%`; `social-title-*` vacíos |
| CPT | `metadesc-<cpt>`, `social-description-<cpt>`, `schema-page-type-<cpt>=WebPage`, `schema-article-type-<cpt>=None`, `noindex-<cpt>=false`, `display-metabox-pt-<cpt>=true` |
| No indexable | `noindex-tax-<tax_auxiliar>=true` + `display-metabox-tax-<tax>=false`; `noindex-tax-category/post_tag` (sin blog); `noindex-<tipo>` y sin metabox para los tipos públicos de plugins (`e-floating-buttons`, `elementor_library`…); `disable-post_format`, `disable-attachment`, `disable-author`, `noindex-author-wpseo`, `noindex-author-noposts-wpseo`, `disable-date`, `noindex-archive-wpseo` |
| Migas | `breadcrumbs-enable=true`, `breadcrumbs-home` (idioma base), `breadcrumbs-display-blog-page=false` |
| Redes | `opengraph`, `twitter`, `twitter_card_type=summary_large_image`, `og_default_image(+_id)` |
| Limpieza a true | `remove_shortlinks`, `remove_rest_api_links`, `remove_rsd_wlw_links`, `remove_oembed_links`, `remove_generator`, `remove_pingback_header`, `remove_powered_by_header`, `remove_emoji_scripts`, todos los `remove_feed_*`, `remove_atom_rdf_feeds`, `search_cleanup*`, `deny_search_crawling`, `enable_xml_sitemap` |
| A false | `enable_index_now`, `tracking`, `enable_enhanced_slack_sharing`, `semrush_integration_active`, `wincher_integration_active`, `enable_llms_txt`, `clean_campaign_tracking_urls`, `clean_permalinks`, `deny_wp_json_crawling`, `deny_gptbot`, `deny_ccbot`, `deny_google_extended`, `first_time_install` |

Con `remove_emoji_scripts` Yoast ya quita los emojis: no actives además la opción del optimizador.

## 3. Identidad y títulos

- `%%sitename%%` = `get_bloginfo('name')`. Con el eslogan dentro de `blogname` los títulos salían
  «Inicio - Marca — Eslogan». Regla: `blogname` = solo la marca, separador `sc-mdash`, y el
  eslogan en el título de la portada (`%%sitename%% %%sep%% <eslogan>`).
- Cambiar `blogname` cambia también `og:site_name`, el título del feed y el nombre de WebSite si
  `website_name` quedara vacío, y **deja huérfana su traducción** en Polylang (la clave es el
  texto de origen). Avisa a quien lleve las cadenas y rehazla.
- Verificar el `<title>` de las páginas y de dos singles.

## 4. Indexables, reindexado y caché

- `wp yoast index --reindex --skip-confirmation`: después de escribir la configuración, después de
  migrar o de un search-replace y **otra vez después de `blog_public=1`** (el noindex vive en el
  indexable como `is_robots_noindex`).
- El canonical no aparece mientras hay noindex (`canonical-presenter.php`): no lo des por roto en
  staging.
- Purga la caché después de cada cambio de Yoast: `robots.txt` y los sitemaps también se cachean.
- Verificar: `wp db query "SELECT object_id,object_sub_type,title,description,is_public FROM
  $(wp db prefix)yoast_indexable WHERE object_type='post' AND object_sub_type IN ('page','<cpt>')"`.

## 5. Sitemaps y taxonomías

**Síntoma.** `<tax>-sitemap.xml` listaba URL `/?taxonomy=<tax>&term=…` que respondían 200
(contenido duplicado). **Causa.** La taxonomía de filtro era `public` sin `publicly_queryable` ni
rewrite. **Regla.** Taxonomías auxiliares con `noindex-tax-<tax>=true` y
`display-metabox-tax-<tax>=false`; **no** la uses como `post_types-<cpt>-maintax` (las migas
apuntarían a esas URL). Además: noindex en category/post_tag si no hay blog, en los tipos públicos
de plugins y archivos de autor/fecha/adjuntos desactivados.
**Verificar:** `sitemap_index.xml` solo con `page-` y `<cpt>-sitemap`; `<tax>-sitemap.xml` → 404;
`/?author=1` redirige a la portada.

Con `force_lang=1` hay **un solo sitemap** para todos los idiomas. Polylang excluye los idiomas
inactivos del hreflang y del sitemap de Yoast.

## 6. `clean_permalinks` siempre en false con Elementor

Con la opción activa Yoast redirige los parámetros que no conoce y rompe el filtro por taxonomía de
Pro (`?e-filter-<id>-<tax>`), los enlaces con `?cat=` y la vista previa (`?elementor-preview`).
Verificar en incógnito que el filtro del listado y la vista previa funcionan.

## 7. Yoast + Polylang

| Qué | Comportamiento | Qué hacer |
|---|---|---|
| Meta de Yoast | Polylang **copia** `_yoast_wpseo_*` a cada traducción nueva (`wpseo.php:431`): el segundo idioma nace con título y descripción del base | Escribir la meta del segundo idioma con copy propio a partir de su contenido real (mapa base→segundo con `pll_get_post`) y el mismo script (`YOAST_DATA=datos-<IDIOMA_2>.json`) |
| Plantillas | `title-*`, `metadesc-*`, `social-*`, `breadcrumbs-*`, `company_name`, `title-404-wpseo` se traducen como cadenas del grupo «wordpress-seo» con **el texto de origen como clave** | Traducirlas por script con `PLL_MO` (skill `wp-polylang`). Si cambias un texto de origen, rehaz su traducción: queda huérfana |
| No traducibles | `org-description`, `website_name`, `alternate_website_name` | La descripción de Organization del segundo idioma por filtro `wpseo_schema_organization` a **prioridad 99** (Premium la añade después de la 10) |
| hreflang | Lo imprime **Polylang**, no Yoast. Sin `x-default` cuando el idioma base va sin prefijo | Filtro `pll_rel_hreflang_attributes` (§9) |
| Idioma vacío | Una portada del segundo idioma sin traducir responde 200 y el base la anuncia por hreflang | Antes de lanzar: traducir o desactivar el idioma |

- **Un solo dueño** de las cadenas de Polylang entre sesiones: dos escritores se pisan
  `_pll_strings_translations`.
- Si otra sesión **regenera** el segundo idioma desde el base por script, pídele que preserve
  `_yoast_wpseo_*` y verifica después (alt en el idioma, `sizes`, meta).
- Verificar: extrae `yoast-schema-graph` en `/en/…` y comprueba el idioma de Organization, títulos
  y descripciones.

## 8. Título del 404 (y otras plantillas nuevas) por idioma

**Síntoma.** `/en/<no-existe>/` mostraba «Página no encontrada — Marca» en el `<title>`.
**Causa.** Polylang registra `title-404-wpseo` como cadena traducible pero, sin traducción, devuelve
la del idioma base. **Regla.** Tras crear cualquier plantilla nueva revisa las cadenas de Yoast que
le afectan (`title-404-wpseo`, `title-search-wpseo`, separador de migas) y tradúcelas en la MO del
idioma, conservando `%%sep%% %%sitename%%`:

```php
$es     = (string) ( get_option( 'wpseo_titles' )['title-404-wpseo'] ?? '' );
$en_txt = preg_replace( '/^P[áa]gina no encontrada/u', 'Page not found', $es );
$en     = PLL()->model->get_language( 'en' );   // objeto PLL_Language, no el slug
$mo     = new PLL_MO();
$mo->import_from_db( $en );
$mo->add_entry( $mo->make_entry( $es, $en_txt ) );
$mo->export_to_db( $en );
echo "EN: «{$en_txt}»\n";   // con llaves: sin ellas PHP lee el » que sigue como parte del nombre de la variable
```
Un informe vacío no significa que la escritura fallara: compruébalo aparte. Para todas las cadenas
de Yoast a la vez (grupo `yoast_titulos`): `../../wp-polylang/scripts/cadenas.plantilla.php`.
**Verificar:** `curl -s https://<DOMINIO>/en/nada-xq/ | grep -o '<title>[^<]*'` (usa slugs absurdos:
`/product` da 301 por `redirect_guess_404_permalink`, no 404).

## 9. Filtros de salida (una línea cada uno)

Todos en `../scripts/tema-hijo/seo.plantilla.php`, independientes entre sí:

| Síntoma encontrado por la revisión SEO | Causa | Filtro |
|---|---|---|
| `WebPage.name` con la entidad literal `&#8212;` | separador `sc-mdash` | `wpseo_schema_webpage` + `html_entity_decode( …, ENT_QUOTES \| ENT_HTML5, 'UTF-8' )` |
| `og:locale` es_ES mientras Polylang anunciaba es_LA | Polylang mapea es_PR a `facebook=es_LA`; Yoast valida contra su lista | `wpseo_og_locale` (es_ES → el que anuncie Polylang) |
| `primaryImageOfPage` = un logo o un recorte | las páginas de Elementor no tienen imagen destacada; Yoast toma la primera del contenido | `wpseo_schema_main_image_id` → `_yoast_wpseo_opengraph-image-id` en `is_page()` |
| sin hreflang `x-default` | Polylang no lo imprime con el idioma base sin prefijo | `pll_rel_hreflang_attributes` añadiendo `x-default` = idioma base |
| `SearchAction` sin buscador en el sitio | Yoast lo emite por defecto | `add_filter( 'disable_wpseo_json_ld_search', '__return_true' )` |
| Organization en el idioma base dentro de `/en/` | `org-description` no es traducible | `wpseo_schema_organization`, prioridad 99 |
| `og:description` automático con «Reproducir vídeo Pausar vídeo» | derivado del contenido de Elementor | escribir `metadesc` y `og_desc` explícitos en todas las páginas (no es un filtro) |

## 10. Schema propio: una pieza en el grafo de Yoast

Yoast solo genera WebPage/WebSite/Organization (+ migas). Para un CPT con datos en meta y
`post_content` vacío (recetas en el caso) se añade una pieza:

```php
add_filter( 'wpseo_schema_graph_pieces', function ( $pieces, $context ) {
	if ( '<cpt>' === ( $context->indexable->object_sub_type ?? '' ) ) {
		require_once __DIR__ . '/schema-<cpt>.php';   // dentro del filtro: sin Yoast no se carga
		$pieces[] = new Pfx_Schema_Tipo();
	}
	return $pieces;
}, 11, 2 );
```
```php
use Yoast\WP\SEO\Config\Schema_IDs;
use Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece;

class Pfx_Schema_Tipo extends Abstract_Schema_Piece {
	public $identifier = 'pfx_tipo';   // filtros wpseo_schema_needs_pfx_tipo / wpseo_schema_pfx_tipo
	public function is_needed() {
		return 'post' === $this->context->indexable->object_type
			&& '<cpt>' === $this->context->indexable->object_sub_type
			&& $this->context->has_image;   // + los campos que Google exige para el tipo
	}
	public function generate() {
		$page = $this->context->main_schema_id;
		$html = $this->helpers->schema->html;
		$data = [
			'@type'            => '<Tipo schema.org>',
			'@id'              => $page . '#<tipo>',
			'name'             => $html->smart_strip_tags( $this->helpers->post->get_post_title_with_fallback( (int) $this->context->id ) ),
			'mainEntityOfPage' => [ '@id' => $page ],
			'image'            => [ $this->context->main_image_url ],
			'datePublished'    => $this->helpers->date->format( $this->context->post->post_date_gmt ),
			'dateModified'     => $this->helpers->date->format( $this->context->post->post_modified_gmt ),
		];
		if ( 'company' === $this->context->site_represents ) {
			$org               = $this->context->site_url . Schema_IDs::ORGANIZATION_HASH;
			$data['author']    = [ '@type' => 'Organization', '@id' => $org, 'name' => $html->smart_strip_tags( $this->context->company_name ), 'url' => $this->context->site_url ];
			$data['publisher'] = [ '@id' => $org ];
		}
		// … campos propios leídos de la meta del CPT
		return $this->helpers->schema->language->add_piece_language( $data );
	}
}
```
Reglas del caso (Recipe): `is_needed` exige imagen **y** los campos obligatorios (ingredientes);
`recipeYield` = `[número, texto]`; las líneas acabadas en «:» son subtítulos, no ingredientes;
`HowToStep.name` cuando la línea es «Nombre: texto» (la misma regex que pinta la ficha); **no
emitas `totalTime`** si el campo no incluye reposos (Google lo mostraría como tiempo total); no
inventes `aggregateRating`, `nutrition` ni `video`. La página archivo en las migas:
`wpseo_breadcrumb_links` + `array_splice` con la página en el idioma del post (`pll_get_post`).
El análisis de contenido de Yoast sale gris con `post_content` vacío: avisa al cliente para que no
pegue texto ahí. **Verificar:** extracción de `yoast-schema-graph` con Python y la prueba de
resultados enriquecidos de Google (funciona aunque haya noindex).

## 11. Yoast dentro del editor de Elementor

Yoast añade su pestaña junto a «Globales» del panel de Elementor buscando ese marcado con una
expresión regular; aparece según `display-metabox-pt-*` y guarda con el botón Publicar. Si una
actualización de Elementor 4 cambia el marcado, la pestaña desaparece sin avisar: la alternativa
es el panel de Yoast en la pantalla de edición de WordPress. Las plantillas del Theme Builder no
tienen panel (`display-metabox-pt-elementor_library=false`). Documéntalo en la entrega.

## 12. Mapa 301/410 desde la base de datos del sitio viejo

**Síntoma.** El mapa del prototipo cubría unas 20 URL; la BD vieja tenía 17.615 fichas de un CPT
de plugin, categorías, testimonios, slides, 18 entradas con `/%category%/%postname%/` y páginas
jerárquicas. La primera versión del mapa además capturaba una URL nueva.
**Causa.** El prototipo solo conoce las páginas visibles; los CPT de plugins y las entradas viejas no.

**Receta**
1. Del dump del sitio viejo lee `permalink_structure`, `rewrite_rules` (tipos públicos) y
   `wp_posts` publicados **por tipo público** con su ruta jerárquica y sus categorías.
   **Los dumps tienen datos personales** (en el caso, 2.524 `flamingo_inbound` y 2.115
   `flamingo_contact` de formularios viejos): filtra por tipo público antes de imprimir nada,
   nunca vuelques correos ni los guardes en informes, y borra el dump al terminar (con permiso).
2. Una `RewriteRule` por grupo al equivalente nuevo; ancla de sección con flag `NE` (el `#` viaja
   tal cual); `[G]` (410) para lo que no tiene equivalente. Si el sitio viejo estaba en otro
   idioma, el destino es el equivalente en ese idioma (`/en/…`).
   ```apache
   RewriteRule ^(?:products/)?<slug-viejo>/?$      /en/<listado>/#<ancla>   [R=301,L,NC,NE]
   RewriteRule ^<cpt-viejo>(?:-categories)?/.+     /en/<pagina-nueva>/      [R=301,L,NC]
   RewriteRule ^(?:<tipo-sin-equivalente>)(?:/.*)?$  -                     [G,L,NC]
   ```
3. Las URL de primer nivel que siguen existiendo no se redirigen.
4. En `.htaccess`, en un bloque propio **encima de `# BEGIN WordPress`** (no en un plugin ni en los
   Redirects de Yoast Premium), dentro de su `<IfModule>` con `RewriteBase /`.
5. **Pruébalo antes de subir** con `../scripts/medicion/probar-301.py mapa.conf viejas.tsv
   nuevas.txt`: cada URL vieja a su destino y **ninguna** URL nueva capturada (todas las páginas y
   singles de ambos idiomas, login, `wp-json`, sitemap, robots). En el caso: 42 viejas y 24 nuevas,
   «fallos: ninguno».
6. Si el segundo idioma no se lanza: `sed -i 's#/en/#/#g' <mapa>.conf`.
7. **cPanel/Bluehost:** las `[G]` devolvían la página 410 global de cPanel, WordPress acababa en
   404. Añade `ErrorDocument 410 default` al `.htaccess` del sitio (respeta el `AddHandler` de PHP
   que pone cPanel y copia el archivo antes de editarlo).
8. En vivo: `curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' https://<DOMINIO>/<vieja>/`;
   retiradas → 410; `/no-existe-xq/` → 404.

## 13. Cortina y SEO

- En un dominio con historial: **503 + `Retry-After`** (cortina estática o modo Mantenimiento de
  Elementor, que envía `Retry-After: 600`), nunca «Próximamente» de Elementor (200 + noindex; y
  `robots.txt` como HTML 200): puede desindexar las URL existentes. «Próximamente» solo en un
  staging sin historial.
- Mantenla días, no semanas: con 503 también `robots.txt` y los sitemaps dan 503.
- Con la cortina puesta, PageSpeed mide la cortina (SEO 58 en el caso). Pide a quien la gestione
  que no la active hasta que digas «listo» y mide antes o justo después del lanzamiento.
- Al quitarla, purga (cambiar el modo no purga).
Verificación detrás de la cortina sin exponer la llave: `medicion.md` §5.

## 14. Lanzamiento (parte SEO)

Orden acordado con la sesión de migración (runbook completo en `../../wp-migracion/references/runbook.md` y
`../../wp-migracion/references/lanzamiento.md`):
1. search-replace en el orden del runbook (F5 de `../../wp-migracion/references/runbook.md`):
   **primero la ruta absoluta del servidor** que Yoast guarda en `wpseo_titles` y en los
   indexables (normal y en JSON escapado), a la ruta final; **después el dominio** sin esquema
   (cubre también `https:\/\/…`) y la normalización a https. Si el dominio va antes, la ruta del
   staging (que en SiteGround contiene el dominio) queda como una ruta inexistente con el
   dominio nuevo. Luego `wp elementor replace_urls` y `wp elementor flush_css` (formas con guion
   bajo: trampa A10 de `elementor4-atomico`). Yoast guarda URL absolutas (`company_logo`,
   `og_default_image`, imágenes OG por página): sin el reemplazo y el reindexado los canonical,
   OG y URL de fuentes/CSS apuntan a staging.
2. Comprueba que viajaron `mu-plugins/`, `fonts/` y pósters.
3. `.htaccess`: https y dominio canónico, cortina, 301/410 + `ErrorDocument 410 default`, WebP
   por `Accept` + `Vary`, caché de estáticos, compresión.
4. `blog_public=1` **antes** de retirar la cortina (si no, hay un intervalo con el sitio visible y
   noindex) → modo de mantenimiento a `''` o fuera el bloque de cortina → IndexNow a true **solo en
   producción** → `wp yoast index --reindex --skip-confirmation` → `flush_css` → purga.
5. Comprueba `robots.txt` (con `Sitemap:`), `sitemap_index.xml`, `index, follow`, canonical,
   hreflang de ambos idiomas + x-default con el dominio final, y las 301 (`checks-html.sh`).
6. Search Console (verificación por DNS) y envío de `/sitemap_index.xml`.
7. Caché de página del hosting nuevo (D11 del runbook: recomendada antes o justo al lanzar, la
   decide el usuario; `rendimiento.md` §1) y PageSpeed 7 URL × 2 × 3 (`medicion.md`).
8. Pendientes que son del cliente, no tuyos: perfiles sociales (`sameAs`), correo y teléfono
   públicos de la organización (los «XXX-XXXX» del material no se publican), política de
   privacidad enlazada desde formularios y pie.

Propuesto por el análisis de Yoast y **no verificado** en el caso: activar `llms.txt` al lanzar
(`llms_txt_selection_mode=manual`, `about_us_page`, `contact_page`, `other_included_pages`,
`enable_llms_txt=true` y `wp cron event run wpseo_llms_txt_population`). Si lo usas, verifícalo.

## 15. Verificaciones de SEO

```bash
# <head> de Yoast por página
curl -s "https://<STAGING_URL>/<ruta>/" | sed -n '/<!-- This site is optimized/,/<!-- \/ Yoast SEO/p' \
  | grep -E '<title>|name="description"|og:title|og:image"|og:site_name|hreflang|robots'
# tipos del grafo JSON-LD
curl -s "https://<STAGING_URL>/<single>/" | python3 -c "import sys,re,json; h=sys.stdin.read(); g=json.loads(re.search(r'yoast-schema-graph\">(.*?)</script>',h,re.S).group(1))['@graph']; print([n.get('@type') for n in g])"
# sitemap y robots
curl -s "https://<STAGING_URL>/sitemap_index.xml" | grep -o '<loc>[^<]*'
curl -s -o /dev/null -w '%{http_code}\n' "https://<STAGING_URL>/<tax>-sitemap.xml"   # 404
curl -s "https://<STAGING_URL>/robots.txt"
# opciones
wp option get wpseo_titles --format=json | python3 -c "import sys,json; d=json.load(sys.stdin); print({k:d.get(k) for k in ['separator','website_name','company_name','company_logo_id','breadcrumbs-home']})"
```
Todo junto por URL: `../scripts/medicion/checks-html.sh`. Detrás de la cortina:
`RENDER_URL=/ruta/ wp eval-file render.php` y los mismos greps sobre el archivo.
