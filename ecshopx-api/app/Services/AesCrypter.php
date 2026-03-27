<?php

namespace App\Services;

/**
 * AES 加解密服務的類別。
 */
class AesCrypter
{

    private $Key = null;
    private $IV = null;

    /**
     * AES 加解密服務類別的建構式。
     */
    function __construct($key, $iv)
    {
        $this->Key = $key;
        $this->IV = $iv;
    }

    /**
     * 加密服務的方法。
     */
    function encrypt($data)
    {
        $szData = openssl_encrypt($data, 'AES-128-CBC', $this->Key, OPENSSL_RAW_DATA, $this->IV);
        return base64_encode($szData);
    }

    /**
     * 解密服務的方法。
     */
    function decrypt($data)
    {
        $szValue = openssl_decrypt(base64_decode($data), 'AES-128-CBC', $this->Key, OPENSSL_RAW_DATA, $this->IV);
        return urldecode($szValue);
    }
}
