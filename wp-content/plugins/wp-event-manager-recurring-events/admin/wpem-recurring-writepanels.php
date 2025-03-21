<?php 
/*
 * This file use to cretae fields of gam event manager at admin side.
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WPEM_Recurring_Writepanels {
	
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'event_manager_event_listing_data_fields', array($this ,'event_listing_event_recurring_fields'),100  );
        add_action( 'admin_enqueue_scripts', array($this ,'admin_enqueue_script' ) );
        
		//check for settings of event recurring 
        if(!get_option('event_manager_recurring_events')){
            add_action( 'event_manager_save_event_listing', array($this , 'update_event_recurrence' ),99,2);
        }else{
        	add_filter( 'manage_edit-event_listing_columns', array( $this, 'columns' ) ,20);
        	add_action( 'manage_event_listing_posts_custom_column', array( $this, 'custom_columns' ), 3 );
        }
        
        //Our class extends the WP_List_Table class, so we need to make sure that it's there
        if(!class_exists('WP_List_Table')){
            require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
        }
        
        // Create menu for recurring event page
        if(get_option('event_manager_recurring_events')){
            add_action( 'admin_menu', array( $this, 'admin_menu' ), 12 );
        }
        
        //Call to ajax for recurring event from admin side
        add_action( 'wp_ajax_create_event_recurring', array( $this, 'create_event_recurring')  );

        //Call to ajax for delete recurring event from admin side
        add_action( 'wp_ajax_delete_event_recurring', array( $this, 'delete_event_recurring')  );
        
        //create serach for event-listing for recurring event list
        add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_parents_posts' ) );
        add_filter( 'request', array( $this, 'request' ) );
        
        //create settings for duplicate event recurreing or schedual cron
        add_filter( 'event_manager_settings', array( $this, 'event_manager_recurring_settings' ), 99 );
	}

	public function columns($columns){
		$new_coumns = array();
		foreach($columns as $key => $column){
			
			if($key == 'event_actions')
				$new_coumns['recurring'] = 'Recurrece';
			$new_coumns[$key] = $column;
		}
		return $new_coumns;
	}

	/**
	 * custom_columns function.
	 *
	 * @access public
	 * @param mixed $column
	 * @return void
	 */
	public function custom_columns( $column ) {
		global $post;

		switch ( $column ) {
			case "recurring" :
				$args = array(
				    'post_parent' => $post->ID, // Current post's ID
				    'post_type'      => 'event_listing'
				);
				$children = get_children( $args );
				// Check if the post has any child
				if ( ! empty($children) ) {
				    echo '<a href="'.esc_url( add_query_arg( 'post_parent', $post->ID ) ).'" >View</a>';
				} else {
				    echo '-';
				}
			break;
		}
	}

	public function recurring_columns(){
		return 'her';
	}

    /**
	 * event recurring setting function.
	 *
	 * @access public
	 * @return array
	 */
	public function event_manager_recurring_settings($settings) {
	    $settings['event-recurring'] = array(
	        __( 'Event Recurring', 'wp-event-manager-recurring-events' ),
	        array(
	            array(
	                'name' 		=> 'event_manager_recurring_events',
	                'std' 		=> '1',
	                'label'      => __( 'Duplicate Recurring Events', 'wp-event-manager-recurring-events' ),
	                'cb_label'   => __( 'Enable Duplicate Recurring Events', 'wp-event-manager-recurring-events' ),
	                'desc'       => __( 'If enabled, recurring events creates duplicate events, else Cron schedules and updates current event after event end.', 'wp-event-manager-recurring-events' ),
	                'type'       => 'checkbox',
	            )
	        )
	    );
	    return $settings;
	}
	
	/**
	 * Filter for recurring event list
	 */
	public function restrict_manage_parents_posts() {
	    global $typenow, $wp_query, $wpdb;
	    
	    if ( 'event_listing' != $typenow ) {
	        return;
	    } ?>

		<select id="dropdown_event_listings" name="post_parent">
			<option value=""><?php _e( 'Select All Events', 'wp-event-manager' ) ?></option>
			<?php
				$events_with_registrations = $wpdb->get_col( "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'event_listing';" );
				$current                = isset( $_GET['post_parent'] ) ? $_GET['post_parent'] : 0;
				echo '<option value="parent_events" '. selected( $current, 'parent_events', false ) .'>' . __( 'Parent Events', 'wp-event-manager' ) . '</option>';
				foreach ( $events_with_registrations as $event_id ) {
					if ( ( $title = get_the_title( $event_id ) ) && $event_id ) {
						echo '<option value="' . $event_id . '" ' . selected( $current, $event_id, false ) . '">' . $title . '</option>';
					}
				}?>
		</select>
		<?php
	}

	/**
 	 * modify what recurring event list are shown
 	 */
	  public function request( $vars ) {
		global $typenow, $wp_query;

		if ( $typenow == 'event_listing' && isset($_GET['post_parent'] )  && $_GET['post_parent'] > 0 ) {
			$vars['post_parent'] = (int) $_GET['post_parent'];
		} elseif ( $typenow == 'event_listing' && isset($_GET['post_parent'] )  && $_GET['post_parent'] == 'parent_events' ){
			$vars['post_parent'] = 0;
		} elseif( $typenow == 'event_listing' && !isset($_GET['post_parent'] ) && get_option('event_manager_recurring_events'))
			$vars['post_parent'] = 0;

		return $vars;
	}
	
	/**
	 * admin_enqueue_script load admin side enqueue script
	 *
	 **/
	public function admin_enqueue_script(){

		wp_register_style( 'wp-event-manager-recurring-events-backend', WPEM_RECURRING_PLUGIN_URL . '/assets/css/backend.css' );

		wp_enqueue_script( 'jquery-tiptip', EVENT_MANAGER_PLUGIN_URL. '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), EVENT_MANAGER_VERSION, true );	
		
		wp_register_script('wp-event-manager-recurring-events-admin-script', WPEM_RECURRING_PLUGIN_URL . '/assets/js/admin.min.js',array ('jquery'), false, false);
		//localize javascript file
		wp_localize_script( 'wp-event-manager-recurring-events-admin-script', 'event_manager_recurring_events', array(
			'every_day' 	 => __( 'day(s)' , 'wp-event-manager-recurring-events'),
			'every_week' 	 => __( 'week(s) on' , 'wp-event-manager-recurring-events'),
			'every_month' 	 => __( 'month(s) on' , 'wp-event-manager-recurring-events'),
			'ofthe_month' 	 => __( 'of the month(s)' , 'wp-event-manager-recurring-events'),
			'every_year' 	 => __( 'year(s) on' , 'wp-event-manager-recurring-events'),
			'ajax_url'       => admin_url('admin-ajax.php'),
		) );
		//always enqueue the script after registering or nothing will happen
		wp_enqueue_script('wp-event-manager-recurring-events-admin-script');
	}
	
	/**
	 * event_listing_event_recurring_fields function.
	 *
	 * @access public
	 * @return void
	 */
	public static function event_listing_event_recurring_fields( $fields ) {
		
		$event_field_count = 50;

		if( !empty($fields) ) {
			$event_field_count = count($fields);	
		}

		$event_form_fields = get_option( 'event_manager_submit_event_form_fields' );

		if( isset($event_form_fields['event']) && !empty($event_form_fields['event']) ) {
			$event_field_count = count($event_form_fields['event']);
		}
		
		$fields['_event_recurrence'] = array(
				'label'		=> __( 'Event Recurrence', 'wp-event-manager-recurring-events' ),
				'type'  	=> 'select',
				'default'  	=> 'no',
				'priority'  => $event_field_count + 1,
				'required'	=> true,
				'options'  	=> array(
						'no' 		    => __( 'Don\'t repeat','wp-event-manager-recurring-events'),
						'daily'         => __( 'Daily','wp-event-manager-recurring-events'),
						'weekly'        => __( 'Weekly','wp-event-manager-recurring-events'),
						'monthly'       => __( 'Monthly','wp-event-manager-recurring-events'),
						'yearly'        => __( 'Yearly','wp-event-manager-recurring-events')
				)
		);
		$fields['_recure_every'] = array(
				'label'			=> __( 'Repeat Every', 'wp-event-manager-recurring-events' ),
				'type'  		=> 'number',
				'default'  		=> '',
				'priority'    	=> $event_field_count + 2,
				'placeholder'	=> '',
				'required'		=> true,
				'description'	=> ' '
		);
		$fields['_recure_time_period'] =  array(
				'label'		  => __('On The','wp-event-manager-recurring-events'),
				'type'        => 'radio',
				'required'    => true,
				'priority'    => $event_field_count + 3,
				'options'=> array(
						'same_time'=> __( 'same day','wp-event-manager-recurring-events'),
						'specific_time'=> __( 'specific day','wp-event-manager-recurring-events')
				)
		);
		$fields['_recure_month_day'] =  array(
				'label'		  => __('Day Number','wp-event-manager-recurring-events'),
				'type'        => 'select',
				'required'    => true,
				'priority'    => $event_field_count + 4,
				'options'=> array(
						'first'		=> __( 'First','wp-event-manager-recurring-events'),
						'second'	=> __( 'Second','wp-event-manager-recurring-events'),
						'third'		=> __( 'Third','wp-event-manager-recurring-events'),
						'fourth'	=> __( 'Fourth','wp-event-manager-recurring-events'),
						'last'		=> __( 'Last','wp-event-manager-recurring-events')
						
				)
		);
		$fields['_recure_weekday'] = array(
				'label'		  => __('Day Name','wp-event-manager-recurring-events'),
				'type'        => 'select',
				'required'    => true,
				'priority'    => $event_field_count + 5,
				'options'=> array(
						'sun'=> __( 'Sunday','wp-event-manager-recurring-events'),
						'mon'=> __( 'Monday','wp-event-manager-recurring-events'),
						'tue'=> __( 'Tuesday','wp-event-manager-recurring-events'),
						'wed'=> __( 'Wednesday','wp-event-manager-recurring-events'),
						'thu'=> __( 'Thursday','wp-event-manager-recurring-events'),
						'fri'=> __( 'Friday','wp-event-manager-recurring-events'),
						'sat'=> __( 'Saturday','wp-event-manager-recurring-events'),
				)
		);
		$fields['_recure_untill'] = array(
				'label'=> __( 'Repeat untill', 'wp-event-manager-recurring-events' ),
				'type'  => 'date',
				'default'  => '',
				'priority'    => $event_field_count + 6,
				'placeholder'	=> '',
				'required'=>true,
		);
		return $fields;
    }
    
    /**
     * create_event_recurring function to duplicate selected events
     *
     * @access public
     * @return json_array
     */
    public function create_event_recurring()
    {
    	global $wpdb;
    	if(isset($_POST['event_id']) && isset( $_POST['start_date']) && isset($_POST['end_date']) ){
    		$event_id=	$_POST['event_id'];
	        $start_date= $_POST['start_date'];
	        $end_date= $_POST['end_date'];
	        $registration_expiry_date= $_POST['registration_expiry_date'];
	        $event = get_post( $event_id);

	       	if ( in_array( 'wp-event-manager-wc-paid-listings/wp-event-manager-wc-paid-listings.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$package_id = get_post_meta( $event_id ,'_user_package_id',true);

				$user_package = wpem_paid_listings_get_user_package($package_id);

				if ( $user_package && $user_package->has_package() ) {
					if( absint($user_package->get_limit()) == absint($user_package->get_count()) ) {
						update_post_meta($event_id, '_check_event_recurrence', 1);
	                
	                	wp_send_json( array('status'=>false) );
					} else {
						wpem_paid_listings_increase_package_count($event->post_author, $package_id);
					}
				}
			}

	        do_action('event_manager_event_recurring_start', $event_id);

	        $recurrece_frequency = get_post_meta( $event_id ,'_event_recurrence',true);
	        $recure_every = get_post_meta( $event_id ,'_recure_every',true);
	        $recure_weekday = get_post_meta( $event_id ,'_recure_weekday',true);
	        $recure_month_day = get_post_meta( $event_id ,'_recure_month_day',true);
	        $recure_time_period = get_post_meta( $event_id ,'_recure_time_period',true);
	        $recure_untill = strtotime(get_post_meta( $event_id ,'_recure_untill',true));
	        
	        $start_time = get_post_meta( $event_id, '_event_start_time',true );
	        $end_time = get_post_meta( $event_id, '_event_end_time',true );
	        $expiry_date=get_post_meta($event_id, '_event_expiry_date', true);
	        
	        if(!empty($start_date) && !empty($end_date) ){
	            $str_time =  strtotime($end_date) - strtotime($start_date);
	            $diff_days = floor($str_time/3600/24);//get the timestamp from start and end date
	            $diff_days = ' + '.$diff_days.' days';
	        }

	        $diff_days_deadline = '';
	        if(!empty($start_date) && !empty($registration_expiry_date) ){
	        	$_start_date = explode(" ",$start_date);
	            $str_time =  strtotime($registration_expiry_date) - strtotime($_start_date[0]);
	            $diff_days_deadline = floor($str_time/3600/24);//get the timestamp from start and end date
	            $diff_days_deadline = ' '.$diff_days_deadline .' days';
	        }

	        $post = get_post( $event_id );
	        
	        if(!empty($event_id) && !empty($recurrece_frequency)  && !empty($recure_every) && !empty($recure_weekday) && !empty($recure_month_day) && get_option('event_manager_submission_requires_approval')!= 1){
	            if($recure_untill<strtotime($start_date)){
	                update_post_meta($event_id, '_check_event_recurrence', 1);
	                wp_send_json( array('status'=>false) );
	            }
	        }
	        
	        switch ( $recurrece_frequency ) {
	            case 'daily' :
	                $next = ' + '.$recure_every.' day';
	                break;
	            case 'weekly' :
	                $next = ' + '.$recure_every.' week '.$recure_weekday;
	                break;
	            case 'monthly' :
	            	if($recure_time_period == 'specific_time'){
	                    $next = ' '.$recure_month_day.' '.$recure_weekday.' of + '.$recure_every.' month';
	                }
	                else{
	                    $next = ' + '.$recure_every.' month today';
	                }
	                break;
	            case 'yearly' :
	                $next = ' + '.$recure_every.' year';
	                break;
	            default :
	                break;
	        }

	        $start_date=date('Y-m-d', strtotime($start_date. $next));
	        $end_date=date('Y-m-d', strtotime($start_date. $diff_days));
	        $registration_expiry_date=date('Y-m-d', strtotime($start_date. $diff_days_deadline));
	        
	        if($recure_untill<strtotime($start_date)){
	            update_post_meta($event_id, '_check_event_recurrence', 1);
	            wp_send_json( array('status'=>false));
	        }
	        
	        /**
	         * Recurre the event.
	         */
	        $new_event_id = wp_insert_post( array(
	        'comment_status' => $post->comment_status,
	        'ping_status'    => $post->ping_status,
	        'post_author'    => $post->post_author,
	        'post_content'   => $post->post_content,
	        'post_excerpt'   => $post->post_excerpt,
	        'post_name'      => $post->post_name,
	        'post_parent'    => $event_id,
	        'post_password'  => $post->post_password,
	        'post_status'    => 'publish',
	        'post_title'     => $post->post_title,
	        'post_type'      => $post->post_type,
	        'to_ping'        => $post->to_ping,
	        'menu_order'     => $post->menu_order
	        ) );
	        
	        /**
	         * Copy taxonomies.
	         */
	        $taxonomies = get_object_taxonomies( $post->post_type );
	        
	        foreach ( $taxonomies as $taxonomy ) {
	            $post_terms = wp_get_object_terms( $event_id, $taxonomy, array( 'fields' => 'slugs' ) );
	            wp_set_object_terms( $new_event_id, $post_terms, $taxonomy, false );
	        }
	        
	        /*
	         * Duplicate post meta, aside from some reserved fields.
	         */
	        $post_meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d", $event_id ) );
	        
	        if ( ! empty( $post_meta ) ) {
	            $post_meta = wp_list_pluck( $post_meta, 'meta_value', 'meta_key' );
	            foreach ( $post_meta as $meta_key => $meta_value ) {
	                if($meta_key=='_event_start_date'){
	                	$db_formatted_start_time = WP_Event_Manager_Date_Time::get_db_formatted_time( $start_time );
	                	$start_time = !empty($db_formatted_start_time) ? $db_formatted_start_time : $start_time;
	                    update_post_meta( $new_event_id, '_event_start_date',  $start_date.' '.$start_time);
	                }elseif($meta_key=='_event_end_date'){
	                	$db_formatted_end_time = WP_Event_Manager_Date_Time::get_db_formatted_time( $end_time );
	                	$end_time = !empty($db_formatted_end_time) ? $db_formatted_end_time : $end_time;
	                    update_post_meta( $new_event_id, '_event_end_date',  $end_date.' '.$end_time);
	                }elseif($meta_key=='_event_registration_deadline'){
	                    update_post_meta( $new_event_id, '_event_registration_deadline',  $registration_expiry_date);
	                }elseif($meta_key=='_event_expiry_date'){
	                    update_post_meta( $new_event_id, '_event_expiry_date',  $end_date);
	                }elseif($meta_key=='_featured'){
	                    update_post_meta( $new_event_id, '_featured',  0);
	                }elseif($meta_key=='_cancelled'){
	                    update_post_meta( $new_event_id, '_cancelled',  0);
	                }elseif($meta_key=='_paid_tickets'){
	                	update_post_meta( $new_event_id, '_paid_tickets',  '');
	                }elseif($meta_key=='_free_tickets'){
	                	update_post_meta( $new_event_id, '_free_tickets',  '');
	                }elseif($meta_key=='_donation_tickets'){
	                	update_post_meta( $new_event_id, '_donation_tickets',  '');
	                }else{
	                    update_post_meta( $new_event_id, $meta_key, maybe_unserialize( $meta_value ) );
	                }
	            }
	        }
	        
	        do_action('event_manager_event_recurring_end', $event_id,$new_event_id);
	        wp_send_json( array('status'=>true,'start_date'=>$start_date,'end_date'=>$end_date,'registration_expiry_date'=>$registration_expiry_date) );
    	}
    	 wp_send_json( array('status'=>true,'message'=> __('Opps! something went wrong.','wp-event-manager-recurring-events')) );
    }

    /**
     * delete_event_recurring function
     * 
     * @access public
     * @return json_array
     */
    public function delete_event_recurring()
    {
    	global $wpdb;
    	if(isset($_POST['event_id']) ){
    		$event_id=	$_POST['event_id'];
	        
	        $event = get_post( $event_id);

	       	do_action('event_manager_delete_event_recurring_start', $event_id);

	       	$args = [
	       		'post_parent'    => $event_id,
		        'post_type'      => $event->post_type,
		        'posts_per_page' => '-1',
	       	];

	       	$recurring_events = get_posts($args);

	       	if(!empty($recurring_events))	{
	       		foreach ($recurring_events as $recurring_event) {
	       			do_action('wpem_before_deleting_recurring_event', $recurring_event->ID,$event_id);
	       			update_post_meta($recurring_event->ID, '_event_banner', '');
	       			delete_post_thumbnail($recurring_event->ID);
	       			wp_delete_post($recurring_event->ID);
	       			do_action('wpem_after_deleting_recurring_event', $recurring_event->ID,$event_id);
	       		}
	       	}

	       	wp_reset_query();
	       	wp_reset_postdata();	       	

	        do_action('event_manager_delete_event_recurring_end', $event_id);

	        update_post_meta($event_id, '_check_event_recurrence', 0);

	        wp_send_json( array('status'=>true,'message'=> __('Successfully! delete recurring events.','wp-event-manager-recurring-events')) );
    	}
    	wp_send_json( array('status'=>false,'message'=> __('Opps! something went wrong.','wp-event-manager-recurring-events')) );
    }
    
    /**
     * admin_menu function.
     *
     * @access public
     * @return void
     */
    public function admin_menu() {
        add_submenu_page( 'edit.php?post_type=event_listing', __( 'Recurring Events', 'wp-event-manager-event-recurring' ), __( 'Recurring Events', 'wp-event-manager-recurring-events' ), 'manage_options', 'event-manager-recurring', array( $this, 'recurring_output' ) );
    }
    
    /**
     * recurring_output function to create recurring event page.
     *
     */
    public function recurring_output(){
        include_once( 'wpem-recurring-events-listing.php' );
        $class = new WPEM_Recurring_Events();
        $class->prepare_items(); ?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2><?php _e('Recurring Event List','wp-event-manager-recurring-events');?></h2>

			<?php 
			if(isset($_GET['create_rec']) && $_GET['create_rec'] == 1){
				echo '<div class="updated"><p>';
				echo __('Event duplicated successfully.','wp-event-manager-recurring-events');
				echo '</p></div>';
			}
			if(isset($_GET['del_rec']) && $_GET['del_rec'] == 1){
				echo '<div class="updated"><p>';
				echo __('Recurrece event deleted successfully.','wp-event-manager-recurring-events');
				echo '</p></div>';
			} ?>
			<?php $class->display(); ?>
		</div>
		<?php
	}
	
    /**
    * update_event_recurrence save recurrence on event update
    * @param $event_id, $post
    * @since 1.4
    */
    public function update_event_recurrence( $event_id, $post  ){

		$event = get_post( $event_id);
		$recurrece_frequency = get_post_meta( $event_id ,'_event_recurrence',true);
		$recure_every = get_post_meta( $event_id ,'_recure_every',true);
		$recure_weekday = get_post_meta( $event_id ,'_recure_weekday',true);
		$recure_month_day = get_post_meta( $event_id ,'_recure_month_day',true);	
			
		if(!empty($event_id) && !empty($recurrece_frequency)  && !empty($recure_every) && !empty($recure_weekday) && !empty($recure_month_day) ){
			wp_clear_scheduled_hook( 'event_manager_event_recurring', array( $event_id) );
			// Schedule new recurrece
			switch ( $recurrece_frequency ) {
				case 'daily' :
					$next = strtotime( '+'.$recure_every.' day' );
					break;
				case 'weekly' :
					$next = strtotime( '+'.$recure_every.' week '.$recure_weekday );
					break;
				case 'monthly' :
					if($fields['event']['recure_time_period'] == 'specific_time'){
						$next = strtotime($recure_month_day.' '.$recure_weekday.' of +'.$recure_every.' month');
					}
					else{
						$next = strtotime( '+'.$recure_every.' month today' );
					}
					break;
				case 'yearly' :
					$next = strtotime( '+'.$recure_every.' year' );
					break;
				default :
					break;
			}
			//Create cron
			wp_schedule_event( $next,$recurrece_frequency,'event_manager_event_recurring', array( $event_id ) );
		}		
	}
}

new WPEM_Recurring_Writepanels();