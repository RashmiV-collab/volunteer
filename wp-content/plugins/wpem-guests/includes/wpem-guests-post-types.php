<?php
/**
 * WPEM_Guests_Post_Types class.
 */
class WPEM_Guests_Post_Types {


	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  1.0.0
	 */
	private static $_instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  1.0.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ), 20 );
	}


	/**
	 * register_post_types function.
	 */
	public function register_post_types() {
		if ( post_type_exists( "event_guests" ) ) {
			return;
		}

		$plural   = __( 'Guests', 'wp-event-manager-guests' );
		$singular = __( 'Guest', 'wp-event-manager-guests' );

		register_post_type( "event_guests",
			apply_filters( "wpem_post_type_event_guest_list", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $plural,
					'all_items'             => sprintf( __( 'All %s', 'wp-event-manager-guests' ), $plural ),
					'add_new' 				=> __( 'Add Guest', 'wp-event-manager-guests' ),
					'add_new_item' 			=> sprintf( __( 'Add %s', 'wp-event-manager-guests' ), $singular ),
					'edit' 					=> __( 'Edit', 'wp-event-manager-guests' ),
					'edit_item' 			=> sprintf( __( 'Edit %s', 'wp-event-manager-guests' ), $singular ),
					'new_item' 				=> sprintf( __( 'New %s', 'wp-event-manager-guests' ), $singular ),
					'view' 					=> sprintf( __( 'View %s', 'wp-event-manager-guests' ), $singular ),
					'view_item' 			=> sprintf( __( 'View %s', 'wp-event-manager-guests' ), $singular ),
					'search_items' 			=> sprintf( __( 'Search %s', 'wp-event-manager-guests' ), $plural ),
					'not_found' 			=> sprintf( __( 'No %s found', 'wp-event-manager-guests' ), $plural ),
					'not_found_in_trash' 	=> sprintf( __( 'No %s found in trash', 'wp-event-manager-guests' ), $plural ),
					'parent' 				=> sprintf( __( 'Parent %s', 'wp-event-manager-guests' ), $singular )
				),
				'description'         => __( 'This is where you can edit and view guest lists.', 'wp-event-manager-guests' ),
				'menu_icon'           => 'dashicons-id',
				'public'              => false,
				'show_ui'             => true,
				//'capability_type'     => 'event_guests',
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'custom-fields', 'editor' ),
				'has_archive'         => false,
				'show_in_nav_menus'   => true
			) )
		);
	}

	/**
	 * save_guest_lists_group function.
	 *
	 * @return int
	 */
	public static function save_guest_lists_group( $data ) {
		global $wpdb;

		$defaults = array(
			'id' 				=> '',
			'user_id' 			=> '',
			'event_id' 			=> '',
			'group_name' 		=> '',
			'group_description' => '',
			'group_fields'      => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$insert = array(
			'user_id' 			=> $data['user_id'],
			'event_id' 			=> $data['event_id'],
			'group_name' 		=> $data['group_name'],
			'group_description'	=> $data['group_description'],
			'group_fields'     	=> !empty($data['group_fields']) ? json_encode($data['group_fields']) : '',
        );

		if( isset($data['id']) && !empty($data['id']) ) {
			$wpdb->update( $wpdb->prefix . 'wpem_guests_group', $insert, ['id' => $data['id']] );
			$group_id = $data['id'];
		} else {
			$wpdb->insert( $wpdb->prefix . 'wpem_guests_group', $insert );
			$group_id = $wpdb->insert_id;
		}

        return $group_id;
	}

}
