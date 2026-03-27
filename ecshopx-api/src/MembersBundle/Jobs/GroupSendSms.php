<?php

namespace MembersBundle\Jobs;

use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use PromotionsBundle\Services\SmsService;
use EspierBundle\Jobs\Job;

class GroupSendSms extends Job
{
    public $smsData;
    public function __construct($smsData)
    {
        $this->smsData = $smsData;
    }

    public function handle()
    {
        $smsData = $this->smsData;
        try {
            $companyId = $smsData['company_id'];
            $mobiles = $smsData['send_to_phones'];
            $content = $smsData['sms_content'];

            app('log')->debug('短信群发1: fan-out =>'.$companyId);
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);

            app('log')->debug('短信群发2: fan-out =>'.$shopexUid);
            $smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));

            // 下游供应商该接口不支持批量发短信，所以改成一次提交一个手机号
            foreach ($mobiles as $mobile) {
                $smsService->sendContent($companyId, $mobile, $content, 'fan-out');
            }
        } catch (\Exception $e) {
            app('log')->debug('短信群发失败: fan-out =>'.var_export($e->getMessage(), 1));
        }
    }
}
