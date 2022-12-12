<?php
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_payermax_settings',
    [

        'enabled'     => [
            'title'       => PayerMax::__('Enable/Disable', 'woocommerce-gateway-payermax'),
            'label'       => PayerMax::__('PayerMax', 'woocommerce-gateway-payermax'),
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
            'default' => PAYERMAX_API_DEV_GATEWAY,
            'options' => PayerMax::get_envs()
        ),

        'callbacks'     => [
            'title'       => PayerMax::__('Callbacks', 'woocommerce-gateway-payermax'),
            'type'        => 'title',
            'description' => sprintf(
                PayerMax::__("<pre>Payment Result: %s \nRefund Result:  %s</pre>", 'woocommerce-gateway-payermax'),
                $this->get_payment_callback_url(),
                $this->get_refund_callback_url()
            ),
        ],



        'available_for_payment_method' => array(
            'title'             => PayerMax::__('Available for Payment Method', 'woocommerce-gateway-payermax'),
            'type'              => 'title',
            'description'       => PayerMax::__('PayerMax is only available for <code>CARD</code> method is this version.', 'woocommerce-gateway-payermax'),
        ),

        'available_for_currencies' => array(
            'title'             => PayerMax::__('Available for Currencies', 'woocommerce-gateway-payermax'),
            'type'              => 'title',
            'css'      => 'max-width: 600px;',
            'description'       => sprintf(
                PayerMax::__('PayerMax is only available for those currencies: <code>%s</code>, <br>Current currency is: <code>%s</code>', 'woocommerce-gateway-payermax'),
                join(', ', PayerMax::get_currencies()),
                get_option('woocommerce_currency')
            )
        ),

        'debug'     => [
            'title'       => PayerMax::__('Debug', 'woocommerce-gateway-payermax'),
            'label'       => PayerMax::__('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'title',
            'description' => sprintf(
                PayerMax::__('<code>WP_DEBUG</code> is <code>%s</code>, if enabled, plugin will store trade info into <code>%s</code>.', 'woocommerce-gateway-payermax'),
                WP_DEBUG ? 'true' : 'false',
                wc_get_log_file_path(self::ID)
            ),
            'default'     => WP_DEBUG ? 'yes' : 'no',
        ],
    ]
);
