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
    // 微信相關信息
    $api->group(['namespace' => 'WechatBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/wechat/pre_auth_url', ['name'=>'獲取微信公眾號預授權URL地址','middleware'=>'activated',  'as' => 'wechat.pre_auth_url',  'uses'=>'Authorized@getPreAuthUrl']);
        $api->post('/wechat/bind', [ 'name'=>'回調綁定當前授權信息', 'middleware'=>'activated', 'as' => 'wechat.bind', 'uses'=>'Authorized@authorizedBind']);
        $api->post('/wechat/directbind', [ 'name'=>'添加直連小程序', 'middleware'=>'activated', 'as' => 'wechat.bind.direct', 'uses'=>'Authorized@directBind']);

        $api->get('/wechat/authorizerinfo', [ 'name'=>'獲取公眾帳號基礎信息','middleware'=>'activated', 'as' => 'wechat.authorizerInfo', 'uses'=>'Authorized@getAuthorizerInfo']);
        //同步微信端菜單到開發端
        //$api->get('/wechat/menu', [ 'middleware'=>'activated', 'as' => 'wechat.menu', 'uses'=>'Menu@getAll']);

        $api->post('/wechat/menu', [ 'name'=>'添加菜單', 'middleware' => 'activated',  'as' => 'wechat.add_menu', 'uses'=>'Menu@addMenu']);
        $api->delete('/wechat/menu', [ 'name'=>'刪除菜單', 'middleware' => 'activated',  'as' => 'wechat.remove_menu', 'uses'=>'Menu@removeMenu']);
        $api->get('/wechat/menutree', ['name'=>'獲取菜單樹形列表','middleware' => 'activated',  'as' => 'wechat.get_menu', 'uses' => 'Menu@getMenuTree']);

        $api->post('/wechat/kfs', [ 'name'=>'添加微信客服', 'middleware'=>'activated', 'as' => 'wechat.create.kfs', 'uses'=>'Kf@createWechatKf']);
        $api->get('/wechat/kfs', [ 'name'=>'獲取所有客服賬號列表','middleware'=>'activated', 'as' => 'wechat.lists.kfs', 'uses'=>'Kf@lists']);
        $api->delete('/wechat/kfs', ['name'=>'刪除指定微信客服', 'middleware'=>'activated', 'as' => 'wechat.delete.kfs', 'uses'=>'Kf@deleteWechatKf']);
        $api->post('/wechat/update/kfs', [ 'name'=>'修改指定微信客服','middleware'=>'activated', 'as' => 'wechat.update.kfs', 'uses'=>'Kf@updateWechatKf']);

        $api->post('/wechat/keyword/reply', ['name'=>'新增關鍵字消息回復',  'middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.add', 'uses'=>'MessageReply@addKeywordReply']);
        $api->get('/wechat/keyword/reply', ['name'=>'獲取關鍵字回復列表', 'middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.get', 'uses'=>'MessageReply@getKeywordReplyList']);
        $api->put('/wechat/keyword/reply', [ 'name'=>'更新關鍵字消息回復','middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.put', 'uses'=>'MessageReply@updateKeywordReply']);
        $api->delete('/wechat/keyword/reply', [ 'name'=>'刪除關鍵字回復規則','middleware'=>'activated', 'as' => 'wechat.keyword.messagereply.delete', 'uses'=>'MessageReply@deleteKeywordReply']);

        $api->post('/wechat/default/reply', [ 'name'=>'設置默認回復', 'middleware'=>'activated', 'as' => 'wechat.default.messagereply.add', 'uses'=>'MessageReply@setDefaultReply']);
        $api->get('/wechat/default/reply', ['name'=>'獲取默認消息回復', 'middleware'=>'activated', 'as' => 'wechat.default.messagereply.get', 'uses'=>'MessageReply@getDefaultReply']);
        $api->get('/wechat/openkf/reply', ['name'=>'獲取多客服回復配置', 'middleware'=>'activated', 'as' => 'wechat.openkf.messagereply.get', 'uses'=>'MessageReply@getOpenKfReply']);
        $api->post('/wechat/openkf/reply', [ 'name'=>'設置多客服回復配置','middleware'=>'activated', 'as' => 'wechat.openkf.messagereply.set', 'uses'=>'MessageReply@setOpenKfReply']);
        $api->get('/wechat/subscribe/reply', [ 'name'=>'獲取被關註自動回復消息配置','middleware'=>'activated', 'as' => 'wechat.subscribe.messagereply.get', 'uses'=>'MessageReply@getSubscribeReply']);
        $api->post('/wechat/subscribe/reply', [ 'name'=>'設置被關註自動回復消息', 'middleware'=>'activated', 'as' => 'wechat.subscribe.messagereply.set', 'uses'=>'MessageReply@setSubscribeReply']);

        $api->post('/wechat/material', ['name'=>'上傳素材', 'middleware'=>'activated', 'as' => 'wechat.material.upload', 'uses'=>'Material@uploadMaterial']);
        $api->post('/wechat/news/image', ['name'=>'上傳圖文內的圖片', 'middleware'=>'activated', 'as' => 'wechat.news.image.upload', 'uses'=>'Material@uploadArticleImage']);
        $api->delete('/wechat/material', [ 'name'=>'刪除素材','middleware'=>'activated', 'as' => 'wechat.material.delete', 'uses'=>'Material@deleteMaterial']);
        $api->get('/wechat/material', [ 'name'=>'獲取永久素材列表','middleware'=>'activated', 'as' => 'wechat.material.list', 'uses'=>'Material@getMaterialLists']);
        $api->get('/wechat/material/stats', ['name'=>'獲取素材狀態', 'middleware'=>'activated', 'as' => 'wechat.material.stats', 'uses'=>'Material@getMaterialStats']);

        $api->post('/wechat/news', [ 'name'=>'創建圖文素材', 'middleware'=>'activated', 'as' => 'wechat.material.news.add', 'uses'=>'Material@createNews']);
        $api->get('/wechat/news/{materialId}', ['name'=>'獲取圖文素材詳情', 'middleware'=>'activated', 'as' => 'wechat.material.news.get', 'uses'=>'Material@getNewsMaterial']);
        $api->put('/wechat/news/', [ 'name'=>'修改圖文素材','middleware'=>'activated', 'as' => 'wechat.material.news.update', 'uses'=>'Material@updateArticle']);

        $api->get('/wechat/stats/userweeksummary', ['name'=>'最近七天用戶數據統', 'middleware'=>'activated', 'as' => 'wechat.stats.userWeekSummary', 'uses'=>'Stats@userWeekSummary']);

        $api->post('/wechat/open', [ 'name'=>'開通開放平臺賬號並且綁定小程序','middleware'=>'activated', 'as' => 'wechat.user.open', 'uses'=>'Open@openCreate']);
        $api->get('/wechat/offiaccountcodeforever', [ 'name'=>'獲取公眾號永久二維碼','middleware'=>'activated',  'as' => 'wechat.wxa.codeunlimit',  'uses'=>'Wxa@getOffiaccountCodeForever']);
    });

    $api->group(['namespace' => 'WorkWechatBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/workwechat/config', ['name'=>'獲取企業微信配置','middleware'=>'activated',  'as' => 'workwechat.config.info',  'uses'=>'WorkWechat@getConfig']);
        $api->get('/workwechat/report', ['name'=>'獲取企業微信通訊錄','middleware'=>'activated',  'as' => 'workwechat.report.info',  'uses'=>'WorkWechat@getReport']);
        $api->get('/workwechat/report/{department_id}', ['name'=>'獲取企業微信部門成員列表','middleware'=>'activated',  'as' => 'workwechat.report.userlists',  'uses'=>'WorkWechat@getReportUserLists']);
        $api->post('/workwechat/config', [ 'name'=>'保存企業微信配置', 'middleware'=>'activated', 'as' => 'workwechat.config.save', 'uses'=>'WorkWechat@setConfig']);
        $api->post('/workwechat/report/syncDistributor', [ 'name'=>'同步企微部門信息到店鋪', 'middleware'=>'activated', 'as' => 'workwechat.report.distributor.sync', 'uses'=>'WorkWechat@syncDistributor']);
        $api->post('/workwechat/report/syncSalesperson', [ 'name'=>'同步企微部門成員到導購員', 'middleware'=>'activated', 'as' => 'workwechat.report.salesperson.sync', 'uses'=>'WorkWechat@syncSalesperson']);
        $api->get('/workwechat/rellist/{salespersonId}', [ 'name'=>'導購員企業微信關聯信息', 'middleware'=>'activated', 'as' => 'workwechat.rel.list', 'uses'=>'WorkWechat@getWorkWechatList']);
        $api->get('/workwechat/rellogs/{userId}', [ 'name'=>'導購員企業微信關聯信息日誌', 'middleware'=>'activated', 'as' => 'workwechat.rel.log', 'uses'=>'WorkWechat@getWorkWechatLogsList']);
        $api->get('/workwechat/messagetemplate', ['name'=>'企業微信通知模板列表獲取','middleware'=>'activated',  'as' => 'workwechat.message.template.list',  'uses'=>'WorkWechatMessageTemplate@getTemplateList']);
        $api->get('/workwechat/messagetemplate/{templateId}', ['name'=>'企業微信通知模板獲取','middleware'=>'activated',  'as' => 'workwechat.message.template.get',  'uses'=>'WorkWechatMessageTemplate@getTemplate']);
        $api->put('/workwechat/messagetemplate/{templateId}', ['name'=>'企業微信通知模板保存','middleware'=>'activated',  'as' => 'workwechat.message.template.save',  'uses'=>'WorkWechatMessageTemplate@saveTemplate']);
        $api->put('/workwechat/messagetemplate/open/{templateId}', ['name'=>'企業微信通知模板保存','middleware'=>'activated',  'as' => 'workwechat.message.template.open',  'uses'=>'WorkWechatMessageTemplate@openTemplate']);
        $api->put('/workwechat/messagetemplate/close/{templateId}', ['name'=>'企業微信通知模板保存','middleware'=>'activated',  'as' => 'workwechat.message.template.close',  'uses'=>'WorkWechatMessageTemplate@closeTemplate']);

        $api->post('/workwechat/distributor/js/config', [ 'name'=>'前臺獲取JsSDK', 'middleware'=>'activated', 'as' => 'workwechat.distributor.js.config', 'uses'=>'WorkWechat@getDistributorJsConfig']);
        $api->post('/workwechat/domain/verify', [ 'name'=>'上傳企業微信校驗域名文件', 'middleware'=>'activated', 'as' => 'workwechat.domain.verify', 'uses'=>'WorkWechat@verifyDomain']);
    });

    $api->group(['namespace' => 'WorkWechatBundle\Http\Controllers'], function($api) {
        $api->any('/workwechat/notify/{corpid}', ['name'=>'企業微信回調','middleware'=>'activated',  'as' => 'workwechat.notify',  'uses'=>'WorkWechatCallback@notify']);
        $api->any('/workwechat/customer/notify/{corpid}', ['name'=>'企業微信客戶聯系回調','middleware'=>'activated',  'as' => 'workwechat.customer.notify',  'uses'=>'WorkWechatCallback@customerNotify']);
        $api->any('/workwechat/report/notify/{corpid}', ['name'=>'企業微信通訊錄回調','middleware'=>'activated',  'as' => 'workwechat.report.notify',  'uses'=>'WorkWechatCallback@reportNotify']);
    });
});

