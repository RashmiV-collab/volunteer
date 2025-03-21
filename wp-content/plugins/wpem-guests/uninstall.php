<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
$options = array(
        'event_guests_form_fields',
        'event_guests_organizer_email_content',
        'event_guests_organizer_email_subject',
        'event_guests_email_content',
        'event_guests_email_subject',
);

$allposts= get_posts( array('post_type'=>'event_guests','numberposts'=>-1) );
foreach ($allposts as $eachpost) {
  wp_delete_post( $eachpost->ID, true );
}
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wpem_guests_group");
foreach ( $options as $option ) {
    delete_option( $option );
}
