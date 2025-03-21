(function ($) {

    
    // this is common js file . 
    $('.esig-pro-pack h3 a,.esig-pro-pack .esig-dismiss').on('click', function (e) {
        e.preventDefault();
        $('.esig-pro-pack').toggleClass('open');
        $('.esig-pro-pack p').slideToggle('fast');
        $('.esig-pro-pack h3 span').fadeToggle('fast');
        $('.esig-dismiss').slideToggle('fast');
    });

    $('.esig-add-on-actions .esig-add-on-enabled a').hover(function () {
        $(this).text($(this).attr('data-text-disable'));
    }, function () {

        $(this).text($(this).attr('data-text-enabled'));

    });

    $('.esig-add-on-actions .esig-add-on-disabled a').hover(function () {
        $(this).text($(this).attr('data-text-enable'));
    }, function () {
        $(this).text($(this).attr('data-text-disabled'));
    });

    //progress bar start here 
    $("#esig-install-alladdons").click(function (e) {
        //e.preventDefault();

        if (e.target.getAttribute('install-permission') =="no") return false;

        var overlay = $('<div class="page-loader-overlay"></div>').appendTo('body');

        $(overlay).show();

        $(".esig-addon-devbox").show();

        $.fx.interval = 3000;

        $(".progress").animate({ width: "100%" }, {
            duration: 90000,
            step: function (now, fx) {
                if (fx.prop == 'width') {
                    var countup = Math.round((now / 100) * 100) + '%';
                    $(".countup").html(countup);
                }
            },

            start: function () { $(this).before("<div class='load'><p>Installing... Please wait</p></div>"); },

            complete: function () { $(this).after("<div class='logo'></div>"); },

            done: function () { $("div.load").html("<p>Successfully Installed</p>"); }

        });

    }); 

    $("#esig-update-alladdons").click(function (e) {
        //e.preventDefault();

        if (e.target.getAttribute('install-permission') =="no") return false;

        var overlay = $('<div class="page-loader-overlay"></div>').appendTo('body');

        $(overlay).show();

        $(".esig-addon-devbox").show();

        $.fx.interval = 3000;

        $(".progress").animate({ width: "100%" }, {
            duration: 90000,
            step: function (now, fx) {
                if (fx.prop == 'width') {
                    var countup = Math.round((now / 100) * 100) + '%';
                    $(".countup").html(countup);
                }
            },

            start: function () { $(this).before("<div class='load'><p>Updating... Please wait</p></div>"); },

            complete: function () { $(this).after("<div class='logo'></div>"); },

            done: function () { $("div.load").html("<p>Successfully Updated</p>"); }

        });

    });
	

	  // hiding tooltip when cancel delete 
	  $("body").on("click","#esig-secondary-btn",function() {
		    $(".ui-tooltip-content").parents('div').hide();
	  });
	  
	  // tooltip
	  $(".esig-add-ons-wrapper").tooltip({ position: {
	        my: "right-110 bottom-10",
	        at: "right center",
	        using: function (position, feedback) {
	            $(this).css(position);
	            $("<div>")
	            .addClass("esign-arrow")
	            .addClass(feedback.vertical)
	            .addClass(feedback.horizontal)
	            .appendTo(this);
	        } 
	    }
	});

})(jQuery);
