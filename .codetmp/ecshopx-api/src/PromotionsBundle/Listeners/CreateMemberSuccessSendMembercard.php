<?php

namespace PromotionsBundle\Listeners;

use MembersBundle\Events\CreateMemberSuccessEvent;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use PromotionsBundle\Services\RegisterPromotionsService;

class CreateMemberSuccessSendMembercard extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent  $event
     * @return void
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        if (!$event->ifRegisterPromotion) {
            return;
        }

        $registerPromotionsService = new RegisterPromotionsService();
        $registerPromotionsService->actionPromotionByCompanyId($event->companyId, $event->userId, $event->mobile, 'membercard');
    }
}
