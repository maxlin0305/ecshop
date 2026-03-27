<?php

namespace WorkWechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WorkWechatBundle\Services\WorkWechatMessageTemplateService;

class sendWaitingDeliveryNoticeJob extends Job
{
    public $companyId;
    public $orderId;
    public $distributorId;

    public function __construct($companyId, $orderId, $distributorId)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
        $this->distributorId = $distributorId;
    }

    public function handle()
    {
        $workWechatMessageTemplateService = new WorkWechatMessageTemplateService();
        $result = $workWechatMessageTemplateService->sendWaitingDeliveryNotice($this->companyId, $this->orderId, $this->distributorId);
        return true;
    }
}
