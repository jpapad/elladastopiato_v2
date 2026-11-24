<?php
/**
 * ♿ Πληροφορίες προσβασιμότητας στα τοπικά σημεία (ellada_point).
 *
 *   Πλαϊνό κουτί ACF «Προσβασιμότητα» στο admin: επιλογές (χωρίς σκαλιά/ράμπα, τουαλέτα, στάθμευση, πρόσβαση στη θάλασσα,
 *   σκύλοι-οδηγοί, μενού/πληροφορίες σε μεγάλα γράμματα), σημείωση και «ελέγχθηκε στις».
 *   Εμφάνιση: σήματα ♿ στην κάρτα του σημείου και ενότητα στη σελίδα του — ΜΟΝΟ όταν έχει συμπληρωθεί κάτι.
 *   Κανόνας: μόνο επιβεβαιωμένες πληροφορίες (από επίσκεψη ή από την ίδια την επιχείρηση).
 */

defined( 'ABSPATH' ) || exit;

function ellada_access_choices() {
	return array(
		'step_free' => array( 'Είσοδος χωρίς σκαλιά ή με ράμπα', 'Χωρίς σκαλιά' ),
		'wc'        => array( 'Προσβάσιμη τουαλέτα', 'Τουαλέτα ΑμεΑ' ),
		'parking'   => array( 'Θέση στάθμευσης ΑμεΑ κοντά', 'Στάθμευση ΑμεΑ' ),
		'sea'       => array( 'Αυτόνομη πρόσβαση στη θάλασσα (π.χ. Seatrac)', 'Πρόσβαση στη θάλασσα' ),
		'dogs'      => array( 'Επιτρέπονται σκύλοι-οδηγοί', 'Σκύλοι-οδηγοί' ),
		'large'     => array( 'Μενού ή πληροφορίες σε μεγάλα γράμματα', 'Μεγάλα γράμματα' ),
	);
}

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		$choices = array();
		foreach ( ellada_access_choices() as $k => $c ) {
			$choices[ $k ] = $c[0];
		}
		acf_add_local_field_group(
			array(
				'key'        => 'group_ellada_access',
				'title'      => '♿ Προσβασιμότητα',
				'position'   => 'side',
				'menu_order' => 6,
				'location'   => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'ellada_point' ) ) ),
				'fields'     => array(
					array( 'key' => 'field_ellada_access', 'label' => 'Τι ισχύει', 'name' => 'access', 'type' => 'checkbox', 'choices' => $choices, 'layout' => 'vertical', 'return_format' => 'value', 'instructions' => 'Μόνο ό,τι έχει επιβεβαιωθεί (από επίσκεψη ή από την ίδια την επιχείρηση).' ),
					array( 'key' => 'field_ellada_access_note', 'label' => 'Σημείωση', 'name' => 'access_note', 'type' => 'textarea', 'rows' => 3, 'new_lines' => '', 'placeholder' => 'π.χ. Ένα σκαλί στην είσοδο· το προσωπικό βοηθά πρόθυμα.' ),
					array( 'key' => 'field_ellada_access_checked', 'label' => 'Ελέγχθηκε στις', 'name' => 'access_checked', 'type' => 'date_picker', 'display_format' => 'd/m/Y', 'return_format' => 'Y-m-d' ),
				),
			)
		);
	}
);

/** Σήματα ♿ (ή κενό αν δεν έχει συμπληρωθεί τίποτα). */
function ellada_access_badges( $id, $long = false ) {
	$on = array_filter( (array) get_post_meta( $id, 'access', true ) );
	if ( ! $on ) {
		return '';
	}
	$c    = ellada_access_choices();
	$html = '';
	foreach ( $on as $k ) {
		if ( isset( $c[ $k ] ) ) {
			$html .= '<li>' . esc_html( $c[ $k ][ $long ? 0 : 1 ] ) . '</li>';
		}
	}
	return $html ? '<ul class="ellada-access' . ( $long ? ' is-long' : '' ) . '" aria-label="Προσβασιμότητα"><li class="ellada-access__ico" aria-hidden="true">♿</li>' . $html . '</ul>' : '';
}

/* Στη σελίδα του σημείου: ενότητα «Προσβασιμότητα» μετά το κείμενο. */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_singular( 'ellada_point' ) || ! in_the_loop() ) {
			return $content;
		}
		$id     = get_the_ID();
		$badges = ellada_access_badges( $id, true );
		$note   = trim( (string) get_post_meta( $id, 'access_note', true ) );
		if ( ! $badges && ! $note ) {
			return $content;
		}
		$date = (string) get_post_meta( $id, 'access_checked', true );
		$when = $date ? ' Ελέγχθηκε: ' . wp_date( 'F Y', strtotime( $date ) ) . '.' : '';
		return $content . '<section class="ellada-access-box"><h2 class="ellada-h">Προσβασιμότητα</h2>' . $badges
			. ( $note ? '<p>' . esc_html( $note ) . '</p>' : '' )
			. '<p class="ellada-muted">Οι πληροφορίες είναι ενδεικτικές.' . esc_html( $when ) . ' Αν κάτι έχει αλλάξει, <a href="' . esc_url( add_query_arg( array( 'thema' => 'prosvasimotita', 'selida' => $id ), home_url( '/epikoinonia/' ) ) ) . '">πείτε μας</a>.</p></section>';
	},
	20
);
