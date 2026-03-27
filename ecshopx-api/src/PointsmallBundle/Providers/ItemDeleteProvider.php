<?php

namespace PointsmallBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemDeleteProvider extends ServiceProvider
{
    protected $listen = [
        'PointsmallBundle\Events\ItemDeleteEvent' => [
        ],
    ];
}
