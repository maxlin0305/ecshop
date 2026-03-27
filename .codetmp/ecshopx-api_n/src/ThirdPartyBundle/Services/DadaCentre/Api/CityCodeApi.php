<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class CityCodeApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::CITY_ORDER_URL, $params);
    }
}
