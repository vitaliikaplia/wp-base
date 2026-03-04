/**
 * document ready
 */
(function ($) {
    $(document).ready(function () {

        /** Cookie Pop-Up */
        if(!$.cookie("user-cookies-accepted") && $('.cookiePopupWrapper').length){
            setTimeout(function(){
                $('.cookiePopupWrapper').addClass('show');
            }, 3000);
            $('.cookiePopup .accept').click(function(){
                $.cookie("user-cookies-accepted", true, {
                    expires: 365,
                    path: "/",
                    secure: false
                });
                $('.cookiePopupWrapper').removeClass('show');
            });
        }

    });
})(jQuery);
