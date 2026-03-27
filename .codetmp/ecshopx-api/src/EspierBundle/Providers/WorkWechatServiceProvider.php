<?php

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use WorkWechatBundle\Services\WechatManagerService;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class WorkWechatServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('wechat.work.wechat', function () {
            return new WechatManagerService();
        });
    }
}
