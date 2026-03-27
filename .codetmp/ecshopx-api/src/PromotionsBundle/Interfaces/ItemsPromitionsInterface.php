<?php

namespace PromotionsBundle\Interfaces;

interface ItemsPromitionsInterface
{
    /**
     * 添加活动判断特有参数
     */
    public function checkActivityParams(array $data);
}
