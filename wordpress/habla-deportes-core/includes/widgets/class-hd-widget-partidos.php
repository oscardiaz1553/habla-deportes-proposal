<?php
/**
 * Widget de Elementor — Centro de Partidos.
 * ---------------------------------------------------------------
 * Carrusel de próximos partidos del CPT `partido`, con las medidas de
 * propuesta/centro-partidos-spec.md como valores de partida.
 *
 * El HTML sale de hd_partidos_render(), el mismo del shortcode
 * [hd_partidos]. El panel de publicidad NO va aquí: es material del
 * cliente y su sitio es un widget Imagen al lado, en un contenedor de
 * dos columnas.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;

class HD_Widget_Partidos extends \Elementor\Widget_Base {

	public function get_name() {
		return 'hd-partidos';
	}

	public function get_title() {
		return 'Centro de Partidos';
	}

	public function get_icon() {
		return 'eicon-image-box';
	}

	public function get_categories() {
		return array( 'habla-deportes' );
	}

	public function get_keywords() {
		return array( 'partido', 'fixture', 'calendario', 'escudos', 'habla' );
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

		$this->add_control( 'cantidad', array(
			'label'       => 'Partidos',
			'type'        => Controls_Manager::NUMBER,
			'min'         => 1,
			'max'         => 10,
			'default'     => 5,
			'description' => 'Solo se muestran los que tienen fecha futura, del más próximo al más lejano.',
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
			'label'     => 'Cada (ms)',
			'type'      => Controls_Manager::NUMBER,
			'min'       => 1000,
			'max'       => 20000,
			'step'      => 500,
			'default'   => 6000,
			'condition' => array( 'autoplay' => 'yes' ),
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · BANNER ---------------- */
		$this->start_controls_section( 'seccion_banner', array(
			'label' => 'Banner',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'alto', array(
			'label'      => 'Alto',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 240, 'max' => 700 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .hd-match-slider__viewport' => 'height: {{SIZE}}px;',
			),
		) );

		$this->add_responsive_control( 'radio', array(
			'label'      => 'Redondeo',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .hd-match-slider__viewport' => 'border-radius: {{SIZE}}px;',
			),
		) );

		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => 'sombra',
			'label'    => 'Sombra',
			'selector' => '{{WRAPPER}} .hd-match-slider__viewport',
		) );

		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'      => 'overlay',
			'label'     => 'Velo sobre la foto',
			'types'     => array( 'classic', 'gradient' ),
			'separator' => 'before',
			'selector'  => '{{WRAPPER}} .hd-match-banner__overlay',
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · ESCUDOS ---------------- */
		$this->start_controls_section( 'seccion_escudos', array(
			'label' => 'Escudos',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'escudo_alto', array(
			'label'       => 'Alto de los escudos',
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 60, 'max' => 260 ) ),
			'description' => 'El ancho se ajusta solo, para respetar la proporción de cada escudo.',
			'selectors'   => array(
				'{{WRAPPER}} .hd-match-banner__crest' => 'height: {{SIZE}}px;',
			),
		) );

		$this->add_responsive_control( 'escudo_sep', array(
			'label'      => 'Separación entre escudos',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 8, 'max' => 90 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .hd-match-banner__fixture' => 'gap: {{SIZE}}px;',
			),
		) );

		$this->add_control( 'vs_color', array(
			'label'     => 'Color del "vs"',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-match-banner__vs' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'vs_tipografia',
			'label'    => 'Tipografía del "vs"',
			'selector' => '{{WRAPPER}} .hd-match-banner__vs',
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · TEXTOS ---------------- */
		$this->start_controls_section( 'seccion_textos', array(
			'label' => 'Textos',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'kicker_color', array(
			'label'     => 'Color del antetítulo',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-match-banner__kicker' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'kicker_tipografia',
			'label'    => 'Tipografía del antetítulo',
			'selector' => '{{WRAPPER}} .hd-match-banner__kicker',
		) );

		$this->add_control( 'info_color', array(
			'label'     => 'Color de fecha y estadio',
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .hd-match-banner__info' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'info_tipografia',
			'label'    => 'Tipografía de fecha y estadio',
			'selector' => '{{WRAPPER}} .hd-match-banner__info',
		) );

		$this->add_responsive_control( 'info_sep', array(
			'label'      => 'Separación fecha ↔ estadio',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 8, 'max' => 90 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .hd-match-banner__info' => 'column-gap: {{SIZE}}px;',
			),
		) );

		$this->add_control( 'canal_color', array(
			'label'     => 'Color del canal',
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .hd-match-banner__tv-text' => 'color: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · PUNTOS ---------------- */
		$this->start_controls_section( 'seccion_dots', array(
			'label'     => 'Puntos',
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'dots' => 'yes' ),
		) );

		$this->add_control( 'dot_color', array(
			'label'     => 'Punto inactivo',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-match-slider__dot::before' => 'background: {{VALUE}};' ),
		) );

		$this->add_control( 'dot_color_activo', array(
			'label'     => 'Punto activo',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-match-slider__dot[aria-current="true"]::before' => 'background: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'dot_abajo', array(
			'label'      => 'Distancia desde abajo',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'  => array( '{{WRAPPER}} .hd-match-slider__dots' => 'bottom: {{SIZE}}px;' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		echo hd_partidos_render( array(
			'cantidad' => $s['cantidad'],
			'autoplay' => $s['autoplay'],
			'interval' => $s['interval'],
			'dots'     => $s['dots'],
		) );
	}
}
