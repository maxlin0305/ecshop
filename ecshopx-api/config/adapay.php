<?php
// 聚合支付配置文件

return [
    'notify_url' => env('ADAPAY_AGENT_NOTIFY_URL'),
    'agent_url' => env('ADAPAY_AGENT_URL', 'http://agent.shopex123.com/api/ecshopx'),
    'agent_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDRVfRTHRLZOSodX5jhbwfLXGeCtmTxhPd7lxSiFdh4tlb20/JCU0zu1NI9c9gL56MRRURk9FGKEmc+stZQxiCao9lI8MmwzNecKAu8OVxTKSAXy40PZsOs58RrpOiBgk71piMrcaXBg2ji0N0IOVYjbV52ermPA8A5uOSCJ2awOwIDAQAB',
    
    //商户菜单控制  
    //valid 已开户 invalid 未开户; example: admin->valid 主商户已开户需要禁用的菜单项; dealer->invalid (adapay)经销商未开户需要禁用的菜单项
    //'adapay_merchant' => 2   key表示菜单的alias_name, 数值表示level层级
    'deny_menus' => [
        //主商户
        'admin' => [
            'succ' => ['/applications/adapay/adapay_merchant' => 3],
            'fail' => [
                '/shop_dealer' => 1, '/applications/adapay/adapay_merchant_info' => 3, '/applications/adapay/adapay_member_audit' => 3,'/applications/adapay/adapay_trades' => 3, '/applications/adapay/adapay_cash' => 3, '/applications/adapay/adapay_cash_setting' => 3],
        ],
        //adapay经销商端
        'dealer' => [
            'succ' => ['/dealer/adapay_member/entry' => 2],
            'fail' => ['/dealer/adapay_member/info' => 2, '/dealer/trades/adapay_trades' => 2, '/dealer/trades/adapay_cash' => 2],
            'sub_deny' => ['/dealer/setting/account_management' => 2],
        ],
        //店铺端
        'distributor' => [
            'admin_fail' => ['/shopadmin/applications' => 1],
            'succ' => ['/shopadmin/applications/adapay/adapay_member_entry' => 3],
            'fail' => ['/shopadmin/applications/adapay/adapay_trades' => 3, '/shopadmin/applications/adapay/adapay_cash' => 3, '/shopadmin/applications/adapay/adapay_member_info' => 3],
        ]
    ],
];
