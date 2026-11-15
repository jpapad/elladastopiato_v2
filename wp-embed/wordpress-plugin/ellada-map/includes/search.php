<?php
/**
 * Αναζήτηση του οδηγού.
 *
 *   [ellada_search]           Κουμπί με φακό· ανοίγει αναζήτηση σε όλη την οθόνη με αποτελέσματα καθώς πληκτρολογείτε.
 *   [ellada_search_results]   Η σελίδα αποτελεσμάτων (για το template «Αποτελέσματα αναζήτησης» του Elementor).
 *   GET /wp-json/ellada/v1/search?q=…   Τα άμεσα αποτελέσματα (συνταγές, περιοχές, συλλογές).
 *
 * Η αντιστοίχιση αγνοεί τόνους και κεφαλαία («ντακος» βρίσκει «Ντάκος») και δέχεται και λατινικούς
 * χαρακτήρες («dakos»). Ψάχνει στον τίτλο και, για τις συνταγές, και στην περιοχή τους.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_SEARCH_TYPES = array( 'ellada_recipe', 'ellada_location', 'ellada_collection' );

/** Πεζά, χωρίς τόνους και διαλυτικά, με «σ» στη θέση του τελικού «ς». */
function ellada_search_norm( $text ) {
	static $map = array(
		'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ϊ' => 'ι', 'ΐ' => 'ι', 'ό' => 'ο',
		'ύ' => 'υ', 'ϋ' => 'υ', 'ΰ' => 'υ', 'ώ' => 'ω', 'ς' => 'σ',
	);
	$text = strtr( mb_strtolower( wp_strip_all_tags( (string) $text ), 'UTF-8' ), $map );
	return trim( preg_replace( '/\s+/u', ' ', remove_accents( $text ) ) );
}

/**
 * Λατινική γραφή ενός τίτλου, και με τις συνηθισμένες εναλλακτικές στην αρχή των λέξεων
 * («Ντάκος» → «ntakos dakos», «Γκόγκες» → «gkogkes gogkes»), για όποιον γράφει με λατινικά.
 */
function ellada_search_latin( $text ) {
	$l   = ellada_search_norm( ellada_greeklish( $text ) );
	$alt = preg_replace( array( '/(^|\s)nt/u', '/(^|\s)gk/u' ), array( '$1d', '$1g' ), $l );
	return $alt === $l ? $l : $l . ' ' . $alt;
}

/** Ευρετήριο τίτλων (μικρό, στη μνήμη cache)· ξαναχτίζεται όταν αλλάζει κάτι. */
function ellada_search_index() {
	$index = get_transient( 'ellada_search_index' );
	if ( is_array( $index ) ) {
		return $index;
	}
	$index = array();
	$ids   = get_posts(
		array(
			'post_type'        => ELLADA_SEARCH_TYPES,
			'post_status'      => 'publish',
			'numberposts'      => 3000,
			'fields'           => 'ids',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		$title = get_the_title( $id );
		$place = '';
		if ( 'ellada_recipe' === get_post_type( $id ) ) {
			$loc   = (int) get_post_meta( $id, 'location', true );
			$place = trim( ( $loc ? get_the_title( $loc ) : '' ) . ' ' . get_post_meta( $id, '_ellada_region', true ) . ' ' . get_post_meta( $id, '_ellada_prefecture', true ) );
		}
		$t       = ellada_search_norm( $title );
		$p       = ellada_search_norm( $place );
		$index[] = array(
			'id'    => (int) $id,
			'type'  => get_post_type( $id ),
			't'     => $t,
			'tl'    => ellada_search_latin( $title ),
			'p'     => $p,
			'pl'    => ellada_search_latin( $place ),
		);
	}
	set_transient( 'ellada_search_index', $index, DAY_IN_SECONDS );
	return $index;
}

function ellada_search_flush( $post_id = 0 ) {
	if ( ! $post_id || in_array( get_post_type( $post_id ), ELLADA_SEARCH_TYPES, true ) ) {
		delete_transient( 'ellada_search_index' );
	}
}
add_action( 'save_post', 'ellada_search_flush' );
add_action( 'deleted_post', 'ellada_search_flush' );
add_action( 'trashed_post', 'ellada_search_flush' );
add_action( 'acf/save_post', 'ellada_search_flush', 20 );

/**
 * Τα ids που ταιριάζουν, ανά τύπο, με τα πιο σχετικά πρώτα.
 * Κάθε λέξη της αναζήτησης πρέπει να υπάρχει στον τίτλο ή στην περιοχή.
 *
 * @return array<string,int[]>
 */
function ellada_search_ids( $query ) {
	$q   = ellada_search_norm( $query );
	$out = array_fill_keys( ELLADA_SEARCH_TYPES, array() );
	if ( mb_strlen( $q ) < 2 ) {
		return $out;
	}
	$words  = array_filter( explode( ' ', $q ), 'strlen' );
	$scored = array();
	foreach ( ellada_search_index() as $row ) {
		$score = 0;
		foreach ( $words as $w ) {
			$in_title = false !== mb_strpos( $row['t'], $w ) || false !== mb_strpos( $row['tl'], $w );
			$in_place = false !== mb_strpos( $row['p'], $w ) || false !== mb_strpos( $row['pl'], $w );
			if ( ! $in_title && ! $in_place ) {
				continue 2;
			}
			$score += $in_title ? 10 : 3;
			// Λέξη του τίτλου που ξεκινά με ό,τι γράφτηκε: πιο σχετικό.
			if ( preg_match( '/(^|\s)' . preg_quote( $w, '/' ) . '/u', $row['t'] . ' ' . $row['tl'] ) ) {
				$score += 5;
			}
		}
		if ( 0 === mb_strpos( $row['t'], $q ) || 0 === mb_strpos( $row['tl'], $q ) ) {
			$score += 20;
		}
		$scored[] = array( $score, $row );
	}
	// Σταθερή ταξινόμηση: ίδια βαθμολογία, μένει η σειρά του ευρετηρίου (νεότερα πρώτα).
	$order = array_keys( $scored );
	usort(
		$order,
		function ( $a, $b ) use ( $scored ) {
			return $scored[ $b ][0] <=> $scored[ $a ][0] ?: $a <=> $b;
		}
	);
	foreach ( $order as $i ) {
		$out[ $scored[ $i ][1]['type'] ][] = $scored[ $i ][1]['id'];
	}
	return $out;
}

function ellada_search_recipe_count( $location_id ) {
	$mq = ellada_area_meta_query( $location_id );
	if ( ! $mq ) {
		return 0;
	}
	$q = new WP_Query(
		array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => $mq, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
	return (int) $q->found_posts;
}

function ellada_search_count_label( $n ) {
	return sprintf( 1 === $n ? '%d συνταγή' : '%d συνταγές', $n );
}

/* ═════════════════════ Άμεσα αποτελέσματα (REST) ═════════════════════ */

function ellada_search_suggest( $query ) {
	$ids  = ellada_search_ids( $query );
	$item = function ( $id, $meta, $img = '' ) {
		return array(
			'title' => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			'url'   => get_permalink( $id ),
			'meta'  => $meta,
			'img'   => $img,
		);
	};
	$recipes = array();
	foreach ( array_slice( $ids['ellada_recipe'], 0, 6 ) as $id ) {
		$loc       = (int) get_post_meta( $id, 'location', true );
		$total     = (int) get_post_meta( $id, 'prep_time', true ) + (int) get_post_meta( $id, 'cook_time', true );
		$recipes[] = $item( $id, implode( ' · ', array_filter( array( $loc ? get_the_title( $loc ) : '', ellada_minutes( $total ) ) ) ), (string) get_the_post_thumbnail_url( $id, 'thumbnail' ) );
	}
	$areas = array();
	foreach ( array_slice( $ids['ellada_location'], 0, 4 ) as $id ) {
		$n       = ellada_search_recipe_count( $id );
		$areas[] = $item( $id, $n ? ellada_search_count_label( $n ) : 'Οδηγός περιοχής' );
	}
	$collections = array();
	foreach ( array_slice( $ids['ellada_collection'], 0, 4 ) as $id ) {
		$n             = count( array_filter( (array) get_post_meta( $id, 'recipes', true ) ) );
		$collections[] = $item( $id, $n ? ellada_search_count_label( $n ) : 'Συλλογή', (string) get_the_post_thumbnail_url( $id, 'thumbnail' ) );
	}
	// Πόλεις και χωριά (ταξιδιωτικός οδηγός), όσα δεν είναι ήδη στις «Περιοχές».
	$places = array();
	if ( function_exists( 'ellada_feature' ) && ellada_feature( 'guide' ) ) {
		$seen = wp_list_pluck( $areas, 'url' );
		foreach ( ellada_guide_search_places( $query, 6 ) as $pl ) {
			if ( ! in_array( $pl['url'], $seen, true ) && count( $places ) < 5 ) {
				$places[] = $pl;
			}
		}
	}
	return array(
		'query'       => (string) $query,
		'total'       => count( $ids['ellada_recipe'] ) + count( $ids['ellada_location'] ) + count( $ids['ellada_collection'] ) + count( $places ),
		'recipes'     => $recipes,
		'areas'       => $areas,
		'places'      => $places,
		'collections' => $collections,
		'all_url'     => add_query_arg( 's', rawurlencode( (string) $query ), home_url( '/' ) ),
	);
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ellada/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => function ( $v ) {
							return mb_substr( sanitize_text_field( (string) $v ), 0, 80 );
						},
					),
				),
				'callback'            => function ( WP_REST_Request $req ) {
					$res = rest_ensure_response( ellada_search_suggest( $req['q'] ) );
					$res->header( 'Cache-Control', 'public, max-age=300' );
					return $res;
				},
			)
		);
	}
);

/* ═════════════════════ Η κύρια αναζήτηση του WordPress (?s=) ═════════════════════ */

/** Η αναζήτηση του site δείχνει τα περιεχόμενα του οδηγού, με τους ίδιους κανόνες (χωρίς τόνους). */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() ) {
			return;
		}
		$ids = array_merge( ...array_values( ellada_search_ids( $q->get( 's' ) ) ) );
		$q->set( 'post_type', ELLADA_SEARCH_TYPES );
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		$q->set( 'orderby', 'post__in' );
		$q->set( 'posts_per_page', 48 );
		$q->set( 'ellada_search', true );
	}
);
add_filter(
	'posts_search',
	function ( $sql, $q ) {
		return $q->get( 'ellada_search' ) ? '' : $sql;
	},
	10,
	2
);

/* ═════════════════════ Κουμπί και παράθυρο αναζήτησης ═════════════════════ */

function ellada_search_icon( $size = 20 ) {
	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5 21 21"/></svg>',
		(int) $size
	);
}

function ellada_search_assets() {
	ellada_front_style();
	$path = ELLADA_MAP_DIR . 'public/search.js';
	wp_enqueue_script( 'ellada-search', plugins_url( 'public/search.js', ELLADA_MAP_FILE ), array(), file_exists( $path ) ? (string) filemtime( $path ) : ELLADA_MAP_VERSION, true );
	wp_add_inline_script(
		'ellada-search',
		'window.elladaSearch=' . wp_json_encode(
			array(
				'endpoint' => rest_url( 'ellada/v1/search' ),
				'searchUrl' => home_url( '/' ),
			)
		) . ';',
		'before'
	);
}

/*
 * Το script και το παράθυρο φορτώνουν σε κάθε σελίδα (η κεφαλίδα είναι παντού). Έτσι δουλεύουν
 * και όταν το Elementor σερβίρει την κεφαλίδα από τη δική του cache, χωρίς να ξανατρέξει το shortcode.
 */
add_action( 'wp_enqueue_scripts', 'ellada_search_assets' );
add_action( 'wp_footer', 'ellada_search_overlay', 5 );

add_shortcode(
	'ellada_search',
	function () {
		return '<button type="button" class="ellada-search-btn" aria-label="Αναζήτηση" aria-haspopup="dialog" aria-controls="ellada-search" data-ellada-search-open>' . ellada_search_icon() . '</button>';
	}
);

function ellada_search_overlay() {
	?>
	<div id="ellada-search" class="ellada-search" role="dialog" aria-modal="true" aria-label="Αναζήτηση" hidden>
		<div class="ellada-search__in">
			<button type="button" class="ellada-search__close" aria-label="Κλείσιμο αναζήτησης" data-ellada-search-close>
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19"/></svg>
			</button>
			<form class="ellada-search__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<label class="ellada-search__label" for="ellada-search-input">Αναζήτηση στον οδηγό</label>
				<div class="ellada-search__row">
					<?php echo ellada_search_icon( 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- σταθερό SVG ?>
					<input id="ellada-search-input" class="ellada-search__input" type="search" name="s" placeholder="Τι θέλετε να μαγειρέψετε;" autocomplete="off" spellcheck="false" enterkeyhint="search">
				</div>
			</form>
			<p class="ellada-search__hint">Γράψτε μια συνταγή, ένα υλικό στον τίτλο, μια περιοχή ή ένα χωριό. Π.χ. <button type="button" data-ellada-search-try>ντάκος</button> <button type="button" data-ellada-search-try>Κρήτη</button> <button type="button" data-ellada-search-try>μακαρόνια</button></p>
			<div class="ellada-search__results" aria-live="polite"></div>
		</div>
	</div>
	<?php
}

/* ═════════════════════ Σελίδα αποτελεσμάτων ═════════════════════ */

add_shortcode(
	'ellada_search_results',
	function () {
		ellada_front_style();
		$query = get_search_query( false );
		if ( '' === $query && isset( $_GET['q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- απλή ανάγνωση για προεπισκόπηση
			$query = sanitize_text_field( wp_unslash( $_GET['q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$ids    = ellada_search_ids( $query );
		$places = ( function_exists( 'ellada_feature' ) && ellada_feature( 'guide' ) ) ? ellada_guide_search_places( $query, 12 ) : array();
		$total  = count( $ids['ellada_recipe'] ) + count( $ids['ellada_location'] ) + count( $ids['ellada_collection'] ) + count( $places );

		ob_start();
		?>
		<div class="ellada-results">
			<p class="ellada-results__eyebrow">Αναζήτηση</p>
			<form class="ellada-results__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
				<label class="screen-reader-text" for="ellada-results-input">Αναζήτηση στον οδηγό</label>
				<input id="ellada-results-input" type="search" name="s" value="<?php echo esc_attr( $query ); ?>" placeholder="Τι θέλετε να μαγειρέψετε;">
				<button type="submit" aria-label="Αναζήτηση"><?php echo ellada_search_icon( 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</form>
			<?php if ( '' === trim( $query ) ) : ?>
				<p class="ellada-results__count">Γράψτε τι ψάχνετε: μια συνταγή ή μια περιοχή.</p>
			<?php elseif ( ! $total ) : ?>
				<p class="ellada-results__count">Δεν βρέθηκε κάτι για «<?php echo esc_html( $query ); ?>». Δοκιμάστε μια άλλη λέξη ή <a href="<?php echo esc_url( home_url( '/xartis/' ) ); ?>">ανοίξτε τον χάρτη</a>.</p>
			<?php else : ?>
				<p class="ellada-results__count"><?php echo esc_html( sprintf( 1 === $total ? '1 αποτέλεσμα για «%2$s»' : '%1$d αποτελέσματα για «%2$s»', $total, $query ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		if ( $ids['ellada_location'] ) {
			echo '<div class="ellada-block"><h2 class="ellada-h">Περιοχές</h2><div class="ellada-regions">';
			foreach ( $ids['ellada_location'] as $id ) {
				$n = ellada_search_recipe_count( $id );
				printf(
					'<a class="ellada-region" href="%s"><span class="ellada-region__name">%s</span><span class="ellada-region__count">%s</span></a>',
					esc_url( get_permalink( $id ) ),
					esc_html( get_the_title( $id ) ),
					esc_html( $n ? ellada_search_count_label( $n ) : 'Οδηγός περιοχής' )
				);
			}
			echo '</div></div>';
		}
		if ( $places ) {
			echo '<div class="ellada-block"><h2 class="ellada-h">Πόλεις και χωριά</h2><ul class="ellada-gchildren">';
			foreach ( $places as $pl ) {
				printf(
					'<li><a href="%s"><span class="ellada-gchildren__name">%s</span><span class="ellada-gchildren__meta">%s</span><span class="ellada-gchildren__arrow" aria-hidden="true">→</span></a></li>',
					esc_url( $pl['url'] ),
					esc_html( $pl['title'] ),
					esc_html( $pl['meta'] )
				);
			}
			echo '</ul></div>';
		}
		foreach ( array( 'ellada_recipe' => array( 'Συνταγές', 'ellada_recipe_card' ), 'ellada_collection' => array( 'Συλλογές', 'ellada_collection_card' ) ) as $type => $conf ) {
			if ( ! $ids[ $type ] ) {
				continue;
			}
			echo '<div class="ellada-block"><h2 class="ellada-h">' . esc_html( $conf[0] ) . '</h2><div class="ellada-cards">';
			foreach ( array_slice( $ids[ $type ], 0, 48 ) as $id ) {
				echo call_user_func( $conf[1], $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- οι κάρτες κάνουν escape
			}
			echo '</div></div>';
		}
		return ob_get_clean();
	}
);
