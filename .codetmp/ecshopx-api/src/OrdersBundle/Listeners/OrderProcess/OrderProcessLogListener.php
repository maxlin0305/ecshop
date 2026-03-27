<?php

namespace OrdersBundle\Listeners\OrderProcess;

use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\OrderProcessLogService;

class OrderProcessLogListener extends BaseListeners implements ShouldQueue
{
    protected $queue = 'slow';

    /**
     * Handle the event.
     *
     * @param  OrderProcessLogEvent  $event
     * @return void
     */
    public function handle(OrderProcessLogEvent $event)
    {
        $data = [
            'order_id' => $event->entities['order_id'],
            'company_id' => $event->entities['company_id'],
            'operator_type' => $event->entities['operator_type'],
            'operator_id' => $event->entities['operator_id'] ?? 0,
            'remarks' => $event->entities['remarks'],
            'detail' => $event->entities['detail'] ?? '',
            'params' => $event->entities['params'] ?? [],
        ];
        $orderProcessLogService = new OrderProcessLogService();
        $orderProcessLogService->createOrderProcessLog($data);
        return true;
    }
}
