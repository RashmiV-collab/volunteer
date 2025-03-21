<?php
/*
* This file use for setings at admin site for event alerts settings.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WPEM_Event_Tags_Submit_Event class.
 */
class WPEM_Event_Tags_Submit_Event {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct(){		
		add_filter( 'submit_event_form_validate_fields', array( $this, 'validate_event_tag_field' ), 10, 3 );
		add_action( 'event_manager_update_event_data', array( $this, 'save_event_tag_field' ), 10, 2 );
		add_action( 'submit_event_form_fields_get_event_data', array( $this, 'get_event_tag_field_data' ), 10, 2 );
	}

	/**
	 * validate fields
	 * @param  bool $passed
	 * @param  array $fields
	 * @param  array $values
	 * @return bool on success, wp_error on failure
	 */
	public function validate_event_tag_field( $passed, $fields, $values ) {
		$max  = get_option( 'event_manager_max_tags' );
		$tags = is_array( $values['event']['event_tags'] ) ? $values['event']['event_tags'] : array_filter( explode( ',', $values['event']['event_tags'] ) );

		if ( $max && sizeof( $tags ) > $max )
			throw new Exception( sprintf( __( 'Please enter no more than %d tags.', 'wp-event-manager-event-tags' ), $max ) );
		return $passed;
	}

	/**
	 * Save posted tags to the event
	 */
	public function save_event_tag_field( $event_id, $values ) {
		switch ( get_option( 'event_manager_tag_input' ) ) {
			case "multiselect" :
			case "checkboxes" :
				$tags = array_map( 'absint', $values['event']['event_tags'] );
			break;
			default :
				if ( is_array( $values['event']['event_tags'] ) ) {
					$tags = array_map( 'absint', $values['event']['event_tags'] );
				} else {
					$tags = array_filter( array_map( 'sanitize_text_field', explode( ',', $values['event']['event_tags'] ) ) );
				}
			break;
		}
		wp_set_object_terms( $event_id, $tags, 'event_listing_tag', false );
	}

	/**
	 * Get Event Tags for the field when editing
	 * @param  object $event
	 * @param  class $form
	 */
	public function get_event_tag_field_data( $data, $event ) {
		switch ( get_option( 'event_manager_tag_input' ) ) {
			case "multiselect" :
			case "checkboxes" :
				$data[ 'event' ][ 'event_tags' ]['value'] = wp_get_object_terms( $event->ID, 'event_listing_tag', array( 'fields' => 'ids' ) );
			break;
			default :
				$data[ 'event' ][ 'event_tags' ]['value'] = implode( ', ', wp_get_object_terms( $event->ID, 'event_listing_tag', array( 'fields' => 'names' ) ) );
			break;
		}
		return $data;
	}
}
new WPEM_Event_Tags_Submit_Event();