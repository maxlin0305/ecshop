<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class FormalCancelApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::FORMAL_CANCEL, $params);
    }
}
