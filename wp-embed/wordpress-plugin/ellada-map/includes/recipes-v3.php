<?php
/**
 * Συνταγές v3.
 *
 * Διαχειριστικό (ενεργά πάντα):
 *   - Ετικέτες περίστασης (taxonomy recipe_occasion): οι Συλλογές γεμίζουν μόνες τους από το «Θέμα» τους
 *   - Συνταγές → «Συνταγές που λείπουν»: τα πιάτα του «Τι να φάτε» των τόπων χωρίς συνταγή, με «Νέα συνταγή»
 *     που ανοίγει με έτοιμο τίτλο και περιοχή
 *   - «Ολόκληρη συνταγή» στη Γρήγορη συμπλήρωση: τίτλος, περιγραφή, μερίδες, χρόνοι, υλικά, βήματα
 *   - Φωτογραφίες σε 1:1, 4:3, 16:9 (μεγέθη ellada-1x1/4x3/16x9)
 *
 * Σελίδα συνταγής (λειτουργία «recipe_v3»· προεπισκόπηση ?ellada_preview=1):
 *   - «Το έφτιαξα!» με μετρητή (REST ellada/v1/made — χωρίς προσωπικά δεδομένα)
 *   - ♥ Αγαπημένα (στη συσκευή) + σελίδα «Οι συνταγές μου» με [ellada_my_recipes]
 *   - schema.org image σε 3 αναλογίες · αυτόματη εικόνα κοινοποίησης όταν λείπει φωτογραφία
 */

defined( 'ABSPATH' ) || exit;

function ellada_recipe_v3() {
	return function_exists( 'ellada_feature' ) && ellada_feature( 'recipe_v3' );
}

/* ═════════════════════ Ετικέτες περίστασης ═════════════════════ */

function ellada_occasions() {
	return array(
		'xristougenna'      => 'Χριστούγεννα',
		'protoxronia'       => 'Πρωτοχρονιά',
		'apokries'          => 'Απόκριες',
		'sarakosti'         => 'Σαρακοστή',
		'pasxa'             => 'Πάσχα',
		'dekapentavgoustos' => 'Δεκαπενταύγουστος',
		'kalokairi'         => 'Καλοκαίρι',
		'xeimonas'          => 'Χειμώνας',
	);
}

/** «Θέμα» συλλογής → ετικέτες περίστασης. */
function ellada_collection_theme_occasions() {
	return array(
		'christmas' => array( 'xristougenna', 'protoxronia' ),
		'easter'    => array( 'pasxa' ),
		'fasting'   => array( 'sarakosti' ),
		'summer'    => array( 'kalokairi', 'dekapentavgoustos' ),
		'winter'    => array( 'xeimonas' ),
	);
}

add_action(
	'init',
	function () {
		register_taxonomy(
			'recipe_occasion',
			'ellada_recipe',
			array(
				'labels'            => array(
					'name'          => 'Περιστάσεις',
					'singular_name' => 'Περίσταση',
					'menu_name'     => 'Περιστάσεις',
					'add_new_item'  => 'Νέα περίσταση',
				),
				'hierarchical'      => true, // κουτάκια στην οθόνη επεξεργασίας
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
			)
		);
		if ( ! get_option( 'ellada_occasions_v1' ) ) {
			foreach ( ellada_occasions() as $slug => $name ) {
				if ( ! term_exists( $slug, 'recipe_occasion' ) ) {
					wp_insert_term( $name, 'recipe_occasion', array( 'slug' => $slug ) );
				}
			}
			update_option( 'ellada_occasions_v1', 1 );
		}
	},
	20
);

/** Οι συνταγές μιας συλλογής: όσες επιλέχθηκαν + όσες έχουν την περίσταση του θέματος της. */
function ellada_collection_recipe_ids( $collection_id ) {
	$ids   = array_values( array_filter( array_map( 'intval', (array) get_post_meta( $collection_id, 'recipes', true ) ) ) );
	$theme = function_exists( 'ellada_select_value' ) ? ellada_select_value( get_post_meta( $collection_id, 'theme', true ) ) : (string) get_post_meta( $collection_id, 'theme', true );
	$occ   = ellada_collection_theme_occasions()[ $theme ] ?? array();
	if ( $occ ) {
		$more = get_posts(
			array(
				'post_type'   => 'ellada_recipe',
				'post_status' => 'publish',
				'numberposts' => 50,
				'fields'      => 'ids',
				'tax_query'   => array( array( 'taxonomy' => 'recipe_occasion', 'field' => 'slug', 'terms' => $occ ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$ids = array_values( array_unique( array_merge( $ids, array_map( 'intval', $more ) ) ) );
	}
	return $ids;
}

add_filter(
	'ellada_recipe_query_args',
	function ( $args, $source, $post_id ) {
		if ( 'collection' === $source && 'ellada_collection' === get_post_type( $post_id ) ) {
			$ids                    = ellada_collection_recipe_ids( $post_id );
			$args['post__in']       = $ids ? $ids : array( 0 );
			$args['orderby']        = 'post__in';
			$args['posts_per_page'] = max( count( $ids ), 1 );
		}
		return $args;
	},
	10,
	3
);

/* ═════════════════════ Συνταγές που λείπουν ═════════════════════ */

/** [[πιάτο, περιγραφή, id τόπου], …] για πιάτα του «Τι να φάτε» χωρίς συνταγή στο site. */
function ellada_missing_recipes() {
	$out = array();
	$ids = get_posts(
		array(
			'post_type'   => 'ellada_location',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array( array( 'key' => 'dishes', 'value' => '', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $ids as $loc ) {
		foreach ( ellada_guide_pairs( get_post_meta( $loc, 'dishes', true ) ) as list( $name, $desc ) ) {
			if ( ! ellada_guide_dish_recipe( $name ) ) {
				$out[] = array( $name, $desc, (int) $loc );
			}
		}
	}
	return $out;
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'edit.php?post_type=ellada_recipe',
			'Συνταγές που λείπουν',
			'Συνταγές που λείπουν',
			'edit_posts',
			'ellada-missing',
			function () {
				$rows = ellada_missing_recipes();
				echo '<div class="wrap"><h1>Συνταγές που λείπουν</h1>';
				echo '<p>Τα πιάτα που αναφέρονται στο «Τι να φάτε» του οδηγού και δεν έχουν ακόμα συνταγή στο site. Μόλις γραφτεί μια συνταγή με το ίδιο όνομα, το πιάτο στον οδηγό γίνεται link και φεύγει από εδώ.</p>';
				if ( ! $rows ) {
					echo '<p><strong>Δεν λείπει καμία.</strong></p></div>';
					return;
				}
				echo '<table class="widefat striped"><thead><tr><th>Πιάτο</th><th>Περιγραφή στον οδηγό</th><th>Τόπος</th><th></th></tr></thead><tbody>';
				foreach ( $rows as list( $name, $desc, $loc ) ) {
					$new = add_query_arg(
						array(
							'post_type'       => 'ellada_recipe',
							'ellada_title'    => rawurlencode( $name ),
							'ellada_location' => $loc,
						),
						admin_url( 'post-new.php' )
					);
					printf(
						'<tr><td><strong>%1$s</strong></td><td>%2$s</td><td><a href="%3$s">%4$s</a></td><td><a class="button button-primary" href="%5$s">Νέα συνταγή</a></td></tr>',
						esc_html( $name ),
						esc_html( $desc ),
						esc_url( get_edit_post_link( $loc ) ),
						esc_html( get_the_title( $loc ) ),
						esc_url( $new )
					);
				}
				echo '</tbody></table><p class="description">' . esc_html( sprintf( 'Σύνολο: %d πιάτα.', count( $rows ) ) ) . '</p></div>';
			}
		);
	}
);

/* «Νέα συνταγή» από τη λίστα: έτοιμος τίτλος και περιοχή. */
add_filter(
	'default_title',
	function ( $title, $post ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 'ellada_recipe' === $post->post_type && ! empty( $_GET['ellada_title'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ellada_title'] ) ) ) : $title;
	},
	10,
	2
);
add_filter(
	'acf/load_value/name=location',
	function ( $value, $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $value && is_admin() && ! empty( $_GET['ellada_location'] ) && 'ellada_recipe' === get_post_type( $post_id ) ) {
			return absint( $_GET['ellada_location'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		return $value;
	},
	10,
	2
);

/* ═════════════════════ Ολόκληρη συνταγή (επικόλληση) ═════════════════════ */

/** Χωρίζει ένα κείμενο συνταγής σε τίτλο, εισαγωγή, μερίδες, χρόνους, υλικά, βήματα. */
function ellada_quick_parse_full( $text ) {
	$out  = array( 'title' => '', 'intro' => '', 'servings' => 0, 'prep' => 0, 'cook' => 0, 'ingredients' => '', 'steps' => '' );
	$mode = 'head';
	$head = array();
	$ing  = array();
	$st   = array();
	$mins = function ( $num, $unit ) {
		$n = (float) str_replace( ',', '.', $num );
		return (int) round( preg_match( '/^ώρ|^h/ui', $unit ) ? $n * 60 : $n );
	};
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $raw ) {
		$line = trim( $raw );
		$low  = mb_strtolower( $line );
		if ( preg_match( '/^(?:τα\s+)?(?:υλικά|συστατικά|χρειαζόμαστε|θα χρειαστούμε)\b[^:]*:?\s*$/u', $low ) ) {
			$mode = 'ing';
			continue;
		}
		if ( preg_match( '/^(?:εκτέλεση|οδηγίες|βήματα|παρασκευή|διαδικασία|τρόπος παρασκευής|μέθοδος|πώς τη? φτιάχνουμε)\b[^:]*:?\s*$/u', $low ) ) {
			$mode = 'steps';
			continue;
		}
		if ( preg_match( '/μερίδ\w*\s*[:\-–]?\s*(\d+)|(\d+)\s*μερίδ/u', $low, $m ) ) {
			$out['servings'] = (int) ( $m[1] ?: $m[2] );
			if ( mb_strlen( $line ) < 40 ) {
				continue;
			}
		}
		if ( preg_match( '/προετοιμασ\w*\s*[:\-–]?\s*(\d+(?:[.,]\d+)?)\s*(ώρ\w*|λεπτ\w*|΄|\'|min)/u', $low, $m ) ) {
			$out['prep'] = $mins( $m[1], $m[2] );
			if ( mb_strlen( $line ) < 50 ) {
				continue;
			}
		}
		if ( preg_match( '/(?:μαγείρεμ\w*|ψήσιμο|βράσιμο|χρόνος (?:μαγειρέματος|ψησίματος))\s*[:\-–]?\s*(\d+(?:[.,]\d+)?)\s*(ώρ\w*|λεπτ\w*|΄|\'|min)/u', $low, $m ) ) {
			$out['cook'] = $mins( $m[1], $m[2] );
			if ( mb_strlen( $line ) < 50 ) {
				continue;
			}
		}
		if ( 'ing' === $mode ) {
			$ing[] = $raw;
		} elseif ( 'steps' === $mode ) {
			$st[] = $raw;
		} elseif ( '' !== $line ) {
			$head[] = $line;
		}
	}
	if ( $head ) {
		$out['title'] = array_shift( $head );
		$out['intro'] = implode( ' ', $head );
	}
	$out['ingredients'] = trim( implode( "\n", $ing ) );
	$out['steps']       = trim( implode( "\n", $st ) );
	return $out;
}

/* Το πεδίο «Ολόκληρη συνταγή» πάνω από τα άλλα δύο της Γρήγορης συμπλήρωσης. */
add_action(
	'ellada_quick_box_top',
	function () {
		echo '<p><label for="ellada_quick_full"><strong>Ολόκληρη συνταγή</strong> (προαιρετικά): επικολλήστε όλο το κείμενο</label>'
			. '<textarea id="ellada_quick_full" name="ellada_quick_full" rows="8" style="width:100%" placeholder="Γεμιστά&#10;Τα γεμιστά του καλοκαιριού, με ρύζι και μυρωδικά.&#10;Μερίδες: 4 · Προετοιμασία: 30 λεπτά · Ψήσιμο: 1 ώρα&#10;&#10;Υλικά&#10;6 ντομάτες&#10;…&#10;&#10;Εκτέλεση&#10;1. Κόβουμε τα καπάκια…"></textarea>'
			. '<span class="description">Αναγνωρίζονται: ο τίτλος (πρώτη γραμμή, αν δεν έχετε γράψει τίτλο), η περιγραφή, «Μερίδες», «Προετοιμασία», «Ψήσιμο/Μαγείρεμα», και οι ενότητες «Υλικά» και «Εκτέλεση». Συμπληρώνονται μόνο όσα πεδία είναι κενά — ελέγξτε τα μετά την αποθήκευση.</span></p>';
	}
);

add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( ! is_numeric( $post_id ) || 'ellada_recipe' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['ellada_quick_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ellada_quick_nonce'] ), 'ellada_quick' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		static $done = array();
		if ( isset( $done[ $post_id ] ) ) {
			return;
		}
		$done[ $post_id ] = true;
		$raw = isset( $_POST['ellada_quick_full'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ellada_quick_full'] ) ) : '';
		if ( '' === trim( $raw ) ) {
			return;
		}
		$p = ellada_quick_parse_full( $raw );
		if ( $p['title'] && in_array( trim( get_the_title( $post_id ) ), array( '', 'Αυτόματο πρόχειρο', 'Auto Draft' ), true ) ) {
			static $busy = false;
			if ( ! $busy ) {
				$busy = true;
				wp_update_post( array( 'ID' => $post_id, 'post_title' => $p['title'], 'post_name' => '' ) );
				$busy = false;
			}
		}
		$empty = fn( $k ) => '' === trim( (string) get_post_meta( $post_id, $k, true ) ) || '0' === (string) get_post_meta( $post_id, $k, true );
		if ( $p['intro'] && $empty( 'short_description' ) ) {
			update_field( 'short_description', $p['intro'], $post_id );
		}
		foreach ( array( 'servings' => 'servings', 'prep' => 'prep_time', 'cook' => 'cook_time' ) as $k => $field ) {
			if ( $p[ $k ] && $empty( $field ) ) {
				update_field( $field, $p[ $k ], $post_id );
			}
		}
		foreach ( array( 'ingredients' => 'ellada_quick_parse_ingredients', 'steps' => 'ellada_quick_parse_steps' ) as $field => $parser ) {
			if ( $p[ $field ] && ! (int) get_post_meta( $post_id, $field, true ) ) {
				$rows = call_user_func( $parser, $p[ $field ] );
				if ( $rows ) {
					update_field( $field, $rows, $post_id );
				}
			}
		}
		if ( function_exists( 'ellada_update_total_time' ) ) {
			ellada_update_total_time( $post_id );
		}
	},
	21
);

/* ═════════════════════ Φωτογραφίες σε τρεις αναλογίες ═════════════════════ */

add_action(
	'after_setup_theme',
	function () {
		add_image_size( 'ellada-1x1', 1200, 1200, true );
		add_image_size( 'ellada-4x3', 1200, 900, true );
		add_image_size( 'ellada-16x9', 1200, 675, true );
	}
);

/** Η φωτογραφία μιας συνταγής σε 1:1, 4:3, 16:9 (όσες υπάρχουν· αλλιώς η αρχική). */
function ellada_recipe_image_set( $id ) {
	$att = (int) get_post_thumbnail_id( $id );
	if ( ! $att ) {
		return array();
	}
	$meta = wp_get_attachment_metadata( $att );
	// Παλιές φωτογραφίες: τα νέα μεγέθη φτιάχνονται μία φορά, όταν χρειαστούν.
	if ( is_array( $meta ) && empty( $meta['sizes']['ellada-4x3'] ) && empty( get_post_meta( $att, '_ellada_sizes_v1', true ) ) && function_exists( 'wp_get_original_image_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$file = wp_get_original_image_path( $att );
		if ( $file && file_exists( $file ) ) {
			$meta = wp_generate_attachment_metadata( $att, $file );
			wp_update_attachment_metadata( $att, $meta );
		}
		update_post_meta( $att, '_ellada_sizes_v1', 1 );
	}
	$urls = array();
	foreach ( array( 'ellada-1x1' => 1, 'ellada-4x3' => 4 / 3, 'ellada-16x9' => 16 / 9 ) as $size => $ratio ) {
		$sz = $meta['sizes'][ $size ] ?? null;
		// Μόνο όταν η φωτογραφία ήταν αρκετά μεγάλη για ακριβή αναλογία.
		if ( $sz && ! empty( $sz['height'] ) && abs( $sz['width'] / $sz['height'] - $ratio ) < 0.02 ) {
			$urls[] = wp_get_attachment_image_url( $att, $size );
		}
	}
	return $urls ? array_values( array_unique( $urls ) ) : array( wp_get_attachment_image_url( $att, 'full' ) );
}

add_filter(
	'ellada_recipe_schema',
	function ( $data, $id ) {
		if ( ellada_recipe_v3() ) {
			$set = ellada_recipe_image_set( $id );
			if ( $set ) {
				$data['image'] = $set;
			}
		}
		return $data;
	},
	20,
	2
);

/* ═════════════════════ Αυτόματη εικόνα κοινοποίησης ═════════════════════ */

/** PNG 1200×630 (στο uploads/ellada-og/) για συνταγή ή τόπο χωρίς φωτογραφία. */
function ellada_og_card_url( $id ) {
	if ( ! function_exists( 'imagettftext' ) ) {
		return '';
	}
	$type  = get_post_type( $id );
	$title = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
	if ( 'ellada_recipe' === $type ) {
		$cats    = get_the_terms( $id, 'recipe_category' );
		$eyebrow = is_array( $cats ) && $cats ? $cats[0]->name : 'Συνταγή';
		$sub     = (string) get_post_meta( $id, ELLADA_AREA_KEYS['region'], true );
	} elseif ( 'ellada_location' === $type ) {
		$eyebrow = 'Ταξιδιωτικός οδηγός';
		$sub     = function_exists( 'ellada_guide_first_sentence' ) ? ellada_guide_first_sentence( $id ) : '';
	} else {
		return '';
	}
	$up   = wp_upload_dir();
	$key  = substr( md5( $title . '|' . $eyebrow . '|' . $sub . '|v3' ), 0, 10 );
	$rel  = 'ellada-og/' . $type . '-' . $id . '-' . $key . '.png';
	$path = trailingslashit( $up['basedir'] ) . $rel;
	$url  = trailingslashit( $up['baseurl'] ) . $rel;
	if ( file_exists( $path ) ) {
		return $url;
	}
	wp_mkdir_p( dirname( $path ) );
	foreach ( (array) glob( trailingslashit( $up['basedir'] ) . 'ellada-og/' . $type . '-' . $id . '-*.png' ) as $old ) {
		@unlink( $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$w    = 1200;
	$h    = 630;
	$im   = imagecreatetruecolor( $w, $h );
	$dark = imagecolorallocate( $im, 23, 76, 75 );
	$or   = imagecolorallocate( $im, 210, 154, 72 );
	$wh   = imagecolorallocate( $im, 246, 242, 233 );
	$mu   = imagecolorallocate( $im, 169, 189, 184 );
	imagefilledrectangle( $im, 0, 0, $w, $h, $dark );
	// Διακριτικές τελείες στο φόντο.
	$dot = imagecolorallocate( $im, 31, 88, 86 );
	for ( $x = 12; $x < $w; $x += 28 ) {
		for ( $y = 12; $y < $h; $y += 28 ) {
			imagefilledellipse( $im, $x, $y, 3, 3, $dot );
		}
	}
	imagefilledrectangle( $im, 0, $h - 14, $w, $h, $or );

	$fonts = ELLADA_MAP_DIR . 'public/fonts/';
	$big   = $fonts . 'FiraSansExtraCondensed_800ExtraBold.ttf';
	$small = $fonts . 'Commissioner_600SemiBold.ttf';

	// Το λογότυπο (εικονίδιο του site) πάνω δεξιά.
	$icon = get_site_icon_url( 512 );
	if ( $icon ) {
		$ipath = str_replace( trailingslashit( $up['baseurl'] ), trailingslashit( $up['basedir'] ), $icon );
		if ( file_exists( $ipath ) ) {
			$src = @imagecreatefromstring( (string) file_get_contents( $ipath ) ); // phpcs:ignore
			if ( $src ) {
				imagecopyresampled( $im, $src, $w - 80 - 150, 70, 0, 0, 150, 150, imagesx( $src ), imagesy( $src ) );
			}
		}
	}

	// Κεφαλαία χωρίς τόνους, όπως γράφονται στα ελληνικά.
	$caps = fn( $t ) => mb_strtoupper( strtr( $t, array( 'ά' => 'α', 'έ' => 'ε', 'ή' => 'η', 'ί' => 'ι', 'ό' => 'ο', 'ύ' => 'υ', 'ώ' => 'ω', 'ΐ' => 'ϊ', 'ΰ' => 'ϋ', 'Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι', 'Ό' => 'Ο', 'Ύ' => 'Υ', 'Ώ' => 'Ω' ) ) );
	$x    = 80;
	imagettftext( $im, 22, 0, $x, 120, $or, $small, $caps( $eyebrow ) );
	// Τίτλος: κεφαλαία, αναδίπλωση σε έως 3 γραμμές.
	$size  = 84;
	$text  = $caps( $title );
	$maxw  = $w - 2 * $x - 180;
	$lines = array();
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
		$size   -= 6;
	} while ( count( $lines ) > 3 && $size > 40 );
	$size += 6;
	$y     = 140 + $size;
	foreach ( $lines as $i => $ln ) {
		imagettftext( $im, $size, 0, $x, $y, 0 === $i ? $wh : $or, $big, $ln );
		$y += (int) ( $size * 1.05 );
	}
	if ( $sub ) {
		// Υπότιτλος σε έως 2 γραμμές.
		$sl  = array( '' );
		foreach ( preg_split( '/\s+/u', $sub ) as $word ) {
			$try = trim( end( $sl ) . ' ' . $word );
			$bb  = imagettfbbox( 24, 0, $small, $try );
			if ( end( $sl ) && ( $bb[2] - $bb[0] ) > $w - 2 * $x ) {
				if ( count( $sl ) === 2 ) {
					$sl[1] .= '…';
					break;
				}
				$sl[] = $word;
			} else {
				$sl[ count( $sl ) - 1 ] = $try;
			}
		}
		$sy = min( $y + 30, $h - 150 );
		foreach ( $sl as $ln ) {
			imagettftext( $im, 24, 0, $x, $sy, $mu, $small, $ln );
			$sy += 38;
		}
	}
	imagettftext( $im, 20, 0, $x, $h - 50, $wh, $small, 'elladastopiato.gr' );
	imagepng( $im, $path, 6 );
	imagedestroy( $im );
	return file_exists( $path ) ? $url : '';
}

/* ═════════════════════ Το έφτιαξα! · Αγαπημένα ═════════════════════ */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ellada/v1',
			'/made/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => function ( WP_REST_Request $r ) {
						$res = rest_ensure_response( array( 'count' => (int) get_post_meta( (int) $r['id'], '_ellada_made', true ) ) );
						$res->header( 'Cache-Control', 'no-store' );
						return $res;
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => '__return_true',
					'callback'            => function ( WP_REST_Request $r ) {
						$id = (int) $r['id'];
						if ( 'ellada_recipe' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
							return new WP_Error( 'not_found', 'Δεν βρέθηκε', array( 'status' => 404 ) );
						}
						$n = (int) get_post_meta( $id, '_ellada_made', true ) + 1;
						update_post_meta( $id, '_ellada_made', $n );
						$res = rest_ensure_response( array( 'count' => $n ) );
						$res->header( 'Cache-Control', 'no-store' );
						return $res;
					},
				),
			)
		);
		register_rest_route(
			'ellada/v1',
			'/cards',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $r ) {
					$ids  = array_slice( array_filter( array_map( 'absint', explode( ',', (string) $r['ids'] ) ) ), 0, 60 );
					$html = '';
					foreach ( $ids as $id ) {
						if ( 'ellada_recipe' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) {
							$html .= ellada_recipe_card( $id );
						}
					}
					return rest_ensure_response( array( 'html' => $html ) );
				},
			)
		);
	}
);

function ellada_made_label( $n ) {
	if ( $n && $n < (int) apply_filters( 'ellada_made_min', 0 ) ) {
		return 'Γίνετε από τους πρώτους που θα τη φτιάξουν';
	}
	return $n ? sprintf( 1 === $n ? 'Την έφτιαξε %d άτομο' : 'Την έφτιαξαν %d άτομα', $n ) : 'Γίνετε οι πρώτοι που θα τη φτιάξουν';
}

/* Μετά τα βήματα: «Το έφτιαξα!». */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_recipe_steps' !== $tag || ! ellada_recipe_v3() ) {
			return $output;
		}
		$id = ellada_ctx_id( (array) $attr );
		if ( 'ellada_recipe' !== get_post_type( $id ) ) {
			return $output;
		}
		$n = (int) get_post_meta( $id, '_ellada_made', true );
		return $output . sprintf(
			'<div class="ellada-made" data-ellada-made="%1$d" data-endpoint="%2$s"><button type="button" class="ellada-made__btn"><span aria-hidden="true">✓</span> <span data-made-label>Το έφτιαξα!</span></button><p class="ellada-made__count" data-made-count>%3$s</p></div>',
			$id,
			esc_url( rest_url( 'ellada/v1/made/' . $id ) ),
			esc_html( ellada_made_label( $n ) )
		);
	},
	25,
	3
);

/* ♥ στη μπάρα ενεργειών. */
add_filter(
	'ellada_recipe_actions_extra',
	function ( $html, $id ) {
		if ( ! ellada_recipe_v3() ) {
			return $html;
		}
		$page = get_page_by_path( 'oi-syntages-mou', OBJECT, 'page' );
		return $html . sprintf(
			'<button type="button" class="ellada-ractions__btn is-fav" data-ellada-fav="%1$d" data-page="%2$s" aria-pressed="false"><span aria-hidden="true">♡</span> <span data-fav-label>Αγαπημένη</span></button>',
			$id,
			$page ? esc_url( get_permalink( $page ) ) : ''
		);
	},
	10,
	2
);

add_shortcode(
	'ellada_my_recipes',
	function () {
		ellada_front_style();
		if ( function_exists( 'ellada_recipe_tools_assets' ) ) {
			ellada_recipe_tools_assets();
		}
		return sprintf(
			'<div class="ellada-myrecipes" data-endpoint="%1$s"><p class="ellada-myrecipes__empty">Δεν έχετε ακόμα αγαπημένες συνταγές. Πατήστε <strong>♡ Αγαπημένη</strong> σε όποια συνταγή σας αρέσει και θα τη βρίσκετε εδώ — αποθηκεύεται μόνο σε αυτή τη συσκευή.</p><div class="ellada-cards"></div><p class="ellada-myrecipes__more"><a href="%2$s">Δείτε όλες τις συνταγές <span aria-hidden="true">→</span></a></p></div>',
			esc_url( rest_url( 'ellada/v1/cards' ) ),
			esc_url( get_post_type_archive_link( 'ellada_recipe' ) )
		);
	}
);

/* ═════════════════════ Πρόταση «Νηστίσιμο;» ═════════════════════ */

/** true όταν τα υλικά δεν έχουν ζωικά προϊόντα και η συνταγή δεν είναι σημειωμένη ως νηστίσιμη. */
function ellada_fasting_hint( $id ) {
	$diets = get_the_terms( $id, 'recipe_diet' );
	foreach ( is_array( $diets ) ? $diets : array() as $t ) {
		if ( false !== mb_stripos( $t->name, 'νηστ' ) || false !== mb_stripos( $t->name, 'vegan' ) ) {
			return false;
		}
	}
	$items = array();
	$n     = (int) get_post_meta( $id, 'ingredients', true );
	for ( $i = 0; $i < $n; $i++ ) {
		$items[] = (string) get_post_meta( $id, 'ingredients_' . $i . '_item', true );
	}
	if ( ! $items ) {
		return false;
	}
	$text = function_exists( 'ellada_search_norm' ) ? ellada_search_norm( implode( ' ', $items ) ) : mb_strtolower( implode( ' ', $items ) );
	$animal = array( 'κρεα', 'κιμα', 'μοσχ', 'χοιριν', 'αρνι', 'κατσικ', 'κοτοπουλ', 'κοτα', 'γαλοπουλ', 'κουνελ', 'λουκανικ', 'μπεικον', 'μπεϊκον', 'ζαμπον', 'παστουρμ', 'συκωτ', 'ψαρι', 'μπακαλιαρ', 'σαρδελ', 'γαυρ', 'τονο', 'σολομ', 'αντζουγ', 'γαλα', 'τυρι', 'φετα', 'γραβιερ', 'κεφαλοτυρ', 'μυζηθρ', 'ανθοτυρ', 'μανουρ', 'κασερ', 'παρμεζαν', 'ριγανατ', 'γιαουρτ', 'βουτυρ', 'κρεμα γαλακτος', 'αυγ', 'στακοβουτυρ', 'ξινογαλ', 'τσιγαριδ', 'απακι', 'σιγκλινο' );
	foreach ( $animal as $w ) {
		if ( false !== mb_strpos( $text, $w ) ) {
			return false;
		}
	}
	return true;
}

/* Η σελίδα «Οι συνταγές μου» (απλό template του theme): περιθώρια όπως στις υπόλοιπες. */
add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_page() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'ellada_my_recipes' ) ) {
			$classes[] = 'ellada-page-mine';
		}
		return $classes;
	}
);
