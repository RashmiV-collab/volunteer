<?php
/**
 *  Cron
 */
class Registrations_mail_cron{
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

        
        // Custom script for remove default event alert cron hook
       add_filter( 'schedule_event', array($this,'remove_alert_event_hook') );
       add_action( 'save_post_event_alert', array($this,'volunteer_remove_alert_event_hook'), 10, 3 ); 

        // Change Alert Subject
        add_filter( 'event_manager_alerts_subject', array($this,'volunteer_alter_alert_subject'),10,2);
       
        //---------------
       // additional Guests
       // 1. Save waiver
       remove_action('woocommerce_checkout_update_order_meta', 'save_registration_form_at_checkout_page',10,1 );
       add_action('woocommerce_checkout_update_order_meta', array($this,'volunteer_save_registration_form_at_checkout_page'),10,1);

       // 2. Show meta data over order - admin panel
        add_action( 'woocommerce_after_order_itemmeta', array($this,'display_admin_order_item_event_registration'), 10, 3 );
        // add the action 
        add_action( 'woocommerce_order_refunded', array($this,'volunteer_woocommerce_order_refunded'), 10, 2 ); 

        add_action('admin_footer',array($this,'volunteer_refund_js'));


    }

   /**
     * Change Alert Subject
     */
    function volunteer_alter_alert_subject($title , $alert){
        return 'Upcoming Shoreline Cleanups near you';
    } 

    /**
     * // additional Guests
     * 1. Save waiver
     */
    function volunteer_save_registration_form_at_checkout_page($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $items = $order->get_items();
        $cart_contents =  0;
        $total_ticket_price = 0;
        $ticket_ids = array();
        $ticket_types = array();
        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $event_id = get_post_meta( $product_id , '_event_id',true);
            if(!empty($event_id)){
                $quantity = $item['quantity'];
                $cart_contents += $quantity;
                $total_ticket_price += $item->get_total();
                for($i = 1; $i <= $quantity; $i++){
                    array_push($ticket_ids, $item->get_product_id());
                    array_push($ticket_types, get_post_meta($item->get_product_id(), '_ticket_type', true));
                }
            }
        }
        //check functoin for count attendee total forms attendee information 
        if(function_exists('get_display_count_of_attendee_forms') && !is_admin()){
            $cart_contents = get_display_count_of_attendee_forms();
        }
        if (empty($product_id))
            return;
        $_product = wc_get_product($product_id);

        $event_id = get_post_meta($product_id, '_event_id', true);
        update_post_meta($order_id, '_event_id', $event_id);

        //check if current order is new or existing update
        $args = array(
            'post_type' => 'event_registration',
            'post_parent' => $event_id,
            'post_status' => 'any',
            'posts_per_page'      => -1,
            'meta_query' => array(
                array(
                    'key' => '_order_id',
                    'value' => $order_id,
                    'compare' => '=',
                )
            )
        );
        $registration = get_posts($args);

        if (count($registration) > 0) {
            return;
        }

        $attendee_information_type = get_post_meta($event_id, '_attendee_information_type', true);
        $total_registration = isset($attendee_information_type) && $attendee_information_type == 'buyer_only' ? 1 : $cart_contents;
        $registration_type = isset($attendee_information_type) && $attendee_information_type == 'buyer_only' ? 1 : 0;

        for ($i = 1; $i <= $total_registration; $i++) {
            $ticket_id = array();
            $ticket_type = array();
            $registration_fields = array();
            $fields = get_event_registration_form_fields();
            if (!empty($fields)) {
                foreach ($fields as $key => $field) {
                    if (!empty($_POST[$key . '-' . $i])) {
                        if (in_array('from_name', $field['rules'])){
                            $from_name = $_POST[$key . '-' . $i];
                            $meta['from_name'] = $from_name;
                        }
                        if (in_array('from_email', $field['rules'])){
                            $from_email = $_POST[$key . '-' . $i];
                            $meta['from_email'] = $from_email;
                        }

                        if (is_array($_POST[$key . '-' . $i])) {
                            $keyValue = implode(',', $_POST[$key . '-' . $i]);
                        } else {
                            $keyValue = $_POST[$key . '-' . $i];

                            $registration_fields[$key] = $keyValue;
                        }
                        $meta[$key] = $keyValue;
                    }
                }
            } else {
                if (isset($_POST['billing_first_name']))
                    $registration_fields['first-name'] = $_POST['billing_first_name'];

                if (isset($_POST['billing_last_name']))
                    $registration_fields['last-name'] = $_POST['billing_last_name'];

                if (isset($_POST['billing_email']))
                    $registration_fields['email-address'] = $_POST['billing_email'];
            }
            //check if order from backend then set varibles 
            if(is_admin()){
                //extra meta keys and values
                if($registration_type == 0){
                    array_push($ticket_id, isset($ticket_ids[$i-1]) ? $ticket_ids[$i-1] : '');
                    array_push($ticket_type, isset($ticket_types[$i-1]) && !empty($ticket_types[$i-1]) ? $ticket_types[$i-1]:'');
                    $total_ticket_price = isset($ticket_ids[$i-1]) && !empty($ticket_ids[$i-1]) ? get_post_meta($ticket_ids[$i-1], '_price', true):'';
                }
                $total_ticket = isset($attendee_information_type) && $attendee_information_type == 'buyer_only' ? $cart_contents : 1;
            }else{
                //extra meta keys and values
                if($registration_type == 0){
                    array_push($ticket_id, isset($_POST['ticket_id_' . $i]) ? $_POST['ticket_id_' . $i] : '');
                    array_push($ticket_type, isset($_POST['ticket_type_' . $i]) ? $_POST['ticket_type_' . $i] : '');
                    $total_ticket_price = isset($_POST['total_ticket_price_' . $i]) ? $_POST['total_ticket_price_' . $i] : '';
                }
                $total_ticket = isset($attendee_information_type) && $attendee_information_type == 'buyer_only' ? $woocommerce->cart->cart_contents_count : 1;
            }
            if(isset($_POST['waiver_' . $i])){
                $meta['waiver_'] = $_POST['waiver_' . $i];
            }
            if(isset($_POST['waiver_desc_' . $i])){
                $meta['waiver_desc_'] = $_POST['waiver_desc_' . $i];
            }
            if($registration_type == 1){
                $meta['_ticket_type'] = $ticket_types;
                $meta['_ticket_id'] = $ticket_ids;
            }else{
                $meta['_ticket_type'] = $ticket_type;
                $meta['_ticket_id'] = $ticket_id;
            }
            $meta['_order_id'] = $order_id;
            $meta['_total_ticket_price'] = $total_ticket_price;
            $meta['_total_ticket'] = $total_ticket;
            $meta['_registration_type'] = $registration_type;
            /**
             * Create a new event registration
             * @param  int $event_id
             * @param  string $attendee_name    
             * @param  string $attendee_email
             * @param  array  $meta
             * @param  bool $notification
             * @return int|bool success
             */
            // Create registration
            $registration_id = create_event_registration($event_id, $registration_fields, $meta, true, $source = '');
        }
    }
    // 1. finishes
    /**
     * // additional Guests
     * 2. Show meta data over order - admin panel
     */ 
    function display_admin_order_item_event_registration($item_id, $item, $product ){
        // Only "line" items and backend order pages
        if( ! is_admin() )
            return;
        global $woocommerce, $post;
        $order_id = $post->ID;
        $ticket_id = $product->get_id();
        $args = array(
            'post_type'  => 'event_registration',
            'posts_per_page'      => -1,
            'meta_key'     => '_order_id',
            'meta_value'   => $order_id,
            'post_status' => array('new','confirmed','waiting')
        );
        $registrations = get_posts($args);
       
        if($registrations){
            echo '<div class="view">';
            foreach($registrations as $reg){
                $reg_ticket_id = get_post_meta($reg->ID, '_ticket_id', true) ;
                if(is_array($reg_ticket_id)){
                    $reg_ticket_id = is_serialized($reg_ticket_id) ? @unserialize($reg_ticket_id) : $reg_ticket_id ;
                    if(is_array($reg_ticket_id)){
                        $reg_ticket_id = $reg_ticket_id[0];
                    }
                }
               if($reg_ticket_id == $ticket_id){
                 echo '<div>'.get_post_meta($reg->ID, '_attendee_name',true).' : '.get_post_meta($reg->ID, '_attendee_email',true).'</div>';
                 if(get_post_meta($reg->ID, 'waiver_',true)){
                    echo '<div class="'.$reg->ID.'">'.get_post_meta($reg->ID, 'waiver_',true).' : '.get_post_meta($reg->ID, 'waiver_desc_',true).'</div>';
                    echo '<div><hr/></div>';
                 }
                 
               }
            }
            echo '</div>';
            echo '<div class="refund_registrations" style="display:none;">';
            foreach($registrations as $reg){
                $reg_ticket_id = get_post_meta($reg->ID, '_ticket_id', true) ;
                if(is_array($reg_ticket_id)){
                    $reg_ticket_id = is_serialized($reg_ticket_id) ? @unserialize($reg_ticket_id) : $reg_ticket_id ;
                    if(is_array($reg_ticket_id)){
                        $reg_ticket_id = $reg_ticket_id[0];
                    }
                }
               if($reg_ticket_id == $ticket_id){
                 echo '<div><input type="checkbox" value="'.$reg->ID.'" name="delete_registration[]" class="delete_registration">'.get_post_meta($reg->ID, '_attendee_name',true).'</div>';
               }
            }
            echo '</div>';
        }
    }
    // 2. - finishes

    // additional guest
    // admin refund
    function volunteer_woocommerce_order_refunded($order_id, $refund_id ) 
    { 
        $refund_registration_ids = array();
        if(get_post_meta($order_id,'refund_registration_ids',true)){
            $refund_registration_ids = get_post_meta($order_id,'refund_registration_ids',true);
        }
        if(isset($_POST['reg_ids']) && !empty($_POST['reg_ids'])){

            $order = wc_get_order( $order_id );
            $customer_id = $order->get_user_id();
		    $customer_display_name = get_the_author_meta('display_name', $customer_id);

            foreach($_POST['reg_ids'] as $id){
               // $trash = wp_trash_post( $id );
               $update = wp_update_post(array(
                        'ID'    =>  $id,
                        'post_status'   =>  'cancelled'
                        ));

               if ( !(is_wp_error( $update ) && ! empty( $update->errors ) )){
                    $refund_registration_ids[$refund_id][] = $id;
                    update_post_meta($id, 'registration_cancel_date',date('Y-m-d h:i:s'));
                    update_post_meta($id, 'registration_cancel_user',get_current_user_id());
                }
                $attendee_email = get_post_meta($id, '_attendee_email',true);
                $attendee_name = get_post_meta($id, '_attendee_name',true);

                $event_post = get_post($id);
                $event = get_the_title($event_post->post_parent);

                //Send Email to Attendee
                $messages ="Hello ".$attendee_name.",<br><br>You have removed by ".$customer_display_name." from ".$event." event.<br><br>";
                  
                $header = array('Content-Type: text/html; charset=UTF-8');

                $subject = "Volunteer Cleanup - Event Removal Notification.";
                wp_mail($attendee_email,$subject,$messages,$header);

            }
            update_post_meta($order_id,'refund_registration_ids',$refund_registration_ids);
        }
    }

    // additional guest
    // order refund
    function volunteer_refund_js(){
        
       ?>
       <script type="text/javascript">
        jQuery(document).ready(function(event){
            jQuery('#woocommerce-order-items').on('woocommerce_order_meta_box_do_refund_ajax_data',function(e, data){
                var ids = [];
                jQuery('.delete_registration:checked').each(function() {
                    ids.push(this.value);
                });
                data.reg_ids = ids;
                return data;
            });
            jQuery('button.refund-items').click(function(e){
                jQuery(".refund_registrations").show();
            });
            jQuery('.refund-actions button.cancel-action').click(function(e){
                jQuery(".refund_registrations").hide();
            });
            jQuery('.do-manual-refund').click(function(e){
                var status = false;
                var custom_delete = jQuery('.delete_registration').length;
                if(custom_delete == 0){
                    status = true;
                }else{
                    var ids = [];
                    jQuery('.delete_registration:checked').each(function() {
                        ids.push(this.value);
                    });
                    if(ids.length > 0){
                        status = true;
                    }else{
                        alert('Please select User whose registration admin wants to cancel.');
                    }
                }
                if(!status){
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
       </script>
       <?php
    }
    
     // Custom script for remove default event alert cron hook
     //
    function remove_alert_event_hook($event){
        if($event->hook == 'event-manager-alert'){
            return false;
        }
        return $event;
    }

    // // Custom script for remove default event alert cron hook
    //
    function volunteer_remove_alert_event_hook($post_id, $post, $update){
        wp_clear_scheduled_hook( 'event-manager-alert', array( $post_id ) );
	}

}
Registrations_mail_cron::instance();