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
    public $app_id;
    public $merchant_number;
    public $merchant_private_key;
    public $sandbox = "no";

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

        add_action('woocommerce_update_options_payment_gateways_' . self::ID, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' .  self::ID, array($this, 'thankyou_page'));
        add_filter('woocommerce_payment_complete_order_status', array($this, 'change_payment_complete_order_status'), 10, 3);

        // Customer Emails.
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);

        add_action('admin_notices', [$this, 'warning_no_debug_or_sandbox_on_production'], 0);
    }

    public function get_settings()
    {
        $this->title = $this->get_option('title', $this->method_title);
        $this->description = $this->get_option('description', $this->method_description);
        $this->instructions = $this->get_option('instructions', $this->description);
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
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        // TODO: check payment status here.
        if ($this->instructions) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)));
        }
    }

    /**
     * Change payment complete order status to completed for payleo orders.
     *
     * @since  3.1.0
     * @param  string         $status Current order status.
     * @param  int            $order_id Order ID.
     * @param  WC_Order|false $order Order object.
     * @return string
     */
    public function change_payment_complete_order_status($status, $order_id = 0, $order = false)
    {
        if ($order && self::ID === $order->get_payment_method()) {
            $status = 'completed';
        }
        return $status;
    }

    /**
     * Add content to the WC emails.
     *
     * @param WC_Order $order Order object.
     * @param bool     $sent_to_admin  Sent to admin.
     * @param bool     $plain_text Email format: plain text or HTML.
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
            echo wp_kses_post(wpautop(wptexturize($this->instructions)) . PHP_EOL);
        }
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order->get_total() > 0) {
            include_once dirname(__FILE__) . '/class-wc-gateway-payermax-request.php';

            $request = new WC_Gateway_PayerMax_Request($this);

            $url = $request->get_request_url($order);
            return array(
                'result'   => empty($url) ? 'failed' : 'success',
                'redirect' => $url,
            );
        } else {
            $order->payment_complete();
        }

        // Remove cart.
        WC()->cart->empty_cart();

        // Return thankyou redirect.
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        );
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
}
