<?php
/**
 * Plugin Name:       Ελλάδα στο Πιάτο — Διαδραστικός Χάρτης
 * Description:       Διαδραστικός χάρτης (Περιφέρειες → Νομοί → Δήμοι → Τοπικά Σημεία) με δεδομένα από το WordPress REST API και ACF Pro. Widget Elementor «Χάρτης Ελλάδας», shortcode [ellada_map] και ρυθμίσεις στο Ρυθμίσεις → Χάρτης Ελλάδας.
 * Version:           1.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Ελλάδα στο Πιάτο
 * License:           GPL-2.0-or-later
 * Text Domain:       ellada-map
 */

defined( 'ABSPATH' ) || exit;

define( 'ELLADA_MAP_VERSION', '1.1.0' );
define( 'ELLADA_MAP_FILE', __FILE__ );
define( 'ELLADA_MAP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELLADA_MAP_OPTION', 'ellada_map_settings' );

/**
 * Προεπιλεγμένες ρυθμίσεις (Ρυθμίσεις → Χάρτης Ελλάδας).
 * Ισχύουν όταν το shortcode ή το widget δεν ορίζει δική του τιμή.
 */
function ellada_map_defaults() {
	return array(
		'title'       => '',
		'height'      => 600,
		'settlements' => false,
		'accent'      => '',
	);
}

/** Οι αποθηκευμένες ρυθμίσεις, συμπληρωμένες με τις προεπιλογές. */
function ellada_map_settings() {
	$saved = get_option( ELLADA_MAP_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), ellada_map_defaults() );
}

/**
 * Επιτρεπτό χρώμα τονισμού: hex (#f97316) ή καθολικό χρώμα του Elementor
 * (var(--e-global-color-…)). Οτιδήποτε άλλο → '' (προεπιλεγμένο πορτοκαλί).
 */
function ellada_map_sanitize_accent( $value ) {
	// Το Elementor δίνει τα καθολικά χρώματα ως «var( --e-global-color-primary )».
	$value = preg_replace( '/\s+/', '', (string) $value );
	if ( '' === $value ) {
		return '';
	}
	$hex = sanitize_hex_color( $value );
	if ( $hex ) {
		return $hex;
	}
	if ( preg_match( '/^var\(--[A-Za-z0-9_-]+\)$/', $value ) ) {
		return $value;
	}
	return '';
}

/**
 * Καταχώριση του JavaScript bundle (φορτώνεται μόνο όταν χρησιμοποιείται ο χάρτης).
 * Η έκδοση βασίζεται στο filemtime ώστε κάθε νέο build να παρακάμπτει την cache (LiteSpeed/CDN).
 */
function ellada_map_register_assets() {
	$rel  = 'assets/js/ellada-map.js';
	$path = ELLADA_MAP_DIR . $rel;
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
 * Αποδίδει το HTML του χάρτη. Το χρησιμοποιούν το shortcode και το widget του Elementor.
 *
 * @param array $args title, height, settlements (true/false/null = ρύθμιση),
 *                    accent, locations, points. Κενές τιμές → ρυθμίσεις του plugin.
 */
function ellada_map_render( array $args = array() ) {
	$settings = ellada_map_settings();

	$title  = isset( $args['title'] ) && '' !== $args['title'] ? (string) $args['title'] : (string) $settings['title'];
	$height = isset( $args['height'] ) ? absint( $args['height'] ) : 0;
	if ( ! $height ) {
		$height = absint( $settings['height'] ) ? absint( $settings['height'] ) : 600;
	}
	$settlements = isset( $args['settlements'] ) && null !== $args['settlements'] && '' !== $args['settlements']
		? filter_var( $args['settlements'], FILTER_VALIDATE_BOOLEAN )
		: (bool) $settings['settlements'];
	$accent = ellada_map_sanitize_accent( isset( $args['accent'] ) && '' !== $args['accent'] ? $args['accent'] : $settings['accent'] );

	if ( ! wp_script_is( 'ellada-map', 'registered' ) ) {
		ellada_map_register_assets();
	}
	wp_enqueue_script( 'ellada-map' );

	$attributes = array(
		'data-ellada-map'         => '',
		'data-api'                => esc_url( rest_url() ),
		'data-geo'                => esc_url( plugins_url( 'assets/geo/', ELLADA_MAP_FILE ) ),
		'data-settlements'        => $settlements ? 'true' : 'false',
		'data-title'              => $title,
		'data-height'             => (string) $height,
		'data-accent'             => $accent,
		'data-locations-endpoint' => sanitize_title( isset( $args['locations'] ) ? $args['locations'] : '' ),
		'data-points-endpoint'    => sanitize_title( isset( $args['points'] ) ? $args['points'] : '' ),
	);

	$html = '';
	foreach ( $attributes as $name => $value ) {
		if ( '' === $value && 'data-ellada-map' !== $name ) {
			continue;
		}
		$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
	}

	// Ελάχιστο ύψος ώστε να μη «πηδάει» η σελίδα πριν φορτώσει ο χάρτης.
	return sprintf(
		'<div class="ellada-map"%s style="min-height:%dpx"><noscript>%s</noscript></div>',
		$html,
		$height,
		esc_html__( 'Ο διαδραστικός χάρτης χρειάζεται JavaScript.', 'ellada-map' )
	);
}

/**
 * Shortcode [ellada_map]
 *
 * Παράμετροι (όλες προαιρετικές· όσες λείπουν παίρνουν την τιμή από τις ρυθμίσεις):
 *   title="Εξερεύνηση"        Τίτλος πάνω αριστερά
 *   height="600"              Ελάχιστο ύψος σε px
 *   settlements="true|false"  Εμφάνιση οικισμών σε επίπεδο δήμου
 *   accent="#f97316"          Χρώμα τονισμού
 *   locations="locations"     REST base των τοποθεσιών (αν το άλλαξες στο ACF)
 *   points="local-points"     REST base των τοπικών σημείων
 */
function ellada_map_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'       => '',
			'height'      => '',
			'settlements' => '',
			'accent'      => '',
			'locations'   => '',
			'points'      => '',
		),
		$atts,
		'ellada_map'
	);
	return ellada_map_render( $atts );
}
add_shortcode( 'ellada_map', 'ellada_map_shortcode' );

/**
 * Elementor: στο preview του editor τα scripts δεν «τυπώνονται» από περιεχόμενο που αποδίδεται
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

/** Widget «Χάρτης Ελλάδας» για το Elementor. */
add_action(
	'elementor/widgets/register',
	function ( $widgets_manager ) {
		require_once ELLADA_MAP_DIR . 'includes/class-elementor-widget.php';
		$widgets_manager->register( new Ellada_Map_Elementor_Widget() );
	}
);

if ( is_admin() ) {
	require_once ELLADA_MAP_DIR . 'includes/admin-settings.php';
	require_once ELLADA_MAP_DIR . 'includes/admin-columns.php';
}
