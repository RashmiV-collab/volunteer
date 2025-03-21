<?php

/*
 * This file use for setings at admin site for google recaptcha settings.
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * WP_Event_Manager_Google_Recaptcha_Settings class.
 */
class WP_Event_Manager_Google_Recaptcha_Settings {

    /**
     * __construct function.
     * @access public
     * @return void
     */
    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

        add_filter('event_manager_settings', array($this, 'google_recaptcha_settings'));
    }

    /**
     * admin_enqueue_scripts function.
     * enqueue style and script for admin
     * @access public
     * @param 
     * @return 
     * @since 1.0.0
     */
    public function admin_enqueue_scripts() 
    {
        wp_register_script( 'wp-event-manager-google-recaptcha-admin-google-recaptcha', WPEM_GOOGLE_RECAPTCHA_PLUGIN_URL . '/assets/js/admin-google-recaptcha.min.js', array( 'jquery' ), WPEM_GOOGLE_RECAPTCHA_VERSION, true);
    }

    /**
     * google_recaptcha_settings function.
     * @access public
     * @return void
     */
    public function google_recaptcha_settings($settings)
    {
        wp_enqueue_script( 'wp-event-manager-google-recaptcha-admin-google-recaptcha' );

        $settings['google_recaptcha'] = apply_filters( 'event_manager_google_recaptcha_settings', array(
            __('Google Recaptcha', 'wp-event-manager-google-recaptcha'),
            array(
                array(
                    'name'       => 'enable_event_manager_google_recaptcha',
                    'std'        => '0',
                    'label'      => __( 'Enable Google reCAPTCHA', 'wp-event-manager-google-recaptcha' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA on Your Event Website', 'wp-event-manager-google-recaptcha' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                ),
                array(
                    'name'      => 'event_manager_google_recaptcha_type',
                    'std'       => 'v3',
                    'label'     => __( 'reCAPTCHA type', 'wp-event-manager-google-recaptcha' ),
                    'desc'      => __( 'Select reCAPTCHA type', 'wp-event-manager-google-recaptcha' ),
                    'type'      => 'radio',
                    'options'   =>  array(
                        'v3' => __( 'reCAPTCHA v3', 'wp-event-manager-google-recaptcha' ),
                        'v2' => __( 'reCAPTCHA v2', 'wp-event-manager-google-recaptcha' )
                    )
                ),

                array(
                    'name'       => 'event_manager_google_recaptcha_site_key',
                    'std'        => '',
                    'label'      => __('v2 RECAPTCHA_SITE_KEY', 'event_manager_google_recaptcha_site_key'),
                    'desc'       => __('Please enter your google v2 recaptcha site key', 'wp-event-manager-google-recaptcha'),
                    'type'       => 'text',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_recaptcha_secret_key',
                    'std'        => '',
                    'label'      => __('v2 RECAPTCHA_SECRET_KEY', 'event_manager_google_recaptcha_site_key'),
                    'desc'       => __('Please enter your google v2 recaptcha secret key', 'wp-event-manager-google-recaptcha'),
                    'type'       => 'text',
                    'attributes' => array()
                ),

                array(
                    'name'       => 'event_manager_google_recaptcha_site_key_v3',
                    'std'        => '',
                    'label'      => __('v3 RECAPTCHA_SITE_KEY', 'event_manager_google_recaptcha_site_key'),
                    'desc'       => __('Please enter your google v3 recaptcha site key', 'wp-event-manager-google-recaptcha'),
                    'type'       => 'text',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_recaptcha_secret_key_v3',
                    'std'        => '',
                    'label'      => __('v3 RECAPTCHA_SECRET_KEY', 'event_manager_google_recaptcha_site_key'),
                    'desc'       => __('Please enter your google v3 recaptcha secret key', 'wp-event-manager-google-recaptcha'),
                    'type'       => 'text',
                    'attributes' => array()
                ),

                array(
                    'name'       => 'enable_event_manager_google_recaptcha_submit_event_form',
                    'std'        => '1',
                    'label'      => __( 'Enable reCAPTCHA for Submit Event Form', 'wp-event-manager-google-recaptcha' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA for Submit Event Form.', 'wp-event-manager-google-recaptcha' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                ),

                array(
                    'name'       => 'enable_event_manager_google_recaptcha_submit_organizer_form',
                    'std'        => '1',
                    'label'      => __( 'Enable reCAPTCHA for Submit Organizer Form', 'wp-event-manager-google-recaptcha' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA for Submit Organizer Form.', 'wp-event-manager-google-recaptcha' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                ),

                array(
                    'name'       => 'enable_event_manager_google_recaptcha_submit_venue_form',
                    'std'        => '1',
                    'label'      => __( 'Enable reCAPTCHA for Submit Venue Form', 'wp-event-manager-google-recaptcha' ),
                    'cb_label'   => __( 'Disable this to remove reCAPTCHA for Submit Venue Form.', 'wp-event-manager-google-recaptcha' ),
                    'desc'       => '',
                    'type'       => 'checkbox',
                    'attributes' => array(),
                ),
            )
        ));

        return $settings;
    }

}

new WP_Event_Manager_Google_Recaptcha_Settings();
