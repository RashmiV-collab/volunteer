var EmbeddableWidgetForm = function () {
    /// <summary>Constructor function of the EmbeddableWidgetForm class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="EmbeddableWidgetForm" /> 

    return {

        init: function () {
            /// <summary>Initializes the EmbeddableWidgetForm.</summary>
            /// <since>1.0.0</since>

            Common.logInfo("EmbeddableWidgetForm.init...");

            jQuery('.event-manager-chosen-select').chosen();
            jQuery("#widget-get-code").on('click', EmbeddableWidgetForm.actions.getCode);
        },

        actions:
        {
            /// <summary>
            /// get widget code           
            /// </summary>
            /// <param name="parent" type="string"></param>           
            /// <returns type="bool" />     
            /// <since>1.0.0</since>       
            getCode: function (event)
            {
                Common.logInfo("EmbeddableWidgetForm.getCode...");

                var keywords = jQuery('#widget_keyword').val();
                var location = jQuery('#widget_location').val();
                var per_page = jQuery('#widget_per_page').val();
                var pagination = jQuery('#widget_pagination').is(':checked') ? 1 : 0;
                var categories = jQuery('#widget_categories').val();
                var event_types = jQuery('#widget_event_type').val();
                var event_hosted = jQuery('#widget_event_hosting').is(':checked') ? 1 : 0;
                var event_distance = jQuery('#widget_distance').val();

                if (categories) {
                    categories = categories.join();
                } else {
                    categories = '';
                }

                if (event_types) {
                    event_types = event_types.join();
                } else {
                    event_types = '';
                }

                // This is showing whole code, here <script>.... </script> part is very imp, embeddable_event_widget_options will use in embed.js at get_events method.
                // then it will feed argument to event_widget_js method.
                // used in get_events method of the form.js file 
                var embed_code = "<script type='text/javascript'>\n\
	                                var embeddable_event_widget_options = {\n\
		                                'script_url' : '" + embeddable_event_widget_form_args.script_url + "',\n\
		                                'keywords'   : '" + escape(keywords) + "',\n\
		                                'location'   : '" + escape(location) + "',\n\
		                                'categories' : '" + categories + "',\n\
		                                'event_types'  : '" + event_types + "',\n\
                                        'event_distance'  : '" + event_distance + "',\n\
		                                'per_page'   : '" + parseInt(per_page) + "',\n\
		                                'pagination' : '" + parseInt(pagination) + "',\n\
                                        'event_hosted' : '" + parseInt(event_hosted) + "'\n\
	                                };\n\
                                </script>\n" + embeddable_event_widget_form_args.css + "\n" + embeddable_event_widget_form_args.code;

                jQuery('#widget-code').val(embed_code).focus().select();
                jQuery('#widget-code-preview iframe').remove();
                var iframe = document.createElement('iframe');
                var html = '<!doctype html><html><head></head><body style="margin:0; padding: 0;">' + embed_code + '</body></html>';
                jQuery('#widget-code-preview').append(iframe);
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(html);
                iframe.contentWindow.document.close();
                jQuery('#widget-code-wrapper').slideDown();

            }
        }      


    }
};
EmbeddableWidgetForm = EmbeddableWidgetForm();
jQuery(document).ready(function ($) {
    EmbeddableWidgetForm.init();
});