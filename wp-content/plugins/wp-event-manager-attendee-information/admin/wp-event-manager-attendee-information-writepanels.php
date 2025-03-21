<?php
/*
* This file use to cretae fields of gam event manager at admin side.
*/
if(!defined('ABSPATH')) exit; // Exit if accessed directly

class WP_Eevent_Manager_Attendee_Inforamtion_Writepanels {
    
    /**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter('event_manager_event_listing_data_fields', array($this, 'event_listing_attendee_inforatmation_fields'));
		add_filter('event_manager_registrations_settings', array($this, 'event_manager_attendee_inforatmation_settings'));

		add_filter('wp_event_manager_shortcode_plugin', array($this, 'add_attendee_shortcode_plugin_list'));
		add_action('wp_event_manager_shortcode_list', array($this, 'add_attendee_shortcode_list'));
	}
	
	/**
	 * add_attendee_shortcode_plugin_list function.
	 *
	 * @access public
	 * @return array
	 * @since 1.2.5
	 */
	public function add_attendee_shortcode_plugin_list($shortcode_plugins) {
		$shortcode_plugins['wp-event-manager-attendee-information'] =  __('WP Event Manager Attendee Information', 'wp-event-manager-attendee-information');
		return $shortcode_plugins;
	}

	/**
	 * add_attendee_shortcode_list function.
	 *
	 * @access public
	 * @return void
	 * @since 1.2.5
	 */
	public function add_attendee_shortcode_list($detail_link) { ?>
		<tr class="shortcode_list wp-event-manager-attendee-information">
			<td class="wpem-shortcode-td">[event_attendee event_id='event_id']</td>
			<td><?php _e('Event Attendee List', 'wp-event-manager-attendee-information');?></td>
			<td><?php _e('This will return all attendee of particular event', 'wp-event-manager-attendee-information');?></td>
			<td>-</td>
		</tr>
	<?php
	}

	/**
	 * event_listing_fields function.
	 *
	 * @access public
	 * @return void
	 */
	public static function event_listing_attendee_inforatmation_fields($fields) {
		$event_field_count = 50;

		if(!empty($fields)){
			$event_field_count = count($fields);	
		}

		$event_form_fields = get_option('event_manager_submit_event_form_fields');
		if(isset($event_form_fields['event']) && !empty($event_form_fields['event'])){
			$event_field_count = count($event_form_fields['event']);
		}

    	$fields['_attendee_information_type'] = array(
			'label'    => __('Attendee Information Collection Type', 'wp-event-manager-attendee-information'), 
			'type'     => 'radio', 
			'options'  => array(
							'buyer_only' => __('Buyer Only', 'wp-event-manager-attendee-information'), 
							'each_attendee' => __('Each Attendee', 'wp-event-manager-attendee-information'), 				
			), 
			'required' => true, 
			'priority' => $event_field_count + 1, 
      	);

	    //Buyer only and each attendee information field 
	    $options = WP_Event_Manager_Attendee_Information_Submit_Event_Form ::get_registration_fields_as_options(); 
	    $desc = empty($options) ? __('There is no any field in registration form', 'wp-event-manager-attendee-information') : __('Based on selected fields, you will collect information from the attendee', 'wp-event-manager-attendee-information');
	    $fields['_attendee_information_fields'] =  array(
			'label'       => __('Attendee Information To Collect', 'wp-event-manager-attendee-information'), 
			'type'        => 'multiselect', 
			'required'    => true, 
			'priority'    => $event_field_count + 2, 
			'description' => $desc, 
			'options'     =>  $options
		);

    	return $fields;							                       
	}
	
	/**
	 * attendee information setting function.
	 *
	 * @access public
	 * @return array
	 */
	public static function event_manager_attendee_inforatmation_settings($fields) {
	    $fields['attendee_inforatmation'] = array(
	        __('Attendee Information', 'wp-event-manager-attendee-information'), 
	        array(
	            array(
	                'name' 	   => 'event_registration_show_attendee', 
	                'std' 	   => '0', 
	                'label'    => __('Show Attendee', 'wp-event-manager-attendee-information'), 
	                'cb_label' => __('Show attendee Publicly', 'wp-event-manager-attendee-information'), 
					'desc'	   => __('Show attendee publicly at single event page.', 'wp-event-manager-attendee-information'), 
	                'type'     => 'checkbox'
	         	), 
	        	array(
	        			'name' 	   => 'event_registration_attendee_limit', 
	        			'std' 	   => '10', 
	        			'label'    => __('Attendee Per Page', 'wp-event-manager-attendee-information'), 
	        			'cb_label' => __('Show attendee per page', 'wp-event-manager-attendee-information'), 
					'desc'	   => __('Default 10 attendee show at single event page.', 'wp-event-manager-attendee-information'), 
	        			'type'     => 'text'
	        	)
	     	)
	 	);
	    return $fields;
	}
}
new WP_Eevent_Manager_Attendee_Inforamtion_Writepanels(); ?>
