<?php
/*
Plugin Name: WP Event Manager - Google Analytics
Plugin URI: http://www.wp-eventmanager.com/plugins/
Description: Listing owners can setup a new Google Analytics profile by visiting  http://Analytics.Google.com.  A profile ID will look something like UA-XXXXXXXX-XX.
You don't need to worry about the domain for your Google Analytics accounts.  We have seen correct data show up in a Google Analytics account regardless of the domain chosen.

Author: WP Event Manager
Author URI: http://www.wp-eventmanager.com
Text Domain: wp-event-manager-google-analytics
Domain Path: /languages
Version: 1.1
Since: 1.0
Requires WordPress Version at least: 4.1

Copyright: 2018 WP Event Manager
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GAM_Updater' ) ) {
	include( 'autoupdater/gam-plugin-updater.php' );
}

function pre_check_before_installing_google_analytics() 
{
    /*
    * Check weather WP Event Manager is installed or not
    */
    if (! in_array( 'wp-event-manager/wp-event-manager.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
    {
            global $pagenow;
        	if( $pagenow == 'plugins.php' )
        	{
                echo '<div id="error" class="error notice is-dismissible"><p>';
                echo __( 'WP Event Manager is require to use WP Event Manager - Google Analytics' , 'wp-event-manager-google-analytics');
                echo '</p></div>';		
        	}
          		
    }
}
add_action( 'admin_notices', 'pre_check_before_installing_google_analytics' );	

/**
 * WP_Event_Manager_Google_Analytics class.
 */
class WP_Event_Manager_Google_Analytics extends GAM_Updater {

	/**
	 * Constructor
	 */
	public function __construct() {
		/** update restriction removed
		$plugin_slug = str_replace( '.php', '', basename( __FILE__ ) );
		$activation_key = get_option( $plugin_slug . '_licence_key' );
		if(!$activation_key) return;
		***/
		// Define constants
		define( 'EVENT_MANAGER_GOOGLE_ANALYTICS_VERSION', '1.1' );
		define( 'EVENT_MANAGER_GOOGLE_ANALYTICS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'EVENT_MANAGER_GOOGLE_ANALYTICS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );  
        
        if ( is_admin() ) {
			include( 'admin/wp-event-manager-google-analytics-settings.php' );
		}
       
		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 12 );		
        add_action( 'admin_enqueue_scripts', array( $this, 'backend_scripts' ) );
		add_action('wp_footer', array( $this, 'add_google_analytics_tracking_code' ));

		// Init updates
		$this->init_updates( __FILE__ );
	
	}
	
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-google-analytics';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-google-analytics/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
    
    /**
	 * backend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function backend_scripts() {        
        wp_enqueue_style( 'wp-event-manager-google-analytics-backend', EVENT_MANAGER_GOOGLE_ANALYTICS_PLUGIN_URL . '/assets/css/backend.min.css' ); 
		wp_register_script('wp-event-manager-google-analytics-backend',EVENT_MANAGER_GOOGLE_ANALYTICS_PLUGIN_URL . '/assets/js/backend.min.js', array('jquery','wp-event-manager-common'), EVENT_MANAGER_GOOGLE_ANALYTICS_VERSION, true );       
		wp_enqueue_script('wp-event-manager-google-analytics-backend');
		wp_localize_script( 'wp-event-manager-google-analytics-backend', 'event_manager_google_analytics_backend', array(
			'i18n_message_tracking_code' => __( 'Please enter your google analytics tracking code', 'wp-event-manager-google-analytics' ),
			'i18n_message_tracking_code_with_script_tag' => __( 'Please enter your google analytics tracking code with script tag.', 'wp-event-manager-google-analytics' )
		) );
	}
		
	 /**
     * Knowing how audience interacts with website. 
     * The best way to know audience is through your traffic stats and this is what Google Analytics 
     *
     * @return void
     * @since 1.0.0
     **/
    public function add_google_analytics_tracking_code() 
    {       
    	$analytics_tracking_code = get_option( 'event_manager_google_analytics_tracking_code' );
    	if ( $analytics_tracking_code )
    	{
    		if ( strpos($analytics_tracking_code, '<script') !== FALSE) {
    			echo $analytics_tracking_code;
    		}
    	}
    }     
}
$GLOBALS['event_manager_google_analytics'] = new WP_Event_Manager_Google_Analytics();