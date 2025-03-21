<?php
/*
* Main Admin functions class which responsible for the entire amdin functionality and scripts loaded and files.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Event_Manager_Admin class.
 */
class WPEM_Google_Maps_Admin {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ));
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_autocomplete' ));

		add_action( 'wp_ajax_check_google_api_key', array( $this, 'check_google_api_key' ) );

		add_filter( 'wp_event_manager_shortcode_plugin', array( $this, 'add_google_map_shortcode_plugin_list' ) );
		add_action( 'wp_event_manager_shortcode_list', array( $this, 'add_google_map_shortcode_list' ) );
	}
	
	/**
	 * Trigger autocomplete on the location field in backend
	 * 
	 * @since 1.0.0
	 */
	public function admin_autocomplete() {
		global $post_type;
		
		if ( $post_type != 'event_listing' || get_option('event_manager_google_maps_google_address_autocomplete_backend') == false ) 
			return;
		
		$language = get_option('event_manager_google_maps_api_language');
		$region   = get_option('event_manager_google_maps_api_default_region');
		$api_key   = get_option('event_manager_google_maps_api_key');
		
		//register google maps api
		if ( !wp_script_is( 'google-maps', 'registered' ) ) {
			wp_register_script( 'google-maps', ( is_ssl() ? 'https' : 'http' ) . '://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&language='.$language.'&region='.$region.'&key='.$api_key, array( 'jquery' ), false );
		}
		//register google maps api
		if ( !wp_script_is( 'google-maps', 'enqueued' ) ) {
			wp_enqueue_script( 'google-maps' );
		}
		
		$country= array( 'country' => get_option('event_manager_google_maps_autocomplete_country_display'));
		
		$autocomplete_options = array(
			'input_address'	=> '_event_address',
			'input_pincode'	=> '_event_pincode',
			'input_location'   => '_event_location',
			'options' 		=> $country
		);
		wp_enqueue_script( 'wp-event-manager-google-maps-autocomplete-backend', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_URL .'/assets/js/google-maps-autocomplete.min.js', array( 'jquery' ), EVENT_MANAGER_GOOGLE_MAPS_VERSION, true );
		wp_localize_script( 'wp-event-manager-google-maps-autocomplete-backend', 'AutoCompOptionsLocation', $autocomplete_options );
	}

	/**
	 * admin_enqueue_scripts
	 * 
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts() {
		wp_register_script( 'wp-event-manager-google-admin-google-map', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_URL .'/assets/js/admin-google-map.min.js', array( 'jquery' ), EVENT_MANAGER_GOOGLE_MAPS_VERSION, true );
		wp_localize_script( 'wp-event-manager-google-admin-google-map', 'event_manager_google_map_admin_google_map', array( 
								'ajax_url' 	 => admin_url( 'admin-ajax.php' ),
								'event_manager_google_map_security'  => wp_create_nonce( '_nonce_event_manager_google_map_security' ),
							)
						);
	}

	/**
	 * add_google_map_shortcode_plugin_list function.
	 *
	 * @access public
	 * @return array
	 * @since 1.8.7
	 */
	public function add_google_map_shortcode_plugin_list($shortcode_plugins) {
		$shortcode_plugins['wp-event-manager-google-maps'] =  __('WP Event Manager Google Map', 'wp-event-manager-google-maps');
		return $shortcode_plugins;
	}

	/**
	 * add_google_map_shortcode_list function.
	 *
	 * @access public
	 * @return void
	 * @since 1.8.7
	 */
	public function add_google_map_shortcode_list($detail_link) { ?>
		<tr class="shortcode_list wp-event-manager-google-maps">
			<td class="wpem-shortcode-td">[events_map]</td>
			<td><?php _e('Events Map', 'wp-event-manager-google-maps');?></td>
			<td><?php _e('This will be used to display all events location on single map', 'wp-event-manager-google-maps');?></td>
			<td><a class="button add-field" href="<?php echo $detail_link.'google-maps/#articleTOC_5';?>" target="_blank"><?php _e('View Details', 'wp-event-manager-google-maps');?></a></td>
		</tr>
		<tr class="shortcode_list wp-event-manager-google-maps">
			<td class="wpem-shortcode-td">[single_event_location_map event_id='event_id']</td>
			<td><?php _e('Single Event Location on Map', 'wp-event-manager-google-maps');?></td>
			<td><?php _e('This is used when you want to show particular event location in google map.', 'wp-event-manager-google-maps');?></td>
			<td><a class="button add-field" href="<?php echo $detail_link.'google-maps/#articleTOC_6';?>" target="_blank"><?php _e('View Details', 'wp-event-manager-google-maps');?></a></td>
		</tr>
	<?php
	}

	/**
	 * check_zoom_connection function.
	 *
	 * @access public
	 * @param 
	 * @return 
	 * @since 1.0
	 */
	public function check_google_api_key() {
		check_ajax_referer( '_nonce_event_manager_google_map_security', 'security' );

		$response = [];

		$address = "WP Event Manager, Varachha Main Road, Ramdarshan Society, Varachha, Surat, Gujarat, India";
		$geocoder  = google_maps_geocoder($address);
		if( isset($geocoder['lat']) && isset($geocoder['lng']) ){
			$response['geocoder'] = array( 'message' => __( 'Google Geocoder API enabled correctly.', 'wp-event-manager-google-maps' ), 'code' => '200' );
		}else{
			$response['geocoder'] = array( 'message' => 'Google Geocoder API: '.$geocoder['error'], 'code' => '404' );
		}

		$places  = google_maps_places($address);

		if( isset($places['error']) && !empty($places['error']) ){			
			$response['places'] = array( 'message' => 'Google Places API: '.$places['error'], 'code' => '404' );
		} else{
			$response['places'] = array( 'message' => __( 'Google Places API enabled correctly.', 'wp-event-manager-google-maps' ), 'code' => '200' );
		}

		wp_send_json( $response );
		wp_die();
	}
}
new WPEM_Google_Maps_Admin();