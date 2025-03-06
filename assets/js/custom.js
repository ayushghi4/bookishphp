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
            if ($('#carousel-example').length) {
                $('#carousel-example').carousel({
                    interval: 3000 // THIS TIME IS IN MILLI SECONDS
                });
            }
        },
        
        dataTable_fun: function () {
            if ($('#dataTables-example').length && $.fn.dataTable) {
                $('#dataTables-example').dataTable();
            }
        },
       
        custom_fun: function() {
            // Handle Read Now button clicks
            $('.read-now').on('click', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                
                // Show loading state
                $(this).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
                $(this).addClass('disabled');
                
                // Navigate to the read-book page
                window.location.href = href;
            });
        }
    };
   
    $(document).ready(function () {
        mainApp.slide_fun();
        mainApp.dataTable_fun();
        mainApp.custom_fun();
    });
}(jQuery));
