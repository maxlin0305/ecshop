<?php

$api->version('v1', function ($api) {
    $api->group(['prefix' => '/aliminiapp', 'namespace' => 'AliBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/setting/info', ['name' => '獲取支付寶小程序設置', 'middleware' => 'activated', 'as' => 'aliminiapp.setting.info', 'uses' => 'AliMiniAppSettingController@actionInfo']);
        $api->post('/setting/save', ['name' => '保存支付寶小程序設置', 'middleware' => 'activated', 'as' => 'aliminiapp.setting.save', 'uses' => 'AliMiniAppSettingController@actionSave']);
    });
});
