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

    public function __construct()
    {

        // Setup general properties.
        $this->setup_general_properties();

        // Load the form fields.
        $this->init_form_fields();
        // Load the settings.
        $this->init_settings();

        // Get settings.
        $this->title = $this->get_option('title', $this->method_title);
        $this->description = $this->get_option('description', $this->method_description);
        $this->instructions = $this->get_option('instructions', $this->description, $this->method_description);

        add_action('woocommerce_update_options_payment_gateways_' . self::ID, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' .  self::ID, array($this, 'thankyou_page'));
        add_filter('woocommerce_payment_complete_order_status', array($this, 'change_payment_complete_order_status'), 10, 3);

        // Customer Emails.
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }

    /**
     * Setup general properties for the gateway.
     */
    protected function setup_general_properties()
    {
        $this->id   = self::ID;
        $this->icon = WC_PAYERMAX_ASSETS_URI . 'assets/images/logo.png';
        $this->method_title = __('PayerMax Payment', 'woocommerce-gateway-stripe');
        $this->method_description = __('PayerMax payment systems.', 'woocommerce-gateway-stripe');
        $this->has_fields         = false;
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
}
