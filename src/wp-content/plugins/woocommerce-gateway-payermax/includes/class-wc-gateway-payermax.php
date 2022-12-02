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
    public $endpoint = PAYERMAX_API_DEV_GATEWAY;

    const ICON_ID_KEY = 'woocommerce_payermax_icon_id';
    const ORDER_NOTIFY_CALLBACK = 'payermax-order-notify-v1';
    const REFUND_ORDER_NOTIFY_CALLBACK = 'payermax-refund-order-notify-v1';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'add_media_script']);

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


        add_action('admin_notices', [$this, 'missing_settings_warning']);
        add_action('admin_notices', [$this, 'endpoint_warning']);
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'manually_check_payment_status']);

        // @see https://woocommerce.com/document/wc_api-the-woocommerce-api-callback/#section-2
        // payermax order notify `https://domain.com/wc-api/payermax-order-notify-v1`
        add_action('woocommerce_api_' . self::ORDER_NOTIFY_CALLBACK, [$this, 'order_notify']);

        // payermax order notify `https://domain.com/wc-api/payermax-refund-order-notify-v1`
        add_action('woocommerce_api_' . self::REFUND_ORDER_NOTIFY_CALLBACK, [$this, 'order_refund_notify']);
    }

    function add_media_script($hook_suffix)
    {
        if ($hook_suffix === "woocommerce_page_wc-settings") {
            wp_enqueue_media();
        }
    }

    public function process_admin_options()
    {
        $post_data = $this->get_post_data();

        if (isset($post_data[self::ICON_ID_KEY])) {
            update_option(self::ICON_ID_KEY, $post_data[self::ICON_ID_KEY]);
        }

        parent::process_admin_options();
    }

    public function get_settings()
    {
        $this->title = $this->get_option('title', $this->method_title);
        $this->description = $this->get_option('description', $this->method_description);
        $this->app_id = $this->get_option('app_id');
        $this->merchant_number = $this->get_option('merchant_number');
        $this->merchant_public_key = $this->get_option('merchant_public_key');
        $this->merchant_private_key = $this->get_option('merchant_private_key');
        $this->endpoint = $this->get_option('endpoint');
        if ($this->get_option('enable_refunds') === 'yes' && !in_array('refunds', $this->supports)) {
            $this->supports[] = 'refunds';
        }
    }

    public function missing_settings_warning()
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->merchant_public_key && $this->merchant_private_key && $this->merchant_number && $this->app_id) {
            return;
        }

        $message = __('PayerMax Payment Gateway missing `App Id`, `Merchant Number`, `Public Key` or `Private Key` in settings. you must complete the settings to use it.', 'woocommerce-gateway-payermax');

        echo '<div class="notice notice-error">
        <p style="font-weight: bold;">' . esc_html($message) . ' ' . $this->get_settings_link() . '</p>
        </div>';
    }

    public function endpoint_warning()
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->endpoint !== PAYERMAX_API_GATEWAY) {
            $message = __("Don't use payermax test env on production.", 'woocommerce-gateway-payermax');
            echo '<div class="notice notice-warning">
            <p style="font-weight: bold;">' . esc_html($message) . ' ' . $this->get_settings_link() . '</p>
            </div>';
        }
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_general_properties()
    {
        $this->id   = self::ID;
        $this->icon = $this->get_custom_icon((int)get_option(self::ICON_ID_KEY));
        $this->method_title = __('PayerMax Payment', 'woocommerce-gateway-payermax');
        $this->method_description = sprintf(
            __('<a target="_blank" href="%s">PayerMax</a>, Your reliable global payment partner.', 'woocommerce-gateway-payermax'),
            'https://www.payermax.com/'
        );
        $this->has_fields = false;
        $this->supports = ['products'];
    }

    function admin_options()
    {
        // parent::admin_options();
        require_once WC_PAYERMAX_PLUGIN_DIR . '/includes/admin/admin-options.php';
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = require WC_PAYERMAX_PLUGIN_DIR . '/includes/admin/payermax-settings.php';
    }

    public static function get_payment_callback_url()
    {
        return WC()->api_request_url(self::ORDER_NOTIFY_CALLBACK);
    }

    public static function get_refund_callback_url()
    {
        return WC()->api_request_url(self::REFUND_ORDER_NOTIFY_CALLBACK);
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

            include_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-request.php';

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

            // Mark as on-hold (we're awaiting the payermax payment)
            $order->update_status('on-hold', __('Awaiting payermax payment', 'woocommerce-gateway-payermax'));

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
        include_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-request.php';
        $result = (new WC_Gateway_PayerMax_Request($this))->refund_transaction($order, $amount, $reason);

        // 3. record refund trade no if apply successfully.
        if (!is_wp_error($result) && $result['code'] === 'APPLY_SUCCESS') {
            $order->add_order_note(sprintf(
                __('Refund Status: %1$s - Refund ID: %2$s', 'woocommerce-gateway-payermax'),
                $result['data']['status'],
                $result['data']['refundTradeNo']
            ));
            return true;
        }

        // 4. else return wp error
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

        require_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-notify.php';

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

        require_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-notify.php';
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

        return parent::is_available();
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page($order_id)
    {
        if ($order = wc_get_order($order_id)) {
            include_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-request.php';

            $request = new WC_Gateway_PayerMax_Request($this);

            $this->verify_payermax_payment_status(
                $request->get_transaction_status($order),
                $order
            );
        }
    }

    /**
     * Add button to order details, so shop owner can check payment status via it manually.
     *
     * @param WC_Order $order Order.
     */
    public function manually_check_payment_status($order)
    {
        if (!$order instanceof WC_Order || !$order->get_id()) {
            return;
        }

        if ($order->get_status() === 'on-hold' && $order->get_payment_method() === self::ID) {
            include_once WC_PAYERMAX_PLUGIN_DIR . '/includes/admin/manually-check-payment-status.php';
        }
    }

    /**
     * Ajax check payment status
     *
     * @return string
     */
    public static function check_payermax_payment_status()
    {
        $order_id = $_POST['order_id'];
        $order = wc_get_order((int)$order_id);
        if (!$order->get_id()) {
            wp_die('Order Not Found');
        }

        include_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-request.php';

        $payment_methods = WC()->payment_gateways()->payment_gateways();

        /** @var self $gateway */
        $gateway = $payment_methods[self::ID];

        $request = new WC_Gateway_PayerMax_Request($gateway);

        $response = $gateway->verify_payermax_payment_status($request->get_transaction_status($order), $order);
        echo json_encode($response);
        wp_die();
    }

    public static function account_view_order(int $order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $payment_methods = WC()->payment_gateways()->payment_gateways();
        /** @var self $gateway */
        $gateway = $payment_methods[self::ID];

        if ($order->get_payment_method() === $gateway::ID && $order->get_status() === 'on-hold') {
            include_once WC_PAYERMAX_PLUGIN_DIR . '/includes/class-wc-gateway-payermax-request.php';
            $request = new WC_Gateway_PayerMax_Request($gateway);
            $transaction_status = $request->get_transaction_status($order);
            $gateway->verify_payermax_payment_status($transaction_status, $order);
        }
    }

    public static function clean_payermax_logs()
    {
        // the date of 30 days ago
        $results = PayerMax_Logger::remove_logs();

        // update lock
        update_option('clean_payermax_logs', date('Y-m-d'));

        echo json_encode($results);
        wp_die();
    }
}
