<?php

namespace ThirdPartyBundle\Services\DadaCentre;

use Dingo\Api\Exception\ResourceException;
use ThirdPartyBundle\Services\DadaCentre\Api\BalanceApi;
use ThirdPartyBundle\Services\DadaCentre\Client\DadaRequest;

class BalanceService
{
    /**
     * 查询账户余额
     * @param string $companyId 企业Id
     * @return mixed 账户余额信息
     */
    public function query($companyId)
    {
        $params = [
            'category' => '3'
        ];
        $balanceApi = new BalanceApi(json_encode($params));
        $dadaClient = new DadaRequest($companyId, $balanceApi);
        $resp = $dadaClient->makeRequest();
        if ($resp->status == 'fail') {
            throw new ResourceException($resp->msg);
        }
        return $resp->result;
    }
}
