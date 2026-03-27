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
    $api->group(['namespace' => 'SelfserviceBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/selfhelp/formdata', ['name'=>'新增表單元素配置項',  'middleware'=>'activated',  'as' => 'selfhelp.form.add',  'uses'=>'FormSettingController@createData']);
        $api->put('/selfhelp/formdata', ['name'=>'更新表單元素配置項', 'middleware'=>'activated',  'as' => 'selfhelp.form.edit',  'uses'=>'FormSettingController@updateData']);
        $api->get('/selfhelp/formdata', ['name'=>'獲取表單元素配置項列表', 'middleware'=>'activated',  'as' => 'selfhelp.form.list',  'uses'=>'FormSettingController@getDatalist']);
        $api->get('/selfhelp/formdata/{id}', [ 'name'=>'獲取表單元素配置項詳情', 'middleware'=>'activated',  'as' => 'selfhelp.form.info',  'uses'=>'FormSettingController@getDataInfo']);
        $api->post('/selfhelp/formdata/discard/{id}', ['name'=>'廢棄表單元素配置項', 'middleware'=>'activated',  'as' => 'selfhelp.form.delete',  'uses'=>'FormSettingController@deleteData']);
        $api->post('/selfhelp/formdata/restore/{id}', ['name'=>'恢復表單元素配置項', 'middleware'=>'activated',  'as' => 'selfhelp.form.delete',  'uses'=>'FormSettingController@restoreData']);

        $api->post('/selfhelp/formtem', ['name'=>'新增表單模板',  'middleware'=>'activated',  'as' => 'selfhelp.template.add',  'uses'=>'FormTemplateController@createData']);
        $api->put('/selfhelp/formtem', [ 'name'=>'更新表單模板', 'middleware'=>'activated',  'as' => 'selfhelp.template.edit',  'uses'=>'FormTemplateController@updateData']);
        $api->get('/selfhelp/formtem', ['name'=>'獲取表單模板列表', 'middleware'=>'activated',  'as' => 'selfhelp.template.list',  'uses'=>'FormTemplateController@getDatalist']);
        $api->get('/selfhelp/formtem/{id}', [ 'name'=>'獲取表單模板詳情','middleware'=>'activated',  'as' => 'selfhelp.template.info',  'uses'=>'FormTemplateController@getDataInfo']);
        $api->post('/selfhelp/formtem/discard/{id}', ['name'=>'廢棄表單模板',  'middleware'=>'activated',  'as' => 'selfhelp.template.delete',  'uses'=>'FormTemplateController@deleteData']);
        $api->post('/selfhelp/formtem/restore/{id}', ['name'=>'恢復表單模板',  'middleware'=>'activated',  'as' => 'selfhelp.template.delete',  'uses'=>'FormTemplateController@deleteData']);


        $api->post('/selfhelp/setting/physical', ['name'=>'配置體測表單',  'middleware'=>'activated',  'as' => 'selfhelp.setting.physical.set',  'uses'=>'UserDailyRecordController@settingPhysical']);
        $api->get('/selfhelp/setting/physical', ['name'=>'獲取體測表單配置',  'middleware'=>'activated',  'as' => 'selfhelp.setting.physical.get',  'uses'=>'UserDailyRecordController@getSettingPhysical']);
        $api->get('/selfhelp/physical/alluserlist', ['name'=>'獲取體測數據所有會員（最近一次的記錄）',  'middleware'=>'activated',  'as' => 'selfhelp.physical.alluserlist',  'uses'=>'UserDailyRecordController@getAllUserList']);
        $api->get('/selfhelp/physical/userdata', ['name'=>'獲取指定會員最近5次的體測記錄',  'middleware'=>'activated',  'as' => 'selfhelp.physical.userlist',  'uses'=>'UserDailyRecordController@getUserPersonalRecord']);

        $api->get('/selfhelp/physical/datelist', ['name'=>'獲取所有記錄的日期列表',  'middleware'=>'activated',  'as' => 'selfhelp.physical.datelist',  'uses'=>'UserDailyRecordController@getRecordDateList']);

        $api->post('/selfhelp/registrationActivity/create', ['name'=>'新增報名活動',  'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.add',  'uses'=>'RegistrationActivityController@createData']);
        $api->put('/selfhelp/registrationActivity/update', [ 'name'=>'更新報名活動', 'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.edit',  'uses'=>'RegistrationActivityController@updateData']);
        $api->get('/selfhelp/registrationActivity/list', ['name'=>'獲取報名活動列表', 'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.list',  'uses'=>'RegistrationActivityController@getDatalist']);
        $api->get('/selfhelp/registrationActivity/get', [ 'name'=>'獲取報名活動詳情','middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.info',  'uses'=>'RegistrationActivityController@getDataInfo']);
        $api->post('/selfhelp/registrationActivity/del', ['name'=>'刪除報名活動',  'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.delete',  'uses'=>'RegistrationActivityController@deleteData']);
        $api->post('/selfhelp/registrationActivity/invalid', ['name'=>'過期報名活動',  'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.invalid',  'uses'=>'RegistrationActivityController@restoreData']);
        $api->get('/selfhelp/registrationActivity/easylist', ['name'=>'獲取報名活動列表', 'middleware'=>'activated',  'as' => 'selfhelp.registrationActivity.easylist',  'uses'=>'RegistrationActivityController@getEasyDatalist']);

        $api->get('/selfhelp/registrationRecord/list', ['name'=>'獲取表單模板列表', 'middleware'=>['activated', 'datapass'],  'as' => 'selfhelp.registrationRecord.list',  'uses'=>'RegistrationRecordController@getDatalist']);
        $api->get('/selfhelp/registrationRecord/get', [ 'name'=>'獲取表單模板詳情','middleware'=>['activated', 'datapass'],  'as' => 'selfhelp.registrationRecord.info',  'uses'=>'RegistrationRecordController@getDataInfo']);
        $api->put('/selfhelp/registrationReview',     [ 'name'=>'報名審核','middleware'=>'activated',  'as' => 'selfhelp.registrationRecord.review',  'uses'=>'RegistrationRecordController@registrationReview']);
        $api->get('/selfhelp/registrationRecord/export', [ 'name'=>'導出報名記錄','middleware'=>['activated','datapass'],  'as' => 'selfhelp.registrationRecord.export',  'uses'=>'RegistrationRecordController@exportRegistrationRecord']);
    });
});
