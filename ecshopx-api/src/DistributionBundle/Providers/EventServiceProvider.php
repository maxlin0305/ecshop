<?php

namespace DistributionBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'DistributionBundle\Events\DistributorCreateEvent' => [
            'SystemLinkBundle\Listeners\ShopCreateSendOme',
        ],

        'DistributionBundle\Events\DistributorUpdateEvent' => [
            'SystemLinkBundle\Listeners\ShopUpdateSendOme',
        ],
    ];
}
