<?php
/**
 * /odigos/: «Οι 13 περιφέρειες» ως tabs (λειτουργία «guide_tabs»· προεπισκόπηση ?ellada_preview=1).
 *
 * Αριστερά (στο κινητό: λωρίδα που σύρεται) οι περιφέρειες με εικόνα — η φωτογραφία εξωφύλλου (`cover_image`)
 * της σελίδας της περιφέρειας, αλλιώς το σχήμα της στον χάρτη. Δεξιά η επιλεγμένη: κείμενο, αριθμοί,
 * χάρτης σε μεγέθυνση με τους νομούς (links), λίστα νομών και link στον οδηγό της.
 * Χωρίς JavaScript φαίνονται όλες οι περιφέρειες η μία κάτω από την άλλη.
 */

defined( 'ABSPATH' ) || exit;

function ellada_guide_tabs_on() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'guide_tabs' );
}

/** Όρια (viewBox) ενός ή περισσότερων paths «M x,y x,y … Z», με περιθώριο. */
function ellada_gtabs_bbox( array $paths, $pad = 0.08 ) {
	$minx = $miny = INF;
	$maxx = $maxy = -INF;
	foreach ( $paths as $d ) {
		if ( preg_match_all( '/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $d, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $p ) {
				$minx = min( $minx, (float) $p[1] );
				$maxx = max( $maxx, (float) $p[1] );
				$miny = min( $miny, (float) $p[2] );
				$maxy = max( $maxy, (float) $p[2] );
			}
		}
	}
	if ( INF === $minx ) {
		return '0 0 300 260';
	}
	$w  = $maxx - $minx;
	$h  = $maxy - $miny;
	$s  = max( $w, $h * 1.1 ); // λίγο πιο τετράγωνο, για να μη φαίνεται «λωρίδα»
	$cx = $minx + $w / 2;
	$cy = $miny + $h / 2;
	$s *= 1 + 2 * $pad;
	return sprintf( '%.1f %.1f %.1f %.1f', $cx - $s / 2, $cy - $s / 2 / 1.1, $s, $s / 1.1 );
}

function ellada_gtabs_slug( $name ) {
	return 'perifereia-' . substr( md5( $name ), 0, 6 );
}

/**
 * @param array $byreg περιφέρεια → [νομοί]
 * @param array $pages ellada_guide_pages()
 * @param array $munis νομός → αριθμός δήμων
 */
function ellada_guide_tabs_html( $byreg, $pages, $munis ) {
	$mini = function_exists( 'ellada_regions_mini' ) ? ellada_regions_mini() : array();
	$pm   = function_exists( 'ellada_prefectures_mini' ) ? ellada_prefectures_mini() : array();
	if ( empty( $mini['regions'] ) ) {
		return '';
	}
	$tabs   = '';
	$panels = '';
	$first  = '';
	$i      = 0;
	foreach ( $byreg as $region => $prefs ) {
		$rid    = $pages['region'][ $region ] ?? 0;
		$has    = ellada_guide_has_text( $rid );
		$slug   = ellada_gtabs_slug( $region );
		$rmunis = array_sum( array_map( fn( $p ) => $munis[ $p ] ?? 0, $prefs ) );
		$sum    = sprintf( '%d %s · %d δήμοι', count( $prefs ), 1 === count( $prefs ) ? 'νομός' : 'νομοί', $rmunis );
		if ( '' === $first && $has ) {
			$first = $slug;
		}

		/* Εικόνα της καρτέλας */
		$cover = $rid ? (int) get_post_meta( $rid, 'cover_image', true ) : 0;
		$thumb = $cover
			? wp_get_attachment_image( $cover, 'thumbnail', false, array( 'class' => 'ellada-gtabs__photo', 'loading' => 'lazy', 'alt' => '' ) )
			: ( ! empty( $mini['regions'][ $region ] )
				? sprintf( '<svg class="ellada-gtabs__thumb" viewBox="%s"><path class="%s" d="%s"/></svg>', esc_attr( ellada_gtabs_bbox( array( $mini['regions'][ $region ] ), 0.06 ) ), $has ? 'is-on' : 'is-off', esc_attr( $mini['regions'][ $region ] ) )
				: '' );

		$tabs .= sprintf(
			'<button type="button" role="tab" class="ellada-gtabs__tab%1$s" id="%2$s-tab" aria-controls="%2$s" aria-selected="false" tabindex="-1" data-slug="%2$s">'
			. '<span class="ellada-gtabs__img" aria-hidden="true">%3$s</span><span class="ellada-gtabs__txt"><span class="ellada-gtabs__name">%4$s</span><span class="ellada-gtabs__sum">%5$s</span></span>%6$s</button>',
			$has ? ' is-on' : '',
			esc_attr( $slug ),
			$thumb,
			esc_html( $region ),
			esc_html( $sum ),
			$has ? '<span class="ellada-gtabs__dot" title="Με οδηγό"></span>' : ''
		);

		/* Χάρτης της περιφέρειας με τους νομούς */
		$rpaths = array();
		$nomoi  = '';
		foreach ( $prefs as $p ) {
			if ( empty( $pm[ $p ] ) ) {
				continue;
			}
			$rpaths[] = $pm[ $p ];
			$pid      = $pages['prefecture'][ $p ] ?? 0;
			$label    = ellada_guide_prefecture_title( $p );
			$on       = ellada_guide_has_text( $pid );
			$path     = '<path class="' . ( $on ? 'is-on' : 'is-off' ) . '" d="' . esc_attr( $pm[ $p ] ) . '"><title>' . esc_html( $label ) . '</title></path>';
			$nomoi   .= $pid ? '<a href="' . esc_url( get_permalink( $pid ) ) . '" aria-label="' . esc_attr( $label ) . '">' . $path . '</a>' : $path;
		}
		if ( ! $rpaths && ! empty( $mini['regions'][ $region ] ) ) {
			$rpaths[] = $mini['regions'][ $region ];
		}
		$ctx = '';
		foreach ( $mini['regions'] as $name => $d ) {
			if ( $name !== $region ) {
				$ctx .= '<path d="' . esc_attr( $d ) . '"/>';
			}
		}
		$map = sprintf(
			'<svg class="ellada-gtabs__map" viewBox="%1$s" role="group" aria-label="%2$s"><g class="is-ctx">%3$s</g>%4$s</svg>',
			esc_attr( ellada_gtabs_bbox( $rpaths ) ),
			esc_attr( 'Οι νομοί της περιφέρειας ' . $region ),
			$ctx,
			$nomoi
		);

		/* Νομοί */
		$items = '';
		foreach ( $prefs as $p ) {
			$pid    = $pages['prefecture'][ $p ] ?? 0;
			$label  = esc_html( ellada_guide_prefecture_title( $p ) );
			$on     = ellada_guide_has_text( $pid );
			$n      = $munis[ $p ] ?? 0;
			$meta   = $n ? esc_html( sprintf( 1 === $n ? '%d δήμος' : '%d δήμοι', $n ) ) : '';
			$inner  = '<span class="ellada-gtabs__pname">' . $label . '</span>' . ( $on ? '<span class="ellada-gbadge">Οδηγός</span>' : '' ) . '<span class="ellada-gtabs__pmeta">' . $meta . '</span>';
			$items .= '<li class="' . ( $on ? 'is-on' : '' ) . '">' . ( $pid ? '<a href="' . esc_url( get_permalink( $pid ) ) . '">' . $inner . '<span class="ellada-gtabs__arr" aria-hidden="true">→</span></a>' : '<span>' . $inner . '</span>' ) . '</li>';
		}

		$lead = $has ? ellada_guide_first_sentence( $rid ) : 'Ο οδηγός της περιφέρειας γράφεται· στο μεταξύ θα βρείτε τους νομούς και τους δήμους της με τα βασικά.';
		$photo = $cover ? '<figure class="ellada-gtabs__cover">' . wp_get_attachment_image( $cover, 'large', false, array( 'loading' => 'lazy', 'alt' => '' ) ) . '</figure>' : '';

		$panels .= sprintf(
			'<section role="tabpanel" class="ellada-gtabs__panel" id="%1$s" aria-labelledby="%1$s-tab" tabindex="0">'
			. '<div class="ellada-gtabs__info"><p class="ellada-gtabs__eyebrow">Περιφέρεια%2$s</p><h3 class="ellada-gtabs__title">%3$s</h3><p class="ellada-gtabs__lead">%4$s</p>'
			. '<dl class="ellada-gtabs__stats"><div><dt>Νομοί</dt><dd>%5$d</dd></div><div><dt>Δήμοι</dt><dd>%6$d</dd></div></dl>%7$s'
			. '<ul class="ellada-gtabs__prefs">%8$s</ul></div>'
			. '<div class="ellada-gtabs__visual">%9$s%10$s<p class="ellada-gtabs__legend"><span class="is-on"></span> Με οδηγό <span class="is-off"></span> Σε εξέλιξη</p></div>'
			. '</section>',
			esc_attr( $slug ),
			$has ? ' · <span>Με πλήρη οδηγό</span>' : '',
			esc_html( $region ),
			esc_html( $lead ),
			count( $prefs ),
			$rmunis,
			$rid ? '<a class="ellada-gtabs__cta" href="' . esc_url( get_permalink( $rid ) ) . '">' . ( $has ? 'Διαβάστε τον οδηγό' : 'Η σελίδα της περιφέρειας' ) . ' <span aria-hidden="true">→</span></a>' : '',
			$items,
			$photo,
			$map
		);
		++$i;
	}

	$id = 'ellada-gtabs';
	return '<div class="ellada-gtabs" id="' . $id . '" data-first="' . esc_attr( $first ) . '">'
		. '<div class="ellada-gtabs__list" role="tablist" aria-label="Οι 13 περιφέρειες" aria-orientation="vertical">' . $tabs . '</div>'
		. '<div class="ellada-gtabs__panels">' . $panels . '</div></div>'
		. '<script>(function(){var w=document.getElementById("' . $id . '");if(!w)return;w.classList.add("is-js");'
		. 'var tabs=[].slice.call(w.querySelectorAll("[role=tab]")),pans=[].slice.call(w.querySelectorAll("[role=tabpanel]"));'
		. 'function show(s,focus,push){tabs.forEach(function(t){var on=t.dataset.slug===s;t.setAttribute("aria-selected",on?"true":"false");t.tabIndex=on?0:-1;if(on){if(focus)t.focus();'
		. 'var l=t.parentNode;if(getComputedStyle(l).display==="flex"&&l.scrollWidth>l.clientWidth+4)l.scrollTo({left:t.offsetLeft-l.clientWidth/2+t.offsetWidth/2,behavior:"smooth"});}});'
		. 'pans.forEach(function(p){p.hidden=p.id!==s;});if(push&&history.replaceState)history.replaceState(null,"","#"+s);}'
		. 'tabs.forEach(function(t,i){t.addEventListener("click",function(){show(t.dataset.slug,false,true);});'
		. 't.addEventListener("keydown",function(e){var k=e.key,j=-1;if(k==="ArrowDown"||k==="ArrowRight")j=(i+1)%tabs.length;else if(k==="ArrowUp"||k==="ArrowLeft")j=(i-1+tabs.length)%tabs.length;'
		. 'else if(k==="Home")j=0;else if(k==="End")j=tabs.length-1;if(j>-1){e.preventDefault();show(tabs[j].dataset.slug,true,true);}});});'
		. 'var h=location.hash.slice(1);show(pans.some(function(p){return p.id===h})?h:(w.dataset.first||tabs[0].dataset.slug),false,false);'
		. '})();</script>';
}

/* ═════════════════════ Η σελίδα «Χάρτης» είναι ανενεργή (22/11) ═════════════════════ */

/**
 * Η σελίδα /xartis/ είναι σε πρόχειρο (ο χάρτης υπάρχει στην αρχική και στον οδηγό).
 * Οι παλιοί σύνδεσμοι πάνε μόνιμα (301) στον χάρτη της αρχικής, μαζί με το ?topos= αν υπάρχει.
 * Επαναφορά: δημοσίευση της σελίδας + μενού (το redirect ισχύει μόνο όσο η σελίδα δεν είναι δημοσιευμένη).
 */
add_action(
	'template_redirect',
	function () {
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( 'xartis' !== $path ) {
			return;
		}
		$page = get_page_by_path( 'xartis', OBJECT, 'page' );
		if ( $page && 'publish' === $page->post_status ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$topos = isset( $_GET['topos'] ) ? sanitize_text_field( wp_unslash( $_GET['topos'] ) ) : '';
		$to    = $topos ? add_query_arg( 'topos', rawurlencode( $topos ), home_url( '/' ) ) : home_url( '/' );
		wp_safe_redirect( $to . '#xartis', 301 );
		exit;
	},
	1
);

