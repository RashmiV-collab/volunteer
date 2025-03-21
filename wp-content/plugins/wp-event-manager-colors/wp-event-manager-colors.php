<?php
/*
Plugin Name: WP Event Manager - Event Colors
Plugin URI: http://www.wp-eventmanager.com/plugins/
Description: Manage event types and category with colors
Author: WP Event Manager
Author URI: https://www.wp-eventmanager.com/
Text Domain: wp-event-manager-colors
Domain Path: /languages
Version: 1.1.1
Since: 1.0
Requires WordPress Version at least: 4.1
Copyright: 2018 WP Event Manager
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'GAM_Updater' ) ) {
	include( 'autoupdater/gam-plugin-updater.php' );
}

function pre_check_before_installing_colors() 
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
                echo __( 'WP Event Manager is require to use WP Event Manager - Colors' , 'wp-event-manager-colors');
                echo '</p></div>';		
        	}
           		
    }
}
add_action( 'admin_notices', 'pre_check_before_installing_colors' );

/**
 * WP_Event_Manager_Colors class.
 */
class WP_Event_Manager_Colors extends GAM_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {
		
		// Init updates
		$this->init_updates( __FILE__ );
		
		// Define constants
		define( 'EVENT_MANAGER_COLORS_VERSION', '1.1.1' );
		define( 'EVENT_MANAGER_COLORS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'EVENT_MANAGER_COLORS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		add_action( 'wp_head', array( $this, 'apply_color_style' ) );
		
		//Include admin 
		if(is_admin()){
			include( 'admin/wp-event-manager-color-settings.php' );
		}
		// Add actions
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 12 );
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {
	    
	    $domain = 'wp-event-manager-colors';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain( $domain, WP_LANG_DIR . "/wp-event-manager-colors/".$domain."-" .$locale. ".mo" );
		load_plugin_textdomain($domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	public function apply_color_style() {
		$event_types   = get_terms( 'event_listing_type', array( 'hide_empty' => false ) );
		$event_category   = get_terms( 'event_listing_category', array( 'hide_empty' => false ) );

		echo "<style id='event_manager_colors'>\n";
		if(get_option( 'event_manager_enable_event_types' ) && !empty($event_types)){
    		foreach ( $event_types as $term ) {
    			$background = get_option( 'event_manager_event_type_' . $term->slug . '_color', '#fff' );
    			$text_color = get_option( 'event_manager_event_type_' . $term->slug . '_text_color', '#fff' );
    			printf( ".event-type.term-%s, .event-type.%s { background-color: %s !important; color:%s !important  } \n", $term->term_id, $term->slug, $background,$text_color );
    			printf( ".event-style-color.%s{border-left-color:%s !important; } \n", $term->slug, $background );
    		}
		}
		if(get_option( 'event_manager_enable_categories' ) && !empty($event_category)){
    		foreach ( $event_category as $term ) {
    
    			$background = get_option( 'event_manager_event_category_' . $term->slug . '_color', '#fff' );
    			$text_color = get_option( 'event_manager_event_category_' . $term->slug . '_text_color', '#fff' );
    			printf( ".event-category.term-%s, .event-category.%s { background-color: %s !important; color:%s !important } \n", $term->term_id, $term->slug, $background,$text_color );
    
    		}
		}

		echo "</style>\n";
	}
}

$GLOBALS['event_manager_colors'] = new WP_Event_Manager_Colors();
