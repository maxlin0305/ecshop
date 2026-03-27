<?php

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageService;

class sendAfterSaleCancelNoticeJob extends Job
{
    public $companyId;
    public $afterSalesBn;

    public function __construct($companyId, $afterSalesBn)
    {
        $this->companyId = $companyId;
        $this->afterSalesBn = $afterSalesBn;
    }

    public function handle()
    {
        $workWechatMessageService = new WorkWechatMessageService();
        $result = $workWechatMessageService->afterSaleCancel($this->companyId, $this->afterSalesBn);
        return true;
    }
}
