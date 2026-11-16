<?php
/**
 * Αποστολή email του site μέσω SMTP (Hostinger) με τον λογαριασμό info@.
 *
 * Χωρίς SMTP το WordPress στέλνει από τον web server ως wordpress@…, χωρίς DKIM και εκτός SPF,
 * οπότε τα μηνύματα (π.χ. της φόρμας επικοινωνίας) πάνε στα spam.
 *
 * Ρύθμιση: Ρυθμίσεις → Email του site (κωδικός του mailbox). Εναλλακτικά, στο wp-config.php:
 *   define( 'ELLADA_SMTP_USER', 'info@elladastopiato.gr' );
 *   define( 'ELLADA_SMTP_PASS', '…' );
 * (οι σταθερές έχουν προτεραιότητα έναντι της ρύθμισης στη βάση).
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_SMTP_OPTION = 'ellada_smtp';
const ELLADA_SMTP_HOST   = 'smtp.hostinger.com';
const ELLADA_SMTP_PORT   = 465;

/** @return array{enabled:bool,user:string,pass:string,source:string} */
function ellada_smtp_config() {
	if ( defined( 'ELLADA_SMTP_USER' ) && defined( 'ELLADA_SMTP_PASS' ) && ELLADA_SMTP_USER && ELLADA_SMTP_PASS ) {
		return array(
			'enabled' => true,
			'user'    => (string) ELLADA_SMTP_USER,
			'pass'    => (string) ELLADA_SMTP_PASS,
			'source'  => 'wp-config',
		);
	}
	$o = wp_parse_args(
		(array) get_option( ELLADA_SMTP_OPTION, array() ),
		array(
			'enabled' => false,
			'user'    => 'info@elladastopiato.gr',
			'pass'    => '',
		)
	);
	return array(
		'enabled' => ! empty( $o['enabled'] ) && '' !== $o['pass'] && is_email( $o['user'] ),
		'user'    => (string) $o['user'],
		'pass'    => (string) $o['pass'],
		'source'  => 'option',
	);
}

add_action(
	'phpmailer_init',
	function ( $mailer ) {
		$c = ellada_smtp_config();
		if ( ! $c['enabled'] ) {
			return;
		}
		$mailer->isSMTP();
		$mailer->Host       = ELLADA_SMTP_HOST;
		$mailer->Port       = ELLADA_SMTP_PORT;
		$mailer->SMTPSecure = 'ssl';
		$mailer->SMTPAuth   = true;
		$mailer->Username   = $c['user'];
		$mailer->Password   = $c['pass'];
		$mailer->Timeout    = 15;
		// Ο Hostinger δέχεται αποστολή μόνο ως ο λογαριασμός που συνδέθηκε· το Reply-To (π.χ. ο επισκέπτης) μένει.
		$mailer->setFrom( $c['user'], wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), true );
	}
);

/** Τελευταίο σφάλμα αποστολής (για τη σελίδα ρυθμίσεων). */
add_action(
	'wp_mail_failed',
	function ( $error ) {
		update_option( 'ellada_smtp_last_error', array( 'time' => time(), 'message' => $error->get_error_message() ), false );
	}
);

/* ═════════════════════ Ρυθμίσεις → Email του site ═════════════════════ */

if ( is_admin() ) {
	add_action(
		'admin_menu',
		function () {
			add_options_page( 'Email του site', 'Email του site', 'manage_options', 'ellada-mail', 'ellada_smtp_page' );
		}
	);

	add_action(
		'admin_post_ellada_smtp_save',
		function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Δεν επιτρέπεται.' );
			}
			check_admin_referer( 'ellada_smtp_save' );
			$old  = (array) get_option( ELLADA_SMTP_OPTION, array() );
			$user = sanitize_email( wp_unslash( $_POST['user'] ?? '' ) );
			$pass = (string) wp_unslash( $_POST['pass'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- κωδικός, αποθηκεύεται αυτούσιος
			update_option(
				ELLADA_SMTP_OPTION,
				array(
					// Νέος κωδικός = ενεργοποίηση (αλλιώς εύκολα ξεχνιέται το κουτάκι).
					'enabled' => ! empty( $_POST['enabled'] ) || '' !== $pass,
					'user'    => $user ? $user : 'info@elladastopiato.gr',
					'pass'    => '' !== $pass ? $pass : ( $old['pass'] ?? '' ), // κενό = κράτα τον παλιό
				),
				false
			);
			wp_safe_redirect( admin_url( 'options-general.php?page=ellada-mail&saved=1' ) );
			exit;
		}
	);

	add_action(
		'admin_post_ellada_smtp_test',
		function () {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'Δεν επιτρέπεται.' );
			}
			check_admin_referer( 'ellada_smtp_test' );
			delete_option( 'ellada_smtp_last_error' );
			$c  = ellada_smtp_config();
			$ok = wp_mail(
				$c['user'],
				'Δοκιμή email από το elladastopiato.gr',
				"Αν διαβάζεις αυτό το μήνυμα στα εισερχόμενα (όχι στα spam), η αποστολή μέσω SMTP δουλεύει σωστά.\n\n— " . home_url( '/' )
			);
			wp_safe_redirect( admin_url( 'options-general.php?page=ellada-mail&test=' . ( $ok ? 'ok' : 'fail' ) ) );
			exit;
		}
	);
}

function ellada_smtp_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$c   = ellada_smtp_config();
	$o   = (array) get_option( ELLADA_SMTP_OPTION, array() );
	$err = get_option( 'ellada_smtp_last_error' );
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- μόνο μηνύματα κατάστασης
	$saved = isset( $_GET['saved'] );
	$test  = isset( $_GET['test'] ) ? sanitize_key( $_GET['test'] ) : '';
	// phpcs:enable
	?>
	<div class="wrap">
		<h1>Email του site</h1>
		<p>Τα email του site (φόρμα επικοινωνίας, ειδοποιήσεις WordPress) στέλνονται μέσω του mail server του Hostinger
			με τον λογαριασμό <strong>info@</strong>, ώστε να φτάνουν στα εισερχόμενα και όχι στα spam.</p>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success"><p>Αποθηκεύτηκε.</p></div>
		<?php endif; ?>
		<?php if ( 'ok' === $test ) : ?>
			<div class="notice notice-success"><p>Το δοκιμαστικό στάλθηκε στο <?php echo esc_html( $c['user'] ); ?>. Δες αν ήρθε στα εισερχόμενα.</p></div>
		<?php elseif ( 'fail' === $test ) : ?>
			<div class="notice notice-error"><p>Η αποστολή απέτυχε<?php echo $err ? ': ' . esc_html( $err['message'] ) : ''; ?></p></div>
		<?php endif; ?>

		<p><strong>Κατάσταση:</strong>
			<?php
			if ( $c['enabled'] ) {
				echo esc_html( 'Ενεργό — αποστολή ως ' . $c['user'] . ( 'wp-config' === $c['source'] ? ' (από το wp-config.php)' : '' ) );
			} else {
				echo 'Ανενεργό — το WordPress στέλνει με τον προεπιλεγμένο τρόπο (πιθανόν στα spam).';
			}
			?>
		</p>

		<?php if ( 'wp-config' !== $c['source'] ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ellada_smtp_save">
			<?php wp_nonce_field( 'ellada_smtp_save' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Ενεργό</th>
					<td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $o['enabled'] ) ); ?>> Αποστολή μέσω SMTP</label>
						<p class="description">Ενεργοποιείται αυτόματα όταν γράψεις νέο κωδικό. Ξετσέκαρέ το για να γυρίσεις στην προεπιλεγμένη αποστολή.</p></td></tr>
				<tr><th scope="row"><label for="ellada-smtp-user">Λογαριασμός</label></th>
					<td><input id="ellada-smtp-user" type="email" name="user" class="regular-text" value="<?php echo esc_attr( $o['user'] ?? 'info@elladastopiato.gr' ); ?>"></td></tr>
				<tr><th scope="row"><label for="ellada-smtp-pass">Κωδικός mailbox</label></th>
					<td><input id="ellada-smtp-pass" type="password" name="pass" class="regular-text" autocomplete="new-password" value=""
						placeholder="<?php echo ! empty( $o['pass'] ) ? 'αποθηκευμένος — άφησέ το κενό για να μείνει' : ''; ?>">
						<p class="description">Ο κωδικός του mailbox όπως ορίστηκε στο Hostinger (Emails). Δεν εμφανίζεται ξανά μετά την αποθήκευση.</p></td></tr>
				<tr><th scope="row">Server</th><td><code><?php echo esc_html( ELLADA_SMTP_HOST . ':' . ELLADA_SMTP_PORT ); ?></code> (SSL)</td></tr>
			</table>
			<?php submit_button( 'Αποθήκευση' ); ?>
		</form>
		<?php endif; ?>

		<h2>Δοκιμή</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ellada_smtp_test">
			<?php wp_nonce_field( 'ellada_smtp_test' ); ?>
			<?php submit_button( 'Αποστολή δοκιμαστικού στο ' . $c['user'], 'secondary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
