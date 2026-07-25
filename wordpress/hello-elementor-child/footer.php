<?php
/**
 * Habla Deportes — footer portado del prototipo aprobado.
 * Cierra el <main> que abre header.php, pinta el footer navy de 4
 * columnas + el lightbox de video compartido (shell que home.js llena
 * al hacer clic en cualquier pieza con data-youtube-id) y wp_footer().
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
?>
  </main>

  <!-- ===================== FOOTER ===================== -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer__grid">
        <!-- Marca -->
        <div>
          <img class="footer__brand-logo" src="<?php echo esc_url( $hd_img . '/LogoHablaDeportes-white.png' ); ?>" width="800" height="272" loading="lazy" alt="Habla Deportes">
          <p class="footer__tagline">Video, análisis y radio del fútbol colombiano. Junior de Barranquilla, Selección Colombia y todo lo que hay que hablar.</p>
          <div class="footer__social">
            <a href="https://www.youtube.com/@HablaDeportesRadio" target="_blank" rel="noopener" aria-label="YouTube de Habla Deportes">
              <?php echo hd_svg_youtube(); ?>
            </a>
            <a href="https://www.instagram.com/habladeportes" target="_blank" rel="noopener" aria-label="Instagram de Habla Deportes">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            </a>
            <a href="#" aria-label="X de Habla Deportes">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-6.9l-5.4-7-6.2 7H1.6l8.1-9.3L1 2h7l4.9 6.4L18.9 2Zm-1.2 18h1.7L6.4 3.8H4.6L17.7 20Z"/></svg>
            </a>
          </div>
        </div>

        <!-- Secciones -->
        <nav aria-label="Secciones del sitio">
          <h2 class="footer__col-title">Secciones</h2>
          <div class="footer__links">
            <a href="<?php echo esc_url( $hd_home ); ?>">Inicio</a>
            <a href="<?php echo esc_url( hd_tax_link( 'equipo', 'junior' ) ); ?>">Junior FC</a>
            <a href="<?php echo esc_url( hd_tax_link( 'equipo', 'seleccion-colombia' ) ); ?>">Selección Colombia</a>
            <a href="<?php echo esc_url( hd_tax_link( 'tema', 'fichaje' ) ); ?>">Fichajes</a>
            <a href="<?php echo esc_url( $hd_videos ); ?>">Videos</a>
          </div>
        </nav>

        <!-- El programa -->
        <div>
          <h2 class="footer__col-title">El programa</h2>
          <p class="footer__show"><span class="live-dot" aria-hidden="true"></span>El show de la mañana</p>
          <p class="footer__schedule">Lunes a viernes<br>10:00 a.m. – 12:00 m.<br>Barranquilla, Colombia</p>
          <div class="footer__links" style="margin-top:var(--space-4)">
            <a href="#">Escuchar en vivo</a>
            <a href="#">Parrilla de la semana</a>
          </div>
        </div>

        <!-- Institucional + sponsor -->
        <div>
          <h2 class="footer__col-title">Institucional</h2>
          <div class="footer__links">
            <a href="#">Quiénes somos</a>
            <a href="mailto:info@habladeportes.co">Contacto</a>
            <a href="#">Política de privacidad</a>
            <a href="#">Términos y condiciones</a>
          </div>
          <div class="footer__sponsor">
            <span class="footer__sponsor-label">Presentado por</span>
            <img src="<?php echo esc_url( $hd_img . '/logo-transelca.svg' ); ?>" width="200" height="44" loading="lazy" alt="Transelca">
          </div>
        </div>
      </div>

      <div class="footer__bottom">
        <p>© <?php echo esc_html( date_i18n( 'Y' ) ); ?> Habla Deportes. Todos los derechos reservados.</p>
        <div class="footer__legal">
          <span>Barranquilla, Colombia</span>
          <a href="mailto:info@habladeportes.co">info@habladeportes.co</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- ===================== LIGHTBOX DE VIDEO (YouTube) ===================== -->
  <!-- Shell estático; home.js inyecta el <iframe> al abrir y lo ELIMINA
       al cerrar (para detener el audio). role/aria-modal + focus trap. -->
  <div class="lightbox" data-lightbox hidden role="dialog" aria-modal="true"
       aria-label="Reproductor de video de Habla Deportes">
    <div class="lightbox__backdrop" data-lightbox-close aria-hidden="true"></div>
    <div class="lightbox__dialog" role="document">
      <button class="lightbox__close" type="button" data-lightbox-close aria-label="Cerrar video">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
      </button>
      <div class="lightbox__frame" data-lightbox-frame>
        <!-- iframe inyectado por JS al abrir -->
      </div>
    </div>
  </div>

<?php wp_footer(); ?>
</body>
</html>
