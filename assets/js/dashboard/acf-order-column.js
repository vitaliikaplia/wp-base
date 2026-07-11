(function ($) {
    'use strict';

    $(document).ready(function () {

        if ($('body').hasClass('acf-admin-page')) {

            // Click on order display to show input
            $(document).on('click', '.acf-order-display', function (e) {
                e.stopPropagation();
                var $wrapper = $(this).closest('.acf-order-value');
                var $display = $wrapper.find('.acf-order-display');
                var $input = $wrapper.find('.acf-order-input');

                $display.hide();
                $input.show().focus().select();
            });

            // Save on blur
            $(document).on('blur', '.acf-order-input', function () {
                saveOrder($(this));
            });

            // Save on Enter, cancel on Escape
            $(document).on('keydown', '.acf-order-input', function (e) {
                if (e.keyCode === 13) { // Enter
                    e.preventDefault();
                    $(this).blur();
                } else if (e.keyCode === 27) { // Escape
                    e.preventDefault();
                    cancelEdit($(this));
                }
            });

            // Prevent negative values
            $(document).on('input', '.acf-order-input', function () {
                var val = parseInt($(this).val(), 10);
                if (isNaN(val) || val < 0) {
                    $(this).val(0);
                }
            });

            function saveOrder($input) {
                var $wrapper = $input.closest('.acf-order-value');
                var $display = $wrapper.find('.acf-order-display');
                var postId = $wrapper.data('post-id');
                var newOrder = parseInt($input.val(), 10) || 0;
                var originalOrder = parseInt($display.text(), 10) || 0;

                // If value hasn't changed, just hide input
                if (newOrder === originalOrder) {
                    $input.hide();
                    $display.show();
                    return;
                }

                $wrapper.addClass('acf-order-saving');

                $.ajax({
                    url: acfOrderColumn.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'acf_update_menu_order',
                        nonce: acfOrderColumn.nonce,
                        post_id: postId,
                        order: newOrder
                    },
                    success: function (response) {
                        if (response.success) {
                            $display.text(response.data.order);
                        } else {
                            $input.val(originalOrder);
                            alert(response.data.message || 'Error saving order');
                        }
                    },
                    error: function () {
                        $input.val(originalOrder);
                        alert('Error saving order');
                    },
                    complete: function () {
                        $wrapper.removeClass('acf-order-saving');
                        $input.hide();
                        $display.show();
                    }
                });
            }

            function cancelEdit($input) {
                var $wrapper = $input.closest('.acf-order-value');
                var $display = $wrapper.find('.acf-order-display');
                var originalOrder = parseInt($display.text(), 10) || 0;

                $input.val(originalOrder);
                $input.hide();
                $display.show();
            }
        }

    });
})(jQuery);