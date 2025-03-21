<?php
/**
 * Defines a class with methods for cleaning up plugin data. To be used when
 * the plugin is deleted.
 *
 * @package Core
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Methods for cleaning up all plugin data.
 *
 * @since 2.5
 */
class WPEM_Bookmarks_Data_Clear {

	/**
	 * Custom post types to be deleted.
	 *
	 * @var $custom_post_types
	 */
	private static $custom_post_types = array();

	/** Cron jobs to be unscheduled.
	 *
	 * @var $cron_jobs
	 */
	private static $cron_jobs = array();

	/**
	 * User meta key names to be deleted.
	 *
	 * @var array $user_meta_keys
	 */
	private static $user_meta_keys = array();

	/**
	 * table to be deleted.
	 *
	 * @var array $custom_tables
	 */
	private static $custom_tables = array(
		'event_manager_bookmarks'
	);

	/**
	 * Cleanup all data.
	 *
	 * @access public
	 */
	public static function cleanup_all() {
		self::cleanup_custom_post_types();
		self::cleanup_pages();
		self::cleanup_user_meta();
		self::cleanup_cron_jobs();
		self::cleanup_tables();
	}

	/**
	 * Cleanup data for custom post types.
	 *
	 * @access private
	 */
	private static function cleanup_custom_post_types() {
		foreach ( self::$custom_post_types as $post_type ) {
			$items = get_posts(
				array(
					'post_type'   => $post_type,
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			foreach ( $items as $item ) {
				wp_delete_post( $item, true );
			}
		}
	}

	/**
	 * Cleanup data for pages.
	 *
	 * @access private
	 */
	private static function cleanup_pages() {
		$event_manager_alerts_page_id = get_option( 'event_manager_bookmarks_page_id' );
		if ( $event_manager_alerts_page_id ) {
			wp_delete_post( $event_manager_alerts_page_id, true );
		}
	}

	/**
	 * Cleanup user meta from the database.
	 *
	 * @access private
	 */
	private static function cleanup_user_meta() {
		global $wpdb;

		foreach ( self::$user_meta_keys as $meta_key ) {
			$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => $meta_key ) );
		}
	}

	/**
	 * Cleanup cron jobs. Note that this should be done on deactivation, but
	 * doing it here as well for safety.
	 *
	 * @access private
	 */
	private static function cleanup_cron_jobs() {
		foreach ( self::$cron_jobs as $job ) {
			wp_clear_scheduled_hook( $job );
		}
	}

	/**
	 * Cleanup cron jobs. Note that this should be done on deactivation, but
	 * doing it here as well for safety.
	 *
	 * @access private
	 */
	private static function cleanup_tables() {
		global $wpdb;

		foreach ( self::$custom_tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . $table );
		}
	}
}