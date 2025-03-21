<?php
/*
* This file use for setings at admin site for google analytics settings.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Event_Manager_Google_Analytics_Settings class.
 */
class WP_Event_Manager_Google_Analytics_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() 
    {		
		add_filter( 'event_manager_settings', array( $this, 'google_analytics_settings' ) );   
	}

	/**
	 * google_analytics_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function google_analytics_settings($settings) {		
		$settings[ 'google_analytics' ] = array(
					                                __( 'Google Analytics', 'wp-event-manager-google-analytics' ),
					                                array(
						                                        array(
							                                              'name'       => 'event_manager_google_analytics_tracking_code',  
							                                               'std'        => '',
							                                               'label'      => __( 'Tracking Code', 'wp-event-manager-google-analytics' ),		
							                                               'placeholder' => __( 'Please enter your google analytics tracking code, <script>..your code..</script>. It will contain tracking id, something like UA-XXXXXXXX-XX', 'wp-event-manager-google-analytics' ),
							                                               'desc'       => __( 'This google analytics tracking code will insert at footer of the every page of your site.', 'wp-event-manager-google-analytics' ),
							                                               'type'       => 'textarea',
							                                               'attributes' => array()
						                                               )
					                                        )
				                                   );
                                                   
         return $settings;	                                                          
	}
}
new WP_Event_Manager_Google_Analytics_Settings();