(function () {
	'use strict';

	var config = window.WIYCW_help || {};
	var defaults = config.defaults || {};
	var root = document.getElementById('wiycw-generator');
	var input = document.getElementById('wiycw-city');
	var results = document.getElementById('wiycw-results');
	var picked = document.getElementById('wiycw-picked');
	var output = document.getElementById('wiycw-shortcode');
	var preview = document.getElementById('wiycw-preview');
	var copy = document.getElementById('wiycw-copy');
	var copied = document.getElementById('wiycw-copied');
	if (!root || !input || !results || !output) {
		return;
	}

	var city = {id: '86168', city: 'New York City'};
	var searchTimer = 0;
	var previewTimer = 0;
	var previewAsked = 0;

	function fields() {
		return Array.prototype.slice.call(root.querySelectorAll('[data-attribute]'));
	}

	function valueOf(field) {
		return field.type === 'checkbox' ? (field.checked ? 'on' : 'off') : String(field.value);
	}

	function attribute(text) {
		return String(text)
			.replace(/&(?=#?[a-z0-9]+;)/gi, '&#38;')
			.replace(/"/g, '&quot;')
			.replace(/\[/g, '&#91;')
			.replace(/\]/g, '&#93;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/\\/g, '&#92;');
	}

	function fontSize(value) {
		var size = parseInt(value, 10);
		if (isNaN(size)) {
			return String(defaults.fontsize || '15');
		}
		return String(Math.max(11, Math.min(22, size)));
	}

	function attributes() {
		var design = '';
		var all = {cityid: city.id, city: city.city};
		fields().forEach(function (field) {
			var name = field.getAttribute('data-attribute');
			var value = valueOf(field);
			if (name === 'fontsize') {
				value = fontSize(value);
			}
			if (name === 'design') {
				design = value;
			}
			all[name] = value;
		});
		var used = {cityid: all.cityid, city: all.city, design: design};
		var owner = root.querySelectorAll('[data-only]');
		Object.keys(all).forEach(function (name) {
			if (name in used) {
				return;
			}
			var field = root.querySelector('[data-attribute="' + name + '"]');
			var box = field ? field.closest('[data-only],[data-needs]') : null;
			if (box && box.hidden) {
				return;
			}
			if (name === 'title' && all[name] === '') {
				return;
			}
			if (String(defaults[name]) !== all[name]) {
				used[name] = all[name];
			}
		});
		return used;
	}

	function refresh() {
		var design = (root.querySelector('[data-attribute="design"]') || {}).value;
		var today = root.querySelector('[data-attribute="today"]');
		var days = root.querySelector('[data-attribute="days"]');
		Array.prototype.forEach.call(root.querySelectorAll('[data-only],[data-needs]'), function (box) {
			var only = box.getAttribute('data-only');
			var needs = box.getAttribute('data-needs');
			var hide = (only && only !== design) || (needs === 'today' && today && !today.checked) || (needs === 'days' && days && days.value === 'off');
			box.hidden = !!hide;
		});
	}

	function write() {
		refresh();
		var used = attributes();
		var written = {};
		Object.keys(used).forEach(function (name) {
			written[name] = attribute(used[name]);
		});
		output.value = '[weather_pro ' + Object.keys(written).map(function (name) {
			return name + '="' + written[name] + '"';
		}).join(' ') + ']';
		clearTimeout(previewTimer);
		previewTimer = setTimeout(function () { show(written); }, 250);
	}

	function show(used) {
		if (!preview || !config.ajaxUrl) {
			return;
		}
		var asked = ++previewAsked;
		var body = new FormData();
		body.append('action', 'wiycw_preview');
		body.append('nonce', config.nonce || '');
		Object.keys(used).forEach(function (name) {
			body.append('atts[' + name + ']', used[name]);
		});
		preview.classList.add('is-loading');
		fetch(config.ajaxUrl, {method: 'POST', body: body, credentials: 'same-origin'})
			.then(function (response) { return response.ok ? response.text() : ''; })
			.then(function (html) {
				if (asked !== previewAsked) {
					return;
				}
				var wide = used.layout === 'horizontal' || (!('layout' in used) && defaults.layout === 'horizontal');
				preview.innerHTML = '<div class="wiycw-gen-fit"><div class="wiycw-gen-stage"' + (wide ? ' style="width:' + WIDE + 'px"' : '') + '>' + html + '</div></div>';
				preview.classList.remove('is-loading');
				if (window.WIYCW && window.WIYCW.init) {
					window.WIYCW.init(preview);
				}
				fit();
			})
			.catch(function () { preview.classList.remove('is-loading'); });
	}

	var WIDE = 780;
	function fit() {
		var box = preview && preview.querySelector('.wiycw-gen-fit');
		var stage = box && box.querySelector('.wiycw-gen-stage');
		if (!stage) {
			return;
		}
		var room = box.clientWidth;
		var scale = stage.style.width ? Math.min(1, room / WIDE) : 1;
		stage.style.transform = scale < 1 ? 'scale(' + scale + ')' : '';
		box.style.height = scale < 1 ? Math.ceil(stage.offsetHeight * scale) + 'px' : '';
		var note = document.getElementById('wiycw-scaled');
		if (note) {
			note.hidden = !(scale < 1);
			note.textContent = String(config.scaled || 'Shown at %d%% of its size so that it fits here: on your site it will look bigger.')
				.replace('%d', Math.round(scale * 100)).replace('%%', '%');
		}
	}

	if (window.ResizeObserver && preview) {
		new ResizeObserver(fit).observe(preview);
		new MutationObserver(function () { window.requestAnimationFrame(fit); }).observe(preview, {childList: true, subtree: true});
	}
	window.addEventListener('resize', fit);

	function list(items) {
		results.textContent = '';
		items.slice(0, 8).forEach(function (item) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'button-link';
			button.setAttribute('role', 'option');
			button.style.display = 'block';
			button.style.padding = '4px 0';
			button.textContent = item.text;
			button.addEventListener('click', function () {
				city = {id: item.id, city: item.city};
				if (picked) {
					picked.textContent = item.text;
				}
				input.value = '';
				results.textContent = '';
				write();
			});
			results.appendChild(button);
		});
	}

	input.addEventListener('input', function () {
		var text = input.value.trim();
		clearTimeout(searchTimer);
		if (text.length < 2) {
			results.textContent = '';
			return;
		}
		searchTimer = setTimeout(function () {
			fetch((config.searchUrl || 'https://eltiempoen.com/api/weather/wpsearch') + '?q=' + encodeURIComponent(text))
				.then(function (response) { return response.ok ? response.json() : []; })
				.then(function (items) { list(Array.isArray(items) ? items : []); })
				.catch(function () { list([]); });
		}, 300);
	});

	fields().forEach(function (field) {
		field.addEventListener('change', write);
		if (field.type === 'text' || field.type === 'number' || field.type === 'color') {
			field.addEventListener('input', write);
		}
	});

	if (copy) {
		copy.addEventListener('click', function () {
			output.select();
			var done = function () {
				if (copied) {
					copied.hidden = false;
					setTimeout(function () { copied.hidden = true; }, 2000);
				}
			};
			var byHand = function () {
				var ok = false;
				try {
					ok = document.execCommand('copy');
				} catch (e) {
					ok = false;
				}
				output.focus();
				output.select();
				if (ok) {
					done();
				}
			};
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(output.value).then(done, byHand);
			} else {
				byHand();
			}
		});
	}

	write();
})();
