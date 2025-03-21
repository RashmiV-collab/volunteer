<?php
/*
 * This file is use to create a sortcode of gam event manager embeddbale event widget plugin. 
 * Lets users generate and embed a widget containing your event listings on their own sites via a form added to your site with the shortcode [embeddable_event_widget_generator].
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * WPEM_Google_Maps_Shortcodes class.
 */
class WPEM_Google_Maps_Shortcodes{

    private $_post_stack = array();

    /**
     * Constructor
     */
    public function __construct() {
        add_action('single_event_listing_end', array($this, 'single_event_maps'), 10, 4);
        add_action('submit_event_form_event_fields_start', array($this, 'event_form_autocomplete_location_scripts'));
        add_action('event_manager_event_filters_start', array($this, 'frontend_event_search_form_autocomplete'));
        add_action('event_manager_event_filters_search_events_end', array($this, 'modify_event_search_form'));

        add_filter('event_manager_get_listings_args', array($this, 'wpem_event_manager_get_listings_args'), 20);
        add_filter('get_event_listings_query_args', array($this, 'wpem_get_event_listings_query_args'), 10, 2);
        add_filter('get_event_listings_result_args', array($this, 'search_loaction_query'), 10, 2);
        add_filter('get_event_listings_cache_results', '__return_false');

        add_shortcode('single_event_location_map', array($this, 'single_event_location'));
        add_shortcode('events_map', array($this, 'output_events_map'));
    }

    /**
     * Single event map shortcode
     * This function will get the event location and show the map on single event page only.
     * Show the map on single event page by height , width , map_type
     */
    public function single_event_location($atts){
        ob_start();
        extract(shortcode_atts(array(
            'event_id'  => '',
            'maps_type' => 'ROADMAP',
            'height'    => '400px',
            'width'     => '400px'
        ), $atts));

        if (!get_option('event_manager_single_maps_use') && empty($event_id))
            return ob_get_clean();

        if (empty($event_id) && is_single())
            $event_id = get_the_ID();

        if (get_post_type($event_id) != 'event_listing') {
            echo '<div class="wpem-alert wpem-alert-danger">' . __('There is no event found with the Event ID mentioned.', 'wp-event-manager-google-maps') . '</div>';
            return ob_get_clean();
        }

        if (is_event_online($event_id)) {
            echo '<div class="wpem-alert wpem-alert-warning">' . __('Event is online.', 'wp-event-manager-google-maps') . '</div>';
            return ob_get_clean();
        }

        $event_status = get_post_status($event_id);

        if ((($event_status == 'publish' || $event_status == 'expired' || $event_status == 'preview') && false == get_option('event_manager_hide_expired_content', 1)) || (true == get_option('event_manager_hide_expired_content', 1))) {
            $event_address = WP_Event_Manager_Geocode::get_location_data(get_event_location($event_id));
            if (is_wp_error($event_address)) {
                $event_address         = array();
                $event_address['lat']  = '';
                $event_address['long'] = '';
                $event_address['formatted_address'] = '';
            }

            ob_start();
            get_event_manager_template('google-map-event-tooltip.php', array('event_id' => $event_id), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');
            $message = ob_get_contents();
            ob_end_clean();

            wp_enqueue_script('wp-event-manager-google-maps-single-event');
            wp_localize_script('wp-event-manager-google-maps-single-event', 'event_manager_google_maps', array(
                'ajax_url'    => WP_Event_Manager_Ajax::get_endpoint(),
                'lat'         => $event_address['lat'],
                'lag'         => $event_address['long'],
                'address'     => $message,
                'marker'      => get_option('event_manager_google_maps_location_marker'),
                'scrollwheel' => get_option('event_manager_single_maps_scroll_wheel'),
                'zoom'        => get_option('event_manager_single_maps_zoom'),
                'map_type'    => $maps_type,
                'style_json'  => json_decode(get_option('event_manager_google_maps_map_style', ''))
            ));

            $event_content_toggle = apply_filters('event_manager_event_content_toggle', true);
            $event_content_toggle_class = $event_content_toggle ? 'wpem-listing-accordion' : 'wpem-event-google-map-title';

            get_event_manager_template('google-map.php', array('maps_width' => $width, 'maps_height' => $height,), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');
        }
        return ob_get_clean();
    }

    /**
     * Single event map
     * This function will get the event location and show the map on single event.
     */
    public function single_event_maps(){
        global $post;

        if (!get_option('event_manager_single_maps_use'))
            return;

        $event_id = $post->ID;
        if (is_event_online($event_id))
            return;

        $maps_type = get_option('event_manager_single_maps_type');
        $height = get_option('event_manager_single_maps_height');
        $width  = get_option('event_manager_single_maps_width');

        $event_content_toggle = apply_filters('event_manager_event_content_toggle', true);
        $event_content_toggle_class = $event_content_toggle ? 'wpem-listing-accordion' : 'wpem-event-google-map-title';?>

        <div class="wpem-single-event-footer">
            <div class="wpem-row">
                <div class="wpem-col-md-12">
                    <div class="wpem-google-map-wrapper">
                        <div class="<?php echo $event_content_toggle_class; ?> active">
                            <h3 class="wpem-heading-text"><?php _e('Event Location', 'wp-event-manager-google-maps'); ?></h3>
                            <i class="wpem-icon-minus"></i><i class="wpem-icon-plus"></i>
                        </div>
                        <div class="wpem-google-map-block-wrapper wpem-listing-accordion-panel active" style="display: block;">
                            <?php
                            echo do_shortcode('[single_event_location_map event_id="' . $event_id . '" maps_type="' . $maps_type . '" height="' . $height . '" width="' . $width . '" ]');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * add Google address autocomplete to new/edit event form
     * @since  1.0
     * @author Gam team
     */
    public function event_form_autocomplete_location_scripts()   {
        if (!get_option('event_manager_google_maps_google_address_autocomplete_frontend') && empty($event_id))
            return;
        $country              = array('country' => get_option('event_manager_google_maps_autocomplete_country_restriction'));
        $autocomplete_options = array(
            'input_address'  => 'event_address',
            'input_pincode'  => 'event_pincode',
            'input_location' => 'event_location',
            'form_type'      => 'event_listings',
            'options'        => $country
        );
        wp_enqueue_script('wp-event-manager-google-maps-autocomplete');
        wp_localize_script('wp-event-manager-google-maps-autocomplete', 'AutoCompOptionsLocation', $autocomplete_options);
    }

    /**
     * add Google address autocomplete to serching  event form
     * @since  1.0
     * @author Gam team
     */
    public function frontend_event_search_form_autocomplete()  {
        if (!get_option('event_manager_autocomplete'))
            return;

        $country              = array('country' => get_option('event_manager_google_maps_autocomplete_country_restriction'));
        $restrict_country     = get_option('event_manager_google_maps_autocomplete_country_restriction', '');
        $autocomplete_options = array(
            'input_field' => 'search_location',
            'form_type'   => 'event_listings',
            'country'     => $restrict_country
        );
        wp_enqueue_script('wp-event-manager-google-maps-search-location-autocomplete');
        wp_localize_script('wp-event-manager-google-maps-search-location-autocomplete', 'AutoCompOptions', $autocomplete_options);
    }

    /**
     * activate function.
     * We replace orderby value because we don't have filter in WP Event Manager plugin
     * @access public
     * @param 
     * @return array
     * @since 1.8.2
     */
    public function wpem_event_manager_get_listings_args($args) {
        if (empty($_REQUEST['form_data']))
            return $args;

        parse_str($_REQUEST['form_data'], $form_data);

        $order_by = isset($form_data['search_orderby']) ? $form_data['search_orderby'] : '';
        if (isset($order_by[0]) && !empty($order_by[0])) {
            $args['orderby'] = $order_by[0];
        }

        return $args;
    }

    /**
     * activate function.
     * We apply filter because of extend get listing query
     * We apply groupby filter because in query "GROUP BY" conflict with "HAVING distance"
     * @access public
     * @param 
     * @return array
     * @since 1.8.2
     */
    public function wpem_get_event_listings_query_args($query_args, $args) {

        $params = array();

        if (isset($_REQUEST['form_data']) && !empty($_REQUEST['form_data'])) {
            parse_str($_REQUEST['form_data'], $params);
        }
        if (isset($params['search_within_radius'][0]) && !empty($params['search_within_radius'][0])) {
            //remove extra meta key searching feature of core plugin
            if (isset($query_args['meta_query'][0]))
                unset($query_args['meta_query'][0]);

            add_filter('posts_groupby', function ($groupby) {
                return '';
            });
            add_filter('posts_clauses', [$this, 'wpem_posts_clauses'], 10, 2);
        }

        if (isset($args['search_location']) && !empty($params['search_location']) && !empty($args['search_location'])) {
            $query_args['meta_query'][] = array(
                'key'     => '_event_online',
                'value'   => 'no',
                'compare' => 'LIKE',
            );
        }
        return $query_args;
    }

    /**
     * activate function.
     * Extend get listing query
     * @access public
     * @param 
     * @return array
     * @since 1.8.2
     */
    public function wpem_posts_clauses($clauses, $wp_query) {
        global $wpdb;

        if (empty($_REQUEST['form_data']))
            return $clauses;

        parse_str($_REQUEST['form_data'], $form_data);

        $address        = $form_data['search_location'];
        $distance       = isset($form_data['search_within_radius'][0]) ? $form_data['search_within_radius'][0] : '';
        $unit           = isset($form_data['search_distance_units'][0]) ? $form_data['search_distance_units'][0] : '';

        if (!empty($address)) {
            $lat_lng   = google_maps_geocoder($address);
            $latitude  = isset($lat_lng['lat']) ? $lat_lng['lat'] : '';
            $longitude = isset($lat_lng['lng']) ? $lat_lng['lng'] : '';
        } else if (!empty($form_data['google_map_lat']) && !empty($form_data['google_map_lng'])) {
            $latitude  = $form_data['google_map_lat'];
            $longitude = $form_data['google_map_lng'];
        } else {
            $latitude  = NULL;
            $longitude = NULL;
        }

        if ($latitude != NULL && $longitude != NULL) {
            // Radius of the earth 3959 miles or 6371 kilometers.
            $earth_radius = $unit == 'km' ? 6371 : 3959;

            if (isset($wp_query->query['tax_query'])) {
                $tax_sql = get_tax_sql($wp_query->query['tax_query'], $wpdb->posts, 'ID');
                if (isset($tax_sql['where']))
                    $tax_where = $tax_sql['where'];
            } else {
                $tax_where = $clauses['where'];
            }

            $where = $clauses['where'] . " AND geolocation_lat.meta_key = 'geolocation_lat' AND geolocation_long.meta_key = 'geolocation_long' GROUP BY $wpdb->posts.ID HAVING distance < $distance ";

            $join = $clauses['join'] . " INNER JOIN $wpdb->postmeta geolocation_lat ON ( $wpdb->posts.ID = geolocation_lat.post_id ) INNER JOIN $wpdb->postmeta geolocation_long ON ( $wpdb->posts.ID = geolocation_long.post_id ) ";

            $fields = " $wpdb->posts.*, 
                        geolocation_lat.meta_value as latitude, 
                        geolocation_long.meta_value as longitude, 
                        ( $earth_radius * acos(
                                cos( radians( $latitude ) )
                                * cos( radians( geolocation_lat.meta_value ) )
                                * cos( radians( geolocation_long.meta_value ) 
                                - radians( $longitude ) )
                                + sin( radians( $latitude ) )
                                * sin( radians( geolocation_lat.meta_value ) )
                        ) )
                        AS distance ";

            $order_by = isset($form_data['search_orderby']) ? $form_data['search_orderby'] : '';
            if (isset($order_by[0]) && $order_by[0] == 'distance') {
                $orderby = " distance DESC ";
            } else {
                $orderby = $clauses['orderby'];
            }
            $clauses['where'] = $where;
            $clauses['join'] = $join;
            $clauses['orderby'] = $orderby;
            $clauses['fields'] = $fields;
        }
        return $clauses;
    }

    /**
     * Functin will filter the result on map and also filter the event listing 
     * It will filter the map and event linsting by within radius ,Kilometer / Miles , Order by 
     * It shows the map before the event listing
     * Manipulate result of get listing if location passed in the event filter
     * Here we are merging two result 
     * 1. Geolocated query result 
     * 2 Get losting query result
     * Using get listing we can filter events by geolocatio so we have separated geolocation sql query and merged result 
     * @parma $query_args
     * @return $result
     * @since  1.0
     */
    public function search_loaction_query($result, $query_args) {
        /**
         * Remove extend get listing query filter
         * Remove groupby filter
         * @since 1.8.2
         */
        remove_filter('posts_clauses', [$this, 'wpem_posts_clauses'], 10, 2);
        remove_filter('posts_groupby', function ($groupby) {
            return $groupby;
        });

        global $wpdb, $post;

        if (empty($_REQUEST['form_data']))
            return $result;

        parse_str($_REQUEST['form_data'], $form_data);
        $address        = $form_data['search_location'];

        $distance       = isset($form_data['search_within_radius'][0]) ? $form_data['search_within_radius'][0] : '';
        $unit           = isset($form_data['search_distance_units'][0]) ? $form_data['search_distance_units'][0] : '';

        $latitude  = isset($form_data['google_map_lat']) ? $form_data['google_map_lat'] : '';
        $longitude = isset($form_data['google_map_lng']) ? $form_data['google_map_lng'] : '';

        $include_ids    = array();
        $all_locations  = [];
        $temp_locations = array();

        if (!empty($address)) {
            $lat_lng   = google_maps_geocoder($address);
            $latitude  = isset($lat_lng['lat']) ? $lat_lng['lat'] : '';
            $longitude = isset($lat_lng['lng']) ? $lat_lng['lng'] : '';
        } 
        if ($result->have_posts()) :
            while ($result->have_posts()) {
                $result->the_post();

                $event_id = get_the_ID();
                if (!is_event_online($event_id)) {

                    ob_start();
                    get_event_manager_template('google-map-event-tooltip.php', array(), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');
                    $message = ob_get_contents();
                    ob_end_clean();

                    $event_tool_tip_template['tool_tip'] = $message;
                    $event_address = get_selected_region_location_data(get_event_location($event_id));
                    if (is_array($event_address) && !empty($event_address)) {
                        $event_latitude  = $event_address['lat'];
                        $event_longitude = $event_address['long'];

                        $is_within = $this->distance($latitude, $longitude, $event_latitude, $event_longitude, $unit);
                        array_push($event_address, $event_tool_tip_template);
                        array_push($temp_locations, $event_address);
                    }
                }
            } //end while
            wp_reset_postdata();
        //else if there is no events found
        else :
            return $result;
        endif;

        $meta_key = '';
        $order_by = isset($form_data['search_orderby']) ? $form_data['search_orderby'] : '';

        if (isset($order_by[0]) && $order_by[0] == 'title') {
            $meta_key = '_event_title';
        } elseif (isset($order_by[0]) && $order_by[0] == 'date') {
            $meta_key = '_event_start_date';
        } elseif (isset($order_by[0]) && $order_by[0] == 'featured') {
            $meta_key = '_featured';
        } else {
            $meta_key = '';
        }
        
        foreach ($temp_locations as $event_locations) {
            $temp_location     = array();
            $formatted_address = $event_locations['formatted_address'];
            $lat               = $event_locations['lat'];
            $long              = $event_locations['long'];

            $event_tool_tip = $event_locations[0]['tool_tip'];
            array_push($temp_location, array('lng' => $long, 'lat' => $lat, 'address' => $formatted_address, 'tool_tip' => $event_tool_tip));
            array_push($all_locations, array('lng' => $long, 'lat' => $lat, 'address' => $formatted_address, 'tool_tip' => $event_tool_tip));
        }

        $maps_type   = get_option('event_manager_maps_type');
        $maps_height = get_option('event_manager_maps_height');
        $maps_width  = get_option('event_manager_maps_width');

        $marker      = get_option('event_manager_google_maps_location_marker');
        $style_json  = get_option('event_manager_google_maps_map_style', '');
        $scrollwheel = get_option('event_manager_scroll_wheel', true);

        $display_map = get_option('event_manager_display_maps', true);

        $current_latitude =  isset($form_data['google_map_lat']) ? $form_data['google_map_lat'] : '';
        $current_longitude =  isset($form_data['google_map_lng']) ? $form_data['google_map_lng'] : '';

        if (empty($all_locations)) {
            $all_locations[] = array('lng' => $longitude, 'lat' => $latitude, 'address' => $address);
        }

        $map_km = 0;
        if (isset($form_data['search_within_radius'][0]) && !empty($form_data['search_within_radius'][0])) {
            if (isset($form_data['search_distance_units'][0]) && $form_data['search_distance_units'][0] == 'mi') {
                $map_km = $form_data['search_within_radius'][0] * 1.60934;
            } else {
                $map_km = $form_data['search_within_radius'][0];
            }
        }

        $map_radius = $map_km * 1000;
        if ($display_map == true) {  ?>
            <script type="text/javascript">
                /**
                 * Script for loadin map at events page.
                 * This script load the map in googleMap id.
                 */
                var locations = <?php echo json_encode($all_locations); ?>;
            </script>

            <?php
            //This will modify the query only for the google map.When user clicks on load more it will show the updated result on google map.
            if (isset($_REQUEST['page']) && absint($_REQUEST['page'] >= 2)) :
                $query_args['offset']         = 0;
                $query_args['posts_per_page'] = absint($_REQUEST['per_page']) * absint($_REQUEST['page']); ?>
                <script type="text/javascript">
                    locations = locations.concat(old_locations);
                </script>
            <?php endif; ?>

            <script type="text/javascript">
                var address = '<?php echo $form_data['search_location'] ?>';
                if (address !== '') {
                    var current_latitude = '<?php echo isset($form_data['google_map_lat']) ? $form_data['google_map_lat'] : '' ?>';
                    var current_longitude = '<?php echo isset($form_data['google_map_lng']) ? $form_data['google_map_lng'] : '' ?>';
                } else {
                    var current_latitude = '';
                    var current_longitude = '';
                }
                var infowindow = new google.maps.InfoWindow();
                var bounds = new google.maps.LatLngBounds();

                // initialize map options
                var options = {
                    mapTypeId: google.maps.MapTypeId.<?php echo $maps_type; ?>,
                    styles: <?php echo !empty($style_json) ? $style_json : '[]'; ?>,
                    scrollwheel: <?php echo ($scrollwheel) ? 'true' : 'false'; ?>
                };

                // create map environment to load google map
                var map = new google.maps.Map(document.getElementById('googleMap'), options);

                //marker cluster
                var mc;
                var mcOptions = {
                    gridSize: 50,
                    maxZoom: 4,
                    imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
                };
                mc = new MarkerClusterer(map, [], mcOptions);

                // get each location and add it into google marker
                for (var i = 0; i < locations.length; i++) {
                    if (locations[i]['lat'] == null || locations[i]['lng'] == '') {
                        continue;
                    }
                    var latlng = new google.maps.LatLng(locations[i]['lat'], locations[i]['lng']);
                    var marker = createMarker(latlng, locations[i]['tool_tip']);
                    marker.setIcon('<?php echo $marker; ?>');
                    mc.addMarker(marker);
                    bounds.extend(latlng);
                }
                if (current_latitude != '' && current_longitude != '') {
                    var latlng = new google.maps.LatLng(current_latitude, current_longitude);
                    var marker = new google.maps.Marker({
                        position: latlng,
                        map: map,
                    });
                    bounds.extend(latlng);
                    google.maps.event.addListener(marker, 'click', function() {
                        infowindow.close();
                        infowindow.setContent('My Location');
                        infowindow.open(map, marker);
                    });
                    var wpem_radius = <?php echo $map_radius; ?>;
                    if (wpem_radius > 0) {
                        var circle = new google.maps.Circle({
                            map: map,
                            radius: wpem_radius, // 5000 5km in metres
                            strokeColor: "#00a5fa",
                            strokeOpacity: 0.8,
                            strokeWeight: 2,
                        });
                        circle.bindTo('center', marker, 'position');
                    }
                }

                //set map into center
                map.setCenter(bounds.getCenter());
                map.fitBounds(bounds);
                var old_locations = locations;

                // create map marker
                function createMarker(latlng, text) {
                    var marker = new google.maps.Marker({
                        position: latlng
                    });
                    ///get array of markers currently in cluster
                    var allMarkers = mc.getMarkers();

                    //check to see if any of the existing markers match the latlng of the new marker
                    if (allMarkers.length != 0) {
                        for (i = 0; i < allMarkers.length; i++) {
                            var currentMarker = allMarkers[i];
                            var pos = currentMarker.getPosition();
                            if (latlng.equals(pos)) {
                                text = text + "</br>" + locations[i]['tool_tip'];
                            }
                        }
                    }
                    google.maps.event.addListener(marker, 'click', function() {
                        infowindow.close();
                        infowindow.setContent(text);
                        infowindow.open(map, marker);
                    });
                    return marker;
                }
            </script>
        <?php
            get_event_manager_template('google-map.php', array('maps_width' => $maps_width, 'maps_height' => $maps_height,), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');
        }
        return $result;
    }

    /**
     * Modify search form add new fields in the filter
     * This will call the filter template
     */
    public function modify_event_search_form(){
        get_event_manager_template('google-maps-filters.php', array(), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');
    }

    /**
     * Function will return distance from km , ml 
     * @parma $lat, $long, $event_lat, $event_long, $unit
     * @return $miles
     */
    public function distance($lat, $long, $event_lat, $event_long, $unit){
        if (empty($lat) || empty($long)) {
            return;
        }

        $theta = $long - $event_long;
        $dist  = sin(deg2rad($lat)) * sin(deg2rad($event_lat)) + cos(deg2rad($lat)) * cos(deg2rad($event_lat)) * cos(deg2rad($theta));
        $dist  = acos($dist);
        $dist  = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit  = strtoupper($unit);
        if ($unit == "km") {
            return ($miles * 1.609344);
        } elseif ($unit == "mi") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }

    /**
     * Function will show map with all events on map.
     * @parma 
     * @return
     */
    public function output_events_map($atts){
        extract($atts = shortcode_atts(apply_filters('event_manager_output_events_defaults', array(
            'height'      => '400px',
            'width'       => '',
            'maps_type'   => 'roadmap',
            // Limit what events are shown based on category and type
            'categories'  => '',
            'event_types' => '',
            'featured'    => null, // True to show only featured, false to hide featured, leave null to show both.
            'cancelled'   => null, // True to show only cancelled, false to hide cancelled, leave null to show both/use the settings.
            // Default values for filters
            'location'    => '',
            'keywords'    => ''
        )), $atts));

        $categories = is_array($categories) ? $categories : array_filter(array_map('trim', explode(',', $categories)));
        $event_types = is_array($event_types) ? $event_types : array_filter(array_map('trim', explode(',', $event_types)));

        if (!is_null($featured)) {
            $featured = (is_bool($featured) && $featured) || in_array($featured, array('1', 'true', 'yes')) ? true : false;
        }

        if (!is_null($cancelled)) {
            $cancelled = (is_bool($cancelled) && $cancelled) || in_array($cancelled, array('1', 'true', 'yes')) ? true : false;
        }
   
        $events         = get_event_listings(apply_filters('google_map_output_events_args', array(
            'search_location'    => $location,
            'search_keywords'    => $keywords,
            'search_datetimes'   => '',
            'search_categories'  => $categories,
            'search_event_types' => $event_types,
            'posts_per_page'     => '-1',
            'featured'           =>  $featured,
            'cancelled'          =>$cancelled
        )));

        $events_address = [];
        if ($events->have_posts()) :
            while ($events->have_posts()) : $events->the_post();
                $event_id = get_the_ID();
                if (!is_event_online($event_id)) {
                    ob_start();
                    get_event_manager_template('google-map-event-tooltip.php', array(), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');
                    $message = ob_get_contents();
                    echo get_event_location();
                    ob_end_clean();
                    $event_address = WP_Event_Manager_Geocode::get_location_data(get_event_location());
                    if (is_array($event_address)) {
                        $event_address['tool_tip'] = $message;
                        $events_address[]          = $event_address;
                    }
                }
            endwhile;
        endif;

        wp_reset_postdata();

        ob_start();
        get_event_manager_template('google-map.php', array('maps_width' => $width, 'maps_height' => $height), 'wp-event-manager-google-maps', EVENT_MANAGER_GOOGLE_MAPS_PLUGIN_DIR . '/templates/');

        $marker      = get_option('event_manager_google_maps_location_marker');
        $style_json  = get_option('event_manager_google_maps_map_style', '');
        $scrollwheel = get_option('event_manager_scroll_wheel', true); ?>
        <script type="text/javascript">
            jQuery(function() {
                // create map marker
                function createMarker(latlng, text) {
                    var marker = new google.maps.Marker({
                        position: latlng
                    });
                    ///get array of markers currently in cluster
                    var allMarkers = mc.getMarkers();

                    //check to see if any of the existing markers match the latlng of the new marker
                    if (allMarkers.length != 0) {
                        for (i = 0; i < allMarkers.length; i++) {
                            var currentMarker = allMarkers[i];
                            var pos = currentMarker.getPosition();

                            if (latlng.equals(pos)) {
                                text = text + "</br>" + locations[i]['tool_tip'];
                            }
                        }
                    }
                    google.maps.event.addListener(marker, 'click', function() {
                        infowindow.close();
                        infowindow.setContent(text);
                        infowindow.open(map, marker);
                    });
                    return marker;
                }

                /**
                 * Script for loadin map at events page.
                 * This script load the map in googleMap id.
                 */
                var locations = <?php echo json_encode($events_address); ?>;
                var options = {
                    mapTypeId: google.maps.MapTypeId.<?php echo $maps_type; ?>,
                    styles: <?php echo !empty($style_json) ? $style_json : '[]'; ?>

                }
                window.map = new google.maps.Map(document.getElementById('googleMap'), options);

                var infowindow = new google.maps.InfoWindow();
                var bounds = new google.maps.LatLngBounds();

                //marker cluster
                var mc;
                var mcOptions = {
                    gridSize: 50,
                    maxZoom: 4,
                    imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
                };
                mc = new MarkerClusterer(map, [], mcOptions);

                // get each location and add it into google marker
                for (var i = 0; i < locations.length; i++) {
                    var latlng = new google.maps.LatLng(locations[i]['lat'], locations[i]['long']);
                    var marker = createMarker(latlng, locations[i]['tool_tip']);
                    marker.setIcon('<?php echo $marker; ?>');
                    mc.addMarker(marker);
                    bounds.extend(latlng);
                }

                //now fit the map to the newly inclusive bounds
                map.setCenter(bounds.getCenter());
                map.fitBounds(bounds);

                //(optional) restore the zoom level after the map is done scaling
                var listener = google.maps.event.addListener(map, "idle", function() {
                    google.maps.event.removeListener(listener);
                });
            });
        </script>
    <?php return ob_get_clean();
    }
}
new WPEM_Google_Maps_Shortcodes();