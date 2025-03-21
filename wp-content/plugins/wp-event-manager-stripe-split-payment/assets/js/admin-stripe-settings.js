var AdminStripeSettings = function () {
    /// <summary>Constructor function of the event AdminStripeSettings class.</summary>
    /// <returns type="Home" />

    return {

        ///<summary>
        ///Initializes the Connect With Stripe.  
        ///</summary>     
        ///<returns type="initialization settings" />   
        /// <since>1.0.0</since> 
        init: function ()
        {
            //load on init
            AdminStripeSettings.actions.testModeToggle();
            //Test mode toggle 
            jQuery('#woocommerce_wpem_stripe_stripe_testmode').on('change', AdminStripeSettings.actions.testModeToggle);
        },

        actions:
	    {
	        /// <summary>
	        /// Toggle the test mode settings     
	        /// </summary>                 
	        /// <returns type="generate name and id " />     
	        /// <since>1.0.0</since>  			
	        testModeToggle: function ()
	        {
	        	if(jQuery('#woocommerce_wpem_stripe_stripe_testmode').is(":checked")){
                    //show 
                    jQuery("#woocommerce_wpem_stripe_stripe_test_clientid").parents('tr').show();
                    jQuery("#woocommerce_wpem_stripe_stripe_test_publishablekey").parents('tr').show();
                    jQuery("#woocommerce_wpem_stripe_stripe_test_secretkey").parents('tr').show();
                    
                    //hide
                    jQuery("#woocommerce_wpem_stripe_stripe_live_clientid").parents('tr').hide();
                    jQuery("#woocommerce_wpem_stripe_stripe_live_publishablekey").parents('tr').hide();
                    jQuery("#woocommerce_wpem_stripe_stripe_live_secretkey").parents('tr').hide();

	        	}else{
                    //hide 
                    jQuery("#woocommerce_wpem_stripe_stripe_test_clientid").parents('tr').hide();
                    jQuery("#woocommerce_wpem_stripe_stripe_test_publishablekey").parents('tr').hide();
                    jQuery("#woocommerce_wpem_stripe_stripe_test_secretkey").parents('tr').hide();
	        		
                    //show
                    jQuery("#woocommerce_wpem_stripe_stripe_live_clientid").parents('tr').show();
                    jQuery("#woocommerce_wpem_stripe_stripe_live_publishablekey").parents('tr').show();
                    jQuery("#woocommerce_wpem_stripe_stripe_live_secretkey").parents('tr').show();
	        	}
	        }

	    } //end of action

    }; //enf of return



}; //end of class
AdminStripeSettings = AdminStripeSettings();

jQuery(document).ready(function ($) {
    AdminStripeSettings.init();
});
