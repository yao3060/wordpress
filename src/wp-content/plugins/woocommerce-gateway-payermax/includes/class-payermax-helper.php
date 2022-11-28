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

    /**
     * Convert total amount into payermax format
     *
     * 商户传入的订单金额，金额的单位为元。
     * 除以下国家外按照各国币种支持的小数点位上送。
     * 注意：巴林、科威特、伊拉克，约旦、突尼斯、利比亚、奥马尔地区，本币只支持两位小数；
     * 印尼、中国台湾、韩国、越南、智利、巴基斯坦、哥伦比亚地区，本币不支持带小数金额。
     * @ref https://taxsummaries.pwc.com/glossary/currency-codes
     * @example 美元：15.00
     * @example 日元：101
     *
     * @param float $amount
     * @param string $currency
     * @return string 最大长度(20,4)
     */
    static function payment_amount(float $amount, string $currency): string
    {
        // 巴林、科威特、伊拉克，约旦、突尼斯、利比亚、奥马尔地区，本币只支持两位小数；
        if (in_array($currency, ['BHD', 'KWD', 'IQD', 'JOD', 'TND', 'LYD'])) {
            return (string)round($amount, 2);
        }

        // 印尼、中国台湾、韩国、越南、智利、巴基斯坦、哥伦比亚地区，本币不支持带小数金额。
        if (in_array($currency, ['IDR', 'TWD', 'KRW', 'VND', 'CLP', 'PKR', 'COP'])) {
            return (string)round($amount);
        }

        return (string)round($amount, 4);
    }

    static function is_equal_payment_amount(float $order_amount, string $transaction_amount, string $currency)
    {
        if (self::payment_amount($order_amount, $currency) === $transaction_amount) {
            return true;
        }
        return false;
    }

    /**
     * 退款金额，金额的单位为元。
     * 除以下国家外按照各国币种支持的小数点位上送。
     * 注意：巴林、科威特、伊拉克，约旦、突尼斯、利比亚、奥马尔地区，本币只支持两位小数；
     * 印尼、中国台湾、巴基斯坦、哥伦比亚地区，本币不支持带小数金额。
     * @ref https://taxsummaries.pwc.com/glossary/currency-codes
     *
     * @param float $amount
     * @param string $currency
     * @return string 最大长度(20,4)
     */
    static function refund_amount(float $amount, string $currency)
    {
        // 巴林、科威特、伊拉克，约旦、突尼斯、利比亚、奥马尔地区，本币只支持两位小数；
        if (in_array($currency, ['BHD', 'KWD', 'IQD', 'JOD', 'TND', 'LYD'])) {
            return (string)round($amount, 2);
        }

        // 印尼、中国台湾、巴基斯坦、哥伦比亚地区，本币不支持带小数金额
        if (in_array($currency, ['IDR', 'TWD', 'PKR', 'COP'])) {
            return (string)round($amount);
        }

        return (string)round($amount);
    }

    /**
     * Get Woo Order by Transaction ID.
     *
     * @param string $transaction_id
     * @return WC_Order | null
     */
    static function get_order_by_transaction_id(string $transaction_id)
    {
        $query = new WP_Query([
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_key' => '_transaction_id',
            'meta_value' => $transaction_id,
            'meta_compare' => '='
        ]);

        if (!$query->have_posts()) {
            PayerMax_Logger::warning('Order not found by transaction id: ' .  $transaction_id);
            return null;
        }

        $order = wc_get_order($query->posts[0]->ID);
        if (!$order) {
            return null;
        }

        return $order;
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

    static function get_order_transaction_id(WC_Order $order)
    {
        return substr((new Datetime())->format('YmdHisv') . $order->get_order_number(), -64);
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
