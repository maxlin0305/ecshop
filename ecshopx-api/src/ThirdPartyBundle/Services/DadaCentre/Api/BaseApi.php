<?php

namespace ThirdPartyBundle\Services\DadaCentre\Api;

class BaseApi
{
    private $url;

    private $businessParams;

    public function __construct($url, $params)
    {
        $this->url = $url;
        $this->businessParams = $params;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getBusinessParams()
    {
        return $this->businessParams;
    }
}
