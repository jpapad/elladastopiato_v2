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
	return $title . ( $from ? ' — συνταγή από ' . $from : ' — συνταγή' ) . ' · Ελλάδα στο Πιάτο';
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
		'viber'     => array( 'Viber', 'viber://forward?text=' . rawurlencode( $msg . "\n" . $url ) ),
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
				navigator.share({ title: bar.getAttribute('data-title'), text: box ? box.getAttribute('data-msg') : '', url: bar.getAttribute('data-url') }).catch(function () {});
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
