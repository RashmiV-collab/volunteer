<?php
/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPEM_VOLUNTEER_2_1 {
	
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
       // (10.) Embeddable Event Widget
		add_action( 'wp_footer', [$this,'deregister_embeddable_form'] ,5,0);
        add_filter('get_event_listings_result_args', [$this,'volunteer_event_manager_embeddable_get_listings'],100,2);
		add_action( 'wp_loaded',  [$this,'volunteer_event_widget_js'] );

		// (4.) Admin event notification
		add_action( 'event_manager_event_submitted', [$this,'volunteer_event_admin_mail'],100,1 ); 

		// Syncing Fluent CRM
		add_action('fluentform/notify_on_form_submit', [$this,'volunteer_notify_on_form_submit'],10,3);
		add_action('admin_menu', [$this, 'add_2_1_sub_menu'],100,0);
		add_action( 'save_post', array($this,'volunteer_sync_fluent_data'), 100, 3 ); 
		add_action('fluentcrm_contact_custom_data_updated', array($this,'volunteer_fluentCRM_contact_update'),10,3);

		// Delete Event
		add_action('wp',array($this,'volunteer_enable_event_dashboard'));		

		// event preview
		add_filter( 'submit_event_steps', array($this,'volunteer_submit_event_steps'), 10, 1);
		//add_filter( 'submit_event_form_save_event_data', array($this,'volunteer_submit_event_form_save_event_data'), 100, 5);

		//iCal 
		add_action('init',array($this,'volunteer_event_manager_ical'));

    }
    // 2.1

	function volunteer_event_manager_ical(){
		remove_action('do_feed_single-event-listings-ical', 'wpem_single_event_ical',10,0);
		add_feed('single-event-listings-ical', 'volunteer_single_ical_file_description');
	}

	//embeddale form
	function deregister_embeddable_form(){
        $handle = 'embeddable-event-widget-form';
        $list = 'enqueued';
        if (wp_script_is( $handle, $list )) {
   
            wp_dequeue_script( 'embeddable-event-widget-form' );
            wp_deregister_script( 'embeddable-event-widget-form' );

            wp_register_script( 'embeddable-event-widget-form', WPEM_VOLUNTEER_URI . 'assets/embeddable-form/form.min.js', array( 'jquery', 'chosen','wp-event-manager-common' ), EMBEDDABLE_EVENT_WIDGET_VERSION, true );
            
            ob_start();
            get_event_manager_template( 'embed-code.php', array(), 'wp-event-manager-embeddable-event-widget', EMBEDDABLE_EVENT_WIDGET_PLUGIN_DIR . '/templates/' );
            $code = ob_get_clean();

            ob_start();
            get_event_manager_template( 'embed-code-css.php', array(), 'wp-event-manager-embeddable-event-widget', EMBEDDABLE_EVENT_WIDGET_PLUGIN_DIR . '/templates/' );
            $css = ob_get_clean();

            wp_localize_script( 'embeddable-event-widget-form', 'embeddable_event_widget_form_args', array(
                'code'       => $code,
                'css'        => $css,
                'theme_dark' => '',
                'script_url' => home_url( '/?embed=volunteer_wp_event_manager_widget' ),
                'id' => get_current_user_id(),
            ) );

            wp_enqueue_script( 'embeddable-event-widget-form' );
        }
		
	}

    function volunteer_event_manager_embeddable_get_listings($result,$query_args){
       if ( ! empty( $_GET['embed'] ) && 'volunteer_wp_event_manager_widget' == $_GET['embed'] ){
		
		unset($query_args['orderby']);
		unset($query_args['order']);
			$change = false;
			// Organizer events
            $group = absint( isset( $_GET['volunteer_group'] ) ? $_GET['volunteer_group'] : 0 );
			$event_hosted = absint( isset( $_GET['event_hosted'] ) ? $_GET['event_hosted'] : 0 );
            if($group && $event_hosted == 1){
				$change = true;
                $query_args['author'] = $group;
            }
			// nearby events
			$event_distance =  ( isset($_GET['event_distance']) && $_GET['event_distance'] != '') ? absint( $_GET['event_distance']) : 0;
			if($event_distance > 0 && isset($_GET['location']) && !empty($_GET['location'])){
				$change = true;
				$lat_lng   = google_maps_geocoder($_GET['location']);
				$latitude  = isset($lat_lng['lat']) ? $lat_lng['lat'] : null;
				$longitude = isset($lat_lng['lng']) ? $lat_lng['lng'] : null;
				$events =  $this->get_volunteer_user_events_by_miles($latitude,$longitude,$event_distance);
				if($events){
					$event_arr = array();
					foreach($events as $e){
						$event_arr[] = $e->ID;
					}
					if(!empty($event_arr)){
						$query_args['post_type'] = 'event_listing';
						$query_args['post__in'] = $event_arr;
						$query_args['meta_query'] = array(
											'relation' => 'OR',
											array(
												'key' => '_private_event', // Replace with the actual meta key for the checkbox field
												'value' => '1', // Replace with the value representing the checkbox being checked
												'compare' => '!=',
											),
											array(
												'key'     => '_private_event',
												'compare' => 'NOT EXISTS',
											)
											);
					}
				}else{
					$query_args = array(
						'post_type' => 'event_listing',
						'post__in' => array(2),
					);
				}
			}	
			// sort by latest
			$query_args['meta_key'] = '_event_start_date';
			$query_args['orderby'] = 'meta_value';
			$query_args['order'] = 'ASC';	
			$query_args['meta_type'] = 'DATETIME';
			$result =  new WP_Query( $query_args );
		}
        return $result;
    }

	/**
	 * calculation get miles nearby events by user address
	 */
	public function get_volunteer_user_events_by_miles($latitude = NULL, $longitude = NULL,$miles = null){
		$result = null;
		global $wpdb;
		if ($latitude != NULL && $longitude != NULL) {
			// Radius of the earth 3959 miles or 6371 kilometers.
			$earth_radius = 3959;
			$distance = $miles;
			$where = " 
				$wpdb->posts.post_type IN ( 'event_listing') AND (wp_posts.post_status = 'publish') AND geolocation_lat.meta_key = 'geolocation_lat' AND geolocation_long.meta_key = 'geolocation_long' GROUP BY $wpdb->posts.ID HAVING distance < $distance ";
	
			$join = "$wpdb->posts INNER JOIN $wpdb->postmeta geolocation_lat ON ( $wpdb->posts.ID = geolocation_lat.post_id ) INNER JOIN $wpdb->postmeta geolocation_long ON ( $wpdb->posts.ID = geolocation_long.post_id ) ";
	
			$fields = " $wpdb->posts.*, 
						geolocation_lat.meta_value as latitude, 
						geolocation_long.meta_value as longitude, 
						( $earth_radius * acos(
								cos( radians( $latitude ) )
								* cos( radians( geolocation_lat.meta_value ) )
								* cos( radians( geolocation_long.meta_value ) 
								- radians( $longitude ) )
								+ sin( radians( $latitude ) )
								* sin( radians( geolocation_lat.meta_value ) )
						) )
						AS distance ";
	
			$orderby = " distance DESC ";
			
			$result = $wpdb->get_results("SELECT {$fields} FROM {$join} WHERE {$where} ORDER BY {$orderby}");
		}
		return $result;
	}

	function volunteer_event_widget_js(){
		if ( ! empty( $_GET['embed'] ) && 'volunteer_wp_event_manager_widget' === $_GET['embed'] ) 
        	{
            		//this is getting form form.js: 51            
			$categories = array_filter( array_map( 'absint', explode( ',', $_GET['categories'] ) ) );
			$event_types  = array_filter( array_map( 'sanitize_text_field', explode( ',', $_GET['event_types'] ) ) );
			$page       = absint( isset( $_GET['page'] ) ? $_GET['page'] : 1 );
			$per_page   = absint( $_GET['per_page'] );
            
			$events       = get_event_listings( apply_filters( 'event_manager_embeddable_event_widget_query_args', array(
				                                                    'search_location'   => urldecode( $_GET['location'] ),
				                                                    'search_keywords'   => urldecode( $_GET['keywords'] ),
				                                                    'search_categories' =>  $categories  ,
				                                                    'search_event_types' =>  $event_types ,
				                                                    'posts_per_page'    => $per_page,
				                                                    'offset'            => ( $page - 1 ) * $per_page
			                ) ) );		
			
			//print_R($events->request);exit;
			ob_start();
            
            /* embeddable-event-widget-content is must equal to line 59 
             * also you can find this id embed-code.php:4
            */
			echo '<div class="embeddable-event-widget-content">';
			echo '<ul class="embeddable-event-widget-listings">';

			if ( $events->have_posts() ) : ?>
				<?php while ( $events->have_posts() ) : $events->the_post(); ?>
					<?php get_event_manager_template_part( 'content-embeddable-widget', 'event_listing', 'wp-event-manager-embeddable-event-widget', EMBEDDABLE_EVENT_WIDGET_PLUGIN_DIR . '/templates/' ); ?>
				<?php endwhile; ?>
			<?php else : ?>
				<li class="no-results"><?php _e( 'No matching events found', 'wp-event-manager-embeddable-event-widget' ); ?></li>
			<?php endif;

			echo '</ul>';

			if ( ! empty( $_GET['pagination'] ) ) {
				echo '<div id="embeddable-event-widget-pagination">';
				if ( $page > 1 ) {
					echo '<a href="#" class="embeddable-event-widget-prev" onclick="EmbeddableEventWidget.prev_page(); return false;">' . __( 'Previous', 'wp-event-manager-embeddable-event-widget' ) . '</a>';
				}
				if ( $page < $events->max_num_pages ) {
					echo '<a href="#" class="embeddable-event-widget-next" onclick="EmbeddableEventWidget.next_page(); return false;">' . __( 'SHOW MORE CLEANUPS', 'wp-event-manager-embeddable-event-widget' ) . '</a>';
				}
				echo '</div>';
			}

			echo '</div>';

			$content = ob_get_clean();

			header( "Content-Type: text/javascript; charset=" . get_bloginfo( 'charset' ) );
			header( "Vary: Accept-Encoding" ); // Handle proxies
			?>
			if ( EmbeddableEventWidget != undefined ) {
				EmbeddableEventWidget.show_events( 'embeddable-event-widget-content', '<?php echo esc_js( $content ); ?>' );
			}
			<?php
			exit;
		}
	}

	// 10. - finishes

	//4. admin email
	function volunteer_event_admin_mail($post_id){
		// Only want to set if this is a new post
		if(class_exists('WP_Event_Manager_Emails')){
			$event_id = $post_id;
			//get organizer info
			$post = get_post($post_id);
			$organizer_info = get_organizer_notification_email($event_id, $post );
			$admin_email = get_option('admin_email');
			$admin_event = get_option( 'admin_event_email_nofication',true )? true : false;
			// send mail to admin for new event post
			if ( $admin_email && $admin_event) {					
				//get mail content for admin mail
				$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
				remove_all_shortcodes();
				event_manager_email_add_shortcodes( array( 'event_id' => $event_id, 'user_id' => '', 'organizer_email' => $organizer_info['organizer_email'], 'site_admin_email' => $admin_email ) );
				
				$subject = do_shortcode( get_admin_event_email_subject() );
				$subject = html_entity_decode( $subject , ENT_QUOTES, "UTF-8" );

				$message = do_shortcode( get_admin_event_email_content() );

				$message = str_replace( "\n\n\n\n", "\n\n", implode( "\n", array_map( 'trim', explode( "\n", $message ) ) ) );
				$is_html = ( $message != strip_tags( $message ) );
			
				$message = nl2br( $message );

				$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;

				$headers   = array();
				$headers[] = 'From: ' . $organizer_info['organizer_name'] . ' <' . $organizer_info['organizer_email'] . '>';
				$headers[] = 'Reply-To: ' . $admin_email;
				$headers[] = 'Content-Type: text/html';
				$headers[] = 'charset=utf-8';

				wp_mail(
					apply_filters( 'send_new_event_admin_email_notification_recipient', $admin_email, $event_id ),
					apply_filters( 'send_new_event_admin_email_notification_subject', sprintf(__('%s','wp-event-manager-emails'),$subject), $event_id ),
					apply_filters( 'send_new_event_admin_email_notification_message', sprintf(__('%s','wp-event-manager-emails'),$message )),
					apply_filters( 'send_new_event_admin_email_notification_headers', $headers, $event_id )						
				);
			}


			// Organizer email
			//check organizer email address
			if ( isset($organizer_info) && !empty($organizer_info)) {
					
				$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
				remove_all_shortcodes();
				event_manager_email_add_shortcodes( array( 'event_id' => $event_id, 'user_id' => '', 'organizer_email' => $organizer_info['organizer_email'], 'site_admin_email' => $admin_email ) );
				
				$subject = do_shortcode( get_published_event_email_subject() );
				$subject = html_entity_decode( $subject , ENT_QUOTES, "UTF-8" );
	
				$message = do_shortcode( get_published_event_email_content() );
				$message = str_replace( "\n\n\n\n", "\n\n", implode( "\n", array_map( 'trim', explode( "\n", $message ) ) ) );
				$is_html = ( $message != strip_tags( $message ) );
			
				$message = nl2br( $message );
	
				$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;
	
				$headers =  get_wpem_email_headers($event_id, '', $admin_email, $organizer_info['organizer_name'], $organizer_info['organizer_email']);
	
				$check_mail = wp_mail(
					apply_filters( 'send_published_event_email_notification_recipient', $organizer_info['organizer_email'], $event_id ),
					apply_filters( 'send_published_event_email_notification_subject',sprintf( __('%s','wp-event-manager-emails'),$subject), $event_id ),
					apply_filters( 'send_published_event_email_notification_message', sprintf(__('%s','wp-event-manager-emails'),$message) ),
					apply_filters( 'send_published_event_email_notification_headers', $headers, $event_id )						
				);
				if($check_mail){
					update_post_meta($event_id, '_send_published_event_email_status', 1);
				}
			}
		}
	}

	// fluent CRM to organizer

	function volunteer_notify_on_form_submit($insertId, $formData, $form){
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$user_email = $current_user->user_email;
		if($user_id && $user_id > 0){
			$post_arr = array(
				'post_status'   => 'publish',
				'author'   => $user_id,
				'post_type' => 'event_organizer',
				'meta_key' => '_organizer_email',
				'meta_value' => $user_email,
			);
			$organizers = get_posts($post_arr);
			if($organizers){
				$id = null;
				foreach($organizers as $o){
					$id = $o->ID;
					$user_id = $o->post_author;
					// bio
					if(isset($formData['ff_profile_bio'])){
						$organizer_post = array(
							'ID'           => $id ,
							'post_content' => $formData['ff_profile_bio'],
						   );
					  
						  // Update the post into the database
						  wp_update_post( $organizer_post );
}
					// website
					if(isset($formData['ff_profile_website'])){
						update_post_meta( $id,'_organizer_website',$formData['ff_profile_website']);
					}
					// facebook
					if(isset($formData['ff_profile_facebook'])){
						update_post_meta( $id,'_organizer_facebook',$formData['ff_profile_facebook']);
					}
					// instagram
					if(isset($formData['ff_profile_instagram'])){
						update_post_meta( $id,'_organizer_instagram',$formData['ff_profile_instagram']);
					}
					// twitter
					if(isset($formData['ff_profile_twitter'])){
						update_post_meta( $id,'_organizer_twitter',$formData['ff_profile_twitter']);
					}
					// twitter
					if(isset($formData['ff_profile_youtube'])){
						update_post_meta( $id,'_organizer_youtube',$formData['ff_profile_youtube']);
					}
					if(isset($formData['ff_profile_picture']) && !empty($formData['ff_profile_picture'])){

						$profile = $formData['ff_profile_picture'][0];
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
							
							// Include image.php
							require_once(ABSPATH . 'wp-admin/includes/image.php');

							// Define attachment metadata
							$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
							
							// Assign metadata to attachment
							wp_update_attachment_metadata( $attach_id, $attach_data );
							
							// And finally assign featured image to post
							set_post_thumbnail( $id, $attach_id );
							update_user_meta( $user_id, '_organizer_logo', $attach_id );
						}
					}
				}
			}
			
		}// valid  user_id
	}

	//
	// organizer to fluent CRM

	function add_2_1_sub_menu(){
		add_submenu_page(
			'volunteer-event-alert',
			'Volunteer Settings',
			'Volunteer Settings',
			'manage_options',
			'volunteer--settings',
			[$this, 'volunteer_admin__settings']
		  );
	}

	function volunteer_admin__settings(){

		if(!empty($_POST['volunteer_settings'])){
			update_option('volunteer_custom_settings', $_POST['volunteer_settings']);
		}
	
		$html = '
		<div class="wrap">
			<h2>Settings</h2>
			<div class="white-background" style="padding: 1rem;border:solid 1px #c3c4c7">
			<form action="" method="post" style="margin-top:2rem;	">
			';
			$pages = get_pages();
			$option_html = '<option value="">None</option>';
			$volunteer_settings = get_option('volunteer_custom_settings');
			$event_dashboard = (is_array($volunteer_settings) && isset($volunteer_settings['event_dashboard']))?$volunteer_settings['event_dashboard']:'';
			if($pages){
				foreach($pages as $p){
					$option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $event_dashboard)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$alert_option_html = '<option value="">None</option>';
			$alert_page = (is_array($volunteer_settings) && isset($volunteer_settings['alert_page']))?$volunteer_settings['alert_page']:'';
			if($pages){
				foreach($pages as $p){
					$alert_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $alert_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$registered_user_login_page_option_html = '<option value="">None</option>';
			$registered_user_login_page = (is_array($volunteer_settings) && isset($volunteer_settings['registered_user_login_page']))?$volunteer_settings['registered_user_login_page']:'';
			if($pages){
				foreach($pages as $p){
					$registered_user_login_page_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $registered_user_login_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$homepage_alert_creation_page_option_html = '<option value="">None</option>';
			$homepage_alert_creation_page = (is_array($volunteer_settings) && isset($volunteer_settings['homepage_alert_creation_page']))?$volunteer_settings['homepage_alert_creation_page']:'';
			if($pages){
				foreach($pages as $p){
					$homepage_alert_creation_page_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $homepage_alert_creation_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$homepage_user_verification_option_html = '<option value="">None</option>';
			$homepage_user_verification_page = (is_array($volunteer_settings) && isset($volunteer_settings['homepage_user_verification_page']))?$volunteer_settings['homepage_user_verification_page']:'';
			if($pages){
				foreach($pages as $p){
					$homepage_user_verification_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $homepage_user_verification_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$login_option_html = '<option value="">None</option>';
			$login_page = (is_array($volunteer_settings) && isset($volunteer_settings['login_page']))?$volunteer_settings['login_page']:'';
			if($pages){
				foreach($pages as $p){
					$login_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $login_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$thankyou_page_user_verification_option_html = '<option value="">None</option>';
			$thankyou_user_verification_page = (is_array($volunteer_settings) && isset($volunteer_settings['thankyou_user_verification_page']))?$volunteer_settings['thankyou_user_verification_page']:'';
			if($pages){
				foreach($pages as $p){
					$thankyou_page_user_verification_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $thankyou_user_verification_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$thankyou_alert_creation_page_option_html = '<option value="">None</option>';
			$thankyou_alert_creation_page = (is_array($volunteer_settings) && isset($volunteer_settings['thankyou_alert_creation_page']))?$volunteer_settings['thankyou_alert_creation_page']:'';
			if($pages){
				foreach($pages as $p){
					$thankyou_alert_creation_page_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $thankyou_alert_creation_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$create_account_page_user_verification_option_html = '<option value="">None</option>';
			$create_account_user_verification_page = (is_array($volunteer_settings) && isset($volunteer_settings['create_account_user_verification_page']))?$volunteer_settings['create_account_user_verification_page']:'';
			if($pages){
				foreach($pages as $p){
					$create_account_page_user_verification_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $create_account_user_verification_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$create_account_registration_page_option_html = '<option value="">None</option>';
			$create_account_registration_page = (is_array($volunteer_settings) && isset($volunteer_settings['create_account_registration_page']))?$volunteer_settings['create_account_registration_page']:'';
			if($pages){
				foreach($pages as $p){
					$create_account_registration_page_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $create_account_registration_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			$cancel_registration_page_option_html = '<option value="">None</option>';
			$cancel_registration_page = (is_array($volunteer_settings) && isset($volunteer_settings['cancel_registration_page']))?$volunteer_settings['cancel_registration_page']:'';
			if($pages){
				foreach($pages as $p){
					$cancel_registration_page_option_html .= '<option value="'.$p->ID.'" '.(($p->ID == $cancel_registration_page)?'selected':'').'>'.$p->post_title.'</option>';
				}
			}

			//-----------------
			$enable_alert_cron = (is_array($volunteer_settings) && isset($volunteer_settings['enable_alert_cron']))?$volunteer_settings['enable_alert_cron']:'';
			//-----------------
			$html .= '
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="blogname">Enable Alert Cron</label> </th>
						<td>
							<input type="checkbox" name="volunteer_settings[enable_alert_cron]" value="yes" '.(('yes' == $enable_alert_cron)?'checked':'').'>
							<p class="description">Check to enable </p>
						</td>
					</tr>
					<tr style="border-top:1px solid">
						<th scope="row"><label for="blogname"> User Login Page </label> </th>
						<td>
							<select name="volunteer_settings[login_page]" class="regular-text">
							'.$login_option_html.'
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname"> Event Dashboard Page(Used Force Event delete) </label> </th>
						<td>
							<select name="volunteer_settings[event_dashboard]" class="regular-text">
							'.$option_html.'
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Event Manager User Alert Page</label> </th>
						<td>
							<select name="volunteer_settings[alert_page]" class="regular-text">
							'.$alert_option_html.'
							</select>
							<p class="description">This lets the plugin know where the logged-user alert listing page is present.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Registered User Login Page</label> </th>
						<td>
							<select name="volunteer_settings[registered_user_login_page]" class="regular-text">
							'.$registered_user_login_page_option_html.'
							</select>
							<p class="description">This lets the plugin know where to redirect user if they try to create account with registered email.</p>
						</td>
					</tr>
					';

					// cancel registration
					$html .= '
					<tr>
						<th scope="row"><label for="blogname">Cancel Registration Page</label></th>
						<td>
							<select name="volunteer_settings[cancel_registration_page]" class="regular-text">
							'.$cancel_registration_page_option_html.'
							</select>
							<p class="description">Select the page where registrant will be redirected to cancel their registration.
							</p>
						</td>
					</tr>';
			
					// homepage alert /  user creation
			$html .= '<tr style="border-top:1px solid">
						<!-- <th scope="row"><label for="blogname">Homepage to User Verification Page</label> </th>
						<td>
							<select name="volunteer_settings[homepage_user_verification_page]" class="regular-text">
							'.$homepage_user_verification_option_html.'
							</select>
							<p class="description">Select the page which will be link to Homepage "Get Notified of Future Cleanups" Button. This lets the plugin know where the homepage user verify form is present. Non-logged user will be redirected to this page to get register.</p>
							<p class="description">Shortcode Used in Fluent Form: <br/> volunteer_page - {embed_post.ID} <br/>
							volunteer_user_email_verify - yes </p>
						</td>
					</tr>
					<tr> -->
						<th scope="row"><label for="blogname">Homepage Alert Creation Page</label> </th>
						<td>
							<select name="volunteer_settings[homepage_alert_creation_page]" class="regular-text">
							'.$homepage_alert_creation_page_option_html.'
							</select>
							<p class="description">Select the page that will be linked to the "Get Notified of Future Cleanups" button on  homepage having ID  "volunteer_alert_notify". This allows the plugin to identify where the alert creation form for homepage page is located. Non-logged-in users will be redirected to this page to register, while logged-in users will be redirected to the Weekly Alert Page.<br/>
It will check in the background if user is registered or no, and in the background either create new user or add alert to existing user.</p>
							<p>Prerequisite
							<ol>
							<li>Button with ID "volunteer_alert_notify"</li>
							<li>Homepage Alert Creation Page should contains fluentform shortcode</li>
							<li> In fluent form: shortcode for email default value : {vol_session_user_email} <br/>
							name attribute in form for email : volunteer_email <br/>
							name attribute in form for zipcode : zipcode <br/>
							 All form Integration : New User Registration , FluentCRM Integration</li>
							</ol>
							</p>
						</td>
					</tr>';
			
					// thankyou page alert /  user creation
			$html .= '
					<tr style="border-top:1px solid">
						<th scope="row"><label for="blogname">Thankyou Page to User Verification Page </label> (First Page & Required) </th>
						<td>
							<select name="volunteer_settings[thankyou_user_verification_page]" class="regular-text">
							'.$thankyou_page_user_verification_option_html.'
							</select>
							<p class="description">Select the page that will be linked to the ThankYou Page - "Receive Weekly Alert of Cleanups Near You" button. This allows the plugin to identify where the user verification form for thankyou page is located. Non-logged-in users will be redirected to this page to register, while logged-in users will be redirected to the Weekly Alert Page.<br/>
							After submission it checks, If user exists , then form will redirect to "Registered User Login Page" else "Thankyou Alert Creation Page" <br/>
							Shortcode used in Fluent Form: `volunteer_page - {embed_post.ID}`, which will contain the current page ID which will used at the time if user gets verified.
							`volunteer_user_email_verify - yes`
							The plugin will redirect to the "Thankyou Alert Creation Page" by comparing the current page ID. </p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Thankyou Alert Creation Page</label> (Second Page & Required If first page is user verify )</th>
						<td>
							<select name="volunteer_settings[thankyou_alert_creation_page]" class="regular-text">
							'.$thankyou_alert_creation_page_option_html.'
							</select>
							<p class="description">When a user verifies their account through the "Receive Weekly Alert of Cleanups Near You" button, the plugin will determine where the alert creation form is located. The "Thankyou Alert Creation Page" will open after the "Thankyou Page to User Verification Page". <br/>
							1. First, the user will register through email confirmation.<br/>
							2. Second, an alert will be created after their registration.<br/>
							3. This page will open using the `{redirect-volunteer-plugin-settings}` shortcode if the parent `volunteer_page - {embed_post.ID}` contains the "Thankyou Page to User Verification Page" Page ID.<br/></p>
							<p>Prerequisite
							<ol>
							<li>Shortcode : [woocommerce_thankyou_alert_button] on thank you page</li>
							<li>User Verification Page should contains user verification fluentform shortcode</li>
							<li> In fluent form: shortcode for hidden field volunteer_page default value : {embed_post.ID} <br/>
							 hidden field volunteer_user_email_verify default value : yes </br>
							name attribute in form for email : user_email <br/>
							</li>
							<li> In Thankyou Alert Creation Page fluent form: shortcode for email default value : {vol_session_user_email} <br/>
							name attribute in form for email : volunteer_email <br/>
							name attribute in form for zipcode : zipcode <br/>
							 All form Integration : New User Registration , FluentCRM Integration</li>
							</ol>
							</p>
						</td>
					</tr>';
			
					// registration
					$html .= '
					<tr style="border-top:1px solid">
						<th scope="row"><label for="blogname">Create An Account to User Verification Page</label> (First Page & Required)</th>
						<td>
							<select name="volunteer_settings[create_account_user_verification_page]" class="regular-text">
							'.$create_account_page_user_verification_option_html.'
							</select>
							<p class="description">Select the page that is linked to the "Create An Account" button or menu item. This allows the plugin to identify where the user verification form for account creation is located. Non-logged-in users will be redirected to this page to register, while logged-in users will be redirected to the Weekly Alert Page.</br>
							After submission it checks, If user exists , then form will redirect to "Registered User Login Page" else "Create An Account Registration Page"<br/>
							Shortcode used in Fluent Form: `volunteer_page - {embed_post.ID}`, which will contain the current page ID which will used at the time if user gets verified.  <br/>
							`volunteer_user_email_verify - yes` <br/>
							The plugin will redirect to the "Create An Account Registration Page" by comparing the current page ID.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="blogname">Create An Account Registration Page</label>  (Second Page & Required If first page is user verify )</th>
						<td>
							<select name="volunteer_settings[create_account_registration_page]" class="regular-text">
							'.$create_account_registration_page_option_html.'
							</select>
							<p class="description">When a user verifies their account through the "Create An Account" button, the plugin will determine where the user registration form is located. The "Create An Account Registration Page" will open after the "Create An Account to User Verification Page." <br/>
1. First, the user will register through email confirmation.<br/>
2. Second, an alert will be created after their registration.<br/>
3. This page will open using the `{redirect-volunteer-plugin-settings}` shortcode if the parent `volunteer_page - {embed_post.ID}` contains the "Create An Account to User Verification Page" Page ID.<br/></p>
						</td>
					</tr>
				</tbody>
			</table>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th><input type="submit" value="Save Settings" class="button-primary" /></th>
					</tr>
				</table>
				</form>
			</div> ';
			echo $html;
	}

	// 
	function volunteer_sync_fluent_data($post_id, $post, $update){
		if(isset($_POST['action']) && ($_POST['action'] == 'fluentform_submit') && isset($_POST['form_id'])){
			return;
		}

		if(defined('FLUENTCRM') && $post->post_type == 'event_organizer'){
			$email = get_post_meta( $post_id, '_organizer_email', true );
			$user_id = $post->post_author;
			if($email){
				$profile = FluentCrmApi('contacts')->getContact($email);
				$id = $profile->id;
				// if user exists in fluentCRM
				if($id){
					$data = array();
					$data['email'] = $email;
					// data
					$data['custom_values']['fcrm_vol_bio'] = $post->post_content;
					// website
					$website = '';
					if(get_post_meta( $post_id,'_organizer_website',true)){
						$data['custom_values']['fcrm_vol_website'] = get_post_meta( $post_id,'_organizer_website',true);
					}
					// facebook
					$facebook = '';
					if(get_post_meta( $post_id,'_organizer_facebook',true)){
						$data['custom_values']['fcrm_vol_facebook'] = get_post_meta( $post_id,'_organizer_facebook',true);
					}
					// instagram
					$instagram = '';
					if(get_post_meta( $post_id,'_organizer_instagram',true)){
						$data['custom_values']['fcrm_vol_instagram'] = get_post_meta( $post_id,'_organizer_instagram',true);
					}
					// twitter
					$twitter = '';
					if(get_post_meta( $post_id,'_organizer_twitter',true)){
						$data['custom_values']['fcrm_vol_twitter'] = get_post_meta( $post_id,'_organizer_twitter',true);
					}
					// youtube
					$youtube = '';
					if(get_post_meta( $post_id,'_organizer_youtube',true)){
						$data['custom_values']['fcrm_vol_youtube'] = get_post_meta( $post_id,'_organizer_youtube',true);
					}

					// profile
					$featured_img_url = '';
					if(get_the_post_thumbnail_url($post_id, 'full')){
						//$profile = get_user_meta( $user_id,'_organizer_logo',true);
						$featured_img_url = get_the_post_thumbnail_url($post_id, 'full'); 
						$data['avatar'] = $featured_img_url;
					}
					
					FluentCrmApi('contacts')->createOrUpdate($data);
				}
			}
		}	
	}

	// wp-admin fluent CRM to organizer
	function volunteer_fluentCRM_contact_update($newValues, $subscriber, $updateValues){
		$user_email = $subscriber->email;
		if($user_email){
			$post_arr = array(
				'post_status'   => 'publish',
				'post_type' => 'event_organizer',
				'meta_key' => '_organizer_email',
				'meta_value' => $user_email,
			);
			$organizers = get_posts($post_arr);
			if($organizers){
				$id = null;
				foreach($organizers as $o){
					$id = $o->ID;
					// bio
					if(isset($newValues['fcrm_vol_bio'])){
						$organizer_post = array(
							'ID'           => $id ,
							'post_content' => $newValues['fcrm_vol_bio'],
						   );
					  
						  // Update the post into the database
						  wp_update_post( $organizer_post );
					}
					// website
					if(isset($newValues['fcrm_vol_website'])){
						update_post_meta( $id,'_organizer_website',$newValues['fcrm_vol_website']);
					}
					// facebook
					if(isset($newValues['fcrm_vol_facebook'])){
						update_post_meta( $id,'_organizer_facebook',$newValues['fcrm_vol_facebook']);
					}
					// instagram
					if(isset($newValues['fcrm_vol_instagram'])){
						update_post_meta( $id,'_organizer_instagram',$newValues['fcrm_vol_instagram']);
					}
					// twitter
					if(isset($newValues['fcrm_vol_twitter'])){
						update_post_meta( $id,'_organizer_twitter',$newValues['fcrm_vol_twitter']);
					}
					// twitter
					if(isset($newValues['fcrm_vol_youtube'])){
						update_post_meta( $id,'_organizer_youtube',$newValues['fcrm_vol_youtube']);
					}
				} // each organizer
			} //if organizers
		}// valid  user_id
	}

	// syncing  - finishes

	// Delete Event
	function volunteer_enable_event_dashboard(){
		if(isset($_GET['action']) && ( $_GET['action'] == 'delete' || $_GET['action'] == 'duplicate') && isset($_GET['event_id']) && !empty($_GET['event_id']) && isset($_GET['_wpnonce'])){
			global $post;
			$volunteer_settings = get_option('volunteer_custom_settings');
			$event_dashboard = (is_array($volunteer_settings) && isset($volunteer_settings['event_dashboard']))?$volunteer_settings['event_dashboard']:'';
			if($post->ID == $event_dashboard){
				$em_shotcode = new WP_Event_Manager_Shortcodes();
				$em_shotcode->event_dashboard_handler();
			}
		}
	}

	// Delete - finishes

	// event preview
	function volunteer_submit_event_steps($steps){
		if(isset($steps['preview'])){
			unset($steps['preview']);
		}
		return $steps;
	}

	function volunteer_submit_event_form_save_event_data($event_data, $post_title, $post_content, $status, $values){
		if(isset($event_data['post_status']) && $event_data['post_status'] == 'preview'){
			$event_data['post_status'] = 'publish';
		}
		return $event_data;
	}
}
WPEM_VOLUNTEER_2_1::instance();



	// ical
	function volunteer_single_ical_file_description(){
		global $post;
		$proid = "WP EVENT Manager Ical";
		$file_name = "event-calendar" . date("Y-m-d h:i") . ".ics";
		$file_name = apply_filters('wp_event_manager_single_ical_file_name', $file_name, $post);

		ob_start();
		
		// - file header -
		header('Content-type: text/calendar');
		header('Content-Disposition: attachment; filename="' . $file_name . '" ');
		// - content header -
		$startdate = strtr(get_post_meta($post->ID, '_event_start_date', true), '/', '-');
		$enddate   = strtr(get_post_meta($post->ID, '_event_end_date', true), '/', '-');
		if(empty($enddate)){
			$endtime = strtr(get_post_meta($post->ID, '_event_end_time', true), '/', '-');
			if(!empty($endtime))
				$enddate = date("Y-m-d", strtotime($startdate)).' '.$endtime;
			else
				$enddate = $startdate;
		}

		$location   = get_post_meta($post->ID, '_event_location', true) ? get_post_meta($post->ID, '_event_location', true) : 'Online';
	
		$start_date = date("Ymd\THis", strtotime($startdate));
		$end_date = date("Ymd\THis", strtotime($enddate));
		$timezone = get_post_meta($post->ID,'_event_timezone', true);
		//$start_date = date("Ymd\THis", get_gmt_date_time($startdate))."Z";
		//$end_date = date("Ymd\THis", get_gmt_date_time($enddate)) ."Z";
		$content = apply_filters('the_content', $post->post_content); 
		$description =  apply_filters('wp_event_manager_single_ical_file_description', str_replace(array("\r", "\n"), '', strip_tags(htmlspecialchars_decode($content))), $post);
		// URL 
		$url = str_replace('https://','www.', get_permalink($post->ID));
		$new_content = "EVENT - ".html_entity_decode( get_the_title() ) . "\\n";
		$new_content .= "Start Date Time - ".(  date("m-d-Y T", strtotime($startdate)) ) . " at ".( get_event_start_time($post->ID) ) . " \\n ";
		$new_content .= "End Date Time - ".(  date("m-d-Y T", strtotime($enddate)) ) . " at ".( get_event_end_time($post->ID) ) . " \\n ";
		$new_content .= "Location - ".(  $location ) . " \\n ";
		$new_content .= "Event Page - ".(  $url ) . " \\n ";
		// Organizer 
		$organizer_ids = get_post_meta($post->ID,'_event_organizer_ids',true);
		$organizer_name = $organizer_title = '';
		if($organizer_ids){
			foreach($organizer_ids as $id){
				$organizer_post = get_post($id);
				if($organizer_post){
					$organizer_name = get_post_meta($id,'_organizer_email',true).' ';
					$organizer_title = $organizer_post->post_title.' ';
				}
			}
			$new_content .= "Organizer Name - ".(trim($organizer_title))." \\n ";
			$new_content .= "Organizer Email - ".(trim($organizer_name))." \\n ";
		}
		
		$what_attendees_should_bring = get_post_meta($post->ID,'_what_should_volunteers_bring?',true);
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
			$new_content .= "What should Volunteers Bring - ".(trim($bring))." \\n ";
		}

		if(get_post_meta($post->ID,'_meeting_spot_details',true)){	
			$new_content .= "Meeting Spot Details - ".(get_post_meta($post->ID,'_meeting_spot_details',true))." \\n ";
		}
		//$new_content = $new_content."Description - ".$description;
		$description =  str_replace(':','-',$new_content);
		// - item output -
		?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//<?php echo $proid; ?>//NONSGML Events //EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:<?php echo $timezone. "\n"; ?>
X-LIC-LOCATION:<?php echo $timezone. "\n"; ?>
BEGIN:DAYLIGHT
TZOFFSETFROM:-0500
TZOFFSETTO:-0400
TZNAME:EDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0400
TZOFFSETTO:-0500
TZNAME:EST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
DTSTART;TZID=<?php echo $timezone ?>:<?php echo $start_date . "\n";?>
DTEND;TZID=<?php echo $timezone ?>:<?php echo $end_date . "\n";?>
SUMMARY:<?php echo html_entity_decode( get_the_title() ) . "\n"; ?>
DESCRIPTION:<?php echo $description . "\n"; ?>
LOCATION:<?php echo $location . "\n"; ?>
URL:<?php echo $url . "\n"; ?>
BEGIN:VALARM
TRIGGER:-PT60M
REPEAT:1
ACTION:DISPLAY
DESCRIPTION:Reminder
END:VALARM
END:VEVENT
END:VCALENDAR<?php		$eventcontents = ob_get_contents();
		ob_end_clean();
		echo $eventcontents;
	}