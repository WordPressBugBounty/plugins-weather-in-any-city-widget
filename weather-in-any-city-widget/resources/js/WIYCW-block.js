(function (wp) {
	'use strict';

	if (!wp || !wp.blocks || !wp.element) {
		return;
	}

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useRef = wp.element.useRef;
	var useEffect = wp.element.useEffect;

	function usePainter() {
		var holder = useRef ? useRef(null) : null;
		if (useEffect) {
			useEffect(function () {
				var node = holder && holder.current;
				var view = node && node.ownerDocument && node.ownerDocument.defaultView;
				if (!node || !view || !view.MutationObserver) {
					return undefined;
				}
				var start = function () {
					var painter = view.WIYCW || window.WIYCW;
					if (painter && painter.init) {
						painter.init(node);
					}
				};
				var observer = new view.MutationObserver(start);
				observer.observe(node, {childList: true, subtree: true});
				start();
				return function () { observer.disconnect(); };
			});
		}
		return holder;
	}
	var config = window.WIYCW_block || {};
	var __ = function (text) {
		return (config.i18n && config.i18n[text]) || text;
	};
	function themes() {
		var names = (config.i18n && config.i18n.themes) || {};
		return ['sky', 'dark', 'full', 'flat', 'blend'].map(function (key) {
			return {value: key, label: typeof names[key] === 'string' ? names[key] : key};
		});
	}
	var components = wp.components;
	var editor = wp.blockEditor || wp.editor;
	var ServerSideRender = wp.serverSideRender;

	function CitySearch(props) {
		var state = useState('');
		var query = state[0];
		var setQuery = state[1];
		var found = useState([]);
		var results = found[0];
		var setResults = found[1];
		var timer = useState({id: 0})[0];

		function search(text) {
			setQuery(text);
			clearTimeout(timer.id);
			if (text.length < 2) {
				setResults([]);
				return;
			}
			timer.id = setTimeout(function () {
				fetch((config.searchUrl || 'https://eltiempoen.com/api/weather/wpsearch') + '?q=' + encodeURIComponent(text))
					.then(function (response) { return response.ok ? response.json() : []; })
					.then(function (list) { setResults(Array.isArray(list) ? list.slice(0, 8) : []); })
					.catch(function () { setResults([]); });
			}, 300);
		}

		return el('div', {className: 'WIYCW-block-search'},
			el(components.TextControl, {
				label: __('City', 'weather-in-any-city-widget'),
				value: query,
				placeholder: props.current || '',
				onChange: search,
				autoComplete: 'off'
			}),
			results.map(function (city) {
				return el(components.Button, {
					key: city.id,
					variant: 'tertiary',
					style: {display: 'block', width: '100%', textAlign: 'left', height: 'auto', padding: '6px 8px'},
					onClick: function () {
						props.onPick(city);
						setQuery('');
						setResults([]);
					}
				}, city.text);
			})
		);
	}

	function toggle(props, name, label) {
		return el(components.ToggleControl, {
			key: name,
			label: label,
			checked: props.attributes[name] === 'on',
			onChange: function (on) {
				var change = {};
				change[name] = on ? 'on' : 'off';
				props.setAttributes(change);
			}
		});
	}

	function select(props, name, label, options) {
		return el(components.SelectControl, {
			key: name,
			label: label,
			value: props.attributes[name],
			options: options,
			onChange: function (value) {
				var change = {};
				change[name] = value;
				props.setAttributes(change);
			}
		});
	}

	function edit(props) {
		var a = props.attributes;
		var card = a.design === 'card';
		var blockProps = editor.useBlockProps ? editor.useBlockProps() : {};
		var holder = usePainter();
		if (useEffect) {
			useEffect(function () {
				if (!props.attributes.cityID) {
					props.setAttributes({now_pressure: 'on', now_sunrise: 'off'});
				}
			}, []);
		}

		var general = el(components.PanelBody, {title: __('General', 'weather-in-any-city-widget'), initialOpen: true},
			el(CitySearch, {
				current: a.location,
				onPick: function (city) {
					props.setAttributes({cityID: String(city.id), location: city.text, city: city.city, state: city.state,
						country: city.country, url_en: city.url_en, url_es: city.url_es});
				}
			}),
			a.location ? el('p', {style: {marginTop: 0}}, el('strong', null, a.location)) : null,
			el(components.TextControl, {
				label: __('Title', 'weather-in-any-city-widget') + ' (' + __('optional', 'weather-in-any-city-widget') + ')',
				value: a.title,
				onChange: function (value) { props.setAttributes({title: value}); }
			}),
			select(props, 'design', __('Design', 'weather-in-any-city-widget'), [
				{value: 'card', label: __('Sky card', 'weather-in-any-city-widget')},
				{value: 'classic', label: __('Classic', 'weather-in-any-city-widget')}
			]),
			card ? select(props, 'theme', __('Style', 'weather-in-any-city-widget'), themes()) : null,
			select(props, 'layout', __('Layout', 'weather-in-any-city-widget'), [
				{value: 'vertical', label: __('Vertical', 'weather-in-any-city-widget')},
				{value: 'horizontal', label: __('Horizontal', 'weather-in-any-city-widget')}
			]),
			select(props, 'units', __('Units', 'weather-in-any-city-widget'), [
				{value: 'c', label: __('Celsius', 'weather-in-any-city-widget')},
				{value: 'f', label: __('Fahrenheit', 'weather-in-any-city-widget')}
			]),
			select(props, 'time_format', __('Time format', 'weather-in-any-city-widget'), [
				{value: 'universal', label: __('24 hours', 'weather-in-any-city-widget')},
				{value: 'standard', label: __('12 hours', 'weather-in-any-city-widget')}
			])
		);

		var now = el(components.PanelBody, {title: __('Current conditions', 'weather-in-any-city-widget'), initialOpen: false},
			toggle(props, 'today', __('Show current conditions', 'weather-in-any-city-widget')),
			toggle(props, 'now_icon', __('Icon', 'weather-in-any-city-widget')),
			toggle(props, 'now_temp', __('Temperature', 'weather-in-any-city-widget')),
			card ? toggle(props, 'description', __('Weather in words', 'weather-in-any-city-widget')) : null,
			card ? toggle(props, 'feels_like', __('Feels like', 'weather-in-any-city-widget')) : null,
			toggle(props, 'now_humidity', __('Humidity', 'weather-in-any-city-widget')),
			toggle(props, 'now_wind', __('Wind', 'weather-in-any-city-widget')),
			toggle(props, 'now_pressure', __('Pressure', 'weather-in-any-city-widget')),
			toggle(props, 'now_cloudiness', __('Cloudiness', 'weather-in-any-city-widget')),
			card ? toggle(props, 'now_visibility', __('Visibility', 'weather-in-any-city-widget')) : null,
			toggle(props, 'now_sunrise', __('Sunrise / sunset', 'weather-in-any-city-widget'))
		);

		var forecast = el(components.PanelBody, {title: __('Forecast', 'weather-in-any-city-widget'), initialOpen: false},
			el(components.RangeControl, {
				label: __('Days', 'weather-in-any-city-widget'),
				value: a.days === 'off' ? 0 : (parseInt(a.days, 10) || 0),
				min: 0,
				max: 14,
				onChange: function (value) { props.setAttributes({days: String(value || 0)}); }
			}),
			card ? toggle(props, 'hourly', __('Hourly forecast', 'weather-in-any-city-widget')) : null,
			toggle(props, 'icon', __('Icon', 'weather-in-any-city-widget')),
			toggle(props, 'temp', __('Temperature', 'weather-in-any-city-widget')),
			toggle(props, 'rainChance', __('Rain probability', 'weather-in-any-city-widget')),
			toggle(props, 'rain', __('Rain', 'weather-in-any-city-widget')),
			toggle(props, 'wind', __('Wind', 'weather-in-any-city-widget'))
		);

		var look = el(components.PanelBody, {title: __('Look and feel', 'weather-in-any-city-widget'), initialOpen: false},
			el(components.RangeControl, {
				label: __('Font size', 'weather-in-any-city-widget'),
				value: parseInt(a.fontSize, 10) || 15,
				min: 11,
				max: 22,
				onChange: function (value) { props.setAttributes({fontSize: String(value || 15)}); }
			}),
			card ? toggle(props, 'unit_switch', __('Let visitors switch between °C and °F', 'weather-in-any-city-widget')) : null,
			card ? toggle(props, 'updated', __('Time of the last update', 'weather-in-any-city-widget')) : null,
			card ? toggle(props, 'animation', __('Animated sky', 'weather-in-any-city-widget')) : null,
			card ? null : el(components.BaseControl, {label: __('Font color', 'weather-in-any-city-widget'), id: 'wiycw-text'},
				el(components.ColorPalette, {value: a.textColor, clearable: false, onChange: function (value) { props.setAttributes({textColor: value || '#000000'}); }})),
			card ? null : el(components.BaseControl, {label: __('Background color', 'weather-in-any-city-widget'), id: 'wiycw-bg'},
				el(components.ColorPalette, {value: a.backgroundColor, clearable: false, onChange: function (value) { props.setAttributes({backgroundColor: value || '#ffffff'}); }})),
			card ? null : el(components.BaseControl, {label: __('Border color', 'weather-in-any-city-widget'), id: 'wiycw-border'},
				el(components.ColorPalette, {value: a.borderColor, clearable: false, onChange: function (value) { props.setAttributes({borderColor: value || '#ffffff'}); }})),
			card ? null : select(props, 'weatherIconsColor', __('Weather icons color', 'weather-in-any-city-widget'), [
				{value: 'color', label: __('Color', 'weather-in-any-city-widget')},
				{value: 'light', label: __('Light', 'weather-in-any-city-widget')},
				{value: 'dark', label: __('Dark', 'weather-in-any-city-widget')}
			]),
			card ? null : select(props, 'iconsColor', __('UI icons color', 'weather-in-any-city-widget'), [
				{value: 'light', label: __('Light', 'weather-in-any-city-widget')},
				{value: 'dark', label: __('Dark', 'weather-in-any-city-widget')}
			]),
			card ? null : toggle(props, 'shadow', __('Shadow', 'weather-in-any-city-widget')),
			toggle(props, 'credit_link', __('Show the logo', 'weather-in-any-city-widget'))
		);

		var body = a.cityID
			? el(ServerSideRender, {block: 'wiycw/weather', attributes: a})
			: el(components.Placeholder, {icon: 'cloud', label: __('Weather Widget Pro', 'weather-in-any-city-widget'),
				instructions: __('Search for a city to show its weather.', 'weather-in-any-city-widget')},
				el('div', {style: {width: '100%', maxWidth: '360px'}},
					el(CitySearch, {
						current: '',
						onPick: function (city) {
							props.setAttributes({cityID: String(city.id), location: city.text, city: city.city, state: city.state,
								country: city.country, url_en: city.url_en, url_es: city.url_es});
						}
					})));

		return el('div', blockProps,
			el(editor.InspectorControls, null, general, now, forecast, look),
			el('div', {ref: holder}, body)
		);
	}

	wp.blocks.registerBlockType('wiycw/weather', {
		apiVersion: parseInt(config.apiVersion, 10) === 3 ? 3 : 2,
		title: __('Weather Widget Pro', 'weather-in-any-city-widget'),
		icon: 'cloud',
		category: 'widgets',
		attributes: Object.keys(config.defaults || {}).reduce(function (all, name) {
			all[name] = {type: 'string', 'default': config.defaults[name]};
			return all;
		}, {}),
		edit: edit,
		save: function () { return null; }
	});
})(window.wp);
