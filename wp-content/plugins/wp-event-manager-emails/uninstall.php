<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// used to delete tables of this plugin when delete this plugin
$custom_tables = array(
	'wpem_email_templates'
);
if(!empty($custom_tables)){
	global $wpdb;
	foreach ( $custom_tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . $table );
	}
}

// used to delete data from options table once plugin will delete
$options = array(
	'new_event_email_nofication',
	'publish_event_email_nofication',
	'expired_event_email_nofication',
	'new_event_email_content',
	'new_event_email_subject',
	'published_event_email_content',
	'published_event_email_subject',
	'expired_event_email_content',
	'expired_event_email_subject',
	'admin_event_email_nofication',
	'organizer_mail_account_setting',
	'admin_event_email_content',
	'admin_event_email_subject',

	'wp-event-manager-emails_errors',
	'wp-event-manager-emails_licence_key',
	'wp-event-manager-emails_email',
	'wp-event-manager-emails_hide_key_notice',
);
foreach ( $options as $option ) {
	delete_option( $option );
}