<?php
/**
 * Σελίδα «Συνταγές» v4 (λειτουργία «recipes_v4»· προεπισκόπηση ?ellada_preview=1 για editors).
 *
 *   1. Hero στο ύφος της αρχικής: πετρόλ, τίτλος σε δύο χρώματα, φωτογραφία δεξιά που σβήνει στο πετρόλ.
 *   3. Κάρτες: 3 στήλες, περιφέρεια πάνω στη φωτογραφία, ♥ αγαπημένα, ζουμ στο hover·
 *      χωρίς φωτογραφία: η περιφέρεια της συνταγής τονισμένη στον χάρτη.
 *   4. Φίλτρα σε μία μπάρα πάνω από τις κάρτες (κολλάει στο πάνω μέρος σε υπολογιστή, κουμπί «Φίλτρα» στο κινητό).
 *   5. Κάτω από τις κάρτες: «Στην εποχή τους», «Ανά περιφέρεια», «Έρχονται σύντομα», «Στείλτε μας συνταγή».
 *   6. Footer: οι περιφέρειες σε στήλες χωρίς κενά (ισχύει σε όλο το site όταν η λειτουργία είναι ανοιχτή).
 *
 * Όλα μέσα από τα υπάρχοντα shortcodes του template 122 — το Elementor δεν αλλάζει.
 */

defined( 'ABSPATH' ) || exit;

function ellada_recipes_v4() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'recipes_v4' );
}

/** Είμαστε σε λίστα συνταγών με τη v4; */
function ellada_recipes_v4_page() {
	return ellada_recipes_v4() && function_exists( 'ellada_is_recipe_listing' ) && ellada_is_recipe_listing();
}

/** Στην προεπισκόπηση, τα links των φίλτρων κρατούν το ?ellada_preview=1. */
function ellada_recipes_v4_keep() {
	$opts = (array) get_option( 'ellada_features', array() );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return empty( $opts['recipes_v4'] ) && isset( $_GET['ellada_preview'] ) ? array( 'ellada_preview' => 1 ) : array();
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_recipes_v4() ) {
			$c[] = 'ellada-rv4-on'; // footer (6)
		}
		if ( ellada_recipes_v4_page() ) {
			$c[] = 'ellada-rv4';
			if ( is_post_type_archive( 'ellada_recipe' ) ) {
				$c[] = 'ellada-rv4-main';
			}
		}
		return $c;
	}
);

/* ═════════════════════ (1) Hero ═════════════════════ */

add_action(
	'wp_head',
	function () {
		if ( ! ellada_recipes_v4_page() ) {
			return;
		}
		$img = content_url( 'uploads/ellada-brand/ellada-hero-dakos-1600.webp' );
		echo '<style id="ellada-rv4-hero">body.ellada-rv4 .elementor-element.s-arch-hero{--ellada-rv4-img:url("' . esc_url( $img ) . '");}</style>' . "\n";
	},
	30
);

/* ═════════════════════ (3) Κάρτες ═════════════════════ */

/** Χωρίς φωτογραφία: ο χάρτης με την περιφέρεια της συνταγής τονισμένη. */
function ellada_rv4_card_placeholder( $post_id, $cat ) {
	$region = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $post_id ) : '';
	$map    = $region && function_exists( 'ellada_mini_map_svg' ) ? ellada_mini_map_svg( $region, 'ellada-card__ph-map' ) : '';
	if ( ! $map ) {
		return '';
	}
	return '<span class="ellada-card__placeholder is-map" aria-hidden="true">' . $map
		. ( $cat ? '<span class="ellada-card__ph-label">' . esc_html( $cat ) . '</span>' : '' ) . '</span>';
}

/**
 * Συμπληρώνει την κάρτα του ellada_recipe_card(): περιφέρεια πάνω στη φωτογραφία, ♥, χάρτης αντί για πιατάκι.
 * Καλείται από το blocks.php όταν είναι ανοιχτή η v4.
 */
function ellada_rv4_card( $html, $post_id, $has_img, $cat ) {
	$region = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $post_id ) : '';
	if ( ! $has_img ) {
		$ph = ellada_rv4_card_placeholder( $post_id, $cat );
		if ( $ph ) {
			$html = preg_replace( '#<span class="ellada-card__placeholder".*?</span></span>#s', $ph, $html, 1 );
		}
	}
	if ( $region ) {
		$html = str_replace( '<div class="ellada-card__media">', '<div class="ellada-card__media"><span class="ellada-card__region">' . esc_html( $region ) . '</span>', $html );
	}
	$fav  = sprintf(
		'<button type="button" class="ellada-card__fav" data-ellada-cardfav="%1$d" aria-pressed="false" aria-label="%2$s"><span aria-hidden="true">♡</span></button>',
		(int) $post_id,
		esc_attr( 'Στις αγαπημένες: ' . get_the_title( $post_id ) )
	);
	$html = str_replace( '<article class="ellada-card">', '<article class="ellada-card is-v4">' . $fav, $html );
	return $html;
}

/* ═════════════════════ (4) Φίλτρα πάνω από τις κάρτες ═════════════════════ */

add_filter(
	'pre_do_shortcode_tag',
	function ( $out, $tag ) {
		// Στο hero δεν μπαίνουν πια· βγαίνουν πάνω από τις κάρτες (παρακάτω).
		if ( 'ellada_recipe_filters' === $tag && empty( $GLOBALS['ellada_rv4_inner'] ) && ellada_recipes_v4_page() ) {
			return '';
		}
		return $out;
	},
	10,
	2
);

function ellada_rv4_filters_html() {
	$GLOBALS['ellada_rv4_inner'] = true;
	$html                        = do_shortcode( '[ellada_recipe_filters]' );
	unset( $GLOBALS['ellada_rv4_inner'] );
	if ( '' === trim( wp_strip_all_tags( $html, true ) ) && false === strpos( $html, '<a' ) ) {
		return '';
	}
	// Προεπισκόπηση: και τα links των κατηγοριών και η φόρμα κρατούν το ?ellada_preview=1.
	if ( ellada_recipes_v4_keep() ) {
		$html = preg_replace_callback(
			'#<a class="ellada-chip([^"]*)" href="([^"]+)"#',
			fn( $m ) => '<a class="ellada-chip' . $m[1] . '" href="' . esc_url( add_query_arg( 'ellada_preview', 1, html_entity_decode( $m[2] ) ) ) . '"',
			$html
		);
		$html = str_replace( '</form>', '<input type="hidden" name="ellada_preview" value="1"></form>', $html );
	}
	$active = function_exists( 'ellada_recipe_filters_active' ) && ellada_recipe_filters_active();
	return '<div class="ellada-rv4bar' . ( $active ? ' has-active' : '' ) . '">'
		. '<button type="button" class="ellada-rv4bar__toggle" aria-expanded="false"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>Φίλτρα' . ( $active ? ' <span class="ellada-rv4bar__dot" aria-label="ενεργά"></span>' : '' ) . '</button>'
		. $html . '</div>';
}

/* ═════════════════════ (5) Ενότητες κάτω από τις κάρτες ═════════════════════ */

function ellada_rv4_head( $eyebrow, $title, $lead = '' ) {
	return '<div class="ellada-rv4s__head"><p class="ellada-rv4s__eyebrow">' . esc_html( $eyebrow ) . '</p><h2 class="ellada-rv4s__title">' . esc_html( $title ) . '</h2>'
		. ( $lead ? '<p class="ellada-rv4s__lead">' . esc_html( $lead ) . '</p>' : '' ) . '</div>';
}

/** Στην εποχή τους: η γιορτή/νηστεία των ημερών + συλλογή + συνταγές με υλικά της εποχής. */
function ellada_rv4_season() {
	if ( ! function_exists( 'ellada_season_occasion' ) ) {
		return '';
	}
	$now  = new DateTimeImmutable( 'now', wp_timezone() );
	$occ  = ellada_season_occasion( $now );
	$coll = $occ && function_exists( 'ellada_collection_by_theme' ) ? ellada_collection_by_theme( $occ[2] ) : 0;
	$ids  = function_exists( 'ellada_season_recipes' ) ? ellada_season_recipes( (int) $now->format( 'n' ), 3 ) : array();
	if ( ! $occ && ! $ids ) {
		return '';
	}
	$title = $occ ? $occ[0] : 'Με υλικά της εποχής';
	$lead  = $occ ? $occ[1] : 'Συνταγές με ό,τι βγάζει τώρα ο τόπος.';
	$html  = '<section class="ellada-rv4s ellada-rv4s--season">' . ellada_rv4_head( 'Στην εποχή τους', $title, $lead );
	if ( $coll ) {
		$html .= sprintf( '<a class="ellada-rv4s__more" href="%s">Η συλλογή «%s» <span aria-hidden="true">→</span></a>', esc_url( get_permalink( $coll ) ), esc_html( get_the_title( $coll ) ) );
	}
	if ( $ids ) {
		$html .= '<div class="ellada-cards ellada-rv4s__cards">' . implode( '', array_map( 'ellada_recipe_card', $ids ) ) . '</div>';
	}
	return $html . '</section>';
}

/** Ανά περιφέρεια: χάρτης με τις περιφέρειες που έχουν συνταγές (links στο φίλτρο). */
function ellada_rv4_regions() {
	if ( ! function_exists( 'ellada_regions_mini' ) || ! function_exists( 'ellada_recipe_regions_in_use' ) ) {
		return '';
	}
	$mini = ellada_regions_mini();
	$used = ellada_recipe_regions_in_use();
	if ( empty( $mini['regions'] ) || ! $used ) {
		return '';
	}
	global $wpdb;
	$counts = array();
	foreach ( $used as $r ) {
		$counts[ $r ] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s AND pm.meta_value = %s AND p.post_type = 'ellada_recipe' AND p.post_status = 'publish'",
				ELLADA_AREA_KEYS['region'],
				$r
			)
		);
	}
	$base  = get_post_type_archive_link( 'ellada_recipe' );
	$keep  = ellada_recipes_v4_keep();
	$paths = '';
	$list  = '';
	foreach ( $mini['regions'] as $name => $d ) {
		if ( isset( $counts[ $name ] ) ) {
			$url    = add_query_arg( array_merge( array( 'perioxi' => $name ), $keep ), $base );
			$label  = $name . ' — ' . ellada_plural( $counts[ $name ], '%s συνταγή', '%s συνταγές' );
			$paths .= sprintf( '<a href="%1$s" aria-label="%2$s"><path class="is-on" d="%3$s"><title>%2$s</title></path></a>', esc_url( $url ), esc_attr( $label ), esc_attr( $d ) );
			$list  .= sprintf( '<li><a href="%1$s"><span>%2$s</span><span class="ellada-rv4s__n">%3$s</span></a></li>', esc_url( $url ), esc_html( $name ), esc_html( ellada_plural( $counts[ $name ], '%s συνταγή', '%s συνταγές' ) ) );
		} else {
			$paths .= '<path class="is-off" d="' . esc_attr( $d ) . '"/>';
		}
	}
	return '<section class="ellada-rv4s ellada-rv4s--regions"><div class="ellada-rv4s__split"><div>'
		. ellada_rv4_head( 'Ανά περιφέρεια', 'Διαλέξτε τόπο', 'Οι περιοχές σε κεραμιδί έχουν ήδη συνταγές· οι υπόλοιπες έρχονται σιγά σιγά.' )
		. '<ul class="ellada-rv4s__regions">' . $list . '</ul></div>'
		. '<svg class="ellada-rv4s__map" viewBox="' . esc_attr( $mini['viewBox'] ) . '" role="img" aria-label="Χάρτης περιφερειών με συνταγές">' . $paths . '</svg>'
		. '</div></section>';
}

/** Έρχονται σύντομα: πιάτα του οδηγού που δεν έχουν ακόμα συνταγή. */
function ellada_rv4_coming() {
	if ( ! function_exists( 'ellada_missing_recipes' ) ) {
		return '';
	}
	$rows = get_transient( 'ellada_rv4_coming' );
	if ( ! is_array( $rows ) ) {
		$rows = ellada_missing_recipes();
		set_transient( 'ellada_rv4_coming', $rows, 6 * HOUR_IN_SECONDS );
	}
	if ( ! $rows ) {
		return '';
	}
	// Ένα πιάτο ανά τόπο πρώτα, για ποικιλία.
	$seen = array();
	$pick = array();
	foreach ( $rows as $r ) {
		if ( ! isset( $seen[ $r[2] ] ) ) {
			$seen[ $r[2] ] = 1;
			$pick[]        = $r;
		}
	}
	foreach ( $rows as $r ) {
		if ( count( $pick ) >= 4 ) {
			break;
		}
		if ( ! in_array( $r, $pick, true ) ) {
			$pick[] = $r;
		}
	}
	$cards = '';
	foreach ( array_slice( $pick, 0, 4 ) as list( $name, $desc, $loc ) ) {
		$cards .= sprintf(
			'<li class="ellada-rv4s__soon"><span class="ellada-rv4s__tag">Έρχεται σύντομα</span><h3>%1$s</h3>%2$s<a href="%3$s">%4$s</a></li>',
			esc_html( $name ),
			$desc ? '<p>' . esc_html( $desc ) . '</p>' : '',
			esc_url( get_permalink( $loc ) ),
			esc_html( get_the_title( $loc ) )
		);
	}
	return '<section class="ellada-rv4s ellada-rv4s--soon">' . ellada_rv4_head( 'Στην κουζίνα μας', 'Έρχονται σύντομα', 'Πιάτα του οδηγού που ετοιμάζουμε να γράψουμε.' )
		. '<ul class="ellada-rv4s__soonlist">' . $cards . '</ul></section>';
}
add_action(
	'save_post_ellada_recipe',
	function () {
		delete_transient( 'ellada_rv4_coming' );
	}
);

function ellada_rv4_submit() {
	$page = get_page_by_path( 'epikoinonia' );
	if ( ! $page || 'publish' !== $page->post_status ) {
		return '';
	}
	$url = add_query_arg( 'thema', 'syntagi', get_permalink( $page ) );
	return '<section class="ellada-rv4s ellada-rv4cta"><div><p class="ellada-rv4s__eyebrow">Από τη δική σας κουζίνα</p>'
		. '<h2 class="ellada-rv4cta__title">Έχετε μια συνταγή του τόπου σας;</h2>'
		. '<p class="ellada-rv4cta__text">Στείλτε μας τη όπως τη φτιάχνετε· τα υπόλοιπα θα τα γράψουμε μαζί.</p></div>'
		. '<a class="ellada-rv4cta__btn" href="' . esc_url( $url ) . '">Στείλτε μας τη <span aria-hidden="true">→</span></a></section>';
}

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_cards' !== $tag || ! ellada_recipes_v4_page() || ( $attr['source'] ?? '' ) !== 'archive' ) {
			return $output;
		}
		$before = ellada_rv4_filters_html();
		$after  = '';
		if ( is_post_type_archive( 'ellada_recipe' ) && ! is_paged() && ! ellada_recipe_filters_active() ) {
			$after = ellada_rv4_season() . ellada_rv4_regions() . ellada_rv4_coming() . ellada_rv4_submit();
		}
		return $before . $output . $after;
	},
	10,
	3
);

/* ═════════════════════ JS: φίλτρα στο κινητό, ♥ στις κάρτες ═════════════════════ */

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_recipes_v4_page() ) {
			return;
		}
		$page = get_page_by_path( 'oi-syntages-mou' );
		$mine = $page && 'publish' === $page->post_status ? get_permalink( $page ) : '';
		?>
		<script>
		(function () {
			var bar = document.querySelector('.ellada-rv4bar');
			if (bar) {
				var t = bar.querySelector('.ellada-rv4bar__toggle');
				t.addEventListener('click', function () {
					var open = bar.classList.toggle('is-open');
					t.setAttribute('aria-expanded', open ? 'true' : 'false');
				});
				// Σκιά όταν η μπάρα έχει κολλήσει πάνω.
				if (window.IntersectionObserver) {
					var s = document.createElement('div');
					s.className = 'ellada-rv4bar__sentinel';
					bar.parentNode.insertBefore(s, bar);
					new IntersectionObserver(function (e) { bar.classList.toggle('is-stuck', !e[0].isIntersecting); }, { rootMargin: '-13px 0px 0px 0px' }).observe(s);
				}
			}
			function favs() { try { var f = JSON.parse(localStorage.getItem('ellada-favs') || 'null'); return Array.isArray(f) ? f : []; } catch (e) { return []; } }
			var mine = <?php echo wp_json_encode( $mine ); ?>;
			document.querySelectorAll('[data-ellada-cardfav]').forEach(function (b) {
				var id = parseInt(b.getAttribute('data-ellada-cardfav'), 10);
				function paint() { var on = favs().indexOf(id) !== -1; b.setAttribute('aria-pressed', on ? 'true' : 'false'); b.firstChild.textContent = on ? '♥' : '♡'; b.classList.toggle('is-on', on); }
				paint();
				b.addEventListener('click', function (e) {
					e.preventDefault();
					var f = favs(), i = f.indexOf(id);
					if (i === -1) f.unshift(id); else f.splice(i, 1);
					try { localStorage.setItem('ellada-favs', JSON.stringify(f.slice(0, 60))); } catch (er) {}
					paint();
					if (i === -1 && mine && !document.querySelector('.ellada-rv4toast')) {
						var a = document.createElement('a'); a.className = 'ellada-rv4toast'; a.href = mine; a.textContent = 'Αποθηκεύτηκε · Οι συνταγές μου →';
						document.body.appendChild(a); setTimeout(function () { a.remove(); }, 3200);
					}
				});
			});
		})();
		</script>
		<?php
	},
	30
);
