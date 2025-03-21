<?php
class WPEM_Emails_Post_Types {
	/**
	 * Post Type Flag
	 * @var string
	 */
	private $post_type = 'wpem_email_template';

	/**
	 * Constructor - get the plugin hooked in and ready
	 */
	public function __construct(){
		//add_action( 'init', array( $this, 'register_post_types' ), 20 );
	}

	/**
	 * register_post_types function.
	 * register post types Zoom Event
	 * @access public
	 * @param 
	 * @return 
	 * @since 1.0.0
	 */
	public function register_post_types() {
		if ( post_type_exists( $this->post_type ) ) {
			return;
		}

		$menu_name   = __( 'WPEM Emails', 'wp-event-manager-emails' );
		$plural   = __( 'WPEM Email templates', 'wp-event-manager-emails' );
		$singular = __( 'WPEM Email template', 'wp-event-manager-emails' );

		register_post_type( $this->post_type,
			apply_filters( "register_post_type_wpem_email_template", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $menu_name,
					'all_items'             => sprintf( __( 'All %s', 'wp-event-manager-emails' ), $plural ),
					'add_new' 				=> sprintf( __( 'Add New %s', 'wp-event-manager-emails' ), $singular ),
					'add_new_item' 			=> sprintf( __( 'Add %s', 'wp-event-manager-emails' ), $singular ),
					'edit' 					=> sprintf( __( 'Edit %s', 'wp-event-manager-emails' ), $singular ),
					'edit_item' 			=> sprintf( __( 'Edit %s', 'wp-event-manager-emails' ), $singular ),
					'new_item' 				=> sprintf( __( 'New %s', 'wp-event-manager-emails' ), $singular ),
					'view' 					=> sprintf( __( 'View %s', 'wp-event-manager-emails' ), $singular ),
					'view_item' 			=> sprintf( __( 'View %s', 'wp-event-manager-emails' ), $singular ),
					'search_items' 			=> sprintf( __( 'Search %s', 'wp-event-manager-emails' ), $plural ),
					'not_found' 			=> sprintf( __( 'No %s found', 'wp-event-manager-emails' ), $plural ),
					'not_found_in_trash' 	=> sprintf( __( 'No %s found in trash', 'wp-event-manager-emails' ), $plural ),
					'parent' 				=> sprintf( __( 'Parent %s', 'wp-event-manager-emails' ), $singular )
				),
				'description'         => __( 'This is where you can edit and view zoom.', 'wp-event-manager-emails' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'menu_icon'          => 'dashicons-email-alt2',
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'map_meta_cap'       => true,
				'supports'           => array( 'title', 'editor', ),
				'rewrite'            => array( 'slug' => $this->post_type ),
			) )
		);
	}	
}
new WPEM_Emails_Post_Types();