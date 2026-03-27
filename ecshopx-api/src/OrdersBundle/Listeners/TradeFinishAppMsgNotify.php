<?php

namespace OrdersBundle\Listeners;

use CompanysBundle\Services\OperatorDataPassService;
use CompanysBundle\Services\PushMessageService;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use MembersBundle\Services\MemberService;
use OrdersBundle\Events\TradeFinishEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;

class TradeFinishAppMsgNotify  extends BaseListeners implements ShouldQueue
{
    use GetOrderServiceTrait;

    protected $queue = 'slow';

    public $normalOrdersRepository;
    public $normalOrdersItemsRepository;
    /**
     * Handle the event.
     * @param TradeFinishEvent $event
     * @return false|void
     */
    public function handle(TradeFinishEvent $event)
    {
        app('log')->info('触发订单完成通知:'.json_encode($event,JSON_UNESCAPED_UNICODE));
        $companyId = $event->entities->getCompanyId();
        $orderId = $event->entities->getOrderId();
        $sourceType = $event->entities->getTradeSourceType();
        app('log')->info('订单ID:'.$orderId.'开发发送消息，companyId:'.$companyId.'sourceType:'.$sourceType);
        $this->doSendAppMsg($orderId,$companyId,$sourceType);
        app('log')->info('订单ID:'.$orderId.'消息发送完成');
    }

    /****
     * 发送订单下单成功消息
     * @param $orderId
     * @return void
     * @throws \Exception
     */
    private function doSendAppMsg($orderId,$companyId,$sourceType)
    {
        $orderService = $this->getOrderService($sourceType);
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        if ($orderData && isset($orderData['orderInfo'])) {
            $orderInfo = $orderData['orderInfo'];
            app('log')->info('订单信息:'.json_encode($orderInfo,JSON_UNESCAPED_UNICODE));

        }else{
            throw new \Exception('订单信息未找到');
        }
        $itemList = $orderInfo['items'];
        # 判断是否开启通知
        $operatorDataPassService = new OperatorDataPassService();
        $status = $operatorDataPassService->getPushMessageStatusV2($orderInfo['merchant_id'],$orderInfo['company_id'],$orderInfo['distributor_id']);
       // echo '消息开关为:'.$status."\r\n";
        //$status =1;
        app('log')->info($orderId.'消息开关:'.$status);
        if($status == 1 ){
            # 获取商品信息
            if(!empty($itemList)){
                foreach ($itemList as $i){
                    $items_data[] = [
                        'name' => $i['item_name'],
                        'num'  => $i['num']
                    ];
                }
            }
            $memberService = new MemberService();
            $memberInfo    = $memberService->getMemberInfo(['user_id' => $orderInfo['user_id']]);
            $request_params = [
                'memberId' => $memberInfo['app_member_id'] ?? 0 ,
                'messageId'   => 0,
                'messageType' => 2,//消息类型[2:下单成功通知]
                'data' => [
                    "orderNo"              =>  $orderId,//订单号
                    "commodityInfo"        =>  $items_data ?? [],//商品信息
                    "deliveryCompany"      =>  $orderInfo['delivery_corp'] ?? '',//快递公司
                    "deliveryOne"          =>  $orderInfo['delivery_code'] ?? '',//快递单号
                    "deliveryDate"         =>  date("Y-m-d H:i:s",time()),//发货日期
                    "orderCreationDate"    => isset($orderInfo['create_time']) && !empty($orderInfo['create_time']) ? date("Y-m-d H:i:s",$orderInfo['create_time']) : '',//订单创建日期
                ],
            ];
            $request_url = config('common.zgj_app_url').'/v1/api/message/shopping';
            $header_param = [
                'Content-Type'  =>'application/json; charset=utf-8' ,
                'Accept'        => "application/json",
                'appKey'        => config('common.zgj_app_key'),
                'appSecret'     => config('common.zgj_app_secret'),
            ];
            $pushMessageService = new PushMessageService();
            $typeName = '訂單成功通知:'.$orderId;
            app('log')->info('request_params'.json_encode($request_params));
            $pushMessageService->createPushMessage('post',
                $request_url,$request_params,2,$typeName,
                $orderInfo['company_id'] ?? 0 ,
                $orderInfo['distributor_id']>0 ? 0 : $orderInfo['merchant_id'],//有店鋪ID的時候就不要商鋪ID了
                $orderInfo['distributor_id'] ?? 0,
                $orderInfo['user_id'] ?? 0,
                $header_param
            );
        }
    }
    public function testSendAppMsg()
    {
        $orderId = '4290912000180003';
        $companyId = '1';
        $sourceType = 'normal';
        $this->doSendAppMsg($orderId,$companyId,$sourceType);
    }
}
