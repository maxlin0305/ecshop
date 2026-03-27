<?php

namespace PopularizeBundle\Services;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

class PopularizeExportService implements ExportFileInterface
{
    private $title = [
        'username' => '姓名',
        'mobile' => '手機號',
        'cashWithdrawalRebate' => '可提現',
        'payedRebate' => '已提現',
        'freezeCashWithdrawalRebate' => '申請提現',
        'noCloseRebate' => '未結算',
        'rebateTotal' => '傭金總額',
        'itemTotalPrice' => '商品總額',
        'noClosePoint' => '未結算積分',
        'pointTotal' => '積分總額',
    ];

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $promoterService = new PromoterService();
        $data = $promoterService->getPromoterList($filter, 1, 1);
        $count = $data['total_count'];
        if ($count <= 0) {
            return [];
        }
        $isGetSkuList = [];
        $fileName = date('YmdHis')."popularize";
        $dataList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $dataList);
        return $result;
    }

    public function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->title;
        $limit = 500;
        $totalPage = ceil($count / $limit);
        $promoterService = new PromoterService();
        for ($i = 1; $i <= $totalPage; $i++) {
            $promoterData = [];
            $result = $promoterService->getPromoterList($filter, $i, $limit);
            if ($result['total_count'] > 0) {
                $promoterCountService = new PromoterCountService();
                foreach ($result['list'] as $k => $row) {
                    $count = $promoterCountService->getPromoterCount($filter['company_id'], $row['user_id']);
                    $result['list'][$k] = array_merge($result['list'][$k], $count);
                }
            }

            $list = $result['list'];
            foreach ($list as $key => $value) {
                $username = $value['username'] ?? '';
                $mobile = $value['mobile'] ?? '';
                if ($datapassBlock) {
                    $username = data_masking('truename', (string) $username);
                    $mobile = data_masking('mobile', (string) $mobile);
                }
                foreach ($title as $k => $val) {
                    if ($k == 'username') {
                        $promoterData[$key][$k] = $username;
                    } else if ($k == 'mobile') {
                        $promoterData[$key][$k] = $mobile;
                    } else if (isset($value[$k])) {
                        switch ($k) {
                            case 'noClosePoint':
                            case 'pointTotal':
                                $promoterData[$key][$k] = $value[$k];
                                break;
                            default:
                                $promoterData[$key][$k] = round(intval($value[$k])/100, 2);
                        }
                    }
                }
            }
            yield $promoterData;
        }
    }
}
