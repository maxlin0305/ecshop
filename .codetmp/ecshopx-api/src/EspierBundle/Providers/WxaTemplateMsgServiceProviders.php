<?php

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;
use PromotionsBundle\Services\WxaTemplateMsgService;
use PromotionsBundle\Services\AliTemplateMsgService;

class WxaTemplateMsgServiceProviders extends ServiceProvider
{
    public function register()
    {
        $this->registerWebsocketClient();
    }

    public function registerWebsocketClient()
    {
        $this->app->singleton('wxaTemplateMsg', function () {
            return new WxaTemplateMsgService();
        });

        $this->app->singleton('aliTemplateMsg', function () {
            return new AliTemplateMsgService();
        });
    }
}
