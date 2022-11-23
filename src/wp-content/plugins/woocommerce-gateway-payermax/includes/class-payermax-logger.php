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
    private array $context;
    private WC_Logger $logger;

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
        $this->context = ['source' => 'payermax'];
        $this->logger = wc_get_logger();
    }

    public static function debug(string $message): void
    {
        if (WP_DEBUG) {
            $payermax_logger = static::getInstance();
            $payermax_logger->logger->debug($message, $payermax_logger->context);
        }
    }

    /**
     * Add info log when debug is enabled
     */
    public static function info(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->info($message, $payermax_logger->context);
    }

    public static function notice(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->info($message, $payermax_logger->context);
    }

    public static function warning(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->warning($message, $payermax_logger->context);
    }

    public static function error(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->error($message, $payermax_logger->context);
    }

    public static function critical(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->critical($message, $payermax_logger->context);
    }

    public static function alert(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->alert($message, $payermax_logger->context);
    }

    public static function emergency(string $message): void
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->emergency($message, $payermax_logger->context);
    }
}
