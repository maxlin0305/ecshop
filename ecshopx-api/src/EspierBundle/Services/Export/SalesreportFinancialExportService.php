<?php

namespace EspierBundle\Services\Export;

use OrdersBundle\Services\OrderItemsService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class SalesreportFinancialExportService implements ExportFileInterface
{
    private $title = [
        'order_id' => '訂單號',
        'barnd' => '品牌',
        'main_category' => '商品品類',
        'create_time' => '下單日期',
        'delivery_time' => '發貨日期',
        'item_fee' => '商品價格',
        'discount_fee' => '折扣金額',
        'total_fee' => '折後金額',
    ];

    public function exportData($filter)
    {
        $orderItemsService = new OrderItemsService();
        $count = $orderItemsService->salesReportCount($filter);

        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_財務銷售報表';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        if ($count > 0) {
            $orderItemsService = new OrderItemsService();

            $data = $orderItemsService->exportFinancialSalesreport($filter);
            foreach ($data['list'] as $key => $value) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['create_time','delivery_time'])) {
                        $recordData[$key][$k] = $value[$k] ? date('Y-m-d H:i:s', $value[$k]) : '';
                    } elseif (in_array($k, ['order_id']) && isset($value[$k])) {
                        $recordData[$key][$k] = "\"'".$value[$k]."\"";
                    } elseif (in_array($k, ['item_fee','discount_fee','total_fee'])) {
                        $recordData[$key][$k] = $value[$k] ? bcdiv($value[$k], 100, 2) : 0;
                    } else {
                        $recordData[$key][$k] = $value[$k] ?? '';
                    }
                }
            }
            yield $recordData;
        }
    }
}
