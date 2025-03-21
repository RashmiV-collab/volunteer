<?php

if ( !function_exists( 'wpsect_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wpsect_fs()
    {
        global  $wpsect_fs ;
        if ( !isset( $wpsect_fs ) ) {
            $wpsect_fs = fs_dynamic_init( array(
                'id'             => '6996',
                'slug'           => 'bulk-edit-custom-tables',
                'type'           => 'plugin',
                'public_key'     => 'pk_321712facbe5e8ab5de9df1cb2388',
                'is_premium'     => true,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'       => 'wpsect_welcome_page',
                'first-path' => 'admin.php?page=wpsect_welcome_page',
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        return $wpsect_fs;
    }
    
    // Init Freemius.
    wpsect_fs();
    // Signal that SDK was initiated.
    do_action( 'wpsect_fs_loaded' );
}
