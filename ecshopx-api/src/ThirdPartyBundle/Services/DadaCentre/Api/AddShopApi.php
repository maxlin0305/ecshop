<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class AddShopApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::SHOP_ADD_URL, $params);
    }
}
