 
var WPEMGoogleMaps = function () {
    return {
        init: function(){
            if(jQuery('.event_filters .wpem-location-container').length > 0){
                jQuery('.event_filters .wpem-location-container .wpem-my-location').remove();
                jQuery('.event_filters .wpem-location-container').append(wpem_google_maps.i18n_current_location_button_html);
            }
            jQuery('body').on('click', '.event_filters .wpem-location-container .wpem-my-location',WPEMGoogleMaps.actions.updateCurrentUserLocation);
             
             /**
             * JS for filter
             */
            jQuery('#search_within_radius , #search_orderby , #search_distance_units').change(function (){
                var target = jQuery(this).closest('div.event_listings');
                target.triggerHandler('update_event_listings', [1, false]);
                EventAjaxFilters.event_manager_store_state(target, 1)
            }).on("keyup", function (e){
                if (e.which === 13){
                    jQuery(this).trigger('change')
                }
            });

            //on event listing reset
            jQuery('.event_listings').on('reset', WPEMGoogleMaps.actions.resetGoogleMapFilters);
            //Set default distance
            jQuery('.wpem-my-location').on('click',function(){
                jQuery('#search_within_radius').prop('selectedIndex',1);
            });
        },
        actions: {
            /**
             * updateCurrentUserLocation function.
             *
             * @access public
             * @param event
             * @return NULL
             * @since 1.8.3
             */
            updateCurrentUserLocation: function (e) {
                var options = {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                };
                navigator.geolocation.getCurrentPosition(
                    function(position){
                        var coords = position.coords;
                        jQuery('#google_map_lat').val(coords.latitude);
                        jQuery('#google_map_lng').val(coords.longitude);

                        jQuery.ajax({
                            url: wpem_google_maps.ajax_url.toString().replace("%%endpoint%%", "get_formatted_address_from_cordinates"),
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                security: wpem_google_maps.security,
                                coords: coords,
                            },
                            success: function (response)
                            {
                                if(response.formatted_address.length > 0)
                                jQuery('#search_location').val(response.formatted_address);
                            jQuery( "#search_location" ).trigger( "change" );
                            }
                        }); 
                    },
                    function(){
                        console.warn('ERROR(${err.code}): ${err.message}');
                    }, options);
            },

            /**
             * resetGoogleMapFilters function.
             *
             * @access public
             * @param event
             * @return NULL
             * @since 1.8.3
             */
            resetGoogleMapFilters: function (e) {
               jQuery('#search_within_radius').prop('selectedIndex',0);
               jQuery('#search_distance_units').prop('selectedIndex',0);
               jQuery('#search_orderby').prop('selectedIndex',0);
            }
        } /* end of action */
    }; /* enf of return */
}; /* end of class */
WPEMGoogleMaps = WPEMGoogleMaps();

jQuery(document).ready(function($) {
   WPEMGoogleMaps.init();
});
