<?php

namespace EspierBundle\Services\Export;

use CommunityBundle\Services\CashWithdrawalService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class CashWithdrawalExportService implements ExportFileInterface
{
    private $title = [
        'created' => '申請時間',
        'bank_name' => '打款方式',
        'account_name' => '戶名',
        'bank_account' => '銀行卡號',
        'bank_address' => '開戶行信息',
        'account_mobile' => '手機號',
        'money' => '提現金額',
        'point' => '提現積分',
        'status' => '提現狀態',
    ];

    public function exportData($filter)
    {
        // TODO: Implement exportData() method.
        $cashWithdrawalService = new CashWithdrawalService();
        $count = $cashWithdrawalService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'community_withdraw';
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $orderList);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $field_title = $this->title;

        if ($count > 0) {
            $cashWithdrawalService = new CashWithdrawalService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $cashData = [];
                $data = $cashWithdrawalService->lists($filter, $page, $limit, ["created" => "DESC"]);

                foreach ($data['list'] as $key => $value) {
                    foreach ($field_title as $k => $v) {
                        if ($k == 'created') {
                            $cashData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif ($k == 'status') {
                            switch ($value[$k]) {
                                case 'apply':
                                    $title = '待處理';
                                    break;
                                case 'process':
                                    $title = '處理異常';
                                    break;
                                case 'success':
                                    $title = '提現完成';
                                    break;
                                case 'reject':
                                    $title = '以拒絕';
                                    break;
                                default:
                                    $title = '未知狀態';
                            }
                            $cashData[$key][$k] = $title;
                        } elseif ($k == 'money') {
                            $cashData[$key][$k] = 'NT$'.$value[$k] / 100;
                        } else {
                            $cashData[$key][$k] = $value[$k];
                        }
                    }
                }
                yield $cashData;
            }
        }
    }
}
