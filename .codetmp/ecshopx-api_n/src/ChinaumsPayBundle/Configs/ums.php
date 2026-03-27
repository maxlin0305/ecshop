<?php

return [
    'uri'     => env('UMS_URI', 'https://api-mop.chinaums.com/v1/netpay'),
    'AppId' => env('UMS_APP_ID', '8a81c1be7f93c87101818507cab71492'),
    'AppKey'   => env('UMS_APP_KEY', '62afbf72615b4007bb7b79b12a3d2621'),
    'Md5Key'   => env('UMS_md5_KEY', 'E58zSTwBrJtjECcMkxAyfePy32Gf3wyS'),
    'pre' => '32C2',
    'group_no' => env('CHINAUMSPAY_GROUP_NO', 'YHJLSP'),// 商户集团编号
    'sftp' => [
        'host' => '180.169.95.129',
        'port' => 22,
        'username' => 'YHJLSP_fan',
        'password' => '5tI#k7A6',
        'timeout' => 10,
    ],
];