(function () {
	'use strict';

	var FRESH = 30 * 60;
	var USABLE = 3 * 3600;
	var NOW_MAX = 3 * 3600;
	var STALE_MAX = 2 * 86400;
	var TTL_MAX = 6 * 3600;
	var UNITS_KEY = 'wiycw-units';
	var WEEK_DAYS = ['mon.', 'tue.', 'wed.', 'thu.', 'fri.', 'sat.', 'sun.'];

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

	var apart = false;

	function directionOf(widget) {
		try {
			return (widget.ownerDocument.defaultView || window).getComputedStyle(widget).direction === 'rtl';
		} catch (e) {
			return false;
		}
	}

	function rightToLeft(widget) {
		var rtl = typeof widget.wiycwDir === 'boolean' ? widget.wiycwDir : directionOf(widget);
		widget.wiycwDir = null;
		widget.classList.toggle('WIYCW-rtl', rtl);
		return rtl;
	}

	function readDirections(list) {
		list.forEach(function (widget) {
			widget.wiycwDir = directionOf(widget);
		});
		if (later) {
			later.push(function () {
				list.forEach(function (widget) {
					widget.wiycwDir = null;
				});
			});
		}
	}

	function num(text) {
		if (!apart) {
			return document.createTextNode(String(text));
		}
		var node = el('span', 'WIYCW-num', text);
		node.dir = 'ltr';
		return node;
	}

	function withNum(node, before, figure, after) {
		if (!apart) {
			node.appendChild(document.createTextNode((before || '') + figure + (after || '')));
			return node;
		}
		if (before) {
			node.appendChild(document.createTextNode(before));
		}
		node.appendChild(num(figure));
		if (after) {
			node.appendChild(document.createTextNode(after));
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

	function blank(size, className) {
		var node = el('span', className);
		node.setAttribute('aria-hidden', 'true');
		node.style.display = 'block';
		node.style.flexShrink = '0';
		node.style.width = size + 'px';
		node.style.height = size + 'px';
		node.style.visibility = 'hidden';
		return node;
	}

	function isNumber(value) {
		return typeof value === 'number' && isFinite(value);
	}


	var skew = null;

	function nowFor(data) {
		var now = Date.now() / 1000;
		if (skew !== null) {
			return now - skew;
		}
		if (data && isNumber(data.updated)) {
			now = Math.min(Math.max(now, data.updated), data.updated + STALE_MAX + USABLE);
		}
		return now;
	}

	function learnClock(from, to) {
		var device = Date.now() / 1000;
		if (!isNumber(from)) {
			return;
		}
		if (device < from || (isNumber(to) && device > to)) {
			skew = device - from;
		} else if (isNumber(to)) {
			skew = 0;
		}
	}

	function heard(data) {
		var site = data && typeof data === 'object' && data.wiycw && typeof data.wiycw === 'object' ? data.wiycw : null;
		if (site && isNumber(site.saved) && site.saved > 0) {
			learnClock(site.saved, site.saved + (site.stale ? STALE_MAX : TTL_MAX + 300) + 60);
		}
	}

	function pad(value) {
		return ('0' + value).slice(-2);
	}

	function ymd(seconds) {
		var when = new Date(seconds * 1000);
		return when.getUTCFullYear() + '-' + pad(when.getUTCMonth() + 1) + '-' + pad(when.getUTCDate());
	}


	function temp(value, units) {
		if (!isNumber(value)) {
			return '-';
		}
		return (units === 'c' ? Math.round(value) : Math.round(value * 1.8 + 32)) + '°';
	}

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
		var figure = units === 'c' ? Math.round(value * 10) / 10 : Math.round(value / 25.4 * 100) / 100;
		return {text: figure + (units === 'c' ? ' mm' : ' in'), none: figure === 0};
	}

	function distance(meters, units) {
		if (!isNumber(meters)) {
			return '-';
		}
		return units === 'c' ? (Math.round(meters / 100) / 10) + ' km' : (Math.round(meters / 1609.34 * 10) / 10) + ' mi';
	}

	function chance(value) {
		return Math.min(1, Math.max(0, value));
	}

	function percent(value) {
		return Math.round(chance(value) * 100) + '%';
	}

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

	var chosenUnits = null;

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


	var OWM_ICON = /^(01|02|03|04|09|10|11|13|50)[dn]$/;
	var MODERN_ICON = /^wi-[a-z]+(-[a-z]+)*$/;
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

	function knownIcon(item) {
		return !!item && ((typeof item.icon2 === 'string' && MODERN_ICON.test(item.icon2)) || OWM_ICON.test(String(item.icon)));
	}

	function modernIcon(item) {
		if (item && typeof item.icon2 === 'string' && MODERN_ICON.test(item.icon2)) {
			return item.icon2;
		}
		return FROM_OWM[owmIcon(item && item.icon)];
	}

	function cardImage(src, alt, size, className) {
		var node = image(src, alt, size, className);
		node.style.maxWidth = size + 'px';
		return node;
	}

	function modernImage(base, item, alt, size, className) {
		var node = cardImage(base + modernIcon(item) + '.svg', alt, size, className);
		node.onerror = function () {
			node.onerror = null;
			node.src = node.src.replace(/[^\/]*\.svg(?=$|[?#])/, FROM_OWM[owmIcon(item && item.icon)] + '.svg');
		};
		return node;
	}

	function sceneOf(now) {
		var light = now.light === 'night' || now.light === 'dawn' || now.light === 'dusk' || now.light === 'day' ? now.light
			: (String(now.icon || '').slice(-1) === 'n' || String(now.icon2 || '').slice(-2) === '-n' ? 'night' : 'day');
		var scene = SCENE_NAMES.indexOf(now.scene) > -1 ? now.scene : null;
		if (!scene && knownIcon(now)) {
			var name = modernIcon(now).replace(/^wi-/, '');
			var base = name.replace(/-[dn]$/, '');
			scene = base === 'clear' ? (name.slice(-2) === '-n' ? 'stars' : 'sun') : (SCENES[base] || 'few');
			if (scene === 'sun' && light === 'night') {
				scene = 'stars';
			}
		}
		return {scene: scene, light: light};
	}


	var THEMES = ['dark', 'full', 'flat', 'blend'];

	function themeOf(widget) {
		var theme = widget.getAttribute('data-theme');
		return THEMES.indexOf(theme) > -1 ? theme : 'sky';
	}

	var painter;
	var painted = {};

	function rgb(text) {
		text = String(text || '').trim();
		var hex = text.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
		if (hex) {
			var h = hex[1].length === 3 ? hex[1].replace(/(.)/g, '$1$1') : hex[1];
			return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16), 1];
		}
		var parts = text.match(/^rgba?\(([^)]+)\)$/i);
		if (parts) {
			var n = parts[1].split(/[\s,\/]+/).filter(function (v) { return v !== ''; }).map(function (v) {
				return /%$/.test(v) ? parseFloat(v) / 100 : parseFloat(v);
			});
			if (n.length > 2 && isNumber(n[0]) && isNumber(n[1]) && isNumber(n[2])) {
				return [n[0], n[1], n[2], n.length > 3 && isNumber(n[3]) ? n[3] : 1];
			}
		}
		if (!text || text === 'none') {
			return null;
		}
		if (painted.hasOwnProperty(text)) {
			return painted[text];
		}
		var colour = null;
		try {
			if (painter === undefined) {
				var canvas = document.createElement('canvas');
				canvas.width = 1;
				canvas.height = 1;
				painter = (canvas.getContext && canvas.getContext('2d', {willReadFrequently: true})) || null;
			}
			if (painter) {
				painter.clearRect(0, 0, 1, 1);
				painter.fillStyle = 'rgba(0, 0, 0, 0)';
				painter.fillStyle = text;
				painter.fillRect(0, 0, 1, 1);
				var pixel = painter.getImageData(0, 0, 1, 1).data;
				colour = [pixel[0], pixel[1], pixel[2], pixel[3] / 255];
			}
		} catch (e) {
			colour = null;
		}
		painted[text] = colour;
		return colour;
	}

	function luminance(c) {
		var a = c.slice(0, 3).map(function (v) {
			v /= 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
		});
		return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
	}

	function contrast(a, b) {
		var x = luminance(a);
		var y = luminance(b);
		return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
	}

	function mix(a, b, part) {
		return a.slice(0, 3).map(function (v, i) { return Math.round(v + (b[i] - v) * part); });
	}

	function css(c) {
		return 'rgb(' + c.join(',') + ')';
	}

	var WHITE = [255, 255, 255];
	var BLACK = [0, 0, 0];
	var WET = [0, 119, 182];
	var WET_DARK = [102, 200, 245];
	var LOGO = [0, 171, 233];

	function parentOf(node) {
		return node.parentElement || (node.parentNode && node.parentNode.host) || null;
	}

	function groundOf(widget, view) {
		var colour = [0, 0, 0];
		var cover = 0;
		for (var node = parentOf(widget); node && node.nodeType === 1 && cover < 0.995; node = parentOf(node)) {
			var style = view.getComputedStyle(node);
			if (style.backgroundImage && style.backgroundImage !== 'none') {
				return null;
			}
			var back = rgb(style.backgroundColor);
			if (back && back[3] > 0) {
				var part = (1 - cover) * back[3];
				colour = colour.map(function (v, i) { return v + part * back[i]; });
				cover += part;
			}
		}
		return colour.map(function (v, i) { return Math.round(v + (1 - cover) * WHITE[i]); });
	}

	function groundState(widget) {
		var state = {dark: false, wet: css(WET), logo: 'blue'};
		try {
			var view = widget.ownerDocument.defaultView || window;
			var parent = parentOf(widget) || widget;
			var ink = rgb(view.getComputedStyle(parent).color);
			var ground = groundOf(widget, view);
			var light = ink && ink[3] >= 0.5 ? luminance(ink) : null;
			var against = light !== null && ground ? contrast(mix(ground, ink, ink[3]), ground) : 0;
			if (against >= 1.5 && against < 3) {
				light = null;
			}
			if (light !== null && light > 0.4) {
				state.dark = true;
			} else if (light !== null && light < 0.1) {
				state.dark = false;
			} else if (ground) {
				state.dark = luminance(ground) < 0.18;
			} else {
				state.dark = light !== null && light > 0.18;
			}
			state.wet = 'currentColor';
			if (ground) {
				var base = state.dark ? WET_DARK : WET;
				for (var part = 0; part <= 0.5; part += 0.05) {
					var wet = mix(base, state.dark ? WHITE : BLACK, part);
					if (contrast(wet, ground) >= 4.5) {
						state.wet = css(wet);
						break;
					}
				}
			}
			if (ground) {
				var blueReads = contrast(LOGO, ground) >= 2;
				var whiteReads = contrast(WHITE, ground) >= 3;
				state.logo = state.dark ? (whiteReads ? 'light' : blueReads ? 'blue' : 'pill') : (blueReads ? 'blue' : whiteReads ? 'light' : 'pill');
			} else {
				state.logo = state.dark ? 'light' : 'blue';
			}
		} catch (e) {
			state = {dark: false, wet: css(WET), logo: 'blue'};
		}
		return state;
	}

	function logoTo(img, light) {
		var swap = function (src) {
			return String(src).replace(/(-light)?\.svg(?=$|[?#])/, light ? '-light.svg' : '.svg');
		};
		var src = img.getAttribute('src');
		if (src && swap(src) !== src) {
			img.setAttribute('src', swap(src));
		}
		['data-src', 'data-lazy-src'].forEach(function (name) {
			var value = img.getAttribute(name);
			if (value && swap(value) !== value) {
				img.setAttribute(name, swap(value));
			}
		});
	}

	function blendGround(widget, known) {
		if (themeOf(widget) !== 'blend') {
			return false;
		}
		var state = known || groundState(widget);
		widget.wiycwState = state;
		var sign = state.dark + '|' + state.wet + '|' + state.logo;
		if (widget.wiycwGround === sign) {
			return state.dark;
		}
		widget.wiycwGround = sign;
		widget.classList.toggle('WIYCW-on-dark', state.dark);
		widget.classList.toggle('WIYCW-logo-pill', state.logo === 'pill');
		widget.style.setProperty('--wiycw-wet', state.wet);
		var logo = widget.querySelector('.WIYCW-credit img');
		if (logo) {
			logoTo(logo, state.logo === 'light');
		}
		var icons = widget.querySelectorAll('.WIYCW-hour-icon, .WIYCW-day-icon, .WIYCW-card-plain .WIYCW-card-icon');
		for (var i = 0; i < icons.length; i++) {
			var src = icons[i].getAttribute('src');
			if (src) {
				icons[i].setAttribute('src', src.replace(/\/(modern|sky)\/(?=[^\/]*$)/, state.dark ? '/sky/' : '/modern/'));
			}
		}
		return state.dark;
	}

	var watching = [];

	function groundLater(widget) {
		if (!later) {
			blendGround(widget);
		} else if (!widget.wiycwQueued) {
			widget.wiycwQueued = true;
			later.push(function () {
				widget.wiycwQueued = false;
				blendGround(widget);
			});
		}
		return false;
	}

	function watchGround(doc) {
		for (var i = 0; i < watching.length; i++) {
			if (watching[i].doc === doc) {
				return;
			}
		}
		var view = doc.defaultView || window;
		var quick = null;
		var settle = null;
		var last = null;
		var signOf = function () {
			return [doc.documentElement, doc.body].map(function (node) {
				if (!node) {
					return '';
				}
				var name = node.className;
				return String(name && name.baseVal !== undefined ? name.baseVal : name) + '/' + node.getAttribute('data-theme') + '/' +
					node.getAttribute('data-bs-theme');
			}).join('|');
		};
		var lookAll = function (walk) {
			prune();
			if (!doc.defaultView) {
				return;
			}
			var sign = signOf();
			if (!walk && sign === last) {
				return;
			}
			last = sign;
			widgets.forEach(function (entry) {
				var widget = entry.widget;
				if (widget.ownerDocument !== doc || themeOf(widget) !== 'blend') {
					return;
				}
				var seen = widget.wiycwSeen || {};
				blendGround(widget, !walk && seen.hasOwnProperty(sign) ? seen[sign] : null);
				if (Object.keys(seen).length > 8) {
					seen = {};
				}
				seen[sign] = widget.wiycwState;
				widget.wiycwSeen = seen;
			});
		};
		var look = function (event) {
			var walk = !!(event && event.matches !== undefined);
			clearTimeout(quick);
			clearTimeout(settle);
			quick = setTimeout(function () { lookAll(walk); }, 50);
			settle = setTimeout(function () { lookAll(true); }, 700);
		};
		var watcher = null;
		var scheme = null;
		if (view.MutationObserver) {
			watcher = new view.MutationObserver(look);
			var options = {attributes: true, attributeFilter: ['class', 'data-theme', 'data-bs-theme']};
			[doc.documentElement, doc.body].forEach(function (node) {
				if (node) {
					watcher.observe(node, options);
				}
			});
		}
		if (view.matchMedia) {
			scheme = view.matchMedia('(prefers-color-scheme: dark)');
			if (scheme.addEventListener) {
				scheme.addEventListener('change', look);
			} else if (scheme.addListener) {
				scheme.addListener(look);
			}
		}
		watching.push({doc: doc, stop: function () {
			clearTimeout(quick);
			clearTimeout(settle);
			if (watcher) {
				watcher.disconnect();
			}
			if (scheme && scheme.removeEventListener) {
				scheme.removeEventListener('change', look);
			} else if (scheme && scheme.removeListener) {
				scheme.removeListener(look);
			}
		}});
	}

	var SKY_HI = {cloudy: [125, 144, 163], wind: [125, 144, 163], fog: [128, 140, 151], snow: [130, 151, 171], drizzle: [115, 134, 154],
		sleet: [120, 135, 150], blizzard: [110, 131, 152], rain: [104, 123, 143], downpour: [90, 108, 128], storm: [75, 90, 108],
		thunder: [75, 90, 108], overcast: [111, 130, 150], dust: [156, 134, 95]};

	function skyHi(sky) {
		var scene = (sky.className.match(/WIYCW-sky-([a-z]+)/) || [])[1];
		var clear = !scene || scene === 'sun' || scene === 'few' || scene === 'stars';
		if (sky.classList.contains('WIYCW-is-night')) {
			return scene === 'stars' || scene === 'few' ? [22, 62, 108] : [52, 63, 75];
		}
		if (sky.classList.contains('WIYCW-is-dusk') && (scene === 'sun' || scene === 'few')) {
			return [31, 122, 196];
		}
		return clear ? [0, 171, 233] : (SKY_HI[scene] || [0, 171, 233]);
	}

	function deepSky(widget, sky) {
		var deep = null;
		try {
			if (sky) {
				var view = widget.ownerDocument.defaultView || window;
				var base = rgb(view.getComputedStyle(sky).getPropertyValue('--wiycw-sky-hi')) || skyHi(sky);
				var night = [11, 26, 46];
				for (var part = 0; part <= 1; part += 0.02) {
					deep = mix(base, night, part);
					var text = luminance(mix(deep, WHITE, 0.8));
					if ((text + 0.05) / (luminance(deep) + 0.05) >= 4.6) {
						break;
					}
				}
			}
		} catch (e) {
			deep = null;
		}
		if (deep) {
			widget.style.setProperty('--wiycw-deep', css(deep));
			widget.style.setProperty('--wiycw-deeper', css(mix(deep, [11, 26, 46], 0.35)));
		} else {
			widget.style.removeProperty('--wiycw-deep');
			widget.style.removeProperty('--wiycw-deeper');
		}
	}

	function skiesAgain() {
		var again = function () {
			widgets.forEach(function (entry) {
				if (entry.data && themeOf(entry.widget) === 'full') {
					deepSky(entry.widget, entry.widget.querySelector('.WIYCW-skycard'));
				}
			});
		};
		again();
		setTimeout(again, 1000);
	}

	function watchSkySheet(link) {
		if (link.wiycwWatched || !/WIYCW-sky/.test(link.getAttribute('href') || '')) {
			return;
		}
		link.wiycwWatched = true;
		link.addEventListener('load', skiesAgain);
	}

	function condition(item) {
		var texts = i18n().conditions || {};
		var text = item && texts[String(item.code)];
		return typeof text === 'string' ? text : '';
	}


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
			heard(data);
			if (valid(tidy(data))) {
				return done(data, null);
			}
			var why = 'site';
			if (data && typeof data === 'object') {
				why = typeof data.kind === 'string' ? data.kind : (Array.isArray(data.forecast) && data.now ? 'service' : 'site');
			}
			done(null, why);
		};
		request.open('GET', url);
		request.timeout = 15000;
		request.send();
	}

	function tidy(data) {
		var whole = function (item) { return !!item && typeof item === 'object'; };
		if (data && typeof data === 'object') {
			if (Array.isArray(data.forecast)) {
				data.forecast = data.forecast.filter(whole);
			}
			data.hourly = Array.isArray(data.hourly) ? data.hourly.filter(whole) : [];
			current(data);
		}
		return data;
	}

	function current(data) {
		var city = data.city;
		if (!city || typeof city !== 'object' || !isNumber(city.utc_offset)) {
			return;
		}
		var now = nowFor(data);
		var today = ymd(now + city.utc_offset);
		if (Array.isArray(data.forecast)) {
			while (data.forecast.length && /^\d{4}-\d\d-\d\d$/.test(String(data.forecast[0].date)) && data.forecast[0].date < today) {
				data.forecast.shift();
			}
		}
		var hours = data.hourly;
		var step = hours.length > 1 && isNumber(hours[0].ts) && isNumber(hours[1].ts) && hours[1].ts > hours[0].ts &&
			hours[1].ts - hours[0].ts <= 6 * 3600 ? hours[1].ts - hours[0].ts : 3 * 3600;
		data.hourly = hours.filter(function (slot) { return !isNumber(slot.ts) || slot.ts + step > now; });
		if (!data.now || typeof data.now !== 'object' || !isNumber(data.updated) || now - data.updated <= NOW_MAX) {
			return;
		}
		var day = data.forecast && data.forecast[0] && data.forecast[0].date === today ? data.forecast[0] : null;
		var slot = null;
		data.hourly.forEach(function (item) {
			if (!slot && isNumber(item.ts) && item.ts <= now && now < item.ts + step) {
				slot = item;
			}
		});
		var was = data.now;
		var fresh = {observed: false, sunrise: was.sunrise, sunset: was.sunset};
		var from = slot || day;
		if (from) {
			['icon', 'icon2', 'code', 'feels_like', 'wind_speed', 'wind_deg', 'gust'].forEach(function (key) {
				fresh[key] = from[key];
			});
			fresh.temp = slot ? slot.temp : partOfDay(day, new Date((now + city.utc_offset) * 1000).getUTCHours());
			if (!slot) {
				fresh.feels_like = null;
				fresh.gust = null;
				var light = lightAt(day.sunrise || was.sunrise, day.sunset || was.sunset, now + city.utc_offset);
				if (light) {
					fresh.light = light;
					if (light === 'night') {
						fresh.icon = typeof fresh.icon === 'string' ? fresh.icon.replace(/^(\d\d)d$/, '$1n') : fresh.icon;
						fresh.icon2 = typeof fresh.icon2 === 'string' ? fresh.icon2.replace(/-d$/, '-n') : fresh.icon2;
					}
				}
			}
		}
		if (day) {
			fresh.humidity = day.humidity;
			fresh.pressure = day.pressure;
			fresh.cloudiness = day.clouds;
			fresh.sunrise = day.sunrise || fresh.sunrise;
			fresh.sunset = day.sunset || fresh.sunset;
		}
		data.now = fresh;
	}

	function lightAt(sunrise, sunset, local) {
		var minutes = function (time) {
			var match = String(time || '').match(/^([01]?\d|2[0-3]):([0-5]\d)/);
			return match ? Number(match[1]) * 60 + Number(match[2]) : null;
		};
		var rise = minutes(sunrise);
		var set = minutes(sunset);
		if (rise === null || set === null || set <= rise) {
			return null;
		}
		var at = Math.floor((((local % 86400) + 86400) % 86400) / 60);
		return at >= rise && at < set ? 'day' : 'night';
	}

	function partOfDay(day, hour) {
		var key = hour >= 6 && hour < 12 ? 'temp_morn' : hour >= 12 && hour < 18 ? 'temp_day' : hour >= 18 && hour < 22 ? 'temp_eve' : 'temp_night';
		return isNumber(day[key]) ? day[key] : null;
	}

	function valid(data) {
		return !!data && typeof data === 'object' && !data.error && !!data.now && typeof data.now === 'object' &&
			Array.isArray(data.forecast) && data.forecast.length > 0;
	}

	var requests = {};

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
			batch(function () {
				entry.waiting.splice(0).forEach(function (fn) { fn(data, why); });
			});
			setTimeout(function () { delete requests[id]; }, 5 * 60 * 1000);
		};
		var site = widget.dataset.url + '?action=' + encodeURIComponent(widget.dataset.action || 'WIYCW_get_weather') +
			'&f=2&id=' + encodeURIComponent(id) + '&k=' + encodeURIComponent(widget.dataset.k || '');
		getJSON(site, finish);
	}

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
		if (saved > 0) {
			learnClock(saved, null);
		}
		if (!valid(tidy(data)) || !(saved > 0)) {
			return null;
		}
		return {data: data, age: Math.max(0, Date.now() / 1000 - (skew || 0) - saved)};
	}


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
		col.appendChild(num(text));
		return col;
	}

	function classicFigure(value, unit) {
		return isNumber(value) ? value + unit : '-';
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
				iconBox.appendChild(knownIcon(data.now)
					? image(iconsBase + owmIcon(data.now.icon) + '.svg', condition(data.now) || t('alt_now', 'Current conditions'), 70, classes.weather)
					: blank(70));
				row1.appendChild(iconBox);
			}
			if (set.nowtemp === 'on') {
				row1.appendChild(withNum(el('div', 'WIYCW-now-temp'), '', tempClassic(data.now.temp, units)));
			}
			if (set.nowicon === 'off' && set.nowtemp === 'off') {
				row1.style.display = 'none';
			}
			var info = el('div', 'WIYCW-now-row-info');
			if (set.nowsunrise === 'on') {
				info.appendChild(infoCol(widget, 'sunrise.svg', t('sunrise', 'Sunrise'), clock(data.now.sunrise, set.timeformat) || '-', classes));
				info.appendChild(infoCol(widget, 'sunset.svg', t('sunset', 'Sunset'), clock(data.now.sunset, set.timeformat) || '-', classes));
			}
			if (set.nowhumidity === 'on') {
				info.appendChild(infoCol(widget, 'dew-point.svg', t('humidity', 'Humidity'), classicFigure(data.now.humidity, '%'), classes));
			}
			if (set.nowwind === 'on') {
				info.appendChild(infoCol(widget, 'arrow.svg', t('wind', 'Wind'), speed(data.now.wind_speed, units), classes, isNumber(data.now.wind_deg) ? data.now.wind_deg : null));
			}
			if (set.nowpressure === 'on') {
				info.appendChild(infoCol(widget, 'pressure.svg', t('pressure', 'Pressure'), classicFigure(data.now.pressure, ' hPa'), classes));
			}
			if (set.nowcloudiness === 'on') {
				info.appendChild(infoCol(widget, 'clouds.svg', t('cloudiness', 'Cloudiness'), classicFigure(data.now.cloudiness, '%'), classes));
			}
			now.appendChild(row1);
			now.appendChild(info);
			fragment.appendChild(now);
		}

		if (set.days !== 'off') {
			var forecast = el('div', 'WIYCW-forecast WIYCW-forecast-' + set.layout);
			var limit = Number(set.days);
			data.forecast.forEach(function (day, index) {
				if (index + 1 > limit) {
					return;
				}
				var row = el('div', 'WIYCW-forecast-row');
				row.appendChild(el('div', 'WIYCW-forecast-date WIYCW-col-1', dayLabel(day, index, widget.getAttribute('data-lang'))));
				if (set.temp === 'on') {
					row.appendChild(withNum(el('div', 'WIYCW-forecast-temp WIYCW-col-1'), '', tempClassic(day.temp_max, units) + ' / ' + tempClassic(day.temp_min, units)));
				}
				if (set.forecasticon === 'on') {
					var box = el('div', 'WIYCW-forecast-icon WIYCW-col-1');
					box.appendChild(knownIcon(day)
						? image(iconsBase + owmIcon(day.icon) + '.svg', condition(day) || t('forecast', 'Forecast'), 30, classes.weather)
						: blank(30));
					row.appendChild(box);
				}
				if (set.rainchance === 'on') {
					var pop = el('div', 'WIYCW-forecast-pop WIYCW-col-1');
					pop.appendChild(image(set.path + '/resources/icons/ui-icons/umbrella.svg', t('rain_chance', 'Rain probability'), 11, 'WIYCW-item-icon ' + classes.ui));
					pop.appendChild(num(isNumber(day.pop) ? percent(day.pop) : '-'));
					row.appendChild(pop);
				}
				if (set.rain === 'on') {
					row.appendChild(withNum(el('div', 'WIYCW-forecast-rain WIYCW-col-1'), '', volume(day.rain, units)));
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
					wind.appendChild(num(speed(day.wind_speed, units)));
					row.appendChild(wind);
				}
				forecast.appendChild(row);
			});
			fragment.appendChild(forecast);
		}

		content.textContent = '';
		content.appendChild(fragment);
	}

	function dayLabel(day, index, lang) {
		var words = i18n();
		if (index === 0) {
			return capitalize(words.today || 'today');
		}
		if (index === 1) {
			return capitalize(words.tomorrow || 'tmrw.');
		}
		var names = Array.isArray(words.week_days) && words.week_days.length === 7 ? words.week_days : WEEK_DAYS;
		var parts = [];
		if (typeof names[day.wday] === 'string' && names[day.wday]) {
			parts.push(capitalize(names[day.wday]));
		}
		if (isNumber(day.day) || (typeof day.day === 'string' && day.day)) {
			if (/^ja(?:[-_]|$)/i.test(String(lang || '')) && parts.length === 1) {
				return String(day.day) + '日(' + parts[0] + ')';
			}
			parts.push(String(day.day));
		}
		return parts.join(' ');
	}

	function updatedStamp(data, set) {
		var offset = data.city && typeof data.city === 'object' && isNumber(data.city.utc_offset) ? data.city.utc_offset : null;
		var now = nowFor(data);
		var when = new Date(data.updated * 1000);
		var parts;
		if (offset !== null) {
			when = new Date((data.updated + offset) * 1000);
			parts = [when.getUTCFullYear(), when.getUTCMonth(), when.getUTCDate(), when.getUTCHours(), when.getUTCMinutes()];
		} else {
			parts = [when.getFullYear(), when.getMonth(), when.getDate(), when.getHours(), when.getMinutes()];
		}
		var stamp = clock(pad(parts[3]) + ':' + pad(parts[4]), set.timeformat);
		var local = new Date(now * 1000);
		var today = offset !== null ? ymd(now + offset) : ymd(Date.UTC(local.getFullYear(), local.getMonth(), local.getDate()) / 1000);
		var day = Date.UTC(parts[0], parts[1], parts[2]);
		if (ymd(day / 1000) !== today) {
			var date = parts[2] + '/' + (parts[1] + 1);
			try {
				var lang = set.lang || document.documentElement.lang || undefined;
				date = new Intl.DateTimeFormat(lang, {day: 'numeric', month: 'numeric', timeZone: 'UTC', calendar: 'gregory', numberingSystem: 'latn'}).format(new Date(day));
			} catch (e) {}
			stamp = date + ' ' + stamp;
		}
		return stamp;
	}


	function fact(widget, iconFile, label, value, rotate) {
		var item = el('div', 'WIYCW-fact');
		var icon = cardImage(widget.dataset.path + '/resources/icons/ui-icons/' + iconFile, '', 16, 'WIYCW-fact-icon');
		icon.setAttribute('aria-hidden', 'true');
		if (isNumber(rotate)) {
			icon.style.transform = 'rotate(' + rotate + 'deg)';
		}
		item.appendChild(icon);
		var text = el('div', 'WIYCW-fact-text');
		text.appendChild(el('span', 'WIYCW-fact-label', label));
		if (Array.isArray(value)) {
			var node = el('span', 'WIYCW-fact-value');
			var holder = node;
			if (apart) {
				holder = el('span', 'WIYCW-num');
				holder.dir = 'ltr';
				node.appendChild(holder);
			}
			value.forEach(function (part, index) {
				if (index) {
					holder.appendChild(document.createTextNode(' '));
				}
				holder.appendChild(el('span', 'WIYCW-fact-part', part));
			});
			text.appendChild(node);
		} else {
			text.appendChild(withNum(el('span', 'WIYCW-fact-value'), '', value));
		}
		item.appendChild(text);
		return item;
	}

	function fitHours(hours) {
		var items = hours.children;
		if (!items.length || !hours.offsetHeight) {
			return;
		}
		var top = items[0].offsetTop;
		for (var i = 0; i < items.length; i++) {
			if (items[i].offsetTop > top) {
				items[i].setAttribute('aria-hidden', 'true');
			} else {
				items[i].removeAttribute('aria-hidden');
			}
		}
	}

	var legacy = null;

	function isLegacy(days) {
		if (legacy === null) {
			try {
				legacy = (days.ownerDocument.defaultView || window).getComputedStyle(days).containerType !== 'inline-size';
			} catch (e) {
				legacy = false;
			}
		}
		return legacy;
	}

	function fitDays(days) {
		var width = days.offsetWidth;
		if (!width || days.wiycwWidth === width) {
			return;
		}
		days.wiycwWidth = width;
		days.classList.remove('WIYCW-tight');
		var rows = days.children;
		for (var i = 0; i < rows.length; i++) {
			if (rows[i].scrollWidth > rows[i].clientWidth + 1) {
				days.classList.add('WIYCW-tight');
				return;
			}
		}
	}

	function fitBox(box) {
		if (box.classList.contains('WIYCW-card-days')) {
			fitDays(box);
		} else {
			fitHours(box);
		}
	}

	var sizes = null;

	function watchHours(entry, hours, days) {
		if (sizes) {
			[entry.hours, entry.days].forEach(function (box) {
				if (box) {
					sizes.unobserve(box);
				}
			});
		}
		entry.hours = hours;
		entry.days = days && isLegacy(days) ? days : null;
		if (sizes === null) {
			sizes = window.ResizeObserver ? new ResizeObserver(function (changes) {
				changes.forEach(function (change) { fitBox(change.target); });
			}) : false;
			if (!sizes) {
				var timer = null;
				window.addEventListener('resize', function () {
					clearTimeout(timer);
					timer = setTimeout(function () {
						widgets.forEach(function (one) {
							[one.hours, one.days].forEach(function (box) {
								if (box) {
									fitBox(box);
								}
							});
						});
					}, 150);
				});
			}
		}
		[entry.hours, entry.days].forEach(function (box) {
			if (!box) {
				return;
			}
			if (sizes) {
				sizes.observe(box);
			} else {
				fitBox(box);
			}
		});
	}

	function paintCard(widget, data, units, entry) {
		var set = widget.dataset;
		var content = widget.querySelector('.WIYCW-content');
		var now = data.now;
		var icons = set.path + '/resources/icons/weather-icons/';
		var fragment = document.createDocumentFragment();
		var theme = themeOf(widget);
		var onDark = theme === 'blend' && (widget.wiycwGround ? widget.classList.contains('WIYCW-on-dark') : later ? groundLater(widget) : blendGround(widget));
		var ground = icons + (theme === 'dark' || theme === 'full' || onDark ? 'sky/' : 'modern/');
		var hours = null;
		var list = null;

		var withNow = set.today !== 'off';
		content.classList.toggle('WIYCW-no-sky', !withNow);
		var look = sceneOf(now);
		var plain = set.animation === 'off' && theme !== 'full';
		var sky = !withNow
			? el('div', 'WIYCW-card-plain')
			: plain
				? el('section', 'WIYCW-skycard WIYCW-card-plain')
				: el('section', 'WIYCW-skycard' + (look.scene ? ' WIYCW-sky-' + look.scene : '') + (look.light !== 'day' ? ' WIYCW-is-' + look.light : ''));
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
			[['c', '°C'], ['f', '°F']].forEach(function (option, index) {
				var button = el('button', 'WIYCW-units-btn', option[1]);
				button.type = 'button';
				button.setAttribute('aria-pressed', units === option[0] ? 'true' : 'false');
				button.addEventListener('click', function () {
					var focused = button.ownerDocument.activeElement === button;
					storeUnits(option[0]);
					repaintAll();
					var again = focused && widget.querySelectorAll('.WIYCW-units-btn')[index];
					if (again) {
						try {
							again.focus({preventScroll: true});
						} catch (e) {
							again.focus();
						}
					}
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
			if (set.nowicon !== 'off' && knownIcon(now)) {
				main.appendChild(modernImage(plain ? ground : icons + 'sky/', now, text, 84, 'WIYCW-card-icon'));
			}
			if (set.nowtemp !== 'off') {
				main.appendChild(withNum(el('div', 'WIYCW-card-temp'), '', temp(now.temp, units)));
			}
			body.appendChild(main);
			var words = set.description !== 'off' && text ? text : '';
			var feels = set.feelslike !== 'off' && isNumber(now.feels_like);
			if (words || feels) {
				var desc = el('div', 'WIYCW-card-desc');
				if (feels) {
					withNum(desc, (words ? words + ' · ' : '') + t('feels', 'Feels like') + ' ', temp(now.feels_like, units));
				} else {
					desc.appendChild(document.createTextNode(words));
				}
				body.appendChild(desc);
			}
			var today = data.forecast[0];
			if (today && isNumber(today.temp_max)) {
				body.appendChild(withNum(el('div', 'WIYCW-card-range'), capitalize(t('today', 'today')) + ' ', temp(today.temp_max, units) + ' / ' + temp(today.temp_min, units)));
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
				var gusty = isNumber(now.gust) && now.gust >= now.wind_speed + 10;
				var windValue = gusty ? [speed(now.wind_speed, units), '(' + speed(now.gust, units) + ')'] : speed(now.wind_speed, units);
				facts.appendChild(fact(widget, isNumber(now.wind_deg) ? 'arrow.svg' : 'wind.svg', t('wind', 'Wind'), windValue, now.wind_deg));
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
			}
			if (set.nowsunrise === 'on' && now.sunset) {
				facts.appendChild(fact(widget, 'sunset.svg', t('sunset', 'Sunset'), clock(now.sunset, set.timeformat)));
			}
			if (facts.childNodes.length) {
				fragment.appendChild(facts);
			}
		}

		if (set.hourly === 'on' && Array.isArray(data.hourly) && data.hourly.length) {
			hours = el('div', 'WIYCW-card-hours');
			hours.setAttribute('role', 'list');
			hours.setAttribute('aria-label', t('hourly', 'Hourly forecast'));
			data.hourly.slice(0, 8).forEach(function (slot) {
				var item = el('div', 'WIYCW-hour');
				item.setAttribute('role', 'listitem');
				item.appendChild(withNum(el('div', 'WIYCW-hour-time'), '', hourOnly(slot.hour, set.timeformat)));
				item.appendChild(knownIcon(slot) ? modernImage(ground, slot, condition(slot), 30, 'WIYCW-hour-icon') : blank(30, 'WIYCW-hour-icon'));
				item.appendChild(withNum(el('div', 'WIYCW-hour-temp'), '', temp(slot.temp, units)));
				var pop = el('div', 'WIYCW-hour-pop' + (isNumber(slot.pop) && chance(slot.pop) >= 0.3 ? ' is-wet' : ''));
				if (isNumber(slot.pop)) {
					pop.appendChild(num(percent(slot.pop)));
				}
				item.appendChild(pop);
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
					low = Math.min(low, day.temp_min, day.temp_max);
					high = Math.max(high, day.temp_min, day.temp_max);
				}
			});
			var span = Math.max(1, high - low);
			list = el('div', 'WIYCW-card-days');
			list.setAttribute('role', 'list');
			list.setAttribute('aria-label', t('forecast', 'Forecast'));
			days.forEach(function (day, index) {
				var row = el('div', 'WIYCW-day');
				row.setAttribute('role', 'listitem');
				row.appendChild(el('div', 'WIYCW-day-name', dayLabel(day, index, widget.getAttribute('data-lang'))));
				if (set.forecasticon !== 'off') {
					row.appendChild(knownIcon(day) ? modernImage(ground, day, condition(day), 30, 'WIYCW-day-icon') : blank(30, 'WIYCW-day-icon'));
				}
				var wet = el('div', 'WIYCW-day-wet');
				if (set.rainchance !== 'off' && isNumber(day.pop)) {
					var dayPop = el('span', 'WIYCW-day-pop' + (chance(day.pop) >= 0.5 ? ' is-wet' : chance(day.pop) < 0.05 ? ' is-none' : ''), percent(day.pop));
					if (apart) {
						dayPop.dir = 'ltr';
					}
					wet.appendChild(dayPop);
				}
				if (set.rain === 'on') {
					var litres = isNumber(day.rain) ? volumeShort(Math.max(0, day.rain), units) : null;
					var rain = el('span', 'WIYCW-day-rain' + (litres && litres.none ? ' is-none' : ''), litres ? litres.text : '');
					if (apart) {
						rain.dir = 'ltr';
					}
					wet.appendChild(rain);
				}
				if (set.rainchance !== 'off' || set.rain === 'on') {
					row.appendChild(wet);
				}
				if (set.temp !== 'off' && isNumber(day.temp_min) && isNumber(day.temp_max)) {
					row.appendChild(withNum(el('div', 'WIYCW-day-min'), '', temp(day.temp_min, units)));
					var track = el('div', 'WIYCW-day-track');
					track.setAttribute('aria-hidden', 'true');
					var bar = el('span', 'WIYCW-day-bar');
					var from = Math.max(0, Math.min(100, Math.round((Math.min(day.temp_min, day.temp_max) - low) / span * 100)));
					var to = Math.max(0, Math.min(100 - from, Math.round((high - Math.max(day.temp_min, day.temp_max)) / span * 100)));
					bar.style.left = from + '%';
					bar.style.right = to + '%';
					bar.style.setProperty('inset-inline-start', from + '%');
					bar.style.setProperty('inset-inline-end', to + '%');
					track.appendChild(bar);
					row.appendChild(track);
					row.appendChild(withNum(el('div', 'WIYCW-day-max'), '', temp(day.temp_max, units)));
				}
				if (set.wind === 'on' && isNumber(day.wind_speed)) {
					var dayWind = el('div', 'WIYCW-day-wind');
					if (isNumber(day.wind_deg)) {
						var dayArrow = cardImage(set.path + '/resources/icons/ui-icons/arrow.svg', '', 10, 'WIYCW-day-arrow');
						dayArrow.setAttribute('aria-hidden', 'true');
						dayArrow.style.transform = 'rotate(' + day.wind_deg + 'deg)';
						dayWind.appendChild(dayArrow);
					}
					dayWind.appendChild(num(speed(day.wind_speed, units)));
					row.appendChild(dayWind);
				}
				list.appendChild(row);
			});
			fragment.appendChild(list);
		}

		if (set.updated === 'on' && isNumber(data.updated)) {
			var said = t('updated', 'Updated at %s').split('%s');
			var updated = el('div', 'WIYCW-card-updated');
			if (said.length > 1) {
				withNum(updated, said[0], updatedStamp(data, set), said.slice(1).join('%s'));
			} else {
				updated.appendChild(document.createTextNode(said[0] + ' '));
				updated.appendChild(num(updatedStamp(data, set)));
			}
			fragment.appendChild(updated);
		}

		content.textContent = '';
		content.appendChild(fragment);
		if (theme === 'full') {
			if (later) {
				later.push(function () { deepSky(widget, withNow ? sky : null); });
			} else {
				deepSky(widget, withNow ? sky : null);
			}
		}
		if (entry) {
			watchHours(entry, hours, list);
		}
	}


	var widgets = [];
	var later = null;

	function unitsOf(widget) {
		var own = String(widget.dataset.units).toLowerCase() === 'f' ? 'f' : 'c';
		return widget.dataset.unitswitch === 'on' && widget.dataset.design === 'card' ? (storedUnits() || own) : own;
	}

	function prune() {
		widgets = widgets.filter(function (entry) {
			var doc = entry.widget.ownerDocument;
			var alive = !!doc && !!doc.defaultView && !!doc.documentElement && doc.documentElement.contains(entry.widget);
			if (!alive && sizes) {
				[entry.hours, entry.days].forEach(function (box) {
					if (box) {
						sizes.unobserve(box);
					}
				});
			}
			return alive;
		});
		watching = watching.filter(function (one) {
			if (one.doc.defaultView) {
				return true;
			}
			one.stop();
			return false;
		});
	}

	function batch(fn) {
		if (later) {
			fn();
			return;
		}
		later = [];
		try {
			fn();
		} finally {
			var reads = later;
			later = null;
			reads.forEach(function (read) { read(); });
		}
	}

	function paint(entry) {
		var widget = entry.widget;
		var holder = widget.querySelector('script.WIYCW-data');
		apart = rightToLeft(widget);
		try {
			if (!valid(tidy(entry.data))) {
				unavailable(widget, 'service');
			} else {
				if (widget.dataset.design === 'card') {
					paintCard(widget, entry.data, unitsOf(widget), entry);
				} else {
					paintClassic(widget, entry.data, unitsOf(widget));
				}
				widget.removeAttribute('aria-busy');
				widget.classList.add('WIYCW-ready');
			}
		} catch (e) {
			if (window.console && console.error) {
				console.error('Weather Widget Pro', e);
			}
			unavailable(widget, 'service');
		}
		if (holder && !holder.parentNode) {
			widget.appendChild(holder);
		}
	}

	function repaintAll() {
		prune();
		batch(function () {
			readDirections(widgets.filter(function (entry) { return !!entry.data; }).map(function (entry) { return entry.widget; }));
			widgets.forEach(function (entry) {
				if (entry.data) {
					paint(entry);
				}
			});
		});
	}

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
		if (widget.dataset.design === 'card') {
			var name = widget.dataset.title || widget.dataset.city || '';
			content.classList.add('WIYCW-no-sky');
			if (name) {
				var plain = el('div', 'WIYCW-card-plain');
				var head = el('div', 'WIYCW-card-head');
				head.appendChild(el('div', 'WIYCW-card-title', name));
				plain.appendChild(head);
				content.appendChild(plain);
			}
		}
		content.appendChild(box);
		widget.removeAttribute('aria-busy');
		widget.classList.add('WIYCW-ready');
	}

	function start(widget) {
		if (widget.getAttribute('data-wiycw') === '1' || !widget.querySelector('.WIYCW-content')) {
			return;
		}
		widget.setAttribute('data-wiycw', '1');
		prune();
		if (themeOf(widget) === 'blend') {
			groundLater(widget);
			watchGround(widget.ownerDocument);
		}
		var entry = {widget: widget, data: null, hours: null};
		widgets.push(entry);

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
		batch(function () {
			var fresh = [];
			for (var j = 0; j < found.length; j++) {
				if (found[j].getAttribute('data-wiycw') !== '1' && found[j].querySelector('script.WIYCW-data')) {
					fresh.push(found[j]);
				}
			}
			readDirections(fresh);
			for (var i = 0; i < found.length; i++) {
				start(found[i]);
			}
			if (scope !== document && scope.matches && scope.matches('.WIYCW-wrapper[data-f="2"]')) {
				start(scope);
			}
		});
	}

	function ready() {
		init(document);
		var links = document.querySelectorAll('link[rel~="stylesheet"]');
		for (var i = 0; i < links.length; i++) {
			watchSkySheet(links[i]);
		}
		if (document.readyState !== 'complete') {
			window.addEventListener('load', skiesAgain);
		}
		if (window.MutationObserver && document.documentElement) {
			var timer = null;
			new MutationObserver(function (changes) {
				var added = false;
				for (var i = 0; i < changes.length; i++) {
					var nodes = changes[i].addedNodes || [];
					for (var j = 0; j < nodes.length; j++) {
						added = true;
						if (nodes[j].nodeName === 'LINK') {
							watchSkySheet(nodes[j]);
						}
					}
				}
				if (added && !timer) {
					timer = setTimeout(function () { timer = null; init(document); }, 100);
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
