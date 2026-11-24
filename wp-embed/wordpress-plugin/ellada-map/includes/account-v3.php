<?php
/**
 * Σελίδα «Ο λογαριασμός μου» v3 (λειτουργία «acct_v3»· προβολή ?ellada_demo=account3).
 *
 *   - Πετρόλ κεφαλίδα: αρχικό, όνομα, email, «Αποσύνδεση» πάνω δεξιά· οι αριθμοί είναι κουμπιά που ανοίγουν την καρτέλα τους.
 *   - «Πρώτα βήματα» για νέα μέλη (όνομα · πρώτο ♥ · πρώτη συλλογή · πρώτη πρόταση), κρύβεται όταν γίνουν ή με «Απόκρυψη».
 *   - «Το ταξίδι μου με το πιρούνι»: από πόσες περιφέρειες έχετε μαγειρέψει (μπάρα + κουμπάκια περιφερειών· χωρίς χάρτη).
 *   - Καρτέλες: Αγαπημένες · Τις έφτιαξα · Λίστα για ψώνια · Συλλογές · Προτάσεις · Ρυθμίσεις (#agapimenes … στη διεύθυνση).
 *   - Συνταγές σε μικρές γραμμές (φωτογραφία, τίτλος, περιφέρεια, «Αφαίρεση»)· άδειες καρτέλες με 3 προτάσεις και ♡.
 */

defined( 'ABSPATH' ) || exit;

function ellada_acct3() {
	if ( ! function_exists( 'ellada_accounts' ) || ! ellada_accounts() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'account3' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'acct_v3' );
}

function ellada_acct3_ico( $k, $size = 20 ) {
	$p = array(
		'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
		'check' => '<path d="m5 12.5 4.2 4.2L19 7"/>',
		'cart'  => '<path d="M5 7h14l-1.5 12h-11L5 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>',
		'fold'  => '<path d="M3.5 7.5A2 2 0 0 1 5.5 5.5h4l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2v-10Z"/>',
		'mail'  => '<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
		'gear'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
		'fork'  => '<path d="M7 3v8a2 2 0 0 0 4 0V3M9 11v10M17 3c-1.7 0-3 2-3 5s1.3 4 3 4v9"/>',
		'out'   => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h11"/>',
	);
	return '<svg viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ( $p[ $k ] ?? '' ) . '</svg>';
}

/** Μικρή γραμμή συνταγής. $act: remove-fav | remove-made | remove-list | add-fav */
function ellada_acct3_row( $id, $act, $extra = '' ) {
	if ( 'ellada_recipe' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		return '';
	}
	$img    = get_the_post_thumbnail( $id, 'thumbnail', array( 'class' => 'ellada-arow__img', 'loading' => 'lazy', 'alt' => '' ) );
	$region = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $id ) : '';
	$total  = (int) get_post_meta( $id, 'prep_time', true ) + (int) get_post_meta( $id, 'cook_time', true );
	$meta   = array_filter( array( $region, $total ? $total . ' λεπτά' : '' ) );
	$title  = get_the_title( $id );
	$labels = array(
		'remove-fav'  => array( 'Αφαίρεση', 'Αφαίρεση από τις αγαπημένες: ' ),
		'remove-made' => array( 'Αφαίρεση', 'Αφαίρεση από «Τις έφτιαξα»: ' ),
		'remove-list' => array( 'Αφαίρεση', 'Αφαίρεση από τη συλλογή: ' ),
		'add-fav'     => array( '♡ Αγαπημένη', 'Στις αγαπημένες: ' ),
	);
	$lb = $labels[ $act ];
	return sprintf(
		'<li class="ellada-arow" data-id="%1$d"><a class="ellada-arow__link" href="%2$s"><span class="ellada-arow__th">%3$s</span><span class="ellada-arow__txt"><b>%4$s</b><small>%5$s</small></span></a><button type="button" class="ellada-arow__act%6$s" data-act="%7$s"%8$s aria-label="%9$s">%10$s</button></li>',
		$id,
		esc_url( get_permalink( $id ) ),
		$img ? $img : '<img class="ellada-arow__img is-ph" src="' . esc_url( plugins_url( 'public/plate-mark.svg', ELLADA_MAP_FILE ) ) . '" alt="">',
		esc_html( $title ),
		esc_html( implode( ' · ', $meta ) ),
		'add-fav' === $act ? ' is-add' : '',
		esc_attr( $act ),
		$extra, // phpcs:ignore WordPress.Security.EscapeOutput
		esc_attr( $lb[1] . $title ),
		esc_html( $lb[0] )
	);
}

/** 3 προτάσεις (με φωτογραφία πρώτα), εκτός όσων υπάρχουν ήδη. */
function ellada_acct3_suggest( $exclude ) {
	$ids = get_posts(
		array(
			'post_type'      => 'ellada_recipe',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post__not_in'   => array_map( 'intval', $exclude ),
		)
	);
	usort( $ids, fn( $a, $b ) => (int) has_post_thumbnail( $b ) - (int) has_post_thumbnail( $a ) );
	$feat = function_exists( 'ellada_featured_recipe_id' ) ? (int) ellada_featured_recipe_id() : 0;
	if ( $feat && in_array( $feat, $ids, true ) ) {
		$ids = array_merge( array( $feat ), array_diff( $ids, array( $feat ) ) );
	}
	$out = '';
	foreach ( array_slice( $ids, 0, 3 ) as $id ) {
		$out .= ellada_acct3_row( $id, 'add-fav' );
	}
	return $out ? '<p class="ellada-acct3__sug-t">Ξεκινήστε από εδώ</p><ul class="ellada-arows">' . $out . '</ul>' : '';
}

/** Περιφέρειες: [όνομα χάρτη => [τίτλος, url, μαγειρεμένη]] */
function ellada_acct3_journey( $made ) {
	if ( ! function_exists( 'ellada_map_region_names' ) ) {
		return array();
	}
	$done = array();
	foreach ( $made as $id ) {
		$r = function_exists( 'ellada_recipe_region' ) ? ellada_recipe_region( $id ) : '';
		if ( $r ) {
			$done[ $r ] = true;
		}
	}
	$in_use  = function_exists( 'ellada_recipe_regions_in_use' ) ? ellada_recipe_regions_in_use() : array();
	$archive = get_post_type_archive_link( 'ellada_recipe' );
	$out     = array();
	foreach ( ellada_map_region_names() as $name ) {
		$page  = function_exists( 'ellada_region_page' ) ? ellada_region_page( $name ) : 0;
		$title = $page ? get_the_title( $page ) : $name;
		$url   = in_array( $name, $in_use, true ) ? add_query_arg( 'perioxi', rawurlencode( $name ), $archive ) : ( $page ? get_permalink( $page ) : '' );
		$out[ $name ] = array( $title, $url, ! empty( $done[ $name ] ) );
	}
	return $out;
}

function ellada_acct3_html( $u, $wel = '' ) {
	$uid    = $u->ID;
	$d      = ellada_acct_get( $uid );
	$name   = ellada_acct_name( $u );
	$member = ellada_acct_is_member( $u );
	$shop   = function_exists( 'ellada_acct2_shop' ) ? ellada_acct2_shop( $uid ) : array();
	$lists  = function_exists( 'ellada_acct2_lists' ) ? ellada_acct2_lists( $uid ) : array();
	$subs   = function_exists( 'ellada_acct2_submissions' ) ? ellada_acct2_submissions( $uid ) : array();
	$left   = 0;
	foreach ( $shop as $r ) {
		foreach ( $r['items'] as $it ) {
			$left += empty( $it['d'] ) ? 1 : 0;
		}
	}
	$demo     = isset( $_GET['ellada_demo'] ) && 'account3' === $_GET['ellada_demo']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$privacy  = get_page_by_path( 'politiki-aporritou' );
	$priv_url = $privacy ? get_permalink( $privacy ) : home_url( '/politiki-aporritou/' );
	$contact  = get_page_by_path( 'epikoinonia', OBJECT, 'page' );
	$c_url    = $contact ? add_query_arg( 'thema', 'syntagi', get_permalink( $contact ) ) : '';
	$recipes  = get_post_type_archive_link( 'ellada_recipe' );
	$letter   = mb_strtoupper( mb_substr( $name ? $name : $u->user_email, 0, 1 ) );
	$logout   = wp_logout_url( home_url( '/' ) );
	$journey  = ellada_acct3_journey( $d['made'] );
	$jdone    = count( array_filter( $journey, fn( $r ) => $r[2] ) );
	$jtotal   = count( $journey );

	/* Πρώτα βήματα */
	$steps = array(
		array( 'Πείτε μας πώς να σας λέμε', '' !== $name, '#rythmiseis' ),
		array( 'Βάλτε ♥ σε μια συνταγή', (bool) $d['favs'], $recipes ),
		array( 'Φτιάξτε μια συλλογή', (bool) $lists, $recipes ),
		array( 'Στείλτε μας μια συνταγή του τόπου σας', (bool) $subs, $c_url ),
	);
	$sdone      = count( array_filter( $steps, fn( $s ) => $s[1] ) );
	$show_steps = $member && $sdone < count( $steps ) && ! get_user_meta( $uid, 'ellada_onb_hide', true );

	$tabs = array(
		'agapimenes' => array( 'heart', 'Αγαπημένες', count( $d['favs'] ), 'favs' ),
		'eftiaxa'    => array( 'check', 'Τις έφτιαξα', count( $d['made'] ), 'made' ),
		'psonia'     => array( 'cart', 'Λίστα για ψώνια', $left, 'shop' ),
		'syllogi'    => array( 'fold', 'Συλλογές', count( $lists ), 'lists' ),
		'protaseis'  => array( 'mail', 'Προτάσεις', count( $subs ), 'subs' ),
		'rythmiseis' => array( 'gear', 'Ρυθμίσεις', null, 'set' ),
	);

	ob_start();
	?>
	<header class="ellada-acct3__hero">
		<span class="ellada-acct3__av" aria-hidden="true"><?php echo esc_html( $letter ); ?></span>
		<div class="ellada-acct3__who">
			<p class="ecv3-eyebrow"><?php echo 'new' === $wel ? 'Καλώς ήρθατε!' : 'Ο λογαριασμός μου'; ?></p>
			<h2 class="ellada-acct__hi">Γεια σας<?php echo $name ? ', ' . esc_html( $name ) : ''; ?></h2>
			<p class="ellada-acct__mail"><?php echo esc_html( $u->user_email ); ?></p>
		</div>
		<a class="ellada-acct3__out" href="<?php echo esc_url( $logout ); ?>"><?php echo ellada_acct3_ico( 'out', 16 ); // phpcs:ignore ?> Αποσύνδεση</a>
		<ul class="ellada-acct3__stats">
			<?php
			foreach ( array( 'agapimenes', 'eftiaxa', 'psonia', 'syllogi' ) as $k ) :
				$t     = $tabs[ $k ];
				$words = array(
					'agapimenes' => 'αγαπημένες',
					'eftiaxa'    => 'τις έφτιαξα',
					'psonia'     => 'να αγοράσω',
					'syllogi'    => 1 === $t[2] ? 'συλλογή' : 'συλλογές',
				);
				?>
				<li><a href="#<?php echo esc_attr( $k ); ?>" data-goto="<?php echo esc_attr( $k ); ?>"><b data-acct-count="<?php echo esc_attr( $t[3] ); ?>"><?php echo (int) $t[2]; ?></b><span><?php echo esc_html( $words[ $k ] ); ?></span></a></li>
			<?php endforeach; ?>
		</ul>
	</header>

	<?php if ( 'new' === $wel ) : ?>
		<p class="ellada-acct__note" role="status">Ο λογαριασμός σας είναι έτοιμος. Ό,τι είχατε σημειώσει σε αυτή τη συσκευή (♥ και «Την έφτιαξα») πέρασε στον λογαριασμό σας.</p>
	<?php endif; ?>

	<div class="ellada-acct3__top<?php echo $show_steps ? '' : ' is-one'; ?>">
		<?php if ( $show_steps ) : ?>
		<section class="ellada-acct3__steps" aria-labelledby="acct3-steps" data-acct-steps>
			<div class="ellada-acct3__boxh"><h3 id="acct3-steps">Πρώτα βήματα</h3><span><?php echo (int) $sdone; ?>/<?php echo count( $steps ); ?></span></div>
			<ol>
				<?php foreach ( $steps as $s ) : ?>
					<li class="<?php echo $s[1] ? 'is-done' : ''; ?>"><span class="ellada-acct3__tick" aria-hidden="true"><?php echo $s[1] ? '✓' : ''; ?></span><?php echo $s[1] || ! $s[2] ? '<span>' . esc_html( $s[0] ) . '</span>' : '<a href="' . esc_url( $s[2] ) . '"' . ( '#' === $s[2][0] ? ' data-goto="' . esc_attr( substr( $s[2], 1 ) ) . '"' : '' ) . '>' . esc_html( $s[0] ) . ' →</a>'; ?><?php echo $s[1] ? '<span class="ellada-sr"> (έγινε)</span>' : ''; ?></li>
				<?php endforeach; ?>
			</ol>
			<button type="button" class="ellada-acct__link" data-steps-hide>Απόκρυψη</button>
		</section>
		<?php endif; ?>

		<?php if ( $jtotal ) : ?>
		<section class="ellada-acct3__journey" aria-labelledby="acct3-journey">
			<div class="ellada-acct3__boxh"><h3 id="acct3-journey"><?php echo ellada_acct3_ico( 'fork', 20 ); // phpcs:ignore ?> Το ταξίδι μου με το πιρούνι</h3></div>
			<p class="ellada-acct3__jtxt"><?php echo $jdone ? sprintf( 'Έχετε μαγειρέψει από <b>%d</b> %s από τις %d.', (int) $jdone, 1 === $jdone ? 'περιφέρεια' : 'περιφέρειες', (int) $jtotal ) : 'Πατήστε <b>«Το έφτιαξα!»</b> όταν μαγειρεύετε μια συνταγή και δείτε την Ελλάδα να «γεμίζει».'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<div class="ellada-acct3__bar" role="progressbar" aria-label="Περιφέρειες που έχετε μαγειρέψει" aria-valuemin="0" aria-valuemax="<?php echo (int) $jtotal; ?>" aria-valuenow="<?php echo (int) $jdone; ?>"><i style="width:<?php echo esc_attr( round( 100 * $jdone / max( 1, $jtotal ) ) ); ?>%"></i></div>
			<ul class="ellada-acct3__regions">
				<?php foreach ( $journey as $r ) : ?>
					<li<?php echo $r[2] ? ' class="is-done"' : ''; ?>><?php echo $r[1] ? '<a class="' . ( $r[2] ? 'is-done' : '' ) . '" href="' . esc_url( $r[1] ) . '">' : '<span class="' . ( $r[2] ? 'is-done' : '' ) . '">'; ?><?php echo $r[2] ? '<span aria-hidden="true">✓ </span>' : ''; ?><?php echo esc_html( $r[0] ); ?><?php echo $r[2] ? '<span class="ellada-sr"> (μαγειρέψατε)</span>' : ''; ?><?php echo $r[1] ? '</a>' : '</span>'; ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>
	</div>

	<div class="ellada-acct3__tabs" role="tablist" aria-label="Ο λογαριασμός μου">
		<?php
		$first = true;
		foreach ( $tabs as $k => $t ) :
			?>
			<button type="button" role="tab" id="tab-<?php echo esc_attr( $k ); ?>" aria-controls="<?php echo esc_attr( $k ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" tabindex="<?php echo $first ? '0' : '-1'; ?>"><?php echo ellada_acct3_ico( $t[0], 18 ); // phpcs:ignore ?><span><?php echo esc_html( $t[1] ); ?></span><?php echo null !== $t[2] ? '<em data-acct-count="' . esc_attr( $t[3] ) . '">' . (int) $t[2] . '</em>' : ''; ?></button>
			<?php
			$first = false;
		endforeach;
		?>
	</div>

	<?php /* ── Αγαπημένες ── */ ?>
	<section class="ellada-acct3__panel" role="tabpanel" id="agapimenes" aria-labelledby="tab-agapimenes" tabindex="0">
		<?php
		$rows = '';
		foreach ( $d['favs'] as $id ) {
			$rows .= ellada_acct3_row( $id, 'remove-fav' );
		}
		?>
		<ul class="ellada-arows" data-acct3-list="favs"><?php echo $rows; // phpcs:ignore ?></ul>
		<div class="ellada-acct3__empty" data-acct3-empty="favs"<?php echo $rows ? ' hidden' : ''; ?>>
			<p>Δεν έχετε ακόμα αγαπημένες. Πατήστε <strong>♡</strong> σε όποια συνταγή σας αρέσει — ή ξεκινήστε από αυτές:</p>
			<?php echo ellada_acct3_suggest( $d['favs'] ); // phpcs:ignore ?>
		</div>
	</section>

	<?php /* ── Τις έφτιαξα ── */ ?>
	<section class="ellada-acct3__panel" role="tabpanel" id="eftiaxa" aria-labelledby="tab-eftiaxa" tabindex="0" hidden>
		<?php
		$rows = '';
		foreach ( $d['made'] as $id ) {
			$rows .= ellada_acct3_row( $id, 'remove-made' );
		}
		?>
		<ul class="ellada-arows" data-acct3-list="made"><?php echo $rows; // phpcs:ignore ?></ul>
		<div class="ellada-acct3__empty" data-acct3-empty="made"<?php echo $rows ? ' hidden' : ''; ?>>
			<p>Όταν μαγειρέψετε μια συνταγή, πατήστε <strong>«Το έφτιαξα!»</strong> στο τέλος των βημάτων. Μια ιδέα για να ξεκινήσετε:</p>
			<?php echo ellada_acct3_suggest( $d['favs'] ); // phpcs:ignore ?>
		</div>
	</section>

	<?php /* ── Λίστα για ψώνια (το σενάριο του account-v2 τη ζωγραφίζει) ── */ ?>
	<section class="ellada-acct3__panel" role="tabpanel" id="psonia" aria-labelledby="tab-psonia" tabindex="0" hidden>
		<div class="ellada-shop" data-acct-shop></div>
		<div class="ellada-acct3__empty" data-acct-shop-empty hidden><p>Η λίστα είναι άδεια. Σε κάθε συνταγή, πατήστε <strong>«＋ Στη λίστα μου»</strong> κάτω από τα υλικά.</p></div>
	</section>

	<?php /* ── Συλλογές ── */ ?>
	<section class="ellada-acct3__panel" role="tabpanel" id="syllogi" aria-labelledby="tab-syllogi" tabindex="0" hidden>
		<?php if ( ! $lists ) : ?>
			<div class="ellada-acct3__empty"><p>Φτιάξτε δικές σας ομάδες συνταγών («Για το Πάσχα», «Γρήγορα», «Της γιαγιάς»…): σε κάθε συνταγή πατήστε <strong>«＋ Συλλογή»</strong> δίπλα στο ♡. <a href="<?php echo esc_url( $recipes ); ?>">Δείτε τις συνταγές →</a></p></div>
		<?php else : ?>
			<div class="ellada-lists" data-acct-lists>
			<?php foreach ( $lists as $i => $l ) : ?>
				<details class="ellada-lists__one" data-i="<?php echo (int) $i; ?>"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary><span class="ellada-lists__n"><?php echo esc_html( $l['n'] ); ?></span> <span class="ellada-lists__c"><?php echo count( $l['ids'] ); ?> <?php echo 1 === count( $l['ids'] ) ? 'συνταγή' : 'συνταγές'; ?></span></summary>
					<ul class="ellada-arows">
					<?php
					foreach ( $l['ids'] as $id ) {
						echo ellada_acct3_row( $id, 'remove-list', ' data-list="' . (int) $i . '"' ); // phpcs:ignore
					}
					?>
					</ul>
					<p class="ellada-lists__act"><button type="button" class="ellada-acct__btn2" data-rename>Μετονομασία</button> <button type="button" class="ellada-acct__link" data-remove>Διαγραφή συλλογής</button></p>
				</details>
			<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<?php /* ── Προτάσεις ── */ ?>
	<section class="ellada-acct3__panel" role="tabpanel" id="protaseis" aria-labelledby="tab-protaseis" tabindex="0" hidden>
		<?php if ( ! $subs ) : ?>
			<div class="ellada-acct3__empty"><p>Έχετε μια συνταγή του τόπου σας; <?php echo $c_url ? '<a href="' . esc_url( $c_url ) . '">Στείλτε τη μας →</a>' : ''; ?> Όσο είστε συνδεδεμένοι, θα βλέπετε εδώ την πορεία της — και αν θέλετε, θα γράψουμε το όνομά σας όταν δημοσιευτεί.</p></div>
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

	<?php /* ── Ρυθμίσεις ── */ ?>
	<section class="ellada-acct3__panel ellada-acct__set" role="tabpanel" id="rythmiseis" aria-labelledby="tab-rythmiseis" tabindex="0" hidden>
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
	</section>
	<?php
	return ob_get_clean();
}

/* «Απόκρυψη» των πρώτων βημάτων. */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ellada/v1',
			'/me/onb-hide',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'callback'            => function () {
					update_user_meta( get_current_user_id(), 'ellada_onb_hide', 1 );
					return rest_ensure_response( array( 'ok' => true ) );
				},
			)
		);
	}
);

add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_acct3() && function_exists( 'ellada_acct_is_page' ) && ellada_acct_is_page() && is_user_logged_in() ) {
			$c[] = 'ellada-acct3-on';
		}
		return $c;
	}
);

/* Καρτέλες, αφαίρεση, ♡ στις προτάσεις, απόκρυψη πρώτων βημάτων. */
add_action(
	'wp_footer',
	function () {
		if ( ! ellada_acct3() || ! is_user_logged_in() || ! function_exists( 'ellada_acct_is_page' ) || ! ellada_acct_is_page() ) {
			return;
		}
		$cfg = array(
			'hide'  => rest_url( 'ellada/v1/me/onb-hide' ),
			'me'    => rest_url( 'ellada/v1/me' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		);
		?>
		<script>
		(function () {
			var C = <?php echo wp_json_encode( $cfg ); ?>;
			var root = document.querySelector('.ellada-acct'); if (!root) return;
			var tabs = [].slice.call(root.querySelectorAll('[role=tab]'));
			function show(id, focus, push) {
				var ok = tabs.some(function (t) { return t.getAttribute('aria-controls') === id; }); if (!ok) id = tabs[0].getAttribute('aria-controls');
				tabs.forEach(function (t) { var on = t.getAttribute('aria-controls') === id; t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1;
					document.getElementById(t.getAttribute('aria-controls')).hidden = !on; if (on && focus) t.focus(); if (on) t.scrollIntoView({ block: 'nearest', inline: 'center' }); });
				if (push) history.replaceState(null, '', '#' + id);
			}
			tabs.forEach(function (t, i) {
				t.addEventListener('click', function () { show(t.getAttribute('aria-controls'), false, true); });
				t.addEventListener('keydown', function (e) { var k = e.key, n = null;
					if (k === 'ArrowRight') n = tabs[(i + 1) % tabs.length]; else if (k === 'ArrowLeft') n = tabs[(i - 1 + tabs.length) % tabs.length]; else if (k === 'Home') n = tabs[0]; else if (k === 'End') n = tabs[tabs.length - 1];
					if (n) { e.preventDefault(); show(n.getAttribute('aria-controls'), true, true); } });
			});
			root.addEventListener('click', function (e) {
				var g = e.target.closest('[data-goto]'); if (!g) return; e.preventDefault(); show(g.getAttribute('data-goto'), true, true);
				var bar = root.querySelector('.ellada-acct3__tabs'); if (bar) bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
			var h = (location.hash || '').slice(1); show(h || tabs[0].getAttribute('aria-controls'), false, false);
			window.addEventListener('hashchange', function () { show(location.hash.slice(1), false, false); });

			/* localStorage: ο συγχρονισμός (account.php) στέλνει τις αλλαγές στον λογαριασμό μετά το κλικ */
			function get(k) { try { return JSON.parse(localStorage.getItem(k) || 'null'); } catch (er) { return null; } }
			function set(k, v) { try { if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, JSON.stringify(v)); } catch (er) {} }
			function count(k, n) { root.querySelectorAll('[data-acct-count=' + k + ']').forEach(function (el) { el.textContent = n; }); }
			function empty(k) { var ul = root.querySelector('[data-acct3-list=' + k + ']'), em = root.querySelector('[data-acct3-empty=' + k + ']'); if (ul && em) em.hidden = ul.children.length > 0; }
			root.addEventListener('click', function (e) {
				var b = e.target.closest('.ellada-arow__act'); if (!b) return;
				var li = b.closest('.ellada-arow'), id = +li.getAttribute('data-id'), act = b.getAttribute('data-act');
				if (act === 'remove-fav') { var f = (get('ellada-favs') || []).filter(function (x) { return +x !== id; }); set('ellada-favs', f.length ? f : null); li.remove(); count('favs', f.length); empty('favs'); }
				else if (act === 'remove-made') { set('ellada-made-' + id, null); li.remove(); var m = root.querySelectorAll('[data-acct3-list=made] .ellada-arow').length; count('made', m); empty('made'); }
				else if (act === 'add-fav') { var f2 = get('ellada-favs') || []; if (f2.indexOf(id) === -1) f2.unshift(id); set('ellada-favs', f2.slice(0, 60)); b.textContent = '♥ Στις αγαπημένες'; b.disabled = true; count('favs', f2.length);
					var ul = root.querySelector('[data-acct3-list=favs]'); if (ul && !ul.querySelector('[data-id="' + id + '"]')) { var c = li.cloneNode(true); var cb = c.querySelector('.ellada-arow__act'); cb.className = 'ellada-arow__act'; cb.setAttribute('data-act', 'remove-fav'); cb.disabled = false; cb.textContent = 'Αφαίρεση'; ul.appendChild(c); } }
				else if (act === 'remove-list' && window.elladaAcct2) { var A = window.elladaAcct2, i = +b.getAttribute('data-list'), l = A.lists[i]; if (!l) return;
					l.ids = l.ids.filter(function (x) { return x !== id; }); li.remove(); var det = root.querySelector('.ellada-lists__one[data-i="' + i + '"] .ellada-lists__c'); if (det) det.textContent = l.ids.length + (l.ids.length === 1 ? ' συνταγή' : ' συνταγές'); A.save({ lists: A.lists }); }
			});

			/* Πρώτα βήματα: απόκρυψη */
			var hide = root.querySelector('[data-steps-hide]');
			if (hide) hide.addEventListener('click', function () {
				var box = root.querySelector('[data-acct-steps]'); box.remove(); var top = root.querySelector('.ellada-acct3__top'); if (top) top.classList.add('is-one');
				fetch(C.hide, { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': C.nonce } });
			});

			/* Μετά τον πρώτο συγχρονισμό μιας νέας συσκευής: αν ήρθαν νέες συνταγές, ανανέωση (μία φορά). */
			document.addEventListener('ellada:acct-synced', function () {
				fetch(C.me, { credentials: 'same-origin', headers: { 'X-WP-Nonce': C.nonce } }).then(function (r) { return r.json(); }).then(function (d) {
					var shown = [].map.call(root.querySelectorAll('[data-acct3-list=favs] .ellada-arow'), function (li) { return +li.getAttribute('data-id'); });
					var extra = (d.favs || []).some(function (x) { return shown.indexOf(x) === -1; });
					try { if (extra && !sessionStorage.getItem('ellada-acct3-r')) { sessionStorage.setItem('ellada-acct3-r', '1'); location.reload(); } } catch (er) {}
				}).catch(function () {});
			});
		})();
		</script>
		<?php
	},
	12
);
