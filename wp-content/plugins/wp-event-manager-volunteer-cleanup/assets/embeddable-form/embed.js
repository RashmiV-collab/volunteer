var EmbeddableEventWidget = function () {
    /// <summary>Constructor function of the EmbeddableEventWidget class.</summary>
    /// <since>1.0.0</since>
    /// <returns type="EmbeddableEventWidget" /> 

    var embeddable_event_widget_page = 1;
    var embeddable_event_widget_script;

    return {

        init: function () {
            /// <summary>Initializes the EmbeddableEventWidget.</summary>
            /// <since>1.0.0</since>
           
            EmbeddableEventWidget.get_events(1);
        },

        get_events: function (page) {
            /// <summary>get the events.</summary>
            /// <since>1.0.0</since>

            var head = document.getElementsByTagName("head")[0];
            embeddable_event_widget_script = document.createElement("script");
            embeddable_event_widget_script.async = true;
            embeddable_event_widget_script.src = embeddable_event_widget_options.script_url + '&keywords=' + escape(embeddable_event_widget_options.keywords) + '&location=' + escape(embeddable_event_widget_options.location) + '&categories=' + escape(embeddable_event_widget_options.categories) + '&event_types=' + escape(embeddable_event_widget_options.event_types) + '&per_page=' + escape(embeddable_event_widget_options.per_page) + '&pagination=' + escape(embeddable_event_widget_options.pagination) + '&page=' + escape(page);
            
            head.appendChild(embeddable_event_widget_script);
            return false;
        },

        show_events: function (target_id, content) {
            /// <summary>show the events.</summary>
            /// <since>1.0.0</since>
                       
            var target = document.getElementById(target_id);
            if (target) {
                target.innerHTML = EmbeddableEventWidget.decode_html(content);
            }
        },

        decode_html: function (html) {
            /// <summary>decode the html.</summary>
            /// <since>1.0.0</since>
           
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        },

        prev_page: function () {
            /// <summary>previous page.</summary>
            /// <since>1.0.0</since>

            embeddable_event_widget_script.parentNode.removeChild(embeddable_event_widget_script);
            embeddable_event_widget_page = embeddable_event_widget_page - 1;

            if (embeddable_event_widget_page < 1) {
                embeddable_event_widget_page = 1;
            }

            EmbeddableEventWidget.get_events(embeddable_event_widget_page)
        },

        next_page: function () {
            /// <summary>next page.</summary>
            /// <since>1.0.0</since>

            embeddable_event_widget_script.parentNode.removeChild(embeddable_event_widget_script);
            embeddable_event_widget_page = embeddable_event_widget_page + 1;
            EmbeddableEventWidget.get_events(embeddable_event_widget_page)
        }

    }
};
EmbeddableEventWidget = EmbeddableEventWidget();

EmbeddableEventWidget.init();