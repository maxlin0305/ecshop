<?php

namespace KaquanBundle\Listeners;

use KaquanBundle\Events\WechatCardSyncEvent;
use KaquanBundle\Services\DiscountCardService;

class WechatCardSyncListener
{
    /** @var discountCardService */
    private $discountCardService;

    public function __construct(DiscountCardService $DiscountCardService)
    {
        $this->discountCardService = $DiscountCardService;
    }

    /**
     * Handle the event.
     *
     * @param  WechatCardSyncEvent  $event
     * @return void
     */
    public function handle(WechatCardSyncEvent $event)
    {
        $cardIds = $event->cardIds;
        return $this->discountCardService->saveWechatCard($cardIds);
    }
}
