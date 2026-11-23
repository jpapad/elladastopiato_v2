<?php
/**
 * Αρχική: η τελευταία ενότητα «Κάθε τόπος, ένα πιάτο» (h3c-hero) σε νέα μορφή (λειτουργία «home_closing»).
 *
 *   - ανοιχτό φόντο #E7E2D5 (όχι δύο πετρόλ μπλοκ στη σειρά με το footer)
 *   - δεξιά: ζευγάρια «τόπος → πιάτο» από τις δημοσιευμένες συνταγές (με φωτογραφία όπου υπάρχει)
 *   - κάτω από τον τίτλο: «κορδέλα» με ονόματα πιάτων (συνταγές + πιάτα του οδηγού) που κυλάει αργά
 *   - δεύτερο κουμπί «Ο οδηγός →»
 * Το Elementor (σελίδα 163) δεν αλλάζει: τα νέα κομμάτια μπαίνουν με JS στην ενότητα.
 * Προβολή χωρίς σύνδεση: ?ellada_demo=closing
 */

defined( 'ABSPATH' ) || exit;

function ellada_home_closing() {
	if ( is_admin() || ! is_front_page() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'closing' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'home_closing' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_home_closing() ) {
			$c[] = 'ellada-hc';
		}
		return $c;
	}
);

/** [[τόπος, πιάτο, url, img html], …] από τις συνταγές. */
function ellada_hc_pairs( $limit = 4 ) {
	$out = array();
	$ids = get_posts(
		array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => 30,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	// Πρώτα όσες έχουν φωτογραφία.
	usort( $ids, fn( $a, $b ) => (int) has_post_thumbnail( $b ) - (int) has_post_thumbnail( $a ) );
	$seen = array();
	foreach ( $ids as $id ) {
		$loc   = (int) get_post_meta( $id, 'location', true );
		$place = $loc ? get_the_title( $loc ) : ( function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $id ) : '' );
		if ( ! $place || isset( $seen[ $place ] ) ) {
			continue;
		}
		$seen[ $place ] = 1;
		$img            = get_the_post_thumbnail( $id, 'thumbnail', array( 'class' => 'ellada-hc__img', 'loading' => 'lazy', 'alt' => '' ) );
		$out[]          = array( $place, get_the_title( $id ), get_permalink( $id ), $img );
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/** Ονόματα πιάτων για την κορδέλα. */
function ellada_hc_dishes() {
	$names = array();
	foreach ( get_posts( array( 'post_type' => 'ellada_recipe', 'post_status' => 'publish', 'posts_per_page' => 30, 'no_found_rows' => true ) ) as $p ) {
		$names[] = $p->post_title;
	}
	if ( function_exists( 'ellada_missing_recipes' ) ) {
		$rows = get_transient( 'ellada_hc_missing' );
		if ( ! is_array( $rows ) ) {
			$rows = ellada_missing_recipes();
			set_transient( 'ellada_hc_missing', $rows, 6 * HOUR_IN_SECONDS );
		}
		foreach ( $rows as $r ) {
			$names[] = $r[0];
		}
	}
	// Σύντομα ονόματα (ως το κόμμα), μοναδικά, με πεζά πρώτο γράμμα όπως είναι.
	$names = array_map( fn( $n ) => trim( preg_split( '/[,(]/u', $n )[0] ), $names );
	return array_values( array_unique( array_filter( $names ) ) );
}
add_action(
	'save_post_ellada_recipe',
	function () {
		delete_transient( 'ellada_hc_missing' );
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_home_closing() ) {
			return;
		}
		$pairs  = ellada_hc_pairs();
		$dishes = ellada_hc_dishes();
		$guide  = get_page_by_path( 'odigos', OBJECT, 'page' );
		$bowl   = '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 11h16a8 8 0 0 1-16 0Z"/><path d="M8 7c0-1.5 1-2 1-3M12 7c0-1.5 1-2 1-3M16 7c0-1.5 1-2 1-3"/></svg>';

		$list = '';
		foreach ( $pairs as list( $place, $dish, $url, $img ) ) {
			$list .= sprintf(
				'<li><a class="ellada-hc__pair" href="%1$s"><span class="ellada-hc__thumb">%2$s</span><span class="ellada-hc__txt"><span class="ellada-hc__place">%3$s</span><span class="ellada-hc__dish">%4$s</span></span><span class="ellada-hc__go" aria-hidden="true">→</span></a></li>',
				esc_url( $url ),
				$img ? $img : $bowl,
				esc_html( $place ),
				esc_html( $dish )
			);
		}
		$aside = $list ? '<aside class="ellada-hc__aside" aria-label="Τόποι και πιάτα"><p class="ellada-hc__eyebrow">Από τον τόπο στο πιάτο</p><ul class="ellada-hc__pairs">' . $list . '</ul></aside>' : '';

		$ticker = '';
		if ( count( $dishes ) > 3 ) {
			$items  = '';
			foreach ( $dishes as $d ) {
				$items .= '<span>' . esc_html( mb_strtolower( $d ) ) . '</span>';
			}
			// Δύο φορές το ίδιο για συνεχόμενη κίνηση.
			$ticker = '<div class="ellada-hc__ticker" aria-hidden="true"><div class="ellada-hc__track">' . $items . $items . '</div></div>';
		}
		$guide_btn = $guide && 'publish' === $guide->post_status ? get_permalink( $guide ) : '';
		?>
		<template id="ellada-hc-tpl"><?php echo $aside . $ticker; // phpcs:ignore WordPress.Security.EscapeOutput ?></template>
		<script>
		(function () {
			var sec = document.querySelector('.elementor-element-h3c-hero');
			var tpl = document.getElementById('ellada-hc-tpl');
			if (!sec || !tpl) return;
			var inner = sec.querySelector('[data-id="h3c-hero-in"]') || sec;
			var frag = tpl.content.cloneNode(true);
			var aside = frag.querySelector('.ellada-hc__aside');
			var ticker = frag.querySelector('.ellada-hc__ticker');
			var title = inner.querySelector('[data-id="h3c-title"]');
			if (ticker && title) title.insertAdjacentElement('afterend', ticker);
			var btn = inner.querySelector('[data-id="h3c-maplink"]');
			var guide = <?php echo wp_json_encode( $guide_btn ); ?>;
			if (btn && guide) {
				var wrap = document.createElement('div'); wrap.className = 'ellada-hc__btns';
				btn.parentNode.insertBefore(wrap, btn); wrap.appendChild(btn);
				var b2 = btn.cloneNode(true); b2.href = guide; b2.removeAttribute('data-id'); b2.classList.add('ellada-hc__guide');
				b2.textContent = 'Ο οδηγός →'; wrap.appendChild(b2);
			}
			if (aside) inner.appendChild(aside);
			sec.classList.add('is-hc');
		})();
		</script>
		<?php
	},
	25
);
