<?php
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_payermax_settings',
    [

        'enabled'     => [
            'title'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'label'       => __('PayerMax', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
        ],
        'title'       => [
            'title'       => __('Title', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-payermax'),
            'default'     => __('PayerMax', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],
        'description' => [
            'title'       => __('Description', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-payermax'),
            'default'     => __('You will be redirected to PayerMax.', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],

        'app_id' => [
            'title'       => __('App ID', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => __('App ID', 'woocommerce-gateway-payermax'),
            'default'     => __('', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],

        'merchant_number' => [
            'title'       => __('Merchant No.', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => __('Merchant Number', 'woocommerce-gateway-payermax'),
            'default'     => __('', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],

        'merchant_public_key' => [
            'title'       => __('Merchant Public Key', 'woocommerce-gateway-payermax'),
            'type'        => 'textarea',
            'description' => __('Merchant Public Key', 'woocommerce-gateway-payermax'),
            'default'     => '',
            'desc_tip'    => true,
            'css'      => 'max-width: 650px; min-height:60px;',
        ],

        'merchant_private_key' => [
            'title'       => __('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'type'        => 'textarea',
            'description' => __('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'default'     => '',
            'desc_tip'    => true,
            'css'      => 'max-width: 650px; min-height:250px;',
        ],

        'sandbox'     => [
            'title'       => __('Sandbox Mode', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'description' => __('Sandbox is for test only, Do not enable it on production.', 'woocommerce-gateway-payermax'),
            'default'     => 'no',
        ],

        'callbacks'     => [
            'title'       => __('Callbacks', 'woocommerce-gateway-payermax'),
            'type'        => 'title',
            'description' => sprintf(
                __('<strong>Payment:</strong><code>%s</code><br><strong>Refund:</strong><code>%s</code>', 'woocommerce-gateway-payermax'),
                home_url('wc-api/' . self::ORDER_NOTIFY_CALLBACK),
                home_url('wc-api/' . self::REFUND_ORDER_NOTIFY_CALLBACK)
            ),
        ],

        'debug'     => [
            'title'       => __('Debug', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'title',
            'description' => sprintf(
                __('<code>WP_DEBUG</code> is %s, if enabled, plugin will store <code title="%s">%s</code> level logs.', 'woocommerce-gateway-payermax'),
                WP_DEBUG ? 'true' : 'false',
                'PayerMax_Logger::debug()',
                'debug'
            ),
            'default'     => WP_DEBUG ? 'yes' : 'no',
        ],

        'available_for_payment_method' => array(
            'title'             => __('Available for Payment Method', 'woocommerce-gateway-payermax'),
            'type'              => 'title',
            'description'       => __('PayerMax is only available for <code>CARD</code> method is this version.', 'woocommerce-gateway-payermax'),
        ),

        'available_for_currencies' => array(
            'title'             => __('Available for Currencies', 'woocommerce-gateway-payermax'),
            'type'              => 'title',
            'css'      => 'max-width: 600px;',
            'description'       => sprintf(
                __('PayerMax is only available for those currencies: <code>%s</code>, <br>Current currency is: <code>%s</code>', 'woocommerce-gateway-payermax'),
                join(', ', PayerMax::get_currencies()),
                get_option('woocommerce_currency')
            )
        ),
    ]
);
