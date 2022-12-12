<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_Logger Factory
 */
class PayerMax_Logger
{
    private static $instance;

    /**
     * @var array
     */
    private $context;

    /**
     * @var WC_Logger
     */
    private $logger;

    private $log_file;

    private $file_handle;

    /** @var WC_Gateway_PayerMax */
    private $gateway;

    const PAYERMAX_LOGS_FOLDER = 'payermax-logs';

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    protected function __construct()
    {
        $this->context = ['source' => 'payermax'];
        $this->create_payermax_logs_folder();
        $this->logger = wc_get_logger();

        $payment_methods = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $payment_methods[WC_Gateway_PayerMax::ID];
    }

    private function create_payermax_logs_folder()
    {
        $upload_dir = wp_upload_dir(null, false);
        if (!file_exists($upload_dir['basedir'] . '/' . self::PAYERMAX_LOGS_FOLDER . '/')) {
            mkdir($upload_dir['basedir'] . '/' . self::PAYERMAX_LOGS_FOLDER . '/', 0777, true);
            $file = fopen($upload_dir['basedir'] . '/' . self::PAYERMAX_LOGS_FOLDER . '/index.html', "a");
            fwrite($file, 'index.html');
            fclose($file);
        }

        $this->log_file = $upload_dir['basedir'] . '/' . self::PAYERMAX_LOGS_FOLDER . '/' . date('Y-m-d') . '.log';

        $this->file_handle = fopen($this->log_file, 'a');
    }

    /**
     * PayerMax Private Logs
     *
     * @param string $level DEBUG, INFO, WARNING, ERROR
     * @param string $message Message
     * @return void
     */
    public static function log(string $level, string $message)
    {
        $payermax_logger = static::getInstance();

        $log = [
            'timestamp' => (new DateTime())->format('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'summary' => array_slice(wp_debug_backtrace_summary(null, 1, false), 0, 2)
        ];

        fwrite(
            $payermax_logger->file_handle,
            $payermax_logger::encrypt_decrypt(
                json_encode($log),
                'encrypt',
                $payermax_logger->gateway->merchant_private_key,
                $payermax_logger->gateway->merchant_public_key
            ) . PHP_EOL
        );
    }

    static function clean_logs()
    {
        $date = get_option('clean_payermax_logs');
        if ($date === date('Y-m-d')) {
            return;
        }

        echo <<<EOF
<script type="text/javascript">
        jQuery(document).ready(function($) {
            jQuery.post(ajaxurl, {
                action: 'clean_payermax_logs'
            }, function(response) {
                // no need handle response
            });
        });
</script>
EOF;
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

    public static function remove_logs()
    {
        $payermax_logger = static::getInstance();
        $date = $date = date('Y-m-d', (strtotime('-30 day')));
        $logs_dir = str_replace(date('Y-m-d') . '.log', '', $payermax_logger->log_file);

        $files  = @scandir($logs_dir);
        $results = [];
        if (!empty($files)) {
            foreach ($files as $value) {
                if (!in_array($value, array('.', '..', 'index.html'), true)) {
                    $filename = str_replace('.log', '', $value);
                    if (date($filename) < date($date)) {
                        unlink(str_replace(date('Y-m-d') . '.log', $value, $payermax_logger->log_file));
                        $results[] = 'delete log file: ' . $value;
                    }
                }
            }
        }

        return $results;
    }

    public static function debug(string $message)
    {
        $payermax_logger = static::getInstance();
        if (WP_DEBUG) {
            $payermax_logger->logger->debug($message, $payermax_logger->context);
        }

        $payermax_logger->log('DEBUG', $message);
    }

    public static function info(string $message)
    {
        $payermax_logger = static::getInstance();
        if (WP_DEBUG) {
            $payermax_logger->logger->info($message, $payermax_logger->context);
        }
        $payermax_logger->log('INFO', $message);
    }

    public static function warning(string $message)
    {
        $payermax_logger = static::getInstance();
        if (WP_DEBUG) {
            $payermax_logger->logger->warning($message, $payermax_logger->context);
        }
        $payermax_logger->log('WARNING', $message);
    }

    public static function error(string $message)
    {
        $payermax_logger = static::getInstance();
        if (WP_DEBUG) {
            $payermax_logger->logger->error($message, $payermax_logger->context);
        }
        $payermax_logger->log('ERROR', $message);
    }

    /**
     * encrypt_decrypt payermax logs
     *
     * @param string $string
     * @param string $action
     * @param string $secret_key merchant_private_key
     * @param string $secret_iv  merchant_public_key
     * @return void
     */
    public static function encrypt_decrypt($string, $action = 'encrypt', $secret_key, $secret_iv)
    {
        $encrypt_method = "AES-256-CBC";
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16); // sha256 is hash_hmac_algo
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            return base64_encode($output);
        }
        if ($action == 'decrypt') {
            return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
        return '';
    }
}
