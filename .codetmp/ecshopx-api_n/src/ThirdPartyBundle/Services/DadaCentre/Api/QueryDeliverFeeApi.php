<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

use ThirdPartyBundle\Services\DadaCentre\Config\UrlConfig;

class QueryDeliverFeeApi extends BaseApi
{
    public function __construct($params)
    {
        parent::__construct(UrlConfig::QUERY_DELIVER_FEE, $params);
    }
}
