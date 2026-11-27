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
		var clear = sec.querySelector('.ellada-steps__clear');
		function save() {
			var on = [];
			btns.forEach(function (x, j) {
				if (x.getAttribute('aria-pressed') === 'true') on.push(j);
			});
			store(key, on.length ? on : null);
			if (clear) clear.hidden = !on.length;
		}
		btns.forEach(function (b, i) {
			set(b, saved.indexOf(i) > -1);
			b.addEventListener('click', function () {
				set(b, b.getAttribute('aria-pressed') !== 'true');
				save();
			});
		});
		if (clear) {
			clear.hidden = !saved.length;
			clear.addEventListener('click', function () {
				btns.forEach(function (b) {
					set(b, false);
				});
				save();
				if (typeof nextStep === 'function') setTimeout(nextStep, 0);
				if (btns[0]) btns[0].focus();
			});
		}
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

	/* ── Μαγειρεύω τώρα: η οθόνη μένει αναμμένη, τονίζεται το επόμενο βήμα ── */
	function nextStep() {
		var items = document.querySelectorAll('.ellada-steps.is-tools li');
		var found = false;
		items.forEach(function (li) {
			var on = !found && !li.classList.contains('is-done') && document.body.classList.contains('ellada-cooking');
			li.classList.toggle('is-next', on);
			if (on) found = true;
		});
	}

	function cook(btn) {
		var lock = null;
		var label = btn.querySelector('[data-cook-label]');
		function request() {
			if (!('wakeLock' in navigator)) return Promise.resolve(false);
			return navigator.wakeLock.request('screen').then(function (l) {
				lock = l;
				return true;
			}, function () {
				return false;
			});
		}
		function set(on) {
			document.body.classList.toggle('ellada-cooking', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			if (label) label.textContent = on ? 'Τέλος μαγειρέματος' : 'Μαγειρεύω τώρα';
			nextStep();
			if (on) {
				request().then(function (ok) {
					toast(ok ? 'Η οθόνη θα μείνει αναμμένη όσο μαγειρεύετε.' : 'Η συσκευή σας δεν επιτρέπει να μένει αναμμένη η οθόνη.');
				});
				var target = document.getElementById('ylika');
				if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			} else if (lock) {
				lock.release().catch(function () {});
				lock = null;
			}
		}
		btn.addEventListener('click', function () {
			set(btn.getAttribute('aria-pressed') !== 'true');
		});
		// Η κλειδαριά χάνεται όταν η καρτέλα κρύβεται — ξαναζητιέται όταν επιστρέψετε.
		document.addEventListener('visibilitychange', function () {
			if (document.visibilityState === 'visible' && document.body.classList.contains('ellada-cooking')) request();
		});
		document.addEventListener('click', function (e) {
			if (e.target.closest('.ellada-steps__n')) setTimeout(nextStep, 0);
		});
	}

	var toastEl = null;
	function toast(msg) {
		if (!toastEl) {
			toastEl = document.createElement('div');
			toastEl.className = 'ellada-toast';
			toastEl.setAttribute('role', 'status');
			document.body.appendChild(toastEl);
		}
		toastEl.textContent = msg;
		toastEl.classList.add('is-on');
		clearTimeout(toastEl._t);
		toastEl._t = setTimeout(function () {
			toastEl.classList.remove('is-on');
		}, 3200);
	}

	/* ── Λίστα για ψώνια: τα υλικά με τις τρέχουσες ποσότητες ── */
	function shopping(btn) {
		btn.addEventListener('click', function () {
			var sec = btn.closest('.ellada-ingredients');
			var title = btn.getAttribute('data-title') || document.title;
			var serv = sec.querySelector('.ellada-serv output');
			var lines = [title + (serv ? ' (' + serv.textContent + ' μερίδες)' : '')];
			var missing = !!window.elladaShopMissing, skipped = 0, kept = 0;
			sec.querySelectorAll('.ellada-ingredients__group, li').forEach(function (el) {
				if (el.classList.contains('ellada-ingredients__group')) {
					lines.push('', el.textContent.trim() + ':');
					return;
				}
				// Μόνο όσα λείπουν: τα τσεκαρισμένα (τα έχετε ήδη) δεν μπαίνουν στη λίστα.
				if (missing && el.classList.contains('is-done')) { skipped++; return; }
				kept++;
				var q = el.querySelector('.ellada-ingredients__qty');
				var item = el.querySelector('.ellada-ingredients__item');
				var text = ((q ? q.textContent.trim() + ' ' : '') + (item ? item.textContent : el.textContent)).replace(/\s+/g, ' ').trim();
				if (text) lines.push('• ' + text);
			});
			if (missing && !kept) { toast('Τα έχετε όλα — δεν λείπει τίποτα!'); return; }
			lines.push('', location.origin + location.pathname);
			var text = lines.join('\n');
			if (navigator.share && window.matchMedia('(pointer: coarse)').matches) {
				navigator.share({ title: 'Λίστα για ψώνια: ' + title, text: text }).catch(function () {});
				return;
			}
			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(function () {
					toast('Η λίστα αντιγράφηκε — επικολλήστε τη όπου θέλετε.');
				}, function () {
					window.prompt('Αντιγράψτε τη λίστα:', text);
				});
			} else {
				window.prompt('Αντιγράψτε τη λίστα:', text);
			}
		});
	}

	/* ── Το έφτιαξα! (ένα κλικ ανά συσκευή, χωρίς προσωπικά δεδομένα) ── */
	function madeLabel(n) {
		if (n && n < (window.elladaMadeMin || 0)) return 'Γίνετε από τους πρώτους που θα τη φτιάξουν';
		return n ? (n === 1 ? 'Την έφτιαξε 1 άτομο' : 'Την έφτιαξαν ' + n.toLocaleString('el-GR') + ' άτομα') : 'Γίνετε οι πρώτοι που θα τη φτιάξουν';
	}
	function made(box) {
		var id = box.getAttribute('data-ellada-made');
		var url = box.getAttribute('data-endpoint');
		var btn = box.querySelector('button');
		var count = box.querySelector('[data-made-count]');
		var key = 'ellada-made-' + id;
		function done() {
			btn.disabled = true;
			btn.classList.add('is-done');
			btn.querySelector('[data-made-label]').textContent = 'Την έφτιαξα ✓';
		}
		if (store(key)) done();
		// Ο μετρητής από τον server (η σελίδα μπορεί να είναι σε cache).
		fetch(url, { credentials: 'omit' }).then(function (r) { return r.json(); }).then(function (d) {
			if (d && typeof d.count === 'number') count.textContent = madeLabel(d.count);
		}).catch(function () {});
		btn.addEventListener('click', function () {
			if (store(key)) return;
			store(key, 1);
			done();
			fetch(url, { method: 'POST', credentials: 'omit' }).then(function (r) { return r.json(); }).then(function (d) {
				if (d && typeof d.count === 'number') count.textContent = madeLabel(d.count) + ' — ευχαριστούμε!';
			}).catch(function () {});
		});
	}

	/* ── Αγαπημένα (μόνο σε αυτή τη συσκευή) ── */
	function favs() {
		var f = store('ellada-favs');
		return Array.isArray(f) ? f : [];
	}
	function fav(btn) {
		var id = parseInt(btn.getAttribute('data-ellada-fav'), 10);
		var page = btn.getAttribute('data-page');
		function paint() {
			var on = favs().indexOf(id) !== -1;
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			btn.querySelector('[aria-hidden]').textContent = on ? '♥' : '♡';
			btn.querySelector('[data-fav-label]').textContent = on ? 'Στις αγαπημένες' : 'Αγαπημένη';
		}
		paint();
		btn.addEventListener('click', function () {
			var f = favs();
			var i = f.indexOf(id);
			if (i === -1) f.unshift(id);
			else f.splice(i, 1);
			store('ellada-favs', f.slice(0, 60));
			paint();
			if (i === -1 && page && !btn.parentNode.querySelector('.ellada-fav-note')) {
				var a = document.createElement('a');
				a.className = 'ellada-fav-note';
				a.href = page;
				a.textContent = 'Οι συνταγές μου →';
				btn.insertAdjacentElement('afterend', a);
			}
		});
	}
	function myRecipes(box) {
		var ids = favs();
		if (!ids.length) return;
		box.classList.add('has-favs');
		fetch(box.getAttribute('data-endpoint') + '?ids=' + ids.join(','), { credentials: 'omit' })
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (d && d.html) box.querySelector('.ellada-cards').innerHTML = d.html;
				else box.classList.remove('has-favs');
			}).catch(function () { box.classList.remove('has-favs'); });
	}

	function init() {
		document.querySelectorAll('[data-ellada-cook]').forEach(cook);
		document.querySelectorAll('[data-ellada-shop]').forEach(shopping);
		var ing = document.querySelector('.ellada-ingredients.is-tools');
		if (ing) {
			servings(ing);
			checklist(ing);
		}
		var st = document.querySelector('.ellada-steps.is-tools');
		if (st) steps(st, ing ? ing.getAttribute('data-recipe') : location.pathname);
		document.querySelectorAll('.ellada-ractions').forEach(actions);
		document.querySelectorAll('[data-ellada-made]').forEach(made);
		document.querySelectorAll('[data-ellada-fav]').forEach(fav);
		document.querySelectorAll('.ellada-myrecipes').forEach(myRecipes);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
