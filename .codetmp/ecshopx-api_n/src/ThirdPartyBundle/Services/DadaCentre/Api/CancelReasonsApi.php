<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class CancelReasonsApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::CANCEL_REASONS, $params);
    }
}
