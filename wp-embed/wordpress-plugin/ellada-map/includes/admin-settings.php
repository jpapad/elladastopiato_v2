<?php
/**
 * Ρυθμίσεις → Χάρτης Ελλάδας
 *
 * Προεπιλογές του χάρτη, σύνοψη περιεχομένου και οδηγίες χρήσης.
 * Αποθηκεύει μόνο την επιλογή ellada_map_settings· δεν αγγίζει περιεχόμενο ή πεδία ACF.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_MAP_PAGE = 'ellada-map';

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Χάρτης Ελλάδας', 'Χάρτης Ελλάδας', 'manage_options', ELLADA_MAP_PAGE, 'ellada_map_settings_page' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( ELLADA_MAP_FILE ),
	function ( $links ) {
		array_unshift( $links, sprintf( '<a href="%s">Ρυθμίσεις</a>', esc_url( admin_url( 'options-general.php?page=' . ELLADA_MAP_PAGE ) ) ) );
		return $links;
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'ellada_map',
			ELLADA_MAP_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'ellada_map_sanitize_settings',
				'default'           => ellada_map_defaults(),
			)
		);
	}
);

function ellada_map_sanitize_settings( $input ) {
	$input  = is_array( $input ) ? $input : array();
	$height = isset( $input['height'] ) ? absint( $input['height'] ) : 600;
	return array(
		'title'       => isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '',
		'height'      => min( 1600, max( 300, $height ? $height : 600 ) ),
		'settlements' => ! empty( $input['settlements'] ),
		'accent'      => isset( $input['accent'] ) ? (string) sanitize_hex_color( $input['accent'] ) : '',
	);
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'settings_page_' . ELLADA_MAP_PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){'
			. '$(".ellada-color").wpColorPicker();'
			. '$(".ellada-copy").on("click",function(){var b=$(this),t=b.data("copy");'
			. 'navigator.clipboard.writeText(t).then(function(){var o=b.text();b.text("Αντιγράφηκε ✓");setTimeout(function(){b.text(o)},1500)});});'
			. '});'
		);
	}
);

/** Πλήθος εγγραφών ανά κατάσταση για ένα post type. */
function ellada_map_count( $post_type ) {
	if ( ! post_type_exists( $post_type ) ) {
		return null;
	}
	$c = wp_count_posts( $post_type );
	return array(
		'publish' => (int) $c->publish,
		'draft'   => (int) $c->draft + (int) $c->pending + (int) $c->future,
	);
}

/** Πλήθος δημοσιευμένων τοποθεσιών ανά επίπεδο χάρτη (meta map_level του ACF). */
function ellada_map_level_counts() {
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT pm.meta_value AS level, COUNT(*) AS n
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'
			 GROUP BY pm.meta_value",
			'map_level',
			'ellada_location'
		)
	);
	$out = array(
		'region'       => 0,
		'prefecture'   => 0,
		'municipality' => 0,
	);
	foreach ( (array) $rows as $r ) {
		if ( isset( $out[ $r->level ] ) ) {
			$out[ $r->level ] = (int) $r->n;
		}
	}
	return $out;
}

function ellada_map_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s         = ellada_map_settings();
	$locations = ellada_map_count( 'ellada_location' );
	$points    = ellada_map_count( 'ellada_point' );
	$levels    = ellada_map_level_counts();
	$acf_ok    = function_exists( 'acf_get_field_groups' );
	$elementor = did_action( 'elementor/loaded' );

	$examples = array(
		'[ellada_map]'                                       => 'Με τις ρυθμίσεις αυτής της σελίδας',
		'[ellada_map title="Γαστρονομικός χάρτης"]'          => 'Με δικό του τίτλο',
		'[ellada_map height="750" settlements="true"]'       => 'Πιο ψηλός, με οικισμούς',
		'[ellada_map accent="#2f7d4f"]'                      => 'Με άλλο χρώμα τονισμού',
	);
	?>
	<div class="wrap">
		<h1>Χάρτης Ελλάδας</h1>

		<?php if ( ! $acf_ok || null === $locations || null === $points ) : ?>
			<div class="notice notice-warning"><p>
				Δεν βρέθηκαν όλα όσα χρειάζεται ο χάρτης.
				<?php echo $acf_ok ? '' : ' Το ACF Pro δεν είναι ενεργό.'; ?>
				<?php echo null === $locations ? ' Λείπει ο τύπος «Τοποθεσίες» (ellada_location).' : ''; ?>
				<?php echo null === $points ? ' Λείπει ο τύπος «Τοπικά Σημεία» (ellada_point).' : ''; ?>
				Εισάγετε το <code>acf-import.json</code> του plugin από ACF → Εργαλεία.
			</p></div>
		<?php endif; ?>

		<style>
			.ellada-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin:16px 0 24px;max-width:960px}
			.ellada-card{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px 18px}
			.ellada-card h3{margin:0 0 6px;font-size:13px;color:#50575e;font-weight:500}
			.ellada-card .num{font-size:28px;font-weight:600;line-height:1.2}
			.ellada-card .sub{color:#646970;margin:4px 0 10px}
			.ellada-card a{margin-right:10px}
			.ellada-howto{max-width:960px}
			.ellada-howto td code{font-size:13px}
		</style>

		<div class="ellada-cards">
			<?php if ( null !== $locations ) : ?>
			<div class="ellada-card">
				<h3>Τοποθεσίες</h3>
				<div class="num"><?php echo esc_html( $locations['publish'] ); ?></div>
				<div class="sub">
					δημοσιευμένες<?php echo $locations['draft'] ? esc_html( ' · ' . $locations['draft'] . ' πρόχειρες' ) : ''; ?><br>
					<?php echo esc_html( sprintf( '%d περιφέρειες · %d νομοί · %d δήμοι', $levels['region'], $levels['prefecture'], $levels['municipality'] ) ); ?>
				</div>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ellada_location' ) ); ?>">Όλες</a>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ellada_location' ) ); ?>">+ Νέα</a>
			</div>
			<?php endif; ?>
			<?php if ( null !== $points ) : ?>
			<div class="ellada-card">
				<h3>Τοπικά Σημεία</h3>
				<div class="num"><?php echo esc_html( $points['publish'] ); ?></div>
				<div class="sub">
					δημοσιευμένα<?php echo $points['draft'] ? esc_html( ' · ' . $points['draft'] . ' πρόχειρα' ) : ''; ?><br>
					Μπαίνουν στον χάρτη αυτόματα από τις συντεταγμένες.
				</div>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ellada_point' ) ); ?>">Όλα</a>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ellada_point' ) ); ?>">+ Νέο</a>
			</div>
			<?php endif; ?>
			<div class="ellada-card">
				<h3>Καλό να ξέρετε</h3>
				<div class="sub">
					Ο χάρτης δείχνει μόνο <strong>δημοσιευμένες</strong> εγγραφές.
					Οι περιοχές με περιεχόμενο φαίνονται πιο έντονες.
				</div>
			</div>
		</div>

		<h2>Προεπιλογές</h2>
		<p>Ισχύουν σε κάθε χάρτη, εκτός αν το widget ή το shortcode ορίζει δική του τιμή.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'ellada_map' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ellada-title">Τίτλος</label></th>
					<td>
						<input id="ellada-title" class="regular-text" type="text" name="<?php echo esc_attr( ELLADA_MAP_OPTION ); ?>[title]" value="<?php echo esc_attr( $s['title'] ); ?>" placeholder="Εξερεύνηση">
						<p class="description">Εμφανίζεται πάνω αριστερά στον χάρτη. Κενό = «Εξερεύνηση».</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ellada-height">Ύψος</label></th>
					<td>
						<input id="ellada-height" class="small-text" type="number" min="300" max="1600" step="10" name="<?php echo esc_attr( ELLADA_MAP_OPTION ); ?>[height]" value="<?php echo esc_attr( $s['height'] ); ?>"> px
						<p class="description">Ελάχιστο ύψος του χάρτη (300–1600).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Οικισμοί</th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( ELLADA_MAP_OPTION ); ?>[settlements]" value="1" <?php checked( $s['settlements'] ); ?>>
							Εμφάνιση οικισμών όταν ο επισκέπτης ανοίγει έναν δήμο
						</label>
						<p class="description">Φορτώνει ~460 KB επιπλέον, μόνο όταν χρειαστεί.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ellada-accent">Χρώμα τονισμού</label></th>
					<td>
						<input id="ellada-accent" class="ellada-color" type="text" name="<?php echo esc_attr( ELLADA_MAP_OPTION ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" data-default-color="#f97316">
						<p class="description">Πινέζες, επιλεγμένες περιοχές και κουμπιά. Κενό = το αρχικό πορτοκαλί.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Αποθήκευση' ); ?>
		</form>

		<h2>Πώς τον βάζω σε σελίδα</h2>
		<div class="ellada-howto">
			<?php if ( $elementor ) : ?>
				<p><strong>Elementor:</strong> αναζητήστε το widget <strong>«Χάρτης Ελλάδας»</strong> και σύρετέ το στη σελίδα.
				Τίτλο, ύψος και οικισμούς τα αλλάζετε στην καρτέλα <em>Περιεχόμενο</em>, το χρώμα στην καρτέλα <em>Στυλ</em>
				(μπορείτε να διαλέξετε και καθολικό χρώμα του site).</p>
			<?php endif; ?>
			<p><strong>Shortcode</strong> (σε οποιαδήποτε σελίδα, block ή widget κειμένου):</p>
			<table class="widefat striped">
				<tbody>
				<?php foreach ( $examples as $code => $label ) : ?>
					<tr>
						<td><code><?php echo esc_html( $code ); ?></code></td>
						<td><?php echo esc_html( $label ); ?></td>
						<td style="text-align:right"><button type="button" class="button button-small ellada-copy" data-copy="<?php echo esc_attr( $code ); ?>">Αντιγραφή</button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p style="margin-top:24px;color:#646970">Έκδοση plugin <?php echo esc_html( ELLADA_MAP_VERSION ); ?></p>
	</div>
	<?php
}
