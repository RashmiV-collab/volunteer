<?php
/**
 * Call when the plugin is uninstalled.
 */

// If WP_UNINSTALL_PLUGIN not called from WordPress, then exit.
if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

// Cleanup all data.
require 'includes/wpem-alerts-data-cleaner.php';

if(!is_multisite()) {

	// Only do deletion if the setting is true.
	$do_deletion = get_option('wpem_alerts_delete_data_on_uninstall');
	if($do_deletion) {
		WPEM_Alert_Data_Clear::cleanup_all();
	}
} else {
	global $wpdb;

	$blog_ids         = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	$original_blog_id = get_current_blog_id();

	foreach ($blog_ids as $blog_id) {
		switch_to_blog($blog_id);

		// Only do deletion if the setting is true.
		$do_deletion = get_option('wpem_alerts_delete_data_on_uninstall');
		if($do_deletion) {
			WPEM_Alert_Data_Clear::cleanup_all();
		}
	}

	switch_to_blog($original_blog_id);
}

// In the entire plugin's option name's array which is used in plugin for the deletion.
$options = array(
	'event_manager_alerts_email_template',
	'wpem_alerts_email_template',
	'event_manager_alerts_auto_disable',
	'wpem_alerts_auto_disable',
	'event_manager_alerts_matches_only',
	'wpem_alerts_matches_only',
	'event_manager_alerts_page_slug',
	'wpem_alerts_page_slug',
	'event_manager_alerts_page_id',
	'wpem_alerts_page_id',
	'event_manager_alerts_delete_data_on_uninstall',
	'wpem_alerts_delete_data_on_uninstall',
	'wpem_alerts_version',
	'wp-event-manager-alerts_hide_key_notice',
	'wp-event-manager-alerts_errors',
	'wp-event-manager-alerts_licence_key',
	'wp-event-manager-alerts_email'
);
// Delete the options
foreach ($options as $option) {
	delete_option($option);
}