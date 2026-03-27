<?php

namespace ThirdPartyBundle\Listeners\ShopexCrm;

use OrdersBundle\Events\NormalOrderConfirmReceiptEvent;
use ThirdPartyBundle\Services\ShopexCrm\SyncSingleOrderService;

class SyncConfirmReceiptOrder
{
    public function handle(NormalOrderConfirmReceiptEvent $event)
    {
        if (empty(config('crm.crm_sync'))) {
            return true;
        }
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $syncSingleOrderService = new SyncSingleOrderService();
        $syncSingleOrderService->syncSingleOrder($company_id, $order_id);
    }
}
