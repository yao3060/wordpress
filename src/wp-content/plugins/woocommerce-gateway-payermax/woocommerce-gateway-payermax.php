<?php

/**
 * Plugin Name: WooCommerce PayerMax Gateway
 * Plugin URI: https://www.payermax.com/
 * Description: Take credit card payments on your store using PayerMax.
 * Author: PayerMax
 * Author URI: https://www.payermax.com/
 * Version: 1.0.0
 * Requires at least: 5.8
 * Tested up to: 6.0
 * WC requires at least: 6.8
 * WC tested up to: 7.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woocommerce-gateway-payermax
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

use Automattic\Jetpack\Constants;

/**
 * Required minimums and constants
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('WC_PAYERMAX_PLUGIN_VERSION', '1.0.0');
define('WC_PAYERMAX_PLUGIN_NAME', 'woocommerce-gateway-payermax');
define('WC_PAYERMAX_API_VERSION', '1.0');
define('WC_PAYERMAX_API_KEY_VERSION', '1');
define('WC_PAYERMAX_MIN_PHP_VER', '7.3.0');
define('WC_PAYERMAX_MIN_WC_VER', '6.8');
define('WC_PAYERMAX_FUTURE_MIN_WC_VER', '6.9');
define('WC_PAYERMAX_PLUGIN_FILE', __FILE__);
define('WC_PAYERMAX_ASSETS_URI',   plugins_url('/', WC_PAYERMAX_PLUGIN_FILE)); // with tail slash
define('WC_PAYERMAX_PLUGIN_PATH', untrailingslashit(plugin_dir_path(WC_PAYERMAX_PLUGIN_FILE))); // without tail slash
define('PAYERMAX_API_GATEWAY', 'https://pay-gate-uat.payermax.com/aggregate-pay/api/gateway/');
define('PAYERMAX_API_UAT_GATEWAY', 'https://pay-gate-uat.payermax.com/aggregate-pay/api/gateway/');

/**
 * WooCommerce fallback notice.
 *
 * @since 4.1.2
 */
function woocommerce_payermax_missing_wc_notice()
{
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' .
        sprintf(
            esc_html__('PayerMax requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-payermax'),
            '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
        ) .
        '</strong></p></div>';
}

/**
 * WooCommerce not supported fallback notice.
 *
 * @since 4.4.0
 */
function woocommerce_payermax_wc_not_supported()
{
    /* translators: $1. Minimum WooCommerce version. $2. Current WooCommerce version. */
    echo '<div class="error"><p><strong>' .
        sprintf(
            esc_html__('PayerMax requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'woocommerce-gateway-stripe'),
            WC_PAYERMAX_MIN_WC_VER,
            Constants::get_constant('WC_VERSION')
        ) .
        '</strong></p></div>';
}


add_action('plugins_loaded', 'woocommerce_gateway_payermax_init', 11);

function woocommerce_gateway_payermax_init()
{

    load_plugin_textdomain('woocommerce-gateway-payermax', false, plugin_basename(dirname(WC_PAYERMAX_PLUGIN_FILE)) . '/languages');

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'woocommerce_payermax_missing_wc_notice');
        return;
    }

    if (version_compare(Constants::get_constant('WC_VERSION'), WC_PAYERMAX_MIN_WC_VER, '<')) {
        add_action('admin_notices', 'woocommerce_payermax_wc_not_supported');
        return;
    }

    // permalink is required
    if (get_option('permalink_structure')) {
    }

    woocommerce_gateway_payermax();

    /**
     * This action hook registers our PHP class as a WooCommerce payment gateway
     */
    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = WC_Gateway_PayerMax::class;
        return $methods;
    });
}

function woocommerce_gateway_payermax()
{
    if (!class_exists('WC_Payment_Gateway')) return;
    require_once __DIR__ . '/includes/class-payermax.php';
    require_once __DIR__ . '/includes/class-payermax-logger.php';
    require_once __DIR__ . '/includes/class-payermax-helper.php';
    require_once __DIR__ . '/includes/abstracts/abstract-wc-payermax-payment-gateway.php';
    require_once __DIR__ . '/includes/class-wc-gateway-payermax.php';

    PayerMax_Logger::debug("Supported Currencies:" . json_encode(PayerMax::get_currencies()));
    PayerMax_Logger::debug("Supported Languages:" . json_encode(PayerMax::get_languages()));
}



add_filter('plugin_action_links_' . plugin_basename(WC_PAYERMAX_PLUGIN_FILE), function ($links) {
    $plugin_links = [
        '<a href="admin.php?page=wc-settings&tab=checkout&section=payermax">' . esc_html__('Settings', 'woocommerce-gateway-payermax') . '</a>',
    ];
    return array_merge($plugin_links, $links);
});
