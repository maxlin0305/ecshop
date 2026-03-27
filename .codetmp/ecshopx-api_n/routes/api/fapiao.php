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
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {

        $api->get('/fapiao/getFapiaoset', ['name' => '获取发票配置', 'as' => 'fapiao.getFapiaoset', 'uses' => 'Fapiao@getFapiaoset']);
        $api->post('/fapiao/saveFapiaoset', ['name' => '保存发票配置', 'as' => 'fapiao.saveFapiaoset', 'uses' => 'Fapiao@saveFapiaoset']);
    });
});