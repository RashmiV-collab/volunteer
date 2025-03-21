<?php
/**
 * WPEM_Emails_Notifications class.
 */
class WPEM_Emails_Notifications {
    /**
	 * Constructor
	 */
	public function __construct() {
	    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
	}

	/**
	 * Add form editor menu item
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=event_listing', __( 'Email Notifications', 'wp-event-manager-emails' ),  __( 'Email Notifications', 'wp-event-manager-emails' ) , 'manage_options', 'event-emails-notifications', array( $this, 'output' ) );
	}

	/**
     * Register scripts
     */
    public function admin_enqueue_scripts() {
        wp_register_style( 'wp-event-manager-emails-admin', WPEM_EMAILS_PLUGIN_URL . '/assets/css/admin.css', '', WPEM_EMAILS_VERSION );
    }

    /**
	 * Output email templates the screen
	 */
	public function output_email_templates() {
		echo 'email templates';
	}
		
	/**
	 * Output the screen
	 */
	public function output() {

	    $tabs = array(
			'event-notification-settings'   => __('Notification Settings','wp-event-manager-emails'),
			'new-event-notification'        => __('New Event Notification','wp-event-manager-emails'),
			'published-event-notification'  => __('Published Event Notification','wp-event-manager-emails'),
			'expired-event-notification'    => __('Expired Event Notification','wp-event-manager-emails'),
			'admin-event-notification'      => __('Admin Event Notification','wp-event-manager-emails'),
			// 'event-notification-templates'  => __('Email Templates (NEW)','wp-event-manager-emails')
		);
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'event-notification-settings'; ?>
		<div class="wrap">
			<h2 class="wp-heading-inline"><?php _e('Email Notifications', 'wp-event-manager-emails'); ?></h2>
			<div class="wpem-wrap wp-event-manager-emails-notifications">
				<h2 class="nav-tab-wrapper">
					<?php
					foreach( $tabs as $key => $value ) {
						$active = ( $key == $tab ) ? 'nav-tab-active' : '';
						echo '<a class="nav-tab ' . $active . '" href="' . admin_url( 'edit.php?post_type=event_listing&page=event-emails-notifications&tab=' . esc_attr( $key ) ) . '">' . esc_html( $value ) . '</a>';
					} ?>
				</h2>
				<form method="post" id="mainform" action="edit.php?post_type=event_listing&amp;page=event-emails-notifications&amp;tab=<?php echo esc_attr( $tab ); ?>">
					<?php
					switch ( $tab ) {
					    case 'event-notification-settings' :
							$this->event_notification_settings();
						break;
						case 'new-event-notification' :
							$this->new_event_notification_email();
						break;
						case 'published-event-notification' :
						    $this->published_event_notification_email();
						break;
						case 'expired-event-notification' :
						    $this->expired_event_notification_email();
						break;
						case 'admin-event-notification' :
						    $this->admin_event_notification_email();
						break;
						case 'event-notification-templates' :
						    $this->event_notification_templates();
						break;
						default :
						    $this->event_notification_settings();
						break;
					} ?>
					<?php wp_nonce_field( 'save-' . $tab ); ?>
				</form>
			</div>
		</div>
		<?php
	}
	
    /**
     * Event Notification settings
     */
	public function event_notification_settings(){

		wp_enqueue_style( 'wp-event-manager-emails-admin' );

	    if ( !empty( $_POST ) && !empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-event-notification-settings' )  ) {
			echo $this->save_event_notification_settings();
		}  
		$new_event              = get_option( 'new_event_email_nofication',true ) ? true : false;
		$publish_event 	        = get_option( 'publish_event_email_nofication',true )? true : false;
		$expire_event           = get_option( 'expired_event_email_nofication',true )? true : false;
		$admin_event   	        = get_option( 'admin_event_email_nofication',true )? true : false;
		$organizer_mail_setting = get_option( 'organizer_mail_account_setting',true );
		$reminder   	        = get_option( 'event_reminder_email_nofication',true )? true : false; ?>

		<div class="wp-event-emails-email-content-wrapper">	
            <div class="admin-setting-left">			     	
			    <div class="white-background">
			        <div class="wp-event-emails">
			            <p>
							<input id="email-settings-new-event" name="email-settings-new-event" type="checkbox" <?php if( $new_event ) echo 'checked=checked';?>> 
							<?php _e('New Event Notification','wp-event-manager-emails'); ?>
						</p>
			            <p class="description">
							<?php _e('The email is sent to the Organizer when the new event is submitted on the website and the event status is pending for approval.','wp-event-manager-emails');?>
						</p>
			            
						<p>
							<input id="email-settings-pulish-event" name="email-settings-publish-event" type="checkbox" <?php if( $publish_event ) echo 'checked=checked';?>> 
							<?php _e('Publish Event Notification','wp-event-manager-emails'); ?>
						</p>
			            <p class="description">
							<?php _e('The email that is sent to an Organizer after the event is published.','wp-event-manager-emails');?>
						</p>
			            
						<p>
							<input id="email-settings-expired-event" name="email-settings-expired-event" type="checkbox" <?php if( $expire_event ) echo 'checked=checked';?>>
							<?php _e('The email that is sent to an Organizer after the event has been expired.','wp-event-manager-emails'); ?>
						</p>
			            <p class="description">
							<?php _e('The email that is sent to an Organizer after the event is published.','wp-event-manager-emails');?>
						</p>

						<p>
							<input id="email-settings-admin-event" name="email-settings-admin-event" type="checkbox" <?php if( $admin_event ) echo 'checked=checked';?>> 
							<?php _e('Admin Event Notification','wp-event-manager-emails'); ?>
						</p>
			         	<p class="description">
							<?php _e('The email that is sent to admin when a new event posted on the website by an Organizer.','wp-event-manager-emails');?>
						</p>

						<p> 
							<select id="organizer-mail-account-setting" name="organizer-mail-account-setting">
								<?php if(isset($organizer_mail_setting) && ($organizer_mail_setting == 'false' || empty($organizer_mail_setting)) ): ?>
									<option selected="selected" value='false'><?php _e('Mail send to organizer email account', 'wp-event-manager-emails');?></option>
									<option value='true'><?php _e('Mail send to event registration email account', 'wp-event-manager-emails');?></option>
								<?php else: ?>
									<option value='false'><?php _e('Mail send to organizer email account', 'wp-event-manager-emails');?></option>
									<option selected="selected" value='true'><?php _e('Mail send to event registration email account', 'wp-event-manager-emails');?></option>
								<?php endif; ?>
							</select>
						</p>
			         	<p class="description">
							<?php _e('The email will sent on selected email account.','wp-event-manager-emails');?>
						</p>

    				    <p class="submit-email save-actions">
							<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-event-manager-emails' ); ?>" />
						</p>
				    </div>
			    </div>	<!--white-background-->		       
		</div>	<!--admin-setting-left--> 
		<?php
	}
	
	/**
	 * Save the email
	 */
	private function save_event_notification_settings() {
	    
		$new_event              = isset( $_POST['email-settings-new-event'] ) ? true : false;
		$publish_event          = isset( $_POST['email-settings-publish-event'] ) ? true : false;
		$expire_event           = isset( $_POST['email-settings-expired-event'] ) ? true : false;
		$admin_event   	        = isset( $_POST['email-settings-admin-event'] ) ? true : false;
		$organizer_mail_setting = isset( $_POST['organizer-mail-account-setting'] ) ? $_POST['organizer-mail-account-setting']  : false;
		
		$result        = update_option( 'new_event_email_nofication', $new_event );
		$result2       = update_option( 'publish_event_email_nofication', $publish_event );
		$result3       = update_option( 'expired_event_email_nofication', $expire_event );
		$result4       = update_option( 'admin_event_email_nofication', $admin_event);
		$result5       = update_option( 'organizer_mail_account_setting', $organizer_mail_setting);
		
		if ( true === $result || true === $result2 || true === $result3 || $result5) {
			echo '<div class="updated"><p>' . __( 'Settings successfully saved.', 'wp-event-manager-emails' ) . '</p></div>';
		}
	}
	
	/**
	 * New email notification 
	 */
	public function new_event_notification_email()	{
		wp_enqueue_style( 'wp-event-manager-emails-admin' );

    	if ( !empty( $_GET['reset-new-event-email'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
		    delete_option( 'new_event_email_content' );
		    delete_option( 'new_event_email_subject' );
		    echo '<div class="updated"><p>' . __( 'The email was successfully reset.', 'wp-event-manager-emails' ) . '</p></div>';
		}
		if ( !empty( $_POST ) && !empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-new-event-notification' )  ) {
			echo $this->save_new_event_notification();
		} ?>
		<div class="wp-event-emails-email-content-wrapper">	
            <div class="admin-setting-left">			     	
			    <div class="white-background">
			      	<p><?php _e( 'Below you will find the email that is sent to an Organizer when event status is pending for approval.', 'wp-event-manager-emails' ); ?></p>
			        <div class="wp-event-emails-email-content">
    					<p>
    						<label><b><?php _e( 'Email Subject', 'wp-event-manager-emails' ); ?></b></label>
    						<input type="text" name="new-event-email-subject" value="<?php echo esc_attr( get_new_event_email_subject() ); ?>" placeholder="<?php echo esc_attr( __( 'Subject', 'wp-event-manager-emails' ) ); ?>" />
						</p>
    					<p>
    						<label><b><?php _e( 'Email Content', 'wp-event-manager-emails' ); ?></b></label>
    						<textarea name="new-event-email-content" cols="71" rows="10"><?php echo esc_textarea( get_new_event_email_content() ); ?></textarea>
    				    </p>    				    
				    </div>
				    <p class="submit-email save-actions">
						<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-new-event-email', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-event-manager-emails' ); ?></a>
						<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-event-manager-emails' ); ?>" />
					</p>
			    </div><!--white-background-->		       
			</div>	<!--admin-setting-left-->  	
			<?php $this->get_dynamic_shortcode_email_box();?>
		</div>
		<?php
	}
	
	/**
	 * Save the email
	 */
	private function save_new_event_notification() {
		$email_content = wp_unslash( $_POST['new-event-email-content'] );
		$email_subject = sanitize_text_field( wp_unslash( $_POST['new-event-email-subject'] ) );
		$result        = update_option( 'new_event_email_content', $email_content );
		$result2       = update_option( 'new_event_email_subject', $email_subject );

		if ( true === $result || true === $result2 ) {
			echo '<div class="updated"><p>' . __( 'The email was successfully saved.', 'wp-event-manager-emails' ) . '</p></div>';
		}
	}
	
	/**
	 * Published email notification 
	 */
	public function published_event_notification_email()	{
		wp_enqueue_style( 'wp-event-manager-emails-admin' );

    	if ( !empty( $_GET['reset-published-event-email'] ) && !empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
		    delete_option( 'published_event_email_content' );
		    delete_option( 'published_event_email_subject' );
		    echo '<div class="updated"><p>' . __( 'The email was successfully reset.', 'wp-event-manager-emails' ) . '</p></div>';
		}
		if ( !empty( $_POST ) && !empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-published-event-notification' )  ) {
			echo $this->save_published_event_notification();
		} ?>
		<div class="wp-event-emails-email-content-wrapper">	
            <div class="admin-setting-left">			     	
			    <div class="white-background">
			      	<p><?php _e( 'Below you will find the email that is sent to an Organizer after event is published.', 'wp-event-manager-emails' ); ?></p>
			        <div class="wp-event-emails-email-content">
    					<p>
    						<label><b><?php _e( 'Email Subject', 'wp-event-manager-emails' ); ?></b></label>
    						<input type="text" name="published-event-email-subject" value="<?php echo esc_attr( get_published_event_email_subject() ); ?>" placeholder="<?php echo esc_attr( __( 'Subject', 'wp-event-manager-emails' ) ); ?>" />
						</p>
    					<p>
    						<label><b><?php _e( 'Email Content', 'wp-event-manager-emails' ); ?></b></label>
    						<textarea name="published-event-email-content" cols="71" rows="10"><?php echo esc_textarea( get_published_event_email_content() ); ?></textarea>
    				    </p>    				    
				    </div>
				    <p class="submit-email save-actions">
						<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-published-event-email', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-event-manager-emails' ); ?></a>
						<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-event-manager-emails' ); ?>" />
					</p>
			    </div>	<!--white-background-->		       
			</div>	<!--admin-setting-left-->  	
			<?php $this->get_dynamic_shortcode_email_box();?>
		</div>
		<?php
	}
	
	/**
	 * Save published email
	 */
	private function save_published_event_notification() {
		$email_content = wp_unslash( $_POST['published-event-email-content'] );
		$email_subject = sanitize_text_field( wp_unslash( $_POST['published-event-email-subject'] ) );
		$result        = update_option( 'published_event_email_content', $email_content );
		$result2       = update_option( 'published_event_email_subject', $email_subject );

		if ( true === $result || true === $result2 ) {
			echo '<div class="updated"><p>' . __( 'The email was successfully saved.', 'wp-event-manager-emails' ) . '</p></div>';
		}
	}
	
	/**
	 * Expired email notification 
	 */
	public function expired_event_notification_email()	{
		wp_enqueue_style( 'wp-event-manager-emails-admin' );

    	if ( !empty( $_GET['reset-published-event-email'] ) && !empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
		    delete_option( 'expired_event_email_content' );
		    delete_option( 'expired_event_email_subject' );
		    echo '<div class="updated"><p>' . __( 'The email was successfully reset.', 'wp-event-manager-emails' ) . '</p></div>';
		}
		if ( !empty( $_POST ) && !empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-expired-event-notification' )  ) {
			echo $this->save_expired_event_notification();
		} ?>
		<div class="wp-event-emails-email-content-wrapper">	
            <div class="admin-setting-left">			     	
			    <div class="white-background">
			      	<p><?php _e( 'Below you will find the email that is sent to an Organizer after event is expired.', 'wp-event-manager-emails' ); ?></p>
			        <div class="wp-event-emails-email-content">
    					<p>
    						<label><b><?php _e( 'Email Subject', 'wp-event-manager-emails' ); ?></b></label>
    						<input type="text" name="expired-event-email-subject" value="<?php echo esc_attr( get_expired_event_email_subject() ); ?>" placeholder="<?php echo esc_attr( __( 'Subject', 'wp-event-manager-emails' ) ); ?>" />
						</p>
    					<p>
    						<label><b><?php _e( 'Email Content', 'wp-event-manager-emails' ); ?></b></label>
    						<textarea name="expired-event-email-content" cols="71" rows="10"><?php echo esc_textarea( get_expired_event_email_content() ); ?></textarea>
    				    </p>    				    
				    </div>
				    <p class="submit-email save-actions">
						<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-expired-event-email', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-event-manager-emails' ); ?></a>
						<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-event-manager-emails' ); ?>" />
					</p>
			    </div>	<!--white-background-->		       
			</div>	<!--admin-setting-left-->  	
			<?php $this->get_dynamic_shortcode_email_box();?>
		</div>
		<?php
	}
	
	/**
	 * Save published email
	 */
	private function save_expired_event_notification() {
		$email_content = wp_unslash( $_POST['expired-event-email-content'] );
		$email_subject = sanitize_text_field( wp_unslash( $_POST['expired-event-email-subject'] ) );
		$result        = update_option( 'expired_event_email_content', $email_content );
		$result2       = update_option( 'expired_event_email_subject', $email_subject );

		if ( true === $result || true === $result2 ) {
			echo '<div class="updated"><p>' . __( 'The email was successfully saved.', 'wp-event-manager-emails' ) . '</p></div>';
		}
	}

	/**
	 * admin new event email notification 
	 */
	public function admin_event_notification_email()	{
		wp_enqueue_style( 'wp-event-manager-emails-admin' );

    	if ( !empty( $_GET['reset-published-event-email'] ) && !empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reset' ) ) {
		    delete_option( 'admin_event_email_content' );
		    delete_option( 'admin_event_email_subject' );
		    echo '<div class="updated"><p>' . __( 'The email was successfully reset.', 'wp-event-manager-emails' ) . '</p></div>';
		}
		if ( !empty( $_POST ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-admin-event-notification' )  ) {
			echo $this->save_admin_event_notification();
		} ?>
		<div class="wp-event-emails-email-content-wrapper">	
            <div class="admin-setting-left">			     	
			    <div class="white-background">
			      	<p>
						<?php _e( 'Below you will find the email that is sent to admin when new event posted.', 'wp-event-manager-emails' ); ?>
					</p>
			        <div class="wp-event-emails-email-content">
    					<p>
    						<label><b><?php _e( 'Email Subject', 'wp-event-manager-emails' ); ?></b></label>
    						<input type="text" name="admin-event-email-subject" value="<?php echo esc_attr( get_admin_event_email_subject() ); ?>" placeholder="<?php echo esc_attr( __( 'Subject', 'wp-event-manager-emails' ) ); ?>" />
						</p>
    					<p>
    						<label><b><?php _e( 'Email Content', 'wp-event-manager-emails' ); ?></b></label>
    						<textarea name="admin-event-email-content" cols="71" rows="10"><?php echo esc_textarea( get_admin_event_email_content() ); ?></textarea>
    				    </p>    				    
				    </div>
				    <p class="submit-email save-actions">
						<a href="<?php echo wp_nonce_url( add_query_arg( 'reset-admin-event-email', 1 ), 'reset' ); ?>" class="reset"><?php _e( 'Reset to defaults', 'wp-event-manager-emails' ); ?></a>
						<input type="submit" class="save-email button-primary" value="<?php _e( 'Save Changes', 'wp-event-manager-emails' ); ?>" />
					</p>
			    </div>	<!--white-background-->		       
			</div>	<!--admin-setting-left-->  	
			<?php $this->get_dynamic_shortcode_email_box();?>
		</div>
		<?php
	}
	
	/**
	 * Save published email
	 */
	private function save_admin_event_notification() {
		$email_content = wp_unslash( $_POST['admin-event-email-content'] );
		$email_subject = sanitize_text_field( wp_unslash( $_POST['admin-event-email-subject'] ) );
		$result        = update_option( 'admin_event_email_content', $email_content );
		$result2       = update_option( 'admin_event_email_subject', $email_subject );

		if ( true === $result || true === $result2 ) {
			echo '<div class="updated"><p>' . __( 'The email was successfully saved.', 'wp-event-manager-emails' ) . '</p></div>';
		}
	}

  	/**
     * Event Notification settings
     */
	public function event_notification_templates(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'wpem_email_templates'; // do not forget about tables prefix  
	    if ( !empty( $_POST ) && !empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'save-event-notification-templates' )  ) {
			echo $this->save_event_notification_templates();
		} 
		if(isset($_GET['delete_template'])) {
			$id = absint($_GET['delete_template']);
			$wpdb->delete( $table_name, array( 'id' => $id ) );
		}
		$email_templates = new WPEM_Email_Template_List_Table();
		$email_templates->prepare_items();

		$post_types = apply_filters('wpem_email_reminder_post_type_selection',array('event_listing'=> 'Event'));
		$statuses = apply_filters('wpem_email_reminder_post_status_selection',array(
			'event_listing' => array(
				'publish'=> 'Event publish',
				'pending'=>'Event pending',
				'cancelled'=>'Event cancelled',
				'expired'=>'Event expired',
				)
		));

		wp_enqueue_style( 'wp-event-manager-emails-admin' );
		if(isset($_GET['form']) && $_GET['form'] == 'add-new' || isset($_GET['edit']))
			include('templates/wpem-email-template-add-new.php');
		else
			include('templates/wpem-email-template-list-table.php');

		$this->get_dynamic_shortcode_email_box();
	}
	
	/**
	 * Save the email template
	 */
	private function save_event_notification_templates() {
		global $wpdb;
		if(isset($_POST['wpem-email-template-submit'])){
			$template_name = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-name'] ) );
			$template_type = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-type'] ) );
			$template_status_before = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-status-before'] ) );
			$template_status_after = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-status-after'] ) );
			$template_subject = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-subject'] ) );
			$template_to = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-to'] ) );
			$template_cc = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-cc'] ) );
			$template_from = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-from'] ) );
			$template_reply = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-replyto'] ) );
			$template_status = sanitize_text_field( wp_unslash( $_POST['wpem-email-template-active'] ) );

			$email_content = wp_unslash( $_POST['wpem-email-template-email-content'] );
			$show_error = false;
			if(empty($template_name)){
				echo '<div class="error"><p>' . __( 'Template name is required.', 'wp-event-manager-emails' ) . '</p></div>';
				$show_error = true;
			}

			if(!is_email($template_to)){
				echo '<div class="error"><p>' . __( 'To is not valid email.', 'wp-event-manager-emails' ) . '</p></div>';
				$show_error = true;
			}
			if(!is_email($template_cc)){
				echo '<div class="error"><p>' . __( 'CC is not valid email.', 'wp-event-manager-emails' ) . '</p></div>';
				$show_error = true;
			}
			if(!is_email($template_from)){
				echo '<div class="error"><p>' . __( 'From is not valid email.', 'wp-event-manager-emails' ) . '</p></div>';
				$show_error = true;
			}
			if(!is_email($template_reply)){
				echo '<div class="error"><p>' . __( 'Reply-To is not valid email.', 'wp-event-manager-emails' ) . '</p></div>';
				$show_error = true;
			}
			
			if($show_error == false){
				$data=array(
						'name' => $template_name, 
						'type' => $template_type, 
						'status_before' => $template_status_before, 
						'status_after' => $template_status_after, 
						'subject' => $template_subject, 
						'body' => $email_content, 
						'to' => $template_to, 
						'cc' => $template_cc, 
						'from' => $template_from, 
						'reply_to' => $template_reply, 
						'active' => $template_status,
						'date_created'=> current_time( 'mysql' )
				);
				$tablename	=	$wpdb->prefix.'wpem_email_templates';
				if(isset($_POST['wpem-email-template-edit'])){
					$edit_id = absint($_POST['wpem-email-template-edit']);
					$id = $wpdb->update( $tablename, $data, [ 'id' => $edit_id ]);
				}else{
					$id = $wpdb->insert( $tablename, $data);
				}
				if($id != false){
					echo '<div class="updated"><p>' . __( 'Email template saved successfully.', 'wp-event-manager-emails' ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * Dynamic shortcode box
	 */
	public function get_dynamic_shortcode_email_box(){ ?>
		<div class="box-info">
			<div class="wp-event-emails-email-content-tags">
			<p><?php _e( 'The following tags can be used to add content dynamically:', 'wp-event-manager-emails' ); ?></p>
			<ul>
				<?php foreach ( get_event_manager_email_tags() as $tag => $name ) : ?>
					<li><code>[<?php echo esc_html( $tag ); ?>]</code> - <?php echo wp_kses_post( $name ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><?php _e( 'All tags can be passed a prefix and a suffix which is only output when the value is set e.g. <code>[event_title prefix="Event Title: " suffix="."]</code>', 'wp-event-manager-emails' ); ?></p>
			</div>
		</div> <!--box-info--> 
		<?php 
	}
}
new WPEM_Emails_Notifications();