<?php

namespace EspierBundle\Services\Export;

use AftersalesBundle\Services\AftersalesService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Services\TradeService;

class AftersalesRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name' => '店鋪名稱',
        'shop_code' => '店鋪號',
        'aftersales_bn' => '售後單號',
        'order_id' => '訂單號',
        'trade_no' => '訂單序號',
        'item_bn' => '商品編號',
        'item_name' => '商品名稱',
        'num' => '數量',
        'aftersales_type' => '售後類型',
        'aftersales_status' => '售後狀態',
        'create_time' => '創建時間',
        'refund_fee' => '退款金額',
        'progress' => '處理進度',
        'description' => '申請描述',
        'reason' => '申請售後原因',
        'refuse_reason' => '拒絕原因',
        'memo' => '售後備註'
    ];

    public function exportData($filter)
    {
        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_售後列表';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $aftersales_type = [
            'ONLY_REFUND' => '僅退款',
            'REFUND_GOODS' => '退貨退款',
            'EXCHANGING_GOODS' => '換貨',
        ];

        $aftersales_status = [
            0 => '待處理',
            1 => '處理中',
            2 => '已處理',
            3 => '已駁回',
            4 => '已關閉',
        ];

        $progress = [
            0 => '等待商家處理',
            1 => '商家接受申請，等待消費者回寄',
            2 => '消費者回寄，等待商家收貨確認',
            3 => '已駁回',
            4 => '已處理',
            5 => '退款駁回',
            6 => '退款完成',
            7 => '售後關閉',
            8 => '商家確認收貨,等待審核退款',
            9 => '退款處理中',
        ];

        if ($count > 0) {
            $aftersalesService = new AftersalesService();
            $tradeService = new TradeService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $aftersalesService->exportAftersalesList($filter, $page, $limit, ["create_time" => "DESC"]);

                $orderIdList = array_column($data['list'], 'order_id');
                $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

                foreach ($data['list'] as $key => $value) {
                    $value['trade_no'] = $tradeIndex[$value['order_id']] ?? '-';
                    foreach ($title as $k => $v) {
                        if ($k == 'create_time') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['order_id', 'aftersales_bn']) && isset($value[$k])) {
                            $recordData[$key][$k] = "\"'".$value[$k]."\"";
                        } elseif ($k == 'refund_fee') {
                            $recordData[$key][$k] = $value[$k] / 100;
                        } elseif ($k == "aftersales_type") {
                            $recordData[$key][$k] = $aftersales_type[$value[$k]] ?? '--';
                        } elseif ($k == "aftersales_status") {
                            $recordData[$key][$k] = $aftersales_status[$value[$k]] ?? '--';
                        } elseif ($k == "progress") {
                            $recordData[$key][$k] = $progress[$value[$k]] ?? '--';
                        } elseif ($k == 'item_bn' && is_numeric($value[$k])) {
                            $recordData[$key][$k] = "\"'".$value[$k]."\"";
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
