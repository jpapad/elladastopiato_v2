<?php
/**
 * Μικρές ρυθμίσεις ασφάλειας, αόρατες για τους επισκέπτες:
 *   - το όνομα χρήστη του διαχειριστή δεν φαίνεται δημόσια (REST /wp/v2/users, σελίδες συγγραφέα, ?author=, oEmbed)
 *   - το παλιό XML-RPC είναι κλειστό (στόχος για επιθέσεις με κωδικούς· το site δεν το χρειάζεται)
 *   - βασικές κεφαλίδες ασφαλείας στις απαντήσεις του site
 */

defined( 'ABSPATH' ) || exit;

/* Λίστα χρηστών στο REST: μόνο για συνδεδεμένους (το Elementor και ο editor τη χρειάζονται). */
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
);

/* Σελίδες συγγραφέα και ?author=1: ο οδηγός δεν έχει άρθρα ανά συγγραφέα, πάνε στην αρχική. */
add_action(
	'template_redirect',
	function () {
		if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);

/* Το oEmbed (προεπισκόπηση συνδέσμων σε άλλα sites) να μη δείχνει τον λογαριασμό. */
add_filter(
	'oembed_response_data',
	function ( $data ) {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}
);

/* XML-RPC κλειστό. */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
add_filter(
	'xmlrpc_methods',
	function () {
		return array();
	}
);

/* Κεφαλίδες ασφαλείας (μόνο στο δημόσιο site, όχι στο wp-admin). */
add_action(
	'send_headers',
	function () {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Permissions-Policy: camera=(), microphone=(), payment=()' );
	}
);
