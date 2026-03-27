<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */


$api->version('v1', function ($api) {
    // 跨境電商產地國相關信息
    $api->group(['namespace' => 'CrossBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/crossborder/origincountry', ['name' => '獲取產地國列表', 'as' => 'crossborder.origincountry.getlist', 'uses' => 'OriginCountry@getList']);
        $api->post('/crossborder/origincountry', ['name' => '產地國添加&修改', 'as' => 'crossborder.origincountry.isadd', 'uses' => 'OriginCountry@isAdd']);
        $api->put('/crossborder/origincountry/{origincountry_id}', ['name' => '產地國修改', 'as' => 'crossborder.origincountry.isupdate', 'uses' => 'OriginCountry@isUpdate']);
        $api->delete('/crossborder/origincountry/{origincountry_id}', ['name' => '產地國刪除', 'as' => 'crossborder.origincountry.isdel', 'uses' => 'OriginCountry@isDel']);
    });

    // 跨境電商設置
    $api->group(['namespace' => 'CrossBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/crossborder/set', ['name' => '獲取設置信息', 'as' => 'crossborder.set.info', 'uses' => 'CrossBorderSet@getInfo']);
        $api->post('/crossborder/set', ['name' => '保存設置信息', 'as' => 'crossborder.set.save', 'uses' => 'CrossBorderSet@Save']);
    });

    // 跨境電商階梯稅率策略設置
    $api->group(['namespace' => 'CrossBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/crossborder/taxstrategy', ['name' => '稅費策略列表', 'as' => 'crossborder.taxstrategy.getlist', 'uses' => 'Taxstrategy@getList']);
        $api->get('/crossborder/taxstrategy/{taxstrategy_id}', ['name' => '稅費策略詳情', 'as' => 'crossborder.taxstrategy.getinfo', 'uses' => 'Taxstrategy@getInfo']);
        $api->post('/crossborder/taxstrategy', ['name' => '稅費策略添加', 'as' => 'crossborder.taxstrategy.isadd', 'uses' => 'Taxstrategy@isAdd']);
        $api->put('/crossborder/taxstrategy/{taxstrategy_id}', ['name' => '稅費策略修改', 'as' => 'crossborder.taxstrategy.isupdate', 'uses' => 'Taxstrategy@isUpdate']);
        $api->delete('/crossborder/taxstrategy/{taxstrategy_id}', ['name' => '稅費策略刪除', 'as' => 'crossborder.taxstrategy.isdel', 'uses' => 'Taxstrategy@isDel']);
    });

});
