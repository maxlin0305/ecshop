<?php

namespace MembersBundle\Listeners;

use MembersBundle\Events\CreateMemberSuccessEvent;
use MembersBundle\Services\MemberService;
use EspierBundle\Listeners\BaseListeners;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterPointListener extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  TradeFinishEvent $event
     * @return void
     */
    public function handle(CreateMemberSuccessEvent $event)
    {
        $pointMemberService = new \PointBundle\Services\PointMemberService();
        $pointMemberService->RegisterPoint($event->userId, $event->inviter_id, $event->companyId);
//        $memberService = new MemberService();
//        $memberService->usePointOpen($event->userId, $event->companyId);
    }
}
