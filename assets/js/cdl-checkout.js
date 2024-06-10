jQuery(document).ready(function ($) {

    let cdl_checkout_submit = false;

    $( '#cdl-checkout-wc-form' ).hide();


    $("#cdl-checkout-payment-button").click(function () {
        window.location.reload();
    });

    $('#cdl-checkout-payment-button').hide();
    $('#cdl-checkout-cancel-payment-button').hide();

    function signTransaction(transaction) {
        return $.ajax({
            url: cdlCheckoutData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'sign_transaction',
                nonce: cdlCheckoutData.signTransactionNonce,
                transaction: transaction
            }
        });
    }

    function generateUniqueSessionId(length) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        return result;
    }

    window.openCheckout = function () {

        if ( cdl_checkout_submit ) {
            cdl_checkout_submit = false;
            return true;
        }

        const transaction = {
            totalAmount: cdlCheckoutData.totalAmount,
            customerEmail: cdlCheckoutData.customerEmail,
            customerPhone: cdlCheckoutData.customerPhone,
            sessionId: generateUniqueSessionId(15),
            products: cdlCheckoutData.products
        };

        signTransaction(transaction).done(function (signature) {
            console.log(transaction)
            console.log(cdlCheckoutData.publicKey)
            const config = {
                publicKey: cdlCheckoutData.publicKey,
                signature: signature,
                transaction: transaction,
                isLive: cdlCheckoutData.isLive,
                onSuccess: function () {
                    window.location.href = cdlCheckoutData.returnUrl;
                },
                onClose: function () {
                    $('#cdl-checkout-payment-button').show();
                    $('#cdl-checkout-cancel-payment-button').show();
                    console.log('User closed checkout widget.');
                },
                onPopup: function (response) {
                    $("body").unblock()
                    console.log(JSON.stringify(response));

                    // Save checkoutTransactionId to order
                    $.ajax({
                        url: cdlCheckoutData.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'save_checkout_transaction_id',
                            order_id: cdlCheckoutData.orderId,
                            checkoutTransactionId: response.checkoutTransactionId
                        },
                        success: function (res) {
                            console.log('Transaction ID saved:', res);
                        },
                        error: function (err) {
                            console.error('Failed to save transaction ID:', err);
                        }
                    });
                }
            };
            const connect = new Connect(config);
            connect.setup();
            connect.open();
        });
    };

    openCheckout()
});
