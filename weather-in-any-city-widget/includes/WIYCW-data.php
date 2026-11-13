<?php
/**
 * Weather Widget Pro: the data of a city for the script of 2.0.0 (f=2) and the files of the page.
 *
 * The answer of the weather service is kept in a transient (wiycw2_{id}) with the moment it was saved: it is fresh for
 * `ttl` seconds (the service says how long; 30 minutes by default) and it stays for a day, so when the service fails
 * the last good answer is served instead of an empty widget. While a request is on its way nobody else asks (a lock),
 * and after a failure the next try waits longer each time.
 *
 * The widgets of the versions up to 1.1.42 (a page kept in a cache) keep using wiycw_{id}, in the main file.
 */

defined( 'ABSPATH' ) or die( 'ABSPATH not defined' );

define ('WIYCW_TTL', 1800);
define ('WIYCW_STALE', DAY_IN_SECONDS);
// requests to the weather service a site may make in a minute, whatever asks for them (the service allows ten)
define ('WIYCW_CALLS_PER_MINUTE', 8);
// and the requests without signature (the pages of before 2.0.0 kept in a cache), in an hour
define ('WIYCW_UNSIGNED_PER_HOUR', 50);

function WIYCW_enqueue($card = false){
	wp_enqueue_style('WIYCW-style', WIYCW_DEF_BASEURL . '/resources/css/WIYCW-style.css', array(), WIYCW_VERSION);
	if($card){
		wp_enqueue_style('WIYCW-sky', WIYCW_DEF_BASEURL . '/resources/css/WIYCW-sky.css', array('WIYCW-style'), WIYCW_VERSION);
	}
	if(!wp_script_is('WIYCW-widget', 'registered')){
		// in the footer: the script looks for the widgets of the page, which must be there
		wp_register_script('WIYCW-widget', WIYCW_DEF_BASEURL . '/resources/js/WIYCW-widget2.js', array(), WIYCW_VERSION, true);
		wp_localize_script('WIYCW-widget', 'WIYCW_i18n', WIYCW_i18n());
	}
	wp_enqueue_script('WIYCW-widget');
}

// The styles in the <head> when the page is known to have a widget: enqueued while the widget is printed they came
// in the footer and the widget was first seen without them
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

// What is kept of a city: array('body' => the JSON of the service, 'saved' => when, 'ttl' => seconds it is fresh) or null
function WIYCW_stored($id){
	$entry = get_transient('wiycw2_' . intval($id));
	if(!is_array($entry) || empty($entry['body']) || empty($entry['saved'])){
		return null;
	}
	$entry['ttl'] = isset($entry['ttl']) ? intval($entry['ttl']) : WIYCW_TTL;
	return $entry;
}

// The answer of the service as the widgets need it: an object with `now` and a list of days
function WIYCW_valid($data){
	return is_object($data) && empty($data->error) && isset($data->now) && is_object($data->now)
		&& isset($data->forecast) && is_array($data->forecast) && count($data->forecast) > 0;
}

// The signature of a city: the pages of this site carry it (data-k), so only the cities the site shows can make it ask
// the weather service and keep an answer. It does not change between visitors: it is fine in a page kept in a cache
function WIYCW_key($id){
	return substr(wp_hash('wiycw-city-' . intval($id)), 0, 16);
}

// The requests of the script of before 2.0.0 are not signed: they may ask for a few new cities an hour
function WIYCW_unsigned_allowed(){
	$hour = 'wiycw_unsigned_' . floor(time() / HOUR_IN_SECONDS);
	$calls = intval(get_transient($hour));
	if($calls >= WIYCW_UNSIGNED_PER_HOUR){
		return false;
	}
	set_transient($hour, $calls + 1, 2 * HOUR_IN_SECONDS);
	return true;
}

function WIYCW_clamp($value, $min, $max, $default){
	$value = is_numeric($value) ? intval($value) : $default;
	return max($min, min($max, $value));
}

/**
 * The data of a city: array('data' => object, 'saved' => int, 'stale' => bool) or array('error' => string).
 * Fresh from the transient; if not, asked to the service; if that cannot be done or fails, the last good answer.
 */
function WIYCW_weather($id, $signed = true){
	$id = intval($id);
	$now = time();
	$entry = WIYCW_stored($id);
	if($entry && $now - $entry['saved'] < $entry['ttl']){
		$data = json_decode($entry['body']);
		if(WIYCW_valid($data)){
			return array('data' => $data, 'saved' => intval($entry['saved']), 'stale' => false);
		}
	}

	// $kind says why there is no weather, for the message of the widget: blocked (the service turned this site away),
	// limit (too many requests, of the service or of this site), city (it does not exist), network (the service could
	// not be reached) or service (any other failure)
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

	// a request without the signature of the site gets what is kept, and never makes the site ask or keep anything
	if(!$signed){
		return $last('Not signed', 'site');
	}

	// after a failure, wait (longer each time); while another request asks, do not ask too
	$failed = get_transient('wiycw2_fail_' . $id);
	if(is_array($failed) && isset($failed['until']) && $now < $failed['until']){
		return $last(isset($failed['message']) ? $failed['message'] : 'Service error', isset($failed['kind']) ? $failed['kind'] : 'service');
	}
	if(get_transient('wiycw2_lock_' . $id)){
		return $last('Busy', 'limit');
	}
	// and not more than a few a minute for the whole site, whatever city is asked for
	$minute = 'wiycw2_calls_' . floor($now / 60);
	$calls = intval(get_transient($minute));
	if($calls >= WIYCW_CALLS_PER_MINUTE){
		return $last('Too many requests', 'limit');
	}
	set_transient($minute, $calls + 1, 2 * MINUTE_IN_SECONDS);
	set_transient('wiycw2_lock_' . $id, 1, 15);

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
	delete_transient('wiycw2_lock_' . $id);

	if(WIYCW_valid($data)){
		$cache = isset($data->cache) && is_object($data->cache) ? $data->cache : new stdClass();
		$ttl = WIYCW_clamp(isset($cache->ttl) ? $cache->ttl : null, 900, 6 * HOUR_IN_SECONDS, WIYCW_TTL);
		$stale = WIYCW_clamp(isset($cache->stale) ? $cache->stale : null, HOUR_IN_SECONDS, 2 * DAY_IN_SECONDS, WIYCW_STALE);
		set_transient('wiycw2_' . $id, array('body' => $body, 'saved' => $now, 'ttl' => $ttl), max($stale, $ttl + 60));
		delete_transient('wiycw2_fail_' . $id);
		return array('data' => $data, 'saved' => $now, 'stale' => false);
	}

	// the service answers its refusals with its own JSON ({"error": true, …}): a 403 or a 404 of anything in between
	// (a firewall, a proxy) is a failure like any other, not «this site is blocked» or «this city does not exist»
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
	// 1, 2, 5 and 15 minutes; a city that does not exist or a site the service turns away, six hours; what the service
	// asks for, if it says so
	$count = is_array($failed) && isset($failed['count']) ? intval($failed['count']) : 0;
	// «too many requests» is not a failure of the service: it does not make the next waits longer
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

// The answer to the script of 2.0.0 (admin-ajax, f=2)
function WIYCW_send_weather($id, $signed = true){
	$result = WIYCW_weather($id, $signed);
	if(isset($result['error'])){
		wp_send_json(array('error' => true, 'message' => $result['error'], 'kind' => isset($result['kind']) ? $result['kind'] : 'service'));
	}
	$data = $result['data'];
	$data->wiycw = array('saved' => $result['saved'], 'stale' => $result['stale']);
	// the same for every visitor: a browser may keep it for a few minutes. Written here, not with wp_send_json(): it ends
	// with wp_die(), which says «never keep this» again
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

/**
 * The data the site already has, written in the page for the script: with it the widget needs no request. Only what
 * is fresh: a page kept in a cache carries the moment it was saved, and the script asks again when it is old.
 */
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
