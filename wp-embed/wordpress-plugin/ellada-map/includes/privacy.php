<?php
/**
 * Λιγότερα εξωτερικά αιτήματα για τους επισκέπτες.
 *
 * Το Hostinger Reach φορτώνει το script του (cdn-reach.hostinger.com) σε κάθε σελίδα, ακόμα κι όταν
 * δεν έχει στηθεί καμία φόρμα newsletter. Όσο δεν υπάρχει φόρμα, το script δεν φορτώνεται·
 * μόλις δημιουργηθεί η πρώτη φόρμα στο Reach, φορτώνεται ξανά αυτόματα.
 */

defined( 'ABSPATH' ) || exit;

function ellada_reach_has_forms() {
	$cached = get_transient( 'ellada_reach_has_forms' );
	if ( false !== $cached ) {
		return '1' === $cached;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'hostinger_reach_forms';
	$has   = false;
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$has = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ) > 0; // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	set_transient( 'ellada_reach_has_forms', $has ? '1' : '0', 10 * MINUTE_IN_SECONDS );
	return $has;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_admin() || ellada_reach_has_forms() ) {
			return;
		}
		wp_dequeue_script( 'hostinger-reach-embed' );
		wp_deregister_script( 'hostinger-reach-embed' );
	},
	100
);

/** Χωρίς το script δεν χρειάζεται ούτε το dns-prefetch προς τον server του. */
add_filter(
	'wp_resource_hints',
	function ( $urls, $relation ) {
		if ( 'dns-prefetch' !== $relation || ellada_reach_has_forms() ) {
			return $urls;
		}
		return array_values(
			array_filter(
				$urls,
				function ( $u ) {
					$href = is_array( $u ) ? ( $u['href'] ?? '' ) : (string) $u;
					return false === strpos( $href, 'cdn-reach.hostinger.com' );
				}
			)
		);
	},
	10,
	2
);
