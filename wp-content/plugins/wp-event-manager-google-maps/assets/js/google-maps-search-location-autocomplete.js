
var GoogleMapAutocompleteLocation = function () {
    /// <summary>Constructor function of the event GoogleMapAutocompleteLocation class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="GoogleMapAutocompleteLocation" />  
    
  	return {
		/// <summary>
        /// Initializes the GoogleMaps autocomplete location.       
        /// </summary>                 
        /// <returns type="initialization settings" />     
        /// <since>1.0.0</since>  
        init: function() {
              Common.logInfo("GoogleMapAutocompleteLocation.init...");  
			  jQuery(document).ready(GoogleMapAutocompleteLocation.actions.autocompleteLocationSearch);   
		},
		actions:{
			/// <summary>
	        /// autocomplete Location Search on events page.	     
	        /// </summary>
	        /// <param name="parent" type="assign"></param>           
	        /// <returns type="actions" />     
	        /// <since>1.0.0</since>       
	      
			autocompleteLocationSearch: function(event){
				input=document.getElementById(AutoCompOptions.input_field);

				var acOptions = {};
				if ( AutoCompOptions.country != '' ) {
					var countries = AutoCompOptions.country.split(',');
					acOptions.componentRestrictions = { country: countries }
				}

				var autocomplete = new google.maps.places.Autocomplete(input, acOptions);	
				autocomplete.addListener('place_changed', function() {
    			     jQuery('#'+AutoCompOptions.input_field).trigger('change')
    			});
			}
		}
	}
};		   

GoogleMapAutocompleteLocation = GoogleMapAutocompleteLocation();
jQuery(document).ready(function($) {
	GoogleMapAutocompleteLocation.init();
});