<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'emwcpl_user_packages' );

$options = array(		
	'event_manager_paid_listings_flow',
	'enable_event_category_for_event_manager_paid_listings',	
	'enable_event_type_for_event_manager_paid_listings',		
);

foreach ( $options as $option ) {	
	delete_option( $option );	
}