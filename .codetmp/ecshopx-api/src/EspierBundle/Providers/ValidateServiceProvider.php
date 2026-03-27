<?php

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider;

class ValidateServiceProvider extends ServiceProvider
{
    /**
     * 启动应用服务
     *
     * @return void
     */
    public function boot()
    {
        app('validator')->extend('mobile', function ($attribute, $value, $parameters) {
            return preg_match("/^[1][3-8]\d{9}$|^([6|9])\d{7}$|^[0][9]\d{8}$|^6\d{5}$/", $value);
        });

        app('validator')->extend('idcard', function ($attribute, $value, $parameters) {
            return preg_match("/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/", $value);
        });

        app('validator')->extend('postcode', function ($attribute, $value, $parameters) {
            return preg_match("/^\d{6}$/", $value);
        });

        app('validator')->extend('zhstring', function ($attribute, $value, $parameters) {
            preg_match("/^[０１２３４５６７８９a-z0-9A-Z\x{4e00}-\x{9fff}]+$/u", $value, $matches);
            if ($matches) {
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
