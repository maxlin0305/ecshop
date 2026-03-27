<?php

$api->version('v1', function ($api) {
    $api->group(['namespace' => 'HfPayBundle\Http\ThirdApi\V1\Action'], function ($api) {
        //汇付天下推送通知
        $api->post('/third/hfpay/notify', ['as' => 'third.hfpay.notify', 'uses' => 'HfPay@notify']);
    });

    $api->group(['namespace' => 'EcPayBundle\Http\ThirdApi\V1\Action'], function ($api) {
        //绑卡前端回调 POST
        $api->post('/third/ecpay_card/notify', ['as' => 'third.ecpay.notify', 'uses' => 'EcPayCard@notify']);
    });
});
