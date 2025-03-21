<?php
/*
Plugin Name: WP Event Manager - Guest List
Plugin URI: http://www.wp-eventmanager.com/
Description: The Guest List Addons enhances the Guest experience by allowing organizers to add guest in different groups, thus making the Guest feel satisfied during the Entry at the event gate.
	
Author: WP Event Manager
Author URI: http://www.wp-eventmanager.com/
Text Domain: wpem-guests
Domain Path: /languages
Version: 1.0.2
Since: 1.0.0
Requires WordPress Version at least: 4.1

Copyright: 2021 WP Event Manager
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPEM_Updater' ) ) 
{
	include( 'autoupdater/wpem-updater.php' );
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_guest_lists() 
{	
	/*
	* Check weather WP Event Manager is installed or not. If WP Event Manger is not installed or active then it will give notification to admin panel
	*/
	if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php' ) ) 
	{
        global $pagenow;
    	if( $pagenow == 'plugins.php' )
    	{
           echo '<div id="error" class="error notice is-dismissible"><p>';
           echo __( 'WP Event Manager is require to use WP Event Manager Guest Lists' , 'wp-event-manager-guests');
           echo '</p></div>';	
    	}
    	return true;
	}
}
add_action( 'admin_notices', 'pre_check_before_installing_guest_lists' );
	
/**
 * WP_Event_Manager_Sell_Tickets class.
 */
class WPEM_Guests extends WPEM_Updater {
	
	/**
	 * Constructor
	 */
	public function __construct() 
	{
		//if wp event manager not active return from the plugin
		if (! is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			return;

		// Define constants
		define( 'WPEM_GUESTS_VERSION', '1.0.2' );
		define( 'WPEM_GUESTS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_GUESTS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        
		
		//include
	   	include( 'wpem-guests-functions.php' );
	   	include( 'includes/wpem-guests-post-types.php' );
	   	include( 'includes/wpem-guests-dashboard.php' );
	   	include( 'includes/wpem-guests-ajax.php' );

		//shortcode
	   	include( 'shortcodes/wpem-guests-shortcodes.php' );


	   	$this->post_types = WPEM_Guests_Post_Types::instance();

		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );	
		add_action( 'init', array( $this, 'load_admin' ), 12 );
		add_action( 'after_switch_theme', array( $this->post_types, 'register_post_types' ), 11 );
		add_action( 'plugins_loaded', array($this,'after_all_plugin_loaded') );

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		
		// Activation hooks
		register_activation_hook( __FILE__, array( $this, 'plugin_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivate' ) );

		// Init updates
		$this->init_updates( __FILE__ );
	}
	
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wpem-guests';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wpem-guests/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Init the admin area
	 */
	public function load_admin() {
		if ( is_admin() && class_exists( 'WP_EVENT_Manager' ) ) {
			include('admin/wpem-guests-admin.php');
		}
	}

	/**
	 * @since 1.0.0
	 */
	public function frontend_scripts() {
		wp_register_style( 'wpem-guest-frontend-style', WPEM_GUESTS_PLUGIN_URL . '/assets/css/frontend.css' );
		wp_register_script( 'wpem-guest-lists-dashboard', WPEM_GUESTS_PLUGIN_URL . '/assets/js/guests-dashboard.js' );
		  wp_localize_script( 'wpem-guest-lists-dashboard', 'wpem_guest_lists_dashboard',
        array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'wpem_guests_security' => wp_create_nonce('_nonce_wpem_guests_security'),
            'i18n_confirm_group_delete' => __('Are you sure you want to delete the Group? This will delete the Guests linked with the group.

','wpem-guests'),
            'i18n_confirm_guest_lists_delete' => __('Are you sure do you want to delete guest?','wpem-guests'),
        )
    );
		
	}

	/**
	*/
	public function after_all_plugin_loaded(){
		//Rest api
		if(class_exists('WPEM_REST_CRUD_Controller'))
		include('includes/rest-api/wpem-rest-guests-controller.php');
	}	

	/**
	 * runs various functions when the plugin first activates
	 */
	public function plugin_activate() {
		global $wpdb;

		$wpdb->hide_errors();

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty($wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		//$wpdb->query( "DROP TABLE IF EXISTS ". $wpdb->prefix . "wpem_guest_lists_group" );

	    // Table for storing licence keys for purchases
	    $sql = "CREATE TABLE ". $wpdb->prefix . "wpem_guests_group (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id int(20) NOT NULL,
				event_id int(20) NOT NULL,
				group_name varchar(200) NOT NULL,
				group_description varchar(200) NOT NULL,
				group_fields varchar(200) NOT NULL,
				PRIMARY KEY  (id)
				) $collate;";

		dbDelta( $sql );
	}

	/**
	 * runs various functions when the plugin first deactivate
	 */
	public function plugin_deactivate() {
		
	}
}

$GLOBALS['event_manager_guests'] = new WPEM_Guests();
?>
