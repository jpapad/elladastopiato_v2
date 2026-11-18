<?php
/**
 * «SEO του site»: πίνακας ελέγχου στο διαχειριστικό (μόνο για διαχειριστές).
 *
 *   Πίνακας   βαθμός 0–100, αριθμοί, «Κάντε σήμερα»
 *   Σελίδες   έλεγχος κάθε σελίδας όπως τη βλέπει το Google (το site διαβάζει τις δικές του σελίδες
 *             από το sitemap: τίτλος, περιγραφή, canonical, robots, φωτογραφίες, δομημένα δεδομένα,
 *             εσωτερικοί σύνδεσμοι — χωρίς header/footer — και σπασμένοι σύνδεσμοι)
 *   Ευκαιρίες πιάτα χωρίς συνταγή, τόποι χωρίς οδηγό, ημερολόγιο γιορτών, ορφανές σελίδες
 *   Τεχνικά   robots.txt, sitemap, ορατότητα στις μηχανές, 404 (μόνο διεύθυνση + πλήθος, χωρίς IP)
 *   Google    Search Console API (μόνο ανάγνωση) με λογαριασμό υπηρεσίας: το κλειδί ΜΟΝΟ στο wp-config.php
 *             define( 'ELLADA_GSC_KEY_FILE', '/πλήρης/διαδρομή/κλειδί.json' ); — ποτέ στη βάση ή στο git
 *   Αναφορά   κάθε Δευτέρα στο email του site
 *
 * Δεν αλλάζει τίποτα στις σελίδες των επισκεπτών και δεν στέλνει δεδομένα σε τρίτους (εκτός από τα
 * αιτήματα ανάγνωσης προς το Google, όταν συνδεθεί το Search Console).
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_SEO_UA = 'ElladaSEO/1.0 (+elladastopiato.gr)';

/* ═════════════════════ Ρυθμίσεις ═════════════════════ */

function ellada_seo_settings() {
	return wp_parse_args(
		(array) get_option( 'ellada_seo_settings', array() ),
		array(
			'weekly_email' => 1,
			'email_to'     => '',
			'gsc_property' => 'sc-domain:' . wp_parse_url( home_url(), PHP_URL_HOST ),
		)
	);
}

/* ═════════════════════ Ανάγνωση σελίδων ═════════════════════ */

/** Όλες οι διευθύνσεις του sitemap (με τις υπο-λίστες). */
function ellada_seo_sitemap_urls() {
	$urls  = array();
	$queue = array( home_url( '/wp-sitemap.xml' ) );
	$seen  = array();
	while ( $queue && count( $seen ) < 40 ) {
		$sm = array_shift( $queue );
		if ( isset( $seen[ $sm ] ) ) {
			continue;
		}
		$seen[ $sm ] = 1;
		$body        = wp_remote_retrieve_body( wp_remote_get( $sm, array( 'timeout' => 20, 'user-agent' => ELLADA_SEO_UA ) ) );
		if ( ! preg_match_all( '#<loc>\s*([^<\s]+)\s*</loc>#', $body, $m ) ) {
			continue;
		}
		foreach ( $m[1] as $loc ) {
			$loc = html_entity_decode( $loc );
			if ( false !== strpos( $body, '<sitemapindex' ) ) {
				$queue[] = $loc;
			} else {
				$urls[] = $loc;
			}
		}
	}
	return array_values( array_unique( $urls ) );
}

function ellada_seo_norm_url( $url ) {
	$url = strtok( html_entity_decode( (string) $url ), '#' );
	if ( '' === $url || false === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		$url = home_url( $url );
	}
	return $url;
}

/** Διαβάζει μια σελίδα και κρατά ό,τι χρειάζεται ο έλεγχος. */
function ellada_seo_fetch( $url ) {
	$t0   = microtime( true );
	$res  = wp_remote_get( $url, array( 'timeout' => 25, 'redirection' => 0, 'user-agent' => ELLADA_SEO_UA ) );
	$ms   = (int) round( ( microtime( true ) - $t0 ) * 1000 );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	$html = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res );
	$d    = array(
		'url'      => $url,
		'status'   => $code,
		'ms'       => $ms,
		'bytes'    => strlen( $html ),
		'title'    => '',
		'desc'     => '',
		'canon'    => '',
		'robots'   => '',
		'og_image' => '',
		'h1'       => 0,
		'imgs'     => 0,
		'no_alt'   => 0,
		'schema'   => array(),
		'links'    => array(),
		'post_id'  => url_to_postid( $url ),
		'checked'  => time(),
	);
	if ( 200 !== $code || '' === $html ) {
		return $d;
	}
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
	libxml_clear_errors();
	$xp = new DOMXPath( $dom );

	$t          = $xp->query( '//title' );
	$d['title'] = $t->length ? trim( html_entity_decode( $t->item( 0 )->textContent, ENT_QUOTES, 'UTF-8' ) ) : '';
	foreach ( $xp->query( '//meta' ) as $m ) {
		$n = strtolower( $m->getAttribute( 'name' ) . $m->getAttribute( 'property' ) );
		$c = trim( $m->getAttribute( 'content' ) );
		if ( 'description' === $n ) {
			$d['desc'] = $c;
		} elseif ( 'robots' === $n ) {
			$d['robots'] .= ' ' . strtolower( $c );
		} elseif ( 'og:image' === $n ) {
			$d['og_image'] = $c;
		}
	}
	$d['robots'] = trim( $d['robots'] );
	foreach ( $xp->query( '//link[@rel="canonical"]' ) as $l ) {
		$d['canon'] = $l->getAttribute( 'href' );
	}
	foreach ( $xp->query( '//script[@type="application/ld+json"]' ) as $s ) {
		$j = json_decode( $s->textContent, true );
		if ( ! is_array( $j ) ) {
			continue;
		}
		$items = isset( $j['@graph'] ) ? $j['@graph'] : ( isset( $j[0] ) ? $j : array( $j ) );
		foreach ( $items as $it ) {
			if ( is_array( $it ) && ! empty( $it['@type'] ) ) {
				$d['schema'][] = $it;
			}
		}
	}
	// Χωρίς header/footer του Elementor: μετράμε μόνο το περιεχόμενο της σελίδας.
	foreach ( $xp->query( '//*[contains(concat(" ",normalize-space(@class)," ")," elementor-location-header ") or contains(concat(" ",normalize-space(@class)," ")," elementor-location-footer ") or @id="wpadminbar"]' ) as $chrome ) {
		$chrome->parentNode->removeChild( $chrome );
	}
	$d['h1'] = $xp->query( '//h1' )->length;
	foreach ( $xp->query( '//body//img' ) as $img ) {
		++$d['imgs'];
		if ( ! $img->hasAttribute( 'alt' ) || ( '' === trim( $img->getAttribute( 'alt' ) ) && 'presentation' !== $img->getAttribute( 'role' ) && 'true' !== $img->getAttribute( 'aria-hidden' ) ) ) {
			++$d['no_alt'];
		}
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	foreach ( $xp->query( '//body//a[@href]' ) as $a ) {
		$href = ellada_seo_norm_url( $a->getAttribute( 'href' ) );
		if ( ! $href || wp_parse_url( $href, PHP_URL_HOST ) !== $host || preg_match( '#/wp-(admin|content|json|login)|\?|\.(jpe?g|png|webp|pdf|xml)$#i', $href ) ) {
			continue;
		}
		$d['links'][ $href ] = 1;
	}
	$d['links'] = array_keys( $d['links'] );
	return $d;
}

/* ═════════════════════ Έλεγχοι ═════════════════════ */

/** [κωδικός => [βαρύτητα, μήνυμα]] — βαρύτητα: critical | high | medium | low */
function ellada_seo_check_defs() {
	return array(
		'status'       => array( 'critical', 'Η σελίδα δεν ανοίγει σωστά' ),
		'noindex'      => array( 'critical', 'Είναι στο sitemap αλλά λέει στο Google «μη με δείχνεις»' ),
		'canon'        => array( 'high', 'Λάθος ή χωρίς κανονική διεύθυνση (canonical)' ),
		'title_none'   => array( 'high', 'Χωρίς τίτλο' ),
		'title_len'    => array( 'medium', 'Τίτλος πολύ μικρός ή πολύ μεγάλος (ιδανικά 30–65 χαρακτήρες)' ),
		'title_dup'    => array( 'medium', 'Ίδιος τίτλος με άλλη σελίδα' ),
		'desc_none'    => array( 'high', 'Χωρίς περιγραφή για το Google' ),
		'desc_len'     => array( 'low', 'Περιγραφή πολύ μικρή ή πολύ μεγάλη (ιδανικά 70–160)' ),
		'desc_dup'     => array( 'medium', 'Ίδια περιγραφή με άλλη σελίδα' ),
		'h1'           => array( 'low', 'Δεν έχει ακριβώς έναν κύριο τίτλο (H1)' ),
		'og_image'     => array( 'medium', 'Χωρίς εικόνα για κοινοποίηση' ),
		'alt'          => array( 'medium', 'Φωτογραφίες χωρίς περιγραφή (alt)' ),
		'heavy'        => array( 'medium', 'Βαριά σελίδα (HTML > 400 KB)' ),
		'slow'         => array( 'low', 'Αργή απόκριση server (> 2 δευτ.)' ),
		'orphan'       => array( 'high', 'Καμία άλλη σελίδα δεν δείχνει εδώ (ορφανή)' ),
		'broken'       => array( 'high', 'Σύνδεσμοι σε σελίδες που δεν υπάρχουν' ),
		'r_schema'     => array( 'critical', 'Συνταγή χωρίς δομημένα δεδομένα (Recipe)' ),
		'r_photo'      => array( 'critical', 'Συνταγή χωρίς φωτογραφία — δεν βγαίνει με εικόνα στο Google' ),
		'r_required'   => array( 'critical', 'Λείπουν υλικά ή βήματα από τα δεδομένα συνταγής' ),
		'r_time'       => array( 'medium', 'Λείπει χρόνος ή μερίδες από τα δεδομένα συνταγής' ),
		'r_source'     => array( 'low', '«Παραδοσιακή» χωρίς πηγή' ),
		'loc_cover'    => array( 'low', 'Τόπος χωρίς φωτογραφία εξωφύλλου' ),
	);
}

function ellada_seo_weights() {
	return array( 'critical' => 25, 'high' => 10, 'medium' => 5, 'low' => 2 );
}

/** Εφαρμόζει τους ελέγχους σε όλες τις σελίδες (χρειάζεται το σύνολο για διπλότυπα και ορφανές). */
function ellada_seo_evaluate( array $pages, array $broken ) {
	$titles  = array();
	$descs   = array();
	$inbound = array();
	foreach ( $pages as $u => $p ) {
		if ( $p['title'] ) {
			$titles[ mb_strtolower( $p['title'] ) ][] = $u;
		}
		if ( $p['desc'] ) {
			$descs[ mb_strtolower( $p['desc'] ) ][] = $u;
		}
		foreach ( $p['links'] as $l ) {
			if ( $l !== $u ) {
				$inbound[ trailingslashit( $l ) ][ $u ] = 1;
			}
		}
	}
	$w    = ellada_seo_weights();
	$defs = ellada_seo_check_defs();
	$home = trailingslashit( home_url() );
	foreach ( $pages as $u => &$p ) {
		$issues = array();
		$add    = function ( $code, $extra = '' ) use ( &$issues ) {
			$issues[ $code ] = $extra;
		};
		if ( 200 !== $p['status'] ) {
			$add( 'status', (string) $p['status'] );
		} else {
			if ( false !== strpos( $p['robots'], 'noindex' ) ) {
				$add( 'noindex' );
			}
			if ( ! $p['canon'] || untrailingslashit( $p['canon'] ) !== untrailingslashit( $u ) ) {
				$add( 'canon', $p['canon'] );
			}
			$tl = mb_strlen( $p['title'] );
			if ( ! $tl ) {
				$add( 'title_none' );
			} elseif ( $tl < 30 || $tl > 65 ) {
				$add( 'title_len', $tl . ' χαρ.' );
			}
			if ( $p['title'] && count( $titles[ mb_strtolower( $p['title'] ) ] ) > 1 ) {
				$add( 'title_dup' );
			}
			$dl = mb_strlen( $p['desc'] );
			if ( ! $dl ) {
				$add( 'desc_none' );
			} elseif ( $dl < 70 || $dl > 160 ) {
				$add( 'desc_len', $dl . ' χαρ.' );
			}
			if ( $p['desc'] && count( $descs[ mb_strtolower( $p['desc'] ) ] ) > 1 ) {
				$add( 'desc_dup' );
			}
			if ( 1 !== (int) $p['h1'] ) {
				$add( 'h1', (string) $p['h1'] );
			}
			if ( ! $p['og_image'] ) {
				$add( 'og_image' );
			}
			if ( $p['no_alt'] ) {
				$add( 'alt', $p['no_alt'] . '/' . $p['imgs'] );
			}
			if ( $p['bytes'] > 400 * 1024 ) {
				$add( 'heavy', round( $p['bytes'] / 1024 ) . ' KB' );
			}
			if ( $p['ms'] > 2000 ) {
				$add( 'slow', round( $p['ms'] / 1000, 1 ) . ' δευτ.' );
			}
			$in = count( $inbound[ trailingslashit( $u ) ] ?? array() );
			if ( $u !== $home && 0 === $in ) {
				$add( 'orphan' );
			}
			$p['inbound'] = $in;
			$bad          = array_values( array_intersect( $p['links'], $broken ) );
			if ( $bad ) {
				$add( 'broken', implode( ' ', array_slice( $bad, 0, 3 ) ) );
			}
			$id   = (int) $p['post_id'];
			$type = $id ? get_post_type( $id ) : '';
			if ( 'ellada_recipe' === $type ) {
				$rec = null;
				foreach ( $p['schema'] as $s ) {
					if ( in_array( 'Recipe', (array) $s['@type'], true ) ) {
						$rec = $s;
					}
				}
				if ( ! $rec ) {
					$add( 'r_schema' );
				} else {
					if ( empty( $rec['recipeIngredient'] ) || empty( $rec['recipeInstructions'] ) ) {
						$add( 'r_required' );
					}
					if ( empty( $rec['totalTime'] ) && empty( $rec['cookTime'] ) || empty( $rec['recipeYield'] ) ) {
						$add( 'r_time' );
					}
				}
				if ( ! has_post_thumbnail( $id ) || ( $rec && empty( $rec['image'] ) ) ) {
					$add( 'r_photo' );
				}
				if ( 'traditional' === get_post_meta( $id, 'recipe_kind', true ) && '' === trim( (string) get_post_meta( $id, 'sources', true ) ) ) {
					$add( 'r_source' );
				}
			} elseif ( 'ellada_location' === $type && ! get_post_meta( $id, 'cover_image', true ) ) {
				$add( 'loc_cover' );
			}
		}
		$score = 100;
		foreach ( $issues as $code => $x ) {
			$score -= $w[ $defs[ $code ][0] ];
		}
		$p['issues'] = $issues;
		$p['score']  = max( 0, $score );
	}
	unset( $p );
	return $pages;
}

/* ═════════════════════ Ανάλυση σε βήματα (κουμπί ή καθημερινό cron) ═════════════════════ */

/**
 * Ένα βήμα της ανάλυσης. Επιστρέφει ['done' => bool, 'progress' => '12/58'].
 * Η κατάσταση κρατιέται στο option ellada_seo_run (χωρίς autoload).
 */
function ellada_seo_step( $batch = 6 ) {
	$run = get_option( 'ellada_seo_run' );
	if ( ! is_array( $run ) || empty( $run['urls'] ) ) {
		$urls = ellada_seo_sitemap_urls();
		array_unshift( $urls, trailingslashit( home_url() ) );
		$run = array( 'urls' => array_values( array_unique( $urls ) ), 'i' => 0, 'pages' => array(), 'started' => time() );
	}
	$n = count( $run['urls'] );
	for ( $k = 0; $k < $batch && $run['i'] < $n; $k++, $run['i']++ ) {
		$u                  = $run['urls'][ $run['i'] ];
		$run['pages'][ $u ] = ellada_seo_fetch( $u );
	}
	if ( $run['i'] < $n ) {
		update_option( 'ellada_seo_run', $run, false );
		return array( 'done' => false, 'progress' => $run['i'] . '/' . $n );
	}
	// Σπασμένοι εσωτερικοί σύνδεσμοι: όσοι δεν είναι στο sitemap ελέγχονται ένας-ένας (έως 60).
	$known = array();
	foreach ( array_keys( $run['pages'] ) as $u ) {
		$known[ trailingslashit( $u ) ] = 1;
	}
	$other = array();
	foreach ( $run['pages'] as $p ) {
		foreach ( $p['links'] as $l ) {
			if ( ! isset( $known[ trailingslashit( $l ) ] ) ) {
				$other[ $l ] = 1;
			}
		}
	}
	$broken = array();
	foreach ( array_slice( array_keys( $other ), 0, 60 ) as $l ) {
		$code = (int) wp_remote_retrieve_response_code( wp_remote_head( $l, array( 'timeout' => 10, 'redirection' => 3, 'user-agent' => ELLADA_SEO_UA ) ) );
		if ( $code >= 400 ) {
			$broken[] = $l;
		}
	}
	$pages = ellada_seo_evaluate( $run['pages'], $broken );
	$score = $pages ? (int) round( array_sum( wp_list_pluck( $pages, 'score' ) ) / count( $pages ) ) : 0;
	update_option( 'ellada_seo_pages', $pages, false );
	update_option( 'ellada_seo_last', array( 'time' => time(), 'score' => $score, 'count' => count( $pages ), 'broken' => $broken, 'sec' => time() - $run['started'] ), false );
	$hist                      = (array) get_option( 'ellada_seo_history', array() );
	$hist[ wp_date( 'Y-m-d' ) ] = $score;
	update_option( 'ellada_seo_history', array_slice( $hist, -120, null, true ), false );
	delete_option( 'ellada_seo_run' );
	return array( 'done' => true, 'progress' => $n . '/' . $n, 'score' => $score );
}

add_action(
	'wp_ajax_ellada_seo_step',
	function () {
		check_ajax_referer( 'ellada_seo' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		if ( ! empty( $_POST['restart'] ) ) {
			delete_option( 'ellada_seo_run' );
		}
		wp_send_json_success( ellada_seo_step() );
	}
);

/* Καθημερινά τη νύχτα: όλη η ανάλυση σε διαδοχικά βήματα. */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ellada_seo_daily' ) ) {
			$t = new DateTime( 'tomorrow 04:10', wp_timezone() );
			wp_schedule_event( $t->getTimestamp(), 'daily', 'ellada_seo_daily' );
		}
		if ( ! wp_next_scheduled( 'ellada_seo_weekly' ) ) {
			$t = new DateTime( 'next monday 08:20', wp_timezone() );
			wp_schedule_event( $t->getTimestamp(), 'weekly', 'ellada_seo_weekly' );
		}
	}
);
add_action(
	'ellada_seo_daily',
	function () {
		delete_option( 'ellada_seo_run' );
		do_action( 'ellada_seo_continue' );
	}
);
add_action(
	'ellada_seo_continue',
	function () {
		$r = ellada_seo_step( 10 );
		if ( ! $r['done'] ) {
			wp_schedule_single_event( time() + 30, 'ellada_seo_continue' );
		} elseif ( ellada_gsc_ready() ) {
			ellada_gsc_refresh();
		}
	}
);

/* ═════════════════════ 404: μόνο διεύθυνση + πλήθος ═════════════════════ */

add_action(
	'template_redirect',
	function () {
		if ( ! is_404() || is_user_logged_in() ) {
			return;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' ) : '';
		if ( '' === $path || preg_match( '#^/(wp-|xmlrpc|\.well-known)|\.(php|env|ini|sql|bak|zip|ico|map|txt|js|css)$#i', $path ) || mb_strlen( $path ) > 200 ) {
			return; // σαρωτές και αρχεία: δεν μας αφορούν
		}
		$log = (array) get_option( 'ellada_404_log', array() );
		if ( ! isset( $log[ $path ] ) && count( $log ) >= 300 ) {
			return;
		}
		$log[ $path ] = array( ( $log[ $path ][0] ?? 0 ) + 1, time() );
		update_option( 'ellada_404_log', $log, false );
	},
	1
);

/* ═════════════════════ Ευκαιρίες ═════════════════════ */

/* Το ορθόδοξο Πάσχα: ellada_orthodox_easter() στο season.php (DateTimeImmutable). */

/** Οι γιορτές των επόμενων ~4 μηνών: [όνομα, ημερομηνία, περιστάσεις, μέρες]. */
function ellada_seo_holidays() {
	$now = new DateTime( 'today', wp_timezone() );
	$out = array();
	foreach ( array( (int) $now->format( 'Y' ), (int) $now->format( 'Y' ) + 1 ) as $y ) {
		$easter = ellada_orthodox_easter( $y );
		$list   = array(
			array( 'Χριστούγεννα', new DateTime( "$y-12-25", wp_timezone() ), array( 'xristougenna' ) ),
			array( 'Πρωτοχρονιά', new DateTime( "$y-01-01", wp_timezone() ), array( 'protoxronia' ) ),
			array( 'Απόκριες / Τσικνοπέμπτη', ( clone $easter )->modify( '-59 days' ), array( 'apokries' ) ),
			array( 'Καθαρά Δευτέρα & Σαρακοστή', ( clone $easter )->modify( '-48 days' ), array( 'sarakosti' ) ),
			array( 'Πάσχα', $easter, array( 'pasxa' ) ),
			array( 'Δεκαπενταύγουστος', new DateTime( "$y-08-15", wp_timezone() ), array( 'dekapentavgoustos' ) ),
		);
		foreach ( $list as $h ) {
			$days = (int) $now->diff( $h[1] )->format( '%r%a' );
			if ( $days >= 0 && $days <= 130 ) {
				$n     = get_posts( array( 'post_type' => 'ellada_recipe', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'recipe_occasion', 'field' => 'slug', 'terms' => $h[2] ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
				$out[] = array( $h[0], $h[1], count( $n ), $days );
			}
		}
	}
	usort( $out, fn( $a, $b ) => $a[3] <=> $b[3] );
	return $out;
}

/** Νομοί/περιφέρειες με συνταγές αλλά χωρίς κείμενο οδηγού. */
function ellada_seo_places_without_guide() {
	$out = array();
	$ids = get_posts(
		array(
			'post_type'   => 'ellada_location',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array( array( 'key' => 'map_level', 'value' => array( 'region', 'prefecture' ), 'compare' => 'IN' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $ids as $id ) {
		if ( '' !== trim( (string) get_post_meta( $id, 'guide_intro', true ) ) || ! function_exists( 'ellada_area_meta_query' ) ) {
			continue;
		}
		$mq = ellada_area_meta_query( $id );
		if ( ! $mq ) {
			continue;
		}
		$n = count( get_posts( array( 'post_type' => 'ellada_recipe', 'post_status' => 'publish', 'numberposts' => 50, 'fields' => 'ids', 'meta_query' => $mq ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $n ) {
			$out[] = array( $id, $n );
		}
	}
	return $out;
}

/* ═════════════════════ Τα 5 για σήμερα ═════════════════════ */

function ellada_seo_todo( $limit = 5 ) {
	$pages = (array) get_option( 'ellada_seo_pages', array() );
	$defs  = ellada_seo_check_defs();
	$w     = ellada_seo_weights();
	$group = array();
	foreach ( $pages as $u => $p ) {
		foreach ( (array) ( $p['issues'] ?? array() ) as $code => $x ) {
			$group[ $code ][] = $p;
		}
	}
	$todo = array();
	foreach ( $group as $code => $list ) {
		$todo[] = array(
			'weight' => $w[ $defs[ $code ][0] ] * count( $list ),
			'sev'    => $defs[ $code ][0],
			'text'   => $defs[ $code ][1],
			'count'  => count( $list ),
			'pages'  => $list,
			'code'   => $code,
		);
	}
	foreach ( ellada_seo_holidays() as $h ) {
		if ( $h[3] <= 63 && $h[2] < 3 ) {
			$todo[] = array(
				'weight' => 120 - $h[3],
				'sev'    => 'high',
				'text'   => sprintf( '%s σε %d μέρες: %d συνταγές με αυτή την περίσταση. Το Google θέλει χρόνο — δημοσιεύστε νωρίς.', $h[0], $h[3], $h[2] ),
				'count'  => 0,
				'pages'  => array(),
				'code'   => 'holiday',
			);
		}
	}
	usort( $todo, fn( $a, $b ) => $b['weight'] <=> $a['weight'] );
	return array_slice( $todo, 0, $limit );
}

/* ═════════════════════ Google Search Console ═════════════════════ */

function ellada_gsc_key() {
	if ( ! defined( 'ELLADA_GSC_KEY_FILE' ) || ! is_readable( ELLADA_GSC_KEY_FILE ) ) {
		return null;
	}
	$k = json_decode( (string) file_get_contents( ELLADA_GSC_KEY_FILE ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return ( is_array( $k ) && ! empty( $k['client_email'] ) && ! empty( $k['private_key'] ) ) ? $k : null;
}

function ellada_gsc_ready() {
	return null !== ellada_gsc_key() && function_exists( 'openssl_sign' );
}

/** Token πρόσβασης (μόνο ανάγνωση) με JWT του λογαριασμού υπηρεσίας. */
function ellada_gsc_token() {
	$cached = get_transient( 'ellada_gsc_token' );
	if ( $cached ) {
		return $cached;
	}
	$k = ellada_gsc_key();
	if ( ! $k ) {
		return new WP_Error( 'nokey', 'Δεν βρέθηκε το κλειδί (ELLADA_GSC_KEY_FILE).' );
	}
	$b64 = fn( $s ) => rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' );
	$now = time();
	$jwt = $b64( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) ) . '.' . $b64(
		wp_json_encode(
			array(
				'iss'   => $k['client_email'],
				'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
				'aud'   => 'https://oauth2.googleapis.com/token',
				'iat'   => $now,
				'exp'   => $now + 3600,
			)
		)
	);
	$sig = '';
	if ( ! openssl_sign( $jwt, $sig, $k['private_key'], 'sha256WithRSAEncryption' ) ) {
		return new WP_Error( 'sign', 'Το κλειδί δεν είναι σωστό.' );
	}
	$res  = wp_remote_post( 'https://oauth2.googleapis.com/token', array( 'timeout' => 20, 'body' => array( 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt . '.' . $b64( $sig ) ) ) );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $body['access_token'] ) ) {
		return new WP_Error( 'token', 'Το Google δεν έδωσε πρόσβαση: ' . ( $body['error_description'] ?? $body['error'] ?? 'άγνωστο σφάλμα' ) );
	}
	set_transient( 'ellada_gsc_token', $body['access_token'], 50 * MINUTE_IN_SECONDS );
	return $body['access_token'];
}

function ellada_gsc_request( $method, $path, $payload = null ) {
	$token = ellada_gsc_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$args = array( 'method' => $method, 'timeout' => 30, 'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ) );
	if ( null !== $payload ) {
		$args['body'] = wp_json_encode( $payload );
	}
	$res  = wp_remote_request( 'https://searchconsole.googleapis.com/' . ltrim( $path, '/' ), $args );
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code >= 400 || ! is_array( $body ) ) {
		return new WP_Error( 'gsc', 'Search Console: ' . ( $body['error']['message'] ?? ( 'HTTP ' . $code ) ) );
	}
	return $body;
}

/** Τα δεδομένα των τελευταίων 28 ημερών (αναζητήσεις, σελίδες, σύνολα) στο option ellada_gsc_data. */
function ellada_gsc_refresh() {
	$prop = rawurlencode( ellada_seo_settings()['gsc_property'] );
	$end  = wp_date( 'Y-m-d', time() - 3 * DAY_IN_SECONDS );
	$beg  = wp_date( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
	$q    = fn( $dims, $limit ) => ellada_gsc_request( 'POST', "webmasters/v3/sites/$prop/searchAnalytics/query", array( 'startDate' => $beg, 'endDate' => $end, 'dimensions' => $dims, 'rowLimit' => $limit ) );
	$tot  = $q( array(), 1 );
	if ( is_wp_error( $tot ) ) {
		update_option( 'ellada_gsc_data', array( 'error' => $tot->get_error_message(), 'time' => time() ), false );
		return $tot;
	}
	$data = array(
		'time'    => time(),
		'range'   => array( $beg, $end ),
		'totals'  => $tot['rows'][0] ?? array(),
		'queries' => $q( array( 'query' ), 250 )['rows'] ?? array(),
		'pages'   => $q( array( 'page' ), 250 )['rows'] ?? array(),
		'qp'      => $q( array( 'query', 'page' ), 500 )['rows'] ?? array(),
	);
	$sm = ellada_gsc_request( 'GET', "webmasters/v3/sites/$prop/sitemaps" );
	if ( ! is_wp_error( $sm ) ) {
		$data['sitemaps'] = $sm['sitemap'] ?? array();
	}
	$hist                       = (array) get_option( 'ellada_gsc_history', array() );
	$hist[ wp_date( 'Y-m-d' ) ] = array( (int) ( $data['totals']['clicks'] ?? 0 ), (int) ( $data['totals']['impressions'] ?? 0 ) );
	update_option( 'ellada_gsc_history', array_slice( $hist, -120, null, true ), false );
	update_option( 'ellada_gsc_data', $data, false );
	return $data;
}

/** Έλεγχος μιας διεύθυνσης στο Google (URL Inspection API). */
function ellada_gsc_inspect( $url ) {
	$r = ellada_gsc_request( 'POST', 'v1/urlInspection/index:inspect', array( 'inspectionUrl' => $url, 'siteUrl' => ellada_seo_settings()['gsc_property'], 'languageCode' => 'el' ) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	return $r['inspectionResult'] ?? array();
}

add_action(
	'wp_ajax_ellada_gsc',
	function () {
		check_ajax_referer( 'ellada_seo' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$what = sanitize_key( $_POST['what'] ?? '' );
		if ( 'inspect' === $what ) {
			$r = ellada_gsc_inspect( esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ) );
			if ( is_wp_error( $r ) ) {
				wp_send_json_error( $r->get_error_message() );
			}
			$ix = $r['indexStatusResult'] ?? array();
			$rr = array();
			foreach ( (array) ( $r['richResultsResult']['detectedItems'] ?? array() ) as $it ) {
				$rr[] = ( $it['richResultType'] ?? '' ) . ': ' . count( (array) ( $it['items'] ?? array() ) );
			}
			wp_send_json_success(
				array(
					'verdict'  => $ix['verdict'] ?? '',
					'coverage' => $ix['coverageState'] ?? '',
					'crawled'  => $ix['lastCrawlTime'] ?? '',
					'rich'     => implode( ', ', $rr ),
					'link'     => $r['inspectionResultLink'] ?? '',
				)
			);
		}
		$r = ellada_gsc_refresh();
		is_wp_error( $r ) ? wp_send_json_error( $r->get_error_message() ) : wp_send_json_success();
	}
);

/* ═════════════════════ Εβδομαδιαία αναφορά ═════════════════════ */

function ellada_seo_report_text() {
	$last  = (array) get_option( 'ellada_seo_last', array() );
	$hist  = (array) get_option( 'ellada_seo_history', array() );
	$prev  = null;
	$cut   = wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS );
	foreach ( $hist as $d => $s ) {
		if ( $d <= $cut ) {
			$prev = $s;
		}
	}
	$score = (int) ( $last['score'] ?? 0 );
	$lines = array( 'Ελλάδα στο Πιάτο — εβδομαδιαία αναφορά SEO', str_repeat( '─', 44 ), '' );
	$lines[] = sprintf( 'Βαθμός SEO: %d/100%s', $score, null !== $prev ? sprintf( ' (%s%d από την προηγούμενη εβδομάδα)', $score >= $prev ? '+' : '', $score - $prev ) : '' );
	$lines[] = sprintf( 'Σελίδες που ελέγχθηκαν: %d', (int) ( $last['count'] ?? 0 ) );
	$g       = (array) get_option( 'ellada_gsc_data', array() );
	if ( ! empty( $g['totals'] ) ) {
		$lines[] = '';
		$lines[] = sprintf( 'Google (28 μέρες): %s κλικ · %s εμφανίσεις · μέση θέση %.1f', number_format_i18n( $g['totals']['clicks'] ?? 0 ), number_format_i18n( $g['totals']['impressions'] ?? 0 ), (float) ( $g['totals']['position'] ?? 0 ) );
		$top = array_slice( (array) ( $g['queries'] ?? array() ), 0, 5 );
		if ( $top ) {
			$lines[] = 'Κορυφαίες αναζητήσεις:';
			foreach ( $top as $r ) {
				$lines[] = sprintf( '  • %s — %d κλικ, θέση %.1f', $r['keys'][0], $r['clicks'], $r['position'] );
			}
		}
	}
	$lines[] = '';
	$lines[] = 'Κάντε αυτή την εβδομάδα:';
	foreach ( ellada_seo_todo( 3 ) as $i => $t ) {
		$lines[] = sprintf( '  %d. %s%s', $i + 1, $t['text'], $t['count'] ? ' (' . $t['count'] . ')' : '' );
	}
	$lines[] = '';
	$lines[] = 'Όλες οι λεπτομέρειες: ' . admin_url( 'admin.php?page=ellada-seo' );
	return implode( "\n", $lines );
}

add_action(
	'ellada_seo_weekly',
	function () {
		$s = ellada_seo_settings();
		if ( empty( $s['weekly_email'] ) ) {
			return;
		}
		$to = is_email( $s['email_to'] ) ? $s['email_to'] : get_option( 'admin_email' );
		wp_mail( $to, 'Εβδομαδιαία αναφορά SEO — Ελλάδα στο Πιάτο', ellada_seo_report_text() );
	}
);

/* ═════════════════════ Η σελίδα στο διαχειριστικό ═════════════════════ */

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'SEO του site', 'SEO', 'manage_options', 'ellada-seo', 'ellada_seo_page', 'dashicons-chart-line', 3 );
	}
);

add_action(
	'admin_init',
	function () {
		if ( isset( $_POST['ellada_seo_save'] ) && current_user_can( 'manage_options' ) && check_admin_referer( 'ellada_seo_settings' ) ) {
			update_option(
				'ellada_seo_settings',
				array(
					'weekly_email' => empty( $_POST['weekly_email'] ) ? 0 : 1,
					'email_to'     => sanitize_email( wp_unslash( $_POST['email_to'] ?? '' ) ),
					'gsc_property' => sanitize_text_field( wp_unslash( $_POST['gsc_property'] ?? '' ) ),
				)
			);
			if ( ! empty( $_POST['send_test'] ) ) {
				do_action( 'ellada_seo_weekly' );
			}
			wp_safe_redirect( admin_url( 'admin.php?page=ellada-seo&tab=settings&saved=1' ) );
			exit;
		}
	}
);

function ellada_seo_sev_badge( $sev ) {
	$l = array( 'critical' => 'Κρίσιμο', 'high' => 'Σημαντικό', 'medium' => 'Μέτριο', 'low' => 'Μικρό' );
	return '<span class="es-sev is-' . esc_attr( $sev ) . '">' . esc_html( $l[ $sev ] ) . '</span>';
}

function ellada_seo_page_label( $p ) {
	$id = (int) $p['post_id'];
	if ( $id ) {
		$types = array( 'ellada_recipe' => 'Συνταγή', 'ellada_location' => 'Τόπος', 'ellada_collection' => 'Συλλογή', 'ellada_point' => 'Σημείο', 'page' => 'Σελίδα', 'ellada_testimony' => 'Μαρτυρία' );
		return array( get_the_title( $id ), $types[ get_post_type( $id ) ] ?? get_post_type( $id ), get_edit_post_link( $id, 'raw' ) );
	}
	$path = wp_parse_url( $p['url'], PHP_URL_PATH );
	return array( '/' === $path ? 'Αρχική' : urldecode( $path ), 'Λίστα', '' );
}

function ellada_seo_score_class( $s ) {
	return $s >= 85 ? 'is-good' : ( $s >= 60 ? 'is-ok' : 'is-bad' );
}

function ellada_seo_page() {
	$tab   = sanitize_key( $_GET['tab'] ?? 'dash' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tabs  = array( 'dash' => 'Πίνακας', 'pages' => 'Σελίδες', 'opps' => 'Ευκαιρίες', 'tech' => 'Τεχνικά', 'google' => 'Google', 'settings' => 'Ρυθμίσεις' );
	$last  = (array) get_option( 'ellada_seo_last', array() );
	$pages = (array) get_option( 'ellada_seo_pages', array() );
	echo '<div class="wrap ellada-seo"><h1>SEO του site</h1>';
	echo '<nav class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $label ) {
		printf( '<a class="nav-tab%s" href="%s">%s</a>', $k === $tab ? ' nav-tab-active' : '', esc_url( admin_url( 'admin.php?page=ellada-seo&tab=' . $k ) ), esc_html( $label ) );
	}
	echo '</nav>';
	printf(
		'<p class="es-run"><button type="button" class="button button-primary" id="es-run">Νέα ανάλυση τώρα</button> <span id="es-progress">%s</span></p>',
		$last ? esc_html( sprintf( 'Τελευταία ανάλυση: %s · %d σελίδες · γίνεται αυτόματα κάθε βράδυ.', wp_date( 'j/n/Y H:i', $last['time'] ), $last['count'] ) ) : 'Δεν έχει γίνει ακόμα ανάλυση.'
	);

	if ( ! $pages && ! in_array( $tab, array( 'opps', 'tech', 'google', 'settings' ), true ) ) {
		echo '<div class="es-card"><p>Πατήστε <strong>«Νέα ανάλυση τώρα»</strong>: το site θα διαβάσει όλες τις σελίδες του sitemap, όπως τις βλέπει το Google (1–2 λεπτά).</p></div>';
	} elseif ( 'dash' === $tab ) {
		ellada_seo_tab_dash( $last, $pages );
	} elseif ( 'pages' === $tab ) {
		ellada_seo_tab_pages( $pages );
	}
	if ( 'opps' === $tab ) {
		ellada_seo_tab_opps( $pages );
	} elseif ( 'tech' === $tab ) {
		ellada_seo_tab_tech( $last );
	} elseif ( 'google' === $tab ) {
		ellada_seo_tab_google();
	} elseif ( 'settings' === $tab ) {
		ellada_seo_tab_settings();
	}
	echo '</div>';
}

function ellada_seo_tab_dash( $last, $pages ) {
	$score = (int) $last['score'];
	$hist  = (array) get_option( 'ellada_seo_history', array() );
	$vals  = array_values( $hist );
	$prev  = count( $vals ) > 1 ? $vals[ count( $vals ) - 2 ] : null;
	$cnt   = fn( $code ) => count( array_filter( $pages, fn( $p ) => isset( $p['issues'][ $code ] ) ) );
	$rec   = array_filter( $pages, fn( $p ) => 'ellada_recipe' === get_post_type( (int) $p['post_id'] ) );
	$rich  = count( array_filter( $rec, fn( $p ) => ! array_intersect_key( $p['issues'], array_flip( array( 'r_schema', 'r_photo', 'r_required' ) ) ) ) );
	$thin  = count( get_posts( array( 'post_type' => 'ellada_location', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_ellada_thin', 'meta_value' => '1' ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery

	echo '<div class="es-grid">';
	printf(
		'<div class="es-card es-score %1$s"><p class="es-k">Βαθμός SEO</p><p class="es-big">%2$d<small>/100</small></p><p class="es-sub">%3$s</p>%4$s</div>',
		esc_attr( ellada_seo_score_class( $score ) ),
		$score,
		null !== $prev ? esc_html( sprintf( '%s%d από την προηγούμενη ανάλυση', $score >= $prev ? '+' : '', $score - $prev ) ) : 'Ο μέσος όρος όλων των σελίδων',
		ellada_seo_sparkline( $vals )
	);
	$stats = array(
		array( count( $pages ), 'σελίδες στο sitemap (για το Google)' ),
		array( $thin, 'τόποι χωρίς κείμενο (εκτός Google, σκόπιμα)' ),
		array( sprintf( '%d/%d', $rich, count( $rec ) ), 'συνταγές έτοιμες για πλούσια αποτελέσματα' ),
		array( $cnt( 'r_photo' ), 'συνταγές χωρίς φωτογραφία' ),
		array( $cnt( 'orphan' ), 'ορφανές σελίδες' ),
		array( $cnt( 'desc_none' ) + $cnt( 'title_none' ), 'σελίδες χωρίς τίτλο ή περιγραφή' ),
	);
	foreach ( $stats as $s ) {
		printf( '<div class="es-card es-stat"><p class="es-big">%s</p><p class="es-sub">%s</p></div>', esc_html( $s[0] ), esc_html( $s[1] ) );
	}
	echo '</div>';

	echo '<h2>Κάντε σήμερα</h2><ol class="es-todo">';
	$todo = ellada_seo_todo( 5 );
	if ( ! $todo ) {
		echo '<li>Τίποτα επείγον. 👍</li>';
	}
	foreach ( $todo as $t ) {
		$links = '';
		foreach ( array_slice( $t['pages'], 0, 4 ) as $p ) {
			list( $name, , $edit ) = ellada_seo_page_label( $p );
			$links                .= $edit ? '<a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a>' : '<a href="' . esc_url( $p['url'] ) . '" target="_blank">' . esc_html( $name ) . '</a>';
		}
		if ( count( $t['pages'] ) > 4 ) {
			$links .= '<a href="' . esc_url( admin_url( 'admin.php?page=ellada-seo&tab=pages&issue=' . $t['code'] ) ) . '">+' . ( count( $t['pages'] ) - 4 ) . ' ακόμα</a>';
		}
		if ( 'holiday' === $t['code'] ) {
			$links = '<a href="' . esc_url( admin_url( 'post-new.php?post_type=ellada_recipe' ) ) . '">Νέα συνταγή</a><a href="' . esc_url( admin_url( 'edit.php?post_type=ellada_recipe&page=ellada-missing' ) ) . '">Συνταγές που λείπουν</a>';
		}
		printf( '<li>%s <strong>%s</strong>%s<div class="es-links">%s</div></li>', ellada_seo_sev_badge( $t['sev'] ), esc_html( $t['text'] ), $t['count'] ? ' <span class="es-n">' . (int) $t['count'] . '</span>' : '', $links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</ol>';

	$g = (array) get_option( 'ellada_gsc_data', array() );
	if ( ! empty( $g['totals'] ) ) {
		printf(
			'<h2>Google (28 μέρες)</h2><div class="es-grid"><div class="es-card es-stat"><p class="es-big">%s</p><p class="es-sub">κλικ</p></div><div class="es-card es-stat"><p class="es-big">%s</p><p class="es-sub">εμφανίσεις</p></div><div class="es-card es-stat"><p class="es-big">%.1f%%</p><p class="es-sub">ποσοστό κλικ</p></div><div class="es-card es-stat"><p class="es-big">%.1f</p><p class="es-sub">μέση θέση</p></div></div>',
			esc_html( number_format_i18n( $g['totals']['clicks'] ?? 0 ) ),
			esc_html( number_format_i18n( $g['totals']['impressions'] ?? 0 ) ),
			100 * (float) ( $g['totals']['ctr'] ?? 0 ),
			(float) ( $g['totals']['position'] ?? 0 )
		);
	} elseif ( ! ellada_gsc_ready() ) {
		echo '<div class="es-card es-note"><strong>Google Search Console:</strong> δεν έχει συνδεθεί ακόμα. Δείτε την καρτέλα <a href="' . esc_url( admin_url( 'admin.php?page=ellada-seo&tab=google' ) ) . '">Google</a>.</div>';
	}
}

function ellada_seo_sparkline( $vals ) {
	$vals = array_slice( array_values( $vals ), -30 );
	if ( count( $vals ) < 2 ) {
		return '';
	}
	$w   = 160;
	$h   = 36;
	$pts = array();
	foreach ( $vals as $i => $v ) {
		$pts[] = round( $i * $w / ( count( $vals ) - 1 ), 1 ) . ',' . round( $h - $v / 100 * $h, 1 );
	}
	return '<svg class="es-spark" viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" aria-hidden="true"><polyline points="' . implode( ' ', $pts ) . '"/></svg>';
}

function ellada_seo_tab_pages( $pages ) {
	$defs  = ellada_seo_check_defs();
	$issue = sanitize_key( $_GET['issue'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$type  = sanitize_key( $_GET['type'] ?? '' );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$rows  = $pages;
	if ( $issue ) {
		$rows = array_filter( $rows, fn( $p ) => isset( $p['issues'][ $issue ] ) );
	}
	if ( $type ) {
		$rows = array_filter( $rows, fn( $p ) => ( $p['post_id'] ? get_post_type( (int) $p['post_id'] ) : 'list' ) === $type );
	}
	uasort( $rows, fn( $a, $b ) => $a['score'] <=> $b['score'] );

	$base = admin_url( 'admin.php?page=ellada-seo&tab=pages' );
	echo '<form class="es-filters" method="get"><input type="hidden" name="page" value="ellada-seo"><input type="hidden" name="tab" value="pages">';
	echo '<select name="type"><option value="">Όλοι οι τύποι</option>';
	foreach ( array( 'ellada_recipe' => 'Συνταγές', 'ellada_location' => 'Τόποι', 'ellada_collection' => 'Συλλογές', 'ellada_point' => 'Σημεία', 'page' => 'Σελίδες', 'list' => 'Λίστες' ) as $k => $l ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $type, $k, false ), esc_html( $l ) );
	}
	echo '</select><select name="issue"><option value="">Όλα τα προβλήματα</option>';
	foreach ( $defs as $k => $d ) {
		$n = count( array_filter( $pages, fn( $p ) => isset( $p['issues'][ $k ] ) ) );
		if ( $n ) {
			printf( '<option value="%s"%s>%s (%d)</option>', esc_attr( $k ), selected( $issue, $k, false ), esc_html( $d[1] ), $n );
		}
	}
	echo '</select> <button class="button">Φίλτρο</button>' . ( $issue || $type ? ' <a href="' . esc_url( $base ) . '">Καθαρισμός</a>' : '' ) . '</form>';

	$gsc = ellada_gsc_ready();
	echo '<table class="widefat striped es-table"><thead><tr><th>Βαθμός</th><th>Σελίδα</th><th>Τι να διορθωθεί</th><th>Σύνδεσμοι προς</th>' . ( $gsc ? '<th>Google</th>' : '' ) . '</tr></thead><tbody>';
	foreach ( $rows as $p ) {
		list( $name, $tlabel, $edit ) = ellada_seo_page_label( $p );
		$list                         = '';
		foreach ( $p['issues'] as $code => $x ) {
			$list .= '<li>' . ellada_seo_sev_badge( $defs[ $code ][0] ) . ' ' . esc_html( $defs[ $code ][1] ) . ( '' !== (string) $x ? ' <em>' . esc_html( $x ) . '</em>' : '' ) . '</li>';
		}
		printf(
			'<tr><td><span class="es-pill %1$s">%2$d</span></td><td><strong>%3$s</strong> <span class="es-type">%4$s</span><div class="es-links">%5$s<a href="%6$s" target="_blank">Προβολή</a></div><div class="es-meta">%7$s</div></td><td>%8$s</td><td>%9$s</td>%10$s</tr>',
			esc_attr( ellada_seo_score_class( $p['score'] ) ),
			(int) $p['score'],
			esc_html( $name ),
			esc_html( $tlabel ),
			$edit ? '<a href="' . esc_url( $edit ) . '">Επεξεργασία</a>' : '',
			esc_url( $p['url'] ),
			esc_html( $p['title'] ) . '<br>' . esc_html( mb_strimwidth( $p['desc'], 0, 170, '…' ) ),
			$list ? '<ul class="es-issues">' . $list . '</ul>' : '<span class="es-ok">✓ Όλα καλά</span>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			(int) ( $p['inbound'] ?? 0 ),
			$gsc ? '<td><button type="button" class="button button-small es-inspect" data-url="' . esc_attr( $p['url'] ) . '">Έλεγχος</button><div class="es-insp"></div></td>' : ''
		);
	}
	echo '</tbody></table>';
}

function ellada_seo_tab_opps( $pages ) {
	echo '<h2>Ημερολόγιο γιορτών</h2><p class="description">Το Google χρειάζεται εβδομάδες για να βρει και να κατατάξει μια νέα σελίδα. Οι συνταγές μιας γιορτής θέλουν δημοσίευση 6–8 εβδομάδες πριν.</p><table class="widefat striped"><thead><tr><th>Γιορτή</th><th>Πότε</th><th>Σε</th><th>Συνταγές με την περίσταση</th></tr></thead><tbody>';
	foreach ( ellada_seo_holidays() as $h ) {
		printf( '<tr><td><strong>%s</strong></td><td>%s</td><td>%d μέρες</td><td>%s</td></tr>', esc_html( $h[0] ), esc_html( wp_date( 'j/n/Y', $h[1]->getTimestamp() ) ), (int) $h[3], $h[2] ? (int) $h[2] : '<span class="es-bad">0</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</tbody></table>';

	$missing = function_exists( 'ellada_missing_recipes' ) ? ellada_missing_recipes() : array();
	printf( '<h2>Πιάτα του οδηγού χωρίς συνταγή <span class="es-n">%d</span></h2><p class="description">Κάθε νέα συνταγή γίνεται αυτόματα σύνδεσμος από τη σελίδα του τόπου. <a href="%s">Όλη η λίστα →</a></p><p class="es-chips">', count( $missing ), esc_url( admin_url( 'edit.php?post_type=ellada_recipe&page=ellada-missing' ) ) );
	foreach ( array_slice( $missing, 0, 30 ) as $m ) {
		printf( '<a href="%s">%s <small>%s</small></a>', esc_url( add_query_arg( array( 'post_type' => 'ellada_recipe', 'ellada_title' => rawurlencode( $m[0] ), 'ellada_location' => $m[2] ), admin_url( 'post-new.php' ) ) ), esc_html( $m[0] ), esc_html( get_the_title( $m[2] ) ) );
	}
	echo '</p>';

	$noguide = ellada_seo_places_without_guide();
	echo '<h2>Τόποι με συνταγές αλλά χωρίς κείμενο οδηγού</h2><p class="description">Χωρίς κείμενο, η σελίδα του τόπου δεν είναι στο Google — και μαζί της χάνεται ο δρόμος προς τις συνταγές του.</p>';
	if ( $noguide ) {
		echo '<ul class="es-list">';
		foreach ( $noguide as $n ) {
			printf( '<li><a href="%s">%s</a> — %d συνταγές</li>', esc_url( get_edit_post_link( $n[0] ) ), esc_html( get_the_title( $n[0] ) ), (int) $n[1] );
		}
		echo '</ul>';
	} else {
		echo '<p>Κανένας. ✓</p>';
	}

	$orph = array_filter( $pages, fn( $p ) => isset( $p['issues']['orphan'] ) );
	echo '<h2>Ορφανές σελίδες</h2><p class="description">Καμία άλλη σελίδα του site δεν δείχνει σε αυτές (εκτός από μενού/footer). Ένας σύνδεσμος από μια σχετική σελίδα βοηθά το Google να τις βρει.</p>';
	if ( $orph ) {
		echo '<ul class="es-list">';
		foreach ( $orph as $p ) {
			list( $name, $tl, $edit ) = ellada_seo_page_label( $p );
			printf( '<li><a href="%s">%s</a> <span class="es-type">%s</span></li>', esc_url( $edit ? $edit : $p['url'] ), esc_html( $name ), esc_html( $tl ) );
		}
		echo '</ul>';
	} else {
		echo '<p>Καμία. ✓</p>';
	}
}

function ellada_seo_tab_tech( $last ) {
	$robots  = wp_remote_retrieve_body( wp_remote_get( home_url( '/robots.txt' ), array( 'timeout' => 10 ) ) );
	$sm_code = (int) wp_remote_retrieve_response_code( wp_remote_head( home_url( '/wp-sitemap.xml' ), array( 'timeout' => 10 ) ) );
	$checks  = array(
		array( '1' === (string) get_option( 'blog_public' ), 'Το site επιτρέπει στις μηχανές αναζήτησης να το δουν', 'Ρυθμίσεις → Ανάγνωση: ξετσεκάρετε «Αποθάρρυνση μηχανών αναζήτησης».' ),
		array( 0 === strpos( home_url(), 'https://' ), 'Ασφαλής σύνδεση (https)', '' ),
		array( 200 === $sm_code, 'Sitemap: ' . home_url( '/wp-sitemap.xml' ), 'Το sitemap δεν απαντά.' ),
		array( false !== stripos( $robots, 'sitemap:' ), 'Το robots.txt δείχνει το sitemap', '' ),
		array( false === stripos( $robots, "disallow: /\n" ), 'Το robots.txt δεν μπλοκάρει όλο το site', 'Το robots.txt έχει «Disallow: /».' ),
		array( (bool) get_option( 'permalink_structure' ), 'Φιλικές διευθύνσεις (permalinks)', '' ),
		array( ellada_gsc_ready(), 'Google Search Console συνδεδεμένο', 'Δείτε την καρτέλα Google.' ),
	);
	echo '<table class="widefat striped es-table"><tbody>';
	foreach ( $checks as $c ) {
		printf( '<tr><td style="width:30px">%s</td><td><strong>%s</strong>%s</td></tr>', $c[0] ? '<span class="es-ok">✓</span>' : '<span class="es-bad">✗</span>', esc_html( $c[1] ), ! $c[0] && $c[2] ? '<br><span class="description">' . esc_html( $c[2] ) . '</span>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</tbody></table>';

	$broken = (array) ( $last['broken'] ?? array() );
	echo '<h2>Σπασμένοι εσωτερικοί σύνδεσμοι</h2>' . ( $broken ? '<ul class="es-list"><li>' . implode( '</li><li>', array_map( 'esc_html', $broken ) ) . '</li></ul><p class="description">Στην καρτέλα «Σελίδες» → φίλτρο «Σύνδεσμοι σε σελίδες που δεν υπάρχουν» φαίνεται πού βρίσκονται.</p>' : '<p>Κανένας. ✓</p>' );

	$log = (array) get_option( 'ellada_404_log', array() );
	uasort( $log, fn( $a, $b ) => $b[0] <=> $a[0] );
	echo '<h2>Σελίδες που ζήτησαν οι επισκέπτες και δεν βρήκαν (404)</h2><p class="description">Μόνο η διεύθυνση και πόσες φορές — χωρίς IP ή άλλα στοιχεία. Αν μια παλιά διεύθυνση ζητιέται συχνά, αξίζει ανακατεύθυνση.</p>';
	if ( $log ) {
		echo '<table class="widefat striped"><thead><tr><th>Διεύθυνση</th><th>Φορές</th><th>Τελευταία</th></tr></thead><tbody>';
		foreach ( array_slice( $log, 0, 40, true ) as $path => $v ) {
			printf( '<tr><td><code>%s</code></td><td>%d</td><td>%s</td></tr>', esc_html( urldecode( $path ) ), (int) $v[0], esc_html( wp_date( 'j/n H:i', (int) $v[1] ) ) );
		}
		echo '</tbody></table>';
	} else {
		echo '<p>Τίποτα ακόμα (η καταγραφή ξεκίνησε τώρα).</p>';
	}
}

function ellada_seo_tab_google() {
	if ( ! ellada_gsc_ready() ) {
		$conf = defined( 'ELLADA_GSC_KEY_FILE' ) ? ' Η σταθερά ELLADA_GSC_KEY_FILE υπάρχει, αλλά το αρχείο δεν διαβάζεται ή δεν είναι σωστό.' : '';
		echo '<div class="es-card es-guide"><h2>Σύνδεση με το Google Search Console</h2>' . ( $conf ? '<p class="es-bad">' . esc_html( $conf ) . '</p>' : '' ) . '
		<ol>
			<li><strong>Επαλήθευση του site</strong> — στο <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Search Console</a>: «Προσθήκη ιδιοκτησίας» → «Τομέας» → <code>' . esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ) . '</code>. Η εγγραφή TXT μπαίνει στο Hostinger: hPanel → Domains → DNS / Nameservers → DNS records (τύπος TXT, όνομα @).</li>
			<li><strong>Sitemap</strong> — στο Search Console → Sitemaps: <code>wp-sitemap.xml</code>.</li>
			<li><strong>Λογαριασμός υπηρεσίας</strong> — στο <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud</a>: νέο project → «APIs & Services» → ενεργοποίηση <em>Google Search Console API</em> → «Credentials» → «Create credentials» → «Service account» → στο νέο λογαριασμό «Keys» → «Add key» → JSON. Κατεβαίνει ένα αρχείο .json.</li>
			<li><strong>Πρόσβαση</strong> — στο Search Console → Ρυθμίσεις → Χρήστες και άδειες → «Προσθήκη χρήστη»: το email του λογαριασμού υπηρεσίας (τελειώνει σε <code>iam.gserviceaccount.com</code>), άδεια <em>Περιορισμένη</em>.</li>
			<li><strong>Το κλειδί στο site</strong> — με το File Manager του Hostinger ανεβάστε το .json <em>έξω</em> από το public_html (π.χ. <code>/home/…/gsc-key.json</code>) και στο <code>wp-config.php</code> προσθέστε:<br><code>define( \'ELLADA_GSC_KEY_FILE\', \'/home/…/gsc-key.json\' );</code><br>Το κλειδί δεν αποθηκεύεται στη βάση και δεν το βλέπει κανείς από εδώ.</li>
		</ol><p class="description">Η εφαρμογή μόνο <em>διαβάζει</em> από το Google (webmasters.readonly). Τα δεδομένα είναι συγκεντρωτικά — χωρίς προσωπικά στοιχεία επισκεπτών.</p></div>';
		return;
	}
	$g = (array) get_option( 'ellada_gsc_data', array() );
	echo '<p><button type="button" class="button" id="es-gsc">Ανανέωση από το Google</button> <span id="es-gsc-msg">' . ( ! empty( $g['time'] ) ? esc_html( 'Τελευταία ενημέρωση: ' . wp_date( 'j/n/Y H:i', $g['time'] ) . ( ! empty( $g['range'] ) ? ' · δεδομένα ' . $g['range'][0] . ' έως ' . $g['range'][1] : '' ) ) : '' ) . '</span></p>';
	if ( ! empty( $g['error'] ) ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html( $g['error'] ) . '</p></div>';
		return;
	}
	if ( empty( $g['totals'] ) && empty( $g['queries'] ) ) {
		echo '<p>Δεν υπάρχουν ακόμα δεδομένα. Σε ένα νέο site το Google χρειάζεται μερικές εβδομάδες.</p>';
		return;
	}
	$table = function ( $title, $desc, $rows, $first ) {
		echo '<h2>' . esc_html( $title ) . '</h2>' . ( $desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : '' );
		if ( ! $rows ) {
			echo '<p>—</p>';
			return;
		}
		echo '<table class="widefat striped es-table"><thead><tr><th>' . esc_html( $first ) . '</th><th>Κλικ</th><th>Εμφανίσεις</th><th>Ποσοστό κλικ</th><th>Θέση</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$k = $r['keys'][0];
			printf( '<tr><td>%s</td><td>%d</td><td>%d</td><td>%.1f%%</td><td>%.1f</td></tr>', 0 === strpos( $k, 'http' ) ? '<a href="' . esc_url( $k ) . '" target="_blank">' . esc_html( urldecode( wp_parse_url( $k, PHP_URL_PATH ) ) ) . '</a>' : esc_html( $k ), (int) $r['clicks'], (int) $r['impressions'], 100 * $r['ctr'], $r['position'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</tbody></table>';
	};
	$q    = (array) ( $g['queries'] ?? array() );
	$near = array_filter( (array) ( $g['qp'] ?? array() ), fn( $r ) => $r['position'] >= 8 && $r['position'] <= 20 && $r['impressions'] >= 10 );
	usort( $near, fn( $a, $b ) => $b['impressions'] <=> $a['impressions'] );
	$low = array_filter( (array) ( $g['pages'] ?? array() ), fn( $r ) => $r['impressions'] >= 50 && $r['ctr'] < 0.02 );
	echo '<h2>Σχεδόν στην πρώτη σελίδα</h2><p class="description">Αναζητήσεις όπου είμαστε στις θέσεις 8–20. Μια μικρή βελτίωση της σελίδας (κείμενο, φωτογραφία, σύνδεσμοι) εδώ φέρνει τα περισσότερα νέα κλικ.</p>';
	if ( $near ) {
		echo '<table class="widefat striped es-table"><thead><tr><th>Αναζήτηση</th><th>Σελίδα</th><th>Εμφανίσεις</th><th>Θέση</th></tr></thead><tbody>';
		foreach ( array_slice( $near, 0, 25 ) as $r ) {
			printf( '<tr><td>%s</td><td><a href="%s" target="_blank">%s</a></td><td>%d</td><td>%.1f</td></tr>', esc_html( $r['keys'][0] ), esc_url( $r['keys'][1] ), esc_html( urldecode( wp_parse_url( $r['keys'][1], PHP_URL_PATH ) ) ), (int) $r['impressions'], $r['position'] );
		}
		echo '</tbody></table>';
	} else {
		echo '<p>—</p>';
	}
	$table( 'Εμφανίζονται αλλά δεν πατιούνται', 'Πολλές εμφανίσεις, ποσοστό κλικ κάτω από 2%: ο τίτλος ή η περιγραφή δεν «πείθει». Αξίζει να ξαναγραφτούν.', array_slice( $low, 0, 20 ), 'Σελίδα' );
	$table( 'Με τι μας βρίσκουν', '', array_slice( $q, 0, 50 ), 'Αναζήτηση' );
	$table( 'Οι σελίδες στο Google', '', array_slice( (array) ( $g['pages'] ?? array() ), 0, 50 ), 'Σελίδα' );
	if ( ! empty( $g['sitemaps'] ) ) {
		echo '<h2>Sitemaps στο Google</h2><ul class="es-list">';
		foreach ( $g['sitemaps'] as $s ) {
			printf( '<li><code>%s</code> — τελευταία ανάγνωση %s%s</li>', esc_html( $s['path'] ), esc_html( isset( $s['lastDownloaded'] ) ? wp_date( 'j/n/Y', strtotime( $s['lastDownloaded'] ) ) : '—' ), ! empty( $s['errors'] ) ? ' · <span class="es-bad">' . (int) $s['errors'] . ' σφάλματα</span>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</ul>';
	}
}

function ellada_seo_tab_settings() {
	$s = ellada_seo_settings();
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success inline"><p>Αποθηκεύτηκε.</p></div>';
	}
	echo '<form method="post" class="es-card" style="max-width:720px">';
	wp_nonce_field( 'ellada_seo_settings' );
	printf( '<p><label><input type="checkbox" name="weekly_email" value="1"%s> <strong>Εβδομαδιαία αναφορά</strong> κάθε Δευτέρα πρωί</label></p>', checked( $s['weekly_email'], 1, false ) );
	printf( '<p><label>Email παραλήπτη<br><input type="email" name="email_to" class="regular-text" value="%s" placeholder="%s"></label><br><span class="description">Κενό = το email διαχείρισης του site.</span></p>', esc_attr( $s['email_to'] ), esc_attr( get_option( 'admin_email' ) ) );
	printf( '<p><label>Ιδιοκτησία Search Console<br><input type="text" name="gsc_property" class="regular-text" value="%s"></label><br><span class="description">«sc-domain:…» για ιδιοκτησία τομέα, ή η πλήρης διεύθυνση (https://…/) για πρόθεμα URL.</span></p>', esc_attr( $s['gsc_property'] ) );
	echo '<p><button class="button button-primary" name="ellada_seo_save" value="1">Αποθήκευση</button> <button class="button" name="ellada_seo_save" value="1" onclick="this.form.send_test.value=1">Αποθήκευση και αποστολή δοκιμαστικής αναφοράς</button><input type="hidden" name="send_test" value=""></p>';
	echo '<h3>Προεπισκόπηση αναφοράς</h3><pre class="es-pre">' . esc_html( ellada_seo_report_text() ) . '</pre></form>';
}

/* Στυλ και script της σελίδας. */
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_ellada-seo' !== $screen->id ) {
			return;
		}
		echo '<style>
		.ellada-seo .es-run{margin:14px 0}
		.ellada-seo .es-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin:12px 0 8px}
		.ellada-seo .es-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px 18px}
		.ellada-seo .es-score{grid-column:span 2;border-width:2px}
		.ellada-seo .es-score.is-good{border-color:#2e7d32}.ellada-seo .es-score.is-ok{border-color:#dba617}.ellada-seo .es-score.is-bad{border-color:#b32d2e}
		.ellada-seo .es-k{margin:0;font-weight:600;color:#50575e}
		.ellada-seo .es-big{margin:2px 0;font-size:34px;font-weight:700;line-height:1.1}
		.ellada-seo .es-big small{font-size:16px;color:#787c82;font-weight:400}
		.ellada-seo .es-sub{margin:0;color:#646970}
		.ellada-seo .es-stat .es-big{font-size:26px}
		.ellada-seo .es-spark{margin-top:8px}.ellada-seo .es-spark polyline{fill:none;stroke:#f97316;stroke-width:2}
		.ellada-seo .es-todo{margin:0 0 20px 1.4em}.ellada-seo .es-todo li{margin:0 0 12px}
		.ellada-seo .es-links{display:flex;flex-wrap:wrap;gap:4px 12px;margin-top:4px;font-size:12px}
		.ellada-seo .es-n{display:inline-block;min-width:20px;padding:0 6px;border-radius:10px;background:#f0f0f1;text-align:center;font-size:12px}
		.ellada-seo .es-sev{display:inline-block;padding:1px 7px;border-radius:9px;font-size:11px;font-weight:600;margin-right:4px}
		.ellada-seo .es-sev.is-critical{background:#fbe5e5;color:#8a1f1f}.ellada-seo .es-sev.is-high{background:#fdeedd;color:#8a4b0f}.ellada-seo .es-sev.is-medium{background:#fcf6dc;color:#6b5a00}.ellada-seo .es-sev.is-low{background:#eef0f2;color:#50575e}
		.ellada-seo .es-pill{display:inline-block;min-width:34px;padding:3px 0;border-radius:6px;text-align:center;font-weight:700;color:#fff}
		.ellada-seo .es-pill.is-good{background:#2e7d32}.ellada-seo .es-pill.is-ok{background:#b8860b}.ellada-seo .es-pill.is-bad{background:#b32d2e}
		.ellada-seo .es-table td{vertical-align:top}
		.ellada-seo .es-issues{margin:0}.ellada-seo .es-issues li{margin:0 0 4px}.ellada-seo .es-issues em{color:#787c82;font-style:normal;font-size:12px}
		.ellada-seo .es-type{color:#787c82;font-size:12px}
		.ellada-seo .es-meta{margin-top:6px;font-size:12px;color:#646970;max-width:520px}
		.ellada-seo .es-ok{color:#2e7d32;font-weight:600}.ellada-seo .es-bad{color:#b32d2e;font-weight:600}
		.ellada-seo .es-filters{margin:8px 0 12px}
		.ellada-seo .es-chips{display:flex;flex-wrap:wrap;gap:6px}.ellada-seo .es-chips a{padding:4px 10px;border:1px solid #dcdcde;border-radius:14px;background:#fff;text-decoration:none}.ellada-seo .es-chips small{color:#787c82}
		.ellada-seo .es-list li{margin:0 0 4px}
		.ellada-seo .es-guide ol li{margin-bottom:10px;line-height:1.6}
		.ellada-seo .es-note{margin-top:16px}
		.ellada-seo .es-pre{white-space:pre-wrap;background:#f6f7f7;border:1px solid #dcdcde;padding:12px;border-radius:6px;font-size:12px}
		.ellada-seo .es-insp{font-size:12px;margin-top:4px;max-width:220px}
		</style>';
		$nonce = wp_create_nonce( 'ellada_seo' );
		echo "<script>
		document.addEventListener('DOMContentLoaded',function(){
			var ajax=" . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ",nonce=" . wp_json_encode( $nonce ) . ";
			function post(data){var f=new FormData();f.append('_ajax_nonce',nonce);for(var k in data)f.append(k,data[k]);return fetch(ajax,{method:'POST',body:f,credentials:'same-origin'}).then(function(r){return r.json();});}
			var run=document.getElementById('es-run'),pr=document.getElementById('es-progress');
			if(run)run.addEventListener('click',function(){run.disabled=true;var first=true;
				(function step(){post({action:'ellada_seo_step',restart:first?1:''}).then(function(r){first=false;
					if(!r.success){pr.textContent='Σφάλμα.';run.disabled=false;return;}
					pr.textContent='Ανάλυση… '+r.data.progress;
					if(r.data.done){pr.textContent='Ολοκληρώθηκε — βαθμός '+r.data.score+'/100. Ανανέωση…';location.reload();}else step();
				}).catch(function(){pr.textContent='Σφάλμα σύνδεσης — δοκιμάστε ξανά.';run.disabled=false;});})();
			});
			var g=document.getElementById('es-gsc');
			if(g)g.addEventListener('click',function(){g.disabled=true;document.getElementById('es-gsc-msg').textContent='Ανάγνωση από το Google…';
				post({action:'ellada_gsc',what:'refresh'}).then(function(r){if(r.success)location.reload();else{document.getElementById('es-gsc-msg').textContent=r.data||'Σφάλμα';g.disabled=false;}});});
			document.querySelectorAll('.es-inspect').forEach(function(b){b.addEventListener('click',function(){var out=b.nextElementSibling;b.disabled=true;out.textContent='…';
				post({action:'ellada_gsc',what:'inspect',url:b.getAttribute('data-url')}).then(function(r){b.disabled=false;
					if(!r.success){out.textContent=r.data||'Σφάλμα';return;}
					var d=r.data,ok=d.verdict==='PASS';out.textContent='';var st=document.createElement('strong');st.style.color=ok?'#2e7d32':'#b32d2e';st.textContent=ok?'Στο Google':'Όχι στο Google';out.appendChild(st);
					[d.coverage,d.rich].forEach(function(t){if(t){out.appendChild(document.createElement('br'));out.appendChild(document.createTextNode(t));}});
					if(d.link&&/^https:\/\/search\.google\.com\//.test(d.link)){out.appendChild(document.createElement('br'));var a=document.createElement('a');a.href=d.link;a.target='_blank';a.rel='noopener';a.textContent='Λεπτομέρειες';out.appendChild(a);}
				});});});
		});
		</script>";
	}
);
