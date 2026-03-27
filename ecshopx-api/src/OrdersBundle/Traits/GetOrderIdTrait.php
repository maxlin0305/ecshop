<?php

namespace OrdersBundle\Traits;

use OrdersBundle\Entities\NormalOrdersRelDada;

trait GetOrderIdTrait
{
    public function genId($identifier)
    {
        // 压测默认，订单号生成方式修改;
        if (env('TEST_MODE', false) == true) {
            return date('ymdhis').substr(implode(null, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 7);
        }
        $time = time();
        $startTime = 1325347200;//2012-01-01 做为初始年
        //当前时间相距初始年的天数，4位可使用20年
        $day = floor(($time - $startTime) / 86400);

        //确定每90秒的的订单生成 一天总共有960个90秒，控制在三位
        $minute = floor(($time - strtotime(date('Y-m-d'))) / 90);

        //防止通过订单号计算出商城生成的订单数量，导致泄漏关键数据
        $redisId = app('redis')->hincrby(date('Ymd'), $minute, rand(1, 9));

        //设置过期时间
        app('redis')->expire(date('Ymd'), 86400);

        $id = $day . str_pad($minute, 3, '0', STR_PAD_LEFT) . str_pad($redisId, 5, '0', STR_PAD_LEFT) . str_pad($identifier % 10000, 4, '0', STR_PAD_LEFT);//16位

        return $id;
    }

    /**
     * 根据达达的状态，获取订单号，将订单号作为筛选条件
     * @param  array $filter 筛选条件
     * @return array filter  处理后的筛选条件
     */
    public function getOrderIdByDadaStatus($filter)
    {
        if (isset($filter['order_status']) && $filter['order_status'] && is_string($filter['order_status'])) {
            $order_status = explode('_', $filter['order_status']);
            if ($order_status[0] == 'DADA') {
                $normalOrdersRelDadaRepository = app('registry')->getManager('default')->getRepository(NormalOrdersRelDada::class);
                $dada_filter = [
                    'company_id' => $filter['company_id'],
                    'dada_status' => $order_status[1],
                ];
                $relDaDaList = $normalOrdersRelDadaRepository->getLists($dada_filter, 'order_id');
                $filter['order_id'] = array_column($relDaDaList, 'order_id');
                unset($filter['order_status']);
            }
        }
        return $filter;
    }
}
