<?php
/**
 * Plugin Name:       Habla Deportes Core
 * Description:       Modelo de contenido (videos, partidos, equipos/torneos/temas) e importador automático del canal de YouTube. Funcionalidad del sitio, independiente del diseño.
 * Version:           1.0.0
 * Author:            Out. Studio — Oscar Díaz
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Text Domain:       habla-deportes-core
 *
 * ---------------------------------------------------------------
 * Este plugin contiene SOLO datos y funcionalidad:
 *   - CPT `video` + taxonomías `equipo` / `torneo` / `tema`.
 *   - Siembra idempotente de los 14 términos base.
 *   - Importador RSS del canal de YouTube (cron horario + página admin).
 *   - CPT `partido` con meta box "Detalles del partido", escudo por
 *     equipo (term meta) y siembra de 3 partidos demo.
 *   - Shortcodes de presentación del video ([hd_video_player] y
 *     [hd_youtube_url]) en includes/shortcodes.php.
 *
 * NO contiene plantillas del prototipo: el front lo diseña el usuario
 * con Elementor. Las únicas piezas con markup propio son los shortcodes
 * ([hd_videos], [hd_video_player]), autónomos, con su CSS/JS mínimo en
 * assets/css/shortcodes.css y assets/js/shortcodes.js, encolados solo
 * si el shortcode correspondiente se usa en la página.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acceso directo prohibido.
}

define( 'HDC_VERSION', '1.0.0' );
define( 'HDC_PATH', plugin_dir_path( __FILE__ ) );   // .../plugins/habla-deportes-core/
define( 'HDC_URL', plugin_dir_url( __FILE__ ) );     // https://sitio/wp-content/plugins/habla-deportes-core/
define( 'HDC_FILE', __FILE__ );

require_once HDC_PATH . 'includes/cpt.php';
require_once HDC_PATH . 'includes/taxonomy-seed.php';
require_once HDC_PATH . 'includes/youtube-importer.php';
require_once HDC_PATH . 'includes/cpt-partido.php';
require_once HDC_PATH . 'includes/shortcodes.php';
require_once HDC_PATH . 'includes/shortcodes-home.php';

/* Widgets propios de Elementor. Solo si Elementor está activo: el plugin
   debe seguir funcionando (CPT, importador, shortcodes) sin él. */
if ( did_action( 'elementor/loaded' ) || defined( 'ELEMENTOR_VERSION' ) ) {
	require_once HDC_PATH . 'includes/elementor.php';
} else {
	add_action( 'elementor/loaded', function () {
		require_once HDC_PATH . 'includes/elementor.php';
	} );
}

/* ============================================================
 * Activación / desactivación
 * ============================================================
 * En un tema esto vivía en `switch_theme` y en un flag
 * `hd_model_flushed` leído en cada `init`. En un plugin lo correcto
 * es usar los hooks de ciclo de vida: se registran los CPTs y las
 * taxonomías a mano (en la activación `init` ya pasó) y solo
 * DESPUÉS se refrescan los permalinks.
 */

function hdc_activate() {
	// 1) Registrar el modelo de contenido para que las reglas existan.
	if ( function_exists( 'hd_register_content_model' ) ) {
		hd_register_content_model();
	}
	if ( function_exists( 'hd_register_partido' ) ) {
		hd_register_partido();
	}

	// 2) Sembrar términos base (idempotente: no duplica).
	if ( function_exists( 'hd_seed_terms' ) ) {
		hd_seed_terms();
	}

	// 3) Programar el cron del importador si no está ya programado.
	if ( ! wp_next_scheduled( 'hd_yt_import_event' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'hd_yt_import_event' );
	}

	// 4) Permalinks al final, con los CPTs ya registrados.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hdc_activate' );

function hdc_deactivate() {
	wp_clear_scheduled_hook( 'hd_yt_import_event' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hdc_deactivate' );
