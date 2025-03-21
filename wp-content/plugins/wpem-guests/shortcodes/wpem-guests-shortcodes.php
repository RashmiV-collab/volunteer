<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * WPEM_Guests_Shortcodes class.
 */

class WPEM_Guests_Shortcodes {

	private $guest_lists_dashboard_message = '';
	private $group_dashboard_message = '';

	public function __construct() {

		add_shortcode( 'guests_groups', array( $this, 'output_guest_lists_groups' ) );

		add_shortcode( 'guest_lists_guests', array( $this, 'output_guest_lists_guests' ) );
	}	

	/**
	 * output_guest_lists_groups function.
	 * @access public
	 * @param 
	 * @return 
	 * @since 1.0.0
	 */
	public function output_guest_lists_groups( $atts = [] ) 
	{
		global $wpdb;

		ob_start();

		if ( ! is_user_logged_in() ) {			

			get_event_manager_template( 'event-dashboard-login.php' );

			return ob_get_clean();
		}
		
		extract( shortcode_atts( array(

			'event_id' => '',
			'posts_per_page' => '10',

		), $atts ) );

		$user_id = get_current_user_id();

		$args = [
			'post_type'   => 'event_listing',
		    'post_status' => 'publish',
		    'posts_per_page'    => -1,
		    'author' => $user_id,
		];

		$events = get_posts($args);

		/*$groups = get_event_guests_group('', $user_id, $event_id);*/
		if(get_event_guests_group('', $user_id, $event_id)){
				$total_groups = count(get_event_guests_group('', $user_id, $event_id));
		}else{
			$total_groups = 0;
		}
		
		$number_groups = 10;
		$paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
		$offset = ( $paged * $number_groups ) - $number_groups;
		$groups = get_event_guests_group('', $user_id, $event_id, $offset,$number_groups);

		$group_dashboard_columns = apply_filters( 'event_manager_group_dashboard_columns', array(
			'group_name' 		=> __( 'Name', 'wp-event-manager-guests' ),
			'group_description' => __( 'Description', 'wp-event-manager-guests' ),
			'group_fields' 		=> __( 'Fields', 'wp-event-manager-guests' ),
			'event_id' 			=> __( 'Event', 'wp-event-manager-guests' ),
			'guest_count' 		=> __( 'Guest', 'wp-event-manager-guests' ),
			'group_action' 		=> __( 'Action', 'wp-event-manager-guests' ),
		) );

		//wp_enqueue_style( 'wpem-guest-lists-frontend' );
		wp_enqueue_script( 'wpem-guest-lists-dashboard' );

		get_event_manager_template( 
			'group-dashboard.php', 
			array( 
				'groups' => $groups,
				'events' => $events,
				'user_id' => $user_id,
				'event_id' => $event_id,
				'total_groups' => $total_groups,
				'number_groups' => $number_groups,
				'paged' 	=> $paged,
				'group_dashboard_columns' => $group_dashboard_columns,
				'group_dashboard_message' => $this->group_dashboard_message,
			), 
			'wpem-guests', 
			WPEM_GUESTS_PLUGIN_DIR . '/templates/'
		);

		return ob_get_clean();
	}

	/**
	 * output_guest_lists_guests function.
	 * @access public
	 * @param 
	 * @return 
	 * @since 1.0.0
	 */
	public function output_guest_lists_guests( $atts = [] ) 
	{
		global $wpdb;

		ob_start();

		if ( ! is_user_logged_in() ) {

			get_event_manager_template( 'event-dashboard-login.php' );

			return ob_get_clean();
		}
		
		extract( shortcode_atts( array(

			'event_id' => '',
			'group_id' => '',
			'posts_per_page' => '10',

		), $atts ) );

		$user_id = get_current_user_id();

		$args = [
			'post_type'   => 'event_listing',
		    'post_status' => 'publish',
		    'posts_per_page'    => -1,
		    'author' => $user_id,
		];

		$events = get_posts($args);

		$groups = get_event_guests_group('', $user_id, $event_id);
		$fields = get_event_guests_form_fields();

		/*$guests = get_guests($group_id, $user_id, $event_id);*/
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 0;
		$args = [
			'post_type'      => 'event_guests',
			'post_status'    => array( 'publish' ),
			'posts_per_page' => 10,
			'paged' => $paged,
			'meta_query' 	 => [],
		];

		if( isset($user_id) && !empty($user_id) )
		{
			$args['author'] = $user_id;
		}

		if( isset($event_id) && !empty($event_id) )
		{
			$args['post_parent'] = $event_id;
		}

		if( isset($group_id) && !empty($group_id) )
		{
			$args['meta_query'][] = [
		            'key'     => '_guests_group',
		            'value'   => $group_id,
		            'compare' => '=',
		        ];
		}
		$guests = new WP_Query($args);

		//wp_enqueue_style( 'wpem-guest-lists-frontend' );
		wp_enqueue_script( 'wpem-guest-lists-dashboard' );

		get_event_manager_template( 
			'guests-dashboard.php', 
			array( 
				'user_id' 	=> $user_id,
				'group_id'	=> $group_id,
				'event_id' 	=> $event_id,
				'events'	=> $events,
				'groups'	=> $groups,
				'fields'	=> $fields,
				'guests'	=> $guests,
				'max_num_pages' => $guests->max_num_pages,
				'guest_lists_dashboard_message' => $this->guest_lists_dashboard_message,
			), 
			'wp-event-manager-guests', 
			WPEM_GUESTS_PLUGIN_DIR . '/templates/'
		);

		return ob_get_clean();
	}

}

new WPEM_Guests_Shortcodes();
