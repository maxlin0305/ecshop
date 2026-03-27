<?php

namespace EspierBundle\Jobs;

use EspierBundle\Traits\GetExportServiceTraits;
use EspierBundle\Services\ExportLogService;

class ExportFileJob extends Job
{
    use GetExportServiceTraits;
    /**
     * 上传文件的基本信息
     */
    protected $type;
    protected $companyId;
    protected $operator_id;
    protected $exportFilter;

    public function __construct($type, $companyId, $filter, $operator_id = 0)
    {
        $this->type = $type;
        $this->companyId = $companyId;
        $this->operator_id = $operator_id;
        $this->exportFilter = $filter;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $filter = $this->exportFilter;
        $filter['company_id'] = $this->companyId;
        $exportType = $this->type;
        $exportService = $this->getService($exportType);
        $result = $exportService->exportData($filter);
        if ($result) {
            if (isset($filter['order_class']) && $filter['order_class'] == "drug") {
                $exportType = 'drug_order';
            }
            $data = [
                'export_type' => $exportType,
                'handle_status' => 'finish',
                'finish_time' => time(),
                'file_name' => $result['filename'],
                'file_url' => $result['url'],
                'company_id' => $filter['company_id'],
                'operator_id' => $this->operator_id,
                'merchant_id' => $filter['merchant_id'] ?? 0,
            ];
            $logData = $this->updateLog($data);
            if (!$logData) {
                app('log')->debug('队列导出: 导出日志完成状态更新失败');
            }
        } else {
            app('log')->debug('队列导出: 执行导出时失败');
        }
        return true;
    }

    private function updateLog($data)
    {
        $exportLogService = new ExportLogService();
        $logData = $exportLogService->create($data);
        return $logData;
    }
}
