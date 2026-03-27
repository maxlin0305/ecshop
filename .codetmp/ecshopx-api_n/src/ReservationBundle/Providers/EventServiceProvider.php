<?php

namespace ReservationBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'ReservationBundle\Events\ReservationFinishEvent' => [
            'ReservationBundle\Listeners\ReservationFinishWorkShiftAdd',
            'ReservationBundle\Listeners\ReservationFinishSendWxaTemplate',
            'ReservationBundle\Listeners\ReservationRemindSendWxaTemplate',
        ],
    ];
}
