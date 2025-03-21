<?php
/*
Plugin Name: WP Event Manager - Event Alerts
Plugin URI: http://www.wp-eventmanager.com/product-category/plugins/
Description: Allow users to subscribe to event alerts for their searches. Once registered, users can access a 'My Alerts' page which you can create with the shortcode [event_alerts].
Author: WP Event Manager
Author URI: https://www.wp-eventmanager.com/
Text Domain: wp-event-manager-alerts
Domain Path: /languages
Version: 1.2.6
Since: 1.0
Requires WordPress Version at least: 4.1
Copyright: 2017 WP Event Manager
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if(!defined('ABSPATH'))
	exit;

// include updater if class exist
if(!class_exists('WPEM_Updater')) {
	include('autoupdater/wpem-updater.php');
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

/**
 * Used to check that WP Event Manager plugin is installed or not
 *
 * @static
 * @return 
 * @since 1.0.0
 */
function pre_check_before_installing_alerts(){
    /*
    * Check weather WP Event Manager is installed or not
    */
    if(!is_plugin_active('wp-event-manager/wp-event-manager.php')){
		global $pagenow;
		if($pagenow == 'plugins.php'){
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __('WP Event Manager is require to use WP Event Manager - Event Alerts' , 'wp-event-manager-alerts');
			echo '</p></div>';		
		}   		
    }
}
add_action('admin_notices', 'pre_check_before_installing_alerts');

/**
 * WP_Event_Manager_Alerts Main class.
 */
class WP_Event_Manager_Alerts extends WPEM_Updater {

	/**
	 * __construct function.
	 */
	public function __construct() {
		
		//if wp event manager not active return from the plugin
		if(!is_plugin_active('wp-event-manager/wp-event-manager.php'))
			return;

		// Define constants
		define('WPEM_ALERTS_VERSION', '1.2.6');
		define('WPEM_ALERTS_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
		define('WPEM_ALERTS_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
		
		//Include Sortcodes
		include('shortcodes/wpem-alerts-shortcodes.php');

		// Includes
		include('includes/wpem-alerts-install.php');
		include('includes/wpem-alerts-post-types.php');
		include('includes/wpem-alerts-notifier.php');

		//Include Admin Setings
		if(is_admin()){
			include('admin/wpem-alerts-setup.php');
			include('admin/wpem-alerts-admin.php');	
			include('admin/wpem-alerts-settings.php');
		}		

		//external 
		include('external/external.php');
		// Init classes
		$this->post_types = new WPEM_Alerts_Post_Types();

		// Add actions
		add_action('init', array($this, 'load_plugin_textdomain'), 12);
		add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
		add_filter('event_manager_event_filters_showing_events_links', array($this, 'alert_link'), 10, 2);
		add_action('single_event_listing_button_end', array($this, 'single_alert_link'));

		add_action('admin_init', array($this, 'updater'));

		// Activation / deactivation - works with symlinks
		register_activation_hook(basename(dirname(__FILE__)) . '/' . basename(__FILE__), array($this, 'plugin_activation'));
		register_deactivation_hook(basename(dirname(__FILE__)) . '/' . basename(__FILE__), array($this, 'plugin_deactivate'));
		
		// Init updates
		$this->init_updates(__FILE__);
	}

	/**
     * activate function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.2.4
     */
	public function plugin_activation() {
		// check for old meta keys
		if(!get_option('wpem_alerts_update_db')){
			$email_template = get_option('event_manager_alerts_email_template');
			update_option('wpem_alerts_email_template', $email_template);
			$auto_disable = get_option('event_manager_alerts_auto_disable');
			update_option('wpem_alerts_auto_disable', $auto_disable);
			$matches_only = get_option('event_manager_alerts_matches_only');
			update_option('wpem_alerts_matches_only', $matches_only);
			$page_slug = get_option('event_manager_alerts_page_slug');
			update_option('wpem_alerts_page_slug', $page_slug);
			$page_id = get_option('event_manager_alerts_page_id');
			update_option('wpem_alerts_page_id', $page_id);
			$delete_data_on_uninstall = get_option('event_manager_alerts_delete_data_on_uninstall');
			update_option('wpem_alerts_delete_data_on_uninstall', $page_id);

			//update post meta key
			$args = array(
				'post_type'      => 'event_alert',
				'posts_per_page' => -1,
			);
			$query = new WP_Query($args);

			if($query->have_posts()) {
				while ($query->have_posts()) {
					$query->the_post();
					$alert_id = get_the_ID();

					$alert_keyword = get_post_meta($alert_id, 'alert_keyword', true);
					update_post_meta($alert_id, '_alert_keyword', $alert_keyword);

					$alert_frequency = get_post_meta($alert_id, 'alert_frequency', true);
					update_post_meta($alert_id, '_alert_frequency', $alert_frequency);

					$alert_location = get_post_meta($alert_id, 'alert_location', true);
					update_post_meta($alert_id, '_alert_location', $alert_location);

					$send_count = get_post_meta($alert_id, 'send_count', true);
					update_post_meta($alert_id, '_alert_send_count', $send_count);
				}
			
				wp_reset_postdata();
			} 

			update_option('wpem_alerts_update_db', true);
		}
		WPEM_Alerts_Install::install();
	}

	/**
     * deactivate function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.2.4
     */
	public function plugin_deactivate() {}

	/**
     * updater function.
     *
     * @access public
     * @param 
     * @return 
     * @since 1.2.4
     */
	public function updater() {
		if(version_compare(WPEM_ALERTS_VERSION, get_option('wpem_alerts_version'), '>')){
			WPEM_Alerts_Install::update();
			flush_rewrite_rules();
		}
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {
	    
	    $domain = 'wp-event-manager-alerts';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain($domain, WP_LANG_DIR . "/wp-event-manager-alerts/".$domain."-" .$locale. ".mo");
		load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		wp_register_script('event-alerts', WPEM_ALERTS_PLUGIN_URL . '/assets/js/event-alerts.min.js', array('jquery', 'chosen','wp-event-manager-common'), WPEM_ALERTS_VERSION, true);
		wp_register_script('chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', array('jquery'), '1.1.0', true);
		wp_localize_script('event-alerts', 'event_manager_alerts', array(
			'i18n_confirm_delete' => __('Are you sure you want to delete this alert?', 'wp-event-manager-alerts')
		));
		wp_enqueue_style('event-alerts-frontend', WPEM_ALERTS_PLUGIN_URL . '/assets/css/frontend.min.css');
		if(!wp_script_is('chosen'))
		wp_enqueue_style('chosen');
	}

	/**
	 * Add the alert link
     *
     * @since 1.0.0
     */
	public function alert_link($links, $args) {
		if(is_user_logged_in() && get_option('wpem_alerts_page_id')) {
			$alert_cats = [];
			if(isset($args['search_categories']) && !empty($args['search_categories'])){
				foreach ($args['search_categories'] as $cat){
					$term = get_term_by('slug', $cat, 'event_listing_category');

					if(!empty($term)){
						$alert_cats[] =	$term->term_id;
					}
				}
			}

			$alert_event_type = [];
			if(isset($args['search_event_types']) && !empty($args['search_event_types'])){
				foreach ($args['search_event_types'] as $type){
					$term = get_term_by('slug', $type, 'event_listing_type');

					if(!empty($term)){
						$alert_event_type[] =	$term->term_id;
					}
				}
			}

			$links['events-alert'] = array(
				'name' => __('Add alert', 'wp-event-manager-alerts'),
				'url'  => add_query_arg(array(
					'action'         => 'add_alert',
					'alert_event_type' => $alert_event_type,
					'alert_location' => urlencode($args['search_location']),
					'alert_cats'     => $alert_cats,
					'alert_keyword'  => urlencode($args['search_keywords']),
				), get_permalink(get_option('wpem_alerts_page_id')))
			);
		}
		$link = apply_filters('wpem_alert_link', $links, $args);
		return $links;
	}
	
	/**
	 * Single listing alert link
	 *
     * @since 1.0.0
     */
	public function single_alert_link() {
		global $post, $event_preview;
				
		if(!empty($event_preview)) {
			return;
		}
		
		if(is_user_logged_in() && get_option('wpem_alerts_page_id')) {
			$link     =  add_query_arg(array(
				'action'         => 'add_alert',
				'alert_name'     => urlencode($post->post_title),
				'alert_event_type' => wp_get_post_terms($post->ID, 'event_listing_type', array('fields' => 'ids')),
				'alert_location' => urlencode(get_event_location($post)),
				'alert_cats'     => wp_get_post_terms($post->ID, 'event_listing_category', array('fields' => 'ids')),
				'alert_keyword'  => urlencode($post->post_title),
			), get_permalink(get_option('wpem_alerts_page_id')));
			$link = apply_filters('wpem_single_alert_link', $link, $post);
			get_event_manager_template('single-event-alert-link.php', array('alert_link' => $link), 'wp-event-manager-alerts', WPEM_ALERTS_PLUGIN_DIR . '/templates/');
		}
	}
}
$GLOBALS['event_manager_alerts'] = new WP_Event_Manager_Alerts();
