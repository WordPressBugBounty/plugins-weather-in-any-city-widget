<?php

defined( 'WP_UNINSTALL_PLUGIN' ) or die( 'WP_UNINSTALL_PLUGIN not defined' );

function WIYCW_uninstall_site(){
	global $wpdb;
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_wiycw%' OR option_name LIKE '\_transient\_timeout\_wiycw%' OR option_name LIKE 'wiycw2\_lock\_%'");
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
