<?php
/*
 * This file use for setings at admin site for google maps settings.
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * WPEM_Google_Maps_Settings class.
 */

class WPEM_Google_Maps_Settings {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        add_filter('event_manager_settings', array($this, 'google_maps_settings'));
        add_action( 'wp_event_manager_admin_field_link', array( $this, 'event_manager_admin_field_link' ), 10, 2 );
    }

    /**
     *  google_maps_settings function.
     *
     * @access public
     * @return void
     */
    public function google_maps_settings($settings) {
        wp_enqueue_script('wp-event-manager-google-admin-google-map');

        $settings['google_maps_general_settings']     = array(
            __('Google Maps General Settings', 'wp-event-manager-google-maps'),
            array(
                array(
                    'name'       => 'event_manager_google_maps_api_language',
                    'std'        => '',
                    'label'      => __('Google API Language', 'wp-event-manager-google-maps'),
                    'desc'       => __('This feature controls the language of the autocomplete results and Google maps. Enter the language code of the langauge you would like to use. List of avaliable langauges can be found <a href="https://developers.google.com/admin-sdk/directory/v1/languages" target="_blank"> here</a>.', 'wp-event-manager-google-maps'),
                    'type'       => 'text',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_maps_api_default_region',
                    'std'        => '',
                    'label'      => __('Google API Default Region', 'wp-event-manager-google-maps'),
                    'desc'       => __('This feature controls the regions of Goole API. Enter a country code; for example for United States enter US. you can find your country code <a href="https://countrycode.org/" target="_blank">here</a>.', 'wp-event-manager-google-maps'),
                    'type'       => 'text',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_maps_google_address_autocomplete_backend',
                    'std'        => '',
                    'label'      => __(' Google Address Autocomplete(For Backend Submission Form)', 'wp-event-manager-google-maps'),
                    'desc'       => __('Display suggested results by Google when typing an address in the location field of the new/edit WP Event Manager Google Maps screen.', 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_maps_google_address_autocomplete_frontend',
                    'std'        => '',
                    'label'      => __('Google Address Autocomplete(For Frontend Submission Form).', 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'desc'       => __('Display suggested results by Google when typing an address in the location field of the new/edit Google Maps form in the front end.', 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_maps_autocomplete_country_display',
                    'std'        => '',
                    'label'      => __('Autocomplete Country Restriction.', 'wp-event-manager-google-maps'),
                    'desc'       => __('Enter the country code of the country which you would like to restrict the autocomplete results to. Leave it empty to show all countries. <b>For example: us,in,uk</b> (add multiple with comma separated).', 'wp-event-manager-google-maps'),
                    'type'       => 'text',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_maps_location_marker',
                    'std'        => '',
                    'label'      => __('Location Marker', 'wp-event-manager-google-maps'),
                    'std'        => 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                    'desc'       => __('Url to the marker represents locations on the map.', 'wp-event-manager-google-maps'),
                    'type'       => 'text',
                    'attributes' => array()
                ),
                array(
                    'name'       => 'event_manager_google_maps_map_style',
                    'std'        => '',
                    'label'      => __('Map Style JSON', 'wp-event-manager-google-maps'),
                    'std'        => '',
                    'desc'       => __('Generate style json from <a href="https://mapstyle.withgoogle.com/" target="_blank">here</a>.', 'wp-event-manager-google-maps'),
                    'type'       => 'textarea',
                    'attributes' => array()
                )
            )
        );

        $settings['google_maps_search_form_settings'] = array(
            __('Google Maps Search Form Settings', 'wp-event-manager-google-maps'),
            array(
                array(
                    'name'       => 'event_manager_autocomplete',
                    'std'        => '1',
                    'label'      => __('Google Address Autocomplete', 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'desc'       => __('Display suggested results by Google when typing an address in the location field of the event listing filter.', 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'        => 'event_manager_radius',
                    'std'         => '5,10,15,25,50,100',
                    'placeholder' => '',
                    'label'       => __('Radius', 'wp-event-manager-google-maps'),
                    'desc'        => __('Enter the first value as a default radius, proceeded by multiple values, separated by comma, that would appear in the dropdown.', 'wp-event-manager-google-maps'),
                    'attributes'  => array()
                ),
                array(
                    'name'        => 'event_manager_orderby',
                    'std'         => 'distance,title,featured,event_start_date',
                    'placeholder' => '',
                    'label'       => __('Order By', 'wp-event-manager-google-maps'),
                    'desc'        => __('Enter the values you want to use in the "Sort by" dropdown select box. Enter, comma separated, in the order that you want the values to appear any of the values: distance, title, date and featured.', 'wp-event-manager-google-maps'),
                    'attributes'  => array()
                ),
                array(
                    'name'       => 'event_manager_display_maps',
                    'std'        => '1',
                    'label'      => __('Display Maps', 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'desc'       => __('Display google map on the event listing page.', 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'        => 'event_manager_maps_width',
                    'std'         => '100%',
                    'placeholder' => '',
                    'label'       => __('Maps Width', 'wp-event-manager-google-maps'),
                    'desc'        => __('Maps width in pixels or percentage (ex. 100% or 250px).', 'wp-event-manager-google-maps'),
                    'attributes'  => array()
                ),
                array(
                    'name'        => 'event_manager_maps_height',
                    'std'         => '250px',
                    'placeholder' => '',
                    'label'       => __('Maps Height', 'wp-event-manager-google-maps'),
                    'desc'        => __('Maps height in pixels or percentage (ex. 100% or 250px).', 'wp-event-manager-google-maps'),
                    'attributes'  => array()
                ),
                array(
                    'name'    => 'event_manager_maps_type',
                    'std'     => 'ROADMAP',
                    'label'   => __('Maps Type', 'wp-event-manager-google-maps'),
                    'desc'    => __('Choose the maps type.', 'wp-event-manager-google-maps'),
                    'type'    => 'select',
                    'options' => array(
                        'ROADMAP'   => __('ROADMAP', 'wp-event-manager-google-maps'),
                        'SATELLITE' => __('SATELLITE', 'wp-event-manager-google-maps'),
                        'HYBRID'    => __('HYBRID', 'wp-event-manager-google-maps'),
                        'TERRAIN'   => __('TERRAIN', 'wp-event-manager-google-maps')
                    ),
                ),
                array(
                    'name'       => 'event_manager_scroll_wheel',
                    'std'        => '1',
                    'label'      => __("Enable Maps Scroll Wheel Control?", 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'desc'       => __("Zoom maps in/out using mouse wheel?", 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),          
            ),
        );
        $settings['google_maps_single_page_options']  = array(
            __('Google Maps Single Page Settings', 'wp-event-manager-google-maps'),
            array(
                array(
                    'name'       => 'event_manager_single_maps_use',
                    'std'        => '1',
                    'label'      => __('Display Maps', 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'desc'       => __('Display maps showing the location in a single event page.', 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'        => 'event_manager_single_maps_width',
                    'std'         => '100%',
                    'placeholder' => '',
                    'label'       => __('Maps Width', 'wp-event-manager-google-maps'),
                    'desc'        => __('Maps width in pixels or percentage (ex. 100% or 250px).', 'wp-event-manager-google-maps'),
                    'attributes'  => array()
                ),
                array(
                    'name'        => 'event_manager_single_maps_height',
                    'std'         => '250px',
                    'placeholder' => '',
                    'label'       => __('Maps Height', 'wp-event-manager-google-maps'),
                    'desc'        => __('Maps height in pixels or percentage (ex. 100% or 250px).', 'wp-event-manager-google-maps'),
                    'attributes'  => array()
                ),
                array(
                    'name'    => 'event_manager_single_maps_type',
                    'std'     => 'ROADMAP',
                    'label'   => __('Maps Type', 'wp-event-manager-google-maps'),
                    'desc'    => __('Choose the maps type.', 'wp-event-manager-google-maps'),
                    'type'    => 'select',
                    'options' => array(
                        'ROADMAP'   => __('ROADMAP', 'wp-event-manager-google-maps'),
                        'SATELLITE' => __('SATELLITE', 'wp-event-manager-google-maps'),
                        'HYBRID'    => __('HYBRID', 'wp-event-manager-google-maps'),
                        'TERRAIN'   => __('TERRAIN', 'wp-event-manager-google-maps')
                    ),
                ),
                array(
                    'name'       => 'event_manager_single_maps_scroll_wheel',
                    'std'        => '1',
                    'label'      => __('Enable Maps Scroll-wheel Control?', 'wp-event-manager-google-maps'),
                    'cb_label'   => __('Yes', 'wp-event-manager-google-maps'),
                    'desc'       => __('Zoom maps in/out using mouse wheel?', 'wp-event-manager-google-maps'),
                    'type'       => 'checkbox',
                    'attributes' => array()
                ),
                array(
                    'name'     => 'event_manager_single_maps_zoom',
                    'std'      => '5',
                    'label'    => __('Zoom Level', 'wp-event-manager-google-maps'),
                    'cb_label' => '',
                    'desc'     => __('Add valid zoom level.<a href="https://developers.google.com/maps/documentation/javascript/tutorial#MapOptions" target="_blank">Click here</a> for more zoom level.', 'wp-event-manager-google-maps')
                )
            ),
        );
        if( !empty(get_option('event_manager_google_maps_api_key')) ) : 
            $settings['general_settings'][1][] = array(
                    'name'       => 'event_manager_check_google_api_key',
                    'label'      => __( 'Check Googel API', 'wp-event-manager-zoom' ),
                    'link_label' => __( 'Test API', 'wp-event-manager-zoom' ),
                    'link'       => 'javascript:void(0);',
                    'desc'       => __( 'Click to test if all the required APIs are enabled properly.', 'wp-event-manager-zoom' ),
                    'type'       => 'link',
                );
        endif;
        return $settings;
    }

    /**
     * event_manager_admin_field_link function.
     *
     * @access public
     * @param $option, $attributes
     * @return 
     * @since 1.0
     */
    public function event_manager_admin_field_link($option, $attributes) {
        if( $option['name'] == 'event_manager_check_google_api_key' ) {  ?>
            <label><a class="button-primary" id="check_google_api_key" href="<?php echo $option['link']; ?>"><?php echo $option['link_label']; ?></a></label>
        
            <?php if ( $option['desc'] ) :
                echo '<p class="description">' . $option['desc'] . '</p>';
            endif;
        }
    }
}
new WPEM_Google_Maps_Settings();