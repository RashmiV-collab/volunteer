<?php
if(!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

$all_fields = get_option('event_manager_form_fields', true);
if(is_array($all_fields)){
	$attendee_fields = array('attendee_information_type','attendee_information_fields');
	foreach ($attendee_fields as $key => $value) {
		if(isset($all_fields['event'][$value]))
			unset($all_fields['event'][$value]);
	}
}
update_option('event_manager_form_fields', $all_fields);