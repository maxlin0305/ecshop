<?php

namespace AliyunsmsBundle\Jobs;

use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class DeleteSmsTemplate extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $client = new AliyunSmsClient($this->params['company_id']);
        $result = $client->deleteSmsTemplate($this->params);
        return true;
    }
}
