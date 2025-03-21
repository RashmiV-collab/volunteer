var EventExport= function () {
    /// <summary>Constructor function of the contact to the organizer class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="EventExport" /> 

    return {
	    /// <summary>
        /// Initializes the EventExport.       
        /// </summary>                 
        /// <returns type="initialization settings" />     
        /// <since>1.0.0</since>  
        init: function() {           
             
      		jQuery("#event_csv_custome").click(function(){
                jQuery('#custom_events_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
        		jQuery("#event_csv_custome").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');
                        
                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
                jQuery('#event_manager_export_events_chosen .chosen-choices .search-field .default').val('Select Events');
			});
            jQuery('#event_manager_export_event_fields').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_event_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_event_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });
            jQuery('#event_manager_export_events').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_events_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_events option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_events").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });

            // event xml
            jQuery("#event_xml_custome").click(function(){
                jQuery('#custom_events_xml_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
        				  
        		jQuery("#event_xml_custome").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');
                        
                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
                jQuery('#event_manager_export_xml_events_chosen .chosen-choices .search-field .default').val('Select Events');
			});
           
            jQuery('#event_manager_export_xml_event_fields').change(function(){
                var custom_fields = [];    
                setTimeout(function () {  
                    jQuery('#event_manager_export_xml_event_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xml_event_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_xml_export_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });
            jQuery('#event_manager_export_xml_events').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_xml_events_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xml_events option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_xml_events").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });
            
            //event xls
            jQuery("#event_xls_custome").click(function(){
                jQuery('#custom_events_xls_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
        		jQuery("#event_xls_custome").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');
                        
                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
                jQuery('#event_manager_export_xls_events_chosen .chosen-choices .search-field .default').val('Select Events');
			});
            jQuery('#event_manager_export_xls_event_fields').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_xls_event_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xls_event_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });
            jQuery('#event_manager_export_xls_events').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_xls_events_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xls_events option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_xls_events").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });

            //organizer
            jQuery("#custom_organizer_csv").click(function(){
                jQuery('#custom_organizer_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
                
                jQuery("#custom_organizer_csv").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');

                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
           }); 
           jQuery('#event_manager_export_organizer_fields').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_organizer_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_organizer_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_organizer_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });

            //organizer xml
            jQuery("#custom_organizer_xml").click(function(){
                jQuery('#custom_organizer_xml_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
                
                jQuery("#custom_organizer_xml").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');

                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
            });
            jQuery('#event_manager_export_xml_organizer_fields').change(function(){
                var custom_fields = []; 
                setTimeout(function () {    
                    jQuery('#event_manager_export_xml_organizer_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xml_organizer_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_xml_organizer_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });
            
             //organizer xls
             jQuery("#custom_organizer_xls").click(function(){
                jQuery('#custom_organizer_xls_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
                
                jQuery("#custom_organizer_xls").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');

                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
           }); 
           jQuery('#event_manager_export_xls_organizer_fields').change(function(){
                var custom_fields = [];  
                setTimeout(function () {    
                    jQuery('#event_manager_export_xls_organizer_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xls_organizer_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_xls_organizer_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });

            //venue
            jQuery("#custom_venue_csv").click(function(){
                jQuery('#custom_venue_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
                
                jQuery("#custom_venue_csv").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');

                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
           });
           jQuery('#event_manager_export_venue_fields').change(function(){
                var custom_fields = []; 
                setTimeout(function () {     
                    jQuery('#event_manager_export_venue_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_venue_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_venue_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });

            //venue xml
            jQuery("#custom_venue_xml").click(function(){
                jQuery('#custom_venue_xml_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
                
                jQuery("#custom_venue_xml").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');

                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
            });
            jQuery('#event_manager_export_xml_venue_fields').change(function(){
                var custom_fields = []; 
                setTimeout(function () {     
                    jQuery('#event_manager_export_xml_venue_fields').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xml_venue_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_xml_venue_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });

            //venue xls
            jQuery("#custom_venue_xls").click(function(){
                jQuery('#custom_venue_xls_form').slideToggle("slow");
                jQuery(this).toggleClass('wpem-active-button');
                
                jQuery("#custom_venue_xls").find('i').toggleClass('wpem-icon-arrow-up').toggleClass('wpem-icon-arrow-down');

                if (jQuery(".event-manager-select-chosen").length > 0){
                    jQuery(".event-manager-select-chosen").chosen();
                }
           });
           jQuery('#event_manager_export_xls_venue_fields').change(function(){
                var custom_fields = []; 
                setTimeout(function () {     
                    jQuery('#event_manager_export_xls_venue_fields_chosen').find('[class*="search-choice-close"]').each(function(){
                        //get field index
                        var field_index = jQuery(this).attr("data-option-array-index");
                        //get custom field from index
                        var field_value = jQuery("#event_manager_export_xls_venue_fields option:eq("+field_index+")").val();
                        custom_fields.push(field_value);
                    });
                
                    jQuery("#event_manager_custom_export_xls_venue_fields").val(custom_fields);
                    custom_fields = [];
                }, 500);
            });
		},
        actions:
        {  } //end of the actions       
    } //end of the return
};

EventExport= EventExport();
jQuery(document).ready(function($) {
   EventExport.init();  
});