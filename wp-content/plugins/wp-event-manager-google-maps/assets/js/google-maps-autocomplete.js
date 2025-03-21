
var GoogleMapAutocomplete = function () {
    /// <summary>Constructor function of the event GoogleMapAutocomplete class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="GoogleMapAutocomplete" />  
    
  	return {
		/// <summary>
        /// Initializes the GoogleMaps autocomplete.       
        /// </summary>                 
        /// <returns type="initialization settings" />     
        /// <since>1.0.0</since>  
        init: function(){
			jQuery(document).ready(GoogleMapAutocomplete.actions.autocomplete);
		},
		actions:{
			/// <summary>
	        /// autocomplete Location Search on events page.	     
	        /// </summary>
	        /// <param name="parent" type="assign"></param>           
	        /// <returns type="actions" />     
	        /// <since>1.0.0</since>       
	      
			autocomplete: function(event){
				var acOptions = {};
				if ( AutoCompOptionsLocation.options.country != '' ) {
					var countries = AutoCompOptionsLocation.options.country.split(',');
					acOptions.componentRestrictions = { country: countries }
				}

				input=document.getElementById(AutoCompOptionsLocation.input_location);
				var autocomplete = new google.maps.places.Autocomplete(input, acOptions);

				input=document.getElementById(AutoCompOptionsLocation.input_address);
				var autocomplete = new google.maps.places.Autocomplete(input, acOptions);
				
				input=document.getElementById(AutoCompOptionsLocation.input_pincode);
				var autocomplete = new google.maps.places.Autocomplete(input, acOptions);					
			}
		}
	}
};		   

GoogleMapAutocomplete = GoogleMapAutocomplete();
jQuery(document).ready(function($) {
	GoogleMapAutocomplete.init();
});