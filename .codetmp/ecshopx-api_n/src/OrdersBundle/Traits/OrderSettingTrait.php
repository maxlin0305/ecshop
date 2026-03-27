<?php

namespace OrdersBundle\Traits;

use Dingo\Api\Exception\ResourceException;

trait OrderSettingTrait
{
    public $settingType = [
        'order_finish_time' => 7, //默认7天
        'order_cancel_time' => 15, //默认15分钟
        'notpay_order_wxapp_notice' => 0,  //默认0分钟，不发送通知
        'latest_aftersale_time' => 0, //默认确认收货后不可申请售后
        'aftersale_close_time' => 7, //申请售后未处理7天关闭
        'auto_refuse_time' => 0, //售后驳回
        'auto_aftersales' => false, //未发货售后自动同意
        'offline_aftersales' => false,
    ];

    public function getOrdersSetting($companyId, $type = null)
    {
        $key = $this->__key();
        $setting = app('redis')->hget($key, $companyId);
        $result = $setting ? json_decode($setting, true) : [];

        if ($type) {
            return $result[$type] ?? ($this->settingType[$type] ?? 0);
        }
        return $result;
    }

    public function setOrdersSetting($companyId, $setting)
    {
        $key = $this->__key();
        if (!$setting) {
            $setting = $this->settingType;
        } else {
            $setting = $this->__commonParams($setting);
            $this->__checkParams($setting);
        }
        $setting = json_encode($setting);
        app('redis')->hset($key, $companyId, $setting);
        return $this->getOrdersSetting($companyId);
    }

    private function __commonParams($setting)
    {
        $setting['order_cancel_time'] = intval($setting['order_cancel_time']);
        $setting['latest_aftersale_time'] = intval($setting['latest_aftersale_time']);
        return $setting;
    }


    private function __checkParams($setting)
    {
        if ($setting['order_cancel_time'] < 5) {
            throw new ResourceException('订单自动取消时间需大于等于5分钟');
        }
        return true;
    }

    // public function getAllSetting($type = null)
    // {
    //     $allSetting = app('redis')->hgetall($key);
    //     foreach ($allSetting as $companyId => $setting) {
    //         $setting = json_decode($setting, true);
    //         if ($type) {
    //             $result[$companyId] = $setting[$type];
    //         }
    //     }
    //     return $result ?? [];
    // }

    private function __key()
    {
        return 'order_validity_setting';
    }
}
