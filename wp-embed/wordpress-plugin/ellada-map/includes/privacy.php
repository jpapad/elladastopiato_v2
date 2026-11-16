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

/* ═════════════════════ Μηνύματα φόρμας επικοινωνίας (Elementor) ═════════════════════
 *
 * Όπως γράφει η σελίδα «Απόρρητο & cookies»:
 *  - κρατάμε μόνο όνομα, email και μήνυμα: το Elementor αποθηκεύει και IP / user agent, τα σβήνουμε
 *    αμέσως μετά την αποστολή (και ξανά, για σιγουριά, στον ημερήσιο έλεγχο)·
 *  - τα μηνύματα διαγράφονται 12 μήνες μετά την τελευταία ενημέρωσή τους.
 */

const ELLADA_SUBMISSIONS_RETENTION_MONTHS = 12;
const ELLADA_SUBMISSIONS_CRON             = 'ellada_submissions_cleanup';

function ellada_submissions_table() {
	global $wpdb;
	$table = $wpdb->prefix . 'e_submissions';
	return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ? $table : ''; // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/** Σβήνει IP και user agent από όλα τα αποθηκευμένα μηνύματα. */
function ellada_submissions_strip_tracking() {
	global $wpdb;
	$table = ellada_submissions_table();
	if ( ! $table ) {
		return 0;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return (int) $wpdb->query( "UPDATE `{$table}` SET user_ip = '', user_agent = '' WHERE user_ip <> '' OR user_agent <> ''" );
}

/** Διαγράφει μηνύματα παλαιότερα από 12 μήνες (με τις τιμές και το log τους). */
function ellada_submissions_delete_old() {
	global $wpdb;
	$table = ellada_submissions_table();
	if ( ! $table ) {
		return 0;
	}
	$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . ELLADA_SUBMISSIONS_RETENTION_MONTHS . ' months' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE updated_at_gmt < %s LIMIT 500", $cutoff ) );
	if ( ! $ids ) {
		return 0;
	}
	$query = 'ElementorPro\Modules\Forms\Submissions\Database\Query';
	foreach ( $ids as $id ) {
		if ( class_exists( $query ) && method_exists( $query, 'get_instance' ) ) {
			$query::get_instance()->delete_submission( (int) $id );
			continue;
		}
		// Χωρίς το Elementor Pro: απευθείας από τους πίνακες.
		$wpdb->delete( $wpdb->prefix . 'e_submissions_values', array( 'submission_id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'e_submissions_actions_log', array( 'submission_id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $table, array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
	return count( $ids );
}

/** Μετά την αποστολή φόρμας: στο τέλος του αιτήματος (αφού γραφτεί το μήνυμα) σβήνονται IP / user agent. */
add_action(
	'elementor_pro/forms/new_record',
	function () {
		add_action( 'shutdown', 'ellada_submissions_strip_tracking' );
	}
);

add_action(
	ELLADA_SUBMISSIONS_CRON,
	function () {
		ellada_submissions_strip_tracking();
		ellada_submissions_delete_old();
	}
);

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( ELLADA_SUBMISSIONS_CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', ELLADA_SUBMISSIONS_CRON );
		}
	}
);

register_deactivation_hook(
	ELLADA_MAP_FILE,
	function () {
		wp_clear_scheduled_hook( ELLADA_SUBMISSIONS_CRON );
	}
);
