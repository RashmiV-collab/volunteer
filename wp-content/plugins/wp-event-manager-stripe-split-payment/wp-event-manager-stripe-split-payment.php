<?php
/*
Plugin Name: WP Event Manager - Stripe Split Payment
Plugin URI: http://www.wp-eventmanager.com/

Description:  Manage dynamic payments, collect commissions in a secure and compliant environment for organizers and admins for all your events tickets using Split payment. Split Payment allows users to split order payments, using Stripe.
Author: WP Event Manager
Author URI: http://www.wp-eventmanager.com/

Text Domain: wp-event-manager-stripe-split-payment
Domain Path: /languages
Version: 1.0.0
Since: 1.0.0

Requires WordPress Version at least: 4.1
Copyright: 2019 WP Event Manager
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
	
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

if ( ! class_exists( 'WPEM_Updater' ) ) {
	include( 'autoupdater/wpem-updater.php' );
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_stripe_split_payment() 
{
	/*
	 * Check weather WP Event Manager is installed and version of wp event manger is higher than or equal to 3.0
	*/
	if ( !is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
	{
		global $pagenow;
		if( $pagenow == 'plugins.php' )
		{
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __( 'WP Event Manager Stripe Split Payment require WP Event Manager plugin.' , 'wp-event-manager-stripe-split-payment');
			echo '</p></div>';
		}
		return true;
	}

	/*
	 * Check weather WP Event Manager is installed and version of wp event manger is higher than or equal to 3.0
	*/
	if ( !is_plugin_active( 'wp-event-manager-sell-tickets/wp-event-manager-sell-tickets.php') )
	{
		global $pagenow;
		if( $pagenow == 'plugins.php' )
		{
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __( 'WP Event Manager Stripe Split Payment add-on require WP Event Manager Sell Tickets.' , 'wp-event-manager-stripe-split-payment');
			echo '</p></div>';
		}
		return true;
	}

	/*
	 * Check weather woocommerce is installed or not. If Woocommerce is not active then it will give notification to admin panel
	 */
	if ( !is_plugin_active( 'woocommerce/woocommerce.php') ) 
	{	   
	     global $pagenow;
	     if( $pagenow == 'plugins.php' )
	     {
	     	echo '<div id="error" class="error notice is-dismissible"><p>';
	     	echo  __( 'Woocommerce is require to use WP Event Manager Stripe Split Payment' , 'wp-event-manager-stripe-split-payment');
	     	echo '</p></div>';	
	     }  
	     return false;     
	}
}
add_action( 'admin_notices', 'pre_check_before_installing_stripe_split_payment' );

/**
 * WPEM_Stripe_Split_Payment class.
 */
class WPEM_Stripe_Split_Payment extends WPEM_Updater {

	/**
	 * @var Reference to logging class.
	 */
	private static $log;

	/**
	 * Constructor
	 */
	public function __construct() 
	{
		if ( !is_plugin_active( 'woocommerce/woocommerce.php') || !is_plugin_active( 'wp-event-manager-sell-tickets/wp-event-manager-sell-tickets.php') || !is_plugin_active( 'wp-event-manager/wp-event-manager.php') )
			 return false;

		// Add actions
		add_action( 'plugins_loaded', array( $this, 'init' ),0 );
	}

	/**
	 * Init the plugin after plugins_loaded so environment variables are set.
	 */
	public function init() 
    {
    	// Define constants
		define( 'WPEM_STRIPE_SPLIT_PAYMENT_VERSION', '1.0.0' );
		define( 'WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
        
		// Add actions      
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );	
        
        // If the parent WC_Payment_Gateway class doesn't exist
	    // it means WooCommerce is not installed on the site
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            return;   
        } 
        
        //Added official stripe php lib for stripe api request.	
		if ( ! class_exists( 'Stripe' ) ) {          
			include_once('includes/stripe-php/init.php');
		}

		include('wp-event-manager-stripe-split-payment-functions.php');		
		include('wp-event-manager-stripe-split-payment-template.php');
		
		//includes stripe communication api file
		include('includes/wpem-stripe-split-payment-api.php');      
		include('includes/wpem-stripe-split-payment-customer.php');
		include('includes/wpem-stripe-split-payment-dashboard.php');
		
		include('shortcodes/wpem-stripe-split-payment-shortcodes.php');
		
        //register our stripe connect payment gateway to woocommerce
        add_filter( 'woocommerce_payment_gateways',array( $this, 'register_stripe_connect_gateway_class' ) );
        include('includes/wpem-stripe-split-payment-gateway.php');
        $this->settings_page = new WPEM_Stripe_Split_Payment_Gateway();
        add_action('admin_notices', array($this->settings_page, 'stripe_admin_notices'));

        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		
		// Init updates
		$this->init_updates( __FILE__ );	
	}

	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-stripe-split-payment';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-stripe-split-payment/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/*
    * Register our stripe connect gateway with woocommerce
    * As well as defining your class, you need to also tell WooCommerce (WC) that it exists. Do this by filtering woocommerce_payment_gateways:
    */
    public function register_stripe_connect_gateway_class( $methods ) 
	{
		$methods[] = 'WPEM_Stripe_Split_Payment_Gateway'; 
		return $methods;
	}


	/**
	 * Register and enqueue scripts and css
	 */

	public function frontend_scripts() 
	{
		wp_register_style( 'wp-event-manager-stripe-split-payment-frontend', WPEM_STRIPE_SPLIT_PAYMENT_PLUGIN_URL . '/assets/css/frontend.min.css');
	}


	/**
	 * What rolls down stairs
	 * alone or in pairs,
	 * and over your neighbor's dog?
	 * What's great for a snack,
	 * And fits on your back?
	 * It's log, log, log
	 */
	public static function log( $message ) 
    {
    	
        //check if stripe loggin enabled then show log
        if( WPEM_Stripe_Split_Payment_API::is_stripe_logging_enabled() ==false)
            return;            
        
		if ( empty( self::$log ) ) {
			self::$log = new WC_Logger();
		}

		self::$log->add( 'wp-event-manager-stripe-split-payment', $message );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $message );
		}
	}
}

$GLOBALS['wpem_stripe_split_payment'] = new WPEM_Stripe_Split_Payment();
