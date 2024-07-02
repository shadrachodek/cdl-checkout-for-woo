<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Gateway_CdlCheckout_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'cdl_checkout';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_my_custom_gateway_settings', [] );
        $this->gateway = new WC_Gateway_CdlCheckout();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        $script_asset_path = plugins_url( '/assets/js/block/blocks.asset.php', WC_CDL_CHECKOUT_MAIN_FILE );
        $script_asset      = file_exists( $script_asset_path )
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version'      => WC_CDL_CHECKOUT_VERSION,
            );

        $script_url = plugins_url( '/assets/js/block/blocks.js', WC_CDL_CHECKOUT_MAIN_FILE );

        wp_register_script(
            'cdl_checkout-blocks-integration',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'cdl_checkout-blocks-integration');
            
        }
        return [ 'cdl_checkout-blocks-integration' ];
    }

    public function get_payment_method_data() {
        $payment_gateways_class = WC()->payment_gateways();
        $payment_gateways       = $payment_gateways_class->payment_gateways();
        $gateway                = $payment_gateways['cdl_checkout'];
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'logo_url'         => array( $payment_gateways['cdl_checkout']->get_logo_url() ),
        ];
    }

}