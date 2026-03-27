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
    // 商品相關信息
    $api->group(['namespace' => 'PointsmallBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt', 'prefix' => 'pointsmall'], function($api) {
        $api->post('/goods/items', ['name' => '添加商品', 'as' => 'pointsmall.goods.items.create', 'uses' => 'Items@createItems']);
        $api->post('/goods/setItemsTemplate', ['name' => '更新商品運費模板', 'as' => 'pointsmall.goods.items.templates_change', 'uses' => 'Items@setItemsTemplate']);
        $api->post('/goods/setItemsCategory', ['name' => '更新商品分類', 'as' => 'pointsmall.goods.items.category_change', 'uses' => 'Items@setItemsCategory']);
        $api->get('/goods/items', ['name' => '獲取商品列表', 'as' => 'pointsmall.goods.items.lists', 'uses' => 'Items@getItemsList']);
        $api->get('/goods/items/{item_id}', ['name' => '獲取商品詳情', 'as' => 'pointsmall.goods.items.detail', 'uses' => 'Items@getItemsDetail']);
        $api->delete('/goods/items/{item_id}', ['name' => '刪除商品', 'as' => 'pointsmall.goods.items.delete', 'uses' => 'Items@deleteItems']);
        $api->put('/goods/items/{item_id}', ['name' => '更新商品', 'as' => 'pointsmall.goods.items.update', 'uses' => 'Items@updateItems']);
        $api->post('/goods/setItemsSort', ['name' => '更新商品排序', 'as' => 'pointsmall.goods.items.sort', 'uses' => 'Items@setItemsSort']);
        //修改商品庫存
        $api->put('/goods/itemstoreupdate', ['name' => '設置商品庫存','middleware'=>'activated', 'as' => 'pointsmall.goods.store.upate', 'uses' =>'Items@batchUpdateItemStore']);
        $api->put('/goods/itemstatusupdate', ['name' => '設置商品狀態','middleware'=>'activated', 'as' => 'pointsmall.goods.status.upate', 'uses' =>'Items@batchUpdateItemStatus']);
        // 分類
        $api->get('/goods/category', ['name'=> '獲取商品分類列表', 'as' => 'pointsmall.goods.category.lists', 'uses' => 'ItemsCategory@getCategory']);
        $api->get('/goods/category/{category_id}', ['name'=> '獲取單條分類數據', 'as' => 'pointsmall.goods.category.get', 'uses' => 'ItemsCategory@getCategoryInfo']);
        $api->post('/goods/category', ['name' => '添加分類', 'as' => 'pointsmall.goods.category.create', 'uses' => 'ItemsCategory@createCategory']);
        $api->delete('/goods/category/{category_id}', ['name' => '刪除分類', 'as' => 'pointsmall.goods.category.delete', 'uses' => 'ItemsCategory@deleteCategory']);
        $api->put('/goods/category/{category_id}', ['name' => '更新單條分類信息', 'as' => 'pointsmall.goods.category.update', 'uses' => 'ItemsCategory@updateCategory']);

        $api->post('/goods/export', ['name'=>'導出商品信息', 'as' => 'pointsmall.goods.export', 'uses' =>'ExportItems@exportItemsData']);
    });

    // 設置
    $api->group(['namespace' => 'PointsmallBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt', 'prefix' => 'pointsmall'], function($api) {

        $api->post('/setting', ['name'=> '保存基礎設置', 'as' => 'pointsmall.setting.save', 'uses' => 'Setting@saveSetting']);
        $api->get('/setting', ['name' => '獲取基礎設置', 'as' => 'pointsmall.setting.get', 'uses' => 'Setting@getSetting']);

        $api->post('/template/setting', ['name'=> '保存模板設置', 'as' => 'pointsmall.template.setting.save', 'uses' => 'Setting@saveTemplateSetting']);
        $api->get('/template/setting', ['name' => '獲取模板設置', 'as' => 'pointsmall.template.setting.get', 'uses' => 'Setting@getTemplateSetting']);


    });
});
