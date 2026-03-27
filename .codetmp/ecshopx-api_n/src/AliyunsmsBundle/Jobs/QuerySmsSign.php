<?php

namespace AliyunsmsBundle\Jobs;

use AliyunsmsBundle\Services\SignService;
use EspierBundle\Jobs\Job;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;

class QuerySmsSign extends Job
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $signService = new SignService();
        $client = new AliyunSmsClient($this->params['company_id']);
        $result = $client->querySmsSign($this->params);
        if(isset($result['SignStatus'])) {
            $filter = ['company_id' => $this->params['company_id'], 'sign_name' => $this->params['sign_name'], 'status' => 0];
            $updateData = ['status' => $result['SignStatus'], 'reason' => $result['Reason'] ?? ''];
            $signService->updateOneBy($filter, $updateData);
        }
        return true;
    }
}
