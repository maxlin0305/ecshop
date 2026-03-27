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
    // 企業相關信息
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action' ,'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/discountcard', ['name' => '添加優惠券', 'as' => 'card.create', 'uses'=>'DiscountCard@createDiscountCard']);
        $api->delete('/discountcard', ['name' => '刪除卡券', 'as' => 'card.delete', 'uses'=>'DiscountCard@deleteDiscountCard']);
        $api->patch('/discountcard', ['name' => '修改卡券內容', 'as' => 'card.update', 'uses'=>'DiscountCard@updateDiscountCard']);
        $api->get('/discountcard/get', ['name' => '獲取卡券明細', 'as' => 'card.get', 'uses'=>'DiscountCard@getDiscountCardDetail']);
        $api->get('/discountcard/list', ['name' => '獲取卡券列表', 'as' => 'card.list', 'uses'=>'DiscountCard@getDiscountCardList']);
        $api->get('/discountcard/detail/list', ['name' => '獲取卡券領取列表以及使用明細', 'middleware' => ['datapass'], 'as' => 'card.detail.list', 'uses'=>'DiscountCardDetail@getDiscountCardDetail']);
        $api->get('/effectiveDiscountcard/list', ['name' => '獲取有效卡券列表', 'as' => 'effectiveCard.list', 'uses'=>'DiscountCard@getEffectiveDiscountCardList']);
        $api->post('/discountcard/updatestore', ['name' => '修改卡券庫存', 'as' => 'card.store', 'uses'=>'DiscountCard@updateCardStore']);
        $api->post('/discountcard/uploadToWechat', ['name' => '卡券推送至微信', 'as' => 'card.upload', 'uses'=>'DiscountCard@uploadToWechatCard']);

        $api->get('/discountcard/listdata', ['name' => '獲取優惠券列表', 'as' => 'card.easy.list', 'uses'=>'DiscountCard@getEasyDiscountList']);
        $api->get('/discountcard/couponGrantSetting', ['name' => '獲取優惠券發放管理配置信息', 'as' => 'couponGrantSetting.list', 'uses' => 'DiscountCard@getCouponCardGrantSetting']);
        $api->post('/discountcard/couponGrantSetting', ['name' => '保存優惠券發放管理配置信息', 'as' => 'couponGrantSetting.set', 'uses' => 'DiscountCard@setCouponCardGrantSetting']);

        $api->post('/discountcard/consume', ['name' => '兌換券核銷', 'as' => 'card.consume', 'uses'=>'DiscountCard@consumeExCard']);

    });

    //會員卡相關
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->put('/membercard', ['name' => '更新會員卡設置', 'as' => 'membercard.setting', 'uses'=>'MemberCard@setMemberCard']);
        $api->get('/membercard', ['name' => '獲取會員卡信息', 'as' => 'membercard.info', 'uses'=>'MemberCard@getMemberCard']);

        $api->put('/membercard/grade', ['name' => '更新會員卡等級', 'as' => 'membercard.grade.add', 'uses'=>'MemberCard@updateMembercardGrade']);
        $api->get('/membercard/defaultGrade', ['name' => '獲取會員卡默認等級', 'as' => 'membercard.default.grade', 'uses'=>'MemberCard@getDefaultGrade']);
        $api->get('/membercard/grades', ['name' => '獲取會員等級列表', 'as' => 'membercard.grade.list', 'uses'=>'MemberCard@getGradeList']);



        //会员卡升级设置
        $api->get('/membercard/settings', ['name' => '獲取會員等級列表', 'as' => 'membercard.grade.list', 'uses'=>'MemberCard@getSettings']);
        $api->post('/membercard/settings', ['name' => '更新會員等級列表', 'as' => 'membercard.grade.list', 'uses'=>'MemberCard@setSettings']);
    });

    //付費會員等級相關
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->put('/membercard/vipgrade', ['name' => '保存付費會員等級卡', 'as' => 'membercard.vipgrade.add', 'uses'=>'VipGradeController@addDataVipGrade']);
        $api->get('/membercard/vipgrade', ['name' => '獲取付費會員等級卡列表', 'as' => 'membercard.vipgrade.list', 'uses'=>'VipGradeController@listDataVipGrade']);

        $api->get('/vipgrade/order', ['name' => '獲取會員卡購買記錄', 'middleware' => 'datapass', 'as' => 'vipgrade.order.list', 'uses'=>'VipGradeController@listDataVipGradeOrder']);
        $api->get('/vipgrades/uselist', ['name' => '獲取指定用戶所有的付費會員等級到期時間', 'as' => 'vipgrade.use.list', 'uses'=>'VipGradeController@getAllUserVipGrade']);
        $api->put('/vipgrades/active_delay', ['name' => '主動延期付費會員', 'as' => 'vipgrade.use.active.delay', 'uses'=>'VipGradeController@receiveMemberCard']);
        $api->put('/vipgrades/batch_active_delay', ['name' => '批量主動延期付費會員', 'as' => 'vipgrade.use.batch.active.delay', 'uses'=>'VipGradeController@batchReceiveMemberCard']);
    });

    // 卡券包相關操作
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/voucher/package/list', ['name' => '獲取卡券包列表', 'as' => 'voucher.package.list', 'uses' => 'Package@getList']);
        $api->get('/voucher/package/details', ['name' => '獲取卡券包詳情', 'as' => 'voucher.package.details', 'uses' => 'Package@getDetails']);
        $api->post('/voucher/package/check_grade_limit', ['name' => '校驗卡券包等級限製', 'as' => 'voucher.package.check_grade_limit', 'uses' => 'Package@checkCardPackageGradeLimit']);
        $api->get('/voucher/package/get_receives_log', ['name' => '卡券包領取日誌', 'middleware' => ['datapass'], 'as' => 'voucher.package.receives_log', 'uses' => 'Package@getPackageReceivesLog']);
        $api->post('/voucher/package', ['name' => '創建卡券包', 'as' => 'voucher.package.create', 'uses' => 'Package@createPackage']);
        $api->patch('/voucher/package', ['name' => '編輯卡券包', 'as' => 'voucher.package.edit', 'uses' => 'Package@editPackage']);
        $api->delete('/voucher/package', ['name' => '刪除卡券包', 'as' => 'voucher.package.delete', 'uses' => 'Package@deletePackage']);
    });

    // 移動收銀相關操作
    $api->group(['namespace' => 'KaquanBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated'], 'providers' => 'jwt'], function ($api) {
        $api->get('/getUserCardList', ['name' => '獲取用戶可用的優惠券', 'as' => 'user.card.list', 'uses' => 'UserDiscount@getUserCardList']);
    });
});
