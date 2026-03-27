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


// 菜單管理
$api->version('v1', function($api) {
    $api->group(['namespace' => 'ShopmenuBorderBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        // 獲取平臺自己的後臺菜單權限
//        $api->get('/permission', ['as' => 'super.admin.roles.permission', 'uses'=>'Accounts@getPermission']);
        // 獲取菜單列表
        $api->get('/shopmenu',  [ 'as' => 'super.admin.shopmenu.get',   'uses'=>'ShopMenu@getShopMenu']);
        // 添加菜單
        $api->post('/shopmenu', [ 'as' => 'super.admin.shopmenu.add',   'uses'=>'ShopMenu@addShopMenu']);
        // 修改菜單
        $api->put('/shopmenu',  [ 'as' => 'super.admin.shopmenu.update','uses'=>'ShopMenu@updateShopMenu']);
        // 刪除菜單
        $api->delete('/shopmenu/{shopmenuId}',  [ 'as' => 'super.admin.shopmenu.del','uses'=>'ShopMenu@deleteShopMenu']);
        // 下載菜單
        $api->get('/shopmenu/down',  [ 'as' => 'super.admin.shopmenu.down','uses'=>'ShopMenu@downShopMenu']);
        // 上傳菜單
        $api->post('/shopmenu/upload',  [ 'as' => 'super.admin.shopmenu.upload','uses'=>'ShopMenu@uploadMenu']);
    });
});
