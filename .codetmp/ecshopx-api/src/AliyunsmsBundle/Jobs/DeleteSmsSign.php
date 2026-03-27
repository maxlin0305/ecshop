<?php

namespace AliyunsmsBundle\Jobs;

use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class DeleteSmsSign extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $client = new AliyunSmsClient($this->params['company_id']);
        $result = $client->deleteSmsSign($this->params);
        return true;
    }
}
