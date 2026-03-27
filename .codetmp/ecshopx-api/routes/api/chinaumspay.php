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

    // 分賬相關api
    $api->group(['namespace' => 'ChinaumsPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/division/list', ['name' => '獲取分賬單列表', 'as' => 'division.list', 'uses' => 'Division@getList']);
        $api->get('/division/detail/list', ['name' => '獲取分賬單詳情列表', 'as' => 'division.detail.list', 'uses' => 'Division@getDetailList']);
        $api->get('/division/errorlog/list', ['name' => '獲取分賬失敗列表', 'as' => 'division.errorlog.list', 'uses' => 'Division@errorlogList']);
        $api->put('/division/errorlog/resubmit/{id}', ['name' => '分賬失敗重試', 'as' => 'division.errorlog.resubmit', 'uses' => 'Division@errrorlogResubmit']);
        $api->get('/division/exportdata', ['name' => '分賬單導出', 'as' => 'division.exportdata', 'uses' => 'Division@exportDivisionData']);
        $api->get('/division/detail/exportdata', ['name' => '分賬單明細導出', 'as' => 'division.detail.exportdata', 'uses' => 'Division@exportDivisionDetailData']);
    });
});
