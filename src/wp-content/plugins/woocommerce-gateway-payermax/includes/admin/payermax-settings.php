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
            'css'      => 'max-width: 650px; min-height:150px;',
        ],

        // TODO: disabled temporarily.
        // 'enable_refunds'     => [
        //     'title'       => __('Enable PayerMax Refunds', 'woocommerce-gateway-payermax'),
        //     'label'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
        //     'type'        => 'checkbox',
        //     'description' => '',
        //     'default'     => 'no',
        //     'desc_tip'    => true,
        // ],

        'endpoint' => array(
            'title' => __('Gateway', 'woocommerce-gateway-payermax'),
            'type' => 'select',
            'description' => __('This ENV which the user going to use during checkout.', 'woocommerce-gateway-payermax'),
            'default' => PAYERMAX_API_DEV_GATEWAY,
            'options' => PayerMax::get_envs()
        ),

        'callbacks'     => [
            'title'       => __('Callbacks', 'woocommerce-gateway-payermax'),
            'type'        => 'title',
            'description' => sprintf(
                __('<pre>Payment Result: %s
Refund Result:  %s</pre>', 'woocommerce-gateway-payermax'),
                $this->get_payment_callback_url(),
                $this->get_refund_callback_url()
            ),
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

        'debug'     => [
            'title'       => __('Debug', 'woocommerce-gateway-payermax'),
            'label'       => __('Enable/Disable', 'woocommerce-gateway-payermax'),
            'type'        => 'title',
            'description' => sprintf(
                __('<code>WP_DEBUG</code> is <code>%s</code>, if enabled, plugin will store trade info into <code>%s</code>.', 'woocommerce-gateway-payermax'),
                WP_DEBUG ? 'true' : 'false',
                wc_get_log_file_path(self::ID)
            ),
            'default'     => WP_DEBUG ? 'yes' : 'no',
        ],
    ]
);
