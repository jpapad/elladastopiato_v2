<?php
/**
 * Κεντρικό μενού v2 (λειτουργία «menu_v2»· προβολή χωρίς σύνδεση ?ellada_demo=menu):
 *   - «Αρχική» → σπιτάκι (στο κινητό σπιτάκι + «Αρχική»· πάντα με aria-label/title «Αρχική»)
 *   - «Οδηγός» → «Εξερευνήστε» (η σελίδα /odigos/ και ο τίτλος της δεν αλλάζουν)
 * Το μενού του WordPress δεν αλλάζει: μόνο φίλτρα στην εμφάνιση.
 */

defined( 'ABSPATH' ) || exit;

function ellada_menu_v2() {
	if ( is_admin() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'menu' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'menu_v2' );
}

/** Ποιο στοιχείο είναι: 'home', 'guide' ή ''. */
function ellada_menu_v2_kind( $item ) {
	$url  = untrailingslashit( (string) $item->url );
	$home = untrailingslashit( home_url( '/' ) );
	if ( $url === $home || ( 'custom' === $item->type && '/' === $item->url ) ) {
		return 'home';
	}
	if ( 'page' === $item->object && 'odigos' === get_post_field( 'post_name', (int) $item->object_id ) ) {
		return 'guide';
	}
	return '';
}

add_filter(
	'nav_menu_item_title',
	function ( $title, $item ) {
		if ( ! ellada_menu_v2() ) {
			return $title;
		}
		$k = ellada_menu_v2_kind( $item );
		if ( 'guide' === $k ) {
			return 'Εξερευνήστε';
		}
		if ( 'home' === $k ) {
			return '<svg class="ellada-home-ico" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
				. '<path d="M3.5 10.5 12 3.8l8.5 6.7"/><path d="M5.8 9v10.2h4.4v-5.4h3.6v5.4h4.4V9"/></svg><span class="ellada-home-txt">' . esc_html( $title ) . '</span>';
		}
		return $title;
	},
	10,
	2
);

add_filter(
	'nav_menu_link_attributes',
	function ( $atts, $item ) {
		if ( ellada_menu_v2() && 'home' === ellada_menu_v2_kind( $item ) ) {
			$atts['aria-label'] = 'Αρχική';
			$atts['title']      = 'Αρχική';
			$atts['class']      = trim( ( $atts['class'] ?? '' ) . ' ellada-home-link' );
		}
		return $atts;
	},
	10,
	2
);
