<?php
/**
 * get_event_location function.
 * @access public
 * @param mixed $post (default: null)
 * @return void
 */
function get_event_tags($post = null) {
	global $post;
	$tags = wp_get_post_terms($post->ID, 'event_listing_tag');
	
	if(!apply_filters('enable_event_tag_archives', get_option('event_manager_enable_tag_archive')))
		$tags =  $tags ;
		
	return apply_filters('display_event_tags', $tags, $post);
}

/**
 * display_event_location function.
 * @param  boolean $map_link whether or not to link to the map on google maps
 * @return [type]
 */
function display_event_tags($post = null, $after = '') {
	global $post;

	if($event_tags = get_event_tags($post)){
		$numTag = count($event_tags);
	    $i = 0;

		foreach ($event_tags as $tag){
			echo '<a href="'.get_term_link($tag->term_id).'"><span class="wpem-event-tag-text event-tag '. esc_attr(sanitize_title($tag->slug)).' ">'. $tag->name.'</span></a>';
			if($numTag > ++$i){
			    echo $after;
			}
		}
	}
}
/**
 * show tags at single event page sidebar
 */
function show_event_tags_sidebar(){
	if(get_event_tags()){
		echo '<div class="clearfix">&nbsp;</div>';
		echo '<h3 class="wpem-heading-text">'. __('Event Tags', 'wp-event-manager-event-tags').'</h3>';
		echo '<div class="wpem-event-tags">';
			display_event_tags();
		echo '</div>';
	}
}
add_action('single_event_sidebar_end', 'show_event_tags_sidebar');