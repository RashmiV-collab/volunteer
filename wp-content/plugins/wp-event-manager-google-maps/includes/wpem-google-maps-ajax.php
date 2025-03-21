<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Event_Manager_Ajax class.
 */
class WP_Event_Manager_Google_Maps_Ajax {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.8.3
	 */
	private static $_instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.8.3
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	*/
	public function __construct() {
		add_action( 'event_manager_ajax_get_formatted_address_from_cordinates', array( $this, 'get_formatted_address_from_cordinates' ) );
	}

	/**
	* Function will provide formatted address in response
	* @since 1.8.3
	* @param 
	* @return
	*/
	function get_formatted_address_from_cordinates(){
		if ( check_ajax_referer( 'wpem-google-maps-nonce', 'security', false ) ) {
			$coords =  isset($_POST['coords']) ? $_POST['coords'] : '' ;
			$latlng = isset($coords['latitude']) ? $coords['latitude'] : '';
			$latlng .= isset($coords['longitude']) ? ','.$coords['longitude'] : '';
			
			$reverse_geocoder = google_maps_reverse_geocoder(  $latlng , false );
			if($reverse_geocoder)
				wp_send_json($reverse_geocoder);
		}
	}
}
new WP_Event_Manager_Google_Maps_Ajax();