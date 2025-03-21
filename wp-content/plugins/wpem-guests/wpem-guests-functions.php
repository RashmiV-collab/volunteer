<?php 
/**
 * Get default form fields
 * @return array
 */
function get_event_guests_default_form_fields() {
	$default_fields = array(
		'guest_name' => array(
			'label'       => __( 'Guest name', 'wp-event-manager-guests' ),
			'type'        => 'text',
			'required'    => true,
			'placeholder' => '',
			'priority'    => 1,
			'rules'       => array( 'from_name' )
		),
		'guest_email' => array(
			'label'       => __( 'Guest email', 'wp-event-manager-guests' ),
			'description' => '',
			'type'        => 'text',
			'required'    => true,
			'placeholder' => '',
			'priority'    => 2,
			'rules'       => array( 'from_email' )
		)    
	
	);

	return $default_fields;
}

/**
 * Get the form fields for the guest list form
 * @return array
 */
function get_event_guests_form_fields( $suppress_filters = true ) {
	$option = get_option( 'event_guests_form_fields', get_event_guests_default_form_fields() );
	return $suppress_filters ? $option : apply_filters( 'event_guests_form_fields', $option );
}

/**
 * Get group lists
 * @return array
 */
function get_event_guests_group( $group_id = '', $user_id = '', $event_id = '', $start_limit = '', $end_limit = '' ) 
{
	global $wpdb;

	$where = 'WHERE 1=1';
	if ( isset($group_id) && !empty($group_id) ) {

        $where .= ' AND id IN ('. $group_id .')';
    }
    if ( isset($user_id) && !empty($user_id) ) {

        $where .= ' AND user_id IN ('. $user_id .')';
    }
    if ( isset($event_id) && !empty($event_id) ) {

        $where .= ' AND event_id IN ('. $event_id .')';
    }

    /*$query = "SELECT * FROM {$wpdb->prefix}wpem_guests_group
            $where
            ORDER BY `id` asc";*/
     $limit_sql = '';

    if (!is_null($start_limit) && !empty($end_limit) ) {

        $limit_sql = 'LIMIT '.$start_limit .','.$end_limit;
    }

    $query = "SELECT * FROM {$wpdb->prefix}wpem_guests_group $where
            ORDER BY `id` asc {$limit_sql}";        


    if ( isset($group_id) && !empty($group_id) ) 
    {
        $groups = $wpdb->get_row($query);
    }
    else
    {
    	$groups = $wpdb->get_results($query);
    }
    

    if( isset($groups) && !empty($groups) )
    {
    	return $groups;
    }
    else
    {
    	return false;
    }
}

/**
 * Get group lists
 * @return array
 */
function delete_event_guests_group( $group_id = '' ) 
{
	global $wpdb;

	if(!empty($group_id))
	{
		$guests = get_guests($group_id);

		if(!empty($guests))
		{
			foreach ($guests as $guest) 
			{
				wp_trash_post($guest->ID);
			}
		}

		$delete = $wpdb->delete( "{$wpdb->prefix}wpem_guests_group", array( 'id' => $group_id ) );
	}
	else
	{
		$delete = false;
	}

	

	if($delete)
		return true;
	else
		return false;
}

if ( ! function_exists( 'create_event_guests' ) ) {
	/**
	 * Create a new event guest
	 * @param  int $event_id
	 * @param  string $guest_name
	 * @param  string $guest_email
	 * @param  array  $meta
	 * @param  bool $notification
	 * @return int|bool success
	 */
	function create_event_guests( $event_id, $guest_list_fields, $meta = array(), $update = false, $notification = true, $source = 'web' ) {
		$event = get_post( $event_id );
		$user    =  wp_get_current_user();

		if ( ! $event || $event->post_type !== 'event_listing' ) {
			return false;
		}
	
	if(isset($_POST['guest_email'],$_POST['guest_name']) )
		{
			$guest_name = $_POST['guest_name'];
			$guest_email = $_POST['guest_email'];	

		}elseif (empty($guest_list_fields))
		{
			$guest_name  = $user->display_name;
			$guest_email = $user->user_email;
		}
		else{
			$guest_name = '';
			$guest_email = '';
		}	
	
		if ( $meta ) {
			//unset from_name and from_email
			// if(isset($meta['from_name'])){
			// 	$guest_name  =$meta['from_name'];
			// 	unset($meta['from_name']);
			// }
			
			// if(isset($meta['from_email'])){
			// 	$guest_email  =$meta['from_email'];
			// 	unset($meta['from_email']);
			// }

			if(isset($meta['guest_lists_group'])){
				$guest_lists_group  =$meta['guest_lists_group'];
				unset($meta['guest_lists_group']);
			}
		}

		$check_duplicate = apply_filters('event_manager_event_guests_check_duplicate' , false);
		if( $check_duplicate )
		{
			if(email_has_guest_lists_for_event($guest_email, $event_id))
			{
				throw new Exception( __( 'Email already guest list for this event.', 'wp-event-manager-guests' ) );
			}			
		}

		$guest_list_data = apply_filters('wpem_create_event_guests_data',array(
				'post_title'     => wp_kses_post( $guest_name ),
				'post_status'    => 'publish',
				'post_type'      => 'event_guests',
				'comment_status' => 'closed',
				'post_author'    => $event->post_author,
				'post_parent'    => $event_id
		) );

		if($update)
		{
			$guest_list_data['ID'] = $_REQUEST['guest_id'];
			$guest_list_id = wp_update_post( $guest_list_data );
		}
		else
		{
			$guest_list_id = wp_insert_post( $guest_list_data );
		}
		
		
		if ( $guest_list_id ) 
		{
			do_action('wpem_create_event_guests_meta_update_start', $guest_list_id);
			//update_post_meta('_guest_email', $guest_email,  $guest_list_id);
			
			update_post_meta( $guest_list_id, '_guests_group', $guest_lists_group );

			 if ( $meta ) {
		 	foreach ( $meta as $key => $value ) {
			 		update_post_meta( $guest_list_id, $key, $value );
			 	}
			 }
			
			update_post_meta( $guest_list_id, '_guest_email', $guest_email );
			update_post_meta( $guest_list_id, '_guest_name', $guest_name );

			//Needed to be removed in future
			update_post_meta( $guest_list_id, 'guest_email', $guest_email );
			update_post_meta( $guest_list_id, 'guest_name', $guest_name );
			do_action('wpem_create_event_guests_meta_update_end', $guest_list_id);

			//send guest email
			
			if(is_email($guest_email)){
				$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
					remove_all_shortcodes();
					event_guest_email_add_shortcodes( array(
							'guest_id'       		=> $guest_list_id,
							'event_id'              => $event_id,
							'user_id'               => get_current_user_id(),
							'guest_name'         	=> $guest_name,
							'guest_email'        	=> $guest_email,
							'meta'                  => $meta
					) );

		
				$subject = do_shortcode(get_event_guests_email_subject());
				$message = do_shortcode(get_event_guests_email_content());
				wpem_send_guest_email( $guest_email,$subject,$message,$existing_shortcode_tags,'guest',$guest_list_id );

				$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;

			}
			
			//send organizer email
			$organizer_email = get_event_organizer_email($event);
			if(is_email($organizer_email)){
				$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
					remove_all_shortcodes();
					event_guest_email_add_shortcodes( array(
							'guest_id'       		=> $guest_list_id,
							'event_id'              => $event_id,
							'user_id'               => get_current_user_id(),
							'guest_name'         	=> $guest_name,
							'guest_email'        	=> $organizer_email,
							'meta'                  => $meta
					) );

				$subject = do_shortcode(get_event_guests_organizer_email_subject());
				$message = do_shortcode(get_event_guests_organizer_email_content());
				wpem_send_guest_email( $organizer_email,$subject,$message,$existing_shortcode_tags,'guest',$guest_list_id );
				$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;

			}

			return $guest_list_id;
		}
		return false;
	}
}

if ( ! function_exists( 'wpem_send_guest_email' ) ) {
	/**
	 *  Guest email notification
	 * @param  $send_to,$subject,$message,$existing_shortcode_tags,$notification_hook
	 * @return int
	 */
	function wpem_send_guest_email( $send_to,$subject,$message,$existing_shortcode_tags,$notification_hook = '',$guest_id = 0 ){
		
		$message = str_replace( "\n\n\n\n", "\n\n", implode( "\n", array_map( 'trim', explode( "\n", $message ) ) ) );
		$is_html = ( $message != strip_tags( $message ) );
		// Does this message contain formatting already?
		if ( $is_html && ! strstr( $message, '<p' ) && ! strstr( $message, '<br' ) ) {
			//$message = nl2br( $message );
		}

		$message = nl2br( $message );

		$subject = html_entity_decode( $subject );
		
		$headers =  apply_filters("create_event_guest_{$notification_hook}_notification_header",array('Content-Type: text/html; charset=UTF-8'));
		

		wp_mail(
				apply_filters( "create_event_guest_{$notification_hook}_notification_recipient", $send_to),
				apply_filters( "create_event_guest_{$notification_hook}_notification_subject", $subject ),
				apply_filters( "create_event_guest_{$notification_hook}_notification_message", $message ),
				apply_filters( "create_event_guest_{$notification_hook}_notification_headers", $headers ),
				apply_filters( "create_event_guest_{$notification_hook}_notification_attachments", array(), $guest_id),
				);
	}
}


if ( ! function_exists( 'email_has_guests_for_event' ) ) {
	/**
	 * See if a user has already appled for a event
	 * @param  int $user_email
	 * @param  int $event_id
	 * @return bool
	 */
	function email_has_guests_for_event( $user_email, $event_id ) {
		if ( ! $user_email ) {
			return false;
		}

		return sizeof( get_posts( array(
			'post_type'      => 'event_guests',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_parent'    => $event_id,
			'meta_query'     => array(
				array(
					'key' => 'guest_email',
					'value' => $user_email
				)
			)
		) ) );
	}
}

if ( ! function_exists( 'get_guests' ) ) {
	/**
	 * See if a user has already appled for a event
	 * @param  int $group_id
	 * @return array
	 */
	function get_guests( $group_id = '', $user_id = '', $event_id = '' ) 
	{
		$args = [
			'post_type'      => 'event_guests',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => -1,
			'meta_query' 	 => [],
		];

		if( isset($user_id) && !empty($user_id) )
		{
			$args['author'] = $user_id;
		}

		if( isset($event_id) && !empty($event_id) )
		{
			$args['post_parent'] = $event_id;
		}

		if( isset($group_id) && !empty($group_id) )
		{
			$args['meta_query'][] = [
		            'key'     => '_guests_group',
		            'value'   => $group_id,
		            'compare' => '=',
		        ];
		}
		$guests = get_posts($args);

		if( isset($guests) && !empty($guests) )
			return $guests;
		else
			return false;
	}
}

/**
 * Get tags to dynamically replace in the notification email
 * @return array
 */
function get_event_guests_email_tags() {
	$tags = array(
		'from_name'           => __( 'Guest name', 'wp-event-manager-guests' ),
		'from_email'          => __( 'Guest Email', 'wp-event-manager-guests' ),		
		'meta_data'           => __( 'All custom form fields in list format', 'wp-event-manager-guests' ),
		'guest_id'            => __( 'Guest ID', 'wp-event-manager-guests' ),
		'user_id'             => __( 'User ID of attendee', 'wp-event-manager-guests' ),
		'event_id'            => __( 'Event ID', 'wp-event-manager-guests' ),
		'event_title'         => __( 'Event Title', 'wp-event-manager-guests' ),
		'event_dashboard_url' => __( 'URL to the frontend event dashboard page', 'wp-event-manager-guests' ),
	    'event_url'           => __( 'URL to the  current event', 'wp-event-manager-guests' ),
		'organizer_name'      => __( 'Name of the organizer which submitted the event listing', 'wp-event-manager-guests' ),
		'organizer_email'     => __( 'Email of the organizer which submitted the event listing', 'wp-event-manager-guests' ),
		'event_post_meta'     => __( 'Some meta data from the event. e.g. <code>[event_post_meta key="_event_location"]</code>', 'wp-event-manager-guests' )
	);

	foreach ( get_event_guests_form_fields() as $key => $field ) {
		if ( isset( $tags[ $key ] ) ) {
			continue;
		}
		if ( in_array( 'from_name', $field['rules'] ) || in_array( 'from_email', $field['rules'] ) ) {
			continue;
		}
		$tags[ $key ] = sprintf( __( 'Custom field named "%s"', 'wp-event-manager-guests' ), $field['label'] );
	}

	return apply_filters('event_guests_email_tags',$tags);
}



/**
 * Get the default email subject
 * @return string
 */
function get_event_guests_default_email_subject() {
	return __( "New event guest for [event_title]", 'wp-event-manager-guests' );
}


/**
 * Get the default email content
 * @return string
 */
function get_event_guests_default_email_content() {
	$message = <<<EOF
Hello

A attendee ([from_name]) has submitted their guest for the event "[event_title]".

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[message]

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[meta_data]

You can view this and any other guest here: <a href="[event_dashboard_url]">[event_title]</a>

You can contact them directly at: [from_email]
EOF;
	return $message;
}

/**
 * Get email content
 * @return string
 */
function get_event_guests_email_subject() {
	return apply_filters( 'event_guests_email_subject', get_option( 'event_guests_email_subject', get_event_guests_default_email_subject() ) );
}



/**
 * Get email content
 * @return string
 */
function get_event_guests_email_content() {
	return apply_filters( 'event_guests_email_content', get_option( 'event_guests_email_content', get_event_guests_default_email_content() ) );
}



/**
 * Get the default email subject
 * @return string
 */
function get_event_guests_organizer_default_email_subject() {
	return __( "New event guest for [event_title]", 'wp-event-manager-guests' );
}


/**
 * Get the default email content
 * @return string
 */
function get_event_guests_organizer_default_email_content() {
	$message = <<<EOF
Hello

A attendee ([from_name]) has submitted their guest for the event "[event_title]".

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[message]

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

[meta_data]

You can view this and any other guest here: <a href="[event_dashboard_url]">[event_title]</a>

You can contact them directly at: attendee
EOF;
	return $message;
}

/**
 * Get email content
 * @return string
 */
function get_event_guests_organizer_email_subject() {
	return apply_filters( 'event_guests_organizer_email_subject', get_option( 'event_guests_organizer_email_subject', get_event_guests_organizer_default_email_subject() ) );
}



/**
 * Get email content
 * @return string
 */
function get_event_guests_organizer_email_content() {
	return apply_filters( 'event_guests_organizer_email_content', get_option( 'event_guests_organizer_email_content', get_event_guests_organizer_default_email_content() ) );
}


/**
 * Shortcode handler
 * @param  array $atts
 * @return string
 */
function event_guest_email_shortcode_handler( $atts, $content, $value ) {
	$atts = shortcode_atts( array(
		'prefix' => '',
		'suffix' => ''
	), $atts );

	if ( ! empty( $value ) ) {
		if(is_array($value)){
			$new_value = '';
			foreach ($value as  $val) {
				$new_value.= wp_kses_post( $atts['prefix'] ).$val. wp_kses_post( $atts['suffix'] );
			}
			return $new_value;
		}
		
		else
			return wp_kses_post( $atts['prefix'] ) . $value . wp_kses_post( $atts['suffix'] );
	}
}

/**
 * Add shortcodes for email content
 * @param  array $data
 */
function event_guest_email_add_shortcodes( $data ) {
	extract( $data );

	$event_title         	= strip_tags( get_the_title( $event_id ) );
	$dashboard_id      		= get_option( 'event_manager_event_dashboard_page_id' );
	$event_dashboard_url 	= $dashboard_id ? htmlspecialchars_decode( add_query_arg( array( 'action' => 'show_guest_lists'), get_permalink( $dashboard_id ) ) ) : '';
	$event_url 				= get_permalink($event_id);
	$meta_data         		= array();
	$organizer_name      	= get_organizer_name( $event_id );
	$organizer_email      	= get_event_organizer_email( $event_id );
	$guest_id    			= $data['guest_id'];
	$user_id           		= $data['user_id'];

	add_shortcode( 'from_name', function( $atts, $content = '' ) use( $guest_name ) {
		return event_guest_email_shortcode_handler( $atts, $content, $guest_name );
	} );
	add_shortcode( 'from_email', function( $atts, $content = '' ) use( $guest_email ) {
		return event_guest_email_shortcode_handler( $atts, $content, $guest_email );
	} );	
	add_shortcode( 'event_id', function( $atts, $content = '' ) use( $event_id ) {
		return event_guest_email_shortcode_handler( $atts, $content, $event_id );
	} );
	add_shortcode( 'event_title', function( $atts, $content = '' ) use( $event_title ) {
		return event_guest_email_shortcode_handler( $atts, $content, $event_title );
	} );
	add_shortcode( 'event_dashboard_url', function( $atts, $content = '' ) use( $event_dashboard_url ) {
		return event_guest_email_shortcode_handler( $atts, $content, $event_dashboard_url );
	} );
    add_shortcode( 'event_url', function( $atts, $content = '' ) use( $event_url ) {
        return event_guest_email_shortcode_handler( $atts, $content, $event_url );
    } );
	add_shortcode( 'organizer_name', function( $atts, $content = '' ) use( $organizer_name ) {
		return event_guest_email_shortcode_handler( $atts, $content, $organizer_name );
	} );
	add_shortcode( 'organizer_email', function( $atts, $content = '' ) use( $organizer_email ) {
		return event_guest_email_shortcode_handler( $atts, $content, $organizer_email );
	} );
	add_shortcode( 'guest_id', function( $atts, $content = '' ) use( $guest_id ) {
		return event_guest_email_shortcode_handler( $atts, $content, $guest_id );
	} );
	add_shortcode( 'user_id', function( $atts, $content = '' ) use( $user_id ) {
		return event_guest_email_shortcode_handler( $atts, $content, $user_id );
	} );
	add_shortcode( 'event_post_meta', function( $atts, $content = '' ) use( $event_id ) {
		$atts  = shortcode_atts( array( 'key' => '' ), $atts );
		$value = !empty($atts['key']) ? get_post_meta( $event_id, sanitize_text_field( $atts['key'] ), true ) : '';
		return event_guest_email_shortcode_handler( $atts, $content, $value );
	} );

	foreach ( get_event_guests_form_fields() as $key => $field ) {
		if (  in_array( 'from_name', $field['rules'] ) || in_array( 'from_email', $field['rules'] ) ) {
			continue;
		}

		$value = isset( $meta[ $key  ] ) ? $meta[ $key  ] : '';

		if($field['type'] === 'multiselect' && !empty($value))
		//$value = implode(',', (array)$value);
		{
			$meta_data[$field['label'] ] = implode( ', ',(array)$value);
		}
		else
		{
			$meta_data[$field['label'] ] = $value;		
		}		

		add_shortcode( $key, function( $atts, $content = '' ) use( $value ) {
			return event_guest_email_shortcode_handler( $atts, $content, $value );
		} );
	}

	$meta_data         = array_filter( $meta_data );
	$meta_data_strings = array();
	foreach ( $meta_data as $label => $value ) {
		$meta_data_strings[] = $label . ': ' . $value;
	}
	$meta_data_strings = implode( "\n", $meta_data_strings );

	add_shortcode( 'meta_data', function( $atts, $content = '' ) use( $meta_data_strings ) {
		return event_guest_email_shortcode_handler( $atts, $content, $meta_data_strings );
	} );

	do_action( 'wpem_event_guest_email_add_shortcodes', $data );
}




