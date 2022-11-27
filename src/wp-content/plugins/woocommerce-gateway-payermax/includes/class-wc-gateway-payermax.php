<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Gateway_PayerMax class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_PayerMax extends WC_PayerMax_Payment_Gateway
{

    const ID = 'payermax';
    public $app_id = '';
    public $merchant_number = '';
    public $merchant_public_key = '';
    public $merchant_private_key = '';
    public $sandbox = "no";

    const ORDER_NOTIFY_CALLBACK = 'payermax-order-notify-v1';
    const REFUND_ORDER_NOTIFY_CALLBACK = 'payermax-refund-order-notify-v1';

    public function __construct()
    {
        // Setup general properties.
        $this->setup_general_properties();

        // Load the form fields.
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();

        // Get settings.
        $this->get_settings();

        // add a save hook for your settings
        add_action('woocommerce_update_options_payment_gateways_' . self::ID, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' .  self::ID, array($this, 'thankyou_page'));


        add_action('admin_notices', [$this, 'warning_no_debug_or_sandbox_on_production'], 0);

        // @see https://woocommerce.com/document/wc_api-the-woocommerce-api-callback/#section-2
        // payermax order notify `https://domain.com/wc-api/payermax-order-notify-v1`
        add_action('woocommerce_api_' . self::ORDER_NOTIFY_CALLBACK, [$this, 'order_notify']);
        add_action('woocommerce_api_' . self::REFUND_ORDER_NOTIFY_CALLBACK, [$this, 'order_refund_notify']);
    }

    public function get_settings()
    {
        $this->title = $this->get_option('title', $this->method_title);
        $this->description = $this->get_option('description', $this->method_description);
        $this->sandbox = $this->get_option('sandbox', 'no');
        $this->app_id = $this->get_option('app_id');
        $this->merchant_number = $this->get_option('merchant_number');
        $this->merchant_private_key = $this->get_option('merchant_private_key');
    }

    public function warning_no_debug_or_sandbox_on_production()
    {
        if ($this->sandbox === "no") {
            return;
        }
        $message = __("Don't use payermax sandbox mode in production.", 'woocommerce-gateway-payermax');
        $settings = '<a href="admin.php?page=wc-settings&tab=checkout&section=payermax">' . esc_html__('Settings', 'woocommerce-gateway-payermax') . '</a>';
        echo '<div class="notice notice-warning">
        <p style="font-weight: bold;">' . esc_html($message) . ' ' . $settings . '</p>
        </div>';
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_general_properties()
    {
        $this->id   = self::ID;
        $this->icon = WC_PAYERMAX_ASSETS_URI . 'assets/images/logo.png';
        $this->method_title = __('PayerMax Payment', 'woocommerce-gateway-payermax');
        $this->method_description = __('PayerMax payment settings. for more information, please visit our <a target="_blank" href="https://www.payermax.com/">official website</a>.', 'woocommerce-gateway-payermax');
        $this->has_fields = false;
        $this->supports = [
            'products',
            'refunds',
        ];
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = require WC_PAYERMAX_PLUGIN_PATH . '/includes/admin/payermax-settings.php';
    }

    /**
     * Process the payment and return the result.
     *
     * @see https://woocommerce.com/document/payment-gateway-api/
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {

        $order = wc_get_order($order_id);

        if ($order->get_total() > 0) {

            // Mark as on-hold (we're awaiting the payermax payment)
            $order->update_status('on-hold', __('Awaiting payermax payment', 'woocommerce-gateway-payermax'));

            include_once dirname(__FILE__) . '/class-wc-gateway-payermax-request.php';

            $request = new WC_Gateway_PayerMax_Request($this);

            $url = $request->get_request_url($order);

            // make retry if transaction is CLOSED.
            if ($url === 'CLOSED') {
                $url = $request->get_request_url($order);
            }

            if (empty($url)) {
                wc_add_notice(__("Payment error:  Can't get payment link.", 'woocommerce-gateway-payermax'), 'error');
                return;
            }

            // Remove cart.
            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $url,
            );
        } else {
            $order->payment_complete();

            // Remove cart.
            WC()->cart->empty_cart();

            // Return thankyou redirect.
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
    }

    /**
     * Process refund.
     *
     * If the gateway declares 'refunds' support, this will allow it to refund.
     * a passed in amount.
     *
     * @param  int        $order_id Order ID.
     * @param  float|null $amount Refund amount.
     * @param  string     $reason Refund reason.
     * @return boolean True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // 1. determine days the transaction has made till now, only can refund the transaction made in 180 days.
        $interval = (new DateTime())->diff($order->get_date_paid());
        PayerMax_Logger::info('Check Trade Days Diff:' . wc_print_r($interval, true));
        if ($interval->invert && $interval->days >= 180) {
            PayerMax_Logger::warning('Refund window is over. order ID: ' . $order_id);
            return new WP_Error('window_is_over', 'Refund window is over');
        }

        // 2. dispatch refund request.
        include_once dirname(__FILE__) . '/class-wc-gateway-payermax-request.php';
        $result = (new WC_Gateway_PayerMax_Request($this))
            ->refund_transaction($order, $amount, $reason);

        if (!is_wp_error($result) && $result['code'] === 'APPLY_SUCCESS') {
            $order->add_order_note(sprintf(
                __('Refund Status: %1$s - Refund ID: %2$s', 'woocommerce-gateway-payermax'),
                $result['data']['status'],
                $result['data']['refundTradeNo']
            ));
            return true;
        }

        new WP_Error(
            'error',
            __('Refund Failed.', 'woocommerce-gateway-payermax')
        );
    }


    public function order_notify()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || ('POST' !== $_SERVER['REQUEST_METHOD'])) {
            echo 'not supported REQUEST_METHOD';
            exit;
        }

        require_once __DIR__ . '/class-wc-gateway-payermax-notify.php';

        // @see request data from: https://docs.shareitpay.in/#/30?page_id=653&lang=zh-cn
        $request_body = file_get_contents('php://input');
        PayerMax_Logger::info("Payment notice, " . $request_body);

        // verify sign from payermax request header.
        if (!PayerMax_Helper::verify_sign(
            $request_body,
            $_SERVER['HTTP_SIGN'] ?? '',
            $this->merchant_public_key
        )) {
            PayerMax_Logger::warning("verify_sign failed, sign:" . $_SERVER['HTTP_SIGN']);
            echo "verify_sign failed";
            exit;
        }

        $result = WC_Gateway_PayerMax_Notify::payment_complete(json_decode($request_body, true));

        header('Content-Type: application/json');
        if (!$result) {
            status_header(422);
            echo json_encode([
                "code" => "failed",
                "msg" => "Failed"
            ]);
        } else {
            status_header(200);
            echo json_encode([
                "code" => "SUCCESS",
                "msg" => "Success"
            ]);
        }

        // Exit WordPress.
        exit;
    }

    public function order_refund_notify()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || ('POST' !== $_SERVER['REQUEST_METHOD'])) {
            echo 'not supported REQUEST_METHOD';
            exit;
        }

        require_once __DIR__ . '/class-wc-gateway-payermax-notify.php';
        // @see request data from: https://docs.payermax.com/#/30?page_id=657&si=1&lang=zh-cn
        $request_body = file_get_contents('php://input');
        PayerMax_Logger::info("refund notice: " . $request_body);

        // verify sign from payermax request header.
        if (!PayerMax_Helper::verify_sign(
            $request_body,
            $_SERVER['HTTP_SIGN'] ?? '',
            $this->merchant_public_key
        )) {
            PayerMax_Logger::warning("refund sign verify failed, sign:" . $_SERVER['HTTP_SIGN']);
            echo "refund sign verify failed";
            exit;
        }

        $result = WC_Gateway_PayerMax_Notify::refund_complete(json_decode($request_body, true));

        header('Content-Type: application/json');
        status_header(200);
        if ($result) {
            echo json_encode([
                "code" => "SUCCESS",
                "msg" => "Success"
            ]);
        } else {
            echo json_encode([
                "code" => "FAILED",
                "msg" => "Failed"
            ]);
        }

        exit;
    }

    /**
     * Check If The Gateway Is Available For Use.
     *
     * @return bool
     */
    public function is_available()
    {
        if (!$this->merchant_private_key || !$this->merchant_number || !$this->app_id) {
            PayerMax_Logger::debug('Please fill up the "Merchant private key" "merchant number" "app id" in your settings.');
            return false;
        }

        // check is woocommerce_currency available.
        if (
            !get_option('woocommerce_currency') ||
            !in_array(get_option('woocommerce_currency'), PayerMax::get_currencies())
        ) {
            PayerMax_Logger::debug('currency not available.');
            return false;
        }

        return parent::is_available();
    }

    /**
     * Can the order be refunded via PayerMax?
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    function can_refund_order($order)
    {
        return parent::can_refund_order($order);
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page($order_id)
    {
        if ($order = wc_get_order($order_id)) {
            include_once dirname(__FILE__) . '/class-wc-gateway-payermax-request.php';

            $request = new WC_Gateway_PayerMax_Request($this);

            $request->get_transaction_status($order);
        }

        // display some information about PayerMax
        echo '<h2>Thank you.</h2>';
    }
}
