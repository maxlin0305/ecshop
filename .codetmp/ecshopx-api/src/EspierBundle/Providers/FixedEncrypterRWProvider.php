<?php

namespace EspierBundle\Providers;

use EspierBundle\Services\EncrypterRW;

use Illuminate\Support\ServiceProvider;

/**
 * Class FixedEncryptionServiceProvider
 *
 */
class FixedEncrypterRWProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('fixedencrypt', function ($app) {
            return new EncrypterRW();
        });
    }
}
