<?php
/**
 * Καρτέλα «Εργαλεία»: διάγνωση, cache, επαναφορά, εξαγωγή/εισαγωγή ρυθμίσεων.
 * Όλες οι ενέργειες απαιτούν manage_options και nonce.
 */

defined( 'ABSPATH' ) || exit;

/** Έλεγχοι λειτουργίας: [τίτλος, κατάσταση ok|warn|error, λεπτομέρεια]. */
function ellada_map_diagnostics() {
	$checks = array();
	$add    = function ( $label, $status, $detail = '' ) use ( &$checks ) {
		$checks[] = array( $label, $status, $detail );
	};

	// ACF
	if ( ! function_exists( 'acf_get_field_groups' ) ) {
		$add( 'ACF Pro', 'error', 'Δεν είναι ενεργό. Ο χάρτης χρειάζεται το ACF Pro για τα πεδία.' );
	} else {
		$add( 'ACF Pro', 'ok', 'Ενεργό' . ( defined( 'ACF_VERSION' ) ? ' (έκδοση ' . ACF_VERSION . ')' : '' ) );
		foreach ( array(
			'group_ellada_location' => 'Πεδία «Χάρτης — Τοποθεσία»',
			'group_ellada_point'    => 'Πεδία «Χάρτης — Τοπικό Σημείο»',
		) as $key => $label ) {
			$g = acf_get_field_group( $key );
			if ( ! $g ) {
				$add( $label, 'error', 'Λείπουν. Εισάγετε το acf-import.json του plugin από ACF → Εργαλεία.' );
			} elseif ( empty( $g['active'] ) ) {
				$add( $label, 'error', 'Η ομάδα πεδίων είναι ανενεργή.' );
			} elseif ( empty( $g['show_in_rest'] ) ) {
				$add( $label, 'error', 'Δεν είναι ενεργό το «Show in REST API» — ο χάρτης δεν βλέπει τα πεδία.' );
			} else {
				$add( $label, 'ok', 'Ενεργά, διαθέσιμα στο REST API' );
			}
		}
	}

	// Post types
	foreach ( array(
		'ellada_location' => array( 'Τύπος «Τοποθεσίες»', 'locations' ),
		'ellada_point'    => array( 'Τύπος «Τοπικά Σημεία»', 'local-points' ),
	) as $pt => $def ) {
		$o = get_post_type_object( $pt );
		if ( ! $o ) {
			$add( $def[0], 'error', 'Δεν υπάρχει.' );
		} elseif ( empty( $o->show_in_rest ) ) {
			$add( $def[0], 'error', 'Δεν είναι διαθέσιμος στο REST API.' );
		} elseif ( $o->rest_base !== $def[1] ) {
			$add( $def[0], 'warn', sprintf( 'REST base «%s» αντί για «%s» — ορίστε το locations/points στο shortcode.', $o->rest_base, $def[1] ) );
		} else {
			$add( $def[0], 'ok', '/wp-json/wp/v2/' . $o->rest_base );
		}
	}

	// Αρχεία
	foreach ( array( 'assets/js/ellada-map.js', 'assets/geo/regions.json', 'assets/geo/prefectures.json', 'assets/geo/municipalities.json', 'assets/geo/settlements.json' ) as $rel ) {
		$path = ELLADA_MAP_DIR . $rel;
		if ( is_readable( $path ) ) {
			$add( 'Αρχείο ' . basename( $rel ), 'ok', size_format( filesize( $path ) ) );
		} else {
			$add( 'Αρχείο ' . basename( $rel ), 'error', 'Λείπει — ξαναεγκαταστήστε το plugin.' );
		}
	}

	// Permalinks
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		$add( 'Μόνιμοι σύνδεσμοι', 'warn', 'Απλοί (?p=123). Ο χάρτης δουλεύει, αλλά προτείνονται «Όνομα άρθρου».' );
	} else {
		$add( 'Μόνιμοι σύνδεσμοι', 'ok', (string) get_option( 'permalink_structure' ) );
	}

	// REST από τον ίδιο τον server (όπως ένας επισκέπτης)
	$url  = add_query_arg( array( 'per_page' => 1, '_fields' => 'id' ), rest_url( 'wp/v2/locations' ) );
	$resp = wp_remote_get( $url, array( 'timeout' => 8 ) );
	if ( is_wp_error( $resp ) ) {
		$add( 'REST API για επισκέπτες', 'warn', 'Ο server δεν μπόρεσε να καλέσει τον εαυτό του (' . $resp->get_error_message() . '). Συνήθως δεν επηρεάζει τους επισκέπτες· ελέγξτε τη σελίδα του χάρτη.' );
	} else {
		$code = wp_remote_retrieve_response_code( $resp );
		if ( 200 === $code ) {
			$add( 'REST API για επισκέπτες', 'ok', 'Απαντά κανονικά (HTTP 200)' );
		} else {
			$add( 'REST API για επισκέπτες', 'error', 'HTTP ' . $code . ' — κάποιο plugin ασφαλείας ίσως μπλοκάρει το REST API για επισκέπτες.' );
		}
	}

	// Elementor
	if ( did_action( 'elementor/loaded' ) ) {
		$add( 'Elementor', 'ok', 'Ενεργό — διαθέσιμο το widget «Χάρτης Ελλάδας»' );
	} else {
		$add( 'Elementor', 'warn', 'Ανενεργό — χρησιμοποιήστε το shortcode [ellada_map].' );
	}

	$add( 'Εκδόσεις', 'ok', sprintf( 'Plugin %s · WordPress %s · PHP %s', ELLADA_MAP_VERSION, get_bloginfo( 'version' ), PHP_VERSION ) );

	return $checks;
}

function ellada_map_tool_form( $action, $button, $class = 'button', $extra = '' ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ellada-inline-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<?php wp_nonce_field( $action ); ?>
		<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- σταθερό HTML από τον κώδικα ?>
		<button type="submit" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $button ); ?></button>
	</form>
	<?php
}

function ellada_map_tab_tools() {
	$checks = ellada_map_diagnostics();
	$errors = count(
		array_filter(
			$checks,
			function ( $c ) {
				return 'error' === $c[1];
			}
		)
	);
	$icons  = array(
		'ok'    => '✓',
		'warn'  => '!',
		'error' => '✕',
	);
	?>
	<h2>Διάγνωση</h2>
	<p><?php echo $errors ? esc_html( sprintf( 'Βρέθηκαν %d προβλήματα που εμποδίζουν τον χάρτη.', $errors ) ) : '✓ Όλα όσα χρειάζεται ο χάρτης λειτουργούν.'; ?></p>
	<table class="widefat striped ellada-diag">
		<tbody>
		<?php foreach ( $checks as $c ) : ?>
			<tr class="is-<?php echo esc_attr( $c[1] ); ?>">
				<td class="icon"><?php echo esc_html( $icons[ $c[1] ] ); ?></td>
				<td><strong><?php echo esc_html( $c[0] ); ?></strong></td>
				<td><?php echo esc_html( $c[2] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<h2>Cache</h2>
	<p>Αν ένα νέο σημείο ή μια αλλαγή δεν φαίνεται αμέσως στον χάρτη, καθαρίστε την cache της σελίδας (LiteSpeed) και τα αποθηκευμένα στοιχεία της Επισκόπησης.</p>
	<?php ellada_map_tool_form( 'ellada_map_purge', 'Καθαρισμός cache' ); ?>

	<h2>Περιοχές συνταγών και σημείων</h2>
	<p>Κάθε συνταγή και κάθε τοπικό σημείο ξέρει αυτόματα σε ποια περιφέρεια, νομό και δήμο ανήκει, ώστε να εμφανίζεται στις σελίδες των περιοχών. Αν κάτι δεν εμφανίζεται εκεί που περιμένετε, ξαναϋπολογίστε το.</p>
	<?php ellada_map_tool_form( 'ellada_map_resync', 'Επανυπολογισμός περιοχών' ); ?>

	<h2>Εξαγωγή / εισαγωγή ρυθμίσεων</h2>
	<p>Χρήσιμο για αντίγραφο ασφαλείας ή για μεταφορά σε άλλο site. Αφορά μόνο τις ρυθμίσεις του χάρτη, όχι το περιεχόμενο.</p>
	<?php ellada_map_tool_form( 'ellada_map_export', 'Λήψη ρυθμίσεων (.json)' ); ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ellada-import">
		<input type="hidden" name="action" value="ellada_map_import">
		<?php wp_nonce_field( 'ellada_map_import' ); ?>
		<p><label for="ellada-import-json">Επικολλήστε το περιεχόμενο ενός αρχείου ρυθμίσεων:</label></p>
		<textarea id="ellada-import-json" name="settings_json" rows="5" class="large-text code" placeholder='{"plugin":"ellada-map","settings":{…}}'></textarea>
		<p><button type="submit" class="button">Εισαγωγή</button></p>
	</form>

	<h2>Επαναφορά</h2>
	<p>Όλες οι ρυθμίσεις (εμφάνιση, συμπεριφορά, κείμενα) γυρίζουν στις αρχικές τιμές. Το περιεχόμενο δεν αλλάζει.</p>
	<?php
	ellada_map_tool_form(
		'ellada_map_reset',
		'Επαναφορά προεπιλογών',
		'button button-link-delete',
		'<script>document.currentScript.parentNode.addEventListener("submit",function(e){if(!confirm("Επαναφορά όλων των ρυθμίσεων του χάρτη στις προεπιλογές;"))e.preventDefault();});</script>'
	);
}

/** Κοινός έλεγχος δικαιωμάτων/nonce για τις ενέργειες. */
function ellada_map_tool_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Δεν έχετε δικαίωμα για αυτή την ενέργεια.', 403 );
	}
	check_admin_referer( $action );
}

function ellada_map_tool_redirect( $msg ) {
	wp_safe_redirect( ellada_map_admin_url( 'tools', array( 'ellada-msg' => $msg ) ) );
	exit;
}

add_action(
	'admin_post_ellada_map_purge',
	function () {
		ellada_map_tool_guard( 'ellada_map_purge' );
		do_action( 'litespeed_purge_all' );
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'ellada_map' );
		}
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_ellada\\_map\\_%' OR option_name LIKE '\\_transient\\_timeout\\_ellada\\_map\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		ellada_map_tool_redirect( 'purged' );
	}
);

add_action(
	'admin_post_ellada_map_resync',
	function () {
		ellada_map_tool_guard( 'ellada_map_resync' );
		ellada_resync_all_areas();
		do_action( 'litespeed_purge_all' );
		ellada_map_tool_redirect( 'resynced' );
	}
);

add_action(
	'admin_post_ellada_map_reset',
	function () {
		ellada_map_tool_guard( 'ellada_map_reset' );
		update_option( ELLADA_MAP_OPTION, ellada_map_defaults() );
		ellada_map_tool_redirect( 'reset' );
	}
);

add_action(
	'admin_post_ellada_map_export',
	function () {
		ellada_map_tool_guard( 'ellada_map_export' );
		$payload = array(
			'plugin'   => 'ellada-map',
			'version'  => ELLADA_MAP_VERSION,
			'exported' => gmdate( 'c' ),
			'settings' => ellada_map_settings(),
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ellada-map-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}
);

add_action(
	'admin_post_ellada_map_import',
	function () {
		ellada_map_tool_guard( 'ellada_map_import' );
		$raw  = isset( $_POST['settings_json'] ) ? wp_unslash( $_POST['settings_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, ελέγχεται παρακάτω
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) || ( $data['plugin'] ?? '' ) !== 'ellada-map' || ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			ellada_map_tool_redirect( 'bad-import' );
		}
		// Περνάει από τον ίδιο έλεγχο με τη φόρμα, όλες οι ενότητες μαζί.
		$input             = $data['settings'];
		$input['_section'] = 'all';
		update_option( ELLADA_MAP_OPTION, ellada_map_sanitize_settings( $input ) );
		ellada_map_tool_redirect( 'imported' );
	}
);
