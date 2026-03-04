/**
 * document ready
 */
(function ($) {
    $(document).ready(function () {

        // mail preview iframe height adjustment
        if($('.inside .mailPreview').length){
            $('.inside .mailPreview').on('load', function() {
                try {
                    var iframe = $(this)[0]; // DOM-елемент
                    var contentHeight = iframe.contentWindow.document.body.scrollHeight;
                    $(this).height(contentHeight);
                } catch (e) {
                    console.warn('Не вдалося отримати висоту iframe (можливо, інший домен).');
                }
            });
        }

        // remove yoast settings from profile page
        if($('body').hasClass('profile-php')){
            $('#profile-page .yoast.yoast-settings').remove();
        }

        // redirect rules code toggle
        if($('.redirect-rules-code-option').length){
            $('.redirect-rules-code-option').on('click', function(){
                $('.redirect-rules-code-option').removeClass('active');
                $(this).addClass('active');
            });
        }

        // redirect rules
        if($('.redirect-rules-form').length){
            const homeUrl = $('.redirect-rules-form').data('home-url');

            // update open-link button href on input change
            $('.redirect-rules-form input[type="url"]').on('input change', function(){
                const val = $(this).val().trim();
                const $link = $(this).siblings('.redirect-rules-open-link');
                if(val){
                    $link.attr('href', val).removeClass('hidden');
                } else {
                    $link.addClass('hidden');
                }
            });

            // auto-fill domain on focus
            if(homeUrl){
                $('.redirect-rules-form input[type="url"]').on('focus', function(){
                    if(!$(this).val()){
                        $(this).val(homeUrl).trigger('input');
                    }
                });
            }

            // auto-fill title to prevent wp-post-new-reload loop
            if($('#title').length && !$('#title').val()){
                $('#title').val('redirect');
            }
        }

    });
})(jQuery);
