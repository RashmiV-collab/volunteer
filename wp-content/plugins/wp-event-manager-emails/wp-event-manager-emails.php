<?php
/*
* Plugin Name: WP Event Manager - Emails
* Plugin URI: http://www.wp-eventmanager.com/
* Description: Changes the default user email templates. When new user register then send mail with own defined template.
* 
* Author: WP Event Manager
* Author URI: http://www.wp-eventmanager.com/
* Text Domain: wp-event-manager-emails
* Domain Path: /languages
* Version: 1.2.4
* Since: 1.0
* Requires WordPress Version at least: 5.1.0
* 
* Copyright: 2017 WP Event Manager
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WPEM_Updater' ) ) {
	include( 'autoupdater/wpem-updater.php' );
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_emails() {
	/*
	 * Check weather WP Event Manager is installed or not. If WP Event Manger is not installed or active then it will give notification to admin panel
	 */
	if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php' ) )	{
		global $pagenow;
		if( $pagenow == 'plugins.php' ) {
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __( 'WP Event Manager is require to use WP Event Manager Emails' , 'wp-event-manager-emails');
			echo '</p></div>';
		}
		return true;
	}
}
add_action( 'admin_notices', 'pre_check_before_installing_emails' );
/**
 * GAM_Event_Manager_Email class.
 */
class WP_Event_Manager_Emails extends WPEM_Updater {
	/**
	 * Constructor
	 */
	public function __construct() {
		//if wp event manager not active return from the plugin
		if (!is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			return;
	    
		// Define constants
		define( 'WPEM_EMAILS_VERSION', '1.2.4' );
		define( 'WPEM_EMAILS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_EMAILS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ));
		
		//include
		include( 'wpem-emails-functions.php' );	
		if(is_admin()){
		    include( 'admin/wpem-emails-admin.php' );
		}
		include('includes/wpem-emails-post-types.php');
		include( 'forms/wpem-emails-form-submit.php' );
		
		// Init classes
		$this->post_types = new WPEM_Emails_Post_Types();

		// Activation - works with symlinks
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'activate' ) );
		register_deactivation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'deactivate' ) );
		
		/**
		 * Send email notification if settings is enabled
		 */
		if(get_option( 'new_event_email_nofication', true ) == true) 
		    add_action(  'pending_event_listing',  array( $this , 'send_new_event_email_notifications'),10, 2 );
        
        if(get_option( 'publish_event_email_nofication', true ) == true)
            add_action(  'publish_event_listing',  array( $this , 'send_published_event_email_notifications'));
        
        if(get_option( 'expired_event_email_nofication', true ) == true)
            add_action(  'expired_event_listing',  array( $this , 'send_expired_event_email_notifications'),10, 2 ); 
			
		add_action( 'transition_post_status', array($this,'load_email_templates'), 10, 3 );
		
        // Init updates
        $this->init_updates( __FILE__ );
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-emails';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-emails/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * This is used to install the plugin
	 */
	public function activate(){
		include('includes/wpem-email-install.php');
		WPEM_Event_Email_Install::install();
	}
	public function deactivate(){}
	
	/**
	 * Sent new event submitted email notification to organizer
	 */
	public function send_new_event_email_notifications( $event_id, $post ) {
	    if ( $post->post_type !== 'event_listing' ) 
			return;

		//get organizer info
		$organizer_info = get_organizer_notification_email($event_id, $post );
		$admin_email = get_option('admin_email');
			
        if ( isset($organizer_info) && !empty($organizer_info)) {
					
			$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
			remove_all_shortcodes();
			event_manager_email_add_shortcodes( array( 'event_id' => $event_id, 'user_id' => '', 'organizer_email' => $organizer_info['organizer_email'], 'site_admin_email' => $admin_email ) );
			
			$subject = do_shortcode( get_new_event_email_subject() );
			$subject = html_entity_decode( $subject, ENT_QUOTES, "UTF-8");

			$message = do_shortcode( get_new_event_email_content() );
			$message = str_replace( "\n\n\n\n", "\n\n", implode( "\n", array_map( 'trim', explode( "\n", $message ) ) ) );
			$is_html = ( $message != strip_tags( $message ) );
		
			$message = nl2br( $message );

			$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;

			$headers =  get_wpem_email_headers($event_id, '', $admin_email, $organizer_info['organizer_name'], $organizer_info['organizer_email']);

			wp_mail(
				apply_filters( 'send_new_event_email_notification_recipient', $organizer_info['organizer_email'], $event_id ),
				apply_filters( 'send_new_event_email_notification_subject', sprintf(__('%s','wp-event-manager-emails'),$subject), $event_id ),
				apply_filters( 'send_new_event_email_notification_message', sprintf(__('%s','wp-event-manager-emails'),$message) ),
				apply_filters( 'send_new_event_email_notification_headers', $headers, $event_id )						
			);
		}

		// send mail to admin for new event post
		if ( $admin_email ) {					
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

			$headers =  get_wpem_email_headers($event_id, $organizer_info['organizer_name'], $organizer_info['organizer_email'], '', $admin_email);

			wp_mail(
				apply_filters( 'send_new_event_admin_email_notification_recipient', $admin_email, $event_id ),
				apply_filters( 'send_new_event_admin_email_notification_subject', sprintf(__('%s','wp-event-manager-emails'),$subject), $event_id ),
				apply_filters( 'send_new_event_admin_email_notification_message', sprintf(__('%s','wp-event-manager-emails'),$message )),
				apply_filters( 'send_new_event_admin_email_notification_headers', $headers, $event_id )						
			);
		}
    }
    
    /**
	 * Sent event published email notification to organizer
	 */
    public function send_published_event_email_notifications( $event_id ) {
		$post = get_post($event_id);
	    if ( $post->post_type !== 'event_listing' ) return;

		//get organizer info
	    $organizer_info = get_organizer_notification_email($event_id, $post );
		$admin_email = get_option('admin_email');
		
		//check each event organizer mail setting for send mail notification
		$send_event_publish_notification = get_post_meta($event_id, '_send_event_publish_notification', true);
		$send_published_event_email_status = get_post_meta($event_id, '_send_published_event_email_status', true);
		if($send_event_publish_notification=='no' && isset($send_published_event_email_status) && !empty($send_published_event_email_status)){
			return;
		}

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
    
    /**
	 * Sent event published email notification to organizer
	 */
    public function send_expired_event_email_notifications( $event_id, $post ) {
	    if ( $post->post_type !== 'event_listing' ) 
			return;
	    
		//get organizer info
	    $organizer_info = get_organizer_notification_email($event_id, $post );
		$admin_email = get_option('admin_email');
			
		if ( isset($organizer_info) && !empty($organizer_info)) {
					
			$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
			remove_all_shortcodes();
			event_manager_email_add_shortcodes( array( 'event_id' => $event_id, 'user_id' => '', 'organizer_email' => $organizer_info['organizer_email'], 'site_admin_email' => $admin_email ) );
			
			$subject = do_shortcode( get_expired_event_email_subject() );
			$subject = html_entity_decode( $subject , ENT_QUOTES, "UTF-8");
			
			$message = do_shortcode( get_expired_event_email_content() );
			$message = str_replace( "\n\n\n\n", "\n\n", implode( "\n", array_map( 'trim', explode( "\n", $message ) ) ) );
			$is_html = ( $message != strip_tags( $message ) );
		
			$message = nl2br( $message );

			$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;

			$headers =  get_wpem_email_headers($event_id, '', $admin_email, $organizer_info['organizer_name'], $organizer_info['organizer_email']);

			wp_mail(
				apply_filters( 'send_expired_event_email_notification_recipient', $organizer_info['organizer_email'], $event_id ),
				apply_filters( 'send_expired_event_email_notification_subject', $subject, $event_id ),
				apply_filters( 'send_expired_event_email_notification_message', $message ),
				apply_filters( 'send_expired_event_email_notification_headers', $headers, $event_id )						
			);
		}
    }

	/**
	 * This is used to load the template of email for email_templates feature
	 */
	public function load_email_templates($new_status, $old_status, $post){
		global $wpdb;
		$table_name	=	$wpdb->prefix.'wpem_email_templates';
		$email_template = $wpdb->get_results("SELECT * FROM $table_name WHERE `active`= 1 AND `status_before` = '$old_status' AND `status_after` = '$new_status' AND `type` = '$post->post_type' ", ARRAY_A);
            
		if(is_array($email_template)){
			foreach($email_template as $key => $template){

				//email loop start
				$existing_shortcode_tags = $GLOBALS['shortcode_tags'];
				remove_all_shortcodes();
				if($post->post_type == 'event_listing')
					event_manager_email_add_shortcodes( array( 'event_id' => $post->ID, 'user_id' => '', 'organizer_email' => $template['to'] ) );
				else
					do_action('wpem_email_template_add_shortcode',$post); //make compatiblity in each plugin for this
				
				$subject = do_shortcode( $template['subject'] );
				$subject = html_entity_decode( $subject, ENT_QUOTES, "UTF-8");

				$message = do_shortcode( $template['body'] );
				$message = str_replace( "\n\n\n\n", "\n\n", implode( "\n", array_map( 'trim', explode( "\n", $message ) ) ) );
				$is_html = ( $message != strip_tags( $message ) );
			
				$message = nl2br( $message );

				$GLOBALS['shortcode_tags'] = $existing_shortcode_tags;

				$headers   = array();
				$headers[] = 'From: ' . get_bloginfo('name') . ' <' . $template['from'] . '>';
				$headers[] = 'Cc:  '.$template['cc'];
				$headers[] = 'Reply-To: ' . $template['reply_to'];
				$headers[] = 'Content-Type: text/html';
				$headers[] = 'charset=utf-8';

				wp_mail(
					apply_filters( 'send_new_event_email_notification_recipient', $template['to'], $post->ID ),
					apply_filters( 'send_new_event_email_notification_subject', sprintf(__('%s','wp-event-manager-emails'),$subject), $post->ID ),
					apply_filters( 'send_new_event_email_notification_message', sprintf(__('%s','wp-event-manager-emails'),$message) ),
					apply_filters( 'send_new_event_email_notification_headers', $headers, $post->ID )						
				);
				//email loop end
			}
		}
    
	}
}
$GLOBALS['event_manager_emails'] = new WP_Event_Manager_Emails();