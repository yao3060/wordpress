<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Write Logs
 *
 * Dist Depends on wp_config: define( 'UPLOADS', 'wp-content/uploads' );
 * Log dist "{UPLOADS}/wc-logs/2022-11-17/payermax.log"
 */
class PayerMax_Logger
{
    private static $instance;
    private string $date;
    private string $logPath;
    private string $logPathFile;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Since the Singleton's constructor is called only once, just a single file
     * resource is opened at all times.
     *
     * Note, for the sake of simplicity, we open the console stream instead of
     * the actual file here.
     */
    protected function __construct()
    {
        $this->date = date('Y-m-d');
        $this->logPath = WP_CONTENT_DIR . '/uploads/wc-logs/' . $this->date;
        if (!file_exists($this->logPath)) {
            /**
             * 0755 - Permission
             * true - recursive?
             */
            mkdir($this->logPath, 0755, true);
        }
        $this->logPathFile = $this->logPath . '/payermax.log';
    }

    /**
     * Write a log entry to the opened file resource.
     */
    public function writeLog(string $message): void
    {
        $dt = $this->getDateTime();
        $clientIp = $this->getClientIp();
        error_log("{$dt} {$clientIp}" . $message . PHP_EOL, 3, $this->logPathFile);
    }

    /**
     * Just a handy shortcut to reduce the amount of code needed to log messages
     * from the client code.
     */
    public static function info(string $message): void
    {
        $logger = static::getInstance();
        $logger->writeLog(" INFO " . $message);
    }

    public static function warning(string $message): void
    {
        $logger = static::getInstance();
        $logger->writeLog(' WARNING ' . $message);
    }

    public static function error(string $message): void
    {
        $logger = static::getInstance();
        $logger->writeLog(' ERROR ' . $message);
    }

    protected function getDateTime()
    {
        return (new \DateTime())->format('Y-m-d\TH:i:s.vP');
    }

    protected function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return apply_filters('wc_payermax_get_client_ip', $ip);
    }
}
