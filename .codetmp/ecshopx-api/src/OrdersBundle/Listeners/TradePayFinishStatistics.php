<?php

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\TradeFinishEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;

use OrdersBundle\Traits\GetOrderServiceTrait;

class TradePayFinishStatistics extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(TradeFinishEvent $event)
    {
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        app('log')->debug('订单号为' . $orderId . '统计开始');
        $sourceType = $event->entities->getTradeSourceType();
        $date = date('Ymd');
        $userData = app('redis')->sadd("companyIds:".$date, $companyId);

        if (in_array($sourceType, ['service', 'groups', 'service_groups', 'service_seckill'])) {
            $statisticsType = 'service';
        } elseif (in_array($sourceType, ['normal', 'normal_normal', 'normal_groups', 'normal_seckill', 'normal_community', 'bargain', 'normal_shopguide', 'normal_pointsmall'])) {
            $statisticsType = 'normal';
        } else {
            app('log')->debug('订单统计异常：订单号为' . $orderId . '，的订单类型' . $sourceType . '暂不统计');
            return true;
        }

        $orderService = $this->getOrderService($sourceType);
        $orderdata = $orderService->getOrderInfo($companyId, $orderId);
        if ($orderdata && isset($orderdata['orderInfo'])) {
            $orderdata = $orderdata['orderInfo'];
        }
        if ($orderdata['order_status'] == 'PAYED' || $orderdata['order_status'] == 'DONE' || $orderdata['order_status'] == 'WAIT_GROUPS_SUCCESS') {
            $redisKey = $this->__key($companyId, $statisticsType, $date);
            //统计商城订单总支付金额
            app('log')->debug('ajxorderPayFee：'.$orderdata['order_id'].'----->'.$orderdata['total_fee'].'------>'.$orderdata['user_id']);
            $newStore = app('redis')->hincrby($redisKey, "orderPayFee", $orderdata['total_fee']);
            //统计商城订单支付订单数
            $newStore = app('redis')->hincrby($redisKey, "orderPayNum", 1);

            //统计商城订单支付会员数
            $userData = app('redis')->sadd($redisKey."_orderPayUser", $orderdata['user_id']);

            if (isset($orderdata['salesman_id']) && $orderdata['salesman_id']) {
                $salespersonId = $orderdata['salesman_id'];
                $salespersonKey = $this->__salespersonKey($companyId, $statisticsType, $date, $salespersonId);
                //统计导购员销售额
                app('redis')->hincrby($salespersonKey, $salespersonId."_salesperson_orderPayFee", $orderdata['total_fee']);
                //统计导购员销售订单数
                app('redis')->hincrby($salespersonKey, $salespersonId."_salesperson_orderPayNum", 1);
                //统计导购员销售订单支付会员数
                app('redis')->sadd($salespersonKey."_".$salespersonId."_salesperson_orderPayUser", $orderdata['user_id']);
            }

            if (isset($orderdata['distributor_id'])) {
                $shopId = $orderdata['distributor_id'];
                //统计店铺订单总金额
                $newStore = app('redis')->hincrby($redisKey, $shopId."_orderPayFee", $orderdata['total_fee']);
                //统计店铺订单支付订单数
                $newStore = app('redis')->hincrby($redisKey, $shopId."_orderPayNum", 1);
                //统计店铺订单支付会员数
                $userData = app('redis')->sadd($redisKey."_".$shopId."_orderPayUser", $orderdata['user_id']);
            }

            if (!empty($orderdata['merchant_id'])) {
                $merchantId = $orderdata['merchant_id'];
                //统计店铺订单总金额
                $newStore = app('redis')->hincrby($redisKey, $merchantId."_merchant_orderPayFee", $orderdata['total_fee']);
                //统计店铺订单支付订单数
                $newStore = app('redis')->hincrby($redisKey, $merchantId."_merchant_orderPayNum", 1);
                //统计店铺订单支付会员数
                $userData = app('redis')->sadd($redisKey."_".$merchantId."_merchant_orderPayUser", $orderdata['user_id']);
            }
        }
    }

    private function __key($companyId, $type, $date)
    {
        return "OrderPayStatistics:".$type.":".$companyId.":".$date;
    }

    //导购统计键值
    private function __salespersonKey($companyId, $type, $date, $salespersonId)
    {
        return "OrderPaySalespersonStatistics:$type:$companyId:SalespersonId:$salespersonId:$date";
    }
}
