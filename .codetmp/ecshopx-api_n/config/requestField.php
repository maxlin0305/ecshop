<?php

use \EspierBundle\Services\Config\ConfigRequestFieldsService as Service;

return [
    // 用户注册模块
    Service::MODULE_TYPE_MEMBER_INFO => [
        // 必须开启且必填的字段
        "must_start_required" => env("REQUEST_FIELD_MEMBER_INFO_MUST_START_REQUIRED", "username,mobile"),
        // 默认显示的内容，must_start_required的字段必须要存在default中，否则业务逻辑的流程会有问题
        "default" => [
            "mobile"         => [
                "name"         => "手機號",
                "is_open"      => true,
                "element_type" => "mobile",
                "is_required"  => true,
                "prompt"       => "請填寫手機號"
            ],
            "username"       => [
                "name"         => "昵稱",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => true,
                "prompt"       => "請填寫您的昵稱"
            ],
//            "avatar" => [
//                "name" => env("REQUEST_FIELD_MEMBER_INFO_LABEL_AVATAR", "头像"),
//                "is_open" => true,
//                "element_type" => "input",
//                "is_required" => false
//            ],
            "sex"            => [
                "name"         => "性别",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "請填寫您的性別",
                "items"        => [
                    0 => "未知",
                    1 => "男",
                    2 => "女",
                ]
            ],
            "birthday"       => [
                "name"         => "生日",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "請填寫您的生日",
            ],
            "address"        => [
                "name"         => "家庭地址",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => false,
                "prompt"       => "請輸入您的家庭地址",
            ],
            "email"          => [
                "name"         => "email",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => false,
                "prompt"       => "請輸入您的電子郵件",
            ],
            "industry"       => [
                "name"         => "行業",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "請選擇您的工作行業",
                "items"        => [
                    0  => "金融/銀行/投資",
                    1  => "計算機/互聯網",
                    2  => "媒體/出版/影視/文化",
                    3  => "政府/公共事業",
                    4  => "房地產/建材/工程",
                    5  => "咨詢/法律",
                    6  => "加工製造",
                    7  => "教育培訓",
                    8  => "醫療保健",
                    9  => "運輸/物流/交通",
                    10 => "零售/貿易",
                    11 => "旅遊/度假",
                    12 => "其他",
                ],
            ],
            "income"         => [
                "name"         => "年收入",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "請選擇您的年收入區間",
                "items"        => [
                    0 => "5萬以下",
                    1 => "5萬 ~ 15萬",
                    2 => "15萬 ~ 30萬",
                    3 => "30萬以上",
                    4 => "其他",
                ],
            ],
            "edu_background" => [
                "name"         => "學歷",
                "is_open"      => true,
                "element_type" => "select",
                "is_required"  => false,
                "prompt"       => "請選擇您的學歷",
                "items"        => [
                    0 => "碩士及以上",
                    1 => "本科",
                    2 => "大專",
                    3 => "高中/中專及以下",
                    4 => "其他",
                ],
            ],
            "habbit"         => [
                "name"         => "愛好",
                "is_open"      => true,
                "element_type" => "checkbox",
                "is_required"  => false,
                "prompt"       => "請選擇您的愛好",
                "items"        => [
                    0  => [
                        "name"      => "遊戲",
                        "ischecked" => true,
                    ],
                    1  => [
                        "name"      => "閱讀",
                        "ischecked" => true,
                    ],
                    2  => [
                        "name"      => "音樂",
                        "ischecked" => true,
                    ],
                    3  => [
                        "name"      => "運動",
                        "ischecked" => true,
                    ],
                    4  => [
                        "name"      => "動漫",
                        "ischecked" => true,
                    ],
                    5  => [
                        "name"      => "旅遊",
                        "ischecked" => true,
                    ],
                    6  => [
                        "name"      => "家居",
                        "ischecked" => true,
                    ],
                    7  => [
                        "name"      => "曲藝",
                        "ischecked" => true,
                    ],
                    8  => [
                        "name"      => "寵物",
                        "ischecked" => true,
                    ],
                    9  => [
                        "name"      => "美食",
                        "ischecked" => true,
                    ],
                    10 => [
                        "name"      => "娛樂",
                        "ischecked" => true,
                    ],
                    11 => [
                        "name"      => "電影/電視",
                        "ischecked" => true,
                    ],
                    12 => [
                        "name"      => "健康養生",
                        "ischecked" => true,
                    ],
                    13 => [
                        "name"      => "數碼",
                        "ischecked" => true
                    ],
                    14 => [
                        "name"      => "其他",
                        "ischecked" => true,
                    ]
                ]
            ],
        ],
    ],
    // 用户注册模块
    Service::MODULE_TYPE_CHIEF_INFO => [
        // 必须开启且必填的字段
        "must_start_required" => env("REQUEST_FIELD_CHIEF_INFO_MUST_START_REQUIRED", "chief_name"),
        // 默认显示的内容，must_start_required的字段必须要存在default中，否则业务逻辑的流程会有问题
        "default" => [
            "chief_name"       => [
                "name"         => "姓名",
                "is_open"      => true,
                "element_type" => "input",
                "is_required"  => true,
                "prompt"       => "請輸入姓名",
            ],
        ],
    ],
];
