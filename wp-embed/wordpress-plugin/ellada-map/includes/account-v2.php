<?php
/**
 * Λογαριασμός — Βήμα 2 (λειτουργία «accounts_v2»· προβολή: ?ellada_demo=account2, κρατιέται 1 μέρα με cookie).
 *
 *   - «Οι προτάσεις μου»: ό,τι στείλατε από την Επικοινωνία ενώ είστε συνδεδεμένοι, με την πορεία του
 *     (Ελήφθη → Σε επεξεργασία → Δημοσιεύτηκε). Αν το θέλετε, το όνομά σας γράφεται στη συνταγή («Πρόταση αναγνώστη»).
 *   - 📝 «Οι σημειώσεις μου» σε κάθε συνταγή (μόνο εσείς τις βλέπετε).
 *   - 🛒 «Στη λίστα μου»: τα υλικά μπαίνουν σε μία λίστα για ψώνια στον λογαριασμό (τσεκάρισμα στο σούπερ μάρκετ).
 *   - 📁 Συλλογές: δικές σας ομάδες συνταγών («Για το Πάσχα», «Γρήγορα»…).
 * Τα στοιχεία τυπώνονται μόνο σε συνδεδεμένους (οι σελίδες τους δεν μπαίνουν σε cache).
 */

defined( 'ABSPATH' ) || exit;

function ellada_acct2() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	if ( ! function_exists( 'ellada_accounts' ) || ! ellada_accounts() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ( isset( $_GET['ellada_demo'] ) && 'account2' === $_GET['ellada_demo'] ) || ! empty( $_COOKIE['ellada_demo_acct2'] ) ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'accounts_v2' );
}

add_action(
	'init',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_admin() && isset( $_GET['ellada_demo'] ) && 'account2' === $_GET['ellada_demo'] && ! headers_sent() ) {
			setcookie( 'ellada_demo_acct2', '1', time() + DAY_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE['ellada_demo_acct2'] = '1';
		}
	}
);

/* ═════════════════════ Δεδομένα ═════════════════════ */

function ellada_acct2_notes( $uid ) {
	$n = get_user_meta( $uid, 'ellada_notes', true );
	return is_array( $n ) ? $n : array();
}

function ellada_acct2_shop( $uid ) {
	$s = get_user_meta( $uid, 'ellada_shop', true );
	return is_array( $s ) ? array_values( $s ) : array();
}

function ellada_acct2_lists( $uid ) {
	$l = get_user_meta( $uid, 'ellada_lists', true );
	return is_array( $l ) ? array_values( $l ) : array();
}

function ellada_acct2_clean_shop( $v ) {
	$out = array();
	foreach ( array_slice( is_array( $v ) ? $v : array(), 0, 20 ) as $r ) {
		if ( ! is_array( $r ) ) {
			continue;
		}
		$items = array();
		foreach ( array_slice( (array) ( $r['items'] ?? array() ), 0, 60 ) as $it ) {
			$x = mb_substr( sanitize_text_field( (string) ( is_array( $it ) ? ( $it['x'] ?? '' ) : $it ) ), 0, 160 );
			if ( '' !== $x ) {
				$items[] = array( 'x' => $x, 'd' => is_array( $it ) && ! empty( $it['d'] ) ? 1 : 0 );
			}
		}
		$id = absint( $r['r'] ?? 0 );
		$t  = mb_substr( sanitize_text_field( (string) ( $r['t'] ?? '' ) ), 0, 120 );
		if ( $items ) {
			$out[] = array( 'r' => $id, 't' => $t ? $t : ( $id ? get_the_title( $id ) : 'Λίστα' ), 'items' => $items );
		}
	}
	return $out;
}

function ellada_acct2_clean_lists( $v ) {
	$out = array();
	$ids = array();
	foreach ( array_slice( is_array( $v ) ? $v : array(), 0, 20 ) as $l ) {
		if ( ! is_array( $l ) ) {
			continue;
		}
		$n = mb_substr( sanitize_text_field( (string) ( $l['n'] ?? '' ) ), 0, 40 );
		$k = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) ( $l['k'] ?? '' ) ) );
		if ( '' === $n ) {
			continue;
		}
		$k = $k && ! isset( $ids[ $k ] ) ? substr( $k, 0, 12 ) : strtolower( wp_generate_password( 8, false, false ) );
		$ids[ $k ] = 1;
		$out[]     = array( 'k' => $k, 'n' => $n, 'ids' => function_exists( 'ellada_acct_ids' ) ? ellada_acct_ids( $l['ids'] ?? array(), 100 ) : array() );
	}
	return $out;
}

/** Οι προτάσεις του χρήστη: [ [τίτλος, ημερομηνία, κατάσταση, url δημοσίευσης], … ] */
function ellada_acct2_submissions( $uid ) {
	if ( ! defined( 'ELLADA_INBOX_CPT' ) ) {
		return array();
	}
	$out = array();
	$ids = get_posts(
		array(
			'post_type'      => ELLADA_INBOX_CPT,
			'post_status'    => array( 'private', 'publish', 'draft' ),
			'posts_per_page' => 30,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_ellada_user', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => (int) $uid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	$topics = function_exists( 'ellada_cv3_topics' ) ? ellada_cv3_topics() : array();
	foreach ( $ids as $id ) {
		$d     = (array) get_post_meta( $id, '_ellada_submission', true );
		$draft = (int) get_post_meta( $id, '_ellada_draft_id', true );
		$ds    = $draft ? get_post_status( $draft ) : '';
		$url   = '';
		if ( 'publish' === $ds ) {
			$st  = 'published';
			$url = get_permalink( $draft );
		} elseif ( $draft && $ds ) {
			$st = 'working';
		} else {
			$st = get_post_meta( $id, '_ellada_unread', true ) ? 'received' : 'read';
		}
		$title = $d['dish'] ?? ( $d['pname'] ?? '' );
		if ( '' === (string) $title ) {
			$title = preg_replace( '/^[^:]+:\s*/u', '', get_the_title( $id ) );
		}
		$out[] = array(
			'topic' => $topics[ $d['topic'] ?? '' ][0] ?? 'Μήνυμα',
			'title' => (string) $title,
			'date'  => get_the_date( 'j/n/Y', $id ),
			'st'    => $st,
			'url'   => $url,
		);
	}
	return $out;
}

/* Διαγραφή λογαριασμού: οι προτάσεις μένουν ως μηνύματα, χωρίς σύνδεση με τον χρήστη. */
add_action(
	'delete_user',
	function ( $uid ) {
		if ( ! defined( 'ELLADA_INBOX_CPT' ) ) {
			return;
		}
		foreach ( get_posts( array( 'post_type' => ELLADA_INBOX_CPT, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_ellada_user', 'meta_value' => (int) $uid ) ) as $id ) { // phpcs:ignore WordPress.DB.SlowDBQuery
			delete_post_meta( $id, '_ellada_user' );
		}
	}
);

/* ═════════════════════ REST ═════════════════════ */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ellada/v1',
			'/me/v2',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'callback'            => function ( WP_REST_Request $r ) {
					$uid = get_current_user_id();
					$out = array( 'ok' => true );
					$n   = $r['note'];
					if ( is_array( $n ) && ! empty( $n['id'] ) ) {
						$notes = ellada_acct2_notes( $uid );
						$id    = absint( $n['id'] );
						$text  = mb_substr( sanitize_textarea_field( (string) ( $n['text'] ?? '' ) ), 0, 2000 );
						if ( '' === trim( $text ) ) {
							unset( $notes[ $id ] );
						} elseif ( 'ellada_recipe' === get_post_type( $id ) ) {
							$notes[ $id ] = $text;
						}
						if ( count( $notes ) > 300 ) {
							$notes = array_slice( $notes, -300, null, true );
						}
						update_user_meta( $uid, 'ellada_notes', $notes );
					}
					if ( null !== $r['shop'] ) {
						$out['shop'] = ellada_acct2_clean_shop( $r['shop'] );
						update_user_meta( $uid, 'ellada_shop', $out['shop'] );
					}
					if ( null !== $r['lists'] ) {
						$out['lists'] = ellada_acct2_clean_lists( $r['lists'] );
						update_user_meta( $uid, 'ellada_lists', $out['lists'] );
					}
					$res = rest_ensure_response( $out );
					$res->header( 'Cache-Control', 'no-store, private' );
					return $res;
				},
			)
		);
	}
);

/* Εξαγωγή: και τα νέα δεδομένα. */
add_filter(
	'rest_post_dispatch',
	function ( $res, $server, $req ) {
		if ( '/ellada/v1/me/export' !== $req->get_route() || ! is_user_logged_in() || ! ( $res instanceof WP_REST_Response ) ) {
			return $res;
		}
		$uid   = get_current_user_id();
		$d     = (array) $res->get_data();
		$notes = array();
		foreach ( ellada_acct2_notes( $uid ) as $id => $t ) {
			$notes[] = array( 'recipe' => get_the_title( $id ), 'url' => get_permalink( $id ), 'note' => $t );
		}
		$d['notes']         = $notes;
		$d['shopping_list'] = ellada_acct2_shop( $uid );
		$d['collections']   = ellada_acct2_lists( $uid );
		$d['submissions']   = ellada_acct2_submissions( $uid );
		$res->set_data( $d );
		return $res;
	},
	10,
	3
);

/* ═════════════════════ Σελίδα συνταγής (μόνο συνδεδεμένοι) ═════════════════════ */

/* 📝 Οι σημειώσεις μου — μετά τα βήματα (και το «Το έφτιαξα!»). */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_recipe_steps' !== $tag || ! ellada_acct2() || ! is_singular( 'ellada_recipe' ) ) {
			return $output;
		}
		$id = function_exists( 'ellada_ctx_id' ) ? ellada_ctx_id( (array) $attr ) : get_the_ID();
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		$pen = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>';
		if ( ! is_user_logged_in() ) {
			return $output . '<p class="ellada-notes ellada-notes--cta">' . $pen . ' <span>Θέλετε να κρατάτε τις δικές σας σημειώσεις σε κάθε συνταγή; <a href="' . esc_url( ellada_acct_url() ) . '">Συνδεθείτε</a></span></p>';
		}
		$notes = ellada_acct2_notes( get_current_user_id() );
		return $output . sprintf(
			'<div class="ellada-notes" data-acct-note="%1$d"><label for="ellada-note-%1$d" class="ellada-notes__h">%2$s <span>Οι σημειώσεις μου</span> <small>μόνο εσείς τις βλέπετε</small></label><textarea id="ellada-note-%1$d" maxlength="2000" rows="3" placeholder="π.χ. Λίγο λιγότερο αλάτι, 10 λεπτά παραπάνω στον φούρνο…">%3$s</textarea><p class="ellada-notes__st" role="status" aria-live="polite"></p></div>',
			$id,
			$pen, // phpcs:ignore WordPress.Security.EscapeOutput
			esc_textarea( $notes[ $id ] ?? '' )
		);
	},
	30,
	3
);

/* 📁 «Συλλογή» στη μπάρα ενεργειών. */
add_filter(
	'ellada_recipe_actions_extra',
	function ( $html, $id ) {
		if ( ! ellada_acct2() || ! is_user_logged_in() ) {
			return $html;
		}
		return $html . sprintf(
			'<span class="ellada-coll" data-acct-coll="%1$d"><button type="button" class="ellada-ractions__btn" aria-expanded="false" aria-haspopup="true"><span aria-hidden="true">＋</span> <span>Συλλογή</span></button><span class="ellada-coll__pop" hidden role="group" aria-label="Οι συλλογές μου"></span></span>',
			$id
		);
	},
	20,
	2
);

/* «Πρόταση αναγνώστη: …» στις συνταγές που ήρθαν από λογαριασμό (αν το θέλησε). */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_recipe_steps' !== $tag ) {
			return $output;
		}
		$id   = function_exists( 'ellada_ctx_id' ) ? ellada_ctx_id( (array) $attr ) : get_the_ID();
		$name = (string) get_post_meta( $id, '_ellada_credit', true );
		if ( '' === $name ) {
			return $output;
		}
		return $output . '<p class="ellada-credit"><span aria-hidden="true">✉</span> Πρόταση αναγνώστη: <strong>' . esc_html( $name ) . '</strong> — ευχαριστούμε!</p>';
	},
	28,
	3
);

/* Σενάριο για σημειώσεις / λίστα / συλλογές (σε κάθε σελίδα συνδεδεμένου). */
add_action(
	'wp_footer',
	function () {
		if ( ! ellada_acct2() || ! is_user_logged_in() ) {
			return;
		}
		$uid = get_current_user_id();
		$cfg = array(
			'url'   => rest_url( 'ellada/v1/me/v2' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'shop'  => ellada_acct2_shop( $uid ),
			'lists' => ellada_acct2_lists( $uid ),
			'acct'  => ellada_acct_url(),
			'rid'   => is_singular( 'ellada_recipe' ) ? get_queried_object_id() : 0,
		);
		?>
		<script>
		(function () {
			var C = <?php echo wp_json_encode( $cfg ); ?>;
			window.elladaAcct2 = C;
			function save(body) {
				return fetch(C.url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': C.nonce }, body: JSON.stringify(body) })
					.then(function (r) { if (!r.ok) throw new Error(); return r.json(); });
			}
			C.save = save;
			function say(msg) {
				var t = document.querySelector('.ellada-toast');
				if (!t) { t = document.createElement('div'); t.className = 'ellada-toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
				t.textContent = msg; t.classList.add('is-on'); clearTimeout(t._t); t._t = setTimeout(function () { t.classList.remove('is-on'); }, 3200);
			}
			C.say = say;

			/* 📝 Σημειώσεις: αποθήκευση λίγο μετά το γράψιμο */
			document.querySelectorAll('[data-acct-note]').forEach(function (box) {
				var ta = box.querySelector('textarea'), st = box.querySelector('.ellada-notes__st'), id = +box.getAttribute('data-acct-note'), t = null, last = ta.value;
				function go() { if (ta.value === last) return; var v = ta.value; st.textContent = 'Αποθήκευση…';
					save({ note: { id: id, text: v } }).then(function () { last = v; st.textContent = 'Αποθηκεύτηκε ✓'; }).catch(function () { st.textContent = 'Δεν αποθηκεύτηκε — δοκιμάστε ξανά.'; }); }
				function grow() { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight + 2, 420) + 'px'; }
				ta.addEventListener('input', function () { grow(); clearTimeout(t); t = setTimeout(go, 900); });
				ta.addEventListener('blur', go); grow();
			});

			/* 🛒 «Στη λίστα μου» δίπλα στη «Λίστα για ψώνια» */
			document.querySelectorAll('[data-ellada-shop]').forEach(function (shop) {
				var sec = shop.closest('.ellada-ingredients'); if (!sec) return;
				var b = document.createElement('button'); b.type = 'button'; b.className = 'ellada-ingredients__shop is-acct';
				b.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg><span>Στη λίστα μου</span>';
				shop.insertAdjacentElement('afterend', b);
				var rid = C.rid || 0;
				b.addEventListener('click', function () {
					var items = [];
					sec.querySelectorAll('li').forEach(function (el) {
						var q = el.querySelector('.ellada-ingredients__qty'), it = el.querySelector('.ellada-ingredients__item');
						var x = ((q ? q.textContent.trim() + ' ' : '') + (it ? it.textContent : el.textContent)).replace(/\s+/g, ' ').trim();
						if (x) items.push({ x: x, d: 0 });
					});
					if (!items.length) return;
					var title = shop.getAttribute('data-title') || document.title;
					var list = C.shop.filter(function (r) { return !(rid && r.r === rid); });
					list.unshift({ r: rid, t: title, items: items });
					b.disabled = true;
					save({ shop: list.slice(0, 20) }).then(function (d) { C.shop = d.shop || list; b.querySelector('span').textContent = 'Στη λίστα ✓';
						var a = document.createElement('a'); a.href = C.acct + '#psonia'; a.className = 'ellada-fav-note'; a.textContent = 'Η λίστα μου →'; if (!b.nextElementSibling || !b.nextElementSibling.classList.contains('ellada-fav-note')) b.insertAdjacentElement('afterend', a);
						say('Τα υλικά μπήκαν στη λίστα για ψώνια του λογαριασμού σας.'); })
						.catch(function () { say('Δεν αποθηκεύτηκε — δοκιμάστε ξανά.'); }).then(function () { b.disabled = false; });
				});
			});

			/* 📁 Συλλογές: αναδυόμενο με τις συλλογές μου */
			document.querySelectorAll('[data-acct-coll]').forEach(function (wrap) {
				var id = +wrap.getAttribute('data-acct-coll'), btn = wrap.querySelector('button'), pop = wrap.querySelector('.ellada-coll__pop');
				function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
				function paint() {
					var h = '<span class="ellada-coll__t">Βάλτε τη συνταγή σε συλλογή</span>';
					C.lists.forEach(function (l, i) { h += '<label class="ellada-coll__row"><input type="checkbox" data-i="' + i + '"' + (l.ids.indexOf(id) > -1 ? ' checked' : '') + '> <span>' + esc(l.n) + '</span> <small>' + l.ids.length + '</small></label>'; });
					h += '<form class="ellada-coll__new"><label class="ellada-sr" for="coll-new-' + id + '">Νέα συλλογή</label><input id="coll-new-' + id + '" type="text" maxlength="40" placeholder="Νέα συλλογή, π.χ. Για το Πάσχα"><button type="submit">Προσθήκη</button></form>';
					h += '<a class="ellada-coll__all" href="' + C.acct + '#syllogi">Οι συλλογές μου →</a>';
					pop.innerHTML = h;
					var on = C.lists.some(function (l) { return l.ids.indexOf(id) > -1; });
					btn.classList.toggle('is-primary', on);
					btn.querySelector('span:last-child').textContent = on ? 'Σε συλλογή' : 'Συλλογή';
				}
				function store() { return save({ lists: C.lists }).then(function (d) { if (d.lists) C.lists = d.lists; paint(); }).catch(function () { say('Δεν αποθηκεύτηκε — δοκιμάστε ξανά.'); }); }
				function open(v) { pop.hidden = !v; btn.setAttribute('aria-expanded', v ? 'true' : 'false'); if (v) { paint(); var f = pop.querySelector('input'); if (f) f.focus(); } }
				btn.addEventListener('click', function () { open(pop.hidden); });
				pop.addEventListener('change', function (e) { var i = e.target.getAttribute('data-i'); if (i === null) return; var l = C.lists[+i], k = l.ids.indexOf(id);
					if (e.target.checked && k === -1) l.ids.unshift(id); if (!e.target.checked && k > -1) l.ids.splice(k, 1); store(); });
				pop.addEventListener('submit', function (e) { e.preventDefault(); var inp = pop.querySelector('.ellada-coll__new input'), n = inp.value.trim(); if (!n) { inp.focus(); return; }
					if (C.lists.length >= 20) { say('Έως 20 συλλογές.'); return; }
					C.lists.push({ k: '', n: n, ids: [id] }); store().then(function () { say('Νέα συλλογή «' + n + '»'); }); });
				document.addEventListener('click', function (e) { if (!pop.hidden && !wrap.contains(e.target)) open(false); });
				wrap.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !pop.hidden) { open(false); btn.focus(); } });
				paint();
			});
		})();
		</script>
		<?php
	},
	8
);

/* ═════════════════════ Σελίδα «Ο λογαριασμός μου»: νέες ενότητες ═════════════════════ */

add_filter(
	'ellada_account_sections',
	function ( $html, $user ) {
		if ( ! ellada_acct2() ) {
			return $html;
		}
		$uid   = $user->ID;
		$subs  = ellada_acct2_submissions( $uid );
		$shop  = ellada_acct2_shop( $uid );
		$lists = ellada_acct2_lists( $uid );
		$ico   = function ( $p ) {
			return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
		};
		$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
		$c_url   = $contact ? add_query_arg( 'thema', 'syntagi', get_permalink( $contact ) ) : '';
		ob_start();

		/* 🛒 Λίστα για ψώνια */
		?>
		<section class="ellada-acct__sec" id="psonia" aria-labelledby="acct-shop">
			<h3 id="acct-shop"><?php echo $ico( '<path d="M5 7h14l-1.5 12h-11L5 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Λίστα για ψώνια</h3>
			<div class="ellada-shop" data-acct-shop></div>
			<p class="ellada-acct__empty" data-acct-shop-empty hidden>Η λίστα είναι άδεια. Σε κάθε συνταγή, πατήστε <strong>«＋ Στη λίστα μου»</strong> κάτω από τα υλικά.</p>
		</section>

		<section class="ellada-acct__sec" id="syllogi" aria-labelledby="acct-lists">
			<h3 id="acct-lists"><?php echo $ico( '<path d="M3.5 7.5A2 2 0 0 1 5.5 5.5h4l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-10Z"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Οι συλλογές μου</h3>
			<?php if ( ! $lists ) : ?>
				<p class="ellada-acct__empty">Φτιάξτε δικές σας ομάδες συνταγών («Για το Πάσχα», «Γρήγορα», «Της γιαγιάς»…): σε κάθε συνταγή πατήστε <strong>«＋ Συλλογή»</strong> δίπλα στο ♡.</p>
			<?php else : ?>
				<div class="ellada-lists" data-acct-lists>
				<?php foreach ( $lists as $i => $l ) : ?>
					<details class="ellada-lists__one" data-i="<?php echo (int) $i; ?>"<?php echo 0 === $i ? ' open' : ''; ?>>
						<summary><span class="ellada-lists__n"><?php echo esc_html( $l['n'] ); ?></span> <span class="ellada-lists__c"><?php echo count( $l['ids'] ); ?> <?php echo 1 === count( $l['ids'] ) ? 'συνταγή' : 'συνταγές'; ?></span></summary>
						<div class="ellada-cards"><?php echo function_exists( 'ellada_acct_cards' ) ? ellada_acct_cards( $l['ids'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						<p class="ellada-lists__act"><button type="button" class="ellada-acct__btn2" data-rename>Μετονομασία</button> <button type="button" class="ellada-acct__link" data-remove>Διαγραφή συλλογής</button></p>
					</details>
				<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<section class="ellada-acct__sec" aria-labelledby="acct-subs">
			<h3 id="acct-subs"><?php echo $ico( '<path d="M4 6.5 12 12l8-5.5"/><rect x="3" y="5" width="18" height="14" rx="2.2"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Οι προτάσεις μου</h3>
			<?php if ( ! $subs ) : ?>
				<p class="ellada-acct__empty">Έχετε μια συνταγή του τόπου σας; <?php echo $c_url ? '<a href="' . esc_url( $c_url ) . '">Στείλτε τη μας →</a>' : ''; ?> Όσο είστε συνδεδεμένοι, θα βλέπετε εδώ την πορεία της — και αν θέλετε, θα γράψουμε το όνομά σας όταν δημοσιευτεί.</p>
			<?php else : ?>
				<ol class="ellada-subs">
				<?php
				$labels = array(
					'received'  => array( 'Ελήφθη', 1 ),
					'read'      => array( 'Το είδαμε', 2 ),
					'working'   => array( 'Σε επεξεργασία', 2 ),
					'published' => array( 'Δημοσιεύτηκε', 3 ),
				);
				foreach ( $subs as $s ) :
					$lb = $labels[ $s['st'] ] ?? $labels['received'];
					?>
					<li class="ellada-subs__one is-<?php echo esc_attr( $s['st'] ); ?>">
						<div class="ellada-subs__txt"><small><?php echo esc_html( $s['topic'] . ' · ' . $s['date'] ); ?></small><b><?php echo $s['url'] ? '<a href="' . esc_url( $s['url'] ) . '">' . esc_html( $s['title'] ) . ' →</a>' : esc_html( $s['title'] ); ?></b></div>
						<div class="ellada-subs__steps" aria-label="Κατάσταση: <?php echo esc_attr( $lb[0] ); ?>"><?php for ( $k = 1; $k <= 3; $k++ ) : ?><i class="<?php echo $k <= $lb[1] ? 'on' : ''; ?>" aria-hidden="true"></i><?php endfor; ?><span><?php echo esc_html( $lb[0] ); ?></span></div>
					</li>
				<?php endforeach; ?>
				</ol>
				<?php if ( $c_url ) : ?><p class="ellada-acct__more"><a href="<?php echo esc_url( $c_url ); ?>">Στείλτε κι άλλη συνταγή →</a></p><?php endif; ?>
			<?php endif; ?>
		</section>
		<?php
		return $html . ob_get_clean();
	},
	10,
	2
);

/* Σενάριο της σελίδας λογαριασμού: λίστα για ψώνια + συλλογές. */
add_action(
	'wp_footer',
	function () {
		if ( ! ellada_acct2() || ! is_user_logged_in() || ! function_exists( 'ellada_acct_is_page' ) || ! ellada_acct_is_page() ) {
			return;
		}
		?>
		<script>
		(function () {
			var C = window.elladaAcct2; if (!C) return;
			function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
			var box = document.querySelector('[data-acct-shop]'), empty = document.querySelector('[data-acct-shop-empty]');
			function paint() {
				if (!box) return;
				empty.hidden = C.shop.length > 0;
				if (!C.shop.length) { box.innerHTML = ''; return; }
				var h = '<div class="ellada-shop__bar"><button type="button" class="ellada-acct__btn2" data-copy>Αντιγραφή / αποστολή</button><button type="button" class="ellada-acct__link" data-clear-done>Σβήστε τα τσεκαρισμένα</button><button type="button" class="ellada-acct__link" data-clear-all>Άδειασμα</button></div>';
				C.shop.forEach(function (r, i) {
					var done = r.items.filter(function (x) { return x.d; }).length;
					h += '<div class="ellada-shop__r"><div class="ellada-shop__h"><b>' + esc(r.t) + '</b><small>' + done + '/' + r.items.length + '</small><button type="button" class="ellada-shop__x" data-del="' + i + '" aria-label="Αφαίρεση: ' + esc(r.t) + '">×</button></div><ul>';
					r.items.forEach(function (x, j) { h += '<li><label class="' + (x.d ? 'is-done' : '') + '"><input type="checkbox" data-r="' + i + '" data-j="' + j + '"' + (x.d ? ' checked' : '') + '> <span>' + esc(x.x) + '</span></label></li>'; });
					h += '</ul></div>';
				});
				box.innerHTML = h;
			}
			function store() { return C.save({ shop: C.shop }).then(function (d) { if (d.shop) C.shop = d.shop; }).catch(function () { C.say('Δεν αποθηκεύτηκε — δοκιμάστε ξανά.'); }); }
			if (box) {
				paint();
				box.addEventListener('change', function (e) { var r = e.target.getAttribute('data-r'); if (r === null) return; C.shop[+r].items[+e.target.getAttribute('data-j')].d = e.target.checked ? 1 : 0;
					e.target.parentNode.classList.toggle('is-done', e.target.checked); var s = e.target.closest('.ellada-shop__r').querySelector('small'); var it = C.shop[+r].items; s.textContent = it.filter(function (x) { return x.d; }).length + '/' + it.length; store(); });
				box.addEventListener('click', function (e) {
					var b = e.target.closest('button'); if (!b) return;
					if (b.hasAttribute('data-del')) { C.shop.splice(+b.getAttribute('data-del'), 1); paint(); store(); }
					else if (b.hasAttribute('data-clear-done')) { C.shop.forEach(function (r) { r.items = r.items.filter(function (x) { return !x.d; }); }); C.shop = C.shop.filter(function (r) { return r.items.length; }); paint(); store(); }
					else if (b.hasAttribute('data-clear-all')) { if (confirm('Να αδειάσει όλη η λίστα;')) { C.shop = []; paint(); store(); } }
					else if (b.hasAttribute('data-copy')) {
						var lines = []; C.shop.forEach(function (r) { var left = r.items.filter(function (x) { return !x.d; }); if (!left.length) return; lines.push(r.t + ':'); left.forEach(function (x) { lines.push('• ' + x.x); }); lines.push(''); });
						var text = lines.join('\n').trim() || 'Όλα τσεκαρισμένα!';
						if (navigator.share && window.matchMedia('(pointer: coarse)').matches) navigator.share({ title: 'Λίστα για ψώνια', text: text }).catch(function () {});
						else if (navigator.clipboard) navigator.clipboard.writeText(text).then(function () { C.say('Η λίστα αντιγράφηκε.'); }, function () { window.prompt('Αντιγράψτε τη λίστα:', text); });
						else window.prompt('Αντιγράψτε τη λίστα:', text);
					}
				});
			}
			var lists = document.querySelector('[data-acct-lists]');
			if (lists) lists.addEventListener('click', function (e) {
				var b = e.target.closest('button'); if (!b) return; var d = b.closest('[data-i]'), i = +d.getAttribute('data-i'), l = C.lists[i]; if (!l) return;
				if (b.hasAttribute('data-rename')) { var n = (prompt('Νέο όνομα συλλογής:', l.n) || '').trim(); if (!n) return; l.n = n.slice(0, 40); d.querySelector('.ellada-lists__n').textContent = l.n; C.save({ lists: C.lists }); }
				if (b.hasAttribute('data-remove')) { if (!confirm('Να διαγραφεί η συλλογή «' + l.n + '»; Οι συνταγές δεν σβήνονται.')) return; C.lists.splice(i, 1); C.save({ lists: C.lists }).then(function () { location.reload(); }); }
			});
		})();
		</script>
		<?php
	},
	9
);

/* ═════════════════════ Email: «Η πρότασή σας δημοσιεύτηκε» ═════════════════════ */

add_action(
	'transition_post_status',
	function ( $new, $old, $post ) {
		if ( 'publish' !== $new || 'publish' === $old || ! in_array( $post->post_type, array( 'ellada_recipe', 'ellada_point' ), true ) ) {
			return;
		}
		$inbox = (int) get_post_meta( $post->ID, '_ellada_inbox_id', true );
		$uid   = $inbox ? (int) get_post_meta( $inbox, '_ellada_user', true ) : 0;
		if ( ! $uid || get_post_meta( $post->ID, '_ellada_notified', true ) ) {
			return;
		}
		$user = get_userdata( $uid );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		update_post_meta( $post->ID, '_ellada_notified', time() );
		// Μετά την αποθήκευση (για σωστό permalink).
		add_action(
			'shutdown',
			function () use ( $post, $user ) {
				$name   = function_exists( 'ellada_acct_name' ) ? ellada_acct_name( $user ) : '';
				$what   = 'ellada_recipe' === $post->post_type ? 'Η συνταγή που μας στείλατε' : 'Ο τόπος που μας προτείνατε';
				$credit = (string) get_post_meta( $post->ID, '_ellada_credit', true );
				$body   = ( $name ? "Γεια σας {$name}!" : 'Γεια σας!' ) . "\n\n"
					. "{$what} δημοσιεύτηκε στο «Ελλάδα στο Πιάτο»:\n\n«" . get_the_title( $post->ID ) . "»\n" . get_permalink( $post->ID ) . "\n\n"
					. ( $credit ? "Όπως ζητήσατε, γράφει «Πρόταση αναγνώστη: {$credit}».\n\n" : '' )
					. "Σας ευχαριστούμε πολύ που μοιραστήκατε τον τόπο σας μαζί μας!\n\n"
					. 'Όλες οι προτάσεις σας: ' . ( function_exists( 'ellada_acct_url' ) ? ellada_acct_url() : home_url( '/' ) ) . "\n\n— Ελλάδα στο Πιάτο\n" . home_url( '/' );
				wp_mail( $user->user_email, 'Δημοσιεύτηκε! — ' . get_the_title( $post->ID ), $body, array( 'Content-Type: text/plain; charset=UTF-8' ) );
			}
		);
	},
	10,
	3
);
