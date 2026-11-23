<?php
/**
 * Επικοινωνία v2: πιο σύγχρονη φόρμα (λειτουργία «contact_v2»· προεπισκόπηση ?ellada_preview=1).
 *
 * Η φόρμα μένει η ίδια φόρμα του Elementor (email, spam, απόρρητο ίδια). Αλλάζουν:
 *   - «Θέμα» ως κουμπιά με εικονίδια αντί για λίστα (η λίστα μένει κρυφή και στέλνεται κανονικά)
 *   - κεφαλίδα φόρμας, πεδία με γέμισμα και καθαρό focus, μήνυμα που μεγαλώνει μόνο του, μετρητής χαρακτήρων
 *   - οδηγία και παράδειγμα στο μήνυμα ανάλογα με το θέμα
 *   - σήμανση λάθους μόνο αφού ο επισκέπτης αγγίξει το πεδίο · κουμπί με βελάκι και κατάσταση «Αποστολή…»
 *   - μήνυμα επιτυχίας σαν κάρτα
 * Τα χρώματα έρχονται από τις μεταβλητές --ellada-*, άρα ακολουθούν την παλέτα του site.
 */

defined( 'ABSPATH' ) || exit;

function ellada_contact_v2() {
	// Όταν ισχύει η v3 (δική μας φόρμα), η v2 που «ντύνει» τη φόρμα του Elementor δεν χρειάζεται.
	if ( function_exists( 'ellada_contact_v3' ) && ellada_contact_v3() ) {
		return false;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'contact_v2' ) && is_page( 'epikoinonia' );
}

add_filter(
	'body_class',
	function ( $c ) {
		if ( ellada_contact_v2() ) {
			$c[] = 'ellada-cv2';
		}
		return $c;
	}
);

/** Θέμα => [σύντομη ετικέτα, εικονίδιο, οδηγία, παράδειγμα] */
function ellada_contact_v2_topics() {
	$icons = array();
	foreach ( ellada_contact_topics() as $t ) {
		$icons[ $t[0] ] = $t[3];
	}
	$other = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.2 2.4c-.5.2-.7.6-.7 1.1v.5"/><path d="M12 17h.01"/></svg>';
	return array(
		'Πρόταση συνταγής'        => array( 'Συνταγή', $icons['Πρόταση συνταγής'] ?? $other, 'Πείτε μας τη συνταγή όπως τη φτιάχνετε· τα υπόλοιπα θα τα γράψουμε μαζί.', 'Π.χ. «Τα σαρμαδάκια της γιαγιάς μου από τα Ζαγόρια. Τα φτιάχνουμε τα Χριστούγεννα με…»' ),
		'Τοπικό σημείο στον χάρτη' => array( 'Σημείο στον χάρτη', $icons['Τοπικό σημείο στον χάρτη'] ?? $other, 'Ποιο είναι, πού βρίσκεται και γιατί αξίζει να το ξέρουν όλοι.', 'Π.χ. «Το τυροκομείο του κυρ-Νίκου στο Μέτσοβο, ανοιχτό κάθε πρωί…»' ),
		'Διόρθωση / λάθος'        => array( 'Διόρθωση', $icons['Διόρθωση / λάθος'] ?? $other, 'Σε ποια σελίδα είναι το λάθος και ποιο είναι το σωστό;', 'Π.χ. «Στη σελίδα του Ρεθύμνου γράφει… ενώ το σωστό είναι…»' ),
		'Συνεργασία'              => array( 'Συνεργασία', $icons['Συνεργασία'] ?? $other, 'Πείτε μας λίγα λόγια για εσάς και την ιδέα σας.', 'Π.χ. «Είμαι φωτογράφος στην Κέρκυρα και θα ήθελα…»' ),
		'Άλλο'                    => array( 'Κάτι άλλο', $other, '', 'Τι θέλετε να μας πείτε;' ),
	);
}

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_contact_v2() ) {
			return;
		}
		$topics = array();
		foreach ( ellada_contact_v2_topics() as $value => $t ) {
			$topics[] = array( 'v' => $value, 'l' => $t[0], 'i' => $t[1], 'h' => $t[2], 'p' => $t[3] );
		}
		?>
		<script>
		(function () {
			var T = <?php echo wp_json_encode( $topics, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?>;
			function init() {
				var form = document.querySelector('.ellada-contact-form form.elementor-form');
				if (!form || form.dataset.v2) return;
				form.dataset.v2 = '1';
				var sel = form.querySelector('#form-field-topic');
				var msg = form.querySelector('#form-field-message');
				var topicGroup = sel && sel.closest('.elementor-field-group');

				// Κεφαλίδα
				var head = document.createElement('div');
				head.className = 'ecv2-head';
				head.innerHTML = '<p class="ecv2-eyebrow">Γράψτε μας</p><h2 class="ecv2-title">Στείλτε μας μήνυμα</h2><p class="ecv2-sub">Διαβάζουμε κάθε μήνυμα και απαντάμε συνήθως μέσα σε 2–3 εργάσιμες.</p>';
				form.insertBefore(head, form.firstChild);

				// Θέμα ως κουμπιά
				if (sel && topicGroup) {
					var fs = document.createElement('fieldset');
					fs.className = 'ecv2-topics elementor-field-group elementor-column elementor-col-100';
					var lg = document.createElement('legend');
					lg.textContent = 'Για ποιο θέμα;';
					fs.appendChild(lg);
					var row = document.createElement('div');
					row.className = 'ecv2-chips';
					T.forEach(function (t, i) {
						var id = 'ecv2-t' + i;
						var lab = document.createElement('label');
						lab.className = 'ecv2-chip';
						lab.htmlFor = id;
						var r = document.createElement('input');
						r.type = 'radio'; r.name = 'ecv2_topic'; r.id = id; r.value = t.v;
						var ic = document.createElement('span'); ic.className = 'ecv2-chip__i'; ic.setAttribute('aria-hidden', 'true'); ic.innerHTML = t.i;
						var tx = document.createElement('span'); tx.textContent = t.l;
						lab.appendChild(r); lab.appendChild(ic); lab.appendChild(tx);
						r.addEventListener('change', function () { if (r.checked) { sel.value = t.v; sel.dispatchEvent(new Event('change', { bubbles: true })); apply(); } });
						row.appendChild(lab);
					});
					fs.appendChild(row);
					topicGroup.parentNode.insertBefore(fs, topicGroup);
					topicGroup.classList.add('ecv2-hidden');
				}

				// Οδηγία κάτω από το μήνυμα + μετρητής
				var hint = document.createElement('div');
				hint.className = 'ecv2-hint';
				var hintText = document.createElement('span');
				var count = document.createElement('span');
				count.className = 'ecv2-count';
				hint.appendChild(hintText); hint.appendChild(count);
				if (msg) {
					msg.setAttribute('maxlength', '3000');
					msg.parentNode.appendChild(hint);
					var grow = function () { msg.style.height = 'auto'; msg.style.height = Math.min(msg.scrollHeight + 2, 520) + 'px'; count.textContent = msg.value.length ? msg.value.length + ' / 3000' : ''; };
					msg.addEventListener('input', grow);
				}

				function apply() {
					var cur = sel ? sel.value : '';
					form.querySelectorAll('.ecv2-chip input').forEach(function (r) { r.checked = r.value === cur; r.parentNode.classList.toggle('is-on', r.checked); });
					var t = T.filter(function (x) { return x.v === cur; })[0];
					if (t && msg) { msg.placeholder = t.p; hintText.textContent = t.h; }
				}
				apply();
				// Οι κάρτες στα αριστερά και το ?thema= αλλάζουν το θέμα από άλλο script.
				document.addEventListener('change', function (e) { if (e.target === sel) apply(); });
				document.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('.ellada-contact__card')) setTimeout(apply, 0); });
				setTimeout(apply, 50);

				// Λάθη μόνο μετά από επαφή με το πεδίο
				form.querySelectorAll('input, textarea').forEach(function (f) {
					f.addEventListener('blur', function () { f.classList.add('ecv2-touched'); });
				});
				form.addEventListener('submit', function () { form.querySelectorAll('input, textarea').forEach(function (f) { f.classList.add('ecv2-touched'); }); }, true);

				// Κουμπί
				var btn = form.querySelector('button[type="submit"] .elementor-button-text');
				if (btn) { btn.textContent = 'Αποστολή μηνύματος'; btn.insertAdjacentHTML('afterend', '<svg class="ecv2-arrow" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>'); }
			}
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
		})();
		</script>
		<?php
	},
	30
);
