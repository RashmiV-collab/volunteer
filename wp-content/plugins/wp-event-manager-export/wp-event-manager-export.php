<?php
/**
* Plugin Name: WP Event Manager - Export
* Plugin URI: http://www.wp-eventmanager.com/
* Description: Generate CSV and XML files of all the listed events.
* 
* Author: WP Event Manager
* Author URI: http://www.wp-eventmanager.com/
* Text Domain: wp-event-manager-export
* Domain Path: /languages
* Version: 1.3.6
* Since: 1.0
* Requires WordPress Version at least: 4.1
* 
* Copyright: 2017 WP Event Manager
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

function pre_check_before_installing_export() {
    /*
    * Check weather WP Event Manager is installed or not
    */
    if (!is_plugin_active('wp-event-manager/wp-event-manager.php')){
		global $pagenow;
		if($pagenow == 'plugins.php'){
			echo '<div id="error" class="error notice is-dismissible"><p>';
			echo __('WP Event Manager is require to use WP Event Manager - Export' , 'wp-event-manager-export');
			echo '</p></div>';		
		}          		
    }
}
add_action('admin_notices', 'pre_check_before_installing_export');	

/**
 * WP_Event_Manager_Bookmarks class.
 */
class WP_Event_Manager_Export extends WPEM_Updater {
	/**
	 * Constructor
	 */
	public function __construct() {

		//if wp event manager not active return from the plugin
		if(!is_plugin_active('wp-event-manager/wp-event-manager.php'))
			return;
		
		// Define constants
		define('EVENT_MANAGER_EXPORT_VERSION', '1.3.6');
		define('EVENT_MANAGER_EXPORT_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
		define('EVENT_MANAGER_EXPORT_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));
		
		// Add actions
		add_action('init', array($this, 'load_plugin_textdomain'));

		add_filter('wpem_dashboard_menu', array($this,'wpem_dashboard_menu_add'));
		add_action('event_manager_event_dashboard_content_wpem_exports', array($this,'wpem_exports_link'));

		add_action('wp_loaded', array($this, 'file_download_handler'));		
		add_action('in_admin_footer',array($this, 'admin_export_setting_js'));
		add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
		
		//include
		include('admin/wpem-export-settings.php');
		include('admin/wpem-export-writepanels.php');
		include('wpem-export-functions.php');
		// Init updates
		$this->init_updates(__FILE__);
	}
	
	/**
	 * Localisation
	 */
	public function load_plugin_textdomain() {
		$domain = 'wp-event-manager-export';       
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
		load_textdomain($domain, WP_LANG_DIR . "/wp-event-manager-export/".$domain."-" .$locale. ".mo");
		load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}
	
	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {   
	    
	    wp_register_style('wp-event-manager-export-frontend', EVENT_MANAGER_EXPORT_PLUGIN_URL.'/assets/css/frontend.css');
	    wp_enqueue_style('wp-event-manager-export-frontend');

	    wp_register_style('chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/css/chosen.css');
	    wp_register_script('chosen', EVENT_MANAGER_PLUGIN_URL . '/assets/js/jquery-chosen/chosen.jquery.min.js', array('jquery'), '1.1.0', true);
		wp_enqueue_script('chosen-validate', EVENT_MANAGER_EXPORT_PLUGIN_URL . '/assets/js/jquery.validate.min.js', array('jquery'), true);
		wp_register_script('event-export-min-js',EVENT_MANAGER_EXPORT_PLUGIN_URL . '/assets/js/export.min.js', array('jquery'), EVENT_MANAGER_EXPORT_VERSION, true); 		
	}

	/**
	 * add dashboard menu function.
	 * @access public
	 * @return void
	 */
	public function wpem_dashboard_menu_add($menus) {
		$menus['wpem_exports'] = [
			'title' => __('Exports', 'wp-event-manager-export'),
			'icon' => 'wpem-icon-download3',
			'query_arg' => ['action' => 'wpem_exports'],
		];
		return $menus;
	}

	/**
	 * Export Event and Other Data
	 */
	public function wpem_exports_link(){
		wp_enqueue_style('chosen');
    	wp_enqueue_script('chosen');
		wp_enqueue_script('event-export-min-js');

		$args = array(
			'post_type'           => 'event_listing',
			'post_status'         => array('publish','expired'),
			'posts_per_page'      => -1,
			'author'              => get_current_user_id()
		);
		$events = get_posts($args);

		/* For Event */
		$default_event_fields = [
			'event_title',
			'event_description',
			'event_location',
			'event_start_date',
			'event_end_date',
			'view_count',
		];
		$default_event_fields = apply_filters('event_manager_export_event_default_fields', $default_event_fields);
		$GLOBALS['event_manager']->forms->get_form('submit-event', array());
		$form_submit_event_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Event', 'instance'));
		$event_form_fields = $form_submit_event_instance->merge_with_custom_fields('frontend');
		$event_form_fields = apply_filters('event_manager_export_event_form_fields', $event_form_fields);

		/* For Organizer */	
		$default_org_fields = [
			'organizer_name',
			'organizer_logo',
			'organizer_description',
			'organizer_email',
			'organizer_website',
		];
		$default_org_fields = apply_filters('event_manager_export_organizer_default_fields', $default_org_fields);
		$GLOBALS['event_manager']->forms->get_form('submit-organizer', array());
		$form_submit_organizer_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Organizer', 'instance'));
		$organizer_form_fields = $form_submit_organizer_instance->merge_with_custom_fields('frontend');
		$organizer_form_fields = apply_filters('event_manager_export_organizer_form_fields', $organizer_form_fields);

		/* For Venue */
		$default_venue_fields = [
			'venue_name',
			'venue_description',
			'venue_logo',
			'venue_website',
		];
		$default_venue_fields = apply_filters('event_manager_export_venue_form_fields', $default_venue_fields);
		$GLOBALS['event_manager']->forms->get_form('submit-venue', array());
		$form_submit_venue_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Venue', 'instance'));
		$venue_form_fields = $form_submit_venue_instance->merge_with_custom_fields('frontend');
		$venue_form_fields = apply_filters('event_manager_export_venue_form_fields', $venue_form_fields);

		get_event_manager_template('export-dashboard.php',array(),'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('event-csv-export.php',
			array(
				'event_fields' => $event_form_fields, 
				'default_event_fields' => $default_event_fields,
				'events' => $events,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('organizer-csv-export.php',
			array(
				'organizer_fields' => $organizer_form_fields, 
				'default_org_fields' => $default_org_fields,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('venue-csv-export.php',
			array(
				'venue_fields' => $venue_form_fields, 
				'default_venue_fields' => $default_venue_fields,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('event-xls-export.php',
			array(
				'event_fields' => $event_form_fields, 
				'default_event_fields' => $default_event_fields,
				'events' => $events,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('organizer-xls-export.php',
			array(
				'organizer_fields' => $organizer_form_fields, 
				'default_org_fields' => $default_org_fields,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('venue-xls-export.php',
			array(
				'venue_fields' => $venue_form_fields, 
				'default_venue_fields' => $default_venue_fields,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');
		get_event_manager_template('event-xml-export.php',
			array(
				'event_fields' => $event_form_fields, 
				'default_event_fields' => $default_event_fields,
				'events' => $events,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('organizer-xml-export.php',
			array(
				'organizer_fields' => $organizer_form_fields, 
				'default_org_fields' => $default_org_fields,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');

		get_event_manager_template('venue-xml-export.php',
			array(
				'venue_fields' => $venue_form_fields, 
				'default_venue_fields' => $default_venue_fields,
			),
			'wp-event-manager-export',EVENT_MANAGER_EXPORT_PLUGIN_DIR . '/templates/');
	}
	
	/**
     * Download a File
     * This function genrate CSV/xml file.
     * This will write content from the database in to csv/xml file.
     * All the event will added in to csv/xml file
     * There is two type of csv/xml file one is with meta key and value.
     * Second is Custom value is selected from the admin panel export tab.
     */
	public function file_download_handler() { 
		$is_post_id = false;
		if (isset($_GET['download_events_default']) || isset($_GET['event_xml_default']) || isset($_GET['event_xls_default'])) {
			$custom_fields = array();
			$row_heading = array();	
			$row = array();
			$auther_id = $_GET['user_id'];	
			$datas = get_event_posts('event_listing', $auther_id);
			foreach ($datas as $event) {
				if(!empty($custom_fields)){
					$old_field_list = $custom_fields;
					$total_field = count($old_field_list);
					$custom_fields = array_keys(get_post_custom($event->ID));
					$old_field_list =	array_unique(array_merge($old_field_list, $custom_fields));
					$custom_fields = $old_field_list;
				} else {
					$custom_fields = array_keys(get_post_custom($event->ID));
				}
			}

			$custom_fields = array_unique($custom_fields);
			foreach ($custom_fields as $custom_field) {
				$row[] = $custom_field;
				if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
					$is_post_id = true;
				}
			}
			//add custom texonomy for events
			if(get_option('event_manager_enable_categories')):
				array_push($row, 'event_category');
				array_push($custom_fields, 'event_category');
			endif;
			if(get_option('event_manager_enable_event_types')):
				array_push($row, 'event_type');
				array_push($custom_fields, 'event_type');
			endif;
			$data_type = 'events';
			$export_file_type = $_GET['file_type'];
		   
		}  else if (isset($_POST['download_events_custom']) || isset($_POST['event_xml_custome'])) {
			// custom fields
			$custom_fields = array();
			$row_heading = array();
			$export_file_type = $_POST['export_file_type'];
			$auther_id = isset($_POST['download_events_custom'])?$_POST['download_events_custom']:$_POST['event_xml_custome'];	
			if(isset($_POST['event_manager_custom_export_events']) && !empty($_POST['event_manager_custom_export_events'])){
				$events = explode(",", $_POST['event_manager_custom_export_events']);
			} else if(isset($_POST['event_manager_custom_export_xml_events']) && !empty($_POST['event_manager_custom_export_xml_events'])){
				$events = explode(",", $_POST['event_manager_custom_export_xml_events']);
			} else {
				$events = '';
			}
			$datas = get_event_posts('event_listing', $auther_id, $events);

			$GLOBALS['event_manager']->forms->get_form('submit-event', array());
			$form_submit_event_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Event', 'instance'));
			$event_form_fields =	$form_submit_event_instance->merge_with_custom_fields('frontend');
			
			if(!empty($_POST['event_manager_export_event_fields']) || !empty($_POST['event_manager_export_xml_event_fields']) || !empty($_POST['event_manager_custom_xls_export_fields'])) {
				if($export_file_type === 'csv'){
					if(isset($_POST['event_manager_custom_export_fields']) && !empty($_POST['event_manager_custom_export_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_fields']);
					} else {
						$fields = $_POST['event_manager_export_event_fields'];
					}
				} else if($export_file_type === 'xls'){
					if(isset($_POST['event_manager_custom_xls_export_fields']) && !empty($_POST['event_manager_custom_xls_export_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_xls_export_fields']);
					} else {
						$fields = $_POST['event_manager_export_xls_event_fields'];
					}
				} else {
					if(isset($_POST['event_manager_custom_xml_export_fields']) && !empty($_POST['event_manager_custom_xml_export_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_xml_export_fields']);
					} else {
						$fields = $_POST['event_manager_export_xml_event_fields'];
					}
				}
				foreach ($fields as $key => $field){
					if(isset($event_form_fields['event'][$field]['label']))	{
						$row_heading[] = $event_form_fields['event'][$field]['label'];	
					} else {
						$row_heading[] = $field;	
					}
					
					$custom_fields[] = $field;
					if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
						$is_post_id = true;
					}
				}
		   	}
			
			$row = array_map('wrap_column', $row_heading);
			$data_type = 'events';
		} else if (isset($_GET['download_ornagizers_default']) || isset($_GET['ornagizers_xml_default']) || isset($_GET['ornagizers_xls_default'])) {
			$custom_fields = array();
			$row = array();
			$auther_id = $_GET['user_id'];	
			$datas = get_event_posts('event_organizer', $auther_id);
			
			foreach ($datas as $ornagizer) {
			   $custom_fields =  array_keys(get_post_custom($ornagizer->ID));
			}
		   
			$custom_fields = array_unique($custom_fields);
			foreach ($custom_fields as $custom_field) {
			   $row[] = $custom_field;
			   	if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
					$is_post_id = true;
				}
			}
			$data_type = 'organizers';
			$export_file_type = $_GET['file_type'];
		} else if (isset($_POST['download_ornagizers_custom']) || isset($_POST['custom_organizer_xml']) || isset($_POST['custom_organizer_xls_form'])){
			// custom fields
			$custom_fields = array();
			$row_heading = array();
			$export_file_type = $_POST['export_file_type'];
			$auther_id = isset($_POST['download_ornagizers_custom'])?$_POST['download_ornagizers_custom']:$_POST['custom_organizer_xml'];	
			$datas = get_event_posts('event_organizer', $auther_id);

			$GLOBALS['event_manager']->forms->get_form('submit-organizer', array());
			$form_submit_organizer_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Organizer', 'instance'));
			$organizer_form_fields =	$form_submit_organizer_instance->merge_with_custom_fields('frontend');

			if(!empty($_POST['event_manager_export_organizer_fields']) || !empty($_POST['event_manager_export_xml_organizer_fields'])  || !empty($_POST['event_manager_export_xls_organizer_fields'])) {
				if($export_file_type === 'csv'){
					if(isset($_POST['event_manager_custom_export_organizer_fields']) && !empty($_POST['event_manager_custom_export_organizer_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_organizer_fields']);
					} else {
						$fields = $_POST['event_manager_export_organizer_fields'];
					}
				} else if($export_file_type === 'xls'){
					if(isset($_POST['event_manager_custom_export_xls_organizer_fields']) && !empty($_POST['event_manager_custom_export_xls_organizer_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_xls_organizer_fields']);
					} else {
						$fields = $_POST['event_manager_export_xls_organizer_fields'];
					}
				} else {
					if(isset($_POST['event_manager_custom_export_xml_organizer_fields']) && !empty($_POST['event_manager_custom_export_xml_organizer_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_xml_organizer_fields']);
					} else {
						$fields = $_POST['event_manager_export_xml_organizer_fields'];
					}
				}
				foreach ($fields as $key => $field)  {
					if(isset($organizer_form_fields['organizer'][$field]['label'])) {
						$row_heading[] = $organizer_form_fields['organizer'][$field]['label'];	
					}  else  {
						$row_heading[] = $field;	
					}
					
					$custom_fields[] = $field;
					if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
						$is_post_id = true;
					}
				}
		   	}
			$row = array_map('wrap_column', $row_heading);
			$data_type = 'organizers';
			
		} else if (isset($_GET['download_venues_default']) || isset($_GET['event_csv_default']) || isset($_GET['venue_xls_default'])) {
			$custom_fields = array();
			$row = array();	
			$auther_id = $_GET['user_id'];	
			$datas = get_event_posts('event_venue', $auther_id); 
		  
			foreach ($datas as $ornagizer) {
			   $custom_fields =  array_keys(get_post_custom($ornagizer->ID));
			}
		   
			$custom_fields = array_unique($custom_fields);
			foreach ($custom_fields as $custom_field) {
			   	$row[] =$custom_field;
				if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
					$is_post_id = true;
				}
			}
			$data_type = 'venues';
			$export_file_type = $_GET['file_type'];
		} else if (isset($_POST['download_venues_custom']) || isset($_POST['custom_venue_xml']) || isset($_POST['custom_venue_xls'])) {
			// custom fields
			$custom_fields = array();
			$row_heading = array();
			$auther_id = isset($_POST['download_venues_custom'])?$_POST['download_venues_custom']:$_POST['custom_venue_xml'];	
			$export_file_type = $_POST['export_file_type'];
			$datas = get_event_posts('event_venue', $auther_id);

			$GLOBALS['event_manager']->forms->get_form('submit-venue', array());
			$form_submit_venue_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Venue', 'instance'));
			$venue_form_fields =	$form_submit_venue_instance->merge_with_custom_fields('frontend');

			if(!empty($_POST['event_manager_export_venue_fields']) || !empty($_POST['event_manager_export_xml_venue_fields'])) {
				if($export_file_type === 'csv'){
					if(isset($_POST['event_manager_custom_export_venue_fields']) && !empty($_POST['event_manager_custom_export_venue_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_venue_fields']);
					} else {
						$fields = $_POST['event_manager_export_venue_fields'];
					}
				} else if($export_file_type === 'xls'){
					if(isset($_POST['event_manager_custom_export_xls_venue_fields']) && !empty($_POST['event_manager_custom_export_xls_venue_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_xls_venue_fields']);
					} else {
						$fields = $_POST['event_manager_export_xls_venue_fields'];
					}
				} else {
					if(isset($_POST['event_manager_custom_export_xml_venue_fields']) && !empty($_POST['event_manager_custom_export_xml_venue_fields'])){
						$fields = explode(",", $_POST['event_manager_custom_export_xml_venue_fields']);
					} else {
						$fields = $_POST['event_manager_export_xml_venue_fields'];
					}
				}
					
				foreach ($fields as $key => $field) {
					if(isset($venue_form_fields['venue'][$field]['label'])) {
						$row_heading[] = $venue_form_fields['venue'][$field]['label'];	
					} else  {
						$row_heading[] = $field;	
					}
					
					$custom_fields[] = $field;
					if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
						$is_post_id = true;
					}
				}
			}
			$row = array_map('wrap_column', $row_heading);
			$data_type = 'venues';
	    }

		if(!is_admin() && (isset($export_file_type) && isset($data_type) && !empty($data_type) && !empty($export_file_type))){
			if($is_post_id == false){
				$row[] = '_post_id';
				$custom_fields[] = '_post_id';	
			}
			if($export_file_type == 'csv'){
				csv_generate($data_type, $row, $datas, $custom_fields);
			} else if($export_file_type == 'xls'){
				xls_generate($data_type, $row, $datas, $custom_fields);
			} else if($export_file_type == 'xlsx'){
				xlsx_generate($data_type, $row, $datas, $custom_fields);
			} else if($export_file_type == 'xml'){
				xml_generate($data_type, $row, $datas, $custom_fields);
			}
		}
	   /* check for admin downloads */
	   if(is_admin() && (isset($_GET['exportAllEvents']) && $_GET['exportAllEvents'] == true)){
			$row = array();
			$custom_fields = array();
		    $data = get_event_posts('event_listing');
		    $export_type = get_option('event_manager_export_type');

		    if($export_type === 'event_csv_custome'){
			    $export_event_fields = get_option('event_manager_export_event_fields');
			    if(!empty($export_event_fields)) {
				    foreach ($export_event_fields as $key => $field) {
						$row[] = $field;	
						$custom_fields[] = $field;
						if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
							$is_post_id = true;
						}
				    }
			    }
		    } else  {
			    foreach ($data as $event) {
					if(!empty($custom_fields)){
						$old_field_list = $custom_fields;
						$total_field = count($old_field_list);
						$custom_fields = array_keys(get_post_custom($event->ID));
						$old_field_list = array_unique(array_merge($old_field_list, $custom_fields));
						$custom_fields = $old_field_list;
					} else {
						$custom_fields = array_keys(get_post_custom($event->ID));
					}
				}
			   
			   $custom_fields = array_unique($custom_fields);
			   // //add custom texonomy for events
				if(get_option('event_manager_enable_categories')):
					array_push($custom_fields, 'event_category');
				endif;
				if(get_option('event_manager_enable_event_types')):
					array_push($custom_fields, 'event_type');
				endif;
			   foreach ($custom_fields as $custom_field) {
				    $row[] = $custom_field;
					if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
						$is_post_id = true;
					}
			   }

		   }
		   $row   = array_map('wrap_column', $row);
		   $type = 'events';

	   }  else if(is_admin() && isset($_GET['exportAllOrganizers']) && $_GET['exportAllOrganizers'] == true) {
			$row = array();
			$custom_fields = array();
		   
		   $data = get_event_posts('event_organizer');
		   $export_type = get_option('event_manager_export_type');

		   if($export_type === 'event_csv_custome') {
			   $GLOBALS['event_manager']->forms->get_form('submit-organizer', array());
			   $form_submit_organizer_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Organizer', 'instance'));
			   $organizer_form_fields = $form_submit_organizer_instance->merge_with_custom_fields('frontend');
			   $organizer_form_fields = apply_filters('event_manager_export_organizer_form_fields', $organizer_form_fields);

			   $export_organizer_fields = get_option('event_manager_export_organizer_fields');

			   if(!empty($export_organizer_fields)) {
				   foreach ($export_organizer_fields as $key => $field)  {
						if(isset($organizer_form_fields['organizer'][$field]['label']))  {
							$row[] = $organizer_form_fields['organizer'][$field]['label'];	
						}  else   {
							$row[] = $field;	
						}
						
						$custom_fields[] = $field;
						if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
							$is_post_id = true;
						}
				   }
			   }
		   }  else  {
			   foreach ($data as $organizer) {
				   $custom_fields =  array_keys(get_post_custom($organizer->ID));
			   }
			   
			   $custom_fields = array_unique($custom_fields);
			   foreach ($custom_fields as $custom_field) {
				   	$row[] = $custom_field;
					if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
						$is_post_id = true;
					}
			   }	
		   }
		   $type = 'organizers';

	    } else if(is_admin() && isset($_GET['exportAllVenues']) && $_GET['exportAllVenues'] == true){
			$row = array();
			$custom_fields = array();
			
			$data = get_event_posts('event_venue');
			$export_type = get_option('event_manager_export_type');

			if($export_type === 'event_csv_custome'){
				// venue fields
				$GLOBALS['event_manager']->forms->get_form('submit-venue', array());
				$form_submit_venue_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Venue', 'instance'));
				$venue_form_fields = $form_submit_venue_instance->merge_with_custom_fields('frontend');
				$venue_form_fields = apply_filters('event_manager_export_venue_form_fields', $venue_form_fields);

				$export_venue_fields = get_option('event_manager_export_venue_fields');

				if(!empty($export_venue_fields)){
					foreach ($export_venue_fields as $key => $field)  {
						if(isset($venue_form_fields['venue'][$field]['label'])){
							$row[] = $venue_form_fields['venue'][$field]['label'];	
						} else {
							$row[] = $field;	
						}
						$custom_fields[] = $field;
						if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
							$is_post_id = true;
						}
					}
				}
			} else {
				foreach ($data as $venue) {
					$custom_fields =  array_keys(get_post_custom($venue->ID));
				}
				$custom_fields = array_unique($custom_fields);
				foreach ($custom_fields as $custom_field) {
					$row[] = $custom_field;
					if(isset($custom_field) && ($custom_field == 'post_id' || $custom_field == '_post_id')) {
						$is_post_id = true;
					}
				}
			}
			$type = 'venues';
		}
		if(is_admin() && (isset($_GET['fileType']) && isset($type) && !empty($type) && !empty($_GET['fileType']))){
			if($is_post_id == false){
				$row[] = '_post_id';
				$custom_fields[] = '_post_id';	
			}
			$file_type = $_GET['fileType'];
			if($file_type == 'csv'){
				csv_generate($type, $row, $data, $custom_fields);
			} else if($file_type == 'xls'){
				xls_generate($type, $row, $data, $custom_fields);
			} else if($file_type == 'xlsx'){
				xlsx_generate($type, $row, $data, $custom_fields);
			} else if($file_type == 'xml'){
				xml_generate($type, $row, $data, $custom_fields);
			}
		}
    }
		
    /**
	* Admin script for hide and show event export tab setting fields.
	*/
	public function admin_export_setting_js(){
		echo '<script>
		jQuery("#settings-event_export").find("tr").not("#setting-event_manager_export_type").hide();
		jQuery("#settings-event_export").find("tr:first").show();
		var export_type = jQuery("#setting-event_manager_export_type").val();
		if (export_type == "event_csv_custome") {
				jQuery("#settings-event_export").find("tr").show();
			}
		jQuery("#setting-event_manager_export_type").change(function(){
			var option_export = jQuery(this).val();
			if (option_export == "event_csv_custome") {
				jQuery("#settings-event_export").find("tr").show();	 
				
				jQuery("select").chosen("destroy")
				jQuery("select[data-multiple=\'multiple\']").chosen();
			}
			else {
			  jQuery("#settings-event_export").find("tr").not("#setting-event_manager_export_type").hide();
			  jQuery("#settings-event_export").find("tr:first").show();
			}
		});
		</script>';
	}

}
$GLOBALS['event_manager_export'] =  new WP_Event_Manager_Export();