<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = array(
	'event_manager_google_maps_api_language',
	'event_manager_google_maps_api_default_region',
	'event_manager_google_maps_google_address_autocomplete_backend',
	'event_manager_google_maps_google_address_autocomplete_frontend',
	'event_manager_google_maps_autocomplete_country_restriction',
	'event_manager_google_maps_location_marker',
	'event_manager_autocomplete',
	'event_manager_radius',
	'event_manager_orderby',
	'event_manager_display_maps',
	'event_manager_maps_width',
	'event_manager_maps_height',
	'event_manager_maps_type',
	'event_manager_scroll_wheel',
	'event_manager_single_maps_use',
	'event_manager_single_maps_width',
	'event_manager_single_maps_height',
	'event_manager_single_maps_type',
	'event_manager_single_maps_scroll_wheel',
	'event_manager_single_maps_zoom',

	'wp-event-manager-google-maps_errors',
	'wp-event-manager-google-maps_licence_key',
	'wp-event-manager-google-maps_email',
	'wp-event-manager-google-maps_hide_key_notice',
	'wp-event-manager-google-maps_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}