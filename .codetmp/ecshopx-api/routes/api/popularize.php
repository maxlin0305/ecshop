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
    $api->group(['namespace' => 'PopularizeBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/popularize/config', ['name'=>'獲取分銷配置信息','as' => 'popularize.config.get', 'uses' => 'SettingController@getConfig']);
        $api->get('/popularize/getMerchantConfig', ['name'=>'獲取分銷配置信息','as' => 'popularize.config.get', 'uses' => 'SettingController@getMerchantConfig']);
        $api->post('/popularize/config', ['name'=>'設置分銷配置信息','as' => 'popularize.config.set', 'uses' => 'SettingController@setConfig']);
        $api->post('/popularize/setMerchantConfig', ['name'=>'設置分銷配置信息','as' => 'popularize.config.get', 'uses' => 'SettingController@setMerchantConfig']);
        $api->get('/popularize/promoter/config', ['name'=>'獲取推廣員等級','as' => 'popularize.promoter.config.get', 'uses' => 'SettingController@getPromoterGradeConfig']);
        $api->post('/popularize/promoter/config', ['name'=>'設置推廣員等級','as' => 'popularize.promoter.config.set', 'uses' => 'SettingController@setPromoterGradeConfig']);

        $api->post('/popularize/promoter/add', ['name'=>'指定會員成為頂級推廣員','as' => 'popularize.promoter.add', 'uses' => 'PromoterController@addPromoter']);
        $api->get('/popularize/promoter/children', ['name'=>'獲取推廣員直屬下級列表',  'middleware' => ['datapass'], 'as' => 'popularize.promoter.children.list', 'uses' => 'PromoterController@getPromoterchildrenList']);
        $api->get('/popularize/promoter/list', ['name'=>'獲取推廣員列表', 'middleware' => ['datapass'], 'as' => 'popularize.promoter.list.get', 'uses' => 'PromoterController@getPromoterList']);
        $api->get('/popularize/promoter/export', ['name'=>'導出推廣員業績', 'middleware' => ['datapass'], 'as' => 'popularize.promoter.export', 'uses' => 'PromoterController@exportPromoterList']);
        $api->put('/popularize/promoter/grade', ['name'=>'推廣員等級調整','as' => 'popularize.promoter.grade.put', 'uses' => 'PromoterController@updatePromoterGrade']);
        $api->put('/popularize/promoter/disabled', ['name'=>'禁用/激活推廣員','as' => 'popularize.promoter.disabled', 'uses' => 'PromoterController@updatePromoterDisabled']);
        $api->put('/popularize/promoter/remove', ['name'=>'調整推廣員上下級關系','as' => 'popularize.promoter.remove', 'uses' => 'PromoterController@updatePromoterRemove']);
        $api->put('/popularize/promoter/shop', ['name'=>'對推廣員的店鋪狀態進行更新','as' => 'popularize.promoter.shop.update', 'uses' => 'PromoterController@updatePromoterShop']);

        $api->put('/popularize/cash_withdrawals/{cash_withdrawal_id}', ['name'=>'處理推廣員傭金提現申請','as' => 'popularize.cash_withdrawals.process', 'uses' => 'BrokerageController@processCashWithdrawal']);
        $api->get('/popularize/cashWithdrawals', ['name'=>'獲取傭金提現列表', 'middleware' => ['datapass'], 'as' => 'popularize.cash_withdrawals.list.get', 'uses' => 'BrokerageController@getCashWithdrawalList']);
        $api->get('/popularize/cashWithdrawal/payinfo/{cash_withdrawal_id}', ['name'=>'獲取傭金提現支付信息','as' => 'popularize.cash_withdrawals.pay.list.get', 'uses' => 'BrokerageController@getMerchantTradeList']);

        $api->get('/popularize/brokerage/count', ['name'=>'獲取傭金統計','as' => 'popularize.brokerage.count', 'uses' => 'BrokerageController@brokerageCount']);
        $api->get('/popularize/brokerage/logs', ['name'=>'獲取傭金記錄','as' => 'popularize.brokerage.logs', 'uses' => 'BrokerageController@getBrokerageList']);

        $api->get('/popularize/taskBrokerage/logs', ['name'=>'獲取任務傭金記錄', 'middleware' => ['datapass'], 'as' => 'popularize.task.brokerage.logs', 'uses' => 'BrokerageController@getTaskBrokerageList']);
        $api->get('/popularize/taskBrokerage/count', ['name'=>'獲取任務傭金統計', 'middleware' => ['datapass'], 'as' => 'popularize.task.brokerage.count', 'uses' => 'BrokerageController@getTaskBrokerageCountList']);
        $api->get('/popularize/export/taskBrokerage/count', ['name'=>'獲取任務傭金統計', 'middleware' => ['datapass'], 'as' => 'popularize.task.brokerage.count.export', 'uses' => 'BrokerageController@exportTaskBrokerageCount']);


        $api->get('/popularize/aaaa',
            ['name' => '獲取分銷配置信息', 'as' => 'popularize.config.get', 'uses' => 'SettingController@aaaa'
            ]);
        $api->get('/popularize/bbb',
            ['name' => '獲取分銷配置信息', 'as' => 'popularize.config.get', 'uses' => 'SettingController@bbb'
            ]);
        $api->get('/popularize/ccc',
            ['name' => '獲取分銷配置信息', 'as' => 'popularize.config.get', 'uses' => 'SettingController@ccc'
            ]);
    });
});

