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

$api->version('v1', function ($api) {
    $api->post('/ecpay/notify/delivery', ['name'=>'綠界物流回調','as' => 'ecpay.delivery.notify', 'uses'=>'EcPayBundle\Http\ThirdApi\V1\Action\EcpayDeliveryNotify@handle']);
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/rights/getdata', ['name' => '獲取用戶權益', 'middleware' => 'activated', 'as' => 'order.rights.list', 'uses' => 'Rights@getRightsListData']);
        $api->get('/rights/list', ['name' => '獲取權益列表', 'middleware' => ['activated', 'datapass'], 'as' => 'order.rights.list.get', 'uses' => 'Rights@getRightsList']);
        $api->post('/rights', ['name' => '新增權益', 'middleware' => 'activated', 'as' => 'order.rights.add', 'uses' => 'Rights@createRights']);
        $api->put('/transfer/rights', ['name' => '轉贈會員權益', 'middleware' => 'activated', 'as' => 'order.rights.transfer', 'uses' => 'Rights@transferRights']);
        $api->get('/transfer/rights/list', ['name' => '轉贈會員權益列表', 'middleware' => ['activated', 'datapass'], 'as' => 'order.rights.transfer.list', 'uses' => 'Rights@transferRightsList']);

        //獲取權益核銷記錄
        $api->get('/rights/log', ['name' => '獲取權益核銷列表', 'middleware' => ['activated', 'datapass'], 'as' => 'rights.log.list', 'uses' => 'RightsLogs@getLogsList']);
        //權益延期
        $api->get('/rights/info', ['name' => '獲取權益核銷詳情', 'middleware' => 'activated', 'as' => 'rights.info', 'uses' => 'Rights@getRightsInfo']);
        $api->post('/rights/delay', ['name' => '權益延期', 'middleware' => 'activated', 'as' => 'rights.delay', 'uses' => 'Rights@delayRights']);

        // 訂單物流日誌
        $api->get('/delivery/process/list', ['name' => '訂單物流日誌', 'middleware' => 'activated', 'as' => 'delivery.process.list', 'uses' => 'Delivery@processLogList']);
    });
    // 運費模板相關接口
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/shipping/templates/list', ['name' => '獲取運費模板列表', 'middleware' => 'activated', 'as' => 'shipping.templates.list', 'uses' => 'ShippingTemplate@getShippingTemplatesList']);
        $api->get('/shipping/templates/info/{id}', ['name' => '獲取運費模板詳情', 'middleware' => 'activated', 'as' => 'shipping.templates.info', 'uses' => 'ShippingTemplate@getShippingTemplatesInfo']);
        $api->post('/shipping/templates/create', ['name' => '添加運費模板', 'middleware' => 'activated', 'as' => 'shipping.templates.create', 'uses' => 'ShippingTemplate@createShippingTemplates']);
        $api->put('/shipping/templates/update/{id}', ['name' => '更新運費模板', 'middleware' => 'activated', 'as' => 'shipping.templates.update', 'uses' => 'ShippingTemplate@updateShippingTemplates']);
        $api->delete('/shipping/templates/delete/{id}', ['name' => '刪除運費模板', 'middleware' => 'activated', 'as' => 'shipping.templates.delete', 'uses' => 'ShippingTemplate@deleteShippingTemplates']);


        $api->get('/trade/logistics/list', ['name' => '獲取可用物流列表', 'middleware' => 'activated', 'as' => 'trade.logistics.list', 'uses' => 'CompanyRelLogistics@getLogisticsList']);
        $api->get('/company/logistics/list', ['name' => '獲取公司啟用物流列表', 'middleware' => 'activated', 'as' => 'company.logistics.list', 'uses' => 'CompanyRelLogistics@getCompanyLogisticsList']);
        $api->post('/company/logistics/create', ['name' => '創建公司啟用物流', 'middleware' => 'activated', 'as' => 'company.logistics.create', 'uses' => 'CompanyRelLogistics@createCompanyLogistics']);
        $api->delete('/company/logistics/{id}', ['name' => '刪除公司關閉物流', 'middleware' => 'activated', 'as' => 'company.logistics.delete', 'uses' => 'CompanyRelLogistics@deleteCompanyLogistics']);
        $api->get('/company/logistics/qinglongcode', ['name' => '設置公司青龍物流編碼', 'middleware' => 'activated', 'as' => 'company.logistics.qinglongcode.info', 'uses' => 'CompanyRelLogistics@getQinglongcode']);
        $api->post('/company/logistics/qinglongcode', ['name' => '設置公司青龍物流編碼', 'middleware' => 'activated', 'as' => 'company.logistics.qinglongcode', 'uses' => 'CompanyRelLogistics@setQinglongcode']);
    });

    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog', 'activated'], 'providers' => 'jwt'], function ($api) {
        $api->post('/company/dada/create', ['name' => '創建公司啟用達達同城配', 'as' => 'company.dada.create', 'uses' => 'CompanyRelDada@createCompanyRelDada']);
        $api->get('/company/dada/info', ['name' => '獲取公司達達同城配信息', 'as' => 'company.dada.info', 'uses' => 'CompanyRelDada@getCompanyRelDadaInfo']);
        $api->get('/company/delivery', ['name' => '獲取商戶同城配商家自配信息', 'as' => 'company.delivery.info', 'uses' => 'CompanyRelDeliveryController@getInfo']);
        $api->post('/company/delivery', ['name' => '更新商戶同城配商家自配信息', 'as' => 'company.delivery.save', 'uses' => 'CompanyRelDeliveryController@save']);
    });

    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/order/message/new', ['name' => '店務端未讀消息', 'middleware' => 'activated', 'as' => 'order.message.new', 'uses' => 'OrderMessage@getNewInfo']);
        $api->get('/order/message/list', ['name' => '店務端消息列表', 'middleware' => 'activated', 'as' => 'order.message.list', 'uses' => 'OrderMessage@getList']);
        $api->post('/order/message/update', ['name' => '店務端更新消息', 'middleware' => 'activated', 'as' => 'order.message.update', 'uses' => 'OrderMessage@updateMsg']);
    });

    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //結算設置
        $api->get('/statement/period/default/setting', ['name' => '獲取默認結算周期配置', 'middleware' => 'activated', 'as' => 'statement.period.default.setting.get', 'uses' => 'StatementPeriodSetting@getDefaultSetting']);
        $api->get('/statement/period/distributor/setting', ['name' => '獲取店鋪結算周期配置', 'middleware' => 'activated', 'as' => 'statement.period.distributor.setting.get', 'uses' => 'StatementPeriodSetting@getDistributorSetting']);
        $api->post('/statement/period/setting', ['name' => '保存結算周期設置', 'middleware' => 'activated', 'as' => 'statement.period.setting.set', 'uses' => 'StatementPeriodSetting@saveSetting']);
        //結算單
        $api->get('/statement/summarized', ['name' => '獲取結算匯總數據', 'middleware' => 'activated', 'as' => 'statement.summarized.get', 'uses' => 'Statements@getSummarized']);
        $api->post('/statement/summarized/export', ['name' => '導出結算匯總數據', 'middleware' => 'activated', 'as' => 'statement.summarized.export', 'uses' => 'Statements@exportSummarized']);
        $api->post('/statement/confirm/{statement_id}', ['name' => '確認結算', 'middleware' => 'activated', 'as' => 'statement.confirm.post', 'uses' => 'Statements@comfirmStatement']);
        $api->get('/statement/detail/{statement_id}', ['name' => '獲取結算明細數據', 'middleware' => 'activated', 'as' => 'statement.detail.get', 'uses' => 'Statements@getDetail']);
        $api->post('/statement/detail/export', ['name' => '導出結算明細數據', 'middleware' => 'activated', 'as' => 'statement.detail.export', 'uses' => 'Statements@exportDetail']);
    });

    // 移動收銀相關
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated'], 'providers' => 'jwt'], function($api) {
        $api->post('/checkout', ['name' => '購物車結算列表', 'as' => 'order.checkout', 'uses' => 'NormalOrder@checkout']);
        $api->post('/order/create', ['name' => '代客下單', 'as' => 'order.create', 'uses' => 'NormalOrder@createUserOrder']);
        $api->post('/order/payment', ['name' => '下單支付', 'as' => 'order.payment', 'uses' => 'NormalOrder@payment']);
        $api->get('/order/payment/query', ['name' => '支付結果查詢', 'as' => 'order.payment.query', 'uses' => 'NormalOrder@queryPayment']);
    });
});
