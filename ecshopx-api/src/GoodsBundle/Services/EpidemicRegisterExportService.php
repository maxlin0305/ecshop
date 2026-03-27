<?php

namespace GoodsBundle\Services;

use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\OrderEpidemicService;
use OrdersBundle\Services\OrderItemsService;

class EpidemicRegisterExportService
{
    private $title = [
        'order_id' => '訂單號',
        'name' => '姓名',
        'mobile' => '手機號',
        'cert_id' => '身份證號',
        'temperature' => '體溫',
        'job' => '職業',
        'symptom' => '症狀',
        'symptom_des' => '症狀描述',
        'distributor_id' => '店鋪ID',
        'distributor_name' => '店鋪名稱',
        'item_name' => '商品名稱',
        'item_bn' => '商品編碼',
        'barcode' => '商品條碼',
        'num' => '商品數量',
        'is_risk_area' => '14天內是否去過中高風險地區',
        'created' => '登記時間',
    ];

    public function exportData($filter)
    {
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderEpidemicService = new OrderEpidemicService();
        $count = $orderEpidemicService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."疫情防控登記列表";
        $list = $this->getLists($filter, $count, $datapassBlock);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $list);

        return $result;
    }

    public function getLists($filter, $count, $datapassBlock)
    {
        $isRiskArea = ['否','是'];
        $noProcessCols = ['name', 'mobile', 'temperature', 'job', 'symptom', 'symptom_des', 'distributor_name', 'distributor_id', 'created', 'item_name', 'item_bn', 'barcode', 'num'];
        $limit = 500;
        $fileNum = ceil($count / $limit);
        $orderEpidemicService = new OrderEpidemicService();
        $orderItemsService = new OrderItemsService();
        $itemsService = new ItemsService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $list = [];
            $data = $orderEpidemicService->epidemicRegisterListService($filter, '*', $j, $limit, ['created' => 'DESC']);
            foreach ($data['list'] as $key => &$value) {
                if ($datapassBlock) {
                    $value['name'] = data_masking('truename', $value['name']);
                    $value['mobile'] = data_masking('mobile', $value['mobile']);
                    $value['cert_id'] = data_masking('idcard', $value['cert_id']);
                }
                $orderItems = $orderItemsService->getList(
                    [
                        'company_id' => $value['company_id'],
                        'user_id' => $value['user_id'],
                        'order_id' => $value['order_id'],
                    ]
                );
                $num = 0;
                foreach ($orderItems['list'] as $orderItem) {
                    $num += $orderItem['num'];
                }
                $value['num'] = $num;

                $itemIds = array_column($orderItems['list'], 'item_id');
                $items = $itemsService->getLists(
                    [
                        'company_id' => $value['company_id'],
                        'item_id' => $itemIds,
                    ]
                );
                $itemName = '';
                $itemBn = '';
                $barcode = '';
                foreach ($items as $item) {
                    $itemName .= $item['item_name'] . "\n";
                    $itemBn .= $item['item_bn'] . "\n";
                    $barcode .= $item['barcode'] ? $item['barcode']."\n" : "--\n";
                }
                $value['item_name'] = $itemName;
                $value['item_bn'] = $itemBn;
                $value['barcode'] = $barcode;

                foreach ($this->title as $k => $v) {
                    if (in_array($k, $noProcessCols) && isset($value[$k])) {
                        $list[$key][$k] = $value[$k];
                    } elseif (in_array($k, ['order_id', 'cert_id']) && isset($value[$k])) {
                        $list[$key][$k] = "\t".$value[$k]."\t";
                    } elseif ($k == 'is_risk_area' && isset($value[$k])) {
                        $list[$key][$k] = $isRiskArea[$value[$k]];
                    } else {
                        $list[$key][$k] = '--';
                    }
                }
            }
            yield $list;
        }
    }
}
