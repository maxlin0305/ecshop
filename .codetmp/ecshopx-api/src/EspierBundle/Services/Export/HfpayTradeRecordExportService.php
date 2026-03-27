<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use HfPayBundle\Services\HfpayDistributorTransactionStatisticsService;

class HfpayTradeRecordExportService implements ExportFileInterface
{
    private $title = [
        'distributor_name' => '店鋪名稱',
        'withdrawal_balance' => '可提現金額',
        'order_count' => '交易總筆數',
        'order_total_fee' => '總交易金額',
        'order_refund_count' => '已退款總筆數',
        'order_refund_total_fee' => '退款總金額',
        'order_refunding_count' => '在退總筆數',
        'order_refunding_total_fee' => '在退總金額',
        'order_profit_sharing_charge' => '已結算手續費總額',
        'order_un_profit_sharing_charge' => '未結算手續費總額',
    ];

    public function exportData($filter)
    {
        $service = new HfpayDistributorTransactionStatisticsService();
        $count = $service->transactionCount($filter);

        $fileName = date('YmdHis') . '_匯付分賬交易';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);

        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        $service = new HfpayDistributorTransactionStatisticsService();
        $limit = 500;
        $fileNum = ceil($count / $limit);
        for ($page = 1; $page <= $fileNum; $page++) {
            $recordData = [];
            $data = $service->transactionList($filter, $page, $limit);
            if (!empty($data['list'])) {
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == "withdrawal_balance" || $k == "order_total_fee" || $k == "order_refund_total_fee" || $k == "order_refunding_total_fee" || $k == "order_profit_sharing_charge" || $k == "order_un_profit_sharing_charge") {
                            $recordData[$key][$k] = bcdiv($value[$k], 100, 2);
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
