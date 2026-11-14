/*
 * Weather Widget Pro: the widget on the page (since 2.0.0).
 *
 * WIYCW-widget.js is the script of the versions up to 1.1.42 and stays as it was: a page kept in a cache with the old
 * HTML still loads it. This one paints the two designs: «classic» (the same boxes and classes as always) and «card»
 * (the sky card).
 *
 * Where the data comes from: the JSON the page already carries (the site had it in its cache) or, without it, the site
 * (admin-ajax). The visitor's browser never asks the weather service. One request per city and page.
 */
(function () {
	'use strict';

	var FRESH = 30 * 60; // seconds the data carried by the page is good as it is
	var USABLE = 3 * 3600; // older than this (a page kept long in a cache) it is not shown: it waits for the request
	var UNITS_KEY = 'wiycw-units';

	function i18n() {
		return window.WIYCW_i18n || {};
	}

	function t(key, fallback) {
		var value = i18n()[key];
		return typeof value === 'string' && value ? value : fallback;
	}

	function capitalize(text) {
		text = String(text || '');
		return text.charAt(0).toUpperCase() + text.slice(1);
	}

	function el(tag, className, text) {
		var node = document.createElement(tag);
		if (className) {
			node.className = className;
		}
		if (text !== undefined && text !== null) {
			node.appendChild(document.createTextNode(String(text)));
		}
		return node;
	}

	function image(src, alt, size, className) {
		var node = document.createElement('img');
		node.src = src;
		node.alt = alt || '';
		node.width = size;
		node.height = size;
		node.style.width = size + 'px';
		node.style.height = size + 'px';
		if (className) {
			node.className = className;
		}
		return node;
	}

	function isNumber(value) {
		return typeof value === 'number' && isFinite(value);
	}

	// ---- units ----

	function temp(value, units) {
		if (!isNumber(value)) {
			return '-';
		}
		return (units === 'c' ? Math.round(value) : Math.round(value * 1.8 + 32)) + '°';
	}

	// the classic design keeps its own sign
	function tempClassic(value, units) {
		if (!isNumber(value)) {
			return '-';
		}
		return (units === 'c' ? Math.round(value) : Math.round(value * 1.8 + 32)) + 'º';
	}

	function speed(value, units) {
		if (!isNumber(value)) {
			return '-';
		}
		return units === 'c' ? Math.round(value) + ' km/h' : Math.round(value * 0.62137) + ' mph';
	}

	function volume(value, units) {
		if (!isNumber(value)) {
			return '-';
		}
		return units === 'c' ? parseFloat(value).toFixed(2) + ' mm' : parseFloat((value / 25.4).toFixed(2)) + ' in';
	}

	function volumeShort(value, units) {
		if (!isNumber(value)) {
			return '';
		}
		return units === 'c' ? (Math.round(value * 10) / 10) + ' mm' : (Math.round(value / 25.4 * 100) / 100) + ' in';
	}

	function distance(meters, units) {
		if (!isNumber(meters)) {
			return '-';
		}
		return units === 'c' ? (Math.round(meters / 100) / 10) + ' km' : (Math.round(meters / 1609.34 * 10) / 10) + ' mi';
	}

	// "07:58" as it is or as "7:58 AM"
	function clock(time, format) {
		time = String(time || '');
		if (format !== 'standard') {
			return time;
		}
		var match = time.match(/^([01]\d|2[0-3]):([0-5]\d)/);
		if (!match) {
			return time;
		}
		var hours = Number(match[1]);
		return (hours % 12 || 12) + ':' + match[2] + (hours < 12 ? ' AM' : ' PM');
	}

	function hourOnly(time, format) {
		var match = String(time || '').match(/^([01]\d|2[0-3]):([0-5]\d)/);
		if (!match) {
			return String(time || '');
		}
		if (format !== 'standard') {
			return match[1] + ':' + match[2];
		}
		var hours = Number(match[1]);
		return (hours % 12 || 12) + (hours < 12 ? ' AM' : ' PM');
	}

	var chosenUnits = null; // the choice of this page, for a browser that keeps nothing

	function storedUnits() {
		if (chosenUnits) {
			return chosenUnits;
		}
		try {
			var value = window.localStorage.getItem(UNITS_KEY);
			return value === 'c' || value === 'f' ? value : null;
		} catch (e) {
			return null;
		}
	}

	function storeUnits(value) {
		chosenUnits = value;
		try {
			window.localStorage.setItem(UNITS_KEY, value);
		} catch (e) {}
	}

	// ---- icons and sky ----

	var OWM_ICON = /^(01|02|03|04|09|10|11|13|50)[dn]$/;
	var MODERN_ICON = /^wi-[a-z]+(-[a-z]+)*$/;
	// the drawing of the sites for an icon of OpenWeatherMap, when the service does not say which one
	var FROM_OWM = {'01d': 'wi-clear-d', '01n': 'wi-clear-n', '02d': 'wi-few-d', '02n': 'wi-few-n', '03d': 'wi-scattered-d', '03n': 'wi-scattered-n',
		'04d': 'wi-overcast', '04n': 'wi-overcast', '09d': 'wi-rain', '09n': 'wi-rain', '10d': 'wi-rain-d', '10n': 'wi-rain-n',
		'11d': 'wi-thunder-rain', '11n': 'wi-thunder-rain', '13d': 'wi-snow', '13n': 'wi-snow', '50d': 'wi-fog', '50n': 'wi-fog'};
	var SCENES = {'thunder-rain': 'storm', 'thunder': 'thunder', 'few': 'few', 'scattered': 'few', 'broken': 'cloudy', 'overcast': 'overcast',
		'drizzle': 'drizzle', 'rain-light': 'rain', 'rain': 'rain', 'rain-heavy': 'downpour', 'snow-light': 'snow', 'snow': 'snow',
		'snow-heavy': 'blizzard', 'sleet': 'sleet', 'fog': 'fog', 'mist': 'fog', 'haze': 'dust', 'smoke': 'dust', 'dust': 'dust',
		'wind': 'wind', 'tornado': 'wind'};
	var SCENE_NAMES = ['sun', 'stars', 'few', 'cloudy', 'overcast', 'drizzle', 'rain', 'downpour', 'storm', 'thunder', 'snow', 'blizzard', 'sleet', 'fog', 'dust', 'wind'];

	function owmIcon(name) {
		return OWM_ICON.test(String(name)) ? name : '01d';
	}

	function modernIcon(item) {
		if (item && typeof item.icon2 === 'string' && MODERN_ICON.test(item.icon2)) {
			return item.icon2;
		}
		return FROM_OWM[owmIcon(item && item.icon)];
	}

	function modernImage(base, item, alt, size, className) {
		var node = image(base + modernIcon(item) + '.svg', alt, size, className);
		node.onerror = function () {
			node.onerror = null;
			node.src = base + FROM_OWM[owmIcon(item && item.icon)] + '.svg';
		};
		return node;
	}

	function sceneOf(now) {
		var light = now.light === 'night' || now.light === 'dawn' || now.light === 'dusk' || now.light === 'day' ? now.light
			: (String(now.icon || '').slice(-1) === 'n' ? 'night' : 'day');
		var scene = SCENE_NAMES.indexOf(now.scene) > -1 ? now.scene : null;
		if (!scene) {
			var name = modernIcon(now).replace(/^wi-/, '');
			var base = name.replace(/-[dn]$/, '');
			scene = base === 'clear' ? (name.slice(-2) === '-n' ? 'stars' : 'sun') : (SCENES[base] || 'few');
			if (scene === 'sun' && light === 'night') {
				scene = 'stars';
			}
		}
		return {scene: scene, light: light};
	}

	function condition(item) {
		var texts = i18n().conditions || {};
		var text = item && texts[String(item.code)];
		return typeof text === 'string' ? text : '';
	}

	// ---- where the data comes from ----

	function getJSON(url, done) {
		var request = new XMLHttpRequest();
		request.onreadystatechange = function () {
			if (request.readyState !== 4) {
				return;
			}
			var data = null;
			if (request.status === 200 && request.responseText) {
				try {
					data = JSON.parse(request.responseText);
				} catch (e) {
					data = null;
				}
			}
			if (valid(tidy(data))) {
				return done(data, null);
			}
			// why there is no weather: what the site says (blocked, limit, city, network, service) or, when the site
			// itself does not answer, «site»
			done(null, data && typeof data === 'object' && typeof data.kind === 'string' ? data.kind : 'site');
		};
		request.open('GET', url);
		request.timeout = 15000;
		request.send();
	}

	// the lists of days and hours, only with what can be painted
	function tidy(data) {
		var whole = function (item) { return !!item && typeof item === 'object'; };
		if (data && typeof data === 'object') {
			if (Array.isArray(data.forecast)) {
				data.forecast = data.forecast.filter(whole);
			}
			data.hourly = Array.isArray(data.hourly) ? data.hourly.filter(whole) : [];
		}
		return data;
	}

	// the shape every painter relies on
	function valid(data) {
		return !!data && typeof data === 'object' && !data.error && !!data.now && typeof data.now === 'object' &&
			Array.isArray(data.forecast) && data.forecast.length > 0;
	}

	var requests = {}; // city id => {waiting: [fn], data, done}

	function request(widget, done) {
		var id = String(widget.dataset.cityid || '');
		var entry = requests[id];
		if (entry) {
			if (entry.done) {
				done(entry.data, entry.why);
			} else {
				entry.waiting.push(done);
			}
			return;
		}
		entry = requests[id] = {waiting: [done], data: null, done: false};
		var waits = [3000, 20000, 65000];
		var finish = function (data, why) {
			if (!data && why === 'limit' && waits.length) {
				setTimeout(function () { getJSON(site, finish); }, waits.shift());
				return;
			}
			entry.data = data;
			entry.why = why;
			entry.done = true;
			entry.waiting.splice(0).forEach(function (fn) { fn(data, why); });
			// a later widget of the same city (inserted afterwards) asks again after a while
			setTimeout(function () { delete requests[id]; }, 5 * 60 * 1000);
		};
		var site = widget.dataset.url + '?action=' + encodeURIComponent(widget.dataset.action || 'WIYCW_get_weather') +
			'&f=2&id=' + encodeURIComponent(id) + '&k=' + encodeURIComponent(widget.dataset.k || '');
		// only the site is asked: it keeps the last good answer of the weather service, and the visitor's browser never
		// talks to that service
		getJSON(site, finish);
	}

	// the JSON the page carries: {data, age in seconds} or null
	function carried(widget) {
		var holder = widget.querySelector('script.WIYCW-data');
		if (!holder) {
			return null;
		}
		var data = null;
		try {
			data = JSON.parse(holder.textContent || '');
		} catch (e) {
			data = null;
		}
		var saved = Number(holder.getAttribute('data-saved') || 0);
		if (!valid(tidy(data)) || !(saved > 0)) {
			return null;
		}
		return {data: data, age: Math.max(0, Date.now() / 1000 - saved)};
	}

	// ---- the classic design: the boxes and classes of always ----

	function classicIconClasses(widget) {
		var shadow = widget.dataset.shadow === 'on' ? '-shadow' : '';
		var ui = (widget.dataset.iconscolor === 'dark' ? 'WIYCW-icon-dark' : 'WIYCW-icon-light') + shadow;
		var kind = widget.dataset.weathericonscolor;
		var weather = (kind === 'dark' ? 'WIYCW-icon-dark' : kind === 'light' ? 'WIYCW-icon-light' : 'WIYCW-icon-color') + shadow;
		return {ui: ui, weather: weather, path: kind === 'color' ? 'color' : 'monocromatic'};
	}

	function infoCol(widget, iconFile, alt, text, classes, rotate) {
		var col = el('div', 'WIYCW-now-row-info-col');
		var icon = image(widget.dataset.path + '/resources/icons/ui-icons/' + iconFile, alt, 11);
		if (rotate === undefined) {
			icon.className = 'WIYCW-item-icon ' + classes.ui;
			col.appendChild(icon);
		} else {
			var holder = el('div', 'WIYCW-item-icon ' + classes.ui);
			holder.style.width = '11px';
			holder.style.height = '11px';
			holder.style.display = 'flex';
			if (isNumber(rotate)) {
				icon.style.transform = 'rotate(' + rotate + 'deg)';
			}
			holder.appendChild(icon);
			col.appendChild(holder);
		}
		col.appendChild(document.createTextNode(text));
		return col;
	}

	function paintClassic(widget, data, units) {
		var set = widget.dataset;
		var content = widget.querySelector('.WIYCW-content');
		var classes = classicIconClasses(widget);
		var iconsBase = set.path + '/resources/icons/weather-icons/' + classes.path + '/';
		var fragment = document.createDocumentFragment();

		if (set.today === 'on') {
			var now = el('div', 'WIYCW-now');
			var row1 = el('div', 'WIYCW-now-row1');
			if (set.nowicon === 'on') {
				var iconBox = el('div', 'WIYCW-now-icon');
				iconBox.appendChild(image(iconsBase + owmIcon(data.now.icon) + '.svg', condition(data.now) || t('alt_now', 'Current conditions'), 70, classes.weather));
				row1.appendChild(iconBox);
			}
			if (set.nowtemp === 'on') {
				row1.appendChild(el('div', 'WIYCW-now-temp', tempClassic(data.now.temp, units)));
			}
			if (set.nowicon === 'off' && set.nowtemp === 'off') {
				row1.style.display = 'none';
			}
			var info = el('div', 'WIYCW-now-row-info');
			if (set.nowsunrise === 'on') {
				info.appendChild(infoCol(widget, 'sunrise.svg', t('sunrise', 'Sunrise'), clock(data.now.sunrise, set.timeformat), classes));
				info.appendChild(infoCol(widget, 'sunset.svg', t('sunset', 'Sunset'), clock(data.now.sunset, set.timeformat), classes));
			}
			if (set.nowhumidity === 'on') {
				info.appendChild(infoCol(widget, 'dew-point.svg', t('humidity', 'Humidity'), data.now.humidity + '%', classes));
			}
			if (set.nowwind === 'on') {
				info.appendChild(infoCol(widget, 'arrow.svg', t('wind', 'Wind'), speed(data.now.wind_speed, units), classes, isNumber(data.now.wind_deg) ? data.now.wind_deg : null));
			}
			if (set.nowpressure === 'on') {
				info.appendChild(infoCol(widget, 'pressure.svg', t('pressure', 'Pressure'), data.now.pressure + ' hPa', classes));
			}
			if (set.nowcloudiness === 'on') {
				info.appendChild(infoCol(widget, 'clouds.svg', t('cloudiness', 'Cloudiness'), data.now.cloudiness + '%', classes));
			}
			now.appendChild(row1);
			now.appendChild(info);
			fragment.appendChild(now);
		}

		if (set.days !== 'off') {
			var forecast = el('div', 'WIYCW-forecast WIYCW-forecast-' + set.layout);
			var limit = Number(set.days);
			data.forecast.forEach(function (day, index) {
				// a number of days that is not one shows them all, as always
				if (index + 1 > limit) {
					return;
				}
				var row = el('div', 'WIYCW-forecast-row');
				row.appendChild(el('div', 'WIYCW-forecast-date WIYCW-col-1', dayLabel(day, index)));
				if (set.temp === 'on') {
					row.appendChild(el('div', 'WIYCW-forecast-temp WIYCW-col-1', tempClassic(day.temp_max, units) + ' / ' + tempClassic(day.temp_min, units)));
				}
				if (set.forecasticon === 'on') {
					var box = el('div', 'WIYCW-forecast-icon WIYCW-col-1');
					box.appendChild(image(iconsBase + owmIcon(day.icon) + '.svg', condition(day) || t('forecast', 'Forecast'), 30, classes.weather));
					row.appendChild(box);
				}
				if (set.rainchance === 'on') {
					var pop = el('div', 'WIYCW-forecast-pop WIYCW-col-1');
					pop.appendChild(image(set.path + '/resources/icons/ui-icons/umbrella.svg', t('rain_chance', 'Rain probability'), 11, 'WIYCW-item-icon ' + classes.ui));
					pop.appendChild(document.createTextNode(isNumber(day.pop) ? Math.round(day.pop * 100) + '%' : '-'));
					row.appendChild(pop);
				}
				if (set.rain === 'on') {
					row.appendChild(el('div', 'WIYCW-forecast-rain WIYCW-col-1', volume(day.rain, units)));
				}
				if (set.wind === 'on') {
					var wind = el('div', 'WIYCW-forecast-wind WIYCW-col-1');
					var holder = el('div', 'WIYCW-item-icon ' + classes.ui);
					holder.style.width = '11px';
					holder.style.height = '11px';
					holder.style.display = 'flex';
					var arrow = image(set.path + '/resources/icons/ui-icons/arrow.svg', t('wind', 'Wind'), 11);
					if (isNumber(day.wind_deg)) {
						arrow.style.transform = 'rotate(' + day.wind_deg + 'deg)';
					}
					holder.appendChild(arrow);
					wind.appendChild(holder);
					wind.appendChild(document.createTextNode(speed(day.wind_speed, units)));
					row.appendChild(wind);
				}
				forecast.appendChild(row);
			});
			fragment.appendChild(forecast);
		}

		content.textContent = '';
		content.appendChild(fragment);
	}

	function dayLabel(day, index) {
		var words = i18n();
		if (index === 0) {
			return capitalize(words.today || 'today');
		}
		if (index === 1) {
			return capitalize(words.tomorrow || 'tmrw.');
		}
		var names = words.week_days || [];
		return capitalize(names[day.wday] || '') + ' ' + day.day;
	}

	// ---- the card design ----

	// a fact of now: its icon, its label and its value; rotate: the degrees of the arrow of the wind (where it blows to)
	function fact(widget, iconFile, label, value, rotate) {
		var item = el('div', 'WIYCW-fact');
		var icon = image(widget.dataset.path + '/resources/icons/ui-icons/' + iconFile, '', 16, 'WIYCW-fact-icon');
		icon.setAttribute('aria-hidden', 'true');
		if (isNumber(rotate)) {
			icon.style.transform = 'rotate(' + rotate + 'deg)';
		}
		item.appendChild(icon);
		var text = el('div', 'WIYCW-fact-text');
		text.appendChild(el('span', 'WIYCW-fact-label', label));
		text.appendChild(el('span', 'WIYCW-fact-value', value));
		item.appendChild(text);
		return item;
	}

	function paintCard(widget, data, units) {
		var set = widget.dataset;
		var content = widget.querySelector('.WIYCW-content');
		var now = data.now;
		var icons = set.path + '/resources/icons/weather-icons/';
		var fragment = document.createDocumentFragment();

		// the sky: its colour and its movement tell the weather; the light, the moment of the day. Without the conditions
		// of now there is nothing to put on it: the name of the place (and the units) go on a plain line
		var withNow = set.today !== 'off';
		content.classList.toggle('WIYCW-no-sky', !withNow);
		var look = sceneOf(now);
		var sky = withNow
			? el('section', 'WIYCW-skycard WIYCW-sky-' + look.scene + (look.light !== 'day' ? ' WIYCW-is-' + look.light : ''))
			: el('div', 'WIYCW-card-plain');
		if (withNow && set.animation !== 'off') {
			var fx = el('i', 'WIYCW-skyfx');
			fx.setAttribute('aria-hidden', 'true');
			fx.appendChild(el('i', 'WIYCW-skydeck'));
			sky.appendChild(fx);
		}
		var head = el('div', 'WIYCW-card-head');
		head.appendChild(el('div', 'WIYCW-card-title', set.title || set.city || ''));
		if (set.unitswitch === 'on') {
			var toggle = el('div', 'WIYCW-units');
			toggle.setAttribute('role', 'group');
			toggle.setAttribute('aria-label', t('units', 'Units'));
			[['c', '°C'], ['f', '°F']].forEach(function (option) {
				var button = el('button', 'WIYCW-units-btn', option[1]);
				button.type = 'button';
				button.setAttribute('aria-pressed', units === option[0] ? 'true' : 'false');
				button.addEventListener('click', function () {
					storeUnits(option[0]);
					repaintAll();
				});
				toggle.appendChild(button);
			});
			head.appendChild(toggle);
		}
		sky.appendChild(head);
		if (!withNow) {
			fragment.appendChild(sky);
		}

		var body = el('div', 'WIYCW-skybody WIYCW-card-body');
		var text = condition(now);
		if (set.today !== 'off') {
			var main = el('div', 'WIYCW-card-main');
			if (set.nowicon !== 'off') {
				main.appendChild(modernImage(icons + 'sky/', now, text, 84, 'WIYCW-card-icon'));
			}
			if (set.nowtemp !== 'off') {
				main.appendChild(el('div', 'WIYCW-card-temp', temp(now.temp, units)));
			}
			body.appendChild(main);
			var line = [];
			if (set.description !== 'off' && text) {
				line.push(text);
			}
			if (set.feelslike !== 'off' && isNumber(now.feels_like)) {
				line.push(t('feels', 'Feels like') + ' ' + temp(now.feels_like, units));
			}
			if (line.length) {
				body.appendChild(el('div', 'WIYCW-card-desc', line.join(' · ')));
			}
			var today = data.forecast[0];
			if (today && isNumber(today.temp_max)) {
				body.appendChild(el('div', 'WIYCW-card-range', capitalize(t('today', 'today')) + ' ' + temp(today.temp_max, units) + ' / ' + temp(today.temp_min, units)));
			}
		}
		if (withNow) {
			sky.appendChild(body);
			fragment.appendChild(sky);
		}

		if (set.today !== 'off') {
			var facts = el('div', 'WIYCW-card-facts');
			if (set.nowhumidity === 'on' && isNumber(now.humidity)) {
				facts.appendChild(fact(widget, 'dew-point.svg', t('humidity', 'Humidity'), now.humidity + '%'));
			}
			if (set.nowwind === 'on' && isNumber(now.wind_speed)) {
				var gust = isNumber(now.gust) && now.gust >= now.wind_speed + 10 ? ' (' + speed(now.gust, units) + ')' : '';
				facts.appendChild(fact(widget, isNumber(now.wind_deg) ? 'arrow.svg' : 'wind.svg', t('wind', 'Wind'), speed(now.wind_speed, units) + gust, now.wind_deg));
			}
			if (set.nowpressure === 'on' && isNumber(now.pressure)) {
				facts.appendChild(fact(widget, 'pressure.svg', t('pressure', 'Pressure'), now.pressure + ' hPa'));
			}
			if (set.nowcloudiness === 'on' && isNumber(now.cloudiness)) {
				facts.appendChild(fact(widget, 'clouds.svg', t('cloudiness', 'Cloudiness'), now.cloudiness + '%'));
			}
			if (set.nowvisibility === 'on' && isNumber(now.visibility)) {
				facts.appendChild(fact(widget, 'visibility.svg', t('visibility', 'Visibility'), distance(now.visibility, units)));
			}
			if (set.nowsunrise === 'on' && now.sunrise) {
				facts.appendChild(fact(widget, 'sunrise.svg', t('sunrise', 'Sunrise'), clock(now.sunrise, set.timeformat)));
				facts.appendChild(fact(widget, 'sunset.svg', t('sunset', 'Sunset'), clock(now.sunset, set.timeformat)));
			}
			if (facts.childNodes.length) {
				fragment.appendChild(facts);
			}
		}

		if (set.hourly === 'on' && Array.isArray(data.hourly) && data.hourly.length) {
			var hours = el('div', 'WIYCW-card-hours');
			hours.setAttribute('role', 'list');
			hours.setAttribute('aria-label', t('hourly', 'Hourly forecast'));
			data.hourly.slice(0, 8).forEach(function (slot) {
				var item = el('div', 'WIYCW-hour');
				item.setAttribute('role', 'listitem');
				item.appendChild(el('div', 'WIYCW-hour-time', hourOnly(slot.hour, set.timeformat)));
				item.appendChild(modernImage(icons + 'modern/', slot, condition(slot), 30, 'WIYCW-hour-icon'));
				item.appendChild(el('div', 'WIYCW-hour-temp', temp(slot.temp, units)));
				item.appendChild(el('div', 'WIYCW-hour-pop' + (isNumber(slot.pop) && slot.pop >= 0.3 ? ' is-wet' : ''), isNumber(slot.pop) ? Math.round(slot.pop * 100) + '%' : ''));
				hours.appendChild(item);
			});
			fragment.appendChild(hours);
		}

		var count = Number(set.days);
		if (set.days !== 'off' && !(count === 0)) {
			var days = data.forecast.filter(function (day, index) { return !(index + 1 > count); });
			var low = Infinity;
			var high = -Infinity;
			days.forEach(function (day) {
				if (isNumber(day.temp_min) && isNumber(day.temp_max)) {
					low = Math.min(low, day.temp_min);
					high = Math.max(high, day.temp_max);
				}
			});
			var span = Math.max(1, high - low);
			var list = el('div', 'WIYCW-card-days');
			list.setAttribute('role', 'list');
			list.setAttribute('aria-label', t('forecast', 'Forecast'));
			days.forEach(function (day, index) {
				var row = el('div', 'WIYCW-day');
				row.setAttribute('role', 'listitem');
				row.appendChild(el('div', 'WIYCW-day-name', dayLabel(day, index)));
				if (set.forecasticon !== 'off') {
					row.appendChild(modernImage(icons + 'modern/', day, condition(day), 30, 'WIYCW-day-icon'));
				}
				// the rain has its column only when it is shown (empty on a dry day, so that the columns line up): a
				// column switched off leaves no gap
				var wet = el('div', 'WIYCW-day-wet');
				// a dry day says so (0% and 0 mm, toned down) rather than leaving a blank
				if (set.rainchance !== 'off' && isNumber(day.pop)) {
					wet.appendChild(el('span', 'WIYCW-day-pop' + (day.pop >= 0.5 ? ' is-wet' : day.pop < 0.05 ? ' is-none' : ''), Math.round(day.pop * 100) + '%'));
				}
				if (set.rain === 'on') {
					var litres = isNumber(day.rain) && day.rain >= 0.1;
					wet.appendChild(el('span', 'WIYCW-day-rain' + (litres ? '' : ' is-none'), volumeShort(litres ? day.rain : 0, units)));
				}
				if (set.rainchance !== 'off' || set.rain === 'on') {
					row.appendChild(wet);
				}
				if (set.temp !== 'off' && isNumber(day.temp_min) && isNumber(day.temp_max)) {
					row.appendChild(el('div', 'WIYCW-day-min', temp(day.temp_min, units)));
					var track = el('div', 'WIYCW-day-track');
					track.setAttribute('aria-hidden', 'true');
					var bar = el('span', 'WIYCW-day-bar');
					bar.style.left = Math.round((day.temp_min - low) / span * 100) + '%';
					bar.style.right = Math.round((high - day.temp_max) / span * 100) + '%';
					track.appendChild(bar);
					row.appendChild(track);
					row.appendChild(el('div', 'WIYCW-day-max', temp(day.temp_max, units)));
				}
				if (set.wind === 'on' && isNumber(day.wind_speed)) {
					var dayWind = el('div', 'WIYCW-day-wind');
					if (isNumber(day.wind_deg)) {
						var dayArrow = image(set.path + '/resources/icons/ui-icons/arrow.svg', '', 10, 'WIYCW-day-arrow');
						dayArrow.setAttribute('aria-hidden', 'true');
						dayArrow.style.transform = 'rotate(' + day.wind_deg + 'deg)';
						dayWind.appendChild(dayArrow);
					}
					dayWind.appendChild(document.createTextNode(speed(day.wind_speed, units)));
					row.appendChild(dayWind);
				}
				list.appendChild(row);
			});
			fragment.appendChild(list);
		}

		if (set.updated === 'on' && isNumber(data.updated)) {
			var when = new Date(data.updated * 1000);
			var stamp = ('0' + when.getHours()).slice(-2) + ':' + ('0' + when.getMinutes()).slice(-2);
			fragment.appendChild(el('div', 'WIYCW-card-updated', t('updated', 'Updated at %s').replace('%s', clock(stamp, set.timeformat))));
		}

		content.textContent = '';
		content.appendChild(fragment);
	}

	// ---- one widget ----

	var widgets = []; // {widget, data}

	function unitsOf(widget) {
		var own = widget.dataset.units === 'c' || String(widget.dataset.units).toLowerCase() === 'c' ? 'c' : 'f';
		return widget.dataset.unitswitch === 'on' && widget.dataset.design === 'card' ? (storedUnits() || own) : own;
	}

	function paint(entry) {
		var widget = entry.widget;
		var holder = widget.querySelector('script.WIYCW-data');
		try {
			if (widget.dataset.design === 'card') {
				paintCard(widget, entry.data, unitsOf(widget));
			} else {
				paintClassic(widget, entry.data, unitsOf(widget));
			}
			widget.removeAttribute('aria-busy');
			widget.classList.add('WIYCW-ready');
		} catch (e) {
			if (window.console && console.error) {
				console.error('Weather Widget Pro', e);
			}
			unavailable(widget, 'service');
		}
		// the data of the page stays for a later start (the block editor paints again)
		if (holder && !holder.parentNode) {
			widget.appendChild(holder);
		}
	}

	function repaintAll() {
		widgets = widgets.filter(function (entry) { return !!entry.widget.ownerDocument && entry.widget.ownerDocument.documentElement.contains(entry.widget); });
		widgets.forEach(function (entry) {
			if (entry.data) {
				paint(entry);
			}
		});
	}

	// Without weather to show: a line for every visitor and, for who can edit the site (data-admin), why and what to do
	function unavailable(widget, why) {
		var content = widget.querySelector('.WIYCW-content');
		if (!content) {
			return;
		}
		var box = el('div', 'WIYCW-message', why === 'blocked'
			? t('err_blocked', 'The weather service is not available for this site.')
			: t('unavailable', 'Weather data is temporarily unavailable.'));
		if (widget.dataset.admin === '1') {
			var reasons = {
				blocked: t('admin_blocked', 'The weather service has blocked this site. Contact us at weatherin.org/contact to restore access.'),
				limit: t('admin_limit', 'Too many requests to the weather service. The widget will try again in a few minutes.'),
				city: t('admin_city', 'The city of this widget was not found. Choose it again in the widget settings.'),
				network: t('admin_network', 'This site could not reach the weather service (eltiempoen.com). Check that your server allows outgoing connections.'),
				service: t('admin_service', 'The weather service did not respond. The widget will try again in a few minutes.'),
				site: t('admin_site', 'This site did not respond to the request of the widget (admin-ajax.php).')
			};
			var note = el('div', 'WIYCW-message-admin', reasons[why] || reasons.service);
			note.appendChild(el('span', 'WIYCW-message-who', ' ' + t('admin_only', '(Only site editors can see this.)')));
			box.appendChild(note);
		}
		content.textContent = '';
		content.appendChild(box);
		widget.removeAttribute('aria-busy');
		widget.classList.add('WIYCW-ready');
	}

	function start(widget) {
		if (widget.getAttribute('data-wiycw') === '1' || !widget.querySelector('.WIYCW-content')) {
			return;
		}
		widget.setAttribute('data-wiycw', '1');
		var entry = {widget: widget, data: null};
		widgets.push(entry);

		// the classic design takes its colours from its settings, as always
		if (widget.dataset.design !== 'card') {
			widget.style.color = widget.dataset.textcolor;
			widget.style.backgroundColor = widget.dataset.backgroundcolor;
			widget.style.border = '1px solid ' + widget.dataset.bordercolor;
		}

		var got = function (data, why) {
			if (data) {
				entry.data = data;
				paint(entry);
			} else if (!entry.data) {
				unavailable(widget, why);
			}
		};
		var inPage = carried(widget);
		if (inPage && inPage.age < USABLE) {
			entry.data = inPage.data;
			paint(entry);
			if (inPage.age < FRESH) {
				return;
			}
		}
		request(widget, got);
	}

	function init(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var found = scope.querySelectorAll('.WIYCW-wrapper[data-f="2"]');
		for (var i = 0; i < found.length; i++) {
			start(found[i]);
		}
		if (scope !== document && scope.matches && scope.matches('.WIYCW-wrapper[data-f="2"]')) {
			start(scope);
		}
	}

	function ready() {
		init(document);
		// widgets put on the page later (page builders, the block editor). The whole document is watched, not its body:
		// the frame of the block editor gets its body after this script has run
		if (window.MutationObserver && document.documentElement) {
			var timer = null;
			new MutationObserver(function (changes) {
				for (var i = 0; i < changes.length; i++) {
					if (changes[i].addedNodes && changes[i].addedNodes.length) {
						if (!timer) {
							timer = setTimeout(function () { timer = null; init(document); }, 100);
						}
						return;
					}
				}
			}).observe(document.documentElement, {childList: true, subtree: true});
		}
	}

	window.WIYCW = {init: init};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', ready);
	} else {
		ready();
	}
})();
