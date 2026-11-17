<?php
/**
 * Ταξιδιωτικός οδηγός v2 (λειτουργία «guide_v2» στο ellada_features· προεπισκόπηση ?ellada_preview=1).
 *
 *   [ellada_guide_hub2]   Η σελίδα /odigos/: hero με αναζήτηση «Πού πάτε;» και χάρτη που πατιέται,
 *                         «Ξεκινήστε από εδώ» (όσοι τόποι έχουν κείμενο), όλοι οι τόποι ανά 13 περιφέρειες,
 *                         πρόσκληση «Ξέρετε έναν τόπο;».
 *   [ellada_guide_hero]   Στο hero κάθε σελίδας τόπου: είδος/γονική περιοχή + μικρός χάρτης (ή φωτογραφία εξωφύλλου).
 *   - σελίδες χωρίς κείμενο: «Ο οδηγός γράφεται» αντί για άδεια σελίδα
 *   - BreadcrumbList για το Google στις σελίδες τόπων
 *
 * Τα περιγράμματα: public/regions-mini.json και public/prefectures-mini.json (ίδια προβολή, viewBox 0 0 300 260).
 */

defined( 'ABSPATH' ) || exit;

function ellada_guide_v2() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'guide_v2' );
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( ellada_guide_v2() && ( is_singular( 'ellada_location' ) || is_page( 'odigos' ) ) ) {
			$classes[] = 'ellada-guide-v2';
		}
		return $classes;
	}
);

/* ═════════════════════ Δεδομένα ═════════════════════ */

function ellada_prefectures_mini() {
	static $data = null;
	if ( null === $data ) {
		$file = ELLADA_MAP_DIR . 'public/prefectures-mini.json';
		$data = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	return is_array( $data ) ? $data : array();
}

function ellada_guide_has_text( $id ) {
	return $id && '' !== trim( (string) get_post_meta( $id, 'guide_intro', true ) );
}

/** Η πρώτη πρόταση του κειμένου του οδηγού. */
function ellada_guide_first_sentence( $id ) {
	$intro = trim( (string) get_post_meta( $id, 'guide_intro', true ) );
	if ( ! $intro ) {
		return '';
	}
	$parts = preg_split( '/(?<=[.!;])\s+/u', preg_replace( '/\s+/u', ' ', $intro ) );
	return $parts[0];
}

/** Περιφέρεια → [νομοί (όνομα χάρτη)], με αλφαβητική σειρά τίτλου. */
function ellada_guide_region_prefs() {
	$out = array_fill_keys( ellada_map_region_names(), array() );
	foreach ( ellada_map_prefecture_regions() as $pref => $region ) {
		if ( isset( $out[ $region ] ) ) {
			$out[ $region ][] = $pref;
		}
	}
	foreach ( $out as &$prefs ) {
		usort( $prefs, fn( $a, $b ) => strcoll( ellada_guide_prefecture_title( $a ), ellada_guide_prefecture_title( $b ) ) );
	}
	return $out;
}

/** Νομός → πόσοι δήμοι έχουν σελίδα. */
function ellada_guide_muni_counts() {
	$n = array();
	foreach ( array_keys( ellada_guide_pages()['municipality'] ) as $key ) {
		$pref       = explode( '|', $key )[0];
		$n[ $pref ] = ( $n[ $pref ] ?? 0 ) + 1;
	}
	return $n;
}

/** Μικρός χάρτης: όλες οι περιφέρειες γκρι, η περιοχή (περιφέρεια ή νομός) πορτοκαλί. */
function ellada_guide_mini_svg( $region = '', $pref = '', $class = 'ellada-ghero__map' ) {
	$mini = function_exists( 'ellada_regions_mini' ) ? ellada_regions_mini() : array();
	if ( empty( $mini['regions'] ) ) {
		return '';
	}
	$on = '';
	if ( $pref ) {
		$pm = ellada_prefectures_mini();
		$on = $pm[ $pref ] ?? '';
	}
	if ( ! $on && $region ) {
		$on = $mini['regions'][ $region ] ?? '';
	}
	$paths = '';
	foreach ( $mini['regions'] as $d ) {
		$paths .= '<path d="' . esc_attr( $d ) . '"/>';
	}
	return sprintf(
		'<svg class="%1$s" viewBox="%2$s" aria-hidden="true"><g class="is-other">%3$s</g>%4$s</svg>',
		esc_attr( $class ),
		esc_attr( $mini['viewBox'] ),
		$paths,
		$on ? '<path class="is-on" d="' . esc_attr( $on ) . '"/>' : ''
	);
}

/* ═════════════════════ Σελίδα /odigos/ ═════════════════════ */

add_shortcode(
	'ellada_guide_hub2',
	function () {
		ellada_front_style();
		$pages   = ellada_guide_pages();
		$mini    = function_exists( 'ellada_regions_mini' ) ? ellada_regions_mini() : array();
		$counts  = function_exists( 'ellada_guide_place_counts' ) ? ellada_guide_place_counts() : array( 'region' => 13, 'prefecture' => 74, 'municipality' => 326 );
		$places  = count( ellada_guide_places() );
		$munis   = ellada_guide_muni_counts();
		$byreg   = ellada_guide_region_prefs();
		$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );

		/* Hero: κείμενο, αριθμοί, αναζήτηση + χάρτης που πατιέται */
		$svg = '';
		if ( ! empty( $mini['regions'] ) ) {
			$links = '';
			foreach ( $mini['regions'] as $name => $d ) {
				$rid    = $pages['region'][ $name ] ?? 0;
				$on     = ellada_guide_has_text( $rid );
				$path   = '<path d="' . esc_attr( $d ) . '"/>';
				$label  = $name . ( $on ? ' — οδηγός' : '' );
				$links .= $rid
					? sprintf( '<a href="%s" class="%s" aria-label="%s"><title>%s</title>%s</a>', esc_url( get_permalink( $rid ) ), $on ? 'is-on' : 'is-off', esc_attr( $label ), esc_html( $label ), $path )
					: '<g class="is-off">' . $path . '</g>';
			}
			$svg = '<figure class="ellada-ghub__map"><svg viewBox="' . esc_attr( $mini['viewBox'] ) . '" role="group" aria-label="Χάρτης των περιφερειών">' . $links . '</svg>'
				. '<figcaption><span class="is-on"></span> Με πλήρη οδηγό <span class="is-off"></span> Σε εξέλιξη</figcaption></figure>';
		}

		$html  = '<div class="ellada-ghub">';
		$html .= '<section class="ellada-ghub__hero"><div class="ellada-ghub__wrap ellada-ghub__hero-in"><div class="ellada-ghub__hero-text">';
		$html .= '<p class="ellada-ghub__eyebrow">Ταξιδιωτικός οδηγός</p><h1 class="ellada-ghub__title">Η Ελλάδα<br><strong>τόπο τόπο</strong></h1>';
		$html .= '<p class="ellada-ghub__lead">Κάθε περιφέρεια, νομός και δήμος έχει τη σελίδα του: τι να δείτε, τι να φάτε, γιατί να πάτε. Γράφουμε τον οδηγό περιοχή-περιοχή, ξεκινώντας από την Κρήτη.</p>';
		$html .= sprintf(
			'<dl class="ellada-ghub__stats"><div><dt>Περιφέρειες</dt><dd>%d</dd></div><div><dt>Νομοί</dt><dd>%d</dd></div><div><dt>Δήμοι</dt><dd>%d</dd></div><div><dt>Πόλεις &amp; χωριά</dt><dd>%s</dd></div></dl>',
			$counts['region'],
			$counts['prefecture'],
			$counts['municipality'],
			esc_html( number_format( $places, 0, ',', '.' ) )
		);
		$html .= '<form class="ellada-gsearch" role="search" action="' . esc_url( home_url( '/' ) ) . '" data-endpoint="' . esc_url( rest_url( 'ellada/v1/search' ) ) . '">'
			. '<label for="ellada-gsearch-q">Πού πάτε;</label><div class="ellada-gsearch__box">'
			. '<input id="ellada-gsearch-q" type="search" name="s" autocomplete="off" placeholder="π.χ. Σφακιά, Νάξος, Μέτσοβο" aria-controls="ellada-gsearch-res">'
			. '<button type="submit" aria-label="Αναζήτηση">→</button></div><div class="ellada-gsearch__res" id="ellada-gsearch-res" aria-live="polite"></div></form>';
		$html .= '</div>' . $svg . '</div></section>';

		/* Ξεκινήστε από εδώ */
		$start = '';
		foreach ( $pages['region'] as $name => $rid ) {
			if ( ! ellada_guide_has_text( $rid ) ) {
				continue;
			}
			$written = array_values( array_filter( $byreg[ $name ] ?? array(), fn( $p ) => ellada_guide_has_text( $pages['prefecture'][ $p ] ?? 0 ) ) );
			$start  .= sprintf(
				'<a class="ellada-hguide__card ellada-ghub__big" href="%1$s"><div class="ellada-hguide__text"><p class="ellada-hguide__eyebrow">Περιφέρεια</p><h3 class="ellada-hguide__title">%2$s</h3><p class="ellada-hguide__lead">%3$s</p><span class="ellada-hguide__more">Διαβάστε τον οδηγό <span aria-hidden="true">→</span></span></div>%4$s</a>',
				esc_url( get_permalink( $rid ) ),
				esc_html( get_the_title( $rid ) ),
				esc_html( ellada_guide_first_sentence( $rid ) ),
				ellada_guide_mini_svg( $name, '', 'ellada-hguide__map' )
			);
			if ( $written ) {
				$start .= '<div class="ellada-ghub__subs">';
				foreach ( $written as $p ) {
					$pid    = $pages['prefecture'][ $p ];
					$start .= sprintf(
						'<a class="ellada-ghub__sub" href="%1$s">%2$s<div><p class="ellada-ghub__sub-eyebrow">Νομός · %3$s</p><h4>%4$s</h4><p>%5$s</p></div></a>',
						esc_url( get_permalink( $pid ) ),
						ellada_guide_mini_svg( $name, $p, 'ellada-ghub__sub-map' ),
						esc_html( sprintf( 1 === ( $munis[ $p ] ?? 0 ) ? '%d δήμος' : '%d δήμοι', $munis[ $p ] ?? 0 ) ),
						esc_html( get_the_title( $pid ) ),
						esc_html( ellada_guide_first_sentence( $pid ) )
					);
				}
				$start .= '</div>';
			}
		}
		if ( $start ) {
			$html .= '<section class="ellada-ghub__sec"><div class="ellada-ghub__wrap"><p class="ellada-ghub__eyebrow is-dark">Έτοιμοι οδηγοί</p><h2 class="ellada-ghub__h">Ξεκινήστε<br><strong>από εδώ</strong></h2><div class="ellada-ghub__start">' . $start . '</div></div></section>';
		}

		/* Όλοι οι τόποι ανά περιφέρεια */
		$all = '';
		foreach ( $byreg as $region => $prefs ) {
			$rid    = $pages['region'][ $region ] ?? 0;
			$rmunis = array_sum( array_map( fn( $p ) => $munis[ $p ] ?? 0, $prefs ) );
			$items  = '';
			foreach ( $prefs as $p ) {
				$pid    = $pages['prefecture'][ $p ] ?? 0;
				$label  = esc_html( ellada_guide_prefecture_title( $p ) );
				$badge  = ellada_guide_has_text( $pid ) ? '<span class="ellada-gbadge">Οδηγός</span>' : '';
				$n      = $munis[ $p ] ?? 0;
				$meta   = $n ? '<span class="ellada-gall__n">' . esc_html( sprintf( 1 === $n ? '%d δήμος' : '%d δήμοι', $n ) ) . '</span>' : '';
				$items .= '<li>' . ( $pid ? '<a href="' . esc_url( get_permalink( $pid ) ) . '"><span>' . $label . '</span>' . $badge . $meta . '</a>' : '<span>' . $label . '</span>' ) . '</li>';
			}
			$all .= sprintf(
				'<details class="ellada-gall__reg%1$s"><summary><span class="ellada-gall__name">%2$s</span>%3$s<span class="ellada-gall__sum">%4$s</span></summary><ul>%5$s</ul>%6$s</details>',
				ellada_guide_has_text( $rid ) ? ' is-on' : '',
				esc_html( $region ),
				ellada_guide_has_text( $rid ) ? '<span class="ellada-gbadge">Οδηγός</span>' : '',
				esc_html( sprintf( '%d %s · %d δήμοι', count( $prefs ), 1 === count( $prefs ) ? 'νομός' : 'νομοί', $rmunis ) ),
				$items,
				$rid ? '<a class="ellada-gall__more" href="' . esc_url( get_permalink( $rid ) ) . '">Η περιφέρεια ' . esc_html( $region ) . ' <span aria-hidden="true">→</span></a>' : ''
			);
		}
		$html .= '<section class="ellada-ghub__sec is-alt"><div class="ellada-ghub__wrap"><p class="ellada-ghub__eyebrow is-dark">Όλοι οι τόποι</p><h2 class="ellada-ghub__h">Οι 13<br><strong>περιφέρειες</strong></h2><div class="ellada-gall">' . $all . '</div></div></section>';

		/* Ξέρετε έναν τόπο; */
		if ( $contact && 'publish' === $contact->post_status ) {
			$html .= sprintf(
				'<section class="ellada-ghub__sec"><div class="ellada-ghub__wrap"><div class="ellada-hsubmit"><div class="ellada-hsubmit__text"><p class="ellada-sh__eyebrow">Από εσάς</p><h2 class="ellada-hsubmit__title">Ξέρετε έναν τόπο<br><strong>καλύτερα από εμάς;</strong></h2><p class="ellada-hsubmit__lead">Ένα χωριό που αξίζει, μια ταβέρνα, ένας παραγωγός, ένα πανηγύρι. Γράψτε μας και θα μπει στον οδηγό.</p></div><div class="ellada-hsubmit__actions"><a class="ellada-hsubmit__btn" href="%1$s">Γράψτε μας <span aria-hidden="true">→</span></a><a class="ellada-hsubmit__link" href="%2$s">Ή στείλτε μια συνταγή από τον τόπο σας</a></div></div></div></section>',
				esc_url( add_query_arg( 'thema', 'topos', get_permalink( $contact ) ) . '#form-field-message' ),
				esc_url( add_query_arg( 'thema', 'syntagi', get_permalink( $contact ) ) . '#form-field-message' )
			);
		}
		$html .= '</div>';

		// Αναζήτηση καθώς γράφετε + στο desktop ανοιχτές οι περιφέρειες.
		$html .= '<script>(function(){'
			. 'if(window.matchMedia("(min-width: 1025px)").matches)document.querySelectorAll(".ellada-gall__reg").forEach(function(d){d.open=true});'
			. 'var f=document.querySelector(".ellada-gsearch");if(!f)return;var i=f.querySelector("input"),out=f.querySelector(".ellada-gsearch__res"),t,last="";'
			. 'function esc(s){return String(s).replace(/[&<>"]/g,function(c){return{"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c]})}'
			. 'function row(x){return "<li><a href=\""+esc(x.url)+"\"><strong>"+esc(x.title)+"</strong><span>"+esc(x.meta||"")+"</span></a></li>"}'
			. 'i.addEventListener("input",function(){clearTimeout(t);var q=i.value.trim();if(q.length<2){out.innerHTML="";return}t=setTimeout(function(){last=q;'
			. 'fetch(f.dataset.endpoint+"?q="+encodeURIComponent(q)).then(function(r){return r.json()}).then(function(d){if(q!==last)return;var a=(d.areas||[]).concat(d.places||[]);'
			. 'out.innerHTML=a.length?"<ul>"+a.slice(0,8).map(row).join("")+"</ul>":"<p>Δεν βρέθηκε τόπος με αυτό το όνομα.</p>"}).catch(function(){})},250)});'
			. '})();</script>';
		return $html;
	}
);

/* ═════════════════════ Σελίδες τόπων: hero ═════════════════════ */

add_shortcode(
	'ellada_guide_hero',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( ! ellada_guide_v2() || 'ellada_location' !== get_post_type( $id ) ) {
			return '';
		}
		ellada_front_style();
		$c      = ellada_guide_ctx( $id );
		$munis  = ellada_guide_muni_counts();
		$byreg  = ellada_guide_region_prefs();
		$labels = array( 'region' => 'Περιφέρεια', 'prefecture' => 'Νομός', 'municipality' => 'Δήμος', 'settlement' => 'Τόπος' );
		$bits   = array( $labels[ $c['level'] ] ?? '' );
		if ( 'region' === $c['level'] ) {
			$prefs  = $byreg[ $c['region'] ] ?? array();
			$bits[] = sprintf( 1 === count( $prefs ) ? '%d νομός' : '%d νομοί', count( $prefs ) );
			$bits[] = sprintf( '%d δήμοι', array_sum( array_map( fn( $p ) => $munis[ $p ] ?? 0, $prefs ) ) );
		} elseif ( 'prefecture' === $c['level'] ) {
			$bits[] = $c['region'];
			$bits[] = sprintf( 1 === ( $munis[ $c['pref'] ] ?? 0 ) ? '%d δήμος' : '%d δήμοι', $munis[ $c['pref'] ] ?? 0 );
		} else {
			$bits[] = ellada_guide_prefecture_title( $c['pref'] );
			$bits[] = $c['region'];
		}
		$cover = (int) get_post_meta( $id, 'cover_image', true );
		$img   = $cover ? wp_get_attachment_image_url( $cover, 'full' ) : '';
		$style = $img ? '<style>.s-loc-hero{background:linear-gradient(90deg,rgba(10,10,10,.92) 0%,rgba(10,10,10,.7) 45%,rgba(10,10,10,.25) 100%),url("' . esc_url( $img ) . '") center/cover no-repeat!important}</style>' : '';
		$map   = $img ? '' : ellada_guide_mini_svg( $c['region'], 'region' === $c['level'] ? '' : $c['pref'] );
		return $style . '<div class="ellada-ghero"><p class="ellada-ghero__meta">' . esc_html( implode( ' · ', array_filter( $bits ) ) ) . '</p>' . $map . '</div>';
	}
);

/* ═════════════════════ Σελίδες χωρίς κείμενο: «Ο οδηγός γράφεται» ═════════════════════ */

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_guide' !== $tag || ! ellada_guide_v2() ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_location' !== get_post_type( $id ) || ellada_guide_has_text( $id ) ) {
			return $output;
		}
		$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
		$example = '';
		foreach ( ellada_guide_pages()['region'] as $rid ) {
			if ( $rid !== $id && ellada_guide_has_text( $rid ) ) {
				$example = sprintf( '<a class="ellada-gpending__link" href="%s">Δείτε έναν έτοιμο οδηγό: %s <span aria-hidden="true">→</span></a>', esc_url( get_permalink( $rid ) ), esc_html( get_the_title( $rid ) ) );
				break;
			}
		}
		$block = sprintf(
			'<div class="ellada-gpending"><p class="ellada-gpending__eyebrow">Σε εξέλιξη</p><h2 class="ellada-gpending__title">%1$s: ο οδηγός γράφεται</h2><p>Γράφουμε τον οδηγό περιοχή-περιοχή: τι να δείτε, τι να φάτε, γιατί να έρθετε. Μέχρι τότε, εδώ θα βρείτε τους τόπους της περιοχής και τις συνταγές της.</p><div class="ellada-gpending__actions">%2$s%3$s</div></div>',
			esc_html( get_the_title( $id ) ),
			$contact ? sprintf( '<a class="ellada-gpending__btn" href="%s">Ξέρετε την περιοχή; Γράψτε μας <span aria-hidden="true">→</span></a>', esc_url( add_query_arg( 'thema', 'topos', get_permalink( $contact ) ) . '#form-field-message' ) ) : '',
			$example
		);
		// Μετά το «Με μια ματιά».
		$facts = ellada_guide_facts_html( ellada_guide_ctx( $id ) );
		$pos   = $facts ? strpos( $output, $facts ) : false;
		return false === $pos ? $block . $output : substr_replace( $output, $facts . $block, $pos, strlen( $facts ) );
	},
	20,
	3
);

/* ═════════════════════ Google: διαδρομή στις σελίδες τόπων ═════════════════════ */

add_action(
	'wp_head',
	function () {
		if ( ! ellada_guide_v2() || ! is_singular( 'ellada_location' ) ) {
			return;
		}
		$id    = get_queried_object_id();
		$c     = ellada_guide_ctx( $id );
		$items = array( array( 'Ταξιδιωτικός οδηγός', home_url( '/odigos/' ) ) );
		if ( $c['region'] && 'region' !== $c['level'] && ( $p = ellada_guide_page( 'region', $c['region'] ) ) ) {
			$items[] = array( $c['region'], get_permalink( $p ) );
		}
		if ( $c['pref'] && in_array( $c['level'], array( 'municipality', 'settlement' ), true ) && ( $p = ellada_guide_page( 'prefecture', $c['pref'] ) ) ) {
			$items[] = array( ellada_guide_prefecture_title( $c['pref'] ), get_permalink( $p ) );
		}
		if ( $c['muni'] && 'settlement' === $c['level'] && ( $p = ellada_guide_page( 'municipality', $c['pref'] . '|' . $c['muni'] ) ) ) {
			$items[] = array( ellada_guide_muni_title( $c['muni'] ), get_permalink( $p ) );
		}
		$items[] = array( get_the_title( $id ), get_permalink( $id ) );
		$list    = array();
		foreach ( $items as $i => $it ) {
			$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $it[0], 'item' => $it[1] );
		}
		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG )
		);
	},
	20
);
