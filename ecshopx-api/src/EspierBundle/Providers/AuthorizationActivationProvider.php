<?php

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use CompanysBundle\Ego\CompanysActivationEgo;

class AuthorizationActivationProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('authorization', function () {
            return new CompanysActivationEgo();
        });
    }
}
