<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class BalanceApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::BALANCE_QUERY_URL, $params);
    }
}
