<?php
/**
 * Υλικά v2 — «μοντέρνα κάρτα» (καμβάς Δ): ποσότητα σε ετικέτα δεξιά, στρογγυλό τσεκ (#A8AD78), σημείωση κάτω από το υλικό,
 * «N από M στον πάγκο» με μπάρα, φίλτρο Όλα / Μου λείπουν, μερίδες σε κάψουλα με «Για N μερίδες», αλλεργιογόνα ως ετικέτες,
 * κύριο κουμπί «Λίστα για ψώνια · N υλικά». Χτίζεται με JS πάνω στο [ellada_recipe_ingredients] — μερίδες/τσεκ/λίστα μένουν ίδια.
 * Λειτουργία «ing_v2»· προβολή ?ellada_demo=ing2.
 *
 * @package ellada-map
 */

defined( 'ABSPATH' ) || exit;

function ellada_ing2() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'ing2' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'ing_v2' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( is_singular( 'ellada_recipe' ) && ellada_ing2() ) {
			$c[] = 'ellada-ing2';
		}
		return $c;
	}
);

add_action(
	'wp_footer',
	function () {
		if ( ! is_singular( 'ellada_recipe' ) || ! ellada_ing2() ) {
			return;
		}
		?>
		<script>
		(function () {
			var sec = document.querySelector('.ellada-ingredients.is-tools');
			if (!sec) return;
			var $ = function (s, r) { return (r || sec).querySelector(s); };
			var h2 = $('h2'), serv = $('.ellada-serv'), out = serv && serv.querySelector('output');
			// Κεφαλίδα: «Για N μερίδες» + τίτλος αριστερά, μερίδες δεξιά.
			var head = document.createElement('div'); head.className = 'ing2-head';
			var tl = document.createElement('div'); tl.className = 'ing2-title';
			var eb = document.createElement('span'); eb.className = 'ing2-eyebrow';
			h2.parentNode.insertBefore(head, h2); tl.appendChild(eb); tl.appendChild(h2); head.appendChild(tl);
			if (serv) head.appendChild(serv);
			function servText() { var n = out ? parseInt(out.textContent, 10) : 0; eb.textContent = n ? 'Για ' + n + (n === 1 ? ' μερίδα' : ' μερίδες') : ''; }
			servText(); if (out) new MutationObserver(servText).observe(out, { childList: true, characterData: true, subtree: true });
			// Πρόοδος + φίλτρο.
			var prog = document.createElement('div'); prog.className = 'ing2-prog';
			prog.innerHTML = '<div class="ing2-prog__t"><span><b></b> στον πάγκο</span><span class="ing2-miss"></span></div><div class="ing2-bar" role="progressbar" aria-label="Υλικά που έχετε" aria-valuemin="0"><i></i></div>';
			var seg = document.createElement('div'); seg.className = 'ing2-seg'; seg.setAttribute('role', 'group'); seg.setAttribute('aria-label', 'Προβολή υλικών');
			seg.innerHTML = '<button type="button" aria-pressed="true" data-f="all"></button><button type="button" aria-pressed="false" data-f="miss"></button>';
			var note = $('.ellada-serv__note'), anchor = note || head;
			anchor.parentNode.insertBefore(prog, anchor.nextSibling); prog.parentNode.insertBefore(seg, prog.nextSibling);
			var done = document.createElement('p'); done.className = 'ing2-done'; done.hidden = true; done.textContent = '✓ Τα έχετε όλα — καλό μαγείρεμα!';
			var lists = sec.querySelectorAll('ul'); var last = lists[lists.length - 1]; if (last) last.parentNode.insertBefore(done, last.nextSibling);
			// Αλλεργιογόνα ως ετικέτες.
			var al = $('.r6-allergens');
			if (al && al.querySelector('.ellada-alg')) { al.classList.add('ing2-als'); }
			else if (al) {
				var txt = al.textContent.replace(/^\s*Περιέχει:\s*/, '');
				al.innerHTML = '<span class="screen-reader-text">Περιέχει: </span>' + txt.split(/\s*,\s*/).map(function (t) { return '<span class="ing2-al">' + t.replace(/[<>&]/g, '') + '</span>'; }).join('');
				al.classList.add('ing2-als');
			}
			// Κουμπιά: πρώτα το κύριο, μετά τα δευτερεύοντα.
			var shop = $('.ellada-ingredients__shop[data-ellada-shop]') || $('.ellada-ingredients__shop');
			var cnt = document.createElement('span'); cnt.className = 'ing2-cnt';
			if (shop) { (shop.querySelector('span') || shop).appendChild(cnt); }
			var acts = document.createElement('div'); acts.className = 'ing2-acts';
			var clear = $('.ellada-ingredients__clear');
			if (shop) { shop.parentNode.insertBefore(acts, shop); acts.appendChild(shop); }
			var row = document.createElement('div'); row.className = 'ing2-row'; acts.appendChild(row);
			function moveAcct() { var a = $('.ellada-ingredients__shop.is-acct'); if (a && a.parentNode !== row) row.insertBefore(a, row.firstChild); }
			moveAcct(); new MutationObserver(moveAcct).observe(sec, { childList: true, subtree: true });
			if (clear) { clear.textContent = '↺'; clear.setAttribute('aria-label', 'Ξετσεκάρισμα όλων'); clear.title = 'Ξετσεκάρισμα όλων'; row.appendChild(clear); }
			if (al) acts.parentNode.insertBefore(al, acts);
			var boxes = Array.prototype.slice.call(sec.querySelectorAll('li input[type="checkbox"]'));
			var filter = 'all';
			function upd() {
				var have = boxes.filter(function (b) { return b.checked; }).length, total = boxes.length, miss = total - have;
				prog.querySelector('b').textContent = have + ' από ' + total;
				prog.querySelector('.ing2-miss').textContent = miss ? miss + (miss === 1 ? ' λείπει' : ' λείπουν') : 'τίποτα δεν λείπει';
				var bar = prog.querySelector('.ing2-bar'); bar.setAttribute('aria-valuemax', total); bar.setAttribute('aria-valuenow', have);
				bar.querySelector('i').style.width = (total ? Math.round(100 * have / total) : 0) + '%';
				seg.children[0].textContent = 'Όλα · ' + total; seg.children[1].textContent = 'Μου λείπουν · ' + miss;
				cnt.textContent = miss ? ' · ' + miss + (miss === 1 ? ' υλικό' : ' υλικά') : '';
				done.hidden = !(have === total && total > 0);
				if (clear) clear.hidden = !have;
			}
			seg.addEventListener('click', function (e) {
				var b = e.target.closest('button'); if (!b) return;
				filter = b.getAttribute('data-f');
				Array.prototype.forEach.call(seg.children, function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
				sec.classList.toggle('ing2-only-miss', filter === 'miss');
			});
			sec.addEventListener('change', function () { setTimeout(upd, 0); });
			if (clear) clear.addEventListener('click', function () { setTimeout(upd, 0); });
			upd();
			document.addEventListener('DOMContentLoaded', upd); window.addEventListener('load', upd); setTimeout(upd, 600);
			sec.classList.add('ing2-ready');
		})();
		</script>
		<?php
	},
	55
);
