<?php

namespace GoodsBundle\Providers;

use Illuminate\Support\ServiceProvider;
use GoodsBundle\Routes\ServiceApi;

class GoodsBundleProvider extends ServiceProvider
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
