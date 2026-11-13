<?php
/**
 * Δομημένα κομμάτια των σελίδων του οδηγού, ως shortcodes για τα templates του Elementor.
 * Χρησιμοποιούν τα χρώματα και τις γραμματοσειρές του Elementor Kit (var(--e-global-*)).
 *
 *   [ellada_recipe_facts]        Χρόνοι, μερίδες, δυσκολία, κατηγορία, ετικέτες, περιοχή
 *   [ellada_recipe_ingredients]  Υλικά (με ομάδες)
 *   [ellada_recipe_steps]        Βήματα εκτέλεσης
 *   [ellada_recipe_extras]       Μυστικά επιτυχίας, αλλεργιογόνα, φωτογραφίες
 *   [ellada_location_guide]      Ο οδηγός μιας περιοχής (προϊόντα, αξιοθέατα, πότε, πώς)
 *   [ellada_area_map]            Ο χάρτης ανοιχτός στην περιφέρεια της σελίδας
 *   [ellada_cards source="area|collection|related|latest|featured|archive" type="recipe|point|collection" limit="12"
 *                 title="" more_url="" more_label=""]   Ο σύνδεσμος «more» εμφανίζεται μόνο όταν υπάρχουν κάρτες.
 *   [ellada_regions]             Οι περιφέρειες που έχουν σελίδα, με το πλήθος συνταγών τους
 *   [ellada_term_filter taxonomy="recipe_category"]
 */

defined( 'ABSPATH' ) || exit;

/* ═════════════════════ Στυλ ═════════════════════ */

function ellada_front_style() {
	if ( ! wp_style_is( 'ellada-front', 'registered' ) ) {
		$path = ELLADA_MAP_DIR . 'public/front.css';
		wp_register_style( 'ellada-front', plugins_url( 'public/front.css', ELLADA_MAP_FILE ), array(), file_exists( $path ) ? (string) filemtime( $path ) : ELLADA_MAP_VERSION );
	}
	wp_enqueue_style( 'ellada-front' );
}

/** Στις σελίδες του οδηγού τα στυλ μπαίνουν στο <head> (όχι αργά στο footer). */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_singular( array( 'ellada_recipe', 'ellada_location', 'ellada_collection', 'ellada_point' ) )
			|| is_post_type_archive( array( 'ellada_recipe', 'ellada_collection' ) )
			|| is_tax( array( 'recipe_category', 'recipe_diet' ) )
			|| is_front_page() ) {
			ellada_front_style();
		}
	}
);
add_action( 'elementor/preview/enqueue_styles', 'ellada_front_style' );

/* ═════════════════════ Βοηθητικά ═════════════════════ */

function ellada_ctx_id( $atts ) {
	$id = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;
	return $id ? $id : get_the_ID();
}

function ellada_minutes( $min ) {
	$min = (int) $min;
	if ( $min <= 0 ) {
		return '';
	}
	if ( $min < 60 ) {
		return $min . ' λεπτά';
	}
	$h = intdiv( $min, 60 );
	$m = $min % 60;
	return $h . ' ώρ' . ( 1 === $h ? 'α' : 'ες' ) . ( $m ? ' ' . $m . ' λ.' : '' );
}

/** Γραμμές κειμένου (textarea) ως λίστα. */
function ellada_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}

/** Χρώμα πινέζας ανά τύπο σημείου (ρυθμίσεις χάρτη, αλλιώς τα προτεινόμενα). */
function ellada_point_color( $type ) {
	$s = ellada_map_settings();
	if ( ! empty( $s['type_colors'][ $type ] ) ) {
		return $s['type_colors'][ $type ];
	}
	$sug = ellada_map_suggested_type_colors();
	return isset( $sug[ $type ] ) ? $sug[ $type ] : '#F97316';
}

function ellada_select_value( $v ) {
	return is_array( $v ) ? ( $v['value'] ?? '' ) : (string) $v;
}

/* ═════════════════════ Συνταγή ═════════════════════ */

add_shortcode(
	'ellada_recipe_facts',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return '';
		}
		ellada_front_style();
		$prep  = (int) get_post_meta( $id, 'prep_time', true );
		$cook  = (int) get_post_meta( $id, 'cook_time', true );
		$serv  = (int) get_post_meta( $id, 'servings', true );
		$diff  = ellada_select_value( get_post_meta( $id, 'difficulty', true ) );
		$diffs = array(
			'easy'   => 'Εύκολη',
			'medium' => 'Μέτρια',
			'hard'   => 'Απαιτητική',
		);

		$facts = array_filter(
			array(
				'Προετοιμασία' => ellada_minutes( $prep ),
				'Μαγείρεμα'    => ellada_minutes( $cook ),
				'Σύνολο'       => $prep && $cook ? ellada_minutes( $prep + $cook ) : '',
				'Μερίδες'      => $serv ? (string) $serv : '',
				'Δυσκολία'     => isset( $diffs[ $diff ] ) ? $diffs[ $diff ] : '',
			)
		);

		$chips = array();
		$loc   = (int) get_post_meta( $id, 'location', true );
		if ( $loc && 'publish' === get_post_status( $loc ) ) {
			$chips[] = sprintf( '<a class="ellada-chip is-area" href="%s">%s</a>', esc_url( get_permalink( $loc ) ), esc_html( get_the_title( $loc ) ) );
		}
		foreach ( array( 'recipe_category', 'recipe_diet' ) as $tax ) {
			$terms = get_the_terms( $id, $tax );
			foreach ( is_array( $terms ) ? $terms : array() as $t ) {
				$chips[] = sprintf( '<a class="ellada-chip" href="%s">%s</a>', esc_url( get_term_link( $t ) ), esc_html( $t->name ) );
			}
		}

		if ( ! $facts && ! $chips ) {
			return '';
		}
		$html = '<div class="ellada-facts">';
		if ( $facts ) {
			$html .= '<dl class="ellada-facts__list">';
			foreach ( $facts as $label => $value ) {
				$html .= sprintf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html( $label ), esc_html( $value ) );
			}
			$html .= '</dl>';
		}
		if ( $chips ) {
			$html .= '<div class="ellada-chips">' . implode( '', $chips ) . '</div>';
		}
		return $html . '</div>';
	}
);

add_shortcode(
	'ellada_recipe_ingredients',
	function ( $atts ) {
		$id   = ellada_ctx_id( (array) $atts );
		$rows = function_exists( 'get_field' ) ? (array) get_field( 'ingredients', $id ) : array();
		$rows = array_filter( $rows, 'is_array' );
		if ( ! $rows ) {
			return '';
		}
		ellada_front_style();
		$serv = (int) get_post_meta( $id, 'servings', true );
		$html = '<section class="ellada-ingredients"><h2 class="ellada-h">Υλικά</h2>';
		if ( $serv ) {
			$html .= sprintf( '<p class="ellada-muted">Για %d %s</p>', $serv, 1 === $serv ? 'μερίδα' : 'μερίδες' );
		}
		$open = false;
		foreach ( $rows as $row ) {
			$section = trim( (string) ( $row['section'] ?? '' ) );
			$item    = trim( (string) ( $row['item'] ?? '' ) );
			if ( $section && ! $item ) {
				$html .= ( $open ? '</ul>' : '' ) . sprintf( '<h3 class="ellada-ingredients__group">%s</h3><ul>', esc_html( $section ) );
				$open  = true;
				continue;
			}
			if ( ! $item ) {
				continue;
			}
			if ( ! $open ) {
				$html .= '<ul>';
				$open  = true;
			}
			$qty   = trim( (string) ( $row['quantity'] ?? '' ) );
			$note  = trim( (string) ( $row['note'] ?? '' ) );
			$html .= sprintf(
				'<li>%s<span class="ellada-ingredients__item">%s</span>%s</li>',
				$qty ? '<span class="ellada-ingredients__qty">' . esc_html( $qty ) . '</span>' : '',
				esc_html( $item ),
				$note ? ' <span class="ellada-muted">' . esc_html( $note ) . '</span>' : ''
			);
		}
		return $html . ( $open ? '</ul>' : '' ) . '</section>';
	}
);

add_shortcode(
	'ellada_recipe_steps',
	function ( $atts ) {
		$id   = ellada_ctx_id( (array) $atts );
		$rows = function_exists( 'get_field' ) ? (array) get_field( 'steps', $id ) : array();
		$rows = array_filter(
			$rows,
			function ( $r ) {
				return is_array( $r ) && trim( wp_strip_all_tags( (string) ( $r['text'] ?? '' ) ) ) !== '';
			}
		);
		if ( ! $rows ) {
			return '';
		}
		ellada_front_style();
		$html = '<section class="ellada-steps"><h2 class="ellada-h">Εκτέλεση</h2><ol>';
		$n    = 0;
		foreach ( $rows as $row ) {
			$n++;
			$img = '';
			if ( ! empty( $row['image']['ID'] ) ) {
				$img = wp_get_attachment_image( (int) $row['image']['ID'], 'medium_large', false, array( 'class' => 'ellada-steps__img', 'loading' => 'lazy' ) );
			}
			$html .= sprintf(
				'<li id="step-%1$d"><span class="ellada-steps__n" aria-hidden="true">%1$d</span><div class="ellada-steps__body"><p>%2$s</p>%3$s</div></li>',
				$n,
				wp_kses( (string) $row['text'], array( 'br' => array() ) ),
				$img
			);
		}
		return $html . '</ol></section>';
	}
);

add_shortcode(
	'ellada_recipe_extras',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}
		$tips      = trim( (string) get_field( 'tips', $id ) );
		$allergens = (array) get_field( 'allergens', $id );
		$gallery   = (array) get_field( 'gallery', $id );
		if ( ! $tips && ! array_filter( $allergens ) && ! array_filter( $gallery ) ) {
			return '';
		}
		ellada_front_style();
		$html = '<div class="ellada-extras">';
		if ( $tips ) {
			$html .= '<section class="ellada-tips"><h2 class="ellada-h">Μυστικά επιτυχίας</h2>' . wp_kses_post( $tips ) . '</section>';
		}
		$labels = array();
		foreach ( $allergens as $a ) {
			$labels[] = is_array( $a ) ? ( $a['label'] ?? '' ) : (string) $a;
		}
		$labels = array_filter( $labels );
		if ( $labels ) {
			$html .= '<p class="ellada-allergens"><strong>Περιέχει:</strong> ' . esc_html( implode( ', ', $labels ) ) . '</p>';
		}
		$html .= ellada_gallery_html( $gallery );
		return $html . '</div>';
	}
);

function ellada_gallery_html( $images ) {
	$images = array_filter( (array) $images );
	if ( ! $images ) {
		return '';
	}
	$html = '<div class="ellada-gallery">';
	foreach ( $images as $img ) {
		$aid = is_array( $img ) ? (int) ( $img['ID'] ?? 0 ) : (int) $img;
		if ( ! $aid ) {
			continue;
		}
		$html .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( wp_get_attachment_image_url( $aid, 'large' ) ),
			wp_get_attachment_image( $aid, 'medium_large', false, array( 'loading' => 'lazy' ) )
		);
	}
	return $html . '</div>';
}

/* ═════════════════════ Τοποθεσία ═════════════════════ */

add_shortcode(
	'ellada_location_guide',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( 'ellada_location' !== get_post_type( $id ) || ! function_exists( 'get_field' ) ) {
			return '';
		}
		ellada_front_style();
		$best   = get_field( 'best_time_to_visit', $id );
		$best   = is_array( $best ) ? ( $best['label'] ?? '' ) : (string) $best;
		$lists  = array(
			'Τοπικά προϊόντα'  => ellada_lines( get_field( 'local_products', $id ) ),
			'Αξίζει να δείτε' => ellada_lines( get_field( 'highlights', $id ) ),
		);
		$texts  = array(
			'Πώς θα φτάσετε' => trim( (string) get_field( 'how_to_get_there', $id ) ),
			'Συμβουλές'      => trim( (string) get_field( 'travel_tips', $id ) ),
		);
		$gallery = (array) get_field( 'gallery', $id );

		$main = '';
		foreach ( $lists as $title => $items ) {
			if ( ! $items ) {
				continue;
			}
			$main .= '<section><h2 class="ellada-h">' . esc_html( $title ) . '</h2><ul class="ellada-list">';
			foreach ( $items as $it ) {
				$main .= '<li>' . esc_html( $it ) . '</li>';
			}
			$main .= '</ul></section>';
		}
		$side = '';
		if ( $best ) {
			$side .= '<div><dt>Καλύτερη εποχή</dt><dd>' . esc_html( $best ) . '</dd></div>';
		}
		foreach ( $texts as $title => $text ) {
			if ( $text ) {
				$side .= '<div><dt>' . esc_html( $title ) . '</dt><dd>' . nl2br( esc_html( $text ) ) . '</dd></div>';
			}
		}
		if ( ! $main && ! $side && ! array_filter( $gallery ) ) {
			return '';
		}
		$html = '<div class="ellada-guide">';
		$html .= $main ? '<div class="ellada-guide__main">' . $main . '</div>' : '';
		$html .= $side ? '<dl class="ellada-guide__side">' . $side . '</dl>' : '';
		$html .= '</div>';
		return $html . ellada_gallery_html( $gallery );
	}
);

add_shortcode(
	'ellada_area_map',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( 'ellada_location' !== get_post_type( $id ) ) {
			return ellada_map_render( array() );
		}
		$area = ellada_location_area( $id );
		return ellada_map_render(
			array(
				'start_region' => $area['region'],
				'height'       => isset( $atts['height'] ) ? $atts['height'] : '',
			)
		);
	}
);

/* ═════════════════════ Κάρτες ═════════════════════ */

function ellada_recipe_card( $post_id ) {
	$img   = get_the_post_thumbnail( $post_id, 'medium_large', array( 'loading' => 'lazy', 'class' => 'ellada-card__img' ) );
	$cats  = get_the_terms( $post_id, 'recipe_category' );
	$cat   = is_array( $cats ) && $cats ? $cats[0]->name : '';
	$total = (int) get_post_meta( $post_id, 'prep_time', true ) + (int) get_post_meta( $post_id, 'cook_time', true );
	$loc   = (int) get_post_meta( $post_id, 'location', true );
	$meta  = array_filter( array( ellada_minutes( $total ), $loc ? get_the_title( $loc ) : '' ) );
	return sprintf(
		'<article class="ellada-card"><a href="%1$s" class="ellada-card__link"><div class="ellada-card__media">%2$s</div><div class="ellada-card__body">%3$s<h3 class="ellada-card__title">%4$s</h3>%5$s</div></a></article>',
		esc_url( get_permalink( $post_id ) ),
		$img ? $img : '<span class="ellada-card__placeholder" aria-hidden="true"></span>',
		$cat ? '<p class="ellada-card__eyebrow">' . esc_html( $cat ) . '</p>' : '',
		esc_html( get_the_title( $post_id ) ),
		$meta ? '<p class="ellada-card__meta">' . esc_html( implode( ' · ', $meta ) ) . '</p>' : ''
	);
}

function ellada_collection_card( $post_id ) {
	$img   = get_the_post_thumbnail( $post_id, 'medium_large', array( 'loading' => 'lazy', 'class' => 'ellada-card__img' ) );
	$theme = get_post_meta( $post_id, 'theme', true );
	$themes = array(
		'spring'    => 'Άνοιξη',
		'summer'    => 'Καλοκαίρι',
		'autumn'    => 'Φθινόπωρο',
		'winter'    => 'Χειμώνας',
		'easter'    => 'Πάσχα',
		'christmas' => 'Χριστούγεννα',
		'fasting'   => 'Νηστίσιμα',
	);
	$label = isset( $themes[ ellada_select_value( $theme ) ] ) ? $themes[ ellada_select_value( $theme ) ] : '';
	$count = count( array_filter( (array) get_post_meta( $post_id, 'recipes', true ) ) );
	$desc  = trim( (string) get_post_meta( $post_id, 'short_description', true ) );
	return sprintf(
		'<article class="ellada-card is-collection"><a href="%1$s" class="ellada-card__link"><div class="ellada-card__media">%2$s</div><div class="ellada-card__body">%3$s<h3 class="ellada-card__title">%4$s</h3>%5$s%6$s</div></a></article>',
		esc_url( get_permalink( $post_id ) ),
		$img ? $img : '<span class="ellada-card__placeholder" aria-hidden="true"></span>',
		$label ? '<p class="ellada-card__eyebrow">' . esc_html( $label ) . '</p>' : '',
		esc_html( get_the_title( $post_id ) ),
		$desc ? '<p class="ellada-card__desc">' . esc_html( $desc ) . '</p>' : '',
		$count ? '<p class="ellada-card__meta">' . esc_html( sprintf( 1 === $count ? '%d συνταγή' : '%d συνταγές', $count ) ) . '</p>' : ''
	);
}

function ellada_point_card( $post_id ) {
	$type   = ellada_select_value( get_post_meta( $post_id, 'point_type', true ) );
	$types  = ellada_map_point_types();
	$label  = isset( $types[ $type ] ) ? $types[ $type ] : '';
	$desc   = trim( (string) get_post_meta( $post_id, 'short_description', true ) );
	$ext    = trim( (string) get_post_meta( $post_id, 'external_url', true ) );
	$href   = $ext ? $ext : get_permalink( $post_id );
	$target = $ext ? ' target="_blank" rel="noopener"' : '';
	$img_id = (int) get_post_meta( $post_id, 'cover_image', true );
	$img    = $img_id ? wp_get_attachment_image( $img_id, 'medium', false, array( 'loading' => 'lazy', 'class' => 'ellada-point__img' ) ) : '';
	return sprintf(
		'<article class="ellada-point"><a href="%1$s"%2$s>%3$s<div><p class="ellada-point__type"><span style="background:%4$s"></span>%5$s</p><h3 class="ellada-point__title">%6$s</h3>%7$s</div></a></article>',
		esc_url( $href ),
		$target,
		$img,
		esc_attr( ellada_point_color( $type ) ),
		esc_html( $label ),
		esc_html( get_the_title( $post_id ) ),
		$desc ? '<p class="ellada-point__desc">' . esc_html( $desc ) . '</p>' : ''
	);
}

add_shortcode(
	'ellada_cards',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'source' => 'latest',
				'type'   => 'recipe',
				'limit'  => 12,
				'title'  => '',
				'empty'      => '',
				'id'         => 0,
				'more_url'   => '',
				'more_label' => '',
			),
			$atts,
			'ellada_cards'
		);
		ellada_front_style();
		$types     = array(
			'recipe'     => array( 'ellada_recipe', 'ellada_recipe_card', 'ellada-cards' ),
			'point'      => array( 'ellada_point', 'ellada_point_card', 'ellada-points' ),
			'collection' => array( 'ellada_collection', 'ellada_collection_card', 'ellada-cards' ),
		);
		list( $post_type, $render, $grid ) = isset( $types[ $atts['type'] ] ) ? $types[ $atts['type'] ] : $types['recipe'];

		if ( 'archive' === $atts['source'] ) {
			global $wp_query;
			$ids = wp_list_pluck( (array) $wp_query->posts, 'ID' );
			$pag = paginate_links(
				array(
					'total'     => (int) $wp_query->max_num_pages,
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'prev_text' => 'Προηγούμενες',
					'next_text' => 'Επόμενες',
				)
			);
		} else {
			$ctx = absint( $atts['id'] ) ? absint( $atts['id'] ) : get_queried_object_id();
			$q   = new WP_Query( ellada_recipe_query_args( $atts['source'], $ctx, $atts['limit'], $post_type ) );
			$ids = wp_list_pluck( $q->posts, 'ID' );
			$pag = '';
			// «Δείτε ακόμα»: αν η ίδια περιφέρεια δεν έχει αρκετές, συμπληρώνεται με τις πιο πρόσφατες.
			if ( 'related' === $atts['source'] && count( $ids ) < (int) $atts['limit'] ) {
				$more = ellada_recipe_query_args( 'latest', $ctx, (int) $atts['limit'] - count( $ids ), $post_type );
				$more['post__not_in'] = array_merge( array( $ctx ), $ids );
				$ids = array_merge( $ids, wp_list_pluck( ( new WP_Query( $more ) )->posts, 'ID' ) );
			}
		}

		if ( ! $ids ) {
			return $atts['empty'] ? '<p class="ellada-empty">' . esc_html( $atts['empty'] ) . '</p>' : '';
		}
		$html = $atts['title'] ? '<h2 class="ellada-h">' . esc_html( $atts['title'] ) . '</h2>' : '';
		$html .= '<div class="' . $grid . '">';
		foreach ( $ids as $pid ) {
			$html .= call_user_func( $render, $pid );
		}
		$html .= '</div>';
		if ( $pag ) {
			$html .= '<nav class="ellada-pagination" aria-label="Σελίδες">' . $pag . '</nav>';
		}
		if ( $atts['more_url'] && $atts['more_label'] ) {
			$html .= sprintf( '<p class="ellada-more"><a href="%s">%s</a></p>', esc_url( home_url( $atts['more_url'] ) ), esc_html( $atts['more_label'] ) );
		}
		// Το περιθώριο μπαίνει μόνο όταν υπάρχει περιεχόμενο, ώστε μια άδεια ενότητα να μην αφήνει κενό.
		return '<div class="ellada-block">' . $html . '</div>';
	}
);

add_shortcode(
	'ellada_term_filter',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'taxonomy' => 'recipe_category' ), $atts, 'ellada_term_filter' );
		$terms = get_terms(
			array(
				'taxonomy'   => sanitize_key( $atts['taxonomy'] ),
				'hide_empty' => true,
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			return '';
		}
		ellada_front_style();
		$current = is_tax( $atts['taxonomy'] ) ? get_queried_object_id() : 0;
		$html    = '<nav class="ellada-chips ellada-filter" aria-label="Κατηγορίες">';
		$html   .= sprintf( '<a class="ellada-chip%s" href="%s">Όλες</a>', $current ? '' : ' is-active', esc_url( get_post_type_archive_link( 'ellada_recipe' ) ) );
		foreach ( $terms as $t ) {
			$html .= sprintf( '<a class="ellada-chip%s" href="%s">%s</a>', $current === $t->term_id ? ' is-active' : '', esc_url( get_term_link( $t ) ), esc_html( $t->name ) );
		}
		return $html . '</nav>';
	}
);

add_shortcode(
	'ellada_regions',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'title' => '' ), $atts, 'ellada_regions' );
		$ids = get_posts(
			array(
				'post_type'      => 'ellada_location',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'meta_key'       => 'map_level', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'region', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);
		if ( ! $ids ) {
			return '';
		}
		ellada_front_style();
		$html = '<div class="ellada-block">' . ( $atts['title'] ? '<h2 class="ellada-h">' . esc_html( $atts['title'] ) . '</h2>' : '' ) . '<div class="ellada-regions">';
		foreach ( $ids as $id ) {
			$q     = new WP_Query( array_merge( ellada_recipe_query_args( 'area', $id, 1 ), array( 'no_found_rows' => false, 'fields' => 'ids' ) ) );
			$n     = (int) $q->found_posts;
			$html .= sprintf(
				'<a class="ellada-region" href="%s"><span class="ellada-region__name">%s</span>%s</a>',
				esc_url( get_permalink( $id ) ),
				esc_html( get_the_title( $id ) ),
				$n ? '<span class="ellada-region__count">' . esc_html( sprintf( 1 === $n ? '%d συνταγή' : '%d συνταγές', $n ) ) . '</span>' : ''
			);
		}
		return $html . '</div></div>';
	}
);

/** Αρχείο συνταγών: 12 ανά σελίδα. */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_post_type_archive( array( 'ellada_recipe', 'ellada_collection' ) ) || $q->is_tax( array( 'recipe_category', 'recipe_diet' ) ) ) {
			$q->set( 'posts_per_page', 12 );
		}
	}
);
