<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

use ChinaumsPayBundle\Services\ChinaumsPayDivisionService;

class DivisionDetailExportService implements ExportFileInterface
{
    public function exportData($filter)
    {

        $divisionService = new ChinaumsPayDivisionService();
        $count = $divisionService->getDetailCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."分賬單明細";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'division_id' => '指令ID',
            'order_id' => '訂單號',
            'total_fee' => '訂單金額',
            'actual_fee' => '實際金額',
            'commission_rate_fee' => '收單手續費',
            'division_fee' => '分賬金額',
            'create_time' => '創建時間',
        ];
        return $title;
    }
    private function getLists($filter, $count)
    {
        $title = $this->getTitle();

        $divisionService = new ChinaumsPayDivisionService();

        $limit = 500;
        $orderBy = ['id' => 'DESC'];
        $total = ceil($count / $limit);

        for ($i = 1; $i <= $total; $i++) {
            $dataList = [];
            $divisionDetailList = $divisionService->getDetailList($filter, '*', $i, $limit, $orderBy);
            foreach ($divisionDetailList['list'] as $key => $divisionDetail) {
                foreach ($title as $k => $v) {
                    if (in_array($k, ['division_id', 'order_id']) && isset($divisionDetail[$k])) {
                        $dataList[$key][$k] = "\t".$divisionDetail[$k];
                    } elseif (in_array($k, ['total_fee', 'actual_fee', 'division_fee', 'commission_rate_fee']) && isset($divisionDetail[$k])) {
                        if (!$divisionDetail[$k]) {
                            $divisionDetail[$k] = 0;
                        }
                        $dataList[$key][$k] = $divisionDetail[$k] / 100;
                    } elseif (in_array($k, ['create_time']) && isset($divisionDetail[$k]) && $divisionDetail[$k]) {
                        $dataList[$key][$k] = date('Y-m-d H:i:s', $divisionDetail[$k]);
                    } elseif ($k == "back_status" && isset($divisionDetail[$k])) {
                        $dataList[$key][$k] = $backStatus[$divisionDetail[$k]] ?? '--';
                    } else {
                        $dataList[$key][$k] = '--';
                    }
                }
            }
            yield $dataList;
        }
    }
}
