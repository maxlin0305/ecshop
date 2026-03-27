<?php

namespace HfPayBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'HfPayBundle\Events\HfpayProfitSharingEvent' => [
            'HfPayBundle\Listeners\ProfitSharing',
        ],
        'HfPayBundle\Events\HfPayDistributorWithdrawEvent' => [
            'HfPayBundle\Listeners\DistributorWithdrawListener',
        ],
        'HfPayBundle\Events\HfPayPopularizeWithdrawEvent' => [
            'HfPayBundle\Listeners\PopularizeWithdrawListener',
        ],
    ];

    /**
     * 需要注册的订阅者类。
     *
     * @var array
     */
    protected $subscribe = [
        'HfPayBundle\Listeners\HfpayTradeRecordListener',
        'HfPayBundle\Listeners\HfEnterapplyInit',
    ];
}
