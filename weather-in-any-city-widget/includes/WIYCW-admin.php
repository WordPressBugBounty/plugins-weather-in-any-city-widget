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
	wp_enqueue_script('WIYCW-help', WIYCW_DEF_BASEURL . '/resources/js/WIYCW-help.js', array(), WIYCW_VERSION, true);
	wp_localize_script('WIYCW-help', 'WIYCW_help', array('searchUrl' => WIYCW_API . '/wpsearch'));
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

		<div class="card" style="max-width: 760px;">
			<h2>3. <?php echo esc_html__('Shortcode', 'weather-in-any-city-widget'); ?></h2>
			<p><?php echo esc_html__('Search for a city, choose a design, then copy the shortcode and paste it into any page, post or text widget.', 'weather-in-any-city-widget'); ?></p>
			<table class="form-table" role="presentation" id="wiycw-generator">
				<tr>
					<th scope="row"><label for="wiycw-city"><?php echo esc_html__('City', 'weather-in-any-city-widget'); ?></label></th>
					<td>
						<input type="search" id="wiycw-city" class="regular-text" autocomplete="off" />
						<div id="wiycw-results" role="listbox" style="max-width: 25em;"></div>
						<p class="description" id="wiycw-picked"></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wiycw-design"><?php echo esc_html__('Design', 'weather-in-any-city-widget'); ?></label></th>
					<td>
						<select id="wiycw-design">
							<option value="card"><?php echo esc_html__('Sky card (new)', 'weather-in-any-city-widget'); ?></option>
							<option value="classic"><?php echo esc_html__('Classic', 'weather-in-any-city-widget'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wiycw-layout"><?php echo esc_html__('Layout', 'weather-in-any-city-widget'); ?></label></th>
					<td>
						<select id="wiycw-layout">
							<option value="vertical"><?php echo esc_html__('Vertical', 'weather-in-any-city-widget'); ?></option>
							<option value="horizontal"><?php echo esc_html__('Horizontal', 'weather-in-any-city-widget'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wiycw-units"><?php echo esc_html__('Units', 'weather-in-any-city-widget'); ?></label></th>
					<td>
						<select id="wiycw-units">
							<option value="c"><?php echo esc_html__('Celsius', 'weather-in-any-city-widget'); ?></option>
							<option value="f"><?php echo esc_html__('Fahrenheit', 'weather-in-any-city-widget'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wiycw-days"><?php echo esc_html__('Days', 'weather-in-any-city-widget'); ?></label></th>
					<td><input type="number" id="wiycw-days" min="0" max="14" value="5" class="small-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__('Sky card', 'weather-in-any-city-widget'); ?></th>
					<td>
						<label><input type="checkbox" id="wiycw-hourly" checked /> <?php echo esc_html__('Hourly forecast', 'weather-in-any-city-widget'); ?></label><br />
						<label><input type="checkbox" id="wiycw-switch" checked /> <?php echo esc_html__('Let visitors switch between °C and °F', 'weather-in-any-city-widget'); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wiycw-shortcode"><?php echo esc_html__('Shortcode', 'weather-in-any-city-widget'); ?></label></th>
					<td>
						<textarea id="wiycw-shortcode" class="large-text code" rows="3" readonly></textarea>
						<p><button type="button" class="button button-primary" id="wiycw-copy"><?php echo esc_html__('Copy', 'weather-in-any-city-widget'); ?></button>
						<span id="wiycw-copied" style="margin-left: 8px;" hidden><?php echo esc_html__('Copied', 'weather-in-any-city-widget'); ?></span></p>
					</td>
				</tr>
			</table>
			<p class="description"><?php echo esc_html__('Every widget option is also a shortcode attribute: the plugin page lists them all.', 'weather-in-any-city-widget'); ?></p>
		</div>
	</div>
	<?php
}
