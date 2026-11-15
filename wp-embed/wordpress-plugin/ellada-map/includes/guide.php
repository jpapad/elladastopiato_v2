<?php
/**
 * Ταξιδιωτικός οδηγός: κάθε περιφέρεια, νομός, δήμος και πόλη / χωριό έχει τη σελίδα του.
 *
 *   [ellada_guide_crumbs]   Ελλάδα › Περιφέρεια › Νομός › Δήμος › Χωριό (στην κορυφή της σελίδας)
 *   [ellada_guide]          Με μια ματιά, εισαγωγή, «Γιατί να πάτε», τι να δείτε, πού να φάτε,
 *                           γιορτές, οι μικρότερες περιοχές που περιέχει, σύνδεσμος στον χάρτη
 *   [ellada_guide_hub]      Τα γεωγραφικά διαμερίσματα με τους νομούς τους (σελίδα «Ταξιδιωτικός οδηγός»)
 *
 * Οι οικισμοί έρχονται από το assets/geo/places.json (© OpenStreetMap contributors, ODbL) και
 * τα στοιχεία «με μια ματιά» από το assets/geo/areas.json (παράγονται στο build του plugin).
 *
 * Όλα εμφανίζονται όταν είναι ενεργή η λειτουργία «guide» (ellada_features). Ο διαχειριστής
 * τα βλέπει νωρίτερα με ?ellada_preview=1.
 */

defined( 'ABSPATH' ) || exit;

/* ═════════════════════ Ονόματα ═════════════════════ */

/** Νομοί (περιφερειακές ενότητες): όνομα του χάρτη → όνομα για τίτλους. */
function ellada_guide_prefecture_names() {
	return array(
		'ΑΡΤΑΣ' => 'Άρτα', 'ΘΕΣΠΡΩΤΙΑΣ' => 'Θεσπρωτία', 'ΙΩΑΝΝΙΝΩΝ' => 'Ιωάννινα', 'ΠΡΕΒΕΖΑΣ' => 'Πρέβεζα',
		'ΔΡΑΜΑΣ' => 'Δράμα', 'ΕΒΡΟΥ' => 'Έβρος', 'ΘΑΣΟΥ' => 'Θάσος', 'ΚΑΒΑΛΑΣ' => 'Καβάλα', 'ΞΑΝΘΗΣ' => 'Ξάνθη', 'ΡΟΔΟΠΗΣ' => 'Ροδόπη',
		'ΑΝΑΤΟΛΙΚΗΣ ΑΤΤΙΚΗΣ' => 'Ανατολική Αττική', 'ΒΟΡΕΙΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ' => 'Βόρειος Τομέας Αθηνών', 'ΔΥΤΙΚΗΣ ΑΤΤΙΚΗΣ' => 'Δυτική Αττική',
		'ΔΥΤΙΚΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ' => 'Δυτικός Τομέας Αθηνών', 'ΚΕΝΤΡΙΚΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ' => 'Κεντρικός Τομέας Αθηνών', 'ΝΗΣΩΝ' => 'Νήσοι Αττικής',
		'ΝΟΤΙΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ' => 'Νότιος Τομέας Αθηνών', 'ΠΕΙΡΑΙΩΣ' => 'Πειραιάς',
		'ΙΚΑΡΙΑΣ' => 'Ικαρία', 'ΛΕΣΒΟΥ' => 'Λέσβος', 'ΛΗΜΝΟΥ' => 'Λήμνος', 'ΣΑΜΟΥ' => 'Σάμος', 'ΧΙΟΥ' => 'Χίος',
		'ΑΙΤΩΛΟΑΚΑΡΝΑΝΙΑΣ' => 'Αιτωλοακαρνανία', 'ΑΧΑΪΑΣ' => 'Αχαΐα', 'ΗΛΕΙΑΣ' => 'Ηλεία',
		'ΓΡΕΒΕΝΩΝ' => 'Γρεβενά', 'ΚΑΣΤΟΡΙΑΣ' => 'Καστοριά', 'ΚΟΖΑΝΗΣ' => 'Κοζάνη', 'ΦΛΩΡΙΝΑΣ' => 'Φλώρινα',
		'ΚΑΡΔΙΤΣΑΣ' => 'Καρδίτσα', 'ΛΑΡΙΣΑΣ' => 'Λάρισα', 'ΜΑΓΝΗΣΙΑΣ' => 'Μαγνησία', 'ΣΠΟΡΑΔΩΝ' => 'Σποράδες', 'ΤΡΙΚΑΛΩΝ' => 'Τρίκαλα',
		'ΖΑΚΥΝΘΟΥ' => 'Ζάκυνθος', 'ΙΘΑΚΗΣ' => 'Ιθάκη', 'ΚΕΡΚΥΡΑΣ' => 'Κέρκυρα', 'ΚΕΦΑΛΛΗΝΙΑΣ' => 'Κεφαλονιά', 'ΛΕΥΚΑΔΑΣ' => 'Λευκάδα',
		'ΒΟΙΩΤΙΑΣ' => 'Βοιωτία', 'ΕΥΒΟΙΑΣ' => 'Εύβοια', 'ΕΥΡΥΤΑΝΙΑΣ' => 'Ευρυτανία', 'ΦΘΙΩΤΙΔΑΣ' => 'Φθιώτιδα', 'ΦΩΚΙΔΑΣ' => 'Φωκίδα',
		'ΗΜΑΘΙΑΣ' => 'Ημαθία', 'ΘΕΣΣΑΛΟΝΙΚΗΣ' => 'Θεσσαλονίκη', 'ΚΙΛΚΙΣ' => 'Κιλκίς', 'ΠΕΛΛΑΣ' => 'Πέλλα', 'ΠΙΕΡΙΑΣ' => 'Πιερία',
		'ΣΕΡΡΩΝ' => 'Σέρρες', 'ΧΑΛΚΙΔΙΚΗΣ' => 'Χαλκιδική',
		'ΗΡΑΚΛΕΙΟΥ' => 'Ηράκλειο', 'ΛΑΣΙΘΙΟΥ' => 'Λασίθι', 'ΡΕΘΥΜΝΟΥ' => 'Ρέθυμνο', 'ΧΑΝΙΩΝ' => 'Χανιά',
		'ΑΝΔΡΟΥ' => 'Άνδρος', 'ΘΗΡΑΣ' => 'Θήρα (Σαντορίνη)', 'ΚΑΛΥΜΝΟΥ' => 'Κάλυμνος', 'ΚΑΡΠΑΘΟΥ' => 'Κάρπαθος', 'ΚΕΑΣ - ΚΥΘΝΟΥ' => 'Κέα - Κύθνος',
		'ΚΩ' => 'Κως', 'ΜΗΛΟΥ' => 'Μήλος', 'ΜΥΚΟΝΟΥ' => 'Μύκονος', 'ΝΑΞΟΥ' => 'Νάξος', 'ΠΑΡΟΥ' => 'Πάρος', 'ΡΟΔΟΥ' => 'Ρόδος',
		'ΣΥΡΟΥ' => 'Σύρος', 'ΤΗΝΟΥ' => 'Τήνος',
		'ΑΡΓΟΛΙΔΑΣ' => 'Αργολίδα', 'ΑΡΚΑΔΙΑΣ' => 'Αρκαδία', 'ΚΟΡΙΝΘΙΑΣ' => 'Κορινθία', 'ΛΑΚΩΝΙΑΣ' => 'Λακωνία', 'ΜΕΣΣΗΝΙΑΣ' => 'Μεσσηνία',
	);
}

/** Γεωγραφικά διαμερίσματα → νομοί (με τη σειρά που εμφανίζονται). */
function ellada_guide_departments() {
	return array(
		'Μακεδονία'      => array( 'ΘΕΣΣΑΛΟΝΙΚΗΣ', 'ΧΑΛΚΙΔΙΚΗΣ', 'ΗΜΑΘΙΑΣ', 'ΠΕΛΛΑΣ', 'ΠΙΕΡΙΑΣ', 'ΚΙΛΚΙΣ', 'ΣΕΡΡΩΝ', 'ΔΡΑΜΑΣ', 'ΚΑΒΑΛΑΣ', 'ΘΑΣΟΥ', 'ΚΟΖΑΝΗΣ', 'ΚΑΣΤΟΡΙΑΣ', 'ΦΛΩΡΙΝΑΣ', 'ΓΡΕΒΕΝΩΝ' ),
		'Θράκη'          => array( 'ΞΑΝΘΗΣ', 'ΡΟΔΟΠΗΣ', 'ΕΒΡΟΥ' ),
		'Ήπειρος'        => array( 'ΙΩΑΝΝΙΝΩΝ', 'ΑΡΤΑΣ', 'ΠΡΕΒΕΖΑΣ', 'ΘΕΣΠΡΩΤΙΑΣ' ),
		'Θεσσαλία'       => array( 'ΛΑΡΙΣΑΣ', 'ΜΑΓΝΗΣΙΑΣ', 'ΤΡΙΚΑΛΩΝ', 'ΚΑΡΔΙΤΣΑΣ', 'ΣΠΟΡΑΔΩΝ' ),
		'Στερεά Ελλάδα'  => array( 'ΚΕΝΤΡΙΚΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ', 'ΒΟΡΕΙΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ', 'ΝΟΤΙΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ', 'ΔΥΤΙΚΟΥ ΤΟΜΕΑ ΑΘΗΝΩΝ', 'ΠΕΙΡΑΙΩΣ', 'ΑΝΑΤΟΛΙΚΗΣ ΑΤΤΙΚΗΣ', 'ΔΥΤΙΚΗΣ ΑΤΤΙΚΗΣ', 'ΒΟΙΩΤΙΑΣ', 'ΕΥΒΟΙΑΣ', 'ΦΘΙΩΤΙΔΑΣ', 'ΦΩΚΙΔΑΣ', 'ΕΥΡΥΤΑΝΙΑΣ', 'ΑΙΤΩΛΟΑΚΑΡΝΑΝΙΑΣ' ),
		'Πελοπόννησος'   => array( 'ΑΡΓΟΛΙΔΑΣ', 'ΑΡΚΑΔΙΑΣ', 'ΚΟΡΙΝΘΙΑΣ', 'ΛΑΚΩΝΙΑΣ', 'ΜΕΣΣΗΝΙΑΣ', 'ΑΧΑΪΑΣ', 'ΗΛΕΙΑΣ' ),
		'Ιόνια Νησιά'    => array( 'ΚΕΡΚΥΡΑΣ', 'ΛΕΥΚΑΔΑΣ', 'ΚΕΦΑΛΛΗΝΙΑΣ', 'ΙΘΑΚΗΣ', 'ΖΑΚΥΝΘΟΥ' ),
		'Νησιά Αιγαίου'  => array( 'ΛΕΣΒΟΥ', 'ΛΗΜΝΟΥ', 'ΧΙΟΥ', 'ΣΑΜΟΥ', 'ΙΚΑΡΙΑΣ', 'ΣΥΡΟΥ', 'ΑΝΔΡΟΥ', 'ΤΗΝΟΥ', 'ΜΥΚΟΝΟΥ', 'ΚΕΑΣ - ΚΥΘΝΟΥ', 'ΜΗΛΟΥ', 'ΠΑΡΟΥ', 'ΝΑΞΟΥ', 'ΘΗΡΑΣ', 'ΡΟΔΟΥ', 'ΚΩ', 'ΚΑΛΥΜΝΟΥ', 'ΚΑΡΠΑΘΟΥ', 'ΝΗΣΩΝ' ),
		'Κρήτη'          => array( 'ΧΑΝΙΩΝ', 'ΡΕΘΥΜΝΟΥ', 'ΗΡΑΚΛΕΙΟΥ', 'ΛΑΣΙΘΙΟΥ' ),
	);
}

function ellada_guide_department_of( $prefecture ) {
	foreach ( ellada_guide_departments() as $dep => $prefs ) {
		if ( in_array( $prefecture, $prefs, true ) ) {
			return $dep;
		}
	}
	return '';
}

function ellada_guide_prefecture_title( $pref ) {
	$names = ellada_guide_prefecture_names();
	return isset( $names[ $pref ] ) ? $names[ $pref ] : mb_convert_case( mb_strtolower( $pref, 'UTF-8' ), MB_CASE_TITLE, 'UTF-8' );
}

/** «ΑγίουΕυστρατίου» → «Αγίου Ευστρατίου». */
function ellada_guide_prettify( $name ) {
	return preg_replace( '/(\p{Ll})(\p{Lu})/u', '$1 $2', (string) $name );
}

function ellada_guide_muni_title( $muni ) {
	$special = array(
		'Άθως'             => 'Άγιον Όρος',
		'Μεγίστη'          => 'Δήμος Μεγίστης',
		'Τροιζηνία-Μέθανα' => 'Δήμος Τροιζηνίας-Μεθάνων',
	);
	return isset( $special[ $muni ] ) ? $special[ $muni ] : 'Δήμος ' . ellada_guide_prettify( $muni );
}

/* ═════════════════════ Δεδομένα ═════════════════════ */

function ellada_guide_json( $file ) {
	static $cache = array();
	if ( ! isset( $cache[ $file ] ) ) {
		$path            = ELLADA_MAP_DIR . 'assets/geo/' . $file;
		$cache[ $file ]  = file_exists( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	return $cache[ $file ];
}

function ellada_guide_areas() {
	return ellada_guide_json( 'areas.json' );
}

/** Οικισμοί ως πίνακες: id, name, name_en, type, population, population_year, municipality, prefecture, region, lat, lon. */
function ellada_guide_places() {
	$d = ellada_guide_json( 'places.json' );
	return isset( $d['rows'] ) ? $d['rows'] : array();
}

function ellada_guide_place( $id ) {
	$id = (int) $id;
	foreach ( ellada_guide_places() as $r ) {
		if ( (int) $r[0] === $id ) {
			return $r;
		}
	}
	return null;
}

function ellada_guide_place_types() {
	return array(
		'c' => 'Πόλη',
		't' => 'Κωμόπολη',
		'v' => 'Χωριό',
		'h' => 'Οικισμός',
	);
}

/** Η διεύθυνση του χάρτη ανοιχτού σε έναν δήμο ή οικισμό. */
function ellada_guide_map_url( $pref, $muni, $id = '' ) {
	return add_query_arg( 'topos', rawurlencode( $pref . '|' . $muni . '|' . $id ), home_url( '/xartis/' ) );
}

/**
 * Όλες οι δημοσιευμένες σελίδες του οδηγού, ανά επίπεδο και τιμή:
 * region[Κρήτη], prefecture[ΧΑΝΙΩΝ], municipality[ΧΑΝΙΩΝ|Χανίων], settlement[31376615] → id.
 */
function ellada_guide_pages() {
	static $pages = null;
	if ( null !== $pages ) {
		return $pages;
	}
	$pages = get_transient( 'ellada_guide_pages' );
	if ( is_array( $pages ) ) {
		return $pages;
	}
	$pages = array(
		'region'       => array(),
		'prefecture'   => array(),
		'municipality' => array(),
		'settlement'   => array(),
	);
	$ids   = get_posts(
		array(
			'post_type'        => 'ellada_location',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	foreach ( $ids as $id ) {
		$level = (string) get_post_meta( $id, 'map_level', true );
		if ( 'region' === $level ) {
			$v = (string) get_post_meta( $id, 'geo_region', true );
		} elseif ( 'prefecture' === $level ) {
			$v = (string) get_post_meta( $id, 'geo_prefecture', true );
		} elseif ( 'municipality' === $level ) {
			$v = (string) get_post_meta( $id, 'geo_municipality', true );
		} elseif ( 'settlement' === $level ) {
			$parts = explode( '|', (string) get_post_meta( $id, 'geo_settlement', true ) );
			$v     = isset( $parts[2] ) ? $parts[2] : '';
		} else {
			continue;
		}
		if ( '' !== $v && ! isset( $pages[ $level ][ $v ] ) ) {
			$pages[ $level ][ $v ] = (int) $id;
		}
	}
	set_transient( 'ellada_guide_pages', $pages, DAY_IN_SECONDS );
	return $pages;
}

function ellada_guide_page( $level, $value ) {
	$p = ellada_guide_pages();
	return isset( $p[ $level ][ (string) $value ] ) ? $p[ $level ][ (string) $value ] : 0;
}

function ellada_guide_flush( $post_id ) {
	if ( 'ellada_location' === get_post_type( $post_id ) ) {
		delete_transient( 'ellada_guide_pages' );
	}
}
add_action( 'save_post', 'ellada_guide_flush' );
add_action( 'deleted_post', 'ellada_guide_flush' );
add_action( 'trashed_post', 'ellada_guide_flush' );
add_action( 'transition_post_status', function ( $new, $old, $post ) { ellada_guide_flush( $post->ID ); }, 10, 3 );

/** Το πλαίσιο μιας σελίδας του οδηγού: επίπεδο, περιφέρεια, νομός, δήμος (όνομα), οικισμός. */
function ellada_guide_ctx( $id ) {
	$area = ellada_location_area( $id );
	$ctx  = array(
		'level'  => $area['level'] ? $area['level'] : 'region',
		'region' => $area['region'],
		'pref'   => $area['prefecture'],
		'muni'   => '',
		'place'  => null,
	);
	if ( $area['municipality'] ) {
		$parts       = explode( '|', $area['municipality'] );
		$ctx['muni'] = isset( $parts[1] ) ? $parts[1] : $parts[0];
	}
	if ( 'settlement' === $ctx['level'] && ! empty( $area['settlement'] ) ) {
		$ctx['place'] = ellada_guide_place( $area['settlement'] );
	}
	return $ctx;
}

/* ═════════════════════ Διαδρομή (breadcrumbs) ═════════════════════ */

add_shortcode(
	'ellada_guide_crumbs',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( 'ellada_location' !== get_post_type( $id ) || ! ellada_feature( 'guide' ) ) {
			return '';
		}
		ellada_front_style();
		$c     = ellada_guide_ctx( $id );
		$items = array( array( 'Ελλάδα', home_url( '/odigos/' ) ) );
		$add   = function ( $label, $level, $value ) use ( &$items, $id ) {
			$page    = ellada_guide_page( $level, $value );
			$items[] = array( $label, $page && $page !== $id ? get_permalink( $page ) : '' );
		};
		if ( $c['region'] && 'region' !== $c['level'] ) {
			$add( $c['region'], 'region', $c['region'] );
		}
		if ( $c['pref'] && in_array( $c['level'], array( 'municipality', 'settlement' ), true ) ) {
			$add( ellada_guide_prefecture_title( $c['pref'] ), 'prefecture', $c['pref'] );
		}
		if ( $c['muni'] && 'settlement' === $c['level'] ) {
			$add( ellada_guide_muni_title( $c['muni'] ), 'municipality', $c['pref'] . '|' . $c['muni'] );
		}
		$items[] = array( get_the_title( $id ), '' );

		$html = '<nav class="ellada-crumbs" aria-label="Διαδρομή"><ol>';
		$last = count( $items ) - 1;
		foreach ( $items as $i => list( $label, $url ) ) {
			$html .= '<li>' . ( $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' : '<span' . ( $i === $last ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</span>' ) . '</li>';
		}
		$dep = $c['pref'] ? ellada_guide_department_of( $c['pref'] ) : '';
		if ( 'region' === $c['level'] ) {
			$deps = array();
			foreach ( ellada_map_prefecture_regions() as $p => $r ) {
				if ( $r === $c['region'] ) {
					$deps[ ellada_guide_department_of( $p ) ] = true;
				}
			}
			$dep = implode( ' · ', array_filter( array_keys( $deps ) ) );
		}
		$html .= '</ol>' . ( $dep ? '<span class="ellada-crumbs__dep">' . esc_html( $dep ) . '</span>' : '' ) . '</nav>';
		return $html;
	}
);

/* ═════════════════════ Ο οδηγός μιας περιοχής ═════════════════════ */

function ellada_guide_num( $n ) {
	return number_format_i18n( (int) $n );
}

/** «Με μια ματιά». */
function ellada_guide_facts_html( $c ) {
	$areas = ellada_guide_areas();
	$facts = array();
	$types = ellada_guide_place_types();
	if ( 'settlement' === $c['level'] && $c['place'] ) {
		$r     = $c['place'];
		$facts = array(
			'Είδος'    => $types[ $r[3] ] ?? '',
			'Δήμος'    => ellada_guide_prettify( $r[6] ),
			'Νομός'    => ellada_guide_prefecture_title( $r[7] ),
			'Κάτοικοι' => $r[4] ? '≈ ' . ellada_guide_num( $r[4] ) . ( $r[5] ? ' (' . $r[5] . ')' : '' ) : '',
		);
	} elseif ( 'municipality' === $c['level'] ) {
		$a     = $areas['municipalities'][ $c['pref'] . '|' . $c['muni'] ] ?? array();
		$facts = array(
			'Νομός'             => ellada_guide_prefecture_title( $c['pref'] ),
			'Έκταση'            => ! empty( $a['km2'] ) ? ellada_guide_num( $a['km2'] ) . ' τ.χλμ.' : '',
			'Οικισμοί'          => ! empty( $a['settlements'] ) ? ellada_guide_num( $a['settlements'] ) : '',
			'Μεγαλύτερος τόπος' => $a['largest'] ?? '',
		);
	} elseif ( 'prefecture' === $c['level'] ) {
		$a     = $areas['prefectures'][ $c['pref'] ] ?? array();
		$facts = array(
			'Περιφέρεια'      => $c['region'],
			'Έκταση'          => ! empty( $a['km2'] ) ? ellada_guide_num( $a['km2'] ) . ' τ.χλμ.' : '',
			'Δήμοι'           => $a['municipalities'] ?? '',
			'Οικισμοί'        => ! empty( $a['settlements'] ) ? ellada_guide_num( $a['settlements'] ) : '',
			'Μεγαλύτερη πόλη' => $a['largest'] ?? '',
		);
	} else {
		$a     = $areas['regions'][ $c['region'] ] ?? array();
		$facts = array(
			'Έκταση'          => ! empty( $a['km2'] ) ? ellada_guide_num( $a['km2'] ) . ' τ.χλμ.' : '',
			'Νομοί'           => $a['prefectures'] ?? '',
			'Δήμοι'           => $a['municipalities'] ?? '',
			'Οικισμοί'        => ! empty( $a['settlements'] ) ? ellada_guide_num( $a['settlements'] ) : '',
			'Μεγαλύτερη πόλη' => $a['largest'] ?? '',
		);
	}
	$facts = array_filter( $facts, 'strlen' );
	if ( ! $facts ) {
		return '';
	}
	$html = '<section class="ellada-glance"><h2 class="ellada-glance__h">Με μια ματιά</h2><dl>';
	foreach ( $facts as $k => $v ) {
		$html .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
	}
	return $html . '</dl></section>';
}

/** Τοπικά σημεία της περιοχής (για χωριό: σε ακτίνα 5 χλμ.). */
function ellada_guide_points( $id, $c ) {
	if ( 'settlement' === $c['level'] && $c['place'] ) {
		$lat = (float) $c['place'][9];
		$lon = (float) $c['place'][10];
		$out = array();
		foreach ( get_posts( array( 'post_type' => 'ellada_point', 'post_status' => 'publish', 'numberposts' => 500, 'fields' => 'ids' ) ) as $pid ) {
			$plat = (float) get_post_meta( $pid, 'lat', true );
			$plon = (float) get_post_meta( $pid, 'lng', true );
			if ( ! $plat ) {
				continue;
			}
			$d = 12742 * asin( sqrt( pow( sin( deg2rad( $plat - $lat ) / 2 ), 2 ) + cos( deg2rad( $lat ) ) * cos( deg2rad( $plat ) ) * pow( sin( deg2rad( $plon - $lon ) / 2 ), 2 ) ) );
			if ( $d <= 5 ) {
				$out[ $pid ] = $d;
			}
		}
		asort( $out );
		return array_keys( $out );
	}
	$mq = ellada_area_meta_query( $id );
	if ( ! $mq ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'   => 'ellada_point',
			'post_status' => 'publish',
			'numberposts' => 200,
			'fields'      => 'ids',
			'orderby'     => 'title',
			'order'       => 'ASC',
			'meta_query'  => $mq, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
}

function ellada_guide_places_html( $id, $c ) {
	$ids = ellada_guide_points( $id, $c );
	if ( ! $ids ) {
		return '';
	}
	$groups = array(
		'Τι να δείτε'                  => array( 'sight', 'beach', 'museum', 'trail' ),
		'Πού να φάτε και να ψωνίσετε' => array( 'restaurant', 'market', 'producer', 'winery', 'product' ),
		'Γιορτές και πανηγύρια'        => array( 'festival' ),
		'Ακόμα'                        => array( 'other' ),
	);
	$by = array();
	foreach ( $ids as $pid ) {
		$by[ ellada_select_value( get_post_meta( $pid, 'point_type', true ) ) ][] = $pid;
	}
	$html = '';
	foreach ( $groups as $title => $types ) {
		$list = array();
		foreach ( $types as $t ) {
			$list = array_merge( $list, $by[ $t ] ?? array() );
		}
		if ( ! $list ) {
			continue;
		}
		$html .= '<section class="ellada-gsec"><h2 class="ellada-h">' . esc_html( $title ) . '</h2><div class="ellada-points">';
		foreach ( $list as $pid ) {
			$card = ellada_point_card( $pid );
			$when = trim( (string) get_post_meta( $pid, 'event_when', true ) );
			if ( $when ) {
				$card = str_replace( '<h3 class="ellada-point__title">', '<p class="ellada-point__when">' . esc_html( $when ) . '</p><h3 class="ellada-point__title">', $card );
			}
			$html .= $card;
		}
		$html .= '</div></section>';
	}
	return $html;
}

/** Οι μικρότερες περιοχές που περιέχει η σελίδα. */
function ellada_guide_children_html( $id, $c ) {
	$items = array();
	$title = '';
	$after = '';
	if ( 'region' === $c['level'] ) {
		$title = 'Οι νομοί';
		$areas = ellada_guide_areas()['prefectures'] ?? array();
		foreach ( ellada_map_prefecture_regions() as $pref => $region ) {
			if ( $region !== $c['region'] ) {
				continue;
			}
			$page    = ellada_guide_page( 'prefecture', $pref );
			$items[] = array( ellada_guide_prefecture_title( $pref ), $page ? get_permalink( $page ) : '', isset( $areas[ $pref ]['municipalities'] ) ? sprintf( '%d δήμοι', $areas[ $pref ]['municipalities'] ) : '' );
		}
	} elseif ( 'prefecture' === $c['level'] ) {
		$title = 'Οι δήμοι';
		$areas = ellada_guide_areas()['municipalities'] ?? array();
		foreach ( $areas as $key => $a ) {
			list( $pref, $muni ) = explode( '|', $key );
			if ( $pref !== $c['pref'] ) {
				continue;
			}
			$page    = ellada_guide_page( 'municipality', $key );
			$items[] = array( ellada_guide_prettify( $muni ), $page ? get_permalink( $page ) : ellada_guide_map_url( $pref, $muni ), $a['largest'] ? 'με κέντρο ' . $a['largest'] : '' );
		}
	} elseif ( 'municipality' === $c['level'] ) {
		$title = 'Πόλεις και χωριά';
		$all   = array();
		foreach ( ellada_guide_places() as $r ) {
			if ( $r[7] === $c['pref'] && $r[6] === $c['muni'] ) {
				$all[] = $r;
			}
		}
		$types = ellada_guide_place_types();
		foreach ( $all as $r ) {
			$page = ellada_guide_page( 'settlement', $r[0] );
			if ( $page || in_array( $r[3], array( 'c', 't' ), true ) ) {
				$items[] = array( $r[1], $page ? get_permalink( $page ) : ellada_guide_map_url( $r[7], $r[6], $r[0] ), $types[ $r[3] ] . ( $r[4] ? ' · ≈ ' . ellada_guide_num( $r[4] ) . ' κάτοικοι' : '' ) );
			}
		}
		if ( count( $all ) > count( $items ) ) {
			$after = '<details class="ellada-allplaces"><summary>Όλοι οι οικισμοί του δήμου (' . count( $all ) . ')</summary><ul>';
			foreach ( $all as $r ) {
				$page   = ellada_guide_page( 'settlement', $r[0] );
				$after .= '<li><a href="' . esc_url( $page ? get_permalink( $page ) : ellada_guide_map_url( $r[7], $r[6], $r[0] ) ) . '">' . esc_html( $r[1] ) . '</a></li>';
			}
			$after .= '</ul></details>';
		}
	}
	if ( ! $items && ! $after ) {
		return '';
	}
	$html = '<section class="ellada-gsec"><h2 class="ellada-h">' . esc_html( $title ) . '</h2>';
	if ( $items ) {
		$html .= '<ul class="ellada-gchildren">';
		foreach ( $items as list( $label, $url, $meta ) ) {
			$inner = '<span class="ellada-gchildren__name">' . esc_html( $label ) . '</span>' . ( $meta ? '<span class="ellada-gchildren__meta">' . esc_html( $meta ) . '</span>' : '' );
			$html .= '<li>' . ( $url ? '<a href="' . esc_url( $url ) . '">' . $inner . '<span class="ellada-gchildren__arrow" aria-hidden="true">→</span></a>' : '<span class="ellada-gchildren__row">' . $inner . '</span>' ) . '</li>';
		}
		$html .= '</ul>';
	}
	return $html . $after . '</section>';
}

add_shortcode(
	'ellada_guide',
	function ( $atts ) {
		$id = ellada_ctx_id( (array) $atts );
		if ( 'ellada_location' !== get_post_type( $id ) || ! ellada_feature( 'guide' ) ) {
			return '';
		}
		ellada_front_style();
		$c     = ellada_guide_ctx( $id );
		$intro = trim( (string) get_post_meta( $id, 'guide_intro', true ) );
		$why   = function_exists( 'ellada_lines' ) ? ellada_lines( get_post_meta( $id, 'why_go', true ) ) : array();

		$top = ellada_guide_facts_html( $c );
		if ( $intro || $why ) {
			$top .= '<div class="ellada-gintro">';
			$top .= $intro ? '<div class="ellada-gintro__text">' . wpautop( wp_kses_post( $intro ) ) . '</div>' : '';
			if ( $why ) {
				$top .= '<div class="ellada-gwhy"><h2 class="ellada-glance__h">Γιατί να πάτε</h2><ul>';
				foreach ( $why as $w ) {
					$top .= '<li>' . esc_html( $w ) . '</li>';
				}
				$top .= '</ul></div>';
			}
			$top .= '</div>';
		}

		$map = '';
		if ( in_array( $c['level'], array( 'municipality', 'settlement' ), true ) && $c['pref'] && $c['muni'] ) {
			$map = sprintf(
				'<p class="ellada-gmap"><a class="ellada-feat__btn" href="%s">Δείτε %s στον χάρτη <span aria-hidden="true">→</span></a></p>',
				esc_url( ellada_guide_map_url( $c['pref'], $c['muni'], $c['place'] ? $c['place'][0] : '' ) ),
				'settlement' === $c['level'] ? 'τον τόπο' : 'τον δήμο'
			);
		}
		$source = '<p class="ellada-gsource">Στοιχεία έκτασης και οικισμών: © OpenStreetMap contributors. Τα στοιχεία κατοίκων είναι ενδεικτικά.</p>';
		return '<div class="ellada-guidebox">' . $top . ellada_guide_places_html( $id, $c ) . ellada_guide_children_html( $id, $c ) . $map . $source . '</div>';
	}
);

/* Με ενεργό τον οδηγό, οι κάρτες «Τοπικά σημεία» της σελίδας γίνονται ομάδες μέσα στον οδηγό. */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_cards' !== $tag || ! is_singular( 'ellada_location' ) || ! ellada_feature( 'guide' ) ) {
			return $output;
		}
		$attr = (array) $attr;
		return ( isset( $attr['type'] ) && 'point' === $attr['type'] ) ? '' : $output;
	},
	10,
	3
);

/* ═════════════════════ Σελίδα «Ταξιδιωτικός οδηγός» ═════════════════════ */

add_shortcode(
	'ellada_guide_hub',
	function () {
		ellada_front_style();
		$regions = ellada_map_prefecture_regions();
		$html    = '<div class="ellada-hub">';
		foreach ( ellada_guide_departments() as $dep => $prefs ) {
			$html .= '<section class="ellada-hub__dep"><h2 class="ellada-hub__title">' . esc_html( $dep ) . '</h2><ul>';
			foreach ( $prefs as $pref ) {
				$page  = ellada_guide_page( 'prefecture', $pref );
				$label = ellada_guide_prefecture_title( $pref );
				$html .= '<li>' . ( $page ? '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a>' : '<span>' . esc_html( $label ) . '</span>' ) . '</li>';
			}
			$html .= '</ul></section>';
		}
		unset( $regions );
		return $html . '</div>';
	}
);

/* ═════════════════════ Google: σελίδες χωρίς κείμενο ακόμα ═════════════════════ */

/** Μια σελίδα του οδηγού είναι «άδεια» όσο δεν έχει δικό της κείμενο ούτε συνταγές. */
function ellada_guide_is_thin( $id ) {
	foreach ( array( 'guide_intro', 'short_description', 'highlights', 'local_products', 'travel_tips' ) as $k ) {
		if ( '' !== trim( (string) get_post_meta( $id, $k, true ) ) ) {
			return false;
		}
	}
	if ( '' !== trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ) ) ) {
		return false;
	}
	return ! ( function_exists( 'ellada_search_recipe_count' ) && ellada_search_recipe_count( $id ) > 0 );
}

function ellada_guide_mark_thin( $post_id ) {
	if ( 'ellada_location' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ellada_guide_is_thin( $post_id ) ) {
		update_post_meta( $post_id, '_ellada_thin', 1 );
	} else {
		delete_post_meta( $post_id, '_ellada_thin' );
	}
}
add_action( 'save_post_ellada_location', 'ellada_guide_mark_thin', 30 );
add_action( 'acf/save_post', 'ellada_guide_mark_thin', 30 );
/* Μια νέα συνταγή «ζωντανεύει» τη σελίδα της περιοχής της. */
add_action(
	'save_post_ellada_recipe',
	function ( $recipe_id ) {
		$loc = (int) get_post_meta( $recipe_id, 'location', true );
		if ( $loc ) {
			ellada_guide_mark_thin( $loc );
		}
	},
	40
);

add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( ellada_feature( 'guide' ) && is_singular( 'ellada_location' ) && get_post_meta( get_queried_object_id(), '_ellada_thin', true ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $post_type ) {
		if ( 'ellada_location' === $post_type && ellada_feature( 'guide' ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_ellada_thin',
					'compare' => 'NOT EXISTS',
				),
			);
		}
		return $args;
	},
	10,
	2
);

/* Ο χάρτης ξέρει ποιες σελίδες δεν έχουν ακόμα κείμενο, ώστε να μην τις «ανάβει». */
add_action(
	'rest_api_init',
	function () {
		register_rest_field(
			'ellada_location',
			'ellada_thin',
			array(
				'get_callback' => function ( $obj ) {
					return (bool) get_post_meta( $obj['id'], '_ellada_thin', true );
				},
				'schema'       => array( 'type' => 'boolean' ),
			)
		);
	}
);

/* ═════════════════════ Αναζήτηση: πόλεις και χωριά ═════════════════════ */

/** [id, κανονικό όνομα, λατινικά] για κάθε οικισμό (κρατιέται μία εβδομάδα). */
function ellada_guide_place_search_index() {
	$idx = get_transient( 'ellada_place_index_' . ELLADA_MAP_VERSION );
	if ( is_array( $idx ) ) {
		return $idx;
	}
	$idx = array();
	foreach ( ellada_guide_places() as $k => $r ) {
		$idx[] = array( $k, ellada_search_norm( $r[1] ), ellada_search_latin( $r[1] ) . ' ' . ellada_search_norm( $r[2] ) );
	}
	set_transient( 'ellada_place_index_' . ELLADA_MAP_VERSION, $idx, WEEK_IN_SECONDS );
	return $idx;
}

/** Οικισμοί που ταιριάζουν με την αναζήτηση: οι πόλεις και όσα ξεκινούν με τη λέξη πρώτα. */
function ellada_guide_search_places( $query, $limit = 5 ) {
	$q = ellada_search_norm( $query );
	if ( mb_strlen( $q ) < 3 ) {
		return array();
	}
	$rank   = array( 'c' => 0, 't' => 1, 'v' => 2, 'h' => 3 );
	$places = ellada_guide_places();
	$hits   = array();
	foreach ( ellada_guide_place_search_index() as list( $k, $n, $l ) ) {
		$starts = 0 === mb_strpos( $n, $q ) || 0 === mb_strpos( $l, $q );
		$word   = $starts || preg_match( '/(^|\s)' . preg_quote( $q, '/' ) . '/u', $n . ' ' . $l );
		if ( ! $word ) {
			continue;
		}
		$r      = $places[ $k ];
		$score  = ( $n === $q ? 0 : ( $starts ? 10 : 20 ) ) + $rank[ $r[3] ] * 3;
		$hits[] = array( $score, -$r[4], $k );
	}
	sort( $hits );
	$types = ellada_guide_place_types();
	$out   = array();
	foreach ( array_slice( $hits, 0, $limit ) as $h ) {
		$r     = $places[ $h[2] ];
		$page  = ellada_guide_page( 'settlement', $r[0] );
		$out[] = array(
			'title' => $r[1],
			'url'   => $page ? get_permalink( $page ) : ellada_guide_map_url( $r[7], $r[6], $r[0] ),
			'meta'  => $types[ $r[3] ] . ' · ' . ellada_guide_prettify( $r[6] ) . ', ' . ellada_guide_prefecture_title( $r[7] ),
			'img'   => '',
		);
	}
	return $out;
}

/* ═════════════════════ Εγκατάσταση: πεδία και σελίδες ═════════════════════ */

/** Προσθέτει στα πεδία του ACF ό,τι χρειάζεται ο οδηγός (τρέχει μία φορά ανά έκδοση). */
function ellada_guide_install_fields() {
	if ( ! function_exists( 'acf_get_field' ) ) {
		return 'no acf';
	}
	$done = array();
	$lvl  = acf_get_field( 'field_ellada_loc_level' );
	if ( $lvl && ! isset( $lvl['choices']['settlement'] ) ) {
		$lvl['choices']['settlement'] = 'Πόλη / Χωριό';
		acf_update_field( $lvl );
		$done[] = 'level';
	}
	$pt = acf_get_field( 'field_ellada_pt_type' );
	if ( $pt ) {
		$new = array(
			'beach'  => 'Παραλία',
			'museum' => 'Μουσείο',
			'winery' => 'Οινοποιείο',
			'trail'  => 'Μονοπάτι / Φύση',
		);
		$other = $pt['choices']['other'] ?? 'Άλλο';
		unset( $pt['choices']['other'] );
		$pt['choices']          = array_merge( $pt['choices'], $new, array( 'other' => $other ) );
		acf_update_field( $pt );
		$done[] = 'point types';
	}
	$loc_group = acf_get_field_group( 'group_ellada_location' );
	$pt_group  = acf_get_field_group( 'group_ellada_point' );
	$add       = function ( $field, $group, $after_key ) use ( &$done ) {
		if ( ! $group || acf_get_field( $field['key'] ) ) {
			return;
		}
		$after               = acf_get_field( $after_key );
		$field['parent']     = $group['ID'];
		$field['menu_order'] = $after ? (int) $after['menu_order'] + 1 : 99;
		// Τα επόμενα πεδία μετακινούνται μία θέση πιο κάτω.
		foreach ( acf_get_fields( $group ) as $f ) {
			if ( $after && (int) $f['menu_order'] > (int) $after['menu_order'] ) {
				$f['menu_order'] = (int) $f['menu_order'] + 1;
				acf_update_field( $f );
			}
		}
		acf_update_field( $field );
		$done[] = $field['name'];
	};
	$base = array( 'instructions' => '', 'required' => 0, 'conditional_logic' => 0, 'wrapper' => array( 'width' => '', 'class' => '', 'id' => '' ), 'default_value' => '', 'placeholder' => '' );
	$add(
		$base + array(
			'key'               => 'field_ellada_loc_settlement',
			'label'             => 'Πόλη / χωριό στον χάρτη',
			'name'              => 'geo_settlement',
			'type'              => 'text',
			'instructions'      => 'Μορφή: ΝΟΜΟΣ|Δήμος|id (συμπληρώνεται όταν η σελίδα δημιουργείται από τον οδηγό).',
			'conditional_logic' => array( array( array( 'field' => 'field_ellada_loc_level', 'operator' => '==', 'value' => 'settlement' ) ) ),
		),
		$loc_group,
		'field_ellada_loc_municipality'
	);
	$add(
		array_merge( $base, array( 'key' => 'field_ellada_loc_intro', 'label' => 'Εισαγωγή οδηγού', 'name' => 'guide_intro', 'type' => 'textarea', 'rows' => 6, 'new_lines' => '', 'instructions' => 'Λίγες παράγραφοι για τον χαρακτήρα του τόπου. Όσο είναι κενή, η σελίδα δεν εμφανίζεται στο Google.' ) ),
		$loc_group,
		'field_ellada_loc_tab_guide'
	);
	$add(
		array_merge( $base, array( 'key' => 'field_ellada_loc_why', 'label' => 'Γιατί να πάτε', 'name' => 'why_go', 'type' => 'textarea', 'rows' => 4, 'new_lines' => '', 'instructions' => 'Ένας λόγος σε κάθε γραμμή (3–4 γραμμές).' ) ),
		$loc_group,
		'field_ellada_loc_intro'
	);
	$add(
		array_merge( $base, array( 'key' => 'field_ellada_pt_when', 'label' => 'Πότε γίνεται', 'name' => 'event_when', 'type' => 'text', 'instructions' => 'π.χ. «14–15 Αυγούστου» ή «Κάθε Σάββατο του Οκτωβρίου».', 'conditional_logic' => array( array( array( 'field' => 'field_ellada_pt_type', 'operator' => '==', 'value' => 'festival' ) ) ) ) ),
		$pt_group,
		'field_ellada_pt_type'
	);
	return $done;
}

/**
 * Πρόχειρες σελίδες για όλες τις περιφέρειες, τους νομούς και τους δήμους που δεν έχουν ακόμα.
 *
 * @param string $level region | prefecture | municipality
 */
function ellada_guide_create_skeleton( $level ) {
	$existing = array();
	foreach ( get_posts( array( 'post_type' => 'ellada_location', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
		$l = (string) get_post_meta( $id, 'map_level', true );
		$k = array( 'region' => 'geo_region', 'prefecture' => 'geo_prefecture', 'municipality' => 'geo_municipality' );
		if ( isset( $k[ $l ] ) ) {
			$existing[ $l . ':' . get_post_meta( $id, $k[ $l ], true ) ] = $id;
		}
	}
	$todo    = array();
	$regions = ellada_map_prefecture_regions();
	if ( 'region' === $level ) {
		foreach ( ellada_map_region_names() as $r ) {
			$todo[] = array( $r, array( 'map_level' => 'region', 'geo_region' => $r ) );
		}
	} elseif ( 'prefecture' === $level ) {
		foreach ( $regions as $p => $r ) {
			$todo[] = array( ellada_guide_prefecture_title( $p ), array( 'map_level' => 'prefecture', 'geo_prefecture' => $p, 'geo_region' => $r ) );
		}
	} elseif ( 'municipality' === $level ) {
		foreach ( array_keys( ellada_guide_areas()['municipalities'] ?? array() ) as $key ) {
			list( $p, $m ) = explode( '|', $key );
			$todo[]        = array( ellada_guide_muni_title( $m ), array( 'map_level' => 'municipality', 'geo_municipality' => $key, 'geo_region' => $regions[ $p ] ?? '' ) );
		}
	}
	$made = 0;
	foreach ( $todo as list( $title, $fields ) ) {
		$val = $fields[ 'geo_' . $level ];
		if ( isset( $existing[ $level . ':' . $val ] ) ) {
			continue;
		}
		$id = wp_insert_post( array( 'post_type' => 'ellada_location', 'post_status' => 'draft', 'post_title' => $title ), true );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		foreach ( $fields as $k => $v ) {
			update_field( $k, $v, $id );
		}
		update_post_meta( $id, '_ellada_thin', 1 );
		update_post_meta( $id, '_ellada_skeleton', 1 );
		$made++;
	}
	delete_transient( 'ellada_guide_pages' );
	return $made;
}
