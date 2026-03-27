<?php

namespace CompanysBundle\Traits;

use CompanysBundle\Services\CurrencyExchangeRateService;

trait GetDefaultCur
{
    public function getCur($companyId)
    {
        $currencyExchangeRate = new CurrencyExchangeRateService();
        $result = $currencyExchangeRate->getDefaultCurrency($companyId);
        return $result;
    }

    public function getGoodsRateSettingStatus($companyId)
    {
        $key = 'TradeRateSetting:'.$companyId;
        $inputData = app('redis')->connection('companys')->get($key);
        $inputData = $inputData ? json_decode($inputData, true) : ['rate_status' => false];
        return $inputData['rate_status'];
    }
}
