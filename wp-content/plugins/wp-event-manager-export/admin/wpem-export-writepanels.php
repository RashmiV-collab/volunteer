<?php
/*
* This file use to cretae fields of wp event manager at admin side.
*/
if(!defined('ABSPATH')) exit; // Exit if accessed directly
class WPEM_Export_Writepanels {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		
		wp_register_script('wp-event-manager-export-admin-export', EVENT_MANAGER_EXPORT_PLUGIN_URL . '/assets/js/admin-export.min.js', array('jquery'), EVENT_MANAGER_EXPORT_VERSION, true);
		wp_localize_script('wp-event-manager-export-admin-export', 'event_manager_export_admin_export', array(
								'export_events_url'  => '?post_type=event_listing&exportAllEvents=true&fileType=csv', 
								'export_events_text' => __('Download Events CSV ', 'wp-event-manager-export'), 

								'export_organizers_url'  => '?post_type=event_organizer&exportAllOrganizers=true&fileType=csv', 
								'export_organizers_text' => __('Download Organizers CSV ', 'wp-event-manager-export'), 

								'export_venues_url'  => '?post_type=event_venue&exportAllVenues=true&fileType=csv', 
								'export_venues_text' => __('Download Venues CSV ', 'wp-event-manager-export'), 

								'export_events_xls_url'  => '?post_type=event_listing&exportAllEvents=true&fileType=xls', 
								'export_events_xls_text' => __('Download Events Xls ', 'wp-event-manager-export'), 

								'export_organizers_xls_url'  => '?post_type=event_organizer&exportAllOrganizers=true&fileType=xls', 
								'export_organizers_xls_text' => __('Download Organizers Xls ', 'wp-event-manager-export'), 

								'export_venues_xls_url'  => '?post_type=event_venue&exportAllVenues=true&fileType=xls', 
								'export_venues_xls_text' => __('Download Venues Xls ', 'wp-event-manager-export'), 

								'export_events_xlsx_url'  => '?post_type=event_listing&exportAllEvents=true&fileType=xlsx', 
								'export_events_xlsx_text' => __('Download Events Xlsx ', 'wp-event-manager-export'), 

								'export_organizers_xlsx_url'  => '?post_type=event_organizer&exportAllOrganizers=true&fileType=xlsx', 
								'export_organizers_xlsx_text' => __('Download Organizers Xlsx ', 'wp-event-manager-export'), 

								'export_venues_xlsx_url'  => '?post_type=event_venue&exportAllVenues=true&fileType=xlsx', 
								'export_venues_xlsx_text' => __('Download Venues Xlsx ', 'wp-event-manager-export'), 
								
								'export_events_xml_url'  => '?post_type=event_listing&exportAllEvents=true&fileType=xml', 
								'export_events_xml_text' => __('Download Events XML ', 'wp-event-manager-export'), 

								'export_organizers_xml_url'  => '?post_type=event_organizer&exportAllOrganizers=true&fileType=xml', 
								'export_organizers_xml_text' => __('Download Organizers XML ', 'wp-event-manager-export'), 

								'export_venues_xml_url'  => '?post_type=event_venue&exportAllVenues=true&fileType=xml', 
								'export_venues_xml_text' => __('Download Venues XML ', 'wp-event-manager-export'), 
							)							
						 );
		wp_enqueue_script('wp-event-manager-export-admin-export');
	}
}
new WPEM_Export_Writepanels();