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
    // 來源相關api
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/datacube/sources',               ['name' => '添加來源', 'as' => 'source.create', 'uses' => 'Sources@createSources']);
        $api->get('/datacube/sources',                ['name' => '獲取來源列表', 'as' => 'source.list',   'uses' => 'Sources@getSourcesList']);
        $api->get('/datacube/sources/{source_id}',    ['name' => '獲取來源詳情', 'as' => 'source.detail', 'uses' => 'Sources@getSourcesDetail']);
        $api->delete('/datacube/sources/{source_id}', ['name' => '刪除來源', 'as' => 'source.delete', 'uses' => 'Sources@deleteSources']);
        $api->put('/datacube/sources/{source_id}',    ['name' => '更新來源', 'as' => 'source.update', 'uses' => 'Sources@updateSources']);
        $api->get('/datacube/companydata',    ['name' => '獲取商城統計數據', 'as' => 'datacube.company.data', 'uses' => 'CompanyData@getCompanyData']);
        $api->get('/datacube/distributordata',    ['name' => '獲取商城門店統計數據', 'as' => 'datacube.distributor.data', 'uses' => 'DistributorData@getDistributorData']);
        $api->get('/datacube/goodsdata',    ['name' => '獲取商品統計數據', 'as' => 'datacube.goods.data', 'uses' => 'GoodsData@getGoodsData']);
        $api->post('/datacube/savetags',     ['name' => '來源綁定會員標簽', 'as' => 'source.savetags', 'uses' => 'Sources@saveSourceTags']);

    });

    // 來源監控相關api
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/datacube/monitors',                        ['name' => '獲取頁面監控列表', 'as' => 'monitors.list',              'uses' => 'SourcesMonitors@getSourcesMonitors']);
        $api->get('/datacube/monitors/{monitor_id}',           ['name' => '獲取監控詳情', 'as' => 'monitors.detail',            'uses' => 'SourcesMonitors@getMonitorsDetail']);
        $api->delete('/datacube/monitors/{monitor_id}',        ['name' => '刪除監控頁面', 'as' => 'monitors.delete',            'uses' => 'SourcesMonitors@deleteMonitors']);
        $api->post('/datacube/monitors',                       ['name' => '添加監控鏈接', 'as' => 'monitors.add',               'uses' => 'SourcesMonitors@addMonitors']);
        $api->post('/datacube/monitorsRelSources',             ['name' => '監控頁面關聯來源', 'as' => 'monitors.relsources.detail', 'uses' => 'SourcesMonitors@relSources']);
        $api->get('/datacube/monitorsRelSources/{monitor_id}', ['name' => '獲取監控頁面關聯來源信息', 'as' => 'monitors.relsources.list',   'uses' => 'SourcesMonitors@getRelSources']);
        $api->delete('/datacube/monitorsRelSources/{monitor_id}/{source_id}', ['name' => '刪除監控頁面的某個來源', 'as' => 'monitors.relsources.delete', 'uses' => 'SourcesMonitors@deleteRelSources']);
        $api->get('/datacube/monitorsstats',                   ['name' => '獲取監控頁面的來源統計', 'as' => 'monitors.stats',             'uses' => 'SourcesMonitors@getStats']);
    });

    // 來源監控相關api,不需要權限
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action'], function($api) {
        $api->get('/datacube/monitorsWxaCode64',     ['name' => '獲取監控的小程序碼參數', 'as' => 'monitors.wxacode64',     'uses' => 'SourcesMonitors@getMonitorWxaCode64']);
        $api->get('/datacube/monitorsWxaCodeStream', ['name' => '獲取監控的小程序碼流信息', 'as' => 'monitors.wxacodestream', 'uses' => 'SourcesMonitors@getMonitorWxaCodeStream']);
    });

    // 小程序頁面信息api
    $api->group(['namespace' => 'DataCubeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/datacube/miniprogram/pages', ['name' => '獲取小程序的頁面及參數信息', 'as' => 'miniprogram.pages', 'uses' => 'MiniProgram@getPages']);
    });

});
