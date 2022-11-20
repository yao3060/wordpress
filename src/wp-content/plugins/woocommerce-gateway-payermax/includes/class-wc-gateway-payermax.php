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

		$this->id   = self::ID;
		$this->icon = WC_PAYERMAX_ASSETS_URI . 'assets/images/logo.png';
		$this->method_title = __('PayerMax Payment', 'woocommerce-gateway-stripe');
		$this->method_description = __('PayerMax payment systems.', 'woocommerce-gateway-stripe');

		$this->title = $this->get_option('title', $this->method_title);
		$this->description = $this->get_option('description', $this->method_description);
		$this->instructions = $this->get_option('instructions', $this->description, $this->method_description);

		$this->supports = [
			'products',
			'refunds',
		];

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = require WC_PAYERMAX_PLUGIN_PATH . '/includes/admin/payermax-settings.php';
	}
}
