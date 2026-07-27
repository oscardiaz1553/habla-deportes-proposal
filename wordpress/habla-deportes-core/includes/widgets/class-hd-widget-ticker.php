<?php
/**
 * Widget de Elementor — Marquesina de titulares.
 * ---------------------------------------------------------------
 * Franja con los últimos videos desplazándose en bucle continuo.
 * El HTML sale de hd_ticker_render(), el mismo del shortcode
 * [hd_ticker].
 *
 * La velocidad se controla con la duración de la animación CSS, no
 * con JavaScript: es un control de estilo que Elementor aplica en
 * vivo sin repintar el widget.
 * ---------------------------------------------------------------
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class HD_Widget_Ticker extends \Elementor\Widget_Base {

	public function get_name() {
		return 'hd-ticker';
	}

	public function get_title() {
		return 'Marquesina de titulares';
	}

	public function get_icon() {
		return 'eicon-animation-text';
	}

	public function get_categories() {
		return array( 'habla-deportes' );
	}

	public function get_keywords() {
		return array( 'ticker', 'marquesina', 'titulares', 'noticias', 'habla' );
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

		$this->add_control( 'etiqueta', array(
			'label'   => 'Etiqueta',
			'type'    => Controls_Manager::TEXT,
			'default' => 'Noticias',
		) );

		$this->add_control( 'categoria', array(
			'label'       => 'Rótulo de cada titular',
			'type'        => Controls_Manager::TEXT,
			'default'     => 'Videos',
			'description' => 'Texto en negrita delante de cada titular.',
		) );

		$this->add_control( 'cantidad', array(
			'label'   => 'Titulares',
			'type'    => Controls_Manager::NUMBER,
			'min'     => 3,
			'max'     => 20,
			'default' => 8,
		) );

		$this->add_control( 'duracion', array(
			'label'       => 'Duración de una vuelta (s)',
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 's' ),
			'range'       => array( 's' => array( 'min' => 10, 'max' => 120 ) ),
			'default'     => array( 'unit' => 's', 'size' => 38 ),
			'description' => 'Más segundos, más lento. Se pausa al pasar el cursor.',
			'selectors'   => array(
				'{{WRAPPER}} .hd-ticker__track' => 'animation-duration: {{SIZE}}s;',
			),
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · FRANJA ---------------- */
		$this->start_controls_section( 'seccion_franja', array(
			'label' => 'Franja',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'fondo', array(
			'label'     => 'Color de fondo',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .hd-ticker'        => 'background: {{VALUE}};',
				'{{WRAPPER}} .hd-ticker__label' => 'background: {{VALUE}};',
			),
		) );

		$this->add_responsive_control( 'alto_relleno', array(
			'label'      => 'Alto (relleno vertical)',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 4, 'max' => 40 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .hd-ticker__label' => 'padding-top: {{SIZE}}px; padding-bottom: {{SIZE}}px;',
			),
		) );

		$this->add_responsive_control( 'separacion', array(
			'label'       => 'Separación entre titulares',
			'type'        => Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 16, 'max' => 120 ) ),
			'description' => 'Se aplica también en el empalme del bucle.',
			'selectors'   => array(
				'{{WRAPPER}} .hd-ticker__item' => 'margin-right: {{SIZE}}px;',
			),
		) );

		$this->end_controls_section();

		/* ---------------- ESTILO · TEXTOS ---------------- */
		$this->start_controls_section( 'seccion_textos', array(
			'label' => 'Textos',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'etiqueta_color', array(
			'label'     => 'Color de la etiqueta',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-ticker__label' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'punto_color', array(
			'label'     => 'Color del punto',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-live-dot' => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'etiqueta_tipografia',
			'label'    => 'Tipografía de la etiqueta',
			'selector' => '{{WRAPPER}} .hd-ticker__label',
		) );

		$this->add_control( 'cat_color', array(
			'label'     => 'Color del rótulo',
			'type'      => Controls_Manager::COLOR,
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .hd-ticker__cat' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'titular_color', array(
			'label'     => 'Color del titular',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-ticker__headline' => 'color: {{VALUE}};' ),
		) );

		$this->add_control( 'titular_color_hover', array(
			'label'     => 'Color del titular al pasar',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .hd-ticker__item:hover .hd-ticker__headline' => 'color: {{VALUE}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => 'titular_tipografia',
			'label'    => 'Tipografía de los titulares',
			'selector' => '{{WRAPPER}} .hd-ticker__item',
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		echo hd_ticker_render( array(
			'cantidad'  => $s['cantidad'],
			'etiqueta'  => $s['etiqueta'],
			'categoria' => $s['categoria'],
		) );
	}
}
