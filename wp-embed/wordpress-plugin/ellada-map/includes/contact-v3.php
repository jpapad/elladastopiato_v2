<?php
/**
 * Επικοινωνία v3 (λειτουργία «contact_v3»): δική μας φόρμα με πεδία ανά θέμα.
 *
 *   Θέματα: syntagi · topos · lathos · synergasia · allo (ίδια slugs με το ?thema=).
 *   Συνταγή: πιάτο, τόπος, υλικά, εκτέλεση, ιστορία, φωτογραφία (JPG/PNG/WebP ≤ 8 MB, «είναι δική μου»),
 *            «μπορείτε να αναφέρετε το όνομά μου».
 *   Τόπος:   είδος, όνομα, πού, γιατί αξίζει, τηλέφωνο/site.
 *   Διόρθωση: σελίδα (γεμίζει από ?selida=), τι γράφει, το σωστό, πηγή.
 *   Συνεργασία: ποιος είστε, η ιδέα, site/social.   Κάτι άλλο: μήνυμα.
 *
 * Κάθε αποστολή:
 *   - αποθηκεύεται ως ιδιωτικό «Μήνυμα» (CPT ellada_inbox, μενού «Προτάσεις & μηνύματα»)
 *   - στέλνεται στο info@ (plain text, Reply-To ο αποστολέας, μέσω του SMTP του site)
 *   - Συνταγή → πρόχειρη συνταγή (ellada_recipe, draft) · Τόπος → πρόχειρο σημείο (ellada_point, draft),
 *     με το κουτί «Πρόταση αναγνώστη» στο admin.
 * Spam: κρυφό πεδίο-παγίδα, χρόνος συμπλήρωσης ≥ 4" (υπογεγραμμένος), όριο 5 αποστολές/ώρα ανά συσκευή (hash, όχι IP).
 * Απόρρητο: τα στοιχεία του αποστολέα σβήνονται μετά από 12 μήνες (cron).
 * Η φόρμα του Elementor μένει στη σελίδα ως εφεδρεία: όταν η λειτουργία είναι κλειστή, εμφανίζεται ξανά.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_INBOX_CPT = 'ellada_inbox';

function ellada_contact_v3() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'contact_v3' );
}

/** slug => [ετικέτα κουμπιού, τιμή «Θέμα» της παλιάς φόρμας (για τις κάρτες), εικονίδιο] */
function ellada_cv3_topics() {
	$old  = function_exists( 'ellada_contact_topics' ) ? ellada_contact_topics() : array();
	$icon = function ( $slug, $fallback ) use ( $old ) {
		return $old[ $slug ][3] ?? $fallback;
	};
	$dots = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>';
	return array(
		'syntagi'    => array( 'Συνταγή', $old['syntagi'][0] ?? 'Πρόταση συνταγής', $icon( 'syntagi', $dots ) ),
		'topos'      => array( 'Τόπος / σημείο', $old['topos'][0] ?? 'Τοπικό σημείο στον χάρτη', $icon( 'topos', $dots ) ),
		'lathos'     => array( 'Διόρθωση', $old['lathos'][0] ?? 'Διόρθωση / λάθος', $icon( 'lathos', $dots ) ),
		'synergasia' => array( 'Συνεργασία', $old['synergasia'][0] ?? 'Συνεργασία', $icon( 'synergasia', $dots ) ),
		'prosvasimotita' => array( 'Προσβασιμότητα', 'Προσβασιμότητα', '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="4.5" r="1.8"/><path d="M12 7.5v6h5l2 5"/><path d="M8.5 10.5a5.5 5.5 0 1 0 6.6 7.4"/><path d="M8 9.5h7"/></svg>' ),
		'allo'       => array( 'Κάτι άλλο', 'Άλλο', $dots ),
	);
}

/**
 * Πεδία ανά θέμα: name => [ετικέτα, τύπος, υποχρεωτικό, βοήθεια/παράδειγμα]
 * τύποι: text, textarea, select:<a|b|c>, url, file, check
 */
function ellada_cv3_fields() {
	return array(
		'syntagi'    => array(
			'dish'        => array( 'Όνομα πιάτου', 'text', true, 'Π.χ. Σαρμαδάκια της γιαγιάς' ),
			'place'       => array( 'Από πού είναι;', 'text', true, 'Χωριό, δήμος ή περιοχή — π.χ. Ζαγόρι, Ιωάννινα' ),
			'ingredients' => array( 'Υλικά', 'textarea', false, 'Ένα υλικό ανά γραμμή, με ποσότητες αν τις ξέρετε' ),
			'method'      => array( 'Πώς φτιάχνεται', 'textarea', true, 'Όπως τη φτιάχνετε· τα υπόλοιπα θα τα γράψουμε μαζί' ),
			'story'       => array( 'Η ιστορία του', 'textarea', false, 'Πότε το φτιάχνετε, ποιος σας το έμαθε… (προαιρετικό)' ),
			'photo'       => array( 'Φωτογραφία', 'file', false, 'JPG, PNG ή WebP, έως 8 MB (προαιρετικό)' ),
			'photo_own'   => array( 'Η φωτογραφία είναι δική μου και μπορείτε να τη χρησιμοποιήσετε στο site', 'check', false, '' ),
			'credit'      => array( 'Μπορείτε να αναφέρετε το όνομά μου στη συνταγή', 'check', false, '' ),
		),
		'topos'      => array(
			'kind'    => array( 'Τι είναι;', 'select:Ταβέρνα / εστιατόριο|Παραγωγός|Αγορά / παντοπωλείο|Πανηγύρι / γιορτή|Αξιοθέατο|Άλλο', true, '' ),
			'pname'   => array( 'Όνομα', 'text', true, 'Π.χ. Τυροκομείο του κυρ-Νίκου' ),
			'where'   => array( 'Πού βρίσκεται;', 'text', true, 'Χωριό ή πόλη, δήμος — π.χ. Μέτσοβο' ),
			'why'     => array( 'Γιατί αξίζει;', 'textarea', true, 'Τι το κάνει ξεχωριστό' ),
			'contact' => array( 'Τηλέφωνο ή site', 'text', false, '(προαιρετικό)' ),
		),
		'lathos'     => array(
			'page'    => array( 'Σε ποια σελίδα;', 'text', true, 'Τίτλος ή διεύθυνση της σελίδας' ),
			'wrong'   => array( 'Τι γράφει;', 'textarea', true, '' ),
			'right'   => array( 'Ποιο είναι το σωστό;', 'textarea', true, '' ),
			'source'  => array( 'Πηγή', 'text', false, 'Αν έχετε, π.χ. ένα βιβλίο ή μια διεύθυνση (προαιρετικό)' ),
		),
		'synergasia' => array(
			'who'   => array( 'Ποιος είστε;', 'select:Φωτογράφος|Παραγωγός|Επιχείρηση|Συντάκτης / δημοσιογράφος|Φορέας / δήμος|Άλλο', true, '' ),
			'idea'  => array( 'Η ιδέα σας', 'textarea', true, 'Πείτε μας λίγα λόγια για εσάς και τι θα θέλατε να κάνουμε μαζί' ),
			'link'  => array( 'Site ή social', 'text', false, '(προαιρετικό)' ),
		),
		'prosvasimotita' => array(
			'page'    => array( 'Πού το συναντήσατε;', 'text', false, 'Σελίδα ή σημείο του site (προαιρετικό)' ),
			'problem' => array( 'Τι δυσκολία είχατε;', 'textarea', true, 'Π.χ. «Δεν μπορώ να ανοίξω τον χάρτη με το πληκτρολόγιο»' ),
			'tech'    => array( 'Τι χρησιμοποιείτε;', 'text', false, 'Π.χ. αναγνώστη οθόνης, μεγέθυνση, μόνο πληκτρολόγιο (προαιρετικό)' ),
		),
		'allo'       => array(
			'message' => array( 'Μήνυμα', 'textarea', true, 'Τι θέλετε να μας πείτε;' ),
		),
	);
}

/* ═════════════════════ CPT «Μηνύματα» (ιδιωτικό) ═════════════════════ */

add_action(
	'init',
	function () {
		register_post_type(
			ELLADA_INBOX_CPT,
			array(
				'labels'          => array(
					'name'          => 'Προτάσεις & μηνύματα',
					'singular_name' => 'Μήνυμα',
					'menu_name'     => 'Προτάσεις & μηνύματα',
					'all_items'     => 'Όλα τα μηνύματα',
					'edit_item'     => 'Μήνυμα',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-email-alt',
				'menu_position'   => 26,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
				'show_in_rest'    => false,
			)
		);
	}
);

/** Σε πόσες εκκρεμούν (για το φουσκάκι του μενού). */
function ellada_cv3_unread() {
	return (int) ( new WP_Query(
		array(
			'post_type'      => ELLADA_INBOX_CPT,
			'post_status'    => 'private',
			'meta_key'       => '_ellada_unread', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	) )->found_posts;
}
add_action(
	'admin_menu',
	function () {
		global $menu;
		$n = ellada_cv3_unread();
		if ( ! $n || ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as &$m ) {
			if ( isset( $m[2] ) && 'edit.php?post_type=' . ELLADA_INBOX_CPT === $m[2] ) {
				$m[0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>';
			}
		}
	},
	99
);

/** Στήλες λίστας: Θέμα, Από, Πρόχειρο. */
add_filter(
	'manage_' . ELLADA_INBOX_CPT . '_posts_columns',
	function ( $cols ) {
		return array(
			'cb'           => $cols['cb'] ?? '',
			'title'        => 'Μήνυμα',
			'ellada_topic' => 'Θέμα',
			'ellada_from'  => 'Από',
			'ellada_draft' => 'Πρόχειρο',
			'date'         => 'Ημερομηνία',
		);
	}
);
add_action(
	'manage_' . ELLADA_INBOX_CPT . '_posts_custom_column',
	function ( $col, $id ) {
		$d = (array) get_post_meta( $id, '_ellada_submission', true );
		if ( 'ellada_topic' === $col ) {
			$t = ellada_cv3_topics();
			echo esc_html( $t[ $d['topic'] ?? '' ][0] ?? '' );
			if ( get_post_meta( $id, '_ellada_unread', true ) ) {
				echo ' <strong style="color:#C2602F">● νέο</strong>';
			}
		} elseif ( 'ellada_from' === $col ) {
			echo esc_html( trim( ( $d['name'] ?? '' ) . ' · ' . ( $d['email'] ?? '' ), ' ·' ) );
		} elseif ( 'ellada_draft' === $col ) {
			$draft = (int) get_post_meta( $id, '_ellada_draft_id', true );
			if ( $draft && get_post( $draft ) ) {
				printf( '<a href="%s">%s →</a>', esc_url( get_edit_post_link( $draft ) ), esc_html( get_the_title( $draft ) ) );
			}
		}
	},
	10,
	2
);

/** Το περιεχόμενο της πρότασης σε κουτί (στο μήνυμα και στο πρόχειρο). */
function ellada_cv3_box_html( $data ) {
	$fields = ellada_cv3_fields();
	$topics = ellada_cv3_topics();
	$topic  = $data['topic'] ?? 'allo';
	$html   = '<table class="widefat striped" style="border:0"><tbody>';
	$html  .= '<tr><th style="width:180px">Θέμα</th><td>' . esc_html( $topics[ $topic ][0] ?? $topic ) . '</td></tr>';
	$html  .= '<tr><th>Όνομα</th><td>' . esc_html( $data['name'] ?? '' ) . '</td></tr>';
	$html  .= '<tr><th>Email</th><td>' . ( ! empty( $data['email'] ) ? '<a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>' : '—' ) . '</td></tr>';
	foreach ( $fields[ $topic ] ?? array() as $key => $f ) {
		if ( 'file' === $f[1] ) {
			$aid = (int) ( $data['photo_id'] ?? 0 );
			$val = $aid ? wp_get_attachment_image( $aid, 'medium' ) . '<br><a href="' . esc_url( wp_get_attachment_url( $aid ) ) . '" target="_blank">Πλήρες μέγεθος</a>' : '—';
		} elseif ( 'check' === $f[1] ) {
			$val = ! empty( $data[ $key ] ) ? '✔ Ναι' : '✘ Όχι';
		} else {
			$val = '' !== (string) ( $data[ $key ] ?? '' ) ? nl2br( esc_html( $data[ $key ] ) ) : '—';
		}
		$html .= '<tr><th>' . esc_html( $f[0] ) . '</th><td>' . $val . '</td></tr>';
	}
	if ( ! empty( $data['user'] ) ) {
		$html .= '<tr><th>Λογαριασμός</th><td>Ναι — το βλέπει στο «Ο λογαριασμός μου» · Να φαίνεται το όνομα αν δημοσιευτεί: ' . ( ! empty( $data['credit'] ) ? '✔ Ναι' : '✘ Όχι' ) . '</td></tr>';
	}
	if ( ! empty( $data['from_page'] ) ) {
		$html .= '<tr><th>Από τη σελίδα</th><td><a href="' . esc_url( $data['from_page'] ) . '" target="_blank">' . esc_html( $data['from_page'] ) . '</a></td></tr>';
	}
	return $html . '</tbody></table>';
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( ELLADA_INBOX_CPT, 'ellada_recipe', 'ellada_point' ) as $pt ) {
			add_meta_box(
				'ellada_cv3_box',
				ELLADA_INBOX_CPT === $pt ? 'Το μήνυμα' : 'Πρόταση αναγνώστη',
				function ( $post ) {
					$data = get_post_meta( $post->ID, '_ellada_submission', true );
					if ( ! $data ) {
						$inbox = (int) get_post_meta( $post->ID, '_ellada_inbox_id', true );
						$data  = $inbox ? get_post_meta( $inbox, '_ellada_submission', true ) : array();
					}
					if ( ! $data ) {
						echo '<p>—</p>';
						return;
					}
					if ( ELLADA_INBOX_CPT === $post->post_type ) {
						delete_post_meta( $post->ID, '_ellada_unread' );
						$draft = (int) get_post_meta( $post->ID, '_ellada_draft_id', true );
						if ( $draft && get_post( $draft ) ) {
							printf( '<p><a class="button button-primary" href="%s">Άνοιγμα του πρόχειρου «%s» →</a></p>', esc_url( get_edit_post_link( $draft ) ), esc_html( get_the_title( $draft ) ) );
						}
					} else {
						echo '<p style="margin-top:0">Ήρθε από τη φόρμα επικοινωνίας. Ξαναγράψτε το κείμενο στο ύφος του site πριν τη δημοσίευση.</p>';
					}
					echo ellada_cv3_box_html( (array) $data ); // phpcs:ignore WordPress.Security.EscapeOutput
				},
				$pt,
				'normal',
				'high'
			);
		}
	},
	5
);
// Το κουτί στις συνταγές/σημεία μόνο όταν υπάρχει πρόταση.
add_action(
	'do_meta_boxes',
	function ( $pt ) {
		if ( in_array( $pt, array( 'ellada_recipe', 'ellada_point' ), true ) ) {
			global $post;
			if ( $post && ! get_post_meta( $post->ID, '_ellada_inbox_id', true ) ) {
				remove_meta_box( 'ellada_cv3_box', $pt, 'normal' );
			}
		}
	}
);

/* ═════════════════════ Η φόρμα ═════════════════════ */

function ellada_cv3_token() {
	$t = time();
	return $t . '.' . substr( hash_hmac( 'sha256', 'cv3|' . $t, wp_salt( 'nonce' ) ), 0, 20 );
}

function ellada_cv3_field_html( $topic, $key, $f, $prefill = '' ) {
	list( $label, $type, $req, $help ) = $f;
	$id   = 'ecv3-' . $topic . '-' . $key;
	$name = 'f[' . $key . ']';
	$star = $req ? ' <span class="ecv3-req" aria-hidden="true">*</span>' : '';
	$attr = ( $req ? ' required data-req="1"' : '' ) . ' data-topic="' . esc_attr( $topic ) . '"';
	if ( 'check' === $type ) {
		return '<label class="ecv3-check" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . $attr . '><span>' . esc_html( $label ) . '</span></label>';
	}
	$out = '<div class="ecv3-field ecv3-field--' . esc_attr( $type === 'textarea' ? 'area' : 'line' ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . $star . '</label>';
	if ( 'textarea' === $type ) {
		$out .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4" maxlength="5000" placeholder="' . esc_attr( $help ) . '"' . $attr . '>' . esc_textarea( $prefill ) . '</textarea>';
	} elseif ( 0 === strpos( $type, 'select:' ) ) {
		$out .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $attr . '><option value="">Διαλέξτε…</option>';
		foreach ( explode( '|', substr( $type, 7 ) ) as $o ) {
			$out .= '<option>' . esc_html( $o ) . '</option>';
		}
		$out .= '</select>';
	} elseif ( 'file' === $type ) {
		$out .= '<div class="ecv3-file"><input type="file" id="' . esc_attr( $id ) . '" name="photo" accept="image/jpeg,image/png,image/webp"' . $attr . '><span class="ecv3-file__ui" aria-hidden="true"><span class="ecv3-file__btn">Επιλογή φωτογραφίας</span><span class="ecv3-file__name">' . esc_html( $help ) . '</span></span></div>';
	} else {
		$out .= '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" maxlength="300" value="' . esc_attr( $prefill ) . '" placeholder="' . esc_attr( $help ) . '"' . $attr . '>';
	}
	return $out . '</div>';
}

function ellada_cv3_form_html() {
	ellada_front_style();
	$topics = ellada_cv3_topics();
	$fields = ellada_cv3_fields();
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$start = isset( $_GET['thema'] ) ? sanitize_key( wp_unslash( $_GET['thema'] ) ) : '';
	$page  = isset( $_GET['selida'] ) ? absint( $_GET['selida'] ) : 0;
	// phpcs:enable
	$start   = isset( $topics[ $start ] ) ? $start : 'syntagi';
	$page_tx = $page && get_post( $page ) ? get_the_title( $page ) . ' — ' . get_permalink( $page ) : '';
	$privacy = get_page_by_path( 'politiki-aporritou' );
	$me      = function_exists( 'ellada_acct2' ) && ellada_acct2() && is_user_logged_in() ? wp_get_current_user() : null;
	$me_name  = $me && function_exists( 'ellada_acct_name' ) ? ellada_acct_name( $me ) : '';
	$me_email = $me ? $me->user_email : '';

	$chips = '';
	foreach ( $topics as $slug => $t ) {
		$chips .= sprintf(
			'<label class="ecv3-chip"><input type="radio" name="topic" value="%1$s"%2$s><span class="ecv3-chip__i" aria-hidden="true">%3$s</span><span>%4$s</span></label>',
			esc_attr( $slug ),
			checked( $slug, $start, false ),
			$t[2], // σταθερό SVG
			esc_html( $t[0] )
		);
	}
	$groups = '';
	foreach ( $fields as $slug => $list ) {
		$g = '';
		foreach ( $list as $key => $f ) {
			$g .= ellada_cv3_field_html( $slug, $key, $f, 'lathos' === $slug && 'page' === $key ? $page_tx : '' );
		}
		$groups .= '<div class="ecv3-group" data-group="' . esc_attr( $slug ) . '"' . ( $slug === $start ? '' : ' hidden' ) . '>' . $g . '</div>';
	}

	$map = array();
	foreach ( $topics as $slug => $t ) {
		$map[ $t[1] ] = $slug;
	}

	ob_start();
	?>
	<div class="ecv3" id="ecv3">
		<form class="ecv3-form" method="post" enctype="multipart/form-data" novalidate action="<?php echo esc_url( rest_url( 'ellada/v1/contact' ) ); ?>">
			<div class="ecv3-head">
				<p class="ecv3-eyebrow">Γράψτε μας</p>
				<h2 class="ecv3-title">Στείλτε μας μήνυμα</h2>
				<p class="ecv3-sub">Διαλέξτε θέμα και θα σας ζητήσουμε μόνο ό,τι χρειάζεται. Απαντάμε συνήθως μέσα σε 2–3 εργάσιμες.</p>
			</div>
			<fieldset class="ecv3-topics"><legend>Για ποιο θέμα;</legend><div class="ecv3-chips"><?php echo $chips; // phpcs:ignore WordPress.Security.EscapeOutput ?></div></fieldset>
			<div class="ecv3-row">
				<div class="ecv3-field ecv3-field--line"><label for="ecv3-name">Όνομα <span class="ecv3-req" aria-hidden="true">*</span></label><input type="text" id="ecv3-name" name="name" maxlength="120" required data-req="1" autocomplete="name" value="<?php echo esc_attr( $me_name ); ?>"></div>
				<div class="ecv3-field ecv3-field--line"><label for="ecv3-email">Email <span class="ecv3-req" aria-hidden="true">*</span></label><input type="email" id="ecv3-email" name="email" maxlength="160" required data-req="1" autocomplete="email" value="<?php echo esc_attr( $me_email ); ?>"></div>
			</div>
			<div class="ecv3-groups"><?php echo $groups; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="ecv3-hp" aria-hidden="true"><label>Αφήστε το κενό <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
			<input type="hidden" name="t" value="<?php echo esc_attr( ellada_cv3_token() ); ?>">
			<input type="hidden" name="from_page" value="">
			<?php if ( $me ) : ?>
			<label class="ecv3-check ecv3-credit" data-credit><input type="checkbox" name="credit" value="1" checked><span>Αν δημοσιευτεί, να γράψετε το όνομά μου («Πρόταση αναγνώστη: …»). Θα βλέπετε την πορεία της στο <strong>«Ο λογαριασμός μου»</strong>.</span></label>
			<?php endif; ?>
			<label class="ecv3-check ecv3-consent" for="ecv3-consent"><input type="checkbox" id="ecv3-consent" name="consent" value="1" required data-req="1"><span>Διάβασα την <?php echo $privacy ? '<a href="' . esc_url( get_permalink( $privacy ) ) . '" target="_blank">Πολιτική απορρήτου</a>' : 'Πολιτική απορρήτου'; ?> και συμφωνώ να κρατηθούν τα στοιχεία μου για να μου απαντήσετε.</span></label>
			<div class="ecv3-actions">
				<button type="submit" class="ecv3-btn"><span class="ecv3-btn__t">Αποστολή</span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
				<p class="ecv3-status" role="status" aria-live="polite"></p>
			</div>
		</form>
		<div class="ecv3-done" hidden tabindex="-1">
			<div class="ecv3-done__i" aria-hidden="true"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></div>
			<h2>Ευχαριστούμε!</h2>
			<p class="ecv3-done__t">Το μήνυμά σας έφτασε. Θα σας απαντήσουμε σύντομα στο email σας.</p>
			<button type="button" class="ecv3-again">Νέο μήνυμα</button>
		</div>
	</div>
	<script>
	(function () {
		var w = document.getElementById('ecv3'); if (!w) return;
		var f = w.querySelector('form'), st = w.querySelector('.ecv3-status'), btn = w.querySelector('.ecv3-btn');
		var MAP = <?php echo wp_json_encode( $map, JSON_UNESCAPED_UNICODE ); ?>;
		f.from_page.value = document.referrer && document.referrer.indexOf(location.host) > -1 ? document.referrer : '';
		function topic() { var r = f.querySelector('input[name=topic]:checked'); return r ? r.value : 'allo'; }
		function show(t, focus) {
			w.querySelectorAll('.ecv3-group').forEach(function (g) { var on = g.getAttribute('data-group') === t; g.hidden = !on;
				g.querySelectorAll('input,textarea,select').forEach(function (el) { el.disabled = !on; }); });
			w.querySelectorAll('.ecv3-chip').forEach(function (c) { c.classList.toggle('is-on', c.querySelector('input').value === t); });
			var cr = w.querySelector('[data-credit]'); if (cr) { var cOn = t === 'syntagi' || t === 'topos'; cr.hidden = !cOn; cr.querySelector('input').disabled = !cOn; }
			document.querySelectorAll('.ellada-contact__card').forEach(function (c) { c.classList.toggle('is-active', MAP[c.getAttribute('data-topic')] === t); });
			if (focus) { var first = w.querySelector('.ecv3-group:not([hidden]) input, .ecv3-group:not([hidden]) textarea, .ecv3-group:not([hidden]) select'); w.scrollIntoView({ behavior: 'smooth', block: 'start' }); if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 450); }
		}
		f.addEventListener('change', function (e) { if (e.target.name === 'topic') show(e.target.value, false); });
		// Κάρτες αριστερά: πάνε στο σωστό θέμα (πριν από το παλιό script).
		document.addEventListener('click', function (e) { var c = e.target.closest && e.target.closest('.ellada-contact__card'); if (!c) return; var t = MAP[c.getAttribute('data-topic')]; if (!t) return;
			e.preventDefault(); e.stopImmediatePropagation(); var r = f.querySelector('input[name=topic][value="' + t + '"]'); if (r) { r.checked = true; show(t, true); } }, true);
		// Φωτογραφία: όνομα αρχείου + έλεγχος μεγέθους
		var file = f.querySelector('input[type=file]');
		if (file) file.addEventListener('change', function () { var n = file.closest('.ecv3-file').querySelector('.ecv3-file__name'); var x = file.files[0];
			if (!x) return; if (x.size > 8 * 1024 * 1024) { file.value = ''; n.textContent = 'Το αρχείο ξεπερνά τα 8 MB'; return; } n.textContent = x.name; });
		// Λάθη μόνο αφού αγγίξει κανείς το πεδίο
		f.querySelectorAll('input,textarea,select').forEach(function (el) { el.addEventListener('blur', function () { el.classList.add('is-touched'); }); });
		w.querySelectorAll('textarea').forEach(function (t) { t.addEventListener('input', function () { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight + 2, 480) + 'px'; }); });
		function err(el, bad) {
			var id = (el.id || el.name) + '-err', box = el.closest('.ecv3-field, .ecv3-check'), m = document.getElementById(id);
			if (!bad) { if (m) m.remove(); el.removeAttribute('aria-describedby'); return; }
			if (!m && box) { m = document.createElement('span'); m.id = id; m.className = 'ecv3-err'; box.appendChild(m); }
			if (m) { m.textContent = el.type === 'email' && el.value ? 'Γράψτε ένα σωστό email.' : (el.type === 'checkbox' ? 'Χρειάζεται να το τσεκάρετε.' : 'Υποχρεωτικό πεδίο.'); el.setAttribute('aria-describedby', id); }
		}
		function valid() { var ok = true, firstBad = null;
			f.querySelectorAll('[data-req]').forEach(function (el) { if (el.disabled) return; var bad = el.type === 'checkbox' ? !el.checked : !String(el.value).trim();
				if (el.type === 'email' && !bad) bad = !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value);
				el.classList.add('is-touched'); el.toggleAttribute('aria-invalid', bad); err(el, bad); if (bad) { ok = false; firstBad = firstBad || el; } });
			var own = f.querySelector('input[name="f[photo_own]"]'); if (file && file.files.length && own && !own.disabled && !own.checked) { ok = false; own.setAttribute('aria-invalid', ''); firstBad = firstBad || own; }
			if (firstBad) firstBad.focus(); return ok; }
		f.addEventListener('submit', function (e) {
			e.preventDefault(); st.textContent = ''; st.classList.remove('is-err');
			if (!valid()) { st.textContent = 'Συμπληρώστε τα πεδία με αστεράκι.'; st.classList.add('is-err'); return; }
			btn.disabled = true; btn.querySelector('.ecv3-btn__t').textContent = 'Αποστολή…';
			fetch(f.action, { method: 'POST', body: new FormData(f), credentials: 'same-origin', headers: window.elladaAcct && window.elladaAcct.nonce ? { 'X-WP-Nonce': window.elladaAcct.nonce } : {} }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
				.then(function (res) { if (!res.ok || !res.d.ok) throw new Error(res.d && res.d.message ? res.d.message : 'Κάτι πήγε στραβά.');
					f.hidden = true; var done = w.querySelector('.ecv3-done'); if (res.d.text) done.querySelector('.ecv3-done__t').textContent = res.d.text; done.hidden = false; done.focus(); done.scrollIntoView({ behavior: 'smooth', block: 'center' }); })
				.catch(function (err) { st.textContent = err.message + ' Δοκιμάστε ξανά ή γράψτε μας στο info@elladastopiato.gr.'; st.classList.add('is-err'); })
				.then(function () { btn.disabled = false; btn.querySelector('.ecv3-btn__t').textContent = 'Αποστολή'; });
		});
		w.querySelector('.ecv3-again').addEventListener('click', function () { f.reset(); f.hidden = false; w.querySelector('.ecv3-done').hidden = true; show(topic(), true); });
		show(topic(), false);
	})();
	</script>
	<?php
	return ob_get_clean();
}

/* Η φόρμα μπαίνει στη θέση της φόρμας του Elementor στη σελίδα Επικοινωνία. */
add_filter(
	'elementor/widget/render_content',
	function ( $content, $widget ) {
		if ( ! ellada_contact_v3() || 'form' !== $widget->get_name() || ! is_page( 'epikoinonia' ) ) {
			return $content;
		}
		return ellada_cv3_form_html();
	},
	10,
	2
);
add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_contact_v3() && is_page( 'epikoinonia' ) ) {
			$c[] = 'ellada-cv3';
		}
		return $c;
	}
);
/* ═════════════════════ Αποστολή (REST) ═════════════════════ */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ellada/v1',
			'/contact',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => 'ellada_cv3_submit',
			)
		);
	}
);

function ellada_cv3_fail( $msg, $code = 400 ) {
	return new WP_REST_Response( array( 'ok' => false, 'message' => $msg ), $code );
}

function ellada_cv3_submit( WP_REST_Request $req ) {
	if ( ! ellada_contact_v3() ) {
		return ellada_cv3_fail( 'Η φόρμα δεν είναι διαθέσιμη.', 403 );
	}
	$p = $req->get_body_params();

	// Spam: παγίδα, χρόνος, όριο.
	if ( ! empty( $p['website'] ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 ); // σιωπηλά
	}
	$tok = explode( '.', (string) ( $p['t'] ?? '' ) );
	if ( 2 !== count( $tok ) || ! hash_equals( substr( hash_hmac( 'sha256', 'cv3|' . (int) $tok[0], wp_salt( 'nonce' ) ), 0, 20 ), $tok[1] ) ) {
		return ellada_cv3_fail( 'Η σελίδα έμεινε ανοιχτή πολλή ώρα· ανανεώστε τη και ξαναστείλτε.' );
	}
	$age = time() - (int) $tok[0];
	if ( $age < 4 || $age > 2 * DAY_IN_SECONDS ) {
		return ellada_cv3_fail( 'Ανανεώστε τη σελίδα και ξαναστείλτε.' );
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rk  = 'ellada_cv3_rl_' . substr( hash_hmac( 'sha256', $ip . gmdate( 'YmdH' ), wp_salt() ), 0, 16 ); // hash, όχι IP
	$cnt = (int) get_transient( $rk );
	if ( $cnt >= 5 ) {
		return ellada_cv3_fail( 'Έχετε στείλει αρκετά μηνύματα· δοκιμάστε ξανά σε λίγο.', 429 );
	}

	$topics = ellada_cv3_topics();
	$topic  = sanitize_key( $p['topic'] ?? '' );
	if ( ! isset( $topics[ $topic ] ) ) {
		return ellada_cv3_fail( 'Διαλέξτε θέμα.' );
	}
	$name  = sanitize_text_field( wp_unslash( $p['name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $p['email'] ?? '' ) );
	if ( '' === $name || ! is_email( $email ) ) {
		return ellada_cv3_fail( 'Συμπληρώστε όνομα και σωστό email.' );
	}
	if ( empty( $p['consent'] ) ) {
		return ellada_cv3_fail( 'Χρειάζεται η συναίνεση στην Πολιτική απορρήτου.' );
	}
	$in   = (array) ( $p['f'] ?? array() );
	$data = array(
		'topic'     => $topic,
		'name'      => $name,
		'email'     => $email,
		'from_page' => esc_url_raw( wp_unslash( $p['from_page'] ?? '' ) ),
	);
	// Από λογαριασμό (το nonce στέλνεται μόνο από συνδεδεμένους): σύνδεση με «Οι προτάσεις μου» + αναφορά ονόματος.
	if ( get_current_user_id() ) {
		$data['user']   = get_current_user_id();
		$data['credit'] = empty( $p['credit'] ) ? 0 : 1;
	}
	foreach ( ellada_cv3_fields()[ $topic ] as $key => $f ) {
		if ( 'file' === $f[1] ) {
			continue;
		}
		$v = 'check' === $f[1] ? ( empty( $in[ $key ] ) ? 0 : 1 ) : ( 'textarea' === $f[1] ? sanitize_textarea_field( wp_unslash( $in[ $key ] ?? '' ) ) : sanitize_text_field( wp_unslash( $in[ $key ] ?? '' ) ) );
		if ( 0 === strpos( $f[1], 'select:' ) && $v && ! in_array( $v, explode( '|', substr( $f[1], 7 ) ), true ) ) {
			$v = '';
		}
		if ( $f[2] && ( '' === $v || 0 === $v ) ) {
			return ellada_cv3_fail( 'Συμπληρώστε το πεδίο «' . $f[0] . '».' );
		}
		$data[ $key ] = is_string( $v ) ? mb_substr( $v, 0, 5000 ) : $v;
	}
	// Πολλοί σύνδεσμοι = spam.
	if ( preg_match_all( '#https?://#i', implode( ' ', array_filter( $data, 'is_string' ) ) ) > 4 ) {
		return ellada_cv3_fail( 'Το μήνυμα έχει πολλούς συνδέσμους.' );
	}

	// Φωτογραφία (μόνο στη συνταγή).
	$files = $req->get_file_params();
	$photo = 'syntagi' === $topic && ! empty( $files['photo']['tmp_name'] ) ? $files['photo'] : null;
	if ( $photo ) {
		if ( (int) $photo['size'] > 8 * MB_IN_BYTES || ! empty( $photo['error'] ) ) {
			return ellada_cv3_fail( 'Η φωτογραφία πρέπει να είναι έως 8 MB.' );
		}
		$check = wp_check_filetype_and_ext( $photo['tmp_name'], $photo['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
		if ( empty( $check['type'] ) || ! @getimagesize( $photo['tmp_name'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return ellada_cv3_fail( 'Η φωτογραφία πρέπει να είναι JPG, PNG ή WebP.' );
		}
		if ( empty( $data['photo_own'] ) ) {
			return ellada_cv3_fail( 'Επιβεβαιώστε ότι η φωτογραφία είναι δική σας.' );
		}
	}

	set_transient( $rk, $cnt + 1, HOUR_IN_SECONDS );

	/* 1. Μήνυμα (ιδιωτικό) */
	$titles = array(
		'syntagi'    => 'Συνταγή: ' . ( $data['dish'] ?? '' ),
		'topos'      => 'Τόπος: ' . ( $data['pname'] ?? '' ),
		'lathos'     => 'Διόρθωση: ' . ( $data['page'] ?? '' ),
		'synergasia' => 'Συνεργασία: ' . $name,
		'prosvasimotita' => 'Προσβασιμότητα: ' . mb_substr( (string) ( $data['problem'] ?? '' ), 0, 60 ),
		'allo'       => 'Μήνυμα: ' . $name,
	);
	$title = mb_substr( $titles[ $topic ], 0, 140 );
	$inbox = wp_insert_post(
		array(
			'post_type'   => ELLADA_INBOX_CPT,
			'post_status' => 'private',
			'post_title'  => $title,
		),
		true
	);
	if ( is_wp_error( $inbox ) ) {
		return ellada_cv3_fail( 'Δεν αποθηκεύτηκε το μήνυμα.', 500 );
	}

	if ( $photo ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$_FILES['photo'] = $photo; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$aid             = media_handle_upload( 'photo', $inbox, array( 'post_title' => 'Πρόταση: ' . ( $data['dish'] ?? '' ) ), array( 'test_form' => false ) );
		if ( ! is_wp_error( $aid ) ) {
			$data['photo_id'] = (int) $aid;
		}
	}
	update_post_meta( $inbox, '_ellada_submission', $data );
	update_post_meta( $inbox, '_ellada_unread', 1 );

	/* 2. Πρόχειρο (συνταγή ή σημείο) */
	$draft = 0;
	if ( 'syntagi' === $topic && post_type_exists( 'ellada_recipe' ) ) {
		$draft = wp_insert_post( array( 'post_type' => 'ellada_recipe', 'post_status' => 'draft', 'post_title' => $data['dish'] ) );
	} elseif ( 'topos' === $topic && post_type_exists( 'ellada_point' ) ) {
		$draft = wp_insert_post( array( 'post_type' => 'ellada_point', 'post_status' => 'draft', 'post_title' => $data['pname'] ) );
	}
	if ( $draft && ! is_wp_error( $draft ) ) {
		update_post_meta( $draft, '_ellada_inbox_id', $inbox );
		update_post_meta( $inbox, '_ellada_draft_id', $draft );
		if ( 'syntagi' === $topic ) {
			if ( ! empty( $data['story'] ) && function_exists( 'update_field' ) ) {
				update_field( 'story', $data['story'], $draft );
			}
			if ( ! empty( $data['photo_id'] ) ) {
				set_post_thumbnail( $draft, $data['photo_id'] );
			}
		} elseif ( function_exists( 'update_field' ) && ! empty( $data['why'] ) ) {
			update_field( 'short_description', mb_substr( $data['why'], 0, 300 ), $draft );
		}
	} else {
		$draft = 0;
	}
	if ( ! empty( $data['user'] ) ) {
		update_post_meta( $inbox, '_ellada_user', (int) $data['user'] );
		if ( $draft && ! empty( $data['credit'] ) ) {
			update_post_meta( $draft, '_ellada_credit', $name );
		}
	}

	/* 3. Email στο info@ */
	$lines = array( 'Θέμα: ' . $topics[ $topic ][0], 'Όνομα: ' . $name, 'Email: ' . $email, '' );
	foreach ( ellada_cv3_fields()[ $topic ] as $key => $f ) {
		if ( 'file' === $f[1] ) {
			$lines[] = $f[0] . ': ' . ( ! empty( $data['photo_id'] ) ? wp_get_attachment_url( $data['photo_id'] ) : '—' );
			continue;
		}
		$v       = $data[ $key ] ?? '';
		$lines[] = $f[0] . ': ' . ( 'check' === $f[1] ? ( $v ? 'Ναι' : 'Όχι' ) : ( '' !== (string) $v ? ( false !== strpos( (string) $v, "\n" ) ? "\n" . $v : $v ) : '—' ) );
	}
	if ( $data['from_page'] ) {
		$lines[] = 'Από τη σελίδα: ' . $data['from_page'];
	}
	$lines[] = '';
	$lines[] = 'Στο admin: ' . admin_url( 'post.php?post=' . $inbox . '&action=edit' );
	if ( $draft ) {
		$lines[] = 'Πρόχειρο: ' . admin_url( 'post.php?post=' . $draft . '&action=edit' );
	}
	$sent = wp_mail(
		'info@elladastopiato.gr',
		'[Ελλάδα στο Πιάτο] ' . $title,
		implode( "\n", $lines ),
		array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' )
	);
	update_post_meta( $inbox, '_ellada_mailed', $sent ? 1 : 0 );

	$texts = array(
		'syntagi'    => 'Η συνταγή σας έφτασε! Θα τη δοκιμάσουμε, θα τη γράψουμε στο ύφος του site και θα σας ενημερώσουμε πριν δημοσιευτεί.',
		'topos'      => 'Ο τόπος σας έφτασε! Θα τον ελέγξουμε και θα σας ενημερώσουμε όταν μπει στον οδηγό.',
		'lathos'     => 'Ευχαριστούμε που μας το είπατε! Θα το ελέγξουμε και θα το διορθώσουμε.',
		'synergasia' => 'Η πρότασή σας έφτασε! Θα σας απαντήσουμε σύντομα στο email σας.',
		'prosvasimotita' => 'Ευχαριστούμε που μας το είπατε! Θα το εξετάσουμε άμεσα και θα σας απαντήσουμε.',
		'allo'       => 'Το μήνυμά σας έφτασε. Θα σας απαντήσουμε σύντομα στο email σας.',
	);
	return new WP_REST_Response( array( 'ok' => true, 'text' => $texts[ $topic ] ), 200 );
}

/* ═════════════════════ Απόρρητο: 12 μήνες ═════════════════════ */

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'ellada_inbox_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ellada_inbox_cleanup' );
		}
	}
);
add_action(
	'ellada_inbox_cleanup',
	function () {
		$old = get_posts(
			array(
				'post_type'   => ELLADA_INBOX_CPT,
				'post_status' => 'any',
				'numberposts' => 200,
				'fields'      => 'ids',
				'date_query'  => array( array( 'before' => '12 months ago' ) ),
			)
		);
		foreach ( $old as $id ) {
			$draft = (int) get_post_meta( $id, '_ellada_draft_id', true );
			if ( $draft ) {
				delete_post_meta( $draft, '_ellada_inbox_id' );
			}
			wp_delete_post( $id, true ); // η φωτογραφία μένει στη βιβλιοθήκη (ο αποστολέας δήλωσε ότι μπορεί να χρησιμοποιηθεί)
		}
	}
);
