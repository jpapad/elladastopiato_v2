<?php
/**
 * Social: έτοιμες αναρτήσεις για Instagram (post + story) και Facebook από κάθε συνταγή.
 *
 * - Μόλις δημοσιεύεται συνταγή φτιάχνεται «πακέτο» (κείμενα + εικόνες στα σωστά μεγέθη).
 * - Οθόνη ελέγχου (wp-admin → Social): προεπισκόπηση, διόρθωση, αντιγραφή/λήψη, αποστολή τώρα ή προγραμματισμός.
 * - Σύνδεση με Meta (Facebook Login): βρίσκει μόνη της τις Σελίδες και το Instagram που είναι συνδεδεμένο σε καθεμία.
 *   Τίποτα δεν στέλνεται χωρίς κλικ του διαχειριστή. Τα tokens δεν εμφανίζονται ποτέ στη σελίδα.
 *
 * @package ellada-map
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_SOCIAL_GRAPH = 'https://graph.facebook.com/v21.0/';
const ELLADA_SOCIAL_NETS  = array( 'ig', 'story', 'fb' );

function ellada_social_net_label( $n ) {
	$l = array(
		'ig'    => 'Instagram · post',
		'story' => 'Instagram · story',
		'fb'    => 'Facebook',
	);
	return $l[ $n ] ?? $n;
}

/* ═════════════════════ Στοιχεία συνταγής ═════════════════════ */

function ellada_social_plain( $s ) {
	return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $s, ENT_QUOTES, 'UTF-8' ) ) ) );
}

/** Οι πρώτες $n προτάσεις ενός κειμένου. */
function ellada_social_sentences( $s, $n = 1 ) {
	$s = ellada_social_plain( $s );
	if ( '' === $s ) {
		return '';
	}
	$parts = preg_split( '/(?<=[.!;…])\s+/u', $s );
	return trim( implode( ' ', array_slice( $parts, 0, $n ) ) );
}

function ellada_social_minutes( $m ) {
	$m = (int) $m;
	if ( $m <= 0 ) {
		return '';
	}
	if ( $m < 60 ) {
		return $m . ' λεπτά';
	}
	$h = intdiv( $m, 60 );
	$r = $m % 60;
	return $h . ( 1 === $h ? ' ώρα' : ' ώρες' ) . ( $r ? ' ' . $r . '′' : '' );
}

function ellada_social_ctx( $id ) {
	$region = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $id ) : '';
	$from   = ( $region && function_exists( 'ellada_region_from' ) ) ? ellada_region_from( $region ) : '';
	$total  = (int) get_post_meta( $id, '_ellada_total_time', true );
	if ( ! $total ) {
		$total = (int) get_post_meta( $id, 'prep_time', true ) + (int) get_post_meta( $id, 'cook_time', true );
	}
	$diff  = array(
		'easy'   => 'Εύκολη',
		'medium' => 'Μέτρια',
		'hard'   => 'Απαιτητική',
	);
	$cats  = wp_get_post_terms( $id, 'recipe_category', array( 'fields' => 'names' ) );
	$diets = wp_get_post_terms( $id, 'recipe_diet', array( 'fields' => 'names' ) );
	return array(
		'id'       => $id,
		'title'    => ellada_social_plain( get_the_title( $id ) ),
		'region'   => $region,
		'from'     => $from,
		'short'    => ellada_social_plain( get_post_meta( $id, 'short_description', true ) ),
		'story'    => (string) get_post_meta( $id, 'story', true ),
		'total'    => $total,
		'servings' => (int) get_post_meta( $id, 'servings', true ),
		'diff'     => $diff[ (string) get_post_meta( $id, 'difficulty', true ) ] ?? '',
		'cats'     => is_wp_error( $cats ) ? array() : $cats,
		'diets'    => is_wp_error( $diets ) ? array() : $diets,
		'url'      => get_permalink( $id ),
		'tid'      => (int) get_post_thumbnail_id( $id ),
	);
}

function ellada_social_tag( $s ) {
	$s = mb_strtolower( (string) $s );
	$s = strtr( $s, array( 'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω', 'ΐ' => 'ι', 'ΰ' => 'υ', 'ϊ' => 'ι', 'ϋ' => 'υ' ) );
	$s = preg_replace( '/[^\p{L}\p{N}]+/u', '', $s );
	return '' === $s ? '' : '#' . $s;
}

/** Hashtags: site, πιάτο, τόπος, κατηγορία, διατροφή + σταθερά (χωρίς τόνους, όπως τα γράφουν οι περισσότεροι). */
function ellada_social_hashtags( $c ) {
	$local = array(
		'Κρήτη'        => '#κρητικηκουζινα',
		'Ήπειρος'      => '#ηπειρωτικηκουζινα',
		'Πελοπόννησος' => '#πελοποννησιακηκουζινα',
		'Θεσσαλία'     => '#θεσσαλικηκουζινα',
		'Ιόνια Νησιά'  => '#επτανησιακηκουζινα',
	);
	$dish = ellada_social_tag( $c['title'] );
	if ( mb_strlen( $dish ) > 18 ) {
		$dish = ellada_social_tag( preg_split( '/\s+/u', $c['title'] )[0] );
	}
	$t = array( '#ελλαδαστοπιατο', $dish );
	if ( $c['region'] ) {
		$t[] = ellada_social_tag( $c['region'] );
		if ( isset( $local[ $c['region'] ] ) ) {
			$t[] = $local[ $c['region'] ];
		}
	}
	foreach ( array_merge( $c['cats'], $c['diets'] ) as $x ) {
		$t[] = ellada_social_tag( $x );
	}
	$t = array_merge( $t, array( '#ελληνικηκουζινα', '#παραδοσιακεςσυνταγες', '#συνταγες', '#σπιτικοφαγητο', '#greekfood', '#greekrecipes', '#mediterraneandiet' ) );
	return implode( ' ', array_slice( array_values( array_unique( array_filter( $t ) ) ), 0, 15 ) );
}

/** Κείμενα ανά δίκτυο (πρότυπα στο ύφος του site). */
function ellada_social_texts( $id ) {
	$c     = ellada_social_ctx( $id );
	$facts = array_filter(
		array(
			$c['total'] ? '⏱ ' . ellada_social_minutes( $c['total'] ) : '',
			$c['servings'] ? '🍽 ' . $c['servings'] . ' μερίδες' : '',
			$c['diff'] ? '👩‍🍳 ' . $c['diff'] : '',
		)
	);
	$head  = $c['title'] . ( $c['from'] ? ' · από ' . $c['from'] : '' );
	$story = ellada_social_sentences( $c['story'], 1 );

	$ig = $head . "\n\n"
		. ( $c['short'] ? $c['short'] . "\n\n" : '' )
		. ( $story && $story !== $c['short'] ? $story . "\n\n" : '' )
		. ( $facts ? implode( ' · ', $facts ) . "\n\n" : '' )
		. "👉 Όλη η συνταγή βήμα-βήμα στο elladastopiato.gr (link στο bio)\n"
		. "💾 Αποθηκεύστε τη για να τη βρίσκετε εύκολα!\n\n"
		. ellada_social_hashtags( $c );

	$fb = '🍽 ' . $c['title'] . ( $c['from'] ? ' από ' . $c['from'] : '' ) . "\n\n"
		. ( $c['short'] ? $c['short'] . "\n\n" : '' )
		. ( ellada_social_sentences( $c['story'], 2 ) ? ellada_social_sentences( $c['story'], 2 ) . "\n\n" : '' )
		. ( $facts ? implode( ' · ', $facts ) . "\n\n" : '' )
		. "Η συνταγή βήμα-βήμα 👇\n" . $c['url'];

	$st = "Σύνδεσμος για το αυτοκόλλητο «Link»:\n" . $c['url'];

	return array(
		'ig'    => $ig,
		'story' => $st,
		'fb'    => $fb,
	);
}

/* ═════════════════════ Εικόνες (GD) ═════════════════════ */

function ellada_social_caps( $t ) {
	return mb_strtoupper( strtr( $t, array( 'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω', 'ΐ' => 'ϊ', 'ΰ' => 'ϋ', 'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι', 'Ό' => 'Ο', 'Ύ' => 'Υ', 'Ώ' => 'Ω' ) ) );
}

/** Αναδίπλωση με σμίκρυνση: επιστρέφει [size, lines]. */
function ellada_social_fit( $text, $font, $size, $maxw, $maxlines, $min = 36 ) {
	do {
		$lines = array();
		$cur   = '';
		foreach ( preg_split( '/\s+/u', $text ) as $word ) {
			$try = trim( $cur . ' ' . $word );
			$bb  = imagettfbbox( $size, 0, $font, $try );
			if ( $cur && ( $bb[2] - $bb[0] ) > $maxw ) {
				$lines[] = $cur;
				$cur     = $word;
			} else {
				$cur = $try;
			}
		}
		$lines[] = $cur;
		$wide    = 0;
		foreach ( $lines as $ln ) {
			$bb   = imagettfbbox( $size, 0, $font, $ln );
			$wide = max( $wide, $bb[2] - $bb[0] );
		}
		$ok = count( $lines ) <= $maxlines && $wide <= $maxw;
		if ( ! $ok ) {
			$size -= 4;
		}
	} while ( ! $ok && $size > $min );
	return array( $size, $lines );
}

/** Φωτογραφία σε ορθογώνιο (cover). */
function ellada_social_cover( $im, $file, $x, $y, $w, $h ) {
	$src = @imagecreatefromstring( (string) file_get_contents( $file ) ); // phpcs:ignore
	if ( ! $src ) {
		return false;
	}
	$sw = imagesx( $src );
	$sh = imagesy( $src );
	$r  = max( $w / $sw, $h / $sh );
	$cw = (int) round( $w / $r );
	$ch = (int) round( $h / $r );
	imagecopyresampled( $im, $src, $x, $y, (int) ( ( $sw - $cw ) / 2 ), (int) ( ( $sh - $ch ) / 2 ), $w, $h, $cw, $ch );
	imagedestroy( $src );
	return true;
}

function ellada_social_png( $im, $file, $x, $y, $w ) {
	if ( ! $file || ! file_exists( $file ) ) {
		return 0;
	}
	$p = @imagecreatefromstring( (string) file_get_contents( $file ) ); // phpcs:ignore
	if ( ! $p ) {
		return 0;
	}
	$h = (int) round( imagesy( $p ) * $w / imagesx( $p ) );
	imagecopyresampled( $im, $p, $x, $y, 0, 0, $w, $h, imagesx( $p ), imagesy( $p ) );
	imagedestroy( $p );
	return $h;
}

/** Η Ελλάδα με τονισμένη την περιφέρεια (από public/regions-mini.json, viewBox 300×260), με εξομάλυνση ×2. */
function ellada_social_map( $im, $region, $x, $y, $w ) {
	$f = ELLADA_MAP_DIR . 'public/regions-mini.json';
	$d = file_exists( $f ) ? json_decode( (string) file_get_contents( $f ), true ) : null;
	if ( empty( $d['regions'] ) ) {
		return false;
	}
	$k  = 2;
	$h  = (int) round( $w * 260 / 300 );
	$cv = imagecreatetruecolor( $w * $k, $h * $k );
	imagesavealpha( $cv, true );
	imagealphablending( $cv, false );
	imagefill( $cv, 0, 0, imagecolorallocatealpha( $cv, 0, 0, 0, 127 ) );
	imagealphablending( $cv, true );
	$sc  = $w * $k / 300;
	$off = imagecolorallocate( $cv, 44, 104, 102 );
	$on  = imagecolorallocate( $cv, 217, 119, 69 );
	foreach ( array( false, true ) as $pass ) {
		foreach ( $d['regions'] as $name => $path ) {
			if ( ( $name === $region ) !== $pass ) {
				continue;
			}
			foreach ( preg_split( '/M/', (string) $path, -1, PREG_SPLIT_NO_EMPTY ) as $sub ) {
				$pts = array();
				foreach ( preg_split( '/\s+/', trim( str_replace( 'Z', '', $sub ) ) ) as $pt ) {
					$xy = explode( ',', $pt );
					if ( 2 === count( $xy ) ) {
						$pts[] = (int) round( (float) $xy[0] * $sc );
						$pts[] = (int) round( (float) $xy[1] * $sc );
					}
				}
				if ( count( $pts ) >= 6 ) {
					imagefilledpolygon( $cv, $pts, $pass ? $on : $off );
				}
			}
		}
	}
	imagecopyresampled( $im, $cv, $x, $y, 0, 0, $w, $h, $w * $k, $h * $k );
	imagedestroy( $cv );
	return true;
}

function ellada_social_pill( $im, $x, $y, $w, $h, $col ) {
	$r = (int) ( $h / 2 );
	imagefilledrectangle( $im, $x + $r, $y, $x + $w - $r, $y + $h, $col );
	imagefilledellipse( $im, $x + $r, $y + $r, $h, $h, $col );
	imagefilledellipse( $im, $x + $w - $r, $y + $r, $h, $h, $col );
}

/**
 * Εικόνα ανά δίκτυο: ig 1080×1350, story 1080×1920. Για fb χρησιμοποιείται η κάρτα κοινοποίησης (og).
 */
function ellada_social_image( $id, $kind, $force = false ) {
	if ( 'fb' === $kind ) {
		$u = function_exists( 'ellada_share2_og_url' ) ? ellada_share2_og_url( $id ) : '';
		if ( ! $u && function_exists( 'ellada_og_card_url' ) ) {
			$u = ellada_og_card_url( $id );
		}
		return $u;
	}
	if ( ! function_exists( 'imagettftext' ) ) {
		return '';
	}
	$c    = ellada_social_ctx( $id );
	$file = $c['tid'] ? get_attached_file( $c['tid'] ) : '';
	$up   = wp_upload_dir();
	$key  = substr( md5( wp_json_encode( array( $c['title'], $c['from'], $c['total'], $c['servings'], $c['diff'], $c['tid'], $file && file_exists( $file ) ? filemtime( $file ) : 0, 's4' ) ) ), 0, 10 );
	$rel  = 'ellada-social/' . $kind . '-' . $id . '-' . $key . '.jpg';
	$path = trailingslashit( $up['basedir'] ) . $rel;
	$url  = trailingslashit( $up['baseurl'] ) . $rel;
	if ( ! $force && file_exists( $path ) ) {
		return $url;
	}
	wp_mkdir_p( dirname( $path ) );
	foreach ( (array) glob( trailingslashit( $up['basedir'] ) . 'ellada-social/' . $kind . '-' . $id . '-*.jpg' ) as $old ) {
		@unlink( $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$w  = 1080;
	$h  = 'story' === $kind ? 1920 : 1350;
	$ph = 'story' === $kind ? 1180 : 860; // ύψος φωτογραφίας
	$im = imagecreatetruecolor( $w, $h );
	imagealphablending( $im, true );
	$pet  = imagecolorallocate( $im, 23, 76, 75 );
	$dark = imagecolorallocate( $im, 18, 60, 59 );
	$gold = imagecolorallocate( $im, 210, 154, 72 );
	$crem = imagecolorallocate( $im, 246, 242, 233 );
	$mute = imagecolorallocate( $im, 169, 189, 184 );
	imagefilledrectangle( $im, 0, 0, $w, $h, $pet );

	$photo = $file && file_exists( $file ) && ellada_social_cover( $im, $file, 0, 0, $w, $ph );
	if ( ! $photo ) {
		imagefilledrectangle( $im, 0, 0, $w, $ph, $dark );
		$dot = imagecolorallocate( $im, 31, 88, 86 );
		for ( $x = 14; $x < $w; $x += 30 ) {
			for ( $y = 14; $y < $ph; $y += 30 ) {
				imagefilledellipse( $im, $x, $y, 4, 4, $dot );
			}
		}
		$mw = 'story' === $kind ? 860 : 760;
		$mh = (int) round( $mw * 260 / 300 );
		if ( ! ellada_social_map( $im, $c['region'], (int) ( ( $w - $mw ) / 2 ), (int) max( 150, ( $ph - $mh ) / 2 + 40 ), $mw ) ) {
			$icon = get_attached_file( (int) get_option( 'site_icon' ) );
			$sz   = 'story' === $kind ? 520 : 440;
			ellada_social_png( $im, $icon, (int) ( ( $w - $sz ) / 2 ), (int) ( ( $ph - $sz ) / 2 ), $sz );
		}
	}
	// Σβήσιμο της φωτογραφίας προς το πετρόλ (κάτω) και ελαφρύ σκούρο πάνω για το λογότυπο.
	$fade = 220;
	for ( $i = 0; $i < $fade; $i++ ) {
		$a = (int) round( 127 * ( 1 - pow( $i / $fade, 1.6 ) ) );
		imageline( $im, 0, $ph - $fade + $i, $w, $ph - $fade + $i, imagecolorallocatealpha( $im, 23, 76, 75, $a ) );
	}
	$top = 200;
	for ( $i = 0; $i < $top; $i++ ) {
		$a = (int) round( 127 - 70 * pow( 1 - $i / $top, 1.5 ) );
		imageline( $im, 0, $i, $w, $i, imagecolorallocatealpha( $im, 10, 36, 35, $a ) );
	}

	$big   = ELLADA_MAP_DIR . 'public/fonts/FiraSansExtraCondensed_800ExtraBold.ttf';
	$small = ELLADA_MAP_DIR . 'public/fonts/Commissioner_600SemiBold.ttf';
	$x     = 72;

	ellada_social_png( $im, get_attached_file( 667 ), $x, 'story' === $kind ? 110 : 60, 'story' === $kind ? 300 : 250 );

	// Κείμενα στο πετρόλ.
	$y = $ph - 10;
	imagettftext( $im, 24, 0, $x, $y, $gold, $small, ellada_social_caps( $c['from'] ? 'Συνταγή από ' . $c['from'] : 'Συνταγή' ) );
	list( $size, $lines ) = ellada_social_fit( ellada_social_caps( $c['title'] ), $big, 'story' === $kind ? 150 : 118, $w - 2 * $x, 'story' === $kind ? 3 : 2 );
	$y += 24 + $size;
	foreach ( $lines as $ln ) {
		imagettftext( $im, $size, 0, $x, $y, $crem, $big, $ln );
		$y += (int) round( $size * 1.1 );
	}
	$facts = array_filter(
		array(
			$c['total'] ? ellada_social_minutes( $c['total'] ) : '',
			$c['servings'] ? $c['servings'] . ' μερίδες' : '',
			$c['diff'],
		)
	);
	if ( $facts ) {
		imagettftext( $im, 26, 0, $x, $y + 22, $mute, $small, ellada_social_caps( implode( '  ·  ', $facts ) ) );
	}

	if ( 'story' === $kind ) {
		// Κουμπί + χώρος για το αυτοκόλλητο «Link».
		$by = $h - 330;
		ellada_social_pill( $im, $x, $by, $w - 2 * $x, 104, $gold );
		$lbl = 'ΔΕΙΤΕ ΤΗ ΣΥΝΤΑΓΗ ΣΤΟ SITE';
		$bb  = imagettfbbox( 30, 0, $small, $lbl );
		imagettftext( $im, 30, 0, (int) ( ( $w - ( $bb[2] - $bb[0] ) ) / 2 ), $by + 66, $dark, $small, $lbl );
		$lb = 'elladastopiato.gr';
		$bb = imagettfbbox( 26, 0, $small, $lb );
		imagettftext( $im, 26, 0, (int) ( ( $w - ( $bb[2] - $bb[0] ) ) / 2 ), $h - 150, $mute, $small, $lb );
	} else {
		imagettftext( $im, 24, 0, $x, $h - 52, $mute, $small, 'elladastopiato.gr' );
	}
	imagefilledrectangle( $im, 0, $h - 16, $w, $h, $gold );

	imagejpeg( $im, $path, 88 );
	imagedestroy( $im );
	return file_exists( $path ) ? $url : '';
}

/* ═════════════════════ Πακέτο ανάρτησης ═════════════════════ */

function ellada_social_get( $id ) {
	$p = get_post_meta( $id, '_ellada_social', true );
	return is_array( $p ) ? $p : array();
}

function ellada_social_build( $id, $reset_texts = false, $force_img = false ) {
	$p     = ellada_social_get( $id );
	$texts = ellada_social_texts( $id );
	foreach ( ELLADA_SOCIAL_NETS as $n ) {
		$cur = $p[ $n ] ?? array();
		if ( $reset_texts || empty( $cur['text'] ) ) {
			$cur['text'] = $texts[ $n ];
		}
		$cur['img'] = ellada_social_image( $id, $n, $force_img );
		if ( empty( $cur['status'] ) ) {
			$cur['status'] = 'ready';
		}
		$p[ $n ] = $cur;
	}
	$p['built'] = time();
	update_post_meta( $id, '_ellada_social', $p );
	return $p;
}

// Νέα συνταγή δημοσιεύεται → έτοιμο πακέτο.
add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'publish' === $new && 'publish' !== $old && 'ellada_recipe' === $post->post_type ) {
			$id = (int) $post->ID;
			add_action(
				'shutdown',
				function () use ( $id ) {
					ellada_social_build( $id );
					$q   = (array) get_option( 'ellada_social_new', array() );
					$q[] = $id;
					update_option( 'ellada_social_new', array_values( array_unique( $q ) ), false );
				}
			);
		}
	},
	10,
	3
);

/* ═════════════════════ Σύνδεση με Meta ═════════════════════ */

function ellada_social_app() {
	$o = (array) get_option( 'ellada_social_app', array() );
	return array(
		'id'     => defined( 'ELLADA_META_APP_ID' ) ? ELLADA_META_APP_ID : ( $o['id'] ?? '' ),
		'secret' => defined( 'ELLADA_META_APP_SECRET' ) ? ELLADA_META_APP_SECRET : ( $o['secret'] ?? '' ),
	);
}

function ellada_social_conn() {
	$c = get_option( 'ellada_social_conn', array() );
	return is_array( $c ) ? $c : array();
}

/** Η επιλεγμένη Σελίδα (με token) και το Instagram της. */
function ellada_social_target() {
	$c = ellada_social_conn();
	if ( empty( $c['pages'] ) ) {
		return null;
	}
	$sel = $c['page'] ?? '';
	foreach ( $c['pages'] as $p ) {
		if ( (string) $p['id'] === (string) $sel ) {
			return $p;
		}
	}
	return $c['pages'][0];
}

function ellada_social_redirect_uri() {
	return admin_url( 'admin-post.php?action=ellada_social_oauth' );
}

function ellada_social_api( $method, $path, $args = array() ) {
	$url = ELLADA_SOCIAL_GRAPH . ltrim( $path, '/' );
	if ( 'GET' === $method ) {
		$res = wp_remote_get( add_query_arg( array_map( 'rawurlencode', $args ), $url ), array( 'timeout' => 30 ) );
	} else {
		$res = wp_remote_post( $url, array( 'timeout' => 60, 'body' => $args ) );
	}
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'http', $res->get_error_message() );
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $body ) ) {
		return new WP_Error( 'json', 'Άκυρη απάντηση από το Meta (' . wp_remote_retrieve_response_code( $res ) . ').' );
	}
	if ( isset( $body['error'] ) ) {
		$m = $body['error']['error_user_msg'] ?? ( $body['error']['message'] ?? 'Σφάλμα' );
		return new WP_Error( 'meta', $m );
	}
	return $body;
}

/** Εντοπισμός Σελίδων + Instagram με ένα user token. */
function ellada_social_discover( $user_token ) {
	$r = ellada_social_api(
		'GET',
		'me/accounts',
		array(
			'fields'       => 'id,name,access_token,picture{url},instagram_business_account{id,username,profile_picture_url}',
			'limit'        => '50',
			'access_token' => $user_token,
		)
	);
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$pages = array();
	foreach ( (array) ( $r['data'] ?? array() ) as $p ) {
		$pages[] = array(
			'id'       => (string) $p['id'],
			'name'     => (string) $p['name'],
			'token'    => (string) $p['access_token'],
			'pic'      => (string) ( $p['picture']['data']['url'] ?? '' ),
			'ig'       => (string) ( $p['instagram_business_account']['id'] ?? '' ),
			'ig_user'  => (string) ( $p['instagram_business_account']['username'] ?? '' ),
			'ig_pic'   => (string) ( $p['instagram_business_account']['profile_picture_url'] ?? '' ),
		);
	}
	return $pages;
}

add_action(
	'admin_post_ellada_social_oauth',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Δεν επιτρέπεται.' );
		}
		$back = admin_url( 'admin.php?page=ellada-social&tab=syndesi' );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$err   = sanitize_text_field( wp_unslash( $_GET['error_description'] ?? '' ) );
		// phpcs:enable
		if ( ! $state || get_transient( 'ellada_social_state_' . get_current_user_id() ) !== $state ) {
			wp_safe_redirect( add_query_arg( 'esm', rawurlencode( 'Η σύνδεση έληξε. Δοκιμάστε ξανά.' ), $back ) );
			exit;
		}
		delete_transient( 'ellada_social_state_' . get_current_user_id() );
		if ( ! $code ) {
			wp_safe_redirect( add_query_arg( 'esm', rawurlencode( $err ? $err : 'Η σύνδεση ακυρώθηκε.' ), $back ) );
			exit;
		}
		$app = ellada_social_app();
		$t   = ellada_social_api(
			'GET',
			'oauth/access_token',
			array(
				'client_id'     => $app['id'],
				'client_secret' => $app['secret'],
				'redirect_uri'  => ellada_social_redirect_uri(),
				'code'          => $code,
			)
		);
		if ( ! is_wp_error( $t ) && ! empty( $t['access_token'] ) ) {
			// Μακράς διάρκειας token χρήστη → τα tokens των Σελίδων δεν λήγουν.
			$l = ellada_social_api(
				'GET',
				'oauth/access_token',
				array(
					'grant_type'        => 'fb_exchange_token',
					'client_id'         => $app['id'],
					'client_secret'     => $app['secret'],
					'fb_exchange_token' => $t['access_token'],
				)
			);
			$user  = ( ! is_wp_error( $l ) && ! empty( $l['access_token'] ) ) ? $l['access_token'] : $t['access_token'];
			$pages = ellada_social_discover( $user );
			if ( ! is_wp_error( $pages ) ) {
				$sel = '';
				foreach ( $pages as $p ) {
					if ( $p['ig'] ) {
						$sel = $p['id'];
						break;
					}
				}
				update_option(
					'ellada_social_conn',
					array(
						'pages'     => $pages,
						'page'      => $sel ? $sel : ( $pages[0]['id'] ?? '' ),
						'connected' => time(),
						'by'        => get_current_user_id(),
					),
					false
				);
				$msg = $pages ? 'Συνδέθηκε! Βρέθηκαν ' . count( $pages ) . ' Σελίδες.' : 'Συνδέθηκε, αλλά δεν βρέθηκε Σελίδα Facebook. Δώστε πρόσβαση στη Σελίδα σας.';
				wp_safe_redirect( add_query_arg( 'esm', rawurlencode( $msg ), $back ) );
				exit;
			}
			$t = $pages;
		}
		wp_safe_redirect( add_query_arg( 'esm', rawurlencode( 'Σφάλμα: ' . ( is_wp_error( $t ) ? $t->get_error_message() : 'χωρίς token' ) ), $back ) );
		exit;
	}
);

/* ═════════════════════ Αποστολή ═════════════════════ */

function ellada_social_send( $id, $net ) {
	$p   = ellada_social_get( $id );
	$it  = $p[ $net ] ?? array();
	$tg  = ellada_social_target();
	$res = null;
	if ( ! $tg ) {
		$res = new WP_Error( 'conn', 'Δεν υπάρχει σύνδεση με Facebook/Instagram.' );
	} elseif ( 'fb' === $net ) {
		$r = ellada_social_api(
			'POST',
			$tg['id'] . '/feed',
			array(
				'message'      => (string) ( $it['text'] ?? '' ),
				'link'         => get_permalink( $id ),
				'access_token' => $tg['token'],
			)
		);
		$res = is_wp_error( $r ) ? $r : array(
			'id'   => $r['id'] ?? '',
			'link' => 'https://www.facebook.com/' . ( $r['id'] ?? '' ),
		);
	} elseif ( empty( $tg['ig'] ) ) {
		$res = new WP_Error( 'ig', 'Η Σελίδα δεν έχει συνδεδεμένο επαγγελματικό λογαριασμό Instagram.' );
	} else {
		$args = array(
			'image_url'    => (string) ( $it['img'] ?? '' ),
			'access_token' => $tg['token'],
		);
		if ( 'story' === $net ) {
			$args['media_type'] = 'STORIES';
		} else {
			$args['caption'] = (string) ( $it['text'] ?? '' );
		}
		$m = ellada_social_api( 'POST', $tg['ig'] . '/media', $args );
		if ( is_wp_error( $m ) ) {
			$res = $m;
		} else {
			$pub = null;
			for ( $i = 0; $i < 6; $i++ ) {
				$pub = ellada_social_api(
					'POST',
					$tg['ig'] . '/media_publish',
					array(
						'creation_id'  => $m['id'],
						'access_token' => $tg['token'],
					)
				);
				if ( ! is_wp_error( $pub ) ) {
					break;
				}
				sleep( 3 ); // το Instagram χρειάζεται λίγα δευτερόλεπτα να «ετοιμάσει» την εικόνα
			}
			if ( is_wp_error( $pub ) ) {
				$res = $pub;
			} else {
				$link = '';
				$pm   = ellada_social_api(
					'GET',
					$pub['id'],
					array(
						'fields'       => 'permalink',
						'access_token' => $tg['token'],
					)
				);
				if ( ! is_wp_error( $pm ) ) {
					$link = (string) ( $pm['permalink'] ?? '' );
				}
				$res = array(
					'id'   => $pub['id'],
					'link' => $link,
				);
			}
		}
	}
	if ( is_wp_error( $res ) ) {
		$it['status'] = 'error';
		$it['err']    = $res->get_error_message();
	} else {
		$it['status'] = 'sent';
		$it['sent']   = time();
		$it['link']   = $res['link'];
		$it['err']    = '';
	}
	unset( $it['at'] );
	$p[ $net ] = $it;
	update_post_meta( $id, '_ellada_social', $p );
	return $res;
}

add_action( 'ellada_social_cron', 'ellada_social_send', 10, 2 );

/* ═════════════════════ Διαχειριστικό ═════════════════════ */

add_action(
	'admin_menu',
	function () {
		$new   = count( (array) get_option( 'ellada_social_new', array() ) );
		$badge = $new ? ' <span class="awaiting-mod">' . (int) $new . '</span>' : '';
		add_menu_page( 'Social', 'Social' . $badge, 'edit_others_posts', 'ellada-social', 'ellada_social_page', 'dashicons-share', 26 );
	}
);

// Σύνδεσμος «Social» στη λίστα συνταγών.
add_filter(
	'post_row_actions',
	function ( $a, $post ) {
		if ( 'ellada_recipe' === $post->post_type && 'publish' === $post->post_status && current_user_can( 'edit_others_posts' ) ) {
			$a['ellada_social'] = '<a href="' . esc_url( admin_url( 'admin.php?page=ellada-social&recipe=' . $post->ID ) ) . '">Social</a>';
		}
		return $a;
	},
	10,
	2
);

add_action(
	'admin_post_ellada_social',
	function () {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( 'Δεν επιτρέπεται.' );
		}
		check_admin_referer( 'ellada_social' );
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$do  = sanitize_key( $_POST['do'] ?? '' );
		$id  = (int) ( $_POST['recipe'] ?? 0 );
		$net = sanitize_key( $_POST['net'] ?? '' );
		$msg = '';
		$url = admin_url( 'admin.php?page=ellada-social' . ( $id ? '&recipe=' . $id : '' ) );

		if ( in_array( $do, array( 'save', 'send', 'schedule', 'unschedule', 'reset', 'images', 'markdone' ), true ) && $id && 'ellada_recipe' === get_post_type( $id ) ) {
			$p = ellada_social_get( $id );
			if ( isset( $_POST['text'] ) && is_array( $_POST['text'] ) ) {
				foreach ( ELLADA_SOCIAL_NETS as $n ) {
					if ( isset( $_POST['text'][ $n ] ) ) {
						$p[ $n ]['text'] = sanitize_textarea_field( wp_unslash( $_POST['text'][ $n ] ) );
					}
				}
				update_post_meta( $id, '_ellada_social', $p );
			}
			$q = array_diff( (array) get_option( 'ellada_social_new', array() ), array( $id ) );
			update_option( 'ellada_social_new', array_values( $q ), false );

			if ( 'reset' === $do ) {
				ellada_social_build( $id, true, false );
				$msg = 'Τα κείμενα ξαναγράφτηκαν από τη συνταγή.';
			} elseif ( 'images' === $do ) {
				ellada_social_build( $id, false, true );
				$msg = 'Οι εικόνες ξαναφτιάχτηκαν.';
			} elseif ( 'send' === $do && in_array( $net, ELLADA_SOCIAL_NETS, true ) ) {
				$r   = ellada_social_send( $id, $net );
				$msg = is_wp_error( $r ) ? 'Σφάλμα (' . ellada_social_net_label( $net ) . '): ' . $r->get_error_message() : 'Στάλθηκε: ' . ellada_social_net_label( $net ) . ' ✓';
			} elseif ( 'schedule' === $do && in_array( $net, ELLADA_SOCIAL_NETS, true ) ) {
				$when = sanitize_text_field( wp_unslash( $_POST['when'][ $net ] ?? '' ) );
				$ts   = $when ? ( new DateTime( $when, wp_timezone() ) )->getTimestamp() : 0;
				if ( $ts > time() + 60 ) {
					wp_clear_scheduled_hook( 'ellada_social_cron', array( $id, $net ) );
					wp_schedule_single_event( $ts, 'ellada_social_cron', array( $id, $net ) );
					$p                   = ellada_social_get( $id );
					$p[ $net ]['status'] = 'scheduled';
					$p[ $net ]['at']     = $ts;
					update_post_meta( $id, '_ellada_social', $p );
					$msg = 'Προγραμματίστηκε: ' . ellada_social_net_label( $net ) . ' — ' . wp_date( 'D j/n, H:i', $ts );
				} else {
					$msg = 'Διαλέξτε ώρα στο μέλλον.';
				}
			} elseif ( 'unschedule' === $do && in_array( $net, ELLADA_SOCIAL_NETS, true ) ) {
				wp_clear_scheduled_hook( 'ellada_social_cron', array( $id, $net ) );
				$p                   = ellada_social_get( $id );
				$p[ $net ]['status'] = 'ready';
				unset( $p[ $net ]['at'] );
				update_post_meta( $id, '_ellada_social', $p );
				$msg = 'Ο προγραμματισμός ακυρώθηκε.';
			} elseif ( 'markdone' === $do && in_array( $net, ELLADA_SOCIAL_NETS, true ) ) {
				$p                   = ellada_social_get( $id );
				$p[ $net ]['status'] = 'sent';
				$p[ $net ]['sent']   = time();
				$p[ $net ]['manual'] = 1;
				update_post_meta( $id, '_ellada_social', $p );
				$msg = 'Σημειώθηκε ως ανεβασμένο: ' . ellada_social_net_label( $net );
			} else {
				$msg = 'Αποθηκεύτηκε.';
			}
		} elseif ( 'app' === $do && current_user_can( 'manage_options' ) ) {
			$old = (array) get_option( 'ellada_social_app', array() );
			$sec = trim( sanitize_text_field( wp_unslash( $_POST['app_secret'] ?? '' ) ) );
			update_option(
				'ellada_social_app',
				array(
					'id'     => preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['app_id'] ?? '' ) ) ),
					'secret' => '' !== $sec ? $sec : ( $old['secret'] ?? '' ),
				),
				false
			);
			$url = admin_url( 'admin.php?page=ellada-social&tab=syndesi' );
			$msg = 'Τα στοιχεία της εφαρμογής αποθηκεύτηκαν.';
		} elseif ( 'connect' === $do && current_user_can( 'manage_options' ) ) {
			$app   = ellada_social_app();
			$state = wp_generate_password( 24, false );
			set_transient( 'ellada_social_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS );
			$go = add_query_arg(
				array(
					'client_id'     => rawurlencode( $app['id'] ),
					'redirect_uri'  => rawurlencode( ellada_social_redirect_uri() ),
					'state'         => rawurlencode( $state ),
					'response_type' => 'code',
					'scope'         => rawurlencode( 'pages_show_list,pages_read_engagement,pages_manage_posts,instagram_basic,instagram_content_publish,business_management' ),
				),
				'https://www.facebook.com/v21.0/dialog/oauth'
			);
			wp_redirect( $go ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- facebook.com
			exit;
		} elseif ( 'page' === $do && current_user_can( 'manage_options' ) ) {
			$c         = ellada_social_conn();
			$c['page'] = sanitize_text_field( wp_unslash( $_POST['page_id'] ?? '' ) );
			update_option( 'ellada_social_conn', $c, false );
			$url = admin_url( 'admin.php?page=ellada-social&tab=syndesi' );
			$msg = 'Η Σελίδα άλλαξε.';
		} elseif ( 'test' === $do && current_user_can( 'manage_options' ) ) {
			$tg  = ellada_social_target();
			$r   = $tg ? ellada_social_api( 'GET', $tg['id'], array( 'fields' => 'name', 'access_token' => $tg['token'] ) ) : new WP_Error( 'x', 'Δεν υπάρχει σύνδεση.' );
			$url = admin_url( 'admin.php?page=ellada-social&tab=syndesi' );
			$msg = is_wp_error( $r ) ? 'Σφάλμα: ' . $r->get_error_message() : 'Η σύνδεση δουλεύει ✓ (' . $r['name'] . ')';
		} elseif ( 'disconnect' === $do && current_user_can( 'manage_options' ) ) {
			delete_option( 'ellada_social_conn' );
			$url = admin_url( 'admin.php?page=ellada-social&tab=syndesi' );
			$msg = 'Αποσυνδέθηκε.';
		}
		// phpcs:enable
		wp_safe_redirect( add_query_arg( 'esm', rawurlencode( $msg ), $url ) );
		exit;
	}
);

function ellada_social_chip( $it ) {
	$s = $it['status'] ?? 'ready';
	$m = array(
		'ready'     => array( 'Έτοιμο', 'is-ready' ),
		'scheduled' => array( 'Προγραμματισμένο', 'is-sch' ),
		'sent'      => array( 'Ανέβηκε', 'is-sent' ),
		'error'     => array( 'Σφάλμα', 'is-err' ),
	);
	$x = $m[ $s ] ?? $m['ready'];
	$t = $x[0];
	if ( 'scheduled' === $s && ! empty( $it['at'] ) ) {
		$t .= ' · ' . wp_date( 'j/n H:i', (int) $it['at'] );
	}
	if ( 'sent' === $s && ! empty( $it['sent'] ) ) {
		$t .= ' · ' . wp_date( 'j/n', (int) $it['sent'] );
	}
	return '<span class="esoc-chip ' . esc_attr( $x[1] ) . '">' . esc_html( $t ) . '</span>';
}

function ellada_social_page() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$tab = sanitize_key( $_GET['tab'] ?? '' );
	$rid = (int) ( $_GET['recipe'] ?? 0 );
	$esm = sanitize_text_field( wp_unslash( $_GET['esm'] ?? '' ) );
	// phpcs:enable
	$tg   = ellada_social_target();
	$base = admin_url( 'admin.php?page=ellada-social' );
	?>
	<div class="wrap esoc">
		<style>
			.esoc{--p:#174C4B;--g:#D29A48;--c:#F6F2E9;--l:#E7E2D5;--i:#343B39;--m:#58615E;max-width:1280px}
			.esoc h1{font-weight:800;color:var(--p)}
			.esoc .esoc-tabs{display:flex;gap:6px;margin:14px 0 18px}
			.esoc .esoc-tabs a{padding:8px 14px;border-radius:999px;text-decoration:none;background:#fff;border:1px solid var(--l);color:var(--i);font-weight:600}
			.esoc .esoc-tabs a.is-on{background:var(--p);color:var(--c);border-color:var(--p)}
			.esoc .esoc-msg{padding:10px 14px;border-left:4px solid var(--g);background:#fff;margin:10px 0 16px;font-weight:600}
			.esoc .esoc-conn{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#fff;border:1px solid var(--l);border-radius:12px;margin-bottom:16px}
			.esoc .esoc-conn img{width:28px;height:28px;border-radius:50%}
			.esoc table.esoc-list{width:100%;border-collapse:separate;border-spacing:0 8px}
			.esoc table.esoc-list td{background:#fff;padding:10px 12px;vertical-align:middle}
			.esoc table.esoc-list td:first-child{border-radius:12px 0 0 12px;width:64px}
			.esoc table.esoc-list td:last-child{border-radius:0 12px 12px 0;text-align:right}
			.esoc .esoc-th{width:56px;height:56px;border-radius:10px;object-fit:cover;background:var(--p);display:block}
			.esoc .esoc-chip{display:inline-block;padding:3px 9px;border-radius:999px;font-size:12px;font-weight:600;background:#EEF2EF;color:var(--p);margin:2px 4px 2px 0}
			.esoc .esoc-chip.is-sch{background:#FBF1E1;color:#8A5A14}.esoc .esoc-chip.is-sent{background:#E3F1E7;color:#2F7A4A}.esoc .esoc-chip.is-err{background:#FBE7E2;color:#A8461A}
			.esoc .esoc-new{background:#C2602F;color:#fff;border-radius:999px;padding:2px 8px;font-size:11px;margin-left:6px}
			.esoc .esoc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;align-items:start}
			@media(max-width:1200px){.esoc .esoc-grid{grid-template-columns:1fr 1fr}}
			@media(max-width:782px){.esoc .esoc-grid{grid-template-columns:1fr}}
			.esoc .esoc-card{background:#fff;border:1px solid var(--l);border-radius:18px;padding:14px;display:flex;flex-direction:column;gap:10px}
			.esoc .esoc-card h2{margin:0;font-size:15px;display:flex;justify-content:space-between;align-items:center;gap:8px}
			.esoc .esoc-mock{border:1px solid var(--l);border-radius:14px;overflow:hidden;background:#fff}
			.esoc .esoc-mock__bar{display:flex;align-items:center;gap:8px;padding:8px 10px;font-weight:700;font-size:12.5px}
			.esoc .esoc-mock__bar i{width:26px;height:26px;border-radius:50%;background:var(--p) center/cover;display:inline-block}
			.esoc .esoc-mock img{display:block;width:100%;height:auto}
			.esoc .esoc-mock.is-story{max-width:260px;margin:0 auto;border-radius:18px}
			.esoc .esoc-mock__cap{padding:8px 10px 12px;font-size:12.5px;white-space:pre-wrap;color:var(--i);max-height:120px;overflow:hidden}
			.esoc textarea{width:100%;min-height:200px;border-radius:12px;border:1px solid var(--l);padding:10px;font-size:13px;line-height:1.5}
			.esoc .esoc-count{font-size:12px;color:var(--m);text-align:right;margin-top:-6px}
			.esoc .esoc-row{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
			.esoc .button.esoc-go{background:var(--p);border-color:var(--p);color:#fff}
			.esoc .button.esoc-go:hover{background:#123C3B;border-color:#123C3B;color:#fff}
			.esoc .esoc-note{font-size:12px;color:var(--m);margin:0}
			.esoc .esoc-err{font-size:12px;color:#A8461A;margin:0}
			.esoc .esoc-help{background:#fff;border:1px solid var(--l);border-radius:18px;padding:18px 22px;max-width:860px}
			.esoc .esoc-help ol li{margin-bottom:8px}
			.esoc code.esoc-copy{cursor:pointer;background:var(--c);padding:3px 8px;border-radius:6px}
			.esoc .esoc-pages label{display:flex;align-items:center;gap:10px;padding:8px 0}
			.esoc .esoc-pages img{width:28px;height:28px;border-radius:50%}
		</style>
		<h1>Social <span style="font-weight:400;color:#58615E;font-size:14px">· έτοιμες αναρτήσεις για Instagram & Facebook</span></h1>
		<nav class="esoc-tabs">
			<a href="<?php echo esc_url( $base ); ?>" class="<?php echo 'syndesi' !== $tab ? 'is-on' : ''; ?>">Αναρτήσεις</a>
			<a href="<?php echo esc_url( $base . '&tab=syndesi' ); ?>" class="<?php echo 'syndesi' === $tab ? 'is-on' : ''; ?>">Σύνδεση λογαριασμών</a>
		</nav>
		<?php if ( $esm ) : ?>
			<div class="esoc-msg" role="status"><?php echo esc_html( $esm ); ?></div>
		<?php endif; ?>
		<div class="esoc-conn">
			<?php if ( $tg ) : ?>
				<?php if ( $tg['pic'] ) : ?><img src="<?php echo esc_url( $tg['pic'] ); ?>" alt=""><?php endif; ?>
				<span>Συνδεδεμένο: <b><?php echo esc_html( $tg['name'] ); ?></b> (Facebook)<?php echo $tg['ig_user'] ? ' · <b>@' . esc_html( $tg['ig_user'] ) . '</b> (Instagram)' : ' · <span style="color:#A8461A">χωρίς Instagram</span>'; ?></span>
			<?php else : ?>
				<span>Δεν έχουν συνδεθεί λογαριασμοί ακόμα — μπορείτε να <b>αντιγράψετε και να ανεβάσετε</b> με το χέρι, ή να τους <a href="<?php echo esc_url( $base . '&tab=syndesi' ); ?>">συνδέσετε</a> για αποστολή με ένα κλικ.</span>
			<?php endif; ?>
		</div>
		<?php
		if ( 'syndesi' === $tab ) {
			ellada_social_page_conn();
		} elseif ( $rid ) {
			ellada_social_page_recipe( $rid, $tg );
		} else {
			ellada_social_page_list();
		}
		?>
	</div>
	<script>
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-esoc-copy]');
		if (b) {
			var src = b.getAttribute('data-esoc-copy'), el = src ? document.getElementById(src) : b, t = el.value !== undefined ? el.value : el.textContent;
			navigator.clipboard.writeText(t).then(function () { var o = b.textContent; b.textContent = '✓ Αντιγράφηκε'; setTimeout(function () { b.textContent = o; }, 1800); });
		}
		var s = e.target.closest('[data-esoc-confirm]');
		if (s && !window.confirm(s.getAttribute('data-esoc-confirm'))) e.preventDefault();
	});
	document.querySelectorAll('.esoc textarea[data-max]').forEach(function (t) {
		var c = t.parentNode.querySelector('.esoc-count'), cap = document.getElementById(t.id + '-cap');
		function up() { var n = t.value.length, h = (t.value.match(/#[^\s#]+/g) || []).length; c.textContent = n + ' / ' + t.getAttribute('data-max') + ' χαρακτήρες' + (t.id.indexOf('ig') > -1 ? ' · ' + h + ' / 30 hashtags' : ''); c.style.color = n > +t.getAttribute('data-max') || h > 30 ? '#A8461A' : ''; if (cap) cap.textContent = t.value; }
		t.addEventListener('input', up); up();
	});
	</script>
	<?php
}

function ellada_social_page_list() {
	$new = (array) get_option( 'ellada_social_new', array() );
	$ids = get_posts(
		array(
			'post_type'   => 'ellada_recipe',
			'post_status' => 'publish',
			'numberposts' => 100,
			'fields'      => 'ids',
		)
	);
	echo '<table class="esoc-list"><tbody>';
	foreach ( $ids as $id ) {
		$p     = ellada_social_get( $id );
		$thumb = get_the_post_thumbnail_url( $id, 'thumbnail' );
		if ( ! $thumb && ! empty( $p['ig']['img'] ) ) {
			$thumb = $p['ig']['img'];
		}
		echo '<tr><td>' . ( $thumb ? '<img class="esoc-th" src="' . esc_url( $thumb ) . '" alt="">' : '<span class="esoc-th"></span>' ) . '</td><td><b>' . esc_html( get_the_title( $id ) ) . '</b>';
		if ( in_array( $id, $new, true ) ) {
			echo '<span class="esoc-new">νέα</span>';
		}
		echo '<br>';
		if ( $p ) {
			foreach ( ELLADA_SOCIAL_NETS as $n ) {
				echo '<span class="esoc-note" style="display:inline">' . esc_html( ellada_social_net_label( $n ) ) . ':</span> ' . ellada_social_chip( $p[ $n ] ?? array() ) . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} else {
			echo '<span class="esoc-note">Δεν έχει ετοιμαστεί ακόμα.</span>';
		}
		echo '</td><td><a class="button esoc-go" href="' . esc_url( admin_url( 'admin.php?page=ellada-social&recipe=' . $id ) ) . '">' . ( $p ? 'Άνοιγμα' : 'Ετοίμασε αναρτήσεις' ) . '</a></td></tr>';
	}
	echo '</tbody></table>';
	if ( ! $ids ) {
		echo '<p>Δεν υπάρχουν δημοσιευμένες συνταγές.</p>';
	}
}

function ellada_social_page_recipe( $id, $tg ) {
	if ( 'ellada_recipe' !== get_post_type( $id ) ) {
		echo '<p>Δεν βρέθηκε η συνταγή.</p>';
		return;
	}
	$p = ellada_social_get( $id );
	if ( ! $p || empty( $p['ig']['img'] ) ) {
		$p = ellada_social_build( $id );
	}
	$name = $tg && $tg['ig_user'] ? $tg['ig_user'] : 'elladastopiato';
	$pic  = $tg ? ( $tg['ig_pic'] ? $tg['ig_pic'] : $tg['pic'] ) : '';
	$max  = array(
		'ig'    => 2200,
		'story' => 500,
		'fb'    => 5000,
	);
	$help = array(
		'ig'    => 'Στο Instagram οι σύνδεσμοι στη λεζάντα δεν πατιούνται — γι’ αυτό «link στο bio».',
		'story' => 'Το κείμενο εδώ είναι για εσάς: ο σύνδεσμος για το αυτοκόλλητο «Link» (προσθέτεται από την εφαρμογή του Instagram). Με αυτόματη αποστολή το story ανεβαίνει χωρίς αυτοκόλλητο.',
		'fb'    => 'Ο σύνδεσμος στο τέλος βγάζει αυτόματα την κάρτα με φωτογραφία.',
	);
	?>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=ellada-social' ) ); ?>">← Όλες οι συνταγές</a> · <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener">Η συνταγή στο site ↗</a></p>
	<h2 style="font-size:24px;margin:6px 0 16px;color:#174C4B"><?php echo esc_html( get_the_title( $id ) ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ellada_social' ); ?>
		<input type="hidden" name="action" value="ellada_social">
		<input type="hidden" name="recipe" value="<?php echo (int) $id; ?>">
		<div class="esoc-grid">
		<?php
		foreach ( ELLADA_SOCIAL_NETS as $n ) :
			$it = $p[ $n ] ?? array();
			?>
			<section class="esoc-card">
				<h2><?php echo esc_html( ellada_social_net_label( $n ) ); ?> <?php echo ellada_social_chip( $it ); // phpcs:ignore ?></h2>
				<div class="esoc-mock<?php echo 'story' === $n ? ' is-story' : ''; ?>">
					<?php if ( 'story' !== $n ) : ?>
						<div class="esoc-mock__bar"><i style="<?php echo $pic ? 'background-image:url(' . esc_url( $pic ) . ')' : ''; ?>"></i><?php echo esc_html( 'fb' === $n ? ( $tg ? $tg['name'] : 'Ελλάδα στο Πιάτο' ) : $name ); ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $it['img'] ) ) : ?>
						<img src="<?php echo esc_url( $it['img'] ); ?>" alt="" loading="lazy">
					<?php endif; ?>
					<?php if ( 'story' !== $n ) : ?>
						<div class="esoc-mock__cap" id="esoc-t-<?php echo esc_attr( $n ); ?>-cap"><?php echo esc_html( $it['text'] ?? '' ); ?></div>
					<?php endif; ?>
				</div>
				<label class="screen-reader-text" for="esoc-t-<?php echo esc_attr( $n ); ?>">Κείμενο <?php echo esc_html( ellada_social_net_label( $n ) ); ?></label>
				<textarea id="esoc-t-<?php echo esc_attr( $n ); ?>" name="text[<?php echo esc_attr( $n ); ?>]" data-max="<?php echo (int) $max[ $n ]; ?>" <?php echo 'story' === $n ? 'style="min-height:80px"' : ''; ?>><?php echo esc_textarea( $it['text'] ?? '' ); ?></textarea>
				<div class="esoc-count"></div>
				<p class="esoc-note"><?php echo esc_html( $help[ $n ] ); ?></p>
				<?php if ( ! empty( $it['err'] ) && 'error' === ( $it['status'] ?? '' ) ) : ?>
					<p class="esoc-err">Σφάλμα: <?php echo esc_html( $it['err'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $it['link'] ) ) : ?>
					<p class="esoc-note"><a href="<?php echo esc_url( $it['link'] ); ?>" target="_blank" rel="noopener">Δείτε την ανάρτηση ↗</a></p>
				<?php endif; ?>
				<div class="esoc-row">
					<button type="button" class="button" data-esoc-copy="esoc-t-<?php echo esc_attr( $n ); ?>">Αντιγραφή κειμένου</button>
					<?php if ( ! empty( $it['img'] ) ) : ?>
						<a class="button" href="<?php echo esc_url( $it['img'] ); ?>" download>Λήψη εικόνας</a>
					<?php endif; ?>
				</div>
				<div class="esoc-row">
					<?php if ( $tg && ( 'fb' === $n || $tg['ig'] ) ) : ?>
						<button class="button esoc-go" name="do" value="send" onclick="this.form.net.value='<?php echo esc_attr( $n ); ?>'" data-esoc-confirm="Να ανέβει τώρα στο <?php echo esc_attr( ellada_social_net_label( $n ) ); ?>;">Αποστολή τώρα</button>
						<input type="datetime-local" name="when[<?php echo esc_attr( $n ); ?>]" aria-label="Ώρα προγραμματισμού" min="<?php echo esc_attr( wp_date( 'Y-m-d\TH:i' ) ); ?>">
						<button class="button" name="do" value="schedule" onclick="this.form.net.value='<?php echo esc_attr( $n ); ?>'">Προγραμματισμός</button>
						<?php if ( 'scheduled' === ( $it['status'] ?? '' ) ) : ?>
							<button class="button-link" name="do" value="unschedule" onclick="this.form.net.value='<?php echo esc_attr( $n ); ?>'">Ακύρωση</button>
						<?php endif; ?>
					<?php else : ?>
						<button class="button" name="do" value="markdone" onclick="this.form.net.value='<?php echo esc_attr( $n ); ?>'">✓ Το ανέβασα</button>
					<?php endif; ?>
				</div>
			</section>
		<?php endforeach; ?>
		</div>
		<input type="hidden" name="net" value="">
		<p class="esoc-row" style="margin-top:18px">
			<button class="button button-primary esoc-go" name="do" value="save">Αποθήκευση κειμένων</button>
			<button class="button" name="do" value="reset" data-esoc-confirm="Να ξαναγραφτούν τα κείμενα από τη συνταγή; Οι αλλαγές σας θα χαθούν.">Ξαναγράψε τα κείμενα</button>
			<button class="button" name="do" value="images">Ξαναφτιάξε τις εικόνες</button>
		</p>
	</form>
	<?php
}

function ellada_social_page_conn() {
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<p>Μόνο ο διαχειριστής μπορεί να συνδέσει λογαριασμούς.</p>';
		return;
	}
	$app  = ellada_social_app();
	$conn = ellada_social_conn();
	$cst  = defined( 'ELLADA_META_APP_ID' );
	?>
	<div class="esoc-help">
		<?php if ( ! empty( $conn['pages'] ) ) : ?>
			<h2>Βρέθηκαν αυτόματα</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="esoc-pages">
				<?php wp_nonce_field( 'ellada_social' ); ?>
				<input type="hidden" name="action" value="ellada_social">
				<?php foreach ( $conn['pages'] as $pg ) : ?>
					<label><input type="radio" name="page_id" value="<?php echo esc_attr( $pg['id'] ); ?>" <?php checked( (string) ( $conn['page'] ?? '' ), (string) $pg['id'] ); ?>>
						<?php if ( $pg['pic'] ) : ?><img src="<?php echo esc_url( $pg['pic'] ); ?>" alt=""><?php endif; ?>
						<b><?php echo esc_html( $pg['name'] ); ?></b>
						<?php echo $pg['ig_user'] ? '· Instagram <b>@' . esc_html( $pg['ig_user'] ) . '</b> ✓' : '· <span style="color:#A8461A">χωρίς επαγγελματικό Instagram</span>'; ?>
					</label>
				<?php endforeach; ?>
				<p class="esoc-row">
					<button class="button esoc-go" name="do" value="page">Χρήση αυτής της Σελίδας</button>
					<button class="button" name="do" value="test">Έλεγχος σύνδεσης</button>
					<button class="button" name="do" value="connect">Ξανά σύνδεση (αν προσθέσατε λογαριασμό)</button>
					<button class="button-link" name="do" value="disconnect" data-esoc-confirm="Αποσύνδεση από Facebook/Instagram;">Αποσύνδεση</button>
				</p>
				<p class="esoc-note">Συνδέθηκε στις <?php echo esc_html( wp_date( 'j/n/Y H:i', (int) $conn['connected'] ) ); ?>. Τα κλειδιά πρόσβασης φυλάσσονται στη βάση και δεν εμφανίζονται πουθενά.</p>
			</form>
			<hr>
		<?php endif; ?>

		<h2>Σύνδεση με Facebook & Instagram</h2>
		<ol>
			<li><b>Facebook:</b> να υπάρχει <b>Σελίδα</b> (όχι προσωπικό προφίλ) «Ελλάδα στο Πιάτο».</li>
			<li><b>Instagram:</b> <b>επαγγελματικός</b> λογαριασμός (Creator ή Business), συνδεδεμένος με τη Σελίδα (Instagram → Ρυθμίσεις → Κέντρο λογαριασμών).</li>
			<li>Στο <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">developers.facebook.com/apps</a> → <b>Δημιουργία εφαρμογής</b> → «Άλλο» → τύπος <b>Business</b>. Προσθέστε το προϊόν <b>Facebook Login for Business</b>.</li>
			<li>Στο Facebook Login → Ρυθμίσεις → <b>Valid OAuth Redirect URIs</b>, βάλτε: <code class="esoc-copy" data-esoc-copy=""><?php echo esc_html( ellada_social_redirect_uri() ); ?></code></li>
			<li>Από Ρυθμίσεις εφαρμογής → Βασικά, αντιγράψτε το <b>App ID</b> και το <b>App Secret</b> στη φόρμα παρακάτω και πατήστε «Σύνδεση». Η εφαρμογή μπορεί να μείνει σε κατάσταση «Development»: αρκεί για τη δική σας Σελίδα.</li>
		</ol>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ellada_social' ); ?>
			<input type="hidden" name="action" value="ellada_social">
			<?php if ( $cst ) : ?>
				<p class="esoc-note">Τα στοιχεία της εφαρμογής ορίζονται στο wp-config.php.</p>
			<?php else : ?>
				<p><label>App ID<br><input type="text" name="app_id" value="<?php echo esc_attr( $app['id'] ); ?>" class="regular-text" inputmode="numeric" autocomplete="off"></label></p>
				<p><label>App Secret<br><input type="password" name="app_secret" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $app['secret'] ? esc_attr( '•••••••• αποθηκευμένο — αφήστε κενό για να μείνει' ) : ''; ?>"></label></p>
				<p><button class="button" name="do" value="app">Αποθήκευση στοιχείων</button></p>
			<?php endif; ?>
			<?php if ( $app['id'] && $app['secret'] ) : ?>
				<p><button class="button button-primary esoc-go" name="do" value="connect" style="font-size:15px;padding:4px 18px">Σύνδεση με Facebook →</button></p>
				<p class="esoc-note">Θα ανοίξει το Facebook: επιλέξτε τη Σελίδα και το Instagram σας και πατήστε «Συνέχεια». Μετά επιστρέφετε εδώ και οι λογαριασμοί βρίσκονται αυτόματα.</p>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
