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
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => [/* 'api.auth', 'shoplog' */], 'providers' => 'jwt'], function ($api) {
        //發表
        $api->post('/ugc/post/create', [ 'name'=>'發布筆記', 'as' => 'ugc.post.create', 'uses'=>'PostController@createPost']);
        //筆記批量打角標
        $api->post('/ugc/post/edit', [ 'name'=>'筆記批量打角標', 'as' => 'ugc.post.edit', 'uses'=>'PostController@editPost']);
        //筆記批量打角標
        $api->post('/ugc/post/setBadges', [ 'name'=>'筆記批量打角標', 'as' => 'ugc.post.setBadges', 'uses'=>'PostController@setBadges']);
        //審核
        $api->post('/ugc/post/verify', [ 'name'=>'審核', 'as' => 'ugc.post.verify', 'uses'=>'PostController@verifyPost']);
        //發布與下架
        $api->post('/ugc/post/enable', [ 'name'=>'發布/上架', 'as' => 'ugc.post.enable', 'uses'=>'PostController@enablePost']);

        //筆記列表
        $api->get('/ugc/post/list', ['name' => '獲取筆記列表', 'middleware' => 'activated', 'as' => 'ugc.post.list', 'uses' => 'PostController@getPostList']);

        //詳情
        $api->get('/ugc/post/detail', ['name' => '筆記詳情', 'middleware' => ['activated'], 'as' => 'ugc.post.detail', 'uses' => 'PostController@getPostDetail']);

        //刪除
        $api->post('/ugc/post/delete',  ['as' => 'ugc.post.delete',  'uses' => 'PostController@deletePost']);

        //話題置頂
        $api->post('/ugc/post/settop',  ['as' => 'ugc.topic.settop',  'uses' => 'PostController@setTopTopic']);


        //feedlog-列表查詢
        $api->get('/mps/feedlog/list',  ['as' => 'ugc.feedlog.list',  'uses' => 'MpsFeedLogController@getMpsFeedLogList']);
        //feedlog-導出
        $api->get('/mps/feedlog/export',  ['as' => 'ugc.feedlog.list',  'uses' => 'MpsFeedLogController@exportMpsFeedLog']);

        //feed文件拉取
        $api->get('/mps/pullfeed',  ['as' => 'ugc.feedlog.list',  'uses' => 'MpsFeedLogController@pullFeed']);

        //寫入支付csv
        $api->get('/mps/ordercsv/genPaidCsvFile', ['name' => '寫入支付csv', 'middleware' => ['activated'], 'as' => 'ugc.ordercsv.genPaidCsvFile', 'uses' => 'MpsFeedLogController@genPaidCsvFile']);

        //寫入發貨日誌csv
        $api->get('/mps/ordercsv/genShippedCsvFile', ['name' => '寫入發貨csv', 'middleware' => ['activated'], 'as' => 'ugc.ordercsv.genShippedCsvFile', 'uses' => 'MpsFeedLogController@genShippedCsvFile']);

        //寫入退貨日誌csv
        $api->get('/mps/ordercsv/genReturndCsvFile', ['name' => '寫入取消csv', 'middleware' => ['activated'], 'as' => 'ugc.ordercsv.genReturndCsvFile', 'uses' => 'MpsFeedLogController@genReturndCsvFile']);

        //寫入取消日誌csv
        $api->get('/mps/ordercsv/genCancelCsvFile', ['name' => '寫入售前退款csv', 'middleware' => ['activated'], 'as' => 'ugc.ordercsv.genCancelCsvFile', 'uses' => 'MpsFeedLogController@genCancelCsvFile']);

        //下架/清空庫存不在feed裏商品
        $api->get('/mps/feedlog/unmarketNotInMpsFeed', ['name' => '下架/清空庫存不在feed裏商品', 'middleware' => ['activated'], 'as' => 'ugc.ordercsv.unmarketNotInMpsFeed', 'uses' => 'MpsFeedLogController@unmarketNotInMpsFeed']);

        //定時上下架商品根據庫存
        $api->get('/mps/feedlog/scheduleApproveStatusDefaultItem', ['name' => '定時上下架商品根據庫存', 'middleware' => ['activated'], 'as' => 'ugc.ordercsv.scheduleApproveStatusDefaultItem', 'uses' => 'MpsFeedLogController@scheduleApproveStatusDefaultItem']);


        //有數商品推送2022-12-05 11:53:02
        $api->get('/mps/feedlog/youshu/addGoods', ['name' => '有數商品推送', 'middleware' => ['activated'], 'as' => 'ugc.youshu.youshu_addGoods', 'uses' => 'MpsFeedLogController@YoushuAddGoods']);

        //有數商品類目推送2022-12-05 11:53:36
        $api->get('/mps/feedlog/youshu/addCategory', ['name' => '有數商品推送', 'middleware' => ['activated'], 'as' => 'ugc.youshu.youshu_addCategory', 'uses' => 'MpsFeedLogController@YoushuAddCategory']);

    });
    // 話題
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action',  'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //創建話題
        $api->post('/ugc/topic/create', [ 'name'=>'新建話題', 'as' => 'ugc.topic.create', 'uses'=>'TopicController@createTopic']);
        //審核話題
        $api->post('/ugc/topic/verify', [ 'name'=>'審核話題', 'as' => 'ugc.topic.verify', 'uses'=>'TopicController@verifyTopic']);

        //置頂話題
        $api->post('/ugc/topic/top', [ 'name'=>'置頂話題', 'as' => 'ugc.topic.top', 'uses'=>'TopicController@topTopic']);
        //發布與下架話題
        $api->post('/ugc/topic/enable', [ 'name'=>'發布/上架話題', 'as' => 'ugc.topic.enable', 'uses'=>'TopicController@enableTopic']);
        //話題列表
        $api->get('/ugc/topic/list', ['name' => '獲取話題列表', 'middleware' => 'activated', 'as' => 'ugc.topic.list', 'uses' => 'TopicController@getTopicList']);
        //話題詳情
        $api->get('/ugc/topic/detail', ['name' => '話題詳情', 'middleware' => ['activated'], 'as' => 'ugc.topic.detail', 'uses' => 'TopicController@getTopicDetail']);
        //刪除話題
        $api->post('/ugc/topic/delete',  ['as' => 'ugc.topic.delete',  'uses' => 'TopicController@deleteTopic']);
        //話題置頂
        $api->post('/ugc/topic/settop',  ['as' => 'ugc.topic.settop',  'uses' => 'TopicController@setTopTopic']);
    });

    //tag
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action',  'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //創建圖片標簽
        $api->post('/ugc/tag/create', [ 'name'=>'新建圖片標簽', 'as' => 'ugc.tag.create', 'uses'=>'TagController@createTag']);

        //審核圖片標簽
        $api->post('/ugc/tag/verify', [ 'name'=>'審核圖片標簽', 'as' => 'ugc.tag.verify', 'uses'=>'TagController@verifyTag']);

        //發布與下架圖片標簽
        $api->post('/ugc/tag/enable', [ 'name'=>'發布/上架圖片標簽', 'as' => 'ugc.tag.enable', 'uses'=>'TagController@enableTag']);


        //圖片標簽列表
        $api->get('/ugc/tag/list', ['name' => '獲取圖片標簽列表', 'middleware' => 'activated', 'as' => 'ugc.tag.list', 'uses' => 'TagController@getTagList']);

        //圖片標簽詳情
        $api->get('/ugc/tag/detail', ['name' => '圖片標簽詳情', 'middleware' => ['activated'], 'as' => 'ugc.tag.detail', 'uses' => 'TagController@getTagDetail']);

        //刪除圖片標簽
        $api->delete('/ugc/tag/delete',  ['as' => 'ugc.tag.delete',  'uses' => 'TagController@deleteTag']);
    });

    //角標
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //創建角標
        $api->post('/ugc/badge/create', [ 'name'=>'新建角標', 'as' => 'ugc.badge.create', 'uses'=>'BadgeController@createBadge']);
        //角標列表
        $api->get('/ugc/badge/list', ['name' => '獲取角標列表', 'middleware' => 'activated', 'as' => 'ugc.badge.list', 'uses' => 'BadgeController@getBadgeList']);
        //角標詳情
        $api->get('/ugc/badge/detail', ['name' => '角標詳情', 'middleware' => ['activated'], 'as' => 'ugc.badge.detail', 'uses' => 'BadgeController@getBadgeDetail']);
        //刪除角標
        $api->post('/ugc/badge/delete',  ['name' => '刪除角標','as' => 'ugc.badge.delete',  'uses' => 'BadgeController@deleteBadge']);
        //角標置頂
        //   $api->post('/ugc/badge/settop',  ['as' => 'ugc.badge.settop',  'uses' => 'BadgeController@setTopBadge']);

    });
    //評論
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //審核
        $api->post('/ugc/comment/verify', [ 'name'=>'審核評論', 'as' => 'ugc.comment.verify', 'uses'=>'CommentController@verifyComment']);

        // //發布與下架
        // $api->post('/ugc/comment/enable', [ 'name'=>'發布/上架', 'as' => 'ugc.comment.enable', 'uses'=>'CommentController@enableComment']);

        //評論列表
        $api->get('/ugc/comment/list', ['name' => '獲取評論列表', 'middleware' => 'activated', 'as' => 'ugc.comment.list', 'uses' => 'CommentController@getCommentList']);

        //詳情
        $api->get('/ugc/comment/detail', ['name' => '筆記詳情', 'middleware' => ['activated'], 'as' => 'ugc.comment.detail', 'uses' => 'CommentController@getCommentDetail']);

        //刪除
        $api->post('/ugc/comment/delete',  ['as' => 'ugc.comment.delete',  'uses' => 'CommentController@deleteComment']);

        // //評論置頂
        // $api->post('/ugc/comment/settop',  ['as' => 'ugc.comment.settop',  'uses' => 'CommentController@setTopTopic']);
    });
    //ugc設置
    $api->group(['namespace' => 'WsugcBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        //保存積分設置
        $api->post('/ugc/setting/point/saveSetting', [ 'name'=>'保存積分設置', 'as' => 'ugc.setting.point.saveSetting', 'uses'=>'SettingController@savePointSetting']);


        //獲取積分設置
        $api->get('/ugc/setting/point/getSetting', ['name' => '獲取積分設置', 'middleware' => ['activated'], 'as' => 'ugc.setting.point.getSetting', 'uses' => 'SettingController@getPointSetting']);



    });
});
