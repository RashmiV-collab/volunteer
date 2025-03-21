<?php
/**
 * WPEM_Recurring_Submit_Event_Form class.
 */
class WPEM_Recurring_Submit_Event_Form {
	
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Add filters
		add_filter( 'submit_event_form_fields', array( $this, 'init_fields') );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
	}
	
	/**
	 * frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		wp_register_script( 'wp-event-manager-recurring', WPEM_RECURRING_PLUGIN_URL . '/assets/js/event-recurring.min.js', array('jquery'), WPEM_RECURRING_VERSION, true );
		//localize javascript file
		wp_localize_script( 'wp-event-manager-recurring', 'event_manager_recurring_events', array(
			'every_day' 	 => __( 'day(s)' , 'wp-event-manager-recurring-events'),
			'every_week' 	 => __( 'week(s) on' , 'wp-event-manager-recurring-events'),
			'every_month' 	 => __( 'month(s) on' , 'wp-event-manager-recurring-events'),
			'ofthe_month' 	 => __( 'of the month(s)' , 'wp-event-manager-recurring-events'),
			'every_year' 	 => __( 'year(s) on' , 'wp-event-manager-recurring-events'),
			'i18n_datepicker_format' => WP_Event_Manager_Date_Time::get_datepicker_format(),
			'i18n_timepicker_format' => WP_Event_Manager_Date_Time::get_timepicker_format(),
			'i18n_timepicker_step' => WP_Event_Manager_Date_Time::get_timepicker_step(),
		) );
		wp_enqueue_script('wp-event-manager-recurring');
	}
	
	/**
	 * init_fields function.
	 * This function add the tickets fields to the submit event form
	 */
	public function init_fields($fields) 
	{
		$event_field_count = 50;

		if( isset($count['event']) && !empty($count['event']) ){
			$event_field_count = count($fields['event']);
		}

		$event_form_fields = get_option( 'event_manager_submit_event_form_fields' );

		if( isset($event_form_fields['event']) && !empty($event_form_fields['event']) ) {
			$event_field_count = count($event_form_fields['event']);
		}

		$fields['event']['event_recurrence'] = array(
				'label'=> __( 'Event Recurrence', 'wp-event-manager-recurring-events' ),
				'type'  => 'select',
				'default'  => 'no',
				'options'  => array(
						'no' 		    => __( "Don't repeat",'wp-event-manager-recurring-events'),
						'daily'         => __( 'Daily','wp-event-manager-recurring-events'),
						'weekly'        => __( 'Weekly','wp-event-manager-recurring-events'),
						'monthly'       => __( 'Monthly','wp-event-manager-recurring-events'),
						'yearly'        => __( 'Yearly','wp-event-manager-recurring-events')
				),
				'priority'    => $event_field_count + 1,
				'required'=>true
		);
		$fields['event']['recure_every'] = array(
				'label'			=> __( 'Repeat Every', 'wp-event-manager-recurring-events' ),
				'type'  		=> 'number',
				'default'  		=> '',
				'priority'    	=> $event_field_count + 2,
				'placeholder'	=> '',
				'required'		=> true,
				'description'	=>  ' '
		);
		$fields['event']['recure_time_period'] =  array(
				'label'		  => __('On The','wp-event-manager-recurring-events'),
				'type'        => 'radio',
				'required'    => true,
				'priority'    => $event_field_count + 3,
				'options'=> array(
						'same_time'		=> __( 'same day','wp-event-manager-recurring-events'),
						'specific_time'	=> __( 'specific day','wp-event-manager-recurring-events')
				)
		);
		$fields['event']['recure_month_day'] =  array(
				'label'		  => __('Day Number','wp-event-manager-recurring-events'),
				'type'        => 'select',
				'required'    => true,
				'priority'    => $event_field_count + 4,
				'options'=> array(
						'first'		=> __( 'First','wp-event-manager-recurring-events'),
						'second'	=> __( 'Second','wp-event-manager-recurring-events'),
						'third'		=> __( 'Third','wp-event-manager-recurring-events'),
						'fourth'	=> __( 'Fourth','wp-event-manager-recurring-events'),
						'last'		=> __( 'Last','wp-event-manager-recurring-events')
				)
		);
		$fields['event']['recure_weekday'] = array(
				'label'		  => __('Day Name','wp-event-manager-recurring-events'),
				'type'        => 'select',
				'required'    => true,
				'priority'    => $event_field_count + 5,
				'options'=> array(
						'sun'=> __( 'Sunday','wp-event-manager-recurring-events'),
						'mon'=> __( 'Monday','wp-event-manager-recurring-events'),
						'tue'=> __( 'Tuesday','wp-event-manager-recurring-events'),
						'wed'=> __( 'Wednesday','wp-event-manager-recurring-events'),
						'thu'=> __( 'Thursday','wp-event-manager-recurring-events'),
						'fri'=> __( 'Friday','wp-event-manager-recurring-events'),
						'sat'=> __( 'Saturday','wp-event-manager-recurring-events'),
				)
		);
		$fields['event']['recure_untill'] = array(
				'label'=> __( 'Repeat untill', 'wp-event-manager-recurring-events' ),
				'type'  => 'date',
				'default'  => '',
				'priority'    => $event_field_count + 5,
				'placeholder'	=> '',
				'required'=>true,
		);
		return $fields;
	}
}

new WPEM_Recurring_Submit_Event_Form(); ?>