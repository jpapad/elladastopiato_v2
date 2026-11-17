<?php
/**
 * Σελίδα τόπου v3 (λειτουργία «guide_v3»· προεπισκόπηση ?ellada_preview=1).
 *
 *   - hero: η περιφέρεια σε μεγέθυνση, χωρισμένη στους νομούς της (κάθε νομός link)
 *   - μενού πλοήγησης: Με μια ματιά · Τι να φάτε · Πότε & πώς · Νομοί/Δήμοι · Συνταγές · Χάρτης
 *   - «Τι να φάτε» (ACF dishes: «Πιάτο | περιγραφή» ανά γραμμή — link σε συνταγή αν υπάρχει) και
 *     «Τοπικά προϊόντα» (local_products: «Όνομα | σημείωση»)
 *   - «Πότε & πώς»: καλύτερη εποχή, γαστρονομικό ημερολόγιο (ACF food_calendar: «Εποχή | τι τρώγεται»),
 *     πώς θα έρθετε, συμβουλές (travel_tips: μία ανά γραμμή)
 *   - «Οι νομοί» σε κάρτες με μικρό χάρτη· συνταγές με «Όλες οι συνταγές» + «Στείλτε μας»
 *   - ο χάρτης ανοίγει χωρίς την κάρτα της περιοχής · TouristDestination για το Google
 *
 * Προεπισκόπηση κειμένων: meta `_preview_<πεδίο>` (μόνο για editors με ?ellada_preview=1) υπερισχύει του πεδίου.
 */

defined( 'ABSPATH' ) || exit;

function ellada_guide_v3() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'guide_v3' );
}

function ellada_guide_preview_mode() {
	return isset( $_GET['ellada_preview'] ) && is_user_logged_in() && current_user_can( 'edit_posts' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/** Τιμή πεδίου του οδηγού (στην προεπισκόπηση: το πρόχειρο `_preview_<key>`, αν υπάρχει). */
function ellada_guide_val( $id, $key ) {
	if ( ellada_guide_preview_mode() ) {
		$draft = get_post_meta( $id, '_preview_' . $key, true );
		if ( '' !== $draft && null !== $draft ) {
			return $draft;
		}
	}
	return get_post_meta( $id, $key, true );
}

/** Γραμμές «Α | Β» → [[Α, Β], …]. */
function ellada_guide_pairs( $text ) {
	$out = array();
	foreach ( preg_split( '/\r?\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$out[] = array( $parts[0], $parts[1] ?? '' );
	}
	return $out;
}

/* ═════════════════════ Χάρτης περιφέρειας σε μεγέθυνση ═════════════════════ */

/** Περιφέρεια με τους νομούς της· ο $highlight νομός πορτοκαλί (κενό = όλοι). */
function ellada_region_zoom_svg( $region, $highlight = '' ) {
	$pm    = function_exists( 'ellada_prefectures_mini' ) ? ellada_prefectures_mini() : array();
	$prefs = ellada_guide_region_prefs()[ $region ] ?? array();
	if ( ! $prefs || ! $pm ) {
		return '';
	}
	$minx = $miny = INF;
	$maxx = $maxy = -INF;
	$body = '';
	foreach ( $prefs as $p ) {
		$d = $pm[ $p ] ?? '';
		if ( ! $d ) {
			continue;
		}
		preg_match_all( '/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $d, $m );
		foreach ( $m[1] as $i => $x ) {
			$x    = (float) $x;
			$y    = (float) $m[2][ $i ];
			$minx = min( $minx, $x );
			$maxx = max( $maxx, $x );
			$miny = min( $miny, $y );
			$maxy = max( $maxy, $y );
		}
		$pid   = ellada_guide_page( 'prefecture', $p );
		$on    = ! $highlight || $highlight === $p;
		$title = ellada_guide_prefecture_title( $p );
		$path  = '<path d="' . esc_attr( $d ) . '"/>';
		$body .= $pid
			? sprintf( '<a href="%s" class="%s" aria-label="%s"><title>%s</title>%s</a>', esc_url( get_permalink( $pid ) ), $on ? 'is-on' : 'is-off', esc_attr( $title ), esc_html( $title ), $path )
			: '<g class="' . ( $on ? 'is-on' : 'is-off' ) . '">' . $path . '</g>';
	}
	if ( INF === $minx ) {
		return '';
	}
	$w   = $maxx - $minx;
	$h   = $maxy - $miny;
	$pad = max( $w, $h ) * 0.06;
	return sprintf(
		'<svg class="ellada-gzoom" viewBox="%s" role="group" aria-label="%s">%s</svg>',
		esc_attr( sprintf( '%.2f %.2f %.2f %.2f', $minx - $pad, $miny - $pad, $w + 2 * $pad, $h + 2 * $pad ) ),
		esc_attr( 'Οι νομοί: ' . $region ),
		$body
	);
}

add_filter(
	'ellada_guide_hero_map',
	function ( $map, $c ) {
		if ( ! ellada_guide_v3() || ! $c['region'] ) {
			return $map;
		}
		$zoom = ellada_region_zoom_svg( $c['region'], 'region' === $c['level'] ? '' : $c['pref'] );
		return $zoom ? $zoom : $map;
	},
	10,
	2
);

/* ═════════════════════ Τι να φάτε · Πότε & πώς ═════════════════════ */

/** Συνταγή του site με τίτλο που ταιριάζει στο πιάτο (χωρίς τόνους), ή 0. */
function ellada_guide_dish_recipe( $name ) {
	static $titles = null;
	if ( null === $titles ) {
		$titles = array();
		foreach ( get_posts( array( 'post_type' => 'ellada_recipe', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) as $rid ) {
			$titles[ $rid ] = ellada_search_norm( get_the_title( $rid ) );
		}
	}
	$n = ellada_search_norm( $name );
	foreach ( $titles as $rid => $t ) {
		if ( $t === $n || 0 === mb_strpos( $t, $n . ' ' ) || 0 === mb_strpos( $n, $t . ' ' ) ) {
			return $rid;
		}
	}
	return 0;
}

function ellada_guide_food_html( $id ) {
	$dishes   = ellada_guide_pairs( ellada_guide_val( $id, 'dishes' ) );
	$products = ellada_guide_pairs( ellada_guide_val( $id, 'local_products' ) );
	if ( ! $dishes && ! $products ) {
		return '';
	}
	$html = '<section class="ellada-gfood" id="ti-na-fate"><h2 class="ellada-h">Τι να φάτε</h2>';
	if ( $dishes ) {
		$html .= '<ul class="ellada-gdishes">';
		foreach ( $dishes as list( $name, $desc ) ) {
			$rid   = ellada_guide_dish_recipe( $name );
			$inner = '<strong>' . esc_html( $name ) . '</strong>' . ( $desc ? '<span>' . esc_html( $desc ) . '</span>' : '' )
				. ( $rid ? '<em>Η συνταγή <span aria-hidden="true">→</span></em>' : '' );
			$html .= '<li' . ( $rid ? ' class="has-recipe"' : '' ) . '>' . ( $rid ? '<a href="' . esc_url( get_permalink( $rid ) ) . '">' . $inner . '</a>' : '<div>' . $inner . '</div>' ) . '</li>';
		}
		$html .= '</ul>';
	}
	if ( $products ) {
		$html .= '<h3 class="ellada-gfood__h">Τοπικά προϊόντα</h3><ul class="ellada-gproducts">';
		foreach ( $products as list( $name, $note ) ) {
			$html .= '<li><strong>' . esc_html( $name ) . '</strong>' . ( $note ? '<span>' . esc_html( $note ) . '</span>' : '' ) . '</li>';
		}
		$html .= '</ul>';
	}
	return $html . '</section>';
}

function ellada_guide_when_html( $id ) {
	$choices = array(
		'all_year' => 'Όλο τον χρόνο',
		'spring'   => 'Άνοιξη (Μάρτιος – Μάιος)',
		'summer'   => 'Καλοκαίρι (Ιούνιος – Αύγουστος)',
		'autumn'   => 'Φθινόπωρο (Σεπτέμβριος – Νοέμβριος)',
		'winter'   => 'Χειμώνας (Δεκέμβριος – Φεβρουάριος)',
	);
	$best     = ellada_guide_val( $id, 'best_time_to_visit' );
	$best     = is_array( $best ) ? ( $best['value'] ?? '' ) : (string) $best;
	$best     = $choices[ $best ] ?? '';
	$calendar = ellada_guide_pairs( ellada_guide_val( $id, 'food_calendar' ) );
	$how      = trim( (string) ellada_guide_val( $id, 'how_to_get_there' ) );
	$tips     = array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) ellada_guide_val( $id, 'travel_tips' ) ) ) ) );
	if ( ! $best && ! $calendar && ! $how && ! $tips ) {
		return '';
	}
	$html = '<section class="ellada-gwhen" id="pote"><h2 class="ellada-h">Πότε &amp; πώς</h2><div class="ellada-gwhen__grid">';
	if ( $best || $calendar ) {
		$html .= '<div class="ellada-gwhen__col">';
		$html .= $best ? '<p class="ellada-gwhen__best"><span>Καλύτερη εποχή</span>' . esc_html( $best ) . '</p>' : '';
		if ( $calendar ) {
			$html .= '<h3 class="ellada-gfood__h">Γαστρονομικό ημερολόγιο</h3><ol class="ellada-gcal">';
			foreach ( $calendar as list( $when, $what ) ) {
				$html .= '<li><span class="ellada-gcal__when">' . esc_html( $when ) . '</span><span class="ellada-gcal__what">' . esc_html( $what ) . '</span></li>';
			}
			$html .= '</ol>';
		}
		$html .= '</div>';
	}
	if ( $how || $tips ) {
		$html .= '<div class="ellada-gwhen__col">';
		if ( $how ) {
			$html .= '<h3 class="ellada-gfood__h">Πώς θα έρθετε</h3><div class="ellada-gwhen__text">' . wpautop( esc_html( $how ) ) . '</div>';
		}
		if ( $tips ) {
			$html .= '<h3 class="ellada-gfood__h">Συμβουλές</h3><ul class="ellada-gtips">';
			foreach ( $tips as $t ) {
				$html .= '<li>' . esc_html( ltrim( $t, "-•· \t" ) ) . '</li>';
			}
			$html .= '</ul>';
		}
		$html .= '</div>';
	}
	return $html . '</div></section>';
}

/** «Οι νομοί» της περιφέρειας σε κάρτες με χάρτη και την πρώτη πρόταση του οδηγού. */
function ellada_guide_pref_cards_html( $c ) {
	$prefs = ellada_guide_region_prefs()[ $c['region'] ] ?? array();
	$munis = ellada_guide_muni_counts();
	$cards = '';
	foreach ( $prefs as $p ) {
		$pid = ellada_guide_page( 'prefecture', $p );
		if ( ! $pid ) {
			continue;
		}
		$first  = ellada_guide_first_sentence( $pid );
		$n      = $munis[ $p ] ?? 0;
		$cards .= sprintf(
			'<a class="ellada-gpref" href="%1$s">%2$s<div><p class="ellada-ghub__sub-eyebrow">%3$s%4$s</p><h3>%5$s</h3>%6$s</div></a>',
			esc_url( get_permalink( $pid ) ),
			ellada_region_zoom_svg( $c['region'], $p ),
			esc_html( sprintf( 1 === $n ? '%d δήμος' : '%d δήμοι', $n ) ),
			ellada_guide_has_text( $pid ) ? ' · <span class="ellada-gbadge">Οδηγός</span>' : '',
			esc_html( get_the_title( $pid ) ),
			$first ? '<p>' . esc_html( $first ) . '</p>' : ''
		);
	}
	return $cards ? '<section class="ellada-gsec" id="topoi"><h2 class="ellada-h">Οι νομοί</h2><div class="ellada-gprefs">' . $cards . '</div></section>' : '';
}

/* ═════════════════════ Ένταξη στο [ellada_guide] ═════════════════════ */

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( ! ellada_guide_v3() ) {
			return $output;
		}
		// Τα ίδια πεδία τα δείχνει πλέον ο οδηγός (στη σωστή θέση).
		if ( 'ellada_location_guide' === $tag ) {
			return '';
		}
		if ( 'ellada_guide' !== $tag ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_location' !== get_post_type( $id ) ) {
			return $output;
		}
		$c      = ellada_guide_ctx( $id );
		$facts  = ellada_guide_facts_html( $c );
		$kids   = ellada_guide_children_html( $id, $c );
		$extra  = ellada_guide_food_html( $id ) . ellada_guide_when_html( $id );

		// «Οι νομοί» σε κάρτες (περιφέρειες).
		if ( 'region' === $c['level'] && $kids ) {
			$cards = ellada_guide_pref_cards_html( $c );
			if ( $cards ) {
				$output = str_replace( $kids, $cards, $output );
				$kids   = $cards;
			}
		} elseif ( $kids ) {
			$output = str_replace( $kids, str_replace( '<section class="ellada-gsec">', '<section class="ellada-gsec" id="topoi">', $kids ), $output );
			$kids   = str_replace( '<section class="ellada-gsec">', '<section class="ellada-gsec" id="topoi">', $kids );
		}
		// Φαγητό και «Πότε & πώς» πριν από τους νομούς/δήμους.
		if ( $extra ) {
			$pos    = $kids ? strpos( $output, $kids ) : false;
			$pos    = false === $pos ? strpos( $output, '<p class="ellada-gsource"' ) : $pos;
			$output = false === $pos ? $output . $extra : substr_replace( $output, $extra, $pos, 0 );
		}
		if ( $facts ) {
			$output = str_replace( '<section class="ellada-glance">', '<section class="ellada-glance" id="me-mia-matia">', $output );
		}

		// Μενού πλοήγησης (μόνο σε σελίδες με κείμενο).
		if ( ellada_guide_has_text( $id ) ) {
			$links = array( 'me-mia-matia' => 'Με μια ματιά' );
			if ( false !== strpos( $output, 'id="ti-na-fate"' ) ) {
				$links['ti-na-fate'] = 'Τι να φάτε';
			}
			if ( false !== strpos( $output, 'id="pote"' ) ) {
				$links['pote'] = 'Πότε & πώς';
			}
			if ( false !== strpos( $output, 'id="topoi"' ) ) {
				$links['topoi'] = 'region' === $c['level'] ? 'Νομοί' : ( 'prefecture' === $c['level'] ? 'Δήμοι' : 'Τόποι' );
			}
			if ( function_exists( 'ellada_search_recipe_count' ) && ellada_search_recipe_count( $id ) > 0 ) {
				$links['syntages'] = 'Συνταγές';
			}
			$links['xartis'] = 'Χάρτης';
			$nav             = '<nav class="ellada-gnav" aria-label="Σε αυτή τη σελίδα"><ul>';
			foreach ( $links as $anchor => $label ) {
				$nav .= '<li><a href="#' . esc_attr( $anchor ) . '">' . esc_html( $label ) . '</a></li>';
			}
			$nav   .= '</ul></nav>';
			// Οι ενότητες «Συνταγές» και «Χάρτης» είναι στο template: παίρνουν id από εδώ.
			$nav   .= '<script>(function(){function go(){var m={"s-loc-recipes":"syntages","s-loc-map":"xartis"};Object.keys(m).forEach(function(c){var e=document.querySelector("."+c);if(e&&!e.id)e.id=m[c]});}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",go);else go();})();</script>';
			$output = $nav . $output;
		}
		return $output;
	},
	30,
	3
);

/* Συνταγές της περιοχής: «Όλες οι συνταγές» + «Στείλτε μας». */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_cards' !== $tag || ! ellada_guide_v3() || ! is_singular( 'ellada_location' ) || '' === trim( $output ) ) {
			return $output;
		}
		$attr = (array) $attr;
		if ( ( $attr['source'] ?? '' ) !== 'area' || ( isset( $attr['type'] ) && 'recipe' !== $attr['type'] ) ) {
			return $output;
		}
		$c       = ellada_guide_ctx( get_queried_object_id() );
		$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
		$links   = '';
		if ( $c['region'] ) {
			$links .= sprintf( '<a class="ellada-grecipes__all" href="%s">Όλες οι συνταγές της περιοχής <span aria-hidden="true">→</span></a>', esc_url( add_query_arg( 'perioxi', rawurlencode( $c['region'] ), get_post_type_archive_link( 'ellada_recipe' ) ) ) );
		}
		if ( $contact ) {
			$links .= sprintf( '<a class="ellada-grecipes__send" href="%s">Έχετε μια συνταγή από εδώ; Στείλτε τη μας</a>', esc_url( add_query_arg( 'thema', 'syntagi', get_permalink( $contact ) ) . '#form-field-message' ) );
		}
		return $output . ( $links ? '<div class="ellada-grecipes">' . $links . '</div>' : '' );
	},
	30,
	3
);

/* ═════════════════════ Google: TouristDestination ═════════════════════ */

add_action(
	'wp_head',
	function () {
		if ( ! ellada_guide_v3() || ! is_singular( 'ellada_location' ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( ! ellada_guide_has_text( $id ) ) {
			return;
		}
		$c      = ellada_guide_ctx( $id );
		$parent = '';
		if ( 'region' !== $c['level'] && $c['region'] ) {
			$parent = 'prefecture' === $c['level'] || ! $c['pref'] ? $c['region'] : ellada_guide_prefecture_title( $c['pref'] );
		}
		$cover = (int) get_post_meta( $id, 'cover_image', true );
		$data  = array_filter(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'TouristDestination',
				'name'            => get_the_title( $id ),
				'url'             => get_permalink( $id ),
				'description'     => function_exists( 'ellada_meta_text' ) ? ellada_meta_text( get_post_meta( $id, 'guide_intro', true ), 300 ) : '',
				'image'           => $cover ? wp_get_attachment_image_url( $cover, 'large' ) : null,
				'containedInPlace' => array_filter(
					array(
						'@type' => 'AdministrativeArea',
						'name'  => $parent ? $parent : 'Ελλάδα',
					)
				),
			)
		);
		printf( "<script type=\"application/ld+json\">%s</script>\n", wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) );
	},
	21
);
