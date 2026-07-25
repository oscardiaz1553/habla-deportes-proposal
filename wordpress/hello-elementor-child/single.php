<?php
/**
 * Habla Deportes — Single de entradas (notas), portado de articulo.html.
 * Breadcrumb, cabecera con kicker/lead/meta, imagen destacada,
 * cuerpo de la entrada, etiquetas, sponsor, relacionados y
 * JSON-LD NewsArticle con datos reales del post.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hd_img = hd_proto_uri() . '/img';

while ( have_posts() ) :
	the_post();

	$hd_id    = get_the_ID();
	$hd_title = get_the_title();
	$hd_thumb = get_the_post_thumbnail_url( $hd_id, 'large' );
	$hd_cats  = get_the_category();
	$hd_cat   = ! empty( $hd_cats ) ? $hd_cats[0] : null;
?>

  <!-- JSON-LD: NewsArticle -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    "headline": <?php echo wp_json_encode( $hd_title ); ?>,
    "description": <?php echo wp_json_encode( wp_strip_all_tags( get_the_excerpt() ) ); ?>,
    <?php if ( $hd_thumb ) : ?>
    "image": [<?php echo wp_json_encode( $hd_thumb ); ?>],
    <?php endif; ?>
    "datePublished": <?php echo wp_json_encode( get_post_time( 'c', false, $hd_id ) ); ?>,
    "dateModified": <?php echo wp_json_encode( get_post_modified_time( 'c', false, $hd_id ) ); ?>,
    "inLanguage": "es-CO",
    <?php if ( $hd_cat ) : ?>
    "articleSection": <?php echo wp_json_encode( $hd_cat->name ); ?>,
    <?php endif; ?>
    "author": {
      "@type": "Person",
      "name": <?php echo wp_json_encode( get_the_author() ); ?>
    },
    "publisher": {
      "@type": "Organization",
      "name": "Habla Deportes",
      "logo": {
        "@type": "ImageObject",
        "url": <?php echo wp_json_encode( $hd_img . '/LogoHablaDeportes.webp' ); ?>
      }
    },
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": <?php echo wp_json_encode( get_permalink() ); ?>
    }
  }
  </script>

    <div class="container">
      <article class="article">

        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Miga de pan">
          <ol class="breadcrumb__list">
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li>
            <?php if ( $hd_cat ) : ?>
            <li class="breadcrumb__sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( get_category_link( $hd_cat ) ); ?>"><?php echo esc_html( $hd_cat->name ); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb__sep" aria-hidden="true">›</li>
            <li aria-current="page"><?php echo esc_html( $hd_title ); ?></li>
          </ol>
        </nav>

        <!-- Cabecera -->
        <header class="article__header">
          <p class="article__kicker">
            <span class="live-dot" aria-hidden="true"></span>
            <?php echo $hd_cat ? esc_html( $hd_cat->name ) : 'Noticias'; ?>
          </p>
          <h1 class="article__title"><?php echo esc_html( $hd_title ); ?></h1>
          <?php if ( has_excerpt() ) : ?>
          <p class="article__lead"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
          <?php endif; ?>

          <div class="article__meta">
            <span class="article__author">
              <?php echo get_avatar( get_the_author_meta( 'ID' ), 96, '', '', array( 'class' => 'article__avatar' ) ); ?>
              <span class="article__author-name"><?php echo esc_html( get_the_author() ); ?></span>
            </span>
            <span class="article__meta-sep" aria-hidden="true">•</span>
            <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'j \d\e F \d\e Y' ) ); ?></time>
            <?php
            // Tiempo de lectura estimado (200 palabras por minuto).
            $hd_words   = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $hd_id ) ) );
            $hd_minutes = max( 1, (int) round( $hd_words / 200 ) );
            ?>
            <span class="article__meta-sep" aria-hidden="true">•</span>
            <span class="article__reading">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
              <?php echo esc_html( $hd_minutes ); ?> min de lectura
            </span>
          </div>
        </header>

        <?php if ( $hd_thumb ) : ?>
        <!-- Imagen destacada -->
        <figure class="article__media article__figure">
          <img src="<?php echo esc_url( $hd_thumb ); ?>" width="1200" height="740"
               fetchpriority="high"
               alt="<?php echo esc_attr( $hd_title ); ?>">
          <?php if ( get_the_post_thumbnail_caption() ) : ?>
          <figcaption class="article__caption"><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption>
          <?php endif; ?>
        </figure>
        <?php endif; ?>

        <!-- Cuerpo -->
        <div class="article__body">
          <?php the_content(); ?>
        </div>

        <?php
        // Etiquetas: taxonomías del modelo + etiquetas nativas.
        $hd_terms = array();
        foreach ( array( 'equipo', 'torneo', 'tema', 'post_tag' ) as $hd_tax ) {
            $hd_t = get_the_terms( $hd_id, $hd_tax );
            if ( $hd_t && ! is_wp_error( $hd_t ) ) {
                $hd_terms = array_merge( $hd_terms, $hd_t );
            }
        }
        ?>
        <?php if ( ! empty( $hd_terms ) ) : ?>
        <nav class="article__tags" aria-label="Etiquetas de la nota">
          <span class="article__tags-label">Etiquetas:</span>
          <?php foreach ( $hd_terms as $hd_term ) : ?>
            <?php $hd_term_url = get_term_link( $hd_term ); ?>
            <?php if ( ! is_wp_error( $hd_term_url ) ) : ?>
          <a class="article__tag" href="<?php echo esc_url( $hd_term_url ); ?>"><?php echo esc_html( $hd_term->name ); ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <!-- Espacio de sponsor -->
        <aside class="article__sponsor" aria-label="Contenido patrocinado">
          <span class="article__sponsor-label">Presentado por</span>
          <img src="<?php echo esc_url( $hd_img . '/logo-transelca.svg' ); ?>" width="200" height="44" loading="lazy" alt="Transelca">
        </aside>

      </article>
    </div>

    <!-- ===================== SIGUE LEYENDO / RELACIONADOS ===================== -->
    <?php
    $hd_related = get_posts( array(
        'post_type'      => array( 'post', 'video' ),
        'posts_per_page' => 4,
        'post_status'    => 'publish',
        'post__not_in'   => array( $hd_id ),
    ) );
    ?>
    <?php if ( ! empty( $hd_related ) ) : ?>
    <section class="section related" aria-labelledby="related-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--crest" aria-hidden="true">
            <img src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="208" height="237" loading="lazy" alt="">
          </span>
          <h2 class="section-head__title" id="related-title">Sigue leyendo</h2>
        </div>

        <div class="vcards">
          <?php foreach ( $hd_related as $hd_v ) : ?>
            <?php hd_render_vcard( $hd_v->ID ); ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

<?php
endwhile;

get_footer();
