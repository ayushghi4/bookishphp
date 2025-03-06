/*=============================================================
    Authour URI: www.binarytheme.com
    License: Commons Attribution 3.0

    http://creativecommons.org/licenses/by/3.0/

    100% Free To use For Personal And Commercial Use.
    IN EXCHANGE JUST GIVE US CREDITS AND TELL YOUR FRIENDS ABOUT US
   
    ========================================================  */

(function ($) {
    "use strict";
    var mainApp = {
        slide_fun: function () {
            $('#carousel-example').carousel({
                interval:3000 // THIS TIME IS IN MILLI SECONDS
            })
        },
       
        custom_fun:function() {
            /*====================================
             WRITE YOUR   SCRIPTS  BELOW
            ======================================*/
        },
    }
   
    $(document).ready(function () {
        mainApp.slide_fun();
        mainApp.custom_fun();
        
        // Enable Bootstrap dropdowns
        $('.dropdown-toggle').dropdown();
        
        // Add hover functionality for desktop
        if(window.innerWidth > 768) {
            $('.dropdown').hover(
                function() { 
                    $(this).find('.dropdown-menu').stop(true, true).delay(100).fadeIn(200);
                },
                function() { 
                    $(this).find('.dropdown-menu').stop(true, true).delay(100).fadeOut(200);
                }
            );
        }
        
        // Handle mobile menu collapse
        $('.navbar-toggle').click(function() {
            $('.navbar-collapse').toggleClass('in');
        });
        
        // Close mobile menu when clicking outside
        $(document).click(function(e) {
            if(!$(e.target).closest('.navbar').length) {
                $('.navbar-collapse').collapse('hide');
            }
        });
    });
}(jQuery));
