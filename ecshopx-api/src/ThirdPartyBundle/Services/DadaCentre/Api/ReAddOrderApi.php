<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class ReAddOrderApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::RE_ADD_ORDER, $params);
    }
}
