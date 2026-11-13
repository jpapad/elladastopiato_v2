<?php
/**
 * Widget «Χάρτης Ελλάδας» για το Elementor.
 *
 * Όλα τα πεδία είναι προαιρετικά: όσα μένουν κενά παίρνουν την τιμή από
 * Ρυθμίσεις → Χάρτης Ελλάδας. Το HTML το φτιάχνει η ίδια συνάρτηση με το shortcode.
 */

defined( 'ABSPATH' ) || exit;

class Ellada_Map_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ellada_map';
	}

	public function get_title() {
		return 'Χάρτης Ελλάδας';
	}

	public function get_icon() {
		return 'eicon-google-maps';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'χάρτης', 'ελλάδα', 'map', 'greece', 'ellada', 'τοποθεσίες' );
	}

	public function get_script_depends() {
		return array( 'ellada-map' );
	}

	protected function register_controls() {
		$defaults = ellada_map_settings();

		$this->start_controls_section(
			'section_map',
			array(
				'label' => 'Χάρτης',
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'Τίτλος',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '' !== $defaults['title'] ? $defaults['title'] : 'Εξερεύνηση',
				'description' => 'Κενό = ο τίτλος από τις ρυθμίσεις του χάρτη.',
				'label_block' => true,
			)
		);

		$this->add_control(
			'height',
			array(
				'label'       => 'Ύψος (px)',
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 300,
				'max'         => 1600,
				'step'        => 10,
				'placeholder' => (string) $defaults['height'],
				'description' => 'Κενό = το ύψος από τις ρυθμίσεις του χάρτη.',
			)
		);

		$this->add_control(
			'settlements',
			array(
				'label'   => 'Οικισμοί σε επίπεδο δήμου',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''      => 'Όπως στις ρυθμίσεις',
					'true'  => 'Εμφάνιση',
					'false' => 'Απόκρυψη',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => 'Χρώματα',
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'       => 'Χρώμα τονισμού',
				'type'        => \Elementor\Controls_Manager::COLOR,
				'alpha'       => false,
				'description' => 'Πινέζες, επιλεγμένες περιοχές, κουμπιά. Κενό = το χρώμα από τις ρυθμίσεις.',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		// ellada_map_render() κάνει όλο το escaping.
		echo ellada_map_render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'title'       => isset( $s['title'] ) ? $s['title'] : '',
				'height'      => isset( $s['height'] ) ? $s['height'] : '',
				'settlements' => isset( $s['settlements'] ) && '' !== $s['settlements'] ? $s['settlements'] : null,
				'accent'      => isset( $s['accent'] ) ? $s['accent'] : '',
			)
		);
	}
}
