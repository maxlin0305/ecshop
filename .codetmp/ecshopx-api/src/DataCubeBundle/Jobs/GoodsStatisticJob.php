<?php

namespace DataCubeBundle\Jobs;

use EspierBundle\Jobs\Job;
use DataCubeBundle\Services\GoodsDataService;

class GoodsStatisticJob extends Job
{
    public $order_ids;
    public $date;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($order_ids, $date)
    {
        $this->order_ids = $order_ids;
        $this->date = $date;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $companyDataService = new GoodsDataService();
        $companyDataService->runStatistics($this->order_ids, $this->date);
    }
}
