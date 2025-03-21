<?php
/*
* This file is use to create a shortcode of gam event manager sell tickets plugin. 
* This file include shortcode to show all tickets per event.
*/
// Exit if accessed directly
if(!defined('ABSPATH')) 
     exit; 

/**
 * WP_Event_Manager_Attendee_Information_Shortcodes class used to define attendee information plugin shortcodes.
 */
class WP_Event_Manager_Attendee_Information_Shortcodes {
	/**
	 * Constructor
	 */
	 public function __construct(){					
		//shortcode for event sell tickets
		add_shortcode('event_attendee', array($this, 'output_event_attendee'));
		
		//check for the setting Show attendee list on single event page
		if(get_option('event_registration_show_attendee', false)==true)
			add_action('single_event_listing_end',array($this,'add_shortcode_after_single_event_listing'));
		
		add_action('wp_ajax_nopriv_get_paginated_attendees', array($this , 'get_paginated_attendees'));
          add_action('wp_ajax_get_paginated_attendees', array($this , 'get_paginated_attendees'));
	 }
	
	/**
	 *  It is very simply a plugin that outputs a list of attendee that have registered on events on your website. 
	 *  
	 *  This will output a attendees list.
	 */
	public function output_event_attendee($atts) {
	 	wp_enqueue_style('wp-event-manager-attemdee-information-frontend');
		wp_enqueue_style('wp-event-manager-registrations-frontend');
		wp_enqueue_script('wp-event-manager-attendee-information');
		ob_start();
	
		extract(shortcode_atts(array(
			'event_id' => '',
			'posts_per_page' => apply_filters('event_registration_attendee_limit', get_option('event_registration_attendee_limit',10)),
		), $atts));
        
          if(empty($event_id))
			$event_id = get_the_ID();
		
		$event_status = get_post_status($event_id);
			
		if((($event_status=='publish' || $event_status=='expired') && false == get_option('event_manager_hide_expired_content', 1)) || ($event_status=='publish' && true == get_option('event_manager_hide_expired_content', 1))){
		    echo '<div class="single-event-attendee-container wpem-single-event-page" data-event-id="'.$event_id.'" data-per-page="'.$posts_per_page.'"></div>';
		}?>
	     <script type="text/javascript">
			get_pulic_attendee_list('');    
		</script>
		<?php	
	   return ob_get_clean();
	}
	
	/**
	 *  This will use to add shortcode on single event page.
	 */
	public function add_shortcode_after_single_event_listing() {
	    echo do_shortcode('[event_attendee]');
	}
	
	/**
	 *  This will use get pagination on attendee list.
	 */
	public function get_paginated_attendees(){
	    $event_id = $_POST['event_id'];
		$paged = $_POST['paged'];
		$posts_per_page=$_POST['post_per_page'];
		$args = apply_filters('event_manager_event_public_attendee_list_args', array(
			'post_type'           => 'event_registration',
			'post_status'         => array_diff(array_merge(array_keys(get_event_registration_statuses()), array('publish')), array('archived')),
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => $posts_per_page,
			'paged'               => $paged,
			'post_parent'         => $event_id
		));

		$registrations = new WP_Query($args);

		ob_start();

		if($registrations->found_posts > 0) {
			$columns = apply_filters('event_manager_event_registrations_columns', array(
				'name'  => __('Name', 'wp-event-manager-attendee-information'),
				'email' => __('Email', 'wp-event-manager-attendee-information'),
				'date'  => __('Date Received', 'wp-event-manager-attendee-information'),
			));

			get_event_manager_template(
				'attendee-listings.php', 
				array(
					'registrations'        => $registrations->posts, 
					'event_id'             => $event_id, 
					'max_num_pages'        => $registrations->max_num_pages, 
					'current_page'         => $paged, 
					'columns'              => $columns, 
					'registration_status'  => '', 
					'registration_orderby' => '',
					'registration_byname'  =>'' 
				), 
				'wp-event-manager-attendee-information', 
				WPEM_ATTENDEE_INFORMATION_PLUGIN_DIR . '/templates/' 
			);
		}
		
		$object=ob_get_clean();
	    wp_send_json(array('success' => true, 'html' => $object));
	    wp_die();
	}
}
new WP_Event_Manager_Attendee_Information_Shortcodes();
