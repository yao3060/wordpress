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
            'css'      => 'max-width: 600px;',
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

        array(
            'title' => __('Advanced Settings', 'woocommerce-gateway-payermax'),
            'type'  => 'title',
            'id'    => 'payermax_advanced_settings',
            /* translators: %s: privacy page link. */
            'desc'  => sprintf(esc_html__('Advanced Settings', 'woocommerce-gateway-payermax')),
        ),

        'enable_for_payment_methods' => array(
            'title'             => __('Enable for payment methods', 'woocommerce-gateway-payermax'),
            'type'              => 'multiselect',
            'disabled'          => true, // only card for phase 1, so disabled this option.
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 400px;',
            'default'           => 'cashier_card',
            'description'       => __('PayerMax is only available for certain methods', 'woocommerce-gateway-payermax'),
            'options'           => [
                ''         => __('All Payment Methods', 'woocommerce-gateway-payermax'),
                'cashier_card' => __('CARD', 'woocommerce-gateway-payermax'),
            ],
            'desc_tip'          => true,
            'custom_attributes' => array(
                'data-placeholder' => __('Select payment methods', 'woocommerce-gateway-payermax'),
            ),
        ),

        'enable_for_currencies' => array(
            'title'             => __('Enable for Currencies', 'woocommerce-gateway-payermax'),
            'type'              => 'multiselect',
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 400px;',
            'default'           => '',
            'description'       => __('PayerMax is only available for certain currencies', 'woocommerce-gateway-payermax'),
            'options'           => [
                ''         => __('All Currencies', 'woocommerce-gateway-payermax'),
                'usd' => __('USD', 'woocommerce-gateway-payermax'),
                'rmb' => __('RMB', 'woocommerce-gateway-payermax'),
            ],
            'desc_tip'          => true,
            'custom_attributes' => array(
                'data-placeholder' => __('Select payment methods', 'woocommerce-gateway-payermax'),
            ),
        ),
    ]
);
