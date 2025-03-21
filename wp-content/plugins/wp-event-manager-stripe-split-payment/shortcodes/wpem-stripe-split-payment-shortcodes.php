<?php
/*
* Integrating OAuth. 
* This file include shortcode to show all tickets per event.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) 
     exit; 

/**
 * WPEM_Stripe_Split_Payment_Shortcodes class.
 */
class WPEM_Stripe_Split_Payment_Shortcodes 
{
	
	/**
	 * Constructor
	 */
	 public function __construct()
	 {					
		//shortcode for connecting to seller's stripe account
		add_shortcode( 'connect_with_stripe', array( $this, 'output_connect_with_stripe' ) );
        
        //disconnect from stripe account
        add_action('wp_ajax_disconnect_stripe_account',  array( $this, 'disconnect_stripe_account' ));

        
        //add application fee to request body
        add_filter('wpem_stripe_api_request_body',array($this,'wpem_stripe_api_request_body') ,10,3) ;
                        

        //wooocommerce hooks
        add_filter( 'woocommerce_account_menu_items', array($this,'stripe_connect_account_menu_items'), 10, 1 );
        add_action( 'init', array($this,'stripe_connect_my_account_endpoint') );
        add_action( 'woocommerce_account_stripe_connect_endpoint', array($this, 'stripe_connect_endpoint_content') );
      
        	
	 }
	
	/**
	 *  With these two pieces of information in hand, you�re ready to have your users connect with your platform.
     *  We recommend showing a Connect button that sends them to our authorize_url endpoint:
	 *  Once you have added a title to your page add the this shortcode: [connect_with_stripe], you will ready to connect with seller stripe account.
	 *  
	 *  Based on Client id (Client id of the platform), it will connect all other stripe account with platform.
	 */
	 public function output_connect_with_stripe($atts)
	 {
		ob_start();
		extract( shortcode_atts( array('event_id'  => ''), $atts ) );
        
        $seller_connected = false;		
		$user = wp_get_current_user();		
		$user_id = $user->ID; 
        $stripe_connect_url='';        
        $stripe_settings = get_option('woocommerce_wpem_stripe_settings');    
        
        if($user_id==0)
        	return __( 'Please login to platform for further usage.', 'wp-event-manager-stripe-split-payment' );

        if(current_user_can( 'manage_options' ))
        {
            return '<div class="wpem-alert wpem-alert-warning">' . __( 'As an Admin, you are not allowed to connect to Stripe.', 'wp-event-manager-stripe-split-payment' ) . '</div>';
        }       
        
        if( isset($stripe_settings) && !empty($stripe_settings) ) 
        {
            if(isset($stripe_settings['enabled']) && $stripe_settings['enabled'] == 'yes'   ) 
            {    
			    $client_id =WPEM_Stripe_Split_Payment_API::get_client_id();
			    $secret_key = WPEM_Stripe_Split_Payment_API::get_secret_key();  
                
                if( isset($client_id) && isset($secret_key) ) 
                {					
					if (isset($_GET['code'])) // Redirect w/ code
                    { 
						$code = $_GET['code'];
						
						if( !is_user_logged_in() ) 
                        {
							if( isset($_GET['state']) ) 
                            {
								$user_id = $_GET['state'];
							}
						}
                        
                        $response = WPEM_Stripe_Split_Payment_API::get_authorization_credentials($secret_key, $client_id, $code);                          
                      
                        if(empty($response['error']))
                        {                        	
                            $seller_connected = update_user_meta( $user_id, '_seller_connected', 1 );
						    update_user_meta( $user_id, '_admin_client_id', $client_id ); // platform client id
						    update_user_meta( $user_id, '_access_token', $response['access_token'] );
						    update_user_meta( $user_id, '_refresh_token', $response['refresh_token'] );
						    update_user_meta( $user_id, '_stripe_publishable_key', $response['stripe_publishable_key'] );
						    update_user_meta( $user_id, '_stripe_user_id', $response['stripe_user_id'] );   //CONNECTED_STRIPE_ACCOUNT_ID
                            update_user_meta( $user_id, '_scope', $response['scope'] );  
                            update_user_meta( $user_id, '_token_type', $response['token_type'] ); 
                            update_user_meta( $user_id, '_livemode', $response['livemode'] );

                            echo '<div class="wpem-alert wpem-alert-success">'.__('Your stripe account has been connected', 'wp-event-manager-stripe-split-payment').'</div>';
                        }
                        else
                        {
                            echo '<div class="wpem-alert wpem-alert-danger">'.$response['error_description'].'</div>';
                        }
                    } 
                    else if (isset($_GET['error'])) 
                    { 
                        // Error
                        echo '<div class="wpem-alert wpem-alert-danger">'.$response['error_description'].'</div>';
                    } 
                    else 
                    { // Show OAuth link
                    
                        $seller_connected = get_user_meta( $user_id, '_seller_connected', true );       
	                    if( $seller_connected == 0 ) 
                        {
                             $stripe_connect_url=WPEM_Stripe_Split_Payment_API::connect_with_stripe($user_id, $client_id, 'read_write');             
                        }                                   
                    }        
	               
                }
            }
            
            wp_enqueue_style('wp-event-manager-stripe-split-payment-frontend');

            wp_enqueue_script( 'gam-wc-connect-with-stripe', WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL .   '/assets/js/connect-with-stripe.min.js', array( 'jquery' ), WPEM_STRIPE_SPLIT_PAYMENT_VERSION, true );

            wp_localize_script('gam-wc-connect-with-stripe', 'wc_connect_with_stripe', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'), 
                    'i18n_processing_message' => __( 'Processing please wait...', 'wp-event-manager-stripe-split-payment' ),
                    'i18n_error_message' => __( 'There was an unexpected error.', 'wp-event-manager-stripe-split-payment' )
                ));

            get_event_manager_template( 'connect-with-stripe.php', array( 
                                                                        'enabled'=>$stripe_settings['enabled'],
                                                                        'stripe_connect'=>$stripe_settings['stripe_connect'],
                                                                        'seller_connected'=> $seller_connected ,
                                                                        'stripe_connect_url' => $stripe_connect_url), 
            'wp-event-manager-stripe-split-payment', 
            WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_DIR. '/templates/' );


        }
        else{
            return '<div class="wpem-alert wpem-alert-warning">' . __( 'WPEM Stripe split payment is not enabled.', 'wp-event-manager-stripe-split-payment' ) . '</div>';
        }
        
       
		
	   return ob_get_clean();
	}
    
    /**
    * Disconnect stripe account
    *
    */
    public function disconnect_stripe_account() 
    {
        //WPEM_Stripe_Split_Payment::log( "disconnect_stripe_account...");
        
		$user = wp_get_current_user();	
		$user_id = $user->ID;
					
		if( $user_id ) 
        {
            update_user_meta( $user_id, '_seller_connected', 0 );
            
            delete_user_meta( $user_id, '_admin_client_id' ); 
			delete_user_meta( $user_id, '_access_token');
			delete_user_meta( $user_id, '_refresh_token');
			delete_user_meta( $user_id, '_stripe_publishable_key');
			delete_user_meta( $user_id, '_stripe_user_id');  
            delete_user_meta( $user_id, '_scope' );  
            delete_user_meta( $user_id, '_token_type'); 
            delete_user_meta( $user_id, '_livemode');  
		}      
        wp_die();
		
	}
    /**
    * Apply application fee/Event flavour fee per transcation
    * Maximum fees alreay handled in sell tickets.
    * @param array $request
    * @param string $api
    * @param int $order_id
    * @return array
    */
    public function wpem_stripe_api_request_body( $request, $api, $order_id) 
    {

        WPEM_Stripe_Split_Payment::log( "wpem_stripe_api_request_body");
        $order = new WC_Order( $order_id );
        $order_items = $order->get_items();

        foreach ($order_items as $key => $item) 
        {
            $product_id = $item->get_product_id();
        }

        $seller_connected = false;

        if( isset($product_id) && !empty($product_id) )
        {
            $product = get_post( $product_id );

            $seller_connected = get_user_meta( $product->post_author, '_seller_connected', true );    
        }

        if($api=='charges' && $seller_connected == 1 )
        {
            global $woocommerce;      
            $fees_pay_by_attendee=0;
              
            //if fee pay by attendee
            $application_fees_object=$woocommerce->cart->get_fees();   
              
             
           
              
            if(!empty($application_fees_object))
            {
                foreach($application_fees_object as $value)
                {
                    WPEM_Stripe_Split_Payment::log( "application_Fee:" .number_format($value->amount, 2, '', ','));
                    
                    if(!empty( $value->amount))
                      // $fees_pay_by_attendee=number_format($value->amount, 2, '', ',');
                      $fees_pay_by_attendee=round($value->amount,2);                 
                    break;
                }
            }
          
            //fees pay by organizer
            $order  = wc_get_order( $order_id );       
            $fees_array=get_fees_pay_by_organizer_from_order( $order );
            $fees_pay_by_organizer=$fees_array['total_fee'];
            $post_author_id=$fees_array['post_author_id'];       
            //if some ticket fee pay by organizer and some ticket fee pay by attendee
            //even if only fees pay by orgnaizer then $fees_pay_by_attendee=0 always zero so need to sump for final calculation.
            $total_fees=$fees_pay_by_attendee +  $fees_pay_by_organizer;
           
        
            //Need to do  
            //before tax, need total and from this total need to minus total fees and after that need to apply tax if enable and that need to send to organizer.
            
            //if orgnizer do not have vat id then we will charge vat on fees, so need to apply 19% on fees.

            $request['application_fee']=number_format($total_fees, 2, '', ',');         
        }
        return $request;
    }

    /**
     * Account menu items
     *
     * @param arr $items
     * @return arr
     */
    public function stripe_connect_account_menu_items( $items ) {
        $items['stripe_connect'] = __( 'Connect Stripe', 'wp-event-manager-stripe-split-payment' );
        return $items;
    }

    /**
     * Add endpoint
     */
    public function stripe_connect_my_account_endpoint() {
        add_rewrite_endpoint( 'stripe_connect', EP_PAGES );
    }

    /**
     * stripe_connect_endpoint_content
     */
    public function stripe_connect_endpoint_content() {
        
        /* if (isset($_GET['code']) )
            $this->save_connected_stripe_account_handler(); */

        echo do_shortcode('[connect_with_stripe]');
    }

    /**
    * save_connected_stripe_account_handler 
    * @since 1.8.2
    * @param $columns array
    * @return $columns array
    */
    public function save_connected_stripe_account_handler(){
        if (isset($_GET['code']) ) // Redirect w/ code
        {
            global $wpevm_wc_getway_stripe;
            $code           = $_GET['code'];
            $secret_key     = WPEM_Stripe_Split_Payment_API::get_secret_key();
            $client_id      = WPEM_Stripe_Split_Payment_API::get_client_id(); 
            $response       = WPEM_Stripe_Split_Payment_API::get_authorization_credentials($secret_key, $client_id, $code);
            if( !isset($response['error']) || empty($response['error']) ){
                $user_id            = get_current_user_id();
                $seller_connected   = update_user_meta( $user_id, '_seller_connected', 1 );
                update_user_meta( $user_id, '_admin_client_id', $client_id ); // platform client id
                update_user_meta( $user_id, '_access_token', $response['access_token'] );
                update_user_meta( $user_id, '_refresh_token', $response['refresh_token'] );
                update_user_meta( $user_id, '_stripe_publishable_key', $response['stripe_publishable_key'] );
                update_user_meta( $user_id, '_stripe_user_id', $response['stripe_user_id'] );   //CONNECTED_STRIPE_ACCOUNT_ID
                update_user_meta( $user_id, '_scope', $response['scope'] );  
                update_user_meta( $user_id, '_token_type', $response['token_type'] ); 
                update_user_meta( $user_id, '_livemode', $response['livemode'] );
            } 

        }
    }
	
}

new WPEM_Stripe_Split_Payment_Shortcodes();
