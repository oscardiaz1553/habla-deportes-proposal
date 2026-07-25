<?php
/**
 * Habla Deportes — Single del CPT `video`, portado de video.html.
 * Reproductor facade (home.js inyecta el iframe CON sonido al hacer
 * clic), ficha del video, barra de suscripción/compartir, etiquetas
 * de taxonomías, sponsor, "Más videos" y JSON-LD VideoObject.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hd_img = hd_proto_uri() . '/img';

while ( have_posts() ) :
	the_post();

	$hd_id     = get_the_ID();
	$hd_yt     = hd_youtube_id( $hd_id );
	$hd_title  = get_the_title();
	$hd_thumb  = hd_thumb_url( $hd_id, 'large' );
	$hd_link   = get_permalink();
	$hd_share_txt = rawurlencode( $hd_title );
	$hd_share_url = rawurlencode( $hd_link );
	$hd_videos_url = get_post_type_archive_link( 'video' );
	if ( ! $hd_videos_url ) {
		$hd_videos_url = '#';
	}
?>

  <!-- JSON-LD: VideoObject (adaptado del prototipo con datos reales del post) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "VideoObject",
    "name": <?php echo wp_json_encode( $hd_title ); ?>,
    "description": <?php echo wp_json_encode( wp_strip_all_tags( get_the_excerpt() ) ); ?>,
    "thumbnailUrl": [<?php echo wp_json_encode( $hd_thumb ); ?>],
    "uploadDate": <?php echo wp_json_encode( get_post_time( 'c', false, $hd_id ) ); ?>,
    <?php if ( $hd_yt ) : ?>
    "contentUrl": <?php echo wp_json_encode( 'https://www.youtube.com/watch?v=' . $hd_yt ); ?>,
    "embedUrl": <?php echo wp_json_encode( 'https://www.youtube.com/embed/' . $hd_yt ); ?>,
    <?php endif; ?>
    "inLanguage": "es-CO",
    "publisher": {
      "@type": "Organization",
      "name": "Habla Deportes",
      "logo": {
        "@type": "ImageObject",
        "url": <?php echo wp_json_encode( $hd_img . '/LogoHablaDeportes.webp' ); ?>
      }
    }
  }
  </script>

    <div class="container">
      <div class="video-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Miga de pan">
          <ol class="breadcrumb__list">
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li>
            <li class="breadcrumb__sep" aria-hidden="true">›</li>
            <li><a href="<?php echo esc_url( $hd_videos_url ); ?>">Videos</a></li>
            <li class="breadcrumb__sep" aria-hidden="true">›</li>
            <li aria-current="page"><?php echo esc_html( $hd_title ); ?></li>
          </ol>
        </nav>

        <!-- Reproductor grande (facade -> iframe inline con sonido al hacer clic) -->
        <div class="player" data-video-facade>
          <button class="player__facade" type="button"
                  data-player-yt="<?php echo esc_attr( $hd_yt ); ?>"
                  data-player-title="<?php echo esc_attr( $hd_title ); ?>"
                  aria-label="<?php echo esc_attr( 'Reproducir video: ' . $hd_title ); ?>">
            <img class="player__img" src="<?php echo esc_url( $hd_thumb ); ?>"
                 width="1715" height="917" fetchpriority="high"
                 alt="<?php echo esc_attr( $hd_title ); ?>">
            <span class="player__scrim" aria-hidden="true"></span>
            <span class="player__badge" aria-hidden="true">
              <?php echo hd_svg_youtube(); ?>
              Ver en Habla Deportes
            </span>
            <span class="play-btn player__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
          </button>
        </div>

        <!-- Cabecera de la ficha del video -->
        <header class="video-head">
          <p class="video-head__kicker">
            <span class="live-dot" aria-hidden="true"></span>
            Video · El show de la mañana
          </p>
          <h1 class="video-head__title"><?php echo esc_html( $hd_title ); ?></h1>

          <div class="video-meta">
            <span class="video-meta__item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg>
              <time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"><?php echo esc_html( get_the_date( 'j \d\e F \d\e Y' ) ); ?></time>
            </span>
            <span class="video-meta__sep" aria-hidden="true">•</span>
            <a class="video-meta__program" href="#">
              <span class="live-dot" aria-hidden="true"></span>
              El show de la mañana
            </a>
          </div>

          <div class="video-desc">
            <?php
            // Descripción importada de YouTube (contenido del post).
            the_content();
            ?>
          </div>
        </header>

        <!-- Barra de acciones: suscribirse (rojo) + compartir -->
        <section class="video-actions" aria-label="Suscríbete y comparte">
          <div class="video-actions__sub">
            <span class="video-actions__channel">
              <img class="video-actions__avatar" src="<?php echo esc_url( $hd_img . '/juansalvador1.png' ); ?>" width="1715" height="917" loading="lazy" alt="Foto de perfil del canal Habla Deportes">
              <span>
                <span class="video-actions__name">Habla Deportes</span><br>
                <span class="video-actions__handle">@HablaDeportesRadio</span>
              </span>
            </span>
            <a class="btn btn--subscribe" href="https://www.youtube.com/@HablaDeportesRadio?sub_confirmation=1" target="_blank" rel="noopener">
              Suscríbete al canal
              <?php echo hd_svg_youtube( 'btn__yt' ); ?>
            </a>
          </div>

          <div class="share">
            <span class="share__label">Compartir:</span>
            <a class="share__btn share__btn--wa" href="<?php echo esc_url( 'https://api.whatsapp.com/send?text=' . $hd_share_txt . '%20' . $hd_share_url ); ?>" target="_blank" rel="noopener" aria-label="Compartir en WhatsApp">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm5.8 14.16c-.25.69-1.45 1.32-2 1.4-.51.08-1.16.11-1.87-.12-.43-.14-.98-.32-1.69-.63-2.98-1.29-4.92-4.28-5.07-4.48-.15-.2-1.21-1.6-1.21-3.06 0-1.45.76-2.16 1.03-2.46.27-.3.59-.37.79-.37.2 0 .39 0 .56.01.18.01.42-.07.66.5.25.59.83 2.04.9 2.19.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.6.17.3.76 1.25 1.63 2.02 1.12.99 2.05 1.3 2.35 1.45.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.3.4-.25.66-.15.27.1 1.71.81 2.01.96.3.15.5.22.57.35.07.12.07.72-.18 1.41Z"/></svg>
            </a>
            <a class="share__btn share__btn--x" href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . $hd_share_url . '&text=' . $hd_share_txt ); ?>" target="_blank" rel="noopener" aria-label="Compartir en X">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-6.9l-5.4-7-6.2 7H1.6l8.1-9.3L1 2h7l4.9 6.4L18.9 2Zm-1.2 18h1.7L6.4 3.8H4.6L17.7 20Z"/></svg>
            </a>
            <a class="share__btn share__btn--fb" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $hd_share_url ); ?>" target="_blank" rel="noopener" aria-label="Compartir en Facebook">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.9 3.78-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.44 2.9h-2.34V22c4.78-.79 8.43-4.94 8.43-9.94Z"/></svg>
            </a>
          </div>
        </section>

        <?php
        // Etiquetas: términos de las taxonomías del modelo (equipo/torneo/tema).
        $hd_terms = array();
        foreach ( array( 'equipo', 'torneo', 'tema' ) as $hd_tax ) {
            $hd_t = get_the_terms( $hd_id, $hd_tax );
            if ( $hd_t && ! is_wp_error( $hd_t ) ) {
                $hd_terms = array_merge( $hd_terms, $hd_t );
            }
        }
        ?>
        <?php if ( ! empty( $hd_terms ) ) : ?>
        <nav class="video-tags article__tags" aria-label="Etiquetas del video">
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
        <aside class="article__sponsor video-sponsor" aria-label="Contenido patrocinado">
          <span class="article__sponsor-label">Presentado por</span>
          <img src="<?php echo esc_url( $hd_img . '/logo-transelca.svg' ); ?>" width="200" height="44" loading="lazy" alt="Transelca">
        </aside>

      </div>
    </div>

    <!-- ===================== MÁS VIDEOS ===================== -->
    <?php
    $hd_more = get_posts( array(
        'post_type'      => 'video',
        'posts_per_page' => 8,
        'post_status'    => 'publish',
        'post__not_in'   => array( $hd_id ),
        'meta_key'       => '_hd_youtube_id',
    ) );
    ?>
    <?php if ( ! empty( $hd_more ) ) : ?>
    <section class="section related" aria-labelledby="more-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--yt" aria-hidden="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8Z" fill="#D20000"/><path d="M9.6 15.6V8.4l6.3 3.6-6.3 3.6Z" fill="#fff"/></svg>
          </span>
          <h2 class="section-head__title" id="more-title">Más videos</h2>
          <div class="section-head__actions">
            <a class="section-head__link" href="<?php echo esc_url( $hd_videos_url ); ?>">Ver todos
              <?php echo hd_svg_chevron(); ?>
            </a>
          </div>
        </div>

        <div class="vcards">
          <?php foreach ( $hd_more as $hd_v ) : ?>
            <?php hd_render_vcard( $hd_v->ID ); ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

<?php
endwhile;

get_footer();
