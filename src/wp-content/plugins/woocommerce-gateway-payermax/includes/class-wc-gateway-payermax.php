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
    public $sp_merchant_number = '';
    public $sp_merchant_auth_token = '';
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

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return parent::process_refund($order_id, $amount = null, $reason = '');
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
            '123'
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
