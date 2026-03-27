<?php

namespace HfPayBundle\Services\src\Kernel;

use HfPayBundle\Services\src\Acou\Client as acouClient;
use HfPayBundle\Services\src\Hfpay\Client as hfpayClient;

class Factory
{
    public $config = null;
    public $kernel = null;
    protected static $instance;
    protected static $app;

    private function __construct($config)
    {
        $kernel = new Kernel($config);
        self::$app = new App($kernel);
    }

    public static function setOptions($config)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public static function app()
    {
        return self::$app;
    }
}

class App
{
    private $kernel;

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }

    public function Acou()
    {
        return new acouClient($this->kernel);
    }

    public function Hfpay()
    {
        return new hfpayClient($this->kernel);
    }
}
