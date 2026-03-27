<?php
return [
    'merchant_id' => Yaconf::get('ecpay.merchant_id', '2000132'),
    'mpos' => [
        'base_uri' => Yaconf::get('ecpay.mpos.base_uri', 'https://ecpg-stage.ecpay.com.tw'),
        'hash_key' => Yaconf::get('ecpay.mpos.hash_key', '5294y06JbISpM5x9'),
        'hash_iv' => Yaconf::get('ecpay.mpos.hash_iv', 'v77hoKGq4kWxNNIS'),
    ],
    'invoice' => [
        'base_uri' => Yaconf::get('ecpay.invoice.base_uri', 'https://einvoice-stage.ecpay.com.tw'),
        'hash_key' => Yaconf::get('ecpay.invoice.hash_key', 'ejCk326UnaZWKisg'),
        'hash_iv' => Yaconf::get('ecpay.invoice.hash_iv', 'q9jcZX8Ib9LM8wYk'),
    ],
    'logistics' => [
        'base_uri' => Yaconf::get('ecpay.logistics.base_uri', 'https://logistics-stage.ecpay.com.tw'),
        'hash_key' => Yaconf::get('ecpay.logistics.base_uri', '5294y06JbISpM5x9'),
        'hash_iv' => Yaconf::get('ecpay.logistics.base_uri', 'v77hoKGq4kWxNNIS'),
    ],
];
