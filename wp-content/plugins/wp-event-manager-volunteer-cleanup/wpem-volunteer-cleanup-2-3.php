<?php
/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */

use FluentCrm\App\Models\Tag;

final class WPEM_VOLUNTEER_2_3 {
	
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
        // homepage alert creation
        add_action( 'wp_enqueue_scripts', [$this,'volunteer_enqueue_scripts'] );
        add_action( 'wp_ajax_nopriv_volunteer_get_notify_alert_url', [$this,'volunteer_get_notify_alert_url'] );
		add_action( 'wp_ajax_volunteer_get_notify_alert_url', [$this,'volunteer_get_notify_alert_url'] );

		// thank you page - alert creation
        add_shortcode('woocommerce_thankyou_ical_button',[$this,'volunteer_woocommerce_thankyou_ical']);
        add_shortcode('woocommerce_thankyou_alert_button',[$this,'volunteer_woocommerce_thankyou_alert']);
		//add_action( 'woocommerce_order_details_after_order_table', [$this,'volunteer_woocommerce_thankyou'],40,0 );
         add_shortcode('volunteer_thankyou_event_information',[$this,'volunteer_thankyou_event_information']);


		// logging
		add_filter('fluent_crm/purchase_history_woocommerce', [$this,'volunteer_purchase_history_woocommerce'], 100, 2);
		add_action('fluent_crm/after_init', [$this,'volunteer_show_subscriber_log_details']);
        add_action( 'user_register', [$this,'volunteer_log_user_register'],10,2);
        add_action( 'wp_set_password', [$this,'volunteer_wp_set_password'],10,2);
        add_action( 'wp_insert_post', [$this,'volunteer_insert_logging'] ,  10,3 );

        // tagging
        add_action('updated_post_meta', [$this, 'volunteer_save_alert_zip_tagging'], 10,4);
        add_action('added_post_meta', [$this, 'volunteer_save_alert_zip_tagging'], 10,4);
       // add_filter( "update_post_metadata", [$this, 'volunteer_update_alert_zip_tagging'],100,5 );
        add_action( 'before_delete_post', [$this, 'volunteer_delete_alert_zip_tagging'],10,2 );
        add_action( 'deleted_post', [$this, 'volunteer_delete_alert_basic_tagging'],10,2 );
        add_action('fluent_crm/contact_added_by_fluentform',[$this, 'volunteer_contact_added_by_fluentform'], 10,4);
        add_action('fluent_crm/contact_updated_by_fluentform',[$this, 'volunteer_contact_added_by_fluentform'], 10,4);

        // sendgrid Log
		add_action('admin_menu', [$this, 'add_volunteer_menu_sub_menu']);

        // thankyou page
       // add_action('woocommerce_email_order_details', [$this, 'volunteer_sell_tickets_woo_email_event_information'], 10, 4);
        //add_action('woocommerce_order_details_before_order_table', [$this, 'volunteer_sell_tickets_woo_order_info_event_info'], 10,1);
    }

    function volunteer_enqueue_scripts(){

        wp_enqueue_script( 'volunteer-custom-js', WPEM_VOLUNTEER_URI . 'assets/custom.js', array( 'jquery'),strtotime(date('Y-m-d H:i:s')), true );
        wp_localize_script( 'volunteer-custom-js', 'frontendajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

    }

    function volunteer_get_notify_alert_url(){
        $current_user = is_user_logged_in();
        $url = get_site_url();
        $volunteer_settings = get_option('volunteer_custom_settings');
		if($current_user  && is_array($volunteer_settings) && isset($volunteer_settings['alert_page']) && !empty($volunteer_settings['alert_page'])){
            $url = get_permalink(trim($volunteer_settings['alert_page'])).'/?action=add_alert';
        }elseif(!$current_user && is_array($volunteer_settings) && isset($volunteer_settings['homepage_alert_creation_page']) && !empty($volunteer_settings['homepage_alert_creation_page'])){
            $url = get_permalink(trim($volunteer_settings['homepage_alert_creation_page']));
        }
        echo json_encode(array('url' => $url));
        exit;
    }

	function volunteer_woocommerce_thankyou(){
		$volunteer_settings = get_option('volunteer_custom_settings');
		$html = '<div class="volunteer_thankyou_page">';
		global $wp;

		if ( isset($wp->query_vars['order-received']) ) {
			$order_id = absint($wp->query_vars['order-received']); // The order ID
			$order    = wc_get_order( $order_id ); // The WC_Order object
			if($order){
				$order_items = $order->get_items();
				if($order_items){
					foreach ($order_items as $item_id => $item) {
						$product_id = $item->get_product_id();
						$product_name = $item['name'];
						$event_id = get_post_meta($product_id,'_event_id',true);
						if($event_id){
							$html .= '<span><a href="'.(get_permalink($event_id).'?feed=single-event-listings-ical').'" name="submit-event-alert" class="wpem-theme-button" title="Download '.(get_the_title( $event_id )).' Event iCal"> <i class="fa fa-plus" aria-hidden="true"></i> Add to your calendar </a></span>';
						}
					}
				}
			}
		}

		if(is_array($volunteer_settings) && isset($volunteer_settings['thankyou_user_verification_page']) && !empty($volunteer_settings['thankyou_user_verification_page'])){
			$html .= '<span><a href="'.(get_permalink($volunteer_settings['thankyou_user_verification_page'])).'" name="submit-event-alert" class="wpem-theme-button">Receive Weekly Alert of Cleanups Near You</a></span>';
		}

		$html .= '</div>';

		echo $html;
	}

    function volunteer_woocommerce_thankyou_ical(){
		$html = '';
        ob_start();
		global $wp;
		if ( isset($wp->query_vars['order-received']) ) {
			$order_id = absint($wp->query_vars['order-received']); // The order ID
			$order    = wc_get_order( $order_id ); // The WC_Order object
			if($order){
				$order_items = $order->get_items();
				if($order_items){
					foreach ($order_items as $item_id => $item) {
						$product_id = $item->get_product_id();
						$product_name = $item['name'];
						$event_id = get_post_meta($product_id,'_event_id',true);
						if($event_id){
							$html .= '<span><a href="'.(get_permalink($event_id).'?feed=single-event-listings-ical').'" name="submit-event-alert" class="wpem-theme-button" title="Download '.(get_the_title( $event_id )).' Event iCal"> <i class="fa fa-plus" aria-hidden="true"></i> Add to your calendar </a></span>';
						}
					}
				}
			}
		}
        echo $html;
        return ob_get_clean();
    }


    function volunteer_woocommerce_thankyou_alert(){
        $volunteer_settings = get_option('volunteer_custom_settings');
        $html = '';
        ob_start();
        if ( ! is_user_logged_in() ) {
            if(is_array($volunteer_settings) && isset($volunteer_settings['thankyou_user_verification_page']) && !empty($volunteer_settings['thankyou_user_verification_page'])){
                $html .= '<span><a href="'.(get_permalink($volunteer_settings['thankyou_user_verification_page'])).'" name="submit-event-alert" class="wpem-theme-button">Receive Weekly Alert of Cleanups Near You</a></span>';
            }
        }
		echo $html;
        return ob_get_clean();
	}

	function volunteer_purchase_history_woocommerce($data, $subscriber){
		if (!defined('WC_PLUGIN_FILE')) {
            return $data;
        }

        $hasRecount = defined('FLUENTCAMPAIGN') && \FluentCampaign\App\Services\Commerce\Commerce::isEnabled('woo');

        $app = fluentCrm();

        if ($hasRecount && $app->request->get('will_recount') == 'yes') {
            (new \FluentCampaign\App\Services\Integrations\WooCommerce\DeepIntegration)->syncCustomerBySubscriber($subscriber);
        }

        $page = (int)$app->request->get('page', 1);
        $per_page = (int)$app->request->get('per_page', 10);

        $orders = $this->getWooOrders($subscriber);
        $totalOrders = count($orders);
        $orders = array_slice($orders, ($page - 1) * $per_page, $per_page);

        $formattedOrders = [];

        foreach ($orders as $order) {

			$order_items = $order->get_items();
			$event_html = '';
			if($order_items){
				foreach ($order_items as $item_id => $item) {
					$product_id = $item->get_product_id();
					$product_name = $item['name'];
					$event_id = get_post_meta($product_id,'_event_id',true);
					if($event_id){
						$event_title = get_the_title( $event_id );
						$event_html = '<span><a href="'.(get_permalink($event_id)).'" title="'.($event_title).'"> '.($event_title).' </a></span>';
					}
				}
			}

            $item_count = $order->get_item_count() - $order->get_item_count_refunded();
            $actionsHtml = '<a target="_blank" href="' . $order->get_edit_order_url() . '">' . __('View Order', 'fluent-crm') . '</a>';
            $formattedOrders[] = [
                'order'   => '#' . $order->get_order_number(),
                'date'    => esc_html(wc_format_datetime($order->get_date_created())),
				'event' => $event_html,
                'status'  => $order->get_status(),
                'total'   => wp_kses_post(sprintf(_n('%1$s for %2$s item', '%1$s for %2$s items', $item_count, 'fluent-crm'), $order->get_formatted_order_total(), $item_count)),
                'actions' => $actionsHtml
            ];
        }

        $sidebarHtml = apply_filters('fluent_crm/woo_purchase_sidebar_html', '', $subscriber, $page);

        return [
            'data'           => $formattedOrders,
            'sidebar_html'   => $sidebarHtml,
            'total'          => $totalOrders,
            'has_recount'    => $hasRecount,
            'columns_config' => [
                'order'   => [
                    'label' => __('Order', 'fluent-crm'),
                    'width' => '100px'
                ],
                'date'    => [
                    'label' => __('Date', 'fluent-crm')
                ],
				'event' => [
                    'label' => __('Event', 'fluent-crm')
                ],
                'status'  => [
                    'label' => __('Status', 'fluent-crm'),
                    'width' => '100px'
                ],
                'total'   => [
                    'label' => __('Total', 'fluent-crm'),
                    'width' => '160px'
                ],
                'actions' => [
                    'label' => __('Actions', 'fluent-crm'),
                    'width' => '100px'
                ]
            ]
        ];
	}

	private function getWooOrders($subscriber)
    {
        $email = $subscriber->email;

        $user = get_user_by('email', $email);

        // check HPOS is enabled or not
        if (get_option('woocommerce_custom_orders_table_enabled') === 'yes') {
            // high performance order is enabled
            if ($user) {
                $hposOrders = fluentCrmDb()->table('wc_orders')
                    ->select(['id'])
                    ->where(function ($query) use ($user) {
                        $query->where('customer_id', $user->ID)
                            ->orWhere(function ($query) use ($user) {
                                $query->where('billing_email', $user->user_email)
                                    ->where('customer_id', 0);
                            });
                    })
                    ->get();
            } else {
                $hposOrders = fluentCrmDb()->table('wc_orders')
                    ->select(['id'])
                    ->where('billing_email', $email)
                    ->where('customer_id', 0)
                    ->get();
            }

            if (!$hposOrders) {
                return [];
            }

            $orders = [];
            foreach ($hposOrders as $hposOrder) {
                $order = wc_get_order($hposOrder->id);
                if ($order) {
                    $orders[$hposOrder->id] = $order;
                }
            }

            ksort($orders);
            return array_values($orders);
        }


        $orders = [];
        // Get all orders by user id
        $storeUseId = $user ? $user->ID : false;

        if ($storeUseId) {
            $userOrders = wc_get_orders([
                'customer_id' => $storeUseId,
                'limit'       => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
            ]);

            foreach ($userOrders as $order) {
                $orders[$order->get_id()] = $order;
            }
        }

        // get orders by billing email
        $guestOrders = wc_get_orders([
            'customer' => $email,
            'limit'    => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        foreach ($guestOrders as $order) {
            $userId = $order->get_user_id();
            if ($userId && $storeUseId != $userId) {
                continue;
            }
            $orders[$order->get_id()] = $order;
        }


        return array_values($orders);
    }

	function volunteer_show_subscriber_log_details() {
		$key = 'volunteer_subscriber_log';
		$sectionTitle = 'User Activity';
		$callback = array($this,'volunteer_subscriber_log_listing');
		FluentCrmApi('extender')->addProfileSection( $key, $sectionTitle, $callback);
	}

	function volunteer_subscriber_log_listing($contentArr, $subscriber) {
        //
        $email = $subscriber->email;
        $user = get_user_by('email', $email);
        $result = null;
        //
        $row_data = '';
        if($user->ID){
            global $wpdb;
            $tablename = $wpdb->prefix.'volunteer_user_activity_logs';
            //
            $formattedOrders = '';
            $i = 1;
            $results = $wpdb->get_results ( "SELECT * FROM  $tablename  WHERE user_id = $user->ID order by ID desc LIMIT  100" );
            if($results){
                foreach ($results as $result) {
                    //
                    $type = $data = '';
                    if($result->a_type == 'user_register'){
                        $type = 'User Register';
                        $data_arr = json_decode($result->activity,true);
                        if($data_arr){
                            $data = 'User ID : '.$user->ID;
                        }
                    }
                    if($result->a_type == 'event_alert'){
                        $type = 'Create Alert';
                        $data_arr = json_decode($result->activity,true);
                        if($data_arr){
                            $post = get_post($data_arr['post_id']);
                            if($post){
                             $data = 'Event Alert : '.($post->post_title).' (ID : '.($post->ID).') (Zipcode : '.(get_post_meta($post->ID,'_alert_location',true)).')';
                            }
                        }
                    }
                    if($result->a_type == 'event_listing'){
                        $type = 'Create Event';
                        $data_arr = json_decode($result->activity,true);
                        if($data_arr){
                            $post = get_post($data_arr['post_id']);
                            if($post){
                                $data = '<a href="'.(get_permalink($post->ID)).'" target="_blank">'.($post->post_title).'</a>';
                            }
                        }
                    }
                    if($result->a_type == 'event_registration'){
                        $type = 'Create Registration';
                        $data_arr = json_decode($result->activity,true);
                        if($data_arr){
                            $post = get_post($data_arr['post_id']);
                            if($post){
                                $event = get_post( $post->post_parent );
                                $data = 'Attendee Email: '.(get_post_meta($post->ID,'_attendee_email',true)).' (ID : '.($post->ID).') (Event : <a href="'.(get_permalink($event->ID)).'" target="_blank">'.($event->post_title).'</a>)';
                            }
                        }
                    }

                    $formattedOrders .= '<tr>
					<td>' . ($i++).' </td>
					<td> '.($result->created_at).' </td>
					<td> '.($type).' </td>
					<td>'.$data.'</td>
				</tr>';
                }
            }

            $row_data = '<div class="provider_data"><div>
				<table class="widefat striped dataTable" id="volunteer_user_log_datatable">
				<thead>
					<tr>
						<th> ID </th>
						<th> DateTime </th>
						<th> Type </th>
						<th> Actions </th>
					</tr>
				</thead>
				<tbody>'.($formattedOrders).'</tbody>
				</table>
				</div>
                </div>
        ';
            
        }

        //
		$contentArr['heading'] = 'Logs';
		$contentArr['content_html'] = $row_data;
               
		return $contentArr;
	}

	function volunteer_save_alert_zip_tagging($meta_id, $post_id, $meta_key='', $meta_value=''){

          // Stop if not the correct meta key
        if ( $meta_key != '_alert_location') {
                return false;
            }

        $post = get_post($post_id);
                   
        if($post->post_type == 'event_alert'){
           
            // zipcode
            $zipcode = trim($meta_value);
            $zipcode_slug = str_replace(' ', '-', strtolower($zipcode));

            // user
            $user = get_user_by( 'id', $post->post_author );
            //
            if($user->user_email && $zipcode){
                $email = $user->user_email;
                $tagIntance = FluentCrmApi('tags')->getInstance();
                 /* $tags = $tagIntance->where('slug', $zipcode_slug)->get(); 
                $tag_id = null;
                if($tags){
                    foreach($tags as $tag){
                        if($tag->id){
                            $tag_id = $tag->id;
                        }
                    }
                }*/

                // additional tags 
                $newsletter_tags = $tagIntance->where('slug', 'fcrm-tag-newsletter')->get(); 
                $newsletter_tag_id = null;
                if($newsletter_tags){
                    foreach($newsletter_tags as $tag){
                        if($tag->id){
                            $newsletter_tag_id = $tag->id;
                        }
                    }
                }
                
                $weekly_tags = $tagIntance->where('slug', 'weekly-alert')->get(); 
                $weekly_tag_id = null;
                if($weekly_tags){
                    foreach($weekly_tags as $tag){
                        if($tag->id){
                            $weekly_tag_id = $tag->id;
                        }
                    }
                }
               
                //
                //if($tag_id){
                if(true){
                    $subscriber = FluentCrmApi('contacts')->getContact($email);
                    if($subscriber){
                        //$arr = [$tag_id];
                        $arr = [];
                        if($newsletter_tag_id){
                            $arr[] = $newsletter_tag_id;
                        }
                        if($weekly_tag_id){
                            $arr[] = $weekly_tag_id;
                        }
                        $subscriber->attachTags($arr);
                    }
                }else{
                    
                    $tag = Tag::create([
                        'title'       => sanitize_text_field( $zipcode ),
                        'slug'        => $zipcode_slug ,
                    ]);
                    if($tag){
                        do_action('fluentcrm_tag_created', $tag->id);
                        do_action('fluent_crm/tag_created', $tag);
                        $subscriber = FluentCrmApi('contacts')->getContact($email);
                        if($subscriber){
                            $arr = [$tag->id];
                        if($newsletter_tag_id){
                            $arr[] = $newsletter_tag_id;
                        }
                        if($weekly_tag_id){
                            $arr[] = $weekly_tag_id;
                        }
                        $subscriber->attachTags($arr);
                        }
                    }
                }
            }
        }
	}

    function volunteer_contact_added_by_fluentform($subscriber, $entry, $form, $feed){
        $formData = $entry->user_inputs;
        if(isset( $formData['zipcode']) && !empty(isset( $formData['zipcode']))){
            // zipcode
            $zipcode = $formData['zipcode'];
            $zipcode_slug = str_replace(' ', '-', strtolower($zipcode));
            //
            $tagIntance = FluentCrmApi('tags')->getInstance();
          /*  $tags = $tagIntance->where('slug', $zipcode_slug)->get(); 
            $tag_id = null;
            if($tags){
                foreach($tags as $tag){
                    if($tag->id){
                        $tag_id = $tag->id;
                    }
                }
            }*/

            // additional tags 
            $newsletter_tags = $tagIntance->where('slug', 'fcrm-tag-newsletter')->get(); 
            $newsletter_tag_id = null;
            if($newsletter_tags){
                foreach($newsletter_tags as $tag){
                    if($tag->id){
                        $newsletter_tag_id = $tag->id;
                    }
                }
            }
            
            $weekly_tags = $tagIntance->where('slug', 'weekly-alert')->get(); 
            $weekly_tag_id = null;
            if($weekly_tags){
                foreach($weekly_tags as $tag){
                    if($tag->id){
                        $weekly_tag_id = $tag->id;
                    }
                }
            }
           
            //

           // if($tag_id){
            if(true){
                //$arr = [$tag_id];
                $arr = [];
                if($newsletter_tag_id){
                    $arr[] = $newsletter_tag_id;
                }
                if($weekly_tag_id){
                    $arr[] = $weekly_tag_id;
                }
                $subscriber->attachTags($arr);
            }else{
                
                $tag = Tag::create([
                    'title'       => sanitize_text_field( $zipcode ),
                    'slug'        => $zipcode_slug ,
                ]);
                if($tag){
                    do_action('fluentcrm_tag_created', $tag->id);
                    do_action('fluent_crm/tag_created', $tag);
                    $arr = [$tag->id];
                    if($newsletter_tag_id){
                        $arr[] = $newsletter_tag_id;
                    }
                    if($weekly_tag_id){
                        $arr[] = $weekly_tag_id;
                    }
                    $subscriber->attachTags($arr);
                }
            }
        }
    }

    function volunteer_update_alert_zip_tagging($check, $post_id, $meta_key, $meta_value, $prev_value ){
        
         // Stop if not the correct meta key
        $post = get_post($post_id);
        if( $meta_key == '_alert_location' && $post->post_type == 'event_alert' && ( null === $check )){
            
            $previous = get_post_meta($post_id ,'_alert_location',true );
            
            if($previous != $meta_value){
                // zipcode
                $zipcode = trim($previous);
                $zipcode_slug = str_replace(' ', '-', strtolower($zipcode));

                // user
                $user = get_user_by( 'id', $post->post_author );
                //
                if($user->user_email && $zipcode){
                    $email = $user->user_email;
                    $tagIntance = FluentCrmApi('tags')->getInstance();
                    $tags = $tagIntance->where('slug', $zipcode_slug)->get(); 
                    $tag_id = null;
                    if($tags){
                        foreach($tags as $tag){
                            if($tag->id){
                                $tag_id = $tag->id;
                            }
                        }
                    }

                    if($tag_id){

                        $subscriber = FluentCrmApi('contacts')->getContact($email);
                        if($subscriber){
                            $subscriber->detachTags([$tag_id]);
                        }
                    }
                }
            }
        }
        return $check;
    }

    function volunteer_delete_alert_zip_tagging( $post_id, $post){
       
        if($post->post_type == 'event_alert'){
             
            $previous = get_post_meta($post_id ,'_alert_location',true );
             if($previous){
                 // zipcode
                 $zipcode = trim($previous);
                 $zipcode_slug = str_replace(' ', '-', strtolower($zipcode));
 
                 // user
                 $user = get_user_by( 'id', $post->post_author );
                 //
                 if($user->user_email && $zipcode){
                     $email = $user->user_email;
                     $tagIntance = FluentCrmApi('tags')->getInstance();
                     $tags = $tagIntance->where('slug', $zipcode_slug)->get(); 
                     $tag_id = null;
                     if($tags){
                         foreach($tags as $tag){
                             if($tag->id){
                                 $tag_id = $tag->id;
                             }
                         }
                     }
 
                     if($tag_id){
 
                         $subscriber = FluentCrmApi('contacts')->getContact($email);
                         if($subscriber){
                             $subscriber->detachTags([$tag_id]);
                         }
                     }
                 }
            }
         }
    }

    function volunteer_delete_alert_basic_tagging( $post_id, $post){
       
       if($post->post_type == 'event_alert'){

            // user
            $user = get_user_by( 'id', $post->post_author );
            //
            if($user->user_email){
                $email = $user->user_email;
                $tagIntance = FluentCrmApi('tags')->getInstance();
                // additional tags 
                $newsletter_tag_id = $weekly_tag_id = null;

                $args = array(
                    'post_type'  => 'event_alert',
                    'author'     => $post->post_author,
                );
                
                $wp_posts = get_posts($args);

                if(!$wp_posts){

                    $newsletter_tags = $tagIntance->where('slug', 'fcrm-tag-newsletter')->get(); 
                    if($newsletter_tags){
                        foreach($newsletter_tags as $tag){
                            if($tag->id){
                                $newsletter_tag_id = $tag->id;
                            }
                        }
                    }
                    
                    $weekly_tags = $tagIntance->where('slug', 'weekly-alert')->get(); 
                    if($weekly_tags){
                        foreach($weekly_tags as $tag){
                            if($tag->id){
                                $weekly_tag_id = $tag->id;
                            }
                        }
                    }
                }
            
                //

                $subscriber = FluentCrmApi('contacts')->getContact($email);
                if($subscriber){
                    $arr = array();
                    if($newsletter_tag_id){
                        $arr[] = $newsletter_tag_id;
                    }
                    if($weekly_tag_id){
                        $arr[] = $weekly_tag_id;
                    }
                    $subscriber->detachTags($arr);
                }
            }
        }
    }

    function volunteer_log_user_register($user_id, $userdata ){
        global $wpdb;
        $tablename = $wpdb->prefix.'volunteer_user_activity_logs';

       $wpdb->insert( $tablename, array(
           'user_id' => $user_id, 
           'a_type' => 'user_register',
           'activity' => json_encode($userdata) )
       );
    }

    function volunteer_wp_set_password($password, $user_id  ){
        global $wpdb;
        $tablename = $wpdb->prefix.'volunteer_user_activity_logs';

       $wpdb->insert( $tablename, array(
           'user_id' => $user_id, 
           'a_type' => 'set_passowrd',
           'activity' => json_encode($password) )
       );
    }

    function volunteer_insert_logging($post_id, $post, $update){
        if(!$update){
            global $wpdb;
            $tablename = $wpdb->prefix.'volunteer_user_activity_logs';
            if($post->post_type == 'event_alert' || $post->post_type == 'event_registration' || $post->post_type == 'event_listing' ){
                $wpdb->insert( $tablename, array(
                    'user_id' => $post->post_author, 
                    'a_type' => $post->post_type,
                    'activity' => json_encode(array('post_id' => $post_id) )
                    )
                );
            }
        }
    }


    function volunteer_sell_tickets_woo_order_info_event_info($order){
        $this->volunteer_thankyou_page_event_meta($order);
    }

    function volunteer_sell_tickets_woo_email_event_information($order, $sent_to_admin, $plain_text, $email){
        $this->volunteer_thankyou_page_event_meta($order);
    }

    function volunteer_thankyou_event_information(){
        ob_start();
		global $wp;
		if ( isset($wp->query_vars['order-received']) ) {
			$order_id = absint($wp->query_vars['order-received']); // The order ID
			$order    = wc_get_order( $order_id ); // The WC_Order object
			if($order){
                $this->volunteer_thankyou_page_event_meta($order);
            }
        }
        return ob_get_clean();
    }

    function volunteer_thankyou_page_event_meta($order){
        $order_id = $order->get_id();
	    $event_id = get_post_meta($order_id, '_event_id', true);
        $new_content = '';
        // - content -
		$startdate = strtr(get_post_meta($event_id, '_event_start_date', true), '/', '-');
		$enddate   = strtr(get_post_meta($event_id, '_event_end_date', true), '/', '-');
		if(empty($enddate)){
			$endtime = strtr(get_post_meta($event_id, '_event_end_time', true), '/', '-');
			if(!empty($endtime))
				$enddate = date("Y-m-d", strtotime($startdate)).' '.$endtime;
			else
				$enddate = $startdate;
		}

		$location   = get_post_meta($event_id, '_event_location', true) ? get_post_meta($event_id, '_event_location', true) : 'Online';
		// URL 
		$url = get_permalink($event_id);
        $date_format = "m-d-Y T";
		$new_content .= "Start Date Time : ".( wp_kses_post(date_i18n($date_format, strtotime($startdate))) ) . " at ".( get_event_start_time($event_id) ) . "<br/>";
		$new_content .= "End Date Time : ".(  wp_kses_post(date_i18n($date_format, strtotime($enddate)))  ) . " at ".(  get_event_end_time($event_id) ) . "<br/>";
		$new_content .= "Location : ".(  $location ) . "<br/>";
		$new_content .= "Event Page : ".(  '<a href="'.($url).'" target="_blank">'.($url).'</a>' ) . "<br/>";
		// Organizer
		$organizer_ids = get_post_meta($event_id,'_event_organizer_ids',true);
		$organizer_name = $organizer_title = '';
		if($organizer_ids){
			foreach($organizer_ids as $id){
				$organizer_post = get_post($id);
				if($organizer_post){
					$organizer_name = get_post_meta($id,'_organizer_email',true).' ';
					$organizer_title = $organizer_post->post_title.' ';
				}
			}
			$new_content .= "Organizer Name : ".(trim($organizer_title))."<br/>";
			$new_content .= "Organizer Email : ".(trim($organizer_name))."<br/>";
		}
		
		$what_attendees_should_bring = get_post_meta($event_id,'_what_should_volunteers_bring?',true);
		$bring = '';
		if($what_attendees_should_bring){
			$i = 1;
			foreach($what_attendees_should_bring as $item){
				if($i == 1){
					$bring .= ucwords(str_replace('_' , ' ',$item));
					$i++;
				}else{
					$bring .= ', '.ucwords(str_replace('_' , ' ',$item));
				}
			}
			$new_content .= "What should Volunteers Bring : ".(trim($bring))."<br/>";
		}

        $orgainzer_provide = get_post_meta($event_id,'_what_will_be_provided?',true);
        $provide = '';
		if($orgainzer_provide){
			$i = 1;
			foreach($orgainzer_provide as $item){
				if($i == 1){
					$provide .= ucwords(str_replace('_' , ' ',$item));
					$i++;
				}else{
					$provide .= ', '.ucwords(str_replace('_' , ' ',$item));
				}
			}
			$new_content .= "What will be provided : ".(trim($provide))."<br/>";
		}

		if(get_post_meta($event_id,'_meeting_spot_details',true)){	
			$new_content .= "Meeting Spot Details : ".(get_post_meta($event_id,'_meeting_spot_details',true))."<br/>";
		}

        if(get_post_meta($event_id,'_parking_info',true)){	
			$new_content .= "Parking Info : ".(get_post_meta($event_id,'_parking_info',true))."<br/>";
		}

		$description =  $new_content;
		echo '<div style="margin-bottom:15px;">'.$description.'</div>' ; 
    }


    function add_volunteer_menu_sub_menu(){
        $alert_cron_settings = add_submenu_page(
			'volunteer-event-alert',
			'New SendGrid Log',
			'New SendGrid Log',
			'manage_options',
			'volunteer-event-alert-sendgrid-v2-3',
			[$this, 'volunteer_cron_sendgrid_v2_3']
		  );

		add_action('load-' . $alert_cron_settings, array('WPEM_VOLUNTEER','volunteer_admin_custom_css' ));

        if (isset($_GET['page']) && $_GET['page'] == 'fluentcrm-admin' && is_admin()) {
            $wpem_volunteer = WPEM_VOLUNTEER::instance();
            $wpem_volunteer->volunteer_admin_custom_css();
        }
    }

    function volunteer_cron_sendgrid_v2_3(){
        if(isset($_GET['search']) && !empty($_GET['search'])){
			$this->volunteer_get_single_sendgrid_v2_3($_GET['search']);
			return;
		}
        global $wpdb;
        $postmeta_table = $wpdb->prefix.'postmeta';
        $post_table = $wpdb->prefix.'posts';
        $get_alerts = $wpdb->get_results( "SELECT meta_value FROM $postmeta_table as pm join $post_table as p on p.ID = pm.post_id WHERE `meta_key` LIKE '_alert_location' AND `meta_value` IS NOT NULL and meta_value!='' and post_status = 'publish' and post_type = 'event_alert' group by meta_value",ARRAY_A );

        $html = '<div class="wrap cron_alert_log">
        <h2>Alert Listing</h2>';
        $i = 1;
        if($get_alerts){
        $html .= '
        <div class="white-background" style="margin-top:3rem;padding: 1rem;border:solid 1px #c3c4c7">
			<table class="widefat striped dataTable">
			<thead>
                <tr>
                    <th> ID </th>
                    <th> Alert </th>
                    <th> Actions </th>
                </tr>
            </thead>
            <tbody>';
            foreach($get_alerts as $alert){
                $html .= '<tr>
                <td> '.$i++.' </td>
                <td> '.$alert['meta_value'].' </td>
                <td> <a href="admin.php?page=volunteer-event-alert-sendgrid-v2-3&search='.($alert['meta_value']).'" target="_blank">View More</a></td>
            </tr>';
            } 

            $html .= '</tbody>
            </table>
            </div>';
        }

			$html .= '
			</div>
			
		<!-- css -->	
		<style>
			.noVis {
				display: none;
			}
		</style>
		<!-- script -->	
		<script>
		jQuery(document).ready(function($){
			if($(".cron_alert_log").length > 0){
				var table = $(".dataTable").DataTable({
					responsive: true,
					dom: "Bfrtip",
					buttons: [
						"csv",
					]
				} );


			}
		});
		</script>';
			echo $html;
               
    }

    function volunteer_get_single_sendgrid_v2_3($search = null){
		global $wpdb;
        $table_name = $wpdb->prefix.'sendgrid_execute_log';
        $user_table = $wpdb->prefix.'users';
        $post_table = $wpdb->prefix.'posts';
        $postmeta_table = $wpdb->prefix.'postmeta';

		$html = '<div class="wrap cron_alert_log">
			<h2>Alert Based Data</h2>';

        $query = "SELECT u.user_email as email, p.post_date as created 
        FROM $user_table as u
        INNER JOIN $post_table as p ON u.ID = p.post_author
        INNER JOIN $postmeta_table pm ON p.ID  = pm.post_id 
        WHERE `meta_key` LIKE '_alert_location' AND `meta_value` LIKE '".($search)."' and post_status = 'publish' and post_type = 'event_alert'";

        $get_data = $wpdb->get_results($query,ARRAY_A);

        // logs
        $get_logs = $wpdb->get_results( "SELECT * from $table_name order by ID desc",ARRAY_A );
        $logs = $counts = [];
       foreach($get_logs as $log){
            $data = [];
            $mail_data = [];
            $data = json_decode($log['request_data'],true);
           foreach($data as $row){
            $mail_data[] = $row['email'];
           }
           $logs = array_merge($logs,$mail_data);
           $counts = array_count_values($logs);
       }
		$i = 1;
		if($get_data){
            $html .= '<div class="white-background" style="margin-top:5rem;padding: 1rem;border:solid 1px #c3c4c7">
            <h3>User List</h3>
            <table class="widefat striped dataTable">
            <thead>
                <tr>
                    <th> ID </th>
                    <th> User Email </th>
                    <th> Registered On </th>
                    <th> Alert Recieved </th>
                </tr>
            </thead>
            <tbody>';
            foreach($get_data as $log){
                $count = ($counts[$log['email']])?$counts[$log['email']]:0;
                $html .= '<tr>
                <td> '.($i++).' </td>
                <td> '.$log['email'].' </td>
                <td> '.$log['created'].' </td>
                <td> '.$count.' </td>
            </tr>';
            } 

            $html .= '</tbody>
            </table>
            </div>';
		}else{
			$html .= '<div> No Customer found.</div>';
		}
		$html .= '
		</div>
		
	<!-- script -->	
	<script>
	jQuery(document).ready(function($){
		if($(".cron_alert_log").length > 0){
			var table = $(".dataTable").DataTable({
				responsive: true,
				dom: "Bfrtip",
				buttons: [
					"csv",
				]
			} );


		}
	});
	</script>';
		echo $html;
	}

}
WPEM_VOLUNTEER_2_3::instance();