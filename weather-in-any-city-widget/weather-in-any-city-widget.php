<?php
/**
 * Plugin Name: Weather Widget Pro
 * Plugin URI: https://weatherin.org
 * Description: Weather Widget Pro provides a complete weather forecast for any location around the world.
 * Version: 2.0.4
 * Author: El tiempo
 * Author URI: https://eltiempoen.com
 * Text Domain: weather-in-any-city-widget
 * Domain Path: /languages/
 * License: GPLv2 or later
**/



defined( 'ABSPATH' ) or die( 'ABSPATH not defined' );

define ('WIYCW_VERSION', '2.0.4');
// The weather service, on the standard HTTPS port (the old eltiempoen.com:9090 is not reachable any more)
define ('WIYCW_API', 'https://eltiempoen.com/api/weather');
define ('WIYCW_DEF_PLUGIN', 'weather-in-any-city-widget');
define ('WIYCW_DEF_BASEURL', plugins_url('', __FILE__));
define ('WIYCW_FILE', __FILE__);

require_once dirname(__FILE__) . '/includes/WIYCW-data.php';
require_once dirname(__FILE__) . '/includes/WIYCW-block.php';
require_once dirname(__FILE__) . '/includes/WIYCW-admin.php';


add_action('init', 'WIYCW_textdomain');


// The texts of the plugin first: WordPress keeps the first translation it loads of each text, and the language pack of
// translate.wordpress.org (wp-content/languages/plugins) can be one of an old version, without the texts of now. Loaded
// after, the pack fills in what the plugin does not bring (another language)
function WIYCW_textdomain() {
	$locale = function_exists('determine_locale') ? determine_locale() : get_locale();
	load_textdomain('weather-in-any-city-widget', dirname(__FILE__) . '/languages/weather-in-any-city-widget-' . $locale . '.mo');
    load_plugin_textdomain('weather-in-any-city-widget', FALSE, plugin_basename(dirname(__FILE__)) . '/languages' );
}

// Two Spanish translations for every variety of Spanish: es_ES (Spain) and es (the Americas: «clima», «nublado»).
// WordPress asks for the file of the exact locale (es_MX, es_AR…) and, without it, shows English. Only the files of
// this plugin are redirected: a translation of translate.wordpress.org for that locale (wp-content/languages) still wins
add_filter('load_textdomain_mofile', 'WIYCW_spanish_file', 10, 2);
function WIYCW_spanish_file($mofile, $domain){
	if($domain !== 'weather-in-any-city-widget' || file_exists($mofile)){
		return $mofile;
	}
	$dir = wp_normalize_path(dirname(__FILE__) . '/languages/');
	$path = wp_normalize_path($mofile);
	if(strpos($path, $dir) !== 0 || !preg_match('~-es_([A-Z]{2})\.mo$~', $path, $match)){
		return $mofile;
	}
	$file = $dir . 'weather-in-any-city-widget-' . ($match[1] === 'ES' ? 'es_ES' : 'es') . '.mo';
	return file_exists($file) ? $file : $mofile;
}

function WIYCW_default_values($instance){
	$instance['title'] = !empty($instance['title']) ? $instance['title'] : "";
	$instance['cityID'] = !empty($instance['cityID']) ? $instance['cityID'] : 86168;
	$instance['location'] = !empty($instance['location']) ? $instance['location'] : "New York City, New York (United States)";
	$instance['city'] = !empty($instance['city']) ? $instance['city'] : "New York City";
	$instance['state'] = !empty($instance['state']) ? $instance['state'] : "New York";
	$instance['country'] = !empty($instance['country']) ? $instance['country'] : "United States";
	$instance['units'] = !empty($instance['units']) ? $instance['units'] : "c";
	$instance['today'] = !empty($instance['today']) ? $instance['today'] : "on";
	$instance['now_icon'] = !empty($instance['now_icon']) ? $instance['now_icon'] : "on";
	$instance['now_temp'] = !empty($instance['now_temp']) ? $instance['now_temp'] : "on";
	$instance['now_humidity'] = !empty($instance['now_humidity']) ? $instance['now_humidity'] : "on";
	$instance['now_pressure'] = !empty($instance['now_pressure']) ? $instance['now_pressure'] : "on";
	$instance['now_cloudiness'] = !empty($instance['now_cloudiness']) ? $instance['now_cloudiness'] : "on";
	$instance['now_wind'] = !empty($instance['now_wind']) ? $instance['now_wind'] : "on";
	$instance['now_sunrise'] = !empty($instance['now_sunrise']) ? $instance['now_sunrise'] : "off";
	$instance['days'] = !empty($instance['days']) ? $instance['days'] : 4;
	$instance['layout'] = !empty($instance['layout']) ? $instance['layout'] : "horizontal";
	$instance['wind'] = !empty($instance['wind']) ? $instance['wind'] : "off";
	$instance['rain'] = !empty($instance['rain']) ? $instance['rain'] : "off";
	$instance['rainChance'] = !empty($instance['rainChance']) ? $instance['rainChance'] : "on";
	$instance['icon'] = !empty($instance['icon']) ? $instance['icon'] : "on";
	$instance['temp'] = !empty($instance['temp']) ? $instance['temp'] : "on";
	$instance['backgroundColor'] = !empty($instance['backgroundColor']) ? $instance['backgroundColor'] : "#ffffff";
	$instance['fontSize'] = !empty($instance['fontSize']) ? $instance['fontSize'] : "15";
	$instance['borderColor'] = !empty($instance['borderColor']) ? $instance['borderColor'] : "#ffffff";
	$instance['textColor'] = !empty($instance['textColor']) ? $instance['textColor'] : "#000000";
	$instance['iconsColor'] = !empty($instance['iconsColor']) ? $instance['iconsColor'] : "dark";
	$instance['weatherIconsColor'] = !empty($instance['weatherIconsColor']) ? $instance['weatherIconsColor'] : "color";
	$instance['shadow'] = !empty($instance['shadow']) ? $instance['shadow'] : "off";
	$instance['credits'] = !empty($instance['credits']) ? $instance['credits'] : "on";
	$instance['url_en'] = !empty($instance['url_en']) ? $instance['url_en'] : "";
	$instance['url_es'] = !empty($instance['url_es']) ? $instance['url_es'] : "";
	$instance['time_format'] = !empty($instance['time_format']) ? $instance['time_format'] : "universal";
	// since 2.0.0. design: «classic» (as always) or «card» (the sky card). The rest only count in the card
	$instance['design'] = !empty($instance['design']) && $instance['design'] == 'card' ? 'card' : 'classic';
	$instance['description'] = !empty($instance['description']) ? $instance['description'] : "on";
	$instance['feels_like'] = !empty($instance['feels_like']) ? $instance['feels_like'] : "on";
	$instance['hourly'] = !empty($instance['hourly']) ? $instance['hourly'] : "off";
	$instance['unit_switch'] = !empty($instance['unit_switch']) ? $instance['unit_switch'] : "off";
	$instance['updated'] = !empty($instance['updated']) ? $instance['updated'] : "off";
	$instance['animation'] = !empty($instance['animation']) ? $instance['animation'] : "on";
	// since 2.0.4: the small link to the weather service under the widget (on unless it is switched off)
	$instance['credit_link'] = !empty($instance['credit_link']) && $instance['credit_link'] == 'off' ? 'off' : 'on';
	$instance['now_visibility'] = !empty($instance['now_visibility']) ? $instance['now_visibility'] : "off";
	$instance['url'] = admin_url('admin-ajax.php');
	$instance['action'] = "WIYCW_get_weather";
	return $instance;
}

function WIYCW_i18n(){
	$i18n = array('week_days' => 
				array(
					__('mon.', 'weather-in-any-city-widget'), 
					__('tue.', 'weather-in-any-city-widget'),
					__('wed.', 'weather-in-any-city-widget'),
					__('thu.', 'weather-in-any-city-widget'),
					__('fri.', 'weather-in-any-city-widget'),
					__('sat.', 'weather-in-any-city-widget'),
					__('sun.', 'weather-in-any-city-widget')),
				'today' => __('today', 'weather-in-any-city-widget'),
				'tomorrow' => __('tmrw.', 'weather-in-any-city-widget'),
				// since 2.0.0
				'feels' => __('Feels like', 'weather-in-any-city-widget'),
				'humidity' => __('Humidity', 'weather-in-any-city-widget'),
				'wind' => __('Wind', 'weather-in-any-city-widget'),
				'pressure' => __('Pressure', 'weather-in-any-city-widget'),
				'cloudiness' => __('Cloudiness', 'weather-in-any-city-widget'),
				'visibility' => __('Visibility', 'weather-in-any-city-widget'),
				'sunrise' => __('Sunrise', 'weather-in-any-city-widget'),
				'sunset' => __('Sunset', 'weather-in-any-city-widget'),
				'rain_chance' => __('Rain probability', 'weather-in-any-city-widget'),
				'forecast' => __('Forecast', 'weather-in-any-city-widget'),
				'hourly' => __('Hourly forecast', 'weather-in-any-city-widget'),
				'units' => __('Units', 'weather-in-any-city-widget'),
				'alt_now' => __('Current conditions', 'weather-in-any-city-widget'),
				/* translators: %s: a time, like 14:30 */
				'updated' => __('Updated at %s', 'weather-in-any-city-widget'),
				'unavailable' => __('Weather data is temporarily unavailable.', 'weather-in-any-city-widget'),
				'err_blocked' => __('The weather service is not available for this site.', 'weather-in-any-city-widget'),
				'admin_blocked' => __('The weather service has blocked this site. Contact us at weatherin.org/contact to restore access.', 'weather-in-any-city-widget'),
				'admin_limit' => __('Too many requests to the weather service. The widget will try again in a few minutes.', 'weather-in-any-city-widget'),
				'admin_city' => __('The city of this widget was not found. Choose it again in the widget settings.', 'weather-in-any-city-widget'),
				'admin_network' => __('This site could not reach the weather service (eltiempoen.com). Check that your server allows outgoing connections.', 'weather-in-any-city-widget'),
				'admin_service' => __('The weather service did not respond. The widget will try again in a few minutes.', 'weather-in-any-city-widget'),
				'admin_site' => __('This site did not respond to the request of the widget (admin-ajax.php).', 'weather-in-any-city-widget'),
				'admin_only' => __('(Only site editors can see this.)', 'weather-in-any-city-widget'),
				'conditions' => WIYCW_conditions()
			);
	return $i18n;
}

// The words of every condition code of the weather service (the card design and the texts of the images)
function WIYCW_conditions(){
	$storm_rain = __('Thunderstorm with rain', 'weather-in-any-city-widget');
	$storm = __('Thunderstorm', 'weather-in-any-city-widget');
	$drizzle = __('Drizzle', 'weather-in-any-city-widget');
	$light_rain = __('Light rain', 'weather-in-any-city-widget');
	$rain = __('Rain', 'weather-in-any-city-widget');
	$heavy_rain = __('Heavy rain', 'weather-in-any-city-widget');
	$showers = __('Showers', 'weather-in-any-city-widget');
	$sleet = __('Sleet', 'weather-in-any-city-widget');
	$dust = __('Dust', 'weather-in-any-city-widget');
	return array(
		200 => $storm_rain, 201 => $storm_rain, 202 => $storm_rain, 230 => $storm_rain, 231 => $storm_rain, 232 => $storm_rain,
		210 => $storm, 211 => $storm, 212 => $storm, 221 => $storm,
		300 => $drizzle, 301 => $drizzle, 302 => $drizzle, 321 => $drizzle,
		310 => $light_rain, 311 => $rain, 312 => $heavy_rain, 313 => $showers, 314 => $heavy_rain,
		500 => $light_rain, 501 => $rain, 502 => $heavy_rain, 503 => $heavy_rain, 504 => $heavy_rain,
		511 => __('Freezing rain', 'weather-in-any-city-widget'),
		520 => __('Light showers', 'weather-in-any-city-widget'), 521 => $showers,
		522 => __('Heavy showers', 'weather-in-any-city-widget'), 531 => $showers,
		600 => __('Light snow', 'weather-in-any-city-widget'), 601 => __('Snow', 'weather-in-any-city-widget'),
		602 => __('Heavy snow', 'weather-in-any-city-widget'),
		611 => $sleet, 612 => $sleet, 613 => $sleet,
		615 => __('Rain and snow', 'weather-in-any-city-widget'), 616 => __('Rain and snow', 'weather-in-any-city-widget'),
		620 => __('Light snow showers', 'weather-in-any-city-widget'), 621 => __('Snow showers', 'weather-in-any-city-widget'),
		622 => __('Heavy snow showers', 'weather-in-any-city-widget'),
		701 => __('Mist', 'weather-in-any-city-widget'), 711 => __('Smoke', 'weather-in-any-city-widget'),
		721 => __('Haze', 'weather-in-any-city-widget'), 731 => $dust, 741 => __('Fog', 'weather-in-any-city-widget'),
		751 => $dust, 761 => $dust, 762 => $dust,
		771 => __('Squalls', 'weather-in-any-city-widget'), 781 => __('Tornado', 'weather-in-any-city-widget'),
		800 => __('Clear sky', 'weather-in-any-city-widget'), 801 => __('Mostly clear', 'weather-in-any-city-widget'),
		802 => __('Partly cloudy', 'weather-in-any-city-widget'), 803 => __('Mostly cloudy', 'weather-in-any-city-widget'),
		804 => __('Overcast', 'weather-in-any-city-widget')
	);
}

// The settings a box of the form turns on and off (WIYCW_NEW_BOXES: those of 2.0.0)
function WIYCW_boxes(){
	return array('today', 'credits', 'now_icon', 'now_temp', 'now_humidity', 'now_wind', 'now_sunrise', 'now_pressure',
		'now_cloudiness', 'wind', 'rain', 'rainChance', 'icon', 'temp', 'shadow');
}

function WIYCW_new_boxes(){
	return array('description', 'feels_like', 'hourly', 'unit_switch', 'updated', 'animation', 'now_visibility', 'credit_link');
}

class WIYCW_widget extends WP_Widget
{
    public function __construct()
    {
        $widget_options = array('classname' => 'WIYCW_widget', 'description' => __('Weather Widget Pro provides a complete weather forecast for any location around the world.', 'weather-in-any-city-widget'));
        parent::__construct('WIYCW_widget', __('Weather Widget Pro', 'weather-in-any-city-widget'), $widget_options);
    }

   
   	

    public function widget($args, $instance)
    {

    	$instance = WIYCW_default_values($instance);
    	$instance['isWidget'] = true;
    	echo WIYCW_public($instance);   
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $value = function ($key) use ($new_instance) {
            return isset($new_instance[$key]) ? $new_instance[$key] : '';
        };

        foreach (array('title', 'cityID', 'location', 'city', 'state', 'country', 'url_en', 'url_es', 'units', 'days', 'layout',
            'iconsColor', 'weatherIconsColor', 'fontSize', 'time_format') as $key) {
            $instance[$key] = sanitize_text_field(strip_tags($value($key)));
        }
        foreach (array('backgroundColor', 'textColor', 'borderColor') as $key) {
            $instance[$key] = sanitize_hex_color(strip_tags($value($key)));
        }
        foreach (WIYCW_boxes() as $key) {
            $instance[$key] = $value($key) == "on" ? "on" : "off";
        }

        // the settings of 2.0.0, only when what was sent has them: the form of now (WIYCW_v2) or an instance already made
        // (the widgets screen of the block editor sends it back with `design`). A form of before leaves them as they were
        if ($value('WIYCW_v2') == '1' || isset($new_instance['design'])) {
            $instance['design'] = $value('design') == 'card' ? 'card' : 'classic';
            foreach (WIYCW_new_boxes() as $key) {
                $instance[$key] = $value($key) == "on" ? "on" : "off";
            }
        }

        return $instance;
    }



     public function form($instance)
    {

    	$is_new = empty($instance) || empty($instance['cityID']);
    	$instance = WIYCW_default_values($instance);
    	if ($is_new) {
    		$instance['design'] = 'card';
    		// as in the block: humidity, wind and pressure make one line of the card (saved widgets keep theirs)
    		$instance['now_cloudiness'] = 'off';
    	}
    	$title = $instance['title'];
    	$cityID = $instance['cityID'];
    	$location = $instance['location'];
    	$city = $instance['city'];
    	$state = $instance['state'];
    	$country = $instance['country'];
    	$units = $instance['units'];
    	$today = $instance['today'];
    	$now_icon = $instance['now_icon'];
		$now_temp = $instance['now_temp'];
    	$now_humidity = $instance['now_humidity'];
    	$now_pressure = $instance['now_pressure'];
    	$now_cloudiness = $instance['now_cloudiness'];
    	$now_wind = $instance['now_wind'];
    	$now_sunrise = $instance['now_sunrise'];
    	$days = $instance['days'];
    	$layout = $instance['layout'];
    	$wind = $instance['wind'];
    	$rain = $instance['rain'];
    	$rainChance = $instance['rainChance'];
    	$icon = $instance['icon'];
    	$temp = $instance['temp'];
    	$backgroundColor = $instance['backgroundColor'];
    	$borderColor = $instance['borderColor'];
    	$textColor = $instance['textColor'];
    	$iconsColor = $instance['iconsColor'];
    	$weatherIconsColor = $instance['weatherIconsColor'];
    	$fontSize = $instance['fontSize'];
    	$shadow = $instance['shadow'];
    	$credits = $instance['credits'];
    	$url_es = $instance['url_es'];
    	$url_en = $instance['url_en'];
    	$time_format = $instance['time_format'];
    	$widgetID =  $this->id;


    	?>
    	
    	<div class="wrap">
	
			<div class="WIYCW-form WIYCW-admin" id="<?php echo esc_attr($widgetID); ?>"
			  data-search="<?php echo $this->get_field_id('search'); ?>"
			  data-location="<?php echo $this->get_field_id('location'); ?>"
			  data-city="<?php echo $this->get_field_id('city'); ?>"
			  data-cityid="<?php echo $this->get_field_id('cityID'); ?>"
			  data-state="<?php echo $this->get_field_id('state'); ?>"
			  data-country="<?php echo $this->get_field_id('country'); ?>"
			  data-url_en="<?php echo $this->get_field_id('url_en'); ?>"
			  data-url_es="<?php echo $this->get_field_id('url_es'); ?>"
			  data-init="false"
			 >
				
				<div class="form-section">
					<h3><?php echo __('General', 'weather-in-any-city-widget')?></h3>

					
					<p class="widefat autoComplete_wrapper">
						<label for="<?php echo $this->get_field_id('location'); ?>"><?php echo __('City', 'weather-in-any-city-widget')?></label>	

						<input class="widefat WIYCW-city" type="search"
						for="<?php echo $this->get_field_id('search'); ?>" 
						id="<?php echo $this->get_field_id('search'); ?>"
						name="<?php echo $this->get_field_name('search'); ?>"
						value="<?php echo esc_attr($location); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('location'); ?>" 
						id="<?php echo $this->get_field_id('location'); ?>"
						name="<?php echo $this->get_field_name('location'); ?>"
						value="<?php echo esc_attr($location); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('city'); ?>" 
						id="<?php echo $this->get_field_id('city'); ?>"
						name="<?php echo $this->get_field_name('city'); ?>"
						value="<?php echo esc_attr($city); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('cityID'); ?>" 
						id="<?php echo $this->get_field_id('cityID'); ?>"
						name="<?php echo $this->get_field_name('cityID'); ?>"
						value="<?php echo esc_attr($cityID); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('state'); ?>" 
						id="<?php echo $this->get_field_id('state'); ?>"
						name="<?php echo $this->get_field_name('state'); ?>"
						value="<?php echo esc_attr($state); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('country'); ?>" 
						id="<?php echo $this->get_field_id('country'); ?>"
						name="<?php echo $this->get_field_name('country'); ?>"
						value="<?php echo esc_attr($country); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('url_en'); ?>" 
						id="<?php echo $this->get_field_id('url_en'); ?>"
						name="<?php echo $this->get_field_name('url_en'); ?>"
						value="<?php echo esc_attr($url_en); ?>"></input>

						<input type="hidden" 
						for="<?php echo $this->get_field_id('url_es'); ?>" 
						id="<?php echo $this->get_field_id('url_es'); ?>"
						name="<?php echo $this->get_field_name('url_es'); ?>"
						value="<?php echo esc_attr($url_es); ?>"></input>

					</p>

					<p class="form-line">
						<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Title', 'weather-in-any-city-widget')?> (<?php echo __('optional', 'weather-in-any-city-widget')?>)</label>		
						<input class="widefat" type="text"
						for="<?php echo $this->get_field_id('title'); ?>" 
						id="<?php echo $this->get_field_id('title'); ?>"
						name="<?php echo $this->get_field_name('title'); ?>"
						value="<?php echo esc_attr($title); ?>"></input>
						<p style="font-size: 0.8rem;font-style: italic;"><?php echo __('Title will overwrite the city name', 'weather-in-any-city-widget')?></p>
					</p>
					

					<p class="form-line">

						<label for="<?php echo $this->get_field_id('units'); ?>"><?php echo __('Units', 'weather-in-any-city-widget')?></label>		

						<select class="widefat" name="<?php echo $this->get_field_name('units'); ?>" id="<?php echo $this->get_field_id('units'); ?>">
	                        <option value="c" <?php if ($units == "c") {
	                            echo 'selected';
	                        } ?>><?php echo __('Celsius', 'weather-in-any-city-widget')?>
	                        </option>
	                        <option value="f" <?php if ($units == "f") {
	                            echo 'selected';
	                        } ?>><?php echo __('Fahrenheit', 'weather-in-any-city-widget')?>
	                        </option>
		                </select>
					</p>
				</div>
				
				<div class="form-section">
					<h2><?php echo esc_html__('Design', 'weather-in-any-city-widget'); ?></h2>
					<input type="hidden" name="<?php echo esc_attr($this->get_field_name('WIYCW_v2')); ?>" value="1" />
					<p class="form-line">
						<label for="<?php echo esc_attr($this->get_field_id('design')); ?>"><?php echo esc_html__('Design', 'weather-in-any-city-widget'); ?></label>
						<select class="widefat" name="<?php echo esc_attr($this->get_field_name('design')); ?>" id="<?php echo esc_attr($this->get_field_id('design')); ?>">
							<option value="card" <?php selected($instance['design'], 'card'); ?>><?php echo esc_html__('Sky card', 'weather-in-any-city-widget'); ?></option>
							<option value="classic" <?php selected($instance['design'], 'classic'); ?>><?php echo esc_html__('Classic', 'weather-in-any-city-widget'); ?></option>
						</select>
					</p>
					<p class="form-line">
						<label for="<?php echo $this->get_field_id('layout'); ?>"><?php echo __('Layout', 'weather-in-any-city-widget')?></label>		

						<select class="widefat" name="<?php echo $this->get_field_name('layout'); ?>" id="<?php echo $this->get_field_id('layout'); ?>">
	                        <option value="vertical" <?php if ($layout == "vertical") {
	                            echo 'selected';
	                        } ?>><?php echo __('Vertical', 'weather-in-any-city-widget')?>
	                        </option>
	                        <option value="horizontal" <?php if ($layout == "horizontal") {
	                            echo 'selected';
	                        } ?>><?php echo __('Horizontal', 'weather-in-any-city-widget')?>
	                        </option>
		                </select>
					</p>
					<p class="wiycw-only-card" style="font-size: 0.8rem;font-style: italic;"><?php echo esc_html__('The sky card changes its sky with the weather. These options only apply to it:', 'weather-in-any-city-widget'); ?></p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['description'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('description')); ?>"
							name="<?php echo esc_attr($this->get_field_name('description')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('description')); ?>"><?php echo esc_html__('Weather in words', 'weather-in-any-city-widget'); ?></label>
					</p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['feels_like'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('feels_like')); ?>"
							name="<?php echo esc_attr($this->get_field_name('feels_like')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('feels_like')); ?>"><?php echo esc_html__('Feels like', 'weather-in-any-city-widget'); ?></label>
					</p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['now_visibility'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('now_visibility')); ?>"
							name="<?php echo esc_attr($this->get_field_name('now_visibility')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('now_visibility')); ?>"><?php echo esc_html__('Visibility', 'weather-in-any-city-widget'); ?></label>
					</p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['hourly'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('hourly')); ?>"
							name="<?php echo esc_attr($this->get_field_name('hourly')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('hourly')); ?>"><?php echo esc_html__('Hourly forecast', 'weather-in-any-city-widget'); ?></label>
					</p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['unit_switch'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('unit_switch')); ?>"
							name="<?php echo esc_attr($this->get_field_name('unit_switch')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('unit_switch')); ?>"><?php echo esc_html__('Let visitors switch between °C and °F', 'weather-in-any-city-widget'); ?></label>
					</p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['updated'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('updated')); ?>"
							name="<?php echo esc_attr($this->get_field_name('updated')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('updated')); ?>"><?php echo esc_html__('Time of the last update', 'weather-in-any-city-widget'); ?></label>
					</p>
					<p class="form-line wiycw-only-card">
						<input class="checkbox" type="checkbox" <?php checked($instance['animation'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('animation')); ?>"
							name="<?php echo esc_attr($this->get_field_name('animation')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('animation')); ?>"><?php echo esc_html__('Animated sky', 'weather-in-any-city-widget'); ?></label>
					</p>
				</div>

				<div class="form-section">
					<h2><?php echo __('Current conditions', 'weather-in-any-city-widget')?></h2>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($today == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('today'); ?>" 
							id="<?php echo $this->get_field_id('today'); ?>"
							name="<?php echo $this->get_field_name('today'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('today'); ?>"><?php echo __('Show current conditions', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_icon == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_icon'); ?>" 
							id="<?php echo $this->get_field_id('now_icon'); ?>"
							name="<?php echo $this->get_field_name('now_icon'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_icon'); ?>"><?php echo __('Icon', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_temp == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_temp'); ?>" 
							id="<?php echo $this->get_field_id('now_temp'); ?>"
							name="<?php echo $this->get_field_name('now_temp'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_temp'); ?>"><?php echo __('Temperature', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_humidity == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_humidity'); ?>" 
							id="<?php echo $this->get_field_id('now_humidity'); ?>"
							name="<?php echo $this->get_field_name('now_humidity'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_humidity'); ?>"><?php echo __('Humidity', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_wind == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_wind'); ?>" 
							id="<?php echo $this->get_field_id('now_wind'); ?>"
							name="<?php echo $this->get_field_name('now_wind'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_wind'); ?>"><?php echo __('Wind', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_pressure == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_pressure'); ?>" 
							id="<?php echo $this->get_field_id('now_pressure'); ?>"
							name="<?php echo $this->get_field_name('now_pressure'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_pressure'); ?>"><?php echo __('Pressure', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_cloudiness == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_cloudiness'); ?>" 
							id="<?php echo $this->get_field_id('now_cloudiness'); ?>"
							name="<?php echo $this->get_field_name('now_cloudiness'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_cloudiness'); ?>"><?php echo __('Cloudiness', 'weather-in-any-city-widget')?></label>	
					</p>



					<p class="form-line">
						<input class="checkbox" type="checkbox" 
							<?php if ($now_sunrise == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('now_sunrise'); ?>" 
							id="<?php echo $this->get_field_id('now_sunrise'); ?>"
							name="<?php echo $this->get_field_name('now_sunrise'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('now_sunrise'); ?>"><?php echo __('Sunrise / sunset', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						<label for="<?php echo $this->get_field_id('time_format'); ?>"><?php echo __('Time format', 'weather-in-any-city-widget')?></label>		

						<select class="widefat" name="<?php echo $this->get_field_name('time_format'); ?>" id="<?php echo $this->get_field_id('time_format'); ?>">
	                        <option value="universal" <?php if ($time_format == "universal") {
	                            echo 'selected';
	                        } ?>><?php echo __('24 hours', 'weather-in-any-city-widget')?>
	                        </option>
	                        <option value="standard" <?php if ($time_format == "standard") {
	                            echo 'selected';
	                        } ?>><?php echo __('12 hours', 'weather-in-any-city-widget')?>
	                        </option>
		                </select>
					</p>



				</div>
				
				<div class="form-section">
					<h2><?php echo __('Forecast', 'weather-in-any-city-widget')?></h2>
					<p class="form-line">
						<label for="<?php echo $this->get_field_id('days'); ?>"><?php echo __('Days', 'weather-in-any-city-widget')?></label>
						<select class="widefat" name="<?php echo $this->get_field_name('days'); ?>" id="<?php echo $this->get_field_id('days'); ?>">

							 		<option value="off" <?php if ($days == "off") {
			                            echo 'selected';
			                        } ?>><?php echo __('Disable forecast', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="1" <?php if ($days == 1) {
			                            echo 'selected';
			                        } ?>><?php echo __('1 day', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="2" <?php if ($days == 2) {
			                            echo 'selected';
			                        } ?>><?php echo __('2 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="3" <?php if ($days == 3) {
			                            echo 'selected';
			                        } ?>><?php echo __('3 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="4" <?php if ($days == 4) {
			                            echo 'selected';
			                        } ?>><?php echo __('4 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="5" <?php if ($days == 5) {
			                            echo 'selected';
			                        } ?>><?php echo __('5 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="6" <?php if ($days == 6) {
			                            echo 'selected';
			                        } ?>><?php echo __('6 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="7" <?php if ($days == 7) {
			                            echo 'selected';
			                        } ?>><?php echo __('7 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="8" <?php if ($days == 8) {
			                            echo 'selected';
			                        } ?>><?php echo __('8 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="9" <?php if ($days == 9) {
			                            echo 'selected';
			                        } ?>><?php echo __('9 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="10" <?php if ($days == 10) {
			                            echo 'selected';
			                        } ?>><?php echo __('10 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="11" <?php if ($days == 11) {
			                            echo 'selected';
			                        } ?>><?php echo __('11 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="12" <?php if ($days == 12) {
			                            echo 'selected';
			                        } ?>><?php echo __('12 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="13" <?php if ($days == 13) {
			                            echo 'selected';
			                        } ?>><?php echo __('13 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <option value="14" <?php if ($days == 14) {
			                            echo 'selected';
			                        } ?>><?php echo __('14 days', 'weather-in-any-city-widget'); ?>
			                        </option>
			                        <?php // a widget saved with more days (the versions before offered 15) keeps them ?>
			                        <?php if (is_numeric($days) && $days > 14) { ?>
			                        <option value="<?php echo esc_attr(intval($days)); ?>" selected><?php echo esc_html(str_replace('14', (string) intval($days), __('14 days', 'weather-in-any-city-widget'))); ?>
			                        </option>
			                        <?php } ?>
			                    </select>	
					</p>

					

					<p class="form-line">
						<input type="checkbox" 
							<?php if ($temp == "on") {
                                echo 'checked';
                            }; ?>
							for="<?php echo $this->get_field_id('temp'); ?>" 
							id="<?php echo $this->get_field_id('temp'); ?>"
							name="<?php echo $this->get_field_name('temp'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('temp'); ?>"><?php echo __('Temperature', 'weather-in-any-city-widget')?></label>	
					</p>

					<p class="form-line">
						
						<input type="checkbox" 
							<?php if ($icon == "on") {
                                echo 'checked';
                            }; ?>
							for="<?php echo $this->get_field_id('icon'); ?>" 
							id="<?php echo $this->get_field_id('icon'); ?>"
							name="<?php echo $this->get_field_name('icon'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('icon'); ?>"><?php echo __('Icon', 'weather-in-any-city-widget')?></label>
					</p>

					<p class="form-line">
						<input type="checkbox" 
						<?php if ($wind == "on") {
                            echo 'checked';
                        }; ?>
						for="<?php echo $this->get_field_id('wind'); ?>" 
						id="<?php echo $this->get_field_id('wind'); ?>"
						name="<?php echo $this->get_field_name('wind'); ?>"
						></input>
						<label for="<?php echo $this->get_field_id('temp'); ?>"><?php echo __('Wind', 'weather-in-any-city-widget')?></label>
					</p>


					<p class="form-line">
						<input class="WIYCW-checkbox" type="checkbox" 
							<?php if ($rainChance == "on") {
                                echo 'checked';
                            }; ?>
							for="<?php echo $this->get_field_id('rainChance'); ?>" 
							id="<?php echo $this->get_field_id('rainChance'); ?>"
							name="<?php echo $this->get_field_name('rainChance'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('rainChance'); ?>"><?php echo __('Rain probability', 'weather-in-any-city-widget')?></label>
					</p>
				</div>


				<div class="form-section">
					<h2><?php echo __('Look and feel', 'weather-in-any-city-widget')?></h2>

					<p class="form-line">
						<label for="<?php echo $this->get_field_id('fontSize'); ?>"><?php echo __('Font size', 'weather-in-any-city-widget')?></label>
						 <input type="number" id="<?php echo $this->get_field_id('fontSize'); ?>"
		                        name="<?php echo $this->get_field_name('fontSize'); ?>"
		                        value="<?php echo esc_attr($fontSize); ?>"/>
					</p>

					<p class="form-line wiycw-only-classic">

						<label for="<?php echo $this->get_field_id('weatherIconsColor'); ?>"><?php echo __('Weather icons color', 'weather-in-any-city-widget')?></label>		

						<select class="widefat" name="<?php echo $this->get_field_name('weatherIconsColor'); ?>" id="<?php echo $this->get_field_id('weatherIconsColor'); ?>">
							 <option value="color" <?php if ($weatherIconsColor == "color") {
	                            echo 'selected';
	                        } ?>><?php echo __('Color', 'weather-in-any-city-widget')?>
	                        </option>
	                        <option value="light" <?php if ($weatherIconsColor == "light") {
	                            echo 'selected';
	                        } ?>><?php echo __('Light', 'weather-in-any-city-widget')?>
	                        </option>
	                        <option value="dark" <?php if ($weatherIconsColor == "dark") {
	                            echo 'selected';
	                        } ?>><?php echo __('Dark', 'weather-in-any-city-widget')?>
	                        </option>
		                </select>
					</p>

				

					<p class="form-line wiycw-only-classic">

						<label for="<?php echo $this->get_field_id('iconsColor'); ?>"><?php echo __('UI icons color', 'weather-in-any-city-widget')?></label>		

						<select class="widefat" name="<?php echo $this->get_field_name('iconsColor'); ?>" id="<?php echo $this->get_field_id('iconsColor'); ?>">
	                        <option value="light" <?php if ($iconsColor == "light") {
	                            echo 'selected';
	                        } ?>><?php echo __('Light', 'weather-in-any-city-widget')?>
	                        </option>
	                        <option value="dark" <?php if ($iconsColor == "dark") {
	                            echo 'selected';
	                        } ?>><?php echo __('Dark', 'weather-in-any-city-widget')?>
	                        </option>
		                </select>
					</p>

					

					<p class="form-line wiycw-only-classic">
						<label for="<?php echo $this->get_field_id('textColor'); ?>"><?php echo __('Font color', 'weather-in-any-city-widget')?></label>
						 <input type="color" id="<?php echo $this->get_field_id('textColor'); ?>"
		                        name="<?php echo $this->get_field_name('textColor'); ?>"
		                        value="<?php echo esc_attr($textColor); ?>"/>
					</p>

					
					<p class="form-line wiycw-only-classic">
						<label for="<?php echo $this->get_field_id('backgroundColor'); ?>"><?php echo __('Background color', 'weather-in-any-city-widget')?></label>
						 <input type="color" id="<?php echo $this->get_field_id('backgroundColor'); ?>"
		                        name="<?php echo $this->get_field_name('backgroundColor'); ?>"
		                        value="<?php echo esc_attr($backgroundColor); ?>"/>
					</p>


					<p class="form-line wiycw-only-classic">
						<label for="<?php echo $this->get_field_id('borderColor'); ?>"><?php echo __('Border color', 'weather-in-any-city-widget')?></label>
						 <input type="color" id="<?php echo $this->get_field_id('borderColor'); ?>"
		                        name="<?php echo $this->get_field_name('borderColor'); ?>"
		                        value="<?php echo esc_attr($borderColor); ?>"/>
					</p>

					<p class="form-line wiycw-only-classic">
						<input class="checkbox" type="checkbox" 
							<?php if ($shadow == "on") {
	                            echo 'checked';
	                        }; ?>
							for="<?php echo $this->get_field_id('shadow'); ?>" 
							id="<?php echo $this->get_field_id('shadow'); ?>"
							name="<?php echo $this->get_field_name('shadow'); ?>"
							></input>
						<label for="<?php echo $this->get_field_id('shadow'); ?>"><?php echo __('Shadow', 'weather-in-any-city-widget')?></label>
					</p>
					<p class="form-line">
						<input class="checkbox" type="checkbox" <?php checked($instance['credit_link'], 'on'); ?>
							id="<?php echo esc_attr($this->get_field_id('credit_link')); ?>"
							name="<?php echo esc_attr($this->get_field_name('credit_link')); ?>" />
						<label for="<?php echo esc_attr($this->get_field_id('credit_link')); ?>"><?php echo esc_html(WIYCW_credit_label()); ?></label>
					</p>

				
				</div>

			</div>
			
		</div>
	
    	<?php
    }

}


function WIYCW_widget()
{
    register_widget('WIYCW_widget');
}

add_action('widgets_init', 'WIYCW_widget');


function WIYCW_admin_scripts( $hook ) {
    wp_enqueue_style('WIYCW-autoComplete-style', plugin_dir_url(__FILE__). 'resources/css/WIYCW-autoComplete.css', array(), WIYCW_VERSION);
   	// the form shows the options of the chosen design only (a browser without :has() shows them all, as before)
   	wp_add_inline_style('WIYCW-autoComplete-style',
   		'.WIYCW-form:has(select[id$="-design"] option[value="card"]:checked) .wiycw-only-classic,'
   		. '.WIYCW-form:has(select[id$="-design"] option[value="classic"]:checked) .wiycw-only-card{display:none}');
    wp_register_script('WIYCW-admin', plugin_dir_url(__FILE__). 'resources/js/WIYCW-admin-widget.js', array('jquery'), WIYCW_VERSION, true);
   	wp_register_script('WIYCW-autoComplete', plugin_dir_url(__FILE__). 'resources/js/WIYCW-autoComplete.min.js', array(), WIYCW_VERSION, true);
   	wp_enqueue_script('WIYCW-admin');
   	wp_localize_script('WIYCW-admin', 'WIYCW_admin', array('searchUrl' => WIYCW_API.'/wpsearch'));
   	wp_enqueue_script('WIYCW-autoComplete');
}

add_action('admin_enqueue_scripts', 'WIYCW_admin_scripts');


// The weather service whose logo goes under the widgets of this site: eltiempoen.com in Spanish, weatherin.org in any
// other language (the language of the site, not the one of whoever edits it)
function WIYCW_credit_brand(){
	return strpos(get_locale(), 'es') === 0 ? 'eltiempoen.com' : 'weatherin.org';
}

// The words of the box that switches the logo off: «Show the logo of eltiempoen.com»
function WIYCW_credit_label(){
	/* translators: %s: eltiempoen.com or weatherin.org */
	return sprintf(__('Show the logo of %s', 'weather-in-any-city-widget'), WIYCW_credit_brand());
}

/**
 * The small link under the widget: the logo of the weather service (eltiempoen.com in Spanish, weatherin.org in any
 * other language) to the page of the city, nofollow, with the name of the city in words in its alt. It can be switched off
 * (credit_link, in the widget, the block and the shortcode).
 */
function WIYCW_credit($instance){
	if(isset($instance['credit_link']) && $instance['credit_link'] == 'off'){
		return '';
	}
	// the locale of the site (es_MX), not get_bloginfo('language'), which says «es» for many varieties of Spanish
	$locale = function_exists('determine_locale') ? determine_locale() : get_locale();
	$spanish = strpos($locale, 'es') === 0;
	// «el tiempo» in Spain, «clima» in the Americas
	$spain = in_array($locale, array('es', 'es_ES', 'es_CL'), true);
	$brand = $spanish ? 'eltiempoen.com' : 'weatherin.org';
	$city = isset($instance['city']) ? $instance['city'] : '';
	// the page of the city when the widget knows it (the widget and the block; a shortcode, only with its city); if not, the home
	if(!empty($instance['url_en']) && !empty($instance['url_es'])){
		$url = $spanish ? 'https://eltiempoen.com/' . $instance['url_es'] : 'https://weatherin.org/' . $instance['url_en'];
	}else{
		$url = 'https://' . $brand;
	}
	if($city !== ''){
		$text = $spanish ? ($spain ? 'El tiempo en ' : 'Clima en ') . $city : $city . ' weather';
	}else{
		$text = $spanish ? ($spain ? 'El tiempo' : 'Clima') : 'Weather';
	}
	$icon = plugin_dir_url(__FILE__) . 'resources/icons/credit/' . ($spanish ? 'eltiempoen' : 'weatherin') . '.svg';
	// the logo of the header of the site (233 × 62, drawn 18 px high), the words in its alt
	return "<a class='WIYCW-credit' href='" . esc_url($url) . "' rel='nofollow' title='" . esc_attr($text) . "'>"
		. "<img src='" . esc_url($icon) . "' alt='" . esc_attr($text) . "' width='68' height='18' loading='lazy' decoding='async'></a>";
}

function WIYCW_public($instance){
	$card = $instance['design'] == 'card';
	WIYCW_enqueue($card);

   	$i18n = WIYCW_i18n();
   	$lang = get_bloginfo("language");

   	$credits = WIYCW_credit($instance);

   	if(!empty($instance['title'])){
   		$title = $instance['title'];
   	}else{
   		$title = $instance['city'];
   	}

   	$iconColorClass = $instance['iconsColor'] == 'dark' ? "WIYCW-icon-dark" : "WIYCW-icon-light";
   	$shadowClass = $instance['shadow'] == 'on' ? "WIYCW-text-shadow" : "";
   	$baseURL = esc_url(WIYCW_DEF_BASEURL);
    $output = "<script>";
    $output .= "var WIYCW_i18n = ".json_encode($i18n)."";
    $output .= "</script>";
    if($instance['isWidget'] == true){
 		$output .= "<div class='widget'>";
    }else{
    	$output .= "<div class='WIYCW-shortcode'>";
    }
	if($card){
		// the card takes its colours from the sky
		$output .= "<div class='WIYCW-wrapper WIYCW-design-card'";
		$output .= " style='font-size:".esc_attr(intval($instance['fontSize']) > 0 ? intval($instance['fontSize']) : 15)."px'";
	}else{
		$output .= "<div class='WIYCW-wrapper ".esc_attr($shadowClass)."'";
		$output .= " style='font-size:".esc_attr($instance['fontSize'])."px;color:".esc_attr($instance['textColor'])."; background-color:".esc_attr($instance['backgroundColor'])."; border: solid 1px ".esc_attr($instance['borderColor'])."'";
	}
	$output .= " aria-busy='true'";
	$output .= " data-version='".esc_attr(WIYCW_VERSION)."'";
	$output .= " data-path='".esc_url(WIYCW_DEF_BASEURL)."'";
	$output .= " data-lang='".esc_attr($lang)."'";
	$output .= " data-cityid='".esc_attr($instance['cityID'])."'";
	$output .= " data-city='".esc_attr($instance['city'])."'";
	$output .= " data-units='".esc_attr($instance['units'])."'";
	$output .= " data-today='".esc_attr($instance['today'])."'";
	$output .= " data-nowicon='".esc_attr($instance['now_icon'])."'";
	$output .= " data-nowtemp='".esc_attr($instance['now_temp'])."'";
	$output .= " data-nowhumidity='".esc_attr($instance['now_humidity'])."'";
	$output .= " data-nowwind='".esc_attr($instance['now_wind'])."'";
	$output .= " data-nowsunrise='".esc_attr($instance['now_sunrise'])."'";
	$output .= " data-timeformat='".esc_attr($instance['time_format'])."'";
	$output .= " data-nowpressure='".esc_attr($instance['now_pressure'])."'";
	$output .= " data-nowcloudiness='".esc_attr($instance['now_cloudiness'])."'";
	$output .= " data-days='".esc_attr($instance['days'])."'";
	$output .= " data-layout='".esc_attr($instance['layout'])."'";
	$output .= " data-wind='".esc_attr($instance['wind'])."'";
	$output .= " data-rain='".esc_attr($instance['rain'])."'";
	$output .= " data-rainchance='".esc_attr($instance['rainChance'])."'";
	$output .= " data-forecasticon='".esc_attr($instance['icon'])."'";
	$output .= " data-temp='".esc_attr($instance['temp'])."'";
	$output .= " data-weathericonscolor='".esc_attr($instance['weatherIconsColor'])."'";
	$output .= " data-iconscolor='".esc_attr($instance['iconsColor'])."'";
	$output .= " data-backgroundcolor='".esc_attr($instance['backgroundColor'])."'";
	$output .= " data-textcolor='".esc_attr($instance['textColor'])."'";
	$output .= " data-shadow='".esc_attr($instance['shadow'])."'";
	$output .= " data-url='".esc_attr($instance['url'])."'";
	$output .= " data-api='".esc_url(WIYCW_API)."'";
	$output .= " data-f='2'";
	// the site signs the cities it shows: only they can make it ask the weather service
	$output .= " data-k='".esc_attr(WIYCW_key($instance['cityID']))."'";
	// who can edit the site is told why a widget has no weather
	if(current_user_can('edit_posts')){
		$output .= " data-admin='1'";
	}
	$output .= " data-design='".esc_attr($instance['design'])."'";
	$output .= " data-title='".esc_attr($title)."'";
	$output .= " data-description='".esc_attr($instance['description'])."'";
	$output .= " data-feelslike='".esc_attr($instance['feels_like'])."'";
	$output .= " data-nowvisibility='".esc_attr($instance['now_visibility'])."'";
	$output .= " data-hourly='".esc_attr($instance['hourly'])."'";
	$output .= " data-unitswitch='".esc_attr($instance['unit_switch'])."'";
	$output .= " data-updated='".esc_attr($instance['updated'])."'";
	$output .= " data-animation='".esc_attr($instance['animation'])."'";
	$output .= " data-action='".esc_attr($instance['action'])."'";
	$output .= " data-nonce='".esc_attr(wp_create_nonce(WIYCW_VERSION))."'";
	$output .= " data-bordercolor='".esc_attr($instance['borderColor'])."'";
	$output .=  ">";
	if($card){
		$output .= "<div class='WIYCW-content'><div class='WIYCW-card-waiting'></div></div>";
	}else{
		$output .= "<div class='WIYCW-header'>";
		$output .=  esc_html($title);
		$output .=  "</div>";
		$output .= "<div class='WIYCW-content'>";


		if($instance['today'] == "on"){
			$output .= "<div class='WIYCW-now'>";

			$output .= "<div class='WIYCW-now-row1'>";
			if($instance['now_icon'] == "on"){
				$output .=  "<div class='WIYCW-now-icon'><img alt='Current weather' style='visibility:hidden; width: 70px; height: 70px;'></div>";
			}

			if($instance['now_temp'] == "on"){
				$output .=  "<div class='WIYCW-now-temp'>-º</div>";
			}
			$output .=  "</div>";

			$output .=  "<div class='WIYCW-now-row-info'>";
			if($instance['now_sunrise'] == "on"){
				$output .= "<div class='WIYCW-now-row-info-col'><img alt='Sunrise' class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/sunrise.svg' style='width: 11px; height: 11px;'>-</div>";
				$output .= "<div class='WIYCW-now-row-info-col'><img alt='Sunset' class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/sunset.svg' style='width: 11px; height: 11px;'>-</div>";
			}
			if($instance['now_humidity'] == "on"){
				$output .= "<div class='WIYCW-now-row-info-col'><img alt='Humidity'class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/dew-point.svg' style='width: 11px; height: 11px;'>-</div>";
			}

			if($instance['now_wind'] == "on"){
				$output .= "<div class='WIYCW-now-row-info-col'><img alt='Wind direction' class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/arrow.svg' style='width: 11px; height: 11px;transform: rotate(0deg)'>-</div>";
			}

			if($instance['now_pressure'] == "on"){
				$output .= "<div class='WIYCW-now-row-info-col'><img alt='Pressure' class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/pressure.svg' style='width: 11px; height: 11px;'>-</div>";
			}

			if($instance['now_cloudiness'] == "on"){
				$output .= "<div class='WIYCW-now-row-info-col'><img alt='Cloudiness'class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/clouds.svg' style='width: 11px; height: 11px;'>-</div>";
			}

			$output .=  "</div>";
			$output .=  "</div>";
		}

		if($instance['days'] != "off"){
			$output .= "<div class='WIYCW-forecast WIYCW-forecast-".esc_attr($instance['layout'])."'>";

			for($WIYCWcounter=0; $WIYCWcounter < min(14, max(0, intval($instance['days']))); $WIYCWcounter++){
				$output .= "<div class='WIYCW-forecast-row'>";
				$output .= "<div class='WIYCW-forecast-date WIYCW-col-1'>-</div>";

				if($instance['temp'] == "on"){
					$output .= "<div class='WIYCW-forecast-temp WIYCW-col-1'>-</div>";
				}
				if($instance['icon'] == "on"){
					$output .= "<div class='WIYCW-forecast-icon WIYCW-col-1'><img alt='Forecast' style='visibility:hidden; width: 30px; height: 30px;'></div>";
				}
				if($instance['rainChance'] == "on"){
					$output .= "<div class='WIYCW-forecast-pop WIYCW-col-1'><img alt='Rain chance' class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/umbrella.svg' style='width: 11px; height: 11px;'>-</div>";
				}
				if($instance['wind'] == "on"){
					$output .= "<div class='WIYCW-forecast-pop WIYCW-col-1'><img alt='Wind direction' class='WIYCW-item-icon ".esc_attr($iconColorClass)."' src='".esc_attr($baseURL)."/resources/icons/ui-icons/arrow.svg' style='width: 11px; height: 11px;transform: rotate(0deg);'>-</div>";
				}

				$output .=  "</div>";
			}

			$output .=  "</div>";
		}	


		$output .= "</div>";
	}

	// the data the site already has, so the page needs no request for it
	$output .= WIYCW_carried($instance['cityID']);

	if($credits !== ""){
		$output .= "<div class='WIYCW-footer'>";
		$output .=  $credits;
		$output .=  "</div>";
	}
	$output .= "</div>";
	$output .= "</div>";
	return $output;
}

function WIYCW_shortcode($atts = []	){
	if(!is_array($atts)){
		$atts = array();
	}
	$atts['cityID'] = empty($atts['cityid']) ? "" : $atts['cityid'];
	$atts['backgroundColor'] = empty($atts['backgroundcolor']) ? "" : $atts['backgroundcolor'];
	$atts['fontSize'] = empty($atts['fontsize']) ? "" : $atts['fontsize'];
	$atts['textColor'] = empty($atts['textcolor']) ? "" : $atts['textcolor'];
	$atts['borderColor'] = empty($atts['bordercolor']) ? "" : $atts['bordercolor'];
	$atts['rainChance'] = empty($atts['rainchance']) ? "" : $atts['rainchance'];
	$atts['iconsColor'] = empty($atts['iconscolor']) ? "" : $atts['iconscolor'];
	$atts['weatherIconsColor'] = empty($atts['weathericonscolor']) ? "" : $atts['weathericonscolor'];
	$atts['url_en'] = '';
	$atts['url_es'] = '';
	$atts['isWidget'] = false;
	$instance = WIYCW_default_values($atts);
	return WIYCW_public($instance);
}



function WIYCW_shortcodes_init() {
	add_shortcode('weather_pro', 'WIYCW_shortcode');
}

add_action( 'init', 'WIYCW_shortcodes_init' );

function WIYCW_get_weather(){

	$error = array('error' => true, 'message' => '');

	if(!isset($_GET['id'])){
		$error['message'] = 'ID not set';
		wp_send_json($error);
		die();
	}


	$id = intval(sanitize_text_field($_GET['id']));
	// the script of 2.0.0 asks with f=2. A page kept in a cache with the script of before does not: it gets what it
	// always got, from the code below
	if($id > 0 && isset($_GET['f']) && $_GET['f'] === '2'){
		$key = isset($_GET['k']) ? sanitize_text_field(wp_unslash($_GET['k'])) : '';
		WIYCW_send_weather($id, hash_equals(WIYCW_key($id), $key));
	}
	if($id <= 0){
		$error['message'] = 'Incorrect city ID';
		wp_send_json($error);
		die();
	}

	$cache_key = substr('wiycw_'.$id, 0, 44);
	$response = get_transient($cache_key);
	$data = empty($response) ? null : json_decode($response);

	if(empty($data)) {

		// after a failure, the service is not asked again for a minute: the browser asks it directly meanwhile
		$failed_key = substr('wiycw_failed_'.$id, 0, 44);
		$failed = get_transient($failed_key);
		if(!empty($failed)){
			$error['message'] = $failed;
			wp_send_json($error);
			die();
		}

		$kept = WIYCW_stored($id);
		$known = $kept ? json_decode($kept['body']) : null;
		if(WIYCW_valid($known) && time() - $kept['saved'] < $kept['ttl']){
			wp_send_json($known);
			die();
		}

		// these requests carry no signature (the pages of before 2.0.0): not more than a few new cities an hour
		if(!WIYCW_valid($known) && !WIYCW_unsigned_allowed()){
			$error['message'] = 'Too many requests';
			wp_send_json($error);
			die();
		}

		$params = array(
		 'id' => $id,
		 's' => get_site_url(),
		 'v' => WIYCW_VERSION,
		 'c' => 'true'
		);

		$url = add_query_arg($params, WIYCW_API.'/widget');
		$arg = array('timeout' => 8);

		$request = wp_remote_get($url, $arg);
		$code = is_wp_error($request) ? 0 : intval(wp_remote_retrieve_response_code($request));
		$response = is_wp_error($request) ? '' : wp_remote_retrieve_body($request);
		$data = ($code == 200 || $code == 201) ? json_decode($response) : null;

		if(!empty($data)) {
			set_transient($cache_key, $response, MINUTE_IN_SECONDS * 30);
		}else{
			if(is_wp_error($request)){
				$error['message'] = $request->get_error_message();
			}else if($code != 200 && $code != 201){
				$error['message'] = 'Response code: '.$code;
			}else{
				$error['message'] = 'Invalid response';
			}
			set_transient($failed_key, $error['message'], MINUTE_IN_SECONDS);

			// the service fails: the last good answer the site keeps, as the script of now gets
			if(WIYCW_valid($known)){
				wp_send_json($known);
				die();
			}

			wp_send_json($error);
			die();
		}

	}

	wp_send_json($data);

}

add_action('wp_ajax_WIYCW_get_weather', 'WIYCW_get_weather');
add_action('wp_ajax_nopriv_WIYCW_get_weather', 'WIYCW_get_weather');

?>