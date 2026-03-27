<?php

namespace App\Services;

class IdGen
{
    public static function genId($identifier)
    {
        $time = time();
        $startTime = 1672531200;
        $day = floor(($time - $startTime) / 86400);
        $minute = floor(($time - strtotime(date('Y-m-d'))) / 90);
        $redisId = app('redis')->hincrby(date('Ymd'), $minute, random_int(1, 9));
        app('redis')->expire(date('Ymd'), 86400);
        return $day . str_pad($minute, 3, '0', STR_PAD_LEFT) . str_pad($redisId, 5, '0', STR_PAD_LEFT) . str_pad($identifier % 10000, 4, '0', STR_PAD_LEFT);
    }
}
