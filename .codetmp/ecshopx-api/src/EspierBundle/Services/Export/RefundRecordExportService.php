<?php

namespace EspierBundle\Services\Export;

use OrdersBundle\Entities\Trade;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Services\TradeService;

class RefundRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name'  => '店鋪名稱',
        'shop_code' => '店鋪號',
        'refund_bn' => '退款單號',
        'aftersales_bn' => '售後單號',
        'order_id' => '訂單號',
        'trade_no' => '訂單序號',
        'refund_type' => '退款類型',
        'refund_channel' => '退款方式',
        'refund_status' => '退款狀態',
        'refund_fee' => '應退金額',
        'refunded_fee' => '實退金額',
        'refund_point' => '退款積分',
        'create_time' => '創建時間',
        'refund_success_time' => '退款成功時間',
    ];

    public function exportData($filter)
    {
        $aftersalesRefundService = new AftersalesRefundService();
        $count = $aftersalesRefundService->refundCount($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_退款列表';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $refund_type = [
            0 => '售後',
            1 => '售前',
            2 => '拒單',
        ];

        $refund_channel = [
            'offline' => '線下退回',
            'original' => '原路退回',
        ];

        $refund_status = [
            'AUDIT_SUCCESS' => '審核成功待退款',
            'SUCCESS' => '退款成功',
            'REFUSE' => '退款駁回',
            'CANCEL' => '撤銷退款',
            'REFUNDCLOSE' => '退款關閉',
            'PROCESSING' => '已發起退款等待到賬',
            'CHANGE' => '退款異常',
        ];

        if ($count > 0) {
            $aftersalesRefundService = new AftersalesRefundService();
            $tradeService = new TradeService();

            //获取交易单信息
            $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);

            $orderBy = ['create_time' => 'DESC'];
            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($j = 1; $j <= $fileNum; $j++) {
                $recordData = [];
                $data = $aftersalesRefundService->getAftersalesRefundList($filter, $orderBy, $limit, $j);

                $orderIdList = array_column($data['list'], 'order_id');
                $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

                foreach ($data['list'] as $key => $value) {
                    $tradeInfo = $tradeRepository->getTradeList([
                        'company_id' => $filter['company_id'],
                        'order_id' => $value['order_id']
                    ]);
                    $value['trade_no'] = $tradeIndex[$value['order_id']] ?? '-';
                    foreach ($title as $k => $v) {
                        if (in_array($k, ['create_time', 'refund_success_time'])) {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['refund_bn', 'aftersales_bn', 'order_id']) && isset($value[$k])) {
                            $recordData[$key][$k] = "\"'".$value[$k]."\"";
                        } elseif (in_array($k, ['refund_fee', 'refunded_fee'])) {
                            $recordData[$key][$k] = $value[$k] / 100;
                        } elseif ($k == "refund_type") {
                            $recordData[$key][$k] = $refund_type[$value[$k]] ?? '--';
                        } elseif ($k == "refund_channel") {
                            $recordData[$key][$k] = $refund_channel[$value[$k]] ?? '--';
                        } elseif ($k == "refund_status") {
                            $recordData[$key][$k] = $refund_status[$value[$k]] ?? '--';
                        } elseif ($k == "pay_time") {
                            $recordData[$key][$k] = $tradeInfo['list'][0]['payDate'] ?? '--';
                        } else {
                            $recordData[$key][$k] = $value[$k] ?? '';
                        }
                    }
                }
                yield $recordData;
            }
        }
    }
}
