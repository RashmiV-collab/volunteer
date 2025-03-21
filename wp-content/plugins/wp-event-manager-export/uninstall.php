<?php
/**
 * Call when the plugin is uninstalled.
 */
// If WP_UNINSTALL_PLUGIN not called from WordPress, then exit.
if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

// In the entire plugin's option name's array which is used in plugin for the deletion.
$options = array(
	'wp-event-manager-export_hide_key_notice',
	'wp-event-manager-export_errors',
	'wp-event-manager-export_licence_key',
	'wp-event-manager-export_email'
);

// Delete the options
foreach ($options as $option) {
	delete_option($option);
}