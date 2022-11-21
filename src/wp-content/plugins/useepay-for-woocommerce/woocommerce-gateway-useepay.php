<?php
/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

/**
 * Plugin Name: Useepay For WooCommerce(Server To Server)
 * Plugin URI: https://www.useepay.com
 * Description: Debit/Credit Card Payments via UseePay
 * Version:1.0.18
 * Author: UseePay
 * Author URI: https://www.useepay.com
 *
 * Text Domain: woocommerce-gateway-useepay
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

define('WC_USEEPAY_VERSION', '1.0.18');


class WC_UseePay
{
    private static $instance = null;

    static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new WC_UseePay();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'woocommerce_gateway_useepay_init'));
    }

    function woocommerce_gateway_useepay_init()
    {
        // load language files
        load_plugin_textdomain('useepay-for-woocommerce', false, plugin_basename(dirname(__FILE__)) . '/languages');

        if (!class_exists('WooCommerce')) {
            // woocommerce not installed
            add_action('admin_notices', array($this, 'woocommerce_not_installed_notice'));
            return;
        }

        require_once dirname(__FILE__) . '/includes/class-wc-useepay-exception.php';
        require_once dirname(__FILE__) . '/includes/class-wc-useepay-util.php';
        require_once dirname(__FILE__) . '/includes/class-wc-useepay-logger.php';
        require_once dirname(__FILE__) . '/includes/class-wc-useepay-api.php';
        require_once dirname(__FILE__) . '/includes/abstract-wc-useepay-payment-gateway.php';
        require_once dirname(__FILE__) . '/includes/class-wc-gateway-useepay.php';

        add_filter('woocommerce_payment_gateways', array($this, 'add_gateways'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wc_useepay_plugin_links'));
        add_filter('woocommerce_get_order_item_totals', array($this, 'wc_useepay_display_order_meta_for_customer'), 10, 2);
    }

    function woocommerce_not_installed_notice()
    {
        echo '<p>' . printf(__('Woocommerce not installed and activated. Before using UseePay, please install and active %1$s first.', 'useepay-for-woocommerce'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</p>';
    }

    /**
     * Add the gateway to WooCommerce
     *
     */
    function add_gateways($methods)
    {
        $methods[] = 'WC_Gateway_UseePay';
        return $methods;
    }

    /**
     * Generate a href tag(named Settings) in plugins installed page for UseePay.
     *
     * @param $links
     * @return array
     */
    function wc_useepay_plugin_links($links)
    {
        return array_merge(
            array(
                '<a href="admin.php?page=wc-settings&tab=checkout&section=useepay">' . esc_html__('Settings', 'useepay-for-woocommerce') . '</a>',
            ),
            $links
        );
    }

    /**
     * Display useepay Trade No. for customer
     *
     *
     * The function is put here because the useepay class
     * is not called on order-received page
     *
     * @param array $total_rows
     * @param mixed $order
     * @return array
     */
    function wc_useepay_display_order_meta_for_customer($total_rows, $order)
    {
        $trade_no = get_post_meta($order->get_id(), 'useepay Trade No.', true);

        if (!empty($trade_no)) {
            $new_row['useepay_trade_no'] = array(
                'label' => __('useepay Trade No.:', 'useepay'),
                'value' => $trade_no
            );
            // Insert $new_row after shipping field
            $total_rows = array_merge(array_splice($total_rows, 0, 2), $new_row, $total_rows);
        }
        return $total_rows;
    }

}

WC_UseePay::init();
