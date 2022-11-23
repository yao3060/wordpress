<?php
if (!defined('ABSPATH')) {
    exit;
}

final class PayerMax
{
    static function get_currencies(): array
    {
        $currencies = array_merge(
            ...array_column(
                self::get_supports(),
                'currencies'
            )
        );
        return array_unique($currencies);
    }

    static function get_languages(): array
    {
        $languages = array_merge(
            ...array_column(
                self::get_supports(),
                'languages'
            )
        );
        return array_unique($languages);
    }

    static function get_supports()
    {
        $cache_key = 'supports';
        $group = WC_PAYERMAX_PLUGIN_NAME . '-' . WC_PAYERMAX_PLUGIN_VERSION;
        $supports = wp_cache_get($cache_key, $group);
        if ($supports) {
            return $supports;
        }

        $supports = [];
        if (($open = fopen(WC_PAYERMAX_PLUGIN_PATH . '/payermax-payment-supports.csv', "r")) !== FALSE) {
            $headers = fgetcsv($open, 10000, ",");
            while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                $combine = array_combine($headers, $data);
                $supports[] = array_merge($combine, [
                    'currencies' => explode(',', $combine['currencies']),
                    'languages' => explode(',', $combine['languages'])
                ]);
            }
            fclose($open);
        }
        wp_cache_set($cache_key, $supports, $group, DAY_IN_SECONDS);
        return $supports;
    }
}
