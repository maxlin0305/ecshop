<?php

$api->version('v1', function($api) {

    $api->group(['prefix' => 'community', 'namespace' => 'CommunityBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {

        //社區團購團長申請字段
        $api->get('/chief/apply_fields', ['name' => '配置字段列表', 'as' => 'community.chief.apply_field.list', 'uses' => 'CommunityChiefApplyFields@list']);
        $api->post('/chief/apply_field', ['name' => '創建字段', 'as' => 'community.chief.apply_field.create', 'uses' => 'CommunityChiefApplyFields@create']);
        $api->post('chief/apply_field/switch/{id}', ['name' => '配置字段開關', 'as' => 'community.chief.apply_field.switch', 'uses' => 'CommunityChiefApplyFields@switch']);
        $api->post('/chief/apply_field/{id}', ['name' => '修改字段', 'as' => 'community.chief.apply_field.update', 'uses' => 'CommunityChiefApplyFields@update']);
        $api->delete('/chief/apply_field/{id}', ['name' => '刪除字段', 'as' => 'community.chief.apply_field.delete', 'uses' => 'CommunityChiefApplyFields@delete']);
        //團長申請
        $api->get('/chief/apply/wxaCode', ['name' => '團長申請頁小程序碼', 'as' => 'community.chief.apply.wxaCode', 'uses' => 'CommunityChief@getWxaCode']);
        $api->get('/chief/apply/list', ['name' => '團長申請列表', 'as' => 'community.chief.apply.list', 'uses' => 'CommunityChief@getApplyList']);
        $api->get('/chief/apply/info/{apply_id}', ['name' => '團長申請信息', 'as' => 'community.chief.apply.info', 'uses' => 'CommunityChief@getApplyInfo']);
        $api->post('/chief/approve/{apply_id}', ['name' => '團長申請審批', 'as' => 'community.chief.approve', 'uses' => 'CommunityChief@approve']);

        $api->post('/chief/setMemberCommunity', ['name' => '設置團長', 'as' => 'community.setting.chief', 'uses' => 'CommunityChief@setMemberCommunity']);

        //團長業績&提現
        $api->get('/rebate/count', ['name' => '團長業績列表', 'as' => 'community.chief.rebate.count', 'uses' => 'CommunityChiefCashWithdrawal@getChiefRebateCount']);
        $api->get('/cash_withdrawal', ['name' => '團長傭金提現列表', 'as' => 'community.chief.cash_withdrawal.list', 'uses' => 'CommunityChiefCashWithdrawal@getCashWithdrawalList']);
        $api->post('/cash_withdrawal/{cash_withdrawal_id}', ['name' => '處理團長傭金提現申請', 'as' => 'community.chief.cash_withdrawal.process', 'uses' => 'CommunityChiefCashWithdrawal@processCashWithdrawal']);
        $api->get('/cash_withdrawal/payinfo/{cash_withdrawal_id}', ['name' => '獲取傭金提現支付信息', 'as' => 'community.chief.cash_withdrawal.payinfo', 'uses' => 'CommunityChiefCashWithdrawal@getMerchantTradeList']);

        // 社區團購訂單
        $api->get('/orders', ['name'=>'獲取訂單列表', 'middleware'=>['datapass'], 'as' => 'community.order.list.get', 'uses'=>'CommunityOrder@getOrderList']);
        $api->get('/orders/export', ['name'=>'導出團購訂單', 'middleware'=>['datapass'], 'as' => 'community.order.list.export', 'uses'=>'CommunityOrder@exportActivityOrderData']);
        $api->get('/order/{order_id}', ['name'=>'獲取訂單詳情', 'middleware'=>['datapass'], 'as' => 'community.order.detail.get', 'uses'=>'CommunityOrder@getOrderDetail']);

        //社區團購活動管理
        $api->get('/list', ['name' => '活動列表', 'as' => 'community.list', 'uses' => 'CommunityActivity@getList']);
        // 活動確認發貨
        $api->post('/activity/confirm/delivery', ['name' => '確認發貨', 'as' => 'community.activity.confirm.delivery', 'uses' => 'CommunityActivity@confirmDeliveryStatus']);
        //社區團購活動管理發貨
        $api->post('/chief/deliver', ['name' => '店鋪發貨', 'as' => 'community.activity.deliver', 'uses' => 'CommunityActivity@deliver']);

        // 社區團購商品池
        $api->get('/items', ['name' => '商品列表', 'as' => 'community.items.list.get', 'uses' => 'CommunityItems@getItemsList']);
        $api->post('/items', ['name' => '添加商品', 'as' => 'community.items.add', 'uses' => 'CommunityItems@createItems']);
        $api->post('/itemMinDeliveryNum', ['name' => '修改商品起送量', 'as' => 'community.item.minDeliveryNum.update', 'uses' => 'CommunityItems@updateItemMinDeliveryNum']);
        $api->post('/itemSort', ['name' => '修改商品排序編號', 'as' => 'community.item.sort.update', 'uses' => 'CommunityItems@updateItemSort']);
        $api->delete('/item/{goods_id}', ['name' => '刪除商品', 'as' => 'community.item.delete', 'uses' => 'CommunityItems@deleteItem']);

        //社區團設置
        $api->get('/activity/setting', ['name' => '商品列表', 'as' => 'community.setting.get', 'uses' => 'CommunitySetting@get']);
        $api->post('/activity/setting', ['name' => '添加商品', 'as' => 'community.setting.save', 'uses' => 'CommunitySetting@save']);
    });
});
