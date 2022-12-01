<?php

/**
 * Plugin Name: WooCommerce PayerMax Gateway
 * Plugin URI: https://www.payermax.com/
 * Description: Take credit card payments on your store using PayerMax.
 * Author: PayerMax
 * Author URI: https://www.payermax.com/
 * Version: 1.0.1
 * Requires PHP: 7.1
 * Requires at least: 4.0
 * Tested up to: 6.0
 * WC requires at least: 3.0
 * WC tested up to: 7.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woocommerce-gateway-payermax
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Required minimums and constants
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('WC_PAYERMAX_PLUGIN_VERSION', '1.0.1');
define('WC_PAYERMAX_PLUGIN_NAME', 'woocommerce-gateway-payermax');
define('WC_PAYERMAX_API_VERSION', '1.0');
define('WC_PAYERMAX_API_KEY_VERSION', '1');
define('WC_PAYERMAX_MIN_PHP_VER', '7.0.0');
define('WC_PAYERMAX_MIN_WC_VER', '3.0');
define('WC_PAYERMAX_PLUGIN_DIR', __DIR__);
define('WC_PAYERMAX_ASSETS_URI',   plugins_url('/', __FILE__)); // with tail slash
define('PAYERMAX_API_GATEWAY', 'https://pay-gate.payermax.com/aggregate-pay-gate/api/gateway/');
define('PAYERMAX_API_DEV_GATEWAY', 'http://pay-dev.shareitpay.in/aggregate-pay/api/gateway/');



final class PayerMax
{
    private static $instance;

    protected function __construct()
    {
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    static function get_currencies(): array
    {
        $currencies = array_merge(
            ...array_column(
                self::get_supports(),
                'currencies'
            )
        );
        return array_unique($currencies);
    }

    static function get_languages(): array
    {
        $languages = array_merge(
            ...array_column(
                self::get_supports(),
                'languages'
            )
        );
        return array_unique($languages);
    }

    static function get_supports()
    {
        $cache_key = 'supports';
        $group = WC_PAYERMAX_PLUGIN_NAME . '-' . WC_PAYERMAX_PLUGIN_VERSION;
        $supports = wp_cache_get($cache_key, $group);
        if ($supports) {
            return $supports;
        }

        $supports = [];
        if (($open = fopen(__DIR__ . '/payermax-payment-supports.csv', "r")) !== FALSE) {
            $headers = fgetcsv($open, 10000, ",");
            while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                $combine = array_combine($headers, $data);
                $supports[] = array_merge($combine, [
                    'currencies' => explode(',', $combine['currencies']),
                    'languages' => explode(',', $combine['languages'])
                ]);
            }
            fclose($open);
        }
        wp_cache_set($cache_key, $supports, $group, DAY_IN_SECONDS);
        return $supports;
    }

    static function missing_wc_notice()
    {
        echo '<div class="error"><p><strong>' .
            sprintf(
                esc_html__('PayerMax requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-payermax'),
                '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
            ) .
            '</strong></p></div>';
    }

    static function wc_not_supported()
    {
        echo '<div class="error"><p><strong>' .
            sprintf(
                esc_html__('PayerMax requires WooCommerce %1$s or greater to be installed and active. WooCommerce %2$s is no longer supported.', 'woocommerce-gateway-payermax'),
                WC_PAYERMAX_MIN_WC_VER,
                WC_VERSION
            ) .
            '</strong></p></div>';
    }

    static function add_gateways($methods)
    {
        $methods[] = WC_Gateway_PayerMax::class;
        return $methods;
    }

    static function plugin_action_links($links)
    {
        $plugin_links = [
            '<a href="admin.php?page=wc-settings&tab=checkout&section=payermax">' . esc_html__('Settings', 'woocommerce-gateway-payermax') . '</a>',
        ];
        return array_merge($plugin_links, $links);
    }

    static function admin_head()
    {
        echo "
        <style type='text/css'>
        .payermax-method-description {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .payermax-method-description a {
            display: flex;
        }
        .payermax-method-description img {
            height: 24px;
        }
        </style>
        ";
    }

    static function plugin_activate()
    {
        update_option(WC_PAYERMAX_PLUGIN_NAME . '-activate-date', date('Y-m-d H:i:s'), false);
    }

    static function get_activate_date()
    {
        return get_option(WC_PAYERMAX_PLUGIN_NAME . '-activate-date');
    }
}


add_action('plugins_loaded', 'woocommerce_gateway_payermax_init', 11);

function woocommerce_gateway_payermax_init()
{

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', [PayerMax::class, 'missing_wc_notice']);
        return;
    }

    if (version_compare(WC_VERSION, WC_PAYERMAX_MIN_WC_VER, '<')) {
        add_action('admin_notices', [PayerMax::class, 'wc_not_supported']);
        return;
    }

    woocommerce_gateway_payermax();
}

function woocommerce_gateway_payermax()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    require_once __DIR__ . '/includes/class-payermax-logger.php';
    require_once __DIR__ . '/includes/class-payermax-helper.php';
    require_once __DIR__ . '/includes/abstracts/abstract-wc-payermax-payment-gateway.php';
    require_once __DIR__ . '/includes/class-wc-gateway-payermax.php';

    /**
     * This action hook registers our PHP class as a WooCommerce payment gateway
     */
    add_filter(
        'woocommerce_payment_gateways',
        [PayerMax::class, 'add_gateways']
    );

    add_filter(
        'plugin_action_links_' . plugin_basename(__FILE__),
        [PayerMax::class, 'plugin_action_links']
    );

    // ajax check payermax payment status in order detail page in dashboard.
    add_action(
        'wp_ajax_check_payermax_payment_status',
        [WC_Gateway_PayerMax::class, 'check_payermax_payment_status']
    );

    add_action('admin_head', [PayerMax::class, 'admin_head']);

    add_action('woocommerce_account_view-order_endpoint', [WC_Gateway_PayerMax::class, 'account_view_order'], 1);
}


register_activation_hook(__FILE__, [PayerMax::class, 'plugin_activate']);
