<?php
/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPEM_VOLUNTEER_2_4 {
	
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

        // duplicate events and create events as publish shifted to event-volunteer-cleanup.php
		//add_action('event_manager_event_submitted',[$this,'volunteer_event_manager_update_event_data'],999,1);

        // Display upcoming events on Organizers Profile Page in date sequence
		add_filter('wpem_single_organizer_current_event_listing_query_args',[$this,'volunteer_wpem_single_organizer_current_event_listing_query_args'],999,1);
		add_filter('wpem_single_organizer_upcoming_event_listing_query_args',[$this,'volunteer_wpem_single_organizer_current_event_listing_query_args'],999,1);

        // RSVP download sheet - delete a column
		add_filter('event_registration_dashboard_csv_fields',[$this,'volunteer_event_registration_dashboard_csv_fields'],999,1);

		if(function_exists('event_registration_dashboard_csv_header')){
			remove_filter('event_registration_dashboard_csv_header', 'event_registration_dashboard_csv_header');
			remove_filter('event_registration_dashboard_csv_row_value', 'event_registration_dashboard_csv_row_value', 10, 2);
			add_filter('event_registration_dashboard_csv_header', [$this,'volunteer_event_registration_dashboard_csv_header']);
			add_filter('event_registration_dashboard_csv_row_value', [$this,'volunteer_event_registration_dashboard_csv_row_value'], 10, 2);
		}

        // Duplicate Events - Organizer shifted to event-volunteer-cleanup.php

        // Duplicate Events - Admin
        add_filter('event_manager_admin_actions', [$this,'volunteer_event_manager_admin_actions'], 10, 2);
        add_action('admin_init', [$this,'volunteer_event_manager_duplicate_event']);

        // BUG: guest overwriting buyer name in Fluent CRM
        add_filter( 'wpf_woocommerce_sync_customer_data', [$this,'volunteer_wpf_woocommerce_sync_customer_data' ],100,2);
        add_filter( 'wpf_wp_event_manager_update_existing_user', [$this,'volunteer_wp_fushion_sync_customer_data' ],100,1);
		add_filter( 'wpf_wp-event-manager_guest_registration_data', [$this,'volunteer_wpf_wp_event_manager_guest_registration_data' ],100,3);

        // add css to post an event page
        add_action('wp_footer',[$this,'volunter_add_css__postanevent']);
		
    }

    function volunteer_wpf_woocommerce_sync_customer_data($result, $order){
		return false;
	}

    function volunteer_wp_fushion_sync_customer_data($result){
		return false;
	}

	function volunteer_wpf_wp_event_manager_guest_registration_data($update_data, $email_address, $contact_id){

		if ( !empty( $contact_id ) && is_checkout() ) {
			$update_data = array();
		}
		return $update_data;
	}


    function volunteer_event_manager_update_event_data($post_id){
		$post = get_post($post_id);
        if($post->post_type == 'event_listing' && $post->post_status == 'preview'){
            $update = wp_update_post(array(
                'ID'    =>  $post_id,
                'post_status'   =>  'publish'
                ));
        }
	
    }

	function volunteer_wpem_single_organizer_current_event_listing_query_args($args){
			$args['meta_key'] = '_event_start_date';
            $args['orderby'] = 'meta_value';
            $args['order'] = 'ASC';
            $args['meta_type'] = 'DATETIME';

		return $args;
	}

	function volunteer_event_registration_dashboard_csv_fields($custom_fields){
		return $custom_fields;
	}

	 /**
     * event_registration_dashboard_csv_header function
     * @param  array $row_header  
     * @return array
     */

	function volunteer_event_registration_dashboard_csv_header($row_header) {
        array_push($row_header, 
            __('Buyer\'s Name', 'wp-event-manager-sell-tickets'), 
            __('Buyer\'s Email', 'wp-event-manager-sell-tickets'), 
             __('Ticket Name', 'wp-event-manager-sell-tickets'), 
             __('Checkin', 'wp-event-manager-sell-tickets')
        );
        return $row_header;
    }

	/**
     * event_registration_dashboard_csv_row_value function
     * @param  string, string 
     * @return string
     */
    function volunteer_event_registration_dashboard_csv_row_value($row_value, $registration_id) {
        $order_id = get_post_meta($registration_id, '_order_id', true);
        try {
            $order = new WC_Order($order_id);
            array_push($row_value, $order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            array_push($row_value, $order->get_billing_email());
            // Check for buyer_only type registration.
            if(get_post_meta($registration_id, '_registration_type', true) == 1) {
                // Get ticket name and quantity from order.
                $ticket_qty = '';
                $ticket_name = '';
                $i = 0;
                foreach ($order->get_items() as $item) {
                    if($i > 0) {
                        $ticket_name = $ticket_name . '  |  ' . $item->get_name();
                        $ticket_qty = $ticket_qty . '  |  ' . $item->get_quantity();
                    } else {
                        $ticket_name = $item->get_name();
                        //$ticket_qty = $item->get_quantity();
                    }
                    $i++;
                }
                //array_push($row_value, $ticket_qty);
                array_push($row_value, $ticket_name);
            } else {
                array_push($row_value, get_post_meta($registration_id, '_total_ticket', true));
                $ticket_id = get_post_meta($registration_id, '_ticket_id', true);
                if(is_array($ticket_id))
                     $ticket_id =  $ticket_id[0];
                array_push($row_value, get_the_title($ticket_id));
            }

            $checkin = get_post_meta($registration_id, '_check_in', true);
            if($checkin == 1) {
                $checkin = 'Yes';
            } else {
                $checkin = 'No';
            }
            array_push($row_value, $checkin);

        } catch (Exception $e) {
            echo 'Invalid order id';
        }
        return $row_value;
    }

    function volunteer_event_manager_admin_actions($admin_actions, $post){
        if(current_user_can('manage_event_listings')) {
            $admin_actions['duplicate_event'] = array(
                'action' => 'duplicate_event',
                'name'   => __('Duplicate', 'wp-event-manager'),
                'url'    => wp_nonce_url(add_query_arg('duplicate_event', $post->ID), 'duplicate_event'),
            );
        }

        return $admin_actions;
    }

    function volunteer_event_manager_duplicate_event(){
        if(!empty($_GET['duplicate_event']) && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'duplicate_event') && current_user_can('manage_event_listings',esc_attr( wp_unslash( $_GET['duplicate_event'] )))) {
            $event_id =  $_GET['duplicate_event'];
            $new_event_id = event_manager_duplicate_listing($event_id);
            if($new_event_id) {
                wp_redirect(esc_url_raw(  admin_url( 'post.php?action=edit&post=' . $new_event_id )));
                exit;
            }
        }
    }


    function volunter_add_css__postanevent(){
        ?>
        <style>
            .page-id-10 .fieldset-free_tickets a.wpem-theme-text-button.event_ticket_add_link::before {
                text-decoration: none;
                text-shadow: 5px 5px 8px #5BBF59;
                color: #00f;
                text-transform: uppercase;
                font-size:21px;
            }
            </style>
        <?php
    }
}
WPEM_VOLUNTEER_2_4::instance();