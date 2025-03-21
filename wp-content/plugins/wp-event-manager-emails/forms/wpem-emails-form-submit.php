<?php
/**
 * WPEM_Emails_Submit_Event_Form class.
 */
class WPEM_Emails_Submit_Event_Form {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add filters
		add_filter( 'submit_event_form_fields', array( $this, 'init_fields') );
	}
	
	/**
	 * init_fields function.
	 * This function add the email fields to the submit event form
	 */
	public function init_fields($fields) {
		$fields['event']['send_event_publish_notification'] = array(
			'label'    => __( 'Send Event Mail Notification', 'wp-event-manager-emails' ),					      	
			'type'     => 'radio',
			'default'  => 'no',
			'options'  => array(
						'no' => __( 'Publish Event', 'wp-event-manager-emails' ),
						'yes' => __( 'Update Event', 'wp-event-manager-emails' )
					 ),
			'priority'  => 25,
			'required'  =>true
		);
		return $fields;
	}
}
new WPEM_Emails_Submit_Event_Form(); ?>