<?php
/**
 * WP_Event_Manager_Attendee_Information_Submit_Event_Form class is used to add attendee information plugin form fields.
 */
class WP_Event_Manager_Attendee_Information_Submit_Event_Form {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add filters
		add_filter('submit_event_form_fields', array($this, 'init_fields'));
	}
	
	/**
	 * init_fields function.
	 * This function add the Attendee Information fields to the submit event form
	 * @return $fields
	 */
	public function init_fields($fields) {
        $event_field_count = 50;

        if(isset($fields['event']) && !empty($fields['event'])){
            $event_field_count = count($fields['event']);
        }

        $event_form_fields = get_option('event_manager_submit_event_form_fields');
        if(isset($event_form_fields['event']) && !empty($event_form_fields['event'])){
            $event_field_count = count($event_form_fields['event']);
        }

        $fields['event']['attendee_information_type'] = array(
    		'label'    => __('Attendee Information Collection type', 'wp-event-manager-attendee-information'),
    		'type'     => 'radio',
    		'options'  => array(
    						'buyer_only' => __('Buyer Only', 'wp-event-manager-attendee-information'),
    						'each_attendee' => __('Each Attendee', 'wp-event-manager-attendee-information'),	
                        ),
    		'required' => true,
    		'priority' => $event_field_count + 1,
   		);
    									   
        //Buyer only and each attendee information field 
        $options = $this->get_registration_fields_as_options(); 
        $desc = empty($options) ? __('There is no any field in registration form','wp-event-manager-attendee-information') : __('Based on selected fields, you will collect information from the attendee','wp-event-manager-attendee-information');

        $fields['event']['attendee_information_fields'] =  array(
    		'label'       => __('Attendee Information to collect', 'wp-event-manager-attendee-information'),
    		'type'        => 'multiselect',
    		'required'    => true,
    		'priority'    => $event_field_count + 2,
    		'description' => $desc,
    		'options'     =>  $options
    	);

    	return $fields;
	}
	
    /**
     * get_registration_fields_as_options   
     * This function return array of option from the all the registration fields 
     * This option is used in the multiselect box in event submit 
     * @return $registration_fields_options
     */
    public static function get_registration_fields_as_options(){        
    	if(!function_exists('get_event_registration_form_fields')){
    		return;
    	}
    	$registration_fields = get_event_registration_form_fields();
        
        foreach($registration_fields as $key => $value){
            $registration_fields_options[$key] = $value['label'];
        }
    	return $registration_fields_options;
    }
}
new WP_Event_Manager_Attendee_Information_Submit_Event_Form(); ?>
