<?php
/*
* This file use to cretae fields of wp event manager at admin side.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class WPEM_Event_Tags_Writepanels {
	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'manage_edit-event_listing_columns', array( $this, 'columns' ), 20 );
		add_filter( 'event_manager_event_listing_data_fields', array($this ,'event_listing_event_tags_fields') );
	}

	/**
	 * Add a event tag column to admin
	 * @return array
	 */
	public function columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( $key == 'event_listing_category' )
				$new_columns['event_tags'] = __( 'Tags', 'wp-event-manager-event-tags' );

			$new_columns[ $key ] = $value;
		}

		return $new_columns;
	}

	/**
	 * Add a event tag column to admin
	 * @return array
	 */
	public function event_listing_event_tags_fields( $fields ) {
		if( isset($fields['_event_tags']) )
			unset( $fields['_event_tags'] ); 

		return $fields;
	}
}
new WPEM_Event_Tags_Writepanels();