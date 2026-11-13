<?php
/**
 * Weather Widget Pro: when the plugin is deleted, the weather it had saved goes with it. The settings of its widgets
 * stay: deleting the plugin and installing it again is a common way of fixing things, and it must not lose them.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) or die( 'WP_UNINSTALL_PLUGIN not defined' );

function WIYCW_uninstall_site(){
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_wiycw%' OR option_name LIKE '\_transient\_timeout\_wiycw%'");
}

if(is_multisite()){
	$WIYCW_sites = get_sites(array('fields' => 'ids', 'number' => 0));
	foreach($WIYCW_sites as $WIYCW_site){
		switch_to_blog($WIYCW_site);
		WIYCW_uninstall_site();
		restore_current_blog();
	}
}else{
	WIYCW_uninstall_site();
}
