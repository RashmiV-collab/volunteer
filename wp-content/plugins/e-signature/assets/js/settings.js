(function($){





	// check for existing signature

	var output = $('input[name="output"]');

	output = output[0];

	var sig = output.value;

	var popup_content_id = 'admin-signature'; //Id of the pop-up content



	var edit_opts = {

		drawOnly: true,

		validateFields : false,

		penColour: '#000000',

		lineWidth: '0',

		lineColour: 'rgba(255,255,255,0)',

		displayOnly:false, //useful for when re-signing

		bgColour : 'transparent'

	};



	var display_opts = {

		penColour: '#000000',

		displayOnly: true,

		bgColour : 'transparent',

	};



	//var signaturePadEdit = $('.signature-wrapper').signaturePad(edit_opts);

	//var signaturePadDisplay = $('.signature-wrapper-displayonly').signaturePad(display_opts);


	// define signaturePad here 
	let signatureCanvas = document.querySelector("#signatureCanvas");

	let wpEignaturePad = '';
	if (signatureCanvas) {
		wpEignaturePad = new SignaturePad(signatureCanvas, {
			// It's Necessary to use an opaque color when saving image as JPEG;
			// this option can be omitted if only saving as PNG or SVG
			backgroundColor: 'rgb(100, 100, 100,0)'
		});
	}



    if(sig != "")
	{
         $(".esig-sig-type").remove();
		// signaturePadDisplay.regenerate(sig);
		// signaturePadEdit.regenerate(sig);
	}
    else
    {
           var signature_text = $("input[name='esignature_in_text']").val();

			if(signature_text)
			{
				$('#signatureCanvas2').hide();
			}

    }

	/**
	 * Clear signature pad
	 * @since 1.5.7.5
	 */
	wpEsig("#esig-admin-signaturepad-clear").on("click",function(e){
			wpEignaturePad.clear();
	});

	$('.signature-wrapper-displayonly').click(function(){

		tb_show("+ Add Signature", '#TB_inline?width=480&inlineId=' + popup_content_id);

	});



	// Save signature

	$('.signature-wrapper .saveButton').click(function(){

		/*var output = $('input[name="output"]');
		
		if(!output.val())
		{
			return;
		} */

		nonce = $(this).attr("data-nonce");

		var elem = this;

        $('.esig-sig-type').remove();

        $('#signatureCanvas2').show();

		let signature_type = $("input[name='esignature_in_text']").val();
		
		if (signature_type && !esign.isFullName(signature_type)) {
			alert("Signature full name is not valid.");
			return false;
		}

		if (wpEignaturePad.isSignatureBlank())
		{
			alert("Signature is not valid.");
			return false;
		}
		//signaturePadDisplay.regenerate(output.val());
		let imageUrl = wpEignaturePad.toDataURL();

		wpEsig("#signatureCanvas2").html('<img class="signature-image" src=" ' + imageUrl + '" >');

		$("#admin-signature-output").val(imageUrl);

		tb_remove();

		$(elem).removeClass('loading');

		$('.signature-wrapper-displayonly .sign-here').removeClass('unsigned').addClass('signed');

	});



	// Modal dialog box for the super admin select . .



		var $overwrite = $("#esig-confirm-dialog");

		$overwrite.dialog({

			'dialogClass'   : 'wp-dialog esig-confirm-dialog',

			'title'         : 'Whoah there',

			'modal'         : true,

			'autoOpen'      : false,

			'buttons'       : {

				"Save": function() {

					$(this).dialog('close');

                   $('.button-appme').trigger('click');

				},

				"Cancel": function() {

                    var old_val = $('select option:selected').data('used');



                    $('#esig_admin_user_id').val(old_val);

					$('#esig_admin_user_id').trigger('chosen:updated');

					$(this).dialog('close');

				}

			}

		});



        // On-change event for #stand_alone_page select menu.

		$('#esig_admin_user_id').change(function(){

			var selected = $('option:selected', this);

			     var new_val = $('#esig_admin_user_id option:selected').text();

                 $('#esig_selected_admin').html(new_val);

				$overwrite.dialog('open'); // Popup a dialog



		});







	$('.settings-form').on("submit", function(e){



		var form = $(this);



		form.find(".error").remove(); //remove previous alerts



		var alerts = [];

		var valid = true;



		// validate text fields

		form.find("input[name='first_name'], input[name='last_name'], input[name='user_email'], input[name='user_title'], select[name='default_display_page']").each(function(index){

			$(this).parent().find(".required-asterisk").remove(); //remove previous alerts

			$(this).removeClass("required-alert");



			if($(this).val() == ""){

				$(this).addClass("required-alert");

				$(this).parent().find("label").prepend("<span class='required-asterisk' style='color:red'>*</span>");

				valid = false;

			}

		});

		//signature can not be empty
		var input_text = $("input[name='esignature_in_text']").val();
		var input_sig = $("input[name='output']").val();
		if (input_text == "" && input_sig == "") {
			valid=false ;
		}

		if(!valid){

			var alertmsg = '<div class="error"><p><strong>E-signature </strong> : The required fields must be filled in before saving them.</p></div>';

			$('form[name="settings_form"]').prepend(alertmsg);

			return false;

		}else{

			return true;

		}

	});





    // signature type end here





	$('#upload_company_logo').click(function() {

		tb_show('', 'media-upload.php?referer=e-signature&type=image&TB_iframe=true&post_id=0');

		return false;

	});



	window.send_to_editor = function(html) {

		imgurl = jQuery('img',html).attr('src');

		$('#company_logo').val(imgurl);

		tb_remove();

		$('#company_logo_image_wrap').hide();

	}





  $('#tabs').smartTab({autoProgress: false,stopOnFocus:true,transitionEffect:'fade in'});



  // error dialog popup

	$( "#esig_show_alert" ).dialog({

	'dialogClass'   : 'wp-dialog esig-error-dialog',

	'title'         : 'Action Required!',

      modal: true,

      buttons: {

        Close: function() {

          $( this ).dialog( "close" );

        }

      }

    });

})(jQuery);

