<?php

defined( 'ABSPATH' ) or die( 'ABSPATH not defined' );

function WIYCW_block_attributes(){
	$defaults = array(
		'title' => '', 'cityID' => '', 'location' => '', 'city' => '', 'state' => '', 'country' => '', 'url_en' => '', 'url_es' => '',
		'design' => 'card', 'theme' => 'sky', 'units' => 'c', 'time_format' => 'universal', 'days' => '5', 'layout' => 'vertical',
		'today' => 'on', 'now_icon' => 'on', 'now_temp' => 'on', 'now_humidity' => 'on', 'now_wind' => 'on', 'now_pressure' => 'off',
		'now_cloudiness' => 'off', 'now_sunrise' => 'on', 'now_visibility' => 'off',
		'description' => 'on', 'feels_like' => 'on', 'hourly' => 'on', 'unit_switch' => 'on', 'updated' => 'off', 'animation' => 'on',
		'icon' => 'on', 'temp' => 'on', 'rainChance' => 'on', 'rain' => 'off', 'wind' => 'off',
		'backgroundColor' => '#ffffff', 'textColor' => '#000000', 'borderColor' => '#ffffff', 'fontSize' => '15',
		'iconsColor' => 'dark', 'weatherIconsColor' => 'color', 'shadow' => 'off', 'credit_link' => 'on'
	);
	$attributes = array();
	foreach($defaults as $name => $default){
		$attributes[$name] = array('type' => 'string', 'default' => $default);
	}
	return $attributes;
}

function WIYCW_block_words(){
	return array(
		'12 hours' => __('12 hours', 'weather-in-any-city-widget'),
		'24 hours' => __('24 hours', 'weather-in-any-city-widget'),
		'Animated sky' => __('Animated sky', 'weather-in-any-city-widget'),
		'Background color' => __('Background color', 'weather-in-any-city-widget'),
		'Border color' => __('Border color', 'weather-in-any-city-widget'),
		'Celsius' => __('Celsius', 'weather-in-any-city-widget'),
		'City' => __('City', 'weather-in-any-city-widget'),
		'Sky card' => __('Sky card', 'weather-in-any-city-widget'),
		'Style' => __('Style', 'weather-in-any-city-widget'),
		'Classic' => __('Classic', 'weather-in-any-city-widget'),
		'Cloudiness' => __('Cloudiness', 'weather-in-any-city-widget'),
		'Color' => _x('Color', 'icon color', 'weather-in-any-city-widget'),
		'Dark' => _x('Dark', 'icon color', 'weather-in-any-city-widget'),
		'Light' => _x('Light', 'icon color', 'weather-in-any-city-widget'),
		'Current conditions' => __('Current conditions', 'weather-in-any-city-widget'),
		'Days' => __('Days', 'weather-in-any-city-widget'),
		'Design' => __('Design', 'weather-in-any-city-widget'),
		'Fahrenheit' => __('Fahrenheit', 'weather-in-any-city-widget'),
		'Feels like' => __('Feels like', 'weather-in-any-city-widget'),
		'Font color' => __('Font color', 'weather-in-any-city-widget'),
		'Font size' => __('Font size', 'weather-in-any-city-widget'),
		'Forecast' => __('Forecast', 'weather-in-any-city-widget'),
		'General' => __('General', 'weather-in-any-city-widget'),
		'Horizontal' => __('Horizontal', 'weather-in-any-city-widget'),
		'Humidity' => __('Humidity', 'weather-in-any-city-widget'),
		'Icon' => __('Icon', 'weather-in-any-city-widget'),
		'Layout' => __('Layout', 'weather-in-any-city-widget'),
		'Let visitors switch between °C and °F' => __('Let visitors switch between °C and °F', 'weather-in-any-city-widget'),
		'Look and feel' => __('Look and feel', 'weather-in-any-city-widget'),
		'Hourly forecast' => __('Hourly forecast', 'weather-in-any-city-widget'),
		'Pressure' => __('Pressure', 'weather-in-any-city-widget'),
		'Rain' => __('Rain', 'weather-in-any-city-widget'),
		'Rain probability' => __('Rain probability', 'weather-in-any-city-widget'),
		'Search for a city to show its weather.' => __('Search for a city to show its weather.', 'weather-in-any-city-widget'),
		'Shadow' => __('Shadow', 'weather-in-any-city-widget'),
		'Show the logo' => WIYCW_credit_label(),
		'Show current conditions' => __('Show current conditions', 'weather-in-any-city-widget'),
		'Sunrise' => __('Sunrise', 'weather-in-any-city-widget'),
		'Sunset' => __('Sunset', 'weather-in-any-city-widget'),
		'Sunrise / sunset' => __('Sunrise / sunset', 'weather-in-any-city-widget'),
		'Temperature' => __('Temperature', 'weather-in-any-city-widget'),
		'Time format' => __('Time format', 'weather-in-any-city-widget'),
		'Time of the last update' => __('Time of the last update', 'weather-in-any-city-widget'),
		'Title' => __('Title', 'weather-in-any-city-widget'),
		'UI icons color' => __('UI icons color', 'weather-in-any-city-widget'),
		'Units' => __('Units', 'weather-in-any-city-widget'),
		'Vertical' => __('Vertical', 'weather-in-any-city-widget'),
		'Visibility' => __('Visibility', 'weather-in-any-city-widget'),
		'Weather Widget Pro' => __('Weather Widget Pro', 'weather-in-any-city-widget'),
		'Weather icons color' => __('Weather icons color', 'weather-in-any-city-widget'),
		'Weather in words' => __('Weather in words', 'weather-in-any-city-widget'),
		'themes' => WIYCW_themes(),
		'Wind' => __('Wind', 'weather-in-any-city-widget'),
		'optional' => __('optional', 'weather-in-any-city-widget'),
	);
}

function WIYCW_block_render($attributes){
	$instance = array();
	foreach(WIYCW_block_attributes() as $name => $definition){
		$value = isset($attributes[$name]) && is_scalar($attributes[$name]) ? (string) $attributes[$name] : $definition['default'];
		$instance[$name] = sanitize_text_field($value);
	}
	if($instance['days'] === '0'){
		$instance['days'] = 'off';
	}
	$instance = WIYCW_default_values($instance);
	$instance['isWidget'] = false;
	$html = WIYCW_public($instance);
	$wrapper = function_exists('get_block_wrapper_attributes') ? get_block_wrapper_attributes() : '';
	return '<div ' . $wrapper . '>' . $html . '</div>';
}

function WIYCW_block_api_version(){
	global $wp_version;
	return isset($wp_version) && version_compare($wp_version, '6.3', '>=') ? 3 : 2;
}

function WIYCW_register_block(){
	if(!function_exists('register_block_type')){
		return;
	}
	wp_register_script('WIYCW-block', WIYCW_DEF_BASEURL . '/resources/js/WIYCW-block.js',
		array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render'), WIYCW_VERSION, true);
	wp_localize_script('WIYCW-block', 'WIYCW_block', array(
		'searchUrl' => WIYCW_API . '/wpsearch',
		'i18n' => WIYCW_block_words(),
		'apiVersion' => WIYCW_block_api_version(),
		'defaults' => array_map(function ($definition) { return $definition['default']; }, WIYCW_block_attributes())
	));
	register_block_type('wiycw/weather', array(
		'api_version' => WIYCW_block_api_version(),
		'title' => __('Weather Widget Pro', 'weather-in-any-city-widget'),
		'description' => __('Weather Widget Pro provides a complete weather forecast for any location around the world.', 'weather-in-any-city-widget'),
		'category' => 'widgets',
		'icon' => 'cloud',
		'keywords' => array('weather', 'forecast', 'clima', 'tiempo'),
		'supports' => array('html' => false, 'align' => array('left', 'center', 'right', 'wide')),
		'attributes' => WIYCW_block_attributes(),
		'editor_script' => 'WIYCW-block',
		'render_callback' => 'WIYCW_block_render'
	));
}

add_action('init', 'WIYCW_register_block');

function WIYCW_block_editor_assets(){
	if(is_admin()){
		WIYCW_enqueue(true);
	}
}

add_action('enqueue_block_assets', 'WIYCW_block_editor_assets');
