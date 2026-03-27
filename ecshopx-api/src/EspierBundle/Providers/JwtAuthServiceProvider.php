<?php

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use EspierBundle\Auth\Jwt\EspierUserProvider as EspierUserProvider;
use EspierBundle\Auth\Jwt\EspierLocalUserProvider as EspierLocalUserProvider;
use EspierBundle\Auth\Jwt\EspierSuperAccountProvider as EspierSuperAccountProvider;
use EspierBundle\Auth\Jwt\EspierOauthUserProvider;
use EspierBundle\Auth\Jwt\EspierMerchantAccountProvider;

class JwtAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make('auth')->provider('espier', function ($app, $config) {
            return new EspierUserProvider();
        });
        // shopexid，oauth登录
        $this->app->make('auth')->provider('espier_oauth', function ($app, $config) {
            return new EspierOauthUserProvider();
        });
        // @todo espier_local 性能很差
        $this->app->make('auth')->provider('espier_local', function ($app, $config) {
            return new EspierLocalUserProvider($app, $config);
        });
        $this->app->make('auth')->provider('espier_super', function ($app, $config) {
            return new EspierSuperAccountProvider($app, $config);
        });
        $this->app->make('auth')->provider('espier_merchant', function ($app, $config) {
            return new EspierMerchantAccountProvider($app, $config);
        });
    }
}
