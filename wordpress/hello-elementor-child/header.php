<?php
/**
 * Habla Deportes — header portado del prototipo aprobado (index.html).
 * Topbar + header navy con megamenús + menú móvil (acordeones).
 * El markup conserva las clases y data-attributes EXACTOS que esperan
 * home.js y megamenu.js. En la home el header arranca transparente
 * sobre el hero; en páginas internas lleva .site-header--solid.
 *
 * Enlaces v1: home -> home_url, Videos -> archivo del CPT, equipos y
 * torneos -> término de taxonomía si existe (si no, '#'). Programas y
 * En Vivo quedan en '#' hasta portar esas plantillas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hd_img    = hd_proto_uri() . '/img';
$hd_home   = home_url( '/' );
$hd_videos = get_post_type_archive_link( 'video' );
if ( ! $hd_videos ) {
	$hd_videos = '#';
}
$hd_junior    = hd_tax_link( 'equipo', 'junior' );
$hd_seleccion = hd_tax_link( 'equipo', 'seleccion-colombia' );
$hd_solid     = is_front_page() ? '' : ' site-header--solid';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#294D6E">
  <link rel="icon" href="<?php echo esc_url( $hd_img . '/LogoHablaDeportes.webp' ); ?>" type="image/webp">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>

  <!-- ===================== TOPBAR ===================== -->
  <div class="topbar">
    <div class="container topbar__inner">
      <span class="live-dot live-dot--pulse" aria-hidden="true"></span>
      <div class="topbar__viewport">
        <div class="topbar__track">
          <p class="topbar__text">El show de la mañana, de lunes a viernes a las 10:00 AM</p>
          <p class="topbar__text topbar__text--clone" aria-hidden="true">El show de la mañana, de lunes a viernes a las 10:00 AM</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== HEADER ===================== -->
  <header class="site-header<?php echo esc_attr( $hd_solid ); ?>">
    <div class="container site-header__inner">
      <a class="brand" href="<?php echo esc_url( $hd_home ); ?>" aria-label="Habla Deportes — Inicio">
        <img class="brand__logo" src="<?php echo esc_url( $hd_img . '/LogoHablaDeportes-white.png' ); ?>"
             width="800" height="272" alt="Habla Deportes">
      </a>

      <nav class="primary-nav" aria-label="Navegación principal">
        <ul class="nav-list">
          <li><a class="nav-link" href="<?php echo esc_url( $hd_home ); ?>"<?php if ( is_front_page() ) { echo ' aria-current="page"'; } ?>>Inicio</a></li>
          <!-- JUNIOR — mega full-width (2 columnas + card destacada) -->
          <li class="mm mm--full">
            <a class="nav-link mm-trigger" href="<?php echo esc_url( $hd_junior ); ?>" aria-haspopup="true" aria-expanded="false">Junior
              <svg class="nav-link__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </a>
            <div class="mm-panel mm-panel--full" role="group" aria-label="Junior FC">
              <div class="mm-panel__inner mm-panel__inner--full container">
                <div class="mm-cols mm-cols--2">
                  <div class="mm-col">
                    <p class="mm-col__title">Contenido</p>
                    <a class="mm-link" href="<?php echo esc_url( $hd_junior ); ?>">Últimas noticias</a>
                    <a class="mm-link" href="<?php echo esc_url( $hd_videos ); ?>">Videos</a>
                    <a class="mm-link" href="<?php echo esc_url( hd_tax_link( 'tema', 'clip' ) ); ?>">Clips</a>
                    <a class="mm-link" href="<?php echo esc_url( hd_tax_link( 'tema', 'fichaje' ) ); ?>">Fichajes de Junior</a>
                  </div>
                  <div class="mm-col">
                    <p class="mm-col__title">El equipo</p>
                    <a class="mm-link" href="#">Próximo partido</a>
                    <span class="mm-note">Junior vs. Nacional · Dom. 7:30 p.m.</span>
                  </div>
                </div>
                <?php
                // Card destacada: último contenido con equipo:junior; si aún no
                // hay, cae al último video del canal (degradación elegante).
                $hd_jfeat = hd_latest_in_term( 'equipo', 'junior' );
                if ( ! $hd_jfeat ) {
                    $hd_jfeat = hd_latest_video();
                }
                if ( $hd_jfeat ) :
                ?>
                <a class="mm-feat mm-feat--wide" href="<?php echo esc_url( get_permalink( $hd_jfeat ) ); ?>">
                  <span class="mm-feat__media">
                    <img src="<?php echo esc_url( hd_thumb_url( $hd_jfeat->ID, 'medium_large' ) ); ?>" width="1080" height="1080" loading="lazy" alt="">
                  </span>
                  <span class="mm-feat__body">
                    <span class="mm-feat__eyebrow">Destacado</span>
                    <span class="mm-feat__title"><?php echo esc_html( get_the_title( $hd_jfeat ) ); ?></span>
                  </span>
                </a>
                <?php endif; ?>
              </div>
            </div>
          </li>
          <!-- FÚTBOL — mega full-width (3 columnas + card destacada) -->
          <li class="mm mm--full">
            <a class="nav-link mm-trigger" href="<?php echo esc_url( $hd_videos ); ?>" aria-haspopup="true" aria-expanded="false">Fútbol
              <svg class="nav-link__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </a>
            <div class="mm-panel mm-panel--full" role="group" aria-label="Fútbol">
              <div class="mm-panel__inner mm-panel__inner--full container">
                <div class="mm-cols mm-cols--3">
                <?php
                // Columnas dinámicas desde las taxonomías (hide_empty=false).
                // Si una taxonomía no tiene términos aún, la columna se omite
                // entera (sin imprimir <div> vacíos ni notices).
                $hd_mm_cols = array(
                    'Equipos'   => 'equipo',
                    'Torneos'   => 'torneo',
                    'Secciones' => 'tema',
                );
                foreach ( $hd_mm_cols as $hd_col_title => $hd_col_tax ) :
                    $hd_col_terms = hd_get_terms( $hd_col_tax );
                    if ( empty( $hd_col_terms ) ) {
                        continue;
                    }
                    ?>
                    <div class="mm-col">
                      <p class="mm-col__title"><?php echo esc_html( $hd_col_title ); ?></p>
                      <?php foreach ( $hd_col_terms as $hd_term ) : ?>
                        <a class="mm-link" href="<?php echo esc_url( hd_term_url( $hd_term ) ); ?>"><?php echo esc_html( $hd_term->name ); ?></a>
                      <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php
                // Card destacada: el video más reciente del canal.
                $hd_ffeat = hd_latest_video();
                if ( $hd_ffeat ) :
                ?>
                <a class="mm-feat mm-feat--wide" href="<?php echo esc_url( get_permalink( $hd_ffeat ) ); ?>">
                  <span class="mm-feat__media">
                    <img src="<?php echo esc_url( hd_thumb_url( $hd_ffeat->ID, 'medium_large' ) ); ?>" width="1280" height="720" loading="lazy" alt="">
                  </span>
                  <span class="mm-feat__body">
                    <span class="mm-feat__eyebrow">Último video</span>
                    <span class="mm-feat__title"><?php echo esc_html( get_the_title( $hd_ffeat ) ); ?></span>
                  </span>
                </a>
                <?php endif; ?>
              </div>
            </div>
          </li>
          <!-- PROGRAMAS — mega full-width (2 columnas + card AL AIRE) -->
          <li class="mm mm--full">
            <a class="nav-link mm-trigger" href="#" aria-haspopup="true" aria-expanded="false">Programas
              <svg class="nav-link__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </a>
            <div class="mm-panel mm-panel--full" role="group" aria-label="Programas">
              <div class="mm-panel__inner mm-panel__inner--full container">
                <div class="mm-cols mm-cols--2">
                  <div class="mm-col">
                    <p class="mm-col__title">El show</p>
                    <a class="mm-link" href="#">El show de la mañana</a>
                    <a class="mm-link" href="#">Escuchar en vivo / Radio</a>
                    <span class="mm-note">Horario: L–V 10:00 a.m. – 12:00 m.</span>
                  </div>
                  <div class="mm-col">
                    <p class="mm-col__title">Contenido del show</p>
                    <a class="mm-link" href="<?php echo esc_url( $hd_videos ); ?>">Últimos episodios</a>
                    <a class="mm-link" href="#">Parrilla de la semana</a>
                    <a class="mm-link" href="#">Presentadores</a>
                  </div>
                </div>
                <a class="mm-feat mm-feat--wide mm-feat--live" href="#">
                  <span class="mm-feat__media">
                    <img src="<?php echo esc_url( $hd_img . '/juansalvador1.png' ); ?>" width="1715" height="917" loading="lazy" alt="">
                    <span class="mm-feat__live"><span class="mm-feat__dot" aria-hidden="true"></span>Al aire ahora</span>
                  </span>
                  <span class="mm-feat__body">
                    <span class="mm-feat__eyebrow">Radio en vivo</span>
                    <span class="mm-feat__title">El show de la mañana</span>
                    <span class="mm-feat__cta">Escuchar en vivo
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                  </span>
                </a>
              </div>
            </div>
          </li>
          <!-- MÁS — mega full-width (institucional + redes + card CTA de pauta) -->
          <li class="mm mm--full">
            <a class="nav-link mm-trigger" href="#" aria-haspopup="true" aria-expanded="false">Más
              <svg class="nav-link__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </a>
            <div class="mm-panel mm-panel--full" role="group" aria-label="Más">
              <div class="mm-panel__inner mm-panel__inner--full container">
                <div class="mm-cols mm-cols--2">
                  <div class="mm-col">
                    <p class="mm-col__title">Institucional</p>
                    <a class="mm-link" href="#">Nosotros</a>
                    <a class="mm-link" href="#">Publicidad / Pauta</a>
                    <a class="mm-link" href="mailto:info@habladeportes.co">Contacto</a>
                    <a class="mm-link" href="#">Política de privacidad</a>
                    <a class="mm-link" href="#">Términos y condiciones</a>
                  </div>
                  <div class="mm-col">
                    <p class="mm-col__title">Redes</p>
                    <a class="mm-link mm-link--ext" href="https://www.youtube.com/@HablaDeportesRadio" target="_blank" rel="noopener">YouTube
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </a>
                    <a class="mm-link mm-link--ext" href="https://www.instagram.com/habladeportes" target="_blank" rel="noopener">Instagram
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </a>
                    <a class="mm-link mm-link--ext" href="#" aria-label="X (Twitter) de Habla Deportes">X (Twitter)
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </a>
                  </div>
                </div>
                <a class="mm-feat mm-feat--wide mm-feat--cta" href="#">
                  <span class="mm-feat__media">
                    <img src="<?php echo esc_url( $hd_img . '/transelca_mobile.png' ); ?>" width="1744" height="902" loading="lazy" alt="">
                  </span>
                  <span class="mm-feat__body">
                    <span class="mm-feat__eyebrow">Publicidad</span>
                    <span class="mm-feat__title">Pauta con Habla Deportes</span>
                    <span class="mm-feat__cta">Escríbenos
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </span>
                  </span>
                </a>
              </div>
            </div>
          </li>
          <li aria-hidden="true"><span class="nav-sep"></span></li>
          <li>
            <a class="btn btn--subscribe btn--on-dark" href="https://www.youtube.com/@HablaDeportesRadio?sub_confirmation=1" target="_blank" rel="noopener">
              Suscribirse
              <?php echo hd_svg_youtube( 'btn__yt' ); ?>
            </a>
          </li>
        </ul>
      </nav>

      <button class="nav-toggle" type="button" data-nav-toggle
              aria-expanded="false" aria-controls="mobile-menu" aria-label="Abrir menú">
        <svg class="icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg class="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
  </header>

  <!-- ===================== MENÚ MÓVIL ===================== -->
  <div class="mobile-menu" id="mobile-menu" data-mobile-menu aria-hidden="true" aria-label="Menú de navegación">
    <div class="mobile-menu__head">
      <img class="brand__logo" src="<?php echo esc_url( $hd_img . '/LogoHablaDeportes.webp' ); ?>" width="1600" height="545" alt="Habla Deportes">
      <button class="nav-toggle" type="button" data-menu-close aria-label="Cerrar menú">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
    </div>
    <a class="mobile-menu__link" href="<?php echo esc_url( $hd_home ); ?>"<?php if ( is_front_page() ) { echo ' aria-current="page"'; } ?>>Inicio</a>
    <!-- Junior (acordeón) -->
    <div class="mm-acc">
      <button class="mm-acc-trigger" type="button" aria-expanded="false" aria-controls="macc-junior">Junior
        <svg class="mm-acc-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="mm-acc-panel" id="macc-junior"><div class="mm-acc-inner">
        <a class="mobile-menu__link" href="<?php echo esc_url( $hd_junior ); ?>">Últimas noticias</a>
        <a class="mobile-menu__link" href="<?php echo esc_url( $hd_videos ); ?>">Videos</a>
        <a class="mobile-menu__link" href="<?php echo esc_url( hd_tax_link( 'tema', 'clip' ) ); ?>">Clips</a>
        <a class="mobile-menu__link" href="<?php echo esc_url( hd_tax_link( 'tema', 'fichaje' ) ); ?>">Fichajes de Junior</a>
        <a class="mobile-menu__link" href="#">Próximo partido</a>
      </div></div>
    </div>
    <!-- Fútbol (acordeón) -->
    <div class="mm-acc">
      <button class="mm-acc-trigger" type="button" aria-expanded="false" aria-controls="macc-futbol">Fútbol
        <svg class="mm-acc-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="mm-acc-panel" id="macc-futbol"><div class="mm-acc-inner">
        <?php
        // Mismo origen de datos que el megamenú de escritorio: términos de
        // equipo/torneo/tema. Cada grupo se omite si no tiene términos.
        foreach ( $hd_mm_cols as $hd_col_title => $hd_col_tax ) :
            $hd_col_terms = hd_get_terms( $hd_col_tax );
            if ( empty( $hd_col_terms ) ) {
                continue;
            }
            ?>
            <span class="mm-acc-note"><?php echo esc_html( $hd_col_title ); ?></span>
            <?php foreach ( $hd_col_terms as $hd_term ) : ?>
              <a class="mobile-menu__link" href="<?php echo esc_url( hd_term_url( $hd_term ) ); ?>"><?php echo esc_html( $hd_term->name ); ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
      </div></div>
    </div>
    <!-- Programas (acordeón) -->
    <div class="mm-acc">
      <button class="mm-acc-trigger" type="button" aria-expanded="false" aria-controls="macc-programas">Programas
        <svg class="mm-acc-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="mm-acc-panel" id="macc-programas"><div class="mm-acc-inner">
        <a class="mobile-menu__link" href="#">El show de la mañana</a>
        <a class="mobile-menu__link" href="#">Escuchar en vivo / Radio</a>
        <a class="mobile-menu__link" href="<?php echo esc_url( $hd_videos ); ?>">Últimos episodios</a>
        <a class="mobile-menu__link" href="#">Parrilla de la semana</a>
        <a class="mobile-menu__link" href="#">Presentadores</a>
        <span class="mm-acc-note">Horario: L–V 10:00 a.m. – 12:00 m.</span>
      </div></div>
    </div>
    <!-- Más (acordeón) -->
    <div class="mm-acc">
      <button class="mm-acc-trigger" type="button" aria-expanded="false" aria-controls="macc-mas">Más
        <svg class="mm-acc-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="mm-acc-panel" id="macc-mas"><div class="mm-acc-inner">
        <a class="mobile-menu__link" href="#">Nosotros</a>
        <a class="mobile-menu__link" href="#">Publicidad / Pauta</a>
        <a class="mobile-menu__link" href="mailto:info@habladeportes.co">Contacto</a>
        <a class="mobile-menu__link" href="#">Política de privacidad</a>
        <a class="mobile-menu__link" href="#">Términos y condiciones</a>
      </div></div>
    </div>
    <a class="btn btn--subscribe" href="https://www.youtube.com/@HablaDeportesRadio?sub_confirmation=1" target="_blank" rel="noopener">
      Suscribirse
      <?php echo hd_svg_youtube( 'btn__yt' ); ?>
    </a>
  </div>

  <main id="contenido">
