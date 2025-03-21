<?php
/**
 * WP E-signature add-ons autoloader
 */

 if ( ! defined( 'WPINC' ) ) {
 	die();
 }

/**
 * Function to files 
 * @param  string $class_name
 * @return boolean
 */
if(!function_exists('esig_addon_includes')){

    function esig_addon_includes($path) {

        // try catch block to handle exception
        try {
            if (file_exists($path)) {
                require_once($path);
            } else {
                throw new Exception('E-signature Addon not found. Please re-install it');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo $e->getMessage();
        } finally {
            return false;
        }
        return false;
    }

}
