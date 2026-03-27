<?php
/**
 * Created by PhpStorm.
 * User: xiaqc
 * Date: 2020/11/6
 * Time: 14:14
 */

namespace GoodsBundle\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class ItemStoreUpdateProvider extends ServiceProvider
{
    protected $listen = [
        'GoodsBundle\Events\ItemStoreUpdateEvent' => [
            'MembersBundle\Listeners\SendTemplateMsgListener',
        ],
    ];
}
