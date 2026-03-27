<?php

namespace SelfserviceBundle\Services;

use SelfserviceBundle\Entities\RegistrationActivity;
use PromotionsBundle\Services\SmsService;

class RegistrationActivityService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(RegistrationActivity::class);
    }

    public function saveData($params, $filter = [])
    {
        $params['is_sms_notice'] = ($params['is_sms_notice'] ?? false) == 'true' ? true : false;
        $params['is_wxapp_notice'] = ($params['is_wxapp_notice'] ?? false) == 'true' ? true : false;
        if ($filter) {
            $result = $this->entityRepository->updateOneBy($filter, $params);
        } else {
            $result = $this->entityRepository->create($params);
        }
        $this->updateSmsTempStatus($result['company_id'], $result['is_sms_notice']);
        return $result;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    private function updateSmsTempStatus($companyId, $isOpen)
    {
        $templateName = 'registration_result_notice';
        $params['is_open'] = ($isOpen == 'true') ? 'true' : 'false';
        $smsService = new SmsService();
        $result = $smsService->updateTemplate($companyId, $templateName, $params);
        return true;
    }
}
