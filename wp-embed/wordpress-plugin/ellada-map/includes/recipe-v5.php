<?php
/**
 * Σελίδα συνταγής v5 — από τον έλεγχο της κριτικής (24/11) (λειτουργία «recipe_v5»· προβολή ?ellada_demo=recipe5).
 *
 *   1. Το (κρυμμένο) footer του Elementor δεν τυπώνεται καθόλου όταν υπάρχει το νέο footer (όλο το site).
 *   2. «Την έφτιαξαν X άτομα» μόνο από 5 και πάνω (αλλιώς «Γίνετε από τους πρώτους…»).
 *   3. ⏱ Χρονόμετρο: «5 λεπτά», «1 ώρα» μέσα στα βήματα γίνονται κουμπιά.
 *   4. «Λίστα για ψώνια» / «Στη λίστα μου»: μόνο όσα δεν έχετε τσεκάρει.
 *   5. «Δείτε ακόμα»: ίδια περιφέρεια → ίδια κατηγορία → πιο πρόσφατες (blocks.php).
 *   6. Γλωσσάρι: τοπικές λέξεις (μιτάτο, κουκουβάγια, ξινομυζήθρα…) με εξήγηση στο πάτημα.
 *   7. Κινητό: μπάρα κάτω «Μαγειρεύω τώρα» όσο διαβάζετε την εκτέλεση.
 *   + Θερμίδες ανά μερίδα (πεδίο «calories», «περίπου») — και στο schema (NutritionInformation).
 */

defined( 'ABSPATH' ) || exit;

function ellada_rv5() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['ellada_demo'] ) && 'recipe5' === $_GET['ellada_demo'] ) {
		return true;
	}
	return function_exists( 'ellada_feature' ) && ellada_feature( 'recipe_v5' );
}

/* ═════════════════════ Πεδίο θερμίδων ═════════════════════ */

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'      => 'group_ellada_nutrition',
				'title'    => 'Διατροφή',
				'fields'   => array(
					array(
						'key'          => 'field_ellada_calories',
						'label'        => 'Θερμίδες ανά μερίδα (περίπου)',
						'name'         => 'calories',
						'type'         => 'number',
						'min'          => 0,
						'max'          => 3000,
						'append'       => 'kcal',
						'instructions' => 'Εκτίμηση από τα υλικά και τις μερίδες. Φαίνεται ως «≈ … kcal ανά μερίδα» και πάει στο Google.',
					),
				),
				'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'ellada_recipe' ) ) ),
				'position' => 'side',
			)
		);
	}
);

function ellada_rv5_calories( $id ) {
	return (int) get_post_meta( $id, 'calories', true );
}

/* Γραμμή θερμίδων στο τέλος των υλικών. */
add_filter(
	'do_shortcode_tag',
	function ( $output, $tag, $attr ) {
		if ( 'ellada_recipe_ingredients' !== $tag || ! ellada_rv5() ) {
			return $output;
		}
		$id   = function_exists( 'ellada_ctx_id' ) ? ellada_ctx_id( (array) $attr ) : get_the_ID();
		$kcal = ellada_rv5_calories( $id );
		if ( ! $kcal ) {
			return $output;
		}
		$line = '<p class="ellada-kcal"><span aria-hidden="true">🔥</span> ≈ <b>' . (int) $kcal . ' kcal</b> ανά μερίδα <small>(εκτίμηση)</small></p>';
		$pos  = strrpos( $output, '</section>' );
		return false === $pos ? $output . $line : substr_replace( $output, $line . '</section>', $pos, strlen( '</section>' ) );
	},
	30,
	3
);

add_filter(
	'ellada_recipe_schema',
	function ( $data, $id ) {
		$kcal = ellada_rv5_calories( $id );
		if ( $kcal && ellada_rv5() ) {
			$data['nutrition'] = array(
				'@type'    => 'NutritionInformation',
				'calories' => $kcal . ' calories',
			);
		}
		return $data;
	},
	20,
	2
);

/* ═════════════════════ 1. Χωρίς το κρυμμένο footer του Elementor ═════════════════════ */

add_action(
	'elementor/theme/before_do_footer',
	function () {
		if ( ellada_rv5() && function_exists( 'ellada_footer_v2' ) && ellada_footer_v2() ) {
			$GLOBALS['ellada_rv5_ob'] = ob_start();
		}
	},
	1
);
add_action(
	'elementor/theme/after_do_footer',
	function () {
		if ( ! empty( $GLOBALS['ellada_rv5_ob'] ) ) {
			$GLOBALS['ellada_rv5_ob'] = false;
			ob_end_clean();
		}
	},
	1
);

/* ═════════════════════ 2. Μετρητής «Το έφτιαξα» ═════════════════════ */

add_filter(
	'ellada_made_min',
	function ( $min ) {
		return ellada_rv5() ? 5 : $min;
	}
);

/* ═════════════════════ 6. Γλωσσάρι ═════════════════════ */

/** [ρίζα λέξης (regex, χωρίς τόνους-ευαισθησία) => [λέξη, εξήγηση]] */
function ellada_glossary() {
	return apply_filters(
		'ellada_glossary',
		array(
			'μιτ[άα]τ'           => array( 'μιτάτο', 'Πέτρινο καταφύγιο των βοσκών στα βουνά της Κρήτης, όπου έμεναν το καλοκαίρι με τα κοπάδια και έφτιαχναν τα τυριά τους.' ),
			'κουκουβ[άα]γι'      => array( 'κουκουβάγια', 'Τοπικό όνομα του ντάκου σε μέρη της Κρήτης.' ),
			'ξινομυζ[ήη]θρ'      => array( 'ξινομυζήθρα', 'Μαλακό, ελαφρώς ξινό κρητικό τυρί από πρόβειο ή γίδινο γάλα — προϊόν ΠΟΠ.' ),
			'παξιμ[άα]δι[α]? κριθαρι' => array( 'παξιμάδι κριθαριού', 'Ψωμί από κριθάρι, ψημένο δύο φορές ώσπου να στεγνώσει· κρατάει μήνες χωρίς να χαλάσει.' ),
			'ανθ[όο]τυρ'         => array( 'ανθότυρο', 'Λευκό τυρί από τον ορό του γάλακτος· φρέσκο είναι μαλακό, ενώ το ξερό τρίβεται σαν σκληρό τυρί.' ),
			'γραβι[έε]ρ'         => array( 'γραβιέρα', 'Σκληρό, κίτρινο τυρί με γλυκιά, βουτυρένια γεύση· οι πιο γνωστές είναι της Κρήτης και της Νάξου.' ),
			'μπο[ύυ]κοβ'         => array( 'μπούκοβο', 'Νιφάδες από ξερή καυτερή πιπεριά.' ),
			'κονκασ[έε]'         => array( 'κονκασέ', 'Ντομάτα χωρίς φλούδα και σπόρους, κομμένη σε μικρά κυβάκια.' ),
		)
	);
}

/* ═════════════════════ Σενάριο: χρονόμετρο, γλωσσάρι, μπάρα κινητού ═════════════════════ */

add_action(
	'wp_head',
	function () {
		if ( ellada_rv5() && is_singular( 'ellada_recipe' ) ) {
			echo "<script>window.elladaMadeMin=5;window.elladaShopMissing=true;</script>\n";
		}
	},
	1
);

add_action(
	'wp_footer',
	function () {
		if ( ! ellada_rv5() || ! is_singular( 'ellada_recipe' ) ) {
			return;
		}
		$gloss = array();
		foreach ( ellada_glossary() as $re => $g ) {
			$gloss[] = array( $re, $g[0], $g[1] );
		}
		?>
		<script>
		(function () {
			var G = <?php echo wp_json_encode( $gloss, JSON_UNESCAPED_UNICODE ); ?>;
			function toast(msg) { var t = document.querySelector('.ellada-toast'); if (!t) { t = document.createElement('div'); t.className = 'ellada-toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); } t.textContent = msg; t.classList.add('is-on'); clearTimeout(t._t); t._t = setTimeout(function () { t.classList.remove('is-on'); }, 3600); }
			function textNodes(root) { var w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, { acceptNode: function (n) { return n.nodeValue.trim() && !n.parentNode.closest('button,a,.ellada-gl,.ellada-tmr-btn,script,style') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT; } }), out = []; while (w.nextNode()) out.push(w.currentNode); return out; }
			function wrap(node, m, el) { var after = node.splitText(m.index); after.nodeValue = after.nodeValue.slice(m[0].length); node.parentNode.insertBefore(el, after); return el; }

			/* ── 6. Γλωσσάρι: η πρώτη εμφάνιση κάθε λέξης (η εξήγηση σε ξεχωριστό αναδυόμενο, ώστε να μη διαβάζεται από το 🔊) ── */
			var areas = [].slice.call(document.querySelectorAll('.ellada-rstory, .ellada-steps__body, .ellada-tips, .ellada-rvars'));
			var pop = document.createElement('div'); pop.className = 'ellada-gl__tip'; pop.id = 'ellada-gl-pop'; pop.setAttribute('role', 'note'); pop.hidden = true; document.body.appendChild(pop);
			var openB = null;
			function closePop() { if (openB) openB.setAttribute('aria-expanded', 'false'); openB = null; pop.hidden = true; }
			G.forEach(function (g, gi) {
				var re = new RegExp('(^|[^Α-Ωα-ωΆ-ώ])(' + g[0] + '[Α-Ωα-ωΆ-ώ]*)', 'i'), done = false;
				areas.forEach(function (a) { if (done) return; textNodes(a).some(function (n) {
					var m = re.exec(n.nodeValue); if (!m) return false;
					var b = document.createElement('button'); b.type = 'button'; b.className = 'ellada-gl'; b.textContent = m[2]; b.setAttribute('data-g', gi);
					b.setAttribute('aria-expanded', 'false'); b.setAttribute('aria-controls', 'ellada-gl-pop'); b.setAttribute('aria-label', m[2] + ' — τι σημαίνει;');
					wrap(n, { index: m.index + m[1].length, 0: m[2] }, b); done = true; return true; }); });
			});
			document.addEventListener('click', function (e) {
				var b = e.target.closest('.ellada-gl');
				if (!b) { if (!e.target.closest('#ellada-gl-pop')) closePop(); return; }
				if (openB === b) { closePop(); return; }
				closePop(); var g = G[+b.getAttribute('data-g')];
				pop.innerHTML = '<b></b> <span></span>'; pop.querySelector('b').textContent = g[1] + ':'; pop.querySelector('span').textContent = g[2];
				pop.hidden = false; openB = b; b.setAttribute('aria-expanded', 'true');
				var r = b.getBoundingClientRect(), w = Math.min(320, window.innerWidth - 24);
				pop.style.width = w + 'px'; pop.style.left = Math.max(12, Math.min(window.innerWidth - w - 12, r.left + r.width / 2 - w / 2)) + window.scrollX + 'px';
				pop.style.top = r.bottom + window.scrollY + 8 + 'px';
			});
			document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && openB) { var b = openB; closePop(); b.focus(); } });
			window.addEventListener('resize', closePop);

			/* ── 3. Χρονόμετρο ── */
			var TRE = /(\d+(?:[.,]\d+)?)(?:\s*[–-]\s*(\d+))?\s*(λεπτ[άόώ]?[α-ωά-ώ]*|ώρ[α-ωά-ώ]*|δευτερ[όο]λεπτ[α-ωά-ώ]*)/i;
			document.querySelectorAll('.ellada-steps__body').forEach(function (body) {
				var li = body.closest('li'), step = li ? [].indexOf.call(li.parentNode.children, li) + 1 : 0;
				textNodes(body).some(function (n) {
					var m = TRE.exec(n.nodeValue); if (!m) return false;
					var v = parseFloat(m[1].replace(',', '.')), u = m[3].toLowerCase(), sec = /^ώρ/.test(u) ? v * 3600 : (/^δευτ/.test(u) ? v : v * 60);
					if (!sec || sec > 6 * 3600 || sec < 10) return false;
					var b = document.createElement('button'); b.type = 'button'; b.className = 'ellada-tmr-btn'; b.setAttribute('data-sec', Math.round(sec)); b.setAttribute('data-step', step);
					b.setAttribute('aria-label', 'Χρονόμετρο ' + m[0] + (step ? ' για το βήμα ' + step : ''));
					b.innerHTML = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9.5 2.5h5"/></svg><span></span>';
					b.querySelector('span').textContent = m[0];
					wrap(n, m, b); return true;
				});
			});
			var dock = null, timers = [], audio = null;
			function fmt(s) { s = Math.max(0, Math.ceil(s)); var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return (h ? h + ':' + (m < 10 ? '0' : '') : '') + m + ':' + (x < 10 ? '0' : '') + x; }
			function beep() { try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); [0, .35, .7].forEach(function (t) { var o = audio.createOscillator(), g = audio.createGain(); o.frequency.value = 880; o.connect(g); g.connect(audio.destination); g.gain.setValueAtTime(.0001, audio.currentTime + t); g.gain.exponentialRampToValueAtTime(.3, audio.currentTime + t + .02); g.gain.exponentialRampToValueAtTime(.0001, audio.currentTime + t + .25); o.start(audio.currentTime + t); o.stop(audio.currentTime + t + .3); }); } catch (e) {} if (navigator.vibrate) navigator.vibrate([300, 150, 300]); }
			function paint() {
				if (!dock) { dock = document.createElement('div'); dock.className = 'ellada-tmr'; dock.setAttribute('role', 'region'); dock.setAttribute('aria-label', 'Χρονόμετρα'); document.body.appendChild(dock); }
				dock.hidden = !timers.length;
				timers.forEach(function (t) {
					if (!t.el) { t.el = document.createElement('div'); t.el.className = 'ellada-tmr__one';
						t.el.innerHTML = '<span class="ellada-tmr__lb"></span><b class="ellada-tmr__t" aria-live="off"></b><button type="button" data-a="p"></button><button type="button" data-a="x" aria-label="Κλείσιμο χρονομέτρου">×</button>';
						t.el.querySelector('.ellada-tmr__lb').textContent = (t.step ? 'Βήμα ' + t.step + ' · ' : '') + t.label;
						t.el.addEventListener('click', function (e) { var a = e.target.getAttribute('data-a'); if (a === 'p') { if (t.done) { t.left = t.total; t.done = false; t.el.classList.remove('is-done'); t.end = Date.now() + t.left * 1000; t.run = true; } else if (t.run) { t.left = (t.end - Date.now()) / 1000; t.run = false; } else { t.end = Date.now() + t.left * 1000; t.run = true; } tick(); } if (a === 'x') { t.el.remove(); timers.splice(timers.indexOf(t), 1); paint(); } });
						dock.appendChild(t.el); }
					var left = t.done ? 0 : (t.run ? (t.end - Date.now()) / 1000 : t.left);
					t.el.querySelector('.ellada-tmr__t').textContent = t.done ? 'Έτοιμο!' : fmt(left);
					var pb = t.el.querySelector('[data-a=p]'); pb.textContent = t.done ? '↻' : (t.run ? '❚❚' : '▶'); pb.setAttribute('aria-label', t.done ? 'Ξανά' : (t.run ? 'Παύση' : 'Συνέχεια'));
				});
			}
			function tick() {
				timers.forEach(function (t) { if (t.run && !t.done && t.end - Date.now() <= 0) { t.run = false; t.done = true; t.el && t.el.classList.add('is-done'); beep(); toast('⏱ Τελείωσε: ' + (t.step ? 'βήμα ' + t.step + ' — ' : '') + t.label); } });
				paint();
			}
			setInterval(function () { if (timers.some(function (t) { return t.run; })) tick(); }, 500);
			document.addEventListener('click', function (e) {
				var b = e.target.closest('.ellada-tmr-btn'); if (!b) return;
				if (timers.length >= 3) { toast('Έως 3 χρονόμετρα μαζί.'); return; }
				try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); audio.resume && audio.resume(); } catch (er) {}
				var s = +b.getAttribute('data-sec');
				timers.push({ total: s, left: s, end: Date.now() + s * 1000, run: true, done: false, label: b.textContent.trim(), step: +b.getAttribute('data-step') });
				paint(); toast('⏱ Ξεκίνησε: ' + b.textContent.trim());
			});

			/* ── 7. Κινητό: μπάρα «Μαγειρεύω τώρα» όσο φαίνεται η εκτέλεση ── */
			var cook = document.querySelector('[data-ellada-cook]'), steps = document.querySelector('.ellada-steps');
			if (cook && steps && 'IntersectionObserver' in window) {
				var bar = document.createElement('div'); bar.className = 'ellada-cookbar'; bar.hidden = true;
				bar.innerHTML = '<button type="button" class="ellada-cookbar__btn" aria-pressed="false"><span aria-hidden="true">◉</span> <span data-l>Μαγειρεύω τώρα</span></button><a class="ellada-cookbar__up" href="#ylika">↑ Υλικά</a>';
				document.body.appendChild(bar);
				var bb = bar.querySelector('button');
				function sync() { var on = cook.getAttribute('aria-pressed') === 'true'; bb.setAttribute('aria-pressed', on ? 'true' : 'false'); bb.querySelector('[data-l]').textContent = on ? 'Τέλος μαγειρέματος' : 'Μαγειρεύω τώρα'; }
				bb.addEventListener('click', function () { var y = window.scrollY; cook.click(); setTimeout(function () { window.scrollTo({ top: y, behavior: 'instant' }); }, 30); sync(); });
				new MutationObserver(sync).observe(cook, { attributes: true, attributeFilter: ['aria-pressed'] }); sync();
				var mq = window.matchMedia('(max-width: 1024px)');
				new IntersectionObserver(function (en) { var vis = en[0].isIntersecting && mq.matches; bar.hidden = !vis; document.body.classList.toggle('ellada-has-cookbar', vis); }, { rootMargin: '-30% 0px -30% 0px' }).observe(steps);
			}
		})();
		</script>
		<?php
	},
	50
);
