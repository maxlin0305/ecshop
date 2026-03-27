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
    // 微信相關信息
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/promotions/register', ['name'=>'獲取註冊引導營銷配置','middleware' => 'activated', 'as' => 'promotions.register.get', 'uses' => 'RegisterPromotions@getRegisterPromotionsConfig']);
        $api->post('/promotions/register', ['name'=>'註冊引導營銷配置設置','middleware' => 'activated', 'as' => 'Promotions.register.add', 'uses' => 'RegisterPromotions@saveRegisterPromotionsConfig']);

        $api->get('/promotions/point', ['name'=>'獲取註冊積分配置','middleware' => 'activated', 'as' => 'promotions.register.point.get', 'uses' => 'RegisterPromotions@getRegisterPointConfig']);
        $api->post('/promotions/point', ['name'=>'註冊積分配置','middleware' => 'activated', 'as' => 'Promotions.register.point.add', 'uses' => 'RegisterPromotions@saveRegisterPointConfig']);

        $api->get('/sms/basic', ['name'=>'短信賬戶基本信息','middleware' => 'activated', 'as' => 'Promotions.sms.basic', 'uses' => 'Sms@getSmsBasic']);
        $api->get('/sms/templates', ['name'=>'獲取短信模版列表','middleware' => 'activated', 'as' => 'Promotions.sms.templates.list', 'uses' => 'Sms@getSmsTemplateList']);
        $api->patch('/sms/template', ['name'=>'更新短信模版配置','middleware' => 'activated', 'as' => 'Promotions.sms.template.up', 'uses' => 'Sms@updateSmsTemplate']);
        $api->get('/sms/sign', ['name'=>'獲取短信簽名','middleware' => 'activated', 'as' => 'Promotions.sms.sign.get', 'uses' => 'Sms@getSmsSign']);
        $api->post('/sms/sign', ['name'=>'設置短信簽名','middleware' => 'activated', 'as' => 'Promotions.sms.sign.save', 'uses' => 'Sms@saveSmsSign']);
        $api->get('/wxa/notice/templates', [ 'name'=>'小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates',  'uses'=>'WxaTemplate@getWxaTemplateList']);
        $api->put('/wxa/notice/templates', [ 'name'=>'開通小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates.open',  'uses'=>'WxaTemplate@openWxaTemplate']);
        $api->get('/ali/notice/templates', [ 'name'=>'小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates',  'uses'=>'AliTemplate@getAliTemplateList']);
        $api->put('/ali/notice/templates', [ 'name'=>'開通小程序通知消息模版','middleware'=>'activated',  'as' => 'wechat.wxa.notice.templates.open',  'uses'=>'AliTemplate@openAliTemplate']);

        // 微信助力活動
        $api->post('/promotions/bargains', ['name'=>'創建助力活動','middleware' => 'activated', 'as' => 'Promotions.bargains.add', 'uses' => 'BargainPromotions@createBargain']);
        $api->get('/promotions/bargains', ['name'=>'獲取助力活動列表','middleware' => 'activated', 'as' => 'Promotions.bargains.list', 'uses' => 'BargainPromotions@getBargainList']);
        $api->get('/promotions/bargains/{bargain_id}', ['name'=>'獲取助力活動詳情','middleware' => 'activated', 'as' => 'Promotions.bargains.detail', 'uses' => 'BargainPromotions@getBargainDetail']);
        $api->put('/promotions/bargains/{bargain_id}', ['name'=>'更新助力活動','middleware' => 'activated', 'as' => 'Promotions.bargains.update', 'uses' => 'BargainPromotions@updateBargain']);
        $api->put('/promotions/bargains/termination/{bargain_id}', ['name'=>'終止助力活動','middleware' => 'activated', 'as' => 'Promotions.bargains.terminate', 'uses' => 'BargainPromotions@terminateBargain']);
        $api->delete('/promotions/bargains/{bargain_id}', ['name'=>'刪除助力活動','middleware' => 'activated', 'as' => 'Promotions.bargains.update', 'uses' => 'BargainPromotions@deleteBargain']);

        // 自動化營銷活動
        $api->post('/promotions/activity/validNum', ['name'=>'檢查當前營銷活動的有效數量','middleware' => 'activated', 'as' => 'promotions.activity.validNum', 'uses' => 'ActivityPromotions@checkActiveValidNum']);
        $api->put('/promotions/activity/invalid', ['name'=>'將當前自動化營銷活動失效','middleware' => 'activated', 'as' => 'promotions.activity.invalid', 'uses' => 'ActivityPromotions@updateStatusInvalid']);
        $api->post('/promotions/activity/create', ['name'=>'創建自動化營銷活動','middleware' => 'activated', 'as' => 'promotions.activity.create', 'uses' => 'ActivityPromotions@createActivity']);
        $api->get('/promotions/activity/lists', ['name'=>'獲取自動化營銷活動列表','middleware' => 'activated', 'as' => 'promotions.activity.lists', 'uses' => 'ActivityPromotions@getActivityList']);
        $api->post('/promotions/activity/give', ['name'=>'後臺發放優惠券','middleware' => 'activated', 'as' => 'promotions.give.create', 'uses' => 'GivePromotions@give']);
        $api->get('/promotions/activity/give', ['name'=>'優惠券發放日誌','middleware' => 'activated', 'as' => 'promotions.give.list', 'uses' => 'GivePromotions@getGiveLog']);
        $api->get('/promotions/activity/give/{id}', ['name'=>'優惠券贈送失敗記錄','middleware' => ['activated', 'datapass'], 'as' => 'promotions.give.info', 'uses' => 'GivePromotions@getGiveErrorLog']);

        // 拼團活動
        $api->get('/promotions/groups', ['name'=>'獲取拼團活動列表','middleware' => 'activated', 'as' => 'promotions.groups.list', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsActivityList']);
        $api->get('/promotions/groups/{groupId}', ['name'=>'獲取拼團活動詳情','middleware' => 'activated', 'as' => 'promotions.groups.detail', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsActivityDetail']);
        $api->get('/promotions/groups/{groupId}/team/', ['name'=>'獲取拼團數據詳情','middleware' => 'activated', 'as' => 'promotions.groups.teamlist', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsTeamList']);
        $api->get('/promotions/groups/team/{teamId}', ['name'=>'獲取拼團數據成員詳情','middleware' => 'activated', 'as' => 'promotions.groups.teaminfo', 'uses' => 'PromotionGroupsActivity@getPromotionGroupsTeamInfo']);
        $api->post('/promotions/groups', ['name'=>'創建拼團活動','middleware' => 'activated', 'as' => 'promotions.groups.create', 'uses' => 'PromotionGroupsActivity@createPromotionGroupsActivity']);
        $api->put('/promotions/groups/{groupId}', ['name'=>'更新拼團活動','middleware' => 'activated', 'as' => 'promotions.groups.update', 'uses' => 'PromotionGroupsActivity@updatePromotionGroupsActivity']);
        $api->put('/promotions/groups/finish/{groupId}', ['name'=>'結束拼團活動','middleware' => 'activated', 'as' => 'promotions.groups.finish', 'uses' => 'PromotionGroupsActivity@finishPromotionGroupsActivity']);
        $api->delete('/promotions/groups/{groupId}', ['name'=>'刪除拼團活動','middleware' => 'activated', 'as' => 'promotions.groups.delete', 'uses' => 'PromotionGroupsActivity@deletePromotionGroupsActivity']);

        //大轉盤
        $api->post('/promotions/turntableconfig', ['name'=>'修改大轉盤配置','middleware' => 'activated', 'as' => 'promotions.turntable.config.set', 'uses' => 'Turntable@setTurntableConfig']);
        $api->get('/promotions/turntableconfig', ['name'=>'獲取大轉盤配置','middleware' => 'activated', 'as' => 'promotions.turntable.config.get', 'uses' => 'Turntable@getTurntableConfig']);

        //活動文章（營銷活動內容管理）
        $api->post('/promotions/activearticle', ['name'=>'添加活動文章','middleware' => 'activated', 'as' => 'promotions.article.save', 'uses' => 'ActivityPromotions@saveActiveArticle']);
        $api->get('/promotions/activearticle/list', ['name'=>'獲取活動文章列表','middleware' => 'activated', 'as' => 'promotions.article.list', 'uses' => 'ActivityPromotions@getActiveArticleList']);
        $api->get('/promotions/activearticle/{id}', ['name'=>'獲取活動文章詳情','middleware' => 'activated', 'as' => 'promotions.article.detail', 'uses' => 'ActivityPromotions@getActiveArticleDetail']);
        $api->put('/promotions/activearticle', ['name'=>'修改活動文章','middleware' => 'activated', 'as' => 'promotions.article.update', 'uses' => 'ActivityPromotions@updateActiveArticle']);
        $api->delete('/promotions/activearticle/{id}', ['name'=>'刪除活動文章','middleware' => 'activated', 'as' => 'promotions.article.delete', 'uses' => 'ActivityPromotions@deleteActiveArticle']);


        //會員營銷-額外積分
        $api->post('/promotions/extrapoint', ['name'=>'創建額外積分活動','middleware' => 'activated', 'as' => 'promotions.extrapoints.create', 'uses' => 'ExtraPointActivity@createActivity']);
        $api->put('/promotions/extrapoint', ['name'=>'修改額外積分活動','middleware' => 'activated', 'as' => 'promotions.extrapoints.update', 'uses' => 'ExtraPointActivity@updateActivity']);
        $api->get('/promotions/extrapoint/lists', ['name'=>'額外積分活動列表','middleware' => 'activated', 'as' => 'promotions.extrapoints.lists', 'uses' => 'ExtraPointActivity@getActivityList']);
        $api->put('/promotions/extrapoint/invalid', ['name'=>'將當前額外積分活動失效','middleware' => 'activated', 'as' => 'promotions.extrapoints.invalid', 'uses' => 'ExtraPointActivity@updateStatusInvalid']);
        $api->get('/promotions/extrapoint/{id}', ['name'=>'獲取額外積分活動詳情','middleware' => 'activated', 'as' => 'promotions.extrapoints.info', 'uses' => 'ExtraPointActivity@getActivityInfo']);

        // 組合商品相關接口
        $api->get('/promotions/package', ['name'=>'組合商品活動列表','middleware' => 'activated', 'as' => 'promotions.package.list.get', 'uses' => 'PackagePromotions@lists']);
        $api->get('/promotions/package/{packageId}', ['name'=>'獲取組合商品活動','middleware' => 'activated', 'as' => 'promotions.package.info.get', 'uses' => 'PackagePromotions@info']);
        $api->post('/promotions/package', ['name'=>'添加組合商品活動','middleware' => 'activated', 'as' => 'promotions.package.create', 'uses' => 'PackagePromotions@create']);
        $api->put('/promotions/package/{packageId}', ['name'=>'修改組合商品活動','middleware' => 'activated', 'as' => 'promotions.package.update', 'uses' => 'PackagePromotions@update']);
        $api->delete('/promotions/package/cancel/{packageId}', ['name'=>'取消組合商品活動','middleware' => 'activated', 'as' => 'promotions.package.cancel', 'uses' => 'PackagePromotions@cancel']);

        // 限購活動相關接口
        $api->get('/promotions/limit', ['name'=>'限購活動列表','middleware' => 'activated', 'as' => 'promotions.limit.list.get', 'uses' => 'LimitPromotions@lists']);
        $api->get('/promotions/limit_error_desc', ['name'=>'下載商品限購錯誤信息','middleware' => 'activated', 'as' => 'promotions.limit.error_desc.get', 'uses' => 'LimitPromotions@exportErrorDesc']);
        $api->get('/promotions/limit/{limitId}', ['name'=>'獲取限購活動詳情','middleware' => 'activated', 'as' => 'promotions.limit.info.get', 'uses' => 'LimitPromotions@info']);
        $api->post('/promotions/limit', ['name'=>'添加限購活動','middleware' => 'activated', 'as' => 'promotions.limit.create', 'uses' => 'LimitPromotions@create']);
        $api->put('/promotions/limit/{limitId}', ['name'=>'修改限購活動','middleware' => 'activated', 'as' => 'promotions.limit.update', 'uses' => 'LimitPromotions@update']);
        $api->post('/promotions/limit_items/upload', ['name'=>'上傳限購商品文件','middleware' => 'activated', 'as' => 'promotions.limit_items.upload', 'uses' => 'LimitPromotions@uploadLimitItems']);

        $api->get('/promotions/limit_items/{limitId}', ['name'=>'獲取限購商品列表','middleware' => 'activated', 'as' => 'promotions.limit_items.list.get', 'uses' => 'LimitPromotions@getLimitItems']);
        $api->delete('/promotions/limit_items/{limitId}', ['name'=>'刪除限購商品','middleware' => 'activated', 'as' => 'promotions.limit_item.delete', 'uses' => 'LimitPromotions@deleteLimitItem']);
        $api->put('/promotions/limit_items/{limitId}', ['name'=>'更新限購商品數量','middleware' => 'activated', 'as' => 'promotions.limit_item.update', 'uses' => 'LimitPromotions@updateLimitItem']);
        $api->post('/promotions/limit_items_save', ['name'=>'保存限購商品','middleware' => 'activated', 'as' => 'promotions.limit_items.save', 'uses' => 'LimitPromotions@saveLimitItems']);

        $api->delete('/promotions/limit/cancel/{limitId}', ['name'=>'取消限購活動','middleware' => 'activated', 'as' => 'promotions.limit.cancel', 'uses' => 'LimitPromotions@cancel']);
        // 直播相關接口
        $api->get('/promotions/liverooms', ['name'=>'獲取直播、回放視頻列表', 'middleware'=>'activated',  'as' => 'promotions.liverooms',  'uses'=>'ActivityPromotions@getLiveRooms']);
    });
});

//以下為新的註冊營銷（分銷商）
$api->version('v1', function($api) {
    // 微信相關信息
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/promotions/register/distributor', ['name'=>'創建/修改 註冊促銷(分銷商)', 'middleware'=>'activated',  'as' => 'Promotions.register.add',  'uses'=>'RegisterController@createRegister']);
        $api->get('/promotions/register/distributor', [ 'name'=>'獲取註冊促銷列表(分銷商)','middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@getRegisterList']);
        $api->get('/promotions/register/distributor/{id}', ['name'=>'獲取註冊促銷詳情(分銷商)', 'middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@getRegisterInfo']);
        $api->delete('/promotions/register/distributor/{id}', ['name'=>'刪除註冊促銷(分銷商)', 'middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@deleteRegister']);
        $api->get('/promotions/distributor', [ 'name'=>'獲取分銷商列表','middleware'=>'activated',  'as' => 'promotions.register.get',  'uses'=>'RegisterController@getDistributorList']);
    });
});

$api->version('v1', function($api){
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        // 秒殺活動
        $api->post('/promotions/seckillactivity/create', ['name'=>'創建秒殺活動', 'middleware'=>'activated',  'as' => 'Promotions.seckill.add',  'uses'=>'SeckillActivity@createSeckillActivity']);
        $api->put('/promotions/seckillactivity/update', [ 'name'=>'修改秒殺活動','middleware'=>'activated',  'as' => 'Promotions.seckill.update',  'uses'=>'SeckillActivity@updateSeckillActivity']);
        $api->get('/promotions/seckillactivity/getlist', ['name'=>'獲取秒殺活動列表', 'middleware'=>'activated',  'as' => 'Promotions.seckill.list',  'uses'=>'SeckillActivity@getSeckillActivityList']);
        $api->get('/promotions/seckillactivity/getinfo', [ 'name'=>'創建秒殺活動','middleware'=>'activated',  'as' => 'Promotions.seckill.info',  'uses'=>'SeckillActivity@getSeckillActivityInfo']);
        $api->put('/promotions/seckillactivity/updatestatus', ['name'=>'更新秒殺活動狀態', 'middleware'=>'activated',  'as' => 'Promotions.seckill.statusupdate',  'uses'=>'SeckillActivity@updateStatus']);
        $api->get('/promotions/seckillactivity/getIteminfo',['name'=>'獲取秒殺活動商品列表', 'middleware'=>'activated',  'as' => 'Promotions.seckill.item.list',  'uses'=>'SeckillActivity@getSeckillItemList']);
        $api->get('/promotions/seckillactivity/wxcode',[ 'name'=>'獲取秒殺活動小程序碼','middleware'=>'activated',  'as' => 'Promotions.seckill.wxcode',  'uses'=>'SeckillActivity@getSeckillWxaCode']);

        //創建活動獲取商品
        $api->get('/promotions/seckillactivity/search/items', ['name' => '根據條件獲取商品列表', 'middleware' => 'activated', 'as' => 'Promotions.seckill.search.item.list', 'uses' => 'SeckillActivity@searchItems']);

        //以下為滿折,滿減，滿贈營銷
        $api->post('/marketing/create', ['name'=>'創建滿折促銷活動', 'middleware'=>'activated',  'as' => 'marketing.add',  'uses'=>'MarketingActivity@createMarketingActivity']);
        $api->delete('/marketing/delete', ['name'=>'刪除滿折促銷活動', 'middleware'=>'activated',  'as' => 'marketing.delete',  'uses'=>'MarketingActivity@deleteMarketingActivity']);
        $api->put('/marketing/update', ['name'=>'修改滿折促銷活動', 'middleware'=>'activated',  'as' => 'marketing.update',  'uses'=>'MarketingActivity@updateMarketingActivity']);
        $api->get('/marketing/getlist', ['name'=>'獲取滿折促銷活動列表', 'middleware'=>'activated',  'as' => 'marketing.list',  'uses'=>'MarketingActivity@getMarketingActivityList']);
        $api->get('/marketing/getinfo', [ 'name'=>'獲取滿折促銷活動詳情','middleware'=>'activated',  'as' => 'marketing.info',  'uses'=>'MarketingActivity@getMarketingActivityInfo']);
        $api->get('/marketing/getItemList', ['name'=>'獲取滿折促銷活動商品列表', 'middleware'=>'activated',  'as' => 'marketing.item.list',  'uses'=>'MarketingActivity@getActivityItemList']);

        //以下為商品團購（多買優惠）營銷活動
        $api->post('/promotions/multibuy/create', ['name'=>'創建多買優惠活動', 'middleware'=>'activated',  'as' => 'multibuy.add',  'uses'=>'MarketingActivity@createMarketingActivity']);
        $api->delete('/promotions/multibuy/delete', ['name'=>'刪除多買優惠活動', 'middleware'=>'activated',  'as' => 'multibuy.delete',  'uses'=>'MarketingActivity@deleteMarketingActivity']);
        $api->put('/promotions/multibuy/update', ['name'=>'修改多買優惠活動', 'middleware'=>'activated',  'as' => 'multibuy.update',  'uses'=>'MarketingActivity@updateMarketingActivity']);
        $api->get('/promotions/multibuy/getlist', ['name'=>'獲取多買優惠活動列表', 'middleware'=>'activated',  'as' => 'multibuy.list',  'uses'=>'MarketingActivity@getMarketingActivityList']);
        $api->get('/promotions/multibuy/getinfo', [ 'name'=>'獲取多買優惠活動詳情','middleware'=>'activated',  'as' => 'multibuy.info',  'uses'=>'MarketingActivity@getMarketingActivityInfo']);
        $api->get('/promotions/multibuy/getItemList', ['name'=>'獲取多買優惠活動商品列表', 'middleware'=>'activated',  'as' => 'multibuy.item.list',  'uses'=>'MarketingActivity@getActivityItemList']);

        //猜你喜歡商品配置
        $api->get('/promotions/recommendlike', ['name'=>'獲取猜你喜歡商品列表','middleware'=>'activated', 'as' => 'recommendlike.list', 'uses' => 'RecommendLikeController@getRecommendLikeLists']);
        $api->put('/promotions/recommendlike', ['name'=>'編輯猜你喜歡商品','middleware'=>'activated', 'as' => 'recommendlike.update', 'uses' => 'RecommendLikeController@updateRecommendLike']);
        //獲取該企業的猜你喜歡的所有商品列表 或者 商品id
        $api->get('/promotions/recommendlikes', ['name'=>'獲取猜你喜歡商品','middleware'=>'activated', 'as' => 'recommendlike.list', 'uses' => 'RecommendLikeController@getRecommendLikeItems']);
        $api->post('/promotions/recommendlike', ['name'=>'添加猜你喜歡商品','middleware'=>'activated', 'as' => 'recommendlike.save', 'uses' => 'RecommendLikeController@createRecommendLike']);
        $api->delete('/promotions/recommendlike/{id}', ['name'=>'刪除猜你喜歡商品','middleware'=>'activated', 'as' => 'recommendlike.delete', 'uses' => 'RecommendLikeController@delRecommendLike']);

        //以下為定向促銷
        $api->post('/specific/crowd/discount', ['name'=>'創建定向促銷', 'middleware'=>'activated',  'as' => 'specific.crowd.discount.add',  'uses'=>'SpecificCrowdDiscount@createSpecificCrowdDiscount']);
        $api->put('/specific/crowd/discount', ['name'=>'更新定向促銷', 'middleware'=>'activated',  'as' => 'specific.crowd.discount.update',  'uses'=>'SpecificCrowdDiscount@updateSpecificCrowdDiscount']);
        $api->get('/specific/crowd/discountList', ['name'=>'獲取定向促銷列表', 'middleware'=>'activated',  'as' => 'specific.crowd.discount.list',  'uses'=>'SpecificCrowdDiscount@getSpecificCrowdDiscountList']);
        $api->get('/specific/crowd/discountInfo', [ 'name'=>'獲取定向促銷詳情','middleware'=>'activated',  'as' => 'specific.crowd.discount.info',  'uses'=>'SpecificCrowdDiscount@getSpecificCrowdDiscountInfo']);
        $api->get('/specific/crowd/discountLogList', [ 'name'=>'獲取定向促銷優惠日誌','middleware'=>['activated','datapass'],  'as' => 'specific.crowd.discount.loglist',  'uses'=>'SpecificCrowdDiscount@getSpecificcrowddiscountLogList']);

        // 會員營銷-積分升值
        $api->get('/promotions/pointupvaluation/lists', ['name'=>'獲取積分升值活動列表', 'middleware'=>'activated',  'as' => 'promotions.pointupvaluation.list',  'uses'=>'PointupvaluationActivity@getActivityList']);
        $api->post('/promotions/pointupvaluation/create', ['name'=>'創建積分升值活動', 'middleware'=>'activated',  'as' => 'promotions.pointupvaluation.add',  'uses'=>'PointupvaluationActivity@createActivity']);
        $api->put('/promotions/pointupvaluation/update', [ 'name'=>'修改積分升值活動','middleware'=>'activated',  'as' => 'promotions.pointupvaluation.update',  'uses'=>'PointupvaluationActivity@updateActivity']);
        $api->get('/promotions/pointupvaluation/getinfo', [ 'name'=>'獲取積分升值活動詳情','middleware'=>'activated',  'as' => 'promotions.pointupvaluation.info',  'uses'=>'PointupvaluationActivity@getActivityInfo']);
        $api->put('/promotions/pointupvaluation/updatestatus', ['name'=>'更新積分升值活動狀態', 'middleware'=>'activated',  'as' => 'promotions.pointupvaluation.statusupdate',  'uses'=>'PointupvaluationActivity@updateStatus']);

        // 營銷-員工內購
        $api->get('/promotions/employeepurchase/lists', ['name'=>'獲取員工內購活動列表', 'middleware'=>'activated',  'as' => 'promotions.employeepurchase.list',  'uses'=>'EmployeePurchaseActivity@getActivityList']);
        $api->post('/promotions/employeepurchase/create', ['name'=>'創建員工內購活動', 'middleware'=>'activated',  'as' => 'promotions.employeepurchase.add',  'uses'=>'EmployeePurchaseActivity@createActivity']);
        $api->put('/promotions/employeepurchase/update', [ 'name'=>'修改員工內購活動','middleware'=>'activated',  'as' => 'promotions.employeepurchase.update',  'uses'=>'EmployeePurchaseActivity@updateActivity']);
        $api->post('/promotions/employeepurchase/endactivity', [ 'name'=>'終止員工內購活動','middleware'=>'activated',  'as' => 'promotions.employeepurchase.endactivity',  'uses'=>'EmployeePurchaseActivity@endActivity']);
        $api->get('/promotions/employeepurchase/getinfo', [ 'name'=>'獲取員工內購活動詳情','middleware'=>'activated',  'as' => 'promotions.employeepurchase.info',  'uses'=>'EmployeePurchaseActivity@getActivityInfo']);
        $api->get('/promotions/employeepurchase/dependents/lists', [ 'name'=>'獲取員工內購活動詳情','middleware'=>'activated',  'as' => 'promotions.employeepurchase.dependents.lists',  'uses'=>'EmployeePurchaseActivity@getDependentsLists']);

    });
});

$api->version('v1', function($api){
    $api->group(['namespace' => 'PromotionsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/promotions/checkin/getlist',['name'=>'獲取會員簽到記錄列表', 'middleware'=>'activated',  'as' => 'Promotions.checkin.list',  'uses'=>'CheckInController@getCheckInList']);
    });
});
