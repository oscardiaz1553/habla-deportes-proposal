<?php
/**
 * Habla Deportes — Archivo genérico, portado de seccion.html.
 * Sirve para el archivo del CPT `video` (/videos), las taxonomías
 * `equipo` / `torneo` / `tema`, categorías y etiquetas (la jerarquía
 * de plantillas de WP cae aquí para todas).
 *
 * Estructura: breadcrumb + encabezado de sección + destacado (primer
 * resultado, solo en la página 1) + chips de filtro Video/Nota (los
 * maneja home.js vía data-filter/data-tipo) + grilla + paginación.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hd_img = hd_proto_uri() . '/img';

/* ---------- Título y descripción del archivo ---------- */
if ( is_post_type_archive( 'video' ) ) {
	$hd_arch_title = 'Videos';
	$hd_arch_desc  = 'Todos los videos del canal: el show completo, análisis y clips. Directo desde YouTube a Habla Deportes.';
} elseif ( is_tax() || is_category() || is_tag() ) {
	$hd_arch_title = single_term_title( '', false );
	$hd_arch_desc  = term_description();
} else {
	$hd_arch_title = get_the_archive_title();
	$hd_arch_desc  = get_the_archive_description();
}

/* ---------- Escudo del encabezado para términos conocidos ---------- */
$hd_crests = array(
	'junior'                 => '/Escudo_Junior_de_Barranquilla_2018.png',
	'seleccion-colombia'     => '/Escudo_de_la_Federación_Colombiana_de_Fútbol.png',
	'atletico-nacional'      => '/Atlético_Nacional.png',
	'millonarios'            => '/Escudo_Millos.png',
	'america-de-cali'        => '/Escudo_América_de_Cali.png',
	'deportes-tolima'        => '/Escudo_Deportes_Tolima.png',
	'independiente-medellin' => '/Escudo_del_Deportivo_Independiente_Medellín.png',
);
$hd_crest = '';
$hd_term  = get_queried_object();
if ( $hd_term && isset( $hd_term->slug ) && isset( $hd_crests[ $hd_term->slug ] ) ) {
	$hd_crest = $hd_img . $hd_crests[ $hd_term->slug ];
}

/* ---------- Posts de la página + conteos para los chips ---------- */
global $wp_query;
$hd_posts = $wp_query->posts;
$hd_total = (int) $wp_query->found_posts;
$hd_count_video = 0;
$hd_count_nota  = 0;
foreach ( $hd_posts as $hd_p ) {
	if ( 'video' === get_post_type( $hd_p ) ) {
		$hd_count_video++;
	} else {
		$hd_count_nota++;
	}
}

/* Destacado: primer resultado, solo en la primera página. */
$hd_feature = ( ! is_paged() && ! empty( $hd_posts ) ) ? $hd_posts[0] : null;
$hd_grid    = $hd_feature ? array_slice( $hd_posts, 1 ) : $hd_posts;
?>

    <div class="container">
      <div class="section-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Miga de pan">
          <ol class="breadcrumb__list">
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li>
            <li class="breadcrumb__sep" aria-hidden="true">›</li>
            <li aria-current="page"><?php echo esc_html( $hd_arch_title ); ?></li>
          </ol>
        </nav>

        <!-- Encabezado de sección -->
        <header class="section-hero">
          <?php if ( $hd_crest ) : ?>
          <span class="section-hero__crest" aria-hidden="true">
            <img src="<?php echo esc_url( $hd_crest ); ?>" width="208" height="237" alt="">
          </span>
          <?php endif; ?>
          <div class="section-hero__body">
            <p class="section-hero__eyebrow">
              <span class="live-dot" aria-hidden="true"></span>
              Habla Deportes
            </p>
            <h1 class="section-hero__title"><?php echo esc_html( $hd_arch_title ); ?></h1>
            <?php if ( $hd_arch_desc ) : ?>
            <div class="section-hero__desc"><?php echo wp_kses_post( $hd_arch_desc ); ?></div>
            <?php endif; ?>
            <p class="section-hero__count">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
              <strong><?php echo esc_html( $hd_total ); ?></strong>&nbsp;contenidos publicados
            </p>
          </div>
        </header>

        <?php if ( $hd_feature ) : ?>
        <?php
        $hd_f_id    = $hd_feature->ID;
        $hd_f_video = ( 'video' === get_post_type( $hd_f_id ) );
        $hd_f_title = get_the_title( $hd_f_id );
        ?>
        <!-- Destacado de la sección (el resultado más reciente) -->
        <section class="section-feature" aria-label="Destacado de la sección">
          <a class="feature-lg feature-lg__link" href="<?php echo esc_url( get_permalink( $hd_f_id ) ); ?>"
             aria-label="<?php echo esc_attr( ( $hd_f_video ? 'Ver video destacado: ' : 'Leer nota destacada: ' ) . $hd_f_title ); ?>">
            <div class="feature-lg__media">
              <img class="feature-lg__img" src="<?php echo esc_url( hd_thumb_url( $hd_f_id, 'large' ) ); ?>" width="1715" height="917"
                   fetchpriority="high"
                   alt="<?php echo esc_attr( $hd_f_title ); ?>">
              <span class="feature-lg__scrim" aria-hidden="true"></span>
              <span class="feature-lg__badge" aria-hidden="true">
                <?php if ( $hd_f_video ) : ?>
                <?php echo hd_svg_youtube(); ?>
                Video destacado
                <?php else : ?>
                Nota destacada
                <?php endif; ?>
              </span>
              <?php if ( $hd_f_video ) : ?>
              <span class="play-btn feature-lg__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
              <?php endif; ?>
            </div>
            <div class="feature-lg__text">
              <p class="feature-lg__eyebrow">
                <span class="live-dot" aria-hidden="true"></span>
                Lo más reciente
              </p>
              <h2 class="feature-lg__title"><?php echo esc_html( $hd_f_title ); ?></h2>
              <?php if ( has_excerpt( $hd_f_id ) ) : ?>
              <p class="feature-lg__desc"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $hd_f_id ) ) ); ?></p>
              <?php endif; ?>
              <span class="byline">
                <span class="byline__meta">
                  <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $hd_f_id ) ); ?>"><?php echo esc_html( hd_fecha( $hd_f_id ) ); ?></time>
                </span>
              </span>
            </div>
          </a>
        </section>
        <?php endif; ?>

        <?php if ( ! empty( $hd_grid ) ) : ?>
        <!-- Barra de filtros (chips accesibles; los maneja home.js por data-tipo) -->
        <div class="filter-bar" data-filter-bar role="group" aria-label="Filtrar contenidos por tipo">
          <span class="filter-bar__label" aria-hidden="true">Filtrar:</span>
          <button class="filter-chip" type="button" data-filter="todo" aria-pressed="true">
            Todo <span class="filter-chip__count"><?php echo esc_html( count( $hd_grid ) ); ?></span>
          </button>
          <button class="filter-chip" type="button" data-filter="video" aria-pressed="false">
            Videos <span class="filter-chip__count"><?php echo esc_html( $hd_feature && 'video' === get_post_type( $hd_feature ) ? $hd_count_video - 1 : $hd_count_video ); ?></span>
          </button>
          <button class="filter-chip" type="button" data-filter="nota" aria-pressed="false">
            Notas <span class="filter-chip__count"><?php echo esc_html( $hd_feature && 'video' !== get_post_type( $hd_feature ) ? $hd_count_nota - 1 : $hd_count_nota ); ?></span>
          </button>
        </div>

        <!-- Grilla de piezas (video -> lightbox · nota -> articulo) -->
        <section class="section-grid" data-filter-grid aria-label="<?php echo esc_attr( 'Contenidos de ' . $hd_arch_title ); ?>">
          <?php foreach ( $hd_grid as $hd_p ) : ?>
            <?php hd_render_vcard( $hd_p->ID, array( 'data_tipo' => true ) ); ?>
          <?php endforeach; ?>
        </section>

        <!-- Estado vacío (sólo visible si un filtro no arroja coincidencias) -->
        <p class="section-empty" data-filter-empty hidden>No hay contenidos de este tipo por ahora. Prueba con otro filtro.</p>

        <!-- Paginación nativa de WordPress -->
        <div class="section-more">
          <?php
          the_posts_pagination( array(
              'mid_size'  => 1,
              'prev_text' => '‹ Anteriores',
              'next_text' => 'Siguientes ›',
          ) );
          ?>
        </div>
        <?php elseif ( ! $hd_feature ) : ?>
        <p class="section-empty">Aún no hay contenidos en esta sección.</p>
        <?php endif; ?>

      </div>
    </div>

<?php
get_footer();
