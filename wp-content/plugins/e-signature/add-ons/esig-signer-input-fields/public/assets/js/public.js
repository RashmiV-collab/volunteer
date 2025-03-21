(function ($) {
    "use strict";

    $(function () {



    });

    $('#sifreadonly').removeClass('esig-sif-textfield');
    $('#sifreadonly').addClass('esig-sifreadonly');

    $(document).ready(function () {
        $("textarea").on("keypress focus",function (event) {
           
            if (event.which == 13) {
                var s = $(this).val();
                $(this).val(s + "\n");  //\t for tab
                event.preventDefault();
            }
            
        });
        

        
        $(".esig-sif-datepicker").on("change", function() {  
            
            var dateRegex = /^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}$/;

            if (dateRegex.test($(this).val())) {
                return false;
            }else{

                if($(this).val().length < 7){
                    alert('Invalid Date');
                    $(this).val(null);
                    return false;
                }
                
                const date = new Date($(this).val());        
                if(date == 'Invalid Date'){
                    alert('Invalid Date');
                    $(this).val(null);
                }
            }        
            

        });
       


    });
    

} (jQuery));

