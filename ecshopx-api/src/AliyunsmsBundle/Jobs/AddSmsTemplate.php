<?php

namespace AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Entities\Template;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class AddSmsTemplate extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $client = new AliyunSmsClient($this->params['company_id']);
        return $client->addSmsTemplate($this->params);
    }
}
