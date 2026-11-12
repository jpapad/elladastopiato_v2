<?php
/**
 * Plugin Name:       Ελλάδα στο Πιάτο — Διαδραστικός Χάρτης
 * Description:       Διαδραστικός χάρτης (Περιφέρειες → Νομοί → Δήμοι → Τοπικά Σημεία) με δεδομένα από το WordPress REST API και ACF Pro. Χρήση: shortcode [ellada_map].
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Ελλάδα στο Πιάτο
 * License:           GPL-2.0-or-later
 * Text Domain:       ellada-map
 */

defined( 'ABSPATH' ) || exit;

define( 'ELLADA_MAP_VERSION', '1.0.0' );
define( 'ELLADA_MAP_FILE', __FILE__ );

/**
 * Καταχώριση του JavaScript bundle (φορτώνεται μόνο όταν χρησιμοποιείται το shortcode).
 * Η έκδοση βασίζεται στο filemtime ώστε κάθε νέο build να παρακάμπτει την cache (LiteSpeed/CDN).
 */
function ellada_map_register_assets() {
	$rel  = 'assets/js/ellada-map.js';
	$path = plugin_dir_path( ELLADA_MAP_FILE ) . $rel;
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : ELLADA_MAP_VERSION;

	wp_register_script(
		'ellada-map',
		plugins_url( $rel, ELLADA_MAP_FILE ),
		array(),
		$ver,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ellada_map_register_assets' );

/**
 * Shortcode [ellada_map]
 *
 * Παράμετροι (όλες προαιρετικές):
 *   title="Εξερεύνηση"        Τίτλος πάνω αριστερά
 *   height="600"              Ελάχιστο ύψος σε px
 *   settlements="false"       true = εμφάνιση οικισμών σε επίπεδο δήμου
 *   locations="locations"     REST base των τοποθεσιών (αν το άλλαξες στο ACF)
 *   points="local-points"     REST base των τοπικών σημείων
 */
function ellada_map_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'       => '',
			'height'      => '',
			'settlements' => 'false',
			'locations'   => '',
			'points'      => '',
		),
		$atts,
		'ellada_map'
	);

	if ( ! wp_script_is( 'ellada-map', 'registered' ) ) {
		ellada_map_register_assets();
	}
	wp_enqueue_script( 'ellada-map' );

	$attributes = array(
		'data-ellada-map'         => '',
		'data-api'                => esc_url( rest_url() ),
		'data-geo'                => esc_url( plugins_url( 'assets/geo/', ELLADA_MAP_FILE ) ),
		'data-settlements'        => filter_var( $atts['settlements'], FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
		'data-title'              => $atts['title'],
		'data-height'             => absint( $atts['height'] ) ? (string) absint( $atts['height'] ) : '',
		'data-locations-endpoint' => sanitize_title( $atts['locations'] ),
		'data-points-endpoint'    => sanitize_title( $atts['points'] ),
	);

	$html = '';
	foreach ( $attributes as $name => $value ) {
		if ( '' === $value && 'data-ellada-map' !== $name ) {
			continue;
		}
		$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
	}

	// Ελάχιστο ύψος ώστε να μη «πηδάει» η σελίδα πριν φορτώσει ο χάρτης.
	$min_height = absint( $atts['height'] ) ? absint( $atts['height'] ) : 600;

	return sprintf(
		'<div class="ellada-map"%s style="min-height:%dpx"><noscript>%s</noscript></div>',
		$html,
		$min_height,
		esc_html__( 'Ο διαδραστικός χάρτης χρειάζεται JavaScript.', 'ellada-map' )
	);
}
add_shortcode( 'ellada_map', 'ellada_map_shortcode' );

/**
 * Elementor: στο preview του editor τα scripts δεν «τυπώνονται» από shortcodes που αποδίδονται
 * με AJAX, οπότε φορτώνουμε το bundle πάντα μέσα στο preview. Το bundle κάνει μόνο του mount
 * σε κάθε νέο [data-ellada-map] (MutationObserver).
 */
add_action(
	'elementor/preview/enqueue_scripts',
	function () {
		ellada_map_register_assets();
		wp_enqueue_script( 'ellada-map' );
	}
);
