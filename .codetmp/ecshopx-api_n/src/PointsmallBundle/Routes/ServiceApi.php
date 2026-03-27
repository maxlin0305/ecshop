<?php

namespace PointsmallBundle\Routes;

class ServiceApi
{
    public static function register()
    {
        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', function ($api) {
            $api->group(['namespace' => 'PointsmallBundle\Api\V1\Action', 'prefix' => 'pointsmallservice', 'middleware' => ['servicesign']], function ($api) {
                $api->post('/goods/category/list', ['name' => '获取分类列表', 'as' => 'service.goods.category.list', 'uses' => 'ItemsCategory@getCategory']);
                $api->get('/goods/category/{company_id}/{category_id}', ['name' => '获取单条分类数据', 'as' => 'service.goods.category.get', 'uses' => 'ItemsCategory@getCategoryInfo']);
                $api->post('/goods/category', ['name' => '添加分类', 'as' => 'service.goods.category.create', 'uses' => 'ItemsCategory@createCategory']);
                $api->delete('/goods/category/{category_id}', ['name' => '删除分类', 'as' => 'service.goods.category.delete', 'uses' => 'ItemsCategory@deleteCategory']);
                $api->put('/goods/category/{company_id}/{category_id}', ['name' => '更新单条分类信息', 'as' => 'service.goods.category.update', 'uses' => 'ItemsCategory@updateCategory']);
            });
        });
    }
}
