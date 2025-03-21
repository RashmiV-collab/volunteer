jQuery(document).ready(function($){
    $(document).on('click','#volunteer_alert_notify',function(e){
        e.preventDefault();
        jQuery.ajax({
            type: "GET",
            url: frontendajax.ajaxurl,
            data: {
                action:'volunteer_get_notify_alert_url',
                },
            dataType: "json",
            success: function(data) {
                if(data.url){
                    window.location.href = data.url;
                }
            }
        });
    });
});