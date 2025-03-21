var ConnectWithStripe = function () {
    /// <summary>Constructor function of the event ConnectWithStripe class.</summary>
    /// <returns type="Home" />

    return {

        ///<summary>
        ///Initializes the Connect With Stripe.  
        ///</summary>     
        ///<returns type="initialization settings" />   
        /// <since>1.0.0</since> 
        init: function ()
        {
            //disconnect stripe account 
            jQuery('#disconnect_stripe').on('click', ConnectWithStripe.actions.disConnectStripe);
        },

        actions:
	    {
	        /// <summary>
	        /// Disconnect Seller's Stripe account	       
	        /// </summary>                 
	        /// <returns type="generate name and id " />     
	        /// <since>1.0.0</since>  			
	        disConnectStripe: function (event)
	        {	           
	            jQuery.ajax({
	                type: 'POST',
	                url: wc_connect_with_stripe.ajaxUrl,
	                data: { action: 'disconnect_stripe_account' },
	                beforeSend: function (jqXHR, settings)
	                {
	                    jQuery('#disconnect_stripe_message').remove();
	                    jQuery('.wpem-row.wpem-stripe-shortcode-wrapper').append('<div class="wpem-col-12" id="disconnect_stripe_message"><div class="wpem-alert wpem-alert-warning wpem-mt-4">' + wc_connect_with_stripe.i18n_processing_message + '</div></div>');
	                },
	                success: function (result)
	                {	                    
	                    jQuery('#disconnect_stripe_message').remove();

	                    //remove url part after ?
	                    //https://www.yoursite.com/?state=1&scope=read_write&code=ac_9FsS20rWWwAneWiaVBHr0Uu4Wi9yxdRg
	                    var url = window.location.href;
	                    //url = url.split('?')[0];
	                    window.location.href = url;
	                    //window.location.reload();
	                },
	                error: function (jqXHR, textStatus, errorThrown)
	                {	                    
	                    jQuery('.wpem-row.wpem-stripe-shortcode-wrapper').append('<div class="wpem-col-12" id="disconnect_stripe_message"><div class="wpem-alert wpem-alert-danger wpem-mt-4">' + wc_connect_with_stripe.i18n_error_message + '</div></div>');
	                },
	                complete: function (jqXHR, textStatus) {
	                }
	            });

	            event.preventDefault();
	        }

	    } //end of action

    }; //enf of return



}; //end of class
ConnectWithStripe = ConnectWithStripe();

jQuery(document).ready(function ($) {
    ConnectWithStripe.init();
});