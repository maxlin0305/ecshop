<?php

namespace EspierBundle\Services\Export;

use EspierBundle\Interfaces\ExportFileInterface;
use OrdersBundle\Traits\GetOrderServiceTrait;
use EspierBundle\Services\ExportFileService;
use OrdersBundle\Services\TradeService;

class InvoicesExportService implements ExportFileInterface
{
    use GetOrderServiceTrait;

    public function exportData($filter)
    {
        $orderService = $this->getOrderService('normal');
        $count = $orderService->countOrderNum($filter);
        if (!$count) {
            return [];
        }

        $fileName = date('YmdHis').$filter['company_id']."invoice";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count);
        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        return [
            'order_id' => '订单号',
            'trade_no'=> '订单序号',
            'item_name' => '商品名称',
            'item_attr' => '规格',
            'item_num' => '数量',
            'total_price' => '总价（元）',
            'content' => '发票抬头',
            'bankname' => '开户银行',
            'bankaccount' => '银行账户',
            'company_phone' => '电话号码',
            'company_address' => '单位地址',
            'registration_number' => '税号',
        ];
    }

    public function getLists($filter, $totalCount = 10000)
    {
        $limit = 1000;
        $fileNum = ceil($totalCount / $limit);
        $orderService = $this->getOrderService('normal');
        $orderBy = ['distributor_id' => 'desc', 'order_id' => 'desc', 'create_time' => 'asc'];
        $orderList = [];
        $tradeService = new TradeService();
        for ($j = 1; $j <= $fileNum; $j++) {
            $orderdata = $orderService->getOrderList($filter, $j, $limit, $orderBy, false)['list'];
            $orderIdList = array_column($orderdata, 'order_id');
            $tradeIndex = $tradeService->getTradeIndexByOrderIdList($filter['company_id'], $orderIdList);

            foreach ($orderdata as $newData) {
                if (isset($newData['invoice'])) {
                    $invoicearr = is_array($newData['invoice']) ? $newData['invoice'] : json_decode($newData['invoice'], true);
                    $newData = array_merge($newData, $invoicearr);
                }

                foreach ($newData['items'] as $item) {
                    $orderList[] = [
                        'order_id'=> "\"'".$newData['order_id']."\"",
                        'trade_no' => $tradeIndex[$newData['order_id']] ?? '-',
                        'item_name' => $item['item_name'],
                        'item_attr' => $item['item_spec_desc'],
                        'item_num' => $item['num'],
                        'total_price' => bcdiv($item['total_fee'], 100, 2),
                        'content' => $newData['content'] ?? '',
                        'bankname' => $newData['bankname'] ?? '',
                        'bankaccount' => empty($newData['bankaccount']) ? '' : "\t".$newData['bankaccount']."\t",
                        'company_phone' => $newData['company_phone'] ?? '',
                        'company_address' => $newData['company_address'] ?? '',
                        'registration_number' => $newData['registration_number'] ?? '',
                    ];
                }
            }
            yield $orderList;
        }
    }
}
