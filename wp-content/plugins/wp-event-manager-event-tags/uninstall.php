<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete all taxonomy on delete
$taxonomy = 'event_listing_tag'; // Replace 'your_taxonomy' with the name of the taxonomy you want to remove terms from

// Get all terms of the specified taxonomy
$terms = get_terms(array(
    'taxonomy' => $taxonomy,
    'hide_empty' => false, // Include terms even if they are not associated with any posts
));

if (!empty($terms) && !is_wp_error($terms)) {
    foreach ($terms as $term) {
        $result = wp_delete_term($term->term_id, $taxonomy);
        if (is_wp_error($result)) {
            // Error occurred while deleting the term
            echo "Error deleting term: " . $term->name . " - " . $result->get_error_message();
        }
    }
} 

// Delete all options on delete plugin
$options = array(
	'event_manager_enable_tag_archive',
    'event_manager_tags_filter_type',
	'event_manager_max_tags',
	'event_manager_tag_input',

	'wp-event-manager-event-tags_errors',
	'wp-event-manager-event-tags_licence_key',
	'wp-event-manager-event-tags_email',
	'wp-event-manager-event-tags_hide_key_notice',
	'wp_event_manager_event_tags_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Update form field on delete plugin
$all_fields = get_option( 'event_manager_form_fields', true );
if(is_array($all_fields)){
	$event_tags_fields = array('event_tags');
	foreach ($event_tags_fields as $key => $value) {
		if(isset($all_fields['event'][$value]))
			unset($all_fields['event'][$value]);
	}
}
update_option('event_manager_form_fields', $all_fields);