<?php

namespace App\Services;

class EcPayService
{
    public static function getBaseUri()
    {
        if (env('ECPAY_PROD')) {
            return 'https://ecpg.ecpay.com.tw';
        }
        return 'https://ecpg-stage.ecpay.com.tw';
    }
}
