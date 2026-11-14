/* Σελίδα συνταγής: μερίδες, τσεκάρισμα υλικών και βημάτων, εκτύπωση, κοινοποίηση. */
(function () {
	'use strict';

	function store(key, val) {
		try {
			if (val === undefined) return JSON.parse(localStorage.getItem(key) || 'null');
			if (val === null) localStorage.removeItem(key);
			else localStorage.setItem(key, JSON.stringify(val));
		} catch (e) {
			return null;
		}
	}

	/* ── Ποσότητες ── */
	var FRAC = { '½': 0.5, '¼': 0.25, '¾': 0.75, '⅓': 1 / 3, '⅔': 2 / 3 };
	// Αριθμός στην αρχή: «1 1/2», «1½», «1/2», «2,5», «2-3», «½».
	var NUM = /^(\d+(?:[.,]\d+)?\s+\d+\/\d+|\d+\s*[½¼¾⅓⅔]|\d+\/\d+|\d+(?:[.,]\d+)?|[½¼¾⅓⅔])(\s*[-–]\s*(\d+(?:[.,]\d+)?|\d+\/\d+))?/;

	function toNum(s) {
		s = s.trim();
		var m;
		if ((m = s.match(/^(\d+)\s+(\d+)\/(\d+)$/))) return +m[1] + m[2] / m[3];
		if ((m = s.match(/^(\d+)\s*([½¼¾⅓⅔])$/))) return +m[1] + FRAC[m[2]];
		if ((m = s.match(/^(\d+)\/(\d+)$/))) return m[1] / m[2];
		if (FRAC[s] !== undefined) return FRAC[s];
		return parseFloat(s.replace(',', '.'));
	}

	function fmt(n) {
		// Στρογγυλέματα που διαβάζονται εύκολα στην κουζίνα.
		if (n >= 1000) return String(Math.round(n / 50) * 50);
		if (n >= 100) return String(Math.round(n / 10) * 10);
		if (n >= 5) return String(Math.round(n));
		var q = n >= 1 ? Math.round(n * 2) / 2 : Math.round(n * 4) / 4;
		if (q === 0) q = 0.25;
		var whole = Math.floor(q);
		var rest = { 0: '', 0.25: '¼', 0.5: '½', 0.75: '¾' }[q - whole];
		return (whole ? String(whole) : '') + rest;
	}

	function scaleQty(text, factor) {
		var m = text.match(NUM);
		if (!m || factor === 1) return text;
		var a = toNum(m[1]);
		if (!isFinite(a)) return text;
		var out = fmt(a * factor);
		if (m[3]) out += '-' + fmt(toNum(m[3]) * factor);
		return out + text.slice(m[0].length);
	}

	function servings(sec) {
		var base = parseInt(sec.getAttribute('data-servings'), 10);
		var outEl = sec.querySelector('.ellada-serv output');
		if (!base || !outEl) return;
		var key = 'ellada-serv-' + sec.getAttribute('data-recipe');
		var note = sec.querySelector('.ellada-serv__note');
		var cur = base;

		function apply(n) {
			cur = Math.max(1, Math.min(99, n));
			outEl.textContent = cur;
			var f = cur / base;
			sec.querySelectorAll('.ellada-ingredients__qty[data-q]').forEach(function (el) {
				el.textContent = scaleQty(el.getAttribute('data-q'), f);
			});
			if (note) {
				note.hidden = cur === base;
				note.querySelector('span').textContent = cur + (cur === 1 ? ' μερίδα' : ' μερίδες');
			}
			sec.querySelector('[data-serv="-1"]').disabled = cur <= 1;
			store(key, cur === base ? null : cur);
		}

		sec.addEventListener('click', function (e) {
			var b = e.target.closest('[data-serv]');
			if (b) apply(cur + parseInt(b.getAttribute('data-serv'), 10));
			if (e.target.closest('[data-serv-reset]')) apply(base);
		});
		apply(store(key) || base);
	}

	/* ── Τσεκάρισμα υλικών ── */
	function checklist(sec) {
		var key = 'ellada-check-' + sec.getAttribute('data-recipe');
		var boxes = Array.prototype.slice.call(sec.querySelectorAll('li input[type="checkbox"]'));
		var clear = sec.querySelector('.ellada-ingredients__clear');
		var saved = store(key) || [];

		function sync() {
			var on = [];
			boxes.forEach(function (b, i) {
				b.closest('li').classList.toggle('is-done', b.checked);
				if (b.checked) on.push(i);
			});
			if (clear) clear.hidden = !on.length;
			store(key, on.length ? on : null);
		}
		boxes.forEach(function (b, i) {
			b.checked = saved.indexOf(i) > -1;
			b.addEventListener('change', sync);
		});
		if (clear) {
			clear.addEventListener('click', function () {
				boxes.forEach(function (b) {
					b.checked = false;
				});
				sync();
			});
		}
		sync();
	}

	/* ── Βήματα ── */
	function steps(sec, recipe) {
		var key = 'ellada-steps-' + recipe;
		var saved = store(key) || [];
		var btns = Array.prototype.slice.call(sec.querySelectorAll('button.ellada-steps__n'));
		function set(b, on) {
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
			b.closest('li').classList.toggle('is-done', on);
		}
		btns.forEach(function (b, i) {
			set(b, saved.indexOf(i) > -1);
			b.addEventListener('click', function () {
				set(b, b.getAttribute('aria-pressed') !== 'true');
				var on = [];
				btns.forEach(function (x, j) {
					if (x.getAttribute('aria-pressed') === 'true') on.push(j);
				});
				store(key, on.length ? on : null);
			});
		});
	}

	/* ── Εκτύπωση και κοινοποίηση ── */
	function actions(bar) {
		var url = bar.getAttribute('data-url');
		var title = bar.getAttribute('data-title');
		var btn = bar.querySelector('[data-ellada-share]');
		var menu = bar.querySelector('.ellada-share__menu');

		function toggle(open) {
			menu.hidden = !open;
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		bar.addEventListener('click', function (e) {
			if (e.target.closest('[data-ellada-print]')) window.print();
			if (e.target.closest('[data-ellada-share]')) {
				// Στο κινητό ανοίγει το μενού κοινοποίησης της συσκευής.
				if (navigator.share && window.matchMedia('(pointer: coarse)').matches) {
					navigator.share({ title: title, url: url }).catch(function () {});
					return;
				}
				toggle(menu.hidden);
			}
			var copy = e.target.closest('[data-ellada-copy]');
			if (copy) {
				var done = function () {
					copy.textContent = 'Ο σύνδεσμος αντιγράφηκε ✓';
					setTimeout(function () {
						copy.textContent = 'Αντιγραφή συνδέσμου';
					}, 2000);
				};
				if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function () {});
				else window.prompt('Αντιγράψτε τον σύνδεσμο:', url);
			}
		});
		document.addEventListener('click', function (e) {
			if (!menu.hidden && !e.target.closest('.ellada-share')) toggle(false);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !menu.hidden) {
				toggle(false);
				btn.focus();
			}
		});
	}

	function init() {
		var ing = document.querySelector('.ellada-ingredients.is-tools');
		if (ing) {
			servings(ing);
			checklist(ing);
		}
		var st = document.querySelector('.ellada-steps.is-tools');
		if (st) steps(st, ing ? ing.getAttribute('data-recipe') : location.pathname);
		document.querySelectorAll('.ellada-ractions').forEach(actions);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
