<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_PayerMax_Notify
{
    /**
     * $notice_data sample data:
     * {
     *    "code": "APPLY_SUCCESS",
     *    "msg": "",
     *    "version": "1.0",
     *    "keyVersion": "1",
     *    "merchantAppId": "3b242b56a8b64274bcc37dac281120e3",
     *    "merchantNo": "020213827212251",
     *    "notifyTime": "2022-01-17T09:33:54.540+00:00",
     *    "notifyType": "PAYMENT",
     *    "data": {
     *       "outTradeNo": "P1642410680681",
     *       "tradeToken": "TOKEN20220117070423078754880",
     *       "totalAmount": "10000",
     *       "currency": "IDR",
     *       "country": "ID",
     *       "status": "SUCCESS",
     *       "paymentDetails": [
     *            {
     *                "paymentMethod": "WALLET",
     *                "targetOrg": "DANA"
     *            }
     *        ],
     *       "reference": "020213827524152"
     *    }
     * }
     */
    public static function payment_complete($notice_data): bool
    {
        $order = wc_get_order((int)$notice_data['reference']);
        if (!$order) {
            PayerMax_Logger::warning("Order not found from Notify, order id:" . $notice_data['reference']);
            return false;
        }

        // verify transaction_id totalAmount and currency
        if (
            $order->get_transaction_id() !== $notice_data['data']['outTradeNo'] ||
            $order->get_total() !== (float)$notice_data['data']['totalAmount'] ||
            $order->get_currency() !== $notice_data['data']['currency']
        ) {
            PayerMax_Logger::warning('Order out_trade_no, total or currency not match.');
            return false;
        }

        $order->payment_complete();

        PayerMax_Logger::info('Order (' . $order->get_id() . ') payment complete via payermax notify.');

        return true;
    }

    /**
     * $request_data post data from payermax
     * {
     *     "code": "APPLY_SUCCESS",
     *     "msg": "",
     *     "version": "1.0",
     *     "keyVersion": "1",
     *     "merchantAppId": "3b242b56a8b64274bcc37dac281120e3",
     *     "merchantNo": "020213827212251",
     *     "notifyTime": "2022-01-17T09:33:54.540+00:00",
     *     "notifyType": "REFUND",
     *     "data": {
     *         "outRefundNo": "R1642411016202",
     *         "refundTradeNo": "20220117091657TI790000055087",
     *         "outTradeNo": "P1642410680681",
     *         "refundAmount": "10000",
     *         "refundCurrency": "IDR",
     *         "status": "REFUND_SUCCESS"
     *     }
     * }
     */
    public static function refund_complete(array $request_data): bool
    {
        if ($request_data['notifyType'] === 'REFUND') {

            $transaction_id = $request_data['data']['outTradeNo'];
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
                return false;
            }

            $order = wc_get_order($query->posts[0]->ID);
            if (!$order) {
                return false;
            }

            // if refund successfully
            if ($order->get_transaction_id() === $transaction_id && $request_data['data']['status'] === 'REFUND_SUCCESS') {
                $order->add_order_note(sprintf(
                    'Refunded - Refund ID: %s - Refund Amount: %s',
                    $request_data['data']['refundTradeNo'],
                    $request_data['data']['refundAmount']
                ), 1);
                return true;
            } else {
                $order->add_order_note(sprintf(
                    'Refund failed - Refund ID: %s - errorMessage: %s',
                    $request_data['data']['refundTradeNo'],
                    $request_data['msg']
                ));
            }
        }

        return false;
    }
}
