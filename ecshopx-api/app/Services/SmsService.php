<?php

namespace App\Services;

class SmsService
{
    public static function send($addr, $body)
    {
        $curl = curl_init();
        $url = config('mitake_sms.base_uri') . '/api/mtk/SmSend?CharsetURL=UTF-8';
        $data = 'username=' . config('mitake_sms.username');
        $data .= '&password=' . config('mitake_sms.password');
        $data .= '&dstaddr=' . $addr;
        $data .= '&smbody=' . $body;

        app('log')->info("发送验证码".$data);
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                "Content-type: application/x-www-form-urlencoded"
            ],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
        ]);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
