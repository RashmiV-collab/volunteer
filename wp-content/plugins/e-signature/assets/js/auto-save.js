(function ($) {

    function get_tinymce_content() {
        if ($("#wp-document_content-wrap").hasClass("tmce-active")) {
            return tinyMCE.activeEditor.getContent();
        }
        else {
            return $('#document_content').val();
        }
    }

    function autosave() {

        jQuery('#document_form').each(function () {

            var document_title = $('#document-title').val();
            var document_content = get_tinymce_content();

            if (document_title == "" && document_content == "")
            {
                return false;
            }

            //Alerting user for auto saving 
            $("#submit_send_stand").val("Auto Saving").prop('disabled', true);
            $("#submit_send").val("Auto Saving").prop('disabled', true);
            $("#submit_add_template").val("Auto Saving").prop('disabled', true);
           //$("#submit_send_stand").prop('disabled', true);

            $('#esig-preview-document').show();
            jQuery.ajax({
                url: autosaveAjax.ajaxurl + "?action=esig_auto_save",
                data: {
                    'autosave': true,
                    'esig_type': autosaveAjax.doc_type,
                    'document_content': get_tinymce_content(),
                    'formData': $(this).serialize()
                },
                type: 'POST',
                success: function (data) {
                    // alert(get_tinymce_content());
                    if (data) {
                        if (!isNaN(data)) {

                            var docId = $('#document_id').val();
                            if (!docId) {
                                $('#document_id').val(data);
                                var previewLink = $("#esig-preview-link").attr('href');
                                if (previewLink) {
                                    var newLink = updateQueryStringParameter(previewLink, 'document_id', data);

                                    $("#esig-preview-link").attr('href', newLink);
                                }
                                
                            }


                        }
                        // revert button text from auto save to previous state
                        $("#submit_send_stand").val("Publish Document").prop('disabled',false);
                        $("#submit_send").val("Send Document").prop('disabled', false);
                        $("#submit_add_template").val("Add Template").prop('disabled', false);

                    } else {
                        // alert("Oh no!");
                    }
                } // end successful POST function
            }); // end jQuery ajax call
        }); // end setting up the autosave on every form on the page
    } // end function autosave()

    var interval = setInterval(autosave, autosaveAjax.autosave_interval * 150);
    //alert('test');
    $("form input[type=submit]").click(function () {
        
        clearInterval(interval); // stop the interval
    });


})(jQuery);


function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
        return uri + separator + key + "=" + value;
    }
}