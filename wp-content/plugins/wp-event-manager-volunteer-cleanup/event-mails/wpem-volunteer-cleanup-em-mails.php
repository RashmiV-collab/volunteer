<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main
 *
 * The main class that initiates and runs the plugin.
 *
 * @since 1.0.0
 */
final class WPEM_VOLUNTEER_EM_MAILS {
	
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
		// add mail to 
		add_action('admin_menu', [$this, 'add_mail_sub_menu'],100);
		add_filter('event_registration_email_tags',[$this,'volunteer_er_email_tags'],10,2);
		add_action( 'event_registration_email_add_shortcodes', [$this,'volunteer_new_event_email_add_shortcodes'],10,1);
	
		// volunteer autoresponse
		add_filter('event_registration_attendee_email_content',[$this, 'volunteer_autoresponse_email_content']);
		add_filter('event_registration_attendee_email_subject',[$this, 'volunteer_autoresponse_subject_content']);
		// orgaization confirmation
		add_filter('event_registration_organizer_email_content',[$this, 'volunteer_orgaization_confirmation_email_content']);
		add_filter('event_registration_organizer_email_subject',[$this, 'volunteer_orgaization_confirmation_subject_content']);
		// remove pdf
		if(!get_option('wpem_registration_checkin_attendee_email_attach')){
			update_option('wpem_registration_checkin_attendee_email_attach', '');
		}
		if(!get_option('event_registration_attendee_email_attach')){
			update_option('event_registration_attendee_email_attach', '');
		}
		add_filter( 'option_event_registration_attendee_email_attach', function( $value ){
			return '';
		});
		add_filter( 'option_wpem_registration_checkin_attendee_email_attach', function( $value ){
			return '';
		});
		// remove pdf - finishes
		//2 Emails to host on every registration order
		add_action('init', [$this,'volunteer_disable_send_notification']);
		add_filter( 'wpem_email_from_name', [$this,'volunteer_wpem_email_from_name'],10,1);

		// Cancel my RSVP (no user account)
		add_shortcode('volunteer-cancel-registration-form',[$this,'volunteer_cancel_registration_form']);
    }

	function volunteer_wpem_email_from_name($from_name){
		return 'Volunteer Cleanup ';
    }

	function volunteer_disable_send_notification(){
		remove_action('transition_post_status', 'send_notification', 10);
	}

    function add_mail_sub_menu(){
        $em_mail_settings = add_submenu_page(
			'volunteer-event-alert',
			'Mail Settings',
			'Mail Settings',
			'manage_options',
			'em-mail-settings',
			[$this, 'volunteer_em_mail_settings']
		  );

		add_action('load-' . $em_mail_settings, array($this,'volunteer_admin_em_mail_css' ));
    }

    function volunteer_em_mail_settings(){

		$tabs = array(
			'volunteer-autoresponse'  => 'Auto-response to Volunteer', 
			'organizer-confirmation' => 'Organizer Confirmation', 
			'rsvp-cancellation' => 'RSVP Cancellation',
			'rsvp-cancel-organizer' => 'RSVP Cancel Inform to Organizer'
		);
		$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'volunteer-autoresponse';?>
		<div class="wrap wp-event-manager-registrations-form-editor">
            <h1 class="wp-heading-inline"><?php echo $tabs[$tab]; ?></h1>
			<div class="wpem-wrap wp-event-manager-registrations-form-editor" id="volunteer-email-templates">
				<h2 class="nav-tab-wrapper">
					<?php
					foreach($tabs as $key => $value) {
						$active = ($key == $tab) ? 'nav-tab-active' : '';
						echo '<a class="nav-tab ' . $active . '" href="' . admin_url('admin.php?page=em-mail-settings&tab=' . esc_attr($key)) . '">' . esc_html($value) . '</a>';
					} ?>
				</h2>
				<form method="post" id="mainform" action="admin.php?page=em-mail-settings&amp;tab=<?php echo esc_attr($tab); ?>">
					<?php
					switch ($tab) {
						case 'organizer-confirmation' :
							$this->organizer_confirmation_email();
							break;
						case 'rsvp-cancellation' :
							$this->rsvp_cancellation_email();
							break;
						case 'rsvp-cancel-organizer' :
							$this->rsvp_cancel_organizer_email();
							break;
						default :
							$this->volunteer_autoresponse_email();
							break;
					} ?>
					<?php wp_nonce_field('save-' . $tab); ?>
				</form>
			</div>
		</div>
		<?php

    }

    function volunteer_admin_em_mail_css(){
     //   add_action( 'admin_enqueue_scripts', array($this,'volunteer_enqueue_em_menu_js' ));
		//add_action('admin_footer', [$this, 'volunteer_mail_js']);
    }

	function volunteer_enqueue_em_menu_js(){
		wp_enqueue_script( 'volunteer_tinymce_script', WPEM_VOLUNTEER_URI.'assets/tinymce/tinymce.min.js', array('jquery'), '', true );
	}

	
	function volunteer_mail_js(){
		?>
		<script>
			jQuery(document).ready(function(){
			console.log(jQuery('#volunteer-email-templates textarea'))
				tinymce.init({
					selector: "textarea",
					plugins: "table code advtable lists fullscreen", 
       toolbar: "undo redo | blocks | bold italic | " +
                "alignleft aligncenter alignright alignjustify | indent outdent | " +
                "table tableinsertdialog tablecellprops tableprops advtablerownumbering | fullscreen",
       content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }'
				});
			});
		</script>
		<?php
	}

	// organization confirmation
	function organizer_confirmation_email(){
		if(!empty($_GET['reset-email']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reset')) {
			delete_option('em_volunteer_organizer_confirmation_email_content');
			delete_option('em_volunteer_organizer_confirmation_email_subject');
			echo '<div class="updated"><p>' . __('The email was successfully reset.', 'wp-event-manager-registrations') . '</p></div>';
		}
		if(!empty($_POST) && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save-organizer-confirmation')) {
			echo $this->organizer_confirmation_editor_save();
		} 
		
		$subject = (get_option('em_volunteer_organizer_confirmation_email_subject')?esc_attr(stripslashes(get_option('em_volunteer_organizer_confirmation_email_subject'))):'');
		$content =  (get_option('em_volunteer_organizer_confirmation_email_content')?stripslashes(get_option('em_volunteer_organizer_confirmation_email_content')) : '');
	?>
		<div class="wp-event-registrations-email-content-wrapper">	
             <div class="admin-setting-left">			     	
			      <div class="white-background">
			      	<p><?php _e('Below you will find the email that is sent to an Organizer after a attendee submits an registration.', 'wp-event-manager-registrations'); ?></p>
			        <div class="wp-event-registrations-email-content">
    					<p>
    					   <input type="text" name="email-subject" value="<?php echo $subject ?>" placeholder="<?php echo esc_attr(__('Subject', 'wp-event-manager-registrations')); ?>" />
    				    </p>
    					<p>
    						<?php /*<textarea name="email-content" cols="71" rows="10"><?php echo $content; ?></textarea>*/
							 wp_editor( $content, 'email_content' );
							?>
    				    </p>
				     </div>
			     </div>	<!--white-background-->		       
			</div>	<!--	admin-setting-left-->  	
			<div class="box-info">
			   <div class="wp-event-registrations-email-content-tags">
				<p><?php _e('The following tags can be used to add content dynamically:', 'wp-event-manager-registrations'); ?></p>
				<ul>
					<?php foreach (get_event_registration_email_tags() as $tag => $name) : ?>
						<li><code>[<?php echo esc_html($tag); ?>]</code> - <?php echo wp_kses_post($name); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php _e('All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[event_title prefix="Event Title: " suffix="."]</code>', 'wp-event-manager-registrations'); ?></p>
			   </div>
		    </div> <!--box-info--> 
		</div>
		<p class="submit-email save-actions">
			<a href="<?php echo wp_nonce_url(add_query_arg('reset-email', 1), 'reset'); ?>" class="reset"><?php _e('Reset to defaults', 'wp-event-manager-registrations'); ?></a>
			<input type="submit" class="save-email button-primary" value="<?php _e('Save Changes', 'wp-event-manager-registrations'); ?>" />
		</p>
		<?php
	}

	/**
	 * Save the email
	 */
	private function organizer_confirmation_editor_save() {
		$email_content = $_POST['email_content'];
		$email_subject = sanitize_text_field(wp_unslash($_POST['email-subject']));
		$result        = update_option('em_volunteer_organizer_confirmation_email_content', $email_content);
		$result2       = update_option('em_volunteer_organizer_confirmation_email_subject', $email_subject);
		if(true === $result || true === $result2) {
			echo '<div class="updated"><p>' . __('The email was successfully saved.', 'wp-event-manager-registrations') . '</p></div>';
		}
	}

	// organization confirmation  - ends

	// volunteer autoresponse
	function volunteer_autoresponse_email(){
		if(!empty($_GET['reset-email']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reset')) {
			delete_option('em_volunteer_response_email_content');
			delete_option('em_volunteer_response_email_subject');
			echo '<div class="updated"><p>' . __('The email was successfully reset.', 'wp-event-manager-registrations') . '</p></div>';
		}
		//var_dump(wp_verify_nonce($_POST['_wpnonce'], 'save-volunteer-response'));exit;
		if(!empty($_POST) && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save-volunteer-autoresponse')) {
			echo $this->volunteer_response_editor_save();
		} 
		
		$subject = (get_option('em_volunteer_response_email_subject')?esc_attr(stripslashes(get_option('em_volunteer_response_email_subject'))):'');
		$content =  (get_option('em_volunteer_response_email_content')?stripslashes(get_option('em_volunteer_response_email_content',true)) : '');
		
		?>
		<div class="wp-event-registrations-email-content-wrapper">	
             <div class="admin-setting-left">			     	
			      <div class="white-background">
			      	<p><?php _e('Below you will find the email that is sent to an Organizer after a attendee submits an registration.', 'wp-event-manager-registrations'); ?></p>
			        <div class="wp-event-registrations-email-content">
    					<p>
    					   <input type="text" name="email-subject" value="<?php echo $subject; ?>" placeholder="<?php echo esc_attr(__('Subject', 'wp-event-manager-registrations')); ?>" />
    				    </p>
    					<p>
    						<?php /*<textarea name="email-content" cols="71" rows="10"><?php echo $content; ?></textarea>*/
							 wp_editor( $content, 'email_content' );
							 ?>
    				    </p>
				     </div>
			     </div>	<!--white-background-->		       
			</div>	<!--	admin-setting-left-->  	
			<div class="box-info">
			   <div class="wp-event-registrations-email-content-tags">
				<p><?php _e('The following tags can be used to add content dynamically:', 'wp-event-manager-registrations'); ?></p>
				<ul>
					<?php foreach (get_event_registration_email_tags() as $tag => $name) : ?>
						<li><code>[<?php echo esc_html($tag); ?>]</code> - <?php echo wp_kses_post($name); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php _e('All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[event_title prefix="Event Title: " suffix="."]</code>', 'wp-event-manager-registrations'); ?></p>
			   </div>
		    </div> <!--box-info--> 
		</div>
		<p class="submit-email save-actions">
			<a href="<?php echo wp_nonce_url(add_query_arg('reset-email', 1), 'reset'); ?>" class="reset"><?php _e('Reset to defaults', 'wp-event-manager-registrations'); ?></a>
			<input type="submit" class="save-email button-primary" value="<?php _e('Save Changes', 'wp-event-manager-registrations'); ?>" />
		</p>
		<?php
	}

	/**
	 * Save the email
	 */
	private function volunteer_response_editor_save() {
		$email_content = $_POST['email_content'];
		$email_subject = sanitize_text_field(wp_unslash($_POST['email-subject']));
		$result        = update_option('em_volunteer_response_email_content', $email_content);
		$result2       = update_option('em_volunteer_response_email_subject', $email_subject);		
		if(true === $result || true === $result2) {
			echo '<div class="updated"><p>' . __('The email was successfully saved.', 'wp-event-manager-registrations') . '</p></div>';
		}
	}

	// organization confirmation  - ends

	function volunteer_er_email_tags($tags){
		$new_tags = array(
			'volunteer_event_meta_data' => __('Event Date in M/D/Y format - _event_start_date / _event_end_date. 12H format and time zone - _event_start_time / _event_end_time. More - _address , _volunteer_bring , _event_type , _cancel_reg_link , _ical , admin_edit_event. e.g. <code>[volunteer_event_meta_data key="_address"]</code>', 'wp-event-manager-registrations'),

			'volunteer_registration_meta_data' => __('Attendee Phone - _attendee_phone  , _needs_community_service_hours. e.g. <code>[volunteer_registration_meta_data key="_attendee_phone"]</code>', 'wp-event-manager-registrations'),

			'volunteer_registration_cancel_link' => __('Link which goes to a confirmation page to cancel registration. Set Cancel Registration page over Volunteer Settings</code>', 'wp-event-manager-registrations'),
		);
		$tags = array_merge($tags,$new_tags);
		return $tags;
	}

	function volunteer_new_event_email_add_shortcodes($data){
		
		extract( $data );

		add_shortcode( 'volunteer_event_meta_data', function( $atts, $content = '' ) use( $event_id ) {
			$atts  = shortcode_atts(array('key' => ''), $atts);
			$val = '';
			switch($atts['key']){
				case '_event_start_date':
					$date = get_post_meta($event_id,'_event_start_date' , true);
				//	print_R($date);exit;
					if($date){
						$new_date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
						if($new_date){
							$val = $new_date->format('m-d-Y');
						}
					}
				break;
				case '_event_end_date':
					$date = get_post_meta($event_id,'_event_end_date' , true);
					if($date){
						$new_date = DateTime::createFromFormat('Y-m-d H:i:s', $date);
						if($new_date){
							$val = $new_date->format('m-d-Y');
						}
					}
				break;
				case '_address':
					$address = array();
					if(get_post_meta($event_id,'_event_location' , true)){
						$address[] = get_post_meta($event_id,'_event_location' , true);
					}
					if(get_post_meta($event_id,'_event_country' , true)){
						$address[] = strtoupper(get_post_meta($event_id,'_event_country' , true));
					}
					if(get_post_meta($event_id,'_event_pincode' , true)){
						$address[] = get_post_meta($event_id,'_event_pincode' , true);
					}
					$val = implode(", ",$address);
				break;
				case '_event_start_time':
					$time = get_post_meta($event_id,'_event_start_time' , true);
					if($time){
						//$date = new DateTime($time, new DateTimeZone(date_default_timezone_get()));
						//$date->setTimezone(new DateTimeZone('US/Eastern'));
						$date = new DateTime($time);
						if($date){
							$val = $date->format('h:ia ') . "EST";
						}
					}
				break;
				case '_event_end_time':
					$time = get_post_meta($event_id,'_event_end_time' , true);
					if($time){
					//	$date = new DateTime($time, new DateTimeZone(date_default_timezone_get()));
						//$date->setTimezone(new DateTimeZone('US/Eastern'));
						$date = new DateTime($time);
						if($date){
							$val = $date->format('h:ia ') . "EST";
						}
					}
				break;
				case '_volunteer_bring':
					$values = get_post_meta($event_id,'_what_should_volunteers_bring?' , true);
					if($values){
						$val = ucwords(str_replace("_"," ",implode(", ",get_post_meta($event_id,'_what_should_volunteers_bring?',true))));
					}
				break;
				case '_event_type':
					$values = get_post_meta($event_id,'_private_event' , true);
					if($values){
						$val = 'Private Event';
					}else{
						$val = 'Public Event';
					}
				break;
				case '_cancel_reg_link':
				break;
				case '_ical':
					$url = apply_filters('wpem_iCal_single_event_custom_link', (get_permalink($event_id).'?feed=single-event-listings-ical'));
					$val = '<a href="'.($url).'" value="Download Events iCal" class="wpem-icon-text-button" download>
						<span>
							<i class="wpem-icon-ical wpem-mr-1"></i>Download Events iCal
						</span>
					</a>';
				break;

				case '_admin_edit_event':
					$url =  admin_url().'post.php?post='.$event_id.'&action=edit';
					$val = '<a href="'.($url).'" value="Edit Admin Post" class="wpem-icon-text-button">
						<span>
							Edit Admin Post
						</span>
					</a>';
				break;	
			}
			if(!empty($val)){
				return $val;
			}
		});
		
		add_shortcode( 'volunteer_registration_meta_data', function( $atts, $content = '' ) use( $registration_id ) {
			$atts  = shortcode_atts(array('key' => ''), $atts);			
			$val = '';
			switch($atts['key']){
				case '_attendee_phone':
						$values = get_post_meta($registration_id,'_attendee_phone' , true);
						if($values){
							$val = $values;
					  }
				break;

				case '_needs_community_service_hours':
					$val = 0;
					$values = get_post_meta($registration_id,'_needs_community_service_hours' , true);
					if($values){
						$val = $values;
					}
				break;	
			}
			if(!empty($val)){
				return $val;
			}

		});

		add_shortcode( 'volunteer_registration_cancel_link', function( $atts, $content = '' ) use( $registration_id ) {
			$val = '';
			//
			$registration = get_post($registration_id);
			$volunteer_settings = get_option('volunteer_custom_settings');
			$cancel_registration_page = (is_array($volunteer_settings) && isset($volunteer_settings['cancel_registration_page']))?$volunteer_settings['cancel_registration_page']:'';
			//
			if(!empty($registration) && $cancel_registration_page){
				global $wpdb;
				if(isset($registration->post_type) && $registration->post_type !== 'event_registration'){
					return;
				}
				$table_name = $wpdb->prefix.'wpevents_cancel_registration_log';
				$row = $wpdb->get_results( "SELECT * FROM $table_name WHERE registration_id = " . $registration_id , ARRAY_A);
				if($row && isset($row[0])){
					$row = $row[0];
					$token = $row['token'];
					$val = get_permalink($cancel_registration_page).'?token='.$token;
				}else{
					$event_id = $registration->post_parent;
					$m_time = microtime();
					$ip = $_SERVER['REMOTE_ADDR'];
					$token = md5($ip .$registration_id. $m_time . rand(0, time()));
					$data = array('event_id' =>$event_id, 'registration_id' => $registration_id,'token' =>$token,'status'=>0) ;
					$result = $wpdb->insert($table_name, $data);
					//
					if($result){
						$val = get_permalink($cancel_registration_page).'?token='.$token;
					}

				}
			}
			if(!empty($val)){
				return $val;
			}


		});
	}

	function volunteer_autoresponse_email_content($template){
		$new_template = do_shortcode(get_option('em_volunteer_response_email_content')?stripslashes(get_option('em_volunteer_response_email_content')):'');
		if($new_template != ''){
			return $new_template;
		}
		return $template;
	}

	function volunteer_autoresponse_subject_content($template){
		$new_template = do_shortcode(get_option('em_volunteer_response_email_subject')?(stripslashes(get_option('em_volunteer_response_email_subject'))):'');
		if($new_template != ''){
			return $new_template;
		}
		return $template;
	}

	function volunteer_orgaization_confirmation_email_content($template){
		$new_template = do_shortcode(get_option('em_volunteer_organizer_confirmation_email_content')?stripslashes(get_option('em_volunteer_organizer_confirmation_email_content')):'');
		if($new_template != ''){
			return $new_template;
		}
		return $template;
	}

	function volunteer_orgaization_confirmation_subject_content($template){
		$new_template = do_shortcode(get_option('em_volunteer_organizer_confirmation_email_subject')?(stripslashes(get_option('em_volunteer_organizer_confirmation_email_subject'))):'');
		if($new_template != ''){
			return $new_template;
		}
		return $template;
	}

	function volunteer_cancel_registration_form(){
		$html = '<div class="volunteer_cancel_registration_form"><div class="invalid _token">Invalid Token</div></div>';

		if(!empty($_REQUEST) && isset($_REQUEST['token'])){
			$token = $_REQUEST['token'];
			//
			if(!empty($_POST) && isset($_POST['volunteer_cancel_registration_button']) && isset($_POST['volunteer_cancel_registration_token']) && ($_POST['volunteer_cancel_registration_token'] == $token)){
				$response = $this->volunteer_cancel_the_event_registration($token);
				if($response){
					$html = '<div class="volunteer_cancel_registration_form"><div class="success_message">Your registration has been successfully cancelled.</div></div>';
				}else{
					$html = '<div class="volunteer_cancel_registration_form"><div class="failure_message">An issue occurred while attempting to cancel the registration. Please contact the organizer for assistance.</div></div>';
				}
			}
			//
			global $wpdb;
			$table_name = $wpdb->prefix.'wpevents_cancel_registration_log';
			$row = $wpdb->get_results( "SELECT * FROM $table_name WHERE token LIKE '" . $token ."' and status = 0 ", ARRAY_A);
			if($row){
				$html = '<div class="volunteer_cancel_registration_form"><div class="cancelation_form">
					<div>Are you sure you want to cancel your RSVP?</div>
					<div class="cancelation_buttons">
						<form method="post"> 
							<input type="hidden" name="volunteer_cancel_registration_token" value="'.($_REQUEST['token']).'"/>
							<input type="submit" name="volunteer_cancel_registration_button"  value="Yes, cancel">
							<input type="submit" name="volunteer_no_cancel_registration_button"  value="Never mind, don’t cancel">
						</form>
					</div>
					</div>
				</div>';
			}
		}
		return $html;
	}

	function volunteer_cancel_the_event_registration($token){
		//
		$response = false;
		global $wpdb;
		$table_name = $wpdb->prefix.'wpevents_cancel_registration_log';
		$row = $wpdb->get_results( "SELECT * FROM $table_name WHERE token LIKE '" . $token ."' and status = 0 ", ARRAY_A);
		if(isset($row[0])){
			$register_row = $row[0];
			$response = false;
			$registration_id = $register_row['registration_id'];
			$ID = $register_row['ID'];
			$registrant_meta = get_post_meta( $registration_id );
			if(isset($registrant_meta['_ticket_id']) && !empty($registrant_meta['_ticket_id'][0])){
				$ticket_id_arr = unserialize($registrant_meta['_ticket_id'][0]);
				if(is_array($ticket_id_arr) && !empty($ticket_id_arr[0])){
					$product_id = $ticket_id_arr[0];
					// 1. Updating the stock quantity
					$stock     = (int)get_post_meta($product_id, '_stock', true);
					$total_sales = (int)get_post_meta($product_id, 'total_sales', true);
					update_post_meta($product_id, '_stock', ($stock + 1));
					update_post_meta($product_id, 'total_sales', ($total_sales - 1));

					// 2. changing registration status to cancel
					$registration_post = array(
						'ID'            => $registration_id,
						'post_status'   => 'cancelled',
					);
					wp_update_post( $registration_post );
					update_post_meta($registration_id, 'canceled_date', date('Y-m-d H:i:s'));
					$ip = $_SERVER['REMOTE_ADDR'];
					update_post_meta($registration_id, 'cancel_by', 'token IP : '.$ip);

					// 3. update registration log
					$result_update = $wpdb->query("UPDATE $table_name SET `status`= 1 WHERE registration_id = $registration_id AND ID = $ID");
					if($result_update){
						$this->send_rsvp_cancellation_mails($registration_id);
						return true;
					}					
				}
			}
			
		}
		return $response;
	}

	private function send_rsvp_cancellation_mails($registration_id){

		$registration = get_post($registration_id);
		if(!empty($registration)){
			if(isset($registration->post_type) && $registration->post_type !== 'event_registration')
				return;
			
			$event_id = $registration->post_parent;
			$event = get_post($event_id);
			$attendee_name = $registration->post_title;
			$attendee_email = get_post_meta($registration_id, '_attendee_email', true);
			$registration_fields = get_event_registration_form_fields();
			$meta = [];
			foreach ($registration_fields as $key => $field) {
				$value = get_post_meta($registration_id, $key, true);
				if(!empty($value))
					$meta[$key] = $value;   
			}
			if(empty($meta)){
				$meta = $_REQUEST;
			}
			$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
			remove_all_shortcodes();
			event_registration_email_add_shortcodes(array(
				'registration_id'       => $registration_id, 
				'event_id'              => $event_id, 
				'user_id'               => $registration->post_author, 
				'attendee_name'         => $attendee_name, 
				'attendee_email'        => $attendee_email, 
				'meta'                  => $meta
			));
			// rsvp-cancellation
			$message = do_shortcode(stripslashes(get_option('em_volunteer_rsvp_cancellation_email_content')));
			$subject = do_shortcode(stripslashes(get_option('em_volunteer_rsvp_cancellation_email_subject')));
			if($message != '' && $subject != ''){
				wpem_send_registration_email($attendee_email, $subject, $message, $existing_shortcode_tags, 'rsvp-cancellation', $registration_id, $attendee_email, $attendee_name);
			}

			// rsvp-cancel-organizer
			$organizer_info = get_oragnizer_registration_email_notification($event_id, $event);
			if(isset($organizer_info) && !empty($organizer_info)) {
				$message = do_shortcode(stripslashes(get_option('em_volunteer_rsvp_cancel_organizer_email_content')));
				$subject = do_shortcode(stripslashes(get_option('em_volunteer_rsvp_cancel_organizer_email_subject')));
				if($message != '' && $subject != ''){
					wpem_send_registration_email($organizer_info['organizer_email'], $subject, $message, $existing_shortcode_tags, 'rsvp-cancel-organizer', $registration_id, $attendee_email, $attendee_name);
				}
			}
			
			$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;
		}	

	}

	// rsvp-cancellation
	function rsvp_cancellation_email(){
		if(!empty($_GET['reset-email']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reset')) {
			delete_option('em_volunteer_rsvp_cancellation_email_content');
			delete_option('em_volunteer_rsvp_cancellation_email_subject');
			echo '<div class="updated"><p>' . __('The email was successfully reset.', 'wp-event-manager-registrations') . '</p></div>';
		}
		if(!empty($_POST) && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save-rsvp-cancellation')) {
			echo $this->rsvp_cancellation_editor_save();
		} 
		
		$subject = (get_option('em_volunteer_rsvp_cancellation_email_subject')?esc_attr(stripslashes(get_option('em_volunteer_rsvp_cancellation_email_subject'))):'RSVP Cancel');
		$content = (get_option('em_volunteer_rsvp_cancellation_email_content')?stripslashes(get_option('em_volunteer_rsvp_cancellation_email_content')) : 'RSVP has been cancel');
	?>
		<div class="wp-event-registrations-email-content-wrapper">	
             <div class="admin-setting-left">			     	
			      <div class="white-background">
			      	<p><?php _e('Below you will find the email that is sent to an Volunteer after their confirmation to cancel the registration.', 'wp-event-manager-registrations'); ?></p>
			        <div class="wp-event-registrations-email-content">
    					<p>
    					   <input type="text" name="email-subject" value="<?php echo $subject ?>" placeholder="<?php echo esc_attr(__('Subject', 'wp-event-manager-registrations')); ?>" />
    				    </p>
    					<p>
    						<?php /*<textarea name="email-content" cols="71" rows="10"><?php echo $content; ?></textarea>*/
							 wp_editor( $content, 'email_content' );
							?>
    				    </p>
				     </div>
			     </div>	<!--white-background-->		       
			</div>	<!--	admin-setting-left-->  	
			<div class="box-info">
			   <div class="wp-event-registrations-email-content-tags">
				<p><?php _e('The following tags can be used to add content dynamically:', 'wp-event-manager-registrations'); ?></p>
				<ul>
					<?php foreach (get_event_registration_email_tags() as $tag => $name) : ?>
						<li><code>[<?php echo esc_html($tag); ?>]</code> - <?php echo wp_kses_post($name); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php _e('All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[event_title prefix="Event Title: " suffix="."]</code>', 'wp-event-manager-registrations'); ?></p>
			   </div>
		    </div> <!--box-info--> 
		</div>
		<p class="submit-email save-actions">
			<a href="<?php echo wp_nonce_url(add_query_arg('reset-email', 1), 'reset'); ?>" class="reset"><?php _e('Reset to defaults', 'wp-event-manager-registrations'); ?></a>
			<input type="submit" class="save-email button-primary" value="<?php _e('Save Changes', 'wp-event-manager-registrations'); ?>" />
		</p>
		<?php
	}

	/**
	 * Save the email
	 */
	private function rsvp_cancellation_editor_save() {
		$email_content = $_POST['email_content'];
		$email_subject = sanitize_text_field(wp_unslash($_POST['email-subject']));
		$result        = update_option('em_volunteer_rsvp_cancellation_email_content', $email_content);
		$result2       = update_option('em_volunteer_rsvp_cancellation_email_subject', $email_subject);
		if(true === $result || true === $result2) {
			echo '<div class="updated"><p>' . __('The email was successfully saved.', 'wp-event-manager-registrations') . '</p></div>';
		}
	}

	function volunteer_rsvp_cancellation_email_content(){
		$template = do_shortcode(get_option('em_volunteer_rsvp_cancellation_email_content')?stripslashes(get_option('em_volunteer_rsvp_cancellation_email_content')):'');
		if($template != ''){
			return $new_template;
		}
		return '';
	}

	function volunteer_rsvp_cancellation_subject_content(){
		$template = do_shortcode(get_option('em_volunteer_rsvp_cancellation_email_subject')?(stripslashes(get_option('em_volunteer_rsvp_cancellation_email_subject'))):'');
		if($template != ''){
			return $template;
		}
		return '';
	}

	// rsvp-cancellation  - ends

	// rsvp-cancel-organizer

	function rsvp_cancel_organizer_email(){
		if(!empty($_GET['reset-email']) && !empty($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'reset')) {
			delete_option('em_volunteer_rsvp_cancel_organizer_email_content');
			delete_option('em_volunteer_rsvp_cancel_organizer_email_subject');
			echo '<div class="updated"><p>' . __('The email was successfully reset.', 'wp-event-manager-registrations') . '</p></div>';
		}
		if(!empty($_POST) && !empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'save-rsvp-cancel-organizer')) {
			echo $this->rsvp_cancel_organizer_editor_save();
		} 
		
		$subject = (get_option('em_volunteer_rsvp_cancel_organizer_email_subject')?esc_attr(stripslashes(get_option('em_volunteer_rsvp_cancel_organizer_email_subject'))):'Volunteer Cancel the registration');
		$content = (get_option('em_volunteer_rsvp_cancel_organizer_email_content')?stripslashes(get_option('em_volunteer_rsvp_cancel_organizer_email_content')) : 'Volunteer cancel the registration.');
	?>
		<div class="wp-event-registrations-email-content-wrapper">	
             <div class="admin-setting-left">			     	
			      <div class="white-background">
			      	<p><?php _e('Below you will find the email that is sent to an Organizer after volunteer confirms to cancel the registration.', 'wp-event-manager-registrations'); ?></p>
			        <div class="wp-event-registrations-email-content">
    					<p>
    					   <input type="text" name="email-subject" value="<?php echo $subject ?>" placeholder="<?php echo esc_attr(__('Subject', 'wp-event-manager-registrations')); ?>" />
    				    </p>
    					<p>
    						<?php /*<textarea name="email-content" cols="71" rows="10"><?php echo $content; ?></textarea>*/
							 wp_editor( $content, 'email_content' );
							?>
    				    </p>
				     </div>
			     </div>	<!--white-background-->		       
			</div>	<!--	admin-setting-left-->  	
			<div class="box-info">
			   <div class="wp-event-registrations-email-content-tags">
				<p><?php _e('The following tags can be used to add content dynamically:', 'wp-event-manager-registrations'); ?></p>
				<ul>
					<?php foreach (get_event_registration_email_tags() as $tag => $name) : ?>
						<li><code>[<?php echo esc_html($tag); ?>]</code> - <?php echo wp_kses_post($name); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php _e('All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[event_title prefix="Event Title: " suffix="."]</code>', 'wp-event-manager-registrations'); ?></p>
			   </div>
		    </div> <!--box-info--> 
		</div>
		<p class="submit-email save-actions">
			<a href="<?php echo wp_nonce_url(add_query_arg('reset-email', 1), 'reset'); ?>" class="reset"><?php _e('Reset to defaults', 'wp-event-manager-registrations'); ?></a>
			<input type="submit" class="save-email button-primary" value="<?php _e('Save Changes', 'wp-event-manager-registrations'); ?>" />
		</p>
		<?php
	}

	/**
	 * Save the email
	 */
	private function rsvp_cancel_organizer_editor_save() {
		$email_content = $_POST['email_content'];
		$email_subject = sanitize_text_field(wp_unslash($_POST['email-subject']));
		$result        = update_option('em_volunteer_rsvp_cancel_organizer_email_content', $email_content);
		$result2       = update_option('em_volunteer_rsvp_cancel_organizer_email_subject', $email_subject);
		if(true === $result || true === $result2) {
			echo '<div class="updated"><p>' . __('The email was successfully saved.', 'wp-event-manager-registrations') . '</p></div>';
		}
	}

	// rsvp-cancel-organizer  - ends

}

WPEM_VOLUNTEER_EM_MAILS::instance();