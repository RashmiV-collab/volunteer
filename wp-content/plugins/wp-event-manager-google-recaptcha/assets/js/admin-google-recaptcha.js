var GoogleRecaptchaAdmin = function () {

    return {

	    init: function() 
        {
            jQuery( 'input[name="event_manager_google_recaptcha_type"]' ).on('change', GoogleRecaptchaAdmin.actions.googleRecaptchaOptions );
            jQuery( 'input[name="event_manager_google_recaptcha_type"][checked="checked"]' ).trigger( "change" );
        },

	    actions:
	    {
            /**
             * google recaptcha Options
             * @param event
             */
            googleRecaptchaOptions: function (event) 
            {
                var google_recaptcha_type = jQuery(event.target).val();

                if(google_recaptcha_type == 'v3')
                {
                    jQuery('input[name="event_manager_google_recaptcha_site_key"]').closest('tr').hide();
                    jQuery('input[name="event_manager_google_recaptcha_secret_key"]').closest('tr').hide();

                    jQuery('input[name="event_manager_google_recaptcha_site_key_v3"]').closest('tr').show();
                    jQuery('input[name="event_manager_google_recaptcha_secret_key_v3"]').closest('tr').show();
                }
                else
                {
                    jQuery('input[name="event_manager_google_recaptcha_site_key_v3"]').closest('tr').hide();
                    jQuery('input[name="event_manager_google_recaptcha_secret_key_v3"]').closest('tr').hide();

                    jQuery('input[name="event_manager_google_recaptcha_site_key"]').closest('tr').show();
                    jQuery('input[name="event_manager_google_recaptcha_secret_key"]').closest('tr').show();
                }
            },
		
		} //end of action

    }; //enf of return
    
}; //end of class

GoogleRecaptchaAdmin = GoogleRecaptchaAdmin();

jQuery(document).ready(function($) 
{
   GoogleRecaptchaAdmin.init();
});
