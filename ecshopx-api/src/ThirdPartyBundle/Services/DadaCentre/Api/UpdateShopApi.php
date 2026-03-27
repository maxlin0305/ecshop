<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class UpdateShopApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::SHOP_UPDATE_URL, $params);
    }
}
