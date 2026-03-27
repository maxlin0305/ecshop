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

$api->version('v1',
    function($api) {
        // 微信相關信息
        $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function ($api) {
            $api->post('/espier/upload_file', ['name'=>'上傳文件','middleware'=>'activated',  'as' => 'espier.upload',  'uses'=>'UploadFile@handleUploadFile']);
            $api->get('/espier/upload_files', ['name'=>'獲取上傳文件列表','middleware'=>'activated',  'as' => 'espier.upload.list',    'uses'=>'UploadFile@getUploadLists']);
            $api->get('/espier/upload_error_file_export/{id}', ['name'=>'上傳文件執行後錯誤信息','middleware'=>'activated',  'as' => 'espier.upload.error.export',    'uses'=>'UploadFile@exportUploadErrorFile']);
            $api->get('/espier/upload_template', ['name'=>'獲取上傳文件模版','middleware'=>'activated',  'as' => 'espier.upload.template.export',    'uses'=>'UploadFile@exportUploadTemplate']);

            //圖片分類相關
            $api->post('/espier/image_upload_token', ['name'=>'獲取上傳圖片token','middleware'=>'activated',  'as' => 'espier.image.uploadToken.get',  'uses'=>'UploadImages@getPicUploadToken']);
            $api->post('/espier/oss_upload_token', ['name'=>'獲取雲存儲上傳token','middleware'=>'activated',  'as' => 'espier.oss.uploadToken.get',  'uses'=>'UploadImages@getUploadToken']);

            $api->post('/espier/video_upload_token', ['name'=>'獲取上傳視頻token','middleware'=>'activated',  'as' => 'espier.image.uploadToken.get',  'uses'=>'UploadImages@getVideoUploadToken']);
            $api->post('/espier/image/cat', ['name'=>'添加圖片分類','middleware'=>'activated',  'as' => 'espier.image.cat.edit',  'uses'=>'UploadImages@editImageCat']);
            $api->get('/espier/image/cat/children', ['name'=>'獲取分類的子類','middleware'=>'activated',  'as' => 'espier.image.cat.children',  'uses'=>'UploadImages@getCatChildren']);
            $api->get('/espier/image/cat/{image_cat_id}', ['name'=>'獲取分類詳情','middleware'=>'activated',  'as' => 'espier.image.cat.info',  'uses'=>'UploadImages@getCatInfo']);
            $api->delete('/espier/image/cat/{image_cat_id}', ['name'=>'刪除圖片分類','middleware' => 'activated', 'as' => 'espier.image.cat.del',  'uses'=>'UploadImages@delImgCat']);

            $api->post('/espier/oss_upload', ['name' => 'OSS直傳文件(圖片&視頻上傳)', 'middleware' => 'activated', 'as' => 'espier.oss.upload', 'uses' => 'UploadImages@ossUpload']);

            // 圖片相關
            $api->post('/espier/image', ['name'=>'保存圖片','middleware'=>'activated',  'as' => 'espier.image.save',  'uses'=>'UploadImages@saveImage']);
            $api->get('/espier/images', ['name'=>'獲取圖片列表','middleware'=>'activated',  'as' => 'espier.image.list',  'uses'=>'UploadImages@getImageList']);
            $api->delete('/espier/images', ['name'=>'刪除圖片','middleware'=>'activated',  'as' => 'espier.image.del',  'uses'=>'UploadImages@deleteImage']);

            $api->post('/espier/image/movecat', ['name'=>'移動圖片到指定分類','middleware'=>'activated',  'as' => 'espier.image.move.cat',  'uses'=>'UploadImages@moveImageCat']);

            $api->post('/espier/upload_localimage', ['name'=>'上傳圖片','middleware'=>'activated',  'as' => 'espier.localimage.upload',  'uses'=>'UploadImages@uploadeImage']);

            $api->get('/espier/exportlog/list', ['name'=>'獲取文件導出列表','middleware'=>'activated',  'as' => 'espier.export.loglist',  'uses'=>'ExportLogController@getExportLogList']);
            $api->get('/espier/exportlog/file/down', ['name'=>'文件導出下載','middleware'=>'activated',  'as' => 'espier.export.file.down',  'uses'=>'ExportLogController@fileDown']);
            // 地區模板
            $api->get('/espier/address', ['name'=>'獲取地址','middleware' => 'activated', 'as' => 'espier.address.info', 'uses' => 'AddressController@get']);

            $api->get('/espier/printer', ['name'=>'獲取易聯雲配置','middleware' => 'activated', 'as' => 'espier.printer.info', 'uses' => 'PrinterController@info']);
            $api->post('/espier/printer', ['name'=>'保存易聯雲配置','middleware' => 'activated', 'as' => 'espier.printer.save', 'uses' => 'PrinterController@update']);
            $api->get('/espier/printer/shop', ['name'=>'獲取商家易聯雲列表','middleware' => 'activated', 'as' => 'espier.printer.shop.list', 'uses' => 'PrinterController@getPrinterList']);
            $api->post('/espier/printer/shop', ['name'=>'添加商家易聯雲打印機','middleware' => 'activated', 'as' => 'espier.printer.shop.created', 'uses' => 'PrinterController@createPrinter']);
            $api->put('/espier/printer/shop/{id}', ['name'=>'更新商家易聯雲打印機','middleware' => 'activated', 'as' => 'espier.printer.shop.updated', 'uses' => 'PrinterController@updatePrinter']);
            $api->delete('/espier/printer/shop/{id}', ['name'=>'刪除商家易聯雲打印機','middleware' => 'activated', 'as' => 'espier.printer.shop.deleted', 'uses' => 'PrinterController@deletePrinter']);

            // 配置
            // 配置請求字段和驗證模式
            $api->get('/espier/config/request_fields', ['name' => '獲取配置的請求字段列表', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.get', 'uses' => 'ConfigRequestFieldsController@list']);
            $api->post('/espier/config/request_fields', ['name' => '創建配置的請求字段', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.create', 'uses' => 'ConfigRequestFieldsController@create']);
            $api->put('/espier/config/request_fields/switch', ['name' => '更新配置的請求字段的開關', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.switch', 'uses' => 'ConfigRequestFieldsController@updateSwitch']);
            $api->put('/espier/config/request_fields/info', ['name' => '更新配置的請求字段的內容', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.info', 'uses' => 'ConfigRequestFieldsController@updateInfo']);
            $api->delete('/espier/config/request_fields', ['name' => '創建配置的請求字段', 'middleware' => 'activated', 'as' => 'espier.config.request_fields.delete', 'uses' => 'ConfigRequestFieldsController@delete']);
            // 配置請求字段和驗證模式 >> 配置項
            $api->post('/espier/config/request_field_setting', ['name' => '設置配置請求字段的配置項', 'middleware' => 'activated', 'as' => 'espier.config.request_field_setting.post', 'uses' => 'ConfigRequestFieldsController@updateConfig']);
            $api->get('/espier/config/request_field_setting', ['name' => '設置配置請求字段的配置項', 'middleware' => 'activated', 'as' => 'espier.config.request_field_setting.get', 'uses' => 'ConfigRequestFieldsController@getConfig']);

            // 街道社區
            $api->get('/espier/subdistrict', ['name' => '獲取街道社區列表', 'middleware' => 'activated', 'as' => 'espier.subdistrict.list.get', 'uses' => 'SubdistrictController@get']);
            $api->get('/espier/subdistrict/{id}', ['name' => '獲取街道社區', 'middleware' => 'activated', 'as' => 'espier.subdistrict.get', 'uses' => 'SubdistrictController@getInfo']);
            $api->delete('/espier/subdistrict/{id}', ['name' => '刪除街道社區', 'middleware' => 'activated', 'as' => 'espier.subdistrict.delete', 'uses' => 'SubdistrictController@delete']);
            $api->put('/espier/subdistrict', ['name' => '保存街道社區', 'middleware' => 'activated', 'as' => 'espier.subdistrict.save', 'uses' => 'SubdistrictController@save']);
        });

        $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action', 'middleware' => ['api.auth','shoplog'], 'providers' => 'jwt'], function ($api) {
            $api->post('/espier/system/detect_version', ['name' => '升級', 'uses' => 'UpgradeController@detectVersion']);
            $api->post('/espier/system/upgrade', ['name' => '升級', 'uses' => 'UpgradeController@upgrade']);
            $api->post('/espier/system/rollback', ['name' => '回滾', 'uses' => 'UpgradeController@rollback']);
            $api->post('/espier/system/changelog', ['name' => '更新日誌', 'uses' => 'UpgradeController@changelog']);
        });
        $api->group(['namespace' => 'EspierBundle\Http\Api\V1\Action', 'middleware' => 'api.throttle', 'limit' => 30, 'expires' => 1], function ($api) {
            $api->post('/espier/system/agreement', ['name' => '獲取安裝協議', 'uses' => 'UpgradeController@getAgreement']);
        });

    });
