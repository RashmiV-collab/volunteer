<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Cleanup all data.
require 'includes/wpem-bookmarks-data-cleaner.php';

if ( ! is_multisite() ) {

	// Only do deletion if the setting is true.
	$do_deletion = get_option( 'event_manager_bookmarks_delete_data_on_uninstall' );
	if ( $do_deletion ) {
		WPEM_Bookmarks_Data_Clear::cleanup_all();
	}
} else {
	global $wpdb;

	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );

		// Only do deletion if the setting is true.
		$do_deletion = get_option( 'event_manager_bookmarks_delete_data_on_uninstall' );
		if ( $do_deletion ) {
			WPEM_Bookmarks_Data_Clear::cleanup_all();
		}
	}

	switch_to_blog( $original_blog_id );
}

$options = array(
	'event_manager_bookmarks_page_id',
	'event_manager_bookmarks_delete_data_on_uninstall',
	'wpem_bookmarks_version'
);

foreach ( $options as $option ) {
	delete_option( $option );
}