<?php
/**
 * Βασικό SEO του οδηγού, χωρίς ξεχωριστό plugin:
 *   - λατινικές διευθύνσεις (slugs) από ελληνικούς τίτλους, για ό,τι αποθηκεύεται από εδώ και πέρα
 *   - meta description, Open Graph και Twitter Card (προεπισκόπηση σε Facebook, Viber, WhatsApp)
 *   - sitemap χωρίς τη λίστα χρηστών (δεν δείχνει ονόματα διαχειριστών)
 * Αν εγκατασταθεί κάποιο plugin SEO (Yoast, Rank Math, AIOSEO, SEOPress), τα meta tags σταματούν
 * εδώ για να μη διπλασιαστούν.
 */

defined( 'ABSPATH' ) || exit;

/* ═════════════════════ Λατινικές διευθύνσεις ═════════════════════ */

/** Μεταγραφή ελληνικών σε λατινικούς χαρακτήρες (ίδιος κανόνας με το Next.js site). */
function ellada_greeklish( $text ) {
	static $map = null;
	if ( null === $map ) {
		$pairs = array(
			'ου' => 'ou', 'ού' => 'ou', 'αι' => 'ai', 'αί' => 'ai', 'ει' => 'ei', 'εί' => 'ei', 'οι' => 'oi', 'οί' => 'oi',
			'μπ' => 'b', 'ντ' => 'nt', 'γκ' => 'gk', 'γγ' => 'ng', 'τσ' => 'ts', 'τζ' => 'tz',
			'α' => 'a', 'ά' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'έ' => 'e', 'ζ' => 'z',
			'η' => 'i', 'ή' => 'i', 'θ' => 'th', 'ι' => 'i', 'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i', 'κ' => 'k',
			'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'ό' => 'o', 'π' => 'p', 'ρ' => 'r',
			'σ' => 's', 'ς' => 's', 'τ' => 't', 'υ' => 'y', 'ύ' => 'y', 'ϋ' => 'y', 'ΰ' => 'y', 'φ' => 'f',
			'χ' => 'ch', 'ψ' => 'ps', 'ω' => 'o', 'ώ' => 'o',
		);
		// Τα δίψηφα πρώτα, ώστε το «ου» να γίνει «ou» και όχι «oy».
		uksort(
			$pairs,
			function ( $a, $b ) {
				return mb_strlen( $b ) - mb_strlen( $a );
			}
		);
		$map = $pairs;
	}
	return strtr( mb_strtolower( (string) $text, 'UTF-8' ), $map );
}

/**
 * Όταν το WordPress φτιάχνει slug από τίτλο (κατά την αποθήκευση), μετατρέπει πρώτα τα ελληνικά.
 * Δεν αλλάζει διευθύνσεις που υπάρχουν ήδη· μόνο ό,τι δημιουργείται ή αλλάζει από εδώ και πέρα.
 */
add_filter(
	'sanitize_title',
	function ( $title, $raw_title = '', $context = 'display' ) {
		if ( 'save' !== $context || ! preg_match( '/[\x{0370}-\x{03FF}\x{1F00}-\x{1FFF}]/u', (string) $raw_title ) ) {
			return $title;
		}
		return sanitize_title_with_dashes( remove_accents( ellada_greeklish( $raw_title ) ), '', 'save' );
	},
	9,
	3
);

/* ═════════════════════ Sitemap ═════════════════════ */

add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

/* ═════════════════════ Meta tags ═════════════════════ */

function ellada_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/** Κείμενο για meta description: καθαρό, μία γραμμή, έως ~160 χαρακτήρες. */
function ellada_meta_text( $text, $limit = 160 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $limit - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	return ( $sp ? mb_substr( $cut, 0, $sp ) : $cut ) . '…';
}

/** Τίτλος, περιγραφή, εικόνα και τύπος της τρέχουσας σελίδας. */
function ellada_meta_data() {
	$site = get_bloginfo( 'name' );
	$data = array(
		'title'       => wp_get_document_title(),
		'description' => '',
		'image'       => '',
		'type'        => 'website',
		'url'         => '',
	);

	if ( is_front_page() ) {
		$data['description'] = 'Ανοίξτε τον χάρτη, διαλέξτε μια περιφέρεια και βρείτε τις συνταγές, τα προϊόντα και τους ανθρώπους που κρατούν ζωντανή την τοπική κουζίνα.';
		$data['url']         = home_url( '/' );
	} elseif ( is_singular() ) {
		$id  = get_queried_object_id();
		$pt  = get_post_type( $id );
		$txt = '';
		if ( in_array( $pt, array( 'ellada_recipe', 'ellada_location', 'ellada_collection', 'ellada_point' ), true ) ) {
			$txt = get_post_meta( $id, 'short_description', true );
		}
		if ( ! $txt ) {
			$txt = has_excerpt( $id ) ? get_the_excerpt( $id ) : get_post_field( 'post_content', $id );
		}
		if ( 'ellada_location' === $pt && ! trim( (string) $txt ) ) {
			$txt = sprintf( '%s: συνταγές, τοπικά προϊόντα και γαστρονομικός οδηγός.', get_the_title( $id ) );
		}
		$data['description'] = $txt;
		$data['url']         = get_permalink( $id );
		$data['type']        = 'ellada_recipe' === $pt ? 'article' : 'website';
		$img                 = get_the_post_thumbnail_url( $id, 'large' );
		if ( ! $img && in_array( $pt, array( 'ellada_location', 'ellada_point' ), true ) ) {
			$cover = (int) get_post_meta( $id, 'cover_image', true );
			$img   = $cover ? wp_get_attachment_image_url( $cover, 'large' ) : '';
		}
		$data['image'] = $img ? $img : '';
	} elseif ( is_post_type_archive( 'ellada_recipe' ) ) {
		$data['description'] = 'Παραδοσιακές συνταγές από όλη την Ελλάδα, οργανωμένες ανά περιοχή και κατηγορία.';
		$data['url']         = get_post_type_archive_link( 'ellada_recipe' );
	} elseif ( is_post_type_archive( 'ellada_collection' ) ) {
		$data['description'] = 'Επιμελημένες λίστες συνταγών για γιορτές, εποχές και τη νηστεία.';
		$data['url']         = get_post_type_archive_link( 'ellada_collection' );
	} elseif ( is_tax() || is_category() ) {
		$term                = get_queried_object();
		$data['description'] = $term->description ? $term->description : sprintf( '%s: συνταγές από το %s.', $term->name, $site );
		$data['url']         = get_term_link( $term );
	}

	$data['description'] = ellada_meta_text( $data['description'] );
	if ( ! $data['image'] && get_option( 'site_icon' ) ) {
		$data['image'] = get_site_icon_url( 512 );
	}
	return $data;
}

add_action(
	'wp_head',
	function () {
		if ( ellada_seo_plugin_active() || is_404() || is_search() ) {
			return;
		}
		$d    = ellada_meta_data();
		$tags = array();
		if ( $d['description'] ) {
			$tags[] = sprintf( '<meta name="description" content="%s">', esc_attr( $d['description'] ) );
		}
		$tags[] = '<meta property="og:locale" content="el_GR">';
		$tags[] = sprintf( '<meta property="og:site_name" content="%s">', esc_attr( get_bloginfo( 'name' ) ) );
		$tags[] = sprintf( '<meta property="og:type" content="%s">', esc_attr( $d['type'] ) );
		$tags[] = sprintf( '<meta property="og:title" content="%s">', esc_attr( $d['title'] ) );
		if ( $d['description'] ) {
			$tags[] = sprintf( '<meta property="og:description" content="%s">', esc_attr( $d['description'] ) );
		}
		if ( $d['url'] ) {
			$tags[] = sprintf( '<meta property="og:url" content="%s">', esc_url( $d['url'] ) );
		}
		if ( $d['image'] ) {
			$tags[] = sprintf( '<meta property="og:image" content="%s">', esc_url( $d['image'] ) );
		}
		$tags[] = sprintf( '<meta name="twitter:card" content="%s">', $d['image'] ? 'summary_large_image' : 'summary' );
		echo "\n" . implode( "\n", $tags ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- κάθε τιμή είναι ήδη escaped
	},
	5
);
