<?php
/**
 * Habla Deportes — HOME portada del prototipo aprobado (index.html).
 * Cableado v1:
 *   - Hero: los 2 videos más recientes del CPT `video` (crossfade + swipe
 *     + lightbox de home.js). Si hay menos de 2 videos importados, cae a
 *     los 2 slides de muestra del prototipo (fallback elegante).
 *   - Ticker: últimos 6 títulos de `video`, enlazando a su single.
 *   - Centro de Partidos: ESTÁTICO (mock del prototipo, igual que aprobado).
 *   - El show: últimos 4 `video` como vcards (clic -> lightbox).
 *   - Mundo Junior: contenidos con término `junior` (tax `equipo`);
 *     si no hay, últimos videos (fallback comentado abajo).
 *   - Clips: videos con término `clip` (tax `tema`); si no, los 6 más
 *     recientes. Autoplay muteado por visibilidad (home.js).
 *   - Temporada de humo / Selección / CTA Instagram / banner Gases:
 *     estáticos del prototipo (v1).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$hd_img    = hd_proto_uri() . '/img';
$hd_videos_url = get_post_type_archive_link( 'video' );
if ( ! $hd_videos_url ) {
	$hd_videos_url = '#';
}

/* ---------- Datos: últimos videos del canal (CPT `video`) ---------- */
$hd_latest = get_posts( array(
	'post_type'      => 'video',
	'posts_per_page' => 6,
	'post_status'    => 'publish',
	'meta_key'       => '_hd_youtube_id',
) );

/* ---------- Hero: 2 más recientes -> slides JSON para home.js ------ */
$hd_slides = array();
foreach ( array_slice( $hd_latest, 0, 2 ) as $hd_v ) {
	$hd_yt = hd_youtube_id( $hd_v->ID );
	if ( '' === $hd_yt ) {
		continue;
	}
	$hd_slides[] = array(
		'img'     => hd_thumb_url( $hd_v->ID, 'large' ),
		'alt'     => get_the_title( $hd_v ),
		'title'   => get_the_title( $hd_v ),
		'time'    => hd_hace( $hd_v->ID ),
		'youtube' => $hd_yt,
	);
}
if ( count( $hd_slides ) < 2 ) {
	// Fallback elegante: los 2 slides de muestra del prototipo aprobado
	// (aparece solo mientras el importador de YouTube no haya traído
	// al menos 2 videos publicados).
	$hd_slides = array(
		array(
			'img'     => $hd_img . '/juansalvador1.png',
			'alt'     => 'Juan Salvador reacciona en vivo a la posible llegada de Cuadrado a Junior',
			'title'   => 'Cuadrado a Junior: ¿qué hay de cierto?',
			'time'    => 'Hace 2 horas',
			'youtube' => 'LK0g1WLTga0',
		),
		array(
			'img'     => $hd_img . '/juansalvador2.png',
			'alt'     => 'Juan Salvador analiza la última hora sobre la Selección Colombia',
			'title'   => 'Contra todo pronóstico con la Selección Colombia',
			'time'    => 'Hace 1 día',
			'youtube' => 'f9rJ95E_pTg',
		),
	);
}
$hd_s0 = $hd_slides[0];
$hd_s1 = $hd_slides[1];
?>

  <!-- JSON-LD: Organización + Sitio web -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "name": "Habla Deportes",
        "url": <?php echo wp_json_encode( home_url( '/' ) ); ?>,
        "logo": <?php echo wp_json_encode( $hd_img . '/LogoHablaDeportes.webp' ); ?>,
        "email": "info@habladeportes.co",
        "areaServed": "CO",
        "sameAs": ["https://www.youtube.com/@HablaDeportesRadio"]
      },
      {
        "@type": "WebSite",
        "name": "Habla Deportes",
        "url": <?php echo wp_json_encode( home_url( '/' ) ); ?>,
        "inLanguage": "es-CO",
        "potentialAction": {
          "@type": "SearchAction",
          "target": <?php echo wp_json_encode( home_url( '/?s={search_term_string}' ) ); ?>,
          "query-input": "required name=search_term_string"
        }
      }
    ]
  }
  </script>

    <!-- ===================== HERO ===================== -->
    <section class="hero" data-hero aria-label="Video destacado"
      data-slides="<?php echo esc_attr( wp_json_encode( $hd_slides ) ); ?>">
      <div class="hero__slide">
        <!-- Dos capas apiladas para el crossfade real entre slides (home.js). -->
        <div class="hero__layers">
          <img class="hero__img is-active" data-hero-layer
               src="<?php echo esc_url( $hd_s0['img'] ); ?>"
               width="1715" height="917" fetchpriority="high"
               alt="<?php echo esc_attr( $hd_s0['alt'] ); ?>">
          <img class="hero__img" data-hero-layer
               src="<?php echo esc_url( $hd_s1['img'] ); ?>"
               width="1715" height="917" loading="lazy" alt="">
        </div>
        <div class="hero__scrim" aria-hidden="true"></div>

        <button class="hero__arrow hero__arrow--prev" type="button" data-hero-prev aria-label="Video anterior">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button class="hero__arrow hero__arrow--next" type="button" data-hero-next aria-label="Siguiente video">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <button class="play-btn hero__play" type="button" data-hero-play
                data-youtube-id="<?php echo esc_attr( $hd_s0['youtube'] ); ?>"
                data-video-title="<?php echo esc_attr( $hd_s0['title'] ); ?>"
                aria-label="Reproducir video destacado">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
        </button>

        <div class="hero__content">
          <h1 class="hero__title" data-hero-title><?php echo esc_html( $hd_s0['title'] ); ?></h1>
          <p class="hero__meta">
            <svg class="yt-mark" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>
            <span>YouTube</span>
            <span class="meta-sep" aria-hidden="true">•</span>
            <span data-hero-time><?php echo esc_html( $hd_s0['time'] ); ?></span>
          </p>
          <div class="hero__dots" role="tablist" aria-label="Seleccionar video destacado">
            <?php foreach ( $hd_slides as $hd_i => $hd_slide ) : ?>
            <button class="hero__dot" type="button" data-hero-dot aria-current="<?php echo 0 === $hd_i ? 'true' : 'false'; ?>" aria-label="Video <?php echo esc_attr( $hd_i + 1 ); ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== TICKER ===================== -->
    <div class="ticker" aria-label="Últimas noticias">
      <span class="ticker__label"><span class="live-dot" aria-hidden="true"></span>Noticias</span>
      <div class="ticker__viewport">
        <div class="ticker__track" data-ticker-track>
          <?php if ( ! empty( $hd_latest ) ) : ?>
            <?php foreach ( $hd_latest as $hd_v ) : ?>
          <a class="ticker__item" href="<?php echo esc_url( get_permalink( $hd_v ) ); ?>"><span class="ticker__cat">Videos</span><span class="ticker__headline"><?php echo esc_html( get_the_title( $hd_v ) ); ?></span></a>
            <?php endforeach; ?>
          <?php else : ?>
          <span class="ticker__item"><span class="ticker__cat">Junior</span><span class="ticker__headline">Confirmada la cartelera completa para la velada del Coliseo</span></span>
          <span class="ticker__item"><span class="ticker__cat">Futbol</span><span class="ticker__headline">La Selección Colombia ya tiene rival para el amistoso de septiembre</span></span>
          <span class="ticker__item"><span class="ticker__cat">Fichajes</span><span class="ticker__headline">Juanfer Quintero y la realidad de su posible regreso a Junior</span></span>
          <span class="ticker__item"><span class="ticker__cat">Copa América</span><span class="ticker__headline">Así quedó el fixture rumbo a la próxima edición</span></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ===================== CENTRO DE PARTIDOS ===================== -->
    <?php $hd_partidos = function_exists( 'hd_partidos_proximos' ) ? hd_partidos_proximos( 5 ) : array(); ?>
    <section class="section" id="centro-partidos" aria-labelledby="partidos-title">
      <div class="container">
        <h2 class="visually-hidden" id="partidos-title">Centro de Partidos</h2>
        <div class="match-center">
          <!-- IZQUIERDA: banner de partido (carrusel) -->
          <div class="match-slider" data-match-slider>
            <div class="match-slider__viewport">
              <div class="match-slider__track" data-match-track>
              <?php if ( ! empty( $hd_partidos ) ) : ?>
                <?php foreach ( $hd_partidos as $hd_pid ) : ?>
                <?php hd_render_match_banner( $hd_pid ); ?>
                <?php endforeach; ?>
              <?php else : // Fallback ESTÁTICO (mock aprobado) si no hay partidos futuros. ?>

                <!-- Slide 1: Junior vs Nacional -->
                <a class="match-banner match-banner--junnac" href="#"
                   aria-label="Final Liga Betplay: Junior vs Atlético Nacional. Sábado 20 de julio en el Estadio Metropolitano. Transmite Win Sports.">
                  <span class="match-banner__overlay" aria-hidden="true"></span>
                  <span class="match-banner__kicker">Final Liga Betplay</span>
                  <span class="match-banner__body">
                    <span class="match-banner__fixture">
                      <img class="match-banner__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="208" height="237" loading="lazy" alt="Escudo de Junior de Barranquilla">
                      <span class="match-banner__vs">vs</span>
                      <img class="match-banner__crest" src="<?php echo esc_url( $hd_img . '/Atlético_Nacional.png' ); ?>" width="200" height="200" loading="lazy" alt="Escudo de Atlético Nacional">
                    </span>
                    <span class="match-banner__info">
                      <span class="match-banner__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg> Sábado 20 de Julio</span>
                      <span class="match-banner__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> Metropolitano</span>
                    </span>
                    <span class="match-banner__tv">
                      <img class="match-banner__tv-logo" src="<?php echo esc_url( $hd_img . '/Win_Sports_nuevo_logo.svg.webp' ); ?>" width="3840" height="2171" loading="lazy" alt="Win Sports">
                    </span>
                  </span>
                </a>

                <!-- Slide 2: Colombia vs Perú -->
                <a class="match-banner match-banner--col" href="#"
                   aria-label="Eliminatorias Sudamericanas: Colombia vs Perú. Martes 9 de septiembre en Barranquilla.">
                  <span class="match-banner__overlay" aria-hidden="true"></span>
                  <span class="match-banner__kicker">Eliminatorias Sudamericanas</span>
                  <span class="match-banner__body">
                    <span class="match-banner__fixture">
                      <img class="match-banner__crest" src="<?php echo esc_url( $hd_img . '/Escudo_de_la_Federación_Colombiana_de_Fútbol.png' ); ?>" width="2160" height="2160" loading="lazy" alt="Escudo de la Selección Colombia">
                      <span class="match-banner__vs">vs</span>
                      <img class="match-banner__crest" src="<?php echo esc_url( $hd_img . '/Logotipo_FPF_2016.png' ); ?>" width="1278" height="1613" loading="lazy" alt="Escudo de la Selección de Perú">
                    </span>
                    <span class="match-banner__info">
                      <span class="match-banner__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg> Martes 9 de Septiembre</span>
                      <span class="match-banner__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> Metropolitano</span>
                    </span>
                    <span class="match-banner__tv">
                      <img class="match-banner__tv-logo" src="<?php echo esc_url( $hd_img . '/Win_Sports_nuevo_logo.svg.webp' ); ?>" width="3840" height="2171" loading="lazy" alt="Win Sports">
                    </span>
                  </span>
                </a>

                <!-- Slide 3: Junior vs Millonarios -->
                <a class="match-banner match-banner--junmil" href="#"
                   aria-label="Liga Betplay: Junior vs Millonarios. Miércoles 24 de julio en el Estadio Metropolitano.">
                  <span class="match-banner__overlay" aria-hidden="true"></span>
                  <span class="match-banner__kicker">Liga Betplay · Fecha 12</span>
                  <span class="match-banner__body">
                    <span class="match-banner__fixture">
                      <img class="match-banner__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="208" height="237" loading="lazy" alt="Escudo de Junior de Barranquilla">
                      <span class="match-banner__vs">vs</span>
                      <img class="match-banner__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Millos.png' ); ?>" width="200" height="200" loading="lazy" alt="Escudo de Millonarios">
                    </span>
                    <span class="match-banner__info">
                      <span class="match-banner__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg> Miércoles 24 de Julio</span>
                      <span class="match-banner__info-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg> Metropolitano</span>
                    </span>
                    <span class="match-banner__tv">
                      <img class="match-banner__tv-logo" src="<?php echo esc_url( $hd_img . '/Win_Sports_nuevo_logo.svg.webp' ); ?>" width="3840" height="2171" loading="lazy" alt="Win Sports">
                    </span>
                  </span>
                </a>
              <?php endif; ?>

              </div>
            </div>
            <div class="match-slider__dots" role="tablist" aria-label="Seleccionar partido">
              <?php if ( ! empty( $hd_partidos ) ) : ?>
                <?php foreach ( $hd_partidos as $hd_i => $hd_pid ) : ?>
                <?php hd_render_match_dot( $hd_pid, $hd_i ); ?>
                <?php endforeach; ?>
              <?php else : ?>
              <button class="match-slider__dot" type="button" data-match-dot aria-current="true" aria-label="Partido 1: Junior vs Nacional"></button>
              <button class="match-slider__dot" type="button" data-match-dot aria-current="false" aria-label="Partido 2: Colombia vs Perú"></button>
              <button class="match-slider__dot" type="button" data-match-dot aria-current="false" aria-label="Partido 3: Junior vs Millonarios"></button>
              <?php endif; ?>
            </div>
          </div>

          <!-- DERECHA: publicidad -->
          <aside class="match-ad" aria-label="Espacio publicitario">
            <a class="match-ad__link" href="#" aria-label="Publicidad de ISA Transelca">
              <picture>
                <source media="(max-width: 899.98px)" srcset="<?php echo esc_url( $hd_img . '/transelca_mobile.png' ); ?>">
                <img class="match-ad__img" src="<?php echo esc_url( $hd_img . '/publicidad.png' ); ?>" width="1126" height="1397" loading="lazy" alt="Publicidad — ISA Transelca: eso que menos ves, es lo que más cuidamos">
              </picture>
              <span class="match-ad__label">Publicidad</span>
            </a>
          </aside>
        </div>
      </div>
    </section>

    <!-- ===================== EL SHOW DE LA MAÑANA (últimos 4 videos) ===================== -->
    <section class="section" id="el-show" data-carousel aria-labelledby="show-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--yt" aria-hidden="true">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8Z" fill="#D20000"/><path d="M9.6 15.6V8.4l6.3 3.6-6.3 3.6Z" fill="#fff"/></svg>
          </span>
          <h2 class="section-head__title" id="show-title">El show de la mañana</h2>
          <div class="section-head__actions">
            <button class="round-arrow" type="button" data-carousel-prev aria-label="Ver videos anteriores">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button class="round-arrow" type="button" data-carousel-next aria-label="Ver más videos">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
          </div>
        </div>

        <div class="vcards" data-carousel-viewport>
          <?php if ( ! empty( $hd_latest ) ) : ?>
            <?php foreach ( array_slice( $hd_latest, 0, 4 ) as $hd_v ) : ?>
              <?php hd_render_vcard( $hd_v->ID ); ?>
            <?php endforeach; ?>
          <?php else : ?>
            <p class="section-empty">Aún no hay videos importados del canal. Ve a <strong>Videos → Importar de YouTube</strong> en el escritorio de WordPress.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ===================== MUNDO JUNIOR ===================== -->
    <?php
    // Contenidos (notas + videos) etiquetados con el término `junior` de la
    // taxonomía `equipo`. FALLBACK: si el término no existe o no tiene
    // contenidos todavía, mostramos los últimos videos del canal para que
    // la sección nunca quede vacía.
    $hd_junior_q = array();
    if ( get_term_by( 'slug', 'junior', 'equipo' ) ) {
        $hd_junior_q = get_posts( array(
            'post_type'      => array( 'post', 'video' ),
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'equipo',
                    'field'    => 'slug',
                    'terms'    => 'junior',
                ),
            ),
        ) );
    }
    if ( empty( $hd_junior_q ) ) {
        $hd_junior_q = array_slice( $hd_latest, 0, 5 ); // fallback: últimos videos
    }
    $hd_junior_feat = ! empty( $hd_junior_q ) ? $hd_junior_q[0] : null;
    $hd_junior_list = array_slice( $hd_junior_q, 1, 4 );
    ?>
    <?php if ( $hd_junior_feat ) : ?>
    <section class="section" id="mundo-junior" aria-labelledby="junior-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--crest" aria-hidden="true">
            <img src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="208" height="237" loading="lazy" alt="">
          </span>
          <h2 class="section-head__title" id="junior-title">Mundo Junior</h2>
          <div class="section-head__actions">
            <a class="section-head__link" href="<?php echo esc_url( hd_tax_link( 'equipo', 'junior' ) ); ?>">Ver todo
              <?php echo hd_svg_chevron(); ?>
            </a>
          </div>
        </div>

        <div class="junior-grid">
          <?php
          $hd_f_id    = $hd_junior_feat->ID;
          $hd_f_video = ( 'video' === get_post_type( $hd_f_id ) );
          $hd_f_yt    = $hd_f_video ? hd_youtube_id( $hd_f_id ) : '';
          $hd_f_title = get_the_title( $hd_f_id );
          ?>
          <article class="feature">
            <a href="<?php echo esc_url( get_permalink( $hd_f_id ) ); ?>"
               <?php if ( $hd_f_video && $hd_f_yt ) : ?>
               data-youtube-id="<?php echo esc_attr( $hd_f_yt ); ?>" data-video-title="<?php echo esc_attr( $hd_f_title ); ?>"
               aria-label="<?php echo esc_attr( 'Reproducir video: ' . $hd_f_title ); ?>"
               <?php else : ?>
               aria-label="<?php echo esc_attr( 'Leer nota: ' . $hd_f_title ); ?>"
               <?php endif; ?>>
              <div class="feature__media">
                <img class="feature__img" src="<?php echo esc_url( hd_thumb_url( $hd_f_id, 'large' ) ); ?>"
                     width="1080" height="1080" loading="lazy"
                     alt="<?php echo esc_attr( $hd_f_title ); ?>">
                <span class="vcard__scrim" aria-hidden="true"></span>
                <?php if ( $hd_f_video ) : ?>
                <span class="feature__badge" aria-hidden="true">
                  <?php echo hd_svg_youtube(); ?>
                  Video
                </span>
                <span class="play-btn feature__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
                <?php else : ?>
                <span class="feature__badge" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg>
                  Nota
                </span>
                <?php endif; ?>
              </div>
              <h3 class="feature__title"><?php echo esc_html( $hd_f_title ); ?></h3>
            </a>
            <div class="byline">
              <img class="byline__avatar" src="<?php echo esc_url( $hd_img . '/juansalvador1.png' ); ?>" width="1715" height="917" loading="lazy" alt="Foto de Juan Salvador">
              <p class="byline__meta">
                <span class="byline__name"><?php echo esc_html( get_the_author_meta( 'display_name', $hd_junior_feat->post_author ) ); ?></span>
                <span class="meta-sep" aria-hidden="true">•</span>
                <span><?php echo esc_html( hd_hace( $hd_f_id ) ); ?></span>
              </p>
            </div>
          </article>

          <!-- Lista -->
          <div class="junior-list">
            <?php foreach ( $hd_junior_list as $hd_v ) : ?>
              <?php hd_render_jitem( $hd_v->ID, 'Junior' ); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- ===================== CLIPS (formato corto 9:16) ===================== -->
    <?php
    // Videos con el término `clip` de la taxonomía `tema`; si el término no
    // existe o está vacío, los 6 videos más recientes (mismo comportamiento
    // visual: preview muteado por visibilidad vía home.js).
    $hd_clips = array();
    if ( get_term_by( 'slug', 'clip', 'tema' ) ) {
        $hd_clips = get_posts( array(
            'post_type'      => 'video',
            'posts_per_page' => 8,
            'post_status'    => 'publish',
            'meta_key'       => '_hd_youtube_id',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'tema',
                    'field'    => 'slug',
                    'terms'    => 'clip',
                ),
            ),
        ) );
    }
    if ( empty( $hd_clips ) ) {
        $hd_clips = $hd_latest; // fallback: los más recientes
    }
    ?>
    <?php if ( ! empty( $hd_clips ) ) : ?>
    <section class="section" id="clips" data-carousel aria-labelledby="clips-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--line" aria-hidden="true">
            <?php echo hd_svg_play(); ?>
          </span>
          <h2 class="section-head__title" id="clips-title">Clips</h2>
          <div class="section-head__actions">
            <button class="round-arrow clips__arrow" type="button" data-carousel-prev aria-label="Ver clips anteriores">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button class="round-arrow clips__arrow" type="button" data-carousel-next aria-label="Ver más clips">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
          </div>
        </div>

        <div class="clips" data-carousel-viewport>
          <?php foreach ( $hd_clips as $hd_v ) : ?>
            <?php
            $hd_c_yt    = hd_youtube_id( $hd_v->ID );
            $hd_c_title = get_the_title( $hd_v );
            if ( '' === $hd_c_yt ) {
                continue;
            }
            ?>
          <a class="clip" href="<?php echo esc_url( get_permalink( $hd_v ) ); ?>" data-youtube-id="<?php echo esc_attr( $hd_c_yt ); ?>" data-video-title="<?php echo esc_attr( $hd_c_title ); ?>" aria-label="<?php echo esc_attr( 'Reproducir clip: ' . $hd_c_title ); ?>">
            <img class="clip__img" src="<?php echo esc_url( hd_thumb_url( $hd_v->ID, 'medium_large' ) ); ?>" width="1280" height="720" loading="lazy" alt="<?php echo esc_attr( $hd_c_title ); ?>" style="object-position:50% 25%">
            <span class="clip__scrim" aria-hidden="true"></span>
            <span class="play-btn clip__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
            <h3 class="clip__title"><?php echo esc_html( $hd_c_title ); ?></h3>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- ===================== TEMPORADA DE HUMO 2026 (estático v1) ===================== -->
    <section class="section" id="humo" aria-labelledby="humo-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--line" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 3v6M17 3l-3 3M17 3l3 3M7 21v-6M7 21l-3-3M7 21l3-3"/></svg>
          </span>
          <h2 class="section-head__title" id="humo-title">Temporada de humo 2026</h2>
          <div class="section-head__actions">
            <a class="section-head__link" href="<?php echo esc_url( hd_tax_link( 'tema', 'fichaje' ) ); ?>">Ver todo
              <?php echo hd_svg_chevron(); ?>
            </a>
          </div>
        </div>

        <ul class="humo-list">
          <li class="humo">
            <span class="humo__date">14/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Millos.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">Millonarios compró a Jhomier Guerrero.</a>
          </li>
          <li class="humo">
            <span class="humo__date">13/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Atlético_Nacional.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">Junior firmó a Marino Hinestroza.</a>
          </li>
          <li class="humo">
            <span class="humo__date">12/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Millos.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Atlético_Nacional.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">Atlético Nacional compró a Daniel Ruiz.</a>
          </li>
          <li class="humo">
            <span class="humo__date">11/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_América_de_Cali.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_del_Deportivo_Independiente_Medellín.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">El DIM contrató al volante Luis Sánchez.</a>
          </li>
          <li class="humo">
            <span class="humo__date">10/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Deportes_Tolima.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">Junior aseguró al defensa Andrés Cadavid.</a>
          </li>
          <li class="humo">
            <span class="humo__date">09/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Junior_de_Barranquilla_2018.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Deportes_Tolima.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">Deportes Tolima fichó a Carlos Bacca.</a>
          </li>
          <li class="humo">
            <span class="humo__date">08/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_del_Deportivo_Independiente_Medellín.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_América_de_Cali.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">América de Cali firmó a Dorlan Pabón.</a>
          </li>
          <li class="humo">
            <span class="humo__date">07/06/2026</span>
            <span class="humo__move" aria-hidden="true">
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Atlético_Nacional.png' ); ?>" width="120" height="120" loading="lazy" alt="">
              <svg class="humo__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              <img class="humo__crest" src="<?php echo esc_url( $hd_img . '/Escudo_Millos.png' ); ?>" width="120" height="120" loading="lazy" alt="">
            </span>
            <a class="humo__text" href="#">Millonarios sumó al extremo Andrés Andrade.</a>
          </li>
        </ul>
      </div>
    </section>

    <!-- ===================== SELECCIÓN COLOMBIA (estático v1, espejo de Mundo Junior) ===================== -->
    <section class="section" id="seleccion" aria-labelledby="seleccion-title">
      <div class="container">
        <div class="section-head">
          <span class="section-head__icon section-head__icon--crest" aria-hidden="true">
            <img src="<?php echo esc_url( $hd_img . '/Escudo_de_la_Federación_Colombiana_de_Fútbol.png' ); ?>" width="2160" height="2160" loading="lazy" alt="">
          </span>
          <h2 class="section-head__title" id="seleccion-title">Selección Colombia</h2>
          <div class="section-head__actions">
            <a class="section-head__link" href="<?php echo esc_url( hd_tax_link( 'equipo', 'seleccion-colombia' ) ); ?>">Ver todo
              <?php echo hd_svg_chevron(); ?>
            </a>
          </div>
        </div>

        <div class="junior-grid">
          <!-- Feature -->
          <article class="feature">
            <a href="#" data-youtube-id="f9rJ95E_pTg" data-video-title="Contra todo pronóstico: la última hora de la Selección Colombia" aria-label="Reproducir video: Contra todo pronóstico, la última hora de la Selección Colombia">
              <div class="feature__media">
                <img class="feature__img" src="<?php echo esc_url( $hd_img . '/seleccion-derrota-gira.webp' ); ?>"
                     width="1280" height="720" loading="lazy"
                     alt="Jugadores de la Selección Colombia durante la concentración de Eliminatorias">
                <span class="vcard__scrim" aria-hidden="true"></span>
                <span class="feature__badge" aria-hidden="true">
                  <?php echo hd_svg_youtube(); ?>
                  12:37 · Video
                </span>
                <span class="play-btn feature__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
              </div>
              <h3 class="feature__title">Contra todo pronóstico: la última hora de la Selección Colombia</h3>
            </a>
            <div class="byline">
              <img class="byline__avatar" src="<?php echo esc_url( $hd_img . '/juansalvador2.png' ); ?>" width="1715" height="917" loading="lazy" alt="Foto de Juan Salvador">
              <p class="byline__meta">
                <span class="byline__name">Juan Salvador</span>
                <span class="meta-sep" aria-hidden="true">•</span>
                <span>Hace 6 horas</span>
              </p>
            </div>
          </article>

          <!-- Lista -->
          <div class="junior-list">
            <a class="jitem" href="#" data-youtube-id="9F1vfbcBZtI" data-video-title="Colombia define su once para el duelo clave de Eliminatorias" aria-label="Reproducir video: Colombia define su once para el duelo clave de Eliminatorias">
              <div class="jitem__thumb">
                <img class="jitem__img" src="<?php echo esc_url( $hd_img . '/img-02.jpg' ); ?>" width="1440" height="706" loading="lazy" alt="Entrenamiento de la Selección Colombia rumbo a las Eliminatorias">
                <span class="jitem__type" aria-hidden="true"><?php echo hd_svg_play(); ?>Video</span>
                <span class="jitem__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
              </div>
              <div class="jitem__body">
                <span class="jitem__cat jitem__cat--sel">Selección</span>
                <h3 class="jitem__title">Colombia define su once para el duelo clave de Eliminatorias</h3>
              </div>
            </a>
            <a class="jitem" href="#" data-youtube-id="f9rJ95E_pTg" data-video-title="El rendimiento de los jugadores de la Liga en la Tricolor" aria-label="Reproducir video: El rendimiento de los jugadores de la Liga en la Tricolor">
              <div class="jitem__thumb">
                <img class="jitem__img" src="<?php echo esc_url( $hd_img . '/img-08.jpg' ); ?>" width="520" height="358" loading="lazy" alt="Análisis del rendimiento de los jugadores de la Selección Colombia">
                <span class="jitem__type" aria-hidden="true"><?php echo hd_svg_play(); ?>Video</span>
                <span class="jitem__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
              </div>
              <div class="jitem__body">
                <span class="jitem__cat jitem__cat--sel">Selección</span>
                <h3 class="jitem__title">El rendimiento de los jugadores de la Liga en la Tricolor</h3>
              </div>
            </a>
            <a class="jitem" href="#" data-youtube-id="sbECwF3dEaI" data-video-title="Lo que se viene para la Selección en la próxima fecha FIFA" aria-label="Reproducir video: Lo que se viene para la Selección en la próxima fecha FIFA">
              <div class="jitem__thumb">
                <img class="jitem__img" src="<?php echo esc_url( $hd_img . '/img-10.jpg' ); ?>" width="1200" height="800" loading="lazy" alt="Concentración de la Selección Colombia antes de la fecha FIFA">
                <span class="jitem__type" aria-hidden="true"><?php echo hd_svg_play(); ?>Video</span>
                <span class="jitem__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
              </div>
              <div class="jitem__body">
                <span class="jitem__cat jitem__cat--sel">Selección</span>
                <h3 class="jitem__title">Lo que se viene para la Selección en la próxima fecha FIFA</h3>
              </div>
            </a>
            <a class="jitem" href="#" data-youtube-id="m-CS9-qo5rA" data-video-title="Jordan Barrera y su irrupción con la camiseta de Colombia" aria-label="Reproducir video: Jordan Barrera y su irrupción con la camiseta de Colombia">
              <div class="jitem__thumb">
                <img class="jitem__img" src="<?php echo esc_url( $hd_img . '/img-11.jpg' ); ?>" width="1200" height="800" loading="lazy" alt="Jordan Barrera con la camiseta de la Selección Colombia">
                <span class="jitem__type" aria-hidden="true"><?php echo hd_svg_play(); ?>Video</span>
                <span class="jitem__play" aria-hidden="true"><?php echo hd_svg_play(); ?></span>
              </div>
              <div class="jitem__body">
                <span class="jitem__cat jitem__cat--sel">Selección</span>
                <h3 class="jitem__title">Jordan Barrera y su irrupción con la camiseta de Colombia</h3>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== CTA SÍGUENOS EN INSTAGRAM ===================== -->
    <section class="section ig-section" aria-labelledby="ig-title">
      <div class="container">
        <div class="ig-cta">
          <span class="ig-cta__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
          </span>
          <div class="ig-cta__text">
            <h2 class="ig-cta__title" id="ig-title">Síguenos en Instagram</h2>
            <p class="ig-cta__sub">Detrás de cámaras, jugadas y lo mejor del show, en tu feed.</p>
          </div>
          <a class="btn btn--instagram" href="https://www.instagram.com/habladeportes" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/></svg>
            Seguir
          </a>
        </div>
      </div>
    </section>

    <!-- ===================== BANNER GASES DEL CARIBE (sponsor full-width) ===================== -->
    <aside class="gases-banner" aria-label="Publicidad — Gases del Caribe">
      <div class="container">
        <a class="gases-banner__link" href="#">
          <img class="gases-banner__img" src="<?php echo esc_url( $hd_img . '/publicidad_gc.png' ); ?>" width="5440" height="576" loading="lazy" alt="Publicidad — Gases del Caribe: Copa 60 aniversario, celebremos juntos nuestro aniversario">
        </a>
      </div>
    </aside>

<?php
get_footer();
