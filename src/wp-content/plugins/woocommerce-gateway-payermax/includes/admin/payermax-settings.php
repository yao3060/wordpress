<?php
if (!defined('ABSPATH')) {
    exit;
}

return apply_filters(
    'wc_payermax_settings',
    [

        'enabled'     => [
            'title'       => __('Enable/Disable', 'woocommerce-gateway-stripe'),
            'label'       => __('Enable Stripe Alipay', 'woocommerce-gateway-stripe'),
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'no',
        ],
        'title'       => [
            'title'       => __('Title', 'woocommerce-gateway-stripe'),
            'type'        => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-stripe'),
            'default'     => __('Alipay', 'woocommerce-gateway-stripe'),
            'desc_tip'    => true,
        ],
        'description' => [
            'title'       => __('Description', 'woocommerce-gateway-stripe'),
            'type'        => 'text',
            'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-stripe'),
            'default'     => __('You will be redirected to Alipay.', 'woocommerce-gateway-stripe'),
            'desc_tip'    => true,
        ],
        'webhook'     => [
            'title'       => __('Webhook Endpoints', 'woocommerce-gateway-stripe'),
            'type'        => 'title',
            /* translators: webhook URL */
            'description' => $this->display_admin_settings_webhook_description(),
        ],
    ]
);
