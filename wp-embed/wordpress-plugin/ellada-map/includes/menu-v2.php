<?php
/**
 * Κεντρικό μενού v2 (λειτουργία «menu_v2»· προβολή χωρίς σύνδεση ?ellada_demo=menu):
 *   - κάθε στοιχείο με εικονίδιο: σπιτάκι (Αρχική), κατσαρόλα (Συνταγές), pin (Εξερευνήστε = /odigos/), φάκελος, βιβλίο, ♥, αλλιώς κουκκίδα
 *   - στον υπολογιστή μόνο εικονίδιο· στο hover/focus ανοίγει «ακορντεόν» με τη λέξη· η τρέχουσα σελίδα μένει ανοιχτή
 *   - στο κινητό (dropdown) εικονίδιο + λέξη
 *   - «Οδηγός» → «Εξερευνήστε» (η σελίδα /odigos/ και ο τίτλος της δεν αλλάζουν)
 * Το μενού του WordPress δεν αλλάζει: μόνο φίλτρα στην εμφάνιση.
 */

defined( 'ABSPATH' ) || exit;

function ellada_menu_v2() {
	if ( is_admin() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'menu' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'menu_v2' );
}

/** Ποιο στοιχείο είναι (για το εικονίδιο). */
function ellada_menu_v2_kind( $item ) {
	$url  = untrailingslashit( (string) $item->url );
	$home = untrailingslashit( home_url( '/' ) );
	if ( $url === $home || ( 'custom' === $item->type && '/' === $item->url ) ) {
		return 'home';
	}
	if ( 'post_type_archive' === $item->type ) {
		return 'ellada_collection' === $item->object ? 'book' : ( 'ellada_recipe' === $item->object ? 'pot' : 'dot' );
	}
	$rec = function_exists( 'get_post_type_archive_link' ) ? untrailingslashit( (string) get_post_type_archive_link( 'ellada_recipe' ) ) : '';
	if ( $rec && $url === $rec ) {
		return 'pot';
	}
	if ( 'taxonomy' === $item->type && in_array( $item->object, array( 'recipe_category', 'recipe_diet' ), true ) ) {
		return 'pot';
	}
	if ( 'page' === $item->object ) {
		$slug = get_post_field( 'post_name', (int) $item->object_id );
		$map  = array(
			'odigos'          => 'pin',
			'epikoinonia'     => 'mail',
			'oi-syntages-mou' => 'heart',
		);
		return $map[ $slug ] ?? 'dot';
	}
	if ( 'ellada_location' === $item->object ) {
		return 'pin';
	}
	return 'dot';
}

function ellada_menu_v2_icon( $kind ) {
	$p = array(
		'home'  => '<path d="M3.5 10.5 12 3.8l8.5 6.7"/><path d="M5.8 9v10.2h4.4v-5.4h3.6v5.4h4.4V9"/>',
		'pot'   => '<path d="M4 10h16v5.5A4.5 4.5 0 0 1 15.5 20h-7A4.5 4.5 0 0 1 4 15.5V10Z"/><path d="M2 10h2M20 10h2M9 7.5c0-1.2.8-1.6.8-2.8M12.5 7.5c0-1.2.8-1.6.8-2.8"/>',
		'pin'   => '<path d="M12 21.2s-6.8-6-6.8-11.3a6.8 6.8 0 0 1 13.6 0c0 5.3-6.8 11.3-6.8 11.3Z"/><circle cx="12" cy="9.9" r="2.6"/>',
		'mail'  => '<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
		'book'  => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20v3H6.5"/>',
		'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
		'dot'   => '<circle cx="12" cy="12" r="3.2"/>',
	);
	return '<svg class="ellada-mi__ico" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $p[ $kind ] ?? $p['dot'] ) . '</svg>';
}

/** Μόνο στο κεντρικό μενού (θέση/μενού της κεφαλίδας), όχι σε άλλα μενού. */
function ellada_menu_v2_applies( $item ) {
	static $menu = null;
	if ( null === $menu ) {
		$menu = 3; // «Κεντρικό μενού»
	}
	$terms = wp_get_object_terms( (int) $item->ID, 'nav_menu', array( 'fields' => 'ids' ) );
	return ! is_wp_error( $terms ) && in_array( $menu, array_map( 'intval', $terms ), true );
}

add_filter(
	'nav_menu_item_title',
	function ( $title, $item ) {
		if ( ! ellada_menu_v2() || ! ellada_menu_v2_applies( $item ) ) {
			return $title;
		}
		$k     = ellada_menu_v2_kind( $item );
		$label = 'page' === $item->object && 'odigos' === get_post_field( 'post_name', (int) $item->object_id ) ? 'Εξερευνήστε' : wp_strip_all_tags( $title );
		return ellada_menu_v2_icon( $k ) . '<span class="ellada-mi__txt">' . esc_html( $label ) . '</span>';
	},
	10,
	2
);

add_filter(
	'nav_menu_link_attributes',
	function ( $atts, $item ) {
		if ( ellada_menu_v2() && ellada_menu_v2_applies( $item ) ) {
			$label              = 'page' === $item->object && 'odigos' === get_post_field( 'post_name', (int) $item->object_id ) ? 'Εξερευνήστε' : wp_strip_all_tags( $item->title );
			$atts['aria-label'] = $label;
			$atts['title']      = $label;
			$atts['class']      = trim( ( $atts['class'] ?? '' ) . ' ellada-mi ellada-mi--' . ellada_menu_v2_kind( $item ) );
		}
		return $atts;
	},
	10,
	2
);
