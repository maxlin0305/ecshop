<?php

namespace App\Services;

/**
 * 呼叫網路服務的類別。
 */
class NetworkService
{
    public $ServiceURL = null;

    /**
     * 提供伺服器端呼叫遠端伺服器 Web API 的方法。
     */
    public function serverPost($parameters)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->ServiceURL);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Content-Length: ' . strlen($parameters)));
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }
}
