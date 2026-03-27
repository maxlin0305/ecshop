<?php
$api->version('v1', function($api) {
    $api->group(['namespace' => 'HfPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/hfpay/ledgerconfig/index', ['name' => '匯付獲取分賬配置', 'middleware'=>'activated', 'as' => 'hfpay.ledgerconfig.index', 'uses' => 'HfpayLedgerConfig@index']);
        $api->post('/hfpay/ledgerconfig/save', ['name' => '保存分賬配置', 'middleware'=>'activated','as' => 'hfpay.ledgerconfig.save', 'uses' => 'HfpayLedgerConfig@save']);
        $api->get('/hfpay/enterapply/apply', ['name' => '獲取入駐信息', 'middleware'=>'activated', 'as' => 'hfpay.enterapply.apply', 'uses' => 'HfpayEnterapply@apply']);
        $api->post('/hfpay/enterapply/save', ['name' => '保存入駐信息', 'middleware'=>'activated','as' => 'hfpay.enterapply.save', 'uses' => 'HfpayEnterapply@saveEnterapply']);
        $api->get('/hfpay/enterapply/getList', ['name' => '獲取店鋪入駐列表信息', 'middleware'=>'activated', 'as' => 'hfpay.enterapply.getlist', 'uses' => 'HfpayEnterapply@getApplyList']);
        $api->post('/hfpay/enterapply/hfkaihu', ['name' => '企業個體戶開戶', 'middleware'=>'activated','as' => 'hfpay.enterapply.hfkaihu', 'uses' => 'HfpayEnterapply@hfKaiHu']);
        $api->post('/hfpay/enterapply/hffile', ['name' => '匯付文件上傳', 'middleware'=>'activated','as' => 'hfpay.enterapply.hffile', 'uses' => 'HfpayEnterapply@hfFile']);
        $api->post('/hfpay/enterapply/opensplit', ['name' => '店鋪分賬開關', 'middleware'=>'activated','as' => 'hfpay.enterapply.opensplit', 'uses' => 'HfpayEnterapply@openSplit']);
        $api->get('/hfpay/getwithdrawset', ['name' => '匯付獲取提現設置', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.get', 'uses' => 'HfpayWithdrawSet@index']);
        $api->post('/hfpay/savewithdrawset', ['name' => '匯付提現設置', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.save', 'uses' => 'HfpayWithdrawSet@save']);
        $api->get('/hfpay/statistics/distributor', ['name' => '店鋪分賬交易統計', 'middleware'=>'activated','as' => 'hfpay.statistics.distributor', 'uses' => 'HfpayStatistics@distributor']);
        $api->get('/hfpay/statistics/company', ['name' => '平臺分賬交易統計', 'middleware'=>'activated','as' => 'hfpay.statistics.company', 'uses' => 'HfpayStatistics@company']);
        $api->get('/hfpay/statistics/exportData', ['name' => '導出報表', 'middleware'=>'activated','as' => 'hfpay.statistics.exportData', 'uses' => 'HfpayStatistics@exportData']);

        $api->get('/hfpay/statistics/orderList', ['name' => '分賬統計列表', 'middleware'=>'activated','as' => 'hfpay.statistics.orderList', 'uses' => 'HfpayStatistics@orderList']);
        $api->get('/hfpay/statistics/orderDetail/{orderId}', ['name' => '分賬統計詳情', 'middleware'=>'activated','as' => 'hfpay.statistics.orderDetail', 'uses' => 'HfpayStatistics@orderDetail']);
        $api->get('/hfpay/statistics/orderExportData', ['name' => '分賬統計導出報表', 'middleware'=>'activated','as' => 'hfpay.statistics.orderExportData', 'uses' => 'HfpayStatistics@orderExportData']);

        $api->get('/hfpay/withdraw/getList', ['name' => '匯付提現記錄', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.getList', 'uses' => 'HfpayCashRecord@getList']);
        $api->post('/hfpay/withdraw', ['name' => '匯付提現', 'middleware'=>'activated', 'as' => 'hfpay.withdraw', 'uses' => 'HfpayCashRecord@withdraw']);
        $api->get('/hfpay/withdraw/exportData', ['name' => '匯付提現記錄導出', 'middleware'=>'activated', 'as' => 'hfpay.withdraw.exportData', 'uses' => 'HfpayCashRecord@exportData']);
    });
});
