var Recurring = function () {

    /// <summary>Constructor function of the Recurring class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="Logs" />  
    return {
  
    	///<summary>
        /// initialize recurring 
        ///</summary>     
        ///<returns type="" />   
        /// <since>1.0.0</since> 
    	init: function () {
			if(jQuery('.fieldset-recure_untill').length > 0){
				jQuery('input#recure_untill').datepicker({
						minDate   : 0,
					dateFormat  : event_manager_recurring_events.i18n_datepicker_format ,
				});
			}
			if(jQuery('.fieldset-event_recurrence').length > 0){
    			//call this function when page is loaded
    			Recurring.actions.onChangeRecurrence();
    			jQuery('#event_recurrence').on('change', Recurring.actions.onChangeRecurrence );	
    		}
        },
        actions:
	    {
			///<summary>
            /// on change recurrence 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	onChangeRecurrence : function(){
				var recurrence_type = jQuery('#event_recurrence').val();
                                
				if((jQuery('.fieldset-recure_time_period').length > 0) && (recurrence_type == 'monthly') ){
						//call this function when page is loaded
						Recurring.actions.onchangeTimePeriod();
						jQuery('input[name=recure_time_period]').on('change', Recurring.actions.onchangeTimePeriod );	
				}
				//if recuurence type is no
        		if(recurrence_type == '' || recurrence_type == 'no' || recurrence_type == 'dont_repeat' ){
        			//hide fields 
        			Recurring.actions.updateRecureEvery(true);
        			Recurring.actions.updateEventUntill(true);
        			Recurring.actions.updateRecureWeekday(true);
        			Recurring.actions.updateRecureTimePeriod(true);
        			Recurring.actions.updateRecureMonthDay(true);
        		}
				
				//if recuurence type is daily
        		if( recurrence_type == 'daily'){
        			//hide fields 
        			Recurring.actions.updateRecureEvery(false);
        			Recurring.actions.updateEventUntill(false);
        			Recurring.actions.updateRecureWeekday(true);
        			Recurring.actions.updateRecureTimePeriod(true);
        			Recurring.actions.updateRecureMonthDay(true);
					//add text after every repeat fields
					if (jQuery('.fieldset-recure_every').length > 0) {
        				jQuery('.fieldset-recure_every small').html(event_manager_recurring_events.every_day);
					}
        		}
				
				//if recuurence type is Weekly
        		if( recurrence_type == 'weekly'){
        			//hide fields 
        			Recurring.actions.updateRecureEvery(false);
        			Recurring.actions.updateEventUntill(false);
        			Recurring.actions.updateRecureWeekday(false);
        			Recurring.actions.updateRecureTimePeriod(true);
        			Recurring.actions.updateRecureMonthDay(true);
					//add text after every repeat fields
					if (jQuery('.fieldset-recure_every').length > 0) {
        				jQuery('.fieldset-recure_every small').html(event_manager_recurring_events.every_week);
					}
        		}
				
				//if recuurence type is Monthly
        		if( recurrence_type == 'monthly'){
        			//hide fields 
        			Recurring.actions.updateRecureEvery(false);
        			Recurring.actions.updateEventUntill(false);
        			Recurring.actions.updateRecureWeekday(false);
        			Recurring.actions.updateRecureTimePeriod(false);
        			Recurring.actions.updateRecureMonthDay(true);
					//update time period
					Recurring.actions.onchangeTimePeriod();
					//add text after every repeat fields
					if (jQuery('.fieldset-recure_every').length > 0) {
        				jQuery('.fieldset-recure_every small').html(event_manager_recurring_events.every_month);
					}
        		}
				//if recuurence type is yearly
        		if( recurrence_type == 'yearly'){
        			//hide fields 
        			Recurring.actions.updateRecureEvery(false);
        			Recurring.actions.updateEventUntill(false);
        			Recurring.actions.updateRecureWeekday(true);
        			Recurring.actions.updateRecureTimePeriod(true);
        			Recurring.actions.updateRecureMonthDay(true);
					//add text after every repeat fields
					if (jQuery('.fieldset-recure_every').length > 0) {
        				jQuery('.fieldset-recure_every small').html(event_manager_recurring_events.every_year);
					}
        		}
			},
			
			///<summary>
            /// updateRecureEvery 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	updateRecureEvery : function(hide = true){
				if( hide == true ){
        			if (jQuery('.fieldset-recure_every').length > 0) {
        				jQuery('input[name=recure_every]').removeAttr('required', 'required');
        				jQuery('.fieldset-recure_every').hide();
        			}
        		} else{
        			if (jQuery('.fieldset-recure_every').length > 0) {
        				jQuery('input[name=recure_every]').attr('required', 'required');
        				jQuery('.fieldset-recure_every').show();
        			}
        		}
			},
			
			///<summary>
            /// updateEventUntill 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	updateEventUntill : function(hide = true){
				if( hide == true ){
        			if (jQuery('.fieldset-recure_untill').length > 0) {
        				jQuery('input[name=recure_untill]').removeAttr('required', 'required');
        				jQuery('.fieldset-recure_untill').hide();
        			}
        		} else{
        			if (jQuery('.fieldset-recure_untill').length > 0) {
        				jQuery('input[name=recure_untill]').attr('required', 'required');
        				jQuery('.fieldset-recure_untill').show();
        			}
        		}
			},
			
			///<summary>
            /// updateRecureWeekday 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	updateRecureWeekday : function(hide = true){
				if( hide == true ){
        			if (jQuery('.fieldset-recure_weekday').length > 0) {
        				jQuery('input[name=recure_weekday]').removeAttr('required', 'required');
        				jQuery('.fieldset-recure_weekday').hide();
        			}
        		} else{
        			if (jQuery('.fieldset-recure_weekday').length > 0) {
        				jQuery('input[name=recure_weekday]').attr('required', 'required');
        				jQuery('.fieldset-recure_weekday').show();
        			}
        		}
			},
			
			///<summary>
            /// updateRecureTimePeriod 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	updateRecureTimePeriod : function( hide = true ){
				if( hide == true ){
        			if (jQuery('.fieldset-recure_time_period').length > 0) {
        				jQuery('input[name=recure_time_period]').removeAttr('required', 'required');
        				jQuery('.fieldset-recure_time_period').hide();
        			}
        		} else{
        			if (jQuery('.fieldset-recure_time_period').length > 0) {
        				jQuery('input[name=recure_time_period]').attr('required', 'required');
        				jQuery('.fieldset-recure_time_period').show();
        			}
					
        		}
			},
			
			///<summary>
            /// updateRecureMonthDay 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	updateRecureMonthDay : function(hide = true){
				if( hide == true ){
        			if (jQuery('.fieldset-recure_month_day').length > 0) {
        				jQuery('input[name=recure_month_day]').removeAttr('required', 'required');
        				jQuery('.fieldset-recure_month_day').hide();
        			}
        		} else{
        			if (jQuery('.fieldset-recure_month_day').length > 0) {
        				jQuery('input[name=recure_month_day]').attr('required', 'required');
        				jQuery('.fieldset-recure_month_day').show();
        			}
        		}
			},
			
			///<summary>
            /// onchangeTimePeriod 
            ///</summary>     
            ///<returns type="" />   
            /// <since>1.0.0</since> 
        	onchangeTimePeriod : function(hide = true){
				//check if time period is specific time
				if(jQuery('input[name=recure_time_period]:checked').val() == 'specific_time'){
					Recurring.actions.updateRecureMonthDay(false);
					Recurring.actions.updateRecureWeekday(false);
				} else{
					Recurring.actions.updateRecureMonthDay(true);
					Recurring.actions.updateRecureWeekday(true);
				}
			}
		} //end of action
    }
};
Recurring = Recurring();
jQuery(document).ready(function($) 
{
	Recurring.init();
});
