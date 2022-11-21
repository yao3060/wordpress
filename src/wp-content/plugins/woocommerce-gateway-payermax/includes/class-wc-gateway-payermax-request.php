<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to PayPal.
 */
class WC_Gateway_PayerMax_Request
{
    /**
     * Pointer to gateway making the request.
     *
     * @var WC_Gateway_PayerMax
     */
    protected $gateway;

    /**
     * Endpoint for requests from PayerMax.
     *
     * @var string
     */
    protected $notify_url;

    /**
     * Endpoint for requests from PayerMax.
     *
     * @var string
     */
    protected $front_callback_url;

    /**
     * Endpoint for requests to PayerMax.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Constructor.
     *
     * @param WC_Gateway_PayerMax $gateway PayerMax gateway object.
     */
    public function __construct($gateway)
    {
        $this->gateway    = $gateway;
        $this->notify_url = $this->gateway->get_option('notify_url');
        $this->front_callback_url = WC()->api_request_url(WC_Gateway_PayerMax::class);
    }

    /**
     * Get the PayerMax request URL for an order.
     *
     * @param  WC_Order $order Order object.
     * @param  bool     $sandbox Whether to use sandbox mode or not.
     * @return string
     */
    public function get_request_url($order, $sandbox = false)
    {
        $this->endpoint = $sandbox ? PAYERMAX_API_GATEWAY : PAYERMAX_API_GATEWAY;

        $request_data = $this->get_request_data($order);
    }

    protected function get_request_data(WC_Order $order)
    {
        $data = [
            "outTradeNo" => PayerMax_Helper::getTradeNo($order),
            "subject" => $order->get_title(),
            "totalAmount" => (string)$order->get_total(),
            "currency" => $order->get_currency(),
            "country" => PayerMax_Helper::get_order_country($order),
            "userId" => (string)$order->get_customer_id(),
            "goodsDetails" => $this->get_goods_details($order),
            "language" => get_user_locale(), // 收银台页面语言
            "reference" => (string)$order->get_id(), // 商户自定义附加数据，可支持商户自定义并在响应中返回
            "frontCallbackUrl" => $this->gateway->get_return_url($order),
            "notifyUrl" => home_url() . $this->gateway->get_option('webhook')
        ];

        return apply_filters('wc_payermax_order_data', $data);
    }

    public function get_goods_details(WC_Order $order)
    {
        $goods = [];

        /** @var WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            /** @var WC_Product | false $product */
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $goods[]      = [
                "goodsId" => (string)$item->get_id(),
                "goodsName" => (string)$item->get_name(),
                "quantity" => (string)$item->get_quantity(),
                "price" => $product->get_sale_price(),
            ];
        }
        return $goods;
    }
}
