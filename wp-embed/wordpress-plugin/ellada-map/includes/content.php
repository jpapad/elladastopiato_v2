<?php
/**
 * Περιεχόμενο του οδηγού: Συνταγές, Συλλογές, Τοποθεσίες, Τοπικά Σημεία.
 *
 * - Κάθε συνταγή και κάθε τοπικό σημείο αποθηκεύει σε ποια περιφέρεια / νομό / δήμο ανήκει
 *   (κρυφά meta _ellada_region, _ellada_prefecture, _ellada_municipality), ώστε η σελίδα
 *   μιας περιφέρειας να δείχνει και ό,τι υπάρχει στους νομούς και τους δήμους της.
 * - Ερωτήματα για τα templates (συνταγές περιοχής, συλλογής, σχετικές).
 * - Δομημένα δεδομένα Recipe (schema.org) για το Google.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_MAP_CONTENT_VERSION = 1;
const ELLADA_AREA_KEYS           = array(
	'region'       => '_ellada_region',
	'prefecture'   => '_ellada_prefecture',
	'municipality' => '_ellada_municipality',
);

/* ═════════════════════ Περιοχή κάθε εγγραφής ═════════════════════ */

/** Η περιοχή που περιγράφει μια Τοποθεσία, από τα πεδία geo_* του ACF. */
function ellada_location_area( $location_id ) {
	$level = (string) get_post_meta( $location_id, 'map_level', true );
	$area  = array(
		'level'        => $level,
		'region'       => (string) get_post_meta( $location_id, 'geo_region', true ),
		'prefecture'   => '',
		'municipality' => '',
	);
	if ( in_array( $level, array( 'prefecture', 'municipality' ), true ) ) {
		$area['prefecture'] = (string) get_post_meta( $location_id, 'geo_prefecture', true );
	}
	if ( 'municipality' === $level ) {
		$area['municipality'] = (string) get_post_meta( $location_id, 'geo_municipality', true );
	}
	// Ο νομός ορίζει μονοσήμαντα την περιφέρεια, αν λείπει.
	if ( '' === $area['region'] && $area['prefecture'] ) {
		$map            = ellada_map_prefecture_regions();
		$area['region'] = isset( $map[ $area['prefecture'] ] ) ? $map[ $area['prefecture'] ] : '';
	}
	return $area;
}

function ellada_store_area( $post_id, $area ) {
	foreach ( ELLADA_AREA_KEYS as $k => $meta ) {
		$v = is_array( $area ) && ! empty( $area[ $k ] ) ? $area[ $k ] : '';
		if ( '' === $v ) {
			delete_post_meta( $post_id, $meta );
		} else {
			update_post_meta( $post_id, $meta, $v );
		}
	}
}

/** Συνταγή: η περιοχή της προκύπτει από την Τοποθεσία που έχει επιλεγεί. */
function ellada_sync_recipe_area( $recipe_id ) {
	$loc = (int) get_post_meta( $recipe_id, 'location', true );
	ellada_store_area( $recipe_id, $loc && 'ellada_location' === get_post_type( $loc ) ? ellada_location_area( $loc ) : null );
}

/** Τοπικό σημείο: η περιοχή προκύπτει από τις συντεταγμένες. */
function ellada_sync_point_area( $point_id ) {
	ellada_store_area( $point_id, ellada_map_locate_area( get_post_meta( $point_id, 'lat', true ), get_post_meta( $point_id, 'lng', true ) ) );
}

/** Τοποθεσία: όταν αλλάζει η περιοχή της, ενημερώνονται οι συνταγές που δείχνουν σε αυτή. */
function ellada_sync_location_recipes( $location_id ) {
	$ids = get_posts(
		array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => 'location', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $location_id, // phpcs:ignore WordPress.DB.SlowDBQuery
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		ellada_sync_recipe_area( $id );
	}
}

/** Μετά την αποθήκευση των πεδίων ACF (φόρμα διαχειριστικού). */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			return;
		}
		switch ( get_post_type( $post_id ) ) {
			case 'ellada_recipe':
				ellada_sync_recipe_area( $post_id );
				break;
			case 'ellada_point':
				ellada_sync_point_area( $post_id );
				break;
			case 'ellada_location':
				ellada_sync_location_recipes( $post_id );
				break;
		}
	},
	20
);

/** Αλλαγές πεδίων εκτός φόρμας (REST, εισαγωγές, update_field). */
add_action(
	'updated_post_meta',
	'ellada_area_meta_changed',
	10,
	3
);
add_action(
	'added_post_meta',
	'ellada_area_meta_changed',
	10,
	3
);
function ellada_area_meta_changed( $meta_id, $post_id, $meta_key ) {
	static $running = false;
	if ( $running ) {
		return;
	}
	$running = true;
	$type    = get_post_type( $post_id );
	if ( 'ellada_recipe' === $type && 'location' === $meta_key ) {
		ellada_sync_recipe_area( $post_id );
	} elseif ( 'ellada_point' === $type && in_array( $meta_key, array( 'lat', 'lng' ), true ) ) {
		ellada_sync_point_area( $post_id );
	} elseif ( 'ellada_location' === $type && in_array( $meta_key, array( 'map_level', 'geo_region', 'geo_prefecture', 'geo_municipality' ), true ) ) {
		ellada_sync_location_recipes( $post_id );
	}
	$running = false;
}

/** Μία φορά μετά από αναβάθμιση: συμπλήρωση της περιοχής σε όσες εγγραφές υπάρχουν ήδη. */
add_action(
	'admin_init',
	function () {
		if ( (int) get_option( 'ellada_map_content_version' ) >= ELLADA_MAP_CONTENT_VERSION ) {
			return;
		}
		ellada_resync_all_areas();
		update_option( 'ellada_map_content_version', ELLADA_MAP_CONTENT_VERSION, false );
	}
);

function ellada_resync_all_areas() {
	$count = 0;
	foreach ( array( 'ellada_recipe', 'ellada_point' ) as $pt ) {
		$ids = get_posts(
			array(
				'post_type'      => $pt,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			'ellada_recipe' === $pt ? ellada_sync_recipe_area( $id ) : ellada_sync_point_area( $id );
			$count++;
		}
	}
	return $count;
}

/* ═════════════════════ Ερωτήματα ═════════════════════ */

/** meta_query για ό,τι ανήκει στην περιοχή μιας Τοποθεσίας (και στις υποπεριοχές της). */
function ellada_area_meta_query( $location_id ) {
	$area  = ellada_location_area( $location_id );
	$level = $area['level'] ? $area['level'] : 'region';
	$value = $area[ $level ];
	if ( '' === $value ) {
		return null;
	}
	return array(
		array(
			'key'   => ELLADA_AREA_KEYS[ $level ],
			'value' => $value,
		),
	);
}

/**
 * Ορίσματα WP_Query για τις λίστες του οδηγού.
 *
 * @param string $source area | collection | related | latest
 * @param int    $post_id Η τρέχουσα σελίδα (τοποθεσία, συλλογή ή συνταγή).
 */
function ellada_recipe_query_args( $source, $post_id, $limit = 12, $post_type = 'ellada_recipe' ) {
	$args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, (int) $limit ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	switch ( $source ) {
		case 'area':
			$mq = 'ellada_location' === get_post_type( $post_id ) ? ellada_area_meta_query( $post_id ) : null;
			if ( ! $mq ) {
				$args['post__in'] = array( 0 );
				break;
			}
			$args['meta_query'] = $mq; // phpcs:ignore WordPress.DB.SlowDBQuery
			break;

		case 'collection':
			$ids              = array_filter( array_map( 'intval', (array) get_post_meta( $post_id, 'recipes', true ) ) );
			$args['post__in'] = $ids ? $ids : array( 0 );
			$args['orderby']  = 'post__in';
			$args['posts_per_page'] = max( count( $ids ), 1 );
			break;

		case 'related':
			// Ίδια περιφέρεια, αλλιώς ίδια κατηγορία.
			$args['post__not_in'] = array( $post_id );
			$region               = (string) get_post_meta( $post_id, ELLADA_AREA_KEYS['region'], true );
			if ( $region ) {
				$args['meta_query'] = array( array( 'key' => ELLADA_AREA_KEYS['region'], 'value' => $region ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			} else {
				$terms = wp_get_post_terms( $post_id, 'recipe_category', array( 'fields' => 'ids' ) );
				if ( ! is_wp_error( $terms ) && $terms ) {
					$args['tax_query'] = array( array( 'taxonomy' => 'recipe_category', 'terms' => $terms ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
				}
			}
			$args['orderby'] = 'rand';
			break;

		default: // latest
			$args['orderby'] = 'date';
	}
	return $args;
}

/**
 * Elementor Pro: Query ID για τα widgets Posts / Loop Grid.
 *   ellada_area_recipes       Συνταγές της περιοχής (σελίδα Τοποθεσίας)
 *   ellada_area_points        Τοπικά σημεία της περιοχής (σελίδα Τοποθεσίας)
 *   ellada_collection_recipes Συνταγές της συλλογής, με τη σειρά της
 *   ellada_related_recipes    Σχετικές συνταγές (σελίδα Συνταγής)
 */
foreach ( array(
	'ellada_area_recipes'       => array( 'area', 'ellada_recipe' ),
	'ellada_area_points'        => array( 'area', 'ellada_point' ),
	'ellada_collection_recipes' => array( 'collection', 'ellada_recipe' ),
	'ellada_related_recipes'    => array( 'related', 'ellada_recipe' ),
) as $query_id => $def ) {
	add_action(
		'elementor/query/' . $query_id,
		function ( $query ) use ( $def ) {
			$args = ellada_recipe_query_args( $def[0], get_queried_object_id(), $query->get( 'posts_per_page' ) ? $query->get( 'posts_per_page' ) : 12, $def[1] );
			foreach ( $args as $k => $v ) {
				if ( 'no_found_rows' !== $k ) {
					$query->set( $k, $v );
				}
			}
		}
	);
}

/* ═════════════════════ Recipe schema (Google) ═════════════════════ */

function ellada_iso_minutes( $min ) {
	$min = (int) $min;
	return $min > 0 ? 'PT' . $min . 'M' : null;
}

/** Τα δεδομένα schema.org/Recipe μιας συνταγής (κενά πεδία παραλείπονται). */
function ellada_recipe_schema( $post_id ) {
	$prep  = (int) get_post_meta( $post_id, 'prep_time', true );
	$cook  = (int) get_post_meta( $post_id, 'cook_time', true );
	$desc  = trim( (string) get_post_meta( $post_id, 'short_description', true ) );
	$data  = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'Recipe',
		'name'          => get_the_title( $post_id ),
		'url'           => get_permalink( $post_id ),
		'description'   => $desc ? $desc : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 30 ),
		'datePublished' => get_the_date( 'c', $post_id ),
		'dateModified'  => get_the_modified_date( 'c', $post_id ),
		'author'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		),
		'recipeCuisine' => 'Ελληνική',
		'prepTime'      => ellada_iso_minutes( $prep ),
		'cookTime'      => ellada_iso_minutes( $cook ),
		'totalTime'     => ellada_iso_minutes( $prep + $cook ),
	);

	$img = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( $img ) {
		$data['image'] = array( $img );
	}
	$servings = (int) get_post_meta( $post_id, 'servings', true );
	if ( $servings ) {
		$data['recipeYield'] = sprintf( '%d μερίδες', $servings );
	}
	$cats = wp_get_post_terms( $post_id, 'recipe_category', array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $cats ) && $cats ) {
		$data['recipeCategory'] = $cats[0];
	}
	$diets = wp_get_post_terms( $post_id, 'recipe_diet', array( 'fields' => 'names' ) );
	$area  = array_filter( array( get_post_meta( $post_id, ELLADA_AREA_KEYS['region'], true ) ) );
	$kw    = array_merge( is_wp_error( $diets ) ? array() : $diets, $area );
	if ( $kw ) {
		$data['keywords'] = implode( ', ', $kw );
	}

	if ( function_exists( 'get_field' ) ) {
		$ingredients = array();
		foreach ( (array) get_field( 'ingredients', $post_id ) as $row ) {
			if ( ! is_array( $row ) || empty( $row['item'] ) ) {
				continue;
			}
			$ingredients[] = trim( implode( ' ', array_filter( array( $row['quantity'] ?? '', $row['item'], ! empty( $row['note'] ) ? '(' . $row['note'] . ')' : '' ) ) ) );
		}
		if ( $ingredients ) {
			$data['recipeIngredient'] = $ingredients;
		}
		$steps = array();
		$n     = 0;
		foreach ( (array) get_field( 'steps', $post_id ) as $row ) {
			$text = is_array( $row ) ? trim( wp_strip_all_tags( (string) ( $row['text'] ?? '' ) ) ) : '';
			if ( '' === $text ) {
				continue;
			}
			$n++;
			$step = array(
				'@type' => 'HowToStep',
				'text'  => $text,
				'url'   => get_permalink( $post_id ) . '#step-' . $n,
			);
			if ( ! empty( $row['image']['url'] ) ) {
				$step['image'] = $row['image']['url'];
			}
			$steps[] = $step;
		}
		if ( $steps ) {
			$data['recipeInstructions'] = $steps;
		}
	}

	return array_filter(
		$data,
		function ( $v ) {
			return null !== $v && '' !== $v && array() !== $v;
		}
	);
}

add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'ellada_recipe' ) ) {
			return;
		}
		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( ellada_recipe_schema( get_queried_object_id() ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG )
		);
	}
);
