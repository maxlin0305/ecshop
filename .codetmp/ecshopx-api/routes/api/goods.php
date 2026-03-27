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
    // 服務類商品相關信息
    $api->group(['namespace' => 'GoodsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        // 數值屬性
        $api->post('/goods/servicelabels', ['name' => '添加數值屬性', 'as' => 'goods.servicelabels.create', 'uses' => 'ServiceLabels@createServiceLabels']);
        $api->get('/goods/servicelabels', ['name' => '獲取數值屬性列表', 'as' => 'goods.servicelabels.lists', 'uses' => 'ServiceLabels@getServiceLabelsList']);
        $api->get('/goods/servicelabels/{label_id}', ['name' => '獲取數值屬性詳情', 'as' => 'goods.servicelabels.detail', 'uses' => 'ServiceLabels@getServiceLabelsDetail']);
        $api->delete('/goods/servicelabels/{label_id}', ['name' => '刪除數值屬性', 'as' => 'goods.servicelabels.delete', 'uses' => 'ServiceLabels@deleteServiceLabels']);
        $api->put('/goods/servicelabels/{label_id}', ['name' => '更新數值屬性', 'as' => 'goods.servicelabels.update', 'uses' => 'ServiceLabels@updateServiceLabels']);

        // 商品
        $api->put('/goods/audit/items', ['name' => '商品審核', 'as' => 'goods.items.audit', 'uses' => 'Items@auditItems']);
        $api->post('/goods/items', ['name' => '添加商品', 'as' => 'goods.items.create', 'uses' => 'Items@createItems']);
        $api->post('/goods/setItemsTemplate', ['name' => '更新商品運費模板', 'as' => 'goods.items.templates_change', 'uses' => 'Items@setItemsTemplate']);
        $api->post('/goods/setItemsCategory', ['name' => '更新商品分類', 'as' => 'goods.items.category_change', 'uses' => 'Items@setItemsCategory']);
        $api->get('/goods/items', ['name' => '獲取商品列表', 'as' => 'goods.items.lists', 'uses' => 'Items@getItemsList']);
        $api->get('/goods/items/onsale', ['name' => '獲取可銷售的商品列表', 'as' => 'goods.items.onsale.lists', 'uses' => 'Items@getOnsaleItemsList']);
        $api->get('/goods/sku', ['name' => '獲取商品列表', 'as' => 'goods.sku.lists', 'uses' => 'Items@getSkuList']);
        $api->get('/goods/items/{item_id}', ['name' => '獲取商品詳情', 'as' => 'goods.items.detail', 'uses' => 'Items@getItemsDetail']);
        $api->delete('/goods/items/{item_id}', ['name' => '刪除商品', 'as' => 'goods.items.delete', 'uses' => 'Items@deleteItems']);
        $api->delete('/goods/items/{item_id}/response', ['name' => '刪除商品', 'as' => 'goods.items.delete.response', 'uses' => 'Items@deleteItemsResponseData']);
        $api->put('/goods/items/{item_id}', ['name' => '更新商品', 'as' => 'goods.items.update', 'uses' => 'Items@updateItems']);
        $api->get('/goods/distributionGoodsWxaCodeStream', ['name' => '獲取商品分銷二維碼', 'as' => 'goods.items.distributiongoodswxacode', 'uses' => 'Items@getDistributionGoodsWxaCodeStream']);
        $api->post('/goods/warning_store', ['name' => '設置商品預警庫存', 'as' => 'goods.items.warning_store', 'uses' => 'Items@setItemWarningStore']);
        $api->post('/goods/setItemsSort', ['name' => '更新商品排序', 'as' => 'goods.items.sort', 'uses' => 'Items@setItemsSort']);
        $api->get('/goods/epidemicItems/list', ['name' => '疫情商品配置列表', 'as' => 'goods.epidemicItems.list', 'uses' => 'EpidemicItems@epidemicItemsList']);
        $api->get('/goods/epidemicRegister/list', ['name' => '疫情訂單登記列表', 'middleware' => ['datapass'], 'as' => 'goods.epidemicRegister.list', 'uses' => 'EpidemicItems@epidemicRegisterList']);
        $api->post('/goods/epidemicRegister/export', ['name' => '疫情防控登記導出', 'middleware' => ['datapass'], 'as' => 'goods.epidemicRegister.export', 'uses' => 'EpidemicItems@exportEpidemicRegisterData']);
        //修改商品庫存
        $api->put('/goods/itemstoreupdate', ['name' => '設置商品庫存','middleware'=>'activated', 'as' => 'goods.store.upate', 'uses' =>'Items@batchUpdateItemStore']);
        $api->put('/goods/itemstatusupdate', ['name' => '設置商品狀態','middleware'=>'activated', 'as' => 'goods.status.upate', 'uses' =>'Items@batchUpdateItemStatus']);
        // 分類
        $api->get('/goods/category', ['name'=> '獲取商品分類列表', 'as' => 'goods.category.lists', 'uses' => 'ItemsCategory@getCategory']);
        $api->get('/goods/category/{category_id}', ['name'=> '獲取單條分類數據', 'as' => 'goods.category.get', 'uses' => 'ItemsCategory@getCategoryInfo']);
        $api->post('/goods/category', ['name' => '添加分類', 'as' => 'goods.category.create', 'uses' => 'ItemsCategory@createCategory']);
        $api->post('/goods/createcategory', ['name' => '添加分類', 'as' => 'goods.createcategory.create', 'uses' => 'ItemsCategory@createClassification']);
        $api->delete('/goods/category/{category_id}', ['name' => '刪除分類', 'as' => 'goods.category.delete', 'uses' => 'ItemsCategory@deleteCategory']);
        $api->put('/goods/category/{category_id}', ['name' => '更新單條分類信息', 'as' => 'goods.category.update', 'uses' => 'ItemsCategory@updateCategory']);

        $api->post('/goods/attributes', ['name' => '新增商品屬性', 'as' => 'goods.attributes.add', 'uses' => 'ItemsAttributes@addItemsAttributes']);
        $api->put('/goods/attributes/{attribute_id}', ['name' => '更新商品屬性', 'as' => 'goods.attributes.update', 'uses' => 'ItemsAttributes@updateItemsAttributes']);
        $api->get('/goods/attributes', ['name' => '獲取商品屬性列表', 'as' => 'goods.attributes.list', 'uses' => 'ItemsAttributes@getItemsAttrList']);
        $api->delete('/goods/attributes/{attribute_id}', ['name' => '刪除商品屬性', 'as' => 'goods.attributes.delete', 'uses' => 'ItemsAttributes@deleteItemsAttributes']);

        //修改商品的價格、庫存、上下架狀態
        $api->put('/goods/itemsupdate', ['name' => '修改商品價格、庫存、上下架狀態', 'as' => 'goods.itemsupdate', 'uses' => 'Items@updateItemsPriceStoreStatus']);

        //保存商品會員價
        $api->post('/goods/memberprice/save', ['name'=> '保存商品會員價', 'as' => 'goods.member.price', 'uses' => 'MemberPrice@saveMemberPrice']);
        //獲取會員價信息
        $api->get('/goods/memberprice/{item_id}', ['name' => '獲取會員價列表', 'as' => 'goods.items.detail', 'uses' => 'MemberPrice@getMemberPriceList']);

        // 添加商品標簽
        $api->post('/goods/tag', ['name'=> '添加商品標簽','middleware'=>'activated', 'as' => 'goods.tag.add', 'uses' =>'ItemsTags@createTags']);

        // 刪除商品標簽
        $api->delete('/goods/tag/{tag_id}', ['name'=> '刪除商品標簽','middleware'=>'activated', 'as' => 'goods.tag.delete', 'uses' =>'ItemsTags@deleteTag']);

        // 更新商品標簽
        $api->put('/goods/tag', ['name'=> '更新商品標簽','middleware'=>'activated', 'as' => 'goods.tag.update', 'uses' =>'ItemsTags@updateTags']);

        // 獲取商品標簽列表
        $api->get('/goods/tag', ['name'=> '獲取商品標簽列表','middleware'=>'activated', 'as' => 'goods.tag.list', 'uses' =>'ItemsTags@getTagsList']);

        // 獲取商品標簽詳情
        $api->get('/goods/tag/{tag_id}', ['name'=> '獲取商品標簽詳情','middleware'=>'activated', 'as' => 'goods.tag.get', 'uses' =>'ItemsTags@getTagsInfo']);

        // 商品關聯標簽
        $api->post('/goods/reltag', ['name'=> '商品關聯標簽','middleware'=>'activated', 'as' => 'goods.tag.rel', 'uses' =>'ItemsTags@tagsRelItem']);

        // 獲取商品關聯標簽
        $api->get('/goods/tagsearch', ['name'=> '獲取商品關聯標簽','middleware'=>'activated', 'as' => 'popularize.config.get', 'uses' =>'ItemsTags@getItemIdsByTagids']);

        $api->post('/goods/rebateconf', ['name'=> '保存商品會員價','middleware'=>'activated', 'as' => 'popularize.config.get', 'uses' =>'Items@updateItemsRebateConf']);

        $api->post('/goods/export', ['name'=>'導出商品信息', 'as' => 'goods.export', 'uses' =>'ExportItems@exportItemsData']);
        $api->post('/goods/tag/export', ['name'=>'導出商品標簽信息', 'as' => 'goods.tag.export', 'uses' =>'ExportItems@exportItemsTagData']);
        $api->post('/goods/code/export', ['name'=>'導出商品碼', 'as' => 'goods.tag.export', 'uses' =>'ExportItems@exportItemsCodeData']);
        $api->get('/goods/goodsbycoupon/{coupon_id}', ['name' => '根據優惠券獲取優惠券可使用的商品', 'as' => 'getGoodsByCoupon.get', 'uses' => 'Items@getGoodsByCoupon']);

        // 商品導購分潤配置
        $api->get('/goods/profit/{item_id}', ['name'=>'商品導購分潤配置獲取', 'as' => 'goods.profit.info', 'uses' =>'ItemsProfit@getItemsProfit']);
        $api->post('/goods/profit/save', ['name'=>'商品導購分潤配置保存', 'as' => 'goods.profit.save', 'uses' =>'ItemsProfit@saveGoodsProfit']);
        $api->post('/goods/category/profit/save', ['name'=>'商品類目導購分潤配置保存', 'as' => 'goods.category.profit.save', 'uses' =>'ItemsCategoryProfit@saveItemsCategoryProfit']);

        //設置關鍵詞搜索
        $api->post('/goods/keywords', ['name' => '設置關鍵詞', 'as' => 'goods.Keywords.set', 'uses' => 'Items@setKeywords']);
        $api->delete('/goods/keywords/{id}', ['name' => '刪除關鍵詞', 'as' => 'goods.Keywords.delete', 'uses' => 'Items@delKeywords']);
        $api->get('/goods/keywords', ['name' => '獲取關鍵詞', 'as' => 'goods.Keywords.get', 'uses' => 'Items@getKeywords']);
        $api->get('/goods/keywordsDetail', ['name' => '獲取關鍵詞詳情', 'as' => 'goods.Keywords.getByShop', 'uses' => 'Items@getKeyWordsDetail']);
        //從oms同步數據
        $api->post('/goods/sync/items', ['name' => '從oms同步商品數據', 'as' => 'goods.sync.items', 'uses' => 'SyncFromOme@syncItems']);
        $api->post('/goods/sync/itemCategory', ['name' => '從oms同步商品分類', 'as' => 'goods.sync.itemCategory', 'uses' => 'SyncFromOme@syncItemCategory']);
        $api->post('/goods/sync/itemSpec', ['name' => '從oms同步商品規格', 'as' => 'goods.sync.itemSpec', 'uses' => 'SyncFromOme@syncItemSpec']);
        $api->post('/goods/sync/brand', ['name' => '從oms同步品牌', 'as' => 'goods.sync.brand', 'uses' => 'SyncFromOme@syncBrand']);

        $api->put('/goods/itemsisgiftupdate', ['name' => '批量設置商品為贈品', 'middleware' => 'activated', 'as' => 'goods.isgift.upate', 'uses' => 'Items@batchUpdateItemIsgift']);
    });
    $api->group(['namespace' => 'GoodsBundle\Http\Api\V1\Action', 'middleware' => [], 'providers' => ''], function($api) {
        $api->get('/goods/clxtest/test', ['name'=>'clxtest','uses'=>'ClxTest@test']);
    });
});
