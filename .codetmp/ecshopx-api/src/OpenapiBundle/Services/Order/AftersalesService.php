<?php

namespace OpenapiBundle\Services\Order;

use AftersalesBundle\Entities\Aftersales;
use OpenapiBundle\Services\BaseService;

class AftersalesService extends BaseService
{
    public function getEntityClass(): string
    {
        return Aftersales::class;
    }

    /**
     * 格式化售后列表
     * @param  array $dataList 订单列表数据
     * @param  int    $page     当前页数
     * @param  int    $pageSize 每页条数
     * @return array
     */
    public function formateAftersalesList($dataList, int $page, int $pageSize)
    {
        $result = $this->handlerListReturnFormat($dataList, $page, $pageSize);
        if (empty($dataList['list'])) {
            return $result;
        }
        $result['list'] = [];
        foreach ($dataList['list'] as $list) {
            $_list = [
                'aftersales_bn' => $list['aftersales_bn'],
                'order_id' => $list['order_id'],
                'aftersales_type' => $list['aftersales_type'],
                'aftersales_status' => $list['aftersales_status'],
                'progress' => $list['progress'],
                'refund_fee' => $list['refund_fee'],
                'refund_point' => $list['refund_point'],
                'reason' => $list['reason'],
                'description' => $list['description'],
                'evidence_pic' => $list['evidence_pic'],
                'refuse_reason' => $list['refuse_reason'],
                'memo' => $list['memo'],
                'sendback_data' => $list['sendback_data'],
                'sendconfirm_data' => $list['sendconfirm_data'],
                'create_time' => date('Y-m-d H:i:s', $list['create_time']),
                'update_time' => date('Y-m-d H:i:s', $list['update_time']),
                'aftersales_address' => $list['aftersales_address'],
                'detail' => $this->formateDetail($list['detail']),

            ];
            $result['list'][] = $_list;
        }
        return $result;
    }

    /**
     * 格式化售后列表的详情字段数据
     * @param  array $detail 售后详情数据
     */
    private function formateDetail($detail): array
    {
        $_detail = [];
        foreach ($detail as $value) {
            $_detail[] = [
                'item_bn' => $value['item_bn'],
                'item_name' => $value['item_name'],
                // 'order_item_type' => $value['order_item_type'],
                'item_pic' => $value['item_pic'],
                'num' => $value['num'],
                'refund_fee' => $value['refund_fee'],
                'refund_point' => $value['refund_point'],
                'aftersales_type' => $value['aftersales_type'],
                'progress' => $value['progress'],
                'aftersales_status' => $value['aftersales_status'],
                'create_time' => date('Y-m-d H:i:s', $value['create_time']),
                'update_time' => date('Y-m-d H:i:s', $value['create_time']),
                'auto_refuse_time' => $value['auto_refuse_time'] ? date('Y-m-d H:i:s', $value['auto_refuse_time']) : '',
            ];
        }
        return $_detail;
    }

    /**
     * 格式化售后详情数据
     * @param  array $detail 售后详情数据
     */
    public function formateAftersalesDetail($detail): array
    {
        $_detail = [
            'aftersales_bn' => $detail['aftersales_bn'],
            'order_id' => $detail['order_id'],
            'aftersales_type' => $detail['aftersales_type'],
            'aftersales_status' => $detail['aftersales_status'],
            'progress' => $detail['progress'],
            'refund_fee' => $detail['refund_fee'],
            'refund_point' => $detail['refund_point'],
            'reason' => $detail['reason'],
            'description' => $detail['description'],
            'evidence_pic' => $detail['evidence_pic'],
            'refuse_reason' => $detail['refuse_reason'],
            'memo' => $detail['memo'],
            'sendback_data' => $detail['sendback_data'],
            'create_time' => date('Y-m-d H:i:s', $detail['create_time']),
            'update_time' => date('Y-m-d H:i:s', $detail['update_time']),
            'aftersales_address' => $detail['aftersales_address'],
            'detail' => $this->formateDetail($detail['detail']),
        ];
        return $_detail;
    }
}
