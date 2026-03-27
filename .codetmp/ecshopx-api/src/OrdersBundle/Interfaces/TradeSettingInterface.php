<?php

namespace OrdersBundle\Interfaces;

interface TradeSettingInterface
{
    /**
     * 存储配置
     */
    public function setSetting($companyId, $params);

    /**
     * 获取配置
     */
    public function getSetting($companyId);
}
