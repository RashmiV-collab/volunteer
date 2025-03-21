var esig_validation = {
    is_email: function (email_address) {
        var filter = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/;
        if (filter.test(email_address)) {
            return true;
        } else {
            return false;
        }
    },
    is_string: function (input_string) {
        
        // check string is not empty
        if (input_string === "") {
            return false;
        }
        // check string is not empty
        if (!input_string) {
            return false;
        }
        // check string is not undefined and not null
        if(typeof(input_string) === "undefined" && input_string === null)
        {
            return false;
        }

        // Regex to check valid HTML tag.
        const regexPattern = new RegExp("<(\"[^\"]*\"|'[^']*'|[^'\">])*>") ;
        // check string for html tags. 
        if (regexPattern.test(input_string))
        {
            // no html 
            return false;
        }

        return true;
    },
    is_fullName: function (value) {
        // check for double consecutive dots. 
       // if (!/^(?!\.)(?!.*\.$)(?!.*?\.\.)/.test(value)) return false;
       value = value.toLowerCase();
        // check for double spaces. 
        if (/\s{2}/.test(value)) return false;
        // Emoji validation 
        const regexExpEmoji = /(\u00a9|\u00ae|[\u2000-\u3300]|\ud83c[\ud000-\udfff]|\ud83d[\ud000-\udfff]|\ud83e[\ud000-\udfff])/gi;
        if (regexExpEmoji.test(value)) return false;
        // full name validation . 
        const regexp = new RegExp(/^[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]([-']?[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]+)*( [^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]([-']?[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]+)*)+$/i);
        return regexp.test(value);

    },
};


(function ($) {

    $.validator.addMethod("esiglegalname", function (value, element) {
        
        // check for double consecutive dots. 
        //if (!/^(?!\.)(?!.*\.$)(?!.*?\.\.)/.test(value)) return false;
        // check for double spaces/
        

        const japanesRegex = /[\u3000-\u303F]|[\u3040-\u309F]|[\u30A0-\u30FF]|[\uFF00-\uFFEF]|[\u4E00-\u9FAF]|[\u2605-\u2606]|[\u2190-\u2195]|\u203B/g; 
        
        if(japanesRegex.test(value)) {      
            return this.optional(element) || japanesRegex.test(value);
        }else{
            if (/\s{2}/.test(value)) return false;
            const apostropheRegex = /^[A-Z][a-z’]+(\s[A-Z][a-z’]+)*$/;
            if(apostropheRegex.test(value)) {
                return this.optional(element) || apostropheRegex.test(value);
            } 
            const regexExpEmoji = /(\u00a9|\u00ae|[\u2000-\u3300]|\ud83c[\ud000-\udfff]|\ud83d[\ud000-\udfff]|\ud83e[\ud000-\udfff])/gi;
            if (regexExpEmoji.test(value)) return false;

            const regexp = new RegExp(/^[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]([-']?[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]+)*( [^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]([-']?[^_!¡?÷?¿\/\\+=@#$%ˆ&*{}|~<>;:[\]]+)*)+$/i);
            return this.optional(element) || regexp.test(value);
        }

       
    }, "A full name including your first and last name is required to sign this document. Spaces after last name will prevent submission.");

})(jQuery);
