var GoogleMapAdmin = function () {

    return {

	    init: function(){
            jQuery('body').on('click', '#check_google_api_key', GoogleMapAdmin.actions.checkAPIKey);
        },

	    actions:{
            /**
             * checkAPIKey function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.8.2
             */
            checkAPIKey: function (e){
                jQuery.post(event_manager_google_map_admin_google_map.ajax_url, {action: 'check_google_api_key', security: event_manager_google_map_admin_google_map.event_manager_google_map_security}).done(function (response) {
                    
                    //remove existing message and add new with response
                    jQuery('#settings-general_settings .update-message').remove();

                    jQuery.each(response, function(i, item) {
                        if ( item.code == '200' ){
                            jQuery('#settings-general_settings .form-table').after('<div class="update-message notice inline notice-alt updated-message notice-success"><p>' + item.message + '</p></div>');
                        }else{
                            jQuery('#settings-general_settings .form-table').after('<div class="update-message notice inline notice-alt notice-error"><p>' + item.message + '</p></div>');
                        }
                    });
                });
                e.preventDefault();
            },
		} /* end of action */
    }; /* enf of return */
}; /* end of class */
GoogleMapAdmin = GoogleMapAdmin();

jQuery(document).ready(function($) {
   GoogleMapAdmin.init();
});
