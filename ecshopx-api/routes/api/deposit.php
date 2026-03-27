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
    // 微信相關信息
    $api->group(['namespace' => 'DepositBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/deposit/rechargerule',        ['name' => '創建充值面額規則','middleware'=>'activated',  'as' => 'deposit.rule.create',  'uses'=>'Recharge@createRechargeRule']);
        $api->get('/deposit/rechargerules',        ['name' => '獲取充值面額規則','middleware'=>'activated',  'as' => 'deposit.rule.list',    'uses'=>'Recharge@getRechargeRuleList']);
        $api->delete('/deposit/rechargerule/{id}', ['name' => '根據ID刪除充值面額規則','middleware'=>'activated',  'as' => 'deposit.rule.delete',  'uses'=>'Recharge@deleteRechargeRuleById']);
        $api->put('/deposit/rechargerule',         ['name' => '編輯指定充值面額規則','middleware'=>'activated',  'as' => 'deposit.rule.edit',    'uses'=>'Recharge@editRechargeRuleById']);

        $api->post('/deposit/recharge/agreement',   ['name' => '設置儲值協議','middleware'=>'activated',    'as' => 'deposit.recharge.agreement.set',  'uses'=>'Recharge@setRechargeAgreement']);
        $api->get('/deposit/recharge/agreement',    ['name' => '獲取儲值協議','middleware'=>'activated',    'as' => 'deposit.recharge.agreement.get',  'uses'=>'Recharge@getRechargeAgreementByCompanyId']);
        $api->post('/deposit/recharge/multiple',   ['name' => '設置充值送積分','middleware'=>'activated',    'as' => 'deposit.recharge.multiple.set',  'uses'=>'Recharge@setRechargeMultiple']);
        $api->get('/deposit/recharge/multiple',    ['name' => '獲取充值送積分信息','middleware'=>'activated',    'as' => 'deposit.recharge.multiple.get',  'uses'=>'Recharge@getRechargeMultipleByCompanyId']);
        $api->get('/deposit/trades',                ['name' => '獲取儲值交易記錄', 'middleware' => ['activated', 'datapass'], 'as' => 'deposit.trades',                  'uses'=>'Recharge@getDepositTradeList']);
        $api->get('/deposit/count/index',           ['name' => '獲取儲值統計頁數據',  'middleware' => 'activated', 'as' => 'deposit.count.index',             'uses'=>'Recharge@getDepositCountIndex']);
    });
});

