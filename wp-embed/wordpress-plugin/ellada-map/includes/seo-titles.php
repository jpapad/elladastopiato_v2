<?php
/**
 * Τίτλοι και περιγραφές για το Google (λειτουργία «seo_titles»· προεπισκόπηση ?ellada_preview=1).
 *
 *   Αρχική     «Ελλάδα στο Πιάτο – Συνταγές και γαστρονομικός οδηγός της Ελλάδας»
 *   Συνταγή    «Ντάκος: συνταγή από την Κρήτη – Ελλάδα στο Πιάτο» (χωρίς την περιοχή ή το όνομα του site όταν
 *              ο τίτλος ξεπερνά τους ~65 χαρακτήρες)
 *   Τόπος      «Κρήτη: γαστρονομικός οδηγός» / «Δράμα: συνταγές και τοπική κουζίνα» (χωρίς κείμενο οδηγού)
 *   Συλλογή    «…: συλλογή συνταγών» · Κατηγορίες/διατροφή «…: συνταγές»
 *   + περιγραφές για Χάρτη, Επικοινωνία, κατηγορίες και τόπους χωρίς κείμενο · canonical στις κατηγορίες
 */

defined( 'ABSPATH' ) || exit;

function ellada_seo_titles_on() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'seo_titles' );
}

/** Περιφέρεια σε αιτιατική με άρθρο: «από την Κρήτη». */
function ellada_region_from( $region ) {
	$map = array(
		'Αττική'                         => 'την Αττική',
		'Κεντρική Μακεδονία'             => 'την Κεντρική Μακεδονία',
		'Ανατολική Μακεδονία και Θράκη'  => 'την Ανατολική Μακεδονία και Θράκη',
		'Δυτική Μακεδονία'               => 'τη Δυτική Μακεδονία',
		'Ήπειρος'                        => 'την Ήπειρο',
		'Θεσσαλία'                       => 'τη Θεσσαλία',
		'Ιόνια Νησιά'                    => 'τα Ιόνια Νησιά',
		'Δυτική Ελλάδα'                  => 'τη Δυτική Ελλάδα',
		'Κεντρική Ελλάδα'                => 'την Κεντρική Ελλάδα',
		'Στερεά Ελλάδα'                  => 'τη Στερεά Ελλάδα',
		'Πελοπόννησος'                   => 'την Πελοπόννησο',
		'Βόρειο Αιγαίο'                  => 'το Βόρειο Αιγαίο',
		'Νότιο Αιγαίο'                   => 'το Νότιο Αιγαίο',
		'Κρήτη'                          => 'την Κρήτη',
	);
	return $map[ $region ] ?? '';
}

/** Ο τίτλος για το Google μιας σελίδας (ή '' για τον προεπιλεγμένο). */
function ellada_seo_title_for( $id = 0, $context = 'singular' ) {
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$fit  = function ( array $tries ) use ( $site ) {
		foreach ( $tries as $t ) {
			if ( mb_strlen( $t ) <= 65 ) {
				return $t;
			}
		}
		return end( $tries );
	};
	if ( 'front' === $context ) {
		return $site . ' – Συνταγές και γαστρονομικός οδηγός της Ελλάδας';
	}
	if ( 'term' === $context ) {
		$term = get_term( $id );
		return $term && ! is_wp_error( $term ) ? $fit( array( $term->name . ': συνταγές – ' . $site, $term->name . ': συνταγές' ) ) : '';
	}
	$name = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	switch ( get_post_type( $id ) ) {
		case 'ellada_recipe':
			$from = ellada_region_from( (string) get_post_meta( $id, ELLADA_AREA_KEYS['region'], true ) );
			return $fit( array_filter( array( $from ? "$name: συνταγή από $from – $site" : '', "$name: συνταγή – $site", "$name – $site", $name ) ) );
		case 'ellada_location':
			$what = '' !== trim( (string) get_post_meta( $id, 'guide_intro', true ) ) ? 'γαστρονομικός οδηγός' : 'συνταγές και τοπική κουζίνα';
			return $fit( array( "$name: $what – $site", "$name: $what", $name ) );
		case 'ellada_collection':
			return $fit( array( "$name: συλλογή συνταγών – $site", "$name – $site", $name ) );
		case 'page':
			$slug = get_post_field( 'post_name', $id );
			if ( 'xartis' === $slug ) {
				return 'Ο γαστρονομικός χάρτης της Ελλάδας – ' . $site;
			}
			if ( 'epikoinonia' === $slug ) {
				return 'Επικοινωνία: στείλτε μας μια συνταγή – ' . $site;
			}
	}
	return '';
}

/** Περιγραφές για σελίδες που δεν έχουν δική τους. */
function ellada_seo_desc_for( $id, $context = 'singular' ) {
	if ( 'term' === $context ) {
		$t = get_term( $id );
		return $t && ! is_wp_error( $t ) && ! $t->description ? sprintf( '%s: παραδοσιακές συνταγές από όλη την Ελλάδα, με υλικά, βήματα, χρόνους και τον τόπο της κάθε συνταγής.', $t->name ) : '';
	}
	$slug = get_post_field( 'post_name', $id );
	if ( 'xartis' === $slug ) {
		return 'Ο διαδραστικός γαστρονομικός χάρτης της Ελλάδας: διαλέξτε περιφέρεια, νομό ή δήμο και δείτε συνταγές, τοπικά προϊόντα και τον οδηγό του τόπου.';
	}
	if ( 'epikoinonia' === $slug ) {
		return 'Στείλτε μας μια συνταγή από τον τόπο σας, προτείνετε έναν παραγωγό ή μια ταβέρνα για τον χάρτη ή πείτε μας για ένα λάθος. Απαντάμε σε 2–3 εργάσιμες.';
	}
	if ( 'ellada_location' === get_post_type( $id ) && '' === trim( (string) get_post_meta( $id, 'guide_intro', true ) ) && '' === trim( (string) get_post_meta( $id, 'short_description', true ) ) ) {
		return sprintf( '%s: παραδοσιακές συνταγές, τοπικά προϊόντα και η κουζίνα του τόπου, στον γαστρονομικό χάρτη της Ελλάδας.', get_the_title( $id ) );
	}
	return '';
}

add_filter(
	'document_title_parts',
	function ( $parts ) {
		if ( ! ellada_seo_titles_on() ) {
			return $parts;
		}
		$t = '';
		if ( is_front_page() ) {
			$t = ellada_seo_title_for( 0, 'front' );
		} elseif ( is_singular() ) {
			$t = ellada_seo_title_for( get_queried_object_id() );
		} elseif ( is_tax() || is_category() ) {
			$t = ellada_seo_title_for( get_queried_object_id(), 'term' );
		}
		return $t ? array( 'title' => $t ) : $parts;
	},
	50
);

add_filter(
	'ellada_meta_data',
	function ( $data ) {
		if ( ! ellada_seo_titles_on() ) {
			return $data;
		}
		$d = '';
		if ( is_singular() ) {
			$d = ellada_seo_desc_for( get_queried_object_id() );
		} elseif ( is_tax() || is_category() ) {
			$d = ellada_seo_desc_for( get_queried_object_id(), 'term' );
		}
		if ( $d ) {
			$data['description'] = $d;
		}
		// Ποτέ shortcode στην περιγραφή.
		$data['description'] = trim( preg_replace( '/\s+/u', ' ', strip_shortcodes( (string) $data['description'] ) ) );
		if ( preg_match( '/^\[[a-z_]+[^\]]*\]$/', $data['description'] ) ) {
			$data['description'] = '';
		}
		$t = is_front_page() ? ellada_seo_title_for( 0, 'front' ) : ( is_singular() ? ellada_seo_title_for( get_queried_object_id() ) : '' );
		if ( $t ) {
			$data['title'] = $t;
		}
		return $data;
	}
);

/* Canonical στις σελίδες κατηγοριών/διατροφής (το WordPress το βάζει μόνο σε άρθρα/σελίδες). */
add_action(
	'wp_head',
	function () {
		if ( ! ellada_seo_titles_on() || ! ( is_tax() || is_category() ) || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$url = get_term_link( get_queried_object() );
		if ( is_wp_error( $url ) ) {
			return;
		}
		$paged = (int) get_query_var( 'paged' );
		if ( $paged > 1 ) {
			$url = trailingslashit( $url ) . 'page/' . $paged . '/';
		}
		printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );
	},
	4
);

/* Χάρτης: κύριος τίτλος H1 για το Google και τους αναγνώστες οθόνης (η σελίδα είναι μόνο ο χάρτης). */
add_filter(
	'the_content',
	function ( $content ) {
		if ( ellada_seo_titles_on() && is_page( 'xartis' ) && in_the_loop() && false === strpos( $content, '<h1' ) ) {
			return '<h1 class="screen-reader-text">Ο γαστρονομικός χάρτης της Ελλάδας</h1>' . $content;
		}
		return $content;
	},
	5
);
