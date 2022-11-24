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
}
