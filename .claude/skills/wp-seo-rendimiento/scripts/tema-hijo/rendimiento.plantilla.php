<?php
/**
 * <PREFIJO> — rendimiento (PageSpeed) para Elementor 4 atómico (V4) + Hello + tema hijo.
 *
 * Plantilla del módulo inc/rendimiento.php del caso de referencia (HomeMaker, Lighthouse local:
 * móvil 89–99 y escritorio 88–100 de mediana en staging con caché; portada 93/99 en producción sin
 * caché de página, una corrida detrás de la cortina).
 * Estado: generalizado, NO probado fuera de HomeMaker. El original sí está probado.
 *
 * Instalación:
 *   1. Copia a wp-content/themes/<child>/inc/rendimiento.php y sustituye el prefijo:
 *      sed -i '' 's/pfx_/<PREFIJO>_/g; s/PFX_/<PREFIJO_MAYÚSCULAS>_/g; s/pfx-/<PREFIJO>-/g' rendimiento.php
 *   2. Rellena CONFIGURACIÓN. Los bloques 1, 4b, 4c, 5, 6, 7 y el respaldo de font-family se
 *      apagan dejando su constante vacía o false. Los bloques 3 (jQuery al pie), 4a (sizes e id
 *      de la imagen V4) y 8 (normalizador de <img>) no tienen constante: van siempre; para
 *      quitarlos, borra el bloque.
 *   3. functions.php: require_once __DIR__ . '/inc/rendimiento.php';  (si otra sesión es dueña de
 *      functions.php, solo esa línea, en el orden acordado).
 *   4. php -l, subir, wp elementor flush-css, purgar la caché y medir (references/medicion.md).
 * Bloques:
 *   1 fuentes autoalojadas + respaldo con métricas (sin CLS)   5 revelado temprano tras </main>
 *   2 envoltorio seguro de transformadores V4 + font-family    6 sin CSS de bloques en fichas de CPT
 *   3 jQuery al pie                                           7 vídeo de fondo de portada tras load
 *   4 imágenes: sizes, id duplicado, prioridad LCP, póster    8 normalizador de <img> (un búfer)
 * Riesgos: depende de hooks y marcado internos de Elementor 4
 *   (elementor/atomic-widgets/{styles,settings}/transformers/register, handles elementor-gf-*,
 *   plantilla de e-background-video, módulo image-loading-optimization). Tras CADA actualización
 *   de Elementor: php -l, render de 3 páginas sin «critical error» y checks-html.sh.
 *   Las fuentes y pósters sin ?ver= se cachean un año: si cambian, cámbiales el nombre.
 */

defined( 'ABSPATH' ) || exit;

/* ================= CONFIGURACIÓN ================= */

/* Familias autoalojadas → nombre de su familia de respaldo. */
const PFX_FUENTES = [
	// 'Familia' => 'Familia Fallback',
];
/* Handles de Elementor que se dejan de imprimir (SOLO los de las familias autoalojadas; el resto
   de Google Fonts que el cliente elija en el editor se sigue cargando). */
const PFX_FUENTES_HANDLES = ''; // '/^elementor-gf-(local-)?(familia1|familia2)$/'
/* Caras: [ familia, peso ('400' o '100 900' si es variable), archivo en fonts/, subconjunto ]. */
const PFX_FUENTES_CARAS = [
	// [ 'Familia', '100 900', 'familia-latin-ext.woff2', 'latin-ext' ],
	// [ 'Familia', '100 900', 'familia-latin.woff2', 'latin' ],
];
/* Solo los latin (los que usa el texto en español e inglés). */
const PFX_FUENTES_PRELOAD = [
	// 'fonts/familia-latin.woff2',
];
/* Métricas del respaldo: cópialas del build del prototipo si es Next.js con next/font:
   grep -rhoE "@font-face\{font-family:(X|Y) Fallback[^}]*\}" .next | sort -u */
const PFX_FUENTES_METRICAS = [
	// 'Familia Fallback' => 'ascent-override:88.96%;descent-override:21.28%;line-gap-override:0%;size-adjust:98.7%',
];
/* unicode-range de Google Fonts (css2, 2026). Compruébalos en el CSS que descargues. */
const PFX_RANGOS = [
	'latin'     => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
	'latin-ext' => 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF',
];
/* Scripts propios que leen window.jQuery: se les añade la dependencia al mover jQuery al pie. */
const PFX_JQUERY_DEPS = [
	// '<handle-propio>',
];
/* Prioridad de imágenes (bloque 4b). true = activo. Clase de la imagen que es LCP en las páginas
   interiores (la única que conserva fetchpriority="high"); '' = ninguna imagen de contenido la
   conserva (portadas cuyo LCP es un fondo CSS o un texto). */
const PFX_PRIORIDAD = false;
const PFX_LCP_CLASE = ''; // 'pfx-banner__img'
/* Póster de la portada pintado como fondo CSS (bloque 4c): rutas en el child y corte móvil. */
const PFX_POSTER = [
	// 'movil' => 'img/hero-poster-m.webp', 'escritorio' => 'img/hero-poster.webp', 'corte' => 767,
];
/* Revelado temprano (bloque 5): solo si el child usa html.pfx-js + [data-pfx-reveal]/[data-pfx-stagger]. */
const PFX_REVELADO = false;
/* CPT cuya ficha no usa bloques de Gutenberg (bloque 6). */
const PFX_CPT_SIN_BLOQUES = [
	// '<cpt>',
];
/* <video> de fondo de la portada (bloque 7). '' = apagado. Cambio VISIBLE: pide aprobación. */
const PFX_VIDEO_SEL = ''; // '.pfx-hero video'

/* ================= utilidades ================= */

function pfx_rend_url( $ruta ) {
	return get_stylesheet_directory_uri() . '/' . $ruta;
}

function pfx_rend_editor() {
	return is_admin() || isset( $_GET['elementor-preview'] );
}

/* ================= 1. fuentes ================= */

if ( PFX_FUENTES_HANDLES ) {
	add_filter( 'style_loader_tag', function ( $tag, $handle ) {
		return preg_match( PFX_FUENTES_HANDLES, $handle ) ? '' : $tag;
	}, 10, 2 );
}

if ( PFX_FUENTES_CARAS ) {
	add_action( 'wp_head', function () {
		foreach ( PFX_FUENTES_PRELOAD as $f ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( pfx_rend_url( $f ) ) );
		}
		$css = '';
		foreach ( PFX_FUENTES_CARAS as [ $familia, $peso, $archivo, $subconjunto ] ) {
			$css .= sprintf( "@font-face{font-family:%s;font-style:normal;font-weight:%s;font-display:swap;src:url(%s) format('woff2');unicode-range:%s}", $familia, $peso, esc_url( pfx_rend_url( 'fonts/' . $archivo ) ), PFX_RANGOS[ $subconjunto ] );
		}
		/* respaldo: Arial o sus equivalentes de Linux/Android (PSI corre en Linux: sin Liberation
		   Sans/Arimo el respaldo allí no existiría) con las métricas de cada fuente */
		$arial = 'local("Arial"),local("ArialMT"),local("Liberation Sans"),local("Arimo")';
		foreach ( PFX_FUENTES_METRICAS as $respaldo => $metricas ) {
			$css .= '@font-face{font-family:"' . $respaldo . '";src:' . $arial . ';' . $metricas . '}';
		}
		echo "<style id=\"pfx-fuentes\">$css</style>\n";
	}, 1 );
}

/* ================= 2. envoltorio seguro de transformadores V4 =================
   Nunca heredes de un transformador concreto (Font_Family_Transformer, Image_Transformer): si una
   actualización añade tipo de retorno o parámetros, marca la clase final o mueve
   Props_Resolver_Context, la subclase da un E_COMPILE_ERROR en TODAS las páginas (el registro se
   construye en cada render atómico y tras cada limpieza de CSS, justo al actualizar). Se toma el
   transformador ya registrado y se registra un envoltorio que solo extiende el abstracto, sin
   tipos; si la firma base cambia, no se hace nada: el fallo posible es «deja de añadir el
   respaldo», nunca un fatal. */
function pfx_envolver_transformador( $transformers, $clave, callable $despues ) {
	$base = '\Elementor\Modules\AtomicWidgets\PropsResolver\Transformer_Base';
	if ( ! class_exists( $base ) || ( new \ReflectionMethod( $base, 'transform' ) )->hasReturnType() ) {
		return;
	}
	$orig = $transformers->get( $clave, false );
	if ( ! $orig instanceof $base ) {
		return;
	}
	$transformers->register( $clave, new class( $orig, $despues ) extends \Elementor\Modules\AtomicWidgets\PropsResolver\Transformer_Base {
		private $orig;
		private $despues;
		public function __construct( $orig, $despues ) {
			$this->orig    = $orig;
			$this->despues = $despues;
		}
		public function transform( $value, $context = null, ...$resto ) {
			return ( $this->despues )( $value, fn() => $this->orig->transform( $value, $context, ...$resto ) );
		}
	} );
}

/* Las clases V4 escriben la familia sin respaldo (font-family:Familia). Con esto salen como
   `Familia, "Familia Fallback", sans-serif`. El nav-menu clásico (V3) usa
   var(--e-global-typography-*) y no pasa por aquí: su respaldo va a mano en style.css. */
if ( PFX_FUENTES ) {
	add_action( 'elementor/atomic-widgets/styles/transformers/register', function ( $transformers ) {
		pfx_envolver_transformador( $transformers, 'font-family', function ( $value, $original ) {
			$familia = is_string( $value ) ? trim( $value, " \t\"'" ) : '';
			return isset( PFX_FUENTES[ $familia ] ) ? sprintf( '%s, "%s", sans-serif', $familia, PFX_FUENTES[ $familia ] ) : $original();
		} );
	}, 20 );
}

/* ================= 3. jQuery al pie =================
   WordPress lo registra en el grupo del <head> aunque sus consumidores vayan al pie: bloqueaba
   0,3–0,5 s. Nunca con el «Defer render-blocking JS» del optimizador del hosting: rompe el inline
   jQuery.uiBackCompat=true → error de consola → Buenas prácticas. Si un script del <head> depende
   de jquery, WordPress lo devuelve al <head>: compruébalo con checks-html.sh. */
add_action( 'wp_enqueue_scripts', function () {
	if ( pfx_rend_editor() ) {
		return;
	}
	foreach ( [ 'jquery', 'jquery-core', 'jquery-migrate' ] as $h ) {
		wp_scripts()->add_data( $h, 'group', 1 );
	}
	foreach ( PFX_JQUERY_DEPS as $handle ) {
		$s = wp_scripts()->query( $handle, 'registered' );
		if ( $s && ! in_array( 'jquery', $s->deps, true ) ) {
			$s->deps[] = 'jquery';
		}
	}
}, 100 );

/* ================= 4. imágenes =================
   a) La imagen V4 sale con srcset pero SIN sizes (el navegador supone 100vw y baja la más grande:
      un logo de 135 px se pedía a 900w) y con id="<ID del adjunto>" (repetido si el logo está en
      cabecera y pie; un id no puede empezar por número). sizes="auto, 100vw" solo lo respeta el
      navegador con loading="lazy": las eager (logos) llevan su sizes explícito en Avanzado ›
      Atributos (se imprime delante y manda). Las imágenes width:auto/height:auto colapsan con
      sizes=auto (contain:size): dales sizes fijo. Simúlalo antes con sizes-sim.mjs. */
add_action( 'elementor/atomic-widgets/settings/transformers/register', function ( $transformers ) {
	pfx_envolver_transformador( $transformers, 'image', function ( $value, $original ) {
		$img = $original();
		if ( is_array( $img ) ) {
			unset( $img['id'] );
			if ( ! empty( $img['srcset'] ) ) {
				$img['sizes'] = 'auto, 100vw';
			}
		}
		return $img;
	} );
}, 20 );
add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' ); // ya lo hace el transformador

/* b) Prioridad. La «Carga de imágenes optimizada» de Elementor (activa por defecto) SUSTITUYE la
      lógica de WordPress: umbral fijo de 3 sin filtro, apaga el fetchpriority del núcleo y
      wp_lazy_loading_enabled, y corre su propio wp_content_img_tag a prioridad 10. Por eso
      wp_omit_loading_attr_threshold y wp_get_loading_optimization_attributes no hacen nada.
      Aquí, a prioridad 11 y SIN ESTADO: solo la clase LCP conserva fetchpriority="high" y todo lo
      que no tenga loading pasa a lazy. Nada de contadores: Yoast pasa el contenido por
      the_content dentro de wp_head (respaldo de og:description) y desplazaría la cuenta.
      Las plantillas del Theme Builder NO pasan por aquí: sus atributos van en _elementor_data
      (scripts/wp-cli/atributos-por-id.php). */
if ( PFX_PRIORIDAD ) {
	add_filter( 'wp_content_img_tag', function ( $img ) {
		if ( pfx_rend_editor() ) {
			return $img;
		}
		if ( str_contains( $img, 'fetchpriority="high"' ) ) {
			if ( '' !== PFX_LCP_CLASE && str_contains( $img, PFX_LCP_CLASE ) ) {
				return $img;
			}
			$img = preg_replace( '/\sfetchpriority="high"/', '', $img );
		}
		if ( ! preg_match( '/\sloading=/', $img ) ) {
			$img = str_replace( '<img ', '<img loading="lazy" ', $img );
		}
		return $img;
	}, 11 );
}

/* c) LCP pintado como fondo CSS (póster del vídeo): el escáner de precarga no ve imágenes de CSS.
      Se precarga el que toca por media query, con el MISMO href que resuelve style.css. Si el
      cliente cambia el vídeo, regenera los pósters (ffmpeg + cwebp, references/rendimiento.md). */
if ( PFX_POSTER ) {
	add_action( 'wp_head', function () {
		if ( ! is_front_page() || pfx_rend_editor() ) {
			return;
		}
		$corte = (int) PFX_POSTER['corte'];
		printf( '<link rel="preload" as="image" href="%s" media="(max-width: %dpx)" fetchpriority="high">' . "\n", esc_url( pfx_rend_url( PFX_POSTER['movil'] ) ), $corte );
		printf( '<link rel="preload" as="image" href="%s" media="(min-width: %dpx)" fetchpriority="high">' . "\n", esc_url( pfx_rend_url( PFX_POSTER['escritorio'] ) ), $corte + 1 );
	}, 2 );
}

/* ================= 5. revelado temprano =================
   Si el LCP está dentro de un bloque animado a opacidad 0 hasta que llega el JS del pie (diferido
   tras todo lo demás), el render delay se dispara (+4,4 s en el caso). Justo después de </main>
   se revela lo que ya está en pantalla; el JS general no repite lo que ya tiene .is-in. Respeta
   prefers-reduced-motion y no actúa en el editor (html.pfx-js no se pone con elementor-preview). */
function pfx_revelar_visible() {
	if ( pfx_rend_editor() ) {
		return;
	}
	echo "<script>(function(d,w){var h=d.documentElement;if(!h.classList.contains('pfx-js')||w.matchMedia('(prefers-reduced-motion: reduce)').matches)return;d.querySelectorAll('main [data-pfx-stagger]').forEach(function(g){[].forEach.call(g.children,function(c,i){c.style.setProperty('--i',i)})});var l=w.innerHeight*.92,a=[];d.querySelectorAll('main [data-pfx-reveal]').forEach(function(e){if(e.getBoundingClientRect().top<l)a.push(e)});a.length&&w.requestAnimationFrame(function(){a.forEach(function(e){var t=parseInt(e.getAttribute('data-pfx-reveal'),10);if(t>0)e.style.transitionDelay=t+'ms';e.classList.add('is-in')})})})(document,window);</script>\n";
}
if ( PFX_REVELADO ) {
	add_action( 'elementor/page_templates/header-footer/after_content', 'pfx_revelar_visible', 20 );
	add_action( 'elementor/theme/after_do_single', 'pfx_revelar_visible', 20 );
}

/* ================= 6. fichas de CPT sin CSS de bloques =================
   «Optimized Gutenberg Loading» de Elementor no se aplica a los singles de CPT: wp-block-library
   (17 KB, 100 % sin usar) seguía cargando. Solo si la ficha no usa bloques. */
if ( PFX_CPT_SIN_BLOQUES ) {
	add_action( 'wp_enqueue_scripts', function () {
		if ( is_singular( PFX_CPT_SIN_BLOQUES ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	}, 100 );
}

/* ================= 7. vídeo de fondo de la portada tras load =================
   e-background-video imprime preload="auto" fijo: el mp4 (1,4 MB en el caso) competía con el
   primer pintado (PSI portada móvil 84 → 94 al diferirlo). La página sale con el póster y el
   vídeo se engancha tras load + requestIdleCallback. Con «reducir movimiento» no se descarga
   hasta pulsar Reproducir (y el control queda en «Reproducir»). Es un cambio VISIBLE (el póster
   dura ~1 s más): solo con aprobación. Compruébalo con video-diferido.mjs. */
if ( PFX_VIDEO_SEL ) {
	add_filter( 'the_content', function ( $html ) {
		if ( ! is_front_page() || pfx_rend_editor() || ! str_contains( $html, 'e-background-video__media' ) ) {
			return $html;
		}
		return preg_replace( '~(<video class="e-background-video__media"[^>]*?)preload="auto"(\s*>\s*<source )src=~', '$1preload="none"$2data-pfx-src=', $html, 1 );
	}, 20 );

	add_action( 'wp_footer', function () {
		if ( ! is_front_page() || pfx_rend_editor() ) {
			return;
		}
		$sel = wp_json_encode( PFX_VIDEO_SEL );
		echo "<script>(function(){var v=document.querySelector($sel),s=v&&v.querySelector('source[data-pfx-src]');if(!s)return;function c(){if(!s.hasAttribute('data-pfx-src'))return;s.src=s.getAttribute('data-pfx-src');s.removeAttribute('data-pfx-src');v.preload='auto';v.load();var p=v.play();if(p&&p.catch)p.catch(function(){});}var r=v.closest('.e-background-video'),b=r&&r.querySelector('.e-background-video__play');if(b)b.addEventListener('click',c);if(window.matchMedia('(prefers-reduced-motion: reduce)').matches){window.addEventListener('load',function(){setTimeout(function(){var q=r&&r.querySelector('.e-background-video__pause');if(q&&q.offsetParent&&s.hasAttribute('data-pfx-src'))q.click();},200);},{once:true});return;}function l(){'requestIdleCallback'in window?requestIdleCallback(c,{timeout:1500}):setTimeout(c,300);}document.readyState==='complete'?l():window.addEventListener('load',l,{once:true});})();</script>\n";
	}, 100 );
}

/* ================= 8. <img> sin atributos repetidos =================
   Los atributos puestos a mano (Avanzado › Atributos) salen delante de los que añaden Elementor
   y los filtros: sizes, loading y fetchpriority acababan duplicados. Se deja la primera copia (la
   que el navegador ya usaba) y se quita «auto» de sizes si la imagen no es lazy. Un solo búfer
   por página: get_header a prioridad 9, porque Elementor abre el suyo a 10 y así lo envuelve (su
   comprobación en flush_header_buffer sigue funcionando). Fuera del editor y del ajax. */
function pfx_img_normaliza( $html ) {
	return preg_replace_callback( '/<img\s[^>]+>/', function ( $m ) {
		$tag = $m[0];
		foreach ( [ 'sizes', 'loading', 'fetchpriority', 'decoding' ] as $n ) {
			$i   = 0;
			$tag = preg_replace_callback( '/\s' . $n . '="[^"]*"/', function ( $a ) use ( &$i ) {
				return $i++ ? '' : $a[0];
			}, $tag );
		}
		return str_contains( $tag, ' loading="lazy"' ) ? $tag : str_replace( ' sizes="auto, ', ' sizes="', $tag );
	}, $html );
}
add_action( 'get_header', function () {
	if ( ! pfx_rend_editor() && ! wp_doing_ajax() ) {
		ob_start( 'pfx_img_normaliza' );
	}
}, 9 );
