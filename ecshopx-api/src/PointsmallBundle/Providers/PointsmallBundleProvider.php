<?php

namespace PointsmallBundle\Providers;

use Illuminate\Support\ServiceProvider;
use PointsmallBundle\Routes\ServiceApi;

class PointsmallBundleProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerRoutes();
    }
    protected function registerRoutes()
    {
        ServiceApi::register();
    }
}
