<?php
/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

class WC_UseePay_API
{
    const SANDBOX_ENDPOINT = 'https://pay-gateway1.uat.useepay.com/api';
    const LIVE_ENDPOINT    = 'https://pay-gateway.useepay.com/api';


    /**
     * @param $parameters
     * @throws WC_UseePay_Exception
     */
    public static function request($parameters)
    {
        $options            = get_option('woocommerce_' . WC_Gateway_UseePay::ID . '_settings');
        $sandbox_mode       = isset($options[WC_Gateway_UseePay::SETTINGS_KEY_SANDBOX_MODE]) && 'yes' === $options[WC_Gateway_UseePay::SETTINGS_KEY_SANDBOX_MODE];
        $endpoint           = $sandbox_mode ? self::SANDBOX_ENDPOINT : self::LIVE_ENDPOINT;
        $parameters['sign'] = Wc_UseePay_Util::generate_signature($parameters, $options[WC_Gateway_UseePay::SETTINGS_KEY_SECURITY_KEY]);
        WC_UseePay_Logger::log("request Endpoint: " . $endpoint);
        WC_UseePay_Logger::log("request parameters: " . var_export($parameters, true));
        $response = wp_safe_remote_post($endpoint, array(
            'timeout'   => 45,
            'blocking'  => true,
            'sslverify' => !$sandbox_mode,
            'body'      => $parameters
        ));
        if (is_wp_error($response) || empty($response['body'])) {
            WC_UseePay_Logger::log("Error response: " . var_export($response, true));

            throw new  WC_UseePay_Exception(var_export($response, true), __('Network error, please try later', 'useepay-for-woocommerce'));
        }
        $final_response = stripslashes_deep(json_decode($response['body'], true));
        WC_UseePay_Logger::log("response body: " . var_export($final_response, true));
        if (Wc_UseePay_Util::verify_signature($final_response, $options[WC_Gateway_UseePay::SETTINGS_KEY_SECURITY_KEY])) {
            WC_UseePay_Logger::log("response signature matched");
            return $final_response;
        } else {
            WC_UseePay_Logger::log("response signature not matched!!!");
            throw new WC_UseePay_Exception('Signature not match!', __('Signature not match!', 'useepay-for-woocommerce'));
        }

    }

}
