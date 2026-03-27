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

/* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ taro小程序、h5、app端、pc端 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
$api->version('v1', function ($api) {
    // 订单权益信息
    $api->group(['prefix' => 'h5app', 'namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => ['dingoguard:h5app', 'api.auth'], 'providers' => 'jwt'], function ($api) {
        $api->post('/wxapp/order/jspayconfig', ['as' => 'front.wxapp.jspayconfig.get', 'uses' => 'WxappOrder@getJsPayConfig']);
        // 创建订单-已支持h5
        $api->post('/wxapp/order', ['name'=>'创建订单并发起支付', 'as' => 'front.wxapp.order.create', 'uses' => 'WxappOrder@createOrder']);
        $api->get('/wxapp/epidemic/info', ['name'=>'疫情登记信息', 'as' => 'front.wxapp.epidemic.info', 'uses' => 'WxappOrder@epidemicRegisterInfo']);
        $api->get('/wxapp/epidemic/mixed/cat', ['name'=>'疫情登记所需信息', 'as' => 'front.wxapp.epidemic.mixed', 'uses' => 'WxappOrder@epidemicRegisterMixedCats']);
        $api->post('/wxapp/epidemic/info/del/{id}', ['name'=>'删除疫情登记信息', 'as' => 'front.wxapp.epidemic.del', 'uses' => 'WxappOrder@delEpidemicRegister']);
        // $api->post('/wxapp/order_new', ['as' => 'front.wxapp.order.new.create', 'uses' => 'WxappOrder@createNewOrder']);
        // 获取订单优惠以及运费信息-已支持h5
        // $api->post('/wxapp/getFreightFee', ['as' => 'front.wxapp.order.get', 'uses' => 'WxappOrder@getOrderFreightFeeInfo']);
        // 获取订单详情-已支持h5
        $api->get('/wxapp/order/{order_id}', ['name'=>'订单详情', 'middleware' => 'datapass', 'as' => 'front.wxapp.order.info', 'uses' => 'WxappOrder@getOrderDetail']);
        $api->get('/wxapp/order_new/{order_id}', ['name'=>'订单详情', 'as' => 'front.wxapp.order.newinfo', 'uses' => 'WxappOrder@getOrderDetailNew']);
        // 获取用户订单列表-已支持h5
        $api->get('/wxapp/orders', ['name'=>'获取用户订单列表', 'as' => 'front.wxapp.order.list', 'uses' => 'WxappOrder@getOrderList']);
        // 获取用户拼团订单列表-已支持h5
        $api->get('/wxapp/groupOrders', ['name'=>'拼团订单列表', 'as' => 'front.wxapp.grouporder.list', 'uses' => 'WxappOrder@getGroupOrderList']);
        // 统计订单数量和权益核销数量-已支持h5
        $api->get('/wxapp/orders/count', ['name' => '统计订单数量和权益核销数量', 'as' => 'front.wxapp.orders.count', 'uses' => 'WxappOrder@countOrderAndRightsLog']);
        // 统计订单数量-已支持h5
        $api->get('/wxapp/orderscount', ['as' => 'front.wxapp.orderscount', 'uses' => 'WxappOrder@countOrders']);
        // 物流跟踪信息
        $api->get('/wxapp/trackerpull', ['name' => '物流跟踪信息', 'as' => 'front.wxapp.orders.tracker', 'uses' => 'WxappOrder@trackerpull']);
        // 获取权益列表-已支持h5
        $api->get('/wxapp/rights', ['name' => '获取权益列表','as' => 'front.wxapp.rights.list', 'uses' => 'Rights@getRightsList']);
        // 获取权益核销记录列表-已支持h5
        $api->get('/wxapp/rightsLogs', ['name' => '获取权益核销记录列表', 'as' => 'front.wxapp.rightslogs.list', 'uses' => 'Rights@getRightsLogList']);
        // 获取权益详情-已支持h5
        $api->get('/wxapp/rights/{rights_id}', ['name' => '获取权益详情', 'as' => 'front.wxapp.rights.info', 'uses' => 'Rights@getRightsDetail']);
        // 获取权益核销码-已支持h5
        $api->get('/wxapp/rightscode/{rights_id}', ['name' => '获取权益核销码', 'as' => 'front.wxapp.rights.code', 'uses' => 'Rights@getRightsCode']);
        // 获取自提码-已支持h5
        $api->get('/wxapp/ziticode', ['name' => '获取自提码', 'as' => 'front.wxapp.ziti.code', 'uses' => 'WxappOrder@getZitiQRCode']);
        // 订单取消-已支持h5
        $api->post('/wxapp/order/cancel', ['name'=>'订单取消', 'as' => 'front.wxapp.order.get', 'uses' => 'WxappOrder@cancelOrder']);
        // 确认发货-已支持h5
        $api->post('/wxapp/order/confirmReceipt', ['name'=>'确认发货', 'as' => 'front.wxapp.order.confirm', 'uses' => 'WxappOrder@confirmReceipt']);
        // 购物车增加-已支持h5
        $api->post('/wxapp/cart', ['name'=>'购物车增加', 'as' => 'front.wxapp.cart', 'uses' => 'CartController@addCart']);
        // 购物车列表-已支持h5
        $api->get('/wxapp/cart', ['as' => 'front.wxapp.cart.list', 'uses' => 'CartController@getCartList']);
        // 购物车删除-已支持h5
        $api->delete('/wxapp/cartdel', ['as' => 'front.wxapp.cart.delete', 'uses' => 'CartController@deleteCartData']);
        //修改购物车选中状态-已支持h5
        $api->put('/wxapp/cartupdate/checkstatus', ['as' => 'front.wxapp.cart.checkstatu.update', 'uses' => 'CartController@updateCartCheckStatus']);
        //修改购物车批量商品数量-已支持h5
        $api->put('/wxapp/cartupdate/batchnum', ['as' => 'front.wxapp.cart.batchnum.update', 'uses' => 'CartController@batchUpdateCartNum']);
        //修改购物车商品数量(单一)-已支持h5
        $api->put('/wxapp/cartupdate/num', ['as' => 'front.wxapp.cart.num.update', 'uses' => 'CartController@updateCartNum']);
        //修改购物车商品促销活动
        $api->put('/wxapp/cartupdate/promotion',  ['as' => 'front.wxapp.cart.num.update',  'uses'=>'CartController@updateCartItemPromotion']);
        $api->get('/wxapp/cartcount',  ['as' => 'front.wxapp.cart.count',  'uses'=>'CartController@getCartItemCount']);
        //订单表票
        $api->get('/wxapp/orders/invoice',  ['as' => 'front.wxapp.order.invoice',  'uses'=>'OrderInvoice@getInvoiceList']);
        $api->get('/wxapp/pickupcode/{order_id}', ['name'=>'获取自提订单提货码', 'as' => 'front.wxapp.order.pickupcode.get', 'uses'=>'WxappOrder@getOrderPickupCode']);
        //选择加价购产品
        $api->post('/wxapp/cart/check/plusitem', ['as' => 'front.wxapp.cart.plusitem', 'uses' => 'CartController@checkPlusItem']);
        //发货单列表
        $api->get('/wxapp/delivery/lists', ['name'=>'发货单列表', 'as' => 'front.wxapp.delivery.lists', 'uses' => 'Delivery@lists']);
        //发货单物流详情
        $api->get('/wxapp/delivery/trackerpull', ['name'=>'发货单物流详情', 'as' => 'front.wxapp.delivery.trackerpull', 'uses' => 'Delivery@deliveryInfo']);
        $api->get('/wxapp/delivery/ecpay', ['name'=>'发货单物流详情', 'as' => 'front.wxapp.delivery.ecpay', 'uses' => 'Delivery@ecpayDeliveryInfo']);
        //绑定订单
        $api->POST('/wxapp/order/bind/{order_id}', ['name'=>'绑定订单', 'as' => 'front.wxapp.order.bind', 'uses' => 'WxappOrder@bindUserOrder']);
        $api->post('/wxapp/getTokenbyBindingCard', ['name'=>'获取绑卡 token', 'as' => 'front.wxapp.card.token', 'uses' => 'CardController@getToken']);
        $api->post('/wxapp/addCard', ['name'=>'获取绑卡 token', 'as' => 'front.wxapp.card.add', 'uses' => 'CardController@addCard']);
        $api->get('/wxapp/cardList', ['name'=>'获取绑卡 列表', 'as' => 'front.wxapp.card.list', 'uses' => 'CardController@cardList']);
        $api->delete('/wxapp/deleteCard', ['name'=>'绑卡删除', 'as' => 'front.wxapp.card.delete', 'uses' => 'CardController@deleteCard']);
        $api->post('/wxapp/invoice/validate',['name'=>'发票参数校验', 'as' => 'front.wxapp.invoice.validae', 'uses' => 'InvoiceController@check']);

        //延期团购订单
        $api->get('/wxapp/multi_buy/extension/{order_id}', ['name'=>'延期团购订单', 'as' => 'front.wxapp.invoice.validae', 'uses' => 'WxappOrder@extensionMultiOrderTime']);
    });
});

$api->version('v1', function ($api) {
    // 根据小程序id不需要授权请求
    $api->group(['prefix' => 'h5app','namespace' => 'OrdersBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        // 获取用户拼团订单详情-已支持h5
        $api->get('/wxapp/groupOrders/{teamId}', ['name'=>'拼团订单详情', 'as' => 'front.wxapp.grouporder.info', 'uses'=>'WxappOrder@getGroupOrderDetail']);
        $api->get('/wxapp/cart/list',  ['as' => 'front.wxapp.cart.get.list',  'uses'=>'CartController@getDistributorCartList']);
        // 获取订单优惠以及运费信息-已支持h5
        $api->post('/wxapp/getFreightFee', ['name'=>'获取订单优惠以及运费信息', 'as' => 'front.wxapp.order.get', 'uses' => 'WxappOrder@getOrderFreightFeeInfo']);
        // 创建订单-已支持h5
        $api->post('/wxapp/order_new', ['name'=>'创建订单', 'as' => 'front.wxapp.order.new.create', 'uses' => 'WxappOrder@createNewOrder']);
        // $api->post('/wxapp/order_new', ['name'=>'创建订单', 'as' => 'front.wxapp.order.new.create', 'uses' => 'WxappOrder@createNewOrder', 'middleware' => 'api.throttle']); // 限流写法
    });

    $api->group(['prefix' => 'h5app','namespace' => 'EspierBundle\Http\FrontApi\V1\Action', 'middleware' => 'frontnoauth:h5app'], function ($api) {
        $api->get('/wxapp/espier/subdistrict', ['name' => '获取街道社区列表', 'as' => 'front.wxapp.espier.subdistrict.list.get', 'uses' => 'SubdistrictController@get']);
    });

    $api->group(['prefix'=>'h5app','namespace' => 'OrdersBundle\Http\FrontApi\V1\Action'], function ($api) {

        //提供给智管家，标记信息为已读
        $api->post('/wxapp/order/update', ['name' => '更新消息状态为已读', 'as' => 'front.wxapp.pushmessage.status.update', 'uses' => 'WxappOrder@updatePushMessageStatus']);

        //获取订单信息
        $api->post('/wxapp/order/info', ['name' => '获取订单信息', 'as' => 'front.wxapp.order.info', 'uses' => 'WxappOrder@getOrderInfoById']);

    });
});
/* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ taro小程序、h5、app端、pc端 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */
