<?php

namespace EspierBundle\Services\Export;

use SelfserviceBundle\Services\RegistrationRecordService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class RegistrationRecordExportService implements ExportFileInterface
{
    private $title = [
        'record_id' => '報名申請編號',
        'mobile' => '會員手機號',
        'activity_name' => '活動名稱',
        'created' => '申請時間',
        'content' => '申請內容',
        'review_result' => '審核結果',
        'reason' => '拒絕原因',
    ];

    public function exportData($filter)
    {
        // 是否需要数据脱敏 1:是 0:否
        $datapassBlock = $filter['datapass_block'];
        unset($filter['datapass_block']);
        $scashWithdrawalService = new RegistrationRecordService();
        $count = $scashWithdrawalService->count($filter);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_registration_record';
        $datalist = $this->getLists($filter, $count, $datapassBlock);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count, $datapassBlock)
    {
        $title = $this->title;

        if ($count > 0) {
            $scashWithdrawalService = new RegistrationRecordService();

            $limit = 500;
            $fileNum = ceil($count / $limit);

            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $scashWithdrawalService->getRocordList($filter, $page, $limit, ["created" => "DESC"]);
                foreach ($data['list'] as $key => $value) {
                    $string = [];
                    $conten = is_array($value['content']) ? $value['content'] : json_decode($value['content'], true);
                    if ($datapassBlock) {
                        $value['mobile'] = data_masking('mobile', (string) $value['mobile']);
                        $conten = $scashWithdrawalService->fixeddecryptRocordContent($conten, $datapassBlock);
                    }
                    foreach ($conten as $card) {
                        foreach ($card['formdata'] as $line) {
                            if (isset($line['answer']) && is_array($line['answer'])) {
                                $answer = implode(';', $line['answer']);
                            } else {
                                $answer = isset($line['answer']) && $line['answer'] ? $line['answer'] : '无';
                            }
                            $string[] = $line['field_title']. "：".$answer;
                        }
                    }
                    $contentStr = implode(';'.PHP_EOL, $string);
                    foreach ($title as $k => $v) {
                        if ($k == 'created') {
                            $recordData[$key][$k] = date('Y-m-d H:i:s', $value[$k]);
                        } elseif ($k == 'content') {
                            $recordData[$key][$k] = $contentStr;
                        } elseif ($k == 'review_result') {
                            $recordData[$key][$k] = ($value['status'] == 'passed') ? '已通過' : ($value['status'] == 'rejected' ? '已拒絕' : '待審核');
                        } else {
                            $recordData[$key][$k] = $value[$k] ?? '';
                        }
                    }
                }
                yield $recordData;
            }
        }
    }
}
