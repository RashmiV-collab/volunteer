<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPEM_Event_Email_Install class.
 */
class WPEM_Event_Email_Install {

	/**
     * install function.
     *
     * @access public static
     * @param 
     * @return 
     * @since 1.0
     */
	public static function install(){
          global $wpdb;

          $email_verison = get_option( 'wpem_email_db_version');
          if ( version_compare( WPEM_EMAILS_VERSION, $email_verison, '>=' ) ) {
               $wpdb->hide_errors();
               $collate = '';
               if ( $wpdb->has_cap( 'collation' ) ) {
                    if ( ! empty($wpdb->charset ) ) {
                         $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                    }
                    if ( ! empty($wpdb->collate ) ) {
                         $collate .= " COLLATE $wpdb->collate";
                    }
               }
               require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
               // Table for storing licence keys for purchases
              $sql = "CREATE TABLE {$wpdb->prefix}wpem_email_templates( 
                    id BIGINT UNSIGNED NOT NULL auto_increment, 
                    name varchar(100), 
                    `type` varchar(100), 
                    `status_before` varchar(100),
                    `status_after` varchar(100),
                    `subject` varchar(200),
                    `body` varchar(200) NULL,
                    `to` varchar(255) NOT NULL,
                    `cc` varchar(255) NOT NULL,
                    `from` varchar(255) NOT NULL,
                    `reply_to` varchar(255) NOT NULL,
                    `specific_time` varchar(255) NOT NULL,
                    active tinyint(1) default 0,
                    
                    date_created datetime NULL default null,
                    PRIMARY KEY (id)
                    ) $collate;";
                    dbDelta( $sql );

          }
		update_option( 'wpem_email_db_version', WPEM_EMAILS_VERSION );
	}
}