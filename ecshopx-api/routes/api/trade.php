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
    // 支付寶支付回調
    $api->post('/ecpay/notify/order_id/{order_id}/trade_id/{trade_id}', ['name'=>'綠界支付回調','as' => 'ecpay.pay.notify', 'uses'=>'EcPayBundle\Http\ThirdApi\V1\Action\EcpayNotify@handle']);
    $api->post('/ecpay/notify/return_app', ['name'=>'返回app界面','as' => 'ecpay.pay.notify.return_app', 'uses'=>'EcPayBundle\Http\ThirdApi\V1\Action\EcpayNotify@returnApp']);
    $api->post('/alipay/notify', ['name'=>'支付寶支付回調','as' => 'alipay.pay.notify', 'uses'=>'PaymentBundle\Http\Controllers\PaymentNotify@handle']);
    // 銀聯支付回調
    $api->post('/chinaums/notify', ['name'=>'銀聯支付回調','as' => 'chinaums.pay.notify', 'uses'=>'PaymentBundle\Http\Controllers\ChinaumsNotify@handle']);
    // 微信相關信息
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/trade', [ 'name'=>'獲取交易列表', 'middleware'=>['activated', 'datapass'],  'as' => 'order.trade.list',  'uses'=>'Trade@getTradelist']);

        $api->get('/orders', ['name'=>'獲取訂單列表','middleware'=>['activated', 'datapass'], 'as' => 'order.list.get', 'uses'=>'Order@getOrderList']);
        $api->get('/order/{order_id}', ['name'=>'獲取訂單詳情','middleware'=>['activated', 'datapass'], 'as' => 'order.info.get', 'uses'=>'Order@getOrderDetail']);

        $api->get('/order/process/{orderId}', ['name'=>'獲取訂單操作詳情','middleware'=>['activated', 'datapass'], 'as' => 'order.process.log.get', 'uses'=>'Order@getOrderProcessLog']);

        //發票後臺功能
        $api->get('/fapiaolist', ['name'=>'獲取發票列表','middleware'=>'activated', 'as' => 'invoice.list.get', 'uses'=>'Order@getInvoiceList']);
        $api->get('/fapiaoset', ['name'=>'發票操作','middleware'=>'activated', 'as' => 'invoice.set', 'uses'=>'Order@setInvoice']);

        // 獲取訂單的取消訂單申請信息
        $api->get('/order/{order_id}/cancelinfo', [ 'name'=>'獲取訂單取消信息','middleware'=>'activated',  'as' => 'order.cancel.info',  'uses'=>'Order@getOrderCancelInfo']);
        // 確認訂單取消審核
        $api->post('/order/{order_id}/confirmcancel', [ 'name'=>'確認訂單取消審核', 'middleware'=>'activated',  'as' => 'order.cancel.info',  'uses'=>'Order@confirmOrderCancel']);
        $api->put('/order/{order_id}/processdrug', [ 'middleware'=>'activated',  'as' => 'order.process.drug',  'uses'=>'Order@processDrugOrders']);
        //訂單配置
        $api->post('/orders/setting/set', ['name'=>'訂單配置設置','middleware'=>'activated', 'as' => 'ordersetting.set', 'uses'=>'Order@setOrderSetting']);
        $api->get('/orders/setting/get', ['name'=>'獲取訂單配置','middleware'=>'activated', 'as' => 'orders.setting.get', 'uses'=>'Order@getOrderSetting']);
        //後端取消訂單
        $api->post('/order/{order_id}/cancel', ['name' => '取消訂單', 'middleware' => 'activated', 'as' => 'order.cancel', 'uses' => 'Order@cancelOrder']);

        // 發貨
        $api->post('/delivery', ['name'=>'訂單發貨','middleware'=>'activated', 'as' => 'order.delivery', 'uses'=>'Order@delivery']);
        $api->put('/delivery/{orders_delivery_id}', ['name'=>'訂單發貨信息修改','middleware'=>'activated', 'as' => 'order.delivery.update', 'uses'=>'Order@updateDelivery']);
        $api->put('/old_delivery/{orderId}', ['name'=>'訂單發貨信息修改（舊)','middleware'=>'activated', 'as' => 'order.delivery.update', 'uses'=>'Order@updateDeliveryOld']);
        $api->put('/remarks/{orderId}', ['name'=>'訂單備註信息修改','middleware'=>'activated', 'as' => 'order.remarks.update', 'uses'=>'Order@updateRemarks']);
        $api->get('/delivery/details', ['name'=>'物流詳情','middleware'=>'activated', 'as' => 'order.delivery.details', 'uses'=>'Order@trackerpull']);
        $api->get('/delivery/lists', ['name'=>'發貨單列表','middleware'=>'activated', 'as' => 'order.delivery.lists', 'uses'=>'Delivery@lists']);

        // 訂單確認送達
        $api->post('/confirmReceipt', ['name'=>'訂單確認送達','middleware'=>'activated', 'as' => 'order.confirmReceipt', 'uses'=>'Order@confirmReceipt']);

        $api->get('/orders/exportdata', ['name'=>'導出訂單列表','middleware'=>['activated', 'datapass'], 'as' => 'order.list.export', 'uses'=>'ExportData@exportOrderData']);
        $api->get('/invoice/exportdata', ['name'=>'導出發票列表','middleware'=>'activated', 'as' => 'invoice.list.export', 'uses'=>'ExportData@exportInvoiceData']);
        $api->get('/rights/exportdata', ['name'=>'導出權益列表','middleware'=>['activated', 'datapass'], 'as' => 'rights.list.export', 'uses'=>'ExportData@exportRightData']);
        $api->get('/orders/exportnormaldata', ['name'=>'導出實體訂單列表','middleware'=>'activated', 'as' => 'normal.list.export', 'uses'=>'ExportData@exportOrderNormalData']);
        $api->get('/trades/exportdata', ['name'=>'導出交易單列表','middleware'=>['activated','datapass'], 'as' => 'trades.list.export', 'uses'=>'ExportData@exportTradeData']);
        $api->get('/rights/logExport',  ['name'=>'導出權益核銷列表','middleware'=>['activated','datapass'], 'as' => 'rights.log.list.export', 'uses'=>'ExportData@exportRightConsumeData']);
        //快遞配置
        $api->post('/trade/kuaidi/setting', [ 'name'=>'快遞配置信息保存','middleware'=>'activated', 'as' => 'trade.kuaidi.setting.set', 'uses'=>'Kuaidi@setKuaidiSetting']);
        $api->get('/trade/kuaidi/setting', [ 'name'=>'獲取快遞配置信息','middleware'=>'activated', 'as' => 'trade.kuaidi.setting.get', 'uses'=>'Kuaidi@getKuaidiSetting']);

        //順豐物流BSP
        $api->post('/trade/sfbsp/setting', [ 'name'=>'順豐物流跟蹤設置保存','middleware'=>'activated', 'as' => 'trade.sfbsp.setting.set', 'uses'=>'Sfbsp@setSfbspSetting']);
        $api->get('/trade/sfbsp/setting', [ 'name'=>'獲取順豐物流跟蹤設置','middleware'=>'activated', 'as' => 'trade.sfbsp.setting.get', 'uses'=>'Sfbsp@getSfbspSetting']);

        //退款失敗日誌
        $api->get('/trade/refunderrorlogs/list', [ 'name'=>'獲取退款錯誤列表','middleware'=>'activated', 'as' => 'trade.refunderrorlogs.list', 'uses'=>'RefundErrorLogs@getList']);
        $api->put('/trade/refunderrorlogs/resubmit/{id}', [ 'name'=>'重新提交退款','middleware'=>'activated', 'as' => 'trade.refunderrorlogs.resubmit', 'uses'=>'RefundErrorLogs@resubmitRefund']);

        //評價
        $api->get('/trade/rate', ['name'=>'獲取評價列表','middleware'=>'activated', 'as' => 'traderate.list.get', 'uses'=>'TradeRate@getTradeRateList']);
        $api->put('/trade/rate', ['name'=>'回復評價','middleware'=>'activated', 'as' => 'traderate.reply.put', 'uses'=>'TradeRate@replyTradeRate']);
        $api->get('/trade/{rate_id}/rate', ['name'=>'獲取評價詳情', 'middleware'=>'activated',  'as' => 'traderate.details.get',  'uses'=>'TradeRate@getTradeRateInfo']);
        $api->delete('/trade/rate/{rate_id}', ['name'=>'刪除評價', 'middleware'=>'activated',  'as' => 'traderate.rate.delete',  'uses'=>'TradeRate@tradeRateDelete']);

        $api->post('/invoice/number', ['name'=>'設置訂單發票號', 'middleware'=>'activated',  'as' => 'order.invoicenumber.set',  'uses'=>'Order@updateInvoiceNumber']);
        $api->post('/invoice/invoiced', ['name'=>'設置訂單開票狀態', 'middleware'=>'activated',  'as' => 'order.invoiced.invoiced',  'uses'=>'Order@setInvoiced']);

        $api->get('/financial/salesreport',  ['name'=>'導出財務銷售報表','middleware'=>'activated', 'as' => 'financial.salesreport.export', 'uses'=>'ExportData@exportSalesreportData']);

        $api->get('/writeoff/{order_id}', ['name'=>'獲取自提訂單核銷信息','middleware'=>'activated', 'as' => 'order.writeoff.info.get', 'uses'=>'Order@getOrderWriteoffInfo']);
        $api->post('/writeoff/{order_id}', ['name'=>'自提訂單核銷','middleware'=>'activated', 'as' => 'order.writeoff.set', 'uses'=>'Order@orderWriteoff']);

        $api->post('/qr_writeoff', ['name'=>'自提訂單掃碼核銷','middleware'=>'activated', 'as' => 'order.writeoff.qr.set', 'uses'=>'Order@orderWriteoffQR']);

        //包裝配置
        $api->post('/trade/setting', [ 'name'=>'交易配置信息保存','middleware'=>'activated', 'as' => 'trade.setting.set', 'uses'=>'TradeSetting@setSetting']);
        $api->get('/trade/setting', [ 'name'=>'獲取交易配置信息','middleware'=>'activated', 'as' => 'trade.setting.get', 'uses'=>'TradeSetting@getSetting']);

        //取消訂單配置
        $api->post('/trade/cancel/setting', [ 'name'=>'取消訂單配置信息保存','middleware'=>'activated', 'as' => 'trade.cancel.setting.set', 'uses'=>'TradeSetting@setCancelSetting']);
        $api->get('/trade/cancel/setting', [ 'name'=>'獲取取消訂單配置信息','middleware'=>'activated', 'as' => 'trade.cancel.setting.get', 'uses'=>'TradeSetting@getCancelSetting']);

        // 達達同城配，商家接單
        $api->post('/businessreceipt/{orderId}', ['name'=>'達達同城配商家接單','middleware'=>'activated', 'as' => 'order.businessreceipt', 'uses'=>'Order@businessReceipt']);
        // 達達同城配，商家確認退回
        $api->post('/confirm/goods/{orderId}', ['name'=>'達達同城配商家確認退回','middleware'=>'activated', 'as' => 'order.confirm.goods', 'uses'=>'Order@confirmGoods']);

        // 訂單改價
        $api->post('/order/markdown', ['name'=>'訂單改價', 'middleware'=>['activated', 'datapass'], 'as' => 'order.markdown', 'uses'=>'Order@markDown']);
        $api->post('/order/markdown/confirm', ['name'=>'訂單改價確認', 'middleware'=>['activated', 'datapass'], 'as' => 'order.markdown.confirm', 'uses'=>'Order@confirmMarkDown']);

        // 團購訂單延期
        $api->put('/order/multi_buy/extension/{order_id}', [ 'middleware'=>'activated',  'as' => 'order.multi.extension',  'uses'=>'Order@extensionMultiOrderTime']);
        // 團購訂單核銷
        $api->put('/order/multi_buy/verify/{order_id}', [ 'middleware'=>'activated',  'as' => 'order.multi.check',  'uses'=>'Order@verifyMultiOrder']);

    });

    // 訂單支狀態相關信息
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/order/payorderinfo/{trade_id}', ['name'=>'獲取支付訂單狀態信息','middleware'=>'activated', 'as' => 'order.info.get', 'uses'=>'OrderStateInfo@getPayOrderInfo']);
        $api->get('/order/refundorderinfo/{refund_bn}', ['name'=>'獲取退款訂單狀態信息','middleware'=>'activated', 'as' => 'order.info.get', 'uses'=>'OrderStateInfo@getRefundOrderInfo']);
    });

    // 支付方式相關
    $api->group(['namespace' => 'PaymentBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/trade/payment/setting', [ 'name'=>'支付配置信息保存','middleware'=>'activated', 'as' => 'trade.payment.setting.set', 'uses'=>'Payment@setPaymentSetting']);
        $api->get('/trade/payment/setting', [ 'name'=>'獲取支付配置信息','middleware'=>'activated', 'as' => 'trade.payment.setting.get', 'uses'=>'Payment@getPaymentSetting']);
        $api->get('/trade/payment/list', [ 'name'=>'獲取支付配置信息列表','middleware'=>'activated', 'as' => 'trade.payment.setting.list', 'uses'=>'Payment@getPaymentSettingList']);
        $api->get('/trade/payment/hfpayversionstatus', [ 'name'=>'獲取匯付天下版本狀態','middleware'=>'activated', 'as' => 'trade.payment.hfpay.status', 'uses'=>'Payment@getHfpayVersionStatus']);
    });

    // 達達財務
    $api->group(['namespace' => 'OrdersBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/dada/finance/info', ['name'=>'獲取賬戶余額','middleware'=>'activated', 'as' => 'dada.finance.info', 'uses'=>'DadaFinance@queryBalance']);
        $api->post('/dada/finance/create', ['name'=>'獲取充值鏈接','middleware'=>'activated', 'as' => 'dada.finance.create', 'uses'=>'DadaFinance@recharge']);
    });


});
