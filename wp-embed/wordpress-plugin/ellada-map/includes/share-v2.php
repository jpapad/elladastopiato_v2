<?php
/**
 * Κοινοποίηση v2: κάρτα στο ύφος του site αντί για απλή λίστα.
 * Προεπισκόπηση, κουμπιά με εικονίδια (WhatsApp, Viber, Facebook, Pinterest, Email),
 * αντιγραφή συνδέσμου, QR «Ανοίξτε τη στο κινητό» (φτιάχνεται στη σελίδα, χωρίς τρίτους),
 * μήνυμα «<τίτλος> — συνταγή από <περιφέρεια> · Ελλάδα στο Πιάτο».
 * Πίσω από τη λειτουργία «share_v2»· προβολή χωρίς σύνδεση: ?ellada_demo=share
 *
 * @package ellada-map
 */

defined( 'ABSPATH' ) || exit;

function ellada_share2() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'share' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'share_v2' );
}

/** Το μήνυμα που συνοδεύει τον σύνδεσμο. */
function ellada_share2_msg( $id ) {
	$title  = get_the_title( $id );
	$region = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $id ) : '';
	$from   = ( $region && function_exists( 'ellada_region_from' ) ) ? ellada_region_from( $region ) : '';
	return '🍽️ ' . $title . ( $from ? ' — συνταγή από ' . $from : ' — συνταγή' );
}

function ellada_share2_icon( $k ) {
	$i = array(
		'whatsapp'  => '<path d="M3.5 20.5l1.3-4.2A8.5 8.5 0 1 1 8 19.4z"/><path d="M9 8.6c0 3.3 2.6 6.2 6.1 6.6l1.2-1.3-1.9-1-.9.8a4.6 4.6 0 0 1-2.6-2.6l.8-.9-1-1.9z" stroke-width="1.6"/>',
		'viber'     => '<path d="M6 3.5h12A2.5 2.5 0 0 1 20.5 6v8a2.5 2.5 0 0 1-2.5 2.5h-6l-4 4v-4H6A2.5 2.5 0 0 1 3.5 14V6A2.5 2.5 0 0 1 6 3.5z"/><path d="M9 7.5c0 2.6 1.9 4.6 4.4 5l.9-1-1.4-.8-.7.6a3 3 0 0 1-1.8-1.8l.6-.7-.8-1.4z" stroke-width="1.5"/>',
		'facebook'  => '<path d="M14.5 21v-7.5h2.6l.4-3h-3v-2c0-.9.3-1.5 1.6-1.5h1.6V4.3A21 21 0 0 0 15.4 4c-2.3 0-3.9 1.4-3.9 4v2.5H9v3h2.5V21"/>',
		'pinterest' => '<circle cx="12" cy="12" r="8.5"/><path d="M11 9.2c.5-1.4 3.6-1.6 4 .6.4 2.1-1.4 4.3-3 3.6M11.6 9.8 9.5 19"/>',
		'email'     => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
		'link'      => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'phone'     => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
	);
	return '<svg class="sh2-ico" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $i[ $k ] ?? '' ) . '</svg>';
}

/** Το περιεχόμενο της κάρτας (μπαίνει μέσα στο .ellada-share__menu). */
function ellada_share2_menu( $id, $url ) {
	$title = get_the_title( $id );
	$msg   = ellada_share2_msg( $id );
	$img   = get_the_post_thumbnail_url( $id, 'thumbnail' );
	$pin   = get_the_post_thumbnail_url( $id, 'large' );
	$nets  = array(
		'whatsapp'  => array( 'WhatsApp', 'https://wa.me/?text=' . rawurlencode( $msg . "\n" . $url ) ),
		'viber'     => array( 'Viber', 'viber://forward?text=' . rawurlencode( $url ) ),
		'facebook'  => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
		'pinterest' => array( 'Pinterest', 'https://pinterest.com/pin/create/button/?url=' . rawurlencode( $url ) . '&description=' . rawurlencode( $msg ) . ( $pin ? '&media=' . rawurlencode( $pin ) : '' ) ),
		'email'     => array( 'Email', 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $msg . "\n\n" . $url ) ),
	);
	$btns = '';
	foreach ( $nets as $k => $n ) {
		$btns .= sprintf(
			'<a class="sh2__btn sh2__btn--%1$s" href="%2$s"%3$s>%4$s<span>%5$s</span></a>',
			esc_attr( $k ),
			esc_url( $n[1], array( 'https', 'mailto', 'viber' ) ),
			in_array( $k, array( 'email', 'viber' ), true ) ? '' : ' target="_blank" rel="noopener"',
			ellada_share2_icon( $k ),
			esc_html( $n[0] )
		);
	}
	$thumb = $img
		? '<img src="' . esc_url( $img ) . '" alt="" width="52" height="52" loading="lazy">'
		: '<span class="sh2__ph" aria-hidden="true"></span>';
	$host  = wp_parse_url( $url, PHP_URL_HOST ) . wp_parse_url( $url, PHP_URL_PATH );

	return '<div class="sh2" data-msg="' . esc_attr( $msg ) . '">'
		. '<div class="sh2__head">' . $thumb . '<div><b>Μοιραστείτε τη συνταγή</b><span>' . esc_html( $title ) . '</span></div></div>'
		. '<div class="sh2__grid">' . $btns . '</div>'
		. '<div class="sh2__copy">' . ellada_share2_icon( 'link' ) . '<input type="text" readonly value="' . esc_attr( rtrim( $host, '/' ) ) . '" aria-label="Σύνδεσμος της συνταγής"><button type="button" data-sh2-copy>Αντιγραφή</button></div>'
		. '<button type="button" class="sh2__qrbtn" aria-expanded="false">' . ellada_share2_icon( 'phone' ) . '<span>Ανοίξτε τη στο κινητό</span><i aria-hidden="true">›</i></button>'
		. '<div class="sh2__qr" hidden><div class="sh2__qrimg" role="img" aria-label="QR code με τον σύνδεσμο της συνταγής"></div><p>Σκανάρετε με την κάμερα του κινητού σας — η συνταγή ανοίγει εκεί, για να μαγειρέψετε με άνεση.</p></div>'
		. '</div>';
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( is_singular( 'ellada_recipe' ) && ellada_share2() ) {
			$c[] = 'ellada-sh2';
		}
		return $c;
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! is_singular( 'ellada_recipe' ) || ! ellada_share2() ) {
			return;
		}
		$qr = plugins_url( 'public/qrcode.min.js', ELLADA_MAP_FILE );
		?>
		<script>
		(function () {
			var QR = <?php echo wp_json_encode( $qr ); ?>;
			function sh(el) { return el && el.closest('.ellada-ractions'); }
			// Κινητό: το μενού της συσκευής, με το μήνυμα μαζί.
			document.addEventListener('click', function (e) {
				var b = e.target.closest('[data-ellada-share]'); if (!b) return;
				if (!(navigator.share && window.matchMedia('(pointer: coarse)').matches)) return;
				var bar = sh(b), box = bar && bar.querySelector('.sh2'); if (!bar) return;
				e.preventDefault(); e.stopImmediatePropagation();
				var u = bar.getAttribute('data-url');
				// Μόνο ο σύνδεσμος: το Viber πετάει την αλλαγή γραμμής και τον κολλάει στο κείμενο· η κάρτα (og) δείχνει τίτλο/φωτογραφία.
				navigator.share({ url: u }).catch(function () {});
			}, true);
			document.addEventListener('click', function (e) {
				var c = e.target.closest('[data-sh2-copy]');
				if (c) {
					var bar = sh(c), url = bar.getAttribute('data-url'), inp = c.parentNode.querySelector('input');
					var ok = function () { c.textContent = '✓ Αντιγράφηκε'; c.classList.add('is-ok'); setTimeout(function () { c.textContent = 'Αντιγραφή'; c.classList.remove('is-ok'); }, 2000); };
					if (navigator.clipboard) navigator.clipboard.writeText(url).then(ok, function () { inp.value = url; inp.select(); });
					else { inp.value = url; inp.select(); }
					return;
				}
				var inp2 = e.target.closest('.sh2__copy input'); if (inp2) { inp2.select(); return; }
				var q = e.target.closest('.sh2__qrbtn');
				if (q) {
					var box = q.nextElementSibling, open = box.hidden;
					box.hidden = !open; q.setAttribute('aria-expanded', open ? 'true' : 'false');
					if (open && !box.getAttribute('data-done')) {
						var url2 = sh(q).getAttribute('data-url');
						var draw = function () {
							var g = window.qrcode(0, 'M'); g.addData(url2); g.make();
							box.querySelector('.sh2__qrimg').innerHTML = g.createSvgTag({ cellSize: 4, margin: 2, scalable: true }).replace(/fill="black"/g, 'fill="#123C3B"');
							box.setAttribute('data-done', '1');
						};
						if (window.qrcode) draw();
						else { var s = document.createElement('script'); s.src = QR; s.onload = draw; document.head.appendChild(s); }
					}
				}
			});
		})();
		</script>
		<?php
	},
	65
);

/* ═════════════════════ Εικόνα κοινοποίησης (og:image) σε JPG 1200×630 ═════════════════════ */

/**
 * Κάρτα κοινοποίησης για συνταγή με φωτογραφία: πετρόλ αριστερά (λογότυπο, «Συνταγή από …», τίτλος),
 * η φωτογραφία δεξιά, χρυσή γραμμή κάτω. JPG ώστε να φαίνεται σε Viber/Facebook/WhatsApp (το WebP δεν φαίνεται παντού).
 */
function ellada_share2_og_url( $id ) {
	$tid = (int) get_post_thumbnail_id( $id );
	if ( ! $tid || ! function_exists( 'imagettftext' ) ) {
		return '';
	}
	$file = get_attached_file( $tid );
	if ( ! $file || ! file_exists( $file ) ) {
		return '';
	}
	$title  = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	$region = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $id ) : '';
	$from   = ( $region && function_exists( 'ellada_region_from' ) ) ? ellada_region_from( $region ) : '';
	$up     = wp_upload_dir();
	$key    = substr( md5( $title . '|' . $from . '|' . $tid . '|' . filemtime( $file ) . '|s1' ), 0, 10 );
	$rel    = 'ellada-og/share-' . $id . '-' . $key . '.jpg';
	$path   = trailingslashit( $up['basedir'] ) . $rel;
	$url    = trailingslashit( $up['baseurl'] ) . $rel;
	if ( file_exists( $path ) ) {
		return $url;
	}
	$src = @imagecreatefromstring( (string) file_get_contents( $file ) ); // phpcs:ignore
	if ( ! $src ) {
		return '';
	}
	wp_mkdir_p( dirname( $path ) );
	foreach ( (array) glob( trailingslashit( $up['basedir'] ) . 'ellada-og/share-' . $id . '-*.jpg' ) as $old ) {
		@unlink( $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$w  = 1200;
	$h  = 630;
	$px = 500; // πού αρχίζει η φωτογραφία
	$im = imagecreatetruecolor( $w, $h );
	imagealphablending( $im, true );
	$pet  = imagecolorallocate( $im, 23, 76, 75 );
	$gold = imagecolorallocate( $im, 210, 154, 72 );
	$crem = imagecolorallocate( $im, 246, 242, 233 );
	$mute = imagecolorallocate( $im, 169, 189, 184 );
	imagefilledrectangle( $im, 0, 0, $w, $h, $pet );

	// Φωτογραφία: γεμίζει το δεξί κομμάτι (cover).
	$sw = imagesx( $src );
	$sh = imagesy( $src );
	$dw = $w - $px;
	$r  = max( $dw / $sw, $h / $sh );
	$cw = (int) round( $dw / $r );
	$ch = (int) round( $h / $r );
	imagecopyresampled( $im, $src, $px, 0, (int) ( ( $sw - $cw ) / 2 ), (int) ( ( $sh - $ch ) / 2 ), $dw, $h, $cw, $ch );
	imagedestroy( $src );
	// Ομαλό πέρασμα από το πετρόλ στη φωτογραφία.
	for ( $i = 0; $i < 170; $i++ ) {
		$a = (int) round( 127 * pow( $i / 170, 1.4 ) );
		imageline( $im, $px + $i, 0, $px + $i, $h, imagecolorallocatealpha( $im, 23, 76, 75, $a ) );
	}
	imagefilledrectangle( $im, 0, $h - 12, $w, $h, $gold );

	$fonts = ELLADA_MAP_DIR . 'public/fonts/';
	$big   = $fonts . 'FiraSansExtraCondensed_800ExtraBold.ttf';
	$small = $fonts . 'Commissioner_600SemiBold.ttf';
	$x     = 64;

	// Λογότυπο (το PNG της κεφαλίδας: κρεμ/κεραμιδί, για σκούρο φόντο).
	$logo = get_attached_file( 667 );
	if ( $logo && file_exists( $logo ) ) {
		$lg = @imagecreatefromstring( (string) file_get_contents( $logo ) ); // phpcs:ignore
		if ( $lg ) {
			$lw = 250;
			$lh = (int) round( imagesy( $lg ) * $lw / imagesx( $lg ) );
			imagecopyresampled( $im, $lg, $x, 54, 0, 0, $lw, $lh, imagesx( $lg ), imagesy( $lg ) );
			imagedestroy( $lg );
		}
	}

	$caps = fn( $t ) => mb_strtoupper( strtr( $t, array( 'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω', 'ΐ' => 'ϊ', 'ΰ' => 'ϋ', 'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι', 'Ό' => 'Ο', 'Ύ' => 'Υ', 'Ώ' => 'Ω' ) ) );

	// Τίτλος: έως 3 γραμμές μέσα στο πετρόλ.
	$maxw = $px - $x - 20;
	$size = 96;
	$text = $caps( $title );
	do {
		$lines = array();
		$cur   = '';
		foreach ( preg_split( '/\s+/u', $text ) as $word ) {
			$try = trim( $cur . ' ' . $word );
			$bb  = imagettfbbox( $size, 0, $big, $try );
			if ( $cur && ( $bb[2] - $bb[0] ) > $maxw ) {
				$lines[] = $cur;
				$cur     = $word;
			} else {
				$cur = $try;
			}
		}
		$lines[] = $cur;
		$widest  = 0;
		foreach ( $lines as $ln ) {
			$bb     = imagettfbbox( $size, 0, $big, $ln );
			$widest = max( $widest, $bb[2] - $bb[0] );
		}
		$size -= 4;
	} while ( ( count( $lines ) > 3 || $widest > $maxw ) && $size > 36 );
	$size += 4;
	$lh    = (int) round( $size * 1.02 );
	$block = 34 + 22 + count( $lines ) * $lh;
	$y     = (int) max( 200, ( $h - $block ) / 2 + 40 );

	imagettftext( $im, 19, 0, $x, $y, $gold, $small, $caps( $from ? 'Συνταγή από ' . $from : 'Συνταγή' ) );
	$y += 22 + $size;
	foreach ( $lines as $ln ) {
		imagettftext( $im, $size, 0, $x, $y, $crem, $big, $ln );
		$y += $lh;
	}
	imagettftext( $im, 18, 0, $x, $h - 46, $mute, $small, 'elladastopiato.gr' );

	imagejpeg( $im, $path, 86 );
	imagedestroy( $im );
	return file_exists( $path ) ? $url : '';
}

add_filter(
	'ellada_meta_data',
	function ( $d ) {
		if ( is_singular( 'ellada_recipe' ) && ellada_share2() ) {
			$img = ellada_share2_og_url( get_queried_object_id() );
			if ( $img ) {
				$d['image']    = $img;
				$d['og_share'] = 1;
			}
		}
		return $d;
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! is_singular( 'ellada_recipe' ) || ! ellada_share2() || ! function_exists( 'ellada_meta_data' ) ) {
			return;
		}
		$d = ellada_meta_data();
		if ( empty( $d['og_share'] ) ) {
			return;
		}
		echo '<meta property="og:image:type" content="image/jpeg">' . "\n";
		echo '<meta property="og:image:width" content="1200">' . "\n";
		echo '<meta property="og:image:height" content="630">' . "\n";
		printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( get_the_title() . ' — Ελλάδα στο Πιάτο' ) );
	},
	6
);
