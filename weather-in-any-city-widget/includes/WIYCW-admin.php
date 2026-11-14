<?php
/**
 * Weather Widget Pro: where to find it in the admin (since 2.0.0). A page under «Settings» that says how to add the
 * weather (block, widget or shortcode) and writes the shortcode of a city; a link to it in the list of plugins; and a
 * notice the first time, after activating.
 */

defined( 'ABSPATH' ) or die( 'ABSPATH not defined' );

function WIYCW_help_url(){
	return admin_url('options-general.php?page=wiycw');
}

function WIYCW_admin_menu(){
	add_options_page(
		__('Weather Widget Pro', 'weather-in-any-city-widget'),
		__('Weather Widget Pro', 'weather-in-any-city-widget'),
		'manage_options',
		'wiycw',
		'WIYCW_help_page'
	);
}

add_action('admin_menu', 'WIYCW_admin_menu');

function WIYCW_action_links($links){
	array_unshift($links, '<a href="' . esc_url(WIYCW_help_url()) . '">' . esc_html__('How to use', 'weather-in-any-city-widget') . '</a>');
	return $links;
}

add_filter('plugin_action_links_' . plugin_basename(WIYCW_FILE), 'WIYCW_action_links');

function WIYCW_activated(){
	set_transient('wiycw_welcome', 1, DAY_IN_SECONDS);
}

register_activation_hook(WIYCW_FILE, 'WIYCW_activated');

function WIYCW_welcome_notice(){
	if(!get_transient('wiycw_welcome') || !current_user_can('manage_options')){
		return;
	}
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if($screen && $screen->id === 'settings_page_wiycw'){
		delete_transient('wiycw_welcome');
		return;
	}
	echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__('Weather Widget Pro', 'weather-in-any-city-widget') . '</strong>: '
		. esc_html__('add the weather with the block, the widget or a shortcode.', 'weather-in-any-city-widget')
		. ' <a href="' . esc_url(WIYCW_help_url()) . '">' . esc_html__('How to use', 'weather-in-any-city-widget') . '</a></p></div>';
	delete_transient('wiycw_welcome');
}

add_action('admin_notices', 'WIYCW_welcome_notice');

function WIYCW_help_assets($hook){
	if($hook !== 'settings_page_wiycw'){
		return;
	}
	// the widget itself, for the preview of the shortcode
	WIYCW_enqueue(true);
	wp_enqueue_script('WIYCW-help', WIYCW_DEF_BASEURL . '/resources/js/WIYCW-help.js', array('WIYCW-widget'), WIYCW_VERSION, true);
	wp_localize_script('WIYCW-help', 'WIYCW_help', array(
		'searchUrl' => WIYCW_API . '/wpsearch',
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('wiycw_preview'),
		/* translators: %d: how big the preview is drawn, in percent */
		'scaled' => __('Shown at %d%% of its size so that it fits here: on your site it will look bigger.', 'weather-in-any-city-widget'),
		// what the shortcode does when an attribute is not written (WIYCW_default_values): only the others are written
		'defaults' => WIYCW_generator_defaults()
	));
}

add_action('admin_enqueue_scripts', 'WIYCW_help_assets');

function WIYCW_help_page(){
	if(!current_user_can('manage_options')){
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__('Weather Widget Pro', 'weather-in-any-city-widget'); ?></h1>
		<p><?php echo esc_html__('Weather for more than 200,000 places, without an API key. There are three ways to add it:', 'weather-in-any-city-widget'); ?></p>

		<div class="card" style="max-width: 760px;">
			<h2>1. <?php echo esc_html__('Block', 'weather-in-any-city-widget'); ?></h2>
			<p><?php echo esc_html__('In the editor, add the “Weather Widget Pro” block, search for your city and choose what to show in the block sidebar.', 'weather-in-any-city-widget'); ?></p>
		</div>

		<div class="card" style="max-width: 760px;">
			<h2>2. <?php echo esc_html__('Widget', 'weather-in-any-city-widget'); ?></h2>
			<p><?php echo esc_html__('Go to Appearance > Widgets (or the Customizer) and add “Weather Widget Pro” to a sidebar. If your theme has no widget areas, use the block or the shortcode.', 'weather-in-any-city-widget'); ?></p>
			<?php if(current_user_can('edit_theme_options')){ ?>
				<p><a class="button" href="<?php echo esc_url(admin_url('widgets.php')); ?>"><?php echo esc_html__('Go to Widgets', 'weather-in-any-city-widget'); ?></a></p>
			<?php } ?>
		</div>

		<div class="card wiycw-gen-card">
			<h2>3. <?php echo esc_html__('Shortcode', 'weather-in-any-city-widget'); ?></h2>
			<p><?php echo esc_html__('Search for a city, choose a design, then copy the shortcode and paste it into any page, post or text widget.', 'weather-in-any-city-widget'); ?></p>
			<div class="wiycw-gen">
				<div class="wiycw-gen-options" id="wiycw-generator">
					<?php foreach(WIYCW_generator_fields() as $group => $fields){ ?>
						<fieldset class="wiycw-gen-group">
							<legend><?php echo esc_html($group); ?></legend>
							<?php foreach($fields as $field){ WIYCW_generator_field($field); } ?>
						</fieldset>
					<?php } ?>
				</div>
				<div class="wiycw-gen-side">
					<h3><?php echo esc_html__('Preview', 'weather-in-any-city-widget'); ?></h3>
					<div class="wiycw-gen-preview" id="wiycw-preview" aria-live="polite"></div>
					<p class="description" id="wiycw-scaled" hidden></p>
					<h3><label for="wiycw-shortcode"><?php echo esc_html__('Shortcode', 'weather-in-any-city-widget'); ?></label></h3>
					<textarea id="wiycw-shortcode" class="large-text code" rows="4" readonly></textarea>
					<p><button type="button" class="button button-primary" id="wiycw-copy"><?php echo esc_html__('Copy', 'weather-in-any-city-widget'); ?></button>
					<span id="wiycw-copied" style="margin-left: 8px;" hidden><?php echo esc_html__('Copied', 'weather-in-any-city-widget'); ?></span></p>
				</div>
			</div>
		</div>
		<style>
			.wiycw-gen-card{max-width:1100px}
			/* the options of the other design (and the parts of now or of the forecast when they are off): their flex
			   display must not show them again */
			.wiycw-gen [hidden]{display:none !important}
			.wiycw-gen{display:grid;grid-template-columns:minmax(0,1fr);gap:24px}
			@media (min-width:1100px){.wiycw-gen{grid-template-columns:minmax(0,1fr) minmax(0,520px)}.wiycw-gen-side{position:sticky;top:48px;align-self:start}}
			/* a horizontal widget needs room to be horizontal (the card, 540 px of its own): it is drawn at its full width
			   and scaled down to the column (WIYCW-help.js), so the preview stays in its place */
			.wiycw-gen-fit{position:relative;overflow:hidden}
			.wiycw-gen-stage{transform-origin:0 0}
			.wiycw-gen-group{margin:0 0 16px;padding:8px 12px 12px;border:1px solid #dcdcde;border-radius:4px}
			.wiycw-gen-group legend{padding:0 6px;font-weight:600}
			.wiycw-gen-row{display:flex;align-items:center;gap:8px 12px;flex-wrap:wrap;margin:8px 0}
			.wiycw-gen-row > label:first-child{min-width:9em}.wiycw-gen-row .regular-text{width:22em;max-width:100%}
			.wiycw-gen-check{display:inline-flex;align-items:center;gap:6px;min-width:13em;margin:4px 12px 4px 0}
			.wiycw-gen-check:has(#wiycw-gen-today){display:flex;width:100%}
			.wiycw-gen-note{margin:4px 0 8px;color:#646970;font-style:italic}
			.wiycw-gen-preview{min-height:120px;padding:16px;border-radius:4px;background:#f0f0f1}
			.wiycw-gen-preview.is-loading{opacity:.55;transition:opacity .2s}
			#wiycw-results{max-width:25em}
		</style>
	</div>
	<?php
}

/**
 * The options of the shortcode writer: every attribute of the shortcode, by the sections of the widget form. Each one:
 * attribute, label, type (check, select, text, number, color, city), its value when the page opens (start) and, for the
 * options of one design only, «only» (card or classic). Its default is the one of WIYCW_default_values (the shortcode).
 */
function WIYCW_generator_fields(){
	// the same words as the form of the widget (already translated)
	$days = array('off' => __('Disable forecast', 'weather-in-any-city-widget'),
		'1' => __('1 day', 'weather-in-any-city-widget'),
		'2' => __('2 days', 'weather-in-any-city-widget'),
		'3' => __('3 days', 'weather-in-any-city-widget'),
		'4' => __('4 days', 'weather-in-any-city-widget'),
		'5' => __('5 days', 'weather-in-any-city-widget'),
		'6' => __('6 days', 'weather-in-any-city-widget'),
		'7' => __('7 days', 'weather-in-any-city-widget'),
		'8' => __('8 days', 'weather-in-any-city-widget'),
		'9' => __('9 days', 'weather-in-any-city-widget'),
		'10' => __('10 days', 'weather-in-any-city-widget'),
		'11' => __('11 days', 'weather-in-any-city-widget'),
		'12' => __('12 days', 'weather-in-any-city-widget'),
		'13' => __('13 days', 'weather-in-any-city-widget'),
		'14' => __('14 days', 'weather-in-any-city-widget'));
	$check = function ($attribute, $label, $start, $only = null, $needs = null) {
		return array('attribute' => $attribute, 'label' => $label, 'type' => 'check', 'start' => $start, 'only' => $only, 'needs' => $needs);
	};
	return array(
		__('General', 'weather-in-any-city-widget') => array(
			array('attribute' => 'cityid', 'label' => __('City', 'weather-in-any-city-widget'), 'type' => 'city'),
			array('attribute' => 'title', 'label' => __('Title', 'weather-in-any-city-widget'), 'type' => 'text', 'start' => '', 'placeholder' => __('optional', 'weather-in-any-city-widget')),
			array('attribute' => 'units', 'label' => __('Units', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => 'c',
				'options' => array('c' => __('Celsius', 'weather-in-any-city-widget'), 'f' => __('Fahrenheit', 'weather-in-any-city-widget'))),
		),
		__('Design', 'weather-in-any-city-widget') => array(
			array('attribute' => 'design', 'label' => __('Design', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => 'card',
				'options' => array('card' => __('Sky card', 'weather-in-any-city-widget'), 'classic' => __('Classic', 'weather-in-any-city-widget'))),
			array('attribute' => 'layout', 'label' => __('Layout', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => 'vertical',
				'options' => array('vertical' => __('Vertical', 'weather-in-any-city-widget'), 'horizontal' => __('Horizontal', 'weather-in-any-city-widget'))),
			array('type' => 'note', 'label' => __('The sky card changes its sky with the weather. These options only apply to it:', 'weather-in-any-city-widget'), 'only' => 'card'),
			$check('description', __('Weather in words', 'weather-in-any-city-widget'), 'on', 'card'),
			$check('feels_like', __('Feels like', 'weather-in-any-city-widget'), 'on', 'card'),
			$check('now_visibility', __('Visibility', 'weather-in-any-city-widget'), 'off', 'card'),
			$check('hourly', __('Hourly forecast', 'weather-in-any-city-widget'), 'on', 'card'),
			$check('unit_switch', __('Let visitors switch between °C and °F', 'weather-in-any-city-widget'), 'on', 'card'),
			$check('updated', __('Time of the last update', 'weather-in-any-city-widget'), 'off', 'card'),
			$check('animation', __('Animated sky', 'weather-in-any-city-widget'), 'on', 'card'),
		),
		__('Current conditions', 'weather-in-any-city-widget') => array(
			$check('today', __('Show current conditions', 'weather-in-any-city-widget'), 'on'),
			$check('now_icon', __('Icon', 'weather-in-any-city-widget'), 'on', null, 'today'),
			$check('now_temp', __('Temperature', 'weather-in-any-city-widget'), 'on', null, 'today'),
			$check('now_humidity', __('Humidity', 'weather-in-any-city-widget'), 'on', null, 'today'),
			$check('now_wind', __('Wind', 'weather-in-any-city-widget'), 'on', null, 'today'),
			$check('now_pressure', __('Pressure', 'weather-in-any-city-widget'), 'on', null, 'today'),
			// off at first, as in the block: humidity, wind and pressure make one line of the card
			$check('now_cloudiness', __('Cloudiness', 'weather-in-any-city-widget'), 'off', null, 'today'),
			$check('now_sunrise', __('Sunrise / sunset', 'weather-in-any-city-widget'), 'off', null, 'today'),
			array('attribute' => 'time_format', 'label' => __('Time format', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => 'universal',
				'options' => array('universal' => __('24 hours', 'weather-in-any-city-widget'), 'standard' => __('12 hours', 'weather-in-any-city-widget'))),
		),
		__('Forecast', 'weather-in-any-city-widget') => array(
			array('attribute' => 'days', 'label' => __('Days', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => '5', 'options' => $days),
			$check('temp', __('Temperature', 'weather-in-any-city-widget'), 'on', null, 'days'),
			$check('icon', __('Icon', 'weather-in-any-city-widget'), 'on', null, 'days'),
			$check('rainchance', __('Rain probability', 'weather-in-any-city-widget'), 'on', null, 'days'),
			$check('rain', __('Rain', 'weather-in-any-city-widget'), 'off', null, 'days'),
			$check('wind', __('Wind', 'weather-in-any-city-widget'), 'off', null, 'days'),
		),
		__('Look and feel', 'weather-in-any-city-widget') => array(
			array('attribute' => 'fontsize', 'label' => __('Font size', 'weather-in-any-city-widget'), 'type' => 'number', 'start' => '15', 'min' => 11, 'max' => 22),
			array('attribute' => 'textcolor', 'label' => __('Font color', 'weather-in-any-city-widget'), 'type' => 'color', 'start' => '#000000', 'only' => 'classic'),
			array('attribute' => 'backgroundcolor', 'label' => __('Background color', 'weather-in-any-city-widget'), 'type' => 'color', 'start' => '#ffffff', 'only' => 'classic'),
			array('attribute' => 'bordercolor', 'label' => __('Border color', 'weather-in-any-city-widget'), 'type' => 'color', 'start' => '#ffffff', 'only' => 'classic'),
			array('attribute' => 'weathericonscolor', 'label' => __('Weather icons color', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => 'color', 'only' => 'classic',
				'options' => array('color' => __('Color', 'weather-in-any-city-widget'), 'dark' => __('Dark', 'weather-in-any-city-widget'), 'light' => __('Light', 'weather-in-any-city-widget'))),
			array('attribute' => 'iconscolor', 'label' => __('UI icons color', 'weather-in-any-city-widget'), 'type' => 'select', 'start' => 'dark', 'only' => 'classic',
				'options' => array('dark' => __('Dark', 'weather-in-any-city-widget'), 'light' => __('Light', 'weather-in-any-city-widget'))),
			$check('shadow', __('Shadow', 'weather-in-any-city-widget'), 'off', 'classic'),
			$check('credit_link', WIYCW_credit_label(), 'on'),
		),
	);
}

// The value of every attribute when the shortcode does not write it (WIYCW_default_values with nothing)
function WIYCW_generator_defaults(){
	$instance = WIYCW_default_values(array());
	$names = array('title' => 'title', 'units' => 'units', 'design' => 'design', 'layout' => 'layout', 'days' => 'days',
		'time_format' => 'time_format', 'fontsize' => 'fontSize', 'textcolor' => 'textColor', 'backgroundcolor' => 'backgroundColor',
		'bordercolor' => 'borderColor', 'weathericonscolor' => 'weatherIconsColor', 'iconscolor' => 'iconsColor', 'rainchance' => 'rainChance');
	foreach(array('description', 'feels_like', 'now_visibility', 'hourly', 'unit_switch', 'updated', 'animation', 'today', 'now_icon', 'now_temp',
		'now_humidity', 'now_wind', 'now_pressure', 'now_cloudiness', 'now_sunrise', 'temp', 'icon', 'rain', 'wind', 'shadow', 'credit_link') as $name){
		$names[$name] = $name;
	}
	$defaults = array();
	foreach($names as $attribute => $key){
		$defaults[$attribute] = isset($instance[$key]) ? (string) $instance[$key] : '';
	}
	return $defaults;
}

function WIYCW_generator_field($field){
	$only = !empty($field['only']) ? ' data-only="' . esc_attr($field['only']) . '"' : '';
	$needs = !empty($field['needs']) ? ' data-needs="' . esc_attr($field['needs']) . '"' : '';
	if($field['type'] === 'note'){
		echo '<p class="wiycw-gen-note"' . $only . '>' . esc_html($field['label']) . '</p>';
		return;
	}
	$id = 'wiycw-gen-' . $field['attribute'];
	if($field['type'] === 'check'){
		echo '<label class="wiycw-gen-check"' . $only . $needs . '><input type="checkbox" id="' . esc_attr($id) . '" data-attribute="' . esc_attr($field['attribute']) . '"'
			. checked($field['start'], 'on', false) . ' /> ' . esc_html($field['label']) . '</label>';
		return;
	}
	echo '<div class="wiycw-gen-row"' . $only . $needs . '><label for="' . esc_attr($field['type'] === 'city' ? 'wiycw-city' : $id) . '">' . esc_html($field['label']) . '</label>';
	if($field['type'] === 'city'){
		echo '<div><input type="search" id="wiycw-city" class="regular-text" autocomplete="off" />'
			. '<div id="wiycw-results" role="listbox"></div><p class="description" id="wiycw-picked">New York City, New York (United States)</p></div>';
	}else if($field['type'] === 'select'){
		echo '<select id="' . esc_attr($id) . '" data-attribute="' . esc_attr($field['attribute']) . '">';
		foreach($field['options'] as $value => $label){
			echo '<option value="' . esc_attr($value) . '"' . selected($field['start'], (string) $value, false) . '>' . esc_html($label) . '</option>';
		}
		echo '</select>';
	}else if($field['type'] === 'number'){
		echo '<input type="number" class="small-text" id="' . esc_attr($id) . '" data-attribute="' . esc_attr($field['attribute']) . '" min="' . intval($field['min']) . '" max="' . intval($field['max']) . '" value="' . esc_attr($field['start']) . '" />';
	}else{
		echo '<input type="' . ($field['type'] === 'color' ? 'color' : 'text') . '"' . ($field['type'] === 'text' ? ' class="regular-text"' : '') . ' id="' . esc_attr($id) . '" data-attribute="' . esc_attr($field['attribute']) . '" value="' . esc_attr($field['start']) . '"'
			. (!empty($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : '') . ' />';
	}
	echo '</div>';
}

// The preview of the shortcode writer: the widget of those attributes, as a page would print it
function WIYCW_generator_preview(){
	check_ajax_referer('wiycw_preview', 'nonce');
	if(!current_user_can('manage_options')){
		wp_die('', '', array('response' => 403));
	}
	$allowed = array_merge(array('cityid', 'city'), array_keys(WIYCW_generator_defaults()));
	$atts = array();
	$posted = isset($_POST['atts']) && is_array($_POST['atts']) ? wp_unslash($_POST['atts']) : array();
	foreach($posted as $name => $value){
		if(in_array($name, $allowed, true) && is_scalar($value)){
			$atts[$name] = sanitize_text_field((string) $value);
		}
	}
	echo WIYCW_shortcode($atts);
	wp_die();
}

add_action('wp_ajax_wiycw_preview', 'WIYCW_generator_preview');
