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

    protected function __construct()
    {
        $this->context = ['source' => 'payermax'];
        $this->logger = wc_get_logger();
    }

    public static function debug(string $message)
    {
        if (WP_DEBUG) {
            $payermax_logger = static::getInstance();
            $payermax_logger->logger->debug($message, $payermax_logger->context);
        }
    }

    public static function info(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->info($message, $payermax_logger->context);
    }

    public static function notice(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->info($message, $payermax_logger->context);
    }

    public static function warning(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->warning($message, $payermax_logger->context);
    }

    public static function error(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->error($message, $payermax_logger->context);
    }

    public static function critical(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->critical($message, $payermax_logger->context);
    }

    public static function alert(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->alert($message, $payermax_logger->context);
    }

    public static function emergency(string $message)
    {
        $payermax_logger = static::getInstance();
        $payermax_logger->logger->emergency($message, $payermax_logger->context);
    }
}
