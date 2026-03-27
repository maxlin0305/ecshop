<?php

namespace GoodsBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemDeleteProvider extends ServiceProvider
{
    protected $listen = [
        'GoodsBundle\Events\ItemDeleteEvent' => [
        ],
    ];
}
