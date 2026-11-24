<?php
/**
 * Προσβασιμότητα v1 (λειτουργία «a11y_v1»· προβολή χωρίς σύνδεση ?ellada_demo=a11y).
 *
 * Μικρές διορθώσεις από τον έλεγχο WCAG 2.2 AA (24/11):
 *   - αντίθεση: χρυσό/γκρι πάνω σε πετρόλ λίγο πιο ανοιχτό, χρυσοί τίτλοι σε ανοιχτό φόντο σε ώχρα,
 *     κεραμιδί μικρά γράμματα πιο σκούρα, σκούρο πετρόλ πάνω σε χρυσό (CSS: body.ellada-a11y)
 *   - λογότυπο-σύνδεσμος με όνομα και περίγραμμα εστίασης
 *   - «Μετάβαση στο περιεχόμενο» → κύρια περιοχή (main) της σελίδας
 *   - διπλή κεφαλίδα (banner μέσα σε banner)
 *   - χάρτης: καθαρό περίγραμμα εστίασης, «Παράλειψη χάρτη», «Οι περιφέρειες σε λίστα»
 *   - footer: σύνδεσμοι ≥ 24px στο κινητό
 * (Η παύση της κορδέλας, ο κρυφός τίτλος H2 στις Συνταγές και τα λάθη της φόρμας είναι στα αντίστοιχα αρχεία.)
 */

defined( 'ABSPATH' ) || exit;

function ellada_a11y() {
	if ( is_admin() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'a11y' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'a11y_v1' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_a11y() ) {
			$c[] = 'ellada-a11y';
		}
		return $c;
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_a11y() ) {
			return;
		}
		$guide = get_page_by_path( 'odigos', OBJECT, 'page' );
		$list  = $guide && 'publish' === $guide->post_status ? get_permalink( $guide ) . '#ellada-gtabs' : '';
		?>
		<script>
		(function () {
			function run() {
				// Λογότυπο-σύνδεσμοι: όνομα για αναγνώστες οθόνης.
				document.querySelectorAll('a[data-interaction-id$="-logo"]').forEach(function (a) {
					if (!a.getAttribute('aria-label')) a.setAttribute('aria-label', 'Ελλάδα στο Πιάτο – Αρχική');
					a.classList.add('ellada-logo-link');
				});
				// Κύρια περιοχή + στόχος του «Μετάβαση στο περιεχόμενο».
				var main = document.querySelector('main, [role=main]');
				if (!main) {
					main = document.querySelector('[data-elementor-type]:not([data-elementor-type=header]):not([data-elementor-type=footer]):not([data-elementor-type=popup])');
					if (main) main.setAttribute('role', 'main');
				}
				if (main) {
					if (!document.getElementById('content')) main.id = main.id || 'content';
					if (!main.hasAttribute('tabindex')) main.setAttribute('tabindex', '-1');
				}
				var skip = document.querySelector('.skip-link');
				if (skip && main && main.id) skip.setAttribute('href', '#' + main.id);
				// Κεφαλίδα μέσα σε κεφαλίδα: η εσωτερική δεν είναι ξεχωριστό «banner».
				document.querySelectorAll('header header').forEach(function (h) { h.setAttribute('role', 'none'); });
				// Χάρτης: εστίαση, παράλειψη, εναλλακτική σε λίστα.
				document.querySelectorAll('.ellada-map').forEach(function (host, i) {
					if (host.dataset.a11y) return; host.dataset.a11y = '1';
					var after = document.createElement('span'); after.id = 'ellada-after-map-' + i; after.tabIndex = -1; after.className = 'ellada-sr';
					after.textContent = 'Τέλος χάρτη';
					var skipMap = document.createElement('a'); skipMap.href = '#' + after.id; skipMap.className = 'ellada-skipmap'; skipMap.textContent = 'Παράλειψη χάρτη';
					host.parentNode.insertBefore(skipMap, host);
					var list = <?php echo wp_json_encode( $list ); ?>;
					var tail = document.createElement('div'); tail.className = 'ellada-maplist';
					if (list && !document.getElementById('ellada-gtabs')) {
						var a = document.createElement('a'); a.href = list; a.textContent = 'Οι περιφέρειες σε λίστα →'; tail.appendChild(a);
					}
					tail.appendChild(after);
					host.parentNode.insertBefore(tail, host.nextSibling);
					var css = 'path[role=button]:focus-visible,[role=button]:focus-visible{outline:none!important;stroke:#F6F2E9!important;stroke-width:3px!important;paint-order:stroke;filter:drop-shadow(0 0 4px rgba(246,242,233,.9))}button:focus-visible,a:focus-visible{outline:3px solid #DAAB67!important;outline-offset:2px}';
					function inject() { if (host.shadowRoot && !host.shadowRoot.querySelector('#ellada-a11y-css')) { var s = document.createElement('style'); s.id = 'ellada-a11y-css'; s.textContent = css; host.shadowRoot.appendChild(s); return true; } return !!(host.shadowRoot); }
					if (!inject()) { var n = 0, t = setInterval(function () { if (inject() || ++n > 40) clearInterval(t); }, 250); }
				});
			}
			/* Αντίθεση: όπου ένα κείμενο δεν φτάνει το όριο (4,5:1 ή 3:1 για μεγάλα γράμματα) πάνω στο πραγματικό του φόντο,
			   η ίδια απόχρωση σκουραίνει (σε ανοιχτό φόντο) ή ανοίγει (σε σκούρο) μόνο όσο χρειάζεται. */
			function rgb(s) { var m = s && s.match(/rgba?\(([^)]+)\)/); if (!m) return null; var p = m[1].split(',').map(parseFloat); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 }; }
			function lum(c) { return [c.r, c.g, c.b].map(function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }).reduce(function (s, v, i) { return s + v * [0.2126, 0.7152, 0.0722][i]; }, 0); }
			function ratio(a, b) { var x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }
			function bgOf(el) { for (var e = el; e && e.nodeType === 1; e = e.parentElement || (e.getRootNode && e.getRootNode().host)) { var cs = getComputedStyle(e); if (cs.backgroundImage && cs.backgroundImage !== 'none' && !/gradient/.test(cs.backgroundImage)) return null; var c = rgb(cs.backgroundColor); if (c && c.a > 0.85) return c; } return { r: 246, g: 242, b: 233, a: 1 }; }
			function fixContrast(root) {
				var els = root.querySelectorAll('h1,h2,h3,h4,p,span,a,dt,dd,li,label,strong,em,button,figcaption,small,legend,td,th,div');
				els.forEach(function (el) {
					if (el.dataset && el.dataset.a11yC) return;
					var own = false; for (var n = el.firstChild; n; n = n.nextSibling) if (n.nodeType === 3 && n.nodeValue.trim()) { own = true; break; }
					if (!own) return;
					var cs = getComputedStyle(el); if (cs.visibility === 'hidden' || cs.display === 'none') return;
					var fg = rgb(cs.color), bg = bgOf(el); if (!fg || !bg || fg.a < 0.5) return;
					var size = parseFloat(cs.fontSize), bold = parseInt(cs.fontWeight, 10) >= 700;
					var need = (size >= 24 || (bold && size >= 18.66)) ? 3.1 : 4.6;
					if (ratio(fg, bg) >= need - 0.1) return;
					function walk(to) { for (var i = 1; i <= 100; i++) { var t = i / 100, x = { r: fg.r + (to - fg.r) * t, g: fg.g + (to - fg.g) * t, b: fg.b + (to - fg.b) * t }; if (ratio(x, bg) >= need) return { c: x, t: t }; } return null; }
					var a = walk(0), b = walk(255), best = a && b ? (a.t <= b.t ? a : b) : (a || b);
					if (!best) return; var c = best.c;
					el.style.setProperty('color', 'rgb(' + Math.round(c.r) + ',' + Math.round(c.g) + ',' + Math.round(c.b) + ')', 'important');
					if (el.dataset) el.dataset.a11yC = '1';
				});
			}
			function contrastAll() { fixContrast(document); document.querySelectorAll('.ellada-map').forEach(function (h) { if (h.shadowRoot) fixContrast(h.shadowRoot); }); }
			function start() { run(); contrastAll(); setTimeout(contrastAll, 1500); setTimeout(contrastAll, 4000); }
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
		})();
		</script>
		<?php
	},
	40
);
