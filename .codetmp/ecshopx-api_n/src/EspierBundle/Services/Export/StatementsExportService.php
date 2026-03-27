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
        'statement_no' => '结算单号',
        'merchant_name' => '商家',
        'distributor_name' => '店铺',
        'order_num' => '订单数量',
        'total_fee' => '订单实付',
        'freight_fee' => '运费',
        'intra_city_freight_fee' => '同城配',
        'refund_fee' => '退款金额',
        'statement_fee' => '结算金额',
        'statement_period' => '结算周期',
        'confirm_time' => '确认时间',
        'statement_time' => '结算时间',
        'statement_status' => '结算状态',
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
                                $result[$key][$k] = '已结算';
                            } elseif ($value['statement_status'] == 'confirmed') {
                                $result[$key][$k] = '待平台结算';
                            } else {
                                $result[$key][$k] = '待商家确认';
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
