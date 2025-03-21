var EventBookmark= function () {
    /// <summary>Constructor function of the event bookmark class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="EventBookmark" />      
  	return {
		/// <summary>
        /// Initializes the EventBookmark.       
        /// </summary>                 
        /// <returns type="initialization settings" />     
        /// <since>1.0.0</since>  
        init: function() {
               Common.logInfo("EventBookmark.init...");  
			   if(jQuery('.bookmark-notice').length > 0){
			      //for delete event confirmation dialog / tooltip 
				  jQuery('.bookmark-notice').on('click', EventBookmark.notes.openNotes);
			   }
			   
               //check if it is my event bookmark page with delete option
			   if(jQuery('.event-manager-bookmark-action-delete').length > 0){
				  jQuery('.event-manager-bookmark-action-delete').css({'cursor':'pointer'});  					  
				  //for delete event confirmation dialog / tooltip 
				  jQuery('.event-manager-bookmark-action-delete').on('click', EventBookmark.confirmation.showDialog);	
	
			   }
	    },
		notes:{	
			/// <summary>
	        /// Make notes textarea as toggle button.	     
	        /// </summary>
	        /// <param name="parent" type="assign"></param>           
	        /// <returns type="actions" />     
	        /// <since>1.0.0</since>       
	        openNotes: function(event){
	        	Common.logInfo("EventBookmark.notes.openNotes...");

	            jQuery('.bookmark-details').slideToggle();
				jQuery(this).toggleClass('open');
				return false;
	        }
		},        
        confirmation:{
            /// <summary>
	        /// Show bootstrap third party confirmation dialog when click on 'Delete' options on my event bookmark page.	     
	        /// </summary>
	        /// <param name="parent" type="assign"></param>           
	        /// <returns type="actions" />     
	        /// <since>1.0.0</since>       
	        showDialog: function(event){
	        	Common.logInfo("EventBookmark.confirmation.showDialog...");
	        	
	            return confirm(event_manager_bookmarks_bookmark.i18n_confirm_delete);        	
	           	event.preventDefault(); 
	        },
	   }
   }
};

EventBookmark= EventBookmark();
jQuery(document).ready(function($) {
	EventBookmark.init();
});