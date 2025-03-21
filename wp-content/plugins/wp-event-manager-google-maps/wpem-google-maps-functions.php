<?php
/**
 *Google Maps function - Geocode address
 * @return $location
 * @since 1.0
 */
function google_maps_geocoder( $address, $force_refresh = false ) {
	
	$address_hash = md5( $address );

	$coordinates = get_transient( $address_hash );
	if ( $force_refresh || $coordinates === false ) {
		$api_key = get_option('event_manager_google_maps_api_key');
		$args    = array( 'address' => urlencode( $address ), 'sensor' => 'false' , 'key' => $api_key);
		
		$language  = get_option('event_manager_google_maps_api_language');
		$args['language'] = $language ;
				
		$url      = add_query_arg( $args, 'https://maps.googleapis.com/maps/api/geocode/json' );
		$response = wp_remote_get( $url );

		if( is_wp_error( $response ) )
			return;

		$data = wp_remote_retrieve_body( $response );

		if( is_wp_error( $data ) )
			return;

		if ( $response['response']['code'] == 200 ) {
			$data = json_decode( $data );
			if ( $data->status === 'OK' ) {

				$coordinates = $data->results[0]->geometry->location;

				$location['street']        = false;
				$location['apt']           = false;
				$location['city']          = false;
				$location['state_short']   = false;
				$location['state_long']    = false;
				$location['zipcode']       = false;
				$location['country_short'] = false;
				$location['country_long']  = false;
				$location['lat']               = $coordinates->lat;
				$location['lng']               = $coordinates->lng;
				$location['formatted_address'] = (string) $data->results[0]->formatted_address;

				$address_componenets = $data->results[0]->address_components;
				foreach ($address_componenets as $ac) :

					if ($ac->types[0] == 'street_number') :
						$street_number = esc_attr($ac->long_name);
					endif;

					if ($ac->types[0] == 'route') :
						$street_f = esc_attr($ac->long_name);
						if (isset($street_number) && !empty($street_number))
							$location['street'] = $street_number . ' ' . $street_f;
						else
							$location['street'] = $street_f;
					endif;

					if ($ac->types[0] == 'subpremise')
						$location['apt'] = esc_attr($ac->long_name);

					if ($ac->types[0] == 'locality')
						$location['city'] = esc_attr($ac->long_name);

					if ($ac->types[0] == 'administrative_area_level_1') :
						$location['state_short'] = esc_attr($ac->short_name);
						$location['state_long']  = esc_attr($ac->long_name);
					endif;

					if ($ac->types[0] == 'postal_code')
						$location['zipcode'] = esc_attr($ac->long_name);
						
					if ($ac->types[0] == 'country') :
						$location['country_short'] = esc_attr($ac->short_name);
						$location['country_long']  = esc_attr($ac->long_name);
					endif;

				endforeach;

				do_action( 'google_map_geocoded_location', $location );

			} elseif ( $data->status === 'ZERO_RESULTS' ) {
				return array( 'error' => __( 'No location found for the entered address.', 'wp-event-manager-google-maps' ) );
			} elseif ( $data->status === 'REQUEST_DENIED' ) {
				return array( 'error' => __( 'This API project is not authorized to use this API.', 'wp-event-manager-google-maps' ) );
			} elseif( $data->status === 'INVALID_REQUEST' ) {
				return array( 'error' => __( 'Invalid request. Did you enter an address?', 'wp-event-manager-google-maps' ) );
			} elseif ( $data->status === 'OVER_QUERY_LIMIT' ) { 
    			return array( 'error' => __( 'Something went wrong while retrieving your location.', 'wp-event-manager-google-maps' ) . '<span style="display:none">OVER_QUERY_LIMIT</span>' );
    		} else {
				return array( 'error' => __( 'Something went wrong while retrieving your location.', 'wp-event-manager-google-maps' ) );
			}
		} else {
			return array( 'error' => __( 'Unable to contact Google API service.', 'wp-event-manager-google-maps' ) );
		}

	} else {
		// return cached results
		$location = $coordinates;
	}
	return $location;
}


/**
 *Google Maps function - Geocode address
 * @return $location
 * @since 1.0
 */
function google_maps_reverse_geocoder( $latlng, $force_refresh=false ) {
	$api_key = get_option('event_manager_google_maps_api_key');
	$language  = get_option('event_manager_google_maps_api_language');
	$args       = array( 'latlng' =>  $latlng , 'key' => $api_key, 'language' => $language);

	$url        = add_query_arg( $args, 'https://maps.googleapis.com/maps/api/geocode/json' );
	$response 	= wp_remote_get( $url );

	$data = wp_remote_retrieve_body( $response );

	$location = array();

	if( is_wp_error( $response ) )
			return $response;

	if ( $response['response']['code'] == 200 ) {
		$data = json_decode( $data );

		if ( $data->status === 'OK' ) {
			$location['formatted_address'] = (string) $data->results[0]->formatted_address;
		} elseif ( $data->status === 'ZERO_RESULTS' ) {
			return array( 'error' => __( 'No location found for the entered address.', 'wp-event-manager-google-maps' ) );
		} elseif ( $data->status === 'REQUEST_DENIED' ) {
			return array( 'error' => __( 'This API project is not authorized to use this API.', 'wp-event-manager-google-maps' ) );
		} elseif( $data->status === 'INVALID_REQUEST' ) {
			return array( 'error' => __( 'Invalid request. Did you enter an address?', 'wp-event-manager-google-maps' ) );
		} elseif ( $data->status === 'OVER_QUERY_LIMIT' ) { 
			return array( 'error' => __( 'Something went wrong while retrieving your location.', 'wp-event-manager-google-maps' ) . '<span style="display:none">OVER_QUERY_LIMIT</span>' );
		} else {
			return array( 'error' => __( 'Something went wrong while retrieving your location.', 'wp-event-manager-google-maps' ) );
		}
	} else {
		return array( 'error' => __( 'Unable to contact Google API service.', 'wp-event-manager-google-maps' ) );
	}
	return $location;

}

/**
 *Google Maps function - Places
 * @return $places
 * @since 1.8.2
 */
function google_maps_places( $address, $force_refresh=false ) {
	$api_key = get_option('event_manager_google_maps_api_key');
	$args       = array( 'input' => urlencode( $address ), 'key' => $api_key);
	
	$language  = get_option('event_manager_google_maps_api_language');
	$args['language'] = $language ;
			
	$url        = add_query_arg( $args, 'https://maps.googleapis.com/maps/api/place/autocomplete/json' );
	$response 	= wp_remote_get( $url );

	if( is_wp_error( $response ) )
			return;

	$data = wp_remote_retrieve_body( $response );

	if( is_wp_error( $data ) )
		return;

	if ( $response['response']['code'] == 200 )  {
		$data = json_decode( $data );

		if ( $data->status === 'OK' ) {
			return $data->predictions;
		}elseif ( $data->status === 'ZERO_RESULTS' ) {
			return array( 'error' => __( 'No location found for the entered address.', 'wp-event-manager-google-maps' ) );
		} elseif ( $data->status === 'REQUEST_DENIED' ) {
			return array( 'error' => __( 'This API project is not authorized to use this API.', 'wp-event-manager-google-maps' ) );
		} elseif( $data->status === 'INVALID_REQUEST' ) {
			return array( 'error' => __( 'Invalid request. Did you enter an address?', 'wp-event-manager-google-maps' ) );
		} elseif ( $data->status === 'OVER_QUERY_LIMIT' ) { 
			return array( 'error' => __( 'Something went wrong while retrieving your location.', 'wp-event-manager-google-maps' ) . '<span style="display:none">OVER_QUERY_LIMIT</span>' );
		} else {
			return array( 'error' => __( 'Something went wrong while retrieving your location.', 'wp-event-manager-google-maps' ) );
		}
	} else {
		return array( 'error' => __( 'Unable to contact Google API service.', 'wp-event-manager-google-maps' ) );
	}
}

/**
 * Get Location Data from Google for selected region
 *
 * @param string $raw_address
 * @return array location data
 * @since 1.8.5
 */
function get_selected_region_location_data( $raw_address ) {
	
	//get default region get from settings
	$default_region = get_option('event_manager_google_maps_api_default_region', true);
	$invalid_chars = array( " " => "+", "?" => "", "&" => "", "=" => "" , "#" => "" );
	$raw_address   = trim( strtolower( str_replace( array_keys( $invalid_chars ), array_values( $invalid_chars ), $raw_address ) ) );
	$address = array();
	if ( empty( $raw_address ) ) {
		return false;
	}

	$transient_name              = 'em_geocode_' . md5( $raw_address );
	$geocoded_address            = get_transient( $transient_name );
	$em_geocode_over_query_limit = get_transient( 'em_geocode_over_query_limit' );

	// Query limit reached - don't geocode for a while
	if ( $em_geocode_over_query_limit && false === $geocoded_address ) {
		return false;
	}

	try {
		$result = wp_remote_get(
			apply_filters( 'event_manager_geolocation_endpoint', WP_Event_Manager_Geocode::GOOGLE_MAPS_GEOCODE_API_URL."?address=" . $raw_address . "&sensor=false&region=" . apply_filters( 'event_manager_geolocation_region_cctld', '', $raw_address ), $raw_address ), 
			array(
				'timeout'     => 5, 
				'redirection' => 1, 
				'httpversion' => '1.1', 
				'user-agent'  => 'WordPress/WP-Event-Manager-' . EVENT_MANAGER_VERSION . '; ' . get_bloginfo( 'url' ), 
				'sslverify'   => false
		));
		
		$result           = wp_remote_retrieve_body( $result );
		$geocoded_address = json_decode( $result );
		//check google qeocode api response
		if ( $geocoded_address->status ) {
			switch ( $geocoded_address->status ) {
				case 'ZERO_RESULTS' :
					throw new Exception( __( "No results found", 'wp-event-manager-google-maps' ) );
					break;
				case 'OVER_QUERY_LIMIT' :
					set_transient( 'em_geocode_over_query_limit', 1, HOUR_IN_SECONDS );
					throw new Exception( __( "Query limit reached", 'wp-event-manager-google-maps') );
					break;
				case 'REQUEST_DENIED' :
					throw new Exception( __( "Request denied from google map api key please enable geolocation and gecoding api", 'wp-event-manager-google-maps' ) );
					break;
				case 'OK' :
					//check for the array element result if address is found
					if ( ! empty( $geocoded_address->results[0] ) ) {
						//get location array
						$location_info = $geocoded_address->results[0]->address_components;

						$check_address = false;
						//verify that event address is inside selected region or not
						if(isset($default_region) && !empty($default_region)){
							if(isset($location_info) && !empty($location_info)){
								if (array_search($default_region, array_column($location_info, 'short_name')) !== FALSE) {
									$check_address = true;
								}else{
									$check_address = false;
								}
							}else{
								$check_address = false;
							}
						}else{
							$check_address = true;
						}

						//add address into array only if address inside selected region
						if ($check_address == true) {
						
							set_transient( $transient_name, $geocoded_address, 24 * HOUR_IN_SECONDS * 365 );

							$address['lat']               = sanitize_text_field( $geocoded_address->results[0]->geometry->location->lat );
							$address['long']              = sanitize_text_field( $geocoded_address->results[0]->geometry->location->lng );
							$address['formatted_address'] = sanitize_text_field( $geocoded_address->results[0]->formatted_address );
							
							if ( !empty( $geocoded_address->results[0]->address_components ) ) {
								
								$address_data             = $geocoded_address->results[0]->address_components;
								$address['street_number'] = false;
								$address['street']        = false;
								$address['city']          = false;
								$address['state_short']   = false;
								$address['state_long']    = false;
								$address['postcode']      = false;
								$address['country_short'] = false;
								$address['country_long']  = false;
								
								foreach ( $address_data as $data ) {
									
									switch ( $data->types[0] ) {
										case 'street_number' :
											$address['street_number'] = sanitize_text_field( $data->long_name );
											break;
										case 'route' :
											$address['street']        = sanitize_text_field( $data->long_name );
											break;
										case 'sublocality_level_1' :
										case 'locality' :
										case 'postal_town' :
											$address['city']          = sanitize_text_field( $data->long_name );
											break;
										case 'administrative_area_level_1' :
										case 'administrative_area_level_2' :
											$address['state_short']   = sanitize_text_field( $data->short_name );
											$address['state_long']    = sanitize_text_field( $data->long_name );
											break;
										case 'postal_code' :
											$address['postcode']      = sanitize_text_field( $data->long_name );
											break;
										case 'country' :
											$address['country_short'] = sanitize_text_field( $data->short_name );
											$address['country_long']  = sanitize_text_field( $data->long_name );
											break;
									}
								}
							}
						}else {
							break;
						}
					} else {
						throw new Exception( __( "Geocoding error", 'wp-event-manager-google-maps' ) );
					}
					break;
				default :
					throw new Exception( __( "Geocoding error", 'wp-event-manager-google-maps') );
					break;
			}
		} else {
			throw new Exception( __( "Geocoding error", 'wp-event-manager-google-maps' ) );
		}
	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}
	return apply_filters( 'event_manager_geolocation_get_selected_region_location_data', $address, $geocoded_address );
}