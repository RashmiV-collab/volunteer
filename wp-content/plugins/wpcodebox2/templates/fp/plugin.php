<?php
/**
 * Plugin Name:     {PLUGIN_NAME}
 * Plugin URI:      https://wpcodebox.com
 * Description:     {PLUGIN_DESCRIPTION}
 * Author:          WPCodeBox
 * Author URI:      https://wpcodebox.com
 * Text Domain:     wpcodebox
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 */

if(!defined('ABSPATH')) {
    die;
}

include_once 'WPCodeBox/Bootstrap.php';

\WPCodeBox\Bootstrap::bootstrap();

class FPCurrentSnippet {
    public static $currentSnippet  = false;
}

$wpcbPreconditions = new \WPCodeBox\Preconditions();

if(!$wpcbPreconditions->check()) {
    return;
}
$wpcbFunctionality = new \WPCodeBox\Functionality();
$autoreload = new \WPCodeBox\Autoreload();
$autoreload->outputAutoreload();

function execute()
{
    if(($_SERVER['REQUEST_URI'] === '/wp-admin/tools.php?page=wpcodebox2&safe_mode=1'
        || $_SERVER['REQUEST_URI'] === '/wp-admin/admin.php?page=wpcodebox2&safe_mode=1')) {
        return true;
    }

    if (defined('WPCB_SAFE_MODE')) {
        return false;
    }
    include_once 'snippets/inline_styles.php';
    include_once 'snippets/inline_scripts.php';

    // Snippets will go before this line, do not edit

    return true;
}

try {
    execute();
} catch (\Throwable $e) {
    $wpcbFunctionality->handleError($e);
}


// Run the functionality plugin before other plugins
$wpcb_fp_first = function ($plugins) {
    $path = str_replace(WP_PLUGIN_DIR . '/', '', __FILE__);
    if ( $key = array_search( $path, $plugins ) ) {
        unset( $plugins[ $key ] );
        array_unshift( $plugins, $path );
    }
    return $plugins;
};
add_action('pre_update_option_active_plugins', $wpcb_fp_first, 998, 1);
add_action('pre_update_option_active_sitewide_plugins', $wpcb_fp_first, 998, 1);
