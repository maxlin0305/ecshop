<?php

namespace PromotionsBundle\Services;

use AliyunsmsBundle\Services\SettingService;
use CompanysBundle\Services\CompanysService;
use PromotionsBundle\Entities\SmsIdiograph;
use PromotionsBundle\Entities\SmsTemplate;

use PromotionsBundle\Services\SmsDriver\ShopexSmsClient;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 短信服務
 */
class SmsManagerService
{
    public $smsService;
    public function __construct($companyId)
    {
        $this->getSmsService($companyId);
    }
    public function getSmsService($companyId)
    {
        $this->smsService = new SmsService();
        return;
        $service = new SettingService();
        $aliyunsmsStatus = $service->getStatus($companyId);
        if($aliyunsmsStatus) {
            $this->smsService = new \AliyunsmsBundle\Services\SmsService($companyId);
        } else {
            $companysService = new CompanysService();
            $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
            $this->smsService = new SmsService(new ShopexSmsClient($companyId, $shopexUid));
        }
    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->smsService->$method(...$parameters);
    }
}
