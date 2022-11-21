<?php
if (!defined('ABSPATH')) {
    exit;
}

class PayerMax_Helper
{
    public static function get_order_country(WC_Order $order)
    {
        return empty(trim($order->get_shipping_country())) ? trim($order->get_billing_country()) : trim($order->get_shipping_country());
    }

    public static function getTradeNo(WC_Order $order)
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
                    /* translators: %s is the PayerMax transaction ID */
                    __('PayerMax transaction ID: %s', 'woocommerce-gateway-payermax'),
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
}
