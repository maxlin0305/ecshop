<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class AddOrderApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::ORDER_ADD_URL, $params);
    }
}
