<?php
/**
 * WPEM_Event_Tags_Category class.
 */
class WPEM_Event_Tags_Category {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_filter( 'archive_template', array( $this, 'event_archive' ), 20 );
	}

	/**
	 * register_post_types function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_taxonomy() {
		if ( taxonomy_exists( "event_listing_tag" ) ) {
			return;
		}

		$singular         = __( 'Event Tag', 'wp-event-manager-event-tags' );
		$plural           = __( 'Event Tags', 'wp-event-manager-event-tags' );
		$admin_capability = 'manage_event_listings';

		register_taxonomy( "event_listing_tag",
	        array( "event_listing" ),
	        array(
	            'hierarchical' 			=> false,
	            'update_count_callback' => '_update_post_term_count',
	            'label' 				=> $plural,
	            'labels' => array(
                    'name' 				=> $plural,
                    'singular_name' 	=> $singular,
                    'search_items' 		=> sprintf( __( 'Search %s', 'wp-event-manager-event-tags' ), $plural ),
                    'all_items' 		=> sprintf( __( 'All %s', 'wp-event-manager-event-tags' ), $plural ),
                    'parent_item' 		=> sprintf( __( 'Parent %s', 'wp-event-manager-event-tags' ), $singular ),
                    'parent_item_colon' => sprintf( __( 'Parent %s:', 'wp-event-manager-event-tags' ), $singular ),
                    'edit_item' 		=> sprintf( __( 'Edit %s', 'wp-event-manager-event-tags' ), $singular ),
                    'update_item' 		=> sprintf( __( 'Update %s', 'wp-event-manager-event-tags' ), $singular ),
                    'add_new_item' 		=> sprintf( __( 'Add New %s', 'wp-event-manager-event-tags' ), $singular ),
                    'new_item_name' 	=> sprintf( __( 'New %s Name', 'wp-event-manager-event-tags' ),  $singular )
            	),
	            'show_ui' 				=> true,
	        	'show_in_rest' 			=> true,
	            'query_var' 			=> apply_filters( 'enable_event_tag_archives', get_option( 'event_manager_enable_tag_archive' ) ),
	            'capabilities'			=> array(
	            	'manage_terms' 		=> $admin_capability,
	            	'edit_terms' 		=> $admin_capability,
	            	'delete_terms' 		=> $admin_capability,
	            	'assign_terms' 		=> $admin_capability,
	            ),
	            'rewrite' 				=> array( 'slug' => _x( 'event-tag', 'permalink', 'wp-event-manager-event-tags' ), 'with_front' => false ),
	        )
	    );
	}

	/**
	 * event_archive function.
	 *
	 * @access public
	 * @return void
	 */
	public function event_archive($template) {
		if ( is_tax( 'event_listing_tag' ) ) {
			$template = WPEM_EVENT_TAGS_PLUGIN_DIR . '/templates/content-event_listing_tag.php';
	    }
	    return $template;
	}
}
new WPEM_Event_Tags_Category();