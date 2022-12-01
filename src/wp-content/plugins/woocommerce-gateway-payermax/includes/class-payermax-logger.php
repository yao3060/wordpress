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

    public static function read_logs(string $date = '')
    {
        $payermax_logger = static::getInstance();

        if ($date) {
            $log_file = str_replace(date('Y-m-d'), $date, $payermax_logger->log_file);
        } else {
            $log_file = $payermax_logger->log_file;
        }

        if (!file_exists($log_file)) {
            wp_die('File Not exists', 'Not Found');
        }

        echo '<pre>' . esc_html(file_get_contents($log_file)) . '</pre>';
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
