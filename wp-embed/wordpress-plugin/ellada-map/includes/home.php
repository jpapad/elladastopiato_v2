<?php
/**
 * Ενότητες της αρχικής σελίδας (στο ύφος του hero).
 *
 *   [ellada_section_head num="02" eyebrow="" title="" more_url="" more_label="" tone="light|dark"]
 *   [ellada_map_intro]          Αριστερή στήλη της ενότητας του χάρτη (τίτλος, κείμενο, αριθμοί)
 *   [ellada_featured]           «Η συνταγή της εβδομάδας» σε μεγάλη κάρτα
 *   [ellada_region_index]       Και οι 13 περιφέρειες: όσες έχουν περιεχόμενο ανάβουν, οι άλλες «Σύντομα»
 *
 * Η συνταγή της εβδομάδας ορίζεται από τη σελίδα επεξεργασίας της συνταγής (κουτί «Δημοσίευση»).
 * Αν δεν έχει οριστεί, μπαίνει η πιο πρόσφατη συνταγή με φωτογραφία.
 */

defined( 'ABSPATH' ) || exit;

/* ═════════════════════ Συνταγή της εβδομάδας ═════════════════════ */

function ellada_featured_recipe_id() {
	$id = (int) get_option( 'ellada_featured_recipe' );
	if ( $id && 'ellada_recipe' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	foreach ( array( true, false ) as $need_photo ) {
		$args = array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);
		if ( $need_photo ) {
			$args['meta_key'] = '_thumbnail_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}
		$ids = get_posts( $args );
		if ( $ids ) {
			return (int) $ids[0];
		}
	}
	return 0;
}

/* Επιλογή στο κουτί «Δημοσίευση» της συνταγής. */
add_action(
	'post_submitbox_misc_actions',
	function ( $post ) {
		if ( 'ellada_recipe' !== $post->post_type ) {
			return;
		}
		wp_nonce_field( 'ellada_featured', 'ellada_featured_nonce' );
		printf(
			'<div class="misc-pub-section"><label><input type="checkbox" name="ellada_featured_recipe" value="1" %s> ★ Συνταγή της εβδομάδας (στην αρχική)</label></div>',
			checked( (int) get_option( 'ellada_featured_recipe' ), $post->ID, false )
		);
	}
);
add_action(
	'save_post_ellada_recipe',
	function ( $post_id ) {
		if ( ! isset( $_POST['ellada_featured_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ellada_featured_nonce'] ), 'ellada_featured' ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$current = (int) get_option( 'ellada_featured_recipe' );
		if ( ! empty( $_POST['ellada_featured_recipe'] ) ) {
			update_option( 'ellada_featured_recipe', $post_id, false );
		} elseif ( $current === $post_id ) {
			delete_option( 'ellada_featured_recipe' );
		}
		do_action( 'litespeed_purge_all' );
	}
);

/* ═════════════════════ Κεφαλίδα ενότητας ═════════════════════ */

add_shortcode(
	'ellada_section_head',
	function ( $atts ) {
		$a = shortcode_atts(
			array(
				'num'        => '',
				'eyebrow'    => '',
				'title'      => '',
				'more_url'   => '',
				'more_label' => '',
				'tone'       => 'light',
			),
			$atts,
			'ellada_section_head'
		);
		ellada_front_style();
		$more = '';
		if ( $a['more_url'] && $a['more_label'] ) {
			$url  = 0 === strpos( $a['more_url'], 'http' ) ? $a['more_url'] : home_url( $a['more_url'] );
			$more = sprintf( '<a class="ellada-sh__more" href="%s">%s <span aria-hidden="true">→</span></a>', esc_url( $url ), esc_html( $a['more_label'] ) );
		}
		return sprintf(
			'<header class="ellada-sh%s">%s<div class="ellada-sh__text">%s<h2 class="ellada-sh__title">%s</h2></div>%s</header>',
			'dark' === $a['tone'] ? ' is-dark' : '',
			$a['num'] ? '<span class="ellada-sh__num">' . esc_html( $a['num'] ) . '</span>' : '',
			$a['eyebrow'] ? '<p class="ellada-sh__eyebrow">' . esc_html( $a['eyebrow'] ) . '</p>' : '',
			wp_kses( $a['title'], array( 'strong' => array(), 'br' => array() ) ),
			$more
		);
	}
);

/* ═════════════════════ Ενότητα χάρτη: εισαγωγή ═════════════════════ */

function ellada_home_counts() {
	$c = get_transient( 'ellada_home_counts' );
	if ( is_array( $c ) ) {
		return $c;
	}
	$recipes = (int) wp_count_posts( 'ellada_recipe' )->publish;
	$regions = 0;
	foreach ( ellada_map_region_names() as $name ) {
		if ( ellada_region_recipe_count( $name ) ) {
			$regions++;
		}
	}
	$c = array(
		'recipes' => $recipes,
		'regions' => $regions,
		'total'   => count( ellada_map_region_names() ),
	);
	set_transient( 'ellada_home_counts', $c, HOUR_IN_SECONDS );
	return $c;
}
function ellada_home_counts_flush( $post_id ) {
	if ( in_array( get_post_type( $post_id ), array( 'ellada_recipe', 'ellada_location' ), true ) ) {
		delete_transient( 'ellada_home_counts' );
	}
}
add_action( 'save_post', 'ellada_home_counts_flush' );
add_action( 'trashed_post', 'ellada_home_counts_flush' );

add_shortcode(
	'ellada_map_intro',
	function ( $atts ) {
		$a = shortcode_atts(
			array(
				'num'     => '01',
				'eyebrow' => 'Ο χάρτης',
				'title'   => 'Διαλέξτε<br><strong>περιφέρεια</strong>',
				'text'    => 'Κάθε περιφέρεια έχει τις δικές της γεύσεις. Πατήστε πάνω στον χάρτη, κατεβείτε σε νομούς και δήμους και βρείτε τις συνταγές, τα προϊόντα και τους ανθρώπους κάθε τόπου.',
			),
			$atts,
			'ellada_map_intro'
		);
		ellada_front_style();
		$c = ellada_home_counts();
		return sprintf(
			'<div class="ellada-mapintro"><span class="ellada-sh__num">%s</span><p class="ellada-sh__eyebrow">%s</p><h2 class="ellada-mapintro__title">%s</h2><p class="ellada-mapintro__text">%s</p><dl class="ellada-mapintro__stats"><div><dt>Συνταγές</dt><dd>%d</dd></div><div><dt>Περιφέρειες με περιεχόμενο</dt><dd>%d<span>/%d</span></dd></div></dl></div>',
			esc_html( $a['num'] ),
			esc_html( $a['eyebrow'] ),
			wp_kses( $a['title'], array( 'strong' => array(), 'br' => array() ) ),
			esc_html( $a['text'] ),
			$c['recipes'],
			$c['regions'],
			$c['total']
		);
	}
);

/* ═════════════════════ Συνταγή της εβδομάδας: κάρτα ═════════════════════ */

add_shortcode(
	'ellada_featured',
	function ( $atts ) {
		$a  = shortcode_atts( array( 'eyebrow' => 'Η συνταγή της εβδομάδας' ), $atts, 'ellada_featured' );
		$id = ellada_featured_recipe_id();
		if ( ! $id ) {
			return '';
		}
		ellada_front_style();
		$img   = get_the_post_thumbnail( $id, 'large', array( 'class' => 'ellada-feat__img', 'loading' => 'lazy', 'sizes' => '(max-width: 860px) 100vw, 640px' ) );
		$loc   = (int) get_post_meta( $id, 'location', true );
		$cats  = get_the_terms( $id, 'recipe_category' );
		$place = array_filter( array( $loc ? get_the_title( $loc ) : '', is_array( $cats ) && $cats ? $cats[0]->name : '' ) );
		$total = (int) get_post_meta( $id, 'prep_time', true ) + (int) get_post_meta( $id, 'cook_time', true );
		$serv  = (int) get_post_meta( $id, 'servings', true );
		$diffs = array( 'easy' => 'Εύκολη', 'medium' => 'Μέτρια', 'hard' => 'Απαιτητική' );
		$diff  = ellada_select_value( get_post_meta( $id, 'difficulty', true ) );
		$facts = array_filter(
			array(
				'Χρόνος'   => ellada_minutes( $total ),
				'Μερίδες'  => $serv ? (string) $serv : '',
				'Δυσκολία' => isset( $diffs[ $diff ] ) ? $diffs[ $diff ] : '',
			)
		);
		$dl = '';
		foreach ( $facts as $k => $v ) {
			$dl .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
		}
		$desc = trim( (string) get_post_meta( $id, 'short_description', true ) );
		$url  = get_permalink( $id );
		return sprintf(
			'<article class="ellada-feat"><a class="ellada-feat__media" href="%1$s" tabindex="-1" aria-hidden="true">%2$s</a><div class="ellada-feat__body"><p class="ellada-feat__eyebrow">%3$s</p>%4$s<h3 class="ellada-feat__title"><a href="%1$s">%5$s</a></h3>%6$s%7$s<a class="ellada-feat__btn" href="%1$s">Δείτε τη συνταγή <span aria-hidden="true">→</span></a></div></article>',
			esc_url( $url ),
			$img ? $img : '<span class="ellada-card__placeholder"></span>',
			esc_html( $a['eyebrow'] ),
			$place ? '<p class="ellada-feat__place">' . esc_html( implode( ' · ', $place ) ) . '</p>' : '',
			esc_html( get_the_title( $id ) ),
			$desc ? '<p class="ellada-feat__desc">' . esc_html( $desc ) . '</p>' : '',
			$dl ? '<dl class="ellada-feat__facts">' . $dl . '</dl>' : ''
		);
	}
);

/* ═════════════════════ Ευρετήριο περιφερειών ═════════════════════ */

function ellada_region_recipe_count( $region ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_ellada_region', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $region, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return (int) $q->found_posts;
}

/** Η σελίδα (Τοποθεσία) μιας περιφέρειας, αν υπάρχει. */
function ellada_region_page( $region ) {
	$ids = get_posts(
		array(
			'post_type'      => 'ellada_location',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array( 'key' => 'map_level', 'value' => 'region' ),
				array( 'key' => 'geo_region', 'value' => $region ),
			),
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

add_shortcode(
	'ellada_region_index',
	function () {
		ellada_front_style();
		$html = '<ol class="ellada-rindex">';
		$i    = 0;
		foreach ( ellada_map_region_names() as $name ) {
			$i++;
			$n    = ellada_region_recipe_count( $name );
			$page = ellada_region_page( $name );
			$url  = $page ? get_permalink( $page ) : ( $n ? add_query_arg( 'perioxi', rawurlencode( $name ), get_post_type_archive_link( 'ellada_recipe' ) ) : '' );
			$info = $n ? sprintf( 1 === $n ? '%d συνταγή' : '%d συνταγές', $n ) : ( $page ? 'Οδηγός περιοχής' : 'Σύντομα' );
			$in   = sprintf(
				'<span class="ellada-rindex__num">%02d</span><span class="ellada-rindex__name">%s</span><span class="ellada-rindex__info">%s</span>',
				$i,
				esc_html( $name ),
				esc_html( $info )
			);
			$html .= $url
				? '<li class="is-on"><a href="' . esc_url( $url ) . '">' . $in . '<span class="ellada-rindex__arrow" aria-hidden="true">→</span></a></li>'
				: '<li class="is-off"><span class="ellada-rindex__row">' . $in . '</span></li>';
		}
		return $html . '</ol>';
	}
);
