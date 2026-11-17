<?php
/**
 * «Οι γεύσεις που χάνονται».
 *
 *   - Συνταγές και τοπικά σημεία (προϊόντα): «Γεύση που χάνεται» + μια φράση για το γιατί
 *   - Μαρτυρίες (νέος τύπος ellada_testimony): κείμενο, ποιος (όνομα + αρχικό), τόπος, χρονιά, συνταγές,
 *     και υποχρεωτικά η συγκατάθεση του ανθρώπου. Αργότερα: ηχητικό / βίντεο.
 *   - [ellada_lost]: η σελίδα της ενότητας (συνταγές, προϊόντα, μαρτυρίες, κάλεσμα)
 *   - Στη συνταγή: σήμα «Γεύση που χάνεται» και «Μαρτυρίες» πριν από την Εκτέλεση
 *
 * Όσα βλέπουν οι επισκέπτες: λειτουργία «lost» (προεπισκόπηση ?ellada_preview=1).
 */

defined( 'ABSPATH' ) || exit;

function ellada_lost() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'lost' );
}

/* ═════════════════════ Τύπος «Μαρτυρία» και πεδία ═════════════════════ */

add_action(
	'init',
	function () {
		register_post_type(
			'ellada_testimony',
			array(
				'labels'          => array(
					'name'          => 'Μαρτυρίες',
					'singular_name' => 'Μαρτυρία',
					'add_new'       => 'Νέα μαρτυρία',
					'add_new_item'  => 'Νέα μαρτυρία',
					'edit_item'     => 'Επεξεργασία μαρτυρίας',
					'all_items'     => 'Όλες οι μαρτυρίες',
					'menu_name'     => 'Μαρτυρίες',
				),
				'public'          => true,
				'show_in_rest'    => false, // απλή οθόνη, όχι Gutenberg
				'menu_icon'       => 'dashicons-format-quote',
				'menu_position'   => 26,
				'supports'        => array( 'title', 'thumbnail' ),
				'has_archive'     => false,
				'rewrite'         => array( 'slug' => 'martyries', 'with_front' => false ),
				'capability_type' => 'post',
			)
		);
	},
	5
);

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'      => 'group_ellada_testimony',
				'title'    => 'Μαρτυρία',
				'position' => 'acf_after_title',
				'style'    => 'seamless',
				'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'ellada_testimony' ) ) ),
				'fields'   => array(
					array( 'key' => 'field_ellada_tm_text', 'label' => 'Η μαρτυρία', 'name' => 'testimony', 'type' => 'textarea', 'rows' => 10, 'new_lines' => '', 'required' => 1, 'instructions' => 'Με τα λόγια του ανθρώπου, όσο γίνεται. Κενή γραμμή = νέα παράγραφος.', 'placeholder' => '«Τα Χριστούγεννα η μάνα μου έπιανε το ζυμάρι από βραδίς…»' ),
					array( 'key' => 'field_ellada_tm_person', 'label' => 'Ποιος μιλάει', 'name' => 'person', 'type' => 'text', 'required' => 1, 'instructions' => 'Όνομα και αρχικό επωνύμου, π.χ. «Ελένη Π.». Το πλήρες όνομα μόνο αν το θέλει ο ίδιος.', 'wrapper' => array( 'width' => '40' ) ),
					array( 'key' => 'field_ellada_tm_age', 'label' => 'Ηλικία', 'name' => 'age', 'type' => 'number', 'instructions' => 'Προαιρετικά.', 'wrapper' => array( 'width' => '20' ) ),
					array( 'key' => 'field_ellada_tm_year', 'label' => 'Χρονιά καταγραφής', 'name' => 'year', 'type' => 'number', 'wrapper' => array( 'width' => '20' ) ),
					array( 'key' => 'field_ellada_tm_place', 'label' => 'Τόπος', 'name' => 'place', 'type' => 'post_object', 'post_type' => array( 'ellada_location' ), 'return_format' => 'id', 'ui' => 1, 'allow_null' => 1, 'instructions' => 'Το χωριό / ο δήμος / ο νομός της μαρτυρίας.', 'wrapper' => array( 'width' => '50' ) ),
					array( 'key' => 'field_ellada_tm_place_text', 'label' => 'Χωριό (αν δεν υπάρχει στον οδηγό)', 'name' => 'place_text', 'type' => 'text', 'placeholder' => 'π.χ. Ανώγεια', 'wrapper' => array( 'width' => '50' ) ),
					array( 'key' => 'field_ellada_tm_recipes', 'label' => 'Σχετικές συνταγές', 'name' => 'recipes', 'type' => 'relationship', 'post_type' => array( 'ellada_recipe' ), 'filters' => array( 'search' ), 'return_format' => 'id', 'instructions' => 'Η μαρτυρία εμφανίζεται και στη σελίδα αυτών των συνταγών.' ),
					array( 'key' => 'field_ellada_tm_by', 'label' => 'Την κατέγραψε', 'name' => 'recorded_by', 'type' => 'text', 'instructions' => 'Προαιρετικά: ποιος μίλησε μαζί του.' ),
					array( 'key' => 'field_ellada_tm_consent', 'label' => 'Συγκατάθεση', 'name' => 'consent', 'type' => 'true_false', 'ui' => 1, 'required' => 1, 'message' => 'Ο άνθρωπος συμφώνησε να δημοσιευτεί η μαρτυρία με αυτό το όνομα.', 'instructions' => 'Χωρίς συγκατάθεση η μαρτυρία δεν εμφανίζεται πουθενά, ακόμα κι αν δημοσιευτεί.' ),
				),
			)
		);
		acf_add_local_field_group(
			array(
				'key'        => 'group_ellada_lost',
				'title'      => 'Οι γεύσεις που χάνονται',
				'position'   => 'side',
				'menu_order' => 5,
				'location'   => array(
					array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'ellada_recipe' ) ),
					array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'ellada_point' ) ),
				),
				'fields'     => array(
					array( 'key' => 'field_ellada_lost_on', 'label' => 'Γεύση που χάνεται', 'name' => 'endangered', 'type' => 'true_false', 'ui' => 1, 'message' => 'Σπάνια πια — μπαίνει στην ενότητα' ),
					array( 'key' => 'field_ellada_lost_note', 'label' => 'Γιατί χάνεται', 'name' => 'endangered_note', 'type' => 'textarea', 'rows' => 3, 'new_lines' => '', 'placeholder' => 'π.χ. Τη φτιάχνουν πια λίγες νοικοκυρές στα ορεινά χωριά.', 'conditional_logic' => array( array( array( 'field' => 'field_ellada_lost_on', 'operator' => '==', 'value' => '1' ) ) ) ),
				),
			)
		);
	}
);

add_filter(
	'enter_title_here',
	function ( $text, $post ) {
		return 'ellada_testimony' === $post->post_type ? 'Τίτλος (π.χ. «Τα Χριστούγεννα της γιαγιάς Ελένης»)' : $text;
	},
	99,
	2
);

/* ═════════════════════ Δεδομένα ═════════════════════ */

/** Μαρτυρίες με συγκατάθεση· προαιρετικά μόνο όσες αφορούν μια συνταγή. */
function ellada_testimonies( $recipe_id = 0, $limit = 30 ) {
	$mq = array( array( 'key' => 'consent', 'value' => '1' ) );
	if ( $recipe_id ) {
		$mq[] = array( 'key' => 'recipes', 'value' => '"' . (int) $recipe_id . '"', 'compare' => 'LIKE' );
	}
	return get_posts(
		array(
			'post_type'   => 'ellada_testimony',
			'post_status' => 'publish',
			'numberposts' => $limit,
			'fields'      => 'ids',
			'meta_query'  => $mq, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

function ellada_testimony_meta_line( $id ) {
	$bits = array();
	$age  = (int) get_post_meta( $id, 'age', true );
	$who  = trim( (string) get_post_meta( $id, 'person', true ) );
	if ( $who ) {
		$bits[] = '<strong>' . esc_html( $who ) . '</strong>' . ( $age ? ', ' . esc_html( $age ) . ' χρονών' : '' );
	}
	$place = (int) get_post_meta( $id, 'place', true );
	$ptxt  = trim( (string) get_post_meta( $id, 'place_text', true ) );
	if ( $ptxt ) {
		$bits[] = esc_html( $ptxt ) . ( $place ? ', <a href="' . esc_url( get_permalink( $place ) ) . '">' . esc_html( get_the_title( $place ) ) . '</a>' : '' );
	} elseif ( $place ) {
		$bits[] = '<a href="' . esc_url( get_permalink( $place ) ) . '">' . esc_html( get_the_title( $place ) ) . '</a>';
	}
	$year = (int) get_post_meta( $id, 'year', true );
	if ( $year ) {
		$bits[] = (string) $year;
	}
	return implode( ' · ', $bits );
}

function ellada_testimony_paras( $id, $max_chars = 0 ) {
	$text = trim( (string) get_post_meta( $id, 'testimony', true ) );
	if ( $max_chars && mb_strlen( $text ) > $max_chars ) {
		$cut  = mb_substr( $text, 0, $max_chars );
		$text = mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) ) . '…';
	}
	return implode( '', array_map( fn( $p ) => '<p>' . nl2br( esc_html( $p ) ) . '</p>', array_filter( array_map( 'trim', preg_split( "/\n\s*\n/", $text ) ) ) ) );
}

/** Κάρτα μαρτυρίας: απόσπασμα + ποιος/πού/πότε + «Διαβάστε όλη τη μαρτυρία». */
function ellada_testimony_card( $id, $excerpt = 420 ) {
	$full  = mb_strlen( trim( (string) get_post_meta( $id, 'testimony', true ) ) ) > $excerpt;
	return sprintf(
		'<figure class="ellada-testimony"><blockquote>%1$s</blockquote><figcaption>%2$s%3$s</figcaption></figure>',
		ellada_testimony_paras( $id, $excerpt ),
		ellada_testimony_meta_line( $id ),
		$full ? ' · <a href="' . esc_url( get_permalink( $id ) ) . '">Όλη η μαρτυρία <span aria-hidden="true">→</span></a>' : ''
	);
}

/* ═════════════════════ Η σελίδα μιας μαρτυρίας ═════════════════════ */

add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_singular( 'ellada_testimony' ) || ! in_the_loop() ) {
			return $content;
		}
		$id = get_the_ID();
		ellada_front_style();
		if ( ! get_post_meta( $id, 'consent', true ) ) {
			return '<p>Η μαρτυρία δεν είναι διαθέσιμη.</p>';
		}
		$recipes = array_filter( array_map( 'intval', (array) get_post_meta( $id, 'recipes', true ) ), fn( $r ) => 'publish' === get_post_status( $r ) );
		$cards   = implode( '', array_map( 'ellada_recipe_card', $recipes ) );
		$by      = trim( (string) get_post_meta( $id, 'recorded_by', true ) );
		$lost    = get_page_by_path( 'oi-geuseis-pou-xanontai', OBJECT, 'page' );
		return '<div class="ellada-tmpage">'
			. ( $lost && 'publish' === $lost->post_status ? '<p class="ellada-sh__eyebrow"><a href="' . esc_url( get_permalink( $lost ) ) . '">Οι γεύσεις που χάνονται</a> · Μαρτυρία</p>' : '<p class="ellada-sh__eyebrow">Μαρτυρία</p>' )
			. '<figure class="ellada-testimony is-full"><blockquote>' . ellada_testimony_paras( $id ) . '</blockquote><figcaption>' . ellada_testimony_meta_line( $id ) . '</figcaption></figure>'
			. ( $by ? '<p class="ellada-tmpage__by">Την κατέγραψε: ' . esc_html( $by ) . '</p>' : '' )
			. ( $cards ? '<h2 class="ellada-h">Οι συνταγές της μαρτυρίας</h2><div class="ellada-cards">' . $cards . '</div>' : '' )
			. '</div>';
	},
	20
);

/* Μαρτυρία χωρίς συγκατάθεση: όχι στο Google. */
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( is_singular( 'ellada_testimony' ) && ( ! ellada_lost() || ! get_post_meta( get_queried_object_id(), 'consent', true ) ) ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular( 'ellada_testimony' ) || ( is_page() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'ellada_lost' ) ) ) {
			$classes[] = 'ellada-page-mine'; // ίδια περιθώρια με τις απλές σελίδες
		}
		return $classes;
	}
);

/* ═════════════════════ Στη συνταγή ═════════════════════ */

add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( ! ellada_lost() ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		// Πριν από την Εκτέλεση: γιατί χάνεται + μαρτυρίες.
		if ( 'ellada_recipe_steps' === $tag ) {
			$pre  = '';
			$note = trim( (string) get_post_meta( $id, 'endangered_note', true ) );
			if ( get_post_meta( $id, 'endangered', true ) ) {
				$pre .= '<p class="ellada-lostnote"><strong>Γεύση που χάνεται.</strong> ' . esc_html( $note ? $note : 'Τη φτιάχνουν πια λίγοι. Τη γράφουμε εδώ για να μη χαθεί.' ) . '</p>';
			}
			$tms = ellada_testimonies( $id, 3 );
			if ( $tms ) {
				$pre .= '<section class="ellada-rtestimonies"><h2 class="ellada-h">' . ( count( $tms ) > 1 ? 'Μαρτυρίες' : 'Μια μαρτυρία' ) . '</h2>' . implode( '', array_map( 'ellada_testimony_card', $tms ) ) . '</section>';
			}
			return $pre . $output;
		}
		return $output;
	},
	15, // πριν από την «ιστορία» (20), ώστε η σειρά να είναι: ιστορία → γεύση που χάνεται/μαρτυρίες → εκτέλεση
	3
);

/* Σήμα «Γεύση που χάνεται» δίπλα στη διαδρομή (μετά το σήμα είδους της αξιοπιστίας). */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_recipe_crumbs' !== $tag || ! ellada_lost() ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		// Σήμα δίπλα στη διαδρομή.
		if ( '' !== $output && get_post_meta( $id, 'endangered', true ) ) {
			$page  = get_page_by_path( 'oi-geuseis-pou-xanontai', OBJECT, 'page' );
			$badge = sprintf(
				'<%1$s class="ellada-kind is-lost"%2$s title="%3$s">Γεύση που χάνεται</%1$s>',
				$page && 'publish' === $page->post_status ? 'a' : 'span',
				$page && 'publish' === $page->post_status ? ' href="' . esc_url( get_permalink( $page ) ) . '"' : '',
				esc_attr( (string) get_post_meta( $id, 'endangered_note', true ) )
			);
			if ( false !== strpos( $output, 'ellada-kinds' ) ) {
				$pos = strrpos( $output, '</span></div>' );
				return substr_replace( $output, $badge . '</span></div>', $pos, strlen( '</span></div>' ) );
			}
			return '<div class="ellada-rcrumbs-row">' . $output . '<span class="ellada-kinds">' . $badge . '</span></div>';
		}
		return $output;
	},
	35,
	3
);

/* ═════════════════════ [ellada_lost]: η σελίδα της ενότητας ═════════════════════ */

add_shortcode(
	'ellada_lost',
	function () {
		ellada_front_style();
		$q = fn( $type ) => get_posts(
			array(
				'post_type'   => $type,
				'post_status' => 'publish',
				'numberposts' => 24,
				'fields'      => 'ids',
				'meta_query'  => array( array( 'key' => 'endangered', 'value' => '1' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$recipes = $q( 'ellada_recipe' );
		$points  = $q( 'ellada_point' );
		$tms     = ellada_testimonies( 0, 12 );
		$contact = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
		$cta     = $contact ? add_query_arg( 'thema', 'syntagi', get_permalink( $contact ) ) . '#form-field-message' : '';

		$html  = '<div class="ellada-lost">';
		$html .= '<div class="ellada-lost__intro"><p class="ellada-lost__lead">Κάθε τόπος έχει φαγητά που τα έφτιαχναν όλοι και σήμερα τα θυμούνται λίγοι: μια πίτα της γιορτής, ένα τυρί που το έπηζαν στα μιτάτα, ένα γλυκό που ήθελε όλη τη γειτονιά για να γίνει. Τα καταγράφουμε εδώ, με τα λόγια των ανθρώπων που τα έζησαν, για να μη χαθούν.</p></div>';

		if ( $recipes ) {
			$html .= '<section class="ellada-lost__sec"><h2 class="ellada-h">Συνταγές που χάνονται</h2><div class="ellada-cards">' . implode( '', array_map( 'ellada_recipe_card', $recipes ) ) . '</div></section>';
		}
		if ( $points && function_exists( 'ellada_point_card' ) ) {
			$html .= '<section class="ellada-lost__sec"><h2 class="ellada-h">Προϊόντα και τεχνικές</h2><div class="ellada-cards">' . implode( '', array_map( 'ellada_point_card', $points ) ) . '</div></section>';
		}
		if ( $tms ) {
			$html .= '<section class="ellada-lost__sec"><h2 class="ellada-h">Μαρτυρίες</h2><div class="ellada-lost__tms">' . implode( '', array_map( 'ellada_testimony_card', $tms ) ) . '</div></section>';
		}
		if ( ! $recipes && ! $tms ) {
			$html .= '<p class="ellada-lost__empty">Η ενότητα μόλις ξεκίνησε. Οι πρώτες συνταγές και μαρτυρίες έρχονται σύντομα.</p>';
		}
		if ( $cta ) {
			$html .= '<aside class="ellada-hsubmit ellada-lost__cta"><div class="ellada-hsubmit__text"><p class="ellada-sh__eyebrow">Από εσάς</p><h2 class="ellada-hsubmit__title">Ξέρετε μια συνταγή<br><strong>που δεν τη φτιάχνει πια κανείς;</strong></h2><p class="ellada-hsubmit__lead">Μια γιαγιά που θυμάται, ένα τετράδιο με συνταγές, ένα έθιμο του χωριού. Γράψτε μας· θα έρθουμε σε επαφή μαζί σας για να την καταγράψουμε σωστά, με τη συγκατάθεση όλων.</p></div><div class="ellada-hsubmit__actions"><a class="ellada-hsubmit__btn" href="' . esc_url( $cta ) . '">Πείτε μας <span aria-hidden="true">→</span></a></div></aside>';
		}
		return $html . '</div>';
	}
);

/* Στο «Τι λείπει» της μαρτυρίας. */
add_action(
	'add_meta_boxes_ellada_testimony',
	function () {
		add_meta_box(
			'ellada-tm-help',
			'Πριν δημοσιεύσετε',
			function ( $post ) {
				$ok = get_post_meta( $post->ID, 'consent', true );
				echo '<p style="margin:0 0 8px;font-weight:600;color:' . ( $ok ? '#2e7d32' : '#b32d2e' ) . '">' . ( $ok ? '✓ Υπάρχει συγκατάθεση' : '✗ Λείπει η συγκατάθεση — δεν θα εμφανιστεί' ) . '</p>'
					. '<p style="margin:0;color:#646970">Μόνο όνομα και αρχικό επωνύμου, εκτός αν ο άνθρωπος θέλει το πλήρες. Χωρίς τηλέφωνα ή διευθύνσεις. Αν ζητήσει να αφαιρεθεί, τη βάζουμε σε πρόχειρο αμέσως.</p>';
			},
			'ellada_testimony',
			'side',
			'high'
		);
	}
);
