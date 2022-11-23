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

        'merchant_private_key' => [
            'title'       => __('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'type'        => 'textarea',
            'description' => __('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'default'     => __('', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
            'css'      => 'max-width: 600px; min-height:250px;',
        ],

        'sandbox'     => [
            'title'       => __('Sandbox Mode', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'description' => __('Sandbox is for test only, Do not enable it on production.', 'woocommerce-gateway-payermax'),
            'default'     => 'no',
        ],

        'debug'     => [
            'title'       => __('Debug', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'disabled' => true,
            'description' => __('Depend on <code>WP_DEBUG</code>, if enabled, plugin will store <code>INFO</code> level logs.', 'woocommerce-gateway-payermax'),
            'default'     => WP_DEBUG ? 'yes' : 'no',
        ],

        'webhook'     => [
            'title'       => __('Webhook Endpoints', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            /* translators: webhook URL */
            'description' => 'Webhook Endpoints',
            'desc_tip'    => true,
        ],

        'enable_for_currencies' => array(
            'title'             => __('Enable for Currencies', 'woocommerce-gateway-payermax'),
            'type'              => 'multiselect',
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 400px;',
            'default'           => '',
            'description'       => __('PayerMax is only available for certain currencies', 'woocommerce-gateway-payermax'),
            'options'  => (function () {
                $currency_code_options = get_woocommerce_currencies();

                foreach ($currency_code_options as $code => $name) {
                    $currency_code_options[$code] =  $name . ' (' . $code . ')';
                }

                return $currency_code_options;
            })(),
            'desc_tip'          => true,
            'custom_attributes' => array(
                'data-placeholder' => __('Select payment methods', 'woocommerce-gateway-payermax'),
            ),
        ),
    ]
);
