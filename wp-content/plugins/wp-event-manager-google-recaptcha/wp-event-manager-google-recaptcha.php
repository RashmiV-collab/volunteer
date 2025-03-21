<?php
/*
Plugin Name: WP Event Manager - Google Recaptcha
Plugin URI: http://www.wp-eventmanager.com/plugins/
Description: To prevent a spam, you can use captcha. Google ReCaptcha will show at event submmision page. 

Author: WP Event Manager
Author URI: http://www.wp-eventmanager.com
Text Domain: wp-event-manager-google-recaptcha
Domain Path: /languages
Version: 1.1.2
Since: 1.0
Requires WordPress Version at least: 4.1

Copyright: 2017 WP Event Manager
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPEM_Updater' ) ) {
	include( 'autoupdater/wpem-updater.php' );
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_google_recaptcha() 
{
    /*
    * Check weather WP Event Manager is installed or not
    */
    if ( !is_plugin_active('wp-event-manager/wp-event-manager.php') ) 
    {
            global $pagenow;
        	if( $pagenow == 'plugins.php' )
        	{
                echo '<div id="error" class="error notice is-dismissible"><p>';
                echo __( 'WP Event Manager is require to use WP Event Manager - Google ReCaptcha' , 'wp-event-manager-google-recaptcha');
                echo '</p></div>';		
        	}
        	return false;          	
    } 
}
add_action( 'admin_notices', 'pre_check_before_installing_google_recaptcha' );

/**
 * WP_Event_Manager_Google_Recaptcha class.
 */
class WP_Event_Manager_Google_Recaptcha extends WPEM_Updater {
	/**
	 * Constructor
	 */
	public function __construct() {

		//if wp event manager not active return from the plugin
		if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			return;
				
		// Define constants
		define( 'WPEM_GOOGLE_RECAPTCHA_VERSION', '1.1.2' );
		define( 'WPEM_GOOGLE_RECAPTCHA_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_GOOGLE_RECAPTCHA_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        
        define( 'Enable_Google_Recaptcha', get_option('enable_event_manager_google_recaptcha'));
        define( 'Google_Recaptcha_Type', get_option('event_manager_google_recaptcha_type'));

        define( 'Google_Recaptcha_Site_Key', get_option('event_manager_google_recaptcha_site_key'));
        define( 'Google_Recaptcha_Secret_Key', get_option('event_manager_google_recaptcha_secret_key'));

        define( 'Google_Recaptcha_Site_Key_V3', get_option('event_manager_google_recaptcha_site_key_v3'));
        define( 'Google_Recaptcha_Secret_Key_V3', get_option('event_manager_google_recaptcha_secret_key_v3'));

        //admin
		include( 'admin/wpem-google-recaptcha-admin.php' );

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 12 );

		if( Enable_Google_Recaptcha )
		{
			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

			// event
			if( get_option('enable_event_venue') && get_option('enable_event_manager_google_recaptcha_submit_event_form') )
			{
				add_action( 'submit_event_form_venue_fields_end',  array( $this,'recaptcha_field') );
				add_filter( 'submit_event_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}
			elseif( get_option('enable_event_organizer') && get_option('enable_event_manager_google_recaptcha_submit_event_form') )
			{
				add_action( 'submit_event_form_organizer_fields_end',  array( $this,'recaptcha_field') );
				add_filter( 'submit_event_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}
			elseif( get_option('enable_event_manager_google_recaptcha_submit_event_form') )
			{
				add_action( 'submit_event_form_event_fields_end',  array( $this,'recaptcha_field') );
				add_filter( 'submit_event_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}

			//organizer
			if( get_option('enable_event_organizer') && get_option('enable_event_manager_google_recaptcha_submit_organizer_form') )
			{
				add_action( 'submit_organizer_form_organizer_fields_end',  array( $this,'recaptcha_field') );
				add_filter( 'submit_organizer_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}

			// venue
			if( get_option('enable_event_venue') && get_option('enable_event_manager_google_recaptcha_submit_venue_form') )
			{
				add_action( 'submit_venue_form_venue_fields_end',  array( $this,'recaptcha_field') );
				add_filter( 'submit_venue_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}

			// registration
			if( get_option('enable_event_manager_google_recaptcha_submit_registration_form') )
			{
				add_action( 'event_registration_form_fields_end',array( $this,'recaptcha_field'));
				add_filter( 'registration_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}
			
			// zoom
			if( get_option('enable_event_manager_google_recaptcha_submit_zoom_meeting_form') )
			{
				add_action( 'submit_zoom_meeting_form_fields_end',array( $this,'recaptcha_field'));
				add_filter( 'submit_zoom_meeting_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}

			// contact organizer
			if( get_option('enable_event_manager_google_recaptcha_submit_contact_organizer_form') )
			{
				add_action( 'submit_contact_organizer_form_fields_end',array( $this,'recaptcha_field'));
				add_filter( 'contact_organizer_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}

			// guest list group
			if( get_option('enable_event_manager_google_recaptcha_submit_guest_lists_group_form') )
			{
				add_action( 'event_manager_guest_lists_group_form_fields_end',array( $this,'recaptcha_field'));
				add_filter( 'guest_list_group_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}
			// guest list guest
			if( get_option('enable_event_manager_google_recaptcha_submit_guest_lists_guest_form') )
			{
				add_action( 'event_manager_guest_lists_guest_form_fields_end',array( $this,'recaptcha_field'));
				add_filter( 'guest_list_form_validate_fields', array( $this,'validate_recaptcha_field'));
			}
		}

		//init update
		$this->init_updates( __FILE__ );
	}
	
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-google-recaptcha';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-recaptcha/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() 
	{
    	if( Google_Recaptcha_Type == 'v3' )
       		wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js?render='.Google_Recaptcha_Site_Key_V3 );
       	else
       		wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js' );
	}
	
	// Add reCAPTCHA to the event submission form
	public function recaptcha_field() 
	{
		if( Google_Recaptcha_Type == 'v3' ) : ?>
			<?php if(defined('Google_Recaptcha_Site_Key_V3') && (Google_Recaptcha_Site_Key_V3 != '')) : ?>
				<input type="hidden" name="g-recaptcha-response" class="g-recaptcha-response">
				<script>
			        grecaptcha.ready(function () {
			            grecaptcha.execute('<?php echo Google_Recaptcha_Site_Key_V3; ?>', { action: 'contact' }).then(function (token) {
			                /* var recaptchaResponse = document.getElementById('g-recaptcha-response');
			                recaptchaResponse.value = token; */
			                jQuery(".g-recaptcha-response").val(token);
			            });
			        });
			    </script>
		    <?php endif; ?>

		<?php else : ?>
			<?php if(defined('Google_Recaptcha_Site_Key') && (Google_Recaptcha_Site_Key != '')) : ?>
				<fieldset>
					<label><?php _e('Are you human?','wp-event-manager-google-recaptcha');?></label>
					<div class="field">
						<div class="g-recaptcha" data-sitekey="<?php echo Google_Recaptcha_Site_Key; ?>"></div>
					</div>
				</fieldset>
			<?php endif; ?>

		<?php endif;
	}
	
	// Validate
	public function validate_recaptcha_field( $success ) 
	{
		if( Google_Recaptcha_Type == 'v3' )
		{
			if( defined('Google_Recaptcha_Site_Key_V3') && (Google_Recaptcha_Site_Key_V3 != '') )
		    {
		    	$response = wp_remote_get( add_query_arg( array(
	    			'secret'   => Google_Recaptcha_Secret_Key_V3,
	    			'response' => isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '',
	    			'remoteip' => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']
	    		), 'https://www.google.com/recaptcha/api/siteverify' ) );
		    }
		}
		else
		{
			if( defined('Google_Recaptcha_Site_Key') && (Google_Recaptcha_Site_Key != '') )
		    {
		    	$response = wp_remote_get( add_query_arg( array(
	    			'secret'   => Google_Recaptcha_Secret_Key,
	    			'response' => isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '',
	    			'remoteip' => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']
	    		), 'https://www.google.com/recaptcha/api/siteverify' ) );
		    }
		}

		if ( is_wp_error( $response ) || empty( $response['body'] ) || ! ( $json = json_decode( $response['body'] ) ) || ! $json->success ) {
			throw new Exception( __( 'Please verify that you are not a robot.', 'wp-event-manager-google-recaptcha' ) );
		}

		return $success;
	}
}

$GLOBALS['event_manager_google_recaptcha'] = new WP_Event_Manager_Google_Recaptcha();
