<?php
$api->version('v1', function($api) {
    $api->group(['prefix' => '/merchant', 'namespace' => 'MerchantBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/basesetting', ['name' => '獲取基礎設置', 'middleware'=>'activated', 'as' => 'merchant.basesetting.get', 'uses' => 'MerchantSetting@getBase']);
        $api->post('/basesetting', ['name' => '保存基礎設置', 'middleware'=>'activated', 'as' => 'merchant.basesetting.save', 'uses' => 'MerchantSetting@saveBase']);

        $api->get('/type/list', ['name' => '獲取商戶類型列表', 'middleware'=>'activated', 'as' => 'merchant.type.list', 'uses' => 'MerchantSetting@getTypeList']);
        $api->post('/type/create', ['name' => '新增商戶類型', 'middleware'=>'activated', 'as' => 'merchant.type.create', 'uses' => 'MerchantSetting@createType']);
        $api->put('/type/{id}', ['name' => '更新單條商戶類型信息', 'as' => 'merchant.type.update', 'uses' => 'MerchantSetting@updateType']);
        $api->delete('/type/{id}', ['name' => '刪除商戶類型', 'as' => 'merchant.type.delete', 'uses' => 'MerchantSetting@deleteType']);
        $api->get('/operator', ['name' => '獲取商戶賬號列表', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.operator.list', 'uses' => 'MerchantOperator@getOperatorList']);
        $api->post('/operator', ['name' => '商戶修改密碼', 'middleware'=>'activated', 'as' => 'merchant.operator.save', 'uses' => 'MerchantOperator@updateOperatorAccount']);
        $api->put('/operator/{id}', ['name' => '平臺重置商戶密碼', 'middleware'=>'activated', 'as' => 'merchant.operator.update', 'uses' => 'MerchantOperator@resetOperatorAccount']);


        $api->get('/settlement/apply/list', ['name' => '獲取商戶入駐申請列表', 'middleware'=>'activated', 'as' => 'merchant.settlement.apply.list', 'uses' => 'MerchantSettlementApply@getList']);
        $api->get('/settlement/apply/{id}', ['name' => '獲取商戶入駐申請詳情', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.settlement.apply.detail', 'uses' => 'MerchantSettlementApply@getDetail']);
        $api->post('/settlement/apply/audit', ['name' => '審核商戶入駐申請', 'middleware'=>['activated'], 'as' => 'merchant.settlement.apply.audit', 'uses' => 'MerchantSettlementApply@auditData']);

        $api->get('/list', ['name' => '獲取商戶列表', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.list', 'uses' => 'Merchant@getList']);
        $api->get('/detail/{id}', ['name' => '獲取商戶詳情', 'middleware'=>['activated', 'datapass'], 'as' => 'merchant.detail.get', 'uses' => 'Merchant@getDetail']);
        $api->post('/{id}', ['name' => '更新商戶', 'middleware'=>['activated'], 'as' => 'merchant.update', 'uses' => 'Merchant@updateMerchant']);
        $api->post('/', ['name' => '新增商戶', 'middleware'=>['activated'], 'as' => 'merchant.create', 'uses' => 'Merchant@createMerchant']);
        $api->get('/visibletype/list', ['name' => '獲取可見商戶類型列表', 'middleware'=>'activated', 'as' => 'merchant.visibletype.list', 'uses' => 'MerchantSetting@getVisibleTypeList']);
        $api->post('/disabled/update/{id}', ['name' => '修改單個商戶的禁用狀態', 'middleware'=>['activated'], 'as' => 'merchant.disabled.update', 'uses' => 'Merchant@updateMerchantDisabled']);
        $api->post('/auditgoods/update/{id}', ['name' => '修改單個商戶的審核商品狀態', 'middleware'=>['activated'], 'as' => 'merchant.audit.goods.update', 'uses' => 'Merchant@updateMerchantAuditGoods']);
        $api->get('/info', ['name' => '獲取商戶詳情', 'middleware'=>['activated'], 'as' => 'merchant.info.get', 'uses' => 'Merchant@getInfo']);


    });
});
