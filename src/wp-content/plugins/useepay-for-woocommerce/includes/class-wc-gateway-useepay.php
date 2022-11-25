<?php
/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);

/**
 * Class WC_Gateway_UseePay
 */
class WC_Gateway_UseePay extends WC_UseePay_Payment_Gateway
{
    private $return_url;
    private $payment_async_notify_url;
    private $refund_async_notify_url;

    private $threeds_pre_processing_url;
    private $threeds_processing_url;
    private $threeds_query_next_step_url;

    private $query_order_status_url;

    public function __construct()
    {
        parent::__construct();

        // WooCommerce required settings
        $this->icon               = apply_filters('woocommerce_useepay_icon', plugins_url('../assets/images/cards.svg', __FILE__));
        $this->has_fields         = true;
        $this->order_button_text  = __('Complete Order', 'useepay-for-woocommerce');
        $this->method_description = __('UseePay Standard redirects customers to UseePay to enter their payment information.', 'useepay-for-woocommerce');
        $this->supports           = array(
            'products',
            'refunds',
        );

        $this->return_url = WC()->api_request_url('WC_useepay_return');

        // async notify
        $this->payment_async_notify_url = WC()->api_request_url('WC_useepay_notify');
        $this->refund_async_notify_url  = WC()->api_request_url('WC_useepay_refund_notify');

        $this->threeds_pre_processing_url  = WC()->api_request_url('WC_useepay_threeds_form_post');
        $this->threeds_processing_url      = WC()->api_request_url('WC_useepay_threeds_handle');
        $this->threeds_query_next_step_url = WC()->api_request_url('WC_useepay_threeds_query_next_step');

        $this->query_order_status_url = WC()->api_request_url("WC_useepay_query_order_status");

        // Actions
        add_filter('http_request_version', array($this, 'use_http_1_1'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options')); // WC >= 2.0

        add_action('woocommerce_api_wc_useepay_return', array($this, 'return_handle'));

        add_action('woocommerce_api_wc_useepay_threeds_form_post', array($this, 'threeds_pre_processing'));
        add_action('woocommerce_api_wc_useepay_threeds_handle', array($this, 'threeds_processing'));
        add_action('woocommerce_api_wc_useepay_threeds_query_next_step', array($this, 'threeds_query_next_step'));

        add_action('woocommerce_api_wc_useepay_notify', array($this, 'payment_async_notify_handle'));
        add_action('woocommerce_api_wc_useepay_refund_notify', array($this, 'refund_async_notify_handle'));

        add_action('woocommerce_api_wc_useepay_query_order_status', array($this, 'query_order_status'));

        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'wc_useepay_display_order_meta_for_admin'));
    }

    /**
     * Admin Panel Option
     * - Options for bits like 'title' and account etc.
     *
     * @access public
     * @return void
     */
    public function admin_options()
    {
        echo '<h1>' . _e('UseePay', 'useepay-for-woocommerce') . '</h1>';
        echo '<p>' . _e('UseePay is a simple, secure and fast online payment gateway, customer can pay via debit card or credit card.', 'useepay-for-woocommerce') . '</p>';
        echo '<table class="form-table">';
        echo $this->generate_settings_html();
        echo '</table>';
    }


    /**
     * Set the HTTP version for the remote posts
     * https://developer.wordpress.org/reference/hooks/http_request_version/
     */
    public function use_http_1_1($httpversion)
    {
        return '1.1';
    }

    /**
     * Validate form fields which rendered in payment_fields()
     */
    public function validate_fields()
    {
        if (!Wc_UseePay_Util::is_valid_card_number($_POST['cardNumber'])) {
            wc_add_notice(__('Credit card number you entered is invalid.', 'useepay-for-woocommerce'), 'error');
        }


        if (!Wc_UseePay_Util::is_valid_expiry($_POST['cardExpirationMonth'], $_POST['cardExpirationYear'])) {
            wc_add_notice(__('Card expiration date is not valid.', 'useepay-for-woocommerce'), 'error');
        }
        if (!Wc_UseePay_Util::is_valid_cvv_number($_POST['securityCode'])) {
            wc_add_notice(__('Card verification number (CVV) is not valid. You can find this number on your credit card.', 'useepay-for-woocommerce'), 'error');
        }
    }


    /**
     * Render Credit/Debit Card form
     */
    public function payment_fields()
    {
        $cardNumber = isset($_REQUEST['cardNumber']) ? esc_attr($_REQUEST['cardNumber']) : '';
        $cardExpirationMonth = isset($_REQUEST['cardExpirationMonth']) ? esc_attr($_REQUEST['cardExpirationMonth']) : '';
        $cardExpirationYear = isset($_REQUEST['cardExpirationYear']) ? esc_attr($_REQUEST['cardExpirationYear']) : '';
?>
        <style>
            .payment_box.payment_method_useepay {
                font-size: 0.75rem;
                padding: 0 !important;
                border-radius: 10px;
                background-color: transparent !important;
            }

            .payment_box.payment_method_useepay:before {
                display: none !important;
            }

            label[for=payment_method_useepay] img {
                width: 60%;
                margin: 0 auto !important;
            }

            .payment_box.payment_method_useepay input {
                padding: 12px;
                border-color: #ddd !important;
                border-radius: 10px !important;
            }

            .payment_box.payment_method_useepay select {
                padding: 0 12px;
                width: 49%;
                height: 2.5rem;
                border-radius: 10px;
                border-color: #ddd !important;
                border-radius: 10px !important;
            }

            .payment_box.payment_method_useepay .select-container {
                display: flex !important;
                width: 90% !important;
                margin: auto !important;
                justify-content: space-between !important;
            }

            .payment_box.payment_method_useepay label {
                display: block;
                width: 100%;
                font-size: 0.75rem !important;
                font-weight: 500;
                margin-bottom: 8px;
            }

            .payment_box.payment_method_useepay .form-row {
                width: 100%;
                padding: 0.5rem 1rem !important;
            }

            .payment_methods .useepay-security-code-hint-section img {
                margin: 0 auto;
                width: 10rem;
                max-height: 3rem !important;
                padding-right: 1rem !important;
                padding-left: 1.5rem;
            }

            @media screen and (max-width: 400px) {
                label[for=payment_method_useepay] img {
                    width: 60%;
                    margin: 0 auto !important;
                }
            }
        </style>

        <?php
        $useepay_js = plugins_url() . "/useepay-for-woocommerce/assets/js/useepay-threeds2-utils.js";
        echo '<script type="text/javascript" src="' . $useepay_js . '"></script>';
        ?>

        <input id="browserInfo" name="browserInfo" type="hidden" value="" />
        <div id="threedsContainer" hidden="hidden"></div>
        <input id="isGather" type="hidden" value="0" />

        <p class="form-row validate-required">
            <label>Card number <span class="required">*</span></label>
            <input id="cardNo" class="input-text" type="text" size="19" maxlength="19" name="cardNumber" value="<?php echo $cardNumber; ?>" />
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label>Expiration date <span class="required">*</span></label>
        <div class="select-container">
            <input id="cardExpirationMonth" class="input-text" placeholder="MM" type="text" maxlength="19" name="cardExpirationMonth" value="<?php echo $cardExpirationMonth; ?>" style="width: 50%;" />
            <input id="cardExpirationYear" class="input-text" placeholder="YY" type="text" maxlength="19" name="cardExpirationYear" value="<?php echo $cardExpirationYear; ?>" style="width: 50%;" />
        </div>
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first validate-required">
            <label>CVV <span class="required">*</span></label>
            <input id="cvv" class="input-text" type="text" size="4" maxlength="4" name="securityCode" value="" />
        </p>
        <?php
        $cvv_hint_img = plugins_url() . "/useepay-for-woocommerce/assets/images/card-security-code-hint.png";
        $cvv_hint_img = apply_filters('useepay-cvv-image-hint-src', $cvv_hint_img);
        echo '<div class="useepay-security-code-hint-section">';
        echo '<img src="' . $cvv_hint_img . '" />';
        echo '</div>';
        ?>
        <div class="clear"></div>
        <script>
            document.getElementsByClassName("checkout woocommerce-checkout").class = "checkout woocommerce-checkout";
            if (document.getElementById("browserInfo")) {
                var browserInfo = window.ThreedDS2Utils.getBrowserInfo();
                document.getElementById("browserInfo").value = JSON.stringify(browserInfo);
            }
        </script>
<?php
    }


    /**
     * Process Payment.
     *
     * Process the payment. Override this in your gateway. When implemented, this should.
     * return the success and redirect in an array. e.g:
     *
     *        return array(
     *            'result'   => 'success',
     *            'redirect' => $this->get_return_url( $order )
     *        );
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        WC_UseePay_Logger::log('Process payment with order_id: ' . $order_id);
        $order = wc_get_order($order_id);
        WC_UseePay_Logger::log('Process payment with order_key: ' . $order->get_order_key());
        if (empty($order)) {
            wc_add_notice('Order not exist!', 'error');
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }
        $parameters = $this->build_payment_request_parameters($order);
        WC_UseePay_Logger::log('Process payment with request parameters: ' . var_export($parameters, true));

        try {
            $response       = WC_UseePay_API::request($parameters);
            $result_code    = isset($response['resultCode']) ? $response['resultCode'] : null;
            $error_code     = isset($response['errorCode']) ? $response['errorCode'] : null;
            $error_msg      = isset($response['errorMsg']) ? $response['errorMsg'] : null;
            $transaction_id = isset($response['reference']) ? $response['reference'] : null;
            if ($result_code == 'succeed') {
                $this->do_order_complete_tasks($order, $transaction_id);
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else if ($result_code == 'pending_review') {

                $this->do_order_on_hold_tasks($order, $transaction_id);

                return array(
                    'result'   => 'success',
                    'redirect' => empty($response['redirectUrl']) ? $this->get_return_url($order) : $response['redirectUrl']
                );
            } else if (($result_code == 'pending' || $result_code == 'challenge' || $result_code == 'gather') && $error_code == '3200') {
                // 3DS transaction
                if ($result_code == "gather") {
                    return array(
                        'result'   => 'success',
                        'redirect' => add_query_arg('resp', urlencode(json_encode($response)), $this->threeds_pre_processing_url)
                    );
                } else {
                    return array(
                        'result'   => 'success',
                        'redirect' => empty($response['redirectUrl']) ? $this->get_return_url($order) : $response['redirectUrl']
                    );
                }
            } else {
                $transaction_error_message = $error_code . "," . $error_msg;
                $this->mark_as_failed_payment($order, $transaction_error_message);
                wc_add_notice(__('(Transaction Error) something is wrong.', 'useepay-for-woocommerce') . ' ' . $transaction_error_message, 'error');
                return array(
                    'result'   => 'fail',
                    'redirect' => '',
                );
            }
        } catch (WC_UseePay_Exception $e) {
            wc_add_notice($e->get_error_message(), 'error');
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }
    }

    /**
     * Process refund.
     *
     * If the gateway declares 'refunds' support, this will allow it to refund.
     * a passed in amount.
     *
     * @param int $order_id Order ID.
     * @param float $amount Refund amount.
     * @param string $reason Refund reason.
     * @return boolean True or false based on success, or a WP_Error object.
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$this->can_refund_order($order)) {
            wc_add_wp_error_notices(new WP_Error('error', __('Refund failed.', 'useepay-for-woocommerce')));
            return false;
        }

        $parameters = $this->build_refund_request_parameters($order, $amount, $reason);

        try {
            $response       = WC_UseePay_API::request($parameters);
            $result_code    = $response['resultCode'];
            $error_code     = $response['errorCode'];
            $error_msg      = $response['errorMsg'];
            $transaction_id = $response['reference'];
            if ($result_code == "pending" && $error_code == "0000") {
                /* translators: 1: Refund amount, 2: Refund ID */
                $order->add_order_note(sprintf('Credit card refund approved;Refund ID: %s', $transaction_id));
                return true;
            } else {
                wc_add_notice($result_code . "," . $error_msg, 'error');
                return false;
            }
        } catch (WC_UseePay_Exception $e) {
            WC_UseePay_Logger::log('Refund Failed: ' . $e->get_localization_message());
            wc_add_wp_error_notices(new  WP_Error('error', $e->get_localization_message()));
            return false;
        }
    }

    /**
     * Can the order be refunded via Useepay?
     *
     * @param WC_Order $order Order object.
     * @return bool
     */
    function can_refund_order($order)
    {
        $has_api_creds = $this->get_merchant_no() && $this->get_app_id() && $this->get_secret_key();

        return $order && $order->get_id() && $has_api_creds;
    }

    /**
     * Build parameters for payment
     *
     * @param $order
     * @return array
     */
    function build_payment_request_parameters($order)
    {
        $currency    = $order->get_currency();    //	交易币种
        $amount      = Wc_UseePay_Util::convert_woo_price_to_useepay_price($currency, $order->get_total(), 2);
        $notifyUrl   = $this->payment_async_notify_url; //	商户通知地址
        $redirectUrl = $this->return_url; //	商户通知地址

        $goods_name_desc = $this->get_goods_name_desc($order);
        $goodsInfo       = [];
        foreach ($order->get_items() as $lineitem) {
            $_product         = $order->get_product_from_item($lineitem);
            if (!$_product) {
                continue;
            }
            $temp             = [];
            $temp['name']     = strval($lineitem->get_name());
            $temp['sku']      = strval($_product->get_data()['sku']);
            $temp['price']    = strval(Wc_UseePay_Util::convert_woo_price_to_useepay_price($currency, $_product->get_data()['sale_price']));
            $temp['quantity'] = strval($lineitem->get_data()['quantity']);
            $goodsInfo[]      = $temp;
        }
        $billingStreet  = $this->clean($order->get_billing_address_1());
        $billingHouseNo = $this->clean($order->get_billing_address_2());
        $billingCity    = $order->get_billing_city(); //	账单城市
        $billingState   = $order->get_billing_state(); //	账单州省
        if ($billingState == null || strlen(trim($billingState)) <= 0) {
            $billingState = $billingCity;
        }

        $shippingStreet  = $this->clean($order->get_shipping_address_1());
        $shippingHouseNo = $this->clean($order->get_shipping_address_2());
        $shippingCity    = $order->get_shipping_city(); //	收货人城市
        $shippingState   = $order->get_shipping_state(); //	收货人州省
        if ($shippingState == null || strlen(trim($shippingState)) <= 0) {
            $shippingState = $billingState;
        }

        $shippingStreet = empty($shippingStreet) ? $billingStreet : $shippingStreet;
        $shippingCity   = empty($shippingCity) ? $billingCity : $shippingCity;
        $shippingState  = empty($shippingState) ? $billingState : $shippingState;
        $orderInfo      = [
            "subject"         => trim($goods_name_desc["product_names"]),
            "goodsInfo"       => $goodsInfo,
            "shippingAddress" => [
                "email"      => trim($order->get_billing_email()),
                "phoneNo"    => trim($order->get_billing_phone()),
                "firstName"  => empty(trim($order->get_shipping_first_name())) ? trim($order->get_billing_first_name()) : trim($order->get_shipping_first_name()),
                "lastName"   => empty(trim($order->get_shipping_last_name())) ? trim($order->get_billing_last_name()) : trim($order->get_shipping_last_name()),
                "street"     => trim($shippingStreet),
                "postalCode" => empty(trim($order->get_shipping_postcode())) ? trim($order->get_billing_postcode()) : trim($order->get_shipping_postcode()),
                "city"       => trim($shippingCity),
                "state"      => trim($shippingState),
                "country"    => empty(trim($order->get_shipping_country())) ? trim($order->get_billing_country()) : trim($order->get_shipping_country()),
                "houseNo"    => $shippingHouseNo,
            ]
        ];

        $userInfo = [
            'userId'     => trim($order->user_id), // 用户在商户系统的id
            'ip'         => trim($order->get_customer_ip_address()),  // 用户下单IP
            'email'      => trim($order->get_billing_email()),     // 用户email
            'phoneNo'    => trim($order->get_billing_phone()), // 用户phone
            'createTime' => '', // 用户的注册时间
        ];

        $cardHolderNumber    = str_replace(" ", "", $_POST['cardNumber']); //	卡号
        $cardHolderFirstName = trim($order->get_billing_first_name());    //	持卡人名
        $cardHolderLastName  = trim($order->get_billing_last_name());    //	持卡人姓
        $cardExpirationMonth = trim($_POST['cardExpirationMonth']);    //	持卡人卡片有效期月
        $cardExpirationYear  = trim($_POST['cardExpirationYear']);    //	持卡人卡片有效期年
        $securityCode        = $_POST['securityCode']; //	持卡人卡片安全码

        $browserInfo = json_decode("\"" . $_POST["browserInfo"] . "\"", true);
        $browserInfo = json_decode($browserInfo, true);

        $payerInfo   = [
            'paymentMethod'       => 'credit_card',
            'authorizationMethod' => 'cvv',
            'cardNo'              => trim($cardHolderNumber),
            'expirationMonth'     => substr($cardExpirationMonth, 0, 2),
            'expirationYear'      => substr($cardExpirationYear, -2, 2),
            'cvv'                 => trim($securityCode),
            'firstName'           => trim($cardHolderFirstName),
            'lastName'            => trim($cardHolderLastName),
            'billingDescriptor'   => '',
            'billingAddress'      => [
                'houseNo'    => $billingHouseNo,
                'email'      => trim($order->get_billing_email()),
                'phoneNo'    => trim($order->get_billing_phone()),
                'firstName'  => trim($order->get_billing_first_name()),
                'lastName'   => trim($order->get_billing_last_name()),
                'street'     => trim($billingStreet), // 账单街道
                'postalCode' => trim($order->get_billing_postcode()), // 账单邮政编码
                'city'       => trim($billingCity),   // 账单城市
                'state'      => trim($billingState),  // 账单省份
                'country'    => trim($order->get_billing_country()) // 账单国家英文二字码
            ],
            "threeDS2RequestData" => [
                "deviceChannel"            => "browser",
                "acceptHeader"             => "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
                "javaEnabled"              => $browserInfo["javaEnabled"],
                "screenHeight"             => $browserInfo["screenHeight"],
                "screenWidth"              => $browserInfo["screenWidth"],
                "timeZoneOffset"           => $browserInfo["timeZoneOffset"],
                "language"                 => $browserInfo["language"],
                "userAgent"                => $browserInfo["userAgent"],
                "colorDepth"               => $browserInfo["colorDepth"],
                "threeDSMethodCallbackUrl" => add_query_arg('order_id', $order->get_id(), $this->threeds_processing_url),
            ]
        ];
        $deviceInfo = null;
        if (isset($_COOKIE['CO_XD_FP_ID'])) {
            $fingerPrintId = $_COOKIE["CO_XD_FP_ID"];
            $deviceInfo    = [
                'fingerPrintId' => trim($fingerPrintId), // 设备指纹
            ];
        }


        $transaction_id = $order->get_transaction_id();
        if (!$transaction_id) {
            $transaction_id = $this->get_useepay_order_transaction_id($order);
            $this->update_transaction_id($transaction_id, $order);
        }

        //组装参数
        return array(
            'transactionType'           => 'pay',
            'version'                   => '1.0',
            'signType'                  => 'MD5',
            'merchantNo'                => $this->get_merchant_no(),
            'transactionId'             => $transaction_id,
            'transactionExpirationTime' => '2880',
            'appId'                     => $this->get_app_id(),
            'amount'                    => $amount,
            'currency'                  => $currency,
            'notifyUrl'                 => $notifyUrl,
            'redirectUrl'               => $redirectUrl,
            'echoParam'                 => $order->get_id(),
            'reserved'                  => '{"pluginName":"woocommerce","pluginVersion":"1.0.18","origVersion":"2.0"}',

            'orderInfo'  => json_encode($orderInfo),
            'userInfo'   => json_encode($userInfo),
            'payerInfo'  => json_encode($payerInfo),
            'deviceInfo' => empty($deviceInfo) ? null : json_encode($deviceInfo),
        );
    }

    /**
     * Sets transaction ID to the WC order.
     *
     * @param string               $transaction_id The transaction ID to set.
     * @param WC_Order             $wc_order The order to set transaction ID to.
     * @param LoggerInterface|null $logger The logger to log errors.
     *
     * @return bool
     */
    protected function update_transaction_id(string $transaction_id, WC_Order $wc_order): bool
    {
        try {
            $wc_order->set_transaction_id($transaction_id);
            $wc_order->save();

            $wc_order->add_order_note(
                sprintf(
                    /* translators: %s is the Useepay transaction ID */
                    __('Useepay transaction ID: %s', 'woocommerce-useepay-payments'),
                    $transaction_id
                )
            );

            return true;
        } catch (Exception $exception) {
            WC_UseePay_Logger::log('Failed to set transaction ID ' . $transaction_id . $exception->getMessage());
            return false;
        }
    }

    /**
     * Build parameters for refund
     *
     * @param $order
     * @param null $amount
     * @param string $reason
     * @return array
     */
    function build_refund_request_parameters($order, $amount = null, $reason = '')
    {
        $uniqId        = md5(uniqid(microtime(true), true));
        $transactionId = time() . substr($uniqId, 0, 6);

        $currency = $order->get_currency();    //	交易币种
        $amount   = Wc_UseePay_Util::convert_woo_price_to_useepay_price($currency, $amount, 2);
        //组装参数
        $parameters = array(
            'transactionType'       => 'refund',
            'version'               => '1.0',
            'signType'              => 'MD5',
            'merchantNo'            => self::get_merchant_no(),
            'transactionId'         => $transactionId,
            'originalTransactionId' => $order->get_transaction_id(),
            'amount'                => $amount,
            'notifyUrl'             => $this->refund_async_notify_url,
            'echoParam'             => $order->get_id(),
            'reserved'              => '{"pluginName":"woocommerce","pluginVersion":"1.0.18","origVersion":"2.0"}',
        );

        WC_UseePay_Logger::log("useepay request:" . var_export($parameters, true));
        return $parameters;
    }

    protected function mark_as_failed_payment($order, $message)
    {
        $order->add_order_note(sprintf("Credit card payment failed with message: '%s'", $message));
    }

    protected function do_order_complete_tasks($order, $transaction_id)
    {
        global $woocommerce;
        if ($order->get_status() == 'completed')
            return;

        if ($order->get_status() == 'pending') {
            $order->add_order_note(
                sprintf("Credit card payment completed with transaction id of '%s'", $transaction_id)
            );
        }

        $order->payment_complete();
        $woocommerce->cart->empty_cart();

        unset(WC()->session->get_session_data()['order_awaiting_payment']);
    }


    protected function do_order_on_hold_tasks($order, $transaction_id)
    {
        global $woocommerce;
        if ($order->get_status() == 'completed')
            return;

        if ($order->get_status() == 'pending') {
            $order->add_order_note(
                sprintf("Credit card authorize with transaction id of '%s'", $transaction_id)
            );
        }

        $order->update_status('on-hold', __('Awaiting capture'));

        $woocommerce->cart->empty_cart();
        unset(WC()->session->get_session_data()['order_awaiting_payment']);
    }

    /**
     * Build the parameters for querying 3ds next-step
     *
     * @param $threeds_trans_id
     * @return array
     */
    private function build_threeds_query_request_parameters($threeds_trans_id, $threeDSMethodData)
    {
        if (empty($threeDSMethodData)) {
            return array(
                'transactionType'      => 'threeDSMethodCompletion',
                'version'              => '1.0',
                'signType'             => 'MD5',
                'merchantNo'           => self::get_merchant_no(),
                'threeDSServerTransId' => $threeds_trans_id,
                'threeDSCompleted'     => 'N'
            );
        } else {
            return array(
                'transactionType'      => 'threeDSMethodCompletion',
                'version'              => '1.0',
                'signType'             => 'MD5',
                'merchantNo'           => self::get_merchant_no(),
                'threeDSServerTransId' => $threeds_trans_id,
                'threeDSCompleted'     => 'Y'
            );
        }
    }

    function return_handle()
    {
        global $woocommerce;
        if (!empty($_GET) && !empty($_GET['resp'])) {
            $_POST = json_decode(stripslashes(urldecode($_GET['resp'])), true);
        } else if (!empty($_GET) && !empty($_GET['resultCode'])) {
            $_POST = stripslashes_deep($_GET);
            unset($_POST['wc-api']);
            foreach ($_POST as $key => $value) {
                $_POST[$key] = urldecode($value);
            }
        } else {
            $_POST = stripslashes_deep($_POST);
        }
        $order_id = $_POST['echoParam']; //	系统订单号
        $order    = wc_get_order($order_id);

        WC_UseePay_Logger::log($order_id . ' return response : ' . var_export($_POST, true));
        if (!Wc_UseePay_Util::verify_signature($_POST, $this->get_secret_key())) {
            WC_UseePay_Logger::log($_POST['reference'] . '	verifyByArray in return failed!');
            return;
        }

        //处理返回结果
        $result_code    = $_POST['resultCode'];
        $error_code     = $_POST['errorCode'];
        $errorMsg       = $_POST['errorMsg'];
        $transaction_id = $_POST['echoParam'];

        if ($result_code == "succeed" || 'completed' === $order->stauts || 'processing' === $order->status) {
            // payment successfully
            $woocommerce->cart->empty_cart();
            WC_UseePay_Logger::log($_POST['transactionId'] . ', ' . $transaction_id . ' received in return.');
            $this->do_order_complete_tasks($order, $transaction_id);
            $appendHtml = "window.parent.location.href='" . $this->get_return_url($order) . "';";
        } else if ('failed' == $result_code || 'closed' == $result_code || 'cancelled' == $result_code) {
            WC_UseePay_Logger::log($_POST['transactionId'] . ', ' . $transaction_id . ' received in return.');
            $order->update_status("failed");
            $appendHtml = "window.parent.location.href='" . $this->get_return_url($order) . "';";
        } else if ('pending' == $result_code) {
            $useepay_css = plugins_url() . "/useepay-for-woocommerce/assets/css/useepay-styles.css";

            echo '<link rel="stylesheet" type="text/css" href="' . $useepay_css . '">';
            echo '<script type="text/javascript" src="' . plugins_url() . "/useepay-for-woocommerce/assets/js/jquery-1.9.1.min.js" . '"></script>';
            echo '<script type="text/javascript" src="' . plugins_url() . "/useepay-for-woocommerce/assets/js/useepay-1.0.1.js" . '"></script>';
            $loading_img      = plugins_url() . "/useepay-for-woocommerce/assets/images/loading.gif";
            $loading_text     = __('Processing...', 'useepay-for-woocommerce');
            $review_order_url = $this->get_return_url($order);
            $poll_url         = add_query_arg('order_id', $order_id, $this->query_order_status_url);
            $order_status     = $order->get_status();
            WC_UseePay_Logger::log('return_handle: order status: ' . $order_status);
            echo "
                <script type='text/javascript'>
                    $(function() {
                        window.UseePay.showPageLoading('$loading_img', '$loading_text');
                        window.UseePay.pollOrderStatusAndRedirect('$poll_url', '$review_order_url', '$order_status');
                    });
                </script>
                ";
            exit;
        } else {
            if (!empty($_POST['redirectUrl'])) {
                $woocommerce->cart->empty_cart();
                unset(WC()->session->get_session_data()['order_awaiting_payment']);
            }
            if ('pending' == $result_code) {
                $order->update_status("AUTHENTICATION REQUIRED");
            }
            $appendHtml = "window.parent.location.href='" . $this->get_return_url($order) . "';";
        }
        $html = "<div></div><script type='text/javascript'>if (window.parent.document.getElementById(\"isGather\")) { window.parent.postMessage(" . json_encode($_POST) . ", location.protocol + \"//\" + location.host);} else {$appendHtml}</script>";
        echo $html;
        exit;
    }

    /**
     *  pre process for threeds.
     */
    function threeds_pre_processing()
    {
        $resp_json_str = json_encode(stripslashes_deep(urldecode($_GET['resp'])));
        WC_UseePay_Logger::log('threeds_handle begin to form post with json str: ' . var_export($resp_json_str, true));
        $resp_json = json_decode($resp_json_str, true);
        $resp_json = json_decode($resp_json, true);
        WC_UseePay_Logger::log('threeds_handle begin to form post with json: ' . var_export($resp_json, true));
        $threeDSMethodCompletionUrl = add_query_arg(array('order_id' => $resp_json['transactionId'], 'threeDSServerTransId' => $resp_json['threeDSServerTransId']), $this->threeds_processing_url);
        WC_UseePay_Logger::log('threeds_handle begin to form post with threeDSMethodCompletionUrl: ' . $threeDSMethodCompletionUrl);
        $useepay_js   = plugins_url() . "/useepay-for-woocommerce/assets/js/useepay-1.0.1.js";
        $useepay_utils_js   = plugins_url() . "/useepay-for-woocommerce/assets/js/useepay-threeds2-utils.js";
        $useepay_css  = plugins_url() . "/useepay-for-woocommerce/assets/css/useepay-styles.css";
        $loading_img  = plugins_url() . "/useepay-for-woocommerce/assets/images/loading.gif";
        $loading_text = __('Processing...', 'useepay-for-woocommerce');

        echo '<link rel="stylesheet" type="text/css" href="' . $useepay_css . '">';
        echo '<script type="text/javascript" src="' . plugins_url() . "/useepay-for-woocommerce/assets/js/jquery-1.9.1.min.js" . '"></script>';
        echo '<script type="text/javascript" src="' . $useepay_js . '"></script>';
        echo '<script type="text/javascript" src="' . $useepay_utils_js . '"></script>';

        echo "
            <script type='text/javascript'>
                $(function() {
                  window.UseePay.showPageLoading('$loading_img', '$loading_text');
                    let resp = JSON.parse($resp_json_str);
                    let redirectParam = JSON.parse(resp.redirectParam);
                    let threeDSMethodURL = resp.redirectUrl;
                    window.UseePay.createAndSubmitFormFor3ds('$threeDSMethodCompletionUrl', threeDSMethodURL, redirectParam , 'POST');
                })
            </script>
            ";
        exit;
    }

    /**
     * processing threeds after form posting
     */
    function threeds_processing()
    {
        WC_UseePay_Logger::log('threeds_handle invoked!');
        WC_UseePay_Logger::log('threeds_handle GET:' . var_export($_GET, true));
        WC_UseePay_Logger::log('threeds_handle POST:' . var_export($_POST, true));
        $threeds_trans_id = $_GET['threeDSServerTransId'];
        $order_id         = $_GET['order_id'];
        $threeDSMethodData        = $_GET['threeDSMethodData'];
        $order            = wc_get_order($order_id);
        $review_order_url = $this->get_return_url($order);
        $poll_url         = add_query_arg('order_id', $order_id, $this->query_order_status_url);
        $url              = add_query_arg(array('threeds_trans_id' => $threeds_trans_id, 'threeDSMethodData' => $threeDSMethodData), $this->threeds_query_next_step_url);

        $useepay_css  = plugins_url() . "/useepay-for-woocommerce/assets/css/useepay-styles.css";
        $loading_img  = plugins_url() . "/useepay-for-woocommerce/assets/images/loading.gif";
        $loading_text = __('Processing...', 'useepay-for-woocommerce');
        echo '<link rel="stylesheet" type="text/css" href="' . $useepay_css . '">';
        echo '<script type="text/javascript" src="' . plugins_url() . "/useepay-for-woocommerce/assets/js/jquery-1.9.1.min.js" . '"></script>';
        echo '<script type="text/javascript" src="' . plugins_url() . "/useepay-for-woocommerce/assets/js/useepay-1.0.1.js" . '"></script>';
        echo "
            <script type='text/javascript'>
                $(function() {
                    window.UseePay.showPageLoading('$loading_img', '$loading_text');
                    window.UseePay.query3dsNextStepAndRedirect('$url', '$this->return_url', '$poll_url', '$review_order_url');
                });
            </script>
        ";
        exit;
    }


    function threeds_query_next_step()
    {
        try {
            $response = WC_UseePay_API::request($this->build_threeds_query_request_parameters(isset($_GET['threeds_trans_id']) ? $_GET['threeds_trans_id'] : null, isset($_GET['threeDSMethodData']) ? $_GET['threeDSMethodData'] : null));
            exit(json_encode($response));
        } catch (WC_UseePay_Exception $e) {
            exit(-1);
        }
    }

    /**
     * UseePay async notify handle for payment
     */
    function payment_async_notify_handle()
    {
        sleep(3);
        $_POST   = stripslashes_deep($_POST);
        $orderId = $_POST['echoParam'];
        $order   = wc_get_order($orderId);
        WC_UseePay_Logger::log($orderId . ' notify response : ' . var_export($_POST, true));
        if (!Wc_UseePay_Util::verify_signature($_POST, $this->get_secret_key())) {
            WC_UseePay_Logger::log($_POST['reference'] . '	verify signature in notice failed!');
            return;
        }

        //处理返回结果
        $resultCode = isset($_POST['resultCode']) ? $_POST['resultCode'] : null;
        $errorMsg   = isset($_POST['errorMsg']) ? $_POST['errorMsg'] : null;
        $reference  = isset($_POST['reference']) ? $_POST['reference'] : null;

        if ('succeed' != $resultCode && 'failed' != $resultCode && 'closed' != $resultCode && 'cancelled' != $resultCode && 'pending_review' != $resultCode) {
            WC_UseePay_Logger::log($_POST['transactionId'] . ', ' . $orderId . ' 支付订单非终态，忽略该异步通知');
            exit();
        }

        /**
         * 订单处于completed /processing 则不修改订单状态
         * Order status. Options: pending, processing, on-hold, completed, cancelled, refunded, failed and trash. Default is pending.
         */
        if ('completed' === $order->status || 'processing' === $order->status) {
            return;
        }

        WC_UseePay_Logger::log($_POST['transactionId'] . ', ' . $orderId . ' received in notice.');
        if ($resultCode == "succeed") {     // 支付成功
            $this->do_order_complete_tasks($order, $reference);
            echo 'OK';
        } else if ('failed' == $resultCode || 'closed' == $resultCode || 'cancelled' == $resultCode) {
            global $woocommerce;
            $order->update_status("failed");
            $woocommerce->cart->empty_cart();
            unset(WC()->session->get_session_data()['order_awaiting_payment']);
            echo 'OK';
        } else if ("pending_review" == $resultCode) {
            WC_UseePay_Logger::log($_POST['transactionId'] . ', ' . $orderId . ' pending_review');
            $this->do_order_on_hold_tasks($order, $orderId);
            echo 'ok';
        }
    }

    /**
     * Handle UseePay async notify for refund
     *
     * @access public
     * @return void
     */
    function refund_async_notify_handle()
    {
        $_POST                 = stripslashes_deep($_POST);
        $originalTransactionId = $_POST['originalTransactionId']; // 原交易订单号
        $order                 = wc_get_order($originalTransactionId);

        WC_UseePay_Logger::log($_POST['transactionId'] . ' refund notify response : ' . var_export($_POST, true));
        if (!Wc_UseePay_Util::verify_signature($_POST, $this->get_secret_key())) {
            WC_UseePay_Logger::log($_POST['reference'] . '	verify signature in refund notice failed!');
            return;
        }

        //处理返回结果
        $resultCode = isset($_POST['resultCode']) ? $_POST['resultCode'] : null;
        $errorCode  = isset($_POST['errorCode']) ? $_POST['errorCode'] : null;
        $errorMsg   = isset($_POST['errorMsg']) ? $_POST['errorMsg'] : null;

        if ($resultCode == "succeed" && $errorCode == "0000") {
            $refund_amount = Wc_UseePay_Util::convert_useepay_price_to_woo_price($order->get_currency(), $_POST['amount'], 2);
            $order->add_order_note(
                sprintf(
                    'Credit card refund succeed;Refund ID: %s ; Refund Amount: %s',
                    $_POST['reference'],
                    $refund_amount
                )
            );
        } else {
            $errorMessage = $errorCode . "," . $errorMsg;
            $order->add_order_note(
                sprintf('Credit card refund failed;Refund ID: %s;errorMessage: %s', $_POST['reference'], $errorMessage)
            );
        }
    }

    function get_goods_name_desc($order)
    {

        ini_set('display_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);
        $result = array(
            "product_names"        => "",
            "product_descriptions" => ""
        );

        if (sizeof($order->get_items()) > 0) {
            foreach ($order->get_items() as $item) {
                if ($item['product_id'] > 0) {
                    if ($result["product_names"] != "") $result["product_names"] .= "|";
                    if ($result["product_descriptions"] != "") $result["product_descriptions"] .= "|";
                    $_product                       = $order->get_product_from_item($item);
                    $result["product_names"]        .= $_product->get_data()["name"];
                    $result["product_descriptions"] .= $_product->get_data()["description"];
                }
            }
        }
        //截断
        if (strlen($result["product_names"]) > 200) $result["product_names"] = mb_strcut($result["product_names"], 0, 256);
        if (strlen($result["product_descriptions"]) > 2000) $result["product_descriptions"] = mb_strcut($result["product_descriptions"], 0, 2000);

        //  var_dump($result);
        return $result;
    }

    function clean($str = '')
    {
        $clean = str_replace(array('%'), '', $str);
        $clean = sanitize_text_field($clean);
        $clean = html_entity_decode($clean, ENT_NOQUOTES);
        return $clean;
    }


    function wc_useepay_display_order_meta_for_admin($order)
    {
        $trade_no = get_post_meta($order->get_id(), 'UseePay Trade No.', true);
        if (!empty($trade_no)) {
            echo '<p><strong>' . __('UseePay Trade No.:', 'useepay') . '</strong><br />' . $trade_no . '</p>';
        }
    }

    function query_order_status()
    {
        WC_UseePay_Logger::log('Start to query order status with query parameters: ' . var_export($_GET, true));
        $order_id = $_GET['order_id'];
        $order    = wc_get_order($order_id);
        if ($order === false) {
            WC_UseePay_Logger::log('Query order status finished, ' . $order_id . ' not exist');
            exit(-1);
        } else {
            WC_UseePay_Logger::log('Query order status finished: ' . $order->get_status());
            exit($order->get_status());
        }
    }

    protected function get_useepay_order_transaction_id(WC_Order $order): ?string
    {
        return  substr($order->get_order_number() . '.' . md5(uniqid(microtime(true), true)), 0, 24);
    }
}

?>
