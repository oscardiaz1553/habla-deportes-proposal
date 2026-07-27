<?php
/**
 * Habla Deportes Core — Etiquetas dinámicas para Elementor.
 * ---------------------------------------------------------------
 * Son la pieza que permite montar el sitio con widgets NATIVOS.
 *
 * El problema: los datos del proyecto viven en metas protegidas
 * (`_hd_local`, `_hd_escudo`, `_hd_youtube_id`…). El guion bajo inicial
 * las marca como protegidas, y la etiqueta "Campo personalizado" de
 * Elementor no lista campos protegidos. Además, los escudos no están en
 * el partido sino en un TERM META de la taxonomía `equipo`, al que no
 * llega ninguna etiqueta nativa.
 *
 * La solución: exponer cada dato como su propia etiqueta dinámica. A
 * partir de aquí, en una plantilla de Loop Item se usa un widget Imagen
 * nativo y se le enchufa "Escudo del local"; un widget Título con
 * "Torneo del partido"; etc. Todo el estilado pasa a ser el nativo de
 * Elementor, sin controles a medida.
 *
 * Las etiquetas leen del post ACTUAL, así que funcionan dentro de un
 * Loop Item, en la plantilla de ficha individual y en cualquier sitio
 * con contexto de post.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Core\DynamicTags\Tag;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

/** Grupo propio, para que salgan juntas en el selector. */
add_action( 'elementor/dynamic_tags/register', function ( $dynamic_tags ) {
	$dynamic_tags->register_group( 'habla-deportes', array(
		'title' => 'Habla Deportes',
	) );

	$dynamic_tags->register( new HD_Tag_Escudo_Local() );
	$dynamic_tags->register( new HD_Tag_Escudo_Visitante() );
	$dynamic_tags->register( new HD_Tag_Partido_Torneo() );
	$dynamic_tags->register( new HD_Tag_Partido_Fecha() );
	$dynamic_tags->register( new HD_Tag_Partido_Estadio() );
	$dynamic_tags->register( new HD_Tag_Partido_Canal() );
	$dynamic_tags->register( new HD_Tag_Partido_Equipos() );
	$dynamic_tags->register( new HD_Tag_Video_Url() );
	$dynamic_tags->register( new HD_Tag_Video_Id() );
	$dynamic_tags->register( new HD_Tag_Video_Hace() );
	$dynamic_tags->register( new HD_Tag_Video_Miniatura() );
} );

/* =================================================================
 * Base para las etiquetas de texto: ahorra repetir grupo y categoría.
 * ============================================================== */
abstract class HD_Tag_Texto extends Tag {

	public function get_group() {
		return 'habla-deportes';
	}

	public function get_categories() {
		return array( TagsModule::TEXT_CATEGORY );
	}
}

/* =================================================================
 * PARTIDO
 * ============================================================== */

/** Escudo del equipo local (term meta `_hd_escudo` de la taxonomía `equipo`). */
class HD_Tag_Escudo_Local extends Data_Tag {

	public function get_name() {
		return 'hd-escudo-local';
	}

	public function get_title() {
		return 'Escudo del local';
	}

	public function get_group() {
		return 'habla-deportes';
	}

	public function get_categories() {
		return array( TagsModule::IMAGE_CATEGORY );
	}

	public function get_value( array $options = array() ) {
		return hd_tag_escudo_valor( '_hd_local', $this->get_settings( 'fallback' ) );
	}

	protected function register_controls() {
		$this->add_control( 'fallback', array(
			'label' => 'Si no hay escudo',
			'type'  => \Elementor\Controls_Manager::MEDIA,
		) );
	}
}

/** Escudo del equipo visitante. */
class HD_Tag_Escudo_Visitante extends Data_Tag {

	public function get_name() {
		return 'hd-escudo-visitante';
	}

	public function get_title() {
		return 'Escudo del visitante';
	}

	public function get_group() {
		return 'habla-deportes';
	}

	public function get_categories() {
		return array( TagsModule::IMAGE_CATEGORY );
	}

	public function get_value( array $options = array() ) {
		return hd_tag_escudo_valor( '_hd_visitante', $this->get_settings( 'fallback' ) );
	}

	protected function register_controls() {
		$this->add_control( 'fallback', array(
			'label' => 'Si no hay escudo',
			'type'  => \Elementor\Controls_Manager::MEDIA,
		) );
	}
}

/**
 * Resuelve el escudo de un equipo del partido actual.
 * Devuelve el array {id, url} que espera el widget Imagen, o el
 * respaldo configurado en la etiqueta si el equipo no tiene escudo.
 *
 * @param string $meta     `_hd_local` o `_hd_visitante`.
 * @param mixed  $fallback Valor del control de respaldo.
 * @return array
 */
function hd_tag_escudo_valor( $meta, $fallback ) {
	$term_id = (int) get_post_meta( get_the_ID(), $meta, true );

	if ( $term_id > 0 ) {
		$att_id = (int) get_term_meta( $term_id, '_hd_escudo', true );
		if ( $att_id > 0 ) {
			$src = wp_get_attachment_image_src( $att_id, 'full' );
			if ( $src ) {
				return array(
					'id'  => $att_id,
					'url' => $src[0],
				);
			}
		}
	}

	return is_array( $fallback ) ? $fallback : array( 'id' => 0, 'url' => '' );
}

/** Nombre del torneo del partido. */
class HD_Tag_Partido_Torneo extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-partido-torneo';
	}

	public function get_title() {
		return 'Torneo del partido';
	}

	public function render() {
		$term_id = (int) get_post_meta( get_the_ID(), '_hd_torneo', true );
		if ( $term_id <= 0 ) {
			return;
		}
		$t = get_term( $term_id, 'torneo' );
		if ( $t && ! is_wp_error( $t ) ) {
			echo esc_html( $t->name );
		}
	}
}

/** Fecha del partido en castellano, con la hora si se pide. */
class HD_Tag_Partido_Fecha extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-partido-fecha';
	}

	public function get_title() {
		return 'Fecha del partido';
	}

	protected function register_controls() {
		$this->add_control( 'con_hora', array(
			'label'        => 'Incluir la hora',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => 'Sí',
			'label_off'    => 'No',
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
	}

	public function render() {
		$post_id = get_the_ID();
		$fecha   = hd_fecha_larga_es( (string) get_post_meta( $post_id, '_hd_fecha', true ) );
		if ( '' === $fecha ) {
			return;
		}

		$hora = (string) get_post_meta( $post_id, '_hd_hora', true );
		if ( $hora && 'yes' === $this->get_settings( 'con_hora' ) ) {
			$fecha .= ' · ' . $hora;
		}

		echo esc_html( $fecha );
	}
}

/** Estadio del partido. */
class HD_Tag_Partido_Estadio extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-partido-estadio';
	}

	public function get_title() {
		return 'Estadio del partido';
	}

	public function render() {
		echo esc_html( (string) get_post_meta( get_the_ID(), '_hd_estadio', true ) );
	}
}

/** Canal o televisión del partido. */
class HD_Tag_Partido_Canal extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-partido-canal';
	}

	public function get_title() {
		return 'Canal del partido';
	}

	public function render() {
		echo esc_html( (string) get_post_meta( get_the_ID(), '_hd_canal', true ) );
	}
}

/** "Junior FC vs Atlético Nacional". Útil para títulos y textos alternativos. */
class HD_Tag_Partido_Equipos extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-partido-equipos';
	}

	public function get_title() {
		return 'Enfrentamiento (local vs visitante)';
	}

	protected function register_controls() {
		$this->add_control( 'separador', array(
			'label'   => 'Separador',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'vs',
		) );
	}

	public function render() {
		$post_id = get_the_ID();
		$local   = hd_equipo_nombre( (int) get_post_meta( $post_id, '_hd_local', true ) );
		$visit   = hd_equipo_nombre( (int) get_post_meta( $post_id, '_hd_visitante', true ) );

		if ( '' === $local && '' === $visit ) {
			return;
		}

		echo esc_html( trim( $local . ' ' . $this->get_settings( 'separador' ) . ' ' . $visit ) );
	}
}

/* =================================================================
 * VIDEO
 * ============================================================== */

/** URL pública del video en YouTube. Para el enlace de un botón. */
class HD_Tag_Video_Url extends Tag {

	public function get_name() {
		return 'hd-video-url';
	}

	public function get_title() {
		return 'URL del video en YouTube';
	}

	public function get_group() {
		return 'habla-deportes';
	}

	public function get_categories() {
		return array( TagsModule::URL_CATEGORY );
	}

	public function render() {
		$yt = hd_get_youtube_id( get_the_ID() );
		if ( '' === $yt ) {
			return;
		}
		echo esc_url( 'https://www.youtube.com/watch?v=' . $yt );
	}
}

/** ID de YouTube en crudo, por si hace falta componer una URL a mano. */
class HD_Tag_Video_Id extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-video-id';
	}

	public function get_title() {
		return 'ID de YouTube';
	}

	public function render() {
		echo esc_html( hd_get_youtube_id( get_the_ID() ) );
	}
}

/** "Hace 2 horas". En castellano fijo, sin depender del idioma de WordPress. */
class HD_Tag_Video_Hace extends HD_Tag_Texto {

	public function get_name() {
		return 'hd-video-hace';
	}

	public function get_title() {
		return 'Tiempo desde la publicación';
	}

	public function render() {
		echo esc_html( hd_home_hace( get_the_ID() ) );
	}
}

/**
 * Miniatura del video: la imagen destacada, y si falta, la miniatura del
 * propio YouTube. Evita huecos si alguna descarga falló al importar.
 */
class HD_Tag_Video_Miniatura extends Data_Tag {

	public function get_name() {
		return 'hd-video-miniatura';
	}

	public function get_title() {
		return 'Miniatura del video';
	}

	public function get_group() {
		return 'habla-deportes';
	}

	public function get_categories() {
		return array( TagsModule::IMAGE_CATEGORY );
	}

	public function get_value( array $options = array() ) {
		$post_id = get_the_ID();

		$att_id = get_post_thumbnail_id( $post_id );
		if ( $att_id ) {
			$src = wp_get_attachment_image_src( $att_id, 'full' );
			if ( $src ) {
				return array( 'id' => $att_id, 'url' => $src[0] );
			}
		}

		// Sin imagen destacada: tiramos de la miniatura de YouTube.
		$yt = hd_get_youtube_id( $post_id );
		if ( '' !== $yt ) {
			return array(
				'id'  => 0,
				'url' => 'https://i.ytimg.com/vi/' . rawurlencode( $yt ) . '/hqdefault.jpg',
			);
		}

		return array( 'id' => 0, 'url' => '' );
	}
}
