<?php
/**
 * Αλλεργιογόνα με τα κλασικά εικονίδια (τα 14 της ΕΕ): χρωματιστός κύκλος + λευκό σχέδιο.
 * Τιμές του ACF checkbox «allergens» (gluten, lactose, eggs, nuts, sesame, fish, shellfish + soy, peanuts, celery, mustard, sulphites, lupin, molluscs).
 *
 * @package ellada-map
 */

defined( 'ABSPATH' ) || exit;

/** slug => [ετικέτα, χρώμα, σχέδιο SVG σε viewBox 24]. */
function ellada_allergens() {
	return array(
		'gluten'    => array( 'Γλουτένη', '#D9962B', '<path d="M12 21V9"/><path d="M12 9c-2.2-.6-3.3-2.3-3.2-4.6 2.2.5 3.3 2.2 3.2 4.6Zm0 0c2.2-.6 3.3-2.3 3.2-4.6-2.2.5-3.3 2.2-3.2 4.6Zm0 4.5c-2.2-.6-3.3-2.3-3.2-4.6 2.2.5 3.3 2.2 3.2 4.6Zm0 0c2.2-.6 3.3-2.3 3.2-4.6-2.2.5-3.3 2.2-3.2 4.6Zm0 4.5c-2.2-.6-3.3-2.3-3.2-4.6 2.2.5 3.3 2.2 3.2 4.6Zm0 0c2.2-.6 3.3-2.3 3.2-4.6-2.2.5-3.3 2.2-3.2 4.6ZM12 4.6V3"/>' ),
		'shellfish' => array( 'Οστρακοειδή', '#3D78B0', '<ellipse cx="12" cy="14" rx="5" ry="3.6"/><path d="M7.4 12.6 4.5 10M16.6 12.6l2.9-2.6M4.5 10c-1.3-1.6-.6-3.6 1-4.4-.2 1.3.4 2.2 1.4 2.4M19.5 10c1.3-1.6.6-3.6-1-4.4.2 1.3-.4 2.2-1.4 2.4M8 17l-2 2.4M16 17l2 2.4M10 17.4 9.2 20M14 17.4l.8 2.6"/>' ),
		'eggs'      => array( 'Αυγά', '#E9843A', '<path d="M12 3.5c3.2 0 5.8 5 5.8 9.4A5.8 5.8 0 0 1 12 18.7a5.8 5.8 0 0 1-5.8-5.8C6.2 8.5 8.8 3.5 12 3.5Z"/>' ),
		'fish'      => array( 'Ψάρια', '#2C5B8A', '<path d="M3.5 12c2.6-3.6 6.2-5 9.5-5 3.4 0 5.6 2.3 6.5 5-.9 2.7-3.1 5-6.5 5-3.3 0-6.9-1.4-9.5-5Z"/><path d="M3.5 12 2 9m1.5 3L2 15"/><circle cx="15.8" cy="11" r=".9" fill="#fff" stroke="none"/>' ),
		'peanuts'   => array( 'Αράπικο φιστίκι', '#9C6436', '<path d="M12 3.5c2.4 0 3.9 1.8 3.9 3.9 0 1.6-.9 2.5-.9 4.6s.9 3 .9 4.6c0 2.1-1.5 3.9-3.9 3.9s-3.9-1.8-3.9-3.9c0-1.6.9-2.5.9-4.6S8.1 9 8.1 7.4c0-2.1 1.5-3.9 3.9-3.9Z"/><path d="M10.5 7h.01M13.5 9h.01M10.6 15h.01M13.4 17h.01"/>' ),
		'soy'       => array( 'Σόγια', '#6B9A36', '<path d="M5 17.5C4 12 8 5.5 15.5 4.5c4 .5 4 5-1 9-3.5 3-7.2 4.7-9.5 4Z"/><circle cx="9.3" cy="13.4" r="1.4"/><circle cx="12.3" cy="10.6" r="1.4"/><circle cx="15.2" cy="7.8" r="1.4"/>' ),
		'lactose'   => array( 'Γαλακτοκομικά / λακτόζη', '#4FA3D1', '<path d="M9.5 3h5M10 3v3l-2.5 3.5V20a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V9.5L14 6V3"/><path d="M7.5 12.5h9"/>' ),
		'nuts'      => array( 'Ξηροί καρποί', '#8A4A2A', '<path d="M6 10.5c0-3 2.7-5 6-5s6 2 6 5Z"/><path d="M6.5 10.5c.2 5.5 2.6 9 5.5 9s5.3-3.5 5.5-9"/><path d="M12 5.5V3.5"/>' ),
		'celery'    => array( 'Σέλινο', '#76AD3F', '<path d="M9 21V11M12 21V9M15 21V11"/><path d="M9 11c-2.5-.5-3.5-2.5-3-4.5 2 .3 3 1.8 3 4.5Zm3-2c0-2.8 1-4.6 3-5.5.6 2.4-.5 4.4-3 5.5Zm3 2c2.5-.5 3.5-2.5 3-4.5-2 .3-3 1.8-3 4.5Z"/>' ),
		'mustard'   => array( 'Μουστάρδα', '#C99A16', '<path d="M8 8h8l1 2.5V20a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-9.5L8 8Z"/><path d="M9.5 8V5.5h5V8M11 5.5V3h2v2.5"/><path d="M7 13h10"/>' ),
		'sesame'    => array( 'Σουσάμι', '#A89067', '<path d="M8 6.5c1.3 0 2 1.4 2 3s-.7 2.5-2 2.5-2-1-2-2.5.7-3 2-3Zm8 0c1.3 0 2 1.4 2 3s-.7 2.5-2 2.5-2-1-2-2.5.7-3 2-3Zm-4 6c1.3 0 2 1.4 2 3s-.7 2.5-2 2.5-2-1-2-2.5.7-3 2-3Z"/>' ),
		'sulphites' => array( 'Θειώδη', '#7B4C8F', '<path d="M7.5 3.5h9c0 4.5-1.8 7.5-4.5 7.5S7.5 8 7.5 3.5ZM12 11v8M8.5 20.5h7"/><path d="M8 6.5h8"/>' ),
		'lupin'     => array( 'Λούπινο', '#B99A1E', '<path d="M12 21V9"/><path d="M12 4a1.4 1.4 0 1 1 0 2.8A1.4 1.4 0 0 1 12 4Zm-2.3 3.3a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8Zm4.6 0a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8ZM9.7 11a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8Zm4.6 0a1.4 1.4 0 1 1 0 2.8 1.4 1.4 0 0 1 0-2.8Z"/><path d="M12 17c-2-.2-3.4-1.2-4-2.8M12 17c2-.2 3.4-1.2 4-2.8"/>' ),
		'molluscs'  => array( 'Μαλάκια', '#2B8A88', '<path d="M12 19.5 4.2 10a8.2 8.2 0 0 1 15.6 0Z"/><path d="M12 19.5V5M12 19.5 8 6.2M12 19.5l4-13.3M12 19.5 5.2 8M12 19.5l6.8-11.5"/><path d="M10 20.5h4"/>' ),
	);
}

function ellada_allergen_icon( $slug, $size = 22 ) {
	$a = ellada_allergens()[ $slug ] ?? null;
	if ( ! $a ) {
		return '';
	}
	return sprintf(
		'<svg class="ellada-alg__i" width="%1$d" height="%1$d" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="12" fill="%2$s"/><g transform="translate(3.6 3.6) scale(.7)" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">%3$s</g></svg>',
		(int) $size,
		esc_attr( $a[1] ),
		$a[2]
	);
}

/** HTML: «Περιέχει:» + ετικέτες με εικονίδιο. */
function ellada_allergens_html( $id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}
	$all   = ellada_allergens();
	$chips = '';
	foreach ( (array) get_field( 'allergens', $id ) as $a ) {
		$slug  = is_array( $a ) ? (string) ( $a['value'] ?? '' ) : (string) $a;
		$label = $all[ $slug ][0] ?? ( is_array( $a ) ? (string) ( $a['label'] ?? '' ) : $slug );
		if ( '' === $label ) {
			continue;
		}
		$chips .= '<span class="ellada-alg" data-a="' . esc_attr( $slug ) . '">' . ellada_allergen_icon( $slug ) . esc_html( $label ) . '</span>';
	}
	return $chips ? '<p class="r6-allergens ellada-algs"><span class="ellada-algs__t">Περιέχει:</span> ' . $chips . '</p>' : '';
}
