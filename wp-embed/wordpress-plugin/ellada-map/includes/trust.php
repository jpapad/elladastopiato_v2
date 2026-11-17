<?php
/**
 * Αξιοπιστία περιεχομένου.
 *
 *   - Συνταγές: «Είδος» (Παραδοσιακή / Παραλλαγή / Σύγχρονη εκδοχή), «Πηγές», «Ελέγχθηκε από» + ημερομηνία
 *   - Τόποι: «Πηγές»
 *   - Στη σελίδα (λειτουργία «trust»· προεπισκόπηση ?ellada_preview=1): σήμα είδους δίπλα στη διαδρομή,
 *     κουτί «Σχετικά με τη συνταγή» / «Πηγές» και «Βρήκατε λάθος; Πείτε μας» → Επικοινωνία με έτοιμο θέμα
 *     και τη σελίδα (?thema=lathos&selida=<id>)
 */

defined( 'ABSPATH' ) || exit;

function ellada_trust() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'trust' );
}

/** είδος => [σήμα, εξήγηση] */
function ellada_recipe_kinds() {
	return array(
		'traditional' => array( 'Παραδοσιακή συνταγή', 'Όπως φτιάχνεται παραδοσιακά στον τόπο της.' ),
		'variation'   => array( 'Παραλλαγή', 'Βασισμένη στην παραδοσιακή συνταγή, με μικρές αλλαγές.' ),
		'modern'      => array( 'Σύγχρονη εκδοχή', 'Μια νέα ματιά σε μια παραδοσιακή γεύση.' ),
	);
}

/* ═════════════════════ Πεδία ACF (μία φορά) ═════════════════════ */

add_action(
	'acf/init',
	function () {
		if ( get_option( 'ellada_trust_fields_v1' ) || ! function_exists( 'acf_get_field_group' ) || ! is_admin() ) {
			return;
		}
		$add = function ( $field, $group_key, $after_key ) {
			$group = acf_get_field_group( $group_key );
			if ( ! $group || acf_get_field( $field['key'] ) ) {
				return;
			}
			$after               = acf_get_field( $after_key );
			$field['parent']     = $group['ID'];
			$field['menu_order'] = $after ? (int) $after['menu_order'] + 1 : 99;
			foreach ( acf_get_fields( $group ) as $f ) {
				if ( $after && (int) $f['menu_order'] > (int) $after['menu_order'] ) {
					$f['menu_order'] = (int) $f['menu_order'] + 1;
					acf_update_field( $f );
				}
			}
			acf_update_field( $field );
		};
		$base = array( 'instructions' => '', 'required' => 0, 'conditional_logic' => 0, 'wrapper' => array( 'width' => '', 'class' => '', 'id' => '' ), 'default_value' => '', 'placeholder' => '' );
		$kinds = array();
		foreach ( ellada_recipe_kinds() as $k => $v ) {
			$kinds[ $k ] = $v[0];
		}
		// Με αντίστροφη σειρά: το καθένα μπαίνει ακριβώς μετά τις «Παραλλαγές».
		$add( array_merge( $base, array( 'key' => 'field_ellada_recipe_verified_on', 'label' => 'Ημερομηνία ελέγχου', 'name' => 'verified_on', 'type' => 'date_picker', 'display_format' => 'd/m/Y', 'return_format' => 'Ymd', 'first_day' => 1, 'wrapper' => array( 'width' => '40', 'class' => '', 'id' => '' ) ) ), 'group_ellada_recipe', 'field_ellada_recipe_variations' );
		$add( array_merge( $base, array( 'key' => 'field_ellada_recipe_verified_by', 'label' => 'Ελέγχθηκε από', 'name' => 'verified_by', 'type' => 'text', 'instructions' => 'Ποιος επιβεβαίωσε τη συνταγή (με τη συγκατάθεσή του), π.χ. «Μαρία Κ., Ανώγεια». Προαιρετικό.', 'wrapper' => array( 'width' => '60', 'class' => '', 'id' => '' ) ) ), 'group_ellada_recipe', 'field_ellada_recipe_variations' );
		$add( array_merge( $base, array( 'key' => 'field_ellada_recipe_sources', 'label' => 'Πηγές', 'name' => 'sources', 'type' => 'textarea', 'rows' => 4, 'new_lines' => '', 'instructions' => 'Μία πηγή ανά γραμμή: «Τίτλος | https://…» ή απλό κείμενο (π.χ. «Μαρτυρία: Ελένη Π., Σφακιά, 2026»). Απαραίτητη για «Παραδοσιακή συνταγή».' ) ), 'group_ellada_recipe', 'field_ellada_recipe_variations' );
		$add( array_merge( $base, array( 'key' => 'field_ellada_recipe_kind', 'label' => 'Είδος συνταγής', 'name' => 'recipe_kind', 'type' => 'select', 'choices' => $kinds, 'allow_null' => 1, 'ui' => 0, 'return_format' => 'value', 'multiple' => 0, 'instructions' => '«Παραδοσιακή» μόνο όταν είμαστε σίγουροι ότι έτσι φτιάχνεται στον τόπο (με πηγή ή μαρτυρία).' ) ), 'group_ellada_recipe', 'field_ellada_recipe_variations' );
		$add( array_merge( $base, array( 'key' => 'field_ellada_loc_sources', 'label' => 'Πηγές', 'name' => 'sources', 'type' => 'textarea', 'rows' => 4, 'new_lines' => '', 'instructions' => 'Μία πηγή ανά γραμμή: «Τίτλος | https://…» ή απλό κείμενο. Εμφανίζονται στο τέλος της σελίδας.' ) ), 'group_ellada_location', 'field_ellada_loc_travel_tips' );
		update_option( 'ellada_trust_fields_v1', 1 );
	},
	30
);

/* ═════════════════════ Εμφάνιση ═════════════════════ */

function ellada_trust_sources_html( $id ) {
	$items = '';
	foreach ( preg_split( '/\r?\n/', (string) get_post_meta( $id, 'sources', true ) ) as $line ) {
		$line = trim( ltrim( trim( $line ), "-•·* \t" ) );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$url   = '';
		if ( isset( $parts[1] ) && preg_match( '#^https?://#i', $parts[1] ) ) {
			$url = $parts[1];
		} elseif ( preg_match( '#^https?://\S+$#i', $parts[0] ) ) {
			$url = $parts[0];
		}
		$label  = $parts[0];
		$items .= '<li>' . ( $url
			? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow">' . esc_html( $label ) . '</a>'
			: esc_html( isset( $parts[1] ) ? $parts[0] . ' — ' . $parts[1] : $label ) ) . '</li>';
	}
	return $items ? '<ul class="ellada-trust__sources">' . $items . '</ul>' : '';
}

function ellada_trust_report_url( $id ) {
	$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
	if ( ! $contact || 'publish' !== $contact->post_status ) {
		return '';
	}
	return add_query_arg( array( 'thema' => 'lathos', 'selida' => $id ), get_permalink( $contact ) ) . '#form-field-message';
}

function ellada_month_year( $ymd ) {
	if ( ! preg_match( '/^(\d{4})(\d{2})\d{2}$/', (string) $ymd, $m ) ) {
		return '';
	}
	$months = array( '', 'Ιανουάριος', 'Φεβρουάριος', 'Μάρτιος', 'Απρίλιος', 'Μάιος', 'Ιούνιος', 'Ιούλιος', 'Αύγουστος', 'Σεπτέμβριος', 'Οκτώβριος', 'Νοέμβριος', 'Δεκέμβριος' );
	return $months[ (int) $m[2] ] . ' ' . $m[1];
}

function ellada_recipe_trust_html( $id ) {
	$kinds = ellada_recipe_kinds();
	$kind  = (string) get_post_meta( $id, 'recipe_kind', true );
	$by    = trim( (string) get_post_meta( $id, 'verified_by', true ) );
	$when  = ellada_month_year( get_post_meta( $id, 'verified_on', true ) );
	$src   = ellada_trust_sources_html( $id );
	$rep   = ellada_trust_report_url( $id );
	$body  = '';
	if ( isset( $kinds[ $kind ] ) ) {
		$body .= sprintf( '<p class="ellada-trust__kind"><span class="ellada-kind is-%1$s">%2$s</span> %3$s</p>', esc_attr( $kind ), esc_html( $kinds[ $kind ][0] ), esc_html( $kinds[ $kind ][1] ) );
	}
	if ( $by ) {
		$body .= '<p class="ellada-trust__by"><span aria-hidden="true">✓</span> Ελέγχθηκε από <strong>' . esc_html( $by ) . '</strong>' . ( $when ? ' · ' . esc_html( $when ) : '' ) . '</p>';
	}
	if ( $src ) {
		$body .= '<h3 class="ellada-trust__h">Πηγές</h3>' . $src;
	}
	$report = $rep ? '<p class="ellada-trust__report">Βρήκατε κάποιο λάθος ή τη φτιάχνετε αλλιώς στον τόπο σας; <a href="' . esc_url( $rep ) . '">Πείτε μας</a></p>' : '';
	if ( ! $body && ! $report ) {
		return '';
	}
	return '<aside class="ellada-trust">' . ( $body ? '<h2 class="ellada-h">Σχετικά με τη συνταγή</h2>' . $body : '' ) . $report . '</aside>';
}

function ellada_location_trust_html( $id ) {
	$src = ellada_trust_sources_html( $id );
	$rep = ellada_trust_report_url( $id );
	$out = $src ? '<h2 class="ellada-h">Πηγές</h2>' . $src : '';
	$out .= $rep ? '<p class="ellada-trust__report">Βρήκατε κάποιο λάθος σε αυτή τη σελίδα ή ξέρετε κάτι που λείπει; <a href="' . esc_url( $rep ) . '">Πείτε μας</a></p>' : '';
	return $out ? '<aside class="ellada-trust is-place">' . $out . '</aside>' : '';
}

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( ! ellada_trust() ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		// Συνταγή: το κουτί μετά τα βήματα (και μετά το «Το έφτιαξα!»).
		if ( 'ellada_recipe_steps' === $tag && 'ellada_recipe' === get_post_type( $id ) ) {
			return $output . ellada_recipe_trust_html( $id );
		}
		// Σήμα είδους δίπλα στη διαδρομή.
		if ( 'ellada_recipe_crumbs' === $tag && 'ellada_recipe' === get_post_type( $id ) && '' !== $output ) {
			$kinds = ellada_recipe_kinds();
			$kind  = (string) get_post_meta( $id, 'recipe_kind', true );
			if ( isset( $kinds[ $kind ] ) ) {
				$output = '<div class="ellada-rcrumbs-row">' . $output . '<span class="ellada-kinds">' . sprintf( '<span class="ellada-kind is-%1$s" title="%3$s">%2$s</span>', esc_attr( $kind ), esc_html( $kinds[ $kind ][0] ), esc_attr( $kinds[ $kind ][1] ) ) . '</span></div>';
			}
			return $output;
		}
		// Τόπος με κείμενο οδηγού: πηγές + αναφορά λάθους στο τέλος.
		if ( 'ellada_guide' === $tag && 'ellada_location' === get_post_type( $id ) && '' !== trim( (string) get_post_meta( $id, 'guide_intro', true ) ) ) {
			return $output . ellada_location_trust_html( $id );
		}
		return $output;
	},
	30,
	3
);

/* Η αναφορά λάθους φτάνει στη φόρμα με τη σελίδα ήδη γραμμένη στο μήνυμα. */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sel = isset( $_GET['selida'] ) ? absint( $_GET['selida'] ) : 0;
		if ( 'ellada_contact_aside' !== $tag || ! $sel || 'publish' !== get_post_status( $sel ) || ! in_array( get_post_type( $sel ), array( 'ellada_recipe', 'ellada_location', 'ellada_point', 'ellada_collection', 'page', 'post' ), true ) ) {
			return $output;
		}
		$text = sprintf( "Σχετικά με: %s\n%s\n\n", html_entity_decode( get_the_title( $sel ), ENT_QUOTES, 'UTF-8' ), get_permalink( $sel ) );
		return $output . '<script>(function(){var t=' . wp_json_encode( $text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . ';'
			. 'function go(){var m=document.getElementById("form-field-message");if(m&&!m.value){m.value=t;}}'
			. 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",go);else go();})();</script>';
	},
	30,
	2
);

/* Recipe schema: όποιος έλεγξε τη συνταγή δεν μπαίνει (προσωπικά δεδομένα)· οι πηγές με link ως citation. */
add_filter(
	'ellada_recipe_schema',
	function ( $data, $id ) {
		if ( ! ellada_trust() ) {
			return $data;
		}
		$urls = array();
		foreach ( preg_split( '/\r?\n/', (string) get_post_meta( $id, 'sources', true ) ) as $line ) {
			if ( preg_match( '#https?://\S+#i', $line, $m ) ) {
				$urls[] = esc_url_raw( $m[0] );
			}
		}
		if ( $urls ) {
			$data['citation'] = count( $urls ) === 1 ? $urls[0] : $urls;
		}
		return $data;
	},
	30,
	2
);
