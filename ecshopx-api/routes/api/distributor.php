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
    $api->group(['namespace' => 'DistributionBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/distributor',                 ['name' => '創建店鋪','middleware'=>'activated',  'as' => 'distributor.create',  'uses'=>'Distributor@createDistributor']);
        $api->get('/distributors',                 ['name' => '獲取店鋪列表','middleware'=>['activated', 'datapass'],  'as' => 'distributor.list',    'uses'=>'Distributor@getDistributorList']);
        $api->get('/distributors/info',            ['name' => '獲取指定店鋪信息','middleware'=>['activated','datapass'],  'as' => 'distributor.info',    'uses'=>'Distributor@getDistributorInfo']);
        $api->put('/distributor/{distributor_id}', ['name' => '更新店鋪','middleware'=>'activated',  'as' => 'distributor.edit',    'uses'=>'Distributor@updateDistributor']);
        $api->get('/distributor/count/{distributorId}', ['name' => '獲取店鋪統計','middleware'=>'activated', 'as' => 'front.wxapp.distributor.count', 'uses'=>'Distributor@getDistributorCount']);
        $api->get('/distributor/wxacode',  ['name' => '獲取店鋪小程序碼','middleware'=>'activated', 'as' => 'distributor.wxacode',           'uses'=>'Distributor@getWxaDistributorCodeStream']);

        $api->get('/distributor/easylist',                 ['name' => '獲取店鋪簡易列表','middleware'=>'activated',  'as' => 'distributor.easy.list',    'uses'=>'Distributor@getEasyList']);

        $api->post('/distributor/items',  ['name' => '添加店鋪關聯店鋪商品','middleware'=>'activated',  'as' => 'distributor.item.create',  'uses'=>'Distributor@saveDistributorItems']);
        $api->get('/distributor/items',   ['name' => '獲取店鋪關聯商品列表','middleware'=>'activated',  'as' => 'distributor.item.list',  'uses'=>'Distributor@getDistributorItems']);
        $api->get('/distributor/items/export',   ['name' => '導出店鋪關聯商品列表','middleware'=>'activated',  'as' => 'distributor.item.exportlist',  'uses'=>'Distributor@exportDistributorItems']);
        $api->delete('/distributor/items', ['name' => '刪除店鋪關聯商品列','middleware'=>'activated',  'as' => 'distributor.item.delete',  'uses'=>'Distributor@delDistributorItems']);
        $api->put('/distributors/item',  ['name' => '配置店鋪價格或庫存','middleware'=>'activated',  'as' => 'distributor.item.update',  'uses'=>'Distributor@updateDistributorItem']);

        $api->post('/distribution/basic_config', ['name' => '保存分銷基礎配置','middleware'=>'activated',  'as' => 'distribution.basic_config.save',  'uses'=>'BasicConfig@saveBasicConfig']);
        $api->get('/distribution/basic_config',  ['name' => '獲取分銷基礎配置','middleware'=>'activated',  'as' => 'distributor.basic_config.get',    'uses'=>'BasicConfig@getBasicConfig']);

        $api->get('/distribution/cash_withdrawals',  ['name' => '獲取傭金提現列表','middleware'=>'activated',  'as' => 'distribution.cash_withdrawal.list',    'uses'=>'CashWithdrawal@getCashWithdrawalList']);
        $api->put('/distribution/cash_withdrawal/{id}',  ['name' => '處理傭金提現申請','middleware'=>'activated',  'as' => 'distribution.cash_withdrawal.process',    'uses'=>'CashWithdrawal@processCashWithdrawal']);
        $api->get('/distributor/cash_withdrawal/payinfo/{cash_withdrawal_id}', ['name' => '獲取傭金提現支付信息','middleware'=>'activated', 'as' => 'front.wxapp.cashWithdrawal.payinfo',  'uses'=>'CashWithdrawal@getMerchantTradeList']);

        $api->get('/distribution/logs', ['name' => '獲取傭金記錄','middleware'=>'activated', 'as' => 'front.wxapp.distribution.log',  'uses'=>'DistributeLogs@getDistributeLogs']);
        $api->get('/distribution/count', ['name' => '獲取分銷統計','middleware'=>'activated', 'as' => 'front.wxapp.distribution.count',  'uses'=>'DistributeLogs@getCompanyCount']);

        //獲取經銷商可關聯的門店列表
        $api->get('/distributor/getShop', ['name' => '獲取有效的門店列表','middleware'=>'activated', 'as' => 'distributor.get.shop',  'uses'=>'Distributor@getValidShopList']);

        $api->post('/distributor/default', ['name' => '設置默認門店','middleware'=>'activated',  'as' => 'distributor.default.set',    'uses'=>'Distributor@defaultSetDistributor']);

        // 添加店鋪標簽
        $api->post('/distributor/tag', ['name'=> '添加店鋪標簽','middleware'=>'activated', 'as' => 'distributor.tag.add', 'uses' =>'DistributorTags@createTags']);
        // 刪除店鋪標簽
        $api->delete('/distributor/tag/{tagId}', ['name'=> '刪除店鋪標簽','middleware'=>'activated', 'as' => 'distributor.tag.delete', 'uses' =>'DistributorTags@deleteTag']);
        // 更新店鋪標簽
        $api->put('/distributor/tag/{tagId}', ['name'=> '更新店鋪標簽','middleware'=>'activated', 'as' => 'distributor.tag.update', 'uses' =>'DistributorTags@updateTags']);
        // 獲取店鋪標簽列表
        $api->get('/distributor/tag', ['name'=> '獲取店鋪標簽列表','middleware'=>'activated', 'as' => 'distributor.tag.list', 'uses' =>'DistributorTags@getTagsList']);
        // 獲取店鋪標簽詳情
        $api->get('/distributor/tag/{tagId}', ['name'=> '獲取店鋪標簽詳情','middleware'=>'activated', 'as' => 'distributor.tag.get', 'uses' =>'DistributorTags@getTagsInfo']);
        // 商品關聯標簽
        $api->post('/distributor/reltag', ['name'=> '店鋪關聯標簽','middleware'=>'activated', 'as' => 'distributor.tag.rel', 'uses' =>'DistributorTags@tagsRelDistributor']);
        // 店鋪與店鋪標簽做解綁
        $api->post('/distributor/deltag', ['name'=> '店鋪與店鋪標簽做解綁','middleware'=>'activated', 'as' => 'distributor.tag.del', 'uses' =>'DistributorTags@tagsRemoveDistributor']);

        //店鋪門店相關接口開始
        $api->post('/shops',                 ['name' => '創建店鋪門店','middleware'=>'activated',  'as' => 'shops.create',  'uses'=>'DistributorShopController@createShops']);
        $api->put('/shops/{distributor_id:[0-9]+}', ['name' => '更新店鋪門店','middleware'=>'activated',  'as' => 'shops.edit',    'uses'=>'DistributorShopController@updateShops']);
        $api->delete('/shops/{distributor_id:[0-9]+}', ['name' => '刪除店鋪門店','middleware'=>'activated', 'as' => 'shops.delete', 'uses'=>'DistributorShopController@deleteShops']);
        $api->get('/shops/{distributor_id:[0-9]+}',  ['name' => '獲取店鋪門店詳情','middleware'=>'activated', 'as' => 'shops.detail',           'uses'=>'DistributorShopController@getShopsDetail']);
        $api->get('/shops',                 ['name' => '獲取店鋪簡易列表','middleware'=>'activated',  'as' => 'shops.list',    'uses'=>'DistributorShopController@getShopsList']);

        $api->post('/dshops/setDefaultShop',                 ['name' => '設置默認店鋪門店','middleware'=>'activated',  'as' => 'shops.setdefault',    'uses'=>'DistributorShopController@setDefaultShop']);
        $api->post('/dshops/setShopStatus',            ['name' => '設置店鋪門店狀態','middleware'=>'activated',  'as' => 'shops.setstatus',    'uses'=>'DistributorShopController@setShopStatus']);
        //店鋪門店相關接口結束

        // 分潤相關配置
        $api->get('/distribution/config', ['name' => '獲取分潤配置', 'middleware'=>'activated', 'as' => 'distribution.config.get',    'uses'=>'DistributionConfig@getConfig']);
        $api->post('/distribution/config', ['name' => '保存分潤配置', 'middleware'=>'activated', 'as' => 'distribution.config.save',    'uses'=>'DistributionConfig@setConfig']);

        // 店鋪售後地址相關
        $api->post('/distributors/aftersalesaddress', ['name' => '添加店鋪售後地址', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.save',    'uses'=>'DistributorAftersalesAddress@setDistributorAfterSalesAddress']);
        $api->put('/distributors/aftersalesaddress', ['name' => '修改店鋪售後地址', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.update',    'uses'=>'DistributorAftersalesAddress@setDistributorAfterSalesAddress']);
        $api->delete('/distributors/aftersalesaddress/{address_id}', ['name' => '刪除店鋪售後地址', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.delete',    'uses'=>'DistributorAftersalesAddress@deleteDistributorAfterSalesAddress']);
        $api->get('/distributors/aftersalesaddress', ['name' => '獲取店鋪售後地址列表', 'middleware'=>['datapass'], 'as' => 'distribution.aftersalesaddress.list',    'uses'=>'DistributorAftersalesAddress@getDistributorAfterSalesAddress']);
        $api->get('/distributors/aftersalesaddress/{address_id}', ['name' => '獲取店鋪售後地址詳情', 'middleware'=>[], 'as' => 'distribution.aftersalesaddress.get',    'uses'=>'DistributorAftersalesAddress@getDistributorAfterSalesAddressDetail']);

        $api->get('/distributors/aftersales', ['name' => '獲取可退貨店鋪列表', 'middleware'=>'activated', 'as' => 'distributor.aftersales.list',    'uses'=>'Distributor@getOtherOfflineAftersalesDistributor']);

        $api->get('/distributor/salesperson/role', ['name' => '獲取門店角色列表', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.list',    'uses'=>'DistributorSalespersonRole@getRoleList']);
        $api->get('/distributor/salesperson/role/{salesmanRoleId}', ['name' => '獲取門店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.get',    'uses'=>'DistributorSalespersonRole@getRoleInfo']);
        $api->post('/distributor/salesperson/role', ['name' => '保存門店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.create',    'uses'=>'DistributorSalespersonRole@createRole']);
        $api->put('/distributor/salesperson/role/{salesmanRoleId}', ['name' => '修改門店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.update',    'uses'=>'DistributorSalespersonRole@updateRole']);
        $api->delete('/distributor/salesperson/role/{salesmanRoleId}', ['name' => '刪除門店角色', 'middleware'=> 'activated', 'as' => 'distribution.salesperson.role.delete',    'uses'=>'DistributorSalespersonRole@delRole']);

        $api->get('/distribution/getdistance', ['name' => '獲取距離配置', 'middleware'=>'activated', 'as' => 'distribution.distance.get',    'uses'=>'Distributor@getDistance']);
        $api->post('/distribution/setdistance', ['name' => '保存距離配置', 'middleware'=>'activated', 'as' => 'distribution.distance.save',    'uses'=>'Distributor@setDistance']);

        //大屏相關接口

        //開屏廣告
        $api->post('/shopScreen/advertisement', ['name' => '添加廣告', 'middleware'=>[], 'as' => 'distribution.advertisement.add',    'uses'=>'ShopScreen@addAdvertisement']);
        $api->delete('/shopScreen/advertisement/{id}', ['name' => '刪除廣告', 'middleware'=>[], 'as' => 'distribution.advertisement.del',    'uses'=>'ShopScreen@deleteAdvertisement']);
        $api->put('/shopScreen/advertisement', ['name' => '排序/發布/撤回', 'middleware'=>[], 'as' => 'distribution.advertisement.setsort', 'uses' => 'ShopScreen@updateAdvertisement']);
        $api->get('/shopScreen/advertisement', ['name' => '開屏廣告列表', 'middleware'=>[], 'as' => 'distribution.advertisement.getAdvertisements',    'uses'=>'ShopScreen@getAdvertisement']);

        //首頁輪播
        $api->post('/shopScreen/slider', ['name' => '輪播圖', 'middleware' => [], 'as' => 'distribution.shopScreen.saveSlider', 'uses' => 'ShopScreen@saveSlider']);
        $api->get('/shopScreen/slider', ['name' => '輪播圖', 'middleware' => [], 'as' => 'distribution.shopScreen.saveSlider', 'uses' => 'ShopScreen@getSlider']);

        // 店鋪圍欄
        $api->get('/distributor/geofence', ['name' => '獲取店鋪圍欄', 'as' => 'distribution.geofence.get', 'uses' => 'DistributorGeofenceController@get']);
        $api->post('/distributor/geofence', ['name' => '添加或更新一個店鋪圍欄', 'as' => 'distribution.geofence.create', 'uses' => 'DistributorGeofenceController@save']);
        $api->delete('/distributor/geofence', ['name' => '刪除一個店鋪圍欄, 或刪除該店鋪下的所有圍欄', 'as' => 'distribution.geofence.delete', 'uses' => 'DistributorGeofenceController@delete']);

        //自提點
        $api->get('/pickuplocation/list', ['name' => '獲取自提點列表', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.list.get', 'uses'=>'PickupLocation@getPickupLocationList']);
        $api->get('/pickuplocation/{id}', ['name' => '獲取自提點詳情', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.info.get', 'uses'=>'PickupLocation@getPickupLocationInfo']);
        $api->post('/pickuplocation', ['name' => '新增自提點', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.create', 'uses'=>'PickupLocation@createPickupLocation']);
        $api->put('/pickuplocation/{id}', ['name' => '更新自提點', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.update', 'uses'=>'PickupLocation@updatePickupLocation']);
        $api->delete('/pickuplocation/{id}', ['name' => '刪除自提點', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.delete', 'uses'=>'PickupLocation@delPickupLocation']);
        $api->post('/pickuplocation/reldistributor', ['name' => '自提點關聯門店', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.reldistributor', 'uses'=>'PickupLocation@relDistributor']);
        $api->post('/pickuplocation/reldistributor/cancel', ['name' => '自提點取消關聯門店', 'middleware'=>['activated', 'datapass'], 'as' => 'pickuplocation.reldistributor.cancel', 'uses'=>'PickupLocation@cancelRelDistributor']);
    });

    $api->group(['namespace' => 'SalespersonBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->get('/distributor/salesmans', ['name' => '獲取店鋪導購員列表', 'middleware' => 'activated', 'as' => 'distribution.salesman.list.get', 'uses' => 'SalespersonController@getSalesmanList']);
        $api->put('/distributor/salesman/{salesmanId}', ['name' => '更新店鋪導購員', 'middleware' => 'activated', 'as' => 'distribution.salesman.update', 'uses' => 'SalespersonController@updateSalesman']);
        $api->get('/distributor/salesman/role', ['name' => '獲取店鋪導購員權限集合', 'middleware' => 'activated', 'as' => 'distribution.salesman.role.list', 'uses' => 'SalespersonController@getSalesmanRoleList']);
        $api->put('/distributor/salesman/role/{salesmanId}', ['name' => '更新店鋪導購員權限', 'middleware' => 'activated', 'as' => 'distribution.salesman.role.update', 'uses' => 'SalespersonController@updateSalesmanRole']);
        $api->post('/distributor/salesman', ['name' => '新增店鋪導購員', 'middleware' => 'activated', 'as' => 'distribution.salesman.add', 'uses' => 'SalespersonController@addSalesman']);
        $api->get('/distributor/salemanCustomerComplaints', ['name' => '獲取店鋪導購員客訴列表', 'middleware' => 'activated', 'as' => 'distribution.salesmancustomercomplaints.list.get', 'uses' => 'SalespersonController@getSalemanCustomerComplaintsList']);
        $api->post('/distributor/salemanCustomerComplaints', ['name' => '回復店鋪導購員客訴列表', 'middleware' => 'activated', 'as' => 'distribution.salesmancustomercomplaints.reply', 'uses' => 'SalespersonController@replySalemanCustomerComplaints']);

        $api->post('/salespersonotice/notice', ['name' => '添加導購員通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.add', 'uses' => 'SalespersonNotice@addNotice']);
        $api->post('/salespersonotice/sendnotice', ['name' => '發送導購員通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.send', 'uses' => 'SalespersonNotice@sendNotice']);
        $api->post('/salespersonotice/withdrawnotice', ['name' => '撤回導購員通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.withdraw', 'uses' => 'SalespersonNotice@withdrawNotice']);
        $api->get('/salespersonotice/list', ['name' => '導購員通知列表', 'middleware' => 'activated', 'as' => 'salesperson.notice.list', 'uses' => 'SalespersonNotice@getNoticeList']);
        $api->get('/salespersonotice/detail', ['name' => '導購員通知詳情', 'middleware' => 'activated', 'as' => 'salesperson.notice.info', 'uses' => 'SalespersonNotice@getNoticeDetail']);
        $api->delete('/salespersonotice/notice', ['name' => '刪除導購員通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.delete', 'uses' => 'SalespersonNotice@deleteNotice']);
        $api->put('/salespersonotice/notice', ['name' => '修改導購員通知', 'middleware' => 'activated', 'as' => 'salesperson.notice.update', 'uses' => 'SalespersonNotice@updateNotice']);

        $api->get('/profit/statistics', ['name' => '獲取分潤統計', 'middleware' => 'activated', 'as' => 'profit.statistics.list.get', 'uses' => 'ProfitController@lists']);
        $api->get('/profit/export', ['name' => '導出分潤信息', 'middleware' => 'activated', 'as' => 'profit.export', 'uses' => 'ProfitController@exportProfitData']);

        $api->get('/salesperson/task', ['name' => '獲取導購任務列表', 'middleware' => 'activated', 'as' => 'salesperson.task.list', 'uses' => 'SalespersonTaskController@lists']);
        $api->get('/salesperson/task/statistics', ['name' => '獲取導購任務統計列表', 'middleware' => 'activated', 'as' => 'salesperson.task.statistics', 'uses' => 'SalespersonTaskController@statistics']);
        $api->get('/salesperson/task/{taskId}', ['name' => '獲取導購任務詳情', 'middleware' => 'activated', 'as' => 'salesperson.task.info', 'uses' => 'SalespersonTaskController@info']);
        $api->post('/salesperson/task', ['name' => '創建導購任務', 'middleware' => 'activated', 'as' => 'salesperson.task.create', 'uses' => 'SalespersonTaskController@create']);
        $api->put('/salesperson/task/{taskId}', ['name' => '修改導購任務', 'middleware' => 'activated', 'as' => 'salesperson.task.update', 'uses' => 'SalespersonTaskController@update']);
        $api->delete('/salesperson/task/{taskId}', ['name' => '取消導購任務', 'middleware' => 'activated', 'as' => 'salesperson.task.cancel', 'uses' => 'SalespersonTaskController@cancel']);

        $api->get('/salesperson/coupon', ['name' => '獲取導購優惠券列表', 'middleware' => 'activated', 'as' => 'salesperson.coupon.list', 'uses' => 'SalespersonCouponController@lists']);
        $api->post('/salesperson/coupon', ['name' => '添加導購可發放優惠券', 'middleware' => 'activated', 'as' => 'salesperson.coupon.create', 'uses' => 'SalespersonCouponController@create']);
        $api->delete('/salesperson/coupon/{id}', ['name' => '刪除導購優惠券', 'middleware' => 'activated', 'as' => 'salesperson.coupon.delete', 'uses' => 'SalespersonCouponController@delete']);

        //門店人員
        $api->post('/shops/salesperson', ['name' => '添加門店人員', 'as' => 'shop.salesperson.create', 'uses'=>'Salesperson@createSalesperson']);
        $api->get('/shops/salesperson', ['name' => '獲取所有門店人員列表', 'middleware' => ['datapass'], 'as' => 'shop.salesperson.lists', 'uses'=>'Salesperson@lists']);
        $api->delete('/shops/salesperson/{salespersonId}', ['name' => '刪除門店人員', 'as' => 'shop.salesperson.del', 'uses'=>'Salesperson@deleteSalesperson']);
        $api->put('/shops/salesperson/{salespersonId}', ['name' => '更新門店人員', 'as' => 'shop.salesperson.update', 'uses'=>'Salesperson@updateSalesperson']);

        $api->get('/shops/saleperson/shoplist', ['name' => '獲取管理員管理的門店數據', 'as' => 'shop.salesperson.update', 'uses'=>'Salesperson@getRelShopList']);
        $api->get('/shops/saleperson/getinfo', ['name' => '獲取門店人員詳細信息', 'middleware' => ['datapass'], 'as' => 'shop.salesperson.getinfo', 'uses'=>'Salesperson@getSalespersonInfo']);

        $api->get('/shops/saleperson/signlogs', ['name' => '獲取簽到記錄', 'as' => 'shop.salesperson.signlogs', 'uses'=>'Salesperson@getSignlogs']);
    });
});

