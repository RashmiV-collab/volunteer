var AdminGuestLists = function () {

    return {

        init: function() 
        {
            //load chosen
            if (jQuery(".event-manager-select-chosen").length > 0)
            {
                jQuery(".event-manager-select-chosen").chosen();
            }
            
            jQuery('#publish').attr('disabled', 'disabled');
            jQuery('body').on('change', 'select[name=_guests_group]', AdminGuestLists.actions.selectGroup);
            if (jQuery('select[name=_guests_group]').length > 0)
            {
                jQuery('select[name=_guests_group]').trigger('change');
            }

            jQuery(".guest_checkin").on("click", AdminGuestLists.actions.updateCheckin); 
            jQuery(".guest_uncheckin").on("click", AdminGuestLists.actions.updateCheckin);

            if (jQuery("#guest_lists_group select#dropdown_event_listing").length > 0)
            {
                console.log('loading js');
                jQuery("#guest_lists_group select#dropdown_event_listing").on("change", AdminGuestLists.actions.getGroupByEventID); 
            }


            if(jQuery('.group-dashboard-action-delete').length >0)
            {
                jQuery('.group-dashboard-action-delete').css({'cursor':'pointer'});                     
                jQuery('.group-dashboard-action-delete').on('click', AdminGuestLists.confirmation.showDialogGroup);
            }
        },


        actions:
        {
            /**
             * selectGroup function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            selectGroup: function (event) 
            {
                if(jQuery(this).val() != '')
                {
                    jQuery('#publish').removeAttr('disabled');
                }
                else
                {
                    jQuery('#publish').attr('disabled', 'disabled');
                }
            },

            /**
             * checin/checkout function.
             *
             * @access public
             * @param 
             * @return 
             * @since 1.0
             */
            updateCheckin: function (t) {
                jQuery(this).closest("span").add(jQuery(this).closest("td").find(".hidden")).toggleClass("hidden");
                var e = jQuery(this).data("value"),
                    i = jQuery(this).data("post-id"),
                    n = jQuery("input[name=event_id]").val(),
                    s = jQuery(this).data("source");
                jQuery.ajax({
                    type: "POST",
                    url: wpem_guests_admin.ajax_url,
                    data: { 
                        check_in_value: e, 
                        guest_id: i, 
                        event_id: n, 
                        source: s,
                        action: 'update_event_guest_checkin_data',
                        security: wpem_guests_admin.wpem_guests_security, 
                    },
                    beforeSend: function (t, e) {},
                    success: function (t) {
                        jQuery(".check_in_total").html(t);
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
                    url: wpem_guests_admin.ajax_url,
                    type: 'POST',
                    dataType: 'HTML',
                    data: {
                        action: 'get_group_by_event_id',
                        event_id: event_id,
                        security: wpem_guests_admin.wpem_guests_security,
                    },
                    success: function (responce)
                    {
                        
                        jQuery('select#groupchecklist').html(responce);
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
                return confirm(wpem_guests_admin.i18n_confirm_group_delete);
                event.preventDefault(); 
            },
    
        } /* end of comfirmation */

    }; /* enf of return */

}; /* end of class */

AdminGuestLists = AdminGuestLists();

jQuery(document).ready(function($) 
{
   AdminGuestLists.init();
});
