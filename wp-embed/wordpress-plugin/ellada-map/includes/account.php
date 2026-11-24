<?php
/**
 * Λογαριασμός επισκέπτη v1 (λειτουργία «accounts»· προβολή χωρίς σύνδεση ?ellada_demo=account).
 *
 *   - Σύνδεση ΧΩΡΙΣ κωδικό: ο επισκέπτης γράφει το email του και παίρνει σύνδεσμο (ισχύει 20', μία φορά).
 *     Ο σύνδεσμος ανοίγει σελίδα με κουμπί «Σύνδεση» (POST), ώστε τα φίλτρα των email που «ανοίγουν»
 *     συνδέσμους να μην τον καταναλώνουν. Λογαριασμός δημιουργείται μόνο όταν πατηθεί το κουμπί.
 *   - Χωρίς Google/Facebook (τίποτα σε τρίτους). Δεν κρατάμε IP. Μόνο email (+ όνομα αν το δώσει).
 *   - Ρόλος «ellada_member» χωρίς δικαιώματα: δεν βλέπει wp-admin / admin bar, δεν μπαίνει με κωδικό,
 *     ο σύνδεσμος δεν συνδέει ποτέ διαχειριστές/συντάκτες.
 *   - ♥ Αγαπημένα και «Την έφτιαξα» συγχρονίζονται με τον λογαριασμό (σε όλες τις συσκευές)·
 *     τα υπάρχοντα της συσκευής περνούν στον λογαριασμό με την πρώτη σύνδεση.
 *   - Σελίδα /logariasmos/ ([ellada_account]): Αγαπημένα, Τις έφτιαξα, όνομα, «Κατέβασε τα δεδομένα μου»,
 *     «Διάγραψε τον λογαριασμό μου», Αποσύνδεση. Δεν μπαίνει σε cache, noindex, εκτός sitemap.
 *   - Λογαριασμοί ανενεργοί > 24 μήνες σβήνονται αυτόματα (cron ellada_members_cleanup).
 * Οι σελίδες του site μένουν σε cache για όλους· τα προσωπικά στοιχεία μπαίνουν μόνο σε συνδεδεμένους (που δεν cachάρονται).
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_ACCT_ROLE = 'ellada_member';

/** Ανοιχτή η λειτουργία (flag ή προβολή demo). */
function ellada_accounts() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification
	$demo = $_REQUEST['ellada_demo'] ?? '';
	if ( 'account' === $demo ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'accounts' );
}

function ellada_acct_is_member( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user && $user->exists() && in_array( ELLADA_ACCT_ROLE, (array) $user->roles, true );
}

function ellada_acct_page() {
	static $p = false;
	if ( false === $p ) {
		$p = get_page_by_path( 'logariasmos', OBJECT, 'page' );
	}
	return $p;
}

function ellada_acct_url( $args = array() ) {
	$p   = ellada_acct_page();
	$url = $p ? get_permalink( $p ) : home_url( '/logariasmos/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

function ellada_acct_is_page() {
	$p = ellada_acct_page();
	return $p && is_page( $p->ID );
}

/* ═════════════════════ Ρόλος & ασφάλεια ═════════════════════ */

add_action(
	'init',
	function () {
		if ( ! get_role( ELLADA_ACCT_ROLE ) ) {
			add_role( ELLADA_ACCT_ROLE, 'Μέλος (επισκέπτης)', array() );
		}
		if ( ! wp_next_scheduled( 'ellada_members_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ellada_members_cleanup' );
		}
	}
);

/* Τα μέλη δεν βλέπουν το wp-admin ούτε τη μπάρα του. */
add_action(
	'init',
	function () {
		if ( ! is_admin() || wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || 'admin-post.php' === basename( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) ) ) {
			return;
		}
		if ( ellada_acct_is_member() ) {
			wp_safe_redirect( ellada_acct_url() );
			exit;
		}
	}
);
add_filter(
	'show_admin_bar',
	function ( $show ) {
		return ellada_acct_is_member() ? false : $show;
	}
);

/* Μέλη: μόνο με σύνδεσμο email — όχι κωδικός, όχι «ξέχασα τον κωδικό». */
add_filter(
	'wp_authenticate_user',
	function ( $user ) {
		if ( $user instanceof WP_User && ellada_acct_is_member( $user ) ) {
			return new WP_Error( 'ellada_member', 'Η σύνδεση γίνεται με σύνδεσμο στο email σας από τη σελίδα «Λογαριασμός».' );
		}
		return $user;
	},
	50
);
add_filter(
	'allow_password_reset',
	function ( $allow, $user_id ) {
		$u = get_userdata( $user_id );
		return $u && ellada_acct_is_member( $u ) ? false : $allow;
	},
	10,
	2
);

/* ═════════════════════ Σύνδεσμος σύνδεσης ═════════════════════ */

function ellada_acct_form_token() {
	$t = time();
	return $t . '.' . substr( hash_hmac( 'sha256', 'acct|' . $t, wp_salt( 'nonce' ) ), 0, 20 );
}

function ellada_acct_check_form_token( $tok ) {
	$p = explode( '.', (string) $tok );
	if ( 2 !== count( $p ) || ! hash_equals( substr( hash_hmac( 'sha256', 'acct|' . (int) $p[0], wp_salt( 'nonce' ) ), 0, 20 ), $p[1] ) ) {
		return false;
	}
	$age = time() - (int) $p[0];
	return $age >= 3 && $age <= DAY_IN_SECONDS;
}

function ellada_acct_key_name( $key ) {
	return 'ellada_ml_' . substr( hash_hmac( 'sha256', (string) $key, wp_salt() ), 0, 32 );
}

/** Μετρητής ορίου (μόνο hash — όχι email/IP σε καθαρή μορφή). */
function ellada_acct_bump( $what, $limit ) {
	$k = 'ellada_acrl_' . substr( hash_hmac( 'sha256', $what . '|' . gmdate( 'YmdH' ), wp_salt() ), 0, 20 );
	$n = (int) get_transient( $k );
	if ( $n >= $limit ) {
		return false;
	}
	set_transient( $k, $n + 1, HOUR_IN_SECONDS );
	return true;
}

/** Αποστολή συνδέσμου. Επιστρέφει true και όταν δεν στέλνεται (ίδια απάντηση για όλους). */
function ellada_acct_send_link( $email, $demo = false ) {
	$user = get_user_by( 'email', $email );
	if ( $user && ! ellada_acct_is_member( $user ) ) {
		return true; // διαχειριστές/συντάκτες: ποτέ σύνδεση με σύνδεσμο.
	}
	$key = wp_generate_password( 40, false, false );
	set_transient( ellada_acct_key_name( $key ), array( 'e' => $email ), 20 * MINUTE_IN_SECONDS );
	$args = array( 'k' => $key );
	if ( $demo ) {
		$args['ellada_demo'] = 'account';
	}
	$link = ellada_acct_url( $args );
	$name = $user && $user->display_name && $user->display_name !== $user->user_login ? $user->display_name : '';
	$body = ( $name ? "Γεια σας {$name}!" : 'Γεια σας!' ) . "\n\n"
		. "Πατήστε τον παρακάτω σύνδεσμο για να συνδεθείτε στο «Ελλάδα στο Πιάτο»:\n\n{$link}\n\n"
		. "Ο σύνδεσμος ισχύει για 20 λεπτά και μόνο μία φορά.\n\n"
		. "Αν δεν το ζητήσατε εσείς, απλώς αγνοήστε αυτό το μήνυμα — δεν γίνεται καμία αλλαγή και δεν δημιουργείται λογαριασμός.\n\n"
		. "— Ελλάδα στο Πιάτο\n" . home_url( '/' );
	return wp_mail( $email, 'Ο σύνδεσμος σύνδεσης στο «Ελλάδα στο Πιάτο»', $body );
}

/** Πατήθηκε «Σύνδεση»: έλεγχος κλειδιού, δημιουργία λογαριασμού αν χρειάζεται, cookie. */
add_action(
	'template_redirect',
	function () {
		if ( ! ellada_acct_is_page() ) {
			return;
		}
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'ellada account' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- το κλειδί του email είναι το διαπιστευτήριο.
		if ( isset( $_POST['ellada_login_key'] ) ) {
			$key  = sanitize_text_field( wp_unslash( $_POST['ellada_login_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$demo = ! empty( $_POST['ellada_demo'] ) ? array( 'ellada_demo' => 'account' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$name = ellada_acct_key_name( $key );
			$data = get_transient( $name );
			delete_transient( $name );
			if ( ! is_array( $data ) || empty( $data['e'] ) || ! is_email( $data['e'] ) ) {
				wp_safe_redirect( ellada_acct_url( $demo + array( 'expired' => 1 ) ) );
				exit;
			}
			$user = get_user_by( 'email', $data['e'] );
			$new  = false;
			if ( ! $user ) {
				$id = wp_insert_user(
					array(
						'user_login'           => 'm' . strtolower( wp_generate_password( 11, false, false ) ),
						'user_email'           => $data['e'],
						'user_pass'            => wp_generate_password( 32, true, true ),
						'role'                 => ELLADA_ACCT_ROLE,
						'display_name'         => '',
						'show_admin_bar_front' => 'false',
					)
				);
				if ( is_wp_error( $id ) ) {
					wp_safe_redirect( ellada_acct_url( $demo + array( 'expired' => 1 ) ) );
					exit;
				}
				$user = get_user_by( 'id', $id );
				update_user_meta( $id, 'ellada_member_since', time() );
				$new = true;
			}
			if ( ! ellada_acct_is_member( $user ) ) {
				wp_safe_redirect( ellada_acct_url( $demo + array( 'expired' => 1 ) ) );
				exit;
			}
			update_user_meta( $user->ID, 'ellada_last_seen', time() );
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID, true, is_ssl() );
			wp_safe_redirect( ellada_acct_url( $demo + array( 'welcome' => $new ? 'new' : '1' ) ) );
			exit;
		}

		// Λειτουργία κλειστή: η σελίδα δεν φαίνεται σε επισκέπτες (μόνο σε συνδεδεμένους / demo).
		if ( ! ellada_accounts() && ! is_user_logged_in() ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
	},
	5
);

/* Τελευταία επίσκεψη (μία φορά τη μέρα) — για τη διαγραφή ανενεργών. */
add_action(
	'wp',
	function () {
		if ( is_admin() || ! ellada_acct_is_member() ) {
			return;
		}
		$id   = get_current_user_id();
		$seen = (int) get_user_meta( $id, 'ellada_last_seen', true );
		if ( time() - $seen > DAY_IN_SECONDS ) {
			update_user_meta( $id, 'ellada_last_seen', time() );
		}
	}
);

add_action(
	'ellada_members_cleanup',
	function () {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$old = time() - 2 * YEAR_IN_SECONDS;
		foreach ( get_users( array( 'role' => ELLADA_ACCT_ROLE, 'fields' => 'ID', 'number' => 200 ) ) as $id ) {
			$seen = (int) get_user_meta( $id, 'ellada_last_seen', true );
			if ( $seen && $seen < $old ) {
				wp_delete_user( (int) $id );
			}
		}
	}
);

/* ═════════════════════ Δεδομένα λογαριασμού ═════════════════════ */

function ellada_acct_ids( $v, $max = 200 ) {
	$v = is_array( $v ) ? $v : array();
	return array_slice( array_values( array_unique( array_filter( array_map( 'absint', $v ) ) ) ), 0, $max );
}

function ellada_acct_get( $uid ) {
	return array(
		'favs' => ellada_acct_ids( get_user_meta( $uid, 'ellada_favs', true ) ),
		'made' => ellada_acct_ids( get_user_meta( $uid, 'ellada_made_list', true ) ),
	);
}

function ellada_acct_name( $user ) {
	return $user->display_name && $user->display_name !== $user->user_login ? $user->display_name : '';
}

add_action(
	'rest_api_init',
	function () {
		$nocache = function ( $data ) {
			$r = rest_ensure_response( $data );
			$r->header( 'Cache-Control', 'no-store, private' );
			return $r;
		};

		register_rest_route(
			'ellada/v1',
			'/account/link',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) use ( $nocache ) {
					if ( 'account' !== $r['ellada_demo'] && ! ( function_exists( 'ellada_feature' ) && ellada_feature( 'accounts' ) ) ) {
						return new WP_Error( 'closed', 'Η λειτουργία δεν είναι ακόμα ανοιχτή.', array( 'status' => 403 ) );
					}
					if ( '' !== trim( (string) $r['website'] ) ) {
						return $nocache( array( 'ok' => true ) ); // bot
					}
					if ( ! ellada_acct_check_form_token( $r['t'] ) ) {
						return new WP_Error( 'token', 'Κάτι πήγε στραβά. Ανανεώστε τη σελίδα και δοκιμάστε ξανά.', array( 'status' => 400 ) );
					}
					$email = sanitize_email( (string) $r['email'] );
					if ( ! is_email( $email ) ) {
						return new WP_Error( 'email', 'Γράψτε ένα σωστό email.', array( 'status' => 400 ) );
					}
					if ( empty( $r['consent'] ) ) {
						return new WP_Error( 'consent', 'Χρειάζεται η συγκατάθεσή σας.', array( 'status' => 400 ) );
					}
					if ( ! ellada_acct_bump( 'e|' . strtolower( $email ), 3 ) || ! ellada_acct_bump( 'all', 60 ) ) {
						return new WP_Error( 'rate', 'Πολλές προσπάθειες. Δοκιμάστε ξανά σε λίγο.', array( 'status' => 429 ) );
					}
					ellada_acct_send_link( $email, 'account' === $r['ellada_demo'] );
					return $nocache( array( 'ok' => true ) );
				},
			)
		);

		$logged = function () {
			return is_user_logged_in();
		};

		register_rest_route(
			'ellada/v1',
			'/me',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => $logged,
					'callback'            => function () use ( $nocache ) {
						return $nocache( ellada_acct_get( get_current_user_id() ) );
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => $logged,
					'callback'            => function ( WP_REST_Request $r ) use ( $nocache ) {
						$uid = get_current_user_id();
						if ( null !== $r['favs'] ) {
							update_user_meta( $uid, 'ellada_favs', ellada_acct_ids( $r['favs'] ) );
						}
						if ( null !== $r['made'] ) {
							update_user_meta( $uid, 'ellada_made_list', ellada_acct_ids( $r['made'] ) );
						}
						if ( null !== $r['name'] ) {
							$name = mb_substr( sanitize_text_field( (string) $r['name'] ), 0, 40 );
							$u    = wp_get_current_user();
							wp_update_user( array( 'ID' => $uid, 'display_name' => '' !== $name ? $name : $u->user_login, 'nickname' => '' !== $name ? $name : $u->user_login ) );
						}
						return $nocache( ellada_acct_get( $uid ) + array( 'ok' => true ) );
					},
				),
				array(
					'methods'             => 'DELETE',
					'permission_callback' => $logged,
					'callback'            => function ( WP_REST_Request $r ) use ( $nocache ) {
						$u = wp_get_current_user();
						if ( ! ellada_acct_is_member( $u ) ) {
							return new WP_Error( 'role', 'Αυτός ο λογαριασμός δεν διαγράφεται από εδώ.', array( 'status' => 403 ) );
						}
						if ( 'ΔΙΑΓΡΑΦΗ' !== trim( (string) $r['confirm'] ) ) {
							return new WP_Error( 'confirm', 'Γράψτε ΔΙΑΓΡΑΦΗ για επιβεβαίωση.', array( 'status' => 400 ) );
						}
						require_once ABSPATH . 'wp-admin/includes/user.php';
						wp_clear_auth_cookie();
						wp_delete_user( $u->ID );
						return $nocache( array( 'ok' => true, 'home' => home_url( '/' ) ) );
					},
				),
			)
		);

		register_rest_route(
			'ellada/v1',
			'/me/export',
			array(
				'methods'             => 'GET',
				'permission_callback' => $logged,
				'callback'            => function () use ( $nocache ) {
					$u    = wp_get_current_user();
					$d    = ellada_acct_get( $u->ID );
					$list = function ( $ids ) {
						$o = array();
						foreach ( $ids as $id ) {
							$o[] = array( 'id' => $id, 'title' => get_the_title( $id ), 'url' => get_permalink( $id ) );
						}
						return $o;
					};
					$since = (int) get_user_meta( $u->ID, 'ellada_member_since', true );
					$seen  = (int) get_user_meta( $u->ID, 'ellada_last_seen', true );
					return $nocache(
						array(
							'site'         => home_url( '/' ),
							'exported_at'  => gmdate( 'c' ),
							'email'        => $u->user_email,
							'name'         => ellada_acct_name( $u ),
							'member_since' => $since ? gmdate( 'c', $since ) : gmdate( 'c', strtotime( $u->user_registered ) ),
							'last_seen'    => $seen ? gmdate( 'c', $seen ) : null,
							'favorites'    => $list( $d['favs'] ),
							'made'         => $list( $d['made'] ),
						)
					);
				},
			)
		);
	}
);

/* ═════════════════════ Συγχρονισμός στη συσκευή (σε όλες τις σελίδες, μόνο για συνδεδεμένους) ═════════════════════ */

add_action(
	'wp_footer',
	function () {
		if ( ! is_user_logged_in() || ! ( ellada_accounts() || ellada_acct_is_member() ) ) {
			return;
		}
		$uid  = get_current_user_id();
		$data = ellada_acct_get( $uid ) + array(
			'uid'   => $uid,
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'me'    => rest_url( 'ellada/v1/me' ),
		);
		?>
		<script>
		(function () {
			var D = <?php echo wp_json_encode( $data ); ?>;
			function get(k) { try { return JSON.parse(localStorage.getItem(k) || 'null'); } catch (e) { return null; } }
			function set(k, v) { try { if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
			function localFavs() { var f = get('ellada-favs'); return Array.isArray(f) ? f.map(Number).filter(Boolean) : []; }
			function localMade() { var o = []; try { for (var i = 0; i < localStorage.length; i++) { var m = /^ellada-made-(\d+)$/.exec(localStorage.key(i)); if (m && get(m[0])) o.push(+m[1]); } } catch (e) {} return o; }
			function union(a, b) { var o = a.slice(); b.forEach(function (x) { if (o.indexOf(x) === -1) o.push(x); }); return o; }
			function write(favs, made) {
				set('ellada-favs', favs.length ? favs : null);
				localMade().forEach(function (id) { if (made.indexOf(id) === -1) set('ellada-made-' + id, null); });
				made.forEach(function (id) { set('ellada-made-' + id, 1); });
			}
			var first = String(get('ellada-acct')) !== String(D.uid);
			var favs = first ? union(localFavs(), D.favs) : D.favs.slice();
			var made = first ? union(localMade(), D.made) : D.made.slice();
			write(favs, made);
			set('ellada-acct', D.uid);
			var last = JSON.stringify([D.favs, D.made]);
			function push() {
				var now = [localFavs(), localMade().sort(function (a, b) { return a - b; })];
				var s = JSON.stringify(now); if (s === last) return;
				var prev = last; last = s;
				fetch(D.me, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': D.nonce }, body: JSON.stringify({ favs: now[0], made: now[1] }) })
					.then(function (r) { if (!r.ok) last = prev; document.dispatchEvent(new CustomEvent('ellada:acct-synced')); })
					.catch(function () { last = prev; });
			}
			last = JSON.stringify([D.favs, D.made.slice().sort(function (a, b) { return a - b; })]);
			if (first) push();
			document.addEventListener('click', function () { setTimeout(push, 200); }, true);
			window.addEventListener('pagehide', push);
			window.elladaAcct = D;
		})();
		</script>
		<?php
	},
	5
);

/* ═════════════════════ Σελίδα «Λογαριασμός» ═════════════════════ */

function ellada_acct_icon( $k ) {
	$p = array(
		'user'  => '<circle cx="12" cy="8.2" r="3.8"/><path d="M4.5 20.2c.9-3.9 3.9-6 7.5-6s6.6 2.1 7.5 6"/>',
		'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
		'check' => '<path d="m5 12.5 4.2 4.2L19 7"/>',
		'phone' => '<rect x="6.5" y="2.8" width="11" height="18.4" rx="2.4"/><path d="M10.5 18h3"/>',
		'lock'  => '<rect x="5" y="10.5" width="14" height="10" rx="2.2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>',
		'mail'  => '<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
	);
	return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $p[ $k ] ?? '' ) . '</svg>';
}

function ellada_acct_cards( $ids ) {
	$html = '';
	foreach ( $ids as $id ) {
		if ( 'ellada_recipe' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
			$html .= ellada_recipe_card( $id );
		}
	}
	return $html;
}

add_shortcode(
	'ellada_account',
	function () {
		ellada_front_style();
		$demo     = isset( $_GET['ellada_demo'] ) && 'account' === $_GET['ellada_demo']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$recipes  = get_post_type_archive_link( 'ellada_recipe' );
		$privacy  = get_page_by_path( 'politiki-aporritou' );
		$priv_url = $privacy ? get_permalink( $privacy ) : home_url( '/politiki-aporritou/' );
		ob_start();
		echo '<div class="ellada-acct ecv3">';

		/* ── Συνδεδεμένος ── */
		if ( is_user_logged_in() ) {
			$u      = wp_get_current_user();
			$d      = ellada_acct_get( $u->ID );
			$name   = ellada_acct_name( $u );
			$member = ellada_acct_is_member( $u );
			$wel    = isset( $_GET['welcome'] ) ? sanitize_key( $_GET['welcome'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<header class="ellada-acct__hero">
				<span class="ellada-acct__avatar" aria-hidden="true"><?php echo $name ? esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ) : ellada_acct_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div>
					<p class="ecv3-eyebrow"><?php echo 'new' === $wel ? 'Καλώς ήρθατε!' : 'Ο λογαριασμός μου'; ?></p>
					<h2 class="ellada-acct__hi">Γεια σας<?php echo $name ? ', ' . esc_html( $name ) : ''; ?></h2>
					<p class="ellada-acct__mail"><?php echo esc_html( $u->user_email ); ?></p>
				</div>
				<ul class="ellada-acct__stats">
					<li><b data-acct-count="favs"><?php echo count( $d['favs'] ); ?></b> αγαπημένες</li>
					<li><b data-acct-count="made"><?php echo count( $d['made'] ); ?></b> τις έφτιαξα</li>
				</ul>
			</header>
			<?php if ( 'new' === $wel ) : ?>
				<p class="ellada-acct__note" role="status">Ο λογαριασμός σας είναι έτοιμος. Ό,τι είχατε σημειώσει σε αυτή τη συσκευή (♥ και «Την έφτιαξα») πέρασε στον λογαριασμό σας και θα το βρίσκετε σε κάθε συσκευή όπου συνδέεστε.</p>
			<?php endif; ?>

			<section class="ellada-acct__sec" aria-labelledby="acct-favs">
				<h3 id="acct-favs"><?php echo ellada_acct_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Αγαπημένες συνταγές</h3>
				<div class="ellada-cards" data-acct-list="favs"><?php echo ellada_acct_cards( $d['favs'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<p class="ellada-acct__empty" data-acct-empty="favs"<?php echo $d['favs'] ? ' hidden' : ''; ?>>Δεν έχετε ακόμα αγαπημένες. Πατήστε <strong>♡ Αγαπημένη</strong> σε όποια συνταγή σας αρέσει. <a href="<?php echo esc_url( $recipes ); ?>">Δείτε τις συνταγές →</a></p>
			</section>

			<section class="ellada-acct__sec" aria-labelledby="acct-made">
				<h3 id="acct-made"><?php echo ellada_acct_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Τις έφτιαξα</h3>
				<div class="ellada-cards" data-acct-list="made"><?php echo ellada_acct_cards( $d['made'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<p class="ellada-acct__empty" data-acct-empty="made"<?php echo $d['made'] ? ' hidden' : ''; ?>>Όταν μαγειρέψετε μια συνταγή, πατήστε <strong>«Το έφτιαξα!»</strong> στο τέλος των βημάτων και θα τη βρίσκετε εδώ.</p>
			</section>

			<?php echo apply_filters( 'ellada_account_sections', '', $u ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<section class="ellada-acct__sec ellada-acct__set" aria-labelledby="acct-set">
				<h3 id="acct-set"><?php echo ellada_acct_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Ρυθμίσεις</h3>
				<?php if ( $member ) : ?>
				<form class="ellada-acct__name" data-acct-name>
					<div class="ecv3-field">
						<label for="acct-name">Πώς να σας λέμε; <span class="ellada-acct__opt">(προαιρετικό)</span></label>
						<div class="ellada-acct__inline">
							<input type="text" id="acct-name" name="name" maxlength="40" autocomplete="given-name" value="<?php echo esc_attr( $name ); ?>" placeholder="π.χ. Μαρία">
							<button type="submit" class="ellada-acct__btn2">Αποθήκευση</button>
						</div>
					</div>
					<p class="ecv3-status" role="status" aria-live="polite"></p>
				</form>
				<?php endif; ?>
				<div class="ellada-acct__data">
					<div>
						<h4>Τα δεδομένα μου</h4>
						<p>Κρατάμε μόνο το email σας<?php echo $member ? ', το όνομα αν το δώσατε' : ''; ?> και ό,τι αποθηκεύετε εδώ (συνταγές, σημειώσεις, λίστες). Χωρίς IP, χωρίς υπηρεσίες τρίτων. <a href="<?php echo esc_url( $priv_url ); ?>">Απόρρητο</a></p>
						<button type="button" class="ellada-acct__btn2" data-acct-export>Κατεβάστε τα δεδομένα μου</button>
					</div>
					<?php if ( $member ) : ?>
					<details class="ellada-acct__del">
						<summary>Διαγραφή λογαριασμού</summary>
						<form data-acct-delete>
							<p>Ο λογαριασμός και ό,τι έχει αποθηκευτεί σε αυτόν σβήνονται οριστικά. Τα ♥ μένουν μόνο σε αυτή τη συσκευή.</p>
							<div class="ecv3-field">
								<label for="acct-del">Γράψτε <strong>ΔΙΑΓΡΑΦΗ</strong> για επιβεβαίωση</label>
								<div class="ellada-acct__inline">
									<input type="text" id="acct-del" name="confirm" autocomplete="off" required>
									<button type="submit" class="ellada-acct__danger">Διαγραφή</button>
								</div>
							</div>
							<p class="ecv3-status" role="status" aria-live="polite"></p>
						</form>
					</details>
					<?php endif; ?>
				</div>
				<p class="ellada-acct__out"><a href="<?php echo esc_url( wp_logout_url( $demo ? ellada_acct_url( array( 'ellada_demo' => 'account' ) ) : home_url( '/' ) ) ); ?>">Αποσύνδεση</a></p>
			</section>
			<?php
			echo '</div>';
			ellada_acct_page_js( true );
			return ob_get_clean();
		}

		/* ── Από τον σύνδεσμο του email ── */
		$key = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $key ) {
			$data = get_transient( ellada_acct_key_name( $key ) );
			if ( is_array( $data ) && ! empty( $data['e'] ) ) {
				$e    = $data['e'];
				$at   = strpos( $e, '@' );
				$mask = mb_substr( $e, 0, 2 ) . str_repeat( '•', max( 1, $at - 2 ) ) . substr( $e, $at );
				?>
				<div class="ellada-acct__card ellada-acct__confirm">
					<span class="ellada-acct__bigi" aria-hidden="true"><?php echo ellada_acct_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<p class="ecv3-eyebrow">Σύνδεση</p>
					<h2 class="ecv3-title">Ένα κλικ ακόμα</h2>
					<p class="ecv3-sub">Θα συνδεθείτε ως <strong><?php echo esc_html( $mask ); ?></strong>.</p>
					<form method="post" action="<?php echo esc_url( ellada_acct_url() ); ?>">
						<input type="hidden" name="ellada_login_key" value="<?php echo esc_attr( $key ); ?>">
						<?php if ( $demo ) : ?><input type="hidden" name="ellada_demo" value="account"><?php endif; ?>
						<button type="submit" class="ecv3-btn">Σύνδεση <span aria-hidden="true">→</span></button>
					</form>
				</div>
				<?php
				echo '</div>';
				return ob_get_clean();
			}
			$expired = true;
		}
		$expired = ! empty( $expired ) || isset( $_GET['expired'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		/* ── Εγγραφή / σύνδεση ── */
		?>
		<div class="ellada-acct__grid">
			<div class="ellada-acct__intro">
				<p class="ecv3-eyebrow">Λογαριασμός</p>
				<h2 class="ecv3-title">Οι συνταγές σας, παντού</h2>
				<p class="ecv3-sub">Χωρίς κωδικό: γράφετε το email σας και σας στέλνουμε έναν σύνδεσμο για να μπείτε. Όλα τα υπόλοιπα στο site λειτουργούν και χωρίς λογαριασμό.</p>
				<ul class="ellada-acct__perks">
					<li><span><?php echo ellada_acct_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b>Τα αγαπημένα σας δεν χάνονται</b>Στο κινητό, στο tablet, στον υπολογιστή — όπου συνδεθείτε.</div></li>
					<li><span><?php echo ellada_acct_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b>Ό,τι έχετε μαγειρέψει</b>Οι συνταγές που πατήσατε «Το έφτιαξα!», σε μία λίστα.</div></li>
					<li><span><?php echo ellada_acct_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b>Μόνο το email σας</b>Χωρίς κωδικούς, χωρίς Google/Facebook, χωρίς διαφημίσεις. Τον διαγράφετε όποτε θέλετε.</div></li>
				</ul>
			</div>
			<div class="ellada-acct__card">
				<?php if ( $expired ) : ?>
					<p class="ellada-acct__warn" role="alert">Ο σύνδεσμος έληξε ή έχει ήδη χρησιμοποιηθεί. Ζητήστε έναν καινούργιο.</p>
				<?php endif; ?>
				<form class="ecv3-form" data-acct-link novalidate>
					<div class="ecv3-field">
						<label for="acct-email">Το email σας</label>
						<input type="email" id="acct-email" name="email" required autocomplete="email" inputmode="email" placeholder="onoma@email.gr">
					</div>
					<label class="ecv3-check"><input type="checkbox" name="consent" value="1" required> <span>Συμφωνώ να κρατηθεί το email μου για τον λογαριασμό, σύμφωνα με την <a href="<?php echo esc_url( $priv_url ); ?>">Πολιτική απορρήτου</a>.</span></label>
					<div class="ecv3-hp" aria-hidden="true"><label>Ιστοσελίδα <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
					<input type="hidden" name="t" value="<?php echo esc_attr( ellada_acct_form_token() ); ?>">
					<?php if ( $demo ) : ?><input type="hidden" name="ellada_demo" value="account"><?php endif; ?>
					<div class="ecv3-actions">
						<button type="submit" class="ecv3-btn">Στείλτε μου σύνδεσμο <span aria-hidden="true">→</span></button>
					</div>
					<p class="ecv3-status" role="status" aria-live="polite"></p>
				</form>
				<div class="ellada-acct__sent" hidden tabindex="-1">
					<span class="ellada-acct__bigi" aria-hidden="true"><?php echo ellada_acct_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h3>Ελέγξτε το email σας</h3>
					<p>Αν η διεύθυνση είναι σωστή, σας στείλαμε σύνδεσμο σύνδεσης. Ισχύει για 20 λεπτά. Δεν ήρθε; Κοιτάξτε και στα ανεπιθύμητα.</p>
					<button type="button" class="ellada-acct__btn2" data-acct-again>Άλλο email</button>
				</div>
			</div>
		</div>
		<?php
		echo '</div>';
		ellada_acct_page_js( false );
		return ob_get_clean();
	}
);

function ellada_acct_page_js( $logged ) {
	$cfg = array(
		'link'   => rest_url( 'ellada/v1/account/link' ),
		'me'     => rest_url( 'ellada/v1/me' ),
		'export' => rest_url( 'ellada/v1/me/export' ),
		'cards'  => rest_url( 'ellada/v1/cards' ),
		'nonce'  => $logged ? wp_create_nonce( 'wp_rest' ) : '',
	);
	?>
	<script>
	(function () {
		var C = <?php echo wp_json_encode( $cfg ); ?>;
		var root = document.querySelector('.ellada-acct'); if (!root) return;
		function api(url, opt) {
			opt = opt || {}; opt.credentials = 'same-origin'; opt.headers = opt.headers || {};
			if (C.nonce) opt.headers['X-WP-Nonce'] = C.nonce;
			if (opt.json) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(opt.json); delete opt.json; }
			return fetch(url, opt).then(function (r) { return r.json().then(function (d) { if (!r.ok) throw new Error((d && d.message) || 'Κάτι πήγε στραβά.'); return d; }); });
		}
		function status(form, msg, err) { var s = form.querySelector('.ecv3-status'); s.textContent = msg || ''; s.classList.toggle('is-err', !!err); }

		/* Αποστολή συνδέσμου */
		var lf = root.querySelector('[data-acct-link]');
		if (lf) {
			var sent = root.querySelector('.ellada-acct__sent');
			lf.addEventListener('submit', function (e) {
				e.preventDefault();
				var em = lf.email, cb = lf.consent, bad = null;
				[em, cb].forEach(function (x) { x.removeAttribute('aria-invalid'); });
				if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em.value.trim())) bad = em;
				else if (!cb.checked) bad = cb;
				if (bad) { bad.setAttribute('aria-invalid', 'true'); bad.classList.add('is-touched'); bad.focus(); status(lf, bad === em ? 'Γράψτε ένα σωστό email.' : 'Χρειάζεται η συγκατάθεσή σας.', true); return; }
				var btn = lf.querySelector('button[type=submit]'); btn.disabled = true; status(lf, 'Αποστολή…');
				var body = { email: em.value.trim(), consent: 1, website: lf.website.value, t: lf.t.value };
				if (lf.ellada_demo) body.ellada_demo = 'account';
				api(C.link, { method: 'POST', json: body }).then(function () {
					lf.hidden = true; sent.hidden = false; sent.focus(); status(lf, '');
				}).catch(function (er) { status(lf, er.message, true); }).then(function () { btn.disabled = false; });
			});
			root.querySelector('[data-acct-again]').addEventListener('click', function () { sent.hidden = true; lf.hidden = false; lf.reset(); lf.email.focus(); });
		}

		/* Όνομα */
		var nf = root.querySelector('[data-acct-name]');
		if (nf) nf.addEventListener('submit', function (e) {
			e.preventDefault(); status(nf, 'Αποθήκευση…');
			api(C.me, { method: 'POST', json: { name: nf.name.value.trim() } }).then(function () {
				status(nf, 'Αποθηκεύτηκε ✓');
				var hi = root.querySelector('.ellada-acct__hi'); var n = nf.name.value.trim(); hi.textContent = 'Γεια σας' + (n ? ', ' + n : '');
			}).catch(function (er) { status(nf, er.message, true); });
		});

		/* Εξαγωγή */
		var ex = root.querySelector('[data-acct-export]');
		if (ex) ex.addEventListener('click', function () {
			api(C.export).then(function (d) {
				var a = document.createElement('a');
				a.href = URL.createObjectURL(new Blob([JSON.stringify(d, null, 2)], { type: 'application/json' }));
				a.download = 'ellada-stopiato-dedomena.json'; document.body.appendChild(a); a.click(); a.remove();
			}).catch(function (er) { alert(er.message); });
		});

		/* Διαγραφή */
		var df = root.querySelector('[data-acct-delete]');
		if (df) df.addEventListener('submit', function (e) {
			e.preventDefault();
			if (df.confirm.value.trim() !== 'ΔΙΑΓΡΑΦΗ') { status(df, 'Γράψτε ΔΙΑΓΡΑΦΗ με κεφαλαία.', true); df.confirm.focus(); return; }
			status(df, 'Διαγραφή…');
			api(C.me, { method: 'DELETE', json: { confirm: 'ΔΙΑΓΡΑΦΗ' } }).then(function (d) {
				try { localStorage.removeItem('ellada-acct'); } catch (er) {}
				location.href = d.home;
			}).catch(function (er) { status(df, er.message, true); });
		});

		/* Μετά τον πρώτο συγχρονισμό της συσκευής: ανανέωση των λιστών. */
		function refresh() {
			if (!window.elladaAcct) return;
			api(C.me).then(function (d) {
				['favs', 'made'].forEach(function (k) {
					var box = root.querySelector('[data-acct-list=' + k + ']'), empty = root.querySelector('[data-acct-empty=' + k + ']'), cnt = root.querySelector('[data-acct-count=' + k + ']');
					if (cnt) cnt.textContent = d[k].length;
					if (empty) empty.hidden = d[k].length > 0;
					if (!box) return;
					if (!d[k].length) { box.innerHTML = ''; return; }
					fetch(C.cards + '?ids=' + d[k].join(','), { credentials: 'omit' }).then(function (r) { return r.json(); }).then(function (c) { box.innerHTML = c.html || ''; });
				});
			}).catch(function () {});
		}
		document.addEventListener('ellada:acct-synced', refresh);
	})();
	</script>
	<?php
}

/* Σελίδα λογαριασμού: noindex, εκτός sitemap, περιθώρια όπως «Οι συνταγές μου». */
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( ellada_acct_is_page() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['max-image-preview'] );
		}
		return $robots;
	}
);
add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args, $type ) {
		$p = ellada_acct_page();
		if ( 'page' === $type && $p ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), array( $p->ID ) );
		}
		return $args;
	},
	10,
	2
);
add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_acct_is_page() ) {
			$c[] = 'ellada-page-mine';
			$c[] = 'ellada-page-acct';
		}
		return $c;
	}
);

/* ═════════════════════ Μενού: 👤 «Λογαριασμός» ═════════════════════ */

add_filter(
	'wp_nav_menu_items',
	function ( $items, $args ) {
		if ( ! ellada_accounts() ) {
			return $items;
		}
		$menu = isset( $args->menu ) ? wp_get_nav_menu_object( $args->menu ) : false;
		if ( ! $menu || 3 !== (int) $menu->term_id ) {
			return $items;
		}
		$drop   = isset( $args->menu_id ) && 0 === strpos( (string) $args->menu_id, 'menu-2-' );
		$active = ellada_acct_is_page();
		$label  = 'Λογαριασμός';
		$url    = isset( $_GET['ellada_demo'] ) && 'account' === $_GET['ellada_demo'] ? ellada_acct_url( array( 'ellada_demo' => 'account' ) ) : ellada_acct_url(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ico    = function_exists( 'ellada_menu_v2' ) && ellada_menu_v2()
			? '<svg class="ellada-mi__ico" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8.2" r="3.8"/><path d="M4.5 20.2c.9-3.9 3.9-6 7.5-6s6.6 2.1 7.5 6"/></svg><span class="ellada-mi__txt">' . $label . '</span>'
			: $label;
		return $items . sprintf(
			'<li class="menu-item menu-item-ellada-acct%1$s"><a href="%2$s" title="%3$s" aria-label="%3$s" class="ellada-mi ellada-mi--user elementor-item%4$s"%5$s>%6$s</a></li>',
			$active ? ' current-menu-item' : '',
			esc_url( $url ),
			esc_attr( $label ),
			$active ? ' elementor-item-active' : '',
			$drop ? ' tabindex="-1"' : '',
			$ico // phpcs:ignore WordPress.Security.EscapeOutput
		);
	},
	10,
	2
);

/* «Οι συνταγές μου»: πρόταση για λογαριασμό. */
add_filter(
	'do_shortcode_tag',
	function ( $out, $tag ) {
		if ( 'ellada_my_recipes' !== $tag || ! ellada_accounts() || is_user_logged_in() ) {
			return $out;
		}
		$demo = isset( $_GET['ellada_demo'] ) && 'account' === $_GET['ellada_demo']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return str_replace(
			'αποθηκεύεται μόνο σε αυτή τη συσκευή.',
			'αποθηκεύεται σε αυτή τη συσκευή. <a href="' . esc_url( ellada_acct_url( $demo ? array( 'ellada_demo' => 'account' ) : array() ) ) . '">Φτιάξτε λογαριασμό</a> για να τις έχετε σε όλες τις συσκευές σας.',
			$out
		);
	},
	10,
	2
);
