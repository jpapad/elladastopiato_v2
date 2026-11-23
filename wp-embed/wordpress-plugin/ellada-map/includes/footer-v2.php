<?php
/**
 * Footer v2 (λειτουργία «footer_v2»).
 *
 * Πετρόλ κάρτα που «επιπλέει» με βαθιά σκιά: μεγάλη φράση + τρεις κάρτες δράσης, στήλες με συνδέσμους,
 * οι 13 περιφέρειες ως κουμπάκια, κάτω γραμμή με ©, νομικά και «↑».
 *
 * Το σημερινό footer (Elementor template 624) ΔΕΝ αλλάζει: όταν η λειτουργία είναι ανοιχτή απλώς κρύβεται
 * και το νέο τυπώνεται αμέσως μετά. Κλείσιμο της λειτουργίας = επιστροφή στο σημερινό.
 *
 * Προβολή χωρίς σύνδεση (για να το δει ο χρήστης στο κινητό): ?ellada_demo=footer — δείχνει μόνο το νέο footer.
 */

defined( 'ABSPATH' ) || exit;

function ellada_footer_v2() {
	if ( is_admin() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'footer' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'footer_v2' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_footer_v2() ) {
			$c[] = 'ellada-fv2';
		}
		return $c;
	}
);

function ellada_fv2_icon( $name ) {
	$i = array(
		'recipe' => '<path d="M4 11h16a8 8 0 0 1-16 0Z"/><path d="M8 7c0-1.5 1-2 1-3M12 7c0-1.5 1-2 1-3M16 7c0-1.5 1-2 1-3"/>',
		'place'  => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'heart'  => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
		'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'up'     => '<path d="M12 19V5M6 11l6-6 6 6"/>',
		'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
	);
	return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $i[ $name ] ?? '' ) . '</svg>';
}

function ellada_fv2_html() {
	$contact = function_exists( 'ellada_footer_page' ) ? ellada_footer_page( 'epikoinonia' ) : 0;
	$curl    = $contact ? get_permalink( $contact ) : '';
	$mine    = function_exists( 'ellada_footer_page' ) ? ellada_footer_page( 'oi-syntages-mou' ) : 0;
	$guide   = function_exists( 'ellada_footer_page' ) ? ellada_footer_page( 'odigos' ) : 0;
	$logo    = wp_get_attachment_image_url( 667, 'full' );

	/* Κάρτες δράσης */
	$cards = '';
	$acts  = array();
	if ( $curl ) {
		$acts[] = array( add_query_arg( 'thema', 'syntagi', $curl ) . '#form-field-message', 'recipe', 'Στείλτε μας μια συνταγή', 'Όπως τη φτιάχνετε στον τόπο σας' );
		$acts[] = array( add_query_arg( 'thema', 'topos', $curl ) . '#form-field-message', 'place', 'Προτείνετε έναν τόπο', 'Ένα χωριό, μια ταβέρνα, έναν παραγωγό' );
	}
	if ( $mine ) {
		$acts[] = array( get_permalink( $mine ), 'heart', 'Οι συνταγές μου', 'Όσες σημειώσατε με ♥' );
	}
	foreach ( $acts as list( $url, $icon, $title, $sub ) ) {
		$cards .= sprintf(
			'<a class="efv2-act" href="%1$s"><span class="efv2-act__i">%2$s</span><span class="efv2-act__t"><strong>%3$s</strong><span>%4$s</span></span><span class="efv2-act__go">%5$s</span></a>',
			esc_url( $url ),
			ellada_fv2_icon( $icon ),
			esc_html( $title ),
			esc_html( $sub ),
			ellada_fv2_icon( 'arrow' )
		);
	}

	/* Στήλες */
	$li      = fn( $u, $l ) => '<li><a href="' . esc_url( $u ) . '">' . esc_html( $l ) . '</a></li>';
	$explore = '';
	if ( $guide ) {
		$explore .= $li( get_permalink( $guide ), 'Ταξιδιωτικός οδηγός' );
	}
	$explore .= $li( home_url( '/#xartis' ), 'Ο χάρτης' );
	foreach ( array( 'ellada_recipe' => 'Όλες οι συνταγές', 'ellada_collection' => 'Συλλογές' ) as $type => $label ) {
		$u = post_type_exists( $type ) ? get_post_type_archive_link( $type ) : '';
		if ( $u ) {
			$explore .= $li( $u, $label );
		}
	}
	$recipes = '';
	foreach ( array( 'recipe_category', 'recipe_diet' ) as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true, 'orderby' => 'name' ) );
		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$recipes .= $li( get_term_link( $t ), $t->name );
		}
	}
	if ( $mine ) {
		$recipes .= $li( get_permalink( $mine ), 'Οι συνταγές μου ♥' );
	}
	$talk = '';
	if ( $curl ) {
		$talk .= $li( $curl, 'Επικοινωνία' );
	}
	$mail = '<a class="efv2-mail" href="mailto:info@elladastopiato.gr">' . ellada_fv2_icon( 'mail' ) . '<span>info@elladastopiato.gr</span></a>';

	/* Περιφέρειες ως κουμπάκια (με κουκκίδα όσες έχουν πλήρη οδηγό) */
	$chips = '';
	if ( function_exists( 'ellada_map_region_names' ) && function_exists( 'ellada_region_page' ) ) {
		foreach ( ellada_map_region_names() as $name ) {
			$page = ellada_region_page( $name );
			if ( ! $page ) {
				continue;
			}
			$on     = function_exists( 'ellada_guide_has_text' ) && ellada_guide_has_text( $page );
			$chips .= '<li><a class="efv2-chip' . ( $on ? ' is-on' : '' ) . '" href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a></li>';
		}
	}

	$legal = function_exists( 'ellada_footer_legal_html' ) ? ellada_footer_legal_html() : '';

	ob_start();
	?>
	<footer class="efv2" aria-label="Υποσέλιδο">
		<div class="efv2__card">
			<div class="efv2__top">
				<div class="efv2__brand">
					<?php if ( $logo ) : ?>
						<a class="efv2__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $logo ); ?>" alt="Ελλάδα στο Πιάτο" width="200" height="60" loading="lazy"></a>
					<?php endif; ?>
					<p class="efv2__claim">Ανακαλύπτουμε την Ελλάδα μέσα από τις <em>γεύσεις</em>, τις παραδόσεις και τους ανθρώπους της.</p>
				</div>
				<?php if ( $cards ) : ?>
					<div class="efv2__acts"><?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<?php endif; ?>
			</div>

			<div class="efv2__cols">
				<nav aria-label="Εξερεύνησε"><h2 class="efv2__h">Εξερεύνησε</h2><ul class="efv2__list"><?php echo $explore; // phpcs:ignore ?></ul></nav>
				<nav aria-label="Συνταγές"><h2 class="efv2__h">Συνταγές</h2><ul class="efv2__list"><?php echo $recipes; // phpcs:ignore ?></ul></nav>
				<div class="efv2__talk"><h2 class="efv2__h">Μιλήστε μας</h2><ul class="efv2__list"><?php echo $talk; // phpcs:ignore ?></ul><?php echo $mail; // phpcs:ignore ?></div>
				<?php if ( $chips ) : ?>
					<nav class="efv2__regions" aria-label="Περιφέρειες">
						<details class="efv2__rdet" open>
							<summary class="efv2__h">Περιφέρειες <span class="efv2__plus" aria-hidden="true">+</span></summary>
							<ul class="efv2__chips"><?php echo $chips; // phpcs:ignore ?></ul>
						</details>
					</nav>
				<?php endif; ?>
			</div>

			<div class="efv2__bottom">
				<p class="efv2__copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Ελλάδα στο Πιάτο · Φτιαγμένο με μεράκι στην Ελλάδα</p>
				<?php echo $legal; // phpcs:ignore ?>
				<a class="efv2__up" href="#top" aria-label="Επιστροφή στην κορυφή"><?php echo ellada_fv2_icon( 'up' ); // phpcs:ignore ?></a>
			</div>
		</div>
	</footer>
	<script>
	(function () {
		var f = document.querySelector('.efv2');
		if (!f) return;
		var up = f.querySelector('.efv2__up');
		if (up) up.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
		// Στο κινητό οι περιφέρειες ξεκινούν κλειστές.
		var d = f.querySelector('.efv2__rdet');
		if (d && window.matchMedia('(max-width: 767px)').matches) d.open = false;
		// Ήπια εμφάνιση όταν φτάνει στην οθόνη.
		if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			f.classList.add('is-pre');
			new IntersectionObserver(function (es, o) { if (es[0].isIntersecting) { f.classList.remove('is-pre'); o.disconnect(); } }, { rootMargin: '0px 0px -10% 0px' }).observe(f);
		}
	})();
	</script>
	<?php
	return ob_get_clean();
}

/* Τυπώνεται αμέσως μετά το (κρυμμένο) footer του Elementor· αλλιώς στην αρχή του wp_footer. */
function ellada_fv2_print() {
	static $done = false;
	if ( $done || ! ellada_footer_v2() ) {
		return;
	}
	$done = true;
	ellada_front_style();
	echo ellada_fv2_html(); // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'elementor/theme/after_do_footer', 'ellada_fv2_print' );
add_action( 'wp_footer', 'ellada_fv2_print', 0 );
