<?php

namespace App\Services;

/**
 * 網頁跳轉的類別。
 */
class ToURL
{

    /**
     * 網頁跳轉類別的建構式。
     */
    function __construct()
    {
        $this->ToURL();
    }


    /**
     * 網頁跳轉類別的實體。
     */
    function ToURL()
    {

    }

    /**
     * 跳轉至3D頁面的方法。
     */
    function To3DURL($data)
    {
        echo " <script   language = 'javascript' type = 'text/javascript'> ";
        echo " window.location.href = '$data' ";
        echo " </script > ";
    }
}
