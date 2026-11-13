<?php
/**
 * Ρυθμίσεις → Χάρτης Ελλάδας
 *
 * Καρτέλες: Επισκόπηση · Εμφάνιση · Συμπεριφορά · Κείμενα · Εργαλεία.
 * Αποθηκεύει μόνο την επιλογή ellada_map_settings· δεν αγγίζει περιεχόμενο ή πεδία ACF.
 */

defined( 'ABSPATH' ) || exit;

const ELLADA_MAP_PAGE = 'ellada-map';

/** Οι καρτέλες της σελίδας (slug => τίτλος). */
function ellada_map_tabs() {
	return array(
		'overview'  => 'Επισκόπηση',
		'look'      => 'Εμφάνιση',
		'behaviour' => 'Συμπεριφορά',
		'texts'     => 'Κείμενα',
		'tools'     => 'Εργαλεία',
	);
}

function ellada_map_admin_url( $tab = 'overview', array $extra = array() ) {
	return add_query_arg( array_merge( array( 'page' => ELLADA_MAP_PAGE, 'tab' => $tab ), $extra ), admin_url( 'options-general.php' ) );
}

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Χάρτης Ελλάδας', 'Χάρτης Ελλάδας', 'manage_options', ELLADA_MAP_PAGE, 'ellada_map_settings_page' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( ELLADA_MAP_FILE ),
	function ( $links ) {
		array_unshift( $links, sprintf( '<a href="%s">Ρυθμίσεις</a>', esc_url( ellada_map_admin_url() ) ) );
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

/**
 * Κάθε καρτέλα στέλνει μόνο τα δικά της πεδία (+ _section). Τα υπόλοιπα κρατιούνται όπως είναι,
 * ώστε η αποθήκευση μιας καρτέλας να μη σβήνει τις άλλες.
 */
function ellada_map_sanitize_settings( $input ) {
	$input   = is_array( $input ) ? $input : array();
	$current = ellada_map_settings();

	// Εισαγωγή/επαναφορά από τα Εργαλεία: ολόκληρο το σύνολο ρυθμίσεων.
	$section = isset( $input['_section'] ) ? sanitize_key( $input['_section'] ) : 'all';
	if ( 'all' === $section ) {
		$current = ellada_map_defaults();
	}
	$has = function ( $s ) use ( $section ) {
		return 'all' === $section || $s === $section;
	};

	$out = $current;

	if ( $has( 'look' ) ) {
		$height               = isset( $input['height'] ) ? absint( $input['height'] ) : 600;
		$out['title']         = isset( $input['title'] ) ? sanitize_text_field( $input['title'] ) : '';
		$out['height']        = min( 1600, max( 300, $height ? $height : 600 ) );
		$hm                   = isset( $input['height_mobile'] ) ? absint( $input['height_mobile'] ) : 0;
		$out['height_mobile'] = $hm ? min( 1600, max( 300, $hm ) ) : 0;
		$out['radius']        = isset( $input['radius'] ) && '' !== $input['radius'] ? min( 80, absint( $input['radius'] ) ) : 40;
		$out['accent']        = isset( $input['accent'] ) ? (string) sanitize_hex_color( $input['accent'] ) : '';
		$bg                   = isset( $input['background'] ) ? (string) sanitize_hex_color( $input['background'] ) : '';
		$out['background']    = $bg && ellada_map_luminance( $bg ) <= 0.25 ? $bg : '';
		if ( $bg && '' === $out['background'] ) {
			add_settings_error( ELLADA_MAP_OPTION, 'ellada-bg', 'Το φόντο πρέπει να είναι σκούρο χρώμα, για να διαβάζονται τα λευκά κείμενα του χάρτη. Κρατήθηκε το προεπιλεγμένο.', 'warning' );
		}
		$out['type_colors'] = array();
		foreach ( array_keys( ellada_map_point_types() ) as $type ) {
			$c = isset( $input['type_colors'][ $type ] ) ? sanitize_hex_color( $input['type_colors'][ $type ] ) : '';
			if ( $c ) {
				$out['type_colors'][ $type ] = $c;
			}
		}
	}

	if ( $has( 'behaviour' ) ) {
		$out['settlements'] = ! empty( $input['settlements'] );
		$out['legend']      = ! empty( $input['legend'] );
		$types              = isset( $input['point_types'] ) ? (array) $input['point_types'] : array();
		$types              = array_values( array_intersect( array_map( 'sanitize_key', $types ), array_keys( ellada_map_point_types() ) ) );
		// Όλοι επιλεγμένοι = «όλοι» (και όσοι προστεθούν στο μέλλον).
		$out['point_types'] = count( $types ) === count( ellada_map_point_types() ) ? array() : $types;
		$region             = isset( $input['start_region'] ) ? sanitize_text_field( $input['start_region'] ) : '';
		$out['start_region'] = in_array( $region, ellada_map_region_names(), true ) ? $region : '';
		$empty              = isset( $input['empty_areas'] ) ? sanitize_key( $input['empty_areas'] ) : 'normal';
		$out['empty_areas'] = in_array( $empty, array( 'normal', 'dim', 'disabled' ), true ) ? $empty : 'normal';
	}

	if ( $has( 'texts' ) ) {
		$out['texts'] = array();
		foreach ( ellada_map_text_fields() as $key => $def ) {
			$v = isset( $input['texts'][ $key ] ) ? sanitize_text_field( $input['texts'][ $key ] ) : '';
			if ( '' !== $v && $v !== $def[1] ) {
				$out['texts'][ $key ] = $v;
			}
		}
	}

	return $out;
}

/** Σχετική φωτεινότητα (0 = μαύρο, 1 = λευκό) ενός hex χρώματος. */
function ellada_map_luminance( $hex ) {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$rgb = array_map(
		function ( $c ) {
			$c = hexdec( $c ) / 255;
			return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		},
		str_split( $hex, 2 )
	);
	return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'settings_page_' . ELLADA_MAP_PAGE !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'ellada-map-admin', plugins_url( 'admin/admin.css', ELLADA_MAP_FILE ), array(), ELLADA_MAP_VERSION );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){'
			. '$(".ellada-color").wpColorPicker();'
			. '$(".ellada-copy").on("click",function(){var b=$(this),t=b.data("copy");'
			. 'navigator.clipboard.writeText(t).then(function(){var o=b.text();b.text("Αντιγράφηκε ✓");setTimeout(function(){b.text(o)},1500)});});'
			. '$(".ellada-suggest-colors").on("click",function(){var c=$(this).data("colors");$.each(c,function(k,v){$("[data-type-color=\'"+k+"\']").wpColorPicker("color",v);});});'
			. '$(".ellada-clear-colors").on("click",function(){$("[data-type-color]").each(function(){$(this).wpColorPicker("color","");$(this).val("");});});'
			. '$(".ellada-types-all").on("click",function(e){e.preventDefault();$("input[name$=\'[point_types][]\']").prop("checked",true);});'
			. '});'
		);
	}
);

/* ═════════════════════ Σελίδα ═════════════════════ */

function ellada_map_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs = ellada_map_tabs();
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tab  = isset( $tabs[ $tab ] ) ? $tab : 'overview';
	?>
	<div class="wrap ellada-admin">
		<h1>Χάρτης Ελλάδας</h1>
		<?php ellada_map_admin_notices(); ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( ellada_map_admin_url( $slug ) ); ?>" class="nav-tab<?php echo $slug === $tab ? ' nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
		<div class="ellada-tab">
			<?php
			switch ( $tab ) {
				case 'look':
					ellada_map_tab_look();
					break;
				case 'behaviour':
					ellada_map_tab_behaviour();
					break;
				case 'texts':
					ellada_map_tab_texts();
					break;
				case 'tools':
					ellada_map_tab_tools();
					break;
				default:
					ellada_map_tab_overview();
			}
			?>
		</div>
		<p class="ellada-version">Έκδοση plugin <?php echo esc_html( ELLADA_MAP_VERSION ); ?></p>
	</div>
	<?php
}

/** Μηνύματα αποθήκευσης και ενεργειών των Εργαλείων. */
function ellada_map_admin_notices() {
	// Τα «Οι ρυθμίσεις αποθηκεύτηκαν» / προειδοποιήσεις τα δείχνει ήδη το WordPress (options-head.php).
	$messages = array(
		'reset'      => array( 'success', 'Οι ρυθμίσεις επανήλθαν στις προεπιλογές.' ),
		'imported'   => array( 'success', 'Οι ρυθμίσεις εισήχθησαν.' ),
		'bad-import' => array( 'error', 'Το κείμενο δεν είναι έγκυρο αρχείο ρυθμίσεων του χάρτη.' ),
		'purged'     => array( 'success', 'Η cache καθαρίστηκε. Οι επισκέπτες θα δουν το πιο πρόσφατο περιεχόμενο.' ),
		'resynced'   => array( 'success', 'Οι περιοχές όλων των συνταγών και των σημείων υπολογίστηκαν ξανά.' ),
	);
	$key = isset( $_GET['ellada-msg'] ) ? sanitize_key( wp_unslash( $_GET['ellada-msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $messages[ $key ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $messages[ $key ][0] ), esc_html( $messages[ $key ][1] ) );
	}
}

/** Άνοιγμα φόρμας ρυθμίσεων για μία καρτέλα. */
function ellada_map_form_open( $section ) {
	echo '<form method="post" action="options.php">';
	settings_fields( 'ellada_map' );
	printf( '<input type="hidden" name="%s[_section]" value="%s">', esc_attr( ELLADA_MAP_OPTION ), esc_attr( $section ) );
	// Μετά την αποθήκευση επιστροφή στην ίδια καρτέλα.
	printf( '<input type="hidden" name="_wp_http_referer" value="%s">', esc_attr( wp_unslash( ellada_map_admin_url( $section, array( 'settings-updated' => 'true' ) ) ) ) );
}

function ellada_map_name( $key ) {
	return esc_attr( ELLADA_MAP_OPTION . $key );
}

/* ═════════════════════ Επισκόπηση ═════════════════════ */

/**
 * Συγκεντρωτικά στοιχεία περιεχομένου ανά περιφέρεια και λίστα «χρειάζονται προσοχή».
 * Τα σημεία μπαίνουν σε περιφέρεια από τις συντεταγμένες, όπως στον χάρτη.
 */
function ellada_map_overview_data() {
	$regions      = ellada_map_region_names();
	$pref_regions = ellada_map_prefecture_regions();

	$rows = array();
	foreach ( $regions as $r ) {
		$rows[ $r ] = array(
			'region_page' => null,
			'prefs'       => array(),
			'prefs_total' => count( array_keys( $pref_regions, $r, true ) ),
			'munis'       => 0,
			'locations'   => 0,
			'points'      => 0,
		);
	}

	$attention = array();
	$add       = function ( $id, $issue ) use ( &$attention ) {
		$attention[] = array( $id, $issue );
	};

	// Τοποθεσίες
	$loc_ids = get_posts(
		array(
			'post_type'      => 'ellada_location',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	update_meta_cache( 'post', $loc_ids );
	$seen = array();
	foreach ( $loc_ids as $id ) {
		$level  = (string) get_post_meta( $id, 'map_level', true );
		$region = (string) get_post_meta( $id, 'geo_region', true );
		$pref   = (string) get_post_meta( $id, 'geo_prefecture', true );
		$muni   = (string) get_post_meta( $id, 'geo_municipality', true );

		$area = 'region' === $level ? $region : ( 'prefecture' === $level ? $pref : ( 'municipality' === $level ? $muni : '' ) );
		if ( '' === $area ) {
			$add( $id, 'Δεν έχει οριστεί περιοχή στον χάρτη — δεν θα εμφανιστεί.' );
			continue;
		}
		$dup_key = $level . ':' . $area;
		if ( isset( $seen[ $dup_key ] ) ) {
			$add( $id, sprintf( 'Ίδια περιοχή με την «%s» — ο χάρτης θα δείξει μόνο τη μία.', get_the_title( $seen[ $dup_key ] ) ) );
		} else {
			$seen[ $dup_key ] = $id;
		}
		if ( '' === trim( (string) get_post_meta( $id, 'short_description', true ) ) ) {
			$add( $id, 'Χωρίς σύντομη περιγραφή — η κάρτα στον χάρτη θα είναι άδεια.' );
		}
		if ( ! get_post_meta( $id, 'cover_image', true ) ) {
			$add( $id, 'Χωρίς εικόνα εξωφύλλου.' );
		}

		// Σε ποια περιφέρεια ανήκει (για το σύνολο).
		$reg = $region;
		if ( 'region' !== $level && $pref && isset( $pref_regions[ $pref ] ) ) {
			$reg = $pref_regions[ $pref ];
		}
		if ( ! isset( $rows[ $reg ] ) ) {
			continue;
		}
		$rows[ $reg ]['locations']++;
		if ( 'region' === $level ) {
			$rows[ $reg ]['region_page'] = $id;
		} elseif ( 'prefecture' === $level ) {
			$rows[ $reg ]['prefs'][ $pref ] = true;
		} else {
			$rows[ $reg ]['munis']++;
		}
	}

	// Τοπικά σημεία
	$pt_ids = get_posts(
		array(
			'post_type'      => 'ellada_point',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	update_meta_cache( 'post', $pt_ids );
	$coords = array();
	foreach ( $pt_ids as $id ) {
		$coords[ $id ] = array( get_post_meta( $id, 'lat', true ), get_post_meta( $id, 'lng', true ) );
	}
	// Η αντιστοίχιση σε περιφέρεια κοστίζει· κρατιέται μέχρι να αλλάξουν οι συντεταγμένες.
	$cache_key = ellada_map_geo_cache_key( 'pt_' . md5( wp_json_encode( $coords ) ) );
	$located   = get_transient( $cache_key );
	if ( ! is_array( $located ) ) {
		$located = array();
		foreach ( $coords as $id => $c ) {
			$located[ $id ] = is_numeric( $c[0] ) && is_numeric( $c[1] ) ? ellada_map_locate_region( $c[0], $c[1] ) : null;
		}
		set_transient( $cache_key, $located, DAY_IN_SECONDS );
	}
	foreach ( $pt_ids as $id ) {
		$reg = isset( $located[ $id ] ) ? $located[ $id ] : null;
		if ( ! is_numeric( $coords[ $id ][0] ) || ! is_numeric( $coords[ $id ][1] ) ) {
			$add( $id, 'Χωρίς συντεταγμένες — δεν θα εμφανιστεί.' );
		} elseif ( ! $reg ) {
			$add( $id, 'Οι συντεταγμένες είναι εκτός Ελλάδας — δεν θα εμφανιστεί.' );
		} elseif ( isset( $rows[ $reg ] ) ) {
			$rows[ $reg ]['points']++;
		}
		if ( '' === trim( (string) get_post_meta( $id, 'short_description', true ) ) ) {
			$add( $id, 'Χωρίς σύντομη περιγραφή.' );
		}
		if ( ! get_post_meta( $id, 'cover_image', true ) ) {
			$add( $id, 'Χωρίς εικόνα εξωφύλλου.' );
		}
	}

	return array(
		'rows'      => $rows,
		'attention' => $attention,
		'locations' => count( $loc_ids ),
		'points'    => count( $pt_ids ),
	);
}

function ellada_map_tab_overview() {
	$data    = ellada_map_overview_data();
	$drafts  = array(
		'ellada_location' => (int) wp_count_posts( 'ellada_location' )->draft,
		'ellada_point'    => (int) wp_count_posts( 'ellada_point' )->draft,
	);
	$covered = count(
		array_filter(
			$data['rows'],
			function ( $r ) {
				return $r['locations'] || $r['points'];
			}
		)
	);
	?>
	<div class="ellada-cards">
		<div class="ellada-card">
			<h3>Τοποθεσίες</h3>
			<div class="num"><?php echo esc_html( $data['locations'] ); ?></div>
			<div class="sub">δημοσιευμένες<?php echo $drafts['ellada_location'] ? esc_html( ' · ' . $drafts['ellada_location'] . ' πρόχειρες' ) : ''; ?></div>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ellada_location' ) ); ?>">+ Νέα τοποθεσία</a>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ellada_location' ) ); ?>">Όλες</a>
		</div>
		<div class="ellada-card">
			<h3>Τοπικά Σημεία</h3>
			<div class="num"><?php echo esc_html( $data['points'] ); ?></div>
			<div class="sub">δημοσιευμένα<?php echo $drafts['ellada_point'] ? esc_html( ' · ' . $drafts['ellada_point'] . ' πρόχειρα' ) : ''; ?></div>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ellada_point' ) ); ?>">+ Νέο σημείο</a>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ellada_point' ) ); ?>">Όλα</a>
		</div>
		<div class="ellada-card">
			<h3>Κάλυψη</h3>
			<div class="num"><?php echo esc_html( $covered ); ?> <span class="of">/ <?php echo esc_html( count( $data['rows'] ) ); ?></span></div>
			<div class="sub">περιφέρειες με περιεχόμενο</div>
			<div class="ellada-bar"><span style="width:<?php echo esc_attr( count( $data['rows'] ) ? round( 100 * $covered / count( $data['rows'] ) ) : 0 ); ?>%"></span></div>
		</div>
	</div>

	<h2>Ανά περιφέρεια</h2>
	<table class="widefat striped ellada-coverage">
		<thead>
			<tr>
				<th>Περιφέρεια</th>
				<th>Σελίδα περιφέρειας</th>
				<th>Νομοί με σελίδα</th>
				<th>Δήμοι με σελίδα</th>
				<th>Τοπικά σημεία</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $data['rows'] as $name => $r ) : ?>
			<tr class="<?php echo $r['locations'] || $r['points'] ? '' : 'is-empty'; ?>">
				<td>
					<strong><?php echo esc_html( $name ); ?></strong>
					<div class="row-actions">
						<a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'ellada_location', 'ellada_region' => $name ), admin_url( 'edit.php' ) ) ); ?>">Τοποθεσίες</a>
					</div>
				</td>
				<td>
					<?php if ( $r['region_page'] ) : ?>
						<span class="ellada-ok">✓</span> <a href="<?php echo esc_url( get_edit_post_link( $r['region_page'] ) ); ?>">Επεξεργασία</a>
					<?php else : ?>
						<span class="ellada-missing">—</span> <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ellada_location' ) ); ?>">Δημιουργία</a>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( count( $r['prefs'] ) . ' / ' . $r['prefs_total'] ); ?></td>
				<td><?php echo esc_html( $r['munis'] ); ?></td>
				<td><?php echo esc_html( $r['points'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<h2>Χρειάζονται προσοχή <span class="count">(<?php echo esc_html( count( $data['attention'] ) ); ?>)</span></h2>
	<?php if ( ! $data['attention'] ) : ?>
		<p class="ellada-ok-line">✓ Όλες οι δημοσιευμένες εγγραφές είναι πλήρεις.</p>
	<?php else : ?>
		<table class="widefat striped">
			<thead><tr><th>Εγγραφή</th><th>Τύπος</th><th>Τι λείπει</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( array_slice( $data['attention'], 0, 100 ) as $item ) : ?>
				<?php list( $id, $issue ) = $item; ?>
				<tr>
					<td><strong><?php echo esc_html( get_the_title( $id ) ); ?></strong></td>
					<td><?php echo esc_html( 'ellada_point' === get_post_type( $id ) ? 'Τοπικό σημείο' : 'Τοποθεσία' ); ?></td>
					<td><?php echo esc_html( $issue ); ?></td>
					<td style="text-align:right"><a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Επεξεργασία</a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( count( $data['attention'] ) > 100 ) : ?>
			<p class="description">Εμφανίζονται οι πρώτες 100.</p>
		<?php endif; ?>
	<?php endif; ?>

	<h2>Πώς τον βάζω σε σελίδα</h2>
	<div class="ellada-howto">
		<?php if ( did_action( 'elementor/loaded' ) ) : ?>
			<p><strong>Elementor:</strong> αναζητήστε το widget <strong>«Χάρτης Ελλάδας»</strong> και σύρετέ το στη σελίδα.
			Τίτλο, ύψος, αρχική περιφέρεια και υπόμνημα τα αλλάζετε στην καρτέλα <em>Περιεχόμενο</em>, το χρώμα στην καρτέλα <em>Στυλ</em>.</p>
		<?php endif; ?>
		<p><strong>Shortcode</strong> (σε οποιαδήποτε σελίδα, block ή widget κειμένου):</p>
		<table class="widefat striped">
			<tbody>
			<?php
			$examples = array(
				'[ellada_map]'                                   => 'Με τις ρυθμίσεις του plugin',
				'[ellada_map title="Γαστρονομικός χάρτης"]'      => 'Με δικό του τίτλο',
				'[ellada_map start_region="Κρήτη"]'              => 'Ανοίγει κατευθείαν στην Κρήτη',
				'[ellada_map types="restaurant,market" legend="true"]' => 'Μόνο εστιατόρια και αγορές, με υπόμνημα',
				'[ellada_map height="750" settlements="true"]'   => 'Πιο ψηλός, με οικισμούς',
			);
			foreach ( $examples as $code => $label ) :
				?>
				<tr>
					<td><code><?php echo esc_html( $code ); ?></code></td>
					<td><?php echo esc_html( $label ); ?></td>
					<td style="text-align:right"><button type="button" class="button button-small ellada-copy" data-copy="<?php echo esc_attr( $code ); ?>">Αντιγραφή</button></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/* ═════════════════════ Εμφάνιση ═════════════════════ */

function ellada_map_tab_look() {
	$s     = ellada_map_settings();
	$types = ellada_map_point_types();
	ellada_map_form_open( 'look' );
	?>
	<h2>Γενικά</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="ellada-title">Τίτλος</label></th>
			<td>
				<input id="ellada-title" class="regular-text" type="text" name="<?php echo ellada_map_name( '[title]' ); ?>" value="<?php echo esc_attr( $s['title'] ); ?>" placeholder="Εξερεύνηση">
				<p class="description">Εμφανίζεται πάνω αριστερά στον χάρτη. Κενό = «Εξερεύνηση».</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ellada-height">Ύψος σε υπολογιστή</label></th>
			<td>
				<input id="ellada-height" class="small-text" type="number" min="300" max="1600" step="10" name="<?php echo ellada_map_name( '[height]' ); ?>" value="<?php echo esc_attr( $s['height'] ); ?>"> px
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ellada-height-m">Ύψος σε κινητό</label></th>
			<td>
				<input id="ellada-height-m" class="small-text" type="number" min="300" max="1600" step="10" name="<?php echo ellada_map_name( '[height_mobile]' ); ?>" value="<?php echo esc_attr( $s['height_mobile'] ? $s['height_mobile'] : '' ); ?>" placeholder="<?php echo esc_attr( $s['height'] ); ?>"> px
				<p class="description">Οθόνες κάτω από 640px. Κενό = ίδιο με τον υπολογιστή.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ellada-radius">Στρογγυλάδα γωνιών</label></th>
			<td>
				<input id="ellada-radius" type="range" min="0" max="80" step="2" name="<?php echo ellada_map_name( '[radius]' ); ?>" value="<?php echo esc_attr( $s['radius'] ); ?>" oninput="this.nextElementSibling.textContent=this.value+' px'">
				<span><?php echo esc_html( $s['radius'] . ' px' ); ?></span>
				<p class="description">0 = τετράγωνες γωνίες. Προεπιλογή 40.</p>
			</td>
		</tr>
	</table>

	<h2>Χρώματα</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="ellada-accent">Χρώμα τονισμού</label></th>
			<td>
				<input id="ellada-accent" class="ellada-color" type="text" name="<?php echo ellada_map_name( '[accent]' ); ?>" value="<?php echo esc_attr( $s['accent'] ); ?>" data-default-color="#f97316">
				<p class="description">Επιλεγμένες περιοχές, κουμπιά, τίτλοι και πινέζες χωρίς δικό τους χρώμα.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ellada-bg">Φόντο</label></th>
			<td>
				<input id="ellada-bg" class="ellada-color" type="text" name="<?php echo ellada_map_name( '[background]' ); ?>" value="<?php echo esc_attr( $s['background'] ); ?>" data-default-color="#0a0a0a">
				<p class="description">Μόνο σκούρα χρώματα — τα κείμενα του χάρτη είναι λευκά.</p>
			</td>
		</tr>
	</table>

	<h2>Χρώμα πινέζας ανά τύπο σημείου</h2>
	<p>
		Κενό = το χρώμα τονισμού.
		<button type="button" class="button button-small ellada-suggest-colors" data-colors="<?php echo esc_attr( wp_json_encode( ellada_map_suggested_type_colors() ) ); ?>">Προτεινόμενα χρώματα</button>
		<button type="button" class="button button-small ellada-clear-colors">Καθαρισμός</button>
	</p>
	<table class="form-table ellada-type-colors" role="presentation">
		<?php foreach ( $types as $type => $label ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $label ); ?></th>
				<td><input class="ellada-color" data-type-color="<?php echo esc_attr( $type ); ?>" type="text" name="<?php echo ellada_map_name( '[type_colors][' . $type . ']' ); ?>" value="<?php echo esc_attr( isset( $s['type_colors'][ $type ] ) ? $s['type_colors'][ $type ] : '' ); ?>"></td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
	submit_button( 'Αποθήκευση' );
	echo '</form>';
}

/* ═════════════════════ Συμπεριφορά ═════════════════════ */

function ellada_map_tab_behaviour() {
	$s       = ellada_map_settings();
	$types   = ellada_map_point_types();
	$checked = $s['point_types'] ? $s['point_types'] : array_keys( $types );
	ellada_map_form_open( 'behaviour' );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="ellada-start">Αρχική προβολή</label></th>
			<td>
				<select id="ellada-start" name="<?php echo ellada_map_name( '[start_region]' ); ?>">
					<option value="">Όλη η Ελλάδα</option>
					<?php foreach ( ellada_map_region_names() as $r ) : ?>
						<option value="<?php echo esc_attr( $r ); ?>" <?php selected( $s['start_region'], $r ); ?>><?php echo esc_html( $r ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">Ο χάρτης ανοίγει κατευθείαν σε αυτή την περιφέρεια. Ο επισκέπτης μπορεί πάντα να γυρίσει στην «Ελλάδα».</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Περιοχές χωρίς περιεχόμενο</th>
			<td>
				<fieldset>
					<?php
					$opts = array(
						'normal'   => 'Κανονικές — όλες οι περιοχές ίδιες και κλικαριστές',
						'dim'      => 'Αχνές — φαίνονται πιο σβηστές, αλλά ανοίγουν',
						'disabled' => 'Αχνές και κλειδωμένες — δεν ανοίγουν',
					);
					foreach ( $opts as $v => $l ) :
						?>
						<label><input type="radio" name="<?php echo ellada_map_name( '[empty_areas]' ); ?>" value="<?php echo esc_attr( $v ); ?>" <?php checked( $s['empty_areas'], $v ); ?>> <?php echo esc_html( $l ); ?></label><br>
					<?php endforeach; ?>
				</fieldset>
				<p class="description">«Περιεχόμενο» = σελίδα τοποθεσίας ή τοπικά σημεία μέσα στην περιοχή.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Τύποι σημείων που εμφανίζονται</th>
			<td>
				<fieldset>
					<?php foreach ( $types as $type => $label ) : ?>
						<label><input type="checkbox" name="<?php echo ellada_map_name( '[point_types][]' ); ?>" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $checked, true ) ); ?>> <?php echo esc_html( $label ); ?></label><br>
					<?php endforeach; ?>
				</fieldset>
				<p class="description"><a href="#" class="ellada-types-all">Επιλογή όλων</a> · Οι υπόλοιποι κρύβονται από τον χάρτη (οι εγγραφές μένουν).</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Υπόμνημα / φίλτρο</th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo ellada_map_name( '[legend]' ); ?>" value="1" <?php checked( $s['legend'] ); ?>>
					Κουμπιά τύπων σημείων με το χρώμα τους, που ο επισκέπτης πατάει για να κρύψει ή να εμφανίσει σημεία
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Οικισμοί</th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo ellada_map_name( '[settlements]' ); ?>" value="1" <?php checked( $s['settlements'] ); ?>>
					Εμφάνιση οικισμών όταν ο επισκέπτης ανοίγει έναν δήμο
				</label>
				<p class="description">Φορτώνει ~460 KB επιπλέον, μόνο όταν χρειαστεί.</p>
			</td>
		</tr>
	</table>
	<?php
	submit_button( 'Αποθήκευση' );
	echo '</form>';
}

/* ═════════════════════ Κείμενα ═════════════════════ */

function ellada_map_tab_texts() {
	$s = ellada_map_settings();
	ellada_map_form_open( 'texts' );
	?>
	<p>Κενό πεδίο = το αρχικό κείμενο (φαίνεται αχνά μέσα στο πεδίο).</p>
	<table class="form-table" role="presentation">
		<?php foreach ( ellada_map_text_fields() as $key => $def ) : ?>
			<tr>
				<th scope="row"><label for="ellada-t-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $def[0] ); ?></label></th>
				<td><input id="ellada-t-<?php echo esc_attr( $key ); ?>" class="large-text" type="text" name="<?php echo ellada_map_name( '[texts][' . $key . ']' ); ?>" value="<?php echo esc_attr( isset( $s['texts'][ $key ] ) ? $s['texts'][ $key ] : '' ); ?>" placeholder="<?php echo esc_attr( $def[1] ); ?>"></td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
	submit_button( 'Αποθήκευση' );
	echo '</form>';
}
