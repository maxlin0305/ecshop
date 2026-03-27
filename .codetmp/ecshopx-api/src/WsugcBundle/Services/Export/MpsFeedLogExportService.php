<?php
namespace WsugcBundle\Services\Export;


use WsugcBundle\Services\MpsFeedLogService;
use EspierBundle\Services\ExportFileService;
use EspierBundle\Interfaces\ExportFileInterface;

class MpsFeedLogExportService implements ExportFileInterface
{
    private $title = [
        'log_id' => '日志id',
        'file_name'=>'文件名',
        'unique_id' => 'unique_id',
        'bn'=>'货号',
        'line' => '行号',
        'status_text'=>'状态',
        'message'=>'错误原因',
        'created_time_text'=>'创建时间'
    ];

    public function exportData($filter)
    {

        app('log')->debug('导出: _mpsfeed_log-exportData =>'.var_export($filter,true));


        $scashWithdrawalService = new MpsFeedLogService();
        $count = $scashWithdrawalService->count($filter);


        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').'_mpsfeed_logs';
        $datalist = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $this->title, $datalist);
        return $result;
    }

    private function getLists($filter, $count)
    {
        $title = $this->title;

        if ($count > 0) {
            $scashWithdrawalService = new MpsFeedLogService();

            $limit = 500;
            $fileNum = ceil($count/$limit);
            $allApiCat=$scashWithdrawalService->getApiCat();
            for ($page = 1; $page <= $fileNum; $page++) {
                $recordData = [];
                $data = $scashWithdrawalService->getLogsList($filter,'*', $page, $limit, ["log_id" => "DESC"]);


                app('log')->debug('导出: _mpsfeed_log =>'.var_export($data,true));

                foreach ($data['list'] as $key=>$value) {
                    $setting = [];
                    foreach ($title as $k => $v) {
                        if ($k == 'response_time') {
                            //
                            $recordData[$key][$k] = date('Y-m-d H:i:s',$value['response_time']);
                        }
                        elseif ($k == 'request_type') {
                            //
                            if($value['request_type']=='request'){
                                $recordData[$key][$k] ='请求';
                            }
                            else{
                                $recordData[$key][$k] ='响应';
                            }

                        }
                        elseif ($k == 'cat_id') {
                            $recordData[$key][$k]=$allApiCat[$value['cat_id']]?$allApiCat[$value['cat_id']]['cat_name']:$value['cat_id'];
                        }
                        else {
                            $recordData[$key][$k] = $value[$k] ?? '';
                        }
                    }
                }
                yield $recordData;
            }
        }
    } 
}
