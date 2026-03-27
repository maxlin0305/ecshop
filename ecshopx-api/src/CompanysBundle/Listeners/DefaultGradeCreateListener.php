<?php

namespace CompanysBundle\Listeners;

use CompanysBundle\Events\CompanyCreateEvent;
use KaquanBundle\Services\MemberCardService;

class DefaultGradeCreateListener
{
    /**
     * Handle the event.
     *
     * @param  WxShopsAddEvent  $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        $companyId = $event->entities['company_id'];

        $memberCardService = new MemberCardService();
        $defaultGrade = $memberCardService->getDefaultGradeByCompanyId($companyId);
        if ($defaultGrade) {
            return true;
        }

        $gradeData = [
            'company_id' => $companyId,
            'grade_name' => '普通会员',
            'default_grade' => true,
            'promotion_condition' => [
                'total_consumption' => 0
            ],
            'privileges' => [
                'discount' => 0,
                'discount_desc' => 0,
            ],
        ];
        return $memberCardService->setDefaultGrade($gradeData);
    }
}
