<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

use ChinaumsPayBundle\Services\ChinaumsPayDivisionService;

class DivisionExportService implements ExportFileInterface
{
    public function exportData($filter)
    {

        $divisionService = new ChinaumsPayDivisionService();
        $count = $divisionService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."分賬單";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'id' => '指令ID',
            'total_fee' => '訂單金額',
            'actual_fee' => '實際金額',
            'division_fee' => ' 分賬金額',
            'backsucc_fee' => '回盤成功金額',
            'rate_fee' => '業務處理費',
            'back_status' => '回盤狀態',
            'create_time' => '創建時間',
        ];
        return $title;
    }
    private function getLists($filter, $count)
    {
        $title = $this->getTitle();
        // 回盘状态 0:未处理、1:处理中、2:成功、3:部分成功、4:失败
        $backStatus = [
            '0' => '未處理',
            '1' => '處理中',
            '2' => '成功',
            '3' => '部分成功',
            '4' => '失敗',
        ];

        $divisionService = new ChinaumsPayDivisionService();

        $limit = 500;
        $orderBy = ['id' => 'DESC'];
        $total = ceil($count / $limit);

        for ($i = 1; $i <= $total; $i++) {
            $dataList = [];
            $divisionList = $divisionService->getLists($filter, '*', $i, $limit, $orderBy);
            foreach ($divisionList as $key => $division) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['id']) && isset($division[$k])) {
                        $dataList[$key][$k] = "\t".$division[$k];
                    } elseif (in_array($k, ['total_fee', 'actual_fee', 'division_fee', 'backsucc_fee', 'rate_fee']) && isset($division[$k])) {
                        if (!$division[$k]) {
                            $division[$k] = 0;
                        }
                        $dataList[$key][$k] = $division[$k] / 100;
                    } elseif (in_array($k, ['create_time']) && isset($division[$k]) && $division[$k]) {
                        $dataList[$key][$k] = date('Y-m-d H:i:s', $division[$k]);
                    } elseif ($k == "back_status" && isset($division[$k])) {
                        $dataList[$key][$k] = $backStatus[$division[$k]] ?? '--';
                    } else {
                        $dataList[$key][$k] = '--';
                    }
                }
            }
            yield $dataList;
        }
    }
}
