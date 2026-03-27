<?php

namespace EspierBundle\Services\Export;

use AftersalesBundle\Services\AftersalesService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class AftersalesFinancialExportService implements ExportFileInterface
{
    private $title = [
        'refund_bn' => '退款單號',
        'aftersales_bn' => '售後單號',
        'order_id' => '訂單號',
        'refund_status' => '退款狀態',
        'refund_fee' => '退款金額',
        'refund_point' => '退款積分',
        'create_time' => '創建時間',
        'refund_success_time' => '退款成功時間',
    ];

    public function exportData($filter)
    {
        $aftersalesService = new AftersalesService();
        $count = $aftersalesService->count($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_退款單';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        if ($count > 0) {
            $aftersalesService = new AftersalesService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $aftersalesService->exportFinancialAftersalesList($filter, $page, $limit, ["create_time" => "DESC"]);
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == 'refund_success_time' || $k == 'create_time') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif (in_array($k, ['order_id', 'refund_bn', 'aftersales_bn']) && isset($value[$k])) {
                            $recordData[$key][$k] = "\"'".$value[$k]."\"";
                        } elseif ($k == 'refund_fee') {
                            $recordData[$key][$k] = $value[$k] / 100;
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
