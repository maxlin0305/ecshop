<?php

namespace OrdersBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'OrdersBundle\Events\TradeFinishEvent' => [
            'OrdersBundle\Listeners\UpdateOrderStatusListener',
            'ThirdPartyBundle\Listeners\MarketingCenter\TradePushMarketingCenter',
            'OrdersBundle\Listeners\HelpToPayForPurchasePlusOne',
            'OrdersBundle\Listeners\TradeFinishConsumeCard',
            'OrdersBundle\Listeners\TradeFinishNotifyPush',
            'OrdersBundle\Listeners\TradeFinishSmsNotify',
            'OrdersBundle\Listeners\TradeFinishWxaTemplateMsg',
            'OrdersBundle\Listeners\UpdateItemSalesListener',
            'OrdersBundle\Listeners\UpdateGroupsActivityOrder',
            'OrdersBundle\Listeners\TradeFinishCountBrokerage',
            'OrdersBundle\Listeners\TradeFinishLinkMember',
            'OrdersBundle\Listeners\TradePayFinishStatistics',   //订单一些统计
            'OrdersBundle\Listeners\TradeFinishProfit',      // 订单分润
            'SystemLinkBundle\Listeners\TradeFinishSendOme', // 订单发送到ome
            'OrdersBundle\Listeners\PrinterOrder',           //订单支付完成推送到shop端
            'OrdersBundle\Listeners\TradeFinishFapiao',      //存入发票数据
            'OrdersBundle\Listeners\TradeFinishCustomDeclareOrder', //跨境订单清关
            'OrdersBundle\Listeners\TradeFinishWorkWechatNotify',//企业微信消息通知
        ],
        'OrdersBundle\Events\MerchantTradeFinishEvent' => [
        ],
        'OrdersBundle\Events\OrderProcessLogEvent' => [
            'OrdersBundle\Listeners\OrderProcess\OrderProcessLogListener', // 订单流程记录
        ],
        'OrdersBundle\Events\NormalOrderCancelEvent' => [
            'OrdersBundle\Listeners\NormalOrderCancelListener',
        ],
    ];
}
