<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_CdlCheckout_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'cdl_checkout';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_my_custom_gateway_settings', [] );
        $this->gateway = new WC_Gateway_CdlCheckout();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        $script_url = plugins_url( '/assets/js/checkout.js', WC_CDL_CHECKOUT_MAIN_FILE );

        wp_register_script(
            'cdl_checkout-blocks-integration',
            $script_url,
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'cdl_checkout-blocks-integration');
            
        }
        return [ 'cdl_checkout-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon'         => plugin_dir_url( __DIR__ ) . 'assets/images/cdl-checkout-wc.jpg',
        ];
    }

}
?>