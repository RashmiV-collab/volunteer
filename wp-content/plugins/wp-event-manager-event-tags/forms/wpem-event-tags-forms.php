<?php
/**
 * WPEM_Event_Tags_Forms class.
 */
class WPEM_Event_Tags_Forms {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'submit_event_form_fields', array( $this, 'event_tag_field' ) );
	}

	/**
	 * Add the event tag field to the submission form
	 * @return array
	 */
	public function event_tag_field( $fields ) {
		if ( $max = get_option( 'event_manager_max_tags' ) ) {
			$max = ' ' . sprintf( __( 'Maximum of %d.', 'wp-event-manager-event-tags' ), $max );
		}
		switch ( get_option( 'event_manager_tag_input' ) ) {
			case "multiselect" :
				$fields['event']['event_tags'] = array(
					'label'       => __( 'Event tags', 'wp-event-manager-event-tags' ),
					'description' => __( 'Choose some tags, such as required drama or festival, for this event.', 'wp-event-manager-event-tags' ) . $max,
					'type'        => 'term-multiselect',
					'taxonomy'    => 'event_listing_tag',
					'required'    => false,
					'priority'    => "4.5"
				);
			break;
			case "checkboxes" :
				$fields['event']['event_tags'] = array(
					'label'       => __( 'Event tags', 'wp-event-manager-event-tags' ),
					'description' => __( 'Comma separate tags, such as required drama, festival or technologies, for this event.', 'wp-event-manager-event-tags' ) . $max,
					'type'        => 'term-checklist',
					'taxonomy'    => 'event_listing_tag',
					'required'    => false,
					'priority'    => "4.5"
				);
			break;
			default :
				$fields['event']['event_tags'] = array(
					'label'       => __( 'Event tags', 'wp-event-manager-event-tags' ),
					'description' => __( 'Comma separate tags, such as required like event type or content for this event.', 'wp-event-manager-event-tags' ) . $max,
					'type'        => 'text',
					'required'    => false,
					'placeholder' => __( 'e.g. Meeting or Networking Events , Drama', 'wp-event-manager-event-tags' ),
					'priority'    => "4.5"
				);
			break;
		}
		return $fields;
	}
}
new WPEM_Event_Tags_Forms();