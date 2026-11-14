/* Αναζήτηση σε όλη την οθόνη, με αποτελέσματα καθώς πληκτρολογείτε. */
(function () {
	'use strict';
	var cfg = window.elladaSearch || {};
	var box = document.getElementById('ellada-search');
	if (!box || !cfg.endpoint) return;

	var input = box.querySelector('.ellada-search__input');
	var out = box.querySelector('.ellada-search__results');
	var hint = box.querySelector('.ellada-search__hint');
	var lastFocus = null;
	var timer = 0;
	var ctrl = null;
	var cache = {};

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function open(e) {
		if (e) e.preventDefault();
		lastFocus = document.activeElement;
		box.hidden = false;
		document.documentElement.classList.add('ellada-search-open');
		requestAnimationFrame(function () {
			box.classList.add('is-open');
			input.focus();
			input.select();
		});
	}

	function close() {
		box.classList.remove('is-open');
		document.documentElement.classList.remove('ellada-search-open');
		box.hidden = true;
		if (lastFocus && lastFocus.focus) lastFocus.focus();
	}

	function group(title, items, withImg) {
		if (!items.length) return '';
		var html = '<section class="ellada-search__group"><h3>' + esc(title) + '</h3><ul>';
		items.forEach(function (it) {
			var media = '';
			if (withImg) {
				media = it.img
					? '<img src="' + esc(it.img) + '" alt="" width="56" height="56" loading="lazy">'
					: '<span class="ellada-search__ph" aria-hidden="true"></span>';
			}
			html += '<li><a href="' + esc(it.url) + '">' + media + '<span><strong>' + esc(it.title) + '</strong>' +
				(it.meta ? '<small>' + esc(it.meta) + '</small>' : '') + '</span></a></li>';
		});
		return html + '</ul></section>';
	}

	function render(data) {
		if (!data.total) {
			out.innerHTML = '<p class="ellada-search__empty">Δεν βρέθηκε κάτι για «' + esc(data.query) + '». Δοκιμάστε μια άλλη λέξη.</p>';
			return;
		}
		var shown = data.recipes.length + data.areas.length + data.collections.length;
		out.innerHTML =
			'<div class="ellada-search__groups">' +
			group('Συνταγές', data.recipes, true) +
			group('Περιοχές', data.areas, false) +
			group('Συλλογές', data.collections, true) +
			'</div>' +
			'<a class="ellada-search__all" href="' + esc(data.all_url) + '">' +
			(data.total > shown ? 'Όλα τα αποτελέσματα (' + data.total + ')' : 'Άνοιγμα σελίδας αποτελεσμάτων') +
			' →</a>';
	}

	function search() {
		var q = input.value.trim();
		hint.hidden = q.length > 0;
		if (q.length < 2) {
			out.innerHTML = '';
			return;
		}
		if (cache[q]) {
			render(cache[q]);
			return;
		}
		if (ctrl) ctrl.abort();
		ctrl = 'AbortController' in window ? new AbortController() : null;
		box.classList.add('is-loading');
		fetch(cfg.endpoint + (cfg.endpoint.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q), {
			signal: ctrl ? ctrl.signal : undefined,
			headers: { Accept: 'application/json' }
		})
			.then(function (r) {
				if (!r.ok) throw new Error(r.status);
				return r.json();
			})
			.then(function (data) {
				cache[q] = data;
				if (input.value.trim() === q) render(data);
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') return;
				out.innerHTML = '<p class="ellada-search__empty">Η αναζήτηση δεν απάντησε. Πατήστε Enter για τη σελίδα αποτελεσμάτων.</p>';
			})
			.then(function () {
				box.classList.remove('is-loading');
			});
	}

	document.addEventListener('click', function (e) {
		var t = e.target.closest && e.target.closest('[data-ellada-search-open]');
		if (t) open(e);
	});
	box.addEventListener('click', function (e) {
		if (e.target === box || e.target.closest('[data-ellada-search-close]')) close();
		var tryBtn = e.target.closest('[data-ellada-search-try]');
		if (tryBtn) {
			input.value = tryBtn.textContent;
			input.focus();
			search();
		}
	});
	input.addEventListener('input', function () {
		clearTimeout(timer);
		timer = setTimeout(search, 180);
	});
	document.addEventListener('keydown', function (e) {
		if (box.hidden) {
			// «/» ανοίγει την αναζήτηση, όταν δεν γράφετε ήδη κάπου.
			var tag = (document.activeElement && document.activeElement.tagName) || '';
			if (e.key === '/' && document.querySelector('[data-ellada-search-open]') && !/INPUT|TEXTAREA|SELECT/.test(tag) && !document.activeElement.isContentEditable) open(e);
			return;
		}
		if (e.key === 'Escape') {
			close();
			return;
		}
		// Το Tab μένει μέσα στο παράθυρο.
		if (e.key === 'Tab') {
			var f = box.querySelectorAll('button, input, a[href]');
			var first = f[0];
			var last = f[f.length - 1];
			if (e.shiftKey && document.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && document.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		}
	});
})();
