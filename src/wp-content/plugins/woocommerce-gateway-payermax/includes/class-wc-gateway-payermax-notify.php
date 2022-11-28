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
        if ($notice_data['code'] !== 'APPLY_SUCCESS' || $notice_data['notifyType'] !== 'PAYMENT') {
            return false;
        }

        // find the order by `reference`
        $order = wc_get_order((int)$notice_data['data']['reference']);
        if (!$order) {
            PayerMax_Logger::warning("Order not found from Notify, order id:" . $notice_data['data']['reference']);
            return false;
        }

        // processing on-hold order only
        PayerMax_Logger::debug("Payment Notify Order Status, " .  wc_print_r(['id' => $order->get_id(), 'status' => $order->get_status()], true));
        if ($order->get_status() !== 'on-hold') {
            PayerMax_Logger::info("it is not on-hold order:" . wc_print_r(['id' => $order->get_id(), 'status' => $order->get_status()], true));
            return false;
        }

        // verify transaction_id
        if ($order->get_transaction_id() !== $notice_data['data']['outTradeNo']) {
            return false;
        }

        // update order status to failed, otherwise customer can't repay.
        if (in_array($notice_data['data']['status'], ['CLOSED', 'FAILED'])) {
            $order->update_status('failed');
            $order->add_order_note(sprintf(
                __('PayerMax Payment Failed, Trade Token: %s, Result message: %s', 'woocommerce-gateway-payermax'),
                $notice_data['data']['tradeToken'],
                $notice_data['data']['resultMsg']
            ));
            return false;
        }

        // verify totalAmount and currency
        if (!PayerMax_Helper::is_equal_payment_amount($order->get_total(), $notice_data['data']['totalAmount'], $order->get_currency())) {
            PayerMax_Logger::warning('Order out_trade_no, total or currency not match.');
            return false;
        }

        $order->payment_complete();

        $order->add_order_note(sprintf(
            __('PayerMax payment succeeded, Trade Token: %s', 'woocommerce-gateway-payermax'),
            $notice_data['data']['tradeToken']
        ));

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
            return false;
        }

        $transaction_id = $request_data['data']['outTradeNo'];

        $order = PayerMax_Helper::get_order_by_transaction_id($transaction_id);
        if (!$order) {
            return false;
        }

        // if refund successfully
        if ($order->get_transaction_id() === $transaction_id && $request_data['data']['status'] === 'REFUND_SUCCESS') {
            $order->add_order_note(sprintf(
                __('Refunded - Refund ID: %s - Refund Amount: %s', 'woocommerce-gateway-payermax'),
                $request_data['data']['refundTradeNo'],
                $request_data['data']['refundAmount']
            ), 1);
            return true;
        } else {
            $order->add_order_note(sprintf(
                __('Refund failed - Refund ID: %s - Error Message: %s', 'woocommerce-gateway-payermax'),
                $request_data['data']['refundTradeNo'],
                $request_data['msg']
            ));
            return false;
        }
    }
}
