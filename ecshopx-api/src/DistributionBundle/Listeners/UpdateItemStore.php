<?php

namespace DistributionBundle\Listeners;

use PointsmallBundle\Events\ItemEditEvent;
use DistributionBundle\Services\DistributorItemsService;

class UpdateItemStore
{
    public function handle(ItemEditEvent $event)
    {
        $distributorItemService = new DistributorItemsService();
        $data = $event->entities;
        $filter = [
            'item_id' => $data['item_id'],
            'is_total_store' => true,
        ];
        $params['store'] = $data['store'];
        return $distributorItemService->updateBy($filter, $params);
    }
}
