<?php
/**
 * Αρχική: ταξιδιωτικός οδηγός, πρόσκληση για συνταγές, δομημένα δεδομένα.
 *
 *   [ellada_home_guide]    Οι περιφέρειες που έχουν κείμενα οδηγού, σε μεγάλες κάρτες με μικρό χάρτη
 *                          (+ κάρτα «Όλος ο οδηγός» με τους αριθμούς). Νέα περιφέρεια με κείμενα → μπαίνει μόνη της.
 *   [ellada_home_submit]   «Έχετε μια συνταγή από τον τόπο σας;» → Επικοινωνία με έτοιμο θέμα
 *   - WebSite (με αναζήτηση) + Organization (λογότυπο) στο <head> της αρχικής
 */

defined( 'ABSPATH' ) || exit;

/** Πόσες σελίδες τόπων ανά επίπεδο είναι δημοσιευμένες. */
function ellada_guide_place_counts() {
	$c = get_transient( 'ellada_guide_place_counts' );
	if ( is_array( $c ) ) {
		return $c;
	}
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT pm.meta_value AS lvl, COUNT(*) AS n FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'map_level'
		 WHERE p.post_type = 'ellada_location' AND p.post_status = 'publish' GROUP BY pm.meta_value",
		ARRAY_A
	);
	$c = array( 'region' => 0, 'prefecture' => 0, 'municipality' => 0, 'settlement' => 0, 'total' => 0 );
	foreach ( (array) $rows as $r ) {
		if ( isset( $c[ $r['lvl'] ] ) ) {
			$c[ $r['lvl'] ] = (int) $r['n'];
			$c['total']    += (int) $r['n'];
		}
	}
	set_transient( 'ellada_guide_place_counts', $c, DAY_IN_SECONDS );
	return $c;
}

/** Σελίδες τόπων ενός επιπέδου (μέσα σε μια περιφέρεια) που έχουν κείμενο οδηγού (guide_intro). */
function ellada_guide_written( $level, $region = '' ) {
	$mq = array(
		array( 'key' => 'map_level', 'value' => $level ),
		array( 'key' => 'guide_intro', 'value' => '', 'compare' => '!=' ),
	);
	if ( $region ) {
		$mq[] = array( 'key' => 'geo_region', 'value' => $region );
	}
	return get_posts(
		array(
			'post_type'      => 'ellada_location',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => $mq, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'no_found_rows'  => true,
		)
	);
}

function ellada_mini_map_svg( $region, $class = 'ellada-hguide__map' ) {
	if ( ! function_exists( 'ellada_regions_mini' ) ) {
		return '';
	}
	$mini = ellada_regions_mini();
	if ( empty( $mini['regions'][ $region ] ) ) {
		return '';
	}
	$paths = '';
	foreach ( $mini['regions'] as $name => $d ) {
		if ( $name !== $region ) {
			$paths .= '<path d="' . esc_attr( $d ) . '"/>';
		}
	}
	return sprintf(
		'<svg class="%1$s" viewBox="%2$s" aria-hidden="true"><g class="is-other">%3$s</g><path class="is-on" d="%4$s"/></svg>',
		esc_attr( $class ),
		esc_attr( $mini['viewBox'] ),
		$paths,
		esc_attr( $mini['regions'][ $region ] )
	);
}

add_shortcode(
	'ellada_home_guide',
	function () {
		if ( function_exists( 'ellada_feature' ) && ! ellada_feature( 'guide' ) ) {
			return '';
		}
		ellada_front_style();
		$cards = '';
		foreach ( ellada_guide_written( 'region' ) as $rid ) {
			$region = (string) get_post_meta( $rid, 'geo_region', true );
			$prefs  = array_map( 'get_the_title', ellada_guide_written( 'prefecture', $region ) );
			$munis  = count( ellada_guide_written( 'municipality', $region ) );
			$intro  = trim( (string) get_post_meta( $rid, 'guide_intro', true ) );
			$first  = $intro ? preg_split( '/(?<=[.!;])\s+/u', preg_replace( '/\s+/u', ' ', $intro ) )[0] : '';
			$cards .= sprintf(
				'<a class="ellada-hguide__card" href="%1$s"><div class="ellada-hguide__text"><p class="ellada-hguide__eyebrow">Ο οδηγός</p><h3 class="ellada-hguide__title">%2$s</h3>%3$s%4$s<span class="ellada-hguide__more">Διαβάστε τον οδηγό <span aria-hidden="true">→</span></span></div>%5$s</a>',
				esc_url( get_permalink( $rid ) ),
				esc_html( get_the_title( $rid ) ),
				$first ? '<p class="ellada-hguide__lead">' . esc_html( $first ) . '</p>' : '',
				$prefs ? '<p class="ellada-hguide__places">' . esc_html( implode( ' · ', $prefs ) ) . ( $munis ? ' — ' . esc_html( sprintf( 1 === $munis ? '%d δήμος' : '%d δήμοι', $munis ) ) : '' ) . '</p>' : '',
				ellada_mini_map_svg( $region )
			);
		}

		$hub = get_page_by_path( 'odigos', OBJECT, 'page' );
		$g   = ellada_guide_place_counts();
		$all = $hub && 'publish' === $hub->post_status
			? sprintf(
				'<a class="ellada-hguide__all" href="%s"><p class="ellada-hguide__eyebrow">Ταξιδιωτικός οδηγός</p><dl><div><dt>Περιφέρειες</dt><dd>%d</dd></div><div><dt>Νομοί</dt><dd>%d</dd></div><div><dt>Δήμοι</dt><dd>%d</dd></div></dl><p class="ellada-hguide__note">Γράφουμε τον οδηγό περιοχή-περιοχή. Κάθε τόπος έχει ήδη τη σελίδα του, με τον χάρτη και τις συνταγές του.</p><span class="ellada-hguide__more">Όλος ο οδηγός <span aria-hidden="true">→</span></span></a>',
				esc_url( get_permalink( $hub ) ),
				$g['region'],
				$g['prefecture'],
				$g['municipality']
			)
			: '';

		return ( $cards || $all ) ? '<div class="ellada-hguide">' . $cards . $all . '</div>' : '';
	}
);

add_action(
	'save_post_ellada_location',
	function () {
		delete_transient( 'ellada_guide_place_counts' );
	}
);

/* ═════════════════════ Στείλτε μας τη συνταγή σας ═════════════════════ */

add_shortcode(
	'ellada_home_submit',
	function () {
		$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
		if ( ! $contact || 'publish' !== $contact->post_status ) {
			return '';
		}
		ellada_front_style();
		$url = get_permalink( $contact );
		return sprintf(
			'<div class="ellada-hsubmit"><div class="ellada-hsubmit__text"><p class="ellada-sh__eyebrow">Από εσάς</p><h2 class="ellada-hsubmit__title">Έχετε μια συνταγή<br><strong>από τον τόπο σας;</strong></h2><p class="ellada-hsubmit__lead">Μια συνταγή της γιαγιάς, ένα φαγητό της γιορτής του χωριού, ένας παραγωγός που αξίζει να τον ξέρουν όλοι. Στείλτε μας τα και θα τα γράψουμε μαζί.</p></div><div class="ellada-hsubmit__actions"><a class="ellada-hsubmit__btn" href="%1$s">Στείλτε μια συνταγή <span aria-hidden="true">→</span></a><a class="ellada-hsubmit__link" href="%2$s">Βάλτε έναν παραγωγό ή μια ταβέρνα στον χάρτη</a></div></div>',
			esc_url( add_query_arg( 'thema', 'syntagi', $url ) . '#form-field-message' ),
			esc_url( add_query_arg( 'thema', 'topos', $url ) . '#form-field-message' )
		);
	}
);

/* ═════════════════════ Δομημένα δεδομένα αρχικής ═════════════════════ */

add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		$name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$home  = home_url( '/' );
		$logo  = get_site_icon_url( 512 );
		$graph = array(
			array(
				'@type'           => 'WebSite',
				'@id'             => $home . '#website',
				'url'             => $home,
				'name'            => $name,
				'inLanguage'      => 'el',
				'publisher'       => array( '@id' => $home . '#organization' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $home . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			),
			array_filter(
				array(
					'@type' => 'Organization',
					'@id'   => $home . '#organization',
					'name'  => $name,
					'url'   => $home,
					'logo'  => $logo ? array( '@type' => 'ImageObject', 'url' => $logo, 'width' => 512, 'height' => 512 ) : null,
					'email' => 'info@elladastopiato.gr',
					'description' => wp_specialchars_decode( get_bloginfo( 'description' ), ENT_QUOTES ),
				)
			),
		);
		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG )
		);
	},
	20
);

/* Το πλάτος της φωτογραφίας του hero εξαρτάται από το ύψος του: το CSS του πλαϊνού σβησίματος το χρειάζεται (--ellada-hero-h). */
add_action(
	'wp_footer',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		echo "<script>(function(){var h=document.querySelector('.elementor-element-home2-hero');if(!h)return;function s(){h.style.setProperty('--ellada-hero-h',h.offsetHeight+'px');}s();window.addEventListener('resize',s,{passive:true});if(window.ResizeObserver)new ResizeObserver(s).observe(h);})();</script>\n";
		// «Ή εξερευνήστε τον χάρτη»: από το hero στην αρχή της ενότητας του χάρτη, με γραμμή σε όλο το πλάτος.
		// Στο hero μένει αόρατο αντίγραφο ίδιου μεγέθους, ώστε το hero να μη μετακινείται.
		echo "<script>(function(){var l=document.querySelector('.elementor-element-home2-hero .s-home2-maplink'),m=document.querySelector('.elementor-element-h3-map');if(!l||!m)return;var ph=l.cloneNode(true);ph.removeAttribute('href');ph.removeAttribute('data-id');ph.setAttribute('aria-hidden','true');ph.classList.add('ellada-maplink-ph');l.parentNode.insertBefore(ph,l);m.insertBefore(l,m.firstChild);m.classList.add('ellada-has-maplink');})();</script>\n";
	},
	40
);
