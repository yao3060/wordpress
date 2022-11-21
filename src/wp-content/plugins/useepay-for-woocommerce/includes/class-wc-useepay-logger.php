<?php
/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

/**
 * Print logs if debug log setting enabled
 */
class WC_UseePay_Logger
{
    private static $logger;
    const WC_USEEPAY_LOG_FILENAME = 'useepay';

    public static function log($message)
    {
        if (empty($message)) {
            return;
        }

        if (!class_exists('WC_Logger')) {
            return;
        }

        if (empty(self::$logger)) {
            self::$logger = wc_get_logger();
        }


        $settings = get_option('woocommerce_' . WC_Gateway_UseePay::ID . '_settings');
        if (empty($settings) || isset($settings[WC_UseePay_Payment_Gateway::SETTINGS_KEY_DEBUG_LOG]) && 'yes' !== $settings[WC_UseePay_Payment_Gateway::SETTINGS_KEY_DEBUG_LOG]) {
            return;
        }

        try {
            $message = preg_replace('/"cardNo"\s*?:\s*"(\d{4}).*?(\d{4})"/', '"card_no":"$1****$2"', $message);
            $message = preg_replace('/"expirationMonth"\s*?:\s*"(.*?)"/', '"expirationMonth":"**"', $message);
            $message = preg_replace('/"expirationYear"\s*?:\s*"(.*?)"/', '"expirationYear":"**"', $message);
            $message = preg_replace('/"cvv"\s*?:\s*"(.*?)"/', '"cvv":"***"', $message);
            $message = preg_replace('/"ip"\s*?:\s*"(.*?)"/', '"ip":"***.***.***.***"', $message);
            $message = preg_replace('/"email"\s*?:\s*"(.*?)"/', '"email":"***@***.***"', $message);
            $message = preg_replace('/"phoneNo"\s*?:\s*"(.*?)"/', '"phoneNo":"***********"', $message);
            $message = preg_replace('/"country"\s*?:\s*"(.*?)"/', '"country":"**"', $message);
            $message = preg_replace('/"state"\s*?:\s*"(.*?)"/', '"state":"****"', $message);
            $message = preg_replace('/"city"\s*?:\s*"(.*?)"/', '"city":"****"', $message);
            $message = preg_replace('/"street"\s*?:\s*"(.*?)"/', '"street":"****"', $message);
            $message = preg_replace('/"houseNo"\s*?:\s*"(.*?)"/', '"houseNo":"****"', $message);
            $message = preg_replace('/"firstName"\s*?:\s*"(.*?)"/', '"firstName":"****"', $message);
            $message = preg_replace('/"lastName"\s*?:\s*"(.*?)"/', '"lastName":"****"', $message);

        } catch (Exception $e) {
        }

        self::$logger->add('useepay', $message);
    }
}