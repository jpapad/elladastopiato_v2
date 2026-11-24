<?php
/**
 * 🔊 «Διάβασέ μου τα βήματα» στις συνταγές (λειτουργία «read_aloud»· προβολή χωρίς σύνδεση ?ellada_demo=read).
 *
 * Ο browser διαβάζει φωναχτά τα βήματα με τη δική του ελληνική φωνή (Web Speech API — χωρίς υπηρεσίες τρίτων,
 * τίποτα δεν στέλνεται πουθενά). Κουμπιά: Προηγούμενο · Παύση/Συνέχεια · Επόμενο · Τέλος. Το βήμα που διαβάζεται
 * τονίζεται και έρχεται στο κέντρο της οθόνης. Αν ο browser δεν έχει φωνή, το κουμπί δεν εμφανίζεται.
 */

defined( 'ABSPATH' ) || exit;

function ellada_read_aloud() {
	if ( is_admin() || ! is_singular( 'ellada_recipe' ) ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'read' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'read_aloud' );
}

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_read_aloud() ) {
			return;
		}
		$title = get_the_title();
		?>
		<script>
		(function () {
			if (!('speechSynthesis' in window) || !window.SpeechSynthesisUtterance) return;
			var sec = document.querySelector('.ellada-steps'); if (!sec) return;
			var items = [].slice.call(sec.querySelectorAll('ol > li, ul > li')).filter(function (li) { return li.querySelector('.ellada-steps__body'); });
			if (!items.length) return;
			var TITLE = <?php echo wp_json_encode( $title ); ?>;
			var synth = window.speechSynthesis, voice = null, i = -1, playing = false, paused = false;
			function pickVoice() { var v = synth.getVoices(); voice = v.filter(function (x) { return /^el(-|_|$)/i.test(x.lang); })[0] || null; }
			pickVoice(); if (synth.onvoiceschanged !== undefined) synth.onvoiceschanged = pickVoice;

			var bar = document.createElement('div'); bar.className = 'ellada-ra'; bar.setAttribute('role', 'group'); bar.setAttribute('aria-label', 'Ανάγνωση των βημάτων');
			bar.innerHTML =
				'<button type="button" class="ellada-ra__start"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 9.5v5h3.5L12 18.5v-13L7.5 9.5H4Z"/><path d="M15.5 9a4 4 0 0 1 0 6M18 6.5a7.5 7.5 0 0 1 0 11"/></svg><span>Διάβασέ μου τα βήματα</span></button>' +
				'<div class="ellada-ra__ctrl" hidden>' +
				'<button type="button" data-a="prev" aria-label="Προηγούμενο βήμα">‹ Προηγ.</button>' +
				'<button type="button" data-a="pause" class="is-main" aria-label="Παύση">❚❚</button>' +
				'<button type="button" data-a="next" aria-label="Επόμενο βήμα">Επόμ. ›</button>' +
				'<button type="button" data-a="stop" aria-label="Τέλος ανάγνωσης">✕</button>' +
				'<span class="ellada-ra__now" aria-live="polite"></span></div>';
			var h = sec.querySelector('h2'); (h && h.nextSibling) ? sec.insertBefore(bar, h.nextSibling) : sec.insertBefore(bar, sec.firstChild);
			var ctrl = bar.querySelector('.ellada-ra__ctrl'), now = bar.querySelector('.ellada-ra__now'), pbtn = bar.querySelector('[data-a=pause]');

			function textOf(li) { var b = li.querySelector('.ellada-steps__body'); return (b ? b.textContent : li.textContent).replace(/\s+/g, ' ').trim(); }
			function mark() { items.forEach(function (li, k) { li.classList.toggle('is-reading', k === i); }); }
			function say(k, intro) {
				synth.cancel(); if (k < 0 || k >= items.length) { finish(k >= items.length); return; }
				i = k; paused = false; pbtn.textContent = '❚❚'; pbtn.setAttribute('aria-label', 'Παύση'); mark();
				items[i].scrollIntoView({ behavior: 'smooth', block: 'center' });
				now.textContent = 'Βήμα ' + (i + 1) + ' από ' + items.length;
				var u = new SpeechSynthesisUtterance((intro ? intro + ' ' : '') + 'Βήμα ' + (i + 1) + '. ' + textOf(items[i]));
				u.lang = 'el-GR'; if (voice) u.voice = voice; u.rate = 0.95;
				u.onend = function () { if (playing && !paused && i === k) setTimeout(function () { if (playing && !paused && i === k) say(k + 1); }, 900); };
				synth.speak(u);
			}
			function finish(done) {
				synth.cancel(); playing = false; paused = false; i = -1; mark(); ctrl.hidden = true; bar.querySelector('.ellada-ra__start').hidden = false;
				if (done) { var u = new SpeechSynthesisUtterance('Τέλος. Καλή όρεξη!'); u.lang = 'el-GR'; if (voice) u.voice = voice; synth.speak(u); }
			}
			bar.querySelector('.ellada-ra__start').addEventListener('click', function () {
				playing = true; this.hidden = true; ctrl.hidden = false; say(0, TITLE + '.'); pbtn.focus();
			});
			ctrl.addEventListener('click', function (e) {
				var b = e.target.closest('button'); if (!b) return; var a = b.getAttribute('data-a');
				if (a === 'prev') say(Math.max(0, i - 1));
				else if (a === 'next') say(i + 1);
				else if (a === 'stop') finish(false);
				else if (a === 'pause') {
					if (!paused) { paused = true; synth.pause(); if (/Chrome|Android/.test(navigator.userAgent)) synth.cancel(); b.textContent = '▶'; b.setAttribute('aria-label', 'Συνέχεια'); }
					else { paused = false; if (synth.paused) synth.resume(); else say(i); b.textContent = '❚❚'; b.setAttribute('aria-label', 'Παύση'); }
				}
			});
			window.addEventListener('pagehide', function () { synth.cancel(); });
		})();
		</script>
		<?php
	},
	45
);
