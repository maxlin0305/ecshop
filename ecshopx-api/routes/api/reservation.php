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
    //預約配置相關
    $api->group(['namespace' => 'ReservationBundle\Http\Api\V1\Action','middleware'=>['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->post('/reservation/setting', ['name'=>'保存預約的詳細配置','middleware'=>'activated', 'as' => 'reservation.setting.save', 'uses'=>'Setting@setSetting']);
        $api->get('/reservation/setting', ['name'=>'預約配置詳細信息','middleware'=>'activated', 'as' => 'reservation.setting.get', 'uses'=>'Setting@getSetting']);
    });

    //資源位管理相關
    $api->group(['namespace' => 'ReservationBundle\Http\Api\V1\Action','middleware'=>['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->Post('/resource/level', ['name'=>'新增資源位','middleware'=>'activated', 'as' => 'resource.level.add', 'uses'=>'ResourceLevel@createData']);
        $api->Patch('/resource/level', ['name'=>'更新資源位','middleware'=>'activated', 'as' => 'resource.level.update', 'uses'=>'ResourceLevel@updateData']);
        $api->Delete('/resource/level', ['name'=>'刪除資源位','middleware'=>'activated', 'as' => 'resource.level.delete', 'uses'=>'ResourceLevel@deleteData']);
        $api->get('/resource/level/{id}', ['name'=>'獲取資源位詳細信息','middleware'=>'activated', 'as' => 'resource.level.get', 'uses'=>'ResourceLevel@getData']);
        $api->get('/resource/levellist', ['name'=>'獲取資源位列表','middleware'=>'activated', 'as' => 'resource.level.list', 'uses'=>'ResourceLevel@getListData']);
        $api->put('/resource/setlevelstatus', ['name'=>'修改資源狀態','middleware'=>'activated', 'as' => 'resource.level.set.status', 'uses'=>'ResourceLevel@updateResourceLevelStatus']);
    });

    //排班相關
    $api->group(['namespace' => 'ReservationBundle\Http\Api\V1\Action','middleware'=>['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->Post('/shifttype', ['name'=>'添加排班類型','middleware' => 'activated', 'as' => 'shift.type.create', 'uses'=>'WorkShiftType@createShiftType']);
        $api->Delete('/shifttype/{tyepId}', ['name'=>'刪除排班類型','middleware' => 'activated', 'as' => 'shift.type.delete', 'uses'=>'WorkShiftType@deleteShiftType']);
        $api->Patch('/shifttype', ['name'=>'編輯排班類型','middleware' => 'activated', 'as' => 'shift.type.update', 'uses'=>'WorkShiftType@updateShiftType']);
        $api->get('/shifttype', ['name'=>'排班類型列表','middleware' => 'activated', 'as' => 'shift.type.getlist', 'uses'=>'WorkShiftType@getListShiftType']);

        $api->Post('/workshift', ['name'=>'新增排班','middleware' => 'activated', 'as' => 'work.shift.create', 'uses'=>'WorkShift@createWorkShift']);
        $api->Delete('/workshift', ['name'=>'刪除排班','middleware' => 'activated', 'as' => 'work.shift.delete', 'uses'=>'WorkShift@deleteWorkShift']);
        $api->Patch('/workshift', ['name'=>'編輯排班','middleware' => 'activated', 'as' => 'work.shift.update', 'uses'=>'WorkShift@updateWorkShift']);
        $api->get('/workshift', ['name'=>'排班列表','middleware' => 'activated', 'as' => 'work.shift.getlist', 'uses'=>'WorkShift@getListWorkShift']);
        $api->get('/getweekday', ['name'=>'獲取每年的周日期','middleware' => 'activated', 'as' => 'work.shift.getweekday', 'uses'=>'WorkShift@getEveryYearWeeks']);

        $api->Post('/workshift/default', ['name'=>'新增門店默認排班','middleware' => 'activated', 'as' => 'shift.default.create', 'uses'=>'DefaultWorkShift@createDefaultWorkShift']);
        $api->Delete('/workshift/default', ['name'=>'刪除門店默認排班','middleware' => 'activated', 'as' => 'shift.default.delete', 'uses'=>'DefaultWorkShift@deleteDefaultWorkShift']);
        $api->get('/workshift/default', ['name'=>'獲取門店默認排班','middleware' => 'activated', 'as' => 'shift.default.getlist', 'uses'=>'DefaultWorkShift@getDefaultWorkShift']);


    });

    //預約相關
    $api->group(['namespace' => 'ReservationBundle\Http\Api\V1\Action','middleware'=>['api.auth','shoplog'], 'providers' => 'jwt'], function($api) {
        $api->Post('/reservation', ['name'=>'商家主動占用資源位','middleware' => 'activated', 'as' => 'reservation.create', 'uses'=>'Reservation@create']);
        $api->get('/reservation', ['name'=>'查看預約記錄','middleware' => 'activated', 'as' => 'reservation.get.list', 'uses'=>'Reservation@getList']);
        $api->get('/reservation/period', ['name'=>'獲取每天預約時間段','middleware' => 'activated', 'as' => 'reservation.get.everydaytime', 'uses'=>'Reservation@getEveryDayTimePeriod']);
    });
});
