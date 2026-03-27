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

$api->version('v1', function($api) {
    //登錄
    $api->post('/operator/login',['middleware'=>'shoplogin', function () use ($api) {
        $credentials = app('request')->only('username', 'password', 'logintype', 'product_model', 'agreement_id');
        $token = app('auth')->guard('api')->attempt($credentials);
        return response()->json(['data'=>['token'=>$token]]);
    }]);

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        //獲取oauth登錄鏈接
        $api->get('/operator/authorizeurl', ['name' => '登陸獲取oauth鏈接','as' => 'operator.authorizeurl', 'uses' => 'Operators@getOuthorizeurl']);
        // 獲取oauth登出鏈接
        $api->get('/operator/oauth/logout', ['name' => '獲取oauth登出鏈接','as' => 'operator.authorizeurl', 'uses' => 'Operators@getOauthLogouturl']);
    });

    $api->post('/operator/oauth/login',['middleware'=>'shoplogin', function () use ($api) {
        $credentials = app('request')->only('code','logintype', 'product_model');
        $token = app('auth')->guard('oauthapi')->attempt($credentials);
        return response()->json(['data'=>['token'=>$token]]);
    }]);

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        $api->post('/operator/workwechat/oauth/login', ['name' => '企業微信oauth登錄', 'middleware' => 'shoplogin', 'as' => 'workwechat.oauth.login', 'uses' => 'WorkWechat@login']);
        $api->post('/operator/workwechat/bind_mobile', ['name' => '綁定企業微信手機號', 'middleware' => 'shoplogin', 'as' => 'workwechat.bind', 'uses' => 'WorkWechat@bindMobile']);
        $api->get('/operator/workwechat/authorizeurl', ['name' => '獲取企業微信oauth登錄鏈接', 'middleware' => 'shoplogin', 'as' => 'workwechat.authorizeurl', 'uses' => 'WorkWechat@getWorkwechatOuthorizeurl']);
    });
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        $api->post('/operator/wechat/oauth/login', ['name' => '微信oauth登錄', 'middleware' => 'shoplogin', 'as' => 'wechat.oauth.login', 'uses' => 'Wxapp@login']);
        $api->post('/operator/wechat/lite/login', ['name' => '小程序登錄', 'middleware' => 'shoplogin', 'as' => 'wechat.lite.login', 'uses' => 'Wxapp@wxLiteLogin']);
        $api->post('/operator/wechat/bind_mobile', ['name' => '綁定微信手機號', 'middleware' => 'shoplogin', 'as' => 'wechat.bind.mobile', 'uses' => 'Wxapp@bindMobile']);
        $api->post('/operator/wechat/bind_account', ['name' => '綁定賬號', 'middleware' => 'shoplogin', 'as' => 'wechat.bind.account', 'uses' => 'Wxapp@bindAccountLogin']);
        $api->get('/operator/wechat/authorizeurl', ['name' => '獲取微信oauth登錄鏈接', 'middleware' => 'shoplogin', 'as' => 'wechat.authorizeurl', 'uses' => 'Wxapp@getWechatOuthorizeurl']);
        $api->post('/operator/wechat/sms/code', ['name' => '發送手機短信驗證碼', 'middleware' => 'shoplogin', 'as' => 'wechat.sms.code', 'uses' => 'Wxapp@getSmsCode']);
        $api->post('/operator/wechat/distributor/js/config', ['name' => '前臺獲取JsSDK', 'middleware' => 'shoplogin', 'as' => 'wechat.distributor.js.config', 'uses' => 'Wxapp@getDistributorJsConfig']);
    });

    //刷新token
    $api->get('/token/refresh', ['middleware'=>'jwt.refresh', function () use ($api) {
        return response()->json(['data'=>['result'=>true]]);
    }]);
});

