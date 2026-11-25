<?php
/**
 * Εργαλεία της σελίδας συνταγής και γρήγορη συμπλήρωση στο διαχειριστικό.
 *
 * Στη σελίδα της συνταγής (όταν είναι ενεργή η λειτουργία «recipe_tools»):
 *   - μπάρα κάτω από τα στοιχεία: «Στη συνταγή», Εκτύπωση, Κοινοποίηση
 *   - μερίδες με + / −, που ξαναϋπολογίζουν τις ποσότητες
 *   - υλικά και βήματα που τσεκάρονται (μένουν σημειωμένα στη συσκευή)
 *   - «Η συνταγή στον χάρτη»: η περιφέρεια της συνταγής σε μικρό χάρτη
 *   - καθαρή εκτύπωση (χωρίς κεφαλίδα, χάρτη, κάρτες)
 *
 * Οι λειτουργίες ανοίγουν από την επιλογή ellada_features. Ο διαχειριστής τις βλέπει νωρίτερα με ?ellada_preview=1.
 */

defined( 'ABSPATH' ) || exit;

/* ═════════════════════ Διακόπτες λειτουργιών ═════════════════════ */

function ellada_feature( $name ) {
	$on = (array) get_option( 'ellada_features', array() );
	if ( ! empty( $on[ $name ] ) ) {
		return true;
	}
	// Προεπισκόπηση για τον διαχειριστή πριν μπει live.
	return isset( $_GET['ellada_preview'] ) && is_user_logged_in() && current_user_can( 'edit_posts' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

function ellada_recipe_tools_assets() {
	ellada_front_style();
	if ( ! wp_script_is( 'ellada-recipe', 'registered' ) ) {
		$path = ELLADA_MAP_DIR . 'public/recipe.js';
		wp_register_script( 'ellada-recipe', plugins_url( 'public/recipe.js', ELLADA_MAP_FILE ), array(), file_exists( $path ) ? (string) filemtime( $path ) : ELLADA_MAP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
	wp_enqueue_script( 'ellada-recipe' );
}

/* ═════════════════════ Μπάρα ενεργειών ═════════════════════ */

function ellada_recipe_actions_html( $id ) {
	$url   = get_permalink( $id );
	$title = get_the_title( $id );
	$img   = get_the_post_thumbnail_url( $id, 'large' );
	$share = array(
		'WhatsApp'  => 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ),
		'Facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ),
		'Pinterest' => 'https://pinterest.com/pin/create/button/?url=' . rawurlencode( $url ) . '&description=' . rawurlencode( $title ) . ( $img ? '&media=' . rawurlencode( $img ) : '' ),
		'Email'     => 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ),
	);
	$links = '';
	foreach ( $share as $label => $href ) {
		$links .= sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $href, array( 'https', 'mailto' ) ), esc_html( $label ) );
	}
	$links .= '<button type="button" data-ellada-copy>Αντιγραφή συνδέσμου</button>';
	if ( function_exists( 'ellada_share2' ) && ellada_share2() ) {
		$links = ellada_share2_menu( $id, $url );
	}
	return sprintf(
		'<div class="ellada-ractions" data-url="%1$s" data-title="%2$s">
			<a class="ellada-ractions__btn is-primary" href="#ylika"><span aria-hidden="true">↓</span> Στη συνταγή</a>
			%5$s<button type="button" class="ellada-ractions__btn" data-ellada-print><span aria-hidden="true">⎙</span> Εκτύπωση</button>
			<div class="ellada-share">
				<button type="button" class="ellada-ractions__btn" data-ellada-share aria-expanded="false" aria-controls="ellada-share-%3$d"><span aria-hidden="true">↗</span> Κοινοποίηση</button>
				<div class="ellada-share__menu" id="ellada-share-%3$d" hidden>%4$s</div>
			</div>
		</div>',
		esc_url( $url ),
		esc_attr( $title ),
		$id,
		$links,
		( function_exists( 'ellada_recipe_v2' ) && ellada_recipe_v2()
			? '<button type="button" class="ellada-ractions__btn is-cook" data-ellada-cook aria-pressed="false"><span aria-hidden="true">◉</span> <span data-cook-label>Μαγειρεύω τώρα</span></button>'
			: '') . apply_filters( 'ellada_recipe_actions_extra', '', $id )
	);
}

/* ═════════════════════ Η συνταγή στον χάρτη ═════════════════════ */

function ellada_recipe_area_html( $id ) {
	$loc = (int) get_post_meta( $id, 'location', true );
	if ( ! $loc || ! function_exists( 'ellada_location_area' ) ) {
		return '';
	}
	$area   = ellada_location_area( $loc );
	$region = isset( $area['region'] ) ? (string) $area['region'] : '';
	if ( ! $region ) {
		return '';
	}
	$page  = function_exists( 'ellada_region_page' ) ? ellada_region_page( $region ) : 0;
	$n     = function_exists( 'ellada_region_recipe_count' ) ? ellada_region_recipe_count( $region ) : 0;
	$more  = $n > 1
		? sprintf( '<a class="ellada-rarea__link" href="%s">Όλες οι συνταγές (%d) <span aria-hidden="true">→</span></a>', esc_url( add_query_arg( 'perioxi', rawurlencode( $region ), get_post_type_archive_link( 'ellada_recipe' ) ) ), $n )
		: '';
	$guide = $page ? sprintf( '<a class="ellada-rarea__link" href="%s">Ο οδηγός της περιοχής <span aria-hidden="true">→</span></a>', esc_url( get_permalink( $page ) ) ) : '';
	$place = 'publish' === get_post_status( $loc ) ? get_the_title( $loc ) : '';
	return sprintf(
		'<section class="ellada-rarea"><div class="ellada-rarea__head"><p class="ellada-sh__eyebrow">Η συνταγή στον χάρτη</p><h2 class="ellada-rarea__title">%s</h2>%s<div class="ellada-rarea__links">%s%s</div></div><div class="ellada-rarea__map">%s</div></section>',
		esc_html( $region ),
		$place && $place !== $region ? '<p class="ellada-muted">' . esc_html( $place ) . '</p>' : '',
		$guide,
		$more,
		ellada_map_render(
			array(
				'start_region' => $region,
				'height'       => '380',
			)
		)
	);
}

/* Τα δύο κομμάτια μπαίνουν στα υπάρχοντα shortcodes, χωρίς αλλαγή στο template του Elementor. */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( ! in_array( $tag, array( 'ellada_recipe_facts', 'ellada_recipe_extras' ), true ) || ! ellada_feature( 'recipe_tools' ) ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		ellada_recipe_tools_assets();
		if ( 'ellada_recipe_facts' === $tag ) {
			return $output . ellada_recipe_actions_html( $id );
		}
		// Νέα σελίδα συνταγής: παραλλαγές + στατικός «τόπος» αντί για τον διαδραστικό χάρτη.
		if ( function_exists( 'ellada_recipe_v2' ) && ellada_recipe_v2() ) {
			return $output . ellada_recipe_variations_html( $id ) . ellada_recipe_place_html( $id );
		}
		return $output . ellada_recipe_area_html( $id );
	},
	10,
	3
);

/* Στην εκτύπωση φαίνεται και η διεύθυνση της σελίδας. */
add_action(
	'wp_footer',
	function () {
		if ( is_singular( 'ellada_recipe' ) && ellada_feature( 'recipe_tools' ) ) {
			printf( '<p class="ellada-print-source">%s · %s</p>', esc_html( get_bloginfo( 'name' ) ), esc_html( get_permalink() ) );
		}
	}
);

/* ═════════════════════ Γρήγορη συμπλήρωση (διαχειριστικό) ═════════════════════ */

add_action(
	'add_meta_boxes_ellada_recipe',
	function () {
		add_meta_box( 'ellada-quick', 'Γρήγορη συμπλήρωση: επικόλληση υλικών και βημάτων', 'ellada_quick_box', 'ellada_recipe', 'normal', 'high' );
	}
);

function ellada_quick_box() {
	wp_nonce_field( 'ellada_quick', 'ellada_quick_nonce' );
	do_action( 'ellada_quick_box_top' );
	?>
	<p>Επικολλήστε τη συνταγή όπως είναι γραμμένη. Με την αποθήκευση, τα υλικά και τα βήματα μπαίνουν στα αντίστοιχα πεδία πιο κάτω.</p>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px">
		<p><label for="ellada_quick_ing"><strong>Υλικά</strong>: ένα σε κάθε γραμμή</label>
			<textarea id="ellada_quick_ing" name="ellada_quick_ing" rows="10" style="width:100%" placeholder="500 γρ. φέτα&#10;2 κ.σ. ελαιόλαδο&#10;3 αυγά | χτυπημένα&#10;&#10;Για τη σάλτσα:&#10;400 γρ. ντομάτα κονκασέ"></textarea>
			<span class="description">Γραμμή που τελειώνει σε «:» γίνεται ομάδα υλικών. Με «|» προσθέτετε σημείωση.</span></p>
		<p><label for="ellada_quick_steps"><strong>Βήματα</strong>: ένα σε κάθε παράγραφο ή γραμμή</label>
			<textarea id="ellada_quick_steps" name="ellada_quick_steps" rows="10" style="width:100%" placeholder="1. Βράζουμε τα μακαρόνια...&#10;2. Σε ένα μπολ χτυπάμε τα αυγά..."></textarea>
			<span class="description">Η αρίθμηση («1.», «2)») σβήνεται μόνη της.</span></p>
	</div>
	<p><label><input type="checkbox" name="ellada_quick_replace" value="1"> Αντικατάσταση όσων υπάρχουν ήδη (αλλιώς προστίθενται στο τέλος)</label></p>
	<?php
}

/** Μονάδες που αναγνωρίζονται μετά την ποσότητα («500 γρ. φέτα» → 500 γρ. | φέτα). */
function ellada_quick_units() {
	return 'γρ\.?|γραμμάρια|γραμ\.?|gr\.?|g|kg|κιλ[όάα]\.?|κ\.?|ml|λίτρ[οα]|lt\.?|l|κ\.\s?σ\.?|κ\.\s?γ\.?|κουταλι[άέ]ς?(?:\s+τ(?:ης|ου)\s+(?:σούπας|γλυκού))?|κουταλάκι(?:α)?(?:\s+του\s+γλυκού)?|φλιτζάνι(?:α)?|φλ\.?|ποτήρι(?:α)?|σκελίδ(?:α|ες)|σκ\.?|ματσάκι(?:α)?|ματσ\.?|πρέζα|πρέζες|τεμ\.?|τεμάχια|φακελάκι(?:α)?|κονσέρβα|κουτί|συσκ\.?|φύλλα|φέτες';
}

function ellada_quick_parse_ingredients( $text ) {
	$rows  = array();
	$units = ellada_quick_units();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( preg_replace( '/^[\-–•*·]\s*/u', '', trim( $line ) ) );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/^(.+?):$/u', $line, $m ) ) {
			$rows[] = array( 'section' => trim( $m[1] ), 'quantity' => '', 'item' => '', 'note' => '' );
			continue;
		}
		$note = '';
		if ( false !== strpos( $line, '|' ) ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) >= 3 ) {
				$rows[] = array( 'section' => '', 'quantity' => $parts[0], 'item' => $parts[1], 'note' => implode( ' ', array_slice( $parts, 2 ) ) );
				continue;
			}
			$line = $parts[0];
			$note = $parts[1];
		}
		$qty  = '';
		$item = $line;
		// Ποσότητα: αριθμός, κλάσμα ή εύρος, προαιρετικά με μονάδα.
		if ( preg_match( '/^((?:\d+(?:[.,]\d+)?|\d+\/\d+|[½¼¾⅓⅔])(?:\s*[½¼¾]|\s+\d\/\d)?(?:\s*[-–]\s*(?:\d+(?:[.,]\d+)?|\d\/\d))?(?:\s*(?:' . $units . ')(?=\s|$))?)\s+(.+)$/u', $line, $m ) ) {
			$qty  = trim( $m[1] );
			$item = trim( $m[2] );
		}
		$rows[] = array( 'section' => '', 'quantity' => $qty, 'item' => $item, 'note' => $note );
	}
	return $rows;
}

function ellada_quick_parse_steps( $text ) {
	$text   = str_replace( array( "\r\n", "\r" ), "\n", trim( (string) $text ) );
	$marker = '(?:βήμα\s*)?\d+\s*[.)\-–:]';
	if ( preg_match_all( '/^\s*' . $marker . '/mu', $text ) >= 2 ) {
		// Αριθμημένα βήματα: οι γραμμές χωρίς αριθμό ανήκουν στο προηγούμενο βήμα.
		$blocks = preg_split( '/\n(?=\s*' . $marker . ')/iu', $text );
	} elseif ( preg_match( '/\n\s*\n/', $text ) ) {
		$blocks = preg_split( '/\n\s*\n/', $text );
	} else {
		$blocks = explode( "\n", $text );
	}
	$rows   = array();
	foreach ( $blocks as $b ) {
		$b = trim( preg_replace( '/^(?:βήμα\s*)?\d+\s*[.)\-–:]\s*/iu', '', trim( $b ) ) );
		if ( '' !== $b ) {
			$rows[] = array( 'text' => preg_replace( '/\s*\n\s*/', ' ', $b ), 'image' => '' );
		}
	}
	return $rows;
}

/* Μετά την αποθήκευση των πεδίων του ACF (προτεραιότητα 20), ώστε να μην ξαναγραφτούν από τη φόρμα. */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( ! is_numeric( $post_id ) || 'ellada_recipe' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ellada_quick_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ellada_quick_nonce'] ), 'ellada_quick' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$replace = ! empty( $_POST['ellada_quick_replace'] );
		$fields  = array(
			'ingredients' => 'ellada_quick_parse_ingredients',
			'steps'       => 'ellada_quick_parse_steps',
		);
		foreach ( $fields as $field => $parser ) {
			$raw = isset( $_POST[ 'ellada_quick_' . ( 'steps' === $field ? 'steps' : 'ing' ) ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'ellada_quick_' . ( 'steps' === $field ? 'steps' : 'ing' ) ] ) ) : '';
			if ( '' === trim( $raw ) ) {
				continue;
			}
			$rows = call_user_func( $parser, $raw );
			if ( ! $rows ) {
				continue;
			}
			if ( ! $replace ) {
				$old = array();
				foreach ( array_filter( (array) get_field( $field, $post_id ), 'is_array' ) as $r ) {
					if ( isset( $r['image'] ) && is_array( $r['image'] ) ) {
						$r['image'] = (int) ( $r['image']['ID'] ?? 0 );
					}
					$old[] = $r;
				}
				$rows = array_merge( $old, $rows );
			}
			update_field( $field, $rows, $post_id );
		}
	},
	20
);
