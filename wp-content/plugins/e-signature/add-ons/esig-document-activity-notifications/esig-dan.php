<?php

/**
 * @package   	      WP E-Signature - Document Activity Notifications
 * @contributors      Kevin Michael Gray (Approve Me), Abu Shoaib (Approve Me)
 * @wordpress-plugin
 * Plugin Name:       WP E-Signature - Document Activity Notifications
 * URI:        https://approveme.com/wp-e-signature
 * Description:       This add-on sends document activity email notifications every time your signer has viewed a document sent for signature (even if they haven't signed it).
 * mini-description  receive email notifications with document activity
 * Version:           1.5.9
 * Author:            ApproveMe.com
 * Author URI:        https://approveme.com/
 * Documentation:     https://wpe.approveme.com/article/132-document-activity-notifications
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}




//if (is_admin()) {

require_once( dirname(__FILE__) . '/admin/esig-dan-admin.php' );
add_action('wp_esignature_loaded', array('ESIG_DAN_Admin', 'get_instance'));

//}
