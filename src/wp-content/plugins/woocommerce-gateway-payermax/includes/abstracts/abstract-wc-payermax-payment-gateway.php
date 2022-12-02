<?php


abstract class WC_PayerMax_Payment_Gateway extends WC_Payment_Gateway
{
    /**
     * Return the gateway's icon.
     *
     * @return string
     */
    public function get_custom_icon(int $id)
    {
        return  wp_get_attachment_image_url($id, 'full');
    }

    public function get_settings_link()
    {
        return '<a href="admin.php?page=wc-settings&tab=checkout&section=payermax">' . __('Settings', 'woocommerce-gateway-payermax') . '</a>';
    }

    public function verify_payermax_payment_status($response_data, WC_Order $order)
    {
        if (is_wp_error($response_data) || !isset($response_data['data'])) {
            return $response_data;
        }

        if ($order->get_status() !== 'on-hold') {
            PayerMax_Logger::info("it is not on-hold order:" . wc_print_r(['id' => $order->get_id(), 'status' => $order->get_status()], true));
            return $response_data;
        }

        // transaction status is `SUCCESS` and transaction amount equal to order total.
        if (
            $response_data['data']['status'] === 'SUCCESS' &&
            PayerMax_Helper::is_equal_payment_amount($order->get_total(), $response_data['data']['totalAmount'], $order->get_currency())
        ) {
            $order->payment_complete();
            $order->add_order_note(sprintf(
                __('PayerMax payment succeeded, Trade Token: %s', 'woocommerce-gateway-payermax'),
                $response_data['data']['tradeToken']
            ));
        }

        // if closed or failed, update failed, otherwise can't repay.
        if (in_array($response_data['data']['status'], ['CLOSED', 'FAILED'])) {
            $order->update_status('failed');
            $order->add_order_note(sprintf(
                __('PayerMax Payment Failed, Trade Token: %s, Result message: %s', 'woocommerce-gateway-payermax'),
                $response_data['data']['tradeToken'],
                $response_data['data']['resultMsg']
            ));
        }

        return $response_data;
    }
}
