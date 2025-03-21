<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * WPEM_Guests_Ajax class.
 */
class WPEM_Guests_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		
		add_action( 'wp_ajax_get_group_by_event_id', array( $this, 'get_group_by_event_id' ) );

		add_action( 'wp_ajax_get_group_fields_by_group_id', array( $this, 'get_group_fields_by_group_id' ) );

		add_action( 'wp_ajax_update_event_guest_checkin_data', array($this,'update_event_guest_checkin_data'));
		add_action( 'wp_ajax_delete_guests', array($this,'delete_guests'));
	}

	/**
	 * get_group_by_event_id
	 */
	public function get_group_by_event_id() {
		
		check_ajax_referer( '_nonce_wpem_guests_security', 'security' );

		$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : '';

		$groups = get_event_guests_group( '', '', $event_id );

		$output = '<option value="">' . __( 'Select Group', 'wp-event-manager-guests' ) . '</option>';
		if(!empty($groups))
		{
			foreach ($groups as $group) 
			{
				$output .= '<option value="' . $group->id . '">' . $group->group_name . '</option>';
			}
		}

		echo $output;

		wp_die();
	}

		/**
	 * get_group_by_event_id
	 */
	public function get_group_fields_by_group_id() {
		
		check_ajax_referer( '_nonce_wpem_guests_security', 'security' );

		$group_id = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : '';

		$group = get_event_guests_group( $group_id );

		$fields = [];

		if(!empty($group))
		{
			$fields = json_decode($group->group_fields, true);	
		}

		wp_send_json( $fields );

		wp_die();
	}



/**
* Update the post meta of registration post type : checkin or undo check in 
* This method used at admin and frontend side to update post meta key : _check_in via ajax.
*/

public function update_event_guest_checkin_data()
{
	check_ajax_referer( '_nonce_wpem_guests_security', 'security' );
	
	$check_in_value = $_POST['check_in_value'];
	$guest_id = $_POST['guest_id'];
	$checkin_source = $_POST['source'];
	
	if(isset($guest_id) && isset($check_in_value)){
		update_post_meta($guest_id ,'_check_in', $check_in_value);	
		//echo get_total_checkedin_by_event_id();
	}

	if(isset($guest_id) && isset($checkin_source)){
		update_post_meta($guest_id ,'_checkin_source', $checkin_source);
		//echo get_total_checkedin_by_event_id();
	}

	wp_send_json(array('message'=>__('Checking updated succefully.','wpem-guests')));
}

	public function delete_guests(){
		check_ajax_referer( '_nonce_wpem_guests_security', 'security' );
		$guests_id = $_POST['delete_guests_id'];
		$guests_id = explode(',', $guests_id);

		if(isset($guests_id)){
			foreach($guests_id as $key => $value){
			 wp_trash_post($value);
			}
		}
		wp_die();
	}

}

new WPEM_Guests_Ajax();
