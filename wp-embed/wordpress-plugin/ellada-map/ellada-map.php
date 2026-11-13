<?php
/**
 * Plugin Name:       Ελλάδα στο Πιάτο — Διαδραστικός Χάρτης
 * Description:       Διαδραστικός χάρτης (Περιφέρειες → Νομοί → Δήμοι → Τοπικά Σημεία) με δεδομένα από το WordPress REST API και ACF Pro. Widget Elementor «Χάρτης Ελλάδας», shortcode [ellada_map] και ρυθμίσεις στο Ρυθμίσεις → Χάρτης Ελλάδας.
 * Version:           1.4.2
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Ελλάδα στο Πιάτο
 * License:           GPL-2.0-or-later
 * Text Domain:       ellada-map
 */

defined( 'ABSPATH' ) || exit;

define( 'ELLADA_MAP_VERSION', '1.4.2' );
define( 'ELLADA_MAP_FILE', __FILE__ );
define( 'ELLADA_MAP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELLADA_MAP_OPTION', 'ellada_map_settings' );

require_once ELLADA_MAP_DIR . 'includes/geo.php';
require_once ELLADA_MAP_DIR . 'includes/content.php';
require_once ELLADA_MAP_DIR . 'includes/blocks.php';

/* ═════════════════════ Ρυθμίσεις ═════════════════════ */

/** Τύποι τοπικών σημείων (value => label). Από το ACF αν υπάρχει, αλλιώς οι αρχικοί. */
function ellada_map_point_types() {
	$fallback = array(
		'product'    => 'Τοπικό προϊόν',
		'producer'   => 'Παραγωγός',
		'restaurant' => 'Εστιατόριο / Ταβέρνα',
		'market'     => 'Αγορά',
		'festival'   => 'Γιορτή / Φεστιβάλ',
		'sight'      => 'Αξιοθέατο',
		'other'      => 'Άλλο',
	);
	if ( function_exists( 'acf_get_field' ) ) {
		$field = acf_get_field( 'point_type' );
		if ( $field && ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
			return array_intersect_key( $field['choices'], $fallback ) + array_intersect_key( $fallback, $field['choices'] );
		}
	}
	return $fallback;
}

/** Προτεινόμενα χρώματα πινέζας ανά τύπο (ο χρήστης τα αλλάζει στις ρυθμίσεις). */
function ellada_map_suggested_type_colors() {
	return array(
		'product'    => '#f59e0b',
		'producer'   => '#22c55e',
		'restaurant' => '#ef4444',
		'market'     => '#3b82f6',
		'festival'   => '#a855f7',
		'sight'      => '#14b8a6',
		'other'      => '#9ca3af',
	);
}

/** Κείμενα του χάρτη που μπορούν να αλλάξουν (κλειδί => [ετικέτα, προεπιλογή]). */
function ellada_map_text_fields() {
	return array(
		'hintRegions'        => array( 'Οδηγία — επίπεδο Περιφερειών', 'Κάντε κλικ σε περιφέρεια για εξερεύνηση' ),
		'hintPrefectures'    => array( 'Οδηγία — επίπεδο Νομών', 'Κάντε κλικ σε νομό για εξερεύνηση' ),
		'hintMunicipalities' => array( 'Οδηγία — επίπεδο Δήμων', 'Κάντε κλικ σε δήμο ή σε τοπικό σημείο' ),
		'hintPoints'         => array( 'Οδηγία — μέσα σε Δήμο', 'Κάντε κλικ σε τοπικό σημείο για λεπτομέρειες' ),
		'guideButton'        => array( 'Κουμπί κάρτας περιοχής', 'Ο γαστρονομικός οδηγός' ),
		'pointsHeading'      => array( 'Τίτλος λίστας σημείων', 'Τοπικά σημεία' ),
	);
}

/**
 * Προεπιλεγμένες ρυθμίσεις (Ρυθμίσεις → Χάρτης Ελλάδας).
 * Ισχύουν όταν το shortcode ή το widget δεν ορίζει δική του τιμή.
 */
function ellada_map_defaults() {
	return array(
		// Εμφάνιση
		'title'         => '',
		'height'        => 600,
		'height_mobile' => 0,
		'radius'        => 40,
		'accent'        => '',
		'background'    => '',
		'type_colors'   => array(),
		// Συμπεριφορά
		'settlements'   => false,
		'legend'        => false,
		'point_types'   => array(),
		'start_region'  => '',
		'empty_areas'   => 'normal',
		// Κείμενα
		'texts'         => array(),
	);
}

/** Οι αποθηκευμένες ρυθμίσεις, συμπληρωμένες με τις προεπιλογές. */
function ellada_map_settings() {
	$saved = get_option( ELLADA_MAP_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), ellada_map_defaults() );
}

/**
 * Επιτρεπτό χρώμα: hex (#f97316) ή μεταβλητή CSS, π.χ. καθολικό χρώμα του Elementor
 * (var(--e-global-color-…)). Οτιδήποτε άλλο → ''.
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

/* ═════════════════════ Απόδοση ═════════════════════ */

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

/** Τιμή boolean από shortcode/widget: true/false/yes/no/1/0, ή null = «όπως στις ρυθμίσεις». */
function ellada_map_bool_arg( $args, $key ) {
	if ( ! isset( $args[ $key ] ) || null === $args[ $key ] || '' === $args[ $key ] ) {
		return null;
	}
	return filter_var( $args[ $key ], FILTER_VALIDATE_BOOLEAN );
}

/**
 * Αποδίδει το HTML του χάρτη. Το χρησιμοποιούν το shortcode και το widget του Elementor.
 *
 * @param array $args title, height, settlements, legend, accent, start_region, empty_areas,
 *                    types (λίστα με κόμμα), locations, points. Κενές τιμές → ρυθμίσεις.
 */
function ellada_map_render( array $args = array() ) {
	$s = ellada_map_settings();

	$pick = function ( $key, $setting_key = null ) use ( $args, $s ) {
		$setting_key = $setting_key ? $setting_key : $key;
		return isset( $args[ $key ] ) && '' !== $args[ $key ] && null !== $args[ $key ] ? $args[ $key ] : $s[ $setting_key ];
	};

	$title  = (string) $pick( 'title' );
	$height = absint( $pick( 'height' ) );
	$height = $height ? $height : 600;

	$settlements = ellada_map_bool_arg( $args, 'settlements' );
	$settlements = null === $settlements ? (bool) $s['settlements'] : $settlements;
	$legend      = ellada_map_bool_arg( $args, 'legend' );
	$legend      = null === $legend ? (bool) $s['legend'] : $legend;

	$accent     = ellada_map_sanitize_accent( $pick( 'accent' ) );
	$background = ellada_map_sanitize_accent( $s['background'] );

	$start_region = (string) $pick( 'start_region' );
	if ( '' !== $start_region && ! in_array( $start_region, ellada_map_region_names(), true ) ) {
		$start_region = '';
	}

	$empty_areas = (string) $pick( 'empty_areas' );
	if ( ! in_array( $empty_areas, array( 'normal', 'dim', 'disabled' ), true ) ) {
		$empty_areas = 'normal';
	}

	$valid_types = array_keys( ellada_map_point_types() );
	$types       = isset( $args['types'] ) && '' !== $args['types']
		? array_map( 'trim', explode( ',', (string) $args['types'] ) )
		: (array) $s['point_types'];
	$types       = array_values( array_intersect( $types, $valid_types ) );

	$type_colors = array();
	foreach ( (array) $s['type_colors'] as $type => $color ) {
		$color = ellada_map_sanitize_accent( $color );
		if ( $color && in_array( $type, $valid_types, true ) ) {
			$type_colors[ $type ] = $color;
		}
	}

	$texts = array();
	foreach ( array_keys( ellada_map_text_fields() ) as $key ) {
		if ( ! empty( $s['texts'][ $key ] ) ) {
			$texts[ $key ] = (string) $s['texts'][ $key ];
		}
	}

	if ( ! wp_script_is( 'ellada-map', 'registered' ) ) {
		ellada_map_register_assets();
	}
	wp_enqueue_script( 'ellada-map' );

	$attributes = array(
		'data-ellada-map'         => '',
		'data-api'                => esc_url( rest_url() ),
		'data-geo'                => esc_url( plugins_url( 'assets/geo/', ELLADA_MAP_FILE ) ),
		'data-settlements'        => $settlements ? 'true' : 'false',
		'data-legend'             => $legend ? 'true' : '',
		'data-title'              => $title,
		'data-height'             => (string) $height,
		'data-height-mobile'      => absint( $s['height_mobile'] ) ? (string) absint( $s['height_mobile'] ) : '',
		'data-radius'             => (string) absint( $s['radius'] ),
		'data-accent'             => $accent,
		'data-bg'                 => $background,
		'data-type-colors'        => $type_colors ? wp_json_encode( $type_colors ) : '',
		'data-types'              => implode( ',', $types ),
		'data-start-region'       => $start_region,
		'data-empty-areas'        => 'normal' !== $empty_areas ? $empty_areas : '',
		'data-texts'              => $texts ? wp_json_encode( $texts, JSON_UNESCAPED_UNICODE ) : '',
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
 *   title="Εξερεύνηση"           Τίτλος πάνω αριστερά
 *   height="600"                 Ελάχιστο ύψος σε px
 *   settlements="true|false"     Οικισμοί σε επίπεδο δήμου
 *   legend="true|false"          Υπόμνημα/φίλτρο τύπων σημείων
 *   accent="#f97316"             Χρώμα τονισμού
 *   start_region="Κρήτη"         Ο χάρτης ανοίγει σε αυτή την περιφέρεια
 *   empty_areas="dim"            normal | dim | disabled για περιοχές χωρίς περιεχόμενο
 *   types="restaurant,market"    Μόνο αυτοί οι τύποι σημείων
 *   locations / points           REST base (αν τα άλλαξες στο ACF)
 */
function ellada_map_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'        => '',
			'height'       => '',
			'settlements'  => '',
			'legend'       => '',
			'accent'       => '',
			'start_region' => '',
			'empty_areas'  => '',
			'types'        => '',
			'locations'    => '',
			'points'       => '',
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
	require_once ELLADA_MAP_DIR . 'includes/admin-tools.php';
	require_once ELLADA_MAP_DIR . 'includes/admin-columns.php';
	require_once ELLADA_MAP_DIR . 'includes/admin-point-picker.php';
}
