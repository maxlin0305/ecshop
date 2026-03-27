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
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => 'token'], function($api) {
        $api->get('/operator/credential', ['name' => '登陸獲取證書','as' => 'operator.credentials', 'uses' => 'Operators@getCredentials']);
        $api->get('/operator/basic', ['name'=>'獲取賬號基本信息','as' => 'operator.basic', 'uses' => 'Operators@getBasicUserById']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        $api->get('/operator/images/code', ['name' =>'獲取圖片驗證碼','as' => 'operator.images.code', 'uses' => 'Operators@getImageVcode']);
        $api->post('/operator/sms/code', ['name' => '獲取手機短信驗證碼','as' => 'operator.images.code', 'uses' => 'Operators@getSmsCode']);
        $api->post('/operator/resetpassword', ['name' => '重置密碼','as' => 'operator.reset.password', 'uses' => 'Operators@resetPassword']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action'], function($api) {
        // app相關接口
        $api->get('/operator/app/image/code', ['name' => '獲取圖片驗證碼','as' => 'operator.app.image.code', 'uses' => 'Operators@getAppImageVcode']);
        $api->post('/operator/app/sms/code', ['name' => '發送手機短信驗證碼','as' => 'operator.app.sms.code', 'uses' => 'Operators@getAppSmsCode']);
    });

    // 企業相關信息
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/company/activate', ['name' => '系統激活', 'as' => 'company.active', 'uses'=>'Companys@active']);
        $api->get('/company/activate', ['name' => '獲取激活信息', 'as' => 'company.activate.info', 'uses'=>'Companys@getActivateInfo']);
        $api->get('/company/applications', ['name' => '獲取授權應用', 'as' => 'company.application.list', 'uses'=>'Companys@getApplications']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/company/resources', ['name' => '獲取當前可用資源包列表', 'as' => 'company.resources', 'uses'=>'Companys@getResourceList']);
        $api->get('/companys/setting', ['name' => '獲取商品配置信息', 'as' => 'companys.setting', 'uses'=>'Companys@getCompanySetting']);

        $api->get('/company/domain_setting', ['name' => '獲取域名配置信息', 'as' => 'companys.domain_setting.get', 'uses'=>'Companys@getDomainSetting']);
        $api->post('/company/domain_setting', ['name' => '保存域名配置信息', 'as' => 'companys.domain_setting.set', 'uses'=>'Companys@setDomainSetting']);

        $api->post('/company/setting', ['name' => '設置當前企業的基礎設置', 'as' => 'company.setting.set', 'uses'=>'Setting@setSetting']);
        $api->get('/company/setting', ['name' => '獲取當前企業的基礎設置', 'as' => 'company.setting.get', 'uses'=>'Setting@getSetting']);
        $api->post('/share/setting', ['name' => '設置分享設置', 'as' => 'share.setting.set', 'uses'=>'Setting@setShareSetting']);
        $api->get('/share/setting', ['name' => '獲取分享設置', 'as' => 'share.setting.get', 'uses'=>'Setting@getShareSetting']);
        $api->post('/setting/selfdelivery', ['name' => '配置固定的自提地址', 'as' => 'company.selfdelivery.set.address', 'uses'=>'Setting@setSelfdeliveryAddress']);
        $api->get('/setting/selfdelivery', ['name' => '配置固定的自提地址', 'as' => 'company.selfdelivery.get.address', 'uses'=>'Setting@getSelfdeliveryAddress']);

        $api->get('/company/operatorlogs', ['name' => '操作日誌', 'as' => 'company.get.operatorlogs', 'uses'=>'Operators@getCompanysLogs']);
        $api->get('/company/pushlogs', ['name' => '推送日誌', 'as' => 'company.get.pushlogs', 'uses'=>'PushLogs@getCompanysPushLogs']);
        $api->post('/company/pushlogs/push', ['name' => '推送日誌重推', 'as' => 'company.get.pushlogs', 'uses'=>'PushLogs@repush']);

        //修改當前登錄賬號的用戶名和頭像
        $api->put('/operator/updatedata', ["name" => '更改用戶名和頭像','as' => 'operator.update.data', 'uses' => 'Operators@updateUserData']);
        $api->post('/operator/select/distributor', ['name'=>'店鋪端選擇店鋪', 'as' => 'operator.select.distributor', 'uses' => 'Operators@shopLoginSelectShopId']);
        $api->put('/operator/changestatus', ['name'=>'修改賬號狀態', 'as' => 'operator.status.change', 'uses' => 'Operators@changeOperatorStatus']);

        //配置外部鏈接
        $api->post('/setting/weburl', ['name' => '配置外部鏈接', 'as' => 'company.setting.weburl.set', 'uses'=>'Setting@saveWebUrlSetting']);
        $api->get('/setting/weburl', ['name' => '獲取配置外部鏈接', 'as' => 'company.setting.weburl.get', 'uses'=>'Setting@getWebUrlSetting']);

        $api->get('/traderate/setting', [ 'name'=>'設置評價狀態','middleware'=>'activated', 'as' => 'trade.rate.setting.get', 'uses'=>'Setting@rateSetting']);
        $api->post('/traderate/setting', [ 'name'=>'設置評價狀態','middleware'=>'activated', 'as' => 'trade.rate.setting.set', 'uses'=>'Setting@rateSetting']);

        $api->get('/member/whitelist/setting', [ 'name'=>'獲取白名單設置狀態','middleware'=>'activated', 'as' => 'member.whitelist.setting.get', 'uses'=>'Setting@whitelistSetting']);
        $api->post('/member/whitelist/setting', [ 'name'=>'設置白名單狀態','middleware'=>'activated', 'as' => 'member.whitelist.setting.set', 'uses'=>'Setting@whitelistSetting']);

        // 預售提貨碼 開啟
        $api->get('/pickupcode/setting', [ 'name'=>'設置預售提貨碼狀態','middleware'=>'activated', 'as' => 'presale.pickupcode.setting.get', 'uses'=>'Setting@pickupcodeSetting']);
        $api->post('/pickupcode/setting', [ 'name'=>'設置預售提貨碼狀態','middleware'=>'activated', 'as' => 'presale.pickupcode.setting.set', 'uses'=>'Setting@pickupcodeSetting']);

        $api->post('/ydleads/create', [ 'name'=>'雲店留資創建','middleware'=>'activated', 'as' => 'companys.ydleads.create', 'uses'=>'Operators@createYdleads']);

        $api->get('/gift/setting', [ 'name'=>'贈品相關設置','middleware'=>'activated', 'as' => 'trade.gift.setting.get', 'uses'=>'Setting@getGiftSetting']);
        $api->post('/gift/setting', [ 'name'=>'贈品相關設置','middleware'=>'activated', 'as' => 'trade.gift.setting.set', 'uses'=>'Setting@setGiftSetting']);
        $api->get('/sendoms/setting', [ 'name'=>'推oms相關設置','middleware'=>'activated', 'as' => 'trade.sendoms.setting.get', 'uses'=>'Setting@getSendOmsSetting']);
        $api->post('/sendoms/setting', [ 'name'=>'推oms相關設置','middleware'=>'activated', 'as' => 'trade.sendoms.setting.set', 'uses'=>'Setting@setSendOmsSetting']);

        // 用於關閉前端店鋪切換功能
        $api->get('/nostores/setting', [ 'name'=>'獲取前端店鋪展示開關','middleware'=>'activated', 'as' => 'nostores.setting.get', 'uses'=>'Setting@getNostoresSetting']);
        $api->post('/nostores/setting', [ 'name'=>'設置前端店鋪展示開關','middleware'=>'activated', 'as' => 'nostores.setting.set', 'uses'=>'Setting@setNostoresSetting']);

        // 儲值功能 開關
        $api->get('/recharge/setting', [ 'name'=>'設置儲值功能狀態','middleware'=>'activated', 'as' => 'presale.recharge.setting.get', 'uses'=>'Setting@rechargeSetting']);
        $api->post('/recharge/setting', [ 'name'=>'設置儲值功能狀態','middleware'=>'activated', 'as' => 'presale.recharge.setting.set', 'uses'=>'Setting@rechargeSetting']);

        // 商品詳情庫存顯示 開關
        $api->get('/itemStore/setting', [ 'name'=>'獲取商品庫存顯示狀態','middleware'=>'activated', 'as' => 'item.store.setting.get', 'uses'=>'Setting@itemStoreSetting']);
        $api->post('/itemStore/setting', [ 'name'=>'設置商品庫存顯示狀態','middleware'=>'activated', 'as' => 'item.store.setting.set', 'uses'=>'Setting@itemStoreSetting']);

        // 商品銷量顯示 開關
        $api->get('/itemSales/setting', [ 'name'=>'獲取商品銷量顯示狀態','middleware'=>'activated', 'as' => 'item.store.setting.get', 'uses'=>'Setting@itemSalesSetting']);
        $api->post('/itemSales/setting', [ 'name'=>'設置商品銷量顯示狀態','middleware'=>'activated', 'as' => 'item.store.setting.set', 'uses'=>'Setting@itemSalesSetting']);

        // 結算頁發票選項 開關
        $api->get('/invoice/setting', [ 'name'=>'獲取發票選項顯示狀態','middleware'=>'activated', 'as' => 'item.store.setting.get', 'uses'=>'Setting@invoiceSetting']);
        $api->post('/invoice/setting', [ 'name'=>'設置發票選項顯示狀態','middleware'=>'activated', 'as' => 'item.store.setting.set', 'uses'=>'Setting@invoiceSetting']);

        // 商品分享設置
        $api->get('/itemshare/setting', [ 'name'=>'獲取商品分享設置','middleware'=>'activated', 'as' => 'item.share.setting.get', 'uses'=>'Setting@getItemShareSetting']);
        $api->post('/itemshare/setting', [ 'name'=>'保存商品分享設置','middleware'=>'activated', 'as' => 'item.share.setting.save', 'uses'=>'Setting@saveItemShareSetting']);

        // 小程序分享參數設置
        $api->get('/shareParameters/setting', [ 'name'=>'獲取小程序分享參數設置','middleware'=>'activated', 'as' => 'share.parameters.setting.get', 'uses'=>'Setting@getShareParametersSetting']);
        $api->post('/shareParameters/setting', [ 'name'=>'保存小程序分享參數設置','middleware'=>'activated', 'as' => 'share.parameters.setting.save', 'uses'=>'Setting@saveShareParametersSetting']);

        // 店務端設置
        $api->get('/dianwu/setting', [ 'name'=>'獲取店務端設置','middleware'=>'activated', 'as' => 'item.dianwu.setting.get', 'uses'=>'Setting@getDianwuSetting']);
        $api->post('/dianwu/setting', [ 'name'=>'保存店務端設置','middleware'=>'activated', 'as' => 'item.dianwu.setting.save', 'uses'=>'Setting@saveDianwuSetting']);

        // 商品價格顯示 開關
        $api->get('/itemPrice/setting', [ 'name'=>'獲取商品價格顯示配置','middleware'=>'activated', 'as' => 'item.price.setting.get', 'uses'=>'Setting@getItemPriceSetting']);
        $api->post('/itemPrice/setting', [ 'name'=>'保存商品價格顯示配置','middleware'=>'activated', 'as' => 'item.price.setting.set', 'uses'=>'Setting@saveItemPriceSetting']);
        //全部設置
        $api->get('/settings', [ 'name'=>'獲取全部配置配置','middleware'=>'activated', 'as' => 'all.setting.get', 'uses'=>'Setting@getAllSetting']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/shops/wxshops', ['name' => '添加微信門店', 'as' => 'shops.create', 'uses'=>'Shops@createWxShops']);

        $api->get('/shops/wxshops/sync', ['name' => '同步微信門店到本地', 'as' => 'shops.sync', 'uses'=>'Shops@syncWxShops']);
        $api->get('/shops/wxshops/setting', ['name' => '配置門店通用配置信息', 'as' => 'shops.setting.get', 'uses'=>'Shops@getWxShopsSetting']);
        $api->put('/shops/wxshops/setting', ['name' => '獲取門店通用配置信息', 'as' => 'shops.setting.set', 'uses'=>'Shops@setWxShopsSetting']);

        $api->get('/shops/wxshops', ['name' => '獲取微信門店列表', 'as' => 'shops.lists', 'uses'=>'Shops@getWxShopsList']);
        $api->post('/shops/wxshops/setDefaultShop', ['name' => '設置默認門店', 'as' => 'shops.defaultshop.set', 'uses'=>'Shops@setDefaultShop']);
        $api->post('/shops/wxshops/setShopResource', ['name' => '激活門店', 'as' => 'shops.shopresource.set', 'uses'=>'Shops@setResource']);
        $api->get('/shops/wxshops/{wx_shop_id}', ['name' => '獲取單個微信門店詳情', 'as' => 'shops.detail', 'uses'=>'Shops@getWxShopsDetail']);
        $api->delete('/shops/wxshops/{wx_shop_id}', ['name' => '刪除微信門店', 'as' => 'shops.delete', 'uses'=>'Shops@deleteWxShops']);
        $api->put('/shops/wxshops/{wx_shop_id}', ['name' => '更新微信門店', 'as' => 'shops.update', 'uses'=>'Shops@updateWxShops']);

        $api->post('/shops/wxshops/setShopStatus', ['name' => '設置門店狀態', 'as' => 'shops.status.set', 'uses'=>'Shops@setShopStatus']);

        $api->patch('/company', ['name' => '更新企業信息', 'as' => 'company.update', 'uses'=>'Companys@updateCompanyInfo']);
        $api->put('/shops/protocol', ['name' => '更新協議信息', 'as' => 'shops.protocol.put', 'uses'=>'ProtocolController@set']);
        $api->get('/shops/protocol', ['name' => '獲取協議信息', 'as' => 'shops.protocol.get', 'uses'=>'ProtocolController@get']);
    });

    //文章相關接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/article/management', ['name' => '創建文章', 'as' => 'article.create', 'uses'=>'ArticleController@createDataArticle']);
        $api->put('/article/management/{article_id}', ['name' => '更新文章', 'as' => 'article.update', 'uses'=>'ArticleController@updateDataArticle']);
        $api->delete('/article/management/{article_id}', ['name' => '刪除文章', 'as' => 'article.delete', 'uses'=>'ArticleController@deleteDataArticle']);
        $api->get('/article/management', ['name' => '獲取文章列表', 'as' => 'article.list', 'uses'=>'ArticleController@listDataArticle']);
        $api->get('/article/management/{article_id}', ['name' => '獲取文章詳情', 'as' => 'article.info', 'uses'=>'ArticleController@infoDataArticle']);
        $api->put('/article/updatestatusorsort', ['name' => '獲取文章詳情', 'as' => 'article.update.sortstatus', 'uses'=>'ArticleController@updateArticleStatusOrSort']);

        $api->post('/article/category', ['name' => '創建文章欄目', 'as' => 'article.create.category', 'uses'=>'ArticleCategory@createData']);
        $api->get('/article/category', ['name' => '獲取文章欄目列表', 'as' => 'article.list.category', 'uses'=>'ArticleCategory@getCategory']);
        $api->get('/article/category/{category_id}', ['name' => '獲取單條文章欄目', 'as' => 'article.info.category', 'uses'=>'ArticleCategory@getCategory']);
        $api->put('/article/category/{category_id}', ['name' => '更新單條文章欄目', 'as' => 'article.update.category', 'uses'=>'ArticleCategory@updateCategory']);
        $api->delete('/article/category/{category_id}', ['name' => '刪除文章欄目', 'as' => 'article.delete.category', 'uses'=>'ArticleCategory@deleteCategory']);
    });

    //貨幣相關接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/currency', ['name' => '貨幣信息新增', 'as' => 'currency.create', 'uses'=>'CurrencyController@createData']);
        $api->delete('/currency/{id}', ['name' => '刪除企業員工', 'as' => 'currency.delete', 'uses'=>'CurrencyController@deleteData']);
        $api->put('/currency/{id}', ['name' => '更新企業員工', 'as' => 'currency.update', 'uses'=>'CurrencyController@updateData']);
        $api->get('/currency/{id}', ['name' => '獲取貨幣詳情', 'as' => 'currency.info', 'uses'=>'CurrencyController@getDataInfo']);
        $api->get('/currency', ['name' => '獲取貨幣列表信息', 'as' => 'currency.list', 'uses'=>'CurrencyController@getDataList']);
        $api->put('/currencySetDefault/{id}', ['name' => '設置默認貨幣', 'as' => 'currency.set.default', 'uses'=>'CurrencyController@setDefaultCurrency']);
        $api->get('/currencyGetDefault', ['name' => '獲取默認貨幣配置', 'as' => 'currency.get.default', 'uses'=>'CurrencyController@getDefaultCurrency']);
    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/account/management', ['name' => '創建企業員工', 'as' => 'account.create', 'uses'=>'EmployeeController@createData']);
        $api->get('/account/management', ['name' => '獲取企業員工信息列表', 'middleware' => ['datapass'], 'as' => 'account.list', 'uses'=>'EmployeeController@getListData']);
        $api->get('/account/management/{operator_id}', ['name' => '獲取企業員工信息', 'as' => 'account.info', 'uses'=>'EmployeeController@getInfoData']);
        $api->patch('/account/management/{operator_id}', ['name' => '更改企業員工信息', 'as' => 'account.update', 'uses'=>'EmployeeController@updateData']);
        $api->delete('/account/management/{operator_id}', ['name' => '刪除企業員工信息', 'as' => 'account.delete', 'uses'=>'EmployeeController@deleteData']);

        $api->post('/roles/management', ['name' => '創建企業員工角色', 'as' => 'roles.create', 'uses'=>'RolesController@createDataRole']);
        $api->get('/roles/management', ['name' => '獲取角色列表', 'as' => 'roles.list', 'uses'=>'RolesController@getDataList']);
        $api->get('/roles/management/{role_id}', ['name' => '獲取角色詳情', 'as' => 'roles.info', 'uses'=>'RolesController@getDataInfo']);
        $api->patch('/roles/management/{role_id}', ['name' => '更新企業員工角色', 'as' => 'roles.update', 'uses'=>'RolesController@updateDataRole']);
        $api->delete('/roles/management/{role_id}', ['name' => '刪除角色', 'as' => 'roles.delete', 'uses'=>'RolesController@deleteDataRole']);


    });

    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/permission', ['name' => '獲取權限詳情', 'as' => 'account.roles.permission', 'uses'=>'RolesController@getPermission']);
        $api->get('/operator/getinfo', ['as' => 'operator.get.data', 'uses' => 'Operators@getUserData']);
    });


    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/getStatistics', ['name' => '獲取商城訂單統計信息', 'as' => 'company.real.statistics', 'uses'=>'StatisticsController@getDataList']);
        $api->get('/getNoticeStatistics', ['name' => '獲取商城總量統計(待處理訂單數，待處理商品數，進行中的營銷活動數)', 'as' => 'company.notice.statistics', 'uses'=>'StatisticsController@getOrderStatusCount']);
    });

    // 地區管理
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/regionauth', ['name' => '地區權限列表', 'as' => 'company.regionauth.list', 'uses'=>'Regionauth@getlist']);
        $api->get('/regionauth/{id}', ['name' => '地區權限詳情', 'as' => 'company.regionauth.info', 'uses'=>'Regionauth@getinfo']);
        $api->post('/regionauth', ['name' => '地區權限添加', 'as' => 'company.regionauth.add', 'uses'=>'Regionauth@add']);
        $api->put('/regionauth/{id}', ['name' => '地區權限修改', 'as' => 'company.regionauth.update', 'uses'=>'Regionauth@update']);
        $api->delete('/regionauth/{id}', ['name' => '地區權限刪除', 'as' => 'company.regionauth.dell', 'uses'=>'Regionauth@del']);
        $api->put('/regionauth/enable/{id}', ['name' => '狀態操作', 'as' => 'company.regionauth.enable', 'uses'=>'Regionauth@enable']);
    });

    //外部小程序配置相關接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->get('/wxexternalconfig/list', ['name' => '獲取外部小程序配置列表', 'as' => 'wxexternalconfig.list', 'uses'=>'WxExternalConfigController@getWxExternalConfigList']);
        $api->post('/wxexternalconfig/create', ['name' => '創建外部小程序配置', 'as' => 'wxexternalconfig.create', 'uses'=>'WxExternalConfigController@createWxExternalConfig']);
        $api->put('/wxexternalconfig/update/{wx_external_config_id}', ['name' => '更新外部小程序配置', 'as' => 'wxexternalconfig.update', 'uses'=>'WxExternalConfigController@updateWxExternalConfig']);

        $api->delete('/wxexternalconfig/{wx_external_config_id}', ['name' => '刪除外部小程序配置', 'as' => 'wxexternalconfig.delete', 'uses'=>'WxExternalConfigController@deleteWxExternalConfig']);

        $api->get('/wxexternalroutes/list', ['name' => '獲取外部小程序路徑列表', 'as' => 'wxexternalroutes.list', 'uses'=>'WxExternalRoutesController@getwxexternalroutesList']);
        $api->post('/wxexternalroutes/create', ['name' => '創建外部小程序路徑', 'as' => 'wxexternalroutes.create', 'uses'=>'WxExternalRoutesController@createwxexternalroutes']);
        $api->put('/wxexternalroutes/update/{wx_external_config_id}', ['name' => '更新外部小程序路徑', 'as' => 'wxexternalroutes.update', 'uses'=>'WxExternalRoutesController@updatewxexternalroutes']);
        $api->delete('/wxexternalroutes/{wx_external_config_id}', ['name' => '刪除外部小程序路徑', 'as' => 'wxexternalroutes.delete', 'uses'=>'WxExternalRoutesController@deleteWxExternalRoutes']);
        $api->get('/wxexternalconfigroutes/list', ['name' => '獲取外部小程序配置路徑列表', 'as' => 'wxexternalconfigroutes.list', 'uses'=>'WxExternalConfigController@getConfigRoutesList']);
    });

    // 數據敏感權限相關接口
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/datapass', ['name' => '申請查看敏感數據', 'as' => 'companys.datapass.apply', 'uses' => 'Operators@applyDataPass']);
        $api->put('/datapass/apply/{id}', ['name' => '敏感數據申請審核', 'as' => 'companys.datapass.approve', 'uses' => 'Operators@approveDataPass']);
        $api->put('/datapass/open/{id}', ['name' => '敏感數據申請開啟', 'as' => 'companys.datapass.approve', 'uses' => 'Operators@approveDataPass']);
        $api->put('/datapass/close/{id}', ['name' => '敏感數據申請關閉', 'as' => 'companys.datapass.approve', 'uses' => 'Operators@approveDataPass']);

        $api->get('/datapass', ['name' => '查看敏感數據申請列表', 'as' => 'companys.datapass.list', 'uses' => 'Operators@listDataPass']);
        $api->get('/datapass/{id}', ['name' => '查看敏感數據申請詳情', 'as' => 'companys.datapass.detail', 'uses' => 'Operators@fetchDataPassDetail']);
        $api->get('/datapasslog', ['name' => '查看敏感數據日誌', 'as' => 'companys.datapass.log.list', 'uses' => 'Operators@listDataPassLog']);
    });

    // 移動收銀相關
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated'], 'providers' => 'jwt'], function($api) {
        $api->post('/operator/scancodeAddcart', ['name' => '掃條形碼加入購物車', 'as' => 'companys.operator.cartdata.scanadd', 'uses' => 'OperatorCartController@scanCodeSales']);
        $api->post('/operator/cartdataadd', ['name' => '管理員購物車新增', 'as' => 'companys.operator.cartdata.add', 'uses' => 'OperatorCartController@cartDataAdd']);
        $api->post('/operator/cartdataupdate', ['name' => '管理員購物車更新', 'as' => 'companys.operator.cartdata.update', 'uses' => 'OperatorCartController@updateCartData']);
        $api->get('/operator/cartdatalist', ['name' => '獲取管理員購物車', 'as' => 'companys.operator.cartdata.list', 'uses' => 'OperatorCartController@getCartDataList']);
        $api->delete('/operator/cartdatadel', ['name' => '管理員購物車刪除', 'as' => 'companys.operator.cartdata.del', 'uses' => 'OperatorCartController@delCartData']);
        $api->get('/operator/pending/list', ['name' => '管理員掛單列表', 'as' => 'companys.operator.pending.list', 'uses' => 'OperatorPendingOrderController@listPendingData']);
        $api->post('/operator/cartdata/pending', ['name' => '管理員購物車掛單', 'as' => 'companys.operator.cartdata.pending', 'uses' => 'OperatorPendingOrderController@pendingCartData']);
        $api->post('/operator/order/pending', ['name' => '管理員待支付訂單掛單', 'as' => 'companys.operator.order.pending', 'uses' => 'OperatorPendingOrderController@pendingOrderData']);
        $api->post('/operator/pending/fetch', ['name' => '管理員取單', 'as' => 'companys.operator.pending.fetch', 'uses' => 'OperatorPendingOrderController@fetchPendingData']);
        $api->delete('/operator/pending/delete', ['name' => '管理員掛單數據刪除', 'as' => 'companys.operator.pending.delete', 'uses' => 'OperatorPendingOrderController@delPendingData']);
    });

    // 消息場景管理
    $api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => [], 'providers' => []], function($api) {
        #$api->group(['namespace' => 'CompanysBundle\Http\Api\V1\Action', 'middleware' => [], 'providers' => []], function($api) {

        // 獲取消息類型
        $api->get('/operator/pushmessagetype', ['name' => '到貨通知消息列表', 'as' => 'companys.operator.cartdata.list', 'uses' => 'Operators@getPushMessageTypeList']);

        // 是否開啟到貨通知
        $api->get('/operator/pushmessagestatus', ['name' => '是否開啟到貨通知', 'as' => 'companys.operator.pushmessage.get', 'uses' => 'Operators@getPushMessageStatus']);
        $api->post('/operator/pushmessagestatus', ['name' => '是否開啟到貨通知', 'as' => 'companys.operator.pushmessage.status', 'uses' => 'Operators@pushMessageStatus']);

        // 消息列表數據
        $api->get('/operator/pushmessagelist', ['name' => '到貨通知消息列表', 'as' => 'companys.operator.cartdata.list', 'uses' => 'Operators@getPushMessageList']);

    });
});
