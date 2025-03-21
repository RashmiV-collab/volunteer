<?php
/**
 * WPEM_Alerts_Post_Types class.
 */
class WPEM_Alerts_Post_Types {

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('init', array($this, 'register_post_types'), 20);
	}

	/**
	 * register_post_types function.
	 * 
	 * @return void
	 * @since 1.0.0
	 */
	public function register_post_types() {
		if(post_type_exists("event_alert")) {
			return;
		}

		register_post_type("event_alert",
			apply_filters("register_post_type_event_alert", array(
				'public'              => false,
				'show_ui'             => false,
				'capability_type'     => 'post',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'show_in_nav_menus'   => false
			))
		);

		if(taxonomy_exists('event_listing_category')) {
			register_taxonomy_for_object_type('event_listing_category', 'event_alert');
		}

		register_taxonomy_for_object_type('event_listing_type', 'event_alert');
	}
}