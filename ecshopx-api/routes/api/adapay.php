<?php
$api->version('v1', function($api) {
    $api->group(['prefix' => '/adapay', 'namespace' => 'AdaPayBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/is_open', ['name' => '是否開通', 'as' => 'adapay.is_open', 'uses' => 'OpenAccount@isOpen']);
        $api->get('/withdrawset', ['name' => '獲取提現設置', 'middleware'=>'activated', 'as' => 'adapay.withdraw.get', 'uses' => 'AdapayWithdrawSet@index']);
        $api->post('/withdrawset', ['name' => '提現設置', 'middleware'=>'activated', 'as' => 'adapay.withdraw.save', 'uses' => 'AdapayWithdrawSet@save']);
        $api->get('/drawcash/getList', ['name' => '提現記錄', 'middleware'=>['activated', 'datapass'], 'as' => 'adapay.drawcash.getList', 'uses' => 'AdapayDrawCash@getList']);
        $api->post('/withdraw', ['name' => '匯付提現申請', 'middleware'=>'activated', 'as' => 'adapay.withdraw', 'uses' => 'AdapayDrawCash@withdraw']);
        $api->get('/trade/list', ['name' => '交易單列表', 'middleware'=>'activated', 'as' => 'adapay.trade.getList', 'uses' => 'AdapayTrade@getTradelist']);
        $api->get('/trade/info/{trade_id}', ['name' => '交易單詳情', 'middleware'=>'activated', 'as' => 'adapay.tradeInfo.get', 'uses' => 'AdapayTrade@getTradeInfo']);
        $api->get('/trade/exportdata', ['name'=>'導出交易單列表','middleware'=>'activated', 'as' => 'adapay.trades.list.export', 'uses'=>'ExportData@exportTradeData']);
        $api->get('/distributor/list', ['name' => '獲取店鋪列表', 'middleware'=>'activated', 'as' => 'adapay.distributor.list', 'uses' => 'AdapayTrade@getDistributorList']);

        $api->post('/merchant_entry/create', ['name' => '創建開戶進件申請', 'as' => 'adapay.merchant_entry.create', 'uses' => 'OpenAccount@merchantEntryCreate']);
        $api->get('/merchant_entry/info', ['name' => '開戶進件詳情', 'as' => 'adapay.merchant_entry.info', 'uses' => 'OpenAccount@merchantEntryInfo']);

        $api->get('/member/auditState', ['name' => '用戶對象審核狀態', 'as' => 'adapay.member.auditState', 'uses' => 'Member@getAuditState']);
        $api->get('/member/setValid', ['name' => '用戶進入結算中心狀態', 'as' => 'adapay.member.valid', 'uses' => 'Member@setValid']);
        $api->get('/member/get', ['name' => '獲取個人用戶對象', 'as' => 'adapay.member.info', 'uses' => 'Member@get']);
        $api->post('/member/create', ['name' => '創建個人用戶對象', 'as' => 'adapay.member.create', 'uses' => 'Member@create']);
        $api->post('/member/modify', ['name' => '修改個人用戶對象(未開戶)', 'as' => 'adapay.member.modify', 'uses' => 'Member@modify']);
        $api->post('/member/update', ['name' => '更新個人用戶對象', 'as' => 'adapay.member.update', 'uses' => 'Member@update']);
        $api->get('/corp_member/get', ['name' => '獲取企業用戶對象', 'as' => 'adapay.corp_member.info', 'uses' => 'CorpMember@get']);
        $api->post('/corp_member/create', ['name' => '創建企業用戶對象', 'as' => 'adapay.corp_member.create', 'uses' => 'CorpMember@create']);
        $api->post('/corp_member/modify', ['name' => '修改企業用戶對象(未開戶)', 'as' => 'adapay.corp_member.modify', 'uses' => 'CorpMember@modify']);
        $api->post('/corp_member/update', ['name' => '更新企業用戶對象', 'as' => 'adapay.corp_member.update', 'uses' => 'CorpMember@update']);
        $api->get('/bank/list', ['name' => '獲取結算銀行列表', 'as' => 'adapay.bank.list', 'uses' => 'OpenAccount@getBanksLists']);
        $api->get('/regions/list', ['name' => '獲取省市列表(四位碼)', 'as' => 'adapay.regions.list', 'uses' => 'OpenAccount@getRegionsLists']);
        $api->get('/regions_third/list', ['name' => '獲取省市區列表(六位碼或九位碼)', 'as' => 'adapay.regions_third.list', 'uses' => 'OpenAccount@getRegionsThirdLists']);
        $api->post('/merchant_resident/create', ['name' => '商戶入駐申請', 'as' => 'adapay.merchant_resident.create', 'uses' => 'OpenAccount@merchantResidentCreate']);
        $api->get('/merchant_resident/info', ['name' => '商戶入駐詳情', 'as' => 'adapay.merchant_resident.info', 'uses' => 'OpenAccount@merchantResidentInfo']);
        $api->get('/wx_business_cat/list', ['name' => '獲取微信經營類目', 'as' => 'adapay.wx_business_cat.list', 'uses' => 'OpenAccount@getWxBusinessCatList']);
        $api->get('alipay_industry_cat/list', ['name' => '獲取支付寶行業類目', 'as' => 'adapay.alipay_industry_cat.list', 'uses' => 'OpenAccount@getAlipayIndustryCatList']);
        $api->post('/license/upload', ['name' => '上傳商戶證照', 'as' => 'adapay.license.upload', 'uses' => 'OpenAccount@uploadLicense']);
        $api->post('/license_submit/create', ['name' => '提交商戶證照', 'as' => 'adapay.license_submit.create', 'uses' => 'OpenAccount@submitLicense']);
        $api->get('/license_submit/info', ['name' => '商戶證照詳情', 'as' => 'adapay.license_submit.info', 'uses' => 'OpenAccount@submitLicenseInfo']);
        $api->get('/open_account/step', ['name' => '商戶開戶步驟', 'middleware' => ['datapass'], 'as' => 'adapay.open_account.step', 'uses' => 'OpenAccount@openAccountStep']);
        $api->get('/generate/key', ['name' => '生成RAS密鑰', 'as' => 'adapay.generate.key', 'uses' => 'OpenAccount@generateKey']);
        $api->get('/other/cat', ['name' => '費率 入駐 商戶分類', 'as' => 'adapay.other.cat', 'uses' => 'OpenAccount@otherCat']);

        $api->get('/sub_approve/list', ['name' => '子商戶審批列表', 'as' => 'adapay.sub_approve.list', 'uses' => 'SubMerchant@subApproveLists']);
        $api->get('/sub_approve/info/{id}', ['name' => '子商戶審批詳情', 'middleware' => ['datapass'], 'as' => 'adapay.sub_approve.info', 'uses' => 'SubMerchant@subApproveInfo']);
        $api->post('/sub_approve/save_split_ledger', ['name' => '子商戶審批保存分賬信息', 'as' => 'adapay.sub_approve.save_split_ledger', 'uses' => 'SubMerchant@saveSplitLedger']);
        $api->post('/sub_approve/draw_limit', ['name' => '保存子商戶提現限額', 'as' => 'adapay.sub_approve.draw_limit_set', 'uses' => 'SubMerchant@setDrawLimit']);
        $api->get('/sub_approve/draw_limit', ['name' => '獲取子商戶提現限額', 'as' => 'adapay.sub_approve.draw_limit_get', 'uses' => 'SubMerchant@getDrawLimit']);
        $api->post('/sub_approve/draw_cash_config', ['name' => '保存子商戶提現限額', 'as' => 'adapay.sub_approve.draw_limit_set', 'uses' => 'SubMerchant@setDrawCashConfig']);
        $api->get('/sub_approve/draw_cash_config', ['name' => '獲取子商戶提現限額', 'as' => 'adapay.sub_approve.draw_limit_get', 'uses' => 'SubMerchant@getDrawCashConfig']);

        $api->get('/dealer/list', ['name' => '經銷商列表', 'middleware' => ['datapass'], 'as' => 'adapay.dealer.list', 'uses' => 'Dealer@dealerList']);
        $api->get('/dealer/distributors', ['name' => '經銷商關聯店鋪列表', 'middleware' => ['datapass'], 'as' => 'adapay.dealer.distributorList', 'uses' => 'Dealer@distributorList']);
        $api->get('/dealer/{id}', ['name' => '經銷商詳情', 'middleware' => ['datapass'], 'as' => 'adapay.dealer.info', 'uses' => 'Dealer@dealerInfo']);
        $api->put('/dealer/disable', ['name' => '經銷商開啟 禁用', 'as' => 'adapay.dealer.disable', 'uses' => 'Dealer@openOrDisable']);
        $api->put('/dealer/rel', ['name' => '經銷商關聯店鋪', 'as' => 'adapay.dealer.rel', 'uses' => 'Dealer@dealerRelDistributor']);

        $api->get('/log/list', ['name' => '操作日誌列表', 'as' => 'adapay.log.list', 'uses' => 'AdapayLog@getList']);
        $api->put('/dealer/reset/{operatorId}', ['name' => '經銷商重置密碼', 'as' => 'adapay.dealer.reset', 'uses' => 'Dealer@resetPassword']);
        $api->put('/dealer/update/{operatorId}', ['name' => '經銷商端賬號編輯', 'as' => 'adapay.dealer.update', 'uses' => 'Dealer@update']);
        $api->delete('/dealer/sub/del/{operatorId}', ['name' => '刪除經銷商子賬號', 'as' => 'adapay.dealer.del', 'uses' => 'Dealer@delDealerSub']);
        $api->get('/member/list', ['name' => 'adapay開戶列表(店鋪端 經銷商端)', 'middleware' => ['datapass'], 'as' => 'adapay.member.list', 'uses' => 'Member@lists']);
        $api->get('/dealer/dealer_parent/get', ['name' => '獲取經銷商主賬號id', 'as' => 'adapay.dealer_parent.get', 'uses' => 'Dealer@getDealerParentId']);

    });
});
