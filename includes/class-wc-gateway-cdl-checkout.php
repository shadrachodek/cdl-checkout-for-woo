<?php
class WC_Gateway_CdlCheckout extends WC_Payment_Gateway {

    public $testmode;
    public $autocomplete_order;
    public $payment_page;
    public $public_key;
    public $secret_key;
    public $enabled;
    public $msg;
    public $remove_cancel_order_button;

    public function __construct() {
        $this->id                 = 'cdl_checkout';
        $this->has_fields = false;
        $this->method_title       = __('CDL Checkout Payment Gateway', 'cdl-checkout');
        $this->method_description = __('Make payment using your debit and credit cards', 'cdl-checkout');

        $this->supports = array(
            'products',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->enabled            = $this->get_option('enabled');
        $this->testmode           = $this->get_option('testmode') === 'yes' ? true : false;
        $this->autocomplete_order = $this->get_option('autocomplete_order') === 'yes' ? true : false;
        $this->remove_cancel_order_button = $this->get_option( 'remove_cancel_order_button' ) === 'yes' ? true : false;

        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');

        // Hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action('admin_enqueue_scripts', array( $this, 'admin_scripts' ));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Webhook listener/API hook.
        add_action( 'woocommerce_api_cdl_checkout_wc_payment_webhook', array( $this, 'process_webhooks' ) );
    }

    /**
     * Display CDL Checkout payment icon.
     */
    public function get_icon() {

        $icon = '<img src="' . WC_HTTPS::force_https_url( plugins_url( 'assets/images/cdl-checkout-wc.jpg', WC_CDL_CHECKOUT_MAIN_FILE ) ) . '" alt="Direct Checkout Payment Options" />';


        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );

    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'cdl-checkout'),
                'type' => 'checkbox',
                'label' => __('Enable CDL Checkout', 'cdl-checkout'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'cdl-checkout'),
                'type' => 'text',
                'description' => __('This controls the payment method title which the user sees during checkout.', 'cdl-checkout'),
                'default' => __('CDL Checkout', 'cdl-checkout'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'cdl-checkout'),
                'type' => 'textarea',
                'description' => __('This controls the payment method description which the user sees during checkout.', 'cdl-checkout'),
                'default' => __('Pay via CDL Checkout', 'cdl-checkout'),
            ),
            'testmode' => array(
                'title' => __('Test mode', 'cdl-checkout'),
                'label' => __('Enable Test Mode', 'cdl-checkout'),
                'type' => 'checkbox',
                'description' => __('Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your CDL Checkout account uncheck this.', 'cdl-checkout'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'public_key' => array(
                'title' => __('Public Key', 'cdl-checkout'),
                'type' => 'text',
                'description' => __('Enter your Public Key here.', 'cdl-checkout'),
                'default' => '',
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'cdl-checkout'),
                'type' => 'password',
                'description' => __('Enter your Secret Key here.', 'cdl-checkout'),
                'default' => '',
            ),
            'autocomplete_order' => array(
                'title' => __('Autocomplete Order After Payment', 'cdl-checkout'),
                'label' => __('Autocomplete Order', 'cdl-checkout'),
                'type' => 'checkbox',
                'description' => __('If enabled, the order will be marked as complete after successful payment', 'cdl-checkout'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'remove_cancel_order_button'       => array(
                'title'       => __( 'Remove Cancel Order & Restore Cart Button', 'cdl-checkout' ),
                'label'       => __( 'Remove the cancel order & restore cart button on the pay for order page', 'cdl-checkout' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
        );
    }

    /**
     * Check if  CDL Checkout Payment is enabled.
     *
     * @return bool
     */
    public function is_available() {
        if ('yes' === $this->enabled) {
            if (!($this->public_key && $this->secret_key)) {
                $this->msg = __('CDL Checkout Payment Gateway Disabled: Missing API keys.', 'cdl-checkout');
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Check if CDL Checkout merchant details is filled.
     */
    public function admin_notices() {

        if ( $this->enabled == 'no' ) {
            return;
        }

        // Check required fields.
        if ( ! ( $this->public_key && $this->secret_key ) ) {
            echo '<div class="error"><p>' . sprintf( __( 'Please enter your CDL Checkout merchant details <a href="%s">here</a> to be able to use the CDL Checkout WooCommerce plugin.', 'cdl-checkout' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cdl_checkout' ) ) . '</p></div>';
            return;
        }

    }

    /**
     * Admin Panel Options.
     */
    public function admin_options() {

        ?>
        <h2><?php _e( 'CDL Checkout', 'cdl-checkout' ); ?>
            <?php
            if ( function_exists( 'wc_back_link' ) ) {
                wc_back_link( __( 'Return to payments', 'cdl-checkout' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
            }
            ?>
        </h2>
        <h4>
            <strong>
                <?php
                $webhook_url = untrailingslashit( WC()->api_request_url( 'Cdl_Checkout_WC_Payment_Webhook' ) );
                printf( __( 'Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below<span style="color: red; display: flex"><pre><code id="webhook-url">%2$s</code></pre><button role="button" id="copy-webhook-url" style="cursor: pointer; margin-left: 15px; padding: 0  8px">Copy</button></span>', 'cdl-checkout' ), 'https://www.creditdirect.ng', esc_html( $webhook_url ) ); ?>
            </strong>
        </h4>

        <?php

        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';

    }

    public function payment_fields() {
        if ( $this->description ) {
            echo wpautop( wptexturize( $this->description ) );
        }

        if ( ! is_ssl() ) {
            return;
        }
    }

    public function receipt_page($order_id) {
        $order = wc_get_order( $order_id );

        echo '<div id="cdl-checkout-wc-form">';

        echo '<p>' . __('Thank you for your order, please click the button below to pay with CDL Checkout.', 'cdl-checkout') . '</p>';

        echo '<div id="cdl_checkout_form"><form id="order_review" method="post" action="' . WC()->api_request_url( 'WC_Gateway_CdlCheckout' ) . '"></form><button class="button" id="cdl-checkout-payment-button">' . __( 'Pay Now', 'cdl-checkout' ) . '</button>';

        if ( ! $this->remove_cancel_order_button ) {
            echo '  <a class="button cancel" id="cdl-checkout-cancel-payment-button" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'cdl-checkout' ) . '</a></div>';
        }

        echo '</div>';

    }

    /**
     * Enqueue the payment scripts
     */
    public function payment_scripts() {
        if ( isset( $_GET['pay_for_order'] ) || ! is_checkout_pay_page() ) {
            return;
        }

        if ( $this->enabled === 'no' ) {
            return;
        }

        wp_enqueue_script( 'jquery' );

        wp_enqueue_script('cdl-checkout-js', 'https://checkout.creditdirect.ng/bnpl/checkout.min.js', array('jquery'), WC_CDL_CHECKOUT_VERSION, true);

        wp_enqueue_script( 'wc-cdl-checkout-js', plugins_url( 'assets/js/cdl-checkout.js', WC_CDL_CHECKOUT_MAIN_FILE ), array( 'jquery', 'cdl-checkout-js' ), WC_CDL_CHECKOUT_VERSION, true );



        $order_key = urldecode( $_GET['key'] );
        $order_id  = absint( get_query_var( 'order-pay' ) );

        $order = wc_get_order($order_id);

        if ($order) {
            wp_localize_script('wc-cdl-checkout-js', 'cdlCheckoutData', [
                'orderId' => $order_id,
                'publicKey' => $this->public_key,
                'isLive' => !$this->testmode,
                'sessionId' => $this->generate_unique_session_id(15),
                'totalAmount' => $order->get_total(),
                'customerEmail' => $order->get_billing_email(),
                'customerPhone' => $order->get_billing_phone(),
                'products' => $this->get_cart_items(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'signTransactionNonce' => wp_create_nonce('sign_transaction'),
                'returnUrl' => $this->get_return_url($order)
            ]);
        }
    }

    /**
     * Enqueue admin scripts.
     */
    public function admin_scripts($hook) {

        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }

        wp_enqueue_script(
                'wp-cdl-checkout-admin-script',
                plugins_url( 'assets/js/cdl-checkout-admin.js', WC_CDL_CHECKOUT_MAIN_FILE ),
                array('jquery'),
                WC_CDL_CHECKOUT_VERSION,
                true
        );


    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $order->add_order_note( __('Awaiting CDL Checkout payment', 'cdl-checkout'));


        // Redirect to the thank you page
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url( true ),
        );
    }


    public function process_webhooks() {

        $post_data = file_get_contents('php://input');
        $response = json_decode($post_data, true);

        $logger = wc_get_logger();
        $log_context = array('source' => 'cdl_checkout_webhook');
        $timestamp = date("Y-m-d H:i:s"); // Current time

        $logger->info("Webhook received at: $timestamp", $log_context);

        $checkoutTransactionId = sanitize_text_field($response['checkoutTransactionId']);

        // query order by checkout transaction Id
        $query = new WC_Order_Query(array(
            'meta_key'    => '_checkout_transaction_id',
            'meta_value'  => $checkoutTransactionId,
            'limit'       => 1,
            'return'      => 'ids',
        ));

        $orders = $query->get_orders();

        if (!empty($orders)) {
            $order_id = $orders[0];
            $order = wc_get_order($order_id);

            if (isset($response['eventType']) && $response['eventType'] === 'Checkout_Customer_Payment_Completed') {
                if ($order) {
                    $order->add_order_note('Customer deposit received through CDL Checkout.');

                    $logger->info("Checkout_Customer_Payment_Completed webhook received at: $timestamp", $log_context);


                    // Respond back to acknowledge receipt of the webhook
                    header('HTTP/1.1 200 OK');
                    echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully 1']);
                    exit;
                }

            }

            if (isset($response['eventType']) && $response['eventType'] === 'Checkout_Merchant_Payment_Completed') {

                if ($order) {
                    $order->payment_complete();
                    $order->add_order_note('Payment received through CDL Checkout.');

                    // Reduce stock levels
                    wc_reduce_stock_levels($order_id);

                    // Remove cart
                    WC()->cart->empty_cart($order_id);

                    if ( $this->is_autocomplete_order_enabled( $order ) ) {
                        $order->update_status( 'completed' );
                    }

                    $logger->info("Checkout_Merchant_Payment_Completed webhook received at: $timestamp", $log_context);

                    // Respond back to acknowledge receipt of the webhook
                    header('HTTP/1.1 200 OK');
                    echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
                    exit;
                }

            }


        }

        $logger->error("Invalid data received at: $timestamp", $log_context);

        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        exit;
    }

    protected function is_autocomplete_order_enabled( $order ) {
        $autocomplete_order = false;

        $payment_method = $order->get_payment_method();

        $cdl_checkout_settings = get_option('woocommerce_' . $payment_method . '_settings');

        if ( isset( $cdl_checkout_settings['autocomplete_order'] ) && 'yes' === $cdl_checkout_settings['autocomplete_order'] ) {
            $autocomplete_order = true;
        }

        return $autocomplete_order;
    }

    private function get_cart_items() {
        $items = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $items[] = array(
                'productName' => $product->get_name(),
                'productAmount' => $cart_item['line_total'],
                'productId' => $product->get_id()
            );
        }
        return $items;
    }

    private function generate_unique_session_id($length) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $result;
    }

    // signed a transaction request
    private function sign_transaction($transaction, $private_key) {
        $message = $transaction['sessionId'] . $transaction['customerEmail'] . $transaction['totalAmount'];
        return hash_hmac('sha256', $message, $private_key);
    }

    // logo url
    public function get_logo_url()
    {

        $url = WC_HTTPS::force_https_url(plugins_url('assets/images/cdl-checkout-wc.jpg', WC_CDL_CHECKOUT_MAIN_FILE));

        return apply_filters('wc_cdl_checkout_gateway_icon_url', $url, $this->id);
    }
  
}
