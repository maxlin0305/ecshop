<?php

namespace DepositBundle\Services\Stats;

/**
 * 会员卡储值交易
 */
class Day
{
    /**
     * 统计当天存储金额
     */
    public function getRechargeTotal($companyId, $date)
    {
        return app('redis')->connection('deposit')->hget('dayRechargeTotal'. $date, $companyId);
    }

    /**
     * 统计当天存储金额
     */
    public function getConsumeTotal($companyId, $date)
    {
        return app('redis')->connection('deposit')->hget('dayConsumeTotal'. $date, $companyId);
    }
}
