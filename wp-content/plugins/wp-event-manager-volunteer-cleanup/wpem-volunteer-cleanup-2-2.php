<?php
/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPEM_VOLUNTEER_2_2 {
	
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
		// Checkout process workflow flaw for existing accounts 
		add_filter( 'xoo_easy-login-woocommerce_get_template', [$this,'volunteer_easy_login_woocommerce_template_located'] ,10,4);

		// Cancelled Tickets don't go back into the Pool of Tickets
		add_action( 'save_post', array($this,'volunteer_product_stock_on_cancellation'), 100, 3 ); 

		//Confirmed vs. Total Registrations - Just to remove option 
		// Template - event-registration-edit.php
		// content-tickets-details.php
		add_filter('event_manager_event_registrations_args',array($this,'volunteer_event_registrations_args'), 10, 1 ); 

		// Fix Front End Post Event - registration limit, attendee information collection and Paid Ticket option 
		// Template - event-submit.php
		// multiselect-field.php

		// Handling wpfulentforms textarea
		add_filter('fluentform/rendering_field_data_textarea', array($this,'volunteer_rendering_field_data_textarea'),100,2);

		// checkout page phone
		add_filter('attendee_information_fields', array($this,'volunteer_attendee_information_fields'),10,1); 
		add_action('woocommerce_checkout_process', array($this,'volunteer_registration_form_validate_fields')); 
		remove_action('woocommerce_checkout_process', 'woocommerce_registration_fields_validate_at_checkout');
    }

	function volunteer_easy_login_woocommerce_template_located($located, $template_name, $args, $template_path){
		if ( is_checkout() && $template_name == 'global/xoo-el-login-section.php') {
			$located = WPEM_VOLUNTEER_DIR.'wpem-templates/'.$template_name;
		}
		return $located;
	}

	function volunteer_product_stock_on_cancellation($post_id,$post,$update ){
		if($update && $post->post_type == 'event_registration' && !empty($_POST['wp_event_manager_edit_registration']) && !empty($_POST['old_registration_status'])){
			$old_status = $_POST['old_registration_status'];
			$status = $_POST['registration_status'];
			$products = get_post_meta($post_id, '_ticket_id', true);
			if(is_array($products)){
				foreach($products as $product){
					$product_id = $product;
					$total_sales  = get_post_meta($product_id, 'total_sales', true);
					$_stock  = get_post_meta($product_id, '_stock', true);
					if($status == 'cancelled' && $old_status != 'cancelled' && $total_sales > 0 ){
						// sale
						$total_sales = $total_sales - 1;
						update_post_meta($product_id, 'total_sales', $total_sales);
						// remaining stock
						$_stock = $_stock + 1;
						update_post_meta($product_id, '_stock', $_stock);
					}else if($status != 'cancelled' && $old_status == 'cancelled' && $_stock > 0 ){
						// sale
						$total_sales = $total_sales + 1;
						update_post_meta($product_id, 'total_sales', $total_sales);
						// remaining stock
						$_stock = $_stock - 1;
						update_post_meta($product_id, '_stock', $_stock);
					}
				}
			}
		}
	}

	function volunteer_event_registrations_args($args){
		$event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : '';
		$idsargs = array(
			'fields' => 'ids', 
			'post_type'           => 'event_listing', 
			'post_status'         => array('publish', 'expired'), 
			'posts_per_page'      => -1, 
			'author'              => get_current_user_id()
		);
		//get events for current user to serach in these events only
		$count_events = false;
		if($event_id == 0 || $event_id == '') {
			$ids = get_posts($idsargs);
			$event_id = '';
			if(empty($ids)){
				$count_events = true;
			}
		} else {
			$ids[] = '';
		}
		if($count_events){
			return array( 
				'post_type' => 'event_listing',
				'post__in' => array(2),
			);
		}

		return $args;
	}

	function volunteer_rendering_field_data_textarea($data, $form){
		if(isset($data['attributes']['value']) &&  $data['attributes']['name'] == 'ff_profile_bio' && empty($data['attributes']['value'])){
			$data['attributes']['value'] = '{fluentcrm.fcrm_vol_bio}';
		}
		return $data;
	}

	function volunteer_attendee_information_fields($fields){
		if(function_exists('get_event_registration_form_fields')){
			$fields = '';
		}
		return $fields;
	}

	function volunteer_registration_form_validate_fields(){
		/*$post_data = $_POST;
		foreach($post_data as $key => $value){
			if (strpos($key, 'attendee_phone') !== false) {
				$numberCustom = $value;
				$numberCustom = str_replace(')','',$numberCustom);
				$numberCustom = str_replace('(','',$numberCustom);
				$numberCustom = str_replace('-','',$numberCustom);
				$numberCustom = str_replace(' ','',$numberCustom);
				$numberCustom = str_replace('+','',$numberCustom);
				if(is_numeric($numberCustom) && strlen($numberCustom) > 13 && strlen($numberCustom) < 10){
					wc_add_notice('<strong> Phone Number </strong> is not valid number.', 'error');
					return;
				}
			}
		}*/

		global $woocommerce;

        //if attendee information addon active then use specifields only.
        if(function_exists('get_event_organizer_attendee_fields')) {
            $registration_fields = get_event_organizer_attendee_fields(get_event_id_from_cart());
        } else {
            $registration_fields = get_event_registration_form_fields($suppress_filters = false);
        }

        //count cart items
        $cart_contents = 0;
        if(function_exists('get_display_count_of_attendee_forms')) {
            $cart_contents = get_display_count_of_attendee_forms();
        } else {
            $items = $woocommerce->cart->get_cart();
            foreach($items as $item => $values) {
                $product_event_id = get_post_meta($values['product_id'], '_event_id', true);
                if(isset($product_event_id) && !empty($product_event_id)){
                    $quantity = $values['quantity'];
                    $cart_contents += $quantity;
                }
            }
        }
        $event_id = isset($_POST['event_id']) ? $_POST['event_id'] : '';
        //check for registration addon duplicate registration
        $check_duplicate = apply_filters('event_manager_event_registrations_check_duplicate', get_option('event_registration_prevent_multiple_registrations', true));

        for ($i = 1; $i <= $cart_contents; $i++) {
            foreach ($registration_fields as $field_key => $field_value) {
            
                for ($j = 0; $j < count($field_value['rules']); $j++) {
                    if($field_value['rules'][$j] == 'required') {
                        if(empty(trim($_POST[$field_key . '-' . $i])) ||  (!isset($_POST[$field_key . '-' . $i]))) {
                            wc_add_notice(sprintf(__('Attendee <strong> %s </strong>is a required field.', 'wp-event-manager-sell-tickets'), $field_value['label']), 'error');
                            return;
                        }
                    } else {
                        if($field_value['rules'][$j] == 'numeric') {
							if (!empty($_POST[$field_key . '-' . $i]) && strpos($field_key, 'attendee_phone') !== false) {
								$numberCustom = $_POST[$field_key . '-' . $i];
								$numberCustom = str_replace(')','',$numberCustom);
								$numberCustom = str_replace('(','',$numberCustom);
								$numberCustom = str_replace('-','',$numberCustom);
								$numberCustom = str_replace(' ','',$numberCustom);
								$numberCustom = str_replace('+','',$numberCustom);
								if(is_numeric($numberCustom) && strlen($numberCustom) > 13 && strlen($numberCustom) < 10){
									wc_add_notice('<strong> Phone Number </strong> is not valid number.', 'error');
									return;
								}
							}else if(!is_numeric($_POST[$field_key . '-' . $i]) && !empty($_POST[$field_key . '-' . $i])) {
                                wc_add_notice(sprintf(__('Attendee <strong> %s </strong>is not a valid number.', 'wp-event-manager-sell-tickets'), $field_value['label']), 'error');
                                return;
                            }
                        } else if($field_value['rules'][$j] == 'email' || $field_value['rules'][$j] == 'from_email') {
                            if(!is_email($_POST[$field_key . '-' . $i])) {
                                wc_add_notice(sprintf(__('Attendee <strong> %s </strong>is not a valid email.', 'wp-event-manager-sell-tickets'), $field_value['label']), 'error');
                                return;
                            }
                            if($check_duplicate && !empty($event_id) && isset($_POST[$field_key . '-' . $i])) {
                                if(email_has_registered_for_event($_POST[$field_key.'-'.$i], $event_id)) {
                                    wc_add_notice(__('Email already registered for this event.', 'wp-event-manager-sell-tickets'), 'error');
                                    return;
                                }           
                            }
                        }
                    }
                }
            }
        }
    }
}
WPEM_VOLUNTEER_2_2::instance();