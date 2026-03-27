<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class AddMerchantApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::MERCHANT_ADD_URL, $params);
    }
}
