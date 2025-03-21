var GuestListsDashboard = function () {

    return {

        init: function() 
        {
            if(jQuery('.group-dashboard-action-delete').length >0)
            {
                jQuery('.group-dashboard-action-delete').css({'cursor':'pointer'});                     
                //for delete event confirmation dialog / tooltip 
                jQuery('.group-dashboard-action-delete').on('click', GuestListsDashboard.confirmation.showDialogGroup);
            }

            if(jQuery('.guest-dashboard-action-delete').length >0)
            {
                jQuery('.guest-dashboard-action-delete').css({'cursor':'pointer'});                     
                //for delete event confirmation dialog / tooltip 
                jQuery('.guest-dashboard-action-delete').on('click', GuestListsDashboard.confirmation.showDialogGuestLists);
            }
            //load chosen
            if (jQuery(".event-manager-select-chosen").length > 0)
            {

                jQuery(".event-manager-select-chosen").chosen();
            }

            jQuery(".guest_checkin").on("click", GuestListsDashboard.actions.updateCheckin); 
            jQuery(".guest_uncheckin").on("click", GuestListsDashboard.actions.updateCheckin);

            if (jQuery(".wpem-event-guest-lists-field select#event_id").length > 0)
            {
                jQuery(".wpem-event-guest-lists-field select#event_id").on("change", GuestListsDashboard.actions.getGroupByEventID); 
            }

            if (jQuery("#wpem-group-dashboard .wpem-group-dashboard-filter-toggle select#event_id").length > 0)
            {
                jQuery("#wpem-group-dashboard .wpem-group-dashboard-filter-toggle select#event_id").on("change", GuestListsDashboard.actions.getGroupByEventID); 
            }

            if (jQuery(".wpem-event-guest-lists-field select#group_id").length > 0)
            {
                jQuery(".wpem-event-guest-lists-field select#group_id").on("change", GuestListsDashboard.actions.getGroupFieldByGroupID); 
            }

            jQuery('body').on('change', '#wpem-guest-dashboard input.guest-list', GuestListsDashboard.actions.selectGuests);
            jQuery('body').on('change', '#wpem-guest-dashboard #all_select', GuestListsDashboard.actions.allSelectGuests);
            if(jQuery('.guest-list').length > 0){
                jQuery('body').on('click', '#guest_delete', function(){
                    var delete_confirm = confirm(wpem_guest_lists_dashboard.i18n_confirm_guest_lists_delete);     
                    if(delete_confirm){ GuestListsDashboard.actions.deleteGuests()};
                });
            } 
        },

        actions:
        {
            /**
             * checin/checkout function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            updateCheckin: function (t) {

                jQuery(this).closest("span").add(jQuery(this).closest("td").find(".wpem-checkin-hide")).toggleClass("wpem-checkin-hide");
                var e = jQuery(this).data("value"),
                    i = jQuery(this).data("post-id"),
                    n = jQuery("input[name=event_id]").val(),
                    n = jQuery(this).data("event-id"),
                    s = jQuery(this).data("source");
                jQuery.ajax({
                    type: "POST",
                    url: wpem_guest_lists_dashboard.ajax_url,
                    data: { 
                        check_in_value: e, 
                        guest_id: i, 
                        event_id: n, 
                        source: s,
                        action: 'update_event_guest_checkin_data',
                        security: wpem_guest_lists_dashboard.wpem_guests_security, 
                    },
                    beforeSend: function (t, e) {},
                    success: function (t) {
                        
                    },
                    error: function (t, e, i) {},
                    complete: function (t, e) {},
                }),
                    t.preventDefault();
            },

            getGroupByEventID: function (event) {

                var obj = jQuery(this);
                var event_id = obj.val();

                jQuery.ajax({
                    url: wpem_guest_lists_dashboard.ajax_url,
                    type: 'POST',
                    dataType: 'HTML',
                    data: {
                        action: 'get_group_by_event_id',
                        event_id: event_id,
                        security: wpem_guest_lists_dashboard.wpem_guests_security,
                    },
                    success: function (responce)
                    {
                        jQuery('select#group_id').html(responce);
                    }
                });
            },

            getGroupFieldByGroupID: function (event) {

                var obj = jQuery(this);
                var group_id = obj.val();

                jQuery.ajax({
                    url: wpem_guest_lists_dashboard.ajax_url,
                    type: 'POST',
                    dataType: 'HTML',
                    data: {
                        action: 'get_group_fields_by_group_id',
                        group_id: group_id,
                        security: wpem_guest_lists_dashboard.wpem_guests_security,
                    },
                    success: function (responce)
                    {
                        console.log(responce);

                        var fields = JSON.parse( responce );

                        jQuery('.wpem-event-guest-lists-field fieldset[class*="fieldset"]').hide();
                        jQuery('.wpem-event-guest-lists-field fieldset[class*="fieldset"]').each(function(event){

                            jQuery(this).find('input').attr('required',false);
                        });

                        jQuery.each(fields, function(i, field) {
                            jQuery('.wpem-event-guest-lists-field fieldset.fieldset-'+field).show();
                            jQuery('.wpem-event-guest-lists-field fieldset.fieldset-'+field + '#'+field).attr("required", "true");
                        });
                    }
                });
            },
            selectGuests: function (e){

                var allSelectGuests = [];

                jQuery.each(jQuery('#wpem-guest-dashboard input.guest-list:checked'), function () {
                    allSelectGuests.push(jQuery(this).val());
                });
                jQuery('#wpem-guest-dashboard input#guests_ids').val(allSelectGuests.toString());
            },
            allSelectGuests: function (e){

                        if (jQuery(e.target).prop("checked") == true) {
                            jQuery('#wpem-guest-dashboard input[type="checkbox"]').prop('checked', true);
                        } else if (jQuery(e.target).prop("checked") == false) {
                            jQuery('#wpem-guest-dashboard input[type="checkbox"]').prop('checked', false);
                        }

                        var allSelectGuests = [];

                        jQuery.each(jQuery('#wpem-guest-dashboard input.guest-list:checked'), function () {
                            allSelectGuests.push(jQuery(this).val());
                        });

                        jQuery('#wpem-guest-dashboard input#guests_ids').val(allSelectGuests.toString());
            },
            deleteGuests: function(){

                    var delete_guests_id =jQuery('#wpem-guest-dashboard input#guests_ids').val();
                    jQuery.ajax({
                        url: wpem_guest_lists_dashboard.ajax_url,
                        type: 'POST',
                        dataType: 'HTML',
                        data: {
                            action: 'delete_guests',
                            delete_guests_id: delete_guests_id,
                            security: wpem_guest_lists_dashboard.wpem_guests_security,
                        },
                        success: function (responce)
                        {
                            location.reload(true);

                        }
                    });
            },  

        
        }, /* end of action */

        confirmation:{      
            /// <summary>
            /// Show bootstrap third party confirmation dialog when click on 'Delete' options on group dashboard page where show delete group option.        
            /// </summary>
            /// <param name="parent" type="assign"></param>           
            /// <returns type="actions" />     
            /// <since>1.0.0</since>       
            showDialogGroup: function(event) 
            {
                return confirm(wpem_guest_lists_dashboard.i18n_confirm_group_delete);
                event.preventDefault(); 
            },

            showDialogGuestLists: function(event) 
            {
                return confirm(wpem_guest_lists_dashboard.i18n_confirm_guest_lists_delete);
                event.preventDefault(); 
            },
    
        } /* end of comfirmation */

    }; /* enf of return */

}; /* end of class */

GuestListsDashboard = GuestListsDashboard();

jQuery(document).ready(function($) 
{
   GuestListsDashboard.init();
    //for edit list fields on group select
    var group_id = jQuery(".wpem-event-guest-lists-field select#group_id").val();
    if(group_id){

        jQuery.ajax({
            url: wpem_guest_lists_dashboard.ajax_url,
            type: 'POST',
            dataType: 'HTML',
            data: {
                action: 'get_group_fields_by_group_id',
                group_id: group_id,
                security: wpem_guest_lists_dashboard.wpem_guests_security,
            },
            success: function (responce)
            {
                console.log(responce);

                var fields = JSON.parse( responce );

                jQuery('.wpem-event-guest-lists-field fieldset[class*="fieldset"]').hide();
                jQuery('.wpem-event-guest-lists-field fieldset[class*="fieldset"]').each(function(event){

                    jQuery(this).find('input').attr('required',false);
                });

                jQuery.each(fields, function(i, field) {
                    jQuery('.wpem-event-guest-lists-field fieldset.fieldset-'+field).show();
                    jQuery('.wpem-event-guest-lists-field fieldset.fieldset-'+field + '#'+field).attr("required", "true");
                });
            }
        });
    }
    
});
