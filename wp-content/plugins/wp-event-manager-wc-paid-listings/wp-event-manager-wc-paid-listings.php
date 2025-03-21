<?php
/**
* Plugin Name: WP Event Manager - WooCommerce Paid Listings
* Plugin URI: http://www.wp-eventmanager.com/
* Description: Paid listing with woocommerce.
*
* Author: WP Event Manager
* Author URI: http://www.wp-eventmanager.com/
* Text Domain: wp-event-manager-wc-paid-listings
* Domain Path: /languages
* Version: 1.1.4
* Since: 1.0
* Requires WordPress Version at least: 4.1
*
* Copyright: 2017 WP Event Manager
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
**/
	
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;
	
if ( ! class_exists( 'WPEM_Updater' ) ) {
	include( 'autoupdater/wpem-updater.php' );
}	

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_paid_listings() {	
	/*
	* Check weather WP Event Manager is installed or not. If WP Event Manger is not installed or active then it will give notification to admin panel
	*/
	if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php') ) {
	        global $pagenow;
	    	if( $pagenow == 'plugins.php' ){
	           echo '<div id="error" class="error notice is-dismissible"><p>';
	           echo __( 'WP Event Manager is require to use WP Event Manager Woocommerce Paid Listings' , 'wp-event-manager-wc-paid-listings');
	           echo '</p></div>';	
	    	}
	    	return true;
	}
		
	/*
	 * Check weather woocommerce is installed or not. If Woocommerce is not active then it will give notification to admin panel
	 */
	if ( !is_plugin_active( 'woocommerce/woocommerce.php') ){	   
	     global $pagenow;
	     if( $pagenow == 'plugins.php' ){
	     	echo '<div id="error" class="error notice is-dismissible"><p>';
	     	echo  __( 'Woocommerce is require to use WooCommerce Paid Listings' , 'wp-event-manager-wc-paid-listings');
	     	echo '</p></div>';	
	     }  
	     return false;     
	}

}
add_action( 'admin_notices', 'pre_check_before_installing_paid_listings' );

/**
 * WP_Event_Manager_WC_Paid_Listings class.
 */
class WP_Event_Manager_WC_Paid_Listings extends WPEM_Updater {
	
	/**
	 * Constructor
	 */
	public function __construct(){
		//if wp event manager not active return from the plugin
		if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			return;

		//if woocommerce not active return from the plugin
		if ( !is_plugin_active( 'woocommerce/woocommerce.php') )
			return;

		// Define constants
		define( 'EVENT_MANAGER_WC_PAID_LISTINGS_VERSION', '1.1.4' );
		define( 'EVENT_MANAGER_WC_PAID_LISTINGS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'EVENT_MANAGER_WC_PAID_LISTINGS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        
        include_once( 'wp-event-manager-wc-paid-listings-functions.php' );	
        
		//includes
		include_once( 'includes/wp-event-manager-wcpl-package-product.php' );
		include_once( 'includes/wp-event-manager-wcpl-product-event-package.php' );
		include_once( 'includes/wp-event-manager-wcpl-submit-event-form.php' );
		include_once( 'includes/wp-event-manager-wcpl-package.php' );
		include_once( 'includes/wp-event-manager-wcpl-cart.php' );
		include_once( 'includes/wp-event-manager-wcpl-orders.php' );
		include_once( 'includes/wp-event-manager-wcpl-subscriptions.php' );
		include_once( 'includes/package-functions.php' );
		include_once( 'includes/user-functions.php' );
				
		if(class_exists('WC_Product_Subscription')){
			include_once('includes/wp-event-manager-wcpl-subscription-product.php');
			include_once('includes/wp-event-manager-wcpl-event-package-subscription.php');
		}
			
		if( is_admin() ){
			include_once( 'admin/wp-event-manager-wcpl-admin.php' );
			include_once( 'admin/wp-event-manager-wcpl-admin-add-package.php' );
			include_once( 'admin/wp-event-manager-wcpl-admin-packages.php' );
		}		
		
		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );		
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_filter( 'get_event_status', array( $this, 'get_event_status' ), 10, 2 );
		add_filter( 'event_manager_valid_submit_event_statuses', array( $this, 'valid_submit_statuses' ) );

		// Init updates
		$this->init_updates( __FILE__ );
	}
	
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		global $event_manager;
		$domain = 'wp-event-manager-wc-paid-listings';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-wc-paid-listings/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		register_post_status( 'pending_payment', array(
				'label'                     => _x( 'Pending Payment', 'event_listing', 'wp-event-manager-wc-paid-listings' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'wp-event-manager-wc-paid-listings' ),
			) );
		add_action( 'pending_payment_to_publish', array( $event_manager->post_types, 'set_event_expiry_date' ) );
	}
	
	/**
	 * @since 1.1
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'wp-event-manager-wc-paid-listing-frontend', EVENT_MANAGER_WC_PAID_LISTINGS_PLUGIN_URL . '/assets/css/frontend.min.css' );
	}
	
	/**
	 * Filter event status name
	 *
	 * @param  string $nice_status
	 * @param  string $status
	 * @return string
	 */
	public function get_event_status( $status, $event ) {
		if ( $event->post_status == 'pending_payment' ) {
			$status = __( 'Pending Payment', 'wp-event-manager-wc-paid-listings' );
		}
		return $status;
	}
	/**
	 * Ensure the submit form lets us continue to edit/process a event with the pending_payment status
	 *
	 * @return array
	 */
	public function valid_submit_statuses( $status ) {
		$status[] = 'pending_payment';
		return $status;
	}

	/**
	 * Check if the installed version of WooCommerce is older than a specified version.
	 *
	 * @from Prospress/woocommerce-subscriptions
	 */
	public static function is_woocommerce_pre( $version ) {

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $version, '<' ) ) {
			$woocommerce_is_pre_version = true;
		} else {
			$woocommerce_is_pre_version = false;
		}

		return $woocommerce_is_pre_version;
	}
}

$GLOBALS['event_manager_paid_listings'] = new WP_Event_Manager_WC_Paid_Listings();

/**
* Install the plugin
*/
function wp_event_manager_wcpl_install() {
	global $wpdb;

	$wpdb->hide_errors();

	$collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty( $wpdb->charset ) ) {
			$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$collate .= " COLLATE $wpdb->collate";
		}
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	/**
	 * Table for user packages
	 */
	$sql = "
	CREATE TABLE {$wpdb->prefix}emwcpl_user_packages (
	  id bigint(20) NOT NULL auto_increment,
	  user_id bigint(20) NOT NULL,
	  product_id bigint(20) NOT NULL,
	  order_id bigint(20) NOT NULL default 0,
	  package_featured int(1) NULL,
	  package_duration bigint(20) NULL,
	  package_limit bigint(20) NOT NULL,
	  package_count bigint(20) NOT NULL,
	  package_type varchar(100) NOT NULL,
	  PRIMARY KEY  (id)
	) $collate;
	";
	dbDelta( $sql );
	add_action( 'shutdown', 'wp_event_manager_wcpl_delayed_install' );
}

/**
 * Installer (delayed)
 */
function wp_event_manager_wcpl_delayed_install() {
	if ( ! get_term_by( 'slug', sanitize_title( 'event_package' ), 'product_type' ) ) {
		wp_insert_term( 'event_package', 'product_type' );
	}
	if ( ! get_term_by( 'slug', sanitize_title( 'event_package_subscription' ), 'product_type' ) ) {
		wp_insert_term( 'event_package_subscription', 'product_type' );
	}
}

register_activation_hook( __FILE__, 'wp_event_manager_wcpl_install' );	