jQuery( document ).ready(function() {
	jQuery(document).delegate('.single-event-attendee-container ul li a.page-numbers','click',get_pulic_attendee_list);

	setTimeout(function(){ 
        if(jQuery('.single-event-attendee-container').length>0)	{
			get_pulic_attendee_list('');
		}
    }, 1000);

	jQuery('body').on('click', '#event-manager-event-registrations .wpem-listing-accordion', function(){
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.display === "block") {
            panel.style.display = "none";
        } else {
            panel.style.display = "block";
        }
    });
});

function get_pulic_attendee_list(event){	 
	if(event){
		event.preventDefault();
		var param = jQuery(this).attr("href");
		var len_param = jQuery(this).attr("href").length;
		var page_serach= param.substr(param.indexOf('?'), len_param); 
		var urlParams = new URLSearchParams(page_serach);
		var page = jQuery(this).attr('data-page');
	}else{
		var page = 1;
	}

	var post_per_page = jQuery('.single-event-attendee-container').attr('data-per-page');
	var event_id = jQuery('.single-event-attendee-container').attr('data-event-id');
	jQuery.ajax({
			type: 'POST',
			url: event_manager_attendee_information.admin_ajax_url,
			data: {
				action: 'get_paginated_attendees',
				event_id : event_id, 
				paged : page,
				post_per_page: post_per_page
			},
		beforeSend: function(jqXHR, settings) {
			jQuery('.single-event-attendee-container').addClass('loading');
		},
		success: function(data){
			jQuery('.single-event-attendee-container').removeClass('loading');
			jQuery('.single-event-attendee-container').html(data.html);
		},
		error: function(jqXHR, textStatus, errorThrown) { },
		complete: function (jqXHR, textStatus) { }
	});
}
