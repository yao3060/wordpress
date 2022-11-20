<?php
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_payermax_settings',
    [

        'enabled'     => [
            'title'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable PayerMax', 'woocommerce-gateway-payermax'),
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

        'merchant_id' => [
            'title'       => __('Merchant Id', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => __('Merchant Id', 'woocommerce-gateway-payermax'),
            'default'     => __('', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],

        'merchant_private_key' => [
            'title'       => __('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => __('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'default'     => __('', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],

        'sandbox'     => [
            'title'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable Sandbox', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
        ],

        'debug'     => [
            'title'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable Debug', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
        ],

        'webhook'     => [
            'title'       => __('Webhook Endpoints', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            /* translators: webhook URL */
            'description' => 'Webhook Endpoints',
            'desc_tip'    => true,
        ],
    ]
);
