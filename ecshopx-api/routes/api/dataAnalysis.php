<?php
$api->version('v1', function ($api) {
    $api->group(['namespace' => 'YoushuBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function ($api) {
        $api->post('/dataAnalysis/youshu/setting', ['name' => '騰訊有數參數設置', 'as' => 'dataAnalysis.youshu.setting', 'uses' => 'Setting@save']);
        $api->get('/dataAnalysis/youshu/query', ['name' => '騰訊有數參數查詢', 'as' => 'dataAnalysis.youshu.query', 'uses' => 'Setting@query']);
    });
});
