jQuery(function ($) {
    'use strict';

    /**
     * Object to handle CDL Checkout admin functions.
     */
    var wc_cdlcheckout_admin = {
        /**
         * Initialize.
         */
        init: function () {

            // Toggle API key settings.
            $(document.body).on('change', '#woocommerce_cdlcheckout_testmode', function () {
                var test_secret_key = $('#woocommerce_cdlcheckout_test_secret_key').parents('tr').eq(0),
                    test_public_key = $('#woocommerce_cdlcheckout_test_public_key').parents('tr').eq(0),
                    live_secret_key = $('#woocommerce_cdlcheckout_live_secret_key').parents('tr').eq(0),
                    live_public_key = $('#woocommerce_cdlcheckout_live_public_key').parents('tr').eq(0);

                if ($(this).is(':checked')) {
                    test_secret_key.show();
                    test_public_key.show();
                    live_secret_key.hide();
                    live_public_key.hide();
                } else {
                    test_secret_key.hide();
                    test_public_key.hide();
                    live_secret_key.show();
                    live_public_key.show();
                }
            });

            $('#woocommerce_cdlcheckout_testmode').change();

            $(".wc-cdlcheckout-payment-icons").select2({
                templateSelection: formatCdlCheckoutPaymentIconDisplay
            });
        }
    };

    function formatCdlCheckoutPaymentIconDisplay(payment_method) {
        return payment_method.text;
    }

    wc_cdlcheckout_admin.init();
});
