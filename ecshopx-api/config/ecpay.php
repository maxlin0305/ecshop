<?php
return [
    'merchant_id' => Yaconf::get('ecpay.merchant_id', '3002607'),
    'mpos' => [
        'base_uri' => Yaconf::get('ecpay.mpos.base_uri', 'https://ecpg-stage.ecpay.com.tw'),
        'base_uri_ext' => Yaconf::get('ecpay.mpos.base_uri_ext', 'https://ecpayment-stage.ecpay.com.tw'),
        'hash_key' => Yaconf::get('ecpay.mpos.hash_key', 'pwFHCqoQZGmho4w6'),
        'hash_iv' => Yaconf::get('ecpay.mpos.hash_iv', 'EkRm7iFT261dpevs'),
    ],
    'invoice' => [
        'base_uri' => Yaconf::get('ecpay.invoice.base_uri', 'https://einvoice-stage.ecpay.com.tw'),
        'hash_key' => Yaconf::get('ecpay.invoice.hash_key', 'pwFHCqoQZGmho4w6'),
        'hash_iv' => Yaconf::get('ecpay.invoice.hash_iv', 'EkRm7iFT261dpevs'),
    ],
    'logistics' => [
        'base_uri' => Yaconf::get('ecpay.logistics.base_uri', 'https://logistics-stage.ecpay.com.tw'),
        'hash_key' => Yaconf::get('ecpay.logistics.base_uri', '5294y06JbISpM5x9'),
        'hash_iv' => Yaconf::get('ecpay.logistics.base_uri', 'v77hoKGq4kWxNNIS'),
    ],
];
