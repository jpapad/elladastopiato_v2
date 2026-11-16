<?php
/**
 * Σελίδα «Συνταγές» (αρχείο συνταγών, κατηγορίες, διατροφές).
 *
 *   [ellada_archive_intro]             Εισαγωγή + μετρητής («12 συνταγές από 5 περιφέρειες»)
 *   [ellada_recipe_filters]            Κατηγορίες (chips) + Περιφέρεια / Διατροφή / Χρόνος (συνδυάζονται)
 *   [ellada_recipe_discover min="12"]  «Της εποχής» + «Συλλογές» — εμφανίζονται μόνο όταν οι συνταγές
 *                                      φτάσουν τις `min` (πριν απλώς θα επαναλάμβαναν τις ίδιες).
 *
 * Φίλτρα στη διεύθυνση (για links): ?perioxi=Κρήτη &diatrofi=nistisimo &xronos=30
 * Φιλτραρισμένες σελίδες: noindex,follow (για να μη γεμίσει το Google με διπλότυπα).
 *
 * Προεπισκόπηση draft template: ?ellada_tpl=<id> (μόνο editors· το draft έχει meta _ellada_preview_of=<live id>).
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_TOTAL_TIME_KEY = '_ellada_total_time';
const ELLADA_TIME_LIMITS    = array(
	30 => 'Έως 30 λεπτά',
	60 => 'Έως 1 ώρα',
);

function ellada_recipes_intro_text() {
	return 'Συνταγές από κάθε γωνιά της Ελλάδας, όπως τις μαγειρεύουν στον τόπο τους: με τα υλικά της εποχής, τα μυστικά της γιαγιάς και τις ιστορίες που τις συνοδεύουν.';
}

/* ═════════════════════ Συνολικός χρόνος (για το φίλτρο «Χρόνος») ═════════════════════ */

function ellada_update_total_time( $post_id ) {
	$total = (int) get_post_meta( $post_id, 'prep_time', true ) + (int) get_post_meta( $post_id, 'cook_time', true );
	if ( $total > 0 ) {
		update_post_meta( $post_id, ELLADA_TOTAL_TIME_KEY, $total );
	} else {
		delete_post_meta( $post_id, ELLADA_TOTAL_TIME_KEY );
	}
}
// Μετά την αποθήκευση των πεδίων ACF (priority 20).
add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( is_numeric( $post_id ) && 'ellada_recipe' === get_post_type( (int) $post_id ) ) {
			ellada_update_total_time( (int) $post_id );
		}
	},
	20
);
add_action(
	'save_post_ellada_recipe',
	function ( $post_id ) {
		ellada_update_total_time( $post_id );
	},
	99
);
/** Μία φορά: συμπλήρωση για τις υπάρχουσες συνταγές. */
add_action(
	'init',
	function () {
		if ( get_option( 'ellada_total_time_v1' ) ) {
			return;
		}
		foreach ( get_posts( array( 'post_type' => 'ellada_recipe', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			ellada_update_total_time( $id );
		}
		update_option( 'ellada_total_time_v1', 1, true );
	},
	30
);

/* ═════════════════════ Φίλτρα από τη διεύθυνση ═════════════════════ */

/** @return array{perioxi:string,diatrofi:string,xronos:int} */
function ellada_recipe_filter_values() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$region = isset( $_GET['perioxi'] ) ? sanitize_text_field( wp_unslash( $_GET['perioxi'] ) ) : '';
	$diet   = isset( $_GET['diatrofi'] ) ? sanitize_title( wp_unslash( $_GET['diatrofi'] ) ) : '';
	$time   = isset( $_GET['xronos'] ) ? absint( $_GET['xronos'] ) : 0;
	// phpcs:enable
	return array(
		'perioxi'  => in_array( $region, ellada_map_region_names(), true ) ? $region : '',
		'diatrofi' => ( $diet && term_exists( $diet, 'recipe_diet' ) ) ? $diet : '',
		'xronos'   => isset( ELLADA_TIME_LIMITS[ $time ] ) ? $time : 0,
	);
}

function ellada_recipe_filters_active() {
	return (bool) array_filter( ellada_recipe_filter_values() );
}

function ellada_is_recipe_listing( $q = null ) {
	$q = $q ? $q : $GLOBALS['wp_query'];
	return $q->is_post_type_archive( 'ellada_recipe' ) || $q->is_tax( array( 'recipe_category', 'recipe_diet' ) );
}

/** Διατροφή και χρόνος (η περιφέρεια φιλτράρεται ήδη στο blocks.php). */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! ellada_is_recipe_listing( $q ) ) {
			return;
		}
		$f = ellada_recipe_filter_values();
		if ( $f['diatrofi'] && ! $q->is_tax( 'recipe_diet' ) ) {
			$tq   = (array) $q->get( 'tax_query' );
			$tq[] = array( 'taxonomy' => 'recipe_diet', 'field' => 'slug', 'terms' => $f['diatrofi'] );
			$q->set( 'tax_query', array_filter( $tq ) );
		}
		if ( $f['xronos'] ) {
			$mq   = (array) $q->get( 'meta_query' );
			$mq[] = array( 'key' => ELLADA_TOTAL_TIME_KEY, 'value' => $f['xronos'], 'compare' => '<=', 'type' => 'NUMERIC' );
			$q->set( 'meta_query', array_filter( $mq ) );
		}
		// Η περιφέρεια ισχύει και στις σελίδες διατροφής.
		if ( $f['perioxi'] && $q->is_tax( 'recipe_diet' ) ) {
			$mq   = (array) $q->get( 'meta_query' );
			$mq[] = array( 'key' => ELLADA_AREA_KEYS['region'], 'value' => $f['perioxi'] );
			$q->set( 'meta_query', array_filter( $mq ) );
		}
	},
	20
);

add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( ellada_is_recipe_listing() && ellada_recipe_filters_active() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

/* ═════════════════════ [ellada_archive_intro] ═════════════════════ */

/** Πόσες δημοσιευμένες συνταγές και από πόσες περιφέρειες. */
function ellada_recipe_totals() {
	$cached = get_transient( 'ellada_recipe_totals' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	$n       = (int) wp_count_posts( 'ellada_recipe' )->publish;
	$regions = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s AND pm.meta_value <> '' AND p.post_type = 'ellada_recipe' AND p.post_status = 'publish'",
			ELLADA_AREA_KEYS['region']
		)
	);
	$totals = array( 'recipes' => $n, 'regions' => $regions );
	set_transient( 'ellada_recipe_totals', $totals, DAY_IN_SECONDS );
	return $totals;
}
add_action(
	'save_post_ellada_recipe',
	function () {
		delete_transient( 'ellada_recipe_totals' );
	}
);
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'ellada_recipe' === $post->post_type && $new !== $old ) {
			delete_transient( 'ellada_recipe_totals' );
		}
	},
	10,
	3
);

function ellada_plural( $n, $one, $many ) {
	return sprintf( 1 === (int) $n ? $one : $many, number_format_i18n( $n ) );
}

add_shortcode(
	'ellada_archive_intro',
	function () {
		ellada_front_style();
		global $wp_query;
		$found = (int) $wp_query->found_posts;
		$term  = is_tax() ? get_queried_object() : null;

		if ( $term && ! empty( $term->description ) ) {
			$text = $term->description;
		} elseif ( $term ) {
			$text = sprintf( '%s από κάθε γωνιά της Ελλάδας, όπως τα μαγειρεύουν στον τόπο τους.', $term->name );
		} else {
			$text = ellada_recipes_intro_text();
		}

		if ( ellada_recipe_filters_active() ) {
			$count = $found
				? ellada_plural( $found, '%s συνταγή με αυτά τα φίλτρα', '%s συνταγές με αυτά τα φίλτρα' )
				: 'Δεν βρέθηκαν συνταγές με αυτά τα φίλτρα';
		} elseif ( $term ) {
			$count = ellada_plural( $found, '%s συνταγή', '%s συνταγές' );
		} else {
			$t     = ellada_recipe_totals();
			$count = ellada_plural( $t['recipes'], '%s συνταγή', '%s συνταγές' )
				. ( $t['regions'] ? ' από ' . ellada_plural( $t['regions'], '%s περιφέρεια', '%s περιφέρειες' ) : '' );
		}

		return '<div class="ellada-aintro"><p class="ellada-aintro__text">' . esc_html( wp_strip_all_tags( $text ) ) . '</p>'
			. '<p class="ellada-aintro__count">' . esc_html( $count ) . '</p></div>';
	}
);

/* ═════════════════════ [ellada_recipe_filters] ═════════════════════ */

/** Περιφέρειες που έχουν τουλάχιστον μία δημοσιευμένη συνταγή. */
function ellada_recipe_regions_in_use() {
	global $wpdb;
	$used = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s AND pm.meta_value <> '' AND p.post_type = 'ellada_recipe' AND p.post_status = 'publish'",
			ELLADA_AREA_KEYS['region']
		)
	);
	// Με τη σειρά του χάρτη.
	return array_values( array_intersect( ellada_map_region_names(), $used ) );
}

/** Όρια χρόνου για τα οποία υπάρχει τουλάχιστον μία συνταγή. */
function ellada_recipe_time_limits_in_use() {
	global $wpdb;
	$min = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT MIN(CAST(pm.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s AND p.post_type = 'ellada_recipe' AND p.post_status = 'publish'",
			ELLADA_TOTAL_TIME_KEY
		)
	);
	return $min ? array_filter( ELLADA_TIME_LIMITS, fn( $limit ) => $min <= $limit, ARRAY_FILTER_USE_KEY ) : array();
}

add_shortcode(
	'ellada_recipe_filters',
	function () {
		ellada_front_style();
		$f       = ellada_recipe_filter_values();
		$keep    = array_filter( $f ); // ενεργά φίλτρα, κρατιούνται στα links των κατηγοριών
		$archive = get_post_type_archive_link( 'ellada_recipe' );
		$on_cat  = is_tax( 'recipe_category' ) ? get_queried_object_id() : 0;
		$on_diet = is_tax( 'recipe_diet' ) ? get_queried_object() : null;
		$base    = is_tax() ? get_term_link( get_queried_object() ) : $archive;

		/* Κατηγορίες */
		$html  = '<div class="ellada-rfilters">';
		$terms = get_terms( array( 'taxonomy' => 'recipe_category', 'hide_empty' => true ) );
		if ( ! is_wp_error( $terms ) && $terms ) {
			$html .= '<nav class="ellada-chips ellada-filter ellada-rfilters__cats" aria-label="Κατηγορίες">';
			$html .= sprintf( '<a class="ellada-chip%s" href="%s">Όλες</a>', $on_cat ? '' : ' is-active', esc_url( add_query_arg( $keep, $archive ) ) );
			foreach ( $terms as $t ) {
				$html .= sprintf( '<a class="ellada-chip%s" href="%s">%s</a>', $on_cat === $t->term_id ? ' is-active' : '', esc_url( add_query_arg( $keep, get_term_link( $t ) ) ), esc_html( $t->name ) );
			}
			$html .= '</nav>';
		}

		/* Περιφέρεια / Διατροφή / Χρόνος */
		$regions = ellada_recipe_regions_in_use();
		$diets   = $on_diet ? array() : get_terms( array( 'taxonomy' => 'recipe_diet', 'hide_empty' => true ) );
		$diets   = is_wp_error( $diets ) ? array() : $diets;
		$times   = ellada_recipe_time_limits_in_use();

		$selects = '';
		if ( count( $regions ) > 1 || $f['perioxi'] ) {
			$selects .= '<label class="ellada-rfilters__field"><span>Περιφέρεια</span><select name="perioxi"><option value="">Όλη η Ελλάδα</option>';
			foreach ( $regions as $r ) {
				$selects .= sprintf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $r ), selected( $f['perioxi'], $r, false ) );
			}
			$selects .= '</select></label>';
		}
		if ( $diets ) {
			$selects .= '<label class="ellada-rfilters__field"><span>Διατροφή</span><select name="diatrofi"><option value="">Όλες</option>';
			foreach ( $diets as $d ) {
				$selects .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $d->slug ), selected( $f['diatrofi'], $d->slug, false ), esc_html( $d->name ) );
			}
			$selects .= '</select></label>';
		}
		if ( $times ) {
			$selects .= '<label class="ellada-rfilters__field"><span>Χρόνος</span><select name="xronos"><option value="">Οποιοσδήποτε</option>';
			foreach ( $times as $limit => $label ) {
				$selects .= sprintf( '<option value="%d"%s>%s</option>', $limit, selected( $f['xronos'], $limit, false ), esc_html( $label ) );
			}
			$selects .= '</select></label>';
		}

		if ( $selects ) {
			$html .= sprintf( '<form class="ellada-rfilters__form" action="%s" method="get" aria-label="Φίλτρα συνταγών">', esc_url( $base ) );
			$html .= $selects;
			$html .= '<noscript><button type="submit" class="ellada-rfilters__btn">Εφαρμογή</button></noscript>';
			if ( $keep ) {
				$html .= sprintf( '<a class="ellada-rfilters__clear" href="%s">Καθαρισμός φίλτρων</a>', esc_url( $base ) );
			}
			$html .= '</form>';
			// Αλλαγή επιλογής = αποστολή (κενά πεδία δεν μπαίνουν στη διεύθυνση).
			$html .= '<script>(function(){document.querySelectorAll(".ellada-rfilters__form").forEach(function(f){if(f.dataset.on)return;f.dataset.on=1;'
				. 'f.addEventListener("change",function(){var p=new URLSearchParams();new FormData(f).forEach(function(v,k){if(v)p.append(k,v)});var q=p.toString();location.href=f.action.split("?")[0]+(q?"?"+q:"");});});})();</script>';
		}
		return $html . '</div>';
	}
);

/* ═════════════════════ [ellada_recipe_discover] ═════════════════════ */

add_shortcode(
	'ellada_recipe_discover',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'min' => 12 ), $atts, 'ellada_recipe_discover' );
		if ( ! is_post_type_archive( 'ellada_recipe' ) || is_paged() || ellada_recipe_filters_active() ) {
			return '';
		}
		if ( ellada_recipe_totals()['recipes'] < (int) $atts['min'] ) {
			return '';
		}
		$html = '';
		foreach ( array( '[ellada_season]', '[ellada_home_collections]' ) as $sc ) {
			$tag = trim( $sc, '[]' );
			if ( shortcode_exists( $tag ) ) {
				$html .= do_shortcode( $sc );
			}
		}
		return $html ? '<div class="ellada-discover">' . $html . '</div>' : '';
	}
);

/* ═════════════════════ Προεπισκόπηση draft template ═════════════════════ */

add_filter(
	'elementor/theme/get_location_templates/template_id',
	function ( $template_id, $location = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preview = isset( $_GET['ellada_tpl'] ) ? absint( $_GET['ellada_tpl'] ) : 0;
		if ( ! $preview || ! is_user_logged_in() || ! current_user_can( 'edit_pages' ) ) {
			return $template_id;
		}
		if ( 'elementor_library' !== get_post_type( $preview ) || 'archive' !== $location ) {
			return $template_id;
		}
		// Το draft δηλώνει ποιο template αντικαθιστά (meta _ellada_preview_of).
		return (int) get_post_meta( $preview, '_ellada_preview_of', true ) === (int) $template_id ? $preview : $template_id;
	},
	10,
	2
);
