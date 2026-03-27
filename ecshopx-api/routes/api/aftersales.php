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
    // 售後相關api
    $api->group(['namespace' => 'AftersalesBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/aftersales', ['name' => '獲取售後列表', 'middleware' => 'datapass', 'as' => 'aftersales.list', 'uses' => 'Aftersales@getAftersalesList']);
        $api->get('/aftersales/logExport',  ['name'=>'導出售後列表', 'as' => 'aftersales.logexport', 'uses'=>'Aftersales@logExport']);
        $api->get('/aftersales/{aftersales_bn}', ['name' => '獲取售後詳情', 'as' => 'aftersales.info', 'uses' => 'Aftersales@getAftersalesDetail']);
        $api->post('/aftersales/review', ['name' => '售後審核', 'as' => 'aftersales.review', 'uses' => 'Aftersales@aftersalesReview']);
        $api->post('/aftersales/refundCheck', ['name' => '售後退款審核', 'as' => 'aftersales.review', 'uses' => 'Aftersales@refundCheck']);
        $api->get('/aftersales/reason/list', ['name' => '售後原因列表獲取', 'as' => 'aftersales.reason.list', 'uses' => 'Reason@getSreasonList']);
        $api->post('/aftersales/reason/save',  ['name'=>'售後原因列表保存', 'as' => 'aftersales.reason.save', 'uses'=>'Reason@Saveset']);
        $api->get('/aftersales/financial/export',  ['name'=>'導出售後報表', 'as' => 'aftersales.financial.export', 'uses'=>'Aftersales@financialExport']);
        $api->get('/aftersales/remind/detail',  ['name'=>'售後提醒內容獲取', 'as' => 'aftersales.remind.get', 'uses'=>'Aftersales@getRemind']);
        $api->post('/aftersales/remind',  ['name'=>'售後提醒內容設置', 'as' => 'aftersales.remind.set', 'uses'=>'Aftersales@setRemind']);

        $api->put('/aftersales/remark', ['name' => '更新售後備註', 'as' => 'aftersales.remark.update', 'uses' => 'Aftersales@updateRemark']);
        $api->post('/aftersales/apply', ['name' => '創建售後申請', 'as' => 'aftersales.apply', 'uses' => 'Aftersales@apply']);
        $api->post('/aftersales/sendback', ['name' => '售後管理員填寫寄回信息', 'as' => 'aftersales.sendback', 'uses' => 'Aftersales@sendback']);
    });

    // 退款單相關api
    $api->group(['namespace' => 'AftersalesBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/refund', ['name' => '獲取退款單列表', 'as' => 'refund.list', 'uses' => 'Refund@getRefundList']);
        $api->get('/refund/detail/{refund_bn}', ['name' => '獲取退款單詳情', 'as' => 'refund.info', 'uses' => 'Refund@getRefundsDetail']);
        $api->get('/refund/logExport',  ['name'=>'導出退款單列表', 'as' => 'refund.logexport', 'uses'=>'Refund@logExport']);
    });
});
