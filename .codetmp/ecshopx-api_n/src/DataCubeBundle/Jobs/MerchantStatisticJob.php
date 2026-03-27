<?php

namespace DataCubeBundle\Jobs;

use DataCubeBundle\Services\MerchantDataService;
use EspierBundle\Jobs\Job;

class MerchantStatisticJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $params = $this->data;
        $companyDataService = new MerchantDataService();
        $companyDataService->runStatistics($params['company_id'], $params['id'], $params['count_date']);
    }
}
