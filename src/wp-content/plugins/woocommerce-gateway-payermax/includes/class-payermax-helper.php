<?php
if (!defined('ABSPATH')) {
    exit;
}

class PayerMax_Helper
{
    public static function get_sign(array $request_data, $private_key)
    {
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($private_key, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        openssl_sign(json_encode($request_data), $sign, $res, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);

        PayerMax_Logger::debug('Base64 Sign: ' . $sign);

        return $sign;
    }

    public static function verify_sign($content, $sign, $public_key)
    {
        if (!$sign || !$content || !$public_key) return false;

        $formatted = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($public_key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        return openssl_verify($content, base64_decode($sign), $formatted, OPENSSL_ALGO_SHA256) === 1;
    }


    public static function get_order_country(WC_Order $order)
    {
        return empty(trim($order->get_shipping_country())) ? trim($order->get_billing_country()) : trim($order->get_shipping_country());
    }

    public static function get_trade_no(WC_Order $order)
    {
        $transaction_id = $order->get_transaction_id();
        if (!$transaction_id) {
            $transaction_id = self::get_order_transaction_id($order);
            self::update_transaction_id($transaction_id, $order);
        }

        return $transaction_id;
    }

    static function get_order_transaction_id(WC_Order $order): ?string
    {
        return substr(date('YmdHis') . $order->get_order_number(), -64);
    }

    static function update_transaction_id(string $transaction_id, WC_Order $wc_order): bool
    {
        try {
            $wc_order->set_transaction_id($transaction_id);
            $wc_order->save();

            $wc_order->add_order_note(
                sprintf(
                    __('Set transaction ID: %s', 'woocommerce-gateway-payermax'),
                    $transaction_id
                )
            );
            PayerMax_Logger::info('Set transaction ID ( ' . $transaction_id . ') to order (' . $wc_order->get_id() . ')');
            return true;
        } catch (Exception $exception) {
            PayerMax_Logger::error('Failed to set transaction ID ' . $transaction_id . $exception->getMessage());
            return false;
        }
    }

    public static function get_payermax_language($language)
    {
        if (in_array($language, ['zh_CN', 'zh-CN', 'zh-Hans'])) {
            return 'zh';
        }

        if (in_array($language, ['zh_HK', 'zh-HK', 'zh_TW', 'zh-TW', 'zh-Hant'])) {
            return 'zh-TW';
        }

        return substr($language, 0, 2);
    }
}
