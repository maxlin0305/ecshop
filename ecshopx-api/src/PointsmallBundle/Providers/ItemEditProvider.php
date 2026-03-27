<?php

namespace PointsmallBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemEditProvider extends ServiceProvider
{
    protected $listen = [
        'PointsmallBundle\Events\ItemEditEvent' => [
        ],
    ];
}
