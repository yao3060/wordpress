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
 * Class with some utility functions for this addon
 */
class Wc_UseePay_Util
{

    public static $acceptable_cards = array(
        "Visa",
        "MasterCard",
        "Discover",
        "Amex"
    );

    static function is_valid_card_number($toCheck)
    {
        $toCheck = preg_replace( '/\s+/', '', $toCheck);
        if (!is_numeric($toCheck))
            return false;

        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13 && $strlen > 19)
            return false;

        for ($i = 0; $i < $strlen; $i++) {
            $digit = substr($number, $strlen - $i - 1, 1);
            if ($i % 2 == 1) {
                $sub_total = $digit * 2;
                if ($sub_total > 9) {
                    $sub_total = $sub_total - 9;
                }
            } else {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }

        if ($sum > 0 and $sum % 10 == 0)
            return true;

        return false;
    }

    static function is_valid_card_type($toCheck)
    {
        return $toCheck and in_array($toCheck, self::$acceptable_cards);
    }

    static function is_valid_expiry($month, $year)
    {
        $now       = time();
        $thisYear  = (int)date('Y', $now);
        $thisMonth = (int)date('m', $now);

        if (is_numeric($year) && is_numeric($month)) {
            $thisDate   = mktime(0, 0, 0, $thisMonth, 1, $thisYear);
            $expireDate = mktime(0, 0, 0, $month, 1, $year);

            return $thisDate <= $expireDate;
        }

        return false;
    }

    static function is_valid_cvv_number($toCheck)
    {
        $length = strlen($toCheck);
        return is_numeric($toCheck) and $length > 2 and $length < 5;
    }


    /**
     * price转成最小单位级别
     * @param $currency
     * @param $price
     * @return int
     */
    static function convert_woo_price_to_useepay_price($currency, $price, $times = 2)
    {
        $currency          = strtoupper($currency);
        $currency_map_json = '{"EUR":2,"AMD":2,"BOV":2,"XAF":0,"COP":2,"COU":2,"CUP":2,"ANG":2,"DOP":2,"HKD":2,"IRR":2,"NPR":2,"PYG":0,"QAR":2,"RON":2,"SBD":2,"SOS":2,"LKR":2,"TWD":2,"VUV":0,"XTS":0,"XAU":0,"AOA":2,"ARS":2,"BRL":2,"BGN":2,"KHR":2,"KMF":0,"CDF":2,"GNF":0,"HNL":2,"ISK":0,"ILS":2,"JPY":0,"KWD":3,"LBP":2,"LYD":3,"MWK":2,"MXV":2,"PKR":2,"RWF":0,"STD":2,"SLL":2,"CHW":2,"UGX":0,"USN":2,"UZS":2,"XBB":0,"BBD":2,"XOF":0,"BMD":2,"CAD":2,"NZD":2,"CZK":2,"FJD":2,"GBP":2,"ZAR":2,"LRD":2,"PHP":2,"WST":2,"SCR":2,"SDG":2,"TMT":2,"UYI":0,"XBA":0,"BAM":2,"BWP":2,"CLP":0,"CRC":2,"GYD":2,"JOD":3,"MKD":2,"MUR":2,"MDL":2,"MNT":2,"NGN":2,"SRD":2,"SEK":2,"TZS":2,"TOP":2,"UAH":2,"UYU":2,"ZWL":2,"USD":2,"BSD":2,"BYN":2,"BZD":2,"CLF":4,"GHS":2,"GTQ":2,"IQD":3,"KRW":0,"MOP":2,"MXN":2,"MAD":2,"NIO":2,"XSU":0,"SZL":2,"THB":2,"VND":0,"ZMW":2,"XPD":0,"AFN":2,"AUD":2,"BOB":2,"CNY":2,"HRK":2,"DJF":0,"EGP":2,"ETB":2,"GEL":2,"HTG":2,"XDR":0,"JMD":2,"KZT":2,"KPW":2,"MGA":2,"MVR":2,"XUA":0,"MMK":2,"NAD":2,"OMR":3,"PGK":2,"RUB":2,"TTD":2,"XXX":0,"ALL":2,"DZD":2,"XCD":2,"AZN":2,"BDT":2,"INR":2,"BTN":2,"NOK":2,"BND":2,"BIF":0,"CUC":2,"SVC":2,"FKP":2,"XPF":0,"GMD":2,"HUF":2,"KES":2,"KGS":2,"LAK":2,"MYR":2,"MRO":2,"PAB":2,"PEN":2,"RSD":2,"SGD":2,"CHE":2,"TND":3,"TRY":2,"VEF":2,"YER":2,"XBD":0,"XPT":0,"XAG":0,"AWG":2,"BHD":3,"CVE":2,"KYD":2,"DKK":2,"ERN":2,"GIP":2,"IDR":2,"LSL":2,"CHF":2,"MZN":2,"PLN":2,"SHP":2,"SAR":2,"SSP":2,"SYP":2,"TJS":2,"AED":2,"XBC":0}';
        $currency_map      = json_decode($currency_map_json, true);
        if (array_key_exists($currency, $currency_map)) {
            $times = $currency_map[$currency];
        }
        WC_UseePay_Logger::log('product price' . $price);

        $newPrice = round(floatval($price), $times);
        while ($times--) {
            $newPrice *= 10;
        }
        return intval($newPrice);
    }

    /**
     * price转成最小单位级别
     * @param $currency
     * @param $price
     * @return int
     */
    static function convert_useepay_price_to_woo_price($currency, $price, $times = 2)
    {
        $currency          = strtoupper($currency);
        $currency_map_json = '{"EUR":2,"AMD":2,"BOV":2,"XAF":0,"COP":2,"COU":2,"CUP":2,"ANG":2,"DOP":2,"HKD":2,"IRR":2,"NPR":2,"PYG":0,"QAR":2,"RON":2,"SBD":2,"SOS":2,"LKR":2,"TWD":2,"VUV":0,"XTS":0,"XAU":0,"AOA":2,"ARS":2,"BRL":2,"BGN":2,"KHR":2,"KMF":0,"CDF":2,"GNF":0,"HNL":2,"ISK":0,"ILS":2,"JPY":0,"KWD":3,"LBP":2,"LYD":3,"MWK":2,"MXV":2,"PKR":2,"RWF":0,"STD":2,"SLL":2,"CHW":2,"UGX":0,"USN":2,"UZS":2,"XBB":0,"BBD":2,"XOF":0,"BMD":2,"CAD":2,"NZD":2,"CZK":2,"FJD":2,"GBP":2,"ZAR":2,"LRD":2,"PHP":2,"WST":2,"SCR":2,"SDG":2,"TMT":2,"UYI":0,"XBA":0,"BAM":2,"BWP":2,"CLP":0,"CRC":2,"GYD":2,"JOD":3,"MKD":2,"MUR":2,"MDL":2,"MNT":2,"NGN":2,"SRD":2,"SEK":2,"TZS":2,"TOP":2,"UAH":2,"UYU":2,"ZWL":2,"USD":2,"BSD":2,"BYN":2,"BZD":2,"CLF":4,"GHS":2,"GTQ":2,"IQD":3,"KRW":0,"MOP":2,"MXN":2,"MAD":2,"NIO":2,"XSU":0,"SZL":2,"THB":2,"VND":0,"ZMW":2,"XPD":0,"AFN":2,"AUD":2,"BOB":2,"CNY":2,"HRK":2,"DJF":0,"EGP":2,"ETB":2,"GEL":2,"HTG":2,"XDR":0,"JMD":2,"KZT":2,"KPW":2,"MGA":2,"MVR":2,"XUA":0,"MMK":2,"NAD":2,"OMR":3,"PGK":2,"RUB":2,"TTD":2,"XXX":0,"ALL":2,"DZD":2,"XCD":2,"AZN":2,"BDT":2,"INR":2,"BTN":2,"NOK":2,"BND":2,"BIF":0,"CUC":2,"SVC":2,"FKP":2,"XPF":0,"GMD":2,"HUF":2,"KES":2,"KGS":2,"LAK":2,"MYR":2,"MRO":2,"PAB":2,"PEN":2,"RSD":2,"SGD":2,"CHE":2,"TND":3,"TRY":2,"VEF":2,"YER":2,"XBD":0,"XPT":0,"XAG":0,"AWG":2,"BHD":3,"CVE":2,"KYD":2,"DKK":2,"ERN":2,"GIP":2,"IDR":2,"LSL":2,"CHF":2,"MZN":2,"PLN":2,"SHP":2,"SAR":2,"SSP":2,"SYP":2,"TJS":2,"AED":2,"XBC":0}';
        $currency_map      = json_decode($currency_map_json, true);
        if (array_key_exists($currency, $currency_map)) {
            $times = $currency_map[$currency];
        }
        $price = round($price, $times);
        while ($times--) {
            $price /= 10;
        }
        return intval($price);
    }

    public static function generate_signature($data, $secret_key)
    {
        ksort($data);
        $signature = '';
        foreach ($data as $key => $value) {
            if (!empty($value) && 'sign' != $key) {
                $signature .= '&' . $key . '=' . $value;
            }
        }
        $signature = substr($signature, 1);
        $signature .= '&pkey=' . $secret_key;
        return strtolower(md5($signature));
    }

    public static function verify_signature($data, $secret_key)
    {
        return self::generate_signature($data, $secret_key) == isset($data['sign']) ? $data['sign'] : null;
    }


}