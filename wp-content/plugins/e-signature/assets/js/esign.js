

var esign = {
    setCookie: function (cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires;
    },
    getCookie: function (name) {
        var pattern = RegExp(name + "=.[^;]*")
        matched = document.cookie.match(pattern)
        if (matched) {
            var cookie = matched[0].split('=')
            return cookie[1]
        }
        return false
    },
    unsetCookie: function (name) {
        var d = new Date();
        d.setTime(d.getTime() - (5000 * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + +"; " + expires;
    },
    is_slv_active: function () {
        if (typeof esig_slv === 'undefined') {
            return false;
        } else {
            return true;
        }
    },
    is_valid_email: function (email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

        if (re.test(email))
        {
            return true;
        } else {
            return false;
        }
    },
    isValidNumber: function (number) {

        if (isNaN(number)) {
            return false;
        }
        return true;
    },
    isCCNumber: function (name) {
        var ccName = name.split(" ");
        for (i = 0; i < ccName.length; i++) {
            if(this.isValidNumber(ccName[i])){
                return true;
            }
        }
    },
    isEmpty: function (str) {

        return (!str || /^\s*$/.test(str));
    },
    isFullName: function (value) {

        value = value.toLowerCase();
        // check for double consecutive dots.
       // if (!/^(?!\.)(?!.*\.$)(?!.*?\.\.)/.test(value)) return false;
        // check for double spaces.
        if (/\s{2}/.test(value)) return false;
        // for japanese character checking
        const japanesRegex = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]|[\u2605-\u2606]|[\u2190-\u2195]|\u203B/g;
        if (japanesRegex.test(value)) {
            return true;
        }
        
      

        const regex = /^[A-Z][a-z’]+(\s[A-Z][a-z’]+)*$/;
        if (regex.test(value)) {
            return true;
        }
        

        // Emoji validation
        const regexExpEmoji = /(\u00a9|\u00ae|[\u2000-\u3300]|\ud83c[\ud000-\udfff]|\ud83d[\ud000-\udfff]|\ud83e[\ud000-\udfff])/gi;
        if (regexExpEmoji.test(value)) return false ;
        // full name validation .
        const regexp = new RegExp(/^[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]([-']?[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]+)*( [^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]([-']?[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]+)*)+$/i);
        return regexp.test(value);
    },
    isUrl: function (value) {
        return  /^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i.test(value);
    },
    tbResize: function () {
        var width = jQuery(window).width();
        jQuery("#TB_ajaxContent, #TB_iframeContent").css({width: 'auto'});

        jQuery("#TB_window").css({height: 500});
    },
    tbSize: function (height) {

        jQuery("#TB_ajaxContent, #TB_iframeContent").css({width: 'auto'});
        jQuery("#TB_window").css({height: height});

    },
    addSlashes: function (string) {
        return string.replace(/\\/g, '\\\\').
                replace(/\u0008/g, '\\b').
                replace(/\t/g, '\\t').
                replace(/\n/g, '\\n').
                replace(/\f/g, '\\f').
                replace(/\r/g, '\\r').
                replace(/'/g, '\\\'').
                replace(/"/g, '\\"');
    },
    validate_signers: function (division, signer_email_name, signer_fname_name) {

        var view_email = jQuery(division + " input[name='" + signer_email_name + "\\[\\]']").map(function () {
            return jQuery(this).val();
        });

        var view_fname = jQuery(division + " input[name='" + signer_fname_name + "\\[\\]']").map(function () {
            return jQuery(this).val();
        });

        var sorted_email = view_email.sort();
        // getting new array
        var exists = false;
        var blank = false;
        var blank_email = false;

        if (view_email.length === 0 || view_fname.length === 0) {
            jQuery(division + ' .esig-error-box').remove();
            jQuery(division).append('<span class="esig-error-box">*You must add at least one signer.</span>');
            return true;
        }

        // if blank signer name is input
        for (var i = 0; i < view_fname.length; i++) {



            if (view_fname[i] == undefined || view_fname[i] == '')
            {

                blank = true;
            }

            var re = /<(.*)>/;
            if (re.test(view_fname[i]))
            {
                blank = true;
            }


            if (!this.isFullName(view_fname[i])) {
                    blank = true;
            }
           

            if (blank)
            {
                jQuery(division + ' .esig-error-box').remove();
                jQuery(division).append('<span class="esig-error-box">*A full name including your first and last name is required to sign this document. Spaces after last name will prevent submission.</span>');
                return true;
            }
        }
        // if blank email address is input
        for (var i = 0; i < view_email.length; i++) {

            if (view_email[i] == undefined || view_email[i] == '')
            {

                blank_email = true;
            }


            if (!this.is_valid_email(view_email[i]))
            {
                blank_email = true;
            }
            if (blank_email)
            {
                // remove previous error msg
                jQuery(division + ' .esig-error-box').remove();
                // add new error msg
                jQuery(division).append('<span class="esig-error-box">*You must fill email address.</span>');
                return true;
            }
        }


        for (var i = 0; i < view_email.length - 1; i++) {

            if (sorted_email[i + 1].toLowerCase() == sorted_email[i].toLowerCase())
            {
                exists = true;
            }
        }

        if (exists)
        {

            jQuery(division + ' .esig-error-box').remove();

            jQuery(division).append('<span class="esig-error-box">*You can not use duplicate email address.</span>');

            return true;
        } else
        {
            jQuery(division + ' .esig-error-box').remove();
            return false;
        }

    },
    isValidBase64Image: function (base64Image){
        const regex = /^data:image\/(?:png)(?:;charset=utf-8)?;base64,(?:[A-Za-z0-9]|[+/])+={0,2}/;
        return base64Image && regex.test(base64Image);
    },
    isIphone: function () {
        /* Detect mobile browser */
        if (navigator.userAgent.match(/iPad/i)
                || navigator.userAgent.match(/iPhone/i)) {
            return true;
        } else {
            return false;
        }
    },
    ccValidate: function (esigSelection, esigError) {

        var view_email = jQuery(esigSelection + " input[name='cc_recipient_emails\\[\\]']").map(function () {
            return jQuery(this).val();
        }).get();

        var view_fname = jQuery(esigSelection + " input[name='cc_recipient_fnames\\[\\]']").map(function () {
            return jQuery(this).val();
        }).get();

        var sorted_email = view_email.sort();

        // getting new array
        var exists = false;
        var blank = false;
        var blank_email = false;
        // if blank signer name is input
        for (var i = 0; i < view_fname.length; i++) {

            if (view_fname[i] == undefined || view_fname[i] == '')
            {

                blank = true;
            }

            var re = /<(.*)>/ ;
            if (re.test(view_fname[i]))
            {
                blank = true;
            }

            // cc first name and last name number validation goes here
            if (this.isCCNumber(view_fname[i])) {
                blank = true;
            }

            // var regexp = new RegExp(/^[a-z]([-']?[a-z]+)*( [a-z]([-']?[a-z]+)*)+$/i);
            if (!this.isFullName(view_fname[i])) {
                    blank = true;
            }
           
            if (blank)
            {

                jQuery('.esig-error-box').remove();
                jQuery(esigError).append('<span class="esig-error-box">*A Full name including your first and last name is required. (number and special character is not valid)</span>').show();
                return true;
            }
        }
        // if blank email address is input
        for (var i = 0; i < view_email.length; i++) {

            if (view_email[i] == undefined || view_email[i] == '')
            {

                blank_email = true;
            }


            if (!this.is_valid_email(view_email[i]))
            {
                blank_email = true;
            }
            if (blank_email)
            {
                // remove previous error msg
                jQuery('.esig-error-box').remove();
                // add new error msg
                jQuery(esigError).append('<span class="esig-error-box">*You must fill CC email address.</span>').show();
                return true;
            }
        }


        for (var i = 0; i < view_email.length - 1; i++) {

            if (sorted_email[i + 1].toLowerCase() == sorted_email[i].toLowerCase())
            {
                exists = true;
            }
        }

        if (exists)
        {

            jQuery('.esig-error-box').remove();

            jQuery(esigError).append('<span class="esig-error-box"> *You can not use CC duplicate email address.</span>').show();

            return true;
        } else
        {

            jQuery('.esig-error-box').remove();
            return false;
        }
    }

};

/**
 * This method will be used to make a server http request with ajax call.
 *
 * @param {string} url
 * @param {string} method
 * @param {CallableFunction} callBack
 * @param {object} postData
 */
function esigRemoteRequest(url, method, callBack, postData = false) {
    // variable xmlhttp object.
    let xmlhttp;

    if (window.XMLHttpRequest) {
        // code for modern browsers
        xmlhttp = new XMLHttpRequest();

    } else {
        // code for old IE browsers
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }

    // concat wordpress ajax url with provided url
    let requestUrl =  ajaxurl + "?action=" + url ;

    // convert method to uppercase letter.
    method = method.toUpperCase();

    // Open xmlhttp request
    xmlhttp.open(method, requestUrl , true);

    //Make get/post form submission from array
    if ((postData instanceof FormData === false) && method === "POST" && typeof postData === 'object' && postData !== null) {
        // If method is post set  content type
        xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

        // Makeing url parameter with given object if that is not a FormData instance
        postData = Object.keys(postData).map(function (key) {
            return encodeURIComponent(key) + "=" + encodeURIComponent(postData[key]);
        }).join('&');
    }


    // get server response when document is ready  after request
    xmlhttp.onreadystatechange = function () {

        if (this.readyState === 4 && this.status === 200) {
            // Typical action to be performed when the document is ready:
            // return the response result to callback functions.
            callBack(xmlhttp);
        }
    };

    // xmlhttp not ready return false
    if (xmlhttp.readyState === 4) return false;

    // send xml request to server .
    xmlhttp.send(postData);

}


/**
 * return a document selector  dom object .
 * @param {string} query
 */

function esig(query)
{
    return document.querySelector(query);
}

function esigHasWhiteSpace(string) {
    // check for white space
    return /\s/g.test(string);
}

function esigValidateInputs(fieldsList,msgObj)
{
    var ret  = false ;

    for (i = 0; i < fieldsList.length; i++)
    {
        if (!esig_validation.is_string(fieldsList[i].value))
        {
            wpEsig("#" + fieldsList[i].id).insertAfterValidationMsg(msgObj[fieldsList[i].id], true);
            ret= true;
        }
    }

    return ret;
}

function esigAlertBox(message)
{
    //Create the element using the createElement method.
    var myDiv = document.createElement("div");

    //Set its unique ID.
    myDiv.id = 'esigAlertBox';

    //Add your content to the DIV
    myDiv.innerHTML = '<span id="esig-alert-close" class="closebtn" onclick="esigAlertClose();">&times;</span>' + message;

    //Finally, append the element to the HTML body
    document.body.appendChild(myDiv);

}

function esigAlertClose()
{
    esig("#esig-alert-close").parentElement.remove();
}

/**
 *  @deprecated 1.8.1
 */
/*
function esigCheckJapaneseCharacters(inputValue)
{


   var japanesRegex = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]|[\u2605-\u2606]|[\u2190-\u2195]|\u203B/g;
   if(japanesRegex.test(inputValue)) {
    return "found";
    }
    else {
      return "Not found";
    }


}

function esigCheckapostrophe(inputValue)
{


   var apostropheRegex = /^[A-Z][a-z’]+(\s[A-Z][a-z’]+)*$/;
   if(apostropheRegex.test(inputValue)) {
    return "found";
    }
    else {
      return "Not found";
    }


} */

