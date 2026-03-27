<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\StatementsService;
use DistributionBundle\Services\DistributorService;
use MerchantBundle\Services\MerchantService;

class StatementsExportService implements ExportFileInterface
{
    private $title = [
        'statement_no' => '結算單號',
        'merchant_name' => '商家',
        'distributor_name' => '店鋪',
        'order_num' => '訂單數量',
        'total_fee' => '訂單實付',
        'freight_fee' => '運費',
        'intra_city_freight_fee' => '同城配',
        'refund_fee' => '退款金額',
        'statement_fee' => '結算金額',
        'statement_period' => '結算周期',
        'confirm_time' => '確認時間',
        'statement_time' => '結算時間',
        'statement_status' => '結算狀態',
    ];

    public function exportData($filter)
    {
        $statementsService = new StatementsService();
        $count = $statementsService->count($filter);
        if (!$count) {
            return [];
        }

        $fileName = date('YmdHis').$filter['company_id'].'statements';
        $list = $this->getList($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $list);
        return $result;
    }

    private function getList($filter, $count)
    {
        $statementsService = new StatementsService();
        $distributorService = new DistributorService();
        $merchantService = new MerchantService();

        $limit = 500;
        $orderBy = ['created' => 'DESC'];
        $title = $this->title;
        $pageNum = ceil($count / $limit);
        for ($page = 1; $page <= $pageNum; $page++) {
            $result = [];
            $list = $statementsService->getLists($filter, '*', $page, $limit, $orderBy);

            if (count($list) > 0) {
                $distributorList = $distributorService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,name');
                $distributorName = array_column($distributorList, 'name', 'distributor_id');

                $merchantList = $merchantService->getLists(['id' => array_column($list, 'merchant_id')], 'id,merchant_name');
                $merchantName = array_column($merchantList, 'merchant_name', 'id');
            }

            foreach ($list as $key => $value) {
                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'statement_no':
                            $result[$key][$k] = "\"'".$value[$k]."\"";
                            break;
                        case 'total_fee':
                        case 'freight_fee':
                        case 'intra_city_freight_fee':
                        case 'refund_fee':
                        case 'statement_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'merchant_name':
                            $result[$key][$k] = $merchantName[$value['merchant_id']] ?? '-';
                            break;
                        case 'distributor_name':
                            $result[$key][$k] = $distributorName[$value['distributor_id']] ?? '-';
                            break;
                        case 'statement_period':
                            $result[$key][$k] = date('Y-m-d H:i:s', $value['start_time']).'~'.date('Y-m-d H:i:s', $value['end_time']);
                            break;
                        case 'confirm_time':
                        case 'statement_time':
                            if ($value['statement_time']) {
                                $result[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                            } else {
                                $result[$key][$k] = '-';
                            }
                            break;
                        case 'statement_status':
                            if ($value['statement_status'] == 'done') {
                                $result[$key][$k] = '已結算';
                            } elseif ($value['statement_status'] == 'confirmed') {
                                $result[$key][$k] = '待平臺結算';
                            } else {
                                $result[$key][$k] = '待商家確認';
                            }
                            break;
                        default:
                            $result[$key][$k] = $value[$k];
                            break;
                    }
                }
            }
            yield $result;
        }
    }
}
