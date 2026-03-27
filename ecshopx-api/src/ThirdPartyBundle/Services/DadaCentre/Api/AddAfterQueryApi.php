<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class AddAfterQueryApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::ADD_AFTER_QUERY, $params);
    }
}
