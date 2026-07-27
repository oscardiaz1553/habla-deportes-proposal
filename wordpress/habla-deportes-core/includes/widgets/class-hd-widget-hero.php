<?php
/**
 * Widget de Elementor — Hero de video.
 * ---------------------------------------------------------------
 * Pinta el hero con los últimos videos importados: crossfade real de
 * dos capas, Ken Burns, flechas, dots, autoavance y swipe.
 *
 * El HTML sale de hd_hero_render(), el mismo que usa el shortcode
 * [hd_hero]: una sola maquetación que mantener.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Text_Shadow;

class HD_Widget_Hero extends \Elementor\Widget_Base {

	public function get_name() {
		return 'hd-hero';
	}

	public function get_title() {
		return 'Hero de video';
	}

	public function get_icon() {
		return 'eicon-slider-video';
	}

	public function get_categories() {
		return array( 'habla-deportes' );
	}

	public function get_keywords() {
		return array( 'hero', 'video', 'destacado', 'carrusel', 'habla' );
	}

	public function get_style_depends() {
		return array( 'hd-home' );
	}

	public function get_script_depends() {
		return array( 'hd-home' );
	}

	protected function register_controls() {

		/* ---------------- CONTENIDO ---------------- */
		$this->start_controls_section( 'seccion_contenido', array(
			'label' => 'Contenido',
		) );

		$this->add_control( 'fuente', array(
			'label'       => 'Qué se destaca',
			'type'        => Controls_Manager::SELECT,
			'default'     => 'video',
			'options'     => array(
				'video' => 'Videos del canal',
				'post'  => 'Noticias y notas',
				'ambos' => 'Ambos, por fecha',
			),
			'description' => 'Las notas suelen quedar mejor en el hero: llevan foto propia en alta y titular escrito, no la portada de YouTube.',
		) );

		$this->add_control( 'cantidad', array(
			'label'       => 'Piezas en el hero',
			'type'        => Controls_Manager::NUMBER,
			'min'         => 2,
			'max'         => 8,
			'default'     => 3,
			'description' => 'Se toman las más recientes. Mínimo 2: el fundido necesita dos capas.',
		) );

		$this->add_control( 'flechas', array(
			'label'        => 'Flechas laterales',
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => 'Sí',
			'label_off'    => 'No',
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$this->add_control( 'dots', array(
			'label'        => 'Puntos de navegación',
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => 'Sí',
			'label_off'    => 'No',
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$this->add_control( 'autoplay', array(
			'label'        => 'Avance automático',
			'type'         => Controls_Manager::SWITCHER,
			'label_on'     => 'Sí',
			'label_off'    => 'No',
			'default'      => 'yes',
			'return_value' => 'yes',
			'separator'    => 'before',
		) );

		$this->add_control( 'interval', array(
			'label'       => 'Cada (ms)',
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1000,
			'max'         => 20000,
			'step'        => 500,
			'default'     => 6000,
			'condition'   => array( 'autoplay' => 'yes' ),
			'description' => 'Se pausa al pasar el cursor o al enfocar dentro del hero.',
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · IMAGEN ---------------- */
		$this->start_controls_section( 'seccion_imagen', array(
			'label' => 'Imagen',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'alto', array(
			'label'      => 'Alto',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array(
				'px' => array( 'min' => 300, 'max' => 900 ),
				'vh' => array( 'min' => 30, 'max' => 100 ),
			),
			'selectors'  => array(
				'{{WRAPPER}} .hd-hero__slide' => 'height: {{SIZE}}{{UNIT}}; min-height: 0;',
			),
		) );

		$this->add_control( 'encuadre', array(
			'label'       => 'Encuadre vertical',
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( '%' ),
			'range'       => array( '%' => array( 'min' => 0, 'max' => 100 ) ),
			'default'     => array( 'unit' => '%', 'size' => 25 ),
			'description' => 'Qué zona de la miniatura manda. 0% arriba, 100% abajo.',
			'selectors'   => array(
				'{{WRAPPER}} .hd-hero__img' => 'object-position: 50% {{SIZE}}%;',
			),
		) );

		/* SELECT y no SWITCHER a propósito: un switcher apagado vale '' y
		   Elementor no aplica los selectores de un control vacío, así que no
		   habría forma de imprimir el `animation-name: none` que lo apaga.
		   Un select siempre tiene valor, y el diccionario lo traduce. */
		$this->add_control( 'kenburns', array(
			'label'                => 'Zoom lento (Ken Burns)',
			'type'                 => Controls_Manager::SELECT,
			'default'              => 'si',
			'options'              => array(
				'si' => 'Sí',
				'no' => 'No',
			),
			'selectors_dictionary' => array(
				'si' => 'hdHeroKenBurns',
				'no' => 'none',
			),
			'selectors'            => array(
				'{{WRAPPER}} .hd-hero__img.is-active' => 'animation-name: {{VALUE}};',
			),
		) );

		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'scrim',
			'label'    => 'Velo sobre la imagen',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .hd-hero__scrim',
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · TITULAR ---------------- */
		$this->start_controls_section( 'seccion_titular', array(
			'label' => 'Titular y fecha',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'titulo_color', array(
			'label'     => 'Color del titular',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-hero__title' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'titulo_tipografia',
			'label'    => 'Tipografía del titular',
			'selector' => '{{WRAPPER}} .hd-hero__title',
		) );

		$this->add_group_control( Group_Control_Text_Shadow::get_type(), array(
			'name'     => 'titulo_sombra',
			'label'    => 'Sombra del titular',
			'selector' => '{{WRAPPER}} .hd-hero__title',
		) );

		$this->add_control( 'titulo_lineas', array(
			'label'       => 'Líneas máximas',
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 4,
			'default'     => 2,
			'description' => 'Lo que sobra se recorta con puntos suspensivos.',
			'selectors'   => array(
				'{{WRAPPER}} .hd-hero__title' => '-webkit-line-clamp: {{VALUE}}; line-clamp: {{VALUE}};',
			),
		) );

		$this->add_control( 'meta_color', array(
			'label'     => 'Color de la fecha',
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .hd-hero__meta' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'meta_tipografia',
			'label'    => 'Tipografía de la fecha',
			'selector' => '{{WRAPPER}} .hd-hero__meta',
		) );

		$this->add_responsive_control( 'contenido_abajo', array(
			'label'      => 'Separación desde abajo',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
			'separator'  => 'before',
			'selectors'  => array(
				'{{WRAPPER}} .hd-hero__content' => 'bottom: {{SIZE}}px;',
			),
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · CONTROLES ---------------- */
		$this->start_controls_section( 'seccion_controles', array(
			'label' => 'Play, flechas y puntos',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'play_tam', array(
			'label'      => 'Tamaño del play',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 40, 'max' => 160 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .hd-hero__play' => 'width: {{SIZE}}px; height: {{SIZE}}px;',
			),
		) );

		$this->add_control( 'play_color', array(
			'label'     => 'Color del play',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-hero__play' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'flechas_color', array(
			'label'     => 'Color de las flechas',
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'condition' => array( 'flechas' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .hd-hero__arrow' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'dots_color', array(
			'label'     => 'Punto inactivo',
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'condition' => array( 'dots' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .hd-hero__dot' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'dots_color_activo', array(
			'label'     => 'Punto activo',
			'type'      => Controls_Manager::COLOR,
			'condition' => array( 'dots' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .hd-hero__dot[aria-current="true"]' => 'background: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'dots_sep', array(
			'label'      => 'Separación de los puntos',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'condition'  => array( 'dots' => 'yes' ),
			'selectors'  => array( '{{WRAPPER}} .hd-hero__dots' => 'margin-top: {{SIZE}}px;' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		echo hd_hero_render( array(
			'cantidad' => $s['cantidad'],
			'fuente'   => $s['fuente'],
			'autoplay' => $s['autoplay'],
			'interval' => $s['interval'],
			'flechas'  => $s['flechas'],
			'dots'     => $s['dots'],
		) );
	}
}
