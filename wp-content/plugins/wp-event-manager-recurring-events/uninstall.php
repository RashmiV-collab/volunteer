<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$options = array();
/*
foreach ( $options as $option ) {
	delete_option( $option );
}
*/
$all_fields = get_option( 'event_manager_form_fields', true );
if(is_array($all_fields)){
	$recurring_fields = array('event_recurrence','recure_every','recure_time_period','recure_month_day','recure_weekday','recure_untill');
	foreach ($recurring_fields as $key => $value) {
		if(isset($all_fields['event'][$value]))
			unset($all_fields['event'][$value]);
	}
}
update_option('event_manager_form_fields', $all_fields);