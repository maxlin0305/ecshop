<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\RightsService;
use OrdersBundle\Services\Rights\TimesCardService;
use EspierBundle\Services\ExportFileService;

class ServiceOrderExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;

    private $title = [
        'order_id' => '訂單號',
        'title' => '訂單標題',
        'store_name' => '所屬門店',
        'create_date' => '下單時間',
        'mobile' => '手機號',
        'total_fee' => '訂單價格',
        'source_name' => '來源名稱',
        'order_source' => '訂單來源',
        'operator_desc' => '操作員手機及姓名',
        'rights_title' => '購買課程',
        'num' => '課程數',
    ];

    public function exportData($filter)
    {
        // 是否需要數據脫敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $orderService = $this->getOrderService('service');
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id'];
        $orderList = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $orderService = $this->getOrderService('service');
        $limit = 500;
        $title = $this->title;
        $fileNum = ceil($count / $limit);
        $rightsObj = new RightsService(new TimesCardService());
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderList = [];
            $orderdata = $orderService->getOrderList($filter, $j, $limit);
            foreach ($orderdata['list'] as $key => $value) {
                if ($datapassBlock) {
                    $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                }
                //獲取訂單權益
                $rightsFilter = [
                    'company_id' => $value['company_id'],
                    'order_id' => $value['order_id']
                ];
                $rights = $rightsObj->getRightsList($rightsFilter);
                if ($rights['list']) {
                    foreach ($rights['list'] as $i => $rightlist) {
                        foreach ($title as $k => $v) {
                            if ($k == "order_id" && isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = "\"'".$value[$k]."\"";
                            } elseif ($k == "total_fee" && isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = $value[$k] / 100;
                            } elseif ($k == "order_source" && isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = ($value[$k] == 'shop') ? '代客下單' : '會員自主下單';
                            } elseif (isset($value[$k])) {
                                $orderList[$key."-".$i][$k] = $value[$k];
                            } else {
                                $orderList[$key."-".$i][$k] = '';
                            }
                        }
                        $orderList[$key."-".$i]['rights_title'] = $rightlist['rights_subname'];
                        $orderList[$key."-".$i]['num'] = $rightlist['total_num'];
                    }
                } else {
                    foreach ($title as $k => $v) {
                        if ($k == "order_id" && isset($value[$k])) {
                            $orderList[$key][$k] = "\"'".$value[$k]."\"";
                        } elseif ($k == "total_fee" && isset($value[$k])) {
                            $orderList[$key][$k] = $value[$k] / 100;
                        } elseif ($k == "order_source" && isset($value[$k])) {
                            $orderList[$key][$k] = ($value[$k] == 'shop') ? '代客下單' : '會員自主下單';
                        } elseif (isset($value[$k])) {
                            $orderList[$key][$k] = $value[$k];
                        } else {
                            $orderList[$key][$k] = '';
                        }
                    }
                    $orderList[$key]['rights_title'] = '未知';
                    $orderList[$key]['num'] = '0';
                }
            }
            yield $orderList;
        }
    }
}
