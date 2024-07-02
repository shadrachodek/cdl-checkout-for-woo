<?php
/**
 * Plugin Name: CDL Checkout for WooCommerce
 * Plugin URI: https://www.creditdirect.ng
 * Author: Credit Direct
 * Author URI: https://www.creditdirect.ng
 * Description: WooCommerce payment gateway for CDL Checkout
 * Version: 1.4.2
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cdl-checkout
 * WC requires at least: 7.0
 * WC tested up to: 8.3
 * Domain Path: /languages
*/


use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\Notes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


define( 'WC_CDL_CHECKOUT_MAIN_FILE', __FILE__ );
define( 'WC_CDL_CHECKOUT_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

define( 'WC_CDL_CHECKOUT_VERSION', '1.4.2' );


/**
 * Initialize CDL Checkout for WooCommerce.
 */
function cdl_checkout_wc_init() {


    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'cdl_checkout_wc_missing_notice' );
        return;
    }

    add_action( 'admin_init', 'cdl_checkout_wc_testmode_notice' );

    require_once __DIR__ . '/includes/class-wc-gateway-cdl-checkout.php';



    add_filter( 'woocommerce_payment_gateways', 'cdl_checkout_wc_add_gateway', 99 );

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cdl_checkout_wc_plugin_action_links' );

}
add_action( 'plugins_loaded', 'cdl_checkout_wc_init', 99 );




/**
 * Add Settings link to the plugin entry in the plugins menu.
 *
 * @param array $links Plugin action links.
 *
 * @return array
 **/
function cdl_checkout_wc_plugin_action_links( $links ) {

    $settings_link = array(
        'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cdl_checkout' ) . '" title="' . __( 'View CDL Checkout WooCommerce Settings', 'cdl-checkout' ) . '">' . __( 'Settings', 'cdl-checkout' ) . '</a>',
    );

    return array_merge( $settings_link, $links );

}

function cdl_checkout_wc_add_gateway($gateways) {
  $gateways[] = 'WC_Gateway_CdlCheckout';
  return $gateways;
}

/**
 * Display a notice if WooCommerce is not installed
 */
function cdl_checkout_wc_missing_notice() {
    echo '<div class="error"><p><strong>' . sprintf( __( 'CDL Checkout requires WooCommerce to be installed and active. Click %s to install WooCommerce.', 'cdl-checkout' ), '<a href="' . admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=539' ) . '" class="thickbox open-plugin-details-modal">here</a>' ) . '</strong></p></div>';
}

/**
 * Display the test mode notice.
 **/
function cdl_checkout_wc_testmode_notice() {

    if ( ! class_exists( Notes::class ) ) {
        return;
    }

    if ( ! class_exists( WC_Data_Store::class ) ) {
        return;
    }

    if ( ! method_exists( Notes::class, 'get_note_by_name' ) ) {
        return;
    }

    $test_mode_note = Notes::get_note_by_name( 'cdl-checkout-test-mode' );

    if ( false !== $test_mode_note ) {
        return;
    }

    $cdl_checkout_settings = get_option( 'woocommerce_cdl_checkout_settings' );
    $test_mode         = $cdl_checkout_settings['testmode'] ?? '';

    if ( 'yes' !== $test_mode ) {
        Notes::delete_notes_with_name( 'cdl-checkout-test-mode' );

        return;
    }

    $note = new Note();
    $note->set_title( __( 'CDL Checkout test mode enabled', 'cdl-checkout' ) );
    $note->set_content( __( 'CDL Checkout test mode is currently enabled. Remember to disable it when you want to start accepting live payment on your site.', 'cdl-checkout' ) );
    $note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
    $note->set_layout( 'plain' );
    $note->set_is_snoozable( false );
    $note->set_name( 'cdl-checkout-test-mode' );
    $note->set_source( 'cdl-checkout' );
    $note->add_action( 'disable-cdl-checkout-test-mode', __( 'Disable CDL Checkout test mode', 'cdl-checkout' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cdl_checkout' ) );
    $note->save();
}

add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}


add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

add_action( 'woocommerce_blocks_loaded', 'cdl_checkout_register_order_approval_payment_method_type' );

/**
 * Registers WooCommerce Blocks integration.
 */
function cdl_checkout_register_order_approval_payment_method_type() {

    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    require_once __DIR__ . '/includes/class-wc-gateway-cdl-checkout-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new WC_Gateway_CdlCheckout_Blocks );
        }
    );
}

// signed transaction

add_action('wp_ajax_sign_transaction', 'wc_cdlcheckout_sign_transaction');
add_action('wp_ajax_nopriv_sign_transaction', 'wc_cdlcheckout_sign_transaction');

function wc_cdlcheckout_sign_transaction() {
    check_ajax_referer('sign_transaction', 'nonce');

    $settings = get_option('woocommerce_cdl_checkout_settings', array());

    $private_key = $settings['secret_key'];

    $transaction = $_POST['transaction'];

    $message = $transaction['sessionId'] . $transaction['customerEmail'] . $transaction['totalAmount'];
    $signature = hash_hmac('sha256', $message, $private_key);

    wp_send_json($signature);
}


// save checkout transaction id
add_action('wp_ajax_save_checkout_transaction_id', 'save_checkout_transaction_id');
add_action('wp_ajax_nopriv_save_checkout_transaction_id', 'save_checkout_transaction_id');

function save_checkout_transaction_id() {
    if (!isset($_POST['order_id']) || !isset($_POST['checkoutTransactionId'])) {
        wp_send_json_error('Missing parameters');
    }

    $order_id = absint($_POST['order_id']);
    $checkoutTransactionId = sanitize_text_field($_POST['checkoutTransactionId']);

    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error('Invalid order ID');
    }

    $order->update_meta_data('_checkout_transaction_id', $checkoutTransactionId);
    $order->save();

    wp_send_json_success('Transaction ID saved');
}
