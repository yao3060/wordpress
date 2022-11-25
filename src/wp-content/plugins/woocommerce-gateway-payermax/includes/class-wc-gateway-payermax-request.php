<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to PayerMax.
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
     * @return string
     */
    public function get_request_url($order)
    {
        $this->endpoint = PayerMax::gateway($this->gateway->sandbox === 'no');

        $request_data = $this->wrap_request_data($this->get_request_data($order));
        PayerMax_Logger::info(json_encode($request_data));
        $response = wp_remote_post(
            $this->endpoint . 'orderAndPay',
            [
                'method' => 'POST',
                'body' => json_encode($request_data),
                'headers' => [
                    'sign' => PayerMax_Helper::get_sign($request_data, $this->gateway->merchant_private_key),
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        if (!is_wp_error($response)) {
            PayerMax_Logger::info($response['body']);
            $response_data = json_decode($response['body'], true);
            // it could be 'APPLY_SUCCESS' or 'TRADE_REQUEST_REPEATED', but if `redirectUrl` exists, it's means apply success.
            if ($response_data['data'] && $response_data['data']['redirectUrl']) {
                return $response_data['data']['redirectUrl'];
            }

            // transaction closed by payermax
            if ($response_data['data'] && $response_data['data']['status'] && $response_data['data']['status'] === 'CLOSED') {
                // reset transaction id
                PayerMax_Helper::update_transaction_id(PayerMax_Helper::get_order_transaction_id($order), $order);
                // return `closed`, so parent class can recall this method according this response.
                return 'CLOSED';
            }

            wc_add_notice(__($response_data['msg'], 'woocommerce-gateway-payermax'), 'error');
            return '';
        } else {
            wc_add_notice(__('Gateway Error.', 'woocommerce-gateway-payermax'), 'error');
            return '';
        }

        return '';
    }

    public function refund_transaction(WC_Order $order, $amount, $reason)
    {
        $out_refund_no = 'R' . str_replace('-', '', wp_generate_uuid4());
        $request_data = $this->wrap_request_data([
            "outRefundNo" => $out_refund_no,
            "outTradeNo" => $order->get_transaction_id(),
            // TODO: cast into payermax format amount
            "refundAmount" => $amount,
            "refundCurrency" => $order->get_currency(),
            "comments" => $reason ?? '',
            "refundNotifyUrl" => home_url('wc-api/' . $this->gateway::REFUND_ORDER_NOTIFY_CALLBACK)
        ]);
        PayerMax_Logger::info('Refund Request: ' . wc_print_r($request_data, true));

        $response = wp_safe_remote_post(
            PayerMax::gateway($this->gateway->sandbox === 'no') . 'refund',
            [
                'method' => 'POST',
                'body' => json_encode($request_data),
                'headers' => [
                    'sign' => PayerMax_Helper::get_sign($request_data, $this->gateway->merchant_private_key),
                    'Content-Type' => 'application/json',
                ]
            ]
        );

        if (is_wp_error($response)) {
            PayerMax_Logger::error('refund transaction failed: ' . $response->get_error_message());
            return $response;
        }

        PayerMax_Logger::info('Refund Response: ' . wc_print_r($response['body'], true));
        return json_decode($response['body'], true);
    }


    /**
     * verify transaction status
     */
    public function get_transaction_status(WC_Order $order)
    {
        $request_data = $this->wrap_request_data(["outTradeNo" => $order->get_transaction_id()]);

        $this->endpoint = PayerMax::gateway($this->gateway->sandbox === 'no');

        $response = wp_remote_post($this->endpoint . 'orderQuery', [
            'method' => 'POST',
            'body' => json_encode($request_data),
            'headers' => [
                'sign' => PayerMax_Helper::get_sign($request_data, $this->gateway->merchant_private_key),
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            PayerMax_Logger::error('query transaction failed: ' . json_encode($request_data));
            PayerMax_Logger::error('get_transaction_status response:' . json_encode($response));
            return false;
        } else {
            PayerMax_Logger::info('get_transaction_status response:' . $response['body']);
            $response_data = json_decode($response['body'], true);

            if ($response_data['data'] && $response_data['data']['status'] === 'SUCCESS') {
                $order->payment_complete();
            }
        }

        // else do nothing
    }

    public function wrap_request_data($data)
    {
        return [
            'version' => WC_PAYERMAX_API_VERSION,
            'keyVersion' => WC_PAYERMAX_API_KEY_VERSION,
            'requestTime' => (new \DateTime())->format('Y-m-d\TH:i:s.vP'),
            'merchantAppId' => (string)$this->gateway->app_id,
            'merchantNo' => (string)$this->gateway->merchant_number,
            'data' => $data
        ];
    }

    protected function get_request_data(WC_Order $order): array
    {
        $data = [
            'outTradeNo' => PayerMax_Helper::get_trade_no($order),
            'subject' => $order->get_title(),
            // TODO: cast into payermax format amount
            'totalAmount' => (string)$order->get_total(),
            'currency' => $order->get_currency(),
            'country' => PayerMax_Helper::get_order_country($order),
            'userId' => (string)$order->get_customer_id(),
            'goodsDetails' => $this->get_goods_details($order),
            'language' => PayerMax_Helper::get_payermax_language(get_user_locale()), // 收银台页面语言
            'reference' => (string)$order->get_id(), // it will returned in notify callback, so we can easily get order object via `reference`.
            'frontCallbackUrl' => $this->gateway->get_return_url($order),
            'notifyUrl' => home_url('wc-api/' . $this->gateway::ORDER_NOTIFY_CALLBACK)
        ];

        // Only CARD pay for Phase 1
        $data['paymentDetail'] = [
            'paymentMethod' => 'CARD'
        ];

        return apply_filters('wc_payermax_order_data', $data, $order);
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
                'goodsId' => (string)$item->get_id(),
                'goodsName' => (string)$item->get_name(),
                'quantity' => (string)$item->get_quantity(),
                'price' => (string)($product->get_price()),
            ];
        }
        return $goods;
    }
}
