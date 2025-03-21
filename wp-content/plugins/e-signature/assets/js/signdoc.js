
(function ($) {

    "use strict";

    var popup_contenat_id = 'signer-signature'; //Id of the pop-up content

    var sender_input = $('input[name="sender_signature"]');
    sender_input = sender_input[0];
    var sender_sig = $(sender_input).val();

    // define signaturePad here 
    let signatureCanvas = document.querySelector("#signatureCanvas");

    let wpEsignaturePad = '';
    if(signatureCanvas)
    {
        wpEsignaturePad = new SignaturePad(signatureCanvas, {
            // It's Necessary to use an opaque color when saving image as JPEG;
            // this option can be omitted if only saving as PNG or SVG
            backgroundColor: 'rgb(100, 100, 100,0)'
        });
    }


    // remove footer if visiting from mobile . 
    if (esigAjax.esig_mobile == '1')
    {
        $('#esig-footer').hide();
        $('#esig-mobile-footer').hide();
    } else
    {
        $('#esig-footer').show();
        $('#esig-mobile-footer').hide();
    }

    // fill the screen width input 

    $("#esig-screen-width").val(screen.width);
    // tab start here
    $('#tabs').smartTab({autoProgress: false, stopOnFocus: true, transitionEffect: 'vSlide'});

    // If read-only form is present, the doc has been signed. Show signatures

    if (document.forms['readonly']) {

        if (esigAjax.esig_mobile == '1')
        {
            $('#esig-footer').hide();
            $('#esig-mobile-footer').show();
        }

        var sig = "yes";

    } else {


        var recipient_input = $('input[name="recipient_signature"]');

        //console.log('reci:'+$('input[name="recipient_signature"]').val());
        recipient_input = recipient_input[0];

        if (recipient_input) {

            var sig = recipient_input.value;

            //var signaturePadEdit = $('.signature-wrapper').signaturePad(edit_opts);

          //  var signatureDisplayRecipient = $('.signature-wrapper-displayonly.recipient').signaturePad(display_opts);


            if (sig != "") {

                if (signatureDisplayRecipient) {
                    signatureDisplayRecipient.regenerate(sig);
                }
                if (signaturePadEdit) {
                   // signaturePadEdit.regenerate(sig);
                }
            }
            
            // preventing enter button click

            /*$("#sign-form").bind("keypress", function(e) {
                if (e.keyCode == 13) {
                    $('.signature-wrapper-displayonly').trigger('click');
                    return false;
                }
            });*/

            /**
             * Listen event to clear signature pad 
             * @since 1.5.7.5 
             */
            wpEsig("#clearSignaturePad").on("click",function(e){

               // e.preventDefault();
                // clear signature pad 
                wpEsignaturePad.clear();

            });

            // Signature pop-up

            $('.signature-wrapper-displayonly').click(function (e) {

                //if($('#sign-form').valid()){
                //e.preventDefault();
              
                validator.form();

                if (validator.numberOfInvalids() != 0) {
                    return;
                }
                
                if ($("#signatureCanvas2").hasClass("esig-signing-disabled")) {
                   
                    return false;
                }

                if (esigAjax.esig_mobile == '1')
                {

                    var fname = $('input[name="recipient_first_name"]').val();

                    if (/<(.*)>/.test(fname))
                    {
                        $('#recipient_first_name').focus();
                        return false;
                    }

                    $('#esignature-in-text').val(fname);
                    
                    $("#esig-mobile-dialog").modal('show');
                    // scrolling top to make signature easy. 
                   // $(document).scrollTop(0);


                } else
                {

                    //var recipient_first_name = $('#recipient_first_name');

                    var fname = $('input[name="recipient_first_name"]').val();

                    if (/<(.*)>/.test(fname))
                    {
                        $('#recipient_first_name').focus();
                        return false;
                    }

                    // fill with value  modal signer anme 
                    $('#esignature-in-text').val(fname);
                    $("#esignature-in-text").css('border', '0px solid #ff0000');
                    $('#esignature-in-text').formError({remove: true});

                    //$('#esig-iam').html(fname);
                    $('#esig-iam-draw').html(fname);
                    $('#esig-iam-type').html(fname);

                    tb_show(Esign_localize.add_signature, '#TB_inline?width=480&height=370&inlineId=signer-signature');
                }

                document.getElementById('page_loader').style.display = 'none';
                return false;
                //alert('hey hey');			
                //}
            });

            // modal hiding 
            $('#esig-mobile-sig-dismiss').click(function () {
                $('#mobilesigpad').modal('hide')

            });

            $("#esignature-in-text").on("keypress keydown",function(e){
                wpEsig("#esignature-in-text").removeValidationMsg(true);
            });
            // Signature inserted event
            var popup_input = $('.signature-wrapper input[name="output"]');

            $('.signature-wrapper .saveButton').click(function () {

                //if legan name is blank 
                var signature_type = $("input[name='esignature_in_text']").val();
                
                if (/<(.*)>/.test(signature_type))
                {
                    $("input[name='esignature_in_text']").focus();
                    return false;
                }
              
                if (!signature_type)
                {
                    wpEsig("#esignature-in-text").insertAfterValidationMsg("Signer name is not valid.", true);
                    //$("#esignature-in-text").parent().parent().append('<div class="esig-error-box">*You must fill valid access code.</div>');
                    $("input[name='esignature_in_text']").focus();
                    $("#esignature-in-text").css('border', '1px solid #ff0000');
                    return false;
                }
                
                if (!esign.isFullName(signature_type) && $("#recipient_first_name").hasClass("esig-no-form-integration"))
                {
                    //alert("A full name including your first and last name is required.");
                    wpEsig("#esignature-in-text").insertAfterValidationMsg("A full name including your first and last name is required to sign this document. Spaces after last name will prevent submission.", true);
                    $("input[name='esignature_in_text']").focus();
                    $("#esignature-in-text").css('border', '1px solid #ff0000');
                    return false;
                   
                }

                // create a content variable 
                let SignaturePadcontent = '<span id="esig-signature-added rtl-sign-arrow">' + 
                        '<img id="esig-sign-arrow" src="http://localhost/esig/2022/wp-content/plugins/e-signature/assets/images/sign-arrow.svg" alt="Signature arrow sign here" class="sign-arrow rtl-sign-arrow" width="80px" height="70px">'+
                        '<div id="signatureCanvas2" area-label="Sign here" alt="Sign here" class="sign-here pad " height="85"></div>' +
                        '<input type="hidden" id="esig-recipient-signature" name="recipient_signature" class="output" value="">' +
                   '</span>';
            
                if(wpEsig("#signatureCanvas2").selector===null  && wpEsig("#esig-signature-added").selector === null)
                {
                    
                    wpEsig(".signature-wrapper-displayonly").append(SignaturePadcontent);
                }
    
                // if recipient name field is not read only then fill with signature name
                let recipientFirstName = $("#recipient_first_name");

                if (recipientFirstName.prop("readonly") == false && signature_type != recipientFirstName.val()) {
                    $("input[name='recipient_first_name']").val(signature_type);
                }

                //$("input[name='recipient_first_name']").val(signature_type);
                // signature adding removing type and enabling draw
                $('#esig-signature-added').show();
                $('.signature-wrapper-displayonly .esig-sig-type').remove();
                var w = $(window).width();
                var canvaswidth = (w / 4) * 3;

                $('#signatureCanvas2').show();
                $("#signatureCanvas2").attr("width", "500");

                // If signature is blank return false;
                if(wpEsignaturePad.isSignatureBlank())
                {
                    alert(Esign_localize.signaturenotvalid);
                    return false;
                }
                

                let imageUrl = wpEsignaturePad.toDataURL();
                
                $("#signatureCanvas2").html('<img class="signature-image" src='+ imageUrl +' >');

               // signatureDisplayRecipient.regenerate(popup_input.val());
                $('#esig-recipient-signature').val(imageUrl);

                tb_remove();

                $('.signature-wrapper input[name="output"]').trigger('change');

                $('.signature-wrapper-displayonly .sign-here').removeClass('unsigned').addClass('signed');
                $('.signature-wrapper-displayonly .sign-here').addClass('sigvalid');
                $('.signature-wrapper-displayonly .sign-here').addClass('sigPadHeight');

                // validation checking here 
                 
                validator.form();

                if (validator.numberOfInvalids() == 0) {
                    $('#esig-print-button').remove();
                    $('#esig-pdf-download').remove();

                    $('#esig-agree-button').removeClass('disabled').trigger('showtip');
                }

                // Hide arrow once signature inserted 
                $("#esig-sign-arrow").hide();

            });
        } // undefined checking here. 
    }

    $('.closeButton').click(function () {
        $('.mobile-overlay-bg').hide();
        $('body').removeClass('mobile-overlay-bg-black');
       
    });

    var popup_invite = $('.signatures input[name="invite_hash"]');


    if (!sig)
    {
        if ($('.signature-wrapper-displayonly-signed').hasClass('signed'))
        {
            sig = 'yes';
        }

    }

    // Footer Ajax. Runs afer each page load for dynamic footer
    if (esigAjax.preview || (esigAjax.document_id && sig)) {
        //alert(esigAjax.esig_mode);
        $('.esig-container').hide();
        $.get(esigAjax.ajaxurl,
                {
                    method: "get_footer_ajax",
                    className: "WP_E_Shortcode",
                    inviteCode: popup_invite.val(),
                    url: esigAjax.ajaxurl,
                    preview: esigAjax.preview,
                    document_id: esigAjax.document_id,
                    esig_mode: esigAjax.esig_mode,
                    ccpreview: esigAjax.ccpreview,
                },
                function (data) {
                    $('#esig-footer').html(data);
                }
        );


    }
    // mobile submit start here 
    $('#esign_click_mobile_submit').click(function () {
        $('#esign_click_submit').trigger('click');
    });

    // Agree button is disabled until document is signed
    $('#esig-agree-button').click(function () {
        document.getElementsByClassName('esig-template-page')[0].removeAttribute( "onbeforeunload" );
        validator.form();

        if (validator.numberOfInvalids() > 0)
        {
            return false;
        }

        // Full signature validation 
        let signatureImage = $("#esig-recipient-signature").val();
      
        if (signatureImage && !esign.isValidBase64Image(signatureImage))
        {
            alert("Signature image is not valid");
            return false;
        }

        if ($('#esig-agree-button').hasClass('disabled')) {
            return false;
        }

        $('.mobile-overlay-bg').hide();
        document.getElementById('page_loader').style.display = 'block';
        var overlay = $('<div class="page_loader_overlay"></div>').appendTo('body');
        $(overlay).show();

        // disabling agree and sign but so that uesr can submit only one time 
        $('#esig-agree-button').addClass('disabled').trigger('hidetip');
        $('#esig-agreed').html(Esign_localize.signing);

        $('form[name="sign-form"]').submit();

        return false;
    });

    $('#esig-agree-button').addClass('disabled');


    var validator = $('#sign-form').validate({
        errorClass: 'esig-error',
        invalidHandler: function (event, validator) {
            try {
                var first_error = validator.errorList[0].element;

                var tag = first_error.tagName;
                var field_name = first_error.getAttribute('name');

                $('html, body').animate({
                    scrollTop: $(tag + '[name="' + field_name + '"]').offset().top - 20
                }, 1500);

            } catch (err) {

                console.log('invalidHandler Error' + err)
            }
        },
        errorPlacement: function (error, element) {

            if (element.attr('type') == "checkbox") {

                error.insertAfter('#error-' + element.attr('id'));

            } else if (element.attr('type') == "radio") {

                error.insertAfter('#error-' + element.attr('id'));
            } else {
                error.insertAfter(element);
            }


        }
    });


    // Validate form when user has signed
    $('#esig-type-in-text-accept-signature').click(function (e) {


        var signature_type = $("input[name='esignature_in_text']").val();

        
        if (signature_type.replace(/\s+/g, '').length == 0)
        {
            
            $("input[name='esignature_in_text']").focus();
            $("#esignature-in-text").css('border', '1px solid #ff0000');
            return false;
        } 
       
        if (!signature_type)
        {
            $("input[name='esignature_in_text']").focus();
            return false;
        }
        
        if (!esign.isFullName(signature_type) && $("#recipient_first_name").hasClass("esig-no-form-integration"))
        {
            $("#esignature-in-text").formError("A full name including your first and last name is required to sign this document. Spaces after last name will prevent submission.");
            $("input[name='esignature_in_text']").focus();
            $("#esignature-in-text").css('border', '1px solid #ff0000');
            return false; 
        }

        validator.form();
        
       

        if (validator.numberOfInvalids() == 0) {
            
           

            $('#esig-print-button').remove();
            $('#esig-pdf-download').remove();
            $('#esig-agree-button').removeClass('disabled').trigger('showtip');
            
            var fname = $("input[name='esignature_in_text']").val();
           
           // $('#esig-iam').html(Esign_localize.iam + ' ' + fname + ' ' + Esign_localize.and + ' ');
            $('#esig-iam').html(fname+' ');
        }
        // Hide signature arrow
        $("#esig-sign-arrow").hide();
    });


    $('#esignature-in-text').keypress(function () {
        $(this).formError({remove: true});
    });

    // Validate form when user has signed
    $('.signature-wrapper input[name="output"]', '#sign-form').change(function () {

        validator.form();

        if (validator.numberOfInvalids() == 0) {
            $('#esig-print-button').remove();
            $('#esig-pdf-download').remove();
            $('#esig-agree-button').removeClass('disabled').trigger('showtip');

            var fname = $("input[name='recipient_first_name']").val();
            $('#esig-iam').html(Esign_localize.iam + ' ' + fname + ' ' + Esign_localize.and + ' ');
        }
    });



    // Eager validate after signed
    $('input[type="text"], select, checkbox', '#sign-form').change(function () {

        //get legan name 

        if ($('.signature-wrapper-displayonly .sign-here').hasClass('sigvalid')) {
            validator.form();
            if (validator.numberOfInvalids() == 0) {
                $('#esig-print-button').remove();
                $('#esig-pdf-download').remove();
                $('#esig-agree-button').removeClass('disabled').trigger('showtip');


                $('#esig-iam').html(Esign_localize.iam + ' ' + fname + ' ' + Esign_localize.and + ' ');

            } else {
                $('#esig-agree-button').addClass('disabled').trigger('hidetip');
            }
        }
    });



    // Agree Button Tool Tip
    $.fn.tooltips = function (el) {

        var $tooltip,
                $body = $('body'),
                $el;


        return $("#esign_click_submit").each(function (i, el) {

            $el = $(el).attr("data-tooltip", i);

            // Make DIV and append to page
            var content = $('#agree-button-tip').html();

            var $tooltip = $('<div class="sig-tooltip"  data-tooltip="' + i + '">' +
                    content +
                    '<div class="arrow"></div></div>'
                    ).appendTo(el);


            var overlay = $('<div class="esig-tooltip-overlay"></div>').appendTo('body');

            // Position right away, so first appearance is smooth
            var linkPosition = $el.offset();

            var topOffset = -20; // Offset the top position of the tip
           
            $tooltip.css({
                top: 0 - $tooltip.outerHeight() - topOffset,
                //left: linkPosition.left - ($el.width() / 2)
                right:0,
                "text-align":"left"
            });

            $el.on('showtip', function () {

                $el = $("#esign_click_submit");

                if ($el.hasClass('disabled')) {
                    //return;
                }

                $tooltip = $('div[data-tooltip=' + $el.data('tooltip') + ']');

                // Reposition tooltip, in case of page movement e.g. screen resize
                var linkPosition = $el.offset();

                $tooltip.css({
                    top: 0 - $tooltip.outerHeight() - topOffset,
                    //left: linkPosition.left - 125
                });

                // Adding class handles animation through CSS
                $tooltip.addClass("active");

                //$(overlay).show();

            });

            $el.on('hidetip', function () {
                $el = $(this);
                $tooltip = $('div[data-tooltip=' + $el.data('tooltip') + ']');
                $tooltip.removeClass('active').addClass('disabled');
            });
        });

    } // End Tool Tip

  $('body').on('click', '.clearButton', function () {
      $('#esig-agree-button').addClass('disabled').trigger('hidetip');
  });

    // Click and show terms and condition 
    $('body').on('click', '.tooltip #esig-terms', function () {

        jQuery.ajax({
            type: "POST",
            url: esigAjax.ajaxurl + "&className=WP_E_Common&method=esig_get_terms_conditions",
            success: function (data, status, jqXHR) {

                $('.esig-terms-modal-lg .modal-body').html(data);
            },
            error: function (xhr, status, error) {
                $('.esig-terms-modal-lg .modal-body').html('<h1>No internet connection</h1>');
            }
        });

    });

    // click terms of service . 
    $('body').on('click', '#esig-terms', function () {

        if (esigAjax.esig_mobile == '1')
        {
            $("#esig-mobile-dialog").modal('hide');
        }

        $.post(esigAjax.ajaxurl + "&className=WP_E_Common&method=esig_get_terms_conditions", function (data) {

            $('.esig-terms-modal-lg .modal-body').html(data);

            // $('.esig-terms-modal-lg .modal-body').append("close<br></br>");

        });


    });





    // inserting signature from mobile
    $("#mobile-adopt-sign").on("tap click", function () {
        document.getElementsByClassName('esig-template-page')[0].removeAttribute( "onbeforeunload" );
         //$("#mobile-adopt-sign").click(function () {


        var fname = $("input[name='recipient_first_name']").val();

        if (!fname)
        {
            alert("Your legal name can not be empty.");
            return false;
        }

        if (fname.replace(/\s+/g, '').length == 0)
        {
            alert("Your legal name can not be empty.");
            $("input[name='esignature_in_text']").focus();
            return false;
        }

        if (!esign.isFullName(fname) && $("#recipient_first_name").hasClass("esig-no-form-integration"))
        {
            
            var japanesRegex = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]|[\u2605-\u2606]|[\u2190-\u2195]|\u203B/g;
            var apostropheRegex = /^[A-Z][a-z’]+(\s[A-Z][a-z’]+)*$/;

            if(!japanesRegex.test(fname) || !apostropheRegex.test(value)) {
                alert("A full name including your first and last name is required to sign this document. Spaces after last name will prevent submission.");
                $("input[name='esignature_in_text']").focus();
                return false;
            }
        }

        if (/<(.*)>/.test(fname))
        {
            $('#recipient_first_name').focus();
            return false;
        }

        if (wpEsignaturePad.isSignatureBlank() && !$("#mobile-type-sig").hasClass("active")) {
          
            alert(Esign_localize.signaturenotvalid);
            return false;
        }

        if ($(this).hasClass('already-signed')) {
            return false;
        } else {
            $(this).addClass('already-signed');
            $(this).html(Esign_localize.signing);
        }
        var signature_type = $("input[name='esignature_in_text']").val();

        if (signature_type)
        {
            var font = $('#font-type').val();
            var draw_signature = $("input[name='output']").val();
            var font_type = $("input[name='font_type']").val();

            var htmlcontent = '<div class="sign-here pad signed esig-sig-type esig-signature-type-font' + font + '" width="100%"><span class="esig-sig-type1">' + signature_type + '</span></div>';
            htmlcontent += '<input type="hidden" name="esig_signature_type" value="typed">';
            htmlcontent += '<input type="hidden" name="esignature_in_text" value="' + signature_type + '">';
            htmlcontent += '<input type="hidden" name="font_type" value="' + font_type + '">';
            //  htmlcontent += '<input type="hidden" name="recipient_signature" class="output" value="'+ draw_signature +'"></div>';


            // getting first name value


            if (signature_type != fname) {
                $("input[name='recipient_first_name']").val(signature_type);
            }

            $("input[name='esignature_in_text']").val(signature_type);

            $('#esig-mob-input').html(htmlcontent);

            var newSize = signature_type.length;
            newSize = 64 - (1.5 * newSize);
            if(newSize < 18){
                    newSize = 18; 
                }
            $('.esig-signature-type-font' + font).css("font-size", newSize + "px");

            var drawSign = $('#mobile-draw-signature').attr('style');
            
            if(drawSign == 'display: none;'){  
                $('.signature-wrapper-displayonly span').attr("id","esig-signature-added-rtl-sign-arrow");
                $('#esig-signature-added-rtl-sign-arrow').remove();
                $('#signatureCanvas').remove();
                $('#signatureCanvas2').remove();
            }
        
        
        }
       
            // making larger signature in small
            //signatureDisplayRecipient.regenerate(popup_input.val());
            let imageUrl = wpEsignaturePad.toDataURL();

        
            if(!wpEsignaturePad.isSignatureBlank() && imageUrl)
            {
                $('#esig-recipient-signature').val(imageUrl);
            }
            //$("#signatureCanvas2").html('<img width="250px" src=' + imageUrl + ' >');
            // signatureDisplayRecipient.regenerate(popup_input.val());
        // Full signature validation 
        let signatureImage = $("#esig-recipient-signature").val();

        if (signatureImage && !esign.isValidBase64Image(signatureImage)) {
            alert("Signature image is not valid");
            return false;
        }

        validator.form();

        if (validator.numberOfInvalids() > 0)
        {
            return false;
        }

        $('#sign-form')[0].submit();
        return false;

    });



})(jQuery);

jQuery(".esig-template-page .agree-button").tooltips();



/**
 * @author Shawna Culp
 * @description keyboard only, the signature area is keyboard accessible.https://secure.helpscout.net/conversation/356638108/14101/?folderId=471644
 * @
 * @param {type} $
 * @returns {undefined}
 */

(function ($) {

    $(document).ready(function () {
        makeSignatureKeyboardAccessible();
    }); 

    function makeSignatureKeyboardAccessible() {

        var signaturePopup = $('.signature-wrapper-displayonly');

        if (signaturePopup) {
            signaturePopup.attr('tabindex', 0);
        }

        $(".signature-wrapper-displayonly").on('keypress', signaturePopup, function (e) {
            var code = e.keyCode || e.which;

            if (code == 13) {
                signaturePopup.click();
            }
        });
    }
    
   

})(jQuery);










