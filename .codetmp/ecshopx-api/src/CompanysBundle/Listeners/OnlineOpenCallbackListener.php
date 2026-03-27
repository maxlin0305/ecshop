<?php

namespace CompanysBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Events\CompanyCreateEvent;
use CompanysBundle\Services\PrismIshopexService;
use CompanysBundle\Services\AuthService;

class OnlineOpenCallbackListener extends BaseListeners implements
    ShouldQueue
// class OnlineOpenCallbackListener extends BaseListeners
{
    /**
     * Handle the event.
     *
     * @param  CompanyCreateEvent $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        // if (!config('common.system_is_saas') || !config('common.system_open_online')) {
        if (!config('common.system_is_saas')) {
            return false;
        }
        $issue_id = $event->entities['issue_id'] ?? '';
        if (!$issue_id) {
            return false;
        }
        $authService = new AuthService();
        $url = $authService->getOuthorizeurl();
        $params = [
            'issue_id' => $issue_id,
            'url' => $url,
        ];
        $prismIshopexService = new PrismIshopexService();
        $result = $prismIshopexService->onlineOpenCallback($params);
        return $result;
    }
}
