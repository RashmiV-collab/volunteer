<?php
/**
 * @package   	      WP E-Signature Save As PDF
 * @contributors	  Kevin Michael Gray (Approve Me), Abu Shoaib (Approve Me)
 * @wordpress-plugin
 * Plugin Name:       WP E-Signature - Save As PDF
 * URI:        https://approveme.com/wp-e-signature
 * Description:       This add-on gives you the ability to add a "Save Document" button to your signed documents which generates a downloadable PDF of your document.
 * mini-description save documents as a PDF
 * Version:           1.5.9
 * Author:            ApproveMe.com
 * Author URI:        https://approveme.com/
 * Documentation:     https://www.approveme.com/wpesign-features/e-signature-save-as-pdf-extension/
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}



define('ESIG_MPDF_PATH', dirname(__FILE__) . '/vendor');

define('ESIG_PDF_INCLUDE_PATH', dirname(__FILE__) . '/includes');

if(!defined("ESIG_PDF_ADMIN_PATH"))
        define('ESIG_PDF_ADMIN_PATH', dirname(__FILE__) . '/admin');

define('ESIG_PDF_ASSET_PATH', dirname(__FILE__) . '/admin/assets/');


require_once( dirname(__FILE__) . '/admin/includes/esig-pdf-settings.php' );
require_once( dirname(__FILE__) . '/admin/esig-pdf-admin.php' );
require_once( dirname(__FILE__) . '/admin/includes/pdf-admin-setting.php' );
require_once( dirname(__FILE__) . '/admin/esig-save-pdf.php' );
require_once(dirname(__FILE__) . '/admin/includes/optionsController.php');
require_once(dirname(__FILE__) . '/admin/includes/class-pdf-options.php');
add_action('wp_esignature_loaded', array('ESIG_PDF_Admin', 'instance'));

ESIG_PDF_OPTION::Init();
