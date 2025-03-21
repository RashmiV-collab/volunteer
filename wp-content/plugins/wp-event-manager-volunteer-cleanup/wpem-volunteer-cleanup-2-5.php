<?php

final class WPEM_VOLUNTEER_2_5 {
	
	private static $_instance = null;
	
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct() {

        add_filter('wp_event_manager_timezone_choice', [$this,'volunteer_wpem_timezone'],10,1);
    }

    //
    function volunteer_wpem_timezone($timezone){
        $selected_zone = WP_Event_Manager_Date_Time::get_current_site_timezone();
        $locale = get_user_locale();

        static $mo_loaded = false, $locale_loaded = null;

	    $continents = array( 'America' );

        // Load translations for continents and cities.
        if ( ! $mo_loaded || $locale !== $locale_loaded ) {
            $locale_loaded = $locale ? $locale : get_locale();
            $mofile        = WP_LANG_DIR . '/continents-cities-' . $locale_loaded . '.mo';
            unload_textdomain( 'continents-cities', true );
            load_textdomain( 'continents-cities', $mofile, $locale_loaded );
            $mo_loaded = true;
        }

        $tz_identifiers = timezone_identifiers_list();
        $zonen          = array();
        foreach ( $tz_identifiers as $zone ) {
            $zone = explode( '/', $zone );
            if ( ! in_array( $zone[0], $continents, true ) ) {
                continue;
            }

            // This determines what gets set and translated - we don't translate Etc/* strings here, they are done later.
            $exists    = array(
                0 => ( isset( $zone[0] ) && $zone[0] ),
                1 => ( isset( $zone[1] ) && $zone[1] ),
                2 => ( isset( $zone[2] ) && $zone[2] ),
            );
            $exists[3] = ( $exists[0] && 'Etc' !== $zone[0] );
            $exists[4] = ( $exists[1] && $exists[3] );
            $exists[5] = ( $exists[2] && $exists[3] );

            // phpcs:disable WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
            $zonen[] = array(
                'continent'   => ( $exists[0] ? $zone[0] : '' ),
                'city'        => ( $exists[1] ? $zone[1] : '' ),
                'subcity'     => ( $exists[2] ? $zone[2] : '' ),
                't_continent' => ( $exists[3] ? translate( str_replace( '_', ' ', $zone[0] ), 'continents-cities' ) : '' ),
                't_city'      => ( $exists[4] ? translate( str_replace( '_', ' ', $zone[1] ), 'continents-cities' ) : '' ),
                't_subcity'   => ( $exists[5] ? translate( str_replace( '_', ' ', $zone[2] ), 'continents-cities' ) : '' ),
            );
            // phpcs:enable
        }
        usort( $zonen, '_wp_timezone_choice_usort_callback' );

        $structure = array();

        if ( empty( $selected_zone ) ) {
            $structure[] = '<option selected="selected" value="">' . __( 'Select a city' ) . '</option>';
        }

        // If this is a deprecated, but valid, timezone string, display it at the top of the list as-is.
        if ( in_array( $selected_zone, $tz_identifiers, true ) === false
            && in_array( $selected_zone, timezone_identifiers_list( DateTimeZone::ALL_WITH_BC ), true )
        ) {
            $structure[] = '<option selected="selected" value="' . esc_attr( $selected_zone ) . '">' . esc_html( $selected_zone ) . '</option>';
        }

        foreach ( $zonen as $key => $zone ) {
            // Build value in an array to join later.
            $value = array( $zone['continent'] );

            if ( empty( $zone['city'] ) ) {
                // It's at the continent level (generally won't happen).
                $display = $zone['t_continent'];
            } else {
                // It's inside a continent group.

                // Continent optgroup.
                if ( ! isset( $zonen[ $key - 1 ] ) || $zonen[ $key - 1 ]['continent'] !== $zone['continent'] ) {
                    $label       = $zone['t_continent'];
                    $structure[] = '<optgroup label="' . esc_attr( $label ) . '">';
                }

                // Add the city to the value.
                $value[] = $zone['city'];

                $display = $zone['t_city'];
                if ( ! empty( $zone['subcity'] ) ) {
                    // Add the subcity to the value.
                    $value[]  = $zone['subcity'];
                    $display .= ' - ' . $zone['t_subcity'];
                }
            }

            // Build the value.
            $value    = implode( '/', $value );
            $selected = '';
            if ( $value === $selected_zone ) {
                $selected = 'selected="selected" ';
            }
            $structure[] = '<option ' . $selected . 'value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';

            // Close continent optgroup.
            if ( ! empty( $zone['city'] ) && ( ! isset( $zonen[ $key + 1 ] ) || ( isset( $zonen[ $key + 1 ] ) && $zonen[ $key + 1 ]['continent'] !== $zone['continent'] ) ) ) {
                $structure[] = '</optgroup>';
            }
        }

	    return implode( "\n", $structure );
    }
}

WPEM_VOLUNTEER_2_5::instance();


// send co-host request mail
 add_action('wp_ajax_nopriv_wpem_send_co_host_request',  'wpem_send_co_host_request');
 add_action('wp_ajax_wpem_send_co_host_request',  'wpem_send_co_host_request');

function wpem_send_co_host_request(){
    $host = array();
    if (isset($_POST['host'])) {
        $host = explode(',', $_POST['host']);
        if (is_array($host)) {
            print_R($host);
        }
    }
    echo json_encode(array('success' => true)); exit;
}


add_shortcode('event_alert_unsusbcribe',  'volunteer_event_co_host');
function volunteer_event_co_host(){

}


// getting event organizer permalink
function get_current_user_event_organizer_url() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();

        // First, try finding an event_organizer post linked via post_author
        $args = array(
            'post_type'      => 'event_organizer',
            'post_status'    => 'publish',
            'author'         => $user_id, // Retrieve by author if possible
            'posts_per_page' => 1,
        );

        $organizer_posts = get_posts($args);

        // If no post found by author, check if a custom meta field is used
        if (empty($organizer_posts)) {
            $args = array(
                'post_type'      => 'event_organizer',
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => '_organizer_user_id', // Change this if a different meta key is used
                        'value' => $user_id,
                    ),
                ),
                'posts_per_page' => 1,
            );
            $organizer_posts = get_posts($args);
        }

        // If we found an organizer post, get the permalink
        if (!empty($organizer_posts)) {
            $organizer_permalink = get_permalink($organizer_posts[0]->ID);
        } else {
            return '<span>No organizer profile found.</span>';
        }

        // Return URL with Copy Icon
        return '<span id="userProfileUrl">' . esc_url($organizer_permalink) . '</span>
                <span onclick="copyUserProfileUrl()" style="cursor: pointer; margin-left: 8px;" title="Copy URL">
                    <img src="' . WPEM_VOLUNTEER_URI . '/assets/img/copy-icon.webp" alt="Copy Icon" class="copy-icon">
                </span>
                <script>
                    function copyUserProfileUrl() {
                        var text = document.getElementById("userProfileUrl").innerText;
                        navigator.clipboard.writeText(text);
                        alert("Copied: " + text);
                    }
                </script>';
    }
    return ''; // Return empty if no user is logged in
}
add_shortcode('current_organizer_url_with_copy', 'get_current_user_event_organizer_url');


// getting current user profile slug without copy icon on the /dashboard/profile page
function get_current_user_event_organizer_url_without_copy() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();

        // First, try finding an event_organizer post linked via post_author
        $args = array(
            'post_type'      => 'event_organizer',
            'post_status'    => 'publish',
            'author'         => $user_id, // Retrieve by author if possible
            'posts_per_page' => 1,
        );

        $organizer_posts = get_posts($args);

        // If no post found by author, check if a custom meta field is used
        if (empty($organizer_posts)) {
            $args = array(
                'post_type'      => 'event_organizer',
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'   => '_organizer_user_id', // Change this if a different meta key is used
                        'value' => $user_id,
                    ),
                ),
                'posts_per_page' => 1,
            );
            $organizer_posts = get_posts($args);
        }

        // If we found an organizer post, get the permalink
        if (!empty($organizer_posts)) {
            $organizer_permalink = get_permalink($organizer_posts[0]->ID);
        } else {
            return '<span>No organizer profile found.</span>';
        }

        // Return URL with Copy Icon
        return '<span>' . esc_url($organizer_permalink) . '</span>';
    }
    return ''; // Return empty if no user is logged in
}
add_shortcode('current_organizer_url_without_copy', 'get_current_user_event_organizer_url_without_copy');


// creating shortcode to display Display_name of a wordpress user for the /dashboard/profile/ page
function getting_display_name() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        
        // Get Display Name
        $display_name = !empty($user->display_name) ? trim($user->display_name) : trim($user->user_login);
        
        // Construct final URL
        return esc_html($display_name);
    }
    return ''; // Return empty if no user is logged in
}
add_shortcode('getting_user_display_name', 'getting_display_name');


// activating duplicate option for the expired events on the frontend
function add_duplicate_button_for_expired_events($actions, $event) {
    // Check if event is expired
    if ($event->post_status === 'expired') {
        $duplicate_url = add_query_arg(array(
            'action'   => 'duplicate',
            'event_id' => $event->ID,
        ), home_url('/dashboard/event-dashboard/'));

        $actions['duplicate'] = '<a href="' . esc_url($duplicate_url) . '" class="button">Duplicate</a>';
    }
    return $actions;
}
add_filter('event_manager_my_event_actions', 'add_duplicate_button_for_expired_events', 10, 2);

// Ensure session starts properly

function process_event_duplication() {
    if (isset($_GET['action']) && $_GET['action'] === 'duplicate' && isset($_GET['event_id'])) {
        $event_id = intval($_GET['event_id']);

        if (!$event_id) {
            wp_die("Invalid event ID.");
        }

        $original_event = get_post($event_id);

        if (!$original_event || $original_event->post_type !== 'event_listing') {
            wp_die("Invalid event ID.");
        }

        // Create a new event post
        $new_event_id = wp_insert_post(array(
            'post_title'    => $original_event->post_title . ' (Duplicate)',
            'post_content'  => $original_event->post_content,
            'post_status'   => 'publish',
            'post_type'     => 'event_listing',
            'post_author'   => get_current_user_id(),
        ));

        if (!$new_event_id) {
            wp_die("Event duplication failed.");
        }

        // Fields to copy
        $fields_to_copy = array(
            'event_title', '_event_start_date', '_event_start_time', '_event_end_date', '_event_end_time',
            '_event_location', '_event_country', '_event_pincode', '_event_banner', '_flyer',
            '_event_category', '_featured', '_cancelled', 'tax_input[event_listing_category][]', '_event_description', '_meeting_spot_details', '_parking_info', '_private_event',
            '_event_online', '_attendee_information_type', '_event_expiry_date', '_event_timezone'
        );

        // Copy specific meta fields
        foreach ($fields_to_copy as $field) {
            $value = get_post_meta($event_id, $field, true);
            if (!empty($value)) {
                update_post_meta($new_event_id, $field, maybe_unserialize($value));
            }
        }

        // Copy select fields that are stored as arrays
        $select_fields = array('_what_should_volunteers_bring?', '_what_will_be_provided?', '_event_organizer_ids', '_attendee_information_fields', '_event_venue_ids');
        foreach ($select_fields as $field) {
            $value = get_post_meta($event_id, $field, false); // Get multiple values
            if (!empty($value)) {
                foreach ($value as $val) {
                    add_post_meta($new_event_id, $field, maybe_unserialize($val));
                }
            }
        }

        // Ensure required meta fields exist
        update_post_meta($new_event_id, 'event_status', 'publish');
        update_post_meta($new_event_id, '_event_listing_status', 'publish');

        // Copy taxonomies (categories, location, types)
        $taxonomies = array('event_listing_category', 'event_listing_location', 'event_listing_type');
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($event_id, $taxonomy, array('fields' => 'ids'));
            if (!empty($terms)) {
                wp_set_post_terms($new_event_id, $terms, $taxonomy);
            }
        }

        // Ensure event exists before redirecting
        if (get_post_status($new_event_id) === 'publish') {
            $edit_url = add_query_arg(array(
                'action'   => 'edit',
                'event_id' => $new_event_id,
            ), home_url('/dashboard/event-dashboard/'));

            wp_redirect($edit_url);
            exit;
        } else {
            wp_die("Invalid listing. Event duplication might have failed.");
        }
    }
}
add_action('template_redirect', 'process_event_duplication');


// Adding full event url on the /?action=edit&event_id=123 pages (on event dashboard)
function get_published_event_url() {
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['event_id'])) {
        $event_id = intval($_GET['event_id']); // Get the event ID from the URL
        
        // Get the published event permalink
        $event_url = get_permalink($event_id);

        if ($event_url) {
            return '<span><strong>Event URL:</strong> <span id="eventPublicUrl">' . esc_url($event_url) . '</span>
                    <span onclick="copyEventPublicUrl()" style="cursor: pointer; margin-left: 8px;" title="Copy Event URL">
                        <img src="' . WPEM_VOLUNTEER_URI . '/assets/img/copy-icon.webp" alt="Copy Icon" class="copy-icon">
                    </span></span>
                    <script>
                        function copyEventPublicUrl() {
                            var text = document.getElementById("eventPublicUrl").innerText;
                            navigator.clipboard.writeText(text);
                            alert("Copied: " + text);
                        }
                    </script>';
        }
    }
    return ''; // Return empty if event_id is not found or event doesn't exist
}
add_shortcode('published_event_url', 'get_published_event_url');


// Register the 'event_url' shortcode for event emails.
function my_custom_event_email_shortcodes($data) {
    if (!isset($data['event_id'])) return; // Prevent errors if event ID is missing

    $event_id = $data['event_id'];
    $event_url = get_permalink($event_id); // Get event permalink

    add_shortcode('event_url', function($atts, $content = '') use ($event_url) {
        return event_manager_email_shortcode_handler($atts, $content, $event_url);
    });
}
add_action('new_event_email_add_shortcodes', 'my_custom_event_email_shortcodes');

// Ensure 'event_url' appears in the email tags list.
function my_custom_event_email_tags($tags) {
    // Ensure the global email tags array is properly handled
    if (!is_array($tags)) {
        $tags = array();
    }

    $tags['event_url'] = __('Event URL', 'wp-event-manager-emails'); 
    return $tags;
}
add_filter('event_manager_email_tags', 'my_custom_event_email_tags', 10, 1);

