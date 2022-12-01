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

    private $is_enable_payermax_log = false;

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
        $this->is_enable_payermax_log = $this->get_payermax_logs_status();
    }

    protected function get_payermax_logs_status()
    {
        $active_date = PayerMax::get_activate_date();
        if (!$active_date) {
            return false;
        }
        $interval = (new DateTime())->diff(new DateTime($active_date));
        if ($interval->days > 90) {
            return false;
        }
        return true;
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

        // no more save payermax logs after 90 days the plugin activated.
        if (!$payermax_logger->is_enable_payermax_log) {
            return;
        }

        // replace email
        $pattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";
        $message = preg_replace($pattern, '*Email*', $message);

        // replace phone number
        $pattern = '!(\b\+?[0-9()\[\]./ -]{7,17}\b|\b\+?[0-9()\[\]./ -]{7,17}\s+(extension|x|#|-|code|ext)\s+[0-9]{1,6})!i';
        $message = preg_replace($pattern, '*Phone*', $message);

        $log = [
            'timestamp' => (new DateTime())->format('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'summary' => array_slice(wp_debug_backtrace_summary(null, 1, false), 0, 2)
        ];

        fwrite(
            $payermax_logger->file_handle,
            json_encode($log) . PHP_EOL
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
        if (WP_DEBUG) {
            $payermax_logger = static::getInstance();
            $payermax_logger->logger->debug($message, $payermax_logger->context);
        }

        $payermax_logger->log('DEBUG', $message);
    }

    public static function info(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->info($message, $payermax_logger->context);
        $payermax_logger->log('INFO', $message);
    }

    public static function warning(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->warning($message, $payermax_logger->context);
        $payermax_logger->log('WARNING', $message);
    }

    public static function error(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->error($message, $payermax_logger->context);
        $payermax_logger->log('ERROR', $message);
    }
}
