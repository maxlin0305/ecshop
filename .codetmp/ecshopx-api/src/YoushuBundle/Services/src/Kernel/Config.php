<?php

namespace YoushuBundle\Services\src\Kernel;

class Config
{
    /**
     * @var string 接口地址
     */
    public $base_uri = 'https://zhls.qq.com';

    /**
     * @var string 分配的app_id
     */
    public $app_id;

    /**
     * @var string 分配的app_secret
     */
    public $app_secret;

    /**
     * @var string 商家id
     */
    public $merchant_id;
}
