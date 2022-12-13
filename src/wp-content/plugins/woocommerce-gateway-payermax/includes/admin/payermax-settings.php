<?php
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_payermax_settings',
    [

        'enabled'     => [
            'title'       => PayerMax::__('Status', 'woocommerce-gateway-payermax'),
            'label'       => PayerMax::__('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
        ],
        'title'       => [
            'title'       => PayerMax::__('Title', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => PayerMax::__('This controls the title which the user sees during checkout.', 'woocommerce-gateway-payermax'),
            'default'     => PayerMax::__('PayerMax', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],
        'description' => [
            'title'       => PayerMax::__('Description', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => PayerMax::__('This controls the description which the user sees during checkout.', 'woocommerce-gateway-payermax'),
            'default'     => PayerMax::__('You will be redirected to PayerMax.', 'woocommerce-gateway-payermax'),
            'desc_tip'    => true,
        ],

        'app_id' => [
            'title'       => PayerMax::__('App ID', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => PayerMax::__('App ID', 'woocommerce-gateway-payermax'),
            'default'     => '',
            'desc_tip'    => true,
        ],

        'merchant_number' => [
            'title'       => PayerMax::__('Merchant No.', 'woocommerce-gateway-payermax'),
            'type'        => 'text',
            'description' => PayerMax::__('Merchant Number', 'woocommerce-gateway-payermax'),
            'default'     => '',
            'desc_tip'    => true,
        ],

        'merchant_public_key' => [
            'title'       => PayerMax::__('Merchant Public Key', 'woocommerce-gateway-payermax'),
            'type'        => 'textarea',
            'description' => PayerMax::__('Merchant Public Key', 'woocommerce-gateway-payermax'),
            'default'     => '',
            'desc_tip'    => true,
            'css'      => 'max-width: 650px; min-height:60px;',
        ],

        'merchant_private_key' => [
            'title'       => PayerMax::__('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'type'        => 'textarea',
            'description' => PayerMax::__('Merchant Private Key', 'woocommerce-gateway-payermax'),
            'default'     => '',
            'desc_tip'    => true,
            'css'      => 'max-width: 650px; min-height:150px;',
        ],

        // TODO: disabled temporarily.
        // 'enable_refunds'     => [
        //     'title'       => PayerMax::__('Enable PayerMax Refunds', 'woocommerce-gateway-payermax'),
        //     'label'       => PayerMax::__('Enable/Disable', 'woocommerce-gateway-payermax'),
        //     'type'        => 'checkbox',
        //     'description' => '',
        //     'default'     => 'no',
        //     'desc_tip'    => true,
        // ],

        'endpoint' => array(
            'title' => PayerMax::__('Gateway', 'woocommerce-gateway-payermax'),
            'type' => 'select',
            'description' => PayerMax::__('This ENV which the user going to use during checkout.', 'woocommerce-gateway-payermax'),
            'default' => PAYERMAX_API_GATEWAY,
            'options' => array_map(function ($item) {
                return sprintf('%s [%s]', PayerMax::__($item, 'woocommerce-gateway-payermax'), $item);
            }, PayerMax::get_envs())
        ),
    ]
);
