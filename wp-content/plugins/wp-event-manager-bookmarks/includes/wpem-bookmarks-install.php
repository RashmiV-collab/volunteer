<?php
/**
 * WPEM_Bookmarks_Install class.
 */
class WPEM_Bookmarks_Install {

	/**
     * install function.
     *
     * @access public static
     * @param 
     * @return 
     * @since 1.2.2
     */
	public static function install(){
          global $wpdb;

          // Redirect to setup screen for new installs
          if ( ! get_option( 'wpem_bookmarks_version' ) ) {
               set_transient( '_event_bookmarks_activation_redirect', 1, HOUR_IN_SECONDS );
          }

          $wpdb->hide_errors();

          $collate = '';
          if ( $wpdb->has_cap( 'collation' ) ) {
               if( ! empty( $wpdb->charset ) ) {
                    $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
               }

               if( ! empty( $wpdb->collate ) ) {
                    $collate .= " COLLATE $wpdb->collate";
               }
          }

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

          $sql = "
               CREATE TABLE {$wpdb->prefix}event_manager_bookmarks (
                    id bigint(20) NOT NULL auto_increment,
                    user_id bigint(20) NOT NULL,
                    post_id bigint(20) NOT NULL,
                    bookmark_note longtext NULL,
                    date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    PRIMARY KEY  (id)
               ) $collate;
               ";
          dbDelta( $sql );
          
		update_option( 'wpem_bookmarks_version', WPEM_BOOKMARKS_VERSION );
	}

	/**
     * update function.
     *
     * @access public static
     * @param 
     * @return 
     * @since 1.2.2
     */
	public static function update(){
		update_option( 'wpem_bookmarks_version', WPEM_BOOKMARKS_VERSION );	
	}	
}