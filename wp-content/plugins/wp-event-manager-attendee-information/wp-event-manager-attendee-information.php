<?php
/**
* Plugin Name: WP Event Manager - Attendee Information
* Plugin URI: http://www.wp-eventmanager.com/
* Description: Collect the Information from attendee based on the organizer selected fields.
*	
* Author: WP Event Manager
* Author URI: http://www.wp-eventmanager.com/
* Text Domain: wp-event-manager-attendee-information
* Domain Path: /languages
* Version: 1.2.5
* Since: 1.0.0
* Requires WordPress Version at least: 4.1
*
* Copyright: 2015 WP Event Manager
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if(!defined('ABSPATH'))
    exit;
	
if(!class_exists('WPEM_Updater')) {
	include('autoupdater/wpem-updater.php');
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_attendee_information() {    
	/**
	* Check weather WP Event Manager is installed or not. If WP Event Manger is not installed or active then it will give notification to admin panel
	*/
	if(!is_plugin_active('wp-event-manager/wp-event-manager.php')) {
		global $pagenow;
		if($pagenow == 'plugins.php') {
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __('WP Event Manager is require to use WP Event Manager Attendee Information', 'wp-event-manager-attendee-information');
			echo '</p></div>';			
		}
	}

	/*
	 * Check weather WP Event Manager Registrations is installed or not. if WP Event Manger Registrations is not installed or active then it will give notification to admin panel
	 */
	if(!is_plugin_active('wp-event-manager-registrations/wp-event-manager-registrations.php')) {	   
		global $pagenow;
		if($pagenow == 'plugins.php') {
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __('WP Event Manager Registrations addon is require to use WP Event Manager Attendee Information', 'wp-event-manager-attendee-information');
			echo '</p></div>';         
		}
	}

	/*
	 * Check weather WP Event Manager Sell Tickets is installed or not. if WP Event Manger Sell Tickets is not installed or active then it will give notification to admin panel
	 */
	if(!is_plugin_active('wp-event-manager-sell-tickets/wp-event-manager-sell-tickets.php')) {	   
		global $pagenow;
		if($pagenow == 'plugins.php') {
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __('WP Event Manager Sell Tickets addon is require to use WP Event Manager Attendee Information', 'wp-event-manager-attendee-information');
			echo '</p></div>';
		}
	}   
}
add_action('admin_notices', 'pre_check_before_installing_attendee_information');
	 	
class WP_Event_Manager_Attendee_Information extends WPEM_Updater{

	/**
	 * Constructor
	 */
	public function __construct() {
		
		define('WPEM_ATTENDEE_INFORMATION_VERSION', '1.2.5');
		define('WPEM_ATTENDEE_INFORMATION_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
		define('WPEM_ATTENDEE_INFORMATION_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
		
		// Add actions
		add_action('init', array($this, 'load_plugin_textdomain'));
		add_action('wp_enqueue_scripts', array($this, 'frontend_scripts')); 
		//include files
		include('forms/wp-event-manager-attendee-information-form-submit-event.php');
		include('wp-event-manager-attendee-information-functions.php');

		//external 
		include('external/external.php');
		
		include('shortcodes/wp-event-manager-attendee-information-shortcodes.php');
		if(is_admin()){
		    include('admin/wp-event-manager-attendee-information-writepanels.php');
		}
		
		//add filter for the front end registration fields button on single event page
		add_filter('event_registration_form_fields', array($this, 'registration_fields_by_event_information_collected'), 10, 1);
		
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
		
		// Init updates
		$this->init_updates(__FILE__);
	}
		
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-attendee-information';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain($domain, WP_LANG_DIR . "/wp-event-manager-attendee-information/".$domain."-" .$locale. ".mo");
		load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}
	
	/**
	 * Filter registration form fields according to the organizer selected fields (attendee information to collact).
	 * @parma $option
	 * @return $organizer_fields
	 */
	public function registration_fields_by_event_information_collected($option){
		global $post;
		if(!$post || $post->post_type !== 'event_listing') {
			return false;
		}
		$organizer_fields = array();
		$attendee_info_collect =  get_post_meta($post->ID, '_attendee_information_fields', true);
		if(is_array($attendee_info_collect)){
			foreach($attendee_info_collect as $field_key)
				if(array_key_exists($field_key, $option)){
					$organizer_fields[$field_key] = $option[$field_key];
			}
		}
		return $organizer_fields;
	}
	
	/**
	 * Remove fields of attendee information fields if plugin is deactivated
	 * @parma
	 * @return
	 **/
	public function plugin_deactivate()	{
		$all_fields = get_option('event_manager_form_fields', true);
		if(is_array($all_fields) && !empty($all_fields)) {
			$attendee_fields = array('attendee_information_type', 'attendee_information_fields');
			foreach ($attendee_fields as $value) {
				if(isset($all_fields['event'][$value]))
					unset($all_fields['event'][$value]);
			}
			update_option('event_manager_form_fields', $all_fields);
			update_option('event_manager_submit_event_form_fields', array('event' => $all_fields['event']));
		}
	}
	
	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
	    
	    wp_register_script('wp-event-manager-attendee-information', WPEM_ATTENDEE_INFORMATION_PLUGIN_URL . '/assets/js/attendee-information.min.js', array('jquery', 'wp-event-manager-common'), WPEM_ATTENDEE_INFORMATION_VERSION, true);
	    wp_localize_script('wp-event-manager-attendee-information', 'event_manager_attendee_information', array(
	        'admin_ajax_url' => admin_url('admin-ajax.php')
	   ));
	    wp_register_style('wp-event-manager-attemdee-information-frontend', WPEM_ATTENDEE_INFORMATION_PLUGIN_URL . '/assets/css/frontend.min.css', array());
	}
}
$GLOBALS['event_manager_attendee_information'] = new WP_Event_Manager_Attendee_Information(); ?>
