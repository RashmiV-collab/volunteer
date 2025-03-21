<?php
/*
 * This file use for setings at admin site for google maps settings.
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * WP_Event_Manager_WC_Paid_Listings_Settings class.
 */

class WP_Event_Manager_WC_Paid_Listings_Settings {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct(){
        add_filter('event_manager_settings', array($this, 'paid_listings_settings'));
    }

    /**
     *  paid_listings_settings function.
     *
     * @access public
     * @return void
     */
    public function paid_listings_settings($settings){
        $settings['event_manager_paid_listings_settings']     = array(
            __('Paid Listings Settings', 'wp-event-manager-wc-paid-listings'),
            array(
                array(
                    'name'      => 'event_manager_paid_listings_flow',
                    'std'       => 'before',
                    'label'     => __( 'Paid Listings Flow', 'wp-event-manager-wc-paid-listings' ),
                    'desc'      => __( 'Define when the user should choose a package for submission.', 'wp-event-manager-wc-paid-listings' ),
                    'type'      => 'select',
                    'options'   => array(
                        '' => __( 'Choose a package after entering event details', 'wp-event-manager-wc-paid-listings' ),
                        'before' => __( 'Choose a package before entering event details', 'wp-event-manager-wc-paid-listings' ),
                    )
                ),
                array(
                    'name'       => 'enable_event_category_for_event_manager_paid_listings',
                    'std'        => '',
                    'label'      => __('Enable event category selection', 'wp-event-manager-wc-paid-listings'),
                    'desc'       => __('If you enable then create listing package as per category selection.', 'wp-event-manager-wc-paid-listings'),
                    'cb_label'   => __('Yes', 'wp-event-manager-wc-paid-listings'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'enable_event_type_for_event_manager_paid_listings',
                    'std'        => '',
                    'label'      => __('Enable event type selection', 'wp-event-manager-wc-paid-listings'),
                    'cb_label'   => __('Yes', 'wp-event-manager-wc-paid-listings'),
                    'desc'       => __('If you enable then create listing package as per type selection.', 'wp-event-manager-wc-paid-listings'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
            )
        );
        return $settings;
    }
}

new WP_Event_Manager_WC_Paid_Listings_Settings();