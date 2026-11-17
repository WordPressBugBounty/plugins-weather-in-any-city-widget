<?php

defined( 'ABSPATH' ) or die( 'ABSPATH not defined' );

define ('WIYCW_TTL', 1800);
define ('WIYCW_STALE', DAY_IN_SECONDS);
define ('WIYCW_CALLS_PER_MINUTE', 8);
define ('WIYCW_UNSIGNED_PER_HOUR', 50);

function WIYCW_enqueue($card = false){
	wp_enqueue_style('WIYCW-style', WIYCW_DEF_BASEURL . '/resources/css/WIYCW-style.css', array(), WIYCW_VERSION);
	if($card){
		wp_enqueue_style('WIYCW-sky', WIYCW_DEF_BASEURL . '/resources/css/WIYCW-sky.css', array('WIYCW-style'), WIYCW_VERSION);
	}
	if(!wp_script_is('WIYCW-widget', 'registered')){
		$script = 'WIYCW-widget2.js';
		if(!(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) && file_exists(dirname(WIYCW_FILE) . '/resources/js/WIYCW-widget2.min.js')){
			$script = 'WIYCW-widget2.min.js';
		}
		wp_register_script('WIYCW-widget', WIYCW_DEF_BASEURL . '/resources/js/' . $script, array(), WIYCW_VERSION, true);
		wp_localize_script('WIYCW-widget', 'WIYCW_i18n', WIYCW_i18n());
	}
	wp_enqueue_script('WIYCW-widget');
}

function WIYCW_early_styles(){
	$card = false;
	$found = false;
	if(is_active_widget(false, false, 'wiycw_widget', true)){
		$found = true;
		$instances = get_option('widget_wiycw_widget');
		if(is_array($instances)){
			foreach($instances as $instance){
				if(is_array($instance) && isset($instance['design']) && $instance['design'] == 'card'){
					$card = true;
				}
			}
		}
	}
	if(is_singular()){
		$post = get_post();
		if($post && is_string($post->post_content)){
			if(strpos($post->post_content, '[weather_pro') !== false){
				$found = true;
				$card = $card || strpos($post->post_content, 'design="card"') !== false || strpos($post->post_content, "design='card'") !== false;
			}
			if(strpos($post->post_content, '<!-- wp:wiycw/weather') !== false){
				$found = true;
				$card = true;
			}
		}
	}
	if($found){
		wp_enqueue_style('WIYCW-style', WIYCW_DEF_BASEURL . '/resources/css/WIYCW-style.css', array(), WIYCW_VERSION);
		if($card){
			wp_enqueue_style('WIYCW-sky', WIYCW_DEF_BASEURL . '/resources/css/WIYCW-sky.css', array('WIYCW-style'), WIYCW_VERSION);
		}
	}
}

add_action('wp_enqueue_scripts', 'WIYCW_early_styles');

function WIYCW_stored($id, $force = false){
	$name = 'wiycw2_' . intval($id);
	if($force && wp_using_ext_object_cache()){
		$entry = wp_cache_get($name, 'transient', true);
	}else{
		if($force){
			wp_cache_delete('_transient_' . $name, 'options');
			wp_cache_delete('_transient_timeout_' . $name, 'options');
		}
		$entry = get_transient($name);
	}
	if(!is_array($entry) || empty($entry['body']) || empty($entry['saved'])){
		return null;
	}
	$entry['ttl'] = isset($entry['ttl']) ? intval($entry['ttl']) : WIYCW_TTL;
	$entry['by'] = isset($entry['by']) && $entry['by'] === 'anon' ? 'anon' : 'site';
	return $entry;
}

function WIYCW_keep($id, $body, $saved, $ttl, $stale, $by){
	if($by === 'anon'){
		$ttl = min($ttl, WIYCW_TTL);
		$life = WIYCW_TTL;
	}else{
		$life = max($stale, $ttl + 60);
	}
	set_transient('wiycw2_' . intval($id), array('body' => $body, 'saved' => $saved, 'ttl' => $ttl, 'by' => $by), $life);
}

function WIYCW_valid($data){
	return is_object($data) && empty($data->error) && isset($data->now) && is_object($data->now)
		&& isset($data->forecast) && is_array($data->forecast) && count($data->forecast) > 0;
}

function WIYCW_key($id){
	return substr(wp_hash('wiycw-city-' . intval($id)), 0, 16);
}

function WIYCW_lock($id){
	$id = intval($id);
	if(WIYCW_cache_works()){
		return (bool) wp_cache_add('wiycw2_lock_' . $id, 1, 'transient', 15);
	}
	global $wpdb;
	$name = 'wiycw2_lock_' . $id;
	$now = time();
	$until = $now + 15;
	$got = (bool) $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
		$name, (string) $until));
	if(!$got){
		$got = (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND CAST(option_value AS UNSIGNED) < %d",
			(string) $until, $name, $now));
	}
	if($got){
		$held = &WIYCW_held();
		$held[$id] = $until;
	}
	return $got;
}

function WIYCW_unlock($id){
	$id = intval($id);
	if(WIYCW_cache_works()){
		wp_cache_delete('wiycw2_lock_' . $id, 'transient');
		return;
	}
	$held = &WIYCW_held();
	if(!isset($held[$id])){
		return;
	}
	global $wpdb;
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", 'wiycw2_lock_' . $id, (string) $held[$id]));
	unset($held[$id]);
}

function &WIYCW_held(){
	static $held = array();
	return $held;
}

function WIYCW_sweep_locks(){
	global $wpdb;
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d",
		$wpdb->esc_like('wiycw2_lock_') . '%', time() - MINUTE_IN_SECONDS));
}

add_action('delete_expired_transients', 'WIYCW_sweep_locks');

function WIYCW_cache_works(){
	static $works = null;
	if($works === null){
		$works = false;
		if(wp_using_ext_object_cache()){
			$key = 'wiycw2_probe_' . wp_rand();
			$first = wp_cache_add($key, 1, 'transient', 30);
			$second = wp_cache_add($key, 1, 'transient', 30);
			wp_cache_delete($key, 'transient');
			$works = $first && !$second;
		}
	}
	return $works;
}

function WIYCW_take($name, $limit, $life){
	if(WIYCW_cache_works()){
		wp_cache_add($name, 0, 'transient', $life);
		$calls = wp_cache_incr($name, 1, 'transient');
		return $calls !== false && $calls <= $limit;
	}
	global $wpdb;
	$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
		'_transient_timeout_' . $name, (string) (time() + $life)));
	$wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, '0', 'no')",
		'_transient_' . $name));
	return (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s AND option_value < %d",
		'_transient_' . $name, $limit));
}

function WIYCW_give_back($name){
	if(WIYCW_cache_works()){
		wp_cache_decr($name, 1, 'transient');
		return;
	}
	global $wpdb;
	$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = option_value - 1 WHERE option_name = %s AND option_value > 0",
		'_transient_' . $name));
}

function WIYCW_taken($name){
	if(WIYCW_cache_works()){
		return intval(wp_cache_get($name, 'transient', true));
	}
	global $wpdb;
	return intval($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", '_transient_' . $name)));
}

function WIYCW_minute($now){
	return 'wiycw2_calls_' . floor($now / MINUTE_IN_SECONDS);
}

function WIYCW_take_call($now){
	return WIYCW_take(WIYCW_minute($now), WIYCW_CALLS_PER_MINUTE, 2 * MINUTE_IN_SECONDS);
}

function WIYCW_hour($now){
	return 'wiycw_unsigned_' . floor($now / HOUR_IN_SECONDS);
}

function WIYCW_clamp($value, $min, $max, $default){
	$value = is_numeric($value) ? intval($value) : $default;
	return max($min, min($max, $value));
}

function WIYCW_weather($id, $signed = true, $new = false){
	$id = intval($id);
	$now = time();
	$entry = WIYCW_stored($id);
	if($entry && $now - $entry['saved'] < $entry['ttl']){
		$data = json_decode($entry['body']);
		if(WIYCW_valid($data)){
			if($signed && $entry['by'] === 'anon'){
				WIYCW_keep($id, $entry['body'], intval($entry['saved']), $entry['ttl'], WIYCW_STALE, 'site');
			}
			return array('data' => $data, 'saved' => intval($entry['saved']), 'stale' => false);
		}
	}

	$last = function ($message, $kind = 'service') use ($entry, $id, $now) {
		if($entry){
			$data = json_decode($entry['body']);
			if(WIYCW_valid($data)){
				return array('data' => $data, 'saved' => intval($entry['saved']), 'stale' => true);
			}
		}
		$before = get_transient(substr('wiycw_' . $id, 0, 44));
		$data = is_string($before) && $before !== '' ? json_decode($before) : null;
		if(WIYCW_valid($data)){
			return array('data' => $data, 'saved' => $now - WIYCW_TTL, 'stale' => true);
		}
		return array('error' => $message, 'kind' => $kind);
	};

	if(!$signed && !$entry && !$new){
		return $last('Not signed', 'site');
	}
	if(!$signed && WIYCW_taken(WIYCW_hour($now)) >= WIYCW_UNSIGNED_PER_HOUR){
		return $last('Too many requests', 'site');
	}

	$failed = get_transient('wiycw2_fail_' . $id);
	if(is_array($failed) && isset($failed['until']) && $now < $failed['until']){
		return $last(isset($failed['message']) ? $failed['message'] : 'Service error', isset($failed['kind']) ? $failed['kind'] : 'service');
	}
	if(!WIYCW_lock($id)){
		return $last('Busy', 'limit');
	}
	$again = WIYCW_stored($id, true);
	if($again && $now - $again['saved'] < $again['ttl']){
		$data = json_decode($again['body']);
		if(WIYCW_valid($data)){
			WIYCW_unlock($id);
			return array('data' => $data, 'saved' => intval($again['saved']), 'stale' => false);
		}
	}
	if(!WIYCW_take_call($now)){
		WIYCW_unlock($id);
		return $last('Too many requests', 'limit');
	}
	if(!$signed && !WIYCW_take(WIYCW_hour($now), WIYCW_UNSIGNED_PER_HOUR, 2 * HOUR_IN_SECONDS)){
		WIYCW_give_back(WIYCW_minute($now));
		WIYCW_unlock($id);
		return $last('Too many requests', 'site');
	}
	$known = $again ? $again : $entry;
	$by = ($signed || ($known && $known['by'] === 'site')) ? 'site' : 'anon';

	$url = add_query_arg(array(
		'id' => $id,
		's' => rawurlencode(get_site_url()),
		'v' => WIYCW_VERSION,
		'c' => 'true',
		'f' => '2'
	), WIYCW_API . '/widget');
	$request = wp_remote_get($url, array('timeout' => 6, 'limit_response_size' => 262144));
	$code = is_wp_error($request) ? 0 : intval(wp_remote_retrieve_response_code($request));
	$body = is_wp_error($request) ? '' : wp_remote_retrieve_body($request);
	$data = $code == 200 ? json_decode($body) : null;
	WIYCW_unlock($id);

	if(WIYCW_valid($data)){
		$cache = isset($data->cache) && is_object($data->cache) ? $data->cache : new stdClass();
		$ttl = WIYCW_clamp(isset($cache->ttl) ? $cache->ttl : null, 900, 6 * HOUR_IN_SECONDS, WIYCW_TTL);
		$stale = WIYCW_clamp(isset($cache->stale) ? $cache->stale : null, HOUR_IN_SECONDS, 2 * DAY_IN_SECONDS, WIYCW_STALE);
		WIYCW_keep($id, $body, $now, $ttl, $stale, $by);
		delete_transient('wiycw2_fail_' . $id);
		return array('data' => $data, 'saved' => $now, 'stale' => false);
	}

	$said = is_wp_error($request) ? null : json_decode($body);
	$own = is_object($said) && !empty($said->error);
	$final = $own && ($code == 403 || $code == 404 || $code == 400);
	$kind = 'service';
	if(is_wp_error($request)){
		$message = $request->get_error_message();
		$kind = 'network';
	}else if($code != 200){
		$message = 'Response code: ' . $code;
		$kind = $code == 429 ? 'limit' : ($final ? ($code == 403 ? 'blocked' : 'city') : 'service');
	}else{
		$message = 'Invalid response';
	}
	$count = is_array($failed) && isset($failed['count']) ? intval($failed['count']) : 0;
	if($code != 429){
		$count++;
	}
	$waits = array(60, 120, 300, 900);
	$wait = $waits[max(1, min($count, count($waits))) - 1];
	if($final){
		$wait = 6 * HOUR_IN_SECONDS;
	}else if($code == 429){
		$retry = intval(wp_remote_retrieve_header($request, 'retry-after'));
		$wait = $retry > 0 ? min($retry, 900) : 60;
	}
	set_transient('wiycw2_fail_' . $id, array('count' => $count, 'until' => $now + $wait, 'message' => $message, 'kind' => $kind), max($wait, HOUR_IN_SECONDS));

	return $last($message, $kind);
}

function WIYCW_send_weather($id, $signed = true){
	$result = WIYCW_weather($id, $signed);
	if(isset($result['error'])){
		wp_send_json(array('error' => true, 'message' => $result['error'], 'kind' => isset($result['kind']) ? $result['kind'] : 'service'));
	}
	$data = $result['data'];
	$data->wiycw = array('saved' => $result['saved'], 'stale' => $result['stale']);
	if(!headers_sent()){
		header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		if(!$result['stale']){
			header_remove('Pragma');
			header_remove('Expires');
			header('Cache-Control: public, max-age=300');
		}
	}
	echo wp_json_encode($data);
	exit;
}

function WIYCW_carried($id){
	$entry = WIYCW_stored($id);
	if(!$entry || time() - $entry['saved'] >= $entry['ttl']){
		return '';
	}
	$data = json_decode($entry['body']);
	if(!WIYCW_valid($data)){
		return '';
	}
	$json = wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
	if(!$json){
		return '';
	}
	return "<script type='application/json' class='WIYCW-data' data-saved='" . intval($entry['saved']) . "'>" . $json . "</script>";
}
