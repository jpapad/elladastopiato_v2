<?php
/**
 * Footer του site, ως shortcodes για το template «Footer» του Elementor.
 *
 *   [ellada_footer_links]   Στήλες: Εξερεύνησε · Περιφέρειες · Συνταγές (κατηγορίες/διατροφές με συνταγές)
 *   [ellada_footer_legal]   Επικοινωνία · Απόρρητο & cookies · Όροι χρήσης (μόνο όσες σελίδες είναι δημοσιευμένες)
 *
 * Όλα βγαίνουν από τα δεδομένα του site: νέα κατηγορία/σελίδα εμφανίζεται μόνη της.
 * Το HTML κρατιέται σε transient και ανανεώνεται όταν αλλάζει σελίδα, τοποθεσία, συνταγή ή όρος.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_FOOTER_CACHE = 'ellada_footer_html_v1';

/** Όσοι επεξεργάζονται σελίδες βλέπουν και τα drafts (για προεπισκόπηση) — χωρίς cache. */
function ellada_footer_editor_view() {
	return is_user_logged_in() && current_user_can( 'edit_pages' );
}

/** Δημοσιευμένη σελίδα από slug (ή 0). Για editors μετράνε και τα drafts. */
function ellada_footer_page( $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page ) {
		return 0;
	}
	$ok = 'publish' === $page->post_status || ( ellada_footer_editor_view() && in_array( $page->post_status, array( 'draft', 'pending', 'future' ), true ) );
	return $ok ? (int) $page->ID : 0;
}

function ellada_footer_link( $url, $label ) {
	return '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
}

function ellada_footer_col( $title, $items, $extra_class = '' ) {
	if ( ! $items ) {
		return '';
	}
	return '<nav class="ellada-footer__col' . ( $extra_class ? ' ' . $extra_class : '' ) . '" aria-label="' . esc_attr( $title ) . '">'
		. '<h2 class="ellada-footer__title">' . esc_html( $title ) . '</h2>'
		. '<ul class="ellada-footer__list">' . implode( '', $items ) . '</ul></nav>';
}

function ellada_footer_links_html() {
	/* Εξερεύνησε */
	$explore = array();
	// Ο χάρτης είναι στην αρχική (η σελίδα «Χάρτης» είναι ανενεργή από 22/11).
	$explore[] = ellada_footer_link( home_url( '/#xartis' ), 'Χάρτης' );
	$guide = ellada_footer_page( 'odigos' );
	if ( $guide && ( ! function_exists( 'ellada_feature' ) || ellada_feature( 'guide' ) ) ) {
		$explore[] = ellada_footer_link( get_permalink( $guide ), 'Ταξιδιωτικός οδηγός' );
	}
	foreach ( array( 'ellada_recipe' => 'Συνταγές', 'ellada_collection' => 'Συλλογές' ) as $type => $label ) {
		$url = post_type_exists( $type ) ? get_post_type_archive_link( $type ) : '';
		if ( $url ) {
			$explore[] = ellada_footer_link( $url, $label );
		}
	}

	/* Περιφέρειες: όσες έχουν σελίδα στον οδηγό */
	$regions = array();
	if ( function_exists( 'ellada_map_region_names' ) && function_exists( 'ellada_region_page' ) ) {
		foreach ( ellada_map_region_names() as $name ) {
			$page = ellada_region_page( $name );
			if ( $page ) {
				$regions[] = ellada_footer_link( get_permalink( $page ), get_the_title( $page ) );
			}
		}
	}

	/* Συνταγές: κατηγορίες και διατροφές που έχουν τουλάχιστον μία συνταγή */
	$recipes = array();
	foreach ( array( 'recipe_category', 'recipe_diet' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$recipes[] = ellada_footer_link( $link, $term->name );
			}
		}
	}

	return '<div class="ellada-footer">'
		. ellada_footer_col( 'Εξερεύνησε', $explore )
		. ellada_footer_col( 'Περιφέρειες', $regions, 'ellada-footer__col--wide' )
		. ellada_footer_col( 'Συνταγές', $recipes )
		. '</div>';
}

function ellada_footer_legal_html() {
	$items = array();
	foreach (
		array(
			'epikoinonia'        => 'Επικοινωνία',
			'politiki-aporritou' => 'Απόρρητο & cookies',
			'oroi-xrisis'        => 'Όροι χρήσης',
		) as $slug => $label
	) {
		$id = ellada_footer_page( $slug );
		if ( $id ) {
			$url     = 'publish' === get_post_status( $id ) ? get_permalink( $id ) : get_preview_post_link( $id );
			$items[] = ellada_footer_link( $url, $label );
		}
	}
	return $items ? '<nav class="ellada-footer-legal" aria-label="Πληροφορίες"><ul>' . implode( '', $items ) . '</ul></nav>' : '';
}

/** Και τα δύο κομμάτια μαζί στην cache (ένα transient για όλο το footer). */
function ellada_footer_cached( $part ) {
	if ( ellada_footer_editor_view() ) {
		return 'links' === $part ? ellada_footer_links_html() : ellada_footer_legal_html();
	}
	$cache = get_transient( ELLADA_FOOTER_CACHE );
	if ( ! is_array( $cache ) ) {
		$cache = array(
			'links' => ellada_footer_links_html(),
			'legal' => ellada_footer_legal_html(),
		);
		set_transient( ELLADA_FOOTER_CACHE, $cache, 12 * HOUR_IN_SECONDS );
	}
	return $cache[ $part ] ?? '';
}

add_shortcode(
	'ellada_footer_links',
	function () {
		ellada_front_style();
		return ellada_footer_cached( 'links' );
	}
);

add_shortcode(
	'ellada_footer_legal',
	function () {
		ellada_front_style();
		return ellada_footer_cached( 'legal' );
	}
);

function ellada_footer_flush() {
	delete_transient( ELLADA_FOOTER_CACHE );
}
add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( in_array( $post->post_type, array( 'page', 'ellada_location', 'ellada_recipe', 'ellada_collection' ), true ) ) {
			ellada_footer_flush();
		}
	},
	10,
	2
);
add_action( 'transition_post_status', 'ellada_footer_flush' );
add_action( 'deleted_post', 'ellada_footer_flush' );
add_action( 'created_term', 'ellada_footer_flush' );
add_action( 'edited_term', 'ellada_footer_flush' );
add_action( 'delete_term', 'ellada_footer_flush' );
add_action( 'set_object_terms', 'ellada_footer_flush' );
