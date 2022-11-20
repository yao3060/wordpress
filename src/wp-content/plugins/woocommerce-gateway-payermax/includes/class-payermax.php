<?php
if (!defined('ABSPATH')) {
    exit;
}

class PayerMax
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    protected function __construct()
    {
    }

    public function sign()
    {
        # code...
    }

    public function request()
    {
        # code...
    }
}
