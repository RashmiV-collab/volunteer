<?php
/**
* Plugin Name: WP Event Manager - Event Tags
* Plugin URI: http://www.wp-eventmanager.com/plugins/
* Description: Adds tags to Event Manager for tagging events with required place and event flavour. Also adds some extra shortcodes. 
* 
* Author: WP Event Manager
* Author URI: http://www.wp-eventmanager.com/
* Text Domain: wp-event-manager-event-tags
* Domain Path: /languages
* Version: 1.4.4
* Since: 1.0
* Requires WordPress Version at least: 4.1
*
* Copyright: 2017 WP Event Manager
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if(!defined('ABSPATH')) {
	exit;
}

if(!class_exists('WPEM_Updater')) {
	include('autoupdater/wpem-updater.php');
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');
function pre_check_before_installing_tags() {
    /*
    * Check weather WP Event Manager is installed or not
    */
    if(!is_plugin_active('wp-event-manager/wp-event-manager.php')){
        global $pagenow;
    	if($pagenow == 'plugins.php'){
            echo '<div id="error" class="error notice is-dismissible"><p>';
            echo  __('WP Event Manager is require to use WP Event Manager - Event Tags' , 'wp-event-manager-event-tags');
            echo '</p></div>';		
    	}           		
    }
}
add_action('admin_notices', 'pre_check_before_installing_tags');	

/**
 * WP_Event_Manager_Event_Tags class.
 */
class WP_Event_Manager_Event_Tags extends WPEM_Updater {
	/**
	 * __construct function.
	 */
	public function __construct() {

		//if wp event manager not active return from the plugin
		if(!is_plugin_active('wp-event-manager/wp-event-manager.php'))
			return;		
		
		// Define constants
		define('WPEM_EVENT_TAGS_PLUGIN_VERSION', '1.4.4');
		define('WPEM_EVENT_TAGS_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
		define('WPEM_EVENT_TAGS_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
		add_action('init', array($this, 'load_plugin_textdomain'));      

		// Includes Admin Settings
		include('admin/wpem-event-tags-settings.php');
		
		//Include External
		include('external/external.php');
		
		//Include file for create Event Tags Texonomy
		include('includes/wpem-event-tags-category.php');
		
		//include tag fileld to event submit form
		include('forms/wpem-event-tags-forms.php');
		
		//Include function for submit tags of event form
		include('forms/wpem-event-tags-form-submit.php');
		
		add_filter('the_event_description', array($this, 'display_tags'));
		add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

		// Feeds
		add_filter('event_feed_args', array($this, 'event_feed_args'));

		// Add column to admin
		include('admin/wpem-event-tags-admin.php');
		include('admin/wpem-event-tags-writepanels.php');
		add_action('manage_event_listing_posts_custom_column', array($this, 'custom_columns'), 2);
		
		// Includes
		include('shortcodes/wpem-event-tags-shortcodes.php');
		include('wp-event-manager-event-tags-template.php');
		
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
		
		// Init updates
		$this->init_updates(__FILE__);
	}

	/**
	 * CSS
	 */
	public function frontend_scripts() {
		wp_enqueue_style('wp-event-manager-event-tags-frontend', WPEM_EVENT_TAGS_PLUGIN_URL . '/assets/css/style.min.css');
	}
	
    /**
	 * Localisation
	 */
	public function load_plugin_textdomain(){
		$domain = 'wp-event-manager-event-tags';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain($domain, WP_LANG_DIR . "/wp-event-manager-event-tags/".$domain."-" .$locale. ".mo");
		load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}
	
	/**
	 * Show tags on event pages
	 * @return string
	 */
	public function display_tags($content) {
		global $post;

		if($terms = $this->get_event_tag_list($post->ID)) {
			$content .= '<p class="event_tags">' . __('Tagged as:', 'wp-event-manager-event-tags') . ' ' . $terms . '</p>';
		}
		return $content;
	}

	/**
	 * Gets a formatted list of event tags for a post ID
	 * @return string
	 */
	public function get_event_tag_list($event_id) {
		$terms = get_the_term_list($event_id, 'event_listing_tag', '', ', ', '');

		if(!apply_filters('enable_event_tag_archives', get_option('event_manager_enable_tag_archive')))
			$terms = strip_tags($terms);
		return $terms;
	}

	/**
	 * Tag support for feeds
	 * @param  [type] $args
	 * @return [type]
	 */
	public function event_feed_args($args) {
		if(!empty($_GET['event_tags'])) {
			$args['tax_query'][] = array(
				'taxonomy' => 'event_listing_tag', 
				'field'    => 'slug', 
				'terms'    => explode(', ', sanitize_text_field($_GET['event_tags']))
			);
		}
		return $args;
	}
	
	/**
	 * Handle display of new column
	 * @param  string $column
	 */
	public function custom_columns($column) {
		global $post;

		if($column == 'event_tags') {
			if(!$terms = $this->get_event_tag_list($post->ID))
				echo '<span class="na">&ndash;</span>';
			else
				echo $terms;
		}
	}
	
	/**
	 * Remove fields of event tags if plugin is deactivated
	 * @parma
	 * @return
	 **/
	public function plugin_deactivate()	{
		$all_fields = get_option('event_manager_form_fields', true);
		if(is_array($all_fields) && !empty($all_fields)){
			$event_tags_fields = array('event_tags');
			foreach ($event_tags_fields as $value) {
				if(isset($all_fields['event'][$value]))
					unset($all_fields['event'][$value]);
			}

			update_option('event_manager_form_fields', $all_fields);
			update_option('event_manager_submit_event_form_fields', array('event' => $all_fields['event']));
		}
	}
}
$GLOBALS['event_manager_tags'] = new WP_Event_Manager_Event_Tags();