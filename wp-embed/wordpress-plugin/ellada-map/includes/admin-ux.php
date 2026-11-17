<?php
/**
 * Οθόνη επεξεργασίας για Συνταγές, Τοποθεσίες, Τοπικά σημεία, Συλλογές.
 *
 *   - απλή οθόνη (όχι Gutenberg): τα πεδία ACF αμέσως κάτω από τον τίτλο
 *   - οι συνταγές χωρίς το κουτί κειμένου (το περιεχόμενο είναι όλο σε πεδία)
 *   - οδηγία στον τίτλο ανά τύπο και παραδείγματα μέσα στα άδεια πεδία
 *   - «Τι λείπει» στο πλάι: τι είναι συμπληρωμένο (✓), τι λείπει (✗), τι είναι προαιρετικό (○)
 *
 * Αφορά μόνο το διαχειριστικό· η σελίδα που βλέπουν οι επισκέπτες δεν αλλάζει.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_ADMIN_TYPES = array( 'ellada_recipe', 'ellada_location', 'ellada_point', 'ellada_collection' );

add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $post_type ) {
		return in_array( $post_type, ELLADA_ADMIN_TYPES, true ) ? false : $use;
	},
	20,
	2
);

add_action(
	'init',
	function () {
		remove_post_type_support( 'ellada_recipe', 'editor' );
	},
	99
);

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		$titles = array(
			'ellada_recipe'     => 'Όνομα συνταγής (π.χ. Γεμιστά με ρύζι)',
			'ellada_location'   => 'Όνομα τόπου (π.χ. Χανιά)',
			'ellada_point'      => 'Όνομα σημείου (π.χ. Ταβέρνα Ο Μανώλης)',
			'ellada_collection' => 'Όνομα συλλογής (π.χ. Χριστουγεννιάτικο τραπέζι)',
		);
		return $titles[ $post->post_type ] ?? $text;
	},
	99, // μετά τη ρύθμιση «Title placeholder» των τύπων του ACF
	2
);

/* ═════════════════════ Παραδείγματα μέσα στα άδεια πεδία ═════════════════════ */

foreach (
	array(
		'dishes'            => "Ντάκος | Παξιμάδι κριθαριού με ντομάτα και μυζήθρα.\nΓαμοπίλαφο | Ρύζι σε ζωμό κρέατος, το φαγητό του γάμου.",
		'food_calendar'     => "Χειμώνας | Άγρια χόρτα και φρέσκο ελαιόλαδο.\nΆνοιξη | Τα φαγητά του Πάσχα.",
		'local_products'    => "Γραβιέρα ΠΟΠ | Σκληρό τυρί από πρόβειο γάλα\nΘυμαρίσιο μέλι | Από τα βουνά της περιοχής",
		'why_go'            => "Το ενετικό λιμάνι και η παλιά πόλη.\nΤα φαράγγια και τα βουνά.",
		'travel_tips'       => "Τα καλύτερα τραπέζια είναι στα ορεινά χωριά.\nΡωτήστε για το φαγητό της ημέρας.",
		'guide_intro'       => "Λίγες παράγραφοι για τον χαρακτήρα του τόπου και το τραπέζι του.\n\nΚενή γραμμή = νέα παράγραφος.",
		'how_to_get_there'  => 'Αεροδρόμια, λιμάνια, πώς κινείται κανείς στην περιοχή.',
		'story'             => 'Πού και πότε τρώγεται, από ποιον την έμαθες, τι σημαίνει για τον τόπο.',
		'variations'        => "Με φέτα αντί για μυζήθρα.\nΣυνοδεύεται με ρακή.",
		'short_description' => '1–2 προτάσεις που θα δει κανείς στις κάρτες και στο Google.',
	) as $name => $example
) {
	add_filter(
		'acf/load_field/name=' . $name,
		function ( $field ) use ( $example ) {
			if ( is_admin() && empty( $field['placeholder'] ) && in_array( $field['type'], array( 'textarea', 'text' ), true ) ) {
				$field['placeholder'] = $example;
			}
			return $field;
		}
	);
}

/* ═════════════════════ «Τι λείπει» ═════════════════════ */

/** @return array<int, array{0:string,1:string,2:string}> [κατάσταση yes|no|opt, ετικέτα, σημείωση] */
function ellada_admin_checklist( $post ) {
	$id   = $post->ID;
	$meta = fn( $k ) => trim( (string) get_post_meta( $id, $k, true ) );
	$rows = array();
	$add  = function ( $ok, $label, $note = '', $optional = false ) use ( &$rows ) {
		$rows[] = array( $ok ? 'yes' : ( $optional ? 'opt' : 'no' ), $label, $note );
	};
	$count = function ( $field ) use ( $id ) {
		$v = get_post_meta( $id, $field, true );
		return is_numeric( $v ) ? (int) $v : ( is_array( $v ) ? count( $v ) : 0 );
	};

	if ( 'ellada_recipe' === $post->post_type ) {
		$cats = get_the_terms( $id, 'recipe_category' );
		$ing  = $count( 'ingredients' );
		$st   = $count( 'steps' );
		$add( has_post_thumbnail( $id ), 'Φωτογραφία', 'Κεντρική φωτογραφία' );
		$add( (bool) get_post_meta( $id, 'location', true ), 'Περιοχή' );
		$add( is_array( $cats ) && $cats, 'Κατηγορία' );
		$add( '' !== $meta( 'short_description' ), 'Σύντομη περιγραφή' );
		$add( (int) $meta( 'prep_time' ) + (int) $meta( 'cook_time' ) > 0, 'Χρόνοι' );
		$add( (int) $meta( 'servings' ) > 0, 'Μερίδες' );
		$add( '' !== $meta( 'difficulty' ), 'Δυσκολία' );
		$add( $ing > 0, 'Υλικά', $ing ? (string) $ing : '' );
		$add( $st > 0, 'Βήματα', $st ? (string) $st : '' );
		$add( '' !== $meta( 'tips' ), 'Μυστικά επιτυχίας', '', true );
		$add( (bool) array_filter( (array) get_post_meta( $id, 'allergens', true ) ), 'Αλλεργιογόνα', '', true );
		$add( '' !== $meta( 'story' ), 'Η ιστορία της συνταγής', '', true );
		$add( '' !== $meta( 'variations' ), 'Παραλλαγές', '', true );
		$kind = $meta( 'recipe_kind' );
		$add( '' !== $kind, 'Είδος συνταγής', '', true );
		$add( '' !== $meta( 'sources' ), 'Πηγές', 'traditional' === $kind && '' === $meta( 'sources' ) ? 'απαραίτητες για «Παραδοσιακή»' : '', 'traditional' !== $kind );
		$occ = get_the_terms( $id, 'recipe_occasion' );
		$add( is_array( $occ ) && $occ, 'Περίσταση', is_array( $occ ) && $occ ? implode( ', ', wp_list_pluck( $occ, 'name' ) ) : 'γιορτή/εποχή → Συλλογές', true );
		if ( function_exists( 'ellada_fasting_hint' ) && ellada_fasting_hint( $id ) ) {
			$rows[] = array( 'no', 'Νηστίσιμη;', 'χωρίς ζωικά υλικά — «Νηστίσιμο» στη Διατροφή, αν ισχύει' );
		}
	} elseif ( 'ellada_location' === $post->post_type ) {
		$intro = '' !== $meta( 'guide_intro' );
		$add( '' !== $meta( 'map_level' ), 'Επίπεδο & θέση στον χάρτη' );
		$add( $intro, 'Εισαγωγή οδηγού', $intro ? '' : 'Χωρίς αυτήν δεν μπαίνει στο Google' );
		$add( '' !== $meta( 'why_go' ), 'Γιατί να πάτε' );
		$add( '' !== $meta( 'dishes' ), 'Τι να φάτε', '', true );
		$add( '' !== $meta( 'local_products' ), 'Τοπικά προϊόντα', '', true );
		$add( '' !== $meta( 'food_calendar' ) || '' !== $meta( 'best_time_to_visit' ), 'Πότε να πάτε', '', true );
		$add( '' !== $meta( 'how_to_get_there' ), 'Πώς θα έρθετε', '', true );
		$add( '' !== $meta( 'travel_tips' ), 'Συμβουλές', '', true );
		$add( (bool) $meta( 'cover_image' ), 'Φωτογραφία εξωφύλλου', '', true );
		$add( '' !== $meta( 'sources' ), 'Πηγές', '', true );
	} elseif ( 'ellada_point' === $post->post_type ) {
		$add( '' !== $meta( 'point_type' ), 'Τύπος σημείου' );
		$add( '' !== $meta( 'lat' ) && '' !== $meta( 'lng' ), 'Θέση στον χάρτη' );
		$add( '' !== $meta( 'short_description' ), 'Σύντομη περιγραφή' );
		$add( (bool) $meta( 'cover_image' ), 'Φωτογραφία', '', true );
		$add( '' !== $meta( 'external_url' ), 'Σύνδεσμος', '', true );
	} elseif ( 'ellada_collection' === $post->post_type ) {
		$n = function_exists( 'ellada_collection_recipe_ids' ) ? count( ellada_collection_recipe_ids( $id ) ) : count( array_filter( (array) get_post_meta( $id, 'recipes', true ) ) );
		$add( has_post_thumbnail( $id ), 'Φωτογραφία' );
		$add( '' !== $meta( 'short_description' ), 'Σύντομη περιγραφή' );
		$add( $n > 0, 'Συνταγές', $n ? (string) $n : '' );
	}
	return $rows;
}

add_action(
	'add_meta_boxes',
	function ( $post_type ) {
		if ( in_array( $post_type, ELLADA_ADMIN_TYPES, true ) ) {
			add_meta_box(
				'ellada-checklist',
				'Τι λείπει',
				function ( $post ) {
					$rows  = ellada_admin_checklist( $post );
					$icons = array( 'yes' => '✓', 'no' => '✗', 'opt' => '○' );
					$need  = count( array_filter( $rows, fn( $r ) => 'no' === $r[0] ) );
					echo '<p class="ellada-ck__sum">' . ( $need
						? esc_html( sprintf( 1 === $need ? 'Λείπει %d βασικό στοιχείο.' : 'Λείπουν %d βασικά στοιχεία.', $need ) )
						: 'Τα βασικά είναι έτοιμα.' ) . '</p><ul class="ellada-ck">';
					foreach ( $rows as list( $state, $label, $note ) ) {
						printf(
							'<li class="is-%1$s"><span aria-hidden="true">%2$s</span> %3$s%4$s</li>',
							esc_attr( $state ),
							esc_html( $icons[ $state ] ),
							esc_html( $label ),
							$note ? ' <em>' . esc_html( $note ) . '</em>' : ''
						);
					}
					echo '</ul><p class="ellada-ck__foot">○ = προαιρετικό · Ενημερώνεται με την αποθήκευση.</p>';
				},
				$post_type,
				'side',
				'high'
			);
		}
	}
);

add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, ELLADA_ADMIN_TYPES, true ) ) {
			return;
		}
		echo '<style>
			#ellada-checklist .ellada-ck__sum{margin:4px 0 10px;font-weight:600}
			#ellada-checklist .ellada-ck{margin:0}
			#ellada-checklist .ellada-ck li{display:flex;gap:6px;align-items:baseline;margin:0;padding:5px 0;border-bottom:1px solid #f0f0f1}
			#ellada-checklist .ellada-ck li span{width:14px;font-weight:700;text-align:center}
			#ellada-checklist .is-yes span{color:#2e7d32}
			#ellada-checklist .is-no{color:#b32d2e;font-weight:600}
			#ellada-checklist .is-opt{color:#787c82}
			#ellada-checklist em{margin-left:auto;font-style:normal;font-size:11px;color:#787c82;text-align:right}
			#ellada-checklist .ellada-ck__foot{margin:10px 0 0;font-size:11px;color:#787c82}
			.post-type-ellada_recipe #titlewrap #title, .post-type-ellada_location #titlewrap #title{font-size:1.6em}
		</style>';
	}
);
