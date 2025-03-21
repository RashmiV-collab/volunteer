<?php
/*
* This file use for setings at admin site for event export settings.
*/
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WPEM_Export_Settings class.
 */
class WPEM_Export_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {		
		add_filter('event_manager_settings', array($this, 'export_settings'));       
	}
	
	/**
	 * export settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function export_settings($settings) {

		if(!class_exists('WP_Event_Manager_Form_Submit_Event')){
			include_once(EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-abstract.php');
			include_once(EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-submit-event.php');
		}

		$form_submit_event_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Event', 'instance'));
		$event_form_fields = $form_submit_event_instance->merge_with_custom_fields('frontend');
		$event_form_fields = apply_filters('event_manager_export_event_form_fields', $event_form_fields);
		
		$event_options = [];
		foreach($event_form_fields as $form_guoups => $form_fields){
			foreach($form_fields as $key => $field){
				$event_options['_'.$key] = $field['label'];
			}			
		}
		$event_options['_view_count'] = __('View Count', 'wp-event-manager-export');
		$settings['event_export'] =  array(
			__('Export', 'wp-event-manager-export'), 
			array(
				array(
					'name'    => 'event_manager_export_type', 
					'std'     => 'event_csv_default', 
					'label'   => __('Export Type', 'wp-event-manager-export'), 
					'desc'    => __('You can set event export type either event with meta key and value or specific column.', 'wp-event-manager-export'), 
					'type'    => 'select', 
					'options' => array(
						'event_csv_default' => __('Export file with meta key and value', 'wp-event-manager-export'), 
						'event_csv_custome' => __('Export file with custome fields', 'wp-event-manager-export'), 
					)
				), 
				array(
					'name'       => 'event_manager_export_event_fields', 
					'std'        => '', 
					'label'      => __('Event Fields', 'wp-event-manager-export'), 
					'desc'       => __('Export file with custome fields', 'wp-event-manager-export'),
					'type'       => 'multiselect', 
					'options' 	 => $event_options, 
					'attributes' => array(
						'data-multiple' => 'multiple', 
					), 
				), 
			)
		);

		if(get_option('enable_event_organizer')){
			if(!class_exists('WP_Event_Manager_Form_Submit_Organizer')){
				include_once(EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-abstract.php');
				include_once(EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-submit-organizer.php');
			}

			$form_submit_organizer_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Organizer', 'instance'));
			$organizer_form_fields = $form_submit_organizer_instance->merge_with_custom_fields('frontend');
			$organizer_form_fields = apply_filters('event_manager_export_organizer_form_fields', $organizer_form_fields);

			$organizer_options = [];
			foreach($organizer_form_fields as $form_guoups => $form_fields){
				foreach($form_fields as $key => $field){
					$organizer_options['_'.$key] = $field['label'];
				}			
			}
			$settings['event_export'][1][] = array(
				'name'       => 'event_manager_export_organizer_fields', 
				'std'        => '', 
				'label'      => __('Organizer Fields', 'wp-event-manager-export'), 
				'desc'       => __('Export file with custome fields', 'wp-event-manager-export'), 
				'type'       => 'multiselect', 
				'options' 	 => $organizer_options, 
				'attributes' => array(
					'data-multiple' => 'multiple', 
				), 
			);
		}

		if(get_option('enable_event_venue')){
			if(!class_exists('WP_Event_Manager_Form_Submit_Venue')){
				include_once(EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-abstract.php');
				include_once(EVENT_MANAGER_PLUGIN_DIR . '/forms/wp-event-manager-form-submit-venue.php');
			}
			
			$form_submit_venue_instance = call_user_func(array('WP_Event_Manager_Form_Submit_Venue', 'instance'));
			$venue_form_fields = $form_submit_venue_instance->merge_with_custom_fields('frontend');
			$venue_form_fields = apply_filters('event_manager_export_venue_form_fields', $venue_form_fields);

			$venue_options = [];
			foreach($venue_form_fields as $form_guoups => $form_fields){
				foreach($form_fields as $key => $field){
					$venue_options['_'.$key] = $field['label'];
				}			
			}
			$settings['event_export'][1][] = array(
					'name'       => 'event_manager_export_venue_fields', 
					'std'        => '', 
					'label'      => __('Venue Fields', 'wp-event-manager-export'), 
					'desc'       =>  __('Export file with custome fields', 'wp-event-manager-export'), 
					'type'       => 'multiselect', 
					'options' 	 => $venue_options, 
					'attributes' => array(
						'data-multiple' => 'multiple', 
					), 
			);
		}
        return $settings;		                                                          
	}
}
new WPEM_Export_Settings();