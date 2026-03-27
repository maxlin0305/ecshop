<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\Rights\LogsService;

class RightConsumeExportService implements ExportFileInterface
{
    private $title = [
        'shop_name' => '門店',
        'salesperson_name' => '核銷員',
        'attendant' => '服務員',
        'rights_name' => '權益',
        'rights_num' => '權益數量',
        'user_name' => '會員',
        'user_sex' => '會員性別',
        'user_mobile' => '會員手機',
        'end_time' => '核銷時間',
    ];
    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $rightsService = new LogsService();
        $count = $rightsService->getCount($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id'];
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $totalNum, $datapassBlock)
    {
        $limit = 1000;
        $rightsService = new LogsService();
        $totalPage = intval(ceil($totalNum / $limit));
        $result = [];
        for ($page = 1; $page <= $totalPage; $page++) {
            $dataList = [];
            $result = $rightsService->getList($filter, $page, $limit);
            foreach ($result['list'] as $value) {
                if ($datapassBlock) {
                    $value['salesperson_mobile'] = data_masking('mobile', (string) $value['salesperson_mobile']);
                }
                if (isset($filter['shop_id'])) {
                    $shopName[$filter['shop_id']] = $value['shop_name'];
                }
                $dataList[] = [
                    'shop_name' => $value['shop_name'],
                    'salesperson_name' => $value['name'],
                    'attendant' => $value['attendant'],
                    'rights_name' => $value['rights_name'],
                    'rights_num' => $value['consum_num'],
                    'user_name' => $value['user_name'],
                    'user_sex' => ($value['user_sex'] == 0) ? '性別未知' : (($value['user_sex'] == 1) ? '男' : '女'),
                    'user_mobile' => $value['user_mobile'],
                    'end_time' => date('Y-m-d H:i:s', $value['end_time']),
                ];
            }
            yield $dataList;
        }
    }
}
