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
    // 用戶相關信息
    $api->group(['namespace' => 'MembersBundle\Http\Api\V1\Action','middleware' => ['api.auth', 'shoplog'], 'providers' => 'jwt'], function($api) {
        //粉絲相關
        $api->get('/wechat/fans/list',  ['name'=>'獲取微信用戶列表','middleware'=>'activated', 'as' => 'wxFans.list',   'uses' =>'WechatFans@getWxFansList' ]);
        $api->get('/wechat/fans',       ['name'=>'獲取微信用戶基本信息','middleware'=>'activated', 'as' => 'wxFans.info',   'uses' =>'WechatFans@getWxFansInfo' ]);
        $api->put('/wechat/fans/remark',['name'=>'修改微信用戶備註','middleware'=>'activated', 'as' => 'wxFans.remark', 'uses'=>'WechatFans@wxremark' ]);
        $api->get('/wechat/fans/sync',  ['name'=>'同步微信用戶列表','middleware'=>'activated', 'as' => 'wxFans.sync',   'uses' =>'WechatFans@syncWechatFans' ]);
        $api->get('/wechat/fans/tags',  ['name'=>'獲取指定用戶的標簽列表','middleware'=>'activated', 'as' => 'wxFans.tags',   'uses'=>'WechatFans@getWxTagsOfUser' ]);

        $api->post('/wechat/tag',       ['name'=>'微信標簽創建','middleware'=>'activated', 'as' => 'wxTags.create', 'uses' =>'WechatFansTags@wxtagCreate']);
        $api->put('/wechat/tag',        ['name'=>'微信標簽更新','middleware'=>'activated', 'as' => 'wxTags.update', 'uses' =>'WechatFansTags@wxtagUpdate']);
        $api->delete('/wechat/tag',     ['name'=>'刪除微信標簽','middleware'=>'activated', 'as' => 'wxTags.delete', 'uses'=>'WechatFansTags@wxtagDelete']);
        $api->get('/wechat/tags',       ['name'=>'獲取微信標簽列表','middleware'=>'activated', 'as' => 'wxTags.list', 'uses' =>'WechatFansTags@getWxtagList']);
        $api->get('/wechat/tag/sync',   ['name'=>'同步微信用戶標簽列表','middleware'=>'activated', 'as' => 'wxTags.sync',  'uses' =>'WechatFansTags@syncWechatTags']);
        $api->get('/wechat/tag/fans',   ['name'=>'獲取指定標簽下用戶列表','middleware'=>'activated', 'as' => 'wxTags.fans', 'uses'=>'WechatFans@getWxFansOfTag']);
        $api->patch('/wechat/tag/batchSet', ['name'=>'微信用戶批量打標簽','middleware'=>'activated', 'as' => 'wxTags.batchSet', 'uses' =>'WechatFansTags@batchSetUserTags']);

        //會員相關
        $api->post('/members/register/setting', ['name'=>'設置會員註冊項','middleware'=>'activated', 'as' => 'member.register.set', 'uses' =>'Members@setMemberRegItems']);
        $api->get('/members/register/setting',  ['name'=>'獲取會員註冊項','middleware'=>'activated', 'as' => 'member.register.get', 'uses' =>'Members@getMemberRegItems']);

        $api->get('/members',  ['name'=>'獲取會員列表','middleware'=>['activated', 'datapass'],  'as' => 'member.list', 'uses' =>'Members@getMemberList']);
        $api->get('/member',   ['name'=>'獲取會員信息','middleware'=>['activated', 'datapass'],  'as' => 'member.info', 'uses' =>'Members@getMemberInfo']);
        // 獲取短信驗證碼-已支持h5
        $api->get('/member/sms/code', ['as' => 'member.sms.code',      'uses' => 'Members@getSmsCode']);
        // 獲取圖片驗證碼-已支持h5
        $api->get('/member/image/code', ['as' => 'member.image.code',      'uses' => 'Members@getImageVcode']);
        $api->post('/member',   ['name'=>'新增會員','middleware'=>['activated'],  'as' => 'member.create', 'uses' =>'Members@createMember']);
        $api->patch('/member', ['name'=>'更新會員信息','middleware'=>'activated',  'as' => 'member.update', 'uses' =>'Members@updateMemberInfo']);
        $api->put('/member',   ['name'=>'更新會員手機信息','middleware'=>'activated',  'as' => 'member.upate.mobile', 'uses' =>'Members@updateMobileById']);
        $api->put('/member/salesman',   ['name'=>'設置會員的導購員','middleware'=>'activated',  'as' => 'member.upate.salesman', 'uses' =>'Members@setMemberSalesman']);
        $api->put('/member/grade',   ['name'=>'更新會員等級','middleware'=>'activated',  'as' => 'member.upate.grade_id', 'uses' =>'Members@updateGradeById']);
        $api->patch('/member/grade',   ['name'=>'批量更新會員等級','middleware'=>'activated',  'as' => 'member.upate.grade_ids', 'uses' =>'Members@updateGrade']);
        $api->get('/operate/loglist',   ['name'=>'獲取會員操作日誌','middleware'=>'activated',  'as' => 'member.operate.logs', 'uses' =>'Members@gerMemberOperateLogList']);


        $api->post('/member/smssend',       ['name'=>'會員群發短信','middleware'=>'activated', 'as' => 'member.smssend', 'uses' =>'SmsController@smsSends']);


        $api->post('/member/tag',       ['name'=>'新增會員標簽','middleware'=>'activated', 'as' => 'member.tag.add', 'uses' =>'MemberTags@createTags']);
        $api->delete('/member/tag/{tag_id}',       ['name'=>'刪除會員標簽','middleware'=>'activated', 'as' => 'member.tag.delete', 'uses' =>'MemberTags@deleteTag']);
        $api->put('/member/tag',       ['name'=>'更新會員標簽','middleware'=>'activated', 'as' => 'member.tag.update', 'uses' =>'MemberTags@updateTags']);
        $api->get('/member/tag',       ['name'=>'獲取會員標簽列表','middleware'=>'activated', 'as' => 'member.tag.list', 'uses' =>'MemberTags@getTagsList']);
        $api->get('/member/tag/{tag_id}',       ['name'=>'獲取會員標簽詳情','middleware'=>'activated', 'as' => 'member.tag.get', 'uses' =>'MemberTags@getTagsInfo']);
        $api->post('/member/reltagdel',       ['name'=>'刪除會員標簽','middleware'=>'activated', 'as' => 'member.tag.del', 'uses' =>'MemberTags@tagsRelUserDel']);
        $api->post('/member/reltag',       ['name'=>'關聯會員標簽','middleware'=>'activated', 'as' => 'member.tag.rel', 'uses' =>'MemberTags@tagsRelUser']);

        $api->get('/member/tagsearch',       ['name'=>'根據標簽篩選會員','middleware'=>'activated', 'as' => 'member.tagsearch', 'uses' =>'MemberTags@getUserIdsByTagids']);

        $api->get('/member/export',       ['name'=>'導出會員信息','middleware'=>['activated', 'datapass'], 'as' => 'member.export', 'uses' =>'ExportData@exportMemberData']);
        $api->post('/member/batchOperating',       ['name'=>'批量操作會員信息', 'middleware'=>'activated', 'as' => 'member.batch.operating', 'uses' =>'ExportData@batchProcessMemberData']);
        //會員標簽分類
        $api->post('/member/tagcategory',                    ['name'=>'新增會員標簽分類','middleware'=>'activated', 'as' => 'member.tagcategory.add', 'uses' =>'TagsCategoryController@createTagsCategory']);
        $api->delete('/member/tagcategory/{category_id}',    ['name'=>'刪除會員標簽分類','middleware'=>'activated', 'as' => 'member.tagcategory.delete', 'uses' =>'TagsCategoryController@deleteTagsCategory']);
        $api->put('/member/tagcategory/{category_id}',       ['name'=>'更新會員標簽分類','middleware'=>'activated', 'as' => 'member.tagcategory.update', 'uses' =>'TagsCategoryController@updateTagsCategory']);
        $api->get('/member/tagcategory',                     ['name'=>'獲取會員標簽分類列表','middleware'=>'activated', 'as' => 'member.tagcategory.list', 'uses' =>'TagsCategoryController@getTagsCategoryList']);
        $api->get('/member/tagcategory/{category_id}',       ['name'=>'獲取會員標簽分類詳情','middleware'=>'activated', 'as' => 'member.tagcategory.get', 'uses' =>'TagsCategoryController@getTagsCategoryInfo']);


        $api->post('/member/bindusersalespersonrel', ['name'=>'添加或修改會員與導購員的綁定關系','middleware'=>'activated', 'as' => 'member.bindusersalesperson.update', 'uses' =>'Members@bindUserSalespersonRel']);
        //更新會員基礎信息
        $api->put('/member/update',  ['name'=>'更新會員基礎信息','middleware'=>'activated', 'as' => 'member.update',  'uses' => 'Members@updateMember']);

        // 白名單
        $api->get('/members/whitelist/list',['name'=>'獲取會員白名單列表','middleware'=>['activated', 'datapass'], 'as' => 'member.whitelist.list', 'uses' =>'MembersWhitelist@getLists']);
        $api->get('/members/whitelist/{id}',['name'=>'獲取會員白名單詳情','middleware'=>'activated', 'as' => 'member.whitelist.info', 'uses' =>'MembersWhitelist@getInfo']);
        $api->post('/members/whitelist', ['name' => '創建會員白名單', 'as' => 'member.whitelist.create', 'uses'=>'MembersWhitelist@createData']);
        $api->post('/members/whitelist/{id}', ['name' => '更改會員白名單信息', 'as' => 'member.whitelist.update', 'uses'=>'MembersWhitelist@updateData']);
        $api->delete('/members/whitelist/{id}', ['name' => '刪除會員白名單信息', 'as' => 'member.whitelist.delete', 'uses'=>'MembersWhitelist@deleteData']);

        //獲取訂閱列表
        $api->get('/members/subscribe/list',['name'=>'獲取訂閱列表','middleware'=>'activated', 'as' => 'member.subscribe.list', 'uses' =>'MembersSubscribe@getLists']);

        //信任登錄列表
        $api->post('/members/trustlogin/list',['name'=>'獲取信任登錄列表','middleware'=>'activated', 'as' => 'member.trustlogin.list', 'uses' =>'TrustLogin@getTrustLoginList']);
        $api->put('/members/trustlogin/setting',['name'=>'保存信任登錄狀態','middleware'=>'activated', 'as' => 'member.trustlogin.setting', 'uses' =>'TrustLogin@saveStatusSetting']);

    });
});
