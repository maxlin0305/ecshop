<?php

namespace WechatBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'WechatBundle\Events\WechatSubscribeEvent' => [
            'MembersBundle\Listeners\WechatSubscribeListener',
        ],
        'WechatBundle\Events\WxShopsAddEvent' => [
            'CompanysBundle\Listeners\WxShopsAddListener',
        ],
        'WechatBundle\Events\WxShopsUpdateEvent' => [
            'CompanysBundle\Listeners\WxShopsUpdateListener',
        ],
    ];
}
