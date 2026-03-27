<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class RechargeApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::RECHARGE_URL, $params);
    }
}
