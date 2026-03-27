<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class ConfirmGoodsApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::CONFIRM_GOODS, $params);
    }
}
