<?php

namespace DataCubeBundle\Jobs;

use EspierBundle\Jobs\Job;
use DataCubeBundle\Services\CompanyDataService;

class StatisticJob extends Job
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
        $companyDataService = new CompanyDataService();
        $companyDataService->runStatistics($params['company_id'], $params['count_date']);
    }
}
