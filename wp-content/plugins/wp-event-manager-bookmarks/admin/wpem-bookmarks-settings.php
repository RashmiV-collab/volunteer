<?php
/*
* This file use for setings at admin site for event bookmarks settings.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WPEM_Bookmarks_Settings class.
 */
class WPEM_Bookmarks_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct(){		
			add_filter( 'event_manager_settings', array( $this, 'wp_event_bookmarks_settings' ) );
			add_filter( 'event_manager_google_recaptcha_settings', array($this, 'google_recaptcha_settings') );
	}
	
	/**
	 * wp_event_bookmarks_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function wp_event_bookmarks_settings($settings){
		$settings['event_bookmarks'] = array(
			__( 'Event Bookmarks', 'wp-event-manager-bookmarks' ),
			apply_filters(
				'wp_event_manager_bookmarks_settings',
				array(
					array(
						'name' 		=> 'event_manager_bookmarks_page_id',
						'std' 		=> '',
						'label' 	=> __( 'Bookmarks Page ID', 'wp-event-manager-bookmarks' ),
						'desc'		=> __( 'So that the plugin knows where to link users to view their bookmarks, you must select the page where you have placed the [event_manager_my_bookmarks] shortcode.', 'wp-event-manager-bookmarks' ),
						'type'      => 'page'
					),
					array(
						'name' 		=> 'event_manager_bookmarks_delete_data_on_uninstall',
						'std' 		=> '0',
						'label' 	=> __( 'Delete Data On Uninstall', 'wp-event-manager-bookmarks' ),
						'cb_label' 	=> __( 'Delete WP Event Manager Alert data when the plugin is deleted. Once removed, this data cannot be restored.', 'wp-event-manager-bookmarks' ),
						'desc'		=> '',
						'type'      => 'checkbox'
					),
				)
			)
		);
		return $settings;
	} 
	public function google_recaptcha_settings($settings) {
		$settings[1][] = array(
                    'name'       => 'enable_event_manager_google_recaptcha_bookmark_form',
                    'std'        => '1',
                    'label'      => __( 'Enable reCAPTCHA for Bookmarks Form', 'wp-event-manager-bookmarks' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA for Bookmarks Form.', 'wp-event-manager-bookmarks' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                );
		return $settings;
	}
}
new WPEM_Bookmarks_Settings();