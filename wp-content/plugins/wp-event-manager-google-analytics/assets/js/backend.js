var GoogleAnalytics= function () {
    /// <summary>Constructor function of the contact to the Google Analytics  class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="GoogleAnalytics" /> 
    return {
		/// <summary>
        /// Initializes the GoogleAnalytics.       
        /// </summary>                 
        /// <returns type="initialization settings" />     
        /// <since>1.0.0</since>  
        init: function() 
        {           
               Common.logInfo("GoogleAnalytics.init...");

	      	   jQuery('#setting-event_manager_google_analytics_tracking_code').on('change paste', GoogleAnalytics.actions.googleAnalyticsValidation);      	   				
	    },
	
         actions:
         {
			 /// <summary>
	         /// Google Analytics validation.    
	         ///  </summary>
	         /// <param name="parent" type="Event"></param>   
	         /// <returns type="actions" />    
	         /// <since>1.0.0</since>       
           	 googleAnalyticsValidation: function() 
             {       
                 Common.logInfo("GoogleAnalytics.actions.googleAnalyticsValidation...");
                 
				var code=jQuery('#setting-event_manager_google_analytics_tracking_code').val();
      			 //Clear old errors messages
		  		jQuery('.error-red-message').remove();		  	
		  		jQuery('#setting-event_manager_google_analytics_tracking_code input').removeClass('error-red-border');	
      		 	var expr = /<script\b[^>]*>([\s\S]*?)<\/script>/;
             	 
				if(code== null || code== '' )
				{				
					jQuery('#setting-event_manager_google_analytics_tracking_code').removeClass('error-red-border');
					jQuery('#setting-event_manager_google_analytics_tracking_code').addClass('error-red-border');
					jQuery('#setting-event_manager_google_analytics_tracking_code').focus();
					jQuery('#setting-event_manager_google_analytics_tracking_code').after('<span class="error-red-message">'+event_manager_google_analytics_backend.i18n_message_tracking_code+'</span>');					
					return false;  
				}
				else
				{
					jQuery('#setting-event_manager_google_analytics_tracking_code').removeClass('error-red-border');
				}	
				
				if(!expr.test(code) )
				{				    
					jQuery('#setting-event_manager_google_analytics_tracking_code').removeClass('error-red-border');
					jQuery('#setting-event_manager_google_analytics_tracking_code').addClass('error-red-border');
					jQuery('#setting-event_manager_google_analytics_tracking_code').focus();
					jQuery('#setting-event_manager_google_analytics_tracking_code').after('<span class="error-red-message">'+event_manager_google_analytics_backend.i18n_message_tracking_code_with_script_tag+'</span>');
					return false;  
				}
				else
				{  
					jQuery('#setting-event_manager_google_analytics_tracking_code').removeClass('error-red-border');
				}			 			 
       		  }
	    } //end of the actions       
    } //end of the return
};

GoogleAnalytics= GoogleAnalytics();
jQuery(document).ready(function($) 
{
   GoogleAnalytics.init();  

});