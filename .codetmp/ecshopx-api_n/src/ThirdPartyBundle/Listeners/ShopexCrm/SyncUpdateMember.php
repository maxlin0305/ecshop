<?php

namespace ThirdPartyBundle\Listeners\ShopexCrm;

use MembersBundle\Events\UpdateMemberSuccessEvent;
use ThirdPartyBundle\Services\ShopexCrm\SyncSingleMemberService;

class SyncUpdateMember
{
    /**
     * 同步会员信息
     * @param UpdateMemberSuccessEvent $event
     */
    public function handle(UpdateMemberSuccessEvent $event)
    {
        if (empty(config('crm.crm_sync'))) {
            return true;
        }
        $syncSingleMemberService = new SyncSingleMemberService();
        $syncSingleMemberService->syncSingleMember($event->companyId, $event->userId);
    }
}
