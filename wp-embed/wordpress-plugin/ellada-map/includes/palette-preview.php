<?php
/**
 * Προεπισκόπηση χρωματικής παλέτας σε όλο το site — ΜΟΝΟ για συνδεδεμένους διαχειριστές/συντάκτες.
 *
 *   ?ellada_palette=petrol   ενεργοποιεί την παλέτα (μένει με cookie για όλες τις σελίδες, 1 μέρα)
 *   ?ellada_palette=off      επιστροφή στα κανονικά χρώματα
 *
 * Πώς: (1) οι γενικές μεταβλητές χρωμάτων του Elementor και οι δικές μας (--ellada-*)
 *      (2) για κάθε κανόνα των αρχείων CSS του Elementor και του front.css που έχει παλιό χρώμα, ο ίδιος
 *          κανόνας με το νέο χρώμα (στο τέλος της σελίδας, ίδια ειδικότητα), μέσα στο ίδιο @media
 *      (3) αντικατάσταση χρωμάτων μέσα στο HTML (inline style, <style>, SVG)
 * Οι επισκέπτες δεν βλέπουν τίποτα· η σελίδα τους δεν περνά από αυτόν τον κώδικα.
 */

defined( 'ABSPATH' ) || exit;

/** Οι παλέτες: παλιό χρώμα → νέο (κεφαλαία hex, και rgb τριάδες για rgba). */
function ellada_palettes() {
	return array(
		'petrol' => array(
			'name' => 'Πετρόλ & χρυσό',
			'vars' => array(
				'--e-global-color-primary'   => '#C5A46D',
				'--e-global-color-secondary' => '#174C4B',
				'--e-global-color-text'      => '#343B39',
				'--e-global-color-accent'    => '#58615E',
				'--ellada-accent'            => '#C5A46D',
				'--ellada-dark'              => '#174C4B',
				'--ellada-ink'               => '#343B39',
				'--ellada-muted'             => '#58615E',
				'--ellada-accent-deep'       => '#174C4B',
				'--ellada-line'              => '#D7DFD8',
				'--ellada-surface'           => '#FFFDF8',
			),
			'hex'  => array(
				'#F97316' => '#C5A46D', // πορτοκαλί → χρυσό άμμου (κουμπιά, λογότυπο, σε σκούρο φόντο)
				'#FB8A3C' => '#D3B886',
				'#FB923C' => '#D3B886',
				'#FE882E' => '#D3B886',
				'#FDBA8C' => '#E2D2B2',
				'#C2410C' => '#174C4B', // βαθύ πορτοκαλί (κείμενο σε ανοιχτό) → πετρόλ
				'#9A3F0B' => '#123C3B',
				'#0A0A0A' => '#174C4B', // μαύρο (header/footer/σκούρα μπλοκ) → πετρόλ
				'#151311' => '#343B39', // κείμενο → ανθρακί
				'#1C1A18' => '#343B39',
				'#2A2724' => '#235B59', // γραμμές πάνω στο σκούρο
				'#3A3632' => '#2C6866',
				'#F5F4F2' => '#F5F1E8', // φόντο → κρεμ
				'#FAF8F5' => '#F9F6EF',
				'#FAF9F7' => '#F9F6EF',
				'#FBFAF8' => '#FAF8F2',
				'#E4E0DA' => '#D7DFD8', // γραμμές → φασκόμηλο
				'#E4E1DD' => '#D7DFD8',
				'#6A645E' => '#58615E',
				'#6B655E' => '#58615E',
				'#A8A099' => '#A9BDB8',
				'#BDB6AE' => '#C3D0CB',
				'#8F8983' => '#8FA39E',
				'#C9C4BE' => '#C9D3CE',
			),
			'rgb'  => array(
				'249,115,22' => '197,164,109',
				'245,244,242' => '245,241,232',
				'10,10,10'   => '23,76,75',
				'21,19,17'   => '52,59,57',
			),
		),
	);
}

function ellada_palette_active() {
	static $p = null;
	if ( null !== $p ) {
		return $p;
	}
	$p = '';
	if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) || is_admin() ) {
		return $p;
	}
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$req = isset( $_GET['ellada_palette'] ) ? sanitize_key( $_GET['ellada_palette'] ) : '';
	$all = ellada_palettes();
	if ( 'off' === $req ) {
		setcookie( 'ellada_palette', '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		return $p;
	}
	if ( $req && isset( $all[ $req ] ) ) {
		setcookie( 'ellada_palette', $req, time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		$p = $req;
	} elseif ( isset( $_COOKIE['ellada_palette'] ) && isset( $all[ sanitize_key( $_COOKIE['ellada_palette'] ) ] ) ) {
		$p = sanitize_key( $_COOKIE['ellada_palette'] );
	}
	// phpcs:enable
	return $p;
}

/** Αντικαθιστά τα χρώματα σε ένα κομμάτι CSS/HTML. Επιστρέφει [κείμενο, άλλαξε;]. */
function ellada_palette_swap( $text, $pal ) {
	$hex     = $pal['hex'];
	$changed = false;
	$out     = preg_replace_callback(
		'/#([0-9a-fA-F]{6})\b/',
		function ( $m ) use ( $hex, &$changed ) {
			$k = '#' . strtoupper( $m[1] );
			if ( isset( $hex[ $k ] ) ) {
				$changed = true;
				return $hex[ $k ];
			}
			return $m[0];
		},
		$text
	);
	$out = preg_replace_callback(
		'/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(\s*,[^)]*)?\)/i',
		function ( $m ) use ( $pal, &$changed ) {
			$k = $m[1] . ',' . $m[2] . ',' . $m[3];
			if ( isset( $pal['rgb'][ $k ] ) ) {
				$changed = true;
				return 'rgba(' . $pal['rgb'][ $k ] . ( isset( $m[4] ) && '' !== $m[4] ? $m[4] : ',1' ) . ')';
			}
			return $m[0];
		},
		(string) $out
	);
	return array( $out, $changed );
}

/** Από ένα αρχείο CSS: μόνο οι κανόνες/δηλώσεις με παλιά χρώματα, αλλαγμένες, με !important, στο ίδιο @media. */
function ellada_palette_css_overrides( $css, $pal ) {
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$out = '';
	$len = strlen( $css );
	$i   = 0;
	while ( $i < $len ) {
		$open = strpos( $css, '{', $i );
		if ( false === $open ) {
			break;
		}
		$sel = trim( substr( $css, $i, $open - $i ) );
		// Το σώμα: μέχρι το αντίστοιχο κλείσιμο.
		$depth = 1;
		$j     = $open + 1;
		while ( $j < $len && $depth > 0 ) {
			if ( '{' === $css[ $j ] ) {
				++$depth;
			} elseif ( '}' === $css[ $j ] ) {
				--$depth;
			}
			++$j;
		}
		$body = substr( $css, $open + 1, $j - $open - 2 );
		$i    = $j;
		if ( 0 === strpos( $sel, '@media' ) || 0 === strpos( $sel, '@supports' ) || 0 === strpos( $sel, '@container' ) ) {
			$inner = ellada_palette_css_overrides( $body, $pal );
			if ( '' !== $inner ) {
				$out .= $sel . '{' . $inner . '}';
			}
			continue;
		}
		if ( '' === $sel || '@' === $sel[0] ) {
			continue; // @font-face, @keyframes κ.λπ.
		}
		$decls = array();
		foreach ( explode( ';', $body ) as $d ) {
			if ( false === strpos( $d, ':' ) ) {
				continue;
			}
			list( $d2, $changed ) = ellada_palette_swap( $d, $pal );
			if ( $changed ) {
				// Ίδιος επιλογέας, μετά το αρχικό: κερδίζει μόνο τον δικό του κανόνα (όχι πιο ειδικούς).
				$decls[] = trim( $d2 );
			}
		}
		if ( $decls ) {
			$out .= $sel . '{' . implode( ';', $decls ) . '}';
		}
	}
	return $out;
}

function ellada_palette_stylesheet( $key ) {
	$pal   = ellada_palettes()[ $key ];
	$up    = wp_upload_dir();
	$files = (array) glob( trailingslashit( $up['basedir'] ) . 'elementor/css/*.css' );
	$files[] = ELLADA_MAP_DIR . 'public/front.css';
	$sig   = $key;
	foreach ( $files as $f ) {
		$sig .= '|' . $f . ':' . @filemtime( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	$tkey   = 'ellada_pal_' . md5( $sig );
	$cached = get_transient( $tkey );
	if ( is_string( $cached ) ) {
		return $cached;
	}
	$vars = '';
	foreach ( $pal['vars'] as $k => $v ) {
		$vars .= $k . ':' . $v . ' !important;';
	}
	$css = ':root,body,body[class*="elementor-kit-"]{' . $vars . '}';
	foreach ( $files as $f ) {
		$css .= ellada_palette_css_overrides( (string) file_get_contents( $f ), $pal ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	// Ο χάρτης (React) βάζει χρώματα με inline style σε μορφή rgb().
	$css .= '[style*="rgb(10, 10, 10)"]{background-color:#174C4B !important}';
	set_transient( $tkey, $css, DAY_IN_SECONDS );
	return $css;
}

add_action(
	'template_redirect',
	function () {
		$key = ellada_palette_active();
		if ( ! $key ) {
			return;
		}
		$pal = ellada_palettes()[ $key ];
		ob_start(
			function ( $html ) use ( $pal, $key ) {
				if ( false === stripos( $html, '</body>' ) ) {
					return $html;
				}
				list( $html ) = ellada_palette_swap( $html, $pal );
				$bar = '<div id="ellada-pal-bar" style="position:fixed;left:12px;bottom:12px;z-index:99999;background:#174C4B;color:#F5F1E8;font:600 12px/1.3 system-ui,sans-serif;padding:8px 12px;border-radius:999px;box-shadow:0 4px 14px rgba(0,0,0,.25)">Προεπισκόπηση παλέτας: ' . esc_html( $pal['name'] ) . ' · <a style="color:#C5A46D" href="' . esc_url( add_query_arg( 'ellada_palette', 'off' ) ) . '">κανονικά χρώματα</a></div>';
				return str_ireplace( '</body>', '<style id="ellada-palette-preview">' . ellada_palette_stylesheet( $key ) . '</style>' . $bar . '</body>', $html );
			}
		);
	},
	0
);

/* Ο χάρτης ζει σε shadow DOM με δικά του στυλ μέσα στο JS: στην προεπισκόπηση φορτώνεται αντίγραφο με τα νέα χρώματα. */
add_filter(
	'script_loader_src',
	function ( $src ) {
		$key = ellada_palette_active();
		if ( ! $key || false === strpos( (string) $src, 'ellada-map/assets/js/ellada-map.js' ) ) {
			return $src;
		}
		$orig = ELLADA_MAP_DIR . 'assets/js/ellada-map.js';
		if ( ! file_exists( $orig ) ) {
			return $src;
		}
		$up   = wp_upload_dir();
		$name = 'ellada-palette/ellada-map-' . $key . '-' . filemtime( $orig ) . '.js';
		$path = trailingslashit( $up['basedir'] ) . $name;
		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( dirname( $path ) );
			list( $js ) = ellada_palette_swap( (string) file_get_contents( $orig ), ellada_palettes()[ $key ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			file_put_contents( $path, $js ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return trailingslashit( $up['baseurl'] ) . $name;
	},
	20
);

/* Η σελίδα με την προεπισκόπηση δεν μπαίνει ποτέ σε cache. */
add_action(
	'send_headers',
	function () {
		if ( ellada_palette_active() ) {
			nocache_headers();
			do_action( 'litespeed_control_set_nocache', 'palette preview' );
		}
	}
);
