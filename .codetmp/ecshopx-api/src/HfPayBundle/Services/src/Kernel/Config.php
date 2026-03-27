<?php

namespace HfPayBundle\Services\src\Kernel;

class Config
{
    /**
     * @var string 接口地址
     */
    public $base_uri = '';

    //版本号
    public $version = 10;

    //商户客户号
    public $mer_cust_id = '';

    //证书密码
    public $pfx_password;
}
