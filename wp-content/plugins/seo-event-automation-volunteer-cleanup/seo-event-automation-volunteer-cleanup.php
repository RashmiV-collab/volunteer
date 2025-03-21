<?php
/**
 * Plugin Name: SEO Event Automation Volunteer Cleanup
 * Plugin URI:  https://yourwebsite.com
 * Description: Automatically sets focus keywords and event schema for event posts.
 * Version:     1.0
 * Author:      Your Name
 * Author URI:  https://yourwebsite.com
 * License:     GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Extract Address Parts (City & State) from Full Address
function extract_address_parts($full_address) {
    $address_parts = [];

    if (preg_match('/^(.*?),\s*([^,]+),\s*([A-Z]{2})\b/', $full_address, $matches)) {
        $address_parts['city'] = trim($matches[2]);
        $address_parts['state'] = trim($matches[3]);
    }

    return $address_parts;
}

// Extract Specific Keywords from Title (Supports Multi-Word Keywords)
function extract_title_keywords($title) {
    $allowed_keywords = [
        'cleanup', 'beach', 'volunteer cleanup event', 
        'community', 'service', 'eco', 'green', 'nature'
    ];

    $title_lower = strtolower($title);

    // Exact Full-Title Matching with Allowed Multi-Word Keywords
    if (in_array($title_lower, $allowed_keywords)) {
        return [$title_lower]; // Include full title if it matches
    }

    // Individual Word Matching
    $words = explode(' ', $title_lower);
    $filtered_words = array_intersect($words, $allowed_keywords);

    return array_unique($filtered_words);
}

// Extract Relevant Keywords from Description (Supports Multi-Word Keywords)
function extract_relevant_keywords_from_description($description, $max_words = 5) {
    $relevant_keywords = [];

    $target_keywords = [
        'cleanup event', 'volunteer cleanup', 'community help', 
        'beach', 'park', 'waterway', 'service'
    ];

    $cleaned_description = strtolower(strip_tags($description));

    // Multi-Word Keywords Matching
    foreach ($target_keywords as $keyword) {
        if (stripos($cleaned_description, $keyword) !== false) {
            $relevant_keywords[] = $keyword;
        }
    }

    // Individual Word Matching for Remaining Keywords
    $words = explode(' ', $cleaned_description);
    foreach ($words as $word) {
        $cleaned_word = strtolower(trim($word, ',.!?'));
        if (in_array($cleaned_word, $target_keywords) && !in_array($cleaned_word, $relevant_keywords)) {
            $relevant_keywords[] = $cleaned_word;
        }

        if (count($relevant_keywords) >= $max_words) break;
    }

    return array_unique($relevant_keywords);
}

// Generate Focus Keywords
function set_dynamic_focus_keyword($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (wp_is_post_revision($post_id)) return;

    if (get_post_type($post_id) !== 'event_listing') return;

    $event_title = get_the_title($post_id);
    $filtered_event_title = extract_title_keywords($event_title); 
    $event_location = get_post_meta($post_id, '_event_location', true);
    $event_description = get_the_excerpt($post_id); 
    $organizer_names = [];

    $organizer_ids = get_post_meta($post_id, '_event_organizer_ids', true);
    if (!empty($organizer_ids) && is_array($organizer_ids)) {
        foreach ($organizer_ids as $organizer_id) {
            $organizer_name = get_the_title($organizer_id);
            if (!empty($organizer_name)) {
                $organizer_names[] = $organizer_name;
            }
        }
    }

    $address_parts = extract_address_parts($event_location);
    $event_categories = wp_get_post_terms($post_id, 'event_listing_category', array('fields' => 'names'));
    $event_type = !empty($event_categories) ? implode(', ', $event_categories) : '';

    // Predefined General Keywords
    $focus_keywords = [
        'volunteer',
        'cleanup',
        'community service'
    ];

    // Add Dynamic Keywords
    $focus_keywords = array_merge($focus_keywords, $filtered_event_title);

    if (!empty($event_type)) $focus_keywords[] = $event_type . ' cleanup';
    if (!empty($address_parts['city'])) $focus_keywords[] = $address_parts['city'];
    if (!empty($address_parts['state'])) $focus_keywords[] = $address_parts['state'];
    if (!empty($organizer_names)) $focus_keywords[] = implode(', ', $organizer_names);

    // Add Description Keywords
    if (!empty($event_description)) {
        $desc_keywords = extract_relevant_keywords_from_description($event_description);
        $focus_keywords = array_merge($focus_keywords, $desc_keywords);
    }

    // Ensure NO Duplicates and Limit to 10 Keywords
    $focus_keywords = array_slice(array_unique($focus_keywords), 0, 10);

    $focus_keyword_str = implode(', ', $focus_keywords);
    update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($focus_keyword_str));
}
add_action('save_post_event_listing', 'set_dynamic_focus_keyword');

// Add Custom Meta Keywords in <head> (Ensuring No Duplicates in View Source)
function add_custom_meta_keywords_to_head() {
    if (is_singular('event_listing')) {
        global $post;
        $focus_keywords = get_post_meta($post->ID, 'rank_math_focus_keyword', true);

        if (!empty($focus_keywords)) {
            $unique_keywords = implode(', ', array_unique(explode(', ', $focus_keywords)));
            echo '<meta name="keywords" content="' . esc_attr($unique_keywords) . '">' . "\n";
        }
    }
}
add_action('wp_head', 'add_custom_meta_keywords_to_head');

// Disable Rank Math's Default Meta Keyword Output
function disable_rank_math_meta_keywords($output) {
    if (get_post_type() === 'event_listing') {
        return '';
    }
    return $output;
}
add_filter('rank_math/frontend/keywords', 'disable_rank_math_meta_keywords', 10, 1);

// Ensure Rank Math Metabox is Visible
add_filter('rank_math/metabox/priority', function() {
    return 'high';
});





// Add Dynamic Event Schema with Multiple Organizers
function add_dynamic_event_schema() {
    if (get_post_type() != 'event_listing') {
        return;
    }

    global $post;
    $event_title = get_the_title();
    $event_location = get_post_meta(get_the_ID(), '_event_location', true);
    $event_categories = wp_get_post_terms(get_the_ID(), 'event_listing_category', ['fields' => 'names']);
    $event_type = !empty($event_categories) ? implode(', ', $event_categories) : "General Cleanup";
    
    // Fetch Organizer Names & URLs from `_event_organizer_ids[]`
    $organizer_ids = get_post_meta(get_the_ID(), '_event_organizer_ids', true);
    $organizers = [];

    if (!empty($organizer_ids) && is_array($organizer_ids)) {
        foreach ($organizer_ids as $organizer_id) {
            $organizer_name = get_the_title($organizer_id);
            $organizer_url = get_permalink($organizer_id); // Fetch Organizer's Permalink

            if (!empty($organizer_name) && !empty($organizer_url)) {
                $organizers[] = [
                    "@type" => "Organization",
                    "name" => $organizer_name,
                    "url" => $organizer_url
                ];
            }
        }
    }

    // If no organizer is found, add default
    if (empty($organizers)) {
        $organizers[] = [
            "@type" => "Organization",
            "name" => "Volunteer Cleanup",
            "url" => get_permalink() // Default to event URL
        ];
    }

    // Fetch start and end date/time
    $event_start_date = get_post_meta(get_the_ID(), '_event_start_date', true);
    $event_end_date = get_post_meta(get_the_ID(), '_event_end_date', true);

    // Convert time to ISO 8601 format if it exists
    $event_start_iso = !empty($event_start_date) ? date('c', strtotime($event_start_date)) : date('c');
    $event_end_iso = !empty($event_end_date) ? date('c', strtotime($event_end_date)) : date('c', strtotime('+3 hours'));

    $event_thumbnail = get_the_post_thumbnail_url();
    $event_url = get_permalink();
    $event_description = strip_tags(get_the_excerpt());

    $schema_data = [
        "@context" => "https://schema.org",
        "@type" => "Event",
        "name" => $event_title,
        "startDate" => $event_start_iso, // ISO 8601 format
        "endDate" => $event_end_iso, // ISO 8601 format
        "eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
        "eventStatus" => "https://schema.org/EventScheduled",
        "location" => [
            "@type" => "Place",
            "name" => $event_location ?: "Unknown Location",
            "address" => [
                "@type" => "PostalAddress",
                "addressLocality" => $event_location,
                "addressCountry" => "US"
            ]
        ],
        "organizer" => $organizers, // Use array of organizers
        "image" => $event_thumbnail ?: "https://example.com/default-thumbnail.jpg",
        "description" => $event_description,
        "url" => $event_url
    ];

    echo '<script type="application/ld+json">' . json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

add_action('wp_head', 'add_dynamic_event_schema');

// removing rank math's schema
function remove_wp_event_manager_schema() {
    ob_start(function($output) {
        return preg_replace_callback('/<script type="application\/ld\+json">(.*?)<\/script>/s', function($matches) {
            $jsonData = json_decode($matches[1], true);
            // Check if it's the WP Event Manager schema (and not our custom one)
            if ($jsonData && isset($jsonData['@context']) && isset($jsonData['@type']) && $jsonData['@type'] === 'Event' && strpos($matches[1], 'wpem-placeholder-wide.jpg') !== false) {
                return ''; // Remove only the default WP Event Manager schema
            }
            return $matches[0]; // Keep everything else
        }, $output);
    });
}
add_action('wp_head', 'remove_wp_event_manager_schema', 1);

