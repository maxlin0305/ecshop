<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use HfPayBundle\Services\HfpayCashRecordService;

class HfpayWithdrawRecordExportService implements ExportFileInterface
{
    private $title = [
        'created_at' => '日期',
        'order_id' => '提現訂單號',
        'bind_card_id' => '到賬銀行卡號',
        'trans_amt' => '提現金額',
        'distributor_name' => '店鋪名稱',
        'login_name' => '操作人',
        'cash_status' => '訂單狀態',
        'resp_desc' => '備註',
    ];

    public function exportData($filter)
    {
        $hfpayCashRecordService = new HfpayCashRecordService();
        $count = $hfpayCashRecordService->count($filter);

        $fileName = date('YmdHis') . '_店鋪提現記錄';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);

        app('log')->debug('队列导出: '. var_export($result, 1));

        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;
        $cash_status = [
            2 => '提現成功',
            3 => '提現失敗',
        ];

        $hfpayCashRecordService = new HfpayCashRecordService();
        $limit = 500;
        $fileNum = ceil($count / $limit);
        for ($page = 1; $page <= $fileNum; $page++) {
            $recordData = [];
            $data = $hfpayCashRecordService->lists($filter, $page, $limit, '*', ["created_at" => "DESC"]);
            if (!empty($data['list'])) {
                foreach ($data['list'] as $key => $value) {
                    foreach ($title as $k => $v) {
                        if ($k == 'order_id' || $k == 'bind_card_id') {
                            $recordData[$key][$k] = "\"'" . $value[$k] . "\"";
                        } elseif ($k == 'cash_status') {
                            if (in_array($value[$k], [0, 1])) {
                                $recordData[$key][$k] = '提现中';
                            } else {
                                $recordData[$key][$k] = $cash_status[$value[$k]] ?? '--';
                            }
                        } elseif ($k == "trans_amt") {
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
