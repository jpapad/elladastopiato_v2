<?php
/**
 * Σελίδα συνταγής v6 — το «Γ · Μιξ» του Claude Design (λειτουργία «recipe_v6»· προβολή ?ellada_demo=recipe6).
 *
 *   - Hero της Α: φωτογραφία σε όλο το πλάτος με τίτλο/ετικέτες πάνω της + λευκή κάρτα «με μια ματιά»
 *     (χρόνος, μερίδες, δυσκολία, θερμίδες, ✓ Την έφτιαξα;, ♡, + Συλλογή, εκτύπωση, κοινοποίηση).
 *   - Πετρόλ μπάρα μαγειρέματος: «Μαγειρεύω τώρα», «Βήμα N από M», πρόοδος, 🔊.
 *   - Βήματα ως κάρτες· το επόμενο («Τώρα») τονίζεται όσο μαγειρεύετε· όσα έγιναν σβήνουν.
 *   - Σημειώσεις κάτω από τα υλικά· αλλεργιογόνα στα υλικά.
 *   - «Το έφτιαξα»: όχι μεγάλο κουτί — κουμπί στην κάρτα + ερώτηση «Καλή όρεξη! Το φτιάξατε;» στο τέλος.
 *   - Κουτάκια: Παραλλαγές + Μυστικά (πάνω), Ιστορία + Τόπος (κάτω).
 *   - Κινητό: λωρίδα «Υλικά · Βήματα · Ιστορία» που πηγαίνει στις ενότητες.
 * Το template 109 του Elementor δεν αλλάζει: οι υπάρχουσες λειτουργίες (μερίδες, λίστα, 🔊, χρονόμετρο, γλωσσάρι,
 * σημειώσεις, ♥, συλλογές) μένουν ίδιες — εδώ αλλάζει μόνο η διάταξη και η εμφάνιση.
 */

defined( 'ABSPATH' ) || exit;

function ellada_rv6() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'recipe6' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'recipe_v6' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( is_singular( 'ellada_recipe' ) && ellada_rv6() ) {
			$c[] = 'ellada-r6';
		}
		return $c;
	}
);

function ellada_rv6_icon( $k, $size = 22 ) {
	$p = array(
		'cheese' => '<path d="M3 16 14 6l7 5v6H3z"/><path d="M3 16v2h18"/><circle cx="9" cy="14" r="1"/><circle cx="15" cy="13" r="1.2"/>',
		'olive'  => '<ellipse cx="10" cy="14" rx="5" ry="6.5" transform="rotate(-20 10 14)"/><path d="M13 7c1-3 4-4 7-4-1 3-3 5-6 5"/>',
		'leaf'   => '<path d="M5 19c0-8 6-14 15-14 0 9-6 15-14 15"/><path d="M5 19 14 10"/>',
		'glass'  => '<path d="M7 4h10l-1.5 15a2 2 0 0 1-2 1.8h-3a2 2 0 0 1-2-1.8L7 4Z"/><path d="M7.5 9h9"/>',
		'bulb'   => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2.1h5c0-.9.4-1.6 1-2.1A6 6 0 0 0 12 3Z"/>',
		'spark'  => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
		'pot'    => '<path d="M4 10h16v5.5A4.5 4.5 0 0 1 15.5 20h-7A4.5 4.5 0 0 1 4 15.5V10Z"/><path d="M2 10h2M20 10h2"/>',
		'cook'   => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="currentColor"/>',
		'sound'  => '<path d="M4 9.5v5h3.5L12 18.5v-13L7.5 9.5H4Z"/><path d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/>',
	);
	return '<svg viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $p[ $k ] ?? '' ) . '</svg>';
}

/** Εικονίδιο για μια γραμμή παραλλαγής (από λέξεις-κλειδιά). */
function ellada_rv6_var_icon( $line ) {
	$t = function_exists( 'ellada_search_norm' ) ? ellada_search_norm( $line ) : mb_strtolower( $line );
	$map = array(
		'leaf'   => array( 'νηστισ', 'χωρις τυρι', 'vegan', 'χορτοφαγ' ),
		'glass'  => array( 'ρακη', 'κρασι', 'τσιπουρ', 'ουζο', 'μπυρα', 'συνοδευ' ),
		'olive'  => array( 'ελιε', 'ελια', 'λαδι' ),
		'cheese' => array( 'φετα', 'τυρι', 'μυζηθρ', 'γραβιερ', 'ανθοτυρ', 'κεφαλοτυρ', 'κασερ' ),
	);
	foreach ( $map as $k => $words ) {
		foreach ( $words as $w ) {
			if ( false !== mb_strpos( $t, $w ) ) {
				return $k;
			}
		}
	}
	return 'spark';
}

/** Χωρίζει «Με φέτα αντί για μυζήθρα.» σε τίτλο + δευτερεύον κείμενο. */
function ellada_rv6_split( $line ) {
	$line = rtrim( trim( ltrim( $line, "-•· \t" ) ), '.' );
	foreach ( array( ' αντί για ', ' ή ', ', ', ' για ', ' με ', ' από ' ) as $sep ) {
		$pos = mb_strpos( $line, $sep, 3 );
		if ( false !== $pos && $pos < 40 ) {
			return array( mb_substr( $line, 0, $pos ), ltrim( trim( mb_substr( $line, $pos ) ), ', ' ) );
		}
	}
	return array( $line, '' );
}

/* ═════════════════════ Κουτάκια: Παραλλαγές + Μυστικά, Ιστορία + Τόπος ═════════════════════ */

function ellada_rv6_bento( $id ) {
	$lines = array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) get_post_meta( $id, 'variations', true ) ) ) );
	$tips  = trim( wp_strip_all_tags( (string) get_post_meta( $id, 'tips', true ) ) );
	$story = trim( (string) get_post_meta( $id, 'story', true ) );
	$place = function_exists( 'ellada_recipe_place_html' ) ? ellada_recipe_place_html( $id ) : '';
	$out   = '';

	/* Πάνω σειρά */
	$top = '';
	if ( $tips ) {
		// Η παράγραφος σπάει σε προτάσεις → αριθμημένες συμβουλές.
		$sent = array_values( array_filter( array_map( 'trim', preg_split( '/(?<=[.!;])\s+/u', $tips ) ) ) );
		$lis  = '';
		foreach ( array_slice( $sent, 0, 5 ) as $i => $s ) {
			$lis .= '<li><b>' . ( $i + 1 ) . '</b><span>' . esc_html( $s ) . '</span></li>';
		}
		$top .= '<section class="r6-box r6-box--tips"><h2 class="r6-eyebrow"><span class="r6-box__ico">' . ellada_rv6_icon( 'bulb' ) . '</span>Μυστικά επιτυχίας</h2><ol class="r6-tips">' . $lis . '</ol></section>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( $lines ) {
		$items = '';
		foreach ( $lines as $l ) {
			list( $title, $sub ) = ellada_rv6_split( $l );
			$k      = ellada_rv6_var_icon( $l );
			$items .= sprintf(
				'<li class="r6-var%1$s"><span class="r6-var__i">%2$s</span><span class="r6-var__t"><b>%3$s</b>%4$s</span></li>',
				'leaf' === $k ? ' is-fast' : '',
				ellada_rv6_icon( $k ), // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $title ),
				'' !== $sub ? '<small>' . esc_html( $sub ) . '</small>' : ''
			);
		}
		$top .= '<section class="r6-box r6-box--vars"><h2 class="r6-eyebrow">Παραλλαγές &amp; συνοδευτικά</h2><ul class="r6-vars">' . $items . '</ul></section>';
	}
	if ( $top ) {
		$out .= '<div class="r6-row">' . $top . '</div>';
	}

	/* Κάτω σειρά */
	$bot = '';
	if ( $story ) {
		$paras = array_values( array_filter( array_map( 'trim', preg_split( "/\n\s*\n/", $story ) ) ) );
		$first = array_shift( $paras );
		$cap   = mb_substr( $first, 0, 1 );
		$html  = '<p class="r6-story__lead"><span class="r6-drop" aria-hidden="true">' . esc_html( $cap ) . '</span><span class="ellada-sr">' . esc_html( $cap ) . '</span>' . nl2br( esc_html( mb_substr( $first, 1 ) ) ) . '</p>';
		foreach ( $paras as $p ) {
			$html .= '<p>' . nl2br( esc_html( $p ) ) . '</p>';
		}
		$bot .= '<section class="r6-box r6-box--story ellada-rstory"><h2 class="r6-eyebrow">Η ιστορία της συνταγής</h2>' . $html . '</section>';
	}
	if ( $place ) {
		$bot .= '<div class="r6-box r6-box--place">' . $place . '</div>';
	}
	if ( $bot ) {
		$out .= '<div class="r6-row r6-row--story">' . $bot . '</div>';
	}
	return $out ? '<div class="r6-bento">' . $out . '</div>' : '';
}

/* Τα shortcodes: η ιστορία φεύγει από τα βήματα, τα «έξτρα» γίνονται κουτάκια, τα αλλεργιογόνα πάνε στα υλικά. */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( ! ellada_rv6() || ! is_singular( 'ellada_recipe' ) ) {
			return $output;
		}
		$id = function_exists( 'ellada_ctx_id' ) ? ellada_ctx_id( (array) $attr ) : get_the_ID();
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		if ( 'ellada_recipe_steps' === $tag && function_exists( 'ellada_recipe_story_html' ) ) {
			$story = ellada_recipe_story_html( $id );
			return $story ? str_replace( $story, '', $output ) : $output;
		}
		if ( 'ellada_recipe_extras' === $tag ) {
			$gallery = function_exists( 'ellada_gallery_html' ) && function_exists( 'get_field' ) ? ellada_gallery_html( (array) get_field( 'gallery', $id ) ) : '';
			return ellada_rv6_bento( $id ) . $gallery;
		}
		if ( 'ellada_recipe_ingredients' === $tag && function_exists( 'get_field' ) ) {
			$labels = array();
			foreach ( (array) get_field( 'allergens', $id ) as $a ) {
				$labels[] = is_array( $a ) ? ( $a['label'] ?? '' ) : (string) $a;
			}
			$labels = array_filter( $labels );
			if ( $labels ) {
				$line = '<p class="r6-allergens">Περιέχει: ' . esc_html( implode( ', ', $labels ) ) . '</p>';
				$pos  = strrpos( $output, '</section>' );
				return false === $pos ? $output . $line : substr_replace( $output, $line . '</section>', $pos, strlen( '</section>' ) );
			}
		}
		return $output;
	},
	99,
	3
);

/* ═════════════════════ Hero + μπάρα μαγειρέματος (template + JS) ═════════════════════ */

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_rv6() || ! is_singular( 'ellada_recipe' ) ) {
			return;
		}
		$id    = get_queried_object_id();
		$prep  = (int) get_post_meta( $id, 'prep_time', true );
		$cookm = (int) get_post_meta( $id, 'cook_time', true );
		$total = $prep + $cookm;
		$serv  = (int) get_post_meta( $id, 'servings', true );
		$diff  = function_exists( 'ellada_select_value' ) ? ellada_select_value( get_post_meta( $id, 'difficulty', true ) ) : '';
		$diffs = array( 'easy' => 'Εύκολη', 'medium' => 'Μέτρια', 'hard' => 'Απαιτητική' );
		$kcal  = function_exists( 'ellada_rv5_calories' ) ? ellada_rv5_calories( $id ) : (int) get_post_meta( $id, 'calories', true );
		$img   = get_the_post_thumbnail_url( $id, 'full' );
		$desc  = trim( (string) get_post_meta( $id, 'short_description', true ) );
		$fmt   = function ( $m ) {
			return $m >= 60 ? floor( $m / 60 ) . ' ώρ.' . ( $m % 60 ? ' ' . ( $m % 60 ) . '′' : '' ) : $m . '′';
		};
		$stats = array_filter(
			array(
				'Χρόνος'     => $total ? $fmt( $total ) : '',
				'Μερίδες'    => $serv ? (string) $serv : '',
				'Δυσκολία'   => $diffs[ $diff ] ?? '',
				'Ανά μερίδα' => $kcal ? '≈ ' . $kcal . ' kcal' : '',
			)
		);
		$st = '';
		foreach ( $stats as $l => $v ) {
			$st .= '<div><dt>' . esc_html( $l ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
		}
		?>
		<template id="r6-tpl">
			<section class="r6-hero<?php echo $img ? '' : ' is-noimg'; ?>" aria-label="Η συνταγή">
				<div class="r6-hero__crumbs"></div>
				<div class="r6-hero__media">
					<?php if ( $img ) : ?>
						<img class="r6-hero__img" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>" fetchpriority="high">
					<?php endif; ?>
					<div class="r6-hero__shade"></div>
					<div class="r6-hero__text">
						<div class="r6-hero__chips"></div>
						<h1 class="r6-hero__title"><?php echo esc_html( get_the_title( $id ) ); ?></h1>
						<?php if ( $desc ) : ?><p class="r6-hero__lead"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
					</div>
				</div>
				<div class="r6-glance">
					<dl class="r6-glance__stats"><?php echo $st; // phpcs:ignore WordPress.Security.EscapeOutput ?></dl>
					<div class="r6-glance__acts"><button type="button" class="r6-made" aria-pressed="false" hidden><span aria-hidden="true">✓</span> <span data-l>Την έφτιαξα;</span></button></div>
				</div>
			</section>
			<section class="r6-cookbar" aria-label="Λειτουργία μαγειρέματος">
				<div class="r6-cookbar__slot"></div>
				<div class="r6-cookbar__prog">
					<p><b data-r6-step>Βήμα 1</b> <span data-r6-hint>· πατήστε «Μαγειρεύω τώρα» και η οθόνη μένει αναμμένη</span></p>
					<div class="r6-cookbar__bar" role="progressbar" aria-label="Πρόοδος βημάτων" aria-valuemin="0" aria-valuemax="1" aria-valuenow="0"><i></i></div>
				</div>
				<button type="button" class="r6-cookbar__read" hidden><?php echo ellada_rv6_icon( 'sound', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>Διάβασέ μου</span></button>
			</section>
			<div class="r6-ask" role="status" hidden>
				<span class="r6-ask__i" aria-hidden="true">✓</span>
				<span class="r6-ask__t"><b>Καλή όρεξη!</b><span>Το φτιάξατε; Θα μπει στο «Τις έφτιαξα» και στο ταξίδι σας με το πιρούνι.</span></span>
				<button type="button" class="r6-ask__yes">✓ Ναι, την έφτιαξα</button>
				<button type="button" class="r6-ask__no">Όχι ακόμα</button>
			</div>
			<p class="r6-thanks" role="status" hidden>✓ Μπήκε στο «Τις έφτιαξα». Ευχαριστούμε!</p>
			<nav class="r6-jump" aria-label="Ενότητες συνταγής">
				<a href="#r6-ylika" data-sec="ylika">Υλικά</a><a href="#r6-vimata" data-sec="vimata">Βήματα</a><a href="#r6-istoria" data-sec="istoria">Ιστορία</a>
			</nav>
		</template>
		<script>
		(function () {
			var tpl = document.getElementById('r6-tpl'); if (!tpl) return;
			var $ = function (s, r) { return (r || document).querySelector(s); };
			var heroEl = $('[data-id="recipe-hero"]'), body = $('[data-id="recipe-body"]');
			if (!heroEl || !body) return;
			var frag = tpl.content.cloneNode(true);
			var hero = frag.querySelector('.r6-hero'), bar = frag.querySelector('.r6-cookbar'), ask = frag.querySelector('.r6-ask'), thanks = frag.querySelector('.r6-thanks'), jump = frag.querySelector('.r6-jump');

			/* Hero: διαδρομή, ετικέτες, ενέργειες μεταφέρονται (κρατούν τα δικά τους σενάρια) */
			var crumbs = $('.ellada-rcrumbs-row', heroEl) || $('.ellada-rcrumbs', heroEl); if (crumbs) hero.querySelector('.r6-hero__crumbs').appendChild(crumbs);
			var chips = $('.ellada-chips', heroEl); if (chips) hero.querySelector('.r6-hero__chips').appendChild(chips);
			var acts = $('.ellada-ractions', heroEl), slotActs = hero.querySelector('.r6-glance__acts');
			if (acts) slotActs.appendChild(acts);
			var cook = acts && $('[data-ellada-cook]', acts);
			if (cook) bar.querySelector('.r6-cookbar__slot').appendChild(cook);
			var wrap = document.createElement('div'); wrap.className = 'r6-top';
			wrap.appendChild(hero); wrap.appendChild(bar);
			heroEl.parentNode.insertBefore(wrap, heroEl);
			heroEl.remove();

			/* Σημειώσεις κάτω από τα υλικά */
			var aside = $('[data-id="recipe-aside"]'), notes = $('.ellada-notes');
			if (aside && notes) aside.appendChild(notes);
			var ing = $('.ellada-ingredients'); if (ing) ing.id = ing.id || 'r6-ylika';
			var steps = $('.ellada-steps'); if (steps) steps.id = steps.id || 'r6-vimata';
			var bento = $('.r6-bento'); if (bento) bento.id = 'r6-istoria';
			if (!bento) jump.querySelector('[data-sec=istoria]').remove();
			body.parentNode.insertBefore(jump, body);
			// Τα κουτάκια σε όλο το πλάτος, μετά τα υλικά και τα βήματα.
			if (bento) { var wide = document.createElement('div'); wide.className = 'r6-wide'; wide.appendChild(bento); body.parentNode.insertBefore(wide, body.nextSibling); }

			/* «Το έφτιαξα»: κουμπί στην κάρτα + ερώτηση στο τέλος */
			var madeBox = $('.ellada-made'), madeBtn = madeBox && $('.ellada-made__btn', madeBox), r6made = hero.querySelector('.r6-made');
			if (steps) { var after = steps.querySelector('ol') || steps; after.parentNode.insertBefore(thanks, after.nextSibling); after.parentNode.insertBefore(ask, after.nextSibling); }
			function isMade() { return madeBtn && (madeBtn.disabled || madeBtn.classList.contains('is-done')); }
			function paintMade() { if (!madeBtn) return; var on = isMade(); r6made.hidden = false; r6made.setAttribute('aria-pressed', on ? 'true' : 'false'); r6made.classList.toggle('is-on', on); r6made.querySelector('[data-l]').textContent = on ? 'Την έφτιαξα' : 'Την έφτιαξα;'; }
			function markMade() { if (madeBtn && !isMade()) madeBtn.click(); setTimeout(paintMade, 50); ask.hidden = true; thanks.hidden = false; }
			r6made.addEventListener('click', function () { if (!isMade()) markMade(); });
			ask.querySelector('.r6-ask__yes').addEventListener('click', markMade);
			ask.querySelector('.r6-ask__no').addEventListener('click', function () { ask.hidden = true; });
			paintMade(); setTimeout(paintMade, 400);

			/* Κουμπιά της κάρτας: εικονίδιο + λέξη που ανοίγει σαν ακορντεόν (όπως το μενού) */
			var IC = {
				fav: '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
				coll: '<path d="M3.5 7.5A2 2 0 0 1 5.5 5.5h4l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-10Z"/><path d="M12 11v5M9.5 13.5h5"/>',
				print: '<path d="M7 9V3h10v6"/><rect x="3.5" y="9" width="17" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
				share: '<path d="M7 17 17 7M9 7h8v8"/>',
				made: '<path d="m5 12.5 4.2 4.2L19 7"/>'
			};
			function svg(k) { return '<svg class="r6-ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + IC[k] + '</svg>'; }
			function accBtn(b, k) {
				if (!b || b.classList.contains('r6-acc')) return;
				var glyph = b.firstElementChild && b.firstElementChild.getAttribute('aria-hidden') === 'true' ? b.firstElementChild : null;
				var lbl = b.querySelector('[data-fav-label],[data-l]') || (b.lastElementChild && b.lastElementChild !== glyph && b.lastElementChild.tagName === 'SPAN' ? b.lastElementChild : null);
				if (!lbl) { lbl = document.createElement('span'); [].slice.call(b.childNodes).forEach(function (n) { if (n.nodeType === 3 && n.nodeValue.trim()) { lbl.appendChild(document.createTextNode(n.nodeValue.trim())); n.remove(); } }); b.appendChild(lbl); }
				lbl.classList.add('r6-lbl');
				if (glyph) glyph.classList.add('r6-glyph');
				var tmp = document.createElement('span'); tmp.innerHTML = svg(k); var ic = tmp.firstChild;
				if (glyph) glyph.after(ic); else b.insertBefore(ic, b.firstChild);
				b.classList.add('r6-acc'); if (!b.title) b.title = lbl.textContent.trim();
			}
			var A = hero.querySelector('.r6-glance__acts');
			accBtn(r6made, 'made');
			accBtn(A.querySelector('[data-ellada-fav]'), 'fav');
			accBtn(A.querySelector('[data-acct-coll] > button'), 'coll');
			accBtn(A.querySelector('[data-ellada-print]'), 'print');
			accBtn(A.querySelector('[data-ellada-share]'), 'share');

			/* Μπάρα: πρόοδος βημάτων + 🔊 */
			var nums = steps ? [].slice.call(steps.querySelectorAll('button.ellada-steps__n')) : [];
			var stepEl = bar.querySelector('[data-r6-step]'), hint = bar.querySelector('[data-r6-hint]'), fill = bar.querySelector('.r6-cookbar__bar'), wasCooking = false, asked = false;
			function prog() {
				var done = nums.filter(function (b) { return b.getAttribute('aria-pressed') === 'true'; }).length, total = nums.length || 1;
				var cur = nums.findIndex(function (b) { return b.getAttribute('aria-pressed') !== 'true'; });
				var cooking = document.body.classList.contains('ellada-cooking');
				stepEl.textContent = done === total ? 'Όλα τα βήματα έγιναν' : 'Βήμα ' + (cur + 1) + ' από ' + total;
				hint.textContent = cooking ? ' · η οθόνη μένει αναμμένη' : (done ? ' · ' + done + ' έγιναν' : ' · πατήστε «Μαγειρεύω τώρα» και η οθόνη μένει αναμμένη');
				fill.setAttribute('aria-valuemax', total); fill.setAttribute('aria-valuenow', done); fill.querySelector('i').style.width = Math.round(100 * done / total) + '%';
				bar.classList.toggle('is-cooking', cooking);
				if (!asked && !isMade() && ((done === total && nums.length) || (wasCooking && !cooking && done > 0))) { asked = true; ask.hidden = false; }
				wasCooking = cooking;
			}
			document.addEventListener('click', function () { setTimeout(prog, 60); });
			new MutationObserver(prog).observe(document.body, { attributes: true, attributeFilter: ['class'] });
			prog();
			var readBtn = bar.querySelector('.r6-cookbar__read');
			function hookRead() { var s = $('.ellada-ra__start'); if (!s) return false; readBtn.hidden = false; readBtn.addEventListener('click', function () { s.click(); var c = $('.ellada-ra'); if (c) c.scrollIntoView({ behavior: 'smooth', block: 'center' }); }); return true; }
			if (!hookRead()) setTimeout(hookRead, 600);

			/* Κινητό: λωρίδα ενοτήτων — τονίζεται η ενότητα που φαίνεται */
			if ('IntersectionObserver' in window) {
				var map = { ylika: ing, vimata: steps, istoria: bento };
				var io = new IntersectionObserver(function (en) { en.forEach(function (e) { if (!e.isIntersecting) return; var k = Object.keys(map).find(function (x) { return map[x] === e.target; }); jump.querySelectorAll('a').forEach(function (a) { var on = a.getAttribute('data-sec') === k; a.classList.toggle('is-on', on); if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current'); }); }); }, { rootMargin: '-45% 0px -50% 0px' });
				Object.keys(map).forEach(function (k) { if (map[k]) io.observe(map[k]); });
			}
		})();
		</script>
		<script>document.body.classList.add('r6-ready');</script>
		<?php
	},
	60
);

/* ═════════════════════ Εκτύπωση: «κάρτα συνταγής» 1–2 σελίδων (λειτουργία «recipe_print»· προβολή ?ellada_demo=print6) ═════════════════════ */

function ellada_rprint() {
	if ( ! ellada_rv6() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'print6' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'recipe_print' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( is_singular( 'ellada_recipe' ) && ellada_rprint() ) {
			$c[] = 'ellada-rp';
			if ( ! in_array( 'ellada-r6', $c, true ) ) {
				$c[] = 'ellada-r6';
			}
		}
		return $c;
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_rprint() || ! is_singular( 'ellada_recipe' ) ) {
			return;
		}
		$url = wp_parse_url( get_permalink(), PHP_URL_HOST ) . wp_parse_url( get_permalink(), PHP_URL_PATH );
		?>
		<template id="rp-tpl"><div class="rp-head" aria-hidden="true"><img class="rp-head__logo" src="<?php echo esc_url( plugins_url( 'public/logo-print.svg', ELLADA_MAP_FILE ) ); ?>" alt="Ελλάδα στο Πιάτο" width="182" height="52" loading="eager" decoding="sync"><span class="rp-head__url"><?php echo esc_html( rtrim( $url, '/' ) ); ?></span></div></template>
		<script>
		(function () {
			var t = document.getElementById('rp-tpl'), top = document.querySelector('.r6-top');
			if (t && top) top.insertBefore(t.content.firstElementChild.cloneNode(true), top.firstChild);
			// Οι σημειώσεις τυπώνονται μόνο αν έχουν κείμενο.
			function mark() { document.querySelectorAll('.ellada-notes').forEach(function (n) { var ta = n.querySelector('textarea'); n.classList.toggle('rp-has', !!(ta && ta.value.trim())); }); }
			window.addEventListener('beforeprint', mark); mark();
		})();
		</script>
		<?php
	},
	70
);

