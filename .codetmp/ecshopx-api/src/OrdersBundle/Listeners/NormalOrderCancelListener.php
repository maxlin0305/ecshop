<?php

namespace OrdersBundle\Listeners;

use OrdersBundle\Events\NormalOrderCancelEvent;
use OrdersBundle\Services\Orders\NormalOrderService;

class NormalOrderCancelListener
{
    /**
     * Handle the event.
     *
     * @param  NormalOrderCancelEvent  $event
     * @return void
     */
    public function handle(NormalOrderCancelEvent $event)
    {
        $companyId = $event->entities['company_id'];
        $orderId = $event->entities['order_id'];
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $normalOrderService = new NormalOrderService();
        $orderInfo = $normalOrderService->getInfo($filter);
        if ($orderInfo['order_status'] == 'PAYED' && $orderInfo['cancel_status'] == 'WAIT_PROCESS') {
            $normalOrderService->autoConfirmCancelOrder($companyId, $orderId);
        }
    }
}
