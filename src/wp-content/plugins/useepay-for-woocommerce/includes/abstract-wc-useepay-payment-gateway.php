<?php
/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

abstract class WC_UseePay_Payment_Gateway extends WC_Payment_Gateway
{
    const ID                                = 'useepay';
    const SETTINGS_KEY_ENABLED              = 'enabled';
    const SETTINGS_KEY_TITLE                = 'title';
    const SETTINGS_KEY_MERCHANT_NO          = 'useepay_merchant_no';
    const SETTINGS_KEY_SECURITY_KEY         = 'useepay_key';
    const SETTINGS_KEY_APP_ID               = 'useepay_app_id';
    const SETTINGS_KEY_SANDBOX_MODE         = 'testmode';
    const SETTINGS_KEY_DEBUG_LOG            = 'debug';

    public function __construct()
    {
        $this->id = self::ID;
        $this->init_form_fields();
        $this->init_settings();
        $this->title       = $this->get_option(self::SETTINGS_KEY_TITLE);
        $this->enabled     = $this->get_option(self::SETTINGS_KEY_ENABLED);
    }

    public function init_form_fields()
    {
        $this->method_title = $this->getModuleTitle();
        $this->form_fields  = array(
            self::SETTINGS_KEY_ENABLED              => array(
                'title'   => __('Enable/Disable', 'useepay-for-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable UseePay Payment', 'useepay-for-woocommerce'),
                'default' => 'no'
            ),
            self::SETTINGS_KEY_TITLE                => array(
                'title'       => __('Title', 'useepay-for-woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'useepay-for-woocommerce'),
                'default'     => __('UseePay', 'useepay-for-woocommerce'),
                'desc_tip'    => true,
            ),
            self::SETTINGS_KEY_MERCHANT_NO          => array(
                'title'       => __('Merchant No', 'useepay-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Please enter the Merchant No<br />If you dont have one, <a href=\"https://useepay.com/global-payment-acquiring-apply\" target=\"_blank\">click here</a> to get.', 'useepay-for-woocommerce'),
                'css'         => 'width:400px'
            ),
            self::SETTINGS_KEY_SECURITY_KEY         => array(
                'title'       => __('Security Key', 'useepay-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Please enter the security key<br />If you dont have one, <a href=\"https://useepay.com/global-payment-acquiring-apply\" target=\"_blank\">click here</a> to get.', 'useepay-for-woocommerce'),
                'css'         => 'width:400px'
            ),
            self::SETTINGS_KEY_APP_ID               => array(
                'title'       => __('UseePay App Id', 'useepay-for-woocommerce'),
                'type'        => 'text',
                'description' => __('Please enter your UseePay App Id ; this is needed in order to take payment.', 'useepay-for-woocommerce'),
                'css'         => 'width:200px',
                'desc_tip'    => true,
            ),
            self::SETTINGS_KEY_SANDBOX_MODE         => array(
                'title'       => __('Sandbox Mode', 'useepay-for-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable Sandbox Mode', 'useepay-for-woocommerce'),
                'default'     => 'no',
                'description' => __('Enable Sandbox Mode', 'useepay-for-woocommerce')
            ),
            self::SETTINGS_KEY_DEBUG_LOG            => array(
                'title'       => __('Debug Log', 'useepay-for-woocommerce'),
                'type'        => 'checkbox',
                'label'       => __('Enable logging', 'useepay-for-woocommerce'),
                'default'     => 'no',
                'description' => sprintf(__('Log UseePay events, such as trade status, inside %1$s', 'useepay-for-woocommerce'), '<code>' . wc_get_log_file_path('useepay') . '</code>')
            )

        );
    }

    protected function getModuleTitle(): string
    {
        return __('UseePay', 'useepay-for-woocommerce');
    }

    public function is_available()
    {
        if ('yes' === $this->enabled) {
            return true;
        }

        return parent::is_available(); // TODO: Change the autogenerated stub
    }


    protected function get_merchant_no()
    {
        return $this->get_option(self::SETTINGS_KEY_MERCHANT_NO);
    }

    protected function get_app_id()
    {
        return $this->get_option(self::SETTINGS_KEY_APP_ID);
    }

    protected function get_secret_key()
    {
        return $this->get_option(self::SETTINGS_KEY_SECURITY_KEY);
    }


}