<?php

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageService;

class sendFinishedFailNoticeJob extends Job
{
    public $companyId;
    public $orderId;

    public function __construct($companyId, $orderId)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $workWechatMessageService = new WorkWechatMessageService();
        $result = $workWechatMessageService->finishedFail($this->companyId, $this->orderId);
        return true;
    }
}
