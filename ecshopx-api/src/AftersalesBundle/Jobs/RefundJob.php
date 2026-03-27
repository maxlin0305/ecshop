<?php

namespace AftersalesBundle\Jobs;

use EspierBundle\Jobs\Job;
use AftersalesBundle\Services\AftersalesRefundService;
use AftersalesBundle\Entities\Aftersales;
use AftersalesBundle\Entities\AftersalesDetail;
use OrdersBundle\Services\Orders\BargainNormalOrderService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\TradeService;

class RefundJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $params = $this->data;
        $refund_filter = [
            'refund_bn' => $params['refund_bn'],
            'company_id' => $params['company_id']
        ];
        $aftersalesRefundService = new AftersalesRefundService();
        $refund = $aftersalesRefundService->getInfo($refund_filter);
        if ($refund['refund_status'] != 'AUDIT_SUCCESS') {
            return false;
        }

        if (!$refund['aftersales_bn']) { // 售前退款
            $aftersalesRefundService->doRefund($refund_filter);

            $tradeId = $refund['trade_id'];
            $tradeService = new TradeService();
            $tradeInfo = $tradeService->getInfoById($tradeId);
            if ($tradeInfo['trade_source_type'] == 'bargain') {
                $orderId = $refund['order_id'];
                $companyId = $refund['company_id'];
                $orderService = new OrderService(new BargainNormalOrderService());
                $orderInfo = $orderService->getOrderInfo($companyId, $orderId);
                if ($orderInfo['orderInfo']['order_class'] == 'bargain') {
                    $bargainNormalOrderService = new BargainNormalOrderService();
                    $params['user_id'] = $orderInfo['orderInfo']['user_id'];
                    $params['bargain_id'] = $orderInfo['orderInfo']['act_id'];
                    $state = 0;
                    $bargainNormalOrderService->changeOrderActivityStatus($params, $state);
                }
            }
        } else { // 售后退款
            //线下退款直接更新状态
            if ($refund['refund_channel'] == 'offline') {
                $updateData = ['refund_status' => 'SUCCESS'];
                $aftersalesRefundService->updateOneBy($refund_filter, $updateData); // 更新退款单状态
            } elseif ($refund['refund_channel'] == 'original') {
                $aftersalesRefundService->doRefund($refund_filter);
            }

            $aftersales_filter = [
                'aftersales_bn' => $refund['aftersales_bn'],
                'company_id' => $refund['company_id']
            ];
            $aftersales_update = [
                'aftersales_status' => 2,
                'progress' => 4,
            ];
            $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
            $aftersalesRepository->update($aftersales_filter, $aftersales_update);// 更新售后主表状态

            $aftersalesDetailRepository = app('registry')->getManager('default')->getRepository(AftersalesDetail::class);
            $aftersalesDetailRepository->updateBy($aftersales_filter, $aftersales_update);// 更新售后明细状态
        }
    }
}
