/* Weather Widget Pro: the shortcode writer of the help page (Settings › Weather Widget Pro). */
(function () {
	'use strict';

	var config = window.WIYCW_help || {};
	var input = document.getElementById('wiycw-city');
	var results = document.getElementById('wiycw-results');
	var picked = document.getElementById('wiycw-picked');
	var output = document.getElementById('wiycw-shortcode');
	var copy = document.getElementById('wiycw-copy');
	var copied = document.getElementById('wiycw-copied');
	if (!input || !results || !output) {
		return;
	}

	var city = {id: '86168', city: 'New York City'};
	var timer = 0;

	function value(id) {
		var field = document.getElementById(id);
		return field ? field.value : '';
	}

	function checked(id) {
		var field = document.getElementById(id);
		return !!(field && field.checked);
	}

	function attribute(text) {
		return String(text).replace(/["\[\]]/g, '');
	}

	function write() {
		var design = value('wiycw-design') === 'classic' ? 'classic' : 'card';
		var days = Math.max(0, Math.min(14, parseInt(value('wiycw-days'), 10) || 0));
		var parts = ['weather_pro', 'cityid="' + attribute(city.id) + '"', 'city="' + attribute(city.city) + '"',
			'design="' + design + '"', 'units="' + (value('wiycw-units') === 'f' ? 'f' : 'c') + '"', 'days="' + (days || 'off') + '"',
			'layout="' + (value('wiycw-layout') === 'horizontal' ? 'horizontal' : 'vertical') + '"'];
		if (design === 'card') {
			parts.push('hourly="' + (checked('wiycw-hourly') ? 'on' : 'off') + '"');
			parts.push('unit_switch="' + (checked('wiycw-switch') ? 'on' : 'off') + '"');
		}
		output.value = '[' + parts.join(' ') + ']';
	}

	function show(list) {
		results.textContent = '';
		list.slice(0, 8).forEach(function (item) {
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
		clearTimeout(timer);
		if (text.length < 2) {
			results.textContent = '';
			return;
		}
		timer = setTimeout(function () {
			fetch((config.searchUrl || 'https://eltiempoen.com/api/weather/wpsearch') + '?q=' + encodeURIComponent(text))
				.then(function (response) { return response.ok ? response.json() : []; })
				.then(function (list) { show(Array.isArray(list) ? list : []); })
				.catch(function () { show([]); });
		}, 300);
	});

	['wiycw-design', 'wiycw-layout', 'wiycw-units', 'wiycw-days', 'wiycw-hourly', 'wiycw-switch'].forEach(function (id) {
		var field = document.getElementById(id);
		if (field) {
			field.addEventListener('change', write);
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
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(output.value).then(done, function () {
					document.execCommand('copy');
					done();
				});
			} else {
				document.execCommand('copy');
				done();
			}
		});
	}

	write();
})();
