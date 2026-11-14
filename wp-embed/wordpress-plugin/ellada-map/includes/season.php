<?php
/**
 * Η εποχή στην αρχική.
 *
 *   [ellada_season]             «Τι τρώμε τον …»: τα φρούτα και τα λαχανικά του μήνα, η γιορτή ή η νηστεία
 *                               αυτών των ημερών και οι συνταγές που έχουν υλικά της εποχής. Αλλάζει μόνη της.
 *   [ellada_home_collections]   Οι συλλογές, μόνο όταν υπάρχει τουλάχιστον μία δημοσιευμένη.
 */

defined( 'ABSPATH' ) || exit;

/** Λέξεις για αναγνώριση στα υλικά (χωρίς τόνους, αρχή λέξης). */
function ellada_season_produce() {
	return array(
		'Πορτοκάλια'   => 'πορτοκαλ',
		'Μανταρίνια'   => 'μανταριν',
		'Λεμόνια'      => 'λεμον',
		'Ακτινίδια'    => 'ακτινιδ',
		'Μήλα'         => 'μηλα|μηλο',
		'Αχλάδια'      => 'αχλαδ',
		'Ρόδια'        => 'ροδι',
		'Κυδώνια'      => 'κυδων',
		'Κάστανα'      => 'καστα',
		'Σταφύλια'     => 'σταφυλ',
		'Σύκα'         => 'συκα|συκο',
		'Καρπούζι'     => 'καρπουζ',
		'Πεπόνι'       => 'πεπον',
		'Ροδάκινα'     => 'ροδακιν',
		'Νεκταρίνια'   => 'νεκταριν',
		'Δαμάσκηνα'    => 'δαμασκην',
		'Κεράσια'      => 'κερασ',
		'Βερίκοκα'     => 'βερικοκ',
		'Φράουλες'     => 'φραουλ',
		'Μούσμουλα'    => 'μουσμουλ',
		'Λάχανο'       => 'λαχανο|λαχανου',
		'Κουνουπίδι'   => 'κουνουπιδ',
		'Μπρόκολο'     => 'μπροκολ',
		'Πράσα'        => 'πρασα|πρασο',
		'Σέλινο'       => 'σελιν',
		'Σπανάκι'      => 'σπανακ',
		'Καρότα'       => 'καροτ',
		'Χόρτα'        => 'χορτα|χορτο',
		'Αγκινάρες'    => 'αγκιναρ',
		'Κουκιά'       => 'κουκι',
		'Αρακάς'       => 'αρακα',
		'Σπαράγγια'    => 'σπαραγγ',
		'Μαρούλια'     => 'μαρουλ',
		'Φρέσκα κρεμμυδάκια' => 'κρεμμυδακ|φρεσκο κρεμμυδ',
		'Άνηθος'       => 'ανηθ',
		'Σέσκουλα'     => 'σεσκουλ',
		'Κολοκυθάκια'  => 'κολοκυθακ',
		'Φασολάκια'    => 'φασολακ',
		'Αγγούρια'     => 'αγγουρ',
		'Νέες πατάτες' => 'πατατ',
		'Ντομάτες'     => 'ντοματ|τοματ',
		'Μελιτζάνες'   => 'μελιτζαν',
		'Πιπεριές'     => 'πιπερι[αε]',
		'Μπάμιες'      => 'μπαμι',
		'Καλαμπόκι'    => 'καλαμποκ',
		'Κολοκύθα'     => 'κολοκυθα|κολοκυθ(?!ακ)',
		'Μανιτάρια'    => 'μανιταρ',
	);
}

/** Ημερολόγιο εποχής για την Ελλάδα: [φρούτα, λαχανικά] ανά μήνα. */
function ellada_season_calendar() {
	return array(
		1  => array( array( 'Πορτοκάλια', 'Μανταρίνια', 'Λεμόνια', 'Ακτινίδια', 'Μήλα', 'Αχλάδια' ), array( 'Λάχανο', 'Κουνουπίδι', 'Μπρόκολο', 'Πράσα', 'Σέλινο', 'Σπανάκι', 'Καρότα', 'Χόρτα' ) ),
		2  => array( array( 'Πορτοκάλια', 'Μανταρίνια', 'Λεμόνια', 'Ακτινίδια', 'Μήλα' ), array( 'Λάχανο', 'Κουνουπίδι', 'Μπρόκολο', 'Πράσα', 'Σπανάκι', 'Αγκινάρες', 'Καρότα', 'Χόρτα' ) ),
		3  => array( array( 'Πορτοκάλια', 'Λεμόνια', 'Ακτινίδια', 'Μήλα' ), array( 'Αγκινάρες', 'Κουκιά', 'Αρακάς', 'Σπανάκι', 'Μαρούλια', 'Φρέσκα κρεμμυδάκια', 'Άνηθος', 'Σέσκουλα' ) ),
		4  => array( array( 'Φράουλες', 'Πορτοκάλια', 'Λεμόνια' ), array( 'Αγκινάρες', 'Κουκιά', 'Αρακάς', 'Σπαράγγια', 'Μαρούλια', 'Φρέσκα κρεμμυδάκια', 'Άνηθος' ) ),
		5  => array( array( 'Φράουλες', 'Κεράσια', 'Μούσμουλα', 'Βερίκοκα' ), array( 'Αρακάς', 'Κολοκυθάκια', 'Φασολάκια', 'Αγγούρια', 'Νέες πατάτες', 'Αγκινάρες' ) ),
		6  => array( array( 'Κεράσια', 'Βερίκοκα', 'Ροδάκινα', 'Καρπούζι', 'Πεπόνι' ), array( 'Ντομάτες', 'Αγγούρια', 'Κολοκυθάκια', 'Φασολάκια', 'Μελιτζάνες', 'Πιπεριές' ) ),
		7  => array( array( 'Καρπούζι', 'Πεπόνι', 'Ροδάκινα', 'Νεκταρίνια', 'Δαμάσκηνα', 'Σύκα' ), array( 'Ντομάτες', 'Μελιτζάνες', 'Πιπεριές', 'Μπάμιες', 'Κολοκυθάκια', 'Αγγούρια' ) ),
		8  => array( array( 'Σύκα', 'Σταφύλια', 'Καρπούζι', 'Πεπόνι', 'Ροδάκινα', 'Δαμάσκηνα' ), array( 'Ντομάτες', 'Μελιτζάνες', 'Πιπεριές', 'Μπάμιες', 'Φασολάκια', 'Καλαμπόκι' ) ),
		9  => array( array( 'Σταφύλια', 'Σύκα', 'Μήλα', 'Αχλάδια', 'Δαμάσκηνα', 'Ρόδια' ), array( 'Ντομάτες', 'Μελιτζάνες', 'Πιπεριές', 'Μπάμιες', 'Κολοκύθα' ) ),
		10 => array( array( 'Ρόδια', 'Κυδώνια', 'Μήλα', 'Αχλάδια', 'Σταφύλια', 'Κάστανα' ), array( 'Κολοκύθα', 'Λάχανο', 'Κουνουπίδι', 'Μπρόκολο', 'Σπανάκι', 'Μανιτάρια' ) ),
		11 => array( array( 'Μανταρίνια', 'Πορτοκάλια', 'Ρόδια', 'Κυδώνια', 'Κάστανα', 'Ακτινίδια' ), array( 'Λάχανο', 'Κουνουπίδι', 'Μπρόκολο', 'Πράσα', 'Σπανάκι', 'Κολοκύθα', 'Χόρτα' ) ),
		12 => array( array( 'Πορτοκάλια', 'Μανταρίνια', 'Λεμόνια', 'Ακτινίδια', 'Ρόδια', 'Κάστανα' ), array( 'Λάχανο', 'Κουνουπίδι', 'Μπρόκολο', 'Πράσα', 'Σέλινο', 'Σπανάκι', 'Χόρτα' ) ),
	);
}

/** Ορθόδοξο Πάσχα (ιουλιανός υπολογισμός + 13 ημέρες, ισχύει 1900–2099). */
function ellada_orthodox_easter( $year ) {
	$a     = $year % 4;
	$b     = $year % 7;
	$c     = $year % 19;
	$d     = ( 19 * $c + 15 ) % 30;
	$e     = ( 2 * $a + 4 * $b - $d + 34 ) % 7;
	$month = intdiv( $d + $e + 114, 31 );
	$day   = ( ( $d + $e + 114 ) % 31 ) + 1;
	$date  = new DateTimeImmutable( sprintf( '%04d-%02d-%02d', $year, $month, $day ), wp_timezone() );
	return $date->modify( '+13 days' );
}

/** Η γιορτή ή η νηστεία αυτών των ημερών: [τίτλος, κείμενο, θέματα συλλογών] ή null. */
function ellada_season_occasion( DateTimeImmutable $now ) {
	$m      = (int) $now->format( 'n' );
	$d      = (int) $now->format( 'j' );
	$today  = $now->setTime( 0, 0 );
	$easter = ellada_orthodox_easter( (int) $now->format( 'Y' ) );
	$diff   = (int) round( ( $today->getTimestamp() - $easter->getTimestamp() ) / DAY_IN_SECONDS );

	if ( ( 12 === $m && $d >= 25 ) || ( 1 === $m && $d <= 6 ) ) {
		return array( 'Γιορτινό τραπέζι', 'Χριστούγεννα, Πρωτοχρονιά και Θεοφάνεια: η εποχή για γεμιστά, μελομακάρονα, κουραμπιέδες και βασιλόπιτα.', array( 'christmas', 'winter' ) );
	}
	if ( ( 11 === $m && $d >= 15 ) || ( 12 === $m && $d <= 24 ) ) {
		return array( 'Σαραντάμερο', 'Από τις 15 Νοεμβρίου ξεκινά η νηστεία των Χριστουγέννων. Ώρα για όσπρια, λαδερά και πίτες με χόρτα, και για την προετοιμασία του γιορτινού τραπεζιού.', array( 'christmas', 'fasting', 'winter' ) );
	}
	if ( $diff >= -70 && $diff <= -49 ) {
		return array( 'Απόκριες', 'Οι εβδομάδες πριν από τη Σαρακοστή: ψητά στα κάρβουνα, πίτες και γλυκά, με αποκορύφωμα την Τσικνοπέμπτη.', array( 'winter' ) );
	}
	if ( $diff >= -48 && $diff <= -8 ) {
		return array( 'Σαρακοστή', 'Από την Καθαρά Δευτέρα ως το Πάσχα: λαγάνα, ταραμοσαλάτα, όσπρια, θαλασσινά και λαδερά.', array( 'fasting', 'spring' ) );
	}
	if ( $diff >= -7 && $diff <= -1 ) {
		return array( 'Μεγάλη Εβδομάδα', 'Κουλούρια, τσουρέκια και κόκκινα αυγά: οι ετοιμασίες για το πασχαλινό τραπέζι.', array( 'easter', 'fasting' ) );
	}
	if ( $diff >= 0 && $diff <= 7 ) {
		return array( 'Πάσχα', 'Αρνί ή κατσίκι στη σούβλα, κοκορέτσι, μαγειρίτσα και τσουρέκι.', array( 'easter', 'spring' ) );
	}
	if ( 8 === $m && $d <= 15 ) {
		return array( 'Δεκαπενταύγουστος', 'Η νηστεία της Παναγίας: λαδερά με τα λαχανικά του καλοκαιριού, ως τη γιορτή της 15ης Αυγούστου.', array( 'fasting', 'summer' ) );
	}
	return null;
}

function ellada_season_of_month( $m ) {
	if ( in_array( $m, array( 12, 1, 2 ), true ) ) {
		return 'winter';
	}
	if ( $m <= 5 ) {
		return 'spring';
	}
	return $m <= 8 ? 'summer' : 'autumn';
}

/** Συνταγές με υλικά της εποχής (κρατιέται 12 ώρες, ξαναϋπολογίζεται όταν αλλάζει μια συνταγή). */
function ellada_season_recipes( $month, $limit = 3 ) {
	$key   = 'ellada_season_recipes_' . $month;
	$cache = get_transient( $key );
	if ( is_array( $cache ) ) {
		return array_slice( $cache, 0, $limit );
	}
	list( $fruits, $veg ) = ellada_season_calendar()[ $month ];
	$stems = array_intersect_key( ellada_season_produce(), array_flip( array_merge( $fruits, $veg ) ) );
	$pat   = '/(?:^|[^\p{L}])(?:' . implode( '|', $stems ) . ')/u';
	$found = array();
	$ids   = get_posts(
		array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		$text = '';
		foreach ( array_filter( (array) get_field( 'ingredients', $id ), 'is_array' ) as $row ) {
			$text .= ' ' . ( $row['item'] ?? '' );
		}
		$text = ellada_search_norm( $text . ' ' . get_the_title( $id ) );
		if ( preg_match_all( $pat, $text, $m ) ) {
			$found[ $id ] = count( $m[0] );
		}
	}
	arsort( $found );
	$found = array_keys( $found );
	set_transient( $key, $found, 12 * HOUR_IN_SECONDS );
	return array_slice( $found, 0, $limit );
}
add_action(
	'save_post_ellada_recipe',
	function () {
		for ( $i = 1; $i <= 12; $i++ ) {
			delete_transient( 'ellada_season_recipes_' . $i );
		}
	}
);

/** Η πρώτη δημοσιευμένη συλλογή με ένα από τα θέματα (με σειρά προτίμησης). */
function ellada_collection_by_theme( array $themes ) {
	foreach ( $themes as $theme ) {
		$ids = get_posts(
			array(
				'post_type'      => 'ellada_collection',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'theme', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $theme, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'no_found_rows'  => true,
			)
		);
		if ( $ids ) {
			return (int) $ids[0];
		}
	}
	return 0;
}

add_shortcode(
	'ellada_season',
	function ( $atts ) {
		$a      = shortcode_atts( array( 'num' => 'auto' ), $atts, 'ellada_season' );
		$now    = current_datetime();
		$m      = (int) $now->format( 'n' );
		$months = array( 1 => 'Ιανουάριο', 'Φεβρουάριο', 'Μάρτιο', 'Απρίλιο', 'Μάιο', 'Ιούνιο', 'Ιούλιο', 'Αύγουστο', 'Σεπτέμβριο', 'Οκτώβριο', 'Νοέμβριο', 'Δεκέμβριο' );
		$short  = array( 1 => 'Ιαν', 'Φεβ', 'Μάρ', 'Απρ', 'Μάι', 'Ιούν', 'Ιούλ', 'Αύγ', 'Σεπ', 'Οκτ', 'Νοέ', 'Δεκ' );
		list( $fruits, $veg ) = ellada_season_calendar()[ $m ];
		ellada_front_style();

		$chips = function ( $items ) {
			return '<ul class="ellada-season__chips">' . implode( '', array_map( fn( $i ) => '<li>' . esc_html( $i ) . '</li>', $items ) ) . '</ul>';
		};
		$cal = sprintf(
			'<div class="ellada-season__cal"><p class="ellada-season__month" aria-hidden="true">%s</p><div class="ellada-season__groups"><div><h3>Φρούτα</h3>%s</div><div><h3>Λαχανικά</h3>%s</div></div></div>',
			esc_html( mb_strtoupper( strtr( $short[ $m ], array( 'ά' => 'α', 'έ' => 'ε', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ' ) ), 'UTF-8' ) ),
			$chips( $fruits ),
			$chips( $veg )
		);

		$side = '';
		$occ  = ellada_season_occasion( $now );
		if ( $occ ) {
			$col   = ellada_collection_by_theme( $occ[2] );
			$side .= sprintf(
				'<div class="ellada-season__occ"><p class="ellada-sh__eyebrow">Αυτές τις μέρες</p><h3>%s</h3><p>%s</p>%s</div>',
				esc_html( $occ[0] ),
				esc_html( $occ[1] ),
				$col ? sprintf( '<a class="ellada-sh__more" href="%s">%s <span aria-hidden="true">→</span></a>', esc_url( get_permalink( $col ) ), esc_html( get_the_title( $col ) ) ) : ''
			);
		} else {
			$col = ellada_collection_by_theme( array( ellada_season_of_month( $m ) ) );
			if ( $col ) {
				$side .= sprintf( '<div class="ellada-season__occ"><p class="ellada-sh__eyebrow">Συλλογή της εποχής</p><h3><a href="%s">%s</a></h3></div>', esc_url( get_permalink( $col ) ), esc_html( get_the_title( $col ) ) );
			}
		}
		$recipes = ellada_season_recipes( $m, 2 );
		if ( $recipes ) {
			$side .= '<div class="ellada-season__recipes"><p class="ellada-sh__eyebrow">Με υλικά της εποχής</p><div class="ellada-cards">' . implode( '', array_map( 'ellada_recipe_card', $recipes ) ) . '</div></div>';
		}

		$head = do_shortcode( sprintf( '[ellada_section_head num="%s" eyebrow="Εποχή" title="Τι τρώμε<br><strong>τον %s</strong>"]', esc_attr( $a['num'] ), $months[ $m ] ) );
		return $head . '<div class="ellada-season' . ( $side ? '' : ' is-solo' ) . '">' . $cal . ( $side ? '<div class="ellada-season__side">' . $side . '</div>' : '' ) . '</div>';
	}
);

add_shortcode(
	'ellada_home_collections',
	function ( $atts ) {
		$a = shortcode_atts( array( 'num' => 'auto', 'limit' => 3 ), $atts, 'ellada_home_collections' );
		if ( ! (int) wp_count_posts( 'ellada_collection' )->publish ) {
			return '';
		}
		$cards = do_shortcode( sprintf( '[ellada_cards type="collection" source="latest" limit="%d"]', absint( $a['limit'] ) ) );
		if ( '' === trim( $cards ) ) {
			return '';
		}
		$head = do_shortcode( sprintf( '[ellada_section_head num="%s" eyebrow="Συλλογές" title="Μαγειρέψτε<br><strong>με θέμα</strong>" more_url="%s" more_label="Όλες οι συλλογές"]', esc_attr( $a['num'] ), esc_url( get_post_type_archive_link( 'ellada_collection' ) ) ) );
		return '<div class="ellada-homecol">' . $head . $cards . '</div>';
	}
);
