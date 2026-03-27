<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\StatementDetailsService;
use DistributionBundle\Services\DistributorService;
use MerchantBundle\Services\MerchantService;

class StatementDetailsExportService implements ExportFileInterface
{
    private $title = [
        'order_id' => '訂單號',
        'distributor_name' => '店鋪',
        'total_fee' => '訂單實付',
        'freight_fee' => '運費',
        'intra_city_freight_fee' => '同城配',
        'refund_fee' => '退款金額',
        'statement_fee' => '結算金額',
        'created' => '創建時間',
        'pay_type' => '支付方式',
    ];

    public function exportData($filter)
    {
        $detailsService = new StatementDetailsService();
        $count = $detailsService->count($filter);
        if (!$count) {
            return [];
        }

        $fileName = date('YmdHis').$filter['company_id'].'statement_details';
        $list = $this->getList($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $list);
        return $result;
    }

    private function getList($filter, $count)
    {
        $detailsService = new StatementDetailsService();
        $distributorService = new DistributorService();
        // $merchantService = new MerchantService();

        $limit = 500;
        $orderBy = ['created' => 'DESC'];
        $title = $this->title;
        $pageNum = ceil($count / $limit);

        $payTypes = [
            'wxpay' => '微信支付',
            'wxpaypc' => '微信支付',
            'wxpayh5' => '微信支付',
            'wxpayjs' => '微信支付',
            'wxpayapp' => '微信支付',
            'wxpaypos' => '微信支付',
            'hfpay' => '微信支付',
            'adapay' => '微信支付',
            'alipay' => '支付寶',
            'alipayh5' => '支付寶',
            'alipayapp' => '支付寶',
            'alipaypos' => '支付寶',
            'point' => '積分支付',
            'deposit' => '余額支付',
        ];

        for ($page = 1; $page <= $pageNum; $page++) {
            $result = [];
            $list = $detailsService->getLists($filter, '*', $page, $limit, $orderBy);

            if (count($list) > 0) {
                $distributorList = $distributorService->getLists(['distributor_id' => array_column($list, 'distributor_id')], 'distributor_id,name');
                $distributorName = array_column($distributorList, 'name', 'distributor_id');

                // $merchantList = $merchantService->getLists(['id' => array_column($list, 'merchant_id')], 'id,merchant_name');
                // $merchantName = array_column($merchantList, 'merchant_name', 'id');
            }

            foreach ($list as $key => $value) {
                foreach ($title as $k => $v) {
                    switch ($k) {
                        case 'order_id':
                            $result[$key][$k] = "\"'".$value[$k]."\"";
                            break;
                        case 'total_fee':
                        case 'freight_fee':
                        case 'intra_city_freight_fee':
                        case 'refund_fee':
                        case 'statement_fee':
                            $result[$key][$k] = bcdiv($value[$k], 100, 2);
                            break;
                        case 'distributor_name':
                            $result[$key][$k] = $distributorName[$value['distributor_id']] ?? '-';
                            break;
                        case 'created':
                            $result[$key][$k] = date('Y-m-d H:i:s', $value['created']);
                            break;
                        case 'pay_type':
                            $result[$key][$k] = $payTypes[$value[$k]];
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
