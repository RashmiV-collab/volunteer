<?php
/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPEM_VOLUNTEER_FluentForm_Custom {
	/**
	 * Instance
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @static
	 *
	 * The single instance of the class.
	 */
	private static $_instance = null;
	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @static
	 *
	 * An instance of the class.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		add_action('init',array($this,'volunteer_cleanup__init'));
		// event create
       // add_filter('fluentform/rendering_field_data_select', array($this,'volunteer_rendering_field_data__event_category'),10,2);
		//add_shortcode('volunteer_event_organizer',array($this,'get_volunteer_event_organizer'));
		//add_action('fluentform/notify_on_form_submit', [$this,'volunteer_event_listing_on_form_submit'],10,3);
		//add_filter( 'wp_footer', [$this,'volunteer_remove_fluentform_footer_credit']);

		// user & alert create
		add_filter('fluentform/submission_confirmation', [$this,'volunteer_alert_notify_redirect_url'],10,3);
		add_action('fluentform/notify_on_form_submit', [$this,'volunteer_alert_creation_on_form_submit'],10,3);
		add_action( 'template_redirect',[$this,'volunteer_restrict_page_loggedIn'] );
		add_filter('fluentform/validate_input_item_input_email', [$this,'volunteer_validate_input_item_input_email'] ,10,5);
		add_filter('fluentform/editor_shortcodes', [$this,'volunteer_editor_shortcodes'] ,10,1);
		add_filter('fluentform/editor_shortcode_callback_vol_session_user_email',  [$this,'volunteer_editor_shortcode_callback_vol_session_user_email'] ,10,2);

		// alert create at the time of registration
		add_action('fluentform/user_registration_completed', [$this,'volunteer_alert_create_registration'],10,4);

		// on checkoutpage
		add_action('fluentform/before_insert_submission',  [$this,'volunteer_custom_before_submission_function'], 10, 3);
		//add_filter('fluentform/form_settings_ajax',  [$this,'volunteer_form_settings_ajax'], 20, 2);
		// no filter works, that why taken get_option filter
		//add_filter( "option__fluentform_double_optin_settings", [$this,'volunteer_form_settings_ajax'], 10, 2 );
    } 

	function volunteer_cleanup__init(){
		if ( (session_status() === PHP_SESSION_NONE)) {
			session_start();
		}
	}

    function volunteer_rendering_field_data__event_category($data, $form){
        if($data['attributes'] ['name'] == '_event_category'){
            $args = array(
                'taxonomy' => 'event_listing_category',
                'orderby' => 'name',
                'order'   => 'ASC',
				'hide_empty' => false,
            );
            $i = 0;
            $cats = get_terms($args);
            $advanced_options = array();
            if($cats){
                foreach($cats as $cat) {
                    $arr = array();
                    $arr = array(
                        'label'  => $cat->name,
                        'value' => $cat->term_id,
                        'calc_value' => '', 
                        'id' => $i
                    );
                    $i++;
                    $advanced_options[] = $arr;
                }
            }
            $data['settings'] ['advanced_options'] = $advanced_options;
       }
        return $data;
    }

	function get_volunteer_event_organizer(){
		$user_logged = is_user_logged_in();
		if($user_logged){
			$current_user = wp_get_current_user();
			$organizer_value = $current_user->display_name;
			$organizer_email =  $current_user->user_email;
			$html = '<h3> Organizer Details </h3>
			<div>'.$organizer_value.'</div>
			<input type="hidden" name="_organizer_email" value="'.$organizer_email.'" />';
		}
		return $html;
	}

	function volunteer_event_listing_on_form_submit($insertId, $formData, $form){
		$user_logged = is_user_logged_in();
		if(isset($formData['event_title']) && !empty($formData['event_title']) && isset($formData['_event_description']) && !empty($formData['_event_description']) && isset($formData['_event_category']) && !empty($formData['_event_category']) && isset($formData['__post_id']) && ($formData['__post_id'] == 0) && $user_logged ){
			$current_user = wp_get_current_user();
			// Create post object
			$my_post = array(
				'post_title'    => wp_strip_all_tags( $formData['event_title'] ),
				'post_content'  => $formData['_event_description'],
				'post_status'   => 'publish',
				'post_author'   => $current_user->ID,
				'post_type' => 'event_listing'
				);
				
				// Insert the post into the database
			$event_post_id = wp_insert_post( $my_post );
			if($event_post_id){
				//category 
				wp_set_post_terms( $event_post_id, $formData['_event_category'], 'event_listing_category');

				// thumbnail
				if(isset($formData['featured_image']) && !empty($formData['featured_image'])){

					$profile = $formData['featured_image'][0];
					$profile_location = '';
					$upload_dir       = wp_upload_dir(); // Set upload folder
					if(isset($upload_dir['basedir']) && isset($upload_dir['baseurl'])){
						//
						$profile_location = str_replace($upload_dir['baseurl'],$upload_dir['basedir'],$profile);
						// Add Featured Image to Post
						$parts = explode('/', $profile_location);
						$image_name = array_pop($parts);
						$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
						$filename         = basename( $unique_file_name ); // Create image file name

						// Check folder permission and define file location
						if( wp_mkdir_p( $upload_dir['path'] ) ) {
						$file = $upload_dir['path'] . '/' . $filename;
						} else {
						$file = $upload_dir['basedir'] . '/' . $filename;
						}

						copy($profile_location,$file);

						// Check image file type
						$wp_filetype = wp_check_filetype( $filename, null );

						// Set attachment data
						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => sanitize_file_name( $filename ),
							'post_content'   => '',
							'post_status'    => 'inherit'
						);

						// Create the attachment
						$attach_id = wp_insert_attachment( $attachment, $file, $id );
						if($attach_id){
							// Include image.php
							require_once(ABSPATH . 'wp-admin/includes/image.php');

							// Define attachment metadata
							$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
							
							// Assign metadata to attachment
							wp_update_attachment_metadata( $attach_id, $attach_data );
							
							// And finally assign featured image to post
							set_post_thumbnail($event_post_id, $attach_id );
						}
					}
				}
				// thumbnail finishes
				// meta 
				$meta_arr = array('_event_start_date','_event_start_time','_event_end_date','_event_end_time','_meeting_spot_details','_parking_info','_attendee_information_type');
				foreach($meta_arr as $meta){
					if(isset($formData[$meta])){
						update_post_meta( $event_post_id,$meta,$formData[$meta]);
					}
				}
				// meta - finishes
				// address 
				if(isset($formData['event_address']) && !empty($formData['event_address'])){
					$location = $formData['event_address']['zip'];
					$lat_lng = google_maps_geocoder($location); 
					if(is_array($lat_lng) && isset($lat_lng['lat']) && isset($lat_lng['lng'])){
						foreach($lat_lng as $key=>$value){
							update_post_meta($event_post_id,'geolocation_'.$key,$value);
						}
					}
					update_post_meta($event_post_id,'_event_location',$formData['event_address']['address_line_1']);
					update_post_meta($event_post_id,'_event_country',strtolower($formData['event_address']['country']));
					update_post_meta($event_post_id,'_event_pincode',$formData['event_address']['zip']);
				}
				// address - finishes
				$user_id = $current_user->ID;
				$user_email = '';
				$user_role = get_user_meta($user_id,'wp_capabilities',true);

				// additional user role of Organizer within WordPress
				if(is_array($user_role) && !array_key_exists('organizer',$user_role)){
					$user_role['organizer'] = 1;
					update_user_meta($user_id,'wp_capabilities',$user_role);
				}
			
				// added as Organizer under WP Event Manager
				
				$user = get_user_by( 'id', $user_id );
				$user_email =  $user->user_email;
				$user_login =  $user->user_login;
				$post_arr = array(
					'post_status'   => 'publish',
					'author'   => $user_id,
					'post_type' => 'event_organizer',
					'meta_key' => '_organizer_email',
					'meta_value' => $user_email,
				);
				$organizer_post = get_posts($post_arr);
				if(is_array($organizer_post) && isset($organizer_post[0])){
					$arr = array($organizer_post[0]->ID);
					update_post_meta( $event_post_id,'_event_organizer_ids',$arr);
				//	$_POST['_event_organizer_ids'] = $arr;
				}else{
					$user_nickname = get_user_meta($user_id,'nickname',true); 
					if(filter_var($user_nickname , FILTER_VALIDATE_EMAIL)) {
						$user_info = new WP_User( $user_id );
						if($user_info->last_name){
							$user_nickname = $user_info->first_name . ' ' . $user_info->last_name;
						}elseif($user_info->first_name){
							$user_nickname = $user_info->first_name;
						}
					}
					// Create post object
					$my_post = array(
						'post_title'    => wp_strip_all_tags( $user_nickname ),
						'post_status'   => 'publish',
						'post_author'   => $user_id,
						'post_type' => 'event_organizer',
					);
					
					// Insert the post into the database
					$organizer_post_id = wp_insert_post( $my_post );
					if($organizer_post_id){
						update_post_meta( $organizer_post_id, '_organizer_email', $user_email );
						update_post_meta( $organizer_post_id, '_organizer_name', wp_strip_all_tags(  $user_nickname ) );
						$arr = array($organizer_post_id);
						update_post_meta( $event_post_id,'_event_organizer_ids',$arr);
					//	$_POST['_event_organizer_ids'] = $arr;
					}
				}
				// added as Organizer under WP Event Manager - finishes
			
				// Registration Email and Organizer Email pre-filled
				if(! get_post_meta($event_post_id,'_event_registration_email',true) || (get_post_meta($event_post_id,'_event_registration_email',true) && get_post_meta($event_post_id,'_event_registration_email',true) != $user_email)){
					update_post_meta($event_post_id,'_event_registration_email',$user_email);
					//$_POST['_event_registration_email'] = $user_email;
				}
				if(! get_post_meta($event_post_id,'_registration',true) || (get_post_meta($event_post_id,'_registration',true) && get_post_meta($event_post_id,'_registration',true)!= $user_email)){
					update_post_meta($event_post_id,'_registration',$user_email);
					//$_POST['_registration'] = $user_email;
				}
				// event expiry date
				$end_date = get_post_meta($event_post_id,'_event_end_date',true);
				if($end_date){
					$expiry_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
					if(strtotime(get_post_meta($event_post_id,'_event_expiry_date',true)) != strtotime($expiry_date)){
						update_post_meta($event_post_id,'_event_expiry_date',$expiry_date);
					//	$_POST['_event_expiry_date'] = $expiry_date;
					}
				}

				// Online Event
				update_post_meta($event_post_id,'_event_online','no');

				// Attende
				$arr = array('attendee_name','attendee_email','attendee_phone');
				update_post_meta($event_post_id,'_attendee_information_fields',$arr);

				//flyer
				if(isset($formData['_flyer']) && !empty($formData['_flyer'])){
					$_flyer = $formData['_flyer'][0];
					update_post_meta($event_post_id,'_flyer',$_flyer);
				}

				//meta 
				if(isset($formData['_what_should_volunteers_bring']) && !empty($formData['_what_should_volunteers_bring'])){
					update_post_meta($event_post_id,'_what_should_volunteers_bring?',$formData['_what_should_volunteers_bring']);
				}
				if(isset($formData['_what_will_be_provided']) && !empty($formData['_what_will_be_provided'])){
					update_post_meta($event_post_id,'_what_will_be_provided?',$formData['_what_will_be_provided']);
				}

				update_post_meta($event_post_id,'fluent_form_insertId',$insertId);
			}
			// event created
		}
		// new event 
	}

	function volunteer_remove_fluentform_footer_credit( ) {
		// Return an empty string to remove the footer credit
		?>
		<style>
			.frm-fluent-form.ff_conv_app_frame .vff-footer{
				display:none;
			}
			</style>
		<?php
	}


	function get_username($user_name){
		if(username_exists( $user_name )){
			$six_digit_random_number = random_int(100000, 999999);
			$user_name .= $six_digit_random_number;
			$this->get_username($user_name);
		}
		return $user_name;
	}

	/**
	 * redirection
	 * 1. form homepage alert 
	 * 2. form  thankyou page alert 
	 * 
	 */
	function volunteer_alert_notify_redirect_url($returnData,$form,$confirmation){
		$current_user = is_user_logged_in();
		$url = get_site_url();
		$volunteer_settings = get_option('volunteer_custom_settings');
		//print_R($confirmation);exit;
		// ----------------
		// 1. homepage alert and thank you page
		//print_r($confirmation);exit;
		if($current_user  && is_array($volunteer_settings) && isset($volunteer_settings['alert_page']) && !empty($volunteer_settings['alert_page']) && isset($confirmation['redirectTo']) && $confirmation['redirectTo'] == 'customUrl' & isset($confirmation['redirectTo']) && $confirmation['customUrl'] == '{volunteer-alert-page}'){
			//
			// logged-in user
            $url = get_permalink(trim($volunteer_settings['alert_page']));
			$returnData['redirectUrl'] = $url;
		}elseif(isset($confirmation['redirectTo']) && $confirmation['redirectTo'] == 'customUrl' & isset($confirmation['redirectTo']) && $confirmation['customUrl'] == '{volunteer-alert-page}'){
			$returnData['redirectUrl'] = get_site_url();
		}
		

		// ----------------
		// 2. redirect user verification page to register page on behalf of referred page
		//var_dump($_SESSION);exit;
		if($_SESSION  &&  is_array($volunteer_settings) && isset($_SESSION['volunteer_account_verification_email']) && $_SESSION['volunteer_account_verification_email'] == 'valid' && isset($_SESSION['volunteer_account_verification_user_email']) && $confirmation['redirectTo'] == 'customUrl' & isset($confirmation['redirectTo']) && $confirmation['customUrl'] == '{redirect-volunteer-plugin-settings}'){
			//
			if(isset($_SESSION['referred_account_verification_page']) && is_array($volunteer_settings) && isset($volunteer_settings['homepage_user_verification_page']) && ($volunteer_settings['homepage_user_verification_page']) == $_SESSION['referred_account_verification_page']){

				// From Homepage for valid user
				$url = get_permalink(trim($volunteer_settings['homepage_alert_creation_page']));
				$returnData['redirectUrl'] = $url;

			}elseif(isset($_SESSION['referred_account_verification_page']) && is_array($volunteer_settings) && isset($volunteer_settings['thankyou_user_verification_page']) && ($volunteer_settings['thankyou_user_verification_page']) == $_SESSION['referred_account_verification_page']){

				// From thankyou for valid user	
				$url = get_permalink(trim($volunteer_settings['thankyou_alert_creation_page']));
				$returnData['redirectUrl'] = $url;

			}elseif(isset($_SESSION['referred_account_verification_page']) && is_array($volunteer_settings) && isset($volunteer_settings['create_account_user_verification_page']) && ($volunteer_settings['create_account_user_verification_page']) == $_SESSION['referred_account_verification_page']){

				// From thankyou for valid user	
				$url = get_permalink(trim($volunteer_settings['create_account_registration_page']));
				$returnData['redirectUrl'] = $url;

			}else{
				$returnData['redirectUrl'] = get_site_url();
			}
            
		}elseif($_SESSION  &&  is_array($volunteer_settings) && isset($_SESSION['volunteer_account_verification_email']) && $_SESSION['volunteer_account_verification_email'] == 'invalid' && $confirmation['redirectTo'] == 'customUrl' & isset($confirmation['redirectTo']) && $confirmation['customUrl'] == '{redirect-volunteer-plugin-settings}'){
			//
			// For invalid user

			if(is_array($volunteer_settings) && isset($volunteer_settings['registered_user_login_page']) && !empty($volunteer_settings['registered_user_login_page'])){
				$url = get_permalink(trim($volunteer_settings['registered_user_login_page']));
				$returnData['redirectUrl'] = $url;
			}else{
				$returnData['redirectUrl'] = get_site_url();
			}
            
		}elseif($confirmation['redirectTo'] == 'customUrl' & isset($confirmation['redirectTo']) && $confirmation['customUrl'] == '{redirect-volunteer-plugin-settings}'){
			$returnData['redirectUrl'] = get_site_url();
		}

		return $returnData;
	}

	function volunteer_alert_create_registration($userId, $feed, $entry, $form){
		$user_input = $entry->user_inputs;

		if(is_array($user_input) && isset($user_input['reg_alert']) && ($user_input['reg_alert'] == 'yes') && isset($user_input['zipcode']) && !empty($user_input['zipcode'])){
			// user logged-in
			if(isset($user_input['account_creation']) && $user_input['account_creation'] == 'yes'){
				wp_clear_auth_cookie();
				wp_set_current_user($userId);
				wp_set_auth_cookie($userId);
			}
			// Alert not present
			$alert_arr = array();
			$zipcode = $user_input['zipcode'];
			$alert_arr = array(
				'post_status'    => 'any',
				'post_type'      => 'event_alert',
				'author'    => $userId,
				'meta_query'   => array(
					array(
						'key' => '_alert_location', // Replace with the actual meta key for the checkbox field
						'value' => trim($zipcode), // Replace with the value representing the checkbox being checked
						'compare' => '=',
					),
				),
			);
			$is_alert = get_posts($alert_arr);
			// final 
			if(!($is_alert && isset($is_alert[0]->ID))){

				$alert_arr = array();
				$alert_arr = array(
					'post_title'     => $zipcode,
					'post_status'    => 'publish',
					'post_type'      => 'event_alert',
					'comment_status' => 'closed',
					'post_author'    => $userId
				);

				$alert_id = wp_insert_post( $alert_arr );
				if ($alert_id) { 
						
					update_post_meta( $alert_id, '_alert_frequency', 'weekly' );
					update_post_meta( $alert_id, '_alert_location', $zipcode );

					$lat_lng = google_maps_geocoder($zipcode);
					if(is_array($lat_lng) && isset($lat_lng['lat']) && isset($lat_lng['lng'])){
						foreach($lat_lng as $key=>$value){
							update_post_meta($alert_id,'geolocation_'.$key,$value);
						}
					}
				}
			}	
		}
	}

	function volunteer_alert_creation_on_form_submit($insertId, $formData, $form){
		if(is_array($formData) && isset($formData['reg_alert']) && ($formData['reg_alert'] == 'yes') && isset($formData['zipcode']) && !empty($formData['zipcode']) && isset($formData['volunteer_email']) && !empty($formData['volunteer_email'])){
			$user = get_user_by( 'email', $formData['volunteer_email'] );
			if($user && $user->ID){
				// Alert not present
				$alert_arr = array();
				$zipcode = $formData['zipcode'];
				$alert_arr = array(
					'post_status'    => 'any',
					'post_type'      => 'event_alert',
					'author'    => $user->ID,
					'meta_query'   => array(
						array(
							'key' => '_alert_location', // Replace with the actual meta key for the checkbox field
							'value' => trim($zipcode), // Replace with the value representing the checkbox being checked
							'compare' => '=',
						),
					),
				);
				$is_alert = get_posts($alert_arr);
				// final 
				if(!($is_alert && isset($is_alert[0]->ID))){

					$alert_arr = array();
					$alert_arr = array(
						'post_title'     => $zipcode,
						'post_status'    => 'publish',
						'post_type'      => 'event_alert',
						'comment_status' => 'closed',
						'post_author'    => $user->ID
					);

					$alert_id = wp_insert_post( $alert_arr );
					if ($alert_id) { 
							
						update_post_meta( $alert_id, '_alert_frequency', 'weekly' );
						update_post_meta( $alert_id, '_alert_location', $zipcode );

						$lat_lng = google_maps_geocoder($zipcode);
						if(is_array($lat_lng) && isset($lat_lng['lat']) && isset($lat_lng['lng'])){
							foreach($lat_lng as $key=>$value){
								update_post_meta($alert_id,'geolocation_'.$key,$value);
							}
						}
					}
				}	
			}
		}
	}

	/**
	 * Setting an environmental variable to indicate whether the user wants to receive notifications upon the creation of an alert, which is configured during the thank you page process.
	 */
	function volunteer_custom_before_submission_function($insertData, $data, $form)
	{
		$formData = json_decode($insertData['response'],true);
		
		if(isset($_SESSION['volunteer_account_creation'])){
			unset($_SESSION['volunteer_account_creation']);
		}

		if(isset($formData['zipcode']) && isset($formData['account_creation']) && $formData['account_creation'] == 'no'){
			$_SESSION['volunteer_account_creation'] = 'no_creation';
		}
	}

	/**
	 * If no, then direct create account without any notifcation
	 */
	function volunteer_form_settings_ajax($value,$option){
		
		if(isset($_SESSION['volunteer_account_creation']) && $_SESSION['volunteer_account_creation'] == 'no_creation'){
			$value['enabled'] = 'no';
		}
		return $value;
	}

	function volunteer_restrict_page_loggedIn(){
		if ( $_SERVER && (strpos($_SERVER['REQUEST_URI'], 'elementor') === false )) {
			$current_user = is_user_logged_in();
			$url = get_site_url();
			$volunteer_settings = get_option('volunteer_custom_settings');

			$page_arr = array();
			if($current_user && is_array($volunteer_settings) && isset($volunteer_settings['alert_page']) && !empty($volunteer_settings['alert_page'])){
				
				if(isset($volunteer_settings['homepage_alert_creation_page']) && !empty($volunteer_settings['homepage_alert_creation_page'])){
					// logged-in user
					$page_arr[] = $volunteer_settings['homepage_alert_creation_page'];
				}
				//
				if(isset($volunteer_settings['thankyou_alert_creation_page']) && !empty($volunteer_settings['thankyou_alert_creation_page'])){
					$page_arr[] = $volunteer_settings['thankyou_alert_creation_page'];
				}
				//thankyou_user_verification_page
				if(isset($volunteer_settings['thankyou_user_verification_page']) && !empty($volunteer_settings['thankyou_user_verification_page'])){
					$page_arr[] = $volunteer_settings['thankyou_user_verification_page'];
				}
				//register_user_verification_page
				if(isset($volunteer_settings['create_account_user_verification_page']) && !empty($volunteer_settings['create_account_user_verification_page'])){
					$page_arr[] = $volunteer_settings['create_account_user_verification_page'];
				}
			}
			//
			global $post;   $post_id = $post->ID;
			if(in_array($post_id ,$page_arr)){
				$url = get_permalink(trim($volunteer_settings['alert_page']));
				wp_redirect( $url );
				exit();
			}
		}
	}

	/*
	** setting data about user existance in session
	*/
	function volunteer_validate_input_item_input_email($errorMessage, $field, $formData, $fields, $form) {

		if(isset($formData['volunteer_user_email_verify']) && $formData['volunteer_user_email_verify'] == 'yes' && isset($formData['user_email'])){

			$email = $formData['user_email'];
			$user = get_user_by( 'email', $email );
			if($user){
				$_SESSION['volunteer_account_verification_email'] = 'invalid';
			}else{
				$_SESSION['volunteer_account_verification_email'] = 'valid';
				$_SESSION['volunteer_account_verification_user_email'] = $email;
				$_SESSION['referred_account_verification_page'] = $formData['volunteer_page'];
			}
		}
		return $errorMessage;
	}

	/**
	 * setting fluentcrm shortcode
	 */
	function volunteer_editor_shortcodes($smartCodes) {
		$smartCodes[0]['shortcodes']['{vol_session_user_email}'] = 'Get Session User Email';
		return $smartCodes;
	}

	/*
	* To replace dynamic new smartcode the filter hook will be
	* fluentform/editor_shortcode_callback_{your_smart_code_name}
	*/
	function volunteer_editor_shortcode_callback_vol_session_user_email ($value, $form) {
		$dynamicValue = '';
		if(isset($_SESSION['volunteer_account_verification_user_email'])){
			$dynamicValue = $_SESSION['volunteer_account_verification_user_email'];
		}
		return $dynamicValue;
	}
}

WPEM_VOLUNTEER_FluentForm_Custom::instance();