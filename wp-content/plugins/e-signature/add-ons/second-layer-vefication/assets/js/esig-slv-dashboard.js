
(function ($) {

    var validationMessage = {
        "esig-email-address": "Invalid e-mail address. Please input valid e-mail adddress.",
        "esig-access-code": "Invalid Access Code/Password.",
        "esig-slv-password": "Please enter your password",
        "esig-slv-confirm-password": "Please enter your confirm password"
    };
    
    // email address validation with on focusout. 
    wpEsig("#esig-email-address").on("focusout",function(){

        var emailAdress = wpEsig("#esig-email-address").value();
           
        // check for valid email address
        if (!esig_validation.is_email(emailAdress))
        {
            wpEsig("#esig-email-address").insertAfterValidationMsg(validationMessage["esig-email-address"], true);
        }
        else 
        {
            wpEsig("#esig-show-error").hide();
            wpEsig("#esig-email-address").removeValidationMsg(true);
        }

    });

    // validate access code on focusout event 
    wpEsig("#esig-access-code").on("focusout", function(){

        var accessCode = wpEsig("#esig-access-code").value(); 
        // check access code is fresh string 
        if (!esig_validation.is_string(accessCode)) 
        { 
            wpEsig("#esig-access-code").insertAfterValidationMsg(validationMessage["esig-access-code"], true);
        }
        else
        {
            wpEsig("#esig-show-error").hide();
            wpEsig("#esig-access-code").removeValidationMsg(true);
        }

    });

    /************ populate login ****************/
    $("#esig-access-code-login").click(function (e) {

        var fieldList = document.querySelectorAll("#esig-login-form input");
        
        var validationResult = esigValidateInputs(fieldList,validationMessage);

        if(validationResult)
        {
            return false; 
        }
        
        // checking valid access code input 
        var data = {
            "esig_email_address": wpEsig("#esig-email-address").value(),
            "esig_access_code": wpEsig("#esig-access-code").value(),
            "invite_hash": $("#esig-invite-hash").val(),
            "checksum": $("#esig-document-checksum").val(),
            "sender_name": $("#esig-document-sender_name").val()
        };
        
        $.post(esigAjax.ajaxurl + "&className=Esig_Slv_Dashboard&method=esig_verify_access_code", data).done(function (response) {

            if (response == "verified") {
                window.location.reload();
            } else if (response == "display") {
                $("#esig-login-form").hide();
                $("#esig-password-set-form").show();
            } else {
                wpEsig("#esig-show-error").show();
                wpEsig("#esig-show-error").html(response);
            }

        });

        // alert();

    });

    /**
     *  Adding password focust out event 
     */
    // validate access code on focusout event 
    wpEsig("#esig-slv-password").on("focusout", function () {

        var password = wpEsig("#esig-slv-password").value();
        // check access code is fresh string 
        if (!esig_validation.is_string(password)) {
            wpEsig("#esig-slv-password").insertAfterValidationMsg(validationMessage["esig-slv-password"], true);
        }
        else if (esigHasWhiteSpace(password)) {
            wpEsig("#esig-slv-password").insertAfterValidationMsg("White space is not allowed", true);
            return false;
        }
        else {
            wpEsig("#esig-show-error").hide();
            wpEsig("#esig-slv-password").removeValidationMsg(true);
        }

    });

    /**
     *  Adding confirm password focust out event to validate its input  
     */
    // validate access code on focusout event 
    wpEsig("#esig-slv-confirm-password").on("focusout", function () {

        var password = wpEsig("#esig-slv-confirm-password").value();
        // check access code is fresh string 
        if (!esig_validation.is_string(password)) {
            wpEsig("#esig-slv-confirm-password").insertAfterValidationMsg(validationMessage["esig-slv-confirm-password"], true);
        }
        else if (esigHasWhiteSpace(password)) {
            wpEsig("#esig-slv-confirm-password").insertAfterValidationMsg("White space is not allowed", true);
            return false;
        }
        else {
            wpEsig("#esig-show-error").hide();
            wpEsig("#esig-slv-confirm-password").removeValidationMsg(true);
        }

    });

    // setting password 
    $("#esig-slv-set-password").click(function () {

        // checking valid access code input 

        var fieldList = document.querySelectorAll("#esig-password-set-form input");

        var validationResult = esigValidateInputs(fieldList, validationMessage);

        if (validationResult) 
        {
            return false;
        }

        var password = $("#esig-slv-password").val();
        var confirmPassword = $("#esig-slv-confirm-password").val(); 

        if (esigHasWhiteSpace(password)) 
        {
            wpEsig("#esig-slv-password").insertAfterValidationMsg("White space is not allowed", true);
            return false;
        }

        if (esigHasWhiteSpace(confirmPassword)) {
            wpEsig("#esig-slv-confirm-password").insertAfterValidationMsg("White space is not allowed", true);
            return false;
        }

        if (password !== confirmPassword) 
        {
            wpEsig("#esig-set-error").show();
            wpEsig("#esig-set-error").html("<span class='esig-icon-esig-alert'></span><span class='error-msg' id='error-access-code'>The Password do not match. Thats OK though....Type each password carefully and try again.</span>");
            return false;
        }

        var data = {
            "esig_slv_password": $("#esig-slv-password").val(),
            "esig_slv_confirm_password": $("#esig-slv-confirm-password").val(),
            "invite_hash": $("#esig-invite-hash").val(),
            "checksum": $("#esig-document-checksum").val()
        };

        // pass to server through Ajax 
        $.post(esigAjax.ajaxurl + "&className=Esig_Slv_Dashboard&method=slv_set_password", data).done(function (response) {

            if (response == "done") {
                window.location.reload();
            }
            else {
                $("#esig-set-error").show().html(response);
                document.getElementById("esig-slv-confirm-password").style.borderColor = "red";
                document.getElementById("esig-slv-password").style.borderColor = "red";
                document.getElementById("access-error-textt").className = 'text-error';
            }
        });

    });

    // password reset popups 
    $("#forget_access_password").click(function () {

        $("#slv-login-form").hide();
        $("#reset-password-popup").show();
        // hide login form 
    });

    // go back button here 
    $("#slv-go-back").click(function () {

        $("#slv-login-form").show();
        $("#reset-password-popup").hide();
        // hide login form 
    });

    // adding onfocus out event in reset password email input field. 
    wpEsig("#esig-slv-reset-address").on("focusout",function(){
        let resetPasswordEmailAddress = wpEsig("#esig-slv-reset-address").value();
        if (esig_validation.is_email(resetPasswordEmailAddress)) {
            wpEsig("#esig-slv-reset-address").removeValidationMsg(true);
            return false;
        }
        else if (!esig_validation.is_email(resetPasswordEmailAddress)) {
            wpEsig("#esig-slv-reset-address").insertAfterValidationMsg("Invalid e-mail address. Please input a valid e-mail address", true);
            return false;
        }
    });

    // Re-setting password 
    $("#esig-slv-reset-password").click(function () {

        let resetPasswordEmailAddress = wpEsig("#esig-slv-reset-address").value();

        if (!esig_validation.is_email(resetPasswordEmailAddress)) {
            wpEsig("#esig-slv-reset-address").insertAfterValidationMsg("Invalid e-mail address. Please input a valid e-mail address", true);
            return false;
        }

        let data = {
            "esig_slv_reset_address": resetPasswordEmailAddress,
            "invite_hash": $("#esig-invite-hash").val(),
            "checksum": $("#esig-document-checksum").val()
        };

        // pass to server through Ajax 
        $.post(esigAjax.ajaxurl + "&className=Esig_Slv_Dashboard&method=slv_reset_password", data).done(function (response) {

            if (response == "done") {
                $("#reset-password-popup").hide();
                $("#slv_reset_confirmation").show();
            }
            else {

                //$("#esig-confirm-error").show().html(response);
                wpEsig("#esig-confirm-error").show();
                wpEsig("#esig-confirm-error").html("<span class='esig-icon-esig-alert'></span><span class='error-msg' id='error-access-code'>E-mail address is not correct</span>");

            }
        });

    });


})(jQuery);
