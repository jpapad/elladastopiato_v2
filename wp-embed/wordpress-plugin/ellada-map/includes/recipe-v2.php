<?php
/**
 * Σελίδα συνταγής — νέα στοιχεία (λειτουργία «recipe_v2» στο ellada_features).
 * Πριν ανοίξει, ο διαχειριστής τα βλέπει με ?ellada_preview=1.
 *
 *   [ellada_recipe_crumbs]   Συνταγές › Κατηγορία › Συνταγή (+ BreadcrumbList για το Google)
 *   - «Ο τόπος της συνταγής»: στατικός μικρός χάρτης (public/regions-mini.json) αντί για τον διαδραστικό
 *   - «Η ιστορία της συνταγής» (ACF story) πριν από την Εκτέλεση
 *   - «Παραλλαγές & συνοδευτικά» (ACF variations, μία ανά γραμμή) μετά τα Μυστικά
 *   - «Μαγειρεύω τώρα» (η οθόνη μένει αναμμένη) και «Λίστα για ψώνια» — στο public/recipe.js
 *   - στο κινητό η φωτογραφία πρώτη
 *   - schema.org suitableForDiet από τη «Διατροφή»
 */

defined( 'ABSPATH' ) || exit;

function ellada_recipe_v2() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'recipe_v2' );
}

/** Η περιφέρεια της συνταγής (όνομα όπως στον χάρτη). */
function ellada_recipe_region( $id ) {
	$region = (string) get_post_meta( $id, ELLADA_AREA_KEYS['region'], true );
	if ( ! $region && function_exists( 'ellada_location_area' ) ) {
		$loc    = (int) get_post_meta( $id, 'location', true );
		$area   = $loc ? ellada_location_area( $loc ) : array();
		$region = isset( $area['region'] ) ? (string) $area['region'] : '';
	}
	return $region;
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular( 'ellada_recipe' ) && ellada_recipe_v2() ) {
			$classes[] = 'ellada-recipe-v2';
		}
		return $classes;
	}
);

/* ═════════════════════ Διαδρομή (breadcrumbs) ═════════════════════ */

/** @return array<int, array{0:string,1:string}> [όνομα, url] — το τελευταίο χωρίς url. */
function ellada_recipe_crumb_items( $id ) {
	$items = array( array( 'Συνταγές', get_post_type_archive_link( 'ellada_recipe' ) ) );
	$cats  = get_the_terms( $id, 'recipe_category' );
	if ( is_array( $cats ) && $cats ) {
		$link = get_term_link( $cats[0] );
		if ( ! is_wp_error( $link ) ) {
			$items[] = array( $cats[0]->name, $link );
		}
	}
	$items[] = array( get_the_title( $id ), '' );
	return $items;
}

add_shortcode(
	'ellada_recipe_crumbs',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( ! ellada_recipe_v2() || 'ellada_recipe' !== get_post_type( $id ) ) {
			return '';
		}
		ellada_front_style();
		$out = array();
		foreach ( ellada_recipe_crumb_items( $id ) as $c ) {
			$out[] = $c[1]
				? '<a href="' . esc_url( $c[1] ) . '">' . esc_html( $c[0] ) . '</a>'
				: '<span aria-current="page">' . esc_html( $c[0] ) . '</span>';
		}
		return '<nav class="ellada-rcrumbs" aria-label="Διαδρομή">' . implode( '<span class="ellada-rcrumbs__sep" aria-hidden="true">›</span>', $out ) . '</nav>';
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'ellada_recipe' ) || ! ellada_recipe_v2() ) {
			return;
		}
		$list = array();
		$pos  = 0;
		foreach ( ellada_recipe_crumb_items( get_queried_object_id() ) as $c ) {
			$list[] = array_filter(
				array(
					'@type'    => 'ListItem',
					'position' => ++$pos,
					'name'     => $c[0],
					'item'     => $c[1] ? $c[1] : get_permalink( get_queried_object_id() ),
				)
			);
		}
		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode(
				array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list ),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
			)
		);
	},
	20
);

/* ═════════════════════ schema.org: διατροφή ═════════════════════ */

add_filter(
	'ellada_recipe_schema',
	function ( $data, $id ) {
		if ( ! ellada_recipe_v2() ) {
			return $data;
		}
		$map   = array(
			'vegan'          => 'https://schema.org/VeganDiet',
			'chortofagiko'   => 'https://schema.org/VegetarianDiet',
			'choris-glouteni' => 'https://schema.org/GlutenFreeDiet',
		);
		$diets = get_the_terms( $id, 'recipe_diet' );
		$out   = array();
		foreach ( is_array( $diets ) ? $diets : array() as $t ) {
			if ( isset( $map[ $t->slug ] ) ) {
				$out[] = $map[ $t->slug ];
			}
		}
		if ( in_array( $map['vegan'], $out, true ) && ! in_array( $map['chortofagiko'], $out, true ) ) {
			$out[] = $map['chortofagiko'];
		}
		if ( $out ) {
			$data['suitableForDiet'] = count( $out ) > 1 ? $out : $out[0];
		}
		return $data;
	},
	10,
	2
);

/* ═════════════════════ Ο τόπος της συνταγής (στατικός χάρτης) ═════════════════════ */

function ellada_regions_mini() {
	static $data = null;
	if ( null === $data ) {
		$file = ELLADA_MAP_DIR . 'public/regions-mini.json';
		$data = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	return is_array( $data ) ? $data : array();
}

function ellada_recipe_place_html( $id ) {
	$region = ellada_recipe_region( $id );
	$mini   = ellada_regions_mini();
	if ( ! $region || empty( $mini['regions'][ $region ] ) ) {
		return '';
	}
	$paths = '';
	foreach ( $mini['regions'] as $name => $d ) {
		if ( $name !== $region ) {
			$paths .= '<path d="' . esc_attr( $d ) . '"/>';
		}
	}
	$svg = sprintf(
		'<svg class="ellada-rplace__map" viewBox="%1$s" role="img" aria-label="%2$s"><g class="is-other">%3$s</g><path class="is-on" d="%4$s"/></svg>',
		esc_attr( $mini['viewBox'] ),
		esc_attr( 'Χάρτης της Ελλάδας: ' . $region ),
		$paths,
		esc_attr( $mini['regions'][ $region ] )
	);

	$loc   = (int) get_post_meta( $id, 'location', true );
	$place = $loc && 'publish' === get_post_status( $loc ) ? get_the_title( $loc ) : '';
	$page  = function_exists( 'ellada_region_page' ) ? ellada_region_page( $region ) : 0;
	$n     = function_exists( 'ellada_region_recipe_count' ) ? ellada_region_recipe_count( $region ) : 0;
	$links = '';
	if ( $page ) {
		$links .= sprintf( '<a class="ellada-rplace__link" href="%s">Ο οδηγός της περιοχής <span aria-hidden="true">→</span></a>', esc_url( get_permalink( $page ) ) );
	}
	if ( $n > 1 ) {
		$links .= sprintf(
			'<a class="ellada-rplace__link" href="%s">%s <span aria-hidden="true">→</span></a>',
			esc_url( add_query_arg( 'perioxi', rawurlencode( $region ), get_post_type_archive_link( 'ellada_recipe' ) ) ),
			esc_html( sprintf( 'Όλες οι συνταγές της περιοχής (%d)', $n ) )
		);
	}

	return sprintf(
		'<section class="ellada-rplace"><div class="ellada-rplace__text"><p class="ellada-sh__eyebrow">Ο τόπος της συνταγής</p><h2 class="ellada-rplace__title">%1$s</h2>%2$s<div class="ellada-rplace__links">%3$s</div></div>%4$s</section>',
		esc_html( $region ),
		$place && $place !== $region ? '<p class="ellada-rplace__place">' . esc_html( $place ) . '</p>' : '',
		$links,
		$svg
	);
}

/* ═════════════════════ Ιστορία και παραλλαγές ═════════════════════ */

function ellada_recipe_story_html( $id ) {
	$story = trim( (string) get_post_meta( $id, 'story', true ) );
	if ( ! $story ) {
		return '';
	}
	$paras = array_filter( array_map( 'trim', preg_split( "/\n\s*\n/", $story ) ) );
	return '<section class="ellada-rstory"><h2 class="ellada-h">Η ιστορία της συνταγής</h2>'
		. implode( '', array_map( fn( $p ) => '<p>' . nl2br( esc_html( $p ) ) . '</p>', $paras ) ) . '</section>';
}

function ellada_recipe_variations_html( $id ) {
	$lines = array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) get_post_meta( $id, 'variations', true ) ) ) );
	if ( ! $lines ) {
		return '';
	}
	return '<section class="ellada-rvars"><h2 class="ellada-h">Παραλλαγές &amp; συνοδευτικά</h2><ul>'
		. implode( '', array_map( fn( $l ) => '<li>' . esc_html( ltrim( $l, "-•· \t" ) ) . '</li>', $lines ) ) . '</ul></section>';
}

/* ═════════════════════ Ένταξη στα υπάρχοντα shortcodes ═════════════════════ */

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( ! in_array( $tag, array( 'ellada_recipe_steps', 'ellada_recipe_ingredients' ), true ) || ! ellada_recipe_v2() ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		if ( 'ellada_recipe_steps' === $tag ) {
			return ellada_recipe_story_html( $id ) . $output;
		}
		// Υλικά: κουμπί «Λίστα για ψώνια» πριν κλείσει η ενότητα.
		$btn = '<button type="button" class="ellada-ingredients__shop" data-ellada-shop data-title="' . esc_attr( get_the_title( $id ) ) . '">'
			. '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14l-1.5 12h-11L5 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>'
			. '<span>Λίστα για ψώνια</span></button>';
		$pos = strrpos( $output, '</section>' );
		return false === $pos ? $output : substr_replace( $output, $btn . '</section>', $pos, strlen( '</section>' ) );
	},
	20,
	3
);
